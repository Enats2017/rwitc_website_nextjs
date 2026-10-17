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
define('PA_SELF', basename($_SERVER['PHP_SELF']));   // this file's own name
$maxChars = 50000;   // max characters of saved HTML (keep the content_text column TEXT or MEDIUMTEXT)
$maxTitle = 200;     // max characters allowed in the title

// ---------------------------------------------------------------
// RICH TEXT HELPERS (sanitizer) - needs the PHP "dom" extension (php-xml)
// ---------------------------------------------------------------
// Tags that are allowed to stay. Everything else is unwrapped (text kept, tag removed).
function rwAllowedTags() {
    return array(
        'p' => 1, 'br' => 1, 'strong' => 1, 'b' => 1, 'em' => 1, 'i' => 1, 'u' => 1, 's' => 1,
        'sub' => 1, 'sup' => 1, 'span' => 1,
        'h2' => 1, 'h3' => 1, 'h4' => 1, 'blockquote' => 1, 'hr' => 1,
        'ul' => 1, 'ol' => 1, 'li' => 1, 'a' => 1,
        'table' => 1, 'thead' => 1, 'tbody' => 1, 'tfoot' => 1, 'tr' => 1, 'th' => 1, 'td' => 1,
        'caption' => 1, 'colgroup' => 1, 'col' => 1
    );
}

// Tags that are removed completely together with everything inside them.
function rwDroppedTags() {
    return array(
        'script' => 1, 'style' => 1, 'iframe' => 1, 'object' => 1, 'embed' => 1, 'form' => 1,
        'svg' => 1, 'math' => 1, 'link' => 1, 'meta' => 1, 'noscript' => 1, 'template' => 1,
        'textarea' => 1, 'select' => 1, 'button' => 1, 'input' => 1, 'applet' => 1,
        'base' => 1, 'title' => 1, 'head' => 1
    );
}

// Only these CSS properties (with safe values) survive in style="..."
function rwCleanStyle($style) {
    $rules = array(
        'text-align'      => '/^(left|right|center|justify)$/i',
        'text-decoration' => '/^(underline|line-through|none)$/i',
        'font-size'       => '/^\d{1,3}(\.\d+)?(px|pt|em|rem|%)$/i',
        'list-style-type' => '/^(none|disc|circle|square|decimal|lower-roman|upper-roman|lower-alpha|upper-alpha|lower-greek)$/i',
        'width'           => '/^\d{1,4}(\.\d+)?(px|%)$/i',
        'vertical-align'  => '/^(top|middle|bottom|baseline)$/i'
    );

    $out = array();
    foreach (explode(';', (string)$style) as $decl) {
        $parts = explode(':', $decl, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $prop = strtolower(trim($parts[0]));
        $val  = trim($parts[1]);
        if (isset($rules[$prop]) && preg_match($rules[$prop], $val)) {
            $out[] = $prop . ': ' . $val;
        }
    }
    return implode('; ', $out);
}

function rwCleanAttributes($el, $tag) {
    $names = array();
    foreach ($el->attributes as $a) {
        $names[] = $a->nodeName;
    }

    foreach ($names as $name) {
        $lname = strtolower($name);
        $val   = $el->getAttribute($name);
        $keep  = false;

        if ($lname === 'style') {
            $clean = rwCleanStyle($val);
            if ($clean !== '') {
                $el->setAttribute('style', $clean);
                $keep = true;
            }
        } elseif ($tag === 'a' && $lname === 'href') {
            // strip control chars/spaces first so "java\nscript:" tricks do not pass
            $test = preg_replace('/[\x00-\x20\x7f]+/', '', $val);
            if (preg_match('#^(https?:|mailto:|tel:|/|\#)#i', $test)) {
                $keep = true;
            }
        } elseif ($tag === 'a' && $lname === 'target') {
            $keep = ($val === '_blank');
        } elseif (($tag === 'td' || $tag === 'th') && ($lname === 'colspan' || $lname === 'rowspan')) {
            $keep = (bool)preg_match('/^\d{1,2}$/', $val);
        } elseif ($tag === 'table' && $lname === 'border') {
            $keep = (bool)preg_match('/^\d$/', $val);
        }

        if (!$keep) {
            $el->removeAttribute($name);
        }
    }

    if ($tag === 'a') {
        if ($el->getAttribute('target') === '_blank') {
            $el->setAttribute('rel', 'noopener noreferrer');
        } else {
            $el->removeAttribute('rel');
        }
    }
}

function rwCleanNode($node) {
    $allowed = rwAllowedTags();
    $drop    = rwDroppedTags();

    $children = array();
    foreach ($node->childNodes as $c) {
        $children[] = $c;
    }

    foreach ($children as $child) {
        if ($child->nodeType === XML_TEXT_NODE) {
            continue;
        }
        if ($child->nodeType !== XML_ELEMENT_NODE) {   // comments, CDATA, processing instructions
            $node->removeChild($child);
            continue;
        }

        $tag = strtolower($child->nodeName);

        if (isset($drop[$tag])) {
            $node->removeChild($child);
            continue;
        }

        rwCleanNode($child);   // clean the inside first

        if (!isset($allowed[$tag])) {
            // unknown tag: keep its (already cleaned) content, drop the tag itself
            while ($child->firstChild) {
                $node->insertBefore($child->firstChild, $child);
            }
            $node->removeChild($child);
            continue;
        }

        rwCleanAttributes($child, $tag);
    }
}

function rwSanitizeHtml($html) {
    $html = trim((string)$html);
    if ($html === '') {
        return '';
    }
    if (!class_exists('DOMDocument')) {
        return false;
    }

    $prev = libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $doc->loadHTML('<?xml encoding="utf-8" ?><div>' . $html . '</div>', LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);

    $root = $doc->getElementsByTagName('div')->item(0);
    if (!$root) {
        return '';
    }

    rwCleanNode($root);

    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $doc->saveHTML($child);
    }
    return trim($out);
}

