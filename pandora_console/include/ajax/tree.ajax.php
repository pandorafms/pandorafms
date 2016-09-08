<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2012 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Only accesible by ajax
if (is_ajax ()) {
	global $config;
	
	// Login check
	check_login ();
	
	require_once($config['homedir'] . "/include/class/Tree.class.php");
	enterprise_include_once("include/class/Tree.class.php");
	require_once($config['homedir'] . "/include/functions_reporting.php");
	require_once($config['homedir'] . "/include/functions_os.php");
	
	$getChildren = (bool) get_parameter('getChildren', 0);
	$getGroupStatus = (bool) get_parameter('getGroupStatus', 0);
	$getDetail = (bool) get_parameter('getDetail');
	
	if ($getChildren) {
		$type = get_parameter('type', 'group');
		$rootType = get_parameter('rootType', '');
		$id = get_parameter('id', -1);
		$rootID = get_parameter('rootID', -1);
		$serverID = get_parameter('serverID', false);
		$childrenMethod = get_parameter('childrenMethod', 'on_demand');

		$default_filters = array(
				'searchAgent' => '',
				'statusAgent' => AGENT_STATUS_ALL,
				'searchModule' => '',
				'statusModule' => -1,
				'groupID' => 0,
				'tagID' => 0,
			);
		$filter = get_parameter('filter', $default_filters);
		
		$agent_a = check_acl ($config['id_user'], 0, "AR");
		$agent_w = check_acl ($config['id_user'], 0, "AW");
		$access = ($agent_a == true) ? 'AR' : ($agent_w == true) ? 'AW' : 'AR';
		if (class_exists('TreeEnterprise')) {
			$tree = new TreeEnterprise($type, $rootType, $id, $rootID, $serverID, $childrenMethod, $access);
		}
		else {
			$tree = new Tree($type, $rootType, $id, $rootID, $serverID, $childrenMethod, $access);
		}
		
		$tree->setFilter($filter);
		ob_clean();
		echo json_encode(array('success' => 1, 'tree' => $tree->getArray()));
		return;
	}
	
	if ($getDetail) {
		require_once($config['homedir']."/include/functions_treeview.php");
		
		$id = (int) get_parameter('id');
		$type = (string) get_parameter('type');
		
		$server = array();
		if (is_metaconsole()) {
			$server_id = (int) get_parameter('serverID');
			$server = metaconsole_get_servers($server_id);
			
			if (metaconsole_connect($server) != NOERR)
				return;
		}
		
		ob_clean();
		
		echo '<div class="left_align">';
		if (!empty($id) && !empty($type)) {
			switch ($type) {
				case 'agent':
					treeview_printTable($id, $server, true);
					break;
				case 'module':
					treeview_printModuleTable($id, $server, true);
					break;
				case 'alert':
					treeview_printAlertsTable($id, $server, true);
					break;
				default:
					// Nothing
					break;
			}
		}
		echo '<br></div>';
		
		if (!empty($server) && is_metaconsole()) {
			metaconsole_restore_db();
		}
		
		return;
	}
	
	return;
}
?>