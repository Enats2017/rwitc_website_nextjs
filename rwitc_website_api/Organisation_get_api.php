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
    "api_tag"        => "organisation_get"
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

// Sirf ye text sections allowed (working_group baad me)
$allowedSections = [
    "structure",
    "managing_committee",
    "stewards_of_club",
    "board_of_appeal"
];

$section = isset($_GET["section"]) ? trim($_GET["section"]) : "";

if (!in_array($section, $allowedSections, true)) {
    $security->respondError("Invalid section", 400);
    if (isset($conn)) { $conn->close(); }
    if (is_resource($handle)) { fclose($handle); }
    exit;
}

$cacheKey = "organisation_" . $section;

if ($security->serveCache($cacheKey)) {
    if (isset($conn)) { $conn->close(); }
    if (is_resource($handle)) { fclose($handle); }
    exit;
}

try {

    $conn->set_charset("utf8mb4");

    $stmt = $conn->prepare(
        "SELECT id, report_date, title, content_text
         FROM organisation_management
         WHERE section = ?
           AND `type` = 'text'
           AND content_text IS NOT NULL
           AND content_text <> ''
         ORDER BY report_date DESC, id DESC
         LIMIT 1"
    );

    if ($stmt === false) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param("s", $section);

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        throw new Exception($err);
    }

    $result = $stmt->get_result();
    $row    = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    $response = [
        "section" => $section,
        "exists"  => (bool) $row,
        "date"    => $row ? $row["report_date"] : null,
        "title"   => ($row && trim((string) $row["title"]) !== "") ? $row["title"] : null,
        "text"    => $row ? $row["content_text"] : ""
    ];

    $security->respondAndCache($cacheKey, $response);
} catch (Throwable $error) {

    $security->logLine("ORGANISATION_API_ERROR | " . $error->getMessage());

    $security->respondError("Internal server error", 500);
} finally {

    if (isset($conn)) {
        $conn->close();
    }

    if (isset($handle) && is_resource($handle)) {
        fclose($handle);
    }
}