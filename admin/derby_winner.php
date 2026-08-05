<?php
  include_once('../bootstrap.php'); 
  require_once("../lib/derby.class.php");   
  require_once("../lib/users.class.php"); 
 
  
  $dcontests = new Derby($db);
  $userObj = new Users($db);  
  
  $q = getParameterString('q','');
  $winnerNo = getParameterNumber('winner_no','');
  $votersList = $dcontests->getVoters();
  $winnerNoToPick =0;
  try {
    $winnerNoToPick = $dcontests->getMAXwinnerNO();         
  } catch (Exception $err) {
      $winnerNoToPick =0;
  }
   
  $pageTitle ='Pick Derby Winner';        
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
  <?php
     if ($q == "pick-winner") { 
         $winnerList = $dcontests->getWinnerList();    
         $winningIndex = mt_rand(0,count($winnerList));  
         $dcontests->updateWinner($winnerList[$winningIndex]['userID'],$winnerNo);  
         $winnerNoToPick = $dcontests->getMAXwinnerNO(); 
      ?>
  <table class="contentTable">
    <tr>
        <th colspan="4">Winner No: <?php echo $winnerNo; ?></th>
    </tr>
    <tr>
        <th>Email</th>
        <th>Name</th>
        <th>Voted ON</th>
        <th>Answers</th>
    </tr>
    <tr>
        <td><?php echo $winnerList[$winningIndex]['email']; ?></td>
        <td><?php echo $winnerList[$winningIndex]['name']; ?></td>
        <td><?php echo $winnerList[$winningIndex]['voted_on']; ?></td>
        <td><?php echo $winnerList[$winningIndex]['ans_1'].",".$winnerList[$winningIndex]['ans_2'].",".$winnerList[$winningIndex]['ans_3']; ?></td>
    </tr>
  </table>
  <?php } ?>
  <br />
  <a href="admin/derby_winner.php?q=pick-winner&amp;winner_no=<?php echo $winnerNoToPick;?>">Pick Winner</a> 
  <br />      
  <table class="contentTable">
         <tr>
                <th class="thwhite" colspan="4">Total Votes: <?php echo count($votersList) ?></th>
         </tr>
          <tr>
            <th>Email</th>
            <th>Name</th>
            <th>Ans 1</th>
            <th>Ans 2</th>
            <th>Ans 3</th>
            <th>Voted On</th>
          </tr>
          <?php foreach($votersList as $voterDetails) {   ?>
                <tr>
                    <td><?php echo $voterDetails['email']; ?></td>
                    <td><?php echo $voterDetails['name']; ?></td>                   
                    <td><?php echo $voterDetails['ans_1']; ?></td>                   
                    <td><?php echo $voterDetails['ans_2']; ?></td>                   
                    <td><?php echo $voterDetails['ans_3']; ?></td>                   
                    <td><?php echo date("d M Y, G:i:s",strtotime($voterDetails['voted_on'])); ?></td>                   
                </tr>
          <?php  }  ?>
  </table> 
  <br />  
   
  <?php                   
  $design->closeDiv();
  $design->rightArea();  
  $design->closeDiv();
  $design->closeDiv();
    $design->endPage();
$design = NULL; // release object