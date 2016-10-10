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
		"Trying to access networkmap");
	require ($config["homedir"]."/general/noaccess.php");
	return;
}

include_once("include/functions_networkmap.php");
include_once("include/functions_pandora_networkmap.php");

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
				"Trying to access networkmap");
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
				"Trying to access networkmap");
			require ("general/noaccess.php");
			return;
		}
		
		$name = (string) get_parameter('name', '');
		
		// Default size values
		$width = 4000;
		$height = 4000;
		
		$method = (string) get_parameter('method', 'twopi');
		
		$recon_task_id = (int) get_parameter(
			'recon_task_id', 0);
		$ip_mask = get_parameter(
			'ip_mask', '');
		$source = (string)get_parameter('source', 'group');
		$dont_show_subgroups = (int)get_parameter('dont_show_subgroups', 0);
		$node_radius = (int)get_parameter('node_radius', 40);
		$description = get_parameter('description', '');
		
		$values = array();
		$values['name'] = $name;
		$values['id_group'] = $id_group;
		$values['source_period'] = 60;
		$values['width'] = $width;
		$values['height'] = $height;
		$values['id_user'] = $config['id_user'];
		$values['description'] = $description;
		
		switch ($method) {
			case 'twopi':
				$values['generation_method'] = 2;
				break;
			case 'dot':
				$values['generation_method'] = 1;
				break;
			case 'circo':
				$values['generation_method'] = 0;
				break;
			case 'neato':
				$values['generation_method'] = 3;
				break;
			case 'fdp':
				$values['generation_method'] = 4;
				break;
			case 'radial_dinamic':
				$values['generation_method'] = 6;
				break;
			default:
				$values['generation_method'] = 2;
				break;
		}
		
		if ($source == 'group') {
			$values['source'] = 0;
			$values['source_data'] = $id_group;
		}
		else if ($source == 'recon_task') {
			$values['source'] = 1;
			$values['source_data'] = $recon_task_id;
		}
		else if ($source == 'ip_mask') {
			$values['source'] = 2;
			$values['source_data'] = $ip_mask;
		}
		
		if (!$networkmap_write && !$networkmap_manage) {
			db_pandora_audit("ACL Violation",
				"Trying to access networkmap");
			require ("general/noaccess.php");
			return;
		}
		
		$filter = array();
		$filter['dont_show_subgroups'] = $dont_show_subgroups;
		$filter['node_radius'] = $node_radius;
		$values['filter'] = json_encode($filter);
		
		$result = false;
		if (!empty($name)) {
			$result = db_process_sql_insert('tmap',
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
			"Trying to access networkmap");
		require ("general/noaccess.php");
		return;
	}
	
	$id_group_old = db_get_value('id_group', 'tmap', 'id', $id);
	if ($id_group_old === false) {
		db_pandora_audit("ACL Violation",
			"Trying to accessnode graph builder");
		require ("general/noaccess.php");
		return;
	}
	
	// ACL for the network map
	$networkmap_write = check_acl ($config['id_user'], $id_group_old, "MW");
	$networkmap_manage = check_acl ($config['id_user'], $id_group_old, "MM");
	
	if (!$networkmap_write && !$networkmap_manage) {
		db_pandora_audit("ACL Violation",
			"Trying to access networkmap");
		require ("general/noaccess.php");
		return;
	}
	
	if ($update_networkmap) {
		$id_group = (int) get_parameter('id_group', 0);
		
		// ACL for the new network map
		$networkmap_write_new = check_acl ($config['id_user'], $id_group, "MW");
		$networkmap_manage_new = check_acl ($config['id_user'], $id_group, "MM");
		
		if (!$networkmap_write && !$networkmap_manage) {
			db_pandora_audit("ACL Violation",
				"Trying to access networkmap");
			require ("general/noaccess.php");
			return;
		}
		
		$name = (string) get_parameter('name', '');
		$method = (string) get_parameter('method', 'twopi');
		
		$recon_task_id = (int) get_parameter(
			'recon_task_id', 0);
			
		$source = (string)get_parameter('source', 'group');
		
		$values = array();
		$values['name'] = $name;
		$values['id_group'] = $id_group;
		if ($source == 'group') {
			$values['source'] = 0;
			$values['source_data'] = $id_group;
		}
		else if ($source == 'recon_task') {
			$values['source'] = 1;
			$values['source_data'] = $recon_task_id;
		}
		else if ($source == 'ip_mask') {
			$values['source'] = 2;
			$values['source_data'] = $ip_mask;
		}
		$description = get_parameter('description', '');
		$values['description'] = $description;
		
		$dont_show_subgroups = (int)get_parameter('dont_show_subgroups', 0);
		$node_radius = (int)get_parameter('node_radius', 40);
		$row = db_get_row('tmap', 'id', $id);
		$filter = json_decode($row['filter'], true);
		$filter['dont_show_subgroups'] = $dont_show_subgroups;
		$filter['node_radius'] = $node_radius;
		
		$values['filter'] = json_encode($filter);
		
		$result = false;
		if (!empty($name)) {
			$result = db_process_sql_update('tmap',
				$values, array('id' => $id));
		}
		
		$result_txt = ui_print_result_message($result,
			__('Succesfully updated'), __('Could not be updated'), '',
			true);
		
		if ($result) {
			$networkmap_write = $networkmap_write_new;
			$networkmap_manage = $networkmap_manage_new;
		}
	}
	if ($copy_networkmap) {
		$id = (int) get_parameter('id_networkmap', 0);
		
		$result = duplicate_networkmap($id);
		$result_txt = ui_print_result_message($result,
			__('Succesfully duplicate'), __('Could not be duplicated'), '',
			true);
	}
	if ($delete) {
		$id = (int)get_parameter('id_networkmap', 0);
		
		$result = networkmap_delete_networkmap($id);
		
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
		$old_networkmaps_enterprise = array();
		$old_networkmaps_open = array();
		
		if (enterprise_installed()) {
			$old_networkmaps_enterprise = db_get_all_rows_sql("SELECT id FROM tnetworkmap_enterprise");
		}
		$old_networkmaps_open = db_get_all_rows_sql("SELECT id_networkmap FROM tnetwork_map");
		
		foreach ($old_networkmaps_enterprise as $old_map_ent) {
			if (!map_migrated($old_map_ent['id'])) {
				if (enterprise_installed()) {
					enterprise_include_once ('include/functions_pandora_networkmap.php');
					
					$return = migrate_older_networkmap_enterprise($old_map_ent['id']);
					
					if (!$return) {
						break;
					}
				}
			}
		}
		
		foreach ($old_networkmaps_enterprise as $old_map_ent) {
			if (!map_migrated($old_map_ent['id'])) {
				$return = migrate_older_open_maps($old_map_ent['id']);
			}
			
			if (!$return) {
				break;
			}
		}
		
		ui_print_page_header(__('Networkmap'),
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
		
		$network_maps = db_get_all_rows_filter('tmap',
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
				
				$data = array();
				if ($network_map['generation_method'] == 6) {
					$data['name'] = '<a href="index.php?' .
						'sec=network&' .
						'sec2=operation/agentes/networkmap.dinamic&' .
						'activeTab=radial_dynamic&' .
						'id_networkmap=' . $network_map['id'] . '">' .
						$network_map['name'] . '</a>';
				}
				else {
					$data['name'] = '<a href="index.php?' .
						'sec=network&' .
						'sec2=operation/agentes/pandora_networkmap&' .
						'tab=view&' .
						'id_networkmap=' . $network_map['id'] . '">' .
						$network_map['name'] . '</a>';
				}
				
				if ($network_map['id_group'] > 0) {
					$nodes = db_get_all_rows_sql("SELECT style FROM titem WHERE id_map = " . $network_map['id'] . " AND deleted = 0");
					$count = 0;
					foreach ($nodes as $node) {
						$node_style = json_decode($node['style'], true);
						if ($node_style['id_group'] == $network_map['id_group']) {
							$count++;
						}
					}
				}
				else {
					$count = db_get_value_sql(
						'SELECT COUNT(*)
						FROM titem
						WHERE id_map = ' . $network_map['id'] . ' AND deleted = 0');
				}
				
				if (empty($count))
					$count = 0;
				
				if (($count == 0) && ($network_map['source'] != 'empty')) {
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
			echo "<div style='width: " . $table->width . "; margin-top: 5px;'>";
			echo '<form method="post" action="index.php?sec=network&amp;sec2=operation/agentes/pandora_networkmap">';
			html_print_input_hidden ('new_networkmap', 1);
			html_print_submit_button (__('Create networkmap'), 'crt', false, 'class="sub next" style="float: right;"');
			echo "</form>";
			echo "</div>";
		}
		
		break;
}
?>
