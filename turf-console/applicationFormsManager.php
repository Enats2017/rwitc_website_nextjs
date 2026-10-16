<?php

ini_set('display_errors', 0);
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

define('APPLICATION_FORMS_TABLE', 'application_forms');
define('APPLICATION_FORMS_S3_PREFIX', 'uploads/ApplicationForms/');
define('APPLICATION_FORMS_MAX_SIZE', 10 * 1024 * 1024);

/*
 * Update this URL to the actual path of your S3 object proxy script.
 * The proxy should accept ?key=<encoded S3 object key>.
 */
define(
    'APPLICATION_FORMS_PROXY_URL',
    '../rwitc_website_api/getS3Object.php'
);

/*
|--------------------------------------------------------------------------
| ADMIN AUTHENTICATION
|--------------------------------------------------------------------------
*/

if (!isAdminlogin()) {
    http_response_code(403);
    exit('Please log in to access this page.');
}

$hasPermission = function_exists('hasModuleAccess')
    ? hasModuleAccess('application_forms')
    : (
        isset($_SESSION['applicationFormsManager']) &&
        $_SESSION['applicationFormsManager'] === 'Y'
    );

if (!$hasPermission) {
    http_response_code(403);
    exit('You do not have permission to access this page.');
}

/*
|--------------------------------------------------------------------------
| DATABASE AND AWS
|--------------------------------------------------------------------------
*/

$userObj = new Users($db);
$rObj = new Racedata($db);

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

function afEscape($value)
{
    global $db;

    return getParameterString_custom((string)$value, '', $db);
}

function afH($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function afRedirect()
{
    header('Location: applicationFormsManager.php');
    exit;
}

function afFlash($type, $message)
{
    $_SESSION['application_forms_flash'] = array(
        'type' => $type,
        'message' => $message
    );
}

function afValidateTitle($title)
{
    $title = trim($title);

    if ($title === '') {
        throw new RuntimeException('Form title is required.');
    }

    if (strlen($title) > 255) {
        throw new RuntimeException(
            'Form title must not exceed 255 characters.'
        );
    }

    return $title;
}

/*
 * Fixed list of form titles. These must match the titles used on the
 * public website (ApplicationForm.jsx). Admin picks one from the dropdown;
 * uploading for a title that already has a file REPLACES that file.
 */
function afTitleOptions()
{
    return array(
        'Application For License - Jockeys',
        'Application For License - Trainers',
        'Trainee Apprentice School Programme',
        'Application for License - Apprentice Trainers',
        'Authorised Agent',
        'Authority to Trainer',
        'Badge Requisition Form',
        'Classification',
        'Club Membership',
        'Declaration Form',
        'Entry Form',
        'Equipment Form',
        'Foreign Jockeys Declaration',
        'Foreign Jockey Application',
        'ID-Card Form',
        'Jockey Retainer',
        'Joint Ownership',
        'Lease Form (Older Horses)',
        'Lease Form (2 Yr Old Horses Only)',
        'Left Charge - Leaving Centre',
        'Proposal for Life Membership - Members Children',
        'Limited Company',
        'Owners Application',
        'Ownership Application For Dependents - Undertaking',
        'Ownership for Director of a Limited Company',
        'Ownership by Limited Liability Partnership (LLP)',
        'Ownership by Partnership Firm',
        'Racing Syndicate',
        'Registration Of Name',
        'Registration Of Racing Colours',
        'Sale With Contingency',
        'Sale Transfer',
        'Scratching - White Form',
        'Scratching - Pink Form',
        'Scratching - Yellow Form',
        'Take Charge Form',
        'Notification of Birth',
        'Certificate of Identity',
        'Pink Form',
        'Sale Form - SBAI'
    );
}

function afValidateTitleChoice($title, $currentTitle = null)
{
    if (in_array($title, afTitleOptions(), true)) {
        return $title;
    }

    if ($currentTitle !== null && $title === (string)$currentTitle) {
        return $title;
    }

    throw new RuntimeException(
        'Please select a valid form title from the list.'
    );
}

function afFindByTitle($title, $excludeId = 0)
{
    global $db;

    $sql = "SELECT * FROM `" . APPLICATION_FORMS_TABLE . "`
            WHERE title = '" . afEscape($title) . "'";

    if ((int)$excludeId > 0) {
        $sql .= " AND id <> " . (int)$excludeId;
    }

    $sql .= " ORDER BY id ASC LIMIT 1";

    $rows = $db->getMultiDimensionalArray($sql);

    return (is_array($rows) && isset($rows[0])) ? $rows[0] : null;
}

function afValidateSortOrder($value)
{
    $order = filter_var($value, FILTER_VALIDATE_INT);

    if ($order === false || $order < 0) {
        throw new RuntimeException(
            'Display order must be a positive integer or zero.'
        );
    }

    return $order;
}

function afValidateUpload($file)
{
    if (!is_array($file) || !isset($file['error'])) {
        throw new RuntimeException('Please select a document.');
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = array(
            UPLOAD_ERR_INI_SIZE =>
                'File exceeds the server upload limit.',
            UPLOAD_ERR_FORM_SIZE =>
                'File exceeds the allowed upload limit.',
            UPLOAD_ERR_PARTIAL =>
                'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE =>
                'Please select a document.',
            UPLOAD_ERR_NO_TMP_DIR =>
                'Server temporary directory is missing.',
            UPLOAD_ERR_CANT_WRITE =>
                'Server could not write the uploaded file.',
            UPLOAD_ERR_EXTENSION =>
                'Server rejected the uploaded file.'
        );

        throw new RuntimeException(
            isset($errors[$file['error']])
                ? $errors[$file['error']]
                : 'File upload failed.'
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
        $file['size'] > APPLICATION_FORMS_MAX_SIZE
    ) {
        throw new RuntimeException(
            'File size must be between 1 byte and 10 MB.'
        );
    }

    $extension = strtolower(
        pathinfo(basename($file['name']), PATHINFO_EXTENSION)
    );

    $allowed = array(
        'pdf' => array('application/pdf'),
        'doc' => array('application/msword'),
        'docx' => array(
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip'
        )
    );

    if (!isset($allowed[$extension])) {
        throw new RuntimeException(
            'Only PDF, DOC and DOCX files are allowed.'
        );
    }

    if (!class_exists('finfo')) {
        throw new RuntimeException(
            'File type validation is unavailable on the server.'
        );
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (
        $mime === false ||
        !in_array($mime, $allowed[$extension], true)
    ) {
        throw new RuntimeException(
            'File content does not match its extension.'
        );
    }

    if ($extension === 'pdf') {
        $handle = fopen($file['tmp_name'], 'rb');

        if ($handle === false) {
            throw new RuntimeException('Unable to read PDF.');
        }

        $signature = fread($handle, 5);
        fclose($handle);

        if ($signature !== '%PDF-') {
            throw new RuntimeException('Invalid PDF document.');
        }
    }

    if ($extension === 'docx') {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException(
                'DOCX validation is unavailable on the server.'
            );
        }

        $zip = new ZipArchive();
        $opened = $zip->open($file['tmp_name']);

        if ($opened !== true) {
            throw new RuntimeException('Invalid DOCX document.');
        }

        $valid = (
            $zip->locateName('[Content_Types].xml') !== false &&
            $zip->locateName('word/document.xml') !== false
        );

        $zip->close();

        if (!$valid) {
            throw new RuntimeException('Invalid DOCX document.');
        }
    }

    return array(
        'extension' => $extension,
        'mime' => $mime,
        'size' => (int)$file['size'],
        'original_name' => basename($file['name'])
    );
}

function afUploadToS3($file)
{
    global $s3Client;

    $info = afValidateUpload($file);

    $key = APPLICATION_FORMS_S3_PREFIX
        . bin2hex(random_bytes(16))
        . '.'
        . $info['extension'];

    $contentTypes = array(
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' =>
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    );

    $s3Client->putObject(array(
        'Bucket' => AWS_BUCKET,
        'Key' => $key,
        'SourceFile' => $file['tmp_name'],
        'ContentType' => $contentTypes[$info['extension']],
        'ContentDisposition' => 'attachment; filename="'
            . str_replace(
                array('"', "\r", "\n"),
                '',
                $info['original_name']
            )
            . '"',
        'Metadata' => array(
            'original-filename' => rawurlencode(
                $info['original_name']
            )
        )
    ));

    $info['key'] = $key;

    return $info;
}

function afDeleteS3Object($key)
{
    global $s3Client;

    if (
        !is_string($key) ||
        strpos($key, APPLICATION_FORMS_S3_PREFIX) !== 0
    ) {
        throw new RuntimeException('Invalid S3 object key.');
    }

    $s3Client->deleteObject(array(
        'Bucket' => AWS_BUCKET,
        'Key' => $key
    ));
}

/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['application_forms_csrf'])) {
    $_SESSION['application_forms_csrf'] = bin2hex(
        random_bytes(32)
    );
}

