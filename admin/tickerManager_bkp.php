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

        if (get_magic_quotes_gpc()) {

            function stripslashes_deep($value) {

                $value = is_array($value) ?   array_map('stripslashes_deep', $value) : stripslashes($value);

                return $value;

            }

            $_POST = array_map('stripslashes_deep', $_POST);

            $_REQUEST = array_map('stripslashes_deep', $_REQUEST);

        }

      

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

<style type="text/css">

  #title { color: #000000; font-size: 14px; margin: 10px; margin: auto; text-align: left; display:block; }

</style>

<style type="text/css">

  .navi {

  width: 500px;

  margin: 5px;

  padding:2px 5px;

  border:1px solid #eee;

  }



  .show {

  color: blue;

  margin: 5px 0;

  padding: 3px 5px;

  cursor: pointer;

  font: 15px/19px Arial,Helvetica,sans-serif;

  }

  .show a {

  text-decoration: none;

  }

  .show:hover {

  text-decoration: underline;

  }





  ul.setPaginate li.setPage{

  padding:15px 10px;

  font-size:14px;

  }



  ul.setPaginate{

  margin:0px;

  padding:0px;

  height:100%;

  overflow:hidden;

  font:12px "Tahoma";

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

  background: none repeat scroll 0 0 #ffffff;

  border: 1px solid #cccccc;

  color: #999999;

  display: inline-block;

  font: 15px/25px Arial,Helvetica,sans-serif;

  margin: 5px 3px 0 0;

  padding: 0 5px;

  text-align: center;

  text-decoration: none;

  } 



  ul.setPaginate li a:hover,

  ul.setPaginate li a.current_page

  {

  background: none repeat scroll 0 0 #0d92e1;

  border: 1px solid #000000;

  color: #ffffff;

  text-decoration: none;

  }



  ul.setPaginate li a{

  color:black;

  display:block;

  text-decoration:none;

  padding:5px 8px;

  text-decoration: none;

  }









  </style>

';

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

    <?php if ($_SESSION['tickerManager'] == "Y") { ?>

        <div class="submenu">

          <a href="admin/tickerManager.php?q=new-ticker">Add New Ticker</a>

          <div style="float:right;">

                <a style="float:left;" href="admin/dashboard.php">Dashboard</a>

                <a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>

           </div>

        </div>

          <br />

          

          <?php if ($q=="new-ticker" || $q=="edit-ticker") { ?>              

           <form name="tickerForm" method="post" action="admin/tickerManager.php">

            <table class="contentTable">

                <col width="20%"><col width="80%">

                <tr>

                    <th>Message</th>

                    <td class="alignLeft"><textarea name="message" id="message" rows="5" cols="50"><?php echo $tickerDetails['body'] ?></textarea></td>

                </tr>

                <tr>

                    <th>Publish</th>

                    <td class="alignLeft">                            

                        <?php 

                        $checked = "checked=\"checked\"";

                        if ($tickerDetails['published'] == "N") {

                            $checked ="";        

                        } 

                        ?>

                        <input type="checkbox" name="publish" id='publish' <?php echo $checked; ?> />

                    </td>

                </tr>

                <tr>

                  <th>Sort Order</th>

                  <td>

                    <input style="width: 100%;" type="text" name="sort_order" value="<?php echo $tickerDetails['sort_order']; ?>" />

                  </td>

                </tr>

                <tr>

                    <td colspan="2">

                        <input type="submit" name="submit" value="Save" />

                        <input type="reset" name="reset" value="Clear" onclick="location.href='admin/tickerManager.php'" />

                        <?php if ($q=="new-ticker") { ?>

                            <input type="hidden" name="q" value="add-ticker" />

                        <?php } elseif ($q == "edit-ticker") { ?>

                                <input type="hidden" name="q" value="update-ticker" />

                                <input type="hidden" name="id" value="<?php echo $tickerID; ?>" />

                        <?php  }   ?>

                    </td>

                </tr>

            </table>

            </form>

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

            <br />

            <hr />

            <br />

          <?php //$paging->writePagination(); ?>

          <?php echo displayPaginationBelow(TICKERS_PER_PAGE,$pageno, $db); ?>

          <br />

          <table class="contentTable">

            <tr>

                <th>Message</th>

                <th>Created</th>

                <th>Published</th>

                <th>Sort Order</th>

                <th>Action</th>                    

            </tr>

            <?php foreach ($allTickers as $tickerInfo) { ?>

                <tr>

                    <td><?php echo nl2br($tickerInfo['body']); ?></td>

                    <td><?php echo date("d-M-Y",$tickerInfo['created']); ?></td>

                    <td><?php echo $tickerInfo['published']; ?></td>

                    <td><?php echo $tickerInfo['sort_order']; ?></td>

                    <td>

                        <a href="admin/tickerManager.php?id=<?php echo $tickerInfo['id'];?>&q=edit-ticker">Edit</a>

                        <a href="javascript:void(0);" onclick="javascript: confirmDelete(<?php echo $tickerInfo['id']; ?>);" >Delete</a>

                    </td>

                </tr>

            <?php } ?>

          </table>

          <br />              

         <?php //$paging->writePagination(); ?>

         <?php echo displayPaginationBelow(TICKERS_PER_PAGE,$pageno, $db); ?>

         <br />

    <?php } ?>

<?php                   

  $design->closeDiv();

  //$design->rightArea();  

  //$design->closeDiv();

  $design->endPage();

  $design->pageClose();    

$design = NULL; // release object