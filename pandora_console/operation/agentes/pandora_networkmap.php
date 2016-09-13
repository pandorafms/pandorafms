<?php
// ______                 __                     _______ _______ _______
//|   __ \.---.-.-----.--|  |.-----.----.---.-. |    ___|   |   |     __|
//|    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
//|___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
//
// ============================================================================
// Copyright (c) 2007-2010 Artica Soluciones Tecnologicas, http://www.artica.es
// This code is NOT free software. This code is NOT licenced under GPL2 licence
// You cannnot redistribute it without written permission of copyright holder.
// ============================================================================

// Load global variables
global $config;

// Check user credentials
check_login();

// General ACL for the network maps
$networkmaps_read = check_acl ($config['id_user'], 0, "MR");
$networkmaps_write = check_acl ($config['id_user'], 0, "MW");
$networkmaps_manage = check_acl ($config['id_user'], 0, "MM");

if (!$networkmaps_read && !$networkmaps_write && !$networkmaps_manage) {
	db_pandora_audit("ACL Violation",
		"Trying to access networkmap enterprise");
	require ($config["homedir"]."/general/noaccess.php");
	return;
}

include_once("include/functions_networkmap.php");
enterprise_include_once("include/functions_networkmap_enterprise.php");

$new_networkmap = (bool) get_parameter('new_networkmap', false);
$save_networkmap = (bool) get_parameter('save_networkmap', false);
$update_networkmap = (bool) get_parameter('update_networkmap', false);
$copy_networkmap = (bool) get_parameter('copy_networkmap', false);
$delete = (bool) get_parameter('delete', false);
$tab = (string) get_parameter('tab', 'list');

$result_txt = '';

