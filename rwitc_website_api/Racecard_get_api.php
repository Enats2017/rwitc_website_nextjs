<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");

require_once __DIR__ . "/config/config.php";
// >>> CHANGE: added for RUN_RACES_LOCAL_PATH / RUN_RACES_BASE_URL constants
require_once __DIR__ . "/config/run_races_config.php";
require_once __DIR__ . "/ApiSecurity.php";

// --------------------------------------------------
// LOG SETUP
// --------------------------------------------------

$logDir = __DIR__ . "/logs";

if (!is_dir($logDir)) {
    @mkdir($logDir, 0750, true);
}

$handle = @fopen(
    $logDir . "/api_logs.txt",
    "a+"
);

// --------------------------------------------------
// INITIALIZE SECURITY (unchanged)
// --------------------------------------------------

$security = new ApiSecurity($handle, [
    "rate_limit"     => 60,
    "rate_window"    => 60,
    "cache_ttl"      => 120,
    "cache_dir"      => __DIR__ . "/cache",
    "rate_limit_dir" => __DIR__ . "/rate_limits",
    "api_tag"        => "racecard_get"
]);

// --------------------------------------------------
// RATE LIMIT (unchanged)
// --------------------------------------------------

if (!$security->gate()) {
    exit;
}

// ============================================================
// RACECARD WEBSITE PUSH + HTML ARCHIVE SUPPORT
// ============================================================