$csrfToken = $_SESSION['application_forms_csrf'];

$message = '';
$messageType = 'success';

if (isset($_SESSION['application_forms_flash'])) {
    $message = $_SESSION['application_forms_flash']['message'];
    $messageType = $_SESSION['application_forms_flash']['type'];

    unset($_SESSION['application_forms_flash']);
}

/*
|--------------------------------------------------------------------------
| PROCESS ADMIN ACTIONS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = isset($_POST['csrf_token'])
        ? (string)$_POST['csrf_token']
        : '';

    if (!hash_equals($csrfToken, $postedToken)) {
        http_response_code(403);
        exit('Invalid security token. Reload the page and try again.');
    }

    $action = isset($_POST['action'])
        ? (string)$_POST['action']
        : '';

    try {
        /*
        |--------------------------------------------------------------------------
        | ADD APPLICATION FORM
        |--------------------------------------------------------------------------
        */

        if ($action === 'add') {
            $title = afValidateTitleChoice(
                afValidateTitle($_POST['title'] ?? '')
            );

            $sortOrder = afValidateSortOrder(
                $_POST['sort_order'] ?? '0'
            );

            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if (!isset($_FILES['form_file'])) {
                throw new RuntimeException(
                    'Please select a document.'
                );
            }

            $uploaded = afUploadToS3($_FILES['form_file']);

            $titleSql = afEscape($title);
            $keySql = afEscape($uploaded['key']);
            $originalSql = afEscape($uploaded['original_name']);
            $typeSql = afEscape($uploaded['extension']);
            $size = (int)$uploaded['size'];
            $uid = isset($_SESSION['uid'])
                ? (int)$_SESSION['uid']
                : 0;

            /*
             * One file per title: if a form with this title already
             * exists, replace its file and details instead of adding
             * a new row.
             */
            $existingByTitle = afFindByTitle($title);

            if ($existingByTitle) {

                $sql = "
                    UPDATE `" . APPLICATION_FORMS_TABLE . "`
                    SET
                        source = '" . $keySql . "',
                        original_file_name = '" . $originalSql . "',
                        file_type = '" . $typeSql . "',
                        file_size = " . $size . ",
                        sort_order = " . (int)$sortOrder . ",
                        is_active = " . $isActive . "
                    WHERE id = " . (int)$existingByTitle['id'] . "
                ";

                try {
                    $rObj->insertBannerimage($sql);
                } catch (Throwable $e) {
                    try {
                        afDeleteS3Object($uploaded['key']);
                    } catch (Throwable $cleanupError) {
                        error_log(
                            'Application form S3 cleanup failed.'
                        );
                    }

                    throw $e;
                }

                if (!empty($existingByTitle['source'])) {
                    try {
                        afDeleteS3Object($existingByTitle['source']);
                    } catch (Throwable $cleanupError) {
                        error_log(
                            'Old application form S3 object cleanup failed.'
                        );
                    }
                }

                afFlash(
                    'success',
                    'Existing application form replaced successfully.'
                );

            } else {

                $sql = "
                    INSERT INTO `" . APPLICATION_FORMS_TABLE . "`
                    (
                        title,
                        source,
                        original_file_name,
                        file_type,
                        file_size,
                        sort_order,
                        is_active,
                        created_by
                    )
                    VALUES
                    (
                        '" . $titleSql . "',
                        '" . $keySql . "',
                        '" . $originalSql . "',
                        '" . $typeSql . "',
                        " . $size . ",
                        " . (int)$sortOrder . ",
                        " . $isActive . ",
                        " . $uid . "
                    )
                ";

                try {
                    /*
                     * Uses the existing RWITC database execution method
                     * demonstrated in bannerManager.php.
                     */
                    $rObj->insertBannerimage($sql);
                } catch (Throwable $e) {
                    try {
                        afDeleteS3Object($uploaded['key']);
                    } catch (Throwable $cleanupError) {
                        error_log(
                            'Application form S3 cleanup failed.'
                        );
                    }

                    throw $e;
                }

                afFlash(
                    'success',
                    'Application form uploaded successfully.'
                );
            }

            afRedirect();
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE APPLICATION FORM
        |--------------------------------------------------------------------------
        */

        if ($action === 'update') {
            $id = filter_var(
                $_POST['id'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$id || $id < 1) {
                throw new RuntimeException('Invalid form ID.');
            }

            $existingRows = $db->getMultiDimensionalArray(
                "SELECT * FROM `" . APPLICATION_FORMS_TABLE . "`
                 WHERE id = " . (int)$id . " LIMIT 1"
            );

            $existing = (
                is_array($existingRows) &&
                isset($existingRows[0])
            ) ? $existingRows[0] : null;

            if (!$existing) {
                throw new RuntimeException(
                    'Application form not found.'
                );
            }

            $title = afValidateTitleChoice(
                afValidateTitle($_POST['title'] ?? ''),
                $existing['title']
            );

            if (afFindByTitle($title, (int)$id)) {
                throw new RuntimeException(
                    'A form with this title already exists. Edit that one instead.'
                );
            }

            $sortOrder = afValidateSortOrder(
                $_POST['sort_order'] ?? '0'
            );

            $isActive = isset($_POST['is_active']) ? 1 : 0;

            $newUpload = null;

            if (
                isset($_FILES['form_file']) &&
                $_FILES['form_file']['error'] !== UPLOAD_ERR_NO_FILE
            ) {
                $newUpload = afUploadToS3($_FILES['form_file']);
            }

            $key = $newUpload
                ? $newUpload['key']
                : $existing['source'];

            $originalName = $newUpload
                ? $newUpload['original_name']
                : $existing['original_file_name'];

            $fileType = $newUpload
                ? $newUpload['extension']
                : $existing['file_type'];

            $fileSize = $newUpload
                ? (int)$newUpload['size']
                : (int)$existing['file_size'];

            $sql = "
                UPDATE `" . APPLICATION_FORMS_TABLE . "`
                SET
                    title = '" . afEscape($title) . "',
                    source = '" . afEscape($key) . "',
                    original_file_name = '" .
                        afEscape($originalName) . "',
                    file_type = '" . afEscape($fileType) . "',
                    file_size = " . $fileSize . ",
                    sort_order = " . (int)$sortOrder . ",
                    is_active = " . $isActive . "
                WHERE id = " . (int)$id . "
            ";

            try {
                $rObj->insertBannerimage($sql);
            } catch (Throwable $e) {
                if ($newUpload) {
                    try {
                        afDeleteS3Object($newUpload['key']);
                    } catch (Throwable $cleanupError) {
                        error_log(
                            'Application form S3 cleanup failed.'
                        );
                    }
                }

                throw $e;
            }

            if ($newUpload && !empty($existing['source'])) {
                try {
                    afDeleteS3Object($existing['source']);
                } catch (Throwable $cleanupError) {
                    error_log(
                        'Old application form S3 object cleanup failed.'
                    );
                }
            }

            afFlash(
                'success',
                'Application form updated successfully.'
            );

            afRedirect();
        }

        /*
        |--------------------------------------------------------------------------
        | DELETE APPLICATION FORM
        |--------------------------------------------------------------------------
        */

        if ($action === 'delete') {
            $id = filter_var(
                $_POST['id'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$id || $id < 1) {
                throw new RuntimeException('Invalid form ID.');
            }

            $rows = $db->getMultiDimensionalArray(
                "SELECT * FROM `" . APPLICATION_FORMS_TABLE . "`
                 WHERE id = " . (int)$id . " LIMIT 1"
            );

            $existing = (
                is_array($rows) &&
                isset($rows[0])
            ) ? $rows[0] : null;

            if (!$existing) {
                throw new RuntimeException(
                    'Application form not found.'
                );
            }

            /*
             * Delete the S3 object first so a failed S3 operation
             * does not remove the database record.
             */
            if (!empty($existing['source'])) {
                afDeleteS3Object($existing['source']);
            }

            $sql = "
                DELETE FROM `" . APPLICATION_FORMS_TABLE . "`
                WHERE id = " . (int)$id . "
            ";

            $rObj->insertBannerimage($sql);

            afFlash(
                'success',
                'Application form deleted successfully.'
            );

            afRedirect();
        }

        throw new RuntimeException('Invalid action.');

    } catch (Throwable $e) {
        error_log(
            'Application Forms Manager error: ' . $e->getMessage()
        );

        if ($e instanceof RuntimeException) {
            $message = $e->getMessage();
        } elseif ($e instanceof AwsException) {
            $message = 'S3 operation failed. Please check AWS configuration and permissions.';
        } else {
            $message = 'Operation failed. Please check the server logs.';
        }

        $messageType = 'error';
    }
}

/*
|--------------------------------------------------------------------------
| FETCH APPLICATION FORMS
|--------------------------------------------------------------------------
*/

$editForm = null;

if (isset($_GET['edit'])) {
    $editId = filter_var(
        $_GET['edit'],
        FILTER_VALIDATE_INT
    );

    if ($editId && $editId > 0) {
        $rows = $db->getMultiDimensionalArray(
            "SELECT * FROM `" . APPLICATION_FORMS_TABLE . "`
             WHERE id = " . (int)$editId . " LIMIT 1"
        );

        $editForm = (
            is_array($rows) &&
            isset($rows[0])
        ) ? $rows[0] : null;
    }
}

$forms = $db->getMultiDimensionalArray(
    "SELECT * FROM `" . APPLICATION_FORMS_TABLE . "`
     ORDER BY sort_order ASC, id DESC"
);

if (!is_array($forms)) {
    $forms = array();
}

/*
|--------------------------------------------------------------------------
| PAGE LAYOUT
|--------------------------------------------------------------------------
*/

/*
 * The page template may output a <base href>, which makes relative URLs
 * resolve against the site root. Use root-relative URLs for links/forms.
 */
$afSelf = $_SERVER['SCRIPT_NAME'];

$afTitleOptions = afTitleOptions();
$afUploadedTitles = array();

foreach ($forms as $afForm) {
    $afUploadedTitles[(string)$afForm['title']] = true;
}

$afCurrentTitle = $editForm ? (string)$editForm['title'] : '';

$design = new Design();

$design->js = '
<link rel="stylesheet"
 href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
';

$design->css = '';
$design->jqueryJs = '';

$design->startPage('Application Forms Manager');
$design->writeLogoTickerMenu();

$design->openDiv('contentWrapper');
$design->openDiv('infoWrapper', 'col-lg-12');
$design->openDiv('leftArea', 'col-lg-9');

?>

<style>
/* ---------- Layout (unchanged structure) ---------- */
#infoWrapper.col-lg-12 {
    display: flex;
    flex-direction: row-reverse;
    align-items: flex-start;
    max-width: 1500px;
    margin: 30px auto;
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

#leftArea .af-root,
#leftArea .af-root * {
    box-sizing: border-box;
}

