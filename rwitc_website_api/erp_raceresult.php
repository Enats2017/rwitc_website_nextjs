<?php 

    include "header.php";

    include "bootstrap.php";

    include "config.php";

    if(isset($_GET['date'])){

      $date = $_GET['date'];

    }else{

      $date = '';

    }

    $refresh_in = 0;
    if(strtotime($date) == date('Y-m-d')){
        $refresh_in = 1;
    }


    if(isset($_GET['raceno'])){

      $searaceno = $_GET['raceno'];

    }else{

      $searaceno = '';

    }

	

	if (strtotime($date) <= strtotime('2022-10-14')) {

      $videoLink = '';

      if (strtotime($date) < strtotime('2015-07-23')) {

          $data = mysqli_fetch_assoc(mysqli_query($conn,"SELECT chan,cat FROM videos WHERE racedate='".$date."'"));

          $videoLink =  "<a href=\"http://www.mumbairaces.com/index.php?chan={$data['chan']}&cat={$data['cat']}\">Video</a>";

      } else {

          $videoLink =  "<a href=\"https://www.rwitcraces.com/RaceArchives.aspx?d=".date('dmY',strtotime($date))."\">Video</a>";

      }



      if ($date !== "") {

        $newArr = array();

          $newArr_sql = mysqli_query($conn,"SELECT UNIX_TIMESTAMP(p.`DATE`) as DATE, p.`SRNO`, p.`NAME` as RACENAME, p.`DAYNARR`, p.`NARRENT`, p.`DISTANCE`, p.`FJOCK`, p.`HTERMS`, p.`RACECAT`, p.`GRADE`, p.`RAISELOWER`, p.`RAISEACP1`, p.`RAISEACP2`, p.`RAISEACP3`, p.`RACETIME1`, p.`RACETIME2`, p.`VOID_HACP`, p.`VOID_ACCP`, f.`RACEDATE`, f.`NAME` as HORSENAME, f.`RACENO`, f.`WEIGHT`, f.`CARDNO`, f.`RTIME`, f.`DIV`, f.`LINK`, f.`TRAINER`, f.`JOCKEY`, f.`JOCKEYNM`, f.`CATEGORY`, f.`SHOE`, f.`SHOEDET`, f.`DRAWNO`, f.`HORSEWT`, f.`HORSESEQ`, f.`RATING`, f.`RACECAT`, f.`CENTRE`, f.`RACENO_SEA`, f.`DISTANCE`, f.`TRN_NM`, f.`HT`, f.`LATENAME`, f.`OWNCODE`, f.`BITSDET`, f.`SERIALNO` FROM prospect p INNER JOIN fdecl f ON p.SRNO=f.LINK WHERE p.DATE='".$date."' AND f.RACEDATE='".$date."'  ORDER BY RACENO ASC");

          if ($newArr_sql->num_rows > 0) {

              while ($row = mysqli_fetch_assoc($newArr_sql)) {

                  $newArr[] = $row; 

              }

          }

      }

      if ($searaceno>0) {

          try {

              $date = mysqli_fetch_assoc(mysqli_query($conn,"SELECT DISTINCT(RACEDATE) FROM fdecl f WHERE RACENO_SEA='".$searaceno."' "));

          } catch (Exception $err) {

              $err = 1;

              $msg = 'Could not find results for Race No. '.$searaceno;

          }

           $newArr = mysqli_query($conn,"SELECT UNIX_TIMESTAMP(p.`DATE`) as DATE, p.`SRNO`, p.`NAME` as RACENAME, p.`DAYNARR`, p.`NARRENT`, p.`DISTANCE`, p.`FJOCK`, p.`HTERMS`, p.`RACECAT`, p.`GRADE`, p.`RAISELOWER`, p.`RAISEACP1`, p.`RAISEACP2`, p.`RAISEACP3`, p.`RACETIME1`, p.`RACETIME2`, p.`VOID_HACP`, p.`VOID_ACCP`, f.`RACEDATE`, f.`NAME` as HORSENAME, f.`RACENO`, f.`WEIGHT`, f.`CARDNO`, f.`RTIME`, f.`DIV`, f.`LINK`, f.`TRAINER`, f.`JOCKEY`, f.`JOCKEYNM`, f.`CATEGORY`, f.`SHOE`, f.`SHOEDET`, f.`DRAWNO`, f.`HORSEWT`, f.`HORSESEQ`, f.`RATING`, f.`RACECAT`, f.`CENTRE`, f.`RACENO_SEA`, f.`DISTANCE`, f.`TRN_NM`, f.`HT`, f.`LATENAME`, f.`OWNCODE`, f.`BITSDET`, f.`SERIALNO` FROM prospect p INNER JOIN fdecl f ON p.SRNO=f.LINK WHERE f.`RACENO_SEA`='".$searaceno."' AND p.`DATE`='".$date."' GROUP BY f.RACENO ORDER BY f.RACENO ASC");

      }



      foreach ($newArr as $kkey => $kvalue) {

          $prospectFDeclJoinData[$kvalue['RACENO']] = $kvalue;   

      }



      $dayNarr = mysqli_fetch_assoc(mysqli_query($conn,"SELECT DAYNARR FROM prospect WHERE DATE='".$date."' AND DAYNARR <> '' LIMIT 1"));



      $pageTitle ='RWITC - Race Results';        



      preg_match('/\d\d\d(\d)-(\d\d)-(\d\d)/',$date,$matchDate);

      $fileDate = $matchDate[3].$matchDate[2].$matchDate[1];

      $file = "../".DOWNLOADFILE_BASE."/RES$fileDate.HTM";

    }

	

	$sql = "SELECT `value` from `config` where `id` = '2' ";

    $res = mysqli_query($conn,$sql);

    $data = mysqli_fetch_assoc($res);

