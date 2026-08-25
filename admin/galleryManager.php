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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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

        /* ===== date-search widget (replaces scrolling through the full race-day list) ===== */
        $('#search_image_date').datepicker({
            buttonImage: 'images/calendar.gif',
            buttonImageOnly: true,
            dateFormat : 'yy-mm-dd'
        });
        $('#searchSponsorTrigger').click(function() {
            if ($('#search_image_date').val()) {
                $.ajax({
                    url: 'admin/galleryManager.php?q=fetch-sponsor&date=' + $('#search_image_date').val(),
                    type: 'GET',
                    success: function (msg) {
                        $('#search_sponsors_list').html(msg);
                    }
                });
            } else {
                alert('Please select a date');
            }
        });
        $('#viewImagesBtn').click(function() {
            var d = $('#search_image_date').val();
            if (!d) { alert('Please select a date'); return; }
            var sponsorSelect = $('#search_sponsors_list select[name=sponsorID]');
            var sid = sponsorSelect.length ? sponsorSelect.val() : 1;
            window.location.href = 'admin/galleryManager.php?q=view-images&date=' + encodeURIComponent(d) + '&sponsorID=' + sid;
        });
  "; 
  $design->startPage("$pageTitle");
  
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea","col-lg-9");
  ?>

<style type="text/css">
/* ===== layout: leftArea + sidebar, same pattern as Articles / Race History ===== */
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

/* ===== message ===== */
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

