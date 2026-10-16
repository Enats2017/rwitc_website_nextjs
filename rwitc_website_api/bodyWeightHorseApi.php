
<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/ApiSecurity.php";

// ------------------------------------------------------------
// 1. Validate API security class
// ------------------------------------------------------------
if (!class_exists("ApiSecurity")) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "data"    => null,
        "error"   => "API security configuration error"
    ]);

    exit;
}

// ------------------------------------------------------------
// 2. Initialize logging
// ------------------------------------------------------------
$logDir = __DIR__ . "/logs";

if (!is_dir($logDir)) {
    @mkdir($logDir, 0750, true);
}

$handle = @fopen($logDir . "/api_logs.txt", "a+");

$security = new ApiSecurity($handle, [
    "rate_limit"     => 60,
    "rate_window"    => 60,
    "cache_ttl"      => 45,
    "cache_dir"      => __DIR__ . "/cache",
    "rate_limit_dir" => __DIR__ . "/rate_limits",
    "api_tag"        => "body_weight_horse_get"
]);

if (!$security->gate()) {
    if (isset($conn)) {
        $conn->close();
    }

    if (is_resource($handle)) {
        fclose($handle);
    }

    exit;
}

// ------------------------------------------------------------
// 3. Allow GET requests only
// ------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    $security->respondError("Method not allowed", 405);

    if (isset($conn)) {
        $conn->close();
    }

    if (is_resource($handle)) {
        fclose($handle);
    }

    exit;
}

// ------------------------------------------------------------
// 4. Clean horse weight
//    Empty weight / NR / zero weight => NR
//    450.0 => 450
// ------------------------------------------------------------
function cleanHorseWeight($weight)
{
    $weight = trim((string) $weight);

    if ($weight === "" || strtoupper($weight) === "NR") {
        return "NR";
    }

    if (is_numeric($weight) && (float) $weight == 0) {
        return "NR";
    }

    $parts = explode(".", $weight);

    if (
        count($parts) === 2 &&
        $parts[1] === "0"
    ) {
        $weight = $parts[0];
    }

    return $weight;
}

// ------------------------------------------------------------
// 5. Cache
//    Versioned key avoids reusing the old season-based response.
// ------------------------------------------------------------
$cacheKey = "body_weight_horses_weights_only_v1";

if ($security->serveCache($cacheKey)) {
    if (isset($conn)) {
        $conn->close();
    }

    if (is_resource($handle)) {
        fclose($handle);
    }

    exit;
}

try {

    // --------------------------------------------------------
    // 6. Fetch data ONLY from horse_weight_data
    //
    // Database: rwitc_website
    // Table:    horse_weight_data
    //
    // No dependency on season_close_erp.
    // Fetch all historical records up to today's date.
    // --------------------------------------------------------
    $sql = "
        SELECT horsenm, raceno, HORSEWT, cracedate
        FROM horse_weight_data
        WHERE cracedate < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
        ORDER BY cracedate ASC
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        throw new Exception($conn->error);
    }

    if (!$stmt->execute()) {
        $errorMessage = $stmt->error;
        $stmt->close();

        throw new Exception($errorMessage);
    }

    $result = $stmt->get_result();

    $horseMap = [];

    while ($row = $result->fetch_assoc()) {

        $name = trim((string) $row["horsenm"]);

        if ($name === "") {
            continue;
        }

        if (!isset($horseMap[$name])) {
            $horseMap[$name] = [];
        }

        $horseMap[$name][] = [
            "raceno" => $row["raceno"],
            "weight" => cleanHorseWeight($row["HORSEWT"])
        ];
    }

    $stmt->close();

    // --------------------------------------------------------
    // 7. Sort horse names alphabetically
    // --------------------------------------------------------
    uksort($horseMap, "strcasecmp");

    $horses = [];

    foreach ($horseMap as $name => $runs) {
        $horses[] = [
            "horse" => $name,
            "runs"  => $runs
        ];
    }

    // --------------------------------------------------------
    // 8. Return the same response structure used by the service
    // --------------------------------------------------------
    $security->respondAndCache($cacheKey, [
        "season"       => null,
        "season_start" => null,
        "season_end"   => date("Y-m-d"),
        "horse_count"  => count($horses),
        "horses"       => $horses
    ]);

} catch (Throwable $error) {

    // Log the actual error on the server; do not expose it publicly.
    $security->logLine(
        "BODY_WEIGHT_API_ERROR | " . $error->getMessage()
    );

    $security->respondError("Internal server error", 500);

} finally {

    if (isset($conn)) {
        $conn->close();
    }

    if (isset($handle) && is_resource($handle)) {
        fclose($handle);
    }
}
