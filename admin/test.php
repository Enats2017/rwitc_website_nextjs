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
  


//echo '<pre>';
//print_r($_SESSION);
//exit;


$pageTitle ='Articles Manager';        
// create a template object
$design = new Design();  

$design->js='

<script type="text/javascript" src="https://cdn.ckeditor.com/4.8.0/full-all/ckeditor.js"></script>
 
<script type="text/javascript">
    function confirmDelete(articleID) {
        if (confirm ("Are you sure ?")){
            location.href="admin/articlesManager.php?q=delete-article&id="+articleID;
        }
    }
    
    
</script>
';
$design->css ='
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
      
<div>
<textarea id="editor" cols="30" rows="10"></textarea>  
</div>

<script>
CKEDITOR.replace('editor', {
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
    
<?php                   

  $design->closeDiv();
  //$design->rightArea();  
  //$design->closeDiv();
  //$design->endPage();
  //$design->pageClose();    
//$design = NULL; // release object