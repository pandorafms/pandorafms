<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2012 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Only accesible by ajax
if (is_ajax ()) {
	global $config;
	
	// Login check
	check_login ();
	
	require_once($config['homedir'] . "/include/functions_os.php");
	if (enterprise_installed()) {
		enterprise_include_once ('include/functions_policies.php');
		enterprise_include_once ('include/functions_networkmap_enterprise.php');
	}
	
	$getNodeData = (bool)get_parameter('getNodeData', 0);
	$getNodeDetails = (bool)get_parameter('getNodeDetails', 0);
	$printEditNodeTable = (bool)get_parameter('printEditNodeTable', 0);
	$printEditMapTable = (bool)get_parameter('printEditMapTable', 0);
	$refresh_nodes_open = (bool)get_parameter('refresh_nodes_open', 0);
	$refresh_arrows_open = (bool)get_parameter('refresh_arrows_open', 0);
	$update_node = (bool)get_parameter('update_node', 0);
	$delete_node = (bool)get_parameter('delete_node', 0);
	$add_relation = (bool)get_parameter('add_relation', 0);
	$update_node_position = (bool)get_parameter('update_node_position', 0);
	$update_node_size = (bool)get_parameter('update_node_size', 0);
	
	if ($update_node) {
		$id_node_data = (int)get_parameter('id_node_data');
		$label = (string)get_parameter('label', '');
		$shape = (string)get_parameter('shape', '');
		$return_update = networkmap_enterprise_update_data($id_node_data, $label, $shape);
		
		return;
	}
	
	if ($delete_node) {
		$id_node_data = (int)get_parameter('id_node_data');
		$return_delete = networkmap_enterprise_delete_node($id_node_data);
		
		echo json_encode($return_delete);
		return;
	}
	
	if ($add_relation) {
		$id_node_data_a = (int)get_parameter('id_node_data');
		$id_node_data_b = (int)get_parameter('id_node_data_b');
		$type = (int)get_parameter('type');
		
		$result_relation = networkmap_enterprise_add_relation($id_node_data_a, $id_node_data_b, $type);
		
		echo json_encode($result_relation);
		return;
	}
	
	if ($update_node_position) {
		$id_node_data = (int)get_parameter('id_node_data');
		$exit_holding_area = (bool)get_parameter('exit_holding_area');
		$new_pos_x = (int)get_parameter('new_pos_x');
		$new_pos_y = (int)get_parameter('new_pos_y');
		
		$result_update_position = networkmap_enterprise_update_position($id_node_data, $new_pos_x, $new_pos_y);
		
		if ($exit_holding_area) {
			networkmap_enterprise_exit_holding_area($id_node_data);
		}
		
		echo json_encode($result_update_position);
		return;
	}
	
	if ($update_node_size) {
		$id_node_data = (int)get_parameter('id_node_data');
		$new_width = (int)get_parameter('new_width');
		$new_height = (int)get_parameter('new_height');
		$new_pos_x = (int)get_parameter('new_pos_x');
		$new_pos_y = (int)get_parameter('new_pos_y');
		
		$result_update_size = networkmap_enterprise_update_size($id_node_data, $new_width, $new_height, $new_pos_x, $new_pos_y);
		
		echo json_encode($result_update_size);
		return;
	}
	
	if ($refresh_arrows_open) {
		$return = array();
		
		$id_map = (int)get_parameter('id_map', 0);
		$arrows = (array)get_parameter('arrows', array());
		
		foreach ($arrows as $arrow) {
			$temp = array();
			
			if (!empty($arrow['to_module']))
				$status = modules_get_agentmodule_status($arrow['to_module']);
			else
				$status = null;
			$temp['to_status'] = $status;
			
			if (!empty($arrow['to_module']))
				$status = modules_get_agentmodule_status($arrow['to_module']);
			else
				$status = null;
			$temp['from_status'] = $status;
			
			$temp['graph_id'] = $arrow['graph_id'];
			
			$return[$arrow['graph_id']] = $temp;
		}
		
		echo json_encode($return);
		return;
	}
	
	if ($refresh_nodes_open) {
		$return = array();
		
		$id_map = (int)get_parameter('id_map', 0);
		$nodes = (array)get_parameter('nodes', array());
		
		
		$status = agents_get_status($node);
		
		foreach ($nodes as $node) {
			switch ($status) {
				case AGENT_STATUS_NORMAL: 
					$color = COL_NORMAL;
					break;
				case AGENT_STATUS_CRITICAL:
					$color = COL_CRITICAL;
					break;
				case AGENT_STATUS_WARNING:
					$color = COL_WARNING;
					break;
				case AGENT_STATUS_ALERT_FIRED:
					$color = COL_ALERTFIRED;
					break;
				# Juanma (05/05/2014) Fix: Correct color for not init agents!
				case AGENT_STATUS_NOT_INIT:
					$color = COL_NOTINIT;
					break;
				default:
					//Unknown monitor
					$color = COL_UNKNOWN;
					break;
			}
			
			$return[$node] = array(
				'status' => $status,
				'color' => $color);
		}
		
		echo json_encode($return);
		return;
	}
	
	if ($getNodeData) {
		$id_node_data = (int)get_parameter('id_node_data');
		$type = (int)get_parameter('type');
		$id_map = (int)get_parameter('id_map');
		$data_graph_id = (int)get_parameter('data_graph_id');
		$node_id = get_parameter('node_id');
		
		ob_start();
		?>
		<div id="tooltip_{data_graph_id}">
			<div class="title_bar">
				<span class="title">{title}</span>
				
				<span class="close_click" onClick="javascript: close_button_tooltip('{data_graph_id}');">&#x2716;</span>
				<span class="open_click" onClick="javascript: tooltip_to_new_window('{data_graph_id}');">&#10138;</span>
			</div>
			<div class="body">
				{body}
			</div>
		</div>
		<?php
		$return_data = ob_get_clean();
		
		switch ($type) {
			case ITEM_TYPE_AGENT_NETWORKMAP:
				$return_data = str_replace(
					"{data_graph_id}",
					$data_graph_id,
					$return_data);
				$return_data = str_replace(
					"{node_id}",
					$node_id,
					$return_data);
				$return_data = str_replace(
					"{title}",
					agents_get_name($id_node_data),
					$return_data);
				
				
				$agent_group = groups_get_name(
					db_get_value('id_grupo', 'tagente', 'id_agente', $id_node_data));
				
				ob_start();
				?>
				<span>
					<strong><?php echo __('IP Address');?>: </strong>
					<?php echo agents_get_address($id_node_data);?>
				</span><br />
				<span>
					<strong><?php echo __('OS');?>: </strong>
					<?php echo os_get_name(agents_get_os($id_node_data));?>
				</span><br />
				<span>
					<strong><?php echo __('Description');?>: </strong>
					<?php echo db_get_value('comentarios', 'tagente', 'id_agente', $id_node_data);?>
				</span> <br/>
				<span>
					<strong><?php echo __('Group');?>: </strong>
					<?php echo $agent_group;?>
				</span><br />
				<span>
					<strong><?php echo __('Agent Version');?>: </strong>
					<?php echo db_get_value('agent_version', 'tagente', 'id_agente', $id_node_data);?>
				</span><br />
				<span>
					<strong><?php echo __('Last Contact');?>: </strong>
					<?php echo db_get_value('ultimo_contacto', 'tagente', 'id_agente', $id_node_data);?>
				</span><br />
				<span>
					<strong><?php echo __('Remote');?>: </strong>
					<?php echo db_get_value('ultimo_contacto_remoto', 'tagente', 'id_agente', $id_node_data);?>
				</span>
				<?php
				$body = ob_get_clean();
				
				$return_data = str_replace(
					"{body}",
					$body,
					$return_data);
				break;
			case ITEM_TYPE_MODULE_NETWORKMAP:
				$node_data = db_get_all_rows_sql("SELECT descripcion
													FROM tagente_modulo
													WHERE id_agente_modulo = " . $id_node_data);

				$node_data = $node_data[0];

				$return_data = str_replace(
					"{data_graph_id}",
					$data_graph_id,
					$return_data);
				$return_data = str_replace(
					"{node_id}",
					$node_id,
					$return_data);
				$return_data = str_replace(
					"{title}",
					modules_get_agentmodule_name($id_node_data),
					$return_data);

				ob_start();
				?>
				<span>
					<strong><?php echo __('Agent Name');?>: </strong>
					<?php echo agents_get_name(modules_get_agentmodule_agent($id_node_data));?>
				</span> <br/>
				<span>
					<strong><?php echo __('Description');?>: </strong>
					<?php echo db_get_value('descripcion', 'tagente_modulo', 'id_agente_modulo', $id_node_data);?>
				</span> <br/>
				<?php
				$body = ob_get_clean();

				$return_data = str_replace(
					"{body}",
					$body,
					$return_data);
				break;
			case ITEM_TYPE_GROUP_NETWORKMAP:
				$return_data = str_replace(
					"{data_graph_id}",
					$data_graph_id,
					$return_data);
				$return_data = str_replace(
					"{node_id}",
					$node_id,
					$return_data);
				$return_data = str_replace(
					"{title}",
					groups_get_name($id_node_data),
					$return_data);
				
				$parent = (int)db_get_value ('parent', 'tgrupo', 'id_grupo', (int)$id_node_data);
				$parent = groups_get_name($parent, true);
				
				$description = (string)db_get_value ('description', 'tgrupo', 'id_grupo', (int)$id_node_data);
				
				$alerts = (int)db_get_value ('disabled', 'tgrupo', 'id_grupo', (int)$id_node_data);
				if ($alerts == 0) {
					$alerts = __('Enabled');
				}
				else {
					$alerts = __('Disabled');
				}
				
				ob_start();
				?>
				<span>
					<strong><?php echo __('Parent');?>: </strong>
					<?php echo $parent;?>
				</span><br />
				<span>
					<strong><?php echo __('Description');?>: </strong>
					<?php echo $description;?>
				</span><br />
				<span>
					<strong><?php echo __('Alerts');?>: </strong>
					<?php echo $alerts;?>
				</span><br />
				<?php
				$body = ob_get_clean();
				
				$return_data = str_replace(
					"{body}",
					$body,
					$return_data);
				break;
			case ITEM_TYPE_POLICY_NETWORKMAP:
				$policy_name = (string)db_get_value ('name', 'tpolicies', 'id', (int)$id_node_data);
				
				$return_data = str_replace(
					"{data_graph_id}",
					$data_graph_id,
					$return_data);
				$return_data = str_replace(
					"{node_id}",
					$node_id,
					$return_data);
				$return_data = str_replace(
					"{title}",
					$policy_name,
					$return_data);
				
				$policy_agents = count(policies_get_agents($id_node_data));
				
				$group = (int)db_get_value('id_group', 'tpolicies', 'id', (int)$id_node_data);
				$group = (string)groups_get_name($group, true);
				
				$policy_status = (int)db_get_value('status', 'tpolicies', 'id', (int)$id_node_data);
				switch ($policy_status) {
					case POLICY_UPDATED:
						$policy_status = __('Updated');
						break;
					case POLICY_PENDING_DATABASE:
						$policy_status = __('Pending Database');
						break;
					case POLICY_PENDING_ALL:
						$policy_status = __('Pending');
						break;
				}
				
				ob_start();
				?>
				<span>
					<strong><?php echo __('Agents');?>: </strong>
					<?php echo $policy_agents;?>
				</span><br />
				<span>
					<strong><?php echo __('Group');?>: </strong>
					<?php echo $group;?>
				</span><br />
				<span>
					<strong><?php echo __('Status');?>: </strong>
					<?php echo $policy_status;?>
				</span><br />
				<?php
				$body = ob_get_clean();
				
				$return_data = str_replace(
					"{body}",
					$body,
					$return_data);
				break;
		}
		
		sleep(1);
		echo json_encode($return_data);
		return;
	}
	else if ($getNodeDetails) {
		$id_node_data = (int)get_parameter('id_node_data');
		$data_graph_id = (int)get_parameter('data_graph_id');
		$node_id = get_parameter('node_id');

		ob_start();
		?>
		<div id="details_{data_graph_id}">
			<div class="title_bar">
				<span class="title">{title}</span>
			</div>
			<div class="body">
				{body}
			</div>
		</div>
		<?php
		$details = ob_get_clean();

		$details = str_replace(
			"{data_graph_id}",
			$data_graph_id,
			$details);
		$details = str_replace(
			"{node_id}",
			$node_id,
			$details);
		$details = str_replace(
			"{title}",
			agents_get_name($id_node_data),
			$details);
		
		$agent_modules = agents_get_modules($id_node_data);
		$agent_modules = array_keys($agent_modules);
		$agent_interval = agents_get_interval ($id_node_data);
		
		$table = new stdClass();
		$table->width = '100%';
		$table->class = 'databox data';

		$table->head = array ();
		$table->head[0] = __('Name');
		$table->head[1] = __('Description');
		$table->head[2] = __('Type');
		$table->head[3] = __('Interval');
		$table->head[4] = __('Status');

		$table->rowstyle = array();
		$table->style = array ();
		$table->style[0] = 'font-weight: bold';
		$table->align = array ();
		$table->align[0] = 'center';
		$table->align[1] = 'center';
		$table->align[2] = 'center';
		$table->align[3] = 'center';
		$table->align[4] = 'center';
		$table->data = array ();
		foreach ($agent_modules as $module) {
			$data = array ();
			$status = '';
			$title = '';
			$module_data = db_get_all_rows_sql("SELECT nombre, id_tipo_modulo, descripcion, module_interval
												FROM tagente_modulo WHERE id_agente_modulo = " . $module);
			$module_data = $module_data[0];
			
			$module_status = db_get_row('tagente_estado', 'id_agente_modulo', $module);
			modules_get_status($module_status['id_agente_modulo'], $module_status['estado'], $module_status['datos'], $status, $title);
			
			$data[0] = $module_data['nombre'];
			$data[1] = $module_data['descripcion'];
			$data[2] = '';
			$type = $module_data['id_tipo_modulo'];
			if ($type) {
				$data[2] = ui_print_moduletype_icon($type, true);
			}
			if ($module_data['module_interval']) {
				$data[3] = human_time_description_raw($module_data['module_interval']);
			}
			else {
				$data[3] = human_time_description_raw($agent_interval);
			}
			$data[4] = ui_print_status_image($status, $title, true);;
			array_push ($table->data, $data);
		}
		$body = html_print_table ($table, true);			
		
		$details = str_replace(
			"{body}",
			$body,
			$details);

		echo json_encode($details);
		return;
	}
	else if ($printEditNodeTable) {
		?>
		<div title="<?php echo __('Edit node');?>">
			<div style="text-align: center; width: 100%;">
		<?php
		
		$id_node_data = (int)get_parameter('id_node_data');
		$type = (int)get_parameter('type');
		$node_id = get_parameter('node_id');
		$data_graph_id = (int)get_parameter('data_graph_id');
		
		$style = db_get_value('style', 'titem', 'id', $data_graph_id);
		$node_style = json_decode($style, true);
		
		$node_label = $node_style['label'];
		$node_shape = $node_style['shape'];
		
		$table = new stdClass();
		$table->id = 'node_options_' . $node_id;
		$table->width = "100%";
		$table->head = array();
		
		$node_name = __('No name');
		if ($type == ITEM_TYPE_AGENT_NETWORKMAP) {
			$node_name = agents_get_name($id_node_data);
			$table->head['type'] = __('Agent');
		}
		else if ($type == ITEM_TYPE_MODULE_NETWORKMAP) {
			$node_name = db_get_value('nombre', 'tagente_modulo', 'id_agente_modulo', $id_node_data);
			
			$table->head['type'] = __('Module');
		}
		else if ($type == ITEM_TYPE_GROUP_NETWORKMAP) {
			$node_name = db_get_value('nombre', 'tgrupo', 'id_grupo', $id_node_data);
			
			$table->head['type'] = __('Group');
		}
		else if ($type == ITEM_TYPE_POLICY_NETWORKMAP) {
			$node_name = db_get_value('name', 'tpolicies', 'id', $id_node_data);
			
			$table->head['type'] = __('Policy');
		}
		
		$table->head['name'] = $node_name;
		
		$table->data = array();
		$table->data[0][0] = __('Label');
		$table->data[0][1] = html_print_input_text('label',
			$node_label, '', 12, 255, true);
		$table->data[1][0] = __('Shape');
		$table->data[1][1] = html_print_select(array(
			'circle' => __('Circle'),
			'square' => __('Square'),
			'rhombus' => __('Rhombus')), 'shape', $node_shape, '', '', 0, true);
		
		html_print_table($table);
		echo '<div class="edit_node" style="float:right; margin-right: 10px;">';
		echo html_print_button(__('Update'), 'upd', false, '') . 
			ui_print_help_tip (__('This function is only fix in Enterprise version'));
		echo '</div>';
		?>
			</div>
		</div>
		<?php
		return;
	}
	else if ($printEditMapTable) {
		?>
		<div title="<?php echo __('Edit map');?>">
			<div style="text-align: center; width: 100%;">
		<?php
		$table = new stdClass();
		$table->id = 'map_options';
		$table->width = "100%";

		$table->head = array();
		$table->head['name'] = __('Nombre del mapa');
		$table->head['type'] = __('Tipo del mapa');

		$table->data = array();
		$table->data[0][0] = __('Capo1');
		$table->data[0][1] = __('Capo2');
		$table->data[1][0] = __('Capo1');
		$table->data[1][1] = __('Capo2');
		$table->data[2][0] = __('Capo1');
		$table->data[2][1] = __('Capo2');

		ui_toggle(html_print_table($table, true), __('Map options'),
			__('Map options'), true);
		?>
			</div>
		</div>
		<?php
		return;
	}

}
?>
