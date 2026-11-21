<?php

ini_set('display_errors', '0');
error_reporting(E_ALL);

include_once('../bootstrap.php');

require_once('../lib/users.class.php');
require_once('../lib/userchecks.php');
require_once('../lib/race.class.php');
require_once('../lib/permissions.php');
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../rwitc_website_api/config/config.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Admin page must never be cached
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

/* CONFIGURATION */

define('DOC_TYPE', 'chart');                        // club_menu_pdf.type
define('DOC_TITLE', 'Chart');                       // shown on the page
define('DOC_MODULE', 'chart_pdf_upload');          // permission module key
define('DOC_S3_KEY', 'uploads/club/Chart.pdf');    // fixed S3 key, replaced on every upload
define('DOC_FILE_NAME', 'CHART.pdf');               // name shown when the PDF is opened

define('CHART_MAX_SIZE', 10 * 1024 * 1024);

/* ADMIN AUTHENTICATION */

if (!isAdminlogin()) {
    http_response_code(403);
    exit('Please log in to access this page.');
}

if (!function_exists('hasModuleAccess') || !hasModuleAccess(DOC_MODULE)) {
    http_response_code(403);
    exit('You do not have permission to access this page.');
}

/* AWS S3 */

$s3Client = new S3Client(array(
    'version' => 'latest',
    'region' => AWS_REGION,
    'credentials' => array(
        'key' => AWS_ACCESS_KEY_ID,
        'secret' => AWS_SECRET_ACCESS_KEY
    )
));

/* HELPERS */

function chartEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function chartIstDate()
{
    $dt = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
    return $dt->format('Y-m-d');
}

/* CSRF TOKEN */

