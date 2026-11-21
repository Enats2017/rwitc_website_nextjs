<?php

require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/pdf_helpers.php";

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

function pdfNotFound()
{
    http_response_code(404);
    echo 'File not found.';
    exit;
}

$registry = pdfRegistry();

// Read type and id from the URL
$uriPath = (string)parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$marker  = 'pdf_stream.php/';
$pos     = strpos($uriPath, $marker);

if ($pos === false) {
    pdfNotFound();
}

$parts = explode('/', substr($uriPath, $pos + strlen($marker)));
$type  = isset($parts[0]) ? $parts[0] : '';
$id    = isset($parts[1]) ? (int)$parts[1] : 0;

// Only types listed in pdf_registry.php are allowed
if (!isset($registry[$type]) || $id <= 0) {
    pdfNotFound();
}

$config   = $registry[$type];
$s3Folder = $config['s3_folder'];
$typeSql  = $conn->real_escape_string($type);   // $type is already validated against the registry

$result = $conn->query("SELECT file_url FROM club_menu_pdf WHERE id = " . $id . " AND `type` = '" . $typeSql . "' LIMIT 1");
$row    = $result ? $result->fetch_assoc() : null;
$conn->close();

if (!$row) {
    pdfNotFound();
}

$s3Url = $row['file_url'];
$s3Key = pdfS3KeyFromS3Url($s3Url, $s3Folder);

if ($s3Key === '') {
    pdfNotFound();
}

// File name sent to the browser
$fileName = pdfPublicFileName($config, $s3Url);
$fileName = str_replace(array('"', "\r", "\n"), '', $fileName);

try {
    $s3Client = new S3Client([
        'version'     => 'latest',
        'region'      => AWS_REGION,
        'credentials' => [
            'key'    => AWS_ACCESS_KEY_ID,
            'secret' => AWS_SECRET_ACCESS_KEY,
        ],
    ]);

    // 1) Read only the file info first (no download yet)
    $head = $s3Client->headObject([
        'Bucket' => AWS_BUCKET,
        'Key'    => $s3Key,
    ]);

    $etag         = isset($head['ETag']) ? (string)$head['ETag'] : '';
    $lastModified = isset($head['LastModified']) ? $head['LastModified']->getTimestamp() : 0;

    // Clear any accidental output before sending headers
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    // Always ask the server first, so a newly uploaded PDF shows immediately
    header('Cache-Control: no-cache, must-revalidate');
    header('X-Content-Type-Options: nosniff');
    header('Access-Control-Allow-Origin: *');

    if ($etag !== '') {
        header('ETag: ' . $etag);
    }
    if ($lastModified > 0) {
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
    }

    // 2) If the browser already has the same version, send 304 (no download)
    $clientEtag     = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : '';
    $clientModified = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) : false;

    $notModified = false;
    if ($clientEtag !== '' && $etag !== '') {
        $notModified = ($clientEtag === $etag);
    } elseif ($clientModified !== false && $lastModified > 0) {
        $notModified = ($lastModified <= $clientModified);
    }

    if ($notModified) {
        http_response_code(304);
        exit;
    }

    // 3) File is new or changed: download from S3 and send it
    $s3Object = $s3Client->getObject([
        'Bucket' => AWS_BUCKET,
        'Key'    => $s3Key,
    ]);

    header('Content-Type: ' . pdfContentType($fileName));
    header('Content-Disposition: inline; filename="' . $fileName . '"');
    if (isset($s3Object['ContentLength'])) {
        header('Content-Length: ' . $s3Object['ContentLength']);
    }

    $body = $s3Object['Body'];
    while (!$body->eof()) {
        echo $body->read(8192);
        flush();
    }
    exit;
} catch (AwsException $e) {
    pdfNotFound();
}