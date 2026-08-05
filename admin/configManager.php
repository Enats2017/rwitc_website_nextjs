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
	if ($_SESSION['configManager'] == "Y") { // check login
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
		

		if (isset($_REQUEST['submit'])) {
			//$body = getParameterString('file','',$db);
			if ($q=="save-data") {
				$ODDSBOX = getParameterString('ODDSBOX','N',$db);
				$LINKSBOX1 = getParameterString('LINKSBOX1','N',$db);
				$LINKSBOX2 = getParameterString('LINKSBOX2','N',$db);
				$LINKSBOX3 = getParameterString('LINKSBOX3','N',$db);
				$LINKSBOX4 = getParameterString('LINKSBOX4','N',$db);
				$FINALRESBOX = getParameterString('FINALRESBOX','N',$db);

				$MAXCOUNT = getParameterString('MAXCOUNT','',$db);
              	
              	// handle checkbox state
              	if (strtolower($ODDSBOX)== "y") {
                	$ODDSBOX="Y";
              	} else {
              		$ODDSBOX="N";
              	}

              	if (strtolower($LINKSBOX1)== "y") {
                	$LINKSBOX1="Y";
              	} else {
              		$LINKSBOX1="N";
              	}	
              	if (strtolower($LINKSBOX2)== "y") {
                	$LINKSBOX2="Y";
              	} else {
              		$LINKSBOX2="N";
              	}	
              	if (strtolower($LINKSBOX3)== "y") {
                	$LINKSBOX3="Y";
              	} else {
              		$LINKSBOX3="N";
              	}	
              	if (strtolower($LINKSBOX4)== "y") {
                	$LINKSBOX4="Y";
              	} else {
              		$LINKSBOX4="N";
              	}	
              	if (strtolower($FINALRESBOX)== "y") {
                	$FINALRESBOX="Y";
              	} else {
              		$FINALRESBOX="N";
              	}
                try {
                	$rObj->updateconfig(1,$ODDSBOX);
                	$rObj->updateconfig(2,$LINKSBOX1);
                	$rObj->updateconfig(3,$LINKSBOX2);
                	$rObj->updateconfig(4,$LINKSBOX3);
                	$rObj->updateconfig(5,$LINKSBOX4);
                	$rObj->updateconfig(6,$FINALRESBOX);
                	$rObj->updateconfig(7,$MAXCOUNT);
                } catch (Exception $err) {
                    echo $err->getMessage();
                }
	            $json['success'] = 'Config Data Updated';      
	        }      		
		}
		$oddsbox = $rObj->getconfig_datas(1);
		$linkbox1 = $rObj->getconfig_datas(2);
		$linkbox2 = $rObj->getconfig_datas(3);
		$linkbox3 = $rObj->getconfig_datas(4);
		$linkbox4 = $rObj->getconfig_datas(5);
		$finalresbox = $rObj->getconfig_datas(6);
		$max_count = $rObj->getconfig_datas(7);
		// echo '<pre>';
		// print_r($config_datas);
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
	  	<a href="admin/configManager.php">Config Manager</a>
	  	<div style="float:right;">
			<a style="float:left;" href="admin/dashboard.php">Dashboard</a>
			<a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
	   	</div>
	</div>
	<br />
	<form enctype="multipart/form-data" name="configimageForm" method="post" action="admin/configManager.php">
	  	<table class="contentTable">
		  	<col width="20%"><col width="80%">
		  	<tr>
			  	<th>ODDSBOX</th>
			  	<td class="alignLeft">
			  		<?php if($oddsbox == "Y"){ ?>
						<input type="radio" name="ODDSBOX" value="Y" checked="checked" /> ON
						<input type="radio" name="ODDSBOX" value="N" /> OFF
					<?php } else { ?>
						<input type="radio" name="ODDSBOX" value="Y" /> ON
						<input type="radio" name="ODDSBOX" checked="checked" value="N" /> OFF
					<?php } ?>
			  	</td>
		  	</tr>
		  	<tr>
			  	<th>LINKSBOX 1</th>
			  	<td class="alignLeft">
			  		<?php if($linkbox1 == "Y"){ ?>
						<input type="radio" name="LINKSBOX1" checked="checked" value="Y"/> ON
						<input type="radio" name="LINKSBOX1" value="N"/> OFF
					<?php } else { ?>
						<input type="radio" name="LINKSBOX1" value="Y" /> ON
						<input type="radio" name="LINKSBOX1" value="N" checked="checked" /> OFF
					<?php } ?>
			  	</td>
			  </tr>
			  <tr>
			  	<th>LINKSBOX 2</th>
			  	<td class="alignLeft">
			  		<?php if($linkbox2 == "Y"){ ?>
						<input type="radio" name="LINKSBOX2" checked="checked" value="Y"/> ON
						<input type="radio" name="LINKSBOX2" value="N"/> OFF
					<?php } else { ?>
						<input type="radio" name="LINKSBOX2" value="Y" /> ON
						<input type="radio" name="LINKSBOX2" value="N" checked="checked" /> OFF
					<?php } ?>
			  	</td>
			  </tr>
			  <tr>
			  	<th>LINKSBOX 3</th>
			  	<td class="alignLeft">
			  		<?php if($linkbox3 == "Y"){ ?>
						<input type="radio" name="LINKSBOX3" checked="checked" value="Y"/> ON
						<input type="radio" name="LINKSBOX3" value="N"/> OFF
					<?php } else { ?>
						<input type="radio" name="LINKSBOX3" value="Y" /> ON
						<input type="radio" name="LINKSBOX3" value="N" checked="checked" /> OFF
					<?php } ?>
			  	</td>
			  </tr>
			  <tr>
			  	<th>LINKSBOX 4</th>
			  	<td class="alignLeft">
			  		<?php if($linkbox4 == "Y"){ ?>
						<input type="radio" name="LINKSBOX4" checked="checked" value="Y"/> ON
						<input type="radio" name="LINKSBOX4" value="N"/> OFF
					<?php } else { ?>
						<input type="radio" name="LINKSBOX4" value="Y" /> ON
						<input type="radio" name="LINKSBOX4" value="N" checked="checked" /> OFF
					<?php } ?>
			  	</td>
			  </tr>
		  	<tr>
			  	<th>FINALRESBOX</th>
			  	<td class="alignLeft">
			  		<?php if($finalresbox == "Y"){ ?>
						<input type="radio" name="FINALRESBOX" value="Y" checked="checked" /> ON
						<input type="radio" name="FINALRESBOX" value="N" /> OFF
					<?php } else { ?>
						<input type="radio" name="FINALRESBOX" value="Y" /> ON
						<input type="radio" name="FINALRESBOX" value="N" checked="checked" /> OFF
					<?php } ?>
			  	</td>
		  	</tr>
		  	<tr>
                <th>MAXCOUNT</th>
                <td>
                	<input style="width: 100%;" type="text" name="MAXCOUNT" value="<?php echo $max_count; ?>" />
                </td>
            </tr>
		  	<tr>
			  	<td colspan="2">
				  	<input type="submit" name="submit" value="submit" />
				  	<input type="hidden" name="q" value="save-data" />
				  	<input type="reset" name="reset" value="Clear" onclick="location.href='admin/configManager.php'" />
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