<?php
include_once('../bootstrap.php');
require_once('../lib/calendar.class.php');
require_once('../lib/availibility.class.php');
require_once("../lib/users.class.php");
require_once("../lib/userchecks.php");
require_once("../lib/function.php");
require_once("../lib/calender.php");
$q = getParameterString('q','',$db);
session_start();                    
if(isset($_COOKIE['uid'])){                    
	$uid = $_COOKIE['uid'];    
} else {
	$uid = 0;
}             
$userObj = new Users($db);  

$events= array();
if (isAdminlogin()) {
	if ($_SESSION['calendar'] == "Y") { // check login
	  	$calendarObj = new Calendar($db);
	  	$calendars = new CalenderDraw();
	  	// all actions POST form submissions go here
	  	if (isset($_REQUEST['submit'])) {
  			// echo '<pre>';
  			// print_r($_POST);
  			// exit;
  			foreach($_POST['event_datas'] as $rdate => $rvalues){
  				foreach($rvalues as $rkey => $rvalue){
  					if($rvalue['centreid'] != '' && $rvalue['old_centreid'] != '' && $rvalue['centreid'] != $rvalue['old_centreid']){
  						$is_exist = $calendarObj->getexistdata($rvalue['old_centreid'], $rdate);
  						if(isset($is_exist['id'])){
  							$calendarObj->deleteCalendarByID($is_exist['id']);
  							$is_exist = $calendarObj->getexistdata($rvalue['centreid'], $rdate);
  							if(!isset($is_exist['id'])){	
  								$calendarObj->insertCalendar($rdate, $rvalue['centreid']);
  							}
  						}
  					} elseif($rvalue['centreid'] != '' && $rvalue['old_centreid'] == ''){
  						$is_exist = $calendarObj->getexistdata($rvalue['centreid'], $rdate);
  						if(!isset($is_exist['id'])){
  							$calendarObj->insertCalendar($rdate, $rvalue['centreid']);
  						}
  					} else {
  						if($rvalue['centreid'] == '' && $rvalue['old_centreid'] != ''){
  							$is_exist = $calendarObj->getexistdata($rvalue['old_centreid'], $rdate);
  							if(isset($is_exist['id'])){
  								$calendarObj->deleteCalendarByID($is_exist['id']);
  							}
  						}
  					}
  				}
  			}
			$msg = "You have Saved the Races Successfully.";	
		}
	  	//$aCalObj = new AvailibilityCalendar($db);
	  	//$allCentres = $calendarObj->getCentresList();
	  	//$centresList = array();
	  	//foreach($allCentres as $centre) {
			//$centresList[$centre['id']] = $centre['centre'];
	  	//}

	  	if(isset($_POST['txtmonth'])) {
			$month = $_POST['txtmonth'];
		} elseif (isset($_GET['month'])) {
			$month = $_GET['month'];
		} else {
			$month = date('m');
		}

		if(isset($_POST['txtyear'])) {
			$year = (int) $_POST['txtyear'];
		} elseif (isset($_GET['year'])) {
			$year = (int) $_GET['year'];
		} else {
			$year = (int) date('Y');
		}
		//echo $year;exit;

		unset($_POST['txtyear']);
		unset($_POST['txtmonth']);
		unset($_GET['year']);
		unset($_GET['month']);
		
		/* select month control */
		$select_month_control = '<select style = "color : green" name="month" id="month">';
		for($x = 1; $x <= 12; $x++) {
			$select_month_control.= '<option value="'.$x.'"'.($x != $month ? '' : ' selected="selected"').'>'.date('F',mktime(0,0,0,$x,1,$year)).'</option>';
		}
		$select_month_control.= '</select>';

		/* select year control */
		$year_range = 26;
		//echo $year;exit;
		$select_year_control = '<select style = "color : green" name="year" id="year">';
		for($x = 2000; $x <= 2020; $x++) {
			//echo $x;
			$select_year_control.= '<option value="'.$x.'"'.($x != $year ? '' : ' selected="selected"').'>'.$x.'</option>';
		}
		//exit;
		$select_year_control.= '</select>';


		if($month == 12){
			$next_month = 1;
			$next_year = $year + 1;
		} else {
			$next_month = $month + 1;
			$next_year = $year;
		}

		$nmonth = 'admin/calendarManager.php?month='.$next_month.'&year='.$next_year;
		/* "next month" control */
		$next_month_link = '<a style = "color : green" href="'.$nmonth.'" class="control">Next Month &gt;&gt;</a>';

		if($month == 1){
			$prev_month = 12;
			$prev_year = $year - 1;
		} else {
			$prev_month = $month - 1;
			$prev_year = $year;
		}
		$pmonth = 'admin/calendarManager.php?month='.$prev_month.'&year='.$prev_year;
		/* "previous month" control */
		$previous_month_link = '<a style = "color : green" href="'.$pmonth.'" class="control">&lt;&lt; 	Previous Month</a>';

		//$hiddenf = '<input type="hidden" name = "txtmonth" id = "txtmonth" value = "'.$month.'"><input type="hidden" name = "txtyear" id = "txtyear" value = "'.$year.'">';

		//$action = 'admin/calendarManager.php';
		/* bringing the controls together */
		//$controls = '<form method="post" action = "'.$action.'">'.$select_month_control.$select_year_control.$hiddenf.'&nbsp;<input type="submit" name="submit" value="Go" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$previous_month_link.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$next_month_link.' </form>';
		$controls = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$previous_month_link.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$next_month_link.' <input type="submit" name="submit" value="Save" />';
		
		$start = date('Y-m-d', strtotime($year.'-'.$month.'-01'));
		$number_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
		$end = date('Y-m-d', strtotime($year.'-'.$month.'-'.$number_days));
			
		$data = array();
		if($start != ''){
			$data['start'] = $start;
		}
		if($end != ''){
			$data['end'] = $end;  
		}
		// echo '<pre>';
		// print_r($data);
		// exit;
		$calendarList = $calendarObj->getAllCalendar($data);
		$allCentres = $calendarObj->getCentresList();
		$centresList = array();
		foreach($allCentres as $centre) {
			$centresList[$centre['id']] = $centre['centre'];
		}
		foreach ($calendarList as $calendar){
			$events[$calendar['racedate']][] = array(
				'id' => $calendar['id'],
				'racedate' => $calendar['racedate'],
				'centre' => $centresList[$calendar['centreid']],
				'centreid' => $calendar['centreid'],
			);
		}

		$html = '<h2 style="float:left; padding-right:30px;">'.date('F',mktime(0,0,0,$month,1,$year)).' '.$year.'</h2>';
		$html .= '<div style="float:left;">'.$controls.'</div>';
		$html .= '<div style="clear:both;"></div>';
		$html .= $calendars->draw_calendar($month,$year,$events,$centresList);
		$html .= '<br /><br />';
		// echo '<pre>';
		// print_r($html);
		// exit;
	} else {
		$msg = "You do not have access to this page.";
	}  
} else {
	$secmsg = "Please login to access this page";
}
?>
<?php 
$pageTitle ='Calendar Manager';        
$design = new Design();
$design->js="
	<script type='text/javascript' src='js/ics.js'></script>
	<script type='text/javascript' src='js/ics.deps.min.js'></script>
	<script type='text/javascript' src='js/jquery-ui-custom.js'></script>
	<script type='text/javascript' src='js/lib/moment.min.js'></script>
	<script type='text/javascript' src='js/fullcalendar.min.js'></script>
	<script type='text/javascript' src='js/jquery.ui.core.min.js'></script>    
    <script type='text/javascript' src='js/jquery.ui.datepicker.min.js'></script>
