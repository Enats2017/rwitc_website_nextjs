<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

require_once __DIR__ . "/config/config.php";
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
    "api_tag"        => "feature_links"
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

if ($security->serveCache("feature_links_latest")) {
    if (isset($conn)) { $conn->close(); }
    if (is_resource($handle)) { fclose($handle); }
    exit;
}

try {

    // Response key => "type" value saved by turf-console/Featurelinks.php in the feature_links table
    $types = [
        "indian_stud_book" => "feature_indian_stud_book",
        "video_archives"   => "feature_video_archives",
        "webportal"        => "feature_webportal",
        "live_stream"      => "feature_live_stream"
    ];

    $links = [];

    foreach ($types as $name => $type) {

        $typeSql = $conn->real_escape_string($type);

        $result = $conn->query("SELECT url FROM feature_links WHERE `type` = '" . $typeSql . "' ORDER BY `updated` DESC, id DESC LIMIT 1");

        if ($result === false) {
            throw new Exception($conn->error);
        }

        $row = $result->fetch_assoc();
        if (!$row) {
            continue;
        }

        $url    = trim((string)$row['url']);
        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));

        // Only http / https links are sent to the website
        if ($url === '' || ($scheme !== 'http' && $scheme !== 'https')) {
            continue;
        }

        $links[$name] = $url;
    }

    $security->respondAndCache("feature_links_latest", $links);

} catch (Throwable $error) {

    $security->logLine("FEATURE_LINKS_API_ERROR | " . $error->getMessage());
    $security->respondError("Internal server error", 500);

} finally {
    if (isset($conn)) { $conn->close(); }
    if (isset($handle) && is_resource($handle)) { fclose($handle); }
}