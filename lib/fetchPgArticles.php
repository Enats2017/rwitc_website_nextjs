<?php
include_once('../bootstrap.php');
include_once("pgarticles.class.php");
$start = getParameterString('start','',$db);
$end = getParameterString('end','',$db);

//Start  showing archives if start date is greater than 26 Jun 2010 
$firstDate = mktime(0,0,0,6,26,2010);   // 26 Jun 2010
if ($start >= $firstDate) {
    $start = date("Y-m-d",$start);
    $end = date("Y-m-d",$end);
}
    
    $pgArticles = new PGArticles($db);
    $articleDates = $pgArticles->getDistinctArticledatesByRange($start,$end);
    
    $jsonArray = array();
    //print_r($articleDates);
    foreach ($articleDates as $articleDate) {           
        $pre = $post = 0;
        try {
            
            $pre = $pgArticles->getArticleIdByDateAndType($articleDate,"PRE");
        } catch (Exception $err) {
             $pre = "";
        }
        //echo "PRE==>$pre";
        try {
            
           $post = $pgArticles->getArticleIdByDateAndType($articleDate,"POST");        
        } catch (Exception $err) {
             $post = "";
        }
            
        if ($pre) { 
            $jsonArray[]=array("id"    => "$pre",
                               "className" => "prerace",
                               "title" => "PRE",
                               "start" => "{$articleDate}",
                               "url"   => "/viewPgArticles.php?id={$pre}"
                              );
       }
       if ($post) {
        $jsonArray[]=array( "id"    => "3",
                            "className" => "postrace",                     
                           "title" => "POST",
                           "start" => "{$articleDate}",
                           "url"   => "/viewPgArticles.php?id={$post}"
                          );
       }       
    }                            
echo json_encode($jsonArray);
