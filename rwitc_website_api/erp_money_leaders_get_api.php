<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

require_once __DIR__ . "/config/config.php";
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
    "api_tag"        => "money_leaders_get"
]);

if (!$security->gate()) {
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    $security->respondError("Method not allowed", 405);
    exit;
}

// --------------------------------------------------
// VALIDATE + NORMALIZE INPUT
// --------------------------------------------------

$allowedTypes = [
    "horse"   => [
        "file"      => "horse.html",
        "db_type"   => "money_horse",
        "race_type" => "post_race"
    ],
    "trainer" => [
        "file"      => "trainer.html",
        "db_type"   => "money_trainer",
        "race_type" => "post_race"
    ],
    "jockey"  => [
        "file"      => "jockey.html",
        "db_type"   => "money_jockey",
        "race_type" => "post_race"
    ],
    "owner"   => [
        "file"      => "owner.html",
        "db_type"   => "money_owner",
        "race_type" => "post_race"
    ]
];

$rawType = isset($_GET["type"]) ? trim($_GET["type"]) : "";

if ($rawType === "" || !isset($allowedTypes[$rawType])) {
    $security->respondError("Invalid or missing parameter: type", 400);
    exit;
}

$fileName = $allowedTypes[$rawType]["file"];
$dbType = $allowedTypes[$rawType]["db_type"];
$raceType = $allowedTypes[$rawType]["race_type"];

// --------------------------------------------------
// LOCAL run_races HELPER FUNCTION
// --------------------------------------------------

/**
 * Look for the Money Leader file in the local run_races folder.
 * Returns ["html" => ..., "mtime" => ...] or null if not found / empty.
 *
 * Folder candidates (first existing file wins):
 *  1. RUN_RACES_PATH constant (from run_races_config.php), if defined
 *  2. <api folder>/run_races
 *  3. <parent of api folder>/run_races
 */
function moneyLeaderReadFromLocal($fileName)
{
    // $fileName comes only from the whitelist above, but stay safe anyway.
    $fileName = basename($fileName);

    $dirs = [];

    if (defined("RUN_RACES_PATH")) {
        $dirs[] = RUN_RACES_PATH;
    }

    $dirs[] = __DIR__ . "/run_races";
    $dirs[] = dirname(__DIR__) . "/run_races";

    foreach ($dirs as $dir) {

        $dir = rtrim(str_replace("\\", "/", (string) $dir), "/");

        if ($dir === "") {
            continue;
        }

        $path = $dir . "/" . $fileName;

        if (!is_file($path) || !is_readable($path)) {
            continue;
        }

        $content = @file_get_contents($path);

        if ($content === false || trim($content) === "") {
            continue;
        }

        return [
            "html"  => $content,
            "mtime" => (int) @filemtime($path)
        ];
    }

    return null;
}

// --------------------------------------------------
// S3 HELPER FUNCTIONS
// --------------------------------------------------

/**
 * Extract a safe run_races/ S3 key from the stored file_url.
 */
function moneyLeaderExtractS3Key($fileUrl)
{
    if (!is_string($fileUrl) || trim($fileUrl) === "") {
        throw new Exception("Empty S3 file URL");
    }

    $value = trim($fileUrl);

    // file_url may already contain the S3 key.
    if (strpos($value, "run_races/") === 0) {
        $key = $value;
    } else {
        $path = parse_url($value, PHP_URL_PATH);
        if ($path === false || $path === null || $path === "") {
            throw new Exception("Invalid S3 file URL");
        }

        $key = ltrim(rawurldecode($path), "/");
    }

    // Only allow files inside run_races/ and only .html files.
    if (
        strpos($key, "run_races/") !== 0 ||
        !preg_match("/\.html$/i", $key) ||
        strpos($key, "..") !== false ||
        strpos($key, "//") !== false
    ) {
        throw new Exception("Invalid Money Leader S3 key");
    }

    return $key;
}

/**
 * Read the private S3 object through the existing s3_set_url.php helper.
 * The helper itself performs authenticated AWS S3 getObject().
 */
