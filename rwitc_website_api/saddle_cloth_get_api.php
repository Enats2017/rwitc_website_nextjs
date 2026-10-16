<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/config/run_races_config.php";
require_once __DIR__ . "/ApiSecurity.php";

$logDir = __DIR__ . "/logs";
if (!is_dir($logDir)) {
    @mkdir($logDir, 0750, true);
}
$handle = @fopen($logDir . "/api_logs.txt", "a+");

$security = new ApiSecurity($handle, [
    "rate_limit"     => 60,
    "rate_window"    => 60,
    "cache_ttl"      => 45,
    "cache_dir"      => __DIR__ . "/cache",
    "rate_limit_dir" => __DIR__ . "/rate_limits",
    "api_tag"        => "saddle_cloth_get"
]);

if (!$security->gate()) {
    if (isset($conn)) { $conn->close(); }
    if (is_resource($handle)) { fclose($handle); }
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    $security->respondError("Method not allowed", 405);
    if (isset($conn)) { $conn->close(); }
    if (is_resource($handle)) { fclose($handle); }
    exit;
}

// ------------------------------------------------------------
// S3 HELPERS (same pattern as the handicaps API)
// HTML is read through the existing s3_set_url.php helper.
// This API does not access S3 directly.
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
        $path  = parse_url($fileUrl, PHP_URL_PATH);
        $s3Key = ($path !== null) ? ltrim(rawurldecode($path), "/") : "";
    } else {
        $s3Key = ltrim(rawurldecode($fileUrl), "/");
    }

    if (
        $s3Key === "" ||
        strpos($s3Key, "run_races/") !== 0 ||
        !preg_match("/\\.html?$/i", $s3Key)
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
        CURLOPT_USERAGENT      => "RWITC-SaddleCloth-API/1.0"
    ]);

    $htmlContent = curl_exec($ch);
    $curlError   = curl_error($ch);
    $httpCode    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($htmlContent === false) {
        throw new Exception("S3 bucket API request failed: " . $curlError);
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        throw new Exception("S3 bucket API returned HTTP " . $httpCode);
    }

    if (trim((string) $htmlContent) === "") {
        throw new Exception("S3 bucket API returned empty HTML");
    }

    return (string) $htmlContent;
}

// Legacy files can be Windows-1252. Browser needs UTF-8.
function ensureUtf8Html($content)
{
    $content = (string) $content;

    if (mb_check_encoding($content, "UTF-8")) {
        return $content;
    }

    $converted = mb_convert_encoding($content, "UTF-8", "Windows-1252");

    $converted = preg_replace(
        '/<meta[^>]+charset[^>]*>/i',
        '<meta charset="UTF-8">',
        $converted,
        1
    );

    return $converted;
}

$cacheKey = "saddle_cloth";

if ($security->serveCache($cacheKey)) {
    if (isset($conn)) { $conn->close(); }
    if (is_resource($handle)) { fclose($handle); }
    exit;
}

try {

    // color => run_race_details.type (as sent by ERP)
    $colorTypes = [
        "green" => "saddle_cloth_green",
        "red"   => "saddle_cloth_red",
        "blue"  => "saddle_cloth_blue",
    ];

    $basePath = rtrim((string) RUN_RACES_LOCAL_PATH, "/\\");

    $sections = [];

    foreach ($colorTypes as $color => $docType) {

        $html   = false;
        $source = "";

        // ----------------------------------------------------
        // 1) S3 file registered by ERP in run_race_details
        //    (latest row for this type)
        // ----------------------------------------------------
        $stmt = $conn->prepare("
            SELECT file_url
            FROM run_race_details
            WHERE `type` = ?
              AND file_url IS NOT NULL
              AND file_url <> ''
            ORDER BY id DESC
            LIMIT 1
        ");

        if ($stmt === false) {
            throw new Exception($conn->error);
        }

        $stmt->bind_param("s", $docType);

        if (!$stmt->execute()) {
            $err = $conn->error;
            $stmt->close();
            throw new Exception($err);
        }

        $result = $stmt->get_result();
        $row    = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
        $stmt->close();

        if ($row) {
            try {
                $html   = readHtmlFromS3BucketApi($row["file_url"]);
                $source = "DB_S3";
            } catch (Throwable $s3Error) {
                // One bad color must not break the other colors
                $security->logLine(
                    "SADDLE_CLOTH_S3_READ_ERROR | " . $color . " | " . $s3Error->getMessage()
                );
                $html = false;
            }
        }

        // ----------------------------------------------------
        // 2) Fallback: old local file (only if S3 gave nothing)
        // ----------------------------------------------------
        if ($html === false || trim((string) $html) === "") {
            $localFile = $basePath . "/saddleCloth_" . $color . ".html";

            if (is_file($localFile)) {
                $localHtml = file_get_contents($localFile);

                if ($localHtml !== false && trim($localHtml) !== "") {
                    $html   = $localHtml;
                    $source = "LOCAL_RUN_RACES";
                }
            }
        }

        if ($html !== false && trim((string) $html) !== "") {
            $sections[] = [
                "color"  => $color,
                "source" => $source,
                "html"   => ensureUtf8Html($html)
            ];
        }
    }

    if (empty($sections)) {
        throw new Exception("No saddle cloth data found (S3 or local)");
    }

    $security->respondAndCache($cacheKey, [
        "mode"     => "html",
        "sections" => $sections
    ]);

} catch (Throwable $error) {

    $security->logLine("SADDLE_CLOTH_API_ERROR | " . $error->getMessage());
    $security->respondError("Internal server error", 500);

} finally {

    if (isset($conn)) { $conn->close(); }
    if (isset($handle) && is_resource($handle)) { fclose($handle); }
}