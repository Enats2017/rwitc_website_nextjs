<?php
include_once('../bootstrap.php');
require_once('../lib/trackwork.class.php');
require_once("../lib/users.class.php");
require_once("../lib/userchecks.php");
  
  
  $q = getParameterString('q','',$db);
  
  $trackworkObj = new Trackwork($db);
  session_start();                    
if(isset($_COOKIE['uid'])){                    
  $uid = $_COOKIE['uid'];    
} else {
  $uid = 0;
}             
$userObj = new Users($db);  

 $msg = $secmsg = "";
 
 
if (isAdminlogin()) {
    if (($_SESSION['role'] == "ADMIN" || ($_SESSION['role'] == "BACK_OFFICE"))) { // check login
        $trackworkDetails = [
            'trackwork_date' => '',
            'trackwork'      => '',
            'published'      => 'Y'
        ];
        
       if (get_magic_quotes_gpc()) {
            function stripslashes_deep($value)
            {
                $value = is_array($value) ?
                            array_map('stripslashes_deep', $value) :
                            stripslashes($value);

                return $value;
            }

            $_POST = array_map('stripslashes_deep', $_POST);
        //    $_GET = array_map('stripslashes_deep', $_GET);
          //  $_COOKIE = array_map('stripslashes_deep', $_COOKIE);
            $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
        }

          
          // all actions POST form submissions go here
          if (isset($_REQUEST['submit'])) {
              
              $trackworkDate = getParameterString('trackwork_date','',$db);
              $trackwork = getParameterString('trackwork','',$db);
              $published = getParameterString('published','N',$db);
              
              
              // handle checkbox state
              if (strtolower($published)== "on") {
                 $published="Y";
              }     
              // save new article
              if ($q == "add-trackwork") {
                  try {
                    $trackworkID = $trackworkObj->insertTrackwork($trackwork,$trackworkDate,$published); 
                 } catch (Exception $err) {
                     echo $err->getMessage();
                 }
              }
          
              //update new article 
              if ($q == "update-trackwork") {
                 $trackworkID=getParameterNumber('id',0);    
                 try {
                    $rowsAffected = $trackworkObj->updateTrackwork($trackworkID,$trackwork,$trackworkDate,$published);
                 } catch (Exception $err) {
                     echo $err->getMessage();
                 }      
              }
          }
          
          if ($q=="edit-trackwork") {
             $trackworkID=getParameterNumber('id',0);         
             try {
                $trackworkDetails = $trackworkObj->getTrackworkByID($trackworkID);        
             } catch (Exception $err) {
                $msg = $err->getMessage();
                echo $msg;
             }
          }
          if ($q == "delete-trackwork") {
             $trackworkID=getParameterNumber('id',0);         
             try {
                $trackworkObj->deleteTrackwork($trackworkID);
                $msg = "Trackwork Deleted";
                // clear action
                $q="";
             } catch (Exception $err) {
                $msg = $err->getMessage();
                echo $msg;
             }
          }
          // fetch all articles
          $allTrackwork = $trackworkObj->getAllTrackwork();
         
          
      
          
      } else {
        $msg = "You do not have access to this page.";
      }  
} else {
    $secmsg = "Please login to access this page";
}
     
$pageTitle ='Articles Manager';        
// create a template object
$design = new Design();  

$design->js='
<script type="text/javascript" src="lib/ckeditor/ckeditor.js"></script>
<link type="text/css" href="css/jquery.ui.all.css" rel="stylesheet" />    
<script type="text/javascript" src="js/jquery.ui.core.min.js"></script>    
<script type="text/javascript" src="js/jquery.ui.datepicker.min.js"></script>
     
    
<script type="text/javascript">
     function confirmDelete(trackworkID) {       
       if (confirm(\'Are you Sure?\')) {          
            location.href = "admin/trackworkManager.php?id="+trackworkID+"&q=delete-trackwork";
        }    
    }
       
</script>
';
$design->css ='';
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
$(\"#trackwork_date\").datepicker({
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
    <?php if (empty($msg) && empty($secmsg)) { ?>
        <div class="submenu">
           <a href="admin/trackworkManager.php?q=new-trackwork">Add New Trackword</a>      
          <div style="float:right;">
                <a style="float:left;" href="admin/dashboard.php">Dashboard</a>
                <a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
           </div>
        </div>
          <br />          
                <?php if ($q=="new-trackwork" || $q=="edit-trackwork") { ?>              
              <form name="trackworkForm" method="post" action="admin/trackworkManager.php">
                <table class="contentTable">
                    <col width="20%"><col width="80%">
                    <tr>
                        <th>TrackworkDate</th>
                        <?php 
                          $trackworkDate = '';
                            if ($q=="edit-trackwork") {
                                $trackworkDate = date("Y-m-d",strtotime($trackworkDetails['trackwork_date']));
                            }
                        ?>
                        <td class="alignLeft"><input type="text" name="trackwork_date" id='trackwork_date' value="<?php echo   $trackworkDate; ?>" /></td>
                    </tr>
                    <tr>
                        <th>TrackWork</th>
                        <td class="alignLeft"><textarea name="trackwork" id="trackwork"><?php echo $trackworkDetails['trackwork']; ?></textarea></td>
                    </tr>
                    <tr>
                    <tr>
                        <th>Publish</th>
                        <td class="alignLeft">                            
                            <?php 
                            $checked = "checked=\"checked\"";
                            if ($trackworkDetails['published'] == "N") {
                                $checked ="";        
                            } 
                            ?>
                            <input type="checkbox" name="published" id='published' <?php echo $checked; ?> />
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <input type="submit" name="submit" value="Save" />
                            <input type="reset" name="reset" value="Clear" />
                            <?php if ($q=="new-trackwork") { ?>
                                <input type="hidden" name="q" value="add-trackwork" />
                            <?php } elseif ($q == "edit-trackwork") { ?>
                                    <input type="hidden" name="q" value="update-trackwork" />
                                    <input type="hidden" name="id" value="<?php echo $trackworkID; ?>" />
                            <?php  }   ?>
                        </td>
                    </tr>
                </table>
                </form>
                <script type="text/javascript">
                 //<![CDATA[
                CKEDITOR.replace( 'trackwork',
                    {
                        fullPage : true,
                        filebrowserBrowseUrl : '/lib/ckfinder/ckfinder.html',
                        filebrowserImageBrowseUrl : '/lib/ckfinder/ckfinder.html?type=Images',
                        filebrowserFlashBrowseUrl : '/lib/ckfinder/ckfinder.html?type=Flash',
                        filebrowserUploadUrl : '/imageUpload.php'
                    });
                //]]>
                </script>
                <?php } ?>
              
              <hr />                    
              <br />
              <table class="contentTable">
                <tr>
                    <th>trackwork_date</th>
                    <th>Published</th>
                    <th>Action</th>                    
                </tr>
                <?php foreach ($allTrackwork as $trackworkInfo) { ?>
                    <tr>                        
                        <td><?php echo date("Y-m-d",strtotime($trackworkInfo['trackwork_date'])); ?></td>
                        <td><?php echo $trackworkInfo['published']; ?></td>
                        <td>
                            <a href="admin/trackworkManager.php?id=<?php echo $trackworkInfo['id'];?>&q=edit-trackwork">Edit</a>
                            <a href="javascript:void(0);" onclick="javascript: confirmDelete(<?php echo $trackworkInfo['id']; ?>);" >Delete</a>
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
