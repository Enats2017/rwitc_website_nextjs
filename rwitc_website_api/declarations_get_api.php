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
    "cache_ttl"      => 120,
    "cache_dir"      => __DIR__ . "/cache",
    "rate_limit_dir" => __DIR__ . "/rate_limits",
    "api_tag"        => "declarations_get"
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

// NOTE: same scope as acceptance_get_api.php - this endpoint only serves
// historical race days (<= 2022-09-25), which is the branch of the legacy
// page that pulled data straight from the DB tables (prospect/fdecl/
// hmaster/pools). Anything after that cutoff was rendered from static
// Declarations_YYYY-MM-DD.html files on disk in the legacy page and is
// intentionally out of scope here.

$date = isset($_GET["date"]) ? trim($_GET["date"]) : "";

if ($date === "" || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || strtotime($date) === false) {
    $security->respondError(
        "A valid date parameter (YYYY-MM-DD) is required",
        400
    );
    exit;
}

if ($date > "2022-09-25") {

    $cacheKey = "declarations_html_" . md5($date);

    if ($security->serveCache($cacheKey)) {
        exit;
    }

    $htmlFile = RUN_RACES_LOCAL_PATH . "/Declarations_" . $date . ".html";

    if (!file_exists($htmlFile)) {
        $security->respondError("No declarations data found for this date", 404);
        exit;
    }

    try {

        $htmlContent = file_get_contents($htmlFile);

        if ($htmlContent === false) {
            throw new Exception("Unable to read archive file: " . $htmlFile);
        }

        $htmFile = RUN_RACES_LOCAL_PATH . "/Declarations_" . $date . ".htm";
        $downloadAvailable = file_exists($htmFile);
        $downloadFile = $downloadAvailable
            ? RUN_RACES_BASE_URL . "/Declarations_" . $date . ".htm"
            : null;

        $response = [
            "found"              => true,
            "date"               => $date,
            "mode"               => "html",
            "html"               => $htmlContent,
            "download_file"      => $downloadFile,
            "download_available" => $downloadAvailable
        ];

        $security->respondAndCache($cacheKey, $response);

    } catch (Throwable $error) {

        $security->logLine("DECLARATIONS_HTML_READ_ERROR | " . $error->getMessage());
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
// CACHE KEY (per date - historical data never changes)
// --------------------------------------------------

$cacheKey = "declarations_" . md5($date);

if ($security->serveCache($cacheKey)) {
    exit;
}

// --------------------------------------------------
// HELPERS
// --------------------------------------------------

function poolLabel($fieldName)
{
    $labels = [
        "FLDSTR1"  => "JACKPOT POOL RACES",
        "FLDSTR2"  => "FIRST JACKPOT POOL RACES",
        "FLDSTR3"  => "SECOND JACKPOT POOL RACES",
        "FLDSTR4"  => "TREBLE POOL RACES",
        "FLDSTR5"  => "FIRST TREBLE POOL RACES",
        "FLDSTR6"  => "SECOND TREBLE POOL RACES",
        "FLDSTR7"  => "THIRD TREBLE POOL RACES",
        "FLDSTR8"  => "FOURTH TREBLE POOL RACES",
        "FLDSTR9"  => "TANALA POOL RACES",
        "FLDSTR10" => "QUARTET POOL RACES",
        "FLDSTR11" => "SUPER JACKPOT POOL RACES",
        "FLDSTR12" => "FIRST SUPER JACKPOT POOL RACES",
        "FLDSTR13" => "SECOND SUPER JACKPOT POOL RACES",
        "FLDSTR14" => "DOUBLE FORECAST POOL RACES",
        "FLDSTR15" => "DOUBLE FORECAST POOL RACES",
    ];

    return $labels[$fieldName] ?? $fieldName;
}

function weightAdjustment($value, $stageLabel)
{
    if ($value === null || $value === "" || (float) $value == 0.0) {
        return null;
    }

    return [
        "stage"     => $stageLabel,
        "direction" => $value > 0 ? "raised" : "lowered",
        "kg"        => abs((float) $value),
    ];
}

function jockeyAllowance($category)
{
    $map = [
        "A" => 5,
        "B" => 3.5,
        "C" => 2.5,
        "D" => 1.5,
    ];

    return $map[$category] ?? null;
}

// --------------------------------------------------
// FETCH DATA
// --------------------------------------------------

try {

    // ---- Day narrative (cheap, indexed LIMIT 1 lookup) ----

    $dayNarr = "";

    $stmt = $conn->prepare(
        "SELECT DAYNARR FROM prospect WHERE `DATE` = ? AND DAYNARR <> '' LIMIT 1"
    );
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $narrResult = $stmt->get_result();
    if ($narrRow = $narrResult->fetch_assoc()) {
        $dayNarr = $narrRow["DAYNARR"];
    }
    $stmt->close();

    // ---- Race-level headers ----
    // One grouped query instead of joining the full fdecl table and
    // collapsing duplicate rows in PHP. RTIME/DIV/RACENO_SEA are constant
    // per race, so MIN() over the grouped fdecl rows is a safe,
    // deterministic pick. Note: DIV is a reserved word in MariaDB/MySQL
    // (the integer division operator) so it must always be backticked.

    $raceHeaders = [];

    $stmt = $conn->prepare(
        "SELECT p.SRNO, p.NAME AS RACENAME, p.DAYNARR, p.NARRENT, p.DISTANCE, p.FJOCK, p.HTERMS,
                p.RACECAT, p.GRADE, p.RAISELOWER, p.RAISEACP1, p.RAISEACP2, p.RAISEACP3,
                p.RACETIME1, p.RACETIME2, p.VOID_HACP, p.VOID_ACCP,
                rh.RACENO, rh.RTIME, rh.`DIV`, rh.RACENO_SEA
         FROM prospect p
         INNER JOIN (
             SELECT RACENO, MIN(LINK) AS LINK, MIN(RTIME) AS RTIME,
                    MIN(`DIV`) AS `DIV`, MIN(RACENO_SEA) AS RACENO_SEA
             FROM fdecl
             WHERE RACEDATE = ?
             GROUP BY RACENO
         ) rh ON rh.LINK = p.SRNO
         WHERE p.`DATE` = ?
         ORDER BY rh.RACENO ASC"
    );
    $stmt->bind_param("ss", $date, $date);
    $stmt->execute();
    $headerResult = $stmt->get_result();
    while ($row = $headerResult->fetch_assoc()) {
        $raceHeaders[$row["RACENO"]] = $row;
    }
    $stmt->close();

    if (empty($raceHeaders)) {
        $security->respondAndCache($cacheKey, [
            "found"   => false,
            "message" => "No declarations found for {$date}",
            "races"   => [],
        ]);
        exit;
    }

    // ---- All horse/declaration rows for the whole date in one query ----
    // Replaces the original per-race fdecl query executed inside the races
    // loop (N+1 queries -> 1 query), grouped by RACENO in PHP below.

    $horsesByRace = [];

    $stmt = $conn->prepare(
        "SELECT f.RACENO, f.NAME, f.WEIGHT, f.CARDNO, f.TRN_NM, f.JOCKEYNM, f.CATEGORY,
                f.HORSEWT, f.SHOE, f.DRAWNO, f.HORSESEQ, f.RATING, h.SIRE, h.DAM, h.DAMNAT
         FROM fdecl f
         INNER JOIN hmaster h ON f.HORSESEQ = h.HORSESEQ
         WHERE f.RACEDATE = ?
         ORDER BY f.RACENO ASC, f.CARDNO ASC"
    );
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $horseResult = $stmt->get_result();

    while ($row = $horseResult->fetch_assoc()) {
        $raceNo = $row["RACENO"];

        if (!isset($horsesByRace[$raceNo])) {
            $horsesByRace[$raceNo] = [];
        }

        $rating = $row["RATING"];
        if ($rating == -99) {
            $rating = "NR";
        }

        $horseWt = $row["HORSEWT"];
        if ($horseWt == 0) {
            $horseWt = null;
        }

        $drawNo = $row["DRAWNO"];
        if ($drawNo == 0) {
            $drawNo = null;
        }

        $breeding = $row["SIRE"] . " - " . $row["DAM"];
        if (!empty($row["DAMNAT"])) {
            $breeding .= " (" . $row["DAMNAT"] . ")";
        }

        $horsesByRace[$raceNo][] = [
            "card_no"          => $row["CARDNO"],
            "name"             => $row["NAME"],
            "horseseq"         => $row["HORSESEQ"],
            "weight"           => $row["WEIGHT"],
            "rating"           => $rating,
            "sire"             => $row["SIRE"],
            "dam"              => $row["DAM"],
            "dam_nation"       => $row["DAMNAT"],
            "breeding"         => $breeding,
            "trainer"          => $row["TRN_NM"],
            "jockey"           => $row["JOCKEYNM"],
            "jockey_category"  => $row["CATEGORY"],
            "jockey_allowance" => jockeyAllowance($row["CATEGORY"]),
            "horse_weight"     => $horseWt,
            "shoe"             => $row["SHOE"],
            "draw_no"          => $drawNo,
        ];
    }
    $stmt->close();

    // ---- Pools (single row keyed by FLDSTR1..15, same as legacy) ----

    $pools = [];

    $stmt = $conn->prepare(
        "SELECT FLDSTR1, FLDSTR2, FLDSTR3, FLDSTR4, FLDSTR5, FLDSTR6, FLDSTR7,
                FLDSTR8, FLDSTR9, FLDSTR10, FLDSTR11, FLDSTR12, FLDSTR13, FLDSTR14, FLDSTR15
         FROM pools
         WHERE RACEDATE = ?"
    );
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $poolsResult = $stmt->get_result();

    if ($poolsRow = $poolsResult->fetch_assoc()) {
        foreach ($poolsRow as $fieldName => $members) {
            if (isset($members) && trim($members) !== "") {
                $pools[] = [
                    "pool_name" => poolLabel($fieldName),
                    "members"   => $members,
                ];
            }
        }
    }
    $stmt->close();

    // ---- Assemble race entries ----

    $races = [];

    foreach ($raceHeaders as $raceNo => $prospect) {

        $division = "";
        $weightsACP = 0;

        switch ((int) ($prospect["DIV"] ?? 0)) {
            case 1:
                $division = "Division I";
                $weightsACP = $prospect["RAISEACP1"] ?? 0;
                break;
            case 2:
                $division = "Division II";
                $weightsACP = $prospect["RAISEACP2"] ?? 0;
                break;
            case 3:
                $division = "Division III";
                $weightsACP = $prospect["RAISEACP3"] ?? 0;
                break;
            default:
                $division = "";
                $weightsACP = $prospect["RAISEACP1"] ?? 0;
                break;
        }

        $adjustments = array_values(array_filter([
            weightAdjustment($prospect["RAISELOWER"] ?? null, "handicap"),
            weightAdjustment($weightsACP, "acceptance"),
        ]));

        $races[] = [
            "race_no"                  => $raceNo,
            "race_no_season"           => $prospect["RACENO_SEA"] ?? null,
            "race_name"                => $prospect["RACENAME"],
            "division"                 => $division,
            "narrative_entry"          => $prospect["NARRENT"],
            "distance"                 => $prospect["DISTANCE"],
            "time"                     => $prospect["RTIME"] ?? "",
            "foreign_jockeys_eligible" => isset($prospect["FJOCK"]) && $prospect["FJOCK"] == 1,
            "weight_adjustments"       => $adjustments,
            "horses"                   => $horsesByRace[$raceNo] ?? [],
        ];
    }

    // ---- Download file link (kept identical to legacy filename logic) ----

    $downloadUrl = null;
    if (preg_match('/\d\d\d(\d)-(\d\d)-(\d\d)/', $date, $matchDate)) {
        $fileDate = $matchDate[3] . $matchDate[2] . $matchDate[1];
        $downloadBase = defined("DOWNLOADFILE_BASE") ? DOWNLOADFILE_BASE : "";
        $downloadUrl = "https://rwitc.com/{$downloadBase}/DEC{$fileDate}.HTM";
    }

    $response = [
        "found"              => true,
        "date"               => $date,
        "mode"               => "json",
        "day_label"          => date("l jS F Y", strtotime($date)),
        "day_narrative"      => $dayNarr,
        "club_name"          => defined("CLUB_NAME") ? CLUB_NAME : null,
        "download_file"      => $downloadUrl,
        "download_available" => $downloadUrl !== null,
        "races"              => $races,
        "pools"              => $pools,
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
        "DECLARATIONS_API_ERROR | "
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