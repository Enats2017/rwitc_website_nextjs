<?php
  include_once('../bootstrap.php');
  include_once('../lib/users.class.php');
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
    if (($_SESSION['role'] == "ADMIN" || ($_SESSION['role'] == "BACK_OFFICE"))) { // check login
 
        if ($q=="send-mail") {
      $usertype = getParameterString('usertype','all',$db,true);   
      $subject = getParameterString('subject','',$db,true);
      //$message = getParameterString('message','',$db,true);
      $message = $_REQUEST['message'];
      $newmessage = nl2br($message);      
      $users = new Users($db);
      $userList = $users->getUserListByType($usertype);
      //$from ='web@rwitc.com';
      //$fromName = 'RWITC Mailers';
      $from = 'edp@rwitc.com';
      $fromName = 'RWITC Mailers';
      $msg = '';
      foreach ($userList as $user) {
        mailer1($from,$fromName,$user['email'],$user['firstname']." ".$user['lastname'],$subject,$newmessage,'','','');
        //sleep(1);
        $msg = "Mail Sent to {$user['email']}<br />";
      }
      
  }
    } else {
        $msg = "You do not have access to this page.";
      }  
} else {
    $secmsg = "Please login to access this page";
} 
  $pageTitle ='Send Mailers';        
  $design = new Design();
  $design->js='';
  $design->css ='
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
';
  $design->jqueryJs = ""; 
  $design->startPage("$pageTitle");  
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea",'col-lg-9');  
?>

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

.mailer-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.mailer-header h2 { margin: 0; font-size: 20px; color: #2b332f; display: flex; align-items: center; gap: 10px; white-space: nowrap; border: none; border-bottom: none; padding: 0; }
.mailer-header h2 i { flex-shrink: 0; } 
.header-links { display: flex; align-items: center; gap: 16px; }
.header-links a { color: #0f5c33; text-decoration: none; font-weight: 600; font-size: 14px; }
.header-links a:hover { text-decoration: underline; }

.mailer-form-wrap { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); max-width: 700px; }
.mailer-form-wrap .form-row { margin-bottom: 20px; }
.mailer-form-wrap label.form-label { display: block; font-size: 14px; font-weight: 600; color: #2b332f; margin-bottom: 8px; }
.mailer-form-wrap .radio-group { display: flex; gap: 20px; flex-wrap: wrap; }
.mailer-form-wrap .radio-group label { display: inline-flex; align-items: center; gap: 6px; font-size: 14px; color: #2b332f; font-weight: 400; cursor: pointer; }
.mailer-form-wrap input[type="radio"] { accent-color: #0f5c33; width: 16px; height: 16px; cursor: pointer; }
.mailer-form-wrap input[type="text"], .mailer-form-wrap textarea {
  width: 100%; border: 1px solid #e2e6e4; border-radius: 8px; padding: 10px 12px; font-size: 14px; color: #2b332f; box-sizing: border-box; font-family: inherit;
}
.mailer-form-wrap input[type="text"]:focus, .mailer-form-wrap textarea:focus { outline: none; border-color: #1a7a45; }
.mailer-form-wrap textarea { resize: vertical; min-height: 140px; }
.mailer-form-wrap .form-actions { display: flex; gap: 10px; padding-top: 6px; }
.mailer-form-wrap input[type="submit"], .mailer-form-wrap input[type="reset"] { background: #0f5c33; color: #fff; border: none; padding: 10px 22px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; }
.mailer-form-wrap input[type="reset"] { background: #fff; color: #2b332f; border: 1px solid #e2e6e4; }
.mailer-form-wrap input[type="submit"]:hover { background: #0c4a29; }
.mailer-form-wrap input[type="reset"]:hover { background: #f5f4ee; }


html, body { scrollbar-width: none; -ms-overflow-style: none; }
html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }

@media (max-width: 700px) {
  #leftArea { padding: 0 16px; }
  .mailer-header { flex-direction: column; align-items: flex-start; }
  .mailer-form-wrap { padding: 18px; }
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
    <?php if (empty($msg) && empty($secmsg)) { ?>
            <div class="mailer-header">
                <h2><i class="fas fa-envelope"></i> Send Mailer to registered users</h2>
                <div class="header-links">
                    <!-- <a href="admin/dashboard.php">Dashboard</a>
                    <a href="admin/adminlogin.php?q=logout">Logout</a> -->
                </div>
            </div>

<div class="mailer-form-wrap">
<form method="post" action="admin/sendMailers.php?q=send-mail">
    <div class="form-row">
        <label class="form-label">Select User Type</label>
        <div class="radio-group">
            <label><input type="radio" name="usertype" value="all" checked="checked" /> All</label>
            <label><input type="radio" name="usertype" value="verified" /> Verified</label>
            <label><input type="radio" name="usertype" value="unverified" /> Unverified</label>
        </div>
    </div>
    <div class="form-row">
        <label class="form-label" for="subject">Subject</label>
        <input type="text" name="subject" id="subject" value='' />
    </div>
    <div class="form-row">
        <label class="form-label" for="message">Message</label>
        <textarea name="message" id="message" rows='3' cols='50'></textarea>
    </div>
    <div class="form-actions">
        <input type="submit" name="submit" value="Send" />
        <input type="reset" name="reset" value="Clear" />
    </div>
</form>
</div>
<?php } ?>
<?php                   
  $design->closeDiv();
  $design->writeLeftPanel();
  $design->closeDiv();
  $design->closeDiv();
    $design->pageClose();
$design = NULL; // release object