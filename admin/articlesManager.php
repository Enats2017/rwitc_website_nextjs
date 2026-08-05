<?php


error_reporting(E_ALL);
ini_set("display_errors", 1);




include_once('../bootstrap.php');
require_once('../lib/articles.class.php');
include_once('../lib/pagination.class.php');
require_once("../lib/users.class.php");
require_once("../lib/userchecks.php");
/*require_once("../lib/facebook/facebook.php");
*/  
$baseurl = "https://rwitc.com/admin/postArticlesToFB.php";
//$baseurl = $http_base.'admin/postArticlesToFB.php';
  
$q = getParameterString('q','',$db);
session_start();
if(isset($_COOKIE['uid'])){                    
  $uid = $_COOKIE['uid'];    
} else {
  $uid = 0;
}        
$userObj = new Users($db);  

/*$facebook = new Facebook(array(
    'appId'  => FB_ARTICLES_APPID,
    'secret' => FB_ARTICLES_APPSECRET,
    'cookie' => true,
    ));
*/

//echo '<pre>';
//print_r($_SESSION);
//exit;


 $msg = $secmsg = "";
 $pageno = getParameterNumber('pageno',1);
 $articles = new Articles($db);
if (isAdminlogin()) {
    if ($_SESSION['articles'] == "Y") { // check login
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
              $published = getParameterString('publish','N',$db);
              $newArt = getParameterString('newArt','N',$db);
              
              // handle checkbox state
              if (strtolower($published)== "on") {
                 $published="Y";
              }     
              // handle checkbox state
              if (strtolower($newArt)== "on") {
                 $newArt="Y";
              }
            
            	
            
            
              // save new article
              if ($q == "add-article") {
                  try {
                    $bodyss = ($body);
                    $articleID = $articles->insertArticle(addslashes($title),$bodyss,$published,$newArt,0); 
                    $msg = "New article added";
                 } catch (Exception $err) {
                     echo $err->getMessage();
                 }
              }
          
            	
              //update new article 
              if ($q == "update-article") {
                 $articleID=getParameterNumber('id',0);    
                 try {
                     $bodyss = ($body);
                    $rowsAffected = $articles->updateArticle($articleID,$title,$bodyss,$published,$newArt);
                    $msg = "Article Updated";
                 } catch (Exception $err) {
                     echo $err->getMessage();
                 }      
              }
          }      
          if ($q=="edit-article") {
             $articleID=getParameterNumber('id',0);         
             try {
                $articleDetails = $articles->getArticleByID($articleID);        
             } catch (Exception $err) {
                $msg = $err->getMessage();
                echo $msg;
             }
          }
          if ($q == "delete-article") {
             $articleID=getParameterNumber('id',0);         
             try {
                $articles->deleteArticle($articleID);                
                $msg = "Article Deleted";
                // clear action
                $q="";
             } catch (Exception $err) {
                $msg = $err->getMessage();
                echo $msg;
             }
          }
          if(!isset($articleDetails['title'])){
            $articleDetails['title'] = '';
          }
          if(!isset($articleDetails['body'])){
            $articleDetails['body'] = '';
          }
          if(!isset($articleDetails['published'])){
            $articleDetails['published'] = 'N';
          }
          if(!isset($articleDetails['new'])){
            $articleDetails['new'] = 'N';
          }
      
          $totalArticles = $articles->getAllArticlesCount();
          // create a pagination object
          $paging = new Pagination($pageno,ARTICLES_PER_PAGE,$totalArticles);  
          //$allReports = $rrObj->getRaceRecordsPageWise($pageno,REPORTS_PER_PAGE);
          
          // fetch all articles
          //$allArticles = $articles->getAllArticles();
          $allArticles = $articles->getArticlesPageWise($pageno,ARTICLES_PER_PAGE);
      } else {
        $msg = "You do not have access to this page.";
      }  
} else {
    $secmsg = "Please login to access this page";
}
$pageTitle ='Articles Manager';        
// create a template object<script type="text/javascript" src="https://cdn.ckeditor.com/4.8.0/full-all/ckeditor.js"></script>
$design = new Design();  

