<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// Load database and security
require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/config/run_races_config.php";
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
    "api_tag"        => "race_result_get"
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
// VALIDATE INPUT
// --------------------------------------------------

$raceno = $_GET["raceno"] ?? "";
$racedate = $_GET["racedate"] ?? "";

if ($racedate === "" || strtotime($racedate) === false) {
    $security->respondError(
        "A valid racedate parameter (YYYY-MM-DD) is required",
        400
    );
    exit;
}

// --------------------------------------------------
// ARCHIVE MODE (racedate > 2022-09-25): served from a static
// Race_results_<date>.html file on disk, same pattern as
// Acceptance/Declarations/Handicaps. The whole day's races live in
// one file, so raceno is not required/used here.
// --------------------------------------------------

if ($racedate > "2022-09-25") {

    $cacheKey = "race_result_html_" . md5($racedate);

    if ($security->serveCache($cacheKey)) {
        exit;
    }

    $htmlFile = RUN_RACES_LOCAL_PATH . "/Race_results_" . $racedate . ".html";

    if (!file_exists($htmlFile)) {
        $security->respondError("No race results found for this date", 404);
        exit;
    }

    try {

        $htmlContent = file_get_contents($htmlFile);

        if ($htmlContent === false) {
            throw new Exception("Unable to read archive file: " . $htmlFile);
        }

        $htmFile = RUN_RACES_LOCAL_PATH . "/Race_results_" . $racedate . ".htm";
        $downloadAvailable = file_exists($htmFile);
        $downloadFile = $downloadAvailable
            ? RUN_RACES_BASE_URL . "/Race_results_" . $racedate . ".htm"
            : null;

        $response = [
            "found"              => true,
            "date"               => $racedate,
            "mode"               => "html",
            "html"               => $htmlContent,
            "download_file"      => $downloadFile,
            "download_available" => $downloadAvailable
        ];

        $security->respondAndCache($cacheKey, $response);

    } catch (Throwable $error) {

        $security->logLine("RACE_RESULT_HTML_READ_ERROR | " . $error->getMessage());
        $security->respondError("Internal server error", 500);

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
// JSON MODE (racedate <= 2022-09-25): DB-driven, per race.
// raceno is required here since results are fetched race by race.
// --------------------------------------------------

if ($raceno === "") {
    $security->respondError(
        "raceno parameter is required for this date",
        400
    );
    exit;
}

// --------------------------------------------------
// CACHE KEY (per race no + race date)
// --------------------------------------------------

$cacheKey = "race_result_" . md5($raceno . "_" . $racedate);

if ($security->serveCache($cacheKey)) {
    exit;
}

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

function normalizeAmount($value)
{
    if ($value === null || $value === "") {
        return $value;
    }

    $normalized = $value;
    $parts = explode(".", $value);

    if (isset($parts[0]) && isset($parts[1])) {
        if ($parts[0] == 0 && $parts[1] == 0) {
            $normalized = 0;
        } elseif ($parts[0] != 0 && ($parts[1] == "0" || $parts[1] == "")) {
            $normalized = $parts[0];
        }
    }

    return $normalized;
}

function mapPlacingCode($placing, &$voidRace)
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
        63 => "WDRN",
        64 => "DNF",
    ];

    if ($placing == 91) {
        $voidRace = true;
        return "-";
    }
    if ($placing == 0) {
        return "-";
    }
    if ($placing > 0 && $placing <= 24) {
        return $placing;
    }
    if (isset($map[$placing])) {
        return $map[$placing];
    }

    return $placing;
}

function mapLengthCode($lengths)
{
    if ($lengths === null || $lengths === "") {
        return "";
    }

    if (!is_numeric($lengths)) {
        return $lengths;
    }

    switch ($lengths) {
        case 0:
            return "";
        case 20:
            return "DH";
        case 30:
            return "Shd";
        case 40:
            return "Hd";
        case 50:
            return "nk";
        case 60.00:
            return "NO";
        case 60:
            return "NO ";
        case 70:
            return "SN ";
        case 80.00:
            return "LN";
        case 80:
            return "LN ";
        case 90:
            return "Dist";
        default:
            return convertDecimalToFractionString($lengths);
    }
}

// --------------------------------------------------
// FETCH DATA
// --------------------------------------------------

