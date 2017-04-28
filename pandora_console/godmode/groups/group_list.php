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

check_login();

enterprise_hook('open_meta_frame');

require_once($config['homedir'] . "/include/functions_groups.php");
require_once($config['homedir'] . "/include/functions_agents.php");
require_once($config['homedir'] . '/include/functions_users.php');
enterprise_include_once ('meta/include/functions_agents_meta.php');

if (is_ajax ()) {
	if (! check_acl($config['id_user'], 0, "AR")) {
		db_pandora_audit("ACL Violation", "Trying to access Group Management");
		require ("general/noaccess.php");
		return;
	}
	
	$get_group_json = (bool) get_parameter ('get_group_json');
	$get_group_agents = (bool) get_parameter ('get_group_agents');
	
	if ($get_group_json) {
		$id_group = (int) get_parameter ('id_group');
		
		if ($id_group == 0) {
			$group = array('id_grupo' => 0,
				'nombre' => 'All', 
				'icon' => 'world',
				'parent' => 0,
				'disabled' => 0,
				'custom_id' => null);
			
			echo json_encode ($group);
			return;
		}
		
		if (! check_acl ($config['id_user'], $id_group, "AR")) {
			db_pandora_audit("ACL Violation",
				"Trying to access Alert Management");
			echo json_encode (false);
			return;
		}
		
		$group = db_get_row ('tgrupo', 'id_grupo', $id_group);
		
		echo json_encode ($group);
		return;
	}
	
	if ($get_group_agents) {
		$id_group = (int) get_parameter ('id_group');
		$disabled = (int) get_parameter ('disabled', 0);
		$search = (string) get_parameter ('search', '');
		$recursion = (int) get_parameter ('recursion', 0);
		$privilege = (string) get_parameter ('privilege', '');
		// Is is possible add keys prefix to avoid auto sorting in js object conversion
		$keys_prefix = (string) get_parameter ('keys_prefix', '');
		// This attr is for the operation "bulk alert accions add", it controls the query that take the agents
		// from db
		$add_alert_bulk_op = get_parameter ('add_alert_bulk_op', false);
		// Ids of agents to be include in the SQL clause as id_agent IN ()
		$filter_agents_json = (string) get_parameter ('filter_agents_json', '');
		$status_agents = (int)get_parameter('status_agents', AGENT_STATUS_ALL);
		// Juanma (22/05/2014) Fix: If setted remove void agents from result (by default and for compatibility show void agents)
		$show_void_agents = (int)get_parameter('show_void_agents', 1);
		
		if (! check_acl ($config['id_user'], $id_group, "AR")) {
			db_pandora_audit("ACL Violation",
				"Trying to access Alert Management");
			echo json_encode (false);
			return;
		}
		
		if ( https_is_running() ) {
			header('Content-type: application/json');
		}
		
		if ($filter_agents_json != '') {
			$filter['id_agente'] = json_decode(io_safe_output($filter_agents_json), true);
		}
		
		$filter['disabled'] = $disabled;
		
		if ($search != '') {
			$filter['string'] = $search;
		}
		
		if ($status_agents != AGENT_STATUS_ALL) {
			$filter['status'] = $status_agents;
		}

		# Juanma (22/05/2014) Fix: If remove void agents setted
		$_sql_post = ' 1=1 ';
		if ($show_void_agents == 0) {
			
			$_sql_post .= ' AND id_agente IN (SELECT a.id_agente FROM tagente a, tagente_modulo b WHERE a.id_agente=b.id_agente AND b.delete_pending=0) AND \'1\'';
			$filter[$_sql_post] = '1';
			
		}
		
		if ( $id_group == 0 && $privilege != '') {
			$groups = users_get_groups ($config["id_user"], $privilege, false);
			//  if group ID doesn't matter and $privilege is specified (like 'AW'),
			//  retruns all agents that current user has $privilege privilege for.
			$agents = agents_get_group_agents(array_keys($groups), $filter, "none", false, $recursion, false, '|', $add_alert_bulk_op);
		}
		else {
			$agents = agents_get_group_agents($id_group, $filter, "none",
				false, $recursion, false, '|', $add_alert_bulk_op);
		}
		
		// Add keys prefix
		if ($keys_prefix !== "") {
			foreach($agents as $k => $v) {
				$agents[$keys_prefix . $k] = $v;
				unset($agents[$k]);
			}
		}
		
		echo json_encode ($agents);
		return;
	}
	
	return;
}

