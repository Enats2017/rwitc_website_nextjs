<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . "/config/config.php";

require_once __DIR__ . "/config/run_races_config.php";

require_once __DIR__ . "/ApiSecurity.php";

require_once __DIR__ . "/../vendor/autoload.php";

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

if (!class_exists("ApiSecurity")) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "data"    => null,
        "error"   => "ApiSecurity class could not be loaded"
    ]);

    exit;
}


// ============================================================
// CONSTANTS (config se aate hain)
// ============================================================

$ratingsType     = RATINGS_TYPE;
$ratingsRaceType = RATINGS_RACE_TYPE;
$ratingsS3Key    = RATINGS_S3_KEY;

// ============================================================
// HELPERS
// ============================================================

// Legacy files Windows-1252 me ho sakti hain. Browser ko UTF-8 chahiye.
function ensureUtf8Html($content)
{
    $content = (string) $content;

    if (mb_check_encoding($content, "UTF-8")) {
        return $content;
    }

    return mb_convert_encoding($content, "UTF-8", "Windows-1252");
}

// Download link ka origin
function getRatingsApiOrigin()
{
    if (defined("RUN_RACES_BASE_URL")) {
        $baseParts = parse_url(RUN_RACES_BASE_URL);

        if (isset($baseParts["scheme"]) && isset($baseParts["host"])) {
            return $baseParts["scheme"] . "://" . $baseParts["host"]
                . (isset($baseParts["port"]) ? ":" . $baseParts["port"] : "");
        }
    }

    $scheme = (
        isset($_SERVER["HTTPS"]) &&
        $_SERVER["HTTPS"] !== "" &&
        strtolower($_SERVER["HTTPS"]) !== "off"
    ) ? "https" : "http";

    $host = isset($_SERVER["HTTP_HOST"]) ? trim($_SERVER["HTTP_HOST"]) : "";

    return $host !== "" ? $scheme . "://" . $host : "";
}


// ============================================================
// LOG FILE + API SECURITY
// ============================================================

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
    "api_tag"        => "all_horse_rating_get"
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

// Only GET requests are allowed
if ($_SERVER["REQUEST_METHOD"] !== "GET") {

    $security->respondError("Method not allowed", 405);

    if (isset($conn)) {
        $conn->close();
    }

    if (is_resource($handle)) {
        fclose($handle);
    }

    exit;
}

$isDownload = isset($_GET["download"]) && $_GET["download"] === "1";

// ============================================================
// MAIN
// ============================================================

try {

    // ---------- 1. Table se latest row (sirf date + existence ke liye) ----------
    $stmt = $conn->prepare("
        SELECT `date`
        FROM run_race_details
        WHERE `type` = ?
          AND `race_type` = ?
          AND (
                (htm_file_url IS NOT NULL AND htm_file_url <> '')
             OR (file_url IS NOT NULL AND file_url <> '')
          )
        ORDER BY `date` DESC, id DESC
        LIMIT 1
    ");

    if ($stmt === false) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param("ss", $ratingsType, $ratingsRaceType);

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    $result = $stmt->get_result();
    $row    = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;

    $stmt->close();

    // ---------- 2. S3 se file padho (fixed key) ----------
    $htmlContent = "";

    if ($row !== null) {

        $s3 = new S3Client([
            "version"     => "latest",
            "region"      => AWS_REGION,
            "credentials" => [
                "key"    => AWS_ACCESS_KEY_ID,
                "secret" => AWS_SECRET_ACCESS_KEY
            ]
        ]);

        try {

            $s3Result = $s3->getObject([
                "Bucket" => AWS_BUCKET,
                "Key"    => $ratingsS3Key
            ]);

            $htmlContent = ensureUtf8Html((string) $s3Result["Body"]);
        } catch (AwsException $awsError) {

            $code = $awsError->getAwsErrorCode();

            if ($code !== "NoSuchKey" && $code !== "NotFound") {
                throw $awsError;
            }

            // File S3 se gayab hai -> "not found" jaisa treat karo
            $security->logLine("ALL_HORSE_RATING_S3_MISSING | " . $ratingsS3Key);
            $htmlContent = "";
        }
    }

    $found = ($row !== null && trim($htmlContent) !== "");

    // ---------- 3. Not found ----------
    if (!$found) {

        if ($isDownload) {
            $security->respondError("Ratings file not found", 404);
        } else {
            echo json_encode([
                "success" => true,
                "data"    => [
                    "exists"             => false,
                    "type"               => $ratingsType,
                    "race_type"          => $ratingsRaceType,
                    "date"               => null,
                    "mode"               => "html",
                    "html"               => "",
                    "download_file"      => null,
                    "download_available" => false
                ],
                "error"   => null
            ]);
        }
    } elseif ($isDownload) {

        // ---------- 4a. DOWNLOAD MODE ----------
        header("Content-Type: text/html; charset=UTF-8");
        header('Content-Disposition: inline; filename="RATINGS.HTM"');
        header("Cache-Control: no-store");

        echo '<meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>RWITC Ratings</title>'
            . $htmlContent;
    } else {

        // ---------- 4b. JSON MODE ----------
        $downloadFile = getRatingsApiOrigin()
            . $_SERVER["SCRIPT_NAME"]
            . "?download=1";

        echo json_encode([
            "success" => true,
            "data"    => [
                "exists"             => true,
                "type"               => $ratingsType,
                "race_type"          => $ratingsRaceType,
                "date"               => $row["date"],
                "mode"               => "html",
                "html"               => $htmlContent,
                "download_file"      => $downloadFile,
                "download_available" => true
            ],
            "error"   => null
        ]);
    }
} catch (Throwable $error) {

    $security->logLine(
        "ALL_HORSE_RATING_READ_ERROR | " . $error->getMessage()
    );

    $security->respondError("Internal server error", 500);
} finally {

    if (isset($conn)) {
        $conn->close();
    }

    if (isset($handle) && is_resource($handle)) {
        fclose($handle);
    }
}