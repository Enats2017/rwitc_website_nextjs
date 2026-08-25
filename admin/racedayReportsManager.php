<?php
  define("REPORTS_PER_PAGE",20);
  include_once('../bootstrap.php');
  require_once('../lib/racedayreports.class.php');
  include_once('../lib/pagination.class.php');
  require_once("../lib/users.class.php");
  require_once("../lib/userchecks.php");
  require_once("../lib/function_race_report.php");

  $q = getParameterString('q','',$db);
  session_start();                    
  if(isset($_COOKIE['uid'])){                    
    $uid = $_COOKIE['uid'];    
  } else {
    $uid = 0;
  }             
  $userObj = new Users($db);  
  
  if (isAdminlogin()) {
    if ($_SESSION['race_day_report'] == "Y") { // check
      $pageno = getParameterNumber('page',1);
      $rrObj = new RaceReport($db);
      
      
      // all actions POST form submissions go here
      if (isset($_REQUEST['submit'])) {      
          $date = getParameterString('date','',$db);
          // save new dividend     
          if ($q == "add-report") {
              try {
                  if (!$_FILES['reportFile']['error'])  { // error =0  
                    $filename = $_FILES['reportFile']['name'];  
                    $filename = basename($filename,".HTM")."_$date.HTM"; 
                    if (move_uploaded_file($_FILES['reportFile']['tmp_name'],$base.RACEREPORTS_BASE."/".$filename)) {
                        $id = $rrObj->insertRaceReport($date,$filename); 
                    }
                  }
             } catch (Exception $err) {
                 $msg = $err->getMessage();
             }
          }
      }
      
      if ($q=="delete-report") {
           $reportID=getParameterNumber('id',0);                
           $reportDetails = $rrObj->getRaceReportById($reportID); 
           try {
               unlink($base.RACEREPORTS_BASE."/".$reportDetails['filename']);   
               $rrObj->deleteRaceReportByID($reportID);
           } catch (Exception $err) {
               $msg = $err->getMessage();        
           }
      }
        // fetch all articles
      
      //$totalRecords = $rrObj->getAllRaceReportsCount();  
      // create a pagination object
      //$paging = new Pagination($pageno,REPORTS_PER_PAGE,$totalRecords);  
      $allReports = $rrObj->getRaceRecordsPageWise($pageno,REPORTS_PER_PAGE);
    } else {
        $msg = "You do not have access to this page.";
      }  
    } else {
        $secmsg = "Please login to access this page";
    }
  
  $pageTitle ='Race Day Reports Manager';        
  // create a template object
  $design = new Design();
  
  
  $design->js='
  <script type="text/javascript" src="js/jquery.ui.core.min.js"></script>    
    <script type="text/javascript" src="js/jquery.ui.datepicker.min.js"></script>
    <script type="text/javascript">
        function confirmDelete(reportID) {
            if (confirm ("Are you sure ?")){
                location.href="admin/racedayReportsManager.php?q=delete-report&id="+reportID;
            }
        }
    </script>
  ';
  $design->css ='
  <link type="text/css" href="css/jquery.ui.all.css" rel="stylesheet" /> 
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style type="text/css">
  .navi {
  width: 500px;
  margin: 5px;
  padding:2px 5px;
  border:1px solid #eee;
  }

  .show {
  color: blue;
  margin: 5px 0;
  padding: 3px 5px;
  cursor: pointer;
  font: 15px/19px Arial,Helvetica,sans-serif;
  }
  .show a {
  text-decoration: none;
  }
  .show:hover {
  text-decoration: underline;
  }


  ul.setPaginate li.setPage{
  padding:15px 10px;
  font-size:14px;
  }

  ul.setPaginate{
  margin:0px;
  padding:0px;
  height:100%;
  overflow:hidden;
  font: 14px "Inter","Segoe UI",Arial,sans-serif;
  list-style-type:none; 
  }  

  ul.setPaginate li.dot{padding: 3px 0;}

  ul.setPaginate li{
  float:left;
  margin:0px;
  padding:0px;
  margin-left:5px;
  }



  ul.setPaginate li a
  {
  background: #ffffff;
  border: 1px solid #e2e6e4;
  border-radius: 8px;
  color: #2b332f;
  display: inline-block;
  font: 14px/25px "Inter","Segoe UI",Arial,sans-serif;
  font-weight: 500;
  margin: 5px 3px 0 0;
  padding: 0 5px;
  text-align: center;
  text-decoration: none;
  } 

  ul.setPaginate li a:hover,
  ul.setPaginate li a.current_page
  {
  background: #0f5c33;
  border: 1px solid #0f5c33;
  color: #ffffff;
  text-decoration: none;
  }

  ul.setPaginate li a{
  display:block;
  text-decoration:none;
  padding:5px 12px;
  text-decoration: none;
  }

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

  .reports-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
  .add-report-btn { display: inline-flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #1a7a45; color: #0f5c33; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; }
  .add-report-btn:hover { background: #e6f4ec; }
  .header-links { display: flex; align-items: center; gap: 16px; }
  .header-links a { color: #0f5c33; text-decoration: none; font-weight: 600; font-size: 14px; }
  .header-links a:hover { text-decoration: underline; }

  .report-form-wrap { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); max-width: 700px; }
  .report-form-wrap .form-row { margin-bottom: 20px; }
  .report-form-wrap label.form-label { display: block; font-size: 14px; font-weight: 600; color: #2b332f; margin-bottom: 8px; }
  .report-form-wrap input[type="text"] {
    width: 100%; border: 1px solid #e2e6e4; border-radius: 8px; padding: 10px 12px; font-size: 14px; color: #2b332f; box-sizing: border-box; font-family: inherit;
  }
  .report-form-wrap input[type="text"]:focus { outline: none; border-color: #1a7a45; }
  .report-form-wrap input[type="file"] {
    width: 100%; border: 1px solid #e2e6e4; border-radius: 8px; padding: 9px 12px; font-size: 14px; color: #2b332f; box-sizing: border-box; background: #f9faf9;
  }
  .report-form-wrap .form-actions { display: flex; gap: 10px; padding-top: 6px; }
  .report-form-wrap input[type="submit"], .report-form-wrap input[type="reset"] { background: #0f5c33; color: #fff; border: none; padding: 10px 22px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; }
  .report-form-wrap input[type="reset"] { background: #fff; color: #2b332f; border: 1px solid #e2e6e4; }
  .report-form-wrap input[type="submit"]:hover { background: #0c4a29; }
  .report-form-wrap input[type="reset"]:hover { background: #f5f4ee; }

  .report-note { background: #f5f4ee; border: 1px solid #e2e6e4; color: #7a8c84; font-size: 13.5px; padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; }

  .reports-list { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.03); }
  .reports-list-row { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border-bottom: 1px solid #eef0ee; }
  .reports-list-row:last-child { border-bottom: none; }
  .reports-list-row .report-date { display: flex; align-items: center; gap: 10px; font-size: 14.5px; color: #2b332f; font-weight: 500; }
  .reports-list-row .report-date i { color: #0f5c33; }
  .reports-list-row .report-delete { color: #c0392b; text-decoration: none; font-size: 13.5px; font-weight: 500; display: flex; align-items: center; gap: 6px; cursor: pointer; }
  .reports-list-row .report-delete:hover { text-decoration: underline; }
  .reports-list-empty { padding: 20px; text-align: center; color: #7a8c84; font-size: 14px; }

  .pagination-block { margin: 18px 0; }

  @media (max-width: 700px) {
    #leftArea.col-lg-9 { padding: 0 16px; }
    .reports-header { flex-direction: column; align-items: flex-start; }
    .report-form-wrap { padding: 18px; }
  }

  html, body { scrollbar-width: none; -ms-overflow-style: none; }
html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }

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
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea",'col-lg-9');
  
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
    <?php if ($_SESSION['race_day_report'] == "Y") { ?>
              <div class="reports-header">
                <a class="add-report-btn" href="admin/racedayReportsManager.php?q=new-report"><i class="fas fa-plus"></i> Add New Report</a>
                 <div class="header-links">
                    <!-- <a href="admin/dashboard.php">Dashboard</a>
                    <a href="admin/adminlogin.php?q=logout">Logout</a> -->
                  </div>
              </div>
              
              <?php if ($q=="new-report") { ?>              
              <div class="report-form-wrap">
              <form name="dividendForm" method="post" action="admin/racedayReportsManager.php" enctype="multipart/form-data">
                <div class="form-row">
                    <label class="form-label" for="report_date">Date</label>
                    <?php 
                      $date = '';
                        if ($q=="edit-report") {
                           // echo $reportDetails['dividend_date'];    
                            $date = date("Y-m-d",strtotime($reportDetails['racedate']));
                        }
                    ?>
                    <input type="text" name="date" id='report_date' value="<?php echo $date; ?>" />
                </div>
                <div class="form-row">
                    <label class="form-label" for="reportFile">Upload File</label>
                    <input type="file" name="reportFile" id="reportFile" />
                </div>
                <div class="form-actions">
                    <input type="submit" name="submit" value="Save" />
                    <input type="reset" name="reset" value="Clear" />
                    <input type="hidden" name="q" value="add-report" />
                </div>
              </form>
              </div>
                <?php } ?>

              <div class="report-note"><i class="fas fa-circle-info"></i> To edit a Race Day Report entry, please delete the old one and Re-add</div>

              <div class="pagination-block"><?php echo displayPaginationBelow(REPORTS_PER_PAGE,$pageno, $db); ?></div>
              <?php //$paging->writePagination(); ?>

              <div class="reports-list">
                <?php if (empty($allReports)) { ?>
                    <div class="reports-list-empty">No race day report entries found.</div>
                <?php } ?>
                <?php foreach ($allReports as $report) { ?>
                    <div class="reports-list-row">
                        <div class="report-date"><i class="far fa-calendar-alt"></i> <?php echo date("d-m-y",strtotime($report['racedate'])); ?></div>
                        <a class="report-delete" onclick="javascript: confirmDelete(<?php echo $report['id'];?>);"><i class="fas fa-trash-alt"></i> Delete</a>
                    </div>
                <?php } ?>
              </div>

              <div class="pagination-block"><?php echo displayPaginationBelow(REPORTS_PER_PAGE,$pageno, $db); ?></div>
              <?php //$paging->writePagination(); ?>
<?php } ?>
              
             <?php                   
  $design->closeDiv();
  $design->writeLeftPanel();
  $design->closeDiv();
  $design->closeDiv();
    $design->pageClose();
$design = NULL; // release object