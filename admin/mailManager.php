<?php
include_once('../bootstrap.php');
require_once('../lib/articles.class.php');
include_once('../lib/pagination.class.php');
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


$msg = $secmsg = "";
$pageno = getParameterNumber('pageno',1);
$articles = new Articles($db);
if (isAdminlogin()) {
	if ($_SESSION['mailManager'] == "Y") { // check login
		//if (get_magic_quotes_gpc()) {
			function stripslashes_deep($value) {
				$value = is_array($value) ?   array_map('stripslashes_deep', $value) : stripslashes($value);
				return $value;
			}
			$_POST = array_map('stripslashes_deep', $_POST);
			$_REQUEST = array_map('stripslashes_deep', $_REQUEST);
		//}
	  
		// all actions POST form submissions go here
		if (isset($_REQUEST['submit'])) {
			$from_email = getParameterString('from_email','',$db);
			$from_name = getParameterString('from_name','',$db);
			$to_email = $_REQUEST['to_email'];
			$to_name = getParameterString('to_name','',$db);
			$subject = getParameterString('subject','',$db);
			//$message = getParameterString('message','',$db);
			$message = $_REQUEST['message'];

			$attachment_array = array();
				
			// echo '<pre>';
			// print_r($_FILES);
			// exit;

			if(isset($_FILES['file'])){
				date_default_timezone_set("Asia/Kolkata");
				$file_upload_path = DIR_ATTACHMENT_UPLOAD.'/';
				$http_upload_path = HTTP_ATTACHMENT_UPLOAD.'/';
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
		   		//$image_name_string = '';
		   		foreach ($files_arr as $fkey => $fvalue) {
					// $timestamp = date('YmdHis').rand(10, 10000);
					// $file = 'Image_'.$timestamp.'.jpg';
					//$file_titles = explode('.', $fvalue['name']);
					$file_title = $fvalue['name'];
					if($file_title != ''){
						$exist_path = $file_upload_path.$file_title;
						if(file_exists($exist_path)){
							unlink($exist_path);
						}
					}
					if (move_uploaded_file($fvalue['tmp_name'], $file_upload_path . $file_title)) {
						$destFile = $file_upload_path . $file_title;
						$destFile_http = $http_upload_path . $file_title;
						chmod($destFile, 0777);
						$attachment_array[] = $destFile_http;
					}
				}
			}

			$to_email_exp = explode(',', $to_email);
			foreach($to_email_exp as $akey => $avalue){
				$to_array[$avalue] = $avalue;
			}
			//echo $message;exit;
			try {
              	require('Mailin.php');
              	$mailin = new Mailin("https://api.sendinblue.com/v2.0","N12OPU6qLfTsxnK7");
              	$data = array("to" => $to_array,
                            "from" => array($from_email, $from_name),
                            "subject" => $subject,
                            "html" => html_entity_decode(trim($message)),
                            "attachment" => $attachment_array
                        );
              	// echo '<pre>';
              	// print_r($data);
              	// exit;
              	$response = $mailin->send_email($data);
              	if($response['code'] == 'success'){
              		$msg = "Mail Sent";
              	} else {
              		$msg = "Please try again later";
              	}
			} catch (Exception $err) {
				 echo $err->getMessage();
			}
		}

		if(!isset($mailDetails['from_email'])){
			$mailDetails['from_email'] = 'edp@rwitc.com';
		}
		if(!isset($mailDetails['from_name'])){
			$mailDetails['from_name'] = 'RWITC';
		}
		if(!isset($mailDetails['to_email'])){
			$mailDetails['to_email'] = '';
		}
		if(!isset($mailDetails['to_name'])){
			$mailDetails['to_name'] = '';
		}
		if(!isset($mailDetails['subject'])){
			$mailDetails['subject'] = '';
		}
		if(!isset($mailDetails['body'])){
			$mailDetails['body'] = '';
		}
	} else {
		$msg = "You do not have access to this page.";
	}  
} else {
	$secmsg = "Please login to access this page";
}
$pageTitle ='Mail Manager';        
// create a template object
$design = new Design();  

$design->js='
<script type="text/javascript" src="https://cdn.ckeditor.com/4.8.0/full-all/ckeditor.js"></script>
<script type="text/javascript">
	function confirmDelete(mailID) {
		if (confirm ("Are you sure ?")){
			location.href="admin/mailManager.php?q=delete-mail&id="+mailID;
		}
	}
