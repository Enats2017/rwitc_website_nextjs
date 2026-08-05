<?php
class CalenderDraw {
	/* draws a calendar */
	function draw_calendar($month,$year,$events = array(), $centresList){
	/* draw table */
		$calendar = '<table cellpadding="0" cellspacing="0" class="calendar">';

		/* table headings */
		$headings = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
		$calendar.= '<tr class="calendar-row"><td class="calendar-day-head">'.implode('</td><td class="calendar-day-head">',$headings).'</td></tr>';

		/* days and weeks vars now ... */
		$running_day = date('w',mktime(0,0,0,$month,1,$year));
		$days_in_month = date('t',mktime(0,0,0,$month,1,$year));
		$days_in_this_week = 1;
		$day_counter = 0;
		$dates_array = array();

		/* row for week one */
		$calendar.= '<tr class="calendar-row">';

		/* print "blank" days until the first of the current week */
		for($x = 0; $x < $running_day; $x++){
			$calendar.= '<td class="calendar-day-np">&nbsp;</td>';
			$days_in_this_week++;
		}

		/* keep going with days.... */
		for($list_day = 01; $list_day <= $days_in_month; $list_day++){
			$calendar.= '<td class="calendar-day"><div style="position:relative;height:100px;">';
			/* add in the day number */
			if(strlen($list_day) == 1) {
				$list_day = '0'.$list_day;
			}
			if(strlen($month) == 1) {
				$month = '0'.$month; 
			}
			$calendar.= '<div class="day-number">'.$list_day.'</div>';
			$event_day = $year.'-'.$month.'-'.$list_day;
			if(isset($events[$event_day])) {
				$inn = 0;
				foreach($events[$event_day] as $event) {
					$inn ++;
					$calendar .= '<div class="event">';
						$calendar .= '<select name="event_datas['.$event['racedate'].']['.$inn.'][centreid]">';
							$calendar .= '<option value="">Please Select</option>';
							foreach($centresList as $ckey => $cvalue){
								if($ckey == $event['centreid']){
									$calendar .= '<option value="'.$ckey.'" selected="selected">'.$cvalue.'</option>';
								} else {
									$calendar .= '<option value="'.$ckey.'">'.$cvalue.'</option>';
								}
							}		
						$calendar .= '</select>';
						$calendar .= '<input type="hidden" name = "event_datas['.$event['racedate'].']['.$inn.'][old_centreid]" value="'.$event['centreid'].'">';
					$calendar .= '</div>';
				}
				for($i = $inn; $i < 4; $i++){
					$inn ++;
					$calendar .= '<div class="event">';
						$calendar .= '<select name="event_datas['.$event_day.']['.$inn.'][centreid]">';
							$calendar .= '<option value="">Please Select</option>';
							foreach($centresList as $ckey => $cvalue){
								$calendar .= '<option value="'.$ckey.'">'.$cvalue.'</option>';
							}		
						$calendar .= '</select>';
						$calendar .= '<input type="hidden" name = "event_datas['.$event_day.']['.$inn.'][old_centreid]" value="">';
					$calendar .= '</div>';	
				}
			} else {
				$calendar .= '<div class="event">';
					$calendar .= '<select name="event_datas['.$event_day.'][1][centreid]">';
						$calendar .= '<option value="">Please Select</option>';
						foreach($centresList as $ckey => $cvalue){
							$calendar .= '<option value="'.$ckey.'">'.$cvalue.'</option>';
						}		
					$calendar .= '</select>';
					$calendar .= '<input type="hidden" name = "event_datas['.$event_day.'][1][old_centreid]" value="">';
				$calendar .= '</div>';
				$calendar .= '<div class="event">';
					$calendar .= '<select name="event_datas['.$event_day.'][2][centreid]">';
						$calendar .= '<option value="">Please Select</option>';
						foreach($centresList as $ckey => $cvalue){
							$calendar .= '<option value="'.$ckey.'">'.$cvalue.'</option>';
						}		
					$calendar .= '</select>';
					$calendar .= '<input type="hidden" name = "event_datas['.$event_day.'][2][old_centreid]" value="">';
				$calendar .= '</div>';
				$calendar .= '<div class="event">';
					$calendar .= '<select name="event_datas['.$event_day.'][3][centreid]">';
						$calendar .= '<option value="">Please Select</option>';
						foreach($centresList as $ckey => $cvalue){
							$calendar .= '<option value="'.$ckey.'">'.$cvalue.'</option>';
						}		
					$calendar .= '</select>';
					$calendar .= '<input type="hidden" name = "event_datas['.$event_day.'][3][old_centreid]" value="">';
				$calendar .= '</div>';
				$calendar .= '<div class="event">';
					$calendar .= '<select name="event_datas['.$event_day.'][4][centreid]">';
						$calendar .= '<option value="">Please Select</option>';
						foreach($centresList as $ckey => $cvalue){
							$calendar .= '<option value="'.$ckey.'">'.$cvalue.'</option>';
						}		
					$calendar .= '</select>';
					$calendar .= '<input type="hidden" name = "event_datas['.$event_day.'][4][old_centreid]" value="">';
				$calendar .= '</div>';
			}
			$calendar.= '</div></td>';
			if($running_day == 6){
				$calendar.= '</tr>';
				if(($day_counter+1) != $days_in_month){
					$calendar.= '<tr class="calendar-row">';
				}
				$running_day = -1;
				$days_in_this_week = 0;
			}
			$days_in_this_week++; $running_day++; $day_counter++;
		}

		/* finish the rest of the days in the week */
		if($days_in_this_week < 8){
			for($x = 1; $x <= (8 - $days_in_this_week); $x++){
				$calendar.= '<td class="calendar-day-np">&nbsp;</td>';
			}
		}
		/* final row */
		$calendar.= '</tr>';
		/* end the table */
		$calendar.= '</table>';
		/** DEBUG **/
		$calendar = str_replace('</td>','</td>'."\n",$calendar);
		$calendar = str_replace('</tr>','</tr>'."\n",$calendar);
		/* all done, return result */
		return $calendar;
	}

	function random_number() {
		srand(time());
		return (rand() % 7);
	}
	/* sample usages */
	// echo '<h2>July 2009</h2>';
	// echo draw_calendar(7,2009);
}
?>