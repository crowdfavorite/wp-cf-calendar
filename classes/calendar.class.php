<?php

/**
 * Class for building and displaying a calendar
 * in the WordPress Admin.  Functionality in place
 * to add content via filters
 *
 * @package cfcal_calendar
 */
class cfcal_calendar {
	
	protected $days_of_week = array(
		0 => array(
			'short' => 'Sun',
			'long' => 'Sunday'
		),
		1 => array(
			'short' => 'Mon',
			'long' => 'Monday'
		),
		2 => array(
			'short' => 'Tue',
			'long' => 'Tuesday'
		),
		3 => array(
			'short' => 'Wed',
			'long' => 'Wednesday'
		),
		4 => array(
			'short' => 'Thu',
			'long' => 'Thursday'
		),
		5 => array(
			'short' => 'Fri',
			'long' => 'Friday'
		),
		6 => array(
			'short' => 'Sat',
			'long' => 'Saturday'
		)
	);
	
	public function __construct() {
		add_action('init', array($this, 'request_handler'), 11);
		
		wp_enqueue_script('cfcal-calendar-admin-js', trailingslashit(get_bloginfo('url')).'?cfcal_action=cfcal_admin_js', array('jquery'), CFCAL_VERSION);
	}
	
	/**
	 * get_date - Checks to see if the date inputted is present, and if not gets the Date format for the current day
	 *
	 * @param string $date - Date to check
	 * @param string $format - Format to use if the date is not present
	 * @return string - Date passed in if present, if not the current date using the format passed in
	 */
	public function get_date($date = false, $format = 'd') {
		if (!$date) {
			$date = date($format);
		}
		return $date;
	}
	
	/**
	 * Take the current month and year, and return the previous month with the proper year
	 *
	 * @param string $month - Any month
	 * @param string $year - Any Year
	 * @return array - Array of the previous month with the proper year
	 */
	public function get_pre_date($month, $year) {
		$month--;
		if ($month <= 0) {
			$month = 12;
			$year--;
		}
		return array('month' => $month, 'year' => $year);
	}

	/**
	 * Take the current month and year, and return the next month with the proper year
	 *
	 * @param string $month - Any month
	 * @param string $year - Any Year
	 * @return array - Array of the next month with the proper year
	 */
	public function get_post_date($month, $year) {
		$month++;
		if ($month > 12) {
			$month = 1;
			$year++;
		}
		return array('month' => $month, 'year' => $year);
	}
	
	/**
	 * Get the Base URL string for the Calendar Page
	 *
	 * @return void
	 */
	public function get_base_url() {
		parse_str($_SERVER['QUERY_STRING'], $output);
		$result = false;
		if (isset($_GET['month'])) {
			unset($output['month']);
		}
		if (isset($_GET['year'])) {
			unset($output['year']);
		}
		
		return trim(get_bloginfo('url').substr_replace($_SERVER['REQUEST_URI'], http_build_query($output), strpos($_SERVER['REQUEST_URI'], '?')+1), '?');
	}
	
	/**
	 * Build the Calendar Navigation with links to the Previous month, the Next month, and a link to the Current month.
	 *
	 * @param string $month - Current Month
	 * @param string $year - Current Year
	 * @return string - Constructed Navigation for the Calendar
	 */
	public function navigation($month, $year) {
		$pre_navigation = apply_filters('cfcal-build-month-pre-navigation', '', $month, $year);
		$post_navigation = apply_filters('cfcal-build-month-post-navigation', '', $month, $year);
		
		$pre_date = $this->get_pre_date($month, $year);
		$post_date = $this->get_post_date($month, $year);
		$pre_month = $pre_date['month'];
		$pre_year = $pre_date['year'];
		$post_month = $post_date['month'];
		$post_year = $post_date['year'];
		
		$base_url = $this->get_base_url();
		
		$html = $pre_navigation.'<a href="'.$base_url.'&month='.$pre_month.'&year='.$pre_year.'">&laquo;</a> <a href="'.$base_url.'&month='.date('n').'&year='.date('Y').'">Today</a> <a href="'.$base_url.'&month='.$post_month.'&year='.$post_year.'">&raquo;</a>'.$post_navigation;
		return $html;
	}
	
	public function request_handler() {
		if (!empty($_GET['cfcal_action'])) {
			switch ($_GET['cfcal_action']) {
				case 'cfcal_admin_js':
					$this->js();
					break;
			}
		}
	}
	
	public function js() {
		header('Content-type: text/javascript');
		$js = '';
		
		$js .= file_get_contents(CFCAL_DIR.'js/jquery.DOMWindow.js');
		$js .= file_get_contents(CFCAL_DIR.'js/json2.js');
		$js .= file_get_contents(CFCAL_DIR.'js/popup.js');
		
		// echo and leave
		echo $js;
		exit;
	}
	
	/**
	 * Builds the HTML content for the admin screen and returns or echos based on what is set
	 *
	 * @param bool $echo - True = will echo content | False = will return content
	 * @param string $month - Month to display
	 * @param string $year - Year to display
	 * @return html - Built Calendar
	 */
	public function admin($echo = false, $month, $year) {
		do_action('cfcal_pre_calendar', $this);
		
		$this->ret .= $this->build_month($month, $year);
		
		if ($echo) {
			echo $this->ret;
		}
		else {
			return $this->ret;
		}
	}
	
