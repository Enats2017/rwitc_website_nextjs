<?php

/**
 * RWITC MOCK RACE RESULT CALENDAR API
 *
 * Fetch priority:
 * 1. Check local run_races folder first.
 * 2. If the file exists locally, return its normal run_races URL.
 * 3. If it does not exist locally, check run_race_details.
 * 4. For DB/S3 fallback, return this API's open URL, never the S3 URL.
 *
 * open=1&id=112 makes this same API fetch the S3 HTML internally
 * and output it, so the S3 URL remains hidden from the browser.
 */

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

require_once __DIR__ . "/config/config.php";

function sendResponse($success, $data = array(), $error = null, $statusCode = 200)
{
    http_response_code($statusCode);

    echo json_encode(
        array(
            "success" => $success,
            "data"    => $data,
            "error"   => $error
        ),
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    exit;
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    sendResponse(false, array(), "Database connection is not available.", 500);
}

$conn->set_charset("utf8mb4");


/* ============================================================
   OPEN MODE
   ============================================================
   Example:
   mock_race_result_calendar_api.php?open=1&id=112

   This same API reads the S3 URL from DB and returns the HTML.
   The S3 URL is never exposed to the browser.
   ============================================================ */

$open = isset($_GET["open"]) ? (int)$_GET["open"] : 0;

if ($open === 1) {

    $id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

    if ($id <= 0) {
        http_response_code(400);
        header("Content-Type: text/plain; charset=UTF-8");
        echo "Invalid Mock Race Result ID.";
        exit;
    }

    $openSql = "
        SELECT file_url
        FROM run_race_details
        WHERE id = ?
          AND `type` = 'mock_race_result'
          AND `race_type` = 'post_race'
        LIMIT 1
    ";

    $openStmt = $conn->prepare($openSql);

    if ($openStmt === false) {
        http_response_code(500);
        header("Content-Type: text/plain; charset=UTF-8");
        echo "Unable to prepare database query.";
        exit;
    }

    $openStmt->bind_param("i", $id);

    if (!$openStmt->execute()) {
        $openStmt->close();
        http_response_code(500);
        header("Content-Type: text/plain; charset=UTF-8");
        echo "Database query failed.";
        exit;
    }

    $openResult = $openStmt->get_result();
    $openRow = $openResult->fetch_assoc();
    $openStmt->close();

    if (!$openRow) {
        http_response_code(404);
        header("Content-Type: text/plain; charset=UTF-8");
        echo "Mock Race Result not found.";
        exit;
    }

    $s3Url = trim($openRow["file_url"]);

    if ($s3Url === "") {
        http_response_code(404);
        header("Content-Type: text/plain; charset=UTF-8");
        echo "Mock Race Result file URL is empty.";
        exit;
    }

    $context = stream_context_create(
        array(
            "http" => array(
                "method" => "GET",
                "timeout" => 30,
                "follow_location" => 1
            )
        )
    );

    $html = @file_get_contents($s3Url, false, $context);

    if ($html === false) {
        http_response_code(404);
        header("Content-Type: text/plain; charset=UTF-8");
        echo "Unable to load Mock Race Result file from S3.";
        exit;
    }

    header("Content-Type: text/html; charset=UTF-8");
    header("Cache-Control: public, max-age=300");

    echo $html;
    exit;
}


/* ============================================================
   YEAR / MONTH
   ============================================================ */

$year = isset($_GET["year"]) ? trim($_GET["year"]) : "";
$month = isset($_GET["month"]) ? trim($_GET["month"]) : "";

if ($year !== "" && !preg_match('/^\d{4}$/', $year)) {
    sendResponse(false, array(), "Invalid year. Expected YYYY.", 400);
}

if ($month !== "") {

    if (!preg_match('/^(0?[1-9]|1[0-2])$/', $month)) {
        sendResponse(false, array(), "Invalid month. Expected 1-12.", 400);
    }

    $month = str_pad($month, 2, "0", STR_PAD_LEFT);
}


/* ============================================================
   DATE RANGE
   ============================================================ */

$startDate = "";
$endDate = "";

if ($year !== "" && $month !== "") {

    $startDate = $year . "-" . $month . "-01";

    $endDate = date(
        "Y-m-d",
        strtotime($startDate . " +1 month")
    );
}


/* ============================================================
   API BASE URL
   ============================================================ */

$scheme =
    (
        isset($_SERVER["HTTPS"]) &&
        $_SERVER["HTTPS"] !== "off"
    )
    ? "https://"
    : "http://";

$apiBaseUrl =
    $scheme .
    $_SERVER["HTTP_HOST"] .
    rtrim(
        str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"])),
        "/"
    );

$apiOpenBaseUrl =
    $apiBaseUrl .
    "/" .
    basename($_SERVER["SCRIPT_NAME"]);


/* ============================================================
   LOCAL RUN_RACES CONFIG
   ============================================================ */

$localRunRacesPath = rtrim(RUN_RACES_LOCAL_PATH, "/\\");
$runRacesBaseUrl = rtrim(RUN_RACES_BASE_URL, "/");

$events = array();
$localFiles = array();


/* ============================================================
   STEP 1: CHECK LOCAL RUN_RACES FIRST
   ============================================================ */

if (
    $localRunRacesPath !== "" &&
    is_dir($localRunRacesPath)
) {

    $patterns = array(
        $localRunRacesPath . DIRECTORY_SEPARATOR . "Mock_Race_Result_*.html",
        $localRunRacesPath . DIRECTORY_SEPARATOR . "Mock_Race_Result_*.htm"
    );

    foreach ($patterns as $pattern) {

        $matchedFiles = glob($pattern);

        if ($matchedFiles === false) {
            continue;
        }

        foreach ($matchedFiles as $fullPath) {

            if (!is_file($fullPath)) {
                continue;
            }

            $fileName = basename($fullPath);

            if (!preg_match(
                '/^Mock_Race_Result_(\d{4}-\d{2}-\d{2})\.(html|htm)$/i',
                $fileName,
                $matches
            )) {
                continue;
            }

            $fileDate = $matches[1];

            if (
                $year !== "" &&
                $month !== "" &&
                (
                    $fileDate < $startDate ||
                    $fileDate >= $endDate
                )
            ) {
                continue;
            }

            /*
             * Mark this filename as already available locally.
             * If DB contains the same file, local version wins.
             */
            $localFiles[$fileName] = true;

            $events[] = array(
                "id" => "local_" . md5($fileName),

                "title" => "Mock Race Result",

                "start" => $fileDate,

                "url" =>
                    $runRacesBaseUrl .
                    "/" .
                    rawurlencode($fileName),

                "raceNo" => null,

                "type" => "mock_race_result",

                "raceType" => "post_race",

                "source" => "run_races"
            );
        }
    }
}


/* ============================================================
   STEP 2: CHECK DATABASE FOR FILES NOT FOUND LOCALLY
   ============================================================ */

$sql = "
    SELECT
        id,
        `date`,
        `type`,
        `race_type`,
        file_url
    FROM run_race_details
    WHERE `type` = ?
      AND `race_type` = ?
";

if ($year !== "" && $month !== "") {

    $sql .= "
        AND `date` >= ?
        AND `date` < ?
    ";
}

$sql .= "
    ORDER BY `date` ASC, id ASC
";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    sendResponse(false, array(), "Unable to prepare database query.", 500);
}

