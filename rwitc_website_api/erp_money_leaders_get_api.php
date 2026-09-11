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
    "api_tag"        => "money_leaders_get"
]);

if (!$security->gate()) {
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    $security->respondError("Method not allowed", 405);
    exit;
}

// --------------------------------------------------
// VALIDATE + NORMALIZE INPUT
// --------------------------------------------------

$allowedTypes = [
    "horse"   => "horse.html",
    "trainer" => "trainer.html",
    "jockey"  => "jockey.html",
    "owner"   => "owner.html",
];

$rawType = isset($_GET["type"]) ? trim($_GET["type"]) : "";

if ($rawType === "" || !isset($allowedTypes[$rawType])) {
    $security->respondError("Invalid or missing parameter: type", 400);
    exit;
}

$fileName = $allowedTypes[$rawType];
$filePath = __DIR__ . "/../run_races/" . $fileName;

$cacheKey = "money_leaders_" . $rawType;

if ($security->serveCache($cacheKey)) {
    exit;
}

// --------------------------------------------------
// FETCH DATA (file-based, same as old PHP tabs)
// --------------------------------------------------

try {

    if (!file_exists($filePath)) {
        $security->respondAndCache(
            $cacheKey,
            [
                "type"       => $rawType,
                "html"       => "",
                "updated_at" => null,
                "available"  => false
            ]
        );
        exit;
    }

    $html = file_get_contents($filePath);

    if ($html === false) {
        throw new Exception("Unable to read file: " . $fileName);
    }

    $mtime = filemtime($filePath);

    $security->respondAndCache(
        $cacheKey,
        [
            "type"       => $rawType,
            "html"       => $html,
            "updated_at" => $mtime ? date("c", $mtime) : null,
            "available"  => true
        ]
    );

} catch (Throwable $error) {

    $security->logLine("MONEY_LEADERS_API_ERROR | " . $error->getMessage());

    $security->respondError("Internal server error", 500);

} finally {

    if (isset($handle) && is_resource($handle)) {
        fclose($handle);
    }
}