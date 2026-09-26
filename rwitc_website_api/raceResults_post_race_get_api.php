<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

// Load database and security
require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/ApiSecurity.php";
require_once __DIR__ . "/config/run_races_config.php";

// --------------------------------------------------
// HELPERS
// --------------------------------------------------

function raceResultJsonResponse($success, $data = [], $message = null, $status = 200)
{
    http_response_code($status);

    echo json_encode([
        "success" => (bool) $success,
        "data"    => $data,
        "message" => $message,
    ]);

    exit;
}

function raceResultValidType($value)
{
    return is_string($value)
        && $value !== ""
        && preg_match("/^[A-Za-z0-9_-]+$/", $value);
}

function raceResultValidRaceType($value)
{
    return is_string($value)
        && $value !== ""
        && preg_match("/^[A-Za-z0-9_-]+$/", $value);
}

// --------------------------------------------------
// EXISTING S3 HTML READER
// Same flow as Handicaps / Acceptance / Declarations / Race Card
// --------------------------------------------------

function raceResultS3Key($fileUrl)
{
    $fileUrl = trim((string) $fileUrl);

    if ($fileUrl === "") {
        return "";
    }

    if (preg_match("/^https?:\/\//i", $fileUrl)) {
        $path = parse_url($fileUrl, PHP_URL_PATH);
        $fileUrl = ($path !== null) ? $path : "";
    }

    return ltrim(rawurldecode($fileUrl), "/");
}

function getS3BucketGetApiUrl()
{
    if (
        defined("S3_BUCKET_GET_API_URL") &&
        S3_BUCKET_GET_API_URL !== ""
    ) {
        return rtrim(S3_BUCKET_GET_API_URL, "/");
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

    $scriptDir = dirname($_SERVER["SCRIPT_NAME"] ?? "");

    if ($scriptDir === "." || $scriptDir === "/") {
        $scriptDir = "";
    }

    return $scheme . "://" . $host . $scriptDir . "/s3_set_url.php";
}

function readHtmlFromS3BucketApi($fileUrl)
{
    $s3Key = raceResultS3Key($fileUrl);

    if (
        $s3Key === "" ||
        strpos($s3Key, "run_races/") !== 0 ||
        strtolower(pathinfo($s3Key, PATHINFO_EXTENSION)) !== "html"
    ) {
        throw new Exception(
            "Invalid race result S3 file key"
        );
    }

    $helperUrl = getS3BucketGetApiUrl();

    if ($helperUrl === "") {
        throw new Exception(
            "S3 HTML helper URL is not configured"
        );
    }

    $requestUrl =
        $helperUrl .
        "?key=" .
        rawurlencode($s3Key);

    $ch = curl_init($requestUrl);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt(
        $ch,
        CURLOPT_USERAGENT,
        "RWITC Race Result S3 Reader"
    );

    $content = curl_exec($ch);

    if ($content === false) {
        $curlError = curl_error($ch);
        curl_close($ch);

        throw new Exception(
            "S3 HTML helper cURL failed: " . $curlError
        );
    }

    $httpCode = curl_getinfo(
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
        trim((string) $content) === ""
    ) {
        throw new Exception(
            "S3 HTML helper returned empty content"
        );
    }

    return $content;
}

function raceResultInjectCss($html)
{
    $style = <<<HTML
<style>
    * { box-sizing: border-box; }
    html, body { overflow-x: hidden !important; width: 100% !important; }
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 12px;
    }
    table {
        width: 100% !important;
        max-width: 100% !important;
        border-collapse: collapse;
    }
    td, th {
        word-break: break-word;
        padding: 10px 12px !important;
        border: 1px solid #cccccc;
    }
    th {
        color: #000 !important;
        font-weight: 700;
        text-align: center;
        background: #fff;
    }
    td {
        color: #222 !important;
        background: #fff;
    }
    span, a {
        text-decoration: none;
        color: #333333;
    }
    .download {
        display: none !important;
    }
</style>
HTML;

    if (preg_match("/<head\\b[^>]*>/i", $html)) {
        return preg_replace(
            "/(<head\\b[^>]*>)/i",
            "$1" . $style,
            $html,
            1
        );
    }

    return "<!DOCTYPE html><html><head><meta charset=\"UTF-8\">" .
        $style .
        "</head><body>" .
        $html .
        "</body></html>";
}

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
    "cache_ttl"      => 120,
    "cache_dir"      => __DIR__ . "/cache",
    "rate_limit_dir" => __DIR__ . "/rate_limits",
    "api_tag"        => "race_results_get"
]);

// --------------------------------------------------
// RATE LIMIT
// --------------------------------------------------

if (!$security->gate()) {
    exit;
}

