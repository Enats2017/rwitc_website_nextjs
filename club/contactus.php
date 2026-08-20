<?php 
include_once('../bootstrap.php');
//include_once('design.php');

  
  $pageTitle ='CONTACT US';        
  $design = new Design();
  $design->js =""; 
  $design->jqueryJs = "";
   
  $design->startPage("$pageTitle");
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea",'col-lg-9');
  
  $design->writeContentPageStyles();
  ?>

<span class="about-eyebrow">The Club</span>
<h2>Contact Us</h2>
<p class="about-subtitle">Get in touch with the Royal Western India Turf Club</p>
<hr class="about-divider" />

<style type="text/css">
  #leftArea a{text-decoration: none !important;color:#fff !important;}

  #leftArea table {
    max-width: 100%;
    table-layout: auto;
    word-wrap: break-word;
  }
  #leftArea {
    overflow-x: auto;
  }
</style>

<?php
  include_once('../rwitc_upload/static/CONTACTUS.HTM');
?>
            
<?php                   
  $design->closeDiv();
  $design->writeLeftPanel();

  $design->closeDiv();
  $design->closeDiv();
    $design->endPage();
$design = NULL; // release object