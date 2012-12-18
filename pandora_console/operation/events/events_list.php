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

require_once ($config['homedir']. "/include/functions_events.php"); //Event processing functions
require_once ($config['homedir']. "/include/functions_alerts.php"); //Alerts processing functions
require_once ($config['homedir']. "/include/functions.php");
require_once($config['homedir'] . "/include/functions_agents.php"); //Agents funtions
require_once($config['homedir'] . "/include/functions_users.php"); //Users functions
require_once ($config['homedir'] . '/include/functions_groups.php');

require_once ($config["homedir"] . '/include/functions_graph.php');
require_once ($config["homedir"] . '/include/functions_tags.php');

check_login ();

if (! check_acl ($config["id_user"], 0, "IR")) {
	db_pandora_audit("ACL Violation",
		"Trying to access event viewer");
	require ("general/noaccess.php");
	return;
}

$meta = false;
if(enterprise_installed() && defined("METACONSOLE")) {
	$meta = true;
}

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
		$event_filter['tag_with'] = io_safe_output($event_filter['tag_with']);
		$event_filter['tag_without'] = io_safe_output($event_filter['tag_without']);
		
		echo json_encode($event_filter);
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
		$values['pagination'] = get_parameter('pagination');
		$values['event_view_hr'] = get_parameter('event_view_hr');
		$values['id_user_ack'] = get_parameter('id_user_ack');
		$values['group_rep'] = get_parameter('group_rep');
		$values['tag_with'] = get_parameter('tag_with', json_encode(array()));
		$values['tag_without'] = get_parameter('tag_without', json_encode(array()));
		$values['filter_only_alert'] = get_parameter('filter_only_alert');
		$values['id_group_filter'] = get_parameter('id_group_filter');
		
		$result = db_process_sql_insert('tevent_filter', $values);
		
		if ($result === false) {
			echo 'error';
		}
		else {
			echo $result;
		}
	}
	
	if ($update_event_filter) {
		$values = array();
		$id = get_parameter('id');
		$values['id_name'] = get_parameter('id_name');
		$values['id_group'] = get_parameter('id_group'); 
		$values['event_type'] = get_parameter('event_type');
		$values['severity'] = get_parameter('severity');
		$values['status'] = get_parameter('status');
		$values['search'] = get_parameter('search');
		$values['text_agent'] = get_parameter('text_agent');
		$values['pagination'] = get_parameter('pagination');
		$values['event_view_hr'] = get_parameter('event_view_hr');	
		$values['id_user_ack'] = get_parameter('id_user_ack');
		$values['group_rep'] = get_parameter('group_rep');
		$values['tag_with'] = get_parameter('tag_with', json_encode(array()));
		$values['tag_without'] = get_parameter('tag_without', json_encode(array()));
		$values['filter_only_alert'] = get_parameter('filter_only_alert');
		$values['id_group_filter'] = get_parameter('id_group_filter');
		
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
		
		echo json_encode($event_filter);
	}
	
	return;
}

$tags = tags_search_tag(false, false, true);

// Error div for ajax messages
echo "<div id='show_filter_error'>";
echo "</div>";

$tag_with_json = io_safe_output(get_parameter("tag_with"));
$tag_with = json_decode($tag_with_json, true);
if (empty($tag_with)) $tag_with = array();
$tag_without_json = io_safe_output(get_parameter("tag_without"));
$tag_without = json_decode($tag_without_json, true);
if (empty($tag_without)) $tag_without = array();

if ($id_agent == 0) {
	$text_agent = (string) get_parameter("text_agent", __("All"));
	
	if ($text_agent != __('All')) {
		$id_agent = -1;
	}
}

$groups = users_get_groups($config['id_user'], 'IR');

//Group selection
if ($ev_group > 0 && in_array ($ev_group, array_keys ($groups))) {
	//If a group is selected and it's in the groups allowed
	$sql_post = " AND id_grupo = $ev_group";
}
else {
	if (is_user_admin ($config["id_user"])) {
		//Do nothing if you're admin, you get full access
		$sql_post = "";
	}
	else {
		//Otherwise select all groups the user has rights to.
		$sql_post = " AND id_grupo IN (".implode (",", array_keys ($groups)).")";
	}
}

// Skip system messages if user is not PM
if (!check_acl ($config["id_user"], 0, "PM")) {
	$sql_post .= " AND id_grupo != 0";
}

switch ($status) {
	case 0:
	case 1:
	case 2:
		$sql_post .= " AND estado = ".$status;
		break;
	case 3:
		$sql_post .= " AND (estado = 0 OR estado = 2)";
		break;
}

if ($search != "") {
	$sql_post .= " AND evento LIKE '%".io_safe_input($search)."%'";
}

if ($event_type != "") {
	// If normal, warning, could be several (going_up_warning, going_down_warning... too complex 
	// for the user so for him is presented only "warning, critical and normal"
	if ($event_type == "warning" || $event_type == "critical"
		|| $event_type == "normal") {
		$sql_post .= " AND event_type LIKE '%$event_type%' ";
	}
	elseif ($event_type == "not_normal") {
		$sql_post .= " AND event_type LIKE '%warning%' OR event_type LIKE '%critical%' OR event_type LIKE '%unknown%' ";
	}
	else {
		$sql_post .= " AND event_type = '" . $event_type."'";
	}

}
if ($severity != -1)
	$sql_post .= " AND criticity = " . $severity;

