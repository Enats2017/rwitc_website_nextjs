<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// Load database and security
require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/ApiSecurity.php";

// --------------------------------------------------
// REMOTE FILE HOST CONFIG
// --------------------------------------------------
// The generated race-day HTML files live on the rwitc_website server,
// not on this API server, so post-cutoff "does this file exist" checks
// have to be done over HTTP instead of via local file_exists().


// =========== Live Url ===================

// if (!defined('RUN_RACES_BASE_URL')) {
//     define('RUN_RACES_BASE_URL', 'https://www.rwitc.com/run_races/');
// }

// if (!defined('RACEDAY_REPORT_BASE_URL')) {
//     define('RACEDAY_REPORT_BASE_URL', 'https://www.rwitc.com/staticpages/racedayreports/');
// }



// ========== Local Url ===================

require_once __DIR__ . "/config/run_races_config.php";

if (!defined('RACEDAY_REPORT_BASE_URL')) {
    define('RACEDAY_REPORT_BASE_URL', RACEDAY_REPORT_PUBLIC_BASE);
}






// Timeouts for the remote existence checks (seconds)
const REMOTE_CHECK_CONNECT_TIMEOUT = 3;
const REMOTE_CHECK_TIMEOUT         = 5;

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
    "api_tag"        => "archives_get"
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

$rawStart = $_GET['start'] ?? '';
$rawEnd   = $_GET['end'] ?? '';

$startTs = strtotime($rawStart);
$endTs   = strtotime($rawEnd);

if ($rawStart === '' || $rawEnd === '' || $startTs === false || $endTs === false) {

    $security->respondError(
        "Invalid or missing 'start'/'end' date parameters",
        400
    );

    exit;
}

$start = date("Y-m-d", $startTs);
$end   = date("Y-m-d", $endTs);

// New validation: start must not be after end (was not checked before)
if (strtotime($start) > strtotime($end)) {

    $security->respondError(
        "'start' date must not be after 'end' date",
        400
    );

    exit;
}

$firstDate = date("Y-m-d", mktime(0, 0, 0, 6, 26, 2010)); // 26 Jun 2010

if (strtotime($start) < strtotime($firstDate)) {
    // Nothing before this date is served - return empty result set (same as legacy behaviour)
    $security->respondAndCache("archives_{$start}_{$end}", []);
    exit;
}

// --------------------------------------------------
// CACHE KEY BASED ON DATE RANGE
// --------------------------------------------------

$cacheKey = "archives_{$start}_{$end}";

if ($security->serveCache($cacheKey)) {
    exit;
}

// --------------------------------------------------
// FETCH DATA
// --------------------------------------------------

