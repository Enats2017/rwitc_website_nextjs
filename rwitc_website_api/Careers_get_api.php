<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// Load database connection
require_once __DIR__ . "/config/config.php";

// Load ApiSecurity class
require_once __DIR__ . "/ApiSecurity.php";

// Make sure the class was loaded correctly
if (!class_exists("ApiSecurity")) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "data"    => null,
        "error"   => "ApiSecurity class could not be loaded"
    ]);
    exit;
}

// Create logs directory
$logDir = __DIR__ . "/logs";

if (!is_dir($logDir)) {
    @mkdir($logDir, 0750, true);
}

// Open log file
$handle = @fopen($logDir . "/api_logs.txt", "a+");

// Initialize API security (rate limit + cache)
$security = new ApiSecurity($handle, [
    "rate_limit"     => 60,
    "rate_window"    => 60,
    "cache_ttl"      => 30,
    "cache_dir"      => __DIR__ . "/cache",
    "rate_limit_dir" => __DIR__ . "/rate_limits",
    "api_tag"        => "careers_get"
]);

// Apply rate limiting
if (!$security->gate()) {
    if (isset($conn)) { $conn->close(); }
    if (is_resource($handle)) { fclose($handle); }
    exit;
}

// Only allow GET requests
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    $security->respondError("Method not allowed", 405);
    if (isset($conn)) { $conn->close(); }
    if (is_resource($handle)) { fclose($handle); }
    exit;
}

$cacheKey = "careers_latest";

// Return cached response if available
if ($security->serveCache($cacheKey)) {
    if (isset($conn)) { $conn->close(); }
    if (is_resource($handle)) { fclose($handle); }
    exit;
}

try {

    $conn->set_charset("utf8mb4");

    // Latest text entry by date
    $result = $conn->query(
        "SELECT id, report_date, title, content_text
         FROM careers
         WHERE `type` = 'text'
           AND content_text IS NOT NULL
           AND content_text <> ''
         ORDER BY report_date DESC, id DESC
         LIMIT 1"
    );

    if ($result === false) {
        throw new Exception($conn->error);
    }

    $row = $result->fetch_assoc();

    $response = [
        "exists" => (bool) $row,
        "date"   => $row ? $row["report_date"] : null,
        "title"  => ($row && trim((string) $row["title"]) !== "") ? $row["title"] : null,
        "text"   => $row ? $row["content_text"] : ""
    ];

    // Return successful response and cache it
    $security->respondAndCache($cacheKey, $response);
} catch (Throwable $error) {

    // Log the real error
    $security->logLine("CAREERS_API_ERROR | " . $error->getMessage());

    // Return a safe error response
    $security->respondError("Internal server error", 500);
} finally {

    // Close database connection
    if (isset($conn)) {
        $conn->close();
    }

    // Close log file
    if (isset($handle) && is_resource($handle)) {
        fclose($handle);
    }
}