if (empty($_SESSION['chart_pdf_csrf'])) {
    $_SESSION['chart_pdf_csrf'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['chart_pdf_csrf'];

$message = '';
$messageType = 'success';
$fileExists = false;
$lastModified = null;
$fileSizeText = '';
$viewUrl = '';

/* UPLOAD PDF */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $postedToken = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';

    if (!hash_equals($csrfToken, $postedToken)) {
        http_response_code(403);
        exit('Invalid security token. Reload the page and try again.');
    }

    try {

        if (!isset($_FILES['chart_pdf']) || !is_array($_FILES['chart_pdf'])) {
            throw new RuntimeException('Please select a PDF file.');
        }

        $file = $_FILES['chart_pdf'];

        if ($file['error'] !== UPLOAD_ERR_OK) {

            $errors = array(
                UPLOAD_ERR_INI_SIZE => 'File exceeds the server upload limit.',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds the allowed size.',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
                UPLOAD_ERR_NO_FILE => 'Please select a PDF file.',
                UPLOAD_ERR_NO_TMP_DIR => 'Server temporary directory is missing.',
                UPLOAD_ERR_CANT_WRITE => 'Server could not save the file.',
                UPLOAD_ERR_EXTENSION => 'Server rejected the upload.'
            );

            throw new RuntimeException(
                isset($errors[$file['error']]) ? $errors[$file['error']] : 'Upload failed.'
            );
        }

        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Invalid uploaded file.');
        }

        if ($file['size'] <= 0 || $file['size'] > CHART_MAX_SIZE) {
            throw new RuntimeException('PDF size must be between 1 byte and 10 MB.');
        }

        $extension = strtolower(pathinfo(basename($file['name']), PATHINFO_EXTENSION));

        if ($extension !== 'pdf') {
            throw new RuntimeException('Only PDF files are allowed.');
        }

        if (!class_exists('finfo')) {
            throw new RuntimeException('File validation is unavailable on the server.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if ($mime !== 'application/pdf') {
            throw new RuntimeException('The uploaded file is not a valid PDF.');
        }

        $handle = fopen($file['tmp_name'], 'rb');

        if ($handle === false) {
            throw new RuntimeException('Unable to read the PDF.');
        }

        $signature = fread($handle, 5);
        fclose($handle);

        if ($signature !== '%PDF-') {
            throw new RuntimeException('Invalid PDF document.');
        }

        // 1) Upload to S3 (same key, so the old PDF is replaced)
        $result = $s3Client->putObject(array(
            'Bucket' => AWS_BUCKET,
            'Key' => DOC_S3_KEY,
            'SourceFile' => $file['tmp_name'],
            'ContentType' => 'application/pdf',
            'ContentDisposition' => 'inline; filename="' . DOC_FILE_NAME . '"',
            'CacheControl' => 'no-cache, must-revalidate'
        ));

        $newUrl = isset($result['ObjectURL'])
            ? (string) $result['ObjectURL']
            : $s3Client->getObjectUrl(AWS_BUCKET, DOC_S3_KEY);

        // 2) Save the S3 url in club_menu_pdf (one row per type: insert first time, update after)
        $type = DOC_TYPE;
        $existing = $db->getSingleRowAssoc(
            "SELECT id FROM club_menu_pdf WHERE `type` = '" . $type . "' ORDER BY id ASC LIMIT 1"
        );

        if ($existing) {
            $db->update(
                "UPDATE club_menu_pdf
                 SET file_url = '" . $db->escape($newUrl) . "',
                     `date` = '" . chartIstDate() . "'
                 WHERE id = " . (int) $existing['id'] . " AND `type` = '" . $type . "'"
            );

            // Remove accidental duplicate rows of this type
            $db->query(
                "DELETE FROM club_menu_pdf WHERE `type` = '" . $type . "' AND id <> " . (int) $existing['id']
            );
        } else {
            $db->insert(
                "INSERT INTO club_menu_pdf (`type`, `label`, file_url, `date`)
                 VALUES ('" . $type . "', '', '" . $db->escape($newUrl) . "', '" . chartIstDate() . "')"
            );
        }

        $_SESSION['chart_pdf_flash'] = array(
            'type' => 'success',
            'message' => DOC_TITLE . ' PDF uploaded successfully. The previous PDF has been replaced.'
        );

        header('Location: ' . $_SERVER['SCRIPT_NAME']);
        exit;

    } catch (Throwable $e) {

        error_log(DOC_TITLE . ' PDF upload error: ' . $e->getMessage());

        if ($e instanceof RuntimeException) {
            $message = $e->getMessage();
        } elseif ($e instanceof AwsException) {
            $message = 'S3 upload failed. Check AWS configuration and permissions.';
        } else {
            $message = 'Upload failed. Please check server logs.';
        }

        $messageType = 'error';
    }
}

/* FLASH MESSAGE */

if (isset($_SESSION['chart_pdf_flash'])) {

    $message = $_SESSION['chart_pdf_flash']['message'];
    $messageType = $_SESSION['chart_pdf_flash']['type'];

    unset($_SESSION['chart_pdf_flash']);
}

/* CURRENT FILE STATUS (from the table row, time from S3 in India time) */

try {

    $row = $db->getSingleRowAssoc(
        "SELECT id, file_url FROM club_menu_pdf WHERE `type` = '" . DOC_TYPE . "' ORDER BY id ASC LIMIT 1"
    );

    if ($row && !empty($row['file_url'])) {

        $head = $s3Client->headObject(array(
            'Bucket' => AWS_BUCKET,
            'Key' => DOC_S3_KEY
        ));

        $fileExists = true;
        $viewVersion = time();

        if (isset($head['LastModified'])) {
            $viewVersion = $head['LastModified']->getTimestamp();

            // S3 returns UTC, convert to India time
            $dt = new DateTime('@' . $viewVersion);
            $dt->setTimezone(new DateTimeZone('Asia/Kolkata'));
            $lastModified = $dt->format('d M Y, h:i:s A');
        }

        if (isset($head['ContentLength'])) {
            $bytes = (int) $head['ContentLength'];
            $fileSizeText = $bytes >= 1024 * 1024
                ? number_format($bytes / (1024 * 1024), 2) . ' MB'
                : number_format($bytes / 1024, 1) . ' KB';
        }

        $viewUrl = (string) $row['file_url'] . '?v=' . $viewVersion;
    }

} catch (Throwable $e) {

    error_log(DOC_TITLE . ' PDF status error: ' . $e->getMessage());

    if ($message === '') {
        $message = 'Unable to verify the current file status.';
        $messageType = 'error';
    }
}

/* PAGE LAYOUT */

$design = new Design();

$design->js = '
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
';

$design->css = '';
$design->jqueryJs = '';

$design->startPage(DOC_TITLE . ' PDF Upload');
$design->writeLogoTickerMenu();

$design->openDiv('contentWrapper');
$design->openDiv('infoWrapper', 'col-lg-12');
$design->openDiv('leftArea', 'col-lg-9');

$design->writeContentPageStyles();

?>

<style>
#infoWrapper.col-lg-12 {
    display: flex;
    flex-direction: row-reverse;
    align-items: flex-start;
    max-width: 1500px;
    margin: 25px auto;
    float: none;
}

