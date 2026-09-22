<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

// Load database and security
require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/config/run_races_config.php";
require_once __DIR__ . "/ApiSecurity.php";

// --------------------------------------------------
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
    "api_tag"        => "acceptance_get"
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

function acceptanceS3Key($fileUrl)
{
    $fileUrl = trim((string) $fileUrl);

    if ($fileUrl === "") {
        return "";
    }

    // DB may contain a complete S3 URL.
    if (preg_match("/^https?:\/\//i", $fileUrl)) {
        $path = parse_url($fileUrl, PHP_URL_PATH);
        $fileUrl = $path !== null ? $path : "";
    }

    return ltrim(rawurldecode($fileUrl), "/");
}


// --------------------------------------------------
// HELPER: EXISTING S3 HTML READER API
// --------------------------------------------------

function getS3BucketGetApiUrl()
{
    if (
        defined("S3_BUCKET_GET_API_URL") &&
        S3_BUCKET_GET_API_URL !== ""
    ) {
        return rtrim(S3_BUCKET_GET_API_URL, "/");
    }

    $scheme = "http";

    if (
        isset($_SERVER["HTTPS"]) &&
        $_SERVER["HTTPS"] !== "" &&
        strtolower($_SERVER["HTTPS"]) !== "off"
    ) {
        $scheme = "https";
    }

    $host = isset($_SERVER["HTTP_HOST"])
        ? trim($_SERVER["HTTP_HOST"])
        : "";

    if ($host === "") {
        throw new Exception("Unable to determine API host");
    }

    $scriptDir = dirname($_SERVER["SCRIPT_NAME"] ?? "");
    $scriptDir = str_replace("\\", "/", $scriptDir);
    $scriptDir = rtrim($scriptDir, "/");

    return $scheme . "://" . $host . $scriptDir . "/s3_set_url.php";
}


function readHtmlFromS3BucketApi($fileUrl)
{
    $fileUrl = trim((string) $fileUrl);

    if ($fileUrl === "") {
        throw new Exception("S3 file URL is empty");
    }

    // DB may contain either a complete S3 URL or an S3 key.
    if (preg_match("/^https?:\\/\\//i", $fileUrl)) {
        $path = parse_url($fileUrl, PHP_URL_PATH);
        $s3Key = ($path !== null)
            ? ltrim(rawurldecode($path), "/")
            : "";
    } else {
        $s3Key = ltrim(rawurldecode($fileUrl), "/");
    }

    if (
        $s3Key === "" ||
        strpos($s3Key, "run_races/") !== 0 ||
        !preg_match("/\\.html$/i", $s3Key)
    ) {
        throw new Exception("Invalid S3 HTML file key");
    }

    $url =
        getS3BucketGetApiUrl() .
        "?key=" .
        urlencode($s3Key);

    $ch = curl_init($url);

    if ($ch === false) {
        throw new Exception(
            "Unable to initialize S3 bucket API request"
        );
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT      => "RWITC-Acceptance-API/1.0"
    ]);

    $htmlContent = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int) curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );

    curl_close($ch);

    if ($htmlContent === false) {
        throw new Exception(
            "S3 bucket API request failed: " .
            $curlError
        );
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        throw new Exception(
            "S3 bucket API returned HTTP " .
            $httpCode
        );
    }

    if (trim((string) $htmlContent) === "") {
        throw new Exception(
            "S3 bucket API returned empty HTML"
        );
    }

    return (string) $htmlContent;
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

            $action = "updated";

        } else {

            $insertStmt = $conn->prepare("
                INSERT INTO run_race_details
                    (`date`, `type`, `file_url`, `race_type`)
                VALUES
                    (?, ?, ?, ?)
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

            $action = "inserted";
        }

        echo json_encode([
            "success" => true,
            "data" => [
                "date"      => $postDate,
                "type"      => $postType,
                "race_type" => $postRaceType,
                "file_url"  => $storedFileUrl,
                "action"    => $action
            ],
            "error" => null
        ]);

    } catch (Throwable $error) {

        $security->logLine(
            "ACCEPTANCE_ERP_PUSH_ERROR | " .
            $error->getMessage()
        );

        $security->respondError(
            "Unable to register acceptance file",
            500
        );

    } finally {

        if (isset($conn)) {
            $conn->close();
        }

        if (
            isset($handle) &&
            is_resource($handle)
        ) {
            fclose($handle);
        }
    }

    exit;
}

// --------------------------------------------------
// ONLY GET AFTER POST BRANCH
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
// READ QUERY PARAMETERS
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

