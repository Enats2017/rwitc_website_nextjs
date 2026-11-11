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
    "api_tag"        => "app_links"
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

if ($security->serveCache("app_links_latest")) {
    if (isset($conn)) { $conn->close(); }
    if (is_resource($handle)) { fclose($handle); }
    exit;
}

try {

    // Response key => "type" value saved by turf-console/Applinks.php in club_menu_pdf
    $types = [
        "android" => "app_android_url",
        "ios"     => "app_ios_url"
    ];

    $links = [];

    foreach ($types as $name => $type) {

        $typeSql = $conn->real_escape_string($type);

        $result = $conn->query("SELECT file_url FROM club_menu_pdf WHERE `type` = '" . $typeSql . "' ORDER BY `date` DESC, id DESC LIMIT 1");

        if ($result === false) {
            throw new Exception($conn->error);
        }

        $row = $result->fetch_assoc();
        if (!$row) {
            continue;
        }

        $url    = trim((string)$row['file_url']);
        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));

        // Only http / https links are sent to the website
        if ($url === '' || ($scheme !== 'http' && $scheme !== 'https')) {
            continue;
        }

        $links[$name] = $url;
    }

    $security->respondAndCache("app_links_latest", $links);

} catch (Throwable $error) {

    $security->logLine("APP_LINKS_API_ERROR | " . $error->getMessage());
    $security->respondError("Internal server error", 500);

} finally {
    if (isset($conn)) { $conn->close(); }
    if (isset($handle) && is_resource($handle)) { fclose($handle); }
}