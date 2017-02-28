<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


// Load global vars
global $config;

require_once ($config['homedir'] . "/include/functions.php");
require_once ($config['homedir'] . "/include/functions_events.php"); //Event processing functions
require_once ($config['homedir'] . "/include/functions_alerts.php"); //Alerts processing functions
require_once ($config['homedir'] . "/include/functions_agents.php"); //Agents funtions
require_once ($config['homedir'] . "/include/functions_users.php"); //Users functions
require_once ($config['homedir'] . "/include/functions_groups.php");
require_once ($config["homedir"] . "/include/functions_graph.php");
require_once ($config["homedir"] . "/include/functions_tags.php");
enterprise_include_once('include/functions_events.php');

check_login ();

$event_a = check_acl ($config['id_user'], 0, "ER");
$event_w = check_acl ($config['id_user'], 0, "EW");
$event_m = check_acl ($config['id_user'], 0, "EM");
$access = ($event_a == true) ? 'ER' : (($event_w == true) ? 'EW' : (($event_m == true) ? 'EM' : 'ER'));

if (!$event_a && !$event_w && !$event_m) {
	db_pandora_audit("ACL Violation",
		"Trying to access event viewer");
	require ("general/noaccess.php");
	return;
}

$is_filter = db_get_value('id_filter', 'tusuario', 'id_user', $config['id_user']);

$jump = '&nbsp;&nbsp;';

if (is_ajax()) {
	$get_filter_values = get_parameter('get_filter_values', 0);
	$save_event_filter = get_parameter('save_event_filter', 0);
	$update_event_filter = get_parameter('update_event_filter', 0);
	$get_event_filters = get_parameter('get_event_filters', 0);
	
	// Get db values of a single filter
	if ($get_filter_values) {
		$id_filter = get_parameter('id');
		
		$event_filter = events_get_event_filter($id_filter);
		
		$event_filter['id_name'] = io_safe_output($event_filter['id_name']);
		$event_filter['tag_with'] = base64_encode(io_safe_output($event_filter['tag_with']));
		$event_filter['tag_without'] = base64_encode(io_safe_output($event_filter['tag_without']));
		
		echo io_json_mb_encode($event_filter);
	}
	
	// Saves an event filter
	if ($save_event_filter) {
		$values = array();
		$values['id_name'] = get_parameter('id_name');
		$values['id_group'] = get_parameter('id_group'); 
		$values['event_type'] = get_parameter('event_type');
		$values['severity'] = get_parameter('severity');
		$values['status'] = get_parameter('status');
		$values['search'] = get_parameter('search');
		$values['text_agent'] = get_parameter('text_agent');
		$values['id_agent'] = get_parameter('id_agent');
		$values['id_agent_module'] = get_parameter('id_agent_module');
		$values['pagination'] = get_parameter('pagination');
		$values['event_view_hr'] = get_parameter('event_view_hr');
		$values['id_user_ack'] = get_parameter('id_user_ack');
		$values['group_rep'] = get_parameter('group_rep');
		$values['tag_with'] = get_parameter('tag_with', io_json_mb_encode(array()));
		$values['tag_without'] = get_parameter('tag_without', io_json_mb_encode(array()));
		$values['filter_only_alert'] = get_parameter('filter_only_alert');
		$values['id_group_filter'] = get_parameter('id_group_filter');
		$values['date_from'] = get_parameter('date_from');
		$values['date_to'] = get_parameter('date_to');

		$exists = (bool)db_get_value_filter(
			'id_filter', 'tevent_filter', $values);
		
		if ($exists) {
			echo 'duplicate';
		}
		else {
			$result = db_process_sql_insert('tevent_filter', $values);
			
			if ($result === false) {
				echo 'error';
			}
			else {
				echo $result;
			}
		}
	}
	
	if ($update_event_filter) {
		$values = array();
		$id = get_parameter('id');
		$values['id_group'] = get_parameter('id_group'); 
		$values['event_type'] = get_parameter('event_type');
		$values['severity'] = get_parameter('severity');
		$values['status'] = get_parameter('status');
		$values['search'] = get_parameter('search');
		$values['text_agent'] = get_parameter('text_agent');
		$values['id_agent'] = get_parameter('id_agent');
		$values['id_agent_module'] = get_parameter('id_agent_module');
		$values['pagination'] = get_parameter('pagination');
		$values['event_view_hr'] = get_parameter('event_view_hr');	
		$values['id_user_ack'] = get_parameter('id_user_ack');
		$values['group_rep'] = get_parameter('group_rep');
		$values['tag_with'] = get_parameter('tag_with', io_json_mb_encode(array()));
		$values['tag_without'] = get_parameter('tag_without', io_json_mb_encode(array()));
		$values['filter_only_alert'] = get_parameter('filter_only_alert');
		$values['id_group_filter'] = get_parameter('id_group_filter');
		$values['date_from'] = get_parameter('date_from');
		$values['date_to'] = get_parameter('date_to');

		$result = db_process_sql_update('tevent_filter',
			$values, array('id_filter' => $id));
		
		if ($result === false) {
			echo 'error';
		}
		else {
			echo 'ok';
		}
	}
	
	if ($get_event_filters) {
		$event_filter = events_get_event_filter_select();
		
		echo io_json_mb_encode($event_filter);
	}
	
	return;
}

$strict_user = db_get_value('strict_acl', 'tusuario', 'id_user', $config['id_user']);

// Get the tags where the user have permissions in Events reading tasks
$tags = tags_get_user_tags($config['id_user'], $access);


if ($id_agent == 0 && !empty($text_agent)) {
	$id_agent = -1;
}

/////////////////////////////////////////////
// Build the condition of the events query

$sql_post = "";

$id_user = $config['id_user'];

$filter_resume = array();
require('events.build_query.php');

// Now $sql_post have all the where condition
/////////////////////////////////////////////

$id_name = get_parameter('id_name', '');

// Trick to catch if any filter button has been pushed (don't collapse filter)
// or the filter was open before click or autorefresh is in use (collapse filter)
$update_pressed = get_parameter_post('update', '');
$update_pressed = (int) !empty($update_pressed);

if ($update_pressed || $open_filter) {
	$open_filter = true;
}

unset($table);

$filters = events_get_event_filter_select();

// Some translated words to be used from javascript
html_print_div(array('hidden' => true,
	'id' => 'not_filter_loaded_text', 'content' => __('No filter loaded')));
html_print_div(array('hidden' => true,
	'id' => 'filter_loaded_text', 'content' => __('Filter loaded')));
html_print_div(array('hidden' => true,
	'id' => 'save_filter_text', 'content' => __('Save filter')));
html_print_div(array('hidden' => true,
	'id' => 'load_filter_text', 'content' => __('Load filter')));