.af-root {
    --af-green-900: #0b4627;
    --af-green-700: #0f5c33;
    --af-green-600: #187346;
    --af-green-100: #e4f2ea;
    --af-green-50: #f3f8f5;
    --af-ink: #1f2f26;
    --af-ink-2: #3a4a40;
    --af-muted: #6f7f75;
    --af-line: #e1e8e3;
    --af-bg: #ffffff;
    --af-danger: #b42318;
    --af-danger-bg: #fff1f0;
    --af-radius: 12px;
    color: var(--af-ink);
}

/* ---------- Header ---------- */
.af-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 24px;
}

.af-header-icon {
    flex: 0 0 auto;
    width: 46px;
    height: 46px;
    border-radius: 12px;
    background: var(--af-green-100);
    color: var(--af-green-700);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.af-header h2 {
    margin: 0;
    color: var(--af-ink);
    font-size: 24px;
    line-height: 1.25;
    font-weight: 700;
}

.af-header p {
    margin: 4px 0 0;
    color: var(--af-muted);
    font-size: 14px;
}

/* ---------- Cards ---------- */
.af-card {
    background: var(--af-bg);
    border: 1px solid var(--af-line);
    border-radius: var(--af-radius);
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(20, 40, 28, .05);
}

.af-card.is-editing {
    border-color: #b7ddc5;
    box-shadow: 0 0 0 3px rgba(24, 115, 70, .08);
}

.af-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin: 0 0 20px;
    padding-bottom: 14px;
    border-bottom: 1px solid var(--af-line);
}

.af-card-head h3 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 16px;
    color: var(--af-green-700);
    font-weight: 700;
}

.af-count {
    display: inline-block;
    min-width: 24px;
    padding: 2px 9px;
    border-radius: 20px;
    background: var(--af-green-100);
    color: var(--af-green-700);
    font-size: 12px;
    font-weight: 700;
    text-align: center;
}

.af-badge-edit {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    background: #fff6dd;
    color: #8a5a00;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .02em;
}

/* ---------- Form ---------- */
.af-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px;
}

.af-field {
    display: flex;
    flex-direction: column;
    gap: 7px;
    min-width: 0;
}

.af-full {
    grid-column: 1 / -1;
}

.af-label {
    color: var(--af-ink-2);
    font-size: 13px;
    font-weight: 600;
}

.af-req {
    color: var(--af-danger);
}

.af-field input[type="text"],
.af-field input[type="number"] {
    width: 100%;
    min-width: 0;
    height: 44px;
    padding: 0 13px;
    border: 1px solid #d5ded8;
    border-radius: 8px;
    background: #fff;
    color: var(--af-ink);
    font-size: 14px;
    transition: border-color .15s, box-shadow .15s;
}

.af-field input::placeholder {
    color: #a3afa8;
}

