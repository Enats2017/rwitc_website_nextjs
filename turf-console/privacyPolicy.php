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

$docType    = 'privacy_policy';
$docTitle   = 'Privacy Policy';
$moduleKey  = 'privacy_policy';
$pageFile   = 'privacyPolicy.php';
$s3Folder   = 'uploads/footer/';
$s3BaseName = 'Privacy_Policy';
$maxMb      = 100;
$maxBytes   = $maxMb * 1024 * 1024; // 100 MB
$sessionKey = 'menudoc_' . $docType;

$tooLargeMsg = 'File is too large. You can upload files up to ' . $maxMb . ' MB only.';

// Security checks
$secmsg = '';
if (!isAdminlogin()) {
    $secmsg = 'You do not have access to this page.';
} elseif (!hasModuleAccess($moduleKey)) {
    $secmsg = 'You do not have access to this module.';
}

// CSRF token and flash message
if (empty($_SESSION[$sessionKey . '_csrf'])) {
    $_SESSION[$sessionKey . '_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION[$sessionKey . '_csrf'];
$flash = isset($_SESSION[$sessionKey . '_flash']['text']) ? $_SESSION[$sessionKey . '_flash']['text'] : '';
$flashType = isset($_SESSION[$sessionKey . '_flash']['type']) ? $_SESSION[$sessionKey . '_flash']['type'] : '';
unset($_SESSION[$sessionKey . '_flash']);

function ppSetFlash($text, $type) {
    global $sessionKey;
    $_SESSION[$sessionKey . '_flash'] = array('text' => $text, 'type' => $type);
}

// Allowed extensions => accepted mime types
function ppAllowedTypes() {
    return array(
        'pdf'  => array('application/pdf'),
        'doc'  => array('application/msword', 'application/octet-stream', 'application/x-ole-storage'),
        'docx' => array('application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'),
        'xls'  => array('application/vnd.ms-excel', 'application/octet-stream', 'application/x-ole-storage'),
        'xlsx' => array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'),
        'ppt'  => array('application/vnd.ms-powerpoint', 'application/octet-stream', 'application/x-ole-storage'),
        'pptx' => array('application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip', 'application/octet-stream'),
        'txt'  => array('text/plain'),
        'rtf'  => array('application/rtf', 'text/rtf', 'text/plain'),
        'csv'  => array('text/csv', 'application/csv', 'text/plain')
    );
}

// Content-Type sent to S3 for each extension
function ppContentType($ext) {
    $map = array(
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'  => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt'  => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'txt'  => 'text/plain',
        'rtf'  => 'application/rtf',
        'csv'  => 'text/csv'
    );
    return isset($map[$ext]) ? $map[$ext] : 'application/octet-stream';
}

function ppS3Client() {
    return new S3Client(array(
        'version' => 'latest',
        'region' => AWS_REGION,
        'credentials' => array('key' => AWS_ACCESS_KEY_ID, 'secret' => AWS_SECRET_ACCESS_KEY)
    ));
}

// Returns the S3 key of a stored URL. The old club folder is also recognised,
// so an older file stored there can still be cleaned up after the move to the footer folder.
function ppKeyFromUrl($url) {
    $path = urldecode((string) parse_url((string) $url, PHP_URL_PATH));
    foreach (array('uploads/footer/', 'uploads/club/') as $prefix) {
        $pos = strpos($path, $prefix);
        if ($pos !== false) {
            return substr($path, $pos);
        }
    }
    return '';
}

function ppDeleteFromS3($url) {
    $key = ppKeyFromUrl($url);
    if ($key === '') return;
    try {
        ppS3Client()->deleteObject(array('Bucket' => AWS_BUCKET, 'Key' => $key));
    } catch (AwsException $e) {
        error_log('Privacy policy document S3 delete failed: ' . $e->getMessage());
    }
}

function ppUpload($tmpFile, $originalName, &$error) {
    global $s3Folder, $s3BaseName, $maxBytes, $tooLargeMsg;

    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowed = ppAllowedTypes();

    if (!isset($allowed[$ext])) {
        $error = 'This file type is not allowed.';
        return '';
    }

    if (filesize($tmpFile) > $maxBytes) {
        $error = $tooLargeMsg;
        return '';
    }

    // Content must match the extension
    $mime = function_exists('mime_content_type') ? mime_content_type($tmpFile) : '';
    if ($mime !== '' && !in_array($mime, $allowed[$ext], true)) {
        $error = 'File content does not match its extension.';
        return '';
    }

    // Extra check for PDF: real PDF header
    if ($ext === 'pdf' && file_get_contents($tmpFile, false, null, 0, 5) !== '%PDF-') {
        $error = 'Invalid PDF file.';
        return '';
    }

    // Fixed base name: every upload replaces the same object (for the same extension)
    try {
        $result = ppS3Client()->putObject(array(
            'Bucket' => AWS_BUCKET,
            'Key' => $s3Folder . $s3BaseName . '.' . $ext,
            'SourceFile' => $tmpFile,
            'ContentType' => ppContentType($ext)
        ));
        return isset($result['ObjectURL']) ? $result['ObjectURL'] : '';
    } catch (AwsException $e) {
        error_log('Privacy policy document S3 upload failed: ' . $e->getMessage());
        $error = 'Could not upload the file. Please try again.';
        return '';
    }
}

function ppFileLabel($url) {
    $name = basename(urldecode((string) parse_url((string) $url, PHP_URL_PATH)));
    return $name === '' ? 'Privacy_Policy' : $name;
}

function ppFileExt($url) {
    return strtolower(pathinfo(urldecode((string) parse_url((string) $url, PHP_URL_PATH)), PATHINFO_EXTENSION));
}

function ppFileIcon($ext) {
    if ($ext === 'pdf') return 'fa-file-pdf';
    if ($ext === 'doc' || $ext === 'docx' || $ext === 'rtf') return 'fa-file-word';
    if ($ext === 'xls' || $ext === 'xlsx' || $ext === 'csv') return 'fa-file-excel';
    if ($ext === 'ppt' || $ext === 'pptx') return 'fa-file-powerpoint';
    return 'fa-file-alt';
}

// When the request is bigger than the server's post_max_size, PHP drops both $_POST and $_FILES
if (empty($secmsg) && $_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES) && !empty($_SERVER['CONTENT_LENGTH'])) {
    ppSetFlash($tooLargeMsg, 'error');
    header('Location: ' . $pageFile);
    exit;
}

// Handle upload
if (empty($secmsg) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf']) || !hash_equals($csrf, (string) $_POST['csrf'])) {
        ppSetFlash('Invalid request. Please try again.', 'error');
        header('Location: ' . $pageFile);
        exit;
    }

    $error = '';
    $uploadErr = isset($_FILES['doc_file']) ? $_FILES['doc_file']['error'] : UPLOAD_ERR_NO_FILE;

    if ($uploadErr === UPLOAD_ERR_INI_SIZE || $uploadErr === UPLOAD_ERR_FORM_SIZE) {
        $error = $tooLargeMsg;
    } elseif ($uploadErr !== UPLOAD_ERR_OK) {
        $error = 'Please choose a file.';
    }

    if ($error === '') {
        $newUrl = ppUpload($_FILES['doc_file']['tmp_name'], $_FILES['doc_file']['name'], $error);
        if ($error === '' && $newUrl !== '') {
            try {
                // Only one row for this type: insert the first time, update every time after
                $existing = $db->getMultiDimensionalArray("SELECT id, file_url FROM club_menu_pdf WHERE `type` = '" . $docType . "' ORDER BY id ASC");

                if (!empty($existing)) {
                    $keepId = (int) $existing[0]['id'];
                    $db->update("UPDATE club_menu_pdf SET file_url = '" . $db->escape($newUrl) . "', `date` = CURDATE() WHERE id = " . $keepId . " AND `type` = '" . $docType . "'");
                    // Remove duplicate rows and old files (a different extension or folder means a different S3 key)
                    foreach ($existing as $i => $old) {
                        if ($i > 0) {
                            $db->query("DELETE FROM club_menu_pdf WHERE id = " . (int) $old['id'] . " AND `type` = '" . $docType . "'");
                        }
                        if ($old['file_url'] !== '' && ppKeyFromUrl($old['file_url']) !== ppKeyFromUrl($newUrl)) {
                            ppDeleteFromS3($old['file_url']);
                        }
                    }
                    ppSetFlash($docTitle . ' was updated successfully.', 'success');
                } else {
                    $db->insert("INSERT INTO club_menu_pdf (`type`, file_url, `date`) VALUES ('" . $docType . "', '" . $db->escape($newUrl) . "', CURDATE())");
                    ppSetFlash($docTitle . ' was saved successfully.', 'success');
                }
            } catch (Exception $e) {
                error_log('Privacy policy document database save failed: ' . $e->getMessage());
                ppSetFlash('Database error. Could not save the file.', 'error');
            }
        } else {
            ppSetFlash($error !== '' ? $error : 'Could not upload the file. Please try again.', 'error');
        }
    } else {
        ppSetFlash($error, 'error');
    }

    header('Location: ' . $pageFile);
    exit;
}

