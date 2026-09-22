<?php

/**
 * RWITC S3 <-> run_race_details API
 *
 * FLOW
 * ----
 * ERP Push Website
 *      |
 *      | POST action=sync
 *      | date + html_file + htm_file
 *      v
 * This API
 *      |
 *      | checks the supplied S3 objects
 *      | INSERT / UPDATE run_race_details
 *      v
 * run_race_details
 *
 * DELETE FLOW
 * -----------
 * ERP deletes S3 file(s)
 *      |
 *      | POST action=delete
 *      v
 * This API checks S3 again
 *      |
 *      | if no requested file remains -> DELETE DB row
 *      | if one file remains       -> UPDATE DB row
 *
 * READ FLOW
 * ---------
 * GET ?action=check&date=YYYY-MM-DD&type=...&race_type=...
 *      |
 *      v
 * run_race_details
 */

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");


// ============================================================
// CONFIG
// ============================================================

require_once __DIR__ . "/config/config.php";

// AWS SDK
require_once __DIR__ . "/../vendor/autoload.php";

use Aws\S3\S3Client;
use Aws\Exception\AwsException;


// ============================================================
// RESPONSE HELPER
// ============================================================

function sendResponse(
    $success,
    $data = null,
    $error = null,
    $statusCode = 200
) {
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

    error_log(
        "S3 CLIENT ERROR: " . $e->getMessage()
    );

    sendResponse(
        false,
        null,
        "Unable to initialize S3.",
        500
    );
}


// ============================================================
// DATABASE CHECK
// ============================================================

if (
    !isset($conn) ||
    !($conn instanceof mysqli)
) {

    sendResponse(
        false,
        null,
        "Database connection is not available.",
        500
    );
}

$conn->set_charset("utf8mb4");


// ============================================================
// CONSTANTS
// ============================================================

$s3Prefix = "run_races/";


// ============================================================
// VALIDATE DATE
// ============================================================

function validateDateValue($date)
{
    if (
        !is_string($date) ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
    ) {
        return false;
    }

    $d = DateTime::createFromFormat(
        "Y-m-d",
        $date
    );

    return (
        $d !== false &&
        $d->format("Y-m-d") === $date
    );
}


// ============================================================
// GET TYPE + RACE TYPE FROM FILE NAME
// ============================================================

function getFileInformation($filename)
{
    $name = strtolower(
        basename($filename)
    );

    // HANDICAPS
    if (
        strpos($name, "handicaps_") === 0
    ) {
        return array(
            "type"      => "handicaps",
            "race_type" => "pre_race"
        );
    }

    // ACCEPTANCES
    if (
        strpos($name, "acceptance_") === 0 ||
        strpos($name, "acceptances_") === 0
    ) {
        return array(
            "type"      => "acceptances",
            "race_type" => "pre_race"
        );
    }

    // DECLARATIONS
    if (
        strpos($name, "declarations_") === 0
    ) {
        return array(
            "type"      => "declarations",
            "race_type" => "pre_race"
        );
    }

    // RACE CARD
    if (
        strpos($name, "race_card_") === 0 ||
        strpos($name, "race_card_report_") === 0
    ) {
        return array(
            "type"      => "racecard",
            "race_type" => "pre_race"
        );
    }

    // RACE RESULTS
    if (
        strpos($name, "race_results_") === 0 ||
        strpos($name, "race_result_") === 0
    ) {
        return array(
            "type"      => "race_result",
            "race_type" => "post_race"
        );
    }

    // RATING CHANGE
    if (
        strpos($name, "rating_change_") === 0
    ) {
        return array(
            "type"      => "rating_change",
            "race_type" => "post_race"
        );
    }

    // MOCK RACE RESULT
    if (
        strpos($name, "mock_race_result_") === 0
    ) {
        return array(
            "type"      => "mock_race_result",
            "race_type" => "post_race"
        );
    }

    return array(
        "type"      => "other",
        "race_type" => "other"
    );
}


// ============================================================
// GET DATE FROM FILE NAME
// ============================================================

function getFileDate($filename)
{
    if (
        preg_match(
            '/_(\d{4}-\d{2}-\d{2})\.(html|htm)$/i',
            basename($filename),
            $matches
        )
    ) {
        return $matches[1];
    }

    return null;
}


// ============================================================
// GET PUBLIC S3 URL
// ============================================================

function getS3FileUrl($bucket, $region, $key)
{
    return "https://" .
        $bucket .
        ".s3." .
        $region .
        ".amazonaws.com/" .
        str_replace(
            "%2F",
            "/",
            rawurlencode($key)
        );
}


// ============================================================
// NORMALIZE / VALIDATE S3 KEY
// ============================================================

