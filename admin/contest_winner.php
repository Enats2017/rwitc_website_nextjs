<?php
  include_once('../bootstrap.php'); 
  require_once("../lib/contests.class.php");   
  require_once("../lib/users.class.php"); 
  require_once("../lib/race.class.php");
  
  $contests = new Contests($db);
  $userObj = new Users($db);  
  
  $q = getParameterString('q','list-contests');
  if ($q == "view-voters") {
    $date = getParameterString('date','',$db,true);
    $raceno = getParameterNumber('raceno',0);
    $votersList = $contests->getVoterList($date,$raceno);
  }
  
  if ($q == "pick-winner") {
    $raceObj = new Racedata($db);   
    $date = getParameterString('date','',$db,true);
    $raceno = getParameterNumber('raceno',0);
    $winningHorseseq = $raceObj->getWinningHorseseqFromScale($date,$raceno);   
    try {
        $votersList = $contests->getWinningVoterList($date,$raceno,$winningHorseseq);          
        if (count($votersList)>0) {
            $winningIndex = mt_rand(0,count($votersList));
            $contests->updateWinner($date,$raceno,$votersList[$winningIndex]['userid']);
        } else {
            $msg = "No Winners Found.";
        }        
    } catch (Exception $err) {
        $msg = "Unable to fetch list of voters";
    }  
  }
  if ($q == "list-contests") {
     $contestList = $contests->getContests();    
  }
  
  $pageTitle ='Pick Contest Winner';        
  $design = new Design();
  $design->js='';
  $design->css ='';
  $design->jqueryJs = ""; 
  $design->startPage("$pageTitle");
  
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper");
  $design->openDiv("leftArea"); 

  ?>
  <div class="message">
        <?php echo $msg; ?>
  </div>  
  <?php if ($q == "pick-winner") { ?>
  <table class="contentTable">
          <tr>
            <th>Email</th>
            <th>Name</th>
            <th>Predicted Winner</th>
            <th>Voted On</th>
          </tr>          
            <tr>
                <td><?php echo $votersList[$winningIndex]['email']; ?></td>
                <td><?php echo $votersList[$winningIndex]['name']; ?></td>                   
                <td><?php echo $votersList[$winningIndex]['HORSENM']; ?></td>                   
                <td><?php echo date("d M Y, G:i:s",strtotime($votersList[$winningIndex]['voted_on'])); ?></td>                   
            </tr>          
  </table>
  <?php  }  ?>
  <?php if ($q == "view-voters") { ?>  
  <table class="contentTable">
         <tr>
                <th class="thwhite" colspan="4">Total Votes: <?php echo count($votersList) ?></th>
         </tr>
          <tr>
            <th>Email</th>
            <th>Name</th>
            <th>Predicted Winner</th>
            <th>Voted On</th>
          </tr>
          <?php foreach($votersList as $voterDetails) {   ?>
                <tr>
                    <td><?php echo $voterDetails['email']; ?></td>
                    <td><?php echo $voterDetails['name']; ?></td>                   
                    <td><?php echo $voterDetails['HORSENM']; ?></td>                   
                    <td><?php echo date("d M Y, G:i:s",strtotime($voterDetails['voted_on'])); ?></td>                   
                </tr>
          <?php  }  ?>
  </table> 
  <br />
   <a href="admin/contest_winner.php">List Contests</a> &nbsp;&nbsp;
   <a href="admin/contest_winner.php?q=pick-winner&amp;date=<?php echo $date; ?>&amp;raceno=<?php echo $raceno; ?>">Pick Winner</a> 
  <?php } ?>    
  <?php if ($q == "list-contests") { ?>
  <table class="contentTable">
          <tr>
            <th>Race Date</th>
            <th>Race No</th>
            <th>Action</th>
          </tr>
          <?php foreach($contestList as $contestDetails) {   ?>
                <tr>
                    <td><?php echo $contestDetails['racedate']; ?></td>
                    <td><?php echo $contestDetails['raceno']; ?></td>
                    <td class="alignLeft">
                        <a href="admin/contest_winner.php?q=view-voters&amp;date=<?php echo $contestDetails['racedate']; ?>&amp;raceno=<?php echo $contestDetails['raceno']; ?>">View Voters</a>&nbsp;&nbsp;
                        <a href="admin/contest_winner.php?q=pick-winner&amp;date=<?php echo $contestDetails['racedate']; ?>&amp;raceno=<?php echo $contestDetails['raceno']; ?>">Pick Winner</a>
                    </td>
                </tr>
          <?php  }  ?>
  </table>   
  <?php } ?>
  <?php                   
  $design->closeDiv();
  $design->rightArea();  
  $design->closeDiv();
  $design->closeDiv();
    $design->endPage();
$design = NULL; // release object