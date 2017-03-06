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

check_login ();

if (! check_acl ($config['id_user'], 0, "PM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Agent Management");
	require ("general/noaccess.php");
	return;
}

require_once ($config['homedir'].'/include/functions_users.php');

$user_groups = users_get_groups(false, 'AW', true, false, null, 'id_grupo');
$user_groups = array_keys($user_groups);

if (is_ajax ()) {
	$get_explanation = (bool) get_parameter('get_explanation', 0);
	
	if ($get_explanation) {
		$id = (int) get_parameter('id', 0);
		
		$explanation = db_get_value('description', 'trecon_script', 'id_recon_script', $id);
		
		echo io_safe_output($explanation);
		
		return;
	}
	
	$get_recon_script_macros = get_parameter('get_recon_script_macros');
	if ($get_recon_script_macros) {
		$id_recon_script = (int) get_parameter('id');
		$id_recon_task = (int) get_parameter('id_rt');
		
		if (!empty($id_recon_task) && empty($id_recon_script)) {
			$recon_script_macros = db_get_value('macros', 'trecon_task', 'id_rt', $id_recon_task);
		}
		else if (!empty($id_recon_task)) {
			$recon_task_id_rs = (int) db_get_value('id_recon_script', 'trecon_task', 'id_rt', $id_recon_task);
			
			if ($id_recon_script == $recon_task_id_rs) {
				$recon_script_macros = db_get_value('macros', 'trecon_task', 'id_rt', $id_recon_task);
			}
			else {
				$recon_script_macros = db_get_value('macros', 'trecon_script', 'id_recon_script', $id_recon_script);
			}
		}
		else if (!empty($id_recon_script)) {
			$recon_script_macros = db_get_value('macros', 'trecon_script', 'id_recon_script', $id_recon_script);
		}
		else {
			$recon_script_macros = array();
		}
		
		$macros = array();
		$macros['base64'] = base64_encode($recon_script_macros);
		$macros['array'] = json_decode($recon_script_macros,true);
		
		echo io_json_mb_encode($macros);
		return;
	}
	
	return;
}

// Edit mode
if (isset($_GET["update"]) || (isset($_GET["upd"]))) {
	
	$update_recon = true;
	if (isset($_GET["upd"])) {
		if ($_GET["upd"] != "update") {
			$update_recon = false;
		}
		else {
			$id_rt = get_parameter("upd");
		}
	}
	
	if ($update_recon) {
		if (!isset($id_rt)) {
			$id_rt = (int) get_parameter_get ("update");
		}
		$row = db_get_row ("trecon_task", "id_rt", $id_rt);
		$name = $row["name"];
		$network = $row["subnet"];
		$id_recon_server = $row["id_recon_server"];
		$description = $row["description"];
		$interval = $row["interval_sweep"];
		$id_group = $row["id_group"];
		$create_incident = $row["create_incident"];
		$id_network_profile = $row["id_network_profile"];
		$id_os = $row["id_os"];
		$recon_ports = $row["recon_ports"];
		$snmp_community = $row["snmp_community"];
		$id_recon_script = $row["id_recon_script"];
		$field1 = $row["field1"];
		$field2 = $row["field2"];
		$field3 = $row["field3"];
		$field4 = $row["field4"];
		if ($id_recon_script == 0)
			$mode = "network_sweep";
		else
			$mode = "recon_script";
		$os_detect = $row["os_detect"];
		$resolve_names = $row["resolve_names"];
		$os_detect = $row["os_detect"];
		$parent_detection = $row["parent_detection"];
		$parent_recursion = $row["parent_recursion"];
		$macros = $row["macros"];
		$alias_as_name = $row["alias_as_name"];
		
		$name_script = db_get_value('name',
			'trecon_script', 'id_recon_script', $id_recon_script);

		if (! in_array($id_group, $user_groups)){
			db_pandora_audit("ACL Violation",
			"Trying to access Recon Task Management");
			require ("general/noaccess.php");
			return;
		}
	}
}
elseif (isset($_GET["create"]) || isset($_GET["crt"])) {
	$create_recon = true;
	if (isset ($_GET["crt"])) {
		if ($_GET["crt"] != "Create") {
			$create_recon = false;
		}
	}
	
	if ($create_recon) {
		$id_rt = -1;
		$name = get_parameter('name');
		$network = get_parameter('network');
		$description = get_parameter('description');
		$id_recon_server = 0;
		$interval = 0;
		$id_group = 0;
		$create_incident = 1;
		$snmp_community = "public";
		$id_network_profile = 0;
		$id_os = -1; // Any
		$recon_ports = ""; // Any
		$field1 = "";
		$field2 = "";
		$field3 = "";
		$field4 = "";
		$id_recon_script = 0;
		$mode = "network_sweep";
		$os_detect = 0;
		$resolve_names = 0;
		$parent_detection = 1;
		$parent_recursion = 5;
		$macros = '';
		$alias_as_name = 0;
	}

	$modify = false;
	if (($name != '') || ($network != '')){
		$modify = true;
	}
}

