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
include_once("dividends.class.php");
$start = getParameterString('start','',$db);
$end = getParameterString('end','',$db);


//echo "START==>".date("Y-m-d",$start)."==>END==>".date("Y-m-d",$end);

  	$start = date("Y-m-d",strtotime($start));
	$end = date("Y-m-d",strtotime($end));

//print_r($start);
//echo '<pre>';
//print_r($end);
//exit;
$dividendObj = new Dividend($db);  
$dividendsList = $dividendObj->getAlldividends();
$allCentres = $dividendObj->getCentresList();
  $centresList = array();
  foreach($allCentres as $centre) {
      $centresList[$centre['id']] = $centre['centre'];
  }

$jsonArray = array();
foreach ($dividendsList as $dividend){
    //echo "<pre>";
      //  print_r($dividend);
    //echo "</pre>";    
    $jsonArray[]=array(                       
                       "className" => "{$centresList[$dividend['centreid']]}",
                       "title" => "{$centresList[$dividend['centreid']]}",
                       "start" => "{$dividend['div_date']}",
                       "url"   => DIVIDENDS_BASE."/".$dividend['filename']
                      );
}
//print_r($jsonArray);
echo json_encode($jsonArray);