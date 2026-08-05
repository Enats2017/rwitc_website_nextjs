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
    "cache_ttl"      => 45,
    "cache_dir"      => __DIR__ . "/cache",
    "rate_limit_dir" => __DIR__ . "/rate_limits",
    "api_tag"        => "trainers_get"
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
// RETURN CACHE IF AVAILABLE
// --------------------------------------------------

if ($security->serveCache("trainers")) {
    exit;
}

// --------------------------------------------------
// FETCH DATA
// --------------------------------------------------

try {

    // Original page query:
    //   SELECT DISTINCT(t.TRAINERNM)
    //   FROM erp_trainers t
    //   INNER JOIN horse_erp h ON h.TRAINER = t.TRAINER
    //   WHERE ((t.LISCENCE='A' OR t.LISCENCE='B')
    //          AND (t.TRAINER != 'CLENT')
    //          AND HORSET > 0)
    //   ORDER BY t.TRAINERNM
    //
    // Note: HORSET belongs to erp_trainers (t.HORSET), not horse_erp -
    // the original query referenced it unqualified and MySQL resolved
    // it against erp_trainers. The horse_erp join was only there to
    // confirm the trainer has at least one row in horse_erp at all.
    //
    // Optimized here: EXISTS replaces the JOIN + DISTINCT so MySQL can
    // stop scanning horse_erp for a trainer as soon as one matching
    // row is found, avoiding the join-then-dedupe cost. Same rows,
    // same order.

    $trainersSql = "
        SELECT t.TRAINERNM
        FROM erp_trainers t
        WHERE (t.LISCENCE = 'A' OR t.LISCENCE = 'B')
          AND t.TRAINER != 'CLENT'
          AND t.HORSET > 0
          AND EXISTS (
                SELECT 1
                FROM horse_erp h
                WHERE h.TRAINER = t.TRAINER
          )
        ORDER BY t.TRAINERNM
    ";

    $trainersResult = $conn->query($trainersSql);

    if ($trainersResult === false) {
        throw new Exception($conn->error);
    }

    $trainers = [];

    while ($row = $trainersResult->fetch_assoc()) {
        $trainers[] = $row;
    }

    // --------------------------------------------------
    // FINAL RESPONSE
    // --------------------------------------------------

    $security->respondAndCache(
        "trainers",
        $trainers
    );

} catch (Throwable $error) {

    // Log actual database error
    $security->logLine(
        "TRAINERS_API_ERROR | "
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