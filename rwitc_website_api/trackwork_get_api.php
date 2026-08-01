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
    "api_tag"        => "trackwork_get"
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
// ?id=123                          -> single record by id
// ?date=YYYY-MM-DD                 -> single record by date
// ?date=YYYY-MM-DD&horsename=xxx   -> single record by date + horse filter
// ?q=byhorse&horsename=xxx         -> list of dates matching a horse name
// -------------------------------------------------------------

$trackworkID = isset($_GET["id"]) ? $_GET["id"] : "";
$rawDate     = isset($_GET["date"]) ? trim($_GET["date"]) : "";
$horseName   = isset($_GET["horsename"]) ? trim($_GET["horsename"]) : "";
$q           = isset($_GET["q"]) ? trim($_GET["q"]) : "";

// Validate id (must be a positive integer if supplied)
if ($trackworkID !== "" && !ctype_digit((string) $trackworkID)) {
    $security->respondError("Invalid id, expected a positive integer", 400);

    if (isset($conn)) {
        $conn->close();
    }

    if (is_resource($handle)) {
        fclose($handle);
    }

    exit;
}

$trackworkID = $trackworkID !== "" ? (int) $trackworkID : 0;

// Validate date format if one was supplied (expects YYYY-MM-DD)
$dateParam = "";

if ($rawDate !== "") {
    $d = DateTime::createFromFormat("Y-m-d", $rawDate);

    if ($d && $d->format("Y-m-d") === $rawDate) {
        $dateParam = $rawDate;
    } else {
        $security->respondError("Invalid date format, expected YYYY-MM-DD", 400);

        if (isset($conn)) {
            $conn->close();
        }

        if (is_resource($handle)) {
            fclose($handle);
        }

        exit;
    }
}

// Cache key must vary per param combination
$cacheKey = "trackwork_"
    . ($q === "byhorse" ? "byhorse_" . strtolower($horseName)
        : ($trackworkID ? "id_" . $trackworkID
            : "date_" . ($dateParam !== "" ? $dateParam : "none")
              . "_horse_" . strtolower($horseName)));

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

    // ---------------------------------------------------------
    // Mode 1: list of trackwork dates matching a horse name
    // ---------------------------------------------------------
    if ($q === "byhorse") {

        if ($horseName === "") {
            throw new InvalidArgumentException("horsename is required when q=byhorse");
        }

        $stmt = $conn->prepare("
            SELECT id, trackwork_date
            FROM trackwork
            WHERE LOWER(trackwork) LIKE ?
            ORDER BY trackwork_date DESC
        ");

        if ($stmt === false) {
            throw new Exception($conn->error);
        }

        $likeTerm = "%" . strtolower($horseName) . "%";
        $stmt->bind_param("s", $likeTerm);
        $stmt->execute();

        $result = $stmt->get_result();
        $matches = [];

        while ($row = $result->fetch_assoc()) {
            $matches[] = [
                "id"             => (int) $row["id"],
                "trackwork_date" => $row["trackwork_date"]
            ];
        }

        $stmt->close();

        $response = [
            "mode"      => "list",
            "horsename" => $horseName,
            "count"     => count($matches),
            "results"   => $matches
        ];

        $security->respondAndCache($cacheKey, $response);
        exit;
    }

    // ---------------------------------------------------------
    // Mode 2: single trackwork record (by id, or by date [+ horsename])
    // ---------------------------------------------------------
    if (!$trackworkID && $dateParam === "" && $horseName === "") {
        throw new InvalidArgumentException(
            "Provide id, date, or q=byhorse with horsename"
        );
    }

    $record = null;

    if ($trackworkID) {

        $stmt = $conn->prepare("
            SELECT trackwork_date, trackwork, published
            FROM trackwork
            WHERE id = ?
        ");

        if ($stmt === false) {
            throw new Exception($conn->error);
        }

        $stmt->bind_param("i", $trackworkID);
        $stmt->execute();

        $result = $stmt->get_result();
        $record = $result->fetch_assoc();

        $stmt->close();

    } else {

        if ($horseName === "") {

            $stmt = $conn->prepare("
                SELECT trackwork_date, trackwork, published
                FROM trackwork
                WHERE trackwork_date = ?
            ");

            if ($stmt === false) {
                throw new Exception($conn->error);
            }

            $stmt->bind_param("s", $dateParam);

        } else {

            $stmt = $conn->prepare("
                SELECT trackwork_date, trackwork, published
                FROM trackwork
                WHERE LOWER(trackwork) LIKE ? AND trackwork_date = ?
            ");

            if ($stmt === false) {
                throw new Exception($conn->error);
            }

            $likeTerm = "%" . strtolower($horseName) . "%";
            $stmt->bind_param("ss", $likeTerm, $dateParam);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $record = $result->fetch_assoc();

        $stmt->close();
    }

    if (!$record) {
        $response = [
            "mode"  => "single",
            "found" => false,
            "data"  => null
        ];

        $security->respondAndCache($cacheKey, $response);
        exit;
    }

    $response = [
        "mode"  => "single",
        "found" => true,
        "data"  => [
            "trackwork_date" => $record["trackwork_date"],
            "trackwork"      => $record["trackwork"],
            "published"      => $record["published"]
        ]
    ];

    // Return successful response and save it in cache
    $security->respondAndCache($cacheKey, $response);

} catch (InvalidArgumentException $error) {

    $security->respondError($error->getMessage(), 400);

} catch (Throwable $error) {

    // Save actual error in log file
    $security->logLine(
        "TRACKWORK_API_ERROR | " .
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