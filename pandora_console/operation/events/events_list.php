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

require_once ("include/functions_events.php"); //Event processing functions
require_once ("include/functions_alerts.php"); //Alerts processing functions
require_once ("include/functions.php");
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

if (is_ajax()) {
	$get_filter_values = get_parameter('get_filter_values', 0);
	$save_event_filter = get_parameter('save_event_filter', 0);
	$update_event_filter = get_parameter('update_event_filter', 0);
	$get_event_filters = get_parameter('get_event_filters', 0);

	// Get db values of a single filter
	if ($get_filter_values) {
		$id_filter = get_parameter('id');
		
		$event_filter = events_get_event_filter($id_filter);

		$event_filter['tag'] = io_safe_output($event_filter['tag']);
		$event_filter['id_name'] = io_safe_output($event_filter['id_name']);
		
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
		$values['tag'] = get_parameter('tag');
		$values['filter_only_alert'] = get_parameter('filter_only_alert');
		$values['id_group_filter'] = get_parameter('id_group_filter');
		
		$result = db_process_sql_insert('tevent_filter', $values);
		
		if ($result === false){
			echo 'error';
		}
		else {
			echo $result;
		}
	}
	
	if ($update_event_filter){
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
		$values['tag'] = get_parameter('tag');
		$values['filter_only_alert'] = get_parameter('filter_only_alert');
		$values['id_group_filter'] = get_parameter('id_group_filter');

		$result = db_process_sql_update('tevent_filter', $values, array('id_filter' => $id));		
	
		if ($result === false){
			echo 'error';
		}
		else {
			echo 'ok';
		}		
	}
	
	if ($get_event_filters){
		$event_filter = events_get_event_filter_select();
		
		echo json_encode($event_filter);		
	}
		
	return;
}

// Error div for ajax messages
echo "<div id='show_filter_error'>";
echo "</div>";

$tag = get_parameter("tag", "");