// --------------------------------------------------
// POST = ERP PUSH TO WEBSITE
// --------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    try {

        $date = isset($_POST["date"]) ? trim($_POST["date"]) : "";
        $type = isset($_POST["type"]) ? trim($_POST["type"]) : "";
        $raceType = isset($_POST["race_type"])
            ? trim($_POST["race_type"])
            : "";

        $htmlFile = isset($_POST["html_file"])
            ? trim($_POST["html_file"])
            : "";

        $fileUrl = isset($_POST["file_url"])
            ? trim($_POST["file_url"])
            : "";

        if (
            $date === ""
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
            || strtotime($date) === false
        ) {
            raceResultJsonResponse(
                false,
                [],
                "A valid date parameter (YYYY-MM-DD) is required",
                400
            );
        }

        if (!raceResultValidType($type)) {
            raceResultJsonResponse(
                false,
                [],
                "Invalid type",
                400
            );
        }

        if (!raceResultValidRaceType($raceType)) {
            raceResultJsonResponse(
                false,
                [],
                "Invalid race_type",
                400
            );
        }

        if (
            $htmlFile === ""
            || strpos($htmlFile, "run_races/") !== 0
            || !preg_match("/\\.html$/i", $htmlFile)
        ) {
            raceResultJsonResponse(
                false,
                [],
                "A valid run_races .html file is required",
                400
            );
        }

        // Store the actual S3 URL when available.
        // If the uploader did not return a URL, store the S3 key.
        $storedFileUrl = $fileUrl !== ""
            ? $fileUrl
            : $htmlFile;

        $sql = "
            SELECT id
            FROM run_race_details
            WHERE `date` = ?
              AND `type` = ?
              AND `race_type` = ?
            ORDER BY id DESC
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            throw new Exception($conn->error);
        }

        $stmt->bind_param(
            "sss",
            $date,
            $type,
            $raceType
        );

        if (!$stmt->execute()) {
            throw new Exception($conn->error);
        }

        $result = $stmt->get_result();
        $existing = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if ($existing && isset($existing["id"])) {

            $stmt = $conn->prepare("
                UPDATE run_race_details
                SET file_url = ?
                WHERE id = ?
            ");

            if ($stmt === false) {
                throw new Exception($conn->error);
            }

            $stmt->bind_param(
                "si",
                $storedFileUrl,
                $existing["id"]
            );
        } else {

            $stmt = $conn->prepare("
                INSERT INTO run_race_details
                    (`date`, `type`, `file_url`, `race_type`)
                VALUES (?, ?, ?, ?)
            ");

            if ($stmt === false) {
                throw new Exception($conn->error);
            }

            $stmt->bind_param(
                "ssss",
                $date,
                $type,
                $storedFileUrl,
                $raceType
            );
        }

        if (!$stmt->execute()) {
            throw new Exception($conn->error);
        }

        $stmt->close();

        if (is_resource($handle)) {
            $security->logLine(
                "RACE_RESULT_PUSH_SUCCESS | date={$date} | type={$type} | race_type={$raceType} | file={$htmlFile}"
            );
        }

        raceResultJsonResponse(
            true,
            [
                "date"      => $date,
                "type"      => $type,
                "race_type" => $raceType,
                "html_file" => $htmlFile,
                "file_url"  => $storedFileUrl,
            ],
            "Race result HTML file registered successfully"
        );
    } catch (Throwable $error) {

        $security->logLine(
            "RACE_RESULT_PUSH_ERROR | " . $error->getMessage()
        );

        raceResultJsonResponse(
            false,
            [],
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
}

// --------------------------------------------------
// GET
// --------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] !== "GET") {

    $security->respondError(
        "Method not allowed",
        405
    );

    exit;
}

// --------------------------------------------------
// VALIDATE INPUT
// --------------------------------------------------

$date = isset($_GET["date"]) ? trim($_GET["date"]) : "";
$searaceno = isset($_GET["raceno"]) ? (int) $_GET["raceno"] : 0;

$type = isset($_GET["type"])
    ? trim($_GET["type"])
    : "";

/*
 * The website page historically sends "raceResults".
 * The registered document type in run_race_details is "race_result".
 * Keep the database/S3 flow canonical as race_result.
 */
if ($type === "raceResults") {
    $type = "race_result";
}

$raceType = isset($_GET["race_type"])
    ? trim($_GET["race_type"])
    : "";

if ($searaceno <= 0) {
    if (
        $date === ""
        || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
        || strtotime($date) === false
    ) {
        $security->respondError(
            "A valid date parameter (YYYY-MM-DD), or a raceno parameter, is required",
            400
        );
        exit;
    }
}

/*
 * Resolve race_type dynamically from run_race_details when the frontend
 * sends the document type but does not send race_type.
 *
 * This keeps the document API free of hardcoded race_type defaults.
 */
if ($raceType === "" && $type !== "" && $date !== "") {

    $stmt = $conn->prepare("
        SELECT race_type
        FROM run_race_details
        WHERE `date` = ?
          AND `type` IN (?, 'race_results')
          AND race_type IS NOT NULL
          AND race_type <> ''
        ORDER BY id DESC
        LIMIT 1
    ");

    if ($stmt === false) {
        $security->respondError("Unable to resolve race_type", 500);
        exit;
    }

    $stmt->bind_param(
        "ss",
        $date,
        $type
    );

    if (!$stmt->execute()) {
        $stmt->close();
        $security->respondError("Unable to resolve race_type", 500);
        exit;
    }

    $result = $stmt->get_result();

    if ($result && ($row = $result->fetch_assoc())) {
        $raceType = trim((string)($row["race_type"] ?? ""));
    }

    $stmt->close();
}

if ($date !== "" && $date > "2022-10-14") {

    if (!raceResultValidType($type)) {
        $security->respondError("Invalid type", 400);
        exit;
    }

    if (!raceResultValidRaceType($raceType)) {
        $security->respondError("Invalid race_type", 400);
        exit;
    }
} elseif ($type !== "" && !raceResultValidType($type)) {

    $security->respondError("Invalid type", 400);
    exit;
}

// --------------------------------------------------
// POST-CUTOFF RACE RESULTS
// --------------------------------------------------

if ($date !== "" && $date > "2022-10-14") {

    // --------------------------------------------------
    // DOWNLOAD / OPEN HTML
    // --------------------------------------------------

    if (
        isset($_GET["download"])
        && $_GET["download"] === "1"
    ) {

        try {

            $htmlFile = rtrim(
                RUN_RACES_LOCAL_PATH,
                "/\\"
            ) . "/Race_results_" . $date . ".html";

            $htmlContent = false;

            if (is_file($htmlFile)) {
                $htmlContent = file_get_contents($htmlFile);

                if ($htmlContent === false) {
                    throw new Exception(
                        "Unable to read local race result HTML file"
                    );
                }
            } else {

                $stmt = $conn->prepare("
                    SELECT file_url
                    FROM run_race_details
                    WHERE `date` = ?
                      AND `type` IN ('race_result', 'race_results')
                      AND `race_type` = ?
                      AND file_url IS NOT NULL
                      AND file_url <> ''
                    ORDER BY id DESC
                    LIMIT 1
                ");

                if ($stmt === false) {
                    throw new Exception($conn->error);
                }

                $stmt->bind_param(
                    "ss",
                    $date,
                    $raceType
                );

                if (!$stmt->execute()) {
                    throw new Exception($conn->error);
                }

                $result = $stmt->get_result();

                if (!$result || $result->num_rows === 0) {
                    $stmt->close();

                    http_response_code(404);
                    header("Content-Type: text/plain; charset=UTF-8");
                    echo "Race result HTML file not found.";
                    exit;
                }

                $row = $result->fetch_assoc();
                $stmt->close();

                $htmlContent =
                    readHtmlFromS3BucketApi(
                        $row["file_url"] ?? ""
                    );
            }

            $downloadContent = raceResultInjectCss($htmlContent);

            header("Content-Type: text/html; charset=UTF-8");
            header(
                'Content-Disposition: inline; filename="Race_results_' .
                    $date .
                    '.html"'
            );

            echo $downloadContent;
            exit;
        } catch (Throwable $error) {

            $security->logLine(
                "RACE_RESULTS_DOWNLOAD_ERROR | " .
                    $error->getMessage()
            );

            http_response_code(500);
            header("Content-Type: text/plain; charset=UTF-8");
            echo "Unable to open race result HTML file.";
            exit;
        }
    }

    // --------------------------------------------------
    // NORMAL HTML RESPONSE
    // Same local-first -> DB -> s3_set_url.php flow.
    // HTML archive mode is intentionally not cached.
    // --------------------------------------------------

    try {

        // 1) Local HTML first
        $htmlFile = rtrim(
            RUN_RACES_LOCAL_PATH,
            "/\\"
        ) . "/Race_results_" . $date . ".html";

        $htmlContent = false;
        $source = "";

        if (is_file($htmlFile)) {

            $source = "LOCAL_RUN_RACES";
            $htmlContent = file_get_contents($htmlFile);

            if ($htmlContent === false) {
                throw new Exception(
                    "Unable to read local race result HTML file"
                );
            }
        }

        // 2) DB -> private S3 fallback
        if ($htmlContent === false) {

            $source = "DB_S3";

            $stmt = $conn->prepare("
                SELECT file_url
                FROM run_race_details
                WHERE `date` = ?
                  AND `type` IN ('race_result', 'race_results')
                  AND `race_type` = ?
                  AND file_url IS NOT NULL
                  AND file_url <> ''
                ORDER BY id DESC
                LIMIT 1
            ");

            if ($stmt === false) {
                throw new Exception($conn->error);
            }

            $stmt->bind_param(
                "ss",
                $date,
                $raceType
            );

            if (!$stmt->execute()) {
                throw new Exception($conn->error);
            }

            $result = $stmt->get_result();

            if (!$result || $result->num_rows === 0) {

                $stmt->close();

                $security->respondError(
                    "No race results data found for this date",
                    404
                );

                exit;
            }

            $row = $result->fetch_assoc();
            $stmt->close();

            $htmlContent =
                readHtmlFromS3BucketApi(
                    $row["file_url"] ?? ""
                );
        }

        $downloadAvailable = true;
        $downloadFile = null;

        $baseParts = parse_url(RUN_RACES_BASE_URL);
        $origin = "";

        if (isset($baseParts["scheme"]) && isset($baseParts["host"])) {
            $origin = $baseParts["scheme"] . "://" . $baseParts["host"]
                . (isset($baseParts["port"]) ? ":" . $baseParts["port"] : "");
        }

        $downloadFile = $origin
            . $_SERVER["SCRIPT_NAME"]
            . "?date=" . urlencode($date)
            . "&type=" . urlencode($type)
            . "&race_type=" . urlencode($raceType)
            . "&download=1";

        $response = [
            "found"              => true,
            "mode"               => "html",
            "html"               => $htmlContent,
            "date"               => $date,
            "type"               => $type,
            "race_type"          => $raceType,
            "source"             => $source,
            "download_file"      => $downloadFile,
            "download_available" => true
        ];

        $security->respondSuccess($response);
    } catch (Throwable $error) {

        $security->logLine(
            "RACE_RESULTS_HTML_READ_ERROR | " .
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

    exit;
}

// --------------------------------------------------
// HISTORICAL DATA-CORRECTION OVERRIDES
// --------------------------------------------------
// The legacy page hardcoded a handful of one-off patches for specific
// corrupted historical race days. Centralized here instead of scattered
// through the logic below.

$CANCELLED_RACES = [
    "2014-11-23|8"  => true,
    "2015-10-11|10" => true,
];

$FORCED_VOID_RACES = [
    "2012-11-22|1" => true,
    "2013-04-13|7" => true,
];

$CARD_NO_OVERRIDES = [
    "2018-04-14|5" => "3&7-6-1-8-4-2-5",
];

$POOL_WINNER_OVERRIDES = [
    "2018-04-14|super_jackpot" => "ISINIT, ROSE GOLD, FRIEZE, ALLORA, POLYNEICES & FIREWINGS, GRAND TENTON",
    "2018-04-14|jackpot"       => "ROSE GOLD, FRIEZE, ALLORA, POLYNEICES & FIREWINGS, GRAND TENTON",
    "2018-04-14|treble"        => "ALLORA, POLYNEICES & FIREWINGS, GRAND TENTON",
];

$POOL_DIV_OVERRIDES = [
    // date => [pool => [dividend_label, dividend, ticket_label, ticket]]
    "2018-04-14" => [
        "super_jackpot_30" => ["100 & 269", "202 & 75"],
        "jackpot_30"       => ["62 & 186", "1840 & 612"],
    ],
];

// --------------------------------------------------
// HELPERS
// --------------------------------------------------

function convertDecimalToFractionString($decNo)
{
    $explodes = explode(".", $decNo);
    if (isset($explodes[1])) {
        preg_match('/(\d*)\.(\d*)/', $decNo, $matchedNos);
        $fraction = "";
        if ($matchedNos[1]) {
            $fraction = $matchedNos[1] . " ";
        }
        if ($matchedNos[2] == 25) {
            $fraction .= "1/4";
        }
        if ($matchedNos[2] == 50) {
            $fraction .= "1/2";
        }
        if ($matchedNos[2] == 75) {
            $fraction .= "3/4";
        }
        return $fraction;
    }
    return $decNo;
}

function mapPlacingCode($placing, &$nullPlacingCount, &$voidRace)
{
    $map = [
        55 => "NDS",
        56 => "NS",
        57 => "NPR",
        58 => "WD",
        59 => "BO",
        60 => "DQ",
        61 => "DNC",
        62 => "NPR",
    ];

    if ($placing == 0) {
        $nullPlacingCount++;
        return ["-", false];
    }
    if ($placing == 91) {
        $voidRace = true;
        return ["-", false];
    }
    if ($placing > 0 && $placing <= 24) {
        return [$placing, false];
    }
    if (isset($map[$placing])) {
        return [$map[$placing], true];
    }
    return [$placing, false];
}

function mapLengthLabel($lengthCode)
{
    if ($lengthCode === null || $lengthCode === "" || (float) $lengthCode == 0.0) {
        return null;
    }
    $labels = [
        20 => "DH",
        30 => "Shd",
        40 => "Hd",
        50 => "nk",
        60 => "NO",
        70 => "SN",
        80 => "LN",
        90 => "Dist",
    ];
    if (isset($labels[$lengthCode])) {
        return $labels[$lengthCode];
    }
    return convertDecimalToFractionString($lengthCode);
}

function jockeyAllowance($category)
{
    $map = ["A" => 5, "B" => 3.5, "C" => 2.5, "D" => 1.5];
    return $map[$category] ?? null;
}

function buildToteStructured($toteInfo)
{
    if (empty($toteInfo)) {
        return null;
    }

    $places = array_values(array_filter([
        $toteInfo["PLA1"] ?? null,
        $toteInfo["PLA2"] ?? null,
        $toteInfo["PLA3"] ?? null,
        $toteInfo["PLA4"] ?? null,
    ]));

    $quinella = array_values(array_filter([
        isset($toteInfo["QIND"]) && $toteInfo["QIND"] ? ["value" => $toteInfo["QIND"], "carried_forward" => false] : null,
        isset($toteInfo["QINC"]) && $toteInfo["QINC"] ? ["value" => $toteInfo["QINC"], "carried_forward" => true] : null,
    ]));

    $tanala = array_values(array_filter([
        isset($toteInfo["TNLD1"]) && $toteInfo["TNLD1"] ? ["value" => $toteInfo["TNLD1"], "carried_forward" => false] : null,
        isset($toteInfo["TNLD2"]) && $toteInfo["TNLD2"] ? ["value" => $toteInfo["TNLD2"], "carried_forward" => false] : null,
        isset($toteInfo["TNLDC"]) && $toteInfo["TNLDC"] ? ["value" => $toteInfo["TNLDC"], "carried_forward" => true] : null,
    ]));

    return [
        "win"                => $toteInfo["WIN"] ?: null,
        "win_alternate"      => $toteInfo["WINA"] ?: null,
        "place"              => $places,
        "shp"                => $toteInfo["SHP"] ?: null,
        "exacta_win"         => $toteInfo["EXW"] ?: null,
        "exacta_win_cf"      => $toteInfo["EXWC"] ?: null,
        "exacta_place"       => $toteInfo["EXP"] ?: null,
        "exacta_place_cf"    => $toteInfo["EXPC"] ?: null,
        "forecast"           => $toteInfo["FORD"] ?: null,
        "forecast_cf"        => $toteInfo["FORC"] ?: null,
        "quinella"           => $quinella,
        "tanala"              => $tanala,
    ];
}

// --------------------------------------------------
// CACHE KEY (historical data only)
// Historical race-result JSON/data is safe to cache per date/type/race_type.
// HTML archive mode above is deliberately not cached.
$cacheKey = "race_results_" .
    md5($date . "_" . $type . "_" . $raceType);

// --------------------------------------------------
// FETCH DATA
// --------------------------------------------------

try {

    // ---- Resolve date from raceno if needed ----

    if ($searaceno > 0 && $date === "") {
        $stmt = $conn->prepare(
            "SELECT DISTINCT RACEDATE FROM fdecl WHERE RACENO_SEA = ? LIMIT 1"
        );
        $stmt->bind_param("i", $searaceno);
        $stmt->execute();
        $dateRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (empty($dateRow)) {
            $security->respondAndCache($cacheKey, [
                "found"   => false,
                "message" => "Could not find results for Race No. {$searaceno}",
                "races"   => [],
            ]);
            exit;
        }
        $date = $dateRow["RACEDATE"];
    }

    if ($date > "2022-10-14") {
        $security->respondError(
            "This endpoint only serves historical race days on or before 2022-10-14",
            400
        );
        exit;
    }

    // ---- Day narrative ----

    $dayNarr = "";
    $stmt = $conn->prepare(
        "SELECT DAYNARR FROM prospect WHERE `DATE` = ? AND DAYNARR <> '' LIMIT 1"
    );
    $stmt->bind_param("s", $date);
    $stmt->execute();
    if ($row = $stmt->get_result()->fetch_assoc()) {
        $dayNarr = $row["DAYNARR"];
    }
    $stmt->close();

    // ---- Race headers (single race if raceno given, else whole day) ----

    $raceHeaders = [];

    if ($searaceno > 0) {
        $stmt = $conn->prepare(
            "SELECT p.SRNO, p.NAME AS RACENAME, p.NARRENT, p.DISTANCE, p.FJOCK,
                    f.RACENO, f.RACENO_SEA, f.RTIME, f.`DIV`
             FROM prospect p
             INNER JOIN fdecl f ON p.SRNO = f.LINK
             WHERE f.RACENO_SEA = ? AND p.`DATE` = ?
             GROUP BY f.RACENO
             ORDER BY f.RACENO ASC"
        );
        $stmt->bind_param("is", $searaceno, $date);
    } else {
        $stmt = $conn->prepare(
            "SELECT p.SRNO, p.NAME AS RACENAME, p.NARRENT, p.DISTANCE, p.FJOCK,
                    rh.RACENO, rh.RACENO_SEA, rh.RTIME, rh.`DIV`
             FROM prospect p
             INNER JOIN (
                 SELECT RACENO, MIN(LINK) AS LINK, MIN(RACENO_SEA) AS RACENO_SEA,
                        MIN(RTIME) AS RTIME, MIN(`DIV`) AS `DIV`
                 FROM fdecl
                 WHERE RACEDATE = ?
                 GROUP BY RACENO
             ) rh ON rh.LINK = p.SRNO
             WHERE p.`DATE` = ?
             ORDER BY rh.RACENO ASC"
        );
        $stmt->bind_param("ss", $date, $date);
    }
    $stmt->execute();
    $headerResult = $stmt->get_result();
    while ($row = $headerResult->fetch_assoc()) {
        $raceHeaders[$row["RACENO"]] = $row;
    }
    $stmt->close();

    if (empty($raceHeaders)) {
        $security->respondAndCache($cacheKey, [
            "found"   => false,
            "message" => "No results found for {$date}",
            "races"   => [],
        ]);
        exit;
    }

    // ---- Bulk jockey lookup (whole table, tiny, load once) ----

    $jockeyNames = [];
    $jkyResult = $conn->query("SELECT JOCKEY, JOCKEYNM FROM jockeys");
    while ($row = $jkyResult->fetch_assoc()) {
        $jockeyNames[$row["JOCKEY"]] = $row["JOCKEYNM"];
    }

    // ---- Bulk race results for the whole date in one query ----
    // Replaces the original per-race fhorse5/fcard/hmaster query
    // (N+1 -> 1), grouped by RACENO (== RACENO_SEA) in PHP below.

    $resultsByRace = [];

    $stmt = $conn->prepare(
        "SELECT fc.HORSENAME, fc.TRAINERNM, h.SIRE, h.DAM, h.BREEDER,
                fc.FINALNAME, fc.FINALNAME1, fc.FINALNAME2, fc.FINALNAME3, f.*
         FROM fhorse5 f
         INNER JOIN fcard fc ON f.HORSESEQ = fc.HORSESEQ
         INNER JOIN hmaster h ON f.HORSESEQ = h.HORSESEQ
         WHERE f.RACEDATE = ? AND fc.RACEDATE = ?
         ORDER BY f.RACENO ASC, f.PLACING ASC, f.CARDNO ASC"
    );
    $stmt->bind_param("ss", $date, $date);
    $stmt->execute();
    $rrResult = $stmt->get_result();
    while ($row = $rrResult->fetch_assoc()) {
        $resultsByRace[$row["RACENO"]][] = $row;
    }
    $stmt->close();

    // ---- Bulk tote (divsingl) for the whole date in one query ----
    // Replaces the original per-race divsingl query (N+1 -> 1).

    $toteByRace = [];
    $stmt = $conn->prepare("SELECT * FROM divsingl WHERE RACEDATE = ?");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $toteResult = $stmt->get_result();
    while ($row = $toteResult->fetch_assoc()) {
        $toteByRace[$row["RACENO"]] = $row;
    }
    $stmt->close();

    // ---- Assemble races ----

    $races = [];

    foreach ($raceHeaders as $raceNo => $prospect) {

        $raceNoSea = $prospect["RACENO_SEA"];
        $overrideKey = "{$date}|{$raceNoSea}";

        $raceResults = $resultsByRace[$raceNoSea] ?? [];

        $isCancelled = isset($CANCELLED_RACES[$overrideKey]);

        if (empty($raceResults) && !$isCancelled) {
            // Legacy page silently skipped races with no results (and no
            // cancellation override) - keep that behavior.
            continue;
        }

        $division = "";
        switch ((int) ($prospect["DIV"] ?? 0)) {
            case 1:
                $division = "Division I";
                break;
            case 2:
                $division = "Division II";
                break;
            case 3:
                $division = "Division III";
                break;
        }

        $entries = [];
        $lengths = [];
        $cardNoResults = [];
        $ownership = "";
        $breeder = "";
        $toteFav = "";
        $nullPlacingCount = 0;
        $voidRace = isset($FORCED_VOID_RACES[$overrideKey]);

        foreach ($raceResults as $raceResult) {

            if ($raceResult["PLACING"] == 1) {
                $ownership = trim(
                    ($raceResult["FINALNAME"] ?? "")
                        . ($raceResult["FINALNAME1"] ?? "")
                        . ($raceResult["FINALNAME2"] ?? "")
                        . ($raceResult["FINALNAME3"] ?? "")
                );
                $breeder = $raceResult["BREEDER"] ?? "";
            }

            [$placingDisplay, $nonFinishFlag] = mapPlacingCode(
                $raceResult["PLACING"],
                $nullPlacingCount,
                $voidRace
            );

            $weight = ($raceResult["WEIGHTCD"] == 0) ? null : $raceResult["WEIGHTCD"];

            $jockey = $jockeyNames[$raceResult["JOCKEY"]] ?? null;
            $allowance = jockeyAllowance($raceResult["CATEGORY"] ?? null);

            $odds = null;
            if (!($raceResult["BKM1ODDS"] == 0 && $raceResult["BKM2ODDS"] == 0)) {
                $odds = "{$raceResult["BKM1ODDS"]}/{$raceResult["BKM2ODDS"]}";
            }

            $time = null;
            if (!($raceResult["TIMINGMTS"] == 0 && $raceResult["TIMINGSEC"] == 0 && $raceResult["TIMINGSECD"] == 0)) {
                $secs = $raceResult["TIMINGSEC"] < 10 ? "0" . $raceResult["TIMINGSEC"] : $raceResult["TIMINGSEC"];
                $secds = $raceResult["TIMINGSECD"];
                if ($secds < 10) {
                    $secds = "00" . $secds;
                } elseif ($secds < 100) {
                    $secds = "0" . $secds;
                }
                $time = "{$raceResult["TIMINGMTS"]}:{$secs}:{$secds}";
            }

            $horseWt = ($raceResult["HORSEWT"] < 100) ? "NR" : $raceResult["HORSEWT"];

            $entries[] = [
                "placing"         => $placingDisplay,
                "non_finish"      => $nonFinishFlag,
                "card_no"         => $raceResult["CARDNO"],
                "horse_name"      => $raceResult["HORSENAME"],
                "horseseq"        => $raceResult["HORSESEQ"],
                "sire"            => $raceResult["SIRE"],
                "dam"             => $raceResult["DAM"],
                "weight"          => $weight,
                "jockey"          => $jockey,
                "jockey_category" => $raceResult["CATEGORY"] ?? null,
                "jockey_allowance" => $allowance,
                "trainer"         => $raceResult["TRAINERNM"],
                "odds"            => $odds,
                "time"            => $time,
                "horse_weight"    => $horseWt,
            ];

            $lengthLabel = mapLengthLabel($raceResult["LENGTH"] ?? null);
            if ($lengthLabel !== null) {
                $lengths[] = $lengthLabel;
            }

            $cardNoResults[] = $nonFinishFlag
                ? "{$raceResult["CARDNO"]} {$placingDisplay}"
                : "{$raceResult["CARDNO"]}";

            if (($raceResult["CLASS"] ?? null) == 1) {
                $toteFav = $raceResult["HORSENAME"];
            }
        }

        $cardNoResultsStr = $CARD_NO_OVERRIDES[$overrideKey] ?? implode("-", $cardNoResults);

        $tote = buildToteStructured($toteByRace[$raceNoSea] ?? null);

        $races[] = [
            "race_no"                  => $raceNo,
            "race_no_season"           => $raceNoSea,
            "race_name"                => $prospect["RACENAME"],
            "division"                 => $division,
            "narrative_entry"          => $prospect["NARRENT"],
            "distance"                 => $prospect["DISTANCE"],
            "time"                     => $prospect["RTIME"] ?? "",
            "foreign_jockeys_eligible" => isset($prospect["FJOCK"]) && $prospect["FJOCK"] == 1,
            "cancelled"                => $isCancelled,
            "void"                     => $voidRace,
            "results"                  => $entries,
            "ownership"                => $ownership,
            "breeder"                  => $breeder,
            "distance_run"             => implode(", ", $lengths),
            "results_by_card_no"       => $cardNoResultsStr,
            "tote_favourite"           => $toteFav,
            "tote"                     => $tote,
        ];
    }

    // ---- Day-level extras: conditions, pools (only for whole-day view) ----

    $conditions = null;
    $pools = null;

    if ($searaceno <= 0) {

        $stmt = $conn->prepare(
            "SELECT WEATHER, PENITROM, FALSERAILS, OTHER FROM scaletop WHERE RACEDATE = ?"
        );
        $stmt->bind_param("s", $date);
        $stmt->execute();
        if ($row = $stmt->get_result()->fetch_assoc()) {
            $conditions = [
                "weather"      => $row["WEATHER"],
                "penetrometer" => $row["PENITROM"],
                "false_rails"  => trim(($row["FALSERAILS"] ?? "") . ($row["OTHER"] ?? "")),
            ];
        }
        $stmt->close();

        $stmt = $conn->prepare(
            "SELECT dm.*, p.* FROM divmulti dm
             INNER JOIN pools p ON dm.RACEDATE = p.RACEDATE
             WHERE dm.RACEDATE = ?"
        );
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $divMultiInfo = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!empty($divMultiInfo)) {

            // OPTIMIZATION: prepare this statement once and re-execute it
            // for every pool-winner lookup (up to 6x per request - super
            // jackpot, jackpot x1-2, treble x1-3) instead of calling
            // prepare() fresh each time. Same query, same bind values per
            // call, same result rows/order - output is unaffected, this
            // only removes redundant parse/plan round-trips to MySQL.
            $raceNosCsvBound = "";
            $winnersStmt = $conn->prepare(
                "SELECT h.HORSENM FROM fhorse5 f
                 INNER JOIN hmaster h ON f.HORSESEQ = h.HORSESEQ
                 WHERE f.RACEDATE = ? AND FIND_IN_SET(f.RACENO, ?) AND f.PLACING = 1
                 ORDER BY f.RACENO ASC, f.PLACING ASC, f.CARDNO ASC"
            );
            $winnersStmt->bind_param("ss", $date, $raceNosCsvBound);

            $fetchWinners = function ($raceNosCsv) use ($winnersStmt, &$raceNosCsvBound) {
                $raceNosCsvBound = $raceNosCsv;
                $winnersStmt->execute();
                $result = $winnersStmt->get_result();
                $names = [];
                while ($row = $result->fetch_assoc()) {
                    $names[] = $row["HORSENM"];
                }
                return $names;
            };

            $pools = [];

            // ---- Super Jackpot ----
            $winnersOverride = $POOL_WINNER_OVERRIDES["{$date}|super_jackpot"] ?? null;
            $raceNos = "{$divMultiInfo["UP0R1"]},{$divMultiInfo["UP0R2"]},{$divMultiInfo["UP0R3"]},{$divMultiInfo["UP0R4"]},{$divMultiInfo["UP0R5"]},{$divMultiInfo["UP0R6"]}";
            $div30 = $POOL_DIV_OVERRIDES[$date]["super_jackpot_30"] ?? [$divMultiInfo["UP0D2"] ?? null, $divMultiInfo["UP0T2"] ?? null];

            $pools["super_jackpot"] = [
                "legs"            => $divMultiInfo["FLDSTR11"] ?? null,
                "winners"         => $winnersOverride !== null ? explode(", ", $winnersOverride) : $fetchWinners($raceNos),
                "dividend_70pct"  => ($divMultiInfo["UP0D1"] ?? 0) > 0 ? ["dividend" => $divMultiInfo["UP0D1"], "tickets" => $divMultiInfo["UP0T1"]] : null,
                "dividend_30pct"  => ($divMultiInfo["UP0D2"] ?? 0) > 0 ? ["dividend" => $div30[0], "tickets" => $div30[1]] : null,
                "carried_forward" => ($divMultiInfo["UP0CF"] ?? 0) > 0 ? $divMultiInfo["UP0CF"] : null,
            ];

            // ---- Jackpot (combined or split first/second) ----
            if (($divMultiInfo["JP0D1"] ?? 0) || ($divMultiInfo["JP0D2"] ?? 0) || ($divMultiInfo["JP0CF"] ?? 0)) {
                $winnersOverride = $POOL_WINNER_OVERRIDES["{$date}|jackpot"] ?? null;
                $raceNos = "{$divMultiInfo["JP0R1"]},{$divMultiInfo["JP0R2"]},{$divMultiInfo["JP0R3"]},{$divMultiInfo["JP0R4"]},{$divMultiInfo["JP0R5"]}";
                $div30 = $POOL_DIV_OVERRIDES[$date]["jackpot_30"] ?? [$divMultiInfo["JP0D2"] ?? null, $divMultiInfo["JP0T2"] ?? null];

                $pools["jackpot"] = [[
                    "label"           => "Jackpot",
                    "legs"            => $divMultiInfo["FLDSTR1"] ?? null,
                    "winners"         => $winnersOverride !== null ? explode(", ", $winnersOverride) : $fetchWinners($raceNos),
                    "dividend_70pct"  => ($divMultiInfo["JP0D1"] ?? 0) > 0 ? ["dividend" => $divMultiInfo["JP0D1"], "tickets" => $divMultiInfo["JP0T1"]] : null,
                    "dividend_30pct"  => ($divMultiInfo["JP0D2"] ?? 0) > 0 ? ["dividend" => $div30[0], "tickets" => $div30[1]] : null,
                    "carried_forward" => ($divMultiInfo["JP0CF"] ?? 0) > 0 ? $divMultiInfo["JP0CF"] : null,
                ]];
            } else {
                $pools["jackpot"] = [];
                foreach ([1 => ["FIRST", "FLDSTR2"], 2 => ["SECOND", "FLDSTR3"]] as $i => $meta) {
                    [$label, $legsField] = $meta;
                    $raceNos = "{$divMultiInfo["JP{$i}R1"]},{$divMultiInfo["JP{$i}R2"]},{$divMultiInfo["JP{$i}R3"]},{$divMultiInfo["JP{$i}R4"]},{$divMultiInfo["JP{$i}R5"]}";
                    $div30 = $POOL_DIV_OVERRIDES[$date]["jackpot_30"] ?? [$divMultiInfo["JP{$i}D2"] ?? null, $divMultiInfo["JP{$i}T2"] ?? null];

                    $pools["jackpot"][] = [
                        "label"           => "{$label} Jackpot",
                        "legs"            => $divMultiInfo[$legsField] ?? null,
                        "winners"         => $fetchWinners($raceNos),
                        "dividend_70pct"  => ($divMultiInfo["JP{$i}D1"] ?? 0) > 0 ? ["dividend" => $divMultiInfo["JP{$i}D1"], "tickets" => $divMultiInfo["JP{$i}T1"]] : null,
                        "dividend_30pct"  => ($divMultiInfo["JP{$i}D2"] ?? 0) > 0 ? ["dividend" => $div30[0], "tickets" => $div30[1]] : null,
                        "carried_forward" => ($divMultiInfo["JP{$i}CF"] ?? 0) > 0 ? $divMultiInfo["JP{$i}CF"] : null,
                    ];
                }
            }

            // ---- Treble (combined or split first/second/third) ----
            if (($divMultiInfo["TR0D1"] ?? 0) || ($divMultiInfo["TR0CF"] ?? 0)) {
                $winnersOverride = $POOL_WINNER_OVERRIDES["{$date}|treble"] ?? null;
                $raceNos = "{$divMultiInfo["TR0R1"]},{$divMultiInfo["TR0R2"]},{$divMultiInfo["TR0R3"]}";

                $pools["treble"] = [[
                    "label"           => "Treble",
                    "legs"            => $divMultiInfo["FLDSTR4"] ?? null,
                    "winners"         => $winnersOverride !== null ? explode(", ", $winnersOverride) : $fetchWinners($raceNos),
                    "dividend"        => ($divMultiInfo["TR0CF"] ?? 0) == 0 ? ["dividend" => $divMultiInfo["TR0D1"], "tickets" => $divMultiInfo["TR0T1"]] : null,
                    "carried_forward" => ($divMultiInfo["TR0CF"] ?? 0) != 0 ? $divMultiInfo["TR0CF"] : null,
                ]];
            } else {
                $pools["treble"] = [];
                foreach ([1 => ["FIRST", "FLDSTR5"], 2 => ["SECOND", "FLDSTR6"], 3 => ["THIRD", "FLDSTR7"]] as $i => $meta) {
                    [$label, $legsField] = $meta;
                    if (($divMultiInfo["TR{$i}D1"] ?? 0) == 0 && ($divMultiInfo["TR{$i}CF"] ?? 0) == 0) {
                        continue;
                    }
                    $raceNos = "{$divMultiInfo["TR{$i}R1"]},{$divMultiInfo["TR{$i}R2"]},{$divMultiInfo["TR{$i}R3"]}";

                    $pools["treble"][] = [
                        "label"           => "{$label} Treble",
                        "legs"            => $divMultiInfo[$legsField] ?? null,
                        "winners"         => $fetchWinners($raceNos),
                        "dividend"        => ($divMultiInfo["TR{$i}CF"] ?? 0) == 0 ? ["dividend" => $divMultiInfo["TR{$i}D1"], "tickets" => $divMultiInfo["TR{$i}T1"]] : null,
                        "carried_forward" => ($divMultiInfo["TR{$i}CF"] ?? 0) != 0 ? $divMultiInfo["TR{$i}CF"] : null,
                    ];
                }
            }

            $winnersStmt->close();
        }
    }

    // ---- Media tips / updates toggle ----

    $mediaTipsEnabled = false;
    $stmt = $conn->prepare("SELECT `value` FROM `config` WHERE `id` = '2'");
    $stmt->execute();
    if ($row = $stmt->get_result()->fetch_assoc()) {
        $mediaTipsEnabled = ($row["value"] ?? "") === "Y";
    }
    $stmt->close();

    // ---- Video link (whole day, not per-race) ----

    $videoUrl = null;
    if (strtotime($date) < strtotime("2015-07-23")) {
        $stmt = $conn->prepare("SELECT chan, cat FROM videos WHERE racedate = ?");
        $stmt->bind_param("s", $date);
        $stmt->execute();
        if ($vrow = $stmt->get_result()->fetch_assoc()) {
            $videoUrl = "http://www.mumbairaces.com/index.php?chan={$vrow["chan"]}&cat={$vrow["cat"]}";
        }
        $stmt->close();
    } else {
        $videoUrl = "https://www.rwitcraces.com/RaceArchives.aspx?d=" . date("dmY", strtotime($date));
    }

    // ---- Download file link ----

    $downloadUrl = null;
    if (preg_match('/\d\d\d(\d)-(\d\d)-(\d\d)/', $date, $matchDate)) {
        $fileDate = $matchDate[3] . $matchDate[2] . $matchDate[1];
        $downloadBase = defined("DOWNLOADFILE_BASE") ? DOWNLOADFILE_BASE : "";
        $downloadUrl = "https://rwitc.com/{$downloadBase}/RES{$fileDate}.HTM";
    }

    // OPTIMIZATION: strtotime($date) was being called twice on the same
    // unchanging string (day_label + auto_refresh); memoized here instead.
    // Same values, same types - output unaffected.
    $dateTimestamp = strtotime($date);
    $todayTimestamp = strtotime(date("Y-m-d"));

    $response = [
        "found"               => true,
        "date"                => $date,
        "day_label"           => date("l jS F Y", $dateTimestamp),
        "day_narrative"       => $dayNarr,
        "club_name"           => defined("CLUB_NAME") ? CLUB_NAME : null,
        "download_url"        => $downloadUrl,
        "video_url"           => $videoUrl,
        "auto_refresh"        => ($dateTimestamp === $todayTimestamp),
        "media_tips_enabled"  => $mediaTipsEnabled,
        "conditions"          => $conditions,
        "races"               => $races,
        "pools"               => $pools,
    ];

    // --------------------------------------------------
    // FINAL RESPONSE
    // --------------------------------------------------

    $security->respondAndCache(
        $cacheKey,
        $response
    );
} catch (Throwable $error) {

    // Log actual database error
    $security->logLine(
        "RACE_RESULTS_API_ERROR | "
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
