<?php

include_once('../bootstrap.php');

require_once('../lib/articles.class.php');

include_once('../lib/pagination.class.php');

require_once("../lib/users.class.php");

require_once("../lib/userchecks.php");

require_once("../lib/function_ticker_manager.php");

  

$q = getParameterString('q','',$db);

session_start();

if(isset($_COOKIE['uid'])){                    

  $uid = $_COOKIE['uid'];    

} else {

  $uid = 0;

}        

$userObj = new Users($db);  





$msg = $secmsg = "";

$pageno = getParameterNumber('pageno',1);

$articles = new Articles($db);

if (isAdminlogin()) {

    if ($_SESSION['tickerManager'] == "Y") { // check login

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

              $body = getParameterString('message','',$db);

              $sort_order = getParameterString('sort_order','',$db);

              $published = getParameterString('publish','N',$db);

              // handle checkbox state

              if (strtolower($published)== "on") {

                 $published="Y";

              }     

              // save new ticker

              if ($q == "add-ticker") {

                  try {

                    $tickerID = $articles->insertTicker($body,$published, $sort_order); 

                    $msg = "New Ticker added";

                 } catch (Exception $err) {

                     echo $err->getMessage();

                 }

              }

          

              //update new ticker 

              if ($q == "update-ticker") {

                 $tickerID=getParameterNumber('id',0);    

                 try {

                    $rowsAffected = $articles->updateTicker($tickerID,$body,$published,$sort_order);

                    $msg = "Ticker Updated";

                 } catch (Exception $err) {

                     echo $err->getMessage();

                 }      

              }

          }



          if ($q=="edit-ticker") {

             $tickerID=getParameterNumber('id',0);         

             try {

                $tickerDetails = $articles->getTickerByID($tickerID);        

             } catch (Exception $err) {

                $msg = $err->getMessage();

                echo $msg;

             }

          }

          if ($q == "delete-ticker") {

             $tickerID=getParameterNumber('id',0);         

             try {

                $articles->deleteTicker($tickerID);                

                $msg = "Ticker Deleted";

                // clear action

                $q="";

             } catch (Exception $err) {

                $msg = $err->getMessage();

                echo $msg;

             }

          }

          if(!isset($tickerDetails['body'])){

            $tickerDetails['body'] = '';

          }

          if(!isset($tickerDetails['sort_order'])){

            $tickerDetails['sort_order'] = '';

          }

          if(!isset($tickerDetails['published'])){

            $tickerDetails['published'] = 'N';

          }

          

          $totalTickers = $articles->getAllTickersCount();

          // create a pagination object

          //$paging = new Pagination($pageno,TICKERS_PER_PAGE,$totalTickers);  

          //$allReports = $rrObj->getRaceRecordsPageWise($pageno,REPORTS_PER_PAGE);

          

          // fetch all articles

          //$allArticles = $articles->getAllArticles();

          $allTickers = $articles->getTickersPageWise($pageno,TICKERS_PER_PAGE);

      } else {

        $msg = "You do not have access to this page.";

      }  

} else {

    $secmsg = "Please login to access this page";

}

$pageTitle ='Ticker Manager';        

// create a template object

$design = new Design();  



$design->js='

<script type="text/javascript" src="lib/ckeditor/ckeditor.js"></script>

<script type="text/javascript">

    function confirmDelete(tickerID) {

        if (confirm ("Are you sure ?")){

            location.href="admin/tickerManager.php?q=delete-ticker&id="+tickerID;

        }

    }

</script>

';

$design->css ='

