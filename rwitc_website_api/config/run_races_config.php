<?php

// ============================================================
// RUN RACES CONFIG
// ------------------------------------------------------------
// Central place for the run_races folder's URL + filesystem
// path, for both local (XAMPP) and live (production) setups.
//
// To deploy: change ONLY the value of IS_LOCAL below.
// Everything else (RUN_RACES_BASE_URL, RUN_RACES_LOCAL_PATH)
// will switch automatically.
// ============================================================

// Set this to false when deploying to the live server
define("IS_LOCAL", false);

if (IS_LOCAL) {

    // ----------------------------------------------------
    // LOCAL (XAMPP) SETTINGS
    // ----------------------------------------------------

    // URL the browser uses to open .htm files directly
    define("RUN_RACES_BASE_URL", "http://localhost/run_races/");

    // Filesystem path PHP uses to check if a file exists
    define("RUN_RACES_LOCAL_PATH", "C:/xampp/htdocs/run_races/");

    // Raceday Report .HTM files of local folder path
    define("RACEDAY_REPORT_DIR", "C:/xampp/htdocs/racedayreports/");

    // Raceday Report .HTM files of public URL (for download link)
    define("RACEDAY_REPORT_PUBLIC_BASE", "http://localhost/racedayreports/");

    // Sweepstake .htm files public URL
    define("STATIC_SWEEPSTAKE_URL", "http://localhost/staticpages/sweepstakes/");

    // Dividends .htm files public URL
    define("STATIC_DIVIDENDS_URL", "http://localhost/staticpages/dividends/");

    define("STATIC_LIVE_URL", "http://localhost/staticpages/live/");

    // Riding Weight
    define("RIDING_WEIGHT_LOCAL_PATH", "C:/xampp/htdocs/rwitc_upload/static/");

    // Record Timings
    define("RECORD_TIMINGS_LOCAL_PATH", "C:/xampp/htdocs/horseracing/");

    // Ratings Change
    define("RATINGSCHANGE_LOCAL_PATH", "C:/xampp/htdocs/staticpages/ratingschange/");

    // Ratings HTM file
    define("RATINGS_FILE_URL", "http://localhost/rwitc_upload/static/RATINGS.HTM");

} else {

    // ----------------------------------------------------
    // LIVE (PRODUCTION) SETTINGS
    // ----------------------------------------------------

    define("RUN_RACES_BASE_URL", "https://rwitc.com/run_races");

    define("RUN_RACES_LOCAL_PATH", realpath(__DIR__ . "/../../run_races"));

    // Raceday Report .HTM files - server filesystem path
    define(
        "RACEDAY_REPORT_DIR",
        realpath(__DIR__ . "/../../staticpages/racedayreports")
    );

    // Raceday Report .HTM files - public URL
    define(
        "RACEDAY_REPORT_PUBLIC_BASE",
        "https://rwitc.com/staticpages/racedayreports/"
    );

    // Sweepstake .htm files public URL
    define(
        "STATIC_SWEEPSTAKE_URL",
        "https://rwitc.com/staticpages/sweepstakes/"
    );

    // Dividends .htm files public URL
    define(
        "STATIC_DIVIDENDS_URL",
        "https://rwitc.com/staticpages/dividends/"
    );

    define(
        "RATINGSCHANGE_LOCAL_PATH",
        realpath(__DIR__ . "/../../staticpages/ratingschange")
    );

    define(
        "STATIC_LIVE_URL",
        "https://www.rwitc.com/rwitc_upload/static/live/"
    );

    define(
        "RIDING_WEIGHT_LOCAL_PATH",
        realpath(__DIR__ . "/../../rwitc_upload/static")
    );

    define(
        "RECORD_TIMINGS_LOCAL_PATH",
        realpath(__DIR__ . "/../../horseracing")
    );

    // Ratings HTM file
    define(
        "RATINGS_FILE_URL",
        "https://rwitc.com/rwitc_upload/static/RATINGS.HTM"
    );
}

define("RATINGSCHANGE_S3_PREFIX", "staticpages/ratingschange/");