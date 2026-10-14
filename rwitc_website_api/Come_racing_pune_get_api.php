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
    "cache_ttl"      => 30,
    "cache_dir"      => __DIR__ . "/cache",
    "rate_limit_dir" => __DIR__ . "/rate_limits",
    "api_tag"        => "come_racing_pune_get"
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

$cacheKey = "come_racing_pune_points";

if ($security->serveCache($cacheKey)) {
    if (isset($conn)) { $conn->close(); }
    if (is_resource($handle)) { fclose($handle); }
    exit;
}

try {

    $conn->set_charset("utf8mb4");

    // The table always holds a single row (id = 1), edited from the admin panel.
    $result = $conn->query(
        "SELECT content, updated_at
         FROM come_racing_pune_content
         WHERE id = 1
         LIMIT 1"
    );

    if ($result === false) {
        throw new Exception($conn->error);
    }

    $row = $result->fetch_assoc();

    // One point per line. Empty lines are dropped; the box may be empty.
    $points = [];

    if ($row) {
        $lines = preg_split('/\n/', str_replace(["\r\n", "\r"], "\n", (string) $row["content"]));

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== "") {
                $points[] = $line;
            }
        }
    }

    $response = [
        "exists"     => count($points) > 0,
        "points"     => $points,
        "updated_at" => $row ? $row["updated_at"] : null
    ];

    $security->respondAndCache($cacheKey, $response);
} catch (Throwable $error) {

    $security->logLine("COME_RACING_PUNE_API_ERROR | " . $error->getMessage());

    $security->respondError("Internal server error", 500);
} finally {

    if (isset($conn)) {
        $conn->close();
    }

    if (isset($handle) && is_resource($handle)) {
        fclose($handle);
    }
}