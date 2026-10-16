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
$perPage = 6;

define('DN_DEFAULT_COL1', 'Odds on withdrawn Horse/s at the time of Withdrawal');
define('DN_DEFAULT_COL2', 'Percentage of deductions');

// ---------------------------------------------------------------
// LOGIN + MODULE ACCESS CHECK
// ---------------------------------------------------------------
$secmsg = '';
if (!isAdminlogin()) {
    $secmsg = "You do not have access to this page.";
} elseif (!hasModuleAccess('deduction_norms_docs')) {
    $secmsg = "You do not have access to this module.";
}

// CSRF token
if (empty($_SESSION['dn_csrf'])) {
    $_SESSION['dn_csrf'] = bin2hex(openssl_random_pseudo_bytes(16));
}
$csrf = $_SESSION['dn_csrf'];

// FLASH MESSAGE
$flash = '';
$flashType = '';
if (isset($_SESSION['dn_flash'])) {
    $flash = $_SESSION['dn_flash']['text'];
    $flashType = $_SESSION['dn_flash']['type'];
    unset($_SESSION['dn_flash']);
}

// ---------------------------------------------------------------
// HELPERS
// ---------------------------------------------------------------
function dnSetFlash($text, $type)
{
    $_SESSION['dn_flash'] = array('text' => $text, 'type' => $type);
}

function dnPageUrl($p)
{
    return 'deductionNormsManager.php' . ($p > 1 ? '?page=' . (int)$p : '');
}

function dnHtmlUrl($p)
{
    return 'turf-console/' . dnPageUrl($p);
}

// Trims a value, normalizes line endings and limits its length.
function dnCleanStr($v, $max)
{
    $v = is_string($v) ? trim($v) : '';
    $v = str_replace(array("\r\n", "\r"), "\n", $v);
    if (function_exists('mb_substr')) {
        $v = mb_substr($v, 0, $max, 'UTF-8');
    } else {
        $v = substr($v, 0, $max);
    }
    return $v;
}

// Decodes and cleans the tables JSON. Returns a clean array of sections.
function dnCleanTables($json)
{
    $out  = array();
    $data = json_decode((string)$json, true);

    if (!is_array($data)) {
        return $out;
    }

    foreach ($data as $sec) {
        if (!is_array($sec)) {
            continue;
        }
        if (count($out) >= 20) {
            break;
        }

        $heading = dnCleanStr(isset($sec['heading']) ? $sec['heading'] : '', 300);
        $text    = dnCleanStr(isset($sec['text']) ? $sec['text'] : '', 5000);
        $col1    = dnCleanStr(isset($sec['col1']) ? $sec['col1'] : '', 300);
        $col2    = dnCleanStr(isset($sec['col2']) ? $sec['col2'] : '', 300);
        $rows    = array();

        if (isset($sec['rows']) && is_array($sec['rows'])) {
            foreach ($sec['rows'] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                if (count($rows) >= 200) {
                    break;
                }
                $c1 = dnCleanStr(isset($row['c1']) ? $row['c1'] : '', 500);
                $c2 = dnCleanStr(isset($row['c2']) ? $row['c2'] : '', 200);
                if ($c1 === '' && $c2 === '') {
                    continue;
                }
                $rows[] = array('c1' => $c1, 'c2' => $c2);
            }
        }

        // skip completely empty tables
        if ($heading === '' && $text === '' && count($rows) === 0) {
            continue;
        }

        if ($col1 === '') {
            $col1 = DN_DEFAULT_COL1;
        }
        if ($col2 === '') {
            $col2 = DN_DEFAULT_COL2;
        }

        $out[] = array(
            'heading' => $heading,
            'text'    => $text,
            'col1'    => $col1,
            'col2'    => $col2,
            'rows'    => $rows
        );
    }

    return $out;
}