if (check_acl ($config["id_user"], 0, "EW") || check_acl ($config["id_user"], 0, "EM") ) {
	// Save filter div for dialog
	echo '<div id="save_filter_layer" style="display: none">';
	$table->id = 'save_filter_form';
	$table->width = '100%';
	$table->cellspacing = 4;
	$table->cellpadding = 4;
	$table->class = 'databox';
	if (is_metaconsole()) {
		$table->class = 'databox filters';
		$table->cellspacing = 0;
		$table->cellpadding = 0;
	}
	$table->styleTable = 'font-weight: bold; text-align:left;';
	if (!is_metaconsole())
		$table->style[0] = 'width: 50%; width:50%;';
	
	$data = array();
	$table->rowid[0] = 'update_save_selector';
	$data[0] = html_print_radio_button('filter_mode', 'new', '', true, true) . __('New filter') . '';
	$data[1] = html_print_radio_button('filter_mode', 'update', '', false, true) . __('Update filter') . '';
	$table->data[] = $data;
	$table->rowclass[] = '';
	
	$data = array();
	$table->rowid[1] = 'save_filter_row1';
	$data[0] = __('Filter name') . $jump;
	$data[0] .= html_print_input_text ('id_name', '', '', 15, 255, true);
	if(is_metaconsole())
		$data[1] = __('Save in Group') . $jump;
	else
		$data[1] = __('Filter group') . $jump;
	# Fix : Only admin users can see group ALL
	$data[1] .= html_print_select_groups($config['id_user'], $access, users_can_manage_group_all(), "id_group_filter",
				$id_group_filter, '', '', 0, true, false, false, 'w130', false, '', false, false, 'id_grupo', $strict_user);
	$table->data[] = $data;
	$table->rowclass[] = '';
	
	$data = array();
	$table->rowid[2] = 'save_filter_row2';
	
	$table->data[] = $data;
	$table->rowclass[] = '';
	
	$data = array();
	$table->rowid[3] = 'update_filter_row1';
	$data[0] = __("Overwrite filter") . $jump;
	# Fix  : Only admin user can see filters of group ALL for update
	$_filters_update = events_get_event_filter_select(false);
	
	$data[0] .= html_print_select ($_filters_update, "overwrite_filter", '', '', '', 0, true);
	$data[1] = html_print_submit_button (__('Update filter'), 'update_filter', false, 'class="sub upd"', true);

	$table->data[] = $data;
	$table->rowclass[] = '';
	
	html_print_table($table);
	unset($table);
	echo '<div>';	
		echo html_print_submit_button (__('Save filter'), 'save_filter', false, 'class="sub upd" style="float:right;"', true);
	echo '</div>';
	echo '</div>';
}

// Load filter div for dialog
echo '<div id="load_filter_layer" style="display: none">';
$table->id = 'load_filter_form';
$table->width = '100%';
$table->cellspacing = 4;
$table->cellpadding = 4;
$table->class = 'databox';
if (is_metaconsole()) {
	$table->cellspacing = 0;
	$table->cellpadding = 0;
	$table->class = 'databox filters';
}

$table->styleTable = 'font-weight: bold; color: #555; text-align:left;';
if (!is_metaconsole())
	$table->style[0] = 'width: 50%; width:50%;';
$data = array();
$table->rowid[3] = 'update_filter_row1';
$data[0] = __("Load filter") . $jump;
$data[0] .= html_print_select ($filters, "filter_id", '', '', __('None'), 0, true);
$data[1] = html_print_submit_button (__('Load filter'), 'load_filter', false, 'class="sub upd"', true);
$table->data[] = $data;
$table->rowclass[] = '';

html_print_table($table);
unset($table);
echo '</div>';

// TAGS
$tags_select_with = array();
$tags_select_without = array();
$tag_with_temp = array();
$tag_without_temp = array();
foreach ($tags as $id_tag => $tag) {
	if (array_search($id_tag, $tag_with) === false) {
		$tags_select_with[$id_tag] = ui_print_truncate_text ($tag, 50, true);
	}
	else {
		$tag_with_temp[$id_tag] = ui_print_truncate_text ($tag, 50, true);
	}
	
	if (array_search($id_tag, $tag_without) === false) {
		$tags_select_without[$id_tag] = ui_print_truncate_text ($tag, 50, true);
	}
	else {
		$tag_without_temp[$id_tag] = ui_print_truncate_text ($tag, 50, true);
	}
}

$add_with_tag_disabled = empty($tags_select_with);
$remove_with_tag_disabled = empty($tag_with_temp);
$add_without_tag_disabled = empty($tags_select_without);
$remove_without_tag_disabled = empty($tag_without_temp);

$tabletags_with = html_get_predefined_table('transparent', 2);
$tabletags_with->id = 'filter_events_tags_with';
$tabletags_with->width = '100%';
$tabletags_with->cellspacing = 4;
$tabletags_with->cellpadding = 4;
$tabletags_with->class = 'noshadow';
$tabletags_with->styleTable = 'border: 0px;';
if (defined('METACONSOLE')) {
	$tabletags_with->class = 'nobady';
	$tabletags_with->cellspacing = 0;
	$tabletags_with->cellpadding = 0;
}


$data = array();

$data[0] = html_print_select ($tags_select_with, 'select_with', '', '', '', 0,
	true, true, true, '', false, 'width: 200px;');

$data[1] = html_print_image('images/darrowright.png', true, array('id' => 'button-add_with', 'style' => 'cursor: pointer;', 'title' => __('Add')));
$data[1] .= html_print_input_hidden('tag_with', $tag_with_base64, true);
$data[1] .= '<br><br>' . html_print_image('images/darrowleft.png', true, array('id' => 'button-remove_with', 'style' => 'cursor: pointer;', 'title' => __('Remove')));

$data[2] = html_print_select ($tag_with_temp, 'tag_with_temp', array(), '', '',
	0, true, true, true, '', false, "width: 200px;");

$tabletags_with->data[] = $data;
$tabletags_with->rowclass[] = '';


$tabletags_without = html_get_predefined_table('transparent', 2);
$tabletags_without->id = 'filter_events_tags_without';
$tabletags_without->width = '100%';
$tabletags_without->cellspacing = 4;
$tabletags_without->cellpadding = 4;
$tabletags_without->class = 'noshadow';
if (defined('METACONSOLE')) {
	$tabletags_without->class = 'nobady';
	$tabletags_without->cellspacing = 0;
	$tabletags_without->cellpadding = 0;
}
$tabletags_without->styleTable = 'border: 0px;';

$data = array();
$data[0] = html_print_select ($tags_select_without, 'select_without', '', '', '', 0,
	true, true, true, '', false, 'width: 200px;');
$data[1] = html_print_image('images/darrowright.png', true, array('id' => 'button-add_without', 'style' => 'cursor: pointer;', 'title' => __('Add')));
$data[1] .= html_print_input_hidden('tag_without', $tag_without_base64, true);
$data[1] .= '<br><br>' . html_print_image('images/darrowleft.png', true, array('id' => 'button-remove_without', 'style' => 'cursor: pointer;', 'title' => __('Remove')));
$data[2] = html_print_select ($tag_without_temp, 'tag_without_temp', array(), '', '',
	0, true, true, true, '', false, "width: 200px;");
