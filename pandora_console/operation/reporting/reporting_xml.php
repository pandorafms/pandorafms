<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

include_once("include/functions_modules.php");
include_once("include/functions_events.php");
include_once ('include/functions_groups.php');

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

// Login check
if (isset ($_GET["direct"])) {
	/* 
	This is in case somebody wants to access the XML directly without
	having the possibility to login and handle sessions
	 
	Use this URL: https://yourserver/pandora_console/operation/reporting/reporting_xml.php?id=<reportid>&direct=1
	
	Although it's not recommended, you can put your login and password
	in a GET request (append &nick=<yourlogin>&password=<password>). 
	 	 
	You SHOULD put it in a POST but some programs
	might not be able to handle it without extensive re-programming
	Either way, you should have a read-only user for getting reports
	 
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
	require_once ("../../include/functions_reporting.php");
	require_once ("../../include/auth/mysql.php");
	
	$nick = get_parameter ("nick");
	$pass = get_parameter ("pass");
	
	$nick = process_user_login ($nick, $pass);
	
	if ($nick !== false) {
		unset ($_GET["sec2"]);
		$_GET["sec"] = "general/logon_ok";
		db_logon ($nick, $_SERVER['REMOTE_ADDR']);
		$_SESSION['id_usuario'] = $nick;
		$config['id_user'] = $nick;
		//Remove everything that might have to do with people's passwords or logins
		unset ($_GET['pass'], $pass, $_POST['pass'], $_REQUEST['pass'], $login_good);
	}
	else {
		// User not known
		$login_failed = true;
		require_once ('general/login_page.php');
		db_pandora_audit("Logon Failed", "Invalid login: ".$nick, $nick);
		exit;
	}
}
else {
	require_once ("include/config.php");
	require_once ("include/functions_reporting.php");
	require_once ("include/auth/mysql.php");	
}

global $config;

check_login ();

$id_report = (int) get_parameter ('id');

if (! $id_report) {
	db_pandora_audit("HACK Attempt",
			  "Trying to access graph viewer without valid ID");
	require ("general/noaccess.php");
	exit;
}

$report = db_get_row ('treport', 'id_report', $id_report);

$report["datetime"] = get_system_time();

if (! check_acl ($config['id_user'], $report['id_group'], "AR")) {
	db_pandora_audit("ACL Violation","Trying to access graph reader");
	include ("general/noaccess.php");
	exit;
}

/* Check if the user can see the graph */
if ($report['private'] && ($report['id_user'] != $config['id_user'] && ! is_user_admin($config['id_user']))) {
	return;
}

header ('Content-type: application/xml; charset="utf-8"', true);

echo '<?xml version="1.0" encoding="UTF-8" ?>'; //' - this is to mislead highlighters giving crap about the PHP closing tag

$date = (string) get_parameter ('date', date ('Y-m-j'));
$time = (string) get_parameter ('time', date ('h:iA'));

$datetime = strtotime ($date.' '.$time);

if ($datetime === false || $datetime == -1) {
	echo "<error>Invalid date selected</error>"; //Not translatable because this is an error message and might have to be used on the other end
	exit;
}

$group_name = groups_get_name ($report['id_group']);
switch ($config["dbtype"]) {
	case "mysql":
		$contents = db_get_all_rows_field_filter ('treport_content', 'id_report', $id_report, '`order`');
		break;
	case "postgresql":
	case "oracle":
		$contents = db_get_all_rows_field_filter ('treport_content', 'id_report', $id_report, '"order"');
		break;
}

$time = get_system_time ();
echo '<report>';
echo '<generated><unix>'.$time.'</unix>';
echo '<rfc2822>'.date ("r",$time).'</rfc2822></generated>';

$xml["id"] = $id_report;
$xml["name"] = io_safe_output_xml ($report['name']);
$xml["description"] = io_safe_output_xml($report['description']);
$xml["group"]["id"] = $report['id_group'];
$xml["group"]["name"] = io_safe_output_xml ($group_name);

if ($contents === false) {
	$contents = array ();
}

xml_array ($xml);

echo '<reports>';
$counter = 0;

foreach ($contents as $content) {
	echo '<object id="'.$counter.'">';
	$data = array ();
	$data["module"] = io_safe_output_xml (db_get_value ('nombre', 'tagente_modulo', 'id_agente_modulo', $content['id_agent_module']));
	$data["agent"] = io_safe_output_xml (modules_get_agentmodule_agent_name ($content['id_agent_module']));
	$data["period"] = human_time_description_raw ($content['period']);
	$data["uperiod"] = $content['period'];
	$data["type"] = $content["type"];

    $session_id = session_id();

	switch ($content["type"]) {
		case 1:
		case 'simple_graph':	
			$data["title"] = __('Simple graph');
			
			$img = grafico_modulo_sparse($content['id_agent_module'],
				$content['period'], 0, 720,
				230, '', null, false, true, false, $datetime, '', 0, 0, true, true);
			preg_match("/src='(.*)'/", $img, $matches);
			$url = $matches[1];
			$url = "<![CDATA[".$url."]]>";
			$data["objdata"]["img"] = $url; 
			
			break;
		case 'simple_baseline_graph':	
			$data["title"] = __('Simple baseline graph');
			
			$img = grafico_modulo_sparse($content['id_agent_module'],
				$content['period'], 0, 720,
				230, '', null, false, true, false, ($datetime + $content['period']), '', true, 0, true, true);
				
			preg_match("/src='(.*)'/", $img, $matches);
			$url = $matches[1];
			
			$data["objdata"]["img"] = $url; 
			break;
		case 2:
		case 'custom_graph':
			$graph = db_get_row ("tgraph", "id_graph", $content['id_gs']);
			$data["title"] = __('Custom graph');
			$data["objdata"]["img_name"] = $graph["name"];
	
			$result = db_get_all_rows_field_filter ("tgraph_source","id_graph",$content['id_gs']);
			$modules = array ();
			$weights = array ();
		
			if ($result === false) {
				$result = array();
			}
	
			foreach ($result as $content2) {
				array_push ($modules, $content2['id_agent_module']);
				array_push ($weights, $content2["weight"]);
			}
			
			$img = $data[0] = 	graphic_combined_module(
				$modules,
				$weights,
				$content['period'],
				$sizgraph_w, $sizgraph_h,
				'Combined%20Sample%20Graph',
				'',
				0,
				0,
				0,
				$graph["stacked"],
				$datetime);
			
			preg_match("/src='(.*)'/", $img, $matches);
			$url = $matches[1];
				
			$data["objdata"]["img"] = $url;
			
			break;
		case 3:
		case 'SLA':
			$data["title"] = __('S.L.A.');
	
			$slas = db_get_all_rows_field_filter ('treport_content_sla_combined','id_report_content', $content['id_rc']);
			if ($slas === false) {
				$data["objdata"]["error"] = __('There are no SLAs defined');
				$slas = array ();
			}
	
			$data["objdata"]["sla"] = array ();
			$sla_failed = false;
			
			foreach ($slas as $sla) {
				$sla_data = array ();
				$sla_data["agent"] = modules_get_agentmodule_agent_name ($sla['id_agent_module']);
				$sla_data["module"] = modules_get_agentmodule_name ($sla['id_agent_module']);
				$sla_data["max"] = $sla['sla_max'];
				$sla_data["min"] = $sla['sla_min'];
				$sla_value = reporting_get_agentmodule_sla ($sla['id_agent_module'], $content['period'], $sla['sla_min'], $sla['sla_max'], $datetime, $content, $content['time_from'], $content['time_to']);
				if ($sla_value === false) {
					$sla_data["error"] = __('Unknown');
				} else {
					if ($sla_value < $sla['sla_limit']) {
						$sla_data["failed"] = true;
					}
					$sla_data["value"] = format_numeric ($sla_value);
				}
				array_push ($data["objdata"]["sla"], $sla_data);
			}
			break;
		case 6:
		case 'monitor_report':
			$data["title"] = __('Monitor report');
			$monitor_value = reporting_get_agentmodule_sla ($content['id_agent_module'], $content['period'], 1, false, $datetime);
			if ($monitor_value === false) {
				$monitor_value = __('Unknown');
			} else {
				$monitor_value = format_numeric ($monitor_value);
			}
			$data["objdata"]["good"] = $monitor_value;
			if ($monitor_value !== __('Unknown')) {
				$monitor_value = format_numeric (100 - $monitor_value);
			}
			$data["objdata"]["bad"] = $monitor_value;
			break;
		case 7:
		case 'avg_value':
			$data["title"] = __('Avg. Value');
			$data["objdata"] = reporting_get_agentmodule_data_average ($content['id_agent_module'], $content['period'], $datetime);
			if ($data["objdata"] === false) {
				$data["objdata"] = __('Unknown');
			} else {
				$data["objdata"] = format_numeric ($data["objdata"]);
			}
			break;
		case 8:
		case 'max_value':
			$data["title"] = __('Max. Value');
			$data["objdata"] = reporting_get_agentmodule_data_max ($content['id_agent_module'], $content['period'], $datetime);
			if ($data["objdata"] === false) {
				$data["objdata"] = __('Unknown');
			} else {
				$data["objdata"] = format_numeric ($data["objdata"]);
			}
			break;
		case 9:
		case 'min_value':
			$data["title"] = __('Min. Value');
			$data["objdata"] = reporting_get_agentmodule_data_min ($content['id_agent_module'], $content['period'], $datetime);
			if ($data["objdata"] === false) {
				$data["objdata"] = __('Unknown');
			} else {
				$data["objdata"] = format_numeric ($data["objdata"]);
			}
			break;
		case 10:
		case 'sumatory':
			$data["title"] = __('Sumatory');
			$data["objdata"] = reporting_get_agentmodule_data_sum ($content['id_agent_module'], $content['period'], $datetime);
			if ($data["objdata"] === false) {
				$data["objdata"] = __('Unknown');
			} else {
				$data["objdata"] = format_numeric ($data["objdata"]);
			}
			break;
		case 'agent_detailed_event':
		case 'event_report_agent':
			$data["title"] = __('Agent detailed event');
			
			$data["objdata"]["event_report_agent"] = array ();
			
			$date = get_system_time ();
			
			$events = events_get_agent ($content['id_agent'], $content['period'], $date );
			if (empty ($events)) {
				$events = array ();
			}
			
			foreach ($events as $event) {
				$objdata = array ();
				$objdata['event'] = $event['evento'];
				$objdata['event_type'] = $event['event_type'];
				$objdata['criticity'] = get_priority_name($event['criticity']);
				$objdata['count'] = $event['count_rep'];
				$objdata['timestamp'] = $event['time2'];
				array_push ($data["objdata"]["event_report_agent"], $objdata);
			}
			break;
		case 'text':
			$data["title"] = __('Text');
			$data["objdata"] = "<![CDATA[";
			$data["objdata"] .= io_safe_output($content["text"]);
			$data["objdata"] .= "]]>";
			break;
		case 'sql':
			$data["title"] = __('SQL');
			
			//name tags of row
			if ($content['header_definition'] != '') {
				$tags = explode('|', $content['header_definition']);
			}
			
			if ($content['treport_custom_sql_id'] != 0) {
				switch ($config["dbtype"]) {
					case "mysql":
						$sql = db_get_value_filter('`sql`', 'treport_custom_sql', array('id' => $content['treport_custom_sql_id']));
						break;
					case "postgresql":
						$sql = db_get_value_filter('"sql"', 'treport_custom_sql', array('id' => $content['treport_custom_sql_id']));
						break;
					case "oracle":
						$sql = db_get_value_filter('sql', 'treport_custom_sql', array('id' => $content['treport_custom_sql_id']));
						break;
				}
			}
			else {
				$sql = $content['external_source'];
			}
			
			$sql = safe_output ($sql);
			$result = db_get_all_rows_sql($sql);
			if ($result === false) {
				$result = array();
			}
			
			if (isset($result[0])) {
				for ($i = 0; $i < (count($result[0]) - count($tags)); $i) {
					$tags[] = 'unname_' . $i;
				}
			}
			
			$data["objdata"]["data"] = array ();
			
			foreach ($result as $row) {
				$objdata = array ();
				$i = 0;
				foreach ($row as $column) {
					$objdata[$tags[$i]] = $column;
					$i++;
				}
				array_push($data["objdata"]["data"], $objdata);
			}
			break;
		case 'event_report_group':
			$data["title"] = __('Group detailed event');
			$data['group'] = groups_get_name($content['id_agent']);
			$data["objdata"]["event_report_group"] = array();
			
			$events = reporting_get_group_detailed_event($content['id_agent'], $content['period'], $report["datetime"], true, false);
			
			foreach ($events->data as $eventRow) {
				$objdata = array();
				$objdata['event_name'] = $eventRow[0];
				$objdata['event_type'] = $eventRow[1];
				$objdata['criticity'] = $eventRow[2];
				$objdata['timestamp'] = $eventRow[3];
				
				array_push($data["objdata"]["event_report_group"], $objdata);
			}
			break;
		case 'event_report_module':
			$data["title"] = __('Agents detailed event');
			$data["objdata"]["event_report_module"] = array();
			
			$events = reporting_get_module_detailed_event($content['id_agent_module'], $content['period'], $report["datetime"], true, false);
			
			foreach ($events->data as $eventRow) {
				$objdata = array();
				$objdata['event_name'] = $eventRow[0];
				$objdata['event_type'] = $eventRow[1];
				$objdata['criticity'] = $eventRow[2];
				$objdata['count'] = $eventRow[3];
				$objdata['timestamp'] = $eventRow[4];
				
				array_push($data["objdata"]["event_report_module"], $objdata);
			}
			break;
		case 'alert_report_module':
			$data["title"] = __('Alert report module');
			$data["objdata"]["alert_report_module"] = array();
		
			$alerts = reporting_alert_reporting_module ($content['id_agent_module'], $content['period'], $report["datetime"], true, false);
			
			foreach ($alerts->data as $row) {
				$objdata = array();
				
				$actionsHtml = strip_tags($row[2], '<li>');
				$actions = explode('</li>', $actionsHtml);
				
				$objdata['template'] = $row[1];
				
				$objdata['action'] = array();
				foreach ($actions as $action) {
					$actionText = strip_tags($action);
					if ($actionText == '') {
						continue;
					}
					$objdata['action'][] = $actionText;
				}
				
				$firedHtml = strip_tags($row[3], '<li>');
				$fireds= explode('</li>', $firedHtml);
				
				$objdata['fired'] = array();
				foreach ($fireds as $fired) {
					$firedText = strip_tags($fired);
					if ($firedText == '') {
						continue;
					}
					$objdata['fired'][] = $firedText;
				}
				array_push($data["objdata"]["alert_report_module"], $objdata);
			}
			break;
		case 'alert_report_agent':
			$data["title"] = __('Alert report agent');
			
			$alerts = reporting_alert_reporting_agent ($content['id_agent'], $content['period'], $report["datetime"], true, false);
			$data["objdata"]["alert_report_agent"] = array();
			
			foreach ($alerts->data as $row) {
				$objdata = array();
				
				$objdata['module'] = $row[0];
				$objdata['template'] = $row[1];
				
				$actionsHtml = strip_tags($row[2], '<li>');
				$actions = explode('</li>', $actionsHtml);
				
				$objdata['action'] = array();
				foreach ($actions as $action) {
					$actionText = strip_tags($action);
					if ($actionText == '') {
						continue;
					}
					$objdata['action'][] = $actionText;
				}
				
				$firedHtml = strip_tags($row[3], '<li>');
				$fireds= explode('</li>', $firedHtml);
				
				$objdata['fired'] = array();
				foreach ($fireds as $fired) {
					$firedText = strip_tags($fired);
					if ($firedText == '') {
						continue;
					}
					$objdata['fired'][] = $firedText;
				}
				
				array_push($data["objdata"]["alert_report_agent"], $objdata);
			}
			break;
		case 'url':
			$data["title"] = __('Import text from URL');
				
			$curlObj = curl_init();
			
			curl_setopt($curlObj, CURLOPT_URL, $content['external_source']);
			curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
	        $output = curl_exec($curlObj);
			curl_close($curlObj);
	        
			$data["objdata"] = $output;
			break;
		case 'database_serialized':
			$data["title"] = __('Serialize data');
			
			//Create the head
			$tags = array();
			if ($content['header_definition'] != '') {
				$tags = explode('|', $content['header_definition']);
			}
			array_unshift($tags, 'Date');
			
			$datelimit = $report["datetime"] - $content['period'];
			
			$result = db_get_all_rows_sql('SELECT *
				FROM tagente_datos_string
				WHERE id_agente_modulo = ' . $content['id_agent_module'] . '
					AND utimestamp > ' . $datelimit . ' AND utimestamp <= ' . $report["datetime"]);
			if ($result === false) {
				$result = array();
			}
			
			$data["objdata"]["data"] = array ();
			foreach ($result as $row) {
				$date = date ($config["date_format"], $row['utimestamp']);
				$serialized = $row['datos'];
				$rowsUnserialize = explode($content['line_separator'], $serialized);
				foreach ($rowsUnserialize as $rowUnser) {
					$columnsUnserialize = explode($content['column_separator'], $rowUnser);
					array_unshift($columnsUnserialize, $date);
					
					$objdata = array ();
					$i = 0;
					foreach ($columnsUnserialize as $column) {
						$objdata[$tags[$i]] = $column;
						$i++;
					}
					array_push($data["objdata"]["data"], $objdata);
				}
			}
			break;
		case 'TTRT':
			$ttr = reporting_get_agentmodule_ttr ($content['id_agent_module'], $content['period'], $report["datetime"]);
			if ($ttr === false) {
				$ttr = __('Unknown');
			} else if ($ttr != 0) {
				$ttr = human_time_description_raw ($ttr);
			}

			$data["title"] = __('TTRT');
			$data["objdata"] = $ttr;
			break;
		case 'TTO':
			$tto = reporting_get_agentmodule_tto ($content['id_agent_module'], $content['period'], $report["datetime"]);
			if ($tto === false) {
				$tto = __('Unknown');
			} else if ($tto != 0) {
				$tto = human_time_description_raw ($tto);
			}
				
			$data["title"] =  __('TTO');
			$data["objdata"] = $tto;
			break;
		case 'MTBF':
			$mtbf = reporting_get_agentmodule_mtbf ($content['id_agent_module'], $content['period'], $report["datetime"]);
			if ($mtbf === false) {
				$mtbf = __('Unknown');
			} else if ($mtbf != 0) {
				$mtbf = human_time_description_raw ($mtbf);
			}
				
			$data["title"] = __('MTBF');
			$data["objdata"] = $mtbf;
			break;
		case 'MTTR':
			$mttr = reporting_get_agentmodule_mttr ($content['id_agent_module'], $content['period'], $report["datetime"]);
			if ($mttr === false) {
				$mttr = __('Unknown');
			} else if ($mttr != 0) {
				$mttr = human_time_description_raw ($mttr);
			}
				
			$data["title"] = __('MTTR');
			$data["objdata"] = $mttr;
			break;
		case 'inventory':
			$data["title"] = __('Inventory');
			$data["objdata"]["inventory"] = array();
			
			$es = json_decode($content['external_source'], true);
			
			$id_agent = $es['id_agents'];
			$module_name = $es['inventory_modules'];
			$date = $es['date'];

			$data["objdata"]["inventory"] = inventory_get_data((array)$id_agent,(array)$module_name,$date,'',false, 'array');
			break;
		case 'inventory_changes':
			$data["title"] = __('Inventory changes');
			$data["objdata"]["inventory_changes"] = array();
			
			$es = json_decode($content['external_source'], true);
			
			$id_agent = $es['id_agents'];
			$module_name = $es['inventory_modules'];

			$data["objdata"]["inventory_changes"] = inventory_get_changes($id_agent, $module_name, $report["datetime"] - $content['period'], $report["datetime"], 'array');

			break;
	}
	xml_array ($data);
	echo '</object>';
	$counter++;
}

echo '</reports></report>';
?>
