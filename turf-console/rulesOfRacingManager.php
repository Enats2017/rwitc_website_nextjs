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

$pdfType    = 'rules_of_racing';
$pdfTitle   = 'Rules of Racing';
$moduleKey  = 'rules_of_racing';
$pageFile   = 'rulesOfRacingManager.php';
$s3Folder   = 'uploads/club/';
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
$flash = isset($_SESSION[$sessionKey . '_flash']['text']) ? $_SESSION[$sessionKey . '_flash']['text'] : '';
$flashType = isset($_SESSION[$sessionKey . '_flash']['type']) ? $_SESSION[$sessionKey . '_flash']['type'] : '';
unset($_SESSION[$sessionKey . '_flash']);

function rulesRacingSetFlash($text, $type) {
    global $sessionKey;
    $_SESSION[$sessionKey . '_flash'] = array('text' => $text, 'type' => $type);
}

function rulesRacingS3Client() {
    return new S3Client(array(
        'version' => 'latest',
        'region' => AWS_REGION,
        'credentials' => array('key' => AWS_ACCESS_KEY_ID, 'secret' => AWS_SECRET_ACCESS_KEY)
    ));
}

function rulesRacingKeyFromUrl($url) {
    $path = urldecode((string) parse_url((string) $url, PHP_URL_PATH));
    $pos = strpos($path, 'uploads/club/');
    return $pos === false ? '' : substr($path, $pos);
}

function rulesRacingDeleteFromS3($url) {
    $key = rulesRacingKeyFromUrl($url);
    if ($key === '') return;
    try {
        rulesRacingS3Client()->deleteObject(array('Bucket' => AWS_BUCKET, 'Key' => $key));
    } catch (AwsException $e) {
        error_log('Rules of Racing S3 delete failed: ' . $e->getMessage());
    }
}

function rulesRacingUpload($tmpFile, &$error) {
    global $s3Folder;
    $mime = function_exists('mime_content_type') ? mime_content_type($tmpFile) : 'application/pdf';
    $header = file_get_contents($tmpFile, false, null, 0, 5);
    if ($mime !== 'application/pdf' || $header !== '%PDF-') {
        $error = 'Invalid PDF file.';
        return '';
    }

    // Fixed S3 key: every upload replaces the same object
    try {
        $result = rulesRacingS3Client()->putObject(array(
            'Bucket' => AWS_BUCKET,
            'Key' => $s3Folder . 'Rules_of_Racing.pdf',
            'SourceFile' => $tmpFile,
            'ContentType' => 'application/pdf'
        ));
        return isset($result['ObjectURL']) ? $result['ObjectURL'] : '';
    } catch (AwsException $e) {
        error_log('Rules of Racing S3 upload failed: ' . $e->getMessage());
        $error = 'Could not upload the PDF. Please try again.';
        return '';
    }
}

function rulesRacingFileLabel($url) {
    $name = basename(urldecode((string) parse_url((string) $url, PHP_URL_PATH)));
    return $name === '' ? 'Rules_of_Racing.pdf' : $name;
}

if (empty($secmsg) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf']) || !hash_equals($csrf, (string) $_POST['csrf'])) {
        rulesRacingSetFlash('Invalid request. Please try again.', 'error');
        header('Location: ' . $pageFile);
        exit;
    }

    $error = '';
    if (!isset($_FILES['report_file']) || $_FILES['report_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please choose a PDF file.';
    } elseif (strtolower(pathinfo($_FILES['report_file']['name'], PATHINFO_EXTENSION)) !== 'pdf') {
        $error = 'Only PDF files are allowed.';
    }

    if ($error === '') {
        $newUrl = rulesRacingUpload($_FILES['report_file']['tmp_name'], $error);
        if ($error === '' && $newUrl !== '') {
            try {
                // Only one row for this type: insert the first time, update every time after
                $existing = $db->getMultiDimensionalArray("SELECT id, file_url FROM club_menu_pdf WHERE `type` = '" . $pdfType . "' ORDER BY id ASC");

                if (!empty($existing)) {
                    $keepId = (int) $existing[0]['id'];
                    $db->update("UPDATE club_menu_pdf SET file_url = '" . $db->escape($newUrl) . "', `date` = CURDATE() WHERE id = " . $keepId . " AND `type` = '" . $pdfType . "'");
                    // Remove any old duplicate rows (and their files) of this type
                    foreach ($existing as $i => $old) {
                        if ($i > 0) {
                            $db->query("DELETE FROM club_menu_pdf WHERE id = " . (int) $old['id'] . " AND `type` = '" . $pdfType . "'");
                        }
                        if ($old['file_url'] !== '' && rulesRacingKeyFromUrl($old['file_url']) !== rulesRacingKeyFromUrl($newUrl)) {
                            rulesRacingDeleteFromS3($old['file_url']);
                        }
                    }
                    rulesRacingSetFlash($pdfTitle . ' was updated successfully.', 'success');
                } else {
                    $db->insert("INSERT INTO club_menu_pdf (`type`, file_url, `date`) VALUES ('" . $pdfType . "', '" . $db->escape($newUrl) . "', CURDATE())");
                    rulesRacingSetFlash($pdfTitle . ' was saved successfully.', 'success');
                }
            } catch (Exception $e) {
                error_log('Rules of Racing database save failed: ' . $e->getMessage());
                rulesRacingSetFlash('Database error. Could not save the PDF.', 'error');
            }
        } else {
            rulesRacingSetFlash($error !== '' ? $error : 'Could not upload the PDF. Please try again.', 'error');
        }
    } else {
        rulesRacingSetFlash($error, 'error');
    }

    header('Location: ' . $pageFile);
    exit;
}

