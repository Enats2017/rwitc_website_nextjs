<?php
include_once('../bootstrap.php');
require_once('../lib/users.class.php');
require_once('../lib/userchecks.php');
require_once('../lib/permissions.php');

session_start();
$userObj = new Users($db);

$pageTitle  = 'Home Page Ticker Text';
$moduleKey  = 'home_ticker_text';
$pageFile   = 'homeTickerTextManager.php';
$maxContent = 500;   // max length of the ticker text (characters)

$secmsg = '';
if (!isAdminlogin()) {
    $secmsg = 'You do not have access to this page.';
} elseif (!hasModuleAccess($moduleKey)) {
    $secmsg = 'You do not have access to this module.';
}

if (empty($_SESSION['hticker_csrf'])) {
    $_SESSION['hticker_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['hticker_csrf'];
$flash = isset($_SESSION['hticker_flash']['text']) ? $_SESSION['hticker_flash']['text'] : '';
$flashType = isset($_SESSION['hticker_flash']['type']) ? $_SESSION['hticker_flash']['type'] : '';
$oldContent = isset($_SESSION['hticker_old']) && is_string($_SESSION['hticker_old']) ? $_SESSION['hticker_old'] : null;
unset($_SESSION['hticker_flash'], $_SESSION['hticker_old']);

// Plain text only: remove tags, turn line breaks into spaces and collapse repeated spaces
function htCleanText($text)
{
    $text = strip_tags((string)$text);
    $text = preg_replace('/\s+/u', ' ', $text);
    return trim((string)$text);
}

// SAVE (the page always uses exactly one row: id = 1)
if (empty($secmsg) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = '';
    $cleanContent = '';

    if (!isset($_POST['csrf']) || !hash_equals($csrf, (string)$_POST['csrf'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $raw = isset($_POST['content']) && is_string($_POST['content']) ? $_POST['content'] : '';
        $cleanContent = htCleanText($raw);
        if ($cleanContent === '') {
            $error = 'Ticker text is required.';
        } elseif (mb_strlen($cleanContent, 'UTF-8') > $maxContent) {
            $error = 'Ticker text is too long (maximum ' . $maxContent . ' characters).';
        }
    }

    if ($error === '') {
        try {
            // Always writes to id = 1: inserts it the first time, updates it afterwards
            $db->query(
                "INSERT INTO home_ticker_text (id, content_text, `created`, `updated`)
                 VALUES (1, '" . $db->escape($cleanContent) . "', NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                    content_text = VALUES(content_text),
                    `updated` = NOW()"
            );
            $_SESSION['hticker_flash'] = array('text' => 'Ticker text updated successfully.', 'type' => 'success');
        } catch (Exception $e) {
            error_log('Home ticker text save error: ' . $e->getMessage());
            $_SESSION['hticker_old'] = $cleanContent;
            $_SESSION['hticker_flash'] = array('text' => 'Database error. Could not save.', 'type' => 'error');
        }
    } else {
        // Keep what the admin typed so nothing has to be entered again
        $_SESSION['hticker_old'] = isset($raw) ? $raw : '';
        $_SESSION['hticker_flash'] = array('text' => $error, 'type' => 'error');
    }

    header('Location: ' . $pageFile);
    exit;
}

// LOAD CURRENT CONTENT
$content = '';
$lastUpdated = '';
if (empty($secmsg)) {
    $row = $db->getSingleRowAssoc("SELECT content_text, `updated` FROM home_ticker_text WHERE id = 1");
    if ($row) {
        $content = (string)$row['content_text'];
        $lastUpdated = !empty($row['updated']) ? date('d M Y', strtotime($row['updated'])) : '';
    }
    // After a validation error show what was typed, otherwise the saved value
    if ($oldContent !== null) {
        $content = $oldContent;
    }
}

$design = new Design();
$design->js = '';
$design->css = '';
$design->jqueryJs = '';
$design->startPage($pageTitle);
$design->writeLogoTickerMenu();
$design->openDiv('contentWrapper');
$design->openDiv('infoWrapper', 'col-lg-12');
$design->openDiv('leftArea', 'col-lg-9');
$design->writeContentPageStyles();
?>
<style>
.message{padding:12px 16px;border-radius:8px;margin-bottom:15px;font-size:15px;background:#fff3cd;border:1px solid #ffe08a}.message.success{background:#e3f6ea;border-color:#a9dcbd;color:#0f5c33}.message.error{background:#fde8e8;border-color:#f5b5b5;color:#a12626}
.ar-header{margin-bottom:20px}.ar-title{font-size:24px;font-weight:700;color:#2b332f;margin:0}.ar-subtitle{font-size:14px;color:#7a8c84;margin:6px 0 0}.ar-card{background:#fff;border:1px solid #e2e6e4;border-radius:12px;padding:24px;box-shadow:0 1px 2px rgba(0,0,0,.03);max-width:100%;margin-bottom:26px}.ar-card-title{font-size:17px;font-weight:600;color:#2b332f;margin:0 0 20px;padding-bottom:14px;border-bottom:1px solid #eef1ef}.ar-updated{font-size:13px;color:#7a8c84;margin-bottom:14px}.ar-hint{font-size:12px;color:#7a8c84;margin-bottom:10px;line-height:1.7}.ar-textarea{width:100%;min-height:130px;padding:12px;font-size:15px;line-height:1.6;color:#2b332f;border:1px solid #d3d9d6;border-radius:8px;background:#fff;box-sizing:border-box;font-family:inherit;resize:vertical}.ar-textarea:focus{outline:none;border-color:#1a7a45;box-shadow:0 0 0 3px rgba(26,122,69,.12)}.ar-count{font-size:12px;color:#7a8c84;margin-top:6px;text-align:right}.ar-count.over{color:#c0392b;font-weight:600}.ar-field-error{display:none;color:#c0392b;font-size:12px;margin-top:6px}.ar-actions{display:flex;gap:10px;margin-top:18px;padding-top:18px;border-top:1px solid #eef1ef;flex-wrap:wrap}.ar-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:11px 22px;font-size:15px;font-weight:600;border-radius:8px;border:1px solid transparent;cursor:pointer;text-decoration:none}.ar-btn-primary{background:#0f5c33;color:#fff}.ar-btn-primary:hover{background:#1a7a45;color:#fff}html,body{scrollbar-width:none;-ms-overflow-style:none}html::-webkit-scrollbar,body::-webkit-scrollbar{display:none}
@media(min-width:1920px){.ar-title{font-size:30px}.ar-card{padding:30px}}@media(max-width:700px){.ar-title{font-size:20px}.ar-card{padding:16px;border-radius:10px}}@media(max-width:560px){.ar-actions{flex-direction:column}.ar-btn{width:100%;box-sizing:border-box}}
</style>
<?php if ($secmsg !== '') { ?><div class="message"><?php echo htmlspecialchars($secmsg); ?></div><?php } ?>
<?php if ($secmsg === '') { ?>
<div class="main-content">
    <div class="ar-header">
        <h1 class="ar-title">HOME PAGE TICKER TEXT</h1>
        <p class="ar-subtitle">Edit the scrolling ticker text shown below the video and images on the website home page. Saving always updates the same entry.</p>
    </div>
    <?php if ($flash !== '') { ?><div class="message <?php echo htmlspecialchars($flashType); ?>"><?php echo htmlspecialchars($flash); ?></div><?php } ?>

    <div class="ar-card">
        <div class="ar-card-title">Ticker Text</div>
        <?php if ($lastUpdated !== '') { ?><div class="ar-updated"><i class="fas fa-clock"></i> Last updated: <?php echo htmlspecialchars($lastUpdated); ?></div><?php } ?>
        <form id="arForm" method="post" action="turf-console/<?php echo htmlspecialchars($pageFile); ?>">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
            <div class="ar-hint">
                Plain text only (one line, maximum <?php echo (int)$maxContent; ?> characters). Formatting, links and line breaks are removed automatically because the ticker scrolls in a single line.
            </div>
            <textarea id="arContent" name="content" class="ar-textarea" maxlength="<?php echo (int)$maxContent; ?>"><?php echo htmlspecialchars($content, ENT_QUOTES, 'UTF-8'); ?></textarea>
            <div class="ar-count" id="arCount"></div>
            <div class="ar-field-error" id="arError"></div>
            <div class="ar-actions">
                <button type="submit" class="ar-btn ar-btn-primary"><i class="fas fa-save"></i> Save Text</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var form = document.getElementById('arForm');
    var area = document.getElementById('arContent');
    var count = document.getElementById('arCount');
    var error = document.getElementById('arError');
    var max = <?php echo (int)$maxContent; ?>;

    function updateCount() {
        var len = area.value.length;
        count.textContent = len + ' / ' + max + ' characters';
        count.className = 'ar-count' + (len > max ? ' over' : '');
    }
    area.addEventListener('input', function () {
        error.style.display = 'none';
        updateCount();
    });
    updateCount();

    form.addEventListener('submit', function (e) {
        area.value = area.value.trim();
        if (area.value === '') {
            e.preventDefault();
            error.textContent = 'Please write the ticker text.';
            error.style.display = 'block';
            area.focus();
        }
    });
})();
</script>
<?php } ?>
<?php
$design->closeDiv();
$design->writeLeftPanel();
$design->closeDiv();
$design->closeDiv();
$design->endPage();
$design = null;
?>