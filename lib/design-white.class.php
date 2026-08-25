<?php

class Design
{

    var $jqueryJs, $js, $css, $bodyAttr;

    function startPage($pageTitle, $articleData = array())
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $url = 'https://' . $_SERVER['HTTP_HOST'] . '' . $_SERVER['REQUEST_URI'];

        $metaOGUrl = $url; //'https://www.rw1.space2let.com';

        $metaOGTitle = 'Royal Western India Turf Club (RWITC)';

        $metaOGDescription = 'Royal Western India Turf Club (RWITC)';

        $metaOGImage = 'https://rw1.space2let.com/~rwitc/images/newdesign/rwitcLogo.png';

        if (count($articleData) > 0) {

            //$articleData['title'] = 'Test Title';

            $metaOGUrl = 'https://rw1.space2let.com/~rwitc/viewArticles.php?id=' . getParameterNumber('id');

            $metaOGTitle = $articleData['title'];

            $metaOGDescription = $articleData['title'];

            $metaOGImage = 'https://rw1.space2let.com/~rwitc/images/newdesign/rwitcLogo.png';
        }

        $main_file = 'assets/css/main.css';

        //echo $_SERVER['DOCUMENT_ROOT'];exit;

        //$mtime = filemtime($_SERVER['DOCUMENT_ROOT'] .'/rwitc_website/assets/css/main.css');

        $mtime = filemtime(DIR_BASE . 'assets/css/main.css');

        $main_css = $main_file . '?version=' . $mtime;

        $base_href = BASE_HREF;

        echo <<< PAGESTART

<!DOCTYPE html>

<html>



    <head>

        <title>Home - RWITC</title>

    <base href="{$base_href}" />

    <title>RWITC</title>

     <link rel="shortcut icon" type="image/x-icon" href="favicon.ico" />

    <meta name="google-site-verification" content="NmaRSR1lflAt0uctM01UOUmyghX9SWYL7bPsu_1b78Q" />

    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

    <meta http-equiv="content-language" content="en">

    <meta name="author" content="RWITC">

    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <meta name="description" content="Royal Western India Turf Club">

    <meta name="keywords" content="Royal Western India Turf Club, RWITC">

    <meta name="revisit-after" content="4 days">

    <meta name="google-site-verification" content="40YqU8CUU2lplCO_m0t500_1r2UDSmkQIeXtJCfQQGw" />



    <meta property="og:url" content="{$metaOGUrl}" />

    <meta property="og:type" content="article" />

    <meta property="og:title" content="{$metaOGTitle}" />

    <meta property="og:description" content="{$metaOGDescription}" />

    <meta property="og:image" content="{$metaOGImage}" />

    

    <!-- <script type="text/javascript" src="assets/gz_files/rwitc.min.js"></script>

      <link rel="stylesheet" href="assets/gz_files/rwitc.min.css">

      <link href='https://fonts.googleapis.com/css?family=Roboto+Condensed' rel='stylesheet' type='text/css'>

      <link rel="stylesheet" href="assets/css/main.min.css"> -->

    <link rel="stylesheet" href="assets/css/owl.carousel.css">



      <script type="text/javascript" src="//code.jquery.com/jquery-1.10.2.js"></script>

      <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">

      <link href="assets/css/jquery.countdown.css" rel="stylesheet" type="text/css">

    

    <script type="text/javascript" src="js/superfish.js"></script>

    <script type="text/javascript" src="js/supersubs.js"></script>

    <script type="text/javascript" src="js/jquery.vticker-min.js"></script>

       


       <script type="text/javascript" src="assets/js/jquery.marquee.js"></script> 



    <script type="text/javascript" src="assets/js/owl.carousel.min.js"></script>

    <script type="text/javascript" src="assets/js/flipclock.min.js"></script>

    <script type="text/javascript" src="assets/js/count-down.js"></script>

    <script type="text/javascript" src="assets/min_js/enhance.min.js"></script>

    <script type="text/javascript" src="assets/js/jquery.countdown.min.js?v=1.0.0.0"></script>

    <!-- Why is this jquery included, when it is not used -->

    <!-- <script language="javascript" type="text/javascript" src="/js/jquery.cycle.all.js"></script> -->

	<link rel="manifest" href="manifest.json">

    <link rel="apple-touch-icon" href="logo_rwitc.jpeg">

    <script src="index.js"></script>





    <script type="text/javascript" src="assets/js/bootstrap.min.js"></script>



       <link rel="stylesheet" href="assets/css/bootstrap.min.css">



           <link rel="stylesheet" href="assets/css/flipclock.css">





    <link rel="stylesheet" type="text/css" href="assets/css/superfish.css" media="screen">

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">

    <!-- Custom styles for our template -->

    <link rel="stylesheet" href="assets/css/bootstrap-theme.css" media="screen" >

    <link href='https://fonts.googleapis.com/css?family=Roboto+Condensed' rel='stylesheet' type='text/css'>

    <link rel="stylesheet" href="{$main_css}">

      {$this->js}

      {$this->css}

   <script type="text/javascript">

     $(window).load(sidebar_sticky_update);

	 $(window).resize(sidebar_sticky_update);



    function sidebar_sticky_update(){

        var viewportWidth = $(window).width();

        console.log(viewportWidth);

        if (viewportWidth < 500) {

            $('.weather_class').hide();

        } else {

            $('.weather_class').show();

        }

	}

   /*HCTObj = new jQuery.noConflict();

   HCTObj(function(){





});

    */

