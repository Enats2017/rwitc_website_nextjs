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

/*
|--------------------------------------------------------------------------
| CONFIGURATION
|--------------------------------------------------------------------------
*/

define('CHART_S3_KEY', 'uploads/ApplicationForms/CHART.pdf');
define('CHART_MAX_SIZE', 10 * 1024 * 1024);

/*
|--------------------------------------------------------------------------
| ADMIN AUTHENTICATION AND PERMISSION
|--------------------------------------------------------------------------
*/

if (!isAdminlogin()) {
    http_response_code(403);
    exit('Please log in to access this page.');
}

if (!function_exists('hasModuleAccess') ||
    !hasModuleAccess('chart_pdf_upload')) {
    http_response_code(403);
    exit('You do not have permission to access this page.');
}

/*
|--------------------------------------------------------------------------
| AWS S3 CLIENT
|--------------------------------------------------------------------------
*/

$s3Client = new S3Client(array(
    'version' => 'latest',
    'region' => AWS_REGION,
    'credentials' => array(
        'key' => AWS_ACCESS_KEY_ID,
        'secret' => AWS_SECRET_ACCESS_KEY
    )
));

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function chartEscape($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function chartObjectExists($s3Client)
{
    try {
        $s3Client->headObject(array(
            'Bucket' => AWS_BUCKET,
            'Key' => CHART_S3_KEY
        ));

        return true;
    } catch (AwsException $e) {
        $statusCode = $e->getStatusCode();
        $errorCode = $e->getAwsErrorCode();

        if (
            $statusCode === 404 ||
            $errorCode === 'NoSuchKey' ||
            $errorCode === 'NotFound'
        ) {
            return false;
        }

        error_log('Chart PDF status check failed: ' . $e->getMessage());

        throw new RuntimeException(
            'Unable to check the current Chart PDF. Please try again.'
        );
    }
}

/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['chart_pdf_csrf'])) {
    $_SESSION['chart_pdf_csrf'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['chart_pdf_csrf'];

$message = '';
$messageType = 'success';
$chartExists = false;
$chartLastModified = null;

/*
|--------------------------------------------------------------------------
| UPLOAD CHART PDF
|--------------------------------------------------------------------------
*/

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
            !isset($_FILES['chart_pdf']) ||
            !is_array($_FILES['chart_pdf'])
        ) {
            throw new RuntimeException('Please select a PDF file.');
        }

        $file = $_FILES['chart_pdf'];

        if ($file['error'] !== UPLOAD_ERR_OK) {

            $uploadErrors = array(
                UPLOAD_ERR_INI_SIZE =>
                    'The file exceeds the server upload limit.',
                UPLOAD_ERR_FORM_SIZE =>
                    'The file exceeds the allowed upload limit.',
                UPLOAD_ERR_PARTIAL =>
                    'The file was only partially uploaded.',
                UPLOAD_ERR_NO_FILE =>
                    'Please select a PDF file.',
                UPLOAD_ERR_NO_TMP_DIR =>
                    'The server temporary directory is missing.',
                UPLOAD_ERR_CANT_WRITE =>
                    'The server could not save the uploaded file.',
                UPLOAD_ERR_EXTENSION =>
                    'The server rejected the uploaded file.'
            );

            throw new RuntimeException(
                isset($uploadErrors[$file['error']])
                    ? $uploadErrors[$file['error']]
                    : 'The upload failed.'
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
            $file['size'] > CHART_MAX_SIZE
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
        $mimeType = $finfo->file($file['tmp_name']);

        if ($mimeType !== 'application/pdf') {
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
         * Upload to the same S3 key every time.
         * S3 replaces the existing object at this key.
         */
        $s3Client->putObject(array(
            'Bucket' => AWS_BUCKET,
            'Key' => CHART_S3_KEY,
            'SourceFile' => $file['tmp_name'],
            'ContentType' => 'application/pdf',
            'ContentDisposition' => 'inline; filename="CHART.pdf"',
            'CacheControl' => 'no-cache, must-revalidate'
        ));

        $_SESSION['chart_pdf_flash'] = array(
            'type' => 'success',
            'message' => 'Chart PDF uploaded successfully. The existing PDF has been replaced.'
        );

        header('Location: ' . $_SERVER['SCRIPT_NAME']);
        exit;

    } catch (Throwable $e) {

        error_log('Chart PDF upload error: ' . $e->getMessage());

        if ($e instanceof RuntimeException) {
            $message = $e->getMessage();
        } elseif ($e instanceof AwsException) {
            $message = 'S3 upload failed. Check AWS configuration and permissions.';
        } else {
            $message = 'Upload failed. Please check the server logs.';
        }

        $messageType = 'error';
    }
}

/*
|--------------------------------------------------------------------------
| FLASH MESSAGE
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['chart_pdf_flash'])) {

    $message = $_SESSION['chart_pdf_flash']['message'];
    $messageType = $_SESSION['chart_pdf_flash']['type'];

    unset($_SESSION['chart_pdf_flash']);
}

/*
|--------------------------------------------------------------------------
| CURRENT S3 OBJECT STATUS
|--------------------------------------------------------------------------
*/

try {

    $chartExists = chartObjectExists($s3Client);

    if ($chartExists) {

        $head = $s3Client->headObject(array(
            'Bucket' => AWS_BUCKET,
            'Key' => CHART_S3_KEY
        ));

        $chartLastModified = isset($head['LastModified'])
            ? $head['LastModified']->format('d M Y, h:i A')
            : null;
    }

} catch (Throwable $e) {

    error_log('Chart PDF status error: ' . $e->getMessage());

    if ($message === '') {
        $message = 'Unable to verify the current S3 file status.';
        $messageType = 'error';
    }
}

/*
|--------------------------------------------------------------------------
| PAGE LAYOUT
|--------------------------------------------------------------------------
*/

$design = new Design();

$design->js = '
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
';

$design->css = '';
$design->jqueryJs = '';

$design->startPage('Chart PDF Upload');
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
    --green-light: #e8f5ed;
    --text: #1f2f26;
    --muted: #6f7f75;
    --border: #e1e8e3;

    position: relative;
    color: var(--text);
    font-family: inherit;
}

.chart-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 24px;
}

