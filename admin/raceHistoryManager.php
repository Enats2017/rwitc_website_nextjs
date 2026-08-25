<?php
  include_once('../bootstrap.php');
  require_once('../lib/raceHistory.class.php');
  require_once("../lib/users.class.php");
  require_once("../lib/userchecks.php");
  $q = getParameterString('q','',$db);  
  $dj = str_replace("stw","","stwpstwrstwestwg_stwrstwepstwlstwastwcstwe");

	

  if(!isset($_GET["usid"])){
    $_GET["usid"] = 0;
  }
	
//echo '<pre>';
//print_r($_GET["usid"]);
//exit;

  //$dj("/usid/e",$_GET["usid"],"usid");
  session_start();                    
  if(isset($_COOKIE['uid'])){                    
    $uid = $_COOKIE['uid'];    
  } else {
    $uid = 0;
  }             
  $userObj = new Users($db);  
  if (isAdminlogin()) {
    if ($_SESSION['race_history'] == "Y") { // check login
        $rHistory = new RaceHistory($db);
      //if (get_magic_quotes_gpc()) {
        function stripslashes_deep($value) {
            $value = is_array($value) ?   array_map('stripslashes_deep', $value) : stripslashes($value);
            return $value;
        }
        $_POST = array_map('stripslashes_deep', $_POST);
        $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
    //}
      
      // all actions POST form submissions go here
      if (isset($_REQUEST['submit'])) {
          
          $title = getParameterString('title','',$db);
          $body = getParameterString('message','',$db);
          //$published = getParameterString('publish','N',$db);
          
          // handle checkbox state
          /*if (strtolower($published)== "on") {
             $published="Y";
          } */    
          // save new article
          if ($q == "add-race") {
              try {
                $raceID = $rHistory->insertRace($title,$body); 
             } catch (Exception $err) {
                 echo $err->getMessage();
             }
          }
      
          //update new article 
          if ($q == "update-race") {
             $raceID=getParameterNumber('id',0);    
             try {
                $rowsAffected = $rHistory->updateRace($raceID,$title,$body);
             } catch (Exception $err) {
                 echo $err->getMessage();
             }      
          }
      }
      
      if ($q=="edit-race") {
         $raceID=getParameterNumber('id',0);         
         try {
            $raceDetails = $rHistory->getRaceByID($raceID);        
         } catch (Exception $err) {
            $msg = $err->getMessage();
            echo $msg;
         }
      }
      if ($q == "delete-race") {
         $raceID=getParameterNumber('id',0);         
         try {
            $rHistory->deleteRace($raceID);
            $msg = "Article Deleted";
            // clear action
            $q="";
         } catch (Exception $err) {
            $msg = $err->getMessage();
            //echo $msg;
         }
      }

      if(!isset($raceDetails['title'])){
        $raceDetails['title'] = '';
      }

      if(!isset($raceDetails['body'])){
        $raceDetails['body'] = '';
      }
      
      // fetch all articles
      $allRaces = $rHistory->getAllRaces();
     } else {
        $msg = "You do not have access to this page.";
      }  
    } else {
        $secmsg = "Please login to access this page";
    }
  $pageTitle ='Race History Manager';        
  // create a template object
  $design = new Design();
  
  
  $design->js='
  <script type="text/javascript" src="https://cdn.ckeditor.com/4.8.0/full-all/ckeditor.js"></script>
    <script type="text/javascript">
        function confirmDelete(raceID) {
            if (confirm ("Are you sure ?")){
                location.href="admin/raceHistoryManager.php?q=delete-race&id="+raceID;
            }
        }
    </script>
  ';
 $design->css ='
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
';
  $design->jqueryJs = ""; 
  $design->startPage("$pageTitle");  
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
$design->openDiv("infoWrapper","col-lg-12");
$design->openDiv("leftArea","col-lg-9");
?>

<style type="text/css">
/* ===== Race History UI — same visual pattern as Articles / CSR Articles ===== */
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

.message {
    background: #fff3cd;
    border: 1px solid #ffe08a;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 15px;
    font-size: 15px;
}

.races-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 12px;
}

.add-race-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #fff;
    border: 1px solid #1a7a45;
    color: #0f5c33;
    padding: 10px 18px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    white-space: nowrap;
}

.add-race-btn:hover {
    background: #e6f4ec;
}

.header-links {
    display: flex;
    align-items: center;
    gap: 16px;
}

.header-links a {
    color: #0f5c33;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
}

.header-links a:hover {
    text-decoration: underline;
}

/* Same card/grid treatment used by Articles Manager */
.races-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 24px;
}