    $(function(){

   function rwAjaxNav(url) {
       $.get(url, function(html) {
           var newContent = $(html).find('#leftArea').html();
           if (newContent) {
               $('#leftArea').html(newContent);
               window.history.pushState({}, '', url);
           } else {
               window.location.href = url; // fallback: normal navigation
           }
       }).fail(function() {
           window.location.href = url; // fallback agar AJAX fail ho
       });
       return false;
   }
   window.rwAjaxNav = rwAjaxNav;

   $(".dropdown-menu > li > a.trigger").on("click",function(e){

        var current=$(this).next();

        var grandparent=$(this).parent().parent();

        if($(this).hasClass('left-caret')||$(this).hasClass('right-caret'))

            $(this).toggleClass('right-caret left-caret');

        grandparent.find('.left-caret').not(this).toggleClass('right-caret left-caret');

        grandparent.find(".sub-menu:visible").not(current).hide();

        current.toggle();

        e.stopPropagation();

    });

    $(".dropdown-menu > li > a:not(.trigger)").on("click",function(){

        var root=$(this).closest('.dropdown');

        root.find('.left-caret').toggleClass('right-caret left-caret');

        root.find('.sub-menu:visible').hide();

    });

           /* $('#ticker').cycle({

                fx:      'scrollLeft',

                speed : 2000,

                timeout:  6000

            });*/

            /*$('#banner').cycle({

                fx:      'fade',

                speed : 1000 ,

                width: 700,

                height: 250

            });*/

       $('marquee#sponsorBlock').marquee('pointer').mouseover(function () {

            $(this).trigger('stop');

        }).mouseout(function () {

            $(this).trigger('start');

        }).mousemove(function (event) {

            if ($(this).data('drag') == true) {

                this.scrollLeft = $(this).data('scrollX') + ($(this).data('x') - event.clientX);

            }

        }).mousedown(function (event) {

           $(this).data('drag', true).data('x', event.clientX).data('scrollX', this.scrollLeft);

        }).mouseup(function () {

            $(this).data('drag', false);

        });

       /* $('.daySponsor').cycle({

                    fx: 'fade',

                    speed : 1000 ,

                    width: '125px',

                    height: '125px'

        });*/

        {$this->jqueryJs}

        $('#username').focus(function(){

            if ($(this).val() == "Email") {

                $(this).val('');

            }

        });

        $('#username').focusout(function(){

            if ($.trim($(this).val()) == '') {

                $(this).val('Email');

            }

        });



        $('#password').focus(function(){

            if ($(this).val() == "Password") {

                $(this).val('');

            }

        });

        $('#password').focusout(function(){

            if ($.trim($(this).val()) == '') {

                $(this).val('Password');

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





    function liveBoxes()
    {

        require_once("dbTools.php");

        $db = new dbTool();

        $raceObj = new Racedata($db);

        $linkbox = $raceObj->getconfig_datas(2);

        if ($linkbox == 'Y') {

            //if (LINKSBOX) {

            echo <<< MEDIA_TIPS



<ul id="rightLinkslive" class="clearfix rightLinkslive">

    <li>

        <a href="liveresults.php" class="colour1">

            <img src="/images/newdesign/live.png" alt="">

            <span class="rightTitle">LIVE RESULTS</span>

        </a>

    </li>



    <li>

        <a href="rwitc_upload/static/live/MEDIATIPS.HTM" class="colour2">

            <img src="/images/newdesign/media.png" alt="">

            <span class="rightTitle">MEDIA TIPS</span>

        </a>

    </li>

    <li>

        <a href="rwitc_upload/static/live/UPDATES.HTM" class="colour3">

            <img src="/images/newdesign/updates.png" alt="">

            <span class="rightTitle">UPDATES</span>

        </a>

    </li>

    <li>

        <a href="rwitc_upload/static/live/ODDS.HTM" class="colour4">

            <img src="/images/newdesign/odds.png" alt="">

            <span class="rightTitle">CHANGING ODDS</span>

        </a>

    </li>

</ul>



MEDIA_TIPS;
        }
    }



    function liveBox()
    {

        require_once("dbTools.php");

        $db = new dbTool();

        $raceObj = new Racedata($db);

        $oddsbox = $raceObj->getconfig_datas(1);

        $finalresbox = $raceObj->getconfig_datas(3);



        if ($oddsbox == 'Y') {

            //if (ODDSBOX) {

            echo <<< ODDS_CONTENT

                <!--<div class="square">

                         <div class="squareHeader">

                            <img src='images/tv.gif' alt='Race Telecast' />

                            <span style="padding-top: 8px;font-size:12px;"><a href="http://www.mumbairaces.com/live.php">Race Telecast</a></span>

                        </div>

                 </div>-->

                 <br />



                <script type='text/javascript'>

                function updateOdds() {

                      /*$(".odds").html('Updating...');*/

                      $.ajax({

                        type: 'GET',

                        url: 'live.php?mode=odds',

                        timeout: 2000,

                        /*beforeSend: function() {

                          $(".odds").html('Updating...');

                        },*/

                        success: function(data) {

                                /*console.log(data);*/

                            /*alert(data);*/

                                if (data!=0) {

                                    /*alert(data);*/

                                    $(".odds").html(data);

                } else {

                                    /*$(".odds").html('Updating...');*/

                    /*$(".odds").html('')*/;

                                }

                            window.setTimeout(updateOdds, 10000);

                        },

                        error: function (XMLHttpRequest, textStatus, errorThrown) {

                console.log('in error odds');

                            $(".odds").html('Retrying server...');

                            window.setTimeout(updateOdds, 10000);

                        }

                      });

                }

                 updateOdds();



                 </script>

ODDS_CONTENT;
        }

        if ($finalresbox == 'Y') {

            //if (FINALRESBOX) {

            echo <<< FINAL_CONTENT



                <script type='text/javascript'>

                function updateFinal() {

                  $(".final_div").html('Updating...');

                  $.ajax({

                    type: 'GET',

                    url: 'live.php?mode=final',

                    timeout: 2000,

                    success: function(data) {

                        console.log(data);

                        /*$(".final_div").html('');*/

            

                        $(".final_div").html(data);

                        /*$(".final_div").html('');*/

                        window.setTimeout(updateFinal, 120000);

                    },

                    error: function (XMLHttpRequest, textStatus, errorThrown) {

                      $(".final_div").html('Retrying server...');

                      window.setTimeout(updateFinal, 10000);

                    }

                  })

                }

               /*$('#final_div').vTicker( {

                showItems: 1,

                height: 170,

                animation: 'fade',

                mousePause: true,

                pause: 5000

                });

*/

                 updateFinal();

                 </script>

FINAL_CONTENT;
        }
    }

    function writeLogoTickerMenuHome()
    {

        require_once('race.class.php');



        echo <<<TICKER

   <!-- Fixed navbar -->

    <div id="header">



            <!-- <div class="container"> -->

                <div class="navbar-header logo">



                    <a class="navbar-brand" href="https://www.rwitc.com/"><img src="assets/images/logo.png"></a>

                </div>

                <div class="navbar-header logo" style="display:none;">

                    <a class="navbar-brand"><img src="assets/images/evening_logo.png"></a>

                </div>

                <div class="navbar-header logo" style="display:none;">

                    <a class="navbar-brand" href="https://www.rwitc.com/"><img src="assets/images/derby_logo.png"></a>

                </div>

                <div class="navbar-header logo" style="display:none;">

                    <p id="title"><b>The 2018 Kingfisher Ultra Indian Derby Weekend</b></p>

                </div>

                <div class="navbar-header logo" style="margin-left: 10%;">

                    <p id="title">

                        <b>ROYAL WESTERN INDIA TURF CLUB, LTD.</b> <br />

                        <b style="font-size: 20px;"></b>

                    </p>

                </div>

                <div class="navbar-header logo" style="display:none;">

                    <a class="navbar-brand" href="https://www.rwitc.com/"><img src="assets/images/derby_logo.png"></a>

                </div>

                <div class="navbar-collapse collapse ">

                    <!--<ul class="nav navbar-nav">

                        

                        <li><p id="title">ROYAL WESTERN INDIA TURF CLUB</p></li>

                        

                    </ul>-->

                    <div class="rightlogo">

                        <a href="feedback.php">

                            <img src="assets/images/rightlogo.png">

                        </a>

                    </div>

                    <div class="socialmedia">

                        <div style="text-align:center;">

                            <a href="https://www.facebook.com/rwitcmumbai/" target="_blank"><img src="assets/images/fb.png"></a>

                            <a href="https://twitter.com/rwitcmumbai" target="_blank"><img src="assets/images/twit.png"></a>

                            <a href="https://www.instagram.com/rwitcmumbai/" target="_blank"><img src="assets/images/insta.png"></a>

                        </div>

                        <div class="timer" style="display:none;">

                            <ul id="example">

                                <div class="start" style="font-weight:bold;">Countdown to the Indian Derby</div>

                                <li><span class="days">00</span><p class="days_text">Days</p></li>

                                <li class="seperator">:</li>

                                <li><span class="hours">00</span><p class="hours_text">Hours</p></li>

                                <li class="seperator">:</li>

                                <li><span class="minutes">00</span><p class="minutes_text">Minutes</p></li>

                                <li class="seperator">:</li>

                                <li><span class="seconds">00</span><p class="seconds_text">Seconds</p></li>

                            </ul>

                            <script>

                                /*

                                $(window).load(function(){

                                  $('.hcaption').hcaptions();

                                });

                                $(selector).hcaptions({

                                    effect: "fade"

                                });

                                */

                            </script>

                        </div>

                        <script type="text/javascript" src="assets/js/set_countdown.js"></script>

                    </div>



                <!-- </div> -->



            </div>





    </div>



TICKER;

        self::siteMenu();
    }



    function writeLogoTickerMenu()
    {

        require_once('race.class.php');

        $dbObj = new dbTool();

        $raceObj = new Racedata($dbObj);

        $galleryDate = $raceObj->getMaxDate('racedate', 'gallery');

        echo <<<TICKER

   <!-- Fixed navbar -->

     <div id="header">



                <div class="navbar-header logo">



                    <a class="navbar-brand" href="admin/dashboard.php"><img src="assets/images/logo.png"></a>

                </div>

                <div class="navbar-header logo" style="display:none;">

                    <a class="navbar-brand"><img src="assets/images/evening_logo.png"></a>

                </div>

                <div class="navbar-header logo" style="display:none;">

                    <a class="navbar-brand" href="https://www.rwitc.com/"><img src="assets/images/derby_logo.png"></a>

                </div>

                <div class="navbar-header logo" style="display:none;">

                    <p id="title"><b>The 2018 Kingfisher Ultra Indian Derby Weekend</b></p>

                </div>

                <div class="navbar-header logo" style="margin-left: 2%;">

                    <p id="title">

                        <b>ROYAL WESTERN INDIA TURF CLUB, LTD.</b>                        

                    </p>

                </div>

                <div class="navbar-header logo" style="display:none;">

                    <a class="navbar-brand" href="https://www.rwitc.com/"><img src="assets/images/derby_logo.png"></a>

                </div>

                <div class="navbar-collapse collapse ">

                    

                    <div class="rightlogo">

                        <a href="club/contactus.php"><img src="assets/images/rightlogo.png"></a>

                    </div>

                    <div class="socialmedia">

                         <div style="text-align:center;">

                            <a href="https://www.facebook.com/rwitcmumbai/" target="_blank"><img src="assets/images/fb.png"></a>

                            <a href="https://twitter.com/rwitcmumbai" target="_blank"><img src="assets/images/twit.png"></a>

                            <a href="https://www.instagram.com/rwitcmumbai/" target="_blank"><img src="assets/images/insta.png"></a>

                        </div>

                    </div>

                    <div class="timer" style="display:none;">

                            <ul id="example">

                                <div class="start" style="font-weight:bold;">Countdown to the Indian Derby</div>

                                <li><span class="days">00</span><p class="days_text">Days</p></li>

                                <li class="seperator">:</li>

                                <li><span class="hours">00</span><p class="hours_text">Hours</p></li>

                                <li class="seperator">:</li>

                                <li><span class="minutes">00</span><p class="minutes_text">Minutes</p></li>

                                <li class="seperator">:</li>

                                <li><span class="seconds">00</span><p class="seconds_text">Seconds</p></li>

                            </ul>

                            <script>

                                /*

                                $(window).load(function(){

                                  $('.hcaption').hcaptions();

                                });

                                $(selector).hcaptions({

                                    effect: "fade"

                                });

                                */

                            </script>

                        </div>

                        <script type="text/javascript" src="assets/js/set_countdown.js"></script>

                    </div>



                </div>







    </div>

TICKER;

        self::siteMenu();
    }



    function siteMenu()
    {

        $url = 'https://' . $_SERVER['HTTP_HOST'] . '' . $_SERVER['REQUEST_URI'];

        $metaOGUrl = $url; //'https://www.rwitc.com';

        $metaOGTitle = 'Royal Western India Turf Club (RWITC)';

        // Super Admin check - Admin ID 19 only
        $isSuperAdmin = (
            isset($_SESSION['uid']) &&
            (int)$_SESSION['uid'] === 19 &&
            isset($_SESSION['role']) &&
            strtoupper((string)$_SESSION['role']) === 'ADMIN'
        );

        $userMenu = '';

        if ($isSuperAdmin) {
            $userMenu = '
        <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                User <span class="caret"></span>
            </a>
            <ul class="dropdown-menu">
                <li><a href="admin/users.php">Users</a></li>
                <li><a href="admin/userGroup.php">User Group</a></li>
            </ul>
        </li>
    ';
        }

        echo <<<MENU

        <!-- Fixed navbar -->

        
        <div class="navbar navbar-inverse" style="display: none !important;" >

            <div class="navbar-header">

                <!-- Button for smallest screens -->

                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse"><span class="icon-bar"></span> <span class="icon-bar"></span> <span class="icon-bar"></span> </button>

            </div>

            <div class="navbar-collapse collapse">

                <ul class="dropdown" style="padding-left: 0px;">

                    <li class="dropdown">

                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">THE CLUB<span class="caret"></span></a>

                        <ul class="dropdown-menu">

                            <li>

                                <a href="club/aboutus.php" onclick="return rwAjaxNav('club/aboutus.php');">About RWITC</a>

                            </li>

                            <li>

                                <a href="club/vision-mission.php" onclick="return rwAjaxNav('club/vision-mission.php');">Vision &amp; Mission</a>

                            </li>

                            <li>

                                <a class="trigger right-caret">Organisation &amp; Management</a>

                                <ul class="dropdown-menu sub-menu">

                                    <li><a href="club/structure.php" onclick="return rwAjaxNav('club/structure.php');">Structure</a></li>

                                    <li><a href="club/managingCommittee.php" onclick="return rwAjaxNav('club/managingCommittee.php');">Managing Committee</a></li>

                                    <li><a href="club/stewardsOfclub.php" onclick="return rwAjaxNav('club/stewardsOfclub.php');">Stewards of the Club</a></li>

                                    <li><a href="club/boardofAppeal.php" onclick="return rwAjaxNav('club/boardofAppeal.php');">Board of Appeal</a></li>

                                    <!--<li><a href="club/--">Working Group</a></li>   -->

                                    <li><a href="club/working_group.php" onclick="return rwAjaxNav('club/working_group.php');">Working Group</a></li>

                                </ul>

                            </li>

                            <li>

                                <a class="trigger right-caret">History</a>

                                <ul class="dropdown-menu sub-menu">

                                    <li><a href="club/timeline.php" onclick="return rwAjaxNav('club/timeline.php');">Timeline / Major Events</a></li>

                                     <li><a href="club/bequeathingLegacy.php" onclick="return rwAjaxNav('club/bequeathingLegacy.php');">Bequeathing a Colonial Legacy</a></li>

                                </ul>

                            </li>

                            <li>

                                <a class="trigger right-caret">Charities</a>

                                <ul class="dropdown-menu sub-menu">

                                    <li><a href="club/charity.php" onclick="return rwAjaxNav('club/charity.php');">Charity Race Days</a></li>

                                </ul>

                            </li>

                            <li><a href="club/contributing.php" onclick="return rwAjaxNav('club/contributing.php');">Contributing to the Community</a></li>

                            <li><a href="club/responsible.php" onclick="return rwAjaxNav('club/responsible.php');">Responsible Gambling</a></li>

                            <li><a href="club/careers.php" onclick="return rwAjaxNav('club/careers.php');">Careers</a></li>

                            <li><a href="club/contactus.php" onclick="return rwAjaxNav('club/contactus.php');">Contact Us</a></li>

                        </ul>

                    </li>

                    <li class="dropdown">

                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">Horse Racing</a>

                        <ul class="dropdown-menu">

                            <li><a href="horseracing/Medication Rules.pdf">Medication Rules 2022</a></li>

                            <li><a href="horseracing/sweepstakes.php" onclick="return rwAjaxNav('horseracing/sweepstakes.php');">Sweepstake Entries</a></li>

                            <li><a href="horseracing/beginnersGuide.php" onclick="return rwAjaxNav('horseracing/beginnersGuide.php');">Beginners Guide</a></li>

                            <li><a href="horseracing/rulesOfRacing.pdf">Rules of Racing</a></li>

                            <li><a href="horseracing/racingCalendar.pdf">Racing Calendar</a></li>
                            <li><a href="horseracing/Memorandum & Articles of Association 2021.pdf">Memorandum & Articles of Association</a></li>							

                            <li><a href="horseracing/stewardsReport.php" onclick="return rwAjaxNav('horseracing/stewardsReport.php');">Notice From Stewards</a></li>

                            <li><a href="horseracing/readyreckoner.php" onclick="return rwAjaxNav('horseracing/readyreckoner.php');">Ready Reckoner</a></li>

                            <li><a href="horseracing/trainerStatistics.php" onclick="return rwAjaxNav('horseracing/trainerStatistics.php');">Trainer's Statistics</a></li>

                            <li><a href="horseracing/jockeyStatistics.php" onclick="return rwAjaxNav('horseracing/jockeyStatistics.php');">Jockey's Statistics</a></li>

                            <li><a href="horseracing/jockey_weights.php" onclick="return rwAjaxNav('horseracing/jockey_weights.php');">Jockey's Riding Weight</a></li>

                            <li><a href="horseracing/horsebodyWeight.php" onclick="return rwAjaxNav('horseracing/horsebodyWeight.php');">Body Weight of Horses</a></li>

                            <li><a href="horseracing/record_timings.php" onclick="return rwAjaxNav('horseracing/record_timings.php');">Record Timings</a></li>

                            <li><a href="horseracing/standard_timings.pdf">Standard Timings</a></li>

                            <!--<li><a href="raceHistory.php">History of Graded Races</a></li>-->

                            <li><a href="horseracing/saddleCloth.php" onclick="return rwAjaxNav('horseracing/saddleCloth.php');">Saddle Cloth Numbers</a></li>

                            <!-- <li><a href="horseracing/incomefromHeads.php">Income from Various Heads</a></li>-->

                         </ul>

                    </li>

                    <!--<li>

                        <a href="rwitc-tv" class="rwitctvmenu">RWITC-TV</a>    

                    </li>-->

                    <li class="dropdown">

                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">Betting &amp; Entertainment</a>

                        <ul class="dropdown-menu">

                            <li><a href="bettingentertainment/overview.php" onclick="return rwAjaxNav('bettingentertainment/overview.php');">Overview</a></li>

                            <li><a href="bettingentertainment/beginnersGuide.php" onclick="return rwAjaxNav('bettingentertainment/beginnersGuide.php');">Beginners Guide</a></li>

                            <li><a href="bettingentertainment/waggeringTerms.php" onclick="return rwAjaxNav('bettingentertainment/waggeringTerms.php');">Wagering Terms</a></li>

                            <li><a href="bettingentertainment/bettingPools.php" onclick="return rwAjaxNav('bettingentertainment/bettingPools.php');">Betting Pools</a></li>

                            <li><a href="bettingentertainment/bettingChannels.php" onclick="return rwAjaxNav('bettingentertainment/bettingChannels.php');">Betting Channels</a></li>

                            <li><a href="bettingentertainment/deductionNorms.php" onclick="return rwAjaxNav('bettingentertainment/deductionNorms.php');">Deduction Norms</a></li>

                           <!-- <li><a href="bettingentertainment/offcourseBettingCentres.php">Off-Course Betting Centres</a></li>-->

                        </ul>

                    </li>

                   <li class="dropdown">

                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">Membership</a>

                        <ul class="dropdown-menu">

                            <li><a href="membership/overview.php" onclick="return rwAjaxNav('membership/overview.php');">Overview</a></li>

                            <li><a href="membership/privileges.php" onclick="return rwAjaxNav('membership/privileges.php');">Membership Privileges</a></li>

                            <!--<li><a href="membership/olives.php">Olive</a></li>-->

                            <li><a href="membership/categories.php" onclick="return rwAjaxNav('membership/categories.php');">Categories</a></li>

                            <!--<li><a href="membership/rulesAndRegulations.php">Rules &amp; Regulations</a></li>-->

                            <li><a href="membership/lawnFacilities.php" onclick="return rwAjaxNav('membership/lawnFacilities.php');">Lawn &amp; Facilities Booking Forms</a></li>

                        </ul>

                    </li>

                    <li class="dropdown">

                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">Come Racing</a>

                        <ul class="dropdown-menu">

                            <li><a href="comeracing/overview.php" onclick="return rwAjaxNav('comeracing/overview.php');">Overview</a></li>

                            <li><a href="comeracing/mumbairacecourse.php" onclick="return rwAjaxNav('comeracing/mumbairacecourse.php');">Mumbai Race Course</a></li>

                            <li><a href="comeracing/puneracecourse.php" onclick="return rwAjaxNav('comeracing/puneracecourse.php');">Pune Race Course</a></li>

                            <li><a href="comeracing/howToGetThere.php" onclick="return rwAjaxNav('comeracing/howToGetThere.php');">How to get there</a></li>

                            <li><a href="comeracing/services.php" onclick="return rwAjaxNav('comeracing/services.php');">Race Course Services &amp; Others</a></li>

                        </ul>

                    </li>

                    <li class="dropdown">

                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">Advertising &amp; Sponsorship</a>

                        <ul class="dropdown-menu">

                            <li><a href="sponsorships/overview.php" onclick="return rwAjaxNav('sponsorships/overview.php');">Overview</a></li>

                            <li><a href="sponsorships/privileges.php" onclick="return rwAjaxNav('sponsorships/privileges.php');">Sponsor's Privileges</a></li>

                            <li><a href="sponsorships/opportunities.php" onclick="return rwAjaxNav('sponsorships/opportunities.php');">Advertising &amp; Sponsorship Opportunities</a></li>

                            <li><a href="sponsorships/contactus.php" onclick="return rwAjaxNav('sponsorships/contactus.php');">Contact Us</a></li>

                            <!--<li><a href="sponsorships/sponsors.php">Our Sponsors</a></li>-->

                        </ul>

                    </li>

                    <li class="dropdown">

                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">Downloads</a>

                        <ul class="dropdown-menu">

                            <li><a href="downloads/forms.php" onclick="return rwAjaxNav('downloads/forms.php');">Forms</a></li>

                            <li><a href="downloads/CHART.pdf">Chart</a></li>

                            <li><a href="downloads/PROSPECTUS.pdf">Prospectus</a></li>

                        </ul>

                    </li> 
                    
                    {$userMenu}

                </ul>

            </div><!--/.nav-collapse -->

        </div>

    <!-- /.navbar -->

MENU;
    }



    function writeLeftPanel()
    {
        $sessionUser = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'ADMIN';
        $shareUrl = 'https://' . $_SERVER['HTTP_HOST'] . '' . $_SERVER['REQUEST_URI'];
        $shareTitle = 'Royal Western India Turf Club (RWITC)';
// Super Admin check - same logic as top User menu
$isSuperAdmin = (
    isset($_SESSION['uid']) &&
    (int)$_SESSION['uid'] === 19 &&
    isset($_SESSION['role']) &&
    strtoupper((string)$_SESSION['role']) === 'ADMIN'
);

$usersMenuHtml = '';
if ($isSuperAdmin) {
    $usersMenuHtml = '
    <li class="sidebar-user-dropdown" id="navUsers">

        <a href="#" onclick="toggleSidebarUsers(event);">
            <i class="fas fa-users"></i>
            Users
            <i class="fas fa-chevron-down sidebar-user-arrow"></i>
        </a>

        <ul id="sidebarUsersMenu" class="sidebar-submenu">

            <li>
                <a href="admin/users.php">
                    <i class="fas fa-user"></i>
                    Users
                </a>
            </li>

            <li>
                <a href="admin/userGroup.php">
                    <i class="fas fa-user-group"></i>
                    User Group
                </a>
            </li>

        </ul>

    </li>';
}

        echo <<< LEFTPANEL

        <style type="text/css">
        #rightArea.col-lg-3 {
    flex: 0 0 300px;
    max-width: 300px;
    width: 300px;
    box-sizing: border-box;
    float: none;
    padding: 24px 20px 24px 30px;
    background: #ffffff;
    color: #2b332f;
}
        #rightArea .profile-card {
            background: linear-gradient(180deg, #0f5c33, #0b3d24);
            border-radius: 14px;
            padding: 22px 20px;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 20px;
        }
        #rightArea .profile-avatar {
            width: 46px; height: 46px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; flex-shrink: 0;
        }
        #rightArea .profile-welcome { font-size: 13px; opacity: 0.85; }
        #rightArea .profile-name { font-size: 18px; font-weight: 700; letter-spacing: 0.5px; }
        #rightArea .profile-status { font-size: 12px; margin-top: 4px; display: flex; align-items: center; gap: 6px; opacity: 0.9; }
        #rightArea .status-dot { width: 8px; height: 8px; border-radius: 50%; background: #4ade80; display: inline-block; }
        #rightArea .quick-access-title { font-size: 12px; font-weight: 700; letter-spacing: 1px; color: #7a8c84; margin: 4px 0 10px 6px; }
        #rightArea .quick-access-list { list-style: none; margin: 0; padding: 0; }
        #rightArea .quick-access-list li a { display: flex; align-items: center; gap: 12px; padding: 11px 14px; border-radius: 8px; color: #2b332f; text-decoration: none; font-size: 15px; margin-bottom: 4px; }
        #rightArea .quick-access-list li a i { width: 18px; text-align: center; color: #0f5c33; }
        #rightArea .quick-access-list li a:hover { background: #e6f4ec; }
        #rightArea .quick-access-list li.active a { background: #e6f4ec; color: #0f5c33; font-weight: 600; border-left: 3px solid #0f5c33; }
        #rightArea .logout-btn { display: flex; align-items: center; justify-content: center; gap: 8px; border: 1px solid #e2e6e4; color: #2b332f; background: #fff; padding: 9px 18px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 500; margin-top: 16px; }
        #rightArea .logout-btn:hover { background: #e6f4ec; color: #0f5c33; }
        .share-wrapper:hover #shareDropdown,
        #shareDropdown.show { display: flex !important; }
        #shareDropdown { padding-bottom: 18px !important; margin-bottom: -10px !important; }
        #shareDropdown a { margin-bottom: 8px; }
        #rightArea .sidebar-user-dropdown {
    position: relative;
}

#rightArea .sidebar-user-dropdown > a {
    cursor: pointer;
}

#rightArea .sidebar-user-arrow {
    margin-left: auto;
    font-size: 11px;
    transition: transform 0.2s ease;
}

#rightArea .sidebar-user-dropdown.open .sidebar-user-arrow {
    transform: rotate(180deg);
}

#rightArea .sidebar-submenu {
    display: none;
    list-style: none;
    margin: 0 0 6px 0;
    padding: 0 0 0 28px;
}

