<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// Load database and security
require_once __DIR__ . "/config/config.php";
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
    "rate_limit"     => 120,
    "rate_window"    => 60,
    "cache_ttl"      => 30,
    "cache_dir"      => __DIR__ . "/cache",
    "rate_limit_dir" => __DIR__ . "/rate_limits",
    "api_tag"        => "horse_autocomplete_get"
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

// NOTE: this mirrors the legacy getdata.php?route=performanceProfile1
// endpoint that the original horsedata() AJAX call in performanceProfile.php
// hit on every keyup. race_date is accepted for parity with the old call
// signature but isn't currently used to filter results below - wire it up
// to a join against `decl` if you want date-scoped suggestions.

$letter = trim($_GET["letter"] ?? "");
$raceDate = $_GET["race_date"] ?? "";

if ($letter === "" || mb_strlen($letter) < 2) {
    // Match legacy behaviour of returning an empty list rather than erroring,
    // since the field fires on every keystroke including the first couple.
    $security->respondAndCache("horse_autocomplete_empty", [
        "results" => [],
    ]);
    exit;
}

// --------------------------------------------------
// CACHE KEY (per search term)
// --------------------------------------------------

$cacheKey = "horse_autocomplete_" . md5(strtolower($letter));

if ($security->serveCache($cacheKey)) {
    exit;
}

// --------------------------------------------------
// FETCH DATA
// --------------------------------------------------

try {

    $searchTerm = $conn->real_escape_string($letter);

    $stmt = $conn->prepare(
        "SELECT DISTINCT HORSENM AS hname
         FROM horse_erp
         WHERE HORSENM LIKE CONCAT(?, '%')
         ORDER BY HORSENM ASC
         LIMIT 20"
    );

    if ($stmt === false) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();

    $result = $stmt->get_result();

    $horses = [];
    while ($row = $result->fetch_assoc()) {
        $horses[] = $row["hname"];
    }

    $stmt->close();

    // --------------------------------------------------
    // FINAL RESPONSE
    // --------------------------------------------------

    $security->respondAndCache(
        $cacheKey,
        [
            "results" => $horses,
        ]
    );

} catch (Throwable $error) {

    // Log actual database error
    $security->logLine(
        "HORSE_AUTOCOMPLETE_API_ERROR | "
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