$row = null;
if (empty($secmsg)) {
    $row = $db->getSingleRowAssoc("SELECT id, file_url, `date` FROM club_menu_pdf WHERE `type` = '" . $pdfType . "' ORDER BY id ASC LIMIT 1");
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
.ar-header{margin-bottom:20px}.ar-title{font-size:24px;font-weight:700;color:#2b332f;margin:0}.ar-subtitle{font-size:14px;color:#7a8c84;margin:6px 0 0}.ar-card{background:#fff;border:1px solid #e2e6e4;border-radius:12px;padding:24px;box-shadow:0 1px 2px rgba(0,0,0,.03);max-width:100%;margin-bottom:26px}.ar-card-title{font-size:17px;font-weight:600;color:#2b332f;margin:0 0 20px;padding-bottom:14px;border-bottom:1px solid #eef1ef}.ar-field{margin-bottom:18px}.ar-field label{display:block;font-size:13px;font-weight:600;color:#2b332f;margin-bottom:6px}.ar-drop{border:2px dashed #b9c4be;border-radius:12px;padding:32px 16px;text-align:center;cursor:pointer;background:#f8faf9}.ar-drop:hover,.ar-drop.dragover{border-color:#1a7a45;background:#eef7f1}.ar-drop-icon{font-size:32px;color:#0f5c33;margin-bottom:10px}.ar-drop-text{font-size:15px;font-weight:500;color:#2b332f;margin:0 0 4px}.ar-drop-text span{color:#0f5c33;text-decoration:underline}.ar-drop-sub{font-size:12px;color:#7a8c84;margin:0}.ar-file-input{display:none}.ar-file-box{display:none;align-items:center;gap:12px;margin-top:12px;padding:12px 14px;border:1px solid #e2e6e4;border-radius:10px}.ar-file-box.show{display:flex}.ar-file-ico{width:38px;height:38px;border-radius:9px;background:#0f5c33;color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0}.ar-file-info{flex:1;min-width:0}.ar-file-name{font-size:14px;font-weight:500;color:#2b332f;overflow-wrap:anywhere}.ar-file-size{font-size:12px;color:#7a8c84}.ar-actions{display:flex;gap:10px;margin-top:8px;padding-top:18px;border-top:1px solid #eef1ef;flex-wrap:wrap}.ar-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:11px 22px;font-size:15px;font-weight:600;border-radius:8px;border:1px solid transparent;cursor:pointer;text-decoration:none}.ar-btn-primary{background:#0f5c33;color:#fff}.ar-btn-primary:hover{background:#1a7a45;color:#fff}.ar-btn-secondary{background:#fff;color:#2b332f;border-color:#d3d9d6}.ar-table-wrap{width:100%;overflow-x:auto}.ar-table{width:100%;border-collapse:collapse;min-width:460px}.ar-table th{text-align:left;font-size:12px;color:#0f5c33;background:#f3f6f4;padding:11px 12px;border-bottom:1px solid #e2e6e4}.ar-table td{font-size:14px;color:#2b332f;padding:12px;border-bottom:1px solid #eef1ef;vertical-align:middle}.ar-t-file{display:flex;align-items:center;gap:10px;font-weight:600;overflow-wrap:anywhere}.ar-t-file i{color:#c0392b;font-size:18px}.ar-icon-btn{width:34px;height:34px;border-radius:8px;border:1px solid #d3d9d6;background:#fff;color:#2b332f;display:inline-flex;align-items:center;justify-content:center;text-decoration:none}.ar-icon-btn:hover{border-color:#1a7a45;color:#0f5c33;background:#eef7f1}.ar-empty{text-align:center;padding:24px 10px;color:#7a8c84;font-size:14px}html,body{scrollbar-width:none;-ms-overflow-style:none}html::-webkit-scrollbar,body::-webkit-scrollbar{display:none}
@media(min-width:1920px){.ar-title{font-size:30px}.ar-card{padding:30px}}@media(max-width:700px){.ar-title{font-size:20px}.ar-card{padding:16px;border-radius:10px}.ar-drop{padding:24px 12px}}@media(max-width:560px){.ar-actions{flex-direction:column}.ar-btn{width:100%;box-sizing:border-box}}
</style>
<?php if ($secmsg !== '') { ?><div class="message"><?php echo htmlspecialchars($secmsg); ?></div><?php } ?>
<?php if ($secmsg === '') { ?>
<div class="main-content">
    <div class="ar-header">
        <h1 class="ar-title"><?php echo htmlspecialchars($pdfTitle); ?></h1>
        <p class="ar-subtitle">Upload the PDF. Only one Rules of Racing file is kept; uploading again replaces the current file.</p>
    </div>
    <?php if ($flash !== '') { ?><div class="message <?php echo htmlspecialchars($flashType); ?>"><?php echo htmlspecialchars($flash); ?></div><?php } ?>

    <div class="ar-card">
        <div class="ar-card-title">Upload Rules of Racing PDF</div>
        <form id="pdfForm" method="post" action="turf-console/<?php echo htmlspecialchars($pageFile); ?>" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
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
        <div class="ar-card-title">Current Rules of Racing</div>
        <?php if (!$row) { ?>
            <div class="ar-empty">No Rules of Racing PDF uploaded yet.</div>
        <?php } else { ?>
            <div class="ar-table-wrap">
                <table class="ar-table">
                    <thead><tr><th>Menu Title</th><th style="width:150px">Uploaded Date</th><th style="width:80px;text-align:center">View</th></tr></thead>
                    <tbody>
                        <tr>
                            <td><div class="ar-t-file"><i class="fas fa-file-pdf"></i><span><?php echo htmlspecialchars($pdfTitle); ?><br><small style="font-weight:400;color:#7a8c84"><?php echo htmlspecialchars(rulesRacingFileLabel($row['file_url'])); ?></small></span></div></td>
                            <td><?php echo !empty($row['date']) ? htmlspecialchars(date('d M Y', strtotime($row['date']))) : '—'; ?></td>
                            <td style="text-align:center"><a class="ar-icon-btn" title="View PDF" target="_blank" rel="noopener noreferrer" href="<?php echo htmlspecialchars($row['file_url']); ?>"><i class="fas fa-eye"></i></a></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php } ?>
    </div>
</div>
<script>
(function(){
    var drop=document.getElementById('pdfDrop'),file=document.getElementById('pdfFile'),box=document.getElementById('pdfFileBox'),name=document.getElementById('pdfFileName'),size=document.getElementById('pdfFileSize'),form=document.getElementById('pdfForm'),error=document.getElementById('pdfError'),reset=document.getElementById('pdfReset');
    function formatSize(bytes){return bytes<1024?bytes+' B':bytes<1048576?(bytes/1024).toFixed(1)+' KB':(bytes/1048576).toFixed(2)+' MB';}
    function showFile(f){name.textContent=f.name;size.textContent=formatSize(f.size);box.classList.add('show');error.style.display='none';}
    function acceptFile(f){if(!f||!(/\.pdf$/i).test(f.name)){file.value='';box.classList.remove('show');error.style.display='block';return;}showFile(f);}
    drop.addEventListener('click',function(){file.click();});
    file.addEventListener('change',function(){if(file.files[0])acceptFile(file.files[0]);});
    ['dragenter','dragover'].forEach(function(ev){drop.addEventListener(ev,function(e){e.preventDefault();drop.classList.add('dragover');});});
    ['dragleave','drop'].forEach(function(ev){drop.addEventListener(ev,function(e){e.preventDefault();drop.classList.remove('dragover');});});
    drop.addEventListener('drop',function(e){var files=e.dataTransfer.files;if(files&&files[0]){try{file.files=files;}catch(err){}acceptFile(files[0]);}});
    reset.addEventListener('click',function(){form.reset();box.classList.remove('show');error.style.display='none';});
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