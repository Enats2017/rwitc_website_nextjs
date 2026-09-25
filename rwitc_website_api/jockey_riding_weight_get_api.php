<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/config/run_races_config.php";
require_once __DIR__ . "/ApiSecurity.php";

$logDir = __DIR__ . "/logs";
if (!is_dir($logDir)) {
    @mkdir($logDir, 0750, true);
}
$handle = @fopen($logDir . "/api_logs.txt", "a+");

$security = new ApiSecurity($handle, [
    "rate_limit"     => 60,
    "rate_window"    => 60,
    "cache_ttl"      => 45,
    "cache_dir"      => __DIR__ . "/cache",
    "rate_limit_dir" => __DIR__ . "/rate_limits",
    "api_tag"        => "jockey_riding_weight_get"
]);

if (!$security->gate()) {
    if (isset($conn)) { $conn->close(); }
    if (is_resource($handle)) { fclose($handle); }
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    $security->respondError("Method not allowed", 405);
    if (isset($conn)) { $conn->close(); }
    if (is_resource($handle)) { fclose($handle); }
    exit;
}

$cacheKey = "jockey_riding_weight";

if ($security->serveCache($cacheKey)) {
    if (isset($conn)) { $conn->close(); }
    if (is_resource($handle)) { fclose($handle); }
    exit;
}

try {

    // -------------------------------------------------------
    // 1) Pehle rwitc_upload/static/RIDINGWEIGHT.HTM (old page jaisa)
    //    Is file me heading + saari tables already hoti hain,
    //    isliye as_on null bhejte hain.
    // -------------------------------------------------------
    $localFile = rtrim((string) RIDING_WEIGHT_LOCAL_PATH, "/\\") . "/RIDINGWEIGHT.HTM";

    if (is_file($localFile)) {

        $html = file_get_contents($localFile);

        if ($html === false || trim($html) === "") {
            throw new Exception("Unable to read " . $localFile);
        }

        $security->respondAndCache($cacheKey, [
            "mode"  => "html",
            "as_on" => null,
            "html"  => $html,
            "rows"  => []
        ]);

    } else {

        // ---------------------------------------------------
        // 2) Fallback: DB se riding weight rows
        //    NOTE: table/column names apne DB schema ke
        //    hisaab se yahan adjust kar lena.
        // ---------------------------------------------------

        $asOn = null;
        $dateRes = $conn->query("SELECT MAX(`RACEDATE`) AS d FROM fhorse5");
        if ($dateRes && ($dateRow = $dateRes->fetch_assoc()) && !empty($dateRow["d"])) {
            $asOn = date("d-M-Y", strtotime($dateRow["d"]));
        }

        $result = $conn->query("
            SELECT `CATEGORY`, `SRNO`, `NAME`, `WEIGHT`, `ALLOWANCE`, `WINNERS`, `TRAINER`
            FROM jockeyridingweight
            ORDER BY `CATEGORY` ASC, `SRNO` ASC
        ");

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = [
                "category"  => $row["CATEGORY"],
                "no"        => (int) $row["SRNO"],
                "name"      => $row["NAME"],
                "weight"    => $row["WEIGHT"],
                "allowance" => $row["ALLOWANCE"],
                "winners"   => $row["WINNERS"] !== null ? (int) $row["WINNERS"] : null,
                "trainer"   => $row["TRAINER"]
            ];
        }

        $security->respondAndCache($cacheKey, [
            "mode"  => "json",
            "as_on" => $asOn,
            "html"  => "",
            "rows"  => $rows
        ]);
    }

} catch (Throwable $error) {

    $security->logLine("JOCKEY_RIDING_WEIGHT_API_ERROR | " . $error->getMessage());
    $security->respondError("Internal server error", 500);

} finally {

    if (isset($conn)) { $conn->close(); }
    if (isset($handle) && is_resource($handle)) { fclose($handle); }
}