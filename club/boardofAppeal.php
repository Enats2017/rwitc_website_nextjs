<?php 

include_once('../bootstrap.php');

//include_once('design.php');
  $pageTitle ='Board of Appeal';        
  $design = new Design();
  $design->startPage("$pageTitle");
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea",'col-lg-9');


  ?>       







<h2>Board of Appeal</h2>

                         <p align="justify" >

                          

                               The Board of Appeal deals with the appeals preferred against the decision of the Stewards of the Club. 

                            

                         </p> 

                         

                         <p align="justify" >

                          

                                Its six members are elected by the Club members in accordance with the Articles of Association of the Club. 1/3rd of the members, i.e. two members, retire in rotation at each Annual General Meeting and in their place two new members are elected by Club members at the Annual General Meeting.

                            

                         </p> 

                         

                         <p align="justify" >

                          

                               In addition, there is a Government nominee on the Board of Appeal, usually the Additional Chief Secretary, Government of Maharashtra, Home Department. 

                            

                         </p> 

                                  <br />

                          <p align="justify" >

                          

                                <b>The following are members of the Board of Appeal at present: </b>

                            

                         </p> 

                         

                            <br />

                           

                          <ul>

                             <p align="justify" >
                                  <li> Mr. Shivlal R. Daga, Chairman</li>                         
								  <li> Mr. Dilip P. Goculdas</li>
								  <li> Mr. Asif Lampwala</li>
								  <li> Mr. Ashwin B. Mehta</li>
								  <li> Mr. Hoshang J. Nazir</li>
								  <li> Mr. Manu Kumar Srivastava, I. A. S. (Govt. Nominee) </li>
								  <li> Mr. Gulamhusein A. Vahanvaty</li>

                             </p> 

                         </ul>

<?php                   

  $design->closeDiv();

  $design->rightArea();  

  $design->closeDiv();

  $design->closeDiv();

  $design->endPage();

$design = NULL; // release object