#rightArea .sidebar-user-dropdown.open .sidebar-submenu {
    display: block;
}

#rightArea .sidebar-submenu li {
    margin: 0;
}

#rightArea .sidebar-submenu li a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 12px;
    border-radius: 7px;
    color: #2b332f;
    text-decoration: none;
    font-size: 14px;
    margin-bottom: 2px;
}

#rightArea .sidebar-submenu li a i {
    width: 16px;
    text-align: center;
    color: #0f5c33;
}

#rightArea .sidebar-submenu li a:hover {
    background: #e6f4ec;
    color: #0f5c33;
}
        </style>
<script>
function toggleSidebarUsers(event) {
    event.preventDefault();

    const dropdown = document.getElementById('navUsers');

    if (dropdown) {
        dropdown.classList.toggle('open');
    }
}
</script>
                <div id="rightArea" class="col-lg-3">

            <div class="profile-card">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div>
                    <div class="profile-welcome">Welcome,</div>
                    <div class="profile-name">{$sessionUser}</div>
                    <div class="profile-status">
                        <span class="status-dot"></span>
                        Online
                    </div>
                </div>
            </div>

            <div class="quick-access-title">QUICK ACCESS</div>

            <ul class="quick-access-list">
                <li class="active" id="navDashboard">
                    <a href="admin/dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                </li>
                {$usersMenuHtml}
                <li id="navAllModules">
    <a href="admin/allModules.php"><i class="fas fa-th-large"></i> All Modules</a>
