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
    "api_tag"        => "record_timings_get"
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

$cacheKey = "record_timings";

if ($security->serveCache($cacheKey)) {
    if (isset($conn)) { $conn->close(); }
    if (is_resource($handle)) { fclose($handle); }
    exit;
}

try {

    // -------------------------------------------------------
    // horseracing/recordTimings.htm serve karo (old page jaisa).
    // Is file me Mumbai + Pune dono tables already hote hain,
    // koi DB fallback nahi hai kyunki original page me bhi
    // sirf static HTM include ho raha tha.
    // -------------------------------------------------------
    $localFile = rtrim((string) RECORD_TIMINGS_LOCAL_PATH, "/\\") . "/recordTimings.htm";

    if (!is_file($localFile)) {
        throw new Exception("File not found: " . $localFile);
    }

    $html = file_get_contents($localFile);

    if ($html === false || trim($html) === "") {
        throw new Exception("Unable to read " . $localFile);
    }

    $security->respondAndCache($cacheKey, [
        "mode" => "html",
        "html" => $html
    ]);

} catch (Throwable $error) {

    $security->logLine("RECORD_TIMINGS_API_ERROR | " . $error->getMessage());
    $security->respondError("Internal server error", 500);

} finally {

    if (isset($conn)) { $conn->close(); }
    if (isset($handle) && is_resource($handle)) { fclose($handle); }
}