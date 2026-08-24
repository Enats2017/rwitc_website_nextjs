<?php 
include_once('../../bootstrap.php');
//include_once('design.php');

  
  $pageTitle ='Overview';        
  $design = new Design();
   
  $design->startPage("$pageTitle");
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
$design->openDiv("leftArea",'col-lg-9');
  $design->writeContentPageStyles();
  ?>
  
  
<h2>Beginner's luck in racing</h2>
<br />
<p>There is a hilarious set piece in the 1937 Marx Brothers?comedy, A Day at the Races. It could well be the archetypical morality tale for an intending punter.</p>
<br />
<p>In it, Groucho (Hackenbush, an animal doctor about to head a sanitarium for people) wants to bet on a form horse, Sun-Up. At the Tote window, Chico (Tony, the good-hearted bus driver who is trying to raise money to save the sanitarium from ruin) stops him. Posing as an ice cream vendor cum tout, Chico manages to take Groucho to the cleaners with his glib talk about inside information. He cajoles Groucho into buying a pile of form books and code books. Finally, with the money thus gained, Chico backs Sun-Up, the original choice of gullible Groucho. As Chico walks away from the pay window counting his money, Groucho takes over his ice cream wagon.</p>
  <br />
<p>The moral of the story is to trust no one except your own judgement when it comes to backing a horse. Distrust, and shun like the black plague, touts who lure the gullible with pie-in-the-sky claims of access to "inside information".</p>
  <br />
<p>The Supreme Court has already ruled that wagering on the races is a game of skill. Luck too plays a bit of role in it, though,</p>
  <br />
<p>This is especially true for the first-time visitor who has no preconceived notions and can therefore give a free play to his imagination. At the end of the day, he may walk out laughing all the way home watched enviously by the cautious old hands who have lost a packet because they were reluctant to take a chance.</p>
  <br />
<p>That said, it cannot be denied that understanding the how and why of the sport can add to your pleasure.</p>
 <br />
<p>
    <b>How to pick a winner.</b><br />
    <br />
     That is the million dollar question every punter is seeking an answer to. <br /></p>
<br />
<p>What Roger�s friend meant was simply this. Everyone at the races has his or her opinion about the form and the ability of each runner. That's why the odds on them differ. So does the amount of money riding on each of them. The fun is to know whose opinion turns out to be right,</p>
<br />
<p>Will Rogers once quoted his friend Damon Runyon as saying: "A difference of opinion is what makes horse racing and missionaries."</p>
<br />
<p> Damon Runyon also said: �The race is not always to the swift nor the battle to the strong ?but that's the way to bet.?He ought to know. He was a regular at the races. On his racing tales is based the hit Broadway show, Guys & Dolls. He even ran a small stable of his own. Even now, every December, the Damon Runyon Stakes is run in his memory at the Aqueduct in Queens, New York.</p>
<br />
<p> So, study the race book. Observe the runners parading in the paddock. Watch the odds fluctuating in the bookmakers' ring. Then, make your move. Best of luck!</p>  
  <?php                   
  $design->closeDiv();
  $design->writeLeftPanel();
  $design->closeDiv();
  $design->closeDiv();
    $design->endPage();
$design = NULL; // release object