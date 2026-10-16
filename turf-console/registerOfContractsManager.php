<?php
include_once('../bootstrap.php');
require_once('../lib/users.class.php');
require_once('../lib/userchecks.php');
require_once('../lib/permissions.php');
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../rwitc_website_api/config/config.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

session_start();
$userObj = new Users($db);

$pdfTitle = 'Register of Contracts';
$moduleKey = 'register_of_contracts';
$pageFile = 'registerOfContractsManager.php';
$s3Folder = 'uploads/club/';
$sections = [
    'register_of_contracts_1' => ['label' => 'Register of Contracts - I', 'key' => $s3Folder . 'Register_of_Contracts-I.pdf'],
    'register_of_contracts_2' => ['label' => 'Register of Contracts - II', 'key' => $s3Folder . 'Register_of_Contracts-Part-II.pdf'],
];
$pdfType = 'register_of_contracts_1';
$requestedType = $_POST['sec'] ?? $_GET['sec'] ?? '';
if (is_string($requestedType) && isset($sections[$requestedType])) {
    $pdfType = $requestedType;
}
$pdfName = $sections[$pdfType]['label'];
$s3Key = $sections[$pdfType]['key'];
$sessionKey = 'menupdf_' . $pdfType;

$secmsg = '';
if (!isAdminlogin()) {
    $secmsg = 'You do not have access to this page.';
} elseif (!hasModuleAccess($moduleKey)) {
    $secmsg = 'You do not have access to this module.';
}

