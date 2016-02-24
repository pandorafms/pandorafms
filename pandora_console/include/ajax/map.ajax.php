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

	require_once($config['homedir'] . "/include/class/Node.class.php");

	$getNodeData = (bool)get_parameter('getNodeData', 0);

	if ($getNodeData) {
		$id_node_data = (int)get_parameter('id_node_data');
		$type = (int)get_parameter('type');
		$id_map = (int)get_parameter('id_map');
		$data_graph_id = (int)get_parameter('data_graph_id');

		$return_data = '';

		switch ($type) {
			case ITEM_TYPE_AGENT_NETWORKMAP:
				$node_data = db_get_all_rows_sql("SELECT *
													FROM tagente
													WHERE id_agente = " . $id_node_data);
				$node_data = $node_data[0];
				if (!empty($node_data)) {
					$return_data .= '<div id="agent_data_to_show_"' . $node_data['id_agente'] .'>';
					$return_data .= '<span><strong>Agent: </strong>' . $node_data['nombre'] . '</span></br>';
					$return_data .= '<span><strong>IP Addres: </strong>' . $node_data['direccion'] . '</span></br>';
					$agent_os = db_get_row_sql("SELECT name FROM tconfig_os WHERE id_os = " . $node_data['id_os']);
					$agent_os = $agent_os['name'];
					$return_data .= '<span><strong>OS: </strong>' . $agent_os . ' ' . $node_data['os_version'] .'</span></br>';
					$return_data .= '<span><strong>Description: </strong>' . $node_data['comentarios'] . '</span></br>';
					$agent_group = db_get_row_sql("SELECT nombre FROM tgrupo WHERE id_grupo = " . $node_data['id_grupo']);
					$agent_group = $agent_group['nombre'];
					$return_data .= '<span><strong>Group: </strong>' . $agent_group . '</span></br>';
					$return_data .= '<span><strong>Agent Version: </strong>' . $node_data['agent_version'] . '</span></br>';
					$return_data .= '<span><strong>Last Contact: </strong>' . $node_data['ultimo_contacto'] . '</span></br>';
					$return_data .= '<span><strong>Remote: </strong>' . $node_data['ultimo_contacto_remoto'] . '</span>';
					$return_data .= '</div>';
				}
				else {
					$return_data = '<span>No data to show</span>';
				}
				break;
			case ITEM_TYPE_MODULE_NETWORKMAP:
				$node_data = db_get_all_rows_sql("SELECT *
													FROM tagente_modulo
													WHERE id_agente_modulo = " . $id_node_data);
				$node_data = $node_data[0];
				if (!empty($node_data)) {
					$return_data .= '<div id="module_data_to_show_"' . $node_data['id_agente'] .'>';
					$return_data .= '<span><strong>Module: </strong>' . $node_data['nombre'] . '</span></br>';
					$agent_module = db_get_row_sql("SELECT nombre FROM tagente WHERE id_agente = " . $node_data['id_agnte']);
					$agent_module = $agent_module['nombre'];
					$return_data .= '<span><strong>Agent: </strong>' . $agent_module . '</span></br>';
					$return_data .= '<span><strong>Description: </strong>' . $node_data['descripcion'] . '</span>';
					$return_data .= '</div>';
				}
				else {
					$return_data = '<span>No data to show</span>';
				}
				break;
		}

		sleep(1);
		echo json_encode($return_data);
		return;
	}

}
?>