#leftArea.col-lg-9 {
    flex: 1 1 auto;
    min-width: 0;
    max-width: none;
    width: auto;
    padding: 0 30px;
    box-sizing: border-box;
    float: none;
}

#leftArea .chart-root,
#leftArea .chart-root * {
    box-sizing: border-box;
}

.chart-root {
    --green: #0f5c33;
    --green-dark: #0b4627;
    --green-mid: #1a7a45;
    --green-light: #e8f5ed;
    --green-soft: #f3f9f5;
    --text: #1f2f26;
    --muted: #6f7f75;
    --border: #e1e8e3;
    --danger: #c0372b;
    position: relative;
    max-width: 860px;
    color: var(--text);
    font-family: inherit;
}

/* ---------- Header ---------- */
.chart-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 26px;
}

.chart-header-icon {
    width: 54px;
    height: 54px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
    background: linear-gradient(135deg, var(--green-mid), var(--green-dark));
    color: #fff;
    font-size: 23px;
    box-shadow: 0 6px 14px rgba(15, 92, 51, .22);
}

.chart-header h2 {
    margin: 0;
    color: var(--text);
    font-size: 26px;
    font-weight: 700;
}

.chart-header p {
    margin: 5px 0 0;
    color: var(--muted);
    font-size: 14px;
    line-height: 1.5;
}

/* ---------- Message ---------- */
.chart-message {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 20px;
    padding: 14px 16px;
    border-radius: 12px;
    font-size: 14px;
    line-height: 1.5;
}

.chart-message i {
    margin-top: 2px;
    font-size: 16px;
}

.chart-message.success {
    background: var(--green-light);
    border: 1px solid #b7ddc5;
    color: var(--green);
}

.chart-message.error {
    background: #fff1f0;
    border: 1px solid #f0c4c4;
    color: #a32121;
}

/* ---------- Card ---------- */
.chart-card {
    padding: 26px;
    margin-bottom: 22px;
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 16px;
    box-shadow: 0 4px 14px rgba(20, 40, 28, .05);
}

.chart-card h3 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border);
    color: var(--green);
    font-size: 16px;
    font-weight: 700;
}

/* ---------- Current file status ---------- */
.chart-status {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 18px;
    margin-bottom: 26px;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: var(--green-soft);
}

.chart-status-icon {
    width: 46px;
    height: 46px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 11px;
    background: #fdecea;
    color: var(--danger);
    font-size: 20px;
}

.chart-status-icon.is-ok {
    background: #e4f7eb;
    color: #176b3d;
}

.chart-status-body {
    min-width: 0;
    flex: 1 1 auto;
}

.chart-status h4 {
    margin: 1px 0 6px;
    font-size: 15px;
    font-weight: 700;
    color: var(--text);
    overflow-wrap: anywhere;
}

.chart-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 11px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}

.chart-badge.active {
    background: #e4f7eb;
    color: #176b3d;
}

.chart-badge.missing {
    background: #fff3cd;
    color: #856404;
}

.chart-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 6px 20px;
    margin-top: 12px;
}

.chart-status p {
    margin: 0;
    color: var(--muted);
    font-size: 12.5px;
    line-height: 1.6;
    overflow-wrap: anywhere;
}

.chart-status p i {
    width: 14px;
    margin-right: 4px;
    color: var(--green-mid);
}

.chart-view-row {
    margin-top: 14px;
}

/* ---------- Upload form ---------- */
.chart-label {
    display: block;
    margin-bottom: 10px;
    font-size: 13px;
    font-weight: 700;
    color: var(--text);
}

.chart-file-input {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
    overflow: hidden;
    pointer-events: none;
}

.chart-dropzone {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 24px 22px;
    border: 2px dashed #c3d3c9;
    border-radius: 14px;
    background: var(--green-soft);
    cursor: pointer;
    transition: .2s ease;
}

.chart-dropzone:hover,
.chart-dropzone.is-drag {
    border-color: var(--green-mid);
    background: #eaf4ee;
}

.chart-dropzone.has-file {
    border-style: solid;
    border-color: var(--green-mid);
    background: var(--green-light);
}

.chart-file-input:focus-visible + .chart-dropzone {
    outline: 3px solid #b7ddc5;
    outline-offset: 2px;
}

