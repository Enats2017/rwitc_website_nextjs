<?php
  include_once('../bootstrap.php');
  require_once('../lib/calendar.class.php');
  require_once('../lib/availibility.class.php');
  require_once("../lib/users.class.php");
  require_once("../lib/userchecks.php");
  require_once("../lib/function.php");
  
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
  
      $calendarObj = new Calendar($db);
      $aCalObj = new AvailibilityCalendar($db);
      $allCentres = $calendarObj->getCentresList();
      $centresList = array();
      foreach($allCentres as $centre) {
          $centresList[$centre['id']] = $centre['centre'];
      }
      
      // all actions POST form submissions go here
      if (isset($_REQUEST['submit'])) {
          
          $date = getParameterString('date','',$db);
          $centreid = getParameterString('centreid','',$db);
                
          
          // save new dividend     
          if ($q == "add-calendar") {
              try {
                   $id = $calendarObj->insertCalendar($date,$centreid); 
                   if ($centreid == "1") {
                       $aCalObj->insertRaceDate($date);
                   }
                   $msg = "New calendar entry added";
             } catch (Exception $err) {
                 $msg = $err->getMessage();
             }
          }
          if ($q == "update-calendar") {
             $calendarID=getParameterNumber('id',0);    
             try {
                $rowsAffected = $calendarObj->updateCalendar($calendarID,$centreid,$date);
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
      if(isset($_GET["page"])){
        $page = (int)$_GET["page"];
      } else {
        $page = 1;
      }
      $setLimit = 30;
      $pageLimit = ($page * $setLimit) - $setLimit;
      $data_cal['setLimit'] = $setLimit;
      $data_cal['pageLimit'] = $pageLimit;
      // fetch all articles
      $allCalendar = $calendarObj->getAllCalendar($data_cal);
  } else {
    $msg = "You do not have access to this page.";
      }  
} else {
    $secmsg = "Please login to access this page";
}
  
?>
<?php 
  $pageTitle ='Calendar Manager';        
  $design = new Design();
  $design->js='
  <script type="text/javascript" src="js/jquery.ui.core.min.js"></script>    
    <script type="text/javascript" src="js/jquery.ui.datepicker.min.js"></script>
    <script type="text/javascript">
        function confirmDelete(calendarID) {
            if (confirm ("Are you sure ?")){
                location.href="admin/calendarManager.php?q=delete-calendar&id="+calendarID;
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
    $('#calendar_date').datepicker({
            showOn: 'button',
            buttonImage: '/images/calendar.gif',
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
              <a href="admin/calendarManager.php?q=new-calendar">Add New Calendar Entry</a>
              <div style="float:right;">
                <a style="float:left;" href="admin/dashboard.php">Dashboard</a>
                <a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
           </div>
        </div>
              <br />   
              
              <?php if ($q=="new-calendar" || $q=="edit-calendar") { ?>              
              <form name="calendarForm" method="post" action="admin/calendarManager.php">
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
                        <th>Centre</th>
                        <td class="alignLeft">  
                            <select name='centreid'>
                                <?php 
                                    $selected = '';
                                    //if (isset($calendarDetails['centreid'])) {  
                                        $selected = $calendarDetails['centreid'];
                                    //}
                                    echo drawOptionsFromHashtable($centresList,$selected); 
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <input type="submit" name="submit" value="Save" />
                            <input type="reset" name="reset" value="Clear" />
                          
                      <?if ($q=="new-calendar") { ?>
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
                    <th>CENTRE</th>                                       
                    <th>ACTIONS</th>                    
                </tr>
                <?php foreach ($allCalendar as $calendarInfo) { ?>
                    <tr>                        
                        <td><?php echo date("d-m-y",strtotime($calendarInfo['racedate'])); ?></td>
                        <td><?php echo $centresList[$calendarInfo['centreid']]; ?></td>
                        <td>
                          <a href="admin/calendarManager.php?id=<?php echo $calendarInfo['id'];?>&q=edit-calendar">Edit</a>
                            <a style="cursor:pointer;" onclick="javascript: confirmDelete(<?php echo $calendarInfo['id'];?>);">Delete</a>
                        </td>
                    </tr>
                <?php } ?>
              </table>
	      <?php
                // Call the Pagination Function to load Pagination.
                echo displayPaginationBelow($setLimit,$page, $db);
              ?>
              <br />  
    <?php } ?>            
  <?php                   
  $design->closeDiv();
  //$design->rightArea();  
 // $design->closeDiv();
  $design->closeDiv();
    $design->pageClose();
$design = NULL; // release object
