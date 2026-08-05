<?php

	/*$year = date('Y');
	$month = date('m');

	echo json_encode(array(
	
		array(
			'id' => 111,
			'title' => "Event1",
			'start' => "$year-$month-10",
			'url' => "http://yahoo.com/"
		),
		
		array(
			'id' => 222,
			'title' => "Event2",
			'start' => "$year-$month-20",
			'end' => "$year-$month-22",
			'url' => "http://yahoo.com/"
		)
	
	));*/
include_once('../bootstrap.php');
include_once("gallery.class.php");
$start = getParameterString('start','',$db);
$end = getParameterString('end','',$db);
//echo "START==>".date("Y-m-d",$start)."==>END==>".date("Y-m-d",$end);
$start = date("Y-m-d",$start);
$end = date("Y-m-d",$end);
$imagesObj = new Image($db);  
//$dividendsList = $dividendObj->getAlldividends();
$sponsorsList = $imagesObj->getAllSponsors();
$jsonArray = array();
$sponsors = array_slice($sponsorsList,1);
//print_r($sponsors);
//exit;
foreach ($sponsors as $sponsor){
 /*   echo "<pre>";
        print_r($dividend);
    echo "</pre>";    */
    $jsonArray[]=array(                                             
                       "title" => "{$sponsor['sponsor_name']}",
                       "start" => "{$sponsor['racedate']}",
                       "url"   => "/sponsorsGallery.php?date={$sponsor['racedate']}&id={$sponsor['id']}"
                      );
}
//print_r($jsonArray);
echo json_encode($jsonArray);
