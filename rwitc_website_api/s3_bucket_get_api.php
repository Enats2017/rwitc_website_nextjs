<?php

/**
 * RWITC S3 <-> run_race_details API
 *
 * SYNC  : POST action=sync   date + html_file [+ htm_file]
 * BATCH : POST action=sync_website_data   date + files[name]=run_races/xxx.html ...
 *         (ERP "Push/Remove Website" button yahi bhejta hai)
 * DELETE: POST action=delete
 * READ  : GET ?action=check&date=YYYY-MM-DD&type=...&race_type=...
 *         GET ?action=list
 *
 * REQUIRES:
 *   ALTER TABLE run_race_details
 *     ADD COLUMN htm_file_url VARCHAR(500) NULL DEFAULT NULL AFTER file_url;
 *   (agar `type` column ENUM hai to usme money_owner, money_jockey, money_horse,
 *    money_trainer, jockey_statistics, trainer_statistics add karna)
 */

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/../vendor/autoload.php";

use Aws\S3\S3Client;
use Aws\Exception\AwsException;


// ============================================================
// RESPONSE HELPER
// ============================================================

function sendResponse($success, $data = null, $error = null, $statusCode = 200)
{
    http_response_code($statusCode);

    echo json_encode(
        array(
            "success" => $success,
            "data"    => $data,
            "error"   => $error
        ),
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


// ============================================================
// OPTIONS
// ============================================================

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}


// ============================================================
// CREATE S3 CLIENT
// ============================================================

try {

    $s3 = new S3Client(
        array(
            "version" => "latest",
            "region"  => AWS_REGION,
            "credentials" => array(
                "key"    => AWS_ACCESS_KEY_ID,
                "secret" => AWS_SECRET_ACCESS_KEY
            )
        )
    );
} catch (Throwable $e) {

    error_log("S3 CLIENT ERROR: " . $e->getMessage());

    sendResponse(false, null, "Unable to initialize S3.", 500);
}


// ============================================================
// DATABASE CHECK
// ============================================================

if (!isset($conn) || !($conn instanceof mysqli)) {

    sendResponse(false, null, "Database connection is not available.", 500);
}

$conn->set_charset("utf8mb4");


// ============================================================
// CONSTANTS
// ============================================================

$s3Prefix = "run_races/";

// Fixed filenames (no date inside name). Date comes from POST["date"].
$fixedFiles = array(
    "owner.html"              => array("type" => "money_owner",        "race_type" => "post_race"),
    "jockey.html"             => array("type" => "money_jockey",       "race_type" => "post_race"),
    "horse.html"              => array("type" => "money_horse",        "race_type" => "post_race"),
    "trainer.html"            => array("type" => "money_trainer",      "race_type" => "post_race"),
    "jockey_statistics.html"  => array("type" => "jockey_statistics",  "race_type" => "post_race"),
    "trainer_statistics.html" => array("type" => "trainer_statistics", "race_type" => "post_race")
);


// ============================================================
// VALIDATE DATE
// ============================================================

function validateDateValue($date)
{
    if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }

    $d = DateTime::createFromFormat("Y-m-d", $date);

    return ($d !== false && $d->format("Y-m-d") === $date);
}


// ============================================================
// GET TYPE + RACE TYPE FROM FILE NAME
// ============================================================

function getFileInformation($filename)
{
    global $fixedFiles;

    $name = strtolower(basename($filename));

    if (strpos($name, "handicaps_") === 0) {
        return array("type" => "handicaps", "race_type" => "pre_race");
    }

    if (strpos($name, "acceptance_") === 0 || strpos($name, "acceptances_") === 0) {
        return array("type" => "acceptances", "race_type" => "pre_race");
    }

    if (strpos($name, "declarations_") === 0) {
        return array("type" => "declarations", "race_type" => "pre_race");
    }

    if (strpos($name, "race_card_") === 0 || strpos($name, "race_card_report_") === 0) {
        return array("type" => "racecard", "race_type" => "pre_race");
    }

    if (strpos($name, "race_results_") === 0 || strpos($name, "race_result_") === 0) {
        return array("type" => "race_result", "race_type" => "post_race");
    }

    if (strpos($name, "rating_change_") === 0) {
        return array("type" => "rating_change", "race_type" => "post_race");
    }

    if (strpos($name, "mock_race_result_") === 0) {
        return array("type" => "mock_race_result", "race_type" => "post_race");
    }

    // MONEY LEADER + STATISTICS fixed filenames
    if (isset($fixedFiles[$name])) {
        return $fixedFiles[$name];
    }

    return array("type" => "other", "race_type" => "other");
}