if (empty($_SESSION[$sessionKey . '_csrf'])) {
    $_SESSION[$sessionKey . '_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION[$sessionKey . '_csrf'];
$flash = $_SESSION[$sessionKey . '_flash']['text'] ?? '';
$flashType = $_SESSION[$sessionKey . '_flash']['type'] ?? '';
unset($_SESSION[$sessionKey . '_flash']);

function menuPdfSetFlash($text, $type) {
    global $sessionKey;
    $_SESSION[$sessionKey . '_flash'] = ['text' => $text, 'type' => $type];
}

function menuPdfS3Client() {
    return new S3Client([
        'version' => 'latest',
        'region' => AWS_REGION,
        'credentials' => ['key' => AWS_ACCESS_KEY_ID, 'secret' => AWS_SECRET_ACCESS_KEY]
    ]);
}

function menuPdfKeyFromUrl($url) {
    $path = urldecode((string) parse_url((string) $url, PHP_URL_PATH));
    $pos = strpos($path, 'uploads/club/');
    return $pos === false ? '' : substr($path, $pos);
}

function menuPdfDeleteFromS3($url) {
    $key = menuPdfKeyFromUrl($url);
    if ($key === '') return;
    try {
        menuPdfS3Client()->deleteObject(['Bucket' => AWS_BUCKET, 'Key' => $key]);
    } catch (AwsException $e) {
        error_log('PDF S3 delete failed: ' . $e->getMessage());
    }
}

function menuPdfUpload($tmpFile, &$error) {
    global $s3Key;
    $mime = function_exists('mime_content_type') ? mime_content_type($tmpFile) : 'application/pdf';
    $header = file_get_contents($tmpFile, false, null, 0, 5);
    if ($mime !== 'application/pdf' || $header !== '%PDF-') {
        $error = 'Invalid PDF file.';
        return '';
    }
    try {
        $result = menuPdfS3Client()->putObject([
            'Bucket' => AWS_BUCKET,
            'Key' => $s3Key,
            'SourceFile' => $tmpFile,
            'ContentType' => 'application/pdf'
        ]);
        return $result['ObjectURL'] ?? '';
    } catch (AwsException $e) {
        error_log('PDF S3 upload failed: ' . $e->getMessage());
        $error = 'Could not upload the PDF. Please try again.';
        return '';
    }
}

function menuPdfLabel($url) {
    $name = basename(urldecode((string) parse_url((string) $url, PHP_URL_PATH)));
    return $name === '' ? 'PDF file' : $name;
}

if (empty($secmsg) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf']) || !hash_equals($csrf, (string) $_POST['csrf'])) {
        menuPdfSetFlash('Invalid request. Please try again.', 'error');
        header('Location: ' . $pageFile . '?sec=' . urlencode($pdfType));
        exit;
    }

    if (!isset($_FILES['report_file']) || $_FILES['report_file']['error'] !== UPLOAD_ERR_OK) {
        menuPdfSetFlash('Please choose a PDF file.', 'error');
    } elseif (strtolower(pathinfo($_FILES['report_file']['name'], PATHINFO_EXTENSION)) !== 'pdf') {
        menuPdfSetFlash('Only PDF files are allowed.', 'error');
    } else {
        $error = '';
        $newUrl = menuPdfUpload($_FILES['report_file']['tmp_name'], $error);
        if ($error === '' && $newUrl !== '') {
            try {
                $rows = $db->getMultiDimensionalArray(
                    "SELECT id, file_url FROM club_menu_pdf WHERE `type` = '" . $pdfType . "' ORDER BY id ASC"
                );
                $keepId = !empty($rows) ? (int) $rows[0]['id'] : 0;
                $oldUrls = [];
                foreach ($rows as $row) {
                    if ((int) $row['id'] !== $keepId && !empty($row['file_url'])) $oldUrls[] = $row['file_url'];
                }
                if ($keepId > 0) {
                    $oldUrl = (string) $rows[0]['file_url'];
                    $db->update("UPDATE club_menu_pdf SET file_url = '" . $db->escape($newUrl) . "', `date` = CURDATE() WHERE id = " . $keepId . " AND `type` = '" . $pdfType . "'");
                    $db->query("DELETE FROM club_menu_pdf WHERE `type` = '" . $pdfType . "' AND id <> " . $keepId);
                    if ($oldUrl !== '' && menuPdfKeyFromUrl($oldUrl) !== $GLOBALS['s3Key']) $oldUrls[] = $oldUrl;
                } else {
                    $db->insert("INSERT INTO club_menu_pdf (`type`, file_url, `date`) VALUES ('" . $pdfType . "', '" . $db->escape($newUrl) . "', CURDATE())");
                }
                foreach (array_unique($oldUrls) as $oldUrl) {
                    if (menuPdfKeyFromUrl($oldUrl) !== $GLOBALS['s3Key']) menuPdfDeleteFromS3($oldUrl);
                }
                menuPdfSetFlash('PDF uploaded successfully.', 'success');
            } catch (Exception $e) {
                error_log('PDF database save failed: ' . $e->getMessage());
                menuPdfSetFlash('Database error. Could not save the PDF.', 'error');
            }
        } else {
            menuPdfSetFlash($error !== '' ? $error : 'Could not upload the PDF. Please try again.', 'error');
        }
    }
    header('Location: ' . $pageFile . '?sec=' . urlencode($pdfType));
    exit;
}

$current = null;
if (empty($secmsg)) {
    $current = $db->getSingleRowAssoc("SELECT id, file_url FROM club_menu_pdf WHERE `type` = '" . $pdfType . "' ORDER BY id ASC LIMIT 1");
}

$pageTitle = $pdfTitle;
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
.ar-header{margin-bottom:20px}.ar-title{font-size:24px;font-weight:700;color:#2b332f;margin:0}.ar-subtitle{font-size:14px;color:#7a8c84;margin:6px 0 0}.ar-card{background:#fff;border:1px solid #e2e6e4;border-radius:12px;padding:24px;box-shadow:0 1px 2px rgba(0,0,0,.03);max-width:900px;margin-bottom:26px}.ar-card-title{font-size:17px;font-weight:600;color:#2b332f;margin:0 0 20px;padding-bottom:14px;border-bottom:1px solid #eef1ef}.ar-field{margin-bottom:18px}.ar-field label{display:block;font-size:13px;font-weight:600;color:#2b332f;margin-bottom:6px}.ar-drop{border:2px dashed #b9c4be;border-radius:12px;padding:32px 16px;text-align:center;cursor:pointer;background:#f8faf9}.ar-drop:hover,.ar-drop.dragover{border-color:#1a7a45;background:#eef7f1}.ar-drop-icon{font-size:32px;color:#0f5c33;margin-bottom:10px}.ar-drop-text{font-size:15px;font-weight:500;color:#2b332f;margin:0 0 4px}.ar-drop-text span{color:#0f5c33;text-decoration:underline}.ar-drop-sub{font-size:12px;color:#7a8c84;margin:0}.ar-file-input{display:none}.ar-file-box{display:none;align-items:center;gap:12px;margin-top:12px;padding:12px 14px;border:1px solid #e2e6e4;border-radius:10px}.ar-file-box.show{display:flex}.ar-file-ico{width:38px;height:38px;border-radius:9px;background:#0f5c33;color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0}.ar-file-info{flex:1;min-width:0}.ar-file-name{font-size:14px;font-weight:500;color:#2b332f;overflow-wrap:anywhere}.ar-file-size{font-size:12px;color:#7a8c84}.ar-actions{display:flex;gap:10px;margin-top:8px;padding-top:18px;border-top:1px solid #eef1ef;flex-wrap:wrap}.ar-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:11px 22px;font-size:15px;font-weight:600;border-radius:8px;border:1px solid transparent;cursor:pointer;text-decoration:none}.ar-btn-primary{background:#0f5c33;color:#fff}.ar-btn-primary:hover{background:#1a7a45;color:#fff}.ar-btn-secondary{background:#fff;color:#2b332f;border-color:#d3d9d6}.ar-table{width:100%;border-collapse:collapse}.ar-table th{text-align:left;font-size:12px;color:#0f5c33;background:#f3f6f4;padding:11px 12px;border-bottom:1px solid #e2e6e4}.ar-table td{font-size:14px;color:#2b332f;padding:12px;border-bottom:1px solid #eef1ef;vertical-align:middle}.ar-t-file{display:flex;align-items:center;gap:10px;font-weight:600;overflow-wrap:anywhere}.ar-t-file i{color:#c0392b;font-size:18px}.ar-icon-btn{width:34px;height:34px;border-radius:8px;border:1px solid #d3d9d6;background:#fff;color:#2b332f;display:inline-flex;align-items:center;justify-content:center;text-decoration:none}.ar-icon-btn:hover{border-color:#1a7a45;color:#0f5c33;background:#eef7f1}.ar-empty{text-align:center;padding:24px 10px;color:#7a8c84;font-size:14px}.ar-tabs{display:flex;gap:8px;margin-bottom:22px;overflow-x:auto;padding-bottom:4px}.ar-tab{flex:0 0 auto;padding:9px 16px;border:1px solid #d3d9d6;border-radius:8px;background:#fff;color:#2b332f;font-size:14px;font-weight:600;text-decoration:none;white-space:nowrap}.ar-tab.active{background:#0f5c33;border-color:#0f5c33;color:#fff}html,body{scrollbar-width:none;-ms-overflow-style:none}html::-webkit-scrollbar,body::-webkit-scrollbar{display:none}
@media(min-width:1920px){.ar-title{font-size:30px}.ar-card{max-width:1100px;padding:30px}}@media(max-width:700px){.ar-title{font-size:20px}.ar-card{padding:16px;border-radius:10px}.ar-drop{padding:24px 12px}}@media(max-width:560px){.ar-actions{flex-direction:column}.ar-btn{width:100%;box-sizing:border-box}}
</style>
<?php if ($secmsg !== '') { ?><div class="message"><?php echo htmlspecialchars($secmsg); ?></div><?php } ?>
<?php if ($secmsg === '') { ?>
<div class="main-content">
    <div class="ar-header"><h1 class="ar-title"><?php echo htmlspecialchars($pdfTitle); ?></h1><p class="ar-subtitle"><?php echo htmlspecialchars($pdfName); ?> - upload the PDF shown on the website.</p></div>
    <div class="ar-tabs">
        <?php foreach ($sections as $typeKey => $section) { ?>
            <a class="ar-tab<?php echo $typeKey === $pdfType ? ' active' : ''; ?>" href="turf-console/<?php echo htmlspecialchars($pageFile); ?>?sec=<?php echo urlencode($typeKey); ?>"><?php echo htmlspecialchars($section['label']); ?></a>
        <?php } ?>
    </div>
    <?php if ($flash !== '') { ?><div class="message <?php echo htmlspecialchars($flashType); ?>"><?php echo htmlspecialchars($flash); ?></div><?php } ?>
    <div class="ar-card">
        <div class="ar-card-title">PDF File</div>
        <?php if (!empty($current['file_url'])) { ?><p style="font-size:13px;color:#7a8c84;margin:-6px 0 16px">A PDF is currently uploaded. Uploading another file replaces it.</p><?php } ?>
        <form id="pdfForm" method="post" action="turf-console/<?php echo htmlspecialchars($pageFile); ?>" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>"><input type="hidden" name="sec" value="<?php echo htmlspecialchars($pdfType); ?>">
            <div class="ar-field">
                <label>PDF File <span style="color:#c0392b">*</span></label>
                <div class="ar-drop" id="pdfDrop"><div class="ar-drop-icon"><i class="fas fa-cloud-upload-alt"></i></div><p class="ar-drop-text">Drag &amp; drop PDF here or <span>browse</span></p><p class="ar-drop-sub">Only PDF files are allowed</p></div>
                <input type="file" id="pdfFile" name="report_file" class="ar-file-input" accept="application/pdf,.pdf" required>
                <div class="ar-file-box" id="pdfFileBox"><div class="ar-file-ico"><i class="fas fa-file-pdf"></i></div><div class="ar-file-info"><div class="ar-file-name" id="pdfFileName"></div><div class="ar-file-size" id="pdfFileSize"></div></div></div>
                <div id="pdfError" style="display:none;color:#c0392b;font-size:12px;margin-top:6px">Please choose a valid PDF file.</div>
            </div>
            <div class="ar-actions"><button type="submit" class="ar-btn ar-btn-primary"><i class="fas fa-save"></i> Save PDF</button><button type="button" class="ar-btn ar-btn-secondary" id="pdfReset"><i class="fas fa-undo"></i> Reset</button></div>
        </form>
    </div>
    <div class="ar-card">
        <div class="ar-card-title">Current PDF</div>
        <?php if (!empty($current['file_url'])) { ?>
            <table class="ar-table"><thead><tr><th>File</th><th style="width:70px;text-align:center">View</th></tr></thead><tbody><tr><td><div class="ar-t-file"><i class="fas fa-file-pdf"></i><span><?php echo htmlspecialchars(menuPdfLabel($current['file_url'])); ?></span></div></td><td style="text-align:center"><a class="ar-icon-btn" title="View PDF" target="_blank" rel="noopener" href="<?php echo htmlspecialchars($current['file_url']); ?>"><i class="fas fa-eye"></i></a></td></tr></tbody></table>
        <?php } else { ?><div class="ar-empty">No PDF uploaded yet.</div><?php } ?>
    </div>
</div>
<script>
(function(){
    var drop=document.getElementById('pdfDrop'),file=document.getElementById('pdfFile'),box=document.getElementById('pdfFileBox'),name=document.getElementById('pdfFileName'),size=document.getElementById('pdfFileSize'),form=document.getElementById('pdfForm'),error=document.getElementById('pdfError');
    function formatSize(bytes){return bytes<1024?bytes+' B':bytes<1048576?(bytes/1024).toFixed(1)+' KB':(bytes/1048576).toFixed(2)+' MB';}
    function showFile(f){name.textContent=f.name;size.textContent=formatSize(f.size);box.classList.add('show');error.style.display='none';}
    function acceptFile(f){if(!f||!(/\.pdf$/i).test(f.name)){file.value='';box.classList.remove('show');error.style.display='block';return;}showFile(f);}
    drop.addEventListener('click',function(){file.click();});
    file.addEventListener('change',function(){if(file.files[0])acceptFile(file.files[0]);});
    ['dragenter','dragover'].forEach(function(ev){drop.addEventListener(ev,function(e){e.preventDefault();drop.classList.add('dragover');});});
    ['dragleave','drop'].forEach(function(ev){drop.addEventListener(ev,function(e){e.preventDefault();drop.classList.remove('dragover');});});
    drop.addEventListener('drop',function(e){var files=e.dataTransfer.files;if(files&&files[0]){try{file.files=files;}catch(err){}acceptFile(files[0]);}});
    document.getElementById('pdfReset').addEventListener('click',function(){form.reset();box.classList.remove('show');error.style.display='none';});
    form.addEventListener('submit',function(e){if(!file.files.length||!(/\.pdf$/i).test(file.files[0].name)){e.preventDefault();error.style.display='block';}});
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
