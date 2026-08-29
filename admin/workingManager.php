<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
include_once('../bootstrap.php');
require_once('../lib/articles.class.php');
include_once('../lib/pagination.class.php');
require_once("../lib/users.class.php");
require_once("../lib/userchecks.php");
/*require_once("../lib/facebook/facebook.php");
*/  
$baseurl = "http://www.rwitc.com/admin/postArticlesToFB.php";
  
$q = getParameterString('q','',$db);
session_start();                    
$uid = $_SESSION['uid'];             
$userObj = new Users($db);  

/*$facebook = new Facebook(array(
    'appId'  => FB_ARTICLES_APPID,
    'secret' => FB_ARTICLES_APPSECRET,
    'cookie' => true,
    ));
*/
 $msg = $secmsg = "";
 $pageno = getParameterNumber('pageno',1);
 $articles = new Articles($db);
if (isAdminlogin()) {
    if ($_SESSION['workingManager'] == "Y") { // check login
        // if (get_magic_quotes_gpc()) {
        //     function stripslashes_deep($value) {
        //         $value = is_array($value) ?   array_map('stripslashes_deep', $value) : stripslashes($value);
        //         return $value;
        //     }
        //     $_POST = array_map('stripslashes_deep', $_POST);
        //     $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
        // }
        
          $json = array();
          $json['success'] = '';
          $json['error'] = '';
          // all actions POST form submissions go here
          if (isset($_REQUEST['submit'])) {
              //$body = getParameterString('file','',$db);
              if (!empty($_FILES['file']['name']) && is_file($_FILES['file']['tmp_name'])) {
                // Sanitize the filename
                $filename = basename(html_entity_decode($_FILES['file']['name'], ENT_QUOTES, 'UTF-8'));
                
                // Allowed file extension types
                $allowed = array();
                $allowed[] = 'html';
                $allowed[] = 'htm';

                if (!in_array(strtolower(substr(strrchr($filename, '.'), 1)), $allowed)) {
                  $json['error'] = 'File Not Supported';
                }
                
                $allowed = array();
                $allowed[] = 'text/html';
                if (!in_array($_FILES['file']['type'], $allowed)) {
                  $json['error'] = 'Invalid File Type';
                }

                // Check to see if any PHP files are trying to be uploaded
                $content = file_get_contents($_FILES['file']['tmp_name']);

                if (preg_match('/\<\?php/i', $content)) {
                  $json['error'] = 'Invalid File Type';
                }

                // Return any upload error
                if ($_FILES['file']['error'] != UPLOAD_ERR_OK) {
                  $json['error'] = 'File Not Uploaded Properly';
                }
              } else {
                $json['error'] = 'File Not Uploaded Properly';
              }

              // echo '<pre>';
              // print_r($json);
              // exit;

              if ($json['error'] == '') {
                //echo 'aaaa';exit;
                $file = 'trustee.html';
                $upload_file_path = '/var/www/vhosts/rwitc.com/httpdocs/club/'.$file;
                //echo $upload_file_path;exit;
                move_uploaded_file($_FILES['file']['tmp_name'], $upload_file_path);
                $json['success'] = 'File Uploaded';
              }
              //update new working 
          }
          $download_file = "admin/custom_download.php";
      } else {
        $msg = "You do not have access to this page.";
      }  
} else {
    $secmsg = "Please login to access this page";
}
$pageTitle ='Working Group Manager';        
// create a template object
$design = new Design();  

$design->js='
<script type="text/javascript" src="lib/ckeditor/ckeditor.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
';
$design->css ='
<style type="text/css">
  #title { color: #000000; font-size: 14px; margin: 10px; margin: auto; text-align: left; display:block; }
</style>
';
$design->jqueryJs = ""; 
$design->startPage("$pageTitle");  
$design->writeLogoTickerMenu();
$design->openDiv("contentWrapper");
$design->openDiv("infoWrapper","col-lg-12");
$design->openDiv("leftArea","col-lg-9");
?>

<style type="text/css">
/* ===== layout: leftArea + sidebar, same pattern as the other managers ===== */
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
#infoWrapper.col-lg-12 #rightArea.col-lg-3 { padding-top: 0 !important; }

.message {
    position: relative;
    background: #e6f4ec;
    border: 1px solid #b7ddc5;
    color: #0f5c33;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 15px;
    font-size: 14.5px;
    font-weight: 500;
}


