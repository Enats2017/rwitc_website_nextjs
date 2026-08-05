<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// Load database and security
require_once __DIR__ . "/config/config.php";
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
    "api_tag"        => "horse_profile_get"
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

$hname = "";

if (!empty($_GET["horsename"])) {
    $hname = $_GET["horsename"];
} elseif (!empty($_GET["as_values"])) {
    $hname = $_GET["as_values"];
}

if ($hname === "") {
    $security->respondError(
        "horsename parameter is required",
        400
    );
    exit;
}

// --------------------------------------------------
// CACHE KEY (per horse name)
// --------------------------------------------------

$cacheKey = "horse_profile_" . md5(strtolower(trim($hname)));

if ($security->serveCache($cacheKey)) {
    exit;
}

// --------------------------------------------------
// HELPERS
// --------------------------------------------------

/**
 * Convert a decimal amount like 12.25 into a fraction string like "12 1/4".
 * Only handles the .25 / .50 / .75 conventions used in the legacy data.
 */
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

/**
 * Collapse a value like 100.00 to 100, but keep genuine fractional stakes/odds intact.
 */
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

/**
 * Map raw PLACING codes to their display abbreviations.
 * Returns [displayPlacing, wintimeOverride] where wintimeOverride is null
 * when the original WINTIME should be left untouched.
 */
function mapPlacingCode($placing)
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

    if ($placing == 0 || $placing == 91) {
        return ["-", ""];
    }
    if ($placing > 0 && $placing <= 24) {
        return [$placing, null];
    }
    if (isset($map[$placing])) {
        return [$map[$placing], ""];
    }

    return [$placing, null];
}

function buildSireDameDetails($sire, $sirenat, $dam, $damnat)
{
    $details = $sire;

    if (stripos($sire, "[") === false && $sirenat != "") {
        $details .= "[" . $sirenat . "]";
    }

    $details .= " - " . $dam;

    if (stripos($dam, "[") === false && $damnat != "") {
        $details .= "[" . $damnat . "]";
    }

    return $details;
}

function buildVideoLink($raceDate, $dayRaceNo)
{
    if (strtotime($raceDate) >= strtotime("2015-07-23")) {
        return "https://www.rwitcraces.com/RaceArchives.aspx?d="
            . date("dmY", strtotime($raceDate))
            . "&rno=" . $dayRaceNo;
    }
    return null;
}

// --------------------------------------------------
// FETCH DATA
// --------------------------------------------------