?>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>



<style type="text/css">

    h1,

    h2,

    h3,

    h4,

    h5,

    h6 {}

    a,

    a:hover,

    a:focus,

    a:active {

    text-decoration: none;

    outline: none;

    }

    a,

    a:active,

    a:focus {

    color: #333;

    text-decoration: none;

    transition-timing-function: ease-in-out;

    -ms-transition-timing-function: ease-in-out;

    -moz-transition-timing-function: ease-in-out;

    -webkit-transition-timing-function: ease-in-out;

    -o-transition-timing-function: ease-in-out;

    transition-duration: .2s;

    -ms-transition-duration: .2s;

    -moz-transition-duration: .2s;

    -webkit-transition-duration: .2s;

    -o-transition-duration: .2s;

    }

    ul {

    margin: 0;

    padding: 0;

    list-style: none;

    }

    span, a, a:hover {

    display: inline-block;

    text-decoration: none;

    color: inherit;

    }

    

    .item {

    /*background: #fff;*/

    text-align: center;

    padding: 22px 61px;

    -webkit-box-shadow: 0 0px 25px rgb(0 0 0 / 7%);

    box-shadow: 0 0px 25px rgb(0 0 0 / 7%);

    border-radius: 20px;

    border: 0px solid rgba(0, 0, 0, 0.07);

    margin-bottom: 31px;

    -webkit-transition: all .5s ease 0;

    transition: all .5s ease 0;

    transition: all 0.5s ease 0s;

    width: 100%;

    height: 227px;

    }

  

    .item .feature_box_col_one{

    background:rgba(247, 198, 5, 0.20);

    color:#f91942

    }

    .item .feature_box_col_two{

    background:rgba(146, 39, 255, 0.15);

    color:#f91942

    }

    .item .feature_box_col_three{

    background:rgba(247, 198, 5, 0.20);

    color:#f91942

    }

    .item .feature_box_col_four{

    background:rgba(146, 39, 255, 0.15);

    color:#f91942

    }

    .item .feature_box_col_five{

    background:rgba(146, 39, 255, 0.15);

    color:#2f2f2f

    }

    .item .feature_box_col_six{

    background:rgba(247, 198, 5, 0.20);

    color:#f91942

    }

    .item .feature_box_col_seven{

    background:rgba(146, 39, 255, 0.15);

    color:#f91942

    }

    .item .feature_box_col_eight{

    background:rgba(247, 198, 5, 0.20);

    color:#f91942

    }

    .item .feature_box_col_nine{

    background:rgba(146, 39, 255, 0.15);

    color:#f91942

    }

    .item .feature_box_col_ten{

    background:rgba(247, 198, 5, 0.20);

    color:#f91942

    }

    .item .feature_box_col_eleven{

    background:rgba(146, 39, 255, 0.15);

    color:#f91942

    }

    .item .feature_box_col_twelve{

    background:rgba(247, 198, 5, 0.20);

    color:#f91942

    }

    .mission p {

    margin-bottom: 10px;

    font-size: 15px;

    line-height: 28px;

    font-weight: 500;

    }

    .mission i {

    display: inline-block;

    width: 50px;

    height: 50px;

    line-height: 50px;

    text-align: center;

    background: #f91942;

    border-radius: 50%;

    color: #fff;

    font-size: 25px;

    }

    .mission .small-text {

    margin-left: 10px;

    font-size: 13px;

    color: #666;

    }

    .skills {

    padding-top:0px;

    }

    .skills .prog-item {

    margin-bottom: 25px;

    }

    .skills .prog-item:last-child {

    margin-bottom: 0;

    }

    .skills .prog-item p {

    font-weight: 500;

    font-size: 15px;

    margin-bottom: 10px;

    }

    .skills .prog-item .skills-progress {

    width: 100%;

    height: 10px;

    background: #e0e0e0;

    border-radius:20px;

    position: relative;

    }

    .skills .prog-item .skills-progress span {

    position: absolute;

    left: 0;

    top: 0;

    height: 100%;

    background: #f91942;

    width: 10%;

    border-radius: 10px;

    -webkit-transition: all 1s;

    transition: all 1s;

    }

    .skills .prog-item .skills-progress span:after {

    content: attr(data-value);

    position: absolute;

    top: -5px;

    right: 0;

    font-size: 10px;

    font-weight:600;    

    color: #fff;

    background:rgba(0, 0, 0, 0.9);

    padding: 3px 7px;

    border-radius: 30px;

    }

    /*///////////slider///////////////////////////*/

    .carousel-bg .item {

    /*background-size: cover;*/

    background-size: 1544px;

    background-position: center;

    min-height: 508px;

    }

    .slide{ 

    /*margin-top: 30px;*/

    }

    @media (max-width: 500px){

    .slide{ 

    /*margin-top: 30px;*/

    }

    }

    .details {

    margin: 50px 0; }

    .details h1 {

    font-size: 32px;

    text-align: center;

    margin-bottom: 3px; }

    .details .back-link {

    text-align: center; }

    .details .back-link a {

    display: inline-block;

    margin: 20px 0;

    padding: 15px 30px;

    background: #333;

    color: #fff;

    border-radius: 24px; }

    .details .back-link a svg {

    margin-right: 10px;

    vertical-align: text-top;

    display: inline-block; }

    .imgradius{

    border-radius: 0px;

    } 

    .btnopacity{

    opacity: -19.5 !important;

    }  

    .btnopacityh{

    /*opacity: -19.5 !important;*/

    }  

    @media (min-width: 320px) {

    .carousel-bg .item {

    background-size: 564px;

    min-height: 276px;

    }  

    .carousel-indicators {

    margin-bottom: 27px;

    }

    }

    @media (min-width: 768px) {

    .carousel-bg .item {

    background-size: cover !important;

    /*background-repeat: no-repeat;*/

    background-position: center;

    padding-top: 29%;

    /*min-height: 750px  !important;*/

    }

    }

    @media (min-width: 320px) and (max-width: 767px){

    #mu-about-us {

    margin-top: -26px !important;

    display: inline;

    float: left;

    width: 100%;

    padding: 16px;

    }

    }

    }

    .back_banner{

    padding-bottom: 206px;

    padding-top: 196px;

    margin-top: -2px;

    border-radius: 11px;

    width: 121%;

    content: "";

    background: rgb(0 0 0 / 45%);

    position: absolute;

    top: 0;

    left: 0;

    right: 0;

    }

    @media (min-width: 2560px) {

    .carousel-bg .item {

    background-size: cover !important;

    /*background-size: 1544px;*/

    /*background-position: center;*/

    min-height: 447px !important;

    padding-top: 29%;

    }

    }

    .h1class {

    font-size: 50px;

    /*padding: 21px;*/

    text-shadow: 0px 11px 4px #08080830;

    }

    /*000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000*/

    @media (min-width: 320px) and (max-width: 778px){

    .hclass{

    color: #ffffff;

    text-align: center;

    }

    .h1class{

    font-size: 16px;

    padding: 16px;

    }

    }

    

   

    table{

    border-collapse: separate;

    border-spacing: 0;

    margin-top: 30px;

    width: 100%;

    /background-color: #008A44;/

    }

    th, td {

    text-align: left;

    padding: 8px;

    color: black;

    border: 1px solid;

    text-align: center;

    }

    

    .download {

        float: right;

        color: #000000;

        text-align: center;

        font-size: 12px;

        background: #C8C8BC;

        border: 1px solid #C8C8BC;

        border-radius: 5px;

        padding: 1px 3px;

    }

  

  .alignLeft{

    text-align:left;

  }

  

    tr:last-child td:first-child {

         border-radius: unset !important;

    }

    tr:last-child td:last-child {

        border-radius: unset !important;

    }



    tr:first-child td:last-child {

        border-radius: unset !important;

    }

    tr:first-child td:first-child {

        border-radius: unset !important;

    }

   

  #leftArea{

    width :100%;

  }

   .hclass {

        text-align: center;

        display: inline-block;

        margin: auto;

        margin-top: 30px;

        background-color: #11a14e;

        border-radius: 33px;

        padding: 11px;

        vertical-align: middle !important;

        color: white;

    }

   h1{

        margin: unset !important;

        font-size: 26px!important;

    }



    @media (max-width: 481px){

        .hclass p{

            font-size: 36px;

        }



        .hclass {

            bottom: 17%;

            display: inline-block !important;

            font-size: 20px !important;

            margin-top: 24px !important;

            padding: 8px !important;

        }

        h1{

            margin: unset !important;

            font-size: 18px!important;

        }

    }

  

  

  @media (min-width: 320px) and (max-width: 500px){

		.text_size {

			font-size: 8px;

		}



		#leftArea{

			padding: 0px !important;

		}

      

      	.download{

			float: unset !important;

          	margin-bottom: 10px;



		}

      

      	td,th{

        	padding:2px !important;

		}

      

	}

   

    @media (min-width: 320px) and (max-width: 375px){

      

      	td,th{

        	padding:2px !important;

		}

      

	}

   .table-bordered{
        font-weight: bold;
    }





