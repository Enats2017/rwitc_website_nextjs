<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/ApiSecurity.php";

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
    "api_tag"        => "foal_records_get"
]);

if (!$security->gate()) {
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    $security->respondError("Method not allowed", 405);
    exit;
}

// --------------------------------------------------
// VALIDATE + NORMALIZE INPUT (same logic as old foalRecords.php)
// --------------------------------------------------

$rawMareName = isset($_GET["mareName"]) ? $_GET["mareName"] : "";

if (trim($rawMareName) === "") {
    $security->respondError("Missing required parameter: mareName", 400);
    exit;
}

$mareName = urldecode($rawMareName);
$damNatFromMare = "";

// Handle "MareName[NAT]" style values the same way the old page did
$mareNameParts = explode("[", $mareName);
if (isset($mareNameParts[1])) {
    $mareName = trim($mareNameParts[0]);
    $damNatFromMare = rtrim($mareNameParts[1], "]");
}

$rawDamNat = isset($_GET["damnat"]) ? trim($_GET["damnat"]) : "";
$damNat = ($rawDamNat !== "") ? $rawDamNat : $damNatFromMare;

$cacheKey = "foal_records_" . md5($mareName . "|" . $damNat);

if ($security->serveCache($cacheKey)) {
    exit;
}

// --------------------------------------------------
// FETCH DATA
// --------------------------------------------------

try {

    $sql = "SELECT * FROM niranjan WHERE MARENAME = ? AND MARENAT = ? ORDER BY YROFFLNG ASC";

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param("ss", $mareName, $damNat);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result === false) {
        throw new Exception($stmt->error);
    }

    $foalDetails = [];

    while ($row = $result->fetch_assoc()) {
        $foalDetails[] = $row;
    }

    $stmt->close();

    $security->respondAndCache(
        $cacheKey,
        [
            "mareName" => $mareName,
            "damNat"   => $damNat,
            "foals"    => $foalDetails
        ]
    );

} catch (Throwable $error) {

    $security->logLine("FOAL_RECORDS_API_ERROR | " . $error->getMessage());

    $security->respondError("Internal server error", 500);

} finally {

    if (isset($conn)) {
        $conn->close();
    }

    if (isset($handle) && is_resource($handle)) {
        fclose($handle);
    }
}