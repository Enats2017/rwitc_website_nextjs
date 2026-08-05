<?php


header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// Load database and security
require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/ApiSecurity.php";
require_once __DIR__ . "/config/run_races_config.php";


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
    "api_tag"        => "raceday_report_get"
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
// CONFIG: static report file location
// --------------------------------------------------

// NOTE: mirrors the original page's hardcoded path -
// centralized here as a constant instead of inlined below.
// define("RACEDAY_REPORT_DIR", "/var/www/html/rwitc_website/staticpages/racedayreports/");
// define("RACEDAY_REPORT_PUBLIC_BASE", "https://rwitc.com/staticpages/racedayreports/");

// --------------------------------------------------
// VALIDATE INPUT
// --------------------------------------------------

$date = isset($_GET["date"]) ? trim($_GET["date"]) : "";

if ($date === "" || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || strtotime($date) === false) {
    $security->respondError(
        "A valid date parameter (YYYY-MM-DD) is required",
        400
    );
    exit;
}

// --------------------------------------------------
// CACHE KEY
// --------------------------------------------------

$cacheKey = "raceday_report_" . md5($date);

if ($security->serveCache($cacheKey)) {
    exit;
}


// --------------------------------------------------
// FETCH DATA
// --------------------------------------------------

try {

    // ---- Report record lookup ----
    // NOTE: original page built this query via raw string concatenation
    // of $_GET['date'] straight into SQL (SQL injection). Replaced with
    // a prepared statement here; behavior (which row is returned) is
    // unchanged for valid date input.

    $stmt = $conn->prepare(
        "SELECT id, racedate, filename FROM raceday_report WHERE racedate = ? LIMIT 1"
    );
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $reportDetails = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (empty($reportDetails) || empty($reportDetails["filename"])) {
        $security->respondAndCache($cacheKey, [
            "found"   => false,
            "message" => "No race day report found for {$date}",
            "date"    => $date,
        ]);
        exit;
    }

    $filename   = $reportDetails["filename"];
    $filePath   = RACEDAY_REPORT_DIR . $filename;
    $publicUrl  = RACEDAY_REPORT_PUBLIC_BASE . $filename;

    // ---- Report content ----
    // The original page did include_once($file) directly into the HTML
    // response, so the fragment may contain PHP. Preserve that behavior
    // by executing it via include and capturing the output, rather than
    // just reading raw bytes with file_get_contents.

    $reportHtml = null;
    $fileExists = is_file($filePath);

    if ($fileExists) {
    ob_start();
    include $filePath;
    $reportHtml = ob_get_clean();

    // Fix: convert legacy Windows-1252/ISO-8859-1 encoded HTML to valid UTF-8
    // so json_encode() doesn't silently fail on invalid byte sequences.
    if ($reportHtml !== null && !mb_check_encoding($reportHtml, 'UTF-8')) {
        $reportHtml = mb_convert_encoding($reportHtml, 'UTF-8', 'Windows-1252');
    }
} else {
        $security->logLine(
            "RACEDAY_REPORT_API_WARNING | Missing file on disk: " . $filePath
        );
    }

    // ---- Day label ----

    $dateTimestamp = strtotime($date);

    $response = [
        "found"        => true,
        "date"         => $date,
        "day_label"    => date("l jS F Y", $dateTimestamp),
        "report_id"    => $reportDetails["id"],
        "filename"     => $filename,
        "download_url" => $publicUrl,
        "file_exists"  => $fileExists,
        "report_html"  => $reportHtml,
    ];

    // --------------------------------------------------
    // FINAL RESPONSE
    // --------------------------------------------------

    $security->respondAndCache(
        $cacheKey,
        $response
    );

} catch (Throwable $error) {

    // Log actual database error
    $security->logLine(
        "RACEDAY_REPORT_API_ERROR | "
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