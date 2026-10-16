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
    "cache_ttl"      => 30,
    "cache_dir"      => __DIR__ . "/cache",
    "rate_limit_dir" => __DIR__ . "/rate_limits",
    "api_tag"        => "wagering_terms_get"
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

$cacheKey = "wagering_terms_latest";

if ($security->serveCache($cacheKey)) {
    if (isset($conn)) { $conn->close(); }
    if (is_resource($handle)) { fclose($handle); }
    exit;
}

try {

    $conn->set_charset("utf8mb4");

    // Latest text entry by date is the one shown on the website
    $result = $conn->query(
        "SELECT id, report_date, title, content_text
         FROM wagering_terms_docs
         WHERE `type` = 'text'
           AND content_text IS NOT NULL
           AND content_text <> ''
         ORDER BY report_date DESC, id DESC
         LIMIT 1"
    );

    if ($result === false) {
        throw new Exception($conn->error);
    }

    $row = $result->fetch_assoc();

    $response = [
        "exists" => (bool) $row,
        "date"   => $row ? $row["report_date"] : null,
        "title"  => ($row && trim((string) $row["title"]) !== "") ? $row["title"] : null,
        "text"   => $row ? $row["content_text"] : ""
    ];

    $security->respondAndCache($cacheKey, $response);
} catch (Throwable $error) {

    $security->logLine("WAGERING_TERMS_API_ERROR | " . $error->getMessage());

    $security->respondError("Internal server error", 500);
} finally {

    if (isset($conn)) {
        $conn->close();
    }

    if (isset($handle) && is_resource($handle)) {
        fclose($handle);
    }
}