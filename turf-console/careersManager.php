<?php

include_once('../bootstrap.php');
require_once("../lib/users.class.php");
require_once("../lib/userchecks.php");
require_once("../lib/permissions.php");

session_start();

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

// Old content was plain text (blank line = new paragraph). If it has no HTML tags, convert it.
function rwTextToHtml($text) {
    $value = trim((string)$text);
    if ($value === '') {
        return '';
    }
    if (preg_match('/<[a-z][^>]*>/i', $value)) {
        return $value;
    }

    $value = str_replace(array("\r\n", "\r"), "\n", $value);
    $out = '';
    foreach (preg_split('/\n\s*\n/', $value) as $p) {
        $p = trim($p);
        if ($p === '') {
            continue;
        }
        $out .= '<p>' . nl2br(htmlspecialchars($p, ENT_QUOTES, 'UTF-8')) . '</p>';
    }
    return $out;
}


$userObj = new Users($db);

// ---------------------------------------------------------------
// SETTINGS
// ---------------------------------------------------------------
$maxChars = 50000;   // max characters of saved HTML (keep the content_text column TEXT or MEDIUMTEXT)
$maxTitle = 200;     // max characters allowed in the title

// ---------------------------------------------------------------
// LOGIN + MODULE ACCESS CHECK
// ---------------------------------------------------------------
$secmsg = '';
if (!isAdminlogin()) {
    $secmsg = "You do not have access to this page.";
} elseif (!hasModuleAccess('careers')) {
    $secmsg = "You do not have access to this module.";
}

// CSRF token
if (empty($_SESSION['careers_csrf'])) {
    $_SESSION['careers_csrf'] = bin2hex(openssl_random_pseudo_bytes(16));
}
$csrf = $_SESSION['careers_csrf'];

// FLASH MESSAGE
$flash = '';
$flashType = '';
if (isset($_SESSION['careers_flash'])) {
    $flash = $_SESSION['careers_flash']['text'];
    $flashType = $_SESSION['careers_flash']['type'];
    unset($_SESSION['careers_flash']);
}

// ---------------------------------------------------------------
// SAVE (the page always uses exactly one row: id = 1)
// ---------------------------------------------------------------
if (empty($secmsg) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['careers_csrf'], (string)$_POST['csrf'])) {
        $_SESSION['careers_flash'] = array('text' => 'Invalid request. Please try again.', 'type' => 'error');
        header('Location: careersManager.php');
        exit;
    }

    $title   = isset($_POST['report_title']) ? trim($_POST['report_title']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';

    // The editor sends HTML. Never trust it: clean it on the server before saving.
    $content = rwSanitizeHtml($content);

    $err = '';
    if ($content === false) {
        $err = 'Server cannot process the content (PHP DOM extension is missing).';
    } elseif (rwPlainText($content) === '') {
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
                "INSERT INTO careers (id, report_date, title, `type`, content_text, file_name)
                 VALUES (1, CURDATE(), $titleSql, 'text', '" . $db->escape($content) . "', NULL)
                 ON DUPLICATE KEY UPDATE
                    report_date = CURDATE(),
                    title = VALUES(title),
                    `type` = 'text',
                    content_text = VALUES(content_text),
                    file_name = NULL"
            );
            $_SESSION['careers_flash'] = array('text' => 'Content updated successfully.', 'type' => 'success');
        } catch (Exception $e) {
            error_log('Careers save error: ' . $e->getMessage());
            $_SESSION['careers_flash'] = array('text' => 'Database error. Could not save.', 'type' => 'error');
        }
    } else {
        $_SESSION['careers_flash'] = array('text' => $err, 'type' => 'error');
    }

    header('Location: careersManager.php');
    exit;
}

// ---------------------------------------------------------------
// LOAD CURRENT CONTENT
// ---------------------------------------------------------------
$currentTitle = '';
$currentContent = '';
$lastUpdated = '';

if (empty($secmsg)) {
    $row = $db->getSingleRowAssoc("SELECT title, content_text, report_date FROM careers WHERE id = 1");
    if ($row) {
        $currentTitle = (string)$row['title'];
        // old plain text content is converted to <p> blocks so the editor shows it properly
        $currentContent = rwTextToHtml($row['content_text']);
        $lastUpdated = $row['report_date'] ? date('d M Y', strtotime($row['report_date'])) : '';
    }
}

