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
    "api_tag"        => "dividends_get"
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

    // NOTE: this endpoint is consumed directly by FullCalendar's
    // `events: url` option, which expects a raw JSON array - not the
    // {success, data, error} envelope used by the other RWITC APIs.
    // So on error we still return a plain array (empty on failure)
    // rather than $security->respondError(), to avoid breaking the
    // calendar widget's JSON parsing.
    http_response_code(405);
    echo json_encode([]);
    exit;
}

// --------------------------------------------------
// RETURN RAW CACHE IF AVAILABLE
// (manual cache handling here since respondAndCache() wraps output
//  in {success, data, error}, which FullCalendar can't consume)
// --------------------------------------------------

$cacheFile = __DIR__ . "/cache/dividends_get.json";
$cacheTtl  = 120;

if (
    is_file($cacheFile)
    && (time() - filemtime($cacheFile)) < $cacheTtl
) {
    readfile($cacheFile);
    exit;
}

// --------------------------------------------------
// FETCH DATA
// --------------------------------------------------

try {

    // Original page logic (fetchdividends.php):
    //   SELECT id, div_date, centreid, filename FROM dividends
    //   ORDER BY centreid ASC, div_date DESC
    //
    //   SELECT id, centre FROM centres ORDER BY id ASC
    //
    // Optimized here: single JOIN query replacing the two separate
    // queries + PHP-side lookup array build. Same rows, same order.

    $dividendsSql = "
        SELECT d.div_date, d.filename, c.centre
        FROM dividends d
        INNER JOIN centres c ON c.id = d.centreid
        ORDER BY d.centreid ASC, d.div_date DESC
    ";

    $dividendsResult = $conn->query($dividendsSql);

    if ($dividendsResult === false) {
        throw new Exception($conn->error);
    }

    // $baseUrl = "https://rwitc.com/staticpages/dividends/";
    $baseUrl = STATIC_DIVIDENDS_URL;
    $events = [];

    while ($row = $dividendsResult->fetch_assoc()) {
        $events[] = [
            "className" => $row["centre"],
            "title"     => $row["centre"],
            "start"     => $row["div_date"],
            "url"       => $baseUrl . $row["filename"]
        ];
    }

    $json = json_encode($events);

    // Write to cache directory (best-effort - a failed cache write
    // should not break the response)
    $cacheDir = __DIR__ . "/cache";

    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0750, true);
    }

    @file_put_contents($cacheFile, $json);

    // --------------------------------------------------
    // FINAL RESPONSE (raw array, no envelope)
    // --------------------------------------------------

    echo $json;
} catch (Throwable $error) {

    // Log actual database error
    $security->logLine(
        "DIVIDENDS_API_ERROR | "
            . $error->getMessage()
    );

    // Fail quiet with an empty array so FullCalendar doesn't choke
    // on an unexpected response shape
    http_response_code(500);
    echo json_encode([]);
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
