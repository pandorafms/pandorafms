<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2012 Artica Soluciones Tecnologicas
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

if (! check_acl ($config['id_user'], 0, "AW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Recon Task Management");
	require ("general/noaccess.php");
	exit;
}

require_once($config['homedir'] . "/include/functions_network_profiles.php");

if (check_acl ($config['id_user'], 0, "AW")) {
	$options[]['text'] = "<a href='index.php?sec=estado&sec2=operation/servers/recon_view'>" . html_print_image ("images/operation.png", true, array ("title" =>__('View'))) . "</a>";
}

$user_groups_w = users_get_groups(false, 'AW', true, false, null, 'id_grupo');
$user_groups_w = array_keys($user_groups_w);

$user_groups_r = users_get_groups(false, 'AR', true, false, null, 'id_grupo');
$user_groups_r = array_keys($user_groups_r);

// Headers
//ui_print_page_header (__('Manage recontask'), "images/gm_servers.png", false, "", true);
ui_print_page_header (__('Manage recontask'), "images/gm_servers.png", false, "", true, $options);


// --------------------------------
// DELETE A RECON TASKs
// --------------------------------
if (isset ($_GET["delete"])) {
	$id = get_parameter_get ("delete");
	
	$result = db_process_sql_delete('trecon_task', array('id_rt' => $id));
	
	if ($result !== false) {
		ui_print_success_message(__('Successfully deleted recon task'));
	}
	else {
		ui_print_error_message(__('Error deleting recon task'));
	}
}
else if(isset($_GET["disabled"])) {
	$id = get_parameter_get ("id");
	$disabled = get_parameter_get ("disabled");
	
	$result = db_process_sql_update('trecon_task', array('disabled' => $disabled), array('id_rt' => $id));
	
	if ($result !== false) {
		ui_print_success_message(__('Successfully updated recon task'));
		// If the action is enabled, we force recon_task to be queued asap
		if($disabled == 0) {
			servers_force_recon_task($id);
		}
	}
	else {
		ui_print_error_message(__('Error updating recon task'));
	}
}

// --------------------------------
// GET PARAMETERS IF UPDATE OR CREATE
// --------------------------------
if ((isset ($_GET["update"])) OR ((isset ($_GET["create"])))) {
	$name = get_parameter_post ("name");
	$network = get_parameter_post ("network");
	$description = get_parameter_post ("description");
	$id_recon_server = get_parameter_post ("id_recon_server");
	$interval = get_parameter_post ("interval");
	$id_group = get_parameter_post ("id_group");
	$create_incident = get_parameter_post ("create_incident");
	$id_network_profile = get_parameter_post ("id_network_profile");
	$recon_ports = get_parameter_post ("recon_ports", "");
	$id_os = get_parameter_post ("id_os", 10);
	$snmp_community = get_parameter_post ("snmp_community", "public");
	$id_recon_script = get_parameter ("id_recon_script", 0);
	$mode = get_parameter ("mode", "");
	$field1 = get_parameter ("_field1_", "");
	$field2 = get_parameter ("_field2_", "");
	$field3 = get_parameter ("_field3_", "");
	$field4 = get_parameter ("_field4_", "");
	
	if ($mode == "network_sweep")
		$id_recon_script = 0;
	else
		$id_network_profile = 0;
	
	$os_detect = (int) get_parameter ("os_detect", 0);
	$resolve_names = (int) get_parameter ("resolve_names", 0);
	$parent_detection = (int) get_parameter ("parent_detection", 0);
	$parent_recursion = (int) get_parameter ("parent_recursion", 1);
	
	// Get macros
	$macros = (string) get_parameter ('macros');
	
	if (!empty($macros)) {
		$macros = json_decode(base64_decode($macros), true);
		
		foreach($macros as $k => $m) {
			$macros[$k]['value'] = get_parameter($m['macro'], '');
		}
	}
	
	$macros = io_json_mb_encode($macros);
}

