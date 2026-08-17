<?php 
include_once('../bootstrap.php');
//include_once('design.php');

  
  $pageTitle ='Colonial Legacy';        
  $design = new Design();
   
  $design->startPage("$pageTitle");
  $design->writeLogoTickerMenu();
  $design->openDiv("contentWrapper");
  $design->openDiv("infoWrapper","col-lg-12");
  $design->openDiv("leftArea",'col-lg-9');
  ?>       
  


<table border="1" cellpadding="3" cellspacing="3" style="border-collapse: collapse" width="590" id="table11" bordercolorlight="#808000" bordercolordark="#808000" >
   <tr>
       <td>  
                <table width="580" border="0" cellspacing="2" cellpadding="2">                   
                    <tr>
                      <td height="35px" align="left">
                         <p align="justify" >
                            <span class="VisionArticleHeading"><b>Bequeathing a colonial legacy</b></span>
                         </p> 
                       </td>
                    </tr>
                    
                    <tr>
                      <td  align="left">
                         <p align="justify" >
                            <span class="VisionArticle">
                                Like cricket and croquet, horse racing in India is a legacy of the British Raj. The nomenclature of the Indian Classics (1000 Guineas, 2000 Guineas, Oaks, Derby and St Leger) devised in 1814 and even the clockwise running of the horses in the races follow the British practice.  
                            </span>
                         </p>          <br \>
                         
                         <p align="justify" >
                            <span class="VisionArticle">
                                The “thoroughbred” is believed to be a chance progeny of the Crusades in the 12th Century. Conceptually a British invention, it was genetically of Arabian origin. When the British Knights returned from the Crusades, they brought with them speedy Arabian steeds. In the 17th Century, the three ancestors of the British thoroughbred – Byerly Turk, Darley Arabian, and Godolphin Arabian – were imported into Great Britain and mated with speedy local mares to produce a brood of foals that became the “foundation” thoroughbreds.
                            </span>
                         </p>    <br \>
                         
                         <p align="justify" >
                            <span class="VisionArticle">
                                During Queen Anne’s reign (1702 - 1714), amateur match (two-horse) races made way for races with many more horses in the field. Spectators started to wager on the outcome of these races. In 1750, the Jockey Club was formed in Newmarket to regulate racing, to define the racing standards and to write the rules of fair practices.
In India, horse racing was a camp follower of the British rulers and the British Indian Army. Most of the 170 cantonments in British India – Meerut and Lucknow (an anti-clockwise course, by the way), for instance – used to have their own little race courses and race meetings. The Madras Race Course, the oldest in the country, was established in 1777, three years prior to the running of the first British Derby in Epsom (1780). When the East India Company made Calcutta (Kolkata) the capital in 1772, it gradually turned into the de facto turf capital too.

                            </span>
                         </p>    <br \>
                         
                         <p align="justify" >
                            <span class="VisionArticle">
                                The horses racing in India in the early days were cavalry horses and chargers imported from Great Britain and Arabia. The owners were mostly British, at times titled aristocracy and army officers. The first-generation Indian owners were the Maharajas of Cooch-Behar, Burdwan, Baroda, Idar, Morvi, Kolhapur, Rajpipla and Mysore among others. They started racing their horses at Mahalaxmi – and later also in Pune – after racing in Western India was started in 1880. Later on, industrialists like the textile tycoon Mathradas Goculdas and the Thackerseys joined their ranks. Some of them even had their horses running in England. Two of the then big owners in Britain – His Excellency The Aga Khan and Sir Victor Sassoon – also had some of their horses running at Mahalaxmi. 
                            </span>
                         </p> 
                         
                         
                       </td>
                    </tr>                   
                 </table> <br \>
            </td>
     </tr>
 </table>
             

<?php                   
  $design->closeDiv();
  //$design->leftArea();
  $design->rightArea();
  
  $design->closeDiv();
  $design->closeDiv();
  
  //$design->rightArea();
  ?>
              
<?php
    $design->endPage();
$design = NULL; // release object