function moneyLeaderReadFromS3Helper($s3Key)
{
    $scriptName = isset($_SERVER["SCRIPT_NAME"])
        ? $_SERVER["SCRIPT_NAME"]
        : "";

    $directory = str_replace(
        "\\",
        "/",
        dirname($scriptName)
    );

    if ($directory === "/" || $directory === ".") {
        $directory = "";
    }

    $scheme = "http";

    if (
        isset($_SERVER["HTTPS"]) &&
        strtolower((string) $_SERVER["HTTPS"]) !== "" &&
        strtolower((string) $_SERVER["HTTPS"]) !== "off"
    ) {
        $scheme = "https";
    } elseif (
        isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) &&
        strtolower((string) $_SERVER["HTTP_X_FORWARDED_PROTO"]) === "https"
    ) {
        $scheme = "https";
    }

    $host = isset($_SERVER["HTTP_HOST"])
        ? $_SERVER["HTTP_HOST"]
        : "";

    if ($host === "") {
        throw new Exception("Unable to determine API host for S3 helper");
    }

    $helperUrl =
        $scheme . "://" .
        $host .
        $directory .
        "/s3_set_url.php?key=" .
        rawurlencode($s3Key);

    $ch = curl_init($helperUrl);

    if ($ch === false) {
        throw new Exception("Unable to initialize S3 helper request");
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT      => "RWITC-Money-Leaders-API/1.0"
    ]);

    $htmlContent = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($htmlContent === false) {
        throw new Exception(
            "S3 helper request failed: " . $curlError
        );
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        throw new Exception(
            "S3 helper returned HTTP " . $httpCode
        );
    }

    if (trim((string) $htmlContent) === "") {
        throw new Exception("S3 helper returned empty HTML");
    }

    return (string) $htmlContent;
}

// --------------------------------------------------
// FETCH DATA
// --------------------------------------------------
// Order of lookup:
//   1. Local run_races folder  -> if file found, use it (S3 is NOT checked)
//   2. run_race_details (DB)   -> file_url -> S3 (only if local not found)
//
// No response cache is used here so a new Push_Website is visible
// immediately on the next API request.
// --------------------------------------------------

try {

    $html = false;
    $source = "";
    $updatedAt = null;
    $s3Key = null;
    $latestId = null;
    $latestDate = null;

    // ==========================================================
    // 1. LOCAL run_races FOLDER FIRST
    // ==========================================================

    $local = moneyLeaderReadFromLocal($fileName);

    if ($local !== null) {

        $html = $local["html"];
        $source = "LOCAL";

        if ($local["mtime"] > 0) {
            $tz = new DateTimeZone("Asia/Kolkata");
            $dt = new DateTime("@" . $local["mtime"]);
            $dt->setTimezone($tz);

            $latestDate = $dt->format("Y-m-d");
            $updatedAt = $dt->format("Y-m-d\TH:i:sP");
        }

    } else {

        // ======================================================
        // 2. NOT IN LOCAL -> DB LATEST RECORD -> S3
        // ======================================================

        $stmt = $conn->prepare("
            SELECT id, `date`, file_url
            FROM run_race_details
            WHERE `type` = ?
              AND `race_type` = ?
              AND file_url IS NOT NULL
              AND file_url <> ''
            ORDER BY id DESC
            LIMIT 1
        ");

        if ($stmt === false) {
            throw new Exception(
                "Unable to prepare run_race_details query: " . $conn->error
            );
        }

        $stmt->bind_param(
            "ss",
            $dbType,
            $raceType
        );

        if (!$stmt->execute()) {
            $error = $stmt->error ?: $conn->error;
            $stmt->close();
            throw new Exception(
                "Unable to query run_race_details: " . $error
            );
        }

        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {

            $row = $result->fetch_assoc();
            $stmt->close();

            $latestId = isset($row["id"]) ? (int) $row["id"] : null;
            $latestDate = isset($row["date"])
                ? trim((string) $row["date"])
                : null;

            // DB file_url -> S3 key
            $s3Key = moneyLeaderExtractS3Key(
                $row["file_url"]
            );

            // Read current content from S3
            $html = moneyLeaderReadFromS3Helper(
                $s3Key
            );

            $source = "DB_S3";

            if ($latestDate !== null && $latestDate !== "") {
                $updatedAt = $latestDate . "T00:00:00+05:30";
            }

        } else {

            $stmt->close();
        }
    }

    // ==========================================================
    // NO DATA
    // ==========================================================

    if ($html === false || trim((string) $html) === "") {

        echo json_encode([
            "success" => true,
            "data"    => [
                "type"       => $rawType,
                "html"       => "",
                "updated_at" => null,
                "available"  => false,
                "source"     => null,
                "date"       => null,
                "record_id"  => null
            ],
            "error"   => null
        ]);

        exit;
    }

    // ==========================================================
    // SUCCESS RESPONSE
    // ==========================================================

    echo json_encode([
        "success" => true,
        "data"    => [
            "type"       => $rawType,
            "html"       => (string) $html,
            "updated_at" => $updatedAt,
            "available"  => true,
            "source"     => $source,
            "date"       => $latestDate,
            "record_id"  => $latestId
        ],
        "error"   => null
    ]);

    exit;

} catch (Throwable $error) {

    $security->logLine(
        "MONEY_LEADERS_API_ERROR | " .
        $error->getMessage()
    );

    $security->respondError(
        "Internal server error",
        500
    );

} finally {

    if (isset($conn)) {
        $conn->close();
    }

    if (isset($handle) && is_resource($handle)) {
        fclose($handle);
    }
}