<?php
//echo "Pls wait..";
//exit;
  include_once('../bootstrap.php');
  require_once('../lib/videos.class.php');
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
    if ($_SESSION['video'] == "Y") { // check login
      $videos = new Videos($db); 
      $msg ='';
      if ($q == "add-video") {
          $racedate = getParameterString('racedate','',$db,true);
           $chan = getParameterNumber('chan',0);
           $cat = getParameterNumber('cat',0);
           try {
               $videos->addVideo($racedate, $chan, $cat);
               $msg = "New Video Slot created.";
           } catch (Exception $err) {
               //echo $err->getMessage();
               $msg = "Error adding video";
           }
      }
      $videoList = array();
      //$videoList = $videos->getVideos();
      } else {
        $msg = "You do not have access to this page.";
      }  
    } else {
        $secmsg = "Please login to access this page";
    }  
  $pageTitle ='Videos Manager';
  $design = new Design();
  $design->js='
  <script type="text/javascript" src="js/jquery.ui.core.min.js"></script>
    <script type="text/javascript" src="js/jquery.ui.datepicker.min.js"></script>
    <script type="text/javascript">
        function confirmDelete(reportID) {
            if (confirm ("Are you sure ?")){
                location.href="admin/racedayReportsManager.php?q=delete-report&id="+reportID;
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

    $('#racedate').datepicker({
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
<?php if ($_SESSION['video'] == "Y") { ?>
 <div style="float:right;">
                    <a style="float:left;" href="admin/dashboard.php">Dashboard</a>
                    <a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
                  </div>
<form method="post" action="admin/manageVideos.php">
<table class="contentTable">
       <tr>
           <th class="thwhite alignLeft" colspan="2">Add New Video</th>
       </tr>
       <tr>
           <th>Race Date</th>
           <td class="alignLeft"><input type="text" name="racedate" id="racedate" /></td>
       </tr>
       <tr>
           <th>Channel</th>
           <td class="alignLeft"><input type="text" name="chan" id="chan" /></td>
       </tr>
       <tr>
           <th>Category</th>
           <td class="alignLeft"><input type="text" name="cat" id="cat" /></td>
       </tr>
       <tr>
           <td colspan="2" >
               <input type="submit" name="submit" value="Add" />
               <input type="reset" name="reset" value="Clear" />
               <input type="hidden" name="q" value="add-video" />
           </td>
       </tr>
</table>
</form>
<br /> <br />
<table class="contentTable">
        <tr>
            <th class="thwhite alignLeft" colspan="2">List of last 50 recent videos</th>
        </tr>
        <tr>
                <th>Race Date</th>
                <th>Chan</th>
                <th>Cat</th>
        </tr>
        <?php
            foreach ($videoList as $videoData) {
                echo "<tr>";
                    echo "<td>{$videoData['racedate']}</td>";
                    echo "<td>{$videoData['chan']}</td>";
                    echo "<td>{$videoData['cat']}</td>";
                echo "</tr>";
            }
        ?>
</table>

<?php } ?>

  <?php
  $design->closeDiv();
  //$design->rightArea();
  //$design->closeDiv();
  $design->closeDiv();
  $design->pageClose();
$design = NULL; // release object
