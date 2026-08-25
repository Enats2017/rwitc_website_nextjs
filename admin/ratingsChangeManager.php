<?php
include_once('../bootstrap.php');
require_once('../lib/ratingschange.class.php');
require_once('../lib/pagination.class.php');
require_once("../lib/users.class.php");
require_once("../lib/userchecks.php");

$q = getParameterString('q', '', $db);
$pageno = getParameterNumber('pageno', 1);
session_start();
if (isset($_COOKIE['uid'])) {
    $uid = $_COOKIE['uid'];
} else {
    $uid = 0;
}
$userObj = new Users($db);

if (isAdminlogin()) {
    if ($_SESSION['rating_change'] == "Y") { // check login
        $rcObj = new RatingsChange($db);
        // all actions POST form submissions go here
        if (isset($_REQUEST['submit'])) {

            $date = getParameterString('date', '', $db);


            // save new dividend     
            if ($q == "add-rating") {
                try {
                    if (!$_FILES['ratingFile']['error']) { // error =0  
                        $filename = $_FILES['ratingFile']['name'];
                        $filename = basename($filename, ".HTM") . "_$date.HTM";
                        if (move_uploaded_file($_FILES['ratingFile']['tmp_name'], $base . RATINGSCHANGE_BASE . "/" . $filename)) {
                            $id = $rcObj->insertRatingsChange($date, $filename);
                        }
                    }
                } catch (Exception $err) {
                    $msg = $err->getMessage();
                }
            }
        }

        if ($q == "delete-rating") {
            $ratingID = getParameterNumber('id', 0);
            $ratingDetails = $rcObj->getRatingsChangeById($ratingID);
            try {
                unlink($base . RATINGSCHANGE_BASE . "/" . $ratingDetails['filename']);
                $rcObj->deleteRatingsChangeByID($ratingID);
            } catch (Exception $err) {
                $msg = $err->getMessage();
            }
        }

        if (!isset($date)) {
            $date = '';
        }

        // fetch all articles
        $allRatingFull = $rcObj->getAllRatingsChange();
        $totalRatings = count($allRatingFull);
        $paging = new Pagination($pageno, 15, $totalRatings);
        $allRating = array_slice($allRatingFull, ($pageno - 1) * 15, 15);
    } else {
        $msg = "You do not have access to this page.";
    }
} else {
    $secmsg = "Please login to access this page";
}
$pageTitle = 'Ratings Change Manager';
$design = new Design();
$design->js = '
  <script type="text/javascript" src="js/jquery.ui.core.min.js"></script>    
    <script type="text/javascript" src="js/jquery.ui.datepicker.min.js"></script>
    <script type="text/javascript">
        function confirmDelete(ratingID) {
            if (confirm ("Are you sure ?")){
                location.href="admin/ratingsChangeManager.php?q=delete-rating&id="+ratingID;
            }
        }
    </script>
  ';