// The networkmap doesn't exist yet
if ($new_networkmap || $save_networkmap) {
	if ($new_networkmap) {
		if ($networkmaps_write || $networkmaps_manage) {
			require('pandora_networkmap.editor.php');
			return;
		}
		else {
			db_pandora_audit("ACL Violation",
				"Trying to access networkmap enterprise");
			require ("general/noaccess.php");
			return;
		}
	}
	if ($save_networkmap) {
		$id_group = (int) get_parameter('id_group', 0);
		
		// ACL for the network map
		// $networkmap_read = check_acl ($config['id_user'], $id_group, "MR");
		$networkmap_write = check_acl ($config['id_user'], $id_group, "MW");
		$networkmap_manage = check_acl ($config['id_user'], $id_group, "MM");
		
		if (!$networkmap_write && !$networkmap_manage) {
			db_pandora_audit("ACL Violation",
				"Trying to access networkmap enterprise");
			require ("general/noaccess.php");
			return;
		}
		
		$name = (string) get_parameter('name', '');
		$generation_process = get_parameter('generation_process', 'group');
		$width = (int) get_parameter('width', 3000);
		$height = (int) get_parameter('height', 3000);
		$method = (string) get_parameter('method', 'twopi');
		$refresh_state = (int) get_parameter('refresh_state', 60);
		$l2_network_interfaces = (int) get_parameter(
			'l2_network_interfaces', 0);
		
		// --------- DEPRECATED ----------------------------------------
		$old_mode = (int)get_parameter('old_mode', 0);
		if ($old_mode) {
			$l2_network_interfaces = 0;
		}
		// --------- END DEPRECATED ------------------------------------
		
		$recon_task_id = (int) get_parameter(
			'recon_task_id', 0);
		$ip_mask = get_parameter(
			'ip_mask', '');
		$source_data = (string)get_parameter('source_data', 'group');
		$dont_show_subgroups = (int)get_parameter('dont_show_subgroups', 0);
		
		$values = array();
		$values['name'] = $name;
		$values['id_group'] = $id_group;
		
		if (!$networkmap_write && !$networkmap_manage) {
			db_pandora_audit("ACL Violation",
				"Trying to access networkmap enterprise");
			require ("general/noaccess.php");
			return;
		}
		
		$options = array();
		$options['refresh_state'] = 60;
		$options['width'] = $width;
		$options['height'] = $height;
		$options['method'] = $method;
		$options['generation_process'] = $generation_process;
		$options['refresh_state'] = $refresh_state;
		$options['l2_network_interfaces'] = $l2_network_interfaces;
		// --------- DEPRECATED ----------------------------------------
		$options['old_mode'] = $old_mode;
		// --------- END DEPRECATED ------------------------------------
		$options['recon_task_id'] = $recon_task_id;
		$options['ip_mask'] = $ip_mask;
		$options['dont_show_subgroups'] = $dont_show_subgroups;
		$options['source_data'] = $source_data;
		$values['options'] = json_encode($options);
		
		$result = false;
		if (!empty($name)) {
			$result = db_process_sql_insert('tnetworkmap_enterprise',
				$values);
		}
		
		$result_txt = ui_print_result_message($result,
			__('Succesfully created'), __('Could not be created'), '',
			true);
		
		// Force the tab = 'list'
		$tab = "list";
	}
}
// The networkmap exists
else if ($update_networkmap || $copy_networkmap || $delete) {
	$id = (int) get_parameter('id_networkmap', 0);
	
	// Networkmap id required
	if (empty($id)) {
		db_pandora_audit("ACL Violation",
			"Trying to access networkmap enterprise");
		require ("general/noaccess.php");
		return;
	}
	
	$id_group_old = db_get_value('id_group', 'tnetworkmap_enterprise', 'id', $id);
	if ($id_group_old === false) {
		db_pandora_audit("ACL Violation",
			"Trying to accessnode graph builder");
		require ("general/noaccess.php");
		return;
	}
	
	// ACL for the network map
	// $networkmap_read = check_acl ($config['id_user'], $id_group_old, "MR");
	$networkmap_write = check_acl ($config['id_user'], $id_group_old, "MW");
	$networkmap_manage = check_acl ($config['id_user'], $id_group_old, "MM");
	
	if (!$networkmap_write && !$networkmap_manage) {
		db_pandora_audit("ACL Violation",
			"Trying to access networkmap enterprise");
		require ("general/noaccess.php");
		return;
	}
	
	if ($update_networkmap) {
		$id_group = (int) get_parameter('id_group', 0);
		
		// ACL for the new network map
		// $networkmap_read_new = check_acl ($config['id_user'], $id_group, "MR");
		$networkmap_write_new = check_acl ($config['id_user'], $id_group, "MW");
		$networkmap_manage_new = check_acl ($config['id_user'], $id_group, "MM");
		
		if (!$networkmap_write && !$networkmap_manage) {
			db_pandora_audit("ACL Violation",
				"Trying to access networkmap enterprise");
			require ("general/noaccess.php");
			return;
		}
		
		$name = (string) get_parameter('name', '');
		$width = (int) get_parameter('width', 3000);
		$height = (int) get_parameter('height', 3000);
		$method = (string) get_parameter('method', 'twopi');
		$refresh_state = (int) get_parameter('refresh_state', 60);
		$l2_network_interfaces = (int) get_parameter(
			'l2_network_interfaces', 0);
		// --------- DEPRECATED ----------------------------------------
		$old_mode = (int)get_parameter('old_mode', 0);
		if ($old_mode) {
			$l2_network_interfaces = 0;
		}
		// --------- END DEPRECATED ------------------------------------
		$recon_task_id = (int) get_parameter(
			'recon_task_id', 0);
		$source_data = (string)get_parameter('source_data', 'group');
		$values = array();
		$values['name'] = $name;
		$values['id_group'] = $id_group;
		
		$row = db_get_row('tnetworkmap_enterprise', 'id', $id);
		$options = json_decode($row['options'], true);
		$options['width'] = $width;
		$options['height'] = $height;
		$options['refresh_state'] = $refresh_state;
		$options['l2_network_interfaces'] = $l2_network_interfaces;
		// --------- DEPRECATED ----------------------------------------
		$options['old_mode'] = $old_mode;
		// --------- END DEPRECATED ------------------------------------
		$options['recon_task_id'] = $recon_task_id;
		$options['source_data'] = $source_data;
		
		$values['options'] = json_encode($options);
		
		
		$result = false;
		if (!empty($name)) {
			$result = db_process_sql_update('tnetworkmap_enterprise',
				$values, array('id' => $id));
		}
		
		$result_txt = ui_print_result_message($result,
			__('Succesfully updated'), __('Could not be updated'), '',
			true);
		
		if ($result) {
			// $networkmap_read = $networkmap_read_new;
			$networkmap_write = $networkmap_write_new;
			$networkmap_manage = $networkmap_manage_new;
		}
	}
	if ($copy_networkmap) {
		$id = (int) get_parameter('id_networkmap', 0);
		
		$result = duplicate_networkmap_enterprise($id);
		$result_txt = ui_print_result_message($result,
			__('Succesfully duplicate'), __('Could not be duplicated'), '',
			true);
	}
	if ($delete) {
		$id = (int)get_parameter('id_networkmap', 0);
		
		$result = networkmap_enterprise_delete_networkmap($id);
		
		$result_txt = ui_print_result_message($result,
			__('Succesfully deleted'), __('Could not be deleted'), '',
			true);
	}
}