</script>
';
$design->css ='
<link type="text/css" href="css/jquery.ui.all.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style type="text/css">
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
.message { background: #fff3cd; border: 1px solid #ffe08a; padding: 12px 16px; border-radius: 8px; margin-bottom: 15px; font-size: 15px; }

.mail-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.mail-header .mail-title { color:#2b332f; font-weight:700; font-size:20px; display:flex; align-items:center; gap:10px; }
.header-links { display: flex; align-items: center; gap: 16px; }
.header-links a { color: #0f5c33; text-decoration: none; font-weight: 600; font-size: 14px; }
.header-links a:hover { text-decoration: underline; }

.mail-form-wrap { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); max-width: 700px; }
.mail-form-wrap .form-row { margin-bottom: 20px; }
.mail-form-wrap label.form-label { display: block; font-size: 14px; font-weight: 600; color: #2b332f; margin-bottom: 8px; }
.mail-form-wrap input[type="text"], .mail-form-wrap textarea {
  width: 100%; border: 1px solid #e2e6e4; border-radius: 8px; padding: 10px 12px; font-size: 14px; color: #2b332f; box-sizing: border-box; font-family: inherit;
}
.mail-form-wrap input[type="text"]:focus, .mail-form-wrap textarea:focus { outline: none; border-color: #1a7a45; }
.mail-form-wrap input[type="text"][readonly] { background: #f5f4ee; color: #7a8c84; }
.mail-form-wrap textarea { resize: vertical; min-height: 140px; }
.mail-form-wrap input[type="file"] {
  width: 100%; border: 1px solid #e2e6e4; border-radius: 8px; padding: 9px 12px; font-size: 14px; color: #2b332f; box-sizing: border-box; background: #f9faf9;
}
.mail-form-wrap .form-actions { display: flex; gap: 10px; padding-top: 6px; }
.mail-form-wrap input[type="submit"], .mail-form-wrap input[type="reset"] { background: #0f5c33; color: #fff; border: none; padding: 10px 22px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; }
.mail-form-wrap input[type="reset"] { background: #fff; color: #2b332f; border: 1px solid #e2e6e4; }
.mail-form-wrap input[type="submit"]:hover { background: #0c4a29; }
.mail-form-wrap input[type="reset"]:hover { background: #f5f4ee; }

html, body { scrollbar-width: none; -ms-overflow-style: none; }
html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }


@media (max-width: 700px) {
  #leftArea.col-lg-9 { padding: 0 16px; }
  .mail-header { flex-direction: column; align-items: flex-start; }
  .mail-form-wrap { padding: 18px; }
}      
.cke_button_icon,
.cke_editor_message img,
.cke_reset_all img {
	filter: none !important;
	-webkit-filter: none !important;
}
</style>
';   
$design->jqueryJs = ""; 
$design->startPage("$pageTitle");  
$design->writeLogoTickerMenu();
$design->openDiv("contentWrapper");
$design->openDiv("infoWrapper","col-lg-12");
$design->openDiv("leftArea",'col-lg-9');
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
	<?php if ($_SESSION['mailManager'] == "Y") { ?>
		<div class="mail-header">
		  	<span class="mail-title"><i class="fas fa-envelope"></i> Draft New Mail</span>
		  	<div class="header-links">
				<!-- <a href="admin/dashboard.php">Dashboard</a>
				<a href="admin/adminlogin.php?q=logout">Logout</a> -->
		   	</div>
		</div>
		<div class="mail-form-wrap">
		<form enctype="multipart/form-data" name="mailForm" method="post" action="admin/mailManager.php">
				<div class="form-row">
			  		<label class="form-label" for="from_email">From Email</label>
						<input readonly="readonly" type="text" name="from_email" id="from_email" value="<?php echo $mailDetails['from_email']; ?>" />
				</div>
				<div class="form-row">
			  		<label class="form-label" for="from_name">From Name</label>
						<input type="text" name="from_name" id="from_name" value="<?php echo $mailDetails['from_name']; ?>" />
				</div>
				<div class="form-row">
			  		<label class="form-label" for="to_email">To Email</label>
						<input type="text" name="to_email" id="to_email" value="<?php echo $mailDetails['to_email']; ?>" />
				</div>
				<div class="form-row" style="display: none;">
			  		<label class="form-label" for="to_name">To Name</label>
						<input type="text" name="to_name" id="to_name" value="<?php echo $mailDetails['to_name']; ?>" />
				</div>
				<div class="form-row">
			  		<label class="form-label" for="subject">Subject</label>
						<input type="text" name="subject" id="subject" value="<?php echo $mailDetails['subject']; ?>" />
				</div>
				<div class="form-row">
					<label class="form-label" for="message">Message</label>
					<textarea name="message" id="message" rows="5" cols="50"><?php echo $mailDetails['body'] ?></textarea>
				</div>
				<div class="form-row">
				  	<label class="form-label" for="file">Upload Attachment</label>
						<input type="file" name="file[]" id="file" value="file" multiple />
			  	</div>
				<div class="form-actions">
						<input type="submit" name="submit" value="Send" />
						<input type="reset" name="reset" value="Clear" onclick="location.href='admin/mailManager.php'" />
				</div>
		</form>
		</div>
	   	<script type="text/javascript">
		//<![CDATA[
		CKEDITOR.replace( 'message',
		{
			fullPage : true,
			filebrowserBrowseUrl : 'lib/ckfinder/ckfinder.html',
			filebrowserImageBrowseUrl : 'lib/ckfinder/ckfinder.html?type=Images',
			filebrowserFlashBrowseUrl : 'lib/ckfinder/ckfinder.html?type=Flash',
			filebrowserUploadUrl : 'imageUpload.php'
		});
		//]]>
		</script>
	<?php } ?>
<?php                   
$design->closeDiv();
$design->writeLeftPanel();
$design->closeDiv();
$design->endPage();
$design->pageClose();    
$design = NULL; // release object