<?php 
include_once('../../bootstrap.php');
  $pageTitle ='Betting Pools';        
  $design = new Design();
   
  $design->startPage("$pageTitle");
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea",'col-lg-9');
  $design->writeContentPageStyles();
  ?>
<table width="700" border="0" cellspacing="2" cellpadding="2"></table>
   <tr>
      <td>
        <h2>How to bet on tote</h2>
      </td>
    </tr>
    <tr>
      <td></td>
    </tr>
    <tr>
      <td align="left"  valign="top">
      <table  border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td  valign="top" align="left">
                  <p>You can go to a manned Tote window each time or purchase a cash voucher from a Tote Service Outlet or call the operator who has a hand held computer. You can either bet on tote odds which is variable or bet on fixed tote odds. </p>
                <br /><br />
                <p><b>Win:</b> Pick a horse to finish 1st.
                <br /><br />
                <b>Place:</b> Pick a horse to be 1st, 2nd or 3rd. In races with 7 or less runners you must be 1st or 2nd.
                <br /><br />
                <b>SHP:</b> The horse you nominate should finish in the second position only for
                    you to get the dividend.
                 <br />
                 <br />
                 <b>Forecast:</b>  You have to nominate the horses finishing first and second in the correct order.
                  <br /><br />
                  <b>Quinella:</b>  You can nominate the horses finishing first and second in any order.
                   <br /><br />
                   <b>Treble:</b> You have to nominate winners of three races earmarked for this pool. You have the option to buy a combination ticket.
                <br /><br />
                <b>Exacta:</b>  Pick four horses to finish among the first four in the correct order. If you buy a combination ticket, you are a winner if you have the first four finishers in your combined pool. 
                <br /><br />
                <b>Trinella/tanala:</b> Pick 3 horses to finish 1st, 2nd or 3rd in correct order or buy a combination ticket  where you need to have the first three finishers in your ticket. 
                <br /><br />
                <b>Jackpot:</b> Your aim is to select the winners of the five races nominated for the jackpot pool. The races which are considered for jackpot pools are announced well in advance by the club at the time of declaration itself. You can either buy a single ticket or through a combination ticket. 
                <br /><br />
                <b>Super Jackpot:</b> The RWITC conducts a super jackpot pool which involves nominating winners in six legs either through combination or through a single ticket which should nominate winners of all races selected for this purpose. 
                </p>
                 <br />
                <p><b>While betting at the tote windows, bear the following in mind:</b> 
                </p>
                      <ul>
                        <li><p>The race meeting</p></li>
                        <li><p>The race number</p></li>
                        <li><p>Amount of money</p></li>
                        <li><p>Type of bet</p></li>
                        <li><p>Number of the horse</p></li>
                      </ul>
                      <p>After placing the bet you will receive a ticket with all the details on it. Make sure they are correct. When your horse wins present the ticket to collect your winnings. </p>
                     <br /> <br />
                     <span style="font-size: 10.5pt; line-height: 140%; font-family: Verdana"><strong>How To Bet On The Bookmakers</strong></span>
                     <p>
                      <br /><br />
                      <b>Win:</b> Pick a horse to finish 1st.
                      <br /><br />
                      <b>Each way:</b>  Win and Place bet. The place price is 1/4 or 1/5 of the win price. Only in terms races, the place odds are at the discretion of the bookmaker. 
                      <br /><br />
                      <b>Without the favourite:</b> With this bet you get reduced odds but two chances to win. Quite often, horses are withdrawn at the gate due to unruly behaviour. If the horse withdrawn happens to be a fancied runner, there will be cut in the dividends. 
                      <br /><br />
                      <b>1. </b>If your horse wins the race.
                      <br /><br />
                      <b>2. </b>If your horse finishes 2nd to the favourite.
                   </p>   
            </td>
        </tr>
      </table>        </td>
    </tr>
   
</table>
  <?php                   
  $design->closeDiv();
  $design->writeLeftPanel();
  $design->closeDiv();
  $design->closeDiv();
    $design->endPage();
$design = NULL; // release object