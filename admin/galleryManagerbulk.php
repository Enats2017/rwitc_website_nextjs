<?php
include_once('../bootstrap.php');
require_once('../lib/gallery.class.php');
require_once("../lib/users.class.php");
require_once("../lib/userchecks.php");
$q = getParameterString('q','',$db);  
session_start();                    
if(isset($_COOKIE['uid'])){                    
	$uid = $_COOKIE['uid'];    
} else {
	$uid = 0;
}             
$userObj = new Users($db);
$date = date('Y-m-d');
if (isAdminlogin()) {
	if ($_SESSION['gallery'] == "Y") { // check login      
		$images = new Image($db);      
		// all actions POST form submissions go here
		// echo '<pre>';
		// print_r($_REQUEST);
		// echo '<pre>';
		// print_r($_POST);
		// echo '<br/ >';
		// echo $q;
		// echo '<br/ >';
		//exit;

		if (isset($_REQUEST['submit'])) {
			// echo 'in request submit if';
			// echo '<br />';
			//exit;
			$date = getParameterString('date','',$db);
			$captions = '';
			$sponsorID = 1;
			if ($q == "add-image") {
				try {
					$dirname = date("d-M-Y",strtotime($date));
					//echo $dirname;exit;
					if (!file_exists(GALLERY_BASE."/".$dirname)) {
						@mkdir($base.GALLERY_BASE."/".$dirname,0777);
					}
					$file_upload_path = $base.GALLERY_BASE."/".$dirname."/";
					$file_upload_path_sponsor = $base.SPONSOR_GALLERY_BASE."/".$sponsorID."/";
					$files_arr = array();
					$image_name = array();
					foreach ($_FILES['imageFile']['name'] as $key => $value) {
						$files_arr[$key]['name'] = $value;
						$image_name[] = $value;
					}
					foreach ($_FILES['imageFile']['type'] as $key => $value) {
						$files_arr[$key]['type'] = $value;
					}
					foreach ($_FILES['imageFile']['tmp_name'] as $key => $value) {
						$files_arr[$key]['tmp_name'] = $value;
					}
					foreach ($_FILES['imageFile']['error'] as $key => $value) {
						$files_arr[$key]['error'] = $value;
					}
					foreach ($_FILES['imageFile']['size'] as $key => $value) {
						$files_arr[$key]['size'] = $value;
					}
					foreach ($files_arr as $fkey => $fvalue) {
						$timestamp = date('YmdHis').rand(10, 10000);
						$file = $fvalue['name'];
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
						$images->insertImage($date,$captions,$file,1); // 1- sponsorID which means none 
					}
					$q = "view-images";
					$raceDayImages = $images->getAllImagesByDateAndSponsorID($date,1);
				} catch (Exception $err) {
					$msg = $err->getMessage();
				}
			}

			if ($q == "Update") {
				// echo 'in q update if';
				// echo '<br />';
				try {
					// echo '<pre>';
					// print_r($_POST);
					// exit;
					foreach ($_POST['sponsor_datas'] as $skey => $svalue) {
						$caption = $svalue['caption'];
						$image_id = $svalue['image_id'];
						$images->updateImage($caption,$image_id); // 1- sponsorID which means none 
					}
					$q = "view-images";
					$raceDayImages = $images->getAllImagesByDateAndSponsorID($date,1);
				} catch (Exception $err) {
					$msg = $err->getMessage();
				}
			}
		}
		//echo 'out';exit;

		if ($q=="delete-image") {
			$imageID=getParameterNumber('id',0);
			$sponsorID = 1;
			try {  
				$imageDetails = $images->getImageById($imageID);
				$date = $imageDetails['racedate'];
				$dirname = date("d-M-Y",strtotime($imageDetails['racedate']));   
				if ( unlink($base.GALLERY_BASE."/$dirname/".$imageDetails['filename']) ) {
					$images->deleteImageByID($imageID);
					$msg = 'Image Delete successfully';
				} else {
					$msg = 'Could Not Delete Image. Please try again';
				} 
				$q = "view-images";
				$raceDayImages = $images->getAllImagesByDateAndSponsorID($date,1);
			} catch (Exception $err) {
				$msg = $err->getMessage();
				$msg .= $err->getTraceAsString();        
			}
		}
	} else {
		$msg = "You do not have access to this page.";
	}  
} else {
	$secmsg = "Please login to access this page";
}
?>
<?php 
	$pageTitle ='Gallery Manager';        
	$design = new Design();
	$design->js='
	<script type="text/javascript" src="js/jquery.ui.core.min.js"></script>    
		<script type="text/javascript" src="js/jquery.ui.datepicker.min.js"></script>
		<script type="text/javascript">
				function confirmDelete(imageID,sponsorID) {
					//alert(sponsorID);        
					if (confirm ("Are you sure ?")){
							location.href="admin/galleryManagerbulk.php?q=delete-image&id="+imageID+"&sponsorID="+sponsorID;
					}
				}
		</script>
	';
	$design->css ='
		<link type="text/css" href="css/jquery.ui.all.css" rel="stylesheet" />    
	';
	$design->jqueryJs = "
		jQuery.browser = {};
		(function () {
			jQuery.browser.msie = false;
			jQuery.browser.version = 0;
			if (navigator.userAgent.match(/MSIE ([0-9]+)\./)) {
				jQuery.browser.msie = true;
				jQuery.browser.version = RegExp.$1;
			}
		})();
		$('#image_date').datepicker({
			showOn: 'button',
			buttonImage: 'images/calendar.gif',
			buttonImageOnly: true,
			dateFormat : 'yy-mm-dd'
		});
		$('.sponsorList').click(function() {  
			if ($('#image_date').val()) {
				$.ajax( {
					url : 'admin/galleryManagerbulk.php?q=fetch-sponsor&date='+$('#image_date').val(),
					type: 'GET',
					success: function (msg) {
						//alert(msg);
						$('#sponsors_list').html(msg);
					}
				});
			} else {
				alert ('Please select a date');
			}
		});
	"; 