$tabletags_without->data[] = $data;
$tabletags_without->rowclass[] = '';


// END OF TAGS

// EVENTS FILTER
// Table for filter controls
if (is_metaconsole()) {
	$events_filter = '<form id="form_filter" class="filters_form" method="post" action="index.php?sec=eventos&amp;sec2=operation/events/events&amp;refr='. 
		(int)get_parameter("refr", 0) .'&amp;pure='.$config["pure"].'&amp;section=' . $section . '&amp;history='.(int)$history.'">';
}
else {
	$events_filter = '<form id="form_filter" method="post" action="index.php?sec=eventos&amp;sec2=operation/events/events&amp;refr='. 
		(int)get_parameter("refr", 0) .'&amp;pure='.$config["pure"].'&amp;section=' . $section . '&amp;history='.(int)$history.'">';
}
// Hidden field with the loaded filter name
$events_filter .= html_print_input_hidden('id_name', $id_name, true);

// Hidden open filter flag
// If autoupdate is in use collapse filter
if ($open_filter) {
	$events_filter .= html_print_input_hidden('open_filter', 'true', true);
} 
else {
	$events_filter .= html_print_input_hidden('open_filter', 'false', true);
}


//----------------------------------------------------------------------
//- INI ADVANCE FILTER -------------------------------------------------
$table_advanced = new stdClass();
$table_advanced->id = 'events_filter_form_advanced';
$table_advanced->width = '100%';
$table_advanced->cellspacing = 4;
$table_advanced->cellpadding = 4;
$table_advanced->class = 'transparent';
$table_advanced->styleTable = 'font-weight: bold; color: #555;';
$table_advanced->data = array();

$data = array();
$data[0] = __('Free search') . $jump;
$data[0] .= html_print_input_text ('search', $search, '', 25, 255, true);
$data[1] = __('Agent search') . $jump;
$params = array();
$params['show_helptip'] = true;
$params['input_name'] = 'text_agent';
$params['value'] = $text_agent;
$params['return'] = true;

if ($meta) {
	$params['javascript_page'] = 'enterprise/meta/include/ajax/events.ajax';
}
else {
	$params['print_hidden_input_idagent'] = true;
	$params['hidden_input_idagent_name'] = 'id_agent';
	$params['hidden_input_idagent_value'] = $id_agent;
}

$data[1] .= ui_print_agent_autocomplete_input($params);
$table_advanced->data[] = $data;
$table_advanced->rowclass[] = '';


$data = array();
$data[0] = __('User ack.') . $jump;

if ($strict_user) {
	$user_users = array($config['id_user']=>$config['id_user']);
}
else {
	$user_users = users_get_user_users($config['id_user'], $access, users_can_manage_group_all());
}

$data[0] .= html_print_select($user_users, "id_user_ack", $id_user_ack, '',
	__('Any'), 0, true);
if (!$meta) {
	$data[1] = __('Module search') . $jump;
	$data[1] .= html_print_autocomplete_modules('module_search',
		$text_module, false, true, '', array(), true,$id_agent_module);
}
else {
	$data[1] = __('Server') . $jump;
	if ($strict_user)
		$data[1] .= html_print_select('','server_id',
						$server_id, 'script', __('All'), '0', true);
	else
		$data[1] .= html_print_select_from_sql(
						'SELECT id, server_name FROM tmetaconsole_setup',
						'server_id', $server_id, 'script', __('All'), '0', true);
}

$table_advanced->data[] = $data;
$table_advanced->rowclass[] = '';

$data = array();
$data[0] = __("Alert events") . $jump;
$alert_events_titles = array(
	'-1' => __('All'),
	'0' => __('Filter alert events'),
	'1' => __('Only alert events')
);
$data[0] .= html_print_select ($alert_events_titles, "filter_only_alert", $filter_only_alert, '', '', '', true);
$data[1] = __('Block size for pagination') . $jump;
$lpagination[25] = 25;
$lpagination[50] = 50;
$lpagination[100] = 100;
$lpagination[200] = 200;
$lpagination[500] = 500;
$data[1] .= html_print_select ($lpagination, "pagination", $pagination, '', __('Default'), $config["block_size"], true);
$table_advanced->data[] = $data;
$table_advanced->rowclass[] = '';

$data = array();
$data[0] = __('Date from') . $jump;

//~ $user_users = users_get_user_users($config['id_user'], "ER", users_can_manage_group_all());

$data[0] .= html_print_input_text ('date_from', $date_from, '', 15, 10, true);

$data[1] = __('Date to') . $jump;
$data[1] .= html_print_input_text ('date_to', $date_to, '', 15, 10, true);

$table_advanced->data[] = $data;
$table_advanced->rowclass[] = '';

$data[0] = __('Timestamp from:') . $jump;
$data[0] .= html_print_input_text('time_from', $time_from, '', 9, 7, true);

$data[1] = __('Timestamp to:') . $jump;
$data[1] .= html_print_input_text('time_to', $time_to, '', 9, 7, true);

$table_advanced->data[] = $data;
$table_advanced->rowclass[] = '';

$data = array();
if (defined('METACONSOLE'))
{
	$data[0] = '<fieldset class="" style="padding:0px; width: 510px;">' .
			'<legend style="padding:0px;">' .
				__('Events with following tags') .
			'</legend>' .
			html_print_table($tabletags_with, true) .
		'</fieldset>';
	$data2[1] = '<fieldset class="" style="padding:0px; width: 310px;">' .
			'<legend style="padding:0px;">' .
				__('Events without following tags') .
			'</legend>' .
			html_print_table($tabletags_without, true) .
		'</fieldset>';
}
else {
	$data[0] = '<fieldset class="databox" style="padding:0px; width: 30%; ">' .
			'<legend>' .
				__('Events with following tags') .
			'</legend>' .
			html_print_table($tabletags_with, true) .
		'</fieldset>';
	$data[1] = '<fieldset class="databox" style="padding:0px; width: 30%;">' .
			'<legend>' .
				__('Events without following tags') .
			'</legend>' .
			html_print_table($tabletags_without, true) .
		'</fieldset>';
}
$table_advanced->data[] = $data;
if (defined('METACONSOLE'))
	$table_advanced->data[] = $data2;

$table_advanced->rowclass[] = '';
//- END ADVANCE FILTER -------------------------------------------------


$table = new stdClass();
$table->id = 'events_filter_form';
$table->width = '100%';
$table->cellspacing = 4;
$table->cellpadding = 4;
$table->class = 'databox filters';

$table->styleTable = 'font-weight: bold; color: #555;';
$table->data = array();

$data = array();
$data[0] = __('Group') . $jump;

