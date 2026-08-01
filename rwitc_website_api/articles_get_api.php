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
    "api_tag"        => "race_media_get"
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
// RETURN CACHE IF AVAILABLE
// --------------------------------------------------

if ($security->serveCache("race_media")) {
    exit;
}

// --------------------------------------------------
// CONSTANTS
// --------------------------------------------------

const PRE_RACE_LIMIT   = 4;
const POST_RACE_LIMIT  = 4;
const TRACK_WORK_LIMIT = 18;

// --------------------------------------------------
// CHECK IF REMOTE FILE EXISTS
// --------------------------------------------------

function remoteFileExists($url)
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_NOBODY         => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT      => "RWITC-Race-Media-API/1.0"
    ]);

    curl_exec($ch);

    $statusCode = curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );

    curl_close($ch);

    return $statusCode >= 200 && $statusCode < 400;
}

// --------------------------------------------------
// RUN A QUERY WITH A DYNAMIC "IN (...)" CLAUSE
// Returns the mysqli_result. $dates must be non-empty.
// --------------------------------------------------

function fetchByDatesIn($conn, $sql, array $dates)
{
    $placeholders = implode(",", array_fill(0, count($dates), "?"));
    $sql = sprintf($sql, $placeholders);

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        throw new Exception($conn->error);
    }

    $types = str_repeat("s", count($dates));

    // bind_param needs references
    $refs = [];
    $refs[] = $types;
    foreach ($dates as $key => $value) {
        $refs[] = &$dates[$key];
    }

    call_user_func_array([$stmt, "bind_param"], $refs);

    $stmt->execute();

    $result = $stmt->get_result();
    $stmt->close();

    return $result;
}

// --------------------------------------------------
// FETCH DATA
// --------------------------------------------------

