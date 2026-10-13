<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    header("Allow: GET, OPTIONS");
    echo json_encode([
        "success" => false,
        "data" => null,
        "error" => "Method not allowed."
    ]);
    exit;
}

require_once __DIR__ . "/config/config.php";

try {
    $result = $conn->query(
        "SELECT file_url, file_name, updated_at
         FROM affiliated_clubs
         WHERE id = 1
         LIMIT 1"
    );

    $row = $result ? $result->fetch_assoc() : null;
    if (!$row || trim((string)$row["file_url"]) === "") {
        throw new RuntimeException("Affiliated clubs file is not uploaded yet.");
    }

    echo json_encode([
        "success" => true,
        "data" => [
            "file_url" => (string)$row["file_url"],
            "file_name" => (string)$row["file_name"],
            "updated_at" => $row["updated_at"]
        ],
        "error" => null
    ]);
} catch (Throwable $error) {
    error_log("AFFILIATED_CLUBS_API_ERROR | " . $error->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "data" => null,
        "error" => "Unable to load affiliated clubs."
    ]);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
