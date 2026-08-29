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
$design->js='
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
';
$pageTitle ='Home Popup';
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

.hp-title { font-size: 18px; font-weight: 700; color: #2b332f; margin: 0 0 18px; }

.hp-form-wrap {
    background: #fff;
    border: 1px solid #e2e6e4;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}
.hp-form-table { width: 100%; border-collapse: collapse; }
.hp-form-table th { text-align: left; padding: 10px 8px; color: #2b332f; vertical-align: top; width: 20%; font-weight: 600; font-size: 13.5px; }
.hp-form-table td { padding: 10px 8px; }
.hp-form-table input[type="text"],
.hp-form-table input[type="file"] {
    border: 1px solid #e2e6e4; border-radius: 6px; padding: 8px 10px; font-size: 14px;
    width: 100%; max-width: 100%; box-sizing: border-box;
}
.hp-form-table input[type="submit"] { background: #0f5c33; color: #fff; border: none; padding: 9px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; margin-right: 8px; }
.hp-form-table input[type="submit"]:hover { background: #0b3d24; }

.hp-radio-group { display: flex; align-items: center; gap: 22px; }
.hp-radio-group label { display: inline-flex; align-items: center; gap: 6px; font-size: 14px; color: #2b332f; cursor: pointer; }
.hp-radio-group input[type="radio"] { accent-color: #0f5c33; width: 16px; height: 16px; }

/* ===== responsive ===== */
@media (max-width: 900px) {
    #infoWrapper.col-lg-12 { flex-direction: column; margin: 16px auto; }
    #leftArea.col-lg-9 { flex: 1 1 100%; max-width: 100%; padding: 28px 24px; }
}
@media (max-width: 700px) {
    #leftArea.col-lg-9 { padding: 0 16px; }
}
@media (max-width: 520px) {
    .hp-form-table, .hp-form-table tbody, .hp-form-table tr, .hp-form-table th, .hp-form-table td {
        display: block; width: 100% !important;
    }
    .hp-form-table th { padding-bottom: 2px; }
    .hp-form-table td { padding-top: 0; padding-bottom: 14px; }
    .hp-radio-group { flex-wrap: wrap; gap: 14px; }
}
</style>

<?php if (empty($msg) && empty($secmsg)) { ?>
<!--
<div style="float:right;">
    <a style="float:left;" href="admin/dashboard.php">Dashboard</a>
    <a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
</div>
-->
<div class="hp-title">Home Page Popup</div>

<div class="hp-form-wrap">
<form enctype="multipart/form-data" name="imageForm" method="post" action="admin/homepopup.php">
	<table class="hp-form-table">
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
</div>

<div class="hp-form-wrap">
<form enctype="multipart/form-data" name="homepopupForm" method="post" action="admin/homepopup.php">
	<table class="hp-form-table">
	
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
				<div class="hp-radio-group">
				<label><input type="radio" name="statustype" value='0' checked="checked" /> Inactive</label>
				<label><input type="radio" name="statustype" value='1' /> Active</label>
				</div>
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
</div>
<?php }?>
<?php                   
$design->closeDiv();
  $design->writeLeftPanel();
  $design->closeDiv();
  $design->closeDiv();
$design->endPage();
  //$design->pageClose();    
$design = NULL; // release object