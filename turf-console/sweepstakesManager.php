<?php

  include_once('../bootstrap.php');

  require_once('../lib/sweepstake.class.php');
  require_once("../lib/users.class.php");
  require_once("../lib/userchecks.php");
  require_once("../lib/permissions.php");
  require_once __DIR__ . '/../vendor/autoload.php';
  require_once __DIR__ . '/../rwitc_website_api/config/config.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

  if (session_status() === PHP_SESSION_NONE) {
      session_start();
  }

  // Login check
  if (!isAdminlogin()) {
      header("Location: index.php");
      exit;
  }

  // Sweepstakes module permission check
  requireModuleAccess('sweepstakes');

  

  $q = getParameterString('q','',$db);

  

  $sweepstakeObj = new Sweepstake($db);

  $msg = '';

$sweepstakeDetails = array();

  

  // all actions POST form submissions go here

  if (isset($_REQUEST['submit'])) {

      

      $date = getParameterString('date','',$db);

      $title = getParameterString('title','',$db);

      $comment = getParameterString('comment','',$db);

      

      

      // save new sweepstake     

      if ($q == "add-sweepstake") {

          try {

              if (!$_FILES['sweepstakeFile']['error'])  { // error =0  

                $filename = $_FILES['sweepstakeFile']['name'];                 

            //     if (move_uploaded_file($_FILES['sweepstakeFile']['tmp_name'],"../".SWEEPSTAKES_BASE."/".$filename)) {

            //         $id = $sweepstakeObj->insertSweepstake($date,$title,$comment,$filename); 

            //     }

            //   }


            //   // Upload to S3 

             $s3_url = '';
          try {
              $s3Client = new S3Client([
                  'version'     => 'latest',
                  'region'      => AWS_REGION,
                  'credentials' => [
                      'key'    => AWS_ACCESS_KEY_ID,
                      'secret' => AWS_SECRET_ACCESS_KEY,
                  ],
              ]);

              $result = $s3Client->putObject([
                  'Bucket'      => AWS_BUCKET,
                  'Key'         => 'staticpages/sweepstake/' . $filename,
                  'SourceFile'  => $_FILES['sweepstakeFile']['tmp_name'],
                  // 'ACL'         => 'public-read',
                  'ContentType' => 'text/html',
              ]);

              $s3_url = $result['ObjectURL'];
          } catch (AwsException $e) {
              $s3_url = '';
          }

          if ($s3_url != '') {
              $id = $sweepstakeObj->insertSweepstake($date,$title,$comment,$s3_url);
              $_SESSION['sweepstake_msg'] = 'Sweepstake Uploaded Successfully!';
              header("Location: sweepstakesManager.php");
              exit;
          }
        }


         } catch (Exception $err) {

             $msg = $err->getMessage();

         }

      }

    //   if ($q == "update-sweepstake") {

    //      $sweeptstakeID=getParameterNumber('id',0);    

    //      try {

    //         $filename = $_FILES['sweepstakeFile']['name'];    

    //         if ($filename !== "")  {

    //             // remove old file

    //             $sweepstakeDetails = $sweepstakeObj->getSweepstakeById($sweeptstakeID);

    //             unlink("../".SWEEPSTAKES_BASE."/".$sweepstakeDetails['filename']);   

    //             //upload new file

    //             if (move_uploaded_file($_FILES['sweepstakeFile']['tmp_name'],"../".SWEEPSTAKES_BASE."/".$filename)) {

    //                   $rowsAffected = $sweepstakeObj->updateSweepstake($sweeptstakeID,$title,$date,$comment,$filename);

    //             }

    //         } else {

    //                 // no new file upload, just metadata update

    //                   $rowsAffected = $sweepstakeObj->updateSweepstake($sweeptstakeID,$title,$date,$comment,$filename);

    //         }

    //      } catch (Exception $err) {

    //          $msg = $err->getMessage();

    //      }      

    //   }


    if ($q == "update-sweepstake") {
   $sweeptstakeID=getParameterNumber('id',0);    
   try {
      $filename = $_FILES['sweepstakeFile']['name'];    
      if ($filename !== "")  {
          // old file: just leave it in S3/local, no unlink needed
          $sweepstakeDetails = $sweepstakeObj->getSweepstakeById($sweeptstakeID);
          // unlink("../".SWEEPSTAKES_BASE."/".$sweepstakeDetails['filename']);

          $s3_url = '';
          try {
              $s3Client = new S3Client([
                  'version'     => 'latest',
                  'region'      => AWS_REGION,
                  'credentials' => [
                      'key'    => AWS_ACCESS_KEY_ID,
                      'secret' => AWS_SECRET_ACCESS_KEY,
                  ],
              ]);

              $result = $s3Client->putObject([
                  'Bucket'      => AWS_BUCKET,
                  'Key'         => 'staticpages/sweepstake/' . $filename,
                  'SourceFile'  => $_FILES['sweepstakeFile']['tmp_name'],
                  // 'ACL'         => 'public-read',
                  'ContentType' => 'text/html',
              ]);

              $s3_url = $result['ObjectURL'];
          } catch (AwsException $e) {
              $s3_url = '';
          }

          if ($s3_url != '') {
              $rowsAffected = $sweepstakeObj->updateSweepstake($sweeptstakeID,$title,$date,$comment,$s3_url);
          }
      } else {
              // no new file upload, just metadata update
                $rowsAffected = $sweepstakeObj->updateSweepstake($sweeptstakeID,$title,$date,$comment,$filename);
      }
      $_SESSION['sweepstake_msg'] = 'Sweepstake Updated Successfully!';
      header("Location: sweepstakesManager.php");
      exit;
   } catch (Exception $err) {
       $msg = $err->getMessage();
   }      
}
      

     



  }

  if ($q=="edit-sweepstake") {

     $sweeptstakeID=getParameterNumber('id',0);         

     try {

        $sweepstakeDetails = $sweepstakeObj->getSweepstakeById($sweeptstakeID);        

     } catch (Exception $err) {

        $msg = $err->getMessage();        

     }

  }

  

