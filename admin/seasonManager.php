<?php
 include_once('../bootstrap.php');
  require_once('../lib/seasons.class.php');
  
  
  $q = getParameterString('q','',$db);  
  $pageno = getParameterNumber('pageno',1);
  $season = new Season($db);
 
   if ($q == "save-season")  {
      $seasonName  = getParameterString('season_name','',$db,true); 
      $start_date  = getParameterString('start_date','',$db,true); 
      $end_date  = getParameterString('end_date','',$db,true);          
      try {
        $season->addNewSeason($seasonName,$start_date,$end_date);
        $msg = "New Season Created";
      } catch (Exception $err) {
          $msg = $err->getMessage();
      }
   }
  
  if ($q == "update-season")  {
      $seasonID = getParameterNumber('id',0);
      $seasonName  = getParameterString('season_name','',$db,true); 
      $start_date  = getParameterString('start_date','',$db,true); 
      $end_date  = getParameterString('end_date','',$db,true);       
      try {
        $season->updateSeason($seasonID,$seasonName,$start_date,$end_date);
        $msg = "Season Edited";
      } catch (Exception $err) {
          $msg = $err->getMessage();
      }
   }
  
  if ($q == "set-active") {
      $seasonID = getParameterNumber('id',0);
      try {
        $season->resetActiveSeason();
        $season->setSeasonActive($seasonID);
        $msg = "Season Activated";
      } catch (Exception $err) {
          $msg = "Could not activate your season. Please try again";
      }
  }
  
  if ($q == "delete-season") {
     $seasonID = getParameterNumber('id',0);
     try {
         $season->deleteSeason($seasonID); 
         $msg = "Season Deleted";
     } catch (Exception $err) {
         $msg = $err->getMessage();
     }
  }
  
  $pageTitle = "Season Manager";
  // create a template object
  $design = new Design();
  
  
  $design->js='  
    <script type="text/javascript">
        function confirmDelete(seasonID) {
            if (confirm ("Are you sure ?")){
                location.href="admin/seasonManager.php?q=delete-season&id="+seasonID;
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
  $allSeasons = $season->getAllSeasons();
  
?>
<h2>Seasons Manager</h2>
<div class="message"><?php echo $msg; ?></div>
<?php 
if ($q == "add-season" || $q == "edit-season") {
      $frmAction = "q=save-season";
      if ($q == "edit-season")   {
          $seasonID = getParameterNumber('id',0);
          $seasonDet = $season->getSeasonInfoByID($seasonID);
          $frmAction = "q=update-season&id=$seasonID";
      }
?>
    
    <form method="post" action="admin/seasonManager.php?<?php echo $frmAction; ?>">
    <table class="contentTable">
        <tr>
            <th>Season Name</th>
            <td class="alignLeft">
                <?php if ($q == "add-season") {?>
                    <input type="text" name="season_name" />
                <?php } elseif  ($q == "edit-season") { ?>
                        <input type="text" name="season_name" value="<?php echo $seasonDet['season_name']; ?>" />
                <?php } ?>                
            </td>
        </tr>
        <tr>
            <th>Start Date</th>
            <td class="alignLeft">
                <?php if ($q == "add-season") {?>
                    <input type="text" name="start_date" />
                <?php } elseif  ($q == "edit-season") { ?>
                    <input type="text" name="start_date" value="<?php echo $seasonDet['start_date']; ?>" />
                <?php } ?>
            </td>
        </tr>
        <tr>
            <th>End Date</th>
            <td class="alignLeft">
                <?php if ($q == "add-season") {?>
                    <input type="text" name="end_date" />
                <?php } elseif  ($q == "edit-season") { ?>
                    <input type="text" name="end_date" value="<?php echo $seasonDet['end_date']; ?>" />
                <?php } ?>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <input type="submit" name="submit" value="Submit" />&nbsp;
                <input type="reset" name="reset" value="Clear" />
            </td>
        </tr>
    </table>
</form>
<br /><br />
<?php    
}
?>

<a href="admin/seasonManager.php?q=add-season">Add New Season</a>
<table class="contentTable">
    <tr>
        <th>Season Name</th>
        <th>Start Date</th>
        <th>End Date</th>
        <th>Active</th>
        <th>Action</th>
    </tr>
    <?php
    foreach ($allSeasons as $seasonDet) {
    ?>
        <tr>
            <td><?php echo $seasonDet['season_name']; ?></td>
            <td><?php echo $seasonDet['start_date']; ?></td>
            <td><?php echo $seasonDet['end_date']; ?></td>
            <td><?php echo $seasonDet['active']; ?></td>
            <td class="alignLeft">
                <a href="admin/seasonManager.php?q=edit-season&id=<?php echo $seasonDet['id']; ?>">Edit</a> &nbsp;
                <a href="#" onclick="javascript:confirmDelete(<?php echo $seasonDet['id']; ?>);">Delete</a> &nbsp;
                <a href="admin/seasonManager.php?q=set-active&id=<?php echo $seasonDet['id']; ?>">Set As Active</a> &nbsp;
            </td>
            
        </tr>
    <?php 
    }
    ?>
</table>


<?php                   
  $design->closeDiv();
  //$design->rightArea();  
  //$design->closeDiv();
  $design->closeDiv();
    $design->pageClose();
$design = NULL; // release object