	/**
	 * Builds the Month content for the month and year passed in.  If no month or year are passed in, it will
	 * go get the current month and year to display.
	 *
	 * @param string $month | (Optional) Month to display
	 * @param string $year | (Optional) Year to display
	 * @return string Built HTML Calendar
	 */
	public function build_month($month = false, $year = false) {
		$today = date('n-d-Y');
		
		// Check to see if the month and year are set using the get_date function
		$month = $this->get_date($month, 'm');
		$year = $this->get_date($year, 'Y');
		
		// Get the WP setting for start of week
		/***** FUTURE DEVELOPMENT *****/
		// $week_begins = intval(get_option('start_of_week'));
		$week_begins = 0;
		$weekday_order = array();
		
		// Get the Base URL of the settings page
		$base_url = $this->get_base_url();
		
		// Get the Pre and Post dates needed
		$pre_date = $this->get_pre_date($month, $year);
		$post_date = $this->get_post_date($month, $year);
		$pre_month = $pre_date['month'];
		$pre_year = $pre_date['year'];
		$post_month = $post_date['month'];
		$post_year = $post_date['year'];
		
		$html = '
		<table class="widefat cfcal-calendar">
			<thead>
				<tr>
					<td colspan="2" style="text-align:left; vertical-align:middle; border-right:0;">
						<h3>Today: <a href="'.$base_url.'&month='.date('n').'&year='.date('Y').'" class="cfcal-today-title">'.date('l F d, Y').'</a></h3>
					</td>
					<td colspan="3" style="text-align:center; vertical-align:middle; border-left:0; border-right:0;">
						<h3>'.date('F Y', mktime(0,0,0,$month,1,$year)).'</h3>
					</td>
					<td colspan="2" style="text-align:right; vertical-align:middle; border-left:0;" class="cfcal-navigation">
						'.$this->navigation($month, $year).'
					</td>
				</tr>
				<tr>
					';
					// Display the days of the week in td's
					$count_dow = count($this->days_of_week);
					for ($i = 0; $i < $count_dow; $i++){
						$position = $i+$week_begins;
						if ($position >= $count_dow) {
							$position = $position-($count_dow);
						}
						$html .= '
						<th style="text-align:center;" style="width:14.25%;">' . $this->days_of_week[$position]['short'] . '</th>
						';
						$weekday_order[] = $position;
					}
					$html .= '
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="2" style="text-align:left; vertical-align:middle; border-right:0;">
					</td>
					<td colspan="3" style="text-align:center; vertical-align:middle; border-left:0; border-right:0;">
						<h3>'.date('F Y', mktime(0,0,0,$month,1,$year)).'</h3>
					</td>
					<td colspan="2" style="text-align:right; vertical-align:middle; border-left:0;" class="cfcal-navigation">
						'.$this->navigation($month, $year).'
					</td>
				</tr>
			</tfoot>
		';
		
		$timestamp = mktime(0,0,0,$month,1,$year);
		$maxday    = date("t",$timestamp);
		$thismonth = getdate ($timestamp);
		$startday  = $thismonth['wday'];
		$unixmonth = mktime(0, 0 , 0, $month, 1, $year);
		$pad = calendar_week_mod(date('w', $unixmonth)-$week_begins);
		$tracking = 0;
		
		for ($day = 1; $day <=$maxday; $day++) {
			// Checking to see if the first day of the month falls on the first day of the week being displayed
			// If not, we need to add days from the previous month here
			if ($pad > 0 && $day == 1) {
				$html .= '<tr>';
				$pre_timestamp = mktime(0,0,0,$pre_month,1,$pre_year);
				$pre_maxday    = date("t",$pre_timestamp);
				
				for ($j=($pre_maxday-$pad+1);$j<=$pre_maxday;$j++) {
					$today_class = '';
					$day_text = $pre_month.'-'.$j.'-'.$pre_year;
					if ($today == $day_text) {
						$today_class = ' cfcal-today';
					}
					$html .= '<td class="cfcal-day cfcal-day-premonth'.$today_class.'" style="width:14.25%;">'.$this->build_day($j, $pre_month, $pre_year).'</td>';
					$tracking++;
				}
			}
			// Adding tr's where neccesary 
			else if (($tracking % 7) == 0) { 
				$html .= '<tr>';
			}

			// Checking to see if the day being build is Today.  If so give it a special class
			$today_class = '';
			$day_text = $month.'-'.$day.'-'.$year;
			if ($today == $day_text) {
				$today_class = ' cfcal-today';
			}
			
			// Build the day for the current month
			$html .= '<td class="cfcal-day'.$today_class.'" style="width:14.25%;">'.$this->build_day($day, $month, $year).'</td>';
			
			// Adding tr's where neccesary 
			if (($tracking % 7) == 6) { 
				$html .= '</tr>';
			}
			$tracking++;
			
			// Checking to see if the last day of the month falls on the last day of the week being displayed
			// If not, fill in with days from the next month
			if ($day == $maxday) {
				if (($tracking % 7) > 0) {
					$ending = 7 - ($tracking % 7);
					
					for ($j=1; $j<=$ending; $j++) {
						$today_class = '';
						$day_text = $post_month.'-'.$j.'-'.$post_year;
						if ($today == $day_text) {
							$today_class = ' cfcal-today';
						}
						
						$html .= '<td class="cfcal-day cfcal-day-postmonth'.$today_class.'" style="width:14.25%;">'.$this->build_day($j, $post_month, $post_year).'</td>';
					}
					$html .= '</tr>';
				}
			}
		}
		
		$html .= '
		</table>
		';
		
		return $html;
	}
	
