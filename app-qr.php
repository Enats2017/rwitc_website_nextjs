<?php
require_once("bootstrap.php");
require_once("lib/race.class.php");

$raceObj = new Racedata($db);


    $pageTitle ="RWITC | ".CURRENT_SEASON ." - QR Codes for RWITC App";     
  $design = new Design();
  $design->css = '
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style type="text/css">
#infoWrapper.col-lg-12 {
    display: flex;
    flex-direction: row-reverse;
    align-items: flex-start;
    max-width: 1500px;
    margin: 30px auto;
    float: none;
}
#leftArea.col-lg-9 {
    flex: 1 1 auto;
    min-width: 0;
    max-width: none;
    margin: 0;
    padding: 0 30px;
    box-sizing: border-box;
    float: none;
    width: auto;
    display: block;
}

.qr-page-title {
    font-size: 24px;
    color: #2b332f;
    margin: 20px 0 20px 0;
    display: flex;
    align-items: center;
    gap: 12px;
}
.qr-page-title i {
    color: #0f5c33;
}

.qr-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.qr-card {
    background: #fff;
    border: 1px solid #e2e6e4;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    text-align: center;
}
.qr-card h2 {
    font-size: 15px;
    color: #2b332f;
    margin: 0 0 16px 0;
}
.qr-card img {
    max-width: 100%;
    height: auto;
    border: 1px solid #e2e6e4;
    border-radius: 8px;
    padding: 10px;
}

@media (max-width: 900px) {
    .qr-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 700px) {
    #leftArea.col-lg-9 { padding: 0 16px; }
    .qr-grid { grid-template-columns: 1fr; }
}
</style>
  ';
  $design->startPage("$pageTitle");
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea",'col-lg-9');


?>

<h1 class="qr-page-title"><i class="fas fa-qrcode"></i> QR Codes for RWITC App</h1>

<div class="qr-grid">

    <div class="qr-card">
        <h2>QR Code for RWITC App on Google Play Store (Android)</h2>
        <img src='images/newdesign/rwitc_play_store_qrcode.png' />
    </div>

    <div class="qr-card">
        <h2>QR Code for RWITC App on iTunes (Apple - iOS)</h2>
        <img src='images/newdesign/rwitc_itunes_qrcode.png' />
    </div>

    <div class="qr-card">
        <h2>QR Code for RWITC App on Blackberry Appworld</h2>
        <img src='images/newdesign/blackberry-qr.png' />
    </div>

</div>

<?php                    
$design->closeDiv();
  $design->writeLeftPanel();
  $design->closeDiv();
    $design->endPage();
$design = NULL; // release object