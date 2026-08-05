<?php
class Design {
var $jqueryJs,$js,$css,$bodyAttr;
function startPage($pageTitle,$articleData=array()) { 
    $metaOGAddl = '';
    $metaOGTitle = 'Royal Western India Turf Club (RWITC)';
if (count($articleData) > 0) {
    $metaOGTitle = $articleData['title'];
    $metaOGAddl = '<meta property="og:url" content="https://www.rwitc.com/viewArticles.php?id='.getParameterNumber('id').'" />';
    $metaOGAddl .= '<meta property="og:description" content="RWITC" />';
}
echo <<< PAGESTART
<!DOCTYPE html>
<html>
    
    <head>            
        <title>Home - RWITC</title>                
     <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <meta http-equiv="content-language" content="en">
    <meta name="author" content="RWITC">
    <meta name="description" content="Royal Western India Turf Club">
    <meta name="keywords" content="Royal Western India Turf Club, RWITC">
    <meta name="revisit-after" content="4 days">
    <meta property="og:title" content="{$metaOGTitle}" />
    <meta property="og:image" content="https://www.rwitc.com/images/newdesign/rwitcLogo.png" />
    {$metaOGAddl}
    <title>RWITC</title>    
    <script language="javascript" type="text/javascript" src="/js/jquery-1.4.2.min.js"></script>
    <script language="javascript" type="text/javascript" src="/js/jquery.cycle.all.js"></script>
    <script type="text/javascript" src="/js/superfish.js"></script>
    <script type="text/javascript" src="/js/supersubs.js"></script> 
    <script type="text/javascript" src="/js/jquery.vticker-min.js"></script> 
    <script type="text/javascript" src="/js/jquery.marquee.js"></script> 
    <link rel="stylesheet" type="text/css" href="/css/invitation/rwitcNew.css">
    <link rel="stylesheet" type="text/css" href="/css/newdesign/superfish.css" media="screen">
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
        HCTObj('.daySponsor').cycle({
                    fx: 'fade',
                    speed : 1000 ,
                    width: '80px',
                    height: '80px'
        });
        {$this->jqueryJs}
        HCTObj('#username').focus(function(){
            if (HCTObj(this).val() == "Email") {
                HCTObj(this).val('');
            } 
        });
        HCTObj('#username').focusout(function(){
            if (HCTObj.trim(HCTObj(this).val()) == '') {
                HCTObj(this).val('Email');
            }   
        });
        
        HCTObj('#password').focus(function(){
            if (HCTObj(this).val() == "Password") {
                HCTObj(this).val('');
            } 
        });
        HCTObj('#password').focusout(function(){
            if (HCTObj.trim(HCTObj(this).val()) == '') {
                HCTObj(this).val('Password');
            }   
        });
        
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
<body>  				
PAGESTART;
}

function writeLogoTickerMenu() { 
    $dbObj = new dbTool();            
    require_once('race.class.php');   
    $dbObj = new dbTool();
    $raceObj = new Racedata($dbObj); 
    $galleryDate = $raceObj->getMaxDate('racedate','gallery');
    $filePath = $_SERVER['DOCUMENT_ROOT'] ."/rwitc_upload/ticker.inc";
    $imagesPath = $_SERVER['DOCUMENT_ROOT'] ."/rwitc_upload/banner.inc";
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
echo <<<TICKER
    <div id="pageWrapper">
        <div id="page">            
            <div id="header">
                <div id="rwitc">
                    <!--<a href="/greencircle.php">
                        <img src="/images/greencircle.jpg" />
                    </a>-->
                    
                    <!-- <a id="rwitcTv" href="/rwitc-tv"><img height="75" src="/images/invitation/rwitc-tv.png" alt="Rwitc Tv" /></a> -->
                    <a id="rwitcTv" style="position:relative;" href="http://www.smalldesigncompany.com/RWITC/Invitation_cup/">
                        <img src="/images/invitation/interviews.png" alt="Rwitc Tv" height="75" />
                        <img src="/images/invitation/new-video.png" style="position:absolute; top:-15px;right:40px;z-index:99;" />
                    </a>
				</div>
            
                <div id="liveRace">
                <!--<a href="http://switchmedia.live-s.cdn.bitgravity.com:1935/content:cdn-live/switchmedia/live/feed002"><img src="/images/newdesign/liveRace.png" /></a>-->
                    <a style="margin-left:15px;" href="/rwitc-tv/liverace.php"><img src="/images/invitation/live-race.png" /></a><br />
                    <a style="font-size:14px;" href="http://switchmedia.live-s.cdn.bitgravity.com:1935/content:cdn-live/switchmedia/live/feed002">Live Race - Alternate Link</a>
				
				</div>
                <div id="interviews">
                    <img src="/images/invitation/logo-omkar.jpg" alt="omkar" height="110" />
                </div>
                <div id="registerBlock">
                    <!--<form name="loginFrm" method="post" action="/login.php">  
                        <ul>
                            <li><h3>Login at the RWITC</h3></li>
                            <li>Register to get Free Racing Updates in your mailbox</li>
                            <li>
                                <input type="text" name="email" id="username" class="textbox floatLeft" value="Email" />
                                <input type="password" name="password" id="password" class="textbox floatRight" value="Password" />
                            </li>
                            <li>
                                <a href="/register.php" class="register floatLeft">New User? / Sign Up</a>
                                
                                <input type="submit" name="submit" class="submit floatRight" value="Submit" />
                            </li>
                        </ul>
                        <input type="hidden" name="q" value="login-user" />            
                    </form>    -->
                    <div class="sponsor" id="sponsor">
                        <style type="text/css">
                            .daySponsor { height: 100px !important; margin-left: 30px; }
                        </style>
                        <div class="daySponsor" style="height: 100px !important;">
						   <img width="100" height="100" src="/rwitc_upload/sponsor/NEW WADIA GROUP.jpg" alt='Sponsor of the day' />
                           <!--<img width="100" height="100" src="/rwitc_upload/sponsor/OMKAR-NEW.jpg" alt='Sponsor of the day' />-->
                           <img width="100" height="100" src="/rwitc_upload/sponsor/TAOI-NEW.jpg" alt='Sponsor of the day' />
                           <img width="100" height="100" src="/rwitc_upload/sponsor/JEPL.jpg" alt='Sponsor of the day' />
                           <img width="100" height="100" src="/rwitc_upload/sponsor/DELTIN CASINO-NEW.jpg" alt='Sponsor of the day' />
                           <img width="100" height="100" src="/rwitc_upload/sponsor/USHA STUD-NEW.jpg" alt='Sponsor of the day' />
                           <img width="100" height="100" src="/rwitc_upload/sponsor/PERCEPT PROP-NEW.jpg" alt='Sponsor of the day' />
                           <img width="100" height="100" src="/rwitc_upload/sponsor/BROADACRES-NEW.jpg" alt='Sponsor of the day' />
                           <img width="100" height="100" src="/rwitc_upload/sponsor/RCTC-NEW.jpg" alt='Sponsor of the day' />
                           <img width="100" height="100" src="/rwitc_upload/sponsor/MRC-NEW.jpg" alt='Sponsor of the day' />
                           <img width="100" height="100" src="/rwitc_upload/sponsor/BTC-NEW.jpg" alt='Sponsor of the day' />
                           <img width="100" height="100" src="/rwitc_upload/sponsor/HRC-NEW.jpg" alt='Sponsor of the day' />
                           <img width="100" height="100" src="/rwitc_upload/sponsor/MYSORE-NEW.jpg" alt='Sponsor of the day' />
                           <img width="100" height="100" src="/rwitc_upload/sponsor/DRC-NEW.jpg" alt='Sponsor of the day' />
						</div>
                    </div>
                </div>
                <div id="logo">
                    <a href="/index.php"><img src="/images/newdesign/rwitcLogo.png" /></a>
                </div>
                <div id="instagram">
                    <a href="http://www.instagram.com/rwitc"><img src="/images/newdesign/insta-black.png" /></a>
                </div>
                <div id="facebook">
                    <a href="http://www.facebook.com/pages/Royal-Western-India-Turf-Club/165292783507507?v=wall"><img src="/images/invitation/fb.png" /></a>
                </div> 
                <div id="twitter">
                    <a href="http://twitter.com/rwitcmumbai"><img src="/images/invitation/twt.png" /></a>
                </div>
            </div>
            <div id="bannerBlock"> 
                <div id="banner">
                      <img src="/images/banner-invitation.png" />  
                      $imagesList
                </div>   
                <div id="tickerWrapper">
                    <div id="tickerHeader">TOP STORIES</div>
                    <div id="ticker">
                        $tickerHTML;
                    </div>
                </div>
                <a href="/photoGallery.php?date=$galleryDate" id="viewPhotos"><img src="/images/viewPhoto.png" /></a>
            </div> 
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
                            <li><a href="/sweepstakes.php">Sweepstake Entries</a></li>
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
<ul id="rightLinks">  
    <li>
        <a href="/liveresults.php" class="colour1">
            <img src="/images/newdesign/live.png" alt="">
            <span class="rightTitle">LIVE RESULTS</span>
        </a>
    </li>
    <li>
        <a href="/rwitc_upload/static/live/MEDIATIPS.HTM" class="colour2">
            <img src="/images/newdesign/media.png" alt="">
            <span class="rightTitle">MEDIA TIPS</span>
        </a>
    </li>                            
    <li>
        <a href="/rwitc_upload/static/live/UPDATES.HTM" class="colour3">
            <img src="/images/newdesign/updates.png" alt="">
            <span class="rightTitle">UPDATES</span>
        </a>
    </li>
    <li>
        <a href="/rwitc_upload/static/live/ODDS.HTM" class="colour4">
            <img src="/images/newdesign/odds.png" alt="">
            <span class="rightTitle">CHANGING ODDS</span>
        </a>
    </li>
</ul>
MEDIA_TIPS;
}
if (ODDSBOX) {
echo <<< ODDS_CONTENT
                <!--<div class="square">
                         <div class="squareHeader">
                            <img src='/images/tv.gif' alt='Race Telecast' />
                            <span style="padding-top: 8px;font-size:12px;"><a href="http://www.mumbairaces.com/live.php">Race Telecast</a></span>
                        </div>
                 </div>-->
                 <br />      
                <div class='liveBox'>
                    <div class='liveBoxContent'>
                         <div id="odds">
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
                         <div id="final_div">
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
self::rightSponsor();
ECHO <<< BOXES
            <ul id="rightLinks">
			    <!--<li>
                                <a target="_blank"href="http://www.agpworld.com" class="colour1">
                                    <img src="/images/newdesign/video.png" alt="">
                                    <span class="rightTitle">The AGP World</span>
                                </a>
                                <span class="rightNew">&nbsp;</span>
                            </li>
				<li>
                                <a target="_blank" href="http://in.bookmyshow.com/plays/a-peasant-of-el-salvador/ET00016687" class="colour2">
                                    <img src="/images/newdesign/android.png" alt="">
                                    <span class="rightTitle">TURF*THEATRE*FESTIVAL</span>
									<span class="rightTitle">Click to book ticket</span>
                                </a>
                                <span class="rightNew">&nbsp;</span>
                            </li>		
                <li>
                                <a target="_blank" href="http://goodtimes.ndtv.com/video/videolist.aspx?vid=327870" class="colour1">
                                    <img src="/images/newdesign/training.png" alt="">
                                    <span class="rightTitle">Horsing around with Milan Luthria on NDTV Goodtimes</span>
                                </a>
                            </li>	
				<li>
                                <a target="_blank" href="http://www.archk2014.com/en/2014-arc-video-05052014.aspx" class="colour3">
                                    <img src="/images/newdesign/training.png" alt="">
                                    <span class="rightTitle">Asian Racing Conference 2014 Videos of the Conference</span>
                                </a>
                                <span class="rightNew">&nbsp;</span>
                            </li>
				
				
				
				<li>
                                <a target="_blank" href="/rwitc-singapore.php" class="colour4">
                                    <img src="/images/newdesign/video.png" alt="">
                                    <span class="rightTitle">Watch The RWITC Cup run at Singapore on 6th April 2014</span>
                                </a>
                                <span class="rightNew">&nbsp;</span>-->
                            </li>
                
				
                <!--<div id="rwitc">
                        <a href="/greencircle.php">
                        <img src="/images/greencircle-2.jpg" />
                    </a>
					</div>-->
				 <!--<div id="rwitc">
                        
                        <img src="/images/Horsepower-Logo.jpg" />
                    </a>
                </div>-->
                    
				<li>
                    <a class="colour3" href="https://www.youtube.com/watch?v=j-AfMbVAwBc">
                        <img alt="Availibility Calendar" src="/images/newdesign/totedividends.png">
                        <span class="rightTitle">Invitation Cup Weekend Seminar - Part I</span>
                    </a>
                    
                </li>
				
								<li>
                    <a class="colour2" href="/availibilityCalendar.php">
                        <img alt="Availibility Calendar" src="/images/newdesign/totedividends.png">
                        <span class="rightTitle">Grounds available for Schools & Colleges</span>
                    </a>
                    
                </li>
                <li>
                    <a href="https://play.google.com/store/apps/details?id=com.rwitc.mobileweb" class="colour1">
                        <img src="/images/newdesign/android.png" alt=">RWITC App on Google Play Store">
                        <span class="rightTitle">RWITC App on Google Play Store</span>
                    </a>
                    
                </li>
                <li>
                    <a href="https://itunes.apple.com/us/app/rwitc/id619375717?ls=1&mt=8" class="colour2">
                        <img src="/images/newdesign/apple.png" alt="RWITC App on Apple Itunes">
                        <span class="rightTitle">RWITC App on Apple Itunes</span>
                    </a>
                    
                </li>
                <li>
                                <a href="http://appworld.blackberry.com/webstore/content/26326879/" class="colour3">
                                    <img style="padding-top:10px;" src="/images/newdesign/blackberry.png" alt="RWITC App on Blackberry Appworld">
                                    <span class="rightTitle">RWITC App on Blackberry Appworld</span>
                                </a>
                                
                            </li>
                <li>
                    <a href="/app-qr.php" class="colour4">
                        <img src="/images/newdesign/qr.png" alt="QR Code for RWITC App">
                        <span class="rightTitle">QR Code for RWITC App</span>
                    </a>
                    
                </li>
                <li>
                    <a href="/performanceProfile.php" class="colour3">
                        <img src="/images/newdesign/performance.png" alt="PERFORMANCE PROFILE OF HORSES">
                        <span class="rightTitle">Performance Profile of Horses</span>
                    </a>
                </li>
                <li>
                    <a href="http://www.horsein.com/" class="colour2">
                        <img src="/images/newdesign/owners.png" alt="WEBPORTAL FOR OWNERS / TRAINERS">
                        <span class="rightTitle">Webportal for Owners/Trainers</span>
                    </a>
                </li>
                <li>
                    <a href="/calendar.php" class="colour3">
                        <img src="/images/newdesign/fixtures.png" alt="RACING FIXTURES">
                        <span class="rightTitle">Racing Fixtures</span>
                    </a>
                </li>
                <li>
                    <a href="/horseRatings.php" class="colour4">
                        <img src="/images/newdesign/rating.png" alt="RATINGS OF ALL HORSES">
                        <span class="rightTitle">Ratings of all Horses</span>
                    </a>
                </li>
                <li>
                    <a href="/horsesInTraining.php" class="colour1">
                        <img src="/images/newdesign/training.png" alt="HORSES IN TRAINING">
                        <span class="rightTitle">Horses in Training</span>
                    </a>
                </li>
                <li>
                    <a href="/dividends.php" class="colour2">
                        <img src="/images/newdesign/totedividends.png" alt="TOTE DIVIDENDS">
                        <span class="rightTitle">Tote Dividends</span>
                    </a>
                </li>
                <li>
                    <a href="http://www.indianstudbook.com/" class="colour3">
                        <img src="/images/newdesign/stud_book.png" alt="INDIAN STUD BOOK">
                        <span class="rightTitle">Indian Stud Book</span>
                    </a>
                </li>
                <!--<li>
                    <a href="/viewPgArticles.php" class="colour4">
                        <img src="/images/newdesign/guest_column.png" alt="PRAKASH GOSAVI COLUMN">
                        <span class="rightTitle">PRAKASH GOSAVI COLUMN</span>
                    </a>
                </li>-->
                <li>
                    <a href="/moneyLeaders.php" class="colour1">
                        <img src="/images/newdesign/money_lenders.png" alt="MONEY LEADERS">
                        <span class="rightTitle">Money Leaders</span>
                    </a>
                </li>                
                <li>
                    <a href="/downloads/Prospectus.pdf" class="colour2">
                        <img src="/images/newdesign/prospectus.png" alt="PROSPECTUS">
                        <span class="rightTitle">Prospectus</span>
                    </a>
                </li>
                <li>
                    <a href="http://www.mumbairaces.com/" class="colour3">
                        <img src="/images/newdesign/video.png" alt="">
                        <span class="rightTitle">Video Archives</span>
                    </a>
                </li>
                <li>
                    <a href="/feedback.php" class="colour2">
                        <img src="/images/newdesign/feedback.png" alt="">
                        <span class="rightTitle">Feedback</span>
                    </a>
                </li>
            </ul>                     
BOXES;
self::rightFooter();    
}

function rightSponsor() {
 echo <<< RIGHT_FOOTER
          

 	<!--<a style="display:block;text-align:center;" href="http://smalldesigncompany.com/RWITC/The_McDowell_Indian_Derby/">
                        <img src="/images/signature_derby_special2.png" />
                    </a>
    <div class="sponsor" id="sponsor">
                <div class="sponsorLabel">SPONSOR OF THE DAY</div>
                <div class="daySponsor">
				   <img width="125" height="125" src="/rwitc_upload/sponsor/NEW WADIA GROUP.jpg" alt='Sponsor of the day' />
				   <img width="125" height="125" src="/rwitc_upload/sponsor/OMKAR-NEW.jpg" alt='Sponsor of the day' />
				   <img width="125" height="125" src="/rwitc_upload/sponsor/TAOI-NEW.jpg" alt='Sponsor of the day' />
				   <img width="125" height="125" src="/rwitc_upload/sponsor/JEPL.jpg" alt='Sponsor of the day' />
				   <img width="125" height="125" src="/rwitc_upload/sponsor/DELTIN CASINO-NEW.jpg" alt='Sponsor of the day' />
				   <img width="125" height="125" src="/rwitc_upload/sponsor/USHA STUD-NEW.jpg" alt='Sponsor of the day' />
				   <img width="125" height="125" src="/rwitc_upload/sponsor/PERCEPT PROP-NEW.jpg" alt='Sponsor of the day' />
				   <img width="125" height="125" src="/rwitc_upload/sponsor/BROADACRES-NEW.jpg" alt='Sponsor of the day' />
				   <img width="125" height="125" src="/rwitc_upload/sponsor/RCTC-NEW.jpg" alt='Sponsor of the day' />
				   <img width="125" height="125" src="/rwitc_upload/sponsor/MRC-NEW.jpg" alt='Sponsor of the day' />
				   <img width="125" height="125" src="/rwitc_upload/sponsor/BTC-NEW.jpg" alt='Sponsor of the day' />
				   <img width="125" height="125" src="/rwitc_upload/sponsor/HRC-NEW.jpg" alt='Sponsor of the day' />
				   <img width="125" height="125" src="/rwitc_upload/sponsor/MYSORE-NEW.jpg" alt='Sponsor of the day' />
				   <img width="125" height="125" src="/rwitc_upload/sponsor/DRC-NEW.jpg" alt='Sponsor of the day' />
				</div>
				             </div>-->
RIGHT_FOOTER;
}

function rightFooter(){
echo <<< RIGHT_FOOTER
    
            
RIGHT_FOOTER;
}

function rightAreaHome() {


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
<ul id="rightLinks">  
    <li>
        <a href="/liveresults.php" class="colour1">
            <img src="/images/newdesign/live.png" alt="">
            <span class="rightTitle">LIVE RESULTS</span>
        </a>
    </li>
    <li>
        <a href="/rwitc_upload/static/live/MEDIATIPS.HTM" class="colour2">
            <img src="/images/newdesign/media.png" alt="">
            <span class="rightTitle">MEDIA TIPS</span>
        </a>
    </li>                            
    <li>
        <a href="/rwitc_upload/static/live/UPDATES.HTM" class="colour3">
            <img src="/images/newdesign/updates.png" alt="">
            <span class="rightTitle">UPDATES</span>
        </a>
    </li>
    <li>
        <a href="/rwitc_upload/static/live/ODDS.HTM" class="colour4">
            <img src="/images/newdesign/odds.png" alt="">
            <span class="rightTitle">CHANGING ODDS</span>
        </a>
    </li>
</ul>
MEDIA_TIPS;
}
if (ODDSBOX) {
echo <<< ODDS_CONTENT
                <!--<div class="square">
                         <div class="squareHeader">
                            <img src='/images/tv.gif' alt='Race Telecast' />
                            <span style="padding-top: 8px;font-size:12px;"><a href="http://www.mumbairaces.com/live.php">Race Telecast</a></span>
                        </div>
                 </div>-->
                 <br />      
                <div class='liveBox'>
                    <div class='liveBoxContent'>
                         <div id="odds">
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
                         <div id="final_div">
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
self::rightSponsor();
ECHO <<< BOXES
       <ul id="rightLinks"> 
                                 
	   <!--<div id="rwitc">
                        <a href="/greencircle.php">
                        <img src="/images/greencircle-2.jpg" />
                    </a>
					</div>-->
                            <!--<div id="rwitc">
                        
                        <img src="/images/Horsepower-Logo.jpg" />
                    </a>
                </div>
							<li>
                                <a target="_blank" href="http://in.bookmyshow.com/plays/a-peasant-of-el-salvador/ET00016687" class="colour2">
                                    <img src="/images/newdesign/android.png" alt="">
                                    <span class="rightTitle">TURF*THEATRE*FESTIVAL</span>
									<span class="rightTitle">Click to book ticket</span>
                                </a>
                                <span class="rightNew">&nbsp;</span>
                            </li>					
						    <li>
                                <a target="_blank" href="http://goodtimes.ndtv.com/video/videolist.aspx?vid=327870" class="colour1">
                                    <img src="/images/newdesign/training.png" alt="">
                                    <span class="rightTitle">Horsing around with Milan Luthria on NDTV Goodtimes</span>
                                </a>
                                
                            </li>								
							<li>
                                <a target="_blank" href="http://www.archk2014.com/en/2014-arc-video-05052014.aspx" class="colour3">
                                    <img src="/images/newdesign/training.png" alt="">
                                    <span class="rightTitle">Asian Racing Conference 2014 Videos of the Conference</span>
                                </a>
                            </li>
						    <li>
                                <a target="_blank" href="/rwitc-singapore.php" class="colour4">
                                    <img src="/images/newdesign/video.png" alt="">
                                    <span class="rightTitle">Watch The RWITC Cup run at Singapore on 6th April 2014</span>
                                </a>
                                <span class="rightNew">&nbsp;</span>
                            </li> -->
                            <li>
                    <a class="colour3" href="https://www.youtube.com/watch?v=j-AfMbVAwBc">
                        <img alt="Availibility Calendar" src="/images/newdesign/totedividends.png">
                        <span class="rightTitle">Watch the Seminar held on Friday, 27th Feb 2015</span>
                    </a>
                    
                </li>
                            <li>
                                <a class="colour2" href="/availibilityCalendar.php">
                                    <img alt="Availibility Calendar" src="/images/newdesign/totedividends.png">
                                    <span class="rightTitle">Grounds available for Schools & Colleges</span>
                                </a>
                                
                            </li>                                 
                            
                            <li>
                                <a href="https://play.google.com/store/apps/details?id=com.rwitc.mobileweb" class="colour1">
                                    <img src="/images/newdesign/android.png" alt=">RWITC App on Google Play Store">
                                    <span class="rightTitle">RWITC App on Google Play Store</span>
                                </a>
                                
                            </li>
                            <li>
                                <a href="https://itunes.apple.com/us/app/rwitc/id619375717?ls=1&mt=8" class="colour2">
                                    <img src="/images/newdesign/apple.png" alt="RWITC App on Apple Itunes">
                                    <span class="rightTitle">RWITC App on Apple Itunes</span>
                                </a>
                                
                            </li>
                            <li>
                                <a href="http://appworld.blackberry.com/webstore/content/26326879/" class="colour1">
                                    <img style="padding-top:10px;" src="/images/newdesign/blackberry.png" alt="RWITC App on Blackberry Appworld">
                                    <span class="rightTitle">RWITC App on Blackberry Appworld</span>
                                </a>
                                
                            </li>
                            <li>
                                <a href="/app-qr.php" class="colour2">
                                    <img src="/images/newdesign/qr.png" alt="QR Code for RWITC App">
                                    <span class="rightTitle">QR Code for RWITC App</span>
                                </a>
                                
                            </li>                            
                            <!--<li>
                                <a href="http://www.mumbairaces.com/" class="colour1">
                                    <img src="/images/newdesign/video.png" alt="">
                                    <span class="rightTitle">VIDEO ARCHIVES</span>
                                </a>
                            </li>-->
                            <li>
                                <a href="/feedback.php" class="colour3">
                                    <img src="/images/newdesign/feedback.png" alt="">
                                    <span class="rightTitle">FEEDBACK</span>
                                </a>
                            </li>
                        </ul>                                 
BOXES;
self::rightFooter();
}


function endPage() {
$sponsorList = $_SERVER['DOCUMENT_ROOT'] ."/rwitc_upload/sponsor_scroll.inc";	
$sponsors = file_get_contents($sponsorList);
echo <<< FOOTER
    <h3>SPONSORS</h3>
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
}// class enf
