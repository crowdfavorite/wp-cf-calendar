<?php
/*
Plugin Name: CF Calendar 
Plugin URI: http://crowdfavorite.com 
Description: Calendar 
Version: 1.0 
Author: Crowd Favorite
Author URI: http://crowdfavorite.com
*/

// ini_set('display_errors', '1'); ini_set('error_reporting', E_ALL);

// Constants
	define('CFCAL_VERSION', '1.0');
	define('CFCAL_DIR',trailingslashit(realpath(dirname(__FILE__))));

// Includes
	include('classes/calendar.class.php');
	include('classes/message.class.php');


if (!defined('PLUGINDIR')) {
	define('PLUGINDIR','wp-content/plugins');
}


load_plugin_textdomain('cfcal');


function cfcal_init() {
// TODO
}
add_action('init', 'cfcal_init');


function cfcal_request_handler() {
	if (!empty($_GET['cf_action'])) {
		switch ($_GET['cf_action']) {
			case 'cfcal_admin_js':
				cfcal_admin_js();
				break;
			case 'cfcal_admin_css':
				cfcal_admin_css();
				die();
				break;
		}
	}
	if (!empty($_POST['cf_action'])) {
		switch ($_POST['cf_action']) {
			case 'cfcal_day_popup':
				cfcal_ajax_popup($_POST['post_id'], $_POST['cfcal_wheight']);
				break;

			// case 'cfcal_update_settings':
			// 	cfcal_save_settings();
			// 	wp_redirect(trailingslashit(get_bloginfo('wpurl')).'wp-admin/options-general.php?page='.basename(__FILE__).'&updated=true');
			// 	die();
			// 	break;
		}
	}
	
	if (class_exists('cfcal_calendar') && is_admin()) {
		global $cfcal_calendar;
		
		$cfcal_calendar = new cfcal_calendar();
	}
}
add_action('init', 'cfcal_request_handler');

wp_enqueue_script('jquery');
wp_enqueue_script('cfcal_admin_js', trailingslashit(get_bloginfo('url')).'?cf_action=cfcal_admin_js', array('jquery'));

function cfcal_admin_js() {
	header('Content-type: text/javascript');
	?>
	;(function($) {
		$(function() {
			$('.cfcal-list li').click(function() {
				var _this = $(this);
				var wheight = Math.floor($(window).height()*0.8);
				
				$.post("index.php", {
					cf_action:"cfcal_day_popup",
					post_id:_this.attr("id").replace("cf-posts-",""),
					cfcal_wheight:wheight-20
				}, function(r) {
					res = eval("("+r+")");
					if (res.success != false) {
						cfcal_popup(res.html, null, wheight);
					}
					else {
						cfcal_popup_error(res.html, res.message);
					}
				});
				
				return false;
			});
		});
	})(jQuery);
	<?php
	die();
}

function cfcal_admin_css() {
	header('Content-type: text/css');
	?>
	.cfcal-calendar td {
		border:1px solid #D5D5D5;
	}
	.cfcal-calendar thead td,
	.cfcal-calendar tfoot td {
		background-color:#D5D5D5;
	}
	.cfcal-calendar tbody td {
		height:160px;
		padding:0;
		overflow:hidden;
	}
	.cfcal-today {
		background-color:#FFFEED;
	}
	.cfcal-today-title {
		color:#639ABA;
	}
	.cfcal-day-title {
		text-align:right;
		background-color:#ECF1FA;
		padding:2px 5px;
	}
	.cfcal-day-content {
		padding:2px;
		overflow:hidden;
		height:125px;
	}
	li.cf-posts {
		background-color:#CEECC8;
	}
	li.status-draft {
		background-color:#F3C8BD;
	}
	.cfcal-list li {
		white-space:nowrap;
		cursor:pointer;
		margin-bottom:2px;
		padding:2px;
		-moz-border-radius: 4px;
		-khtml-border-radius: 4px;
		-webkit-border-radius: 4px;
		border-radius: 4px;
	}
	.cfcal-list li:hover {
		background-image:url(../wp-content/plugins/cf-calendar/images/pencil.png);
		background-position:center right;
		background-repeat:no-repeat;
	}
	<?php
	echo file_get_contents(CFCAL_DIR.'css/popup.css');
	die();
}

function cfcal_admin_head() {
	echo '<link rel="stylesheet" type="text/css" href="'.trailingslashit(get_bloginfo('url')).'?cf_action=cfcal_admin_css" />';
}
add_action('admin_head', 'cfcal_admin_head');

function cfcal_admin_menu() {
	if (current_user_can('manage_options')) {
		add_options_page(
			__('CF Calendar', 'cfcal')
			, __('CF Calendar', 'cfcal')
			, 10
			, basename(__FILE__)
			, 'cfcal_settings_form'
		);
		add_submenu_page(
			'edit.php',
			__('CF Calendar', 'cfcal'),
			__('CF Calendar', 'cfcal'),
			'edit_posts',
			'cf-calendar',
			'cfcal_calendar'
		);
	}
}
add_action('admin_menu', 'cfcal_admin_menu');

function cfcal_plugin_action_links($links, $file) {
	$plugin_file = basename(__FILE__);
	if (basename($file) == $plugin_file) {
		$settings_link = '<a href="options-general.php?page='.$plugin_file.'">'.__('Settings', 'cfcal').'</a>';
		array_unshift($links, $settings_link);
	}
	return $links;
}
add_filter('plugin_action_links', 'cfcal_plugin_action_links', 10, 2);

