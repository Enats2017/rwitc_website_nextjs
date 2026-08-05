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
        <div class="submenu">
          <a href="admin/raceHistoryManager.php?q=new-race">Add New Race</a>
           <div style="float:right;">
                    <a style="float:left;" href="admin/dashboard.php">Dashboard</a>
                    <a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
                  </div>
        </div>
          <br />
          
          <?php if ($q=="new-race" || $q=="edit-race") { ?>              
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
            <br />
            <hr />
            <br />
          <br />
          <table class="contentTable">
            <tr>
                <th>Title</th>                
                <th>Action</th>                    
            </tr>
            <?php foreach ($allRaces as $raceInfo) { ?>
                <tr>
                    <td><?php echo $raceInfo['title']; ?></td>                    
                    <td>
                        <a href="admin/raceHistoryManager.php?id=<?php echo $raceInfo['id'];?>&q=edit-race">Edit</a>
                        <a href="javascript:void(0);" onclick="javascript: confirmDelete(<?php echo $raceInfo['id']; ?>);" >Delete</a>
                    </td>
                </tr>
            <?php } ?>
          </table>
          <br />     
    <?php }  ?>
<?php                   
  $design->closeDiv();
  //$design->rightArea();  
  //$design->closeDiv();
  $design->closeDiv();
    $design->pageClose();
$design = NULL; // release object
?>