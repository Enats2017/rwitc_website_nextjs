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
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
$design->openDiv("infoWrapper","col-lg-12");
$design->openDiv("leftArea","col-lg-9");
//echo $msg;
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

.gallery-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.gallery-header-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.gallery-btn { display: inline-flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #1a7a45; color: #0f5c33; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; white-space: nowrap; cursor: pointer; }
.gallery-btn:hover { background: #e6f4ec; }
.gallery-btn.solid { background: #0f5c33; color: #fff; border-color: #0f5c33; }
.gallery-btn.solid:hover { background: #0b3d24; }

.section-title { font-size: 16px; font-weight: 700; color: #0f5c33; margin: 4px 0 16px; display: flex; align-items: center; gap: 8px; }

/* ===== Add Bulk Image form — normal inline screen section, not a popup ===== */
.gallery-form-wrap {
    background: #fff;
    border: 1px solid #e2e6e4;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}
.form-section-title { margin: 0 0 16px; font-size: 17px; color: #0f5c33; font-weight: 700; }

/* form styling reused inside the form-wrap */
.gallery-form-table { width: 100%; border-collapse: collapse; }
.gallery-form-table th { text-align: left; padding: 10px 8px; color: #2b332f; vertical-align: top; width: 20%; font-weight: 600; font-size: 13.5px; }
.gallery-form-table td { padding: 10px 8px; }
.gallery-form-table input[type="text"],
.gallery-form-table input[type="file"] {
    border: 1px solid #e2e6e4; border-radius: 6px; padding: 8px 10px; font-size: 14px;
    width: 100%; max-width: 100%; box-sizing: border-box;
}
.gallery-form-table input[type="submit"],
.gallery-form-table input[type="reset"] { background: #0f5c33; color: #fff; border: none; padding: 9px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; margin-right: 8px; margin-top: 6px; }
.gallery-form-table input[type="reset"] { background: #fff; color: #2b332f; border: 1px solid #e2e6e4; }

/* ===== bulk caption-edit grid ===== */
.bulk-images-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 18px; margin-bottom: 20px; }
.bulk-image-card { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.03); display: flex; flex-direction: column; }
.bulk-image-card .thumb-wrap { width: 100%; aspect-ratio: 3 / 2; background: #f5f4ee; overflow: hidden; }
.bulk-image-card .thumb-wrap img { width: 100%; height: 100%; object-fit: cover; display: block; }
.bulk-image-card .bulk-image-body { padding: 12px 14px; display: flex; flex-direction: column; gap: 10px; flex: 1; }
.bulk-image-card input[type="text"] {
    width: 100%; box-sizing: border-box; border: 1px solid #e2e6e4; border-radius: 6px; padding: 8px 10px; font-size: 13.5px;
}
.bulk-image-card .bulk-image-actions { display: flex; justify-content: flex-end; border-top: 1px solid #eef0ee; padding-top: 10px; margin-top: auto; }
.bulk-image-card .bulk-image-actions a { font-size: 13px; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 6px; cursor: pointer; color: #c0392b; }
.bulk-empty { grid-column: 1 / -1; text-align: center; padding: 30px 20px; color: #7a8c84; font-size: 14.5px; background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; }
.bulk-update-bar { display: flex; justify-content: flex-end; }
.bulk-update-bar input[type="submit"] { background: #0f5c33; color: #fff; border: none; padding: 10px 22px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; }
.bulk-update-bar input[type="submit"]:hover { background: #0b3d24; }

/* ===== responsive ===== */
@media (max-width: 900px) {
    #infoWrapper.col-lg-12 { flex-direction: column; margin: 16px auto; }
    #leftArea.col-lg-9 { flex: 1 1 100%; max-width: 100%; padding: 28px 24px; }
}
@media (max-width: 700px) {
    #leftArea.col-lg-9 { padding: 0 16px; }
    .gallery-header { flex-direction: column; align-items: stretch; }
    .gallery-header-actions { flex-direction: column; }
    .bulk-images-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; }
}
@media (max-width: 520px) {
    .gallery-form-table, .gallery-form-table tbody, .gallery-form-table tr, .gallery-form-table th, .gallery-form-table td {
        display: block; width: 100% !important;
    }
    .gallery-form-table th { padding-bottom: 2px; }
    .gallery-form-table td { padding-top: 0; padding-bottom: 14px; }
    .bulk-images-grid { grid-template-columns: 1fr; }
}
</style>

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

	<div class="gallery-header">
		<div class="gallery-header-actions">
			<a class="gallery-btn" href="admin/galleryManager.php?q=new-image"><i class="fas fa-image"></i> Add New Image</a>
			<a class="gallery-btn solid" href="admin/galleryManagerbulk.php?q=new-image"><i class="fas fa-layer-group"></i> Add Bulk Image</a>
		</div>
		<!--
		<div style="float:right;">
			<a style="float:left;" href="admin/dashboard.php">Dashboard</a>
			<a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
		</div>
		-->
	</div>

	<?php if ($q=="new-image") { ?>
	<div class="gallery-form-wrap">
	    <h3 class="form-section-title">Add Bulk Image</h3>
		<form name="dividendForm" method="post" action="admin/galleryManagerbulk.php" enctype="multipart/form-data">
			<table class="gallery-form-table">
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
	</div>
	<?php } ?>
								
	<?php if ($q=="view-images") { ?>
		<div class="section-title"><i class="fas fa-images"></i> Images for <?php echo date("d-M-Y",strtotime($date)); ?></div>
		<form name="dividendForm" method="post" action="admin/galleryManagerbulk.php" enctype="multipart/form-data">
			<div class="bulk-images-grid">
				<?php 
					$i=0;
					if (count($raceDayImages) > 0) {
					foreach ($raceDayImages as $raceDayImage) {
						$caption = $raceDayImage['caption'];
						$idss = $raceDayImage['id'];
						$dirname = date("d-M-Y",strtotime($raceDayImage['racedate']));
						echo '<div class="bulk-image-card">';
						echo '<div class="thumb-wrap">';
						if ($sponsorID == 1) { 
							echo '<img src="'.GALLERY_BASE.'/'.$dirname.'/'.$raceDayImage['filename'].'" alt="" />';
						}
						echo '</div>';
						echo '<div class="bulk-image-body">';
						echo '<input type="text" name="sponsor_datas['.$i.'][caption]" value="'.$caption.'" placeholder="Caption" />';
						echo '<input type="hidden" name="sponsor_datas['.$i.'][image_id]" value="'.$idss.'" />';
						echo '<input type="hidden" name="sponsor_datas['.$i.'][date]" value="'.$date.'" />';
						echo '<input type="hidden" name="date" value="'.$date.'" />';
						echo '<div class="bulk-image-actions">';
						echo '<a onclick="return confirmDelete('.trim($raceDayImage["id"]).', 1)"><i class="fas fa-trash-alt"></i> Delete</a>';
						echo '</div>';
						echo '</div>';
						echo '</div>';
						$i++;
					}
					} else {
						echo '<div class="bulk-empty">No images uploaded for this date yet.</div>';
					}
				?>
			</div>
			<?php if (count($raceDayImages) > 0) { ?>
			<div class="bulk-update-bar">
				<input type="submit" name="submit" value="Update Data" />
				<input type="hidden" name="q" value="Update" />
			</div>
			<?php } ?>
		</form>
	<?php } ?>
<?php } ?>
<?php                   
	$design->closeDiv();
	$design->writeLeftPanel();
	$design->closeDiv();
	$design->closeDiv();
	$design->endPage();
	$design->pageClose();
	$design = NULL; // release object
?>