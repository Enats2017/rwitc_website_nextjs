<?php

error_reporting(0);

ini_set("display_errors", 0);

require_once("bootstrap.php");

require_once("lib/race.class.php");

require_once('lib/videos.class.php');

$videos = new Videos($db);            

$raceObj = new Racedata($db);

$horseseq = getParameterNumber('horseseq',0);

//$searchString = getParameterString('searchstring','',$db);

$q = getParameterString('q','',$db);

$horseDetails = array();

$pageTitle ="RWITC | ".CURRENT_SEASON ." - Performance Profile of Horses";    

  $design = new Design();

   $design->js='

    <script type="text/javascript" src="/js/jquery.autoSuggest.js"></script>

  ';

  $design->css = "

  <link type='text/css' href='/css/autoSuggest.css' rel='stylesheet' />    
  <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css'>
  <style type='text/css'>
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

  .pp-title {
      font-size: 24px;
      color: #2b332f;
      margin: 0 0 20px 0;
      display: flex;
      align-items: center;
      gap: 12px;
  }
  .pp-title i {
      color: #0f5c33;
  }

  .pp-search-card {
      background: #fff;
      border: 1px solid #e2e6e4;
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 24px;
      box-shadow: 0 1px 2px rgba(0,0,0,0.03);
  }
  .pp-search-card .contentTable { width: 100%; }
  .pp-search-card input[type='text'] {
      border: 1px solid #e2e6e4;
      border-radius: 8px;
      padding: 9px 12px;
      font-size: 14px;
      color: #2b332f;
      width: 100%;
      box-sizing: border-box;
  }
  .pp-search-card input[type='text']:focus { outline: none; border-color: #1a7a45; }
  .pp-search-card input[type='submit'] {
      background: #0f5c33;
      color: #fff;
      border: none;
      padding: 9px 20px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 14px;
      font-weight: 600;
  }
  .pp-search-card input[type='submit']:hover { background: #0c4a29; }

  .pp-results-card {
      background: #fff;
      border: 1px solid #e2e6e4;
      border-radius: 12px;
      padding: 20px;
      margin-bottom: 24px;
      box-shadow: 0 1px 2px rgba(0,0,0,0.03);
      overflow-x: auto;
  }

  @media (max-width: 700px) {
      #leftArea.col-lg-9 { padding: 0 16px; }
      .pp-search-card, .pp-results-card { padding: 14px; }
  }
  </style>

  ";

  $design->jqueryJs = "

    $(\"#horsename\").autoSuggest('/fetchHorses.php', {

                            selectionLimit: 1,

                            startText: \"Enter Horsename Here\",

                            retrieveLimit: 10                                

                            });

  ";

  $design->startPage("$pageTitle");

  $design->writeLogoTickerMenu();

  $design->openDiv("contentWrapper");

  $design->openDiv("infoWrapper","col-lg-12");

  $design->openDiv("leftArea",'col-lg-9');

  if ($q == "get-profile") {

    try {  

        $hrseName =  addslashes(preg_replace("/\+/"," ",getParameterString('as_values'))); 

        

        $horseseq = $raceObj->getHorseseqFromName($hrseName);    

        

        $newHorseDetails = $raceObj->getHorseDetailsByHorseseq($horseseq);

        $oldHorseDetails = $raceObj->getOldHorseDetailsByHorseseq($horseseq);

        

        // echo '<pre>';

        // print_r($newHorseDetails);

        // echo '<br />';

        // echo '<pre>';

        // print_r($oldHorseDetails);

        // echo '<br />';

        // exit;



        $horseDetails = array_merge($newHorseDetails,$oldHorseDetails);    

    } catch (Exception $err) {

        // handle catch

    }

  }

  /*

    if ($searchString !== "") {      

      $horseList = $raceObj->searchHorseName($searchString);

    }

    */

?>

    <h1 class="pp-title"><i class="fas fa-horse-head"></i> Performance Profile @ RWITC</h1>

<div class="pp-search-card">
<form name='horseSearch' method="POST" action="performanceProfile.php?q=get-profile">

<table class="contentTable">

    <tr>

        <th>Search Horse</th>

        <td class="alignLeft">

            <input type='text'id="horsename" name='searchstring' value="<?php echo $searchString; ?>" />&nbsp;

        </td>

        <td>

            <input type="submit" name="submit" value="submit" />

        </td>

    </tr>    

</table>

</form>
</div>

<?php /*if ($searchString !== "") { ?>

<table class="contentTable">

    <tr>

        <th colspan="4">List of Horses Containing <?php echo $searchString; ?></th>

    </tr>

    <?php 

        $noofItemsPerRow = 4;          

        for ($i=0;$i<count($horseList);$i++) {

            if ($i % $noofItemsPerRow==0 && $i!=count($horseList)) {

                echo "</tr>";

                echo "<tr>";

            }

            if ($i==count($horseList)) {

                echo "</tr>";                    

            }else { 

                if ($horseList == $horseList[$i]['HORSENM']) {

                 echo "<th class='darkGrey alignLeft'>".($i+1).". <a href='/performanceProfile.php?horseseq=".urlencode($horseList[$i]['HORSESEQ'])."'>{$horseList[$i]['HORSENM']}</a></th>";

                }

                else { 

                 echo "<td class='alignLeft'>".($i+1).". <a href='/performanceProfile.php?horseseq=".urlencode($horseList[$i]['HORSESEQ'])."'>{$horseList[$i]['HORSENM']}</a></td>";  

                }

             }           

        }

    ?>

    

</table>

<?php } */?>

<div class="pp-results-card">
<?php if ($horseseq && count($horseDetails)>0){?>

    <table class="contentTable">



        <tr>

            <th class='thwhite' colspan="11"><h3><u><?php echo $horseDetails[0]['HORSENM']; ?></u> </h3></th>

        </tr>

        <tr>    

            <th class='thwhite' colspan="11">(<?php echo $horseDetails[0]['SIRE']; ?> - <?php echo $horseDetails[0]['DAM']; ?> <?php echo ($horseDetails[0]['DAMNAT'] != '')? '['.$horseDetails[0]['DAMNAT'].']': ''; ?>)</th>

            

        </tr>

        <?php

          /*echo "<pre>";

          print_r($horseDetails);

          echo "<pre>";  */

         if ($horseDetails[0]['LISCENCE'] == "A" || $horseDetails[0]['LISCENCE'] == "B") {?>

        <tr>    

            <th class='thwhite' colspan="9">Trainer: <?php echo $horseDetails[0]['TRAINERNM']; ?></th>

        </tr>

        <?php } ?>

        <tr>

            <th>VENUE</th>            

            <th>DATE</th>

            <th>RACENO</th>

            <th>JOCKEY</th>

            <th>CLASS</th>

            <th>DISTANCE</th>

            <th>WEIGHT</th>

            <th>PLACING</th>

            <th>TIME</th>

            <th>STAKES</th>

            <th>VIDEO</th>

        </tr>

        <?php 

            $runs = $wins = $seconds = $thirds = $stakes =  $dq =  0;

            foreach ($horseDetails as $horse) {                

                echo "<tr>";

                    echo "<td>{$horse['VENUE']}</td>";                    

                    echo "<td>".date("d-m-Y",strtotime($horse['RACEDATE']))."</td>";                    

                    echo "<td><a target='_blank' href='performanceProfile.php?q=result&horseseq=$horseseq&raceno={$horse['RACENO']}&racedate=".date("Y-m-d",strtotime($horse['RACEDATE']))."'>{$horse['RACENO']}</a></td>";                    

                    echo "<td>{$horse['JOCKEYNM']}</td>"; 

                    echo "<td>{$horse['RACECAT']}</td>"; 

                    echo "<td>{$horse['DISTANCE']} Mtrs</td>";

                    echo "<td>{$horse['WEIGHTCD']}</td>";

                    switch ($horse['PLACING']) {

                                        case 0:

                                            $horse['PLACING'] ='-';

                                            break;                                        

                                        case ($horse['PLACING']> 0 && $horse['PLACING'] <= 24):

                                            break;

                                        case 55:

                                            $horse['PLACING'] = 'NDS';

                                            break;

                                        case 56:

                                            $horse['PLACING'] = 'NS';

                                            break;

                                        case 57:

                                            $horse['PLACING'] = 'NPR';

                                            break;

                                        case 58:

                                            $horse['PLACING'] = 'WD';

                                            break;

                                        case 59:

                                            $horse['PLACING'] = 'BO';

                                            break;

                                        case 60:

                                            $horse['PLACING'] = 'DQ';

                                            break; 

                                        case 61:

                                            $horse['PLACING'] = '-';

                                            break; 

                                    }

                     if (($horse['PLACING'] > 0 && $horse['PLACING'] <= 24) || $horse['PLACING'] == 60) {

                         $runs++;

                     }          

                     if ($horse['PLACING'] == 1) {

                         $wins++;

                     }

                     if ($horse['PLACING'] == 2) {

                         $seconds++;

                     }

                     if ($horse['PLACING'] == 3) {

                         $thirds++;

                     }         

                    echo "<td>{$horse['PLACING']}</td>";                    

                    echo "<td>{$horse['TIMINGMTS']}:{$horse['TIMINGSEC']}:{$horse['TIMINGSECD']}</td>";

                    if ($horse['STAKES'] == 0)  {

                        echo "<td>{$horse['PLASTAKES']}</td>";                    

                        $stakes += $horse['PLASTAKES'];

                    } else { 

                        echo "<td>{$horse['STAKES']}</td>";                    

                        $stakes += $horse['STAKES'];

                    }

                    echo "<td>";

                        try {

                            if (strtotime($horse['RACEDATE']) < strtotime('2015-07-23')) {

                                //$videos = new Videos($db);

                                $data = $videos->getVideoDataByDate($horse['RACEDATE']);

                                /*echo '<pre>';

                                print_r($data);

                                echo '</pre>';

                                */

                                if (isset($data['chan']) && $data['chan'] != '') {

                                 $videoLink =  "<a href=\"http://rwitc.ext.switchmedia.asia/index.php?raceno={$horse['RACENO']}&chan={$data['chan']}&cat={$data['cat']}\"><img src='/images/videoPlay.png' alt='Video' /></a>";

                                  echo $videoLink;

                                }

                            } else {

                                $videoLink =  "<a href=\"https://www.rwitcraces.com/RaceArchives.aspx?d=".date('dmY',strtotime($horse['RACEDATE']))."&rno=".$horse['DAYRACENO']."\"><img src='/images/videoPlay.png' alt='Video' /></a>";

                              echo $videoLink;

                            }

                            

                            //$data = $videos->getVideoDataByDate($horse['RACEDATE']);

                            //echo "<a href=\"http://rwitc.ext.switchmedia.asia/index.php?raceno={$horse['RACENO']}&chan={$data['chan']}&cat={$data['cat']}\"><img src='/images/videoPlay.png' alt='Video' /></a>";

                        }catch (Exception $err) {

                            echo "-";

                        }  

                        

                    echo "</td>";

                echo "</tr>";

            }    

        ?>

    </table>

    <br />

    <table class="contentTable">

        <tr>

            <th colspan="5" class="thwhite">Runs Data for <?php echo $horseDetails[0]['HORSENM']; ?></th>

        </tr>

        <tr>

            <th>Runs</th>

            <th>Wins</th>

            <th>Second</th>

            <th>Third</th>

            <th>Total Stakes</th>

        </tr>

        <tr>

            <td><?php echo $runs; ?></td>

            <td><?php echo $wins; ?></td>

            <td><?php echo $seconds; ?></td>

            <td><?php echo $thirds; ?></td>

            <td><?php echo $stakes; ?></td>

        </tr>

    </table>

<?php } else {

    if ($q == "get-profile") {

        echo "<table class='contentTable'>";

            echo "<tr>";

                echo "<th class='thwhite'><h3>No Records found for the selected horse</h3></th>";

            echo "</tr>";

        echo "</table>";

    }

} ?>

<?php if ($q == "result") {

      $raceno = getParameterNumber('raceno',0);

      $racedate = getParameterString('racedate','',$db);

      $raceDetails = $raceObj->getHorseDetailsByDateAndRaceNo($racedate,$raceno);      



      if (count($raceDetails)==0) {

        $raceDetails = $raceObj->getOldHorseDetailsByDateAndRaceNo($racedate,$raceno);

      }

      $displayDate = date("d-m-Y",strtotime($racedate));     

      echo <<< TABLE

      <br />

        <table class="contentTable">

            <tr>

                <th colspan="9" class="thwhite">Race Results for Race No. $raceno run on $displayDate </th>

            </tr>

TABLE;

      try {

        $raceNameDetails = $raceObj->getRaceName($racedate,$raceno);
        // echo '<pre>';
        // print_r($raceNameDetails);exit;

        echo "

                <tr>

                <th colspan=\"9\" class=\"darkGrey\">

                    {$raceNameDetails['RACENAME']}<br />

                    {$raceNameDetails['RACETERM']}<br />

                    Distance{$raceNameDetails['DISTANCE']}

                </th>

            </tr>

             ";

      } catch (Exception $err) {

      

      }

            

                                    

echo <<< TABLE

            <tr>

                <th>Placing</th>

                <th>Horse</th>

                <th>Wt</th>

                <th>Length</th>

                <th>Trainer</th>

                <th>Jockey</th>

                <th>Odds</th>

                <th>Time</th>

                <th>Horse Wt</th>                

            </tr>                  

TABLE;

        $voidrace=false;

        foreach ($raceDetails as $raceResult) {
            

            echo "<tr>";

            switch ($raceResult['PLACING']) {

                case 0:

                    $raceResult['PLACING'] ='-';

                    break;

                case ($raceResult['PLACING']> 0 && $raceResult['PLACING'] <= 24):

                    break;

                case 55:

                    $raceResult['PLACING'] = 'NDS';

                    break;

                case 56:

                    $raceResult['PLACING'] = 'NS';

                    break;

                case 57:

                    $raceResult['PLACING'] = 'NPR';

                    break;

                case 58:

                    $raceResult['PLACING'] = 'WD';

                    break;

                case 59:

                    $raceResult['PLACING'] = 'BO';

                    break;

                case 60:

                    $raceResult['PLACING'] = 'DQ';

                    break; 

                case 61:

                    $raceResult['PLACING'] = '-';

                    break;

                case 91:

                    $raceResult['PLACING'] = '-';

                    $voidrace = true;

                    break;

            }

            echo "<td>{$raceResult['PLACING']}</td>";                                                           

            echo "<td>

                <a href='performanceProfile.php?q=get-profile&as_values=".urlencode($raceResult['HORSENM'])."&horseseq={$raceResult['HORSESEQ']}'>{$raceResult['HORSENM']}</a><br />

                <span style='font-size:10px;'>({$raceResult['SIRE']}-{$raceResult['DAM']})</span>

            </td>"; 

            if ($raceResult['WEIGHTCD'] == 0) {

                 echo "<td>-</td>";

            } else {                                                                                            

                echo "<td>{$raceResult['WEIGHTCD']}</td>";

            }

            $length = "";

            switch ($raceResult['LENGTH']) {

              case 0: 

                break;             

              case 20: 

                $length = "DH";

                break;

              case 30: 

                $length = "Shd";

                break;    

              case 40: 

                $length = "Hd";

                break;

              case 50: 

                $length = "nk";

                break;

              case 60:

                $length .= "NO" . ", ";

                break;

              case 70:

                $length .= "SN" . ", ";

                break;

              case 80:

                $length .= "LN" . ", ";

                break;   

              case 90: 

                $length = "Dist";

                break;

              default:

                $length = convertDecimalToFractionString($raceResult['LENGTH']);

                break;    

            }

            echo "<td>$length</td>";

            echo "<td>{$raceResult['TRAINERNME']}</td>";

            echo "<td>{$raceResult['JOCKEYNM']}</td>";

            

            if ($raceResult['BKM1ODDS'] == 0 && $raceResult['BKM2ODDS'] == 0) {
                

                echo "<td>--</td>"; 

            }  else {

                echo "<td>{$raceResult['BKM1ODDS']}/{$raceResult['BKM2ODDS']}</td>";     

            }  
                                                                   

            If ($raceResult['TIMINGMTS']== 0 && $raceResult['TIMINGSEC']==0 && $raceResult['TIMINGSECD']==0) {

                    echo "<td>-</td>"; 

            } else {

                if ($raceResult['TIMINGSEC'] < 10 )

                    $raceResult['TIMINGSEC'] = "0".$raceResult['TIMINGSEC'];

                if ($raceResult['TIMINGSECD'] < 10 )

                    $raceResult['TIMINGSECD'] = "0".$raceResult['TIMINGSECD'];                                        

                echo "<td>{$raceResult['TIMINGMTS']}:{$raceResult['TIMINGSEC']}:{$raceResult['TIMINGSECD']}</td>";                                             

            }

             if ($raceResult['HORSEWT'] <100)

                $raceResult['HORSEWT'] = "NR";

             echo "<td>{$raceResult['HORSEWT']}</td>";             

        echo "</tr>"; 

        }

        if ($voidrace) {

            echo '<tr>';

                echo '<td colspan="9"><b>This race has been declared Null & Void</b></td>';

            echo '</tr>';

        }

        echo "</table>";

} ?>
</div>

<?php                    

$design->closeDiv();

  $design->writeLeftPanel();

  $design->closeDiv();

    $design->endPage();

$design = NULL; // release object