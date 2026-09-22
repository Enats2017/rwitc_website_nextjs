<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

// Load database and security
require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/config/run_races_config.php";
require_once __DIR__ . "/ApiSecurity.php";

// API SECURITY / LOG
// --------------------------------------------------

if (!class_exists("ApiSecurity")) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "data"    => null,
        "error"   => "ApiSecurity class could not be loaded"
    ]);

    exit;
}

$logDir = __DIR__ . "/logs";

if (!is_dir($logDir)) {
    @mkdir($logDir, 0750, true);
}

$handle = @fopen(
    $logDir . "/api_logs.txt",
    "a+"
);

$security = new ApiSecurity($handle, [
    "rate_limit"     => 60,
    "rate_window"    => 60,
    "cache_ttl"      => 120,
    "cache_dir"      => __DIR__ . "/cache",
    "rate_limit_dir" => __DIR__ . "/rate_limits",
    "api_tag"        => "declarations_get"
]);

if (!$security->gate()) {
    if (isset($conn)) {
        $conn->close();
    }

    if (is_resource($handle)) {
        fclose($handle);
    }

    exit;
}

// --------------------------------------------------
// HELPER: S3 URL / KEY -> S3 OBJECT KEY
// --------------------------------------------------

function declarationsS3Key($fileUrl)
{
    $fileUrl = trim((string) $fileUrl);

    if ($fileUrl === "") {
        return "";
    }

    if (preg_match("/^https?:\/\//i", $fileUrl)) {
        $path = parse_url($fileUrl, PHP_URL_PATH);
        $fileUrl = $path !== null ? $path : "";
    }

    return ltrim(
        rawurldecode($fileUrl),
        "/"
    );
}

// --------------------------------------------------
// HELPER: EXISTING S3 HTML READER
// Same flow as Handicaps / Acceptance
// --------------------------------------------------

function getS3BucketGetApiUrl()
{
    if (
        defined("S3_BUCKET_GET_API_URL") &&
        S3_BUCKET_GET_API_URL !== ""
    ) {
        return rtrim(S3_BUCKET_GET_API_URL, "/");
    }

    $scheme = "https";

    if (
        isset($_SERVER["HTTPS"]) &&
        $_SERVER["HTTPS"] !== "off"
    ) {
        $scheme = "https";
    } elseif (
        isset($_SERVER["REQUEST_SCHEME"]) &&
        $_SERVER["REQUEST_SCHEME"] !== ""
    ) {
        $scheme = $_SERVER["REQUEST_SCHEME"];
    }

    $host = $_SERVER["HTTP_HOST"] ?? "";

    if ($host === "") {
        return "";
    }

    $scriptDir = dirname($_SERVER["SCRIPT_NAME"] ?? "");

    if ($scriptDir === "." || $scriptDir === "/") {
        $scriptDir = "";
    }

    return $scheme . "://" . $host . $scriptDir . "/s3_set_url.php";
}

function readHtmlFromS3BucketApi($fileUrl)
{
    $s3Key = declarationsS3Key($fileUrl);

    if (
        $s3Key === "" ||
        strpos($s3Key, "run_races/") !== 0 ||
        strtolower(pathinfo($s3Key, PATHINFO_EXTENSION)) !== "html"
    ) {
        throw new Exception(
            "Invalid declarations S3 file key"
        );
    }

    $helperUrl = getS3BucketGetApiUrl();

    if ($helperUrl === "") {
        throw new Exception(
            "S3 HTML helper URL is not configured"
        );
    }

    $requestUrl =
        $helperUrl .
        "?key=" .
        rawurlencode($s3Key);

    $ch = curl_init($requestUrl);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt(
        $ch,
        CURLOPT_USERAGENT,
        "RWITC Declarations S3 Reader"
    );

    $content = curl_exec($ch);

    if ($content === false) {
        $curlError = curl_error($ch);
        curl_close($ch);

        throw new Exception(
            "S3 HTML helper cURL failed: " . $curlError
        );
    }

    $httpCode = curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );

    curl_close($ch);

    if (
        $httpCode < 200 ||
        $httpCode >= 300
    ) {
        throw new Exception(
            "S3 HTML helper returned HTTP " .
            $httpCode
        );
    }

    if (
        $content === "" ||
        trim((string) $content) === ""
    ) {
        throw new Exception(
            "S3 HTML helper returned empty content"
        );
    }

    return $content;
}

