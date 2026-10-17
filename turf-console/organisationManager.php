<?php

include_once('../bootstrap.php');
require_once("../lib/users.class.php");
require_once("../lib/userchecks.php");
require_once("../lib/permissions.php");
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../rwitc_website_api/config/config.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

session_start();

$userObj = new Users($db);

// ---------------------------------------------------------------
// SETTINGS
// ---------------------------------------------------------------
$maxContent = 120000;                           // max size of the cleaned HTML (bytes, text tabs)
$maxTitle   = 200;                              // max characters allowed in the title
$newsDir    = dirname(__DIR__) . '/news/';      // Old local upload folder (only used to clean up old Working Group files)
$wgS3Key    = 'uploads/club/working_group.pdf'; // Working Group PDF always lives at this one S3 key (replaced on every upload)

// Sections (tabs) of this module: key => label
// Every section keeps exactly ONE row in organisation_management.
$sections = array(
    'structure'          => 'Structure',
    'managing_committee' => 'Managing Committee',
    'stewards_of_club'   => 'Stewards of the Club',
    'board_of_appeal'    => 'Board of Appeal',
    'working_group'      => 'Working Group',
);

// Selected section (validated against the whitelist above)
$secKey = 'structure';
$reqSec = isset($_POST['sec']) ? $_POST['sec'] : (isset($_GET['sec']) ? $_GET['sec'] : '');
if (is_string($reqSec) && isset($sections[$reqSec])) {
    $secKey = $reqSec;
}
$secName = $sections[$secKey];

// All tabs except Working Group are text-only (rich editor). Working Group is PDF-only.
$textOnly = ($secKey !== 'working_group');

// ---------------------------------------------------------------
// LOGIN + MODULE ACCESS CHECK
// ---------------------------------------------------------------
$secmsg = '';
if (!isAdminlogin()) {
    $secmsg = "You do not have access to this page.";
} elseif (!hasModuleAccess('organisation_management')) {
    $secmsg = "You do not have access to this module.";
}

// CSRF token
if (empty($_SESSION['org_csrf'])) {
    $_SESSION['org_csrf'] = bin2hex(openssl_random_pseudo_bytes(16));
}
$csrf = $_SESSION['org_csrf'];

// FLASH MESSAGE
$flash = '';
$flashType = '';
if (isset($_SESSION['org_flash'])) {
    $flash = $_SESSION['org_flash']['text'];
    $flashType = $_SESSION['org_flash']['type'];
    unset($_SESSION['org_flash']);
}

// ---------------------------------------------------------------
// HELPERS
// ---------------------------------------------------------------
function orgSetFlash($text, $type)
{
    $_SESSION['org_flash'] = array('text' => $text, 'type' => $type);
}

function orgS3Client()
{
    return new S3Client(array(
        'version'     => 'latest',
        'region'      => AWS_REGION,
        'credentials' => array('key' => AWS_ACCESS_KEY_ID, 'secret' => AWS_SECRET_ACCESS_KEY),
    ));
}

function orgIsUrl($value)
{
    return (bool)preg_match('#^https?://#i', (string)$value);
}

// S3 key from a stored S3 URL (only keys under uploads/club/ are accepted)
function orgKeyFromUrl($url)
{
    $path = urldecode((string)parse_url((string)$url, PHP_URL_PATH));
    $pos  = strpos($path, 'uploads/club/');
    return $pos === false ? '' : substr($path, $pos);
}

// Deletes a stored Working Group file.
// - old local file name -> removed from the news/ folder
// - S3 URL -> removed from S3, unless it is the key that was just replaced ($skipKey)
function orgDeleteStored($dir, $stored, $skipKey = '')
{
    $stored = (string)$stored;
    if ($stored === '') {
        return;
    }

    if (orgIsUrl($stored)) {
        $key = orgKeyFromUrl($stored);
        if ($key === '' || ($skipKey !== '' && $key === $skipKey)) {
            return;
        }
        try {
            orgS3Client()->deleteObject(array('Bucket' => AWS_BUCKET, 'Key' => $key));
        } catch (AwsException $e) {
            error_log('Working Group S3 delete failed: ' . $e->getMessage());
        }
        return;
    }

    $name = basename($stored);
    if ($name !== '' && is_file($dir . $name)) {
        @unlink($dir . $name);
    }
}