function racecardS3Key($fileUrl)
{
    $fileUrl = trim((string) $fileUrl);

    if ($fileUrl === "") {
        return "";
    }

    if (preg_match("/^https?:\/\//i", $fileUrl)) {
        $path = parse_url($fileUrl, PHP_URL_PATH);
        $fileUrl = $path !== null ? $path : "";
    }

    return ltrim(rawurldecode($fileUrl), "/");
}

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
    $s3Key = racecardS3Key($fileUrl);

    if (
        $s3Key === "" ||
        strpos($s3Key, "run_races/") !== 0 ||
        strtolower(pathinfo($s3Key, PATHINFO_EXTENSION)) !== "html"
    ) {
        throw new Exception(
            "Invalid racecard S3 file key"
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
        "RWITC Racecard S3 Reader"
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

function racecardWrapDownloadHtml($htmlContent)
{
    $htmlContent = (string) $htmlContent;

    $css = <<<'CSS'
* { box-sizing: border-box; }
    body { font-family: Arial; margin: 0; padding: 12px; background: #ffffff; }
    span, a { display: inline-block; text-decoration: none; color: #c9c9c9; }
    img { vertical-align: middle; }
    h3 {
        font-family: 'Roboto Condensed', Arial, sans-serif;
        font-size: 32px;
        letter-spacing: 3px;
        color: #c9c9c9;
        text-align: center;
        margin: 10px 0;
    }
    .pageHeading { text-align: center; margin-bottom: 24px; }
    .subHeading {
        text-align: center;
        font-size: 13px;
        font-weight: bold;
        color: #111;
        margin: 4px 0;
    }

    /* Legacy "download" link — hidden, page already renders its own button. */
    .download { display: none !important; }

    /* Race-number quick-nav pills */
    .slider { text-align: center; margin: 20px 0; }
    .slider a.race_call {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        margin: 0 4px;
        border-radius: 50%;
        background: #16a34a;
        color: #ffffff !important;
        font-weight: 700;
        font-size: 14px;
    }

    table { border-collapse: collapse; width: 100%; }
    .table { max-width: 100%; margin-bottom: 1rem; background-color: transparent; }
    .table-bordered { border: 1px solid #e2e2e2; }
    .table-bordered th, .table-bordered td { border: none; }

    /* ---- Race header bar (green) ---- */
.race_no_data {
    background: #16a34a !important;
    border-radius: 4px;
    margin: 36px 0 18px;
    overflow: hidden;
}

/* ---- Spacing between horse cards ---- */

td > table.infoTable[style*='box-shadow'] {
    margin: 18px 0 !important;
    padding: 24px 28px !important;
}

    .race_no_data th {
    background: transparent;
    color: #ffffff !important;
    text-align: left;
    padding: 12px 26px;
    font-size: 14px;
    line-height: 1.1;
    vertical-align: top;
    border: none !important;
}
.race_no_data th,
.race_no_data th span,
.race_no_data th * {
    color: #ffffff !important;
}

    .darkGrey { color: #ffffff !important; font-weight: bold; }
    .foreign_eligible2 span { display: block; margin: 2px 0; }

    /* ---- Horse card (grey box, background comes from inline style) ---- */
    
    .infoTable { width: 100%; }
    .infoTable td { border: 0; padding: 4px 6px; font-size: 13.5px; color: #222; font-weight: 700; }
    .horse_number_class { color: #111 !important; }
    .alignLeft { text-align: left !important; }
    .alignRight { text-align: right !important; }

    .infoTable img { border: 2px solid #16a34a !important; border-radius: 2px; }
    .infoTable td:has(> img) { text-align: right !important; }

    /* "View Runs" button */
    .view_perform { cursor: pointer; }
    .view_runs {
        display: inline-block;
        background: #16a34a;
        color: #ffffff !important;
        font-weight: 700;
        font-size: 12px;
        padding: 6px 16px;
        border-radius: 4px;
        cursor: pointer;
    }
    .view_runs:hover { background: #12833b; }

    /* ---- Performance history table ---- */
    .perform_head td {
        background: #f7f7f7;
        font-weight: bold;
        font-size: 12px;
        padding: 8px;
        border: 1px solid #e2e2e2;
        text-align: center;
    }
    
    .perform_data td {
    font-size: 12px;
    padding: 8px;
    border: 1px solid #e2e2e2;
    text-align: center;
    font-weight: 700;
}

    /* ---- Pools table ---- */
    .poolsTable th {
        background: #16a34a;
        color: #ffffff !important;
        padding: 10px;
        text-align: left;
    }
    .poolsTable td {
        padding: 10px;
        border: 1px solid #e2e2e2;
    }

    @media (min-width: 500px) and (max-width: 2560px) {
        .show1 { display: none; }
    }

    @media (max-width: 500px) {
        td, th { font-size: 11px !important; }
        .perform_data {
            display: contents;
            border: 1px solid #cdced3 !important;
            border-radius: 12px;
            background: #cdced3 !important;
        }
        .perform_data td:before {
            content: attr(data-label);
            float: left;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: bold;
            width: 45%;
        }
    }
    body { min-height: 100vh; }
    .download { display: none !important; }

CSS;

    if (stripos($htmlContent, "<html") !== false) {
        if (stripos($htmlContent, "</head>") !== false) {
            return preg_replace(
                "/<\/head>/i",
                "<style>" . $css . "</style></head>",
                $htmlContent,
                1
            );
        }

        if (stripos($htmlContent, "<body") !== false) {
            return preg_replace(
                "/<body([^>]*)>/i",
                "<head><style>" . $css . "</style></head><body$1>",
                $htmlContent,
                1
            );
        }
    }

    return "<!DOCTYPE html><html><head><meta charset=\"UTF-8\">"
        . "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">"
        . "<style>" . $css . "</style>"
        . "</head><body>"
        . $htmlContent
        . "</body></html>";
}

$type = isset($_REQUEST["type"])
    ? trim($_REQUEST["type"])
    : "";

$raceType = isset($_REQUEST["race_type"])
    ? trim($_REQUEST["race_type"])
    : "";

// ------------------------------------------------------------
// RESOLVE MISSING TYPE / RACE TYPE FROM run_race_details.
// No document-specific defaults are hard-coded here.
// ------------------------------------------------------------
function resolveRacecardMetadata($conn, $date, $type, $raceType)
{
    $type = trim((string) $type);
    $raceType = trim((string) $raceType);

    if ($type !== "" && $raceType !== "") {
        return array($type, $raceType);
    }

    if ($type !== "" && $raceType === "") {

        $stmt = $conn->prepare(
            "SELECT `race_type`
             FROM run_race_details
             WHERE `date` = ?
               AND `type` = ?
               AND file_url IS NOT NULL
               AND file_url <> ''
             ORDER BY id DESC
             LIMIT 1"
        );

        if ($stmt === false) {
            throw new Exception($conn->error);
        }

        $stmt->bind_param("ss", $date, $type);

    } elseif ($type === "" && $raceType !== "") {

        $stmt = $conn->prepare(
            "SELECT `type`
             FROM run_race_details
             WHERE `date` = ?
               AND `race_type` = ?
               AND file_url IS NOT NULL
               AND file_url <> ''
             ORDER BY id DESC
             LIMIT 1"
        );

        if ($stmt === false) {
            throw new Exception($conn->error);
        }

        $stmt->bind_param("ss", $date, $raceType);

    } else {

        $stmt = $conn->prepare(
            "SELECT `type`, `race_type`
             FROM run_race_details
             WHERE `date` = ?
               AND file_url IS NOT NULL
               AND file_url <> ''
             ORDER BY id DESC
             LIMIT 1"
        );

        if ($stmt === false) {
            throw new Exception($conn->error);
        }

        $stmt->bind_param("s", $date);
    }

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new Exception($error);
    }

    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {

        $row = $result->fetch_assoc();

        if ($type === "" && isset($row["type"])) {
            $type = trim((string) $row["type"]);
        }

        if ($raceType === "" && isset($row["race_type"])) {
            $raceType = trim((string) $row["race_type"]);
        }
    }

    $stmt->close();

    if ($type === "" || $raceType === "") {
        throw new Exception(
            "Unable to resolve racecard type and race_type"
        );
    }

    return array($type, $raceType);
}

// ------------------------------------------------------------
// POST: register the newly uploaded S3 HTML file.
// ------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $postDate = isset($_POST["date"]) ? trim($_POST["date"]) : "";
    $postType = isset($_POST["type"]) ? trim($_POST["type"]) : "";
    $postRaceType = isset($_POST["race_type"]) ? trim($_POST["race_type"]) : "";
    $htmlFile = isset($_POST["html_file"]) ? trim($_POST["html_file"]) : "";
    $fileUrl  = isset($_POST["file_url"]) ? trim($_POST["file_url"]) : "";

    if (
        $postDate === ""
        || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $postDate)
        || strtotime($postDate) === false
    ) {
        $security->respondError(
            "A valid date parameter (YYYY-MM-DD) is required",
            400
        );
        exit;
    }

    if ($htmlFile === "" && $fileUrl === "") {
        $security->respondError(
            "html_file or file_url is required",
            400
        );
        exit;
    }

    if ($postType === "" || $postRaceType === "") {
        $security->respondError(
            "type and race_type are required",
            400
        );
        exit;
    }

    try {
        $storedFileUrl = $fileUrl !== "" ? $fileUrl : $htmlFile;

        $stmt = $conn->prepare(
            "SELECT id
             FROM run_race_details
             WHERE `date` = ?
               AND `type` = ?
               AND `race_type` = ?
             LIMIT 1"
        );

        if ($stmt === false) {
            throw new Exception($conn->error);
        }

        $stmt->bind_param("sss", $postDate, $postType, $postRaceType);

        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }

        $result = $stmt->get_result();
        $existing = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if ($existing) {
            $stmt = $conn->prepare(
                "UPDATE run_race_details
                 SET file_url = ?
                 WHERE id = ?"
            );

            if ($stmt === false) {
                throw new Exception($conn->error);
            }

            $id = (int) $existing["id"];
            $stmt->bind_param("si", $storedFileUrl, $id);

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }

            $stmt->close();
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO run_race_details
                    (`date`, `type`, `race_type`, `file_url`)
                 VALUES (?, ?, ?, ?)"
            );

            if ($stmt === false) {
                throw new Exception($conn->error);
            }

            $stmt->bind_param(
                "ssss",
                $postDate,
                $postType,
                $postRaceType,
                $storedFileUrl
            );

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }

            $stmt->close();
        }

        $security->respondSuccess([
            "date"      => $postDate,
            "type"      => $postType,
            "race_type" => $postRaceType,
            "file_url"  => $storedFileUrl
        ]);

    } catch (Throwable $error) {

        $security->logLine(
            "RACECARD_PUSH_ERROR | " . $error->getMessage()
        );

        $security->respondError(
            "Internal server error",
            500
        );
    }

    exit;
}

// ------------------------------------------------------------
// GET: validate date.
// ------------------------------------------------------------
$date = isset($_GET["date"]) ? trim($_GET["date"]) : "";

if (
    $date === ""
    || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
    || strtotime($date) === false
) {
    $security->respondError(
        "A valid date parameter (YYYY-MM-DD) is required",
        400
    );
    exit;
}

// ------------------------------------------------------------
// Download endpoint: fetch HTML and open it with CSS.
// ------------------------------------------------------------
if (
    isset($_GET["download"])
    && (string) $_GET["download"] === "1"
) {

    try {

        $htmlContent = false;
        $source = "";

        $htmlFile = rtrim(RUN_RACES_LOCAL_PATH, "/\\")
            . "/Race_Card_" . $date . ".html";

        if (is_file($htmlFile)) {
            $source = "LOCAL_RUN_RACES";
            $htmlContent = file_get_contents($htmlFile);

            if ($htmlContent === false) {
                throw new Exception(
                    "Unable to read local racecard file"
                );
            }
        }

        if ($htmlContent === false) {

            if ($type === "" || $raceType === "") {
                list($type, $raceType) =
                    resolveRacecardMetadata(
                        $conn,
                        $date,
                        $type,
                        $raceType
                    );
            }

            $stmt = $conn->prepare(
                "SELECT file_url
                 FROM run_race_details
                 WHERE `date` = ?
                   AND `type` = ?
                   AND `race_type` = ?
                   AND file_url IS NOT NULL
                   AND file_url <> ''
                 LIMIT 1"
            );

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
                throw new Exception($stmt->error);
            }

            $result = $stmt->get_result();

            if (!$result || $result->num_rows === 0) {
                $stmt->close();

                $security->respondError(
                    "No race card found for this date",
                    404
                );

                exit;
            }

            $row = $result->fetch_assoc();
            $stmt->close();

            $htmlContent = readHtmlFromS3BucketApi(
                $row["file_url"] ?? ""
            );
            $source = "DB_S3";
        }

        if ($htmlContent === false || trim((string) $htmlContent) === "") {
            throw new Exception("Racecard HTML content is empty");
        }

        $downloadHtml = racecardWrapDownloadHtml($htmlContent);

        header("Content-Type: text/html; charset=UTF-8");
        header(
            'Content-Disposition: inline; filename="Race_Card_' . $date . '.html"'
        );
        header("X-Racecard-Source: " . $source);

        echo $downloadHtml;
        exit;

    } catch (Throwable $error) {

        $security->logLine(
            "RACECARD_DOWNLOAD_ERROR | "
            . $error->getMessage()
        );

        $security->respondError(
            "Internal server error",
            500
        );
    }

    exit;
}

// ------------------------------------------------------------
// HTML archive dates: date > 2022-11-08
// ------------------------------------------------------------
if ($date > "2022-11-08") {

    try {

        $htmlFile = rtrim(RUN_RACES_LOCAL_PATH, "/\\")
            . "/Race_Card_" . $date . ".html";

        $htmlContent = false;
        $source = "";

        if (is_file($htmlFile)) {

            $source = "LOCAL_RUN_RACES";
            $htmlContent = file_get_contents($htmlFile);

            if ($htmlContent === false) {
                throw new Exception(
                    "Unable to read local racecard file: " . $htmlFile
                );
            }
        }

        if ($htmlContent === false) {

            if ($type === "" || $raceType === "") {
                list($type, $raceType) =
                    resolveRacecardMetadata(
                        $conn,
                        $date,
                        $type,
                        $raceType
                    );
            }

            $stmt = $conn->prepare(
                "SELECT file_url
                 FROM run_race_details
                 WHERE `date` = ?
                   AND `type` = ?
                   AND `race_type` = ?
                   AND file_url IS NOT NULL
                   AND file_url <> ''
                 LIMIT 1"
            );

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
                throw new Exception($stmt->error);
            }

            $result = $stmt->get_result();

            if (!$result || $result->num_rows === 0) {
                $stmt->close();

                $security->respondError(
                    "No race card found for this date",
                    404
                );

                exit;
            }

            $row = $result->fetch_assoc();
            $stmt->close();

            $htmlContent = readHtmlFromS3BucketApi(
                $row["file_url"] ?? ""
            );
            $source = "DB_S3";
        }

        if ($htmlContent === false) {
            throw new Exception("Racecard HTML content is empty");
        }

        $downloadAvailable = true;

        $downloadFile =
            rtrim(
                "https://test.rwitc.com/rwitc-website/rwitc_website_api/Racecard_get_api.php",
                "/"
            )
            . "?date="
            . rawurlencode($date)
            . "&type="
            . rawurlencode($type)
            . "&race_type="
            . rawurlencode($raceType)
            . "&download=1";

        $response = [
            "found"              => true,
            "date"               => $date,
            "mode"               => "html",
            "source"             => $source,
            "html"               => $htmlContent,
            "download_file"      => $downloadFile,
            "download_available" => $downloadAvailable
        ];

        $security->respondSuccess($response);

    } catch (Throwable $error) {

        $security->logLine(
            "RACECARD_HTML_READ_ERROR | "
            . $error->getMessage()
        );

        $security->respondError(
            "Internal server error",
            500
        );
    }

    exit;
}

// --------------------------------------------------
// HISTORICAL MODE METADATA
// Resolve missing values dynamically from run_race_details.
// --------------------------------------------------

if ($type === "" || $raceType === "") {
    try {
        list($type, $raceType) =
            resolveRacecardMetadata(
                $conn,
                $date,
                $type,
                $raceType
            );
    } catch (Throwable $metadataError) {
        $type = trim((string) $type);
        $raceType = trim((string) $raceType);
    }
}

// --------------------------------------------------
// CACHE KEY (per date - historical data never changes) (unchanged)
// --------------------------------------------------

$cacheKey = "racecard_" . md5($date);

if ($security->serveCache($cacheKey)) {
    exit;
}

// --------------------------------------------------
// HELPERS (unchanged - pure functions, no DB access)
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

function divisionLabel($div)
{
    switch ((int) ($div ?? 0)) {
        case 1:
            return "Division I";
        case 2:
            return "Division II";
        case 3:
            return "Division III";
        default:
            return "";
    }
}

// Mirrors the PLACING code -> label switch in the legacy race-card page.
function placingLabel($raw)
{
    $map = [
        0  => "-",
        55 => "NDS",
        56 => "NS",
        57 => "NPR",
        58 => "WD",
        59 => "BO",
        60 => "DQ",
        61 => "-",
    ];

    if (array_key_exists((int) $raw, $map)) {
        return $map[(int) $raw];
    }

    if ((int) $raw > 0 && (int) $raw <= 24) {
        return (string) (int) $raw;
    }

    return (string) $raw;
}

// Video link logic: races before 2015-07-23 point at the old
// mumbairaces.com archive (keyed by channel/category from the Videos
// table); races on/after that date point at the rwitcraces.com archive
// (keyed by date + day race number).
const VIDEO_CUTOVER_DATE = "2015-07-23";

/**
 * OPTIMIZATION: no longer takes $conn / performs a query.
 * Instead of querying the `videos` table once per performance row (the
 * original N+1 hotspot for pre-2015 races), it now looks the channel/
 * category data up in an already-bulk-fetched in-memory map
 * ($videoDataByDate, keyed by RACEDATE), and receives the pre-computed
 * cutover timestamp instead of recalculating strtotime(VIDEO_CUTOVER_DATE)
 * on every call.
 */
function buildVideoUrl(array $racePerf, array $videoDataByDate, $cutoverTs)
{
    $raceDate      = $racePerf["RACEDATE"];
    $raceDateTs    = strtotime($raceDate); // computed once per row, used twice below

    if ($raceDateTs < $cutoverTs) {
        $chanData = $videoDataByDate[$raceDate] ?? null;

        if ($chanData === null) {
            return null;
        }

        return "http://www.mumbairaces.com/index.php?raceno={$racePerf["RACENO"]}&chan={$chanData["chan"]}&cat={$chanData["cat"]}";
    }

    $formattedDate = date("dmY", $raceDateTs);

    return "https://www.rwitcraces.com/RaceArchives.aspx?d={$formattedDate}&rno={$racePerf["DAYRACENO"]}";
}

// --------------------------------------------------
// BULK DATA-ACCESS FUNCTIONS
// (extracted from lib/race.class.php and lib/videos.class.php, but
// rewritten to fetch ALL rows needed for the whole request in a single
// query instead of once per race / once per horse — eliminating the
// N+1 query pattern of the previous implementation.)
// --------------------------------------------------

/**
 * BULK VERSION of getFcardData().
 *
 * Original: one query per race, filtered by RACENO_SEA.
 * Optimized: ONE query for the entire date (no RACENO_SEA filter);
 * results are grouped in PHP into $rowsByRaceNoSea[RACENO_SEA][] = row,
 * preserving the original CARDNO ASC ordering within each group (MySQL
 * returns rows in ORDER BY sequence, and appending to an array preserves
 * that sequence).
 *
 * @return array<string, array<int, array>> keyed by RACENO_SEA (as string)
 */
function getFcardDataBulk($conn, $date)
{
    $stmt = $conn->prepare(
        "SELECT fc.RACENO, fc.CARDNO, fc.HORSESEQ, fc.WEIGHT, fc.TRAINERNM, fc.HORSENAME, fc.LATENAME, fc.SIREDAM,
                fc.COLOR, fc.SEX, fc.AGE, fc.EQPT, fc.ACCOWN1, fc.ACCOWN2, fc.ACCOWN3, fc.ACCOWN4,
                fc.ENTO1, fc.ENTO2, fc.ENTO3, fc.ENTO4,
                fc.FINALNAME, fc.FINALNAME1, fc.FINALNAME2, fc.FINALNAME3, fc.SEXETC, fc.COLOURS1, fc.LRUNDATE,
                fc.COLNO, fc.DATEFOAL, fc.RUNGELD, fc.RUNSDATA, fc.RATING, fc.STUD, fc.DATEGELD, fc.PBREEDER,
                fc.RACEDATE, fc.SHOE, fc.SHOEDET, fc.JOCKEYNM, fc.DRAWNO, fc.BITSDET, fc.RACENO_SEA,
                fc.HRATACH, fc.DISTWON, hm.DAMNAT
         FROM fcard fc
         INNER JOIN hmaster hm ON fc.HORSESEQ = hm.HORSESEQ
         WHERE fc.RACEDATE = ?
         ORDER BY fc.RACENO_SEA ASC, fc.CARDNO ASC"
    );
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();

    $rowsByRaceNoSea = [];
    while ($row = $result->fetch_assoc()) {
        // Cast to string so the key type always matches the lookup key
        // built from $prospect["RACENO_SEA"] later (mysqli returns
        // numeric columns as strings under non-native binding).
        $rowsByRaceNoSea[(string) $row["RACENO_SEA"]][] = $row;
    }
    $stmt->close();

    return $rowsByRaceNoSea;
}

/**
 * BULK VERSION of fetchHorseHistoryRows().
 *
 * Original: one query per horse (WHERE f.HORSESEQ = ?), called once for
 * "fhorse5" and once for "ofhorse5" per horse -> 2 queries per horse.
 * Optimized: ONE query per table for ALL horses at once, using
 * WHERE f.HORSESEQ IN (...).
 *
 * IMPORTANT correctness note: the original per-horse query bound the SAME
 * $horseseq value to both `d.HORSESEQ = ?` (in the decl join) and
 * `f.HORSESEQ = ?` (in the WHERE clause). Since both were pinned to one
 * horse per call, `d.HORSESEQ = ?` was logically equivalent to
 * `d.HORSESEQ = f.HORSESEQ` for every row that query could return. That
 * equivalence is what's used here to make the join safe for a bulk
 * multi-horse IN(...) query: the join condition is rewritten as a direct
 * column correlation (`d.HORSESEQ = f.HORSESEQ`), which reproduces
 * byte-for-byte the same per-horse result rows as before.
 *
 * @param array $horseseqs list of distinct HORSESEQ values to fetch
 * @return array<string, array<int, array>> keyed by HORSESEQ (as string),
 *         rows within each group ordered RACEDATE DESC exactly as before
 */
function fetchHorseHistoryRowsBulk($conn, $table, array $horseseqs, $filterActiveDecl)
{
    if (empty($horseseqs)) {
        return [];
    }

    // Only "fhorse5" or "ofhorse5" are ever passed in from this file —
    // never user input — so interpolating the table name is safe.
    $declFilter = $filterActiveDecl ? "AND d.RACENO_SEA <> '0'" : "";

    $placeholders = implode(",", array_fill(0, count($horseseqs), "?"));

    $sql = "SELECT h.HORSENM, h.TRAINERNME, h.SIRE, h.DAM, h.DAMNAT, j.JOCKEYNM, f.*,
                    t.TRAINERNM, t.LISCENCE, d.RACENO AS DAYRACENO
             FROM {$table} f
             LEFT JOIN hmaster h ON f.HORSESEQ = h.HORSESEQ
             LEFT JOIN jockeys j ON f.JOCKEY = j.JOCKEY
             LEFT JOIN trainers t ON h.TRAINER = t.TRAINER
             LEFT JOIN decl d ON f.RACEDATE = d.RACEDATE AND d.HORSESEQ = f.HORSESEQ
             WHERE f.HORSESEQ IN ({$placeholders}) {$declFilter}
             ORDER BY f.HORSESEQ ASC, f.RACEDATE DESC";

    $stmt = $conn->prepare($sql);

    // Bind the dynamic list of integer horseseqs. Values come straight
    // from DB-fetched fcard rows (never raw user input), and are cast to
    // int below before binding.
    $types = str_repeat("i", count($horseseqs));
    $stmt->bind_param($types, ...$horseseqs);
    $stmt->execute();
    $result = $stmt->get_result();

    $rowsByHorseseq = [];
    while ($row = $result->fetch_assoc()) {
        $rowsByHorseseq[(string) $row["HORSESEQ"]][] = $row;
    }
    $stmt->close();

    return $rowsByHorseseq;
}

/**
 * BULK VERSION of getVideoChannelData().
 *
 * Original: one query per performance row that fell before the video
 * cutover date. Optimized: collect every distinct pre-cutover RACEDATE
 * across ALL horses' history up front, then fetch them all in one
 * WHERE racedate IN (...) query.
 *
 * @param array $raceDates list of distinct pre-cutover race dates
 * @return array<string, array> keyed by racedate -> ["chan"=>..,"cat"=>..]
 */
function getVideoChannelDataBulk($conn, array $raceDates)
{
    if (empty($raceDates)) {
        return [];
    }

    $placeholders = implode(",", array_fill(0, count($raceDates), "?"));

    $stmt = $conn->prepare(
        "SELECT racedate, chan, cat FROM videos WHERE racedate IN ({$placeholders})"
    );
    $types = str_repeat("s", count($raceDates));
    $stmt->bind_param($types, ...$raceDates);
    $stmt->execute();
    $result = $stmt->get_result();

    $dataByDate = [];
    while ($row = $result->fetch_assoc()) {
        // Original used getSingleRowAssoc (first row per date) — keep
        // that behavior by not overwriting an already-set date.
        if (!isset($dataByDate[$row["racedate"]])) {
            $dataByDate[$row["racedate"]] = [
                "chan" => $row["chan"],
                "cat"  => $row["cat"],
            ];
        }
    }
    $stmt->close();

    return $dataByDate;
}

// --------------------------------------------------
// FETCH DATA
// --------------------------------------------------

try {

    // ---- Day narrative (unchanged: single query, was never in a loop) ----

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

    // ---- Race-level headers (unchanged: single query, was never in a loop) ----

    $raceHeaders = [];

    $stmt = $conn->prepare(
        "SELECT p.SRNO, p.NAME AS RACENAME, p.NARRENT, p.DISTANCE, p.FJOCK,
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
            "message" => "No race card found for {$date}",
            "races"   => [],
        ]);
        exit;
    }

    // ----------------------------------------------------------------
    // OPTIMIZATION: fetch ALL fcard rows for the date in one query
    // (previously: one query per race inside the race loop).
    // ----------------------------------------------------------------

    $fcardByRaceNoSea = getFcardDataBulk($conn, $date);

    // ----------------------------------------------------------------
    // OPTIMIZATION: collect every distinct HORSESEQ across every race
    // up front, so history can be bulk-fetched in exactly 2 queries
    // total (one for "fhorse5", one for "ofhorse5") instead of 2 queries
    // per horse.
    // ----------------------------------------------------------------

    $allHorseseqs = [];
    foreach ($fcardByRaceNoSea as $rows) {
        foreach ($rows as $fcard) {
            $allHorseseqs[(int) $fcard["HORSESEQ"]] = true; // dedupe via key
        }
    }
    $allHorseseqs = array_keys($allHorseseqs);

    $currentHistoryByHorseseq = fetchHorseHistoryRowsBulk($conn, "fhorse5", $allHorseseqs, true);
    $oldHistoryByHorseseq     = fetchHorseHistoryRowsBulk($conn, "ofhorse5", $allHorseseqs, false);

    // ----------------------------------------------------------------
    // OPTIMIZATION: figure out which distinct pre-cutover RACEDATEs will
    // need a video lookup by scanning the already-fetched history (no DB
    // access here), then fetch all of them in a single bulk query
    // (previously: one query per qualifying performance row, nested two
    // loops deep).
    // ----------------------------------------------------------------

    $cutoverTs = strtotime(VIDEO_CUTOVER_DATE); // computed once, reused for every row

    $preCutoverDates = [];
    foreach ($allHorseseqs as $horseseq) {
        $key = (string) $horseseq;
        $merged = array_merge(
            $currentHistoryByHorseseq[$key] ?? [],
            $oldHistoryByHorseseq[$key] ?? []
        );
        // Only the first 5 rows per horse are ever rendered (matches the
        // original array_slice(...,0,5) below), so only those need a
        // potential video lookup.
        foreach (array_slice($merged, 0, 5) as $perf) {
            if (strtotime($perf["RACEDATE"]) < $cutoverTs) {
                $preCutoverDates[$perf["RACEDATE"]] = true; // dedupe via key
            }
        }
    }
    $preCutoverDates = array_keys($preCutoverDates);

    $videoDataByDate = getVideoChannelDataBulk($conn, $preCutoverDates);

    // ---- Per-race horse cards + performance history (now loop-only, no DB calls) ----

    $races = [];

    foreach ($raceHeaders as $raceNo => $prospect) {

        $raceNoSeaKey = (string) ($prospect["RACENO_SEA"] ?? "");
        $fcardRows    = $fcardByRaceNoSea[$raceNoSeaKey] ?? [];

        $horses = [];

        foreach ($fcardRows as $fcard) {

            $horseseqKey = (string) $fcard["HORSESEQ"];

            // Pulled from the pre-fetched bulk maps — no query here.
            $fullHistory = array_merge(
                $currentHistoryByHorseseq[$horseseqKey] ?? [],
                $oldHistoryByHorseseq[$horseseqKey] ?? []
            );

            // Legacy page merges "new" + "old" history and shows first 5.
            $history = array_slice($fullHistory, 0, 5);

            $performanceHistory = [];

            foreach ($history as $perf) {
                $performanceHistory[] = [
                    "race_date"  => $perf["RACEDATE"],
                    "race_no"    => $perf["RACENO"],
                    "jockey"     => $perf["JOCKEYNM"],
                    "race_class" => $perf["RACECAT"],
                    "distance"   => $perf["DISTANCE"],
                    "weight"     => $perf["WEIGHTCD"],
                    "placing"    => placingLabel($perf["PLACING"]),
                    "time"       => "{$perf["TIMINGMTS"]}:{$perf["TIMINGSEC"]}:{$perf["TIMINGSECD"]}",
                    "video_url"  => buildVideoUrl($perf, $videoDataByDate, $cutoverTs),
                ];
            }

            $ownership = $fcard["FINALNAME"] ?? "";
            foreach (["FINALNAME1", "FINALNAME2", "FINALNAME3"] as $ownField) {
                if (!empty($fcard[$ownField]) && trim($fcard[$ownField]) !== "") {
                    $ownership .= $fcard[$ownField];
                }
            }

            $rating = $fcard["RATING"] ?? null;
            if ($rating == -99) {
                $rating = "NR";
            }

            $horses[] = [
                "card_no"             => $fcard["CARDNO"] ?? null,
                "name"                => $fcard["HORSENAME"] ?? null,
                "horseseq"            => $fcard["HORSESEQ"] ?? null,
                "weight"              => $fcard["WEIGHT"] ?? null,
                "jockey"              => $fcard["JOCKEYNM"] ?? null,
                "trainer"             => $fcard["TRAINERNM"] ?? null,
                "sire_dam"            => $fcard["SIREDAM"] ?? null,
                "dam_nation"          => $fcard["DAMNAT"] ?? null,
                "draw_no"             => $fcard["DRAWNO"] ?? null,
                "equipment"           => $fcard["EQPT"] ?? null,
                "shoe"                => $fcard["SHOE"] ?? null,
                "shoe_detail"         => $fcard["SHOEDET"] ?? null,
                "bits_detail"         => $fcard["BITSDET"] ?? null,
                "stud"                => $fcard["STUD"] ?? null,
                "colours_owner_code"  => $fcard["ACCOWN1"] ?? null,
                "colour_no"           => $fcard["COLNO"] ?? null,
                "breeder"             => $fcard["PBREEDER"] ?? null,
                "foaled"              => $fcard["DATEFOAL"] ?? null,
                "rating"              => $rating,
                "hra_rating"          => isset($fcard["HRATACH"]) ? trim($fcard["HRATACH"]) : null,
                "distance_won"        => (!empty($fcard["DISTWON"])) ? $fcard["DISTWON"] : null,
                "ownership"           => $ownership,
                "sex_etc"             => $fcard["SEXETC"] ?? null,
                "runs_data"           => $fcard["RUNSDATA"] ?? null,
                "colours"             => $fcard["COLOURS1"] ?? null,
                "performance_history" => $performanceHistory,
            ];
        }

        $races[] = [
            "race_no"                  => $raceNo,
            "race_no_season"           => $prospect["RACENO_SEA"] ?? null,
            "race_name"                => $prospect["RACENAME"],
            "division"                 => divisionLabel($prospect["DIV"] ?? 0),
            "narrative_entry"          => $prospect["NARRENT"],
            "distance"                 => $prospect["DISTANCE"],
            "time"                     => $prospect["RTIME"] ?? "",
            "foreign_jockeys_eligible" => isset($prospect["FJOCK"]) && $prospect["FJOCK"] == 1,
            "horses"                   => $horses,
        ];
    }

    // ---- Pools (unchanged: single query, was never in a loop) ----

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


    // ---- Download file link (unchanged) ----


    $downloadUrl = null;
    if (preg_match('/\d\d\d(\d)-(\d\d)-(\d\d)/', $date, $matchDate)) {
        $fileDate = $matchDate[3] . $matchDate[2] . $matchDate[1];
        $downloadBase = defined("DOWNLOADFILE_BASE") ? DOWNLOADFILE_BASE : "";
        $downloadUrl = "https://rwitc.com/{$downloadBase}/RC{$fileDate}.HTM";  
    }


    $response = [
    "found"         => true,
    "date"          => $date,
    "mode"          => "json", // >>> CHANGE: added so frontend can branch json vs html
    "day_label"     => date("l jS F Y", strtotime($date)),
    "day_narrative" => $dayNarr,
    "club_name"     => defined("CLUB_NAME") ? CLUB_NAME : null,
    "download_url"  => $downloadUrl,
    "races"         => $races,
    "pools"         => $pools,
];

    // --------------------------------------------------
    // FINAL RESPONSE (unchanged)
    // --------------------------------------------------

    $security->respondAndCache(
        $cacheKey,
        $response
    );

} catch (Throwable $error) {

    // Log actual database error
    $security->logLine(
        "RACECARD_API_ERROR | "
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
