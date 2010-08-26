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

check_login ();

if (! give_acl ($config["id_user"], 0, "IR")) {
	audit_db ($config["id_user"], $_SERVER['REMOTE_ADDR'], "ACL Violation",
		"Trying to access event viewer");
	require ("general/noaccess.php");
	return;
}

if (is_ajax ()) {
	$get_event_tooltip = (bool) get_parameter ('get_event_tooltip');
	$validate_event = (bool) get_parameter ('validate_event');
	$delete_event = (bool) get_parameter ('delete_event');
	
	if ($get_event_tooltip) {
		$id = (int) get_parameter ('id');
		$event = get_event ($id);
		if ($event === false)
			return;
		
		echo '<h3>'.__('Event').'</h3>';
		echo '<strong>'.__('Type').': </strong><br />';
		
		print_event_type_img ($event["event_type"]);
		echo ' ';
		if ($event["event_type"] == "system") {
			echo __('System');
		} elseif ($event["id_agente"] > 0) {
			// Agent name
			echo get_agent_name ($event["id_agente"]);
		} else {
			echo __('Alert').__('SNMP');
		}
		echo '<br />';
		echo '<strong>'.__('Timestamp').': </strong><br />';
		print_timestamp ($event['utimestamp']);
		
		echo '<br />';
		echo '<strong>'.__('Description').': </strong><br />';
		echo $event['evento'];
		
		return;
	}
	
	if ($validate_event) {
		$id = (int) get_parameter ("id");
		$similars = (bool) get_parameter ('similars');
		
		$return = validate_event ($id, $similars);
		if ($return)
			echo 'ok';
		else
			echo 'error';
		return;
	}
	
	if ($delete_event) {
		$id = (array) get_parameter ("id");
		$similars = (bool) get_parameter ('similars');
		
		$return = delete_event ($id, $similars);
		if ($return)
			echo 'ok';
		else
			echo 'error';
		return;
	}
	
	return;
}

if ($config['flash_charts']) {
	require_once ("include/fgraph.php");
}

$offset = (int) get_parameter ("offset", 0);
$ev_group = (int) get_parameter ("ev_group", 0); //0 = all
$search = preg_replace ("/&([A-Za-z]{0,4}\w{2,3};|#[0-9]{2,3};)/", "%", rawurldecode (get_parameter ("search")));
$event_type = get_parameter ("event_type", ''); // 0 all
$severity = (int) get_parameter ("severity", -1); // -1 all
$status = (int) get_parameter ("status", 0); // -1 all, 0 only red, 1 only green
$id_agent = (int) get_parameter ("id_agent", -2); //-1 all, 0 system

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

$id_event = (int) get_parameter ("id_event", -1);
$pagination = (int) get_parameter ("pagination", $config["block_size"]);
$event_view_hr = (int) get_parameter ("event_view_hr", $config["event_view_hr"]);
$id_user_ack = get_parameter ("id_user_ack", 0);
$group_rep = (int) get_parameter ("group_rep", 0);
$delete = (bool) get_parameter ("delete");
$validate = (bool) get_parameter ("validate");
$groups = get_user_groups ($config["id_user"], "IR");

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

if ($status == 1) {
	$sql_post .= " AND estado = 1";
} elseif ($status == 0) {
	$sql_post .= " AND estado = 0";
}

if ($search != "")
	$sql_post .= " AND evento LIKE '%".$search."%'";