$design->js='
<script type="text/javascript" src="../lib/ckeditor/ckeditor.js"></script>

 <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script type="text/javascript">
    function confirmDelete(articleID) {
        if (confirm ("Are you sure ?")){
            location.href="admin/articlesManager.php?q=delete-article&id="+articleID;
        }
    }
</script>
';
$design->css ='
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<style type="text/css">
  #title { color: #000000; font-size: 14px; margin: 10px; margin: auto; text-align: left; display:block; }
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
    <?php if ($_SESSION['articles'] == "Y") { ?>
        <div class="submenu">
          <a href="admin/articlesManager.php?q=new-article">Add New Article</a>
          <div style="float:right;">
                <a style="float:left;" href="admin/dashboard.php">Dashboard</a>
                <a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
           </div>
        </div>
          <br />
          
          <?php if ($q=="new-article" || $q=="edit-article") { ?>              
           
             <form  name="articleForm" method="post" action="admin/articlesManager.php">
            <table class="contentTable">
                <col width="20%"><col width="80%">
                <tr>
                    <th>Title</th>
                    <td class="alignLeft"><input type="text" name="title" id='title' size="50"  value="<?php echo $articleDetails['title']; ?>" /></td>
                </tr>
                <tr>
                    <th>Message</th>
                    <td class="alignLeft"><textarea name="message" id="message" cols="30" rows="10"><?php echo $articleDetails['body'] ?></textarea></td>
                </tr>
                <tr>
                    <th>Publish</th>
                    <td class="alignLeft">                            
                        <?php 
                        $checked = "checked=\"checked\"";
                        if ($articleDetails['published'] == "N") {
                            $checked ="";        
                        } 
                        ?>
                        <input type="checkbox" name="publish" id='publish' <?php echo $checked; ?> />
                    </td>
                </tr>
                <tr>
                    <th>New Article ?</th>
                    <td class="alignLeft">                            
                        <?php 
                        $checkedNew = "checked=\"checked\"";
                        if ($articleDetails['new'] == "N") {
                            $checkedNew ="";        
                        } 
                        ?>
                        <input type="checkbox" name="newArt" id='newArt' <?php echo $checkedNew; ?> />
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="submit" name="submit" value="Save" />
                        <input type="reset" name="reset" value="Clear" onclick="location.href='admin/articlesManager.php'" />
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

<script type="text/javascript">

                 //<![CDATA[

                CKEDITOR.replace( 'message',

                    {

                        fullPage : true,

                        filebrowserBrowseUrl : '/lib/ckfinder/ckfinder.html',

                        filebrowserImageBrowseUrl : '/lib/ckfinder/ckfinder.html?type=Images',

                        filebrowserFlashBrowseUrl : '/lib/ckfinder/ckfinder.html?type=Flash',

                        filebrowserUploadUrl : '/imageUpload.php'

                    });

                //]]>

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
                <th>Published</th>
                <th>New</th>
                <th>Action</th>                    
            </tr>
            <?php foreach ($allArticles as $articleInfo) { ?>
                <tr>
                    <td><?php echo $articleInfo['title']; ?></td>
                    <td><?php echo date("d-M-Y",$articleInfo['created']); ?></td>
                    <td><?php echo $articleInfo['published']; ?></td>
                    <td><?php echo $articleInfo['new']; ?></td>
                    <td>
                        <a href="admin/articlesManager.php?id=<?php echo $articleInfo['id'];?>&q=edit-article">Edit</a>
                        <a href="javascript:void(0);" onclick="javascript: confirmDelete(<?php echo $articleInfo['id']; ?>);" >Delete</a>
                        <?php 
                        //     $loginUrl   = $facebook->getLoginUrl(
                        //     array(
                        //         'scope'         => 'publish_stream,offline_access,read_stream,manage_pages',
                        //         'redirect_uri'  => $baseurl."?articleID={$articleInfo['id']}"
                        //     )
                        // );
                        ?>
                        <!-- <a href="<?php echo $loginUrl; ?>" target="_blank">Post To FB</a> -->
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