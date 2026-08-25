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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea","col-lg-9");
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

.videos-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.videos-header h2 { margin: 0; font-size: 22px; color: #2b332f; font-weight: 700; }
.videos-header p { margin: 4px 0 0; font-size: 13.5px; color: #7a8c84; }

.section-title { font-size: 16px; font-weight: 700; color: #0f5c33; margin: 28px 0 14px; display: flex; align-items: center; gap: 8px; }

/* ===== Add Video form — normal inline screen card ===== */
.video-form-wrap {
    background: #fff;
    border: 1px solid #e2e6e4;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}
.video-form-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 16px;
}
.video-form-field label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #2b332f;
    margin-bottom: 6px;
}
.video-form-field input[type="text"] {
    width: 100%;
    box-sizing: border-box;
    border: 1px solid #e2e6e4;
    border-radius: 6px;
    padding: 9px 12px;
    font-size: 14px;
}
.video-form-actions { display: flex; gap: 10px; }
.video-form-actions input[type="submit"] { background: #0f5c33; color: #fff; border: none; padding: 10px 22px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; }
.video-form-actions input[type="submit"]:hover { background: #0b3d24; }
.video-form-actions input[type="reset"] { background: #fff; color: #2b332f; border: 1px solid #e2e6e4; padding: 10px 22px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; }
.video-form-actions input[type="reset"]:hover { background: #f5f4ee; }

/* ===== recent videos table ===== */
.videos-table-wrap { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.03); }
table.videos-table { width: 100%; border-collapse: collapse; font-size: 14.5px; }
table.videos-table th { background: #0b3d24; color: #fff; text-align: left; padding: 14px 20px; font-weight: 600; font-size: 13px; letter-spacing: 0.3px; }
table.videos-table td { padding: 14px 20px; border-bottom: 1px solid #eef0ee; color: #2b332f; }
table.videos-table tr:last-child td { border-bottom: none; }
table.videos-table tr:nth-child(even) td { background: #f7faf8; }
table.videos-table tr:hover td { background: #e6f4ec; }
.videos-empty { padding: 30px 20px; text-align: center; color: #7a8c84; font-size: 14.5px; }

/* ===== responsive ===== */
@media (max-width: 900px) {
    #infoWrapper.col-lg-12 { flex-direction: column; margin: 16px auto; }
    #leftArea.col-lg-9 { flex: 1 1 100%; max-width: 100%; padding: 28px 24px; }
}
@media (max-width: 700px) {
    #leftArea.col-lg-9 { padding: 0 16px; }
    .video-form-grid { grid-template-columns: 1fr; gap: 12px; }
    table.videos-table th, table.videos-table td { padding: 10px 12px; font-size: 13.5px; }
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
<?php if ($_SESSION['video'] == "Y") { ?>

    <div class="videos-header">
        <div>
            <h2>Videos Manager</h2>
            <p>Add a new video slot linked to a race date, channel and category.</p>
        </div>
        <!--
        <div style="float:right;">
            <a style="float:left;" href="admin/dashboard.php">Dashboard</a>
            <a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
        </div>
        -->
    </div>

    <div class="video-form-wrap">
        <form method="post" action="admin/manageVideos.php">
            <div class="video-form-grid">
                <div class="video-form-field">
                    <label for="racedate">Race Date</label>
                    <input type="text" name="racedate" id="racedate" />
                </div>
                <div class="video-form-field">
                    <label for="chan">Channel</label>
                    <input type="text" name="chan" id="chan" />
                </div>
                <div class="video-form-field">
                    <label for="cat">Category</label>
                    <input type="text" name="cat" id="cat" />
                </div>
            </div>
            <div class="video-form-actions">
                <input type="submit" name="submit" value="Add" />
                <input type="reset" name="reset" value="Clear" />
                <input type="hidden" name="q" value="add-video" />
            </div>
        </form>
    </div>

    <div class="section-title"><i class="fas fa-video"></i> List of Last 50 Recent Videos</div>
    <div class="videos-table-wrap">
        <table class="videos-table">
            <tr>
                <th>Race Date</th>
                <th>Chan</th>
                <th>Cat</th>
            </tr>
            <?php if (count($videoList) > 0) { ?>
                <?php foreach ($videoList as $videoData) { ?>
                    <tr>
                        <td><?php echo $videoData['racedate']; ?></td>
                        <td><?php echo $videoData['chan']; ?></td>
                        <td><?php echo $videoData['cat']; ?></td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="3" class="videos-empty">No videos added yet.</td>
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