// Load current record
$row = null;
if (empty($secmsg)) {
    $row = $db->getSingleRowAssoc("SELECT id, file_url, `date` FROM club_menu_pdf WHERE `type` = '" . $docType . "' ORDER BY id ASC LIMIT 1");
}

$pageTitle = $docTitle;
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
.ar-header{margin-bottom:20px}.ar-title{font-size:24px;font-weight:700;color:#2b332f;margin:0}.ar-subtitle{font-size:14px;color:#7a8c84;margin:6px 0 0}.ar-card{background:#fff;border:1px solid #e2e6e4;border-radius:12px;padding:24px;box-shadow:0 1px 2px rgba(0,0,0,.03);max-width:100%;margin-bottom:26px}.ar-card-title{font-size:17px;font-weight:600;color:#2b332f;margin:0 0 20px;padding-bottom:14px;border-bottom:1px solid #eef1ef}.ar-field{margin-bottom:18px}.ar-field label{display:block;font-size:13px;font-weight:600;color:#2b332f;margin-bottom:6px}.ar-drop{border:2px dashed #b9c4be;border-radius:12px;padding:32px 16px;text-align:center;cursor:pointer;background:#f8faf9}.ar-drop:hover,.ar-drop.dragover{border-color:#1a7a45;background:#eef7f1}.ar-drop-icon{font-size:32px;color:#0f5c33;margin-bottom:10px}.ar-drop-text{font-size:15px;font-weight:500;color:#2b332f;margin:0 0 4px}.ar-drop-text span{color:#0f5c33;text-decoration:underline}.ar-drop-sub{font-size:12px;color:#7a8c84;margin:0}.ar-file-input{display:none}.ar-file-box{display:none;align-items:center;gap:12px;margin-top:12px;padding:12px 14px;border:1px solid #e2e6e4;border-radius:10px}.ar-file-box.show{display:flex}.ar-file-ico{width:38px;height:38px;border-radius:9px;background:#0f5c33;color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0}.ar-file-info{flex:1;min-width:0}.ar-file-name{font-size:14px;font-weight:500;color:#2b332f;overflow-wrap:anywhere}.ar-file-size{font-size:12px;color:#7a8c84}.ar-actions{display:flex;gap:10px;margin-top:8px;padding-top:18px;border-top:1px solid #eef1ef;flex-wrap:wrap}.ar-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:11px 22px;font-size:15px;font-weight:600;border-radius:8px;border:1px solid transparent;cursor:pointer;text-decoration:none}.ar-btn-primary{background:#0f5c33;color:#fff}.ar-btn-primary:hover{background:#1a7a45;color:#fff}.ar-btn-secondary{background:#fff;color:#2b332f;border-color:#d3d9d6}.ar-table-wrap{width:100%;overflow-x:auto}.ar-table{width:100%;border-collapse:collapse;min-width:460px}.ar-table th{text-align:left;font-size:12px;color:#0f5c33;background:#f3f6f4;padding:11px 12px;border-bottom:1px solid #e2e6e4}.ar-table td{font-size:14px;color:#2b332f;padding:12px;border-bottom:1px solid #eef1ef;vertical-align:middle}.ar-t-file{display:flex;align-items:center;gap:10px;font-weight:600;overflow-wrap:anywhere}.ar-t-file i{color:#0f5c33;font-size:18px}.ar-t-file i.fa-file-pdf{color:#c0392b}.ar-icon-btn{width:34px;height:34px;border-radius:8px;border:1px solid #d3d9d6;background:#fff;color:#2b332f;display:inline-flex;align-items:center;justify-content:center;text-decoration:none}.ar-icon-btn:hover{border-color:#1a7a45;color:#0f5c33;background:#eef7f1}.ar-empty{text-align:center;padding:24px 10px;color:#7a8c84;font-size:14px}html,body{scrollbar-width:none;-ms-overflow-style:none}html::-webkit-scrollbar,body::-webkit-scrollbar{display:none}
@media(min-width:1920px){.ar-title{font-size:30px}.ar-card{padding:30px}}@media(max-width:700px){.ar-title{font-size:20px}.ar-card{padding:16px;border-radius:10px}.ar-drop{padding:24px 12px}}@media(max-width:560px){.ar-actions{flex-direction:column}.ar-btn{width:100%;box-sizing:border-box}}
</style>
<?php if ($secmsg !== '') { ?><div class="message"><?php echo htmlspecialchars($secmsg); ?></div><?php } ?>
<?php if ($secmsg === '') { ?>
<div class="main-content">
    <div class="ar-header">
        <h1 class="ar-title"><?php echo htmlspecialchars($docTitle); ?></h1>
        <p class="ar-subtitle">Upload a document (PDF, Word, Excel, PowerPoint, TXT, RTF or CSV) up to <?php echo (int) $maxMb; ?> MB. Only one file is kept; uploading again replaces the current file.</p>
    </div>
    <?php if ($flash !== '') { ?><div class="message <?php echo htmlspecialchars($flashType); ?>"><?php echo htmlspecialchars($flash); ?></div><?php } ?>

    <div class="ar-card">
        <div class="ar-card-title">Upload <?php echo htmlspecialchars($docTitle); ?> Document</div>
        <form id="docForm" method="post" action="turf-console/<?php echo htmlspecialchars($pageFile); ?>" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
            <div class="ar-field">
                <label>Document File <span style="color:#c0392b">*</span></label>
                <div class="ar-drop" id="docDrop"><div class="ar-drop-icon"><i class="fas fa-cloud-upload-alt"></i></div><p class="ar-drop-text">Drag &amp; drop file here or <span>browse</span></p><p class="ar-drop-sub">PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, RTF, CSV (max <?php echo (int) $maxMb; ?> MB)</p></div>
                <input type="file" id="docFile" name="doc_file" class="ar-file-input" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.rtf,.csv" required>
                <div class="ar-file-box" id="docFileBox"><div class="ar-file-ico"><i class="fas fa-file-alt"></i></div><div class="ar-file-info"><div class="ar-file-name" id="docFileName"></div><div class="ar-file-size" id="docFileSize"></div></div></div>
                <div id="docError" style="display:none;color:#c0392b;font-size:12px;margin-top:6px"></div>
            </div>
            <div class="ar-actions"><button type="submit" class="ar-btn ar-btn-primary"><i class="fas fa-save"></i> Save File</button><button type="button" class="ar-btn ar-btn-secondary" id="docReset"><i class="fas fa-undo"></i> Reset</button></div>
        </form>
    </div>

    <div class="ar-card">
        <div class="ar-card-title">Current <?php echo htmlspecialchars($docTitle); ?></div>
        <?php if (!$row) { ?>
            <div class="ar-empty">No document uploaded yet.</div>
        <?php } else {
            $curExt = ppFileExt($row['file_url']);
            $canView = ($curExt === 'pdf' || $curExt === 'txt');
        ?>
            <div class="ar-table-wrap">
                <table class="ar-table">
                    <thead><tr><th>Menu Title</th><th style="width:150px">Uploaded Date</th><th style="width:90px;text-align:center"><?php echo $canView ? 'View' : 'Download'; ?></th></tr></thead>
                    <tbody>
                        <tr>
                            <td><div class="ar-t-file"><i class="fas <?php echo ppFileIcon($curExt); ?>"></i><span><?php echo htmlspecialchars($docTitle); ?><br><small style="font-weight:400;color:#7a8c84"><?php echo htmlspecialchars(ppFileLabel($row['file_url'])); ?></small></span></div></td>
                            <td><?php echo !empty($row['date']) ? htmlspecialchars(date('d M Y', strtotime($row['date']))) : '&mdash;'; ?></td>
                            <td style="text-align:center"><a class="ar-icon-btn" title="<?php echo $canView ? 'View file' : 'Download file'; ?>" target="_blank" rel="noopener noreferrer" href="<?php echo htmlspecialchars($row['file_url']); ?>"><i class="fas <?php echo $canView ? 'fa-eye' : 'fa-download'; ?>"></i></a></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php } ?>
    </div>
</div>
<script>
(function(){
    var maxMb=<?php echo (int) $maxMb; ?>,maxBytes=maxMb*1024*1024;
    var allowedRe=/\.(pdf|doc|docx|xls|xlsx|ppt|pptx|txt|rtf|csv)$/i;
    var typeMsg='Please choose a valid file (PDF, Word, Excel, PowerPoint, TXT, RTF or CSV).';
    var sizeMsg='File is too large. You can upload files up to '+maxMb+' MB only.';
    var drop=document.getElementById('docDrop'),file=document.getElementById('docFile'),box=document.getElementById('docFileBox'),name=document.getElementById('docFileName'),size=document.getElementById('docFileSize'),form=document.getElementById('docForm'),error=document.getElementById('docError'),reset=document.getElementById('docReset');
    function formatSize(bytes){return bytes<1024?bytes+' B':bytes<1048576?(bytes/1024).toFixed(1)+' KB':(bytes/1048576).toFixed(2)+' MB';}
    function showError(msg){error.textContent=msg;error.style.display='block';}
    function hideError(){error.style.display='none';}
    // Returns an error message, or an empty string when the file is valid
    function check(f){if(!f||!allowedRe.test(f.name))return typeMsg;if(f.size>maxBytes)return sizeMsg;return '';}
    function showFile(f){name.textContent=f.name;size.textContent=formatSize(f.size);box.classList.add('show');hideError();}
    function acceptFile(f){var msg=check(f);if(msg){file.value='';box.classList.remove('show');showError(msg);return;}showFile(f);}
    drop.addEventListener('click',function(){file.click();});
    file.addEventListener('change',function(){if(file.files[0])acceptFile(file.files[0]);});
    ['dragenter','dragover'].forEach(function(ev){drop.addEventListener(ev,function(e){e.preventDefault();drop.classList.add('dragover');});});
    ['dragleave','drop'].forEach(function(ev){drop.addEventListener(ev,function(e){e.preventDefault();drop.classList.remove('dragover');});});
    drop.addEventListener('drop',function(e){var files=e.dataTransfer.files;if(files&&files[0]){try{file.files=files;}catch(err){}acceptFile(files[0]);}});
    reset.addEventListener('click',function(){form.reset();box.classList.remove('show');hideError();});
    form.addEventListener('submit',function(e){var msg=file.files.length?check(file.files[0]):typeMsg;if(msg){e.preventDefault();showError(msg);}});
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