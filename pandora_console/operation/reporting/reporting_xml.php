<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.


// Login check
require("include/config.php");

check_login();

$id_report = (int) get_parameter ('id');

if (! $id_report) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "HACK Attempt",
		"Trying to access graph viewer withoud ID");
	include ("general/noaccess.php");
	exit;
}

$report = get_db_row ('treport', 'id_report', $id_report);

if (! give_acl ($config['id_user'], $report['id_group'], "AR")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation","Trying to access graph reader");
	include ("general/noaccess.php");
	exit;
}

require ("include/functions_reporting.php");

/* Check if the user can see the graph */
if ($report['id_user'] != $config['id_user'] && ! dame_admin ($config['id_user']) && ! $report['private']) {
	return;
}

echo '<?xml version="1.0" encoding="UTF-8" ?>';

$date = (string) get_parameter ('date', date ('Y-m-j'));
$time = (string) get_parameter ('time', date ('h:iA'));

$datetime = strtotime ($date.' '.$time);

if ($datetime === false || $datetime == -1) {
	$xml["error"][] = __('Invalid date selected');
	return;
}
/* Date must not be older than now */
if ($datetime > time ()) {
	$xml["error"][] = __('Selected date is older than current date');
	return;
}

$group_name = dame_grupo ($report['id_group']);
$contents = get_db_all_rows_field_filter ("treport_content","id_report",$id_report,"order");


$xml["id"] = $id_report;
$xml["name"] = $report['name'];
$xml["description"] = $report['description'];
$xml["group"]["id"] = $report['id_group'];
$xml["group"]["name"] = $group_name;

if ($contents === false) {
	$contents = array ();
};

$xml["reports"] = array ();

