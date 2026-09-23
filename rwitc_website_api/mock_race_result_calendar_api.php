<?php

/**
 * RWITC MOCK RACE RESULT CALENDAR API
 *
 * Flow:
 *   1. Local run_races is checked first.
 *   2. Local .html/.htm files are returned directly.
 *   3. If a file is not local, run_race_details is checked.
 *   4. S3 fallback is read through s3_set_url.php.
 *   5. S3 URL is never exposed to the browser.
 *
 * Multiple Mock Race Results on the same date are returned as
 * separate calendar events:
 *
 *   Mock Race 1
 *   Mock Race 2
 *   Mock Race 3
 *
 * New files:
 *   Mock_Race_Result_1_YYYY-MM-DD.html
 *   Mock_Race_Result_2_YYYY-MM-DD.html
 *
 * Legacy file:
 *   Mock_Race_Result_YYYY-MM-DD.html
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

/* ============================================================
   RESPONSE
   ============================================================ */

function sendResponse($success, $data = array(), $error = null, $statusCode = 200)
{
    http_response_code($statusCode);

    echo json_encode(
        array(
            "success" => (bool)$success,
            "data"    => $data,
            "error"   => $error
        ),
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    exit;
}

/* ============================================================
   S3 HELPERS
   ============================================================ */

function mockRaceS3KeyFromUrl($fileUrl)
{
    $fileUrl = trim((string)$fileUrl);

    if ($fileUrl === "") {
        return "";
    }

    if (preg_match("/^https?:\/\//i", $fileUrl)) {
        $path = parse_url($fileUrl, PHP_URL_PATH);
        $fileUrl = ($path !== null) ? $path : "";
    }

    return ltrim(
        rawurldecode($fileUrl),
        "/"
    );
}

function getS3SetUrlApi()
{
    if (
        defined("S3_SET_URL_API_URL") &&
        S3_SET_URL_API_URL !== ""
    ) {
        return rtrim(
            S3_SET_URL_API_URL,
            "/"
        );
    }

    $scheme = "https";

    if (
        isset($_SERVER["HTTPS"]) &&
        $_SERVER["HTTPS"] !== "off"
    ) {
        $scheme = "https";
    } elseif (
        isset($_SERVER["REQUEST_SCHEME"]) &&
        $_SERVER["REQUEST_SCHEME"] !== ""
    ) {
        $scheme = $_SERVER["REQUEST_SCHEME"];
    }

    $host = $_SERVER["HTTP_HOST"] ?? "";

    if ($host === "") {
        return "";
    }

    $scriptDir = dirname(
        $_SERVER["SCRIPT_NAME"] ?? ""
    );

    if (
        $scriptDir === "." ||
        $scriptDir === "/"
    ) {
        $scriptDir = "";
    }

    return $scheme .
        "://" .
        $host .
        $scriptDir .
        "/s3_set_url.php";
}

function readMockRaceHtmlFromS3($fileUrl)
{
    $s3Key =
        mockRaceS3KeyFromUrl(
            $fileUrl
        );

    if (
        $s3Key === "" ||
        strpos($s3Key, "run_races/") !== 0 ||
        strtolower(
            pathinfo(
                $s3Key,
                PATHINFO_EXTENSION
            )
        ) !== "html"
    ) {
        throw new Exception(
            "Invalid Mock Race Result S3 HTML key."
        );
    }

    $helperUrl =
        getS3SetUrlApi();

    if ($helperUrl === "") {
        throw new Exception(
            "S3 HTML helper URL is not configured."
        );
    }

    $requestUrl =
        $helperUrl .
        "?key=" .
        rawurlencode(
            $s3Key
        );

    $ch =
        curl_init(
            $requestUrl
        );

    if ($ch === false) {
        throw new Exception(
            "Unable to initialize S3 HTML helper cURL."
        );
    }

    curl_setopt(
        $ch,
        CURLOPT_RETURNTRANSFER,
        true
    );

    curl_setopt(
        $ch,
        CURLOPT_FOLLOWLOCATION,
        true
    );

    curl_setopt(
        $ch,
        CURLOPT_CONNECTTIMEOUT,
        10
    );

    curl_setopt(
        $ch,
        CURLOPT_TIMEOUT,
        30
    );

    curl_setopt(
        $ch,
        CURLOPT_SSL_VERIFYPEER,
        true
    );

    curl_setopt(
        $ch,
        CURLOPT_SSL_VERIFYHOST,
        2
    );

    curl_setopt(
        $ch,
        CURLOPT_USERAGENT,
        "RWITC Mock Race Result S3 Reader"
    );

    $content =
        curl_exec($ch);

    if ($content === false) {

        $curlError =
            curl_error($ch);

        curl_close($ch);

        throw new Exception(
            "S3 HTML helper cURL failed: " .
            $curlError
        );
    }

    $httpCode =
        curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );

    curl_close($ch);

    if (
        $httpCode < 200 ||
        $httpCode >= 300
    ) {
        throw new Exception(
            "S3 HTML helper returned HTTP " .
            $httpCode
        );
    }

    if (
        $content === "" ||
        trim((string)$content) === ""
    ) {
        throw new Exception(
            "S3 HTML helper returned empty content."
        );
    }

    return $content;
}

