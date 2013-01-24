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
include_once ('include/functions_netflow.php');
include_once ('include/functions_xml.php');
enterprise_include_once ('include/functions_metaconsole.php');

function xml_array ($array, $buffer_file = array()) {
	
	foreach ($array as $name => $value) {
		//si coincide con el nivel de anidación y existe el fichero
		$file_to_print = false;
		if(isset($buffer_file[$name]) && file_exists($buffer_file[$name])) {
			$file_to_print = $buffer_file[$name];
		}
		
		if (is_int ($name)) {
			echo "<object id=\"".$name."\">";
			$name = "object";
		}
		else {
			echo "<".$name.">";
		}
		
		if (is_array ($value)) {
			//si es la ruta al fichero que contiene el xml
			if(is_string($file_to_print)) {
					$file = fopen($file_to_print, 'r');
					while (!feof($file)) {
						$buffer = fgets($file);
						echo "$buffer";
					}
				$file_to_print = false;
			}
			xml_array ($value, $file_to_print);
		}
		else {
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

if (!$id_report) {
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

if ($report['id_group'] != 0 &&
	!is_user_admin ($config['id_user'])) {
	include ("general/noaccess.php");
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

$group_name = groups_get_name ($report['id_group'], true);

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

	$data["period"] = human_time_description_raw ($content['period']);
	$data["uperiod"] = $content['period'];
	$data["type"] = $content["type"];
	
	// Support for metaconsole
	$server_name = $content ['server_name'];
	
	// Disable remote connections for netflow report items
	if ($content['type'] != 'netflow_area' &&
		$content['type'] != 'netflow_pie' &&
		$content['type'] != 'netflow_data' &&
		$content['type'] != 'netflow_statistics' &&
		$content['type'] != 'netflow_summary') {
		
		$remote_connection = 1;
	}
	else {
		$remote_connection = 0;
	}
	
	if (($config ['metaconsole'] == 1) && ($server_name != '') && defined('METACONSOLE') && $remote_connection == 1) {
		$connection = metaconsole_get_connection($server_name);
		if (metaconsole_connect($connection) != NOERR){
			//ui_print_error_message ("Error connecting to ".$server_name);
		}
	}
	
	$session_id = session_id();
	
	switch ($content["type"]) {
		
		case 1:
		case 'simple_graph':
			$data["module"] = io_safe_output_xml (db_get_value ('nombre', 'tagente_modulo', 'id_agente_modulo', $content['id_agent_module']));
			$data["agent"] = io_safe_output_xml (modules_get_agentmodule_agent_name ($content['id_agent_module']));
			
			$data["title"] = __('Simple graph');
			$data["objdata"] = array();
			
			$date_end = time();
			$date_init = $date_end - $content['period'];
			///
			$temp_file = $config['attachment_store'] . '/simple_graph_' . $time.'_'.$content['id_rc'] . '.tmp';
			$file = fopen ($temp_file, 'a+');
			
			$buffer_file["objdata"] = $config['attachment_store'] . '/simple_graph_' . $time.'_'.$content['id_rc'] . '.tmp';
			
			$limit = 1000;
			$offset = 0;
			
			$sql_count = "SELECT COUNT(id_agente_modulo)
				FROM tagente_datos
				WHERE id_agente_modulo=".$content['id_agent_module']." 
					AND (utimestamp>=$date_init AND utimestamp<=$date_end)";
			$data_count = db_get_value_sql($sql_count);
			
			if ($data_count == false) {
				$content_report = "    <simple_graph/>\n";
				$result = fwrite($file, $content_report);
				fclose($file);
			}
			else if ($data_count <= $limit) {
				$content_report = "    <simple_graph>\n";
				$result = fwrite($file, $content_report);
				fclose($file);
				
				$sql = 	"SELECT *
					FROM tagente_datos
					WHERE id_agente_modulo=".$content['id_agent_module']." 
						AND (utimestamp>=$date_init AND utimestamp<=$date_end)";
				
				$data_module = db_get_all_rows_sql($sql);
				xml_file_graph ($data_module, $temp_file);
				
				$file = fopen ($temp_file, 'a+');
				$content_report = "    </simple_graph>\n";
				$result = fwrite($file, $content_report);
			}
			else {
				$content_report = "    <simple_graph>\n";
				$result = fwrite($file, $content_report);
				fclose($file);
				
				$position = 0;
				while ($offset < $data_count) {
					
					$sql = 	"SELECT *
						FROM tagente_datos
						WHERE id_agente_modulo=".$content['id_agent_module']." 
							AND (utimestamp>=$date_init AND utimestamp<=$date_end) LIMIT $offset,$limit";
					$data_module = db_get_all_rows_sql($sql);
					
					$position = xml_file_graph ($data_module, $temp_file, $position);	
					$offset += $limit;
				}
				
				$file = fopen ($temp_file, 'a+');
				$content_report = "    </simple_graph>\n";
				$result = fwrite($file, $content_report);
				fclose($file);
			}
			break;
		case 'simple_baseline_graph':
			
			$data["module"] = io_safe_output_xml (db_get_value ('nombre', 'tagente_modulo', 'id_agente_modulo', $content['id_agent_module']));
			$data["agent"] = io_safe_output_xml (modules_get_agentmodule_agent_name ($content['id_agent_module']));	
			$data["title"] = __('Simple baseline graph');
			$data["objdata"] = array();
			
			$date_end = time();
			$date_init = $date_end - $content['period'];
			
			$temp_file = $config['attachment_store'] . '/simple_baseline_graph_' . $time.'_'.$content['id_rc'] . '.tmp';
			
			$file = fopen ($temp_file, 'a+');
			
			$buffer_file["objdata"] = $config['attachment_store'] . '/simple_baseline_graph_' . $time.'_'.$content['id_rc'] . '.tmp';
			
			$limit = 1000;
			$offset = 0;
			
			$sql_count = "SELECT COUNT(id_agente_modulo)
				FROM tagente_datos
				WHERE id_agente_modulo=".$content['id_agent_module']." 
					AND (utimestamp>=$date_init AND utimestamp<=$date_end)";
			$data_count = db_get_value_sql($sql_count);
			
			if ($data_count == false) {
				$content_report = "    <simple_baseline_graph/>\n";
				$result = fwrite($file, $content_report);
			}
			else if ($data_count <= $limit) {
				$content_report = "    <simple_baseline_graph>\n";
				$result = fwrite($file, $content_report);
				fclose($file);
				
				$sql = "SELECT *
					FROM tagente_datos
					WHERE id_agente_modulo=".$content['id_agent_module']." 
						AND (utimestamp>=$date_init AND utimestamp<=$date_end)";
				
				$data_module = db_get_all_rows_sql($sql);
				xml_file_graph ($data_module, $temp_file);
				
				$file = fopen ($temp_file, 'a+');
				$content_report = "    </simple_baseline_graph>\n";
				$result = fwrite($file, $content_report);
				fclose($file);
				
			}
			else {
				$content_report = "    <simple_baseline_graph>\n";
				$result = fwrite($file, $content_report);
				fclose($file);
				
				$position = 0;
				while ($offset < $data_count) {
					
					$sql = 	"SELECT * FROM tagente_datos WHERE id_agente_modulo=".$content['id_agent_module']." 
					AND (utimestamp>=$date_init AND utimestamp<=$date_end) LIMIT $offset,$limit";
					$data_module = db_get_all_rows_sql($sql);
					
					$position = xml_file_graph ($data_module, $temp_file, $position);	
					$offset += $limit;
				}
				
				$file = fopen ($temp_file, 'a+');
				$content_report = "    </simple_baseline_graph>\n";
				$result = fwrite($file, $content_report);
				fclose($file);
			}
			///
			break;
		case 2:
		case 'custom_graph':
		case 'automatic_custom_graph':
		
			$data["module"] = io_safe_output_xml (db_get_value ('nombre', 'tagente_modulo', 'id_agente_modulo', $content['id_agent_module']));
			$data["agent"] = io_safe_output_xml (modules_get_agentmodule_agent_name ($content['id_agent_module']));
			
			$graph = db_get_row ("tgraph", "id_graph", $content['id_gs']);
			$data["title"] = __('Custom graph');
			$data["objdata"] = array();
			
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
			
			$date_end = time();
			$date_init = $date_end - $content['period'];
			///
			$temp_file = $config['attachment_store'] . '/custom_graph_' . $time.'_'.$content['id_rc'] . '.tmp';

			$file = fopen ($temp_file, 'a+');

			$buffer_file["objdata"] = $config['attachment_store'] . '/custom_graph_' . $time.'_'.$content['id_rc'] . '.tmp';

			$limit = 1000;
			$offset = 0;

			$sql_count = "SELECT COUNT(id_agente_modulo) FROM tagente_datos WHERE id_agente_modulo=".$content2['id_agent_module']." 
					AND (utimestamp>=$date_init AND utimestamp<=$date_end)";
			$data_count = db_get_value_sql($sql_count);

			if ($data_count == false) {
				$content_report = "    <custom_graph/>\n";
				$result = fwrite($file, $content_report);
				fclose($file);
			} else if ($data_count <= $limit) {
				$content_report = "    <custom_graph>\n";
				$result = fwrite($file, $content_report);
				fclose($file);
				
				$sql = 	"SELECT * FROM tagente_datos WHERE id_agente_modulo=".$content2['id_agent_module']." 
								AND (utimestamp>=$date_init AND utimestamp<=$date_end)";

				$data_module = db_get_all_rows_sql($sql);
				xml_file_graph ($data_module, $temp_file);
				
				$file = fopen ($temp_file, 'a+');
				$content_report = "    </custom_graph>\n";
				$result = fwrite($file, $content_report);

			} else {
				$content_report = "    <custom_graph>\n";
				$result = fwrite($file, $content_report);
				fclose($file);
				
				$position = 0;
				while ($offset < $data_count) {
					
					$sql = 	"SELECT * FROM tagente_datos WHERE id_agente_modulo=".$content['id_agent_module']." 
					AND (utimestamp>=$date_init AND utimestamp<=$date_end) LIMIT $offset,$limit";
					$data_module = db_get_all_rows_sql($sql);
					
					$position = xml_file_graph ($data_module, $temp_file, $position);	
					$offset += $limit;
				}
				
				$file = fopen ($temp_file, 'a+');
				$content_report = "    </custom_graph>\n";
				$result = fwrite($file, $content_report);
				fclose($file);
			}
			///
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
				
				//Metaconsole connection
				$server_name = $sla ['server_name'];
				if (($config ['metaconsole'] == 1) && ($server_name != '') && defined('METACONSOLE')) {
					$connection = metaconsole_get_connection($server_name);
					if (metaconsole_connect($connection) != NOERR) {
						//ui_print_error_message ("Error connecting to ".$server_name);
						continue;
					}
				}
				
				$sla_data = array ();
				$sla_data["agent"] = modules_get_agentmodule_agent_name ($sla['id_agent_module']);
				$sla_data["module"] = modules_get_agentmodule_name ($sla['id_agent_module']);
				$sla_data["max"] = $sla['sla_max'];
				$sla_data["min"] = $sla['sla_min'];
				$sla_value = reporting_get_agentmodule_sla ($sla['id_agent_module'], $content['period'], $sla['sla_min'], $sla['sla_max'], $datetime, $content, $content['time_from'], $content['time_to']);
				if ($sla_value === false) {
					$sla_data["error"] = __('Unknown');
				}
				else {
					if ($sla_value < $sla['sla_limit']) {
						$sla_data["failed"] = true;
					}
					$sla_data["value"] = format_numeric ($sla_value);
				}
				array_push ($data["objdata"]["sla"], $sla_data);
				
				if (($config ['metaconsole'] == 1) && defined('METACONSOLE')) {
					//Restore db connection
					metaconsole_restore_db();
				}
				
			}
			break;
		case 6:
		case 'monitor_report':
			$data["title"] = __('Monitor report');
			$monitor_value = reporting_get_agentmodule_sla ($content['id_agent_module'], $content['period'], 1, false, $datetime);
			if ($monitor_value === false) {
				$monitor_value = __('Unknown');
			}
			else {
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
			$data["module"] = io_safe_output_xml (db_get_value ('nombre', 'tagente_modulo', 'id_agente_modulo', $content['id_agent_module']));
			$data["agent"] = io_safe_output_xml (modules_get_agentmodule_agent_name ($content['id_agent_module']));
			$data["title"] = __('Avg. Value');
			$data["objdata"]["avg_value"] = reporting_get_agentmodule_data_average ($content['id_agent_module'], $content['period'], $datetime);
			if ($data["objdata"]["avg_value"] === false) {
				$data["objdata"]["avg_value"] = __('Unknown');
			}
			else {
				$data["objdata"]["avg_value"] = format_numeric ($data["objdata"]["avg_value"]);
			}
			break;
		case 8:
		case 'max_value':
			$data["module"] = io_safe_output_xml (db_get_value ('nombre', 'tagente_modulo', 'id_agente_modulo', $content['id_agent_module']));
			$data["agent"] = io_safe_output_xml (modules_get_agentmodule_agent_name ($content['id_agent_module']));
			
			$data["title"] = __('Max. Value');
			$data["objdata"]["max_value"] = reporting_get_agentmodule_data_max ($content['id_agent_module'], $content['period'], $datetime);
			if ($data["objdata"]["max_value"] === false) {
				$data["objdata"]["max_value"] = __('Unknown');
			}
			else {
				$data["objdata"]["max_value"] = format_numeric ($data["objdata"]["max_value"]);
			}
			break;
		case 9:
		case 'min_value':
			$data["module"] = io_safe_output_xml (db_get_value ('nombre', 'tagente_modulo', 'id_agente_modulo', $content['id_agent_module']));
			$data["agent"] = io_safe_output_xml (modules_get_agentmodule_agent_name ($content['id_agent_module']));
			
			$data["title"] = __('Min. Value');
			$data["objdata"]["min_value"] = reporting_get_agentmodule_data_min ($content['id_agent_module'], $content['period'], $datetime);
			if ($data["objdata"]["min_value"] === false) {
				$data["objdata"]["min_value"] = __('Unknown');
			}
			else {
				$data["objdata"]["min_value"] = format_numeric ($data["objdata"]["min_value"]);
			}
			break;
		case 10:
		case 'sumatory':
			$data["module"] = io_safe_output_xml (db_get_value ('nombre', 'tagente_modulo', 'id_agente_modulo', $content['id_agent_module']));
			$data["agent"] = io_safe_output_xml (modules_get_agentmodule_agent_name ($content['id_agent_module']));
			
			$data["title"] = __('Sumatory');
			$data["objdata"]["summatory_value"] = reporting_get_agentmodule_data_sum ($content['id_agent_module'], $content['period'], $datetime);
			if ($data["objdata"]["summatory_value"] === false) {
				$data["objdata"]["summatory_value"] = __('Unknown');
			}
			else {
				$data["objdata"]["summatory_value"] = format_numeric ($data["objdata"]["summatory_value"]);
			}
			break;
		case 'agent_detailed_event':
		case 'event_report_agent':
			$data["title"] = __('Agent detailed event');
			$data["objdata"] = array ();
			
			$date = get_system_time ();
			///
			$temp_file = $config['attachment_store'] . '/event_report_agent_' . $time.'_'.$content['id_rc'] . '.tmp';
			$file = fopen ($temp_file, 'a+');
			$buffer_file["objdata"] = $config['attachment_store'] . '/event_report_agent_' . $time.'_'.$content['id_rc'] . '.tmp';
			
			$limit = 1000;
			$offset = 0;
			
			$datelimit = $date - $content['period'];
			
			$sql_count = "SELECT count(*) FROM (SELECT  count(*)
				FROM tevento
				WHERE id_agente =".$content['id_agent']." AND utimestamp > $datelimit AND utimestamp <= $date 
				GROUP BY id_agentmodule, evento) t1";
			$data_count = db_get_value_sql($sql_count);
			
			if ($data_count == false) {
				$content_report = "    <event_report_agent/>\n";
				$result = fwrite($file, $content_report);
				fclose($file);
			}
			else if ($data_count <= $limit) {
				$content_report = "    <event_report_agent>\n";
				$result = fwrite($file, $content_report);
				fclose($file);
				
				$sql = sprintf ('SELECT evento, event_type, criticity, count(*) as count_rep,
				max(timestamp) AS time2, id_agentmodule, estado, user_comment, tags, source, id_extra, owner_user
				FROM tevento
				WHERE id_agente = %d AND utimestamp > %d AND utimestamp <= %d 
				GROUP BY id_agentmodule, evento
				ORDER BY time2 DESC', $content['id_agent'], $datelimit, $date);
				
				$events = db_get_all_rows_sql ($sql);
				xml_file_event ($events, $temp_file,0, $content['id_agent']);
				
				$file = fopen ($temp_file, 'a+');
				$content_report = "    </event_report_agent>\n";
				$result = fwrite($file, $content_report);
				
				
			}
			else {
				$content_report = "    <event_report_agent>\n";
				$result = fwrite($file, $content_report);
				fclose($file);
				
				$position = 0;
				while ($offset < $data_count) {
					
					$sql = sprintf ('SELECT evento, event_type, criticity, count(*) as count_rep,
					max(timestamp) AS time2, id_agentmodule, estado, user_comment, tags, source, id_extra, owner_user
					FROM tevento
					WHERE id_agente = %d AND utimestamp > %d AND utimestamp <= %d 
					GROUP BY id_agentmodule, evento
					ORDER BY time2 DESC LIMIT %d,%d', $content['id_agent'], $datelimit, $date, $offset,$limit);
			
					$events = db_get_all_rows_sql ($sql);
					
					$position = xml_file_event ($events, $temp_file, $position, $content['id_agent']);	
					$offset += $limit;
				}
				
				$file = fopen ($temp_file, 'a+');
				$content_report = "    </event_report_agent>\n";
				$result = fwrite($file, $content_report);
				fclose($file);
			}
			///
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
			$data['group'] = groups_get_name($content['id_group'], true);
			$data["objdata"] = array();
			
			$id_group = groups_safe_acl ($config["id_user"], $content['id_group'], "AR");
			///
			if (!empty ($id_group)) {

				//An empty array means the user doesn't have access
				$datelimit = $report["datetime"] - $content['period'];

				$sql_count = sprintf ('SELECT count(*) FROM tevento
					WHERE utimestamp > %d AND utimestamp <= %d
					AND id_grupo IN (%s)
					ORDER BY utimestamp ASC',
					$datelimit, $report["datetime"], implode (",", $id_group));
					
				$data_count = db_get_value_sql($sql_count);
			
				$temp_file = $config['attachment_store'] . '/event_report_group_' . $time.'_'.$content['id_rc'] . '.tmp';
				$file = fopen ($temp_file, 'a+');
				$buffer_file["objdata"] = $config['attachment_store'] . '/event_report_group_' . $time.'_'.$content['id_rc'] . '.tmp';
				
				$limit = 1000;
				$offset = 0;
				
				if ($data_count == false) {
					$content_report = "    <event_report_group/>\n";
					$result = fwrite($file, $content_report);
					fclose($file);
				} else if ($data_count <= $limit) {
					$content_report = "    <event_report_group>\n";
					$result = fwrite($file, $content_report);
					fclose($file);
					
					$sql = sprintf ('SELECT * FROM tevento
					WHERE utimestamp > %d AND utimestamp <= %d
					AND id_grupo IN (%s)
					ORDER BY utimestamp ASC',
					$datelimit, $report["datetime"], implode (",", $id_group));
					$events = db_get_all_rows_sql($sql);
					xml_file_event ($events, $temp_file, 0, $content['id_agent']);
					
					$file = fopen ($temp_file, 'a+');
					$content_report = "    </event_report_group>\n";
					$result = fwrite($file, $content_report);

				} else {
					$content_report = "    <event_report_group>\n";
					$result = fwrite($file, $content_report);
					fclose($file);
					
					$position = 0;
					while ($offset < $data_count) {
						
						$sql = sprintf ('SELECT * FROM tevento
						WHERE utimestamp > %d AND utimestamp <= %d
						AND id_grupo IN (%s)
						ORDER BY utimestamp ASC LIMIT %d,%d',
						$datelimit, $report["datetime"], implode (",", $id_group), $offset,$limit);

						$events = db_get_all_rows_sql($sql);
						
						$position = xml_file_event ($events, $temp_file, $position, $content['id_agent']);	
						$offset += $limit;
					}
					
					$file = fopen ($temp_file, 'a+');
					$content_report = "    </event_report_group>\n";
					$result = fwrite($file, $content_report);
					fclose($file);
				}
			}
			///
			break;
		case 'event_report_module':

			$data["title"] = __('Agents detailed event');
			$data["objdata"] = array();
	
			$date = get_system_time ();
			$datelimit = $date - $content['period'];
			///	
			$sql_count = "SELECT count(*) FROM (SELECT  count(*)
				FROM tevento
				WHERE id_agente =".$content['id_agent']." AND utimestamp > $datelimit AND utimestamp <=". $date. 
				" GROUP BY id_agentmodule, evento) t1";
	
			$data_count = db_get_value_sql($sql_count);
			
			$temp_file = $config['attachment_store'] . '/event_report_module_' . $time.'_'.$content['id_rc'] . '.tmp';
			$file = fopen ($temp_file, 'a+');
			$buffer_file["objdata"] = $config['attachment_store'] . '/event_report_module_' . $time.'_'.$content['id_rc'] . '.tmp';
				
			$limit = 1000;
			$offset = 0;
				
			if ($data_count == false) {
				$content_report = "    <event_report_module/>\n";
				$result = fwrite($file, $content_report);
				fclose($file);
			} else if ($data_count <= $limit) {
				$content_report = "    <event_report_module>\n";
				$result = fwrite($file, $content_report);
				fclose($file);
				
				$sql = "SELECT evento, event_type, criticity, count(*) as count_rep, max(timestamp) AS time2, id_agentmodule, estado, user_comment, tags, source, id_extra, owner_user
				FROM tevento
				WHERE id_agente =".$content['id_agent']." AND utimestamp > $datelimit AND utimestamp <=". $date. 
				" GROUP BY id_agentmodule, evento";

				$events = db_get_all_rows_sql($sql);

				xml_file_event ($events, $temp_file, 0, $content['id_agent']);
				
				$file = fopen ($temp_file, 'a+');
				$content_report = "    </event_report_module>\n";
				$result = fwrite($file, $content_report);
				
			} else {
				$content_report = "    <event_report_module>\n";
				$result = fwrite($file, $content_report);
				fclose($file);
				
				$position = 0;
				while ($offset < $data_count) {
					
					$sql = sprintf ('SELECT evento, event_type, criticity, count(*) as count_rep, max(timestamp) AS time2, id_agentmodule, estado, user_comment, tags, source, id_extra, owner_user
					FROM tevento
					WHERE id_agentmodule = %d AND utimestamp > %d AND utimestamp <= %d 
					GROUP BY id_agentmodule, evento ORDER BY time2 DESC LIMIT %d,%d', $content['id_agent_module'], $datelimit, $date, $offset,$limit);

					$events = db_get_all_rows_sql($sql);
					
					$position = xml_file_event ($events, $temp_file, $position, $content['id_agent']);	
					$offset += $limit;
				}
				
				$file = fopen ($temp_file, 'a+');
				$content_report = "    </event_report_group>\n";
				$result = fwrite($file, $content_report);
				fclose($file);
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
			}
			else if ($ttr != 0) {
				$ttr = human_time_description_raw ($ttr);
			}
			
			$data["title"] = __('TTRT');
			$data["objdata"] = $ttr;
			break;
		case 'TTO':
			$tto = reporting_get_agentmodule_tto ($content['id_agent_module'], $content['period'], $report["datetime"]);
			if ($tto === false) {
				$tto = __('Unknown');
			}
			else if ($tto != 0) {
				$tto = human_time_description_raw ($tto);
			}
			
			$data["title"] =  __('TTO');
			$data["objdata"] = $tto;
			break;
		case 'MTBF':
			$mtbf = reporting_get_agentmodule_mtbf ($content['id_agent_module'], $content['period'], $report["datetime"]);
			if ($mtbf === false) {
				$mtbf = __('Unknown');
			}
			else if ($mtbf != 0) {
				$mtbf = human_time_description_raw ($mtbf);
			}
			
			$data["title"] = __('MTBF');
			$data["objdata"] = $mtbf;
			break;
		case 'MTTR':
			$mttr = reporting_get_agentmodule_mttr ($content['id_agent_module'], $content['period'], $report["datetime"]);
			if ($mttr === false) {
				$mttr = __('Unknown');
			}
			else if ($mttr != 0) {
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
		case 'agent_configuration':

			$agent_name = agents_get_name ($content['id_agent']);
				
			$sql = "SELECT * FROM tagente WHERE id_agente=".$content['id_agent'];
			$agent_data = db_get_row_sql($sql);
			
			$data["title"] = __('Configuration report for agent ').$agent_name;
			$data["agent"] = $agent_name;
			$data["objdata"] = array();

			/////
			$temp_file = $config['attachment_store'] . '/agent_configuration_' . $time.'_'.$content['id_rc'] . '.tmp';
			$file = fopen ($temp_file, 'a+');
			$buffer_file["objdata"] = $config['attachment_store'] . '/agent_configuration_' . $time.'_'.$content['id_rc'] . '.tmp';
			
			$content_report = "    <configuration_report_agent>\n";
			$result = fwrite($file, $content_report);
			fclose($file);
					
			xml_file_agent_data($agent_data, $temp_file, $agent_name);
			
			$userGroups = users_get_groups($config['id_user'], 'AR', false);
			if (empty($userGroups)) {
				return array();
			}
			$id_groups = array_keys($userGroups);
			if (!empty($id_groups)) {
						
				$sql_count = "SELECT count(*) FROM (SELECT *
					FROM tagente_modulo WHERE
					(
						1 = (
							SELECT is_admin
							FROM tusuario
							WHERE id_user = '".$config['id_user']."'
							)
					OR tagente_modulo.id_agente IN (
								SELECT id_agente
								FROM tagente
								WHERE id_grupo IN (" . implode(',', $id_groups) . ")
								)
					OR 0 IN (
							SELECT id_grupo
							FROM tusuario_perfil
							WHERE id_usuario ='".$config['id_user']."'
							AND id_perfil IN (
								SELECT id_perfil
								FROM tperfil WHERE agent_view = 1
								)
							)
					) AND id_agente=".$content['id_agent']." AND delete_pending = 0 
					ORDER BY nombre) t1";
				$data_count = db_get_value_sql($sql_count);
				
				$limit = 1000;
				$offset = 0;
				
				if ($data_count == false) {

					$content_report = "    	<module_configuration/>\n";
					$content_report .= "   </configuration_report_agent>\n";
					
					$file = fopen ($temp_file, 'a+');
					$result = fwrite($file, $content_report);
					fclose($file);
				} else if ($data_count <= $limit) {
					$content_report = "    	<module_configuration>\n";
					
					$file = fopen ($temp_file, 'a+');
					$result = fwrite($file, $content_report);
					fclose($file);
					
					$sql = "SELECT *
					FROM tagente_modulo WHERE
					(
						1 = (
							SELECT is_admin
							FROM tusuario
							WHERE id_user = '".$config['id_user']."'
							)
					OR tagente_modulo.id_agente IN (
								SELECT id_agente
								FROM tagente
								WHERE id_grupo IN (" . implode(',', $id_groups) . ")
								)
					OR 0 IN (
							SELECT id_grupo
							FROM tusuario_perfil
							WHERE id_usuario ='".$config['id_user']."'
							AND id_perfil IN (
								SELECT id_perfil
								FROM tperfil WHERE agent_view = 1
								)
							)
					) AND id_agente=".$content['id_agent']." AND delete_pending = 0 
					ORDER BY nombre";
				
					$modules = db_get_all_rows_sql ($sql);
					xml_file_agent_conf ($modules, $temp_file,0, $content['id_agent']);
					
					$file = fopen ($temp_file, 'a+');
					$content_report = "    	</module_configuration>\n";
					$content_report .= "    </configuration_report_agent>\n";
					$result = fwrite($file, $content_report);		

				} else {
					$content_report = "    	<module_configuration>\n";
					
					$file = fopen ($temp_file, 'a+');
					$result = fwrite($file, $content_report);
					fclose($file);
					
					$position = 0;
					while ($offset < $data_count) {
						
						$sql = "SELECT *
							FROM tagente_modulo WHERE
							(
								1 = (
									SELECT is_admin
									FROM tusuario
									WHERE id_user = '".$config['id_user']."'
									)
							OR tagente_modulo.id_agente IN (
										SELECT id_agente
										FROM tagente
										WHERE id_grupo IN (" . implode(',', $id_groups) . ")
										)
							OR 0 IN (
									SELECT id_grupo
									FROM tusuario_perfil
									WHERE id_usuario ='".$config['id_user']."'
									AND id_perfil IN (
										SELECT id_perfil
										FROM tperfil WHERE agent_view = 1
										)
									)
							) AND id_agente=".$content['id_agent']." AND delete_pending = 0 
							ORDER BY nombre
							LIMIT $offset,$limit";
				
						$modules = db_get_all_rows_sql ($sql);
						
						$position = xml_file_agent_conf ($modules, $temp_file, $position, $content['id_agent']);	
						$offset += $limit;
					}
					
					$file = fopen ($temp_file, 'a+');
					$content_report = "    		</module_configuration>\n";
					$content_report .= "    </configuration_report_agent>\n";
					$result = fwrite($file, $content_report);
					fclose($file);
				}
		
			}
			
			break;
		case 'group_configuration':

			$group_name = groups_get_name ($content['id_group'], true);
			
			$data["title"] = __('Configuration report for group ').$group_name;
			$data["group"] = $group_name;
			
			///
			$data["objdata"] = array();
			$temp_file = $config['attachment_store'] . '/group_configuration_' . $time.'_'.$content['id_rc'] . '.tmp';
			$file = fopen ($temp_file, 'a+');
			$buffer_file["objdata"] = $config['attachment_store'] . '/group_configuration_' . $time.'_'.$content['id_rc'] . '.tmp';
			
			$content_report = "    <configuration_report_group>\n";
			$content_report .= "    	<name>".$group_name."</name>\n";
			$content_report .= "    	<id>".$content['id_group']."</id>\n";
			$result = fwrite($file, $content_report);
			fclose($file);
			
			$sql = "SELECT * FROM tagente WHERE id_grupo=".$content['id_group'];
			$agents_list = db_get_all_rows_sql($sql);
			if ($agents_list === false)
				$agents_list = array();
			$i = 0;
			foreach ($agents_list as $key=>$agent) {
				$file = fopen ($temp_file, 'a+');
				$content_report = "	<object id=\"$i\">\n";
				$content_report .= "	<agent_data>\n";
				$result = fwrite($file, $content_report);
				xml_file_agent_data($agent, $temp_file);
				
				$userGroups = users_get_groups($config['id_user'], 'AR', false);
				if (empty($userGroups)) {
					return array();
				}
				$id_groups = array_keys($userGroups);
				if (!empty($id_groups)) {
					
					$sql_count = "SELECT count(*) FROM (SELECT *
						FROM tagente_modulo WHERE
						(
							1 = (
								SELECT is_admin
								FROM tusuario
								WHERE id_user = '".$config['id_user']."'
								)
						OR tagente_modulo.id_agente IN (
									SELECT id_agente
									FROM tagente
									WHERE id_grupo IN (" . implode(',', $id_groups) . ")
									)
						OR 0 IN (
								SELECT id_grupo
								FROM tusuario_perfil
								WHERE id_usuario ='".$config['id_user']."'
								AND id_perfil IN (
									SELECT id_perfil
									FROM tperfil WHERE agent_view = 1
									)
								)
						) AND id_agente=".$agent['id_agente']." AND delete_pending = 0 
						ORDER BY nombre) t1";

					$data_count = db_get_value_sql($sql_count);
					
					$limit = 1000;
					$offset = 0;
					
					if ($data_count == false) {
						$content_report = "    	<module_configuration/>\n";
						$content_report .= "   </agent_data>\n";
						
						$file = fopen ($temp_file, 'a+');
						$result = fwrite($file, $content_report);
						fclose($file);
						
					} else if ($data_count <= $limit) {
						$content_report = "    	<module_configuration>\n";
						
						$file = fopen ($temp_file, 'a+');
						$result = fwrite($file, $content_report);
						fclose($file);
						
						$sql = "SELECT *
						FROM tagente_modulo WHERE
						(
							1 = (
								SELECT is_admin
								FROM tusuario
								WHERE id_user = '".$config['id_user']."'
								)
						OR tagente_modulo.id_agente IN (
									SELECT id_agente
									FROM tagente
									WHERE id_grupo IN (" . implode(',', $id_groups) . ")
									)
						OR 0 IN (
								SELECT id_grupo
								FROM tusuario_perfil
								WHERE id_usuario ='".$config['id_user']."'
								AND id_perfil IN (
									SELECT id_perfil
									FROM tperfil WHERE agent_view = 1
									)
								)
						) AND id_agente=".$agent['id_agente']." AND delete_pending = 0 
						ORDER BY nombre";
					
						$modules = db_get_all_rows_sql ($sql);
						xml_file_agent_conf ($modules, $temp_file, 0, $content['id_agent']);
						
						$file = fopen ($temp_file, 'a+');
						$content_report = "    	</module_configuration>\n";
						$content_report .= "   </agent_data>\n";
						$result = fwrite($file, $content_report);
						

					} else {
						$content_report = "    	<module_configuration>\n";
						
						$file = fopen ($temp_file, 'a+');
						$result = fwrite($file, $content_report);
						fclose($file);
						
						$position = 0;
						while ($offset < $data_count) {
							
							$sql = "SELECT *
								FROM tagente_modulo WHERE
								(
									1 = (
										SELECT is_admin
										FROM tusuario
										WHERE id_user = '".$config['id_user']."'
										)
								OR tagente_modulo.id_agente IN (
											SELECT id_agente
											FROM tagente
											WHERE id_grupo IN (" . implode(',', $id_groups) . ")
											)
								OR 0 IN (
										SELECT id_grupo
										FROM tusuario_perfil
										WHERE id_usuario ='".$config['id_user']."'
										AND id_perfil IN (
											SELECT id_perfil
											FROM tperfil WHERE agent_view = 1
											)
										)
								) AND id_agente=".$agent['id_agente']." AND delete_pending = 0 
								ORDER BY nombre
								LIMIT $offset,$limit";
					
							$modules = db_get_all_rows_sql ($sql);
							
							$position = xml_file_agent_conf ($modules, $temp_file, $position, $content['id_agent']);	
							$offset += $limit;
						}
						
						$file = fopen ($temp_file, 'a+');
						$content_report = "    		</module_configuration>\n";
						$content_report .= "    </agent_data>\n";
						$result = fwrite($file, $content_report);
						fclose($file);
					}
				
				}
				$i++;
				
				$file = fopen ($temp_file, 'a+');
				$content_report = "    	</object>\n";
				$result = fwrite($file, $content_report);
				fclose($file);
			}
			$file = fopen ($temp_file, 'a+');
			$content_report = "    </configuration_report_group>\n";
			$result = fwrite($file, $content_report);
			fclose($file);
			/// 
			
			break;
		case 'netflow_area':
		case 'netflow_pie':
		case 'netflow_data':
		case 'netflow_statistics':
		case 'netflow_summary':
				// Read the report item
				$report_id = $report['id_report'];
				$content_id = $content['id_rc'];
				$max_aggregates= $content['top_n_value'];
				$type = $content['show_graph'];
				$description = io_safe_output ($content['description']);
				$resolution = $content['top_n'];
				$type = $content['type'];
				$period = $content['period'];
				
				// Calculate the start and end dates
				$end_date = $report['datetime'];
				$start_date = $end_date - $period;
				
				// Get item filters
				$filter = db_get_row_sql("SELECT *
					FROM tnetflow_filter
					WHERE id_sg = '" . (int)$content['text'] . "'", false, true);
				if ($description == '') {
					$description = io_safe_output ($filter['id_name']);
				}
				
				// Build a unique id for the cache
				//$unique_id = $report_id . '_' . $content_id . '_' . ($end_date - $start_date);
				
				$table->colspan[0][0] = 4;
				if ($filter['aggregate'] != 'none') {
					$data["title"] = $description . ' (' . __($filter['aggregate']) . '/' . __($filter['output']) . ')';
				}
				else { 
					$data["title"] = $description . ' (' . __($filter['output']) . ')';
				}
				
				//$data["objdata"]["netflow"] = netflow_draw_item ($start_date, $end_date, $resolution, $type, $filter, $max_aggregates, $unique_id, $server_name, 'XML');
				$data["objdata"]["netflow"] = netflow_draw_item ($start_date, $end_date, $resolution, $type, $filter, $max_aggregates, $server_name, 'XML');
				$buffer_file["objdata"] = $config['attachment_store'] . '/netflow_' . $time.'_'.$content['id_rc'] . '.tmp';
				$objdata_file = true;
			break;
	}
	
	xml_array ($data, $buffer_file);
	echo '</object>';
	$counter++;
	
	if (($config ['metaconsole'] == 1) && defined('METACONSOLE') && $remote_connection == 1) {
		//Restore db connection
		metaconsole_restore_db();
	}
	
}

echo '</reports></report>';
?>
