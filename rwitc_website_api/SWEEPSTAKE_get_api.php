<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// Load database and security
require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/ApiSecurity.php";

// --------------------------------------------------
// CONSTANTS
// --------------------------------------------------

const SWEEPSTAKE_COLUMNS = "id, sweepstake_date, title, comments, filename";
const SWEEPSTAKE_BASE_URL = "https://rwitc.com/staticpages/sweepstakes/";

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
    "api_tag"        => "sweepstakes_get"
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
// Same rule as the original page: id missing, empty, non-numeric,
// or <= 0 all fall back to "list mode" (id = 0).

$sweepstakeID = 0;

if (isset($_GET["id"]) && is_numeric($_GET["id"]) && (int) $_GET["id"] > 0) {
    $sweepstakeID = (int) $_GET["id"];
}

// Cache is keyed per id so list mode and each detail view don't collide
$cacheKey = "sweepstakes_" . $sweepstakeID;

// --------------------------------------------------
// RETURN CACHE IF AVAILABLE
// --------------------------------------------------

if ($security->serveCache($cacheKey)) {
    exit;
}

// --------------------------------------------------
// HELPERS
// --------------------------------------------------

/**
 * Maps one raw sweepstakes row into the API's response shape.
 * Shared by both list mode and detail mode so the field set can
 * only ever drift in one place.
 */
function formatSweepstakeRow(array $row): array
{
    return [
        "id"             => $row["id"],
        "date"           => $row["sweepstake_date"],
        "formatted_date" => date("d M Y", strtotime($row["sweepstake_date"])),
        "title"          => $row["title"],
        "comments"       => $row["comments"],
        "filename"       => $row["filename"],
        "file_url"       => SWEEPSTAKE_BASE_URL . $row["filename"],
    ];
}

// --------------------------------------------------
// FETCH DATA
// --------------------------------------------------

try {

    if ($sweepstakeID === 0) {

        // --------------------------------------------------
        // LIST MODE -- same query/order as the original page
        // --------------------------------------------------

        $listResult = $conn->query("
            SELECT " . SWEEPSTAKE_COLUMNS . "
            FROM sweepstakes
            ORDER BY sweepstake_date ASC
        ");

        if ($listResult === false) {
            throw new Exception($conn->error);
        }

        $sweepstakes = [];

        while ($row = $listResult->fetch_assoc()) {
            $sweepstakes[] = formatSweepstakeRow($row);
        }

        $listResult->free();

        $security->respondAndCache(
            $cacheKey,
            [
                "mode"        => "list",
                "sweepstakes" => $sweepstakes
            ]
        );

    } else {

        // --------------------------------------------------
        // DETAIL MODE -- same lookup as the original page's
        // WHERE id='$sweeptstakeID' branch, parameterized
        // --------------------------------------------------

        $stmt = $conn->prepare("
            SELECT " . SWEEPSTAKE_COLUMNS . "
            FROM sweepstakes
            WHERE id = ?
        ");

        if ($stmt === false) {
            throw new Exception($conn->error);
        }

        $stmt->bind_param("i", $sweepstakeID);
        $stmt->execute();

        $detailResult = $stmt->get_result();

        if ($detailResult === false) {
            throw new Exception($stmt->error);
        }

        $value = $detailResult->fetch_assoc();

        $detailResult->free();
        $stmt->close();

        if (!$value) {

            $security->respondError(
                "Sweepstake not found",
                404
            );

            exit;
        }

        $security->respondAndCache(
            $cacheKey,
            [
                "mode"       => "detail",
                "sweepstake" => formatSweepstakeRow($value)
            ]
        );
    }

} catch (Throwable $error) {

    // Log actual database error
    $security->logLine(
        "SWEEPSTAKES_API_ERROR | "
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