$data[0] .= html_print_select_groups($config["id_user"], $access, true, 
	'id_group', $id_group, '', '', 0, true, false, false, 'w130', false, false, false, false, 'id_grupo', $strict_user). $jump;
//**********************************************************************
// TODO
// This code is disabled for to enabled in Pandora 5.1
// but it needs a field in tevent_filter.
//
//$data[0] .= __('Group recursion') . ' ';
//$data[0] .= html_print_checkbox ("recursion", 1, $recursion, true, false);
//**********************************************************************

$data[1] = __('Event type') . $jump;
$types = get_event_types ();
// Expand standard array to add not_normal (not exist in the array, used only for searches)
$types["not_normal"] = __("Not normal");
$data[1] .= html_print_select ($types, 'event_type', $event_type, '', __('All'), '', true);

$data[2] = __('Severity') . $jump;
$severities = get_priorities ();
$data[2] .= html_print_select ($severities, "severity", $severity, '', __('All'), '-1', true, false, false);
$table->data[] = $data;
$table->rowclass[] = '';

$data = array();
$data[0] = __('Event status') . $jump;
$fields = events_get_all_status();
$data[0] .= html_print_select ($fields, 'status', $status, '', '', '', true);
$data[1] = __('Max. hours old') . $jump;
$data[1] .= html_print_input_text ('event_view_hr', $event_view_hr, '', 5, 255, true);
$data[2] = __("Repeated") . $jump;
$repeated_sel[0] = __("All events");
$repeated_sel[1] = __("Group events");
$repeated_sel[2] = __("Group agents");
$data[2] .= html_print_select ($repeated_sel, "group_rep", $group_rep, '', '', 0, true);
$table->data[] = $data;
$table->rowclass[] = '';

$data = array();
$data[0] = ui_toggle(html_print_table($table_advanced, true),
	__('Advanced options'), '', true, true);
$table->colspan[count($table->data)][0] = 3;
$table->cellstyle[count($table->data)][0] = 'padding: 10px;';
$table->data[] = $data;
$table->rowclass[] = '';


$data = array();
$table->data[] = $data;
$table->rowclass[] = '';

//The buttons

$data = array();
$data[0] = '<div style="width:100%; text-align:left">';
if ($event_w || $event_m) {
	$data[0] .= '<a href="javascript:" onclick="show_save_filter_dialog();">' . 
				html_print_image("images/disk.png", true, array("border" => '0', "title" => __('Save filter'), "alt" => __('Save filter'))) . '</a> &nbsp;';
}

$data[0] .= '<a href="javascript:" onclick="show_load_filter_dialog();">' . 
				html_print_image("images/load.png", true, array("border" => '0', "title" => __('Load filter'), "alt" => __('Load filter'))) . '</a> &nbsp;';
$data[0] .= '<a id="events_graph_link" href="javascript: show_events_graph_dialog()">' . 
				html_print_image('images/chart_curve.png', true, array('title' => __('Show events graph'))) . '</a> <br />';


if (empty($id_name)) {
	$data[0] .= '<div id="filter_loaded_span" style="font-weight: normal">[' .
		__('No filter loaded') .
		']</div>';
}
else {
	$data[0] .= '<div id="filter_loaded_span" style="font-weight: normal">[' .
		__('Filter loaded') . ': ' . $id_name .
		']</div>';
}

$data[0] .= '</div>';


$table->colspan[count($table->data)][1] = 4;
$table->rowstyle[count($table->data)] = 'text-align:right;';
$table->data[] = $data;
$table->rowclass[] = '';

$events_filter .= html_print_table($table, true);

unset($table);

$botom_update = "<div style='width:100%;float:right;'>";
$botom_update .= html_print_submit_button (__('Update'), 'update', false, 'class="sub upd"  style="float:right;"', true);
$botom_update .= "</div>";

$events_filter .= $botom_update;

$events_filter .= "</form>"; //This is the filter div

if (is_metaconsole())
	ui_toggle($events_filter, __("Show Options"));
else
	ui_toggle($events_filter, __('Event control filter'));

// Error div for ajax messages
echo "<div id='show_filter_error' style='display: none;'>";
ui_print_error_message(__('Error creating filter.'), 'data-type_info_box="error_create_filter"');
ui_print_error_message(__('Error creating filter is duplicated.'), 'data-type_info_box="duplicate_create_filter"');
ui_print_success_message(__('Filter created.'), 'data-type_info_box="success_create_filter"');

ui_print_success_message(__('Filter updated.'), 'data-type_info_box="success_update_filter"');
ui_print_error_message(__('Error updating filter.'), 'data-type_info_box="error_create_filter"');

echo "</div>";
?>
<script type="text/javascript">
	$(document).ready(
		function() {
			$(".info_box").hide();
			$("#show_filter_error").show();
		}
	);
</script>
<?php

$event_table = events_get_events_table($meta, $history);

if ($group_rep == 0) {
	switch ($config["dbtype"]) {
		case "mysql":
			$sql = "SELECT *, 1 event_rep
				FROM $event_table
				WHERE 1=1 " . $sql_post . "
				ORDER BY utimestamp DESC LIMIT ".$offset.",".$pagination;
			break;
		case "postgresql":
			$sql = "SELECT *, 1 event_rep
				FROM $event_table
				WHERE 1=1 " . $sql_post . "
				ORDER BY utimestamp DESC LIMIT ".$pagination." OFFSET ".$offset;
			break;
		case "oracle":
			$set = array();
			$set['limit'] = $pagination;
			$set['offset'] = $offset;
			$sql = "SELECT $event_table.*, 1 event_rep
				FROM $event_table
				WHERE 1=1 " . $sql_post . "
				ORDER BY utimestamp DESC"; 
			$sql = oracle_recode_query ($sql, $set);
			break;
	}
	
	//Extract the events by filter (or not) from db
	$result = db_get_all_rows_sql ($sql);
}
elseif ($group_rep == 1) {
	$filter_resume['duplicate'] = $group_rep;
	$result = events_get_events_grouped(
		$sql_post,
		$offset,
		$pagination,
		$meta,
		$history,
		false,
		false,
		'DESC');
}
elseif ($group_rep == 2) {
	$filter_resume['duplicate'] = $group_rep;
	$result = events_get_events_grouped_by_agent(
		$sql_post,
		$offset,
		$pagination,
		$meta,
		$history);	
}

// Active filter tag view call (only enterprise version)
// It is required to pass some references to enterprise function 
// to translate the active filters
enterprise_hook('print_event_tags_active_filters',
	array( $filter_resume, array(
			'status' => $fields,
			'event_type' => $types,
			'severity' => $severities,
			'duplicate' => $repeated_sel,
			'alerts' => $alert_events_titles)
	)
);