try {

    $racenoEsc = $conn->real_escape_string($raceno);
    $racedateEsc = $conn->real_escape_string($racedate);

    $raceDetails = [];

    if (strtotime($racedateEsc) <= strtotime("2022-07-31")) {

        $legacyTable = strtotime($racedateEsc) < strtotime("2010-07-15") ? "ofhorse5" : "fhorse5";

        $result = $conn->query(
            "SELECT h.HORSENM, h.SIRE, h.DAM, h.OWNERSHIP, t.TRAINERNME AS TRAINERNM, j.JOCKEYNM, f.*
             FROM {$legacyTable} f
             INNER JOIN hmaster h ON f.HORSESEQ = h.HORSESEQ
             INNER JOIN trainers t ON f.TRAINER = t.TRAINER
             INNER JOIN jockeys j ON f.JOCKEY = j.JOCKEY
             WHERE f.RACEDATE = '{$racedateEsc}' AND f.RACENO = '{$racenoEsc}'
             ORDER BY f.PLACING ASC"
        );

        if ($result === false) {
            throw new Exception($conn->error);
        }

        while ($row = $result->fetch_assoc()) {
            $raceDetails[] = $row;
        }

    } else {

        $result = $conn->query(
            "SELECT het.HORSESEQ, het.HORSENM, het.SIRE, het.DAM, het.OWNERSHIP,
                    rd.TRAINERNM, rd.JOCKEYNM, rd.PLACING, rd.WEIGHTCD, rd.BKM1ODDS, rd.BKM2ODDS,
                    rd.TIMINGMTS, rd.TIMINGSEC, rd.TIMINGSECD, rd.HORSEWT, rd.LENGTH, rd.VERDICT,
                    rd.CRACEDATE, rd.racename, rd.raceterm, rd.distance, rd.grade
             FROM horse_erp het
             INNER JOIN runs_data rd ON het.HORSESEQ = rd.HORSESEQ
             WHERE rd.cracedate = '{$racedateEsc}' AND rd.RACENO = '{$racenoEsc}'
             ORDER BY rd.Placing ASC"
        );

        if ($result === false) {
            throw new Exception($conn->error);
        }

        while ($row = $result->fetch_assoc()) {
            $raceDetails[] = $row;
        }
    }

    if (empty($raceDetails)) {
        $security->respondAndCache($cacheKey, [
            "found"   => false,
            "message" => "No results found for race {$raceno} on {$racedate}",
            "results" => [],
        ]);
        exit;
    }

    // --------------------------------------------------
    // RACE NAME / TERM / DISTANCE HEADER
    // --------------------------------------------------

    $raceHeader = null;

    $raceNameResult = $conn->query(
        "SELECT RACENAME, RACETERM, DISTANCE FROM racenm r
         WHERE r.RACEDATE = '{$racedateEsc}' AND r.RACENO = '{$racenoEsc}'"
    );

    if ($raceNameResult === false) {
        throw new Exception($conn->error);
    }

    $raceNameDetails = $raceNameResult->fetch_assoc();

    if (!empty($raceNameDetails)) {
        $raceHeader = [
            "race_name" => $raceNameDetails["RACENAME"],
            "race_term" => $raceNameDetails["RACETERM"],
            "distance"  => $raceNameDetails["DISTANCE"],
            "grade"     => null,
        ];
    } else {
        foreach ($raceDetails as $raceResult) {
            if (isset($raceResult["racename"])) {
                $gradeText = $raceResult["grade"] > 0 ? "Grade " . $raceResult["grade"] : "";
                $raceHeader = [
                    "race_name" => $raceResult["racename"],
                    "race_term" => $raceResult["raceterm"],
                    "distance"  => $raceResult["distance"],
                    "grade"     => $gradeText,
                ];
                break;
            }
        }
    }

    // --------------------------------------------------
    // BUILD RESULT ROWS
    // --------------------------------------------------

    $voidRace = false;
    $results = [];

    foreach ($raceDetails as $raceResult) {

        $placingDisplay = mapPlacingCode($raceResult["PLACING"], $voidRace);

        $weight = ($raceResult["WEIGHTCD"] == 0) ? null : $raceResult["WEIGHTCD"];

        $length = mapLengthCode($raceResult["LENGTH"] ?? "");

        $odds = null;
        if (!($raceResult["BKM1ODDS"] == 0 && $raceResult["BKM2ODDS"] == 0)) {
            $bkm1 = normalizeAmount($raceResult["BKM1ODDS"]);
            $bkm2 = normalizeAmount($raceResult["BKM2ODDS"]);

            $odds = ($bkm2 != "0" && $bkm2 != "") ? "{$bkm1}/{$bkm2}" : "{$bkm1}";
        }

        $time = null;
        if (!($raceResult["TIMINGMTS"] == 0 && $raceResult["TIMINGSEC"] == 0 && $raceResult["TIMINGSECD"] == 0)) {
            $secs = $raceResult["TIMINGSEC"] < 10 ? "0" . $raceResult["TIMINGSEC"] : $raceResult["TIMINGSEC"];
            $secds = $raceResult["TIMINGSECD"] < 10 ? "0" . $raceResult["TIMINGSECD"] : $raceResult["TIMINGSECD"];
            $time = "{$raceResult['TIMINGMTS']}:{$secs}:{$secds}";
        }

        $horseWt = $raceResult["HORSEWT"];
        if ($horseWt < 100) {
            $horseWt = "NR";
        } else {
            $horseWt = normalizeAmount($horseWt);
        }

        $results[] = [
            "placing"        => $placingDisplay,
            "horse_name"     => $raceResult["HORSENM"],
            "horseseq"       => $raceResult["HORSESEQ"] ?? null,
            "sire"           => $raceResult["SIRE"],
            "dam"            => $raceResult["DAM"],
            "weight"         => $weight,
            "length"         => $length,
            "trainer"        => $raceResult["TRAINERNM"],
            "jockey"         => $raceResult["JOCKEYNM"],
            "odds"           => $odds,
            "time"           => $time,
            "horse_weight"   => $horseWt,
        ];
    }

    $response = [
        "found"        => true,
        "race_no"      => $raceno,
        "race_date"    => date("d-m-Y", strtotime($racedate)),
        "race_header"  => $raceHeader,
        "void_race"    => $voidRace,
        "results"      => $results,
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
        "RACE_RESULT_API_ERROR | "
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