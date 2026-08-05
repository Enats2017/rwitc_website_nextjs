<?php
class Design {
var $jqueryJs,$js,$css,$bodyAttr;
function startPage($pageTitle,$pageName="") {
    $style= "";
/*if ($pageName == "home") {
    $style="style='background: url(/images/bg.jpg) repeat-y;'";
} */ 
echo <<< PAGESTART
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    
	<head>    		
		<title>{$pageTitle} - RWITC</title>				
     <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <meta http-equiv="content-language" content="en">
    <meta name="author" content="RWITC">
    <meta name="description" content="Royal Western India Turf Club">
    <meta name="keywords" content="Royal Western India Turf Club, RWITC">
    <meta name="revisit-after" content="4 days">
    <title>RWITC</title>
    <script language="javascript" type="text/javascript" src="/js/jquery-1.4.2.min.js"></script>
    <script language="javascript" type="text/javascript" src="/js/jquery.cycle.all.js"></script>
    <script type="text/javascript" src="/js/superfish.js"></script>
    <script type="text/javascript" src="/js/supersubs.js"></script> 
    <script type="text/javascript" src="/js/jquery.vticker-min.js"></script> 
    <script type="text/javascript" src="/js/jquery.marquee.js"></script> 
<!--    <script type="text/javascript" src="/js/jquery.countdown.js"></script> -->
    <link rel="stylesheet" type="text/css" href="/css/rwitc.css">
    <link rel="stylesheet" type="text/css" href="/css/superfish.css" media="screen">
    {$this->js}
    {$this->css}
      
    <script type="text/javascript">
    HCTObj = new jQuery.noConflict();
    HCTObj(function(){
            HCTObj('ul.sf-menu').
            supersubs({
                minWidth        : 11,
                maxWidth        : 25,
                extraWidth        : 0                
            })
            .superfish({
                 autoArrows:  true,
                 dropShadows: true,
                 delay: 100,
                 speed: 'fast'                  
            });
            HCTObj('#ticker').cycle({ 
                fx:      'scrollLeft',
                speed : 2000,
                timeout:  6000 
            });
            HCTObj('#banner').cycle({ 
                fx:      'fade',
                speed : 1000 ,
                width: 700,
                height: 250
            });
        HCTObj('marquee#sponsorBlock').marquee('pointer').mouseover(function () {
            HCTObj(this).trigger('stop');
        }).mouseout(function () {
            HCTObj(this).trigger('start');
        }).mousemove(function (event) {
            if (HCTObj(this).data('drag') == true) {
                this.scrollLeft = HCTObj(this).data('scrollX') + (HCTObj(this).data('x') - event.clientX);
            }
        }).mousedown(function (event) {
            HCTObj(this).data('drag', true).data('x', event.clientX).data('scrollX', this.scrollLeft);
        }).mouseup(function () {
            HCTObj(this).data('drag', false);
        });
        {$this->jqueryJs}        
        });        
    </script>        
    <script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-28228814-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
<meta name="google-site-verification" content="ENeGh2_Z_Q82czmvYSwqrfw3hrhJS1GFORSYktDgCXs" />
</head>
<body {$this->bodyAttr}>
    <div id="pageWrapper">
    <div id="page" {$style}>
PAGESTART;
}

function writeLogoTickerMenu() {     
    $dbObj = new dbTool();            
    require_once('race.class.php');   
    
    $raceObjTemp = new Racedata($dbObj); 
    
    $galleryDate = $raceObjTemp->getMaxDate('racedate','gallery');
    
    $dbObj =  $raceObjTemp = NULL;       
    
	$filePath = $_SERVER['DOCUMENT_ROOT'] ."/rwitc_upload/ticker.inc";
    
	$imagesPath = $_SERVER['DOCUMENT_ROOT'] ."/rwitc_upload/banner.inc";
    
    //$fh = fopen($filePath,"r");
    $tickers = split("</li>",file_get_contents($filePath));
    $tickerHTML = "";
    for ($i=0;$i<count($tickers)-1;$i++) {
        if ($i %2 == 0)
            $colour = "ticker-cream";
        else 
            $colour = "ticker-lightgreen";
      $tickerHTML .=   "<div class=\"tickerBlock {$colour}\">".str_replace("<li>","",$tickers[$i])."</div>";
     }
	$imagesList = file_get_contents($imagesPath);
echo <<< TICKER
	   <!-- Start : Header BLock -->
        <div id="headerBlock">          
            <div id="logo">
                <a href="/index.php"><img src="/images/rwitc-logo.jpg" alt="Royal Western India Turf Club" /></a>
            </div>
            <div id="social">
                <ul>
                    <li style="background: url(/images/facebook_icon.png) right 2px no-repeat; height: 18px;"><a href="http://www.facebook.com/pages/Royal-Western-India-Turf-Club/165292783507507?v=wall">Like us on Facebook</a></li>
                    <li style="background: url(/images/twitter_icon.png) right 2px no-repeat; height: 20px;"><a href="http://twitter.com/rwitcmumbai">Follow us on Twitter</a></li>
                </ul>
                <!--<h1>The<br />Royal Western India<br />Turf Club Ltd.</h1>-->
            </div>
            <div id="kyazoonga">
                <a href="http://www.kyazoonga.com/Sports/RWITC_Pune_Horse_Races/406/4"><img src="/images/kyazoonga.png" alt="Kyazoonga, Online Ticketing Partner" /></a>
            </div>
            <div id="live">
                <a href="https://www.rwitc.com/liverace.php"><img src="/images/live.png" alt="Race Telecast" /></a>
            </div>
            <div id="tribeAtTruf">
                <a href="http://www.facebook.com/pages/Tribe-at-Turf/175196099185751"><img src="/images/tribe_at_turf.png" alt="Tribe at Turf" /></a>
            </div>
            <div id="registerBlock">
                <form name="loginFrm" method="post" action="/login.php">  
                <ul>
                    <li><h3>Login at the RWITC</h3></li>
                    <li><b>Register to get Free Racing Updates in your mailbox</b></li>
                    <li><input type="text" name="email" class="textbox" value="Username" /></li>
                    <li><input type="password" name="password" class="textbox" value="Password" /></li>
                    <li>
                        <a href="/register.php" class="register">New User? Sign Up</a>
                        <input type="submit" name="submit" class="submit" value="Submit" />
                    </li>
                </ul>
                <input type="hidden" name="q" value="login-user" />            
                </form>    
            </div>
        </div>
        <!-- End : Header BLock -->
        <!-- Start : Banner BLock -->
        <div id="bannerBlock">
            <div id="banner">
                $imagesList
            </div>   
            <div id="ticker">
                $tickerHTML
            </div>
            <a href="/photoGallery.php?date=$galleryDate" id="viewPhotos"><img src="/images/viewPhoto.png" /></a>
        </div>    
        <!-- End : Banner BLock -->
        <div id= "menuBar">
            <ul class="sf-menu">
            <li>
                <a href="#a">The Club</a>
                <ul>
                    <li>
                        <a href="/club/aboutus.php">About RWITC</a>
                    </li>
                    <li>
                        <a href="/club/vision-mission.php">Vision &amp; Mission</a>
                    </li>
                    <li>
                        <a href="#">Organisation &amp; Management</a>
                        <ul>
                            <li><a href="/club/structure.php">Structure</a></li>
                            <li><a href="/club/managingCommittee.php">Managing Committee</a></li>
                            <li><a href="/club/stewardsOfclub.php">Stewards of the Club</a></li>
                            <li><a href="/club/boardofAppeal.php">Board of Appeal</a></li>
                            <li><a href="/club/working_group.php">Working Group</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="#">History</a>
                        <ul>
                            <li><a href="/club/timeline.php">Timeline / Major Events</a></li>                            
                             <li><a href="/club/bequeathingLegacy.php">Bequeathing a Colonial Legacy</a></li> 
                        </ul>
                    </li>
                    <li>
                        <a href="#">Charities</a>
                        <ul>
                            <li><a href="/club/charity.php">Charity Race Days</a></li>                            
                        </ul>
                    </li>
                    <li><a href="/club/contributing.php">Contributing to the Community</a></li>
                    <li><a href="/club/responsible.php">Responsible Gambling</a></li>
                    <li><a href="/club/careers.php">Careers</a></li>
                    <li><a href="/club/contactus.php">Contact Us</a></li>
                </ul>
            </li>
            <li>
                <a href="#">Horse Racing</a>
                <ul>
                            <li><a href="/horseracing/beginnersGuide.php">Beginners Guide</a></li>                           
                            <li><a href="/horseracing/rulesOfRacing.pdf">Rules of Racing</a></li>
                            <li><a href="/horseracing/racingCalendar.pdf">Racing Calendar</a></li>
                            <li><a href="/stewardsReport.php">Notice From Stewards</a></li>
                            <li><a href="/horseracing/readyreckoner.php">Ready Reckoner</a></li>
                            <li><a href="/trainerStatistics.php">Trainer's Statistics</a></li>
                            <li><a href="/jockeyStatistics.php">Jockey's Statistics</a></li>
                            <li><a href="/horseracing/jockey_weights.php">Jockey's Riding Weight</a></li> 
                            <li><a href="/horseracing/horsebodyWeight.php">Body Weight of Horses</a></li>
                            <li><a href="/horseracing/record_timings.php">Record Timings</a></li>
                            <li><a href="/sweepstakes.php">Sweepstake Entries</a></li>
                            <li><a href="/horseracing/standard_timings.pdf">Standard Timings</a></li>
                            <li><a href="/raceHistory.php">History of Graded Races</a></li>
                            <li><a href="/horseracing/saddleCloth.php">Saddle Cloth Numbers</a></li>
                           <!-- <li><a href="/horseracing/incomefromHeads.php">Income from Various Heads</a></li>-->
                        </ul>
            </li>
            <li>
                <a href="#">Betting &amp; Entertainment</a>
                <ul>
                    <li><a href="/bettingentertainment/overview.php">Overview</a></li>
                    <li><a href="/bettingentertainment/beginnersGuide.php">Beginners Guide</a></li>
                    <li><a href="/bettingentertainment/waggeringTerms.php">Wagering Terms</a></li>
                    <li><a href="/bettingentertainment/bettingPools.php">Betting Pools</a></li>
                    <li><a href="/bettingentertainment/bettingChannels.php">Betting Channels</a></li>
                    <li><a href="/bettingentertainment/deductionNorms.php">Deduction Norms</a></li>
                    <li><a href="/bettingentertainment/offcourseBettingCentres.php">Off-Course Betting Centres</a></li>
                </ul>
            </li>
            <li>
                <a href="#">Membership</a>
                <ul>
                    <li><a href="/membership/overview.php">Overview</a></li>
                    <li><a href="/membership/privileges.php">Membership Privileges</a></li>
                    <!--<li><a href="/membership/olives.php">Olive</a></li>-->
                    <li><a href="/membership/categories.php">Categories</a></li>
                    <li><a href="/membership/rulesAndRegulations.php">Rules &amp; Regulations</a></li>
                    <li><a href="/membership/lawnFacilities.php">Lawn &amp; Facilities Booking Forms</a></li>                   
                </ul>
            </li>
            <li>
                <a href="#">Come Racing</a>
                <ul>
                    <li><a href="/comeracing/overview.php">Overview</a></li>
                    <li><a href="/comeracing/mumbairacecourse.php">Mumbai Race Course</a></li>
                    <li><a href="/comeracing/puneracecourse.php">Pune Race Course</a></li>
                    <li><a href="/comeracing/howToGetThere.php">How to get there</a></li>
                    <li><a href="/comeracing/services.php">Race Course Services &amp; Others</a></li>
                </ul>
            </li>    
            <li>
                <a href="#">Advertising &amp; Sponsorship</a>
                <ul>
                    <li><a href="/sponsorships/overview.php">Overview</a></li>
                    <li><a href="/sponsorships/privileges.php">Sponsor's Privileges</a></li>
                    <li><a href="/sponsorships/opportunities.php">Advertising &amp; Sponsorship Opportunities</a></li>
                    <li><a href="/sponsorships/contactus.php">Contact Us</a></li>                    
                    <li><a href="/sponsorships/sponsors.php">Our Sponsors</a></li>
                </ul>
            </li>
            <li>
                <a href="#">Downloads</a>
                <ul style='width:11em;'>
                    <li><a href="/downloads/forms.php">Forms</a></li>
                    <li><a href="/downloads/Chart.pdf">Chart</a></li>
                    <li><a href="/downloads/Prospectus.pdf">Prospectus</a></li>
                </ul>
            </li>
        </ul>
        </div> 
TICKER;

}

function openDiv($id="",$class="") {
	echo "<div id='$id' class='$class'>";
}

function closeDiv() {
	echo "</div>";
}


function rightArea() {


echo '<div id="rightArea">';
/*echo  '<div class="square" style="background: white;">
                                 <div class="squareHeader">
                                        <img src=\'/images/contest-button.gif\' alt=\'Contest ON rwitc.com\' />
                                        <span style="padding: 0px 5px;"><a style="color: #00653D; font-size: 12px; font-weight: bold;" href="/predict_winner.php?date=2011-01-23&raceno=158">CONTEST FOR REGISTERED USERS</a></span>
                                </div>
                 </div>';

echo "
<div class=\"square\" style=\"background: url('/images/derbybg.png') no-repeat; padding: 0 0 5px 0;margin-top:0px;\">
                                 <div class=\"squareHeader\" style=\"border: none;\">                                        
                                        <span style=\"margin-left: 50px;padding-top:10px;width:160px;padding-right:0px; \"><a style=\"color: #00653D; font-size: 11px; font-weight: bold;\" href=\"/derby_contest.php\">SIGNATURE DERBY CONTEST</a></span>
                                </div>
                 </div>";
*/
/*                 
echo '
    <a href="http://www.facebook.com/mcdsignature" style="text-decoration:none; color: white;cursor:pointer;clear:both;">
        <div id="countdown" style="width: 215px; height: 93px; background: url(\'/images/derby-countdown.gif\') no-repeat;">            
                    <div id="defaultCountdown" style="color: white; padding: 3px 0 0 65px; font-weight: bold; font-size: 30pt; font-family: "Arial";"></div>           
        </div> </a>';
*/
if (LINKSBOX) {
echo <<< MEDIA_TIPS
<!--           <div id="media"> -->
                 <ul class='inline'>
                    <li><a href='/rwitc_upload/static/live/MEDIATIPS.HTM'>Media Tips</a></li>
                    <li style='color: red;font-weight:bold;'><a style='color: red;font-weight:bold;' href='/liveresults.php'>Live Results</a></li>                    
                 </ul>
                 <ul class='inline' style="margin-top:0px;">
                    <li><a href='/rwitc_upload/static/live/UPDATES.HTM'>Updates</a></li>
                    <li><a href='/rwitc_upload/static/live/ODDS.HTM'>Changing Odds</a></li>
                 </ul>  
<!--           </div>                -->
MEDIA_TIPS;
}                    
if (ODDSBOX) {
echo <<< ODDS_CONTENT
                    
                <div class='liveBox'>
                    <div class='liveBoxContent'>
                         <div id="odds" style="background: #d4d0b3; font-size: 1.1em; font-weight:bold;">
                            Loading
                         </div>
                    </div>
                </div>
                <script type='text/javascript'>
                function updateOdds() {  
                      //HCTObj("#odds").html('Updating...'); 
                      HCTObj.ajax({
                        type: 'GET',
                        url: '/live.php?mode=odds',
                        timeout: 2000,
                        //beforeSend: function() {
                        //  HCTObj("#odds").html('Updating...');   
                        //},
                        success: function(data) {      
                            if (data!=0) {  
                                HCTObj("#odds").html(data);
                            } else {
                               // HCTObj("#odds").html('Updating...'); 
                            }
                          window.setTimeout(updateOdds, 10000);
                        },
                        error: function (XMLHttpRequest, textStatus, errorThrown) {
                          HCTObj("#odds").html('Retrying server...');
                          window.setTimeout(updateOdds, 10000);
                        }
                      });
                }
                 updateOdds();
               
                 </script>
ODDS_CONTENT;
}
if (FINALRESBOX) {
echo <<< FINAL_CONTENT
                <div class='liveBox'>
                    <div class='liveBoxContent'>
                         <div id="final_div"  style="background: #d4d0b3; font-size: 1.1em; font-weight:bold;">
                            Loading
                         </div>
                    </div>
                </div>
                <script type='text/javascript'>
                function updateFinal() {  
                  HCTObj("#final_div").html('Updating...'); 
                  HCTObj.ajax({
                    type: 'GET',
                    url: '/live.php?mode=final',
                    timeout: 2000,
                    success: function(data) {        
                      HCTObj("#final_div").html('');
                      HCTObj("#final_div").html(data);
                      //HCTObj("#final_div").html(''); 
                      window.setTimeout(updateFinal, 120000);
                    },
                    error: function (XMLHttpRequest, textStatus, errorThrown) {
                      HCTObj("#final_div").html('Retrying server...');
                      window.setTimeout(updateFinal, 10000);
                    }
                  })
                }
               HCTObj('#final_div').vTicker( {
                showItems: 1,
                height: 170,
                animation: 'fade',
                mousePause: true,
                pause: 5000
                });
               
                 updateFinal();  
                 </script>
FINAL_CONTENT;
}

ECHO <<< BOXES
         <div class="sponsor" id="sponsor">
            <div class="daySponsor">
              <img width="140" height="140" src="/rwitc_upload/sponsor/AUTO.jpg" alt='Sponsor of the day' />
			  <img width="140" height="140" src="/rwitc_upload/sponsor/gita.jpg" alt='Sponsor of the day' />
			  </div>
        </div> 
         <ul>        
                <!--<li>
                    <a href="/creativeCampaign.php">
                        <img src="/images/Icon10.jpg" alt="Indian Studbook" />
                        <span>Creative Campaign<br />
                              Mumbai Season 2011-2012                           
                        </span> 
                    </a>
                </li>-->            
                <li>
                    <a href="http://www.indianstudbook.com">
                        <img src="/images/Icon1.jpg" alt="Indian Studbook" />
                        <span>Stud Book Authority of India</span> 
                    </a>
                </li>                
                <li>
                    <a href="/viewPgArticles.php">
                        <img src="/images/prakashIcon.png" alt="The Prakash Gosavi Column" />
                        <span>The Prakash Gosavi Column</span>
                    </a>
                </li>
                <li>
                    <a class="title" href="http://mumbairaces.com/">
                        <img src="/images/Icon2.jpg" alt="VIDEO ARCHIVE" />
                        <span>Video Archive</span>
                    </a>                        
                </li>
                <li>
                    <a class="title" href="/performanceProfile.php">
                        <img src="/images/Icon3.jpg" alt="Performance Profle" />
                        <span>Performance Profile Of Horses</span>
                    </a> 
                </li>
                <li>
                    <a class="title" href="/horsesInTraining.php">
                        <img src="/images/Icon4.jpg" alt="Horses In Training" />                
                        <span>Horses In Training</span>
                    </a>
                </li>
                <li>
                    <a class="title" href="/horseRatings.php">
                        <img src="/images/Icon5.jpg" alt="Rating of all Horses" />
                       <span> Rating Of All Horses</span>
                    </a>
                </li>
                 <li>
                    <a href="http://www.fourseasons.com/mumbai/">
                        <img src="/images/four_seasons.jpg" alt="FOUR SEASONS" />                
                        <span>Four Seasons Hotel, Mumbai</span>
                    </a>
                </li>
                <li>
                    <a class="title" href="http://www.horsein.com/">
                        <img src="/images/Icon6.jpg" alt="WEBPORTAL FOR OWNERS / TRAINERS " />
                        <span>Webportal For Owners / Trainers</span> 
                    </a>
                </li>
                <li>
                    <a class="title" href="/dividends.php">
                        <img src="/images/Icon7.jpg" alt="TOTE DIVIDENDS" />
                        <span>Tote Dividends</span>
                    </a>
                </li>
                <li>
                    <a class="title" href="/moneyLeaders.php">
                        <img src="/images/Icon8.jpg" alt="MONEY LEADERS " />
                        <span>Money Leaders</span>
                    </a>
                </li>
                <li>
                    <a class="title" href="/calendar.php">
                        <img src="/images/Icon9.jpg" alt="RACING FIXTURES " />
                        <span>Racing Fixtures</span>
                    </a>
                </li>
                <li>
                    <a class="title" href="http://rwitc.com/downloads/Prospectus.pdf">
                        <img src="/images/Icon10.jpg" alt="PROSPECTUS" />
                        <span>Prospectus</span>
                    </a>
                </li>
                <li>
                    <a class="title" href="/userfeedback.php">
                        <img src="/images/Icon11.jpg" alt="FEEDBACK" />
                        <span>Feedback</span>
                    </a> 
                </li>
                <!--
                    <li>
                        <span class="img"><img src="/images/Icon1.jpg" alt="STUD BOOK AUTHORITY OF INDIA" /></span>
                        <a class="titleNoMargin" href="http://www.indianstudbook.com/">STUD BOOK AUTHORITY<br />OF INDIA</a>
                    </li>
                    <li>
                          <span class="img"><img src="/images/Icon2.jpg" alt="VIDEO ARCHIVE" /></span>  
                          <a class="title" href="http://mumbairaces.com/">VIDEO ARCHIVE</a>
                    </li>
                    <li>
                          <span class="img"><img src="/images/Icon3.jpg" alt="Performance Profle" /></span>  
                          <a class="title" href="/performanceProfile.php">PERFORMANCE PROFILE<br />OF HORSES</a>
                    </li>
                    <li>
                        <span class="img"><img src="/images/Icon4.jpg" alt="Horses In Training" /></span>  
                         <a class="title" href="/horsesInTraining.php">HORSES IN TRAINING</a>
                    </li>
                    <li>
                        <span class="img"><img src="/images/Icon5.jpg" alt="Rating of all Horses" /></span>  
                         <a class="title" href="/horseRatings.php">RATING OF ALL HORSES</a>
                    </li>
                    <li>
                        <span class="img"><img src="/images/Icon6.jpg" alt="WEBPORTAL FOR OWNERS / TRAINERS " /></span>  
                         <a class="title" href="http://www.horsein.com/">WEBPORTAL FOR<br />OWNERS / TRAINERS </a>
                    </li>
                    <li>
                        <span class="img"><img src="/images/Icon7.jpg" alt="TOTE DIVIDENDS" /></span>  
                         <a class="title" href="/dividends.php">TOTE DIVIDENDS</a>
                    </li>
                    <li>
                        <span class="img"><img src="/images/Icon8.jpg" alt="MONEY LEADERS " /></span>  
                         <a class="title" href="/moneyLeaders.php">MONEY LEADERS</a>
                    </li>
                    <li>
                        <span class="img"><img src="/images/Icon9.jpg" alt="RACING FIXTURES " /></span>  
                         <a class="title" href="/calendar.php">RACING FIXTURES</a>
                    </li>
                    <li>
                        <span class="img"><img src="/images/Icon10.jpg" alt="PROSPECTUS" /></span>  
                         <a class="title" href="http://rwitc.com/downloads/Prospectus.pdf">PROSPECTUS</a>
                    </li>
                    <li>
                        <span class="img"><img src="/images/Icon11.jpg" alt="FEEDBACK" /></span>  
                         <a class="title" href="/userfeedback.php">FEEDBACK</a>
                    </li>
                    -->
                    <!--
                    <li>
                        <span class="img"><img src="/images/Icon12.jpg" alt="HOSPITALITY PARTNERS" /></span>  
                         <a class="title">HOSPITALITY PARTNERS</a>
                    </li>
                    -->
                </ul>
       </div>
       <!-- End : Right Col -->      
BOXES;
}


function endPage() {
$sponsorList = $_SERVER['DOCUMENT_ROOT'] ."/rwitc_upload/sponsor_scroll.inc";	
$sponsors = file_get_contents($sponsorList);
echo <<< FOOTER
	<div id="sponsorBlock" style="background: #FFFFFF;">
	    <marquee style="border: none; margin: 0; top: 0;" id="sponsorBlock" behavior="scroll" direction="left" scrollamount="1"  width="960">            
          $sponsors  
        </marquee>
	</div>	
</div>
</div>
</div>		 
</body>
</html>
FOOTER;
}
function pageClose() {

echo <<< FOOTER
</div>
</div>
</div>         
</body>
</html>
FOOTER;
}
}// class enf