// ============================================================
// GET DATE FROM FILE NAME
// ============================================================

function getFileDate($filename)
{
    global $fixedFiles;

    if (preg_match('/_(\d{4}-\d{2}-\d{2})\.(html|htm)$/i', basename($filename), $matches)) {
        return $matches[1];
    }

    // Fixed filenames have no date; date is supplied in POST["date"].
    if (isset($fixedFiles[strtolower(basename($filename))])) {
        return isset($_POST["date"]) ? trim($_POST["date"]) : null;
    }

    return null;
}


// ============================================================
// GET S3 URL
// ============================================================

function getS3FileUrl($bucket, $region, $key)
{
    return "https://" .
        $bucket .
        ".s3." .
        $region .
        ".amazonaws.com/" .
        str_replace("%2F", "/", rawurlencode($key));
}


// ============================================================
// NORMALIZE / VALIDATE S3 KEY
// ============================================================

function normalizeS3Key($key, $expectedPrefix)
{
    if (!is_string($key) || trim($key) === "") {
        return null;
    }

    $key = trim($key);

    if (strpos($key, $expectedPrefix) !== 0) {
        return null;
    }

    $extension = strtolower(pathinfo($key, PATHINFO_EXTENSION));

    if ($extension !== "html" && $extension !== "htm") {
        return null;
    }

    $relative = substr($key, strlen($expectedPrefix));

    if (
        $relative === "" ||
        strpos($relative, "/") !== false ||
        strpos($relative, "\\") !== false
    ) {
        return null;
    }

    return $key;
}


// ============================================================
// CHECK WHETHER S3 OBJECT EXISTS
// ============================================================

function s3ObjectExists($s3, $bucket, $key)
{
    try {

        $s3->headObject(
            array(
                "Bucket" => $bucket,
                "Key"    => $key
            )
        );

        return true;
    } catch (AwsException $e) {

        $statusCode = $e->getStatusCode();

        if (
            $statusCode === 404 ||
            $e->getAwsErrorCode() === "NotFound" ||
            $e->getAwsErrorCode() === "NoSuchKey"
        ) {
            return false;
        }

        throw $e;
    }
}


// ============================================================
// UPSERT run_race_details (used by batch sync)
// One row per date + type + race_type.
// ============================================================

function upsertRunRaceDetail($conn, $date, $type, $raceType, $fileUrl, $htmUrl)
{
    $sel = $conn->prepare(
        "SELECT id, file_url FROM run_race_details
         WHERE `date` = ? AND `type` = ? AND `race_type` = ?
         ORDER BY id ASC LIMIT 1"
    );

    if ($sel === false) {
        throw new RuntimeException("Unable to prepare select: " . $conn->error);
    }

    $sel->bind_param("sss", $date, $type, $raceType);

    if (!$sel->execute()) {
        $err = $sel->error;
        $sel->close();
        throw new RuntimeException("Select failed: " . $err);
    }

    $res = $sel->get_result();
    $row = ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;
    $sel->close();

    if ($row !== null) {

        $id = (int)$row["id"];

        $upd = $conn->prepare(
            "UPDATE run_race_details
             SET file_url = ?, htm_file_url = COALESCE(?, htm_file_url)
             WHERE id = ?"
        );

        if ($upd === false) {
            throw new RuntimeException("Unable to prepare update: " . $conn->error);
        }

        $upd->bind_param("ssi", $fileUrl, $htmUrl, $id);

        if (!$upd->execute()) {
            $err = $upd->error;
            $upd->close();
            throw new RuntimeException("Update failed: " . $err);
        }

        $upd->close();

        return array("operation" => "update", "id" => $id);
    }

    $ins = $conn->prepare(
        "INSERT INTO run_race_details (`date`, `type`, `race_type`, `file_url`, `htm_file_url`)
         VALUES (?, ?, ?, ?, ?)"
    );

    if ($ins === false) {
        throw new RuntimeException("Unable to prepare insert: " . $conn->error);
    }

    $ins->bind_param("sssss", $date, $type, $raceType, $fileUrl, $htmUrl);

    if (!$ins->execute()) {
        $err = $ins->error;
        $ins->close();
        throw new RuntimeException("Insert failed: " . $err);
    }

    $newId = $ins->insert_id;
    $ins->close();

    return array("operation" => "insert", "id" => $newId);
}


