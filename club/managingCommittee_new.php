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
  ?>
  

<!--<h2>Managing Committee of the Club</h2>-->
<!--                         <p align="justify" >-->
<!--                            The management of the Club and the control over the funds and property of the Club-->
<!--                vests in the Committee consisting of 9 Club Members elected by the Club Members -->
<!--            in accordance with the provisions contained in the Articles of -->
<!--            Association of the Club. In addition to this there are two Government Nominees on the Committee who are usually the Additional Chief -->
<!--            Secretary, Government of Maharashtra, Home Department-->
<!--                and the Additional Chief Secretary, Government of Maharashtra, Revenue and Forests-->
<!--                Department. The Committee retires every year. The Chairman is elected-->
<!--                by the Members of the Managing Committee.-->
<!--                         </p> <br \>-->
                         
<!--                         <p align="justify" >-->
                             
							 
							 
							 
<!--                            <b>The following are the Committee Members of the Club at present: </b>-->
                            
                         </p> <br \>
                        <h3 align="center"> <b></b>TRUSTEES - 2020 / 2021 </b></h3>
                         <table class="table" align="justify">
                         		<tr align="justify">
                         			<td>The Trustees, Gratuity Fund of the RWITC Ltd.</td>
                         			<td><p>Zavaray S. Poonawalla, Esquire The Secretary & CEO - Ex - Officio</p>
										<p>Convenor - Mr. NHS Mani, Secretary & CEO.</p>
									</td>
                         		</tr>

                         		<tr align="justify"> 
                         			<td>The Trustees, RWITC Ltd. Provident Fund</td>
                         			<td><p>Zavaray S. Poonawalla, Esquire  The Secretary - Ex - Officio</p>
										<p>Convenor - Mr. NHS Mani, Secretary & CEO.</p>
									</td>
                         		</tr>

                         		<tr align="justify">
                         			<td>The Trustees, RWITC Ltd., <br>Employees Superannuation Scheme (Pension Fund)</td>
                         			<td><p>Zavaray S. Poonawalla, Esquire The Secretary-Ex-Officio</p>
										<p>Convenor - Mr. NHS Mani, Secretary & CEO.</p>
									</td>
                         		</tr>

                         		<tr align="justify">
                         			<td><p>Club's representative on the Managing Committee of the <br> Maharashtra State National Sports Fund.</p></td>
                         			<td><p>Zavaray S. Poonawalla, Esquire </p></td>
                         		</tr>

                         		<tr align="justify">
                         			<td><p>Nomination and Appointment of Occupier of the <br> Club under the Factories Act 1948. </p></td>
                         			<td><p>Zavaray S. Poonawalla, Esquire</p></td>
                         		</tr>


                         </table>

                          
                            
  <?php                   
  $design->closeDiv();
  $design->rightArea();
  $design->closeDiv();
  $design->closeDiv();
    $design->endPage();
$design = NULL; // release object