function cfcal_settings_form() {
	?>
	<div class="wrap">
		<?php echo screen_icon().'<h2>'.__('CF Calendar', 'cfcal').'</h2>'; ?>
	</div>
	<?php
}

function cfcal_save_settings() {
	if (!current_user_can('manage_options')) {
		return;
	}
}

function cfcal_calendar() {
	global $cfcal_calendar;
	
	?>
	<style type="text/css">
	</style>
	<div class="wrap">
		<?php echo screen_icon().'<h2>Calendar</h2>'; ?>
		<br /><br />
		<?php echo $cfcal_calendar->admin(false, $_GET['month'], $_GET['year']); ?>
	</div>
	<?php
}

function cfcal_day_posts($items = array(), $month = 0, $day = 0, $year = 0) {
	if ($month == 0 || $day == 0 || $year == 0) { return $items; }
	global $post;
	
	if (!is_array($items['cf-posts'])) {
		$items['cf-posts'] = array();
	}
	
	$day_posts = new WP_Query(array(
		'day' => $day,
		'monthnum' => $month,
		'year' => $year,
		'showposts' => 3
	));
	
	while($day_posts->have_posts()) {
		$day_posts->the_post();
		
		$items['cf-posts'][] = array(
			'id' => get_the_ID(),
			'title' => get_the_title(),
			'status' => get_post_status()
		);
	}
	
	wp_reset_query();
	unset($day_posts);
	return $items;
}
add_filter('cfcal-day-content', 'cfcal_day_posts', 10, 4);

function cfcal_ajax_popup($post_id = 0, $window_height) {
	if ($post_id == 0) {
		$ret = new cfcal_message(array(
			'success' => false,
			'html' => '<p>Whoops! No Post Found</p>',
			'message' => 'post id: '.$post_id.' not found'
		));
	}
	else {
		$ret = new cfcal_message(array(
			'success' => true,
			'html' => cfcal_popup($post_id, $window_height),
			'message' => null
		));
	}
	$ret->send();
}

function cfcal_popup($post_id = 0, $window_height) {
	if ($post_id == 0) { return ''; }
	global $post;
	$popup_post = new WP_Query(array(
		'p' => $post_id,
		'showposts' => 1
	));
	
	$html .= '
	<div id="cfcal-popup" class="cfcal-popup">
	';
	
	if ($popup_post->have_posts()) {
		while($popup_post->have_posts()) {
			$popup_post->the_post();
			
			$post_statuses = get_post_statuses();
			$post_status = $post_statuses[get_post_status()];
			
			$html .= '
			<div class="cfcal-popup-head">
				<span class="cfcal-popup-close">
					<a href="#close">Close</a>
				</span>
				<h2>'.get_the_time('F d, Y').'</h2>
			</div>
			<div class="cfcal-popup-content" style="max-height:'.floor($window_height).'px; overflow:auto;">
				<div class="cfcal-popup-title">
					'.get_the_title().'
				</div>
				<div class="cfcal-popup-post-info">
					<div class="author">
						<span class="description">'.__('Author', 'cfcal').':</span> '.get_the_author().'
					</div>
					<div class="categories">
						<span class="description">'.__('Categories', 'cfcal').':</span> '.get_the_category_list(', ', '', get_the_ID()).'
					</div>
					<div class="status">
						<span class="description">'.__('Status', 'cfcal').':</span> '.$post_status.'
					</div>
					<div class="post-date">
						<span class="description">'.__('Post Date', 'cfcal').':</span> '.get_the_time('F d, Y').'
					</div>
				</div>
				<div class="cfcal-popup-edit">
					<a href="'.get_edit_post_link(get_the_ID(), 'other').'">'.__('Edit This Post', 'cfcal').'</a>
				</div>
			</div>
			';
		}
	}
		
	$html .= '	
	</div>
	';
	
	wp_reset_query();
	unset($popup_post);
	return $html;
}














// Helpers

/**
 * JSON ENCODE and DECODE for PHP < 5.2.0
 * Checks if json_encode is not available and defines json_encode & json_decode
 * Uses the Pear Class Services_JSON - http://pear.php.net/package/Services_JSON
 */ 
if (!function_exists('json_encode') && !class_exists('Services_JSON')) {
	require_once('classes/external/JSON.php');
}	

/**
 * cfcal_json_encode
 *
 * @param array/object $json 
 * @return string json
 */
function cfcal_json_encode($data) {
	if (function_exists('json_encode')) {
		return json_encode($data);
	}
	else {
		global $cfcal_json_object;
		if (!($cfcal_json_object instanceof Services_JSON)) {
			$cfcal_json_object = new Services_JSON();
		}
		return $cfcal_json_object->encode($data);
	}
}

/**
 * cfcal_json_decode
 *
 * @param string $json 
 * @param bool $array - toggle true to return array, false to return object  
 * @return array/object
 */
function cfcal_json_decode($json,$array) {
	if (function_exists('json_decode')) {
		return json_decode($json,$array);
	}
	else {
		global $cfcal_json_object;
		if (!($cfcal_json_object instanceof Services_JSON)) {
			$cfcal_json_object = new Services_JSON();
		}
		$cfcal_json_object->use = $array ? SERVICES_JSON_LOOSE_TYPE : 0;
		return $cfcal_json_object->decode($json);
	}
}


?>