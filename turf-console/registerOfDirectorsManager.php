<?php

include_once('../bootstrap.php');
require_once("../lib/users.class.php");
require_once("../lib/userchecks.php");
require_once("../lib/permissions.php");

session_start();

$userObj = new Users($db);

// ---------------------------------------------------------------
// SETTINGS
// ---------------------------------------------------------------
$perPage = 6;                                   // records per page
$newsDir = dirname(__DIR__) . '/news/';         // upload folder

// ---------------------------------------------------------------
// LOGIN + MODULE ACCESS CHECK
// ---------------------------------------------------------------
$secmsg = '';
if (!isAdminlogin()) {
    $secmsg = "You do not have access to this page.";
} elseif (!hasModuleAccess('register_of_directors')) {
    $secmsg = "You do not have access to this module.";
}

// CSRF token
if (empty($_SESSION['directors_csrf'])) {
    $_SESSION['directors_csrf'] = bin2hex(openssl_random_pseudo_bytes(16));
}
$csrf = $_SESSION['directors_csrf'];

// FLASH MESSAGE
$flash = '';
$flashType = '';
if (isset($_SESSION['directors_flash'])) {
    $flash = $_SESSION['directors_flash']['text'];
    $flashType = $_SESSION['directors_flash']['type'];
    unset($_SESSION['directors_flash']);
}

// ---------------------------------------------------------------
// HELPERS
// ---------------------------------------------------------------
function directorsSetFlash($text, $type)
{
    $_SESSION['directors_flash'] = array('text' => $text, 'type' => $type);
}

function directorsDeleteFile($dir, $name)
{
    $name = basename((string)$name);
    if ($name !== '' && is_file($dir . $name)) {
        @unlink($dir . $name);
    }
}

// Handles the upload. Returns the saved file name on success, or '' and sets $err on failure.
function directorsUploadFile($dir, &$err)
{
    if (!isset($_FILES['report_file']) || $_FILES['report_file']['error'] !== UPLOAD_ERR_OK) {
        $err = 'File upload failed. Please try again (check file size limit).';
        return '';
    }

    $orig = basename($_FILES['report_file']['name']);
    $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    $blocked = array('php', 'php3', 'php4', 'php5', 'phtml', 'phar', 'exe', 'sh', 'bat', 'cgi', 'pl', 'htaccess');

    if (in_array($ext, $blocked, true)) {
        $err = 'This file type is not allowed.';
        return '';
    }

    $clean = preg_replace('/[^A-Za-z0-9._-]/', '_', $orig);
    $saved = time() . '_' . $clean;

    if (!move_uploaded_file($_FILES['report_file']['tmp_name'], $dir . $saved)) {
        $err = 'Could not save file on server (check news/ folder permission).';
        return '';
    }

    return $saved;
}

// URL builder for pagination
function directorsPageUrl($p)
{
    return 'registerOfDirectorsManager.php' . ($p > 1 ? '?page=' . (int)$p : '');
}

// URL for HTML links (the page uses <base href>, so the folder prefix is needed)
function directorsHtmlUrl($p)
{
    return 'turf-console/' . directorsPageUrl($p);
}

