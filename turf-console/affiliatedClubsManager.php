<?php

include_once('../bootstrap.php');
require_once("../lib/users.class.php");
require_once("../lib/userchecks.php");
require_once("../lib/permissions.php");
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../rwitc_website_api/config/config.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

session_start();

$userObj = new Users($db);
$moduleKey = 'affiliated_clubs';
$pageTitle = 'Affiliated Clubs with RWITC';
$maxFileSize = 20 * 1024 * 1024;
$s3Folder = 'uploads/AffiliatedClubs/';
$secmsg = '';
$flash = '';
$flashType = '';

if (!isAdminlogin()) {
    $secmsg = "You do not have access to this page.";
} elseif (!hasModuleAccess($moduleKey)) {
    $secmsg = "You do not have access to this module.";
}

if (empty($_SESSION['afc_csrf'])) {
    $_SESSION['afc_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['afc_csrf'];

if (isset($_SESSION['afc_flash'])) {
    $flash = $_SESSION['afc_flash']['text'];
    $flashType = $_SESSION['afc_flash']['type'];
    unset($_SESSION['afc_flash']);
}

function afcS3Client()
{
    return new S3Client([
        'version' => 'latest',
        'region' => AWS_REGION,
        'credentials' => [
            'key' => AWS_ACCESS_KEY_ID,
            'secret' => AWS_SECRET_ACCESS_KEY,
        ],
    ]);
}

if (empty($secmsg) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = '';
    $postedToken = isset($_POST['csrf']) ? (string)$_POST['csrf'] : '';

    if (!hash_equals($_SESSION['afc_csrf'], $postedToken)) {
        $error = 'Invalid request. Please try again.';
    } elseif (!isset($_FILES['clubsFile']) || $_FILES['clubsFile']['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'Please choose a PDF file to upload.';
    } elseif ($_FILES['clubsFile']['error'] !== UPLOAD_ERR_OK) {
        $error = 'The file could not be uploaded. Please try again.';
    } else {
        $file = $_FILES['clubsFile'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($ext !== 'pdf') {
            $error = 'Only PDF files are allowed.';
        } elseif ($file['size'] > $maxFileSize) {
            $error = 'The file is too large. Maximum size is 20 MB.';
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            if ($mime !== 'application/pdf') {
                $error = 'Only PDF files are allowed.';
            }
        }
    }

    if ($error === '') {
        try {
            $newKey = $s3Folder . 'affiliated_clubs_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.pdf';
            $s3 = afcS3Client();

            $result = $s3->putObject([
                'Bucket' => AWS_BUCKET,
                'Key' => $newKey,
                'SourceFile' => $_FILES['clubsFile']['tmp_name'],
                'ContentType' => 'application/pdf',
                'ContentDisposition' => 'inline',
            ]);

            $fileUrl = (string)$result['ObjectURL'];
            $originalName = basename((string)$_FILES['clubsFile']['name']);

            $old = $db->getSingleRowAssoc("SELECT file_url FROM affiliated_clubs WHERE id = 1");
            $oldUrl = ($old && !empty($old['file_url'])) ? (string)$old['file_url'] : '';

            $urlEsc = $db->escape($fileUrl);
            $nameEsc = $db->escape($originalName);
            $db->query(
                "INSERT INTO affiliated_clubs (id, file_url, file_name)
                 VALUES (1, '$urlEsc', '$nameEsc')
                 ON DUPLICATE KEY UPDATE file_url = VALUES(file_url), file_name = VALUES(file_name)"
            );

            if ($oldUrl !== '') {
                $oldKey = urldecode(ltrim((string)parse_url($oldUrl, PHP_URL_PATH), '/'));
                if (strpos($oldKey, $s3Folder) === 0 && $oldKey !== $newKey) {
                    try {
                        $s3->deleteObject(['Bucket' => AWS_BUCKET, 'Key' => $oldKey]);
                    } catch (AwsException $e) {
                        error_log('AFFILIATED_CLUBS_OLD_FILE_DELETE_ERROR | ' . $e->getMessage());
                    }
                }
            }

            $_SESSION['afc_flash'] = ['text' => 'Affiliated clubs file uploaded successfully.', 'type' => 'success'];
        } catch (Throwable $uploadError) {
            error_log('AFFILIATED_CLUBS_UPLOAD_ERROR | ' . $uploadError->getMessage());
            $_SESSION['afc_flash'] = ['text' => 'Unable to upload the file. Please try again or contact support.', 'type' => 'error'];
        }
    } else {
        $_SESSION['afc_flash'] = ['text' => $error, 'type' => 'error'];
    }

    header('Location: affiliatedClubsManager.php');
    exit;
}

$currentUrl = '';
$currentName = '';
$updatedAt = '';
if (empty($secmsg)) {
    $row = $db->getSingleRowAssoc("SELECT file_url, file_name, updated_at FROM affiliated_clubs WHERE id = 1");
    if ($row && !empty($row['file_url'])) {
        $currentUrl = (string)$row['file_url'];
        $currentName = (string)$row['file_name'];
        $updatedAt = (string)$row['updated_at'];
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
    .cmp-card { box-sizing: border-box; width: 100%; min-width: 0; background: #fff; border: 1px solid #e2e8e4; border-radius: 12px; padding: 22px; margin-bottom: 20px; }
    .cmp-card h2 { margin: 0 0 12px; font-size: 18px; color: #0f5c33; }
    .cmp-hint { display: block; color: #748078; font-size: 13px; margin: 0 0 12px; }
    .cmp-file-input { width: 100%; box-sizing: border-box; border: 1px solid #ccd7d0; border-radius: 7px; padding: 10px 12px; font: inherit; background: #fff; }
    .cmp-button { border: 0; border-radius: 7px; cursor: pointer; font: inherit; font-weight: 600; padding: 10px 16px; text-decoration: none; display: inline-block; }
    .cmp-button-primary { background: #0f6935; color: white; }
    .cmp-button-secondary { background: #edf2ef; color: #26382d; }
    .cmp-actions { display: flex; gap: 10px; flex-wrap: wrap; }
    .cmp-message { padding: 12px 15px; border-radius: 7px; margin-bottom: 16px; }
    .cmp-message.success { background: #e5f5ea; color: #155b2d; }
    .cmp-message.error { background: #fde9e8; color: #922b26; }
#leftArea .afc-current {
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    text-align: center !important;
    gap: 10px !important;
    width: 100% !important;
    padding: 10px 0 !important;
    box-sizing: border-box !important;
}
#leftArea .afc-current-icon {
    display: block !important;
    color: #c0392b !important;
    font-size: 48px !important;
    line-height: 1 !important;
}
#leftArea .afc-current-name {
    display: block !important;
    width: 100% !important;
    max-width: 100% !important;
    font-size: 16px !important;
    font-weight: 700 !important;
    color: #25352c !important;
    word-break: break-word !important;
    overflow-wrap: anywhere !important;
    writing-mode: horizontal-tb !important;
    white-space: normal !important;
}
#leftArea .afc-current-meta {
    display: block !important;
    color: #68776f !important;
    font-size: 13px !important;
    writing-mode: horizontal-tb !important;
}
#leftArea .afc-current .cmp-button {
    margin-top: 6px;
    white-space: nowrap;
}
.cmp-meta { color: #68776f; font-size: 13px; }
    @media (max-width: 700px) {
        .cmp-actions { flex-direction: column; }
        .cmp-button { width: 100%; text-align: center; }
    }
</style>

<main class="cmp-manager">
    <h1>Affiliated Clubs with RWITC</h1>
    <p class="cmp-intro">Upload the PDF that should appear on the public membership page.</p>

    <?php if ($secmsg !== '') { ?>
        <div class="cmp-message error" role="alert"><?php echo $escapeHtml($secmsg); ?></div>
    <?php } else { ?>
        <?php if ($flash !== '') { ?>
            <div class="cmp-message <?php echo $escapeHtml($flashType); ?>" role="status"><?php echo $escapeHtml($flash); ?></div>
        <?php } ?>

        <section class="cmp-card">
            <h2>Current file</h2>
<?php if ($currentUrl !== '') { ?>
    <div class="afc-current">
        <i class="fas fa-file-pdf afc-current-icon"></i>
        <span class="afc-current-name"><?php echo $escapeHtml($currentName); ?></span>
        <span class="afc-current-meta">Last updated: <?php echo $escapeHtml($updatedAt); ?></span>
        <a class="cmp-button cmp-button-secondary" href="<?php echo $escapeHtml($currentUrl); ?>" target="_blank" rel="noopener noreferrer">View PDF</a>
    </div>
<?php } else { ?>
                <p class="cmp-meta">No file uploaded yet.</p>
            <?php } ?>
        </section>

        <form method="post" action="turf-console/affiliatedClubsManager.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?php echo $escapeHtml($csrf); ?>">

            <section class="cmp-card">
                <h2><?php echo $currentUrl !== '' ? 'Replace file' : 'Upload file'; ?></h2>
                <span class="cmp-hint">Upload an file</span>
                <input class="cmp-file-input" type="file" name="clubsFile" accept="application/pdf,.pdf" required>
            </section>

            <div class="cmp-actions">
                <button class="cmp-button cmp-button-primary" type="submit">Upload</button>
                <a class="cmp-button cmp-button-secondary" href="turf-console/dashboard.php">Back to dashboard</a>
            </div>
        </form>
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
