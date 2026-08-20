<?php 
include_once('../bootstrap.php');
//include_once('design.php');

  
  $pageTitle ='Responsible';        
  $design = new Design();
   
  $design->startPage("$pageTitle");
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea",'col-lg-9');
  
  $design->writeContentPageStyles();
  ?>

<span class="about-eyebrow">The Club</span>
<h2>Responsible Gambling</h2>
<p class="about-subtitle">Guidelines and principles the Club upholds for safe wagering</p>
<hr class="about-divider" />

                         <p align="justify" >
                               The Club whole-heartedly subscribes to and supports the principles of responsible gambling.
                         </p>

                          <p align="justify" >
                               Children under the age of 18 years are not permitted to place Tote bets or to enter the bookmaker' ring in the Mahalaxmi and Pune Race Courses.
                         </p>

                          <p align="justify" >
                               Punters are advised to exercise extreme caution and restraint in placing bets, always remember that wagering on races is only a game — not a way to get rich quick. Spend only the amount one can afford to lose. Do not place bets when stressed, upset or depressed. Do not borrow money to wager. Do not try to recover your losses with a single big bet.
                         </p>

                          <p align="justify" >
                               Please avoid like the plague touts claiming access to "privileged information". 
                         </p>

                          <p align="justify" >
                               Please do not patronise illegal bookmakers at any time or for any reason. 
                         </p>

                          <p align="justify" >
                               Please cooperate with the anti-corruption and security staff who are responsible for curbing illegal betting and bookmaking.
                         </p>
            
<?php                   
  $design->closeDiv();
  $design->writeLeftPanel();

  $design->closeDiv();
  $design->closeDiv();
    $design->endPage();
$design = NULL; // release object