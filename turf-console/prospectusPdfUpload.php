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

/* CONFIGURATION */

define('PROSPECTUS_S3_KEY', 'uploads/ApplicationForms/PROSPECTUS.pdf');
define('PROSPECTUS_MAX_SIZE', 10 * 1024 * 1024);

/* ADMIN AUTHENTICATION */

if (!isAdminlogin()) {
    http_response_code(403);
    exit('Please log in to access this page.');
}

if (
    !function_exists('hasModuleAccess') ||
    !hasModuleAccess('prospectus_pdf_upload')
) {
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

function prospectusEscape($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function prospectusObjectExists($s3Client)
{
    try {
        $s3Client->headObject(array(
            'Bucket' => AWS_BUCKET,
            'Key' => PROSPECTUS_S3_KEY
        ));

        return true;

    } catch (AwsException $e) {

        $status = $e->getStatusCode();
        $code = $e->getAwsErrorCode();

        if (
            $status === 404 ||
            $code === 'NoSuchKey' ||
            $code === 'NotFound'
        ) {
            return false;
        }

        error_log('Prospectus S3 check failed: ' . $e->getMessage());

        throw new RuntimeException(
            'Unable to check the current Prospectus PDF.'
        );
    }
}

/* CSRF TOKEN */

if (empty($_SESSION['prospectus_pdf_csrf'])) {
    $_SESSION['prospectus_pdf_csrf'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['prospectus_pdf_csrf'];

$message = '';
$messageType = 'success';
$fileExists = false;
$lastModified = null;

/* UPLOAD PDF */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $postedToken = isset($_POST['csrf_token'])
        ? (string) $_POST['csrf_token']
        : '';

    if (!hash_equals($csrfToken, $postedToken)) {
        http_response_code(403);
        exit('Invalid security token. Reload the page and try again.');
    }

    try {

        if (
            !isset($_FILES['prospectus_pdf']) ||
            !is_array($_FILES['prospectus_pdf'])
        ) {
            throw new RuntimeException('Please select a PDF file.');
        }

        $file = $_FILES['prospectus_pdf'];

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
                isset($errors[$file['error']])
                    ? $errors[$file['error']]
                    : 'Upload failed.'
            );
        }

        if (
            empty($file['tmp_name']) ||
            !is_uploaded_file($file['tmp_name'])
        ) {
            throw new RuntimeException('Invalid uploaded file.');
        }

        if (
            $file['size'] <= 0 ||
            $file['size'] > PROSPECTUS_MAX_SIZE
        ) {
            throw new RuntimeException(
                'PDF size must be between 1 byte and 10 MB.'
            );
        }

        $extension = strtolower(
            pathinfo(basename($file['name']), PATHINFO_EXTENSION)
        );

        if ($extension !== 'pdf') {
            throw new RuntimeException('Only PDF files are allowed.');
        }

        if (!class_exists('finfo')) {
            throw new RuntimeException(
                'File validation is unavailable on the server.'
            );
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if ($mime !== 'application/pdf') {
            throw new RuntimeException(
                'The uploaded file is not a valid PDF.'
            );
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

        /*
         * Uploading to the same key replaces the existing object.
         */

        $s3Client->putObject(array(
            'Bucket' => AWS_BUCKET,
            'Key' => PROSPECTUS_S3_KEY,
            'SourceFile' => $file['tmp_name'],
            'ContentType' => 'application/pdf',
            'ContentDisposition' => 'inline; filename="PROSPECTUS.pdf"',
            'CacheControl' => 'no-cache, must-revalidate'
        ));

        $_SESSION['prospectus_pdf_flash'] = array(
            'type' => 'success',
            'message' => 'Prospectus PDF uploaded successfully. The previous PDF has been replaced.'
        );

        header('Location: ' . $_SERVER['SCRIPT_NAME']);
        exit;

    } catch (Throwable $e) {

        error_log('Prospectus PDF upload error: ' . $e->getMessage());

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

if (isset($_SESSION['prospectus_pdf_flash'])) {

    $message = $_SESSION['prospectus_pdf_flash']['message'];
    $messageType = $_SESSION['prospectus_pdf_flash']['type'];

    unset($_SESSION['prospectus_pdf_flash']);
}

/* CURRENT FILE STATUS */

try {

    $fileExists = prospectusObjectExists($s3Client);

    if ($fileExists) {

        $head = $s3Client->headObject(array(
            'Bucket' => AWS_BUCKET,
            'Key' => PROSPECTUS_S3_KEY
        ));

        $lastModified = isset($head['LastModified'])
            ? $head['LastModified']->format('d M Y, h:i A')
            : null;
    }

} catch (Throwable $e) {

    error_log('Prospectus PDF status error: ' . $e->getMessage());

    if ($message === '') {
        $message = 'Unable to verify the current S3 file status.';
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

$design->startPage('Prospectus PDF Upload');
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

#leftArea .prospectus-root,
#leftArea .prospectus-root * {
    box-sizing: border-box;
}

.prospectus-root {
    --green: #0f5c33;
    --green-dark: #0b4627;
    --green-light: #e8f5ed;
    --text: #1f2f26;
    --muted: #6f7f75;
    --border: #e1e8e3;
    position: relative;
    color: var(--text);
    font-family: inherit;
}

.prospectus-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 24px;
}

.prospectus-header-icon {
    width: 50px;
    height: 50px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: var(--green-light);
    color: var(--green);
    font-size: 22px;
}

.prospectus-header h2 {
    margin: 0;
    color: var(--text);
    font-size: 25px;
    font-weight: 700;
}

.prospectus-header p {
    margin: 5px 0 0;
    color: var(--muted);
    font-size: 14px;
}

.prospectus-card {
    padding: 25px;
    margin-bottom: 22px;
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 14px;
    box-shadow: 0 2px 8px rgba(20,40,28,.04);
}

.prospectus-card h3 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 18px;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--border);
    color: var(--green);
    font-size: 16px;
    font-weight: 700;
}

.prospectus-status {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 17px;
    margin-bottom: 22px;
    border: 1px solid var(--border);
    border-radius: 10px;
    background: #fafcfb;
}

.prospectus-status-body {
    min-width: 0;
    flex: 1 1 auto;
}

.prospectus-status-icon {
    width: 42px;
    height: 42px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 9px;
    background: #fdecea;
    color: #c0372b;
    font-size: 19px;
}

.prospectus-status h4 {
    margin: 1px 0 5px;
    font-size: 14px;
    font-weight: 700;
}

.prospectus-status p {
    margin: 0;
    color: var(--muted);
    font-size: 12px;
    line-height: 1.6;
    overflow-wrap: anywhere;
}

.prospectus-badge {
    display: inline-block;
    margin-top: 8px;
    padding: 5px 9px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}

.prospectus-badge.active {
    background: #e4f7eb;
    color: #176b3d;
}

.prospectus-badge.missing {
    background: #fff3cd;
    color: #856404;
}

.prospectus-label {
    display: block;
    margin-bottom: 9px;
    font-size: 13px;
    font-weight: 700;
}

.prospectus-file-input {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
    overflow: hidden;
    pointer-events: none;
}

.prospectus-dropzone {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 20px;
    border: 2px dashed #c9d6ce;
    border-radius: 11px;
    background: #f5faf7;
    cursor: pointer;
    transition: .2s ease;
}

.prospectus-dropzone:hover,
.prospectus-dropzone.is-drag {
    border-color: var(--green);
    background: #eaf4ee;
}

.prospectus-file-input:focus-visible + .prospectus-dropzone {
    outline: 3px solid #b7ddc5;
}

.prospectus-upload-icon {
    width: 44px;
    height: 44px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #fff;
    color: var(--green);
    font-size: 19px;
}

.prospectus-file-text {
    min-width: 0;
}

.prospectus-file-name {
    display: block;
    font-size: 14px;
    font-weight: 700;
    overflow-wrap: anywhere;
}

.prospectus-help {
    display: block;
    margin-top: 5px;
    color: var(--muted);
    font-size: 12px;
    line-height: 1.6;
}

.prospectus-warning {
    display: flex;
    gap: 10px;
    margin-top: 17px;
    padding: 12px 14px;
    border-radius: 9px;
    background: #fff8e7;
    color: #805b10;
    font-size: 12px;
    line-height: 1.6;
}

.prospectus-message {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 20px;
    padding: 14px 16px;
    border-radius: 10px;
    font-size: 13px;
    line-height: 1.5;
}

.prospectus-message.success {
    background: #e8f5ed;
    border: 1px solid #b7ddc5;
    color: #0f5c33;
}

.prospectus-message.error {
    background: #fff1f0;
    border: 1px solid #f0c4c4;
    color: #a32121;
}

.prospectus-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 20px;
}

.prospectus-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 42px;
    padding: 0 18px;
    border: 1px solid transparent;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    transition: .2s ease;
}

.prospectus-btn-primary {
    background: var(--green);
    color: #fff;
}

.prospectus-btn-primary:hover {
    background: var(--green-dark);
    color: #fff;
}

.prospectus-btn-secondary {
    background: #fff;
    color: var(--text);
    border-color: #d5ded8;
}

.prospectus-btn-secondary:hover {
    background: #f5faf7;
    color: var(--green);
}

.prospectus-btn:disabled {
    opacity: .65;
    cursor: not-allowed;
}

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

    .prospectus-header h2 {
        font-size: 21px;
    }

    .prospectus-card {
        padding: 17px;
    }

    .prospectus-dropzone {
        padding: 14px;
    }

    .prospectus-actions .prospectus-btn {
        width: 100%;
    }
}