if (!$modify){
	// Headers
	ui_print_page_header (__('Manage recontask'), "", false, "recontask", true);
}


$is_windows = strtoupper(substr(PHP_OS, 0, 3)) == 'WIN';
if ($is_windows) {
	echo '<div class="notify">';
	echo __('Warning') . ": " . __("By default, in Windows, Pandora FMS only support Standard network sweep, not custom scripts");
	echo '</div>';
}

$table = new stdClass();
$table->id = 'table_recon';
$table->width = '100%';
$table->cellspacing = 4;
$table->cellpadding = 4;
$table->class = "databox filters";

$table->rowclass[3] = "network_sweep";
$table->rowclass[5] = "network_sweep";
$table->rowclass[7] = "network_sweep";
$table->rowclass[8] = "network_sweep";
$table->rowclass[11] = "network_sweep";
$table->rowclass[17] = "network_sweep";
$table->rowclass[18] = "network_sweep";
$table->rowclass[19] = "network_sweep";
$table->rowclass[20] = "network_sweep";
$table->rowclass[21] = "network_sweep";

$table->rowclass[6] = "recon_script";
$table->rowclass[12] = "recon_script";
$table->rowclass[13] = "recon_script";
$table->rowclass[14] = "recon_script";
$table->rowclass[15] = "recon_script";
$table->rowclass[16] = "recon_script";
// Name
$table->data[0][0] = "<b>" . __('Task name') . "</b>";
$table->data[0][1] = html_print_input_text ('name', $name, '', 25, 0, true);

// Recon server
$table->data[1][0] = "<b>" . __('Recon server') .
	ui_print_help_tip(
		__('You must select a Recon Server for the Task, otherwise the Recon Task will never run'), true);

$sql = 'SELECT id_server, name
		FROM tserver
		WHERE server_type = 3
		ORDER BY name';
$table->data[1][1] = html_print_select_from_sql ($sql, "id_recon_server", $id_recon_server, '', '', '', true);

$fields['network_sweep'] = __("Network sweep");
if (!$is_windows)
	$fields['recon_script'] = __("Custom script");


$table->data[2][0] = "<b>".__('Mode')."</b>";
$table->data[2][1] = html_print_select ($fields, "mode", $mode, '', '', 0, true);


// Network 
$table->data[3][0] = "<b>" . __('Network') . "</b>";
$table->data[3][1] = html_print_input_text ('network', $network, '', 25, 0, true);

// Interval


$table->data[4][0] = "<b>".__('Interval');
$table->data[4][0] .= ui_print_help_tip (__('Manual interval means that it will be executed only On-demand'), true);

$values = array (0 => __('Defined'), 1 => __('Manual'));
$table->data[4][1] = html_print_select ($values, "interval_manual_defined", (int)($interval === 0), '','','',true);

$table->data[4][1] .= '<span id="interval_manual_container">';
$table->data[4][1] .= html_print_extended_select_for_time ('interval' , $interval, '', '', '0', false, true, false, false);
$table->data[4][1] .= ui_print_help_tip (__('The minimum recomended interval for Recon Task is 5 minutes'), true);
$table->data[4][1] .= '</span>';


// Module template
$table->data[5][0] = "<b>" . __('Module template') . "</b>";

$sql = 'SELECT id_np, name
		FROM tnetwork_profile
		ORDER BY name';
$table->data[5][1] = html_print_select_from_sql ($sql, "id_network_profile", $id_network_profile, '', __('None'), 0, true);

// Recon script
$data[1] = '';
$table->data[6][0] = "<b>" . __('Recon script') . "</b>";


$sql = "SELECT id_recon_script, name
		FROM trecon_script
		WHERE name <> 'IPAM Recon'
		ORDER BY name";
