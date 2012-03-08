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

if (is_ajax()){
	require_once("include/functions_reporting.php");
	
	$save_item_shorcut  = get_parameter("save_item_shorcut", 0);

	if ($save_item_shorcut) {
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
	}
	
	return;
}
$shortcut_state = db_get_value_filter('shortcut', 'tusuario', array('id_user' => $config['id_user']));

// If shortcut bar is disabled return to index.php
if ($shortcut_state == 0)
	return;

if (is_ajax()) {
	require_once("include/functions_events.php");
	
	
	$update_shortcut_state = get_parameter('update_shortcut_state', 0);
	$get_critical_events = get_parameter('get_critical_events', 0);
	$get_opened_incidents = get_parameter('get_opened_incidents', 0);
	
	// Update if shortcut is visible or hidden
	if ($update_shortcut_state){
		$value = get_parameter('value', 0);
		db_process_sql_update('tusuario', array('shortcut' => $value), array('id_user' => $config['id_user']));
	}
	
	// Get critical events (realtime update)
	if ($get_critical_events){
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
		foreach($shortcut_events_update as $event_update){
			if ($event_update['criticity'] == 4 and $event_update['estado'] == 0) {
				$critical_events_update++;
			}
		}
		
		echo $critical_events_update;
	}
	
	// Select only opened incidents
	if ($get_opened_incidents) {		
		$own_info = get_user_info ($config['id_user']);
		
		if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM"))
			$own_groups = array_keys(users_get_groups($config['id_user'], "IR"));
		else
			$own_groups = array_keys(users_get_groups($config['id_user'], "IR", false));
		
		$sql = "SELECT count(*) total_incidents FROM tincidencia WHERE 
			id_grupo IN (".implode (",",array_keys ($own_groups)).") AND estado IN (0) 
			ORDER BY actualizacion";
			
			
		if (!empty($own_groups)) {					
			$result_incidents_update = db_get_all_rows_sql ($sql);
		}
		else {
			$result_incidents_update = false;
		}

		if ($result_incidents_update ===  false)
			$shortcut_incidents = 0;
		else 
			$shortcut_incidents = $result_incidents_update[0]['total_incidents'];
		
		echo $shortcut_incidents;
	}
	
	return;
}

if ($shortcut_state == 2) {
	echo "<div id='shortcut_button' style='position: fixed; overflow: hidden; bottom: 0px; left: 0px; width: 185px; height: 40px; background-color: #FFFFFF; border: 1px solid #808080;  border-top-left-radius: 10px; border-top-right-radius: 10px;'>";
}
else{
	echo "<div id='shortcut_button' style='position: fixed; overflow: hidden; bottom: 0px; left: 0px; width: 185px; height: 0px; background-color: #FFFFFF; border: 1px solid #808080;  border-top-left-radius: 10px; border-top-right-radius: 10px;'>";
}
	html_print_image("images/pandora_textlogo.png", false, array("title" => __("Press here to activate shortcut bar")));
echo "</div>";
if ($shortcut_state == 2) {	
	echo "<div id='shotcut_bar' style='position: fixed; overflow:hidden; bottom: 0px; left: 0px; width:100%; height: 20px; background-color:#DCDCDC; border: 1px solid #808080;'>";
}
else {
	echo "<div id='shotcut_bar' style='position: fixed; overflow:hidden; bottom: 0px; left: 0px; width:100%; height: 0px; background-color:#DCDCDC; border: 1px solid #808080;'>";
}

echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
$own_info = get_user_info ($config['id_user']);

// If user is admin can see all groups
if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM")){		
	echo "<a href='index.php?sec=estado&sec2=operation/agentes/alerts_status&refr=120&filter=fired&free_search=&filter_button=Filter'>";
}
else {
	$own_groups = array_keys(users_get_groups($config['id_user'], "AR", false));
	if (!empty($own_groups)) {
		$alerts_group = array_shift($own_groups);
		echo "<a href='index.php?sec=estado&sec2=operation/agentes/alerts_status&refr=120&filter=fired&free_search=&ag_group=$alerts_group&filter_button=Filter'>";			
	}
}
html_print_image("images/bell.png", false, array("title" => __("Alerts fired"), "style" => "margin-bottom: -5px;"));
echo "&nbsp;";