if ($id_agent == -2) {
	$text_agent = (string) get_parameter("text_agent", __("All"));
	
	switch ($text_agent)
	{
		case __('All'):
			$id_agent = -1;
			break;
		case __('Server'):
			$id_agent = 0;
			break;
		default:
			$id_agent = agents_get_agent_id($text_agent);
			break;
	}
}
else {
	switch ($id_agent)
	{
		case -1:
			$text_agent = __('All');
			break;
		case 0:
			$text_agent = __('Server');
			break;
		default:
			$text_agent = agents_get_name($id_agent);
			break;
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

switch($status) {
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
	if ($event_type == "warning" || $event_type == "critical" || $event_type == "normal") {
		$sql_post .= " AND event_type LIKE '%$event_type%' ";
	}
	elseif ($event_type == "not_normal") {
		$sql_post .= " AND event_type LIKE '%warning%' OR event_type LIKE '%critical%' OR event_type LIKE '%unknown%' ";
	}
	else {
		$sql_post .= " AND event_type = '".$event_type."'";
	}

}
if ($severity != -1)
	$sql_post .= " AND criticity = ".$severity;
if ($id_agent != -1)
	$sql_post .= " AND id_agente = ".$id_agent;
if ($id_event != -1)
	$sql_post .= " AND id_evento = ".$id_event;

if ($id_user_ack != "0")
	$sql_post .= " AND id_usuario = '".$id_user_ack."'";


if ($event_view_hr > 0) {
	$unixtime = get_system_time () - ($event_view_hr * 3600); //Put hours in seconds
	$sql_post .= " AND (utimestamp > ".$unixtime . " OR estado = 2)";
}

//Search by tag
if ($tag != "") {
	$sql_post .= " AND tags LIKE '%".io_safe_input($tag)."%'";
}

// Filter/Only alerts
if (isset($filter_only_alert)){
	if ($filter_only_alert == 0)
		$sql_post .= " AND event_type NOT LIKE '%alert%'";
	else if ($filter_only_alert == 1)
		$sql_post .= " AND event_type LIKE '%alert%'";
}

$url = "index.php?sec=eventos&amp;sec2=operation/events/events&amp;search=" .
	rawurlencode(io_safe_input($search)) . "&amp;event_type=" . $event_type .
	"&amp;severity=" . $severity . "&amp;status=" . $status . "&amp;ev_group=" .
	$ev_group . "&amp;refr=" . $config["refr"] . "&amp;id_agent=" .
	$id_agent . "&amp;id_event=" . $id_event . "&amp;pagination=" .
	$pagination . "&amp;group_rep=" . $group_rep . "&amp;event_view_hr=" .
	$event_view_hr . "&amp;id_user_ack=" . $id_user_ack . "&amp;tag=" . $tag . "&amp;filter_only_alert=" . $filter_only_alert . "&amp;offset=" . $offset . "&amp;toogle_filter=no" .
	"&amp;filter_id=" . $filter_id . "&amp;id_name=" . $id_name . "&amp;id_group=" . $id_group;

echo "<br>";
//Link to toggle filter
if (!empty($id_name)){
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
echo "<td>".__('Free search')."</td><td>";
html_print_input_text ('search', io_safe_output($search), '', 15);
echo '</td>';

//Agent search
$src_code = html_print_image('images/lightning.png', true, false, true);
echo "<td>".__('Agent search')."</td><td>";
html_print_input_text_extended ('text_agent', $text_agent, 'text_id_agent', '', 30, 100, false, '',
array('style' => 'background: url(' . $src_code . ') no-repeat right;'))
. '<a href="#" class="tip">&nbsp;<span>' . __("Type at least two characters to search") . '</span></a>';


echo "</td></tr>";

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

echo "<td>".__('Max. hours old')."</td>";
echo "<td>";
html_print_input_text ('event_view_hr', $event_view_hr, '', 5);
echo "</td>";


echo "</tr><tr>";
echo "<td>".__('User ack.')."</td>";
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
echo "<tr><td>";
echo __("Tag") . "</td><td>";
//html_print_input_text ('tag', $tag_search, '', 15);
$tags = tags_search_tag();

if($tags === false) {
	$tags = array();
}

$tags_name = array();
foreach($tags as $t) {
	$tags_name[$t['name']] = $t['name'];
}

html_print_select ($tags_name, "tag", $tag, '', __('All'), "");

echo "</td>";

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
	if ($autorefresh_toogle == 'no'){
		html_print_input_hidden('toogle_filter', 'false');
	} else {
		
		// If update button has been pushed then don't collapse filter
		if ($update_pressed == 'false'){
			html_print_input_hidden('toogle_filter', 'false');
		} // Else collapse filter
		else{
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
echo grafico_eventos_grupo(350, 248, rawurlencode ($sql_post));
echo '</div>';
echo '<div id="steps_clean">&nbsp;</div>';
echo '</div>';

if ($group_rep == 0) {
	switch ($config["dbtype"]) {
		case "mysql":
			$sql = "SELECT *
				FROM tevento
				WHERE 1=1 ".$sql_post." ORDER BY utimestamp DESC LIMIT ".$offset.",".$pagination;
			break;
		case "postgresql":
			$sql = "SELECT *
				FROM tevento
				WHERE 1=1 ".$sql_post." ORDER BY utimestamp DESC LIMIT ".$pagination." OFFSET ".$offset;
			break;
		case "oracle":
			$set = array();
			$set['limit'] = $pagination;
			$set['offset'] = $offset;
			$sql = "SELECT *
				FROM tevento
				WHERE 1=1 ".$sql_post." ORDER BY utimestamp DESC"; 
			$sql = oracle_recode_query ($sql, $set);
			break;
	}
}
else {
	switch ($config["dbtype"]) {
		case "mysql":
			db_process_sql ('SET group_concat_max_len = 9999999');
			$sql = "SELECT *, MAX(id_evento) AS id_evento, GROUP_CONCAT(DISTINCT user_comment SEPARATOR '') AS user_comment,
					MIN(estado) AS min_estado, MAX(estado) AS max_estado, COUNT(*) AS event_rep, MAX(utimestamp) AS timestamp_rep
				FROM tevento
				WHERE 1=1 ".$sql_post."
				GROUP BY evento, id_agentmodule
				ORDER BY timestamp_rep DESC LIMIT ".$offset.",".$pagination;
			break;
		case "postgresql":
			$sql = "SELECT *, MAX(id_evento) AS id_evento, array_to_string(array_agg(DISTINCT user_comment), '') AS user_comment,
					MIN(estado) AS min_estado, MAX(estado) AS max_estado, COUNT(*) AS event_rep, MAX(utimestamp) AS timestamp_rep
				FROM tevento
				WHERE 1=1 ".$sql_post."
				GROUP BY evento, id_agentmodule, id_evento, id_agente, id_usuario, id_grupo, estado, timestamp, utimestamp, event_type, id_alert_am, criticity, user_comment, tags, source, id_extra
				ORDER BY timestamp_rep DESC LIMIT ".$pagination." OFFSET ".$offset;
			break;
		case "oracle":
			$set = array();
			$set['limit'] = $pagination;
			$set['offset'] = $offset;
			// TODO: Remove duplicate user comments
			$sql = "SELECT a.*, b.event_rep, b.timestamp_rep
				FROM (SELECT * FROM tevento WHERE 1=1 ".$sql_post.") a, 
				(SELECT MAX (id_evento) AS id_evento,  to_char(evento) AS evento, 
				id_agentmodule, COUNT(*) AS event_rep, MIN(estado) AS min_estado, MAX(estado) AS max_estado,
				LISTAGG(user_comment, '') AS user_comment, MAX(utimestamp) AS timestamp_rep 
				FROM tevento 
				WHERE 1=1 ".$sql_post." 
				GROUP BY to_char(evento), id_agentmodule) b 
				WHERE a.id_evento=b.id_evento AND 
				to_char(a.evento)=to_char(b.evento) 
				AND a.id_agentmodule=b.id_agentmodule";
			$sql = oracle_recode_query ($sql, $set);
			break;
	}

}

//Extract the events by filter (or not) from db
$result = db_get_all_rows_sql ($sql);

// Delete rnum field generated by oracle_recode_query() function
if (($config['dbtype'] == 'oracle') && ($result !== false)) {
	for ($i=0; $i < count($result); $i++) {
		unset($result[$i]['rnum']);	
	}
}

if ($group_rep == 0) {
	$sql = "SELECT COUNT(id_evento) FROM tevento WHERE 1=1 ".$sql_post;
}
else {
	$sql = "SELECT COUNT(1) FROM (SELECT 1 FROM tevento
		WHERE 1=1 $sql_post GROUP BY evento, id_agentmodule) AS t";
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
if ($i != 0) {
	$table->head[$i] = __('Action');
	$table->align[$i] = 'center';
	$table->size[$i] = '80px';
	$i++;
	if (check_acl ($config["id_user"], 0, "IW") == 1) {
		$table->head[$i] = html_print_checkbox ("allbox", "1", false, true);
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
	// Grouped events
	if ($group_rep != 0) {
		if ($event["max_estado"] == 2) {
			$estado = 2;
		} else if ($event["min_estado"] == 0) {
			$estado = 0;
		} else {
			$estado = 1;
		}
	}
	// Ungrouped events
	else {
		$estado = $event["estado"];
	}
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
	}
		
	if (in_array('evento', $show_fields)) {
		// Event description
		$data[$i] = '<span title="'.$event["evento"].'" class="f9">';
		$data[$i] .= '<a href="javascript: toggleVisibleExtendedInfo(' . $event["id_evento"] . ');">';
		$data[$i] .= '<span class="'.$myclass.'" style="font-size: 7.5pt;">' . ui_print_truncate_text (io_safe_output($event["evento"]), 160) . '</span>';
		$data[$i] .= '</a></span>';
		$i++;
		
	}	
	
	if (in_array('id_agente', $show_fields)) {
		$data[$i] = '<span style="color: #000000">';
		
		if ($event["id_agente"] > 0) {
			// Agent name
			$data[$i] .= ui_print_agent_name ($event["id_agente"], true);
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
	
	$user_name = db_get_value('fullname', 'tusuario', 'id_user', $event['id_usuario']);
	if (in_array('id_usuario',$show_fields)) {
		$data[$i] = $user_name;
		$i++;
	}
	
	if (in_array('id_grupo',$show_fields)) {
		$id_group = $event["id_grupo"];
		$group_name = db_get_value('nombre', 'tgrupo', 'id_grupo', $id_group);
		if ($id_group == 0) {
			$group_name = __('All');
		}
		$data[$i] = $group_name;
		$i++;
	}
	
	if (in_array('event_type',$show_fields)) {
		$data[$i] = events_print_type_description($event["event_type"], true);
		$i++;
	}
	
	if (in_array('id_agentmodule',$show_fields)) {
		$data[$i] = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$event["id_agente"].'&amp;tab=data">'
					.db_get_value('nombre', 'tagente_modulo', 'id_agente_modulo', $event["id_agentmodule"]).'</a>';
		$i++;
	}
	
	if (in_array('id_alert_am',$show_fields)) {
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
			foreach ($tag_array as $tag_element){
				$blank_char_pos = strpos($tag_element, ' ');
				$tag_name = substr($tag_element, 0, $blank_char_pos);
				$tag_url = substr($tag_element, $blank_char_pos + 1);
				$data[$i] .= ' ' .$tag_name;
				if (!empty($tag_url)){
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
	
	if ($i != 0) {
		//Actions
		$data[$i] = '';
		// Validate event
		if (($event["estado"] != 1) and (check_acl ($config["id_user"], $event["id_grupo"], "IW") == 1)) {
			$data[$i] .= '<a href="javascript: toggleCommentForm(' . $event['id_evento'] . ')" id="validate-'.$event["id_evento"].'">';
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
	
		$data[$i] .= '<a href="javascript: toggleVisibleExtendedInfo(' . $event["id_evento"] . ');">';
		$data[$i] .= html_print_image ("images/eye.png", true,
				array ("title" => __('Show more')));	
		$data[$i] .= '</a>&nbsp;';
	
		// Create incident from this event
		if (check_acl ($config["id_user"], $event["id_grupo"], "IW") == 1) {
			if(isset($config['integria_enabled']) && $config['integria_enabled'] == 1) {
				$incidents_path = 'integria_incidents/incident&amp;tab=editor';
			}
			else {
				$incidents_path = 'incidents/incident_detail&amp;insert_form';
			}
			$data[$i] .= '<a href="index.php?sec=incidencias&amp;sec2=operation/'.$incidents_path.'&amp;from_event='.$event["id_evento"].'">';
			$data[$i] .= html_print_image ("images/page_lightning.png", true,
				array ("title" => __('Create incident from event')));
			$data[$i] .= '</a>';
		}
		$i++;
	
		if (check_acl ($config["id_user"], $event["id_grupo"], "IW") == 1) {
			//Checkbox
			$data[$i] = html_print_checkbox_extended ("eventid[]", $event["id_evento"], false, false, false, 'class="chk"', true);
		}
		array_push ($table->data, $data);
	}
	
	//Hiden row with description form
	$string = '';
	$string .= '<table border="0" style="width:80%; margin-left: 10%;"><tr><td align="left" valign="top" width="30px">';
	$string .=  '<td align="right"><b>' . __('Comment:') . '</b></td>';
	$string .=  '<td align="left" width="450px"><b>' . html_print_textarea("comment_".$event["id_evento"], 2, 10, '', 'style="min-height: 10px; width: 250px;"', true) . '</b></td>';
	$string .= '<td align="left" width="200px">'; 
	$string .= '<div style="text-align:center;">';
		
	if($event["estado"] == 0) {
		$string .= html_print_select(array('1' => __('Validate'), '2' => __('Set in process'), '3' => __('Add comment')), 'select_validate_'.$event["id_evento"], '', '', '', 0, true, false, false, 'select_validate').'<br><br>';
	}
	if($event["estado"] == 2) {
		$string .= html_print_select(array('1' => __('Validate'), '3' => __('Add comment')), 'select_validate_'.$event["id_evento"], '', '', '', 0, true, false, false, 'select_validate').'<br><br>';
	}
		
	$string .= '<a class="validate_event" href="javascript: toggleCommentForm(' . $event['id_evento'] . ')" id="validate-'.$event["id_evento"].'">';
		
	$string .= html_print_button (__('Update'), 'validate', false, '', 'class="sub ok validate_event" id="validate-'.$event["id_evento"].'"', true).'</div>';
	$string .= '</a>';
	$string .= '</td><td width="400px">';
	if($event["id_alert_am"] != 0) {
		$string .= '<div id="standby_alert_checkbox_' . $event['id_evento']. '" class="standby_alert_checkbox" style="display: none">'.__('Set alert on standby').'<br>'.html_print_checkbox('standby-alert-'.$event["id_evento"], 'ff2', false, true).'</div>';
	}
	$string .= '</td></tr></table>';	
	
	$data = array($string);
	
	$idx++;
	
	$table->rowclass[$idx] = 'event_form_' . $event["id_evento"].' event_form';
	$table->colspan[$idx][0] = 10;
	$table->rowstyle[$idx] = 'display: none;';
	array_push ($table->data, $data);

	//Hiden row with extended description
	$string = '<table width="99%" style="border:solid 1px #D3D3D3;" class="toggle" cellpadding="6"><tr>';
	$string .= '<td align="left" valign="top" width="25%" border="solid 1px">';
	$string .= '<b>' . __('Event ID') . '</b></td><td align="left">';
	$string .= io_safe_output($event["id_evento"]);
	$string .= '</td></tr><tr class="rowOdd">';	

	$string .= '<td align="left" valign="top" width="25%" border="solid 1px">';
	$string .= '<b>' . __('Event name') . '</b></td><td align="left">';
	$string .= io_safe_output($event["evento"]);
	$string .= '</td></tr><tr>';
	$string .= '<td align="left" valign="top" width="15%">';
	$string .= '<b>' . __('Severity') . '</b></td><td align="left">';
	$string .= html_print_image ($img_sev, true, 
		array ("class" => "image_status",
			"width" => 12,
			"height" => 12,
			"title" => get_priority_name ($event["criticity"])));
	$string .= ' '.get_priority_name ($event["criticity"]);
	$string .= '</td></tr><tr  style="border-left: solid 1px; #D3D3D3;" class="rowOdd">';
	$string .= '<td align="left" valign="top" width="15%">';
	$string .= '<b>' . __('Type') . '</b></td><td align="left">';
	$string .= events_print_type_img ($event["event_type"], true).' '.events_print_type_description($event["event_type"], true);
	$string .= '</td></tr><tr>';
	$string .= '<td align="left" valign="top" width="15%">';
	$string .= '<b>' . __('Status') . '</b></td><td id="status_row_' . $event["id_evento"] . '" align="left">';
	$string .= $title_st;
	$string .= '</td></tr><tr  style="border-left: solid 1px; #D3D3D3;" class="rowOdd">';
	$string .= '<td align="left" valign="top" width="15%">';
	$string .= '<b>' . __('Timestamp') . '</b></td><td align="left">';
	if ($group_rep == 1) {
		$string .= date ($config["date_format"], $event['timestamp_rep']);
	}
	else {
		$string .= date ($config["date_format"], strtotime($event["timestamp"]));
	}
	$string .= '</td></tr><tr>';

	$odd = 'rowOdd';
	
	$string .= '<td align="left" valign="top" width="15%">';
	$string .= '<b>' . __('Agent name') . '</b></td><td align="left">';
	if ($event["id_agente"] != 0) {
		$string .= ui_print_agent_name ($event["id_agente"], true);
	} else {
		$string .= '<i>- ' . __('Empty') . ' -</i>';
	}
	$string .= '</td></tr><tr class="'. $odd .'">';

	$odd = ($odd == '')? 'rowOdd' : '';
	
	if ($event["id_agentmodule"] != 0) {
		$string .= '<td align="left" valign="top" width="15%">';
		$string .= '<b>' . __('Agent module') . '</b></td><td align="left">';
		$string .= '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$event["id_agente"].'&amp;tab=data">';
		$string .= db_get_value('nombre', 'tagente_modulo', 'id_agente_modulo', $event["id_agentmodule"]);
		$string .= '</a></td></tr><tr class="' . $odd . '">';
		
		$odd = ($odd == '')? 'rowOdd' : '';
		// Module group
		$string .= '<td align="left" valign="top" width="15%">';
		$string .= '<b>' . __('Module group') . '</b></td><td align="left">';
		$id_module_group = db_get_value('id_module_group', 'tagente_modulo', 'id_agente_modulo', $event["id_agentmodule"]);
		$module_group = db_get_value('name', 'tmodule_group', 'id_mg', $id_module_group);
		$string .= '<a href="index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;status=-1&amp;modulegroup=' . $id_module_group . '">';
		$string .= $module_group;
		$string .= '</a></td></tr><tr class="' . $odd . '">';
		
		$odd = ($odd == '')? 'rowOdd' : '';
	}
	else {
		$string .= '<td align="left" valign="top" width="15%">';
		$string .= '<b>' . __('Agent module') . '</b></td><td align="left">';
		$string .= '<i>- ' . __('Empty') . ' -</i>';
		$string .= '</a></td></tr><tr class="' . $odd . '">';
		$odd = ($odd == '')? 'rowOdd' : '';
		// Module group
		$string .= '<td align="left" valign="top" width="15%">';
		$string .= '<b>' . __('Module group') . '</b></td><td align="left">';
		$string .= '<i>- ' . __('Empty') . ' -</i>';
		$string .= '</a></td></tr><tr class="' . $odd . '">';
		$odd = ($odd == '')? 'rowOdd' : '';
	}
	
	$string .= '<td align="left" valign="top" width="15%">';
	$string .= '<b>' . __('Alert source') . '</b></td><td align="left">';
	if ($event["id_alert_am"] != 0) {
		$string .= '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$event["id_agente"].'&amp;tab=alert">';
		$standby = db_get_value('standby', 'talert_template_modules', 'id', $event["id_alert_am"]);
		if(!$standby) {
			$string .= html_print_image ("images/bell.png", true,
				array ("title" => __('Go to data overview')));
		}
		else {
			$string .= html_print_image ("images/bell_pause.png", true,
				array ("title" => __('Go to data overview')));
		}
		
		$sql = 'SELECT name
			FROM talert_templates
			WHERE id IN (SELECT id_alert_template
					FROM talert_template_modules
					WHERE id = ' . $event["id_alert_am"] . ');';
		
		$templateName = db_get_sql($sql);
		
		$string .= $templateName;
		
		$string .= '</a></td></tr><tr class="' . $odd . '">';
		
		$odd = ($odd == '')? 'rowOdd' : '';
		
	} else {
		$string .= '<i>- ' . __('Empty') . ' -</i>';
		$string .= '</a></td></tr><tr class="' . $odd . '">';
		$odd = ($odd == '')? 'rowOdd' : '';
	}
	
	$string .= '<td align="left" valign="top" width="15%">';
	$string .= '<b>' . __('Group') . '</b></td><td align="left">';
	$string .= ui_print_group_icon ($event["id_grupo"], true);
	$string .= groups_get_name ($event["id_grupo"]);
	$string .= '</td></tr><tr class="' . $odd . '">';
	$odd = ($odd == '')? 'rowOdd' : '';
	
	if ($group_rep != 0) {
		$string .= '<td align="left" valign="top" width="15%">';
		$string .= '<b>' . __('Count') . '</b></td><td id="count_event_group_' . $event["id_evento"] . '" align="left">';
	}
	
	if ($group_rep == 1) {
		$string .= $event["event_rep"];
		$string .= '</td></tr><tr class="' . $odd . '">';
		$odd = ($odd == '')? 'rowOdd' : '';
	}

	$string .= '</td></tr>';
	$odd = ($odd == '')? 'rowOdd' : '';
	$string .= '<tr class="' . $odd . '"><td align="left" valign="top">' . '<b>' . __('Comments') . '</td><td id="comment_row_' . $event['id_evento'] . '" align="left">';
	if($event["user_comment"] != '') {
		$string .= $event["user_comment"];
	} else {
		$string .= '<i>- ' . __('Empty') . ' -</i>';
	}
	$string .= '</td></tr>';
	$odd = ($odd == '')? 'rowOdd' : '';
	$string .= '<tr class="' . $odd . '"><td align="left" valign="top">' . '<b>' . __('Tags') . '</td><td align="left">';
	if ($event["tags"] != '') {
		$tag_array = explode(',', $event["tags"]);
		foreach ($tag_array as $tag_element){
			$blank_char_pos = strpos($tag_element, ' ');
			$tag_name = substr($tag_element, 0, $blank_char_pos);
			$tag_url = substr($tag_element, $blank_char_pos + 1);
			$string .= ' ' .$tag_name;
			if (!empty($tag_url)){
				$string .= ' <a href="javascript: openURLTagWindow(\'' . $tag_url . '\');">' . html_print_image('images/lupa.png', true, array('title' => __('Click here to open a popup window with URL tag'))) . '</a> ';
			}
			$string .= ',';
		}
		$string = rtrim($string, ',');
		$odd = ($odd == '')? 'rowOdd' : '';
	}
	else {
		$string .= '<i>- ' . __('Empty') . ' -</i>';
		$odd = ($odd == '')? 'rowOdd' : '';
	}

	$string .= '<tr class="' . $odd . '"><td align="left" valign="top">' . '<b>' . __('Source') . '</td><td align="left">';
	if ($event["source"] != '') {
		$string .= $event["source"];
		$string .= '</td></tr><tr>';
		$odd = ($odd == '')? 'rowOdd' : '';
	} else {
		$string .= '<i>- ' . __('Empty') . ' -</i>';
		$odd = ($odd == '')? 'rowOdd' : '';
	}
	
	$string .= '<tr class="' . $odd . '"><td align="left" valign="top">' . '<b>' . __('Extra id') . '</td><td align="left">';
	if ($event["id_extra"] != '') {
		$string .= $event["id_extra"];
		$string .= '</td></tr><tr>';
		$odd = ($odd == '')? 'rowOdd' : '';
	} else {
		$string .= '<i>- ' . __('Empty') . ' -</i>';
	}
	$string .= '</td></tr>';
	
	$string .= '<tr class="' . $odd . '"><td align="left" valign="top">' . '<b>' . __('User name') . '</td><td align="left">';
	if (($event["id_usuario"]!= '') || ($event["id_usuario"]!= 0)){
		$string .= $user_name;
	} 
	else {
		$string .= '<i>- ' . __('Empty') . ' -</i>';
	} 
	
	$string .= '</td></tr><tr>';
	$string .= '</table>';
	
	$data = array($string);
	
	$idx++;
	
	$table->rowclass[$idx] = 'event_info_' . $event["id_evento"].' event_info';
	$table->colspan[$idx][0] = 10;
	$table->rowstyle[$idx] = 'display: none;';
	array_push ($table->data, $data);
	
	$idx++;
}

echo '<div id="events_list">';
if (!empty ($table->data)) {
	ui_pagination ($total_events, $url."&pure=".$config["pure"], $offset, $pagination);
	
	echo '<form method="post" id="form_events" action="'.$url.'&amp;section=validate">';
	echo "<input type='hidden' name='delete' id='hidden_delete_events' value='0' />";
	
	html_print_table ($table);
	
	echo '<div style="width:'.$table->width.';" class="action-buttons">';
	if (check_acl ($config["id_user"], 0, "IW") == 1) {
		html_print_submit_button (__('Update'), 'validate_btn', false, 'class="sub ok"');
	}
	if (check_acl ($config["id_user"], 0,"IM") == 1) {
		html_print_button(__('Delete'), 'delete_button', false, 'submit_delete();', 'class="sub delete"');
		?>
		<script type="text/javascript">
		function submit_delete() {
			$("#hidden_delete_events").val(1);
			$("#form_events").submit();
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

?>

<script language="javascript" type="text/javascript">
/* 
 <![CDATA[ */
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
		if ( $("#filter_id").val() == 0 ){
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
			jQuery.post ("ajax.php",
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
					  if (i == 'tag')
						$("#tag").val(val);	
					  if (i == 'filter_only_alert')
						$("#filter_only_alert").val(val);
					  if (i == 'id_group_filter')
						$("#id_group").val(val);
				  });
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
			$('#show_filter_error').html('<h3 class="error"> <?php echo __('Define name and group for the filter and click on Save filter again'); ?> </h3>');
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
			
			jQuery.post ("ajax.php",
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
				"tag" : $("#tag").val(),
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
			jQuery.post ("ajax.php",
							{"page" : "operation/events/events_list",
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
			$('#show_filter_error').html('<h3 class="error"> <?php echo __('Filter name cannot be left blank'); ?> </h3>');
			return false;
		}		
		
		var id_filter_update =  $("#filter_id").val();
		
		jQuery.post ("ajax.php",
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
			"tag" : $("#tag").val(),
			"filter_only_alert" : $("#filter_only_alert").val(),
			"id_group_filter": $("#id_group").val()
			},
			function (data) {
				if (data == 'ok'){
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
			jQuery.post ("ajax.php",
							{"page" : "operation/events/events_list",
							"get_event_filters" : 1
						},
						function (data) {
							jQuery.each (data, function (i, val) {
								  s = js_html_entity_decode(val);
								  if (i == id_filter_update){
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
				url: action="ajax.php",
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
				url: action="ajax.php",
				async: false,
				timeout: 10000,
				success: function (data) {
					$("#toggle_arrow").attr('src', data);
				}
			});
		}
	});
		
});
/* ]]> */
</script>

