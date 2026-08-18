<?php 
error_reporting(0);
ini_set("display_errors", 0);
include_once('bootstrap.php');
include_once('lib/race.class.php');
  
  $pageTitle ='Money Leaders';        
  $design = new Design();
  $design->css ="
    <style type='text/css'>
        
    
    </style>
  ";
   $design->jqueryJs = "
   //Get all the LI from the #tabMenu UL
  $('.tabMenu > li').click(function(){
     //remove the selected class from all LI    
    $('.tabMenu > li').removeClass('selected');
    
    //Reassign the LI
    $(this).addClass('selected');
    
    //Hide all the DIV in .boxBody
    $('.boxBody div').slideUp('1500');
    
    //Look for the right DIV in boxBody according to the Navigation UL index, therefore, the arrangement is very important.
    $('.boxBody div:eq(' + $('.tabMenu > li').index(this) + ')').slideDown('1500');
    
  }).mouseover(function() {

    //Add and remove class, Personally I dont think this is the right way to do it, anyone please suggest    
    $(this).addClass('mouseover');
    $(this).removeClass('mouseout');   
    
  }).mouseout(function() {
    
    //Add and remove class
    $(this).addClass('mouseout');
    $(this).removeClass('mouseover');    
    
  });

  //Mouseover with animate Effect for Category menu list
  $('.boxBody #category li').mouseover(function() {

    //Change background color and animate the padding
    $(this).css('backgroundColor','#888');
    $(this).children().animate({paddingLeft: '20px'}, {queue:false, duration:300});
  }).mouseout(function() {
    
    //Change background color and animate the padding
    $(this).css('backgroundColor','');
    $(this).children().animate({paddingLeft:'0'}, {queue:false, duration:300});
  });  
    
  //Mouseover effect for Posts, Comments, Famous Posts and Random Posts menu list.
  $('.boxBody ul li').click(function(){
    window.location = $(this).find('a').attr('href');
  }).mouseover(function() {
    $(this).css('backgroundColor','#888');
  }).mouseout(function() {
    $(this).css('backgroundColor','');
  }); 
   ";  
   $raceObj = new Racedata($db);
  $design->startPage("$pageTitle");
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea",'col-lg-9');
    $moneyLeaders = $raceObj->getWebstatsData();
    $sortedML = array();
    foreach ($moneyLeaders as $money) {
        if ($money["CATEGORY"] == "Owners") {
            $sortedML["Owners"][]=$money;
        }      
        if ($money["CATEGORY"] == "Trainers") {
            $sortedML["Trainers"][]=$money;
        }
        if ($money["CATEGORY"] == "Jockeys") {
            $sortedML["Jockeys"][]=$money;
        }
        if ($money["CATEGORY"] == "Horses") {
            $sortedML["Horses"][]=$money;
        }
    }
    /*echo "<pre>";
    print_r($sortedML);
    echo "</pre>";*/

  ?>
<div class="tabMenu">
  <ul class="nav nav-tabs">
  <li class="active"><a class="btn btn-success" data-toggle="tab" href="#home">OWNERS</a></li>
  <li><a class="btn btn-success" data-toggle="tab" href="#menu1">JOCKEYS</a></li>
  <li><a class="btn btn-success" data-toggle="tab" href="#menu2">HORSES</a></li>
  <li><a class="btn btn-success" data-toggle="tab" href="#menu3">TRAINERS</a></li>
</ul>