switch ($id_agent) {
	case 0:
		break;
	case -1:
		// Agent doesnt exist. No results will returned
		$sql_post .= " AND 1 = 0";
		break;
	default:
		$sql_post .= " AND id_agente = " . $id_agent;
		break;
}

if ($id_event != -1)
	$sql_post .= " AND id_evento = " . $id_event;

if ($id_user_ack != "0")
	$sql_post .= " AND id_usuario = '" . $id_user_ack . "'";


if ($event_view_hr > 0) {
	$unixtime = get_system_time () - ($event_view_hr * SECONDS_1HOUR);
	$sql_post .= " AND (utimestamp > " . $unixtime . ")";
}

//Search by tag
if (!empty($tag_with)) {
	$sql_post .= ' AND ( ';
	$first = true;
	foreach ($tag_with as $id_tag) {
		if ($first) $first = false;
		else $sql_post .= " OR ";
		$sql_post .= "tags LIKE '%" . tags_get_name($id_tag) . "%'";
	}
	$sql_post .= ' ) ';
}
if (!empty($tag_without)) {
	$sql_post .= ' AND ( ';
	$first = true;
	foreach ($tag_without as $id_tag) {
		if ($first) $first = false;
		else $sql_post .= " OR ";
		$sql_post .= "tags NOT LIKE '%" . tags_get_name($id_tag) . "%'";
	}
	$sql_post .= ' ) ';
}

// Filter/Only alerts
if (isset($filter_only_alert)) {
	if ($filter_only_alert == 0)
		$sql_post .= " AND event_type NOT LIKE '%alert%'";
	else if ($filter_only_alert == 1)
		$sql_post .= " AND event_type LIKE '%alert%'";
}

$url = "index.php?sec=eventos&amp;sec2=operation/events/events&amp;search=" .
	rawurlencode(io_safe_input($search)) .
	"&amp;event_type=" . $event_type .
	"&amp;severity=" . $severity .
	"&amp;status=" . $status .
	"&amp;ev_group=" . $ev_group .
	"&amp;refr=" . $config["refr"] .
	"&amp;id_agent=" . $id_agent .
	"&amp;id_event=" . $id_event .
	"&amp;pagination=" . $pagination .
	"&amp;group_rep=" . $group_rep .
	"&amp;event_view_hr=" . $event_view_hr .
	"&amp;id_user_ack=" . $id_user_ack .
	"&amp;tag_with=" . $tag_with .
	"&amp;tag_without=" . $tag_without .
	"&amp;filter_only_alert=" . $filter_only_alert .
	"&amp;offset=" . $offset .
	"&amp;toogle_filter=no" .
	"&amp;filter_id=" . $filter_id .
	"&amp;id_name=" . $id_name .
	"&amp;id_group=" . $id_group;

echo "<br>";
//Link to toggle filter
if (!empty($id_name)) {
	echo '<a href="#" id="tgl_event_control"><b>'.__('Event control filter').'</b>&nbsp;'.html_print_image ("images/go.png", true, array ("title" => __('Toggle filter(s)'), "id" => 'toggle_arrow')).'</a><br><br>';
}
else{
	echo '<a href="#" id="tgl_event_control"><b>'.__('Event control filter').'</b>&nbsp;'.html_print_image ("images/down.png", true, array ("title" => __('Toggle filter(s)'), "id" => 'toggle_arrow')).'</a><br><br>';
}

//Start div
echo '<div id="event_control" style="display:none">';

// Table for filter controls
echo '<form id="form_filter" method="post" action="index.php?sec=eventos&amp;sec2=operation/events/events&amp;refr='.$config["refr"].'&amp;pure='.$config["pure"].'&amp;section=list">';
echo '<table style="float:left;" width="550" cellpadding="4" cellspacing="4" class="databox"><tr id="row_name" style="visibility: hidden">';

// Filter name
echo "<td id='filter_name_color'>".__('Filter name')."</td><td>";
html_print_input_text ('id_name', $id_name, '', 15);
echo "</td>";

// Filter group
echo "<td id='filter_group_color'>".__('Filter group')."</td><td>";
html_print_select_groups($config["id_user"], "IR", true, 'id_group', $id_group, '', '', 0, false, false, false, 'w130');
echo "</td></tr>";

// Group combo
echo "<td>".__('Group')."</td><td>";
html_print_select_groups($config["id_user"], "IR", true, 'ev_group', $ev_group, '', '', 0, false, false, false, 'w130');
echo "</td>";

// Event type
echo "<td>".__('Event type')."</td><td>";
$types = get_event_types ();
// Expand standard array to add not_normal (not exist in the array, used only for searches)
$types["not_normal"] = __("Not normal");
html_print_select ($types, 'event_type', $event_type, '', __('All'), '');


echo "</td></tr><tr>";

// Severity
echo "<td>".__('Severity')."</td><td>";
html_print_select (get_priorities (), "severity", $severity, '', __('All'), '-1');
echo '</td>';

// Status
echo "<td>".__('Event status')."</td><td>";

$fields = events_get_all_status();

html_print_select ($fields, 'status', $status, '', '', '');

//NEW LINE
echo "</td></tr><tr>";

// Free search
echo "<td>" . __('Free search') . "</td>";
echo "<td>";
html_print_input_text ('search', io_safe_output($search), '', 15);
echo '</td>';

