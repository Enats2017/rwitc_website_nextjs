<?php

include_once('../bootstrap.php');
require_once("../lib/users.class.php");
require_once("../lib/userchecks.php");
require_once("../lib/permissions.php");

session_start();

$userObj = new Users($db);
$moduleKey = 'membership_categories';
$pageTitle = 'Membership Categories';
$maxContentLength = 500000;
$secmsg = '';
$flash = '';
$flashType = '';

if (!isAdminlogin()) {
    $secmsg = "You do not have access to this page.";
} elseif (!hasModuleAccess($moduleKey)) {
    $secmsg = "You do not have access to this module.";
}

if (empty($_SESSION['mcat_csrf'])) {
    $_SESSION['mcat_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['mcat_csrf'];

if (isset($_SESSION['mcat_flash'])) {
    $flash = $_SESSION['mcat_flash']['text'];
    $flashType = $_SESSION['mcat_flash']['type'];
    unset($_SESSION['mcat_flash']);
}

function mcatSanitizeHtml($html)
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
    $loaded = $doc->loadHTML(
        '<?xml encoding="utf-8" ?><div>' . $html . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    if (!$loaded) {
        throw new RuntimeException('Unable to parse the submitted HTML.');
    }

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
                if ($colspan > 1) {
                    $child->setAttribute('colspan', (string)$colspan);
                }
                if ($rowspan > 1) {
                    $child->setAttribute('rowspan', (string)$rowspan);
                }
            }
            if ($style !== '' && preg_match('/text-align\s*:\s*(left|right|center|justify)/i', $style, $match)) {
                $child->setAttribute('style', 'text-align: ' . strtolower($match[1]) . ';');
            }
        }
    };

    $clean($root);

    $output = '';
    foreach ($root->childNodes as $child) {
        $output .= $doc->saveHTML($child);
    }
    return trim($output);
}

if (empty($secmsg) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = '';
    $postedToken = isset($_POST['csrf']) ? (string)$_POST['csrf'] : '';

    if (!hash_equals($_SESSION['mcat_csrf'], $postedToken)) {
        $error = 'Invalid request. Please try again.';
    } else {
        $rawContent = isset($_POST['content']) && is_string($_POST['content'])
            ? $_POST['content']
            : '';

        if (mb_strlen($rawContent, 'UTF-8') > $maxContentLength) {
            $error = 'Content is too long.';
        } else {
            try {
                $cleanContent = mcatSanitizeHtml($rawContent);
                if ($cleanContent === '') {
                    $error = 'Content cannot be empty.';
                }
            } catch (Throwable $sanitizeError) {
                error_log('MEMBERSHIP_CATEGORIES_SANITIZE_ERROR | ' . $sanitizeError->getMessage());
                $error = 'Unable to process the content. Please review it and try again.';
            }
        }

        if ($error === '') {
            try {
                $contentEsc = $db->escape($cleanContent);
                $existingRow = $db->getSingleRowAssoc(
                    "SELECT id FROM membership_categories WHERE id = 1 LIMIT 1"
                );
                if (!$existingRow) {
                    throw new RuntimeException('The membership categories record is missing. Run the database setup SQL first.');
                }

                $db->query(
                    "UPDATE membership_categories
                     SET content = '$contentEsc'
                     WHERE id = 1"
                );
                $_SESSION['mcat_flash'] = [
                    'text' => 'Membership categories updated successfully.',
                    'type' => 'success'
                ];
            } catch (Throwable $saveError) {
                error_log('MEMBERSHIP_CATEGORIES_SAVE_ERROR | ' . $saveError->getMessage());
                $error = 'Unable to save the content. Please try again or contact support.';
            }
        }

        if ($error !== '') {
            $_SESSION['mcat_flash'] = ['text' => $error, 'type' => 'error'];
        }
    }

    header('Location: membershipCategoriesManager.php');
    exit;
}

$content = '';
if (empty($secmsg)) {
    $row = $db->getSingleRowAssoc(
        "SELECT content FROM membership_categories WHERE id = 1"
    );
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
    .mcat-manager { max-width: 960px; margin: 24px auto; color: #25352c; }
    .mcat-manager h1 { margin: 0 0 8px; font-size: 26px; }
    .mcat-intro { color: #68776f; margin: 0 0 22px; }
    .mcat-card { background: #fff; border: 1px solid #e2e8e4; border-radius: 12px; padding: 22px; margin-bottom: 20px; }
    .mcat-hint { display: block; color: #748078; font-size: 13px; margin: 0 0 12px; }
    .mcat-button { border: 0; border-radius: 7px; cursor: pointer; font: inherit; font-weight: 600; padding: 10px 16px; text-decoration: none; display: inline-block; }
    .mcat-button-primary { background: #0f6935; color: #fff; }
    .mcat-button-secondary { background: #edf2ef; color: #26382d; }
    .mcat-actions { display: flex; gap: 10px; flex-wrap: wrap; }
    .mcat-message { padding: 12px 15px; border-radius: 7px; margin-bottom: 16px; }
    .mcat-message.success { background: #e5f5ea; color: #155b2d; }
    .mcat-message.error { background: #fde9e8; color: #922b26; }
    @media (max-width: 600px) { .mcat-card { padding: 15px; } }
</style>

<main class="mcat-manager">
    <h1>Membership Categories</h1>
    <p class="mcat-intro">Paste the content from your Word file into the editor below. It will be shown on the public “Categories of Members” page.</p>

    <?php if ($secmsg !== '') { ?>
        <div class="mcat-message error" role="alert"><?php echo $escapeHtml($secmsg); ?></div>
    <?php } else { ?>
        <?php if ($flash !== '') { ?>
            <div class="mcat-message <?php echo $escapeHtml($flashType); ?>" role="status"><?php echo $escapeHtml($flash); ?></div>
        <?php } ?>

        <form method="post" action="turf-console/membershipCategoriesManager.php">
            <input type="hidden" name="csrf" value="<?php echo $escapeHtml($csrf); ?>">
            <section class="mcat-card">
                <span class="mcat-hint">Headings, lists, bold, italic, links and tables are supported. Fonts, colours and unsupported Word styling are removed when saved.</span>
                <textarea id="mcat-content" name="content"><?php echo $escapeHtml($content); ?></textarea>
            </section>

            <div class="mcat-actions">
                <button class="mcat-button mcat-button-primary" type="submit">Save changes</button>
                <a class="mcat-button mcat-button-secondary" href="turf-console/dashboard.php">Back to dashboard</a>
            </div>
        </form>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
        <script>
            if (window.tinymce) {
                tinymce.init({
                    selector: '#mcat-content',
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
            } else {
                document.getElementById('mcat-content').removeAttribute('hidden');
            }
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
