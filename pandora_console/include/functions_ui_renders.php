<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


/**
 */
function render_agent_field ($agent, $field, $field_value = false, $return = false) {
	global $config;
	
	if (empty ($agent))
		return '';
	
	$output = '';
	switch ($field) {
	case 'group_name':
		if (! isset ($agent['id_grupo']))
			return '';
		
		$output = get_group_name ($agent['id_grupo'], true);
		
		break;
	case 'group_icon':
		if (! isset ($agent['id_grupo']))
			return '';
		$output = print_group_icon ($agent['id_grupo'], true);
		
		break;
	case 'group':
		if (! isset ($agent['id_grupo']))
			return '';
		
		$output = print_group_icon ($agent['id_grupo'], true);
		$output .= ' ';
		$output .= get_group_name ($agent['id_grupo']);
		
		break;
	case 'view_link':
		if (! isset ($agent['nombre']))
			return '';
		if (! isset ($agent['id_agente']))
			return '';
		
		$output = '<a class="agent_link" id="agent-'.$agent['id_agente'].'" href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$agent['id_agente'].'">';
		$output .= $agent['nombre'];
		$output .= '</a>';
		
		break;
	case 'name':
		if (! isset ($agent['nombre']))
			return '';
		
		$output = $agent['nombre'];
		
		break;
	case 'status':
		if (! isset ($agent['id_agente']))
			return print_status_image (STATUS_AGENT_NO_DATA, '', $return);
		
		require_once ('include/functions_reporting.php');
		$info = get_agent_module_info ($agent['id_agente']);
		$output = $info['status_img'];
		
		break;
	case 'ajax_link':
		if (! $field_value || ! is_array ($field_value))
			return '';
		
		if (! isset ($field_value['callback']))
			return '';
		
		if (! isset ($agent['id_agente']))
			return '';
		
		$parameters = $agent['id_agente'];
		if (isset ($field_value['parameters']))
			$parameters = implode (',', $field_value['parameters']);
		
		$text = __('Action');
		if (isset ($field_value['name']))
			$text = $field_value['name'];
		
		if (isset ($field_value['image']))
			$text = print_image ($field_value['image'], true, array ('title' => $text));
		
		$output = '<a href="#" onclick="'.$field_value['callback'].'(this, '.$parameters.'); return false"">';
		$output .= $text;
		$output .= '</a>';
		
		break;
	default:
		if (! isset ($agent[$field]))
			return '';
		
		$ouput = $agent[$field];
	}
	
	if ($return)
		return $output;
	echo $output;
}
?>