function normalizeS3Key($key, $expectedPrefix)
{
    if (
        !is_string($key) ||
        trim($key) === ""
    ) {
        return null;
    }

    $key = trim($key);

    // Must stay inside run_races/
    if (
        strpos($key, $expectedPrefix) !== 0
    ) {
        return null;
    }

    // Only HTML / HTM files are accepted.
    $extension = strtolower(
        pathinfo($key, PATHINFO_EXTENSION)
    );

    if (
        $extension !== "html" &&
        $extension !== "htm"
    ) {
        return null;
    }

    // Do not allow another directory level.
    $relative = substr(
        $key,
        strlen($expectedPrefix)
    );

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
//
// Used by Archives API.
//
// Example:
// ?action=list
//
// Returns all S3-backed archive files from run_race_details
// in the format expected by fetchArchives_get_api.php:
// data.files[]
//
// ============================================================

if ($method === "GET" && $action === "list") {

    $stmt = $conn->prepare(
        "
        SELECT
            id,
            `date`,
            `type`,
            `race_type`,
            `file_url`
        FROM run_race_details
        WHERE file_url IS NOT NULL
          AND file_url <> ''
          AND `race_type` IN ('pre_race', 'post_race')
        ORDER BY `date` ASC, id ASC
        "
    );

    if ($stmt === false) {

        sendResponse(
            false,
            null,
            "Unable to prepare archive list query.",
            500
        );
    }

    if (!$stmt->execute()) {

        $error = $stmt->error;
        $stmt->close();

        sendResponse(
            false,
            null,
            "Archive list query failed: " . $error,
            500
        );
    }

    $result = $stmt->get_result();

    $files = [];

    while ($row = $result->fetch_assoc()) {

        $files[] = array(
            "id"        => (int)$row["id"],
            "date"      => $row["date"],
            "type"      => $row["type"],
            "race_type" => $row["race_type"],
            "file_url"  => $row["file_url"]
        );
    }

    $stmt->close();

    sendResponse(
        true,
        array(
            "files" => $files
        ),
        null,
        200
    );
}

// ============================================================
// GET ACTION
// ============================================================
//
// GET action=check
//
// Example:
// ?action=check
// &date=2026-08-22
// &type=handicaps
// &race_type=pre_race
//
// ============================================================

if ($method === "GET") {

    if ($action !== "check") {

        sendResponse(
            false,
            null,
            "Invalid GET action.",
            400
        );
    }

    $date = isset($_GET["date"])
        ? trim($_GET["date"])
        : "";

    $type = isset($_GET["type"])
        ? trim($_GET["type"])
        : "";

    $raceType = isset($_GET["race_type"])
        ? trim($_GET["race_type"])
        : "";

    if (!validateDateValue($date)) {

        sendResponse(
            false,
            null,
            "Invalid date. Expected YYYY-MM-DD.",
            400
        );
    }

    if ($type === "" || $raceType === "") {

        sendResponse(
            false,
            null,
            "type and race_type are required.",
            400
        );
    }

    $stmt = $conn->prepare(
        "
        SELECT
            id,
            `date`,
            `type`,
            `race_type`,
            `file_url`
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

        sendResponse(
            false,
            null,
            "Unable to prepare database check query.",
            500
        );
    }

    $stmt->bind_param(
        "sss",
        $date,
        $type,
        $raceType
    );

    if (!$stmt->execute()) {

        $error = $stmt->error;
        $stmt->close();

        sendResponse(
            false,
            null,
            "Database check failed: " . $error,
            500
        );
    }

    $result = $stmt->get_result();

    if (
        !$result ||
        $result->num_rows === 0
    ) {

        $stmt->close();

        sendResponse(
            true,
            array(
                "exists"    => false,
                "date"      => $date,
                "type"      => $type,
                "race_type" => $raceType,
                "file_url"  => null
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
            "exists"    => true,
            "date"      => $row["date"],
            "type"      => $row["type"],
            "race_type" => $row["race_type"],
            "file_url"  => $row["file_url"]
        ),
        null,
        200
    );
}


// ============================================================
// ONLY POST BELOW THIS POINT
// ============================================================

if ($method !== "POST") {

    sendResponse(
        false,
        null,
        "Only GET and POST methods are allowed.",
        405
    );
}


// ============================================================
// COMMON POST DATA
// ============================================================

$date = isset($_POST["date"])
    ? trim($_POST["date"])
    : "";

$htmlFile = isset($_POST["html_file"])
    ? trim($_POST["html_file"])
    : "";

$htmFile = isset($_POST["htm_file"])
    ? trim($_POST["htm_file"])
    : "";


// ============================================================
// VALIDATE DATE
// ============================================================

if (!validateDateValue($date)) {

    sendResponse(
        false,
        null,
        "Invalid date. Expected YYYY-MM-DD.",
        400
    );
}


// ============================================================
// NORMALIZE FILE KEYS
// ============================================================

$htmlKey = null;
$htmKey  = null;

if ($htmlFile !== "") {

    $htmlKey = normalizeS3Key(
        $htmlFile,
        $s3Prefix
    );

    if ($htmlKey === null) {

        sendResponse(
            false,
            null,
            "Invalid html_file. File must be an HTML file inside run_races/.",
            400
        );
    }
}

if ($htmFile !== "") {

    $htmKey = normalizeS3Key(
        $htmFile,
        $s3Prefix
    );

    if ($htmKey === null) {

        sendResponse(
            false,
            null,
            "Invalid htm_file. File must be an HTM file inside run_races/.",
            400
        );
    }
}

if (
    $htmlKey === null &&
    $htmKey === null
) {

    sendResponse(
        false,
        null,
        "html_file or htm_file is required.",
        400
    );
}


// ============================================================
// CHECK FILE DATE + TYPE
// ============================================================

$referenceKey =
    $htmlKey !== null
        ? $htmlKey
        : $htmKey;

$referenceFilename =
    basename($referenceKey);

$fileDate =
    getFileDate($referenceFilename);

if (
    $fileDate === null ||
    $fileDate !== $date
) {

    sendResponse(
        false,
        null,
        "The date does not match the date in the file name.",
        400
    );
}

$fileInfo =
    getFileInformation($referenceFilename);

$type =
    $fileInfo["type"];

$raceType =
    $fileInfo["race_type"];

if (
    $type === "other" ||
    $raceType === "other"
) {

    sendResponse(
        false,
        null,
        "Unable to determine file type from file name.",
        400
    );
}


// ============================================================
// IF BOTH FILES ARE SENT, THEY MUST REPRESENT SAME LOGICAL FILE
// ============================================================

foreach (
    array(
        $htmlKey,
        $htmKey
    ) as $key
) {

    if ($key === null) {
        continue;
    }

    $info =
        getFileInformation(
            basename($key)
        );

    $keyDate =
        getFileDate(
            basename($key)
        );

    if (
        $keyDate !== $date ||
        $info["type"] !== $type ||
        $info["race_type"] !== $raceType
    ) {

        sendResponse(
            false,
            null,
            "html_file and htm_file must belong to the same date and file type.",
            400
        );
    }
}


// ============================================================
// CHECK S3 OBJECTS
// ============================================================

$htmlExists = false;
$htmExists  = false;

try {

    if ($htmlKey !== null) {

        $htmlExists =
            s3ObjectExists(
                $s3,
                AWS_BUCKET,
                $htmlKey
            );
    }

    if ($htmKey !== null) {

        $htmExists =
            s3ObjectExists(
                $s3,
                AWS_BUCKET,
                $htmKey
            );
    }

} catch (AwsException $e) {

    error_log(
        "S3 HEAD ERROR: " . $e->getMessage()
    );

    sendResponse(
        false,
        null,
        "Unable to verify S3 file.",
        500
    );
}


// ============================================================
// DELETE ACTION
// ============================================================
//
// This action is called AFTER ERP deletes S3 file(s).
//
// If no supplied S3 file remains:
//     DELETE DB record.
//
// If one file still remains:
//     UPDATE DB record to the remaining S3 URL.
//
// ============================================================

if ($action === "delete") {

    if (
        $htmlExists ||
        $htmExists
    ) {

        if ($htmlExists) {
            $selectedKey = $htmlKey;
        } else {
            $selectedKey = $htmKey;
        }

        $selectedUrl =
            getS3FileUrl(
                AWS_BUCKET,
                AWS_REGION,
                $selectedKey
            );

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

            sendResponse(
                false,
                null,
                "Unable to prepare database query.",
                500
            );
        }

        $selectStmt->bind_param(
            "sss",
            $date,
            $type,
            $raceType
        );

        $selectStmt->execute();

        $result =
            $selectStmt->get_result();

        if (
            $result &&
            $result->num_rows > 0
        ) {

            $row =
                $result->fetch_assoc();

            $id =
                (int)$row["id"];

            $selectStmt->close();

            $updateStmt = $conn->prepare(
                "
                UPDATE run_race_details
                SET file_url = ?
                WHERE id = ?
                "
            );

            if ($updateStmt === false) {

                sendResponse(
                    false,
                    null,
                    "Unable to prepare database update query.",
                    500
                );
            }

            $updateStmt->bind_param(
                "si",
                $selectedUrl,
                $id
            );

            if (!$updateStmt->execute()) {

                $error =
                    $updateStmt->error;

                $updateStmt->close();

                sendResponse(
                    false,
                    null,
                    "Database update failed: " . $error,
                    500
                );
            }

            $updateStmt->close();

            sendResponse(
                true,
                array(
                    "action"       => "delete",
                    "operation"    => "update",
                    "date"         => $date,
                    "type"         => $type,
                    "race_type"    => $raceType,
                    "remaining_file" => basename($selectedKey),
                    "file_url"     => $selectedUrl
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


    // No supplied file exists in S3.
    // Delete the DB record.

    $deleteStmt = $conn->prepare(
        "
        DELETE FROM run_race_details
        WHERE `date` = ?
          AND `type` = ?
          AND `race_type` = ?
        "
    );

    if ($deleteStmt === false) {

        sendResponse(
            false,
            null,
            "Unable to prepare database delete query.",
            500
        );
    }

    $deleteStmt->bind_param(
        "sss",
        $date,
        $type,
        $raceType
    );

    if (!$deleteStmt->execute()) {

        $error =
            $deleteStmt->error;

        $deleteStmt->close();

        sendResponse(
            false,
            null,
            "Database delete failed: " . $error,
            500
        );
    }

    $deletedRows =
        $deleteStmt->affected_rows;

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

if (
    $action !== "" &&
    $action !== "sync"
) {

    sendResponse(
        false,
        null,
        "Invalid POST action. Use sync or delete.",
        400
    );
}


// ============================================================
// SYNC ACTION
// ============================================================
//
// At this point:
//     S3 object(s) have already been uploaded by ERP.
//
// We verify them again.
// Then:
//     existing DB row -> UPDATE
//     no DB row        -> INSERT
//
// HTML is preferred over HTM for file_url.
// ============================================================

if (
    !$htmlExists &&
    !$htmExists
) {

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
// SELECT BEST AVAILABLE FILE
// ============================================================

if ($htmlExists) {

    $selectedKey =
        $htmlKey;

} else {

    $selectedKey =
        $htmKey;
}

$selectedUrl =
    getS3FileUrl(
        AWS_BUCKET,
        AWS_REGION,
        $selectedKey
);


// ============================================================
// CHECK EXISTING DATABASE ROW
// ============================================================

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

if ($checkStmt === false) {

    sendResponse(
        false,
        null,
        "Unable to prepare database check query.",
        500
    );
}

$checkStmt->bind_param(
    "sss",
    $date,
    $type,
    $raceType
);

if (!$checkStmt->execute()) {

    $error =
        $checkStmt->error;

    $checkStmt->close();

    sendResponse(
        false,
        null,
        "Database check failed: " . $error,
        500
    );
}

$result =
    $checkStmt->get_result();


// ============================================================
// UPDATE EXISTING
// ============================================================

if (
    $result &&
    $result->num_rows > 0
) {

    $row =
        $result->fetch_assoc();

    $id =
        (int)$row["id"];

    $oldUrl =
        $row["file_url"];

    $checkStmt->close();

    $updateStmt = $conn->prepare(
        "
        UPDATE run_race_details
        SET file_url = ?
        WHERE id = ?
        "
    );

    if ($updateStmt === false) {

        sendResponse(
            false,
            null,
            "Unable to prepare database update query.",
            500
        );
    }

    $updateStmt->bind_param(
        "si",
        $selectedUrl,
        $id
    );

    if (!$updateStmt->execute()) {

        $error =
            $updateStmt->error;

        $updateStmt->close();

        sendResponse(
            false,
            null,
            "Database update failed: " . $error,
            500
        );
    }

    $updateStmt->close();

    sendResponse(
        true,
        array(
            "action"         => "sync",
            "operation"      => "update",
            "id"             => $id,
            "date"           => $date,
            "type"           => $type,
            "race_type"      => $raceType,
            "file_url"       => $selectedUrl,
            "previous_url"   => $oldUrl,
            "html_exists"    => $htmlExists,
            "htm_exists"     => $htmExists
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
    (
        `date`,
        `type`,
        `race_type`,
        `file_url`
    )
    VALUES
    (
        ?,
        ?,
        ?,
        ?
    )
    "
);

if ($insertStmt === false) {

    sendResponse(
        false,
        null,
        "Unable to prepare database insert query.",
        500
    );
}

$insertStmt->bind_param(
    "ssss",
    $date,
    $type,
    $raceType,
    $selectedUrl
);

if (!$insertStmt->execute()) {

    $error =
        $insertStmt->error;

    $insertStmt->close();

    sendResponse(
        false,
        null,
        "Database insert failed: " . $error,
        500
    );
}

$newId =
    $insertStmt->insert_id;

$insertStmt->close();


// ============================================================
// FINAL SYNC SUCCESS
// ============================================================

sendResponse(
    true,
    array(
        "action"      => "sync",
        "operation"   => "insert",
        "id"          => $newId,
        "date"        => $date,
        "type"        => $type,
        "race_type"   => $raceType,
        "file_url"    => $selectedUrl,
        "html_exists" => $htmlExists,
        "htm_exists"  => $htmExists
    ),
    null,
    200
);
