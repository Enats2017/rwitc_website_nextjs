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
		if (get_magic_quotes_gpc()) {
			function stripslashes_deep($value) {
				$value = is_array($value) ?   array_map('stripslashes_deep', $value) : stripslashes($value);
				return $value;
			}
			$_POST = array_map('stripslashes_deep', $_POST);
			$_REQUEST = array_map('stripslashes_deep', $_REQUEST);
		}
	  
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
<script type="text/javascript" src="lib/ckeditor/ckeditor.js"></script>
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
<style type="text/css">
  #title { color: #000000; font-size: 14px; margin: 10px; margin: auto; text-align: left; display:block; }
</style>
<style type="text/css">
  .navi {
  width: 500px;
  margin: 5px;
  padding:2px 5px;
  border:1px solid #eee;
  }

  .show {
  color: blue;
  margin: 5px 0;
  padding: 3px 5px;
  cursor: pointer;
  font: 15px/19px Arial,Helvetica,sans-serif;
  }
  .show a {
  text-decoration: none;
  }
  .show:hover {
  text-decoration: underline;
  }


  ul.setPaginate li.setPage{
  padding:15px 10px;
  font-size:14px;
  }

  ul.setPaginate{
  margin:0px;
  padding:0px;
  height:100%;
  overflow:hidden;
  font:12px "Tahoma";
  list-style-type:none; 
  }  

  ul.setPaginate li.dot{padding: 3px 0;}

  ul.setPaginate li{
  float:left;
  margin:0px;
  padding:0px;
  margin-left:5px;
  }



  ul.setPaginate li a
  {
  background: none repeat scroll 0 0 #ffffff;
  border: 1px solid #cccccc;
  color: #999999;
  display: inline-block;
  font: 15px/25px Arial,Helvetica,sans-serif;
  margin: 5px 3px 0 0;
  padding: 0 5px;
  text-align: center;
  text-decoration: none;
  } 

  ul.setPaginate li a:hover,
  ul.setPaginate li a.current_page
  {
  background: none repeat scroll 0 0 #0d92e1;
  border: 1px solid #000000;
  color: #ffffff;
  text-decoration: none;
  }

  ul.setPaginate li a{
  color:black;
  display:block;
  text-decoration:none;
  padding:5px 8px;
  text-decoration: none;
  }




  </style>
';
$design->jqueryJs = ""; 
$design->startPage("$pageTitle");  
$design->writeLogoTickerMenu();
$design->openDiv("contentWrapper");
$design->openDiv("infoWrapper");
$design->openDiv("leftArea");
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
		<div class="submenu">
		  	<a href="admin/mailManager.php?q=new-mail">Draft New Mail</a>
		  	<div style="float:right;">
				<a style="float:left;" href="admin/dashboard.php">Dashboard</a>
				<a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
		   	</div>
		</div>
		<br />
		<form enctype="multipart/form-data" name="mailForm" method="post" action="admin/mailManager.php">
			<table class="contentTable">
				<col width="20%"><col width="80%">
				<tr>
			  		<th>From Email</th>
			  		<td>
						<input readonly="readonly" style="width: 100%;" type="text" name="from_email" value="<?php echo $mailDetails['from_email']; ?>" />
			  		</td>
				</tr>
				<tr>
			  		<th>From Name</th>
			  		<td>
						<input style="width: 100%;" type="text" name="from_name" value="<?php echo $mailDetails['from_name']; ?>" />
			  		</td>
				</tr>
				<tr>
			  		<th>To Email</th>
			  		<td>
						<input style="width: 100%;" type="text" name="to_email" value="<?php echo $mailDetails['to_email']; ?>" />
			  		</td>
				</tr>
				<tr style="display: none;">
			  		<th>To Name</th>
			  		<td>
						<input style="width: 100%;" type="text" name="to_name" value="<?php echo $mailDetails['to_name']; ?>" />
			  		</td>
				</tr>
				<tr>
			  		<th>Subject</th>
			  		<td>
						<input style="width: 100%;" type="text" name="subject" value="<?php echo $mailDetails['subject']; ?>" />
			  		</td>
				</tr>
				<tr>
					<th>Message</th>
					<td class="alignLeft"><textarea name="message" id="message" rows="5" cols="50"><?php echo $mailDetails['body'] ?></textarea></td>
				</tr>
				<tr>
				  	<th>Upload Attachment</th>
				  	<td class="alignLeft">
						<input type="file" name="file[]" value="file" multiple />
				  	</td>
			  	</tr>
				<tr>
					<td colspan="2">
						<input type="submit" name="submit" value="Send" />
						<input type="reset" name="reset" value="Clear" onclick="location.href='admin/mailManager.php'" />
					</td>
				</tr>
			</table>
		</form>
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
//$design->rightArea();  
//$design->closeDiv();
$design->endPage();
$design->pageClose();    
$design = NULL; // release object