<?php 
include_once('../bootstrap.php');
  
  $pageTitle ='SADDLE CLOTH';        
  $design = new Design();
  $design->js =""; 
  $design->jqueryJs = "";
  $design->startPage("$pageTitle");
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea",'col-lg-9');  
  
  
  include_once('../rwitc_upload/static/SADDLECLOTH.HTM');   
  
  
  
  $design->closeDiv();
  $design->rightArea();
  $design->closeDiv();
  $design->closeDiv();
    $design->endPage();
$design = NULL; // release object