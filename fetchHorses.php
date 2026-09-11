<?php
include_once('bootstrap.php');
require_once('lib/race.class.php');
$raceObj = new Racedata($db);
$letter = addslashes(urldecode($_REQUEST['q']));
$limit = $_REQUEST['limit'];
$horselist = $raceObj->getAllActiveHorsesByLetter($letter,$limit);
$i=0;
$data = array();
  foreach ($horselist as $horse) {
      $data[$i]['name'] = $horse['HORSENM'];
      $data[$i]['value'] = $horse['HORSENM'];
      $i++;
  }
header("Content-type: application/json");
echo json_encode($data);
exit;