/* ===== header ===== */
.gallery-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.gallery-header-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.gallery-btn { display: inline-flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #1a7a45; color: #0f5c33; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; white-space: nowrap; cursor: pointer; }
.gallery-btn:hover { background: #e6f4ec; }
.gallery-btn.solid { background: #0f5c33; color: #fff; border-color: #0f5c33; }
.gallery-btn.solid:hover { background: #0b3d24; }

.section-title { font-size: 16px; font-weight: 700; color: #0f5c33; margin: 28px 0 14px; display: flex; align-items: center; gap: 8px; }

/* ===== date search bar ===== */
.gallery-search-card {
    background: #fff;
    border: 1px solid #e2e6e4;
    border-radius: 12px;
    padding: 18px 20px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    margin-bottom: 24px;
}
.gallery-search-title { font-size: 13px; font-weight: 700; color: #7a8c84; letter-spacing: .3px; text-transform: uppercase; margin-bottom: 12px; }
.gallery-search-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.gallery-search-row input[type="text"] {
    border: 1px solid #e2e6e4; border-radius: 6px; padding: 9px 12px; font-size: 14px; min-width: 160px;
}
.gallery-search-row .sponsorList { font-size: 13px; color: #0f5c33; text-decoration: none; font-weight: 600; white-space: nowrap; }
.gallery-search-row .sponsorList:hover { text-decoration: underline; }
#search_sponsors_list select { border: 1px solid #e2e6e4; border-radius: 6px; padding: 8px 10px; font-size: 14px; }

/* ===== collapsible full race-day list (kept, but tucked away) ===== */
.gallery-browse-all { margin-bottom: 24px; }
.gallery-browse-all summary {
    cursor: pointer; font-size: 13.5px; font-weight: 600; color: #0f5c33;
    padding: 10px 4px; list-style: none; display: flex; align-items: center; gap: 8px;
}
.gallery-browse-all summary::-webkit-details-marker { display: none; }
.gallery-browse-all summary i { transition: transform .2s ease; }
.gallery-browse-all[open] summary i { transform: rotate(90deg); }
.gallery-browse-all-panel { max-height: 360px; overflow-y: auto; padding-top: 8px; }

/* ===== dates grid ===== */
.dates-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 14px; }
.date-card { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; padding: 14px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); display: flex; flex-direction: column; gap: 8px; }
.date-card-top { display: flex; align-items: center; gap: 10px; }
.date-icon { width: 34px; height: 34px; border-radius: 50%; background: #e6f4ec; color: #0f5c33; display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0; }
.date-value { font-size: 14.5px; font-weight: 700; color: #2b332f; }
.date-sponsor { font-size: 12px; color: #7a8c84; }
.date-card a.view-link { margin-top: auto; align-self: flex-start; font-size: 13px; font-weight: 600; color: #0f5c33; text-decoration: none; display: flex; align-items: center; gap: 6px; }
.date-card a.view-link:hover { text-decoration: underline; }

/* ===== images grid (used inside the View Images modal) ===== */
.images-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 18px; }
.image-card { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.03); display: flex; flex-direction: column; }
.image-card .thumb-wrap { width: 100%; aspect-ratio: 3 / 2; background: #f5f4ee; overflow: hidden; }
.image-card .thumb-wrap img { width: 100%; height: 100%; object-fit: cover; display: block; }
.image-card .image-body { padding: 12px 14px; display: flex; flex-direction: column; gap: 10px; flex: 1; }
.image-card .image-caption { font-size: 14px; color: #2b332f; font-weight: 500; word-break: break-word; }
.image-card .image-actions { display: flex; justify-content: flex-end; gap: 14px; border-top: 1px solid #eef0ee; padding-top: 10px; margin-top: auto; }
.image-card .image-actions a { font-size: 13px; text-decoration: none; font-weight: 500; display: flex; align-items: center; gap: 6px; cursor: pointer; }
.image-card .image-actions a.edit-link { color: #0f5c33; }
.image-card .image-actions a.delete-link { color: #c0392b; }
.gallery-empty { grid-column: 1 / -1; text-align: center; padding: 30px 20px; color: #7a8c84; font-size: 14.5px; background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; }

/* ===== modal (Add / Edit Image, and View Images) ===== */
.rw-modal-overlay { position: fixed; inset: 0; background: rgba(11, 61, 36, 0.45); display: flex; align-items: center; justify-content: center; padding: 20px; z-index: 1000; box-sizing: border-box; }
.rw-modal-box { background: #fff; width: 100%; max-width: 620px; max-height: 90vh; border-radius: 14px; box-shadow: 0 20px 60px rgba(0,0,0,0.25); display: flex; flex-direction: column; overflow: hidden; }
.rw-modal-box.rw-modal-box-lg { max-width: 960px; height: 80vh; max-height: 80vh; }
.rw-modal-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #e2e6e4; flex-shrink: 0; }
.rw-modal-header h3 { margin: 0; font-size: 17px; color: #0f5c33; font-weight: 700; }
.rw-modal-close { text-decoration: none; color: #7a8c84; font-size: 22px; line-height: 1; padding: 4px 8px; border-radius: 6px; flex-shrink: 0; }
.rw-modal-close:hover { background: #f5f4ee; color: #c0392b; }
.rw-modal-body { padding: 20px; overflow-y: auto; flex: 1; min-height: 0; }

/* form styling reused inside the modal */
.gallery-form-table { width: 100%; border-collapse: collapse; }
.gallery-form-table th { text-align: left; padding: 10px 8px; color: #2b332f; vertical-align: top; width: 32%; font-weight: 600; font-size: 13.5px; }
.gallery-form-table td { padding: 10px 8px; }
.gallery-form-table input[type="text"],
.gallery-form-table input[type="file"],
.gallery-form-table select {
    border: 1px solid #e2e6e4; border-radius: 6px; padding: 8px 10px; font-size: 14px;
    width: 100%; max-width: 100%; box-sizing: border-box;
}
.gallery-form-table .sponsorList { font-size: 13px; color: #0f5c33; margin-left: 8px; }
.gallery-form-table input[type="submit"],
.gallery-form-table input[type="reset"] { background: #0f5c33; color: #fff; border: none; padding: 9px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; margin-right: 8px; margin-top: 6px; }
.gallery-form-table input[type="reset"] { background: #fff; color: #2b332f; border: 1px solid #e2e6e4; }

/* ===== responsive ===== */
@media (max-width: 900px) {
    #infoWrapper.col-lg-12 { flex-direction: column; margin: 16px auto; }
    #leftArea.col-lg-9 { flex: 1 1 100%; max-width: 100%; padding: 28px 24px; }
}
@media (max-width: 700px) {
    #leftArea.col-lg-9 { padding: 0 16px; }
    .gallery-header { flex-direction: column; align-items: stretch; }
    .gallery-header-actions { flex-direction: column; }
    .gallery-search-row { flex-direction: column; align-items: stretch; }
    .gallery-search-row input[type="text"] { width: 100%; }
    .dates-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; }
    .images-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; }
    .rw-modal-overlay { padding: 0; align-items: flex-end; }
    .rw-modal-box, .rw-modal-box.rw-modal-box-lg { max-width: 100%; width: 100%; max-height: 92vh; height: 92vh; border-radius: 16px 16px 0 0; }
}
@media (max-width: 520px) {
    .gallery-form-table, .gallery-form-table tbody, .gallery-form-table tr, .gallery-form-table th, .gallery-form-table td {
        display: block; width: 100% !important;
    }
    .gallery-form-table th { padding-bottom: 2px; }
    .gallery-form-table td { padding-top: 0; padding-bottom: 14px; }
    .dates-grid, .images-grid { grid-template-columns: 1fr; }
    .rw-modal-body { padding: 16px; }
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
    <?php if ($_SESSION['gallery'] == "Y") { ?>

            <div class="gallery-header">
                <div class="gallery-header-actions">
                    <a class="gallery-btn solid" href="admin/galleryManager.php?q=new-image"><i class="fas fa-plus"></i> Add New Image</a>
                    <a class="gallery-btn" href="admin/galleryManagerbulk.php?q=new-image"><i class="fas fa-layer-group"></i> Add Bulk Image</a>
                </div>
                <!--
                <div style="float:right;">
                    <a style="float:left;" href="admin/dashboard.php">Dashboard</a>
                    <a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
                </div>
                -->
            </div>

              <?php if ($q=="new-image") { ?>
              <div class="rw-modal-overlay" id="rwGalleryModal">
                <div class="rw-modal-box">
                  <div class="rw-modal-header">
                    <h3>Add New Image</h3>
                    <a href="admin/galleryManager.php" class="rw-modal-close" aria-label="Close">&times;</a>
                  </div>
                  <div class="rw-modal-body">
              <form name="dividendForm" method="post" action="admin/galleryManager.php" enctype="multipart/form-data">
                <table class="gallery-form-table">
                    <col width="32%"><col width="68%">
                    <tr>
                        <th>Date</th>
                        <td class="alignLeft">
                            <input type="text" name="date" id='image_date' />
                            <a class="sponsorList" style="cursor: pointer;">Get Sponsor List</a>
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
                  </div>
                </div>
              </div>
                <?php } ?>
                <?php if ($q == "edit-image") {?>
              <div class="rw-modal-overlay" id="rwGalleryModal">
                <div class="rw-modal-box">
                  <div class="rw-modal-header">
                    <h3>Edit Image</h3>
                    <a href="admin/galleryManager.php" class="rw-modal-close" aria-label="Close">&times;</a>
                  </div>
                  <div class="rw-modal-body">
                     <form name="dividendForm" method="post" action="admin/galleryManager.php?q=update-image&id=<?php  echo $imageDetails['id']; ?>" enctype="multipart/form-data">
                <table class="gallery-form-table">
                    <col width="32%"><col width="68%">                    
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
                  </div>
                </div>
              </div>
                <?php } ?>

                <?php if ($q=="new-image" || $q=="edit-image") { ?>
                <script type="text/javascript">
                    document.documentElement.style.overflow = 'hidden';
                    document.body.style.overflow = 'hidden';
                    document.addEventListener('keydown', function (e) {
                        if (e.key === 'Escape') { window.location.href = 'admin/galleryManager.php'; }
                    });
                    var rwGalleryOverlayEl = document.getElementById('rwGalleryModal');
                    if (rwGalleryOverlayEl) {
                        rwGalleryOverlayEl.addEventListener('click', function (e) {
                            if (e.target === rwGalleryOverlayEl) { window.location.href = 'admin/galleryManager.php'; }
                        });
                    }
                </script>
                <?php } ?>

              <div class="gallery-search-card">
                <div class="gallery-search-title"><i class="fas fa-magnifying-glass"></i> Jump to a Race Day</div>
                <div class="gallery-search-row">
                    <input type="text" id="search_image_date" placeholder="Select date" readonly />
                    <a class="sponsorList" id="searchSponsorTrigger">Get Sponsor List</a>
                    <span id="search_sponsors_list"></span>
                    <button type="button" id="viewImagesBtn" class="gallery-btn solid"><i class="fas fa-images"></i> View Images</button>
                </div>
              </div>

              <details class="gallery-browse-all" open>
                <summary><i class="fas fa-chevron-right"></i> Browse all race days (<?php echo count($allDates); ?>)</summary>
                <div class="gallery-browse-all-panel">
                  <div class="dates-grid">
                    <?php if (count($allDates) > 0) { ?>
                    <?php foreach ($allDates as $raceDate) { ?>
                        <div class="date-card">
                            <div class="date-card-top">
                                <span class="date-icon"><i class="fas fa-calendar-day"></i></span>
                                <div>
                                    <div class="date-value"><?php echo date("d-m-y",strtotime($raceDate['racedate'])); ?></div>
                                    <div class="date-sponsor"><?php echo $allSponsors[$raceDate['sponsor_id']]; ?></div>
                                </div>
                            </div>
                            <a class="view-link" href="admin/galleryManager.php?q=view-images&date=<?php echo $raceDate['racedate']; ?>&sponsorID=<?php echo $raceDate['sponsor_id']; ?>">
                                <i class="fas fa-images"></i> View Images
                            </a>
                        </div>
                    <?php } ?>
                    <?php } else { ?>
                        <div class="gallery-empty">No race days added yet.</div>
                    <?php } ?>
                  </div>
                </div>
              </details>

              <?php if ($q== "view-images") { ?>
                  <div class="rw-modal-overlay" id="rwGalleryViewModal">
                    <div class="rw-modal-box rw-modal-box-lg">
                      <div class="rw-modal-header">
                        <h3>Images for <?php echo date("d-M-Y",strtotime($date)); ?></h3>
                        <a href="admin/galleryManager.php" class="rw-modal-close" aria-label="Close">&times;</a>
                      </div>
                      <div class="rw-modal-body">
                        <div class="images-grid">
                            <?php if (count($raceDayImages) > 0) { ?>
                            <?php 
                                foreach ($raceDayImages as $raceDayImage) {
                                    $dirname = date("d-M-Y",strtotime($raceDayImage['racedate']));
                                    echo "<div class='image-card'>";
                                    echo "<div class='thumb-wrap'>";
                                    if ($sponsorID == 1) { 
                                        echo "<img src='".GALLERY_BASE."/$dirname/".$raceDayImage['filename']."' alt='' />";
                                    }
                                    if ($sponsorID > 1) { 
                                        echo "<img src='".SPONSOR_GALLERY_BASE."/$sponsorID/".$raceDayImage['filename']."' alt='' />";
                                    } 
                                    echo "</div>";
                                    echo "<div class='image-body'>";
                                    echo "<div class='image-caption'>{$raceDayImage['caption']}</div>";
                                    echo "<div class='image-actions'>
                                        <a class='edit-link' href='admin/galleryManager.php?q=edit-image&id={$raceDayImage['id']}'><i class=\"fas fa-edit\"></i> Edit</a>
                                        <a class='delete-link' onclick='return confirmDelete({$raceDayImage['id']},{$raceDayImage['sponsor_id']})'><i class=\"fas fa-trash-alt\"></i> Delete</a>
                                    </div>";
                                    echo "</div>";
                                    echo "</div>";
                                }
                            ?>
                            <?php } else { ?>
                                <div class="gallery-empty">No images uploaded for this race day yet.</div>
                            <?php } ?>
                        </div>
                      </div>
                    </div>
                  </div>
                  <script type="text/javascript">
                      document.documentElement.style.overflow = 'hidden';
                      document.body.style.overflow = 'hidden';
                      document.addEventListener('keydown', function (e) {
                          if (e.key === 'Escape') { window.location.href = 'admin/galleryManager.php'; }
                      });
                      var rwGalleryViewOverlayEl = document.getElementById('rwGalleryViewModal');
                      if (rwGalleryViewOverlayEl) {
                          rwGalleryViewOverlayEl.addEventListener('click', function (e) {
                              if (e.target === rwGalleryViewOverlayEl) { window.location.href = 'admin/galleryManager.php'; }
                          });
                      }
                  </script>
              <?php }?>
        <?php } ?>
             <?php                   
  $design->closeDiv();
  $design->writeLeftPanel();
  $design->closeDiv();
  $design->closeDiv();
  $design->endPage();
  $design->pageClose();
$design = NULL; // release object