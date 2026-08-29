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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
$design->openDiv("infoWrapper","col-lg-12");
$design->openDiv("leftArea","col-lg-9");
$allAdmin = $adminsObj->getAllAdmins();
?>

<style type="text/css">
/* ===== layout: leftArea + sidebar, same pattern as the other managers ===== */
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
#infoWrapper.col-lg-12 #rightArea.col-lg-3 { padding-top: 0 !important; }

.message {
    position: relative;
    background: #e6f4ec;
    border: 1px solid #b7ddc5;
    color: #0f5c33;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 15px;
    font-size: 14.5px;
    font-weight: 500;
}

.admin-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.add-admin-btn { display: inline-flex; align-items: center; gap: 8px; background: #0f5c33; border: 1px solid #0f5c33; color: #fff; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; white-space: nowrap; }
.add-admin-btn:hover { background: #0b3d24; }

.section-title { font-size: 16px; font-weight: 700; color: #0f5c33; margin: 0 0 14px; display: flex; align-items: center; gap: 8px; }

/* ===== admin list table ===== */
.admin-table-wrap { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.03); }
table.admin-table { width: 100%; border-collapse: collapse; font-size: 14.5px; }
table.admin-table th { background: #0b3d24; color: #fff; text-align: left; padding: 14px 20px; font-weight: 600; font-size: 13px; letter-spacing: 0.3px; }
table.admin-table th.action-col { text-align: right; width: 260px; }
table.admin-table td { padding: 14px 20px; border-bottom: 1px solid #eef0ee; color: #2b332f; }
table.admin-table tr:last-child td { border-bottom: none; }
table.admin-table tr:nth-child(even) td { background: #f7faf8; }
table.admin-table tr:hover td { background: #e6f4ec; }
table.admin-table td.action-col { text-align: right; white-space: nowrap; }
table.admin-table td.action-col a { font-size: 13px; text-decoration: none; font-weight: 500; margin-left: 14px; display: inline-flex; align-items: center; gap: 6px; }
table.admin-table td.action-col a.edit-link { color: #0f5c33; }
table.admin-table td.action-col a.pass-link { color: #b8860b; }
table.admin-table td.action-col a.delete-link { color: #c0392b; }
.status-pill { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; }
.status-pill.yes { background: #e6f4ec; color: #0f5c33; }
.status-pill.no { background: #fdecea; color: #b3261e; }
.admin-empty { padding: 30px 20px; text-align: center; color: #7a8c84; font-size: 14.5px; }

/* ===== modal (Add / Edit Admin, Change Password) ===== */
.rw-modal-overlay { position: fixed; inset: 0; background: rgba(11, 61, 36, 0.45); display: flex; align-items: center; justify-content: center; padding: 20px; z-index: 1000; box-sizing: border-box; }
.rw-modal-box { background: #fff; width: 100%; max-width: 720px; max-height: 90vh; border-radius: 14px; box-shadow: 0 20px 60px rgba(0,0,0,0.25); display: flex; flex-direction: column; overflow: hidden; }
.rw-modal-box.rw-modal-box-sm { max-width: 460px; }
.rw-modal-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #e2e6e4; flex-shrink: 0; }
.rw-modal-header h3 { margin: 0; font-size: 17px; color: #0f5c33; font-weight: 700; }
.rw-modal-close { text-decoration: none; color: #7a8c84; font-size: 22px; line-height: 1; padding: 4px 8px; border-radius: 6px; }
.rw-modal-close:hover { background: #f5f4ee; color: #c0392b; }
.rw-modal-body { padding: 20px; overflow-y: auto; flex: 1; min-height: 0; }

/* admin form fields */
.admin-form-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
.admin-form-table th { text-align: left; padding: 9px 8px; color: #2b332f; vertical-align: top; width: 32%; font-weight: 600; font-size: 13.5px; }
.admin-form-table td { padding: 9px 8px; }
.admin-form-table input[type="text"],
.admin-form-table input[type="password"] {
    border: 1px solid #e2e6e4; border-radius: 6px; padding: 8px 10px; font-size: 14px;
    width: 100%; max-width: 100%; box-sizing: border-box;
}
.admin-form-actions { padding: 12px 8px 0; }
.admin-form-actions input[type="submit"] { background: #0f5c33; color: #fff; border: none; padding: 9px 22px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; margin-right: 8px; }
.admin-form-actions input[type="submit"]:hover { background: #0b3d24; }
.admin-form-actions input[type="reset"] { background: #fff; color: #2b332f; border: 1px solid #e2e6e4; padding: 9px 22px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; }
.admin-form-actions input[type="reset"]:hover { background: #f5f4ee; }

.active-toggle-row { display: flex; align-items: center; gap: 10px; padding: 8px; margin-bottom: 6px; background: #f7faf8; border-radius: 8px; }
.active-toggle-row label { font-size: 13.5px; font-weight: 600; color: #2b332f; }

/* permissions grid */
.permissions-title { font-size: 13px; font-weight: 700; color: #7a8c84; letter-spacing: .3px; text-transform: uppercase; margin: 18px 0 10px; }
.permissions-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; }
.permission-item { display: flex; align-items: center; gap: 8px; padding: 8px 10px; border: 1px solid #e2e6e4; border-radius: 8px; font-size: 13.5px; color: #2b332f; background: #fff; }
.permission-item input[type="checkbox"] { width: 16px; height: 16px; flex-shrink: 0; accent-color: #0f5c33; }
.permission-item:has(input:checked) { background: #e6f4ec; border-color: #1a7a45; }


html, body { scrollbar-width: none; -ms-overflow-style: none; }
html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }

/* ===== responsive ===== */
@media (max-width: 900px) {
    #infoWrapper.col-lg-12 { flex-direction: column; margin: 16px auto; }
    #leftArea.col-lg-9 { flex: 1 1 100%; max-width: 100%; padding: 28px 24px; }
}
@media (max-width: 700px) {
    #leftArea.col-lg-9 { padding: 0 16px; }
    .admin-header { flex-direction: column; align-items: stretch; }
    table.admin-table th, table.admin-table td { padding: 10px 12px; font-size: 13px; }
    table.admin-table td.action-col a { margin-left: 0; margin-right: 12px; }
    .permissions-grid { grid-template-columns: 1fr; }
    .rw-modal-overlay { padding: 0; align-items: flex-end; }
    .rw-modal-box, .rw-modal-box.rw-modal-box-sm { max-width: 100%; width: 100%; max-height: 92vh; border-radius: 16px 16px 0 0; }
}
@media (max-width: 520px) {
    .admin-form-table, .admin-form-table tbody, .admin-form-table tr, .admin-form-table th, .admin-form-table td {
        display: block; width: 100% !important;
    }
    .admin-form-table th { padding-bottom: 2px; }
    .admin-form-table td { padding-top: 0; padding-bottom: 10px; }
}
</style>

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

        <div class="admin-header">
            <a class="add-admin-btn" href="admin/manageAdmin.php?q=add-admin"><i class="fas fa-user-plus"></i> Add New Admin</a>
            <!--
            <div style="float:right;">
                <a style="float:left;" href="admin/dashboard.php">Dashboard</a>
                <a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
            </div>
            -->
        </div>

            <?php if ($q == "add-admin" || $q == "edit-admin") { ?>
            <div class="rw-modal-overlay" id="rwAdminModal">
              <div class="rw-modal-box">
                <div class="rw-modal-header">
                  <h3><?php echo ($q == "add-admin") ? "Add New Admin" : "Edit Admin"; ?></h3>
                  <a href="admin/manageAdmin.php" class="rw-modal-close" aria-label="Close">&times;</a>
                </div>
                <div class="rw-modal-body">
                  <form action="admin/manageAdmin.php" method="post">
                  <table class="admin-form-table">                    
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
                  </table>

                  <div class="active-toggle-row">
                      <input type="checkbox" name="active" id="admActive" <?php echo ($adminInfo['active'] == "Y")? 'checked="checked"': ''; ?>/>
                      <label for="admActive">Account Active</label>
                  </div>

                  <div class="permissions-title">Admin Permissions</div>
                  <div class="permissions-grid">
                    <label class="permission-item"><input type="checkbox" name="articles" <?php echo ($adminInfo['articles'] == "Y")? 'checked="checked"': ''; ?>/> Manage Articles</label>
                    <label class="permission-item"><input type="checkbox" name="race_history" <?php echo ($adminInfo['race_history'] == "Y")? 'checked="checked"': ''; ?>/> Manage Race History</label>
                    <label class="permission-item"><input type="checkbox" name="send_mailer" <?php echo ($adminInfo['send_mailer'] == "Y")? 'checked="checked"': ''; ?>/> Send Mailers</label>
                    <label class="permission-item"><input type="checkbox" name="rating_change" <?php echo ($adminInfo['rating_change'] == "Y")? 'checked="checked"': ''; ?>/> Rating Change</label>
                    <label class="permission-item"><input type="checkbox" name="gallery" <?php echo ($adminInfo['gallery'] == "Y")? 'checked="checked"': ''; ?>/> Gallery</label>
                    <label class="permission-item"><input type="checkbox" name="video" <?php echo ($adminInfo['video'] == "Y")? 'checked="checked"': ''; ?>/> Video</label>
                    <label class="permission-item"><input type="checkbox" name="dividends" <?php echo ($adminInfo['dividends'] == "Y")? 'checked="checked"': ''; ?>/> Dividends</label>
                    <label class="permission-item"><input type="checkbox" name="stewards_report" <?php echo ($adminInfo['stewards_report'] == "Y")? 'checked="checked"': ''; ?>/> Stewards Report</label>
                    <label class="permission-item"><input type="checkbox" name="race_day_report" <?php echo ($adminInfo['race_day_report'] == "Y")? 'checked="checked"': ''; ?>/> Race Day Report</label>
                    <label class="permission-item"><input type="checkbox" name="calendar" <?php echo ($adminInfo['calendar'] == "Y")? 'checked="checked"': ''; ?>/> Calendar</label>
                    <label class="permission-item"><input type="checkbox" name="prakash_gosavi" <?php echo ($adminInfo['prakash_gosavi'] == "Y")? 'checked="checked"': ''; ?>/> Prakash Gosavi</label>
                    <label class="permission-item"><input type="checkbox" name="polls" <?php echo ($adminInfo['polls'] == "Y")? 'checked="checked"': ''; ?>/> Polls</label>
                    <label class="permission-item"><input type="checkbox" name="adminusers" <?php echo ($adminInfo['adminusers'] == "Y")? 'checked="checked"': ''; ?>/> Manage Admins</label>
                    <label class="permission-item"><input type="checkbox" name="workingManager" <?php echo ($adminInfo['workingManager'] == "Y")? 'checked="checked"': ''; ?>/> Manage Working Manager</label>
                    <label class="permission-item"><input type="checkbox" name="bannerManager" <?php echo ($adminInfo['bannerManager'] == "Y")? 'checked="checked"': ''; ?>/> Banner Manager</label>
                    <label class="permission-item"><input type="checkbox" name="tickerManager" <?php echo ($adminInfo['tickerManager'] == "Y")? 'checked="checked"': ''; ?>/> Ticker Manager</label>
                    <label class="permission-item"><input type="checkbox" name="sponsorManager" <?php echo ($adminInfo['sponsorManager'] == "Y")? 'checked="checked"': ''; ?>/> Sponsor Manager</label>
                    <label class="permission-item"><input type="checkbox" name="sponsorofthedayManager" <?php echo ($adminInfo['sponsorofthedayManager'] == "Y")? 'checked="checked"': ''; ?>/> Sponsor Of the Day Manager</label>
                    <label class="permission-item"><input type="checkbox" name="horseweightManager" <?php echo ($adminInfo['horseweightManager'] == "Y")? 'checked="checked"': ''; ?>/> Reset Horse Weight Manager</label>
                    <label class="permission-item"><input type="checkbox" name="racedataManager" <?php echo ($adminInfo['racedataManager'] == "Y")? 'checked="checked"': ''; ?>/> Reset Race Data Manager</label>
                    <label class="permission-item"><input type="checkbox" name="configManager" <?php echo ($adminInfo['configManager'] == "Y")? 'checked="checked"': ''; ?>/> Config Manager</label>
                    <label class="permission-item"><input type="checkbox" name="mailManager" <?php echo ($adminInfo['mailManager'] == "Y")? 'checked="checked"': ''; ?>/> Draft Mail Manager</label>
                  </div>

                  <div class="admin-form-actions">
                      <input type="submit" name="submit" value="Save" />
                      <input type="reset" name="reset" value="Clear" />
                  </div>

                  <?php if ($q == "add-admin") { ?>
                    <input type="hidden" name="q" value="save-admin" />
                  <?php } else if ($q == "edit-admin") {?>
                    <input type="hidden" name="id" value="<?php echo $adminInfo['id'] ?>" />
                    <input type="hidden" name="q" value="update-admin" />
                  <?php } ?>
                  </form>
                </div>
              </div>
            </div>
            <?php } ?>

            <?php if ($q == "change-password") { ?>
            <div class="rw-modal-overlay" id="rwAdminModal">
              <div class="rw-modal-box rw-modal-box-sm">
                <div class="rw-modal-header">
                  <h3>Change Password</h3>
                  <a href="admin/manageAdmin.php" class="rw-modal-close" aria-label="Close">&times;</a>
                </div>
                <div class="rw-modal-body">
            <form action="admin/manageAdmin.php" method="post">
                  <table class="admin-form-table"> 
                     <tr>
                        <th>Password</th>
                        <td class="alignLeft"><input type="password" name="pass1" value="" /></td>
                    </tr>
                    <tr>
                        <th>Re-enter Password</th>
                        <td class="alignLeft"><input type="password" name="pass2" value="" /></td>
                    </tr>
                  </table>
                  <div class="admin-form-actions">
                      <input type="submit" name="submit" value="Save" />
                      <input type="reset" name="reset" value="Clear" />
                  </div>
                  <input type="hidden" name="id" value="<?php echo getParameterNumber('id',0); ?>" />
                  <input type="hidden" name="q" value="update-password" />
             </form>
                </div>
              </div>
            </div>
            <?php } ?>

            <?php if ($q=="add-admin" || $q=="edit-admin" || $q=="change-password") { ?>
            <script type="text/javascript">
                document.documentElement.style.overflow = 'hidden';
                document.body.style.overflow = 'hidden';
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') { window.location.href = 'admin/manageAdmin.php'; }
                });
                var rwAdminOverlayEl = document.getElementById('rwAdminModal');
                if (rwAdminOverlayEl) {
                    rwAdminOverlayEl.addEventListener('click', function (e) {
                        if (e.target === rwAdminOverlayEl) { window.location.href = 'admin/manageAdmin.php'; }
                    });
                }
            </script>
            <?php } ?>

          <div class="section-title"><i class="fas fa-users-gear"></i> Admin List</div>
          <div class="admin-table-wrap">
          <table class="admin-table">
            <tr>
                <th>Username</th>
                <th>Full Name</th>
                <th>Active</th>                    
                <th class="action-col">Action</th>                    
            </tr>
            <?php if (count($allAdmin) > 0) { ?>
            <?php foreach ($allAdmin as $adminDet) { ?>
                <tr>
                    <td><?php echo $adminDet['username']; ?></td>
                    <td><?php echo $adminDet['name']; ?></td>
                    <td><span class="status-pill <?php echo ($adminDet['active']=='Y') ? 'yes' : 'no'; ?>"><?php echo $adminDet['active']; ?></span></td>
                    <td class="action-col">
                        <a class="edit-link" href="admin/manageAdmin.php?id=<?php echo $adminDet['id'];?>&q=edit-admin"><i class="fas fa-edit"></i> Edit</a>
                        <a class="pass-link" href="admin/manageAdmin.php?id=<?php echo $adminDet['id'];?>&q=change-password"><i class="fas fa-key"></i> Change Password</a>
                        <?php if ($adminDet['username'] != "admin") { ?>
                            <a class="delete-link" href="javascript:void(0);" onclick="javascript: confirmDelete(<?php echo $adminDet['id']; ?>);"><i class="fas fa-trash-alt"></i> Delete</a>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="4" class="admin-empty">No admins added yet.</td>
                </tr>
            <?php } ?>
          </table>
          </div>
    <?php } ?>
<?php                   
  $design->closeDiv();
  $design->writeLeftPanel();
  $design->closeDiv();
  $design->closeDiv();
  $design->endPage();
  $design->pageClose();    
$design = NULL; // release object