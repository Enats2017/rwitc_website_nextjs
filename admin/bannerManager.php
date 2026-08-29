<?php
//error_reporting(E_ALL);
//ini_set("display_errors", 1);

include_once('../bootstrap.php');

include_once('../lib/pagination.class.php');

require_once("../lib/users.class.php");

require_once("../lib/userchecks.php");

require_once("../lib/function_race_report.php");

require_once("../lib/race.class.php");

$q = getParameterString('q','',$db);

session_start();                    

$uid = $_SESSION['uid'];             

$userObj = new Users($db);  

$msg = $secmsg = "";

$rObj = new Racedata($db);

if (isAdminlogin()) {

	if ($_SESSION['bannerManager'] == "Y") { // check login

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

		if ($q=="delete-banner") {

           	$bannerID=getParameterNumber('id',0);                

           	$bannerDetails = $rObj->getBannerById($bannerID); 

           	try {

               unlink(DIR_BANNER_UPLOAD."/".$bannerDetails);   

               $rObj->deleteBannerByID($bannerID);

           	} catch (Exception $err) {

               $msg = $err->getMessage();        

           	}

  		}



		if (isset($_REQUEST['submit'])) {

			//$body = getParameterString('file','',$db);

			if ($q=="save-data") {

	           	foreach($_POST['banner_datas'] as $bkey => $value){

	           		$id = getParameterString_custom($value['id'],'',$db);

	           		$title = getParameterString_custom($value['title'],'',$db);

		            $link = getParameterString_custom($value['link'],'',$db);

		            $sort_order = getParameterString_custom($value['sort_order'],'',$db);

	                try {

	                	$rObj->updateBanner($id,$title,$link,$sort_order);

	                } catch (Exception $err) {

	                    echo $err->getMessage();

	                }

	            }

	            $json['success'] = 'Banner Data Updated';      

	        }      		



			if(isset($_FILES['file']) && $q == 'image-upload'){

				date_default_timezone_set("Asia/Kolkata");

				$file_upload_path = DIR_BANNER_UPLOAD.'/';

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

					

					$sql = "INSERT INTO `banner` SET `banner_id` = '1', `title` = '".$file_title."', `source` = '".$file."', `sort_order` = '".$cnt."' ";

					$rObj->insertBannerimage($sql); 

					$cnt ++;

				}

				$json['success'] = 'Banner Images Uploaded';

			}



			/*

			if (!empty($_FILES['file']['name']) && is_file($_FILES['file']['tmp_name'])) {

				// Sanitize the filename

				$filename = basename(html_entity_decode($_FILES['file']['name'], ENT_QUOTES, 'UTF-8'));

				

				// Allowed file extension types

				$allowed = array();

				$allowed[] = 'jpg';

				$allowed[] = 'jpeg';

				$allowed[] = 'png';



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

			

			if ($json['error'] == '') {

				$file = 'trustee.html';

				$upload_file_path = DIR_BANNER_UPLOAD.'/'.$file;

				move_uploaded_file($_FILES['file']['tmp_name'], $upload_file_path);

				$json['success'] = 'File Uploaded';

			}

			*/

		}

		$banner_datas = $rObj->getbanner_datas();

		$banner_datas_count = count($banner_datas) + 1;

		// echo '<pre>';

		// print_r($banner_datas);

		// exit;



	} else {

		$msg = "You do not have access to this page.";

	}  

} else {

	$secmsg = "Please login to access this page";

}

$pageTitle ='Banner Manager';        

// create a template object

$design = new Design();  



$design->js='

