<?php 
include_once('../bootstrap.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
//include_once('design.php');

  
  $pageTitle ='About RWITC';        
  $design = new Design();
   
  $design->startPage("$pageTitle");
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea",'col-lg-9');
    $design->writeContentPageStyles();
  ?>

<style type="text/css">
.view-panel { display: none; }
.view-panel.active { display: block; }

.all-modules-panel {
    position: relative; padding: 40px 44px; border-radius: 16px; color: #eef4ee;
    background: radial-gradient(circle at 15% 15%, rgba(199,228,106,0.10), transparent 45%), radial-gradient(circle at 80% 70%, rgba(21,146,60,0.25), transparent 55%), linear-gradient(160deg, #04160c 0%, #0b3d20 55%, #04160c 100%);
    overflow: hidden;
}
.all-modules-panel .rightLinkstag { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px 14px; width: 100%; padding: 0; margin: 0; list-style: none; }
.all-modules-panel .rightLinkstag li { margin: 0 !important; width: 100% !important; float: none !important; }
.all-modules-panel .rightLinkstag a { display: flex; align-items: center; width: 100% !important; min-height: 54px; padding: 10px 14px; border-radius: 10px; color: #fff !important; font-family: 'Inter','Segoe UI',Arial,sans-serif; font-size: 12.5px; font-weight: 700; text-decoration: none; border: 1px solid rgba(255,255,255,0.12); box-sizing: border-box; }
.all-modules-panel .rightLinkstag li:nth-child(1) a { background: linear-gradient(135deg, #0b3d20, #15923c); }
.all-modules-panel .rightLinkstag li:nth-child(2) a { background: linear-gradient(135deg, #3d5c1f, #7a9c3f); }
.all-modules-panel .rightLinkstag li:nth-child(3) a { background: linear-gradient(135deg, #0d4c28, #1c9e45); }
.all-modules-panel .rightLinkstag li:nth-child(4) a { background: linear-gradient(135deg, #43631f, #87ab45); }
.all-modules-panel .rightLinkstag li:nth-child(5) a { background: linear-gradient(135deg, #0f5a30, #22a94c); }
.all-modules-panel .rightLinkstag li:nth-child(6) a { background: linear-gradient(135deg, #0e4a3d, #1c8a6e); }
.all-modules-panel .rightLinkstag li:nth-child(7) a { background: linear-gradient(135deg, #125f33, #2ab355); }
.all-modules-panel .rightLinkstag li:nth-child(8) a { background: linear-gradient(135deg, #4a5c1f, #96af4a); }
.all-modules-panel .rightLinkstag li:nth-child(9) a { background: linear-gradient(135deg, #14683a, #33bb5c); }
.all-modules-panel .rightLinkstag li:nth-child(10) a { background: linear-gradient(135deg, #123d2e, #1f6b52); }
.all-modules-panel .rightLinkstag li:nth-child(11) a { background: linear-gradient(135deg, #0b3d20, #26ad50); }
.all-modules-panel .rightLinkstag li:nth-child(12) a { background: linear-gradient(135deg, #3a5c22, #7ea850); }
.all-modules-panel .rightLinkstag li:nth-child(13) a { background: linear-gradient(135deg, #0d4c28, #2fb85d); }
.all-modules-panel .rightLinkstag li:nth-child(14) a { background: linear-gradient(135deg, #0b3d20, #1c9e45); }
.all-modules-panel .rightLinkstag li:nth-child(15) a { background: linear-gradient(135deg, #0f5a30, #3ac267); }
.all-modules-panel .rightLinkstag li:nth-child(16) a { background: linear-gradient(135deg, #0f2e1c, #1c5638); }
@media (max-width: 700px) { .all-modules-panel .rightLinkstag { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px) { .all-modules-panel { padding: 26px 22px; } .all-modules-panel .rightLinkstag { grid-template-columns: 1fr; } }

</style>

<div id="aboutView" class="view-panel active">

<span class="about-eyebrow">The Club</span>
<h2>About RWITC</h2>
<p class="about-subtitle">The story, legacy and workings of the Royal Western India Turf Club</p>
<hr class="about-divider" />

                         <p align="justify">
                            The Royal Western India Turf Club Limited (RWITC), one of the oldest and most well-known horse racing Clubs in the country, is a company limited by guarantee and regulated by its Memorandum and Articles of Association of the Club.
                         </p> 

                          <p align="justify">
                            From the beginning, it has been an exclusive Club enjoying the patronage of socialites and eminent personalities alike. It had about 10,250 members as on 20th December 2020.
                         </p> 

                         <p align="justify">
                            The Club hosts racing at two race courses: at the Mahalaxmi Race Course, Mumbai from November to April and at 6, Arjun Marg, Pune from July to October - in all a total of around 70 race days in a year. In Mumbai, Thursdays and Sundays are race days from November to March and Saturdays and Sundays in April. In Pune, races are held on Saturdays and Sundays.
                         </p> 

                         <p align="justify">
                           The oval-shaped 2400-metre Mahalaxmi race track, rated one of the best in Asia, has an even surface and a three-furlong (600 metre) straight. There is also a straight chute that allows races up to 1600 metres to be run with only the home run. The race course was originally built under the direction and supervision of Major JE Hughes around 1883.
                         </p> 

                         <p align="justify">
                            By and by, modern apparatus to facilitate fair and clean racing and to weed out human error as well as malpractice such as automated starting stalls, photo-finish cameras, split-second electronic timers and close-circuit television systems to watch the action on the track from more than one angle have been installed at both the race courses. 
                         </p> 

                        <p align="justify">
                           One can watch live racing and replays on the close circuit television sets installed at various vantage points of the Mahalaxmi and Pune Race Courses. Live telecasts and replays of races held in Bangalore, Mysore, Kolkata, Hyderabad and Delhi are offered. Proceedings of the Stewards' Enquiries at Mahalaxmi, Pune and Bangalore can also be viewed. The replay of the day's proceedings benefits the latecomers. 
                         </p>

                         <p align="justify">
                            Computerised Tote betting means almost instantaneous declaration of dividends as soon as a race is over. Payments are made only after the "All Clear" has been sounded, though.
                         </p>

                         <p align="justify">
                            Going by the fact that world dignitaries including the Queen of England, the erstwhile Shah of Iran and the King of Saudi Arabia made it a point to squeeze a visit to the Mahalaxmi Race Course in their crowded itineraries, it is no exaggeration to say that it is a showpiece of the Urbs Prima In Indis. The spontaneous accolades from Her Majesty, Queen Elizabeth II, herself a horse lover, owner and breeder par excellence, are a treasured memory in the Club Archives.
                         </p>

                         <p align="justify">
                            The Club had around 600 Owners, 46 licensed trainers and 80 jockeys as on 15th July 2010. There are normally around 1400 horses in training at Mumbai housed in permanent stables. In Pune, however, some of them have to be housed in temporary structures.
                         </p>

                         <p align="justify">
                            The Club permits Amateur Riders' Club to conduct equestrian activities in the centre of the race course and to hold Gymkhana Races after the end of the Mumbai season as well as polo matches in the polo season.
                         </p> 

                          <p align="justify">
                            Racing in Delhi at the Delhi Racing Club (1940) Limited is conducted under the supervision and rules of RWITC,
                         </p> 

                          <p align="justify">
                            The 225-acre Mahalaxmi campus houses a Club House as well as the widely popular Gallops Restaurant to cater to its members and guests. The internationally acclaimed "Tote on the Turf" with speciality cuisine &amp; the city's biggest bar is the latest addition on one premesis where members enjoy a 20% discount.
                         </p> 

                          <p align="justify">
                            The Club has a residential facility in Pune with 25 well appointed rooms and 4 cottages.
                         </p> 

                          <p align="justify">
                            As a socially conscious and responsible corporate citizen, the Club maintains the public garden in the centre of the Mahalaxmi Race Course and also the recently upgraded walking/jogging track for free public use. The much appreciated facility is used by hundreds of citizens every day of the year. 
                         </p> 

                          <p align="justify">
                            RWITC rents out its lawns with associated facilities for weddings and other outdoor events such as fashion shows, musical evenings and the like. It is considered to be one of the most <a href="https://www.notonthewires.com/category/rolex"><font color="#5b5b6b">replica rolex</font></a> prestigious venues in the city. 
                         </p> 

                          <p align="justify">
                            There is a helipad in the Mahalaxmi campus regularly patronised by Club members and corporates.  
                         </p> 

                          <p align="justify">
                            The spectacular Mahalaxmi Grand Stands have recently been accorded Heritage status. 
                         </p>

                          <p align="justify">
                            A brand new 2,000 sq. ft. gymnasium with sauna and a card room with a bar / lounge exclusively for members of the club was inaugurated in December 2009.
                         </p>

</div>

<div id="allModulesView" class="view-panel">
    <div class="all-modules-panel">
        <ul class="rightLinkstag">
            <li><a href="#">Grounds available for Schools &amp; Colleges</a></li>
            <li><a href="#">RWITC App on Google Play Store</a></li>
            <li><a href="#">RWITC App on Apple Itunes</a></li>
            <li><a href="#">RWITC App on Blackberry Appworld</a></li>
            <li><a href="#">QR Code for RWITC App</a></li>
            <li><a href="#">Performance Profile of Horses</a></li>
            <li><a href="#">Webportal for Owners/Trainers</a></li>
            <li><a href="#">Racing Fixtures</a></li>
            <li><a href="#">Ratings of all Horses</a></li>
            <li><a href="#">Horses in Training</a></li>
            <li><a href="#">Tote Dividends</a></li>
            <li><a href="#">Indian Stud Book</a></li>
            <li><a href="#">Money Leaders</a></li>
            <li><a href="#">Prospectus</a></li>
            <li><a href="#">Video Archives</a></li>
            <li><a href="#">Feedback</a></li>
        </ul>
    </div>
</div>

<?php
  $design->closeDiv();
  $design->writeLeftPanel();
?>

<script>
function rwSwitchView(view) {
    var aboutView = document.getElementById('aboutView');
    var allModulesView = document.getElementById('allModulesView');
    var navDashboard = document.getElementById('navDashboard');
    var navAllModules = document.getElementById('navAllModules');

    if (view === 'allModules') {
        aboutView.classList.remove('active');
        allModulesView.classList.add('active');
        navDashboard.classList.remove('active');
        navAllModules.classList.add('active');
    } else {
        allModulesView.classList.remove('active');
        aboutView.classList.add('active');
        navAllModules.classList.remove('active');
        navDashboard.classList.add('active');
    }
}
</script>

<?php
  $design->closeDiv();
  $design->closeDiv();
    $design->endPage();
$design = NULL; // release object