//Agent search
echo "<td>" . __('Agent search') . "</td>";
echo '<td class="datos">';
$params = array();
$params['show_helptip'] = false;
$params['input_name'] = 'text_agent';
$params['print_hidden_input_idagent'] = true;
$params['hidden_input_idagent_name'] = 'id_agent';
$params['value'] = $text_agent;
$params['hidden_input_idagent_value'] = $id_agent;
ui_print_agent_autocomplete_input($params);
echo '</td>';




echo "</tr>";

// User selectable block size
echo '<tr><td>';
echo __('Block size for pagination');
echo '</td>';
$lpagination[25] = 25;
$lpagination[50] = 50;
$lpagination[100] = 100;
$lpagination[200] = 200;
$lpagination[500] = 500;

echo "<td>";
html_print_select ($lpagination, "pagination", $pagination, '', __('Default'), $config["block_size"]);
echo "</td>";

echo "<td>" . __('Max. hours old') . "</td>";
echo "<td>";
html_print_input_text ('event_view_hr', $event_view_hr, '', 5);
echo "</td>";


echo "</tr><tr>";
echo "<td>" . __('User ack.') . "</td>";
echo "<td>";
$users = users_get_info ();
html_print_select ($users, "id_user_ack", $id_user_ack, '', __('Any'), 0);
echo "</td>";

echo "<td>";
echo __("Repeated");
echo "</td><td>";

$repeated_sel[0] = __("All events");
$repeated_sel[1] = __("Group events");
html_print_select ($repeated_sel, "group_rep", $group_rep, '');
echo "</td></tr>";






echo "<tr>";
echo "<td colspan='2'>" . __('Events with following tags') . "</td>";
echo "<td colspan='2'>" . __('Events without following tags') . "</td>";
echo "</tr>";

echo "<tr>";
$tags_select_with = array();
$tags_select_without = array();
$tag_with_temp = array();
$tag_without_temp = array();
foreach ($tags as $id_tag => $tag) {
	if (array_search($id_tag, $tag_with) === false) {
		$tags_select_with[$id_tag] = $tag;
	}
	else {
		$tag_with_temp[$id_tag] = $tag;
	}
	
	if (array_search($id_tag, $tag_without) === false) {
		$tags_select_without[$id_tag] = $tag;
	}
	else {
		$tag_without_temp[$id_tag] = $tag;
	}
}

$add_with_tag_disabled = empty($tags_select_with);
$remove_with_tag_disabled = empty($tag_with_temp);
$add_without_tag_disabled = empty($tags_select_without);
$remove_without_tag_disabled = empty($tag_without_temp);
echo "<td>";
html_print_select ($tags_select_with, 'select_with', '', '', '', 0,
	false, false, true, '', false, 'width: 120px;');
echo "</td>";
echo "<td>";
html_print_button(__('Add'), 'add_whith', $add_with_tag_disabled,
	'', 'class="add sub"');
echo "</td>";
echo "<td>";
html_print_select ($tags_select_without, 'select_without', '', '', '', 0,
	false, false, true, '', false, 'width: 120px;');
echo "</td>";
echo "<td>";
html_print_button(__('Add'), 'add_whithout', $add_without_tag_disabled,
	'', 'class="add sub"');
echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td valign='top'>";
html_print_select ($tag_with_temp, 'tag_with_temp', array(), '', '',
	0, false, true,
	true, '', false, "width: 120px; height: 50px;");
html_print_input_hidden('tag_with', json_encode($tag_with));
echo "</td>";
echo "<td valign='top'>";
html_print_button(__('Remove'), 'remove_whith', $remove_with_tag_disabled,
	'', 'class="delete sub"');
echo "</td>";
echo "<td valign='top'>";
html_print_select ($tag_without_temp, 'tag_without_temp', array(), '',
	'', 0, false, true,
	true, '', false, "width: 120px; height: 50px;");
html_print_input_hidden('tag_without', json_encode($tag_without));
echo "</td>";
echo "<td valign='top'>";
html_print_button(__('Remove'), 'remove_whithout', $remove_without_tag_disabled,
	'', 'class="delete sub"');
echo "</td>";
echo "</tr>";






echo "<tr>";

echo "<td>";
echo __("Alert events") . "</td><td>";

html_print_select (array('-1' => __('All'), '0' => __('Filter alert events'), '1' => __('Only alert events')), "filter_only_alert", $filter_only_alert, '', '', '');

echo "</td></tr>";

echo '<tr><td>';

echo __("Load filter");

echo '</td><td>';
// Custom filters from user
$filters = events_get_event_filter_select();
html_print_select ($filters, "filter_id", $filter_id, '', __('none'), 0, false);

echo '</td></tr>';


echo '<tr><td colspan="4" style="text-align:right">';

// Trick to catch if the update button has been pushed (don't collapse filter)
// or autorefresh is in use (collapse filter)
$autorefresh_toogle = get_parameter_get('toogle_filter', 'true');
$update_pressed = get_parameter_post('toogle_filter', 'true');
// If autoupdate is in use collapse filter
if ($autorefresh_toogle == 'false'){
	html_print_input_hidden('toogle_filter', 'true');
} 
else{
	// Keeps state with pagination
	if ($autorefresh_toogle == 'no') {
		html_print_input_hidden('toogle_filter', 'false');
	}
	else {
		
		// If update button has been pushed then don't collapse filter
		if ($update_pressed == 'false') {
			html_print_input_hidden('toogle_filter', 'false');
		} // Else collapse filter
		else {
			html_print_input_hidden('toogle_filter', 'true');
		}
	}
}