	/**
	 * Builds the day content using 2 separate divs.  The Day Title div will display the day number, and any additional content filtered
	 * in.  The Day Content div will display the day content from the build_day_content function
	 *
	 * @param string $day - Day to display
	 * @param string $month - Month the day is in
	 * @param string $year - Year the month and day are in
	 * @return string - Built Day Content
	 */
	public function build_day($day = false, $month = false, $year = false) {
		// Check to see if the Day/Month/Year have been passed in
		$day = $this->get_date($day, 'd');
		$month = $this->get_date($month, 'm');
		$year = $this->get_date($year, 'Y');
		
		// Add the Shortened Month Name if it is the first day of the month
		$day_text = '';
		if ($day == 1) {
			$day_text = date('M', mktime(0,0,0,$month,$day,$year)).' ';
		}
		
		// Build the day title, with the month text if it exists, and the day text.  Then filter in content
		// from external sources
		$day_title = '<div class="cfcal-day-title-text">'.$day_text.$day.'</div>';
		$day_title = apply_filters('cfcal-day-title', $day_title, $month, $day, $year);
		
		// Build the day
		$html .= '
			<div class="cfcal-day-title">
				'.$day_title.'
			</div>
			<div class="cfcal-day-content">
				'.$this->build_day_content($day, $month, $year).'
			</div>
		';
		
		return $html;
	}
	
	/**
	 * Builds the day content for a specific day.  Day items are passed in via filter and displayed as Unordered lists.  Arrays
	 * passed in should have the format listed in the function
	 *
	 * @param string $day - Day to display
	 * @param string $month - Month the day is in
	 * @param string $year - Year the month and day are in
	 * @return string - Built Day Content
	 */
	public function build_day_content($day = false, $month = false, $year = false) {
		// Check to see if the Day/Month/Year have been passed in
		$day = $this->get_date($day, 'd');
		$month = $this->get_date($month, 'm');
		$year = $this->get_date($year, 'Y');
		
		// Content Items array should be formatted:
		// $content_items = array(
		// 	'1234567890' => array(
		// 		array(
		// 			'id' => '1',
		// 			'title' => 'This is the title here',
		// 			'status' => 'publish',
		//			'type' => 'cf-posts'
		// 		),
		//	'1234567810' => array(
		// 		array(
		// 			'id' => '2',
		// 			'title' => 'This is the second title here',
		// 			'status' => 'draft'
		//			'type' => 'cf-posts'
		// 		)
		// 	)
		// );
		//
		
		// Filter in the items
		$content_items = array();
		$content_items = apply_filters('cfcal-day-content', $content_items, $month, $day, $year);
		
		// Only display a set number of items so it doesn't mess up the display
		$i = 0;
		$i_to_show = 6;
		
		// Build the content list
		if (is_array($content_items) && !empty($content_items)) {
			$content .= '<ul class="cfcal-list">';
			// Sort the array so everything is in chronological order
			krsort($content_items);
			foreach ($content_items as $key => $item) {
				if (isset($item['id']) && isset($item['title']) && isset($item['status'])) {
					if ($i == $i_to_show) { break; }
					$title = $item['title'];
					if (strlen($title) > 20) {
						$title = substr($title,0,20).'&hellip;';
					}
					
					$content .= '
					<li id="'.$key.'-'.$item['id'].'" class="cfcal-status-'.$item['status'].' '.$item['type'].' cfcal-js-open" title="'.$item['title'].'">
						<a class="cfcal-day-edit-link" href="'.$item['edit'].'"><img src="../'.PLUGINDIR.'/cf-calendar/images/pencil.png" /></a> <a class="cfcal-day-item-title" href="'.$item['edit'].'">'.$title.'</a>
					</li>';
					$i++;
				}
			}
			// If there are more items than space allows for, display the more link that will open the day in the popup window
			if (count($content_items) > $i_to_show) {
				$content .= '
				<li id="cfcal-'.$month.'-'.$day.'-more" class="cfcal-day-more hide-if-no-js" onclick="cfcal_open_day('.zeroise($month, 2).', '.zeroise($day, 2).', '.zeroise($year, 2).')">
					<span>'.__('More', 'cfcal').'&hellip;</span>
				</li>
				';
			}
			$content .= '</ul>';
		}
		
		return $content;
	}
}


?>