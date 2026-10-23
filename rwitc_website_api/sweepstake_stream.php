<?php

/**
 * Sweepstakes - file stream
 *
 * GET sweepstake_stream.php/<sweepstake id>/<file name>
 *
 * Reads the sweepstake file from S3 on the server and sends it to the browser,
 * so the visitor only sees our own URL and never the S3 address.
 * Only S3 files under staticpages/sweepstake/ can be served.
 * Old files that live in the local static folder are NOT served from here.
 */

require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/pdf_helpers.php";

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

// Fixed S3 folder. The visitor can never choose a different one.
const SWEEPSTAKE_S3_FOLDER = "staticpages/sweepstake/";

function sweepstakeNotFound()
{
    http_response_code(404);
    header("Content-Type: text/plain; charset=UTF-8");
    echo "File not found.";
    exit;
}

// Read the sweepstake id from the URL: /sweepstake_stream.php/<id>/<file name>
$uriPath = (string)parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$marker  = "sweepstake_stream.php/";
$pos     = strpos($uriPath, $marker);

if ($pos === false) {
    sweepstakeNotFound();
}

$parts = explode("/", substr($uriPath, $pos + strlen($marker)));
$id    = isset($parts[0]) ? (int)$parts[0] : 0;

if ($id <= 0) {
    sweepstakeNotFound();
}

// Look the file up by id (the visitor never supplies an S3 key)
$stmt = $conn->prepare("SELECT filename FROM sweepstakes WHERE id = ? LIMIT 1");

if ($stmt === false) {
    sweepstakeNotFound();
}

$stmt->bind_param("i", $id);
$stmt->execute();

$res = $stmt->get_result();
$row = ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;

$stmt->close();
$conn->close();

if (!$row) {
    sweepstakeNotFound();
}

// Only S3 files (full URL stored in the database) are streamed here.
// A plain file name means an old local file, which is not served by this script.
$storedUrl = trim((string)$row["filename"]);

if (stripos($storedUrl, "http") !== 0) {
    sweepstakeNotFound();
}

// Only files under staticpages/sweepstake/ are allowed
$s3Key = pdfS3KeyFromS3Url($storedUrl, SWEEPSTAKE_S3_FOLDER);

if ($s3Key === "" || strpos($s3Key, "..") !== false) {
    sweepstakeNotFound();
}

// File name sent to the browser
$fileName = basename($s3Key);
$fileName = str_replace(array('"', "\r", "\n"), "", $fileName);

try {
    $s3Client = new S3Client([
        "version"     => "latest",
        "region"      => AWS_REGION,
        "credentials" => [
            "key"    => AWS_ACCESS_KEY_ID,
            "secret" => AWS_SECRET_ACCESS_KEY,
        ],
    ]);

    $s3Object = $s3Client->getObject([
        "Bucket" => AWS_BUCKET,
        "Key"    => $s3Key,
    ]);

    // Use the content type stored on S3 (keeps the same behaviour as the old direct link);
    // fall back to the file extension when S3 has nothing useful.
    $contentType = isset($s3Object["ContentType"]) ? (string)$s3Object["ContentType"] : "";

    if ($contentType === "" || $contentType === "binary/octet-stream" || $contentType === "application/octet-stream") {
        $contentType = pdfContentType($fileName);
    }

    // Clear any accidental output before sending the file
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header("Content-Type: " . $contentType);
    header('Content-Disposition: inline; filename="' . $fileName . '"');
    if (isset($s3Object["ContentLength"])) {
        header("Content-Length: " . $s3Object["ContentLength"]);
    }
    header("Cache-Control: public, max-age=300");
    header("X-Content-Type-Options: nosniff");
    header("Access-Control-Allow-Origin: *");

    $body = $s3Object["Body"];

    while (!$body->eof()) {
        echo $body->read(8192);
        flush();
    }

    exit;
} catch (AwsException $e) {
    error_log("SWEEPSTAKE_STREAM_ERROR | " . $e->getMessage());
    sweepstakeNotFound();
}