<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

global $config;

// Login check
check_login ();

require_once("include/functions_reporting.php");
require_once("include/functions_events.php");

if (is_ajax()) {
	
	$save_item_shorcut  = (bool) get_parameter("save_item_shorcut");
	$update_shortcut_state = (bool) get_parameter('update_shortcut_state');
	$get_alerts_fired = (bool) get_parameter('get_alerts_fired');
	$get_critical_events = (bool) get_parameter('get_critical_events');
	$get_opened_incidents = (bool) get_parameter('get_opened_incidents');
	
	if ($save_item_shorcut) {
		$result = false;
		$data = get_parameter("data", '');
		$id_user = get_parameter('id_user', 0);
		
		if ($config['id_user'] != $id_user) return;
		
		$shortcut_data = db_get_value('shortcut_data', 'tusuario', 'id_user', $id_user);
		if ($shortcut_data !== false) {
			$serialize = $shortcut_data;
			$unserialize = json_decode($serialize, true);
			
			$unserialize['item_shorcut'][] = $data;
			$shortcut_data = array();
			$shortcut_data['shortcut_data'] = json_encode($unserialize);
			
			db_process_sql_update('tusuario', $shortcut_data, array('id_user' => $id_user));
		}

		echo json_encode($result);
		return;
	}
	
	// Update if shortcut is visible or hidden
	if ($update_shortcut_state) {
		$value = (int) get_parameter('value');
		$result = db_process_sql_update('tusuario', array('shortcut' => $value), array('id_user' => $config['id_user']));

		echo json_encode($result);
		return;
	}

	// Get critical events (realtime update)
	if ($get_alerts_fired) {
		echo sc_get_alerts_fired();
		return;
	}
	
	// Get critical events (realtime update)
	if ($get_critical_events) {
		echo sc_get_critical_events();
		return;
	}
	
	// Select only opened incidents
	if ($get_opened_incidents) {
		echo sc_get_opened_incidents();
		return;
	}
	
	return;
}

function sc_get_alerts_fired () {
	global $config;

	$data_reporting = reporting_get_group_stats();

	return $data_reporting['monitor_alerts_fired'];
}

function sc_get_critical_events () {
	global $config;

	$own_info = get_user_info ($config['id_user']);
	
	if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM"))
		$own_groups = array_keys(users_get_groups($config['id_user'], "IR"));
	else
		$own_groups = array_keys(users_get_groups($config['id_user'], "IR", false));
	
	// Get events in the last 8 hours
	$shortcut_events_update = events_get_group_events($own_groups, 28800, time());
	if ($shortcut_events_update == false)
		$shortcut_events_update = array();
	
	$critical_events_update = 0;
	foreach ($shortcut_events_update as $event_update) {
		if ($event_update['criticity'] == 4 and $event_update['estado'] == 0) {
			$critical_events_update++;
		}
	}
	
	return $critical_events_update;
}

function sc_get_opened_incidents () {
	global $config;
	
	$own_info = get_user_info ($config['id_user']);
	
	if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM")) {
		$sql = "SELECT count(*) total_incidents
				FROM tincidencia
				WHERE estado = 0 
				ORDER BY actualizacion";
		$result_incidents_update = db_get_all_rows_sql ($sql);
	}
	else {
		$own_groups = array_keys(users_get_groups($config['id_user'], "IR", false));
		$sql = "SELECT count(*) total_incidents
				FROM tincidencia
				WHERE id_grupo IN (".implode (",",array_keys ($own_groups)).")
					AND estado = 0
				ORDER BY actualizacion";
		if (!empty($own_groups)) {
			$result_incidents_update = db_get_all_rows_sql($sql);
		}
		else {
			$result_incidents_update = false;
		}
	}
	
	if ($result_incidents_update === false)
		$shortcut_incidents = 0;
	else 
		$shortcut_incidents = $result_incidents_update[0]['total_incidents'];

	return $shortcut_incidents;
}


$shortcut_state = db_get_value_filter('shortcut', 'tusuario', array('id_user' => $config['id_user']));

// If shortcut bar is disabled return to index.php
if ($shortcut_state == 0)
	return;

$own_info = get_user_info ($config['id_user']);

$shortcut_html = "<div id='shortcut_container'>";
$shortcut_html .= "<div id='shortcut_button'>";
$shortcut_html .= html_print_image("images/control_play.png", true, array("title" => __("Press here to activate shortcut bar")));
$shortcut_html .= "</div>";
$shortcut_html .= "<div id='shortcut_bar'>";

