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
  font:12px "Tahoma";
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
  background: none repeat scroll 0 0 #ffffff;
  border: 1px solid #cccccc;
  color: #999999;
  display: inline-block;
  font: 15px/25px Arial,Helvetica,sans-serif;
  margin: 5px 3px 0 0;
  padding: 0 5px;
  text-align: center;
  text-decoration: none;
  } 

  ul.setPaginate li a:hover,
  ul.setPaginate li a.current_page
  {
  background: none repeat scroll 0 0 #0d92e1;
  border: 1px solid #000000;
  color: #ffffff;
  text-decoration: none;
  }

  ul.setPaginate li a{
  color:black;
  display:block;
  text-decoration:none;
  padding:5px 8px;
  text-decoration: none;
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
    <?php if ($_SESSION['race_day_report'] == "Y") { ?>
              <div class="submenu">  
                <a href="admin/racedayReportsManager.php?q=new-report">Add New Report</a>
                 <div style="float:right;">
                    <a style="float:left;" href="admin/dashboard.php">Dashboard</a>
                    <a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
                  </div>
              </div>
              
              <?php if ($q=="new-report") { ?>              
              <form name="dividendForm" method="post" action="admin/racedayReportsManager.php" enctype="multipart/form-data">
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
                        <td class="alignLeft"><input type="text" name="date" id='report_date' value="<?php echo $date; ?>" /></td>
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
              <?php echo displayPaginationBelow(REPORTS_PER_PAGE,$pageno, $db); ?>
              <?php //$paging->writePagination(); ?>
              <table class="contentTable" style="margin-top:0px;">
                <tr>
                    <th class="thwhite alignLeft" colspan="3">To edit a Race Day Report entry, please delete the old one and Re-add</th>
                </tr>
                <tr>
                    <th>DATE</th>
                    <th>ACTIONS</th>                    
                </tr>
                <?php foreach ($allReports as $report) { ?>
                    <tr>                        
                        <td><?php echo date("d-m-y",strtotime($report['racedate'])); ?></td>
                        <td>
                            <a style="cursor:pointer;" onclick="javascript: confirmDelete(<?php echo $report['id'];?>);">Delete</a>
                        </td>
                    </tr>
                <?php } ?>
              </table>              
              <br />  
              <?php echo displayPaginationBelow(REPORTS_PER_PAGE,$pageno, $db); ?>
              <?php //$paging->writePagination(); ?>
              <br />            
<?php } ?>
              
             <?php                   
  $design->closeDiv();
  //$design->rightArea();  
  //$design->closeDiv();
  $design->closeDiv();
    $design->pageClose();
$design = NULL; // release object