.chart-upload-icon {
    width: 50px;
    height: 50px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #fff;
    color: var(--green);
    font-size: 20px;
    box-shadow: 0 2px 8px rgba(20, 40, 28, .08);
}

.chart-file-text {
    min-width: 0;
}

.chart-file-name {
    display: block;
    font-size: 14px;
    font-weight: 700;
    color: var(--text);
    overflow-wrap: anywhere;
}

.chart-help {
    display: block;
    margin-top: 5px;
    color: var(--muted);
    font-size: 12px;
    line-height: 1.6;
}

.chart-inline-error {
    margin-top: 10px;
    padding: 10px 14px;
    border-radius: 9px;
    background: #fff1f0;
    border: 1px solid #f0c4c4;
    color: #a32121;
    font-size: 12.5px;
    line-height: 1.5;
}

.chart-inline-error[hidden] {
    display: none;
}

.chart-warning {
    display: flex;
    gap: 10px;
    margin-top: 18px;
    padding: 13px 15px;
    border-radius: 10px;
    background: #fff8e7;
    color: #805b10;
    font-size: 12.5px;
    line-height: 1.6;
}

.chart-warning i {
    margin-top: 3px;
}

.chart-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 22px;
    padding-top: 20px;
    border-top: 1px solid var(--border);
}

/* ---------- Buttons ---------- */
.chart-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 44px;
    padding: 0 20px;
    border: 1px solid transparent;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    transition: .2s ease;
}

.chart-btn-primary {
    background: var(--green);
    color: #fff;
}

.chart-btn-primary:hover {
    background: var(--green-dark);
    color: #fff;
}

.chart-btn-secondary {
    background: #fff;
    color: var(--text);
    border-color: #d5ded8;
}

.chart-btn-secondary:hover {
    background: var(--green-soft);
    color: var(--green);
}

.chart-btn:disabled {
    opacity: .65;
    cursor: not-allowed;
}

/* ---------- Hardening against global site CSS ---------- */
#leftArea .chart-root .chart-header h2 {
    margin: 0 !important;
    padding: 0 !important;
    border: 0 !important;
    text-decoration: none !important;
    text-transform: none !important;
    line-height: 1.25 !important;
}

#leftArea .chart-root .chart-header p {
    margin: 5px 0 0 !important;
    padding: 0 !important;
    border: 0 !important;
}

#leftArea .chart-root .chart-card h3 {
    margin: 0 0 20px !important;
    padding: 0 0 16px !important;
    border: 0 !important;
    border-bottom: 1px solid var(--border) !important;
    text-transform: none !important;
}

#leftArea .chart-root .chart-status h4 {
    margin: 1px 0 6px !important;
    padding: 0 !important;
    border: 0 !important;
    text-transform: none !important;
}

#leftArea .chart-root .chart-status p {
    margin: 0 !important;
    padding: 0 !important;
}

#leftArea .chart-root label.chart-label {
    display: block !important;
    margin: 0 0 10px !important;
    padding: 0 !important;
    float: none !important;
    width: auto !important;
}

#leftArea .chart-root label.chart-dropzone {
    display: flex !important;
    margin: 0 !important;
    float: none !important;
    width: 100% !important;
}

#leftArea .chart-root .chart-btn {
    height: auto;
    line-height: 1.2 !important;
    font-family: inherit !important;
    text-transform: none !important;
    letter-spacing: normal !important;
    box-shadow: none !important;
}

#leftArea .chart-root a.chart-btn-secondary,
#leftArea .chart-root button.chart-btn-secondary {
    background: #fff !important;
    color: var(--text) !important;
    border: 1px solid #d5ded8 !important;
}

#leftArea .chart-root a.chart-btn-secondary:hover,
#leftArea .chart-root button.chart-btn-secondary:hover {
    background: var(--green-soft) !important;
    color: var(--green) !important;
}

#leftArea .chart-root button.chart-btn-primary {
    background: var(--green) !important;
    color: #fff !important;
    border: 1px solid var(--green) !important;
}

#leftArea .chart-root button.chart-btn-primary:hover {
    background: var(--green-dark) !important;
}

#leftArea .chart-root .chart-btn-sm {
    min-height: 36px;
    padding: 0 14px;
    font-size: 12px;
}

