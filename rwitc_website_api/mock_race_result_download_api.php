<?php

require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/ApiSecurity.php";
require_once __DIR__ . "/../vendor/autoload.php";

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

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
    "api_tag"        => "mock_race_result_download"
]);

if (!$security->gate()) {
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    $security->respondError("Method not allowed", 405);
    exit;
}

$date = isset($_GET["date"]) ? trim($_GET["date"]) : "";

$dateObject = DateTime::createFromFormat("Y-m-d", $date);

if (
    $date === ""
    || $dateObject === false
    || $dateObject->format("Y-m-d") !== $date
) {
    $security->respondError("Invalid date. Expected YYYY-MM-DD.", 400);
    exit;
}

try {

    if (!isset($conn) || !($conn instanceof mysqli)) {
        throw new Exception("Database connection is not available.");
    }

    $stmt = $conn->prepare(
        "SELECT htm_file_url
         FROM run_race_details
         WHERE `date` = ?
           AND `type` = 'mock_race_result'
           AND `race_type` = 'post_race'
           AND htm_file_url IS NOT NULL
           AND htm_file_url <> ''
         ORDER BY id DESC
         LIMIT 1"
    );

    if ($stmt === false) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param("s", $date);
    $stmt->execute();

    $result = $stmt->get_result();
    $row    = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;

    $stmt->close();

    if ($row === null) {
        $security->respondError("No file found for this date.", 404);
        exit;
    }

    $urlPath = parse_url($row["htm_file_url"], PHP_URL_PATH);
    $key     = ltrim(rawurldecode((string)$urlPath), "/");

    if (!preg_match('/^run_races\/Mock_Race_Result_\d{4}-\d{2}-\d{2}\.htm$/i', $key)) {
        $security->respondError("Invalid file.", 400);
        exit;
    }

    $s3 = new S3Client([
        "version"     => "latest",
        "region"      => AWS_REGION,
        "credentials" => [
            "key"    => AWS_ACCESS_KEY_ID,
            "secret" => AWS_SECRET_ACCESS_KEY
        ]
    ]);

    $object = $s3->getObject([
        "Bucket" => AWS_BUCKET,
        "Key"    => $key
    ]);

    $body = (string)$object["Body"];

    header("Content-Type: text/html; charset=UTF-8");
    header("Content-Disposition: inline; filename=\"" . basename($key) . "\"");
    header("Content-Length: " . strlen($body));
    header("Cache-Control: no-cache");
    header("X-Content-Type-Options: nosniff");

    echo $body;
} catch (AwsException $error) {

    $security->logLine("MOCK_RACE_DOWNLOAD_S3_ERROR | " . $error->getMessage());

    $statusCode = ($error->getStatusCode() === 404) ? 404 : 500;

    $security->respondError(
        $statusCode === 404 ? "File not found." : "Internal server error",
        $statusCode
    );
} catch (Throwable $error) {

    $security->logLine("MOCK_RACE_DOWNLOAD_ERROR | " . $error->getMessage());

    $security->respondError("Internal server error", 500);
} finally {

    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }

    if (isset($handle) && is_resource($handle)) {
        fclose($handle);
    }
}