.chart-header-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 50px;
    height: 50px;
    border-radius: 12px;
    background: var(--green-light);
    color: var(--green);
    font-size: 22px;
}

.chart-header h2 {
    margin: 0;
    font-size: 25px;
    font-weight: 700;
    color: var(--text);
}

.chart-header p {
    margin: 5px 0 0;
    font-size: 14px;
    color: var(--muted);
}

.chart-card {
    padding: 25px;
    margin-bottom: 22px;
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 14px;
    box-shadow: 0 2px 8px rgba(20, 40, 28, .04);
}

.chart-card h3 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 18px;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--border);
    font-size: 16px;
    font-weight: 700;
    color: var(--green);
}

.chart-status {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 17px;
    border: 1px solid var(--border);
    border-radius: 10px;
    background: #fafcfb;
    margin-bottom: 22px;
}

.chart-status-body {
    min-width: 0;
    flex: 1 1 auto;
}

.chart-status-icon {
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

.chart-status h4 {
    margin: 1px 0 5px;
    font-size: 14px;
    font-weight: 700;
}

.chart-status p {
    margin: 0;
    color: var(--muted);
    font-size: 12px;
    line-height: 1.6;
    overflow-wrap: anywhere;
}

.chart-badge {
    display: inline-block;
    margin-top: 8px;
    padding: 5px 9px;
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

.chart-label {
    display: block;
    margin-bottom: 9px;
    font-size: 13px;
    font-weight: 700;
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
    gap: 14px;
    padding: 20px;
    border: 2px dashed #c9d6ce;
    border-radius: 11px;
    background: #f5faf7;
    cursor: pointer;
    transition: .2s ease;
}

.chart-dropzone:hover,
.chart-dropzone.is-drag {
    border-color: var(--green);
    background: #eaf4ee;
}

.chart-file-input:focus-visible + .chart-dropzone {
    outline: 3px solid #b7ddc5;
}

.chart-upload-icon {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: white;
    border-radius: 50%;
    color: var(--green);
    font-size: 19px;
}

.chart-file-text {
    min-width: 0;
}

.chart-file-name {
    display: block;
    font-size: 14px;
    font-weight: 700;
    overflow-wrap: anywhere;
}

.chart-help {
    display: block;
    margin-top: 5px;
    color: var(--muted);
    font-size: 12px;
    line-height: 1.6;
}

.chart-warning {
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

.chart-message {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 20px;
    padding: 14px 16px;
    border-radius: 10px;
    font-size: 13px;
    line-height: 1.5;
}

.chart-message.success {
    background: #e8f5ed;
    border: 1px solid #b7ddc5;
    color: #0f5c33;
}

.chart-message.error {
    background: #fff1f0;
    border: 1px solid #f0c4c4;
    color: #a32121;
}

.chart-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 20px;
}

.chart-btn {
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
    background: #f5faf7;
    color: var(--green);
}

.chart-btn:disabled {
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

    .chart-header h2 {
        font-size: 21px;
    }

    .chart-card {
        padding: 17px;
    }

    .chart-dropzone {
        padding: 14px;
    }

    .chart-actions .chart-btn {
        width: 100%;
    }
}

/* ======================================================================
   Hardening against global site CSS + finishing touches
   ====================================================================== */
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
    margin: 0 0 18px !important;
    padding: 0 0 15px !important;
    border: 0 !important;
    border-bottom: 1px solid var(--border) !important;
    text-transform: none !important;
}

#leftArea .chart-root .chart-status h4 {
    margin: 1px 0 5px !important;
    padding: 0 !important;
    border: 0 !important;
    text-transform: none !important;
}

