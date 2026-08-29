<?php
include_once('../bootstrap.php');
include_once('../lib/pagination.class.php');
require_once("../lib/users.class.php");
require_once("../lib/userchecks.php");
require_once("../lib/race.class.php");
$q = getParameterString('q', '', $db);

session_start();
$uid = $_SESSION['uid'];
$userObj = new Users($db);
$msg = $secmsg = "";
$rObj = new Racedata($db);
if (isAdminlogin()) {
	if ($_SESSION['configManager'] == "Y") { // check login
		//if (get_magic_quotes_gpc()) {
		function stripslashes_deep($value)
		{
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
			if ($q == "save-data") {
				$ODDSBOX = getParameterString('ODDSBOX', 'N', $db);
				$LINKSBOX1 = getParameterString('LINKSBOX1', 'N', $db);
				$LINKSBOX2 = getParameterString('LINKSBOX2', 'N', $db);
				$LINKSBOX3 = getParameterString('LINKSBOX3', 'N', $db);
				$LINKSBOX4 = getParameterString('LINKSBOX4', 'N', $db);
				$FINALRESBOX = getParameterString('FINALRESBOX', 'N', $db);

				$MAXCOUNT = getParameterString('MAXCOUNT', '', $db);

				// handle checkbox state
				if (strtolower($ODDSBOX) == "y") {
					$ODDSBOX = "Y";
				} else {
					$ODDSBOX = "N";
				}

				if (strtolower($LINKSBOX1) == "y") {
					$LINKSBOX1 = "Y";
				} else {
					$LINKSBOX1 = "N";
				}
				if (strtolower($LINKSBOX2) == "y") {
					$LINKSBOX2 = "Y";
				} else {
					$LINKSBOX2 = "N";
				}
				if (strtolower($LINKSBOX3) == "y") {
					$LINKSBOX3 = "Y";
				} else {
					$LINKSBOX3 = "N";
				}
				if (strtolower($LINKSBOX4) == "y") {
					$LINKSBOX4 = "Y";
				} else {
					$LINKSBOX4 = "N";
				}
				if (strtolower($FINALRESBOX) == "y") {
					$FINALRESBOX = "Y";
				} else {
					$FINALRESBOX = "N";
				}
				try {
					$rObj->updateconfig(1, $ODDSBOX);
					$rObj->updateconfig(2, $LINKSBOX1);
					$rObj->updateconfig(3, $LINKSBOX2);
					$rObj->updateconfig(4, $LINKSBOX3);
					$rObj->updateconfig(5, $LINKSBOX4);
					$rObj->updateconfig(6, $FINALRESBOX);
					$rObj->updateconfig(7, $MAXCOUNT);
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
$pageTitle = 'Banner Manager';
// create a template object
$design = new Design();

$design->js = '
<script type="text/javascript" src="lib/ckeditor/ckeditor.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
';
$design->css = '
<style type="text/css">
  #title { color: #000000; font-size: 14px; margin: 10px; margin: auto; text-align: left; display:block; }
</style>
';
$design->jqueryJs = "";
$design->startPage("$pageTitle");
$design->writeLogoTickerMenu();
$design->openDiv("contentWrapper");
$design->openDiv("infoWrapper", "col-lg-12");
$design->openDiv("leftArea", "col-lg-9");
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

	#infoWrapper.col-lg-12 #rightArea.col-lg-3 {
		padding-top: 0 !important;
	}

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

	.config-header {
		margin-bottom: 20px;
	}

	.config-header h2 {
		margin: 0;
		font-size: 22px;
		color: #2b332f;
		font-weight: 700;
	}

	.config-header p {
		margin: 4px 0 0;
		font-size: 13.5px;
		color: #7a8c84;
	}

	/* ===== config form — normal inline screen card ===== */
	.config-form-wrap {
		background: #fff;
		border: 1px solid #e2e6e4;
		border-radius: 12px;
		padding: 8px 20px;
		box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
	}

	.config-row {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 16px;
		padding: 16px 4px;
		border-bottom: 1px solid #eef0ee;
		flex-wrap: wrap;
	}

	.config-row:last-of-type {
		border-bottom: none;
	}

	.config-row-label {
		font-size: 14.5px;
		font-weight: 600;
		color: #2b332f;
	}

	.config-toggle {
		display: inline-flex;
		/* border: 1px solid #e2e6e4; */
		border-radius: 999px;
		overflow: hidden;
	}

	.config-toggle label {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		padding: 7px 18px;
		font-size: 13px;
		font-weight: 700;
		letter-spacing: .3px;
		cursor: pointer;
		color: #7a8c84;
		background: #fff;
	}

	.config-toggle label:first-child {
		border-radius: 999px 0 0 999px;
	}

	.config-toggle label:last-child {
		border-radius: 0 999px 999px 0;
	}

	.config-toggle input[type="radio"] {
		display: none;
	}

	.config-toggle label.on-label:has(input:checked) {
		background: #0f5c33;
		color: #fff;
	}

	.config-toggle label.off-label:has(input:checked) {
		background: #c0392b;
		color: #fff;
	}

	.config-maxcount-row .config-row-label {
		flex-shrink: 0;
	}

	.config-maxcount-row input[type="text"] {
		border: 1px solid #e2e6e4;
		border-radius: 6px;
		padding: 8px 12px;
		font-size: 14px;
		width: 160px;
		max-width: 100%;
		box-sizing: border-box;
	}

	.config-form-actions {
		display: flex;
		gap: 10px;
		padding: 18px 4px 8px;
	}

	.config-form-actions input[type="submit"] {
		background: #0f5c33;
		color: #fff;
		border: none;
		padding: 10px 24px;
		border-radius: 8px;
		cursor: pointer;
		font-size: 14px;
		font-weight: 600;
	}

	.config-form-actions input[type="submit"]:hover {
		background: #0b3d24;
	}

	.config-form-actions input[type="reset"] {
		background: #fff;
		color: #2b332f;
		border: 1px solid #e2e6e4;
		padding: 10px 24px;
		border-radius: 8px;
		cursor: pointer;
		font-size: 14px;
		font-weight: 600;
	}

	.config-form-actions input[type="reset"]:hover {
		background: #f5f4ee;
	}

	/* ===== responsive ===== */
	@media (max-width: 900px) {
		#infoWrapper.col-lg-12 {
			flex-direction: column;
			margin: 16px auto;
		}

		#leftArea.col-lg-9 {
			flex: 1 1 100%;
			max-width: 100%;
			padding: 28px 24px;
		}
	}

	@media (max-width: 700px) {
		#leftArea.col-lg-9 {
			padding: 0 16px;
		}

		.config-row {
			flex-direction: column;
			align-items: flex-start;
			gap: 10px;
		}

		.config-maxcount-row {
			flex-direction: column;
			align-items: flex-start;
		}

		.config-maxcount-row input[type="text"] {
			width: 100%;
		}
	}
</style>

<?php if (!empty($json['success'])) { ?>
	<div class="message">
		<?php echo $json['success']; ?>
	</div>
<?php } ?>
<?php if (!empty($json['error'])) { ?>
	<div class="message">
		<?php echo $json['error']; ?>
	</div>
<?php } ?>

<!-- <div class="config-header">
		<h2>Config Manager</h2>
		<p>Toggle the site's live-race boxes on or off and set the max record count.</p>
	</div> -->
<!--
	<div class="submenu">
	  	<a href="admin/configManager.php">Config Manager</a>
	  	<div style="float:right;">
			<a style="float:left;" href="admin/dashboard.php">Dashboard</a>
			<a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
	   	</div>
	</div>
	-->

<div class="config-form-wrap">
	<form enctype="multipart/form-data" name="configimageForm" method="post" action="admin/configManager.php">

		<div class="config-row">
			<span class="config-row-label"><i class="fas fa-toggle-on"></i> ODDSBOX</span>
			<div class="config-toggle">
				<?php if ($oddsbox == "Y") { ?>
					<label class="on-label"><input type="radio" name="ODDSBOX" value="Y" checked="checked" /> ON</label>
					<label class="off-label"><input type="radio" name="ODDSBOX" value="N" /> OFF</label>
				<?php } else { ?>
					<label class="on-label"><input type="radio" name="ODDSBOX" value="Y" /> ON</label>
					<label class="off-label"><input type="radio" name="ODDSBOX" checked="checked" value="N" /> OFF</label>
				<?php } ?>
			</div>
		</div>

		<div class="config-row">
			<span class="config-row-label"><i class="fas fa-toggle-on"></i> LINKSBOX 1</span>
			<div class="config-toggle">
				<?php if ($linkbox1 == "Y") { ?>
					<label class="on-label"><input type="radio" name="LINKSBOX1" checked="checked" value="Y" /> ON</label>
					<label class="off-label"><input type="radio" name="LINKSBOX1" value="N" /> OFF</label>
				<?php } else { ?>
					<label class="on-label"><input type="radio" name="LINKSBOX1" value="Y" /> ON</label>
					<label class="off-label"><input type="radio" name="LINKSBOX1" value="N" checked="checked" /> OFF</label>
				<?php } ?>
			</div>
		</div>

		<div class="config-row">
			<span class="config-row-label"><i class="fas fa-toggle-on"></i> LINKSBOX 2</span>
			<div class="config-toggle">
				<?php if ($linkbox2 == "Y") { ?>
					<label class="on-label"><input type="radio" name="LINKSBOX2" checked="checked" value="Y" /> ON</label>
					<label class="off-label"><input type="radio" name="LINKSBOX2" value="N" /> OFF</label>
				<?php } else { ?>
					<label class="on-label"><input type="radio" name="LINKSBOX2" value="Y" /> ON</label>
					<label class="off-label"><input type="radio" name="LINKSBOX2" value="N" checked="checked" /> OFF</label>
				<?php } ?>
			</div>
		</div>

		<div class="config-row">
			<span class="config-row-label"><i class="fas fa-toggle-on"></i> LINKSBOX 3</span>
			<div class="config-toggle">
				<?php if ($linkbox3 == "Y") { ?>
					<label class="on-label"><input type="radio" name="LINKSBOX3" checked="checked" value="Y" /> ON</label>
					<label class="off-label"><input type="radio" name="LINKSBOX3" value="N" /> OFF</label>
				<?php } else { ?>
					<label class="on-label"><input type="radio" name="LINKSBOX3" value="Y" /> ON</label>
					<label class="off-label"><input type="radio" name="LINKSBOX3" value="N" checked="checked" /> OFF</label>
				<?php } ?>
			</div>
		</div>

		<div class="config-row">
			<span class="config-row-label"><i class="fas fa-toggle-on"></i> LINKSBOX 4</span>
			<div class="config-toggle">
				<?php if ($linkbox4 == "Y") { ?>
					<label class="on-label"><input type="radio" name="LINKSBOX4" checked="checked" value="Y" /> ON</label>
					<label class="off-label"><input type="radio" name="LINKSBOX4" value="N" /> OFF</label>
				<?php } else { ?>
					<label class="on-label"><input type="radio" name="LINKSBOX4" value="Y" /> ON</label>
					<label class="off-label"><input type="radio" name="LINKSBOX4" value="N" checked="checked" /> OFF</label>
				<?php } ?>
			</div>
		</div>

		<div class="config-row">
			<span class="config-row-label"><i class="fas fa-toggle-on"></i> FINALRESBOX</span>
			<div class="config-toggle">
				<?php if ($finalresbox == "Y") { ?>
					<label class="on-label"><input type="radio" name="FINALRESBOX" value="Y" checked="checked" /> ON</label>
					<label class="off-label"><input type="radio" name="FINALRESBOX" value="N" /> OFF</label>
				<?php } else { ?>
					<label class="on-label"><input type="radio" name="FINALRESBOX" value="Y" /> ON</label>
					<label class="off-label"><input type="radio" name="FINALRESBOX" value="N" checked="checked" /> OFF</label>
				<?php } ?>
			</div>
		</div>

		<div class="config-row config-maxcount-row">
			<span class="config-row-label"><i class="fas fa-hashtag"></i> MAXCOUNT</span>
			<input type="text" name="MAXCOUNT" value="<?php echo $max_count; ?>" />
		</div>

		<div class="config-form-actions">
			<input type="submit" name="submit" value="Save Changes" />
			<input type="hidden" name="q" value="save-data" />
			<input type="reset" name="reset" value="Clear" onclick="location.href='admin/configManager.php'" />
		</div>

	</form>
</div>
<?php
$design->closeDiv();
$design->writeLeftPanel();
$design->closeDiv();
$design->closeDiv();
$design->endPage();
//$design->pageClose();    
$design = NULL; // release object