/* ======================================================================
   Hardening against global site CSS + finishing touches
   ====================================================================== */
#leftArea .prospectus-root .prospectus-header h2 {
    margin: 0 !important;
    padding: 0 !important;
    border: 0 !important;
    text-decoration: none !important;
    text-transform: none !important;
    line-height: 1.25 !important;
}

#leftArea .prospectus-root .prospectus-header p {
    margin: 5px 0 0 !important;
    padding: 0 !important;
    border: 0 !important;
}

#leftArea .prospectus-root .prospectus-card h3 {
    margin: 0 0 18px !important;
    padding: 0 0 15px !important;
    border: 0 !important;
    border-bottom: 1px solid var(--border) !important;
    text-transform: none !important;
}

#leftArea .prospectus-root .prospectus-status h4 {
    margin: 1px 0 5px !important;
    padding: 0 !important;
    border: 0 !important;
    text-transform: none !important;
}

#leftArea .prospectus-root .prospectus-status p {
    margin: 0 !important;
    padding: 0 !important;
}

#leftArea .prospectus-root .prospectus-status p.prospectus-modified {
    margin-top: 8px !important;
}

#leftArea .prospectus-root label.prospectus-label {
    display: block !important;
    margin: 0 0 9px !important;
    padding: 0 !important;
    float: none !important;
    width: auto !important;
}

