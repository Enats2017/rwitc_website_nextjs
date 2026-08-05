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
define("IS_LOCAL", true);   

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


} else {

    // ----------------------------------------------------
    // LIVE (PRODUCTION) SETTINGS
    // ----------------------------------------------------
    // TODO: confirm the exact production URL + server path
    // before going live, then update these two lines only.

    define("RUN_RACES_BASE_URL", "https://rwitc.com/run_races");

    define("RUN_RACES_LOCAL_PATH", $_SERVER["DOCUMENT_ROOT"] . "/run_races");

}