// ============================================================
// READ REQUEST DATA
// ============================================================

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {

    $action = isset($_GET["action"])
        ? strtolower(trim($_GET["action"]))
        : "check";
} else {

    $action = isset($_POST["action"])
        ? strtolower(trim($_POST["action"]))
        : "sync";
}


// ============================================================
// GET ACTION = LIST
// ============================================================

if ($method === "GET" && $action === "list") {

    $stmt = $conn->prepare(
        "
        SELECT
            id,
            `date`,
            `type`,
            `race_type`,
            `file_url`,
            `htm_file_url`
        FROM run_race_details
        WHERE file_url IS NOT NULL
          AND file_url <> ''
          AND `race_type` IN ('pre_race', 'post_race')
        ORDER BY `date` ASC, id ASC
        "
    );

    if ($stmt === false) {
        sendResponse(false, null, "Unable to prepare archive list query.", 500);
    }

    if (!$stmt->execute()) {

        $error = $stmt->error;
        $stmt->close();

        sendResponse(false, null, "Archive list query failed: " . $error, 500);
    }

    $result = $stmt->get_result();

    $files = array();

    while ($row = $result->fetch_assoc()) {

        $files[] = array(
            "id"           => (int)$row["id"],
            "date"         => $row["date"],
            "type"         => $row["type"],
            "race_type"    => $row["race_type"],
            "file_url"     => $row["file_url"],
            "htm_file_url" => $row["htm_file_url"]
        );
    }

    $stmt->close();

    sendResponse(true, array("files" => $files), null, 200);
}


// ============================================================
// GET ACTION = CHECK
// ============================================================

if ($method === "GET") {

    if ($action !== "check") {
        sendResponse(false, null, "Invalid GET action.", 400);
    }

    $date     = isset($_GET["date"]) ? trim($_GET["date"]) : "";
    $type     = isset($_GET["type"]) ? trim($_GET["type"]) : "";
    $raceType = isset($_GET["race_type"]) ? trim($_GET["race_type"]) : "";

    if (!validateDateValue($date)) {
        sendResponse(false, null, "Invalid date. Expected YYYY-MM-DD.", 400);
    }

    if ($type === "" || $raceType === "") {
        sendResponse(false, null, "type and race_type are required.", 400);
    }

    $stmt = $conn->prepare(
        "
        SELECT
            id,
            `date`,
            `type`,
            `race_type`,
            `file_url`,
            `htm_file_url`
        FROM run_race_details
        WHERE `date` = ?
          AND `type` = ?
          AND `race_type` = ?
        ORDER BY
            CASE
                WHEN file_url LIKE '%.html' THEN 0
                ELSE 1
            END,
            id DESC
        LIMIT 1
        "
    );

    if ($stmt === false) {
        sendResponse(false, null, "Unable to prepare database check query.", 500);
    }

    $stmt->bind_param("sss", $date, $type, $raceType);

    if (!$stmt->execute()) {

        $error = $stmt->error;
        $stmt->close();

        sendResponse(false, null, "Database check failed: " . $error, 500);
    }

    $result = $stmt->get_result();

    if (!$result || $result->num_rows === 0) {

        $stmt->close();

        sendResponse(
            true,
            array(
                "exists"       => false,
                "date"         => $date,
                "type"         => $type,
                "race_type"    => $raceType,
                "file_url"     => null,
                "htm_file_url" => null
            ),
            null,
            200
        );
    }

    $row = $result->fetch_assoc();

    $stmt->close();

    sendResponse(
        true,
        array(
            "exists"       => true,
            "date"         => $row["date"],
            "type"         => $row["type"],
            "race_type"    => $row["race_type"],
            "file_url"     => $row["file_url"],
            "htm_file_url" => $row["htm_file_url"]
        ),
        null,
        200
    );
}


// ============================================================
// ONLY POST BELOW THIS POINT
// ============================================================

if ($method !== "POST") {
    sendResponse(false, null, "Only GET and POST methods are allowed.", 405);
}


// ============================================================
// BATCH SYNC  (action = sync_website_data)
// POST: date=YYYY-MM-DD & files[owner]=run_races/owner.html & ...
// ERP "Push/Remove Website" button yahi call karta hai.
// ============================================================

