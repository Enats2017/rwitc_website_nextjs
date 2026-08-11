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
    define("RUN_RACES_BASE_URL", "http://localhost/run_races");

    // Filesystem path PHP uses to check if a file exists
    define("RUN_RACES_LOCAL_PATH", "C:/xampp/htdocs/run_races");

    // Raceday Report .HTM files of local folder path
    define("RACEDAY_REPORT_DIR", "C:/xampp/htdocs/racedayreports/");

    // Raceday Report .HTM files of public URL (for download link)
    define("RACEDAY_REPORT_PUBLIC_BASE", "http://localhost/racedayreports/");

    // Sweepstake .htm files public URL (for file_url in API response)
    define("STATIC_SWEEPSTAKE_URL", "http://localhost/staticpages/sweepstakes/");

    // Dividends .htm files public URL (for API response)
    define("STATIC_DIVIDENDS_URL", "http://localhost/staticpages/dividends/");
} else {

    // ----------------------------------------------------
    // LIVE (PRODUCTION) SETTINGS
    // ----------------------------------------------------
    // TODO: confirm the exact production URL + server path
    // before going live, then update these two lines only.

    define("RUN_RACES_BASE_URL", "http://91.99.229.154/rwitc-website/run_races");

    define("RUN_RACES_LOCAL_PATH", $_SERVER["DOCUMENT_ROOT"] . "run_races");

    // Sweepstake .htm files public URL (production)
    define("STATIC_SWEEPSTAKE_URL", "http://91.99.229.154/rwitc-website/staticpages/sweepstakes/");

    // Dividends .htm files public URL (production)
    define("STATIC_DIVIDENDS_URL", "http://91.99.229.154/rwitc-website/staticpages/dividends/");
}