try {

    $horseName = $conn->real_escape_string(preg_replace("/\+/", " ", $hname));

    $horseseqResult = $conn->query(
        "SELECT horseseq, HORSENM, DATEFOAL
         FROM horse_erp
         WHERE HORSENM = '{$horseName}'
         ORDER BY horseseq DESC
         LIMIT 1"
    );

    if ($horseseqResult === false) {
        throw new Exception($conn->error);
    }

    $horseseq = $horseseqResult->fetch_assoc();

    $horse_id = "";
    $horse_name = "";
    $horsefoal_date = "";

    if (!empty($horseseq)) {
        $horse_id = $horseseq["horseseq"];
        $horse_name = $conn->real_escape_string($horseseq["HORSENM"]);
        $horsefoal_date = $conn->real_escape_string($horseseq["DATEFOAL"]);
    }

    if (empty($horseseq)) {
        $security->respondAndCache($cacheKey, [
            "found"   => false,
            "message" => "No records found for '{$hname}'",
            "runs"    => [],
        ]);
        exit;
    }

    $horse_namess = $horse_name;

    // Jockey lookup map
    $jockey_datas = [];
    $jkyResult = $conn->query("SELECT JOCKEYNM, JOCKEY FROM jockeys WHERE 1=1");
    if ($jkyResult === false) {
        throw new Exception($conn->error);
    }
    while ($jvalue = $jkyResult->fetch_assoc()) {
        $jockey_datas[$jvalue["JOCKEY"]] = $jvalue["JOCKEYNM"];
    }

    // Trainer lookup map
    $trainer_datas = [];
    $trnResult = $conn->query("SELECT TRAINERNM, TRAINER FROM trainers WHERE 1=1");
    if ($trnResult === false) {
        throw new Exception($conn->error);
    }
    while ($jvalue = $trnResult->fetch_assoc()) {
        $trainer_datas[$jvalue["TRAINER"]] = $jvalue["TRAINERNM"];
    }

    // Day race no map (declarations)
    $dec_datas = [];
    $declResult = $conn->query("SELECT RACENO, HORSESEQ, RACEDATE FROM `decl` WHERE 1=1");
    if ($declResult === false) {
        throw new Exception($conn->error);
    }
    while ($jvalue = $declResult->fetch_assoc()) {
        $dec_datas[$jvalue["HORSESEQ"] . "_" . $jvalue["RACEDATE"]] = $jvalue["RACENO"];
    }

    // Horse sequence migration map
    $horse_migration_datas = [];
    $migResult = $conn->query("SELECT * FROM `updated_horsesequence_number` WHERE 1=1");
    if ($migResult === false) {
        throw new Exception($conn->error);
    }
    while ($jvalue = $migResult->fetch_assoc()) {
        $horse_migration_datas[$jvalue["seq_no"]] = $jvalue["id"];
    }

    // --------------------------------------------------
    // NEW RUNS DATA (post 2022-07-31)
    // --------------------------------------------------

    $horse_data = [];
    $new_horse = [];

    $newHorseDetails = $conn->query(
        "SELECT rd.HORSESEQ, rd.HORSENM, hm.HORSENMOLD, rd.VENUE, rd.RACENO, rd.JOCKEYNM,
                rd.DISTANCE, rd.WEIGHTCD, rd.PLACING, rd.WINTIME, rd.STAKES, rd.STAKES_NEW,
                rd.racecat, rd.PLASTAKES, rd.TRAINERNM, rd.RACEDATE, rd.cracedate, rd.wingross,
                rd.day_race_no, hm.HORSENM, hm.BREEDER, hm.SIRE, hm.SIRENAT, hm.DAM, hm.DAMNAT
         FROM runs_data rd
         LEFT JOIN horse_erp hm ON (rd.HORSESEQ = hm.HORSESEQ)
         WHERE (hm.HORSENM = '{$horse_namess}' OR hm.HORSENMOLD = '{$horse_namess}')
           AND RACENO > '0'
           AND rd.cracedate >= '2022-07-31'
         ORDER BY rd.cracedate DESC"
    );

    if ($newHorseDetails === false) {
        throw new Exception($conn->error);
    }

    while ($nvalue = $newHorseDetails->fetch_assoc()) {

        $nvalue["cracedate_new"] = "";
        if ($nvalue["cracedate"] != "" && $nvalue["cracedate"] != "1970-01-01") {
            $nvalue["cracedate_new"] = date("d-m-Y", strtotime($nvalue["cracedate"]));
        }

        $nvalue["PLASTAKES_1"] = normalizeAmount($nvalue["PLASTAKES"]);

        if ($nvalue["STAKES_NEW"] > 0) {
            $nvalue["STAKES_1"] = $nvalue["STAKES_NEW"];
            $nvalue["STAKES"] = $nvalue["STAKES_NEW"];
        } else {
            $nvalue["STAKES_1"] = $nvalue["STAKES"];
        }
        $nvalue["STAKES_1"] = normalizeAmount($nvalue["STAKES_1"]);

        $nvalue["sire_dame_details"] = buildSireDameDetails(
            $nvalue["SIRE"],
            $nvalue["SIRENAT"],
            $nvalue["DAM"],
            $nvalue["DAMNAT"]
        );

        $new_horse[] = $nvalue;
        $horse_data[$nvalue["HORSENM"]] = $nvalue["HORSESEQ"];
    }

    // --------------------------------------------------
    // OLD RUNS DATA (ofhorse5 + fhorse5, up to 2022-07-31)
    // --------------------------------------------------

    $old_horse_data = [];

    $legacyTables = ["ofhorse5", "fhorse5"];

    foreach ($legacyTables as $legacyTable) {

        $old_hrs_sql = $conn->query(
            "SELECT * FROM {$legacyTable} fh
             LEFT JOIN hmaster hm ON (fh.HORSESEQ = hm.HORSESEQ)
             WHERE ((hm.HORSENM = '{$horse_namess}' OR hm.HORSENMOLD = '{$horse_namess}')
               AND hm.DATEFOAL = '{$horsefoal_date}')
               AND RACEDATE <= '2022-07-31'"
        );

        if ($old_hrs_sql === false) {
            throw new Exception($conn->error);
        }

        while ($odvalue = $old_hrs_sql->fetch_assoc()) {

            if ($odvalue["RACEDATE"] == "" || $odvalue["RACEDATE"] == "1970-01-01" || $odvalue["RACEDATE"] == "0000-00-00") {
                continue;
            }

            $rowHorseName = $odvalue["HORSENM"];
            $rowOldHorseName = $odvalue["HORSENMOLD"];
            $rowHorseId = $odvalue["HORSESEQ"];

            if (isset($horse_migration_datas[$rowHorseId])) {
                $rowHorseId = $horse_migration_datas[$rowHorseId];
            }

            $include = true;
            if (isset($horse_data[$rowHorseName])) {
                $include = ($horse_data[$rowHorseName] == $rowHorseId);
            } elseif (isset($horse_data[$rowOldHorseName])) {
                $include = ($horse_data[$rowOldHorseName] == $rowHorseId);
            }

            if (!$include) {
                continue;
            }

            $jky_name = $jockey_datas[$odvalue["JOCKEY"]] ?? "";
            $trn_name = $trainer_datas[$odvalue["TRAINER"]] ?? "";
            $day_race_no = $dec_datas[$odvalue["HORSESEQ"] . "_" . $odvalue["RACEDATE"]] ?? "";

            $sire_dame_details = buildSireDameDetails(
                $odvalue["SIRE"],
                $odvalue["SIRENAT"],
                $odvalue["DAM"],
                $odvalue["DAMNAT"]
            );

            $old_horse_data[] = [
                "HORSESEQ"          => $odvalue["HORSESEQ"],
                "HORSENM"           => $rowHorseName,
                "HORSENMOLD"        => $odvalue["HORSENMOLD"],
                "VENUE"             => $odvalue["VENUE"],
                "RACENO"            => $odvalue["RACENO"],
                "JOCKEYNM"          => $jky_name,
                "DISTANCE"          => $odvalue["DISTANCE"],
                "WEIGHTCD"          => $odvalue["WEIGHTCD"],
                "PLACING"           => $odvalue["PLACING"],
                "WINTIME"           => $odvalue["TIMINGMTS"] . ":" . $odvalue["TIMINGSEC"] . ":" . $odvalue["TIMINGSECD"],
                "STAKES"            => $odvalue["STAKES"],
                "racecat"           => $odvalue["RACECAT"],
                "PLASTAKES"         => $odvalue["PLASTAKES"],
                "TRAINERNM"         => $trn_name,
                "RACEDATE"          => date("d-M-Y", strtotime($odvalue["RACEDATE"])),
                "cracedate"         => date("Y-m-d", strtotime($odvalue["RACEDATE"])),
                "wingross"          => $odvalue["WINGROSS"],
                "day_race_no"       => $day_race_no,
                "cracedate_new"     => date("d-m-Y", strtotime($odvalue["RACEDATE"])),
                "PLASTAKES_1"       => normalizeAmount($odvalue["PLASTAKES"]),
                "STAKES_1"          => normalizeAmount($odvalue["STAKES"]),
                "BREEDER"           => $odvalue["BREEDER"],
                "sire_dame_details" => $sire_dame_details,
            ];
        }
    }

    // --------------------------------------------------
    // MERGE + SORT
    // --------------------------------------------------

    $horseDetails = array_merge($new_horse, $old_horse_data);

    if (!empty($horseDetails)) {
        $sortOrder = [];
        foreach ($horseDetails as $key => $value) {
            $sortOrder[$key] = date("Y-m-d", strtotime($value["cracedate"]));
        }
        array_multisort($sortOrder, SORT_DESC, $horseDetails);
    }

    if (empty($horseDetails)) {
        $security->respondAndCache($cacheKey, [
            "found"   => false,
            "message" => "No records found for '{$hname}'",
            "runs"    => [],
        ]);
        exit;
    }

    // --------------------------------------------------
    // BUILD RESPONSE ROWS + TOTALS
    // --------------------------------------------------

    $runs = $wins = $seconds = $thirds = $stakes = 0;
    $rows = [];

    foreach ($horseDetails as $horse) {

        $weight = $horse["WEIGHTCD"] != "" ? ($horse["WEIGHTCD"] + 0) : "";

        [$placingDisplay, $wintimeOverride] = mapPlacingCode($horse["PLACING"]);
        $wintime = $wintimeOverride !== null ? $wintimeOverride : $horse["WINTIME"];

        if (($horse["PLACING"] > 0 && $horse["PLACING"] <= 24) || $horse["PLACING"] == 60) {
            $runs++;
        }
        if ($horse["PLACING"] == 1) {
            $wins++;
        }
        if ($horse["PLACING"] == 2) {
            $seconds++;
        }
        if ($horse["PLACING"] == 3) {
            $thirds++;
        }

        if ($horse["STAKES_1"] == 0) {
            $stakeValue = $horse["PLASTAKES_1"];
            $stakes += $horse["PLASTAKES_1"];
        } else {
            $stakeValue = $horse["STAKES_1"];
            $stakes += $horse["STAKES_1"];
        }

        $rows[] = [
            "venue"        => $horse["VENUE"],
            "date"         => $horse["cracedate_new"],
            "race_no"      => $horse["RACENO"],
            "horseseq"     => $horse_id,
            "race_date"    => $horse["cracedate"],
            "jockey"       => $horse["JOCKEYNM"],
            "class"        => $horse["racecat"],
            "distance"     => $horse["DISTANCE"],
            "weight"       => $weight,
            "placing"      => $placingDisplay,
            "time"         => $wintime,
            "stakes"       => $stakeValue,
            "video_url"    => buildVideoLink($horse["RACEDATE"], $horse["day_race_no"]),
        ];
    }

    $response = [
        "found"             => true,
        "horse_name"        => $horseDetails[0]["HORSENM"],
        "sire_dame_details" => $horseDetails[0]["sire_dame_details"],
        "totals"            => [
            "runs"    => $runs,
            "wins"    => $wins,
            "seconds" => $seconds,
            "thirds"  => $thirds,
            "stakes"  => $stakes,
        ],
        "runs_data" => $rows,
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
        "HORSE_PROFILE_API_ERROR | "
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