html, body { scrollbar-width: none; -ms-overflow-style: none; }
html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }

.working-header { margin-bottom: 20px; }
.working-header h2 { margin: 0; font-size: 22px; color: #2b332f; font-weight: 700; }
.working-header p { margin: 4px 0 0; font-size: 13.5px; color: #7a8c84; }

/* ===== upload form — normal inline screen card ===== */
.working-form-wrap {
    background: #fff;
    border: 1px solid #e2e6e4;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}
.working-form-table { width: 100%; border-collapse: collapse; }
.working-form-table th { text-align: left; padding: 10px 8px; color: #2b332f; vertical-align: top; width: 20%; font-weight: 600; font-size: 13.5px; }
.working-form-table td { padding: 10px 8px; }
.working-form-table input[type="file"] {
    border: 1px solid #e2e6e4; border-radius: 6px; padding: 8px 10px; font-size: 14px;
    width: 100%; max-width: 100%; box-sizing: border-box; background: #f7faf8;
}
.working-form-table input[type="submit"] { background: #0f5c33; color: #fff; border: none; padding: 9px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; margin-right: 8px; }
.working-form-table input[type="submit"]:hover { background: #0b3d24; }
.working-form-table input[type="reset"] { background: #fff; color: #2b332f; border: 1px solid #e2e6e4; padding: 9px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; margin-right: 8px; }
.working-form-table input[type="reset"]:hover { background: #f5f4ee; }
.working-form-table a.button {
    display: inline-flex; align-items: center; gap: 6px;
    background: #fff; border: 1px solid #1a7a45; color: #0f5c33 !important;
    padding: 9px 16px !important; border-radius: 6px; text-decoration: none !important;
    font-size: 14px; font-weight: 600;
}
.working-form-table a.button:hover { background: #e6f4ec; }

/* ===== responsive ===== */
@media (max-width: 900px) {
    #infoWrapper.col-lg-12 { flex-direction: column; margin: 16px auto; }
    #leftArea.col-lg-9 { flex: 1 1 100%; max-width: 100%; padding: 28px 24px; }
}
@media (max-width: 700px) {
    #leftArea.col-lg-9 { padding: 0 16px; }
}
@media (max-width: 520px) {
    .working-form-table, .working-form-table tbody, .working-form-table tr, .working-form-table th, .working-form-table td {
        display: block; width: 100% !important;
    }
    .working-form-table th { padding-bottom: 2px; }
    .working-form-table td { padding-top: 0; padding-bottom: 14px; }
    .working-form-table input[type="submit"],
    .working-form-table input[type="reset"],
    .working-form-table a.button { margin-bottom: 8px; }
}
</style>

    <?php if (!empty($json['success'])) {?>
        <div class="message">
            <?php echo $json['success']; ?>
        </div>
    <?php } ?>
    <?php if (!empty($json['error'])) {?>
        <div class="message">
            <?php echo $json['error']; ?>
        </div>
    <?php } ?>    
    
    <!-- <div class="working-header">
        <h2>Working Group Manager</h2>
        <p>Upload the Working Group HTML file used on the club website.</p>
    </div> -->
    <!--
    <div class="submenu">
      <a href="admin/workingManager.php">Working Group Manager</a>
      <div style="float:right;">
            <a style="float:left;" href="admin/dashboard.php">Dashboard</a>
            <a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
       </div>
    </div>
    -->
    <div class="working-form-wrap">
    <form enctype="multipart/form-data" name="articleForm" method="post" action="admin/workingManager.php">
      <table class="working-form-table">
          <col width="20%"><col width="80%">
          <tr>
              <th>File Upload</th>
              <td class="alignLeft">
                <input type="file" name="file" value="file">
              </td>
          </tr>
          <tr>
              <td colspan="2">
                  <input type="submit" name="submit" value="Upload" />
                  <a target="_blank" class="button" href="<?php echo $download_file; ?>">Download File</a>
                  <input type="reset" name="reset" value="Clear" onclick="location.href='admin/workingManager.php'" />
              </td>
          </tr>
      </table>
    </form>
    </div>
<?php                   
  $design->closeDiv();
  $design->writeLeftPanel();
  $design->closeDiv();
  $design->closeDiv();
  $design->endPage();
  //$design->pageClose();    
$design = NULL; // release object