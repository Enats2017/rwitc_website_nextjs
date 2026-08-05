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
include_once("availibility.class.php");
$start = getParameterString('start','',$db);
$end = getParameterString('end','',$db);
//echo "START==>".date("Y-m-d",$start)."==>END==>".date("Y-m-d",$end);
$start = date("Y-m-d",$start);
$end = date("Y-m-d",$end);
$calendarObj = new AvailibilityCalendar($db);  
$calendarList = $calendarObj->getAllCalendar();
/*$allCentres = $calendarObj->getCentresList();*/
  $centresList = array();
  foreach($allCentres as $centre) {
      $centresList[$centre['id']] = $centre['centre'];
  }
//print_r($trackworklist);
//print_r($raceDates);
$jsonArray = array();
foreach ($calendarList as $calendar){
 /*   echo "<pre>";
        print_r($dividend);
    echo "</pre>";    */
    $jsonArray[]=array(                       
                       "className" => "{1}",
                       "title" => "Not Available",
                       "start" => "{$calendar['racedate']}",
                      );
}
//print_r($jsonArray);
echo json_encode($jsonArray);