// Calculate alerts fired 
$data_reporting = reporting_get_group_stats();

echo "<span id='shortcut_alerts_fired' style='font-size: 9pt; color:#696969; font-weight: bold;' title='" . __('Alerts fired') . "'>" . $data_reporting['monitor_alerts_fired'] . "</span>";
if (!empty($own_groups)){
	echo "</a>";
}
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

$own_info = get_user_info ($config['id_user']);

// If user is admin can see all groups
if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM")){	
	echo "<a href='index.php?sec=eventos&sec2=operation/events/events&status=3&severity=4&event_view_hr=8&ev_group=0&group_rep=1&filter_only_alert=-1'>";		
}
else {
	$own_groups = array_keys(users_get_groups($config['id_user'], "IR", false));
	if (!empty($own_groups)){
		$events_group = array_shift($own_groups);
		echo "<a href='index.php?sec=eventos&sec2=operation/events/events&status=3&severity=4&event_view_hr=8&ev_group=0&group_rep=1&ev_group=$events_group&filter_only_alert=-1'>";		
	}			
}
html_print_image("images/lightning_go.png", false, array("title" => __("Critical events"), "style" => "margin-bottom: -5px;"));
echo "&nbsp;";		

// Calculate critical events (not validated)
$own_info = get_user_info ($config['id_user']);

if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM"))
	$own_groups = array_keys(users_get_groups($config['id_user'], "IR"));
else
	$own_groups = array_keys(users_get_groups($config['id_user'], "IR", false));

// Get events in the last 8 hours
$shortcut_events = events_get_group_events($own_groups, 28800, time());
if ($shortcut_events == false)
	$shortcut_events = array();

$critical_events = 0;
foreach($shortcut_events as $event){
	if ($event['criticity'] == 4 and $event['estado'] == 0){
		$critical_events++;
	}
}

echo "<span id='shortcut_critical_events' style='font-size: 9pt; color:#696969; font-weight: bold;' title='" . __('Critical events') . "'>" . $critical_events . "</span>";
echo "</a>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";		
// Calculate opened incidents (id integria incidents are not enabled)
if ($config['integria_enabled'] == 0){
	echo "<a href='index.php?sec=incidencias&sec2=operation/incidents/incident&estado=0'>";
	html_print_image("images/book_edit.png", false, array("title" => __("Incidents opened"),  "style" => "margin-bottom: -5px;"));
	echo "&nbsp;";
	// Select only opened incidents
	$sql = "SELECT count(*) total_incidents FROM tincidencia WHERE 
		id_grupo IN (".implode (",",array_keys ($own_groups)).") AND estado IN (0) 
		ORDER BY actualizacion";
	
	if (!empty($own_groups)) {	
		$result_incidents = db_get_all_rows_sql ($sql);
	}
	else {
		$result_incidents = false;
	}
	
	if ($result_incidents ===  false)
		$shortcut_incidents = 0;
	else 
		$shortcut_incidents = $result_incidents[0]['total_incidents'];
	
	
	echo "<span id='shortcut_incidents_opened' style='font-size: 9pt; color:#696969; font-weight: bold;' title='" . __('Incidents opened') . "'>" . $shortcut_incidents . "</span>";
	echo "</a>";
	echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";		
}
echo "&nbsp;&nbsp;&nbsp;";
echo "<span style='font-size: 9pt; color:#696969; font-weight: bold;'>|</span>";
echo "&nbsp;&nbsp;&nbsp;&nbsp;";
echo "&nbsp;&nbsp;&nbsp;&nbsp;";

echo "<a href='index.php?sec=reporting&sec2=operation/reporting/custom_reporting'>";
html_print_image("images/reporting.png", false, array("title" => __("View reports"), "style" => "margin-bottom: -5px;"));
echo "</a>";

echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

echo "<a href='index.php?sec=messages&sec2=operation/messages/message'>";
html_print_image("images/email.png", false, array("title" => __("Create new message"), "style" => "margin-bottom: -5px;"));
echo "</a>";

