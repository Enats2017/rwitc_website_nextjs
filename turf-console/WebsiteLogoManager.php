<?php
include_once('../bootstrap.php');
require_once("../lib/users.class.php");
require_once("../lib/userchecks.php");
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../rwitc_website_api/config/config.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

/* ================= MODULE SETTINGS ================= */

const LOGO_TYPE        = 'header_logo';      // row key in website_logo table
const LOGO_S3_FOLDER   = 'uploads/WebsiteLogo/';    // folder inside the S3 bucket
const LOGO_MAX_BYTES   = 512000;             // 500 KB
const LOGO_MAX_W       = 600;                // max allowed width in px
const LOGO_MAX_H       = 600;                // max allowed height in px
const LOGO_REC_W       = 240;                // recommended width in px (shown in the note)
const LOGO_REC_H       = 240;                // recommended height in px (shown in the note)
const LOGO_SESSION_KEY = 'website_logo';     // module key (must match Design::moduleCatalog key)

/* ================= HELPERS ================= */
function logoS3Client()
{
    return new S3Client([
        'version'     => 'latest',
        'region'      => AWS_REGION,
        'credentials' => [
            'key'    => AWS_ACCESS_KEY_ID,
            'secret' => AWS_SECRET_ACCESS_KEY,
        ],
    ]);
}

// Get the S3 object key back from the saved full url
function logoKeyFromUrl($url)
{
    $path = ltrim((string) parse_url($url, PHP_URL_PATH), '/');
    $bucketPrefix = AWS_BUCKET . '/';
    if (strpos($path, $bucketPrefix) === 0) {
        $path = substr($path, strlen($bucketPrefix)); // path-style url support
    }
    return rawurldecode($path);
}

function logoDeleteFromS3($url)
{
    if (empty($url)) {
        return;
    }
    try {
        logoS3Client()->deleteObject([
            'Bucket' => AWS_BUCKET,
            'Key'    => logoKeyFromUrl($url),
        ]);
    } catch (AwsException $e) {
        // old file delete failure should not block the admin flow
    }
}

function logoGetRow($db, $type)
{
    $row = $db->getSingleRowAssoc(
        "SELECT id, url, created_at, updated_at FROM website_logo WHERE type = '" . $db->escape($type) . "' LIMIT 1"
    );
    return $row ? $row : null;
}

// Access: module flag in session, OR super admin, OR module ticked for this admin in any group
function logoHasAccess($db)
{
    if (isset($_SESSION[LOGO_SESSION_KEY]) && $_SESSION[LOGO_SESSION_KEY] == "Y") {
        return true;
    }

    $adminId = isset($_SESSION['uid']) ? (int) $_SESSION['uid'] : 0;
    if ($adminId <= 0) {
        return false;
    }

    // Super Admin (Admin ID 19 only)
    if (
        $adminId === 19 &&
        isset($_SESSION['role']) &&
        strtoupper((string) $_SESSION['role']) === 'ADMIN'
    ) {
        return true;
    }

    $row = $db->getSingleRowAssoc(
        "SELECT 1 AS ok FROM admin_group_modules
         WHERE admin_id = " . $adminId . "
           AND module_key = '" . $db->escape(LOGO_SESSION_KEY) . "'
         LIMIT 1"
    );
    return $row ? true : false;
}

/* ================= PAGE LOGIC ================= */
$q = getParameterString('q', '', $db);
session_start();
$msg = '';
$secmsg = '';
$hasAccess = false;
$currentLogo = null;

