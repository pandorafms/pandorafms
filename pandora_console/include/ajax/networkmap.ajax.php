<?php

//Pandora FMS- http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

// Login check
check_login ();

if (! check_acl ($config['id_user'], 0, "IR")) {
	db_pandora_audit("ACL Violation",
		"Trying to access report builder");
	require ("general/noaccess.php");
	exit;
}

$action = get_parameter('action');

//error_reporting(E_ALL);
//ini_set("display_errors", 1);

switch($action) {
	case 'get_networkmap_summary':
		$stats = get_parameter('stats', array());
		$stats = json_decode(base64_decode($stats),true);
		$metaconsole = (bool)get_parameter('metaconsole', false);
		
		$hack_metaconsole = '';
		if ($metaconsole) {
			$hack_metaconsole = '../../';
		}
		
		$summary = '<br>';
		
		if (isset($stats['policies'])) {
			$summary .= count($stats['policies']) . " x " .
				html_print_image($hack_metaconsole . 'images/policies.png',true) . ' '.
				__('Policies') . "<br>";
		}
		
		if (isset($stats['groups'])) {
			// TODO: GET STATUS OF THE GROUPS AND ADD IT TO SUMMARY
			$summary .= count($stats['groups']) . " x " .
				html_print_image($hack_metaconsole . 'images/group.png',true) . ' ' .
				__('Groups') . "<br>";
		}
		
		if (isset($stats['agents'])) {
			if ($metaconsole) {
				include_once ('include/functions_reporting.php');
				
				$servers = db_get_all_rows_sql ("SELECT *
					FROM tmetaconsole_setup");
				if ($servers === false)
					$servers = array();
				
				$total_agents = 0;
				
				foreach ($servers as $server) {
					// If connection was good then retrieve all data server
					if (metaconsole_load_external_db ($server)) {
						$connection = true;
					}
					else {
						$connection = false;
					}
					
					if ($connection)
						$data = reporting_get_group_stats();
					
					metaconsole_restore_db();
					
					$total_agents += $data["total_agents"];
				}
				
				
				$total_agents = format_numeric($total_agents);
				
				$summary .= $total_agents .
					" x " . html_print_image($hack_metaconsole . 'images/bricks.png',true) .
					' ' . __('Agents') . "<br>";
			}
			else {
				$summary .= count($stats['agents']) .
					" x " . html_print_image($hack_metaconsole . 'images/bricks.png',true) .
					' ' . __('Agents') . "<br>";
			}
		}
		
		if (isset($stats['modules'])) {
			// TODO: GET STATUS OF THE MODULES AND ADD IT TO SUMMARY
			$summary .= count($stats['modules'])." x ".html_print_image('images/brick.png',true).' '.__('Modules')."<br>";
		}
		
		echo '<h3>'.__('Map summary').'</h3><strong>'.$summary.'</strong>';
		break;
	case 'get_networkmap_summary_pandora_server':
		$id_server = (int)get_parameter('id_server', 0);
		$stats = get_parameter('stats', array());
		$stats = json_decode(base64_decode($stats),true);
		$metaconsole = (bool)get_parameter('metaconsole', false);
		
		$hack_metaconsole = '';
		if ($metaconsole) {
			$hack_metaconsole = '../../';
		}
		
		$summary = '<br>';
		
		if (isset($stats['agents'])) {
			if ($metaconsole) {
				include_once ('include/functions_reporting.php');
				
				$servers = db_get_all_rows_sql ("SELECT *
					FROM tmetaconsole_setup
					WHERE id = " . $id_server);
				if ($servers === false)
					$servers = array();
				
				$total_agents = 0;
				
				foreach ($servers as $server) {
					// If connection was good then retrieve all data server
					if (metaconsole_load_external_db ($server)) {
						$connection = true;
					}
					else {
						$connection = false;
					}
					
					if ($connection)
						$data = reporting_get_group_stats();
					
					metaconsole_restore_db();
					
					$total_agents += $data["total_agents"];
				}
				
				
				$total_agents = format_numeric($total_agents);
				
				$summary .= $total_agents .
					" x " . html_print_image($hack_metaconsole . 'images/bricks.png',true) .
					' ' . __('Agents') . "<br>";
			}
			else {
				$summary .= count($stats['agents']) .
					" x " . html_print_image($hack_metaconsole . 'images/bricks.png',true) .
					' ' . __('Agents') . "<br>";
			}
		}
		echo '<h3>'.__('Map summary').'</h3><strong>'.$summary.'</strong>';
		break;
}
?>