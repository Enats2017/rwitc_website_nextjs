<?php
  include_once('../bootstrap.php');
  require_once('../lib/ratingschange.class.php');
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
    if ($_SESSION['rating_change'] == "Y") { // check login
        $rcObj = new RatingsChange($db);  
      // all actions POST form submissions go here
      if (isset($_REQUEST['submit'])) {
          
          $date = getParameterString('date','',$db);
                
          
          // save new dividend     
          if ($q == "add-rating") {
              try {
                  if (!$_FILES['ratingFile']['error'])  { // error =0  
                    $filename = $_FILES['ratingFile']['name'];  
                    $filename = basename($filename,".HTM")."_$date.HTM"; 
                    if (move_uploaded_file($_FILES['ratingFile']['tmp_name'],$base.RATINGSCHANGE_BASE."/".$filename)) {
                        $id = $rcObj->insertRatingsChange($date,$filename); 
                    }
                  }
             } catch (Exception $err) {
                 $msg = $err->getMessage();
             }
          }
      }
      
      if ($q=="delete-rating") {
           $ratingID=getParameterNumber('id',0);                
           $ratingDetails = $rcObj->getRatingsChangeById($ratingID); 
           try {
               unlink($base.RATINGSCHANGE_BASE."/".$ratingDetails['filename']);   
               $rcObj->deleteRatingsChangeByID($ratingID);
           } catch (Exception $err) {
               $msg = $err->getMessage();        
           }
      }

      if(!isset($date)){
        $date = '';
      }

      // fetch all articles
      $allRating = $rcObj->getAllRatingsChange();
    } else {
        $msg = "You do not have access to this page.";
    }  
  } else {
        $secmsg = "Please login to access this page";
  } 
  $pageTitle ='Ratings Change Manager';        
  $design = new Design();
  $design->js='
  <script type="text/javascript" src="js/jquery.ui.core.min.js"></script>    
    <script type="text/javascript" src="js/jquery.ui.datepicker.min.js"></script>
    <script type="text/javascript">
        function confirmDelete(ratingID) {
            if (confirm ("Are you sure ?")){
                location.href="admin/ratingsChangeManager.php?q=delete-rating&id="+ratingID;
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
    $('#rating_date').datepicker({
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
    <?php if ($_SESSION['rating_change'] == "Y") { ?>
              <div class="submenu">  
                <a href="admin/ratingsChangeManager.php?q=new-report">Add New Rating Change</a>
                 <div style="float:right;">
                    <a style="float:left;" href="admin/dashboard.php">Dashboard</a>
                    <a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
                  </div>
              </div>
              <br />  
              <br />
              <?php if ($q=="new-report") { ?>              
              <form name="dividendForm" method="post" action="admin/ratingsChangeManager.php" enctype="multipart/form-data">
                <table class="contentTable">
                    <col width="20%"><col width="80%">
                    <tr>
                        <th>Date</th>
                        <td class="alignLeft"><input type="text" name="date" id='rating_date' value="<?php echo $date; ?>" /></td>
                    </tr>
                    <tr>         
                        <th>Upload File</th>
                        <td class="alignLeft"><input type="file" name="ratingFile" /></td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <input type="submit" name="submit" value="Save" />
                            <input type="reset" name="reset" value="Clear" />
                            <input type="hidden" name="q" value="add-rating" />
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
                    <th class="thwhite alignLeft" colspan="3">To edit a Ratings Change entry, please delete the old one and Re-add</th>
                </tr>
                <tr>
                    <th>DATE</th>
                    <th>ACTIONS</th>                    
                </tr>
                <?php foreach ($allRating as $rating) { ?>
                    <tr>                        
                        <td><?php echo date("d-m-y",strtotime($rating['racedate'])); ?></td>
                        <td>
                            <a href="javascript:void(0)" onclick="javascript: confirmDelete(<?php echo $rating['id'];?>);">Delete</a>
                        </td>
                    </tr>
                <?php } ?>
              </table>
<?php } ?>              
<?php                   
  $design->closeDiv();
  //$design->rightArea();  
  //$design->closeDiv();
  $design->closeDiv();
    $design->pageClose();
$design = NULL; // release object