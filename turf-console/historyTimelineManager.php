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
$maxContent = 120000;   // max size of the cleaned HTML (bytes)
$maxTitle   = 200;      // max characters allowed in the title

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

// Old plain-text content -> HTML.
// Timeline tab: every non-empty line ("Year | Text") becomes one paragraph.
// Other tab: a blank line separates paragraphs, single line breaks stay as <br>.
// If the content already contains HTML tags it is returned unchanged.
function historyLegacyToHtml($text, $isTimeline)
{
    $text = (string)$text;
    if (preg_match('/<[a-z][^>]*>/i', $text)) {
        return $text;
    }
    $text = str_replace(array("\r\n", "\r"), "\n", trim($text));
    if ($text === '') {
        return '';
    }

    $out = '';
    if ($isTimeline) {
        foreach (explode("\n", $text) as $line) {
            $line = trim($line);
            if ($line !== '') {
                $out .= '<p>' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</p>';
            }
        }
    } else {
        foreach (preg_split('/\n\s*\n/', $text) as $part) {
            $part = trim($part);
            if ($part !== '') {
                $out .= '<p>' . nl2br(htmlspecialchars($part, ENT_QUOTES, 'UTF-8')) . '</p>';
            }
        }
    }
    return $out;
}

// HTML sanitizer (allow-list). Keeps bold/italic/lists/links/tables, text-align, font-size and list style only.
function historySanitizeHtml($html)
{
    $html = trim((string)$html);
    if ($html === '') {
        return '';
    }

    $allowedTags = array('p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'sub', 'sup', 'span', 'div',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'blockquote', 'hr',
        'a', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th');
    $dropTags = array('script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button',
        'textarea', 'select', 'link', 'meta', 'svg', 'math', 'head', 'title', 'xml', 'img');

    $previous = libxml_use_internal_errors(true);
    $doc = new DOMDocument('1.0', 'UTF-8');
    $doc->loadHTML('<?xml encoding="utf-8" ?><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    $root = $doc->getElementsByTagName('div')->item(0);
    if (!$root) {
        return '';
    }

    $clean = function (DOMNode $node) use (&$clean, $allowedTags, $dropTags) {
        $children = array();
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }
        foreach ($children as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                continue;
            }
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                $node->removeChild($child);
                continue;
            }
            $tag = strtolower($child->nodeName);
            if (in_array($tag, $dropTags, true)) {
                $node->removeChild($child);
                continue;
            }
            $clean($child);
            if (!in_array($tag, $allowedTags, true)) {
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }

            $href = $child->getAttribute('href');
            $style = $child->getAttribute('style');
            $colspan = (int)$child->getAttribute('colspan');
            $rowspan = (int)$child->getAttribute('rowspan');
            while ($child->attributes->length > 0) {
                $child->removeAttributeNode($child->attributes->item(0));
            }

            if ($tag === 'a' && preg_match('#^(https?://|mailto:|tel:)#i', trim($href))) {
                $child->setAttribute('href', trim($href));
                $child->setAttribute('target', '_blank');
                $child->setAttribute('rel', 'noopener noreferrer');
            }
            if ($tag === 'td' || $tag === 'th') {
                if ($colspan > 1) $child->setAttribute('colspan', (string)$colspan);
                if ($rowspan > 1) $child->setAttribute('rowspan', (string)$rowspan);
            }

            $safeStyle = '';
            if ($style !== '' && preg_match('/text-align\s*:\s*(left|right|center|justify)/i', $style, $m)) {
                $safeStyle .= 'text-align: ' . strtolower($m[1]) . ';';
            }
            if ($style !== '' && preg_match('/font-size\s*:\s*(\d+(?:\.\d+)?)(px|pt|em|rem|%)/i', $style, $m) && (float)$m[1] > 0 && (float)$m[1] <= 200) {
                $safeStyle .= ($safeStyle !== '' ? ' ' : '') . 'font-size: ' . $m[1] . strtolower($m[2]) . ';';
            }
            if (($tag === 'ol' || $tag === 'ul') && $style !== '' && preg_match('/list-style-type\s*:\s*(decimal|lower-roman|upper-roman|lower-alpha|upper-alpha|lower-greek|disc|circle|square)/i', $style, $m)) {
                $safeStyle .= ($safeStyle !== '' ? ' ' : '') . 'list-style-type: ' . strtolower($m[1]) . ';';
            }
            if ($safeStyle !== '') {
                $child->setAttribute('style', $safeStyle);
            }
        }
    };
    $clean($root);

    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $doc->saveHTML($child);
    }
    return trim($out);
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

    $title = isset($_POST['report_title']) && is_string($_POST['report_title']) ? trim($_POST['report_title']) : '';
    $raw   = isset($_POST['content']) && is_string($_POST['content']) ? $_POST['content'] : '';
    $text  = historySanitizeHtml($raw);

    $err = '';
    if ($text === '') {
        $err = 'Content is required.';
    } elseif (strlen($text) > $maxContent) {
        $err = 'Content is too long. Please shorten it.';
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
        $fText  = historyLegacyToHtml($row['content_text'], $isTimeline);
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

    .ar-card { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; padding: 24px; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03); max-width: 100%; margin-bottom: 26px; }
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
    .ar-hint { font-size: 12px; color: #7a8c84; margin-top: 6px; margin-bottom: 10px; line-height: 1.7; }
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

    html, body { scrollbar-width: none; -ms-overflow-style: none; }
    html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }

    @media (min-width: 1920px) {
        .ar-title { font-size: 30px; }
        .ar-card { padding: 30px; }
    }
    @media (max-width: 700px) {
        .ar-title { font-size: 20px; }
        .ar-card { padding: 16px; border-radius: 10px; }
    }
    @media (max-width: 560px) {
        .ar-header { flex-direction: column; align-items: flex-start; margin-bottom: 14px; gap: 8px; }
        .ar-subtitle { font-size: 13px; }
        .ar-actions { flex-direction: column; }
        .ar-btn { width: 100%; }
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
                <p class="ar-subtitle"><?php echo htmlspecialchars($secName); ?> &mdash; edit the content shown on the website page</p>
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
                    <input type="text" id="arTitle" name="report_title" class="ar-input" maxlength="<?php echo (int)$maxTitle; ?>" placeholder="Enter title (optional)" value="<?php echo htmlspecialchars($fTitle, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="ar-hint">Leave this field empty to display the default heading on the website.</div>
                    <div class="ar-err" id="errTitle">Title is too long.</div>
                </div>

                <div class="ar-field">
                    <label for="arContent">Content <span class="req">*</span></label>
                    <div class="ar-hint">
                        <?php if ($isTimeline) { ?>
                            <b>One paragraph = one event.</b> Format: <b>Year | Text</b> (for example, <b>1798 | A two-day race meeting...</b>). Press Enter to start the next event. The <b>|</b> symbol must come right after the year.<br>
                        <?php } else { ?>
                            Each paragraph shows as a separate block on the website.<br>
                        <?php } ?>
                        Use the Bold / Italic / Underline toolbar for text styling &nbsp;|&nbsp; Heading 2 = main underlined heading &nbsp;|&nbsp; Heading 3 = green underlined sub-heading<br>
                        Numbered list arrow = choose "Lower Roman" for (i), (ii), (iii). Tables: make the first row a header row (Table &gt; Row &gt; Row properties &gt; Row type: Header).
                    </div>
                    <textarea id="arContent" name="content"><?php echo htmlspecialchars($fText, ENT_QUOTES, 'UTF-8'); ?></textarea>
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

    </div>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
    <script type="text/javascript">
        (function() {

            var maxTitle     = <?php echo (int)$maxTitle; ?>;
            var savedContent = <?php echo json_encode($fText); ?>;

            var form    = document.getElementById('arForm');
            var titleIn = document.getElementById('arTitle');
            var area    = document.getElementById('arContent');
            var revert  = document.getElementById('arRevert');
            var msgBox  = document.getElementById('arMsg');
            var savedTitle = titleIn.value;
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
                if (!e) return;
                if (text) e.textContent = text;
                e.classList.toggle('show', show);
                if (inputEl) inputEl.classList.toggle('invalid', show);
            }

            titleIn.addEventListener('input', function() {
                if (titleIn.value.trim().length <= maxTitle) setErr('errTitle', titleIn, false);
            });

            tinymce.init({
                selector: '#arContent',
                height: 620,
                menubar: false,
                branding: false,
                promotion: false,
                plugins: 'lists advlist link table code',
                toolbar: 'undo redo | blocks fontsize | bold italic underline | alignleft aligncenter alignright | bullist numlist | link table | removeformat | code',
                block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3; Heading 4=h4',
                font_size_formats: '12px 14px 16px 18px 20px 24px 28px 32px',
                advlist_number_styles: 'default,lower-roman,upper-roman,lower-alpha',
                table_header_type: 'sectionCells',
                paste_data_images: false,
                paste_remove_styles_if_webkit: true,
                content_style: 'body { font-family: Arial, sans-serif; font-size: 15px; line-height: 1.7; } table { border-collapse: collapse; width: 100%; } td, th { border: 1px solid #ccc; padding: 6px 8px; }'
            });

            revert.addEventListener('click', function() {
                titleIn.value = savedTitle;
                setErr('errTitle', titleIn, false);
                var ed = tinymce.get('arContent');
                if (ed) ed.setContent(savedContent);
                setErr('errContent', null, false);
            });

            form.addEventListener('submit', function(e) {
                var t = titleIn.value.trim();

                if (t.length > maxTitle) {
                    e.preventDefault();
                    setErr('errTitle', titleIn, true, 'Title is too long (max ' + maxTitle + ' characters).');
                    showMsg('Title is too long.', 'error');
                    return;
                }

                // Copy the editor content into the textarea, then check it is not empty
                tinymce.triggerSave();
                if (!area.value.replace(/<[^>]*>|&nbsp;|\s/g, '')) {
                    e.preventDefault();
                    setErr('errContent', null, true, 'Please write some content.');
                    showMsg('Please fill all required fields correctly.', 'error');
                } else {
                    setErr('errContent', null, false);
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
$design = NULL;

?>