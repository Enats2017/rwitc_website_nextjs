<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// Load database connection
require_once __DIR__ . "/config/config.php";

// Load ApiSecurity class
require_once __DIR__ . "/ApiSecurity.php";

// Make sure the class was loaded correctly
if (!class_exists("ApiSecurity")) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "data"    => null,
        "error"   => "ApiSecurity class could not be loaded"
    ]);

    exit;
}

// Create logs directory
$logDir = __DIR__ . "/logs";

if (!is_dir($logDir)) {
    @mkdir($logDir, 0750, true);
}

// Open log file
$handle = @fopen(
    $logDir . "/api_logs.txt",
    "a+"
);

// Initialize API Security
$security = new ApiSecurity($handle, [
    "rate_limit"     => 60,
    "rate_window"    => 60,
    "cache_ttl"      => 45,
    "cache_dir"      => __DIR__ . "/cache",
    "rate_limit_dir" => __DIR__ . "/rate_limits",
    "api_tag"        => "photo_gallery_get"
]);

// Apply rate limiting
if (!$security->gate()) {

    if (isset($conn)) {
        $conn->close();
    }

    if (is_resource($handle)) {
        fclose($handle);
    }

    exit;
}

// Only allow GET requests
if ($_SERVER["REQUEST_METHOD"] !== "GET") {

    $security->respondError(
        "Method not allowed",
        405
    );

    if (isset($conn)) {
        $conn->close();
    }

    if (is_resource($handle)) {
        fclose($handle);
    }

    exit;
}

// -------------------------------------------------------------
// Read + sanitize query params
// -------------------------------------------------------------
// ?date=YYYY-MM-DD  (optional - falls back to latest race date)
// ?sponsor_id=1      (optional - defaults to 1, matches original page)
// -------------------------------------------------------------

$rawDate      = isset($_GET["date"]) ? trim($_GET["date"]) : "";
$sponsorParam = isset($_GET["sponsor_id"]) ? (int) $_GET["sponsor_id"] : 1;

// Validate date format if one was supplied (expects YYYY-MM-DD)
$dateParam = "";

if ($rawDate !== "") {
    $d = DateTime::createFromFormat("Y-m-d", $rawDate);

    if ($d && $d->format("Y-m-d") === $rawDate) {
        $dateParam = $rawDate;
    } else {
        $security->respondError(
            "Invalid date format, expected YYYY-MM-DD",
            400
        );

        if (isset($conn)) {
            $conn->close();
        }

        if (is_resource($handle)) {
            fclose($handle);
        }

        exit;
    }
}

// Cache key must vary per date/sponsor so responses don't collide
$cacheKey = "photo_gallery_"
    . ($dateParam !== "" ? $dateParam : "latest")
    . "_" . $sponsorParam;

// Return cached response if available
if ($security->serveCache($cacheKey)) {

    if (isset($conn)) {
        $conn->close();
    }

    if (is_resource($handle)) {
        fclose($handle);
    }

    exit;
}

try {

    $raceDate = $dateParam;

    // If no date supplied, look up the most recent race date
    if ($raceDate === "") {

        $maxSql = "SELECT MAX(racedate) AS race_date FROM gallery";
        $maxResult = $conn->query($maxSql);

        if ($maxResult === false) {
            throw new Exception($conn->error);
        }

        $maxRow   = $maxResult->fetch_assoc();
        $raceDate = $maxRow["race_date"] ?? "";
    }

    $images = [];

    if ($raceDate !== "") {

        $stmt = $conn->prepare("
            SELECT id, racedate, caption, filename, sponsor_id
            FROM gallery
            WHERE racedate = ? AND sponsor_id = ?
            ORDER BY racedate DESC
        ");

        if ($stmt === false) {
            throw new Exception($conn->error);
        }

        $stmt->bind_param("si", $raceDate, $sponsorParam);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {

            // Build the public image URL the same way photoGallery.php does
            $row["image_url"] = "../rwitc_upload/gallery/"
                . date("d-M-Y", strtotime($row["racedate"]))
                . "/" . $row["filename"];

            $images[] = $row;
        }

        $stmt->close();
    }

    $response = [
        "race_date" => $raceDate,
        "count"     => count($images),
        "images"    => $images
    ];

    // Return successful response and save it in cache
    $security->respondAndCache(
        $cacheKey,
        $response
    );

} catch (Throwable $error) {

    // Save actual error in log file
    $security->logLine(
        "PHOTO_GALLERY_API_ERROR | " .
        $error->getMessage()
    );

    // Return safe error response
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
    if (isset($handle) && is_resource($handle)) {
        fclose($handle);
    }
}