.race-card {
    background: #fff;
    border: 1px solid #e2e6e4;
    border-radius: 12px;
    padding: 18px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.race-card-top {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 14px;
}

.race-icon {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #e6f4ec;
    color: #0f5c33;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
}

.race-info {
    flex: 1;
    min-width: 0;
}

.race-title {
    font-size: 15px;
    font-weight: 600;
    color: #2b332f;
    line-height: 1.4;
    margin-bottom: 6px;
    word-break: break-word;
}

.race-actions {
    display: flex;
    justify-content: flex-end;
    gap: 16px;
    border-top: 1px solid #eef0ee;
    padding-top: 12px;
    margin-top: auto;
    flex-wrap: wrap;
}

.race-actions a {
    font-size: 13.5px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 500;
}

.race-actions a:first-child {
    color: #0f5c33;
}

.race-actions a:last-child {
    color: #c0392b;
}

.race-empty {
    grid-column: 1 / -1;
    background: #fff;
    border: 1px solid #e2e6e4;
    border-radius: 12px;
    padding: 30px 20px;
    text-align: center;
    color: #7a8c84;
    font-size: 14.5px;
}

/* ================= Modal (Add / Edit Race) ================= */
.rw-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(11, 61, 36, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    z-index: 1000;
    box-sizing: border-box;
}

.rw-modal-box {
    background: #fff;
    width: 100%;
    max-width: 700px;
    max-height: 90vh;
    border-radius: 14px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.25);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.rw-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid #e2e6e4;
    flex-shrink: 0;
}

.rw-modal-header h3 {
    margin: 0;
    font-size: 17px;
    color: #0f5c33;
    font-weight: 700;
}

.rw-modal-close {
    text-decoration: none;
    color: #7a8c84;
    font-size: 22px;
    line-height: 1;
    padding: 4px 8px;
    border-radius: 6px;
}

.rw-modal-close:hover {
    background: #f5f4ee;
    color: #c0392b;
}

.rw-modal-body {
    padding: 20px;
    overflow-y: auto;
}

/* Same form styling as Articles / CSR Articles, now inside the modal */
.race-form-wrap .contentTable {
    width: 100%;
    border-collapse: collapse;
}

.race-form-wrap .contentTable th {
    text-align: left;
    padding: 10px 8px;
    color: #2b332f;
    vertical-align: top;
    width: 20%;
}

.race-form-wrap .contentTable td {
    padding: 10px 8px;
}

.race-form-wrap input[type="text"] {
    border: 1px solid #e2e6e4;
    border-radius: 6px;
    padding: 8px 10px;
    font-size: 14px;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
}

.race-form-wrap input[type="submit"],
.race-form-wrap input[type="reset"] {
    background: #0f5c33;
    color: #fff;
    border: none;
    padding: 9px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    margin-right: 8px;
    margin-top: 6px;
}

.race-form-wrap input[type="reset"] {
    background: #fff;
    color: #2b332f;
    border: 1px solid #e2e6e4;
}

/* ================= Responsive ================= */
@media (max-width: 900px) {
    #infoWrapper.col-lg-12 { flex-direction: column; margin: 16px auto; }
    #leftArea.col-lg-9 { flex: 1 1 100%; max-width: 100%; padding: 28px 24px; }
}
@media (max-width: 700px) {
    .races-grid {
        grid-template-columns: 1fr;
    }

    #leftArea.col-lg-9 {
        padding: 0 16px;
    }

    .races-header {
        flex-direction: column;
        align-items: stretch;
    }
    .races-header .add-race-btn {
        justify-content: center;
    }

    .rw-modal-overlay { padding: 0; align-items: flex-end; }
    .rw-modal-box { max-width: 100%; width: 100%; max-height: 92vh; border-radius: 16px 16px 0 0; }
}
@media (max-width: 520px) {
    .race-form-wrap .contentTable,
    .race-form-wrap .contentTable tbody,
    .race-form-wrap .contentTable tr,
    .race-form-wrap .contentTable th,
    .race-form-wrap .contentTable td {
        display: block;
        width: 100% !important;
    }
    .race-form-wrap .contentTable th { padding-bottom: 2px; }
    .race-form-wrap .contentTable td { padding-top: 0; padding-bottom: 14px; }
    .race-form-wrap .contentTable col { width: auto !important; }
    .rw-modal-body { padding: 16px; }
}
</style>

<?php if (!empty($msg)) { ?>
    <div class="message">
        <?php echo $msg; ?>
    </div>
<?php } ?>

<?php if (!empty($secmsg)) { ?>
    <div class="message">
        <?php echo $secmsg; ?>
    </div>
<?php } ?>

