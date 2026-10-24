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
 *
 * HTML/HTM files are re-encoded to UTF-8 when they are saved in an older
 * encoding (for example Windows-1252), so no broken characters are shown.
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

/**
 * Returns the HTML text as valid UTF-8.
 * - Already valid UTF-8: returned unchanged.
 * - Otherwise: converted from the charset the file declares in its <meta> tag,
 *   or from Windows-1252 when nothing usable is declared.
 */
function sweepstakeHtmlToUtf8($html)
{
    if (!function_exists("mb_check_encoding") || !function_exists("mb_convert_encoding")) {
        return $html;
    }

    if (mb_check_encoding($html, "UTF-8")) {
        return $html;
    }

    $fromCharset = "Windows-1252";

    // Charset declared by the file itself (<meta charset=...> or http-equiv)
    if (preg_match('/<meta[^>]+charset\s*=\s*["\']?\s*([A-Za-z0-9_\-:.]+)/i', substr($html, 0, 4096), $m)) {

        $declared = strtoupper(trim($m[1]));
        $known    = array_map("strtoupper", mb_list_encodings());

        // A file that says UTF-8 but contains invalid bytes is really an old ANSI file
        if ($declared !== "UTF-8" && $declared !== "UTF8" && in_array($declared, $known, true)) {
            $fromCharset = $declared;
        }
    }

    return mb_convert_encoding($html, "UTF-8", $fromCharset);
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

$extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$isHtml    = ($extension === "htm" || $extension === "html");

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

    // Clear any accidental output before sending the file
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    // ---------- HTML / HTM: re-encode to UTF-8 and send ----------
    if ($isHtml) {

        $html = sweepstakeHtmlToUtf8((string)$s3Object["Body"]);

        header("Content-Type: text/html; charset=UTF-8");
        header('Content-Disposition: inline; filename="' . $fileName . '"');
        header("Content-Length: " . strlen($html));
        header("Cache-Control: public, max-age=300");
        header("X-Content-Type-Options: nosniff");
        header("Access-Control-Allow-Origin: *");

        echo $html;
        exit;
    }

    // ---------- Any other file type: stream as it is ----------

    // Use the content type stored on S3 (same behaviour as the old direct link);
    // fall back to the file extension when S3 has nothing useful.
    $contentType = isset($s3Object["ContentType"]) ? (string)$s3Object["ContentType"] : "";

    if ($contentType === "" || $contentType === "binary/octet-stream" || $contentType === "application/octet-stream") {
        $contentType = pdfContentType($fileName);
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