<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
require_once __DIR__ . "/config/run_races_config.php";
// Website colours folder where images will be saved
// TODO: change this path if your website colours folder is different

// Max file size in bytes (10 MB)
define("MAX_FILE_SIZE", 10485760);

// Allowed image extensions (svg is not allowed because it can contain scripts)
$allowedExt = ["jpg", "jpeg", "png", "gif", "bmp", "webp", "tiff", "tif", "ico", "jfif"];

function respond($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    respond(["success" => false, "message" => "Method not allowed."], 405);
}

// Check that a file was sent
if (!isset($_FILES["file"]) || $_FILES["file"]["error"] === UPLOAD_ERR_NO_FILE) {
    respond(["success" => false, "message" => "No file received."], 400);
}

$file = $_FILES["file"];

if ($file["error"] !== UPLOAD_ERR_OK) {
    respond(["success" => false, "message" => "Upload error. Error code: " . $file["error"]], 400);
}

if (!is_uploaded_file($file["tmp_name"])) {
    respond(["success" => false, "message" => "Invalid upload."], 400);
}

if ($file["size"] > MAX_FILE_SIZE) {
    respond(["success" => false, "message" => "File is too large. Maximum allowed size is " . (MAX_FILE_SIZE / 1048576) . " MB."], 400);
}

// Sanitize the file name
$originalName = basename($file["name"]);
$ext  = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$base = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));

if ($base === "" || $base === "." || $base === "..") {
    respond(["success" => false, "message" => "Invalid file name."], 400);
}

// Check extension
if (!in_array($ext, $allowedExt, true)) {
    respond(["success" => false, "message" => "Invalid file type. Allowed: " . implode(", ", $allowedExt)], 400);
}

// Check real MIME type, not only the extension
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file["tmp_name"]);

if ($mime === false || strpos($mime, "image/") !== 0 || $mime === "image/svg+xml") {
    respond(["success" => false, "message" => "The file is not a valid image."], 400);
}

// Create the colours folder if it does not exist
if (!is_dir(COLOURS_DIR)) {
    if (!@mkdir(COLOURS_DIR, 0775, true)) {
        respond(["success" => false, "message" => "Could not create colours folder."], 500);
    }
}

if (!is_writable(COLOURS_DIR)) {
    respond(["success" => false, "message" => "Colours folder is not writable."], 500);
}

$fileName   = $base . "." . $ext;
$targetFile = rtrim(COLOURS_DIR, "/\\") . "/" . $fileName;

// Same name file will be replaced
if (!move_uploaded_file($file["tmp_name"], $targetFile)) {
    respond(["success" => false, "message" => "Failed to save the file."], 500);
}

respond([
    "success" => true,
    "message" => "File saved.",
    "file"    => $fileName
]);
