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
        if (get_magic_quotes_gpc()) {
            function stripslashes_deep($value) {
                $value = is_array($value) ?   array_map('stripslashes_deep', $value) : stripslashes($value);
                return $value;
            }
            $_POST = array_map('stripslashes_deep', $_POST);
            $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
        }
        
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
$design->openDiv("infoWrapper");
$design->openDiv("leftArea");
?>
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
    
    <div class="submenu">
      <a href="admin/workingManager.php">Working Group Manager</a>
      <div style="float:right;">
            <a style="float:left;" href="admin/dashboard.php">Dashboard</a>
            <a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
       </div>
    </div>
    <br />
    <form enctype="multipart/form-data" name="articleForm" method="post" action="admin/workingManager.php">
      <table class="contentTable">
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
                  <a target="_blank" style="padding:3px 6px;-webkit-appearance: button;-moz-appearance: button;appearance: button;text-decoration: none;color: initial;" class="button" href="<?php echo $download_file; ?>">Download File</a>
                  <input type="reset" name="reset" value="Clear" onclick="location.href='admin/workingManager.php'" />
              </td>
          </tr>
      </table>
    </form>
<?php                   
  $design->closeDiv();
  //$design->rightArea();  
  //$design->closeDiv();
  $design->endPage();
  //$design->pageClose();    
$design = NULL; // release object