switch ($tab) {
	case 'edit':
		require('pandora_networkmap.editor.php');
		break;
	case 'view':
		require('pandora_networkmap.view.php');
		break;
	case 'list':
		ui_print_page_header(__('Networkmap enterprise'),
			"images/op_network.png", false, "network_map_enterprise",
			false);
		
		//Information to correct configuration
		ui_print_message (__('The default display will depend on the definition and topology detected by Pandora.'), 'info');
		
		echo $result_txt;
		
		$table = new stdClass();
		$table->width = "100%";
		$table->class = "databox data";
		$table->headstyle['copy'] = 'text-align: center;';
		$table->headstyle['edit'] = 'text-align: center;';
		
		$table->style = array();
		$table->style['name'] = '';
		$table->style['nodes'] = 'text-align: center;';
		$table->style['groups'] = 'text-align: left;';
		if ($networkmaps_write || $networkmaps_manage) {
			$table->style['copy'] = 'text-align: center;';
			$table->style['edit'] = 'text-align: center;';
			$table->style['delete'] = 'text-align: center;';
		}
		
		$table->size = array();
		$table->size['name'] = '60%';
		$table->size['nodes'] = '30px';
		$table->size['groups'] = '400px';
		if ($networkmaps_write || $networkmaps_manage) {
			$table->size['copy'] = '30px';
			$table->size['edit'] = '30px';
			$table->size['delete'] = '30px';
		}
		
		$table->head = array();
		$table->head['name'] = __('Name');
		$table->head['nodes'] = __('Nodes');
		$table->head['groups'] = __('Groups');
		if ($networkmaps_write || $networkmaps_manage) {
			$table->head['copy'] = __('Copy');
			$table->head['edit'] = __('Edit');
			$table->head['delete'] = __('Delete');
		}
		$id_groups = array_keys(users_get_groups());
		
		$network_maps = db_get_all_rows_filter('tnetworkmap_enterprise',
			array('id_group' => $id_groups));
		
		if ($network_maps !== false) {
			$table->data = array();
			
			foreach ($network_maps as $network_map) {
				// ACL for the network map
				$networkmap_read = check_acl ($config['id_user'], $network_map['id_group'], "MR");
				$networkmap_write = check_acl ($config['id_user'], $network_map['id_group'], "MW");
				$networkmap_manage = check_acl ($config['id_user'], $network_map['id_group'], "MM");
				
				if (!$networkmap_read && !$networkmap_write && !$networkmap_manage) {
					db_pandora_audit("ACL Violation",
						"Trying to access networkmap enterprise");
					require ("general/noaccess.php");
					return;
				}
				
				$options = json_decode($network_map['options'], true);
				
				$data = array();
				$data['name'] = '<a href="index.php?' .
					'sec=network&' .
					'sec2=operation/agentes/pandora_networkmap&' .
					'tab=view&' .
					'id_networkmap=' . $network_map['id'] . '">' .
					$network_map['name'] . '</a>';
				
				$count = db_get_value_sql(
					'SELECT COUNT(*)
					FROM tnetworkmap_enterprise_nodes
					WHERE id_networkmap_enterprise = ' . $network_map['id'] . ' AND
						id_agent_module = 0 AND id_module = 0 AND deleted = 0');
				if (empty($count))
					$count = 0;
				
				if (($count == 0) && ($options['generation_process'] != 'empty')) {
					$data['nodes'] = __('Pending to generate');
				}
				else {
					$data['nodes'] = $count;
				}
				
				$data['groups'] = ui_print_group_icon ($network_map['id_group'], true);
				
				if ($networkmap_write || $networkmap_manage) {
					$data['copy'] = '<a href="index.php?' .
						'sec=network&' .
						'sec2=operation/agentes/pandora_networkmap&amp;' .
						'copy_networkmap=1&' .
						'id_networkmap=' . $network_map['id'] . '" alt="' . __('Copy') . '">' .
						html_print_image("images/copy.png", true) . '</a>';
					$data['edit'] = '<a href="index.php?' .
						'sec=network&' .
						'sec2=operation/agentes/pandora_networkmap&' .
						'tab=edit&' .
						'edit_networkmap=1&' .
						'id_networkmap=' . $network_map['id'] . '" alt="' . __('Config') . '">' .
						html_print_image("images/config.png", true) . '</a>';
					$data['delete'] = '<a href="index.php?' .
						'sec=network&' .
						'sec2=operation/agentes/pandora_networkmap&' .
						'delete=1&' .
						'id_networkmap=' . $network_map['id'] . '" alt="' . __('Delete') . '" onclick="javascript: if (!confirm(\'' . __('Are you sure?') . '\')) return false;">' .
						html_print_image('images/cross.png', true) . '</a>';
				}
				
				$table->data[] = $data;
			}
			
			html_print_table($table);
		}
		else{
			ui_print_info_message ( array('no_close'=>true, 'message'=> __('There are no maps defined.') ) );
		}
		
		if ($networkmaps_write || $networkmaps_manage) {
			
			echo "<div style='width: " . $table->width . "; text-align: right; margin-top: 5px;'>";
			echo '<form method="post" action="index.php?sec=network&amp;sec2=operation/agentes/pandora_networkmap">';
			html_print_input_hidden ('new_networkmap', 1);
			html_print_submit_button (__('Create networkmap'), 'crt', false, 'class="sub next"');
			echo "</form>";
			echo "</div>";
			
		}
		break;
}
?>
