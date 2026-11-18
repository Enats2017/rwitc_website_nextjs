<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// Load database and security
require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/ApiSecurity.php";

// LOG SETUP
$logDir = __DIR__ . "/logs";

if (!is_dir($logDir)) {
    @mkdir($logDir, 0750, true);
}

$handle = @fopen(
    $logDir . "/api_logs.txt",
    "a+"
);

// INITIALIZE SECURITY
$security = new ApiSecurity($handle, [
    "rate_limit"     => 60,
    "rate_window"    => 60,
    "cache_ttl"      => 45,
    "cache_dir"      => __DIR__ . "/cache",
    "rate_limit_dir" => __DIR__ . "/rate_limits",
    "api_tag"        => "website_logo_get"
]);

// RATE LIMIT
if (!$security->gate()) {
    exit;
}

// ONLY ALLOW GET
if ($_SERVER["REQUEST_METHOD"] !== "GET") {

    $security->respondError(
        "Method not allowed",
        405
    );

    exit;
}

// RETURN CACHE IF AVAILABLE
if ($security->serveCache("website_logo")) {
    exit;
}

// FETCH DATA
try {

    $logoSql = "
        SELECT type, url
        FROM website_logo
    ";

    $logoResult = $conn->query($logoSql);

    if ($logoResult === false) {
        throw new Exception($conn->error);
    }

    $logos = [];
    while ($row = $logoResult->fetch_assoc()) {
        $logos[$row["type"]] = $row["url"];
    }

    $security->respondAndCache(
        "website_logo",
        $logos
    );

} catch (Throwable $error) {

    $security->logLine(
        "WEBSITE_LOGO_API_ERROR | "
        . $error->getMessage()
    );

    $security->respondError(
        "Internal server error",
        500
    );

} finally {

    // Close database connection
    if (isset($conn)) {
        $conn->close();
    }

    // Close log file
    if (
        isset($handle)
        && is_resource($handle)
    ) {
        fclose($handle);
    }
}