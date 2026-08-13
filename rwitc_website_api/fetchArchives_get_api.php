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
    // POST-CUTOFF: bulk-load raceday_report filenames for the whole
    // batch in ONE query, instead of one query per date.
    // --------------------------------------------------

    $reportFilenames = [];

    if (!empty($postCutoffDates)) {

        $placeholdersPost = implode(',', array_fill(0, count($postCutoffDates), '?'));
        $typesPost        = str_repeat('s', count($postCutoffDates));

        $reportFilenameStmt = $conn->prepare(
            "SELECT RACEDATE, filename FROM raceday_report WHERE RACEDATE IN ({$placeholdersPost})"
        );
        if ($reportFilenameStmt === false) {
            throw new Exception($conn->error);
        }
        $reportFilenameStmt->bind_param($typesPost, ...$postCutoffDates);
        $reportFilenameStmt->execute();
        $reportFilenameResult = $reportFilenameStmt->get_result();
        while ($row = $reportFilenameResult->fetch_assoc()) {
            // Same semantics as the original: only keep it if a non-empty
            // filename was found, and only the first match per date.
            if (!empty($row['filename']) && !isset($reportFilenames[$row['RACEDATE']])) {
                $reportFilenames[$row['RACEDATE']] = $row['filename'];
            }
        }
        $reportFilenameStmt->close();
    }

    // --------------------------------------------------
    // POST-CUTOFF: collect every remote URL that needs a HEAD check
    // across ALL post-cutoff dates, de-duplicated via isset(), then
    // fire them all concurrently with curl_multi instead of one at a
    // time with curl_exec.
    // --------------------------------------------------

    $remoteUrlsToFetch = [];

    foreach ($postCutoffDates as $date) {

        $urlsForDate = [
            RUN_RACES_BASE_URL . "Handicaps_{$date}.html",
            RUN_RACES_BASE_URL . "Acceptance_{$date}.html",
            RUN_RACES_BASE_URL . "Declarations_{$date}.html",
            RUN_RACES_BASE_URL . "Race_results_{$date}.html",
            RUN_RACES_BASE_URL . "Rating_change_{$date}.html",
        ];

        if (isset($reportFilenames[$date])) {
            $urlsForDate[] = RACEDAY_REPORT_BASE_URL . $reportFilenames[$date];
        }

        foreach ($urlsForDate as $url) {
            if (!isset($remoteUrlsToFetch[$url])) {
                $remoteUrlsToFetch[$url] = true;
            }
        }
    }

    $remoteStatusCache = []; // url => bool (2xx = true)

    if (!empty($remoteUrlsToFetch)) {

        $multiHandle = curl_multi_init();
        $curlHandles = [];

        foreach (array_keys($remoteUrlsToFetch) as $url) {

            $ch = curl_init($url);

            curl_setopt_array($ch, [
                CURLOPT_NOBODY         => true,   // HEAD request, no body needed
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => REMOTE_CHECK_CONNECT_TIMEOUT,
                CURLOPT_TIMEOUT        => REMOTE_CHECK_TIMEOUT,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);

            curl_multi_add_handle($multiHandle, $ch);
            $curlHandles[$url] = $ch;
        }

        $stillRunning = null;
        do {
            $mrc = curl_multi_exec($multiHandle, $stillRunning);
            if ($stillRunning) {
                curl_multi_select($multiHandle);
            }
        } while ($stillRunning && $mrc === CURLM_OK);

        foreach ($curlHandles as $url => $ch) {

            $errorNumber = curl_errno($ch);
            $httpCode    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($errorNumber !== 0) {
                $security->logLine("ARCHIVES_API_REMOTE_CHECK_ERROR | {$url} | curl_errno={$errorNumber}");
                $remoteStatusCache[$url] = false;
            } else {
                $remoteStatusCache[$url] = ($httpCode >= 200 && $httpCode < 300);
            }

            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);
        }

        curl_multi_close($multiHandle);
    }

    // --------------------------------------------------
    // Build events: walk $raceDates once, reading everything from the
    // already-loaded arrays above via isset() — no per-date DB query,
    // no per-date HTTP request.
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

        } else {

            $handicapUrl     = RUN_RACES_BASE_URL . "Handicaps_{$date}.html";
            $acceptancesUrl  = RUN_RACES_BASE_URL . "Acceptance_{$date}.html";
            $declarationsUrl = RUN_RACES_BASE_URL . "Declarations_{$date}.html";
            $resultsUrl      = RUN_RACES_BASE_URL . "Race_results_{$date}.html";
            $ratingUrl       = RUN_RACES_BASE_URL . "Rating_change_{$date}.html";

            $handicap     = isset($remoteStatusCache[$handicapUrl]) && $remoteStatusCache[$handicapUrl];
            $acceptances  = isset($remoteStatusCache[$acceptancesUrl]) && $remoteStatusCache[$acceptancesUrl];
            $declarations = isset($remoteStatusCache[$declarationsUrl]) && $remoteStatusCache[$declarationsUrl];
            $results      = isset($remoteStatusCache[$resultsUrl]) && $remoteStatusCache[$resultsUrl];
            $ratingCheck  = isset($remoteStatusCache[$ratingUrl]) && $remoteStatusCache[$ratingUrl];

            // Race day report HTML is also on the remote server, keyed by
            // the filename found in raceday_report for this date.
            $filename = $reportFilenames[$date] ?? '';

            if ($filename !== '') {
                $raceDayUrl   = RACEDAY_REPORT_BASE_URL . $filename;
                $raceDayCheck = isset($remoteStatusCache[$raceDayUrl]) && $remoteStatusCache[$raceDayUrl];
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