//The buttons
html_print_submit_button (__('Update filter'), 'update_filter', false, 'class="sub upd" style="visibility:hidden"');
html_print_submit_button (__('Save filter'), 'save_filter', false, 'class="sub upd"');
html_print_submit_button (__('Update'), 'update', false, 'class="sub upd"');

echo "</td></tr></table></form>"; //This is the filter div
echo '<div style="width:220px; float:left;">';
echo grafico_eventos_grupo(350, 248, rawurlencode ($sql_post), $meta);
echo '</div>';
echo '<div id="steps_clean">&nbsp;</div>';
echo '</div>';

// Choose the table where search if metaconsole or not
if($meta) {
	$event_table = 'tmetaconsole_event';
}
else {
	$event_table = 'tevento';
}

if ($group_rep == 0) {
	switch ($config["dbtype"]) {
		case "mysql":
			$sql = "SELECT *
				FROM $event_table
				WHERE 1=1 ".$sql_post."
				ORDER BY utimestamp DESC LIMIT ".$offset.",".$pagination;
			break;
		case "postgresql":
			$sql = "SELECT *
				FROM $event_table
				WHERE 1=1 ".$sql_post."
				ORDER BY utimestamp DESC LIMIT ".$pagination." OFFSET ".$offset;
			break;
		case "oracle":
			$set = array();
			$set['limit'] = $pagination;
			$set['offset'] = $offset;
			$sql = "SELECT *
				FROM $event_table
				WHERE 1=1 ".$sql_post."
				ORDER BY utimestamp DESC"; 
			$sql = oracle_recode_query ($sql, $set);
			break;
	}
	
	//Extract the events by filter (or not) from db
	$result = db_get_all_rows_sql ($sql);
}
else {
	$result = events_get_events_grouped($sql_post, $offset, $pagination, $meta);
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
		WHERE 1=1 " . $sql_post;
}
else {
	$sql = "SELECT COUNT(1)
		FROM (SELECT 1
			FROM $event_table
			WHERE 1=1 " . $sql_post . "
			GROUP BY evento, id_agentmodule) AS t";
}

$total_events = (int) db_get_sql ($sql);
if (empty ($result)) {
	$result = array ();
}

$table->width = '100%';
$table->id = "eventtable";
$table->cellpadding = 4;
$table->cellspacing = 4;
$table->class = "databox";
$table->head = array ();
$table->data = array ();

//fields that the user has selected to show
$show_fields = explode (',', $config['event_fields']);

//headers
$i = 0;
$table->head[$i] = __('ID');
$table->align[$i] = 'center';
$i++;
if (in_array('server_name', $show_fields)) {
	$table->head[$i] = __('Server');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('estado', $show_fields)) {
	$table->head[$i] = __('Status');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('id_evento', $show_fields)) {
	$table->head[$i] = __('Event ID');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('evento', $show_fields)) {
	$table->head[$i] = __('Event Name');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('id_agente', $show_fields)) {
	$table->head[$i] = __('Agent name');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('timestamp', $show_fields)) {
	$table->head[$i] = __('Timestamp');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('id_usuario', $show_fields)) {
	$table->head[$i] = __('User');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('owner_user', $show_fields)) {
	$table->head[$i] = __('Owner');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('id_grupo', $show_fields)) {
	$table->head[$i] = __('Group');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('event_type', $show_fields)) {
	$table->head[$i] = __('Event type');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('id_agentmodule', $show_fields)) {
	$table->head[$i] = __('Agent Module');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('id_alert_am', $show_fields)) {
	$table->head[$i] = __('Alert');
	$table->align[$i] = 'center';
	$i++;
}

if (in_array('criticity', $show_fields)) {
	$table->head[$i] = __('Severity');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('user_comment', $show_fields)) {
	$table->head[$i] = __('Comment');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('tags', $show_fields)) {
	$table->head[$i] = __('Tags');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('source', $show_fields)) {
	$table->head[$i] = __('Source');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('id_extra', $show_fields)) {
	$table->head[$i] = __('Extra ID');
	$table->align[$i] = 'center';
	$i++;
}
if (in_array('ack_utimestamp', $show_fields)) {
	$table->head[$i] = __('ACK Timestamp');
	$table->align[$i] = 'center';
	$i++;
}
if ($i != 0) {
	$table->head[$i] = __('Action');
	$table->align[$i] = 'center';
	$table->size[$i] = '80px';
	$i++;
	if (check_acl ($config["id_user"], 0, "IW") == 1) {
		$table->head[$i] = html_print_checkbox ("all_validate_box", "1", false, true);
		$table->align[$i] = 'center';
	}
}

