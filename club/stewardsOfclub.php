<?php 
include_once('../bootstrap.php');
//include_once('design.php');

  
  $pageTitle ='Stewards of the Club';        
  $design = new Design();
   
  $design->startPage("$pageTitle");
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea",'col-lg-9');
  
  $design->writeContentPageStyles();
  ?>

<span class="about-eyebrow">Organisation &amp; Management</span>
<h2>Stewards of the Club</h2>
<p class="about-subtitle">Overseeing the conduct of racing and all racing matters</p>
<hr class="about-divider" />

                         <p align="justify" >
                                The Stewards are responsible for the conduct of racing and have jurisdiction over all racing matters. The Chairman of the Stewards is chosen by the Stewards of the Club. 
                         </p>
                         
                          <p align="justify" >
                                After the annual elections of the Managing Committee, the Committee nominates nine Club members of the Club to serve as the Stewards of the Club for the period of its own tenure. There are two Government nominees as additional Stewards of the Club, usually the Commissioner/Joint Commissioner of Police, Mumbai/Pune and the Director General, Anti-Corruption Bureau. In addition, the Club invites the GOC-in-Chief, Southern Command, Pune, to be a Steward of the Club. 
                         </p>
                         
                         <p align="justify" >
                            <b>The following are the Stewards of the Club at present: </b>
                         </p>

                          <ul>
                                  <li>Dr. Ram H. Shroff (Chairman)</li>
                                  <li>Lt. Gen. J. S. Nain, AVSM,PVSM, SM, GOC-in-C, Southern Command</li>
                                  <li>Mr. Amitabh Gupta, I.P.S.</li>
                                  <li>Mr. Gautam P. Lala</li>
                                  <li>Mr. Jiyaji M. Bhosale</li>
                                  <li>Mr. J. H. Damania</li>
                                  <li>Mr. Khushroo N. Dhunjibhoy</li>
                                  <li>Mr. Prabhat Kumar, IPS</li>
                                  <li>Mr. S. M. Ruia</li>
                                  <li>Mr. S. R. Sanas</li>
                                  <li>Mr. Sunil G. Jhangiani</li>
                                  <li>Mr. Vijay B. Shirke</li>
                         </ul>
            
<?php                   
  $design->closeDiv();
  $design->writeLeftPanel();

  $design->closeDiv();
  $design->closeDiv();
    $design->endPage();
$design = NULL; // release object