<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/config/run_races_config.php";
require_once __DIR__ . "/ApiSecurity.php";

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

$handle = @fopen($logDir . "/api_logs.txt", "a+");

// --------------------------------------------------
// SECURITY
// --------------------------------------------------

$security = new ApiSecurity($handle, [
    "rate_limit"     => 60,
    "rate_window"    => 60,
    "cache_ttl"      => 45,
    "cache_dir"      => __DIR__ . "/cache",
    "rate_limit_dir" => __DIR__ . "/rate_limits",
    "api_tag"        => "rating_change_get"
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
// HELPERS
// --------------------------------------------------

function getS3SetUrlApiUrl()
{
    if (defined("S3_SET_URL_API_URL") && S3_SET_URL_API_URL !== "") {
        return rtrim(S3_SET_URL_API_URL, "/");
    }

    $scheme = "https";

    if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") {
        $scheme = "https";
    } elseif (isset($_SERVER["REQUEST_SCHEME"]) && $_SERVER["REQUEST_SCHEME"] !== "") {
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

// S3 se key ke basis pe file padhta hai (sirf ratingschange folder allowed)
function readHtmlFromS3($s3Key)
{
    $s3Key = ltrim($s3Key, "/");

    if (
        strpos($s3Key, RATINGSCHANGE_S3_PREFIX) !== 0 ||
        !in_array(
            strtolower(pathinfo($s3Key, PATHINFO_EXTENSION)),
            ["html", "htm"],
            true
        )
    ) {
        throw new Exception("Invalid rating change S3 file key");
    }

    $helperUrl = getS3SetUrlApiUrl();

    if ($helperUrl === "") {
        throw new Exception("S3 HTML helper URL is not configured");
    }

    $ch = curl_init($helperUrl . "?key=" . rawurlencode($s3Key));

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_USERAGENT, "RWITC Rating Change S3 Reader");

    $content = curl_exec($ch);

    if ($content === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception("S3 helper cURL failed: " . $err);
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode < 200 || $httpCode >= 300) {
        throw new Exception("S3 helper returned HTTP " . $httpCode);
    }

    if (trim((string) $content) === "") {
        throw new Exception("S3 helper returned empty content");
    }

    return $content;
}

// Purani .HTM files Windows-1252 me hoti hain -> UTF-8
function ensureUtf8Html($content)
{
    $content = (string) $content;

    if (mb_check_encoding($content, "UTF-8")) {
        return $content;
    }

    $converted = mb_convert_encoding($content, "UTF-8", "Windows-1252");

    return preg_replace(
        '/<meta[^>]+charset[^>]*>/i',
        '<meta charset="UTF-8">',
        $converted,
        1
    );
}

// ratings_change table se is date ki saari filenames (latest pehle)
function getRatingChangeFilenames($conn, $date)
{
    $names = [];

    $stmt = $conn->prepare("
        SELECT filename
        FROM ratings_change
        WHERE DATE(racedate) = ?
          AND filename IS NOT NULL
          AND filename != ''
        ORDER BY id DESC
    ");

    if ($stmt === false) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param("s", $date);

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        throw new Exception($err);
    }

    $result = $stmt->get_result();

    while ($result && ($row = $result->fetch_assoc())) {
        $file = basename(trim((string) $row["filename"]));

        // safe filename only
        if (preg_match('/^[A-Za-z0-9_.\-]+\.html?$/i', $file)) {
            $names[] = $file;
        }
    }

    $stmt->close();

    return $names;
}

// 1) old local folder  2) S3.  Milne pe [html, source] return, warna null
// 1) old local folder (table ka naam + date pattern)  2) S3
function loadRatingChangeHtml($conn, $date, $security)
{
    $names = getRatingChangeFilenames($conn, $date);

    $localDir = defined("RATINGSCHANGE_LOCAL_PATH") && RATINGSCHANGE_LOCAL_PATH
        ? rtrim(RATINGSCHANGE_LOCAL_PATH, "/\\")
        : "";

    // 1. OLD LOCAL FOLDER
    if ($localDir !== "") {

        $candidates = [];

        // table me jo naam hai wo
        foreach ($names as $file) {
            $candidates[] = $localDir . "/" . $file;
        }

        // table ka naam galat/alag ho to date se dhundo: *_2026-09-20.HTM / .htm / .html
        $found = glob($localDir . "/*_" . $date . ".[Hh][Tt][Mm]*");

        if (is_array($found)) {
            foreach ($found as $path) {
                $candidates[] = $path;
            }
        }

        foreach (array_unique($candidates) as $path) {
            if (is_file($path)) {
                $content = file_get_contents($path);

                if ($content !== false && trim($content) !== "") {
                    return [ensureUtf8Html($content), "LOCAL_RATINGSCHANGE"];
                }
            }
        }
    }

    // 2. S3 (table me jo filename hai)
    foreach ($names as $file) {
        try {
            $content = readHtmlFromS3(RATINGSCHANGE_S3_PREFIX . $file);

            return [ensureUtf8Html($content), "S3_RATINGSCHANGE"];
        } catch (Throwable $e) {
            $security->logLine(
                "RATING_CHANGE_S3_MISS | date={$date} | file={$file} | "
                    . $e->getMessage()
            );
        }
    }

    return null;
}

function ratingChangeDownloadCss()
{
    return <<<'CSS'
* { box-sizing: border-box; }
body { font-family: Arial, sans-serif; margin: 0; padding: 24px 20px 40px; color: #333333; background: #ffffff; }
span, a { text-decoration: none; color: #333333; }
.row { display: flex; flex-wrap: wrap; row-gap: 6px; }
.row > div { padding: 2px 10px 2px 0; line-height: 1.7; font-size: 12.5px !important; }
.MsoPlainText { margin: 0 0 10px; line-height: 1.6; }
p.MsoPlainText { margin-bottom: 14px; }
table { max-width: 100%; }
img { max-width: 100%; height: auto; }
@media (max-width: 500px) {
    body { padding: 16px; }
    .row > div { width: 100% !important; }
    table { width: 100% !important; }
}
CSS;
}

// --------------------------------------------------
// ONLY GET
// --------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    $security->respondError("Method not allowed", 405);
    exit;
}

// --------------------------------------------------
// PARAMETERS
// --------------------------------------------------

$date = isset($_GET["date"]) ? trim($_GET["date"]) : "";

if ($date === "") {
    $security->respondError("date is required (format YYYY-MM-DD)", 400);
    exit;
}

$d = DateTime::createFromFormat("Y-m-d", $date);

if (!$d || $d->format("Y-m-d") !== $date) {
    $security->respondError("Invalid date format, expected YYYY-MM-DD", 400);
    exit;
}

$isDownload = isset($_GET["download"]) && (string) $_GET["download"] === "1";

// --------------------------------------------------
// MAIN
// --------------------------------------------------

try {

    $loaded = loadRatingChangeHtml($conn, $date, $security);

    // ---------------- DOWNLOAD / OPEN MODE ----------------
    if ($isDownload) {

        if ($loaded === null) {
            throw new Exception("No rating change file found");
        }

        $htmlContent = $loaded[0];
        $css = ratingChangeDownloadCss();

        if (stripos($htmlContent, "<html") !== false) {

            if (stripos($htmlContent, "</head>") !== false) {
                $out = preg_replace(
                    "/<\\/head>/i",
                    "<style>\n" . $css . "\n</style>\n</head>",
                    $htmlContent,
                    1
                );
            } else {
                $out = "<!DOCTYPE html>\n<html>\n<head>\n<meta charset=\"UTF-8\">\n"
                    . "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n"
                    . "<style>\n" . $css . "\n</style>\n</head>\n<body>\n"
                    . $htmlContent . "\n</body>\n</html>";
            }
        } else {
            $out = "<!DOCTYPE html>\n<html>\n<head>\n<meta charset=\"UTF-8\">\n"
                . "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n"
                . "<title>Rating Change " . htmlspecialchars($date, ENT_QUOTES, "UTF-8") . "</title>\n"
                . "<style>\n" . $css . "\n</style>\n</head>\n<body>\n"
                . $htmlContent . "\n</body>\n</html>";
        }

        header("Content-Type: text/html; charset=UTF-8");
        header('Content-Disposition: inline; filename="Rating_change_' . $date . '.htm"');

        echo $out;

        exit;
    }

    // ---------------- JSON MODE ----------------
    if ($loaded === null) {
        $security->respondSuccess([
            "found"              => false,
            "date"               => $date,
            "message"            => "No rating Change Found",
            "html"               => "",
            "download_file"      => null,
            "download_available" => false
        ]);

        exit;
    }

    $baseParts = parse_url(RUN_RACES_BASE_URL);
    $origin = "";

    if (isset($baseParts["scheme"]) && isset($baseParts["host"])) {
        $origin = $baseParts["scheme"] . "://" . $baseParts["host"]
            . (isset($baseParts["port"]) ? ":" . $baseParts["port"] : "");
    }

    $downloadFile = $origin
        . $_SERVER["SCRIPT_NAME"]
        . "?date=" . urlencode($date)
        . "&download=1";

    $security->respondSuccess([
        "found"              => true,
        "date"               => $date,
        "source"             => $loaded[1],
        "html"               => $loaded[0],
        "download_file"      => $downloadFile,
        "download_available" => true
    ]);
} catch (Throwable $error) {

    $security->logLine("RATING_CHANGE_API_ERROR | " . $error->getMessage());

    $security->respondError("Internal server error", 500);
} finally {

    if (isset($conn)) {
        $conn->close();
    }

    if (isset($handle) && is_resource($handle)) {
        fclose($handle);
    }
}

exit;
