<?php
  include_once('../bootstrap.php');
  require_once('../lib/gallery.class.php');
  
  $q = getParameterString('q','',$db);
  $images = new Image($db);
  if ($q == "add-sponsor") {
      $date = getParameterString('date','',$db);
      $sponsorName = getParameterString('sponsor_name','',$db);
      if (!$_FILES['logo']['error'] )  { // error =0 
            $sponsorID = $images->insertSponsor($date,$sponsorName,$_FILES['logo']['name']); 
            if (!file_exists(SPONSOR_GALLERY_BASE."/".$sponsorID)) {
                @mkdir($base.SPONSOR_GALLERY_BASE."/".$sponsorID);                
            }
            move_uploaded_file($_FILES['logo']['tmp_name'],$base.SPONSOR_GALLERY_BASE."/$sponsorID/logo.jpg");
      }
  }
  if ($q == "edit-sponsor") {
      $sponsorID = getParameterNumber('id',0);
      try {
        $sponsorDetails = $images->getSponsorDetails($sponsorID);
      } catch (Exception $err) {
        $msg = "Error Fetching Data. Possibly incorrect id<br />";  
      }
  }
  
  if ($q == "update-sponsor") {
     $date = getParameterString('date','',$db);
     $sponsorID = getParameterNumber('id',0);
     $sponsorName = getParameterString('sponsor_name','',$db);
     try {
          if (!$_FILES['logo']['error'] )  { // error =0 
             // unlink($base.SPONSOR_GALLERY_BASE."/$sponsorID/logo.jpg");
              move_uploaded_file($_FILES['logo']['tmp_name'],$base.SPONSOR_GALLERY_BASE."/$sponsorID/logo.jpg");              
          } 
          $images->updateSponsor($sponsorID,$date,$sponsorName);     
     } catch (Exception $err) {
         echo $err->getMessage();
         $msg = "Error Saving Data. Please try again after sometime<br />";
     }
  }
  
  if ($q== "delete-sponsor") {
      $sponsorID = getParameterNumber('id',0);    
      try {
        $images->deleteSponsor($sponsorID);
        // scan directory for all imgaes
        $filesList = scandir($base.SPONSOR_GALLERY_BASE."/$sponsorID");
        //print_r($filesList);
        //delete all images
        for ($i=2;$i<count($filesList);$i++) {
          unlink($base.SPONSOR_GALLERY_BASE."/$sponsorID/".$filesList[$i]);            
        }
        // remove directory 
        rmdir($base.SPONSOR_GALLERY_BASE."/$sponsorID");
        $msg = "Sponsor Deleted";
      } catch (Exception $err) {
        $msg = "Error Deleting Data.<br />";  
      }
      // clerar image gallery from database for the deleted sponsor     
  }
  
  // fetch all sponsors
  $sponsorsList = $images->getAllSponsors();
  // remove & discard the first element (None) from the array
  array_splice($sponsorsList,0,1);
  
  $pageTitle ='Sponsors Manager';        
  $design = new Design();
  $design->js='
  <script type="text/javascript" src="js/jquery.ui.core.min.js"></script>    
    <script type="text/javascript" src="js/jquery.ui.datepicker.min.js"></script>
    <script type="text/javascript">
        function confirmDelete(sponsorID) {        
            if (confirm ("Are you sure ?")){
                location.href="admin/sponsorManager.php?q=delete-sponsor&id="+sponsorID;
            }
        }
    </script>
  ';
  $design->css ='
  <link type="text/css" href="css/jquery.ui.all.css" rel="stylesheet" />    
  ';
  $design->jqueryJs = "
    $('#race_date').datepicker({
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
    echo $msg; 
  ?>    
   <a href="admin/sponsorManager.php?q=new-sponsor">Add New Sponsor</a>
   
   <?php if ($q == "new-sponsor" || $q== "edit-sponsor") {?> 
   <?php if ($q == "new-sponsor") { ?>  
        <form method="post" action="admin/sponsorManager.php?q=add-sponsor" enctype="multipart/form-data">
   <?php } ?>
    <?php if ($q == "edit-sponsor") { ?>        
        <form method="post" action="admin/sponsorManager.php?q=update-sponsor&id=<?php echo $sponsorDetails['id']; ?>" enctype="multipart/form-data">
    <?php } ?>
           <table class="contentTable">
                <tr>
                    <th>Date</th>
                    <td class="alignLeft"><input type="text" name="date" id='race_date' value='<?php echo $sponsorDetails['racedate']; ?>' /></td>
                </tr>
                <tr>
                    <th>Sponsor Name</th>
                    <td class="alignLeft"><input type="text" name="sponsor_name" id='sponsor_name' value='<?php echo $sponsorDetails['sponsor_name']; ?>' /></td>
                </tr>
                <tr>
                    <th>Upload Logo</th>
                    <td class="alignLeft"><input type="file" name="logo" /></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="submit" name="submit" value="Create">
                        <input type="reset" name="reset" value="Clear">
                    </td>                                  
                </tr>
           </table>
        </form>        
   <?php } ?>
   <br />
   <table class="contentTable">
        <tr>
            <th>ID</th>
            <th>Date</th>
            <th>Sponsor Name</th>
            <th>Action</th>
        </tr>
        <?php 
            foreach ($sponsorsList as $sponsor) { ?>
                <tr>
                    <td><?php echo $sponsor['id'] ?></td>
                    <td><?php echo $sponsor['racedate'] ?></td>
                    <td><?php echo $sponsor['sponsor_name'] ?></td>
                    <td>
                        <a href="admin/sponsorManager.php?q=edit-sponsor&id=<?php echo $sponsor['id'] ?>">Edit</a>
                        <a href="#" onclick="javascript:confirmDelete(<?php echo $sponsor['id'] ?>)">Delete</a>
                    </td>
                </tr>
             <?php } ?>
   </table>
   
<?php                   
  $design->closeDiv();
  //$design->rightArea();  
  //$design->closeDiv();
  $design->closeDiv();
    $design->pageClose();
$design = NULL; // release object