<?php
  include_once('../bootstrap.php');
  require_once('../lib/dividends.class.php');
  require_once('../lib/pagination.class.php');
  require_once("../lib/users.class.php");
  require_once("../lib/userchecks.php");
  $q = getParameterString('q','',$db);
  $pageno = getParameterNumber('pageno',1);
  session_start();    
  if(isset($_COOKIE['uid'])){                    
    $uid = $_COOKIE['uid'];    
  } else {
    $uid = 0;
  }             
  $userObj = new Users($db);
 if (isAdminlogin()) {
    if ($_SESSION['dividends'] == "Y") { // check login
     $dividendObj = new Dividend($db);
      $allCentres = $dividendObj->getCentresList();
      $centresList = array();
      foreach($allCentres as $centre) {
          $centresList[$centre['id']] = $centre['centre'];
      }
      
      // all actions POST form submissions go here
      if (isset($_REQUEST['submit'])) {
          
          $date = getParameterString('date','',$db);
          $centreid = getParameterString('centreid','',$db);
                
          
          // save new dividend     
          if ($q == "add-dividend") {
              try {
                  if (!$_FILES['dividendFile']['error'])  { // error =0  
                    $filename = $_FILES['dividendFile']['name'];  
                    $filename = basename($filename,".HTM")."_".$centresList[$centreid]."_$date.HTM"; 
                    if (move_uploaded_file($_FILES['dividendFile']['tmp_name'],$base.DIVIDENDS_BASE."/".$filename)) {
                        $id = $dividendObj->insertDividend($date,$centreid,$filename); 
                    }
                  }
             } catch (Exception $err) {
                 $msg = $err->getMessage();
             }
          }
          /*if ($q == "update-dividend") {
             $dividendID=getParameterNumber('id',0);    
             try {
                $filename = $_FILES['dividendFile']['name'];    
                if ($filename !== "")  {
                    // remove old file
                    $dividendDetails = $dividendObj->getdividendById($dividendID);
                    unlink(DIVIDENDS_BASE."/".$dividendDetails['filename']);   
                    //upload new file
                    $filename = basename($filename,".HTM")."_".$centresList[$centreid]."_$date.HTM"; 
                    echo $filename;              
                    if (move_uploaded_file($_FILES['dividendFile']['tmp_name'],DIVIDENDS_BASE."/".$filename)) {
                          $rowsAffected = $dividendObj->updateDividend($dividendID,$centreid,$date,$filename);
                    }
                } else {
                        // no new file upload, just metadata update
                          $rowsAffected = $dividendObj->updateDividend($dividendID,$centreid,$date,$filename);
                }
             } catch (Exception $err) {
                 $msg = $err->getMessage();
             }      
          }         */
          
         

      }
      if ($q=="edit-dividend") {
         $dividendID=getParameterNumber('id',0);         
         try {
            $dividendDetails = $dividendObj->getDividendById($dividendID);        
         } catch (Exception $err) {
            $msg = $err->getMessage();        
         }
      }
      
      if ($q=="delete-dividend") {
           $dividendID=getParameterNumber('id',0);                
           $dividendDetails = $dividendObj->getdividendById($dividendID); 
           try {
               unlink($base.DIVIDENDS_BASE."/".$dividendDetails['filename']);   
               $dividendObj->deleteDividendByID($dividendID);
           } catch (Exception $err) {
               $msg = $err->getMessage();        
           }
      }
        // fetch all articles
      $alldividend = $dividendObj->getAlldividends();

      // ==== display-only pagination: getAlldividends() itself is untouched,
      // we just slice the already-returned array for the current page ====
      $DIVIDENDS_PER_PAGE = 10;
      $totalDividends = count($alldividend);
      $paging = new Pagination($pageno,$DIVIDENDS_PER_PAGE,$totalDividends);
      $alldividendPageWise = array_slice($alldividend,($pageno-1)*$DIVIDENDS_PER_PAGE,$DIVIDENDS_PER_PAGE);
   } else {
        $msg = "You do not have access to this page.";
      }  
} else {
    $secmsg = "Please login to access this page";
}
  
