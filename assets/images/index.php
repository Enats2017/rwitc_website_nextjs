<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0"); 
header("Cache-Control: post-check=0, pre-check=0", false); 
header("Pragma: no-cache"); 
header('Access-Control-Allow-Origin: *');
ini_set('display_errors','off');
error_reporting(1);
//error_reporting(E_ALL);
//ini_set("display_errors", 1);
require_once('bootstrap.php');
require_once('lib/articles.class.php');
require_once('lib/trackwork.class.php');
require_once('lib/race.class.php');
require_once('lib/videos.class.php');
require_once('lib/gallery.class.php');
require_once('lib/polls.class.php');
require_once('lib/design-white.class.php');

// if(!isset($_SESSION['is_set'])){
//     $is_set = 0;
//     $_SESSION['is_set'] = 1;
// } else {
//     $is_set = 1;
// }

$is_set = 1;
if (!isset($_SESSION['CREATED'])) {
    $is_set = 0;
    $_SESSION['CREATED'] = time();
} else if (time() - $_SESSION['CREATED'] > 900) {
    // session started more than 30 minutes ago
    session_regenerate_id(true);    // change session ID for the current session and invalidate old session ID
    $_SESSION['CREATED'] = time();  // update creation time
    $is_set = 0;
}
//echo $is_set;exit;

$rArticles = new Articles($db);
$allArticles = $rArticles->getPublishedArticles();
$trackwork = new Trackwork($db);
$trackworkList = $trackwork->getPublishedTrackwork();
$poll = new Polls($db);
$colorsArray = array('be1e2d','666699','92d5ea','ee8310','8d10ee');
$q = getParameterString('q','',$db);
$pollMsg = '';
$pollstatus = 0;
if ($q == "addVote") {
    $optionID = getParameterString('pollOpt','');
    $questionID = getParameterString('questionID','');
    try {
        $poll->addReply($questionID,$optionID);
        $pollMsg = "Your vote has been logged";
        $pollstatus = 1;
    }catch (Exception $err) {
        $pollMsg="Only 1 vote per IP address allowed";
        $pollstatus = 2;
    }
}
$pollDetails = $poll->getActivePoll();
$images = new Image($db);
$pageTitle ='Home';
$design = new Design();

