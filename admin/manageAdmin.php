<?php
include_once('../bootstrap.php');
require_once('../lib/admin.class.php');
include_once('../lib/pagination.class.php');
require_once("../lib/users.class.php");
require_once("../lib/userchecks.php");
session_start();  
$q = getParameterString('q','',$db);
$uid = $_SESSION['uid'];             
$userObj = new Users($db);

$msg = $secmsg = "";
$pageno = getParameterNumber('pageno',1);
  
$adminsObj = new Admin($db);
if (isAdminlogin()) {
    if ($_SESSION['adminusers'] == "Y") { // check login
        if ($q == "save-admin") {
            $username = getParameterString('username','',$db);
            $pass1 = getParameterString('pass1','',$db);
            $pass2 = getParameterString('pass2','',$db);
            $firstname= getParameterString('firstname','',$db);
            $lastname = getParameterString('lastname','',$db);
            //$role = getParameterString('userRole','',$db);
            $active = getParameterString('active','',$db);
            $articles = (getParameterString('articles','',$db)=="on")? "Y":"N";
            $raceHistory  = (getParameterString('race_history','',$db)=="on")? "Y":"N";
            $sendMailer   = (getParameterString('send_mailer','',$db)=="on")? "Y":"N";
            $ratingChange = (getParameterString('rating_change','',$db)=="on")? "Y":"N";
            $gallery  = (getParameterString('gallery','',$db)=="on")? "Y":"N";
            $video  = (getParameterString('video','',$db)=="on")? "Y":"N";
            $dividends  = (getParameterString('dividends','',$db)=="on")? "Y":"N";
            $stewardsReport  = (getParameterString('stewards_report','',$db)=="on")? "Y":"N";
            $raceDayReport  = (getParameterString('race_day_report','',$db)=="on")? "Y":"N";
            $calendar  = (getParameterString('calendar','',$db)=="on")? "Y":"N";
            $prakashGosavi  = (getParameterString('prakash_gosavi','',$db)=="on")? "Y":"N";
            $polls  = (getParameterString('polls','',$db)=="on")? "Y":"N";
            $adminUsers  = (getParameterString('adminusers','',$db)=="on")? "Y":"N";
            $workingManager  = (getParameterString('workingManager','',$db)=="on")? "Y":"N";
            $bannerManager  = (getParameterString('bannerManager','',$db)=="on")? "Y":"N";
            $tickerManager  = (getParameterString('tickerManager','',$db)=="on")? "Y":"N";
            $sponsorManager  = (getParameterString('sponsorManager','',$db)=="on")? "Y":"N";
            $sponsorofthedayManager  = (getParameterString('sponsorofthedayManager','',$db)=="on")? "Y":"N";
            $horseweightManager  = (getParameterString('horseweightManager','',$db)=="on")? "Y":"N";
            $racedataManager  = (getParameterString('racedataManager','',$db)=="on")? "Y":"N";
            $configManager  = (getParameterString('configManager','',$db)=="on")? "Y":"N";
            $mailManager  = (getParameterString('mailManager','',$db)=="on")? "Y":"N";
            if ($active == "on") {
                $active = "Y";
            } else {
                $active = "N";
            }
            if ($pass1 == "" || $pass2 == "" || $username == "") {
                $msg = "Username / Passwords cannot be blank";
            } else if ($pass1 !== $pass2) {
                  $msg = "Both passwords do not match";
            } else {
                 try {
                    $adminsObj->insertAdmin($username,$pass1,$firstname,$lastname,$active,$articles,$raceHistory,$sendMailer,$ratingChange,$gallery,$video,$dividends,$stewardsReport,$raceDayReport,$calendar,$prakashGosavi,$polls,$adminUsers,$workingManager,$bannerManager,$tickerManager,$sponsorManager, $sponsorofthedayManager, $horseweightManager, $racedataManager, $configManager, $mailManager);
                    $msg = "New Admin Created";
                    $q = "";
                 } catch (Exception $err) {
                     echo $err->getMessage();    
                     $msg = "Cannot Create admin";
                 }
            }
        }
        if ($q == "edit-admin") {
            $adminID = getParameterNumber('id',0);
            $adminInfo = $adminsObj->getAdminById($adminID);
        } 
        
        if ($q == "update-admin") {        
            $adminID = getParameterNumber('id',0);
            $username = getParameterString('username','',$db);            
            $firstname= getParameterString('firstname','',$db);
            $lastname = getParameterString('lastname','',$db);
            //$role = getParameterString('userRole','',$db);
            $active = getParameterString('active','',$db);
            $articles = (getParameterString('articles','',$db)=="on")? "Y":"N";
            $raceHistory  = (getParameterString('race_history','',$db)=="on")? "Y":"N";
            $sendMailer   = (getParameterString('send_mailer','',$db)=="on")? "Y":"N";
            $ratingChange = (getParameterString('rating_change','',$db)=="on")? "Y":"N";
            $gallery  = (getParameterString('gallery','',$db)=="on")? "Y":"N";
            $video  = (getParameterString('video','',$db)=="on")? "Y":"N";
            $dividends  = (getParameterString('dividends','',$db)=="on")? "Y":"N";
            $stewardsReport  = (getParameterString('stewards_report','',$db)=="on")? "Y":"N";
            $raceDayReport  = (getParameterString('race_day_report','',$db)=="on")? "Y":"N";
            $calendar  = (getParameterString('calendar','',$db)=="on")? "Y":"N";
            $prakashGosavi  = (getParameterString('prakash_gosavi','',$db)=="on")? "Y":"N";
            $polls  = (getParameterString('polls','',$db)=="on")? "Y":"N";
            $adminUsers  = (getParameterString('adminusers','',$db)=="on")? "Y":"N";
            $workingManager  = (getParameterString('workingManager','',$db)=="on")? "Y":"N";
            $bannerManager  = (getParameterString('bannerManager','',$db)=="on")? "Y":"N";
            $tickerManager  = (getParameterString('tickerManager','',$db)=="on")? "Y":"N";
            $sponsorManager  = (getParameterString('sponsorManager','',$db)=="on")? "Y":"N";
            $sponsorofthedayManager  = (getParameterString('sponsorofthedayManager','',$db)=="on")? "Y":"N";
            $horseweightManager  = (getParameterString('horseweightManager','',$db)=="on")? "Y":"N";
            $racedataManager  = (getParameterString('racedataManager','',$db)=="on")? "Y":"N";
            $configManager  = (getParameterString('configManager','',$db)=="on")? "Y":"N";
            $mailManager  = (getParameterString('mailManager','',$db)=="on")? "Y":"N";
            //echo $bannerManager;exit;
            if ($active == "on") {
                $active = "Y";
            } else {
                $active = "N";
            }
            try {
                $adminsObj->updateAdmin($adminID,$username,$firstname,$lastname,$active,$articles,$raceHistory,$sendMailer,$ratingChange,$gallery,$video,$dividends,$stewardsReport,$raceDayReport,$calendar,$prakashGosavi,$polls,$adminUsers,$workingManager,$bannerManager,$tickerManager,$sponsorManager, $sponsorofthedayManager, $horseweightManager, $racedataManager, $configManager, $mailManager);
                $msg = "Admin Updated";
                $q = "";
             } catch (Exception $err) {
                 echo $err->getMessage();    
                 $msg = "Cannot updated admin";
             }
            
        }
        if ($q == "delete-admin") {
           $adminID = getParameterNumber('id',0);
           try {
                $adminsObj->deleteAdmin($adminID);
                $msg = "Admin Delete";
                $q = "";
             } catch (Exception $err) {
                 echo $err->getMessage();    
                 $msg = "Cannot delete admin";
             } 
        }
        if ($q == "update-password") {
           $adminID = getParameterNumber('id',0); 
           $pass1 = getParameterString('pass1','',$db);
           $pass2 = getParameterString('pass2','',$db);
            if ($pass1 == "" || $pass2 == "") {
                $msg = "Passwords cannot be blank";
                 $q = ""; 
            } else if ($pass1 !== $pass2) {
                  $msg = "Both passwords do not match";
                   $q = ""; 
            } else {
                try {
                    $adminsObj->updatepassword($adminID,$pass1);
                    $msg = "Password Updated";                    
                     $q = ""; 
                }catch (Exception $err) {
                    $msg = "Cannot change password";                    
                     $q = ""; 
                }
            }              
        }
        if(!isset($adminInfo['username'])){
            $adminInfo['username'] = '';
        }
        if(!isset($adminInfo['firstname'])){
            $adminInfo['firstname'] = '';
        }
        if(!isset($adminInfo['lastname'])){
            $adminInfo['lastname'] = '';
        }
        if(!isset($adminInfo['active'])){
            $adminInfo['active'] = 'N';
        }
        if(!isset($adminInfo['articles'])){
            $adminInfo['articles'] = 'N';
        }
        if(!isset($adminInfo['race_history'])){
            $adminInfo['race_history'] = 'N';
        }
        if(!isset($adminInfo['send_mailer'])){
            $adminInfo['send_mailer'] = 'N';
        }
        if(!isset($adminInfo['rating_change'])){
            $adminInfo['rating_change'] = 'N';
        }
        if(!isset($adminInfo['gallery'])){
            $adminInfo['gallery'] = 'N';
        }
        if(!isset($adminInfo['video'])){
            $adminInfo['video'] = 'N';
        }
        if(!isset($adminInfo['dividends'])){
            $adminInfo['dividends'] = 'N';
        }
        if(!isset($adminInfo['stewards_report'])){
            $adminInfo['stewards_report'] = 'N';
        }
        if(!isset($adminInfo['race_day_report'])){
            $adminInfo['race_day_report'] = 'N';
        }
        if(!isset($adminInfo['calendar'])){
            $adminInfo['calendar'] = 'N';
        }
        if(!isset($adminInfo['prakash_gosavi'])){
            $adminInfo['prakash_gosavi'] = 'N';
        }
        if(!isset($adminInfo['polls'])){
            $adminInfo['polls'] = 'N';
        }
        if(!isset($adminInfo['adminusers'])){
            $adminInfo['adminusers'] = 'N';
        }
        if(!isset($adminInfo['workingManager'])){
            $adminInfo['workingManager'] = 'N';
        }
        if(!isset($adminInfo['bannerManager'])){
            $adminInfo['bannerManager'] = 'N';
        }
        if(!isset($adminInfo['sponsorManager'])){
            $adminInfo['sponsorManager'] = 'N';
        }
        if(!isset($adminInfo['sponsorofthedayManager'])){
            $adminInfo['sponsorofthedayManager'] = 'N';
        }
        if(!isset($adminInfo['horseweightManager'])){
            $adminInfo['horseweightManager'] = 'N';
        }
        if(!isset($adminInfo['racedataManager'])){
            $adminInfo['racedataManager'] = 'N';
        }
        if(!isset($adminInfo['configManager'])){
            $adminInfo['configManager'] = 'N';
        }
        if(!isset($adminInfo['mailManager'])){
            $adminInfo['mailManager'] = 'N';
        }
    } else {
        $msg = "You do not have access to this page.";
    }  
} else {
    $secmsg = "Please login to access this page";
}
$pageTitle ='Admin manager';        
// create a template object
$design = new Design();  

