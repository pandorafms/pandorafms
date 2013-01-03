<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

//Remember the hard-coded values
/*
-- id_modulo now uses tmodule 
-- ---------------------------
-- 1 - Data server modules (agent related modules)
-- 2 - Network server modules
-- 4 - Plugin server
-- 5 - Predictive server
-- 6 - WMI server
-- 7 - WEB Server (enteprise)

In the xml is the tag "module_source"
*/

require_once ($config['homedir'].'/include/functions_agents.php');
enterprise_include_once('include/functions_local_components.php');

function insert_item_report($report_id, $values) {
	foreach ($report_id as $id => $name) {
		$values['id_report'] = $id;
		$result = (bool)db_process_sql_insert('treport_content', $values);
		
		ui_print_result_message($result,
			sprintf(__("Success add '%s' item in report '%s'."), $values['type'], $name),
			sprintf(__("Error create '%s' item in report '%s'."), $values['type'], $name));
	}
}

function process_upload_xml_report($xml, $group_filter = 0) {
	foreach ($xml->xpath('/report') as $reportElement) {
		
		$values = array();
		
		if (isset($reportElement->name)) {
			$values['name'] = $reportElement->name;
			
			$posible_name = $values['name'];
			$exist = true;
			$loops = 30; //Loops to exit or tries
			while ($exist && $loops > 0) {
				$exist = (bool)db_get_row_filter('treport', array('name' => io_safe_input($posible_name)));
				
				if ($exist) {
					$loops--;
					$posible_name = $values['name'] . " (" . (30 - $loops) . ")";
				}
			}
			
			if ($exist) {
				ui_print_error_message(
					sprintf(
						__("Error create '%s' report, the name exist and there aren't free name."),
						$reportElement->name));
				break;
			}
			else if ($loops != 30) {
				ui_print_error_message(
					sprintf(
						__("Warning create '%s' report, the name exist, the report have a name %s."),
						$reportElement->name, $posible_name));
			}
			
			$values['name'] = io_safe_input($posible_name);
		}
		else {
			ui_print_error_message(__("Error the report haven't name."));
			break;
		}
		
		$id_group = db_get_value('id_grupo', 'tgrupo', 'nombre', $reportElement->group);
		if ($id_group === false) {
			ui_print_error_message(__("Error the report haven't group."));
			break;
		}
		
		if (isset($reportElement->description))
			$values['description'] = $reportElement->description;
		
		$id_report = db_process_sql_insert ('treport', $values);
		
		ui_print_result_message($id_report,
			sprintf(__("Success create '%s' report."), $posible_name),
			sprintf(__("Error create '%s' report."), $posible_name));
		
		if ($id_report) {
			db_pandora_audit("Report management", "Create report " . $id_report, false, false);
		}
		else {
			db_pandora_audit("Report management", "Fail to create report", false, false);
			break;
		}
		
		foreach ($reportElement->item as $item) {
			$item = (array)$item;
			
			$values = array();
			$values['id_report'] = $id_report;
			if (isset($item['description']))
				$values['description'] = io_safe_input($item['description']);
			if (isset($item['period']))
				$values['period'] = io_safe_input($item['period']);
			if (isset($item['type']))
				$values['type'] = io_safe_input($item['type']);
			
			$agents_item= array();
			if (isset($item['agent'])) {
				$agents = agents_get_agents(array('id_grupo' => $group_filter),
					array('id_agente', 'nombre'));
				
				$agent_clean = str_replace(array('[', ']'), '', $item['agent']);
				$regular_expresion = ($agent_clean != $item['agent']);
				
				foreach ($agents as $agent) {
					if ($regular_expresion) {
						if ((bool)preg_match("/" . $agent_clean . "/", io_safe_output($agent['nombre']))) {
							$agents_item[$agent['id_agente']]['name'] = $agent['nombre']; 
						}
					}
					else {
						if ($agent_clean == io_safe_output($agent['nombre'])) {
							$agents_item[$agent['id_agente']]['name'] = $agent['nombre'];
						}
					}
				}
			}
			if (isset($item['module'])) {
				$module_clean = str_replace(array('[', ']'), '', $item['module']);
				$regular_expresion = ($module_clean != $item['module']);
				
				foreach ($agents_item as $id => $agent) {
					$modules = db_get_all_rows_filter('tagente_modulo',
						array('id_agente' => $id), array('id_agente_modulo', 'nombre'));
					
					$agents_item[$id]['modules'] = array();
					
					foreach ($modules as $module) {
						if ($regular_expresion) {
							if ((bool)preg_match("/" . $module_clean . "/", io_safe_output($module['nombre']))) {
								$agents_item[$id]['modules'][$module['id_agente_modulo']]['name'] = 
									$module['nombre']; 
							}
						}
						else {
							if ($module_clean == io_safe_output($module['nombre'])) {
								$agents_item[$id]['modules'][$module['id_agente_modulo']]['name'] = 
									$module['nombre']; 
							}
						}
					}
				}
			}
			
			switch ($item['type']) {
				case 1:
				case 'simple_graph':
					break;
				case 'simple_baseline_graph':
					break;
				case 2:
				case 'custom_graph':
					$group = db_get_value('id_grupo', 'tgrupo', 'nombre', io_safe_input($item['graph']));
					$values['id_gs'] = $group;
					break;
				case 3:
				case 'SLA':
					if (isset($item['only_display_wrong']))
						$values['only_display_wrong'] = (string)$item['only_display_wrong'];
					if (isset($item['monday']))
						$values['monday'] = (string)$item['monday'];
					if (isset($item['tuesday']))
						$values['tuesday'] = (string)$item['tuesday'];
					if (isset($item['wednesday']))
						$values['wednesday'] = (string)$item['wednesday'];
					if (isset($item['thursday']))
						$values['thursday'] = (string)$item['thursday'];
					if (isset($item['friday']))
						$values['friday'] = (string)$item['friday'];
					if (isset($item['saturday']))
						$values['saturday'] = (string)$item['saturday'];
					if (isset($item['sunday']))
						$values['sunday'] = (string)$item['sunday'];
					if (isset($item['time_from']))
						$values['time_from'] = (string)$item['time_from'];
					if (isset($item['time_to']))
						$values['time_to'] = (string)$item['time_to'];
					
					$slas = array();
					if (!isset($item['sla'])) {
						$item['sla'] = array();
					}
					foreach ($item['sla'] as $sla_xml) {
						if (isset($sla_xml->agent)) {
							$agents = agents_get_agents(array('id_grupo' => $group_filter), array('id_agente', 'nombre'));
							
							$agent_clean = str_replace(array('[', ']'), '', $sla_xml->agent);
							$regular_expresion = ($agent_clean != $sla_xml->agent);
							
							foreach ($agents as $agent) {
								$id_agent = false;
								if ($regular_expresion) {
									if ((bool)preg_match("/" . $agent_clean . "/", io_safe_output($agent['nombre']))) {
										$id_agent = $agent['id_agente'];
									}
									else {
										if ($agent_clean == io_safe_output($agent['nombre'])) {
											$id_agent = $agent['id_agente'];
										}
									}
								}
								
								if ($id_agent) {
									if (isset($sla_xml->module)) {
										$module_clean = str_replace(array('[', ']'), '', $sla_xml->module);
										$regular_expresion = ($module_clean != $sla_xml->module);
										
										$modules = db_get_all_rows_filter('tagente_modulo',
											array('id_agente' => $id_agent), array('id_agente_modulo', 'nombre'));
											
										foreach ($modules as $module) {
											if ($regular_expresion) {
												if ((bool)preg_match("/" . $module_clean . "/", io_safe_output($module['nombre']))) {
													$slas[] = array(
														'id_agent_module' => $module['id_agente_modulo'],
														'sla_max' => (string)$sla_xml->sla_max,
														'sla_min' => (string)$sla_xml->sla_min,
														'sla_limit' => (string)$sla_xml->sla_limit
														); 
												}
											}
											else {
												if ($module_clean == io_safe_output($module['nombre'])) {
													 $slas[] = array(
														'id_agent_module' => $module['id_agente_modulo'],
														'sla_max' => (string)$sla_xml->sla_max,
														'sla_min' => (string)$sla_xml->sla_min,
														'sla_limit' => (string)$sla_xml->sla_limit
														);
												}
											}
										}
									}
								}
							}
						}
					}
					break;
				case 6:
				case 'monitor_report':
					break;
				case 7:
				case 'avg_value':
					break;
				case 8:
				case 'max_value':
					break;
				case 9:
				case 'min_value':
					break;
				case 10:
				case 'sumatory':
					break;
				case 'agent_detailed_event':
				case 'event_report_agent':
					break;
				case 'text':
					$values['text'] = io_safe_input($item['text']);
					break;
				case 'sql':
					$values['header_definition'] = io_safe_input($item['header_definition']);
					$values['external_source'] = io_safe_input($item['sql']);
					break;
				case 'sql_graph_pie':
				case 'sql_graph_vbar':
				case 'sql_graph_hbar':
					$values['header_definition'] = io_safe_input($item['header_definition']);
					$values['external_source'] = io_safe_input($item['sql']);
					break;
				case 'event_report_group':
					$values['id_agent'] = db_get_value('id_grupo', 'tgrupo', 'nombre', io_safe_input($item->group));
					break;
				case 'event_report_module':
					break;
				case 'alert_report_module':
					break; 
				case 'alert_report_agent':
					break;
				case 'alert_report_group':
					break;
				case 'url':
					$values["external_source"] = io_safe_input($item['url']);
					break;
				case 'database_serialized':
					$values['header_definition'] = io_safe_input($item['header_definition']);
					$values['line_separator'] = io_safe_input($item['line_separator']);
					$values['column_separator'] = io_safe_input($item['column_separator']);
					break;
				case 'TTRT':
					break;
				case 'TTO':
					break;
				case 'MTBF':
					break;
				case 'MTTR':
					break;
			}
			
			if (empty($agents_item)) {
				$id_content = db_process_sql_insert ('treport_content', $values);
					ui_print_result_message($id_content,
						sprintf(__("Success add '%s' content."), $values['type']),
						sprintf(__("Error add '%s' action."), $values['type']));
				
				if ($item['type'] == 'SLA') {
					foreach ($slas as $sla) {
						$sla['id_report_content'] = $id_content;
						$result = db_process_sql_insert ('treport_content_sla_combined', $sla);
						ui_print_result_message($result,
							sprintf(__("Success add '%s' SLA."), $sla['id_agent_module']),
							sprintf(__("Error add '%s' SLA."), $sla['id_agent_module']));
						
					}
					
				}
			}
			else {
				foreach ($agents_item as $id_agent => $agent) {
					if (empty($agent['modules'])) {
						$values['id_agent'] = $id_agent;
						$id_content = db_process_sql_insert ('treport_content', $values);
						ui_print_result_message($id_content,
							sprintf(__("Success add '%s' content."), $values['type']),
							sprintf(__("Error add '%s' action."), $values['type']));
					}
					else {
						foreach ($agent['modules'] as $id_module => $module) {
							$values['id_agent_module'] = $id_module;
							$values['id_agent'] = $id_agent;
							
							$id_content = db_process_sql_insert ('treport_content', $values);
							ui_print_result_message($id_content,
								sprintf(__("Success add '%s' content."), $values['type']),
								sprintf(__("Error add '%s' action."), $values['type']));
						}
					}
				}
			}
		}
	}
}