.af-field input[type="text"]:hover,
.af-field input[type="number"]:hover {
    border-color: #b9c7be;
}

.af-field input[type="text"]:focus,
.af-field input[type="number"]:focus {
    outline: none;
    border-color: var(--af-green-600);
    box-shadow: 0 0 0 3px rgba(24, 115, 70, .14);
}

/* File dropzone */
.af-file-input {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
    overflow: hidden;
    pointer-events: none;
}

.af-dropzone {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 18px;
    border: 2px dashed #c9d6ce;
    border-radius: 10px;
    background: var(--af-green-50);
    cursor: pointer;
    transition: border-color .15s, background .15s;
}

.af-dropzone:hover,
.af-dropzone.is-drag {
    border-color: var(--af-green-600);
    background: #eaf4ee;
}

.af-file-input:focus-visible + .af-dropzone {
    border-color: var(--af-green-600);
    box-shadow: 0 0 0 3px rgba(24, 115, 70, .18);
}

.af-dz-icon {
    flex: 0 0 auto;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #fff;
    color: var(--af-green-600);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, .06);
}

.af-dz-text {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.af-dz-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--af-ink);
    overflow-wrap: anywhere;
}

.af-dz-sub {
    font-size: 12px;
    color: var(--af-muted);
}

.af-help {
    color: var(--af-muted);
    font-size: 12px;
    line-height: 1.5;
}

.af-current-file {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: var(--af-ink-2);
    font-size: 12px;
    overflow-wrap: anywhere;
}

/* Toggle switch */
.af-switch {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    height: 44px;
    cursor: pointer;
    font-size: 13px;
    color: var(--af-ink-2);
    user-select: none;
}

.af-switch input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.af-switch-track {
    position: relative;
    flex: 0 0 auto;
    width: 42px;
    height: 24px;
    border-radius: 24px;
    background: #c5cfc9;
    transition: background .2s;
}

.af-switch-track::after {
    content: "";
    position: absolute;
    top: 3px;
    left: 3px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #fff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, .25);
    transition: transform .2s;
}

.af-switch input:checked + .af-switch-track {
    background: var(--af-green-600);
}

.af-switch input:checked + .af-switch-track::after {
    transform: translateX(18px);
}

.af-switch input:focus-visible + .af-switch-track {
    box-shadow: 0 0 0 3px rgba(24, 115, 70, .25);
}

/* Buttons */
.af-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.af-form-actions {
    padding-top: 6px;
}

.af-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    height: 40px;
    padding: 0 16px;
    border: 1px solid transparent;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    line-height: 1;
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
    transition: background .15s, border-color .15s, transform .05s, box-shadow .15s;
}

.af-btn:active {
    transform: translateY(1px);
}

.af-btn:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(24, 115, 70, .25);
}

.af-btn[disabled] {
    opacity: .65;
    cursor: not-allowed;
}

.af-btn-sm {
    height: 34px;
    padding: 0 12px;
    font-size: 12px;
}

.af-primary {
    background: var(--af-green-700);
    color: #fff;
}

.af-primary:hover {
    background: var(--af-green-900);
    color: #fff;
}

.af-secondary {
    background: #fff;
    color: var(--af-ink-2);
    border-color: #d5ded8;
}

.af-secondary:hover {
    background: var(--af-green-50);
    border-color: #b9c7be;
    color: var(--af-ink);
}

.af-danger {
    background: #fff;
    color: var(--af-danger);
    border-color: #f1c7c3;
}

.af-danger:hover {
    background: var(--af-danger-bg);
    border-color: #e8a8a2;
}

.af-inline-form {
    margin: 0;
    display: inline-flex;
}

/* Messages */
.af-message {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 13px 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 14px;
    line-height: 1.45;
}

.af-message i {
    margin-top: 2px;
}

.af-message-body {
    flex: 1 1 auto;
}

.af-message-close {
    flex: 0 0 auto;
    background: none;
    border: 0;
    padding: 2px 4px;
    color: inherit;
    opacity: .6;
    cursor: pointer;
    font-size: 14px;
}

.af-message-close:hover {
    opacity: 1;
}

.af-message.success {
    background: #e8f5ed;
    color: #0f5c33;
    border: 1px solid #b7ddc5;
}

.af-message.error {
    background: var(--af-danger-bg);
    color: #a32121;
    border: 1px solid #f0c4c4;
}

/* ---------- Table ---------- */
.af-table-wrap {
    width: 100%;
    overflow-x: auto;
    border: 1px solid var(--af-line);
    border-radius: 10px;
}

.af-table {
    width: 100%;
    min-width: 760px;
    border-collapse: collapse;
}

.af-table th,
.af-table td {
    text-align: left;
    padding: 14px 14px;
    border-bottom: 1px solid #edf1ee;
    vertical-align: middle;
    font-size: 13px;
    color: var(--af-ink);
}

.af-table tbody tr:last-child td {
    border-bottom: 0;
}

.af-table th {
    background: var(--af-green-50);
    color: #52635a;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
    white-space: nowrap;
}

.af-table tbody tr {
    transition: background .12s;
}

.af-table tbody tr:hover {
    background: #fafcfb;
}

.af-table tbody tr.is-current {
    background: #fffbea;
}

.af-col-id {
    width: 56px;
    color: var(--af-muted);
}

.af-col-order {
    width: 70px;
}

.af-title-cell {
    font-weight: 600;
    min-width: 180px;
}

.af-doc {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    min-width: 0;
}

.af-type {
    flex: 0 0 auto;
    width: 38px;
    height: 38px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    background: #f0f2f1;
    color: #5b6760;
}

.af-type.pdf {
    background: #fdecea;
    color: #c0372b;
}

.af-type.doc,
.af-type.docx {
    background: #e8f0fc;
    color: #2a5db0;
}

.af-doc-meta {
    min-width: 0;
}

.af-file-name {
    overflow-wrap: anywhere;
    font-weight: 500;
}

.af-doc-meta .af-help {
    margin-top: 2px;
}

.af-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border-radius: 20px;
    padding: 5px 10px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}

.af-status::before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
}

.af-status.active {
    background: #e4f7eb;
    color: #176b3d;
}

.af-status.inactive {
    background: #f0f2f1;
    color: #626d66;
}

.af-empty {
    text-align: center;
    color: var(--af-muted);
    padding: 44px 20px !important;
}

.af-empty i {
    display: block;
    margin-bottom: 10px;
    font-size: 30px;
    color: #c3d0c8;
}

/* ---------- Responsive ---------- */
@media (max-width: 900px) {
    #infoWrapper.col-lg-12 {
        flex-direction: column;
        margin: 16px auto;
    }

    #leftArea.col-lg-9 {
        width: 100%;
        max-width: 100%;
        padding: 20px;
    }
}

@media (max-width: 560px) {
    #leftArea.col-lg-9 {
        padding: 12px;
    }

    .af-header h2 {
        font-size: 20px;
    }

    .af-header-icon {
        width: 40px;
        height: 40px;
        font-size: 17px;
    }

    .af-card {
        padding: 16px;
    }

    .af-grid {
        grid-template-columns: 1fr;
    }

    .af-full {
        grid-column: auto;
    }

    .af-form-actions .af-btn {
        flex: 1 1 auto;
    }
}

@media (prefers-reduced-motion: reduce) {
    .af-root * {
        transition: none !important;
    }
}

/* ======================================================================
   Hardening against global site CSS + table / mobile fixes
   ====================================================================== */
#leftArea .af-root .af-header h2 {
    margin: 0 !important;
    padding: 0 !important;
    border: 0 !important;
    text-decoration: none !important;
    text-transform: none !important;
}

