<?php
include_once('../bootstrap.php');
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
	if ($_SESSION['sponsorofthedayManager'] == "Y") { // check login
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
		// all actions POST form submissions go here
		// echo '<pre>';
		// print_r($_REQUEST);
		// exit;
		//echo $q;exit;
		if ($q=="delete-sponsoroftheday") {
           	$sponsorID=getParameterNumber('id',0);                
           	$sponsorDetails = $rObj->getSponsorofthedayById($sponsorID); 
           	try {
               //unlink(DIR_SPONSOR_UPLOAD."/".$sponsorDetails);   
               $rObj->deleteSponsorofthedayByID($sponsorID);
           	} catch (Exception $err) {
               $msg = $err->getMessage();        
           	}
  		}

		if (isset($_REQUEST['submit'])) {
			//$body = getParameterString('file','',$db);
			if ($q=="save-data") {
				// echo '<pre>';
				// print_r($_POST);
				// exit;
	           	foreach($_POST['sponsor_datas'] as $bkey => $value){
	           		$id = getParameterString_custom($value['id'],'',$db);
	           		$title = getParameterString_custom($value['title'],'',$db);
		            $link = getParameterString_custom($value['link'],'',$db);
		            $sort_order = getParameterString_custom($value['sort_order'],'',$db);
	                try {
	                	$rObj->updateSponsoroftheday($id,$title,$link,$sort_order);
	                } catch (Exception $err) {
	                    echo $err->getMessage();
	                }
	            }
	            $json['success'] = 'Sponsor Of the Day Data Updated';      
	        }      		

			if(isset($_FILES['file']) && $q == 'image-upload'){
				date_default_timezone_set("Asia/Kolkata");
				$file_upload_path = DIR_SPONSOR_UPLOAD.'/';
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
					$file = 'Image_'.$timestamp.'.jpg';
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
					
					$sql = "INSERT INTO `sponsoroftheday` SET `title` = '".$file_title."', `link` = '', `source` = '".$file."', `sort_order` = '".$cnt."' ";
					
					$rObj->insertSponsorofthedayimage($sql); 
					$cnt ++;
				}
				$json['success'] = 'Sponsor Of the Day Images Uploaded';
			}
		}
		$sponsoroftheday_datas = $rObj->getsponsoroftheday_datas();
		$sponsoroftheday_datas_count = count($sponsoroftheday_datas) + 1;
		// echo '<pre>';
		// print_r($sponsor_datas);
		// exit;

	} else {
		$msg = "You do not have access to this page.";
	}  
} else {
	$secmsg = "Please login to access this page";
}
$pageTitle ='Sponsor Of the Day Manager';        
// create a template object
$design = new Design();  

$design->js='
<script type="text/javascript" src="lib/ckeditor/ckeditor.js"></script>
<script type="text/javascript">
    function confirmDelete(sponsorID) {
        if (confirm ("Are you sure ?")){
            location.href="admin/sponsorofthedayManager.php?q=delete-sponsoroftheday&id="+sponsorID;
        }
    }
</script>
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
	  	<a href="admin/sponsorofthedayManager.php">Sponsor Of the Day Manager</a>
	  	<div style="float:right;">
			<a style="float:left;" href="admin/dashboard.php">Dashboard</a>
			<a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
	   	</div>
	</div>
	<br />
	<form enctype="multipart/form-data" name="sponsorimageForm" method="post" action="admin/sponsorofthedayManager.php">
	  	<table class="contentTable">
		  	<col width="20%"><col width="80%">
		  	<tr>
			  	<th>File Upload</th>
			  	<td class="alignLeft">
					<input type="file" name="file[]" value="file" multiple />
			  	</td>
		  	</tr>
		  	<tr>
			  	<td colspan="2">
				  	<input type="submit" name="submit" value="Upload" />
				  	<input type="hidden" name="q" value="image-upload" />
				  	<input type="hidden" name="count" value="<?php echo $sponsoroftheday_datas_count; ?>" />
				  	<input type="reset" name="reset" value="Clear" onclick="location.href='admin/sponsorofthedayManager.php'" />
			  	</td>
		  	</tr>
	  	</table>
	</form>
	<form enctype="multipart/form-data" name="sponsorForm" method="post" action="admin/sponsorofthedayManager.php">
	  	<table class="contentTable" style="width: 80%;">
		  	<tr>
			  	<th style="width: 20%;">Image Title</th>
			  	<th style="width: 20%;">Link Href</th>
			  	<th style="width: 25%;">Image</th>
			  	<th style="width: 20%;">Sort Order</th>
			  	<th style="width: 15%;">Action</th>
			</tr>
			<?php foreach($sponsoroftheday_datas as $bkey => $bvalue){ ?>
			  	<tr>
					<td>
						<input style="width: 90%;" type="text" name="sponsor_datas[<?php echo $bkey ?>][title]" value="<?php echo $bvalue['title']; ?>" />
				  	</td>
				  	<td>
						<input style="width: 90%;" type="text" name="sponsor_datas[<?php echo $bkey ?>][link]" value="<?php echo $bvalue['link']; ?>" />
				  	</td>
				  	<td>
				  		<img src="<?php echo HTTP_SPONSOR_UPLOAD.'/'.$bvalue['source']; ?>" style="width: 100px;height: 100px;" />
						<input type="hidden" name="sponsor_datas[<?php echo $bkey ?>][source]" value="<?php echo $bvalue['source']; ?>" />
						<input type="hidden" name="sponsor_datas[<?php echo $bkey ?>][id]" value="<?php echo $bvalue['id']; ?>" />
				  	</td>
				  	<td>
						<input style="width: 90%;" type="text" name="sponsor_datas[<?php echo $bkey ?>][sort_order]" value="<?php echo $bvalue['sort_order']; ?>" />
				  	</td>
				  	<td>
						<a style="cursor:pointer;" onclick="javascript: confirmDelete(<?php echo $bvalue['id'];?>);">Delete</a>
				  	</td>
			  	</tr>
			<?php } ?>
			<tr>
			  	<td colspan="5">
				  	<input type="submit" name="submit" value="submit" />
				  	<input type="hidden" name="q" value="save-data" />
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