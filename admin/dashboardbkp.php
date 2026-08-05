<?php
include_once('../bootstrap.php');
require_once("../lib/users.class.php");
require_once("../lib/userchecks.php");

session_start();
//$uid = $_COOKIE['uid'];             
$userObj = new Users($db);
if (!isAdminlogin()) { // check login    
    $secmsg = "You do not have access to this page.";
}
$pageTitle = 'Dashboard';
// create a template object
$design = new Design();
$design->js = '';
$design->css = '';
$design->jqueryJs = "";
$design->startPage("$pageTitle");
$design->writeLogoTickerMenu();
$design->openDiv("contentWrapper");
$design->openDiv("infoWrapper");
$design->openDiv("leftArea");
?>
<style type="text/css">
    table,
    td {
        background-color: ;
        border: 1px solid black;
    }
    th,
    td {
        font-size: 19px;
        padding: 15px;
        text-align: left;
    }
    .light {
        background-color: #FFF;
    }
    .dark {
        background-color: #f2f2f2;
    }
    .abc {
        color: black;
    }
</style>
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
<?php if (empty($secmsg)) { ?>
    <div class="submenu">
        <div style="float:right;">
            <a style="float:left;" href="admin/dashboard.php">Dashboard</a>
            <a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
        </div>
    </div>
    <br />
    <div>
        <center>
            <table>
                <tr>
                    <td class="dark">
                        <?php if ($_SESSION['articles'] == "Y") { ?>
                            <a class="abc" ; href="admin/articlesManager.php">Articles Manager</a><br />
                        <?php } ?>
                    </td>
                    <td class="light" ;>
                        <?php if ($_SESSION['articles'] == "Y") { ?>
                            <a class="abc" ; href="admin/csrArticlesManager.php">CSR Articles Manager</a><br />
                        <?php } ?>
                    </td>
                    <td class="dark">
                        <?php if ($_SESSION['race_history'] == "Y") { ?>
                            <a class="abc" ; href="admin/raceHistoryManager.php">Race History Manager</a><br />
                        <?php } ?>
                    </td>
                    <td class="light" ;>
                        <?php if ($_SESSION['send_mailer'] == "Y") { ?>
                            <a class="abc" ; href="admin/sendMailers.php">Send Mailers</a><br />
                        <?php } ?>
                    </td>
                </tr>
                <tr>
                    <td class="dark" ;>
                        <?php if ($_SESSION['rating_change'] == "Y") { ?>
                            <a class="abc" ; href="admin/ratingsChangeManager.php">Ratings Change Manager</a><br />
                        <?php } ?>
                    </td>
                    <td class="light" ;>
                        <?php if ($_SESSION['gallery'] == "Y") { ?>
                            <a class="abc" ; href="admin/galleryManager.php">Gallery Manager</a><br />
                        <?php } ?>
                    </td>
                    <td class="dark" ;>
                        <?php if ($_SESSION['video'] == "Y") { ?>
                            <a class="abc" ; href="admin/manageVideos.php">Videos Manager</a><br />
                        <?php } ?>
                    </td>
                    <td class="light" ;>
                        <?php if ($_SESSION['dividends'] == "Y") { ?>
                            <a class="abc" ; href="admin/dividendsManager.php">Dividends Manager</a><br />
                        <?php } ?>
                    </td>
                <tr>
                <tr>
                    <td class="dark" ;>
                        <?php if ($_SESSION['stewards_report'] == "Y") { ?>
                            <a class="abc" ; href="admin/stewardsReportManager.php">Stewards Report Manager</a><br />
                        <?php } ?>
                    </td>
                    <td class="light" ;>
                        <?php if ($_SESSION['race_day_report'] == "Y") { ?>
                            <a class="abc" ; href="admin/racedayReportsManager.php">Race Day Reports Manager</a><br />
                        <?php } ?>
                    </td>
                    <td class="dark" ;>
                        <?php if ($_SESSION['calendar'] == "Y") { ?>
                            <a class="abc" ; href="admin/calendarManager.php">Calendar Manager</a><br />
                        <?php } ?>
                    </td>
                    <td class="light" ;>
                        <?php if ($_SESSION['calendar'] == "Y") { ?>
                            <a class="abc" ; href="admin/availibilityManager.php">Racecource Availibility Calendar
                                Manager</a><br />
                        <?php } ?>
                    </td>
                </tr>
                <tr>
                    <td class="dark" ;>
                        <?php if ($_SESSION['prakash_gosavi'] == "Y") { ?>
                            <a class="abc" ; href="admin/pgArticlesManager.php">Prakash Gosavi Articles Manager</a><br />
                        <?php } ?>
                    </td>
                    <td class="light" ;>
                        <?php if ($_SESSION['shiven_surendranath'] == "Y") { ?>
                            <a class="abc" ; href="admin/ssArticlesManager.php">Shiven Surendranath Articles Manager</a><br />
                        <?php } ?>
                    </td>
                    <td class="dark" ;>
                        <?php if ($_SESSION['polls'] == "Y") { ?>
                            <a class="abc" ; href="admin/managePolls.php">Manage Polls</a><br />
                        <?php } ?>
                    </td>
                    <td class="light" ;>
                        <?php if ($_SESSION['adminusers'] == "Y") { ?>
                            <a class="abc" ; href="admin/manageAdmin.php">Manage Admins</a><br />
                        <?php } ?>
                    </td>
                </tr>
                <tr>
                    <td class="dark" ;>
                        <?php if (isset($_SESSION['workingManager']) && $_SESSION['workingManager'] == "Y") { ?>
                            <a class="abc" ; href="admin/workingManager.php">Working Group Upload</a><br />
                        <?php } ?>
                    </td>
                    <td class="light" ;>
                        <?php if (isset($_SESSION['bannerManager']) && $_SESSION['bannerManager'] == "Y") { ?>
                            <a class="abc" ; href="admin/bannerManager.php">Banner Manager</a><br />
                        <?php } ?>
                    </td>
                    <td class="dark" ;>
                        <?php if (isset($_SESSION['tickerManager']) && $_SESSION['tickerManager'] == "Y") { ?>
                            <a class="abc" ; href="admin/tickerManager.php">Ticker Manager</a><br />
                        <?php } ?>
                    </td>
                    <td class="light" ;>
                        <?php if (isset($_SESSION['sponsorManager']) && $_SESSION['sponsorManager'] == "Y") { ?>
                            <a class="abc" ; href="admin/sponsorManager.php">Sponsor Manager</a><br />
                        <?php } ?>
                    </td>
                </tr>
                <tr>
                    <td class="dark" ;>
                        <?php if (isset($_SESSION['sponsorofthedayManager']) && $_SESSION['sponsorofthedayManager'] == "Y") { ?>
                            <a class="abc" ; href="admin/sponsorofthedayManager.php">Sponsor Of the Day Manager</a><br />
                        <?php } ?>
                    </td>
                    <td class="light" ;>
                        <?php if (isset($_SESSION['configManager']) && $_SESSION['configManager'] == "Y") { ?>
                            <a class="abc" ; href="admin/configManager.php">Config Manager</a><br />
                        <?php } ?>
                    </td>
                    <td class="dark" ;>
                        <?php if (isset($_SESSION['horseweightManager']) && $_SESSION['horseweightManager'] == "Y") { ?>
                            <a class="abc" ; href="admin/horseweightManager.php">Reset Horse Weight Manager</a><br />
                        <?php } ?>
                    </td>
                    <td class="light" ;>
                        <?php if (isset($_SESSION['racedataManager']) && $_SESSION['racedataManager'] == "Y") { ?>
                            <a class="abc" ; href="admin/racedataManager.php">Reset Race Data Manager</a><br />
                        <?php } ?>
                    </td>
                </tr>
                <tr>
                    <td class="dark" ;>
                        <?php if (isset($_SESSION['mailManager']) && $_SESSION['mailManager'] == "Y") { ?>
                            <a class="abc" ; href="admin/mailManager.php">Draft Mail Manager</a><br />
                        <?php } ?>
                    </td>
                    <td class="light" ;>
                        <?php if (isset($_SESSION['homepopup']) && $_SESSION['homepopup'] == "Y") { ?>
                            <a class="abc" ; href="admin/homepopup.php">Home Popup</a><br />
                        <?php } ?>
                    </td>
                    <td class="dark" ;>
                        Race Date
                    </td>
                    <td class="light" ;>
                        <a class="abc" ; href="admin/erp_prerace.php">Pre Race Date</a><br />
                    </td>
                </tr>
                <tr>
                    <td class="dark" ;>
                        Race Date
                    </td>
                    <td class="light" ;>
                        <a class="abc" ; href="admin/erp_postrace.php">Post Race Date</a><br />
                    </td>
                    <td class="dark" ;>
                        <a class="abc" ; href="admin/trackworkManager.php">Trackwork Manager</a><br />
                    </td>
                    <td class="light" ;>
                    </td>
                </tr>
                <tr>
                    <td class="dark" ;>
                        Suggestion List
                    </td>
                    <td class="light" ;>
                        <a class="abc" ; href="admin/suggestion_feedback_list.php">Suggestion Feedback</a><br />
                    </td>
                    <td class="dark" ;>
                        <a class="abc" ; href="admin/youtube_videos_upload.php">YouTube Upload</a><br />
                    </td>
                    <td class="light" ;>
                    </td>
                </tr>
                <tr>
                    <td class="dark" ;>
                        Chairman List
                    </td>
                    <td class="light" ;>
                        <a class="abc" ; href="admin/email_to_chairman_list.php">Chairman Email list</a><br />
                    </td>
                    <td class="dark" ;>
                        <a class="abc" ; href="admin/image_upload.php">Image Upload</a><br />
                    </td>
                    <td class="light" ;>
                    </td>
                </tr>
            </table>
            <center>
    </div>
<?php } ?>
<?php
$design->closeDiv();
//$design->rightArea();  
//$design->closeDiv();
$design->closeDiv();
//$design->pageClose();
$design = NULL; // release object