// ---------------------------------------------------------------
// POST ACTIONS (save / update / delete)
// ---------------------------------------------------------------
if (empty($secmsg) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $action   = isset($_POST['action']) ? $_POST['action'] : '';
    $pageNo   = isset($_POST['page']) ? max(1, (int)$_POST['page']) : 1;
    $redirect = directorsPageUrl($pageNo);

    // CSRF check
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['directors_csrf'], (string)$_POST['csrf'])) {
        directorsSetFlash('Invalid request. Please try again.', 'error');
        header('Location: registerOfDirectorsManager.php');
        exit;
    }

    // ---------------- DELETE ----------------
    if ($action === 'delete') {

        $id  = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $row = $id > 0 ? $db->getSingleRowAssoc("SELECT * FROM register_of_directors WHERE id = $id") : null;

        if ($row) {
            try {
                $db->query("DELETE FROM register_of_directors WHERE id = $id");
                if ($row['type'] === 'file') {
                    directorsDeleteFile($newsDir, $row['file_name']);
                }
                directorsSetFlash('Entry deleted successfully.', 'success');
            } catch (Exception $e) {
                directorsSetFlash('Database error. Could not delete.', 'error');
            }
        } else {
            directorsSetFlash('Entry not found.', 'error');
        }

        // if the current page becomes empty, go back one page
        $total    = (int)$db->getSingleValue("SELECT COUNT(*) FROM register_of_directors");
        $lastPage = max(1, (int)ceil($total / $perPage));
        if ($pageNo > $lastPage) {
            $pageNo = $lastPage;
        }
        header('Location: ' . directorsPageUrl($pageNo));
        exit;
    }

    // ---------------- SAVE / UPDATE ----------------
    if ($action === 'save' || $action === 'update') {

        $date   = isset($_POST['report_date']) ? trim($_POST['report_date']) : '';
        $title  = isset($_POST['report_title']) ? trim($_POST['report_title']) : '';
        $type   = isset($_POST['report_type']) ? $_POST['report_type'] : '';
        $text   = isset($_POST['report_text']) ? trim($_POST['report_text']) : '';
        $id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $err    = '';
        $oldRow = null;

        if ($action === 'update') {
            $oldRow = $id > 0 ? $db->getSingleRowAssoc("SELECT * FROM register_of_directors WHERE id = $id") : null;
            if (!$oldRow) {
                $err = 'Entry not found.';
            }
        }

        $d = DateTime::createFromFormat('Y-m-d', $date);
        if ($err !== '') {
            // already set
        } elseif (!$d || $d->format('Y-m-d') !== $date) {
            $err = 'Invalid date.';
        } elseif ($title === '') {
            $err = 'Title is required.';
        } elseif ($type !== 'text' && $type !== 'file') {
            $err = 'Invalid type.';
        } elseif ($type === 'text' && $text === '') {
            $err = 'Text is required.';
        }

        $newFile      = '';   // newly uploaded file
        $fileToKeep   = '';   // old file to keep
        $fileToDelete = '';   // old file to delete

        if ($err === '' && $type === 'file') {

            $hasUpload = isset($_FILES['report_file']) && $_FILES['report_file']['error'] !== UPLOAD_ERR_NO_FILE;
            $oldIsFile = ($oldRow && $oldRow['type'] === 'file' && $oldRow['file_name'] !== '');

            if ($hasUpload) {
                $newFile = directorsUploadFile($newsDir, $err);
                if ($err === '' && $oldIsFile) {
                    $fileToDelete = $oldRow['file_name'];
                }
            } elseif ($oldIsFile) {
                $fileToKeep = $oldRow['file_name'];
            } else {
                $err = 'Please choose a file.';
            }
        }

        if ($err === '') {

            $finalFile = ($type === 'file') ? ($newFile !== '' ? $newFile : $fileToKeep) : '';

            // if switched to text, remove the old file
            if ($type === 'text' && $oldRow && $oldRow['type'] === 'file') {
                $fileToDelete = $oldRow['file_name'];
            }

            $textSql = ($type === 'text') ? "'" . $db->escape($text) . "'" : "NULL";
            $fileSql = ($type === 'file') ? "'" . $db->escape($finalFile) . "'" : "NULL";

            try {
                if ($action === 'update') {
                    $db->update("UPDATE register_of_directors SET
                                    report_date = '" . $db->escape($date) . "',
                                    title = '" . $db->escape($title) . "',
                                    type = '" . $db->escape($type) . "',
                                    content_text = $textSql,
                                    file_name = $fileSql
                                 WHERE id = $id");
                    directorsSetFlash('Entry updated successfully.', 'success');
                } else {
                    $db->insert("INSERT INTO register_of_directors (report_date, title, type, content_text, file_name)
                                 VALUES ('" . $db->escape($date) . "',
                                         '" . $db->escape($title) . "',
                                         '" . $db->escape($type) . "',
                                         $textSql,
                                         $fileSql)");
                    directorsSetFlash('Saved successfully.', 'success');
                    $redirect = 'registerOfDirectorsManager.php';   // new entry -> page 1
                }

                if ($fileToDelete !== '') {
                    directorsDeleteFile($newsDir, $fileToDelete);
                }
            } catch (Exception $e) {
                // if the DB save fails, remove the newly uploaded file
                if ($newFile !== '') {
                    directorsDeleteFile($newsDir, $newFile);
                }
                directorsSetFlash('Database error. Could not save.', 'error');
            }
        } else {
            directorsSetFlash($err, 'error');
            if ($action === 'update' && $id > 0) {
                $redirect = 'registerOfDirectorsManager.php?edit=' . $id . ($pageNo > 1 ? '&page=' . $pageNo : '');
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
        $editRow = $db->getSingleRowAssoc("SELECT * FROM register_of_directors WHERE id = $eid");
    }

    $total      = (int)$db->getSingleValue("SELECT COUNT(*) FROM register_of_directors");
    $totalPages = max(1, (int)ceil($total / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;

    $rows = $db->getMultiDimensionalArray(
        "SELECT id, report_date, title, type, content_text, file_name
         FROM register_of_directors
         ORDER BY report_date DESC, id DESC
         LIMIT $perPage OFFSET $offset"
    );
}

$isEdit = !empty($editRow);

// default form values
$fDate  = $isEdit ? $editRow['report_date'] : '';
$fTitle = $isEdit ? $editRow['title'] : '';
$fType  = $isEdit ? $editRow['type'] : '';
$fText  = $isEdit ? (string)$editRow['content_text'] : '';
$fFile  = ($isEdit && $editRow['type'] === 'file') ? (string)$editRow['file_name'] : '';

// PAGE TITLE
$pageTitle = 'Register of Directors';

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
    .ar-input, .ar-select, .ar-textarea { width: 100%; box-sizing: border-box; padding: 10px 12px; font-size: 15px; font-family: inherit; color: #2b332f; background: #fff; border: 1px solid #d3d9d6; border-radius: 8px; outline: none; transition: border-color .15s ease, box-shadow .15s ease; }
    .ar-input::placeholder, .ar-textarea::placeholder { color: #9aa9a2; }
    .ar-input:focus, .ar-select:focus, .ar-textarea:focus { border-color: #1a7a45; box-shadow: 0 0 0 3px rgba(26, 122, 69, 0.15); }
    .ar-textarea { min-height: 220px; resize: vertical; line-height: 1.5; }
    .ar-select { cursor: pointer; appearance: none; -webkit-appearance: none; background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' fill='none' stroke='%237a8c84' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 14px center; padding-right: 38px; }
    .ar-hint { font-size: 12px; color: #7a8c84; margin-top: 6px; display: flex; justify-content: space-between; gap: 10px; }
    .ar-input.invalid, .ar-select.invalid, .ar-textarea.invalid, .ar-drop.invalid { border-color: #c0392b; }
    .ar-err { color: #c0392b; font-size: 12px; margin-top: 5px; display: none; }
    .ar-err.show { display: block; }

    .ar-section { display: none; }
    .ar-section.active { display: block; animation: arFade .2s ease; }
    @keyframes arFade { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: none; } }

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
    .ar-t-sub { font-size: 12px; color: #7a8c84; margin-top: 3px; word-break: break-word; }
    .ar-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 20px; white-space: nowrap; }
    .ar-badge.text { background: #e8f0fb; color: #1f4f9c; }
    .ar-badge.file { background: #fdf0e0; color: #9a5a0c; }
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
        .ar-textarea { min-height: 180px; }
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
                <h1 class="ar-title">REGISTER OF DIRECTORS</h1>
                <p class="ar-subtitle">Upload a file or write text</p>
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
                <?php echo $isEdit ? 'Edit Register of Directors Entry' : 'Add New Register of Directors Entry'; ?>
            </div>

            <form id="arForm" method="post" action="turf-console/registerOfDirectorsManager.php" enctype="multipart/form-data" novalidate>

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
                        <label for="arTitle">Title <span class="req">*</span></label>
                        <input type="text" id="arTitle" name="report_title" class="ar-input" maxlength="200" placeholder="Enter title" value="<?php echo htmlspecialchars($fTitle); ?>">
                        <div class="ar-err" id="errTitle">Please enter a title.</div>
                    </div>

                </div>

                <div class="ar-field">
                    <label for="arType">Type <span class="req">*</span></label>
                    <select id="arType" name="report_type" class="ar-select">
                        <option value="">-- Select --</option>
                        <option value="text" <?php echo $fType === 'text' ? 'selected' : ''; ?>>Write Text</option>
                        <option value="file" <?php echo $fType === 'file' ? 'selected' : ''; ?>>Upload File</option>
                    </select>
                    <div class="ar-err" id="errType">Please select a type.</div>
                </div>


                <!-- TEXT SECTION -->
                <div class="ar-section" id="secText">
                    <div class="ar-field">
                        <label for="arText">Text <span class="req">*</span></label>
                        <textarea id="arText" name="report_text" class="ar-textarea" placeholder="Write your text here..."><?php echo htmlspecialchars($fText); ?></textarea>
                        <div class="ar-hint"><span>Plain text / HTML allowed</span><span id="arCount">0 characters</span></div>
                        <div class="ar-err" id="errText">Please write some text.</div>
                    </div>
                </div>


                <!-- FILE SECTION -->
                <div class="ar-section" id="secFile">
                    <div class="ar-field">
                        <label>Upload File <span class="req">*</span></label>

                        <?php if ($fFile !== '') { ?>
                            <div class="ar-current" style="margin:0 0 12px;">
                                Current file:
                                <a href="news/<?php echo rawurlencode($fFile); ?>" target="_blank"><?php echo htmlspecialchars($fFile); ?></a>
                                <br><span style="color:#7a8c84;">If you choose a new file, it will replace this one. Otherwise the current file is kept.</span>
                            </div>
                        <?php } ?>

                        <div class="ar-drop" id="arDrop">
                            <div class="ar-drop-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                            <p class="ar-drop-text">Drag &amp; drop file here or <span>browse</span></p>
                            <p class="ar-drop-sub">HTML, DOC, DOCX, PDF, ZIP, images, etc.</p>
                        </div>

                        <input type="file" id="arFile" name="report_file" class="ar-file-input">

                        <div class="ar-file-box" id="arFileBox">
                            <div class="ar-file-ico"><i class="fas fa-file" id="arFileIcon"></i></div>
                            <div class="ar-file-info">
                                <div class="ar-file-name" id="arFileName"></div>
                                <div class="ar-file-size" id="arFileSize"></div>
                            </div>
                            <button type="button" class="ar-file-remove" id="arFileRemove" title="Remove file">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        <div class="ar-err" id="errFile">Please choose a file.</div>
                    </div>
                </div>


                <div class="ar-actions">
                    <button type="submit" class="ar-btn ar-btn-primary">
                        <i class="fas fa-save"></i> <?php echo $isEdit ? 'Update' : 'Save'; ?>
                    </button>
                    <?php if ($isEdit) { ?>
                        <a class="ar-btn ar-btn-secondary" href="<?php echo htmlspecialchars(directorsHtmlUrl($page)); ?>">
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
                                <th style="width:110px;">Date</th>
                                <th>Title</th>
                                <th style="width:100px;">Type</th>
                                <th style="width:120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r) {
                                $rid = (int)$r['id'];
                                $isRowEditing = ($isEdit && (int)$editRow['id'] === $rid);
                                $editUrl = 'turf-console/registerOfDirectorsManager.php?edit=' . $rid . ($page > 1 ? '&page=' . $page : '');

                                if ($r['type'] === 'file') {
                                    $sub = (string)$r['file_name'];
                                } else {
                                    $plain = trim(strip_tags((string)$r['content_text']));
                                    $sub = (function_exists('mb_substr') ? mb_substr($plain, 0, 90) : substr($plain, 0, 90)) . (strlen($plain) > 90 ? '...' : '');
                                }
                            ?>
                                <tr class="<?php echo $isRowEditing ? 'is-editing' : ''; ?>">
                                    <td><?php echo htmlspecialchars(date('d M Y', strtotime($r['report_date']))); ?></td>
                                    <td>
                                        <div class="ar-t-title"><?php echo htmlspecialchars($r['title']); ?></div>
                                        <?php if ($sub !== '') { ?>
                                            <div class="ar-t-sub"><?php echo htmlspecialchars($sub); ?></div>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php if ($r['type'] === 'file') { ?>
                                            <span class="ar-badge file"><i class="fas fa-file"></i> File</span>
                                        <?php } else { ?>
                                            <span class="ar-badge text"><i class="fas fa-align-left"></i> Text</span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <div class="ar-act">
                                            <?php if ($r['type'] === 'file' && $r['file_name'] !== '') { ?>
                                                <a class="ar-icon-btn" title="Open file" target="_blank" href="news/<?php echo rawurlencode($r['file_name']); ?>">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            <?php } ?>
                                            <a class="ar-icon-btn" title="Edit" href="<?php echo htmlspecialchars($editUrl); ?>">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <form method="post" action="turf-console/registerOfDirectorsManager.php" class="arDelForm">
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
                            <a class="ar-page<?php echo $page <= 1 ? ' disabled' : ''; ?>" href="<?php echo htmlspecialchars(directorsHtmlUrl($page - 1)); ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>

                            <?php
                            $prev = 0;
                            foreach ($show as $p) {
                                if ($prev && $p - $prev > 1) {
                                    echo '<span class="ar-page dots">...</span>';
                                }
                                $cls = ($p === $page) ? ' current' : '';
                                echo '<a class="ar-page' . $cls . '" href="' . htmlspecialchars(directorsHtmlUrl($p)) . '">' . $p . '</a>';
                                $prev = $p;
                            }
                            ?>

                            <a class="ar-page<?php echo $page >= $totalPages ? ' disabled' : ''; ?>" href="<?php echo htmlspecialchars(directorsHtmlUrl($page + 1)); ?>">
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

            var existingFile = <?php echo json_encode($fFile !== ''); ?>;

            var form     = document.getElementById('arForm');
            var dateIn   = document.getElementById('arDate');
            var titleIn  = document.getElementById('arTitle');
            var typeSel  = document.getElementById('arType');
            var secText  = document.getElementById('secText');
            var secFile  = document.getElementById('secFile');
            var txtArea  = document.getElementById('arText');
            var counter  = document.getElementById('arCount');
            var drop     = document.getElementById('arDrop');
            var fileIn   = document.getElementById('arFile');
            var fileBox  = document.getElementById('arFileBox');
            var fileName = document.getElementById('arFileName');
            var fileSize = document.getElementById('arFileSize');
            var fileIcon = document.getElementById('arFileIcon');
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

            function setErr(errId, inputEl, show) {
                var e = $(errId);
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

            function iconFor(name) {
                var ext = (name.split('.').pop() || '').toLowerCase();
                if (ext === 'pdf') return 'fas fa-file-pdf';
                if (ext === 'doc' || ext === 'docx') return 'fas fa-file-word';
                if (ext === 'xls' || ext === 'xlsx' || ext === 'csv') return 'fas fa-file-excel';
                if (ext === 'zip' || ext === 'rar' || ext === '7z') return 'fas fa-file-archive';
                if (ext === 'htm' || ext === 'html') return 'fas fa-file-code';
                if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].indexOf(ext) !== -1) return 'fas fa-file-image';
                return 'fas fa-file';
            }

            function showFile(file) {
                fileName.textContent = file.name;
                fileSize.textContent = formatSize(file.size);
                fileIcon.className = iconFor(file.name);
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

            function updateCount() {
                counter.textContent = txtArea.value.length + ' characters';
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

            // Type dropdown
            function applyType() {
                var v = typeSel.value;
                secText.classList.toggle('active', v === 'text');
                secFile.classList.toggle('active', v === 'file');
                setErr('errText', txtArea, false);
                setErr('errFile', drop, false);
                if (v) setErr('errType', typeSel, false);
            }
            typeSel.addEventListener('change', applyType);

            // Text counter
            txtArea.addEventListener('input', function() {
                updateCount();
                if (txtArea.value.trim()) setErr('errText', txtArea, false);
            });

            // File: click and drag/drop
            drop.addEventListener('click', function() {
                fileIn.click();
            });

            fileIn.addEventListener('change', function() {
                if (fileIn.files && fileIn.files[0]) {
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
                    try {
                        fileIn.files = e.dataTransfer.files;
                    } catch (err) {}
                    showFile(e.dataTransfer.files[0]);
                }
            });

            fileRem.addEventListener('click', clearFile);

            titleIn.addEventListener('input', function() {
                if (this.value.trim()) setErr('errTitle', this, false);
            });

            // Reset (Add mode only)
            if (resetBtn) {
                resetBtn.addEventListener('click', function() {
                    form.reset();
                    clearFile();
                    updateCount();
                    applyType();
                    ['errDate', 'errTitle', 'errType', 'errText', 'errFile'].forEach(function(id) {
                        setErr(id, null, false);
                    });
                    [dateIn, titleIn, typeSel, txtArea, drop].forEach(function(el) {
                        el.classList.remove('invalid');
                    });
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
                if (!titleIn.value.trim()) {
                    setErr('errTitle', titleIn, true);
                    ok = false;
                } else {
                    setErr('errTitle', titleIn, false);
                }
                if (!typeSel.value) {
                    setErr('errType', typeSel, true);
                    ok = false;
                } else {
                    setErr('errType', typeSel, false);
                }

                if (typeSel.value === 'text') {
                    if (!txtArea.value.trim()) {
                        setErr('errText', txtArea, true);
                        ok = false;
                    } else {
                        setErr('errText', txtArea, false);
                    }
                }
                if (typeSel.value === 'file') {
                    var hasNew = fileIn.files && fileIn.files.length;
                    if (!hasNew && !existingFile) {
                        setErr('errFile', drop, true);
                        ok = false;
                    } else {
                        setErr('errFile', drop, false);
                    }
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
                    if (!confirm('Are you sure you want to delete this entry? This cannot be undone (the file will also be deleted).')) {
                        e.preventDefault();
                    }
                });
            }

            // initial state (in edit mode the fields are pre-filled)
            updateCount();
            applyType();

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