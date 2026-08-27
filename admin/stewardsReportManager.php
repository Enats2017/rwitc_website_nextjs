<?php
include_once('../bootstrap.php');
require_once('../lib/stewards.class.php');
require_once("../lib/users.class.php");
require_once("../lib/userchecks.php");

$q = getParameterString('q', '', $db);
session_start();
if (isset($_COOKIE['uid'])) {
    $uid = $_COOKIE['uid'];
} else {
    $uid = 0;
}
$userObj = new Users($db);
if (isAdminlogin()) {
    if ($_SESSION['stewards_report'] == "Y") { // check login  
        $srObj = new StewardsReport($db);

        // all actions POST form submissions go here
        if (isset($_REQUEST['submit'])) {

            $date = getParameterString('date', '', $db);
            $title = getParameterString('title', '', $db);


            // save new dividend     
            if ($q == "add-report") {
                try {
                    if (!$_FILES['reportFile']['error']) { // error =0  
                        $filename = $_FILES['reportFile']['name'];
                        $filename = basename($filename, ".HTM") . "_$date.HTM";
                        if (move_uploaded_file($_FILES['reportFile']['tmp_name'], $base . STEWARDS_REPORT_BASE . "/" . $filename)) {
                            $id = $srObj->insertStewardsReport($date, $title, $filename);
                        }
                    }
                } catch (Exception $err) {
                    $msg = $err->getMessage();
                }
            }
        }

        if ($q == "delete-report") {
            $reportID = getParameterNumber('id', 0);
            $reportDetails = $srObj->getStewardsReportById($reportID);
            try {
                unlink($base . STEWARDS_REPORT_BASE . "/" . $reportDetails['filename']);
                $srObj->deleteStewardsReportByID($reportID);
            } catch (Exception $err) {
                $msg = $err->getMessage();
            }
        }
        // fetch all articles
        $allReports = $srObj->getAllStewardsReports();
    } else {
        $msg = "You do not have access to this page.";
    }
} else {
    $secmsg = "Please login to access this page";
}

?>
<?php
$pageTitle = 'Stewards Reports Manager';
$design = new Design();
$design->js = '
  <script type="text/javascript" src="js/jquery.ui.core.min.js"></script>    
    <script type="text/javascript" src="js/jquery.ui.datepicker.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script type="text/javascript">
        function confirmDelete(reportID) {
            if (confirm ("Are you sure ?")){
                location.href="admin/stewardsReportManager.php?q=delete-report&id="+reportID;
            }
            return false;
        }
    </script>
  ';