foreach ($contents as $content) {
	$data = array ();
	$data["module"] = get_db_value ('nombre', 'tagente_modulo', 'id_agente_modulo', $content['id_agent_module']);
	$data["agent"] = dame_nombre_agente_agentemodulo ($content['id_agent_module']);
	$data["period"] = human_time_description ($content['period']);
	$data["uperiod"] = $content['period'];
	$data["type"] = $content["type"];
	
	switch ($content["type"]) {
	case 1:
	case 'simple_graph':	
		$data["title"] = __('Simple graph');
		$data["objdata"]["img"] = 'reporting/fgraph.php?tipo=sparse&id='.$content['id_agent_module'].'&height=230&width=720&period='.$content['period'].'&date='.$datetime.'&avg_only=1&pure=1';
		break;
	case 2:
	case 'custom_graph':
		$graph = get_db_row ("tgraph", "id_graph", $content['id_gs']);
		$data["title"] = __('Custom graph');
		$data["objdata"]["img_name"] = $graph["name"];
		
		$result = get_db_all_rows_field_filter ("tgraph_source","id_graph",$content['id_gs']);
		$modules = array ();
		$weights = array ();
		if ($result === false)
			$result = array();
		
		foreach ($result as $content2) {
			array_push ($modules, $content2['id_agent_module']);
			array_push ($weights, $content2["weight"]);
		}
		
		$data["objdata"]["img"] = 'reporting/fgraph.php?tipo=combined&id='.implode (',', $modules).'&weight_l='.implode (',', $weights).'&height=230&width=720&period='.$content['period'].'&date='.$datetime.'&stacked='.$graph["stacked"].'&pure=1"';
		break;
	case 3:
	case 'SLA':
		$data["title"] = __('S.L.A');
		
		$slas = get_db_all_rows_field_filter ('treport_content_sla_combined','id_report_content', $content['id_rc']);
		if ($slas === false) {
			$data["objdata"]["error"] = __('There are no SLAs defined');
			$slas = array ();
		}
		
		$data["objdata"]["sla"] = array ();
		$sla_failed = false;
		foreach ($slas as $sla) {
			$sla = array ();
			$sla["agent"] .= dame_nombre_agente_agentemodulo ($sla['id_agent_module']);
			$sla["module"] .= dame_nombre_modulo_agentemodulo ($sla['id_agent_module']);
			$sla["max"] .= $sla['sla_max'];
			$sla["min"] .= $sla['sla_min'];
			
			$sla_value = get_agent_module_sla ($sla['id_agent_module'], $content['period'],
							$sla['sla_min'], $sla['sla_max'], $datetime);
			if ($sla_value === false) {
				$sla["error"] .= __('Unknown');
			} else {
				if ($sla_value < $sla['sla_limit']) {
					$sla["failed"] = "true";
				}
				$sla["value"] = format_numeric ($sla_value);
			}
			array_push ($data["objdata"]["sla"], $sla);
		}
		
		break;
	case 4:
	case 'event_report':	
		$data["title"] = __("Event report");
		$table_report = event_reporting ($report['id_group'], $content['period'], $datetime, true);
		$data["objdata"] = "<![CDATA[";
		$data["objdata"] .= print_table ($table_report, true);
		$data["objdata"] .= "]]>";
		break;
	case 5:
	case 'alert_report':
		$data["title"] = __('Alert report');
		$data["objdata"] = "<![CDATA[";
		$data["objdata"] .= alert_reporting ($report['id_group'], $content['period'], $datetime, true);
		$data["objdata"] .= "]]>";
		break;
	case 6:
	case 'monitor_report':
		$data["title"] = __('Monitor report');
		$monitor_value = format_numeric (get_agent_module_sla ($content['id_agent_module'], $content['period'], 1, 1, $datetime));
		$data["objdata"]["good"] = $monitor_value;
		$data["objdata"]["bad"] = format_numeric (100 - $monitor_value, 2);
		break;
	case 7:
	case 'avg_value':
		$data["title"] = __('Avg. Value');
		$data["objdata"] = format_numeric (get_agent_module_value_average ($content['id_agent_module'], $content['period'], $datetime));
		break;
	case 8:
	case 'max_value':
		$data["title"] = __('Max. Value');
		$data["objdata"] = format_numeric (get_agent_module_value_max ($content['id_agent_module'], $content['period'], $datetime));
		break;
	case 9:
	case 'min_value':
		$data["title"] = __('Min. Value');
		$data["objdata"] = format_numeric (get_agent_module_value_min ($content['id_agent_module'], $content['period'], $datetime));
		break;
	case 10:
	case 'sumatory':
		$data["title"] = __('Sumatory');
		$data["objdata"] = format_numeric (get_agent_module_value_sumatory ($content['id_agent_module'], $content['period'], $datetime));
		break;
	case 11:
	case 'general_group_report':
		$data["title"] = __('Group');
		$data["objdata"] = "<![CDATA[";
		$data["objdata"] .= general_group_reporting ($report['id_group'], true);
		$data["objdata"] .= "]]>";
		break;
	case 12:
	case 'monitor_health':
		$data["title"] = __('Monitor health');
		$data["objdata"] = "<![CDATA[";
		$data["objdata"] .= monitor_health_reporting ($report['id_group'], $content['period'], $datetime, true);
		$data["objdata"] .= "]]>";
		break;
	case 13:
	case 'agents_detailed':
		$data["title"] = __('Agents detailed view');
		$data["objdata"] = "<![CDATA[";
		$data["objdata"] .= get_agents_detailed_reporting ($report['id_group'], $content['period'], $datetime, true);
		$data["objdata"] .= "]]>";
		break;
	}
	array_push ($xml["reports"],$data);
}

header('Content-type: application/xml; charset="utf-8"',true);


function xml_array ($array) {
	foreach ($array as $name => $value) {
		if (is_int ($name)) {
			echo "<object id=\"".$name."\">";
			$name = "object";
		} else {
			echo "<".$name.">";
		}
		
		if (is_array ($value)) {
			xml_array ($value);
		} else {
			echo $value;
		}
		
		echo "</".$name.">";
	}
}

echo "<report>";
xml_array ($xml);
echo "</report>";

?>
