<?php

require_once("bootstrap.php");

require_once("lib/race.class.php");



$raceObj = new Racedata($db);



$date = date('Y-m-d');





  $pageTitle ="RWITC | ".CURRENT_SEASON ." - Horses In Training - $date";     

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

.hit-page-title {
    font-size: 24px;
    color: #2b332f;
    margin: 20px 0 0 0;
    display: flex;
    align-items: center;
    gap: 12px;
}
.hit-page-title i {
    color: #0f5c33;
}

.hit-card {
    background: #fff;
    border: 1px solid #e2e6e4;
    border-radius: 12px;
    padding: 20px;
    margin-top: 16px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    overflow-x: auto;
}

@media (max-width: 700px) {
    #leftArea.col-lg-9 { padding: 0 16px; }
    .hit-card { padding: 14px; }
}
</style>
  ';

  $design->startPage("$pageTitle");

  $design->writeLogoTickerMenu();

  $design->openDiv("contentWrapper");

  $design->openDiv("infoWrapper","col-lg-12");

  $design->openDiv("leftArea",'col-lg-9');

  $trainersList = $raceObj->getAllActiveTrainerNames();

  //print_r($trainersList);

  $trainerName = urldecode(getParameterString('trainer','none',$db));



?>

<style type="text/css">

.headingBand { float: left; width: 100%; position:relative; margin-top: 20px; } 

.headstripGrey { background: #E0E0DE; width: 100%; height: 40px; }

.greenPatch { position: absolute; top: -4px; left: 210px;}

.greenPatch .greenLeft { float: left; background: url(images/newdesign/headLeft.png) 0 0; width: 8px; height: 38px; }

.greenHead { float: left; background: #0E663E; text-transform: uppercase; font-family: 'Arial'; font-size: 16px; color: #FFFFFF; padding: 9px;}

.greenPatch .greenRight { float: left; background: url(images/newdesign/headRight.png) 0 0; width: 8px; height: 38px; }

.trainerList td { background: none; border-bottom: 1px dotted #000000;   }

.srNumber { width: 16px; height: 15px; float: left; color: #FFFFFF; background: url(images/newdesign/numberBG.png) 0 0 no-repeat; font-size: 12px; margin-right: 5px; text-align: center; }

html, body { scrollbar-width: none; -ms-overflow-style: none; }
html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }

</style>

<!-- <h1 class="hit-page-title"><i class="fas fa-horse"></i> Horses In Training</h1> -->

<div class="hit-card">

   <div class='headingBand'>

            <div class='headstripGrey' >              

             <div class='greenHead' align="center">TRAINER WISE HORSES IN TRAINING</div>

            </div>

            <div class='greenPatch'>

            </div>            

   </div>

   <table class="contentTable trainerList" style="margin-top: 15px;" cellspacing="5">

        <!--<tr>

            <th colspan='4'>List of Trainers</th>

        </tr>-->

        <tr>

        <?php 

            $noofItemsPerRow = 4;          

            for ($i=0;$i<count($trainersList);$i++) {

                if ($i % $noofItemsPerRow==0 && $i!=count($trainersList)) {

                    echo "</tr>";

                    echo "<tr>";

                }

                if ($i==count($trainersList)) {

                    echo "</tr>";                    

                }else { 

                    if ($trainerName == $trainersList[$i]) {

                    echo "<th class='darkGrey alignLeft'><span class='srNumber'>".($i+1)."</span><a href='horsesInTraining.php?trainer=".urlencode($trainersList[$i])."'>{$trainersList[$i]}</a></th>";

                    }

                    else { 

                     echo "<td class='alignLeft'><span class='srNumber'>".($i+1)."</span><a href='horsesInTraining.php?trainer=".urlencode($trainersList[$i])."'>{$trainersList[$i]}</a></td>";
                    }

                 }           

            }

        ?>

   </table>                

   <?php 

    

    if ($trainerName !== "none") {

        $trainerCode = $raceObj->getTrainerCode($trainerName);               

        $trainerHorseList = $raceObj->getHorsesDetailsForTrainers($trainerCode);

        echo '<br /><br />';

        echo '<div class="pageHeader">

                    <div class="pageHeading">'.$trainerName.'

                        <div class="subHeading">Click on a horse to know its Performance Profile @ RWITC</div>

                        <div class="subHeading">Click on the Dam to get her progeny details</div>

                    </div>

               </div>';

        echo "<table class='contentTable'>";

            //echo "<th class='thwhite' colspan='5'><h3>$trainerName</h3></th>";

		    //echo "<tr>"; 

			//echo "<th class='thwhite' colspan='5'>Click on a horse to know its Performance Profile @ RWITC</th>";

		    //echo "<tr>"; 

			//echo "<th class='thwhite' colspan='5'>Click on the Dam to get her progeny details</th>";

		    echo "<tr>"; 

            echo "<th>Sr. No.</th>";

            echo "<th>Horse</th>";

            echo "<th>Rating</th>";

            echo "<th>Description</th>";

            echo "<th>Sire-Dam</th>";

            echo "<th>Ownership</th>";

        echo "</tr>";    

        $i=1;                                     

        foreach ($trainerHorseList as $horse) {

          if ($horse['RATING'] == '') {

			$horse['RATING']= "NR";

		}

        $damNatDisp ='';

        if ($horse['DAMNAT'] != '') {

          $damNatDisp = "[" . $horse['DAMNAT'] . "]";  

        }

	    $ownership = $horse['OWNERSHIP'];

	    if (trim($horse['OWNERSHIP1']) != '') {

		$ownership .= $horse['OWNERSHIP1'];

	    } 	

	    if (trim($horse['OWNERSHIP2']) != '') {

		$ownership .= $horse['OWNERSHIP2'];

	    }

	    if (trim($horse['OWNERSHIP3']) != '') {

		$ownership .= $horse['OWNERSHIP3'];

	    }

            echo "<tr>";

                echo "<td width='7%'>$i</td>";

                echo "<td class='alignLeft'><a href='performanceProfile.php?q=get-profile&as_values={$horse['HORSENM']}&horseseq={$horse['HORSESEQ']}'>{$horse['HORSENM']}</a></td>";
                
                echo "<td>".$horse['RATING']."</td>";

                echo "<td>{$horse['COLOR']} {$horse['SEX']} {$horse['AGE']}</td>";

                echo "<td class='alignLeft'>{$horse['SIRE']}-<a href='foalRecords.php?mareName=".urlencode($horse['DAM'])."&damnat={$horse['DAMNAT']}'>".htmlentities($horse['DAM'])." {$damNatDisp}</a></td>";

                echo "<td class='alignLeft'>{$ownership}</td>";

            echo "</tr>";

            $i++;

        }

        echo "</table>";

    }

	?>

</div>

<?php                    

$design->closeDiv();

  $design->writeLeftPanel();

  $design->closeDiv();

    $design->endPage();

$design = NULL; // release object

?>