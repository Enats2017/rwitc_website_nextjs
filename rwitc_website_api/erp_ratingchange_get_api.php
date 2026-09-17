<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// --------------------------------------------------
// LOAD CONFIG / SECURITY
// --------------------------------------------------

require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/config/run_races_config.php";
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
    "cache_ttl"      => 45,
    "cache_dir"      => __DIR__ . "/cache",
    "rate_limit_dir" => __DIR__ . "/rate_limits",
    "api_tag"        => "ratings_change_get"
]);

// --------------------------------------------------
// RATE LIMIT
// --------------------------------------------------

if (!$security->gate()) {

    if (isset($conn)) {
        $conn->close();
    }

    if (is_resource($handle)) {
        fclose($handle);
    }

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

    if (isset($conn)) {
        $conn->close();
    }

    if (is_resource($handle)) {
        fclose($handle);
    }

    exit;
}

// --------------------------------------------------
// VALIDATE DATE
// --------------------------------------------------

$date = isset($_GET["date"])
    ? trim($_GET["date"])
    : "";

if ($date === "") {

    $security->respondError(
        "date is required (format YYYY-MM-DD)",
        400
    );

    if (isset($conn)) {
        $conn->close();
    }

    if (is_resource($handle)) {
        fclose($handle);
    }

    exit;
}

$d = DateTime::createFromFormat("Y-m-d", $date);

if (!$d || $d->format("Y-m-d") !== $date) {

    $security->respondError(
        "Invalid date format, expected YYYY-MM-DD",
        400
    );

    if (isset($conn)) {
        $conn->close();
    }

    if (is_resource($handle)) {
        fclose($handle);
    }

    exit;
}

// --------------------------------------------------
// CACHE KEY
// --------------------------------------------------

$cacheKey = "ratings_change_html_" . $date;

// Return cached response if available
if ($security->serveCache($cacheKey)) {

    if (isset($conn)) {
        $conn->close();
    }

    if (is_resource($handle)) {
        fclose($handle);
    }

    exit;
}

// --------------------------------------------------
// FETCH DATA
// --------------------------------------------------

try {

    /*
     * ======================================================
     * RATING CHANGE FILE FLOW
     * ======================================================
     *
     * FIRST:
     *     Existing local run_races folder
     *
     * SECOND:
     *     run_race_details DB record
     *     -> file_url
     *     -> S3 HTML
     *
     * This keeps the old local behaviour and adds
     * DB/S3 fallback for migrated files.
     */

    $htmlFile = RUN_RACES_LOCAL_PATH
        . "/Rating_change_" . $date . ".html";

    $htmlContent = false;
    $source = "";

    // ======================================================
    // 1. FIRST: LOCAL RUN_RACES
    // ======================================================

    if (is_file($htmlFile)) {

        $source = "LOCAL_RUN_RACES";

        $htmlContent = file_get_contents($htmlFile);

        if ($htmlContent === false) {
            throw new Exception(
                "Unable to read local rating change file: "
                . $htmlFile
            );
        }
    }

    // ======================================================
    // 2. SECOND: DB -> S3
    // ======================================================

    if ($htmlContent === false) {

        $stmt = $conn->prepare("
            SELECT file_url
            FROM run_race_details
            WHERE `date` = ?
              AND `type` = 'rating_change'
              AND `race_type` = 'post_race'
              AND file_url IS NOT NULL
              AND file_url != ''
            ORDER BY id DESC
            LIMIT 1
        ");

        if ($stmt === false) {
            throw new Exception($conn->error);
        }

        $stmt->bind_param("s", $date);

        if (!$stmt->execute()) {
            throw new Exception($conn->error);
        }

        $result = $stmt->get_result();

        if (!$result || $result->num_rows === 0) {

            $stmt->close();

            $security->respondError(
                "No rating Change Found",
                404
            );

            exit;
        }

        $row = $result->fetch_assoc();

        $s3Url = trim($row["file_url"]);

        $stmt->close();

        if ($s3Url === "") {
            throw new Exception(
                "Rating change S3 file URL is empty"
            );
        }

        /*
         * Read the actual HTML from S3.
         *
         * S3 URL is used internally only.
         * Frontend receives HTML, not the S3 URL.
         */

        $htmlContent = @file_get_contents($s3Url);

        if ($htmlContent === false) {
            throw new Exception(
                "Unable to read rating change file from S3"
            );
        }

        $source = "DB_S3";
    }

    // ======================================================
    // 3. RESPONSE
    // ======================================================

    $response = [
        "found"  => true,
        "date"   => $date,
        "source" => $source,
        "html"   => $htmlContent
    ];

    $security->respondAndCache(
        $cacheKey,
        $response
    );

} catch (Throwable $error) {

    // Log actual server-side error
    $security->logLine(
        "RATINGS_CHANGE_API_ERROR | "
        . $error->getMessage()
    );

    // Do not expose internal error details publicly
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
