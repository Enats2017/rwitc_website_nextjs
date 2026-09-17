<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// Load database connection
require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/config/run_races_config.php";

// Load ApiSecurity class
require_once __DIR__ . "/ApiSecurity.php";
// AWS SDK (bucket is private, so we need authenticated reads for S3)
if (!class_exists('Aws\S3\S3Client')) {
    require_once __DIR__ . "/../vendor/autoload.php";
}

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

$s3Client = null;

try {
    $s3Client = new S3Client([
        "version"     => "latest",
        "region"      => AWS_REGION,
        "credentials" => [
            "key"    => AWS_ACCESS_KEY_ID,
            "secret" => AWS_SECRET_ACCESS_KEY
        ]
    ]);
} catch (Throwable $e) {
    $s3Client = null;
}
// Make sure the class was loaded correctly
if (!class_exists("ApiSecurity")) {
    http_response_code(500);

    echo json_encode([
        "success" => false,
        "data"    => null,
        "error"   => "ApiSecurity class could not be loaded"
    ]);

    exit;
}

// Create logs directory
$logDir = __DIR__ . "/logs";

if (!is_dir($logDir)) {
    @mkdir($logDir, 0750, true);
}

// Open log file
$handle = @fopen(
    $logDir . "/api_logs.txt",
    "a+"
);

// Initialize API Security
$security = new ApiSecurity($handle, [
    "rate_limit"     => 60,
    "rate_window"    => 60,
    "cache_ttl"      => 45,
    "cache_dir"      => __DIR__ . "/cache",
    "rate_limit_dir" => __DIR__ . "/rate_limits",
    "api_tag"        => "handicaps_get"
]);

// Apply rate limiting
if (!$security->gate()) {

    if (isset($conn)) {
        $conn->close();
    }

    if (is_resource($handle)) {
        fclose($handle);
    }

    exit;
}

// Only allow GET requests
if ($_SERVER["REQUEST_METHOD"] !== "GET") {

    $security->respondError(
        "Method not allowed",
        405
    );

    if (isset($conn)) {
        $conn->close();
    }

    if (is_resource($handle)) {
        fclose($handle);
    }

    exit;
}

// -------------------------------------------------------------
// Read + sanitize query params
// -------------------------------------------------------------
// ?date=YYYY-MM-DD  (required - matches the SQL branch of the
//                     original page, i.e. date <= 2022-09-25)
// -------------------------------------------------------------

$date = "";

if (isset($_GET["date"])) {
    $date = trim($_GET["date"]);
}

if ($date === "") {
    $security->respondError("date is required (format YYYY-MM-DD)", 400);

    if (isset($conn)) {
        $conn->close();
    }

    if (is_resource($handle)) {
        fclose($handle);
    }

    exit;
}

$d = DateTime::createFromFormat("Y-m-d", $date);

if (!$d || $d->format("Y-m-d") !== $date) {
    $security->respondError("Invalid date format, expected YYYY-MM-DD", 400);

    if (isset($conn)) {
        $conn->close();
    }

    if (is_resource($handle)) {
        fclose($handle);
    }

    exit;
}

// ============================================================
// >>> CHANGE START — HTML ARCHIVE SUPPORT ADDED (date > 2022-09-25)
// ============================================================

