<?php 
include_once('../bootstrap.php');
  
  $pageTitle ='CONTACT US';        
  $design = new Design();
  $design->js =""; 
  $design->jqueryJs = "";
  $design->startPage("$pageTitle");
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea",'col-lg-9');  
  ?>
<style type="text/css">
  a{text-decoration: none !important;color:#fff !important;}
</style>
  <?php
  
  include_once('../rwitc_upload/static/CONTACTUS.HTM');   
  
  
  
  $design->closeDiv();
  $design->rightArea();
  $design->closeDiv();
  $design->closeDiv();
    $design->endPage();
$design = NULL; // release object