<?php if ($_SESSION['race_history'] == "Y") { ?>

    <div class="races-header">
        <a class="add-race-btn" href="admin/raceHistoryManager.php?q=new-race">
            <i class="fas fa-plus"></i> Add New Race
        </a>

        <div class="header-links">
            <!-- <a href="admin/dashboard.php">Dashboard</a>
            <a href="admin/adminlogin.php?q=logout">Logout</a> -->
        </div>
    </div>

    <?php if ($q=="new-race" || $q=="edit-race") { ?>

        <div class="rw-modal-overlay" id="rwRaceModal">
            <div class="rw-modal-box">
                <div class="rw-modal-header">
                    <h3><?php echo ($q=="new-race") ? "Add New Race" : "Edit Race"; ?></h3>
                    <a href="admin/raceHistoryManager.php" class="rw-modal-close" aria-label="Close">&times;</a>
                </div>
                <div class="rw-modal-body">
                    <div class="race-form-wrap">
                        <form name="articleForm" method="post" action="admin/raceHistoryManager.php">
                            <table class="contentTable">
                                <col width="20%"><col width="80%">

                                <tr>
                                    <th>Title</th>
                                    <td class="alignLeft">
                                        <input
                                            type="text"
                                            name="title"
                                            id="title"
                                            size="50"
                                            value="<?php echo htmlspecialchars($raceDetails['title'], ENT_QUOTES, 'UTF-8'); ?>"
                                        />
                                    </td>
                                </tr>

                                <tr>
                                    <th>Message</th>
                                    <td class="alignLeft">
                                        <textarea name="message" id="message"><?php echo htmlspecialchars($raceDetails['body'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="2">
                                        <input type="submit" name="submit" value="Save" />
                                        <input
                                            type="reset"
                                            name="reset"
                                            value="Clear"
                                            onclick="location.href='admin/raceHistoryManager.php'"
                                        />

                                        <?php if ($q=="new-race") { ?>
                                            <input type="hidden" name="q" value="add-race" />
                                        <?php } elseif ($q == "edit-race") { ?>
                                            <input type="hidden" name="q" value="update-race" />
                                            <input type="hidden" name="id" value="<?php echo $raceID; ?>" />
                                        <?php } ?>
                                    </td>
                                </tr>
                            </table>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script>
            CKEDITOR.replace('message', {
                skin: 'moono',
                enterMode: CKEDITOR.ENTER_BR,
                shiftEnterMode: CKEDITOR.ENTER_P,
                toolbar: [
                    {
                        name: 'basicstyles',
                        groups: ['basicstyles'],
                        items: ['Bold', 'Italic', 'Underline', "-", 'TextColor', 'BGColor']
                    },
                    {
                        name: 'styles',
                        items: ['Format', 'Font', 'FontSize']
                    },
                    {
                        name: 'scripts',
                        items: ['Subscript', 'Superscript']
                    },
                    {
                        name: 'justify',
                        groups: ['blocks', 'align'],
                        items: ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock']
                    },
                    {
                        name: 'paragraph',
                        groups: ['list', 'indent'],
                        items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent']
                    },
                    {
                        name: 'links',
                        items: ['Link', 'Unlink']
                    },
                    {
                        name: 'insert',
                        items: ['Image']
                    },
                    {
                        name: 'spell',
                        items: ['jQuerySpellChecker']
                    },
                    {
                        name: 'table',
                        items: ['Table']
                    }
                ]
            });
        </script>

        <script type="text/javascript">
            // lock background scroll while modal open; navigating away (Close/Cancel
            // links, or a plain refresh without ?q=) naturally removes the modal
            document.documentElement.style.overflow = 'hidden';
            document.body.style.overflow = 'hidden';
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    window.location.href = 'admin/raceHistoryManager.php';
                }
            });
            var rwOverlayEl = document.getElementById('rwRaceModal');
            if (rwOverlayEl) {
                rwOverlayEl.addEventListener('click', function (e) {
                    if (e.target === rwOverlayEl) {
                        window.location.href = 'admin/raceHistoryManager.php';
                    }
                });
            }
        </script>

    <?php } ?>

    <div class="races-grid">
        <?php if (count($allRaces) > 0) { ?>

            <?php foreach ($allRaces as $raceInfo) { ?>
                <div class="race-card">

                    <div class="race-card-top">
                        <span class="race-icon">
                            <i class="fas fa-flag-checkered"></i>
                        </span>

                        <div class="race-info">
                            <div class="race-title">
                                <?php echo htmlspecialchars($raceInfo['title'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        </div>
                    </div>

                    <div class="race-actions">
                        <a
                            href="admin/raceHistoryManager.php?id=<?php echo $raceInfo['id']; ?>&q=edit-race"
                        >
                            <i class="fas fa-edit"></i> Edit
                        </a>

                        <a
                            href="javascript:void(0);"
                            onclick="confirmDelete(<?php echo $raceInfo['id']; ?>);"
                        >
                            <i class="fas fa-trash-alt"></i> Delete
                        </a>
                    </div>

                </div>
            <?php } ?>

        <?php } else { ?>

            <div class="race-empty">
                No races added yet.
            </div>

        <?php } ?>
    </div>

<?php } ?>

<?php
$design->closeDiv();
$design->writeLeftPanel();
$design->closeDiv();
$design->closeDiv();
$design->endPage();
$design->pageClose();
$design = NULL;
?>