if ($event_type != ""){
	// If normal, warning, could be several (going_up_warning, going_down_warning... too complex 
	// for the user so for him is presented only "warning, critical and normal"
	if ($event_type == "warning" || $event_type == "critical" || $event_type == "normal"){
		$sql_post .= " AND event_type LIKE '%$event_type%' ";
	}
	elseif ($event_type == "not_normal"){
		$sql_post .= " AND event_type LIKE '%warning%' OR event_type LIKE '%critical%' OR event_type LIKE '%unknown%' ";
	}
	else
		$sql_post .= " AND event_type = '".$event_type."'";

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
	$event_view_hr . "&amp;id_user_ack=" . $id_user_ack;

// Header
if ($config["pure"] == 0) {
	$buttons = array(
	'fullscreen' => array('active' => false,
		'text' => '<a href="'.$url.'&amp;pure=1">' . 
			print_image("images/fullscreen.png", true, array ("title" => __('Full screen'))) .'</a>'),
	'rss' => array('active' => false,
		'text' => '<a href="operation/events/events_rss.php?ev_group='.$ev_group.'&amp;event_type='.$event_type.'&amp;search='.rawurlencode ($search).'&amp;severity='.$severity.'&amp;status='.$status.'&amp;event_view_hr='.$event_view_hr.'&amp;id_agent='.$id_agent.'">' . 
			print_image("images/rss.png", true, array ("title" => __('RSS Events'))) .'</a>'),
	'marquee' => array('active' => false,
		'text' => '<a href="operation/events/events_marquee.php">' . 
			print_image("images/heart.png", true, array ("title" => __('Marquee display'))) .'</a>'),
	'csv' => array('active' => false,
		'text' => '<a href="operation/events/export_csv.php?ev_group='.$ev_group.'&amp;event_type='.$event_type.'&amp;search='.rawurlencode ($search).'&amp;severity='.$severity.'&amp;status='.$status.'&amp;event_view_hr='.$event_view_hr.'&amp;id_agent='.$id_agent.'">' . 
			print_image("images/disk.png", true, array ("title" => __('Export to CSV file'))) .'</a>')
	);
	
	print_page_header (__("Events"), "images/lightning_go.png", false, "eventview", false,$buttons);
}
else {
	// Fullscreen
	echo "<h2>".__('Events')." &raquo; ".__('Main event view'). "&nbsp;";
	echo print_help_icon ("eventview", true);
	echo "&nbsp;";

	echo '<a target="_top" href="'.$url.'&amp;pure=0">';
	print_image ("images/fullscreen.png", false, array ("title" => __('Full screen')));
	echo '</a>';
	echo "</h2>";
}

//Process validation (pass array or single value)
if ($validate) {
	$ids = (array) get_parameter ("eventid", -1);
	
	if($ids[0] != -1){
		$return = validate_event ($ids, ($group_rep == 1));
		print_result_message ($return,
			__('Successfully validated'),
			__('Could not be validated'));
	}
}

//Process deletion (pass array or single value)
if ($delete) {
	$ids = (array) get_parameter ("eventid", -1);
		
	if($ids[0] != -1){
		$return = delete_event ($ids, ($group_rep == 1));
		print_result_message ($return,
			__('Successfully deleted'),
			__('Could not be deleted'));
	}
}

//Link to toggle filter
echo '<a href="#" id="tgl_event_control"><b>'.__('Event control filter').'</b>&nbsp;'.print_image ("images/down.png", true, array ("title" => __('Toggle filter(s)'))).'</a><br><br>';

//Start div
echo '<div id="event_control" style="display:none">';

// Table for filter controls
echo '<form method="post" action="index.php?sec=eventos&amp;sec2=operation/events/events&amp;refr='.$config["refr"].'&amp;pure='.$config["pure"].'">';
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
$fields[1] = __('Only validated');
$fields[0] = __('Only pending');

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

$table->head[0] = "<span title='" . __('Validate') . "'>" . __('V.') . "</span>";
$table->align[0] = 'center';

$table->head[1] = "<span title='" . __('Severity') . "'>" . __('S.') . "</span>";
$table->align[1] = 'center';

$table->head[2] ="<span title='" . __('Type') . "'>" . __('T.') . "</span>";
$table->headclass[2] = 'f9';
$table->align[2] = 'center';

$table->head[3] = __('Event name');

$table->head[4] = __('Agent name');
$table->align[4] = 'center';

$table->head[5] = __('S.');
$table->align[5] = 'center';

$table->head[6] = __('G.');
$table->align[6] = 'center';

if ($group_rep == 0) {
	$table->head[7] = __('User ID');
} else {
	$table->head[7] = __('Count');
}
$table->align[7] = 'center';

$table->head[8] = __('Timestamp');
$table->align[8] = 'center';

$table->head[9] = __('Action');
$table->align[9] = 'right';
$table->size[9] = '50px';

$table->head[10] = print_checkbox ("allbox", "1", false, true);
$table->align[10] = 'center';

//Arrange data. We already did ACL's in the query
foreach ($result as $event) {
	$data = array ();
	
	//First pass along the class of this row
	$table->rowclass[] = get_priority_class ($event["criticity"]);
	
	// Colored box
	if ($event["estado"] == 0) {
		$img = "images/tick_off.png";
		$title = __('Event not validated');
	}
	else {
		$img = "images/tick.png";
		$title = __('Event validated');
	}
	$data[0] = print_image ($img, true, 
		array ("class" => "image_status",
			"width" => 16,
			"height" => 16,
			"title" => $title));
	
	switch ($event["criticity"]) {
		default:
		case 0:
			$img = "images/status_sets/default/severity_maintenance.png";
			break;
		case 1:
			$img = "images/status_sets/default/severity_informational.png";
			break;
		case 2:
			$img = "images/status_sets/default/severity_normal.png";
			break;
		case 3:
			$img = "images/status_sets/default/severity_warning.png";
			break;
		case 4:
			$img = "images/status_sets/default/severity_critical.png";
			break;
	}
	
	$data[1] = print_image ($img, true, 
		array ("class" => "image_status",
			"width" => 12,
			"height" => 12,
			"title" => get_priority_name ($event["criticity"])));
	
	$data[2] = print_event_type_img ($event["event_type"], true);
	
	// Event description
	$data[3] = '<span title="'.$event["evento"].'" class="f9">';
	$data[3] .= '<a href="'.$url.'&amp;group_rep=0&amp;pure='.$config["pure"].'&amp;search='.rawurlencode ($event["evento"]).'">';
	if (strlen ($event["evento"]) > 50) {
		$data[3] .= mb_substr ($event["evento"], 0, 50)."...";
	}
	else {
		$data[3] .= $event["evento"];
	}
	$data[3] .= '</a></span>';

	if ($event["event_type"] == "system") {
		$data[4] = __('System');
	}
	elseif ($event["id_agente"] > 0) {
		// Agent name
		$data[4] = print_agent_name ($event["id_agente"], true);
	}
	else {
		$data[4] = __('Alert').__('SNMP');
	}
	
	$data[5] = '';
	if ($event["id_agentmodule"] != 0) {
		$data[5] .= '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$event["id_agente"].'&amp;tab=data">';
		$data[5] .= print_image ("images/bricks.png", true,
			array ("title" => __('Go to data overview')));
		$data[5] .= '</a>&nbsp;';
	}
	if ($event["id_alert_am"] != 0) {
		$data[5] .= '<a href="index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$event["id_agente"].'&amp;tab=alert">';
		$data[5] .= print_image ("images/bell.png", true,
			array ("title" => __('Go to alert overview')));
		$data[5] .= '</a>';
	}

	$data[6] = print_group_icon ($event["id_grupo"], true);

	if ($group_rep == 1) {
		$data[7] = $event["event_rep"];
	}
	else {
		if (!empty ($event["estado"])) {
			if ($event["id_usuario"] != '0' && $event["id_usuario"] != '' && $event["id_usuario"] != 'system' && $event["id_usuario"] != "System"){
				$data[7] = '<a href="index.php?sec=usuarios&sec2=operation/users/user_edit&id='.$event["id_usuario"].'" title="'.dame_nombre_real ($event["id_usuario"]).'">'.mb_substr ($event["id_usuario"],0,8).'</a>';
			}
			else {
				$data[7] = __('System');
			}
		}
		else {
			$data[7] = '';
		}
	}
	
	//Time
	if ($group_rep == 1) {
		$data[8] = print_timestamp ($event['timestamp_rep'], true);
	}
	else {
		$data[8] = print_timestamp ($event["timestamp"], true);
	}
	
	//Actions
	$data[9] = '';
	// Validate event
	if (($event["estado"] == 0) and (give_acl ($config["id_user"], $event["id_grupo"], "IW") == 1)) {
		$data[9] .= '<a class="validate_event" href="#" onclick="return false" id="delete-'.$event["id_evento"].'">';
		$data[9] .= print_image ("images/ok.png", true,
			array ("title" => __('Validate event')));
		$data[9] .= '</a>';
	}
	// Delete event
	if (give_acl ($config["id_user"], $event["id_grupo"], "IM") == 1) {
		$data[9] .= '<a class="delete_event" href="#" onclick="return false" id="validate-'.$event['id_evento'].'">';
		$data[9] .= print_image ("images/cross.png", true,
			array ("title" => __('Delete event')));
		$data[9] .= '</a>';
	}
	// Create incident from this event
	if (give_acl ($config["id_user"], $event["id_grupo"], "IW") == 1) {
		$data[9] .= '<a href="index.php?sec=incidencias&amp;sec2=operation/incidents/incident_detail&amp;insert_form&amp;from_event='.$event["id_evento"].'">';
		$data[9] .= print_image ("images/page_lightning.png", true,
			array ("title" => __('Create incident from event')));
		$data[9] .= '</a>';
	}
	
	//Checkbox
	$data[10] = print_checkbox_extended ("eventid[]", $event["id_evento"], false, false, false, 'class="chk"', true);
	
	array_push ($table->data, $data);
}

echo '<div id="events_list">';
if (!empty ($table->data)) {
	pagination ($total_events, $url."&pure=".$config["pure"], $offset, $pagination);
	echo '<form method="post" action="'.$url.'&amp;pure='.$config["pure"].'">';

	print_table ($table);
	
	echo '<div style="width:'.$table->width.';" class="action-buttons">';
	if (give_acl ($config["id_user"], 0, "IW") == 1) {
		print_submit_button (__('Validate'), 'validate', false, 'class="sub ok"');
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

require_jquery_file ('bgiframe');
require_jquery_file ('autocomplete');
?>
<script language="javascript" type="text/javascript">
/* <![CDATA[ */

$(document).ready( function() {

	$("#text_id_agent").autocomplete(
			"ajax.php",
			{
				minChars: 2,
				scroll:true,
				extraParams: {
					page: "operation/agentes/exportdata",
					search_agents: 1,
					add: '<?php echo json_encode(array('-1' => "All", '0' => "System"));?>',
					id_group: function() { return $("#id_group").val(); }
				},
				formatItem: function (data, i, total) {
					if (total == 0)
						$("#text_id_agent").css ('background-color', '#cc0000');
					else
						$("#text_id_agent").css ('background-color', '');
					if (data == "")
						return false;
					
					return data[0]+'<br><span class="ac_extra_field"><?php echo __("IP") ?>: '+data[1]+'</span>';
				},
				delay: 200
			}
		);
	
	
	$("input[name=allbox]").change (function() {
		$("input[name='eventid[]']").attr('checked', $(this).attr('checked'));
	});
	
	$("#tgl_event_control").click (function () {
		$("#event_control").toggle ();
		return false;
	});
	
	$("a.validate_event").click (function () {
		$tr = $(this).parents ("tr");
		id = this.id.split ("-").pop ();
		jQuery.post ("ajax.php",
			{"page" : "operation/events/events",
			"validate_event" : 1,
			"id" : id,
			"similar" : <?php echo ($group_rep ? 1 : 0) ?>
			},
			function (data, status) {
				if (data == "ok") {
					<?php if ($status == 0) : ?>
					$tr.remove ();
					<?php else: ?>
					$("img.image_status", $tr).attr ("src", "images/pixel_green.png");
					<?php endif; ?>
				} else {
					$("#result")
						.showMessage ("<?php echo __('Could not be validated')?>")
						.addClass ("error");
				}
			},
			"html"
		);
		return false;
	});
	
	$("a.delete_event").click (function () {
		$tr = $(this).parents ("tr");
		id = this.id.split ("-").pop ();
		jQuery.post ("ajax.php",
			{"page" : "operation/events/events",
			"delete_event" : 1,
			"id" : id,
			"similar" : <?php echo ($group_rep ? 1 : 0) ?>
			},
			function (data, status) {
				if (data == "ok")
					$tr.remove ();
				else
					$("#result")
						.showMessage ("<?php echo __('Could not be deleted')?>")
						.addClass ("error");
			},
			"html"
		);
		return false;
	});
});
/* ]]> */
</script>
