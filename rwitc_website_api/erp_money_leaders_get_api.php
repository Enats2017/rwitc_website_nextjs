<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/ApiSecurity.php";

use Aws\S3\S3Client;

if (!class_exists('Aws\S3\S3Client')) {
    require_once __DIR__ . "/../vendor/autoload.php";
}

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
// VALIDATE INPUT
// --------------------------------------------------

$allowedTypes = [
    "horse"   => ["file" => "horse.html",   "db_type" => "money_horse",   "race_type" => "post_race"],
    "trainer" => ["file" => "trainer.html", "db_type" => "money_trainer", "race_type" => "post_race"],
    "jockey"  => ["file" => "jockey.html",  "db_type" => "money_jockey",  "race_type" => "post_race"],
    "owner"   => ["file" => "owner.html",   "db_type" => "money_owner",   "race_type" => "post_race"]
];

$rawType = isset($_GET["type"]) ? trim($_GET["type"]) : "";

if ($rawType === "" || !isset($allowedTypes[$rawType])) {
    $security->respondError("Invalid or missing parameter: type", 400);
    exit;
}

$fileName  = $allowedTypes[$rawType]["file"];
$dbType    = $allowedTypes[$rawType]["db_type"];
$raceType  = $allowedTypes[$rawType]["race_type"];
$debugMode = isset($_GET["debug"]) && $_GET["debug"] === "1";

// --------------------------------------------------
// HELPERS
// --------------------------------------------------

function moneyLeaderIst($timestamp)
{
    $dt = new DateTime("@" . (int) $timestamp);
    $dt->setTimezone(new DateTimeZone("Asia/Kolkata"));
    return $dt;
}

function moneyLeaderS3Client()
{
    return new S3Client([
        "version"     => "latest",
        "region"      => AWS_REGION,
        "credentials" => [
            "key"    => AWS_ACCESS_KEY_ID,
            "secret" => AWS_SECRET_ACCESS_KEY
        ]
    ]);
}

/**
 * Read an S3 object. Returns ["html" => ..., "mtime" => ...].
 * Throws on failure / empty content.
 */
function moneyLeaderReadFromS3($s3Key)
{
    $s3  = moneyLeaderS3Client();
    $obj = $s3->getObject(["Bucket" => AWS_BUCKET, "Key" => $s3Key]);

    $html = (string) $obj["Body"];

    if (trim($html) === "") {
        throw new Exception("S3 object is empty: " . $s3Key);
    }

    $mtime = 0;
    if (isset($obj["LastModified"])) {
        $lm = $obj["LastModified"];
        if (is_object($lm) && method_exists($lm, "getTimestamp")) {
            $mtime = $lm->getTimestamp();
        } else {
            $mtime = (int) strtotime((string) $lm);
        }
    }

    return ["html" => $html, "mtime" => (int) $mtime];
}

/**
 * Safe run_races/ key from DB file_url.
 */
function moneyLeaderExtractS3Key($fileUrl)
{
    if (!is_string($fileUrl) || trim($fileUrl) === "") {
        throw new Exception("Empty S3 file URL");
    }

    $value = trim($fileUrl);

    if (strpos($value, "run_races/") === 0) {
        $key = $value;
    } else {
        $path = parse_url($value, PHP_URL_PATH);
        if ($path === false || $path === null || $path === "") {
            throw new Exception("Invalid S3 file URL");
        }
        $key = ltrim(rawurldecode($path), "/");
    }

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
 * Local run_races folder (fallback only).
 */
function moneyLeaderReadFromLocal($fileName)
{
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

        return ["html" => $content, "mtime" => (int) @filemtime($path)];
    }

    return null;
}

// --------------------------------------------------
// FETCH
// Priority:
//   1. S3 fixed key run_races/<file>   (always the latest push)
//   2. S3 key from latest DB row       (backup)
//   3. Local run_races folder          (last fallback)
// --------------------------------------------------