<link type="text/css" href="css/jquery.ui.all.css" rel="stylesheet" />

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style type="text/css">

  ul.setPaginate li.setPage{

  padding:15px 10px;

  font-size:14px;

  }



  ul.setPaginate{

  margin:0px;

  padding:0px;

  height:100%;

  overflow:hidden;

  font: 14px "Inter","Segoe UI",Arial,sans-serif;

  list-style-type:none; 

  }  



  ul.setPaginate li.dot{padding: 3px 0;}



  ul.setPaginate li{

  float:left;

  margin:0px;

  padding:0px;

  margin-left:5px;

  }



  ul.setPaginate li a

  {

  background: #ffffff;

  border: 1px solid #e2e6e4;

  border-radius: 8px;

  color: #2b332f;

  display: inline-block;

  font: 14px/25px "Inter","Segoe UI",Arial,sans-serif;

  font-weight: 500;

  margin: 5px 3px 0 0;

  padding: 0 5px;

  text-align: center;

  text-decoration: none;

  } 



  ul.setPaginate li a:hover,

  ul.setPaginate li a.current_page

  {

  background: #0f5c33;

  border: 1px solid #0f5c33;

  color: #ffffff;

  text-decoration: none;

  }



  ul.setPaginate li a{

  display:block;

  text-decoration:none;

  padding:5px 12px;

  text-decoration: none;

  }

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
  .message { background: #fff3cd; border: 1px solid #ffe08a; padding: 12px 16px; border-radius: 8px; margin-bottom: 15px; font-size: 15px; }

  .ticker-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
  .add-ticker-btn { display: inline-flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #1a7a45; color: #0f5c33; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; }
  .add-ticker-btn:hover { background: #e6f4ec; }
  .header-links { display: flex; align-items: center; gap: 16px; }
  .header-links a { color: #0f5c33; text-decoration: none; font-weight: 600; font-size: 14px; }
  .header-links a:hover { text-decoration: underline; }

  .ticker-form-wrap { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); max-width: 700px; }
  .ticker-form-wrap .form-row { margin-bottom: 20px; }
  .ticker-form-wrap label.form-label { display: block; font-size: 14px; font-weight: 600; color: #2b332f; margin-bottom: 8px; }
  .ticker-form-wrap textarea, .ticker-form-wrap input[type="text"] {
    width: 100%; border: 1px solid #e2e6e4; border-radius: 8px; padding: 10px 12px; font-size: 14px; color: #2b332f; box-sizing: border-box; font-family: inherit;
  }
  .ticker-form-wrap textarea:focus, .ticker-form-wrap input[type="text"]:focus { outline: none; border-color: #1a7a45; }
  .ticker-form-wrap .checkbox-row { display: flex; align-items: center; gap: 8px; }
  .ticker-form-wrap input[type="checkbox"] { width: 17px; height: 17px; accent-color: #0f5c33; cursor: pointer; }
  .ticker-form-wrap .form-actions { display: flex; gap: 10px; padding-top: 6px; }
  .ticker-form-wrap input[type="submit"], .ticker-form-wrap input[type="reset"] { background: #0f5c33; color: #fff; border: none; padding: 10px 22px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; }
  .ticker-form-wrap input[type="reset"] { background: #fff; color: #2b332f; border: 1px solid #e2e6e4; }
  .ticker-form-wrap input[type="submit"]:hover { background: #0c4a29; }
  .ticker-form-wrap input[type="reset"]:hover { background: #f5f4ee; }

  .tickers-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 10px; }
  .ticker-card { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; padding: 18px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); display: flex; flex-direction: column; }
  .ticker-card-body { font-size: 14px; color: #2b332f; line-height: 1.5; margin-bottom: 14px; }
  .ticker-tags { display: flex; gap: 10px; margin-bottom: 14px; flex-wrap: wrap; }
  .ticker-tags .tag { font-size: 12.5px; color: #2b332f; background: #f5f4ee; border: 1px solid #e2e6e4; padding: 6px 10px; border-radius: 6px; display: flex; align-items: center; gap: 6px; }
  .ticker-tags .tag b { padding: 1px 8px; border-radius: 4px; font-size: 12px; font-weight: 700; }
  .ticker-tags .tag b.yes { background: #0f5c33; color: #fff; }
  .ticker-tags .tag b.no { background: #c0392b; color: #fff; }
  .ticker-tags .tag b.order { background: #0f5c33; color: #fff; }
  .ticker-meta { font-size: 12.5px; color: #7a8c84; display: flex; align-items: center; gap: 6px; margin-bottom: 14px; }
  .ticker-actions { display: flex; justify-content: flex-end; gap: 16px; border-top: 1px solid #eef0ee; padding-top: 12px; margin-top: auto; }
  .ticker-actions a { font-size: 13.5px; text-decoration: none; display: flex; align-items: center; gap: 6px; font-weight: 500; }
  .ticker-actions a:nth-child(1) { color: #0f5c33; }
  .ticker-actions a:nth-child(2) { color: #c0392b; }
  .tickers-empty { padding: 20px; text-align: center; color: #7a8c84; font-size: 14px; background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; }

  .pagination-wrap { margin: 18px 0; }
  html, body { scrollbar-width: none; -ms-overflow-style: none; }
html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }


  @media (max-width: 700px) {
    .tickers-grid { grid-template-columns: 1fr; }
    #leftArea.col-lg-9 { padding: 0 16px; }
    .ticker-header { flex-direction: column; align-items: flex-start; }
    .ticker-form-wrap { padding: 18px; }
  }

  </style>

';

$design->jqueryJs = ""; 

$design->startPage("$pageTitle");  

$design->writeLogoTickerMenu();

$design->openDiv("contentWrapper");

$design->openDiv("infoWrapper","col-lg-12");

$design->openDiv("leftArea",'col-lg-9');

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

    <?php if ($_SESSION['tickerManager'] == "Y") { ?>

        <div class="ticker-header">

          <a class="add-ticker-btn" href="admin/tickerManager.php?q=new-ticker"><i class="fas fa-plus"></i> Add New Ticker</a>

          <div class="header-links">

                <!-- <a href="admin/dashboard.php">Dashboard</a>

                <a href="admin/adminlogin.php?q=logout">Logout</a> -->

           </div>

        </div>

          

          <?php if ($q=="new-ticker" || $q=="edit-ticker") { ?>              

           <div class="ticker-form-wrap">

           <form name="tickerForm" method="post" action="admin/tickerManager.php">

                <div class="form-row">

                    <label class="form-label" for="message">Message</label>

                    <textarea name="message" id="message" rows="5" cols="50"><?php echo $tickerDetails['body'] ?></textarea>

                </div>

                <div class="form-row checkbox-row">

                        <?php 

                        $checked = "checked=\"checked\"";

                        if ($tickerDetails['published'] == "N") {

                            $checked ="";        

                        } 

                        ?>

                        <input type="checkbox" name="publish" id='publish' <?php echo $checked; ?> />

                    <label class="form-label" for="publish" style="margin-bottom:0;">Publish</label>

                </div>

                <div class="form-row">

                  <label class="form-label" for="sort_order">Sort Order</label>

                    <input type="text" name="sort_order" id="sort_order" value="<?php echo $tickerDetails['sort_order']; ?>" />

                </div>

                <div class="form-actions">

                        <input type="submit" name="submit" value="Save" />

                        <input type="reset" name="reset" value="Clear" onclick="location.href='admin/tickerManager.php'" />

                        <?php if ($q=="new-ticker") { ?>

                            <input type="hidden" name="q" value="add-ticker" />

                        <?php } elseif ($q == "edit-ticker") { ?>

                                <input type="hidden" name="q" value="update-ticker" />

                                <input type="hidden" name="id" value="<?php echo $tickerID; ?>" />

                        <?php  }   ?>

                </div>

            </form>

            </div>

           <script type="text/javascript">

             //<![CDATA[

            CKEDITOR.replace( 'message111',

                {

                    fullPage : true,

                    filebrowserBrowseUrl : 'lib/ckfinder/ckfinder.html',

                    filebrowserImageBrowseUrl : 'lib/ckfinder/ckfinder.html?type=Images',

                    filebrowserFlashBrowseUrl : 'lib/ckfinder/ckfinder.html?type=Flash',

                    filebrowserUploadUrl : 'imageUpload.php'

                });

            //]]>

            </script>

          <?php } ?>

          <div class="tickers-grid">

            <?php if (empty($allTickers)) { ?>

                <div class="tickers-empty">No tickers found.</div>

            <?php } ?>

            <?php foreach ($allTickers as $tickerInfo) { ?>

                <div class="ticker-card">

                    <div class="ticker-card-body"><?php echo nl2br($tickerInfo['body']); ?></div>

                    <div class="ticker-meta"><i class="far fa-calendar-alt"></i> <?php echo date("d-M-Y",$tickerInfo['created']); ?></div>

                    <div class="ticker-tags">

                        <span class="tag">Published <b class="<?php echo ($tickerInfo['published']=='Y') ? 'yes' : 'no'; ?>"><?php echo $tickerInfo['published']; ?></b></span>

                        <span class="tag">Order <b class="order"><?php echo $tickerInfo['sort_order']; ?></b></span>

                    </div>

                    <div class="ticker-actions">

                        <a href="admin/tickerManager.php?id=<?php echo $tickerInfo['id'];?>&q=edit-ticker"><i class="fas fa-edit"></i> Edit</a>

                        <a href="javascript:void(0);" onclick="javascript: confirmDelete(<?php echo $tickerInfo['id']; ?>);" ><i class="fas fa-trash-alt"></i> Delete</a>

                    </div>

                </div>

            <?php } ?>

          </div>

          <div class="pagination-wrap"><?php echo displayPaginationBelow(TICKERS_PER_PAGE,$pageno, $db); ?></div>

    <?php } ?>

<?php                   

  $design->closeDiv();

  $design->writeLeftPanel();

  $design->closeDiv();

  $design->endPage();

  $design->pageClose();    

$design = NULL; // release object