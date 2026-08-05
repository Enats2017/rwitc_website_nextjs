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
    "api_tag"        => "trainer_horses_get"
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

if (
    !isset($_GET["trainer"])
    || trim($_GET["trainer"]) === ""
) {

    $security->respondError(
        "Missing required parameter: trainer",
        400
    );

    exit;
}

$trainerName = urldecode($_GET["trainer"]);

if ($trainerName === "") {

    $security->respondError(
        "Trainer name cannot be empty",
        400
    );

    exit;
}

// Cache is keyed per-trainer so different trainers don't collide
$cacheKey = "trainer_horses_" . md5($trainerName);

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

    // Original page logic (two sequential queries):
    //   1. SELECT TRAINER FROM erp_trainers WHERE TRAINERNM = '$trainerName'
    //   2. SELECT HORSESEQ, HORSENM, AGE, SEX, COLOR, SIRE, DAM, OWNERSHIP,
    //             OWNERSHIP1, OWNERSHIP2, OWNERSHIP3, DAMNAT, RATING
    //      FROM horse_erp WHERE TRAINER = '$trainerCode'
    //      ORDER BY HORSENM
    //
    // Optimized here: combined into a single INNER JOIN keyed on
    // TRAINERNM, using a bound parameter instead of string
    // interpolation. Same rows, same order.

    $horsesSql = "
        SELECT h.HORSESEQ, h.HORSENM, h.AGE, h.SEX, h.COLOR, h.SIRE, h.DAM,
               h.OWNERSHIP, h.OWNERSHIP1, h.OWNERSHIP2, h.OWNERSHIP3,
               h.DAMNAT, h.RATING
        FROM horse_erp h
        INNER JOIN erp_trainers t ON t.TRAINER = h.TRAINER
        WHERE t.TRAINERNM = ?
        ORDER BY h.HORSENM
    ";

    $stmt = $conn->prepare($horsesSql);

    if ($stmt === false) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param("s", $trainerName);
    $stmt->execute();

    $horsesResult = $stmt->get_result();

    if ($horsesResult === false) {
        throw new Exception($stmt->error);
    }

    $horses = [];

    while ($row = $horsesResult->fetch_assoc()) {

        // Same "Not Rated" business rule as the original page
        if ($row["RATING"] == 0 || $row["RATING"] == "") {
            $row["RATING"] = "NR";
        }

        $horses[] = $row;
    }

    $stmt->close();

    // --------------------------------------------------
    // FINAL RESPONSE
    // --------------------------------------------------

    $security->respondAndCache(
        $cacheKey,
        [
            "trainer" => $trainerName,
            "horses"  => $horses
        ]
    );

} catch (Throwable $error) {

    // Log actual database error
    $security->logLine(
        "TRAINER_HORSES_API_ERROR | "
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