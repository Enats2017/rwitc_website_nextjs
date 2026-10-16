<?php
include_once('../bootstrap.php');
require_once('../lib/users.class.php');
require_once('../lib/userchecks.php');
require_once('../lib/permissions.php');

session_start();
$userObj = new Users($db);

$pageTitle  = 'Betting Channels';
$moduleKey  = 'betting_channels_docs';
$pageFile   = 'bettingChannelsManager.php';
$maxContent = 60000;   // max size of the cleaned HTML (bytes)

$secmsg = '';
if (!isAdminlogin()) {
    $secmsg = 'You do not have access to this page.';
} elseif (!hasModuleAccess($moduleKey)) {
    $secmsg = 'You do not have access to this module.';
}

if (empty($_SESSION['bc_csrf'])) {
    $_SESSION['bc_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['bc_csrf'];
$flash = isset($_SESSION['bc_flash']['text']) ? $_SESSION['bc_flash']['text'] : '';
$flashType = isset($_SESSION['bc_flash']['type']) ? $_SESSION['bc_flash']['type'] : '';
unset($_SESSION['bc_flash']);

// HTML sanitizer (allow-list). Keeps bold/italic/lists/links/tables, text-align and font-size only.
function bcSanitizeHtml($html)
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
                // Unknown tag: keep its children, drop the tag
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
            if (($tag === 'td' || $tag === 'th')) {
                if ($colspan > 1) $child->setAttribute('colspan', (string)$colspan);
                if ($rowspan > 1) $child->setAttribute('rowspan', (string)$rowspan);
            }
            // Only text-align and font-size are kept from inline styles
            $safeStyle = '';
            if ($style !== '' && preg_match('/text-align\s*:\s*(left|right|center|justify)/i', $style, $m)) {
                $safeStyle .= 'text-align: ' . strtolower($m[1]) . ';';
            }
            if ($style !== '' && preg_match('/font-size\s*:\s*(\d+(?:\.\d+)?)(px|pt|em|rem|%)/i', $style, $m) && (float)$m[1] > 0 && (float)$m[1] <= 200) {
                $safeStyle .= ($safeStyle !== '' ? ' ' : '') . 'font-size: ' . $m[1] . strtolower($m[2]) . ';';
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

// SAVE (the page always uses exactly one row: id = 1)
if (empty($secmsg) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = '';
    $cleanContent = '';

    if (!isset($_POST['csrf']) || !hash_equals($csrf, (string)$_POST['csrf'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $raw = isset($_POST['content']) && is_string($_POST['content']) ? $_POST['content'] : '';
        $cleanContent = bcSanitizeHtml($raw);
        if ($cleanContent === '') {
            $error = 'Content is required.';
        } elseif (strlen($cleanContent) > $maxContent) {
            $error = 'Content is too long. Please shorten it.';
        }
    }

    if ($error === '') {
        try {
            // Always writes to id = 1: inserts it the first time, updates it afterwards
            $db->query(
                "INSERT INTO betting_channels_docs (id, report_date, title, `type`, content_text, file_name)
                 VALUES (1, CURDATE(), NULL, 'text', '" . $db->escape($cleanContent) . "', NULL)
                 ON DUPLICATE KEY UPDATE
                    report_date = CURDATE(),
                    title = NULL,
                    `type` = 'text',
                    content_text = VALUES(content_text),
                    file_name = NULL"
            );
            // Remove any old extra rows so only one entry exists
            $db->query("DELETE FROM betting_channels_docs WHERE id <> 1");
            $_SESSION['bc_flash'] = array('text' => 'Betting Channels updated successfully.', 'type' => 'success');
        } catch (Exception $e) {
            error_log('Betting Channels save error: ' . $e->getMessage());
            $_SESSION['bc_flash'] = array('text' => 'Database error. Could not save.', 'type' => 'error');
        }
    } else {
        $_SESSION['bc_flash'] = array('text' => $error, 'type' => 'error');
    }

    header('Location: ' . $pageFile);
    exit;
}

// LOAD CURRENT CONTENT
$content = '';
$lastUpdated = '';
if (empty($secmsg)) {
    $row = $db->getSingleRowAssoc("SELECT content_text, report_date FROM betting_channels_docs WHERE id = 1");
    if ($row) {
        $content = (string)$row['content_text'];
        $lastUpdated = $row['report_date'] ? date('d M Y', strtotime($row['report_date'])) : '';
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
.ar-header{margin-bottom:20px}.ar-title{font-size:24px;font-weight:700;color:#2b332f;margin:0}.ar-subtitle{font-size:14px;color:#7a8c84;margin:6px 0 0}.ar-card{background:#fff;border:1px solid #e2e6e4;border-radius:12px;padding:24px;box-shadow:0 1px 2px rgba(0,0,0,.03);max-width:100%;margin-bottom:26px}.ar-card-title{font-size:17px;font-weight:600;color:#2b332f;margin:0 0 20px;padding-bottom:14px;border-bottom:1px solid #eef1ef}.ar-updated{font-size:13px;color:#7a8c84;margin-bottom:14px}.ar-hint{font-size:12px;color:#7a8c84;margin-bottom:10px}.ar-actions{display:flex;gap:10px;margin-top:18px;padding-top:18px;border-top:1px solid #eef1ef;flex-wrap:wrap}.ar-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:11px 22px;font-size:15px;font-weight:600;border-radius:8px;border:1px solid transparent;cursor:pointer;text-decoration:none}.ar-btn-primary{background:#0f5c33;color:#fff}.ar-btn-primary:hover{background:#1a7a45;color:#fff}html,body{scrollbar-width:none;-ms-overflow-style:none}html::-webkit-scrollbar,body::-webkit-scrollbar{display:none}
@media(min-width:1920px){.ar-title{font-size:30px}.ar-card{padding:30px}}@media(max-width:700px){.ar-title{font-size:20px}.ar-card{padding:16px;border-radius:10px}}@media(max-width:560px){.ar-actions{flex-direction:column}.ar-btn{width:100%;box-sizing:border-box}}
</style>
<?php if ($secmsg !== '') { ?><div class="message"><?php echo htmlspecialchars($secmsg); ?></div><?php } ?>
<?php if ($secmsg === '') { ?>
<div class="main-content">
    <div class="ar-header">
        <h1 class="ar-title">BETTING CHANNELS</h1>
        <p class="ar-subtitle">Edit the content shown on the website page. Saving always updates the same entry.</p>
    </div>
    <?php if ($flash !== '') { ?><div class="message <?php echo htmlspecialchars($flashType); ?>"><?php echo htmlspecialchars($flash); ?></div><?php } ?>

    <div class="ar-card">
        <div class="ar-card-title">Page Content</div>
        <?php if ($lastUpdated !== '') { ?><div class="ar-updated"><i class="fas fa-clock"></i> Last updated: <?php echo htmlspecialchars($lastUpdated); ?></div><?php } ?>
        <form id="arForm" method="post" action="turf-console/<?php echo htmlspecialchars($pageFile); ?>">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
            <div class="ar-hint">Use the toolbar for headings, bold, text size, lists and links. Pasted Word styling is cleaned automatically.</div>
            <textarea id="arContent" name="content"><?php echo htmlspecialchars($content, ENT_QUOTES, 'UTF-8'); ?></textarea>
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
    height: 560,
    menubar: false,
    branding: false,
    promotion: false,
    plugins: 'lists link table code',
    toolbar: 'undo redo | blocks fontsize | bold italic underline | alignleft aligncenter alignright | bullist numlist | link table | removeformat | code',
    block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3; Heading 4=h4',
    font_size_formats: '12px 14px 16px 18px 20px 24px 28px 32px',
    paste_data_images: false,
    paste_remove_styles_if_webkit: true,
    content_style: 'body { font-family: Arial, sans-serif; font-size: 15px; line-height: 1.7; }'
});
// Block submit if the editor is empty
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