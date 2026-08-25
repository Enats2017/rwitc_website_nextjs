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
	<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css'>
  	<style type='text/css'>
  		#infoWrapper.col-lg-12 {
			display: flex;
			flex-direction: row-reverse;
			align-items: flex-start;
			max-width: 1500px;
			margin: 30px auto;
			float: none;
		}
		#leftArea.col-lg-9 {
			flex: 1 1 auto;
			min-width: 0;
			max-width: none;
			margin: 0;
			padding: 0 30px;
			box-sizing: border-box;
			float: none;
			width: auto;
			display: block;
		}
		.message { background: #fff3cd; border: 1px solid #ffe08a; padding: 12px 16px; border-radius: 8px; margin-bottom: 15px; font-size: 15px; }

		.calendar-header { display: flex; align-items: center; justify-content: flex-end; margin-bottom: 20px; }
		.header-links { display: flex; align-items: center; gap: 16px; }
		.header-links a { color: #0f5c33; text-decoration: none; font-weight: 600; font-size: 14px; }
		.header-links a:hover { text-decoration: underline; }

		.calendar-card { background: #fff; border: 1px solid #e2e6e4; border-radius: 12px; padding: 24px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); }
		.calendar-card h2 { color: #2b332f; font-size: 22px; margin: 0 0 4px 0; }
		.calendar-card a.control { color: #0f5c33 !important; font-weight: 600; text-decoration: none; }
		.calendar-card a.control:hover { text-decoration: underline; }
		.calendar-card select { border: 1px solid #e2e6e4; border-radius: 6px; padding: 6px 10px; color: #0f5c33 !important; font-size: 14px; }
		.calendar-card input[type='submit'] { background: #0f5c33; color: #fff; border: none; padding: 8px 18px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; }
		.calendar-card input[type='submit']:hover { background: #0c4a29; }

  		/* calendar */
table.calendar		{ border-left:1px solid #e2e6e4; border-collapse: collapse; width: 100%; min-width: 900px; margin-top: 16px; display: block; overflow-x: auto; max-width: 100%; }
		tr.calendar-row	{  }
		td.calendar-day	{ min-height:80px; font-size:11px; position:relative; } * html div.calendar-day { height:80px; }
		td.calendar-day:hover	{ background:#e6f4ec; }
		td.calendar-day-np	{ background:#f5f4ee; min-height:80px; } * html div.calendar-day-np { height:80px; }
		td.calendar-day-head { background:#0f5c33; color:#fff; font-weight:bold; text-align:center; width:120px; padding:8px; border-bottom:1px solid #0f5c33; border-top:1px solid #0f5c33; border-right:1px solid #0c4a29; }
		div.day-number		{ background:#0f5c33; padding:5px; color:#fff; font-weight:bold; float:right; margin:-5px -5px 0 0; width:20px; text-align:center; }
		/* shared */
		td.calendar-day, td.calendar-day-np { width:170px; padding:5px; border-bottom:1px solid #0f5c33; border-right:1px solid #e2e6e4; }

		div.day-number	 { 
			background:#0f5c33; 
			position:absolute; 
			z-index:2; 
			top:-5px; 
			right:-25px; 
			padding:5px; 
			color:#fff; 
			font-weight:bold; 
			width:20px; 
			text-align:center; 
			border-radius: 4px;
		}


		.selected1{
			color : #c0392b;
		}


		td.calendar-day, td.calendar-day-np { 
			width:170px; 
			padding:5px 25px 110px 5px; 
			border-bottom:1px solid #e2e6e4; 
			border-right:1px solid #e2e6e4; 
		}

		.cpadd {
			padding-left: 10px;
			padding-top: 11px;
			padding-bottom: 15px;
		}

		/* table.calendar already handles its own horizontal scroll above, no separate media rule needed */

		@media (max-width: 700px) {
			#leftArea.col-lg-9 { padding: 0 16px; }
			.calendar-card { padding: 16px; }
		}

			html, body { scrollbar-width: none; -ms-overflow-style: none; }
html::-webkit-scrollbar, body::-webkit-scrollbar { display: none; }
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
$design->openDiv("infoWrapper","col-lg-12");
$design->openDiv("leftArea",'col-lg-9');
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
		<div class="calendar-header">
			<div class="header-links">
				<!-- <a href="admin/dashboard.php">Dashboard</a>
				<a href="admin/adminlogin.php?q=logout">Logout</a> -->
			</div>
		</div>
		<?php //if ($q=="new-calendar" || $q=="edit-calendar") { ?>              
			<form name="calendarForm" method="post" action="admin/calendarManager.php">
				<div class="calendar-card">
					<?php echo $html; ?>
				</div>
				<!-- <div id='calendar'></div> -->
			</form>
		<?php //} ?>
	<?php } ?>            
<?php                   
	$design->closeDiv();
  	$design->writeLeftPanel();
  	$design->closeDiv();
  	$design->closeDiv();
	$design->pageClose();
	$design = NULL; // release object
?>