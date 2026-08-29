<?php 

include_once('bootstrap.php');

//include_once('design.php');


  

  $pageTitle ='Ratings of all horses';        

  $design = new Design();

   

  $design->js = '';

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

.horseratings-title {
    font-size: 24px;
    color: #2b332f;
    margin: 0 0 20px 0;
    display: flex;
    align-items: center;
    gap: 12px;
}
.horseratings-title i {
    color: #0f5c33;
}

html, body { scrollbar-width: none; -ms-overflow-style: none; }
html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }

.horseratings-card {
    background: #fff;
    border: 1px solid #e2e6e4;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    overflow-x: auto;
}

@media (max-width: 700px) {
    #leftArea.col-lg-9 { padding: 0 16px; }
    .horseratings-card { padding: 16px; }
}
</style>
  ';

  $design->startPage("$pageTitle");

  $design->writeLogoTickerMenu();

  $design->openDiv("contentWrapper");

  $design->openDiv("infoWrapper","col-lg-12");

  $design->openDiv("leftArea",'col-lg-9');

  ?>       

    <!-- <h1 class="horseratings-title"><i class="fas fa-horse"></i> Ratings of all horses</h1> -->

    <div class="horseratings-card">
    <?php   include_once('rwitc_upload/static/RATINGS.HTM'); ?>
    </div>

<?php                   

  $design->closeDiv();

  $design->writeLeftPanel();

  $design->closeDiv();

  $design->endPage();

$design = NULL; // release object

?>