#leftArea .prospectus-root label.prospectus-dropzone {
    display: flex !important;
    margin: 0 !important;
    float: none !important;
    width: 100% !important;
}

#leftArea .prospectus-root .prospectus-btn {
    height: auto;
    line-height: 1.2 !important;
    font-family: inherit !important;
    text-transform: none !important;
    letter-spacing: normal !important;
    box-shadow: none !important;
}

#leftArea .prospectus-root a.prospectus-btn-secondary,
#leftArea .prospectus-root button.prospectus-btn-secondary {
    background: #fff !important;
    color: var(--text) !important;
    border: 1px solid #d5ded8 !important;
}

#leftArea .prospectus-root a.prospectus-btn-secondary:hover,
#leftArea .prospectus-root button.prospectus-btn-secondary:hover {
    background: #f5faf7 !important;
    color: var(--green) !important;
}

#leftArea .prospectus-root button.prospectus-btn-primary {
    background: var(--green) !important;
    color: #fff !important;
    border: 1px solid var(--green) !important;
}

#leftArea .prospectus-root button.prospectus-btn-primary:hover {
    background: var(--green-dark) !important;
}

#leftArea .prospectus-root .prospectus-btn-sm {
    min-height: 34px;
    padding: 0 13px;
    font-size: 12px;
}

.prospectus-view-row {
    margin-top: 12px;
}

@media (max-width: 560px) {
    .prospectus-status {
        flex-direction: column;
        gap: 12px;
    }

    .prospectus-header-icon {
        width: 42px;
        height: 42px;
        font-size: 18px;
    }

    .prospectus-view-row .prospectus-btn {
        width: 100%;
    }
}

@media (prefers-reduced-motion: reduce) {
    .prospectus-root * {
        transition: none !important;
    }
}
</style>

