<?php
//error_reporting(E_ALL);
//ini_set("display_errors", 1);

include_once('../bootstrap.php');

include_once('../lib/pagination.class.php');

require_once("../lib/users.class.php");

require_once("../lib/userchecks.php");

require_once("../lib/function_race_report.php");

require_once("../lib/race.class.php");

$q = getParameterString('q', '', $db);

session_start();

$uid = $_SESSION['uid'];

$userObj = new Users($db);

$msg = $secmsg = "";

$rObj = new Racedata($db);

if (isAdminlogin()) {

    // echo $q;exit;
    if ($_SESSION['bannerManager'] == "Y") { // check login
        //if (get_magic_quotes_gpc()) {
            function stripslashes_deep($value)
            {
                $value = is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
                return $value;
            }
            $_POST = array_map('stripslashes_deep', $_POST);
            $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
       // }

        $json = array();
        $json['success'] = '';
        $json['error'] = '';
        // all actions POST form submissions go here
        // echo '<pre>';print_r($_REQUEST);exit;
        if ($q == "delete-banner") {
            $bannerID = getParameterNumber('id', 0);
            $bannerDetails = $rObj->getBannerById($bannerID);
            try {
                unlink(DIR_UPLOAD . "/" . $bannerDetails);
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

            if (isset($_POST['submit']) && $_POST['q'] == 'youtube-upload') {
                $date = $_POST['selected_date'];
                $link = 'https://www.youtube.com/embed/' . $_POST['link'];
                // echo $link;
                // exit;
                $sql = "INSERT INTO `youtube_upload` SET `date` = '" . $date . "', `link` = '" . $link . "'";
                $rObj->insertBannerimage($sql);
                // if ($_POST['file_type'] == 1) {
                //     // echo "inn";
                //     // echo "<pre>";print_r($_FILES);exit;
                //     // Image Upload Logic
                //     if (isset($_FILES['file'])) {
                //         date_default_timezone_set("Asia/Kolkata");
                //         $file_upload_path = '../' . DIR_UPLOAD . '/';
                //         $current_date = date('Y-m-d');

                //         foreach ($_FILES['file']['name'] as $key => $value) {
                //             if ($_FILES['file']['error'][$key] == UPLOAD_ERR_OK) {
                //                 // Only process if there is no error
                //                 $timestamp = date('YmdHis') . rand(10, 10000);
                //                 $file = 'Image_' . $timestamp . '.jpg';
                //                 $file_titles = explode('.', $value);
                //                 $file_title = $file_titles[0];
                //                 if (isset($_POST['selected_date'])) {
                //                     $selected_date = $_POST['selected_date'];
                //                 }else{
                //                     $selected_date = '';
                //                 }

                //                 if ($file != '') {
                //                     $exist_path = $file_upload_path . $file;
                //                     if (file_exists($exist_path)) {
                //                         unlink($exist_path);
                //                     }
                //                 }

                //                 if (move_uploaded_file($_FILES['file']['tmp_name'][$key], $file_upload_path . $file)) {
                //                     $destFile = $file_upload_path . $file;
                //                     chmod($destFile, 0777);

                //                     $sql = "INSERT INTO `images_upload` SET `curr_date` = '" . $current_date . "', `enter_date` = '" . $selected_date . "', `path` = '" . $file . "', `type` = '1'";
                //                     $rObj->insertBannerimage($sql);
                //                 }
                //             }
                //         }

                //         $json['success'] = 'Images Uploaded';
                //         header("Location: image_upload.php");
                //     }
                // } elseif ($_POST['file_type'] == 2 && isset($_POST['video_url'])) {
                //     echo "inn2";
                //     // Video Upload Logic
                //     $file_type = $_POST['file_type'];
                //     if (isset($_POST['selected_date'])) {
                //         $selected_date = $_POST['selected_date'];
                //     }else{
                //         $selected_date = '';
                //     }

                //     $current_date = date('Y-m-d');

                //     foreach ($_POST['video_url'] as $key => $value) {
                //         $url = trim($value);
                //         if (!empty($url)) {
                //             $sql = "INSERT INTO `images_upload` SET `curr_date` = '" . $current_date . "', `enter_date` = '" . $selected_date . "', `path` = '" . $url . "', `type` = '" . $file_type . "'";
                //             $rObj->insertBannerimage($sql);
                //         }
                //     }

                $json['success'] = 'Youtube Videos Uploaded';
                header("Location: youtube_videos_upload.php");


            }
            echo "out";
            exit;


            // echo "inn2";exit;

            // ... (existing code)

        }

        // ... (existing code)

        // $banner_datas = $rObj->getbanner_datas();
        // $banner_datas_count = count($banner_datas) + 1;

        // echo '<pre>';
        // print_r($banner_datas);
        // exit;

    } else {
        $msg = "You do not have access to this page.";
    }

} else {
    $secmsg = "Please login to access this page";
}

$pageTitle = 'Image Upload';
// create a template object
$design = new Design();
$design->js = '
<script type="text/javascript" src="lib/ckeditor/ckeditor.js"></script>
<script type="text/javascript">
    function confirmDelete(bannerID) {
        if (confirm ("Are you sure ?")){
            location.href="admin/bannerManager.php?q=delete-banner&id="+bannerID;
        }
    }
</script>';
$design->css = '<style type="text/css">
  #title { color: #000000; font-size: 14px; margin: 10px; margin: auto; text-align: left; display:block; }</style>';

$design->jqueryJs = "";

$design->startPage("$pageTitle");
$design->writeLogoTickerMenu();
$design->openDiv("contentWrapper");
$design->openDiv("infoWrapper");
$design->openDiv("leftArea");
?>
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

<div class="submenu">
    <a href="admin/bannerManager.php">Banner Manager</a>
    <div style="float:right;">
        <a style="float:left;" href="admin/dashboard.php">Dashboard</a>
        <a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
    </div>
</div>
<br />
<?php
// ... (existing code)

?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" onsubmit="return validateForm()">
    <table class="contentTable">
        <col width="20%">
        <col width="80%">
        <!-- <tr>
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
        </tr>-->

        <tr>
            <th>Date</th>
            <td class="alignLeft">
                <input type="date" name="selected_date" />
            </td>
        </tr>

        <!-- Dynamic File Upload Inputs -->
        <tr>
            <th>Link Upload</th>
            <td class="alignLeft">
                <input type="text" name="link" placeholder="Enter Video Unique ID" />
            </td>
        </tr>


        <!-- Dynamic Video URL Inputs -->
        <!-- <tr id="text_input_row" style="display:none;">
            <th>Video URLs</th>
            <td class="alignLeft">
                <php for ($i = 1; $i <= 3; $i++) { ?>
                    <input type="text" name="video_url[]" placeholder="Enter video URL <php echo $i; ?>" />
                <php } ?>
            </td>
        </tr> -->

        <tr>
            <td colspan="2">
                <input type="submit" name="submit" value="Upload" />
                <input type="hidden" name="q" value="youtube-upload" />
                <input type="reset" name="reset" value="Clear"
                    onclick="location.href='admin/youtube_videos_upload.php'" />
            </td>
        </tr>
    </table>
</form>

<script>
    function validateForm() {
        // Check if the date is selected
        var selectedDate = document.getElementsByName('selected_date').value;
        if (selectedDate === '') {
            alert('Please select a date before submitting the form.');
            return false; // Prevent form submission
        }

        // Additional validation logic can be added here if needed

        return true; // Allow form submission
    }

    // function updateInputs() {
    //     var count = document.getElementByName('selected_date').value;
    //     var fileType = document.getElementsByName('link').value;
    //     // var textInputRow = document.getElementById('text_input_row');

    //     // for (var i = 1; i <= 3; i++) {
    //     //     var fileInputRow = document.getElementById('file_input_' + i);
    //     //     fileInputRow.style.display = (i <= count && fileType === '1') ? 'table-row' : 'none';
    //     // }

    //     // textInputRow.style.display = (fileType === '2') ? 'table-row' : 'none';

    //     // Update the number of text input fields based on the selected count
    //     // var textInputs = textInputRow.getElementsByTagName('input');
    //     // for (var i = 0; i < textInputs.length; i++) {
    //     //     textInputs[i].style.display = (i < count && fileType === '2') ? 'inline' : 'none';
    //     // }
    // }

    // // Call updateInputs on page load to set the initial state
    // window.onload = function() {
    //     updateInputs();
    // };
</script>



<?php
$design->closeDiv();
//$design->rightArea();  
//$design->closeDiv();
$design->endPage();
//$design->pageClose();    
$design = NULL; // release object