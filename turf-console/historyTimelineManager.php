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

// Sections (tabs) of this module: key => label
// Every section keeps exactly ONE row in history_timeline.
$sections = array(
    'timeline'           => 'Timeline / Major Events',
    'bequeathing_legacy' => 'Bequeathing a Colonial Legacy',
);

// Selected section (validated against the whitelist above)
$secKey = 'timeline';
$reqSec = isset($_POST['sec']) ? $_POST['sec'] : (isset($_GET['sec']) ? $_GET['sec'] : '');
if (is_string($reqSec) && isset($sections[$reqSec])) {
    $secKey = $reqSec;
}
$secName    = $sections[$secKey];
$isTimeline = ($secKey === 'timeline');

// ---------------------------------------------------------------
// LOGIN + MODULE ACCESS CHECK
// ---------------------------------------------------------------
$secmsg = '';
if (!isAdminlogin()) {
    $secmsg = "You do not have access to this page.";
} elseif (!hasModuleAccess('history_timeline')) {
    $secmsg = "You do not have access to this module.";
}

// CSRF token
if (empty($_SESSION['history_csrf'])) {
    $_SESSION['history_csrf'] = bin2hex(openssl_random_pseudo_bytes(16));
}
$csrf = $_SESSION['history_csrf'];

// FLASH MESSAGE
$flash = '';
$flashType = '';
if (isset($_SESSION['history_flash'])) {
    $flash = $_SESSION['history_flash']['text'];
    $flashType = $_SESSION['history_flash']['type'];
    unset($_SESSION['history_flash']);
}

// ---------------------------------------------------------------
// HELPERS
// ---------------------------------------------------------------
function historySetFlash($text, $type)
{
    $_SESSION['history_flash'] = array('text' => $text, 'type' => $type);
}

// The ONE row of a section. $secKey is whitelisted, so it is safe to use in SQL.
// If old duplicate rows exist, the latest one (by date, then id) is used, whatever its id is.
function historyGetSectionRow($db, $secKey)
{
    return $db->getSingleRowAssoc(
        "SELECT * FROM history_timeline
         WHERE section = '" . $db->escape($secKey) . "'
         ORDER BY report_date DESC, id DESC
         LIMIT 1"
    );
}

// URL for HTML links (the page uses <base href>, so the folder prefix is needed)
function historyHtmlUrl($sec)
{
    return 'turf-console/historyTimelineManager.php?sec=' . urlencode($sec);
}

