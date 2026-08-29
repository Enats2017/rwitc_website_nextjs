<?php
include_once('../bootstrap.php');
require_once('../lib/trackwork.class.php');
require_once("../lib/users.class.php");
require_once("../lib/userchecks.php");
require_once('../lib/pagination.class.php');


$q = getParameterString('q', '', $db);
$pageno = getParameterNumber('pageno', 1);

$trackworkObj = new Trackwork($db);
session_start();
if (isset($_COOKIE['uid'])) {
    $uid = $_COOKIE['uid'];
} else {
    $uid = 0;
}
$userObj = new Users($db);

$msg = $secmsg = "";


if (isAdminlogin()) {
    if (($_SESSION['role'] == "ADMIN" || ($_SESSION['role'] == "BACK_OFFICE"))) { // check login
        $trackworkDetails = [
            'trackwork_date' => '',
            'trackwork'      => '',
            'published'      => 'Y'
        ];

        //if (get_magic_quotes_gpc()) {
        function stripslashes_deep($value)
        {
            $value = is_array($value) ?
                array_map('stripslashes_deep', $value) :
                stripslashes($value);

            return $value;
        }

        $_POST = array_map('stripslashes_deep', $_POST);
        //    $_GET = array_map('stripslashes_deep', $_GET);
        //  $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
        $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
        //}


        // all actions POST form submissions go here
        if (isset($_REQUEST['submit'])) {

            $trackworkDate = getParameterString('trackwork_date', '', $db);
            $trackwork = getParameterString('trackwork', '', $db);
            $published = getParameterString('published', 'N', $db);


            // handle checkbox state
            if (strtolower($published) == "on") {
                $published = "Y";
            }
            // save new article
            if ($q == "add-trackwork") {
                try {
                    $trackworkID = $trackworkObj->insertTrackwork($trackwork, $trackworkDate, $published);
                } catch (Exception $err) {
                    echo $err->getMessage();
                }
            }

            //update new article 
            if ($q == "update-trackwork") {
                $trackworkID = getParameterNumber('id', 0);
                try {
                    $rowsAffected = $trackworkObj->updateTrackwork($trackworkID, $trackwork, $trackworkDate, $published);
                } catch (Exception $err) {
                    echo $err->getMessage();
                }
            }
        }

        if ($q == "edit-trackwork") {
            $trackworkID = getParameterNumber('id', 0);
            try {
                $trackworkDetails = $trackworkObj->getTrackworkByID($trackworkID);
            } catch (Exception $err) {
                $msg = $err->getMessage();
                echo $msg;
            }
        }
        if ($q == "delete-trackwork") {
            $trackworkID = getParameterNumber('id', 0);
            try {
                $trackworkObj->deleteTrackwork($trackworkID);
                $msg = "Trackwork Deleted";
                // clear action
                $q = "";
            } catch (Exception $err) {
                $msg = $err->getMessage();
                echo $msg;
            }
        }
        // fetch all articles
        $allTrackworkFull = $trackworkObj->getAllTrackwork();
        $totalTrackwork = count($allTrackworkFull);
        $paging = new Pagination($pageno, 6, $totalTrackwork);
        $allTrackwork = array_slice($allTrackworkFull, ($pageno - 1) * 8, 8 );   
    } else {
        $msg = "You do not have access to this page.";
    }
} else {
    $secmsg = "Please login to access this page";
}

$pageTitle = 'Trackwork Manager';
// create a template object
$design = new Design();

