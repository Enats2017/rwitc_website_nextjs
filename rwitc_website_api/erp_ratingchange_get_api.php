<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/config/run_races_config.php";
require_once __DIR__ . "/ApiSecurity.php";

// --------------------------------------------------
// API SECURITY
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
// INITIALIZE SECURITY
// --------------------------------------------------

$security = new ApiSecurity($handle, [
    "rate_limit"     => 60,
    "rate_window"    => 60,
    "cache_ttl"      => 45,
    "cache_dir"      => __DIR__ . "/cache",
    "rate_limit_dir" => __DIR__ . "/rate_limits",
    "api_tag"        => "rating_change_get"
]);

// --------------------------------------------------
// RATE LIMIT
// --------------------------------------------------

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
// HELPERS
// --------------------------------------------------

function ratingChangeS3Key($fileUrl)
{
    $fileUrl = trim((string) $fileUrl);

    if ($fileUrl === "") {
        return "";
    }

    if (preg_match("/^https?:\\/\\//i", $fileUrl)) {
        $path = parse_url($fileUrl, PHP_URL_PATH);
        $fileUrl = $path !== null ? $path : "";
    }

    return ltrim(rawurldecode($fileUrl), "/");
}

function getS3SetUrlApiUrl()
{
    if (
        defined("S3_SET_URL_API_URL") &&
        S3_SET_URL_API_URL !== ""
    ) {
        return rtrim(S3_SET_URL_API_URL, "/");
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

function readHtmlFromS3SetUrl($fileUrl)
{
    $s3Key = ratingChangeS3Key($fileUrl);

    if (
        $s3Key === "" ||
        strpos($s3Key, "run_races/") !== 0 ||
        strtolower(pathinfo($s3Key, PATHINFO_EXTENSION)) !== "html"
    ) {
        throw new Exception(
            "Invalid rating change S3 file key"
        );
    }

    $helperUrl = getS3SetUrlApiUrl();

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
        "RWITC Rating Change S3 Reader"
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

function ratingChangeValidType($type)
{
    return preg_match(
        "/^[A-Za-z0-9_-]+$/",
        $type
    );
}

function ratingChangeValidRaceType($raceType)
{
    return preg_match(
        "/^[A-Za-z0-9_-]+$/",
        $raceType
    );
}

// --------------------------------------------------
// POST: ERP -> WEBSITE API
// --------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        $date = isset($_POST["date"])
            ? trim($_POST["date"])
            : "";

        $type = isset($_POST["type"])
            ? trim($_POST["type"])
            : "";

        $raceType = isset($_POST["race_type"])
            ? trim($_POST["race_type"])
            : "";

        $htmlFile = isset($_POST["html_file"])
            ? trim($_POST["html_file"])
            : "";

        $fileUrl = isset($_POST["file_url"])
            ? trim($_POST["file_url"])
            : "";

        if (
            $date === ""
            || $type === ""
            || $raceType === ""
            || $htmlFile === ""
        ) {
            $security->respondError(
                "date, type, race_type and html_file are required",
                400
            );

            exit;
        }

        $d = DateTime::createFromFormat(
            "Y-m-d",
            $date
        );

        if (
            !$d
            || $d->format("Y-m-d") !== $date
        ) {
            $security->respondError(
                "Invalid date format, expected YYYY-MM-DD",
                400
            );

            exit;
        }

        if (!ratingChangeValidType($type)) {
            $security->respondError(
                "Invalid type",
                400
            );

            exit;
        }

        if (!ratingChangeValidRaceType($raceType)) {
            $security->respondError(
                "Invalid race_type",
                400
            );

            exit;
        }

        // Only the intended S3 HTML location is accepted.
        $htmlFile = ltrim(
            rawurldecode($htmlFile),
            "/"
        );

        if (
            strpos($htmlFile, "run_races/") !== 0
            || !preg_match("/\\.html$/i", $htmlFile)
        ) {
            $security->respondError(
                "Invalid html_file",
                400
            );

            exit;
        }

        // Prefer the actual S3 URL returned by the uploader.
        // If it is empty, store the S3 key so the GET side can
        // still resolve the object.
        $storedFileUrl = $fileUrl !== ""
            ? $fileUrl
            : $htmlFile;

        // --------------------------------------------------
        // CHECK EXISTING EXACT RECORD
        // --------------------------------------------------

        $stmt = $conn->prepare("
            SELECT id
            FROM run_race_details
            WHERE `date` = ?
              AND `type` = ?
              AND `race_type` = ?
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
            throw new Exception($stmt->error);
        }

        $result = $stmt->get_result();

        $existingId = 0;

        if (
            $result
            && $result->num_rows > 0
        ) {
            $row = $result->fetch_assoc();
            $existingId = (int) $row["id"];
        }

        $stmt->close();

        // --------------------------------------------------
        // UPDATE OR INSERT
        // --------------------------------------------------

        if ($existingId > 0) {

            $stmt = $conn->prepare("
                UPDATE run_race_details
                SET file_url = ?
                WHERE id = ?
            ");

            if ($stmt === false) {
                throw new Exception($conn->error);
            }

            $stmt->bind_param(
                "si",
                $storedFileUrl,
                $existingId
            );

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }

            $stmt->close();

            $message = "Rating change file record updated successfully";

        } else {

            $stmt = $conn->prepare("
                INSERT INTO run_race_details
                    (`date`, `type`, `file_url`, `race_type`)
                VALUES
                    (?, ?, ?, ?)
            ");

            if ($stmt === false) {
                throw new Exception($conn->error);
            }

            $stmt->bind_param(
                "ssss",
                $date,
                $type,
                $storedFileUrl,
                $raceType
            );

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }

            $stmt->close();

            $message = "Rating change file record inserted successfully";
        }

        $security->logLine(
            "RATING_CHANGE_PUSH_SUCCESS | date={$date} | type={$type} | race_type={$raceType} | html_file={$htmlFile} | file_url={$storedFileUrl}"
        );

        $security->respondSuccess([
            "date"      => $date,
            "type"      => $type,
            "race_type" => $raceType,
            "html_file" => $htmlFile,
            "file_url"  => $storedFileUrl,
            "message"   => $message
        ]);

    } catch (Throwable $error) {

        $security->logLine(
            "RATING_CHANGE_PUSH_ERROR | "
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
// GET PARAMETERS
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

/*
 * Frontend uses "ratingChange" in the page URL.
 * run_race_details stores the canonical DB type as "rating_change".
 * Normalize the frontend alias before the DB lookup.
 */
if ($type === "ratingChange") {
    $type = "rating_change";
}

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
    !$d
    || $d->format("Y-m-d") !== $date
) {

    $security->respondError(
        "Invalid date format, expected YYYY-MM-DD",
        400
    );

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

        $metaStmt->bind_param(
            "ss",
            $date,
            $type
        );

        if ($metaStmt->execute()) {

            $metaResult = $metaStmt->get_result();

            if (
                $metaResult &&
                $metaResult->num_rows > 0
            ) {

                $metaRow = $metaResult->fetch_assoc();

                $raceType = trim(
                    (string) $metaRow["race_type"]
                );
            }
        }

        $metaStmt->close();
    }
}

if (!ratingChangeValidType($type)) {

    $security->respondError(
        "Invalid type",
        400
    );

    exit;
}

if (!ratingChangeValidRaceType($raceType)) {

    $security->respondError(
        "Invalid race_type",
        400
    );

    exit;
}

// --------------------------------------------------
// DOWNLOAD MODE
// --------------------------------------------------

if (
    isset($_GET["download"])
    && (string) $_GET["download"] === "1"
) {

    try {

        $htmlContent = false;
        $source = "";

        // --------------------------------------------------
        // 1. LOCAL HTML
        // --------------------------------------------------

        $htmlFile = RUN_RACES_LOCAL_PATH
            . "/Rating_change_" . $date . ".html";

        if (is_file($htmlFile)) {

            $htmlContent = file_get_contents(
                $htmlFile
            );

            if ($htmlContent === false) {
                throw new Exception(
                    "Unable to read local rating change file"
                );
            }

            $source = "LOCAL_RUN_RACES";
        }

        // --------------------------------------------------
        // 2. DB -> S3
        // --------------------------------------------------

        if ($htmlContent === false) {

            $stmt = $conn->prepare("
                SELECT file_url
                FROM run_race_details
                WHERE `date` = ?
                  AND `type` = ?
                  AND `race_type` = ?
                  AND file_url IS NOT NULL
                  AND file_url != ''
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
                throw new Exception($stmt->error);
            }

            $result = $stmt->get_result();

            if (
                !$result
                || $result->num_rows === 0
            ) {
                throw new Exception(
                    "No rating change file found"
                );
            }

            $row = $result->fetch_assoc();

            $s3Value = trim(
                $row["file_url"]
            );

            $stmt->close();

            if ($s3Value === "") {
                throw new Exception(
                    "Rating change S3 file URL is empty"
                );
            }

            $htmlContent = readHtmlFromS3SetUrl(
                $s3Value
            );

            $source = "DB_S3";
        }

        // --------------------------------------------------
        // INLINE CSS FOR OPEN/DOWNLOAD
        // --------------------------------------------------

        $downloadCss = <<<CSS
* {
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 24px 20px 40px;
    color: #333333;
    background: #ffffff;
}

span,
a {
    text-decoration: none;
    color: #333333;
}

.row {
    display: flex;
    flex-wrap: wrap;
    row-gap: 6px;
}

.row > div {
    padding: 2px 10px 2px 0;
    line-height: 1.7;
    font-size: 12.5px !important;
}

.MsoPlainText {
    margin: 0 0 10px;
    line-height: 1.6;
}

p.MsoPlainText {
    margin-bottom: 14px;
}

table {
    max-width: 100%;
}

img {
    max-width: 100%;
    height: auto;
}

@media (max-width: 500px) {
    body {
        padding: 16px;
    }

    .row > div {
        width: 100% !important;
    }

    table {
        width: 100% !important;
    }
}
CSS;

        // If the source is already a complete HTML document,
        // inject our CSS into <head>. Otherwise wrap the fragment.
        if (
            stripos($htmlContent, "<html") !== false
        ) {

            if (
                stripos(
                    $htmlContent,
                    "</head>"
                ) !== false
            ) {
                $downloadContent = preg_replace(
                    "/<\\/head>/i",
                    "<style>\n"
                    . $downloadCss
                    . "\n</style>\n</head>",
                    $htmlContent,
                    1
                );
            } else {
                $downloadContent =
                    "<!DOCTYPE html>\n"
                    . "<html>\n<head>\n"
                    . "<meta charset=\"UTF-8\">\n"
                    . "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n"
                    . "<style>\n"
                    . $downloadCss
                    . "\n</style>\n"
                    . "</head>\n<body>\n"
                    . $htmlContent
                    . "\n</body>\n</html>";
            }

        } else {

            $downloadContent =
                "<!DOCTYPE html>\n"
                . "<html>\n<head>\n"
                . "<meta charset=\"UTF-8\">\n"
                . "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n"
                . "<title>Rating Change "
                . htmlspecialchars(
                    $date,
                    ENT_QUOTES,
                    "UTF-8"
                )
                . "</title>\n"
                . "<style>\n"
                . $downloadCss
                . "\n</style>\n"
                . "</head>\n<body>\n"
                . $htmlContent
                . "\n</body>\n</html>";
        }

        header(
            "Content-Type: text/html; charset=UTF-8"
        );

        header(
            'Content-Disposition: inline; filename="Rating_change_'
            . $date
            . '.html"'
        );

        echo $downloadContent;

    } catch (Throwable $error) {

        $security->logLine(
            "RATING_CHANGE_DOWNLOAD_ERROR | "
            . $error->getMessage()
        );

        $security->respondError(
            "Internal server error",
            500
        );
    }

    if (isset($conn)) {
        $conn->close();
    }

    if (
        isset($handle)
        && is_resource($handle)
    ) {
        fclose($handle);
    }

    exit;
}

// --------------------------------------------------
// HTML ARCHIVE MODE
// --------------------------------------------------

try {

    $htmlContent = false;
    $source = "";

    // --------------------------------------------------
    // 1. LOCAL RUN_RACES
    // --------------------------------------------------

    $htmlFile = RUN_RACES_LOCAL_PATH
        . "/Rating_change_" . $date . ".html";

    if (is_file($htmlFile)) {

        $htmlContent = file_get_contents(
            $htmlFile
        );

        if ($htmlContent === false) {
            throw new Exception(
                "Unable to read local rating change file"
            );
        }

        $source = "LOCAL_RUN_RACES";
    }

    // --------------------------------------------------
    // 2. DB -> S3
    // --------------------------------------------------

    if ($htmlContent === false) {

        $stmt = $conn->prepare("
            SELECT file_url
            FROM run_race_details
            WHERE `date` = ?
              AND `type` = ?
              AND `race_type` = ?
              AND file_url IS NOT NULL
              AND file_url != ''
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
            throw new Exception($stmt->error);
        }

        $result = $stmt->get_result();

        if (
            !$result
            || $result->num_rows === 0
        ) {

            $stmt->close();

            $security->respondSuccess([
                "found" => false,
                "date"  => $date,
                "type"  => $type,
                "race_type" => $raceType,
                "message" =>
                    "No rating Change Found",
                "html"  => "",
                "download_file" => null,
                "download_available" => false
            ]);

            exit;
        }

        $row = $result->fetch_assoc();

        $s3Value = trim(
            $row["file_url"]
        );

        $stmt->close();

        if ($s3Value === "") {
            throw new Exception(
                "Rating change S3 file URL is empty"
            );
        }

        $htmlContent = readHtmlFromS3SetUrl(
            $s3Value
        );

        $source = "DB_S3";
    }

    // --------------------------------------------------
    // DOWNLOAD URL
    // --------------------------------------------------

    $downloadFile =
        "erp_ratingchange_get_api.php?"
        . "date=" . rawurlencode($date)
        . "&type=" . rawurlencode($type)
        . "&race_type=" . rawurlencode($raceType)
        . "&download=1";

    $response = [
        "found" => true,
        "date"  => $date,
        "type"  => $type,
        "race_type" => $raceType,
        "source" => $source,
        "html"  => $htmlContent,
        "download_file" => $downloadFile,
        "download_available" => true
    ];

    $security->respondSuccess(
        $response
    );

} catch (Throwable $error) {

    $security->logLine(
        "RATING_CHANGE_API_ERROR | "
        . $error->getMessage()
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
        isset($handle)
        && is_resource($handle)
    ) {
        fclose($handle);
    }
}

exit;