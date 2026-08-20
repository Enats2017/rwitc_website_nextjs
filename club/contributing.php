<?php 
include_once('../bootstrap.php');
//include_once('design.php');

  
  $pageTitle ='Contributing';        
  $design = new Design();
   
  $design->startPage("$pageTitle");
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea",'col-lg-9');
  
  $design->writeContentPageStyles();
  ?>

<span class="about-eyebrow">The Club</span>
<h2>Contributing to the Community</h2>
<p class="about-subtitle">RWITC's role as a socially responsible corporate citizen</p>
<hr class="about-divider" />

                         <p align="justify" >
                                As a socially responsible and responsive corporate citizen, RWITC hosts several special race days the proceeds of which are donated to charity. A part of its budget is earmarked for such contributions. The Club members have also helped it to raise significant amount of money on special occasions and in distress situations such as the Gujarat Earthquake.
                         </p>

                         <p align="justify" >
                                The Club commemorates people who have made a special contribution to the society by naming certain races after them. A recent example was the races honouring the 26/11 martyrs in Mumbai including the Major General Hooda Trophy. The Club also contributed towards a Fund to help the injured in the attacks.  
                         </p>
            
<?php                   
  $design->closeDiv();
  $design->writeLeftPanel();

  $design->closeDiv();
  $design->closeDiv();
    $design->endPage();
$design = NULL; // release object