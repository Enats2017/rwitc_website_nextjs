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
$pdfType   = 'annual_report';              // must match the key in pdf_registry.php and the "type" column
$pdfTitle  = 'Annual Report';              // heading of this admin page
$moduleKey = 'annual_report';              // module permission name used by hasModuleAccess()
$pageFile  = 'annualReportManager.php';    // this file's own name (must match the real file name)

$perPage  = 6;                      // records per page
$s3Folder = 'uploads/club/';        // S3 folder (key prefix) for all PDFs

$sessionKey = 'menupdf_' . $pdfType;   // session key prefix (csrf + flash)

// Menu title template comes from pdf_registry.php ({label} = report number typed in admin)
$pdfRegistry = require __DIR__ . '/../rwitc_website_api/pdf_registry.php';
$menuTitle   = isset($pdfRegistry[$pdfType]['menu_title']) ? $pdfRegistry[$pdfType]['menu_title'] : '{label}';

// ---------------------------------------------------------------
// LOGIN + MODULE ACCESS CHECK
// ---------------------------------------------------------------
$secmsg = '';
if (!isAdminlogin()) {
    $secmsg = "You do not have access to this page.";
} elseif (!hasModuleAccess($moduleKey)) {
    $secmsg = "You do not have access to this module.";
}

// CSRF token
if (empty($_SESSION[$sessionKey . '_csrf'])) {
    $_SESSION[$sessionKey . '_csrf'] = bin2hex(openssl_random_pseudo_bytes(16));
}
$csrf = $_SESSION[$sessionKey . '_csrf'];

// FLASH MESSAGE
$flash = '';
$flashType = '';
if (isset($_SESSION[$sessionKey . '_flash'])) {
    $flash = $_SESSION[$sessionKey . '_flash']['text'];
    $flashType = $_SESSION[$sessionKey . '_flash']['type'];
    unset($_SESSION[$sessionKey . '_flash']);
}

// ---------------------------------------------------------------
// HELPERS
// ---------------------------------------------------------------
function menuPdfSetFlash($text, $type)
{
    global $sessionKey;
    $_SESSION[$sessionKey . '_flash'] = array('text' => $text, 'type' => $type);
}

// Returns a ready S3 client
function menuPdfS3Client()
{
    return new S3Client([
        'version'     => 'latest',
        'region'      => AWS_REGION,
        'credentials' => [
            'key'    => AWS_ACCESS_KEY_ID,
            'secret' => AWS_SECRET_ACCESS_KEY,
        ],
    ]);
}

// Extracts the S3 key (uploads/club/xxx.pdf) from a stored S3 url
function menuPdfKeyFromS3Url($s3Url, $folder)
{
    $path = (string)parse_url((string)$s3Url, PHP_URL_PATH);
    $path = urldecode($path);
    $pos  = strpos($path, $folder);
    if ($pos === false) {
        return '';
    }
    return substr($path, $pos);
}

// Deletes a PDF from S3 using its stored S3 url (best effort)
function menuPdfDeleteFromS3($s3Url, $folder)
{
    $key = menuPdfKeyFromS3Url($s3Url, $folder);
    if ($key === '') {
        return;
    }
    try {
        menuPdfS3Client()->deleteObject(array(
            'Bucket' => AWS_BUCKET,
            'Key'    => $key,
        ));
    } catch (AwsException $e) {
        // ignore, DB is the source of truth
    }
}

// Validates and uploads the PDF to S3. Returns the S3 url on success, or '' and sets $err on failure.
function menuPdfUploadPdf($folder, &$err)
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

    $clean = preg_replace('/[^A-Za-z0-9._-]/', '_', $orig);
    $key   = $folder . time() . '_' . $clean;

    try {
        $result = menuPdfS3Client()->putObject([
            'Bucket'      => AWS_BUCKET,
            'Key'         => $key,
            'SourceFile'  => $tmp,
            'ContentType' => 'application/pdf',
        ]);
        return $result['ObjectURL'];
    } catch (AwsException $e) {
        $err = 'Could not upload file to S3. Please try again.';
        return '';
    }
}