// --------------------------------
// UPDATE A RECON TASK
// --------------------------------
if (isset($_GET["update"])) {
	$id = get_parameter_get ("update");
	
	$values = array(
		'snmp_community' => $snmp_community,
		'id_os' => $id_os,
		'name' => $name,
		'subnet' => $network,
		'description' => $description,
		'id_recon_server' => $id_recon_server,
		'create_incident' => $create_incident,
		'id_group' => $id_group,
		'interval_sweep' => $interval,
		'id_network_profile' => $id_network_profile,
		'recon_ports' => $recon_ports,
		'id_recon_script' => $id_recon_script,
		'field1' => $field1,
		'field2' => $field2,
		'field3' => $field3,
		'field4' => $field4,
		'os_detect' => $os_detect,
		'resolve_names' => $resolve_names,
		'parent_detection' => $parent_detection,
		'parent_recursion' => $parent_recursion,
		'macros' => $macros
		);
		
	$where = array('id_rt' => $id);
	
	$reason = '';
	if ($name != "") {
		if (empty($id_recon_script)) {
			if (!preg_match("/[0-9]+.+[0-9]+.+[0-9]+.+[0-9]+\/+[0-9]/", $network)){
				$reason = __('Wrong format in Subnet field');
				$result = false;
			}
			else{
				$result = db_process_sql_update('trecon_task', $values, $where);
			}
		}
		else {
			$result = db_process_sql_update('trecon_task', $values, $where);
		}
	}
	else {
		$result = false;
	}

	if ($result !== false) {
		ui_print_success_message(__('Successfully updated recon task'));
	}
	else {
		ui_print_error_message(__('Error updating recon task'));
		echo $reason;
		include('manage_recontask_form.php');
		return;
	}
}

// --------------------------------
// CREATE A RECON TASK
// --------------------------------
if (isset($_GET["create"])) {
	$values = array(
		'name' => $name,
		'subnet' => $network,
		'description' => $description,
		'id_recon_server' => $id_recon_server,
		'create_incident' => $create_incident,
		'id_group' => $id_group,
		'id_network_profile' => $id_network_profile,
		'interval_sweep' => $interval,
		'id_os' => $id_os,
		'recon_ports' => $recon_ports,
		'snmp_community' => $snmp_community,
		'id_recon_script' => $id_recon_script,
		'field1' => $field1,
		'field2' => $field2,
		'field3' => $field3,
		'field4' => $field4,
		'os_detect' => $os_detect,
		'resolve_names' => $resolve_names,
		'parent_detection' => $parent_detection,
		'parent_recursion' => $parent_recursion,
		'macros' => $macros
		);

	$name = io_safe_output($name);
	$name = trim($name, ' ');
	$name = io_safe_input($name);
	
	$reason = "";

	if ($name != "") {

		$name_exists = (bool) db_get_value ('name', 'trecon_task', 'name', $name);

		if (empty($id_recon_script)) {
			if ($name_exists && (!preg_match("/[0-9]+.+[0-9]+.+[0-9]+.+[0-9]+\/+[0-9]/", $network))){
				$reason = __('Recon-task name already exists and incorrect format in Subnet field');
				$result = false;
			}
			else if (!preg_match("/[0-9]+.+[0-9]+.+[0-9]+.+[0-9]+\/+[0-9]/", $network)){
				$reason = __('Wrong format in Subnet field');
				$result = false;
			}
			else if ($name_exists){
				$reason = __('Recon-task name already exists');
				$result = false;
			}
			else{
				$result = db_process_sql_insert('trecon_task', $values);
			}
		}
		else {
			if ($name_exists){
				$reason = __('Recon-task name already exists');
				$result = false;
			}
			else{
				$result = db_process_sql_insert('trecon_task', $values);
			}
		}
	}
	else {
		$reason = 'The field "Task name" is empty';
		$result = false;
	}

	if ($result !== false) {
		ui_print_success_message(__('Successfully created recon task'));
	}
	else {
		ui_print_error_message(__('Error creating recon task'));
		echo $reason;
		include('manage_recontask_form.php');
		return;
	}
}

