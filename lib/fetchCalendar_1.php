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
include_once("calendar.class.php");
$start = getParameterString('start','',$db);
$end = getParameterString('end','',$db);
$start = date("Y-m-d",strtotime($start));
$end = date("Y-m-d",strtotime($end));
$calendarObj = new Calendar($db);
$data['start'] = $start;
$data['end'] = $end;  
$calendarList = $calendarObj->getAllCalendar($data);
$allCentres = $calendarObj->getCentresList();
$centresList = array();
foreach($allCentres as $centre) {
	$centresList[$centre['id']] = $centre['centre'];
}
$jsonArray = array();
foreach ($calendarList as $calendar){
 	/*
 	echo "<pre>";
	print_r($dividend);
	*/
	$jsonArray[]=array(                       
		"className" => "{$centresList[$calendar['centreid']]}",
		"title" => "{$centresList[$calendar['centreid']]}",
		"start" => "{$calendar['racedate']}",
	);
}
//print_r($jsonArray);
echo json_encode($jsonArray);
