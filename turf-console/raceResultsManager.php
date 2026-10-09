<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once("../bootstrap.php");
require_once("../lib/dbTools.php");
require_once("../lib/userchecks.php");
require_once("../lib/permissions.php");
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../rwitc_website_api/config/config.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

session_start();

/* ================= SETTINGS ================= */



// rwitc_website/rwitc_upload/static/live/
define('RESULT_UPLOAD_DIR', dirname(__DIR__) . '/rwitc_upload/static/live/');



/* ================= ACCESS CHECK ================= */

if (!isAdminlogin() || !hasModuleAccess('race_results')) {
    header("Location: dashboard.php?msg=no_access");
    exit;
}

$db = new dbTool();

/* ================= CSRF TOKEN ================= */

if (empty($_SESSION['rr_token'])) {
    $_SESSION['rr_token'] = bin2hex(random_bytes(16));
}

/* ================= HANDLE UPLOAD ================= */

$error = null;

/* ================= HANDLE CLEAR ================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_result'])) {

    if (
        !isset($_POST['token']) ||
        !hash_equals($_SESSION['rr_token'], $_POST['token'])
    ) {
        $error = "Invalid request. Please refresh the page and try again.";
    } else {
        // $last = $db->getSingleRowAssoc(
        //     "SELECT id, file_name FROM race_results ORDER BY id DESC LIMIT 1"
        // );

        // if (!$last) {
        //     $error = "No uploaded file to clear.";
        // } else {
        //     // Sirf file name use karo, path nahi (security)
        //     $file = RESULT_UPLOAD_DIR . basename($last['file_name']);

        //     if (is_file($file)) {
        //         @unlink($file);
        //     }

        //     $db->query("DELETE FROM race_results WHERE id = " . (int)$last['id']);

        //     header("Location: raceResultsManager.php?msg=cleared");
        //     exit;
        // }



        $msg = $_SESSION['rr_success'] ?? null;
        unset($_SESSION['rr_success']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_result'])) {

    if (
        !isset($_POST['token']) ||
        !hash_equals($_SESSION['rr_token'], $_POST['token'])
    ) {
        $error = "Invalid request. Please refresh the page and try again.";
    } elseif (
        !isset($_FILES['result_file']) ||
        $_FILES['result_file']['error'] === UPLOAD_ERR_NO_FILE
    ) {
        $error = "Please choose a file to upload.";
    } elseif ($_FILES['result_file']['error'] !== UPLOAD_ERR_OK) {
        $error = "Upload failed (error code " . (int)$_FILES['result_file']['error'] . ").";
    } else {

        $tmp  = $_FILES['result_file']['tmp_name'];
        $name = $_FILES['result_file']['name'];
        $size = (int)$_FILES['result_file']['size'];
        $ext = pathinfo($name, PATHINFO_EXTENSION);

        if (!in_array(strtolower($ext), array('htm', 'html'), true)) {
            $error = "Only .htm or .html files are allowed.";
        } elseif ($size <= 0) {
            $error = "The uploaded file is empty.";
        } else {

            $content = file_get_contents($tmp);

            if ($content === false || stripos($content, '<?php') !== false || stripos($content, '<?=') !== false) {
                $error = "Invalid file content.";
            } else {

                if (!is_dir(RESULT_UPLOAD_DIR)) {
                    @mkdir(RESULT_UPLOAD_DIR, 0755, true);
                }


                $saveName = basename($name);

                if ($saveName === '' || $saveName === '.' || $saveName === '..') {
                    $saveName = 'result.' . $ext;
                }

                // $target = RESULT_UPLOAD_DIR . $saveName;

                // if (!move_uploaded_file($tmp, $target)) {
                //     $error = "Could not save the file. Check folder permissions for rwitc_upload/static/live/.";
                // } else {
                //     @chmod($target, 0644);

                //     try {
                //         $fname = $db->escape($saveName);

                //         $db->insert(
                //             "INSERT INTO race_results (upload_date, file_name)
                //              VALUES (NOW(), '$fname')"
                //         );

                //         header("Location: raceResultsManager.php?msg=uploaded");
                //         exit;
                //     } catch (Exception $e) {
                //         $error = "File saved but DB update failed: " . $e->getMessage();
                //     }
                // }


                // ===== S3 Upload =====
                $s3_url = '';
                try {
                    $s3Client = new S3Client([
                        'version'     => 'latest',
                        'region'      => AWS_REGION,
                        'credentials' => [
                            'key'    => AWS_ACCESS_KEY_ID,
                            'secret' => AWS_SECRET_ACCESS_KEY,
                        ],
                    ]);

                    $s3Key  = 'uploads/RaceResults/' . $saveName;
                    $result = $s3Client->putObject([
                        'Bucket'      => AWS_BUCKET,
                        'Key'         => $s3Key,
                        'SourceFile'  => $tmp,
                        'ContentType' => mime_content_type($tmp),
                    ]);

                    $s3_url = $result['ObjectURL'];
                } catch (AwsException $e) {
                    $s3_url = '';
                    $error = "S3 upload failed: " . $e->getMessage();
                }
                // ===== End S3 Upload =====

                if ($s3_url === '') {
                    if (!$error) {
                        $error = "S3 upload failed. File was not saved.";
                    }
                } else {
                    try {
                        $fname = $db->escape($s3_url);

                        $db->insert(
                            "INSERT INTO race_results (upload_date, file_name)
             VALUES (NOW(), '$fname')"
                        );

                        $_SESSION['rr_success'] = 'uploaded';
                        header("Location: raceResultsManager.php");
                        exit;
                    } catch (Exception $e) {
                        $error = "File saved but DB update failed: " . $e->getMessage();
                    }
                }
            }
        }
    }
}

/* ================= FETCH CURRENT RECORD ================= */

