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

    // echo $q;exit;
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
        // echo '<pre>';print_r($_REQUEST);exit;
        if ($q=="delete-banner") {
            $bannerID=getParameterNumber('id',0);                
            $bannerDetails = $rObj->getBannerById($bannerID); 
            try {
               unlink(DIR_UPLOAD."/".$bannerDetails);   
               $rObj->deleteBannerByID($bannerID);
            } catch (Exception $err) {
               $msg = $err->getMessage();        
            }
        }
        // ... (existing code)

            // echo "inn";
        if (isset($_REQUEST['submit'])) {
            // echo "inn";exit;
            // echo "<pre>";print_r($_POST);exit;
            //$body = getParameterString('file','',$db);
            if ($q == "save-data") {
                foreach ($_POST['banner_datas'] as $bkey => $value) {
                    $id = getParameterString_custom($value['id'], '', $db);
                    $title = getParameterString_custom($value['title'], '', $db);
                    $link = getParameterString_custom($value['link'], '', $db);
                    $sort_order = getParameterString_custom($value['sort_order'], '', $db);
                    try {
                        $rObj->updateBanner($id, $title, $link, $sort_order);
                    } catch (Exception $err) {
                        echo $err->getMessage();
                    }
                }
                $json['success'] = 'Image Data Updated';
            }

            // ... (existing code)

        if (isset($_POST['submit']) && $_POST['q'] == 'image-upload') {
            if ($_POST['file_type'] == 1) {
                // echo "inn";
                // echo "<pre>";print_r($_FILES);exit;
                // Image Upload Logic
                if (isset($_FILES['file'])) {
                    date_default_timezone_set("Asia/Kolkata");
                    $file_upload_path = '../' . DIR_UPLOAD . '/';
                    $current_date = date('Y-m-d');

                    foreach ($_FILES['file']['name'] as $key => $value) {
                        if ($_FILES['file']['error'][$key] == UPLOAD_ERR_OK) {
                            // Only process if there is no error
                            $timestamp = date('YmdHis') . rand(10, 10000);
                            $file = 'Image_' . $timestamp . '.jpg';
                            $file_titles = explode('.', $value);
                            $file_title = $file_titles[0];
                            if (isset($_POST['selected_date'])) {
                                $selected_date = $_POST['selected_date'];
                            }else{
                                $selected_date = '';
                            }

                            if ($file != '') {
                                $exist_path = $file_upload_path . $file;
                                if (file_exists($exist_path)) {
                                    unlink($exist_path);
                                }
                            }

                            if (move_uploaded_file($_FILES['file']['tmp_name'][$key], $file_upload_path . $file)) {
                                $destFile = $file_upload_path . $file;
                                chmod($destFile, 0777);

                                $sql = "INSERT INTO `images_upload` SET `curr_date` = '" . $current_date . "', `enter_date` = '" . $selected_date . "', `path` = '" . $file . "', `type` = '1'";
                                $rObj->insertBannerimage($sql);
                            }
                        }
                    }

                    $json['success'] = 'Images Uploaded';
                    header("Location: image_upload.php");
                }
            } elseif ($_POST['file_type'] == 2 && isset($_POST['video_url'])) {
                echo "inn2";
                // Video Upload Logic
                $file_type = $_POST['file_type'];
                if (isset($_POST['selected_date'])) {
                    $selected_date = $_POST['selected_date'];
                }else{
                    $selected_date = '';
                }

                $current_date = date('Y-m-d');

                foreach ($_POST['video_url'] as $key => $value) {
                    $url = trim($value);
                    if (!empty($url)) {
                        $sql = "INSERT INTO `images_upload` SET `curr_date` = '" . $current_date . "', `enter_date` = '" . $selected_date . "', `path` = '" . $url . "', `type` = '" . $file_type . "'";
                        $rObj->insertBannerimage($sql);
                    }
                }

                $json['success'] = 'Videos Uploaded';
                header("Location: image_upload.php");
            }

        }
        echo "out";exit;


            // echo "inn2";exit;

            // ... (existing code)

        }

        // ... (existing code)

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

$pageTitle ='Image Upload';        
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
</script>';
$design->css ='<style type="text/css">
  #title { color: #000000; font-size: 14px; margin: 10px; margin: auto; text-align: left; display:block; }</style>';

$design->jqueryJs = ""; 

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
    flex-direction: row;
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