if (isAdminlogin()) {
    if (logoHasAccess($db)) {
        $hasAccess = true;

        // ---------- SAVE / REPLACE LOGO ----------
        if ($q == "save-logo" && $_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                if (!isset($_FILES['logoFile']) || $_FILES['logoFile']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception('Please choose an image file to upload.');
                }
                $file = $_FILES['logoFile'];

                if ($file['size'] > LOGO_MAX_BYTES) {
                    throw new Exception('File is too large. Maximum allowed size is ' . (LOGO_MAX_BYTES / 1024) . ' KB.');
                }

                $info = @getimagesize($file['tmp_name']);
                if (!$info) {
                    throw new Exception('Invalid image file.');
                }

                $allowedTypes = array(
                    IMAGETYPE_PNG  => 'png',
                    IMAGETYPE_JPEG => 'jpg',
                    IMAGETYPE_WEBP => 'webp',
                );
                if (!isset($allowedTypes[$info[2]])) {
                    throw new Exception('Only PNG, JPG or WEBP images are allowed.');
                }

                if ($info[0] > LOGO_MAX_W || $info[1] > LOGO_MAX_H) {
                    throw new Exception('Image is too big (' . $info[0] . ' x ' . $info[1] . ' px). Maximum allowed is ' . LOGO_MAX_W . ' x ' . LOGO_MAX_H . ' px.');
                }

                $ext   = $allowedTypes[$info[2]];
                $s3Key = LOGO_S3_FOLDER . LOGO_TYPE . '_' . date('YmdHis') . '.' . $ext; // unique name so browser/CDN cache never shows the old logo

                $result = logoS3Client()->putObject([
                    'Bucket'      => AWS_BUCKET,
                    'Key'         => $s3Key,
                    'SourceFile'  => $file['tmp_name'],
                    'ContentType' => $info['mime'],
                ]);
                $newUrl = $result['ObjectURL'];

                $oldRow = logoGetRow($db, LOGO_TYPE);

                $db->query(
                    "INSERT INTO website_logo (type, url) VALUES ('" . $db->escape(LOGO_TYPE) . "', '" . $db->escape($newUrl) . "')
                     ON DUPLICATE KEY UPDATE url = VALUES(url)"
                );

                if ($oldRow && !empty($oldRow['url']) && $oldRow['url'] !== $newUrl) {
                    logoDeleteFromS3($oldRow['url']);
                }

                $_SESSION['website_logo_msg'] = 'Logo uploaded successfully!';
                header("Location: WebsiteLogoManager.php");
                exit;
            } catch (AwsException $e) {
                $msg = 'Upload to S3 failed. Please try again.';
            } catch (Exception $err) {
                $msg = $err->getMessage();
            }
        }

        // ---------- REMOVE LOGO ----------
        if ($q == "remove-logo" && $_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $oldRow = logoGetRow($db, LOGO_TYPE);
                if ($oldRow) {
                    logoDeleteFromS3($oldRow['url']);
                    $db->query("DELETE FROM website_logo WHERE type = '" . $db->escape(LOGO_TYPE) . "'");
                }
                $_SESSION['website_logo_msg'] = 'Logo removed. The website will show the default logo until a new one is uploaded.';
                header("Location: WebsiteLogoManager.php");
                exit;
            } catch (Exception $err) {
                $msg = 'Could not remove the logo. Please try again.';
            }
        }

        $currentLogo = logoGetRow($db, LOGO_TYPE);
    } else {
        $msg = "You do not have access to this page.";
    }
} else {
    $secmsg = "Please login to access this page";
}

if (isset($_SESSION['website_logo_msg'])) {
    $msg = $_SESSION['website_logo_msg'];
    unset($_SESSION['website_logo_msg']);
}
?>
<?php
$pageTitle = 'Website Logo Manager';
$design = new Design();
$design->js = '
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  ';
$design->css = '';
$design->jqueryJs = '';

$design->startPage("$pageTitle");

$design->writeLogoTickerMenu();
$design->openDiv("contentWrapper");
$design->openDiv("infoWrapper", "col-lg-12");
$design->openDiv("leftArea", "col-lg-9");
?>