if ($action === "sync_website_data") {

    $date  = isset($_POST["date"]) ? trim($_POST["date"]) : "";
    $files = isset($_POST["files"]) && is_array($_POST["files"]) ? $_POST["files"] : array();

    if (!validateDateValue($date)) {
        sendResponse(false, null, "Invalid date. Expected YYYY-MM-DD.", 400);
    }

    if (empty($files)) {
        sendResponse(false, null, "files array is required.", 400);
    }

    $results   = array();
    $hasFailed = false;

    foreach ($files as $label => $fileKey) {

        $key = normalizeS3Key($fileKey, $s3Prefix);

        if ($key === null) {
            $results[$label] = array("ok" => false, "error" => "Invalid S3 key: " . (is_string($fileKey) ? $fileKey : ""));
            $hasFailed = true;
            continue;
        }

        $info = getFileInformation(basename($key));

        if ($info["type"] === "other") {
            $results[$label] = array("ok" => false, "error" => "Unable to determine file type from file name.");
            $hasFailed = true;
            continue;
        }

        try {

            if (!s3ObjectExists($s3, AWS_BUCKET, $key)) {
                $results[$label] = array("ok" => false, "error" => "File not found in S3: " . $key);
                $hasFailed = true;
                continue;
            }

            $url = getS3FileUrl(AWS_BUCKET, AWS_REGION, $key);

            $op = upsertRunRaceDetail($conn, $date, $info["type"], $info["race_type"], $url, null);

            $results[$label] = array(
                "ok"        => true,
                "operation" => $op["operation"],
                "id"        => $op["id"],
                "type"      => $info["type"],
                "race_type" => $info["race_type"],
                "file_url"  => $url
            );
        } catch (Throwable $e) {

            error_log("sync_website_data (" . $label . ") failed: " . $e->getMessage());

            $results[$label] = array("ok" => false, "error" => $e->getMessage());
            $hasFailed = true;
        }
    }

    if ($hasFailed) {
        sendResponse(false, array("date" => $date, "results" => $results), "One or more files failed to sync.", 422);
    }

    sendResponse(true, array("action" => "sync_website_data", "date" => $date, "results" => $results), null, 200);
}


// ============================================================
// COMMON POST DATA
// ============================================================

$date     = isset($_POST["date"]) ? trim($_POST["date"]) : "";
$htmlFile = isset($_POST["html_file"]) ? trim($_POST["html_file"]) : "";
$htmFile  = isset($_POST["htm_file"]) ? trim($_POST["htm_file"]) : "";

if (!validateDateValue($date)) {
    sendResponse(false, null, "Invalid date. Expected YYYY-MM-DD.", 400);
}


// ============================================================
// NORMALIZE FILE KEYS
// ============================================================

$htmlKey = null;
$htmKey  = null;

if ($htmlFile !== "") {

    $htmlKey = normalizeS3Key($htmlFile, $s3Prefix);

    if ($htmlKey === null) {
        sendResponse(false, null, "Invalid html_file. File must be an HTML file inside run_races/.", 400);
    }
}

if ($htmFile !== "") {

    $htmKey = normalizeS3Key($htmFile, $s3Prefix);

    if ($htmKey === null) {
        sendResponse(false, null, "Invalid htm_file. File must be an HTM file inside run_races/.", 400);
    }
}

if ($htmlKey === null && $htmKey === null) {
    sendResponse(false, null, "html_file or htm_file is required.", 400);
}


// ============================================================
// CHECK FILE DATE + TYPE
// ============================================================

$referenceKey      = ($htmlKey !== null) ? $htmlKey : $htmKey;
$referenceFilename = basename($referenceKey);

$fileDate = getFileDate($referenceFilename);

if ($fileDate === null || $fileDate !== $date) {
    sendResponse(false, null, "The date does not match the date in the file name.", 400);
}

$fileInfo = getFileInformation($referenceFilename);

$type     = $fileInfo["type"];
$raceType = $fileInfo["race_type"];

if ($type === "other" || $raceType === "other") {
    sendResponse(false, null, "Unable to determine file type from file name.", 400);
}


// ============================================================
// BOTH FILES MUST BELONG TO THE SAME LOGICAL DOCUMENT
// ============================================================

foreach (array($htmlKey, $htmKey) as $key) {

    if ($key === null) {
        continue;
    }

    $info    = getFileInformation(basename($key));
    $keyDate = getFileDate(basename($key));

    if (
        $keyDate !== $date ||
        $info["type"] !== $type ||
        $info["race_type"] !== $raceType
    ) {
        sendResponse(false, null, "html_file and htm_file must belong to the same date and file type.", 400);
    }
}


