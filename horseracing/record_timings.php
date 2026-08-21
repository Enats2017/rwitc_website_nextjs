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
  $design->writeContentPageStyles();
  ?>
  <style type="text/css">
  #leftArea.col-lg-9 table { width: 500px !important; }
  </style>
  <?php
  include_once('recordTimings.htm');
  $design->closeDiv();
  $design->writeLeftPanel();
  $design->closeDiv();
  $design->closeDiv();
  $design->endPage();
$design = NULL; // release object