// Validates the uploaded PDF and uploads it to S3 (always the same key, so it replaces the old file).
// Returns the file URL on success, or '' and sets $err on failure.
function orgUploadPdf($key, &$err)
{
    if (!isset($_FILES['report_file']) || $_FILES['report_file']['error'] !== UPLOAD_ERR_OK) {
        $err = 'File upload failed. Please try again (check file size limit).';
        return '';
    }

    $tmp  = $_FILES['report_file']['tmp_name'];
    $orig = basename($_FILES['report_file']['name']);
    $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));

    // extension check
    if ($ext !== 'pdf') {
        $err = 'Only PDF files are allowed.';
        return '';
    }

    // real content check (mime + PDF header)
    $mime = function_exists('mime_content_type') ? mime_content_type($tmp) : 'application/pdf';
    $head = file_get_contents($tmp, false, null, 0, 5);
    if ($mime !== 'application/pdf' || $head !== '%PDF-') {
        $err = 'Invalid PDF file.';
        return '';
    }

    try {
        $result = orgS3Client()->putObject(array(
            'Bucket'      => AWS_BUCKET,
            'Key'         => $key,
            'SourceFile'  => $tmp,
            'ContentType' => 'application/pdf',
        ));
        $url = isset($result['ObjectURL']) ? (string)$result['ObjectURL'] : '';
        if ($url === '') {
            $err = 'Could not upload the PDF. Please try again.';
        }
        return $url;
    } catch (AwsException $e) {
        error_log('Working Group S3 upload failed: ' . $e->getMessage());
        $err = 'Could not upload the PDF. Please try again.';
        return '';
    }
}

// Display name of a stored file
function orgFileLabel($stored)
{
    $stored = (string)$stored;
    if (orgIsUrl($stored)) {
        $name = basename(urldecode((string)parse_url($stored, PHP_URL_PATH)));
    } else {
        $name = preg_replace('/^\d+_/', '', basename($stored));
    }
    return $name === '' ? 'working_group.pdf' : $name;
}

// Link to open a stored file (S3 URL as it is, old local file from news/)
function orgFileUrl($stored)
{
    $stored = (string)$stored;
    if (orgIsUrl($stored)) {
        return $stored;
    }
    return 'news/' . rawurlencode($stored);
}

// The ONE row of a section. $secKey is whitelisted, so it is safe to use in SQL.
// If old duplicate rows exist, the latest one (by date, then id) is used, whatever its id is.
function orgGetSectionRow($db, $secKey)
{
    return $db->getSingleRowAssoc(
        "SELECT * FROM organisation_management
         WHERE section = '" . $db->escape($secKey) . "'
         ORDER BY report_date DESC, id DESC
         LIMIT 1"
    );
}

// URL for HTML links (the page uses <base href>, so the folder prefix is needed)
function orgHtmlUrl($sec)
{
    return 'turf-console/organisationManager.php?sec=' . urlencode($sec);
}

// Old plain-text content -> HTML.
// Blank line = new block. A block where every line starts with "- " (or a bullet)
// becomes a bullet list, any other block becomes a paragraph.
// If the content already contains HTML tags it is returned unchanged.
function orgLegacyToHtml($text)
{
    $text = (string)$text;
    if (preg_match('/<[a-z][^>]*>/i', $text)) {
        return $text;
    }
    $text = str_replace(array("\r\n", "\r"), "\n", trim($text));
    if ($text === '') {
        return '';
    }

    $out = '';
    foreach (preg_split('/\n\s*\n/', $text) as $block) {
        $block = trim($block);
        if ($block === '') {
            continue;
        }

        $lines = array();
        foreach (explode("\n", $block) as $line) {
            $line = trim($line);
            if ($line !== '') {
                $lines[] = $line;
            }
        }
        if (empty($lines)) {
            continue;
        }

        $allBullets = true;
        foreach ($lines as $line) {
            if (!preg_match('/^(?:-|\x{2022})\s+/u', $line)) {
                $allBullets = false;
                break;
            }
        }

        if ($allBullets) {
            $out .= '<ul>';
            foreach ($lines as $line) {
                $item = preg_replace('/^(?:-|\x{2022})\s+/u', '', $line);
                $out .= '<li>' . htmlspecialchars($item, ENT_QUOTES, 'UTF-8') . '</li>';
            }
            $out .= '</ul>';
        } else {
            $out .= '<p>' . htmlspecialchars(implode(' ', $lines), ENT_QUOTES, 'UTF-8') . '</p>';
        }
    }
    return $out;
}

