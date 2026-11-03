<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

date_default_timezone_set("Asia/Kolkata");

require_once __DIR__ . "/config/config.php";

try {
    $res = $conn->query("SELECT `value` FROM `config` WHERE `id` = 2 LIMIT 1");
    $row = $res->fetch_assoc();

    $scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
    $dir    = rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/\\");
    $proxy  = $scheme . "://" . $_SERVER["HTTP_HOST"] . $dir . "/s3_set_url.php";

    $v = date("YmdHi"); // cache bust

    $notices = [];
    try {
        $nres = $conn->query(
            "SELECT `id`, `btn_text`, `file_url`, `is_video`
             FROM `normal_day_buttons`
             WHERE `is_active` = 'Y' AND `file_url` <> ''
             ORDER BY `id` ASC"
        );

        if ($nres) {
            while ($n = $nres->fetch_assoc()) {
                $notices[] = [
                    "id"    => (int)$n["id"],
                    "text"  => $n["btn_text"],
                    "href"  => $n["file_url"],
                    "video" => ($n["is_video"] === "Y"),
                ];
            }
        }
    } catch (Throwable $ne) {
        $notices = [];
    }

    echo json_encode([
        "success" => true,
        "data" => [
            "raceDay"      => ($row && $row["value"] === "Y"),
            "today"        => date("Y-m-d"),
            "mediaTipsUrl" => $proxy . "?key=" . rawurlencode("uploads/RaceResults/MEDIATIPS.HTM") . "&v=" . $v,
            "updatesUrl"   => $proxy . "?key=" . rawurlencode("uploads/RaceResults/UPDATES.HTM") . "&v=" . $v,
            "notices"      => $notices,
        ],
        "error" => null
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "data"    => null,
        "error"   => "Unable to read race day status."
    ]);
}