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
	
	
	$getNodeData = (bool)get_parameter('getNodeData', 0);
	$getNodeDetails = (bool)get_parameter('getNodeDetails', 0);
	$printEditNodeTable = (bool)get_parameter('printEditNodeTable', 0);
	$printEditMapTable = (bool)get_parameter('printEditMapTable', 0);
	$refresh_nodes_open = (bool)get_parameter('refresh_nodes_open', 0);
	
	if ($refresh_nodes_open) {
		$return = array();
		
		$id_map = (int)get_parameter('id_map', 0);
		$nodes = (array)get_parameter('nodes', array());
		
		
		$status = agents_get_status($node);
		$status = 1;
		
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
		
		$table = new stdClass();
		$table->id = 'node_options_' . $node_id;
		$table->width = "100%";

		$table->head = array();
		
		$node_name = __('No name');
		if ($type == ITEM_TYPE_AGENT_NETWORKMAP) {
			$node_name = agents_get_name($id_node_data);
			$table->head['type'] = __('Agent');
		}
		else {
			$node_name = db_get_all_rows_sql("SELECT nombre
												FROM tagente_modulo
												WHERE id_agente_modulo = " . $id_node_data);
			$node_name = $node_name[0];
			$table->head['type'] = __('Module');
		}
		$table->head['name'] = $node_name;
		
		$node = db_get_all_rows_sql("SELECT style FROM titem WHERE id = " . $id_node_data);
		$node = $node[0];
		$node_style = json_decode($node);
		
		$table->data = array();
		$table->data[0][0] = __('Label');
		$table->data[0][1] = html_print_input_text('label',
			$node_style['label'], '', 5, 10, true);
		$table->data[3][0] = __('Shape');
		$table->data[3][1] = html_print_select(array(
			'circle' => __('Circle'),
			'square' => __('Square'),
			'rhombus' => __('Rhombus')), 'shape', '',
			'javascript:', '', 0, true) . '&nbsp;' .
			'<span id="shape_icon_in_progress" style="display: none;">' . 
				html_print_image('images/spinner.gif', true) . '</span>' .
			'<span id="shape_icon_correct" style="display: none;">' .
				html_print_image('images/dot_green.png', true) . '</span>' .
			'<span id="shape_icon_fail" style="display: none;">' .
				html_print_image('images/dot_red.png', true) . '</span>';

		ui_toggle(html_print_table($table, true), __('Node options'),
			__('Node options'), true);
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