$idx = 0;
//Arrange data. We already did ACL's in the query
foreach ($result as $event) {
	$data = array ();
	
	//First pass along the class of this row
	$myclass = get_priority_class ($event["criticity"]);
	$table->rowclass[] = $myclass;
	
	//print status
	$estado = $event["estado"];
	
	// Colored box
	switch($estado) {
		case 0:
			$img_st = "images/star.png";
			$title_st = __('New event');
			break;
		case 1:
			$img_st = "images/tick.png";
			$title_st = __('Event validated');
			break;
		case 2:
			$img_st = "images/hourglass.png";
			$title_st = __('Event in process');
			break;
	}
	
	$i = 0;
	
	$data[$i] = "#".$event["id_evento"];
	
	// Pass grouped values in hidden fields to use it from modal window
	if($group_rep) {
		$similar_ids = $event['similar_ids'];
		$timestamp_first = $event['timestamp_rep_min'];
		$timestamp_last = $event['timestamp_rep'];
	}
	else {
		$similar_ids = $event["id_evento"];
		$timestamp_first = $event['utimestamp'];
		$timestamp_last = $event['utimestamp'];
	}
	
	// Store group data to show in extended view
	$data[$i] .= html_print_input_hidden('similar_ids_' . $event["id_evento"], $similar_ids, true);
	$data[$i] .= html_print_input_hidden('timestamp_first_' . $event["id_evento"], $timestamp_first, true);
	$data[$i] .= html_print_input_hidden('timestamp_last_' . $event["id_evento"], $timestamp_last, true);

	// Store server id if is metaconsole. 0 otherwise
	if($meta) {
		$server_id = $event['server_id'];
		
		// If meta activated, propagate the id of the event on node (source id)
		$data[$i] .= html_print_input_hidden('source_id_' . $event["id_evento"], $event['id_source_event'], true);
	}		
	else {
		$server_id = 0;
	}
	
	$data[$i] .= html_print_input_hidden('server_id_' . $event["id_evento"], $server_id, true);

	if (empty($event['event_rep'])) {
		$event['event_rep'] = 0;
	}
	$data[$i] .= html_print_input_hidden('event_rep_'.$event["id_evento"], $event['event_rep'], true);
	// Store concat comments to show in extended view
	$data[$i] .= html_print_input_hidden('user_comment_'.$event["id_evento"], base64_encode($event['user_comment']), true);		
	
	$i++;
	
	if (in_array('server_name',$show_fields)) {
		if($meta) {
			$data[$i] = db_get_value('server_name','tmetaconsole_setup','id',$event["server_id"]);
		}
		else {
			$data[$i] = db_get_value('name','tserver','id_server',$event["server_id"]);
		}
		$i++;
	}
	if (in_array('estado',$show_fields)) {
		$data[$i] = html_print_image ($img_st, true, 
			array ("class" => "image_status",
				"width" => 16,
				"height" => 16,
				"title" => $title_st,
				"id" => 'status_img_'.$event["id_evento"]));
		$i++;
	}
	if (in_array('id_evento',$show_fields)) {
		$data[$i] = $event["id_evento"];
		$i++;
	}
	
	switch ($event["criticity"]) {
		default:
		case 0:
			$img_sev = "images/status_sets/default/severity_maintenance.png";
			break;
		case 1:
			$img_sev = "images/status_sets/default/severity_informational.png";
			break;
		case 2:
			$img_sev = "images/status_sets/default/severity_normal.png";
			break;
		case 3:
			$img_sev = "images/status_sets/default/severity_warning.png";
			break;
		case 4:
			$img_sev = "images/status_sets/default/severity_critical.png";
			break;
		case 5:
			$img_sev = "images/status_sets/default/severity_minor.png";
			break;
		case 6:
			$img_sev = "images/status_sets/default/severity_major.png";
			break;
	}
	
	if (in_array('evento', $show_fields)) {
		// Event description
		$data[$i] = '<span title="'.$event["evento"].'" class="f9">';
		$data[$i] .= '<a href="javascript:" onclick="show_event_dialog(' . $event["id_evento"] . ', '.$group_rep.');">';
		$data[$i] .= '<span class="'.$myclass.'" style="font-size: 7.5pt;">' . ui_print_truncate_text (io_safe_output($event["evento"]), 160) . '</span>';
		$data[$i] .= '</a></span>';
		$i++;
	}
	
	if (in_array('id_agente', $show_fields)) {
		$data[$i] = '<span style="color: #000000">';
		
		if ($event["id_agente"] > 0) {
			// Agent name
			if($meta) {
				$data[$i] .= $event["agent_name"];
			}
			else {
				$data[$i] .= ui_print_agent_name ($event["id_agente"], true);
			}
		}
		else {
			$data[$i] .= '';
		}
		$data[$i] .= '</span>';
		$i++;
	}
	
	if (in_array('timestamp', $show_fields)) {
		//Time
		$data[$i] = '<span class="'.$myclass.'">';
		if ($group_rep == 1) {
			$data[$i] .= ui_print_timestamp ($event['timestamp_rep'], true);
		}
		else {
			$data[$i] .= ui_print_timestamp ($event["timestamp"], true);
		}
		$data[$i] .= '</span>';
		$i++;
	}
	
	if (in_array('owner_user',$show_fields)) {
		$owner_name = db_get_value('fullname', 'tusuario', 'id_user', $event['owner_user']);
		$data[$i] = $owner_name;
		$i++;
	}
	
	if (in_array('id_usuario',$show_fields)) {
		$user_name = db_get_value('fullname', 'tusuario', 'id_user', $event['id_usuario']);
		$data[$i] = $user_name;
		$i++;
	}
	
	if (in_array('id_grupo',$show_fields)) {
		if($meta) {
			$data[$i] = $event['group_name'];
		}
		else {
			$id_group = $event["id_grupo"];
			$group_name = db_get_value('nombre', 'tgrupo', 'id_grupo', $id_group);
			if ($id_group == 0) {
				$group_name = __('All');
			}
			$data[$i] = $group_name;
		}
		$i++;
	}
	
	if (in_array('event_type',$show_fields)) {
		$data[$i] = events_print_type_description($event["event_type"], true);
		$i++;
	}
	
	if (in_array('id_agentmodule',$show_fields)) {
		if($meta) {
			$data[$i] = $event["module_name"];
		}
		else {
			$data[$i] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$event["id_agente"].'&amp;tab=data">'
				. db_get_value('nombre', 'tagente_modulo', 'id_agente_modulo', $event["id_agentmodule"]).'</a>';
		}
		$i++;
	}
	
	if (in_array('id_alert_am',$show_fields)) {
		if($meta) {
			$data[$i] = $event["alert_template_name"];
		}
		else {
			if ($event["id_alert_am"] != 0) {
				$sql = 'SELECT name
					FROM talert_templates
					WHERE id IN (SELECT id_alert_template
					FROM talert_template_modules
					WHERE id = ' . $event["id_alert_am"] . ');';
				
				$templateName = db_get_sql($sql);
				$data[$i] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$event["id_agente"].'&amp;tab=alert">'.$templateName.'</a>';
			}
			else {
				$data[$i] = '';
			}
		}
		$i++;
	}
	
	if (in_array('criticity',$show_fields)) {
		$data[$i] = get_priority_name ($event["criticity"]);
		$i++;
	}
	
	if (in_array('user_comment',$show_fields)) {
		$data[$i] = '<span id="comment_header_' . $event['id_evento'] . '">' .ui_print_truncate_text(strip_tags($event["user_comment"])) . '</span>';
		$i++;
	}
	
	if (in_array('tags',$show_fields)) {
		if ($event["tags"] != '') {
			$tag_array = explode(',', $event["tags"]);
			$data[$i] = '';
			foreach ($tag_array as $tag_element) {
				$blank_char_pos = strpos($tag_element, ' ');
				$tag_name = substr($tag_element, 0, $blank_char_pos);
				$tag_url = substr($tag_element, $blank_char_pos + 1);
				$data[$i] .= ' ' .$tag_name;
				if (!empty($tag_url)) {
					$data[$i] .= ' <a href="javascript: openURLTagWindow(\'' . $tag_url . '\');">' . html_print_image('images/lupa.png', true, array('title' => __('Click here to open a popup window with URL tag'))) . '</a> ';
				}
				$data[$i] .= ',';
			}
			$data[$i] = rtrim($data[$i], ',');
		}
		else {
			$data[$i] = '';
		}
		
		$i++;
	}
	
	if (in_array('source',$show_fields)) {
		$data[$i] = $event["source"];
		$i++;
	}
	
	if (in_array('id_extra',$show_fields)) {
		$data[$i] = $event["id_extra"];
		$i++;
	}
	
	if (in_array('ack_utimestamp',$show_fields)) {
		if($event["ack_utimestamp"] == 0){
			$data[$i] = '';
		}
		else {
			$data[$i] = date ($config["date_format"], $event['ack_utimestamp']);
		}
		$i++;
	}
	
	if ($i != 0) {
		//Actions
		$data[$i] = '';
		// Validate event
		if (($event["estado"] != 1) and (check_acl ($config["id_user"], $event["id_grupo"], "IW") == 1)) {
			$data[$i] .= '<a href="javascript:validate_event_advanced('.$event["id_evento"].', 1)" id="validate-'.$event["id_evento"].'">';
			$data[$i] .= html_print_image ("images/ok.png", true,
				array ("title" => __('Validate event')));
			$data[$i] .= '</a>&nbsp;';
		}
		else {
			$data[$i] .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		}
		// Delete event
		if (check_acl ($config["id_user"], $event["id_grupo"], "IM") == 1) {
			if($event['estado'] != 2) {
				$data[$i] .= '<a class="delete_event" href="#" id="delete-'.$event['id_evento'].'">';
				$data[$i] .= html_print_image ("images/cross.png", true,
					array ("title" => __('Delete event'), "id" => 'delete_cross_' . $event['id_evento']));
				$data[$i] .= '</a>&nbsp;';
			}
			else {
				$data[$i] .= html_print_image ("images/cross.disabled.png", true,
					array ("title" => __('Is not allowed delete events in process'))).'&nbsp;';
			}
		}
		
		$data[$i] .= '<a href="javascript:" onclick="show_event_dialog(' . $event["id_evento"] . ', '.$group_rep.');">';
		$data[$i] .= html_print_input_hidden('event_title_'.$event["id_evento"], "#".$event["id_evento"]." - ".$event["evento"], true);
		$data[$i] .= html_print_image ("images/eye.png", true,
			array ("title" => __('Show more')));	
		$data[$i] .= '</a>&nbsp;';
		$i++;
		
		if (check_acl ($config["id_user"], $event["id_grupo"], "IW") == 1) {
			//Checkbox
			$data[$i] = html_print_checkbox_extended ("validate_ids[]", $event['id_evento'], false, false, false, 'class="chk_val"', true);
		}
		array_push ($table->data, $data);
	}
	
	$idx++;
}