$num_shortcut_items = 0;

// Alerts item
if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM") || check_acl ($config['id_user'], 0, "AR")) {
	$alerts_fired = sc_get_alerts_fired();

	$shortcut_html .= "<a class='shortcut_item' href='index.php?sec=estado&sec2=operation/agentes/alerts_status&refr=120&filter=fired&filter_button=Filter'>";
	$shortcut_html .= html_print_image("images/op_alerts.png", true, array("title" => __("Alerts fired"), "style" => "margin-bottom: 0px;"));
	$shortcut_html .= "&nbsp;";
	$shortcut_html .= "<span id='shortcut_alerts_fired' title='" . __('Alerts fired') . "'>" . $alerts_fired . "</span>";
	$shortcut_html .= "</a>";

	$num_shortcut_items++;
}

// Events item
if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM") || check_acl ($config['id_user'], 0, "IR")) {	
	$critical_events = sc_get_critical_events();

	$shortcut_html .= "<a class='shortcut_item' href='index.php?sec=eventos&sec2=operation/events/events&status=3&severity=4&event_view_hr=8&id_group=0&group_rep=1&filter_only_alert=-1'>";
	$shortcut_html .= html_print_image("images/op_events.png", true, array("title" => __("Critical events"), "style" => "margin-bottom: 0px;"));
	$shortcut_html .= "&nbsp;";
	$shortcut_html .= "<span id='shortcut_critical_events' title='" . __('Critical events') . "'>" . $critical_events . "</span>";
	$shortcut_html .= "</a>";

	$num_shortcut_items++;
}

// Calculate opened incidents (id integria incidents are not enabled)
if ($config['integria_enabled'] == 0) {
	$shortcut_incidents = sc_get_opened_incidents();
	
	$shortcut_html .= "<a class='shortcut_item' href='index.php?sec=incidencias&sec2=operation/incidents/incident&estado=0'>";
	$shortcut_html .= html_print_image("images/incidents.png", true, array("title" => __("Incidents opened"),  "style" => "margin-bottom: 0px;"));
	$shortcut_html .= "&nbsp;";
	$shortcut_html .= "<span id='shortcut_incidents_opened' title='" . __('Incidents opened') . "'>" . $shortcut_incidents . "</span>";
	$shortcut_html .= "</a>";

	$num_shortcut_items++;
}

if ($num_shortcut_items > 0) {
	$shortcut_html .= "<span class='shortcut_item' href='javascript:;'>";
	$shortcut_html .= "<span>|</span>";
	$shortcut_html .= "</span>";
}

$shortcut_html .= "<a class='shortcut_item' href='index.php?sec=reporting&sec2=operation/reporting/custom_reporting'>";
$shortcut_html .= html_print_image("images/op_reporting.png", true, array("title" => __("View reports"), "style" => "margin-bottom: 0px;"));
$shortcut_html .= "</a>";
$num_shortcut_items++;

$shortcut_html .= "<a class='shortcut_item' href='index.php?sec=workspace&sec2=operation/messages/message_list'>";
$shortcut_html .= html_print_image("images/email_mc.png", true, array("title" => __("Create new message"), "style" => "margin-bottom: 0px;"));
$shortcut_html .= "</a>";
$num_shortcut_items++;

//Quick access
// $shortcut_data = db_get_value('shortcut_data', 'tusuario', 'id_user', $config['id_user']);
// if (!empty($shortcut_data)) {
// 	$serialize = $shortcut_data;
// 	$unserialize = json_decode($serialize, true);
	
// 	$items = $unserialize['item_shorcut'];
// }
// else {
// 	$items = array();
// }
// $shortcut_html .= "<div id='shortcut_icons_box' style='font-size: 9pt; color:#696969; font-weight: bold; display: inline; float: right; padding-right: 20px;'>" . 
// 	__("Shortcut: ");
// $shortcut_html .= "<ul style='display: inline; font-size: 9pt; color:#000;'>";
// foreach ($items as $item) {
// 	$shortcut_html .= "<li style='display: inline; padding-right: 10px;'>" . io_safe_output($item) . "</li>";
// }
// $shortcut_html .= "</ul>";

$shortcut_html .= "</div>";
$shortcut_html .= "</div>";

echo $shortcut_html;

// Login in Console and shortcut bar is disabled
// This will show and hide the shortcut value in Javascript code
if (isset($_POST['nick']) and $shortcut_state != 2) {
	html_print_input_hidden("login_console", 1);
}
else {
	html_print_input_hidden("login_console", 0);
}