";
$design->css ="
	<link rel='stylesheet' type='text/css' href='css/fullcalendar.css' />
	<link type='text/css' href='css/jquery.ui.all.css' rel='stylesheet' />
  	<style type='text/css'>
  		/* calendar */
		table.calendar		{ border-left:1px solid #fc0606; }
		tr.calendar-row	{  }
		td.calendar-day	{ min-height:80px; font-size:11px; position:relative; } * html div.calendar-day { height:80px; }
		td.calendar-day:hover	{ background:#fc0606; }
		td.calendar-day-np	{ background:#eee; min-height:80px; } * html div.calendar-day-np { height:80px; }
		td.calendar-day-head { background:#fc0606; font-weight:bold; text-align:center; width:120px; padding:5px; border-bottom:1px solid #999; border-top:1px solid #999; border-right:1px solid #999; }
		div.day-number		{ background:#fc0606; padding:5px; color:#fff; font-weight:bold; float:right; margin:-5px -5px 0 0; width:20px; text-align:center; }
		/* shared */
		td.calendar-day, td.calendar-day-np { width:170px; padding:5px; border-bottom:1px solid #fc0606; border-right:1px solid #999; }

		div.day-number	 { 
			background:#fc0606; 
			position:absolute; 
			z-index:2; 
			top:-5px; 
			right:-25px; 
			padding:5px; 
			color:#fff; 
			font-weight:bold; 
			width:20px; 
			text-align:center; 
		}


		.selected1{
			color : red;
		}


		td.calendar-day, td.calendar-day-np { 
			width:170px; 
			padding:5px 25px 110px 5px; 
			border-bottom:1px solid #999; 
			border-right:1px solid #999; 
		}

		.cpadd {
			padding-left: 10px;
			padding-top: 11px;
			padding-bottom: 15px;
		}
	</style>    
";
$design->jqueryJs = "
	jQuery.browser = {};
	(function () {
		jQuery.browser.msie = false;
		jQuery.browser.version = 0;
		if (navigator.userAgent.match(/MSIE ([0-9]+)\./)) {
			jQuery.browser.msie = true;
			jQuery.browser.version = RegExp.$1;
		}
	})();
	$('#calendar_date').datepicker({
			showOn: 'button',
			buttonImage: '/images/calendar.gif',
			buttonImageOnly: true,
			dateFormat : 'yy-mm-dd'
		});
  "; 
$design->startPage("$pageTitle");  
$design->writeLogoTickerMenu();
$design->openDiv("contentWrapper");
$design->openDiv("infoWrapper");
$design->openDiv("leftArea");
//echo $msg;
?>  
<?php 
	if (!empty($msg)) {?>
		<div class="message">
			<?php echo $msg; ?>
		</div>
	<?php } ?>
	<?php if (!empty($secmsg)) {?>
		<div class="message">
			<?php echo $secmsg; ?>
		</div>
	<?php } ?>    
	<?php if ($_SESSION['calendar'] == "Y") { ?>
		<div class="submenu">  
			<div style="float:right;">
				<a style="float:left;" href="admin/dashboard.php">Dashboard</a>
				<a style="float:left; margin-left: 5px;" href="admin/adminlogin.php?q=logout">Logout</a>
			</div>
		</div>
		<br />   
		<?php //if ($q=="new-calendar" || $q=="edit-calendar") { ?>              
			<form name="calendarForm" method="post" action="admin/calendarManager.php">
				<?php echo $html; ?>
				<!-- <div id='calendar'></div> -->
			</form>
		<?php //} ?>
	<?php } ?>            
<?php                   
	$design->closeDiv();
  	//$design->rightArea();  
 	//$design->closeDiv();
  	$design->closeDiv();
	$design->pageClose();
	$design = NULL; // release object
?>