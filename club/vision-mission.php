<?php 
include_once('../bootstrap.php');
$pageTitle ='Vision-Mission';        
$design = new Design();
$design->startPage("$pageTitle");
$design->writeLogoTickerMenu();
$design->openDiv("contentWrapper");
$design->openDiv("infoWrapper","col-lg-12");
$design->openDiv("leftArea",'col-lg-9');
?>
<h2>The RWITC Vision & Mission Statement</h2>        
                         <p align="justify">
                            RWITC is renowned as the premier racing club in India offering facilities matching the best in the world. It is also the home of the Indian Classics introduced first in 1943 and modelled on the British originals – Classics that are truly national in character.
                         </p> 
                         <br \>
                         <p align="justify">
                            <b>The following in brief is the Club’s vision and mission statement:</b>
                         </p> 
                         <br \>
                         <ul>
                            <li>
                                <p align="justify">To ensure quality in its race programmes, racing surfaces, racing environment and conduct as behoves one of Asia's most famous race courses and home to the five Indian Classics.</p>
                            </li>
                            <li>
                                <p align="justify">To ensure that race courses at Mumbai and Pune continue to be maintained as world-class racing venues so as to measure up to RWITC's reputation as one of the leading race Clubs in Asia.
                            </p>
                            </li>
                            <li>
                                <p align="justify">To set the highest standards in the organization and administration of the sport. 
                            </p>
                            </li>
                            <li>
                                <p align="justify">To provide superior amenities and up-to-date facilities to its racing patrons and members by way of the quality of entertainment, infrastructure and betting facilities.</p>
                            </li>
                            <li>
                                <p align="justify">To be totally transparent in every aspect of its working and to be always owner- as well as punter-friendly.</p>
                            </li>
                            <li>
                                <p align="justify">To maximize returns from its racing and non-racing activities.</p>
                            </li>
                            <li>
                                <p align="justify">To ensure for its sponsors optimum returns on investment.</p>
                            </li>
                            <li>
                                <p align="justify">To make horse racing a clean and family-oriented sport.</p>
                            </li>
                            <li>
                                <p align="justify">To provide the best working environment to its staff.</p>
                            </li>
                            <li>
                                <p align="justify">To contribute its bit toward social causes and be responsive to the needs of the society in to the best of its abilities. 
                                </p>
                            </li>
                         </ul>
<?php                   
  $design->closeDiv();
  $design->rightArea();  
  $design->closeDiv();
  $design->closeDiv();
  $design->endPage();
$design = NULL; // release object