#leftArea .af-root .af-header p {
    margin: 4px 0 0 !important;
    padding: 0 !important;
    border: 0 !important;
}

#leftArea .af-root .af-card-head h3 {
    margin: 0 !important;
    padding: 0 !important;
    border: 0 !important;
}

#leftArea .af-root .af-field {
    display: flex !important;
    flex-direction: column !important;
    gap: 7px !important;
    margin: 0 !important;
    padding: 0 !important;
    float: none !important;
    width: auto !important;
}

#leftArea .af-root .af-field > label.af-label,
#leftArea .af-root .af-field > span.af-label {
    display: block !important;
    margin: 0 !important;
    padding: 0 !important;
    float: none !important;
    width: auto !important;
    line-height: 1.4 !important;
}

#leftArea .af-root .af-field input[type="text"],
#leftArea .af-root .af-field input[type="number"] {
    display: block !important;
    position: static !important;
    float: none !important;
    width: 100% !important;
    max-width: 100% !important;
    min-width: 0 !important;
    height: 44px !important;
    margin: 0 !important;
    padding: 0 13px !important;
    border: 1px solid #d5ded8 !important;
    border-radius: 8px !important;
    background: #fff !important;
    background-image: none !important;
    color: #1f2f26 !important;
    -webkit-text-fill-color: #1f2f26 !important;
    caret-color: #1f2f26 !important;
    font-size: 14px !important;
    font-weight: 400 !important;
    line-height: 42px !important;
    text-indent: 0 !important;
    text-shadow: none !important;
    letter-spacing: normal !important;
    text-transform: none !important;
    opacity: 1 !important;
    box-shadow: none !important;
    box-sizing: border-box !important;
}

#leftArea .af-root .af-field input[type="text"]:focus,
#leftArea .af-root .af-field input[type="number"]:focus {
    border-color: #187346 !important;
    box-shadow: 0 0 0 3px rgba(24, 115, 70, .14) !important;
    outline: none !important;
}

#leftArea .af-root .af-field input::placeholder {
    color: #a3afa8 !important;
    -webkit-text-fill-color: #a3afa8 !important;
    opacity: 1 !important;
}

#leftArea .af-root .af-form-actions {
    margin: 0 !important;
}

/* ---------- Table: balanced columns ---------- */
.af-root .af-table {
    table-layout: fixed;
    width: 100%;
    min-width: 880px;
    margin: 0;
}

.af-root .af-table th,
.af-root .af-table td {
    text-align: left;
    vertical-align: middle;
    padding: 14px 16px;
}

.af-root .af-table td.af-empty {
    text-align: center !important;
    padding: 48px 20px !important;
}

.af-root .af-table td.af-empty i {
    display: block;
    margin: 0 auto 12px;
}

.af-root .af-col-id {
    color: var(--af-muted);
}

.af-root .af-title-cell {
    overflow-wrap: anywhere;
}

.af-root .af-table .af-actions {
    flex-wrap: wrap;
    gap: 6px;
}

/* Desktop: don't force horizontal scroll unless really narrow */
@media (min-width: 1100px) {
    .af-root .af-table {
        min-width: 0;
    }
}

/* ---------- Mobile: table becomes stacked cards ---------- */
@media (max-width: 900px) {
    .af-root .af-table-wrap {
        border: 0;
        overflow: visible;
    }

    .af-root .af-table,
    .af-root .af-table tbody {
        display: block;
        min-width: 0;
        width: 100%;
    }

    .af-root .af-table colgroup,
    .af-root .af-table thead {
        display: none;
    }

    .af-root .af-table tbody tr {
        display: block;
        margin-bottom: 14px;
        border: 1px solid var(--af-line);
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 1px 3px rgba(20, 40, 28, .05);
        overflow: hidden;
    }

    .af-root .af-table tbody tr:hover {
        background: #fff;
    }

    .af-root .af-table tbody tr.is-current {
        background: #fffbea;
    }

    .af-root .af-table tbody td {
        display: grid;
        grid-template-columns: 88px minmax(0, 1fr);
        align-items: center;
        gap: 12px;
        width: 100% !important;
        padding: 11px 14px;
        border-bottom: 1px solid #edf1ee;
        text-align: left;
    }

    .af-root .af-table tbody td::before {
        content: attr(data-label);
        color: var(--af-muted);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .05em;
        text-transform: uppercase;
    }

    .af-root .af-table tbody td:last-child {
        border-bottom: 0;
        background: var(--af-green-50);
    }

    .af-root .af-table tbody td.af-empty {
        display: block;
        padding: 36px 16px !important;
        border-bottom: 0;
    }

    .af-root .af-table tbody td.af-empty::before {
        display: none;
    }

    .af-root .af-table tbody tr:has(td.af-empty) {
        border-style: dashed;
    }

    .af-root .af-col-id,
    .af-root .af-col-order {
        width: auto !important;
    }

    .af-root .af-table .af-actions .af-btn,
    .af-root .af-table .af-inline-form {
        flex: 1 1 auto;
    }

    .af-root .af-table .af-inline-form .af-btn {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .af-root .af-table tbody td {
        grid-template-columns: 1fr;
        gap: 4px;
    }

    .af-root .af-card-head {
        flex-wrap: wrap;
    }
}

/* ---------- Title dropdown ---------- */
#leftArea .af-root .af-field select {
    display: block !important;
    position: static !important;
    float: none !important;
    width: 100% !important;
    max-width: 100% !important;
    min-width: 0 !important;
    height: 44px !important;
    margin: 0 !important;
    padding: 0 38px 0 13px !important;
    border: 1px solid #d5ded8 !important;
    border-radius: 8px !important;
    background-color: #fff !important;
    background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'><path fill='none' stroke='%236f7f75' stroke-width='2' d='M1 1l5 5 5-5'/></svg>") !important;
    background-repeat: no-repeat !important;
    background-position: right 14px center !important;
    color: #1f2f26 !important;
    -webkit-text-fill-color: #1f2f26 !important;
    font-size: 14px !important;
    font-weight: 400 !important;
    line-height: 42px !important;
    text-indent: 0 !important;
    text-transform: none !important;
    opacity: 1 !important;
    box-shadow: none !important;
    box-sizing: border-box !important;
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
    appearance: none !important;
    cursor: pointer;
    text-overflow: ellipsis;
}

#leftArea .af-root .af-field select:hover {
    border-color: #b9c7be !important;
}

#leftArea .af-root .af-field select:focus {
    border-color: #187346 !important;
    box-shadow: 0 0 0 3px rgba(24, 115, 70, .14) !important;
    outline: none !important;
}

/* ---------- Custom searchable title dropdown ---------- */
#leftArea .af-root .af-combo {
    position: relative;
    width: 100%;
}

#leftArea .af-root .af-combo .af-combo-btn {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    gap: 10px !important;
    width: 100% !important;
    height: 44px !important;
    margin: 0 !important;
    padding: 0 14px !important;
    background: #fff !important;
    color: #1f2f26 !important;
    border: 1px solid #d5ded8 !important;
    border-radius: 8px !important;
    font-family: inherit !important;
    font-size: 14px !important;
    font-weight: 400 !important;
    line-height: 1.2 !important;
    text-align: left !important;
    text-transform: none !important;
    letter-spacing: normal !important;
    box-shadow: none !important;
    cursor: pointer;
    transition: border-color .15s, box-shadow .15s;
}

#leftArea .af-root .af-combo .af-combo-btn:hover {
    border-color: #b9c7be !important;
}