/* ---------- Responsive ---------- */
@media (max-width: 900px) {
    #infoWrapper.col-lg-12 {
        flex-direction: column;
        margin: 15px auto;
    }

    #leftArea.col-lg-9 {
        width: 100%;
        padding: 15px 20px;
    }
}

@media (max-width: 560px) {
    #leftArea.col-lg-9 {
        padding: 10px;
    }

    .chart-header h2 {
        font-size: 21px;
    }

    .chart-header-icon {
        width: 44px;
        height: 44px;
        font-size: 19px;
    }

    .chart-card {
        padding: 18px;
    }

    .chart-status {
        flex-direction: column;
        gap: 12px;
    }

    .chart-dropzone {
        padding: 16px;
    }

    .chart-actions .chart-btn,
    .chart-view-row .chart-btn {
        width: 100%;
    }
}

@media (prefers-reduced-motion: reduce) {
    .chart-root * {
        transition: none !important;
    }
}
</style>

<div class="chart-root">

    <div class="chart-header">
        <div class="chart-header-icon">
            <i class="fas fa-file-pdf"></i>
        </div>

        <div>
            <h2><?php echo chartEscape(DOC_TITLE); ?> PDF Upload</h2>
            <p>Upload and replace the <?php echo chartEscape(DOC_TITLE); ?> PDF displayed on the public website.</p>
        </div>
    </div>

    <?php if ($message !== '') { ?>
        <div
            class="chart-message <?php echo chartEscape($messageType); ?>"
            role="<?php echo $messageType === 'error' ? 'alert' : 'status'; ?>"
        >
            <i class="fas <?php echo $messageType === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check'; ?>"></i>

            <div><?php echo chartEscape($message); ?></div>
        </div>
    <?php } ?>

    <div class="chart-card">

        <h3>
            <i class="fas fa-file-circle-check"></i>
            Current <?php echo chartEscape(DOC_TITLE); ?> PDF
        </h3>

        <div class="chart-status">

            <div class="chart-status-icon<?php echo $fileExists ? ' is-ok' : ''; ?>">
                <i class="fas fa-file-pdf"></i>
            </div>

            <div class="chart-status-body">
                <h4><?php echo chartEscape(DOC_FILE_NAME); ?></h4>

                <?php if ($fileExists) { ?>

                    <span class="chart-badge active">
                        <i class="fas fa-check-circle"></i>
                        PDF uploaded
                    </span>

                    <div class="chart-meta">
                        <?php if ($lastModified) { ?>
                            <p class="chart-modified">
                                <i class="far fa-clock"></i>
                                Last modified: <?php echo chartEscape($lastModified); ?>
                            </p>
                        <?php } ?>

                        <?php if ($fileSizeText !== '') { ?>
                            <p>
                                <i class="far fa-hard-drive"></i>
                                Size: <?php echo chartEscape($fileSizeText); ?>
                            </p>
                        <?php } ?>
                    </div>

                    <div class="chart-view-row">
                        <a
                            href="<?php echo chartEscape($viewUrl); ?>"
                            target="_blank"
                            rel="noopener"
                            class="chart-btn chart-btn-secondary chart-btn-sm"
                        >
                            <i class="fas fa-arrow-up-right-from-square"></i>
                            View current PDF
                        </a>
                    </div>

                <?php } else { ?>

                    <span class="chart-badge missing">
                        <i class="fas fa-exclamation-circle"></i>
                        PDF not uploaded yet
                    </span>

                <?php } ?>

            </div>
        </div>

        <form
            method="POST"
            action="<?php echo chartEscape($_SERVER['SCRIPT_NAME']); ?>"
            enctype="multipart/form-data"
            id="chart-upload-form"
        >

            <input type="hidden" name="csrf_token" value="<?php echo chartEscape($csrfToken); ?>">

            <label class="chart-label" for="chart_pdf">
                Select <?php echo chartEscape(DOC_TITLE); ?> PDF
            </label>

            <input
                type="file"
                name="chart_pdf"
                id="chart_pdf"
                class="chart-file-input"
                accept=".pdf,application/pdf"
                required
            >

            <label for="chart_pdf" class="chart-dropzone" id="chart-dropzone">
                <span class="chart-upload-icon">
                    <i class="fas fa-file-arrow-up" id="chart-upload-icon"></i>
                </span>

                <span class="chart-file-text">
                    <span class="chart-file-name" id="chart-file-name">
                        Click to select a PDF or drag it here
                    </span>

                    <span class="chart-help">
                        PDF only &middot; Maximum file size: 10 MB
                    </span>
                </span>
            </label>

            <div class="chart-inline-error" id="chart-error" role="alert" hidden></div>

            <div class="chart-warning">
                <i class="fas fa-triangle-exclamation"></i>

                <span>
                    Uploading a new PDF replaces the existing
                    <?php echo chartEscape(DOC_FILE_NAME); ?>.
                    Use this module only for the <?php echo chartEscape(DOC_TITLE); ?> document.
                </span>
            </div>

            <div class="chart-actions">

                <button type="submit" class="chart-btn chart-btn-primary" id="chart-submit">
                    <i class="fas fa-cloud-arrow-up"></i>
                    Upload <?php echo chartEscape(DOC_TITLE); ?> PDF
                </button>

                <button type="reset" class="chart-btn chart-btn-secondary" id="chart-reset">
                    <i class="fas fa-rotate-left"></i>
                    Clear
                </button>

            </div>

        </form>

    </div>
