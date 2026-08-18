<?php

require_once("bootstrap.php");

require_once("lib/race.class.php");



$raceObj = new Racedata($db);



$date = date('Y-m-d');





  $pageTitle ="RWITC | ".CURRENT_SEASON ." - Horses In Training - $date";     

  $design = new Design();

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

</style>

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

                     echo "<th class='darkGrey alignLeft'><span class='srNumber'>".($i+1)."</span><a href='/horsesInTraining.php?trainer=".urlencode($trainersList[$i])."'>{$trainersList[$i]}</a></th>";

                    }

                    else { 

                     echo "<td class='alignLeft'><span class='srNumber'>".($i+1)."</span><a href='/horsesInTraining.php?trainer=".urlencode($trainersList[$i])."'>{$trainersList[$i]}</a></td>";  

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

                echo "<td class='alignLeft'><a href='/performanceProfile.php?q=get-profile&as_values={$horse['HORSENM']}&horseseq={$horse['HORSESEQ']}'>{$horse['HORSENM']}</a></td>";

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

<?php                    

$design->closeDiv();

  $design->rightArea();  

  $design->closeDiv();

  $design->closeDiv();

    $design->endPage();

$design = NULL; // release object

?>