<style type="text/css">
    :root {
        --g: #0f5c33;
        --g-dark: #0b3d24;
        --bd: #e2e6e4;
        --mut: #7a8c84;
    }

    /* ===== layout: leftArea + sidebar ===== */
    #infoWrapper.col-lg-12 {
        display: flex;
        flex-direction: row-reverse;
        align-items: flex-start;
        max-width: 1500px;
        margin: 30px auto;
        float: none;
    }

    #leftArea.col-lg-9 {
        flex: 1 1 auto;
        min-width: 0;
        max-width: none;
        margin: 0;
        padding: 0 30px;
        box-sizing: border-box;
        float: none;
        width: auto;
        display: block;
    }

    #infoWrapper.col-lg-12 #rightArea.col-lg-3 {
        padding-top: 0 !important;
    }

    html,
    body {
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    html::-webkit-scrollbar,
    body::-webkit-scrollbar {
        display: none;
    }

    /* ===== heading + message ===== */
    .logo-heading {
        font-size: 22px;
        font-weight: 700;
        color: var(--g-dark);
        margin: 0 0 18px;
        padding: 0;
        border: 0;
        background: none;
        width: auto;
        display: flex;
        align-items: center;
        gap: 10px;
        white-space: nowrap;
        text-transform: none;
        letter-spacing: normal;
    }

    .logo-heading::before,
    .logo-heading::after {
        content: none !important;
        display: none !important;
    }

    .message {
        background: #e6f4ec;
        border: 1px solid #b7ddc5;
        border-left: 4px solid var(--g);
        color: var(--g);
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 18px;
        font-size: 14.5px;
        font-weight: 500;
    }

    /* ===== cards ===== */
    .logo-card {
        background: #fff;
        border: 1px solid var(--bd);
        border-radius: 14px;
        padding: 22px;
        box-shadow: 0 4px 14px rgba(15, 92, 51, .06);
        margin-bottom: 24px;
    }

    .logo-card-title {
        font-size: 16px;
        font-weight: 700;
        color: var(--g);
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid #eef1ef;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .logo-card-title i {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #e6f4ec;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
    }

    /* ===== compact logo preview (only as big as the logo) ===== */
    .logo-preview-box {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        max-width: 100%;
        box-sizing: border-box;
        background: linear-gradient(90deg, #08501f 0%, #0d7a30 100%);
        border-radius: 10px;
        padding: 14px 24px;
        min-height: 70px;
    }

    .logo-preview-box img {
        width: 80px;
        max-width: 100%;
        height: auto;
        object-fit: contain;
        display: block;
    }

    .logo-preview-empty {
        color: rgba(255, 255, 255, .9);
        font-size: 13.5px;
    }

    .logo-meta {
        font-size: 12.5px;
        color: var(--mut);
    }

    /* ===== current logo row: preview left, details right ===== */
    .logo-current {
        display: flex;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
    }

    .logo-current-info {
        display: flex;
        flex-direction: column;
        gap: 12px;
        align-items: flex-start;
    }

    /* ===== size note ===== */
    .logo-note {
        background: #fffaf0;
        border: 1px solid #f3dca0;
        border-left: 4px solid #e6a800;
        color: #7a5a00;
        border-radius: 10px;
        padding: 14px 18px;
        font-size: 13.5px;
        line-height: 1.7;
        margin-bottom: 20px;
    }

    .logo-note strong {
        color: #5c4300;
    }

    .logo-note ul {
        margin: 8px 0 0;
        padding: 0;
        list-style: none;
    }

    .logo-note li {
        position: relative;
        padding-left: 22px;
    }

    .logo-note li::before {
        content: "\2713";
        position: absolute;
        left: 0;
        color: #e6a800;
        font-weight: 700;
    }

    /* ===== form ===== */
    .logo-form-row {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .logo-form-row input[type="file"] {
        flex: 1 1 260px;
        min-width: 0;
        border: 2px dashed #b7ddc5;
        background: #f8fcf9;
        border-radius: 10px;
        padding: 12px;
        font-size: 14px;
        cursor: pointer;
        box-sizing: border-box;
    }

    .logo-form-row input[type="file"]::file-selector-button {
        background: #e6f4ec;
        color: var(--g);
        border: 1px solid #b7ddc5;
        border-radius: 6px;
        padding: 7px 14px;
        margin-right: 12px;
        font-weight: 600;
        cursor: pointer;
    }

    .logo-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        background: var(--g);
        color: #fff;
        border: 1px solid var(--g);
        padding: 11px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        white-space: nowrap;
        transition: .2s;
    }

    .logo-btn:hover {
        background: var(--g-dark);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(15, 92, 51, .25);
    }

    .logo-btn.danger {
        background: #fff;
        color: #c0392b;
        border-color: #c0392b;
        padding: 9px 16px;
    }

    .logo-btn.danger:hover {
        background: #fdecea;
        box-shadow: none;
        transform: none;
    }

    .logo-new-preview {
        margin-top: 18px;
        padding-top: 16px;
        border-top: 1px dashed var(--bd);
        display: none;
    }

    .logo-new-preview .logo-meta {
        margin-bottom: 10px;
    }

    @media (max-width: 900px) {
        #infoWrapper.col-lg-12 {
            flex-direction: column;
            margin: 16px auto;
        }

        #leftArea.col-lg-9 {
            flex: 1 1 100%;
            max-width: 100%;
            padding: 28px 24px;
        }
    }

    @media (max-width: 700px) {
        #leftArea.col-lg-9 {
            padding: 0 16px;
        }

        .logo-heading {
            font-size: 19px;
        }

        .logo-card {
            padding: 16px;
        }

        .logo-form-row {
            flex-direction: column;
            align-items: stretch;
        }

        .logo-form-row input[type="file"] {
            flex: none;
            width: 100%;
        }

        .logo-current {
            flex-direction: column;
            align-items: stretch;
        }

        .logo-current .logo-preview-box {
            align-self: flex-start;
        }

        .logo-current-info .logo-btn {
            width: 100%;
        }
    }
</style>

<?php if (!empty($msg)) { ?>
    <div class="message"><?php echo htmlspecialchars($msg); ?></div>
<?php } ?>
<?php if (!empty($secmsg)) { ?>
    <div class="message"><?php echo htmlspecialchars($secmsg); ?></div>
<?php } ?>

