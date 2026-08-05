<?php
include_once('../bootstrap.php');
require_once('../lib/stewards.class.php');  
require_once("../lib/users.class.php");
require_once("../lib/userchecks.php");
  
$q = getParameterString('q','',$db);
session_start();                    
if(isset($_COOKIE['uid'])){                    
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
              
              $date = getParameterString('date','',$db);
              $title = getParameterString('title','',$db);
                    
              
              // save new dividend     
              if ($q == "add-report") {
                  try {
                      if (!$_FILES['reportFile']['error'])  { // error =0  
                        $filename = $_FILES['reportFile']['name'];  
                        $filename = basename($filename,".HTM")."_$date.HTM"; 
                        if (move_uploaded_file($_FILES['reportFile']['tmp_name'],$base.STEWARDS_REPORT_BASE."/".$filename)) {
                            $id = $srObj->insertStewardsReport($date,$title,$filename); 
                        }
                      }
                 } catch (Exception $err) {
                     $msg = $err->getMessage();
                 }
              }
          }
          
          if ($q=="delete-report") {
               $reportID=getParameterNumber('id',0);                
               $reportDetails = $srObj->getStewardsReportById($reportID); 
               try {
                   unlink($base.STEWARDS_REPORT_BASE."/".$reportDetails['filename']);   
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
  $pageTitle ='Stewards Reports Manager';        
  $design = new Design();
  $design->js='
  <script type="text/javascript" src="js/jquery.ui.core.min.js"></script>    
    <script type="text/javascript" src="js/jquery.ui.datepicker.min.js"></script>
    <script type="text/javascript">
        function confirmDelete(reportID) {
            if (confirm ("Are you sure ?")){
                location.href="admin/stewardsReportManager.php?q=delete-report&id="+reportID;
            }
            return false;
        }
    </script>
  ';
  $design->css ='
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
  $design->openDiv("infoWrapper");
  $design->openDiv("leftArea");
  ?>    
    <?php if (!empty($msg)) {?>
        <div class="message">
            <?php echo $msg; ?>
        </div>
    <?php } ?>
    <?php if (!empty($secmsg)) {?>
        <div class="message">
            <?php echo $secmsg; ?>
        </div>
    <?php } ?>    
    <?php if ($_SESSION['stewards_report'] == "Y") { ?>
              <div class="submenu">  
                <a href="admin/stewardsReportManager.php?q=new-report">Add New Report</a>
                 <div style="float:right;">
                    <a style="float:left;" href="admin/dashboard.php">Dashboard</a>
                    <a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
                  </div>
              </div>  
              <br />
              
              <?php if ($q=="new-report") { ?>              
              <form name="dividendForm" method="post" action="admin/stewardsReportManager.php" enctype="multipart/form-data">
                <table class="contentTable">
                    <col width="20%"><col width="80%">
                    <tr>
                        <th>Date</th>
                        <?php 
                          $date = '';
                            if ($q=="edit-report") {
                               // echo $reportDetails['dividend_date'];    
                                $date = date("Y-m-d",strtotime($reportDetails['racedate']));
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
                <?php } ?>
              <br />   
              <hr />
              <br />
              <table class="contentTable" style="margin-top:0px;">
                <tr>
                    <th class="thwhite alignLeft" colspan="3">To edit a Stewards Report entry, please delete the old one and Re-add</th>
                </tr>
                <tr>
                    <th>DATE</th>
                    <th>TITLE</th>
                    <th>ACTIONS</th>                    
                </tr>
                <?php foreach ($allReports as $report) { ?>
                    <tr>                        
                        <td><?php echo date("d-m-y",strtotime($report['racedate'])); ?></td>
                        <td><?php echo $report['title']; ?></td>
                        <td>
                            <a href="#" onclick="javascript: confirmDelete(<?php echo $report['id'];?>); return false;">Delete</a>
                        </td>
                    </tr>
                <?php } ?>
              </table>
              <br />              
<?php } ?>
             <?php                   
  $design->closeDiv();
  //$design->rightArea();  
  //$design->closeDiv();
  $design->closeDiv();
    $design->pageClose();
$design = NULL; // release object