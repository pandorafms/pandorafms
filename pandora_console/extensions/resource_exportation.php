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

if (isset($_GET['get_ptr'])) {
	if ($_GET['get_ptr'] == 1) {
		
		$ownDir = dirname(__FILE__) . '/';
		$ownDir = str_replace("\\", "/", $ownDir);
		require_once ($ownDir.'../include/config.php');
		
		if (! isset ($_SESSION["id_usuario"])) {
			session_start ();
			session_write_close ();
		}
		
		// Login check
		if (!isset($_SESSION["id_usuario"])) {
			$config['id_user'] = null;
		}
		else {
			$config['id_user'] = $_SESSION["id_usuario"];
		}
		
		
		if (! check_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
			db_pandora_audit("ACL Violation", "Trying to access Setup Management");
			require ("general/noaccess.php");
			return;
		}
		
		$hook_enterprise = enterprise_include ('extensions/resource_exportation/functions.php');
		
		header("Content-type: binary");
		header("Content-Disposition: attachment; filename=\"".get_name_xml_resource($hook_enterprise)) . "\"";
		header("Pragma: no-cache");
		header("Expires: 0");
		
		output_xml_resource($hook_enterprise);
	}
}
else {
	extensions_add_godmode_menu_option (__('Resource exportation'), 'PM','gservers','', "v1r1");
	extensions_add_godmode_function('resource_exportation_extension_main');
}

function output_xml_resource($hook_enterprise) {
	global $config;
	
	$type = get_parameter('type');
	$id = (int)get_parameter('id');
	
	switch ($type) {
		case 'report':
			output_xml_report($id);
			break;
		case 'visual_console':
			output_xml_visual_console($id);
			break;
		default:
			if ($hook_enterprise === true)
				return enterprise_output_xml_resource($type, $id);
			break;
	}
}

