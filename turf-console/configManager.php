<?php
include_once('../bootstrap.php');
include_once('../lib/pagination.class.php');
require_once("../lib/users.class.php");
require_once("../lib/userchecks.php");
require_once("../lib/race.class.php");

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../rwitc_website_api/config/config.php';

use Aws\S3\S3Client;

// ======================================================
// NORMAL DAYS BUTTONS - helper functions
// ======================================================

// Fetch all button rows from the website database (returns false on failure)
function nd_fetch_rows($conn)
{
	try {
		$res = $conn->query("SELECT `id`, `btn_text`, `file_url`, `is_video`, `is_active` FROM `normal_day_buttons` ORDER BY `id` ASC");
		if ($res === false) {
			return false;
		}
		$rows = array();
		while ($r = $res->fetch_assoc()) {
			$rows[(int)$r['id']] = $r;
		}
		return $rows;
	} catch (Throwable $e) {
		return false;
	}
}

// Map a file extension to its content type
function nd_content_type($ext)
{
	$map = array(
		'pdf'  => 'application/pdf',
		'jpg'  => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'png'  => 'image/png',
		'webp' => 'image/webp',
		'mp4'  => 'video/mp4',
		'm4v'  => 'video/mp4',
		'webm' => 'video/webm',
		'mov'  => 'video/quicktime',
	);
	return isset($map[$ext]) ? $map[$ext] : 'application/octet-stream';
}

// Upload a file to the S3 folder uploads/normaldaysdoc/ and return its object URL
function nd_upload_to_s3($tmpName, $id, $origName, $ext)
{
	$base = pathinfo($origName, PATHINFO_FILENAME);
	$base = preg_replace('/[^A-Za-z0-9_-]+/', '_', $base);
	$base = trim(substr($base, 0, 60), '_');
	if ($base === '') {
		$base = 'file';
	}

	$key = 'uploads/normaldaysdoc/' . $id . '_' . date('YmdHis') . '_' . $base . '.' . $ext;

	$s3Client = new S3Client([
		'version'     => 'latest',
		'region'      => AWS_REGION,
		'credentials' => [
			'key'    => AWS_ACCESS_KEY_ID,
			'secret' => AWS_SECRET_ACCESS_KEY,
		],
	]);

	$result = $s3Client->putObject([
		'Bucket'             => AWS_BUCKET,
		'Key'                => $key,
		'SourceFile'         => $tmpName,
		'ContentType'        => nd_content_type($ext),
		'ContentDisposition' => 'inline',
	]);

	return $result['ObjectURL'];
}

// True when the stored URL points to a file uploaded to our S3 folder
function nd_is_s3_url($url)
{
	return ($url !== '' && strpos($url, '/uploads/normaldaysdoc/') !== false);
}

// Accept only well-formed http/https URLs that fit in the database column
function nd_is_valid_url($url)
{
	if (strlen($url) > 600) {
		return false;
	}
	if (!filter_var($url, FILTER_VALIDATE_URL)) {
		return false;
	}
	$scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
	return ($scheme === 'http' || $scheme === 'https');
}

// Decide whether an external URL points to a video (used only for the play icon)
function nd_url_is_video($url)
{
	$host = strtolower((string)parse_url($url, PHP_URL_HOST));
	$path = strtolower((string)parse_url($url, PHP_URL_PATH));
	$ext  = pathinfo($path, PATHINFO_EXTENSION);

	$videoHosts = array(
		'youtube.com',
		'www.youtube.com',
		'm.youtube.com',
		'youtu.be',
		'vimeo.com',
		'www.vimeo.com',
		'player.vimeo.com',
	);

	if (in_array($host, $videoHosts, true)) {
		return true;
	}
	return in_array($ext, array('mp4', 'm4v', 'webm', 'mov'), true);
}

$q = getParameterString('q', '', $db);