// ============================================================
// CHECK S3 OBJECTS
// ============================================================

$htmlExists = false;
$htmExists  = false;

try {

    if ($htmlKey !== null) {
        $htmlExists = s3ObjectExists($s3, AWS_BUCKET, $htmlKey);
    }

    if ($htmKey !== null) {
        $htmExists = s3ObjectExists($s3, AWS_BUCKET, $htmKey);
    }
} catch (AwsException $e) {

    error_log("S3 HEAD ERROR: " . $e->getMessage());

    sendResponse(false, null, "Unable to verify S3 file.", 500);
}


// ============================================================
// URLS OF THE FILES THAT ACTUALLY EXIST IN S3
// ============================================================

$htmlUrl = $htmlExists ? getS3FileUrl(AWS_BUCKET, AWS_REGION, $htmlKey) : null;
$htmUrl  = $htmExists  ? getS3FileUrl(AWS_BUCKET, AWS_REGION, $htmKey)  : null;

// file_url = html (preferred), otherwise htm
$selectedUrl = ($htmlUrl !== null) ? $htmlUrl : $htmUrl;


// ============================================================
// DELETE ACTION
// ============================================================

if ($action === "delete") {

    if ($htmlExists || $htmExists) {

        $selectStmt = $conn->prepare(
            "
            SELECT id
            FROM run_race_details
            WHERE `date` = ?
              AND `type` = ?
              AND `race_type` = ?
            ORDER BY id ASC
            LIMIT 1
            "
        );

        if ($selectStmt === false) {
            sendResponse(false, null, "Unable to prepare database query.", 500);
        }

        $selectStmt->bind_param("sss", $date, $type, $raceType);
        $selectStmt->execute();

        $result = $selectStmt->get_result();

        if ($result && $result->num_rows > 0) {

            $row = $result->fetch_assoc();
            $id  = (int)$row["id"];

            $selectStmt->close();

            if ($htmKey !== null) {

                $updateStmt = $conn->prepare(
                    "
                    UPDATE run_race_details
                    SET file_url = ?, htm_file_url = ?
                    WHERE id = ?
                    "
                );

                if ($updateStmt === false) {
                    sendResponse(false, null, "Unable to prepare database update query.", 500);
                }

                $updateStmt->bind_param("ssi", $selectedUrl, $htmUrl, $id);
            } else {

                $updateStmt = $conn->prepare(
                    "
                    UPDATE run_race_details
                    SET file_url = ?
                    WHERE id = ?
                    "
                );

                if ($updateStmt === false) {
                    sendResponse(false, null, "Unable to prepare database update query.", 500);
                }

                $updateStmt->bind_param("si", $selectedUrl, $id);
            }

            if (!$updateStmt->execute()) {

                $error = $updateStmt->error;
                $updateStmt->close();

                sendResponse(false, null, "Database update failed: " . $error, 500);
            }

            $updateStmt->close();

            sendResponse(
                true,
                array(
                    "action"         => "delete",
                    "operation"      => "update",
                    "date"           => $date,
                    "type"           => $type,
                    "race_type"      => $raceType,
                    "remaining_file" => basename(($htmlExists ? $htmlKey : $htmKey)),
                    "file_url"       => $selectedUrl,
                    "htm_file_url"   => $htmUrl
                ),
                null,
                200
            );
        }

        $selectStmt->close();

        sendResponse(
            true,
            array(
                "action"    => "delete",
                "operation" => "nothing_to_delete",
                "date"      => $date,
                "type"      => $type,
                "race_type" => $raceType
            ),
            null,
            200
        );
    }

    // No supplied file exists in S3 -> delete the DB record.

    $deleteStmt = $conn->prepare(
        "
        DELETE FROM run_race_details
        WHERE `date` = ?
          AND `type` = ?
          AND `race_type` = ?
        "
    );

    if ($deleteStmt === false) {
        sendResponse(false, null, "Unable to prepare database delete query.", 500);
    }

    $deleteStmt->bind_param("sss", $date, $type, $raceType);

    if (!$deleteStmt->execute()) {

        $error = $deleteStmt->error;
        $deleteStmt->close();

        sendResponse(false, null, "Database delete failed: " . $error, 500);
    }

    $deletedRows = $deleteStmt->affected_rows;

    $deleteStmt->close();

    sendResponse(
        true,
        array(
            "action"       => "delete",
            "operation"    => "delete",
            "date"         => $date,
            "type"         => $type,
            "race_type"    => $raceType,
            "deleted_rows" => $deletedRows
        ),
        null,
        200
    );
}


