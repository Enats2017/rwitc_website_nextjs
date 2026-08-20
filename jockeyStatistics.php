<?php 
	include_once('bootstrap.php');
	include_once('lib/race.class.php');

  
  $pageTitle ='Jockey Statistics';        
  $design = new Design();
  $raceObj = new Racedata($db); 
  $design->startPage("$pageTitle");
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea",'col-lg-9');
  
  $lastUpdateDate = $raceObj->getMaxDate("RACEDATE","fhorse5");
  $jockeyData = $raceObj->getJockeyStats();
  ?>
  <h2>Jockey's Statistics as on <?php echo date("d-M-Y",strtotime($lastUpdateDate)); ?></h2>
   <table class="contentTable" style="margin-top: 20px;">
   <col width="150" /><col width="93" /><col width="93" /><col width="93" /><col width="93" /><col width="93" /><col width="93" />
          <tr>
            <th>JOCKEY</th>            
            <th>WINS</th>
            <th>SECOND</th>
            <th>THIRD</th>
            <th>FOURTH</th>
            <th>TOTAL MOUNTS</th>
            <th>WIN %</th> 
          </tr>
          <?php foreach ($jockeyData as $jockey) { ?>
            <tr>
                <th class="alignLeft darkGrey" style="padding-left: 5px;"><?php echo $jockey['NAME']; ?></th>                
                <td><?php echo $jockey['LWIN']; ?></td>
                <td><?php echo $jockey['LSEC']; ?></td>
                <td><?php echo $jockey['LTHI']; ?></td>
                <td><?php echo $jockey['LFOU']; ?></td>
                <td><?php echo $jockey['LMOUNTS']; ?></td>
                <td><?php echo round((($jockey['LWIN']*100) / $jockey['LMOUNTS']),2); ?></td>
            </tr>
          <?php } ?>
   </table>

<?php                   
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