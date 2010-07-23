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
check_login ();

if (! give_acl ($config['id_user'], 0, "AW")) {
	audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation",
		"Trying to access massive operation section");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_agents.php');
require_once ('include/functions_alerts.php');
require_once ('include/functions_modules.php');

$tab = (string) get_parameter ('tab', 'copy_modules');

/* Copy modules */
$copymoduletab['text'] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/massive_operations&tab=copy_modules">'
		. print_image ("images/copy.png", true, array ("title" => __('Copy modules')))
		. "</a>";
if($tab == 'copy_modules')
	$copymoduletab['active'] = true;
else
	$copymoduletab['active'] = false;
	
/* Edit Modules */
$editmoduletab['text'] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/massive_operations&tab=edit_modules">'
		. print_image ("images/edit.png", true, array ("title" => __('Edit modules')))
		. "</a>";
if($tab == 'edit_modules')
	$editmoduletab['active'] = true;
else
	$editmoduletab['active'] = false;
	
/* Delete Modules */
$deletemoduletab['text'] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/massive_operations&tab=delete_modules">'
		. print_image ("images/delete_modules.png", true, array ("title" => __('Delete modules')))
		. "</a>";
if($tab == 'delete_modules')
	$deletemoduletab['active'] = true;
else
	$deletemoduletab['active'] = false;

/* Delete Agents */
$deleteagenttab['text'] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/massive_operations&tab=delete_agents">'
		. print_image ("images/delete_agents.png", true, array ("title" => __('Delete agents')))
		. "</a>";
if($tab == 'delete_agents')
	$deleteagenttab['active'] = true;
else
	$deleteagenttab['active'] = false;
	
/* Add alerts actions */
$addactionalerttab['text'] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/massive_operations&tab=add_action_alerts">'
		. print_image ("images/cog.png", true, array ("title" => __('Add Actions')))
		. "</a>";
if($tab == 'add_action_alerts')
	$addactionalerttab['active'] = true;
else
	$addactionalerttab['active'] = false;
	
/* Delete alerts actions */
$deleteactionalerttab['text'] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/massive_operations&tab=delete_action_alerts">'
		. print_image ("images/cog_del.png", true, array ("title" => __('Delete Actions')))
		. "</a>";
if($tab == 'delete_action_alerts')
	$deleteactionalerttab['active'] = true;
else
	$deleteactionalerttab['active'] = false;
	
/* Add Alerts */
$addalerttab['text'] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/massive_operations&tab=add_alerts">'
		. print_image ("images/god2.png", true, array ("title" => __('Add alerts')))
		. "</a>";
if($tab == 'add_alerts')
	$addalerttab['active'] = true;
else
	$addalerttab['active'] = false;
	
/* Delete Alerts */
$deletealerttab['text'] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/massive_operations&tab=delete_alerts">'
		. print_image ("images/delete_alerts.png", true, array ("title" => __('Delete alerts')))
		. "</a>";
if($tab == 'delete_alerts')
	$deletealerttab['active'] = true;
else
	$deletealerttab['active'] = false;
		

$onheader = array('copy_modules' => $copymoduletab, 'edit_modules' => $editmoduletab, 'delete_modules' => $deletemoduletab, 'delete_agents' => $deleteagenttab, 'add_action_alerts' => $addactionalerttab, 'delete_action_alerts' => $deleteactionalerttab, 'add_alerts' => $addalerttab, 'delete_alerts' => $deletealerttab);

print_page_header (__('Agent configuration'). ' &raquo; '. __('Massive operations'), "images/god1.png", false, "", true, $onheader);


switch ($tab) {
case 'delete_alerts':
	require_once ('godmode/agentes/massive_delete_alerts.php');
	break;
case 'add_alerts':
	require_once ('godmode/agentes/massive_add_alerts.php');
	break;
case 'delete_action_alerts':
	require_once ('godmode/agentes/massive_delete_action_alerts.php');
	break;
case 'add_action_alerts':
	require_once ('godmode/agentes/massive_add_action_alerts.php');
	break;
case 'delete_agents':
	require_once ('godmode/agentes/massive_delete_agents.php');
	break;
case 'delete_modules':
	require_once ('godmode/agentes/massive_delete_modules.php');
	break;
case 'edit_modules':
	require_once ('godmode/agentes/massive_edit_modules.php');
	break;
case 'copy_modules':
default:
	require_once ('godmode/agentes/massive_config.php');
}
?>
