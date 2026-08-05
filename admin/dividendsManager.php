<?php
  include_once('../bootstrap.php');
  require_once('../lib/dividends.class.php');
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
    <?php if ($_SESSION['dividends'] == "Y") { ?>
            <div class="submenu">  
              <a href="admin/dividendsManager.php?q=new-dividend">Add New dividend</a>
                  <div style="float:right;">
                    <a style="float:left;" href="admin/dashboard.php">Dashboard</a>
                    <a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
                  </div>
            </div>
              
              <br />   
              
              <?php if ($q=="new-dividend" || $q=="edit-dividend") { ?>              
              <form name="dividendForm" method="post" action="admin/dividendsManager.php" enctype="multipart/form-data">
                <table class="contentTable">
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
                <?php } ?>
              <br />
              <br />
              <table class="contentTable" style="margin-top:0px;">
                <tr>
                    <th class="thwhite alignLeft" colspan="3">To edit a dividend entry, please delete the old one and Re-add</th>
                </tr>
                <tr>
                    <th>DATE</th>
                    <th>CENTRE</th>                                       
                    <th>ACTIONS</th>                    
                </tr>
                <?php foreach ($alldividend as $dividendInfo) { ?>
                    <tr>                        
                        <td><?php echo date("d-m-y",strtotime($dividendInfo['div_date'])); ?></td>
                        <td><?php echo $centresList[$dividendInfo['centreid']]; ?></td>
                        <td>
                          <!--  <a href="/admin/dividendsManager.php?id=<?php echo $dividendInfo['id'];?>&q=edit-dividend">Edit</a>         -->
                            <a href="#" onclick="javascript: confirmDelete(<?php echo $dividendInfo['id'];?>);">Delete</a>
                        </td>
                    </tr>
                <?php } ?>
              </table>
  <?php } ?>
              <br />              
             <?php                   
  $design->closeDiv();
  //$design->rightArea();  
  //$design->closeDiv();
  $design->closeDiv();
    $design->pageClose();
$design = NULL; // release object