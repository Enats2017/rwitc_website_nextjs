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

// ---------------------------------------------------------------
// LOGIN + MODULE ACCESS CHECK
// ---------------------------------------------------------------
$secmsg = '';
if (!isAdminlogin()) {
    $secmsg = "You do not have access to this page.";
} elseif (!hasModuleAccess('come_racing_pune')) {
    $secmsg = "You do not have access to this module.";
}

// CSRF token
if (empty($_SESSION['crp_csrf'])) {
    $_SESSION['crp_csrf'] = bin2hex(openssl_random_pseudo_bytes(16));
}
$csrf = $_SESSION['crp_csrf'];

// FLASH MESSAGE
$flash = '';
$flashType = '';
if (isset($_SESSION['crp_flash'])) {
    $flash = $_SESSION['crp_flash']['text'];
    $flashType = $_SESSION['crp_flash']['type'];
    unset($_SESSION['crp_flash']);
}

// ---------------------------------------------------------------
// SAVE (the table always holds exactly one row: id = 1)
// ---------------------------------------------------------------
if (empty($secmsg) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['crp_csrf'], (string)$_POST['csrf'])) {
        $_SESSION['crp_flash'] = array('text' => 'Invalid request. Please try again.', 'type' => 'error');
        header('Location: comeRacingPuneManager.php');
        exit;
    }

    // One point per line: trim each line, drop empty lines.
    // An empty box is allowed and is stored as an empty string.
    $content = isset($_POST['content']) ? (string)$_POST['content'] : '';
    $content = str_replace(array("\r\n", "\r"), "\n", $content);
    $lines = array();
    foreach (explode("\n", $content) as $line) {
        $line = trim($line);
        if ($line !== '') {
            $lines[] = $line;
        }
    }
    $content = implode("\n", $lines);

    $err = '';
    if (mb_strlen($content) > $maxChars) {
        $err = 'Content is too long (max ' . $maxChars . ' characters).';
    }

    if ($err === '') {
        try {
            $db->query(
                "INSERT INTO come_racing_pune_content (id, content)
                 VALUES (1, '" . $db->escape($content) . "')
                 ON DUPLICATE KEY UPDATE content = VALUES(content)"
            );
            $_SESSION['crp_flash'] = array('text' => 'Content updated successfully.', 'type' => 'success');
        } catch (Exception $e) {
            $_SESSION['crp_flash'] = array('text' => 'Database error. Could not save.', 'type' => 'error');
        }
    } else {
        $_SESSION['crp_flash'] = array('text' => $err, 'type' => 'error');
    }

    header('Location: comeRacingPuneManager.php');
    exit;
}

// ---------------------------------------------------------------
// LOAD CURRENT CONTENT
// ---------------------------------------------------------------
$currentContent = '';
$lastUpdated = '';

if (empty($secmsg)) {
    $row = $db->getSingleRowAssoc("SELECT content, updated_at FROM come_racing_pune_content WHERE id = 1");
    if ($row) {
        $currentContent = (string)$row['content'];
        $lastUpdated = $row['updated_at'] ? date('d M Y, h:i A', strtotime($row['updated_at'])) : '';
    }
}

// PAGE TITLE
$pageTitle = 'Come Racing - Pune Race Course';

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
    .ar-card-title .ar-count { font-size: 13px; font-weight: 500; color: #7a8c84; margin-left: 8px; }

    .ar-field { margin-bottom: 18px; min-width: 0; }
    .ar-field label { display: block; font-size: 13px; font-weight: 600; color: #2b332f; margin-bottom: 6px; }
    .ar-field label .req { color: #c0392b; }
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
    .ar-preview ul { margin: 0; padding-left: 22px; }
    .ar-preview li { color: #0f3d2a; font-weight: 600; font-size: 14.5px; line-height: 1.7; margin-bottom: 12px; }
    .ar-preview li::marker { color: #1a9a4a; }
    .ar-preview li:last-child { margin-bottom: 0; }
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
                <h1 class="ar-title">COME RACING - PUNE RACE COURSE</h1>
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

            <div class="ar-card-title">Race Course Points</div>

            <?php if ($lastUpdated !== '') { ?>
                <div class="ar-updated"><i class="fas fa-clock"></i> Last updated: <?php echo htmlspecialchars($lastUpdated); ?></div>
            <?php } ?>

            <form id="arForm" method="post" action="turf-console/comeRacingPuneManager.php" novalidate>

                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">

                <div class="ar-field">
                    <label for="arContent">Points</label>
                    <textarea id="arContent" name="content" class="ar-textarea" placeholder="One point per line..."><?php echo htmlspecialchars($currentContent); ?></textarea>
                    <div class="ar-hint">
                        <span>Write one point per line. Each line becomes one bullet point on the website. The box can be left empty.</span>
                        <span id="arCount">0 points</span>
                    </div>
                    <div class="ar-err" id="errContent">Content is too long.</div>
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
            var area    = document.getElementById('arContent');
            var counter = document.getElementById('arCount');
            var preview = document.getElementById('arPreview');
            var revert  = document.getElementById('arRevert');
            var msgBox  = document.getElementById('arMsg');
            var saved   = area.value;
            var maxChars = <?php echo (int)$maxChars; ?>;
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

            function setErr(show, text) {
                var e = document.getElementById('errContent');
                if (text) e.textContent = text;
                e.classList.toggle('show', show);
                area.classList.toggle('invalid', show);
            }

            // One non-empty line = one bullet point
            function getLines() {
                return area.value.replace(/\r\n?/g, '\n').split('\n')
                    .map(function(l) { return l.trim(); })
                    .filter(function(l) { return l !== ''; });
            }

            function renderPreview() {
                var lines = getLines();
                preview.innerHTML = '';
                if (lines.length === 0) {
                    var empty = document.createElement('div');
                    empty.className = 'ar-preview-empty';
                    empty.textContent = 'Nothing to preview yet.';
                    preview.appendChild(empty);
                    return;
                }
                var ul = document.createElement('ul');
                for (var i = 0; i < lines.length; i++) {
                    var li = document.createElement('li');
                    li.textContent = lines[i];   // textContent keeps it safe from HTML
                    ul.appendChild(li);
                }
                preview.appendChild(ul);
            }

            function update() {
                var n = getLines().length;
                counter.textContent = n + (n === 1 ? ' point' : ' points');
                setErr(false);
                renderPreview();
            }

            area.addEventListener('input', update);

            revert.addEventListener('click', function() {
                area.value = saved;
                setErr(false);
                update();
            });

            form.addEventListener('submit', function(e) {
                var v = area.value.trim();
                if (v.length > maxChars) {
                    e.preventDefault();
                    setErr(true, 'Content is too long (max ' + maxChars + ' characters).');
                    showMsg('Content is too long.', 'error');
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