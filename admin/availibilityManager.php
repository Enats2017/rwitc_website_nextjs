<?php
  include_once('../bootstrap.php');
  require_once('../lib/availibility.class.php');
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
    if ($_SESSION['calendar'] == "Y") { // check login
  
      $calendarObj = new AvailibilityCalendar($db);
      /*$allCentres = $calendarObj->getCentresList();
      $centresList = array();
      foreach($allCentres as $centre) {
          $centresList[$centre['id']] = $centre['centre'];
      }
      */
      // all actions POST form submissions go here
      if (isset($_REQUEST['submit'])) {
          
          $date = getParameterString('date','',$db);
          //$centreid = getParameterString('centreid','',$db);
                
          
          // save new dividend     
          if ($q == "add-calendar") {
              try {
                   $id = $calendarObj->insertRaceDate($date); 
                   $msg = "New calendar entry added";
             } catch (Exception $err) {
                 $msg = $err->getMessage();
             }
          }
          if ($q == "update-calendar") {
             $calendarID=getParameterNumber('id',0);    
             try {
                $rowsAffected = $calendarObj->updateRaceDate($calendarID,$date);
                $msg = "Calendar entry updated";
             } catch (Exception $err) {
                 $msg = $err->getMessage();
             }      
          }    
          
         

      }
      if ($q=="edit-calendar") {
         $calendarID=getParameterNumber('id',0);         
         try {
            $calendarDetails = $calendarObj->getCalendarById($calendarID);
         } catch (Exception $err) {
            $msg = $err->getMessage();        
         }
      }
      
      if ($q=="delete-calendar") {
           $calendarID=getParameterNumber('id',0);                
           $calendarDetails = $calendarObj->deleteCalendarByID($calendarID);
           $msg = "Calendar entry deleted"; 
      }
        // fetch all articles
      $allCalendar = $calendarObj->getAllCalendar();
  } else {
    $msg = "You do not have access to this page.";
      }  
} else {
    $secmsg = "Please login to access this page";
}
  
?>
<?php 
  $pageTitle ='Mumbai Racecource Availibility Calendar Manager';        
  $design = new Design();
  $design->js='
  <script type="text/javascript" src="js/jquery.ui.core.min.js"></script>    
    <script type="text/javascript" src="js/jquery.ui.datepicker.min.js"></script>
    <script type="text/javascript">
        function confirmDelete(calendarID) {
            if (confirm ("Are you sure ?")){
                location.href="admin/availibilityManager.php?q=delete-calendar&id="+calendarID;
            }
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
    $('#calendar_date').datepicker({
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
    //echo $msg;
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
    <?php if ($_SESSION['calendar'] == "Y") { ?>
        <div class="submenu">  
              <a href="admin/availibilityManager.php?q=new-calendar">Add New Calendar Entry</a>
              <div style="float:right;">
                <a style="float:left;" href="admin/dashboard.php">Dashboard</a>
                <a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
           </div>
        </div>
              <br />   
              
              <?php if ($q=="new-calendar" || $q=="edit-calendar") { ?>              
              <form name="calendarForm" method="post" action="admin/availibilityManager.php">
                <table class="contentTable">
                    <col width="20%"><col width="80%">
                    <tr>
                        <th>Date</th>
                        <?php 
                          $date = '';
                            if ($q=="edit-calendar") {
                               // echo $calendarDetails['dividend_date'];    
                                $date = date("Y-m-d",strtotime($calendarDetails['racedate']));
                            }
                        ?>
                        <td class="alignLeft"><input type="text" name="date" id='calendar_date' value="<?php echo $date; ?>" /></td>
                    </tr>                    
                    <tr>
                        <td colspan="2">
                            <input type="submit" name="submit" value="Save" />
                            <input type="reset" name="reset" value="Clear" />
                          
                      <?php if ($q=="new-calendar") { ?>
                                <input type="hidden" name="q" value="add-calendar" />
                            <?php } elseif ($q == "edit-calendar") { ?>
                                    <input type="hidden" name="q" value="update-calendar" />
                                    <input type="hidden" name="id" value="<?php echo $calendarID; ?>" />
                            <?php  }   ?>  
                        </td>
                    </tr>
                </table>
                </form>
                <?php } ?>
              <br />
              <br />
              <table class="contentTable" style="margin-top:0px;">
                <tr>
                    <th class="thwhite alignLeft" colspan="3">To edit a calendar entry, please delete the old one and Re-add</th>
                </tr>
                <tr>
                    <th>DATE</th>                    
                    <th>ACTIONS</th>                    
                </tr>
                <?php foreach ($allCalendar as $calendarInfo) { ?>
                    <tr>                        
                        <td><?php echo date("d-m-y",strtotime($calendarInfo['racedate'])); ?></td>
                        <td>
                          <a href="admin/availibilityManager.php?id=<?php echo $calendarInfo['id'];?>&q=edit-calendar">Edit</a>
                            <a href="#" onclick="javascript: confirmDelete(<?php echo $calendarInfo['id'];?>);">Delete</a>
                        </td>
                    </tr>
                <?php } ?>
              </table>
              <br />  
    <?php } ?>            
  <?php                   
  $design->closeDiv();
  //$design->rightArea();  
 // $design->closeDiv();
  $design->closeDiv();
    $design->pageClose();
$design = NULL; // release object