// --------------------------------------------------
// ERP PUSH MODE (POST)
// --------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        $postDate = isset($_POST["date"])
            ? trim($_POST["date"])
            : "";

        $postType = isset($_POST["type"])
            ? trim($_POST["type"])
            : "";

        $postRaceType = isset($_POST["race_type"])
            ? trim($_POST["race_type"])
            : "";

        $postHtmlFile = isset($_POST["html_file"])
            ? trim($_POST["html_file"])
            : "";

        $postFileUrl = isset($_POST["file_url"])
            ? trim($_POST["file_url"])
            : "";

        if (
            $postDate === "" ||
            $postType === "" ||
            $postRaceType === "" ||
            $postHtmlFile === ""
        ) {
            $security->respondError(
                "date, type, race_type and html_file are required",
                400
            );

            exit;
        }

        $postDateObj = DateTime::createFromFormat(
            "Y-m-d",
            $postDate
        );

        if (
            !$postDateObj ||
            $postDateObj->format("Y-m-d") !== $postDate
        ) {
            $security->respondError(
                "Invalid date format, expected YYYY-MM-DD",
                400
            );

            exit;
        }

        // Store the real S3 URL returned by S3Uploader.
        // If URL is not supplied, keep the S3 key as fallback.
        $storedFileUrl =
            $postFileUrl !== ""
                ? $postFileUrl
                : $postHtmlFile;

        $checkStmt = $conn->prepare("
            SELECT file_url
            FROM run_race_details
            WHERE `date` = ?
              AND `type` = ?
              AND `race_type` = ?
            LIMIT 1
        ");

        if ($checkStmt === false) {
            throw new Exception($conn->error);
        }

        $checkStmt->bind_param(
            "sss",
            $postDate,
            $postType,
            $postRaceType
        );

        if (!$checkStmt->execute()) {
            throw new Exception($conn->error);
        }

        $checkResult = $checkStmt->get_result();

        $exists =
            $checkResult &&
            $checkResult->num_rows > 0;

        $checkStmt->close();

        if ($exists) {

            $updateStmt = $conn->prepare("
                UPDATE run_race_details
                SET file_url = ?
                WHERE `date` = ?
                  AND `type` = ?
                  AND `race_type` = ?
            ");

            if ($updateStmt === false) {
                throw new Exception($conn->error);
            }

            $updateStmt->bind_param(
                "ssss",
                $storedFileUrl,
                $postDate,
                $postType,
                $postRaceType
            );

            if (!$updateStmt->execute()) {
                throw new Exception($conn->error);
            }

            $updateStmt->close();

        } else {

            $insertStmt = $conn->prepare("
                INSERT INTO run_race_details
                (`date`, `type`, `file_url`, `race_type`)
                VALUES (?, ?, ?, ?)
            ");

            if ($insertStmt === false) {
                throw new Exception($conn->error);
            }

            $insertStmt->bind_param(
                "ssss",
                $postDate,
                $postType,
                $storedFileUrl,
                $postRaceType
            );

            if (!$insertStmt->execute()) {
                throw new Exception($conn->error);
            }

            $insertStmt->close();
        }

        $security->logLine(
            "DECLARATIONS_PUSH_SUCCESS | date=" . $postDate
                . " | type=" . $postType
                . " | race_type=" . $postRaceType
                . " | file_url=" . $storedFileUrl
        );

        $security->respondSuccess([
            "date"      => $postDate,
            "type"      => $postType,
            "race_type" => $postRaceType,
            "html_file" => $postHtmlFile,
            "file_url"  => $storedFileUrl
        ]);

    } catch (Throwable $error) {

        $security->logLine(
            "DECLARATIONS_PUSH_ERROR | " . $error->getMessage()
        );

        $security->respondError(
            "Internal server error",
            500
        );
    } finally {

        if (isset($conn)) {
            $conn->close();
        }

        if (isset($handle) && is_resource($handle)) {
            fclose($handle);
        }
    }

    exit;
}

// --------------------------------------------------
// ONLY ALLOW GET
// --------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] !== "GET") {

    $security->respondError(
        "Method not allowed",
        405
    );

    if (isset($conn)) {
        $conn->close();
    }

    if (is_resource($handle)) {
        fclose($handle);
    }

    exit;
}

