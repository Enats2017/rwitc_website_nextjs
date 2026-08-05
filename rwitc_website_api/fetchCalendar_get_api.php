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
    "rate_limit"     => 60,
    "rate_window"    => 60,
    "cache_ttl"      => 300,
    "cache_dir"      => __DIR__ . "/cache",
    "rate_limit_dir" => __DIR__ . "/rate_limits",
    "api_tag"        => "calendar_get"
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
// start/end are both optional. Each is accepted only if it is a
// well-formed YYYY-MM-DD date; anything else (missing, malformed,
// garbage input) is treated as "not provided" rather than coerced
// into a fake date like 1970-01-01. This fixes the original
// fetchCalendar.php behavior per requirement #5:
//   - both given  -> filter by range
//   - only start  -> filter from that date onward
//   - only end    -> filter up to that date
//   - neither     -> return all records

function isValidDate(string $value): bool
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return false;
    }

    [$y, $m, $d] = explode("-", $value);

    return checkdate((int) $m, (int) $d, (int) $y);
}

$rawStart = isset($_GET["start"]) ? trim($_GET["start"]) : "";
$rawEnd   = isset($_GET["end"])   ? trim($_GET["end"])   : "";

$start = isValidDate($rawStart) ? $rawStart : null;
$end   = isValidDate($rawEnd)   ? $rawEnd   : null;

// --------------------------------------------------
// CACHE KEY (reflects the actual filters applied)
// --------------------------------------------------

$cacheKey = "calendar_" . md5(($start ?? "any") . "_" . ($end ?? "any"));

// --------------------------------------------------
// RETURN CACHE IF AVAILABLE
// --------------------------------------------------

if ($security->serveCache($cacheKey)) {
    exit;
}

// --------------------------------------------------
// FETCH DATA
// --------------------------------------------------

try {

    // --------------------------------------------------
    // Single JOIN query replaces the original's two separate
    // lookups (racing_calendar + centres). The WHERE clause is
    // built dynamically so 0, 1, or 2 date filters can be bound
    // safely -- every value still goes through a prepared statement,
    // so no user input is ever concatenated into the SQL itself.
    // --------------------------------------------------

    $conditions = [];
    $params     = [];
    $types      = "";

    if ($start !== null) {
        $conditions[] = "rc.racedate >= ?";
        $types       .= "s";
        $params[]     = $start;
    }

    if ($end !== null) {
        $conditions[] = "rc.racedate <= ?";
        $types       .= "s";
        $params[]     = $end;
    }

    $whereClause = $conditions
        ? "WHERE " . implode(" AND ", $conditions)
        : "";

    $sql = "
        SELECT rc.racedate, ce.centre
        FROM racing_calendar rc
        INNER JOIN centres ce ON ce.id = rc.centreid
        {$whereClause}
        ORDER BY rc.racedate ASC
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        throw new Exception($conn->error);
    }

    if ($types !== "") {
        // bind_param needs its arguments by reference, so build the
        // reference list dynamically to support a variable number
        // of bound parameters (0, 1, or 2).
        $boundParams = [$types];

        foreach ($params as $key => $value) {
            $boundParams[] = &$params[$key];
        }

        call_user_func_array([$stmt, "bind_param"], $boundParams);
    }

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result === false) {
        throw new Exception($stmt->error);
    }

    // --------------------------------------------------
    // BUILD FULLCALENDAR-COMPATIBLE EVENT LIST
    // --------------------------------------------------
    // Response stays a plain JSON array (not wrapped in an object),
    // since this is consumed directly as a FullCalendar `events`
    // source -- identical shape to the original fetchCalendar.php:
    // [{ "className": ..., "title": ..., "start": ... }, ...]

    $jsonArray = [];

    while ($row = $result->fetch_assoc()) {
        $jsonArray[] = [
            "className" => $row["centre"],
            "title"     => $row["centre"],
            "start"     => $row["racedate"],
        ];
    }

    $result->free();
    $stmt->close();

    // --------------------------------------------------
    // FINAL RESPONSE
    // --------------------------------------------------

    $security->respondAndCache(
        $cacheKey,
        $jsonArray
    );

} catch (Throwable $error) {

    // Log actual database error
    $security->logLine(
        "CALENDAR_API_ERROR | "
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