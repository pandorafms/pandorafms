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

check_login ();

if (! give_acl ($config["id_user"], 0, "IR")) {
	audit_db ($config["id_user"], $_SERVER['REMOTE_ADDR'], "ACL Violation",
		"Trying to access event viewer");
	require ("general/noaccess.php");
	return;
}

if($id_agent == -2) {
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
			$id_agent = get_agent_id($text_agent);
			break;
	}
}
else{
	switch ($id_agent)
	{
		case -1:
			$text_agent = __('All');
			break;
		case 0:
			$text_agent = __('Server');
			break;
		default:
			$text_agent = get_agent_name($id_agent);
			break;
	}
}

//Group selection
if ($ev_group > 0 && in_array ($ev_group, array_keys ($groups))) {
	//If a group is selected and it's in the groups allowed
	$sql_post = " AND id_grupo = $ev_group";
} else {
	if (is_user_admin ($config["id_user"])) {
		//Do nothing if you're admin, you get full access
		$sql_post = "";
	} else {
		//Otherwise select all groups the user has rights to.
		$sql_post = " AND id_grupo IN (".implode (",", array_keys ($groups)).")";
	}
}

// Skip system messages if user is not PM
if (!give_acl ($config["id_user"], 0, "PM")) {
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
	$sql_post .= " AND evento LIKE '%".$search."%'";
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
	$sql_post .= " AND criticity >= ".$severity;
if ($id_agent != -1)
	$sql_post .= " AND id_agente = ".$id_agent;
if ($id_event != -1)
	$sql_post .= " AND id_evento = ".$id_event;

if ($id_user_ack != "0")
	$sql_post .= " AND id_usuario = '".$id_user_ack."'";


if ($event_view_hr > 0) {
	$unixtime = get_system_time () - ($event_view_hr * 3600); //Put hours in seconds
	$sql_post .= " AND utimestamp > ".$unixtime;
}

$url = "index.php?sec=eventos&amp;sec2=operation/events/events&amp;search=" .
	rawurlencode($search) . "&amp;event_type=" . $event_type .
	"&amp;severity=" . $severity . "&amp;status=" . $status . "&amp;ev_group=" .
	$ev_group . "&amp;refr=" . $config["refr"] . "&amp;id_agent=" .
	$id_agent . "&amp;id_event=" . $id_event . "&amp;pagination=" .
	$pagination . "&amp;group_rep=" . $group_rep . "&amp;event_view_hr=" .
	$event_view_hr . "&amp;id_user_ack=" . $id_user_ack . "&amp;offset=" . $offset;

//Link to toggle filter
echo '<a href="#" id="tgl_event_control"><b>'.__('Event control filter').'</b>&nbsp;'.print_image ("images/down.png", true, array ("title" => __('Toggle filter(s)'))).'</a><br><br>';

//Start div
echo '<div id="event_control" style="display:none">';

// Table for filter controls
echo '<form method="post" action="index.php?sec=eventos&amp;sec2=operation/events/events&amp;refr='.$config["refr"].'&amp;pure='.$config["pure"].'&amp;section=list">';
echo '<table style="float:left;" width="550" cellpadding="4" cellspacing="4" class="databox"><tr>';

// Group combo
echo "<td>".__('Group')."</td><td>";
print_select_groups($config["id_user"], "IR", true, 'ev_group', $ev_group, '', '', 0, false, false, false, 'w130');
echo "</td>";

// Event type
echo "<td>".__('Event type')."</td><td>";
$types = get_event_types ();
// Expand standard array to add not_normal (not exist in the array, used only for searches)
$types["not_normal"] = __("Not normal");
print_select ($types, 'event_type', $event_type, '', __('All'), '');


echo "</td></tr><tr>";

// Severity
echo "<td>".__('Severity')."</td><td>";
print_select (get_priorities (), "severity", $severity, '', __('All'), '-1');
echo '</td>';

// Status
echo "<td>".__('Event status')."</td><td>";
$fields = array ();
$fields[-1] = __('All event');
$fields[0] = __('Only new');
$fields[1] = __('Only validated');
$fields[2] = __('Only in process');
$fields[3] = __('Only not validated');

print_select ($fields, 'status', $status, '', '', '');

//NEW LINE
echo "</td></tr><tr>";

// Free search
echo "<td>".__('Free search')."</td><td>";
print_input_text ('search', $search, '', 15);
echo '</td>';

//Agent search
echo "<td>".__('Agent search')."</td><td>";
print_input_text_extended ('text_agent', $text_agent, 'text_id_agent', '', 30, 100, false, '',
array('style' => 'background: url(images/lightning.png) no-repeat right;'))
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
print_select ($lpagination, "pagination", $pagination, '', __('Default'), $config["block_size"]);
echo "</td>";

echo "<td>".__('Max. hours old')."</td>";
echo "<td>";
print_input_text ('event_view_hr', $event_view_hr, '', 5);
echo "</td>";


echo "</tr><tr>";
echo "<td>".__('User ack.')."</td>";
echo "<td>";
$users = get_users_info ();
print_select ($users, "id_user_ack", $id_user_ack, '', __('Any'), 0);
echo "</td>";

echo "<td>";
echo __("Repeated");
echo "</td><td>";

$repeated_sel[0] = __("All events");
$repeated_sel[1] = __("Group events");
print_select ($repeated_sel, "group_rep", $group_rep, '');
echo "</td></tr>";

echo '<tr><td colspan="4" style="text-align:right">';
//The buttons
print_submit_button (__('Update'), '', false, 'class="sub upd"');

echo "</td></tr></table></form>"; //This is the filter div
echo '<div style="width:220px; float:left;">';
if ($config['flash_charts']) {
	echo grafico_eventos_grupo (220, 180, rawurlencode ($sql_post));
} else {
	print_image ("include/fgraph.php?tipo=group_events&width=220&height=180&url=".rawurlencode ($sql_post), false, array ("border" => 0));
}
echo '</div>';
echo '<div id="steps_clean">&nbsp;</div>';
echo '</div>';

if ($group_rep == 0) {
	$sql = "SELECT * FROM tevento WHERE 1=1 ".$sql_post." ORDER BY utimestamp DESC LIMIT ".$offset.",".$pagination;
} else {
	$sql = "SELECT *, COUNT(*) AS event_rep, MAX(utimestamp) AS timestamp_rep FROM tevento WHERE 1=1 ".$sql_post." GROUP BY evento, id_agentmodule ORDER BY timestamp_rep DESC LIMIT ".$offset.",".$pagination;
}

//Extract the events by filter (or not) from db
$result = get_db_all_rows_sql ($sql);

if ($group_rep == 0) {
	$sql = "SELECT COUNT(id_evento) FROM tevento WHERE 1=1 ".$sql_post;
} else {
	$sql = "SELECT COUNT(DISTINCT(evento)) FROM tevento WHERE 1=1 ".$sql_post;
}



//Count the events with this filter (TODO but not utimestamp).
$total_events = (int) get_db_sql ($sql);

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

$table->head[0] = __('Status');
$table->align[0] = 'center';

$table->head[1] = __('Event name');

$table->head[2] = __('Agent name');
$table->align[2] = 'center';

$table->head[3] = __('Timestamp');
$table->align[3] = 'center';

$table->head[4] = __('Action');
$table->align[4] = 'center';
$table->size[4] = '80px';

$table->head[5] = print_checkbox ("allbox", "1", false, true);
$table->align[5] = 'center';

$idx = 0;
//Arrange data. We already did ACL's in the query
foreach ($result as $event) {
	$data = array ();
	
	//First pass along the class of this row
	$table->rowclass[] = get_priority_class ($event["criticity"]);
	
	// Colored box
	switch($event["estado"]) {
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

	$data[0] = print_image ($img_st, true, 
		array ("class" => "image_status",
			"width" => 16,
			"height" => 16,
			"title" => $title_st,
			"id" => 'status_img_'.$event["id_evento"]));
	
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
	
	// Event description
	$data[1] = '<span title="'.$event["evento"].'" class="f9">';
	$data[1] .= '<a href="'.$url.'&amp;group_rep=0&amp;pure='.$config["pure"].'&amp;search='.rawurlencode ($event["evento"]).'">';
	if (strlen ($event["evento"]) > 50) {
		$data[1] .= mb_substr ($event["evento"], 0, 50)."...";
	}
	else {
		$data[1] .= $event["evento"];
	}
	$data[1] .= '</a></span>';

	if ($event["event_type"] == "system") {
		$data[2] = __('System');
	}
	elseif ($event["id_agente"] > 0) {
		// Agent name
		$data[2] = print_agent_name ($event["id_agente"], true);
	}
	else {
		$data[2] = __('Alert').__('SNMP');
	}
	
	//Time
	if ($group_rep == 1) {
		$data[3] = print_timestamp ($event['timestamp_rep'], true);
	}
	else {
		$data[3] = print_timestamp ($event["timestamp"], true);
	}
	
	//Actions
	$data[4] = '';
	// Validate event
	if (($event["estado"] != 1) and (give_acl ($config["id_user"], $event["id_grupo"], "IW") == 1)) {
		$data[4] .= '<a href="javascript: toggleCommentForm(' . $event['id_evento'] . ')" id="validate-'.$event["id_evento"].'">';
		$data[4] .= print_image ("images/ok.png", true,
			array ("title" => __('Validate event')));
		$data[4] .= '</a>&nbsp;';
	}else {
		$data[4] .= print_image ("images/tick.png", true,
			array ("title" => __('Event validated'))).'&nbsp;';
	}
	// Delete event
	if (give_acl ($config["id_user"], $event["id_grupo"], "IM") == 1) {
		if($event['estado'] != 2) {
			$data[4] .= '<a class="delete_event" href="#"  onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;" id="delete-'.$event['id_evento'].'">';
			$data[4] .= print_image ("images/cross.png", true,
				array ("title" => __('Delete event')));
			$data[4] .= '</a>&nbsp;';
		}else {
			$data[4] .= print_image ("images/cross.disabled.png", true,
				array ("title" => __('Is not allowed delete events in process'))).'&nbsp;';
		}
	}
	
	$data[4] .= '<a href="javascript: toggleVisibleExtendedInfo(' . $event["id_evento"] . ');">';
	$data[4] .= print_image ("images/eye.png", true,
			array ("title" => __('Show more')));	
	$data[4] .= '</a>&nbsp;';
	
	// Create incident from this event
	if (give_acl ($config["id_user"], $event["id_grupo"], "IW") == 1) {
		$data[4] .= '<a href="index.php?sec=incidencias&amp;sec2=operation/incidents/incident_detail&amp;insert_form&amp;from_event='.$event["id_evento"].'">';
		$data[4] .= print_image ("images/page_lightning.png", true,
			array ("title" => __('Create incident from event')));
		$data[4] .= '</a>';
	}
	
	//Checkbox
	if($event["estado"] != 1) {
		$data[5] = print_checkbox_extended ("eventid[]", $event["id_evento"], false, false, false, 'class="chk"', true);
	}
	else {
		$data[5] = '';
	}		
	array_push ($table->data, $data);

	//Hiden row with description form
	$string = '<form method="post" action="'.$url.'&amp;section=list">';
	$string .= '<table border="0" style="width:80%; margin-left: 10%;"><tr><td align="left" valign="top" width="30px">';
	$string .=  '<td align="right"><b>' . __('Comment:') . '</b></td>';
	$string .=  '<td align="left" width="450px"><b>' . print_textarea("comment_".$event["id_evento"], 2, 10, '', 'style="min-height: 10px; width: 250px;"', true) . '</b></td>';
	$string .= '<td align="left" width="200px">'; 
	$string .= '<div style="text-align:center;">';
	if($event["estado"] == 0) {
		$string .= print_select(array('1' => __('Validate'), '2' => __('Set in process')), 'select_validate_'.$event["id_evento"], '', '', '', 0, true, false, false, 'select_validate').'<br><br>';
	}
	$string .= '<a class="validate_event" href="javascript: toggleCommentForm(' . $event['id_evento'] . ')" id="validate-'.$event["id_evento"].'">';
	if($event["estado"] == 2) {
		$string .= print_button (__('Validate'), 'validate', false, '', 'class="sub ok validate_event" id="validate-'.$event["id_evento"].'"', true).'</div>';
	}else {
		$string .= print_button (__('Change status'), 'validate', false, '', 'class="sub ok validate_event" id="validate-'.$event["id_evento"].'"', true).'</div>';
	}
	$string .= '</a>';
	$string .= '</td><td width="400px">';
	if($event["id_alert_am"] != 0) {
		$string .= '<div class="standby_alert_checkbox" style="display: none">'.__('Set alert on standby').'<br>'.print_checkbox('standby-alert-'.$event["id_evento"], 'ff2', false, true).'</div>';
	}
	$string .= '</td></tr></table></form>';	
	
	$data = array($string);
	
	$idx++;
	
	$table->rowclass[$idx] = 'event_form_' . $event["id_evento"].' event_form';
	$table->colspan[$idx][0] = 10;
	$table->rowstyle[$idx] = 'display: none;';
	array_push ($table->data, $data);
	
	//Hiden row with extended description
	$string = '<table border="0" width="90%"><tr>';
	$string .= '<td align="left" valign="top" width="25%">';
	$string .= '<b>' . __('Event name') . ':</b></td><td align="left">';
	$string .= $event["evento"];
	$string .= '</td></tr><tr>';
	$string .= '<td align="left" valign="top" width="15%">';
	$string .= '<b>' . __('Severity') . ':</b></td><td align="left">';
	$string .= print_image ($img_sev, true, 
		array ("class" => "image_status",
			"width" => 12,
			"height" => 12,
			"title" => get_priority_name ($event["criticity"])));
	$string .= ' '.get_priority_name ($event["criticity"]);
	$string .= '</td></tr><tr>';
	$string .= '<td align="left" valign="top" width="15%">';
	$string .= '<b>' . __('Type') . ':</b></td><td align="left">';
	$string .= print_event_type_img ($event["event_type"], true).' '.print_event_type_description($event["event_type"], true);
	$string .= '</td></tr><tr>';
	$string .= '<td align="left" valign="top" width="15%">';
	$string .= '<b>' . __('Agent name') . ':</b></td><td align="left">';
	$string .= print_agent_name ($event["id_agente"], true);
	$string .= '</td></tr><tr>';
	
	
	if ($event["id_agentmodule"] != 0) {
		$string .= '<td align="left" valign="top" width="15%">';
		$string .= '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$event["id_agente"].'&amp;tab=data">';
		$string .= '<b>' . __('Agent module source') . ':</b></td><td align="left">';
		$string .= print_image ("images/bricks.png", true,
			array ("title" => __('Go to data overview')));
		$string .= '</a></td></tr><tr>';
	}
	
	if ($event["id_alert_am"] != 0) {
		$string .= '<td align="left" valign="top" width="15%">';
		$string .= '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$event["id_agente"].'&amp;tab=alert">';
		$string .= '<b>' . __('Alert source') . ':</b></td><td align="left">';
		$standby = get_db_value('standby', 'talert_template_modules', 'id', $event["id_alert_am"]);
		if(!$standby) {
			$string .= print_image ("images/bell.png", true,
				array ("title" => __('Go to data overview')));
		}else {
			$string .= print_image ("images/bell_pause.png", true,
				array ("title" => __('Go to data overview')));
		}
		
		$string .= '</a></td></tr><tr>';
	}
	
	$string .= '<td align="left" valign="top" width="15%">';
	$string .= '<b>' . __('Group') . ':</b></td><td align="left">';
	$string .= print_group_icon ($event["id_grupo"], true);
	$string .= ' '. get_group_name ($event["id_grupo"], true);
	$string .= '</td></tr><tr>';
	$string .= '<td align="left" valign="top" width="15%">';
	if ($group_rep == 0) {
		$string .= '<b>' . __('User ID') . ':</b></td><td align="left">';
	} else {
		$string .= '<b>' . __('Count') . ':</b></td><td align="left">';
	}
	
	if ($group_rep == 1) {
		$string .= $event["event_rep"];
	}
	else {
		if (!empty ($event["estado"])) {
			if ($event["id_usuario"] != '0' && $event["id_usuario"] != '' && $event["id_usuario"] != 'system' && $event["id_usuario"] != "System"){
				$string .= '<a href="index.php?sec=usuarios&sec2=operation/users/user_edit&id='.$event["id_usuario"].'" title="'.get_user_fullname ($event["id_usuario"]).'">'.mb_substr ($event["id_usuario"],0,8).'</a>';
			}
			else {
				$string .= __('System');
			}
		}
		else {
			$string .= '<i>- ' . __('Empty') . ' -</i>';
		}
	}
	$string .= '</td></tr>';
	$string .= '<tr><td align="left" valign="top">' . '<b>' . __('Comments') . ':</td><td align="left">';
	if($event["user_comment"] != '') {
		$string .= $event["user_comment"];
	} else {
		$string .= '<i>- ' . __('Empty') . ' -</i>';
	}
	
	$string .= '</td></tr>';
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
	pagination ($total_events, $url."&pure=".$config["pure"], $offset, $pagination);
	
	echo '<form method="post" action="'.$url.'&amp;section=validate">';

	print_table ($table);

	echo '<div style="width:'.$table->width.';" class="action-buttons">';
	if (give_acl ($config["id_user"], 0, "IW") == 1) {
		print_submit_button (__('Change status'), 'validate_btn', false, 'class="sub ok"');
	}
	if (give_acl ($config["id_user"], 0,"IM") == 1) {
		print_submit_button (__('Delete'), 'delete', false, 'class="sub delete"');
	}
	echo '</div></form>';

}
else {
	echo '<div class="nf">'.__('No events').'</div>';
}
echo '</div>';

unset ($table);

?>