if ($name_script != "IPAM Recon") {
	$table->data[6][1] = html_print_select_from_sql ($sql, "id_recon_script", $id_recon_script, '', '', '', true);
	$table->data[6][1] .= "<span id='spinner_recon_script' style='display: none;'>" . html_print_image ("images/spinner.gif", true) . "</span>";
	$table->data[6][1] .= $data[1] .= html_print_input_hidden('macros', base64_encode($macros),true);
}
else {
	$table->data[6][1] = "IPAM Recon";
}

// OS
$table->data[7][0] = "<b>" . __('OS') . "</b>";

$sql = 'SELECT id_os, name
		FROM tconfig_os
		ORDER BY name';
$table->data[7][1] = html_print_select_from_sql ($sql, "id_os", $id_os, '', __('Any'), -1, true);

// Recon ports
$table->data[8][0] = "<b>" . __('Ports') . "</b>";
$table->data[8][1] =  html_print_input_text ('recon_ports', $recon_ports, '', 25, 0, true);
$table->data[8][1] .= ui_print_help_tip(
	__('Ports defined like: 80 or 80,443,512 or even 0-1024 (Like Nmap command line format). If dont want to do a sweep using portscan, left it in blank'), true);

// Group
$table->data[9][0] = "<b>".__('Group');
$groups = users_get_groups (false, "PM", false);
$table->data[9][1] = html_print_select_groups(false, "PM", false, 'id_group', $id_group, '', '', 0, true);

// Incident
$values = array (0 => __('No'), 1 => __('Yes'));
$table->data[10][0] = "<b>".__('Incident');
$table->data[10][1] = html_print_select ($values, "create_incident", $create_incident,
	'','','',true) . ' ' . ui_print_help_tip (__('Choose if the discovery of a new system creates an incident or not.'), true);

// SNMP default community
$table->data[11][0] = "<b>".__('SNMP Default community');
$table->data[11][1] =  html_print_input_text ('snmp_community', $snmp_community, '', 35, 0, true);

// Explanation
$explanation = db_get_value('description', 'trecon_script', 'id_recon_script', $id_recon_script);

$table->data[12][0] = "<b>" . __('Explanation') . "</b>";
$table->data[12][1] = "<span id='spinner_layout' style='display: none;'>" . html_print_image ("images/spinner.gif", true) .
"</span>" . html_print_textarea('explanation', 4, 60, $explanation, 'style="width: 388px;"', true);

// A hidden "model row" to clone it from javascript to add fields dynamicaly
$data = array ();
$data[0] = 'macro_desc';
$data[0] .= ui_print_help_tip ('macro_help', true);
$data[1] = html_print_input_text ('macro_name', 'macro_value', '', 100, 255, true);
$table->colspan['macro_field'][1] = 3;
$table->rowstyle['macro_field'] = 'display:none';
$table->data['macro_field'] = $data;

// If there are $macros, we create the form fields
if (!empty($macros)) {
	$macros = json_decode($macros, true);
	
	foreach ($macros as $k => $m) {
		$data = array ();
		$data[0] = "<b>" . $m['desc'] . "</b>";
		if (!empty($m['help'])) {
			$data[0] .= ui_print_help_tip ($m['help'], true);
		}
		if($m['hide']) {
			$data[1] = html_print_input_password($m['macro'], $m['value'], '', 100, 255, true);
		}
		else {
			$data[1] = html_print_input_text($m['macro'], $m['value'], '', 100, 255, true);
		}
		$table->colspan['macro'.$m['macro']][1] = 3;
		$table->rowclass['macro'.$m['macro']] = 'macro_field';
		
		$table->data['macro'.$m['macro']] = $data;
	}
}

// Comments
$table->data[17][0] = "<b>".__('Comments');
$table->data[17][1] =  html_print_input_text ('description', $description, '', 45, 0, true);

// OS detection
$table->data[18][0] = "<b>".__('OS detection');
$table->data[18][1] =  html_print_checkbox ('os_detect', 1, $os_detect, true);

// Name resolution
$table->data[19][0] = "<b>".__('Name resolution');
$table->data[19][1] =  html_print_checkbox ('resolve_names', 1, $resolve_names, true);

// Parent detection
$table->data[20][0] = "<b>".__('Parent detection');
$table->data[20][1] =  html_print_checkbox ('parent_detection', 1, $parent_detection, true);