<script type="text/javascript" src="lib/ckeditor/ckeditor.js"></script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<script type="text/javascript">

    function confirmDelete(bannerID) {

        if (confirm ("Are you sure ?")){

            location.href="admin/bannerManager.php?q=delete-banner&id="+bannerID;

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
    $('#bannerSearchInput').on('keyup', function() {
        var term = $(this).val().toLowerCase().trim();
        var visibleCount = 0;
        $('.banner-card').each(function() {
            var titleVal = $(this).find('input[name*=\"[title]\"]').val() || '';
            var sortVal = $(this).find('input[name*=\"[sort_order]\"]').val() || '';
            var match = titleVal.toLowerCase().indexOf(term) !== -1 || sortVal.toLowerCase().indexOf(term) !== -1;
            $(this).toggle(match);
            if (match) { visibleCount++; }
        });
        $('#bannerNoResults').toggle(term.length > 0 && visibleCount === 0);
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

.banner-header { margin-bottom: 20px; }
.banner-header h2 { margin: 0; font-size: 22px; color: #2b332f; font-weight: 700; }
.banner-header p { margin: 4px 0 0; font-size: 13.5px; color: #7a8c84; }

.section-title { font-size: 16px; font-weight: 700; color: #0f5c33; margin: 28px 0 14px; display: flex; align-items: center; gap: 8px; }

/* ===== search bar ===== */
.banner-search-wrap {
    position: relative;
    margin-bottom: 16px;
}
.banner-search-wrap i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #7a8c84;
    font-size: 13.5px;
}
.banner-search-wrap input[type="text"] {
    width: 100%;
    box-sizing: border-box;
    padding: 11px 14px 11px 38px;
    border: 1px solid #e2e6e4;
    border-radius: 8px;
    font-size: 14px;
    background: #fff;
}
.banner-search-wrap input[type="text"]:focus {
    outline: none;
    border-color: #1a7a45;
    box-shadow: 0 0 0 3px rgba(26,122,69,0.12);
}
/* ===== upload form — normal inline screen card ===== */
.banner-upload-wrap {
    background: #fff;
    border: 1px solid #e2e6e4;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}
.banner-upload-table { width: 100%; border-collapse: collapse; }
.banner-upload-table th { text-align: left; padding: 10px 8px; color: #2b332f; vertical-align: top; width: 20%; font-weight: 600; font-size: 13.5px; }
.banner-upload-table td { padding: 10px 8px; }
.banner-upload-table input[type="file"] {
    border: 1px solid #e2e6e4; border-radius: 6px; padding: 8px 10px; font-size: 14px;
    width: 100%; max-width: 100%; box-sizing: border-box; background: #f7faf8;
}
.banner-upload-table input[type="submit"] { background: #0f5c33; color: #fff; border: none; padding: 9px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; margin-right: 8px; }
.banner-upload-table input[type="submit"]:hover { background: #0b3d24; }
.banner-upload-table input[type="reset"] { background: #fff; color: #2b332f; border: 1px solid #e2e6e4; padding: 9px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; }
.banner-upload-table input[type="reset"]:hover { background: #f5f4ee; }

/* ===== banner editor card grid ===== */
.banner-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 18px; margin-bottom: 20px; }
.banner-card { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.03); display: flex; flex-direction: column; }
.banner-card .thumb-wrap { width: 100%; aspect-ratio: 1 / 1; background: #f5f4ee; overflow: hidden; }
.banner-card .thumb-wrap img { width: 100%; height: 100%; object-fit: cover; display: block; }
.banner-card .banner-card-body { padding: 12px 14px; display: flex; flex-direction: column; gap: 10px; flex: 1; }
.banner-card label { font-size: 12px; font-weight: 700; color: #7a8c84; text-transform: uppercase; letter-spacing: .3px; margin-bottom: -4px; }
.banner-card input[type="text"] {
    width: 100%; box-sizing: border-box; border: 1px solid #e2e6e4; border-radius: 6px; padding: 8px 10px; font-size: 13.5px;
}
.banner-card .banner-card-actions { display: flex; justify-content: flex-end; border-top: 1px solid #eef0ee; padding-top: 10px; margin-top: auto; }
.banner-card .banner-card-actions a { font-size: 13px; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 6px; cursor: pointer; color: #c0392b; }
.banner-empty { grid-column: 1 / -1; text-align: center; padding: 30px 20px; color: #7a8c84; font-size: 14.5px; background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; }
.banner-save-bar { display: flex; justify-content: flex-end; }
.banner-save-bar input[type="submit"] { background: #0f5c33; color: #fff; border: none; padding: 10px 22px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; }
.banner-save-bar input[type="submit"]:hover { background: #0b3d24; }

html, body { scrollbar-width: none; -ms-overflow-style: none; }
html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }

/* ===== responsive ===== */
@media (max-width: 900px) {
    #infoWrapper.col-lg-12 { flex-direction: column; margin: 16px auto; }
    #leftArea.col-lg-9 { flex: 1 1 100%; max-width: 100%; padding: 28px 24px; }
}
@media (max-width: 700px) {
    #leftArea.col-lg-9 { padding: 0 16px; }
    .banner-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; }
}
@media (max-width: 520px) {
    .banner-upload-table, .banner-upload-table tbody, .banner-upload-table tr, .banner-upload-table th, .banner-upload-table td {
        display: block; width: 100% !important;
    }
    .banner-upload-table th { padding-bottom: 2px; }
    .banner-upload-table td { padding-top: 0; padding-bottom: 14px; }
    .banner-grid { grid-template-columns: 1fr; }
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

	<!-- <div class="banner-header">
		<h2>Banner Manager</h2>
		<p>Upload homepage banner images and manage their title, link and sort order.</p>
	</div> -->
	<!--
	<div class="submenu">

	  	<a href="admin/bannerManager.php">Banner Manager</a>

	  	<div style="float:right;">

			<a style="float:left;" href="admin/dashboard.php">Dashboard</a>

			<a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>

	   	</div>

	</div>
	-->

	<div class="banner-upload-wrap">
	<form enctype="multipart/form-data" name="bannerimageForm" method="post" action="admin/bannerManager.php">

	  	<table class="banner-upload-table">

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

				  	<input type="hidden" name="count" value="<?php echo $banner_datas_count; ?>" />

				  	<input type="reset" name="reset" value="Clear" onclick="location.href='admin/bannerManager.php'" />

			  	</td>

		  	</tr>

	  	</table>

	</form>
	</div>

	<div class="section-title"><i class="fas fa-images"></i> Banner Images</div>
	<div class="banner-search-wrap">
		<i class="fas fa-magnifying-glass"></i>
		<input type="text" id="bannerSearchInput" placeholder="Search by name or sort order..." />
	</div>
	<form enctype="multipart/form-data" name="bannerForm" method="post" action="admin/bannerManager.php">
	  	<div id="bannerNoResults" class="banner-empty" style="display:none;">No banners match your search.</div>
	  	<div class="banner-grid">

			<?php if (count($banner_datas) > 0) { ?>
			<?php foreach($banner_datas as $bkey => $bvalue){ ?>

			  	<div class="banner-card">
			  		<div class="thumb-wrap">
			  			<img src="<?php echo HTTP_BANNER_UPLOAD.'/'.$bvalue['source']; ?>" alt="" />
			  		</div>
			  		<div class="banner-card-body">
			  			<label>Image Title</label>
						<input type="text" name="banner_datas[<?php echo $bkey ?>][title]" value="<?php echo $bvalue['title']; ?>" />

			  			<label>Link Href</label>
						<input type="text" name="banner_datas[<?php echo $bkey ?>][link]" value="<?php echo $bvalue['link']; ?>" />

			  			<label>Sort Order</label>
						<input type="text" name="banner_datas[<?php echo $bkey ?>][sort_order]" value="<?php echo $bvalue['sort_order']; ?>" />

						<input type="hidden" name="banner_datas[<?php echo $bkey ?>][source]" value="<?php echo $bvalue['source']; ?>" />

						<input type="hidden" name="banner_datas[<?php echo $bkey ?>][id]" value="<?php echo $bvalue['id']; ?>" />

						<div class="banner-card-actions">
							<a onclick="javascript: confirmDelete(<?php echo $bvalue['id'];?>);"><i class="fas fa-trash-alt"></i> Delete</a>
						</div>
			  		</div>
			  	</div>

			<?php } ?>
			<?php } else { ?>
				<div class="banner-empty">No banner images uploaded yet.</div>
			<?php } ?>

	  	</div>

		<?php if (count($banner_datas) > 0) { ?>
		<div class="banner-save-bar">
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