$design->css = '
  <link type="text/css" href="css/jquery.ui.all.css" rel="stylesheet" />    
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
    $('#rating_date').datepicker({
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

    .message {
        background: #fff3cd;
        border: 1px solid #ffe08a;
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 15px;
        font-size: 15px;
    }

    .ratings-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 12px;
    }

    .add-rating-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #fff;
        border: 1px solid #1a7a45;
        color: #0f5c33;
        padding: 10px 18px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
    }

    .add-rating-btn:hover {
        background: #e6f4ec;
    }

    .header-links {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .header-links a {
        color: #0f5c33;
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
    }

    .header-links a:hover {
        text-decoration: underline;
    }

    .rating-form-wrap {
        background: #fff;
        border: 1px solid #e2e6e4;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        max-width: 700px;
    }

    .rating-form-wrap .form-row {
        margin-bottom: 20px;
    }

    .rating-form-wrap label.form-label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #2b332f;
        margin-bottom: 8px;
    }

    .rating-form-wrap input[type="text"] {
        width: 100%;
        border: 1px solid #e2e6e4;
        border-radius: 8px;
        padding: 10px 12px;
        font-size: 14px;
        color: #2b332f;
        box-sizing: border-box;
        font-family: inherit;
    }

    .rating-form-wrap input[type="text"]:focus {
        outline: none;
        border-color: #1a7a45;
    }

    .rating-form-wrap input[type="file"] {
        width: 100%;
        border: 1px solid #e2e6e4;
        border-radius: 8px;
        padding: 9px 12px;
        font-size: 14px;
        color: #2b332f;
        box-sizing: border-box;
        background: #f9faf9;
    }

    .rating-form-wrap .form-actions {
        display: flex;
        gap: 10px;
        padding-top: 6px;
    }

    .rating-form-wrap input[type="submit"],
    .rating-form-wrap input[type="reset"] {
        background: #0f5c33;
        color: #fff;
        border: none;
        padding: 10px 22px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
    }

    .rating-form-wrap input[type="reset"] {
        background: #fff;
        color: #2b332f;
        border: 1px solid #e2e6e4;
    }

    .rating-form-wrap input[type="submit"]:hover {
        background: #0c4a29;
    }

    .rating-form-wrap input[type="reset"]:hover {
        background: #f5f4ee;
    }

    .rating-note {
        background: #f5f4ee;
        border: 1px solid #e2e6e4;
        color: #7a8c84;
        font-size: 13.5px;
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 18px;
    }

    .ratings-list {
        background: #fff;
        border: 1px solid #e2e6e4;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    }

    .ratings-list-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 20px;
        border-bottom: 1px solid #eef0ee;
    }

    .ratings-list-row:last-child {
        border-bottom: none;
    }

    .ratings-list-row .rating-date {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14.5px;
        color: #2b332f;
        font-weight: 500;
    }

    .ratings-list-row .rating-date i {
        color: #0f5c33;
    }

    .ratings-list-row .rating-delete {
        color: #c0392b;
        text-decoration: none;
        font-size: 13.5px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .ratings-list-row .rating-delete:hover {
        text-decoration: underline;
    }

    .ratings-list-empty {
        padding: 20px;
        text-align: center;
        color: #7a8c84;
        font-size: 14px;
    }

    @media (max-width: 700px) {
        #leftArea.col-lg-9 {
            padding: 0 16px;
        }

        .ratings-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .rating-form-wrap {
            padding: 18px;
        }
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
<?php if ($_SESSION['rating_change'] == "Y") { ?>
    <div class="ratings-header">
        <a class="add-rating-btn" href="admin/ratingsChangeManager.php?q=new-report"><i class="fas fa-plus"></i> Add New Rating Change</a>
        <div class="header-links">
            <!-- <a href="admin/dashboard.php">Dashboard</a>
                    <a href="admin/adminlogin.php?q=logout">Logout</a> -->
        </div>
    </div>

    <?php if ($q == "new-report") { ?>
        <div class="rating-form-wrap">
            <form name="dividendForm" method="post" action="admin/ratingsChangeManager.php" enctype="multipart/form-data">
                <div class="form-row">
                    <label class="form-label" for="rating_date">Date</label>
                    <input type="text" name="date" id='rating_date' value="<?php echo $date; ?>" />
                </div>
                <div class="form-row">
                    <label class="form-label" for="ratingFile">Upload File</label>
                    <input type="file" name="ratingFile" id="ratingFile" />
                </div>
                <div class="form-actions">
                    <input type="submit" name="submit" value="Save" />
                    <input type="reset" name="reset" value="Clear" />
                    <input type="hidden" name="q" value="add-rating" />
                </div>
            </form>
        </div>
    <?php } ?>

    <div class="rating-note"><i class="fas fa-circle-info"></i> To edit a Ratings Change entry, please delete the old one and Re-add</div>

    <div class="ratings-list" id="rcRatingsList">
        <?php if (empty($allRating)) { ?>
            <div class="ratings-list-empty">No rating change entries found.</div>
        <?php } ?>
        <?php foreach ($allRating as $rating) { ?>
            <div class="ratings-list-row">
                <div class="rating-date"><i class="far fa-calendar-alt"></i> <?php echo date("d-m-y", strtotime($rating['racedate'])); ?></div>
                <a class="rating-delete" href="javascript:void(0)" onclick="javascript: confirmDelete(<?php echo $rating['id']; ?>);"><i class="fas fa-trash-alt"></i> Delete</a>
            </div>
        <?php } ?>
    </div>
    <?php $paging->writePagination(); ?>
<?php } ?>
<?php
$design->closeDiv();
$design->writeLeftPanel();
$design->closeDiv();
$design->closeDiv();
$design->pageClose();
$design = NULL; // release object