try {

    $html       = false;
    $source     = "";
    $mtime      = 0;
    $latestId   = null;
    $dbDate     = null;
    $errors     = [];
    $fixedKey   = "run_races/" . $fileName;

    // DB row (only for record_id / db_date info + backup key)
    $row = null;
    try {
        $stmt = $conn->prepare("
            SELECT id, `date`, file_url
            FROM run_race_details
            WHERE `type` = ?
              AND `race_type` = ?
              AND file_url IS NOT NULL
              AND file_url <> ''
            ORDER BY `date` DESC, id DESC
            LIMIT 1
        ");

        if ($stmt !== false) {
            $stmt->bind_param("ss", $dbType, $raceType);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                if ($res && $res->num_rows > 0) {
                    $row = $res->fetch_assoc();
                }
            }
            $stmt->close();
        }
    } catch (Throwable $e) {
        $errors[] = "db: " . $e->getMessage();
    }

    if ($row !== null) {
        $latestId = (int) $row["id"];
        $dbDate   = trim((string) $row["date"]);
    }

    // 1. S3 fixed key
    try {
        $r      = moneyLeaderReadFromS3($fixedKey);
        $html   = $r["html"];
        $mtime  = $r["mtime"];
        $source = "S3";
    } catch (Throwable $e) {
        $errors[] = "s3_fixed: " . $e->getMessage();
    }

    // 2. S3 key from DB
    if ($html === false && $row !== null) {
        try {
            $dbKey = moneyLeaderExtractS3Key($row["file_url"]);
            $r      = moneyLeaderReadFromS3($dbKey);
            $html   = $r["html"];
            $mtime  = $r["mtime"];
            $source = "DB_S3";
        } catch (Throwable $e) {
            $errors[] = "s3_db: " . $e->getMessage();
        }
    }

    // 3. Local fallback
    if ($html === false) {
        $local = moneyLeaderReadFromLocal($fileName);
        if ($local !== null) {
            $html   = $local["html"];
            $mtime  = $local["mtime"];
            $source = "LOCAL";
        }
    }

    $updatedAt  = null;
    $latestDate = null;

    if ($html !== false && $mtime > 0) {
        $dt         = moneyLeaderIst($mtime);
        $latestDate = $dt->format("Y-m-d");
        $updatedAt  = $dt->format("Y-m-d\TH:i:sP");
    }

    $debug = $debugMode ? [
        "chosen"      => $source,
        "s3_key"      => $fixedKey,
        "s3_modified" => ($source === "S3" || $source === "DB_S3") ? $updatedAt : null,
        "db_date"     => $dbDate,
        "db_record"   => $latestId,
        "errors"      => $errors
    ] : null;

    if ($html === false || trim((string) $html) === "") {

        $out = [
            "success" => true,
            "data"    => [
                "type"          => $rawType,
                "html"          => "",
                "updated_at"    => null,
                "last_modified" => null,
                "available"     => false,
                "source"        => null,
                "date"          => null,
                "record_id"     => null
            ],
            "error"   => null
        ];

        if ($debug !== null) {
            $out["debug"] = $debug;
        }

        echo json_encode($out);
        exit;
    }

    $out = [
        "success" => true,
        "data"    => [
            "type"          => $rawType,
            "html"          => (string) $html,
            "updated_at"    => $updatedAt,
            "last_modified" => $updatedAt,
            "available"     => true,
            "source"        => $source,
            "date"          => $latestDate,
            "record_id"     => $latestId
        ],
        "error"   => null
    ];

    if ($debug !== null) {
        $out["debug"] = $debug;
    }

    echo json_encode($out);
    exit;

} catch (Throwable $error) {

    $security->logLine("MONEY_LEADERS_API_ERROR | " . $error->getMessage());
    $security->respondError("Internal server error", 500);

} finally {

    if (isset($conn)) {
        $conn->close();
    }

    if (isset($handle) && is_resource($handle)) {
        fclose($handle);
    }
}