// Display name from the stored S3 url (timestamp prefix removed)
function menuPdfFileLabel($s3Url)
{
    $name = basename(urldecode((string)parse_url((string)$s3Url, PHP_URL_PATH)));
    return preg_replace('/^\d+_/', '', $name);
}

// Menu title shown on the website, example: 112th Annual Report
function menuPdfMenuTitle($label)
{
    global $menuTitle;
    return str_replace('{label}', $label, $menuTitle);
}

// URL builder for redirects and pagination
function menuPdfPageUrl($p)
{
    global $pageFile;
    return $pageFile . ($p > 1 ? '?page=' . (int)$p : '');
}

// URL for HTML links (the page uses <base href>, so the folder prefix is needed)
function menuPdfHtmlUrl($p)
{
    return 'turf-console/' . menuPdfPageUrl($p);
}

// ---------------------------------------------------------------
// POST ACTIONS (save / update / delete)
// ---------------------------------------------------------------
if (empty($secmsg) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $action   = isset($_POST['action']) ? $_POST['action'] : '';
    $pageNo   = isset($_POST['page']) ? max(1, (int)$_POST['page']) : 1;
    $redirect = menuPdfPageUrl($pageNo);

    // CSRF check
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION[$sessionKey . '_csrf'], (string)$_POST['csrf'])) {
        menuPdfSetFlash('Invalid request. Please try again.', 'error');
        header('Location: ' . $pageFile);
        exit;
    }

    // ---------------- DELETE ----------------
    if ($action === 'delete') {

        $id  = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $row = $id > 0 ? $db->getSingleRowAssoc("SELECT * FROM club_menu_pdf WHERE id = $id AND `type` = '$pdfType'") : null;

        if ($row) {
            try {
                $db->query("DELETE FROM club_menu_pdf WHERE id = $id AND `type` = '$pdfType'");
                menuPdfDeleteFromS3($row['file_url'], $s3Folder);
                menuPdfSetFlash('Entry deleted successfully.', 'success');
            } catch (Exception $e) {
                menuPdfSetFlash('Database error. Could not delete.', 'error');
            }
        } else {
            menuPdfSetFlash('Entry not found.', 'error');
        }

        // if the current page becomes empty, go back one page
        $total    = (int)$db->getSingleValue("SELECT COUNT(*) FROM club_menu_pdf WHERE `type` = '$pdfType'");
        $lastPage = max(1, (int)ceil($total / $perPage));
        if ($pageNo > $lastPage) {
            $pageNo = $lastPage;
        }
        header('Location: ' . menuPdfPageUrl($pageNo));
        exit;
    }

    // ---------------- SAVE / UPDATE ----------------
    if ($action === 'save' || $action === 'update') {

        $date   = isset($_POST['report_date']) ? trim($_POST['report_date']) : '';
        $label  = isset($_POST['menu_label']) ? trim($_POST['menu_label']) : '';
        $id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $err    = '';
        $oldRow = null;

        if ($action === 'update') {
            $oldRow = $id > 0 ? $db->getSingleRowAssoc("SELECT * FROM club_menu_pdf WHERE id = $id AND `type` = '$pdfType'") : null;
            if (!$oldRow) {
                $err = 'Entry not found.';
            }
        }

        $d = DateTime::createFromFormat('Y-m-d', $date);
        if ($err !== '') {
            // already set
        } elseif (!$d || $d->format('Y-m-d') !== $date) {
            $err = 'Invalid date.';
        } elseif ($label === '') {
            $err = 'Report number is required.';
        } elseif (strlen($label) > 50) {
            $err = 'Report number is too long (maximum 50 characters).';
        } elseif (!preg_match('/^[A-Za-z0-9 .\-\/]+$/', $label)) {
            $err = 'Report number has invalid characters. Use letters, numbers, space, dot, hyphen or slash.';
        }

        $newS3Url       = '';   // newly uploaded file S3 url
        $oldS3UrlDelete = '';   // old file S3 url to delete after a successful update

        if ($err === '') {

            $hasUpload = isset($_FILES['report_file']) && $_FILES['report_file']['error'] !== UPLOAD_ERR_NO_FILE;

            if ($hasUpload) {
                $newS3Url = menuPdfUploadPdf($s3Folder, $err);
                if ($err === '' && $oldRow) {
                    $oldS3UrlDelete = $oldRow['file_url'];
                }
            } elseif (!$oldRow) {
                $err = 'Please choose a PDF file.';
            }
        }

        if ($err === '') {

            $finalS3Url = ($newS3Url !== '') ? $newS3Url : ($oldRow ? $oldRow['file_url'] : '');

            try {
                if ($action === 'update') {
                    $db->update("UPDATE club_menu_pdf SET
                                    `label` = '" . $db->escape($label) . "',
                                    `date` = '" . $db->escape($date) . "',
                                    file_url = '" . $db->escape($finalS3Url) . "'
                                 WHERE id = $id AND `type` = '$pdfType'");
                    menuPdfSetFlash('Entry updated successfully.', 'success');
                } else {
                    $db->insert("INSERT INTO club_menu_pdf (`type`, `label`, file_url, `date`)
                                 VALUES ('" . $pdfType . "',
                                         '" . $db->escape($label) . "',
                                         '" . $db->escape($finalS3Url) . "',
                                         '" . $db->escape($date) . "')");
                    menuPdfSetFlash('Saved successfully.', 'success');
                    $redirect = $pageFile;   // new entry -> page 1
                }

                if ($oldS3UrlDelete !== '') {
                    menuPdfDeleteFromS3($oldS3UrlDelete, $s3Folder);
                }
            } catch (Exception $e) {
                // if the DB save fails, remove the newly uploaded file
                if ($newS3Url !== '') {
                    menuPdfDeleteFromS3($newS3Url, $s3Folder);
                }
                menuPdfSetFlash('Database error. Could not save.', 'error');
            }
        } else {
            menuPdfSetFlash($err, 'error');
            if ($action === 'update' && $id > 0) {
                $redirect = $pageFile . '?edit=' . $id . ($pageNo > 1 ? '&page=' . $pageNo : '');
            }
        }

        header('Location: ' . $redirect);
        exit;
    }
}

// ---------------------------------------------------------------
// PAGE DATA (list + edit row)
// ---------------------------------------------------------------
$page       = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$editRow    = null;
$rows       = array();
$total      = 0;
$totalPages = 1;
$offset     = 0;

if (empty($secmsg)) {

    if (isset($_GET['edit']) && (int)$_GET['edit'] > 0) {
        $eid     = (int)$_GET['edit'];
        $editRow = $db->getSingleRowAssoc("SELECT * FROM club_menu_pdf WHERE id = $eid AND `type` = '$pdfType'");
    }

    $total      = (int)$db->getSingleValue("SELECT COUNT(*) FROM club_menu_pdf WHERE `type` = '$pdfType'");
    $totalPages = max(1, (int)ceil($total / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;

    $rows = $db->getMultiDimensionalArray(
        "SELECT id, `label`, `date`, file_url
         FROM club_menu_pdf
         WHERE `type` = '$pdfType'
         ORDER BY `date` DESC, id DESC
         LIMIT $perPage OFFSET $offset"
    );
}

$isEdit = !empty($editRow);

// default form values
$fDate  = $isEdit ? $editRow['date'] : '';
$fLabel = $isEdit ? (string)$editRow['label'] : '';
$fS3Url = $isEdit ? (string)$editRow['file_url'] : '';

// PAGE TITLE
$pageTitle = $pdfTitle;

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

    .ar-card { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; padding: 24px; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03); max-width: 820px; margin-bottom: 26px; }
    .ar-card.wide { max-width: 100%; }
    .ar-card-title { font-family: inherit; font-size: 17px; font-weight: 600; color: #2b332f; margin: 0 0 20px; padding-bottom: 14px; border-bottom: 1px solid #eef1ef; }
    .ar-card-title::before, .ar-card-title::after { display: none; content: none; }
    .ar-card-title .ar-count { font-size: 13px; font-weight: 500; color: #7a8c84; margin-left: 8px; }
    .ar-card.editing { border-color: #1a7a45; box-shadow: 0 0 0 3px rgba(26, 122, 69, 0.10); }

    .ar-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .ar-field { margin-bottom: 18px; min-width: 0; }
    .ar-field label { display: block; font-size: 13px; font-weight: 600; color: #2b332f; margin-bottom: 6px; }
    .ar-field label .req { color: #c0392b; }
    .ar-input { width: 100%; box-sizing: border-box; padding: 10px 12px; font-size: 15px; font-family: inherit; color: #2b332f; background: #fff; border: 1px solid #d3d9d6; border-radius: 8px; outline: none; transition: border-color .15s ease, box-shadow .15s ease; }
    .ar-input::placeholder { color: #9aa9a2; }
    .ar-input:focus { border-color: #1a7a45; box-shadow: 0 0 0 3px rgba(26, 122, 69, 0.15); }
    .ar-hint { font-size: 12px; color: #7a8c84; margin-top: 6px; display: flex; justify-content: space-between; gap: 10px; }
    .ar-hint strong { color: #0f5c33; }
    .ar-input.invalid, .ar-drop.invalid { border-color: #c0392b; }
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

    /* ---------- LIST TABLE ---------- */
    .ar-table-wrap { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .ar-table { width: 100%; border-collapse: collapse; min-width: 560px; }
    .ar-table th { text-align: left; font-size: 12px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; color: #0f5c33; background: #f3f6f4; padding: 11px 12px; border-bottom: 1px solid #e2e6e4; white-space: nowrap; }
    .ar-table td { font-size: 14px; color: #2b332f; padding: 12px; border-bottom: 1px solid #eef1ef; vertical-align: middle; }
    .ar-table tr:hover td { background: #f8faf9; }
    .ar-table tr.is-editing td { background: #eef7f1; }
    .ar-t-title { font-weight: 600; word-break: break-word; }
    .ar-t-sub { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #7a8c84; margin-top: 3px; word-break: break-word; }
    .ar-t-sub i { color: #c0392b; flex-shrink: 0; }
    .ar-act { display: flex; gap: 6px; flex-wrap: nowrap; }
    .ar-icon-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #d3d9d6; background: #fff; color: #2b332f; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; font-size: 13px; text-decoration: none; transition: all .15s ease; padding: 0; }
    .ar-icon-btn:hover { border-color: #1a7a45; color: #0f5c33; background: #eef7f1; }
    .ar-icon-btn.del:hover { border-color: #c0392b; color: #a12626; background: #fde8e8; }
    .ar-act form { margin: 0; }
    .ar-empty { text-align: center; padding: 30px 10px; color: #7a8c84; font-size: 14px; }

    /* ---------- PAGINATION ---------- */
    .ar-pager { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-top: 18px; }
    .ar-pager-info { font-size: 13px; color: #7a8c84; }
    .ar-pages { display: flex; gap: 6px; flex-wrap: wrap; }
    .ar-page { min-width: 36px; height: 36px; padding: 0 10px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #d3d9d6; border-radius: 8px; background: #fff; color: #2b332f; font-size: 14px; font-weight: 500; text-decoration: none; transition: all .15s ease; }
    .ar-page:hover { border-color: #1a7a45; color: #0f5c33; background: #eef7f1; }
    .ar-page.current { background: #0f5c33; border-color: #0f5c33; color: #fff; }
    .ar-page.disabled { opacity: .45; pointer-events: none; }
    .ar-page.dots { border: none; background: none; pointer-events: none; }

    html, body { scrollbar-width: none; -ms-overflow-style: none; }
    html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }

    @media (min-width: 1920px) {
        .ar-title { font-size: 30px; }
        .ar-card { max-width: 980px; padding: 30px; }
        .ar-card.wide { max-width: 100%; }
    }

    @media (max-width: 700px) {
        .ar-title { font-size: 20px; }
        .ar-card { padding: 16px; border-radius: 10px; }
        .ar-row { grid-template-columns: 1fr; gap: 0; }
        .ar-drop { padding: 24px 12px; }
    }

    @media (max-width: 560px) {
        .ar-header { flex-direction: column; align-items: flex-start; margin-bottom: 14px; gap: 8px; }
        .ar-subtitle { font-size: 13px; }
        .ar-actions { flex-direction: column; }
        .ar-btn { width: 100%; }
        .ar-pager { flex-direction: column; align-items: flex-start; }
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
                <h1 class="ar-title"><?php echo htmlspecialchars($pdfTitle); ?></h1>
                <p class="ar-subtitle">Enter the report number and upload a PDF file</p>
            </div>
        </div>

        <?php if ($flash !== '') { ?>
            <div class="message <?php echo htmlspecialchars($flashType); ?>">
                <?php echo htmlspecialchars($flash); ?>
            </div>
        <?php } ?>

        <?php if (isset($_GET['edit']) && !$isEdit) { ?>
            <div class="message error">Entry not found.</div>
        <?php } ?>

        <div id="arMsg" class="message" style="display:none;"></div>


        <!-- ===================== ADD / EDIT FORM ===================== -->
        <div class="ar-card<?php echo $isEdit ? ' editing' : ''; ?>">

            <div class="ar-card-title">
                <?php echo $isEdit ? 'Edit ' . htmlspecialchars($pdfTitle) . ' Entry' : 'Add New ' . htmlspecialchars($pdfTitle) . ' Entry'; ?>
            </div>

            <form id="arForm" method="post" action="turf-console/<?php echo htmlspecialchars($pageFile); ?>" enctype="multipart/form-data" novalidate>

                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                <input type="hidden" name="action" value="<?php echo $isEdit ? 'update' : 'save'; ?>">
                <input type="hidden" name="page" value="<?php echo (int)$page; ?>">
                <?php if ($isEdit) { ?>
                    <input type="hidden" name="id" value="<?php echo (int)$editRow['id']; ?>">
                <?php } ?>

                <div class="ar-row">

                    <div class="ar-field">
                        <label for="arDate">Date <span class="req">*</span></label>
                        <input type="text" id="arDate" name="report_date" class="ar-input" placeholder="YYYY-MM-DD" maxlength="10" inputmode="numeric" autocomplete="off" value="<?php echo htmlspecialchars($fDate); ?>">
                        <div class="ar-hint"><span>Format: YYYY-MM-DD (e.g. 2026-10-07)</span></div>
                        <div class="ar-err" id="errDate">Please enter a valid date in YYYY-MM-DD format.</div>
                    </div>

                    <div class="ar-field">
                        <label for="arLabel">Report Number <span class="req">*</span></label>
                        <input type="text" id="arLabel" name="menu_label" class="ar-input" maxlength="50" autocomplete="off" placeholder="e.g. 112th" value="<?php echo htmlspecialchars($fLabel); ?>">
                        <div class="ar-hint"><span>Menu will show: <strong id="arPreview"></strong></span></div>
                        <div class="ar-err" id="errLabel">Please enter the report number (example: 112th).</div>
                    </div>

                </div>

                <div class="ar-field">
                    <label>PDF File <span class="req">*</span></label>

                    <?php if ($fS3Url !== '') { ?>
                        <div class="ar-current" style="margin:0 0 12px;">
                            Current file:
                            <a href="<?php echo htmlspecialchars($fS3Url); ?>" target="_blank"><?php echo htmlspecialchars(menuPdfFileLabel($fS3Url)); ?></a>
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


                <div class="ar-actions">
                    <button type="submit" class="ar-btn ar-btn-primary">
                        <i class="fas fa-save"></i> <?php echo $isEdit ? 'Update' : 'Save'; ?>
                    </button>
                    <?php if ($isEdit) { ?>
                        <a class="ar-btn ar-btn-secondary" href="<?php echo htmlspecialchars(menuPdfHtmlUrl($page)); ?>">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    <?php } else { ?>
                        <button type="button" class="ar-btn ar-btn-secondary" id="arReset">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    <?php } ?>
                </div>

            </form>

        </div>


        <!-- ===================== LIST ===================== -->
        <div class="ar-card wide">

            <div class="ar-card-title">
                All Entries <span class="ar-count">(<?php echo (int)$total; ?> total)</span>
            </div>

            <?php if (empty($rows)) { ?>

                <div class="ar-empty">No entries yet.</div>

            <?php } else { ?>

                <div class="ar-table-wrap">
                    <table class="ar-table">
                        <thead>
                            <tr>
                                <th style="width:120px;">Date</th>
                                <th>Menu Title</th>
                                <th style="width:140px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r) {
                                $rid = (int)$r['id'];
                                $isRowEditing = ($isEdit && (int)$editRow['id'] === $rid);
                                $editUrl = 'turf-console/' . $pageFile . '?edit=' . $rid . ($page > 1 ? '&page=' . $page : '');
                            ?>
                                <tr class="<?php echo $isRowEditing ? 'is-editing' : ''; ?>">
                                    <td><?php echo htmlspecialchars(date('d M Y', strtotime($r['date']))); ?></td>
                                    <td>
                                        <div class="ar-t-title"><?php echo htmlspecialchars(menuPdfMenuTitle((string)$r['label'])); ?></div>
                                        <div class="ar-t-sub">
                                            <i class="fas fa-file-pdf"></i>
                                            <span><?php echo htmlspecialchars(menuPdfFileLabel($r['file_url'])); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="ar-act">
                                            <a class="ar-icon-btn" title="View" target="_blank" href="<?php echo htmlspecialchars($r['file_url']); ?>">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a class="ar-icon-btn" title="Edit" href="<?php echo htmlspecialchars($editUrl); ?>">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <form method="post" action="turf-console/<?php echo htmlspecialchars($pageFile); ?>" class="arDelForm">
                                                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $rid; ?>">
                                                <input type="hidden" name="page" value="<?php echo (int)$page; ?>">
                                                <button type="submit" class="ar-icon-btn del" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1) {

                    $show = array();
                    for ($i = 1; $i <= $totalPages; $i++) {
                        if ($i === 1 || $i === $totalPages || abs($i - $page) <= 2) {
                            $show[] = $i;
                        }
                    }
                    $from = $offset + 1;
                    $to   = min($offset + $perPage, $total);
                ?>
                    <div class="ar-pager">
                        <div class="ar-pager-info">Showing <?php echo $from; ?>-<?php echo $to; ?> of <?php echo (int)$total; ?></div>
                        <div class="ar-pages">
                            <a class="ar-page<?php echo $page <= 1 ? ' disabled' : ''; ?>" href="<?php echo htmlspecialchars(menuPdfHtmlUrl($page - 1)); ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>

                            <?php
                            $prev = 0;
                            foreach ($show as $p) {
                                if ($prev && $p - $prev > 1) {
                                    echo '<span class="ar-page dots">...</span>';
                                }
                                $cls = ($p === $page) ? ' current' : '';
                                echo '<a class="ar-page' . $cls . '" href="' . htmlspecialchars(menuPdfHtmlUrl($p)) . '">' . $p . '</a>';
                                $prev = $p;
                            }
                            ?>

                            <a class="ar-page<?php echo $page >= $totalPages ? ' disabled' : ''; ?>" href="<?php echo htmlspecialchars(menuPdfHtmlUrl($page + 1)); ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                <?php } ?>

            <?php } ?>

        </div>

    </div>


    <script type="text/javascript">
        (function() {

            var existingFile = <?php echo json_encode($fS3Url !== ''); ?>;
            var menuTitleTpl = <?php echo json_encode($menuTitle); ?>;

            var form     = document.getElementById('arForm');
            var dateIn   = document.getElementById('arDate');
            var labelIn  = document.getElementById('arLabel');
            var preview  = document.getElementById('arPreview');
            var drop     = document.getElementById('arDrop');
            var fileIn   = document.getElementById('arFile');
            var fileBox  = document.getElementById('arFileBox');
            var fileName = document.getElementById('arFileName');
            var fileSize = document.getElementById('arFileSize');
            var fileRem  = document.getElementById('arFileRemove');
            var resetBtn = document.getElementById('arReset');
            var msgBox   = document.getElementById('arMsg');
            var msgTimer = null;

            function $(id) {
                return document.getElementById(id);
            }

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
                var e = $(errId);
                if (text) e.textContent = text;
                if (show) {
                    e.classList.add('show');
                } else {
                    e.classList.remove('show');
                }
                if (inputEl) {
                    if (show) {
                        inputEl.classList.add('invalid');
                    } else {
                        inputEl.classList.remove('invalid');
                    }
                }
            }

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

            function isValidDate(str) {
                if (!/^\d{4}-\d{2}-\d{2}$/.test(str)) return false;
                var y = parseInt(str.substr(0, 4), 10);
                var m = parseInt(str.substr(5, 2), 10);
                var d = parseInt(str.substr(8, 2), 10);
                if (y < 1900 || y > 2100) return false;
                var dt = new Date(y, m - 1, d);
                return dt.getFullYear() === y && dt.getMonth() === m - 1 && dt.getDate() === d;
            }

            // Live preview of the menu title
            function updatePreview() {
                var v = labelIn.value.trim();
                preview.textContent = menuTitleTpl.replace('{label}', v !== '' ? v : '...');
            }

            // Date auto-format while typing
            dateIn.addEventListener('input', function() {
                var digits = this.value.replace(/\D/g, '').slice(0, 8);
                var out = digits;
                if (digits.length > 6) {
                    out = digits.slice(0, 4) + '-' + digits.slice(4, 6) + '-' + digits.slice(6);
                } else if (digits.length > 4) {
                    out = digits.slice(0, 4) + '-' + digits.slice(4);
                }
                this.value = out;
                if (isValidDate(out)) setErr('errDate', this, false);
            });

            // Report number: preview + clear error while typing
            labelIn.addEventListener('input', function() {
                updatePreview();
                if (this.value.trim()) setErr('errLabel', this, false);
            });

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

            // Reset (Add mode only)
            if (resetBtn) {
                resetBtn.addEventListener('click', function() {
                    form.reset();
                    clearFile();
                    updatePreview();
                    setErr('errDate', dateIn, false);
                    setErr('errLabel', labelIn, false);
                    setErr('errFile', drop, false, 'Please choose a PDF file.');
                });
            }

            // Submit: frontend validation, then normal POST
            form.addEventListener('submit', function(e) {
                var ok = true;

                if (!isValidDate(dateIn.value)) {
                    setErr('errDate', dateIn, true);
                    ok = false;
                } else {
                    setErr('errDate', dateIn, false);
                }

                if (!labelIn.value.trim()) {
                    setErr('errLabel', labelIn, true);
                    ok = false;
                } else {
                    setErr('errLabel', labelIn, false);
                }

                var hasNew = fileIn.files && fileIn.files.length;
                if (!hasNew && !existingFile) {
                    setErr('errFile', drop, true, 'Please choose a PDF file.');
                    ok = false;
                } else if (hasNew && !isPdf(fileIn.files[0])) {
                    setErr('errFile', drop, true, 'Only PDF files are allowed.');
                    ok = false;
                } else {
                    setErr('errFile', drop, false);
                }

                if (!ok) {
                    e.preventDefault();
                    showMsg('Please fill all required fields correctly.', 'error');
                }
            });

            // Delete confirm
            var delForms = document.querySelectorAll('.arDelForm');
            for (var i = 0; i < delForms.length; i++) {
                delForms[i].addEventListener('submit', function(e) {
                    if (!confirm('Are you sure you want to delete this entry? This cannot be undone (the PDF will also be deleted).')) {
                        e.preventDefault();
                    }
                });
            }

            // initial state (in edit mode the fields are pre-filled)
            updatePreview();

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