.iu-title { font-size: 18px; font-weight: 700; color: #2b332f; margin: 0 0 18px; }

.iu-form-wrap {
    background: #fff;
    border: 1px solid #e2e6e4;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}
.iu-form-table { width: 100%; border-collapse: collapse; }
.iu-form-table th { text-align: left; padding: 10px 8px; color: #2b332f; vertical-align: top; width: 20%; font-weight: 600; font-size: 13.5px; }
.iu-form-table td { padding: 10px 8px; }
.iu-form-table input[type="text"],
.iu-form-table input[type="date"],
.iu-form-table input[type="file"],
.iu-form-table select {
    border: 1px solid #e2e6e4; border-radius: 6px; padding: 8px 10px; font-size: 14px;
    width: 100%; max-width: 100%; box-sizing: border-box;
}
.iu-form-table input[type="text"] { margin-bottom: 8px; }
.iu-form-table input[type="submit"] { background: #0f5c33; color: #fff; border: none; padding: 9px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; margin-right: 8px; }
.iu-form-table input[type="submit"]:hover { background: #0b3d24; }
.iu-form-table input[type="reset"] { background: #fff; color: #2b332f; border: 1px solid #e2e6e4; padding: 9px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; }
.iu-form-table input[type="reset"]:hover { background: #f5f4ee; }

/* ===== responsive ===== */
@media (max-width: 900px) {
    #infoWrapper.col-lg-12 { flex-direction: column; margin: 16px auto; }
    #leftArea.col-lg-9 { flex: 1 1 100%; max-width: 100%; padding: 28px 24px; }
}
@media (max-width: 700px) {
    #leftArea.col-lg-9 { padding: 0 16px; }
}
@media (max-width: 520px) {
    .iu-form-table, .iu-form-table tbody, .iu-form-table tr, .iu-form-table th, .iu-form-table td {
        display: block; width: 100% !important;
    }
    .iu-form-table th { padding-bottom: 2px; }
    .iu-form-table td { padding-top: 0; padding-bottom: 14px; }
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

    <!--
    <div class="submenu">
        <a href="admin/bannerManager.php">Banner Manager</a>
        <div style="float:right;">
            <a style="float:left;" href="admin/dashboard.php">Dashboard</a>
            <a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
        </div>
    </div>
    -->
    <div class="iu-title"><i class="fas fa-cloud-arrow-up"></i> Image Upload</div>
   <?php
// ... (existing code)

?>
<div class="iu-form-wrap">
<form enctype="multipart/form-data" name="bannerimageForm" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" onsubmit="return validateForm()">
    <table class="iu-form-table">
        <col width="20%">
        <col width="80%">
        <tr>
            <th>Number of Images and Video</th>
            <td class="alignLeft">
                <select name="image_count" id="image_count" onchange="updateInputs()">
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                </select>
            </td>
        </tr>
        <tr>
            <th>File Type</th>
            <td class="alignLeft">
                <select name="file_type" onchange="updateInputs()">
                    <option value="1">Image</option>
                    <option value="2">Video</option>
                </select>
            </td>
        </tr>

        <tr>
            <th>Date</th>
            <td class="alignLeft">
                <input type="date" name="selected_date" />
            </td>
        </tr>

        <!-- Dynamic File Upload Inputs -->
        <?php for ($i = 1; $i <= 3; $i++) { ?>
            <tr id="file_input_<?php echo $i; ?>" style="<?php echo ($i == 1) ? '' : 'display:none;'; ?>">
                <th>File Upload</th>
                <td class="alignLeft">
                    <input type="file" name="file[]" accept="video/mp4,video/x-m4v,video/*,image/*" multiple />
                </td>
            </tr>
        <?php } ?>


        <!-- Dynamic Video URL Inputs -->
        <tr id="text_input_row" style="display:none;">
            <th>Video URLs</th>
            <td class="alignLeft">
                <?php for ($i = 1; $i <= 3; $i++) { ?>
                    <input type="text" name="video_url[]" placeholder="Enter video URL <?php echo $i; ?>" />
                <?php } ?>
            </td>
        </tr>

        <tr>
            <td colspan="2">
                <input type="submit" name="submit" value="Upload" />
                <input type="hidden" name="q" value="image-upload" />
                <input type="reset" name="reset" value="Clear" onclick="location.href='admin/image_upload.php'" />
            </td>
        </tr>
    </table>
</form>
</div>

<script>
function validateForm() {
    // Check if the date is selected
    var selectedDate = document.getElementsByName('selected_date')[0].value;
    if (selectedDate === '') {
        alert('Please select a date before submitting the form.');
        return false; // Prevent form submission
    }

    // Additional validation logic can be added here if needed

    return true; // Allow form submission
}
function updateInputs() {
    var count = document.getElementById('image_count').value;
    var fileType = document.getElementsByName('file_type')[0].value;
    var textInputRow = document.getElementById('text_input_row');

    for (var i = 1; i <= 3; i++) {
        var fileInputRow = document.getElementById('file_input_' + i);
        fileInputRow.style.display = (i <= count && fileType === '1') ? 'table-row' : 'none';
    }

    textInputRow.style.display = (fileType === '2') ? 'table-row' : 'none';

    // Update the number of text input fields based on the selected count
    var textInputs = textInputRow.getElementsByTagName('input');
    for (var i = 0; i < textInputs.length; i++) {
        textInputs[i].style.display = (i < count && fileType === '2') ? 'inline' : 'none';
    }
}

// Call updateInputs on page load to set the initial state
window.onload = function() {
    updateInputs();
};
</script>



<?php     
  $design->writeLeftPanel();              
  $design->closeDiv();
  $design->closeDiv();
  $design->closeDiv();
  $design->endPage();
  //$design->pageClose();    
$design = NULL; // release object