if (!empty($result)) {
	if ($group_rep == 0) {
		$sql = "SELECT COUNT(id_evento)
			FROM $event_table
			WHERE 1=1 " . $sql_post;
	}
	elseif ($group_rep == 1) {
		switch ($config["dbtype"]) {
			case "mysql":
			case "postgresql":
				$sql = "SELECT COUNT(1)
						FROM (SELECT 1
							FROM $event_table
							WHERE 1=1 " . $sql_post . "
							GROUP BY evento, id_agentmodule) t";
				break;
			case "oracle":
				$sql = "SELECT COUNT(1)
						FROM (SELECT 1
							FROM $event_table
							WHERE 1=1 " . $sql_post . "
							GROUP BY to_char(evento), id_agentmodule) t";
				break;
		}
	}
	elseif ($group_rep == 2) {
		
	}
	$limit = (int) db_get_sql ($sql);
	
	if ($group_rep == 0) {
		switch ($config["dbtype"]) {
			case "mysql":
				$sql = "SELECT *, 1 event_rep
					FROM $event_table
					WHERE 1=1 " . $sql_post . "
					ORDER BY utimestamp DESC LIMIT 0,".$limit;
				break;
			case "postgresql":
				$sql = "SELECT *, 1 event_rep
					FROM $event_table
					WHERE 1=1 " . $sql_post . "
					ORDER BY utimestamp DESC LIMIT ".$limit." OFFSET 0";
				break;
			case "oracle":
				$set = array();
				$set['limit'] = $pagination;
				$set['offset'] = $offset;
				$sql = "SELECT $event_table.*, 1 event_rep
					FROM $event_table
					WHERE 1=1 " . $sql_post . "
					ORDER BY utimestamp DESC"; 
				$sql = oracle_recode_query ($sql, $set);
				break;
		}
		
		//Extract the events by filter (or not) from db
		$results_graph = db_get_all_rows_sql ($sql);
	}
	elseif ($group_rep == 1)  {
		$results_graph = events_get_events_grouped($sql_post,
											0,
											$limit,
											$meta,
											$history);
	}
	elseif ($group_rep == 2) {
		
	}
	
	if (($group_rep == 1) OR ($group_rep == 0)) {
		$graph = '<div style="width: 350px; margin: 0 auto;">' .
			grafico_eventos_agente(350, 185,
				$results_graph, $meta, $history, $tags_acls_condition,$limit) .
			'</div>';
		html_print_div(array('id' => 'events_graph',
			'hidden' => true, 'content' => $graph));
	}
}


if (!empty($result)) {
	//~ Checking the event tags exactly. The event query filters approximated tags to keep events
	//~ with several tags
	$acltags = tags_get_user_module_and_tags ($config['id_user'], $access, true);

	foreach ($result as $key=>$event_data) {
		$has_tags = events_checks_event_tags($event_data, $acltags);
		if (!$has_tags) {
			unset($result[$key]);
		}
	}
}



// Delete rnum field generated by oracle_recode_query() function
if (($config['dbtype'] == 'oracle') && ($result !== false)) {
	for ($i=0; $i < count($result); $i++) {
		unset($result[$i]['rnum']);
	}
}

if ($group_rep == 0) {
	$sql = "SELECT COUNT(id_evento)
			FROM $event_table
			WHERE 1=1 $sql_post";
	$total_events = (int) db_get_sql ($sql);
}
elseif ($group_rep == 1) {
	$total_events = events_get_events_grouped($sql_post, false,
		false, $meta, $history, true, false);
}
elseif ($group_rep == 2) {
	$sql = "SELECT COUNT(*) FROM (select id_agente as total from $event_table WHERE id_agente > 0  
					$sql_post GROUP BY id_agente ORDER BY id_agente ) AS t";
	$total_events = (int) db_get_sql ($sql);
}

if (empty ($result)) {
	$result = array ();
}

$allow_action = true;
$allow_pagination = true;
$id_group_filter = $id_group;
require('events.build_table.php');

enterprise_hook('close_meta_frame');

unset($table);

// Values to be used from javascript library
html_print_input_hidden('ajax_file',
	ui_get_full_url("ajax.php", false, false, false));
html_print_input_hidden('meta', (int)$meta);
html_print_input_hidden('history', (int)$history);
html_print_input_hidden('filterid', $is_filter);

ui_require_jquery_file('json');
ui_include_time_picker();
?>
<script language="javascript" type="text/javascript">
/*<![CDATA[ */

var select_with_tag_empty = <?php echo (int)$remove_with_tag_disabled;?>;
var select_without_tag_empty = <?php echo (int)$remove_without_tag_disabled;?>;
var origin_select_with_tag_empty = <?php echo (int)$add_with_tag_disabled;?>;
var origin_select_without_tag_empty = <?php echo (int)$add_without_tag_disabled;?>;

var val_none = 0;
var text_none = "<?php echo __('None'); ?>";
var group_agents_id = false;

