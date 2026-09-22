<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

// Load database connection
require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/config/run_races_config.php";

// Load ApiSecurity class
require_once __DIR__ . "/ApiSecurity.php";
// S3 HTML is read through the existing s3_set_url.php helper.
// This API does not access S3 directly.

// Make sure the class was loaded correctly
if (!class_exists("ApiSecurity")) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "data"    => null,
        "error"   => "ApiSecurity class could not be loaded"
    ]);

    exit;
}

// Create logs directory
$logDir = __DIR__ . "/logs";

if (!is_dir($logDir)) {
    @mkdir($logDir, 0750, true);
}

// Open log file
$handle = @fopen(
    $logDir . "/api_logs.txt",
    "a+"
);

// Initialize API Security
$security = new ApiSecurity($handle, [
    "rate_limit"     => 60,
    "rate_window"    => 60,
    "cache_ttl"      => 45,
    "cache_dir"      => __DIR__ . "/cache",
    "rate_limit_dir" => __DIR__ . "/rate_limits",
    "api_tag"        => "handicaps_get"
]);

// Apply rate limiting
if (!$security->gate()) {

    if (isset($conn)) {
        $conn->close();
    }

    if (is_resource($handle)) {
        fclose($handle);
    }

    exit;
}

// ============================================================
// ERP PUSH MODE (POST)
// ERP sends: date + type + race_type + html_file
// The uploaded S3 object is registered in run_race_details.
// ============================================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        $postDate = isset($_POST["date"]) ? trim($_POST["date"]) : "";
        $postType = isset($_POST["type"]) ? trim($_POST["type"]) : "";
        $postRaceType = isset($_POST["race_type"]) ? trim($_POST["race_type"]) : "";
        $postHtmlFile = isset($_POST["html_file"]) ? trim($_POST["html_file"]) : "";
        $postFileUrl = isset($_POST["file_url"]) ? trim($_POST["file_url"]) : "";

        if ($postDate === "" || $postType === "" || $postRaceType === "" || $postHtmlFile === "") {
            $security->respondError(
                "date, type, race_type and html_file are required",
                400
            );
            exit;
        }

        $postDateObj = DateTime::createFromFormat("Y-m-d", $postDate);

        if (!$postDateObj || $postDateObj->format("Y-m-d") !== $postDate) {
            $security->respondError(
                "Invalid date format, expected YYYY-MM-DD",
                400
            );
            exit;
        }

        // If ERP did not send a full URL, keep the S3 key in file_url.
        // GET mode below supports both full S3 URLs and S3 keys.
        $storedFileUrl = $postFileUrl !== "" ? $postFileUrl : $postHtmlFile;

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
        $exists = $checkResult && $checkResult->num_rows > 0;
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
                "date" => $postDate,
                "type" => $postType,
                "race_type" => $postRaceType,
                "file_url" => $storedFileUrl,
                "action" => $action
            ],
            "error" => null
        ]);

    } catch (Throwable $error) {

        $security->logLine(
            "HANDICAPS_ERP_PUSH_ERROR | " . $error->getMessage()
        );

        $security->respondError(
            "Unable to register handicaps file",
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

// Only GET requests are allowed after the ERP POST branch.
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

// -------------------------------------------------------------
// Read + sanitize query params
// -------------------------------------------------------------
// Required:
// ?date=YYYY-MM-DD
// &type=<type from ERP>
// &race_type=<race_type from ERP>
// -------------------------------------------------------------

$date = "";
$type = "";
$raceType = "";

if (isset($_GET["date"])) {
    $date = trim($_GET["date"]);
}

if (isset($_GET["type"])) {
    $type = trim($_GET["type"]);
}

if (isset($_GET["race_type"])) {
    $raceType = trim($_GET["race_type"]);
}

if ($date === "") {
    $security->respondError("date is required (format YYYY-MM-DD)", 400);

    if (isset($conn)) {
        $conn->close();
    }

    if (is_resource($handle)) {
        fclose($handle);
    }

    exit;
}

// Resolve missing race_type dynamically from the database.
// The API does not hard-code document metadata.
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

$d = DateTime::createFromFormat("Y-m-d", $date);

if (!$d || $d->format("Y-m-d") !== $date) {
    $security->respondError("Invalid date format, expected YYYY-MM-DD", 400);

    if (isset($conn)) {
        $conn->close();
    }

    if (is_resource($handle)) {
        fclose($handle);
    }

    exit;
}

// ------------------------------------------------------------
// READ HTML FROM EXISTING S3 BUCKET GET API
// ------------------------------------------------------------

function getS3BucketGetApiUrl()
{
    if (defined("S3_BUCKET_GET_API_URL") && S3_BUCKET_GET_API_URL !== "") {
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
        $s3Key = ($path !== null) ? ltrim(rawurldecode($path), "/") : "";
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

    $url = getS3BucketGetApiUrl() . "?key=" . urlencode($s3Key);

    $ch = curl_init($url);

    if ($ch === false) {
        throw new Exception("Unable to initialize S3 bucket API request");
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT      => "RWITC-Handicaps-API/1.0"
    ]);

    $htmlContent = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($htmlContent === false) {
        throw new Exception(
            "S3 bucket API request failed: " . $curlError
        );
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        throw new Exception(
            "S3 bucket API returned HTTP " . $httpCode
        );
    }

    if (trim((string) $htmlContent) === "") {
        throw new Exception("S3 bucket API returned empty HTML");
    }

    return (string) $htmlContent;
}

// ============================================================
// >>> DOWNLOAD MODE (date > 2022-09-25)
// ============================================================
// ?date=2026-08-22&download=1
//
// Source priority:
//   1) OLD local run_races HTML file
//   2) NEW S3 file registered in run_race_details
//
// Download me .html hi milti hai (koi .htm nahi).
// ============================================================

if (
    isset($_GET["download"]) &&
    $_GET["download"] === "1" &&
    $date > "2022-09-25"
) {

    try {

        $htmlContent = false;

        // ---------- 1. OLD LOCAL FILE ----------
        // LOCAL FILE IS ALWAYS CHECKED FIRST.
        $localFile = RUN_RACES_LOCAL_PATH . "/Handicaps_" . $date . ".html";

        if (is_file($localFile)) {

            $htmlContent = file_get_contents($localFile);

            if ($htmlContent === false) {
                throw new Exception(
                    "Unable to read local handicaps file: " . $localFile
                );
            }

        } else {

            if ($type === "" || $raceType === "") {
                throw new Exception("type and race_type are required when the local handicaps file is not available");
            }

            // ---------- 2. NEW S3 FILE ----------
            // If the local file does not exist, check run_race_details
            // and then read the registered S3 file through
            // the existing s3_set_url.php helper.
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

            $stmt->bind_param("sss", $date, $type, $raceType);

            if (!$stmt->execute()) {
                throw new Exception($conn->error);
            }

            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {

                $row = $result->fetch_assoc();
                $stmt->close();

                $htmlContent = readHtmlFromS3BucketApi(
                    $row["file_url"]
                );

            } else {

                $stmt->close();
            }
        }

        if ($htmlContent === false || $htmlContent === "") {
            throw new Exception("Handicaps file not found for this date");
        }

        if (stripos($htmlContent, "<html") === false) {

            $downloadCss = <<<'CSS'
* { box-sizing: border-box; }
span, a { display: inline-block; text-decoration: none; color: #333333; }
body { font-family: Arial; margin: 0; padding: 16px; }
h1 { margin: unset !important; font-size: 26px !important; }
h3 { font-family: 'Roboto Condensed', Arial, sans-serif; font-size: 32px; color: #c1c1c1; margin: 10px 0; }
th { color: #ffffff !important; font-size: 14px; text-align: center; padding: 1px; border: 1px solid #BCBEC0; background: #11a14e; }
td { text-align: left !important; padding: 4px !important; color: #333333 !important; font-weight: 600; }
tbody > tr > th { text-align: left !important; }
tbody tr td:nth-child(2) { text-align: center !important; }
tbody tr td:nth-child(3) { text-align: center !important; }
table { border-collapse: collapse; }
.table { width: 100%; max-width: 100%; margin-bottom: 1rem; background-color: transparent; }
.table th, .table td { padding: 8px; vertical-align: top; border-top: 1px solid #dee2e6; }
.table-bordered { border: 1px solid #dee2e6; font-weight: bold; width: 100%; border-collapse: collapse; }
.table-bordered th, .table-bordered td { border: 1px solid #dee2e6; }
.table-bordered thead th, .table-bordered thead td { border-bottom-width: 2px; }
.download { display: none !important; }
.pageHeading { text-align: center; }
#leftArea .pageHeader .pageHeading .subHeading { clear: both; float: left; width: 100%; color: #000; font-weight: bold; text-align: center; font-size: 12px; margin: 5px 0; padding: 5px 0; }
.show1 { display: none; }
@media (max-width: 500px) {
    .text_size { font-size: 8px; }
    .font12 { font-size: 8px; }
    .nm { text-align: left !important; }
    .perform_data { display: contents; border: 1px solid #cdced3 !important; border-radius: 12px; background: #cdced3 !important; margin-bottom: 5%; padding: 10px !important; }
    .perform_data td:first-child { padding-left: 10px; }
    .perform_data td { font-size: 11px !important; position: relative; border: unset !important; }
    td, th { font-size: 10px !important; border: unset; }
    .racehead { padding: 5px !important; font-size: 12px !important; }
}
CSS;

            $htmlContent =
                "<!DOCTYPE html>\n<html>\n<head>\n"
                . "<meta charset=\"UTF-8\">\n"
                . "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n"
                . "<title>Handicaps " . $date . "</title>\n"
                . "<style>\n" . $downloadCss . "\n</style>\n</head>\n<body>\n"
                . $htmlContent
                . "\n</body>\n</html>";
        }

        // Open in browser. No .htm is used.
        header("Content-Type: text/html; charset=UTF-8");
        header('Content-Disposition: inline; filename="Handicaps_' . $date . '.html"');

        echo $htmlContent;

    } catch (Throwable $error) {

        $security->logLine(
            "HANDICAPS_DOWNLOAD_ERROR | " . $error->getMessage()
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
// >>> CHANGE START — HTML ARCHIVE SUPPORT ADDED (date > 2022-09-25)
// ============================================================

// Dates after 2022-09-25 are served from Handicaps_<date>.html.
// Local run_races file is checked first; if it does not exist,
// the registered S3 file is used.
if ($date > "2022-09-25") {

    // No cache is used in HTML archive mode.
    // This guarantees the required order on every request:
    // 1) local run_races file
    // 2) S3 file via s3_set_url.php

    try {

        $htmlContent = false;
        $source = "";

        // ======================================================
        // 1) OLD LOCAL FILE
        // ======================================================
        // LOCAL FILE IS ALWAYS CHECKED FIRST.
        // ======================================================

        $localFile = RUN_RACES_LOCAL_PATH . "/Handicaps_" . $date . ".html";

        if (is_file($localFile)) {

            $source = "LOCAL_RUN_RACES";
            $htmlContent = file_get_contents($localFile);

            if ($htmlContent === false) {
                throw new Exception(
                    "Unable to read local handicaps file: " . $localFile
                );
            }

        } else {

            if ($type === "" || $raceType === "") {
                throw new Exception("type and race_type are required when the local handicaps file is not available");
            }

            // ======================================================
            // 2) NEW S3 FILE
            // ======================================================
            // If local file does not exist, check run_race_details
            // and read the registered S3 file through the existing
            // s3_set_url.php helper.
            // ======================================================

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

            $stmt->bind_param("sss", $date, $type, $raceType);

            if (!$stmt->execute()) {
                throw new Exception($conn->error);
            }

            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {

                $row = $result->fetch_assoc();
                $stmt->close();

                $htmlContent = readHtmlFromS3BucketApi(
                    $row["file_url"]
                );

                $source = "DB_S3";

            } else {

                $stmt->close();
            }
        }

        if ($htmlContent === false || $htmlContent === "") {
            throw new Exception("No handicaps data found for this date");
        }

        // ======================================================
        // DOWNLOAD LINK
        // ======================================================

        $downloadAvailable = true;
        $downloadFile = null;

        $baseParts = parse_url(RUN_RACES_BASE_URL);
        $origin = "";

        if (isset($baseParts["scheme"]) && isset($baseParts["host"])) {
            $origin = $baseParts["scheme"] . "://" . $baseParts["host"]
                . (isset($baseParts["port"]) ? ":" . $baseParts["port"] : "");
        }

        $downloadFile = $origin
            . $_SERVER["SCRIPT_NAME"]
            . "?date=" . urlencode($date)
            . "&type=" . urlencode($type)
            . "&race_type=" . urlencode($raceType)
            . "&download=1";

        // ======================================================
        // RESPONSE
        // ======================================================

        $response = [
            "date"               => $date,
            "type"               => $type,
            "race_type"          => $raceType,
            "mode"               => "html",
            "source"             => $source,
            "html"               => $htmlContent,
            "download_file"      => $downloadFile,
            "download_available" => $downloadAvailable
        ];

        echo json_encode([
            "success" => true,
            "data"    => $response,
            "error"   => null
        ]);

    } catch (Throwable $error) {

        $security->logLine(
            "HANDICAPS_HTML_READ_ERROR | " . $error->getMessage()
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
// <<< CHANGE END
// ============================================================

if ($type === "" || $raceType === "") {
    $security->respondError(
        "type and race_type are required",
        400
    );

    if (isset($conn)) {
        $conn->close();
    }

    if (is_resource($handle)) {
        fclose($handle);
    }

    exit;
}

$cacheKey = "handicaps_" . $date . "_" . $type . "_" . $raceType;

// Return cached response if available
if ($security->serveCache($cacheKey)) {

    if (isset($conn)) {
        $conn->close();
    }

    if (is_resource($handle)) {
        fclose($handle);
    }

    exit;
}

try {

    // -----------------------------------------------------------
    // 1) Races for this date (prospect table)
    // -----------------------------------------------------------
    $stmt = $conn->prepare("
        SELECT UNIX_TIMESTAMP(p.`DATE`) as DATE, p.`SRNO`, p.`NAME` as RACENAME,
               p.`DAYNARR`, p.`NARRENT`, p.`DISTANCE`, p.`FJOCK`, p.`HTERMS`,
               p.`RACECAT`, p.`GRADE`, p.`RAISELOWER`, p.`RAISEACP1`,
               p.`RAISEACP2`, p.`RAISEACP3`, p.`RACETIME1`, p.`RACETIME2`,
               p.`VOID_HACP`, p.`VOID_ACCP`
        FROM prospect p
        WHERE p.`DATE` = ?
        ORDER BY p.`SRNO` ASC
    ");

    if ($stmt === false) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param("s", $date);
    $stmt->execute();

    $result = $stmt->get_result();
    $prospectData = [];

    while ($row = $result->fetch_assoc()) {
        $prospectData[] = $row;
    }

    $stmt->close();

    // -----------------------------------------------------------
    // 2) Distinct SRNO list from weights for this date
    // -----------------------------------------------------------
    $stmt = $conn->prepare("
        SELECT DISTINCT(w.`SRNO`)
        FROM weights w
        WHERE w.`RACEDATE` = ? AND w.`SRNO` > 0
        ORDER BY w.`SRNO` ASC
    ");

    if ($stmt === false) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param("s", $date);
    $stmt->execute();

    $result = $stmt->get_result();
    $weightsSRNOList = [];

    while ($row = $result->fetch_assoc()) {
        $weightsSRNOList[] = $row["SRNO"];
    }

    $stmt->close();

    // -----------------------------------------------------------
    // 3) Weights (+ horse breeding info) per race, same positional
    //    pairing as the original page: race at index $i is paired
    //    with weightsSRNOList[$i]. Unlike the original, we guard
    //    every access with isset() instead of assuming the two
    //    lists line up, so a mismatch can't throw an undefined
    //    index notice or pull the wrong SRNO.
    // -----------------------------------------------------------
    $races = [];

    foreach ($prospectData as $i => $prospect) {

        $weightsData = [];
        $srNo = isset($weightsSRNOList[$i]) ? $weightsSRNOList[$i] : null;

        if ($srNo !== null) {


            // .. added Age, Color, SEX....

            $stmt = $conn->prepare("
                SELECT w.`SRNOCTRL`, UNIX_TIMESTAMP(w.`RACEDATE`) as RACEDATE,
                w.`WEIGHT`, w.`NAME`, w.`SRNO`, w.`HORSESEQ`,
                w.`ACCPFLAG`, w.`HRATING`, w.`RAISELOWER`, w.`FRT`,
                w.`SSBAN`, w.`VOBAN`, w.`MKBAN`, w.`SSREQD`, w.`SHOE`,
                w.`SHOEDET`, w.`BITSDET`, w.`SORDER`, w.`TRAINERNME`,
                h.SIRE, h.DAM, h.DAMNAT, h.AGE, h.SEX, h.COLOR
                FROM weights w
                INNER JOIN hmaster h ON w.`HORSESEQ` = h.`HORSESEQ`
                WHERE w.`RACEDATE` = ? AND w.`SRNO` = ?
                GROUP BY w.`HORSESEQ`
                ORDER BY w.`SORDER` ASC
            ");

            if ($stmt === false) {
                throw new Exception($conn->error);
            }

            $stmt->bind_param("si", $date, $srNo);
            $stmt->execute();

            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $weightsData[] = $row;
            }

            $stmt->close();
        }

        // Collect ban lists the same way the page does, but as
        // arrays instead of comma-joined strings baked into HTML.
        $ssBan = [];
        $voBan = [];
        $mkBan = [];

        foreach ($weightsData as $weight) {
            if (isset($weight["SSBAN"]) && $weight["SSBAN"] === "Y") {
                $ssBan[] = $weight["NAME"];
            }

            if (isset($weight["VOBAN"]) && $weight["VOBAN"] === "Y") {
                $voBan[] = $weight["NAME"];
            }

            if (isset($weight["MKBAN"]) && $weight["MKBAN"] === "Y") {
                $mkBan[] = $weight["NAME"];
            }
        }

        $races[] = [
            "srno"                     => $prospect["SRNO"],
            "race_name"                => $prospect["RACENAME"],
            "day_narr"                 => $prospect["DAYNARR"],
            "narr_ent"                 => $prospect["NARRENT"],
            "distance"                 => $prospect["DISTANCE"],
            "foreign_jockeys_eligible" => ((int) $prospect["FJOCK"]) === 1,
            "hterms"                   => $prospect["HTERMS"],
            "race_cat"                 => $prospect["RACECAT"],
            "grade"                    => $prospect["GRADE"],
            "raise_lower"              => $prospect["RAISELOWER"],
            "raise_acp1"               => $prospect["RAISEACP1"],
            "raise_acp2"               => $prospect["RAISEACP2"],
            "raise_acp3"               => $prospect["RAISEACP3"],
            "race_time1"               => $prospect["RACETIME1"],
            "race_time2"               => $prospect["RACETIME2"],
            "void_hacp"                => $prospect["VOID_HACP"],
            "void_accp"                => $prospect["VOID_ACCP"],
            "weights"                  => $weightsData,
            "ss_ban_horses"            => $ssBan,
            "vo_ban_horses"            => $voBan,
            "mk_ban_horses"            => $mkBan
        ];
    }

    // -----------------------------------------------------------
    // Download file link + formatted narrative, same derivation
    // as the original page
    // -----------------------------------------------------------
    // Download is handled by the HTML archive/download mode above.
    $downloadAvailable = false;
    $downloadFile = null;
    $dayNarrative = null;
    $formattedDate = null;

    if (isset($prospectData[0])) {
        $dayNarrative = $prospectData[0]["DAYNARR"];
        $formattedDate = date("l jS F Y", $prospectData[0]["DATE"]);
    }

    $response = [
        "date"               => $date,
        "type"               => $type,
        "race_type"          => $raceType,
        "mode"               => "json",
        "download_file"      => $downloadFile,
        "download_available" => $downloadAvailable,
        "day_narrative"      => $dayNarrative,
        "formatted_date"     => $formattedDate,
        "race_count"         => count($races),
        "races"              => $races
    ];

    // Return successful response and save it in cache
    $security->respondAndCache($cacheKey, $response);
} catch (Throwable $error) {

    // Save actual error in log file
    $security->logLine(
        "HANDICAPS_API_ERROR | " .
            $error->getMessage()
    );

    // Return safe error response. The real error is already logged above.
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
    if (isset($handle) && is_resource($handle)) {
        fclose($handle);
    }
}
