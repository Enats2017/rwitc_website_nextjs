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
    "api_tag"        => "partners_contact_get"
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

$cacheKey = "partners_contact_all";

if ($security->serveCache($cacheKey)) {
    if (isset($conn)) { $conn->close(); }
    if (is_resource($handle)) { fclose($handle); }
    exit;
}

try {

    $conn->set_charset("utf8mb4");

    // All contacts, in the order they were added
    $result = $conn->query(
        "SELECT id, `name`, `position`, `phone`, `email`
         FROM partners_contact
         ORDER BY id ASC"
    );

    if ($result === false) {
        throw new Exception($conn->error);
    }

    $contacts = [];

    while ($row = $result->fetch_assoc()) {
        $contacts[] = [
            "name"     => (string) $row["name"],
            "position" => trim((string) $row["position"]) !== "" ? $row["position"] : null,
            "phone"    => trim((string) $row["phone"]) !== "" ? $row["phone"] : null,
            "email"    => trim((string) $row["email"]) !== "" ? $row["email"] : null
        ];
    }

    $response = [
        "exists"   => count($contacts) > 0,
        "contacts" => $contacts
    ];

    $security->respondAndCache($cacheKey, $response);
} catch (Throwable $error) {

    $security->logLine("PARTNERS_CONTACT_API_ERROR | " . $error->getMessage());

    $security->respondError("Internal server error", 500);
} finally {

    if (isset($conn)) {
        $conn->close();
    }

    if (isset($handle) && is_resource($handle)) {
        fclose($handle);
    }
}