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
$maxChars = 50000;   // max characters allowed in the content
$maxTitle = 200;     // max characters allowed in the title

// ---------------------------------------------------------------
// LOGIN + MODULE ACCESS CHECK
// ---------------------------------------------------------------
$secmsg = '';
if (!isAdminlogin()) {
    $secmsg = "You do not have access to this page.";
} elseif (!hasModuleAccess('community_contribution')) {
    $secmsg = "You do not have access to this module.";
}

// CSRF token
if (empty($_SESSION['community_csrf'])) {
    $_SESSION['community_csrf'] = bin2hex(openssl_random_pseudo_bytes(16));
}
$csrf = $_SESSION['community_csrf'];

// FLASH MESSAGE
$flash = '';
$flashType = '';
if (isset($_SESSION['community_flash'])) {
    $flash = $_SESSION['community_flash']['text'];
    $flashType = $_SESSION['community_flash']['type'];
    unset($_SESSION['community_flash']);
}

// ---------------------------------------------------------------
// SAVE (the page always uses exactly one row: id = 1)
// ---------------------------------------------------------------
if (empty($secmsg) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['community_csrf'], (string)$_POST['csrf'])) {
        $_SESSION['community_flash'] = array('text' => 'Invalid request. Please try again.', 'type' => 'error');
        header('Location: communityContributionManager.php');
        exit;
    }

    $title   = isset($_POST['report_title']) ? trim($_POST['report_title']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    // normalise line endings so paragraphs are always separated by "\n\n"
    $content = str_replace("\r\n", "\n", $content);
    $content = str_replace("\r", "\n", $content);

    $err = '';
    if ($content === '') {
        $err = 'Content is required.';
    } elseif (mb_strlen($content) > $maxChars) {
        $err = 'Content is too long (max ' . $maxChars . ' characters).';
    } elseif (mb_strlen($title) > $maxTitle) {
        $err = 'Title is too long (max ' . $maxTitle . ' characters).';
    }

    if ($err === '') {
        try {
            $titleSql = ($title !== '') ? "'" . $db->escape($title) . "'" : "NULL";

            // Always writes to id = 1: inserts it the first time, updates it afterwards
            $db->query(
                "INSERT INTO community_contribution (id, report_date, title, `type`, content_text, file_name)
                 VALUES (1, CURDATE(), $titleSql, 'text', '" . $db->escape($content) . "', NULL)
                 ON DUPLICATE KEY UPDATE
                    report_date = CURDATE(),
                    title = VALUES(title),
                    `type` = 'text',
                    content_text = VALUES(content_text),
                    file_name = NULL"
            );
            $_SESSION['community_flash'] = array('text' => 'Content updated successfully.', 'type' => 'success');
        } catch (Exception $e) {
            error_log('Community Contribution save error: ' . $e->getMessage());
            $_SESSION['community_flash'] = array('text' => 'Database error. Could not save.', 'type' => 'error');
        }
    } else {
        $_SESSION['community_flash'] = array('text' => $err, 'type' => 'error');
    }

    header('Location: communityContributionManager.php');
    exit;
}

// ---------------------------------------------------------------
// LOAD CURRENT CONTENT
// ---------------------------------------------------------------
$currentTitle = '';
$currentContent = '';
$lastUpdated = '';

if (empty($secmsg)) {
    $row = $db->getSingleRowAssoc("SELECT title, content_text, report_date FROM community_contribution WHERE id = 1");
    if ($row) {
        $currentTitle = (string)$row['title'];
        $currentContent = (string)$row['content_text'];
        $lastUpdated = $row['report_date'] ? date('d M Y', strtotime($row['report_date'])) : '';
    }
}

