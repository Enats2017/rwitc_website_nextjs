<?php
 require_once("bootstrap.php");
require_once("lib/race.class.php");
require_once('lib/videos.class.php');
error_reporting(E_ALL);
$raceObj = new Racedata($db);
$date = getParameterString('date','',$db);
$searaceno = getParameterNumber('raceno',0);
$videoLink = '';
if (strtotime($date) < strtotime('2015-07-23')) {
    $videos = new Videos($db); 
    $data = $videos->getVideoDataByDate($date);
    $videoLink =  "<a href=\"http://www.mumbairaces.com/index.php?chan={$data['chan']}&cat={$data['cat']}\">Video</a>";
} else {
    $videoLink =  "<a href=\"https://www.rwitcraces.com/RaceArchives.aspx?d=".date('dmY',strtotime($date))."\">Video</a>";
}

//$videoLink = '';
if ($date !== "") {    
    $prospectFDeclJoinData = $raceObj->getProspectFDeclDataJoinOnSrnoLinkGroupedRaceno($date);
}
if ($searaceno>0) {
    //$raceResults = $raceObj->getHmasterDataJoinfHorse5ByRaceNo($searaceno);
    try {
        $date = $raceObj->getDateByRacenosea($searaceno);
    } catch (Exception $err) {
        $err = 1;
        $msg = 'Could not find results for Race No. '.$searaceno;
    }
     $prospectFDeclJoinData = $raceObj->getProspectFDeclDataJoinProspectByRacenoAndDate($searaceno,$date);   
}
$dayNarr = $raceObj->getDaynarrFromProspectByDate($date);
//print_r($prospectFDeclJoinData);
// echo $dayNarr;exit;
$pageTitle ='RWITC - Race Results';        
$design = new Design();
   