try {

    $jsonArray = [];

    // ---- Trackwork ----

    $trackStmt = $conn->prepare(
        "SELECT id, trackwork_date FROM trackwork
         WHERE trackwork_date >= ? AND trackwork_date <= ? AND published = 'Y'"
    );

    if ($trackStmt === false) {
        throw new Exception($conn->error);
    }

    $trackStmt->bind_param("ss", $start, $end);
    $trackStmt->execute();
    $trackResult = $trackStmt->get_result();

    $trackworklist = [];
    while ($row = $trackResult->fetch_assoc()) {
        $trackworklist[] = $row;
    }
    $trackStmt->close();

    foreach ($trackworklist as $trackwork) {
        $jsonArray[] = [
            "id"        => 1,
            "className" => "trackwork",
            "title"     => "TrackWork",
            "start"     => $trackwork['trackwork_date'],
            "url"       => "trackwork.php?id={$trackwork['id']}",
        ];
    }

    // ---- Race dates (prospect + erp_pre_race, merged/deduped by date) ----

    $raceDates = [];

    $prospectStmt = $conn->prepare(
        "SELECT DISTINCT(`DATE`) AS `DATE` FROM prospect WHERE `DATE` >= ? AND `DATE` <= ?"
    );

    if ($prospectStmt === false) {
        throw new Exception($conn->error);
    }

    $prospectStmt->bind_param("ss", $start, $end);
    $prospectStmt->execute();
    $prospectResult = $prospectStmt->get_result();

    while ($row = $prospectResult->fetch_assoc()) {
        $raceDates[$row['DATE']] = $row;
    }
    $prospectStmt->close();

    $preRaceStmt = $conn->prepare(
        "SELECT DISTINCT(`racedate`) AS `DATE` FROM erp_pre_race WHERE `racedate` >= ? AND `racedate` <= ?"
    );

    if ($preRaceStmt === false) {
        throw new Exception($conn->error);
    }

    $preRaceStmt->bind_param("ss", $start, $end);
    $preRaceStmt->execute();
    $preRaceResult = $preRaceStmt->get_result();

    while ($row = $preRaceResult->fetch_assoc()) {
        $raceDates[$row['DATE']] = $row;
    }
    $preRaceStmt->close();

    // --------------------------------------------------
    // IMPORTANT: Migrated archive dates
    //
    // A migrated archive can exist even when the date is not present
    // in prospect/erp_pre_race. Add those dates to the calendar.
    //
    // Trackwork is completely separate and is not changed.
    // --------------------------------------------------

    $migratedDatesStmt = $conn->prepare(
        "SELECT DISTINCT `date`
         FROM run_race_details
         WHERE `date` >= ?
           AND `date` <= ?
           AND `race_type` = 'pre_race'
           AND `type` IN (
               'handicaps',
               'acceptances',
               'declarations',
               'racecard',
               'race_result',
               'race_results',
               'rating_change'
           )
           AND file_url IS NOT NULL
           AND file_url <> ''"
    );

    if ($migratedDatesStmt === false) {
        throw new Exception($conn->error);
    }

    $migratedDatesStmt->bind_param("ss", $start, $end);
    $migratedDatesStmt->execute();

    $migratedDatesResult = $migratedDatesStmt->get_result();

    while ($row = $migratedDatesResult->fetch_assoc()) {

        $migratedDate = $row['date'];

        if (!isset($raceDates[$migratedDate])) {
            $raceDates[$migratedDate] = [
                'DATE' => $migratedDate
            ];
        }
    }

    $migratedDatesStmt->close();

    // ---- Split the merged race dates into the two cutoff buckets ----
    // (same $cutoffDate comparison as before, just done once up front so
    // the bulk queries below know which dates belong in which bucket)

    $cutoffDate = '2022-07-31';

    $preCutoffDates  = [];
    $postCutoffDates = [];

    foreach ($raceDates as $raceDate) {
        $date = $raceDate['DATE'];
        if ($date <= $cutoffDate) {
            $preCutoffDates[] = $date;
        } else {
            $postCutoffDates[] = $date;
        }
    }

    // --------------------------------------------------
    // PRE-CUTOFF: bulk-load existence per table (6 queries total,
    // instead of 6 queries PER DATE). Each result is stored in a
    // date-indexed array; the date loop later just does isset() on it.
    // --------------------------------------------------

    $handicapDates     = [];
    $acceptancesDates   = [];
    $declarationsDates  = [];
    $resultsDates       = [];
    $raceDayDbDates     = [];
    $ratingDates        = [];

    if (!empty($preCutoffDates)) {

        $placeholders = implode(',', array_fill(0, count($preCutoffDates), '?'));
        $types        = str_repeat('s', count($preCutoffDates));

        // weights -> Handicap
        $handicapStmt = $conn->prepare("SELECT DISTINCT RACEDATE FROM weights WHERE RACEDATE IN ({$placeholders})");
        if ($handicapStmt === false) {
            throw new Exception($conn->error);
        }
        $handicapStmt->bind_param($types, ...$preCutoffDates);
        $handicapStmt->execute();
        $handicapResult = $handicapStmt->get_result();
        while ($row = $handicapResult->fetch_assoc()) {
            $handicapDates[$row['RACEDATE']] = true;
        }
        $handicapStmt->close();

        // decl -> Acceptances
        $acceptancesStmt = $conn->prepare("SELECT DISTINCT RACEDATE FROM decl WHERE RACEDATE IN ({$placeholders})");
        if ($acceptancesStmt === false) {
            throw new Exception($conn->error);
        }
        $acceptancesStmt->bind_param($types, ...$preCutoffDates);
        $acceptancesStmt->execute();
        $acceptancesResult = $acceptancesStmt->get_result();
        while ($row = $acceptancesResult->fetch_assoc()) {
            $acceptancesDates[$row['RACEDATE']] = true;
        }
        $acceptancesStmt->close();

        // fdecl -> Declarations
        $declarationsStmt = $conn->prepare("SELECT DISTINCT RACEDATE FROM fdecl WHERE RACEDATE IN ({$placeholders})");
        if ($declarationsStmt === false) {
            throw new Exception($conn->error);
        }
        $declarationsStmt->bind_param($types, ...$preCutoffDates);
        $declarationsStmt->execute();
        $declarationsResult = $declarationsStmt->get_result();
        while ($row = $declarationsResult->fetch_assoc()) {
            $declarationsDates[$row['RACEDATE']] = true;
        }
        $declarationsStmt->close();

        // fhorse5 -> Race Results
        $resultsStmt = $conn->prepare("SELECT DISTINCT RACEDATE FROM fhorse5 WHERE RACEDATE IN ({$placeholders})");
        if ($resultsStmt === false) {
            throw new Exception($conn->error);
        }
        $resultsStmt->bind_param($types, ...$preCutoffDates);
        $resultsStmt->execute();
        $resultsResult = $resultsStmt->get_result();
        while ($row = $resultsResult->fetch_assoc()) {
            $resultsDates[$row['RACEDATE']] = true;
        }
        $resultsStmt->close();

        // raceday_report -> Race Day Report (existence only, pre-cutoff)
        $raceDayStmt = $conn->prepare("SELECT DISTINCT RACEDATE FROM raceday_report WHERE RACEDATE IN ({$placeholders})");
        if ($raceDayStmt === false) {
            throw new Exception($conn->error);
        }
        $raceDayStmt->bind_param($types, ...$preCutoffDates);
        $raceDayStmt->execute();
        $raceDayResult = $raceDayStmt->get_result();
        while ($row = $raceDayResult->fetch_assoc()) {
            $raceDayDbDates[$row['RACEDATE']] = true;
        }
        $raceDayStmt->close();

        // ratings_change -> Rating Change
        $ratingStmt = $conn->prepare("SELECT DISTINCT RACEDATE FROM ratings_change WHERE RACEDATE IN ({$placeholders})");
        if ($ratingStmt === false) {
            throw new Exception($conn->error);
        }
        $ratingStmt->bind_param($types, ...$preCutoffDates);
        $ratingStmt->execute();
        $ratingResult = $ratingStmt->get_result();
        while ($row = $ratingResult->fetch_assoc()) {
            $ratingDates[$row['RACEDATE']] = true;
        }
        $ratingStmt->close();
    }

    // --------------------------------------------------
    // POST-CUTOFF: LOCAL run_races + DB/S3 fallback
    //
    // Priority:
    //   1. Local physical run_races file (.html OR .htm)
    //   2. run_race_details.file_url (S3)
    //
    // This is a UNION, not an either/or date source:
    // local files and DB/S3 records both make an archive available.
    //
    // Trackwork is intentionally untouched.
    // --------------------------------------------------

    $postCutoffFileDates = [
        'handicaps'     => [],
        'acceptances'   => [],
        'declarations'  => [],
        'racecard'      => [],
        'race_results'  => [],
        'rating_change' => []
    ];

    /*
     * LOCAL FILES
     *
     * Check the actual filesystem, not the public URL.
     * Both .html and .htm are supported.
     */
    foreach ($postCutoffDates as $date) {

        $localFiles = [
            'handicaps' => [
                "Handicaps_{$date}.html",
                "Handicaps_{$date}.htm"
            ],

            'acceptances' => [
                "Acceptance_{$date}.html",
                "Acceptance_{$date}.htm"
            ],

            'declarations' => [
                "Declarations_{$date}.html",
                "Declarations_{$date}.htm"
            ],

            'racecard' => [
                "Race_Card_{$date}.html",
                "Race_Card_{$date}.htm",
                "Race_Card_Report_{$date}.htm"
            ],

            'race_results' => [
                "Race_results_{$date}.html",
                "Race_results_{$date}.htm"
            ],

            'rating_change' => [
                "Rating_change_{$date}.html",
                "Rating_change_{$date}.htm"
            ]
        ];

        foreach ($localFiles as $type => $filenames) {

            foreach ($filenames as $filename) {

                $fullPath =
                    rtrim(RUN_RACES_LOCAL_PATH, DIRECTORY_SEPARATOR)
                    . DIRECTORY_SEPARATOR
                    . $filename;

                if (is_file($fullPath)) {
                    $postCutoffFileDates[$type][$date] = true;
                    break;
                }
            }
        }
    }

    /*
     * DB/S3 FALLBACK
     */
    if (!empty($postCutoffDates)) {

        $placeholdersPost = implode(
            ',',
            array_fill(0, count($postCutoffDates), '?')
        );

        $migratedStmt = $conn->prepare("
            SELECT `date`, `type`, file_url
            FROM run_race_details
            WHERE `date` IN ({$placeholdersPost})
              AND `race_type` = 'pre_race'
              AND `type` IN (
                  'handicaps',
                  'acceptances',
                  'declarations',
                  'racecard',
                  'race_result',
                  'race_results',
                  'rating_change'
              )
              AND file_url IS NOT NULL
              AND file_url <> ''
        ");

        if ($migratedStmt === false) {
            throw new Exception($conn->error);
        }

        $typesPost = str_repeat('s', count($postCutoffDates));

        $migratedStmt->bind_param(
            $typesPost,
            ...$postCutoffDates
        );

        $migratedStmt->execute();

        $migratedResult = $migratedStmt->get_result();

        while ($row = $migratedResult->fetch_assoc()) {

            $date = $row['date'];
            $type = $row['type'];

            if ($type === 'race_result' || $type === 'race_results') {

                $postCutoffFileDates['race_results'][$date] = true;

            } elseif (isset($postCutoffFileDates[$type])) {

                $postCutoffFileDates[$type][$date] = true;
            }
        }

        $migratedStmt->close();
    }

    // --------------------------------------------------
    // Resolve final availability: LOCAL OR DB/S3
    // --------------------------------------------------

    $resolvedPostCutoff = [];

    foreach ($postCutoffDates as $date) {

        $resolvedPostCutoff[$date] = [
            'handicaps' =>
                isset($postCutoffFileDates['handicaps'][$date]),

            'acceptances' =>
                isset($postCutoffFileDates['acceptances'][$date]),

            'declarations' =>
                isset($postCutoffFileDates['declarations'][$date]),

            'racecard' =>
                isset($postCutoffFileDates['racecard'][$date]),

            'results' =>
                isset($postCutoffFileDates['race_results'][$date]),

            'rating' =>
                isset($postCutoffFileDates['rating_change'][$date])
        ];
    }

    // --------------------------------------------------
    // Build events
    // --------------------------------------------------

    foreach ($raceDates as $raceDate) {

        $date = $raceDate['DATE'];

        if ($date <= $cutoffDate) {

            $handicap     = isset($handicapDates[$date]);
            $acceptances  = isset($acceptancesDates[$date]);
            $declarations = isset($declarationsDates[$date]);
            $results      = isset($resultsDates[$date]);
            $raceDayCheck = isset($raceDayDbDates[$date]);
            $ratingCheck  = isset($ratingDates[$date]);
            $racecard     = false;
        } else {

            /*
             * Post-cutoff:
             *
             * Each migrated archive follows:
             *
             *   run_races HTML exists
             *          OR
             *   run_race_details contains a valid S3 file_url
             *
             * Trackwork is not involved in this branch and remains unchanged.
             */

            $resolved = $resolvedPostCutoff[$date] ?? [];

            $handicap =
                !empty($resolved['handicaps']);

            $acceptances =
                !empty($resolved['acceptances']);

            $declarations =
                !empty($resolved['declarations']);

            $results =
                !empty($resolved['results']);

            $ratingCheck =
                !empty($resolved['rating']);

            $racecard =
                !empty($resolved['racecard']);

            /*
             * RACE DAY REPORT - existing logic preserved.
             */
            $filename = isset($reportFilenames[$date])
                ? $reportFilenames[$date]
                : '';

            if ($filename !== '') {

                $raceDayUrl =
                    RACEDAY_REPORT_BASE_URL . $filename;

                $raceDayCheck =
                    isset($remoteStatusCache[$raceDayUrl])
                    && $remoteStatusCache[$raceDayUrl];

            } else {

                $raceDayCheck = false;
            }
        }

        if ($handicap) {
            $jsonArray[] = [
                "id"        => 2,
                "className" => "handicaps",
                "title"     => "Handicap",
                "start"     => $date,
                "url"       => "erp_handcaps.php?date={$date}",
            ];
        }

        if ($racecard) {
            $jsonArray[] = [
                "id"        => 8,
                "className" => "racecard",
                "title"     => "Race Card",
                "start"     => $date,
                "url"       => "race_details?type=racecard&date={$date}",
            ];
        }

        if ($acceptances) {
            $jsonArray[] = [
                "id"        => 3,
                "className" => "acceptances",
                "title"     => "Acceptances",
                "start"     => $date,
                "url"       => "erp_acceptances.php?date={$date}",
            ];
        }

        if ($declarations) {
            $jsonArray[] = [
                "id"        => 4,
                "className" => "declarations",
                "title"     => "Declarations",
                "start"     => $date,
                "url"       => "erp_declarations.php?date={$date}",
            ];
        }

        if ($results) {
            $jsonArray[] = [
                "id"        => 5,
                "className" => "raceresults",
                "title"     => "Race Results",
                "start"     => $date,
                "url"       => "erp_raceresult.php?date={$date}",
            ];
        }

        if ($raceDayCheck) {
            $jsonArray[] = [
                "id"        => 6,
                "className" => "raceday-report",
                "title"     => "Race Report",
                "start"     => $date,
                "url"       => "raceDayReport.php?date={$date}",
            ];
        }

        if ($ratingCheck) {
            $jsonArray[] = [
                "id"        => 7,
                "className" => "rating-change",
                "title"     => "Rating Change",
                "start"     => $date,
                "url"       => "erp_ratingchange.php?date={$date}",
            ];
        }
    }

    // --------------------------------------------------
    // FINAL RESPONSE
    // --------------------------------------------------

    $security->respondAndCache(
        $cacheKey,
        $jsonArray
    );
} catch (Throwable $error) {

    // Log actual database error
    $security->logLine(
        "ARCHIVES_API_ERROR | "
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
