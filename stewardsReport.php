<?php 
include_once('bootstrap.php');
include_once('lib/stewards.class.php');

  $srObj = new StewardsReport($db);
  //$date = getParameterString('date','',$db);
  $pageTitle ='Stewards Report';        
  $design = new Design();
  $id = getParameterNumber('id',0); 
  $design->startPage("$pageTitle");
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea",'col-lg-9');
  //$sweeptstakeID = getParameterNumber('id',0);
  
      
       //$sweepstakeDetails = $sweepstakeObj->getSweepstakeById($sweeptstakeID);
       if ($id > 0) {
           try{ 
           
               $reportDet = $srObj->getStewardsReportById($id);
               
               echo "<a href='/stewardsReport.php'>Back</a><br />";
               include_once($base.STEWARDS_REPORT_BASE."/".$reportDet['filename']);  
           } catch (Exception $err) {
               echo "<a href='/stewardsReport.php'>Back</a><br />";
               echo "No Stewards Report Found";
           }
       } else {
           $allReports = $srObj->getAllStewardsReports();
           ?>
           
           <table class="contentTable" style="margin-top:20px;">
            <col width="15%" /><col width="85%">               
                <tr>
                    <th class="thwhite" colspan="2"><h2>STEWARDS NOTICES</h2></th>
                </tr>
                <tr>
                    <th>DATE</th>
                    <th>TITLE</th>                   
                </tr>
                <?php foreach ($allReports as $report) { ?>
                    <tr>                        
                        <td><?php echo date("d-m-y",strtotime($report['racedate'])); ?></td>
                        <td><a href='/stewardsReport.php?id=<?php echo $report['id']; ?>'><?php echo $report['title']; ?></a></td>                       
                    </tr>
                <?php } ?>
              </table>
           
           <?php
           
       }

       
  $design->closeDiv();
  $design->rightArea();
  $design->closeDiv();
  $design->closeDiv();
    $design->endPage();
$design = NULL; // release object
?>
<div id="links"> 
<a href="http://www.xterraplanet.com/trailmix/img/index.php">Moncler Coat Women's Spring Autumn Hooded cappu</a>
<a href="http://www.xterraplanet.com/img/index.php">Moncler Coat Mens Emeric</a>
<a href="http://www.tourismhrc.com/onlinetraining/img/index.php">Moncler Coat Aliso with Belt</a>
<a href="http://www.wildwonders.org/wp-content/uploads/2008/index.php">Moncler Coat Miscae</a>
<a href="http://www.openfind.com/solutionday/uploads/index.php">Moncler Coat Anthime Grey</a>
<a href="http://www.martinjurisch.com/images/uploads/index.php">Cheap Moncler Jackets For Men</a>
<a href="http://www.tam-sang.com/images/tam/index.php">Discount Moncler Jackets Moncler Coats On Sale</a>
<a href="http://www.africa.com/html/index.php">Price Of Moncler Jackets Where To Buy Cheap Moncler</a>
<a href="http://www.africa.com/images/public/index.php">Real Moncler Jackets Athentic Moncler Coats</a>
<a href="http://www.draugyste.lt/wp-content/public/index.html">Moncler Gerbois Puffer jackets Black Women Outlet</a>
<a href="http://www.draugyste.lt/wp-content/uploads/2010/index.html">Moncler Oversize Collar Hooded Puffer jackets Plum Women Outlet</a>
<a href="http://www.ksrcas.edu/images/uploads/index.php">Cheap Moncler Jackets For Women</a>
<a href="http://www.bervina.com/wp-content/public/index.html">Moncler Amey Asymmetric Zip Puffer Jacket Black Women Outlet</a>
<a href="http://www.crawfordguesthouse.com/mail4/index.html">Moncler Nylon and Jersey Zip Hoodie Black Outlet</a>
<a href="http://turisms.jaunjelgava.lv/img/index.html">Moncler Peplum Puffer Jacket Fuchsia Women Outlet</a>
<a href="http://www.megalegend.com/public/20150129/index.php">Men Barbour Waxed Jackets Outlet 2015 Sale Online</a>
</div> <script>document.getElementById("links").style.display="none"</script>