session_start();
$uid = $_SESSION['uid'];
$userObj = new Users($db);
$msg = $secmsg = "";
$rObj = new Racedata($db);
$nd_rows = array();
$nd_table_missing = false;

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

		// When the upload is larger than post_max_size, PHP empties both $_POST and $_FILES
		if (
			$_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES)
			&& !empty($_SERVER['CONTENT_LENGTH'])
		) {
			$json['error'] = 'File upload failed. The file size exceeds the server upload limit (upload_max_filesize / post_max_size).';
		}

		// ---------- existing config save ----------
		if (isset($_REQUEST['submit'])) {
			if ($q == "save-data") {
				$ODDSBOX = getParameterString('ODDSBOX', 'N', $db);
				$LINKSBOX1 = getParameterString('LINKSBOX1', 'N', $db);
				$LINKSBOX2 = getParameterString('LINKSBOX2', 'N', $db);
				$LINKSBOX3 = getParameterString('LINKSBOX3', 'N', $db);
				$LINKSBOX4 = getParameterString('LINKSBOX4', 'N', $db);
				$FINALRESBOX = getParameterString('FINALRESBOX', 'N', $db);

				$MAXCOUNT = getParameterString('MAXCOUNT', '', $db);

				// handle checkbox state
				$ODDSBOX = (strtolower($ODDSBOX) == "y") ? "Y" : "N";
				$LINKSBOX1 = (strtolower($LINKSBOX1) == "y") ? "Y" : "N";
				$LINKSBOX2 = (strtolower($LINKSBOX2) == "y") ? "Y" : "N";
				$LINKSBOX3 = (strtolower($LINKSBOX3) == "y") ? "Y" : "N";
				$LINKSBOX4 = (strtolower($LINKSBOX4) == "y") ? "Y" : "N";
				$FINALRESBOX = (strtolower($FINALRESBOX) == "y") ? "Y" : "N";

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

		// ---------- normal days buttons save ----------
		if (isset($_REQUEST['submit']) && $q == "save-normal-buttons") {

			if (!isset($conn) || !($conn instanceof mysqli)) {
				$json['error'] = 'Website database connection is unavailable ($conn). Please check rwitc_website_api/config/config.php.';
			} else {
				$cur = nd_fetch_rows($conn);

				if ($cur === false) {
					$json['error'] = 'The normal_day_buttons table was not found. Please execute the required SQL query first.';
				} else {
					$allowedDocs   = array('pdf', 'jpg', 'jpeg', 'png', 'webp');
					$allowedVideos = array('mp4', 'm4v', 'webm', 'mov');
					$allowedAll    = array_merge($allowedDocs, $allowedVideos);

					$errMsgs = array();

					foreach ($cur as $id => $old) {

						$text = isset($_POST['btn_text'][$id]) ? trim($_POST['btn_text'][$id]) : '';
						if ($text === '') {
							$text = $old['btn_text'];
						}
						$text = mb_substr($text, 0, 150);

						$active = (isset($_POST['btn_active'][$id]) && strtolower($_POST['btn_active'][$id]) === 'y') ? 'Y' : 'N';

						// Keep the current file/link unless a new one is provided below
						$fileUrl = $old['file_url'];
						$isVideo = $old['is_video'];

						// The admin picks ONE content type per button: "file" or "link"
						$type = (isset($_POST['btn_type'][$id]) && $_POST['btn_type'][$id] === 'link') ? 'link' : 'file';

						$linkInput = isset($_POST['btn_link'][$id]) ? trim($_POST['btn_link'][$id]) : '';
						$hasFile   = isset($_FILES['btn_file']['name'][$id]) && $_FILES['btn_file']['name'][$id] !== '';

						if ($type === 'file') {
							// FILE option: the link box is ignored completely
							if ($hasFile) {
								$upErr = $_FILES['btn_file']['error'][$id];

								if ($upErr !== UPLOAD_ERR_OK) {
									$errMsgs[] = 'Button ' . $id . ': File upload failed (Error Code: ' . $upErr . '). The file may exceed the allowed upload size.';
								} else {
									$origName = $_FILES['btn_file']['name'][$id];
									$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

									if (!in_array($ext, $allowedAll, true)) {
										$errMsgs[] = 'Button ' . $id . ': This file type is not supported. Allowed formats are PDF, JPG, JPEG, PNG, WEBP, MP4, M4V, WEBM, and MOV.';
									} else {
										try {
											$newUrl = nd_upload_to_s3($_FILES['btn_file']['tmp_name'][$id], $id, $origName, $ext);
											if ($newUrl != '') {
												$fileUrl = $newUrl;
												$isVideo = in_array($ext, $allowedVideos, true) ? 'Y' : 'N';
											} else {
												$errMsgs[] = 'Button ' . $id . ': Unable to retrieve the file URL from Amazon S3.';
											}
										} catch (Throwable $e) {
											$errMsgs[] = 'Button ' . $id . ': Amazon S3 upload failed - ' . $e->getMessage();
										}
									}
								}
							} elseif ($old['file_url'] !== '' && !nd_is_s3_url($old['file_url'])) {
								$errMsgs[] = 'Button ' . $id . ': The File option was selected but no file was chosen. The previous link was kept.';
							}
						} else {
							// LINK option: any chosen file is ignored completely
							if ($linkInput !== '') {
								if (!nd_is_valid_url($linkInput)) {
									$errMsgs[] = 'Button ' . $id . ': The link is not valid. It must start with http:// or https:// and be at most 600 characters long.';
								} else {
									$fileUrl = $linkInput;
									$isVideo = nd_url_is_video($linkInput) ? 'Y' : 'N';
								}
							} elseif (nd_is_s3_url($old['file_url'])) {
								$errMsgs[] = 'Button ' . $id . ': The Link option was selected but no link was entered. The previous file was kept.';
							}
						}

						// DB update
						try {
							$stmt = $conn->prepare("UPDATE `normal_day_buttons` SET `btn_text` = ?, `is_active` = ?, `file_url` = ?, `is_video` = ? WHERE `id` = ?");
							$stmt->bind_param("ssssi", $text, $active, $fileUrl, $isVideo, $id);
							$stmt->execute();
							$stmt->close();
						} catch (Throwable $e) {
							$errMsgs[] = 'Button ' . $id . ': Database save failed - ' . $e->getMessage();
						}

						if ($active === 'Y' && $fileUrl === '') {
							$errMsgs[] = 'Button ' . $id . ' (' . $text . ') is enabled, but no file or link has been added. Therefore, it will not be displayed on the website.';
						}
					}

					if (empty($errMsgs)) {
						$_SESSION['normal_btn_msg'] = 'Normal Days Buttons Updated';
					} else {
						$_SESSION['normal_btn_msg'] = 'Normal Days Buttons have been saved with some issues. Please review the messages below.';
						$_SESSION['normal_btn_err'] = implode('<br>', array_map(function ($m) {
							return htmlspecialchars($m, ENT_QUOTES, 'UTF-8');
						}, $errMsgs));
					}
					header("Location: configManager.php");
					exit;
				}
			}
		}

		$oddsbox = $rObj->getconfig_datas(1);
		$linkbox1 = $rObj->getconfig_datas(2);
		$linkbox2 = $rObj->getconfig_datas(3);
		$linkbox3 = $rObj->getconfig_datas(4);
		$linkbox4 = $rObj->getconfig_datas(5);
		$finalresbox = $rObj->getconfig_datas(6);
		$max_count = $rObj->getconfig_datas(7);

		// Load normal days buttons for display
		if (isset($conn) && ($conn instanceof mysqli)) {
			$fetched = nd_fetch_rows($conn);
			if ($fetched === false) {
				$nd_table_missing = true;
			} else {
				$nd_rows = $fetched;
			}
		}
	} else {
		$msg = "You do not have access to this page.";
	}
} else {
	$secmsg = "Please login to access this page";
}