/* ============================================================
   FILENAME HELPERS
   ============================================================ */

function getMockRaceFilenameInfo($filename)
{
    $name =
        basename(
            (string)$filename
        );

    /*
     * New file:
     * Mock_Race_Result_1_2026-09-22.html
     */
    if (preg_match(
        '/^Mock_Race_Result_(\d+)_(\d{4}-\d{2}-\d{2})\.html$/i',
        $name,
        $matches
    )) {
        return array(
            "sequence" => (int)$matches[1],
            "date"     => $matches[2]
        );
    }

    /*
     * Legacy file:
     * Mock_Race_Result_2026-09-22.html
     */
    if (preg_match(
        '/^Mock_Race_Result_(\d{4}-\d{2}-\d{2})\.(html|htm)$/i',
        $name,
        $matches
    )) {
        return array(
            "sequence" => null,
            "date"     => $matches[1]
        );
    }

    return null;
}

function nextMockRaceSequence(array &$usedSequences, $date)
{
    if (!isset($usedSequences[$date])) {
        $usedSequences[$date] = array();
    }

    $sequence = 1;

    while (
        isset(
            $usedSequences[$date][$sequence]
        )
    ) {
        $sequence++;
    }

    $usedSequences[$date][$sequence] = true;

    return $sequence;
}

/* ============================================================
   DATABASE
   ============================================================ */

if (
    !isset($conn) ||
    !($conn instanceof mysqli)
) {
    sendResponse(
        false,
        array(),
        "Database connection is not available.",
        500
    );
}

$conn->set_charset("utf8mb4");

/* ============================================================
   OPEN MODE
   ?open=1&id=<run_race_details.id>
   ============================================================ */

$open =
    isset($_GET["open"])
    ? (int)$_GET["open"]
    : 0;

