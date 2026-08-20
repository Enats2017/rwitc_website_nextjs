<?php 
include_once('../bootstrap.php');
//include_once('design.php');

  
  $pageTitle ='Careers';        
  $design = new Design();
   
  $design->startPage("$pageTitle");
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea",'col-lg-9');
  $design->writeContentPageStyles();
  ?>

<span class="about-eyebrow">The Club</span>
<h2>Career Opportunities in Racing</h2>
<p class="about-subtitle">Explore the many career paths within the sport of racing</p>
<hr class="about-divider" />

                <p align="justify">The activity of racing provides innumerable opportunities for those seeking to make a career in this Sport of Kings. At the administrative level, there is scope for young and enthusiastic people with talent to make their foray in several areas such as working as racing officials such as Stipendiary Stewards, Veterinary doctors, race day officials, computer operators etc. Racing is a labor intensive industry which gives wide scope for employment opportunities directly through the club and also through the several license holders who are part of its activity in related jobs. </p>

                <p align="justify">Racing also provides opportunities for those with a penchant for riding to take a career as a jockey. This of course is demanding because a person wanting to be a jockey should do so at a young age, have the required physical attributes, with light weight. There are also opportunities to become race horse trainers. These two jobs are highly skilled jobs for which only some are suitabl. All in all, racing triggers a huge economic activity with multiple career options.</p>
            
<?php                   
  $design->closeDiv();
  $design->writeLeftPanel();
  $design->closeDiv();
  $design->closeDiv();
  $design->endPage();
$design = NULL; // release object