function process_upload_xml_visualmap($xml, $filter_group = 0) {
	global $config;
	
	foreach ($xml->xpath('/visual_map') as $visual_map) {
		if (isset($visual_map->name)) {
			$values['name'] = (string)$visual_map->name;
		}
		else {
			ui_print_error_message(
				__("Error create '%s' visual map, lost tag name."));
			break;
		}
		
		$values['id_group'] = 0;
		if (isset($visual_map->group)) {
			$id_group = db_get_value('id_grupo', 'tgrupo', 'nombre', io_safe_input($visual_map->group));
			if ($id_group !== false) $values['id_group'] = $id_group;
		}
		
		if (isset($visual_map->background))
			$values['background'] = (string)$visual_map->background;
		
		$values['width'] = 0;
		if (isset($visual_map->width))
			$values['width'] = (string)$visual_map->width;
		
		$values['height'] = 0;
		if (isset($visual_map->height))
			$values['height'] = (string)$visual_map->height;
		
		$posible_name = $values['name'];
		$exist = true;
		$loops = 30; //Loops to exit or tries
		while ($exist && $loops > 0) {
			$exist = (bool)db_get_row_filter('tlayout', array('name' => io_safe_input($posible_name)));
			
			if ($exist) {
				$loops--;
				$posible_name = $values['name'] . " (" . (30 - $loops) . ")";
			}
		}
		
		if ($exist) {
			ui_print_error_message(
				sprintf(
					__("Error create '%s' visual map, the name exist and there aren't free name."),
					$values['name']));
			continue;
		}
		else if ($loops != 30) {
			ui_print_error_message(
				sprintf(
					__("Warning create '%s' visual map, the name exist, the report have a name %s."),
					$values['name'], $posible_name));
		}
		
		$values['name'] = io_safe_input($posible_name);
		$id_visual_map = db_process_sql_insert('tlayout', $values);
		
		ui_print_result_message((bool)$id_visual_map,
			sprintf(__("Success create '%s' visual map."), $posible_name),
			sprintf(__("Error create '%s' visual map."), $posible_name));
		
		if ($id_visual_map !== false) {
			db_pandora_audit('CREATE VISUAL CONSOLE', $id_visual_map, $config['id_user']);
		}
		else {
			break;
		}
		
		$relation_other_ids = array();
		
		foreach ($visual_map->item as $item) {
			$no_agents = true;
			
			if (isset($item->agent)) {
				$agent_clean = str_replace(array('[', ']'), '', $item->agent);
				$regular_expresion = ($agent_clean != $item->agent);
				
				$agents = agents_get_agents(array('id_grupo' => $filter_group), array('id_agente', 'nombre'));
				if ($agents === false) $agents = array(); 
				$temp = array();
				foreach ($agents as $agent) {
					$temp[$agent['id_agente']] = $agent['nombre'];
				}
				$agents = $temp;
				
				$agents_in_item = array();
				foreach ($agents as $id => $agent) {
					if ($regular_expresion) {
						if ((bool)preg_match("/" . $agent_clean . "/", io_safe_input($agent))) {
							$agents_in_item[$id]['name'] = $agent;
							$no_agents = false;
						}
					}
					else {
						if ($agent_clean == io_safe_input($agent)) {
							$agents_in_item[$id]['name'] = $agent;
							$no_agents = false;
							break;
						}
					}
				}
			}
			
			$no_modules = true;
			if (isset($item->module)) {
				$module_clean = str_replace(array('[', ']'), '', $item->module);
				$regular_expresion = ($module_clean != $item->module);
				
				foreach ($agents_in_item as $id => $agent) {
					$modules = db_get_all_rows_filter('tagente_modulo',
						array('id_agente' => $id), array('id_agente_modulo', 'nombre'));
					
					$modules_in_item = array();
					foreach ($modules as $module) {
						if ($regular_expresion) {
							if ((bool)preg_match("/" . $module_clean . "/", io_safe_input($module['nombre']))) {
								$modules_in_item[$module['id_agente_modulo']] = $module['nombre'];
								$no_modules = false;
							}
						}
						else {
							if ($module_clean == io_safe_input($module['nombre'])) {
								$modules_in_item[$module['id_agente_modulo']] = $module['nombre'];
								$no_modules = false;
								break;
							}
						}
					}
					
					$agents_in_item[$id]['modules'] = $modules_in_item;
				}
			}
			
			$values = array();
			
			$values['id_layout'] = $id_visual_map;
			if (isset($item->label))
				$values['label'] = io_safe_input($item->label);
			if (isset($item->x))
				$values['pos_x'] = (string)$item->x;
			if (isset($item->y))
				$values['pos_y'] = (string)$item->y;
			if (isset($item->height))
				$values['height'] = (string)$item->height;
			if (isset($item->width))
				$values['width'] = (string)$item->width;
			if (isset($item->image))
				$values['image'] = (string)$item->image;
			if (isset($item->period))
				$values['period'] = (string)$item->period;
			if (isset($item->label_color))
				$values['label_color'] = (string)$item->label_color;
			if (isset($item->parent_item)) {
				//Hack for link the items use the <other_id>OTHER_ID</other_id>
				// and have too <parent_item>OTHER_ID</parent_item>
				// then $relation_other_ids[OTHER_ID] have the item_id in DB.
				$values['parent_item'] = (string)$relation_other_ids[(string)$item->parent_item];
			}
			if (isset($item->map_linked))
				$values['id_layout_linked'] = (string)$item->map_linked;
			if (isset($item->type))
				$values['type'] = (string)$item->type;
			
			if ($no_agents) {
				$id_item = db_process_sql_insert('tlayout_data', $values);
				
				ui_print_result_message((bool)$id_item,
					sprintf(__("Success create item type '%d' visual map."), $values['type']),
					sprintf(__("Error create item type '%d' visual map."), $values['type']));
				
				if ($id_item !== false) {
					db_pandora_audit('CREATE ITEM VISUAL CONSOLE', $values['id_layout'] . " - " . $id_item, $config['id_user']);
				}
			}
			else {
				foreach ($agents_in_item as $id => $agent) {
					if ($no_modules) {
						$values['id_agent'] = $id;
						
						$id_item = db_process_sql_insert('tlayout_data', $values);
						
						if (isset($item->other_id)) {
							$relation_other_ids[(string)$item->other_id] = $id_item;
						}
						
						ui_print_result_message((bool)$id_item,
							sprintf(__("Success create item for agent '%s' visual map."), $agent['name']),
							sprintf(__("Error create item for agent '%s' visual map."), $agent['name']));
						
						if ($id_item !== false) {
							db_pandora_audit('CREATE ITEM VISUAL CONSOLE', $values['id_layout'] . " - " . $id_item, $config['id_user']);
						}
					}
					else {
						foreach ($agent['modules'] as $id_module => $module) {
							$values['id_agent'] = $id;
							$values['id_agente_modulo'] = $id_module;
							
							db_process_sql_insert('tlayout_data', $values);
							
							ui_print_result_message((bool)$id_item,
								sprintf(__("Success create item for agent '%s' visual map."), $agent['name']),
								sprintf(__("Error create item for agent '%s' visual map."), $agent['name']));
							
							if ($id_item !== false) {
								db_pandora_audit('CREATE ITEM VISUAL CONSOLE', $values['id_layout'] . " - " . $id_item, $config['id_user']);
							}
						}
					}
				}
			}
		}
	}
}

