<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// Load database and security
require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/config/run_races_config.php";  
require_once __DIR__ . "/ApiSecurity.php";

// --------------------------------------------------
// LOG SETUP
// --------------------------------------------------

$logDir = __DIR__ . "/logs";

if (!is_dir($logDir)) {
    @mkdir($logDir, 0750, true);
}

$handle = @fopen(
    $logDir . "/api_logs.txt",
    "a+"
);

// --------------------------------------------------
// INITIALIZE SECURITY
// --------------------------------------------------

$security = new ApiSecurity($handle, [
    "rate_limit"     => 60,
    "rate_window"    => 60,
    "cache_ttl"      => 120,
    "cache_dir"      => __DIR__ . "/cache",
    "rate_limit_dir" => __DIR__ . "/rate_limits",
    "api_tag"        => "ratings_change_get"
]);

// --------------------------------------------------
// RATE LIMIT
// --------------------------------------------------

if (!$security->gate()) {
    exit;
}

// --------------------------------------------------
// ONLY ALLOW GET
// --------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] !== "GET") {

    $security->respondError(
        "Method not allowed",
        405
    );

    exit;
}

// --------------------------------------------------
// VALIDATE INPUT
// --------------------------------------------------

// NOTE: the original page did not validate/require the date param at
// all - an empty or malformed date would just fail to match any row
// and fall through to the "No rating Change Found" case. We preserve
// that permissiveness (no 400 on missing/blank date) but still guard
// against a garbage format from wrecking the query bind.

$date = isset($_GET["date"]) ? trim($_GET["date"]) : "";

if ($date === "" || strtotime($date) === false) {
    $security->respondError(
        "A valid date parameter (YYYY-MM-DD) is required",
        400
    );
    exit;
}

// --------------------------------------------------
// CACHE KEY
// --------------------------------------------------

$cacheKey = "ratings_change_html_" . md5($date);

if ($security->serveCache($cacheKey)) {
    exit;
}

// --------------------------------------------------
// FETCH DATA
// --------------------------------------------------

try {

    $htmlFile = RUN_RACES_LOCAL_PATH . "/Rating_change_" . $date . ".html";

    if (!file_exists($htmlFile)) {
        $security->respondAndCache($cacheKey, [
            "found"   => false,
            "message" => "No rating Change Found",
            "date"    => $date,
        ]);
        exit;
    }

    $htmlContent = file_get_contents($htmlFile);

    if ($htmlContent === false) {
        throw new Exception("Unable to read archive file: " . $htmlFile);
    }

    $response = [
        "found" => true,
        "date"  => $date,
        "html"  => $htmlContent,
    ];

    $security->respondAndCache(
        $cacheKey,
        $response
    );

} catch (Throwable $error) {

    // Log actual database error
    $security->logLine(
        "RATINGS_CHANGE_API_ERROR | "
        . $error->getMessage()
    );

    // Do not expose database error publicly
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