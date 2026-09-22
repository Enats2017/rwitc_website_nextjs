<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *"); // apni baaki API files mein jo CORS lines hain, wahi rakhna

date_default_timezone_set("Asia/Kolkata");

require_once __DIR__ . "/config/config.php";

try {
    $res = $conn->query("SELECT `value` FROM `config` WHERE `id` = 2 LIMIT 1");
    $row = $res->fetch_assoc();

    echo json_encode([
        "success" => true,
        "data" => [
            "raceDay"      => ($row && $row["value"] === "Y"),
            "today"        => date("Y-m-d"),
            "mediaTipsUrl" => STATIC_LIVE_URL . "MEDIATIPS.HTM",
            "updatesUrl"   => STATIC_LIVE_URL . "UPDATES.HTM",
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