// PAGE TITLE
$pageTitle = 'Careers';

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
    .ar-textarea { width: 100%; box-sizing: border-box; padding: 12px 14px; font-size: 15px; font-family: inherit; min-height: 340px; border: 1px solid #d3d9d6; border-radius: 8px; }
    .ar-hint { font-size: 12px; color: #7a8c84; margin-top: 6px; display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap; line-height: 1.6; }
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

    .ar-updated { font-size: 13px; color: #7a8c84; margin-bottom: 14px; }
    .ar-updated i { color: #0f5c33; margin-right: 4px; }

    html, body { scrollbar-width: none; -ms-overflow-style: none; }
    html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }

    @media (min-width: 1920px) {
        .ar-title { font-size: 30px; }
        .ar-card { max-width: 1100px; padding: 30px; }
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
                <h1 class="ar-title">CAREERS</h1>
                <p class="ar-subtitle">Edit the content shown on the website page. Saving always updates the same entry.</p>
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

            <form id="arForm" method="post" action="turf-console/careersManager.php" novalidate>

                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">

                <div class="ar-field">
                    <label for="arTitle">Title <span class="opt">(optional)</span></label>
                    <input type="text" id="arTitle" name="report_title" class="ar-input" maxlength="<?php echo (int)$maxTitle; ?>" placeholder="Enter title (optional)" value="<?php echo htmlspecialchars($currentTitle); ?>">
                    <div class="ar-hint"><span>Leave this field empty to display the default heading on the website.</span></div>
                    <div class="ar-err" id="errTitle">Title is too long.</div>
                </div>

                <div class="ar-field">
                    <label for="arContent">Content <span class="req">*</span></label>
                    <textarea id="arContent" name="content" class="ar-textarea"><?php echo htmlspecialchars($currentContent); ?></textarea>
                    <div class="ar-hint">
                        <span>
                            Bold / Italic / Underline toolbar se karo &nbsp;|&nbsp; Heading 2 = main underlined heading &nbsp;|&nbsp; Heading 3 = green underlined sub-heading<br>
                            Numbered list arrow = choose "Lower Roman" for (i), (ii), (iii). Tables: first row ko header row banao (Table &gt; Row &gt; Row properties &gt; Row type: Header).
                        </span>
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

    </div>


    <!-- If your About manager already loads TinyMCE from somewhere else, use the same <script> line here. -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js" referrerpolicy="origin"></script>

    <script type="text/javascript">
        (function() {

            var form    = document.getElementById('arForm');
            var titleIn = document.getElementById('arTitle');
            var area    = document.getElementById('arContent');
            var counter = document.getElementById('arCount');
            var revert  = document.getElementById('arRevert');
            var msgBox  = document.getElementById('arMsg');
            var savedTitle   = titleIn.value;
            var savedContent = area.value;
            var maxChars = <?php echo (int)$maxChars; ?>;
            var maxTitle = <?php echo (int)$maxTitle; ?>;
            var msgTimer = null;

            function editor() {
                return (window.tinymce && tinymce.get('arContent')) ? tinymce.get('arContent') : null;
            }

            function plainText() {
                var ed = editor();
                return (ed ? ed.getContent({ format: 'text' }) : area.value).replace(/\u00a0/g, ' ').trim();
            }

            function htmlLength() {
                var ed = editor();
                return (ed ? ed.getContent() : area.value).length;
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

            function setTitleErr(show, text) {
                var e = document.getElementById('errTitle');
                if (text) e.textContent = text;
                e.classList.toggle('show', show);
                titleIn.classList.toggle('invalid', show);
            }

            function setContentErr(show, text) {
                var e = document.getElementById('errContent');
                if (text) e.textContent = text;
                e.classList.toggle('show', show);
                var ed = editor();
                var box = ed ? ed.getContainer() : area;
                if (box) box.classList.toggle(ed ? 'ar-invalid' : 'invalid', show);
            }

            function update() {
                counter.textContent = plainText().length + ' characters';
                if (plainText()) setContentErr(false);
                if (titleIn.value.trim().length <= maxTitle) setTitleErr(false);
            }

            titleIn.addEventListener('input', update);

            tinymce.init({
                selector: '#arContent',
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
                    ed.on('init', update);
                    ed.on('input change keyup SetContent Undo Redo', update);
                }
            });

            revert.addEventListener('click', function() {
                titleIn.value = savedTitle;
                var ed = editor();
                if (ed) ed.setContent(savedContent); else area.value = savedContent;
                setContentErr(false);
                setTitleErr(false);
                update();
            });

            form.addEventListener('submit', function(e) {
                if (window.tinymce) tinymce.triggerSave();   // copy editor HTML into the textarea

                var t = titleIn.value.trim();

                if (!plainText()) {
                    e.preventDefault();
                    setContentErr(true, 'Please write some content.');
                    showMsg('Please fill all required fields correctly.', 'error');
                    return;
                }
                if (htmlLength() > maxChars) {
                    e.preventDefault();
                    setContentErr(true, 'Content is too long (max ' + maxChars + ' characters).');
                    showMsg('Content is too long.', 'error');
                    return;
                }
                if (t.length > maxTitle) {
                    e.preventDefault();
                    setTitleErr(true, 'Title is too long (max ' + maxTitle + ' characters).');
                    showMsg('Title is too long.', 'error');
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