// Dates after 2022-09-25 are served from a static
// Handicaps_<date>.html archive file instead of the DB — same as
// the live page, which does a plain `include` of this file with
// no parsing. We mirror that here: read the file as-is and hand
// the raw markup back to the frontend to render directly
// (see Handicaps.js "html" mode).
if ($date > "2022-09-25") {

    $cacheKey = "handicaps_html_" . $date;

    // Return cached response if available
    if ($security->serveCache($cacheKey)) {

        if (isset($conn)) {
            $conn->close();
        }

        if (is_resource($handle)) {
            fclose($handle);
        }

        exit;
    }

    try {

        /*
         * ======================================================
         * FIRST: OLD LOCAL FILE
         * ======================================================
         */

        $htmlFile = RUN_RACES_LOCAL_PATH . "/Handicaps_" . $date . ".html";

        $htmlContent = false;
        $source = "";

        if (file_exists($htmlFile)) {

            $source = "LOCAL_RUN_RACES";

            $htmlContent = file_get_contents($htmlFile);

            if ($htmlContent === false) {
                throw new Exception(
                    "Unable to read local handicaps file: " . $htmlFile
                );
            }
        }


        /*
         * ======================================================
         * SECOND: NEW S3 FILE
         *
         * Local file not found -> DB -> S3
         * ======================================================
         */

        if ($htmlContent === false) {
            $source = "DB_S3";

            $stmt = $conn->prepare("
                SELECT file_url
                FROM run_race_details
                WHERE `date` = ?
                AND `type` = 'handicaps'
                AND `race_type` = 'pre_race'
                LIMIT 1
            ");

            if ($stmt === false) {
                throw new Exception($conn->error);
            }

            $stmt->bind_param("s", $date);

            if (!$stmt->execute()) {
                throw new Exception($conn->error);
            }

            $result = $stmt->get_result();

            if (!$result || $result->num_rows == 0) {

                $stmt->close();

                $security->respondError(
                    "No handicaps data found for this date",
                    404
                );

                exit;
            }

            $row = $result->fetch_assoc();
            $s3Url = $row["file_url"];

            $stmt->close();

                       /*
             * Read the actual HTML from S3 using an authenticated
             * request. Bucket is private, so plain file_get_contents()
             * on the public S3 URL returns 403 — must go via the SDK.
             */
            if ($s3Client === null) {
                throw new Exception("S3 client is not available");
            }

            $s3Path = parse_url($s3Url, PHP_URL_PATH);
            $s3Key  = ltrim(rawurldecode($s3Path), '/');

            try {
                $s3Object = $s3Client->getObject([
                    "Bucket" => AWS_BUCKET,
                    "Key"    => $s3Key
                ]);

                $htmlContent = (string) $s3Object["Body"];

            } catch (AwsException $e) {
                throw new Exception(
                    "Unable to read handicaps file from S3: " . $e->getMessage()
                );
            }

            if ($htmlContent === "" || $htmlContent === false) {
                throw new Exception(
                    "Unable to read handicaps file from S3 (empty content)"
                );
            }
        }


        /*
         * ======================================================
         * DOWNLOAD LINK
         *
         * Always expose the old website URL.
         * Never expose the S3 URL to frontend.
         * ======================================================
         */

        $htmFile = RUN_RACES_LOCAL_PATH . "/Handicaps_" . $date . ".htm";

        $downloadAvailable = false;

        /*
         * Old .htm file exists locally
         */
        if (file_exists($htmFile)) {

            $downloadAvailable = true;
        } else {

            /*
             * New file:
             * Check DB record exists.
             */
            $stmt = $conn->prepare("
                SELECT id
                FROM run_race_details
                WHERE `date` = ?
                AND `type` = 'handicaps'
                AND `race_type` = 'pre_race'
                LIMIT 1
            ");

            if ($stmt === false) {
                throw new Exception($conn->error);
            }

            $stmt->bind_param("s", $date);

            if (!$stmt->execute()) {
                throw new Exception($conn->error);
            }

            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $downloadAvailable = true;
            }

            $stmt->close();
        }

        /*
         * IMPORTANT:
         * Frontend always receives the old URL format.
         */
        $downloadFile = $downloadAvailable
            ? RUN_RACES_BASE_URL . "/Handicaps_" . $date . ".htm"
            : null;


        /*
         * ======================================================
         * RESPONSE
         * ======================================================
         */

        $response = [
            "date"                => $date,
            "mode"                => "html",
            "source"              => $source,
            "html"                => $htmlContent,
            "download_file"       => $downloadFile,
            "download_available"  => $downloadAvailable
        ];

        $security->respondAndCache($cacheKey, $response);
    } catch (Throwable $error) {

        $security->logLine(
            "HANDICAPS_HTML_READ_ERROR | " . $error->getMessage()
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

// ============================================================
// <<< CHANGE END
// ============================================================

$cacheKey = "handicaps_" . $date;

// Return cached response if available
if ($security->serveCache($cacheKey)) {

    if (isset($conn)) {
        $conn->close();
    }

    if (is_resource($handle)) {
        fclose($handle);
    }

    exit;
}

try {

    // -----------------------------------------------------------
    // 1) Races for this date (prospect table)
    // -----------------------------------------------------------
    $stmt = $conn->prepare("
        SELECT UNIX_TIMESTAMP(p.`DATE`) as DATE, p.`SRNO`, p.`NAME` as RACENAME,
               p.`DAYNARR`, p.`NARRENT`, p.`DISTANCE`, p.`FJOCK`, p.`HTERMS`,
               p.`RACECAT`, p.`GRADE`, p.`RAISELOWER`, p.`RAISEACP1`,
               p.`RAISEACP2`, p.`RAISEACP3`, p.`RACETIME1`, p.`RACETIME2`,
               p.`VOID_HACP`, p.`VOID_ACCP`
        FROM prospect p
        WHERE p.`DATE` = ?
        ORDER BY p.`SRNO` ASC
    ");

    if ($stmt === false) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param("s", $date);
    $stmt->execute();

    $result = $stmt->get_result();
    $prospectData = [];

    while ($row = $result->fetch_assoc()) {
        $prospectData[] = $row;
    }

    $stmt->close();

    // -----------------------------------------------------------
    // 2) Distinct SRNO list from weights for this date
    // -----------------------------------------------------------
    $stmt = $conn->prepare("
        SELECT DISTINCT(w.`SRNO`)
        FROM weights w
        WHERE w.`RACEDATE` = ? AND w.`SRNO` > 0
        ORDER BY w.`SRNO` ASC
    ");

    if ($stmt === false) {
        throw new Exception($conn->error);
    }

    $stmt->bind_param("s", $date);
    $stmt->execute();

    $result = $stmt->get_result();
    $weightsSRNOList = [];

    while ($row = $result->fetch_assoc()) {
        $weightsSRNOList[] = $row["SRNO"];
    }

    $stmt->close();

    // -----------------------------------------------------------
    // 3) Weights (+ horse breeding info) per race, same positional
    //    pairing as the original page: race at index $i is paired
    //    with weightsSRNOList[$i]. Unlike the original, we guard
    //    every access with isset() instead of assuming the two
    //    lists line up, so a mismatch can't throw an undefined
    //    index notice or pull the wrong SRNO.
    // -----------------------------------------------------------
    $races = [];

    foreach ($prospectData as $i => $prospect) {

        $weightsData = [];
        $srNo = isset($weightsSRNOList[$i]) ? $weightsSRNOList[$i] : null;

        if ($srNo !== null) {


            // .. added Age, Color, SEX....

            $stmt = $conn->prepare("
                SELECT w.`SRNOCTRL`, UNIX_TIMESTAMP(w.`RACEDATE`) as RACEDATE,
                w.`WEIGHT`, w.`NAME`, w.`SRNO`, w.`HORSESEQ`,
                w.`ACCPFLAG`, w.`HRATING`, w.`RAISELOWER`, w.`FRT`,
                w.`SSBAN`, w.`VOBAN`, w.`MKBAN`, w.`SSREQD`, w.`SHOE`,
                w.`SHOEDET`, w.`BITSDET`, w.`SORDER`, w.`TRAINERNME`,
                h.SIRE, h.DAM, h.DAMNAT, h.AGE, h.SEX, h.COLOR
                FROM weights w
                INNER JOIN hmaster h ON w.`HORSESEQ` = h.`HORSESEQ`
                WHERE w.`RACEDATE` = ? AND w.`SRNO` = ?
                GROUP BY w.`HORSESEQ`
                ORDER BY w.`SORDER` ASC
            ");

            if ($stmt === false) {
                throw new Exception($conn->error);
            }

            $stmt->bind_param("si", $date, $srNo);
            $stmt->execute();

            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $weightsData[] = $row;
            }

            $stmt->close();
        }

        // Collect ban lists the same way the page does, but as
        // arrays instead of comma-joined strings baked into HTML.
        $ssBan = [];
        $voBan = [];
        $mkBan = [];

        foreach ($weightsData as $weight) {
            if (isset($weight["SSBAN"]) && $weight["SSBAN"] === "Y") {
                $ssBan[] = $weight["NAME"];
            }

            if (isset($weight["VOBAN"]) && $weight["VOBAN"] === "Y") {
                $voBan[] = $weight["NAME"];
            }

            if (isset($weight["MKBAN"]) && $weight["MKBAN"] === "Y") {
                $mkBan[] = $weight["NAME"];
            }
        }

        $races[] = [
            "srno"                     => $prospect["SRNO"],
            "race_name"                => $prospect["RACENAME"],
            "day_narr"                 => $prospect["DAYNARR"],
            "narr_ent"                 => $prospect["NARRENT"],
            "distance"                 => $prospect["DISTANCE"],
            "foreign_jockeys_eligible" => ((int) $prospect["FJOCK"]) === 1,
            "hterms"                   => $prospect["HTERMS"],
            "race_cat"                 => $prospect["RACECAT"],
            "grade"                    => $prospect["GRADE"],
            "raise_lower"              => $prospect["RAISELOWER"],
            "raise_acp1"               => $prospect["RAISEACP1"],
            "raise_acp2"               => $prospect["RAISEACP2"],
            "raise_acp3"               => $prospect["RAISEACP3"],
            "race_time1"               => $prospect["RACETIME1"],
            "race_time2"               => $prospect["RACETIME2"],
            "void_hacp"                => $prospect["VOID_HACP"],
            "void_accp"                => $prospect["VOID_ACCP"],
            "weights"                  => $weightsData,
            "ss_ban_horses"            => $ssBan,
            "vo_ban_horses"            => $voBan,
            "mk_ban_horses"            => $mkBan
        ];
    }

    // -----------------------------------------------------------
    // Download file link + formatted narrative, same derivation
    // as the original page
    // -----------------------------------------------------------
    $htmFile = RUN_RACES_LOCAL_PATH . "/Handicaps_" . $date . ".htm";
    $downloadAvailable = file_exists($htmFile);
    $downloadFile = $downloadAvailable
        ? RUN_RACES_BASE_URL . "/Handicaps_" . $date . ".htm"
        : null;
    $dayNarrative = null;
    $formattedDate = null;

    if (isset($prospectData[0])) {
        $dayNarrative = $prospectData[0]["DAYNARR"];
        $formattedDate = date("l jS F Y", $prospectData[0]["DATE"]);
    }

    $response = [
        "date"               => $date,
        "mode"               => "json",
        "download_file"      => $downloadFile,
        "download_available" => $downloadAvailable,
        "day_narrative"      => $dayNarrative,
        "formatted_date"     => $formattedDate,
        "race_count"         => count($races),
        "races"              => $races
    ];

    // Return successful response and save it in cache
    $security->respondAndCache($cacheKey, $response);
} catch (Throwable $error) {

    // Save actual error in log file
    $security->logLine(
        "HANDICAPS_API_ERROR | " .
            $error->getMessage()
    );

    // Return safe error response
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
    if (isset($handle) && is_resource($handle)) {
        fclose($handle);
    }
}
