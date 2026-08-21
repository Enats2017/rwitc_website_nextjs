<?php 
include_once('../bootstrap.php');
include_once('../lib/race.class.php');
  
  $pageTitle ='Body weight of horses';        
  $design = new Design();
  $design->css ="
    <style type='text/css'>
        .weight { color: red; font-weight: bold; }
    </style>
  ";
  
   $raceObj = new Racedata($db);
   $q = getParameterString("q","");
   if ($q=="Search") {
       $horsename = getParameterString("horsename",""); 
       $horseBodyWeights = $raceObj->getHorseBodyWeightBySearch($horsename);
   } else {
        $horseBodyWeights = $raceObj->getHorseBodyWeight();
   }
  $design->startPage("$pageTitle");
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea",'col-lg-9');
  $design->writeContentPageStyles();
    //print_r($horseBodyWeight);
  ?>
<br /><br />
    <h2>Body Weight of Horses</h2>

    <form method="get" action="horsebodyWeight.php">
        <label>Search by Horse Name : </label><input type="text" name='horsename' />&nbsp;&nbsp;<input type="submit" name="q" value="Search"> 
    </form>       
    <br />
    <table class="contentTable">

        <tr>
            <th class="thwhite alignLeft" colspan="16">Body Weight of Horses are shown in (<span style='color:red;'>RED</span>)</th>
        </tr>
        <tr>
            <th>HORSE</th>
            <th>R1</th>
            <th>R2</th>
            <th>R3</th>
            <th>R4</th>
            <th>R5</th>
            <th>R6</th>
            <th>R7</th>
            <th>R8</th>
            <th>R9</th>
            <th>R10</th>
            <th>R11</th>
            <th>R12</th>
            <th>R13</th>
            <th>R14</th>
            <th>R15</th>
        </tr>
        <?php foreach ($horseBodyWeights as $weight) { ?> 
            <tr>
                <td class="alignLeft"><?php echo $weight['HORSENAME']; ?></td>
                <?php 
                    for ($i=1;$i<=15;$i++) { 
                        /*if ($weight["R$i"] == 0)
                            $weight["R$i"] = "";
                        if ($weight["W$i"] == 0)
                            $weight["W$i"] = "";*/
                        if ($weight["R$i"] == 0 && $weight["W$i"] == 0)    {
                            echo "<td>&nbsp;</td>";
                        } else{
                            if ($weight["W$i"] == 0) {
                                $weight["W$i"] = "NR";
                            }
                            echo "<td>{$weight["R$i"]}(<span class='weight'>{$weight["W$i"]}</span>)</td>";     
                        }
                    } 
                ?>
            </tr> 
        <?php } ?>
    </table>
<?php                   
  $design->closeDiv();
  $design->writeLeftPanel();
  $design->closeDiv();
  $design->closeDiv();
    $design->endPage();   
$design = NULL; // release object