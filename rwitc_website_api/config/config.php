<?php

$host = "localhost";
// $user = "app_user";
$user = "root";

// live
// $password = 'ho{HslC)jWaky${L';

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

// ============================================================
// AWS S3 CONFIG
// ============================================================

require_once __DIR__ . "/run_races_config.php";

// live
$envFile = __DIR__ . "/../../.env.local";

// local
// $envFile = __DIR__ . "/../../rwitc_website_nextjs/.env.local";

if (!file_exists($envFile)) {
    die("Environment configuration file not found.");
}
$env = parse_ini_file(
    $envFile,
    false,
    INI_SCANNER_RAW
);

if ($env === false) {
    die("Unable to read environment configuration.");
}
// ------------------------------------------------------------
// AWS Credentials
// ------------------------------------------------------------
define( "AWS_ACCESS_KEY_ID", $env["AWS_ACCESS_KEY_ID"]);
define( "AWS_SECRET_ACCESS_KEY", $env["AWS_SECRET_ACCESS_KEY"]);
define( "AWS_REGION", $env["AWS_REGION"]);
// ------------------------------------------------------------
// AWS Bucket
// ------------------------------------------------------------
// Current server = test.rwitc.com
// Therefore use TEST bucket.
// ------------------------------------------------------------
define( "AWS_BUCKET", $env["AWS_TEST_BUCKET"]);
define("WEBSITE_API_BASE_URL", $env["NEXT_PUBLIC_API_URL"]);