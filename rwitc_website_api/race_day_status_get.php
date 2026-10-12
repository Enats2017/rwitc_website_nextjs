<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");

date_default_timezone_set("Asia/Kolkata");

require_once __DIR__ . "/config/config.php";

try {
    $res = $conn->query("SELECT `value` FROM `config` WHERE `id` = 2 LIMIT 1");
    $row = $res->fetch_assoc();

    // Apni proxy file ka URL (S3 ka URL kahin nahi dikhega)
    $scheme = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
    $dir    = rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/\\");
    $proxy  = $scheme . "://" . $_SERVER["HTTP_HOST"] . $dir . "/s3_set_url.php";

    $v = date("YmdHi"); // cache bust

    echo json_encode([
        "success" => true,
        "data" => [
            "raceDay"      => ($row && $row["value"] === "Y"),
            "today"        => date("Y-m-d"),
            "mediaTipsUrl" => $proxy . "?key=" . rawurlencode("uploads/RaceResults/MEDIATIPS.HTM") . "&v=" . $v,
            "updatesUrl"   => $proxy . "?key=" . rawurlencode("uploads/RaceResults/UPDATES.HTM") . "&v=" . $v,
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