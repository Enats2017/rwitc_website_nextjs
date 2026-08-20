<?php 
include_once('../bootstrap.php');
//include_once('design.php');

  
  $pageTitle ='Charity Race Days';        
  $design = new Design();
   
  $design->startPage("$pageTitle");
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea",'col-lg-9');
  
  $design->writeContentPageStyles();
  ?>

<span class="about-eyebrow">Charities</span>
<h2>Being Receptive to Social Causes</h2>
<p class="about-subtitle">The Club's contribution towards charity and crisis relief</p>
<hr class="about-divider" />

  <p align="justify">
  The Royal Western India Turf Club has responded to any crisis by way of generous contributions. Apart from raising funds for extraordinary situations like the Gujarat Earthquake, the turf club has earmarked fifteen race days during the Season, the proceeds of which will go to charity. The turf club contributes in excess of Rs. 100 lakhs by way of charity.
  </p>
            
<?php                   
  $design->closeDiv();
  $design->writeLeftPanel();

  $design->closeDiv();
  $design->closeDiv();
    $design->endPage();
$design = NULL; // release object