$design->js = '
<script type="text/javascript" src="https://cdn.ckeditor.com/4.8.0/full-all/ckeditor.js"></script>
<link type="text/css" href="css/jquery.ui.all.css" rel="stylesheet" />    
<script type="text/javascript" src="js/jquery.ui.core.min.js"></script>    
<script type="text/javascript" src="js/jquery.ui.datepicker.min.js"></script>
     
    
<script type="text/javascript">
     function confirmDelete(trackworkID) {       
       if (confirm(\'Are you Sure?\')) {          
            location.href = "admin/trackworkManager.php?id="+trackworkID+"&q=delete-trackwork";
        }    
    }
       
</script>
';
$design->css = '
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

.trackwork-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.add-trackwork-btn { display: inline-flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #1a7a45; color: #0f5c33; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; }
.add-trackwork-btn:hover { background: #e6f4ec; }
.header-links { display: flex; align-items: center; gap: 16px; }
.header-links a { color: #0f5c33; text-decoration: none; font-weight: 600; font-size: 14px; }
.header-links a:hover { text-decoration: underline; }

.trackwork-form-wrap { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; padding: 20px; margin-bottom: 24px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); }
.trackwork-form-wrap .contentTable { width: 100%; }
.trackwork-form-wrap .contentTable th { text-align: left; padding: 10px 8px; color: #2b332f; }
.trackwork-form-wrap .contentTable td { padding: 10px 8px; }
.trackwork-form-wrap input[type="text"] {
  width: 100%; border: 1px solid #e2e6e4; border-radius: 6px; padding: 8px 10px; font-size: 14px; color: #2b332f; box-sizing: border-box; font-family: inherit;
}
.trackwork-form-wrap input[type="submit"], .trackwork-form-wrap input[type="reset"] { background: #0f5c33; color: #fff; border: none; padding: 9px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; margin-right: 8px; }
.trackwork-form-wrap input[type="reset"] { background: #fff; color: #2b332f; border: 1px solid #e2e6e4; }
.trackwork-form-wrap input[type="checkbox"] { width: 17px; height: 17px; accent-color: #0f5c33; cursor: pointer; }

.trackwork-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 24px; }
.trackwork-card { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; padding: 18px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); display: flex; flex-direction: column; }
.trackwork-card-top { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
.trackwork-icon { width: 38px; height: 38px; border-radius: 50%; background: #e6f4ec; color: #0f5c33; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0; }
.trackwork-date { font-size: 14.5px; font-weight: 600; color: #2b332f; }
.trackwork-tags { display: flex; gap: 10px; margin-bottom: 14px; flex-wrap: wrap; }
.trackwork-tags .tag { font-size: 12.5px; color: #2b332f; background: #f5f4ee; border: 1px solid #e2e6e4; padding: 6px 10px; border-radius: 6px; display: flex; align-items: center; gap: 6px; }
.trackwork-tags .tag b { padding: 1px 8px; border-radius: 4px; font-size: 12px; font-weight: 700; }
.trackwork-tags .tag b.yes { background: #0f5c33; color: #fff; }
.trackwork-tags .tag b.no { background: #c0392b; color: #fff; }
.trackwork-actions { display: flex; justify-content: flex-end; gap: 16px; border-top: 1px solid #eef0ee; padding-top: 12px; margin-top: auto; }
.trackwork-actions a { font-size: 13.5px; text-decoration: none; display: flex; align-items: center; gap: 6px; font-weight: 500; }
.trackwork-actions a:nth-child(1) { color: #0f5c33; }
.trackwork-actions a:nth-child(2) { color: #c0392b; }
.trackwork-empty { padding: 20px; text-align: center; color: #7a8c84; font-size: 14px; background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; }

html, body { scrollbar-width: none; -ms-overflow-style: none; }
html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }

@media (max-width: 700px) {
    .trackwork-grid { grid-template-columns: 1fr; }
    #leftArea.col-lg-9 { padding: 0 16px; }
    .trackwork-header { flex-direction: column; align-items: flex-start; }
}
</style>
';
$design->jqueryJs = "
jQuery.browser = {};
    (function () {
        jQuery.browser.msie = false;
        jQuery.browser.version = 0;
        if (navigator.userAgent.match(/MSIE ([0-9]+)\./)) {
            jQuery.browser.msie = true;
            jQuery.browser.version = RegExp.$1;
        }
    })();
$(\"#trackwork_date\").datepicker({
            showOn: 'button',
            buttonImage: 'images/calendar.gif',
            buttonImageOnly: true,
            dateFormat : 'yy-mm-dd'
        });
";
$design->startPage("$pageTitle");
$design->writeLogoTickerMenu();
$design->openDiv("contentWrapper");
$design->openDiv("infoWrapper", "col-lg-12");
$design->openDiv("leftArea", 'col-lg-9');
?>
<?php if (!empty($msg)) { ?>
    <div class="message">
        <?php echo $msg; ?>
    </div>
<?php } ?>
<?php if (!empty($secmsg)) { ?>
    <div class="message">
        <?php echo $secmsg; ?>
    </div>
<?php } ?>
<?php if (empty($msg) && empty($secmsg)) { ?>
    <div class="trackwork-header">
        <a class="add-trackwork-btn" href="admin/trackworkManager.php?q=new-trackwork"><i class="fas fa-plus"></i> Add New Trackwork</a>
        <div class="header-links">
            <!-- <a href="admin/dashboard.php">Dashboard</a>
            <a href="admin/adminlogin.php?q=logout">Logout</a> -->
        </div>
    </div>
    <?php if ($q == "new-trackwork" || $q == "edit-trackwork") { ?>
        <div class="trackwork-form-wrap">
            <form name="trackworkForm" method="post" action="admin/trackworkManager.php">
                <table class="contentTable">
                    <col width="20%">
                    <col width="80%">
                    <tr>
                        <th>TrackworkDate</th>
                        <?php
                        $trackworkDate = '';
                        if ($q == "edit-trackwork") {
                            $trackworkDate = date("Y-m-d", strtotime($trackworkDetails['trackwork_date']));
                        }
                        ?>
                        <td class="alignLeft"><input type="text" name="trackwork_date" id='trackwork_date' value="<?php echo   $trackworkDate; ?>" /></td>
                    </tr>
                    <tr>
                        <th>TrackWork</th>
                        <td class="alignLeft"><textarea name="trackwork" id="trackwork"><?php echo $trackworkDetails['trackwork']; ?></textarea></td>
                    </tr>
                    <tr>
                    <tr>
                        <th>Publish</th>
                        <td class="alignLeft">
                            <?php
                            $checked = "checked=\"checked\"";
                            if ($trackworkDetails['published'] == "N") {
                                $checked = "";
                            }
                            ?>
                            <input type="checkbox" name="published" id='published' <?php echo $checked; ?> />
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <input type="submit" name="submit" value="Save" />
                            <input type="reset" name="reset" value="Clear" />
                            <?php if ($q == "new-trackwork") { ?>
                                <input type="hidden" name="q" value="add-trackwork" />
                            <?php } elseif ($q == "edit-trackwork") { ?>
                                <input type="hidden" name="q" value="update-trackwork" />
                                <input type="hidden" name="id" value="<?php echo $trackworkID; ?>" />
                            <?php  }   ?>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <script type="text/javascript">
            //<![CDATA[
            CKEDITOR.replace('trackwork', {
                fullPage: true,
                filebrowserBrowseUrl: '/lib/ckfinder/ckfinder.html',
                filebrowserImageBrowseUrl: '/lib/ckfinder/ckfinder.html?type=Images',
                filebrowserFlashBrowseUrl: '/lib/ckfinder/ckfinder.html?type=Flash',
                filebrowserUploadUrl: '/imageUpload.php'
            });
            //]]>
        </script>
    <?php } ?>

    <div class="trackwork-grid">
        <?php if (empty($allTrackwork)) { ?>
            <div class="trackwork-empty">No trackwork entries found.</div>
        <?php } ?>
        <?php foreach ($allTrackwork as $trackworkInfo) { ?>
            <div class="trackwork-card">
                <div class="trackwork-card-top">
                    <span class="trackwork-icon"><i class="fas fa-person-running"></i></span>
                    <div class="trackwork-date"><?php echo date("Y-m-d", strtotime($trackworkInfo['trackwork_date'])); ?></div>
                </div>
                <div class="trackwork-tags">
                    <span class="tag">Published <b class="<?php echo ($trackworkInfo['published'] == 'Y') ? 'yes' : 'no'; ?>"><?php echo $trackworkInfo['published']; ?></b></span>
                </div>
                <div class="trackwork-actions">
                    <a href="admin/trackworkManager.php?id=<?php echo $trackworkInfo['id']; ?>&q=edit-trackwork"><i class="fas fa-edit"></i> Edit</a>
                    <a href="javascript:void(0);" onclick="javascript: confirmDelete(<?php echo $trackworkInfo['id']; ?>);"><i class="fas fa-trash-alt"></i> Delete</a>
                </div>
            </div>
        <?php } ?>
    </div>
    <?php $paging->writePagination(); ?>
<?php } ?>
<?php
$design->closeDiv();
$design->writeLeftPanel();
$design->closeDiv();
$design->pageClose();
$design = NULL; // release object