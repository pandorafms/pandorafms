<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


// Load global vars
global $config;

check_login ();

if (! give_acl ($config['id_user'], 0, "AR")) {
	audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation",
		"Trying to access node graph builder");
	include ("general/noaccess.php");
	exit;
}

require_once ('include/functions_networkmap.php');

// Load variables
$layout = (string) get_parameter ('layout', 'radial');
$nooverlap = (int) get_parameter ('nooverlap', 0);
$pure = (int) get_parameter ('pure');
$zoom = (float) get_parameter ('zoom');
$ranksep = (float) get_parameter ('ranksep', 2.5);
$simple = (int) get_parameter ('simple', 0);
$regen = (int) get_parameter ('regen',1); // Always regen by default
$font_size = (int) get_parameter ('font_size', 12);
$group = (int) get_parameter ('group', 0);
$center = (int) get_parameter ('center', 0);
$activeTab = get_parameter ('tab', 'topology');
/* Main code */


if ($pure == 1) {
	$buttons['screen'] = array('active' => false,
		'text' => '<a href="index.php?sec=estado&amp;sec2=operation/agentes/networkmap&amp;tab='.$activeTab.'">' . 
				print_image("images/normalscreen.png", true, array ('title' => __('Normal screen'))) .'</a>');
			
} else {
	$buttons['screen'] = array('active' => false,
		'text' => '<a href="index.php?sec=estado&amp;sec2=operation/agentes/networkmap&amp;pure=1&amp;tab='.$activeTab.'">' . 
				print_image("images/fullscreen.png", true, array ('title' => __('Full screen'))) .'</a>');
}
if($config['enterprise_installed']) {
	$buttons['policies'] = array('active' => $activeTab == 'policies',
		'text' => '<a href="index.php?sec=estado&amp;sec2=operation/agentes/networkmap&amp;tab=policies&amp;pure='.$pure.'">' . 
				print_image("images/policies.png", true, array ("title" => __('Policies view'))) .'</a>');
}
			
$buttons['groups'] = array('active' => $activeTab == 'groups',
	'text' => '<a href="index.php?sec=estado&amp;sec2=operation/agentes/networkmap&amp;tab=groups&amp;pure='.$pure.'">' . 
			print_image("images/group.png", true, array ("title" => __('Groups view'))) .'</a>');
			
$buttons['topology'] = array('active' => $activeTab == 'topology',
	'text' => '<a href="index.php?sec=estado&amp;sec2=operation/agentes/networkmap&amp;tab=topology&amp;pure='.$pure.'">' . 
			print_image("images/recon.png", true, array ("title" => __('Topology view'))) .'</a>');

switch($activeTab){
	case 'topology':
			$title = __('Topology view');
			break;
	case 'groups':
			$title = __('Groups view');
			break;
	case 'policies':
			$title = __('Policies view');
			break;
}

print_page_header (__('Network map')." - ".$title, "images/bricks.png", false, "", false, $buttons);

switch ($activeTab) {
	case 'topology':
		require_once('operation/agentes/networkmap.topology.php');
		break;
	case 'groups':
		require_once('operation/agentes/networkmap.groups.php');
		break;
	case 'policies':
		require_once(''.ENTERPRISE_DIR.'/operation/policies/networkmap.policies.php');
		break;
	default:
		enterprise_selectTab($activeTab);
		break;
}

?>