// HTML sanitizer (allow-list). Keeps bold/italic/lists/links/tables, text-align, font-size and list style only.
function orgSanitizeHtml($html)
{
    $html = trim((string)$html);
    if ($html === '') {
        return '';
    }

    $allowedTags = array('p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'sub', 'sup', 'span', 'div',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'blockquote', 'hr',
        'a', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th');
    $dropTags = array('script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button',
        'textarea', 'select', 'link', 'meta', 'svg', 'math', 'head', 'title', 'xml', 'img');

    $previous = libxml_use_internal_errors(true);
    $doc = new DOMDocument('1.0', 'UTF-8');
    $doc->loadHTML('<?xml encoding="utf-8" ?><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    $root = $doc->getElementsByTagName('div')->item(0);
    if (!$root) {
        return '';
    }

    $clean = function (DOMNode $node) use (&$clean, $allowedTags, $dropTags) {
        $children = array();
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }
        foreach ($children as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                continue;
            }
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                $node->removeChild($child);
                continue;
            }
            $tag = strtolower($child->nodeName);
            if (in_array($tag, $dropTags, true)) {
                $node->removeChild($child);
                continue;
            }
            $clean($child);
            if (!in_array($tag, $allowedTags, true)) {
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }

            $href = $child->getAttribute('href');
            $style = $child->getAttribute('style');
            $colspan = (int)$child->getAttribute('colspan');
            $rowspan = (int)$child->getAttribute('rowspan');
            while ($child->attributes->length > 0) {
                $child->removeAttributeNode($child->attributes->item(0));
            }

            if ($tag === 'a' && preg_match('#^(https?://|mailto:|tel:)#i', trim($href))) {
                $child->setAttribute('href', trim($href));
                $child->setAttribute('target', '_blank');
                $child->setAttribute('rel', 'noopener noreferrer');
            }
            if ($tag === 'td' || $tag === 'th') {
                if ($colspan > 1) $child->setAttribute('colspan', (string)$colspan);
                if ($rowspan > 1) $child->setAttribute('rowspan', (string)$rowspan);
            }

            $safeStyle = '';
            if ($style !== '' && preg_match('/text-align\s*:\s*(left|right|center|justify)/i', $style, $m)) {
                $safeStyle .= 'text-align: ' . strtolower($m[1]) . ';';
            }
            if ($style !== '' && preg_match('/font-size\s*:\s*(\d+(?:\.\d+)?)(px|pt|em|rem|%)/i', $style, $m) && (float)$m[1] > 0 && (float)$m[1] <= 200) {
                $safeStyle .= ($safeStyle !== '' ? ' ' : '') . 'font-size: ' . $m[1] . strtolower($m[2]) . ';';
            }
            if (($tag === 'ol' || $tag === 'ul') && $style !== '' && preg_match('/list-style-type\s*:\s*(decimal|lower-roman|upper-roman|lower-alpha|upper-alpha|lower-greek|disc|circle|square)/i', $style, $m)) {
                $safeStyle .= ($safeStyle !== '' ? ' ' : '') . 'list-style-type: ' . strtolower($m[1]) . ';';
            }
            if ($safeStyle !== '') {
                $child->setAttribute('style', $safeStyle);
            }
        }
    };
    $clean($root);

    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $doc->saveHTML($child);
    }
    return trim($out);
}

