<?php
  include_once('../bootstrap.php');
  //require_once('../lib/availibility.class.php');
  require_once("../lib/users.class.php");
  require_once("../lib/userchecks.php");
 require_once("../lib/erp_race_datas.class.php");
 require_once('../lib/pagination.class.php');
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
    //if ($_SESSION['prerace'] == "Y") { // check login
     
      $preraceObj = new Erpracedatas($db);
      /*$allCentres = $preraceObj->getCentresList();
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
            if ($q == "add-prerace") {
                try {
                    if($date !='' && $date!= '0000-00-00'){
                        $id = $preraceObj->insertPreRaceDate($date); 
                       $msg = "New prerace entry added";
                    } else {
                        $secmsg = "Please select date";
                    }
                       
                } catch (Exception $err) {
                     $msg = $err->getMessage();
                }
            }
              /*if ($q == "update-prerace") {
                 $preraceID=getParameterNumber('id',0);    
                 try {
                    $rowsAffected = $preraceObj->updateRaceDate($preraceID,$date);
                    $msg = "prerace entry updated";
                 } catch (Exception $err) {
                     $msg = $err->getMessage();
                 }      
              }   */ 
          
         

      }
         /* if ($q=="edit-prerace") {
             $preraceID=getParameterNumber('id',0);         
             try {
                $preraceDetails = $preraceObj->getpreraceById($preraceID);
             } catch (Exception $err) {
                $msg = $err->getMessage();        
             }
          }*/
      
      if ($q=="delete-prerace") {
           $preraceID=getParameterNumber('id',0);                
           $preraceDetails = $preraceObj->deletepreraceByID($preraceID);
           $msg = "Prerace entry deleted"; 
      }
        // fetch all articles
      $allpreraceFull = $preraceObj->getPreRace();
      $totalPrerace = count($allpreraceFull);
      $paging = new Pagination($pageno,15,$totalPrerace);
      $allprerace = array_slice($allpreraceFull,($pageno-1)*15,15);

  // } else {
  //   $msg = "You do not have access to this page.";
  //     }  
} else {
    $secmsg = "Please login to access this page";
}
  
?>
<?php 
  $pageTitle ='Mumbai Racecource Availibility prerace Manager';        
  $design = new Design();
  $design->js='
  <script type="text/javascript" src="js/jquery.ui.core.min.js"></script>    
    <script type="text/javascript" src="js/jquery.ui.datepicker.min.js"></script>
    <script type="text/javascript">
        function confirmDelete(preraceID) {
            alert(delete-prerace);
            if (confirm ("Are you sure ?")){

                location.href="admin/erp_prerace.php?q=delete-prerace&id="+preraceID;
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

  .prerace-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
  .add-prerace-btn { display: inline-flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #1a7a45; color: #0f5c33; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; }
  .add-prerace-btn:hover { background: #e6f4ec; }
  .header-links { display: flex; align-items: center; gap: 16px; }
  .header-links a { color: #0f5c33; text-decoration: none; font-weight: 600; font-size: 14px; }
  .header-links a:hover { text-decoration: underline; }

  .prerace-form-wrap { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); max-width: 700px; }
  .prerace-form-wrap .form-row { margin-bottom: 20px; }
  .prerace-form-wrap label.form-label { display: block; font-size: 14px; font-weight: 600; color: #2b332f; margin-bottom: 8px; }
  .prerace-form-wrap input[type="text"] {
    width: 100%; border: 1px solid #e2e6e4; border-radius: 8px; padding: 10px 12px; font-size: 14px; color: #2b332f; box-sizing: border-box; font-family: inherit;
  }
  .prerace-form-wrap input[type="text"]:focus { outline: none; border-color: #1a7a45; }
  .prerace-form-wrap .form-actions { padding-top: 6px; }
  .prerace-form-wrap input[type="submit"] { background: #0f5c33; color: #fff; border: none; padding: 10px 22px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; }
  .prerace-form-wrap input[type="submit"]:hover { background: #0c4a29; }

.prerace-list-title { font-size: 17px; font-weight: 700; color: #2b332f; margin: 0 0 14px 0; }
.prerace-list { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.03); max-width: 100%; }
  .prerace-list-row { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border-bottom: 1px solid #eef0ee; }
  .prerace-list-row:last-child { border-bottom: none; }
  .prerace-list-row .prerace-date { display: flex; align-items: center; gap: 10px; font-size: 14.5px; color: #2b332f; font-weight: 500; }
  .prerace-list-row .prerace-date i { color: #0f5c33; }
  .prerace-list-row .prerace-delete { color: #c0392b; text-decoration: none; font-size: 13.5px; font-weight: 500; display: flex; align-items: center; gap: 6px; }
  .prerace-list-row .prerace-delete:hover { text-decoration: underline; }
  .prerace-list-empty { padding: 20px; text-align: center; color: #7a8c84; font-size: 14px; }

  html, body { scrollbar-width: none; -ms-overflow-style: none; }
html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; } 

  @media (max-width: 700px) {
    #leftArea.col-lg-9 { padding: 0 16px; }
    .prerace-header { flex-direction: column; align-items: flex-start; }
    .prerace-form-wrap { padding: 18px; }
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
    $('#prerace_date').datepicker({
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
    
        <div class="prerace-header">
              <a class="add-prerace-btn" href="admin/erp_prerace.php?q=add-prerace"><i class="fas fa-plus"></i> Add</a>
              <div class="header-links">
                <!-- <a href="admin/dashboard.php">Dashboard</a>
                <a href="admin/adminlogin.php?q=logout">Logout</a> -->
           </div>
        </div>
               <?php if ($q=="add-prerace") { ?>              
                <div class="prerace-form-wrap">
                <form  method="post" action="admin/erp_prerace.php">
                    <input type="hidden" name="q" value="add-prerace" />
                    <div class="form-row">
                        <label class="form-label" for="prerace_date">Date</label>
                        <input type="text" name="date" id='prerace_date' value="" />
                    </div>
                    <div class="form-actions">
                        <input type="submit" name="submit" value="Save" />
                    </div>
                </form>
                </div>
                <?php } ?>   

              <div class="prerace-list-title">Pre Race</div>
              <div class="prerace-list">
                <?php if (empty($allprerace)) { ?>
                    <div class="prerace-list-empty">No pre race entries found.</div>
                <?php } ?>
                <?php foreach ($allprerace as $preraceInfo) { ?>
                    <div class="prerace-list-row">
                        <div class="prerace-date"><i class="far fa-calendar-alt"></i> <?php echo date("d-m-y",strtotime($preraceInfo['racedate'])); ?></div>
                        <a class="prerace-delete" href="admin/erp_prerace.php?q=delete-prerace&id=<?php echo $preraceInfo['id'] ?>"><i class="fas fa-trash-alt"></i> Delete</a>
                    </div>
                <?php } ?>
              </div>
              <?php $paging->writePagination(); ?>
  <?php                   
  $design->closeDiv();
  $design->writeLeftPanel();
  $design->closeDiv();
    $design->pageClose();
$design = NULL; // release object