$(document).ready( function() {
	
	$("#text-date_from, #text-date_to").datepicker({dateFormat: "<?php echo DATE_FORMAT_JS; ?>"});
	
	// If the events are not charged, dont show graphs link
	if ($('#events_graph').val() == undefined) {
		$('#events_graph_link').hide();
	}
	
	// Don't collapse filter if update button has been pushed
	if ($("#hidden-open_filter").val() == 'true') {
		$("#event_control").toggle();
	}
	
	// If selected is not 'none' show filter name
	if ( $("#filter_id").val() != 0 ) {
		$("#row_name").css('visibility', '');
		$("#submit-update_filter").css('visibility', '');
	}

	if ($("#hidden-id_name").val() == ''){
		if($("#hidden-filterid").val() != ''){
			$('#row_name').css('visibility', '');
			$("#submit-update_filter").css('visibility', '');
			jQuery.post ("<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
				{"page" : "operation/events/events_list",
				"get_filter_values" : 1,
				"id" : $('#hidden-filterid').val()
				},
				function (data) {
					jQuery.each (data, function (i, val) {
						if (i == 'id_name')
							$("#hidden-id_name").val(val);
						if (i == 'id_group')
							$("#id_group").val(val);
						if (i == 'event_type')
							$("#event_type").val(val);
						if (i == 'severity')
							$("#severity").val(val);
						if (i == 'status')
							$("#status").val(val);
						if (i == 'search')
							$("#text-search").val(val);
						if (i == 'text_agent')
							$("#text_id_agent").val(val);
						if (i == 'id_agent')
							$('input:hidden[name=id_agent]').val(val);
						if (i == 'id_agent_module')
							$('input:hidden[name=module_search_hidden]').val(val);
						if (i == 'pagination')
							$("#pagination").val(val);
						if (i == 'event_view_hr')
							$("#text-event_view_hr").val(val);
						if (i == 'id_user_ack')
							$("#id_user_ack").val(val);
						if (i == 'group_rep')
							$("#group_rep").val(val);
						if (i == 'tag_with')
							$("#hidden-tag_with").val(val);
						if (i == 'tag_without')
							$("#hidden-tag_without").val(val);
						if (i == 'filter_only_alert')
							$("#filter_only_alert").val(val);
						if (i == 'id_group_filter')
							$("#id_group_filter").val(val);
					});
					reorder_tags_inputs();
					// Update the info with the loaded filter
					$('#filter_loaded_span').html($('#filter_loaded_text').html() + ': ' + $("#hidden-id_name").val());

					// Update the view with the loaded filter
					$('#submit-update').trigger('click');
				},
				"json"
			);
		}
	}

	$("#submit-load_filter").click(function () {
		// If selected 'none' flush filter
		if ( $("#filter_id").val() == 0 ) {
			$("#hidden-id_name").val('');
			$("#id_group").val(0);
			$("#event_type").val('');
			$("#severity").val(-1);
			$("#status").val(3);
			$("#text-search").val('');
			$('input:hidden[name=id_agent]').val();
			$('input:hidden[name=module_search_hidden]').val();
			$("#pagination").val(25);
			$("#text-event_view_hr").val(8);
			$("#id_user_ack").val(0);
			$("#group_rep").val(1);
			$("#tag").val('');
			$("#filter_only_alert").val(-1);
			$("#row_name").css('visibility', 'hidden');
			$("#submit-update_filter").css('visibility', 'hidden');
			$("#id_group").val(0);
			
			clear_tags_inputs();
			
			// Update the view of filter load with no loaded filters message
			$('#filter_loaded_span').html($('#not_filter_loaded_text').html());
		}
		// If filter selected then load filter
		else {
			$('#row_name').css('visibility', '');
			$("#submit-update_filter").css('visibility', '');
			jQuery.post ("<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
				{"page" : "operation/events/events_list",
				"get_filter_values" : 1,
				"id" : $('#filter_id').val()
				},
				function (data) {
					jQuery.each (data, function (i, val) {
						if (i == 'id_name')
							$("#hidden-id_name").val(val);
						if (i == 'id_group')
							$("#id_group").val(val);
						if (i == 'event_type')
							$("#event_type").val(val);
						if (i == 'severity')
							$("#severity").val(val);
						if (i == 'status')
							$("#status").val(val);
						if (i == 'search')
							$("#text-search").val(val);
						if (i == 'text_agent')
							$("#text_id_agent").val(val);
						if (i == 'id_agent')
							$('input:hidden[name=id_agent]').val(val);
						if (i == 'id_agent_module')
							$('input:hidden[name=module_search_hidden]').val(val);
						if (i == 'pagination')
							$("#pagination").val(val);
						if (i == 'event_view_hr')
							$("#text-event_view_hr").val(val);
						if (i == 'id_user_ack')
							$("#id_user_ack").val(val);
						if (i == 'group_rep')
							$("#group_rep").val(val);
						if (i == 'tag_with')
							$("#hidden-tag_with").val(val);
						if (i == 'tag_without')
							$("#hidden-tag_without").val(val);
						if (i == 'filter_only_alert')
							$("#filter_only_alert").val(val);
						if (i == 'id_group_filter')
							$("#id_group_filter").val(val);
						if (i == 'date_from'){
							if((val == '0000-00-00') || (val == null)) {
								$("#text-date_from").val('');
							} else {
								$("#text-date_from").val(val.replace(/\-/g,"/"));
							}
						}
						if (i == 'date_to'){
							if((val == '0000-00-00') || (val == null)) {
								$("#text-date_to").val('');
							} else {
								$("#text-date_to").val(val.replace(/\-/g,"/"));
							}
						}
					});
					reorder_tags_inputs();
					// Update the info with the loaded filter
					$('#filter_loaded_span').html($('#filter_loaded_text').html() + ': ' + $("#hidden-id_name").val());
					
					// Update the view with the loaded filter
					$('#submit-update').trigger('click');
				},
				"json"
			);
		}
		
		// Close dialog
		$('.ui-dialog-titlebar-close').trigger('click');
	});
	
	// Filter save mode selector
	$("[name='filter_mode']").click(function() {
		if ($(this).val() == 'new') {
			$('#save_filter_row1').show();
			$('#save_filter_row2').show();
			$('#update_filter_row1').hide();
		}
		else {
			$('#save_filter_row1').hide();
			$('#save_filter_row2').hide();
			$('#update_filter_row1').show();
		}
	});
	
	// This saves an event filter
	$("#submit-save_filter").click(function () {
		// If the filter name is blank show error
		if ($('#text-id_name').val() == '') {
			$('#show_filter_error').html("<h3 class='error'><?php echo __("Filter name cannot be left blank"); ?></h3>");
			
			// Close dialog
			$('.ui-dialog-titlebar-close').trigger('click');
			return false;
		}
		
		var id_filter_save;
		
		jQuery.post ("<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
			{
				"page" : "operation/events/events_list",
				"save_event_filter" : 1,
				"id_name" : $("#text-id_name").val(),
				"id_group" : $("select#id_group").val(),
				"event_type" : $("#event_type").val(),
				"severity" : $("#severity").val(),
				"status" : $("#status").val(),
				"search" : $("#text-search").val(),
				"text_agent" : $("#text_id_agent").val(),
				"id_agent" : $('input:hidden[name=id_agent]').val(),
				"id_agent_module" : $('input:hidden[name=module_search_hidden]').val(),
				"pagination" : $("#pagination").val(),
				"event_view_hr" : $("#text-event_view_hr").val(),
				"id_user_ack" : $("#id_user_ack").val(),
				"group_rep" : $("#group_rep").val(),
				"tag_with": Base64.decode($("#hidden-tag_with").val()),
				"tag_without": Base64.decode($("#hidden-tag_without").val()),
				"filter_only_alert" : $("#filter_only_alert").val(),
				"id_group_filter": $("#id_group_filter").val(),
				"date_from": $("#text-date_from").val(),
				"date_to": $("#text-date_to").val()
			},
			function (data) {
				$(".info_box").hide();
				if (data == 'error') {
					$(".info_box").filter(function(i, item) {
						if ($(item).data('type_info_box') == "error_create_filter") {
							return true;
						}
						else
							return false;
					}).show();
				}
				else  if (data == 'duplicate') {
					$(".info_box").filter(function(i, item) {
						if ($(item).data('type_info_box') == "duplicate_create_filter") {
							return true;
						}
						else
							return false;
					}).show();
				}
				else {
					id_filter_save = data;
					
					$(".info_box").filter(function(i, item) {
						if ($(item).data('type_info_box') == "success_create_filter") {
							return true;
						}
						else
							return false;
					}).show();
				}
			});
		
		// First remove all options of filters select
		$('#filter_id').find('option').remove().end();
		// Add 'none' option the first
		$('#filter_id').append ($('<option></option>').html ( <?php echo "'" . __('none') . "'" ?> ).attr ("value", 0));	
		// Reload filters select
		jQuery.post ("<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
			{
				"page" : "operation/events/events_list",
				"get_event_filters" : 1
			},
			function (data) {
				jQuery.each (data, function (i, val) {
					s = js_html_entity_decode(val);
					
					if (i == id_filter_save) {
						$('#filter_id').append ($('<option selected="selected"></option>').html (s).attr ("value", i));
					}
					else {
						$('#filter_id').append ($('<option></option>').html (s).attr ("value", i));	  
					}
				});
			},
			"json"
			);
		$("#submit-update_filter").css('visibility', '');
		
		// Close dialog
		$('.ui-dialog-titlebar-close').trigger('click');
		
		// Update the info with the loaded filter
		$("#hidden-id_name").val($('#text-id_name').val());
		$('#filter_loaded_span').html($('#filter_loaded_text').html() + ': ' + $('#text-id_name').val());
		
		return false;
	});
	
	// This updates an event filter
	$("#submit-update_filter").click(function () {
		var id_filter_update =  $("#overwrite_filter").val();
		var name_filter_update = $("#overwrite_filter option[value='"+id_filter_update+"']").text();
		
		jQuery.post ("<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
			{"page" : "operation/events/events_list",
			"update_event_filter" : 1,
			"id" : $("#overwrite_filter").val(),
			"id_group" : $("select#id_group").val(),
			"event_type" : $("#event_type").val(),
			"severity" : $("#severity").val(),
			"status" : $("#status").val(),
			"search" : $("#text-search").val(),
			"text_agent" : $("#text_id_agent").val(),
			"id_agent" : $('input:hidden[name=id_agent]').val(),
			"id_agent_module" : $('input:hidden[name=module_search_hidden]').val(),
			"pagination" : $("#pagination").val(),
			"event_view_hr" : $("#text-event_view_hr").val(),
			"id_user_ack" : $("#id_user_ack").val(),
			"group_rep" : $("#group_rep").val(),
			"tag_with" : Base64.decode($("#hidden-tag_with").val()),
			"tag_without" : Base64.decode($("#hidden-tag_without").val()),
			"filter_only_alert" : $("#filter_only_alert").val(),
			"id_group_filter": $("#id_group_filter").val(),
			"date_from": $("#text-date_from").val(),
			"date_to": $("#text-date_to").val()
			},
			function (data) {
				$(".info_box").hide();
				if (data == 'ok') {
					$(".info_box").filter(function(i, item) {
						if ($(item).data('type_info_box') == "success_update_filter") {
							return true;
						}
						else
							return false;
					}).show();
				}
				else {
					$(".info_box").filter(function(i, item) {
						if ($(item).data('type_info_box') == "error_create_filter") {
							return true;
						}
						else
							return false;
					}).show();
				}
			});
			
			// First remove all options of filters select
			$('#filter_id').find('option').remove().end();
			// Add 'none' option the first
			$('#filter_id').append ($('<option></option>').html ( <?php echo "'" . __('none') . "'" ?> ).attr ("value", 0));	
			// Reload filters select
			jQuery.post ("<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
				{"page" : "operation/events/events_list",
					"get_event_filters" : 1
				},
				function (data) {
					jQuery.each (data, function (i, val) {
						s = js_html_entity_decode(val);
						if (i == id_filter_update) {
							$('#filter_id').append ($('<option selected="selected"></option>').html (s).attr ("value", i));
						}
						else {
							$('#filter_id').append ($('<option></option>').html (s).attr ("value", i));
						}
					});
				},
				"json"
				);
				
			// Close dialog
			$('.ui-dialog-titlebar-close').trigger('click');
			
			// Update the info with the loaded filter
			$("#hidden-id_name").val($('#text-id_name').val());
			$('#filter_loaded_span').html($('#filter_loaded_text').html() + ': ' + name_filter_update);
			return false;
	});
	
	// Change toggle arrow when it's clicked
	$("#tgl_event_control").click(function() {
		if ($("#toggle_arrow").attr("src").match(/[^\.]+down\.png/) == null) {
			var params = [];
			params.push("get_image_path=1");
			params.push("img_src=images/down.png");
			params.push("page=include/ajax/skins.ajax");
			params.push("only_src=1");
			jQuery.ajax ({
				data: params.join ("&"),
				type: 'POST',
				url: action="<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
				success: function (data) {
					$("#toggle_arrow").attr('src', data);
				}
			});
		}
		else {
			var params = [];
			params.push("get_image_path=1");
			params.push("img_src=images/go.png");
			params.push("page=include/ajax/skins.ajax");
			params.push("only_src=1");
			jQuery.ajax ({
				data: params.join ("&"),
				type: 'POST',
				url: action="<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
				success: function (data) {
					$("#toggle_arrow").attr('src', data);
				}
			});
		}
	});
	
	$("#button-add_with").click(function() {
		click_button_add_tag("with");
		});
	
	$("#button-add_without").click(function() {
		click_button_add_tag("without");
		});
	
	$("#button-remove_with").click(function() {
		click_button_remove_tag("with");
	});
	
	$("#button-remove_without").click(function() {
		click_button_remove_tag("without");
	});
	
});