<?php if ($hasAccess) { ?>

    <h2 class="logo-heading"><i class="fas fa-palette"></i> Website Logo Manager</h2>

    <!-- UPLOAD NEW LOGO -->
    <div class="logo-card">
        <div class="logo-card-title"><i class="fas fa-upload"></i> Upload New Logo</div>

        <div class="logo-note">
            <strong><i class="fas fa-circle-info"></i> Please follow this size before uploading:</strong>
            <ul>
                <li>Recommended size: <strong><?php echo LOGO_REC_W; ?> x <?php echo LOGO_REC_H; ?> px</strong> (keep the same width : height ratio as the original logo)</li>
                <li>Maximum allowed: <?php echo LOGO_MAX_W; ?> x <?php echo LOGO_MAX_H; ?> px and <?php echo (LOGO_MAX_BYTES / 1024); ?> KB &mdash; anything bigger will be rejected</li>
                <li>Format: PNG (transparent background), JPG or WEBP</li>
                <li>The logo is shown on a green background, so use the <strong>white version</strong> of the logo</li>
                <li>On the website it is displayed at 80 px wide on desktop, so it will look the same as the preview below</li>
            </ul>
        </div>

        <form method="post" action="turf-console/WebsiteLogoManager.php?q=save-logo" enctype="multipart/form-data" id="logoUploadForm">
            <div class="logo-form-row">
                <input type="file" name="logoFile" id="logoFile" accept="image/png,image/jpeg,image/webp" required />
                <button type="submit" class="logo-btn"><i class="fas fa-cloud-arrow-up"></i> Save Logo</button>
            </div>
        </form>

        <div class="logo-new-preview" id="logoNewPreview">
            <div class="logo-meta">Preview of the selected image (as it will look on the website):</div>
            <div class="logo-preview-box">
                <img id="logoNewPreviewImg" src="" alt="Selected logo preview" />
            </div>
        </div>
    </div>

    <!-- CURRENT LOGO -->
    <div class="logo-card">
        <div class="logo-card-title"><i class="fas fa-image"></i> Current Website Header Logo</div>
        <div class="logo-current">
            <div class="logo-preview-box">
                <?php if ($currentLogo && !empty($currentLogo['url'])) { ?>
                    <img src="<?php echo htmlspecialchars($currentLogo['url']); ?>" alt="Current logo" />
                <?php } else { ?>
                    <span class="logo-preview-empty">No logo uploaded yet. The website is showing the default logo.</span>
                <?php } ?>
            </div>
            <?php if ($currentLogo) { ?>
                <div class="logo-current-info">
                    <div class="logo-meta">
                        <i class="far fa-clock"></i> Last updated: <?php echo date("d-M-Y h:i A", strtotime($currentLogo['updated_at'])); ?>
                    </div>
                    <form method="post" action="turf-console/WebsiteLogoManager.php?q=remove-logo"
                        onsubmit="return confirm('Remove this logo? The website will go back to the default logo.');">
                        <button type="submit" class="logo-btn danger"><i class="fas fa-trash-alt"></i> Remove Logo</button>
                    </form>
                </div>
            <?php } ?>
        </div>
    </div>

    <script type="text/javascript">
        (function() {
            var MAX_BYTES = <?php echo (int) LOGO_MAX_BYTES; ?>;
            var MAX_W = <?php echo (int) LOGO_MAX_W; ?>;
            var MAX_H = <?php echo (int) LOGO_MAX_H; ?>;
            var input = document.getElementById('logoFile');
            var previewWrap = document.getElementById('logoNewPreview');
            var previewImg = document.getElementById('logoNewPreviewImg');

            function resetPicker() {
                input.value = '';
                previewWrap.style.display = 'none';
                previewImg.removeAttribute('src');
            }

            input.addEventListener('change', function() {
                var f = input.files && input.files[0];
                if (!f) {
                    resetPicker();
                    return;
                }
                if (f.size > MAX_BYTES) {
                    alert('File is too large. Maximum allowed size is ' + Math.round(MAX_BYTES / 1024) + ' KB.');
                    resetPicker();
                    return;
                }
                var url = URL.createObjectURL(f);
                var img = new Image();
                img.onload = function() {
                    if (img.width > MAX_W || img.height > MAX_H) {
                        alert('Image is too big (' + img.width + ' x ' + img.height + ' px). Maximum allowed is ' + MAX_W + ' x ' + MAX_H + ' px.');
                        URL.revokeObjectURL(url);
                        resetPicker();
                        return;
                    }
                    previewImg.src = url;
                    previewWrap.style.display = 'block';
                };
                img.onerror = function() {
                    alert('Invalid image file.');
                    URL.revokeObjectURL(url);
                    resetPicker();
                };
                img.src = url;
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
$design->pageClose();
$design = NULL; // release object