// Login in Console and shortcut bar is disabled
// This will show and hide the shortcut value in Javascript code
if (isset($_POST['nick']) and $shortcut_state != 2){
	html_print_input_hidden("login_console", 1);
}
else {
	html_print_input_hidden("login_console", 0);
}

html_print_input_hidden("shortcut_id_user", $config['id_user']);


//Quick access
$shortcut_data = db_get_value('shortcut_data', 'tusuario', 'id_user', $config['id_user']);
if (!empty($shortcut_data)) {
	$serialize = $shortcut_data;
	$unserialize = json_decode($serialize, true);
	
	$items = $unserialize['item_shorcut'];
}
else {
	$items = array();
}
echo "<div id='shortcut_icons_box' style='font-size: 9pt; color:#696969; font-weight: bold; display: inline; float: right; padding-right: 20px;'>" . 
	__("Shortcut: ");
echo "<ul style='display: inline; font-size: 9pt; color:#000;'>";
foreach ($items as $item) {
	echo "<li style='display: inline; padding-right: 10px;'>" . io_safe_output($item) . "</li>";
}
echo "</ul>";
echo "</div>";

echo "</div>";

?>

<script type='text/javascript'>
	$(function() {
		if ($('#hidden-login_console').val() == 1){
			$('#shotcut_bar').css({height: 0}).animate({ height: '20' }, 900);
			$('#shortcut_button').css({height: 22}).animate({ height: '40' }, 900);
			$('#shotcut_bar').css({height: 20}).animate({ height: '0' }, 900);
			$('#shortcut_button').css({height:40}).animate({ height: '22' }, 900);	
		}
		else {
			if ($('#shotcut_bar').css('height') == '0px'){
				$('#shotcut_bar').css('height', '0px');
				$('#shortcut_button').css('height', '22px');
			}
			else{
				$('#shotcut_bar').css('height', '20px');
				$('#shortcut_button').css('height', '40px');			
			}
		}
		
		$('#shortcut_button').click (function () {
			if ($('#shotcut_bar').css('height') == '0px'){
				$('#shotcut_bar').css({height: 0}).animate({ height: '20' }, 900);	
				$('#shortcut_button').css({height: 22}).animate({ height: '40' }, 900);
				jQuery.post ("ajax.php",
					{"page" : "general/shortcut_bar",
					 "update_shortcut_state": 1,
					 "value": 2
					},
					function (data) {
					}
				);		
			}
			else {
				$('#shotcut_bar').css({height: 20}).animate({ height: '0' }, 900);	
				$('#shortcut_button').css({height: 40}).animate({ height: '22' }, 900);		
				jQuery.post ("ajax.php",
					{"page" : "general/shortcut_bar",
					 "update_shortcut_state": 1,
					 "value": 1
					},
					function (data) {
					}
				);
			}
		});
	});
		
	var id_user = $('#hidden-shortcut_id_user').val();	
	function shortcut_check_alerts() {
		jQuery.post ("ajax.php",
			{"page" : "operation/agentes/alerts_status",
			 "get_alert_fired": 1
			},
			function (data) {
				$('#shortcut_alerts_fired').text(data);
			}
		);
	}

	function shortcut_check_events() {
		jQuery.post ("ajax.php",
			{"page" : "general/shortcut_bar",
			 "get_critical_events": 1
			},
			function (data) {
				$('#shortcut_critical_events').text(data);
			}
		);
	}	
	
	function shortcut_check_incidents() {
		jQuery.post ("ajax.php",
			{"page" : "general/shortcut_bar",
			 "get_opened_incidents": 1
			},
			function (data) {
				$('#shortcut_incidents_opened').text(data);
			}
		);
	}		
		
	$(document).ready (function () {
		//TODO: Fix the change the content for the menu as html.
		setInterval("shortcut_check_alerts()", (10 * 1000)); //10 seconds between ajax request
		setInterval("shortcut_check_events()", (10 * 1000)); //10 seconds between ajax request
		setInterval("shortcut_check_incidents()", (10 * 1000)); //10 seconds between ajax request
		
		//For to make a link as item for drag only put "item_drag_shortcut" as class.
		
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