// Visible text only (tags removed, entities decoded, nbsp treated as space)
function rwPlainText($html) {
    $text = html_entity_decode(strip_tags((string)$html), ENT_QUOTES, 'UTF-8');
    $text = preg_replace('/\x{00A0}/u', ' ', $text);
    return trim($text);
}


// Old content was plain text: "## " = heading, "- " = bullet, "Note:" = note, blank line = new block.
// If it has no HTML tags, convert it so the editor shows it properly.
function rwOldParaHtml($para) {
    $text = trim(implode(' ', $para));
    if ($text === '') {
        return '';
    }
    if (preg_match('/^note:\s*/i', $text)) {
        $rest = preg_replace('/^note:\s*/i', '', $text);
        return '<p><strong>Note:</strong> ' . htmlspecialchars($rest, ENT_QUOTES, 'UTF-8') . '</p>';
    }
    return '<p>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</p>';
}

function rwOldListHtml($items) {
    $out = '<ul>';
    foreach ($items as $it) {
        $out .= '<li>' . htmlspecialchars($it, ENT_QUOTES, 'UTF-8') . '</li>';
    }
    return $out . '</ul>';
}

function rwTextToHtml($text) {
    $value = trim((string)$text);
    if ($value === '') {
        return '';
    }
    if (preg_match('/<[a-z][^>]*>/i', $value)) {
        return $value;
    }

    $value = str_replace(array("\r\n", "\r"), "\n", $value);
    $out  = '';
    $para = array();
    $list = array();

    foreach (explode("\n", $value) as $line) {
        $t = trim($line);

        if ($t === '') {
            $out .= rwOldParaHtml($para);
            $para = array();
            if (count($list)) { $out .= rwOldListHtml($list); $list = array(); }
            continue;
        }

        if (preg_match('/^##\s+/', $t)) {
            $out .= rwOldParaHtml($para);
            $para = array();
            if (count($list)) { $out .= rwOldListHtml($list); $list = array(); }
            $out .= '<h2>' . htmlspecialchars(preg_replace('/^##\s+/', '', $t), ENT_QUOTES, 'UTF-8') . '</h2>';
            continue;
        }

        if (preg_match('/^(-|\*|\x{2022})\s+/u', $t)) {
            $out .= rwOldParaHtml($para);
            $para = array();
            $list[] = preg_replace('/^(-|\*|\x{2022})\s+/u', '', $t);
            continue;
        }

        if (count($list)) { $out .= rwOldListHtml($list); $list = array(); }
        $para[] = $t;
    }

    $out .= rwOldParaHtml($para);
    if (count($list)) { $out .= rwOldListHtml($list); }
    return $out;
}

