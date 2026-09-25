<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/config/run_races_config.php";
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
    "api_tag"        => "saddle_cloth_get"
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

$cacheKey = "saddle_cloth";

if ($security->serveCache($cacheKey)) {
    if (isset($conn)) { $conn->close(); }
    if (is_resource($handle)) { fclose($handle); }
    exit;
}

try {

    // -------------------------------------------------------
    // run_races/saddleCloth_green.html, _red.html, _blue.html
    // (old page jaisa) — jo bhi file exist karti hai usko
    // "sections" array me daal dete hain, koi DB fallback nahi
    // hai kyunki original page me bhi sirf static HTML include
    // ho raha tha.
    // -------------------------------------------------------
    $basePath = rtrim((string) RUN_RACES_LOCAL_PATH, "/\\");

    $files = [
        "green" => $basePath . "/saddleCloth_green.html",
        "red"   => $basePath . "/saddleCloth_red.html",
        "blue"  => $basePath . "/saddleCloth_blue.html",
    ];

    $sections = [];

    foreach ($files as $color => $path) {
        if (is_file($path)) {
            $html = file_get_contents($path);
            if ($html !== false && trim($html) !== "") {
                $sections[] = [
                    "color" => $color,
                    "html"  => $html
                ];
            }
        }
    }

    if (empty($sections)) {
        throw new Exception("No saddle cloth files found in " . $basePath);
    }

    $security->respondAndCache($cacheKey, [
        "mode"     => "html",
        "sections" => $sections
    ]);

} catch (Throwable $error) {

    $security->logLine("SADDLE_CLOTH_API_ERROR | " . $error->getMessage());
    $security->respondError("Internal server error", 500);

} finally {

    if (isset($conn)) { $conn->close(); }
    if (isset($handle) && is_resource($handle)) { fclose($handle); }
}