if (! check_acl($config['id_user'], 0, "AW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Group Management");
	require ("general/noaccess.php");
	return;
}

// Header
if (defined('METACONSOLE')) {
	
	agents_meta_print_header();
	$sec = 'advanced';
	
	echo '<div class="notify">';
	echo __("Edit or delete groups can cause problems with synchronization");
	echo '</div>';
	
}
else {
	
	ui_print_page_header (__("Groups defined in Pandora"),
		"images/group.png", false, "", true, "");
	$sec = 'gagente';

}


$create_group = (bool) get_parameter ('create_group');
$update_group = (bool) get_parameter ('update_group');
$delete_group = (bool) get_parameter ('delete_group');
$pure = get_parameter('pure', 0);

/* Create group */
if (($create_group) && (check_acl($config['id_user'], 0, "PM"))) {
	$name = (string) get_parameter ('name');
	$icon = (string) get_parameter ('icon');
	$id_parent = (int) get_parameter ('id_parent');
	$group_pass = (string)get_parameter('group_pass');
	$alerts_disabled = (bool) get_parameter ('alerts_disabled');
	$custom_id = (string) get_parameter ('custom_id');
	$skin = (string) get_parameter ('skin');
	$description = (string) get_parameter ('description');
	$contact = (string) get_parameter ('contact');
	$other = (string) get_parameter ('other');
	$check = db_get_value('nombre', 'tgrupo', 'nombre', $name);
	$propagate = (bool) get_parameter('propagate');
	
	/*Check if name field is empty*/
	if ($name != "") {
		if (!$check) {
			$values = array(
				'nombre' => $name,
				'icon' => empty($icon) ? '' : substr ($icon, 0, -4),
				'parent' => $id_parent,
				'disabled' => $alerts_disabled,
				'custom_id' => $custom_id,
				'description' => $description,
				'contact' => $contact,
				'propagate' => $propagate,
				'other' => $other
			);
			
			if(enterprise_installed()){
				$values['id_skin'] = $skin;
				$values['password'] = io_safe_input($group_pass);
			}
		
			$result = db_process_sql_insert('tgrupo', $values);
			if ($result) {
				ui_print_success_message(__('Group successfully created'));
			}
			else {
				ui_print_error_message(__('There was a problem creating group'));
			}
		}
		else {
			ui_print_error_message(__('Each group must have a different name'));
		}
	}
	else {
		//$result = false;
		ui_print_error_message(__('Group must have a name'));
	}
}

/* Update group */
if ($update_group) {
	$id_group = (int) get_parameter ('id_group');
	$name = (string) get_parameter ('name');
	$icon = (string) get_parameter ('icon');
	$id_parent = (int) get_parameter ('id_parent');
	$description = (string) get_parameter ('description');
	$group_pass = (string)get_parameter('group_pass');
	$alerts_enabled = (bool) get_parameter ('alerts_enabled');
	$custom_id = (string) get_parameter ('custom_id');
	$propagate = (bool) get_parameter('propagate');
	$skin = (string) get_parameter ('skin');
	$description = (string) get_parameter ('description');
	$contact = (string) get_parameter ('contact');
	$other = (string) get_parameter ('other');
	
	/*Check if name field is empty*/
	if ( $name != "") {
		switch ($config["dbtype"]) {
			case "mysql":
				$sql = sprintf ('UPDATE tgrupo  SET nombre = "%s",
					icon = "%s", disabled = %d, parent = %d, custom_id = "%s", propagate = %d, id_skin = %d, description = "%s", contact = "%s", other = "%s", password = "%s"
					WHERE id_grupo = %d',
					$name, empty($icon) ? '' : substr ($icon, 0, -4), !$alerts_enabled, $id_parent, $custom_id, $propagate, $skin, $description, $contact, $other, $group_pass, $id_group);
				break;
			case "postgresql":
			case "oracle":
				$sql = sprintf ('UPDATE tgrupo  SET nombre = \'%s\',
					icon = \'%s\', disabled = %d, parent = %d, custom_id = \'%s\', propagate = %d, id_skin = %d, description = \'%s\', contact = \'%s\', other = \'%s\'
					WHERE id_grupo = %d',
					$name, substr ($icon, 0, -4), !$alerts_enabled, $id_parent, $custom_id, $propagate, $skin, $description, $contact, $other, $id_group);
				break;
		}
		$result = db_process_sql ($sql);
	}
	else {
		$result = false;
	}
	
	if ($result !== false) {
		ui_print_success_message(__('Group successfully updated'));
	}
	else {
		ui_print_error_message(__('There was a problem modifying group'));
	}
}

