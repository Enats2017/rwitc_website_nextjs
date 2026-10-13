<?php

include_once('../bootstrap.php');
require_once("../lib/users.class.php");
require_once("../lib/userchecks.php");
require_once("../lib/permissions.php");

session_start();

$userObj = new Users($db);
$moduleKey = 'club_membership_privileges';
$pageTitle = 'Club Membership Privileges';
$maxContentLength = 500000;
$secmsg = '';
$flash = '';
$flashType = '';

if (!isAdminlogin()) {
    $secmsg = "You do not have access to this page.";
} elseif (!hasModuleAccess($moduleKey)) {
    $secmsg = "You do not have access to this module.";
}

if (empty($_SESSION['cmp_csrf'])) {
    $_SESSION['cmp_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['cmp_csrf'];

if (isset($_SESSION['cmp_flash'])) {
    $flash = $_SESSION['cmp_flash']['text'];
    $flashType = $_SESSION['cmp_flash']['type'];
    unset($_SESSION['cmp_flash']);
}

/* ------------------------------------------------------------------
 * HTML sanitizer (allow-list). Cleans Word junk and removes anything unsafe.
 * ------------------------------------------------------------------ */
function cmpSanitizeHtml($html)
{
    $html = trim((string)$html);
    if ($html === '') {
        return '';
    }

    $allowedTags = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'sub', 'sup', 'span', 'div',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'blockquote', 'hr',
        'a', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th'
    ];
    $dropTags = [
        'script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button',
        'textarea', 'select', 'link', 'meta', 'svg', 'math', 'head', 'title', 'xml', 'img'
    ];

    $previous = libxml_use_internal_errors(true);
    $doc = new DOMDocument('1.0', 'UTF-8');
    $doc->loadHTML(
        '<?xml encoding="utf-8" ?><div>' . $html . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    $root = $doc->getElementsByTagName('div')->item(0);
    if (!$root) {
        return '';
    }

    $clean = function (DOMNode $node) use (&$clean, $allowedTags, $dropTags) {
        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                continue;
            }
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                $node->removeChild($child); // comments, processing instructions, etc.
                continue;
            }

            $tag = strtolower($child->nodeName);

            if (in_array($tag, $dropTags, true)) {
                $node->removeChild($child);
                continue;
            }

            $clean($child);

            if (!in_array($tag, $allowedTags, true)) {
                // Unknown tag (e.g. Word's <o:p>): keep its children, drop the tag
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }

            // Strip all attributes, then re-add only the safe ones
            $href = $child->getAttribute('href');
            $style = $child->getAttribute('style');
            $colspan = (int)$child->getAttribute('colspan');
            $rowspan = (int)$child->getAttribute('rowspan');

            while ($child->attributes->length > 0) {
                $child->removeAttributeNode($child->attributes->item(0));
            }

            if ($tag === 'a') {
                if (preg_match('#^(https?://|mailto:|tel:)#i', trim($href))) {
                    $child->setAttribute('href', trim($href));
                    $child->setAttribute('target', '_blank');
                    $child->setAttribute('rel', 'noopener noreferrer');
                }
            }
            if ($tag === 'td' || $tag === 'th') {
                if ($colspan > 1) $child->setAttribute('colspan', (string)$colspan);
                if ($rowspan > 1) $child->setAttribute('rowspan', (string)$rowspan);
            }
            if ($style !== '' && preg_match('/text-align\s*:\s*(left|right|center|justify)/i', $style, $m)) {
                $child->setAttribute('style', 'text-align: ' . strtolower($m[1]) . ';');
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

/* ------------------------------------------------------------------
 * Save
 * ------------------------------------------------------------------ */
if (empty($secmsg) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = '';
    $postedToken = isset($_POST['csrf']) ? (string)$_POST['csrf'] : '';

    if (!hash_equals($_SESSION['cmp_csrf'], $postedToken)) {
        $error = 'Invalid request. Please try again.';
    } else {
        $rawContent = isset($_POST['content']) && is_string($_POST['content']) ? $_POST['content'] : '';

        if (mb_strlen($rawContent, 'UTF-8') > $maxContentLength) {
            $error = 'Content is too long.';
        } else {
            $cleanContent = cmpSanitizeHtml($rawContent);
            if ($cleanContent === '') {
                $error = 'Content cannot be empty.';
            }
        }

        if ($error === '') {
            try {
                $contentEsc = $db->escape($cleanContent);
                $existingRow = $db->getSingleRowAssoc(
                    "SELECT id FROM club_membership_privileges WHERE id = 1 LIMIT 1"
                );
                if (!$existingRow) {
                    throw new RuntimeException('The membership privileges record is missing. Run the database setup SQL first.');
                }

                $db->query(
                    "UPDATE club_membership_privileges
                     SET content = '$contentEsc'
                     WHERE id = 1"
                );
                $_SESSION['cmp_flash'] = ['text' => 'Club membership privileges updated successfully.', 'type' => 'success'];
            } catch (Throwable $saveError) {
                error_log('CLUB_MEMBERSHIP_PRIVILEGES_SAVE_ERROR | ' . $saveError->getMessage());
                $_SESSION['cmp_flash'] = ['text' => 'Unable to save the content. Please try again or contact support.', 'type' => 'error'];
            }
        } else {
            $_SESSION['cmp_flash'] = ['text' => $error, 'type' => 'error'];
        }
    }

    header('Location: clubMembershipPrivilegesManager.php');
    exit;
}

/* ------------------------------------------------------------------
 * Load (no hard-coded default: editor shows only what is in the database)
 * ------------------------------------------------------------------ */
$content = '';
if (empty($secmsg)) {
    $row = $db->getSingleRowAssoc("SELECT content FROM club_membership_privileges WHERE id = 1");
    if ($row && isset($row['content'])) {
        $content = (string)$row['content'];
    }
}

$escapeHtml = function ($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

$design = new Design();
$design->js = '';
$design->css = '';
$design->jqueryJs = "";
$design->startPage($pageTitle);
$design->writeLogoTickerMenu();
$design->openDiv("contentWrapper");
$design->openDiv("infoWrapper", "col-lg-12");
$design->openDiv("leftArea", 'col-lg-9');
$design->writeContentPageStyles();

?>
<style>
    .cmp-manager { max-width: 960px; margin: 24px auto; color: #25352c; }
    .cmp-manager h1 { margin: 0 0 8px; font-size: 26px; }
    .cmp-manager .cmp-intro { color: #68776f; margin: 0 0 22px; }
    .cmp-card { background: #fff; border: 1px solid #e2e8e4; border-radius: 12px; padding: 22px; margin-bottom: 20px; }
    .cmp-hint { display: block; color: #748078; font-size: 13px; margin: 0 0 12px; }
    .cmp-button { border: 0; border-radius: 7px; cursor: pointer; font: inherit; font-weight: 600; padding: 10px 16px; text-decoration: none; display: inline-block; }
    .cmp-button-primary { background: #0f6935; color: white; }
    .cmp-button-secondary { background: #edf2ef; color: #26382d; }
    .cmp-actions { display: flex; gap: 10px; flex-wrap: wrap; }
    .cmp-message { padding: 12px 15px; border-radius: 7px; margin-bottom: 16px; }
    .cmp-message.success { background: #e5f5ea; color: #155b2d; }
    .cmp-message.error { background: #fde9e8; color: #922b26; }
    @media (max-width: 600px) { .cmp-card { padding: 15px; } }
</style>

<main class="cmp-manager">
    <h1>Club Membership Privileges</h1>
    <p class="cmp-intro">Paste the content from your Word file into the editor below. It will be shown on the public membership page.</p>

    <?php if ($secmsg !== '') { ?>
        <div class="cmp-message error" role="alert"><?php echo $escapeHtml($secmsg); ?></div>
    <?php } else { ?>
        <?php if ($flash !== '') { ?>
            <div class="cmp-message <?php echo $escapeHtml($flashType); ?>" role="status"><?php echo $escapeHtml($flash); ?></div>
        <?php } ?>

        <form method="post" action="clubMembershipPrivilegesManager.php">
            <input type="hidden" name="csrf" value="<?php echo $escapeHtml($csrf); ?>">

            <section class="cmp-card">
                <span class="cmp-hint">Tip: headings, bullet lists, bold, links and tables from Word are kept. Fonts, colours and other Word styling are removed automatically.</span>
                <textarea id="cmp-content" name="content"><?php echo $escapeHtml($content); ?></textarea>
            </section>

            <div class="cmp-actions">
                <button class="cmp-button cmp-button-primary" type="submit">Save changes</button>
                <a class="cmp-button cmp-button-secondary" href="../dashboard.php">Back to dashboard</a>
            </div>
        </form>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
        <script>
            tinymce.init({
                selector: '#cmp-content',
                height: 560,
                menubar: false,
                branding: false,
                promotion: false,
                plugins: 'lists link table code',
                toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist | link table | removeformat | code',
                block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3; Heading 4=h4',
                paste_data_images: false,
                paste_as_text: false,
                paste_remove_styles_if_webkit: true,
                content_style: 'body { font-family: Arial, sans-serif; font-size: 15px; line-height: 1.7; }'
            });
        </script>
    <?php } ?>
</main>

<?php

$design->closeDiv();
$design->writeLeftPanel();
$design->closeDiv();
$design->closeDiv();
$design->endPage();
$design = NULL;

?>