$design->startPage("$pageTitle");
$design->writeLogoTickerMenu();
$design->openDiv("contentWrapper");
$design->openDiv("infoWrapper","col-lg-12");
$design->openDiv("leftArea",'col-lg-9');
preg_match('/\d\d\d(\d)-(\d\d)-(\d\d)/',$date,$matchDate);
$fileDate = $matchDate[3].$matchDate[2].$matchDate[1];
$file = DOWNLOADFILE_BASE."/RES$fileDate.HTM";
?>

    <style type="text/css">
        .infoTable td {padding: 1px; }
    </style>         
    <?php if (!$err) {?>
                    <!--<table class="contentTable">
                        <tr>
                            <th colspan="3" class="thwhite" style='text-align:right;'><a href='<?php echo $file; ?>'>Download Results</a></td>
                        <tr>
                            <th class="thwhite"><?php echo CLUB_NAME; ?></th>
                        </tr>
                        <tr>
                            <th class="thwhite"><?php echo $dayNarr . ", " . date("l jS F Y",$prospectFDeclJoinData[0]['DATE']); ?></th>
                        </tr>
                        <tr>
                            <th class="thwhite"><h3><u>RACE RESULTS</u></h3></th>
                        </tr>
						<tr>
                            <th class="thwhite">Click on a horse to know its Performance Profile @ RWITC</th>
                        </tr>
						<tr>
                            <th class="thwhite">Click on the Dam to get her progeny details</th>
                        </tr>
                    <table>-->
                    <div class="pageHeader">

                        <div class="pageHeading">
                            <div class="subHeading"><a class="download" href='<?php echo $file; ?>'>Download Results</a></div>
                            <div class="subHeading"><?php echo CLUB_NAME; ?></div>
                            <div class="subHeading"><?php echo $dayNarr . ", " . date("l jS F Y",$prospectFDeclJoinData[0]['DATE']); ?></div>
                            <h3 class="clearfix"> RACE RESULTS</h3>
                            <div class="subHeading">Click on a horse to know its Performance Profile @ RWITC</div>
                            <div class="subHeading">Click on the Dam to get her progeny details</div>
                        </div>

                    </div>
                    <br />
                    <?php 
                        if ($searaceno == 0) {
                            try {                            
                                $scaletopInfo = $raceObj->getScaleTopInfoByDate($date);
                            } catch (Exception $err) {
                                 $scaletopInfo = array();
                            }
                    ?>
                        <?php if (count($scaletopInfo)>0) { ?>
                            <table class="contentTable">
                                <tr>
                                    <th>WEATHER</th>
                                    <td class="alignLeft"><?php echo $scaletopInfo['WEATHER']; ?></td>
                                </tr>
                                <tr>
                                    <th>PENETROMETER READING</th>
                                    <td class="alignLeft"><?php echo $scaletopInfo['PENITROM']; ?></td>
                                </tr>
                                <tr>
                                    <th>FALSE RAILS</th>
                                    <td class="alignLeft"><?php echo $scaletopInfo['FALSERAILS'].$scaletopInfo['OTHER']; ?></td>
                                </tr>
                            </table>
                        <?php } ?>
                    <?php } ?>
                    <br />
                        <?php
                        $i=0;
                        foreach ($prospectFDeclJoinData as $prospect) {
				
                        	$raceResults = $raceObj->getHmasterDataJoinfHorse5ByDateAndRaceNo($date,$prospect['RACENO_SEA']);    
                        	if (count($raceResults) || ($date == "2014-11-23" && $prospect['RACENO'] == "8") || ($date == "2015-10-11" && $prospect['RACENO'] == "10")) {                                            
                            $division = '';
                            switch ($prospect['DIV']) {
                                case 0:
                                    $division = '';
                                    break;
                                case 1:
                                    $division = '- Division I';
                                    break;
                                case 2;
                                    $division = '- Division II';
                                    break;
                                case 3:
                                    $division = "- Division III";
                                    break;                
                            }
                            echo "<table class='contentTable'>";
                            echo "<col width='50' /><col width='150' /><col width='50' /><col width='125' /><col width='125' /><col width='50' /><col width='100' /><col width='50' />";
                            echo "<tr>";
                                echo "<th width='8%'>";
                                    echo "
                                        No.: {$prospect['RACENO_SEA']}</span>";
                                echo "</th>";
                                echo "<th class='darkGrey' colspan='6' rowspan='2'>";
                                    echo "
                                        <span style='text-align:center;'>{$prospect['RACENAME']} {$division}</span>
                                        <span style='text-align:center;'>{$prospect['NARRENT']}</span><br />
                                        <span style='text-align:center;'>Time: {$prospect['RTIME']}</span><br />
                                        
                                        ";
                                    echo "(About)  " . "{$prospect['DISTANCE']} Metres. "; 
                                echo "</th>";
                                echo "<th rowspan='2'>{$videoLink}</th>";                                
                              echo "</tr>";   
                                echo "<tr>";
                                    echo "<th width='8%'>{$prospect['RACENO']}</th>";
                                echo "</tr>";                                
                                
                            
                            
                            echo "<tr>";
                                echo "<th>Placing</th>";
                                echo "<th>Horse</th>";
                                echo "<th>Wt</th>";
                                echo "<th>Jockey</th>";
                                echo "<th>Trainer</th>";
                                echo "<th>Odds</th>";
                                echo "<th>Time</th>";
                                echo "<th>Horse Wt</th>";
                            echo "</tr>";
                            $length = '';
                            $cardNoResults=$toteFav='';
                            $ownership = $breeder = "";
                            $nullPlacing = 0;
                            $showVoidRace = false;
                            $in = 0;
                            foreach ($raceResults as $raceResult) { 
                                // find ownership and breeder of the winning horse (PLACING=1)
                                if  ($raceResult['PLACING'] ==1) {
                                    $ownership .= $raceResult['FINALNAME'] ."<br />";                                    
                                    $breeder .= $raceResult['BREEDER'] ."<br />";                                    
                                }
                                /*if  ($raceResult['PLACING'] ==91) {
                                    $showVoidRace = true; 
                                }*/
                                /*if (!$checkVoidRace) {
                                   $showVoidRace = true; 
                                }*/
                                $in = 0;                              
                                echo "<tr>";
                                    switch ($raceResult['PLACING']) {
                                        case 0:
                                            $raceResult['PLACING'] ='-';
                                            $nullPlacing++;
                                            break;
                                        case ($raceResult['PLACING']> 0 && $raceResult['PLACING'] <= 24):
                                            break;
                                        case 55:
                                            $in = 1;
                                            $raceResult['PLACING'] = 'NDS';
                                            break;
                                        case 56:
                                            $in = 1;
                                            $raceResult['PLACING'] = 'NS';
                                            break;
                                        case 57:
                                            $in = 1;
                                            $raceResult['PLACING'] = 'NPR';
                                            //$raceResult['PLACING'] = 'WDR';                                            
                                            break;
                                        case 58:
                                            $in = 1;
                                            $raceResult['PLACING'] = 'WD';                                             
                                            break;
                                        case 59:
                                            $in = 1;
                                            $raceResult['PLACING'] = 'BO';                                            
                                            break;
                                        case 60:
                                            $in = 1;
                                            $raceResult['PLACING'] = 'DQ';                                            
                                            break; 
                                        case 61:
                                            $in = 1;
                                            $raceResult['PLACING'] = 'DNC';                                            
                                            break;
					                    case 62:
                                            $in = 1;
                                            $raceResult['PLACING'] = 'NPR';                                            
                                            break; 
                                        case 91:
                                            // race is null void
                                            $raceResult['PLACING'] = '-';                                            
                                            $showVoidRace = true;
                                            break;
                                    }
                                    echo "<td>{$raceResult['PLACING']}</td>";                                                           
                                    echo "<td class='alignLeft'><a href='/performanceProfile.php?q=get-profile&as_values={$raceResult['HORSENAME']}&horseseq={$raceResult['HORSESEQ']}'>
                                        {$raceResult['HORSENAME']}</a><br />
                                        <span>({$raceResult['SIRE']}-<a href='foalRecords.php?mareName={$raceResult['DAM']}')>{$raceResult['DAM']})</a></span>
                                    </td>"; 
                                    if ($raceResult['WEIGHTCD'] == 0) {
                                         echo "<td>-</td>";
                                    } else {                                                                                            
                                        echo "<td>{$raceResult['WEIGHTCD']}</td>";
                                    }
                                    try {                                                                                   
                                        $jockeyDets = $raceObj->getJockeynameAndAllowanceByJockey($raceResult['JOCKEY']);   
                                        $jockey = $jockeyDets["JOCKEYNM"];
                                        $allowance = $raceResult["CATEGORY"];
                                    } catch (Exception $err) {
                                       $jockey = "-"; 
                                       $allowance = "";
                                    }
                                    
                                    switch ($allowance) {
                                        case "A":
                                            $jockey .= " - 5";
                                            break;
                                        case "B":
                                            $jockey .= " - 3.5";
                                            break;
                                        case "C":
                                            $jockey .= " - 2.5";
                                            break;
                                        case "D":
                                            $jockey .= " - 1.5";
                                            break;
                                    }
                                    echo "<td class='alignLeft'>$jockey</td>";                                                           
                                    echo "<td class='alignLeft'>{$raceResult['TRAINERNM']}</td>"; 
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
                                        if ($raceResult['TIMINGSECD'] < 10 ) {
                                            $raceResult['TIMINGSECD'] = "00".$raceResult['TIMINGSECD'];                                        
                                        } elseif ($raceResult['TIMINGSECD'] < 100 && $raceResult['TIMINGSECD'] > 9) {
                                            $raceResult['TIMINGSECD'] = "0".$raceResult['TIMINGSECD']; 
                                        }
                                        echo "<td>{$raceResult['TIMINGMTS']}:{$raceResult['TIMINGSEC']}:{$raceResult['TIMINGSECD']}</td>";                                             
                                    }
                                     if ($raceResult['HORSEWT'] <100)
                                        $raceResult['HORSEWT'] = "NR";
                                     echo "<td>{$raceResult['HORSEWT']}</td>";
                                echo "</tr>";
                                
                                switch ($raceResult['LENGTH']) {
                                  case 0: 
                                    break;             
                                  case 20: 
                                    $length .= "DH" . ", ";
                                    break;
                                  case 30: 
                                    $length .= "Shd" . ", ";
                                    break;    
                                  case 40: 
                                    $length .= "Hd" . ", ";
                                    break;
                                  case 50: 
                                    $length .= "nk" . ", ";
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
                                    $length .= "Dist" . ", ";
                                    break;
                                  default:
                                    $length .= convertDecimalToFractionString($raceResult['LENGTH']) . ", ";
                                    break;    
                                }
                                if($in == 1){
                                    $cardNoResults .= $raceResult['CARDNO'].' '. $raceResult['PLACING'] .= "-";
                                } else {
                                    $cardNoResults .= $raceResult['CARDNO'] .="-";
                                }
                                if ($raceResult['CLASS']==1) {
                                    $toteFav = $raceResult['HORSENAME'];
                                }                        
                            }
                            if ($showVoidRace || 
                                ($date == "2012-11-22" && $prospect['RACENO'] == "1") || 
                                ($date == "2013-04-13" && $prospect['RACENO'] == "7")
                               ) {
                                 echo "<tr>";                                   
                                   echo "<td colspan='8' class='alignLeft' style='font-weight: bold; font-size:14px;text-align:center;'>This race has been declared Null & Void</td>";
                                 echo "</tr>";
                            } elseif (($date == "2014-11-23" && $prospect['RACENO'] == "8") || ($date == "2015-10-11" && $prospect['RACENO'] == "10")){
                                   echo "<tr>";                                   
                                   echo "<td colspan='8' class='alignLeft' style='font-weight: bold; font-size:14px;text-align:center;'>This race was cancelled.</td>";
                                 echo "</tr>";
                            } else {
                                echo "<tr>";
                                    echo "<th class='darkGrey' colspan='2'>Ownership</th>";                               
                                    echo "<td colspan='6' class='alignLeft'>$ownership</td>";
                                echo "</tr>";
                                echo "<tr>";
                                    echo "<th class='darkGrey' colspan='2'>Breeder</th>";                               
                                    echo "<td colspan='6' class='alignLeft'>$breeder</td>";
                                echo "</tr>";
                                echo "<tr>";
                                    echo "<th class='darkGrey' colspan='2'>Distance</th>";
                                    $length = substr_replace($length,"",-1,1);
                                    echo "<td colspan='6' class='alignLeft'>$length</td>";
                                echo "</tr>";
                                echo "<tr>";
                                    echo "<th class='darkGrey' colspan='2'>Results as per Card Nos</th>";
                                    $cardNoResults = substr_replace($cardNoResults,"",-1,1);
                                    echo "<td colspan='6' class='alignLeft'>$cardNoResults</td>";
                                echo "</tr>";
                                echo "<tr>";
                                   echo "<th class='darkGrey' colspan='2'>Tote Favourite</th>";
                                   echo "<td colspan='6' class='alignLeft'>$toteFav</td>";
                                 echo "</tr>";
                            }     
                             
                            
                            try {
                            // tote information from divsingl table
                            $toteInfo = $raceObj->getDivSinglInfoByDateAndRaceNo($date,$prospect['RACENO_SEA']);
                           
                            $totes = '';
                            $totes .= ($toteInfo['WIN'])?"<b>Win :</b> ".$toteInfo['WIN'] : "";
                            $totes .= ($toteInfo['WINA'])?", ".$toteInfo['WINA'] : "";
                            if ($toteInfo['PLA1'] || $toteInfo['PLA2'] || $toteInfo['PLA3'] || $toteInfo['PLA4']) {
                                $totes .= "&nbsp;<b>Place :</b> ";
                                $totes .= ($toteInfo['PLA1']) ? "{$toteInfo['PLA1']},":"";
                                $totes .= ($toteInfo['PLA2']) ? "{$toteInfo['PLA2']},":"";
                                $totes .= ($toteInfo['PLA3']) ? "{$toteInfo['PLA3']},":"";
                                $totes .= ($toteInfo['PLA4']) ? "{$toteInfo['PLA4']},":"";
                                $totes = substr_replace($totes,"",-1,1);
                            }
                            $totes .= ($toteInfo['SHP'])?"&nbsp;<b>SHP :</b> ".$toteInfo['SHP'] : "";
                            $totes .= ($toteInfo['FORD'])?"&nbsp;<b>Fc :</b> ".$toteInfo['FORD'] : "";
                            $totes .= ($toteInfo['FORC'])?"&nbsp;<b>Fc :</b> ".$toteInfo['FORC'] . ' (c/f)' : "";
                            
                            if ($toteInfo['QIND'] || $toteInfo['QINC']) {
                                 $totes .= "&nbsp;<b>Qn :</b> ";
                                 $totes .= ($toteInfo['QIND']) ? "{$toteInfo['QIND']},":"";
                                $totes .= ($toteInfo['QINC']) ? "{$toteInfo['QINC']} (c/f),":"";
                                $totes = substr_replace($totes,"",-1,1);
                            }
                            if ($toteInfo['TNLD1'] || $toteInfo['TNLD2'] || $toteInfo['TNLDC']) {
                                 $totes .= "&nbsp;<b>Tn :</b> ";
                                 $totes .= ($toteInfo['TNLD1']) ? " {$toteInfo['TNLD1']} &":"";
                                $totes .= ($toteInfo['TNLD2']) ? " {$toteInfo['TNLD2']} &":"";
                                $totes .= ($toteInfo['TNLDC']) ? " {$toteInfo['TNLDC']} (c/f) ":"";
                                $totes = substr_replace($totes,"",-1,2);
                            }
                            echo "<tr>";
                                echo "<th class='darkGrey' colspan='2'>Tote Dividends</th>";
                                echo "<td colspan='6' class='alignLeft'>$totes</td>";
                            echo "</tr>";
                            
                            echo "</table>";
                            $i++;
                            } catch (Exception $e) {
                                
                            }
							} // end if (count($raceResults))                            
                        }
                        if ($searaceno == 0) {
                            $divMultiInfo = $raceObj->getDivMultiInfoByRacedate($date);
                            
                            // SUPER JackPot Dividents, 6 Leg Races , Max 1 Jackpot
                            echo "<table class='contentTable'>";
                            echo "<col width='25%' /><col width='25%' /><col width='25%' /><col width='25%' />";                                    
                                 echo "<tr>";
                                        echo "<th colspan='4'>SUPER JACKPOT</th>";                                    
                                    echo "</tr>";
                                    echo "<tr>";
                                        echo "<th class='darkGrey'>Legs</th>";
                                        echo "<td colspan='3'>{$divMultiInfo['FLDSTR11']}</td>";
                                    echo "</tr>";
                                    echo "<tr>";
                                        echo "<th class='darkGrey'>Winners</th>";
                                        $raceNos = "{$divMultiInfo['UP0R1']},{$divMultiInfo['UP0R2']},{$divMultiInfo['UP0R3']},{$divMultiInfo['UP0R4']},{$divMultiInfo['UP0R5']},{$divMultiInfo['UP0R6']}";
                                        $winners = $raceObj->getWinningHorseNameByRacedateAndRaceNos($date,$raceNos);
                                        echo "<td colspan='3'>".join(", ",$winners)."</td>";                                     
                                    echo "</tr>";
                                   /*if ($divMultiInfo["UP0CF"] == 0) {    // not carried forward   
                                        echo "<tr>";
                                            echo "<th class='darkGrey'>70% Div</th>";
                                            if ($divMultiInfo['UP0D1'] == 0)                                           
                                                echo "<td>c/f</td>";
                                            else 
                                                echo "<td>{$divMultiInfo['UP0D1']}</td>";
                                            echo "<th class='darkGrey'>Tickets</th>";
                                            if ($divMultiInfo['UP0T1'] == 0) { 
                                                if ($divMultiInfo['UP0D1'] > 0) {
                                                    echo "<td>c/f</td>";
                                                }   else {                                       
                                                    echo "<td>-</td>";                                    
                                                }                             
                                            } else {
                                                echo "<td>{$divMultiInfo['UP0T1']}</td>";                                    
                                            }
                                        echo "</tr>";
                                        echo "<tr>";
                                            echo "<th class='darkGrey'>30% Div</th>";
                                            if ($divMultiInfo['UP0D2'] == 0)                                           
                                                echo "<td>c/f</td>";
                                            else    
                                                echo "<td>{$divMultiInfo['UP0D2']}</td>";
                                            echo "<th class='darkGrey'>Tickets</th>";
                                            if ($divMultiInfo['UP0T2'] == 0) {                                           
                                                if ($divMultiInfo['UP0D2'] > 0) {
                                                    echo "<td>c/f</td>";
                                                }   else {                                       
                                                    echo "<td>-</td>";                                    
                                                }                                                
                                            } else {
                                                echo "<td>{$divMultiInfo['UP0T2']}</td>";                                    
                                            }
                                        echo "</tr>";
                                   } else {      // carried forward   
                                         echo "<tr>";
                                             echo "<th class='darkGrey'>Carried Forward</th>";
                                             echo "<td colspan='3'>{$divMultiInfo["UP0CF"]}</th>";                                       
                                           echo "</tr>";
                                   }*/
                                   if ($divMultiInfo['UP0D1'] > 0) {
                                      echo "<tr>";
                                          echo "<th class='darkGrey'>70% Div</th>";
                                          echo "<td>{$divMultiInfo['UP0D1']}</td>";
                                          echo "<th class='darkGrey'>Tickets</th>";
                                          echo "<td>{$divMultiInfo['UP0T1']}</td>";
                                      echo "</tr>"; 
                                   }
                                   if ($divMultiInfo['UP0D2'] > 0) {
                                      echo "<tr>";
                                          echo "<th class='darkGrey'>30% Div</th>";
                                          echo "<td>{$divMultiInfo['UP0D2']}</td>";
                                          echo "<th class='darkGrey'>Tickets</th>";
                                          echo "<td>{$divMultiInfo['UP0T2']}</td>";
                                      echo "</tr>"; 
                                   }
                                   if ($divMultiInfo['UP0CF'] > 0) {
                                      echo "<tr>";
                                        echo "<th class='darkGrey'>Carried Forward</th>";
                                        echo "<td colspan='3'>{$divMultiInfo["UP0CF"]}</th>";                                       
                                      echo "</tr>"; 
                                   }
                            echo "</table>";
                            echo "<br />";
                            // Jackpot Dividends Table, 5 Legs Races, Max 2 JackPpots
                            echo "<table class='contentTable'>";
                            echo "<col width='25%' /><col width='25%' /><col width='25%' /><col width='25%' />";                                                               
                                if ($divMultiInfo['JP0D1'] ||$divMultiInfo['JP0D2'] || $divMultiInfo['JP0CF'] ) {
                                    echo "<tr>";
                                        echo "<th colspan='4'>JACKPOT</th>";                                    
                                    echo "</tr>";
                                    echo "<tr>";
                                        echo "<th class='darkGrey'>Legs</th>";
                                        echo "<td colspan='3'>{$divMultiInfo['FLDSTR1']}</td>";
                                    echo "</tr>";
                                    echo "<tr>";
                                        echo "<th class='darkGrey'>Winners</th>";
                                        $raceNos = "{$divMultiInfo['JP0R1']},{$divMultiInfo['JP0R2']},{$divMultiInfo['JP0R3']},{$divMultiInfo['JP0R4']},{$divMultiInfo['JP0R5']}";
                                        $winners = $raceObj->getWinningHorseNameByRacedateAndRaceNos($date,$raceNos);
                                        echo "<td colspan='3'>".join(", ",$winners)."</td>";                                     
                                    echo "</tr>";
                                    /*if ($divMultiInfo["JP0CF"] == 0) {    // not carried forward 
                                        echo "<tr>";
                                            echo "<th class='darkGrey'>70% Div</th>";
                                            if ($divMultiInfo['JP0D1']==0)
                                                echo "<td>c/f</td>";
                                            else    
                                                echo "<td>{$divMultiInfo['JP0D1']}</td>";
                                            echo "<th class='darkGrey'>Tickets</th>";
                                            if ($divMultiInfo['JP0T1']==0) {
                                                if ($divMultiInfo['JP0D1'] > 0) {
                                                    echo "<td>c/f</td>";
                                                }   else {                                       
                                                    echo "<td>-</td>";                                    
                                                } 
                                            } else { 
                                                echo "<td>{$divMultiInfo['JP0T1']}</td>";                                    
                                            }
                                        echo "</tr>";
                                        echo "<tr>";
                                            echo "<th class='darkGrey'>30% Div</th>";
                                            if ($divMultiInfo['JP0D2']==0)
                                                echo "<td>c/f</th>";
                                            else
                                                echo "<td>{$divMultiInfo['JP0D2']}</td>";
                                            echo "<th class='darkGrey'>Tickets</th>";
                                            if ($divMultiInfo['JP0T2']==0) {
                                                if ($divMultiInfo['JP0D2'] > 0) {
                                                    echo "<td>c/f</td>";
                                                }   else {                                       
                                                    echo "<td>-</td>";                                    
                                                }
                                            } else {
                                                echo "<td>{$divMultiInfo['JP0T2']}</td>";                                    
                                            }
                                        echo "</tr>";
                                    } else {  // carried forward
                                          echo "<tr>";
                                             echo "<th class='darkGrey'>Carried Forward</th>";
                                             echo "<td colspan='3'>{$divMultiInfo["JP0CF"]}</th>";                                       
                                           echo "</tr>"; 
                                    }*/
                                    if ($divMultiInfo['JP0D1'] > 0) {
                                      echo "<tr>";
                                          echo "<th class='darkGrey'>70% Div</th>";
                                          echo "<td>{$divMultiInfo['JP0D1']}</td>";
                                          echo "<th class='darkGrey'>Tickets</th>";
                                          echo "<td>{$divMultiInfo['JP0T1']}</td>";
                                      echo "</tr>"; 
                                   }
                                   if ($divMultiInfo['JP0D2'] > 0) {
                                      echo "<tr>";
                                          echo "<th class='darkGrey'>30% Div</th>";
                                          echo "<td>{$divMultiInfo['JP0D2']}</td>";
                                          echo "<th class='darkGrey'>Tickets</th>";
                                          echo "<td>{$divMultiInfo['JP0T2']}</td>";
                                      echo "</tr>"; 
                                   }
                                   if ($divMultiInfo['JP0CF'] > 0) {
                                      echo "<tr>";
                                        echo "<th class='darkGrey'>Carried Forward</th>";
                                        echo "<td colspan='3'>{$divMultiInfo["JP0CF"]}</th>";                                       
                                      echo "</tr>"; 
                                   }
                                } else {                               
                                    for ($i=1;$i<=2;$i++) {
                                        if ($i == 1) {
                                          $JackPot = "FIRST";
                                          $legs = 'FLDSTR2';
                                        }
                                        if ($i == 2) {
                                          $JackPot = "SECOND";
                                          $legs = 'FLDSTR3';
                                        }                                      
                                        echo "<tr>";                                    
                                            echo "<th colspan='4'>$JackPot JACKPOT</th>";
                                        echo "</tr>";
                                        echo "<tr>";
                                            echo "<th class='darkGrey'>Legs</th>";
                                            echo "<td colspan='3' >{$divMultiInfo[$legs]}</td>";                                    
                                        echo "</tr>";
                                        echo "<tr>";
                                            echo "<th class='darkGrey'>Winners</th>";
                                            $raceNos = "{$divMultiInfo["JP".$i."R1"]},{$divMultiInfo["JP".$i."R2"]},{$divMultiInfo["JP".$i."R3"]},{$divMultiInfo["JP".$i."R4"]},{$divMultiInfo["JP".$i."R5"]}";
                                            $winners = $raceObj->getWinningHorseNameByRacedateAndRaceNos($date,$raceNos);
                                           echo "<td colspan='3' style='font-size: 11px;'>".join(", ",$winners)."</td>";                                       
                                        echo "</tr>";
                                        // if not carried forward
                                       /*if ($divMultiInfo["JP".$i."CF"] == 0) {
                                        echo "<tr>";
                                            echo "<th class='darkGrey'>70% Div</th>";
                                            if ($divMultiInfo["JP".$i."D1"] == 0)
                                                echo "<td>c/f</td>";
                                            else
                                                echo "<td>{$divMultiInfo["JP".$i."D1"]}</td>";
                                            echo "<th class='darkGrey'>Tickets</th>";
                                            if ($divMultiInfo["JP".$i."T1"] == 0) {                      
                                                if ($divMultiInfo["JP".$i."D1"] > 0) {
                                                    echo "<td>c/f</td>";
                                                }   else {                                       
                                                    echo "<td>-</td>";                                    
                                                }
                                            }else {
                                                echo "<td>{$divMultiInfo["JP".$i."T1"]}</td>";                                     
                                            }
                                        echo "</tr>";  
                                        echo "<tr>";
                                            echo "<th class='darkGrey'>30% Div</th>";
                                            if ($divMultiInfo["JP".$i."D2"] == 0)
                                                echo "<td>c/f</td>";
                                            else
                                            echo "<td>{$divMultiInfo["JP".$i."D2"]}</td>";
                                            echo "<th class='darkGrey'>Tickets</th>";
                                            if ($divMultiInfo["JP".$i."T2"] == 0) {
                                                if ($divMultiInfo["JP".$i."D2"] > 0) {
                                                    echo "<td>c/f</td>";
                                                }   else {                                       
                                                    echo "<td>-</td>";                                    
                                                }
                                            } else {
                                                echo "<td>{$divMultiInfo["JP".$i."T2"]}</td>";                                     
                                            }
                                        echo "</tr>"; 
                                       } else {         // carried forward 
                                           echo "<tr>";
                                             echo "<th class='darkGrey'>Carried Forward</th>";
                                             echo "<td colspan='3'>{$divMultiInfo["JP".$i."CF"]}</th>";                                       
                                           echo "</tr>";  
                                       }*/
                                       if ($divMultiInfo["JP".$i."D1"] > 0) {
                                          echo "<tr>";
                                              echo "<th class='darkGrey'>70% Div</th>";
                                              echo "<td>{$divMultiInfo["JP".$i."D1"]}</td>";
                                              echo "<th class='darkGrey'>Tickets</th>";
                                              echo "<td>{$divMultiInfo["JP".$i."T1"]}</td>";
                                          echo "</tr>"; 
                                       }
                                       if ($divMultiInfo["JP".$i."D2"] > 0) {
                                          echo "<tr>";
                                              echo "<th class='darkGrey'>30% Div</th>";
                                              echo "<td>{$divMultiInfo["JP".$i."D2"]}</td>";
                                              echo "<th class='darkGrey'>Tickets</th>";
                                              echo "<td>{$divMultiInfo["JP".$i."T2"]}</td>";
                                          echo "</tr>"; 
                                       }
                                       if ($divMultiInfo["JP".$i."CF"] > 0) {
                                          echo "<tr>";
                                            echo "<th class='darkGrey'>Carried Forward</th>";
                                            echo "<td colspan='3'>{$divMultiInfo["JP".$i."CF"]}</th>";                                       
                                          echo "</tr>"; 
                                       }
                                    }
                                    
                                }
                            echo "</table>";
                            echo "<br />"; 
                            // Treble Dividends Table, 3 Legs Races, Max 3 Trebles                           
                            
                            echo "<table class='contentTable'>";
                            echo "<col width='25%' /><col width='25%' /><col width='25%' /><col width='25%' />";                                                               
                            if ($divMultiInfo['TR0D1'] || $divMultiInfo['TR0CF']) {    
                                    echo "<tr>";
                                        echo "<th colspan='4'>TREBLE</th>";                                    
                                    echo "</tr>";
                                    echo "<tr>";
                                        echo "<th class='darkGrey'>Legs</th>";
                                        echo "<td colspan='3'>{$divMultiInfo['FLDSTR4']}</td>";
                                    echo "</tr>";
                                    echo "<tr>";
                                        echo "<th class='darkGrey'>Winners</th>";
                                        $raceNos = "{$divMultiInfo['TR0R1']},{$divMultiInfo['TR0R2']},{$divMultiInfo['TR0R3']}";
                                        $winners = $raceObj->getWinningHorseNameByRacedateAndRaceNos($date,$raceNos);
                                        echo "<td colspan='3'>".join(", ",$winners)."</td>";                                     
                                    echo "</tr>";
                                    if ($divMultiInfo["TR0CF"] == 0) {    // not carried forward 
                                        echo "<tr>";
                                            echo "<th class='darkGrey'>Dividend</th>";
                                            echo "<td>{$divMultiInfo['TR0D1']}</th>";
                                            echo "<th class='darkGrey'>Tickets</th>";
                                            echo "<td>{$divMultiInfo['TR0T1']}</th>";                                    
                                        echo "</tr>";                                  
                                    } else {  // carried forward
                                          echo "<tr>";
                                             echo "<th class='darkGrey'>Carried Forward</th>";
                                             echo "<td colspan='3'>{$divMultiInfo["TR0CF"]}</th>";                                       
                                           echo "</tr>"; 
                                    }
                                } else {                               
                                	
                                    for ($i=1;$i<=3;$i++) {
                                        if ($i == 1) {
                                          $treble = "FIRST";
                                          $legs = 'FLDSTR5';
                                        }
                                        if ($i == 2) {
                                          $treble = "SECOND";
                                          $legs = 'FLDSTR6';
                                        }if ($i == 3) {
                                          $treble = "THIRD";
                                          $legs = 'FLDSTR7';
                                        }                                                                            
                                        if ($divMultiInfo["TR".$i."D1"] == 0 && $divMultiInfo["TR".$i."CF"] == 0 ) {
                                            continue;
                                        } else {   
                                            echo "<tr>";                                    
                                                echo "<th colspan='4'>$treble TREBLE</th>";
                                            echo "</tr>";
                                            echo "<tr>";
                                                echo "<th class='darkGrey'>Legs</th>";
                                                echo "<td colspan='3' >{$divMultiInfo[$legs]}</td>";                                    
                                            echo "</tr>";
                                            echo "<tr>";
                                                echo "<th class='darkGrey'>Winners</th>";
                                                $raceNos = "{$divMultiInfo["TR".$i."R1"]},{$divMultiInfo["TR".$i."R2"]},{$divMultiInfo["TR".$i."R3"]}";
                                                $winners = $raceObj->getWinningHorseNameByRacedateAndRaceNos($date,$raceNos);
                                               echo "<td colspan='3' style='font-size: 11px;'>".join(", ",$winners)."</td>";                                       
                                            echo "</tr>";
                                            // if not carried forward
                                           if ($divMultiInfo["TR".$i."CF"] == 0) {
                                            echo "<tr>";
                                                echo "<th class='darkGrey'>Dividend</th>";
                                                echo "<td>{$divMultiInfo["TR".$i."D1"]}</th>";
                                                echo "<th class='darkGrey'>Tickets</th>";
                                                echo "<td>{$divMultiInfo["TR".$i."T1"]}</th>";                                     
                                            echo "</tr>";                                     
                                           } else {         // carried forward 
                                               echo "<tr>";
                                                 echo "<th class='darkGrey'>Carried Forward</th>";
                                                 echo "<td colspan='3'>{$divMultiInfo["TR".$i."CF"]}</th>";                                       
                                               echo "</tr>";  
                                           }
                                        }
                                    }
                                    
                                }
                            echo "</table>";
                          /*  echo "<pre>";
                            print_r($divMultiInfo);
                            echo "</pre>";*/
                        }
    } elseif ($err) {
        echo "<Table class='contentTable'>";
            echo "<tr>";
                echo "<th class='thwhite'>$msg</th>"; 
            echo "</tr>";
        echo "</table>";
    }
  $design->closeDiv();
  $design->rightArea();  
  $design->closeDiv();
  $design->closeDiv();
    $design->endPage();
$design = NULL; // release object
