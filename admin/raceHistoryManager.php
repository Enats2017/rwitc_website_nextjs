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
  $design->css ='';
  $design->jqueryJs = ""; 
  $design->startPage("$pageTitle");  
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper");
  $design->openDiv("leftArea");
?>

<style type="text/css">
#leftArea { max-width: 1500px; margin: 30px auto; padding: 0 30px; box-sizing: border-box; float: none; width: auto; display: block; }
.message { background: #fff3cd; border: 1px solid #ffe08a; padding: 12px 16px; border-radius: 8px; margin-bottom: 15px; font-size: 15px; }

.races-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.add-race-btn { display: inline-flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #1a7a45; color: #0f5c33; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; }
.add-race-btn:hover { background: #e6f4ec; }
.header-links { display: flex; align-items: center; gap: 16px; }
.header-links a { color: #0f5c33; text-decoration: none; font-weight: 600; font-size: 14px; }
.header-links a:hover { text-decoration: underline; }

.race-form-wrap { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; padding: 20px; margin-bottom: 24px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); }
.race-form-wrap .contentTable { width: 100%; }
.race-form-wrap .contentTable th { text-align: left; padding: 10px 8px; color: #2b332f; vertical-align: top; }
.race-form-wrap .contentTable td { padding: 10px 8px; }
.race-form-wrap input[type="text"] { border: 1px solid #e2e6e4; border-radius: 6px; padding: 8px 10px; font-size: 14px; }
.race-form-wrap input[type="submit"], .race-form-wrap input[type="reset"] { background: #0f5c33; color: #fff; border: none; padding: 9px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; margin-right: 8px; }
.race-form-wrap input[type="reset"] { background: #fff; color: #2b332f; border: 1px solid #e2e6e4; }

.races-table-wrap { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.03); margin-bottom: 24px; }
table.races-table { width: 100%; border-collapse: collapse; font-size: 14.5px; }
table.races-table th { background: #0b3d24; color: #fff; text-align: left; padding: 14px 20px; font-weight: 600; font-size: 13.5px; letter-spacing: 0.3px; }
table.races-table th.action-col { text-align: right; width: 140px; }
table.races-table td { padding: 14px 20px; border-bottom: 1px solid #eef0ee; color: #2b332f; }
table.races-table tr:last-child td { border-bottom: none; }
table.races-table tr:nth-child(even) td { background: #f7faf8; }
table.races-table tr:hover td { background: #e6f4ec; }
table.races-table td.action-col { text-align: right; white-space: nowrap; }
table.races-table td.action-col a { font-size: 13.5px; text-decoration: none; font-weight: 500; margin-left: 14px; }
table.races-table td.action-col a.edit-link { color: #0f5c33; }
table.races-table td.action-col a.delete-link { color: #c0392b; }
.races-empty { padding: 30px 20px; text-align: center; color: #7a8c84; font-size: 14.5px; }

@media (max-width: 700px) {
    #leftArea { padding: 0 16px; }
    .races-header { flex-direction: column; align-items: flex-start; }
    table.races-table th, table.races-table td { padding: 10px 12px; font-size: 13.5px; }
    table.races-table td.action-col a { margin-left: 10px; }
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
    <?php if ($_SESSION['race_history'] == "Y") { ?>
        <div class="races-header">
          <a class="add-race-btn" href="admin/raceHistoryManager.php?q=new-race"><i class="fas fa-plus"></i> Add New Race</a>
          <div class="header-links">
                <a href="admin/dashboard.php">Dashboard</a>
                <a href="admin/adminlogin.php?q=logout">Logout</a>
           </div>
        </div>
          
          <?php if ($q=="new-race" || $q=="edit-race") { ?>              
           <div class="race-form-wrap">
           <form name="articleForm" method="post" action="admin/raceHistoryManager.php">
            <table class="contentTable">
                <col width="20%"><col width="80%">
                <tr>
                    <th>Title</th>
                    <td class="alignLeft"><input type="text" name="title" id='title' size="50" value="<?php echo $raceDetails['title'] ?>" /></td>
                </tr>
                <tr>
                    <th>Message</th>
                    <td class="alignLeft"><textarea name="message" id="message"><?php echo $raceDetails['body'] ?></textarea></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="submit" name="submit" value="Save" />
                        <input type="reset" name="reset" value="Clear" onclick="location.href='admin/raceHistoryManager.php'" />
                        <?php if ($q=="new-race") { ?>
                            <input type="hidden" name="q" value="add-race" />
                        <?php } elseif ($q == "edit-race") { ?>
                                <input type="hidden" name="q" value="update-race" />
                                <input type="hidden" name="id" value="<?php echo $raceID; ?>" />
                        <?php  }   ?>
                    </td>
                </tr>
            </table>
            </form>
           </div>
			<script>
              CKEDITOR.replace('message', {
                skin: 'moono',
                enterMode: CKEDITOR.ENTER_BR,
                shiftEnterMode:CKEDITOR.ENTER_P,
                toolbar: [{ name: 'basicstyles', groups: [ 'basicstyles' ], items: [ 'Bold', 'Italic', 'Underline', "-", 'TextColor', 'BGColor' ] },
                           { name: 'styles', items: [ 'Format', 'Font', 'FontSize' ] },
                           { name: 'scripts', items: [ 'Subscript', 'Superscript' ] },
                           { name: 'justify', groups: [ 'blocks', 'align' ], items: [ 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock' ] },
                           { name: 'paragraph', groups: [ 'list', 'indent' ], items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent'] },
                           { name: 'links', items: [ 'Link', 'Unlink' ] },
                           { name: 'insert', items: [ 'Image'] },
                           { name: 'spell', items: [ 'jQuerySpellChecker' ] },
                           { name: 'table', items: [ 'Table' ] }
                           ],
              });

		</script>
           
          <?php } ?>

          <div class="races-table-wrap">
          <table class="races-table">
            <tr>
                <th>Title</th>                
                <th class="action-col">Action</th>                    
            </tr>
            <?php if (count($allRaces) > 0) { ?>
                <?php foreach ($allRaces as $raceInfo) { ?>
                    <tr>
                        <td><?php echo $raceInfo['title']; ?></td>                    
                        <td class="action-col">
                            <a class="edit-link" href="admin/raceHistoryManager.php?id=<?php echo $raceInfo['id'];?>&q=edit-race"><i class="fas fa-edit"></i> Edit</a>
                            <a class="delete-link" href="javascript:void(0);" onclick="javascript: confirmDelete(<?php echo $raceInfo['id']; ?>);"><i class="fas fa-trash-alt"></i> Delete</a>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="2" class="races-empty">No races added yet.</td>
                </tr>
            <?php } ?>
          </table>
          </div>
    <?php }  ?>
<?php                   
  $design->closeDiv();
  //$design->rightArea();  
  //$design->closeDiv();
  $design->closeDiv();
    $design->pageClose();
$design = NULL; // release object
?>