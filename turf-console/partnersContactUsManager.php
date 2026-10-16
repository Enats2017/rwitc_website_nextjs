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

// ---------------------------------------------------------------
// LOGIN + MODULE ACCESS CHECK
// ---------------------------------------------------------------
$secmsg = '';
if (!isAdminlogin()) {
    $secmsg = "You do not have access to this page.";
} elseif (!hasModuleAccess('partners_contact')) {
    $secmsg = "You do not have access to this module.";
}

// CSRF token
if (empty($_SESSION['partners_contact_csrf'])) {
    $_SESSION['partners_contact_csrf'] = bin2hex(openssl_random_pseudo_bytes(16));
}
$csrf = $_SESSION['partners_contact_csrf'];

// FLASH MESSAGE
$flash = '';
$flashType = '';
if (isset($_SESSION['partners_contact_flash'])) {
    $flash = $_SESSION['partners_contact_flash']['text'];
    $flashType = $_SESSION['partners_contact_flash']['type'];
    unset($_SESSION['partners_contact_flash']);
}

// ---------------------------------------------------------------
// HELPERS
// ---------------------------------------------------------------
function pcSetFlash($text, $type)
{
    $_SESSION['partners_contact_flash'] = array('text' => $text, 'type' => $type);
}

function pcPageUrl($p)
{
    return 'partnersContactUsManager.php' . ($p > 1 ? '?page=' . (int)$p : '');
}

function pcHtmlUrl($p)
{
    return 'turf-console/' . pcPageUrl($p);
}

// Length that works with or without the mbstring extension
function pcLen($s)
{
    return function_exists('mb_strlen') ? mb_strlen($s, 'UTF-8') : strlen($s);
}

// Escaped SQL string value, or NULL when the value is empty
function pcSqlOrNull($db, $v)
{
    return ($v !== '') ? "'" . $db->escape($v) . "'" : "NULL";
}