#leftArea .af-root .af-combo .af-combo-btn:focus,
#leftArea .af-root .af-combo.is-open .af-combo-btn {
    outline: none !important;
    border-color: #187346 !important;
    box-shadow: 0 0 0 3px rgba(24, 115, 70, .14) !important;
}

#leftArea .af-root .af-combo.is-invalid .af-combo-btn {
    border-color: #b42318 !important;
    box-shadow: 0 0 0 3px rgba(180, 35, 24, .12) !important;
}

.af-combo-value {
    flex: 1 1 auto;
    min-width: 0;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    font-weight: 500;
}

.af-combo-value.is-placeholder {
    color: #a3afa8;
    font-weight: 400;
}

.af-combo-caret {
    flex: 0 0 auto;
    color: #6f7f75;
    font-size: 12px;
    transition: transform .2s;
}

.af-combo.is-open .af-combo-caret {
    transform: rotate(180deg);
}

#leftArea .af-root .af-combo .af-combo-panel {
    position: absolute;
    z-index: 60;
    top: calc(100% + 6px);
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #d5ded8;
    border-radius: 10px;
    box-shadow: 0 14px 32px rgba(20, 40, 28, .16);
    overflow: hidden;
}

#leftArea .af-root .af-combo .af-combo-panel[hidden],
#leftArea .af-root .af-combo .af-combo-empty[hidden],
#leftArea .af-root .af-combo .af-combo-opt[hidden] {
    display: none !important;
}

.af-combo-search {
    position: relative;
    padding: 10px;
    border-bottom: 1px solid #e8eeea;
    background: #fafcfb;
}

.af-combo-search i {
    position: absolute;
    left: 22px;
    top: 50%;
    transform: translateY(-50%);
    color: #7a8c84;
    font-size: 13px;
    pointer-events: none;
}

#leftArea .af-root .af-combo .af-combo-search input[type="text"] {
    height: 38px !important;
    padding: 0 12px 0 34px !important;
    line-height: 36px !important;
    font-size: 13.5px !important;
}

#leftArea .af-root .af-combo ul.af-combo-list {
    list-style: none !important;
    margin: 0 !important;
    padding: 6px !important;
    max-height: 290px;
    overflow-y: auto;
}

#leftArea .af-root .af-combo li.af-combo-opt {
    display: flex !important;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    list-style: none !important;
    margin: 0 !important;
    padding: 10px 12px !important;
    border-radius: 7px;
    font-size: 13.5px;
    line-height: 1.35;
    color: #1f2f26;
    cursor: pointer;
}

#leftArea .af-root .af-combo li.af-combo-opt::before,
#leftArea .af-root .af-combo li.af-combo-opt::marker {
    content: none !important;
}

#leftArea .af-root .af-combo li.af-combo-opt.is-active {
    background: #f1f7f3;
}

#leftArea .af-root .af-combo li.af-combo-opt.is-selected {
    background: #e4f2ea;
    color: #0f5c33;
    font-weight: 600;
}

.af-combo-opt-text {
    min-width: 0;
    overflow-wrap: anywhere;
}

.af-combo-badge {
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 9px;
    border-radius: 20px;
    background: #e4f7eb;
    color: #176b3d;
    font-size: 10.5px;
    font-weight: 700;
    white-space: nowrap;
}

.af-combo-badge i {
    font-size: 9px;
}

.af-combo-empty {
    padding: 22px 14px;
    text-align: center;
    color: #6f7f75;
    font-size: 13px;
}

#af-combo-note.is-replace {
    color: #8a5a00;
}

#af-combo-note.is-error {
    color: #b42318;
    font-weight: 600;
}

@media (max-width: 560px) {
    #leftArea .af-root .af-combo ul.af-combo-list {
        max-height: 240px;
    }

    #leftArea .af-root .af-combo li.af-combo-opt {
        padding: 12px !important;
    }
}
</style>

<div class="af-root">

<div class="af-header">
    <div class="af-header-icon">
        <i class="fas fa-file-pdf"></i>
    </div>

    <div>
        <h2>Application Forms Manager</h2>

        <p>
            Upload and manage the documents displayed on the public website.
        </p>
    </div>
</div>

<?php if ($message !== '') { ?>
    <div
        class="af-message <?php echo afH($messageType); ?>"
        role="<?php echo $messageType === 'error' ? 'alert' : 'status'; ?>"
    >
        <i class="fas <?php
            echo $messageType === 'error'
                ? 'fa-circle-exclamation'
                : 'fa-circle-check';
        ?>"></i>

        <div class="af-message-body">
            <?php echo afH($message); ?>
        </div>

        <button
            type="button"
            class="af-message-close"
            aria-label="Dismiss message"
            onclick="this.parentNode.style.display='none';"
        >
            <i class="fas fa-xmark"></i>
        </button>
    </div>
<?php } ?>

<div
    class="af-card<?php echo $editForm ? ' is-editing' : ''; ?>"
    id="af-form-card"
