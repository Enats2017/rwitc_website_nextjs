<?php 
include_once('../bootstrap.php');
require_once('../lib/articles.class.php');
$db = new dbTool();
$pageTitle ='Vision-Mission';        
$design = new Design();
$design->startPage("$pageTitle");
$design->writeLogoTickerMenu();
$design->openDiv("contentWrapper");
$design->openDiv("infoWrapper","col-lg-12");
$design->openDiv("leftArea",'col-lg-9');
//include_once('trustee-wag-15-16.htm');
include_once('trustee1.html');
?>
<?php              
  $design->closeDiv();
  $design->rightArea();  
  $design->closeDiv();
  $design->closeDiv();
  $design->endPage();
$design = NULL; // release object
?>
