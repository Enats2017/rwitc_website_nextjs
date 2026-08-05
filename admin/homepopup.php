<?php
include_once('../bootstrap.php');
// echo 'after';exit;
include_once('../lib/pagination.class.php');
require_once("../lib/users.class.php");
require_once("../lib/userchecks.php");
require_once("../lib/race.class.php");
$q = getParameterString('q','',$db);

session_start();                    
$uid = $_SESSION['uid'];             
$userObj = new Users($db);  
$msg = $secmsg = "";
$rObj = new Racedata($db);
if (isAdminlogin()) {
	if ($_SESSION['homepopup'] == "Y") {
		//if (get_magic_quotes_gpc()) {
			function stripslashes_deep($value) {
				$value = is_array($value) ?   array_map('stripslashes_deep', $value) : stripslashes($value);
				return $value;
			}
			$_POST = array_map('stripslashes_deep', $_POST);
			$_REQUEST = array_map('stripslashes_deep', $_REQUEST);
		//}
		$json = array();
		$json['success'] = '';
		$json['error'] = '';
		if (isset($_REQUEST['submit'])) {
		  if ($q=="save-data") {
			$feed = getParameterString_custom($_POST['feed'],'',$db);
			$videourl = getParameterString_custom($_POST['videourl'],'',$db);
			$status = getParameterString_custom($_POST['statustype'],'',$db);
		    try {
			    $rObj->updatehomepopup($feed,$videourl,$status);
			} catch (Exception $err) {
				echo $err->getMessage();
			}
				$json['success'] = 'Sponsor Data Updated';      
			}  
			if(isset($_FILES['file']) && $q == 'image-upload'){
				date_default_timezone_set("Asia/Kolkata");
				$file_upload_path = DIR_HOMEPOPUP_UPLOAD.'/';
				$files_arr = array();
				$image_name = array();
				foreach ($_FILES['file']['name'] as $key => $value) {
					$files_arr[$key]['name'] = $value;
					$image_name[] = $value;
				}
				foreach ($_FILES['file']['type'] as $key => $value) {
					$files_arr[$key]['type'] = $value;
				}
				foreach ($_FILES['file']['tmp_name'] as $key => $value) {
					$files_arr[$key]['tmp_name'] = $value;
				}
				foreach ($_FILES['file']['error'] as $key => $value) {
					$files_arr[$key]['error'] = $value;
				}
				foreach ($_FILES['file']['size'] as $key => $value) {
					$files_arr[$key]['size'] = $value;
				}
				$cnt = $_POST['count'];
				  //$image_name_string = '';
				foreach ($files_arr as $fkey => $fvalue) {
					$timestamp = date('YmdHis').rand(10, 10000);
					$file = $fvalue['name'];
					$file_titles = explode('.', $fvalue['name']);
				  	$file_title = $file_titles[0];
					if($file != ''){
						$exist_path = $file_upload_path.$file;
						if(file_exists($exist_path)){
						  unlink($exist_path);
						}
					}
				  	if (move_uploaded_file($fvalue['tmp_name'], $file_upload_path . $file)) {
						$destFile = $file_upload_path . $file;
						chmod($destFile, 0777);
				  	}
				 	$sql = "UPDATE `home_popup` SET  `image_src` = '".$file."' WHERE `id` = '6'  ";
				  	$rObj->inserthomepopup($sql); 
				  	$cnt ++;
				}
				$json['success'] = ' Images Uploaded';
			}
		}
	} else {
		$msg = "You do not have access to this page.";
	}  
} else {
	$secmsg = "Please login to access this page";
}
$design = new Design();  
$design->css ='
<style type="text/css">
  #title { color: #000000; font-size: 14px; margin: 10px; margin: auto; text-align: left; display:block; }
</style>
';
$pageTitle ='Home Popup';
$design->jqueryJs = ""; 
$design->startPage("$pageTitle");  
$design->writeLogoTickerMenu();
$design->openDiv("contentWrapper");
$design->openDiv("infoWrapper");
$design->openDiv("leftArea");
?>

<?php if (empty($msg) && empty($secmsg)) { ?>
<div style="float:right;">
    <a style="float:left;" href="admin/dashboard.php">Dashboard</a>
    <a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
</div>
<form enctype="multipart/form-data" name="imageForm" method="post" action="admin/homepopup.php">
	<table class="contentTable">
		<tr>
			<th class="thwhite" colspan="2"><h2>Home Page Popup</h2></th>
		</tr>
		<!-- <col width="20%"><col width="80%"> -->
		<tr>
		  <th>File Upload</th>
		  <td class="alignLeft">
			  <input type="file" name="file[]" value="file" multiple />
			  <input type="submit" name="submit" value="Upload" />
			  <input type="submit" name="submit" value="Clear" />
			  <input type="hidden" name="q" value="image-upload" />
		  </td>
		</tr>
	</table>
</form>
<form enctype="multipart/form-data" name="homepopupForm" method="post" action="admin/homepopup.php">
	<table class="contentTable">
	
		<tr>
			<th>Feed</th>
			<td class="alignLeft"><input type="text" name="feed" value="" /></td>
		</tr>
		<tr>
			<th>Video Url</th>
			<td class="alignLeft"><input type="text" name="videourl" value="" /></td>
		</tr>
		<tr>
			<th>Select Status</th>
			<td class="alignLeft">
				<input type="radio" name="statustype" value='0' checked="checked" />Inactive &nbsp;
				<input type="radio" name="statustype" value='1' />Active &nbsp;
			  
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<input type="submit" name="submit" value="Send" />
				<input type="hidden" name="q" value="save-data" />              
			</td>
		</tr>
	</table>
</form>
<?php }?>
<?php                   
$design->closeDiv();
  //$design->rightArea();  
  //$design->closeDiv();
$design->endPage();
  //$design->pageClose();    
$design = NULL; // release object