$design->js='
<script type="text/javascript">
    function confirmDelete(adminID) {
        if (confirm ("Are you sure ?")){
            location.href="admin/manageAdmin.php?q=delete-admin&id="+adminID;
        }
    }
</script>
';
$design->css ='';
$design->jqueryJs = ""; 
$design->startPage("$pageTitle");  
$design->writeLogoTickerMenu();
$design->openDiv("contentWrapper");
$design->openDiv("infoWrapper");
$design->openDiv("leftArea");
$allAdmin = $adminsObj->getAllAdmins();
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
    <?php if ($_SESSION['adminusers'] == "Y") { ?>
        <div class="submenu">
          <a href="admin/manageAdmin.php?q=add-admin">Add New Admin</a>
          <div style="float:right;">
                <a style="float:left;" href="admin/dashboard.php">Dashboard</a>
                <a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
           </div>
        </div>
          <br />
            <br />            
            <?php if ($q == "add-admin" || $q == "edit-admin") { ?>
                <h2>Add New Admin</h2>
                <hr />
                <br />
                  <form action="admin/manageAdmin.php" method="post">
                  <table class="contentTable">                    
                    <tr>
                        <th>First name</th>
                        <td class="alignLeft"><input type="text" name="firstname" value="<?php echo $adminInfo['firstname']; ?>" /></td>
                    </tr>
                    <tr>
                        <th>Last Name</th>
                        <td class="alignLeft"><input type="text" name="lastname" value="<?php echo $adminInfo['lastname']; ?>" /></td>
                    </tr>                   
                    <tr>
                        <th>Username</th>
                        <td class="alignLeft"><input type="text" name="username" value="<?php echo $adminInfo['username']; ?>" /></td>
                    </tr>
                    <?php if ($q == "add-admin") { ?>
                    <tr>
                        <th>Password</th>
                        <td class="alignLeft"><input type="password" name="pass1" value="" /></td>
                    </tr>
                    <tr>
                        <th>Re-enter Password</th>
                        <td class="alignLeft"><input type="password" name="pass2" value="" /></td>
                    </tr>
                    <?php } ?>
                    <tr>
                        <th>Active</th>
                        <td class="alignLeft"><input type="checkbox" name="active" <?php echo ($adminInfo['active'] == "Y")? 'checked="checked"': ''; ?>/></td>
                    </tr>
                    <tr>
                        <th colspan="2">Admin Permissions</th>
                    </tr>
                    <tr>
                        <th>Manage Articles</th>
                        <td class="alignLeft"><input type="checkbox" name="articles" <?php echo ($adminInfo['articles'] == "Y")? 'checked="checked"': ''; ?>/></td>
                    </tr>                    
                    <tr>
                        <th>Manage Race History</th>
                        <td class="alignLeft"><input type="checkbox" name="race_history" <?php echo ($adminInfo['race_history'] == "Y")? 'checked="checked"': ''; ?>/></td>
                    </tr>
                    <tr>
                        <th>Send Mailers</th>
                        <td class="alignLeft"><input type="checkbox" name="send_mailer" <?php echo ($adminInfo['send_mailer'] == "Y")? 'checked="checked"': ''; ?>/></td>
                    </tr>
                    <tr>
                        <th>Rating Change</th>
                        <td class="alignLeft"><input type="checkbox" name="rating_change" <?php echo ($adminInfo['rating_change'] == "Y")? 'checked="checked"': ''; ?>/></td>
                    </tr>
                    <tr>
                        <th>Gallery</th>
                        <td class="alignLeft"><input type="checkbox" name="gallery" <?php echo ($adminInfo['gallery'] == "Y")? 'checked="checked"': ''; ?>/></td>
                    </tr>
                    <tr>
                        <th>Video</th>
                        <td class="alignLeft"><input type="checkbox" name="video" <?php echo ($adminInfo['video'] == "Y")? 'checked="checked"': ''; ?>/></td>
                    </tr>
                    <tr>
                        <th>Dividends</th>
                        <td class="alignLeft"><input type="checkbox" name="dividends" <?php echo ($adminInfo['dividends'] == "Y")? 'checked="checked"': ''; ?>/></td>
                    </tr>
                    <tr>
                        <th>Stewards Report</th>
                        <td class="alignLeft"><input type="checkbox" name="stewards_report" <?php echo ($adminInfo['stewards_report'] == "Y")? 'checked="checked"': ''; ?>/></td>
                    </tr>
                    <tr>
                        <th>Race Day report</th>
                        <td class="alignLeft"><input type="checkbox" name="race_day_report" <?php echo ($adminInfo['race_day_report'] == "Y")? 'checked="checked"': ''; ?>/></td>
                    </tr>
                    <tr>
                        <th>Calendar</th>
                        <td class="alignLeft"><input type="checkbox" name="calendar" <?php echo ($adminInfo['calendar'] == "Y")? 'checked="checked"': ''; ?>/></td>
                    </tr>
                    <tr>
                        <th>Prakash Gosavi</th>
                        <td class="alignLeft"><input type="checkbox" name="prakash_gosavi" <?php echo ($adminInfo['prakash_gosavi'] == "Y")? 'checked="checked"': ''; ?>/></td>
                    </tr>
                    <tr>
                        <th>Polls</th>
                        <td class="alignLeft"><input type="checkbox" name="polls" <?php echo ($adminInfo['polls'] == "Y")? 'checked="checked"': ''; ?>/></td>
                    </tr>
                    <tr>
                        <th>Manage Admins</th>
                        <td class="alignLeft"><input type="checkbox" name="adminusers" <?php echo ($adminInfo['adminusers'] == "Y")? 'checked="checked"': ''; ?>/></td>
                    </tr>
                    <tr>
                        <th>Manage Working Manager</th>
                        <td class="alignLeft"><input type="checkbox" name="workingManager" <?php echo ($adminInfo['workingManager'] == "Y")? 'checked="checked"': ''; ?>/></td>
                    </tr>
                    <tr>
                        <th>Banner Manager</th>
                        <td class="alignLeft"><input type="checkbox" name="bannerManager" <?php echo ($adminInfo['bannerManager'] == "Y")? 'checked="checked"': ''; ?>/></td>
                    </tr>
                    <tr>
                        <th>Ticker Manager</th>
                        <td class="alignLeft"><input type="checkbox" name="tickerManager" <?php echo ($adminInfo['tickerManager'] == "Y")? 'checked="checked"': ''; ?>/></td>
                    </tr>
                    <tr>
                        <th>Sponsor Manager</th>
                        <td class="alignLeft"><input type="checkbox" name="sponsorManager" <?php echo ($adminInfo['sponsorManager'] == "Y")? 'checked="checked"': ''; ?>/></td>
                    </tr>
                    <tr>
                        <th>Sponsor Of the Day Manager</th>
                        <td class="alignLeft"><input type="checkbox" name="sponsorofthedayManager" <?php echo ($adminInfo['sponsorofthedayManager'] == "Y")? 'checked="checked"': ''; ?>/></td>
                    </tr>
                    <tr>
                        <th>Reset Horse Weight Manager</th>
                        <td class="alignLeft"><input type="checkbox" name="horseweightManager" <?php echo ($adminInfo['horseweightManager'] == "Y")? 'checked="checked"': ''; ?>/></td>
                    </tr>
                    <tr>
                        <th>Reset Race Data Manager</th>
                        <td class="alignLeft"><input type="checkbox" name="racedataManager" <?php echo ($adminInfo['racedataManager'] == "Y")? 'checked="checked"': ''; ?>/></td>
                    </tr>
                    <tr>
                        <th>Config Manager</th>
                        <td class="alignLeft"><input type="checkbox" name="configManager" <?php echo ($adminInfo['configManager'] == "Y")? 'checked="checked"': ''; ?>/></td>
                    </tr>
                    <tr>
                        <th>Draft Mail Manager</th>
                        <td class="alignLeft"><input type="checkbox" name="mailManager" <?php echo ($adminInfo['mailManager'] == "Y")? 'checked="checked"': ''; ?>/></td>
                    </tr>
                    <tr>
                        <td colspan="2" class="alignLeft">
                            <input type="submit" name="submit" value="Save" />&nbsp;&nbsp;&nbsp;
                            <input type="reset" name="reset" value="Clear" />
                        </td>
                    </tr>
                    
                  </table>
                  <?php if ($q == "add-admin") { ?>
                    <input type="hidden" name="q" value="save-admin" />
                  <?php } else if ($q == "edit-admin") {?>
                    <input type="hidden" name="id" value="<?php echo $adminInfo['id'] ?>" />
                    <input type="hidden" name="q" value="update-admin" />
                  <?php } ?>
                  </form>
            <?php } ?>
            <?php if ($q == "change-password") { ?>       
            <br>
            <h2>Change Password</h2>            
            <form action="admin/manageAdmin.php" method="post">
                  <table class="contentTable"> 
                     <tr>
                        <th>Password</th>
                        <td class="alignLeft"><input type="password" name="pass1" value="" /></td>
                    </tr>
                    <tr>
                        <th>Re-enter Password</th>
                        <td class="alignLeft"><input type="password" name="pass2" value="" /></td>
                    </tr>
                    <tr>
                        <td colspan="2" class="alignLeft">
                            <input type="submit" name="submit" value="Save" />&nbsp;&nbsp;&nbsp;
                            <input type="reset" name="reset" value="Clear" />
                        </td>
                    </tr>
                  </table>
                  <input type="hidden" name="id" value="<?php echo getParameterNumber('id',0); ?>" />
                  <input type="hidden" name="q" value="update-password" />
             </form>  
             <br>
             <hr />
            <?php } ?>          
          <h2>Admin List</h2>  
          <table class="contentTable">
            <tr>
                <th>username</th>
                <th>Full Name</th>
                <th>Active</th>                    
                <th>Action</th>                    
            </tr>
            <?php foreach ($allAdmin as $adminDet) { ?>
                <tr>
                    <td><?php echo $adminDet['username']; ?></td>
                    <td><?php echo $adminDet['name']; ?></td>
                    <td><?php echo $adminDet['active']; ?></td>
                    <td>
                        <a href="admin/manageAdmin.php?id=<?php echo $adminDet['id'];?>&q=edit-admin">Edit</a>
                        <a href="admin/manageAdmin.php?id=<?php echo $adminDet['id'];?>&q=change-password">Change Password</a>
                        <?php if ($adminDet['username'] != "admin") { ?>
                            <a href="javascript:void(0);" onclick="javascript: confirmDelete(<?php echo $adminDet['id']; ?>);" >Delete</a>
                        <?php } ?>
                            
                    </td>
                </tr>
            <?php } ?>
          </table>
         <br />
    <?php } ?>
<?php                   
  $design->closeDiv();
  //$design->rightArea();  
  //$design->closeDiv();
  $design->endPage();
  $design->pageClose();    
$design = NULL; // release object