// ---------------------------------------------------------------
// SAVE (every section always uses exactly one row)
// ---------------------------------------------------------------
if (empty($secmsg) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $redirect = 'historyTimelineManager.php?sec=' . urlencode($secKey);

    // CSRF check
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['history_csrf'], (string)$_POST['csrf'])) {
        historySetFlash('Invalid request. Please try again.', 'error');
        header('Location: ' . $redirect);
        exit;
    }

    $title = isset($_POST['report_title']) ? trim($_POST['report_title']) : '';
    $text  = isset($_POST['content']) ? trim($_POST['content']) : '';
    // normalise line endings so lines / paragraphs are always separated by "\n"
    $text  = str_replace("\r\n", "\n", $text);
    $text  = str_replace("\r", "\n", $text);

    $err = '';
    if ($text === '') {
        $err = 'Content is required.';
    } elseif (mb_strlen($text) > $maxChars) {
        $err = 'Content is too long (max ' . $maxChars . ' characters).';
    } elseif (mb_strlen($title) > $maxTitle) {
        $err = 'Title is too long (max ' . $maxTitle . ' characters).';
    }

    if ($err === '') {
        try {
            $titleSql = ($title !== '') ? "'" . $db->escape($title) . "'" : "NULL";
            $textSql  = "'" . $db->escape($text) . "'";

            $oldRow = historyGetSectionRow($db, $secKey);

            if ($oldRow) {
                // Row already exists -> always UPDATE it (never a new row)
                $db->update("UPDATE history_timeline SET
                                report_date = CURDATE(),
                                title = $titleSql,
                                type = 'text',
                                content_text = $textSql,
                                file_name = NULL
                             WHERE id = " . (int)$oldRow['id'] . " AND section = '" . $db->escape($secKey) . "'");
            } else {
                // First time for this section -> create its one and only row
                $db->insert("INSERT INTO history_timeline (section, report_date, title, type, content_text, file_name)
                             VALUES ('" . $db->escape($secKey) . "',
                                     CURDATE(),
                                     $titleSql,
                                     'text',
                                     $textSql,
                                     NULL)");
            }

            historySetFlash('Content updated successfully.', 'success');
        } catch (Exception $e) {
            error_log('History timeline save error: ' . $e->getMessage());
            historySetFlash('Database error. Could not save.', 'error');
        }
    } else {
        historySetFlash($err, 'error');
    }

    header('Location: ' . $redirect);
    exit;
}

// ---------------------------------------------------------------
// LOAD CURRENT CONTENT (the section's one row)
// ---------------------------------------------------------------
$fTitle = '';
$fText  = '';
$lastUpdated = '';

if (empty($secmsg)) {
    $row = historyGetSectionRow($db, $secKey);
    if ($row) {
        $fTitle = (string)$row['title'];
        $fText  = (string)$row['content_text'];
        $lastUpdated = $row['report_date'] ? date('d M Y', strtotime($row['report_date'])) : '';
    }
}

// PAGE TITLE
$pageTitle = 'History';

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

    .ar-tabs { display: flex; gap: 8px; margin-bottom: 22px; overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 4px; }
    .ar-tab { flex: 0 0 auto; padding: 9px 16px; border: 1px solid #d3d9d6; border-radius: 8px; background: #fff; color: #2b332f; font-size: 14px; font-weight: 600; text-decoration: none; white-space: nowrap; transition: all .15s ease; }
    .ar-tab:hover { border-color: #1a7a45; color: #0f5c33; background: #eef7f1; }
    .ar-tab.active { background: #0f5c33; border-color: #0f5c33; color: #fff; }

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
    .ar-textarea { width: 100%; box-sizing: border-box; padding: 12px 14px; font-size: 15px; font-family: inherit; color: #2b332f; background: #fff; border: 1px solid #d3d9d6; border-radius: 8px; outline: none; min-height: 380px; resize: vertical; line-height: 1.6; transition: border-color .15s ease, box-shadow .15s ease; }
    .ar-textarea::placeholder { color: #9aa9a2; }
    .ar-textarea:focus { border-color: #1a7a45; box-shadow: 0 0 0 3px rgba(26, 122, 69, 0.15); }
    .ar-input.invalid, .ar-textarea.invalid { border-color: #c0392b; }
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
    .ar-tl-item { display: flex; gap: 18px; padding: 10px 0; border-bottom: 1px solid #eef1ef; }
    .ar-tl-item:last-child { border-bottom: none; }
    .ar-tl-year { flex: 0 0 70px; font-weight: 700; color: #0f5c33; font-size: 15px; }
    .ar-tl-text { flex: 1; min-width: 0; color: #4b5563; font-size: 14.5px; line-height: 1.7; word-break: break-word; }

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
        .ar-tl-item { flex-direction: column; gap: 4px; }
        .ar-tl-year { flex: none; }
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
                <h1 class="ar-title">HISTORY</h1>
                <p class="ar-subtitle"><?php echo htmlspecialchars($secName); ?> &mdash; edit the text shown on the website page</p>
            </div>
        </div>

        <div class="ar-tabs">
            <?php foreach ($sections as $tKey => $tLabel) { ?>
                <a class="ar-tab<?php echo $tKey === $secKey ? ' active' : ''; ?>"
                   href="<?php echo htmlspecialchars(historyHtmlUrl($tKey)); ?>">
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

            <div class="ar-card-title"><?php echo htmlspecialchars($secName); ?> Content</div>

            <?php if ($lastUpdated !== '') { ?>
                <div class="ar-updated"><i class="fas fa-clock"></i> Last updated: <?php echo htmlspecialchars($lastUpdated); ?></div>
            <?php } ?>

            <form id="arForm" method="post" action="turf-console/historyTimelineManager.php" novalidate>

                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                <input type="hidden" name="sec" value="<?php echo htmlspecialchars($secKey); ?>">

                <div class="ar-field">
                    <label for="arTitle">Title <span class="opt">(optional)</span></label>
                    <input type="text" id="arTitle" name="report_title" class="ar-input" maxlength="<?php echo (int)$maxTitle; ?>" placeholder="Enter title (optional)" value="<?php echo htmlspecialchars($fTitle); ?>">
                    <div class="ar-hint"><span>Leave this field empty to display the default heading on the website.</span></div>
                    <div class="ar-err" id="errTitle">Title is too long.</div>
                </div>

                <div class="ar-field">
                    <label for="arContent">Content <span class="req">*</span></label>
                    <textarea id="arContent" name="content" class="ar-textarea" placeholder="<?php echo $isTimeline ? '1798 | Enter the event text here' : 'Write the content here...'; ?>"><?php echo htmlspecialchars($fText); ?></textarea>
                    <div class="ar-hint">
                        <?php if ($isTimeline) { ?>
                            <span>One line = one event. Format: <b>Year | Text</b> (for example, <b>1798 | A two-day race meeting...</b>). The <b>|</b> symbol must be included after the year.</span>
                        <?php } else { ?>
                            <span>Leave a blank line between paragraphs. Each paragraph shows as a separate block on the website.</span>
                        <?php } ?>
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

            var isTimeline = <?php echo json_encode($isTimeline); ?>;
            var maxChars   = <?php echo (int)$maxChars; ?>;
            var maxTitle   = <?php echo (int)$maxTitle; ?>;

            var form    = document.getElementById('arForm');
            var titleIn = document.getElementById('arTitle');
            var area    = document.getElementById('arContent');
            var counter = document.getElementById('arCount');
            var preview = document.getElementById('arPreview');
            var revert  = document.getElementById('arRevert');
            var msgBox  = document.getElementById('arMsg');
            var savedTitle   = titleIn.value;
            var savedContent = area.value;
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

            // Preview
            //  - Timeline tab: one line = one event ("Year | Text")
            //  - Other tab: blank lines split paragraphs
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

                var i;
                if (isTimeline) {
                    var lines = text.split('\n');
                    for (i = 0; i < lines.length; i++) {
                        var line = lines[i].trim();
                        if (line === '') continue;

                        var pos = line.indexOf('|');
                        var item = document.createElement('div');
                        item.className = 'ar-tl-item';

                        var yr = document.createElement('div');
                        yr.className = 'ar-tl-year';
                        var tx = document.createElement('div');
                        tx.className = 'ar-tl-text';

                        if (pos === -1) {
                            yr.textContent = '?';
                            tx.textContent = line + '  (missing "|" after the year)';
                        } else {
                            yr.textContent = line.substring(0, pos).trim();
                            tx.textContent = line.substring(pos + 1).trim();   // textContent keeps it safe from HTML
                        }

                        item.appendChild(yr);
                        item.appendChild(tx);
                        preview.appendChild(item);
                    }
                } else {
                    var parts = text.split(/\n\s*\n/);
                    for (i = 0; i < parts.length; i++) {
                        var p = document.createElement('p');
                        p.textContent = parts[i].trim();
                        preview.appendChild(p);
                    }
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