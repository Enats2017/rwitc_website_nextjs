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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
$design->jqueryJs = "
    $('#sponsorSearchInput').on('keyup', function() {
        var term = $(this).val().toLowerCase().trim();
        var visibleCount = 0;
        $('.sponsor-card').each(function() {
            var titleVal = $(this).find('input[name*=\"[title]\"]').val() || '';
            var sortVal = $(this).find('input[name*=\"[sort_order]\"]').val() || '';
            var match = titleVal.toLowerCase().indexOf(term) !== -1 || sortVal.toLowerCase().indexOf(term) !== -1;
            $(this).toggle(match);
            if (match) { visibleCount++; }
        });
        $('#sponsorNoResults').toggle(term.length > 0 && visibleCount === 0);
    });
"; 
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

.sponsor-header { margin-bottom: 20px; }
.sponsor-header h2 { margin: 0; font-size: 22px; color: #2b332f; font-weight: 700; }
.sponsor-header p { margin: 4px 0 0; font-size: 13.5px; color: #7a8c84; }

.section-title { font-size: 16px; font-weight: 700; color: #0f5c33; margin: 28px 0 14px; display: flex; align-items: center; gap: 8px; }

/* ===== search bar ===== */
.sponsor-search-wrap {
    position: relative;
    margin-bottom: 16px;
}
.sponsor-search-wrap i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #7a8c84;
    font-size: 13.5px;
}
.sponsor-search-wrap input[type="text"] {
    width: 100%;
    box-sizing: border-box;
    padding: 11px 14px 11px 38px;
    border: 1px solid #e2e6e4;
    border-radius: 8px;
    font-size: 14px;
    background: #fff;
}
.sponsor-search-wrap input[type="text"]:focus {
    outline: none;
    border-color: #1a7a45;
    box-shadow: 0 0 0 3px rgba(26,122,69,0.12);
}

/* ===== upload form — normal inline screen card ===== */
.sponsor-upload-wrap {
    background: #fff;
    border: 1px solid #e2e6e4;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}
.sponsor-upload-table { width: 100%; border-collapse: collapse; }
.sponsor-upload-table th { text-align: left; padding: 10px 8px; color: #2b332f; vertical-align: top; width: 20%; font-weight: 600; font-size: 13.5px; }
.sponsor-upload-table td { padding: 10px 8px; }
.sponsor-upload-table input[type="file"] {
    border: 1px solid #e2e6e4; border-radius: 6px; padding: 8px 10px; font-size: 14px;
    width: 100%; max-width: 100%; box-sizing: border-box; background: #f7faf8;
}
.sponsor-upload-table input[type="submit"] { background: #0f5c33; color: #fff; border: none; padding: 9px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; margin-right: 8px; }
.sponsor-upload-table input[type="submit"]:hover { background: #0b3d24; }
.sponsor-upload-table input[type="reset"] { background: #fff; color: #2b332f; border: 1px solid #e2e6e4; padding: 9px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; }
.sponsor-upload-table input[type="reset"]:hover { background: #f5f4ee; }

/* ===== sponsor editor card grid ===== */
.sponsor-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 18px; margin-bottom: 20px; }
.sponsor-card { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.03); display: flex; flex-direction: column; }
.sponsor-card .thumb-wrap { width: 100%; aspect-ratio: 1 / 1; background: #f5f4ee; overflow: hidden; }
.sponsor-card .thumb-wrap img { width: 100%; height: 100%; object-fit: cover; display: block; }
.sponsor-card .sponsor-card-body { padding: 12px 14px; display: flex; flex-direction: column; gap: 10px; flex: 1; }
.sponsor-card label { font-size: 12px; font-weight: 700; color: #7a8c84; text-transform: uppercase; letter-spacing: .3px; margin-bottom: -4px; }
.sponsor-card input[type="text"] {
    width: 100%; box-sizing: border-box; border: 1px solid #e2e6e4; border-radius: 6px; padding: 8px 10px; font-size: 13.5px;
}
.sponsor-card .sponsor-card-actions { display: flex; justify-content: flex-end; border-top: 1px solid #eef0ee; padding-top: 10px; margin-top: auto; }
.sponsor-card .sponsor-card-actions a { font-size: 13px; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 6px; cursor: pointer; color: #c0392b; }
.sponsor-empty { grid-column: 1 / -1; text-align: center; padding: 30px 20px; color: #7a8c84; font-size: 14.5px; background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; }
.sponsor-save-bar { display: flex; justify-content: flex-end; }
.sponsor-save-bar input[type="submit"] { background: #0f5c33; color: #fff; border: none; padding: 10px 22px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; }
.sponsor-save-bar input[type="submit"]:hover { background: #0b3d24; }

/* ===== responsive ===== */
@media (max-width: 900px) {
    #infoWrapper.col-lg-12 { flex-direction: column; margin: 16px auto; }
    #leftArea.col-lg-9 { flex: 1 1 100%; max-width: 100%; padding: 28px 24px; }
}
@media (max-width: 700px) {
    #leftArea.col-lg-9 { padding: 0 16px; }
    .sponsor-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; }
}
@media (max-width: 520px) {
    .sponsor-upload-table, .sponsor-upload-table tbody, .sponsor-upload-table tr, .sponsor-upload-table th, .sponsor-upload-table td {
        display: block; width: 100% !important;
    }
    .sponsor-upload-table th { padding-bottom: 2px; }
    .sponsor-upload-table td { padding-top: 0; padding-bottom: 14px; }
    .sponsor-grid { grid-template-columns: 1fr; }
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

	<div class="sponsor-header">
		<h2>Sponsor Of the Day Manager</h2>
		<p>Upload sponsor-of-the-day logos and manage their title, link and sort order.</p>
	</div>
	<!--
	<div class="submenu">
	  	<a href="admin/sponsorofthedayManager.php">Sponsor Of the Day Manager</a>
	  	<div style="float:right;">
			<a style="float:left;" href="admin/dashboard.php">Dashboard</a>
			<a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
	   	</div>
	</div>
	-->

	<div class="sponsor-upload-wrap">
	<form enctype="multipart/form-data" name="sponsorimageForm" method="post" action="admin/sponsorofthedayManager.php">
	  	<table class="sponsor-upload-table">
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
	</div>

	<div class="section-title"><i class="fas fa-star"></i> Sponsor Of the Day Images</div>

	<div class="sponsor-search-wrap">
		<i class="fas fa-magnifying-glass"></i>
		<input type="text" id="sponsorSearchInput" placeholder="Search by name or sort order..." />
	</div>

	<form enctype="multipart/form-data" name="sponsorForm" method="post" action="admin/sponsorofthedayManager.php">
	  	<div id="sponsorNoResults" class="sponsor-empty" style="display:none;">No sponsors match your search.</div>
	  	<div class="sponsor-grid">

			<?php if (count($sponsoroftheday_datas) > 0) { ?>
			<?php foreach($sponsoroftheday_datas as $bkey => $bvalue){ ?>

			  	<div class="sponsor-card">
			  		<div class="thumb-wrap">
			  			<img src="<?php echo HTTP_SPONSOR_UPLOAD.'/'.$bvalue['source']; ?>" alt="" />
			  		</div>
			  		<div class="sponsor-card-body">
			  			<label>Image Title</label>
						<input type="text" name="sponsor_datas[<?php echo $bkey ?>][title]" value="<?php echo $bvalue['title']; ?>" />

			  			<label>Link Href</label>
						<input type="text" name="sponsor_datas[<?php echo $bkey ?>][link]" value="<?php echo $bvalue['link']; ?>" />

			  			<label>Sort Order</label>
						<input type="text" name="sponsor_datas[<?php echo $bkey ?>][sort_order]" value="<?php echo $bvalue['sort_order']; ?>" />

						<input type="hidden" name="sponsor_datas[<?php echo $bkey ?>][source]" value="<?php echo $bvalue['source']; ?>" />

						<input type="hidden" name="sponsor_datas[<?php echo $bkey ?>][id]" value="<?php echo $bvalue['id']; ?>" />

						<div class="sponsor-card-actions">
							<a onclick="javascript: confirmDelete(<?php echo $bvalue['id'];?>);"><i class="fas fa-trash-alt"></i> Delete</a>
						</div>
			  		</div>
			  	</div>

			<?php } ?>
			<?php } else { ?>
				<div class="sponsor-empty">No sponsor of the day images uploaded yet.</div>
			<?php } ?>

	  	</div>

		<?php if (count($sponsoroftheday_datas) > 0) { ?>
		<div class="sponsor-save-bar">
			<input type="submit" name="submit" value="Save Changes" />
			<input type="hidden" name="q" value="save-data" />
		</div>
		<?php } ?>

	</form>
<?php                   
  $design->closeDiv();
  $design->writeLeftPanel();
  $design->closeDiv();
  $design->closeDiv();
  $design->endPage();
  //$design->pageClose();    
$design = NULL; // release object