>

    <div class="af-card-head">
        <h3>
            <i class="fas <?php
                echo $editForm
                    ? 'fa-pen-to-square'
                    : 'fa-cloud-arrow-up';
            ?>"></i>

            <?php
            echo $editForm
                ? 'Edit Application Form'
                : 'Upload Application Form';
            ?>
        </h3>

        <?php if ($editForm) { ?>
            <span class="af-badge-edit">
                EDITING #<?php echo (int)$editForm['id']; ?>
            </span>
        <?php } ?>
    </div>

    <form
        method="POST"
        action="<?php echo afH($afSelf); ?>"
        enctype="multipart/form-data"
        id="af-form"
    >

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo afH($csrfToken); ?>"
        >

        <input
            type="hidden"
            name="action"
            value="<?php echo $editForm ? 'update' : 'add'; ?>"
        >

        <?php if ($editForm) { ?>
            <input
                type="hidden"
                name="id"
                value="<?php echo (int)$editForm['id']; ?>"
            >
        <?php } ?>

        <div class="af-grid">

            <div class="af-field af-full">
                <label class="af-label" for="af-combo-btn">
                    Form Title <span class="af-req">*</span>
                </label>

                <div class="af-combo" id="af-combo">

                    <input
                        type="hidden"
                        name="title"
                        id="af-title-value"
                        value="<?php echo afH($afCurrentTitle); ?>"
                    >

                    <button
                        type="button"
                        class="af-combo-btn"
                        id="af-combo-btn"
                        aria-haspopup="listbox"
                        aria-expanded="false"
                    >
                        <span
                            class="af-combo-value<?php
                            echo $afCurrentTitle === ''
                                ? ' is-placeholder'
                                : '';
                            ?>"
                            id="af-combo-value"
                        ><?php
                            echo $afCurrentTitle !== ''
                                ? afH($afCurrentTitle)
                                : 'Select form title';
                        ?></span>

                        <i class="fas fa-chevron-down af-combo-caret"></i>
                    </button>

                    <div class="af-combo-panel" id="af-combo-panel" hidden>

                        <div class="af-combo-search">
                            <i class="fas fa-magnifying-glass"></i>

                            <input
                                type="text"
                                id="af-combo-search"
                                placeholder="Search form title..."
                                autocomplete="off"
                                aria-label="Search form title"
                            >
                        </div>

                        <ul
                            class="af-combo-list"
                            id="af-combo-list"
                            role="listbox"
                        >
                            <?php
                            $afComboItems = $afTitleOptions;

                            if (
                                $afCurrentTitle !== '' &&
                                !in_array($afCurrentTitle, $afComboItems, true)
                            ) {
                                array_unshift($afComboItems, $afCurrentTitle);
                            }

                            foreach ($afComboItems as $afOption) {
                                $afIsUploaded = isset($afUploadedTitles[$afOption]);
                                $afIsSelected = ($afCurrentTitle === $afOption);
                                ?>
                                <li
                                    role="option"
                                    class="af-combo-opt<?php
                                    echo $afIsSelected ? ' is-selected' : '';
                                    ?>"
                                    data-value="<?php echo afH($afOption); ?>"
                                    data-uploaded="<?php
                                    echo $afIsUploaded ? '1' : '0';
                                    ?>"
                                    aria-selected="<?php
                                    echo $afIsSelected ? 'true' : 'false';
                                    ?>"
                                >
                                    <span class="af-combo-opt-text">
                                        <?php echo afH($afOption); ?>
                                    </span>

                                    <?php if ($afIsUploaded) { ?>
                                        <span class="af-combo-badge">
                                            <i class="fas fa-check"></i>
                                            Uploaded
                                        </span>
                                    <?php } ?>
                                </li>
                                <?php
                            }
                            ?>
                        </ul>

                        <div class="af-combo-empty" id="af-combo-empty" hidden>
                            No matching titles
                        </div>

                    </div>

                </div>

                <div
                    class="af-help"
                    id="af-combo-note"
                    data-default="Titles marked Uploaded already have a file. Uploading again replaces it."
                >
                    Titles marked Uploaded already have a file.
                    Uploading again replaces it.
                </div>
            </div>

            <div class="af-field af-full">
                <label class="af-label" for="form_file">
                    Document
                    <?php if ($editForm) { ?>
                        <span class="af-help">(optional &mdash; leave empty to keep current file)</span>
                    <?php } else { ?>
                        <span class="af-req">*</span>
                    <?php } ?>
                </label>

                <input
                    type="file"
                    id="form_file"
                    name="form_file"
                    class="af-file-input"
                    accept=".pdf,.doc,.docx"
                    <?php echo $editForm ? '' : 'required'; ?>
                >

                <label for="form_file" class="af-dropzone" id="af-dropzone">
                    <span class="af-dz-icon">
                        <i class="fas fa-file-arrow-up"></i>
                    </span>

                    <span class="af-dz-text">
                        <span class="af-dz-title" id="af-dz-title">
                            Click to choose a file or drag it here
                        </span>

                        <span class="af-dz-sub">
                            PDF, DOC or DOCX &middot; Maximum 10 MB
                        </span>
                    </span>
                </label>

                <?php if ($editForm) { ?>
                    <div class="af-current-file">
                        <i class="fas fa-paperclip"></i>
                        Current document:
                        <strong>
                            <?php
                            echo afH($editForm['original_file_name']);
                            ?>
                        </strong>
                    </div>
                <?php } ?>
            </div>

            <div class="af-field">
                <label class="af-label" for="sort_order">
                    Display Order <span class="af-req">*</span>
                </label>

                <input
                    type="number"
                    id="sort_order"
                    name="sort_order"
                    min="0"
                    step="1"
                    required
                    value="<?php
                    echo afH(
                        $editForm
                            ? $editForm['sort_order']
                            : '0'
                    );
                    ?>"
                >

                <div class="af-help">
                    Lower numbers appear first on the website.
                </div>
            </div>

            <div class="af-field">
                <span class="af-label">Status</span>

                <label class="af-switch">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        <?php
                        echo (
                            !$editForm ||
                            (int)$editForm['is_active'] === 1
                        ) ? 'checked' : '';
                        ?>
                    >

                    <span class="af-switch-track"></span>

                    <span>Active &mdash; display on website</span>
                </label>
            </div>

            <div class="af-field af-full">
                <div class="af-actions af-form-actions">

                    <button
                        type="submit"
                        class="af-btn af-primary"
                        id="af-submit"
                    >
                        <i class="fas fa-cloud-arrow-up"></i>

                        <?php
                        echo $editForm
                            ? 'Update Form'
                            : 'Upload Form';
                        ?>
                    </button>

                    <?php if ($editForm) { ?>
                        <a
                            href="<?php echo afH($afSelf); ?>"
                            class="af-btn af-secondary"
                        >
                            Cancel
                        </a>
                    <?php } ?>

                </div>
            </div>

        </div>
    </form>

</div>

<div class="af-card">

    <div class="af-card-head">
        <h3>
            <i class="fas fa-folder-open"></i>
            Uploaded Application Forms
        </h3>

        <span class="af-count"><?php echo count($forms); ?></span>
    </div>

    <div class="af-table-wrap">

        <table class="af-table">

            <colgroup>
                <col style="width:6%">
                <col style="width:24%">
                <col style="width:30%">
                <col style="width:8%">
                <col style="width:12%">
                <col style="width:20%">
            </colgroup>

            <thead>
                <tr>
                    <th class="af-col-id">ID</th>
                    <th>Title</th>
                    <th>Document</th>
                    <th class="af-col-order">Order</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

                <?php if (empty($forms)) { ?>

                    <tr>
                        <td colspan="6" class="af-empty" data-label="">
                            <i class="fas fa-inbox"></i>
                            No application forms have been uploaded.
                        </td>
                    </tr>

                <?php } ?>

                <?php foreach ($forms as $form) { ?>

                    <tr<?php
                        echo (
                            $editForm &&
                            (int)$editForm['id'] === (int)$form['id']
                        ) ? ' class="is-current"' : '';
                    ?>>

                        <td class="af-col-id" data-label="ID">
                            <?php echo (int)$form['id']; ?>
                        </td>

                        <td class="af-title-cell" data-label="Title">
                            <?php echo afH($form['title']); ?>
                        </td>

                        <td data-label="Document">

                            <div class="af-doc">

                                <span class="af-type <?php
                                    echo afH(strtolower($form['file_type']));
                                ?>">
                                    <i class="fas <?php
                                        echo strtolower($form['file_type']) === 'pdf'
                                            ? 'fa-file-pdf'
                                            : 'fa-file-word';
                                    ?>"></i>
                                </span>

                                <div class="af-doc-meta">

                                    <div class="af-file-name">
                                        <?php
                                        echo afH(
                                            $form['original_file_name']
                                        );
                                        ?>
                                    </div>

                                    <div class="af-help">
                                        <?php
                                        echo strtoupper(
                                            afH($form['file_type'])
                                        );

                                        echo ' &middot; ';

                                        if ((int)$form['file_size'] >= 1048576) {
                                            echo number_format(
                                                (int)$form['file_size'] / 1048576,
                                                2
                                            );

                                            echo ' MB';
                                        } else {
                                            echo number_format(
                                                (int)$form['file_size'] / 1024,
                                                1
                                            );

                                            echo ' KB';
                                        }
                                        ?>
                                    </div>

                                </div>

                            </div>

                        </td>

                        <td class="af-col-order" data-label="Order">
                            <?php echo (int)$form['sort_order']; ?>
                        </td>

                        <td data-label="Status">

                            <?php if ((int)$form['is_active'] === 1) { ?>

                                <span class="af-status active">
                                    Active
                                </span>

                            <?php } else { ?>

                                <span class="af-status inactive">
                                    Inactive
                                </span>

                            <?php } ?>

                        </td>

                        <td data-label="Actions">

                            <div class="af-actions">

                                <?php if (!empty($form['source'])) { ?>
                                    <a
                                        href="<?php
                                        echo afH(
                                            $s3Client->getObjectUrl(
                                                AWS_BUCKET,
                                                $form['source']
                                            )
                                        );
                                        ?>"
                                        target="_blank"
                                        rel="noopener"
                                        class="af-btn af-btn-sm af-secondary"
                                    >
                                        <i class="fas fa-arrow-up-right-from-square"></i>
                                        View
                                    </a>
                                <?php } ?>

                                <a
                                    href="<?php echo afH($afSelf); ?>?edit=<?php
                                    echo (int)$form['id'];
                                    ?>"
                                    class="af-btn af-btn-sm af-secondary"
                                >
                                    <i class="fas fa-pen"></i>
                                    Edit
                                </a>

                                <form
                                    method="POST"
                                    action="<?php echo afH($afSelf); ?>"
                                    class="af-inline-form"
                                    onsubmit="return confirm(
                                        'Delete this form and its S3 document?'
                                    );"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?php echo afH($csrfToken); ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="delete"
                                    >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?php
                                        echo (int)$form['id'];
                                        ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="af-btn af-btn-sm af-danger"
                                    >
                                        <i class="fas fa-trash"></i>
                                        Delete
                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>

                <?php } ?>

            </tbody>

        </table>

    </div>