// --------------------------------------------------
// READ + VALIDATE QUERY PARAMS
// --------------------------------------------------

$date = isset($_GET["date"])
    ? trim($_GET["date"])
    : "";

$type = isset($_GET["type"])
    ? trim($_GET["type"])
    : "";

$raceType = isset($_GET["race_type"])
    ? trim($_GET["race_type"])
    : "";

if ($date === "") {
    $security->respondError(
        "date is required (format YYYY-MM-DD)",
        400
    );
    exit;
}


$d = DateTime::createFromFormat(
    "Y-m-d",
    $date
);

if (!$d || $d->format("Y-m-d") !== $date) {
    $security->respondError(
        "Invalid date format, expected YYYY-MM-DD",
        400
    );
    exit;
}

// --------------------------------------------------
// RESOLVE MISSING RACE TYPE DYNAMICALLY
// --------------------------------------------------
// The frontend may send only date + type.
// Resolve race_type from the registered DB row.
// No document-specific race_type is hard-coded in this API.

if ($raceType === "" && $type !== "") {

    $metaStmt = $conn->prepare("
        SELECT `race_type`
        FROM run_race_details
        WHERE `date` = ?
          AND `type` = ?
          AND race_type IS NOT NULL
          AND race_type <> ''
        ORDER BY id DESC
        LIMIT 1
    ");

    if ($metaStmt !== false) {
        $metaStmt->bind_param("ss", $date, $type);

        if ($metaStmt->execute()) {
            $metaResult = $metaStmt->get_result();

            if ($metaResult && $metaResult->num_rows > 0) {
                $metaRow = $metaResult->fetch_assoc();
                $raceType = trim((string) $metaRow["race_type"]);
            }
        }

        $metaStmt->close();
    }
}

// ============================================================
// DOWNLOAD MODE
//
// ?date=2026-08-22&type=declarations&race_type=pre_race&download=1
//
// Source priority:
//   1. Old local run_races HTML file
//   2. New S3 file registered in run_race_details
// ============================================================

if (
    isset($_GET["download"]) &&
    $_GET["download"] === "1" &&
    $date > "2022-09-25"
) {

    try {

        $downloadContent = false;

        // ---------- 1. LOCAL HTML ----------
        $localFile =
            RUN_RACES_LOCAL_PATH .
            "/Declarations_" .
            $date .
            ".html";

        if (is_file($localFile)) {
            $downloadContent =
                file_get_contents($localFile);
        }

        // ---------- 2. PRIVATE S3 HTML ----------
        // Same S3 reader flow as Handicaps / Acceptance.
        if ($downloadContent === false) {

            if ($type === "" || $raceType === "") {
                throw new Exception("type and race_type are required when the local declarations file is not available");
            }

            $stmt = $conn->prepare("
                SELECT file_url
                FROM run_race_details
                WHERE `date` = ?
                  AND `type` = ?
                  AND `race_type` = ?
                  AND file_url IS NOT NULL
                  AND file_url <> ''
                LIMIT 1
            ");

            if ($stmt === false) {
                throw new Exception($conn->error);
            }

            $stmt->bind_param(
                "sss",
                $date,
                $type,
                $raceType
            );

            if (!$stmt->execute()) {
                throw new Exception($conn->error);
            }

            $result = $stmt->get_result();

            if (!$result || $result->num_rows === 0) {

                $stmt->close();

                $security->respondError(
                    "Declarations file not found for this date",
                    404
                );

                exit;
            }

            $row = $result->fetch_assoc();

            $stmt->close();

            $downloadContent =
                readHtmlFromS3BucketApi(
                    $row["file_url"] ?? ""
                );
        }

        if (
            $downloadContent === false ||
            trim((string) $downloadContent) === ""
        ) {
            throw new Exception(
                "Declarations HTML file is empty"
            );
        }

        // ------------------------------------------------------
        // Add the same archive CSS used by Declarations.js.
        // This makes the opened/downloaded HTML page render
        // correctly even outside the NextJS iframe.
        // ------------------------------------------------------

        if (
            stripos(
                (string) $downloadContent,
                "<html"
            ) === false
        ) {

            $downloadCss = <<<'CSS'
* { box-sizing: border-box; }
body { font-family: Arial, sans-serif; margin: 0; padding: 12px; color: #333333; }
span, a { display: inline-block; text-decoration: none; color: #333333; }
img { vertical-align: middle; max-width: 100%; }
h1 { margin: unset !important; font-size: 26px !important; }
h3 { font-family: 'Roboto Condensed', Arial, sans-serif; font-size: 32px; color: #c1c1c1; margin: 10px 0; }
.pageHeading { text-align: center; }

table { border-collapse: collapse; width: 100%; }
.table { width: 100%; max-width: 100%; margin-bottom: 1rem; background-color: transparent; }
.table-bordered { font-weight: bold; border: 1px solid #BCBEC0; }
.table-bordered th, .table-bordered td { border: 1px solid #BCBEC0; }

th {
    color: #ffffff !important;
    font-size: 14px;
    text-align: center;
    padding: 6px;
    border: 1px solid #BCBEC0;
    border-radius: 10px;
    background: #11a14e;
}

td {
    text-align: left !important;
    padding: 6px !important;
    color: #333333 !important;
    font-weight: 600;
}

tbody > tr > th { text-align: left !important; }
tbody tr td:nth-child(2) { text-align: center !important; }
tbody tr td:nth-child(3) { text-align: center !important; }

.darkGrey_old { font-size: 14px; color: black; text-align: center; font-weight: bold; }
.darkGrey { font-size: 14px; color: white; text-align: center; font-weight: bold; }
.white { background-color: #ffffff; color: black !important; }

.download,
.pageHeader .pageHeading .subHeading .download {
    display: none !important;
}

#leftArea .pageHeader .pageHeading .subHeading {
    clear: both;
    float: left;
    width: 100%;
    color: #000;
    font-weight: bold;
    text-align: center;
    font-size: 12px;
    margin: 5px 0;
    padding: 5px 0;
}

.block { display: none; }
.hide { display: block !important; }

.tbbody { margin-bottom: 4%; margin-top: -2%; }
.padd { padding: 1%; }

.show1 { display: none; }

.left, .left1 { text-align: left !important; }

@media (max-width: 500px) {
    .text_size { font-size: 8px; }
    #leftArea { padding: 0 !important; }
    .download { float: unset !important; margin-bottom: 10px; }
    td { padding: 4px !important; }
    .table-bordered { border: unset; }
    .text_size { border: unset !important; }
    .hide { display: none !important; }
    .myhead { display: none; }
    .block { display: block; }
    .nm { text-align: center !important; }

    .perform_data {
        display: contents !important;
        border: 1px solid #cdced3 !important;
        border-radius: 12px;
        background: #cdced3 !important;
        margin-bottom: 5%;
        padding: 10px !important;
    }

    .perform_data td:first-child { padding-left: 10px; }

    .perform_data td:before {
        content: attr(data-label);
        float: left;
        font-size: 11px;
        text-transform: uppercase;
        font-weight: bold;
        width: 45%;
    }

    .perform_data td {
        font-size: 10px !important;
        position: relative;
        border: unset !important;
    }

    .poolsTable tr:nth-child(1) th {
        border: unset !important;
        border-top-left-radius: 10px;
        border-top-right-radius: 10px;
    }

    .poolsTable tr th span { margin-left: 6%; }
    .poolsTable tr td { border: unset !important; padding-left: 30px !important; }
    .poolsTable { background-color: #cdced3 !important; border-radius: 10px !important; }

    .tbbody { margin-top: -8% !important; }
    .bot10 { margin-bottom: 20px !important; }

    .tabhead {
        border: 1px solid #cdced3 !important;
        background: #cdced3 !important;
        text-align: center !important;
    }

    .tabpads {
        text-align: center !important;
        font-size: 12px !important;
    }

    .darkGrey { font-size: 12px !important; }
}

@media (min-width: 320px) and (max-width: 375px) {
    td { padding: 2px !important; }
}
CSS;

            $downloadContent =
                "<!DOCTYPE html>\n"
                . "<html>\n"
                . "<head>\n"
                . "<meta charset=\"UTF-8\">\n"
                . "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n"
                . "<title>Declarations " . htmlspecialchars($date, ENT_QUOTES, "UTF-8") . "</title>\n"
                . "<style>\n"
                . $downloadCss
                . "\n</style>\n"
                . "</head>\n"
                . "<body>\n"
                . $downloadContent
                . "\n</body>\n"
                . "</html>";
        }

        header("Content-Type: text/html; charset=UTF-8");
        header(
            'Content-Disposition: inline; filename="Declarations_' .
            $date .
            '.html"'
        );

        echo $downloadContent;

    } catch (Throwable $error) {

        $security->logLine(
            "DECLARATIONS_DOWNLOAD_ERROR | " .
            $error->getMessage()
        );

        $security->respondError(
            "Internal server error",
            500
        );

    } finally {

        if (isset($conn)) {
            $conn->close();
        }

        if (isset($handle) && is_resource($handle)) {
            fclose($handle);
        }
    }

    exit;
}

// ============================================================
// HTML ARCHIVE SUPPORT (date > 2022-09-25)
// ============================================================

if ($date > "2022-09-25") {

    try {

        /*
         * FIRST: OLD LOCAL FILE
         * Keep old run_races behaviour unchanged.
         */
        $htmlFile =
            RUN_RACES_LOCAL_PATH .
            "/Declarations_" .
            $date .
            ".html";

        $htmlContent = false;
        $source = "";

        if (is_file($htmlFile)) {

            $source = "LOCAL_RUN_RACES";

            $htmlContent =
                file_get_contents($htmlFile);

            if ($htmlContent === false) {
                throw new Exception(
                    "Unable to read local declarations file: " .
                    $htmlFile
                );
            }
        }

        /*
         * SECOND: NEW S3 FILE
         * Local file missing -> DB exact date/type/race_type
         * -> existing s3_set_url.php helper -> private S3.
         */
        if ($htmlContent === false) {

            if ($type === "" || $raceType === "") {
                throw new Exception("type and race_type are required when the local declarations file is not available");
            }

            $source = "DB_S3";

            $stmt = $conn->prepare("
                SELECT file_url
                FROM run_race_details
                WHERE `date` = ?
                  AND `type` = ?
                  AND `race_type` = ?
                  AND file_url IS NOT NULL
                  AND file_url <> ''
                LIMIT 1
            ");

            if ($stmt === false) {
                throw new Exception($conn->error);
            }

            $stmt->bind_param(
                "sss",
                $date,
                $type,
                $raceType
            );

            if (!$stmt->execute()) {
                throw new Exception($conn->error);
            }

            $result = $stmt->get_result();

            if (!$result || $result->num_rows === 0) {

                $stmt->close();

                $security->respondError(
                    "No declarations data found for this date",
                    404
                );

                exit;
            }

            $row = $result->fetch_assoc();

            $stmt->close();

            $htmlContent =
                readHtmlFromS3BucketApi(
                    $row["file_url"] ?? ""
                );
        }

        if (
            $htmlContent === false ||
            trim((string) $htmlContent) === ""
        ) {
            throw new Exception(
                "Declarations HTML file is empty"
            );
        }

        /*
         * Page content is available from either local .html or S3.
         * S3 URL is never exposed to the frontend.
         */
        $downloadAvailable = true;

        $baseParts =
            parse_url(RUN_RACES_BASE_URL);

        $origin = "";

        if (
            isset($baseParts["scheme"]) &&
            isset($baseParts["host"])
        ) {
            $origin =
                $baseParts["scheme"] .
                "://" .
                $baseParts["host"] .
                (
                    isset($baseParts["port"])
                        ? ":" . $baseParts["port"]
                        : ""
                );
        }

        $downloadFile =
            $origin .
            $_SERVER["SCRIPT_NAME"] .
            "?date=" . urlencode($date) .
            "&type=" . urlencode($type) .
            "&race_type=" . urlencode($raceType) .
            "&download=1";

        $response = [
            "found"              => true,
            "date"               => $date,
            "mode"               => "html",
            "source"             => $source,
            "html"               => $htmlContent,
            "download_file"      => $downloadFile,
            "download_available" => $downloadAvailable
        ];

        // IMPORTANT:
        // Do NOT cache HTML archive mode.
        // This keeps the same local-first/S3 fallback behaviour
        // as the fixed Handicaps and Acceptance APIs.
        $security->respondSuccess($response);

    } catch (Throwable $error) {

        $security->logLine(
            "DECLARATIONS_HTML_READ_ERROR | " .
            $error->getMessage()
        );

        $security->respondError(
            "Internal server error",
            500
        );

    } finally {

        if (isset($conn)) {
            $conn->close();
        }

        if (isset($handle) && is_resource($handle)) {
            fclose($handle);
        }
    }

    exit;
}

// CACHE KEY (per date - historical data never changes)
// --------------------------------------------------

$cacheKey = "declarations_" . md5($date);

if ($security->serveCache($cacheKey)) {
    exit;
}

// --------------------------------------------------
// HELPERS
// --------------------------------------------------

function poolLabel($fieldName)
{
    $labels = [
        "FLDSTR1"  => "JACKPOT POOL RACES",
        "FLDSTR2"  => "FIRST JACKPOT POOL RACES",
        "FLDSTR3"  => "SECOND JACKPOT POOL RACES",
        "FLDSTR4"  => "TREBLE POOL RACES",
        "FLDSTR5"  => "FIRST TREBLE POOL RACES",
        "FLDSTR6"  => "SECOND TREBLE POOL RACES",
        "FLDSTR7"  => "THIRD TREBLE POOL RACES",
        "FLDSTR8"  => "FOURTH TREBLE POOL RACES",
        "FLDSTR9"  => "TANALA POOL RACES",
        "FLDSTR10" => "QUARTET POOL RACES",
        "FLDSTR11" => "SUPER JACKPOT POOL RACES",
        "FLDSTR12" => "FIRST SUPER JACKPOT POOL RACES",
        "FLDSTR13" => "SECOND SUPER JACKPOT POOL RACES",
        "FLDSTR14" => "DOUBLE FORECAST POOL RACES",
        "FLDSTR15" => "DOUBLE FORECAST POOL RACES",
    ];

    return $labels[$fieldName] ?? $fieldName;
}

function weightAdjustment($value, $stageLabel)
{
    if ($value === null || $value === "" || (float) $value == 0.0) {
        return null;
    }

    return [
        "stage"     => $stageLabel,
        "direction" => $value > 0 ? "raised" : "lowered",
        "kg"        => abs((float) $value),
    ];
}

function jockeyAllowance($category)
{
    $map = [
        "A" => 5,
        "B" => 3.5,
        "C" => 2.5,
        "D" => 1.5,
    ];

    return $map[$category] ?? null;
}

// --------------------------------------------------
// FETCH DATA
// --------------------------------------------------

try {

    // ---- Day narrative (cheap, indexed LIMIT 1 lookup) ----

    $dayNarr = "";

    $stmt = $conn->prepare(
        "SELECT DAYNARR FROM prospect WHERE `DATE` = ? AND DAYNARR <> '' LIMIT 1"
    );
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $narrResult = $stmt->get_result();
    if ($narrRow = $narrResult->fetch_assoc()) {
        $dayNarr = $narrRow["DAYNARR"];
    }
    $stmt->close();

    // ---- Race-level headers ----
    // One grouped query instead of joining the full fdecl table and
    // collapsing duplicate rows in PHP. RTIME/DIV/RACENO_SEA are constant
    // per race, so MIN() over the grouped fdecl rows is a safe,
    // deterministic pick. Note: DIV is a reserved word in MariaDB/MySQL
    // (the integer division operator) so it must always be backticked.

    $raceHeaders = [];

    $stmt = $conn->prepare(
        "SELECT p.SRNO, p.NAME AS RACENAME, p.DAYNARR, p.NARRENT, p.DISTANCE, p.FJOCK, p.HTERMS,
                p.RACECAT, p.GRADE, p.RAISELOWER, p.RAISEACP1, p.RAISEACP2, p.RAISEACP3,
                p.RACETIME1, p.RACETIME2, p.VOID_HACP, p.VOID_ACCP,
                rh.RACENO, rh.RTIME, rh.`DIV`, rh.RACENO_SEA
         FROM prospect p
         INNER JOIN (
             SELECT RACENO, MIN(LINK) AS LINK, MIN(RTIME) AS RTIME,
                    MIN(`DIV`) AS `DIV`, MIN(RACENO_SEA) AS RACENO_SEA
             FROM fdecl
             WHERE RACEDATE = ?
             GROUP BY RACENO
         ) rh ON rh.LINK = p.SRNO
         WHERE p.`DATE` = ?
         ORDER BY rh.RACENO ASC"
    );
    $stmt->bind_param("ss", $date, $date);
    $stmt->execute();
    $headerResult = $stmt->get_result();
    while ($row = $headerResult->fetch_assoc()) {
        $raceHeaders[$row["RACENO"]] = $row;
    }
    $stmt->close();

    if (empty($raceHeaders)) {
        $security->respondAndCache($cacheKey, [
            "found"   => false,
            "message" => "No declarations found for {$date}",
            "races"   => [],
        ]);
        exit;
    }

    // ---- All horse/declaration rows for the whole date in one query ----
    // Replaces the original per-race fdecl query executed inside the races
    // loop (N+1 queries -> 1 query), grouped by RACENO in PHP below.

    $horsesByRace = [];

    $stmt = $conn->prepare(
        "SELECT f.RACENO, f.NAME, f.WEIGHT, f.CARDNO, f.TRN_NM, f.JOCKEYNM, f.CATEGORY,
                f.HORSEWT, f.SHOE, f.DRAWNO, f.HORSESEQ, f.RATING, h.SIRE, h.DAM, h.DAMNAT
         FROM fdecl f
         INNER JOIN hmaster h ON f.HORSESEQ = h.HORSESEQ
         WHERE f.RACEDATE = ?
         ORDER BY f.RACENO ASC, f.CARDNO ASC"
    );
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $horseResult = $stmt->get_result();

    while ($row = $horseResult->fetch_assoc()) {
        $raceNo = $row["RACENO"];

        if (!isset($horsesByRace[$raceNo])) {
            $horsesByRace[$raceNo] = [];
        }

        $rating = $row["RATING"];
        if ($rating == -99) {
            $rating = "NR";
        }

        $horseWt = $row["HORSEWT"];
        if ($horseWt == 0) {
            $horseWt = null;
        }

        $drawNo = $row["DRAWNO"];
        if ($drawNo == 0) {
            $drawNo = null;
        }

        $breeding = $row["SIRE"] . " - " . $row["DAM"];
        if (!empty($row["DAMNAT"])) {
            $breeding .= " (" . $row["DAMNAT"] . ")";
        }

        $horsesByRace[$raceNo][] = [
            "card_no"          => $row["CARDNO"],
            "name"             => $row["NAME"],
            "horseseq"         => $row["HORSESEQ"],
            "weight"           => $row["WEIGHT"],
            "rating"           => $rating,
            "sire"             => $row["SIRE"],
            "dam"              => $row["DAM"],
            "dam_nation"       => $row["DAMNAT"],
            "breeding"         => $breeding,
            "trainer"          => $row["TRN_NM"],
            "jockey"           => $row["JOCKEYNM"],
            "jockey_category"  => $row["CATEGORY"],
            "jockey_allowance" => jockeyAllowance($row["CATEGORY"]),
            "horse_weight"     => $horseWt,
            "shoe"             => $row["SHOE"],
            "draw_no"          => $drawNo,
        ];
    }
    $stmt->close();

    // ---- Pools (single row keyed by FLDSTR1..15, same as legacy) ----

    $pools = [];

    $stmt = $conn->prepare(
        "SELECT FLDSTR1, FLDSTR2, FLDSTR3, FLDSTR4, FLDSTR5, FLDSTR6, FLDSTR7,
                FLDSTR8, FLDSTR9, FLDSTR10, FLDSTR11, FLDSTR12, FLDSTR13, FLDSTR14, FLDSTR15
         FROM pools
         WHERE RACEDATE = ?"
    );
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $poolsResult = $stmt->get_result();

    if ($poolsRow = $poolsResult->fetch_assoc()) {
        foreach ($poolsRow as $fieldName => $members) {
            if (isset($members) && trim($members) !== "") {
                $pools[] = [
                    "pool_name" => poolLabel($fieldName),
                    "members"   => $members,
                ];
            }
        }
    }
    $stmt->close();

    // ---- Assemble race entries ----

    $races = [];

    foreach ($raceHeaders as $raceNo => $prospect) {

        $division = "";
        $weightsACP = 0;

        switch ((int) ($prospect["DIV"] ?? 0)) {
            case 1:
                $division = "Division I";
                $weightsACP = $prospect["RAISEACP1"] ?? 0;
                break;
            case 2:
                $division = "Division II";
                $weightsACP = $prospect["RAISEACP2"] ?? 0;
                break;
            case 3:
                $division = "Division III";
                $weightsACP = $prospect["RAISEACP3"] ?? 0;
                break;
            default:
                $division = "";
                $weightsACP = $prospect["RAISEACP1"] ?? 0;
                break;
        }

        $adjustments = array_values(array_filter([
            weightAdjustment($prospect["RAISELOWER"] ?? null, "handicap"),
            weightAdjustment($weightsACP, "acceptance"),
        ]));

        $races[] = [
            "race_no"                  => $raceNo,
            "race_no_season"           => $prospect["RACENO_SEA"] ?? null,
            "race_name"                => $prospect["RACENAME"],
            "division"                 => $division,
            "narrative_entry"          => $prospect["NARRENT"],
            "distance"                 => $prospect["DISTANCE"],
            "time"                     => $prospect["RTIME"] ?? "",
            "foreign_jockeys_eligible" => isset($prospect["FJOCK"]) && $prospect["FJOCK"] == 1,
            "weight_adjustments"       => $adjustments,
            "horses"                   => $horsesByRace[$raceNo] ?? [],
        ];
    }

    // ---- Download file link (kept identical to legacy filename logic) ----

    $downloadUrl = null;
    if (preg_match('/\d\d\d(\d)-(\d\d)-(\d\d)/', $date, $matchDate)) {
        $fileDate = $matchDate[3] . $matchDate[2] . $matchDate[1];
        $downloadBase = defined("DOWNLOADFILE_BASE") ? DOWNLOADFILE_BASE : "";
        $downloadUrl = "https://rwitc.com/{$downloadBase}/DEC{$fileDate}.HTM";
    }

    $response = [
        "found"              => true,
        "date"               => $date,
        "mode"               => "json",
        "day_label"          => date("l jS F Y", strtotime($date)),
        "day_narrative"      => $dayNarr,
        "club_name"          => defined("CLUB_NAME") ? CLUB_NAME : null,
        "download_file"      => $downloadUrl,
        "download_available" => $downloadUrl !== null,
        "races"              => $races,
        "pools"              => $pools,
    ];

    // --------------------------------------------------
    // FINAL RESPONSE
    // --------------------------------------------------

    $security->respondAndCache(
        $cacheKey,
        $response
    );

} catch (Throwable $error) {

    // Log actual database error
    $security->logLine(
        "DECLARATIONS_API_ERROR | "
        . $error->getMessage()
    );

    // Do not expose database error publicly
    $security->respondError(
        "Internal server error",
        500
    );

} finally {

    // Close database connection
    if (isset($conn)) {
        $conn->close();
    }

    // Close log file
    if (
        isset($handle)
        && is_resource($handle)
    ) {
        fclose($handle);
    }
}