echo '<div id="events_list">';
if (!empty ($table->data)) {
	echo '<div style="clear:both"></div>';
	ui_pagination ($total_events, $url."&pure=".$config["pure"], $offset, $pagination);
	
	echo '<form method="post" id="form_events" action="'.$url.'">';
	echo "<input type='hidden' name='delete' id='hidden_delete_events' value='0' />";
	
	html_print_table ($table);
	
	echo '<div style="width:'.$table->width.';" class="action-buttons">';
	if (check_acl ($config["id_user"], 0, "IW") == 1) {
		html_print_button(__('Validate selected'), 'validate_button', false, 'validate_selected();', 'class="sub ok"');
	}
	if (check_acl ($config["id_user"], 0,"IM") == 1) {
		html_print_button(__('Delete selected'), 'delete_button', false, 'delete_selected();', 'class="sub delete"');
		?>
		<script type="text/javascript">
		function delete_selected() {
			if(confirm('<?php echo __('Are you sure?'); ?>')) {
				$("#hidden_delete_events").val(1);
				$("#form_events").submit();
			}
		}
		function validate_selected() {
			$(".chk_val").each(function() { 
				if($(this).is(":checked")) {
					validate_event_advanced($(this).val(),1);
				}
			});  
		}
		</script>
		<?php
	}
	echo '</div></form>';
}
else {
	echo '<div class="nf">'.__('No events').'</div>';
}
echo '</div>';

