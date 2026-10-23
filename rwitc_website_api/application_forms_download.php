<?php

/**
 * Application Forms / Chart / Prospectus - open endpoint
 *
 *   GET application_forms_download.php/<form id>/<file name>   -> a form from the Forms list
 *   GET application_forms_download.php/chart/CHART.pdf         -> the Chart PDF
 *   GET application_forms_download.php/prospectus/PROSPECTUS.pdf -> the Prospectus PDF
 *
 * The old query-string links still work:
 *   ?id=<form id>, ?doc=chart, ?doc=prospectus
 *
 * The visitor never supplies an S3 key. Forms are looked up in the database
 * by numeric id (active + under uploads/ApplicationForms/ only); chart and
 * prospectus map to the fixed keys the admin pages overwrite on every upload.
 *
 * The file is streamed by this script, so the browser only ever sees our own
 * URL and never the S3 address. PDFs open in the browser tab, DOC/DOCX
 * (which browsers cannot display) download as usual.
 *
 * Why not s3_set_url.php?  That helper accepts ANY ?key= and would let a
 * visitor read every object in the bucket.
 */
require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/../vendor/autoload.php";

use Aws\S3\S3Client;

function formsFail($status, $message)
{
    http_response_code($status);
    header("Content-Type: text/plain; charset=UTF-8");
    header("Cache-Control: no-store");
    echo $message;
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "GET" && $_SERVER["REQUEST_METHOD"] !== "HEAD") {
    header("Allow: GET, HEAD");
    formsFail(405, "Method not allowed.");
}

$prefix = "uploads/ApplicationForms/";

// Fixed documents - same keys as chartPdfUpload.php / prospectusPdfUpload.php
$fixedDocs = [
    "chart"      => ["key" => $prefix . "CHART.pdf",      "name" => "CHART.pdf"],
    "prospectus" => ["key" => $prefix . "PROSPECTUS.pdf", "name" => "PROSPECTUS.pdf"]
];

$contentTypes = [
    "pdf"  => "application/pdf",
    "doc"  => "application/msword",
    "docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
];
// Read the clean path form:
//   /application_forms_download.php/<id>/<file name>
//   /application_forms_download.php/chart/CHART.pdf
$uriPath   = (string)parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$marker    = "application_forms_download.php/";
$markerPos = strpos($uriPath, $marker);

if ($markerPos !== false) {

    $segments = explode("/", substr($uriPath, $markerPos + strlen($marker)));
    $first    = isset($segments[0]) ? strtolower(trim(urldecode($segments[0]))) : "";

    if ($first !== "" && ctype_digit($first)) {
        $_GET["id"] = $first;
    } elseif ($first !== "") {
        $_GET["doc"] = $first;
    }
}
$id  = null;
$doc = isset($_GET["doc"]) ? strtolower(trim((string)$_GET["doc"])) : null;

if ($doc !== null) {

    if (!isset($fixedDocs[$doc])) {
        formsFail(404, "Document not found.");
    }
} else {

    $id = filter_var($_GET["id"] ?? null, FILTER_VALIDATE_INT);

    if (!$id || $id < 1) {
        formsFail(400, "Invalid form id.");
    }
}

try {

    $s3 = new S3Client([
        "version"     => "latest",
        "region"      => AWS_REGION,
        "credentials" => [
            "key"    => AWS_ACCESS_KEY_ID,
            "secret" => AWS_SECRET_ACCESS_KEY
        ]
    ]);

    if ($doc !== null) {

        // ---- Chart / Prospectus: fixed key, no database row ----
        $key  = $fixedDocs[$doc]["key"];
        $name = $fixedDocs[$doc]["name"];
        $ext  = "pdf";

        try {
            $s3->headObject(["Bucket" => AWS_BUCKET, "Key" => $key]);
        } catch (Aws\Exception\AwsException $e) {

            if ($e->getStatusCode() === 404) {
                formsFail(404, "This document has not been uploaded yet.");
            }

            throw $e;
        }
    } else {

        // ---- Forms list: look the form up by id ----
        $stmt = $conn->prepare(
            "SELECT title, source, original_file_name, file_type
             FROM application_forms
             WHERE id = ? AND is_active = 1
             LIMIT 1"
        );

        if ($stmt === false) {
            throw new RuntimeException("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("i", $id);

        if (!$stmt->execute()) {
            throw new RuntimeException("Execute failed: " . $stmt->error);
        }

        $res = $stmt->get_result();
        $row = ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;
        $stmt->close();

        if ($row === null || strpos((string)$row["source"], $prefix) !== 0) {
            formsFail(404, "Form not found.");
        }

        $key = (string)$row["source"];
        $ext = strtolower((string)$row["file_type"]);

        if (!isset($contentTypes[$ext])) {
            formsFail(404, "Form not found.");
        }

        // File name: the file the admin uploaded, or the title as fallback.
        $name = trim((string)$row["original_file_name"]);
        if ($name === "") {
            $name = (string)$row["title"] . "." . $ext;
        }
    }

    // Strip anything that could break out of the header value.
    $name = preg_replace('/[\x00-\x1F\x7F"\\\\\/]+/', "", $name);
    $name = ($name === "" || $name === null) ? ("file." . $ext) : $name;

    // "inline" = open in the browser tab (PDF). The file name is still set,
    // so "Save as" from the PDF viewer suggests the original name.
    // ASCII fallback + RFC 5987 UTF-8 name for non-ASCII file names.
    $asciiName   = preg_replace('/[^\x20-\x7E]/', "_", $name);
    $disposition = 'inline; filename="' . $asciiName . '"; filename*=UTF-8\'\'' . rawurlencode($name);

    // Read the file from S3 on the server side (the browser never sees the S3 address).
    try {
        $s3Object = $s3->getObject([
            "Bucket" => AWS_BUCKET,
            "Key"    => $key
        ]);
    } catch (Aws\Exception\AwsException $e) {

        if ($e->getStatusCode() === 404) {
            formsFail(404, "File not found.");
        }

        throw $e;
    }

    // Clear any accidental output before sending the file.
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header("Content-Type: " . $contentTypes[$ext]);
    header("Content-Disposition: " . $disposition);
    if (isset($s3Object["ContentLength"])) {
        header("Content-Length: " . $s3Object["ContentLength"]);
    }
    header("Cache-Control: public, max-age=300");
    header("X-Content-Type-Options: nosniff");

    if ($_SERVER["REQUEST_METHOD"] !== "HEAD") {

        $body = $s3Object["Body"];

        while (!$body->eof()) {
            echo $body->read(8192);
            flush();
        }
    }

    exit;
} catch (Throwable $error) {

    error_log("APPLICATION_FORMS_DOWNLOAD_ERROR | " . ($doc !== null ? "doc=" . $doc : "id=" . $id) . " | " . $error->getMessage());

    formsFail(500, "Unable to open this file right now.");
} finally {

    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}