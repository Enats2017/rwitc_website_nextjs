<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

// Load database and security
require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/ApiSecurity.php";

// --------------------------------------------------
// LOG SETUP
// --------------------------------------------------

$logDir = __DIR__ . "/logs";

if (!is_dir($logDir)) {
    @mkdir($logDir, 0750, true);
}

$handle = @fopen(
    $logDir . "/api_logs.txt",
    "a+"
);

// --------------------------------------------------
// INITIALIZE SECURITY
// --------------------------------------------------

$security = new ApiSecurity($handle, [
    "rate_limit"     => 60,
    "rate_window"    => 60,
    "cache_ttl"      => 45,
    "cache_dir"      => __DIR__ . "/cache",
    "rate_limit_dir" => __DIR__ . "/rate_limits",
    "api_tag"        => "news_get"
]);

// --------------------------------------------------
// RATE LIMIT
// --------------------------------------------------

if (!$security->gate()) {
    exit;
}

// --------------------------------------------------
// ONLY ALLOW GET
// --------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] !== "GET") {

    $security->respondError(
        "Method not allowed",
        405
    );

    exit;
}

// --------------------------------------------------
// RETURN CACHE IF AVAILABLE
// --------------------------------------------------

$articleId  = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$cacheScope = $articleId > 0 ? "id_" . $articleId : "list";

if ($security->serveCache("news", $cacheScope)) {
    exit;
}

// --------------------------------------------------
// FETCH DATA
// --------------------------------------------------

function cleanUtf8($data)
{
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = cleanUtf8($value);
        }
        return $data;
    }

    if (is_string($data)) {
        $data = str_replace("\0", '', $data);
        if (!mb_check_encoding($data, 'UTF-8')) {
            $data = mb_convert_encoding($data, 'UTF-8', 'Windows-1252, ISO-8859-1, UTF-8');
        }
        return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
    }

    return $data;
}

try {

    if ($articleId > 0) {
        $stmt = $conn->prepare("
            SELECT id, title, created, body, published, new
            FROM articles
            WHERE id = ? AND published = 'Y'
            LIMIT 1
        ");

        if (!$stmt) {
            throw new Exception($conn->error);
        }

        $stmt->bind_param("i", $articleId);
        $stmt->execute();
        $newsResult = $stmt->get_result();

        if ($row = $newsResult->fetch_assoc()) {
            $articleData = cleanUtf8([
                'id'        => $row['id'],
                'title'     => $row['title'],
                'created'   => $row['created'],
                'body'      => $row['body'],
                'published' => $row['published'],
                'new'       => $row['new']
            ]);

            $stmt->close();

            $security->respondAndCache(
                "news",
                $articleData,
                $cacheScope
            );
        } else {
            $stmt->close();
            $security->respondError(
                "Article not found",
                404
            );
        }

    } else {

        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 9;
        if ($limit <= 0 || $limit > 50) {
            $limit = 9;
        }

        $newsSql = "
            SELECT id, title, created, body, published, new
            FROM articles
            WHERE published = 'Y'
            ORDER BY created DESC
            LIMIT " . (int) $limit . "
        ";

        $newsResult = $conn->query($newsSql);

        if ($newsResult === false) {
            throw new Exception($conn->error);
        }

        $articles = [];

        while ($row = $newsResult->fetch_assoc()) {
            $articles[] = cleanUtf8([
                'id'        => $row['id'],
                'title'     => $row['title'],
                'created'   => $row['created'],
                'body'      => $row['body'],
                'published' => $row['published'],
                'new'       => $row['new']
            ]);
        }

        // --------------------------------------------------
        // FINAL RESPONSE
        // --------------------------------------------------

        $security->respondAndCache(
            "news",
            $articles,
            $cacheScope
        );
    }

} catch (Throwable $error) {

    // Log actual database error
    $security->logLine(
        "NEWS_API_ERROR | "
        . $error->getMessage()
    );

    // Do not expose database error publicly
    $security->respondError(
        "Internal server error",
        500
    );

} finally {

    // Close database connection
    if (isset($conn)) {
        $conn->close();
    }

    // Close log file
    if (
        isset($handle)
        && is_resource($handle)
    ) {
        fclose($handle);
    }
}