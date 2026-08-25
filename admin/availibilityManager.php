<?php
  include_once('../bootstrap.php');
  require_once('../lib/availibility.class.php');
  require_once('../lib/pagination.class.php');
  require_once("../lib/users.class.php");
  require_once("../lib/userchecks.php");
  
  $q = getParameterString('q','',$db);
  $pageno = getParameterNumber('pageno',1);
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
      $allCalendarFull = $calendarObj->getAllCalendar();
      $totalCalendar = count($allCalendarFull);
      $paging = new Pagination($pageno,15,$totalCalendar);
      $allCalendar = array_slice($allCalendarFull,($pageno-1)*15,15);
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

  .avail-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
  .add-avail-btn { display: inline-flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #1a7a45; color: #0f5c33; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; }
  .add-avail-btn:hover { background: #e6f4ec; }
  .header-links { display: flex; align-items: center; gap: 16px; }
  .header-links a { color: #0f5c33; text-decoration: none; font-weight: 600; font-size: 14px; }
  .header-links a:hover { text-decoration: underline; }

  .avail-form-wrap { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); max-width: 700px; }
  .avail-form-wrap .form-row { margin-bottom: 20px; }
  .avail-form-wrap label.form-label { display: block; font-size: 14px; font-weight: 600; color: #2b332f; margin-bottom: 8px; }
  .avail-form-wrap input[type="text"] {
    width: 100%; border: 1px solid #e2e6e4; border-radius: 8px; padding: 10px 12px; font-size: 14px; color: #2b332f; box-sizing: border-box; font-family: inherit;
  }
  .avail-form-wrap input[type="text"]:focus { outline: none; border-color: #1a7a45; }
  .avail-form-wrap .form-actions { display: flex; gap: 10px; padding-top: 6px; }
  .avail-form-wrap input[type="submit"], .avail-form-wrap input[type="reset"] { background: #0f5c33; color: #fff; border: none; padding: 10px 22px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; }
  .avail-form-wrap input[type="reset"] { background: #fff; color: #2b332f; border: 1px solid #e2e6e4; }
  .avail-form-wrap input[type="submit"]:hover { background: #0c4a29; }
  .avail-form-wrap input[type="reset"]:hover { background: #f5f4ee; }

  .avail-note { background: #f5f4ee; border: 1px solid #e2e6e4; color: #7a8c84; font-size: 13.5px; padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; }

  .avail-list { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.03); }
  .avail-list-row { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border-bottom: 1px solid #eef0ee; }
  .avail-list-row:last-child { border-bottom: none; }
  .avail-list-row .avail-date { display: flex; align-items: center; gap: 10px; font-size: 14.5px; color: #2b332f; font-weight: 500; }
  .avail-list-row .avail-date i { color: #0f5c33; }
  .avail-list-row .avail-actions { display: flex; gap: 16px; }
  .avail-list-row .avail-actions a { font-size: 13.5px; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 6px; }
  .avail-list-row .avail-actions a:nth-child(1) { color: #0f5c33; }
  .avail-list-row .avail-actions a:nth-child(2) { color: #c0392b; }
  .avail-list-empty { padding: 20px; text-align: center; color: #7a8c84; font-size: 14px; }

  @media (max-width: 700px) {
    #leftArea.col-lg-9 { padding: 0 16px; }
    .avail-header { flex-direction: column; align-items: flex-start; }
    .avail-form-wrap { padding: 18px; }
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
        <div class="avail-header">
              <a class="add-avail-btn" href="admin/availibilityManager.php?q=new-calendar"><i class="fas fa-plus"></i> Add New Calendar Entry</a>
              <div class="header-links">
                <!-- <a href="admin/dashboard.php">Dashboard</a>
                <a href="admin/adminlogin.php?q=logout">Logout</a> -->
           </div>
        </div>
              
              <?php if ($q=="new-calendar" || $q=="edit-calendar") { ?>              
              <div class="avail-form-wrap">
              <form name="calendarForm" method="post" action="admin/availibilityManager.php">
                <div class="form-row">
                    <label class="form-label" for="calendar_date">Date</label>
                    <?php 
                      $date = '';
                        if ($q=="edit-calendar") {
                           // echo $calendarDetails['dividend_date'];    
                            $date = date("Y-m-d",strtotime($calendarDetails['racedate']));
                        }
                    ?>
                    <input type="text" name="date" id='calendar_date' value="<?php echo $date; ?>" />
                </div>
                <div class="form-actions">
                    <input type="submit" name="submit" value="Save" />
                    <input type="reset" name="reset" value="Clear" />
                  
                    <?php if ($q=="new-calendar") { ?>
                                <input type="hidden" name="q" value="add-calendar" />
                            <?php } elseif ($q == "edit-calendar") { ?>
                                    <input type="hidden" name="q" value="update-calendar" />
                                    <input type="hidden" name="id" value="<?php echo $calendarID; ?>" />
                            <?php  }   ?>  
                </div>
              </form>
              </div>
                <?php } ?>

              <div class="avail-note"><i class="fas fa-circle-info"></i> To edit a calendar entry, please delete the old one and Re-add</div>

              <div class="avail-list">
                <?php if (empty($allCalendar)) { ?>
                    <div class="avail-list-empty">No calendar entries found.</div>
                <?php } ?>
                <?php foreach ($allCalendar as $calendarInfo) { ?>
                    <div class="avail-list-row">
                        <div class="avail-date"><i class="far fa-calendar-alt"></i> <?php echo date("d-m-y",strtotime($calendarInfo['racedate'])); ?></div>
                        <div class="avail-actions">
                          <a href="admin/availibilityManager.php?id=<?php echo $calendarInfo['id'];?>&q=edit-calendar"><i class="fas fa-edit"></i> Edit</a>
                            <a href="#" onclick="javascript: confirmDelete(<?php echo $calendarInfo['id'];?>);"><i class="fas fa-trash-alt"></i> Delete</a>
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
  $design->closeDiv();
  $design->pageClose();
  $design = NULL; // release object  