//   if ($q=="delete-sweepstake") {

//        $sweeptstakeID=getParameterNumber('id',0);                

//        $sweepstakeDetails = $sweepstakeObj->getSweepstakeById($sweeptstakeID); 

//        try {

//            unlink("../".SWEEPSTAKES_BASE."/".$sweepstakeDetails['filename']);   

//            $sweepstakeObj->deleteSweepstakeByID($sweeptstakeID);

//        } catch (Exception $err) {

//            $msg = $err->getMessage();        

//        }

//   }


if ($q=="delete-sweepstake") {
     $sweeptstakeID=getParameterNumber('id',0);                
     $sweepstakeDetails = $sweepstakeObj->getSweepstakeById($sweeptstakeID); 
     try {
         // unlink("../".SWEEPSTAKES_BASE."/".$sweepstakeDetails['filename']);   
         $sweepstakeObj->deleteSweepstakeByID($sweeptstakeID);
         $_SESSION['sweepstake_msg'] = 'Sweepstake Deleted Successfully!';
         header("Location: sweepstakesManager.php");
         exit;
     } catch (Exception $err) {
         $msg = $err->getMessage();        
     }
}

    // fetch all articles

  $allSweepstake = $sweepstakeObj->getAllSweepstakes();

?>

<?php 

  $pageTitle ='Sweekstakes Manager';        

  $design = new Design();

  $design->js='

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <script type="text/javascript" src="js/jquery.ui.core.min.js"></script>   

    <script type="text/javascript" src="js/jquery.ui.datepicker.min.js"></script>

    <script type="text/javascript">

        function confirmDelete(sweepstakeID) {

            if (confirm ("Are you sure ?")){

                location.href="turf-console/sweepstakesManager.php?q=delete-sweepstake&id="+sweepstakeID;

            }

        }

    </script>

  ';

  $design->css ='

  <link type="text/css" href="css/jquery.ui.all.css" rel="stylesheet" />    

  ';

  $design->jqueryJs = ''; 
  $design->startPage("$pageTitle");
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea","col-lg-9");
  ?>