</li>
                <li><a href="#"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="#"><i class="fas fa-history"></i> Activity Log</a></li>
                <li><a href="#"><i class="fas fa-cog"></i> System Settings</a></li>
                <li><a href="#"><i class="fas fa-question-circle"></i> Help &amp; Support</a></li>
            </ul>

                        <div class="share-wrapper" onmouseleave="document.getElementById('shareDropdown').classList.remove('show');" style="position: relative; margin-bottom: 12px;">
                <a href="#" class="logout-btn" onclick="document.getElementById('shareDropdown').classList.toggle('show'); return false;">
                    <i class="fas fa-share-alt"></i> &nbsp; Share On
                </a>
                <div id="shareDropdown" style="display:none; position:absolute; bottom:100%; left:0; background:#fff; border:1px solid #e2e6e4; border-radius:8px; padding:10px 10px 18px 10px; gap:10px; box-shadow:0 4px 14px rgba(0,0,0,0.12); z-index:10;">
                    <a href="https://www.facebook.com/sharer/sharer.php?u={$shareUrl}" target="_blank"><img src="images/newdesign/fb.png" width="26"></a>
                    <a href="https://twitter.com/intent/tweet?text={$shareTitle}&url={$shareUrl}" target="_blank"><img src="images/newdesign/twt.png" width="26"></a>
                </div>
            </div>

            <a class="logout-btn" href="admin/adminlogin.php?q=logout">
                <i class="fas fa-sign-out-alt"></i> &nbsp; Logout
            </a>

        </div>

