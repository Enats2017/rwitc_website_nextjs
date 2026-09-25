<?php

$host = "localhost";
$user = "app_user";
// $user = "root";

// LIVE
$password = 'ho{HslC)jWaky${L';

// LOCAL
// $password = "";
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


// ENVIRONMENT FILE
// LIVE
$envFile = __DIR__ . "/../../.env.local";

// LOCAL
// $envFile = __DIR__ . "/../../rwitc_website_nextjs/.env.local";

if (!file_exists($envFile)) {

    http_response_code(500);

    die(json_encode([
        "success" => false,
        "data"    => null,
        "error"   => "Environment configuration file not found."
    ]));

}


// ============================================================
// READ .env.local
// ============================================================

$env = [];

$lines = file(
    $envFile,
    FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
);

if ($lines === false) {

    http_response_code(500);

    die(json_encode([
        "success" => false,
        "data"    => null,
        "error"   => "Unable to read environment configuration file."
    ]));

}


// ============================================================
// PARSE ENVIRONMENT VARIABLES
// ============================================================

foreach ($lines as $line) {

    // Remove BOM if present
    $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);

    $line = trim($line);

    // Ignore empty lines
    if ($line === '') {
        continue;
    }

    // Ignore comments
    if (str_starts_with($line, '#')) {
        continue;
    }

    // Ignore invalid lines without =
    if (!str_contains($line, '=')) {
        continue;
    }

    // Split only at the FIRST =
    [$key, $value] = explode('=', $line, 2);

    $key = trim($key);
    $value = trim($value);

    // Ignore empty key
    if ($key === '') {
        continue;
    }

    // Remove surrounding double quotes
    if (
        strlen($value) >= 2 &&
        $value[0] === '"' &&
        $value[strlen($value) - 1] === '"'
    ) {
        $value = substr($value, 1, -1);
    }

    // Remove surrounding single quotes
    elseif (
        strlen($value) >= 2 &&
        $value[0] === "'" &&
        $value[strlen($value) - 1] === "'"
    ) {
        $value = substr($value, 1, -1);
    }

    $env[$key] = $value;
}


// ============================================================
// REQUIRED ENVIRONMENT VARIABLES
// ============================================================

$requiredEnv = [
    "AWS_ACCESS_KEY_ID",
    "AWS_SECRET_ACCESS_KEY",
    "AWS_REGION",
    "AWS_TEST_BUCKET",
    "NEXT_PUBLIC_API_URL"
];

foreach ($requiredEnv as $requiredKey) {

    if (!array_key_exists($requiredKey, $env) || $env[$requiredKey] === '') {

        http_response_code(500);

        die(json_encode([
            "success" => false,
            "data"    => null,
            "error"   => "Missing environment configuration: " . $requiredKey
        ]));

    }
}


// ============================================================
// AWS CREDENTIALS
// ============================================================

define(
    "AWS_ACCESS_KEY_ID",
    $env["AWS_ACCESS_KEY_ID"]
);

define(
    "AWS_SECRET_ACCESS_KEY",
    $env["AWS_SECRET_ACCESS_KEY"]
);

define(
    "AWS_REGION",
    $env["AWS_REGION"]
);


// ============================================================
// AWS BUCKET
// ============================================================

// Current server = test.rwitc.com
// Therefore use TEST bucket.

define(
    "AWS_BUCKET",
    $env["AWS_TEST_BUCKET"]
);


// ============================================================
// WEBSITE API BASE URL
// ============================================================

define(
    "WEBSITE_API_BASE_URL",
    $env["NEXT_PUBLIC_API_URL"]
);