<style type="text/css">
    #infoWrapper.col-lg-12 { display: flex; flex-direction: row-reverse; align-items: flex-start; max-width: 1500px; margin: 30px auto; float: none; }
    #leftArea.col-lg-9 { flex: 1 1 auto; min-width: 0; max-width: none; margin: 0; padding: 0 30px; box-sizing: border-box; float: none; width: auto; display: block; }
    html, body { scrollbar-width: none; -ms-overflow-style: none; }
    html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }
    #infoWrapper.col-lg-12 #rightArea.col-lg-3 { padding-top: 0 !important; }

    .message { position: relative; background: #e6f4ec; border: 1px solid #b7ddc5; color: #0f5c33; padding: 12px 16px; border-radius: 8px; margin-bottom: 15px; font-size: 14.5px; font-weight: 500; }
    .message.message-error { background: #fbeae8; border-color: #f1c9c3; color: #c0392b; }

    .reports-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
    .add-report-btn { display: inline-flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #1a7a45; color: #0f5c33; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; white-space: nowrap; }
    .add-report-btn:hover { background: #e6f4ec; }

    .section-title { font-size: 16px; font-weight: 700; color: #0f5c33; margin: 28px 0 14px; display: flex; align-items: center; gap: 8px; }
    .section-hint { font-size: 12.5px; color: #7a8c84; margin: -8px 0 14px; display: flex; align-items: center; gap: 6px; }

    .report-form-wrap { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; padding: 20px; margin-bottom: 24px; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03); }
    .report-form-wrap h3 { margin: 0 0 16px; font-size: 17px; color: #0f5c33; font-weight: 700; }
    .report-form-table { width: 100%; border-collapse: collapse; }
    .report-form-table th { text-align: left; padding: 10px 8px; color: #2b332f; vertical-align: top; width: 20%; font-weight: 600; font-size: 13.5px; }
    .report-form-table td { padding: 10px 8px; }
    .report-form-table input[type="text"],
    .report-form-table input[type="date"],
    .report-form-table input[type="file"],
    .report-form-table textarea { border: 1px solid #e2e6e4; border-radius: 6px; padding: 8px 10px; font-size: 14px; width: 100%; max-width: 100%; box-sizing: border-box; font-family: inherit;}
    .report-form-table input[type="text"],
    .report-form-table input[type="date"] { height: 40px; margin: 0; display: block;}
    .report-form-table th { vertical-align: middle; }
    .report-form-table tr:nth-child(3) th { vertical-align: top; padding-top: 18px; }
    .report-form-table input[type="submit"],
    .report-form-table input[type="reset"] { background: #0f5c33; color: #fff; border: none; padding: 9px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; margin-right: 8px; margin-top: 6px; }
    .report-form-table input[type="reset"] { background: #fff; color: #2b332f; border: 1px solid #e2e6e4; }

    .reports-table-wrap { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03); }
    table.reports-table { width: 100%; border-collapse: collapse; font-size: 14.5px; }
    table.reports-table th { background: #0b3d24; color: #fff; text-align: left; padding: 14px 20px; font-weight: 600; font-size: 13px; letter-spacing: 0.3px; }
    table.reports-table th.action-col { text-align: right; width: 160px; }
    table.reports-table td { padding: 14px 20px; border-bottom: 1px solid #eef0ee; color: #2b332f; }
    table.reports-table tr:last-child td { border-bottom: none; }
    table.reports-table tr:nth-child(even) td { background: #f7faf8; }
    table.reports-table tr:hover td { background: #e6f4ec; }
    table.reports-table td.action-col { text-align: right; white-space: nowrap; }
    table.reports-table td.action-col a { font-size: 13.5px; text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; gap: 6px; margin-left: 14px; }
    table.reports-table td.action-col a.edit-link { color: #0f5c33; }
    table.reports-table td.action-col a.delete-link { color: #c0392b; }
    .reports-empty { padding: 30px 20px; text-align: center; color: #7a8c84; font-size: 14.5px; }

    @media (max-width: 900px) {
        #infoWrapper.col-lg-12 { flex-direction: column; margin: 16px auto; }
        #leftArea.col-lg-9 { flex: 1 1 100%; max-width: 100%; padding: 28px 24px; }
    }
    @media (max-width: 700px) {
        #leftArea.col-lg-9 { padding: 0 16px; }
        .reports-header { flex-direction: column; align-items: stretch; }
        table.reports-table th, table.reports-table td { padding: 10px 12px; font-size: 13.5px; }
    }
    @media (max-width: 520px) {
        .report-form-table, .report-form-table tbody, .report-form-table tr, .report-form-table th, .report-form-table td { display: block; width: 100% !important; }
        .report-form-table th { padding-bottom: 2px; }
        .report-form-table td { padding-top: 0; padding-bottom: 14px; }
    }
</style>

<!-- <?php if (!empty($msg)) { ?>
    <div class="message message-error"><?php echo $msg; ?></div>
<?php } ?> -->


<?php 
if (isset($_SESSION['sweepstake_msg'])) {
    $msg = $_SESSION['sweepstake_msg'];
    unset($_SESSION['sweepstake_msg']);
}
?>
<?php if (!empty($msg)) { ?>
    <div class="message" id="successMsg"><?php echo $msg; ?></div>
    <script>
        setTimeout(function() {
            var el = document.getElementById('successMsg');
            if (el) el.style.display = 'none';
        }, 2500);
    </script>
<?php } ?>


<div class="reports-header">
    <a class="add-report-btn" href="turf-console/sweepstakesManager.php?q=new-sweepstake"><i class="fas fa-plus"></i> Add New Sweepstake</a>
</div>

<?php if ($q=="new-sweepstake" || $q=="edit-sweepstake") { ?>
    <div class="report-form-wrap">
        <h3><?php echo ($q=="edit-sweepstake") ? 'Edit Sweepstake' : 'Add New Sweepstake'; ?></h3>
        <form name="sweepstakeForm" method="post" action="turf-console/sweepstakesManager.php" enctype="multipart/form-data">
            <table class="report-form-table">
                <col width="20%"><col width="80%">
                <tr>
                    <th>Date</th>
                    <?php
                      $date = '';
                      if ($q=="edit-sweepstake") {
                          $date = date("Y-m-d",strtotime($sweepstakeDetails['sweepstake_date']));
                      }
                    ?>
                    <td class="alignLeft"> <input type="date" name="date" id="sweepstake_date" value="<?php echo htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?>" required/></td>
                </tr>
                <tr>
                    <th>Title</th>
                <td class="alignLeft"><input type="text" name="title" id="sweepstake_title" value="<?php echo isset($sweepstakeDetails['title']) ? htmlspecialchars($sweepstakeDetails['title'], ENT_QUOTES) : ''; ?>" /></td>

                </tr>
                <tr>
                    <th>Comments</th>
                    <td class="alignLeft"><textarea name="comment" id="comment"><?php echo isset($sweepstakeDetails['comments']) ? htmlspecialchars($sweepstakeDetails['comments']) : ''; ?></textarea></td>
                </tr>
                <tr>
                    <th>Upload File</th>
                    <td class="alignLeft"><input type="file" name="sweepstakeFile" /></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="submit" name="submit" value="Save" />
                        <input type="reset" name="reset" value="Clear" />

                        <?php if ($q=="new-sweepstake") { ?>
                            <input type="hidden" name="q" value="add-sweepstake" />
                        <?php } elseif ($q == "edit-sweepstake") { ?>
                            <input type="hidden" name="q" value="update-sweepstake" />
                            <input type="hidden" name="id" value="<?php echo $sweeptstakeID; ?>" />
                        <?php } ?>
                    </td>
                </tr>
            </table>
        </form>
    </div>
<?php } ?>

<div class="section-title"><i class="fas fa-trophy"></i> All Sweepstakes</div>
<div class="section-hint"><i class="fas fa-circle-info"></i> To change a sweepstake, click Edit. Uploading a new file replaces the old one.</div>

<div class="reports-table-wrap">
    <table class="reports-table">
        <tr>
            <th>DATE</th>
            <th>TITLE</th>
            <th>COMMENTS</th>
            <th class="action-col">ACTIONS</th>
        </tr>
        <?php if (!empty($allSweepstake)) { ?>
            <?php foreach ($allSweepstake as $sweepstakeInfo) { ?>
                <tr>
                    <td><?php echo date("d-m-y",strtotime($sweepstakeInfo['sweepstake_date'])); ?></td>
                    <td><?php echo $sweepstakeInfo['title']; ?></td>
                    <td><?php echo $sweepstakeInfo['comments']; ?></td>
                    <td class="action-col">
                        <a class="edit-link" href="turf-console/sweepstakesManager.php?id=<?php echo $sweepstakeInfo['id'];?>&q=edit-sweepstake"><i class="fas fa-pen"></i> Edit</a>
                        <a class="delete-link" href="#" onclick="javascript: confirmDelete(<?php echo $sweepstakeInfo['id'];?>); return false;"><i class="fas fa-trash-alt"></i> Delete</a>
                    </td>
                </tr>
            <?php } ?>
        <?php } else { ?>
            <tr>
                <td colspan="4" class="reports-empty">No sweepstakes added yet.</td>
            </tr>
        <?php } ?>
    </table>
</div>

             <?php                   

  $design->closeDiv();

  $design->writeLeftPanel();  

  $design->closeDiv();

  $design->closeDiv();

    $design->endPage();

$design = NULL; // release object