</style>    

     <div class="container-fluid">

        <div class="row">

            <div class="col-sm-12">

				<div class="text-center" >

                	<div class="hclass">

                    	<h1><b>RACE RESULTS</b></h1>

                	</div>

            	</div>

            </div>

            <?php if(isset($data['value']) && $data['value'] == 'Y'){ ?>

              <div class="col-sm-6" style="">

                  <div class="text-center">

                      <a href="/rwitc_upload/static/live/MEDIATIPS.HTM" target="_blank">

                      <div class="hclass" style="margin-top:10px !important;">

                          <h1><b>MEDIA TIPS</b></h1>

                      </div>

                      </a>

                    </div>

              </div>

              <div class="col-sm-6" style="">

                  <div class="text-center">

                      <a href="/rwitc_upload/static/live/UPDATES.HTM" target="_blank">

                      <div class="hclass" style="margin-top:10px !important;">

                          <h1><b>UPDATES</b></h1>

                      </div>

                      </a>

                  </div>

              </div>

          	<?php } ?>


        </div>

    </div>

    <div class="container" style="text-align: center;">

        <div style="overflow-x:auto;">

        <?php if (strtotime($date) <= strtotime('2022-10-14')) {?>

                    <div class="pageHeader">

                        <div class="pageHeading">

                            <div class="subHeading"><a class="download" href='<?php echo $file; ?>'>Download Race Results</a></div>

                            <div class="subHeading" style="margin-left:13%;"><?php echo CLUB_NAME; ?></div>

                            <div class="subHeading" style="margin-left:13%;"><?php echo $dayNarr['DAYNARR'] . ", " . date("l jS F Y",$newArr[0]['DATE']); ?></div>

                            <h3 class="clearfix"> RACE RESULTS</h3>

                            <div class="subHeading">Click on a horse to know its Performance Profile @ RWITC</div>

                            <div class="subHeading">Click on the Dam to get her progeny details</div>

                        </div>

                    </div>

                    <br />

                    <?php 

                        if ($searaceno == 0) {

                            try {                            

                                $scaletopInfo = mysqli_fetch_assoc(mysqli_query($conn,"SELECT `WEATHER`,`PENITROM`,`FALSERAILS`,`OTHER` FROM scaletop WHERE `RACEDATE`='".$date."'"));

                            } catch (Exception $err) {

                                 $scaletopInfo = array();

                            }

                    ?>

                    <?php if (count($scaletopInfo)>0) { ?>

                            <table class="contentTable table table-bordered">

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

                

                            $raceResults_sql = mysqli_query($conn," SELECT fc.HORSENAME,fc.TRAINERNM,h.SIRE,h.DAM,fc.FINALNAME,fc.FINALNAME1,fc.FINALNAME2,fc.FINALNAME3,h.BREEDER,f.* FROM fhorse5 f INNER JOIN fcard fc ON f.HORSESEQ=fc.HORSESEQ INNER JOIN hmaster h ON f.HORSESEQ=h.HORSESEQ WHERE f.RACEDATE='".$date."' AND f.RACENO='".$prospect['RACENO_SEA']."' AND fc.RACEDATE='".$date."' ORDER BY f.RACENO ASC, f.PLACING ASC, f.CARDNO ASC"); 

                            $raceResults = array();

                             if($raceResults_sql->num_rows > 0){

                                while($row = mysqli_fetch_assoc($raceResults_sql)){

                                    $raceResults[] = $row ;

                                }

                            }

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

                            echo "<table class='contentTable table table-bordered'>";

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

                                    $ownership .= $raceResult['FINALNAME'].$raceResult['FINALNAME1'].$raceResult['FINALNAME2'].$raceResult['FINALNAME3'] ."<br />";                                    

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

                                        $jockeyDets = mysqli_fetch_assoc(mysqli_query($conn,"SELECT j.`JOCKEYNM`,j.`ALLOWANCE` FROM jockeys j WHERE j.`JOCKEY`='".$raceResult['JOCKEY']."'"));   

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

                                    if($date == '2018-04-14' && $prospect['RACENO'] == '5') {

                                        echo "<td colspan='6' class='alignLeft'>3&7-6-1-8-4-2-5</td>";

                                    } else {

                                        echo "<td colspan='6' class='alignLeft'>$cardNoResults</td>";

                                    }

                                echo "</tr>";

                                echo "<tr>";

                                   echo "<th class='darkGrey' colspan='2'>Tote Favourite</th>";

                                   echo "<td colspan='6' class='alignLeft'>$toteFav</td>";

                                 echo "</tr>";

                            }     

                             

                            

                            try {

                            // tote information from divsingl table

                            $toteInfo = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM divsingl d WHERE RACEDATE='".$date."' AND RACENO='".$prospect['RACENO_SEA']."' "));

                           

                             

                          

                            $totes = '';

                            if(!empty($toteInfo)) {



                                if($date == '2018-04-14' && $prospect['RACENO'] == '5') {

                                    $totes .= ($toteInfo['WIN'])?"<b>WIN :</b> ".$toteInfo['WIN'] : "";

                                    $totes .= ($toteInfo['WINA'])?"& ".$toteInfo['WINA'] : "";

                                } else {

                                    $totes .= ($toteInfo['WIN'])?"<b>WIN :</b> ".$toteInfo['WIN'] : "";

                                    $totes .= ($toteInfo['WINA'])?", ".$toteInfo['WINA'] : "";

                                }

                                if ($toteInfo['PLA1'] || $toteInfo['PLA2'] || $toteInfo['PLA3'] || $toteInfo['PLA4']) {

                                    $totes .= "&nbsp;<b>PLACE :</b> ";

                                    $totes .= ($toteInfo['PLA1']) ? "{$toteInfo['PLA1']},":"";

                                    $totes .= ($toteInfo['PLA2']) ? "{$toteInfo['PLA2']},":"";

                                    $totes .= ($toteInfo['PLA3']) ? "{$toteInfo['PLA3']},":"";

                                    $totes .= ($toteInfo['PLA4']) ? "{$toteInfo['PLA4']},":"";

                                    $totes = substr_replace($totes,"",-1,1);

                                }

                                $totes .= ($toteInfo['SHP'])?"&nbsp;<b>SHP :</b> ".$toteInfo['SHP'] : "";

                                $totes .= ($toteInfo['EXW'])?"&nbsp;<b>EXW :</b> ".$toteInfo['EXW'] : "";

                                $totes .= ($toteInfo['EXWC'])?"&nbsp;<b>EXW :</b> C/f ".$toteInfo['EXWC'] : "";

                                $totes .= ($toteInfo['EXP'])?"&nbsp;<b>EXP :</b> ".$toteInfo['EXP'] : "";

                                $totes .= ($toteInfo['EXPC'])?"&nbsp;<b>EXP :</b> C/f ".$toteInfo['EXPC'] : "";

                                $totes .= ($toteInfo['FORD'])?"&nbsp;<b>FOR :</b> ".$toteInfo['FORD'] : "";

                                $totes .= ($toteInfo['FORC'])?"&nbsp;<b>FC :</b> ".$toteInfo['FORC'] . ' (c/f)' : "";



                                if ($toteInfo['QIND'] || $toteInfo['QINC']) {

                                     $totes .= "&nbsp;<b>QNL :</b> ";

                                     $totes .= ($toteInfo['QIND']) ? "{$toteInfo['QIND']},":"";

                                    $totes .= ($toteInfo['QINC']) ? "{$toteInfo['QINC']} (c/f),":"";

                                    $totes = substr_replace($totes,"",-1,1);

                                }

                                if ($toteInfo['TNLD1'] || $toteInfo['TNLD2'] || $toteInfo['TNLDC']) {

                                     $totes .= "&nbsp;<b>TNL :</b> ";

                                     $totes .= ($toteInfo['TNLD1']) ? " {$toteInfo['TNLD1']} &":"";

                                    $totes .= ($toteInfo['TNLD2']) ? " {$toteInfo['TNLD2']} &":"";

                                    $totes .= ($toteInfo['TNLDC']) ? " {$toteInfo['TNLDC']} (c/f) ":"";

                                    $totes = substr_replace($totes,"",-1,2);

                                }

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

                            $divMultiInfo = mysqli_fetch_assoc(mysqli_query($conn,"SELECT dm.*,p.* FROM divmulti dm INNER JOIN pools p ON dm.RACEDATE=p.RACEDATE WHERE dm.RACEDATE='".$date."'"));

                           if(!empty($divMultiInfo)) {

                             

                           

                                echo "<table class='contentTable table table-bordered'>";

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

                                    if($date == '2018-04-14') {

                                        echo "<td colspan='3'>ISINIT, ROSE GOLD, FRIEZE, ALLORA, POLYNEICES & FIREWINGS, GRAND TENTON</td>"; 

                                    } else {    

                                        $winners = mysqli_fetch_assoc(mysqli_query($conn,"SELECT h.HORSENM FROM fhorse5 f INNER JOIN hmaster h ON f.HORSESEQ=h.HORSESEQ WHERE f.RACEDATE='".$date."' AND f.RACENO IN ('".$raceNos."') AND PLACING=1 ORDER BY f.RACENO ASC, f.PLACING ASC, f.CARDNO ASC"));

                                            echo "<td colspan='3'>".join(", ",$winners)."</td>";                                     

                                    }

                                    echo "</tr>";

                                   if ($divMultiInfo['UP0D1'] > 0) {

                                      echo "<tr>";

                                         if($date == '2022-07-29'){

                                           echo "<th class='darkGrey'>100% Div</th>";

                                         }else{

                                            echo "<th class='darkGrey'>70% Div</th>";

                                         }

                                          echo "<td>{$divMultiInfo['UP0D1']}</td>";

                                          echo "<th class='darkGrey'>Tickets</th>";

                                          echo "<td>{$divMultiInfo['UP0T1']}</td>";

                                      echo "</tr>"; 

                                   }

                                   if ($divMultiInfo['UP0D2'] > 0) {

                                      echo "<tr>";

                                          echo "<th class='darkGrey'>30% Div</th>";

                                          if($date == '2018-04-14') {

                                            echo "<td>100 & 269</td>";

                                          } else { 

                                            echo "<td>{$divMultiInfo['UP0D2']}</td>";

                                          }

                                          echo "<th class='darkGrey'>Tickets</th>";

                                          if($date == '2018-04-14') {

                                            echo "<td>202 & 75</td>";

                                          } else { 

                                            echo "<td>{$divMultiInfo['UP0T2']}</td>";

                                          }

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



                                echo "<table class='contentTable table table-bordered'>";

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

                                        if($date == '2018-04-14') {

                                            echo "<td colspan='3'>ROSE GOLD, FRIEZE, ALLORA, POLYNEICES & FIREWINGS, GRAND TENTON</td>"; 

                                        } else {

                                            $winners = mysqli_fetch_assoc(mysqli_query($conn,"SELECT h.HORSENM FROM fhorse5 f INNER JOIN hmaster h ON f.HORSESEQ=h.HORSESEQ WHERE f.RACEDATE='".$date."' AND f.RACENO IN ('".$raceNos."') AND PLACING=1 ORDER BY f.RACENO ASC, f.PLACING ASC, f.CARDNO ASC"));

                                            echo "<td colspan='3'>".join(", ",$winners)."</td>";                                     

                                        }

                                    echo "</tr>";

                                    if ($divMultiInfo['JP0D1'] > 0) {

                                      echo "<tr>";

                                          if ($date == '2022-07-29') {

                                              echo "<th class='darkGrey'>100% Div</th>";

                                          }else{

                                              echo "<th class='darkGrey'>70% Div</th>";

                                          }

                                          echo "<td>{$divMultiInfo['JP0D1']}</td>";

                                          echo "<th class='darkGrey'>Tickets</th>";

                                          echo "<td>{$divMultiInfo['JP0T1']}</td>";

                                      echo "</tr>"; 

                                   }

                                   if ($divMultiInfo['JP0D2'] > 0) {

                                      echo "<tr>";

                                          echo "<th class='darkGrey'>30% Div</th>";

                                          if($date == '2018-04-14') {

                                            echo "<td>62 & 186</td>";

                                          } else { 

                                            echo "<td>{$divMultiInfo['JP0D2']}</td>";

                                          }

                                          echo "<th class='darkGrey'>Tickets</th>";

                                          if($date == '2018-04-14') {

                                            echo "<td>1840 & 612</td>";

                                          } else { 

                                            echo "<td>{$divMultiInfo['JP0T2']}</td>";

                                          }

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

                                            $winners = mysqli_fetch_assoc(mysqli_query($conn,"SELECT h.HORSENM FROM fhorse5 f INNER JOIN hmaster h ON f.HORSESEQ=h.HORSESEQ WHERE f.RACEDATE='".$date."' AND f.RACENO IN ('".$raceNos."') AND PLACING=1 ORDER BY f.RACENO ASC, f.PLACING ASC, f.CARDNO ASC"));

                                            if($date == '2018-04-14') {

                                                echo "<td colspan='3' style='font-size: 11px;'>ROSE GOLD, FRIEZE, ALLORA, POLYNEICES & FIREWINGS, GRAND TENTON</td>";

                                            } else {

                                                echo "<td colspan='3' style='font-size: 11px;'>".join(", ",$winners)."</td>";                                       

                                            }

                                        echo "</tr>";

                                       

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

                                              if($date == '2018-04-14') {

                                                echo "<td>62 & 186</td>";

                                              } else { 

                                                echo "<td>{$divMultiInfo["JP".$i."D2"]}</td>";

                                              }

                                              echo "<th class='darkGrey'>Tickets</th>";

                                              if($date == '2018-04-14') {

                                                echo "<td>1840 & 612</td>";

                                              } else { 

                                                echo "<td>{$divMultiInfo["JP".$i."T2"]}</td>";

                                              }

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

                            

                                echo "<table class='contentTable table table-bordered'>";

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

                                    $winners = mysqli_fetch_assoc(mysqli_query($conn,"SELECT h.HORSENM FROM fhorse5 f INNER JOIN hmaster h ON f.HORSESEQ=h.HORSESEQ WHERE f.RACEDATE='".$date."' AND f.RACENO IN ('".$raceNos."') AND PLACING=1 ORDER BY f.RACENO ASC, f.PLACING ASC, f.CARDNO ASC"));

                                    if($date == '2018-04-14') {

                                        echo "<td colspan='3'>ALLORA, POLYNEICES & FIREWINGS, GRAND TENTON</td>";

                                    } else {

                                        echo "<td colspan='3'>".join(", ",$winners)."</td>";                                     

                                    }

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

                                                $winners = mysqli_fetch_assoc(mysqli_query($conn,"SELECT h.HORSENM FROM fhorse5 f INNER JOIN hmaster h ON f.HORSESEQ=h.HORSESEQ WHERE f.RACEDATE='".$date."' AND f.RACENO IN ('".$raceNos."') AND PLACING=1 ORDER BY f.RACENO ASC, f.PLACING ASC, f.CARDNO ASC"));

                                                if($date == '2018-04-14') {

                                                    echo "<td colspan='3' style='font-size: 11px;'>ALLORA, POLYNEICES & FIREWINGS, GRAND TENTON</td>";

                                                } else {

                                                    echo "<td colspan='3' style='font-size: 11px;'>".join(", ",$winners)."</td>";                                       

                                                }

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

                            }

                        }

                ?>

        <?php } else{ ?>

            <table class="table table-bordered table-striped">

                <thead>

                  <tr>

                    <td class="text_size">

                        <?php

                            $date = date('Y-m-d');

                            if(isset($_GET['date'])) {

                                $date = date('Y-m-d', strtotime($_GET['date']));

                            }

                            if(file_exists("../run_races/Race_results_".$date.".html")){ 

                                include "../run_races/Race_results_".$date.".html";

                            }else{

                                echo "Results Not Found.";
                            }

                        ?>

                    </td>

                  </tr>

                </thead>

            </table>

        <?php } ?>

         </div>

    </div>

    <?php 

        include"footer.php";

    ?>     



	<script>

        function autoRefresh() {

            window.location = window.location.href;

        }
        refresh_in = '<?php echo $refresh_in; ?>';
        console.log('refresh_in');
        console.log(refresh_in);
        if(refresh_in == 1){
            setInterval('autoRefresh()', 260000);
        }
    </script>

    <script src="assets/js/jquery.min.js"></script>  

    <script src="assets/js/bootstrap.js"></script>   

    <script type="text/javascript" src="assets/js/slick.js"></script>

    <script type="text/javascript" src="assets/js/waypoints.js"></script>

    <script type="text/javascript" src="assets/js/jquery.counterup.js"></script>  

    <script type="text/javascript" src="assets/js/jquery.mixitup.js"></script>

    <script type="text/javascript" src="assets/js/jquery.fancybox.pack.js"></script>

    <script src="assets/js/custom.js"></script> 

</body>

</html>