$design->css = '
  <link type="text/css" href="css/jquery.ui.all.css" rel="stylesheet" />    
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
    $('#report_date').datepicker({
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

    .reports-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 12px;
    }

    .add-report-btn {
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
        white-space: nowrap;
    }

    .add-report-btn:hover {
        background: #e6f4ec;
    }

    .section-title {
        font-size: 16px;
        font-weight: 700;
        color: #0f5c33;
        margin: 28px 0 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .section-hint {
        font-size: 12.5px;
        color: #7a8c84;
        margin: -8px 0 14px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* ===== Add Report form — normal inline screen card ===== */
    .report-form-wrap {
        background: #fff;
        border: 1px solid #e2e6e4;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 24px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    }

    .report-form-wrap h3 {
        margin: 0 0 16px;
        font-size: 17px;
        color: #0f5c33;
        font-weight: 700;
    }

    .report-form-table {
        width: 100%;
        border-collapse: collapse;
    }

    .report-form-table th {
        text-align: left;
        padding: 10px 8px;
        color: #2b332f;
        vertical-align: top;
        width: 20%;
        font-weight: 600;
        font-size: 13.5px;
    }

    .report-form-table td {
        padding: 10px 8px;
    }

    .report-form-table input[type="text"],
    .report-form-table input[type="file"] {
        border: 1px solid #e2e6e4;
        border-radius: 6px;
        padding: 8px 10px;
        font-size: 14px;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
    }

    .report-form-table input[type="submit"],
    .report-form-table input[type="reset"] {
        background: #0f5c33;
        color: #fff;
        border: none;
        padding: 9px 20px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        margin-right: 8px;
        margin-top: 6px;
    }

    .report-form-table input[type="reset"] {
        background: #fff;
        color: #2b332f;
        border: 1px solid #e2e6e4;
    }

    /* ===== reports table ===== */
    .reports-table-wrap {
        background: #fff;
        border: 1px solid #e2e6e4;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    }

    table.reports-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14.5px;
    }

    table.reports-table th {
        background: #0b3d24;
        color: #fff;
        text-align: left;
        padding: 14px 20px;
        font-weight: 600;
        font-size: 13px;
        letter-spacing: 0.3px;
    }

    table.reports-table th.action-col {
        text-align: right;
        width: 120px;
    }

    table.reports-table td {
        padding: 14px 20px;
        border-bottom: 1px solid #eef0ee;
        color: #2b332f;
    }

    table.reports-table tr:last-child td {
        border-bottom: none;
    }

    table.reports-table tr:nth-child(even) td {
        background: #f7faf8;
    }

    table.reports-table tr:hover td {
        background: #e6f4ec;
    }

    table.reports-table td.action-col {
        text-align: right;
        white-space: nowrap;
    }

    table.reports-table td.action-col a {
        font-size: 13.5px;
        text-decoration: none;
        font-weight: 500;
        color: #c0392b;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .reports-empty {
        padding: 30px 20px;
        text-align: center;
        color: #7a8c84;
        font-size: 14.5px;
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

        .reports-header {
            flex-direction: column;
            align-items: stretch;
        }

        table.reports-table th,
        table.reports-table td {
            padding: 10px 12px;
            font-size: 13.5px;
        }
    }

    @media (max-width: 520px) {

        .report-form-table,
        .report-form-table tbody,
        .report-form-table tr,
        .report-form-table th,
        .report-form-table td {
            display: block;
            width: 100% !important;
        }

        .report-form-table th {
            padding-bottom: 2px;
        }

        .report-form-table td {
            padding-top: 0;
            padding-bottom: 14px;
        }
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
<?php if ($_SESSION['stewards_report'] == "Y") { ?>

    <div class="reports-header">
        <a class="add-report-btn" href="admin/stewardsReportManager.php?q=new-report"><i class="fas fa-plus"></i> Add New Report</a>
        <!--
                <div style="float:right;">
                    <a style="float:left;" href="admin/dashboard.php">Dashboard</a>
                    <a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
                </div>
                -->
    </div>

    <?php if ($q == "new-report") { ?>
        <div class="report-form-wrap">
            <h3>Add New Report</h3>
            <form name="dividendForm" method="post" action="admin/stewardsReportManager.php" enctype="multipart/form-data">
                <table class="report-form-table">
                    <col width="20%">
                    <col width="80%">
                    <tr>
                        <th>Date</th>
                        <?php
                        $date = '';
                        if ($q == "edit-report") {
                            // echo $reportDetails['dividend_date'];    
                            $date = date("Y-m-d", strtotime($reportDetails['racedate']));
                        }
                        ?>
                        <td class="alignLeft"><input type="text" name="date" id="report_date" value="<?php echo $date; ?>" /></td>
                    </tr>
                    <tr>
                        <th>Title</th>
                        <td class="alignLeft"><input type="text" name="title" /></td>
                    </tr>
                    <tr>
                        <th>Upload File</th>
                        <td class="alignLeft"><input type="file" name="reportFile" /></td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <input type="submit" name="submit" value="Save" />
                            <input type="reset" name="reset" value="Clear" />
                            <input type="hidden" name="q" value="add-report" />
                        </td>
                    </tr>
                </table>
            </form>
        </div>
    <?php } ?>

    <div class="section-title"><i class="fas fa-shield-halved"></i> All Stewards Reports</div>
    <div class="section-hint"><i class="fas fa-circle-info"></i> To edit a Stewards Report entry, please delete the old one and re-add.</div>

    <div class="reports-table-wrap">
        <table class="reports-table">
            <tr>
                <th>DATE</th>
                <th>TITLE</th>
                <th class="action-col">ACTIONS</th>
            </tr>
            <?php if (count($allReports) > 0) { ?>
                <?php foreach ($allReports as $report) { ?>
                    <tr>
                        <td><?php echo date("d-m-y", strtotime($report['racedate'])); ?></td>
                        <td><?php echo $report['title']; ?></td>
                        <td class="action-col">
                            <a href="#" onclick="javascript: confirmDelete(<?php echo $report['id']; ?>); return false;"><i class="fas fa-trash-alt"></i> Delete</a>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="3" class="reports-empty">No stewards reports added yet.</td>
                </tr>
            <?php } ?>
        </table>
    </div>
<?php } ?>
<?php
$design->closeDiv();
$design->writeLeftPanel();
$design->closeDiv();
$design->closeDiv();
$design->endPage();
$design->pageClose();
$design = NULL; // release object