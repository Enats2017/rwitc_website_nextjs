<?php
include_once('../bootstrap.php');
require_once("../lib/users.class.php");
require_once("../lib/userchecks.php");
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../rwitc_website_api/config/config.php';

use Aws\S3\S3Client;

session_start();
header('Content-Type: application/json; charset=UTF-8');

function out($arr, $code = 200) {
    http_response_code($code);
    echo json_encode($arr);
    exit;
}
function fail($msg, $code = 400) {
    out(['uploaded' => 0, 'error' => ['message' => $msg]], $code);
}

// ---- auth ----
if (!isAdminlogin() || ($_SESSION['articles'] ?? '') !== 'Y') {
    fail('Not authorised', 403);
}

// ---- validate ----
if (empty($_FILES['upload']) || $_FILES['upload']['error'] !== UPLOAD_ERR_OK) {
    fail('Upload failed');
}
$file = $_FILES['upload'];
if ($file['size'] > 5 * 1024 * 1024) {
    fail('Image must be under 5 MB');
}
$allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];
$mime = mime_content_type($file['tmp_name']);
if (!isset($allowed[$mime])) {
    fail('Only JPG, PNG, GIF, WEBP allowed');
}

// ---- S3 upload ----
try {
    $s3Client = new S3Client([
        'version'     => 'latest',
        'region'      => AWS_REGION,
        'credentials' => [
            'key'    => AWS_ACCESS_KEY_ID,
            'secret' => AWS_SECRET_ACCESS_KEY,
        ],
    ]);

    $s3Key = 'uploads/Images/articles/' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];

    $result = $s3Client->putObject([
        'Bucket'       => AWS_BUCKET,
        'Key'          => $s3Key,
        'SourceFile'   => $file['tmp_name'],
        'ContentType'  => $mime,
        'CacheControl' => 'max-age=31536000',
    ]);

    out([
        'uploaded' => 1,
        'fileName' => basename($s3Key),
        'url'      => $result['ObjectURL'],
    ]);
} catch (Throwable $e) {
    error_log('Article image S3 error: ' . $e->getMessage());
    fail('S3 upload failed', 500);
}