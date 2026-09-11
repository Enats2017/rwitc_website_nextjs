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

.foal-title {
    font-size: 24px;
    color: #2b332f;
    margin: 20px 0 20px 0;
    display: flex;
    align-items: center;
    gap: 12px;
}
.foal-title i {
    color: #0f5c33;
}

.foal-card {
    background: #fff;
    border: 1px solid #e2e6e4;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    overflow-x: auto;
}

@media (max-width: 700px) {
    #leftArea.col-lg-9 { padding: 0 16px; }
    .foal-card { padding: 14px; }
}
</style>
  ';
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

<h1 class="foal-title">Foal Records</h1>

<div class="foal-card">
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
</div>
<?php                    
$design->closeDiv();
  $design->writeLeftPanel();
  $design->closeDiv();
    $design->endPage();
$design = NULL; // release object