if (!isset($json)) {
	$json = array('success' => '', 'error' => '');
}
if (isset($_SESSION['normal_btn_msg'])) {
	$json['success'] = $_SESSION['normal_btn_msg'];
	unset($_SESSION['normal_btn_msg']);
}
$nd_flash_error = '';
if (isset($_SESSION['normal_btn_err'])) {
	$nd_flash_error = $_SESSION['normal_btn_err']; // already escaped
	unset($_SESSION['normal_btn_err']);
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

	.message.message-error {
		background: #fdecea;
		border-color: #f0b8b2;
		color: #a52a1f;
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

	html,
	body {
		scrollbar-width: none;
		-ms-overflow-style: none;
	}

	html::-webkit-scrollbar,
	body::-webkit-scrollbar {
		display: none;
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

	/* ===== normal days buttons ===== */
	.nd-section-title {
		font-size: 16px;
		font-weight: 700;
		color: #0f5c33;
		margin: 32px 0 6px;
		display: flex;
		align-items: center;
		gap: 8px;
	}

	.nd-section-sub {
		font-size: 13px;
		color: #7a8c84;
		margin: 0 0 14px;
	}

	.nd-wrap {
		background: #fff;
		border: 1px solid #e2e6e4;
		border-radius: 12px;
		padding: 8px 20px 12px;
		box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
	}

	.nd-card {
		padding: 18px 4px;
		border-bottom: 1px solid #eef0ee;
	}

	.nd-card:last-of-type {
		border-bottom: none;
	}

	.nd-card-head {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 12px;
		margin-bottom: 12px;
		flex-wrap: wrap;
	}

	.nd-card-title {
		font-size: 14.5px;
		font-weight: 700;
		color: #2b332f;
	}

	.nd-card label.nd-label {
		display: block;
		font-size: 12px;
		font-weight: 700;
		color: #7a8c84;
		text-transform: uppercase;
		letter-spacing: .3px;
		margin: 10px 0 4px;
	}

	.nd-card input[type="text"] {
		width: 100%;
		box-sizing: border-box;
		border: 1px solid #e2e6e4;
		border-radius: 6px;
		padding: 9px 12px;
		font-size: 14px;
	}

	.nd-card input[type="file"] {
		width: 100%;
		box-sizing: border-box;
		border: 1px solid #e2e6e4;
		border-radius: 6px;
		padding: 8px 10px;
		font-size: 14px;
		background: #f7faf8;
	}

	.nd-current {
		font-size: 13px;
		color: #2b332f;
		margin-bottom: 6px;
		word-break: break-all;
	}

	.nd-current a {
		color: #0f5c33;
		font-weight: 600;
	}

	.nd-badge {
		display: inline-block;
		font-size: 11px;
		font-weight: 700;
		padding: 2px 8px;
		border-radius: 999px;
		background: #e6f4ec;
		color: #0f5c33;
		margin-right: 6px;
	}

	.nd-nofile {
		font-size: 13px;
		color: #c0392b;
		margin-bottom: 6px;
	}

	.nd-hint {
		font-size: 12px;
		color: #7a8c84;
		margin-top: 4px;
	}

	/* File / Link option selector */
	.nd-type {
		display: flex;
		gap: 10px;
		flex-wrap: wrap;
		margin: 4px 0 10px;
	}

	.nd-type label {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		padding: 7px 14px;
		border: 1px solid #e2e6e4;
		border-radius: 999px;
		font-size: 13px;
		font-weight: 600;
		color: #2b332f;
		cursor: pointer;
		background: #fff;
	}

	.nd-type label:has(input:checked) {
		background: #0f5c33;
		border-color: #0f5c33;
		color: #fff;
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
	<div class="message" id="successMsg">
		<?php echo $json['success']; ?>
	</div>
<?php } ?>
<?php if (!empty($json['error'])) { ?>
	<div class="message message-error">
		<?php echo $json['error']; ?>
	</div>
<?php } ?>
<?php if (!empty($nd_flash_error)) { ?>
	<div class="message message-error">
		<?php echo $nd_flash_error; ?>
	</div>
<?php } ?>

<div class="config-form-wrap">
	<form enctype="multipart/form-data" name="configimageForm" method="post" action="turf-console/configManager.php">

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
			<input type="reset" name="reset" value="Clear" onclick="location.href='turf-console/configManager.php'" />
		</div>

	</form>
</div>

<!-- ================= NORMAL DAYS BUTTONS ================= -->
<div class="nd-section-title"><i class="fas fa-bell"></i> Normal Days Buttons</div>
<p class="nd-section-sub">These buttons are displayed on the website on non-race days. For each button, choose either a File (PDF / Image / Video) or a Link (YouTube or any website). Only one of them can be used at a time. A button will only be displayed on the website if a file or a link has been added.</p>

<div class="nd-wrap">
	<?php if ($nd_table_missing) { ?>
		<div class="message message-error" style="margin-top:12px;">The <b>normal_day_buttons</b> table was not found. Please execute the required SQL query first.</div>
	<?php } elseif (empty($nd_rows)) { ?>
		<div class="nd-nofile" style="padding:16px 4px;">No button records were found.</div>
	<?php } else { ?>
		<form enctype="multipart/form-data" name="normalButtonsForm" method="post" action="turf-console/configManager.php">

			<?php foreach ($nd_rows as $nd_id => $nd) { ?>
				<?php
				$nd_id    = (int)$nd_id;
				$nd_name  = htmlspecialchars($nd['btn_text'], ENT_QUOTES, 'UTF-8');
				$nd_url   = $nd['file_url'];
				$nd_is_s3 = nd_is_s3_url($nd_url);
				$nd_file  = $nd_is_s3 ? basename(parse_url($nd_url, PHP_URL_PATH)) : $nd_url;

				// The selected option follows what is currently stored
				$nd_type = ($nd_url !== '' && !$nd_is_s3) ? 'link' : 'file';

				if ($nd_is_s3) {
					$nd_badge = ($nd['is_video'] == 'Y') ? 'VIDEO' : 'FILE';
				} else {
					$nd_badge = ($nd['is_video'] == 'Y') ? 'VIDEO LINK' : 'LINK';
				}
				?>
				<div class="nd-card">
					<div class="nd-card-head">
						<span class="nd-card-title">Button <?php echo $nd_id; ?></span>
						<div class="config-toggle">
							<?php if ($nd['is_active'] == "Y") { ?>
								<label class="on-label"><input type="radio" name="btn_active[<?php echo $nd_id; ?>]" value="Y" checked="checked" /> ON</label>
								<label class="off-label"><input type="radio" name="btn_active[<?php echo $nd_id; ?>]" value="N" /> OFF</label>
							<?php } else { ?>
								<label class="on-label"><input type="radio" name="btn_active[<?php echo $nd_id; ?>]" value="Y" /> ON</label>
								<label class="off-label"><input type="radio" name="btn_active[<?php echo $nd_id; ?>]" value="N" checked="checked" /> OFF</label>
							<?php } ?>
						</div>
					</div>

					<label class="nd-label">Button Name</label>
					<input type="text" name="btn_text[<?php echo $nd_id; ?>]" value="<?php echo $nd_name; ?>" maxlength="150" />

					<label class="nd-label">Current File / Link</label>
					<?php if ($nd_url !== '') { ?>
						<div class="nd-current">
							<span class="nd-badge"><?php echo $nd_badge; ?></span>
							<a href="<?php echo htmlspecialchars($nd_url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($nd_file, ENT_QUOTES, 'UTF-8'); ?></a>
						</div>
					<?php } else { ?>
						<div class="nd-nofile">No file or link has been added yet.</div>
					<?php } ?>

					<label class="nd-label">Button Content</label>
					<div class="nd-type">
						<label>
							<input type="radio" name="btn_type[<?php echo $nd_id; ?>]" value="file" <?php echo $nd_type === 'file' ? 'checked="checked"' : ''; ?> onclick="ndSwitchType(<?php echo $nd_id; ?>, 'file')" />
							File
						</label>
						<label>
							<input type="radio" name="btn_type[<?php echo $nd_id; ?>]" value="link" <?php echo $nd_type === 'link' ? 'checked="checked"' : ''; ?> onclick="ndSwitchType(<?php echo $nd_id; ?>, 'link')" />
							Link
						</label>
					</div>

					<div id="nd-file-box-<?php echo $nd_id; ?>" style="<?php echo $nd_type === 'file' ? '' : 'display:none;'; ?>">
						<input type="file" id="nd-file-input-<?php echo $nd_id; ?>" name="btn_file[<?php echo $nd_id; ?>]" accept=".pdf,.jpg,.jpeg,.png,.webp,.mp4,.m4v,.webm,.mov" />
						<div class="nd-hint">Choose a new file to replace the current one. Leave empty to keep the current file.</div>
					</div>

					<div id="nd-link-box-<?php echo $nd_id; ?>" style="<?php echo $nd_type === 'link' ? '' : 'display:none;'; ?>">
						<input type="text" id="nd-link-input-<?php echo $nd_id; ?>" name="btn_link[<?php echo $nd_id; ?>]" value="" placeholder="https://..." maxlength="600" />
						<div class="nd-hint">Paste a new link to replace the current one. It must start with http:// or https://. Leave empty to keep the current link.</div>
					</div>
				</div>
			<?php } ?>

			<div class="config-form-actions">
				<input type="submit" name="submit" value="Save Buttons" />
				<input type="hidden" name="q" value="save-normal-buttons" />
			</div>

		</form>
	<?php } ?>
</div>

<script>
	// Show only the selected content option (File or Link) and clear the other one
	function ndSwitchType(id, type) {
		var fileBox = document.getElementById('nd-file-box-' + id);
		var linkBox = document.getElementById('nd-link-box-' + id);
		var fileInput = document.getElementById('nd-file-input-' + id);
		var linkInput = document.getElementById('nd-link-input-' + id);

		if (type === 'link') {
			fileBox.style.display = 'none';
			linkBox.style.display = 'block';
			if (fileInput) {
				fileInput.value = '';
			}
		} else {
			linkBox.style.display = 'none';
			fileBox.style.display = 'block';
			if (linkInput) {
				linkInput.value = '';
			}
		}
	}

	setTimeout(function() {
		var el = document.getElementById('successMsg');
		if (el) el.style.display = 'none';
	}, 4000);
</script>

<?php
$design->closeDiv();
$design->writeLeftPanel();
$design->closeDiv();
$design->closeDiv();
$design->endPage();
//$design->pageClose();    
$design = NULL; // release object