if (
    !$d ||
    $d->format("Y-m-d") !== $date
) {
    $security->respondError(
        "Invalid date format, expected YYYY-MM-DD",
        400
    );

    exit;
}

// --------------------------------------------------
// DYNAMIC METADATA / BACKWARD COMPATIBILITY
// --------------------------------------------------
// type and race_type are never hard-coded in this API.
// If the frontend does not send either value, resolve it
// from the ERP registration in run_race_details.
//
// For dates after 2022-09-25 the HTML source priority remains:
// 1) local run_races file
// 2) S3 file registered in run_race_details

if ($type === "" || $raceType === "") {

    $metaStmt = $conn->prepare("
        SELECT `type`, `race_type`
        FROM run_race_details
        WHERE `date` = ?
          AND file_url IS NOT NULL
          AND file_url <> ''
        ORDER BY id DESC
        LIMIT 1
    ");

    if ($metaStmt === false) {
        $security->respondError(
            "Unable to resolve acceptance metadata",
            500
        );

        exit;
    }

    $metaStmt->bind_param(
        "s",
        $date
    );

    if (!$metaStmt->execute()) {
        $metaStmt->close();

        $security->respondError(
            "Unable to resolve acceptance metadata",
            500
        );

        exit;
    }

    $metaResult = $metaStmt->get_result();

    if (
        $metaResult &&
        $metaResult->num_rows > 0
    ) {

        $metaRow = $metaResult->fetch_assoc();

        if ($type === "") {
            $type = trim(
                (string) $metaRow["type"]
            );
        }

        if ($raceType === "") {
            $raceType = trim(
                (string) $metaRow["race_type"]
            );
        }
    }

    $metaStmt->close();
}

// Do not add an acceptance/pre_race default here.
// Local archive files can still be served without race_type.
// When S3 is required, the DB registration supplies the metadata.

// --------------------------------------------------
// DOWNLOAD MODE
// --------------------------------------------------

if (
    isset($_GET["download"]) &&
    $_GET["download"] === "1" &&
    $date > "2022-09-25"
) {

    try {

        $htmlContent = false;

        // --------------------------------------------------
        // FIRST: OLD LOCAL HTML FILE
        // --------------------------------------------------

        $localFile =
            RUN_RACES_LOCAL_PATH .
            "/Acceptance_" .
            $date .
            ".html";

        if (is_file($localFile)) {

            $htmlContent =
                file_get_contents($localFile);

            if ($htmlContent === false) {
                throw new Exception(
                    "Unable to read local acceptance file: " .
                    $localFile
                );
            }

        } else {

            // --------------------------------------------------
            // SECOND: NEW S3 FILE
            // --------------------------------------------------

            $stmt = $conn->prepare("
                SELECT file_url
                FROM run_race_details
                WHERE `date` = ?
                  AND `type` = ?
                  AND `race_type` = ?
                  AND file_url IS NOT NULL
                  AND file_url <> ''
                ORDER BY id DESC
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

            if (
                $result &&
                $result->num_rows > 0
            ) {

                $row = $result->fetch_assoc();
                $stmt->close();

                $htmlContent =
                    readHtmlFromS3BucketApi(
                        $row["file_url"]
                    );

            } else {

                $stmt->close();
            }
        }

        if (
            $htmlContent === false ||
            $htmlContent === ""
        ) {
            throw new Exception(
                "Acceptance file not found for this date"
            );
        }

        // If the stored archive is only a fragment,
        // wrap it as a complete downloadable HTML page.
        if (
            stripos(
                $htmlContent,
                "<html"
            ) === false
        ) {

            $downloadCss = <<<'CSS'
* { box-sizing: border-box; }
body { font-family: Arial, sans-serif; margin: 0; padding: 16px; }
span, a { display: inline-block; text-decoration: none; color: #333333; }
table { border-collapse: collapse; width: 100%; }
.table, .table-bordered { width: 100%; }
.table-bordered { border: 1px solid #dee2e6; }
.table-bordered th, .table-bordered td { border: 1px solid #dee2e6; padding: 6px; }
th { color: #ffffff !important; background: #11a14e; text-align: center; }
td { color: #333333; font-weight: 600; }
.download { display: none !important; }
.pageHeading { text-align: center; }
@media (max-width: 500px) {
    td, th { font-size: 10px !important; }
}
CSS;

            $htmlContent =
                "<!DOCTYPE html>\n" .
                "<html>\n<head>\n" .
                "<meta charset=\"UTF-8\">\n" .
                "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n" .
                "<title>Acceptance " .
                htmlspecialchars($date, ENT_QUOTES, "UTF-8") .
                "</title>\n" .
                "<style>\n" .
                $downloadCss .
                "\n</style>\n" .
                "</head>\n<body>\n" .
                $htmlContent .
                "\n</body>\n</html>";
        }

        header(
            "Content-Type: text/html; charset=UTF-8"
        );

        header(
            'Content-Disposition: inline; filename="Acceptance_' .
            $date .
            '.html"'
        );

        echo $htmlContent;

    } catch (Throwable $error) {

        $security->logLine(
            "ACCEPTANCE_DOWNLOAD_ERROR | " .
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

        if (
            isset($handle) &&
            is_resource($handle)
        ) {
            fclose($handle);
        }
    }

    exit;
}

// --------------------------------------------------
// HTML ARCHIVE MODE
// --------------------------------------------------

if ($date > "2022-09-25") {

    try {

        $htmlContent = false;
        $source = "";

        // --------------------------------------------------
        // FIRST: OLD LOCAL HTML FILE
        // --------------------------------------------------

        $localFile =
            RUN_RACES_LOCAL_PATH .
            "/Acceptance_" .
            $date .
            ".html";

        if (is_file($localFile)) {

            $htmlContent =
                file_get_contents($localFile);

            $source =
                "LOCAL_RUN_RACES";

            if ($htmlContent === false) {
                throw new Exception(
                    "Unable to read local acceptance file: " .
                    $localFile
                );
            }

        } else {

            // --------------------------------------------------
            // SECOND: NEW S3 FILE
            // --------------------------------------------------

            $stmt = $conn->prepare("
                SELECT file_url
                FROM run_race_details
                WHERE `date` = ?
                  AND `type` = ?
                  AND `race_type` = ?
                  AND file_url IS NOT NULL
                  AND file_url <> ''
                ORDER BY id DESC
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

            if (
                $result &&
                $result->num_rows > 0
            ) {

                $row = $result->fetch_assoc();
                $stmt->close();

                $htmlContent =
                    readHtmlFromS3BucketApi(
                        $row["file_url"]
                    );

                $source = "DB_S3";

            } else {

                $stmt->close();
            }
        }

        if (
            $htmlContent === false ||
            $htmlContent === ""
        ) {
            throw new Exception(
                "No acceptance data found for this date"
            );
        }

        // --------------------------------------------------
        // API DOWNLOAD URL
        // --------------------------------------------------

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
            "?date=" .
            urlencode($date) .
            "&type=" .
            urlencode($type) .
            "&race_type=" .
            urlencode($raceType) .
            "&download=1";

        $response = [
            "date"               => $date,
            "type"               => $type,
            "race_type"          => $raceType,
            "mode"               => "html",
            "source"             => $source,
            "html"               => $htmlContent,
            "download_file"      => $downloadFile,
            "download_available" => true
        ];

        echo json_encode([
            "success" => true,
            "data"    => $response,
            "error"   => null
        ]);

    } catch (Throwable $error) {

        $security->logLine(
            "ACCEPTANCE_HTML_READ_ERROR | " .
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

        if (
            isset($handle) &&
            is_resource($handle)
        ) {
            fclose($handle);
        }
    }

    exit;
}

// --------------------------------------------------
// HISTORICAL JSON MODE
// --------------------------------------------------

$cacheKey =
    "acceptance_" .
    $date .
    "_" .
    $type .
    "_" .
    $raceType;

if ($security->serveCache($cacheKey)) {

    if (isset($conn)) {
        $conn->close();
    }

    if (
        isset($handle) &&
        is_resource($handle)
    ) {
        fclose($handle);
    }

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
    if (
        $value === null ||
        $value === "" ||
        (float) $value == 0.0
    ) {
        return null;
    }

    return [
        "stage"     => $stageLabel,
        "direction" => $value > 0 ? "raised" : "lowered",
        "kg"        => abs((float) $value),
    ];
}

// --------------------------------------------------
// FETCH HISTORICAL DATA
// --------------------------------------------------

try {

    // ---- Day narrative ----
    $dayNarr = "";

    $stmt = $conn->prepare(
        "SELECT DAYNARR
         FROM prospect
         WHERE DATE = ?
           AND DAYNARR <> ''
         LIMIT 1"
    );

    if ($stmt === false) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param(
        "s",
        $date
    );

    if (!$stmt->execute()) {
        throw new Exception($conn->error);
    }

    $narrResult = $stmt->get_result();

    if ($narrRow = $narrResult->fetch_assoc()) {
        $dayNarr = $narrRow["DAYNARR"];
    }

    $stmt->close();

    // ---- Race-level headers ----
    $raceHeaders = [];

    $stmt = $conn->prepare(
        "SELECT p.SRNO,
                p.NAME AS RACENAME,
                p.DAYNARR,
                p.NARRENT,
                p.DISTANCE,
                p.FJOCK,
                p.HTERMS,
                p.RACECAT,
                p.GRADE,
                p.RAISELOWER,
                p.RAISEACP1,
                p.RAISEACP2,
                p.RAISEACP3,
                p.RACETIME1,
                p.RACETIME2,
                p.VOID_HACP,
                p.VOID_ACCP,
                rh.RACENO,
                rh.RTIME,
                rh.`DIV`
         FROM prospect p
         INNER JOIN (
             SELECT RACENO,
                    MIN(LINK) AS LINK,
                    MIN(RTIME) AS RTIME,
                    MIN(`DIV`) AS `DIV`
             FROM decl
             WHERE RACEDATE = ?
             GROUP BY RACENO
         ) rh
             ON rh.LINK = p.SRNO
         WHERE p.DATE = ?
         ORDER BY rh.RACENO ASC"
    );

    if ($stmt === false) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param(
        "ss",
        $date,
        $date
    );

    if (!$stmt->execute()) {
        throw new Exception($conn->error);
    }

    $headerResult = $stmt->get_result();

    while ($row = $headerResult->fetch_assoc()) {
        $raceHeaders[$row["RACENO"]] = $row;
    }

    $stmt->close();

    if (empty($raceHeaders)) {

        $security->respondAndCache(
            $cacheKey,
            [
                "found"   => false,
                "message" => "No acceptance data found for {$date}",
                "races"   => [],
                "pools"   => []
            ]
        );

        exit;
    }

    // ---- All horses ----
    $horsesByRace = [];

    $stmt = $conn->prepare(
        "SELECT d.RACENO,
                d.NAME,
                d.WEIGHT,
                d.HORSESEQ,
                d.HRATING,
                t.TRAINERNME,
                h.SIRE,
                h.DAM,
                h.DAMNAT
         FROM decl d
         INNER JOIN hmaster h
             ON d.HORSESEQ = h.HORSESEQ
         INNER JOIN trainers t
             ON d.TRAINER = t.TRAINER
         WHERE d.RACEDATE = ?
         ORDER BY d.RACENO ASC,
                  d.WEIGHT DESC,
                  d.NAME ASC"
    );

    if ($stmt === false) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param(
        "s",
        $date
    );

    if (!$stmt->execute()) {
        throw new Exception($conn->error);
    }

    $horseResult = $stmt->get_result();

    while ($row = $horseResult->fetch_assoc()) {

        $raceNo = $row["RACENO"];

        if (!isset($horsesByRace[$raceNo])) {
            $horsesByRace[$raceNo] = [];
        }

        $rating = $row["HRATING"];

        if ($rating == -99) {
            $rating = null;
        }

        $breeding =
            $row["SIRE"] .
            " - " .
            $row["DAM"];

        if (!empty($row["DAMNAT"])) {
            $breeding .=
                " (" .
                $row["DAMNAT"] .
                ")";
        }

        $horsesByRace[$raceNo][] = [
            "position"   => count(
                $horsesByRace[$raceNo]
            ) + 1,
            "name"       => $row["NAME"],
            "horseseq"   => $row["HORSESEQ"],
            "weight"     => $row["WEIGHT"],
            "rating"     => $rating,
            "sire"       => $row["SIRE"],
            "dam"        => $row["DAM"],
            "dam_nation" => $row["DAMNAT"],
            "breeding"   => $breeding,
            "trainer"    => $row["TRAINERNME"]
        ];
    }

    $stmt->close();

    // ---- Operations / scratch notes ----
    $operByRace = [];

    $stmt = $conn->prepare(
        "SELECT RACENO,
                HORSE,
                HORSESEQ,
                `DATE`,
                NARR
         FROM operrace
         WHERE RACEDATE = ?
         ORDER BY RACENO ASC,
                  `DATE` ASC"
    );

    if ($stmt === false) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param(
        "s",
        $date
    );

    if (!$stmt->execute()) {
        throw new Exception($conn->error);
    }

    $operResult = $stmt->get_result();

    while ($row = $operResult->fetch_assoc()) {

        $raceNo = $row["RACENO"];

        if (!isset($operByRace[$raceNo])) {
            $operByRace[$raceNo] = [];
        }

        $operByRace[$raceNo][] = [
            "date"     => date(
                "d/m/y",
                strtotime($row["DATE"])
            ),
            "horse"    => $row["HORSE"],
            "horseseq" => $row["HORSESEQ"],
            "note"     => $row["NARR"]
        ];
    }

    $stmt->close();

    // ---- Pools ----
    $pools = [];

    $stmt = $conn->prepare(
        "SELECT FLDSTR1,
                FLDSTR2,
                FLDSTR3,
                FLDSTR4,
                FLDSTR5,
                FLDSTR6,
                FLDSTR7,
                FLDSTR8,
                FLDSTR9,
                FLDSTR10,
                FLDSTR11,
                FLDSTR12,
                FLDSTR13,
                FLDSTR14,
                FLDSTR15
         FROM pools
         WHERE RACEDATE = ?"
    );

    if ($stmt === false) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param(
        "s",
        $date
    );

    if (!$stmt->execute()) {
        throw new Exception($conn->error);
    }

    $poolsResult = $stmt->get_result();

    if ($poolsRow = $poolsResult->fetch_assoc()) {

        foreach ($poolsRow as $fieldName => $members) {

            if (
                isset($members) &&
                trim($members) !== ""
            ) {

                $pools[] = [
                    "pool_name" => poolLabel($fieldName),
                    "members"   => $members
                ];
            }
        }
    }

    $stmt->close();

    // ---- Assemble races ----
    $races = [];

    foreach ($raceHeaders as $raceNo => $prospect) {

        $void = false;
        $time = "";

        if (
            isset($prospect["RTIME"]) &&
            $prospect["RTIME"] === "VOID"
        ) {

            $void = true;

        } else {

            $time =
                $prospect["RTIME"] ?? "";
        }

        $division = "";
        $weightsACP = 0;

        switch (
            (int) ($prospect["DIV"] ?? 0)
        ) {

            case 1:
                $division = "Division I";
                $weightsACP =
                    $prospect["RAISEACP1"] ?? 0;
                break;

            case 2:
                $division = "Division II";
                $weightsACP =
                    $prospect["RAISEACP2"] ?? 0;
                break;

            case 3:
                $division = "Division III";
                $weightsACP =
                    $prospect["RAISEACP3"] ?? 0;
                break;

            default:
                $division = "";
                $weightsACP =
                    $prospect["RAISEACP1"] ?? 0;
                break;
        }

        $adjustments = array_values(
            array_filter([
                weightAdjustment(
                    $prospect["RAISELOWER"] ?? null,
                    "handicap"
                ),
                weightAdjustment(
                    $weightsACP,
                    "acceptance"
                )
            ])
        );

        $races[] = [
            "race_no" =>
                $raceNo,

            "race_name" =>
                $prospect["RACENAME"],

            "division" =>
                $division,

            "narrative_entry" =>
                $prospect["NARRENT"],

            "distance" =>
                $prospect["DISTANCE"],

            "time" =>
                $time,

            "void" =>
                $void,

            "foreign_jockeys_eligible" =>
                isset($prospect["FJOCK"]) &&
                $prospect["FJOCK"] == 1,

            "weight_adjustments" =>
                $adjustments,

            "operations_notes" =>
                $operByRace[$raceNo] ?? [],

            "horses" =>
                $horsesByRace[$raceNo] ?? []
        ];
    }

    // ---- Historical download URL ----
    $downloadUrl = null;

    if (
        preg_match(
            '/\d\d\d(\d)-(\d\d)-(\d\d)/',
            $date,
            $matchDate
        )
    ) {

        $fileDate =
            $matchDate[3] .
            $matchDate[2] .
            $matchDate[1];

        $downloadBase =
            defined("DOWNLOADFILE_BASE")
                ? DOWNLOADFILE_BASE
                : "";

        $downloadUrl =
            "https://rwitc.com/{$downloadBase}/ACP" .
            $fileDate .
            ".HTM";
    }

    $response = [
        "found" =>
            true,

        "date" =>
            $date,

        "type" =>
            $type,

        "race_type" =>
            $raceType,

        "mode" =>
            "json",

        "day_label" =>
            date(
                "l jS F Y",
                strtotime($date)
            ),

        "day_narrative" =>
            $dayNarr,

        "club_name" =>
            defined("CLUB_NAME")
                ? CLUB_NAME
                : null,

        "final_and_correct" =>
            ($date === "2019-02-03"),

        "download_file" =>
            $downloadUrl,

        "download_available" =>
            $downloadUrl !== null,

        "races" =>
            $races,

        "pools" =>
            $pools
    ];

    $security->respondAndCache(
        $cacheKey,
        $response
    );

} catch (Throwable $error) {

    $security->logLine(
        "ACCEPTANCE_API_ERROR | " .
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

    if (
        isset($handle) &&
        is_resource($handle)
    ) {
        fclose($handle);
    }
}