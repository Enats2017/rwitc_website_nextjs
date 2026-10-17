<?php
include_once('../bootstrap.php');
require_once('../lib/users.class.php');
require_once('../lib/userchecks.php');
require_once('../lib/permissions.php');

session_start();
$userObj = new Users($db);

$pageTitle  = 'Vision & Mission';
$moduleKey  = 'vision_mission';
$pageFile   = 'visionMissionManager.php';
$maxContent = 120000;   // max size of the cleaned HTML (bytes)
$maxTitle   = 200;      // max characters allowed in the title

// ---------------------------------------------------------------
// LOGIN + MODULE ACCESS CHECK
// ---------------------------------------------------------------
$secmsg = '';
if (!isAdminlogin()) {
    $secmsg = 'You do not have access to this page.';
} elseif (!hasModuleAccess($moduleKey)) {
    $secmsg = 'You do not have access to this module.';
}

// CSRF token
if (empty($_SESSION['vision_csrf'])) {
    $_SESSION['vision_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['vision_csrf'];

// FLASH MESSAGE
$flash = isset($_SESSION['vision_flash']['text']) ? $_SESSION['vision_flash']['text'] : '';
$flashType = isset($_SESSION['vision_flash']['type']) ? $_SESSION['vision_flash']['type'] : '';
unset($_SESSION['vision_flash']);

// ---------------------------------------------------------------
// Old plain-text content -> HTML.
// Blank line = new block. A block where every line starts with "- " (or a bullet)
// becomes a bullet list, any other block becomes a paragraph.
// If the content already contains HTML tags it is returned unchanged.
// ---------------------------------------------------------------
function visionLegacyToHtml($text)
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
    foreach (preg_split('/\n\s*\n/', $text) as $block) {
        $block = trim($block);
        if ($block === '') {
            continue;
        }

        $lines = array();
        foreach (explode("\n", $block) as $line) {
            $line = trim($line);
            if ($line !== '') {
                $lines[] = $line;
            }
        }
        if (empty($lines)) {
            continue;
        }

        $allBullets = true;
        foreach ($lines as $line) {
            if (!preg_match('/^(?:-|\x{2022})\s+/u', $line)) {
                $allBullets = false;
                break;
            }
        }

        if ($allBullets) {
            $out .= '<ul>';
            foreach ($lines as $line) {
                $item = preg_replace('/^(?:-|\x{2022})\s+/u', '', $line);
                $out .= '<li>' . htmlspecialchars($item, ENT_QUOTES, 'UTF-8') . '</li>';
            }
            $out .= '</ul>';
        } else {
            $out .= '<p>' . htmlspecialchars(implode(' ', $lines), ENT_QUOTES, 'UTF-8') . '</p>';
        }
    }
    return $out;
}

// ---------------------------------------------------------------
// HTML sanitizer (allow-list).
// Keeps bold/italic/lists/links/tables, text-align, font-size and list style only.
// ---------------------------------------------------------------
function visionSanitizeHtml($html)
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
// SAVE (the page always uses exactly one row: id = 1)
// ---------------------------------------------------------------
if (empty($secmsg) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = '';
    $cleanContent = '';
    $title = '';

    if (!isset($_POST['csrf']) || !hash_equals($csrf, (string)$_POST['csrf'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $title = isset($_POST['report_title']) && is_string($_POST['report_title']) ? trim($_POST['report_title']) : '';
        $raw = isset($_POST['content']) && is_string($_POST['content']) ? $_POST['content'] : '';
        $cleanContent = visionSanitizeHtml($raw);

        if ($cleanContent === '') {
            $error = 'Content is required.';
        } elseif (strlen($cleanContent) > $maxContent) {
            $error = 'Content is too long. Please shorten it.';
        } elseif (mb_strlen($title) > $maxTitle) {
            $error = 'Title is too long (max ' . $maxTitle . ' characters).';
        }
    }

    if ($error === '') {
        try {
            $titleSql = ($title !== '') ? "'" . $db->escape($title) . "'" : 'NULL';

            // Always writes to id = 1: inserts it the first time, updates it afterwards
            $db->query(
                "INSERT INTO vision_mission (id, report_date, title, `type`, content_text, file_name)
                 VALUES (1, CURDATE(), $titleSql, 'text', '" . $db->escape($cleanContent) . "', NULL)
                 ON DUPLICATE KEY UPDATE
                    report_date = CURDATE(),
                    title = VALUES(title),
                    `type` = 'text',
                    content_text = VALUES(content_text),
                    file_name = NULL"
            );
            $_SESSION['vision_flash'] = array('text' => 'Content updated successfully.', 'type' => 'success');
        } catch (Exception $e) {
            error_log('Vision Mission save error: ' . $e->getMessage());
            $_SESSION['vision_flash'] = array('text' => 'Database error. Could not save.', 'type' => 'error');
        }
    } else {
        $_SESSION['vision_flash'] = array('text' => $error, 'type' => 'error');
    }

    header('Location: ' . $pageFile);
    exit;
}

// ---------------------------------------------------------------
// LOAD CURRENT CONTENT
// ---------------------------------------------------------------
$currentTitle = '';
$content = '';
$lastUpdated = '';
if (empty($secmsg)) {
    $row = $db->getSingleRowAssoc("SELECT title, content_text, report_date FROM vision_mission WHERE id = 1");
    if ($row) {
        $currentTitle = (string)$row['title'];
        $content = visionLegacyToHtml($row['content_text']);
        $lastUpdated = $row['report_date'] ? date('d M Y', strtotime($row['report_date'])) : '';
    }
}

// ---------------------------------------------------------------
// PAGE OUTPUT
// ---------------------------------------------------------------
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
.ar-header{margin-bottom:20px}.ar-title{font-size:24px;font-weight:700;color:#2b332f;margin:0}.ar-subtitle{font-size:14px;color:#7a8c84;margin:6px 0 0}.ar-card{background:#fff;border:1px solid #e2e6e4;border-radius:12px;padding:24px;box-shadow:0 1px 2px rgba(0,0,0,.03);max-width:100%;margin-bottom:26px}.ar-card-title{font-size:17px;font-weight:600;color:#2b332f;margin:0 0 20px;padding-bottom:14px;border-bottom:1px solid #eef1ef}.ar-updated{font-size:13px;color:#7a8c84;margin-bottom:14px}.ar-hint{font-size:12px;color:#7a8c84;margin-bottom:10px;line-height:1.7}.ar-actions{display:flex;gap:10px;margin-top:18px;padding-top:18px;border-top:1px solid #eef1ef;flex-wrap:wrap}.ar-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:11px 22px;font-size:15px;font-weight:600;border-radius:8px;border:1px solid transparent;cursor:pointer;text-decoration:none}.ar-btn-primary{background:#0f5c33;color:#fff}.ar-btn-primary:hover{background:#1a7a45;color:#fff}html,body{scrollbar-width:none;-ms-overflow-style:none}html::-webkit-scrollbar,body::-webkit-scrollbar{display:none}
.ar-field{margin-bottom:18px;min-width:0}.ar-field label{display:block;font-size:13px;font-weight:600;color:#2b332f;margin-bottom:6px}.ar-field label .req{color:#c0392b}.ar-field label .opt{color:#7a8c84;font-weight:500}.ar-input{width:100%;box-sizing:border-box;padding:10px 12px;font-size:15px;font-family:inherit;color:#2b332f;background:#fff;border:1px solid #d3d9d6;border-radius:8px;outline:none;transition:border-color .15s ease,box-shadow .15s ease}.ar-input::placeholder{color:#9aa9a2}.ar-input:focus{border-color:#1a7a45;box-shadow:0 0 0 3px rgba(26,122,69,.15)}.ar-field-hint{font-size:12px;color:#7a8c84;margin-top:6px}
@media(min-width:1920px){.ar-title{font-size:30px}.ar-card{padding:30px}}@media(max-width:700px){.ar-title{font-size:20px}.ar-card{padding:16px;border-radius:10px}}@media(max-width:560px){.ar-actions{flex-direction:column}.ar-btn{width:100%;box-sizing:border-box}}
</style>
<?php if ($secmsg !== '') { ?><div class="message"><?php echo htmlspecialchars($secmsg); ?></div><?php } ?>
<?php if ($secmsg === '') { ?>
<div class="main-content">
    <div class="ar-header">
        <h1 class="ar-title">VISION &amp; MISSION</h1>
        <p class="ar-subtitle">Edit the content shown on the website page. Saving always updates the same entry.</p>
    </div>
    <?php if ($flash !== '') { ?><div class="message <?php echo htmlspecialchars($flashType); ?>"><?php echo htmlspecialchars($flash); ?></div><?php } ?>

    <div class="ar-card">
        <div class="ar-card-title">Page Content</div>
        <?php if ($lastUpdated !== '') { ?><div class="ar-updated"><i class="fas fa-clock"></i> Last updated: <?php echo htmlspecialchars($lastUpdated); ?></div><?php } ?>
        <form id="arForm" method="post" action="turf-console/<?php echo htmlspecialchars($pageFile); ?>">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">

            <div class="ar-field">
                <label for="arTitle">Title <span class="opt">(optional)</span></label>
                <input type="text" id="arTitle" name="report_title" class="ar-input" maxlength="<?php echo (int)$maxTitle; ?>" placeholder="Enter title (optional)" value="<?php echo htmlspecialchars($currentTitle, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="ar-field-hint">Leave this field empty to display the default heading on the website.</div>
            </div>

            <div class="ar-field">
                <label for="arContent">Content <span class="req">*</span></label>
                <div class="ar-hint">
                    Use the Bold / Italic / Underline toolbar for text styling &nbsp;|&nbsp; Heading 2 = main underlined heading &nbsp;|&nbsp; Heading 3 = green underlined sub-heading<br>
                    Numbered list arrow = choose "Lower Roman" for (i), (ii), (iii). Tables: make the first row a header row (Table &gt; Row &gt; Row properties &gt; Row type: Header).
                </div>
                <textarea id="arContent" name="content"><?php echo htmlspecialchars($content, ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div class="ar-actions">
                <button type="submit" class="ar-btn ar-btn-primary"><i class="fas fa-save"></i> Save Content</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<script>
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
document.getElementById('arForm').addEventListener('submit', function (e) {
    tinymce.triggerSave();
    if (!document.getElementById('arContent').value.replace(/<[^>]*>|&nbsp;|\s/g, '')) {
        e.preventDefault();
        alert('Please write some content.');
    }
});
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