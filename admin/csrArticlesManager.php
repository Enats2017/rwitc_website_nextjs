<?php

error_reporting(1);
include_once('../bootstrap.php');
require_once('../lib/csrarticles.class.php');
include_once('../lib/pagination.class.php');
require_once("../lib/users.class.php");
require_once("../lib/userchecks.php");
require_once("../lib/facebook/facebook.php");
  
$baseurl = "http://www.rwitc.com/admin/postArticlesToFB.php";
  
$q = getParameterString('q','',$db);
session_start(); 

if(isset($_COOKIE['uid'])){                    
  $uid = $_COOKIE['uid'];    
} else {
  $uid = 0;
} 
$userObj = new Users($db);  

$facebook = new Facebook(array(
    'appId'  => FB_ARTICLES_APPID,
    'secret' => FB_ARTICLES_APPSECRET,
    'cookie' => true,
    ));

 $msg = $secmsg = "";
 $pageno = getParameterNumber('pageno',1);
 $articles = new CSRArticles($db);
if (isAdminlogin()) {
    if ($_SESSION['articles'] == "Y") { // check login
        // if (get_magic_quotes_gpc()) {
        //     function stripslashes_deep($value) {
        //         $value = is_array($value) ?   array_map('stripslashes_deep', $value) : stripslashes($value);
        //         return $value;
        //     }
        //     $_POST = array_map('stripslashes_deep', $_POST);
        //     $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
        // }
      
          // all actions POST form submissions go here
          if (isset($_REQUEST['submit'])) {
              
              $title = getParameterString('title','',$db);
              $body = getParameterString('message','',$db);
              $published = getParameterString('publish','N',$db);
              
              // handle checkbox state
              if (strtolower($published)== "on") {
                 $published="Y";
              }     
              
              // save new article
              if ($q == "add-article") {
                  try {
                    $articleID = $articles->insertArticle(addslashes($title),$body,$published); 
                    $msg = "New article added";
                 } catch (Exception $err) {
                     echo $err->getMessage();
                 }
              }
          
              //update new article 
              if ($q == "update-article") {
                 $articleID=getParameterNumber('id',0);    
                 try {
                    $rowsAffected = $articles->updateArticle($articleID,$title,$body,$published);
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
$pageTitle ='CSR Articles Manager';        
// create a template object
$design = new Design();  

$design->js='
<script type="text/javascript" src="https://cdn.ckeditor.com/4.8.0/full-all/ckeditor.js"></script>
<script type="text/javascript">
    function confirmDelete(articleID) {
        if (confirm ("Are you sure ?")){
            location.href="admin/csrArticlesManager.php?q=delete-article&id="+articleID;
        }
    }
</script>
';
$design->css ='
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
';
$design->jqueryJs = ""; 
$design->startPage("$pageTitle");  
$design->writeLogoTickerMenu();
$design->openDiv("contentWrapper");
$design->openDiv("infoWrapper","col-lg-12");
$design->openDiv("leftArea",'col-lg-9');
?>

    <style type="text/css">

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

.articles-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
.add-article-btn { display: inline-flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #1a7a45; color: #0f5c33; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; }
.add-article-btn:hover { background: #e6f4ec; }
.header-links { display: flex; align-items: center; gap: 16px; }
.header-links a { color: #0f5c33; text-decoration: none; font-weight: 600; font-size: 14px; }
.header-links a:hover { text-decoration: underline; }

.articles-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 24px; }
.article-card { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; padding: 18px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); display: flex; flex-direction: column; }
.article-card-top { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 14px; }
.article-icon { width: 38px; height: 38px; border-radius: 50%; background: #e6f4ec; color: #0f5c33; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0; }
.article-info { flex: 1; min-width: 0; }
.article-title { font-size: 15px; font-weight: 600; color: #2b332f; line-height: 1.4; margin-bottom: 6px; }
.article-date { font-size: 12.5px; color: #7a8c84; display: flex; align-items: center; gap: 6px; }
.article-tags { display: flex; gap: 10px; margin-bottom: 14px; flex-wrap: wrap; }
.article-tags .tag { font-size: 12.5px; color: #2b332f; background: #f5f4ee; border: 1px solid #e2e6e4; padding: 6px 10px; border-radius: 6px; display: flex; align-items: center; gap: 6px; }
.article-tags .tag b { padding: 1px 8px; border-radius: 4px; font-size: 12px; font-weight: 700; }
.article-tags .tag b.yes { background: #0f5c33; color: #fff; }
.article-tags .tag b.no { background: #c0392b; color: #fff; }
.article-actions { display: flex; justify-content: flex-end; gap: 16px; border-top: 1px solid #eef0ee; padding-top: 12px; margin-top: auto; flex-wrap: wrap; }
.article-actions a { font-size: 13.5px; text-decoration: none; display: flex; align-items: center; gap: 6px; font-weight: 500; }
.article-actions a:nth-child(1) { color: #0f5c33; }
.article-actions a:nth-child(2) { color: #c0392b; }
.article-actions a:nth-child(3) { color: #1877f2; }

.article-form-wrap { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; padding: 20px; margin-bottom: 24px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); }
.article-form-wrap .contentTable { width: 100%; }
.article-form-wrap .contentTable th { text-align: left; padding: 10px 8px; color: #2b332f; }
.article-form-wrap .contentTable td { padding: 10px 8px; }
.article-form-wrap input[type="submit"], .article-form-wrap input[type="reset"] { background: #0f5c33; color: #fff; border: none; padding: 9px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; margin-right: 8px; }
.article-form-wrap input[type="reset"] { background: #fff; color: #2b332f; border: 1px solid #e2e6e4; }

.pagination-wrap { margin: 18px 0 24px; }
.pagination-wrap, .pagination-wrap * { font-family: 'Inter','Segoe UI',Arial,sans-serif !important; box-sizing: border-box; }
.pagination-wrap ul.pagination {
    list-style: none;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    margin: 0;
    padding: 0;
}
.pagination-wrap ul.pagination li {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    padding: 0 14px;
    border: 1px solid #e2e6e4;
    border-radius: 10px;
    background: #fff;
    color: #2b332f;
    font-size: 14px;
    font-weight: 500;
    line-height: 1;
}
.pagination-wrap ul.pagination li a {
    color: inherit;
    text-decoration: none;
}
.pagination-wrap ul.pagination li:hover:has(a) { background: #e6f4ec; border-color: #1a7a45; color: #0f5c33; }
.pagination-wrap ul.pagination li.currPage {
    background: #0f5c33;
    border-color: #0f5c33;
    color: #fff;
    font-weight: 700;
}
.pagination-wrap ul.pagination li.dots {
    border: none;
    background: transparent;
    min-width: auto;
    padding: 0 4px;
    color: #7a8c84;
}
.pagination-wrap ul.pagination li.summary {
    border: none;
    background: transparent;
    color: #7a8c84;
    font-weight: 400;
    min-width: auto;
    padding: 0 4px;
}
.pagination-wrap ul.pagination li:not(.currPage):not(.dots):not(.summary):not(:has(a)) {
    color: #b7c0bb;
    background: #f5f4ee;
}

html, body { scrollbar-width: none; -ms-overflow-style: none; }
html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }

@media (max-width: 700px) { .articles-grid { grid-template-columns: 1fr; } #leftArea { padding: 0 16px; } .articles-header { flex-direction: column; align-items: flex-start; } }
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
    <?php if ($_SESSION['articles'] == "Y") { ?>
        <div class="articles-header">
          <a class="add-article-btn" href="admin/csrArticlesManager.php?q=new-article"><i class="fas fa-plus"></i> Add New CSR Article</a>
          <div class="header-links">
                <!-- <a href="admin/dashboard.php">Dashboard</a>
                <a href="admin/adminlogin.php?q=logout">Logout</a> -->
           </div>
        </div>
          
          <?php if ($q=="new-article" || $q=="edit-article") { ?>              
           <div class="article-form-wrap">
             <form name="articleForm" method="post" action="admin/csrArticlesManager.php">
            <table class="contentTable">
                <col width="20%"><col width="80%">          
                <tr>                                        
                    <th>Title</th>
                    <td class="alignLeft"><input type="text" name="title" id='title' size="50"  value='<?php echo $articleDetails['title']; ?>' /></td>
                </tr>
                <tr>
                    <th>Message</th>
                    <td class="alignLeft"><textarea name="message" id="message"><?php echo $articleDetails['body'] ?></textarea></td>
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
                    <td colspan="2">
                        <input type="submit" name="submit" value="Save" />
                        <input type="reset" name="reset" value="Clear" onclick="location.href='admin/csrArticlesManager.php'" />
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
           </div>
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

          <div class="articles-grid">
            <?php foreach ($allArticles as $articleInfo) { ?>
                <div class="article-card">
                    <div class="article-card-top">
                        <span class="article-icon"><i class="fas fa-file-alt"></i></span>
                        <div class="article-info">
                            <div class="article-title"><?php echo $articleInfo['title']; ?></div>
                            <div class="article-date"><i class="far fa-calendar-alt"></i> <?php echo date("d-M-Y",$articleInfo['created']); ?></div>
                        </div>
                    </div>
                    <div class="article-tags">
                        <span class="tag">Published <b class="<?php echo ($articleInfo['published']=='Y') ? 'yes' : 'no'; ?>"><?php echo $articleInfo['published']; ?></b></span>
                    </div>
                    <div class="article-actions">
                        <a href="admin/csrArticlesManager.php?id=<?php echo $articleInfo['id'];?>&q=edit-article"><i class="fas fa-edit"></i> Edit</a>
                        <a href="javascript:void(0);" onclick="javascript: confirmDelete(<?php echo $articleInfo['id']; ?>);" ><i class="fas fa-trash-alt"></i> Delete</a>
                        <?php 
                            $loginUrl   = $facebook->getLoginUrl(
                            array(
                                'scope'         => 'publish_stream,offline_access,read_stream,manage_pages',
                                'redirect_uri'  => $baseurl."?articleID={$articleInfo['id']}"
                            )
                        );
                        ?>
                        <a href="<?php echo $loginUrl; ?>" target="_blank"><i class="fab fa-facebook"></i> Post To FB</a>
                    </div>
                </div>
            <?php } ?>
          </div>

          <div class="pagination-wrap"><?php $paging->writePagination(); ?></div>
          <br />
    <?php } ?>
<?php                   
  $design->closeDiv();
  $design->writeLeftPanel();
  $design->closeDiv();
  $design->closeDiv();
  $design->endPage();
  $design->pageClose();    
$design = NULL; // release object