$design->startPage("$pageTitle");

$design->writeLogoTickerMenu();
$design->openDiv("contentWrapper");
$design->openDiv("infoWrapper");
$design->openDiv("leftArea");
//echo $msg;
?>  
<?php if (!empty($msg)) {?>
	<div class="message">
		<?php echo $msg; ?>
	</div>
<?php } ?>
<?php if (!empty($secmsg)) {?>
	<div class="message">
		<?php echo $secmsg; ?>
	</div>
<?php } ?>    
<?php if ($_SESSION['gallery'] == "Y") { ?>
	<div class="submenu">  
		<a href="admin/galleryManager.php?q=new-image">Add New Image</a>
		<a href="admin/galleryManagerbulk.php?q=new-image">Add Bulk Image</a>
		<div style="float:right;">
			<a style="float:left;" href="admin/dashboard.php">Dashboard</a>
			<a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
		</div>
	</div>   
	<br />   
	<?php if ($q=="new-image") { ?>              
		<form name="dividendForm" method="post" action="admin/galleryManagerbulk.php" enctype="multipart/form-data">
			<table class="contentTable">
				<col width="20%"><col width="80%">
				<tr>
					<th>Date</th>
					<td class="alignLeft">
						<input type="text" name="date" id='image_date' />
					</td>
				</tr>
				<tr>         
					<th>Upload Image File</th>
					<td class="alignLeft"><input type="file" name="imageFile[]" multiple /></td>
				</tr>                    
				<tr>
					<td colspan="2">
						<input type="submit" name="submit" value="Save" />
						<input type="reset" name="reset" value="Clear" />
						<input type="hidden" name="q" value="add-image" />
						<input type="hidden" name="r" value="view-image" />
					</td>
				</tr>
			</table>
		</form>
	<?php } ?>
									
	<?php if ($q=="view-images") { ?>
		<form name="dividendForm" method="post" action="admin/galleryManagerbulk.php" enctype="multipart/form-data">
			<table class="contentTable">
				<tr>
					<th class='thwhite' colspan="3">Images for <?php echo date("d-M-Y",strtotime($date)); ?></th>
				</tr>
				<tr>
					<th>Caption</th>
					<th>Image</th>
					<th>Action<th>
				</tr>                    
				<?php 
					$i=0;
					foreach ($raceDayImages as $raceDayImage) {
						echo '<tr>';
							$caption = $raceDayImage['caption'];
							$idss = $raceDayImage['id'];
							echo '<td><input type ="text" name="sponsor_datas['.$i.'][caption]" value ="'.$caption.'"/><input type ="hidden" name = "sponsor_datas['.$i.'][image_id]" value ="'.$idss.'" /><input type ="hidden" name = "sponsor_datas['.$i.'][date]" value ="'.$date.'" /><input type ="hidden" name = "date" value ="'.$date.'" /></td>';
							
							$dirname = date("d-M-Y",strtotime($raceDayImage['racedate']));
							if ($sponsorID == 1) { 
								echo '<td><img src="'.GALLERY_BASE.'/'.$dirname.'/'.$raceDayImage['filename'].'" width="300" height="200" /></td>';
							}
							echo '<td>
								<a style="cursor:pointer;" onclick="return confirmDelete('.trim($raceDayImage["id"]).', 1)">Delete</a>
							</td>';
						echo '</tr>';
						$i++;
					}
					echo '<tr>
						<td colspan="3">
							<input type="submit" name="submit" value="Update Data" />
							<input type="hidden" name="q" value="Update" />
						</td>
					</td>';
				?>
			</table>
		</form>
	<?php } ?>
<?php } ?>
<?php                   
	$design->closeDiv();
	//$design->rightArea();  
	//$design->closeDiv();
	$design->closeDiv();
	$design->pageClose();
	$design = NULL; // release object
?>