if ($open === 1) {

    $id =
        isset($_GET["id"])
        ? (int)$_GET["id"]
        : 0;

    if ($id <= 0) {

        http_response_code(400);
        header(
            "Content-Type: text/plain; charset=UTF-8"
        );

        echo "Invalid Mock Race Result ID.";
        exit;
    }

    $openSql = "
        SELECT
            id,
            file_url
        FROM run_race_details
        WHERE id = ?
          AND `type` = 'mock_race_result'
          AND `race_type` IN ('post_race', 'result')
        LIMIT 1
    ";

    $openStmt =
        $conn->prepare(
            $openSql
        );

    if ($openStmt === false) {

        http_response_code(500);
        header(
            "Content-Type: text/plain; charset=UTF-8"
        );

        echo "Unable to prepare database query.";
        exit;
    }

    $openStmt->bind_param(
        "i",
        $id
    );

    if (!$openStmt->execute()) {

        $openStmt->close();

        http_response_code(500);
        header(
            "Content-Type: text/plain; charset=UTF-8"
        );

        echo "Database query failed.";
        exit;
    }

    $openResult =
        $openStmt->get_result();

    $openRow =
        $openResult
        ? $openResult->fetch_assoc()
        : null;

    $openStmt->close();

    if (!$openRow) {

        http_response_code(404);
        header(
            "Content-Type: text/plain; charset=UTF-8"
        );

        echo "Mock Race Result not found.";
        exit;
    }

    $fileUrl =
        trim(
            (string)(
                $openRow["file_url"] ?? ""
            )
        );

    if ($fileUrl === "") {

        http_response_code(404);
        header(
            "Content-Type: text/plain; charset=UTF-8"
        );

        echo "Mock Race Result file URL is empty.";
        exit;
    }

    /*
     * Local first.
     */
    $filePath =
        parse_url(
            $fileUrl,
            PHP_URL_PATH
        );

    $fileName =
        basename(
            (string)$filePath
        );

    if ($fileName !== "") {

        $localFile =
            rtrim(
                RUN_RACES_LOCAL_PATH,
                "/\\"
            ) .
            DIRECTORY_SEPARATOR .
            $fileName;

        if (is_file($localFile)) {

            $html =
                file_get_contents(
                    $localFile
                );

            if ($html === false) {

                http_response_code(500);
                header(
                    "Content-Type: text/plain; charset=UTF-8"
                );

                echo "Unable to read local Mock Race Result file.";
                exit;
            }

            header(
                "Content-Type: text/html; charset=UTF-8"
            );

            header(
                "Cache-Control: no-store"
            );

            echo $html;
            exit;
        }
    }

    /*
     * S3 fallback through s3_set_url.php.
     */
    try {

        $html =
            readMockRaceHtmlFromS3(
                $fileUrl
            );

        header(
            "Content-Type: text/html; charset=UTF-8"
        );

        header(
            "Cache-Control: no-store"
        );

        echo $html;
        exit;

    } catch (Throwable $error) {

        error_log(
            "MOCK_RACE_RESULT_OPEN_ERROR | " .
            $error->getMessage()
        );

        http_response_code(500);
        header(
            "Content-Type: text/plain; charset=UTF-8"
        );

        echo "Unable to load Mock Race Result file.";
        exit;
    }
}

/* ============================================================
   YEAR / MONTH
   ============================================================ */

$year =
    isset($_GET["year"])
    ? trim($_GET["year"])
    : "";

$month =
    isset($_GET["month"])
    ? trim($_GET["month"])
    : "";

if (
    $year !== "" &&
    !preg_match(
        '/^\d{4}$/',
        $year
    )
) {
    sendResponse(
        false,
        array(),
        "Invalid year. Expected YYYY.",
        400
    );
}

if ($month !== "") {

    if (
        !preg_match(
            '/^(0?[1-9]|1[0-2])$/',
            $month
        )
    ) {
        sendResponse(
            false,
            array(),
            "Invalid month. Expected 1-12.",
            400
        );
    }

    $month =
        str_pad(
            $month,
            2,
            "0",
            STR_PAD_LEFT
        );
}

/* ============================================================
   DATE RANGE
   ============================================================ */

$startDate = "";
$endDate = "";

