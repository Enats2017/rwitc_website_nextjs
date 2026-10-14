<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/ApiSecurity.php";
require_once __DIR__ . "/pdf_helpers.php";

if (!class_exists("ApiSecurity")) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "data"    => null,
        "error"   => "ApiSecurity class could not be loaded"
    ]);
    exit;
}

$registry = pdfRegistry();

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
    "api_tag"        => "pdf_links"
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

if ($security->serveCache("pdf_links_latest")) {
    if (isset($conn)) { $conn->close(); }
    if (is_resource($handle)) { fclose($handle); }
    exit;
}

try {

    $links = [];

    foreach ($registry as $type => $config) {

        $typeSql = $conn->real_escape_string($type);

        // ---------- many entries: every entry becomes its own menu item ----------
        if (!empty($config['multiple'])) {

            // oldest first (same order as the old hard coded menu). Use DESC for newest first.
            $result = $conn->query("SELECT id, label, `date`, file_url FROM club_menu_pdf WHERE `type` = '" . $typeSql . "' ORDER BY `date` ASC, id ASC");

            if ($result === false) {
                throw new Exception($conn->error);
            }

            $menuTitle = isset($config['menu_title']) ? $config['menu_title'] : '{label}';
            $items     = [];

            while ($row = $result->fetch_assoc()) {
                $label = trim((string)$row['label']);

                // IMPORTANT: file_url (S3 url) is NOT sent to the frontend
                $items[] = [
                    "id"        => (int)$row['id'],
                    "date"      => $row['date'],
                    "label"     => $label,
                    "title"     => str_replace('{label}', $label, $menuTitle),
                    "file_name" => pdfPublicFileName($config, $row['file_url'])
                ];
            }

            if (count($items) > 0) {
                $links[$type] = $items;
            }

            continue;
        }

        // ---------- single entry: only the latest one ----------
        $result = $conn->query("SELECT id, `date`, file_url FROM club_menu_pdf WHERE `type` = '" . $typeSql . "' ORDER BY `date` DESC, id DESC LIMIT 1");

        if ($result === false) {
            throw new Exception($conn->error);
        }

        $row = $result->fetch_assoc();
        if (!$row) {
            continue;
        }

        // IMPORTANT: file_url (S3 url) is NOT sent to the frontend
        $links[$type] = [
            "id"        => (int)$row['id'],
            "date"      => $row['date'],
            "file_name" => pdfPublicFileName($config, $row['file_url'])
        ];
    }

    $security->respondAndCache("pdf_links_latest", $links);

} catch (Throwable $error) {

    $security->logLine("PDF_LINKS_API_ERROR | " . $error->getMessage());
    $security->respondError("Internal server error", 500);

} finally {
    if (isset($conn)) { $conn->close(); }
    if (isset($handle) && is_resource($handle)) { fclose($handle); }
}