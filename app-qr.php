<?php
require_once("bootstrap.php");
require_once("lib/race.class.php");

$raceObj = new Racedata($db);


    $pageTitle ="RWITC | ".CURRENT_SEASON ." - QR Codes for RWITC App";     
  $design = new Design();
  $design->startPage("$pageTitle");
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea",'col-lg-9');


?>

<h2>QR Code for RWITC App on Google Play Store (Android)</h2>
<img src='images/newdesign/rwitc_play_store_qrcode.png' />
<br>
<br>
<h2>QR Code for RWITC App on iTunes (Apple - iOS)</h2>
<img src='images/newdesign/rwitc_itunes_qrcode.png' />
<h2>QR Code for RWITC App on Blackberry Appworld</h2>
<img src='images/newdesign/blackberry-qr.png' />


<?php                    
$design->closeDiv();
  $design->rightArea();  
  $design->closeDiv();
  $design->closeDiv();
    $design->endPage();
$design = NULL; // release object
