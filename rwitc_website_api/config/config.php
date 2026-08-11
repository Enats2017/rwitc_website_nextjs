<?php

$host = "localhost";
$user = "root"; 
// live 
// $password = "vcare@2025";

// local 
$password = "";
$database = "rwitc_website";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {

    $conn = new mysqli(
        $host,
        $user,
        $password,
        $database
    );

    $conn->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {

    http_response_code(500);

    die(json_encode([
        "success" => false,
        "data"    => null,
        "error"   => "Database connection failed.",
        "message" => $e->getMessage()
    ]));

}

?>