<div class="prospectus-root">

    <div class="prospectus-header">
        <div class="prospectus-header-icon">
            <i class="fas fa-file-pdf"></i>
        </div>

        <div>
            <h2>Prospectus PDF Upload</h2>
            <p>Upload and replace the Prospectus PDF displayed on the public website.</p>
        </div>
    </div>

    <?php if ($message !== '') { ?>
        <div
            class="prospectus-message <?php echo prospectusEscape($messageType); ?>"
            role="<?php echo $messageType === 'error' ? 'alert' : 'status'; ?>"
        >
            <i class="fas <?php echo $messageType === 'error'
                ? 'fa-circle-exclamation'
                : 'fa-circle-check'; ?>"></i>

            <div><?php echo prospectusEscape($message); ?></div>
        </div>
    <?php } ?>

    <div class="prospectus-card">

        <h3>
            <i class="fas fa-file-circle-check"></i>
            Current Prospectus PDF
        </h3>

        <div class="prospectus-status">

            <div class="prospectus-status-icon">
                <i class="fas fa-file-pdf"></i>
            </div>

            <div class="prospectus-status-body">
                <h4>PROSPECTUS.pdf</h4>

                <p>
                    S3 object key:
                    <?php echo prospectusEscape(PROSPECTUS_S3_KEY); ?>
                </p>

                <?php if ($fileExists) { ?>

                    <span class="prospectus-badge active">
                        <i class="fas fa-check-circle"></i>
                        PDF exists in S3
                    </span>

                    <?php if ($lastModified) { ?>
                        <p class="prospectus-modified">
                            Last modified:
                            <?php echo prospectusEscape($lastModified); ?>
                        </p>
                    <?php } ?>

                    <div class="prospectus-view-row">
                        <a
                            href="<?php
                            echo prospectusEscape(
                                $s3Client->getObjectUrl(
                                    AWS_BUCKET,
                                    PROSPECTUS_S3_KEY
                                )
                            );
                            ?>"
                            target="_blank"
                            rel="noopener"
                            class="prospectus-btn prospectus-btn-secondary prospectus-btn-sm"
                        >
                            <i class="fas fa-arrow-up-right-from-square"></i>
                            View current PDF
                        </a>
                    </div>

                <?php } else { ?>

                    <span class="prospectus-badge missing">
                        <i class="fas fa-exclamation-circle"></i>
                        PDF not found in S3
                    </span>

                <?php } ?>

            </div>
        </div>

        <form
            method="POST"
            action="<?php echo prospectusEscape($_SERVER['SCRIPT_NAME']); ?>"
            enctype="multipart/form-data"
            id="prospectus-upload-form"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo prospectusEscape($csrfToken); ?>"
            >

            <label class="prospectus-label" for="prospectus_pdf">
                Select Prospectus PDF
            </label>

            <input
                type="file"
                name="prospectus_pdf"
                id="prospectus_pdf"
                class="prospectus-file-input"
                accept=".pdf,application/pdf"
                required
            >

            <label
                for="prospectus_pdf"
                class="prospectus-dropzone"
                id="prospectus-dropzone"
            >
                <span class="prospectus-upload-icon">
                    <i class="fas fa-file-arrow-up"></i>
                </span>

                <span class="prospectus-file-text">
                    <span
                        class="prospectus-file-name"
                        id="prospectus-file-name"
                    >
                        Click to select a PDF or drag it here
                    </span>

                    <span class="prospectus-help">
                        PDF only · Maximum file size: 10 MB
                    </span>
                </span>
            </label>

            <div class="prospectus-warning">
                <i class="fas fa-triangle-exclamation"></i>

                <span>
                    Uploading a new PDF replaces the existing
                    PROSPECTUS.pdf in S3. Use this module only
                    for the Prospectus document.
                </span>
            </div>

            <div class="prospectus-actions">

                <button
                    type="submit"
                    class="prospectus-btn prospectus-btn-primary"
                    id="prospectus-submit"
                >
                    <i class="fas fa-cloud-arrow-up"></i>
                    Upload Prospectus PDF
                </button>

                <button
                    type="reset"
                    class="prospectus-btn prospectus-btn-secondary"
                    id="prospectus-reset"
                >
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

    var input = document.getElementById('prospectus_pdf');
    var zone = document.getElementById('prospectus-dropzone');
    var title = document.getElementById('prospectus-file-name');
    var form = document.getElementById('prospectus-upload-form');
    var submit = document.getElementById('prospectus-submit');
    var reset = document.getElementById('prospectus-reset');

    if (!input || !zone || !form) {
        return;
    }

    var defaultText = 'Click to select a PDF or drag it here';

    function formatSize(bytes) {
        return bytes >= 1024 * 1024
            ? (bytes / (1024 * 1024)).toFixed(2) + ' MB'
            : (bytes / 1024).toFixed(1) + ' KB';
    }

    function refresh() {
        if (input.files && input.files.length) {
            var file = input.files[0];

            title.textContent =
                file.name + ' (' + formatSize(file.size) + ')';
        } else {
            title.textContent = defaultText;
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
            alert('Please select a PDF file.');
            return;
        }

        var file = input.files[0];

        if (!/\.pdf$/i.test(file.name)) {
            event.preventDefault();
            alert('Only PDF files are allowed.');
            return;
        }

        if (file.size <= 0 || file.size > 10 * 1024 * 1024) {
            event.preventDefault();
            alert('PDF size must be between 1 byte and 10 MB.');
            return;
        }

        if (submit) {
            submit.disabled = true;
            submit.innerHTML =
                '<i class="fas fa-spinner fa-spin"></i> Uploading...';
        }
    });

    if (reset) {
        reset.addEventListener('click', function () {
            setTimeout(function () {
                refresh();

                if (submit) {
                    submit.disabled = false;
                    submit.innerHTML =
                        '<i class="fas fa-cloud-arrow-up"></i> Upload Prospectus PDF';
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