/* Delete group */
if (($delete_group) && (check_acl($config['id_user'], 0, "PM"))) {
	$id_group = (int) get_parameter ('id_group');
	
	$usedGroup = groups_check_used($id_group);
	
	if (!$usedGroup['return']) {
		$group = db_get_row_filter('tgrupo',
			array('id_grupo' => $id_group));
		
		db_process_sql_update('tgrupo',
			array('parent' => $group['parent']), array('parent' => $id_group));
		
		$result = db_process_sql_delete('tgroup_stat',
			array('id_group' => $id_group));
		
		$result = db_process_sql_delete('tgrupo',
			array('id_grupo' => $id_group));
	}
	else {
		ui_print_error_message(
			sprintf(__('The group is not empty. It is use in %s.'), implode(', ', $usedGroup['tables'])));
	}
	
	if ($result && (!$usedGroup['return'])) {
		ui_print_success_message(__('Group successfully deleted'));
	} 
	else {
		ui_print_error_message(__('There was a problem deleting group'));
	}
}
db_clean_cache();
$groups = users_get_groups_tree ($config['id_user'], "AR", true);

$table->width = '100%';

$all_parents = array();
$groups_count = 0;
$sons = array();



foreach ($groups as $k => $g) {
	if ($g['parent'] != 0) {
		$all_parents[$g['parent']] = $g['parent'];
	}
}
krsort($all_parents);
foreach ($all_parents as $parent) {
	foreach ($groups as $k => $g) {
		if ($g['parent'] == $parent) {
			$sons[$g['parent']][] = $g;
			unset($groups[$k]);
		}
	}
}


$groups_count = count($groups);

if (!empty($groups)) {
	$table->class = "databox data";
	$table->head = array ();
	$table->head[0] = __('Name');
	$table->head[1] = __('ID');
	$table->head[2] = __('Icon');
	$table->head[3] = __('Alerts');
	$table->head[4] = __('Description');
	$table->head[5] = __('Actions');
	$table->align = array ();
	$table->align[2] = 'left';
	$table->align[5] = 'left';
	$table->size[4] = '30%';
	$table->size[5] = '10%';
	$table->data = array ();
	
	$offset = (int)get_parameter('offset', 0);
	$limit = $offset + $config['block_size'];
	
	
	
	$pagination = ui_pagination($groups_count,
		false, 0, $config['block_size'], true, 'offset', false);
	
	$n = -1;
	$iterator = 0;
	$branch_classes = array();
	foreach ($groups as $group) {
		$n++;
		
		// Only print the page range
		if ($n < $offset || $n >= $limit) {
			continue;
		}
		
		$symbolBranchs = ' symbol_branch_' . $group['parent'];
		
		$data = groups_get_group_to_list($group, $groups_count, $symbolBranchs);
		array_push ($table->data, $data);
		$table->rowstyle[$iterator] = '';
		if ($group['id_grupo'] != 0) {
			$branch_classes[$group['id_grupo']] = ' branch_0';
			$table->rowclass[$iterator] = 'parent_' . $group['parent'] . ' branch_0';
		}
		$iterator++;
		
		groups_print_group_sons($group, $sons, $branch_classes,
			$groups_count, $table, $iterator, $symbolBranchs);
	}
	
	echo $pagination;
	
	html_print_table ($table);
	
	echo $pagination;
}
else {
	ui_print_info_message ( array('no_close'=>true, 'message'=>  __('There are no defined groups') ) );
}

if (check_acl($config['id_user'], 0, "PM")) {
	echo '<form method="post" action="index.php?sec='.$sec.'&sec2=godmode/groups/configure_group&pure='.$pure.'">';
	echo '<div class="action-buttons" style="width: '.$table->width.'">';
	html_print_submit_button (__('Create group'), 'crt', false, 'class="sub next"');
	echo '</div>';
	echo '</form>';
}

enterprise_hook('close_meta_frame');

?>

<script type="text/javascript">
function showBranch(parent) {
	display = $('.parent_' + parent).css('display');
	
	if (display != 'none') {
		$('.symbol_' + parent).html('+');
		$('.parent_' + parent).css('display', 'none');
		
		//Close the child branch too
		$('.branch_' + parent).css('display', 'none');
		$('.symbol_branch_' + parent).html('+');
	}
	else {
		$('.symbol_' + parent).html('-');
		$('.parent_' + parent).css('display', '');
	}
}
</script>