// --------------------------------
// SHOW TABLE WITH ALL RECON TASKs
// --------------------------------
//Pandora Admin must see all columns
if (! check_acl ($config['id_user'], 0, "PM")) {
	
	$sql = sprintf('SELECT *
		FROM trecon_task RT, tusuario_perfil UP
		WHERE 
			UP.id_usuario = "%s" AND UP.id_grupo = RT.id_group', 
		$config['id_user']);
	
	$result = db_get_all_rows_sql ($sql);
}
else {
	$result = db_get_all_rows_in_table('trecon_task');
}
$color=1;
if ($result !== false) {
	$table = new StdClass();
	$table->head = array  (__('Name'), __('Network'), __('Mode'), __('Group'), __('Incident'), __('OS'), __('Interval'), __('Ports'), __('Action'));
	$table->align = array ("left","left","left","left","left","left","left","left");
	$table->width = "100%";
	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->class = "databox data";
	$table->data = array ();
	
	$table->style[8] = 'text-align: left;';
	$table->size[8] = '15%';
	
	foreach ($result as $row) {
		if (in_array($row["id_group"], $user_groups_r)){
			$data = array();
			$data[0] = $row["name"];
			if ($row["id_recon_script"] == 0)
				$data[1] = $row["subnet"];
			else
				$data[1] = "-";
			
			
			if ($row["id_recon_script"] == 0) {
				// Network recon task
				$data[2] = html_print_image ("images/network.png", true, array ("title" => __('Network recon task')))."&nbsp;&nbsp;";
				$data[2] .= network_profiles_get_name ($row["id_network_profile"]);
				$mode_name = '';
			}
			else {
				// APP recon task
				$data[2] = html_print_image ("images/plugin.png", true). "&nbsp;&nbsp;";
				$mode_name = db_get_sql (sprintf("SELECT name FROM trecon_script WHERE id_recon_script = %d", $row["id_recon_script"]));
				$data[2] .= $mode_name;
			}
			
			
			// GROUP
			if ($row["id_recon_script"] == 0) {
				$data[3] = ui_print_group_icon ($row["id_group"], true);
			}
			else {
				$data[3] = "-";
			}
			
			// INCIDENT
			$data[4] = (($row["create_incident"] == 1) ? __('Yes') : __('No'));
			
			// OS
			if ($row["id_recon_script"] == 0) {
				$data[5] =(($row["id_os"] > 0) ? ui_print_os_icon ($row["id_os"], false, true) : __('Any'));
			}
			else {
				$data[5] = "-";
			}
			// INTERVAL
			if ($row["interval_sweep"]==0)
				$data[6] = __("Manual");
			else
				$data[6] =human_time_description_raw($row["interval_sweep"]);
			
			// PORTS
			if ($row["id_recon_script"] == 0) {
				$data[7] = substr($row["recon_ports"],0,15);
			}
			else {
				$data[7] = "-";
			}
			
			// ACTION
			$task_group = $row["id_group"];

			if (in_array($task_group, $user_groups_w)){
				$data[8] = '<a href="index.php?sec=estado&sec2=operation/servers/recon_view">' . html_print_image("images/eye.png", true) . '</a>';
				$data[8] .= '<a href="index.php?sec=gservers&sec2=godmode/servers/manage_recontask&delete='.$row["id_rt"].'">' . html_print_image("images/cross.png", true, array("border" => '0')) . '</a>';
				if($mode_name != 'IPAM Recon'){
					$data[8] .= '<a href="index.php?sec=gservers&sec2=godmode/servers/manage_recontask_form&update='.$row["id_rt"].'">' .html_print_image("images/config.png", true) . '</a>';
				} else {
					$sql_ipam = 'select id from tipam_network where id_recon_task =' . $row["id_rt"];
					$id_recon_ipam = db_get_sql($sql_ipam);
					$data[8] .= '<a href="index.php?sec=godmode/extensions&sec2=enterprise/extensions/ipam&action=edit&id=' . $id_recon_ipam . '">' . html_print_image("images/config.png", true) . '</a>';
				}
				if($row["disabled"] == 0) {
					$data[8] .= '<a href="index.php?sec=gservers&sec2=godmode/servers/manage_recontask&id='.$row["id_rt"].'&disabled=1">' .html_print_image("images/lightbulb.png", true) . '</a>';
				}
				else {
					$data[8] .= '<a href="index.php?sec=gservers&sec2=godmode/servers/manage_recontask&id='.$row["id_rt"].'&disabled=0">' .html_print_image("images/lightbulb_off.png", true) . '</a>';
				}
			}
			
			$table->data[] = $data;
		}
	}
	
	html_print_table ($table);
	unset ($table);
}
else {
	echo '<div class="nf">'.__('There are no recon task configured').'</div>';
}

echo '<div class="action-buttons" style="width: 99%;">';
echo '<form method="post" action="index.php?sec=gservers&sec2=godmode/servers/manage_recontask_form&create">';
echo html_print_submit_button (__('Create'),"crt",false,'class="sub next"',true);
echo '</form>';
echo "</div>";
?>