function process_upload_xml_component($xml) {
	//Extract components
	$components = array();
	foreach ($xml->xpath('/component') as $componentElement) {
		$name = io_safe_input((string)$componentElement->name);
		$id_os = (int)$componentElement->id_os;
		$os_version = io_safe_input((string)$componentElement->os_version);
		$data = io_safe_input((string)$componentElement->data);
		$type = (int)$componentElement->type;
		$group = (int)$componentElement->group;
		$description = io_safe_input((string)$componentElement->description);
		$module_interval = (int)$componentElement->module_interval;
		$max = (float)$componentElement->max;
		$min = (float)$componentElement->min;
		$tcp_send = io_safe_input((string)$componentElement->tcp_send);
		$tcp_rcv_text = io_safe_input((string)$componentElement->tcp_rcv_text);
		$tcp_port = (int)$componentElement->tcp_port;
		$snmp_oid = io_safe_input((string)$componentElement->snmp_oid);
		$snmp_community = io_safe_input((string)$componentElement->snmp_community);
		$id_module_group = (int)$componentElement->id_module_group;
		$module_source = (int)$componentElement->module_source;
		$plugin = (int)$componentElement->plugin;
		$plugin_username = io_safe_input((string)$componentElement->plugin_username);
		$plugin_password = io_safe_input((string)$componentElement->plugin_password);
		$plugin_parameters = io_safe_input((string)$componentElement->plugin_parameters);
		$max_timeout = (int)$componentElement->max_timeout;
		$historical_data = (int)$componentElement->historical_data;
		$min_war = (float)$componentElement->min_war;
		$max_war = (float)$componentElement->max_war;
		$str_war = (string)$componentElement->str_war;
		$min_cri = (float)$componentElement->min_cri;
		$max_cri = (float)$componentElement->max_cri;
		$str_cri = (string)$componentElement->str_cri;
		$ff_treshold = (int)$componentElement->ff_treshold;
		$snmp_version = (int)$componentElement->snmp_version;
		$auth_user = io_safe_input((string)$componentElement->auth_user);
		$auth_password = io_safe_input((string)$componentElement->auth_password);
		$auth_method = io_safe_input((string)$componentElement->auth_method);
		$privacy_method = io_safe_input((string)$componentElement->privacy_method);
		$privacy_pass = io_safe_input((string)$componentElement->privacy_pass);
		$security_level = io_safe_input((string)$componentElement->security_level);
		$wmi_query = io_safe_input((string)$componentElement->wmi_query);
		$key_string = io_safe_input((string)$componentElement->key_string);
		$field_number = (int)$componentElement->field_number;
		$namespace = io_safe_input((string)$componentElement->namespace);
		$wmi_user = io_safe_input((string)$componentElement->wmi_user);
		$wmi_password = io_safe_input((string)$componentElement->wmi_password);
		$post_process = io_safe_input((float)$componentElement->post_process);
		
		$idComponent = false;
		switch ((int)$componentElement->module_source) {
			case 1: //Local component
				$values = array('description' => $description,
					'id_network_component_group' => $group,
					'os_version' => $os_version);
				$return = enterprise_hook('local_components_create_local_component', array($name, $data, $id_os, $values));
				if ($return !== ENTERPRISE_NOT_HOOK) {
					$idComponent = $return;
				}
				break;
			case 2: //Network component
				
				//for modules
				//15 = remote_snmp, 16 = remote_snmp_inc,
				//17 = remote_snmp_string, 18 = remote_snmp_proc
				$custom_string_1 = '';
				$custom_string_2 = '';
				$custom_string_3 = '';
				if ($type >= 15 && $type <= 18) {
					// New support for snmp v3
					$tcp_send = $snmp_version;
					$plugin_user = $auth_user;
					$plugin_pass = $auth_password;
					$plugin_parameter = $auth_method;
					$custom_string_1 = $privacy_method;
					$custom_string_2 = $privacy_pass;
					$custom_string_3 = $security_level;
				}
				
				$idComponent = network_components_create_network_component ($name,
					$type, $group, 
					array ('description' => $description,
						'module_interval' => $module_interval,
						'max' => $max,
						'min' => $min,
						'tcp_send' => $tcp_send,
						'tcp_rcv' => $tcp_rcv_text,
						'tcp_port' => $tcp_port,
						'snmp_oid' => $snmp_oid,
						'snmp_community' => $snmp_community,
						'id_module_group' => $id_module_group,
						'id_modulo' => $module_source,
						'id_plugin' => $plugin,
						'plugin_user' => $plugin_username,
						'plugin_pass' => $plugin_password,
						'plugin_parameter' => $plugin_parameters,
						'max_timeout' => $max_timeout,
						'history_data' => $historical_data,
						'min_warning' => $min_war,
						'max_warning' => $max_war,
						'str_warning' => $str_war,
						'min_critical' => $min_cri,
						'max_critical' => $max_cri,
						'str_critical' => $str_cri,
						'min_ff_event' => $ff_treshold,
						'custom_string_1' => $custom_string_1,
						'custom_string_2' => $custom_string_2,
						'custom_string_3' => $custom_string_3,
						'post_process' => $post_process));
				if ((bool)$idComponent) {
					$components[] = $idComponent; 
				}
				break;
			case 4: //Plugin component
				$idComponent = network_components_create_network_component ($name,
					$type, $group, 
					array ('description' => $description,
						'module_interval' => $module_interval,
						'max' => $max,
						'min' => $min,
						'tcp_send' => $tcp_send,
						'tcp_rcv' => $tcp_rcv_text,
						'tcp_port' => $tcp_port,
						'snmp_oid' => $snmp_oid,
						'snmp_community' => $snmp_community,
						'id_module_group' => $id_module_group,
						'id_modulo' => $module_source,
						'id_plugin' => $plugin,
						'plugin_user' => $plugin_username,
						'plugin_pass' => $plugin_password,
						'plugin_parameter' => $plugin_parameters,
						'max_timeout' => $max_timeout,
						'history_data' => $historical_data,
						'min_warning' => $min_war,
						'max_warning' => $max_war,
						'str_warning' => $str_war,
						'min_critical' => $min_cri,
						'max_critical' => $max_cri,
						'str_critical' => $str_cri,
						'min_ff_event' => $ff_treshold,
						'custom_string_1' => $custom_string_1,
						'custom_string_2' => $custom_string_2,
						'custom_string_3' => $custom_string_3,
						'post_process' => $post_process));
				if ((bool)$idComponent) {
					$components[] = $idComponent; 
				}
				break;
			case 5: //Prediction component
				break;
			case 6: //WMI component
				$idComponent = network_components_create_network_component ($name,
					$type, $group, 
					array ('description' => $description,
						'module_interval' => $module_interval,
						'max' => $max,
						'min' => $min,
						'tcp_send' => $namespace, //work around
						'tcp_rcv' => $tcp_rcv_text,
						'tcp_port' => $field_number, //work around
						'snmp_oid' => $wmi_query, //work around
						'snmp_community' => $key_string, //work around
						'id_module_group' => $id_module_group,
						'id_modulo' => $module_source,
						'id_plugin' => $plugin,
						'plugin_user' => $wmi_user, //work around
						'plugin_pass' => $wmi_password, //work around
						'plugin_parameter' => $plugin_parameters,
						'max_timeout' => $max_timeout,
						'history_data' => $historical_data,
						'min_warning' => $min_war,
						'max_warning' => $max_war,
						'str_warning' => $str_war,
						'min_critical' => $min_cri,
						'max_critical' => $max_cri,
						'str_critical' => $str_cri,
						'min_ff_event' => $ff_treshold,
						'custom_string_1' => $custom_string_1,
						'custom_string_2' => $custom_string_2,
						'custom_string_3' => $custom_string_3,
						'post_process' => $post_process));
				if ((bool)$idComponent) {
					$components[] = $idComponent; 
				}
				break;
			case 7: //Web component
				break;
		}
		
		ui_print_result_message((bool)$idComponent, sprintf(__("Success create '%s' component."), $name),
			sprintf(__("Error create '%s' component."), $name));
	}
	
	//Extract the template
	$templateElement = $xml->xpath('//template');
	if (!empty($templateElement)) {
		$templateElement = $templateElement[0];
		
		$templateName = (string)$templateElement->name;
		$templateDescription = (string)$templateElement->description;
		
		$idTemplate = db_process_sql_insert('tnetwork_profile', array('name' => $templateName, 'description' => $templateDescription));
		
		$result = false;
		if ((bool)$idTemplate) {
			foreach ($components as $idComponent) {
				db_process_sql_insert("tnetwork_profile_component", array('id_nc' => $idComponent, 'id_np' => $idTemplate));
			}
		}
	}
}