if (
    $year !== "" &&
    $month !== ""
) {

    $startDate =
        $year .
        "-" .
        $month .
        "-01";

    $endDate =
        date(
            "Y-m-d",
            strtotime(
                $startDate .
                " +1 month"
            )
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

$host =
    $_SERVER["HTTP_HOST"] ?? "";

$apiBaseUrl =
    $scheme .
    $host .
    rtrim(
        str_replace(
            "\\",
            "/",
            dirname(
                $_SERVER["SCRIPT_NAME"] ?? ""
            )
        ),
        "/"
    );

$apiOpenBaseUrl =
    $apiBaseUrl .
    "/" .
    basename(
        $_SERVER["SCRIPT_NAME"]
    );

/* ============================================================
   LOCAL RUN_RACES
   ============================================================ */

$localRunRacesPath =
    rtrim(
        RUN_RACES_LOCAL_PATH,
        "/\\"
    );

$runRacesBaseUrl =
    rtrim(
        RUN_RACES_BASE_URL,
        "/"
    );

$events = array();
$localFiles = array();
$usedSequences = array();

/* ============================================================
   STEP 1: LOCAL FILES FIRST
   ============================================================ */

if (
    $localRunRacesPath !== "" &&
    is_dir($localRunRacesPath)
) {

    $patterns = array(
        $localRunRacesPath .
            DIRECTORY_SEPARATOR .
            "Mock_Race_Result_*.html",

        $localRunRacesPath .
            DIRECTORY_SEPARATOR .
            "Mock_Race_Result_*.htm"
    );

    foreach ($patterns as $pattern) {

        $matchedFiles =
            glob($pattern);

        if ($matchedFiles === false) {
            continue;
        }

        foreach ($matchedFiles as $fullPath) {

            if (!is_file($fullPath)) {
                continue;
            }

            $fileName =
                basename($fullPath);

            $fileInfo =
                getMockRaceFilenameInfo(
                    $fileName
                );

            if ($fileInfo === null) {
                continue;
            }

            $fileDate =
                $fileInfo["date"];

            $sequence =
                $fileInfo["sequence"];

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
             * Legacy date-only file should not be shown when new
             * numbered files already exist for that date.
             */
            if ($sequence === null) {

                $numberedPattern =
                    $localRunRacesPath .
                    DIRECTORY_SEPARATOR .
                    "Mock_Race_Result_*_" .
                    $fileDate .
                    ".html";

                $numberedFiles =
                    glob(
                        $numberedPattern
                    );

                if (
                    $numberedFiles !== false &&
                    !empty($numberedFiles)
                ) {
                    continue;
                }

                $sequence =
                    nextMockRaceSequence(
                        $usedSequences,
                        $fileDate
                    );

            } else {

                if (
                    !isset(
                        $usedSequences[$fileDate]
                    )
                ) {
                    $usedSequences[$fileDate] =
                        array();
                }

                if (
                    isset(
                        $usedSequences[$fileDate][$sequence]
                    )
                ) {
                    continue;
                }

                $usedSequences[$fileDate][$sequence] =
                    true;
            }

            $localFiles[$fileName] = true;

            $events[] = array(
                "id" =>
                    "local_" .
                    md5($fileName),

                "title" =>
                    "Mock Race " .
                    $sequence,

                "start" =>
                    $fileDate,

                "url" =>
                    $runRacesBaseUrl .
                    "/" .
                    rawurlencode(
                        $fileName
                    ),

                "raceNo" => null,

                "type" =>
                    "mock_race_result",

                "raceType" =>
                    "post_race",

                "mockSequence" =>
                    $sequence,

                "source" =>
                    "run_races"
            );
        }
    }
}

/* ============================================================
   STEP 2: DATABASE / S3 FALLBACK
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
      AND `race_type` IN (?, ?)
";

if (
    $year !== "" &&
    $month !== ""
) {
    $sql .= "
        AND `date` >= ?
        AND `date` < ?
    ";
}

$sql .= "
    ORDER BY `date` ASC, id ASC
";

$stmt =
    $conn->prepare(
        $sql
    );

if ($stmt === false) {
    sendResponse(
        false,
        array(),
        "Unable to prepare database query.",
        500
    );
}

$type =
    "mock_race_result";

$raceTypePost =
    "post_race";

$raceTypeLegacy =
    "result";

if (
    $year !== "" &&
    $month !== ""
) {

    $stmt->bind_param(
        "sssss",
        $type,
        $raceTypePost,
        $raceTypeLegacy,
        $startDate,
        $endDate
    );

} else {

    $stmt->bind_param(
        "sss",
        $type,
        $raceTypePost,
        $raceTypeLegacy
    );
}

if (!$stmt->execute()) {

    $error =
        $stmt->error;

    $stmt->close();

    sendResponse(
        false,
        array(),
        "Database query failed: " .
            $error,
        500
    );
}

$result =
    $stmt->get_result();

/*
 * Read DB rows first so we can identify dates that already have the
 * new numbered filename format. This prevents an old legacy
 * Mock_Race_Result_YYYY-MM-DD.html row from appearing together with
 * Mock_Race_Result_1_YYYY-MM-DD.html, Mock_Race_Result_2_YYYY-MM-DD.html.
 */
$dbRows = array();
$dbNumberedDates = array();

while (
    $row =
        $result->fetch_assoc()
) {
    $dbRows[] = $row;

    $dbFileUrl = isset($row["file_url"])
        ? trim($row["file_url"])
        : "";

    if ($dbFileUrl === "") {
        continue;
    }

    $dbFilePath =
        parse_url(
            $dbFileUrl,
            PHP_URL_PATH
        );

    $dbFileName =
        basename(
            (string)$dbFilePath
        );

    if ($dbFileName === "") {
        continue;
    }

    $dbFileInfo =
        getMockRaceFilenameInfo(
            $dbFileName
        );

    if (
        $dbFileInfo !== null &&
        $dbFileInfo["sequence"] !== null
    ) {
        $dbNumberedDates[
            $dbFileInfo["date"]
        ] = true;
    }
}

/* ============================================================
   DB/S3 EVENTS
   ============================================================ */

foreach ($dbRows as $row) {

    $date =
        isset($row["date"])
        ? trim($row["date"])
        : "";

    $fileUrl =
        isset($row["file_url"])
        ? trim($row["file_url"])
        : "";

    if (
        $date === "" ||
        $fileUrl === ""
    ) {
        continue;
    }

    $formattedDate =
        date(
            "Y-m-d",
            strtotime($date)
        );

    $filePath =
        parse_url(
            $fileUrl,
            PHP_URL_PATH
        );

    $fileName =
        basename(
            (string)$filePath
        );

    if ($fileName === "") {
        continue;
    }

    /*
     * Local file always wins when the same filename exists.
     */
    if (
        isset(
            $localFiles[$fileName]
        )
    ) {
        continue;
    }

    $fileInfo =
        getMockRaceFilenameInfo(
            $fileName
        );

    $sequence =
        $fileInfo !== null
        ? $fileInfo["sequence"]
        : null;

    if (
        $fileInfo !== null &&
        $fileInfo["date"] !== $formattedDate
    ) {
        $sequence = null;
    }

    /*
     * When a date has new numbered DB files, ignore the old
     * date-only DB record for that same date.
     */
    if (
        $sequence === null &&
        isset($dbNumberedDates[$formattedDate])
    ) {
        continue;
    }

    if ($sequence === null) {

        $sequence =
            nextMockRaceSequence(
                $usedSequences,
                $formattedDate
            );

    } else {

        if (
            !isset(
                $usedSequences[$formattedDate]
            )
        ) {
            $usedSequences[$formattedDate] =
                array();
        }

        if (
            isset(
                $usedSequences[$formattedDate][$sequence]
            )
        ) {
            continue;
        }

        $usedSequences[$formattedDate][$sequence] =
            true;
    }

    /*
     * Hide S3 URL from the frontend.
     */
    $hiddenS3Url =
        $apiOpenBaseUrl .
        "?open=1&id=" .
        (int)$row["id"];

    $events[] = array(
        "id" =>
            (int)$row["id"],

        "title" =>
            "Mock Race " .
            $sequence,

        "start" =>
            $formattedDate,

        "url" =>
            $hiddenS3Url,

        "raceNo" => null,

        "type" =>
            "mock_race_result",

        "raceType" =>
            "post_race",

        "mockSequence" =>
            $sequence,

        "source" =>
            "run_race_details"
    );
}

$stmt->close();

/* ============================================================
   SORT
   ============================================================ */

usort(
    $events,
    function ($a, $b) {

        $dateCompare =
            strcmp(
                $a["start"],
                $b["start"]
            );

        if ($dateCompare !== 0) {
            return $dateCompare;
        }

        $sequenceA =
            (int)(
                $a["mockSequence"] ??
                0
            );

        $sequenceB =
            (int)(
                $b["mockSequence"] ??
                0
            );

        if ($sequenceA !== $sequenceB) {
            return $sequenceA <=> $sequenceB;
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
