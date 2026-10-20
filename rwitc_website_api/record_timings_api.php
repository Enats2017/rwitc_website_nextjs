<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

require_once __DIR__ . "/config/config.php";
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

$logDir = __DIR__ . "/logs";
if (!is_dir($logDir)) {
    @mkdir($logDir, 0750, true);
}
$handle = @fopen($logDir . "/api_logs.txt", "a+");

$security = new ApiSecurity($handle, [
    "rate_limit"     => 60,
    "rate_window"    => 60,
    "cache_ttl"      => 20,
    "cache_dir"      => __DIR__ . "/cache",
    "rate_limit_dir" => __DIR__ . "/rate_limits",
    "api_tag"        => "record_timings"
]);

if (!$security->gate()) {
    if (isset($conn)) { $conn->close(); }
    if (is_resource($handle)) { fclose($handle); }
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    $security->respondError("Method not allowed", 405);
    if (isset($conn)) { $conn->close(); }
    if (is_resource($handle)) { fclose($handle); }
    exit;
}

if ($security->serveCache("record_timings_all")) {
    if (isset($conn)) { $conn->close(); }
    if (is_resource($handle)) { fclose($handle); }
    exit;
}

try {

    $data = [
        "mumbai" => ["main" => []],
        "pune"   => ["main" => [], "monsoon" => []]
    ];

    // Sirf wahi records jo admin me "visible" (eye icon on) hain
    $result = $conn->query(
        "SELECT id, track, surface, distance, horse_name, weight, race_date, time_min, time_sec, notes
         FROM record_timings
         WHERE is_visible = 1
         ORDER BY distance ASC, id ASC"
    );

    if ($result === false) {
        throw new Exception($conn->error);
    }

    while ($row = $result->fetch_assoc()) {

        $track   = $row['track'];
        $surface = $row['surface'];

        if (!isset($data[$track][$surface])) {
            continue;
        }

        $data[$track][$surface][] = [
            "id"         => (int)$row['id'],
            "distance"   => (int)$row['distance'],
            "horse_name" => $row['horse_name'],
            "weight"     => $row['weight'],
            "race_date"  => $row['race_date'],
            "time_min"   => (int)$row['time_min'],
            "time_sec"   => $row['time_sec'],
            "notes"      => (string)$row['notes']
        ];
    }

    $security->respondAndCache("record_timings_all", $data);

} catch (Throwable $error) {

    $security->logLine("RECORD_TIMINGS_API_ERROR | " . $error->getMessage());
    $security->respondError("Internal server error", 500);

} finally {
    if (isset($conn)) { $conn->close(); }
    if (isset($handle) && is_resource($handle)) { fclose($handle); }
}