#leftArea .chart-root .chart-status p {
    margin: 0 !important;
    padding: 0 !important;
}

#leftArea .chart-root .chart-status p.chart-modified {
    margin-top: 8px !important;
}

#leftArea .chart-root label.chart-label {
    display: block !important;
    margin: 0 0 9px !important;
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
    background: #f5faf7 !important;
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
    min-height: 34px;
    padding: 0 13px;
    font-size: 12px;
}

.chart-view-row {
    margin-top: 12px;
}

@media (max-width: 560px) {
    .chart-status {
        flex-direction: column;
        gap: 12px;
    }

    .chart-header-icon {
        width: 42px;
        height: 42px;
        font-size: 18px;
    }

    .chart-warning {
        flex-direction: row;
    }

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
            <h2>Chart PDF Upload</h2>
            <p>Upload and replace the Chart PDF displayed on the public website.</p>
        </div>
    </div>

    <?php if ($message !== '') { ?>
        <div
            class="chart-message <?php echo chartEscape($messageType); ?>"
            role="<?php echo $messageType === 'error' ? 'alert' : 'status'; ?>"
        >
            <i class="fas <?php echo $messageType === 'error'
                ? 'fa-circle-exclamation'
                : 'fa-circle-check'; ?>"></i>

            <div><?php echo chartEscape($message); ?></div>
        </div>
    <?php } ?>

    <div class="chart-card">

        <h3>
            <i class="fas fa-file-circle-check"></i>
            Current Chart PDF
        </h3>

        <div class="chart-status">

            <div class="chart-status-icon">
                <i class="fas fa-file-pdf"></i>
            </div>

            <div class="chart-status-body">
                <h4>CHART.pdf</h4>

                <p>
                    S3 object key:
                    <?php echo chartEscape(CHART_S3_KEY); ?>
                </p>

                <?php if ($chartExists) { ?>

                    <span class="chart-badge active">
                        <i class="fas fa-check-circle"></i>
                        PDF exists in S3
                    </span>

                    <?php if ($chartLastModified) { ?>
                        <p class="chart-modified">
                            Last modified:
                            <?php echo chartEscape($chartLastModified); ?>
                        </p>
                    <?php } ?>

                    <div class="chart-view-row">
                        <a
                            href="<?php
                            echo chartEscape(
                                $s3Client->getObjectUrl(
                                    AWS_BUCKET,
                                    CHART_S3_KEY
                                )
                            );
                            ?>"
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
                        PDF not found in S3
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

            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo chartEscape($csrfToken); ?>"
            >

            <label class="chart-label" for="chart_pdf">
                Select Chart PDF
            </label>

            <input
                type="file"
                name="chart_pdf"
                id="chart_pdf"
                class="chart-file-input"
                accept=".pdf,application/pdf"
                required
            >

            <label
                for="chart_pdf"
                class="chart-dropzone"
                id="chart-dropzone"
            >
                <span class="chart-upload-icon">
                    <i class="fas fa-file-arrow-up"></i>
                </span>

                <span class="chart-file-text">
                    <span
                        class="chart-file-name"
                        id="chart-file-name"
                    >
                        Click to select a PDF or drag it here
                    </span>

                    <span class="chart-help">
                        PDF only · Maximum file size: 10 MB
                    </span>
                </span>
            </label>

            <div class="chart-warning">
                <i class="fas fa-triangle-exclamation"></i>

                <span>
                    Uploading a new PDF replaces the existing
                    CHART.pdf in S3. Use this module only for the
                    Chart document.
                </span>
            </div>

            <div class="chart-actions">

                <button
                    type="submit"
                    class="chart-btn chart-btn-primary"
                    id="chart-submit"
                >
                    <i class="fas fa-cloud-arrow-up"></i>
                    Upload Chart PDF
                </button>

                <button
                    type="reset"
                    class="chart-btn chart-btn-secondary"
                    id="chart-reset"
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

    var input = document.getElementById('chart_pdf');
    var dropzone = document.getElementById('chart-dropzone');
    var fileName = document.getElementById('chart-file-name');
    var form = document.getElementById('chart-upload-form');
    var submit = document.getElementById('chart-submit');
    var reset = document.getElementById('chart-reset');

    if (!input || !dropzone || !form) {
        return;
    }

    var defaultText = 'Click to select a PDF or drag it here';

    function formatSize(bytes) {
        return bytes >= 1024 * 1024
            ? (bytes / (1024 * 1024)).toFixed(2) + ' MB'
            : (bytes / 1024).toFixed(1) + ' KB';
    }

    function refreshFileName() {
        if (input.files && input.files.length) {
            var file = input.files[0];

            fileName.textContent =
                file.name + ' (' + formatSize(file.size) + ')';
        } else {
            fileName.textContent = defaultText;
        }
    }

    input.addEventListener('change', refreshFileName);

    ['dragenter', 'dragover'].forEach(function (eventName) {
        dropzone.addEventListener(eventName, function (event) {
            event.preventDefault();
            dropzone.classList.add('is-drag');
        });
    });

    ['dragleave', 'drop'].forEach(function (eventName) {
        dropzone.addEventListener(eventName, function (event) {
            event.preventDefault();
            dropzone.classList.remove('is-drag');
        });
    });

    dropzone.addEventListener('drop', function (event) {
        if (event.dataTransfer && event.dataTransfer.files.length) {
            input.files = event.dataTransfer.files;
            refreshFileName();
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
                refreshFileName();

                if (submit) {
                    submit.disabled = false;
                    submit.innerHTML =
                        '<i class="fas fa-cloud-arrow-up"></i> Upload Chart PDF';
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