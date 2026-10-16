<?php

/**
 * Application Forms (Download > Forms) - public list API
 *
 * GET  application_forms_get_api.php
 *
 * Returns the ACTIVE forms uploaded from the admin panel
 * (turf-console/applicationFormsManager.php), ordered by the admin
 * "Display Order". The S3 key is never exposed: every row carries a
 * `download_url` that points to application_forms_download.php?id=<id>.
 *
 * Response: { success, data: [ {id,title,file_type,file_size,sort_order,download_url} ], error }
 */

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// Admin edits must show up immediately.
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
        "data"    => null,
        "error"   => "Method not allowed."
    ]);
    exit;
}

require_once __DIR__ . "/config/config.php";

try {

    // Same prefix the admin uploader writes to. Anything else is ignored.
    $prefix = "uploads/ApplicationForms/";

    $result = $conn->query(
        "SELECT id, title, source, file_type, file_size, sort_order
         FROM application_forms
         WHERE is_active = 1
           AND source <> ''
         ORDER BY sort_order ASC, id ASC"
    );

    if ($result === false) {
        throw new RuntimeException($conn->error);
    }

    $baseUrl = rtrim(WEBSITE_API_BASE_URL, "/");
    $forms   = [];

    while ($row = $result->fetch_assoc()) {

        if (strpos((string)$row["source"], $prefix) !== 0) {
            continue;
        }

        $forms[] = [
            "id"           => (int)$row["id"],
            "title"        => (string)$row["title"],
            "file_type"    => strtolower((string)$row["file_type"]),
            "file_size"    => (int)$row["file_size"],
            "sort_order"   => (int)$row["sort_order"],
            "download_url" => $baseUrl . "/application_forms_download.php?id=" . (int)$row["id"]
        ];
    }

    echo json_encode([
        "success" => true,
        "data"    => $forms,
        "error"   => null
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {

    error_log("APPLICATION_FORMS_API_ERROR | " . $error->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "data"    => null,
        "error"   => "Unable to load application forms."
    ]);
} finally {

    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}