function click_button_remove_tag(what_button) {
	if (what_button == "with") {
		id_select_origin = "#select_with";
		id_select_destiny = "#tag_with_temp";
		id_button_remove = "#button-remove_with";
		id_button_add = "#button-add_with";
		
		select_origin_empty = origin_select_with_tag_empty;
	}
	else { //without
		id_select_origin = "#select_without";
		id_select_destiny = "#tag_without_temp";
		id_button_remove = "#button-remove_without";
		id_button_add = "#button-add_without";
		
		select_origin_empty = origin_select_without_tag_empty;
	}
	
	if ($(id_select_destiny + " option:selected").length == 0) {
		return; //Do nothing
	}
	
	if (select_origin_empty) {
		$(id_select_origin + " option").remove();
		
		if (what_button == "with") {
			origin_select_with_tag_empty = false;
		}
		else { //without
			origin_select_without_tag_empty = false;
		}
		
		$(id_button_add).removeAttr('disabled');
	}
	
	//Foreach because maybe the user select several items in
	//the select.
	jQuery.each($(id_select_destiny + " option:selected"), function(key, element) {
		val = $(element).val();
		text = $(element).text();
		
		$(id_select_origin).append($("<option value='" + val + "'>" + text + "</option>"));
	});
	
	$(id_select_destiny + " option:selected").remove();
	
	if ($(id_select_destiny + " option").length == 0) {
		$(id_select_destiny).append($("<option value='" + val_none + "'>" + text_none + "</option>"));
		$(id_button_remove).attr('disabled', 'true');
		
		if (what_button == 'with') {
			select_with_tag_empty = true;
		}
		else { //without
			select_without_tag_empty = true;
		}
	}
	
	replace_hidden_tags(what_button);
}