function process_upload_xml($xml) {
	$hook_enterprise = enterprise_include ('extensions/resource_registration/functions.php');
	
	//Extract component
	process_upload_xml_component($xml);
	
	$group_filter = get_parameter('group');
	
	//Extract visual map
	process_upload_xml_visualmap($xml, $group_filter);
	
	
	//Extract policies
	if ($hook_enterprise === true)
		process_upload_xml_policy($xml, $group_filter);
	
	
	//Extract reports
	process_upload_xml_report($xml, $group_filter);
}

function resource_registration_extension_main() {
	global $config;
	
	if (! check_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
		db_pandora_audit("ACL Violation", "Trying to access Setup Management");
		require ("general/noaccess.php");
		return;
	}
	
	require_once($config['homedir'] . '/include/functions_network_components.php');
	require_once($config['homedir'] . '/include/functions_db.php');
	enterprise_include_once('include/functions_local_components.php');
	
	ui_print_page_header (__('Resource registration'), "images/extensions.png", false, "", true, "" );
	
	if (!extension_loaded("libxml")) {
		ui_print_error_message(_("Error, please install the PHP libXML in the system."));
		
		return;
	}
	
	echo "<div class=notify>";
	printf(__("This extension makes registration of resource template more easy. " .
		"Here you can upload a resource template in Pandora FMS 3.x format (.ptr). " .
		"Please refer to documentation on how to obtain and use Pandora FMS resources. " .
		"<br><br>You can get more resurces in our <a href='%s'>Public Resource Library</a>") ,
		"http://pandorafms.org/index.php?sec=community&sec2=repository&lng=en");
	echo "</div>";
	
	echo "<br /><br />";
	
	// Upload form
	echo "<form name='submit_plugin' method='post' enctype='multipart/form-data'>";
		echo '<table class="databox" id="table1" width="98%" border="0" cellpadding="4" cellspacing="4">';
			echo "<tr>";
				echo "<td colspan='2' class='datos'><input type='file' name='resource_upload' />";
			echo "</tr>";
			echo "<tr>";
				echo "<td>" . __('Group filter: ') . "</td>";
				echo "<td>";
					html_print_select_groups(false, "AW", true, 'group');
				echo "</td>";
				echo "<td class='datos'><input type='submit' class='sub next' value='".__('Upload')."' />";
			echo "</tr>";
		echo "</table>";
	echo "</form>";
	
	if (!isset ($_FILES['resource_upload']['tmp_name'])) {
		return;
	}
	$xml = simplexml_load_file($_FILES['resource_upload']['tmp_name'], NULL, LIBXML_NOCDATA);
	
	process_upload_xml($xml);
}

extensions_add_godmode_menu_option (__('Resource registration'), 'PM','gservers','', "v1r1");
extensions_add_godmode_function('resource_registration_extension_main');
?>