html_print_input_hidden("shortcut_id_user", $config['id_user']);

?>

<script type='text/javascript'>
	$(function() {
		
		if (<?php echo json_encode((int) $shortcut_state); ?> < 2) {
			$('#shortcut_bar').hide();
			
			$('#shortcut_button>img')
				.css('transform', 'rotate(-90deg)')
				.css('-o-transform', 'rotate(-90deg)')
				.css('-ms-transform', 'rotate(-90deg)')
				.css('-moz-transform', 'rotate(-90deg)')
				.css('-webkit-transform', 'rotate(-90deg)');
		}
		else {
			$('#shortcut_button>img')
				.css('transform', 'rotate(90deg)')
				.css('-o-transform', 'rotate(90deg)')
				.css('-ms-transform', 'rotate(90deg)')
				.css('-moz-transform', 'rotate(90deg)')
				.css('-webkit-transform', 'rotate(90deg)');
		}
		
		
		$('#shortcut_button').click (function () {
			if ($('#shortcut_bar').is(":visible")) {
				$('#shortcut_bar').slideUp();

				$('#shortcut_button>img')
					.css('transform', 'rotate(-90deg)')
					.css('-o-transform', 'rotate(-90deg)')
					.css('-ms-transform', 'rotate(-90deg)')
					.css('-moz-transform', 'rotate(-90deg)')
					.css('-webkit-transform', 'rotate(-90deg)');

				jQuery.post (
					"ajax.php",
					{
						"page" : "general/shortcut_bar",
						"update_shortcut_state" : 1,
						"value" : 1
					},
					function (data) {}
				);
			}
			else {
				$('#shortcut_bar').slideDown();

				$('#shortcut_button>img')
					.css('transform', 'rotate(90deg)')
					.css('-o-transform', 'rotate(90deg)')
					.css('-ms-transform', 'rotate(90deg)')
					.css('-moz-transform', 'rotate(90deg)')
					.css('-webkit-transform', 'rotate(90deg)');

				jQuery.post (
					"ajax.php",
					{
						"page" : "general/shortcut_bar",
						"update_shortcut_state" : 1,
						"value" : 2
					},
					function (data) {}
				);
			}
		});
	});
	
	var id_user = $('#hidden-shortcut_id_user').val();

	function shortcut_check_alerts() {
		jQuery.post (
			"ajax.php",
			{
				"page" : "general/shortcut_bar",
				"get_alerts_fired": 1
			},
			function (data) {
				$('#shortcut_alerts_fired').html(data);
			}
		);
	}
	
	function shortcut_check_events() {
		jQuery.post (
			"ajax.php",
			{
				"page" : "general/shortcut_bar",
				"get_critical_events": 1
			},
			function (data) {
				$('#shortcut_critical_events').html(data);
			}
		);
	}
	
	function shortcut_check_incidents() {
		jQuery.post (
			"ajax.php",
			{
				"page" : "general/shortcut_bar",
				"get_opened_incidents": 1
			},
			function (data) {
				$('#shortcut_incidents_opened').html(data);
			}
		);
	}
	
	$(document).ready (function () {
		setInterval("shortcut_check_alerts()", (10 * 1000)); //10 seconds between ajax request
		setInterval("shortcut_check_events()", (10 * 1000)); //10 seconds between ajax request
		setInterval("shortcut_check_incidents()", (10 * 1000)); //10 seconds between ajax request
		
		//To make a link as item for drag only put "item_drag_shortcut" as class.
		
		//TODO: In the future show better as icons and the handle some icon.
		//TODO: Remove the class "item_drag_shortcut" for avoid drag.
		//TODO: Method for remove items.
		
		$("#shortcut_icons_box").droppable({
			drop: function( event, ui ) {
				var item = ui.draggable.clone();
				//unescape for avoid change returns 
				var content_item = unescape($('<div id="content_item"></div>').html(item).html()); //hack
				
				//Add the element
				$("<li style='display: inline; padding-right: 10px;'></li>").html(item).appendTo($("#shortcut_icons_box > ul"));
				
				jQuery.post ('ajax.php', 
					{"page": "general/shortcut_bar",
					"save_item_shorcut": 1,
					"id_user": "<?php echo $config['id_user'];?>",
					"data": content_item
					},
					function (data) {
					}
				);
			}
		});
		
		$(".item_drag_shortcut").draggable({
			appendTo: 'body',
			helper: "clone",
			scroll: false
		});
	});
</script>