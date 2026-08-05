<?php
  include_once('../bootstrap.php');
  require_once('../lib/gallery.class.php');
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
    if ($_SESSION['gallery'] == "Y") { // check login      
      $images = new Image($db);      
      if ($q == "fetch-sponsor") {
      // fetch all sponsors by date
       $date = getParameterString('date','',$db); 
       $sponsorsList = $images->getSponsorsByRacadate($date);
       $allSponsors = array();
       foreach ($sponsorsList as $sponsors) {
           $allSponsors[$sponsors['id']] = $sponsors['sponsor_name'];
       }
       //print_r($sponsorsList); 
       echo "<select name='sponsorID'>";
        drawOptionsFromHashtable($allSponsors,0);
       echo "</select>";
       exit;
      }
      // all actions POST form submissions go here
      if (isset($_REQUEST['submit'])) {
          
          $date = getParameterString('date','',$db);
          $captions = getParameterString('captions','',$db);
          $raceno= getParameterString('raceno','',$db);
          $sponsorID = getParameterNumber('sponsorID',1);      
          // save new dividend     
          if ($q == "add-image") {
              echo $sponsorID;
              try {
                  $dirname = date("d-M-Y",strtotime($date));
                  if (!file_exists(GALLERY_BASE."/".$dirname)) {
                    @mkdir($base.GALLERY_BASE."/".$dirname,0777);
                  }
                  $file_upload_path = $base.GALLERY_BASE."/".$dirname."/";
                  $file_upload_path_sponsor = $base.SPONSOR_GALLERY_BASE."/".$sponsorID."/";
                  $files_arr = array();
                  $image_name = array();
                  foreach ($_FILES['imageFile']['name'] as $key => $value) {
                    $files_arr[$key]['name'] = $value;
                    $image_name[] = $value;
                  }
                  foreach ($_FILES['imageFile']['type'] as $key => $value) {
                    $files_arr[$key]['type'] = $value;
                  }
                  foreach ($_FILES['imageFile']['tmp_name'] as $key => $value) {
                    $files_arr[$key]['tmp_name'] = $value;
                  }
                  foreach ($_FILES['imageFile']['error'] as $key => $value) {
                    $files_arr[$key]['error'] = $value;
                  }
                  foreach ($_FILES['imageFile']['size'] as $key => $value) {
                    $files_arr[$key]['size'] = $value;
                  }
                  foreach ($files_arr as $fkey => $fvalue) {
                    if($sponsorID == 1){
                      $timestamp = date('YmdHis').rand(10, 10000);
                      $file = $fvalue['name'];
                      if($file != ''){
                        $exist_path = $file_upload_path.$file;
                        if(file_exists($exist_path)){
                          unlink($exist_path);
                        }
                      }
                      if (move_uploaded_file($fvalue['tmp_name'], $file_upload_path . $file)) {
                        $destFile = $file_upload_path . $file;
                        chmod($destFile, 0777);
                      }
                      $images->insertImage($date,$captions,$file,1); // 1- sponsorID which means none 
                    } else if($sponsorID > 1) {
                      if (move_uploaded_file($fvalue['tmp_name'],$file_upload_path_sponsor.'/'.$file)){
                        $destFile = $file_upload_path_sponsor . $file;
                        chmod($destFile, 0777); 
                      } 
                      $images->insertImage($date,$captions,$file,$sponsorID);             
                    }
                  }
                  /*
                  if (!$_FILES['imageFile']['error'] )  { // error =0  
                    $filename = $_FILES['imageFile']['name'];                  
                    if ($sponsorID == 1) {
                      $dirname = date("d-M-Y",strtotime($date));
                      if (!file_exists(GALLERY_BASE."/".$dirname)) {
                        @mkdir($base.GALLERY_BASE."/".$dirname,0777);
                        //@mkdir($base.GALLERY_BASE."/".$dirname."/images",0777);                   
                      }
                      //$filename = basename($filename,".HTM")."_$date.HTM"; 
                      if (move_uploaded_file($_FILES['imageFile']['tmp_name'],$base.GALLERY_BASE."/$dirname/".$filename)) {
                        $id = $images->insertImage($date,$captions,$filename,1); // 1- sponsorID which means none 
                      }
                    } else if($sponsorID > 1) {
                      echo "Here";
                      move_uploaded_file($_FILES['imageFile']['tmp_name'],$base.SPONSOR_GALLERY_BASE."/$sponsorID/$filename"); 
                      $id = $images->insertImage($date,$captions,$filename,$sponsorID);             
                    }
                  }
                  */
              } catch (Exception $err) {
                $msg = $err->getMessage();
            }
          }
      }
      
      if ($q=="delete-image") {
           $imageID=getParameterNumber('id',0);
           $sponsorID = getParameterNumber('sponsorID',1);                
           try {  
           $imageDetails = $images->getImageById($imageID);
           $date = $imageDetails['racedate'];
           if ($sponsorID == 1) {
               $dirname = date("d-M-Y",strtotime($imageDetails['racedate']));   
               if ( unlink($base.GALLERY_BASE."/$dirname/".$imageDetails['filename']) ) {
                    $images->deleteImageByID($imageID);
                    $msg = 'Image Delete successfully';
               } else {
                   $msg = 'Could Not Delete Image. Please try again';
               } 
           } 
           if ($sponsorID > 1) {          
               if ( unlink($base.SPONSOR_GALLERY_BASE."/$sponsorID/".$imageDetails['filename']) ) {
                    $images->deleteImageByID($imageID);
                    $msg = 'Image Delete successfully';
               } else {
                   $msg = 'Could Not Delete Image. Please try again';
               } 
           } 
           
           $q = "view-images";
           
           } catch (Exception $err) {
               $msg = $err->getMessage();
               $msg .= $err->getTraceAsString();        
           }
      }
      
       if ($q == "edit-image") {
         $imageID=getParameterNumber('id',0);                
         $imageDetails = $images->getImageById($imageID);  
      }
      
      if ($q == "update-image") {
          $captions = getParameterString('captions','',$db);
          $imageID=getParameterNumber('id',0);                
          try {
             $images->updateImageCaption($imageID,$captions);
             $msg ='Image Details updated';   
             $q = 'view-images';
             $date = getParameterString('date','',$db);
          } catch (Exception $err) {
              $msg = 'Update Failed. Please try again';
          } 
      }
      
      // view images for a race date
      if ($q == "view-images")  {
           $sponsorID = getParameterNumber('sponsorID',1);
          $date = getParameterString('date','',$db);
          $raceDayImages = $images->getAllImagesByDateAndSponsorID($date,$sponsorID);
      }
      
      $sponsorsList = $images->getAllSponsors();
       $allSponsors = array();
       foreach ($sponsorsList as $sponsors) {
           $allSponsors[$sponsors['id']] = $sponsors['sponsor_name'];
       }
        // fetch all articles
     try {   
        $allDates = $images->getAllDates();
        //echo $allDates->num_rows;
     } catch (Exception $e) {
        $msg = 'No Images Added';
     }
  } else {
        $msg = "You do not have access to this page.";
      }  
} else {
    $secmsg = "Please login to access this page";
}
?>
<?php 
  $pageTitle ='Gallery Manager';        
  $design = new Design();
  $design->js='
  <script type="text/javascript" src="js/jquery.ui.core.min.js"></script>    
    <script type="text/javascript" src="js/jquery.ui.datepicker.min.js"></script>
    <script type="text/javascript">
        function confirmDelete(imageID,sponsorID) {
            //alert(sponsorID);        
            if (confirm ("Are you sure ?")){
                location.href="admin/galleryManager.php?q=delete-image&id="+imageID+"&sponsorID="+sponsorID;
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
    $('#image_date').datepicker({
            showOn: 'button',
            buttonImage: 'images/calendar.gif',
            buttonImageOnly: true,
            dateFormat : 'yy-mm-dd'
        });
        $('.sponsorList').click(function() {  
            if ($('#image_date').val()) {
                    $.ajax( {
                        url : 'admin/galleryManager.php?q=fetch-sponsor&date='+$('#image_date').val(),
                        type: 'GET',
                        success: function (msg) {
                            //alert(msg);
                            $('#sponsors_list').html(msg);
                        }
                    });
            } else {
                alert ('Please select a date');
            }
        });
  "; 
  $design->startPage("$pageTitle");
  
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper");
  $design->openDiv("leftArea");
    //echo $msg;
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
    <?php if ($_SESSION['gallery'] == "Y") { ?>
            <div class="submenu">  
                   <a href="admin/galleryManager.php?q=new-image">Add New Image</a>
                   <a href="admin/galleryManagerbulk.php?q=new-image">Add Bulk Image</a>
                   <div style="float:right;">
                    <a style="float:left;" href="admin/dashboard.php">Dashboard</a>
                    <a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
                  </div>
            </div>   
            
              <br />   
              <?php if ($q=="new-image") { ?>              
              <form name="dividendForm" method="post" action="admin/galleryManager.php" enctype="multipart/form-data">
                <table class="contentTable">
                    <col width="20%"><col width="80%">
                    <tr>
                        <th>Date</th>
                        <td class="alignLeft">
                            <input type="text" name="date" id='image_date' />
                            <span><a class="sponsorList" style="cursor: pointer;">Get Sponsor List</a></span>
                        </td>
                    </tr>
                    <tr>         
                        <th>Title</th>
                        <td class="alignLeft"><input type="text" name="captions" size="50" /></td>
                    </tr>
                    <tr>
                        <th>Sponsor of the Day</th>
                        <td class="alignLeft" id="sponsors_list">
                            
                        </td>
                    </tr>
                    <tr>         
                        <th>Upload Image File</th>
                        <td class="alignLeft"><input type="file" name="imageFile[]" multiple /></td>
                    </tr>                    
                    <tr>
                        <td colspan="2">
                            <input type="submit" name="submit" value="Save" />
                            <input type="reset" name="reset" value="Clear" />
                            <input type="hidden" name="q" value="add-image" />
                        </td>
                    </tr>
                </table>
                <script type='text/javscript'>
                
                </script>
                </form>
                <?php } ?>
                <?php if ($q == "edit-image") {?>                
                     <form name="dividendForm" method="post" action="admin/galleryManager.php?q=update-image&id=<?php  echo $imageDetails['id']; ?>" enctype="multipart/form-data">
                <table class="contentTable">
                    <col width="20%"><col width="80%">                    
                    <tr>         
                        <th>Title</th>
                        <td class="alignLeft"><input type="text" name="captions" size="50" value="<?php echo $imageDetails['caption']; ?>" /></td>
                    </tr>                    
                    <tr>
                        <td colspan="2">
                            <input type="submit" name="submit" value="Save" />
                            <input type="reset" name="reset" value="Clear" />
                            <input type="hidden" name="date" value="<?php echo $imageDetails['racedate']; ?>" />
                        </td>
                    </tr>
                </table>
                </form>
                <?php } ?>
                <br />
              <table class="contentTable" style="margin-top:0px;">                
                <tr>
                    <th>DATE</th>
                    <th>SPONSOR</th>
                    <th>ACTIONS</th>                    
                </tr>
                <?php foreach ($allDates as $raceDate) { ?>
                    <tr>                        
                        <td><?php echo date("d-m-y",strtotime($raceDate['racedate'])); ?></td>
                        <td><?php echo $allSponsors[$raceDate['sponsor_id']]; ?></td>
                        <td>
                            <a href="admin/galleryManager.php?q=view-images&date=<?php echo $raceDate['racedate']; ?>&sponsorID=<?php echo $raceDate['sponsor_id']; ?>">View Images</a>
                        </td>
                    </tr>
                <?php } ?>
              </table>
              <br />              
              <hr />
              <br />
              <?php if ($q== "view-images") { ?>
                  <table class="contentTable">
                    <tr>
                        <th class='thwhite' colspan="3">Images for <?php echo date("d-M-Y",strtotime($date)); ?></th>
                    </tr>
                    <tr>
                        <th>Caption</th>
                        <th>Image</th>
                        <th>Action</th>
                    </tr>                    
                    <?php 
                        foreach ($raceDayImages as $raceDayImage) {
                           echo "<tr>";
                           
                            echo "<td>{$raceDayImage['caption']}</td>";
                            $dirname = date("d-M-Y",strtotime($raceDayImage['racedate']));
                            if ($sponsorID == 1) { 
                                echo "<td><img src='".GALLERY_BASE."/$dirname/".$raceDayImage['filename']."' width='300' height='200' /></td>";
                            }
                            if ($sponsorID > 1) { 
                                echo "<td><img src='".SPONSOR_GALLERY_BASE."/$sponsorID/".$raceDayImage['filename']."' width='300' height='200' /></td>";
                            } 
                            echo "<td>
                                <a href='admin/galleryManager.php?q=edit-image&id={$raceDayImage['id']}'>Edit</a>
                                <a style='cursor:pointer;' onclick='return confirmDelete({$raceDayImage['id']},{$raceDayImage['sponsor_id']})'>Delete</a>
                            </td>";
                           echo "</tr>";
                        }
                    ?>
                  </table>
                  
              <?php }?>
        <?php } ?>
             <?php                   
  $design->closeDiv();
  //$design->rightArea();  
  //$design->closeDiv();
  $design->closeDiv();
    $design->pageClose();
$design = NULL; // release object
