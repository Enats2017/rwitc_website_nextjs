<?php

// Returns the registry array (loaded only once)
function pdfRegistry()
{
    static $registry = null;
    if ($registry === null) {
        $registry = require __DIR__ . '/pdf_registry.php';
    }
    return $registry;
}

// Returns the S3 object key (example: uploads/club/123_file.pdf) from the stored S3 url.
// Returns '' if the url does not contain the expected folder.
function pdfS3KeyFromS3Url($s3Url, $s3Folder)
{
    $path = urldecode((string)parse_url((string)$s3Url, PHP_URL_PATH));
    $pos  = strpos($path, $s3Folder);
    if ($pos === false) {
        return '';
    }
    return substr($path, $pos);
}

// Returns the original uploaded file name (timestamp prefix removed) from the stored S3 url
function pdfOriginalNameFromS3Url($s3Url)
{
    $name = basename(urldecode((string)parse_url((string)$s3Url, PHP_URL_PATH)));
    return preg_replace('/^\d+_/', '', $name);
}

// Returns the file name that is shown in the public link.
// Uses display_name from the registry when set, otherwise the original uploaded name.
function pdfPublicFileName($config, $s3Url)
{
    if (!empty($config['display_name'])) {
        return $config['display_name'];
    }
    return pdfOriginalNameFromS3Url($s3Url);
}


// Returns the Content-Type for a file name (based on its extension)
function pdfContentType($fileName)
{
    $ext = strtolower(pathinfo((string)$fileName, PATHINFO_EXTENSION));

    $map = array(
        'pdf'  => 'application/pdf',
        'htm'  => 'text/html; charset=UTF-8',
        'html' => 'text/html; charset=UTF-8',
        'txt'  => 'text/plain; charset=UTF-8',
        'csv'  => 'text/csv; charset=UTF-8',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'  => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt'  => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'zip'  => 'application/zip',
    );

    return isset($map[$ext]) ? $map[$ext] : 'application/octet-stream';
}

function applicationFormFileName($title, $fileType, $source = '')
{
    $name = trim(preg_replace('/[^A-Za-z0-9]+/', '_', (string)$title), '_');
    if ($name === '') {
        $name = 'Application_Form';
    }

    $ext = strtolower(preg_replace('/[^A-Za-z0-9]/', '', (string)$fileType));
    if ($ext === '' && $source !== '') {
        $ext = strtolower(preg_replace('/[^A-Za-z0-9]/', '', pathinfo((string)$source, PATHINFO_EXTENSION)));
    }

    return $ext !== '' ? $name . '.' . $ext : $name;
}