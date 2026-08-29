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
    //if ($_SESSION['postrace'] == "Y") { // check login
     
      $postraceObj = new Erpracedatas($db);
      /*$allCentres = $postraceObj->getCentresList();
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
            if ($q == "add-postrace") {
                try {
                    if($date !='' && $date!= '0000-00-00'){
                        $id = $postraceObj->insertPostraceDate($date); 
                       $msg = "New postrace entry added";
                    } else {
                        $secmsg = "Please select date";
                    }
                       
                } catch (Exception $err) {
                     $msg = $err->getMessage();
                }
            }
              /*if ($q == "update-postrace") {
                 $postraceID=getParameterNumber('id',0);    
                 try {
                    $rowsAffected = $postraceObj->updateRaceDate($postraceID,$date);
                    $msg = "postrace entry updated";
                 } catch (Exception $err) {
                     $msg = $err->getMessage();
                 }      
              }   */ 
          
         

      }
         /* if ($q=="edit-postrace") {
             $postraceID=getParameterNumber('id',0);         
             try {
                $postraceDetails = $postraceObj->getpostraceById($postraceID);
             } catch (Exception $err) {
                $msg = $err->getMessage();        
             }
          }*/
      
      if ($q=="delete-postrace") {
           $postraceID=getParameterNumber('id',0);                
           $postraceDetails = $postraceObj->deletePostraceByID($postraceID);
           $msg = "postrace entry deleted"; 
      }
        // fetch all articles
      $allpostraceFull = $postraceObj->getPostrace();
      $totalPostrace = count($allpostraceFull);
      $paging = new Pagination($pageno,15,$totalPostrace);
      $allpostrace = array_slice($allpostraceFull,($pageno-1)*15,15);

  // } else {
  //   $msg = "You do not have access to this page.";
  //     }  
} else {
    $secmsg = "Please login to access this page";
}
  
?>
<?php 
  $pageTitle ='Pre Race';        
  $design = new Design();
  $design->js='
  <script type="text/javascript" src="js/jquery.ui.core.min.js"></script>    
    <script type="text/javascript" src="js/jquery.ui.datepicker.min.js"></script>
    <script type="text/javascript">
        function confirmDelete(postraceID) {
            alert(delete-postrace);
            if (confirm ("Are you sure ?")){

                location.href="admin/erp_postrace.php?q=delete-postrace&id="+postraceID;
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

  .postrace-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
  .add-postrace-btn { display: inline-flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #1a7a45; color: #0f5c33; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; }
  .add-postrace-btn:hover { background: #e6f4ec; }
  .header-links { display: flex; align-items: center; gap: 16px; }
  .header-links a { color: #0f5c33; text-decoration: none; font-weight: 600; font-size: 14px; }
  .header-links a:hover { text-decoration: underline; }

  .postrace-form-wrap { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); max-width: 700px; }
  .postrace-form-wrap .form-row { margin-bottom: 20px; }
  .postrace-form-wrap label.form-label { display: block; font-size: 14px; font-weight: 600; color: #2b332f; margin-bottom: 8px; }
  .postrace-form-wrap input[type="text"] {
    width: 100%; border: 1px solid #e2e6e4; border-radius: 8px; padding: 10px 12px; font-size: 14px; color: #2b332f; box-sizing: border-box; font-family: inherit;
  }
  .postrace-form-wrap input[type="text"]:focus { outline: none; border-color: #1a7a45; }
  .postrace-form-wrap .form-actions { padding-top: 6px; }
  .postrace-form-wrap input[type="submit"] { background: #0f5c33; color: #fff; border: none; padding: 10px 22px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; }
  .postrace-form-wrap input[type="submit"]:hover { background: #0c4a29; }

.postrace-list-title { font-size: 17px; font-weight: 700; color: #2b332f; margin: 0 0 14px 0; }
.postrace-list { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.03); max-width: 100%; }
  .postrace-list-row { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border-bottom: 1px solid #eef0ee; }
  .postrace-list-row:last-child { border-bottom: none; }
  .postrace-list-row .postrace-date { display: flex; align-items: center; gap: 10px; font-size: 14.5px; color: #2b332f; font-weight: 500; }
  .postrace-list-row .postrace-date i { color: #0f5c33; }
  .postrace-list-row .postrace-delete { color: #c0392b; text-decoration: none; font-size: 13.5px; font-weight: 500; display: flex; align-items: center; gap: 6px; }
  .postrace-list-row .postrace-delete:hover { text-decoration: underline; }
  .postrace-list-empty { padding: 20px; text-align: center; color: #7a8c84; font-size: 14px; }

  html, body { scrollbar-width: none; -ms-overflow-style: none; }
html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }

  @media (max-width: 700px) {
    #leftArea.col-lg-9 { padding: 0 16px; }
    .postrace-header { flex-direction: column; align-items: flex-start; }
    .postrace-form-wrap { padding: 18px; }
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
    $('#postrace_date').datepicker({
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
    
        <div class="postrace-header">
              <a class="add-postrace-btn" href="admin/erp_postrace.php?q=add-postrace"><i class="fas fa-plus"></i> Add</a>
              <div class="header-links">
                <!-- <a href="admin/dashboard.php">Dashboard</a>
                <a href="admin/adminlogin.php?q=logout">Logout</a> -->
           </div>
        </div>
               <?php if ($q=="add-postrace") { ?>              
                <div class="postrace-form-wrap">
                <form  method="post" action="admin/erp_postrace.php">
                    <input type="hidden" name="q" value="add-postrace" />
                    <div class="form-row">
                        <label class="form-label" for="postrace_date">Date</label>
                        <input type="text" name="date" id='postrace_date' value="" />
                    </div>
                    <div class="form-actions">
                        <input type="submit" name="submit" value="Save" />
                    </div>
                </form>
                </div>
                <?php } ?>   

              <div class="postrace-list-title">Post Race</div>
              <div class="postrace-list">
                <?php if (empty($allpostrace)) { ?>
                    <div class="postrace-list-empty">No post race entries found.</div>
                <?php } ?>
                <?php foreach ($allpostrace as $postraceInfo) { ?>
                    <div class="postrace-list-row">
                        <div class="postrace-date"><i class="far fa-calendar-alt"></i> <?php echo date("d-m-y",strtotime($postraceInfo['racedate'])); ?></div>
                        <a class="postrace-delete" href="admin/erp_postrace.php?q=delete-postrace&id=<?php echo $postraceInfo['id'] ?>"><i class="fas fa-trash-alt"></i> Delete</a>
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