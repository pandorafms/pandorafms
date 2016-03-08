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
		$type = (int)get_parameter('type');
		$id_map = (int)get_parameter('id_map');
		$data_graph_id = (int)get_parameter('data_graph_id');
		$node_id = get_parameter('node_id');

		switch ($type) {
			case ITEM_TYPE_AGENT_NETWORKMAP:
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
				
				$details = str_replace(
					"{body}",
					$body,
					$details);

				break;
			case ITEM_TYPE_MODULE_NETWORKMAP:
				$node_data = db_get_all_rows_sql("SELECT descripcion
													FROM tagente_modulo
													WHERE id_agente_modulo = " . $id_node_data);

				$node_data = $node_data[0];

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
					modules_get_agentmodule_name($id_node_data),
					$details);

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

				$details = str_replace(
					"{body}",
					$body,
					$details);
				break;
		}

		echo json_encode($details);
		return;
	}

}
?>