$type = "mock_race_result";
$raceType = "post_race";

if ($year !== "" && $month !== "") {

    $stmt->bind_param(
        "ssss",
        $type,
        $raceType,
        $startDate,
        $endDate
    );

} else {

    $stmt->bind_param(
        "ss",
        $type,
        $raceType
    );
}

if (!$stmt->execute()) {

    $error = $stmt->error;
    $stmt->close();

    sendResponse(
        false,
        array(),
        "Database query failed: " . $error,
        500
    );
}

$result = $stmt->get_result();


/* ============================================================
   DB/S3 FALLBACK
   ============================================================ */

while ($row = $result->fetch_assoc()) {

    $date = isset($row["date"])
        ? trim($row["date"])
        : "";

    $fileUrl = isset($row["file_url"])
        ? trim($row["file_url"])
        : "";

    if ($date === "" || $fileUrl === "") {
        continue;
    }

    $formattedDate = date(
        "Y-m-d",
        strtotime($date)
    );

    /*
     * Extract only filename from the S3 URL.
     * The complete S3 URL is NOT returned.
     */
    $filePath = parse_url(
        $fileUrl,
        PHP_URL_PATH
    );

    $fileName = basename($filePath);

    if ($fileName === "") {
        continue;
    }

    /*
     * LOCAL FILE HAS PRIORITY.
     *
     * If the same filename exists in run_races,
     * do not return the DB/S3 fallback.
     */
    if (isset($localFiles[$fileName])) {
        continue;
    }

    /*
     * File was not found locally.
     *
     * Return THIS API URL instead of S3 URL.
     * When clicked, open=1 makes this same file
     * fetch the S3 HTML internally.
     */
    $hiddenS3Url =
        $apiOpenBaseUrl .
        "?open=1&id=" .
        (int)$row["id"];

    $events[] = array(
        "id" => (int)$row["id"],

        "title" => "Mock Race Result",

        "start" => $formattedDate,

        "url" => $hiddenS3Url,

        "raceNo" => null,

        "type" => $row["type"],

        "raceType" => $row["race_type"],

        "source" => "run_race_details"
    );
}

$stmt->close();


/* ============================================================
   SORT EVENTS BY DATE
   ============================================================ */

usort(
    $events,
    function ($a, $b) {

        $dateCompare = strcmp(
            $a["start"],
            $b["start"]
        );

        if ($dateCompare !== 0) {
            return $dateCompare;
        }

        return strcmp(
            (string)$a["id"],
            (string)$b["id"]
        );
    }
);


/* ============================================================
   RESPONSE
   ============================================================ */

sendResponse(
    true,
    $events,
    null,
    200
);