// ---------------------------------------------------------------
// SAVE (every section always uses exactly one row)
// ---------------------------------------------------------------
if (empty($secmsg) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $redirect = 'organisationManager.php?sec=' . urlencode($secKey);

    // CSRF check
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['org_csrf'], (string)$_POST['csrf'])) {
        orgSetFlash('Invalid request. Please try again.', 'error');
        header('Location: ' . $redirect);
        exit;
    }

    $title = isset($_POST['report_title']) && is_string($_POST['report_title']) ? trim($_POST['report_title']) : '';
    $raw   = isset($_POST['content']) && is_string($_POST['content']) ? $_POST['content'] : '';

    // Text tabs: sanitize the HTML coming from the editor
    $text = $textOnly ? orgSanitizeHtml($raw) : '';

    $type = $textOnly ? 'text' : 'file';
    $err  = '';

    $oldRow = orgGetSectionRow($db, $secKey);

    if (mb_strlen($title) > $maxTitle) {
        $err = 'Title is too long (max ' . $maxTitle . ' characters).';
    } elseif ($textOnly && $text === '') {
        $err = 'Content is required.';
    } elseif ($textOnly && strlen($text) > $maxContent) {
        $err = 'Content is too long. Please shorten it.';
    }

    $newUrl       = '';   // URL of the newly uploaded file
    $fileToKeep   = '';   // old file to keep
    $fileToDelete = '';   // old file to delete (old local file or a different S3 file)

    $oldIsFile = ($oldRow && $oldRow['type'] === 'file' && (string)$oldRow['file_name'] !== '');

    if ($err === '' && !$textOnly) {

        $hasUpload = isset($_FILES['report_file']) && $_FILES['report_file']['error'] !== UPLOAD_ERR_NO_FILE;

        if ($hasUpload) {
            // Same S3 key every time -> the old PDF is replaced, not duplicated
            $newUrl = orgUploadPdf($wgS3Key, $err);
            if ($err === '' && $oldIsFile) {
                $fileToDelete = $oldRow['file_name'];   // an old local file is cleaned up; the same S3 key is skipped
            }
        } elseif ($oldIsFile) {
            $fileToKeep = $oldRow['file_name'];
        } else {
            $err = 'Please choose a PDF file.';
        }
    }

    if ($err === '') {

        $finalFile = (!$textOnly) ? ($newUrl !== '' ? $newUrl : $fileToKeep) : '';

        $titleSql = ($title !== '') ? "'" . $db->escape($title) . "'" : "NULL";
        $textSql  = $textOnly ? "'" . $db->escape($text) . "'" : "NULL";
        $fileSql  = (!$textOnly) ? "'" . $db->escape($finalFile) . "'" : "NULL";

        try {
            if ($oldRow) {
                // Row already exists -> always UPDATE it (never a new row)
                $db->update("UPDATE organisation_management SET
                                report_date = CURDATE(),
                                title = $titleSql,
                                type = '" . $db->escape($type) . "',
                                content_text = $textSql,
                                file_name = $fileSql
                             WHERE id = " . (int)$oldRow['id'] . " AND section = '" . $db->escape($secKey) . "'");
            } else {
                // First time for this section -> create its one and only row
                $db->insert("INSERT INTO organisation_management (section, report_date, title, type, content_text, file_name)
                             VALUES ('" . $db->escape($secKey) . "',
                                     CURDATE(),
                                     $titleSql,
                                     '" . $db->escape($type) . "',
                                     $textSql,
                                     $fileSql)");
            }

            orgSetFlash($textOnly ? 'Content updated successfully.' : 'PDF updated successfully.', 'success');

            // Working Group: remove an old local file (the S3 file itself was already replaced in place)
            if (!$textOnly && $fileToDelete !== '') {
                orgDeleteStored($newsDir, $fileToDelete, $wgS3Key);
            }

            // Text tab: if this section was a file before, remove that old file completely
            if ($textOnly && $oldIsFile) {
                orgDeleteStored($newsDir, $oldRow['file_name'], '');
            }
        } catch (Exception $e) {
            error_log('Organisation save error: ' . $e->getMessage());
            orgSetFlash('Database error. Could not save.', 'error');
        }
    } else {
        orgSetFlash($err, 'error');
    }

    header('Location: ' . $redirect);
    exit;
}

// ---------------------------------------------------------------
// LOAD CURRENT CONTENT (the section's one row)
// ---------------------------------------------------------------
$fTitle = '';
$fText  = '';
$fFile  = '';
$lastUpdated = '';

if (empty($secmsg)) {
    $row = orgGetSectionRow($db, $secKey);
    if ($row) {
        $fTitle = (string)$row['title'];
        $fText  = orgLegacyToHtml($row['content_text']);
        $fFile  = ($row['type'] === 'file') ? (string)$row['file_name'] : '';
        $lastUpdated = $row['report_date'] ? date('d M Y', strtotime($row['report_date'])) : '';
    }
}

// PAGE TITLE
$pageTitle = 'Organisation & Management';

// DESIGN OBJECT
$design = new Design();
$design->js = '';
$design->css = '';
$design->jqueryJs = "";
$design->startPage("$pageTitle");
$design->writeLogoTickerMenu();
$design->openDiv("contentWrapper");
$design->openDiv("infoWrapper", "col-lg-12");
$design->openDiv("leftArea", 'col-lg-9');
$design->writeContentPageStyles();

?>
<style type="text/css">
    .message { background: #fff3cd; border: 1px solid #ffe08a; padding: 12px 16px; border-radius: 8px; margin-bottom: 15px; font-size: 15px; }
    .message.success { background: #e3f6ea; border-color: #a9dcbd; color: #0f5c33; }
    .message.error { background: #fde8e8; border-color: #f5b5b5; color: #a12626; }
    .submenu { display: none; }

    .ar-header { display: flex; align-items: baseline; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
    .ar-header > div { display: flex; align-items: baseline; flex-wrap: wrap; gap: 10px; }
    .ar-title { font-size: 24px; font-weight: 700; color: #2b332f; margin: 0; }
    .ar-subtitle { font-size: 14px; color: #7a8c84; margin: 0; }

    .ar-card { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; padding: 24px; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03); max-width: 100%; margin-bottom: 26px; }
    .ar-card-title { font-family: inherit; font-size: 17px; font-weight: 600; color: #2b332f; margin: 0 0 20px; padding-bottom: 14px; border-bottom: 1px solid #eef1ef; }
    .ar-card-title::before, .ar-card-title::after { display: none; content: none; }

    .ar-field { margin-bottom: 18px; min-width: 0; }
    .ar-field label { display: block; font-size: 13px; font-weight: 600; color: #2b332f; margin-bottom: 6px; }
    .ar-field label .req { color: #c0392b; }
    .ar-field label .opt { color: #7a8c84; font-weight: 500; }
    .ar-input { width: 100%; box-sizing: border-box; padding: 10px 12px; font-size: 15px; font-family: inherit; color: #2b332f; background: #fff; border: 1px solid #d3d9d6; border-radius: 8px; outline: none; transition: border-color .15s ease, box-shadow .15s ease; }
    .ar-input::placeholder { color: #9aa9a2; }
    .ar-input:focus { border-color: #1a7a45; box-shadow: 0 0 0 3px rgba(26, 122, 69, 0.15); }
    .ar-input.invalid, .ar-drop.invalid { border-color: #c0392b; }
    .ar-hint { font-size: 12px; color: #7a8c84; margin-top: 6px; margin-bottom: 10px; line-height: 1.7; }
    .ar-err { color: #c0392b; font-size: 12px; margin-top: 5px; display: none; }
    .ar-err.show { display: block; }

    .ar-drop { border: 2px dashed #b9c4be; border-radius: 12px; padding: 32px 16px; text-align: center; cursor: pointer; background: #f8faf9; transition: all .15s ease; }
    .ar-drop:hover, .ar-drop.dragover { border-color: #1a7a45; background: #eef7f1; }
    .ar-drop-icon { font-size: 32px; color: #0f5c33; margin-bottom: 10px; }
    .ar-drop-text { font-size: 15px; font-weight: 500; color: #2b332f; margin: 0 0 4px; }
    .ar-drop-text span { color: #0f5c33; text-decoration: underline; }
    .ar-drop-sub { font-size: 12px; color: #7a8c84; margin: 0; }
    .ar-file-input { display: none; }

    .ar-file-box { display: none; align-items: center; gap: 12px; margin-top: 12px; padding: 12px 14px; border: 1px solid #e2e6e4; border-radius: 10px; background: #fff; }
    .ar-file-box.show { display: flex; }
    .ar-file-ico { width: 38px; height: 38px; border-radius: 9px; background: #0f5c33; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0; }
    .ar-file-info { flex: 1; min-width: 0; }
    .ar-file-name { font-size: 14px; font-weight: 500; color: #2b332f; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ar-file-size { font-size: 12px; color: #7a8c84; }
    .ar-file-remove { border: none; background: #fde8e8; color: #a12626; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; font-size: 13px; flex-shrink: 0; }
    .ar-file-remove:hover { background: #f9d0d0; }

    .ar-current { margin-top: 12px; padding: 10px 14px; border-radius: 10px; background: #eef7f1; border: 1px solid #cfe6d8; font-size: 13px; color: #2b332f; word-break: break-all; }
    .ar-current a { color: #0f5c33; font-weight: 600; }

    .ar-actions { display: flex; gap: 10px; margin-top: 8px; padding-top: 18px; border-top: 1px solid #eef1ef; flex-wrap: wrap; }
    .ar-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 11px 22px; font-size: 15px; font-weight: 600; font-family: inherit; border-radius: 8px; border: 1px solid transparent; cursor: pointer; transition: all .15s ease; text-decoration: none; }
    .ar-btn-primary { background: #0f5c33; color: #fff; }
    .ar-btn-primary:hover { background: #1a7a45; box-shadow: 0 4px 10px rgba(15, 92, 51, 0.25); color: #fff; }
    .ar-btn-secondary { background: #fff; color: #2b332f; border-color: #d3d9d6; }
    .ar-btn-secondary:hover { border-color: #7a8c84; color: #2b332f; }

    .ar-updated { font-size: 13px; color: #7a8c84; margin-bottom: 14px; }
    .ar-updated i { color: #0f5c33; margin-right: 4px; }

    /* ---------- SECTION TABS ---------- */
    .ar-tabs { display: flex; gap: 8px; margin-bottom: 22px; overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 4px; }
    .ar-tab { flex: 0 0 auto; padding: 9px 16px; border: 1px solid #d3d9d6; border-radius: 8px; background: #fff; color: #2b332f; font-size: 14px; font-weight: 600; text-decoration: none; white-space: nowrap; transition: all .15s ease; }
    .ar-tab:hover { border-color: #1a7a45; color: #0f5c33; background: #eef7f1; }
    .ar-tab.active { background: #0f5c33; border-color: #0f5c33; color: #fff; }

    /* ---------- CURRENT PDF LIST (Working Group) ---------- */
    .ar-table-wrap { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .ar-table { width: 100%; border-collapse: collapse; min-width: 480px; }
    .ar-table th { text-align: left; font-size: 12px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; color: #0f5c33; background: #f3f6f4; padding: 11px 12px; border-bottom: 1px solid #e2e6e4; white-space: nowrap; }
    .ar-table td { font-size: 14px; color: #2b332f; padding: 12px; border-bottom: 1px solid #eef1ef; vertical-align: middle; }
    .ar-table tr:hover td { background: #f8faf9; }
    .ar-t-file { display: flex; align-items: center; gap: 10px; word-break: break-word; }
    .ar-t-file i { color: #c0392b; font-size: 18px; flex-shrink: 0; }
    .ar-t-title { font-weight: 600; }
    .ar-t-sub { font-size: 12px; color: #7a8c84; margin-top: 3px; word-break: break-word; }
    .ar-act { display: flex; gap: 6px; flex-wrap: nowrap; }
    .ar-icon-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #d3d9d6; background: #fff; color: #2b332f; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; font-size: 13px; text-decoration: none; transition: all .15s ease; padding: 0; }
    .ar-icon-btn:hover { border-color: #1a7a45; color: #0f5c33; background: #eef7f1; }
    .ar-live { display: inline-block; margin-left: 8px; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 20px; background: #e3f6ea; color: #0f5c33; vertical-align: middle; }
    .ar-empty { text-align: center; padding: 30px 10px; color: #7a8c84; font-size: 14px; }

    html, body { scrollbar-width: none; -ms-overflow-style: none; }
    html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }

    @media (min-width: 1920px) {
        .ar-title { font-size: 30px; }
        .ar-card { padding: 30px; }
    }
    @media (max-width: 700px) {
        .ar-title { font-size: 20px; }
        .ar-card { padding: 16px; border-radius: 10px; }
        .ar-drop { padding: 24px 12px; }
    }
    @media (max-width: 560px) {
        .ar-header { flex-direction: column; align-items: flex-start; margin-bottom: 14px; gap: 8px; }
        .ar-subtitle { font-size: 13px; }
        .ar-actions { flex-direction: column; }
        .ar-btn { width: 100%; }
    }
</style>

<?php if (!empty($secmsg)) { ?>
    <div class="message">
        <?php echo htmlspecialchars($secmsg); ?>
    </div>
<?php } ?>


<?php if (empty($secmsg)) { ?>

    <div class="submenu">
        <div style="float:right;">
            <a style="float:left;" href="../dashboard.php">Dashboard</a>
            <a style="float:left; margin-left:5px;" href="../index.php?q=logout">Logout</a>
        </div>
    </div>

    <div class="main-content">

        <div class="ar-header">
            <div>
                <h1 class="ar-title">ORGANISATION &amp; MANAGEMENT</h1>
                <p class="ar-subtitle">
                    <?php echo htmlspecialchars($secName); ?> &mdash;
                    <?php echo $textOnly ? 'edit the content shown on the website page' : 'upload a PDF file'; ?>
                </p>
            </div>
        </div>

        <div class="ar-tabs">
            <?php foreach ($sections as $tKey => $tLabel) { ?>
                <a class="ar-tab<?php echo $tKey === $secKey ? ' active' : ''; ?>"
                   href="<?php echo htmlspecialchars(orgHtmlUrl($tKey)); ?>">
                    <?php echo htmlspecialchars($tLabel); ?>
                </a>
            <?php } ?>
        </div>

        <?php if ($flash !== '') { ?>
            <div class="message <?php echo htmlspecialchars($flashType); ?>">
                <?php echo htmlspecialchars($flash); ?>
            </div>
        <?php } ?>

        <div id="arMsg" class="message" style="display:none;"></div>


        <!-- ===================== EDITOR ===================== -->
        <div class="ar-card">

            <div class="ar-card-title"><?php echo htmlspecialchars($secName); ?> <?php echo $textOnly ? 'Content' : 'PDF'; ?></div>

            <?php if ($lastUpdated !== '') { ?>
                <div class="ar-updated"><i class="fas fa-clock"></i> Last updated: <?php echo htmlspecialchars($lastUpdated); ?></div>
            <?php } ?>

            <form id="arForm" method="post" action="turf-console/organisationManager.php"<?php echo $textOnly ? '' : ' enctype="multipart/form-data"'; ?> novalidate>

                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                <input type="hidden" name="sec" value="<?php echo htmlspecialchars($secKey); ?>">

                <div class="ar-field">
                    <label for="arTitle">Title <span class="opt">(optional)</span></label>
                    <input type="text" id="arTitle" name="report_title" class="ar-input" maxlength="<?php echo (int)$maxTitle; ?>" placeholder="Enter title (optional)" value="<?php echo htmlspecialchars($fTitle, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="ar-hint">Leave this field empty to display the default heading on the website.</div>
                    <div class="ar-err" id="errTitle">Title is too long.</div>
                </div>

                <?php if ($textOnly) { ?>

                    <!-- TEXT (rich editor) -->
                    <div class="ar-field">
                        <label for="arContent">Content <span class="req">*</span></label>
                        <div class="ar-hint">
                            Use the Bold / Italic / Underline toolbar for text styling &nbsp;|&nbsp; Heading 2 = main underlined heading &nbsp;|&nbsp; Heading 3 = green underlined sub-heading<br>
                            Numbered list arrow = choose "Lower Roman" for (i), (ii), (iii). Tables: make the first row a header row (Table &gt; Row &gt; Row properties &gt; Row type: Header).
                        </div>
                        <textarea id="arContent" name="content"><?php echo htmlspecialchars($fText, ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <div class="ar-err" id="errContent">Please write some content.</div>
                    </div>

                <?php } else { ?>

                    <!-- PDF -->
                    <div class="ar-field">
                        <label>PDF File <span class="req">*</span></label>

                        <?php if ($fFile !== '') { ?>
                            <div class="ar-current" style="margin:0 0 12px;">
                                Current file:
                                <a href="<?php echo htmlspecialchars(orgFileUrl($fFile)); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars(orgFileLabel($fFile)); ?></a>
                                <br><span style="color:#7a8c84;">If you choose a new file, it will replace this one. Otherwise the current file is kept.</span>
                            </div>
                        <?php } ?>

                        <div class="ar-drop" id="arDrop">
                            <div class="ar-drop-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                            <p class="ar-drop-text">Drag &amp; drop PDF here or <span>browse</span></p>
                            <p class="ar-drop-sub">Only PDF files are allowed</p>
                        </div>

                        <input type="file" id="arFile" name="report_file" class="ar-file-input" accept="application/pdf,.pdf">

                        <div class="ar-file-box" id="arFileBox">
                            <div class="ar-file-ico"><i class="fas fa-file-pdf"></i></div>
                            <div class="ar-file-info">
                                <div class="ar-file-name" id="arFileName"></div>
                                <div class="ar-file-size" id="arFileSize"></div>
                            </div>
                            <button type="button" class="ar-file-remove" id="arFileRemove" title="Remove file">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <div class="ar-err" id="errFile">Please choose a PDF file.</div>
                    </div>

                <?php } ?>


                <div class="ar-actions">
                    <button type="submit" class="ar-btn ar-btn-primary">
                        <i class="fas fa-save"></i> Save Content
                    </button>
                    <button type="button" class="ar-btn ar-btn-secondary" id="arRevert">
                        <i class="fas fa-undo"></i> Revert Changes
                    </button>
                </div>

            </form>

        </div>


        <?php if (!$textOnly) { ?>

            <!-- ===================== CURRENT PDF (Working Group) ===================== -->
            <div class="ar-card">

                <div class="ar-card-title">Current PDF</div>

                <?php if ($fFile === '') { ?>

                    <div class="ar-empty">No PDF uploaded yet.</div>

                <?php } else { ?>

                    <div class="ar-table-wrap">
                        <table class="ar-table">
                            <thead>
                                <tr>
                                    <th style="width:120px;">Date</th>
                                    <th>File</th>
                                    <th style="width:90px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?php echo htmlspecialchars($lastUpdated); ?></td>
                                    <td>
                                        <div class="ar-t-file">
                                            <i class="fas fa-file-pdf"></i>
                                            <div>
                                                <?php if (trim($fTitle) !== '') { ?>
                                                    <div class="ar-t-title"><?php echo htmlspecialchars($fTitle); ?><span class="ar-live">LIVE</span></div>
                                                    <div class="ar-t-sub"><?php echo htmlspecialchars(orgFileLabel($fFile)); ?></div>
                                                <?php } else { ?>
                                                    <div class="ar-t-title"><?php echo htmlspecialchars(orgFileLabel($fFile)); ?><span class="ar-live">LIVE</span></div>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="ar-act">
                                            <a class="ar-icon-btn" title="View" target="_blank" rel="noopener" href="<?php echo htmlspecialchars(orgFileUrl($fFile)); ?>">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                <?php } ?>

            </div>

        <?php } ?>

    </div>


    <?php if ($textOnly) { ?>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
    <?php } ?>

    <script type="text/javascript">
        (function() {

            var isWG         = <?php echo json_encode(!$textOnly); ?>;
            var existingFile = <?php echo json_encode($fFile !== ''); ?>;
            var maxTitle     = <?php echo (int)$maxTitle; ?>;
            var savedContent = <?php echo json_encode($fText); ?>;

            var form    = document.getElementById('arForm');
            var titleIn = document.getElementById('arTitle');
            var revert  = document.getElementById('arRevert');
            var msgBox  = document.getElementById('arMsg');
            var msgTimer = null;
            var savedTitle = titleIn.value;

            // text tabs
            var area = document.getElementById('arContent');

            // Working Group (PDF)
            var drop     = document.getElementById('arDrop');
            var fileIn   = document.getElementById('arFile');
            var fileBox  = document.getElementById('arFileBox');
            var fileName = document.getElementById('arFileName');
            var fileSize = document.getElementById('arFileSize');
            var fileRem  = document.getElementById('arFileRemove');

            function showMsg(text, type) {
                msgBox.className = 'message ' + (type || '');
                msgBox.textContent = text;
                msgBox.style.display = 'block';
                clearTimeout(msgTimer);
                msgTimer = setTimeout(function() {
                    msgBox.style.display = 'none';
                }, 4000);
            }

            function setErr(errId, inputEl, show, text) {
                var e = document.getElementById(errId);
                if (!e) return;
                if (text) e.textContent = text;
                e.classList.toggle('show', show);
                if (inputEl) inputEl.classList.toggle('invalid', show);
            }

            titleIn.addEventListener('input', function() {
                if (titleIn.value.trim().length <= maxTitle) setErr('errTitle', titleIn, false);
            });

            // ---------------- TEXT TABS (rich editor) ----------------
            if (!isWG) {

                tinymce.init({
                    selector: '#arContent',
                    height: 620,
                    menubar: false,
                    branding: false,
                    promotion: false,
                    plugins: 'lists advlist link table code',
                    toolbar: 'undo redo | blocks fontsize | bold italic underline | alignleft aligncenter alignright | bullist numlist | link table | removeformat | code',
                    block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3; Heading 4=h4',
                    font_size_formats: '12px 14px 16px 18px 20px 24px 28px 32px',
                    advlist_number_styles: 'default,lower-roman,upper-roman,lower-alpha',
                    table_header_type: 'sectionCells',
                    paste_data_images: false,
                    paste_remove_styles_if_webkit: true,
                    content_style: 'body { font-family: Arial, sans-serif; font-size: 15px; line-height: 1.7; } table { border-collapse: collapse; width: 100%; } td, th { border: 1px solid #ccc; padding: 6px 8px; }'
                });
            }

            // ---------------- PDF (Working Group) ----------------
            function formatSize(b) {
                if (b < 1024) return b + ' B';
                if (b < 1048576) return (b / 1024).toFixed(1) + ' KB';
                if (b < 1073741824) return (b / 1048576).toFixed(2) + ' MB';
                return (b / 1073741824).toFixed(2) + ' GB';
            }

            function isPdf(file) {
                return /\.pdf$/i.test(file.name);
            }

            function showFile(file) {
                fileName.textContent = file.name;
                fileSize.textContent = formatSize(file.size);
                fileBox.classList.add('show');
                setErr('errFile', drop, false);
            }

            function clearFile() {
                fileIn.value = '';
                fileBox.classList.remove('show');
            }

            if (isWG) {

                // File: click and drag/drop
                drop.addEventListener('click', function() {
                    fileIn.click();
                });

                fileIn.addEventListener('change', function() {
                    if (fileIn.files && fileIn.files[0]) {
                        if (!isPdf(fileIn.files[0])) {
                            clearFile();
                            setErr('errFile', drop, true, 'Only PDF files are allowed.');
                            return;
                        }
                        showFile(fileIn.files[0]);
                    }
                });

                ['dragenter', 'dragover'].forEach(function(ev) {
                    drop.addEventListener(ev, function(e) {
                        e.preventDefault();
                        drop.classList.add('dragover');
                    });
                });
                ['dragleave', 'drop'].forEach(function(ev) {
                    drop.addEventListener(ev, function(e) {
                        e.preventDefault();
                        drop.classList.remove('dragover');
                    });
                });
                drop.addEventListener('drop', function(e) {
                    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
                        var f = e.dataTransfer.files[0];
                        if (!isPdf(f)) {
                            clearFile();
                            setErr('errFile', drop, true, 'Only PDF files are allowed.');
                            return;
                        }
                        try {
                            fileIn.files = e.dataTransfer.files;
                        } catch (err) {}
                        showFile(f);
                    }
                });

                fileRem.addEventListener('click', clearFile);
            }

            // ---------------- REVERT ----------------
            revert.addEventListener('click', function() {
                titleIn.value = savedTitle;
                setErr('errTitle', titleIn, false);
                if (isWG) {
                    clearFile();
                    setErr('errFile', drop, false);
                } else {
                    var ed = tinymce.get('arContent');
                    if (ed) ed.setContent(savedContent);
                    setErr('errContent', area, false);
                }
            });

            // ---------------- SUBMIT ----------------
            form.addEventListener('submit', function(e) {
                var t = titleIn.value.trim();

                if (t.length > maxTitle) {
                    e.preventDefault();
                    setErr('errTitle', titleIn, true, 'Title is too long (max ' + maxTitle + ' characters).');
                    showMsg('Title is too long.', 'error');
                    return;
                }

                if (isWG) {
                    var hasNew = fileIn.files && fileIn.files.length;
                    if (!hasNew && !existingFile) {
                        e.preventDefault();
                        setErr('errFile', drop, true, 'Please choose a PDF file.');
                        showMsg('Please fill all required fields correctly.', 'error');
                    } else if (hasNew && !isPdf(fileIn.files[0])) {
                        e.preventDefault();
                        setErr('errFile', drop, true, 'Only PDF files are allowed.');
                        showMsg('Only PDF files are allowed.', 'error');
                    }
                    return;
                }

                // Text tabs: copy the editor content into the textarea, then check it is not empty
                tinymce.triggerSave();
                if (!area.value.replace(/<[^>]*>|&nbsp;|\s/g, '')) {
                    e.preventDefault();
                    setErr('errContent', null, true, 'Please write some content.');
                    showMsg('Please fill all required fields correctly.', 'error');
                } else {
                    setErr('errContent', null, false);
                }
            });

        })();
    </script>

<?php } ?>


<?php

$design->closeDiv();
$design->writeLeftPanel();
$design->closeDiv();
$design->closeDiv();
$design->endPage();
$design = NULL;

?>