?>
<?php 
  $pageTitle ='Dividends Manager';        
  $design = new Design();
  $design->js='
  <script type="text/javascript" src="js/jquery.ui.core.min.js"></script>    
    <script type="text/javascript" src="js/jquery.ui.datepicker.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script type="text/javascript">
        function confirmDelete(dividendID) {
            if (confirm ("Are you sure ?")){
                location.href="admin/dividendsManager.php?q=delete-dividend&id="+dividendID;
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
    $('#dividend_date').datepicker({
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

.dividends-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.add-dividend-btn { display: inline-flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #1a7a45; color: #0f5c33; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; white-space: nowrap; }
.add-dividend-btn:hover { background: #e6f4ec; }

.section-title { font-size: 16px; font-weight: 700; color: #0f5c33; margin: 28px 0 14px; display: flex; align-items: center; gap: 8px; }
.section-hint { font-size: 12.5px; color: #7a8c84; margin: -8px 0 14px; display: flex; align-items: center; gap: 6px; }

/* ===== Add Dividend form — normal inline screen card ===== */
.dividend-form-wrap {
    background: #fff;
    border: 1px solid #e2e6e4;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
}
.dividend-form-wrap h3 { margin: 0 0 16px; font-size: 17px; color: #0f5c33; font-weight: 700; }
.dividend-form-table { width: 100%; border-collapse: collapse; }
.dividend-form-table th { text-align: left; padding: 10px 8px; color: #2b332f; vertical-align: top; width: 20%; font-weight: 600; font-size: 13.5px; }
.dividend-form-table td { padding: 10px 8px; }
.dividend-form-table input[type="text"],
.dividend-form-table input[type="file"],
.dividend-form-table select {
    border: 1px solid #e2e6e4; border-radius: 6px; padding: 8px 10px; font-size: 14px;
    width: 100%; max-width: 100%; box-sizing: border-box;
}
.dividend-form-table input[type="submit"],
.dividend-form-table input[type="reset"] { background: #0f5c33; color: #fff; border: none; padding: 9px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; margin-right: 8px; margin-top: 6px; }
.dividend-form-table input[type="reset"] { background: #fff; color: #2b332f; border: 1px solid #e2e6e4; }

/* ===== dividends table ===== */
.dividends-table-wrap { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.03); }
table.dividends-table { width: 100%; border-collapse: collapse; font-size: 14.5px; }
table.dividends-table th { background: #0b3d24; color: #fff; text-align: left; padding: 14px 20px; font-weight: 600; font-size: 13px; letter-spacing: 0.3px; }
table.dividends-table th.action-col { text-align: right; width: 120px; }
table.dividends-table td { padding: 14px 20px; border-bottom: 1px solid #eef0ee; color: #2b332f; }
table.dividends-table tr:last-child td { border-bottom: none; }
table.dividends-table tr:nth-child(even) td { background: #f7faf8; }
table.dividends-table tr:hover td { background: #e6f4ec; }
table.dividends-table td.action-col { text-align: right; white-space: nowrap; }
table.dividends-table td.action-col a { font-size: 13.5px; text-decoration: none; font-weight: 500; color: #c0392b; display: inline-flex; align-items: center; gap: 6px; }
.dividends-empty { padding: 30px 20px; text-align: center; color: #7a8c84; font-size: 14.5px; }
.rw-pagination-wrap { margin: 16px 0 0 !important; }

/* ===== responsive ===== */
@media (max-width: 900px) {
    #infoWrapper.col-lg-12 { flex-direction: column; margin: 16px auto; }
    #leftArea.col-lg-9 { flex: 1 1 100%; max-width: 100%; padding: 28px 24px; }
}
@media (max-width: 700px) {
    #leftArea.col-lg-9 { padding: 0 16px; }
    .dividends-header { flex-direction: column; align-items: stretch; }
    table.dividends-table th, table.dividends-table td { padding: 10px 12px; font-size: 13.5px; }
}
@media (max-width: 520px) {
    .dividend-form-table, .dividend-form-table tbody, .dividend-form-table tr, .dividend-form-table th, .dividend-form-table td {
        display: block; width: 100% !important;
    }
    .dividend-form-table th { padding-bottom: 2px; }
    .dividend-form-table td { padding-top: 0; padding-bottom: 14px; }
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
    <?php if ($_SESSION['dividends'] == "Y") { ?>

            <div class="dividends-header">
                <a class="add-dividend-btn" href="admin/dividendsManager.php?q=new-dividend"><i class="fas fa-plus"></i> Add New Dividend</a>
                <!--
                <div style="float:right;">
                    <a style="float:left;" href="admin/dashboard.php">Dashboard</a>
                    <a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
                </div>
                -->
            </div>

              <?php if ($q=="new-dividend" || $q=="edit-dividend") { ?>
              <div class="dividend-form-wrap">
                <h3><?php echo ($q=="new-dividend") ? "Add New Dividend" : "Edit Dividend"; ?></h3>
              <form name="dividendForm" method="post" action="admin/dividendsManager.php" enctype="multipart/form-data">
                <table class="dividend-form-table">
                    <col width="20%"><col width="80%">
                    <tr>
                        <th>Date</th>
                        <?php 
                          $date = '';
                            if ($q=="edit-dividend") {
                               // echo $dividendDetails['dividend_date'];    
                                $date = date("Y-m-d",strtotime($dividendDetails['div_date']));
                            }
                        ?>
                        <td class="alignLeft"><input type="text" name="date" id='dividend_date' value="<?php echo $date; ?>" /></td>
                    </tr>
                    <tr>
                        <th>Centre</th>
                        <td class="alignLeft">                            
                            <select name='centreid'>
                                <?php 
                                    $selected = '';
                                    if (isset($dividendDetails['centreid']))
                                        $selected = $dividendDetails['div_date'];
                                    echo drawOptionsFromHashtable($centresList,$selected); 
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>         
                        <th>Upload File</th>
                        <td class="alignLeft"><input type="file" name="dividendFile" /></td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <input type="submit" name="submit" value="Save" />
                            <input type="reset" name="reset" value="Clear" />
                          
                      <?php if ($q=="new-dividend") { ?>
                                <input type="hidden" name="q" value="add-dividend" />
                            <?php } elseif ($q == "edit-dividend") { ?>
                                    <input type="hidden" name="q" value="update-dividend" />
                                    <input type="hidden" name="id" value="<?php echo $dividendID; ?>" />
                            <?php  }   ?>  
                        </td>
                    </tr>
                </table>
                </form>
              </div>
                <?php } ?>

              <div class="section-title"><i class="fas fa-chart-line"></i> All Dividends</div>
              <div class="section-hint"><i class="fas fa-circle-info"></i> To edit a dividend entry, please delete the old one and re-add.</div>

              <div class="dividends-table-wrap">
              <table class="dividends-table">
                <tr>
                    <th>DATE</th>
                    <th>CENTRE</th>                                       
                    <th class="action-col">ACTIONS</th>                    
                </tr>
                <?php if (count($alldividendPageWise) > 0) { ?>
                <?php foreach ($alldividendPageWise as $dividendInfo) { ?>
                    <tr>                        
                        <td><?php echo date("d-m-y",strtotime($dividendInfo['div_date'])); ?></td>
                        <td><?php echo $centresList[$dividendInfo['centreid']]; ?></td>
                        <td class="action-col">
                          <!--  <a href="/admin/dividendsManager.php?id=<?php echo $dividendInfo['id'];?>&q=edit-dividend">Edit</a>         -->
                            <a href="#" onclick="javascript: confirmDelete(<?php echo $dividendInfo['id'];?>);"><i class="fas fa-trash-alt"></i> Delete</a>
                        </td>
                    </tr>
                <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="3" class="dividends-empty">No dividends added yet.</td>
                    </tr>
                <?php } ?>
              </table>
              </div>
              <?php if ($totalDividends > 0) { $paging->writePagination(); } ?>
  <?php } ?>
             <?php                   
  $design->closeDiv();
  $design->writeLeftPanel();
  $design->closeDiv();
  $design->closeDiv();
  $design->endPage();
    $design->pageClose();
$design = NULL; // release object