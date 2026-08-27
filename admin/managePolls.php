<?php
include_once('../bootstrap.php');
require_once('../lib/polls.class.php');
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
  if ($_SESSION['polls'] == "Y") { // check login
      $poll = new Polls($db); 
      $msg ='';
      if ($q == "save-poll") {       
        $polldate = getParameterString('polldate','',$db);
        $question = getParameterString('question','',$db);
        $active = getParameterString('active','',$db);
        if ($active =="on") {
        $active = "Y";
        } else {
          $active = "N"; 
        }
        $options = array();
        for ($i=1;$i<=5;$i++) {
        $opt = getParameterString('opt'.$i,'',$db);
          if (!empty($opt)) {
          $options[]['option'] = $opt;
          }
        }  
        try {               
        $questionID = $poll->addPollQuestion($question,$polldate,$active);
          $poll->addPollOptions($questionID,$options);               
          $msg = "New Poll created.";
          $q = "";
        } catch (Exception $err) {               
          $msg = "Error adding poll";
          $q = "";
        }
      }
      if ($q == "update-poll") {
      try { 
        $pollID = getParameterNumber('id',0);
        $active = getParameterString('active','',$db); 
        if ($active =="on") {
            $active = "Y";
            $poll->deactivatePolls();
          } else {
            $active = "N";               
          }         
          $poll->changePollState($pollID,$active);
          $msg = "Poll Activated";
      } catch (Exception $e) {
        $msg = "Unable to activate poll";
      }
      }
      if ($q == "delete-poll") {
        $pollID = getParameterNumber('id',0);
        try {
        $poll->deletePoll($pollID);
        $msg = "Poll Deleted";
        $q = "";
        } catch (Exception $err) {
          $msg = "Unable to delete poll";
          $q = "";
        }
    }
      if ($q == "view-votes") {
      $pollID = getParameterNumber('id',0);
      $votesCnt = $poll->getVotesCount($pollID); 
      if(empty($votesCnt)){
          $status = 0;
          $votesCnt = $poll->getPolls($pollID); 
      } else {
          $status = 1;
      }
      // echo '<pre>';
      // print_r($votesCnt);
      // exit;
      }
      if ($q == "edit-poll") {
        $pollID = getParameterNumber('id',0);
        $pollDet = $poll->getPollByID($pollID);
      }
  } else {
    $msg = "You do not have access to this page.";
  }  
} else {
  $secmsg = "Please login to access this page";
}  
$pageTitle ='Manage Public Polls';
$design = new Design();
$design->js='
    <script type="text/javascript" src="js/jquery.ui.core.min.js"></script>
  <script type="text/javascript" src="js/jquery.ui.datepicker.min.js"></script>
  <script type="text/javascript">
    function confirmDelete(pollID) {
      if (confirm ("Are you sure ?")){
        location.href="admin/managePolls.php?q=delete-poll&id="+pollID;
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

  .polls-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
  .add-poll-btn { display: inline-flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #1a7a45; color: #0f5c33; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; }
  .add-poll-btn:hover { background: #e6f4ec; }
  .header-links { display: flex; align-items: center; gap: 16px; }
  .header-links a { color: #0f5c33; text-decoration: none; font-weight: 600; font-size: 14px; }
  .header-links a:hover { text-decoration: underline; }

  .poll-form-wrap { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); max-width: 700px; }
  .poll-form-wrap h3.poll-note { font-size: 13.5px; font-weight: 500; color: #7a8c84; background: #f5f4ee; border: 1px solid #e2e6e4; padding: 10px 14px; border-radius: 8px; margin: 0 0 20px 0; }
  .poll-form-wrap .form-title { font-size: 17px; font-weight: 700; color: #2b332f; margin: 0 0 20px 0; }
  .poll-form-wrap .form-row { margin-bottom: 18px; }
  .poll-form-wrap label.form-label { display: block; font-size: 14px; font-weight: 600; color: #2b332f; margin-bottom: 8px; }
  .poll-form-wrap input[type="text"] {
    width: 100%; border: 1px solid #e2e6e4; border-radius: 8px; padding: 10px 12px; font-size: 14px; color: #2b332f; box-sizing: border-box; font-family: inherit;
  }
  .poll-form-wrap input[type="text"]:focus { outline: none; border-color: #1a7a45; }
  .poll-form-wrap input[type="text"]:disabled { background: #f5f4ee; color: #7a8c84; }
  .poll-form-wrap .checkbox-row { display: flex; align-items: center; gap: 8px; }
  .poll-form-wrap input[type="checkbox"] { width: 17px; height: 17px; accent-color: #0f5c33; cursor: pointer; }
  .poll-form-wrap .form-actions { display: flex; gap: 10px; padding-top: 6px; }
  .poll-form-wrap input[type="submit"], .poll-form-wrap input[type="reset"] { background: #0f5c33; color: #fff; border: none; padding: 10px 22px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; }
  .poll-form-wrap input[type="reset"] { background: #fff; color: #2b332f; border: 1px solid #e2e6e4; }
  .poll-form-wrap input[type="submit"]:hover { background: #0c4a29; }
  .poll-form-wrap input[type="reset"]:hover { background: #f5f4ee; }

  .votes-card { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); max-width: 700px; }
  .votes-card h2 { font-size: 18px; color: #2b332f; margin: 0 0 6px 0; }
  .votes-card .votes-question { font-size: 14.5px; color: #7a8c84; margin-bottom: 16px; }
  .votes-row { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #eef0ee; }
  .votes-row:last-of-type { border-bottom: none; }
  .votes-row .votes-option { font-size: 14px; color: #2b332f; font-weight: 500; }
  .votes-row .votes-count { font-size: 14px; color: #0f5c33; font-weight: 700; }
  .votes-total { display: flex; align-items: center; justify-content: space-between; padding-top: 14px; margin-top: 6px; border-top: 2px solid #0f5c33; font-weight: 700; color: #2b332f; }

  .polls-list-title { font-size: 17px; font-weight: 700; color: #2b332f; margin: 0 0 14px 0; }
  .polls-list { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.03); }
  .polls-list-row { display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border-bottom: 1px solid #eef0ee; flex-wrap: wrap; gap: 10px; }
  .polls-list-row:last-child { border-bottom: none; }
  .polls-list-row .poll-info { display: flex; flex-direction: column; gap: 4px; }
  .polls-list-row .poll-question { font-size: 14.5px; color: #2b332f; font-weight: 500; }
  .polls-list-row .poll-meta { display: flex; align-items: center; gap: 14px; font-size: 12.5px; color: #7a8c84; }
  .polls-list-row .poll-meta i { color: #0f5c33; }
  .polls-list-row .poll-active-badge { padding: 2px 8px; border-radius: 4px; font-size: 11.5px; font-weight: 700; }
  .polls-list-row .poll-active-badge.yes { background: #0f5c33; color: #fff; }
  .polls-list-row .poll-active-badge.no { background: #f5f4ee; color: #7a8c84; border: 1px solid #e2e6e4; }
  .polls-list-row .poll-actions { display: flex; gap: 16px; }
  .polls-list-row .poll-actions a { font-size: 13.5px; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 6px; }
  .polls-list-row .poll-actions a:nth-child(1) { color: #0f5c33; }
  .polls-list-row .poll-actions a:nth-child(2) { color: #c0392b; }
  .polls-list-row .poll-actions a:nth-child(3) { color: #1a5fb4; }
  .polls-list-empty { padding: 20px; text-align: center; color: #7a8c84; font-size: 14px; }
  html, body { scrollbar-width: none; -ms-overflow-style: none; }
html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }

  @media (max-width: 700px) {
    #leftArea.col-lg-9 { padding: 0 16px; }
    .polls-header { flex-direction: column; align-items: flex-start; }
    .poll-form-wrap, .votes-card { padding: 18px; }
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
  $('#polldate').datepicker({
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
$pollDetails = $poll->getALLPolls()
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
<?php if ($_SESSION['polls'] == "Y") { ?>
  <div class="polls-header">
    <a class="add-poll-btn" href="admin/managePolls.php?q=new-poll"><i class="fas fa-plus"></i> Add New Polls</a>
    <div class="header-links">
      <!-- <a href="admin/dashboard.php">Dashboard</a>
      <a href="admin/adminlogin.php?q=logout">Logout</a> -->
    </div>
  </div>
  <?php if ($q == "edit-poll") { ?>
    <div class="poll-form-wrap">
    <form method="post" action="admin/managePolls.php">
      <h3 class="poll-note"><i class="fas fa-circle-info"></i> Activating a poll will deactivate all other active polls</h3>
      <div class="form-title">Edit Poll</div>
      <div class="form-row">
          <label class="form-label" for="question">Question</label>
          <input type="text" disabled="disabled" name="question" id="question" value='<?php echo $pollDet['question']; ?>' />
      </div>
      <div class="form-row checkbox-row">
          <input type="checkbox" name="active" id="active" <?php echo ($pollDet['active']) ? 'checked="checked"' : ""; ?> />
          <label class="form-label" for="active" style="margin-bottom:0;">Active ?</label>
      </div>
      <div class="form-actions">
        <input type="submit" name="submit" value="Update" />
        <input type="reset" name="reset" value="Clear" />
        <input type="hidden" name="q" value="update-poll" />
        <input type="hidden" name="id" value="<?php echo $pollDet['id']; ?>" />
      </div>
    </form>
    </div>
  <?php } ?>       
  <?php if ($q == "view-votes") {  ?>
    <div class="votes-card">
      <h2><i class="fas fa-chart-simple"></i> Votes</h2>
      <div class="votes-question">Q) <?php echo $votesCnt[0]['question']; ?></div>
      <?php $total = 0; ?>
      <?php if($status == 1){ ?> 
      <?php foreach ($votesCnt as $vote) { ?>
        <div class="votes-row">
          <div class="votes-option"><?php echo $vote['option']; ?></div>
          <div class="votes-count"><?php echo $vote['votes']; ?></div>
        </div>
        <?php $total += $vote['votes']; ?>
      <?php } ?>
      <?php } ?>
      <div class="votes-total">
        <span>Total</span>
        <span><?php echo $total; ?></span>
      </div>
    </div>
  <?php } ?>
  <?php if ($q=="new-poll") { ?> 
    <div class="poll-form-wrap">
    <form method="post" action="admin/managePolls.php">
      <div class="form-title">Add New Poll</div>
      <div class="form-row">
        <label class="form-label" for="polldate">Poll Date</label>
        <input type="text" name="polldate" id="polldate" />
      </div>
      <div class="form-row">
        <label class="form-label" for="question">Question</label>
        <input type="text" name="question" id="question" />
      </div>
      <div class="form-row">
        <label class="form-label" for="opt1">Option 1</label>
        <input type="text" name="opt1" id="opt1" />
      </div>
      <div class="form-row">
        <label class="form-label" for="opt2">Option 2</label>
        <input type="text" name="opt2" id="opt2" />
      </div>
      <div class="form-row">
        <label class="form-label" for="opt3">Option 3</label>
        <input type="text" name="opt3" id="opt3" />
      </div>
      <div class="form-row">
        <label class="form-label" for="opt4">Option 4</label>
        <input type="text" name="opt4" id="opt4" />
      </div>
      <div class="form-row">
        <label class="form-label" for="opt5">Option 5</label>
        <input type="text" name="opt5" id="opt5" />
      </div>
      <div class="form-row checkbox-row">
        <input type="checkbox" name="active" id="active" />
        <label class="form-label" for="active" style="margin-bottom:0;">Active ?</label>
      </div>
      <div class="form-actions">
        <input type="submit" name="submit" value="Add" />
        <input type="reset" name="reset" value="Clear" />
        <input type="hidden" name="q" value="save-poll" />
      </div>
    </form>
    </div>
  <?php } ?>

  <div class="polls-list-title">List of Polls</div>
  <div class="polls-list">
    <?php if (empty($pollDetails)) { ?>
        <div class="polls-list-empty">No polls found.</div>
    <?php } ?>
    <?php foreach ($pollDetails as $pollDet) {?>
      <div class="polls-list-row">
        <div class="poll-info">
          <div class="poll-question"><?php echo $pollDet['question']; ?></div>
          <div class="poll-meta">
            <span><i class="far fa-calendar-alt"></i> <?php echo date("d-M-Y",strtotime($pollDet['date'])); ?></span>
            <span class="poll-active-badge <?php echo ($pollDet['active']=='Y') ? 'yes' : 'no'; ?>"><?php echo $pollDet['active']; ?></span>
          </div>
        </div>
        <div class="poll-actions">
          <a href="admin/managePolls.php?q=edit-poll&id=<?php echo $pollDet['id']; ?>"><i class="fas fa-edit"></i> Edit</a>
          <a href="#" onclick="confirmDelete('<?php echo $pollDet['id']; ?>')"><i class="fas fa-trash-alt"></i> Delete</a>
          <a href="admin/managePolls.php?q=view-votes&id=<?php echo $pollDet['id']; ?>"><i class="fas fa-chart-simple"></i> View Votes</a>
        </div>
      </div>
    <?php } ?>
  </div>
<?php } ?>
<?php
  $design->closeDiv();
  $design->writeLeftPanel();
  $design->closeDiv();
  $design->closeDiv();
  $design->pageClose();
  $design = NULL; // release object
?>