</div>

</div><!-- /.af-root -->

<script>
(function () {
    var combo = document.getElementById('af-combo');
    if (!combo) { return; }

    var btn = document.getElementById('af-combo-btn');
    var panel = document.getElementById('af-combo-panel');
    var search = document.getElementById('af-combo-search');
    var list = document.getElementById('af-combo-list');
    var emptyEl = document.getElementById('af-combo-empty');
    var valueEl = document.getElementById('af-combo-value');
    var note = document.getElementById('af-combo-note');
    var hidden = document.getElementById('af-title-value');
    var form = document.getElementById('af-form');
    var items = [].slice.call(list.querySelectorAll('.af-combo-opt'));
    var active = -1;
    var defaultNote = note ? note.getAttribute('data-default') : '';

    function visible() {
        return items.filter(function (li) { return !li.hidden; });
    }

    function setActive(index) {
        var v = visible();
        items.forEach(function (li) { li.classList.remove('is-active'); });

        if (!v.length) { active = -1; return; }

        active = Math.max(0, Math.min(index, v.length - 1));
        v[active].classList.add('is-active');
        v[active].scrollIntoView({ block: 'nearest' });
    }

    function filter(q) {
        q = q.toLowerCase().trim();
        var any = false;

        items.forEach(function (li) {
            var match = li.getAttribute('data-value').toLowerCase().indexOf(q) !== -1;
            li.hidden = !match;
            if (match) { any = true; }
        });

        emptyEl.hidden = any;
        setActive(0);
    }

    function setNote(text, cls) {
        if (!note) { return; }
        note.textContent = text;
        note.className = 'af-help' + (cls ? ' ' + cls : '');
    }

    function updateNote(li) {
        if (!li) { setNote(defaultNote, ''); return; }

        if (li.getAttribute('data-uploaded') === '1') {
            setNote('This title already has a file. Uploading will replace it.', 'is-replace');
        } else {
            setNote('New form. It will be added to the list.', '');
        }
    }

    function openList() {
        panel.hidden = false;
        combo.classList.add('is-open');
        btn.setAttribute('aria-expanded', 'true');
        search.value = '';
        filter('');

        var v = visible();
        var sel = list.querySelector('.is-selected');
        if (sel) { setActive(v.indexOf(sel)); }

        search.focus();
    }

    function closeList(focusBtn) {
        panel.hidden = true;
        combo.classList.remove('is-open');
        btn.setAttribute('aria-expanded', 'false');
        if (focusBtn) { btn.focus(); }
    }

    function choose(li) {
        hidden.value = li.getAttribute('data-value');
        valueEl.textContent = hidden.value;
        valueEl.classList.remove('is-placeholder');

        items.forEach(function (x) {
            x.classList.remove('is-selected');
            x.setAttribute('aria-selected', 'false');
        });

        li.classList.add('is-selected');
        li.setAttribute('aria-selected', 'true');
        combo.classList.remove('is-invalid');
        updateNote(li);
        closeList(true);
    }

    btn.addEventListener('click', function () {
        if (panel.hidden) { openList(); } else { closeList(false); }
    });

    btn.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown' && panel.hidden) {
            e.preventDefault();
            openList();
        }
    });

    search.addEventListener('input', function () { filter(search.value); });

    search.addEventListener('keydown', function (e) {
        var v = visible();

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setActive(active + 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActive(active - 1);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (active > -1 && v[active]) { choose(v[active]); }
        } else if (e.key === 'Escape') {
            e.preventDefault();
            closeList(true);
        } else if (e.key === 'Tab') {
            closeList(false);
        }
    });

    list.addEventListener('click', function (e) {
        var li = e.target.closest('.af-combo-opt');
        if (li) { choose(li); }
    });

    list.addEventListener('mousemove', function (e) {
        var li = e.target.closest('.af-combo-opt');
        if (!li) { return; }
        var idx = visible().indexOf(li);
        if (idx > -1 && idx !== active) { setActive(idx); }
    });

    document.addEventListener('click', function (e) {
        if (!combo.contains(e.target) && !panel.hidden) { closeList(false); }
    });

    /* Initial note for edit mode */
    var preselected = list.querySelector('.is-selected');
    if (preselected) { updateNote(preselected); }

    /* A hidden input cannot use the native "required" check */
    if (form) {
        form.addEventListener('submit', function (e) {
            if (!hidden.value) {
                e.preventDefault();
                e.stopImmediatePropagation();
                combo.classList.add('is-invalid');
                setNote('Please select a form title.', 'is-error');
                openList();
            }
        });
    }
})();
</script>

<script>
(function () {
    var input = document.getElementById('form_file');
    var zone = document.getElementById('af-dropzone');
    var title = document.getElementById('af-dz-title');
    var form = document.getElementById('af-form');
    var submit = document.getElementById('af-submit');

    if (!input || !zone || !title) { return; }

    var defaultText = title.textContent;

    function formatSize(bytes) {
        if (bytes >= 1048576) {
            return (bytes / 1048576).toFixed(2) + ' MB';
        }
        return (bytes / 1024).toFixed(1) + ' KB';
    }

    function refresh() {
        if (input.files && input.files.length) {
            var f = input.files[0];
            title.textContent = f.name + ' (' + formatSize(f.size) + ')';
        } else {
            title.textContent = defaultText;
        }
    }

    input.addEventListener('change', refresh);

    ['dragenter', 'dragover'].forEach(function (evt) {
        zone.addEventListener(evt, function (e) {
            e.preventDefault();
            zone.classList.add('is-drag');
        });
    });

    ['dragleave', 'drop'].forEach(function (evt) {
        zone.addEventListener(evt, function (e) {
            e.preventDefault();
            zone.classList.remove('is-drag');
        });
    });

    zone.addEventListener('drop', function (e) {
        if (e.dataTransfer && e.dataTransfer.files.length) {
            input.files = e.dataTransfer.files;
            refresh();
        }
    });

    /* Prevent double submits while the upload is in progress */
    if (form && submit) {
        form.addEventListener('submit', function () {
            if (form.checkValidity()) {
                setTimeout(function () {
                    submit.disabled = true;
                    submit.innerHTML =
                        '<i class="fas fa-spinner fa-spin"></i> Please wait...';
                }, 0);
            }
        });
    }

    /* When editing, bring the form into view */
    <?php if ($editForm) { ?>
    var card = document.getElementById('af-form-card');
    if (card && card.scrollIntoView) {
        card.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    <?php } ?>
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