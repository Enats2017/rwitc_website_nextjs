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
	if ($_SESSION['horseweightManager'] == "Y") { // check login
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
		

		if (isset($_REQUEST['Reset_Horse_Body_Weight'])) {
			//$body = getParameterString('file','',$db);
			if ($q=="save-data") {
				try {
                	$rObj->clearhorsebodyweight();
                } catch (Exception $err) {
                    echo $err->getMessage();
                }
	            $json['success'] = 'Horse Body Weight Cleared Successfully';      
	        }      		
		}
	} else {
		$msg = "You do not have access to this page.";
	}  
} else {
	$secmsg = "Please login to access this page";
}
$pageTitle ='Reset Horse Body Weight Manager';        
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
	  	<a href="admin/horseweightManager.php">Reset Horse Body Weight Manager</a>
	  	<div style="float:right;">
			<a style="float:left;" href="admin/dashboard.php">Dashboard</a>
			<a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
	   	</div>
	</div>
	<br />
	<form enctype="multipart/form-data" name="horseweightimageForm" method="post" action="admin/horseweightManager.php">
	  	<table class="contentTable">
		  	<col width="20%"><col width="80%">
		  	<tr>
			  	<td colspan="2">
				  	<input type="submit" name="Reset Horse Body Weight" value="Reset Horse Body Weight" />
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