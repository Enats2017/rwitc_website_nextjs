<?php 
include_once('../bootstrap.php');
//include_once('design.php');

  
  $pageTitle ='Managing Committee';        
  $design = new Design();
   
  $design->startPage("$pageTitle");
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea",'col-lg-9');
  
  $design->writeContentPageStyles();
  ?>

<span class="about-eyebrow">Organisation &amp; Management</span>
<h2>Managing Committee of the Club</h2>
<p class="about-subtitle">The members responsible for the Club's governance and administration</p>
<hr class="about-divider" />

                         <p align="justify" >
                            The management of the Club and the control over the funds and property of the Club
                vests in the Committee consisting of 9 Club Members elected by the Club Members 
            in accordance with the provisions contained in the Articles of 
            Association of the Club. In addition to this there are two Government Nominees on the Committee who are usually the Additional Chief 
            Secretary, Government of Maharashtra, Home Department
                and the Additional Chief Secretary, Government of Maharashtra, Revenue and Forests
                Department. The Committee retires every year. The Chairman is elected
                by the Members of the Managing Committee.
                         </p>
                         
                         <p align="justify" >
                            <b>The following are the Committee Members of the Club at present: </b>
                         </p>

                          <ul>
                                  <li>Mr. S. R. Sanas (Chairman)</li>
                                  <li>Mr. Gautam P. Lala</li>
                                  <li>Mr. Jaydev M. Mody</li>
                                  <li>Mr. Jiyaji M. Bhosale</li>
                                  <li>Mr. Khushroo N. Dhunjibhoy</li>
                                  <li>Dr. Ram H Shroff</li>
                                  <li>Mr. Shiven Surendranath</li>
                                  <li>Mr. Sunil G. Jhangiani</li>
                                  <li>Mr. Vijay B. Shirke</li>
                                  <li>Mr. Anand M. Limaye,IAS(Govt. Nominee)</li>
                                  <li>Dr. Nitin Kareer, IAS (Govt. Nominee)</li>
                         </ul>
            
<?php                   
  $design->closeDiv();
  $design->writeLeftPanel();

  $design->closeDiv();
  $design->closeDiv();
    $design->endPage();
$design = NULL; // release object