// ---------------------------------------------------------------
// POST ACTIONS (save / update / delete)
// ---------------------------------------------------------------
if (empty($secmsg) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $action   = isset($_POST['action']) ? $_POST['action'] : '';
    $pageNo   = isset($_POST['page']) ? max(1, (int)$_POST['page']) : 1;
    $redirect = dnPageUrl($pageNo);

    // CSRF check
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['dn_csrf'], (string)$_POST['csrf'])) {
        dnSetFlash('Invalid request. Please try again.', 'error');
        header('Location: deductionNormsManager.php');
        exit;
    }

    // ---------------- DELETE ----------------
    if ($action === 'delete') {

        $id  = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $row = $id > 0 ? $db->getSingleRowAssoc("SELECT id FROM deduction_norms_docs WHERE id = $id") : null;

        if ($row) {
            try {
                $db->query("DELETE FROM deduction_norms_docs WHERE id = $id");
                dnSetFlash('Entry deleted successfully.', 'success');
            } catch (Exception $e) {
                error_log($e->getMessage());
                dnSetFlash('Database error. Could not delete.', 'error');
            }
        } else {
            dnSetFlash('Entry not found.', 'error');
        }

        $total    = (int)$db->getSingleValue("SELECT COUNT(*) FROM deduction_norms_docs");
        $lastPage = max(1, (int)ceil($total / $perPage));
        if ($pageNo > $lastPage) {
            $pageNo = $lastPage;
        }
        header('Location: ' . dnPageUrl($pageNo));
        exit;
    }

    // ---------------- SAVE / UPDATE ----------------
    if ($action === 'save' || $action === 'update') {

        $date   = isset($_POST['report_date']) ? trim($_POST['report_date']) : '';
        $title  = dnCleanStr(isset($_POST['report_title']) ? $_POST['report_title'] : '', 200);
        $header = dnCleanStr(isset($_POST['report_header']) ? $_POST['report_header'] : '', 500);
        $text   = dnCleanStr(isset($_POST['report_text']) ? $_POST['report_text'] : '', 200000);
        $tables = dnCleanTables(isset($_POST['tables_json']) ? $_POST['tables_json'] : '');
        $id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $err    = '';

        $tablesJson = '';
        if (count($tables) > 0) {
            $tablesJson = json_encode($tables);
            if ($tablesJson === false || $tablesJson === null) {
                $err = 'Invalid table data.';
            }
        }

        if ($err === '' && $action === 'update') {
            $oldRow = $id > 0 ? $db->getSingleRowAssoc("SELECT id FROM deduction_norms_docs WHERE id = $id") : null;
            if (!$oldRow) {
                $err = 'Entry not found.';
            }
        }

        $d = DateTime::createFromFormat('Y-m-d', $date);
        if ($err !== '') {
            // already set
        } elseif (!$d || $d->format('Y-m-d') !== $date) {
            $err = 'Please select a valid date.';
        } elseif ($header === '' && count($tables) === 0 && $text === '') {
            $err = 'Please add a header, a table or some text.';
        }

        if ($err === '') {

            // Optional fields: NULL is stored when they are left blank
            $titleSql  = ($title !== '') ? "'" . $db->escape($title) . "'" : "NULL";
            $headerSql = ($header !== '') ? "'" . $db->escape($header) . "'" : "NULL";
            $tablesSql = ($tablesJson !== '') ? "'" . $db->escape($tablesJson) . "'" : "NULL";
            $textSql   = ($text !== '') ? "'" . $db->escape($text) . "'" : "NULL";

            try {
                if ($action === 'update') {
                    $db->update("UPDATE deduction_norms_docs SET
                                    report_date = '" . $db->escape($date) . "',
                                    title = $titleSql,
                                    header_text = $headerSql,
                                    tables_json = $tablesSql,
                                    content_text = $textSql
                                 WHERE id = $id");
                    dnSetFlash('Entry updated successfully.', 'success');
                } else {
                    $db->insert("INSERT INTO deduction_norms_docs (report_date, title, header_text, tables_json, content_text)
                                 VALUES ('" . $db->escape($date) . "',
                                         $titleSql,
                                         $headerSql,
                                         $tablesSql,
                                         $textSql)");
                    dnSetFlash('Saved successfully.', 'success');
                    $redirect = 'deductionNormsManager.php';
                }
            } catch (Exception $e) {
                error_log($e->getMessage());
                dnSetFlash('Database error. Could not save.', 'error');
            }
        } else {
            dnSetFlash($err, 'error');
            if ($action === 'update' && $id > 0) {
                $redirect = 'deductionNormsManager.php?edit=' . $id . ($pageNo > 1 ? '&page=' . $pageNo : '');
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
        $editRow = $db->getSingleRowAssoc("SELECT * FROM deduction_norms_docs WHERE id = $eid");
    }

    $total      = (int)$db->getSingleValue("SELECT COUNT(*) FROM deduction_norms_docs");
    $totalPages = max(1, (int)ceil($total / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;

    $rows = $db->getMultiDimensionalArray(
        "SELECT id, report_date, title, header_text, tables_json, content_text
         FROM deduction_norms_docs
         ORDER BY report_date DESC, id DESC
         LIMIT $perPage OFFSET $offset"
    );
}

$isEdit = !empty($editRow);

// default form values
$fDate   = $isEdit ? $editRow['report_date'] : date('Y-m-d');
$fTitle  = $isEdit ? (string)$editRow['title'] : '';
$fHeader = $isEdit ? (string)$editRow['header_text'] : '';
$fText   = $isEdit ? (string)$editRow['content_text'] : '';

$fTables = array();
if ($isEdit && !empty($editRow['tables_json'])) {
    $fTables = dnCleanTables($editRow['tables_json']);
}
if (count($fTables) === 0) {
    $fTables = array(array(
        'heading' => '',
        'text'    => '',
        'col1'    => DN_DEFAULT_COL1,
        'col2'    => DN_DEFAULT_COL2,
        'rows'    => array(array('c1' => '', 'c2' => ''))
    ));
}

// PAGE TITLE
$pageTitle = 'Deduction Norms';

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
    .ar-field label .opt { color: #7a8c84; font-weight: 500; }
    .ar-input, .ar-textarea { width: 100%; box-sizing: border-box; padding: 10px 12px; font-size: 15px; font-family: inherit; color: #2b332f; background: #fff; border: 1px solid #d3d9d6; border-radius: 8px; outline: none; transition: border-color .15s ease, box-shadow .15s ease; }
    .ar-input::placeholder, .ar-textarea::placeholder { color: #9aa9a2; }
    .ar-input:focus, .ar-textarea:focus { border-color: #1a7a45; box-shadow: 0 0 0 3px rgba(26, 122, 69, 0.15); }
    .ar-textarea { min-height: 320px; resize: vertical; line-height: 1.5; }
    .ar-textarea.ar-textarea-sm { min-height: 90px; }
    .ar-hint { font-size: 12px; color: #7a8c84; margin-top: 6px; display: flex; justify-content: space-between; gap: 10px; }
    .ar-input.invalid, .ar-textarea.invalid { border-color: #c0392b; }
    .ar-err { color: #c0392b; font-size: 12px; margin-top: 5px; display: none; }
    .ar-err.show { display: block; }

    .ar-actions { display: flex; gap: 10px; margin-top: 8px; padding-top: 18px; border-top: 1px solid #eef1ef; flex-wrap: wrap; }
    .ar-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 11px 22px; font-size: 15px; font-weight: 600; font-family: inherit; border-radius: 8px; border: 1px solid transparent; cursor: pointer; transition: all .15s ease; text-decoration: none; }
    .ar-btn-primary { background: #0f5c33; color: #fff; }
    .ar-btn-primary:hover { background: #1a7a45; box-shadow: 0 4px 10px rgba(15, 92, 51, 0.25); color: #fff; }
    .ar-btn-secondary { background: #fff; color: #2b332f; border-color: #d3d9d6; }
    .ar-btn-secondary:hover { border-color: #7a8c84; color: #2b332f; }
    .ar-btn.ar-btn-sm { padding: 8px 14px; font-size: 13px; }

    /* ---------- TABLE BUILDER ---------- */
    .ar-sec { border: 1px solid #e2e6e4; border-radius: 12px; padding: 16px; margin-bottom: 16px; background: #f8faf9; }
    .ar-sec-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
    .ar-sec-title { font-size: 13px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; color: #0f5c33; }
    .ar-rows-label { font-size: 13px; font-weight: 600; color: #2b332f; margin-bottom: 8px; }
    .ar-trow { display: grid; grid-template-columns: 1fr 160px 34px; gap: 8px; margin-bottom: 8px; align-items: center; }
    .ar-sec-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 4px; }
    .ar-bulk { display: none; margin-top: 12px; }
    .ar-bulk.open { display: block; }
    .ar-bulk .ar-btn { margin-top: 8px; }

    .ar-table-wrap { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .ar-table { width: 100%; border-collapse: collapse; min-width: 520px; }
    .ar-table th { text-align: left; font-size: 12px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; color: #0f5c33; background: #f3f6f4; padding: 11px 12px; border-bottom: 1px solid #e2e6e4; white-space: nowrap; }
    .ar-table td { font-size: 14px; color: #2b332f; padding: 12px; border-bottom: 1px solid #eef1ef; vertical-align: middle; }
    .ar-table tr:hover td { background: #f8faf9; }
    .ar-table tr.is-editing td { background: #eef7f1; }
    .ar-t-title { font-weight: 600; word-break: break-word; }
    .ar-t-sub { font-size: 12px; color: #7a8c84; margin-top: 3px; word-break: break-word; }
    .ar-act { display: flex; gap: 6px; flex-wrap: nowrap; }
    .ar-icon-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #d3d9d6; background: #fff; color: #2b332f; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; font-size: 13px; text-decoration: none; transition: all .15s ease; padding: 0; }
    .ar-icon-btn:hover { border-color: #1a7a45; color: #0f5c33; background: #eef7f1; }
    .ar-icon-btn.del:hover { border-color: #c0392b; color: #a12626; background: #fde8e8; }
    .ar-act form { margin: 0; }
    .ar-empty { text-align: center; padding: 30px 10px; color: #7a8c84; font-size: 14px; }
    .ar-live { display: inline-block; margin-left: 8px; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 20px; background: #e3f6ea; color: #0f5c33; vertical-align: middle; }

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
    }
    @media (max-width: 560px) {
        .ar-header { flex-direction: column; align-items: flex-start; margin-bottom: 14px; gap: 8px; }
        .ar-actions { flex-direction: column; }
        .ar-btn { width: 100%; }
        .ar-textarea { min-height: 240px; }
        .ar-trow { grid-template-columns: 1fr 90px 34px; }
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
                <h1 class="ar-title">DEDUCTION NORMS</h1>
                <p class="ar-subtitle">Latest entry (by date) is shown on the website</p>
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
                <?php echo $isEdit ? 'Edit Deduction Norms Entry' : 'Add New Deduction Norms Entry'; ?>
            </div>

            <form id="arForm" method="post" action="turf-console/deductionNormsManager.php" novalidate>

                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                <input type="hidden" name="action" value="<?php echo $isEdit ? 'update' : 'save'; ?>">
                <input type="hidden" name="page" value="<?php echo (int)$page; ?>">
                <input type="hidden" name="tables_json" id="arTablesJson" value="">
                <?php if ($isEdit) { ?>
                    <input type="hidden" name="id" value="<?php echo (int)$editRow['id']; ?>">
                <?php } ?>

                <div class="ar-row">

                    <div class="ar-field">
                        <label for="arDate">Date <span class="req">*</span></label>
                        <input type="date" id="arDate" name="report_date" class="ar-input" value="<?php echo htmlspecialchars($fDate); ?>">
                        <div class="ar-err" id="errDate">Please select a date.</div>
                    </div>

                    <div class="ar-field">
                        <label for="arTitle">Page Title <span class="opt">(optional)</span></label>
                        <input type="text" id="arTitle" name="report_title" class="ar-input" maxlength="200" placeholder="Default: Deduction Norms" value="<?php echo htmlspecialchars($fTitle); ?>">
                    </div>

                </div>

                <div class="ar-field">
                    <label for="arHeader">Header <span class="opt">(optional)</span></label>
                    <input type="text" id="arHeader" name="report_header" class="ar-input" maxlength="500" placeholder="e.g. Revised rules for deduction made by bookmakers/Fixed Odds Betting for withdrawn horses" value="<?php echo htmlspecialchars($fHeader); ?>">
                    <div class="ar-hint"><span>Main heading shown above the tables.</span></div>
                </div>

                <div class="ar-field">
                    <label>Tables <span class="opt">(optional)</span></label>
                    <div class="ar-hint" style="margin:0 0 10px;"><span>Each table has a heading, a text, two column headings and rows. Use "Add another table" for (B), (C) and so on.</span></div>
                    <div id="arSections"></div>
                    <button type="button" class="ar-btn ar-btn-secondary" id="arAddSection">
                        <i class="fas fa-plus"></i> Add another table
                    </button>
                </div>

                <div class="ar-field">
                    <label for="arText">Text below the tables <span class="opt">(optional)</span></label>
                    <textarea id="arText" name="report_text" class="ar-textarea" placeholder="Write the text for (C), (D) ... here"><?php echo htmlspecialchars($fText); ?></textarea>
                    <div class="ar-hint"><span>Plain text. Use "## " for an underlined sub-heading, "# " for a main heading, **text** for bold, "1. " for numbered lines, "- " for bullet lines and "&gt;&gt; text" for the bold sign-off line. Leave one blank line between blocks.</span><span id="arCount">0 characters</span></div>
                    <div class="ar-err" id="errContent">Please add a header, a table or some text.</div>
                </div>

                <div class="ar-actions">
                    <button type="submit" class="ar-btn ar-btn-primary">
                        <i class="fas fa-save"></i> <?php echo $isEdit ? 'Update' : 'Save'; ?>
                    </button>
                    <?php if ($isEdit) { ?>
                        <a class="ar-btn ar-btn-secondary" href="<?php echo htmlspecialchars(dnHtmlUrl($page)); ?>">
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
                                <th>Title / Header</th>
                                <th style="width:100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $idx => $r) {
                                $rid = (int)$r['id'];
                                $isRowEditing = ($isEdit && (int)$editRow['id'] === $rid);
                                $editUrl = 'turf-console/deductionNormsManager.php?edit=' . $rid . ($page > 1 ? '&page=' . $page : '');

                                $titleTxt  = trim((string)$r['title']);
                                $headerTxt = trim((string)$r['header_text']);
                                $plain     = trim(strip_tags((string)$r['content_text']));
                                $snip      = (function_exists('mb_substr') ? mb_substr($plain, 0, 90) : substr($plain, 0, 90)) . (strlen($plain) > 90 ? '...' : '');

                                $tArr   = json_decode((string)$r['tables_json'], true);
                                $tCount = is_array($tArr) ? count($tArr) : 0;

                                $main = $titleTxt !== '' ? $titleTxt : ($headerTxt !== '' ? $headerTxt : ($snip !== '' ? $snip : 'Deduction Norms'));

                                $subParts = array();
                                if ($titleTxt !== '' && $headerTxt !== '') {
                                    $subParts[] = $headerTxt;
                                }
                                if ($tCount > 0) {
                                    $subParts[] = $tCount . ($tCount === 1 ? ' table' : ' tables');
                                }
                                if ($snip !== '' && $snip !== $main) {
                                    $subParts[] = $snip;
                                }
                                $sub = implode('  |  ', $subParts);

                                $isLive = ($page === 1 && $idx === 0);
                            ?>
                                <tr class="<?php echo $isRowEditing ? 'is-editing' : ''; ?>">
                                    <td><?php echo htmlspecialchars(date('d M Y', strtotime($r['report_date']))); ?></td>
                                    <td>
                                        <div class="ar-t-title"><?php echo htmlspecialchars($main); ?><?php if ($isLive) { ?><span class="ar-live">LIVE</span><?php } ?></div>
                                        <?php if ($sub !== '') { ?>
                                            <div class="ar-t-sub"><?php echo htmlspecialchars($sub); ?></div>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <div class="ar-act">
                                            <a class="ar-icon-btn" title="Edit" href="<?php echo htmlspecialchars($editUrl); ?>">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <form method="post" action="turf-console/deductionNormsManager.php" class="arDelForm">
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
                            <a class="ar-page<?php echo $page <= 1 ? ' disabled' : ''; ?>" href="<?php echo htmlspecialchars(dnHtmlUrl($page - 1)); ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>

                            <?php
                            $prev = 0;
                            foreach ($show as $p) {
                                if ($prev && $p - $prev > 1) {
                                    echo '<span class="ar-page dots">...</span>';
                                }
                                $cls = ($p === $page) ? ' current' : '';
                                echo '<a class="ar-page' . $cls . '" href="' . htmlspecialchars(dnHtmlUrl($p)) . '">' . $p . '</a>';
                                $prev = $p;
                            }
                            ?>

                            <a class="ar-page<?php echo $page >= $totalPages ? ' disabled' : ''; ?>" href="<?php echo htmlspecialchars(dnHtmlUrl($page + 1)); ?>">
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

            var DEFAULT_COL1 = 'Odds on withdrawn Horse/s at the time of Withdrawal';
            var DEFAULT_COL2 = 'Percentage of deductions';

            var initialTables = <?php echo json_encode($fTables, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

            var emptyTables = [{
                heading: '',
                text: '',
                col1: DEFAULT_COL1,
                col2: DEFAULT_COL2,
                rows: [{ c1: '', c2: '' }]
            }];

            var form       = document.getElementById('arForm');
            var dateIn     = document.getElementById('arDate');
            var headerIn   = document.getElementById('arHeader');
            var txtArea    = document.getElementById('arText');
            var counter    = document.getElementById('arCount');
            var secBox     = document.getElementById('arSections');
            var addSecBtn  = document.getElementById('arAddSection');
            var tablesJson = document.getElementById('arTablesJson');
            var resetBtn   = document.getElementById('arReset');
            var msgBox     = document.getElementById('arMsg');
            var msgTimer   = null;

            function $(id) {
                return document.getElementById(id);
            }

            function esc(s) {
                return String(s)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
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
                    if (inputEl) inputEl.classList.add('invalid');
                } else {
                    e.classList.remove('show');
                    if (inputEl) inputEl.classList.remove('invalid');
                }
            }

            function updateCount() {
                counter.textContent = txtArea.value.length + ' characters';
            }

            function toNode(html) {
                var d = document.createElement('div');
                d.innerHTML = html;
                return d.firstChild;
            }

            // ---------- TABLE BUILDER ----------
            function rowHtml(r) {
                r = r || {};
                return '<div class="ar-trow">' +
                    '<input type="text" class="ar-input r-c1" maxlength="500" placeholder="e.g. If the Current Odds are EVENS or ODDS ON" value="' + esc(r.c1 || '') + '">' +
                    '<input type="text" class="ar-input r-c2" maxlength="200" placeholder="e.g. 50%" value="' + esc(r.c2 || '') + '">' +
                    '<button type="button" class="ar-icon-btn del r-remove" title="Remove row"><i class="fas fa-times"></i></button>' +
                    '</div>';
            }

            function sectionHtml(s) {
                s = s || {};
                var rows = (s.rows && s.rows.length) ? s.rows : [{ c1: '', c2: '' }];
                var rowsHtml = '';
                for (var i = 0; i < rows.length; i++) {
                    rowsHtml += rowHtml(rows[i]);
                }

                return '<div class="ar-sec">' +
                    '<div class="ar-sec-head">' +
                        '<span class="ar-sec-title">Table</span>' +
                        '<button type="button" class="ar-icon-btn del s-remove" title="Remove this table"><i class="fas fa-trash"></i></button>' +
                    '</div>' +
                    '<div class="ar-field"><label>Table heading</label>' +
                        '<input type="text" class="ar-input s-heading" maxlength="300" placeholder="e.g. (A) Deduction for WIN bets" value="' + esc(s.heading || '') + '"></div>' +
                    '<div class="ar-field"><label>Text below the heading</label>' +
                        '<textarea class="ar-textarea ar-textarea-sm s-text" placeholder="e.g. The percentage rate of deduction on WIN bets ...">' + esc(s.text || '') + '</textarea></div>' +
                    '<div class="ar-row">' +
                        '<div class="ar-field"><label>Column 1 heading</label>' +
                            '<input type="text" class="ar-input s-col1" maxlength="300" value="' + esc(s.col1 || DEFAULT_COL1) + '"></div>' +
                        '<div class="ar-field"><label>Column 2 heading</label>' +
                            '<input type="text" class="ar-input s-col2" maxlength="300" value="' + esc(s.col2 || DEFAULT_COL2) + '"></div>' +
                    '</div>' +
                    '<div class="ar-rows-label">Rows</div>' +
                    '<div class="ar-rows">' + rowsHtml + '</div>' +
                    '<div class="ar-sec-actions">' +
                        '<button type="button" class="ar-btn ar-btn-secondary ar-btn-sm s-add-row"><i class="fas fa-plus"></i> Add row</button>' +
                        '<button type="button" class="ar-btn ar-btn-secondary ar-btn-sm s-bulk-toggle"><i class="fas fa-paste"></i> Paste multiple rows</button>' +
                    '</div>' +
                    '<div class="ar-bulk">' +
                        '<textarea class="ar-textarea ar-textarea-sm s-bulk-text" placeholder="One row per line:&#10;Column 1 text | Column 2 text"></textarea>' +
                        '<button type="button" class="ar-btn ar-btn-primary ar-btn-sm s-bulk-add"><i class="fas fa-plus"></i> Add these rows</button>' +
                    '</div>' +
                    '</div>';
            }

            function renumber() {
                var secs = secBox.querySelectorAll('.ar-sec');
                for (var i = 0; i < secs.length; i++) {
                    secs[i].querySelector('.ar-sec-title').textContent = 'Table ' + (i + 1);
                }
            }

            function addSection(s) {
                var node = toNode(sectionHtml(s));
                secBox.appendChild(node);
                renumber();
                return node;
            }

            function buildSections(list) {
                secBox.innerHTML = '';
                for (var i = 0; i < list.length; i++) {
                    addSection(list[i]);
                }
            }

            function collectTables() {
                var out = [];
                var secs = secBox.querySelectorAll('.ar-sec');

                for (var i = 0; i < secs.length; i++) {
                    var sec = secs[i];
                    var heading = sec.querySelector('.s-heading').value.trim();
                    var text    = sec.querySelector('.s-text').value.trim();
                    var col1    = sec.querySelector('.s-col1').value.trim();
                    var col2    = sec.querySelector('.s-col2').value.trim();
                    var rowEls  = sec.querySelectorAll('.ar-trow');
                    var rows    = [];

                    for (var j = 0; j < rowEls.length; j++) {
                        var c1 = rowEls[j].querySelector('.r-c1').value.trim();
                        var c2 = rowEls[j].querySelector('.r-c2').value.trim();
                        if (c1 === '' && c2 === '') continue;
                        rows.push({ c1: c1, c2: c2 });
                    }

                    if (heading === '' && text === '' && rows.length === 0) continue;

                    out.push({
                        heading: heading,
                        text: text,
                        col1: col1 || DEFAULT_COL1,
                        col2: col2 || DEFAULT_COL2,
                        rows: rows
                    });
                }

                return out;
            }

            // Clicks inside the table builder (remove, add row, paste rows)
            secBox.addEventListener('click', function(e) {
                var btn = e.target;
                while (btn && btn !== secBox && btn.tagName !== 'BUTTON') {
                    btn = btn.parentNode;
                }
                if (!btn || btn === secBox) return;

                var sec = btn;
                while (sec && sec !== secBox && !(sec.classList && sec.classList.contains('ar-sec'))) {
                    sec = sec.parentNode;
                }
                if (!sec || sec === secBox) return;

                var rowsBox = sec.querySelector('.ar-rows');

                if (btn.classList.contains('s-remove')) {
                    if (confirm('Remove this table?')) {
                        secBox.removeChild(sec);
                        renumber();
                    }
                    return;
                }

                if (btn.classList.contains('s-add-row')) {
                    var newRow = toNode(rowHtml());
                    rowsBox.appendChild(newRow);
                    newRow.querySelector('.r-c1').focus();
                    return;
                }

                if (btn.classList.contains('r-remove')) {
                    var row = btn.parentNode;
                    if (rowsBox.querySelectorAll('.ar-trow').length > 1) {
                        rowsBox.removeChild(row);
                    } else {
                        row.querySelector('.r-c1').value = '';
                        row.querySelector('.r-c2').value = '';
                    }
                    return;
                }

                if (btn.classList.contains('s-bulk-toggle')) {
                    sec.querySelector('.ar-bulk').classList.toggle('open');
                    return;
                }

                if (btn.classList.contains('s-bulk-add')) {
                    var bulk  = sec.querySelector('.s-bulk-text');
                    var lines = bulk.value.split(/\r?\n/);
                    var added = 0;

                    // remove the blank rows first
                    var existing = rowsBox.querySelectorAll('.ar-trow');
                    for (var j = 0; j < existing.length; j++) {
                        var v1 = existing[j].querySelector('.r-c1').value.trim();
                        var v2 = existing[j].querySelector('.r-c2').value.trim();
                        if (v1 === '' && v2 === '') {
                            rowsBox.removeChild(existing[j]);
                        }
                    }

                    for (var k = 0; k < lines.length; k++) {
                        var line = lines[k].trim();
                        if (line === '') continue;

                        var idx = line.lastIndexOf('|');
                        var c1 = line;
                        var c2 = '';
                        if (idx !== -1) {
                            c1 = line.substring(0, idx).trim();
                            c2 = line.substring(idx + 1).trim();
                        }

                        rowsBox.appendChild(toNode(rowHtml({ c1: c1, c2: c2 })));
                        added++;
                    }

                    if (rowsBox.querySelectorAll('.ar-trow').length === 0) {
                        rowsBox.appendChild(toNode(rowHtml()));
                    }

                    bulk.value = '';
                    sec.querySelector('.ar-bulk').classList.remove('open');
                    if (!added) {
                        showMsg('Nothing to add. Write one row per line.', 'error');
                    }
                    return;
                }
            });

            addSecBtn.addEventListener('click', function() {
                var node = addSection({ col1: DEFAULT_COL1, col2: DEFAULT_COL2, rows: [{ c1: '', c2: '' }] });
                node.querySelector('.s-heading').focus();
            });

            // ---------- OTHER FIELDS ----------
            dateIn.addEventListener('change', function() {
                if (this.value) setErr('errDate', this, false);
            });

            headerIn.addEventListener('input', function() {
                setErr('errContent', null, false);
            });

            txtArea.addEventListener('input', function() {
                updateCount();
                setErr('errContent', null, false);
            });

            if (resetBtn) {
                resetBtn.addEventListener('click', function() {
                    form.reset();
                    buildSections(emptyTables);
                    updateCount();
                    setErr('errDate', dateIn, false);
                    setErr('errContent', null, false);
                });
            }

            form.addEventListener('submit', function(e) {
                var ok = true;

                if (!dateIn.value) {
                    setErr('errDate', dateIn, true);
                    ok = false;
                }

                var tables = collectTables();
                var hasContent = headerIn.value.trim() !== '' || tables.length > 0 || txtArea.value.trim() !== '';

                if (!hasContent) {
                    setErr('errContent', null, true);
                    ok = false;
                } else {
                    setErr('errContent', null, false);
                }

                if (!ok) {
                    e.preventDefault();
                    showMsg('Please fill all required fields.', 'error');
                    return;
                }

                tablesJson.value = tables.length ? JSON.stringify(tables) : '';
            });

            var delForms = document.querySelectorAll('.arDelForm');
            for (var i = 0; i < delForms.length; i++) {
                delForms[i].addEventListener('submit', function(e) {
                    if (!confirm('Are you sure you want to delete this entry? This cannot be undone.')) {
                        e.preventDefault();
                    }
                });
            }

            // initial state
            buildSections(initialTables);
            updateCount();

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