// ---------------------------------------------------------------
// LOGIN + MODULE ACCESS CHECK
// ---------------------------------------------------------------
$secmsg = '';
if (!isAdminlogin()) {
    $secmsg = "You do not have access to this page.";
} elseif (!hasModuleAccess('partners_advertising')) {
    $secmsg = "You do not have access to this module.";
}

// CSRF token
if (empty($_SESSION['partners_advertising_csrf'])) {
    $_SESSION['partners_advertising_csrf'] = bin2hex(openssl_random_pseudo_bytes(16));
}
$csrf = $_SESSION['partners_advertising_csrf'];

// FLASH MESSAGE
$flash = '';
$flashType = '';
if (isset($_SESSION['partners_advertising_flash'])) {
    $flash = $_SESSION['partners_advertising_flash']['text'];
    $flashType = $_SESSION['partners_advertising_flash']['type'];
    unset($_SESSION['partners_advertising_flash']);
}

// ---------------------------------------------------------------
// HELPERS
// ---------------------------------------------------------------
function paSetFlash($text, $type)
{
    $_SESSION['partners_advertising_flash'] = array('text' => $text, 'type' => $type);
}

// ---------------------------------------------------------------
// POST ACTION (single row: insert the first time, update afterwards)
// ---------------------------------------------------------------
if (empty($secmsg) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    $redirect = PA_SELF;

    // CSRF check
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['partners_advertising_csrf'], (string)$_POST['csrf'])) {
        paSetFlash('Invalid request. Please try again.', 'error');
        header('Location: ' . $redirect);
        exit;
    }

    $date  = isset($_POST['report_date']) ? trim($_POST['report_date']) : '';
    $title = isset($_POST['report_title']) ? trim($_POST['report_title']) : '';
    $text  = isset($_POST['report_text']) ? trim($_POST['report_text']) : '';
    $err   = '';

    // The editor sends HTML. Never trust it: clean it on the server before saving.
    $text = rwSanitizeHtml($text);

    $d = DateTime::createFromFormat('Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) {
        $err = 'Please select a valid date.';
    } elseif ($text === false) {
        $err = 'Server cannot process the content (PHP DOM extension is missing).';
    } elseif (rwPlainText($text) === '') {
        $err = 'Text is required.';
    } elseif (mb_strlen($text) > $maxChars) {
        $err = 'Text is too long (max ' . $maxChars . ' characters).';
    } elseif (mb_strlen($title) > $maxTitle) {
        $err = 'Title is too long (max ' . $maxTitle . ' characters).';
    }

    if ($err === '') {

        // Title is optional: NULL is stored when it is left blank
        $titleSql = ($title !== '') ? "'" . $db->escape($title) . "'" : "NULL";
        $textSql  = "'" . $db->escape($text) . "'";

        // Always look up the existing row on the server (never trust a posted id)
        $existing   = $db->getSingleRowAssoc("SELECT id FROM partners_advertising WHERE type = 'text' ORDER BY report_date DESC, id DESC LIMIT 1");
        $existingId = $existing ? (int)$existing['id'] : 0;

        try {
            if ($existingId > 0) {
                // Row already exists -> update it, never insert another one
                $db->update("UPDATE partners_advertising SET
                                report_date = '" . $db->escape($date) . "',
                                title = $titleSql,
                                type = 'text',
                                content_text = $textSql,
                                file_name = NULL
                             WHERE id = $existingId");

                // Keep only this one row, so the website can never read an old one
                $db->query("DELETE FROM partners_advertising WHERE id <> $existingId");

                paSetFlash('Updated successfully.', 'success');
            } else {
                // Table has no text row -> first and only insert
                $db->insert("INSERT INTO partners_advertising (report_date, title, type, content_text, file_name)
                             VALUES ('" . $db->escape($date) . "',
                                     $titleSql,
                                     'text',
                                     $textSql,
                                     NULL)");
                paSetFlash('Saved successfully.', 'success');
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            paSetFlash('Database error. Could not save.', 'error');
        }
    } else {
        paSetFlash($err, 'error');
    }

    header('Location: ' . $redirect);
    exit;
}

// ---------------------------------------------------------------
// PAGE DATA (the single row)
// ---------------------------------------------------------------
$row = null;

if (empty($secmsg)) {
    $row = $db->getSingleRowAssoc(
        "SELECT id, report_date, title, content_text
         FROM partners_advertising
         WHERE type = 'text'
         ORDER BY report_date DESC, id DESC
         LIMIT 1"
    );
}

$hasRow = !empty($row);

// default form values
$fDate  = $hasRow ? $row['report_date'] : date('Y-m-d');
$fTitle = $hasRow ? (string)$row['title'] : '';
$fText  = $hasRow ? rwTextToHtml($row['content_text']) : '';

// PAGE TITLE
$pageTitle = 'Partners - Advertising & Sponsorship Opportunities';

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
    .ar-card.editing { border-color: #1a7a45; box-shadow: 0 0 0 3px rgba(26, 122, 69, 0.10); }

    .ar-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .ar-field { margin-bottom: 18px; min-width: 0; }
    .ar-field label { display: block; font-size: 13px; font-weight: 600; color: #2b332f; margin-bottom: 6px; }
    .ar-field label .req { color: #c0392b; }
    .ar-field label .opt { color: #7a8c84; font-weight: 500; }
    .ar-input, .ar-textarea { width: 100%; box-sizing: border-box; padding: 10px 12px; font-size: 15px; font-family: inherit; color: #2b332f; background: #fff; border: 1px solid #d3d9d6; border-radius: 8px; outline: none; transition: border-color .15s ease, box-shadow .15s ease; }
    .ar-input::placeholder, .ar-textarea::placeholder { color: #9aa9a2; }
    .ar-input:focus, .ar-textarea:focus { border-color: #1a7a45; box-shadow: 0 0 0 3px rgba(26, 122, 69, 0.15); }
    .ar-textarea { min-height: 320px; }
    .ar-hint { font-size: 12px; color: #7a8c84; margin-top: 6px; display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap; line-height: 1.6; }
    .ar-input.invalid, .ar-textarea.invalid { border-color: #c0392b; }
    .ar-err { color: #c0392b; font-size: 12px; margin-top: 5px; display: none; }
    .ar-err.show { display: block; }

    /* editor look */
    .tox-tinymce { border: 1px solid #d3d9d6 !important; border-radius: 8px !important; }
    .tox-tinymce.ar-invalid { border-color: #c0392b !important; }

    .ar-actions { display: flex; gap: 10px; margin-top: 8px; padding-top: 18px; border-top: 1px solid #eef1ef; flex-wrap: wrap; }
    .ar-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 11px 22px; font-size: 15px; font-weight: 600; font-family: inherit; border-radius: 8px; border: 1px solid transparent; cursor: pointer; transition: all .15s ease; text-decoration: none; }
    .ar-btn-primary { background: #0f5c33; color: #fff; }
    .ar-btn-primary:hover { background: #1a7a45; box-shadow: 0 4px 10px rgba(15, 92, 51, 0.25); color: #fff; }
    .ar-btn-secondary { background: #fff; color: #2b332f; border-color: #d3d9d6; }
    .ar-btn-secondary:hover { border-color: #7a8c84; color: #2b332f; }

    html, body { scrollbar-width: none; -ms-overflow-style: none; }
    html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }

    @media (min-width: 1920px) {
        .ar-title { font-size: 30px; }
        .ar-card { max-width: 1100px; padding: 30px; }
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
                <h1 class="ar-title">ADVERTISING &amp; SPONSORSHIP OPPORTUNITIES</h1>
                <p class="ar-subtitle">Edit the content shown on the website page. Saving always updates the same entry.</p>
            </div>
        </div>

        <?php if ($flash !== '') { ?>
            <div class="message <?php echo htmlspecialchars($flashType); ?>">
                <?php echo htmlspecialchars($flash); ?>
            </div>
        <?php } ?>

        <div id="arMsg" class="message" style="display:none;"></div>


        <!-- ===================== ADD / EDIT FORM (single row) ===================== -->
        <div class="ar-card<?php echo $hasRow ? ' editing' : ''; ?>">

            <div class="ar-card-title">
                <?php echo $hasRow ? 'Edit Advertising & Sponsorship Opportunities' : 'Add Advertising & Sponsorship Opportunities'; ?>
            </div>

            <form id="arForm" method="post" action="<?php echo htmlspecialchars('turf-console/' . PA_SELF); ?>" novalidate>

                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">

                <div class="ar-row">

                    <div class="ar-field">
                        <label for="arDate">Date <span class="req">*</span></label>
                        <input type="date" id="arDate" name="report_date" class="ar-input" value="<?php echo htmlspecialchars($fDate); ?>">
                        <div class="ar-err" id="errDate">Please select a date.</div>
                    </div>

                    <div class="ar-field">
                        <label for="arTitle">Title <span class="opt">(optional)</span></label>
                        <input type="text" id="arTitle" name="report_title" class="ar-input" maxlength="<?php echo (int)$maxTitle; ?>" placeholder="Enter title (optional)" value="<?php echo htmlspecialchars($fTitle); ?>">
                        <div class="ar-err" id="errTitle">Title is too long.</div>
                    </div>

                </div>

                <div class="ar-field">
                    <label for="arText">Text <span class="req">*</span></label>
                    <textarea id="arText" name="report_text" class="ar-textarea"><?php echo htmlspecialchars($fText); ?></textarea>
                    <div class="ar-hint">
                        <span>
                            Use the toolbar for Bold / Italic / Underline &nbsp;|&nbsp; Heading 2 = main underlined heading &nbsp;|&nbsp; Heading 3 = green underlined sub-heading<br>
                            Create bullet or numbered lists from the toolbar. For (i), (ii), (iii) choose "Lower Roman" from the numbered list arrow. Tables: make the first row a header row (Table &gt; Row &gt; Row properties &gt; Row type: Header).
                        </span>
                        <span id="arCount">0 characters</span>
                    </div>
                    <div class="ar-err" id="errText">Please write some text.</div>
                </div>

                <div class="ar-actions">
                    <button type="submit" class="ar-btn ar-btn-primary">
                        <i class="fas fa-save"></i> <?php echo $hasRow ? 'Update' : 'Save'; ?>
                    </button>
                    <button type="button" class="ar-btn ar-btn-secondary" id="arReset">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                </div>

            </form>

        </div>

    </div>


    <!-- If your About manager already loads TinyMCE from somewhere else, use the same <script> line here. -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js" referrerpolicy="origin"></script>

    <script type="text/javascript">
        (function() {

            var form     = document.getElementById('arForm');
            var dateIn   = document.getElementById('arDate');
            var titleIn  = document.getElementById('arTitle');
            var txtArea  = document.getElementById('arText');
            var counter  = document.getElementById('arCount');
            var resetBtn = document.getElementById('arReset');
            var msgBox   = document.getElementById('arMsg');
            var savedText = txtArea.value;
            var maxChars = <?php echo (int)$maxChars; ?>;
            var maxTitle = <?php echo (int)$maxTitle; ?>;
            var msgTimer = null;

            function editor() {
                return (window.tinymce && tinymce.get('arText')) ? tinymce.get('arText') : null;
            }

            function plainText() {
                var ed = editor();
                return (ed ? ed.getContent({ format: 'text' }) : txtArea.value).replace(/\u00a0/g, ' ').trim();
            }

            function htmlLength() {
                var ed = editor();
                return (ed ? ed.getContent() : txtArea.value).length;
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
                var e = document.getElementById(errId);
                if (text) e.textContent = text;
                e.classList.toggle('show', show);
                if (inputEl) inputEl.classList.toggle('invalid', show);
            }

            function setTextErr(show, text) {
                var e = document.getElementById('errText');
                if (text) e.textContent = text;
                e.classList.toggle('show', show);
                var ed = editor();
                if (ed) {
                    ed.getContainer().classList.toggle('ar-invalid', show);
                } else {
                    txtArea.classList.toggle('invalid', show);
                }
            }

            function updateCount() {
                counter.textContent = plainText().length + ' characters';
                if (plainText()) setTextErr(false);
            }

            dateIn.addEventListener('change', function() {
                if (this.value) setErr('errDate', this, false);
            });

            titleIn.addEventListener('input', function() {
                if (titleIn.value.trim().length <= maxTitle) setErr('errTitle', titleIn, false);
            });

            tinymce.init({
                selector: '#arText',
                height: 480,
                menubar: false,
                branding: false,
                promotion: false,
                plugins: 'advlist lists link table code',
                toolbar: 'undo redo | blocks fontsize | bold italic underline | alignleft aligncenter alignright | bullist numlist | link table | removeformat | code',
                block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3',
                font_size_formats: '12px 13px 14px 15px 16px 18px 20px 24px',
                advlist_number_styles: 'default,lower-alpha,lower-roman,upper-alpha,upper-roman',
                link_default_target: '_blank',
                convert_urls: false,
                relative_urls: false,
                content_style:
                    'body{font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#2b332f;padding:8px 12px}' +
                    'h2{font-size:22px;font-weight:800;text-decoration:underline}' +
                    'h3{font-size:19px;font-weight:800;color:#16a34a;text-decoration:underline}' +
                    'table{border-collapse:collapse;width:100%}th,td{border:1px solid #d3d9d6;padding:8px}' +
                    'thead th,thead td{background:#d9ead9;color:#14532d;font-weight:800}',
                setup: function(ed) {
                    ed.on('init', updateCount);
                    ed.on('input change keyup SetContent Undo Redo', updateCount);
                }
            });

            if (resetBtn) {
                resetBtn.addEventListener('click', function() {
                    form.reset();   // restores the saved date and title
                    var ed = editor();
                    if (ed) ed.setContent(savedText); else txtArea.value = savedText;
                    updateCount();
                    setErr('errDate', dateIn, false);
                    setErr('errTitle', titleIn, false);
                    setTextErr(false);
                });
            }

            form.addEventListener('submit', function(e) {
                if (window.tinymce) tinymce.triggerSave();   // copy editor HTML into the textarea

                var ok = true;

                if (!dateIn.value) {
                    setErr('errDate', dateIn, true);
                    ok = false;
                }
                if (titleIn.value.trim().length > maxTitle) {
                    setErr('errTitle', titleIn, true, 'Title is too long (max ' + maxTitle + ' characters).');
                    ok = false;
                }
                if (!plainText()) {
                    setTextErr(true, 'Please write some text.');
                    ok = false;
                } else if (htmlLength() > maxChars) {
                    setTextErr(true, 'Text is too long (max ' + maxChars + ' characters).');
                    ok = false;
                }

                if (!ok) {
                    e.preventDefault();
                    showMsg('Please fill all required fields correctly.', 'error');
                }
            });

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