function output_xml_report($id) {
	global $config;
	
	require_once ($config['homedir'].'/include/functions_agents.php');
	
	$report = db_get_row('treport', 'id_report', $id);
	
	echo '<?xml version="1.0" encoding="UTF-8" ?>' . "\n"; 
	echo "<report>\n";
	echo "<name><![CDATA[" . io_safe_output($report['name']) . "]]></name>\n";
	if (isset($report['description']))
		echo "<description><![CDATA[" . io_safe_output($report['description']) . "]]></description>\n";
	$group = db_get_value('nombre', 'tgrupo', 'id_grupo', $report['id_group']);
	echo "<group><![CDATA[" . io_safe_output($group) . "]]></group>\n";
	$items = db_get_all_rows_field_filter('treport_content', 'id_report', $report['id_report']);
	foreach ($items as $item) {
		echo "<item>\n";
			echo "<type>" . io_safe_output($item['type']) . "</type>\n";
			echo "<description>" . io_safe_output($item['description']) . "</description>\n";
			echo "<period>" . io_safe_output($item['period']) . "</period>\n";
			if ($item['id_agent'] != 0) {
				$agent = agents_get_name($item['id_agent']);
			}
			if ($item['id_agent_module'] != 0) {
				$module = db_get_value('nombre', 'tagente_modulo', 'id_agente_modulo', $item['id_agent_module']);
				$id_agent = db_get_value('id_agente', 'tagente_modulo', 'id_agente_modulo', $item['id_agent_module']);
				$agent = agents_get_name($item['id_agent']);
				
				echo "<module><![CDATA[" . io_safe_output($module) . "]]></module>\n";
			}
			if (isset($agent))
				echo "<agent><![CDATA[" . $agent . "]]></agent>\n";
			$agent = null;
			switch (io_safe_output($item['type'])) {
				case 1:
				case 'simple_graph':
					break;
				case 'simple_baseline_graph':
					break;
				case 2:
				case 'custom_graph':
				case 'automatic_custom_graph':
					$graph = db_get_value('name', 'tgraph', 'id_graph', $item['id_gs']);
					echo "<graph><![CDATA[" . io_safe_output($graph) . "]]></graph>\n";
					break;
				case 3:
				case 'SLA':
					echo "<only_display_wrong>" . $item['only_display_wrong'] . "</only_display_wrong>\n";
					echo "<monday>" . $item['monday'] . "</monday>\n";
					echo "<tuesday>" . $item['tuesday'] . "</tuesday>\n";
					echo "<wednesday>" . $item['wednesday'] . "</wednesday>\n";
					echo "<thursday>" . $item['thursday'] . "</thursday>\n";
					echo "<friday>" . $item['friday'] . "</friday>\n";
					echo "<saturday>" . $item['saturday'] . "</saturday>\n";
					echo "<sunday>" . $item['sunday'] . "</sunday>\n";
					echo "<time_from>" . $item['time_from'] . "</time_from>\n";
					echo "<time_to>" . $item['time_to'] . "</time_to>\n";
					
					$slas = db_get_all_rows_field_filter('treport_content_sla_combined', 'id_report_content', $item['id_rc']);
					if ($slas === false) $slas = array();
					
					foreach ($slas as $sla) {
						$module = db_get_value('nombre', 'tagente_modulo', 'id_agente_modulo', $sla['id_agent_module']);
						$id_agent = db_get_value('id_agente', 'tagente_modulo', 'id_agente_modulo', $sla['id_agent_module']);
						$agent = agents_get_name($item['id_agent']);
						echo "<sla>";
							echo "<agent><![CDATA[" . $agent . "]]></agent>\n";
							echo "<module><![CDATA[" . io_safe_output($module) . "]]></module>\n";
							echo "<sla_max>" . $sla['sla_max'] . "</sla_max>\n";
							echo "<sla_min>" . $sla['sla_min'] . "</sla_min>\n";
							echo "<sla_limit>" . $sla['sla_limit'] . "</sla_limit>\n";
						echo "</sla>";
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
					echo "<text><![CDATA[" . io_safe_output($item['text']) . "]]></text>\n";
					break;
				case 'sql':
					echo "<header_definition><![CDATA[" . io_safe_output($item['header_definition']) . "]]></header_definition>\n";
					if (!empty($item['external_source'])) {
						echo "<sql><![CDATA[" . io_safe_output($item['external_source']) . "]]></sql>\n";
					}
					else {
						$sql = db_get_value('sql', 'treport_custom_sql', 'id', $item['treport_custom_sql_id']);
						echo "<sql>" . io_safe_output($sql) . "</sql>\n";
					}
					break;
				case 'sql_graph_pie':
				case 'sql_graph_vbar':
				case 'sql_graph_hbar':
					echo "<header_definition>" . io_safe_output($item['header_definition']) . "</header_definition>\n";
					if (!empty($item['external_source'])) {
						echo "<sql>" . io_safe_output($item['external_source']) . "</sql>\n";
					}
					else {
						$sql = db_get_value('sql', 'treport_custom_sql', 'id', $item['treport_custom_sql_id']);
						echo "<sql>" . io_safe_output($sql) . "</sql>\n";
					}
					break;
				case 'event_report_group':
					$group = db_get_value('nombre', 'tgrupo', 'id_grupo', $item['id_agent']);
					echo "<group><![CDATA[" . io_safe_output($group) . "]]></group>\n";
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
					echo "<url><![CDATA[" . io_safe_output($values["external_source"]) . "]]></url>";
					break;
				case 'database_serialized':
					echo "<header_definition><![CDATA[" . io_safe_output($item["header_definition"]) . "]]></header_definition>";
					echo "<line_separator><![CDATA[" . io_safe_output($item["line_separator"]) . "]]></line_separator>";
					echo "<column_separator><![CDATA[" . io_safe_output($item["header_definition"]) . "]]></column_separator>";
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
		echo "</item>\n";
	}
	echo "</report>\n";
}

function output_xml_visual_console($id) {
	$visual_map = db_get_row('tlayout', 'id', $id);
	
	echo '<?xml version="1.0" encoding="UTF-8" ?>' . "\n"; 
	echo "<visual_map>\n";
	echo "<name><![CDATA[" . io_safe_output($visual_map['name']) . "]]></name>\n";
	if ($visual_map['id_group'] != 0) {
		$group = db_get_value('nombre', 'tgrupo', 'id_grupo', $visual_map['id_group']);
		echo "<group><![CDATA[" . io_safe_output($group) . "]]></group>\n";
	}
	echo "<background><![CDATA[" . io_safe_output($visual_map['background']) . "]]></background>\n";
	echo "<height>" . io_safe_output($visual_map['height']) . "</height>\n";
	echo "<width>" . io_safe_output($visual_map['width']) . "</width>\n";
	$items = db_get_all_rows_field_filter('tlayout_data', 'id_layout', $visual_map['id']);
	if ($items === false) $items = array();
	foreach ($items as $item) {
		echo "<item>\n";
		echo "<other_id>" . $item['id'] . "</other_id>\n"; //OLD ID USE FOR parent item 
		if (!empty($item['label'])) {
			echo "<label><![CDATA[" . io_safe_output($item['label']) . "]]></label>\n";
		}
		echo "<x>" . $item['pos_x'] . "</x>\n";
		echo "<y>" . $item['pos_y'] . "</y>\n";
		echo "<type>" . $item['type'] . "</type>\n";
		if ($item['width'] != 0) {
			echo "<width>" . $item['width'] . "</width>\n";
		}
		if ($item['height'] != 0) {
			echo "<height>" . $item['height'] . "</height>\n";
		}
		if (!empty($item['image'])) {
			echo "<image>" . $item['image'] . "</image>\n";
		}
		if ($item['period'] != 0) {
			echo "<period>" . $item['period'] . "</period>\n";
		}
		$agent = '';
		if ($item['id_agent'] != 0) {
			$agent = agents_get_name($item['id_agent']);
		}
		if (isset($item['id_agente_modulo'])) {
			if ($item['id_agente_modulo'] != 0) {
				$module = db_get_value('nombre', 'tagente_modulo', 'id_agente_modulo', $item['id_agente_modulo']);
				$id_agent = db_get_value('id_agente', 'tagente_modulo', 'id_agente_modulo', $item['id_agente_modulo']);
				$agent = agents_get_name($id_agent);
				
				echo "<module><![CDATA[" . io_safe_output($module) . "]]></module>\n";
			}
		}
		if (!empty($agent)) {
			echo "<agent><![CDATA[" . $agent . "]]></agent>\n";
		}
		if ($item['id_layout_linked'] != 0) {
			echo "<id_layout_linked>" . $item['id_layout_linked'] . "</id_layout_linked>\n";
		}
		if ($item['parent_item'] != 0) {
			echo "<parent_item>" . $item['parent_item'] . "</parent_item>\n";
		}
		echo "</item>\n";
	}
	echo "</visual_map>\n";
}

function get_name_xml_resource($hook_enterprise) {
	global $config;
	
	$type = get_parameter('type');
	$id = (int)get_parameter('id');
	
	switch ($type) {
		case 'report':
			$name = db_get_value('name', 'treport', 'id_report', $id);
			break;
		case 'visual_console':
			$name = db_get_value('name', 'tlayout', 'id', $id);
			break;
		default:
			if ($hook_enterprise === true)
				return enterprise_get_name_xml_resource($type, $id);
			break;
	}
	
	$file = $name . ".ptr";
	
	return $file;
}

function get_xml_resource() {
	global $config;
	
	$hook_enterprise = enterprise_include ('extensions/resource_exportation/functions.php');
}

function resource_exportation_extension_main() {
	global $config;
	
	if (! check_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
		db_pandora_audit("ACL Violation", "Trying to access Setup Management");
		require ("general/noaccess.php");
		return;
	}
	
	$hook_enterprise = enterprise_include ('extensions/resource_exportation/functions.php');
	
	ui_print_page_header (__('Resource exportation'), "images/extensions.png", false, "", true, "" );
	
	echo "<div class=notify>";
	echo __("This extension makes exportation of resource template more easy. " .
		"Here you can export as a resource template in Pandora FMS 3.x format (.ptr). ");
	echo "</div>";
	
	echo "<br /><br />";
	
	$table = null;
	$table->width = '98%';
	$table->style[0] = 'width: 30%;';
	$table->style[1] = 'width: 10%;';
	$table->class = "databox_color";
	$table->data[0][0] = __('Report');
	$table->data[0][1] = html_print_select_from_sql('SELECT id_report, name FROM treport', 'report', '', '', '', 0, true);
	$table->data[0][2] = html_print_button(__('Export'), '', false, 'export_to_ptr(\'report\');', 'class="sub"', true);
	$table->data[1][0] = __('Visual console');
	$table->data[1][1] = html_print_select_from_sql('SELECT id, name FROM tlayout', 'visual_console', '', '', '', 0, true);
	$table->data[1][2] = html_print_button(__('Export'), '', false, 'export_to_ptr(\'visual_console\');', 'class="sub"', true);
	
	if ($hook_enterprise === true)
		add_rows_for_enterprise($table->data);
	
	html_print_table($table);
	
	?>
	<script type="text/javascript">
	function export_to_ptr(type) {
		id = $("select#" + type + " option:selected").val();
		url = location.href.split('index');
		url = url[0] + 'extensions/resource_exportation.php?get_ptr=1&type=' + type
			+ '&id=' + id;

		location.href=url;
	}
	</script>
	<?php
}
?>