$current = $db->getSingleRowAssoc(
    "SELECT id, upload_date, file_name FROM race_results ORDER BY id DESC LIMIT 1"
);


$msg = $_SESSION['rr_success'] ?? null;
unset($_SESSION['rr_success']);

/* ================= PAGE ================= */

$design = new Design();
$design->js = '';
$design->jqueryJs = '';
$design->css = '
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
.rr-btn-clear{background:#fff;color:#c0392b;border:1px solid #f1c9c3;margin-left:10px;}
.rr-btn-clear:hover{background:#fbeae8;}
.rr-autohide{transition:opacity .4s ease;}
.rr-wrap{max-width:900px;margin:0 auto;}
.rr-head{margin-bottom:22px;padding-bottom:18px;border-bottom:1px solid #e4e8e4;}
.rr-eyebrow{font-size:11.5px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#c9a227;margin:0 0 6px;}
.rr-head h1{font-size:28px;font-weight:700;color:#0d3b28;margin:0;}
.rr-head p{margin:4px 0 0;color:#5b655f;font-size:13.5px;}
.rr-alert{padding:12px 18px;border-radius:8px;font-size:14px;font-weight:500;margin-bottom:18px;}
.rr-ok{background:#e9f3ec;color:#14532d;border:1px solid #cfe6d6;}
.rr-err{background:#fbeae8;color:#c0392b;border:1px solid #f1c9c3;}
.rr-card{background:#fff;border:1px solid #e4e8e4;border-radius:10px;box-shadow:0 1px 2px rgba(20,30,24,.04),0 4px 16px rgba(20,30,24,.05);padding:26px 28px;margin-bottom:22px;}
.rr-card h3{font-size:16px;font-weight:700;color:#0d3b28;margin:0 0 4px;}
.rr-card .rr-desc{font-size:12.5px;color:#5b655f;margin:0 0 16px;}
.rr-file{width:100%;padding:12px;border:1px dashed #1a6b3c;border-radius:8px;background:#f4f8f5;font-size:13.5px;}
.rr-btn{display:inline-flex;align-items:center;gap:8px;margin-top:16px;padding:10px 20px;border:0;border-radius:8px;background:#14532d;color:#fff;font-size:14px;font-weight:600;cursor:pointer;}
.rr-btn:hover{background:#0d3b28;}
.rr-link{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border:1px solid #e4e8e4;border-radius:8px;color:#14532d;font-size:13px;font-weight:600;text-decoration:none;}
.rr-table{width:100%;border-collapse:collapse;}
.rr-table th{text-align:left;font-size:11.5px;text-transform:uppercase;letter-spacing:.05em;color:#5b655f;padding:12px 14px;background:#fafbfa;border-bottom:1px solid #e4e8e4;}
.rr-table td{padding:12px 14px;font-size:14px;border-bottom:1px solid #e4e8e4;}
.rr-scroll{overflow-x:auto;}
.rr-preview{width:100%;height:420px;border:1px solid #e4e8e4;border-radius:8px;background:#fff;margin-top:16px;}
.rr-empty{padding:30px;text-align:center;color:#8a938d;}
@media(max-width:600px){.rr-card{padding:18px 14px;}.rr-head h1{font-size:22px;}}
</style>
';

$design->startPage("RWITC | Media Tips & updates");
$design->writeLogoTickerMenu();

$design->openDiv("contentWrapper");
$design->openDiv("infoWrapper", "col-lg-12");
$design->openDiv("leftArea", "col-lg-9");
$design->writeContentPageStyles();
?>

<div class="rr-wrap">

    <div class="rr-head">
        <p class="rr-eyebrow">Race Results</p>
        <h1>Media Tips & updates</h1>
        <p>Upload an HTML / HTM file. It will be saved with its original file name.</p>
    </div>

    <?php if ($msg === 'uploaded'): ?>
        <div class="rr-alert rr-ok rr-autohide"><i class="fa fa-check-circle"></i> File uploaded successfully.</div>
    <?php elseif ($msg === 'cleared'): ?>
        <div class="rr-alert rr-ok rr-autohide"><i class="fa fa-check-circle"></i> File cleared. You can upload again.</div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="rr-alert rr-err"><i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- UPLOAD -->
    <div class="rr-card">
        <h3>Media Tips & updates</h3>
        <p class="rr-desc">Allowed: .htm, .html. Uploading a file with the same name replaces the existing file.</p>

        <form method="post" enctype="multipart/form-data" action="turf-console/raceResultsManager.php">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['rr_token']); ?>">
            <input type="file" name="result_file" class="rr-file" accept=".htm,.html" required>
            <button type="submit" name="upload_result" value="1" class="rr-btn">
                <i class="fa fa-upload"></i> Upload File
            </button>

            <button type="submit" name="clear_result" value="1" class="rr-btn rr-btn-clear" formnovalidate>
                <i class="fa fa-trash"></i> Clear
            </button>
        </form>
    </div>

</div>

<script>
    document.querySelectorAll('.rr-autohide').forEach(function(el) {
        setTimeout(function() {
            el.style.opacity = '0';
            setTimeout(function() {
                el.style.display = 'none';
            }, 400);
        }, 2000);
    });
</script>

<?php
$design->closeDiv();
$design->writeLeftPanel();
$design->closeDiv();
$design->closeDiv();
$design->endPage();
$design = NULL;
?>