$design->js='
    <script type="text/javascript">
        // Run capabilities test
        enhance({
            loadScripts: [
                {src: \'/assets/min_js/excanvas.min.js\', iecondition: \'all\'}
            ],
            loadStyles: [
                \'/assets/min_css/visualize.min.css\',
                \'/assets/min_css/visualize-light.min.css\'
            ],
            appendToggleLink: false
        });

        /* JS is for Newsletter popup */
        function popupnewsletter(){
            if ($(window).width() >= 810) {
                $("#subscribe-me").modal("show");
                $(".xout").click(function(){ $("#subscribe-me").modal("hide");});
                var modal = document.getElementById("subscribe-me");
                window.onclick = function(event) {
                    if (event.target == modal) {
                        modal.style.display = "none";
                    }           
                }
            } else {
                $("#subscribe-me").modal("hide");
            }   
        }
    </script>
  ';
$design->css = "

  ";
$design->jqueryJs = "
    /*HCTObj('.daySponsor').cycle({
                fx: 'fade',
                speed : 1000 ,
                width: 160,
                height: 155
    });*/
    var is_set = $is_set;
    $(window).ready(function(){
        if(is_set == 0){
            //popupnewsletter();
        }
    });
  ";
$design->startPage("$pageTitle");
$design->writeLogoTickerMenuHome();
$design->openDiv("contentWrapper","contentHome clearfix");
$design->openDiv("infoWrapper");
$design->openDiv("leftArea");
$design->liveBox("liveBox");
$dbObj = new dbTool();
$raceObj = new Racedata($dbObj);
$galleryDate = $raceObj->getMaxDate('racedate','gallery');


$raceObj = new Racedata($db);
$images = new Image($db);
$filePath = DIR_BASE."rwitc_upload/ticker.inc";
$imagesPath = DIR_BASE."rwitc_upload/banner.inc";
$tickers = file_get_contents($filePath);
$ticker_datas = $raceObj->getticker_datas();

$banner_datas = $raceObj->getbanner_datas();

$oddsbox = $raceObj->getconfig_datas(1);
$linkbox = $raceObj->getconfig_datas(2);
$finalresbox = $raceObj->getconfig_datas(3);
$maxCount = $raceObj->getconfig_datas(4);
// echo '<pre>';
// print_r($banner_datas);
// exit;
//$imagesList = file_get_contents($imagesPath);

?>
<div id="subscribe-me" class="modal animated fade" role="dialog" data-keyboard="true" tabindex="-1">
    <div class="newsletter-popup">
        <div class="newsletter-popup-static newsletter-popup-top">
            <a class="xout">x</a>
        </div>
    </div>
</div>
<div class="col-lg-12"><!-- MAIN ROW -->
    <div class="row">
        <div class="col-lg-6">  <!-- COL-lg-9 -->
            <div class="row">
                <div class="col-lg-12 col-xs-12"><!--Header div col-lg-12  -->
                    <div id="bannerdiv">
                        <div id="carousel-example-generic" class="carousel slide hometest" data-ride="carousel">
                            <!-- Indicators -->
                            <ol class="carousel-indicators">
                                <?php foreach($banner_datas as $bkey => $bvalue){ ?>
                                    <?php if($bkey == '0'){ ?>
                                        <li data-target="#carousel-example-generic" data-slide-to="<?php echo $bkey; ?>" class="active"></li>
                                    <?php } else { ?>
                                        <li data-target="#carousel-example-generic" data-slide-to="<?php echo $bkey; ?>"></li>
                                    <?php } ?>
                                <?php } ?>
                            </ol>
                            <!-- Wrapper for slides -->
                            <div class="carousel-inner"  id="banner" role="listbox">
                                <?php //echo $imagesList; ?>
                                <?php foreach($banner_datas as $bkey => $bvalue){ ?>
                                    <?php if($bkey == '0'){ ?>
                                        <div class="item active">
                                            <?php if($bvalue['link'] != ''){ ?>
                                                <a href="<?php echo $bvalue['link']; ?>">
                                                    <img src="images/<?php echo $bvalue['source']; ?>" alt="<?php echo $bvalue['title']; ?>" title="<?php echo $bvalue['title']; ?>" />  
                                                </a>
                                            <?php } else { ?>
                                                <img src="images/<?php echo $bvalue['source']; ?>" alt="<?php echo $bvalue['title']; ?>" title="<?php echo $bvalue['title']; ?>" />
                                            <?php } ?>
                                        </div>
                                    <?php } else { ?>
                                        <div class="item">
                                            <?php if($bvalue['link'] != ''){ ?>
                                                <a href="<?php echo $bvalue['link']; ?>">
                                                    <img src="images/<?php echo $bvalue['source']; ?>" alt="<?php echo $bvalue['title']; ?>" title="<?php echo $bvalue['title']; ?>" />  
                                                </a>
                                            <?php } else { ?>
                                                <img src="images/<?php echo $bvalue['source']; ?>" alt="<?php echo $bvalue['title']; ?>" title="<?php echo $bvalue['title']; ?>" />
                                            <?php } ?>
                                        </div>
                                    <?php  } ?>
                                <?php } ?>
                                
                                <a href="photoGallery.php?date=<?php echo $galleryDate?>" id="viewPhotos"><img src="images/viewPhoto.png" /></a>
                            </div>

                            <!-- Controls -->
                            <a class="left carousel-control" href="#carousel-example-generic" role="button" data-slide="prev">
                                <span class="glyphicon glyphicon-chevron-left"></span>
                                <span class="sr-only">Previous</span>
                            </a>
                            <a class="right carousel-control" href="#carousel-example-generic" role="button" data-slide="next">
                                <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                                <span class="sr-only">Next</span>
                            </a>
                        </div>
                    </div>
                </div><!--Header div col-lg-12 END -->
            </div>
            <div class="row">
                <div class="col-xs-12 col-sm-6 col-md-6 hidden-lg">
                    <div id="loginform" class="secureLoginWeb">
                        <a href="//rwitcraces.com/Login.aspx" id="loginlogo" >
                           <img src="assets/images/AUCTION-LIVE-2.jpg">
                            <!-- <img src="assets/images/loginlogo.png">
                            <p>Secure Login To View Live Race</p>-->
                        </a>
                    </div>
                </div>
               <div class="col-xs-12 col-sm-6 col-md-6 hidden-lg">
                    <div class="rwitcTV">
                        <a href="http://www.rwitc.com/viewArticles.php?id=3068">
                            <img src="assets/images/BLUE-SECTION-3.jpg">
                        </a>
                    </div>
                </div>
            
            <div class="col-xs-12 col-sm-6 col-md-6 hidden-lg">
                    <div class="rwitcTV">
                        <a href="https://www.youtube.com/watch?v=FsLjlI70Mss">
                            <img src="assets/images/newauction.jpg">
                        </a>
                    </div>
                </div>
            </div>
            <!--<a href="rwitc-tv">
                <img src="assets/images/rwitcTV.png">
            </a>-->
            <div class="row">
                <div class="LINKSBOX hidden-lg">
                    <?php //if(LINKSBOX){
                    if($linkbox){
                        echo $design->liveBoxes("liveBoxes");
                    } ?>
                </div>  
            </div>      
            <div class="row hidden-lg">
                <!-- <div class="col-xs-12 col-lg-12 <?php //if(FINALRESBOX){ echo "liveracebox"; }?>"> live box-->
                <div class="col-xs-12 col-lg-12 <?php if($finalresbox){ echo "liveracebox"; }?>"><!--live box-->
                    <div class="row">
                        <div class="col-xs-12 col-sm-12 col-lg-12 final_div"><!-- TBALE 3 DIV -->

                        </div><!-- TBALE 3 DIV END -->

                        <div class="col-xs-12 col-sm-6 col-lg-6 odds"><!-- TBALE 3 DIV -->

                        </div><!-- TBALE 3 DIV END -->
                        <!--
                       </div><!-- LIVE INTERVIEW  DIV END-->
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12 col-lg-12 racebox">
                    <div class="row">
                        <div  class="col-xs-12 col-sm-6 col-lg-6"> <!-- TBALE 1 DIV END -->
                                <table class="table">
                                    <tr>
                                        <th colspan="5" align="center">PRE-RACE</th>
                                    </tr>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <?php
                                        $dateList = $raceObj->getRecent4PreRaceDates();
                                        foreach ($dateList as $dateVal) {
                                            echo "<td class='grey'>".date("d/m",strtotime($dateVal))."</td>";
                                        }
                                        ?>
                                    </tr>
                                    <tr>
                                        <td>Handicaps</td>
                                        <?php
                                        foreach ($dateList as $dateVal) {
                                            if (strtotime($dateVal) >= 1411344000) {
                                                echo "<td><a href='handicaps.php?date=$dateVal'><img src='images/newdesign/dot.png' /></a></td>";
                                            } else {
                                                echo "<td><a href='handicaps_old.php?date=$dateVal'><img src='images/newdesign/dot.png' /></a></td>";
                                            }
                                        }
                                        ?>
                                    </tr>
                                    <tr>
                                        <td>Acceptances</td>
                                        <?php
                                        $dbDateList = $raceObj->checkDatesInTableByDates("decl",$dateList);
                                        foreach($dateList as $dateVal) {
                                            if (in_array($dateVal,$dbDateList)) {
                                                if (strtotime($dateVal) >= 1411344000) {
                                                    echo "<td><a href='acceptances.php?date=$dateVal'><img src='images/newdesign/dot.png' /></a></td>";
                                                } else {
                                                    echo "<td><a href='acceptances_old.php?date=$dateVal'><img src='images/newdesign/dot.png' /></a></td>";
                                                }
                                            } else {
                                                echo "<td>&nbsp;</td>";
                                            }
                                        }
                                        ?>
                                    </tr>
                                    <tr>
                                        <td>Declarations</td>
                                        <?php
                                        $dbDateList = $raceObj->checkDatesInTableByDates("fdecl",$dateList);
                                        foreach($dateList as $dateVal) {
                                            if (in_array($dateVal,$dbDateList)) {
                                                echo "<td><a href='declarations.php?date=$dateVal'><img src='images/newdesign/dot.png' /></a></td>";
                                            } else {
                                                echo "<td>&nbsp;</td>";
                                            }
                                        }
                                        ?>
                                    </tr>
                                    <tr>
                                        <td>Race Card</td>
                                        <?php
                                        $dbDateList = $raceObj->checkDatesInTableByDates("fcard",$dateList);
                                        foreach($dateList as $dateVal) {
                                            if (in_array($dateVal,$dbDateList)) {
                                                echo "<td><a href='racecard.php?date=$dateVal'><img src='images/newdesign/dot.png' /></a></td>";
                                            } else {
                                                echo "<td>&nbsp;</td>";
                                            }
                                        }
                                        ?>
                                    </tr>
                                    <tr>
                                        <td colspan="5">&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="rightAlign"><a href="archives.php">View Archives</a></td>
                                    </tr>
                                </table>
                        </div><!-- TBALE 1 DIV END -->
                        <div class="col-xs-12 col-sm-6 col-lg-6"> <!-- TBALE 2 DIV-->
                            <table class="table" cellspacing="1" cellpadding="0">
                                <col width="120" />
                                <col width="25" />
                                <col width="25" />
                                <col width="25" />
                                <col width="25" />
                                <tr>
                                    <th colspan="5">POST-RACE</th>
                                </tr>
                                <tr>
                                    <td></td>
                                    <?php
                                    $dateList = $raceObj->getRecent4PostRaceDates();
                                    foreach ($dateList as $dateVal) {
                                        echo "<td class='grey'>".date("d/m",strtotime($dateVal))."</td>";
                                    }
                                    ?>
                                </tr>
                                <tr>
                                    <td>Race Results</td>
                                    <!--<td><img src="/images/newdesign/dot.png" alt="Handicaps" /></td>
                                    <td><img src="/images/newdesign/dot.png" alt="Handicaps" /></td>
                                    <td><img src="/images/newdesign/dot.png" alt="Handicaps" /></td>
                                    <td><img src="/images/newdesign/dot.png" alt="Handicaps" /></td>-->
                                    <?php
                                    $dbDateList = $raceObj->checkDatesInTableByDates("fhorse5",$dateList);
                                    foreach($dateList as $dateVal) {
                                        if (in_array($dateVal,$dbDateList)) {
                                            if(date('Y-m-d') == $dateVal && $linkbox == 'Y'){
                                            //if(('2017-12-17' == $dateVal) && $linkbox == 'Y'){
                                                echo "<td><a href='liveresults.php'><img src='images/newdesign/dot.png' /></a></td>";
                                            } else {
                                                echo "<td><a href='raceresults.php?date=$dateVal'><img src='images/newdesign/dot.png' /></a></td>";
                                            }
                                        } else {
                                            echo "<td>&nbsp;</td>";
                                        }
                                    }
                                    ?>
                                </tr>
                                <tr>
                                    <td>Rating Change</td>
                                    <?php
                                    $dbDateList = $raceObj->checkDatesInTableByDates("ratings_change",$dateList);
                                    foreach($dateList as $dateVal) {
                                        if (in_array($dateVal,$dbDateList)) {
                                            echo "<td><a href='ratingChange.php?date=$dateVal'><img src='images/newdesign/dot.png' /></a></td>";
                                        } else {
                                            echo "<td>&nbsp;</td>";
                                        }
                                    }
                                    ?>
                                </tr>
                                <tr>
                                    <td>Raceday Report</td>
                                    <?php
                                    $dbDateList = $raceObj->checkDatesInTableByDates("raceday_report",$dateList);
                                    foreach($dateList as $dateVal) {
                                        if (in_array($dateVal,$dbDateList)) {
                                            echo "<td><a href='raceDayReport.php?date=$dateVal'><img src='images/newdesign/dot.png' /></a></td>";
                                        } else {
                                            echo "<td>&nbsp;</td>";
                                        }
                                    }
                                    ?>
                                </tr>
                                <tr>
                                    <td>Photos</td>
                                    <?php
                                    $dbDateList = $images->checkDateByDateListAndSponsorID(1,$dateList);
                                    foreach($dateList as $dateVal) {
                                        if (in_array($dateVal,$dbDateList)) {
                                            echo "<td><a href='photoGallery.php?date=$dateVal'><img src='images/newdesign/dot.png' /></a></td>";
                                        } else {
                                            echo "<td>&nbsp;</td>";
                                        }
                                    }
                                    ?>
                                </tr>
                                <tr>
                                    <td>Videos</td>
                                    <?php
                                    $videos = new Videos($db);
                                    foreach ($dateList as $dateVal) {
                                        $data = $videos->getVideoDataByDate($dateVal);
                                        if (strtotime($dateVal) < strtotime('2015-07-23')) {

                                            echo "<td><a href=\"http://mumbairaces.com/index.php?chan={$data['chan']}&cat={$data['cat']}\"><img src=\"images/newdesign/dot.png\" /></a></td>";
                                        } else {
                                            echo "<td><a href=\"http://rwitcraces.com/RaceArchives.aspx?d=".date("dmY",strtotime($dateVal))."\"><img src=\"images/newdesign/dot.png\" /></a></td>";
                                        }
                                    }
                                    ?>
                                </tr>
                                <tr>
                                    <td colspan="5" class="rightAlign"><a href="archives.php">View Archives</a></td>
                                </tr>
                            </table>
                        </div><!-- TBALE 2 DIV END -->
                    </div>
                </div>
            </div><!-- ROW -->


            <div class="row">
                <div class="col-xs-12 col-lg-12 racebox">
                    <div class="row">
                        <div class="col-xs-12 col-sm-6 col-lg-6"><!-- TBALE 3 DIV -->
                            <table class="table" cellspacing="1" cellpadding="0">
                                <col width="68" />
                                <col width="66" />
                                <col width="66" />
                                <tr>
                                    <th colspan="3">TRACKWORK</th>
                                </tr>
                                <?php
                                for ($i=0;$i<18;$i+=3) {
                                    echo "<tr>";
                                    if ($trackworkList[$i]['id']) {
                                        echo "<td><a href='trackwork.php?id={$trackworkList[$i]['id']}'>".date("d M",strtotime($trackworkList[$i]['trackwork_date']))."</a></td>";
                                    } else {
                                        echo "<td>&nbsp;</td>";
                                    }if ($trackworkList[$i+1]['id']) {
                                        echo "<td><a href='trackwork.php?id={$trackworkList[$i+1]['id']}'>".date("d M",strtotime($trackworkList[$i+1]['trackwork_date']))."</a></td>";
                                    } else {
                                        echo "<td>&nbsp;</td>";
                                    }if ($trackworkList[$i+2]['id']) {
                                        echo "<td><a href='trackwork.php?id={$trackworkList[$i+2]['id']}'>".date("d M",strtotime($trackworkList[$i+2]['trackwork_date']))."</a></td>";
                                    } else {
                                        echo "<td>&nbsp;</td>";
                                    }
                                    echo "</tr>";
                                }
                                ?>
                                <tr>
                                    <td colspan="3" class="rightAlign"><a href="archives.php">View Archives</a></td>
                                </tr>
                            </table>
                        </div><!-- TBALE 3 DIV END -->

                        <div class="hidden-xs col-sm-6  col-lg-6" ><!-- LIVE INTERVIEW  DIV-->
                                <a target="_blank" id="live-interviews" href="downloads/Prospectus.pdf">
                                    <img src="assets/images/image8.png">
                                    </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xs-12 col-lg-12"> <!-- SPONSER DIV -->
                    <div id="sponser">
                        <table id="sponsertbl" width="100%">
                            <tr>
                                <td><a href="performanceProfile.php"><p>PERFORMANCE PROFILE OF HORSES</p></a></td>
                                <td><a href="horsesInTraining.php"><p>TRAINER WISE HORSES IN TRAINING</p></a></td>
                                <tr>
                                <td><a href="horseRatings.php"><p>RATINGS OF ALL HORSES</p></a></td>
                                <td><a href="http://www.horsein.com"><p>WEBPORTAL FOR OWNERS / TRAINERS</p></a></td>
                                </tr>
                                <tr>
                                <td><a href="dividends.php"><p>TOTE DIVIDENDS</p></a></td>
                                <td> <a href="https://www.rwitcraces.com/RaceArchives.aspx"><p>VIDEO ARCHIVES</p></a></td>
                                </tr>
                                <tr>
                                <td> <a href="moneyLeaders.php"><p>MONEY LEADERS</p></a></td>
                                <td> <a href="calendar.php"><p>RACING FIXTURES</p></a></td></tr>
                                <tr>
                                <td> <a href="sweepstakes.php"><p>ENTRIES FOR SWEEPSTAKE RACES</p></a></td>
                                <td> <a href="http://www.indianstudbook.com"><p>INDIAN STUD BOOK</p></a></td>
                                </tr>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div><!-- COL-lg-9 after row end -->
                    
        <div class="col-lg-3">  <!-- MIDDEL COL-3 -->
            <div class="col-lg-12 col-xs-12">
                <div class="row" id="tickerWrapper">
                    <div id="tickerHeader">TOP<br>STORIES</div>
                    <br>
                    <div id="carousel-example-generic" class="carousel slide" data-ride="carousel">
                        <!-- Indicators -->
                        <!-- Wrapper for slides -->
                        <div class="carousel-inner" role="listbox">
                            <?php foreach($ticker_datas as $bkey => $bvalue){ ?>
                                <?php if($bkey == '0'){ ?>
                                    <div class="item active">
                                        <?php echo nl2br($bvalue['body']) ?>
                                    </div>
                                <?php } else { ?>
                                    <div class="item">
                                        <?php echo nl2br($bvalue['body']) ?>
                                    </div>
                                <?php  } ?>
                            <?php } ?>
                            <?php //echo $tickers; ?>
                        </div>
                        <!-- Controls -->
                    </div>
                </div>
            </div>
        
            

            <div class="col-lg-12 col-xs-12">
            <!-- <div class="hidden-xs hidden-sm hidden-md col-lg-12"> -->
                <div class="row">
                    <div class="rwitcTV">
                        <a target="_blank" href="//youtube.com/watch?v=CLq5k8VgyHM">
                            <span>
                                <?php   ?>
                                    <img src="assets/images/AUCTION-2.jpg">
                                <?php  ?>   
                            </span>                         
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-12 col-xs-12">
                <div class="row">
                    <div class="event">
                        
						<a target="_blank" href="https://www.youtube.com/watch?v=FsLjlI70Mss">
                            <span>
                                <?php   ?>
                                    <img src="assets/images/newauction.jpg">
                                <?php  ?>   
                            </span>                         
                        </a>
                    </div>
                    <div class="event">
                        <a href="https://www.youtube.com/watch?v=09mdBLASFbw" target="_blank">
                            <img src="assets/images/RACEKARAJA2501.jpg">
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-12 col-xs-12">
                <div class="row">
                    <div class="rwitcTV clearfix" id="newsWrapper">
                        <h3 id="articlesTitle">ARTICLES</h3>
                        <ul class="news">
                            <?php
                            //print_r($allArticles);
                            //$maxCount = count($allArticles);
                            if ($maxCount >= 1)
                                //$maxCount = 1;
                            for ($i=0;$i<$maxCount;$i++) {
                                $extra = '';
                                if ($allArticles[$i]['new'] == "Y") {
                                    $extra = ' - <span class="new">New</span>';
                                }

                                echo "<li><a href='viewArticles.php?id={$allArticles[$i]['id']}'>".$allArticles[$i]['title']." $extra</a></li>";?>
                                <li id="stardevider"><img src="assets/images/divider.png"></li>
                            <?php
                            }
                            ?>
                            <li class="viewMore"><a href="viewArticles.php">View More</a></li>
                        </ul>
                        <!--     <div id="newsLabel"></div> -->
                    </div>
                </div>
            </div>
        </div><!-- MIDDLE COL-3 END -->
    

        <!-- Section 3rd start -->
        <div class="col-lg-3"><!-- RIGHTSIDEBARCONTENT -->
            <div class="hidden-xs hidden-sm hidden-md col-lg-12">
                <div class="row">
                   <div id="loginform" class="secureLoginWeb">
                        <a href="//rwitcraces.com/Login.aspx" id="loginlogo">
                           <img src="assets/images/AUCTION-LIVE-2.jpg">
						  <!-- <img src="assets/images/loginlogo.png">
                            <p>Secure Login To View Live Race</p>-->
                        </a>
                   </div>
                </div>
            </div>
            <div class="hidden-xs hidden-sm hidden-md LINKSBOX">
                <?php //if(LINKSBOX){
                    if($linkbox){
                        echo $design->liveBoxes("liveBoxes");
                } ?>
            </div>
            
            <!-- <div class="hidden-xs hidden-sm hidden-md col-lg-12 <?php //if(FINALRESBOX){ echo "liveracebox"; }?>">live box-->
            <div class="hidden-xs hidden-sm hidden-md col-lg-12 <?php if($finalresbox){ echo "liveracebox"; }?>"><!--live box-->
                <div class="row">
                    <div class="final_div"><!-- TBALE 3 DIV -->
                    </div><!-- TBALE 3 DIV END -->
                    <div class="odds"><!-- TBALE 3 DIV -->
                    </div><!-- TBALE 3 DIV END -->
                    <!--
                    </div><!-- LIVE INTERVIEW  DIV END-->
                </div>
            </div>
            <div class="hidden-xs hidden-sm hidden-md col-lg-12">
                <div class="row">
                    <div class="rwitcTV">
                        <a target="_blank" href="http://www.rwitc.com/viewArticles.php?id=3068">
                            <span>
                                <?php   ?>
                                    <img src="assets/images/BLUE-SECTION-3.jpg">
                                <?php  ?>   
                            </span>                         
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-xs-12 col-sm-6  col-lg-12">
                <div class="row">
                   <div class="eventCalendar  eventSlider">
                        <div id="carousel-example-generic" class="carousel slide" data-ride="carousel">
                          <!-- Wrapper for slides -->
                            <div class="carousel-inner" role="listbox">
                                <!--<div class="item active">
                                  <a href="http://rwitc.com/rwitc-tv/" target="_blank"> <img src="assets/images/Event_Calendar-06.jpg" ></a>
                                </div>-->
                                <div class="item">
                                    <img src="assets/images/Event_Calendar-02_comp.jpg" >
                                </div>
                                <div class="item">
                                   <a href="http://rwitc.com/rwitc-tv/" target="_blank"> <img src="assets/images/Event_Calendar-05_comp.jpg" ></a>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
             
            <?php $design->rightSponsor()?><!--Right Sponsor of the day-->
            <div class="hidden-xs hidden-sm hidden-md col-lg-12">
                <div class="row">
                    <div class="rwitcTV">
                        <a href="https://www.youtube.com/watch?v=sxGNuVw4A_k&t=2s" target="_blank">
                            <img src="assets/images/dhamaka21-1.jpg">
                        </a>
                    </div>
                </div>
            </div>
            <div class="hidden-xs hidden-sm hidden-md col-lg-12">
                <div class="row">
                    <div class="downloadApp">
                        <p><b>GO DIGITAL <!--<a style="text-decoration:none;color:#FFFFF3" href="http://www.replicasderelojesdelujo.com">RELOJES</a>--> DOWNLOAD
                            OUR APP ON YOUR PHONE</b>
                        </p>
                        <ul>
                            <li>
                                <a target="_blank" href="https://play.google.com/store/apps/details?id=com.rwitc.mobileweb"> <img src="assets/images/android.png"></a>
                            </li>
                            <li>
                                <a target="_blank" href="https://itunes.apple.com/us/app/rwitc/id619375717?ls=1&mt=8"> <img src="assets/images/apple.png"></a>
                            </li>
                            <li>
                                <a target="_blank" href="https://appworld.blackberry.com/webstore/content/26326879/?countrycode=IN&lang=en"> <img src="assets/images/blackberry.png"></a>
                            </li>
                            <li>
                                <a target="_blank" href="app-qr.php"> <img src="assets/images/barcode.png"></a>
                            </li>
                        </ul>
                    </div>
                </div>   
            </div>

            <?php if(isset($pollDetails[0]['question']) && $pollDetails[0]['question'] != ''){ ?>
                <div class="col-lg-12 col-xs-12">
                    <div class="row">
                        <div class="rwitcTV" style="">
                            <?php if($pollMsg != ''){ ?>
                                <?php if($pollstatus == 1){ ?>
                                    <h4 style="color: green;font-weight: bold;"><?php echo $pollMsg; ?></h4>
                                <?php } else { ?>
                                    <h4 style="color: red;font-weight: bold;"><?php echo $pollMsg; ?></h4>
                                <?php } ?>
                            <?php } ?>
                            <table cellpadding="0" cellspacing="0" width="100%" style="border: 1px solid;">
                                <tbody>
                                    <tr>
                                        <td height="22" style="background-color: #9bd1da;font-size: 18px;font-weight: bold;border: 2px solid;padding: 5px;">
                                            Polls
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 5px;">
                                            <form action="index.php" method="POST">
                                                <table>
                                                    <tbody>
                                                        <tr><td width="96%" height="5"></td></tr> 
                                                        <tr>
                                                            <td class="text3_bold" width="96%">
                                                                &nbsp;
                                                                <span class="PollQuestion" style="font-weight: bold;color: #000000;">
                                                                    Q). <?php echo $pollDetails[0]['question'] ?>
                                                                </span>
                                                            </td>
                                                        </tr> 
                                                        <tr>
                                                            <td class="text3" style="margin-top: 10px;padding: 8px;">
                                                                <table width="96%" border="0" align="center" cellpadding="0" cellspacing="0" style="margin-top: 10px;"> 
                                                                    <tbody>
                                                                        <?php foreach($pollDetails as $pollInfo) { ?>
                                                                            <tr>
                                                                                <td width="10" valign="top">
                                                                                    <input type="radio" name="pollOpt" id="opt<?php echo $pollInfo['id'];?>" value="<?php echo $pollInfo['id'];?>">
                                                                                </td>
                                                                                <td width="3" height="10"></td><td align="left">
                                                                                    <label style="color: #000000;" for="opt<?php echo $pollInfo['id'];?>"><?php echo $pollInfo['option'];?></label>
                                                                                </td>
                                                                            </tr>
                                                                        <?php } ?>
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        </tr> 
                                                        <tr>
                                                            <td align="" style="padding: 10px;"> 
                                                                <input type="submit" name="submit" value="Vote" style="background-color: #9bd1da;color: #000000;font-weight: bold;" />
                                                                <input type="hidden" name="q" value="addVote" />
                                                                <input type="hidden" name="questionID" value="<?php echo $pollDetails[0]['questionID'];?>" />
                                                            </td>
                                                        </tr> 
                                                    </tbody>
                                                </table>
                                            </form>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <div class="col-lg-12 col-xs-12">
                <div class="row">
                    <div class="rwitcTV" style="text-align: center;border: 1px solid;background-color: #9bd1da;padding: 10px;margin-top: 20px;font-size: 30px;">
                        <a href="register.php" style="color: #000000;">
                            Subscribe To NewsLetter
                        </a>
                    </div>
                </div>
            </div>

            <div class="hidden-xs hidden-sm hidden-md col-lg-12 weatherWidget">
            <!-- <div class="hidden-xs hidden-sm hidden-md col-lg-6 weatherWidget"> -->
                <div class="row">
                    <iframe style="overflow:hidden;border:none" allowtransparency="true" width="100%" height="200px" src="http://www.weather-forecast.com/locations/Mumbai/forecasts/latest/threedayfree" scrolling="no" frameborder="0" marginwidth="0" marginheight="0"></iframe>
                </div>
            </div>
        </div> <!-- END - RIGHTSIDEBAR -->
    </div>
</div><!-- MAIN ROW -->
<?php
$design->closeDiv();
$design->closeDiv();
$design->closeDiv();
$design->endPage();
$design = NULL; // release object
?>