// Parent recursion
$table->data[21][0] = "<b>".__('Parent recursion');
$table->data[21][1] =  html_print_input_text ('parent_recursion', $parent_recursion, '', 5, 0, true) . ui_print_help_tip (__('Maximum number of parent hosts that will be created if parent detection is enabled.'), true);

// Alias as name
$table->data[22][0] = "<b>".__('Alias as Name');
$table->data[22][1] =  html_print_checkbox ('alias_as_name', 1, $alias_as_name, true);

// Different Form url if it's a create or if it's a update form
echo '<form name="modulo" method="post" action="index.php?sec=gservers&sec2=godmode/servers/manage_recontask&'.(($id_rt != -1) ? 'update='.$id_rt : 'create=1').'">';
html_print_table ($table);
echo '<div class="action-buttons" style="width: '.$table->width.'">';

if ($id_rt != -1) {
	if ($name_script != "IPAM Recon") {
		html_print_submit_button (__('Update'), "crt", false, 'class="sub upd"');
	}
}
else {
	html_print_submit_button (__('Add'), "crt", false, 'class="sub wand"');
}
echo "</div>";

echo "</form>";

ui_require_javascript_file ('pandora_modules');

?>

<script type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
	
});

var xhrManager = function () {
	var manager = {};
	
	manager.tasks = [];
	
	manager.addTask = function (xhr) {
		manager.tasks.push(xhr);
	}
	
	manager.stopTasks = function () {
		while (manager.tasks.length > 0)
			manager.tasks.pop().abort();
	}
	
	return manager;
};

var taskManager = new xhrManager();

$('select#interval_manual_defined').change(function() {
	if ($("#interval_manual_defined").val() == 1) {
		$('#interval_manual_container').hide();
		$('#text-interval_text').val(0);
		$('#hidden-interval').val(0);
	}
	else {
		$('#interval_manual_container').show();
		$('#text-interval_text').val(10);
		$('#hidden-interval').val(600);
		$('#interval_units').val(60);
	}
}).change();

$('select#id_recon_script').change(function() {
	if ($('select#mode').val() == 'recon_script')
		get_explanation_recon_script($(this).val());
});

$('select#mode').change(function() {
	var type = $(this).val();
	
	if (type == 'recon_script') {
		$(".recon_script").show();
		$(".network_sweep").hide();
		
		get_explanation_recon_script($("#id_recon_script").val());
	}
	else if (type == 'network_sweep') {
		$(".recon_script").hide();
		$(".network_sweep").show();
		$('.macro_field').remove();
	}
}).change();

function get_explanation_recon_script (id) {
	// Stop old ajax tasks
	taskManager.stopTasks();
	
	// Show the spinners
	$("#textarea_explanation").hide();
	$("#spinner_layout").show();
	
	var xhr = jQuery.ajax ({
		data: {
			'page': 'godmode/servers/manage_recontask_form',
			'get_explanation': 1,
			'id': id,
			'id_rt': <?php echo json_encode((int)$id_rt); ?>
		},
		url: "<?php echo $config['homeurl']; ?>ajax.php",
		type: 'POST',
		dataType: 'text',
		complete: function (xhr, textStatus) {
			$("#spinner_layout").hide();
		},
		success: function (data, textStatus, xhr) {
			$("#textarea_explanation").val(data);
			$("#textarea_explanation").show();
		},
		error: function (xhr, textStatus, errorThrown) {
			console.log(errorThrown);
		}
	});
	taskManager.addTask(xhr);
	
	// Delete all the macro fields
	$('.macro_field').remove();
	$("#spinner_recon_script").show();
	
	var xhr = jQuery.ajax ({
		data: {
			'page': 'godmode/servers/manage_recontask_form',
			'get_recon_script_macros': 1,
			'id': id,
			'id_rt': <?php echo json_encode((int)$id_rt); ?>
		},
		url: "<?php echo $config['homeurl']; ?>ajax.php",
		type: 'POST',
		dataType: 'json',
		complete: function (xhr, textStatus) {
			$("#spinner_recon_script").hide();
			forced_title_callback();
		},
		success: function (data, textStatus, xhr) {
			if (data.array !== null) {
				$('#hidden-macros').val(data.base64);
				
				jQuery.each (data.array, function (i, macro) {
					if (macro.desc != '') {
						add_macro_field(macro, 'table_recon-macro');
					}
				});
			}
		},
		error: function (xhr, textStatus, errorThrown) {
			console.log(errorThrown);
		}
	});
	taskManager.addTask(xhr);
}
/* ]]> */
</script>
