<?php 
include_once('../bootstrap.php');
//include_once('design.php');

  
  $pageTitle ='Record Timings';        
  $design = new Design();
   
  $design->startPage("$pageTitle");
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea",'col-lg-9');
  include_once('recordTimings.htm');
  $design->closeDiv();
  $design->rightArea();
  $design->closeDiv();
  $design->closeDiv();
  $design->endPage();
$design = NULL; // release object
