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
if (isset ($_GET["direct"]))  {
	/* 
	This is in case somebody wants to access the XML directly without
	having the possibility to login and handle sessions
	
	Use this URL: https://yourserver/pandora_console/operation/reporting/reporting_xml.php?id=<reportid>&direct=1
	
	Although it's not recommended, you can put your login and password
	in a GET request (append &nick=<yourlogin>&password=<password>). 
	
	
	You SHOULD put it in a POST but some programs
	might not be able to handle it without extensive re-programming
	(M$ ShitPoint). Either way, you should have a read-only user for getting reports
	
	XMLHttpRequest can do it (example):
	
	var reportid = 3;
	var login = "yourlogin";
	var password = "yourpassword";
	var url = "https://<yourserver>/pandora_console/operation/reporting/reporting_xml.php?id="+urlencode(reportid)+"&direct=1";
	var params = "nick="+urlencode(login)+"&pass="+urlencode(password);
	var xmlHttp = new XMLHttpRequest();
	var textout = "";
	try { 
		xmlHttp.open("POST", url, false);
		xmlHttp.send(params);
		if(xmlHttp.readyState == 4 && xmlHttp.status == 200) {
			textout = xmlHttp.responseXML;
		}
	} 
	catch (err) {
		alert ("error");
	}
	*/
	require_once ("../../include/config.php");
	require_once ("../../include/functions.php");
	require_once ("../../include/functions_db.php");
	require_once ("../../include/functions_reporting.php");
	
	$nick = get_parameter ("nick");
	$pass = get_parameter ("pass");
	
	$sql = sprintf("SELECT `id_usuario`, `password` FROM `tusuario` WHERE `id_usuario` = '%s'",$nick);
	$row = get_db_row_sql ($sql);
	
	// For every registry
	if ($row !== false) {
		if ($row["password"] == md5 ($pass)) {
			// Login OK
			// Nick could be uppercase or lowercase (select in MySQL
			// is not case sensitive)
			// We get DB nick to put in PHP Session variable,
			// to avoid problems with case-sensitive usernames.
			// Thanks to David Muñiz for Bug discovery :)
			$nick = $row["id_usuario"];
			update_user_contact ($nick);
			$_SESSION['id_usuario'] = $nick;
			$config['id_user'] = $nick;
			unset ($_GET['pass'], $pass);
		} else {
			// Login failed (bad password) 
			echo "Logon failed";
			audit_db ($nick, $_SERVER['REMOTE_ADDR'], "Logon Failed",
					  "Incorrect password: " . $nick);
			exit;
		}
	} else {
		// User not known
		echo "Logon failed";
		audit_db ($nick, $_SERVER['REMOTE_ADDR'], "Logon Failed", "Invalid username: " . $nick);
		exit;
	}

} else {
	require_once ("include/config.php");
	require_once ("include/functions_reporting.php");
}

check_login();

$id_report = (int) get_parameter ('id');

if (! $id_report) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "HACK Attempt",
		"Trying to access graph viewer withoud ID");
	require ("general/noaccess.php");
	exit;
}

$report = get_db_row ('treport', 'id_report', $id_report);

if (! give_acl ($config['id_user'], $report['id_group'], "AR")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation","Trying to access graph reader");
	include ("general/noaccess.php");
	exit;
}

/* Check if the user can see the graph */
if ($report['private'] && ($report['id_user'] != $config['id_user'] && ! dame_admin ($config['id_user']))) {
	return;
}

header ('Content-type: application/xml; charset="utf-8"', true);
echo '<?xml version="1.0" encoding="UTF-8" ?>';

$date = (string) get_parameter ('date', date ('Y-m-j'));
$time = (string) get_parameter ('time', date ('h:iA'));

$datetime = strtotime ($date.' '.$time);

if ($datetime === false || $datetime == -1) {
	echo "<error>Invalid date selected</error>"; //Not translatable because this is an error message and might have to be used on the other end
	exit;
}
/* Date must not be older than now */
if ($datetime > time ()) {
	echo "<error>Date is larger than current time</error>"; //Not translatable because this is an error message
	exit;
}

$group_name = dame_grupo ($report['id_group']);
$contents = get_db_all_rows_field_filter ('treport_content', 'id_report', $id_report, '`order`');


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
		$data["objdata"]["img"] = 'reporting/fgraph.php?tipo=sparse&amp;id='.$content['id_agent_module'].'&amp;height=230&amp;width=720&amp;period='.$content['period'].'&amp;date='.$datetime.'&amp;avg_only=1&amp;pure=1';
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
		
		$data["objdata"]["img"] = 'reporting/fgraph.php?tipo=combined&amp;id='.implode (',', $modules).'&amp;weight_l='.implode (',', $weights).'&amp;height=230&amp;width=720&amp;period='.$content['period'].'&amp;date='.$datetime.'&amp;stacked='.$graph["stacked"].'&amp;pure=1"';
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
	array_push ($xml["reports"], $data);
}


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

$time = time ();
echo '<report>';
echo '<generated><unix>'.$time.'</unix>';
echo '<rfc2822>'.date ("r",$time).'</rfc2822></generated>';
xml_array ($xml);
echo '</report>';

?>
