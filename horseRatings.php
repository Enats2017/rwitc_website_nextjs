<?php 

include_once('bootstrap.php');

//include_once('design.php');


  

  $pageTitle ='Ratings of all horses';        

  $design = new Design();

   

  $design->startPage("$pageTitle");

  $design->writeLogoTickerMenu();

  $design->openDiv("contentWrapper");

  $design->openDiv("infoWrapper","col-lg-12");

  $design->openDiv("leftArea",'col-lg-9');

  ?>       

    <?php   include_once('rwitc_upload/static/RATINGS.HTM'); ?>

<?php                   

  $design->closeDiv();

  $design->rightArea();  

  $design->closeDiv();

  $design->closeDiv();

  $design->endPage();

$design = NULL; // release object

?>