// ---------------------------------------------------------------
// POST ACTIONS (save / update / delete)
// ---------------------------------------------------------------
if (empty($secmsg) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $action   = isset($_POST['action']) ? $_POST['action'] : '';
    $pageNo   = isset($_POST['page']) ? max(1, (int)$_POST['page']) : 1;
    $redirect = pcPageUrl($pageNo);

    // CSRF check
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['partners_contact_csrf'], (string)$_POST['csrf'])) {
        pcSetFlash('Invalid request. Please try again.', 'error');
        header('Location: partnersContactUsManager.php');
        exit;
    }

    // ---------------- DELETE ----------------
    if ($action === 'delete') {

        $id  = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $row = $id > 0 ? $db->getSingleRowAssoc("SELECT id FROM partners_contact WHERE id = $id") : null;

        if ($row) {
            try {
                $db->query("DELETE FROM partners_contact WHERE id = $id");
                pcSetFlash('Contact deleted successfully.', 'success');
            } catch (Exception $e) {
                error_log($e->getMessage());
                pcSetFlash('Database error. Could not delete.', 'error');
            }
        } else {
            pcSetFlash('Contact not found.', 'error');
        }

        // if the current page becomes empty, go back one page
        $total    = (int)$db->getSingleValue("SELECT COUNT(*) FROM partners_contact");
        $lastPage = max(1, (int)ceil($total / $perPage));
        if ($pageNo > $lastPage) {
            $pageNo = $lastPage;
        }
        header('Location: ' . pcPageUrl($pageNo));
        exit;
    }

    // ---------------- SAVE / UPDATE ----------------
    if ($action === 'save' || $action === 'update') {

        $name     = isset($_POST['contact_name']) ? trim($_POST['contact_name']) : '';
        $position = isset($_POST['contact_position']) ? trim($_POST['contact_position']) : '';
        $phone    = isset($_POST['contact_phone']) ? trim($_POST['contact_phone']) : '';
        $email    = isset($_POST['contact_email']) ? trim($_POST['contact_email']) : '';
        $id       = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $err      = '';

        if ($action === 'update') {
            $oldRow = $id > 0 ? $db->getSingleRowAssoc("SELECT id FROM partners_contact WHERE id = $id") : null;
            if (!$oldRow) {
                $err = 'Contact not found.';
            }
        }

        if ($err !== '') {
            // already set
        } elseif ($name === '') {
            $err = 'Name is required.';
        } elseif (pcLen($name) > 150 || pcLen($position) > 150 || pcLen($email) > 150) {
            $err = 'Name, position and email can be at most 150 characters.';
        } elseif (pcLen($phone) > 100) {
            $err = 'Phone can be at most 100 characters.';
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $err = 'Please enter a valid email address.';
        }

        if ($err === '') {

            $nameSql     = "'" . $db->escape($name) . "'";
            $positionSql = pcSqlOrNull($db, $position);
            $phoneSql    = pcSqlOrNull($db, $phone);
            $emailSql    = pcSqlOrNull($db, $email);

            try {
                if ($action === 'update') {
                    $db->update("UPDATE partners_contact SET
                                    `name` = $nameSql,
                                    `position` = $positionSql,
                                    `phone` = $phoneSql,
                                    `email` = $emailSql
                                 WHERE id = $id");
                    pcSetFlash('Contact updated successfully.', 'success');
                } else {
                    $db->insert("INSERT INTO partners_contact (`name`, `position`, `phone`, `email`)
                                 VALUES ($nameSql, $positionSql, $phoneSql, $emailSql)");
                    pcSetFlash('Saved successfully.', 'success');
                    $redirect = 'partnersContactUsManager.php';
                }
            } catch (Exception $e) {
                error_log($e->getMessage());
                pcSetFlash('Database error. Could not save.', 'error');
            }
        } else {
            pcSetFlash($err, 'error');
            if ($action === 'update' && $id > 0) {
                $redirect = 'partnersContactUsManager.php?edit=' . $id . ($pageNo > 1 ? '&page=' . $pageNo : '');
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
        $editRow = $db->getSingleRowAssoc("SELECT * FROM partners_contact WHERE id = $eid");
    }

    $total      = (int)$db->getSingleValue("SELECT COUNT(*) FROM partners_contact");
    $totalPages = max(1, (int)ceil($total / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;

    $rows = $db->getMultiDimensionalArray(
        "SELECT id, `name`, `position`, `phone`, `email`
         FROM partners_contact
         ORDER BY id DESC
         LIMIT $perPage OFFSET $offset"
    );
}

$isEdit = !empty($editRow);

// default form values
$fName     = $isEdit ? (string)$editRow['name'] : '';
$fPosition = $isEdit ? (string)$editRow['position'] : '';
$fPhone    = $isEdit ? (string)$editRow['phone'] : '';
$fEmail    = $isEdit ? (string)$editRow['email'] : '';

// PAGE TITLE
$pageTitle = 'Partner Contacts';

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
    .ar-input { width: 100%; box-sizing: border-box; padding: 10px 12px; font-size: 15px; font-family: inherit; color: #2b332f; background: #fff; border: 1px solid #d3d9d6; border-radius: 8px; outline: none; transition: border-color .15s ease, box-shadow .15s ease; }
    .ar-input::placeholder { color: #9aa9a2; }
    .ar-input:focus { border-color: #1a7a45; box-shadow: 0 0 0 3px rgba(26, 122, 69, 0.15); }
    .ar-input.invalid { border-color: #c0392b; }
    .ar-err { color: #c0392b; font-size: 12px; margin-top: 5px; display: none; }
    .ar-err.show { display: block; }

    .ar-actions { display: flex; gap: 10px; margin-top: 8px; padding-top: 18px; border-top: 1px solid #eef1ef; flex-wrap: wrap; }
    .ar-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 11px 22px; font-size: 15px; font-weight: 600; font-family: inherit; border-radius: 8px; border: 1px solid transparent; cursor: pointer; transition: all .15s ease; text-decoration: none; }
    .ar-btn-primary { background: #0f5c33; color: #fff; }
    .ar-btn-primary:hover { background: #1a7a45; box-shadow: 0 4px 10px rgba(15, 92, 51, 0.25); color: #fff; }
    .ar-btn-secondary { background: #fff; color: #2b332f; border-color: #d3d9d6; }
    .ar-btn-secondary:hover { border-color: #7a8c84; color: #2b332f; }

    .ar-table-wrap { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .ar-table { width: 100%; border-collapse: collapse; min-width: 560px; }
    .ar-table th { text-align: left; font-size: 12px; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; color: #0f5c33; background: #f3f6f4; padding: 11px 12px; border-bottom: 1px solid #e2e6e4; white-space: nowrap; }
    .ar-table td { font-size: 14px; color: #2b332f; padding: 12px; border-bottom: 1px solid #eef1ef; vertical-align: middle; }
    .ar-table tr:hover td { background: #f8faf9; }
    .ar-table tr.is-editing td { background: #eef7f1; }
    .ar-t-title { font-weight: 600; word-break: break-word; }
    .ar-t-sub { font-size: 12px; color: #7a8c84; margin-top: 3px; word-break: break-word; }
    .ar-t-line { word-break: break-word; }
    .ar-act { display: flex; gap: 6px; flex-wrap: nowrap; }
    .ar-icon-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #d3d9d6; background: #fff; color: #2b332f; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; font-size: 13px; text-decoration: none; transition: all .15s ease; padding: 0; }
    .ar-icon-btn:hover { border-color: #1a7a45; color: #0f5c33; background: #eef7f1; }
    .ar-icon-btn.del:hover { border-color: #c0392b; color: #a12626; background: #fde8e8; }
    .ar-act form { margin: 0; }
    .ar-empty { text-align: center; padding: 30px 10px; color: #7a8c84; font-size: 14px; }

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
                <h1 class="ar-title">PARTNER CONTACTS</h1>
                <p class="ar-subtitle">All contacts are shown on the website</p>
            </div>
        </div>

        <?php if ($flash !== '') { ?>
            <div class="message <?php echo htmlspecialchars($flashType); ?>">
                <?php echo htmlspecialchars($flash); ?>
            </div>
        <?php } ?>

        <?php if (isset($_GET['edit']) && !$isEdit) { ?>
            <div class="message error">Contact not found.</div>
        <?php } ?>

        <div id="arMsg" class="message" style="display:none;"></div>


        <!-- ===================== ADD / EDIT FORM ===================== -->
        <div class="ar-card<?php echo $isEdit ? ' editing' : ''; ?>">

            <div class="ar-card-title">
                <?php echo $isEdit ? 'Edit Contact' : 'Add New Contact'; ?>
            </div>

            <form id="arForm" method="post" action="turf-console/partnersContactUsManager.php" novalidate>

                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                <input type="hidden" name="action" value="<?php echo $isEdit ? 'update' : 'save'; ?>">
                <input type="hidden" name="page" value="<?php echo (int)$page; ?>">
                <?php if ($isEdit) { ?>
                    <input type="hidden" name="id" value="<?php echo (int)$editRow['id']; ?>">
                <?php } ?>

                <div class="ar-row">

                    <div class="ar-field">
                        <label for="arName">Name <span class="req">*</span></label>
                        <input type="text" id="arName" name="contact_name" class="ar-input" maxlength="150" placeholder="Enter name" value="<?php echo htmlspecialchars($fName); ?>">
                        <div class="ar-err" id="errName">Please enter a name.</div>
                    </div>

                    <div class="ar-field">
                        <label for="arPosition">Position <span class="opt">(optional)</span></label>
                        <input type="text" id="arPosition" name="contact_position" class="ar-input" maxlength="150" placeholder="e.g. Secretary" value="<?php echo htmlspecialchars($fPosition); ?>">
                    </div>

                </div>

                <div class="ar-row">

                    <div class="ar-field">
                        <label for="arPhone">Phone No. <span class="opt">(optional)</span></label>
                        <input type="text" id="arPhone" name="contact_phone" class="ar-input" maxlength="100" placeholder="e.g. 022-20842550 Extn No- 101" value="<?php echo htmlspecialchars($fPhone); ?>">
                    </div>

                    <div class="ar-field">
                        <label for="arEmail">Email <span class="opt">(optional)</span></label>
                        <input type="text" id="arEmail" name="contact_email" class="ar-input" maxlength="150" placeholder="e.g. secretary@rwitc.com" autocomplete="off" value="<?php echo htmlspecialchars($fEmail); ?>">
                        <div class="ar-err" id="errEmail">Please enter a valid email address.</div>
                    </div>

                </div>

                <div class="ar-actions">
                    <button type="submit" class="ar-btn ar-btn-primary">
                        <i class="fas fa-save"></i> <?php echo $isEdit ? 'Update' : 'Save'; ?>
                    </button>
                    <?php if ($isEdit) { ?>
                        <a class="ar-btn ar-btn-secondary" href="<?php echo htmlspecialchars(pcHtmlUrl($page)); ?>">
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
                All Contacts <span class="ar-count">(<?php echo (int)$total; ?> total)</span>
            </div>

            <?php if (empty($rows)) { ?>

                <div class="ar-empty">No contacts yet.</div>

            <?php } else { ?>

                <div class="ar-table-wrap">
                    <table class="ar-table">
                        <thead>
                            <tr>
                                <th>Name / Position</th>
                                <th>Phone / Email</th>
                                <th style="width:100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r) {
                                $rid = (int)$r['id'];
                                $isRowEditing = ($isEdit && (int)$editRow['id'] === $rid);
                                $editUrl = 'turf-console/partnersContactUsManager.php?edit=' . $rid . ($page > 1 ? '&page=' . $page : '');

                                $rPosition = trim((string)$r['position']);
                                $rPhone    = trim((string)$r['phone']);
                                $rEmail    = trim((string)$r['email']);
                            ?>
                                <tr class="<?php echo $isRowEditing ? 'is-editing' : ''; ?>">
                                    <td>
                                        <div class="ar-t-title"><?php echo htmlspecialchars($r['name']); ?></div>
                                        <?php if ($rPosition !== '') { ?>
                                            <div class="ar-t-sub"><?php echo htmlspecialchars($rPosition); ?></div>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php if ($rPhone !== '') { ?>
                                            <div class="ar-t-line"><?php echo htmlspecialchars($rPhone); ?></div>
                                        <?php } ?>
                                        <?php if ($rEmail !== '') { ?>
                                            <div class="ar-t-sub"><?php echo htmlspecialchars($rEmail); ?></div>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <div class="ar-act">
                                            <a class="ar-icon-btn" title="Edit" href="<?php echo htmlspecialchars($editUrl); ?>">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <form method="post" action="turf-console/partnersContactUsManager.php" class="arDelForm">
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
                            <a class="ar-page<?php echo $page <= 1 ? ' disabled' : ''; ?>" href="<?php echo htmlspecialchars(pcHtmlUrl($page - 1)); ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>

                            <?php
                            $prev = 0;
                            foreach ($show as $p) {
                                if ($prev && $p - $prev > 1) {
                                    echo '<span class="ar-page dots">...</span>';
                                }
                                $cls = ($p === $page) ? ' current' : '';
                                echo '<a class="ar-page' . $cls . '" href="' . htmlspecialchars(pcHtmlUrl($p)) . '">' . $p . '</a>';
                                $prev = $p;
                            }
                            ?>

                            <a class="ar-page<?php echo $page >= $totalPages ? ' disabled' : ''; ?>" href="<?php echo htmlspecialchars(pcHtmlUrl($page + 1)); ?>">
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

            var form     = document.getElementById('arForm');
            var nameIn   = document.getElementById('arName');
            var emailIn  = document.getElementById('arEmail');
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
                    if (inputEl) inputEl.classList.add('invalid');
                } else {
                    e.classList.remove('show');
                    if (inputEl) inputEl.classList.remove('invalid');
                }
            }

            function isValidEmail(str) {
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(str);
            }

            nameIn.addEventListener('input', function() {
                if (this.value.trim()) setErr('errName', this, false);
            });

            emailIn.addEventListener('input', function() {
                var v = this.value.trim();
                if (v === '' || isValidEmail(v)) setErr('errEmail', this, false);
            });

            if (resetBtn) {
                resetBtn.addEventListener('click', function() {
                    form.reset();
                    setErr('errName', nameIn, false);
                    setErr('errEmail', emailIn, false);
                });
            }

            form.addEventListener('submit', function(e) {
                var ok = true;

                if (!nameIn.value.trim()) {
                    setErr('errName', nameIn, true);
                    ok = false;
                }

                var emailVal = emailIn.value.trim();
                if (emailVal !== '' && !isValidEmail(emailVal)) {
                    setErr('errEmail', emailIn, true);
                    ok = false;
                }

                if (!ok) {
                    e.preventDefault();
                    showMsg('Please fill all required fields correctly.', 'error');
                }
            });

            var delForms = document.querySelectorAll('.arDelForm');
            for (var i = 0; i < delForms.length; i++) {
                delForms[i].addEventListener('submit', function(e) {
                    if (!confirm('Are you sure you want to delete this contact? This cannot be undone.')) {
                        e.preventDefault();
                    }
                });
            }

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