<?php
  include_once('../bootstrap.php');
  //require_once('../lib/availibility.class.php');
  require_once("../lib/users.class.php");
  require_once("../lib/userchecks.php");
 require_once("../lib/erp_race_datas.class.php");
  $q = getParameterString('q','',$db);
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
      $allprerace = $preraceObj->getPreRace();

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
    
        <div class="submenu">  
              <div style="float:right;">
                <a style="float:left;" href="admin/dashboard.php">Dashboard</a>
                <a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
           </div>
        </div>
              <br />
              <a href="admin/erp_prerace.php?q=add-prerace" style="padding: 6px;margin-left: 50%;background: #018134;color: #ffff;padding-top: -2%;border: 1px solid #018134;">Add</a>
              <br />
               <?php if ($q=="add-prerace") { ?>              
                <form  method="post" action="admin/erp_prerace.php" style="margin-bottom: 3%;">
                    <input type="hidden" name="q" value="add-prerace" />
                    <table class="contentTable" >
                        <col width="20%"><col width="80%">
                        <tr>
                            <th>Date</th>
                           
                            <td class="alignLeft"><input type="text" name="date" id='prerace_date' value="" /></td>
                        </tr>                    
                        <tr>
                            <td colspan="2">
                                <input type="submit" name="submit" value="Save" />
                            </td>
                        </tr>
                    </table>
                </form>
                <?php } ?>   
              <table class="contentTable" style="margin-top:10px;   width: 58%;" align="center">
                 <tr>
                    <th colspan="2">Pre Race</th>                    
                </tr>
                <tr>
                    <th>DATE</th>                    
                    <th>ACTIONS</th>                    
                </tr>
                <?php foreach ($allprerace as $preraceInfo) { ?>
                    <tr>                        
                        <td><?php echo date("d-m-y",strtotime($preraceInfo['racedate'])); ?></td>
                        <td>
                            <a href="admin/erp_prerace.php?q=delete-prerace&id=<?php echo $preraceInfo['id'] ?>" >Delete</a>
                        </td>
                    </tr>
                <?php } ?>
              </table>
              <br />  
  <?php                   
  $design->closeDiv();
  //$design->rightArea();  
 // $design->closeDiv();
  $design->closeDiv();
    $design->pageClose();
$design = NULL; // release object