// ============================================================
// ONLY SYNC ACTION REMAINS
// ============================================================

if ($action !== "" && $action !== "sync") {
    sendResponse(false, null, "Invalid POST action. Use sync, sync_website_data or delete.", 400);
}

if (!$htmlExists && !$htmExists) {

    sendResponse(
        false,
        array(
            "date"      => $date,
            "type"      => $type,
            "race_type" => $raceType
        ),
        "Neither supplied file exists in S3. Database was not changed.",
        404
    );
}


// ============================================================
// CHECK EXISTING DATABASE ROW
// Mock Race Result: exact file_url is part of row identity.
// ============================================================

if ($type === "mock_race_result") {

    $checkStmt = $conn->prepare(
        "
        SELECT id, file_url
        FROM run_race_details
        WHERE `date` = ?
          AND `type` = ?
          AND `race_type` = ?
          AND file_url = ?
        ORDER BY id ASC
        LIMIT 1
        "
    );
} else {

    $checkStmt = $conn->prepare(
        "
        SELECT id, file_url
        FROM run_race_details
        WHERE `date` = ?
          AND `type` = ?
          AND `race_type` = ?
        ORDER BY id ASC
        LIMIT 1
        "
    );
}

if ($checkStmt === false) {
    sendResponse(false, null, "Unable to prepare database check query.", 500);
}

if ($type === "mock_race_result") {
    $checkStmt->bind_param("ssss", $date, $type, $raceType, $selectedUrl);
} else {
    $checkStmt->bind_param("sss", $date, $type, $raceType);
}

if (!$checkStmt->execute()) {

    $error = $checkStmt->error;
    $checkStmt->close();

    sendResponse(false, null, "Database check failed: " . $error, 500);
}

$result = $checkStmt->get_result();


// ============================================================
// UPDATE EXISTING
// htm_file_url = COALESCE(new htm url, existing htm_file_url)
// ============================================================

if ($result && $result->num_rows > 0) {

    $row = $result->fetch_assoc();

    $id     = (int)$row["id"];
    $oldUrl = $row["file_url"];

    $checkStmt->close();

    $updateStmt = $conn->prepare(
        "
        UPDATE run_race_details
        SET file_url = ?,
            htm_file_url = COALESCE(?, htm_file_url)
        WHERE id = ?
        "
    );

    if ($updateStmt === false) {
        sendResponse(false, null, "Unable to prepare database update query.", 500);
    }

    $updateStmt->bind_param("ssi", $selectedUrl, $htmUrl, $id);

    if (!$updateStmt->execute()) {

        $error = $updateStmt->error;
        $updateStmt->close();

        sendResponse(false, null, "Database update failed: " . $error, 500);
    }

    $updateStmt->close();

    sendResponse(
        true,
        array(
            "action"       => "sync",
            "operation"    => "update",
            "id"           => $id,
            "date"         => $date,
            "type"         => $type,
            "race_type"    => $raceType,
            "file_url"     => $selectedUrl,
            "htm_file_url" => $htmUrl,
            "previous_url" => $oldUrl,
            "html_exists"  => $htmlExists,
            "htm_exists"   => $htmExists
        ),
        null,
        200
    );
}


// ============================================================
// INSERT NEW
// ============================================================

$checkStmt->close();

$insertStmt = $conn->prepare(
    "
    INSERT INTO run_race_details
    (`date`, `type`, `race_type`, `file_url`, `htm_file_url`)
    VALUES (?, ?, ?, ?, ?)
    "
);

if ($insertStmt === false) {
    sendResponse(false, null, "Unable to prepare database insert query.", 500);
}

$insertStmt->bind_param("sssss", $date, $type, $raceType, $selectedUrl, $htmUrl);

if (!$insertStmt->execute()) {

    $error = $insertStmt->error;
    $insertStmt->close();

    sendResponse(false, null, "Database insert failed: " . $error, 500);
}

$newId = $insertStmt->insert_id;

$insertStmt->close();

sendResponse(
    true,
    array(
        "action"       => "sync",
        "operation"    => "insert",
        "id"           => $newId,
        "date"         => $date,
        "type"         => $type,
        "race_type"    => $raceType,
        "file_url"     => $selectedUrl,
        "htm_file_url" => $htmUrl,
        "html_exists"  => $htmlExists,
        "htm_exists"   => $htmExists
    ),
    null,
    200
);