</div>

<script>
(function () {
    'use strict';

    var input = document.getElementById('chart_pdf');
    var zone = document.getElementById('chart-dropzone');
    var title = document.getElementById('chart-file-name');
    var icon = document.getElementById('chart-upload-icon');
    var errorBox = document.getElementById('chart-error');
    var form = document.getElementById('chart-upload-form');
    var submit = document.getElementById('chart-submit');
    var reset = document.getElementById('chart-reset');

    if (!input || !zone || !form) {
        return;
    }

    var MAX_SIZE = 10 * 1024 * 1024;
    var defaultText = 'Click to select a PDF or drag it here';
    var submitHtml = submit ? submit.innerHTML : '';

    function formatSize(bytes) {
        return bytes >= 1024 * 1024
            ? (bytes / (1024 * 1024)).toFixed(2) + ' MB'
            : (bytes / 1024).toFixed(1) + ' KB';
    }

    function showError(text) {
        errorBox.textContent = text;
        errorBox.hidden = false;
    }

    function clearError() {
        errorBox.textContent = '';
        errorBox.hidden = true;
    }

    // Returns an error text, or an empty string when the file is fine
    function validate(file) {
        if (!/\.pdf$/i.test(file.name)) {
            return 'Only PDF files are allowed.';
        }

        if (file.size <= 0 || file.size > MAX_SIZE) {
            return 'PDF size must be between 1 byte and 10 MB.';
        }

        return '';
    }

    function refresh() {
        if (input.files && input.files.length) {
            var file = input.files[0];

            title.textContent = file.name + ' (' + formatSize(file.size) + ')';
            zone.classList.add('has-file');
            icon.className = 'fas fa-file-pdf';

            var problem = validate(file);

            if (problem) {
                showError(problem);
            } else {
                clearError();
            }
        } else {
            title.textContent = defaultText;
            zone.classList.remove('has-file');
            icon.className = 'fas fa-file-arrow-up';
            clearError();
        }
    }

    input.addEventListener('change', refresh);

    ['dragenter', 'dragover'].forEach(function (eventName) {
        zone.addEventListener(eventName, function (event) {
            event.preventDefault();
            zone.classList.add('is-drag');
        });
    });

    ['dragleave', 'drop'].forEach(function (eventName) {
        zone.addEventListener(eventName, function (event) {
            event.preventDefault();
            zone.classList.remove('is-drag');
        });
    });

    zone.addEventListener('drop', function (event) {
        if (event.dataTransfer && event.dataTransfer.files.length) {
            input.files = event.dataTransfer.files;
            refresh();
        }
    });

    form.addEventListener('submit', function (event) {
        if (!input.files || !input.files.length) {
            event.preventDefault();
            showError('Please select a PDF file.');
            return;
        }

        var problem = validate(input.files[0]);

        if (problem) {
            event.preventDefault();
            showError(problem);
            return;
        }

        if (submit) {
            submit.disabled = true;
            submit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
        }
    });

    if (reset) {
        reset.addEventListener('click', function () {
            setTimeout(function () {
                refresh();

                if (submit) {
                    submit.disabled = false;
                    submit.innerHTML = submitHtml;
                }
            }, 0);
        });
    }
})();
</script>

<?php

$design->closeDiv(); // leftArea
$design->writeLeftPanel();
$design->closeDiv(); // infoWrapper
$design->closeDiv(); // contentWrapper
$design->endPage();

$design = null;

?>