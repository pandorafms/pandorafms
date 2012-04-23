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

switch($action) {
	case 'get_networkmap_summary':
		$stats = get_parameter('stats', array());
		$stats = json_decode(base64_decode($stats),true);
		
		$summary = '<br>';
		
		if(isset($stats['policies'])) {
			$summary .= count($stats['policies'])." x ".html_print_image('images/policies.png',true).' '.__('Policies')."<br>";
		}
		
		if(isset($stats['groups'])) {
			// TODO: GET STATUS OF THE GROUPS AND ADD IT TO SUMMARY
			$summary .= count($stats['groups'])." x ".html_print_image('images/group.png',true).' '.__('Groups')."<br>";
		}
		
		if(isset($stats['agents'])) {
			$summary .= count($stats['agents'])." x ".html_print_image('images/bricks.png',true).' '.__('Agents')."<br>";
			// TODO: GET STATUS OF THE AGENTS AND ADD IT TO SUMMARY
			//~ $status_agents = array();
			//~ foreach($stats['agents'] as $id_agent) {
				//~ $st = agents_get_status($id_agent);
				//~ if(!isset($status_agents[$st])) {
					//~ $status_agents[$st] = 0;
				//~ }
				//~ $status_agents[$st] ++;
			//~ }
		}
		
		if(isset($stats['modules'])) {
			// TODO: GET STATUS OF THE MODULES AND ADD IT TO SUMMARY
			$summary .= count($stats['modules'])." x ".html_print_image('images/brick.png',true).' '.__('Modules')."<br>";
		}
		
		echo '<h3>'.__('Map summary').'</h3><strong>'.$summary.'</strong>';
		break;
}


?>