LEFTPANEL;
    }


    function writeContentPageStyles($pageClass = 'about-eyebrow')
    {
        echo <<< STYLES

<link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style type="text/css">
:root {
    --rw-green-dark: #04160c; --rw-green-mid: #0b3d20; --rw-green: #0b6d2a;
    --rw-green-bright: #15923c; --rw-lime: #c7e46a; --rw-cream: #f5f4ee;
    --rw-ink: #17251c; --rw-muted: #667066; --rw-line: #e2e1d8;
    --rwitc-dark-green: #0b3d24; --rwitc-green: #0f5c33; --rwitc-accent-green: #1a7a45;
    --rwitc-light-green: #e6f4ec; --rwitc-border: #e2e6e4; --rwitc-text: #2b332f; --rwitc-muted: #7a8c84;
}
#infoWrapper.col-lg-12 { display: flex; flex-direction: row-reverse; align-items: stretch; max-width: 1500px; margin: 30px auto; border-radius: 22px; overflow: hidden; box-shadow: 0 10px 40px rgba(11,61,36,0.14); font-family: 'Inter','Segoe UI',Arial,sans-serif; float: none; }
#leftArea.col-lg-9 { flex: 1 1 auto; min-width: 0; background: var(--rw-cream); padding: 46px 40px 46px 24px; box-sizing: border-box; float: none; width: auto; }
.about-eyebrow { display: inline-block; font-size: 11px; font-weight: 700; letter-spacing: 1.5px; color: var(--rw-green); background: var(--rwitc-light-green); padding: 5px 14px; border-radius: 20px; margin-bottom: 16px; text-transform: uppercase; }
#leftArea.col-lg-9 h2 { font-family: 'Source Serif 4',serif; font-weight: 600; font-size: 34px; color: var(--rw-ink); margin: 0 0 6px 0; letter-spacing: 0.2px; }
.about-subtitle { font-size: 14px; color: var(--rw-muted); margin: 0 0 22px 0; }
.about-divider { height: 1px; width: 100%; background: var(--rw-line); margin: 0 0 28px 0; border: none; }
#leftArea.col-lg-9 p { font-family: 'Inter','Segoe UI',Arial,sans-serif !important; font-size: 15px !important; line-height: 1.85 !important; color: var(--rwitc-text) !important; margin: 0 0 20px 0 !important; text-align: justify; }
#leftArea.col-lg-9 p b { color: var(--rw-ink); }
#leftArea.col-lg-9 ul { margin: 0 0 20px 0; padding-left: 22px; }
#leftArea.col-lg-9 ul li { margin-bottom: 14px; font-family: 'Inter','Segoe UI',Arial,sans-serif !important; font-size: 15px !important; line-height: 1.85 !important; color: var(--rwitc-text) !important; }
#leftArea.col-lg-9 ul li p { margin: 0 !important; font-family: 'Inter','Segoe UI',Arial,sans-serif !important; font-size: 15px !important; line-height: 1.85 !important; color: var(--rwitc-text) !important; text-align: justify; }
#leftArea.col-lg-9 ul li::marker { color: var(--rw-green); font-weight: 700; }
#leftArea.col-lg-9 table { width: 100%; border-collapse: collapse; margin: 0 0 24px 0; font-family: 'Inter','Segoe UI',Arial,sans-serif; font-size: 14.5px; color: var(--rwitc-text); background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(11,61,36,0.06); }
#leftArea.col-lg-9 table th { background: var(--rw-green-mid); color: #fff; font-weight: 700; text-align: left; padding: 12px 16px; font-size: 13px; letter-spacing: 0.3px; }
#leftArea.col-lg-9 table td { padding: 12px 16px; border-bottom: 1px solid var(--rw-line); line-height: 1.6; }
#leftArea.col-lg-9 table tr:last-child td { border-bottom: none; }
#leftArea.col-lg-9 table tr:nth-child(even) td { background: var(--rwitc-light-green); }
#leftArea.col-lg-9 table tr:hover td { background: #eef4ec; }
@media (max-width: 1100px) { #leftArea.col-lg-9 { padding: 36px 34px; } #leftArea.col-lg-9 h2 { font-size: 28px; } }
@media (max-width: 900px) { #infoWrapper.col-lg-12 { flex-direction: column; margin: 16px auto; border-radius: 16px; } #leftArea.col-lg-9 { flex: 1 1 100%; max-width: 100%; padding: 28px 24px; } }
@media (max-width: 600px) { #leftArea.col-lg-9 table { display: block; overflow-x: auto; white-space: nowrap; font-size: 13px; } #leftArea.col-lg-9 { padding: 22px 18px; } #leftArea.col-lg-9 h2 { font-size: 23px; } #leftArea.col-lg-9 p, #leftArea.col-lg-9 ul li, #leftArea.col-lg-9 ul li p { font-size: 14.5px !important; line-height: 1.75 !important; } }
#sponsorBlockWrapper { max-width: 1500px; margin: 10px auto 30px; float: none; }
#sponsorsTitle { color: var(--rwitc-dark-green) !important; font-family: 'Source Serif 4',serif; font-weight: 700; letter-spacing: 1px; }
#sponsorBlock { border: 1px solid var(--rwitc-border) !important; border-radius: 14px !important; background: #fff !important; box-shadow: 0 4px 14px rgba(11,61,36,0.06); }
html, body { scrollbar-width: none; -ms-overflow-style: none; }
html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }
</style>

STYLES;
    }

    function writeQuickAccessPanel()
    {
        ob_start();
        $this->rightArea();
        $rightAreaHtml = ob_get_clean();

        $badgeHtml = '
    <div class="rwBrandBadge">
        <span class="rwBrandMark"><i class="fa-solid fa-horse-head"></i></span>
        <p class="rwBrandEyebrow">Royal Western India Turf Club</p>
    </div>
    <h1 class="rwPanelHeading">Quick Access</h1>
    <p class="rwPanelSub">Explore key services and resources across the club, right from this page.</p>
  ';

        $rightAreaHtml = str_replace(
            '<div id="rightArea" class="col-lg-3">',
            '<div id="rightArea" class="col-lg-3">' . $badgeHtml,
            $rightAreaHtml
        );

        echo $rightAreaHtml;
    }


    function openDiv($id = "", $class = "")
    {

        echo "<div id='$id' class='$class'>";
    }



    function closeDiv()
    {

        echo "</div>";
    }





    function rightArea()
    {

        require_once("dbTools.php");

        $db = new dbTool();

        $raceObj = new Racedata($db);

        $oddsbox = $raceObj->getconfig_datas(1);

        $linkbox = $raceObj->getconfig_datas(2);

        $finalresbox = $raceObj->getconfig_datas(3);



        echo '<div id="rightArea" class="col-lg-3">';



        if ($linkbox == 'Y') {

            //if (LINKSBOX) {

            echo <<< MEDIA_TIPS



MEDIA_TIPS;
        }

        if ($oddsbox == 'Y') {

            //if (ODDSBOX) {

            echo <<< ODDS_CONTENT



ODDS_CONTENT;
        }

        if ($finalresbox == 'Y') {

            //if (FINALRESBOX) {

            echo <<< FINAL_CONTENT



FINAL_CONTENT;
        }





        self::rightSponsor();

        echo <<< BOXES

            <div class="col-lg-12">

            <ul id="rightLinks" class="rightLinkstag clearfix">

                <!--<li>

                <div class="col-lg-12 rightspo1 ">

                    <a class="colour3" href="https://www.youtube.com/watch?v=j-AfMbVAwBc">

                    <div class="scolor s1">

                        <span class="rightTitle">Watch the Seminar held on Friday, 27th Feb 2015</span>

                     </div>

                    </a>

                    </div>

                </li>-->

               <li>

                    <div class="col-lg-12 rightspo2">

                    <a class="colour2" href="availibilityCalendar.php">

                     <div class="scolor s2">

                        <span class="rightTitle">Grounds available for Schools & Colleges</span>

                    </div>

                    </a>

                   </div>

                </li>

                <li>

                 <div class="col-lg-12 rightspo3">

                    <a href="https://play.google.com/store/apps/details?id=com.rwitc.mobileweb" class="colour1">

                    <div class="scolor s3">

                        <span class="rightTitle">RWITC App on Google Play Store</span>

                    </div>

                    </a>

                 </div>

                </li>

                <li>       <div class="col-lg-12 rightspo4">

                    <a href="https://itunes.apple.com/us/app/rwitc/id619375717?ls=1&mt=8" class="colour2">

                <div class="scolor s4">

                        <span class="rightTitle">RWITC App on Apple Itunes</span>

                        </div>

                    </a>

                    </div>

                </li>

                <li>       <div class="col-lg-12 rightspo5">

                                <a href="http://appworld.blackberry.com/webstore/content/26326879/" class="colour4">

                            <div class="scolor s5">

                                    <span class="rightTitle">RWITC App on Blackberry Appworld</span>

                                    </div>

                                </a>

                              </div>

                            </li>

                <li>       <div class="col-lg-12 rightspo6">

                    <a href="app-qr.php" class="colour3">

                <div class="scolor s6">

                        <span class="rightTitle">QR Code for RWITC App</span>

                        </div>

                    </a>

                    </div>

                </li>

                <li>       <div class="col-lg-12 rightspo7">

                    <a href="performanceProfile.php" class="colour1">

                <div class="scolor s7">

                        <span class="rightTitle">Performance Profile of Horses</span>

                        </div>

                    </a>

                    </div>

                </li>

                <li>       <div class="col-lg-12 rightspo8">

                    <a href="http://www.horsein.com/" class="colour2">

                <div class="scolor s8">

                        <span class="rightTitle">Webportal for Owners/Trainers</span>

                    </div>

                    </a>

                    </div>

                </li>

                <li>       <div class="col-lg-12 rightspo9">

                    <a href="calendar.php" class="colour3">

                <div class="scolor s9">

                        <span class="rightTitle">Racing Fixtures</span>

                        </div>

                    </a>

                    </div>

                </li>

                <li>

                    <div class="col-lg-12 rightspo10">

                    <a href="horseRatings.php" class="colour4">

                    <div class="scolor s10">

                        <span class="rightTitle">Ratings of all Horses</span>

                        </div>

                    </a>

                    </div>

                </li>

                <li>

                       <div class="col-lg-12 rightspo11">

                    <a href="horsesInTraining.php" class="colour1">

                <div class="scolor s11">

                        <span class="rightTitle">Horses in Training</span>

                        </div>

                    </a>

                    </div>

                </li>

                <li>

                       <div class="col-lg-12 rightspo12">

                    <a href="dividends.php" class="colour2">

                <div class="scolor s12">

                        <span class="rightTitle">Tote Dividends</span>

                        </div>

                    </a>

                    </div>

                </li>

                <li>

                       <div class="col-lg-12 rightspo13">

                    <a href="http://www.indianstudbook.com/" class="colour3">

                <div class="scolor s13">

                        <span class="rightTitle">Indian Stud Book</span>

                        </div>

                    </a>

                    </div>

                </li>

                <!--<li>

                       <div class="col-lg-12 rightspo14">

                    <a href="viewPgArticles.php" class="colour4">

                        <span class="rightTitle">PRAKASH GOSAVI COLUMN</span>

                    </a>

                </div>

                </li>-->

                <li>

                       <div class="col-lg-12 rightspo15">

                    <a href="moneyLeaders.php" class="colour1">

                        <div class="scolor s14">

                        <span class="rightTitle">Money Leaders</span>

                        </div>

                    </a>

                    </div>

                </li>

                <li>

                       <div class="col-lg-12 rightspo16">

                    <a href="downloads/Prospectus.pdf" class="colour2">

                       <div class="scolor s15">

                        <span class="rightTitle">Prospectus</span>

                        </div>

                    </a>

                    </div>

                </li>

                <li>

                       <div class="col-lg-12 rightspo17">

                    <a href="http://rwitclive.com/RaceArchives.aspx" class="colour3">

                        <div class="scolor s16">

                        <span class="rightTitle">Video Archives</span>

                        </div>

                    </a>

                    </div>

                </li>

                <li>

                  <div class="col-lg-12 rightspo18">

                    <a href="feedback.php" class="colour2">

                        <div class="scolor s17">

                            <span class="rightTitle">Feedback</span>

                        </div>

                    </a>

                    </div>

                </li>

            </ul>

       </div>

BOXES;
    }





    function rightSponsor()

    {

        require_once("dbTools.php");

        $db = new dbTool();

        $raceObj = new Racedata($db);

        $sponsoroftheday_datas = $raceObj->getsponsoroftheday_datas();

        $sponsorofthday = '';

        foreach ($sponsoroftheday_datas as $skey => $svalue) {

            if ($skey == 0) {

                $sponsorofthday .= '<div class="item active">';

                $link = $svalue['link'];

                if ($svalue['link'] != '') {

                    $sponsorofthday .= '<a href="' . $link . '">';

                    $sponsorofthday .= '<img width="150" height="150" src="images/sponsors/' . $svalue['source'] . '" alt="' . $svalue['title'] . '" title="' . $svalue['title'] . '" />';

                    $sponsorofthday .= '<a>';
                } else {

                    $sponsorofthday .= '<img width="150" height="150" src="images/sponsors/' . $svalue['source'] . '" alt="' . $svalue['title'] . '" title="' . $svalue['title'] . '" />';
                }

                $sponsorofthday .= '</div>';
            } else {

                $sponsorofthday .= '<div class="item">';

                $link = $svalue['link'];

                if ($svalue['link'] != '') {

                    $sponsorofthday .= '<a href="' . $link . '">';

                    $sponsorofthday .= '<img width="150" height="150" src="images/sponsors/' . $svalue['source'] . '" alt="' . $svalue['title'] . '" title="' . $svalue['title'] . '" />';

                    $sponsorofthday .= '</a>';
                } else {

                    $sponsorofthday .= '<img width="150" height="150" src="images/sponsors/' . $svalue['source'] . '" alt="' . $svalue['title'] . '" title="' . $svalue['title'] . '" />';
                }

                $sponsorofthday .= '</div>';
            }
        }

        echo <<< RIGHT_FOOTER

            <div class="col-lg-12 col-xs-12 col-sm-6 col-md-6">

                <div class="row">

                    <div class="sponserborder" id="sponsorside" style="margin-top:0px !important;padding-left: 25px !important;padding-right: 25px !important;">

                        <div class="sponsorLabel">

                            <img src="assets/images/sponserheader.png">

                        </div>

                        <div class="daySponsor">

                            <div id="carousel-example-generic" class="carousel slide" data-ride="carousel">

                                <!-- Indicators -->

                                <!-- Wrapper for slides -->

                                <div class="carousel-inner" role="listbox">

                                $sponsorofthday     

                                <!--

                                    <div class="item active">

                                        <img width="150" height="150" src="rwitc_upload/sponsor/rwitc.jpg" alt='Sponsor of the day' />

                                    </div>

                                    <div class="item">

                                       <img width="150" height="150" src="rwitc_upload/sponsor/rwitc.jpg" alt='Sponsor of the day' />

                                    </div>

                                -->

                                <!-- Controls -->

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

RIGHT_FOOTER;
    }



    function pageClose() {}





    function endPage()
    {

        require_once("dbTools.php");

        $db = new dbTool();

        //$sponsorList = DIR_BASE."rwitc_upload/sponsor_scroll.inc";

        //$sponsors = file_get_contents($sponsorList);

        $raceObj = new Racedata($db);

        $sponsor_datas = $raceObj->getsponsor_datas();

        $sponsors = '';

        foreach ($sponsor_datas as $skey => $svalue) {

            $link = $svalue['link'];

            if ($svalue['link'] != '') {

                $sponsors .= '<a href="' . $link . '">';

                $sponsors .= '<img src="images/sponsors/' . $svalue['source'] . '" alt="' . $svalue['title'] . '" title="' . $svalue['title'] . '"  style="margin-left: 14px; margin-right: 14px;"/>';

                $sponsors .= '<a>';
            } else {

                $sponsors .= '<img src="images/sponsors/' . $svalue['source'] . '" alt="' . $svalue['title'] . '" title="' . $svalue['title'] . '" style="margin-left: 14px; margin-right: 14px;" />';
            }
        }

        //echo $sponsors;exit;

        echo <<< FOOTER

        <div class="col-xs-12 col-lg-12" id="sponsorBlockWrapper">

            <div class="row">

                <h3 id="sponsorsTitle">SPONSORS</h3>

                <div id="sponsorBlock" style="background: #FFFFFF;">

                    <marquee style="border: none; margin: 0; top: 0;" id="sponsorBlock" behavior="scroll" direction="left" scrollamount="2"  width="100%">

                    



                    $sponsors

                    

                    </marquee>

                </div>

            </div>

        </div>

</div>

</div>

</div>

</body>

</html>

FOOTER;
    }
} // class end