function click_button_add_tag(what_button) {
	if (what_button == 'with') {
		id_select_origin = "#select_with";
		id_select_destiny = "#tag_with_temp";
		id_button_remove = "#button-remove_with";
		id_button_add = "#button-add_with";
	}
	else { //without
		id_select_origin = "#select_without";
		id_select_destiny = "#tag_without_temp";
		id_button_remove = "#button-remove_without";
		id_button_add = "#button-add_without";
	}
	
	$(id_select_origin + " option:selected").each(function() {
		if (what_button == 'with') {
			select_destiny_empty = select_with_tag_empty;
		}
		else { //without
			select_destiny_empty = select_without_tag_empty;
		}
		
		
		without_val = $(this).val();
		if(without_val == null) {
			next;
		}
		without_text = $(this).text();
				
		if (select_destiny_empty) {
			$(id_select_destiny).empty();
			
			if (what_button == 'with') {
				select_with_tag_empty = false;
			}
			else { //without
				select_without_tag_empty = false;
			}
		}
		
		$(id_select_destiny).append($("<option value='" + without_val + "'>" + without_text + "</option>"));
		$(id_select_origin + " option:selected").remove();
		$(id_button_remove).removeAttr('disabled');
		
		if ($(id_select_origin + " option").length == 0) {
			$(id_select_origin).append($("<option value='" + val_none + "'>" + text_none + "</option>"));
			$(id_button_add).attr('disabled', 'true');
			
			if (what_button == 'with') {
				origin_select_with_tag_empty = true;
			}
			else { //without
				origin_select_without_tag_empty = true;
			}
		}
			
		replace_hidden_tags(what_button);
	});
	
}

function replace_hidden_tags(what_button) {
	if (what_button == 'with') {
		id_select_destiny = "#tag_with_temp";
		id_hidden = "#hidden-tag_with";
	}
	else { //without
		id_select_destiny = "#tag_without_temp";
		id_hidden = "#hidden-tag_without";
	}
	
	value_store = [];
	
	jQuery.each($(id_select_destiny + " option"), function(key, element) {
		val = $(element).val();
		
		value_store.push(val);
	});
	
	$(id_hidden).val(Base64.encode(jQuery.toJSON(value_store)));
}

function clear_tags_inputs() {
	$("#hidden-tag_with").val(Base64.encode(jQuery.toJSON([])));
	$("#hidden-tag_without").val(Base64.encode(jQuery.toJSON([])));
	reorder_tags_inputs();
}

function reorder_tags_inputs() {
	$('#select_with option[value="' + val_none + '"]').remove();
	jQuery.each($("#tag_with_temp option"), function(key, element) {
		val = $(element).val();
		text = $(element).text();
		
		if (val == val_none)
			return;
		
		$("#select_with").append($("<option value='" + val + "'>" + text + "</option>"));
	});
	$("#tag_with_temp option").remove();
	
	
	$('#select_without option[value="' + val_none + '"]').remove();
	jQuery.each($("#tag_without_temp option"), function(key, element) {
		val = $(element).val();
		text = $(element).text();
		
		if (val == val_none)
			return;
		
		$("#select_without").append($("<option value='" + val + "'>" + text + "</option>"));
	});
	$("#tag_without_temp option").remove();
	
	
	tags_base64 = $("#hidden-tag_with").val();
	tags = jQuery.parseJSON(Base64.decode(tags_base64));
	jQuery.each(tags, function(key, element) {
		if ($("#select_with option[value='" + element + "']").length == 1) {
			text = $("#select_with option[value='" + element + "']").text();
			val = $("#select_with option[value='" + element + "']").val();
			$("#tag_with_temp").append($("<option value='" + val + "'>" + text + "</option>"));
			$("#select_with option[value='" + element + "']").remove();
		}
	});
	if ($("#select_with option").length == 0) {
		origin_select_with_tag_empty = true;
		$("#button-add_with").attr('disabled', 'true');
		$("#select_with").append($("<option value='" + val_none + "'>" + text_none + "</option>"));
	}
	else {
		origin_select_with_tag_empty = false;
		$("#button-add_with").removeAttr('disabled');
	}
	if ($("#tag_with_temp option").length == 0) {
		select_with_tag_empty = true;
		$("#button-remove_with").attr('disabled', 'true');
		$("#tag_with_temp").append($("<option value='" + val_none + "'>" + text_none + "</option>"));
	}
	else {
		select_with_tag_empty = false;
		$("#button-remove_with").removeAttr('disabled');
	}
	
	tags_base64 = $("#hidden-tag_without").val();
	tags = jQuery.parseJSON(Base64.decode(tags_base64));
	jQuery.each(tags, function(key, element) {
		if ($("#select_without option[value='" + element + "']").length == 1) {
			text = $("#select_without option[value='" + element + "']").text();
			val = $("#select_without option[value='" + element + "']").val();
			$("#tag_without_temp").append($("<option value='" + val + "'>" + text + "</option>"));
			$("#select_without option[value='" + element + "']").remove();
		}
	});
	if ($("#select_without option").length == 0) {
		origin_select_without_tag_empty = true;
		$("#button-add_without").attr('disabled', 'true');
		$("#select_without").append($("<option value='" + val_none + "'>" + text_none + "</option>"));
	}
	else {
		origin_select_without_tag_empty = false;
		$("#button-add_without").removeAttr('disabled');
	}
	if ($("#tag_without_temp option").length == 0) {
		select_without_tag_empty = true;
		$("#button-remove_without").attr('disabled', 'true');
		$("#tag_without_temp").append($("<option value='" + val_none + "'>" + text_none + "</option>"));
	}
	else {
		select_without_tag_empty = false;
		$("#button-remove_without").removeAttr('disabled');
	}
}

// Show the modal window of an module
function show_events_graph_dialog() {
	$("#events_graph").hide ()
			.dialog ({
				resizable: true,
				draggable: true,
				title: '<?php echo __('Events generated -by agent-'); ?>',
				modal: true,
				overlay: {
					opacity: 0.5,
					background: "black"
				},
				width: 450,
				height: 380
			})
			.show ();
}
/* ]]> */

//function datetime 
function datetime_picker_callback() {
		
	$("#text-time_from, #text-time_to").timepicker({
		showSecond: true,
		timeFormat: '<?php echo TIME_FORMAT_JS; ?>',
		timeOnlyTitle: '<?php echo __('Choose time');?>',
		timeText: '<?php echo __('Time');?>',
		hourText: '<?php echo __('Hour');?>',
		minuteText: '<?php echo __('Minute');?>',
		secondText: '<?php echo __('Second');?>',
		currentText: '<?php echo __('Now');?>',
		closeText: '<?php echo __('Close');?>'});
		
	$("#text-date_from, #text-date_to").datepicker({dateFormat: "<?php echo DATE_FORMAT_JS; ?>"});
	
	$.datepicker.setDefaults($.datepicker.regional[ "<?php echo get_user_language(); ?>"]);
}
datetime_picker_callback();
</script>
