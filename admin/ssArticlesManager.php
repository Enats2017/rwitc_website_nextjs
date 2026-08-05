<?php
include_once('../bootstrap.php');
require_once('../lib/articles.class.php');
require_once('../lib/ssarticles.class.php');
include_once('../lib/pagination.class.php');
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
$articleDetails = array();
$articleDetails['title'] = '';
 $articleDetails['body'] = '';
$articleDetails['article_type'] = '';
   
 $msg = $secmsg = "";
 $pageno = getParameterNumber('pageno',1);
 $ssArticles = new SSArticles($db);
 $articles = new Articles($db);
if (isAdminlogin()) {
    if ($_SESSION['shiven_surendranath'] == "Y") { // check login
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
              $articleType = getParameterString('articleType','PRE',$db);
              $date = getParameterString('date','',$db);
                  
              // save new article
              if ($q == "add-article") {
                  try {
                    $articleID = $ssArticles->insertArticle($title,$body,$articleType,$date); 
                    $articleID2 = $articles->insertArticle($title,$body,'Y','Y',$articleID);
                 } catch (Exception $err) {
                     echo $err->getMessage();
                 }
              }
          
              //update new article 
              if ($q == "update-article") {
                 $articleID=getParameterNumber('id',0);    
                 try {
                    $rowsAffected = $ssArticles->updateArticle($articleID,$title,$body,$articleType,$date);
                    $rowsAffected2 = $articles->updateArticleByPGArticleID($title,$body,$date,$articleID);
                 } catch (Exception $err) {
                     echo $err->getMessage();
                 }      
              }
          } 
      
      		
          if ($q=="edit-article") {
             $articleID=getParameterNumber('id',0);         
             try {
                $articleDetails = $ssArticles->getArticleByID($articleID);        
             } catch (Exception $err) {
                $msg = $err->getMessage();
                echo $msg;
             }
          }
          if ($q == "delete-article") {
             $articleID=getParameterNumber('id',0);         
             try {
                $ssArticles->deleteArticle($articleID);
                $msg = "Article Deleted";
                // clear action
                $q="";
             } catch (Exception $err) {
                $msg = $err->getMessage();
                echo $msg;
             }
          }
      
          $totalArticles = $ssArticles->getAllArticlesCount();
          // create a pagination object
          $paging = new Pagination($pageno,ARTICLES_PER_PAGE,$totalArticles);  
          //$allReports = $rrObj->getRaceRecordsPageWise($pageno,REPORTS_PER_PAGE);
          
          // fetch all articles
          //$allArticles = $ssArticles->getAllArticles();
          $allArticles = $ssArticles->getArticlesPageWise($pageno,ARTICLES_PER_PAGE);
      } else {
        $msg = "You do not have access to this page.";
      }  
} else {
    $secmsg = "Please login to access this page";
}
$pageTitle ='Articles Manager';        
// create a template object  <script type="text/javascript" src="/lib/ckeditor/ckeditor.js"></script>
$design = new Design();  

$design->js='
<script type="text/javascript" src="/js/jquery.ui.core.min.js"></script>    
    <script type="text/javascript" src="/js/jquery.ui.datepicker.min.js"></script>
<script type="text/javascript" src="https://cdn.ckeditor.com/4.8.0/full-all/ckeditor.js"></script>
<script type="text/javascript">
    function confirmDelete(articleID) {
        if (confirm ("Are you sure ?")){
            location.href="admin/ssArticlesManager.php?q=delete-article&id="+articleID;
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
    $('#article_date').datepicker({
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
    <?php if ($_SESSION['shiven_surendranath'] == "Y") { ?>
        <div class="submenu">
          <a href="admin/ssArticlesManager.php?q=new-article">Add New Article</a>
          <div style="float:right;">
                <a style="float:left;" href="admin/dashboard.php">Dashboard</a>
                <a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
           </div>
        </div>
          <br />
          
          <?php if ($q=="new-article" || $q=="edit-article") { ?>              
           <form name="articleForm" method="post" action="admin/ssArticlesManager.php">
            <table class="contentTable">
                <col width="20%"><col width="80%">
                <tr>
                        <th>Date</th>
                        <?php 
                                $date= "";
                                if ($q=="edit-article") {
                                    $date =  date("Y-m-d",$articleDetails['created']);
                                }
                        ?>
                        <td class="alignLeft"><input type="text" name="date" id='article_date' value="<?php echo $date; ?>" /></td>
                    </tr>
                <tr>
                    <th>Title</th>
                    <td class="alignLeft"><input type="text" name="title" id='title' size="50" value="<?php echo $articleDetails['title'] ?>" /></td>
                </tr>
                <tr>
                    <th>Message</th>
                    <td class="alignLeft"><textarea name="message" id="message"><?php echo $articleDetails['body'] ?></textarea></td>
                </tr>
                <tr>
                    <th>Publish</th>
                    <td class="alignLeft">         
                        <?php $articleDetails['body'] ?>                   
                        <select name="articleType">
                            <option value="PRE" <?php echo ($articleDetails['article_type'] == "PRE")? "selected='selected'" : ""; ?>>PRE</option>
                            <option value="POST" <?php echo ($articleDetails['article_type'] == "POST")? "selected='selected'" : ""; ?>>POST</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="submit" name="submit" value="Save" />
                        <input type="reset" name="reset" value="Clear" onclick="location.href='admin/ssArticlesManager.php'" />
                        <?php if ($q=="new-article") { ?>
                            <input type="hidden" name="q" value="add-article" />
                        <?php } elseif ($q == "edit-article") { ?>
                                <input type="hidden" name="q" value="update-article" />
                                <input type="hidden" name="id" value="<?php echo $articleID; ?>" />
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
          <?php $paging->writePagination(); ?>
          <br />
          <table class="contentTable">
            <tr>
                <th>Title</th>
                <th>Created</th>
                <th>Article Type</th>
                <th>Action</th>                    
            </tr>
            <?php foreach ($allArticles as $articleInfo) { ?>
                <tr>
                    <td><?php echo $articleInfo['title']; ?></td>
                    <td><?php echo date("d-M-Y",$articleInfo['created']); ?></td>
                    <td><?php echo $articleInfo['article_type']; ?></td>
                    <td>
                        <a href="admin/ssArticlesManager.php?id=<?php echo $articleInfo['id'];?>&q=edit-article">Edit</a>
                        <a href="javascript:void(0);" onclick="javascript: confirmDelete(<?php echo $articleInfo['id']; ?>);" >Delete</a>
                    </td>
                </tr>
            <?php } ?>
          </table>
          <br />              
         <?php $paging->writePagination(); ?>
         <br />
    <?php } ?>
<?php                   
  $design->closeDiv();
  //$design->rightArea();  
  //$design->closeDiv();
  $design->endPage();
  $design->pageClose();    
$design = NULL; // release object