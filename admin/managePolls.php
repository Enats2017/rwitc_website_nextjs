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
$design->openDiv("infoWrapper");
$design->openDiv("leftArea");
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
  <div class="submenu">
    <a href="admin/managePolls.php?q=new-poll">Add New Polls</a>
    <div style="float:right;">
      <a style="float:left;" href="admin/dashboard.php">Dashboard</a>
      <a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
    </div>
  </div>
  <?php if ($q == "edit-poll") { ?>
    <br><br>
    <form method="post" action="admin/managePolls.php">
      <h3>Note: Activating a poll will deactivate all other active polls</h3>
      <table class="contentTable">
          <tr>
          <th class="thwhite alignLeft" colspan="2"Edit Poll</th>
          </tr>
          <tr>
          <th>Question</th>
            <td class="alignLeft"><input type="text" disabled="disabled" name="question" id="question" value='<?php echo $pollDet['question']; ?>' /></td>
          </tr>
          <tr>
          <th>Active ?</th>
          <td class="alignLeft"><input type="checkbox" name="active" <?php echo ($pollDet['active']) ? 'checked="checked"' : ""; ?> /></td>
          </tr>
          <tr>
            <td colspan="2" >
            <input type="submit" name="submit" value="Update" />
            <input type="reset" name="reset" value="Clear" />
            <input type="hidden" name="q" value="update-poll" />
            <input type="hidden" name="id" value="<?php echo $pollDet['id']; ?>" />
            </td>
          </tr>
      </table>
    </form>
  <?php } ?>       
  <?php if ($q == "view-votes") {  ?>
    <br><br>
    <h2>Votes</h2>
    <table class="contentTable">
        <tr>
        <th class="thwhite alignLeft" colspan="2">Q) <?php echo $votesCnt[0]['question']; ?> </th>
        </tr>
        <tr>
        <th>Option</th>
        <th>Votes</th>
        </tr>
        <?php $total = 0; ?>
        <?php if($status == 1){ ?> 
        <?php foreach ($votesCnt as $vote) { ?>
          <tr>
            <td><?php echo $vote['option']; ?></td>
            <td><?php echo $vote['votes']; ?></td>
          </tr>
          <?php $total += $vote['votes']; ?>
        <?php } ?>
        <?php } ?>
        <tr>
        <th class="alignRight">Total</th>
        <th><?php echo $total; ?></th>
       </tr> 
    </table>
  <?php } ?>
  <?php if ($q=="new-poll") { ?> 
    <form method="post" action="admin/managePolls.php">
      <table class="contentTable">
          <tr>
            <th class="thwhite alignLeft" colspan="2">Add New Poll</th>
          </tr>
          <tr>
            <th>Poll Date</th>
            <td class="alignLeft"><input type="text" name="polldate" id="polldate" /></td>
          </tr>
          <tr>
            <th>Question</th>
            <td class="alignLeft"><input type="text" name="question" id="question" /></td>
          </tr>
          <tr>
            <th>Option 1</th>
            <td class="alignLeft"><input type="text" name="opt1" id="opt1" /></td>
          </tr>
          <tr>
            <th>Option 2</th>
            <td class="alignLeft"><input type="text" name="opt2" id="opt2" /></td>
          </tr>
          <tr>
            <th>Option 3</th>
            <td class="alignLeft"><input type="text" name="opt3" id="opt3" /></td>
          </tr>
          <tr>
            <th>Option 4</th>
            <td class="alignLeft"><input type="text" name="opt4" id="opt4" /></td>
          </tr>
          <tr>
            <th>Option 5</th>
            <td class="alignLeft"><input type="text" name="opt5" id="opt5" /></td>
          </tr>
          <tr>
          <th>Active ?</th>
          <td class="alignLeft"><input type="checkbox" name="active" /></td>
          </tr>
          <tr>
            <td colspan="2">
              <input type="submit" name="submit" value="Add" />
              <input type="reset" name="reset" value="Clear" />
              <input type="hidden" name="q" value="save-poll" />
            </td>
          </tr>
      </table>
    </form>
  <?php } ?>
  <br /> <br />
  <table class="contentTable">
    <tr>
      <th class="thwhite alignLeft" colspan="2">List of Polls</th>
    </tr>
    <tr>
      <th>Poll Date</th>
      <th>Question</th>               
      <th>Active</th>
      <th>Action</th>
    </tr>        
    <?php foreach ($pollDetails as $pollDet) {?>
      <tr>
        <td><?php echo date("d-M-Y",strtotime($pollDet['date'])); ?></td>
        <td><?php echo $pollDet['question']; ?></td>
        <td><?php echo $pollDet['active']; ?></td>
        <td>
          <a href="admin/managePolls.php?q=edit-poll&id=<?php echo $pollDet['id']; ?>">Edit</a>
          <a href="#" onclick="confirmDelete('<?php echo $pollDet['id']; ?>')">Delete</a>
          <a href="admin/managePolls.php?q=view-votes&id=<?php echo $pollDet['id']; ?>">View Votes</a>
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
?>