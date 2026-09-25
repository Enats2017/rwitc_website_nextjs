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
    "api_tag"        => "jockey_statistics_get"
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

$cacheKey = "jockey_statistics";

if ($security->serveCache($cacheKey)) {
    if (isset($conn)) { $conn->close(); }
    if (is_resource($handle)) { fclose($handle); }
    exit;
}

try {

    // -------------------------------------------------------
    // 1) Pehle run_races/jockey_statistics.html (old page jaisa)
    //    Is file me heading + table dono already hote hain,
    //    isliye as_on null bhejte hain.
    // -------------------------------------------------------
    $localFile = rtrim((string) RUN_RACES_LOCAL_PATH, "/\\") . "/jockey_statistics.html";

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
        // 2) Fallback: DB (old getMaxDate + getJockeyStats)
        // ---------------------------------------------------

        // getMaxDate("RACEDATE", "fhorse5")
        $asOn = null;
        $dateRes = $conn->query("SELECT MAX(`RACEDATE`) AS d FROM fhorse5");
        if ($dateRes && ($dateRow = $dateRes->fetch_assoc()) && !empty($dateRow["d"])) {
            $asOn = date("d-M-Y", strtotime($dateRow["d"]));
        }

        // getJockeyStats()
        $result = $conn->query("
            SELECT `NAME`, `LMOUNTS`, `LWIN`, `LSEC`, `LTHI`, `LFOU`
            FROM statj
            ORDER BY `LWIN` DESC, `LSEC` DESC, `LTHI` DESC, `LFOU` DESC
        ");

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $wins   = (int) $row["LWIN"];
            $mounts = (int) $row["LMOUNTS"];

            $rows[] = [
                "jockey"      => $row["NAME"],
                "wins"        => $wins,
                "second"      => (int) $row["LSEC"],
                "third"       => (int) $row["LTHI"],
                "fourth"      => (int) $row["LFOU"],
                "totalMounts" => $mounts,
                "winPercent"  => $mounts > 0 ? round(($wins * 100) / $mounts, 2) : 0
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

    $security->logLine("JOCKEY_STATS_API_ERROR | " . $error->getMessage());
    $security->respondError("Internal server error", 500);

} finally {

    if (isset($conn)) { $conn->close(); }
    if (isset($handle) && is_resource($handle)) { fclose($handle); }
}