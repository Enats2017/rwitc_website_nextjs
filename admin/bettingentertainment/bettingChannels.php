<?php 
include_once('../../bootstrap.php');
  $pageTitle ='Betting Channels';        
  $design = new Design();
   
  $design->startPage("$pageTitle");
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea",'col-lg-9');
  $design->writeContentPageStyles();
  ?>         
    <table width="700" border="0" cellspacing="2" cellpadding="2">
    <tr>
      <td>
        <h2>Tote betting and backing with the bookmakers</h2>
      </td>
    </tr>
    <tr>
      <td><table width="700" border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td valign="top">
            <blockquote>
                <p align="justify">
                <span class="StaticArticle"><br>A punter can place his bets with 
                either the officially operated totalisator pools where your bet 
                can be as little as Rs 10 or with the legal permitted bookmakers 
                operating at the club premises. To back with the bookmakers, the 
                minimum bet that one needs to do is higher than with the 
                totalisator pools. <br>
                <br>
                The club has set hundreds of tote booths where one can place 
                their bets. The club also offers incentives to those backing 
                with the club operated tote pools by way of giving various 
                bumper prizes which can also include a Mercedes car on the 
                Indian Derby day. <br>
                <br>
                Apart from the race course, the club also operates several 
                outside betting centres where one can place a bet on the tote 
                pools which is linked to the club�s overall pool.<br>
&nbsp;</span></p>
            </blockquote>
            </td>
        </tr>   
      </table>
     </td>
    </tr>
    <tr>
      <td valign="top">&nbsp;</td>
    </tr>
</table>

  <?php                   
  $design->closeDiv();
  $design->writeLeftPanel();
  $design->closeDiv();
  $design->closeDiv();
    $design->endPage();
$design = NULL; // release object