try {

    $runRacesBaseUrl = "http://localhost/run_races/";

    // ==================================================
    // PRE-RACE
    // ==================================================

    $preRaceSql = "
        SELECT racedate
        FROM erp_pre_race
        GROUP BY racedate
        ORDER BY racedate DESC
        LIMIT " . PRE_RACE_LIMIT . "
    ";

    $preRaceResult = $conn->query($preRaceSql);

    if ($preRaceResult === false) {
        throw new Exception($conn->error);
    }

    $preRace = [];

    while ($row = $preRaceResult->fetch_assoc()) {

        $date = $row["racedate"];

        // Old logic:
        // Dates <= 25-09-2022 always show the dot
        $isOldDate = strtotime($date) <= 1664064000;

        // File URLs
        $handicapsUrl =
            $runRacesBaseUrl .
            "Handicaps_" .
            $date .
            ".html";

        $acceptancesUrl =
            $runRacesBaseUrl .
            "Acceptance_" .
            $date .
            ".html";

        $declarationsUrl =
            $runRacesBaseUrl .
            "Declarations_" .
            $date .
            ".html";

        $raceCardUrl =
            $runRacesBaseUrl .
            "Race_Card_" .
            $date .
            ".html";

        $preRace[] = [

            "racedate" => $date,

            "handicaps" => [
                "available" => $isOldDate ||
                    remoteFileExists($handicapsUrl),
                "url" => $handicapsUrl
            ],

            "acceptances" => [
                "available" => $isOldDate ||
                    remoteFileExists($acceptancesUrl),
                "url" => $acceptancesUrl
            ],

            "declarations" => [
                "available" => $isOldDate ||
                    remoteFileExists($declarationsUrl),
                "url" => $declarationsUrl
            ],

            "raceCard" => [
                "available" => $isOldDate ||
                    remoteFileExists($raceCardUrl),
                "url" => $raceCardUrl
            ]
        ];
    }


    // ==================================================
    // POST-RACE
    // ==================================================

    $postRaceSql = "
        SELECT racedate
        FROM erp_post_race
        GROUP BY racedate
        ORDER BY racedate DESC
        LIMIT " . POST_RACE_LIMIT . "
    ";

    $postRaceResult = $conn->query($postRaceSql);

    if ($postRaceResult === false) {
        throw new Exception($conn->error);
    }

    $postRaceDates = [];

    while ($row = $postRaceResult->fetch_assoc()) {
        $postRaceDates[] = $row["racedate"];
    }

    $postRace = [];

    if (!empty($postRaceDates)) {

        // ----------------------------------------------
        // BATCH: RACEDAY REPORT (1 query instead of N)
        // ----------------------------------------------

        $raceDayReportDates = [];

        $raceDayReportResult = fetchByDatesIn(
            $conn,
            "SELECT racedate FROM raceday_report WHERE racedate IN (%s)",
            $postRaceDates
        );

        while ($row = $raceDayReportResult->fetch_assoc()) {
            $raceDayReportDates[$row["racedate"]] = true;
        }

        // ----------------------------------------------
        // BATCH: PHOTOS (1 query instead of N)
        // ----------------------------------------------

        $photoDates = [];

        $photoResult = fetchByDatesIn(
            $conn,
            "SELECT RACEDATE FROM gallery WHERE sponsor_id = '1' AND RACEDATE IN (%s)",
            $postRaceDates
        );

        while ($row = $photoResult->fetch_assoc()) {
            $photoDates[$row["RACEDATE"]] = true;
        }

        // ----------------------------------------------
        // BATCH: VIDEOS (1 query instead of N)
        // ----------------------------------------------

        $videosByDate = [];

        $videoResult = fetchByDatesIn(
            $conn,
            "SELECT racedate, chan, cat FROM videos WHERE racedate IN (%s)",
            $postRaceDates
        );

        while ($row = $videoResult->fetch_assoc()) {
            // Keep the first match per date, same as the old LIMIT 1 behaviour
            if (!isset($videosByDate[$row["racedate"]])) {
                $videosByDate[$row["racedate"]] = $row;
            }
        }

        // ----------------------------------------------
        // BUILD POST-RACE DATA (no DB calls in this loop)
        // ----------------------------------------------

        foreach ($postRaceDates as $date) {

            // RACE RESULTS
            $raceResultsUrl =
                $runRacesBaseUrl .
                "Race_results_" .
                $date .
                ".html";

            // Same as old website logic:
            // Only applicable from 22-09-2014 onwards
            $raceResultsAvailable =
                strtotime($date) >= 1411344000 &&
                remoteFileExists($raceResultsUrl);

            // RATING CHANGE
            $ratingChangeUrl =
                $runRacesBaseUrl .
                "Rating_change_" .
                $date .
                ".html";

            $ratingChangeAvailable =
                remoteFileExists($ratingChangeUrl);

            // RACEDAY REPORT
            $raceDayReportAvailable =
                strtotime($date) >= 1411344000 &&
                isset($raceDayReportDates[$date]);

            $raceDayReportUrl =
                "https://www.rwitc.com/new/raceDayReport.php?date=" .
                urlencode($date);

            // PHOTOS
            $photosAvailable = isset($photoDates[$date]);

            $photosUrl =
                "https://www.rwitc.com/new/photoGallery.php?date=" .
                urlencode($date);

            // VIDEOS
            $videosAvailable = false;
            $videoUrl = null;

            if (isset($videosByDate[$date])) {

                $videosAvailable = true;
                $video = $videosByDate[$date];

                // Old video URL
                if (strtotime($date) < strtotime("2015-07-23")) {

                    $videoUrl =
                        "https://mumbairaces.com/index.php?chan=" .
                        urlencode($video["chan"]) .
                        "&cat=" .
                        urlencode($video["cat"]);

                } else {

                    // New video archive URL
                    $videoUrl =
                        "https://rwitcraces.com/RaceArchives.aspx?d=" .
                        date(
                            "dmY",
                            strtotime($date)
                        );
                }
            }

            $postRace[] = [

                "racedate" => $date,

                "raceResults" => [
                    "available" => $raceResultsAvailable,
                    "url" => $raceResultsUrl
                ],

                "ratingChange" => [
                    "available" => $ratingChangeAvailable,
                    "url" => $ratingChangeUrl
                ],

                "raceDayReport" => [
                    "available" => $raceDayReportAvailable,
                    "url" => $raceDayReportUrl
                ],

                "photos" => [
                    "available" => $photosAvailable,
                    "url" => $photosUrl
                ],

                "videos" => [
                    "available" => $videosAvailable,
                    "url" => $videoUrl
                ]
            ];
        }
    }


    // ==================================================
    // TRACK WORK
    // ==================================================

    $trackWorkSql = "
        SELECT
            id,
            trackwork_date,
            trackwork,
            published
        FROM trackwork
        WHERE published = 'Y'
        ORDER BY trackwork_date DESC
        LIMIT " . TRACK_WORK_LIMIT . "
    ";

    $trackWorkResult =
        $conn->query($trackWorkSql);

    if ($trackWorkResult === false) {
        throw new Exception($conn->error);
    }

    $trackWork = [];

    while (
        $row =
        $trackWorkResult->fetch_assoc()
    ) {
        $trackWork[] = $row;
    }


    // --------------------------------------------------
    // FINAL RESPONSE
    // --------------------------------------------------

    $data = [
        "preRace"   => $preRace,
        "postRace"  => $postRace,
        "trackWork" => $trackWork
    ];

    $security->respondAndCache(
        "race_media",
        $data
    );

} catch (Throwable $error) {

    $security->logLine(
        "RACE_MEDIA_API_ERROR | " .
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

    if (
        isset($handle) &&
        is_resource($handle)
    ) {
        fclose($handle);
    }
}