<div class="tab-content">
    <div id="home" class="tab-pane fade in active">
    
   <div id="owners">
    <table class="leadersTable table">
        <tr class="success">
            <th>Name</th>
            <th>Starts</th>
            <th>Wins</th>
            <th>Second</th>
            <th>Third</th>
            <th>Winnings</th>
        </tr>
        <?php 
            for ($i=0;$i<10;$i++) {
                echo "<tr>";
                    echo "<td style='text-align:left;font-weight:bold;'>{$sortedML['Owners'][$i]['NAME']}</td>";
                    echo "<td>{$sortedML['Owners'][$i]['STARTS']}</td>";
                    echo "<td>{$sortedML['Owners'][$i]['WINS']}</td>";
                    echo "<td>{$sortedML['Owners'][$i]['SECOND']}</td>";
                    echo "<td>{$sortedML['Owners'][$i]['THIRD']}</td>";
                    echo "<td>{$sortedML['Owners'][$i]['WINNINGS']}</td>";
                echo "</tr>";                
            }
            echo "<tr>";
               echo "<td colspan='6'>{$sortedML['Owners'][10]['NAME']}</td>";
            echo "</tr>";
        ?>
    </table>
  </div>
  
  </div>
  <div id="menu1" class="tab-pane fade">
   <div id="jockeys">
    <table class="leadersTable table">
        <tr class="success">
            <th>Name</th>
            <th>Starts</th>
            <th>Wins</th>
            <th>Second</th>
            <th>Third</th>
            <th>Winnings</th>
        </tr>
        <?php 
            for ($i=0;$i<10;$i++) {
                echo "<tr>";
                    echo "<td style='text-align:left;font-weight:bold;'>{$sortedML['Jockeys'][$i]['NAME']}</td>";
                    echo "<td>{$sortedML['Jockeys'][$i]['STARTS']}</td>";
                    echo "<td>{$sortedML['Jockeys'][$i]['WINS']}</td>";
                    echo "<td>{$sortedML['Jockeys'][$i]['SECOND']}</td>";
                    echo "<td>{$sortedML['Jockeys'][$i]['THIRD']}</td>";
                    echo "<td>{$sortedML['Jockeys'][$i]['WINNINGS']}</td>";
                echo "</tr>";                
            }
            echo "<tr>";
               echo "<td colspan='6'>{$sortedML['Jockeys'][10]['NAME']}</td>";
            echo "</tr>";
        ?>
    </table>
  </div>
  </div>
  <div id="menu2" class="tab-pane fade">
    <div id="horses">
    <table class="leadersTable table">
        <tr class="success">
            <th>Name</th>
            <th>Starts</th>
            <th>Wins</th>
            <th>Second</th>
            <th>Third</th>
            <th>Winnings</th>
        </tr>
        <?php 
            for ($i=0;$i<10;$i++) {
                echo "<tr>";
                    echo "<td style='text-align:left;font-weight:bold;'>{$sortedML['Horses'][$i]['NAME']}</td>";
                    echo "<td>{$sortedML['Horses'][$i]['STARTS']}</td>";
                    echo "<td>{$sortedML['Horses'][$i]['WINS']}</td>";
                    echo "<td>{$sortedML['Horses'][$i]['SECOND']}</td>";
                    echo "<td>{$sortedML['Horses'][$i]['THIRD']}</td>";
                    echo "<td>{$sortedML['Horses'][$i]['WINNINGS']}</td>";
                echo "</tr>";                
            }
            echo "<tr>";
               echo "<td colspan='6'>{$sortedML['Horses'][10]['NAME']}</td>";
            echo "</tr>";
        ?>
    </table>
  </div>
  </div> 


   <div id="menu3" class="tab-pane fade">
  <div id="trainers" class="show">
    <table class="leadersTable table">
        <tr class="success">
            <th>Name</th>
            <th>Starts</th>
            <th>Wins</th>
            <th>Second</th>
            <th>Third</th>
            <th>Winnings</th>
        </tr>
        <?php 
            for ($i=0;$i<10;$i++) {
                echo "<tr>";
                    echo "<td style='text-align:left;font-weight:bold;'>{$sortedML['Trainers'][$i]['NAME']}</td>";
                    echo "<td>{$sortedML['Trainers'][$i]['STARTS']}</td>";
                    echo "<td>{$sortedML['Trainers'][$i]['WINS']}</td>";
                    echo "<td>{$sortedML['Trainers'][$i]['SECOND']}</td>";
                    echo "<td>{$sortedML['Trainers'][$i]['THIRD']}</td>";
                    echo "<td>{$sortedML['Trainers'][$i]['WINNINGS']}</td>";
                echo "</tr>";                
            }
            echo "<tr>";
               echo "<td colspan='6'>{$sortedML['Trainers'][10]['NAME']}</td>";
            echo "</tr>";
        ?>
    </table>
  </div>  
  </div>
</div>
 </div>

  <?php
    $design->closeDiv();
  $design->rightArea();
  $design->closeDiv();
  $design->closeDiv();
    $design->endPage();
$design = NULL; // release object
?>
