<?php 
include_once('../bootstrap.php');
//include_once('design.php');

  
  $pageTitle ='Structure';        
  $design = new Design();
   
  $design->startPage("$pageTitle");
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea",'col-lg-9');
  
  $design->writeContentPageStyles();
  ?>

<span class="about-eyebrow">Organisation &amp; Management</span>
<h2>Structure</h2>
<p class="about-subtitle">How the Club is governed and managed</p>
<hr class="about-divider" />

            <p align="justify" class="StaticArticle">
        
            The management of the Club, its finances and property is the responsibility of the 9-member Managing Committee. Club members elect these 9 Committee members as laid down in the Articles of Association of the Club. There are, in addition, two Government nominees on the Committee - usually the Additional Chief Secretary, Government of Maharashtra, Home Department and the Additional Chief Secretary, Government of Maharashtra, Revenue and Forests Department. The Committee retires every year. Fresh elections take place on the third Thursday of December. The Chairman is elected by the members of the Managing Committee.            <br>
           </p>
            
<?php                   
  $design->closeDiv();
  $design->writeLeftPanel();

  $design->closeDiv();
  $design->closeDiv();
    $design->endPage();
$design = NULL; // release object