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
  $design->css ='';
  $design->jqueryJs = ""; 
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
                <div style="float:right;">
                    <a style="float:left;" href="admin/dashboard.php">Dashboard</a>
                    <a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
                  </div>
            </div>
<form method="post" action="admin/sendMailers.php?q=send-mail">
<table class="contentTable">
    <tr>
        <th class="thwhite" colspan="2"><h2>Send Mailer to registered users</h2></th>
    </tr>
    <tr>
        <th>Select User Type</th>
        <td class="alignLeft">
            <input type="radio" name="usertype" value="all" checked="checked" />All &nbsp;
            <input type="radio" name="usertype" value="verified" />Verfied &nbsp;
            <input type="radio" name="usertype" value="unverified" />Unverfied &nbsp;
        </td>
    </tr>
    <tr>
        <th>Subject</th>
        <td class="alignLeft"><input type="text" name="subject" value='' /></td>
    </tr>
    <tr>
        <th>Message</th>
        <td class="alignLeft"><textarea name="message" rows='3' cols='50'></textarea></td>
    </tr>
    <tr>
        <td colspan="2">
            <input type="submit" name="submit" value="Send" />              
            <input type="reset" name="reset" value="Clear" />              
        </td>
    </tr>
</table>
</form>
<?php } ?>
<?php                   
  $design->closeDiv();
  //$design->rightArea();  
  //$design->closeDiv();
  $design->closeDiv();
    $design->pageClose();
$design = NULL; // release object