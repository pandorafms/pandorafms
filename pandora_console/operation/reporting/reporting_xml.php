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
enterprise_include_once ('include/functions_metaconsole.php');

function array2XML($data, $root = null, $xml = NULL) {
	if ($xml == null) {
		$xml = simplexml_load_string(
			"<?xml version='1.0' encoding='UTF-8'?>\n<" . $root . " />");
	}
	
	foreach($data as $key => $value) {
		if (is_numeric($key)) {
			$key = "item_" . $key;
		}
		
		if (is_array($value)) {
			$node = $xml->addChild($key);
			array2XML($value, $root, $node);
		}
		else {
			$value = htmlentities($value);
			
			if (!is_numeric($value) && !is_bool($value)) {
				if (!empty($value)) {
					$xml->addChild($key, $value);
				}
			}
			else {
				$xml->addChild($key, $value);
			}
		}
	}
	
	return html_entity_decode($xml->asXML());
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

$date_mode = get_parameter('date_mode', 'none');

$period = null;
switch ($date_mode) {
	case 'none':
	case 'end_time':
		// Get different date to search the report.
		$date = (string) get_parameter ('date', date ('Y-m-j'));
		$time = (string) get_parameter ('time', date ('h:iA'));
		break;
	case 'init_and_end_time':
		// Get different date to search the report.
		$date = (string) get_parameter ('date', date ('Y-m-j'));
		$time = (string) get_parameter ('time', date ('h:iA'));
		
		// Calculations in order to modify init date of the report
		$date_init_less = strtotime(date ('Y-m-j')) - SECONDS_1DAY;
		
		$date_init = get_parameter('date_init', date ('Y-m-j', $date_init_less));
		$time_init = get_parameter('time_init', date ('h:iA'));
		$datetime_init = strtotime ($date_init.' '.$time_init);
		
		$period = strtotime ($date.' '.$time) - $datetime_init;
		break;
}


$report = reporting_make_reporting_data($id_report, $date, $time,
	$period, 'static');

//------- Removed the unused fields ------------------------------------
unset($report['header']);
unset($report['first_page']);
unset($report['footer']);
unset($report['custom_font']);
unset($report['id_template']);
unset($report['id_group_edit']);
unset($report['metaconsole']);
unset($report['private']);
unset($report['custom_logo']);
//----------------------------------------------------------------------


$xml = null;
$xml = array2XML($report, "report", $xml);
//~ $xml = preg_replace("/(<\/[^>]+>)/", "$1\n", $xml);
$xml = preg_replace("/(<[^>]+>)(<[^>]+>)(<[^>]+>)/", "$1\n$2\n$3", $xml);
$xml = preg_replace("/(<[^>]+>)(<[^>]+>)/", "$1\n$2", $xml);

header("Content-Type: application/xml; charset=utf-8");

echo $xml;
?>
