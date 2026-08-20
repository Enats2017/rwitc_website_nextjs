<?php 
include_once('../bootstrap.php');
require_once('../lib/articles.class.php');
$db = new dbTool();
  $pageTitle ='Working Group';        
  $design = new Design();
   
  $design->startPage("$pageTitle");
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea",'col-lg-9');
  
  $design->writeContentPageStyles();
  ?>

<span class="about-eyebrow">Organisation &amp; Management</span>
<h2>Working Group</h2>
<p class="about-subtitle">Working Group of the Club</p>
<hr class="about-divider" />

<?php
//include_once('trustee-wag-15-16.htm');
include_once('trustee1.html');
?>
            
<?php                   
  $design->closeDiv();
  $design->writeLeftPanel();

  $design->closeDiv();
  $design->closeDiv();
    $design->endPage();
$design = NULL; // release object