// PAGE TITLE
$pageTitle = 'Contribution to the Communities';

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

    .ar-card { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; padding: 24px; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03); max-width: 900px; margin-bottom: 26px; }
    .ar-card-title { font-family: inherit; font-size: 17px; font-weight: 600; color: #2b332f; margin: 0 0 20px; padding-bottom: 14px; border-bottom: 1px solid #eef1ef; }
    .ar-card-title::before, .ar-card-title::after { display: none; content: none; }

    .ar-field { margin-bottom: 18px; min-width: 0; }
    .ar-field label { display: block; font-size: 13px; font-weight: 600; color: #2b332f; margin-bottom: 6px; }
    .ar-field label .req { color: #c0392b; }
    .ar-field label .opt { color: #7a8c84; font-weight: 500; }
    .ar-input { width: 100%; box-sizing: border-box; padding: 10px 12px; font-size: 15px; font-family: inherit; color: #2b332f; background: #fff; border: 1px solid #d3d9d6; border-radius: 8px; outline: none; transition: border-color .15s ease, box-shadow .15s ease; }
    .ar-input::placeholder { color: #9aa9a2; }
    .ar-input:focus { border-color: #1a7a45; box-shadow: 0 0 0 3px rgba(26, 122, 69, 0.15); }
    .ar-input.invalid { border-color: #c0392b; }
    .ar-textarea { width: 100%; box-sizing: border-box; padding: 12px 14px; font-size: 15px; font-family: inherit; color: #2b332f; background: #fff; border: 1px solid #d3d9d6; border-radius: 8px; outline: none; min-height: 340px; resize: vertical; line-height: 1.6; transition: border-color .15s ease, box-shadow .15s ease; }
    .ar-textarea::placeholder { color: #9aa9a2; }
    .ar-textarea:focus { border-color: #1a7a45; box-shadow: 0 0 0 3px rgba(26, 122, 69, 0.15); }
    .ar-textarea.invalid { border-color: #c0392b; }
    .ar-hint { font-size: 12px; color: #7a8c84; margin-top: 6px; display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
    .ar-err { color: #c0392b; font-size: 12px; margin-top: 5px; display: none; }
    .ar-err.show { display: block; }

    .ar-actions { display: flex; gap: 10px; margin-top: 8px; padding-top: 18px; border-top: 1px solid #eef1ef; flex-wrap: wrap; }
    .ar-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 11px 22px; font-size: 15px; font-weight: 600; font-family: inherit; border-radius: 8px; border: 1px solid transparent; cursor: pointer; transition: all .15s ease; text-decoration: none; }
    .ar-btn-primary { background: #0f5c33; color: #fff; }
    .ar-btn-primary:hover { background: #1a7a45; box-shadow: 0 4px 10px rgba(15, 92, 51, 0.25); color: #fff; }
    .ar-btn-secondary { background: #fff; color: #2b332f; border-color: #d3d9d6; }
    .ar-btn-secondary:hover { border-color: #7a8c84; color: #2b332f; }

    .ar-updated { font-size: 13px; color: #7a8c84; margin-bottom: 14px; }
    .ar-updated i { color: #0f5c33; margin-right: 4px; }

    /* ---------- LIVE PREVIEW ---------- */
    .ar-preview { background: #fff; border: 1px solid #e2e6e4; border-radius: 16px; padding: 26px 30px; box-shadow: 0 6px 20px rgba(11, 61, 36, 0.06); }
    .ar-preview h3 { font-size: 18px; font-weight: 700; color: #2b332f; margin: 0 0 16px; }
    .ar-preview p { font-style: italic; color: #4b5563; font-size: 14.5px; line-height: 1.9; margin: 0 0 18px !important; text-align: left !important; }
    .ar-preview > :last-child { margin-bottom: 0 !important; }
    .ar-preview-empty { color: #9aa9a2; font-size: 14px; }

    html, body { scrollbar-width: none; -ms-overflow-style: none; }
    html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }

    @media (min-width: 1920px) {
        .ar-title { font-size: 30px; }
        .ar-card { max-width: 1100px; padding: 30px; }
    }
    @media (max-width: 700px) {
        .ar-title { font-size: 20px; }
        .ar-card { padding: 16px; border-radius: 10px; }
        .ar-preview { padding: 18px 16px; }
    }
    @media (max-width: 560px) {
        .ar-header { flex-direction: column; align-items: flex-start; margin-bottom: 14px; gap: 8px; }
        .ar-subtitle { font-size: 13px; }
        .ar-actions { flex-direction: column; }
        .ar-btn { width: 100%; }
        .ar-textarea { min-height: 260px; }
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
                <h1 class="ar-title">CONTRIBUTION TO THE COMMUNITIES</h1>
                <p class="ar-subtitle">Edit the text shown on the website page</p>
            </div>
        </div>

        <?php if ($flash !== '') { ?>
            <div class="message <?php echo htmlspecialchars($flashType); ?>">
                <?php echo htmlspecialchars($flash); ?>
            </div>
        <?php } ?>

        <div id="arMsg" class="message" style="display:none;"></div>


        <!-- ===================== EDITOR ===================== -->
        <div class="ar-card">

            <div class="ar-card-title">Page Content</div>

            <?php if ($lastUpdated !== '') { ?>
                <div class="ar-updated"><i class="fas fa-clock"></i> Last updated: <?php echo htmlspecialchars($lastUpdated); ?></div>
            <?php } ?>

            <form id="arForm" method="post" action="turf-console/communityContributionManager.php" novalidate>

                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">

                <div class="ar-field">
                    <label for="arTitle">Title <span class="opt">(optional)</span></label>
                    <input type="text" id="arTitle" name="report_title" class="ar-input" maxlength="<?php echo (int)$maxTitle; ?>" placeholder="Enter title (optional)" value="<?php echo htmlspecialchars($currentTitle); ?>">
                    <div class="ar-hint"><span>Leave this field empty to display the default heading on the website.</span></div>
                    <div class="ar-err" id="errTitle">Title is too long.</div>
                </div>

                <div class="ar-field">
                    <label for="arContent">Content <span class="req">*</span></label>
                    <textarea id="arContent" name="content" class="ar-textarea" placeholder="Write the content here..."><?php echo htmlspecialchars($currentContent); ?></textarea>
                    <div class="ar-hint">
                        <span>Leave a blank line between paragraphs. Each paragraph shows as a separate block on the website.</span>
                        <span id="arCount">0 characters</span>
                    </div>
                    <div class="ar-err" id="errContent">Please write some content.</div>
                </div>

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


        <!-- ===================== LIVE PREVIEW ===================== -->
        <div class="ar-card">
            <div class="ar-card-title">Preview</div>
            <div class="ar-preview" id="arPreview"></div>
        </div>

    </div>


    <script type="text/javascript">
        (function() {

            var form    = document.getElementById('arForm');
            var titleIn = document.getElementById('arTitle');
            var area    = document.getElementById('arContent');
            var counter = document.getElementById('arCount');
            var preview = document.getElementById('arPreview');
            var revert  = document.getElementById('arRevert');
            var msgBox  = document.getElementById('arMsg');
            var savedTitle   = titleIn.value;
            var savedContent = area.value;
            var maxChars = <?php echo (int)$maxChars; ?>;
            var maxTitle = <?php echo (int)$maxTitle; ?>;
            var msgTimer = null;

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
                if (text) e.textContent = text;
                e.classList.toggle('show', show);
                inputEl.classList.toggle('invalid', show);
            }

            // Preview: optional title, then blank lines split the text into paragraphs
            function renderPreview() {
                var text = area.value.replace(/\r\n?/g, '\n').trim();
                var title = titleIn.value.trim();
                preview.innerHTML = '';

                if (text === '') {
                    var empty = document.createElement('div');
                    empty.className = 'ar-preview-empty';
                    empty.textContent = 'Nothing to preview yet.';
                    preview.appendChild(empty);
                    return;
                }

                if (title !== '') {
                    var h = document.createElement('h3');
                    h.textContent = title;
                    preview.appendChild(h);
                }

                var parts = text.split(/\n\s*\n/);
                for (var i = 0; i < parts.length; i++) {
                    var p = document.createElement('p');
                    p.textContent = parts[i].trim();   // textContent keeps it safe from HTML
                    preview.appendChild(p);
                }
            }

            function update() {
                counter.textContent = area.value.length + ' characters';
                if (area.value.trim()) setErr('errContent', area, false);
                if (titleIn.value.trim().length <= maxTitle) setErr('errTitle', titleIn, false);
                renderPreview();
            }

            area.addEventListener('input', update);
            titleIn.addEventListener('input', update);

            revert.addEventListener('click', function() {
                titleIn.value = savedTitle;
                area.value = savedContent;
                setErr('errContent', area, false);
                setErr('errTitle', titleIn, false);
                update();
            });

            form.addEventListener('submit', function(e) {
                var v = area.value.trim();
                var t = titleIn.value.trim();

                if (!v) {
                    e.preventDefault();
                    setErr('errContent', area, true, 'Please write some content.');
                    showMsg('Please fill all required fields correctly.', 'error');
                    return;
                }
                if (v.length > maxChars) {
                    e.preventDefault();
                    setErr('errContent', area, true, 'Content is too long (max ' + maxChars + ' characters).');
                    showMsg('Content is too long.', 'error');
                    return;
                }
                if (t.length > maxTitle) {
                    e.preventDefault();
                    setErr('errTitle', titleIn, true, 'Title is too long (max ' + maxTitle + ' characters).');
                    showMsg('Title is too long.', 'error');
                }
            });

            update();

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