<?php
require_once("bootstrap.php");
require_once("lib/race.class.php");

$raceObj = new Racedata($db);
  $mareName = getParameterString('mareName','',$db); 
  $damNat = getParameterString('damnat','',$db);
  $damNatDisp = ''; 
  if ($damNat != '') {
      $damNatDisp = '['.$damNat.']';
  }
  //$mareName = stripslashes($mareName);
  $pageTitle ="RWITC | ".CURRENT_SEASON ." - Foal Records";     
  $design = new Design();
  $design->startPage("$pageTitle");
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea",'col-lg-9');
  if ($mareName !== "")
    $foalDetails = $raceObj->getFoalDataByMareName($mareName,$damNat);
    
    $msirenat = $mdamnat = "";
    if (!empty($foalDetails[0]['MSIRENAT'])) {
        $msirenat = "[{$foalDetails[0]['MSIRENAT']}]";
    }
    if (!empty($foalDetails[0]['MDAMNAT'])) {
        $mdamnat = "[{$foalDetails[0]['MDAMNAT']}]";
    }
    if (!empty($foalDetails[0]['MARENAT'])) {
        $marenat = "({$foalDetails[0]['MARENAT']})";
    }
  //print_r($foalDetails);
  //$mareName = stripslashes($mareName)  
  
?>
  <?php if ($mareName !== "" && count($foalDetails) > 0) { ?> 
   <table class='contentTable'>
        <tr>
            <th colspan="5" class="thwhite">                
                Foals of <?php echo $foalDetails[0]['MARENAME'] . $damNatDisp ." ( ".$foalDetails[0]['MARESIRE']."  $msirenat -".$foalDetails[0]['MAREDAM']." $mdamnat)"; ?>
            </th>
        </tr>
        <tr>
            <th>Year of Foal</th>
            <th>Desc</th>
            <th>Horse</th>
            <th>Wins</th>
            <th>Stakes Won (Rs)</th>
        </tr>
        <?php foreach ($foalDetails as $foal) {?>
            <tr>
                <td><?php echo $foal['YROFFLNG']; ?></td>
                <td><?php echo $foal['HORSECOLOR']." ".$foal['HORSESEX']; ?></td>
              <td><a href="performanceProfile.php?q=get-profile&horsename=<?php echo $foal['HORSE_NAME'];?>"><b><?php echo $foal['HORSE_NAME']; ?></b></a></td>
                <td><?php echo $foal['WIN']; ?></td>
                <?php 
                    if ($foal['STAKES'] == 0 ) { 
                            $foal['STAKES'] = "-"; 
                    }
                ?>
                <td><?php echo $foal['STAKES']; ?></td>
            </tr>
        <?php } ?>
        <tr>
            <th height="5" colspan="5" class="thwhite"></th>
        </tr>
        <tr>
            <th colspan="5" class="thwhite">The above data has been collated from the records maintained by the Stud Book Authority of India and is as on<br />31st July 2022. It does not include details of siblings abroad or Indian horses' performances abroad.</th>
        </tr>
   </table>
   <?php } elseif ($mareName == "") { ?>
    <table class='contentTable'>
        <tr>
            <th class="thwhite">No Mare Selected. Please select Mare from race card.</th>
        </tr>
    </table>    
   <?php } elseif (count($foalDetails) == 0) { ?>
   <table class='contentTable'>
        <tr>
            <th class="thwhite">No Foals found for Mare <?php echo $mareName . " " . $damNatDisp; ?></th>
        </tr>
    </table>    
   <?php } ?>
<?php                    
$design->closeDiv();
  $design->rightArea();  
  $design->closeDiv();
  $design->closeDiv();
    $design->endPage();
$design = NULL; // release object