unset ($table);

// Values to be used from javascript library
html_print_input_hidden('ajax_file', ui_get_full_url("ajax.php", false, false, false));
html_print_input_hidden('meta', (int)$meta);

ui_require_jquery_file('json');
?>
<script language="javascript" type="text/javascript">
/*<![CDATA[ */

var select_with_tag_empty = <?php echo (int)$remove_with_tag_disabled;?>;
var select_without_tag_empty = <?php echo (int)$remove_without_tag_disabled;?>;
var origin_select_with_tag_empty = <?php echo (int)$add_with_tag_disabled;?>;
var origin_select_without_tag_empty = <?php echo (int)$add_without_tag_disabled;?>;

var val_none = 0;
var text_none = "<?php echo __('None'); ?>";

$(document).ready( function() {
	// Don't collapse filter if update button has been pushed
	if ($("#hidden-toogle_filter").val() == 'false'){
		$("#event_control").toggle ();
	}
	
	// If selected is not 'none' show filter name
	if ( $("#filter_id").val() != 0 ) {
		$("#row_name").css('visibility', '');
		$("#submit-update_filter").css('visibility', '');
	}
	
	$("#filter_id").change(function () {
		// If selected 'none' flush filter
		if ( $("#filter_id").val() == 0 ) {
			$("#text-id_name").val('');
			$("#ev_group").val(0);
			$("#event_type").val('');
			$("#severity").val(-1);
			$("#status").val(3);
			$("#text-search").val('');
			$("#text_id_agent").val( <?php echo '"' . __('All') . '"' ?> );
			$("#pagination").val(25);
			$("#text-event_view_hr").val(8);
			$("#id_user_ack").val(0);
			$("#group_rep").val(1);
			$("#tag").val('');
			$("#filter_only_alert").val(-1);
			$("#row_name").css('visibility', 'hidden');
			$("#submit-update_filter").css('visibility', 'hidden');
			$("#id_group").val(0);
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
							$("#text-id_name").val(val);
						if (i == 'id_group')
							$("#ev_group").val(val);
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
						if (i == 'pagination')
							$("#pagination").val(val);
						if (i == 'event_view_hr')
							$("#text-event_view_hr").val(val);
						if (i == 'id_user_ack')
							$("#id_user_ack").val(val);
						if (i == 'group_rep')
							$("#group_rep").val(val);
						if (i == 'tag_with') {
							$("#hidden-tag_with").val(val);
						}
						if (i == 'tag_without') {
							$("#hidden-tag_without").val(val);
						}
						if (i == 'filter_only_alert')
							$("#filter_only_alert").val(val);
						if (i == 'id_group_filter')
							$("#id_group").val(val);
					});
					reorder_tags_inputs();
				},
				"json"
			);
		}
	});
	
	// This saves an event filter
	$("#submit-save_filter").click(function () {
		// Checks if the filter has name or not
		if ($('#row_name').css('visibility') == 'hidden') {
			$('#row_name').css('visibility', '');
			$('#show_filter_error')
				.html('<h3 class="error"> <?php echo __('Define name and group for the filter and click on Save filter again'); ?> </h3>');
			$('#filter_name_color').css('color', '#CC0000');
			$('#filter_group_color').css('color', '#CC0000');
		// If the filter has name insert in database
		}
		else {
			$('#filter_name_color').css('color', '#000000');
			$('#filter_group_color').css('color', '#000000');
			// If the filter name is blank show error
			if ($('#text-id_name').val() == '') {
				$('#show_filter_error').html('<h3 class="error"> <?php echo __('Filter name cannot be left blank'); ?> </h3>');
				return false;
			}
			
			var id_filter_save;
			
			jQuery.post ("<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
				{"page" : "operation/events/events_list",
				"save_event_filter" : 1,
				"id_name" : $("#text-id_name").val(),
				"id_group" : $("#ev_group").val(),
				"event_type" : $("#event_type").val(),
				"severity" : $("#severity").val(),
				"status" : $("#status").val(),
				"search" : $("#text-search").val(),
				"text_agent" : $("#text_id_agent").val(),
				"pagination" : $("#pagination").val(),
				"event_view_hr" : $("#text-event_view_hr").val(),
				"id_user_ack" : $("#id_user_ack").val(),
				"group_rep" : $("#group_rep").val(),
				"tag_with": $("#hidden-tag_with").val(),
				"tag_without": $("#hidden-tag_without").val(),
				"filter_only_alert" : $("#filter_only_alert").val(),
				"id_group_filter": $("#id_group").val()
				},
				function (data) {
					if (data == 'error') {
						$('#show_filter_error').html('<h3 class="error"> <?php echo __('Error creating filter'); ?> </h3>');
					}
					else {
						id_filter_save = data;
						$('#show_filter_error').html('<h3 class="suc"> <?php echo __('Filter created'); ?> </h3>');
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
						
						if (i == id_filter_save){
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
		}
		return false;
	});
	
	// This updates an event filter
	$("#submit-update_filter").click(function () {
		
		// If the filter name is blank show error
		if ($('#text-id_name').val() == '') {
			$('#show_filter_error')
			.html('<h3 class="error"> <?php echo __('Filter name cannot be left blank'); ?> </h3>');
			
			return false;
		}
		
		var id_filter_update =  $("#filter_id").val();
		
		jQuery.post ("<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
			{"page" : "operation/events/events_list",
			"update_event_filter" : 1,
			"id" : $("#filter_id").val(),
			"id_name" : $("#text-id_name").val(),
			"id_group" : $("#ev_group").val(),
			"event_type" : $("#event_type").val(),
			"severity" : $("#severity").val(),
			"status" : $("#status").val(),
			"search" : $("#text-search").val(),
			"text_agent" : $("#text_id_agent").val(),
			"pagination" : $("#pagination").val(),
			"event_view_hr" : $("#text-event_view_hr").val(),
			"id_user_ack" : $("#id_user_ack").val(),
			"group_rep" : $("#group_rep").val(),
			"tag_with" : $("#hidden-tag_with").val(),
			"tag_without" : $("#hidden-tag_without").val(),
			"filter_only_alert" : $("#filter_only_alert").val(),
			"id_group_filter": $("#id_group").val()
			},
			function (data) {
				if (data == 'ok') {
					$('#show_filter_error').html('<h3 class="suc"> <?php echo __('Filter updated'); ?> </h3>');
				}
				else {
					$('#show_filter_error').html('<h3 class="error"> <?php echo __('Error updating filter'); ?> </h3>');
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
			
			return false;
	});
	
	// Change toggle arrow when it's clicked
	$("#tgl_event_control").click(function() {
		if ($("#toggle_arrow").attr("src").match(/[^\.]+down\.png/) == null){
			var params = [];
			params.push("get_image_path=1");
			params.push("img_src=images/down.png");
			params.push("page=include/ajax/skins.ajax");
			params.push("only_src=1");
			jQuery.ajax ({
				data: params.join ("&"),
				type: 'POST',
				url: action="<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
				async: false,
				timeout: 10000,
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
				async: false,
				timeout: 10000,
				success: function (data) {
					$("#toggle_arrow").attr('src', data);
				}
			});
		}
	});
	
	$("#button-add_whith").click(function() {
		click_button_add_tag("with");
		});
	
	$("#button-add_whithout").click(function() {
		click_button_add_tag("without");
		});
	
	$("#button-remove_whith").click(function() {
		click_button_remove_tag("with");
	});
	
	$("#button-remove_whithout").click(function() {
		click_button_remove_tag("without");
	});
	
});

function click_button_remove_tag(what_button) {
	if (what_button == "with") {
		id_select_origin = "#select_with";
		id_select_destiny = "#tag_with_temp";
		id_button_remove = "#button-remove_whith";
		id_button_add = "#button-add_whith";
		
		select_origin_empty = origin_select_with_tag_empty;
	}
	else { //without
		id_select_origin = "#select_without";
		id_select_destiny = "#tag_without_temp";
		id_button_remove = "#button-remove_whithout";
		id_button_add = "#button-add_whithout";
		
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
		id_button_remove = "#button-remove_whith";
		id_button_add = "#button-add_whith";
		
		select_destiny_empty = select_with_tag_empty;
	}
	else { //without
		id_select_origin = "#select_without";
		id_select_destiny = "#tag_without_temp";
		id_button_remove = "#button-remove_whithout";
		id_button_add = "#button-add_whithout";
		
		select_destiny_empty = select_without_tag_empty;
	}
	
	without_val = $(id_select_origin).val();
	without_text = $(id_select_origin + " option:selected").text();
	
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
	
	$(id_hidden).val(jQuery.toJSON(value_store));
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
	
	
	
	
	tags_json = $("#hidden-tag_with").val();
	tags = jQuery.evalJSON(tags_json);
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
		$("#button-add_whith").attr('disabled', 'true');
		$("#select_with").append($("<option value='" + val_none + "'>" + text_none + "</option>"));
	}
	else {
		origin_select_with_tag_empty = false;
		$("#button-add_whith").removeAttr('disabled');
	}
	if ($("#tag_with_temp option").length == 0) {
		select_with_tag_empty = true;
		$("#button-remove_whith").attr('disabled', 'true');
		$("#tag_with_temp").append($("<option value='" + val_none + "'>" + text_none + "</option>"));
	}
	else {
		select_with_tag_empty = false;
		$("#button-remove_whith").removeAttr('disabled');
	}
	
	
	
	
	tags_json = $("#hidden-tag_without").val();
	tags = jQuery.evalJSON(tags_json);
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
		$("#button-add_whithout").attr('disabled', 'true');
		$("#select_without").append($("<option value='" + val_none + "'>" + text_none + "</option>"));
	}
	else {
		origin_select_without_tag_empty = false;
		$("#button-add_whithout").removeAttr('disabled');
	}
	if ($("#tag_without_temp option").length == 0) {
		select_without_tag_empty = true;
		$("#button-remove_whithout").attr('disabled', 'true');
		$("#tag_without_temp").append($("<option value='" + val_none + "'>" + text_none + "</option>"));
	}
	else {
		select_without_tag_empty = false;
		$("#button-remove_whithout").removeAttr('disabled');
	}
}
/* ]]> */
</script>
