<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

require_once ('include/config.php');

check_login ();

enterprise_include ('godmode/menu.php');
require_once ('include/functions_menu.php');

$menu = array ();
$menu['class'] = 'godmode';

if (check_acl ($config['id_user'], 0, "AW") and $config['metaconsole'] == 0) {
	$menu["gagente"]["text"] = __('Manage monitoring');
	$menu["gagente"]["sec2"] = "godmode/agentes/modificar_agente";
	$menu["gagente"]["id"] = "god-agents";
		
	$sub = array ();
	$sub['godmode/agentes/modificar_agente']['text'] = __('Manage agents');
	$sub["godmode/agentes/modificar_agente"]["subsecs"] = array(
		"godmode/agentes/configurar_agente");
		
	$sub["godmode/agentes/manage_config_remote"]["text"] = __('Duplicate config');
	
	if (check_acl ($config["id_user"], 0, "PM")) {
		$sub["godmode/groups/group_list"]["text"] = __('Manage groups');
		$sub["godmode/groups/modu_group_list"]["text"] = __('Module groups');
		$sub["godmode/agentes/planned_downtime"]["text"] = __('Scheduled downtime');
		$sub["godmode/agentes/fields_manager"]["text"] = __('Manage custom fields');
	}
	enterprise_hook('agents_submenu');
	
	$menu["gagente"]["sub"] = $sub;
}

if (check_acl ($config['id_user'], 0, "AW") and $config['metaconsole'] == 0) {
	$menu["gmassive"]["text"] = __('Massive operations');
	$menu["gmassive"]["sec2"] = "godmode/massive/massive_operations";
	$menu["gmassive"]["id"] = "god-massive";
		
	$sub = array ();
	$sub["godmode/massive/massive_operations&amp;tab=massive_agents"]["text"] = __('Agents operations');
	$sub["godmode/massive/massive_operations&amp;tab=massive_modules"]["text"] = __('Modules operations');
	if (check_acl ($config['id_user'], 0, "PM")) {
		$sub["godmode/massive/massive_operations&amp;tab=massive_users"]["text"] = __('Users operations');
	}
	$sub["godmode/massive/massive_operations&amp;tab=massive_alerts"]["text"] = __('Alerts operations');
	enterprise_hook('massivepolicies_submenu');
	
	$menu["gmassive"]["sub"] = $sub;
}

/*
if (check_acl ($config['id_user'], 0, "AW")) {
	enterprise_hook ('services_godmenu');
}
*/

if (check_acl ($config['id_user'], 0, "PM") and $config['metaconsole'] == 0) {
	$menu["gmodules"]["text"] = __('Manage modules');
	$menu["gmodules"]["sec2"] = "godmode/modules/manage_network_templates";
	$menu["gmodules"]["id"] = "god-modules";
	
	$sub = array ();
	$sub["godmode/modules/manage_nc_groups"]["text"] = __('Component groups');
	$sub["godmode/modules/manage_network_components"]["text"] = __('Network components');
	enterprise_hook ('components_submenu');
	$sub["godmode/modules/manage_network_templates"]["text"] = __('Module templates');
	enterprise_hook ('inventory_submenu');
	
	// Tag
	$sub["godmode/tag/tag"]["text"] = __('Manage tags');
	$sub["godmode/tag/tag"]["subsecs"] = "godmode/tag/edit_tag";
	
	$sub["godmode/modules/module_list"]["text"] = __('Module types');
	
	$menu["gmodules"]["sub"] = $sub;
}

if (check_acl ($config['id_user'], 0, "LM") and $config['metaconsole'] == 0) {
	$menu["galertas"]["text"] = __('Manage alerts');
	$menu["galertas"]["sec2"] = "godmode/alerts/alert_list";
	$menu["galertas"]["id"] = "god-alerts";
	
	$sub = array ();
	$sub["godmode/alerts/alert_templates"]["text"] = __('Templates');
	$sub["godmode/alerts/alert_actions"]["text"] = __('Actions');

	if (check_acl ($config['id_user'], 0, "PM")) {
		$sub["godmode/alerts/alert_commands"]["text"] = __('Commands');
	}
	$sub["godmode/alerts/alert_compounds"]["text"] = __('Correlation');
	enterprise_hook('eventalerts_submenu');

	$menu["galertas"]["sub"] = $sub;
}

if (check_acl ($config['id_user'], 0, "AW") and $config['metaconsole'] == 0) {
	enterprise_hook ('policies_menu');
}

if (check_acl ($config['id_user'], 0, "UM") and $config['metaconsole'] == 0) {
	$menu["gusuarios"]["text"] = __('Manage users');
	$menu["gusuarios"]["sec2"] = "godmode/users/user_list";
	$menu["gusuarios"]["id"] = "god-users";
	
	$sub = array ();
	$sub['godmode/users/profile_list']['text'] = __('Manage profiles');
	
	$menu["gusuarios"]["sub"] = $sub;
}

// GIS
if (check_acl ($config['id_user'], 0, "IW") and $config['metaconsole'] == 0) {
	
	if ($config['activate_gis']) {
		$menu["godgismaps"]["text"] = __('GIS Maps builder');
		$menu["godgismaps"]["sec2"] = "godmode/gis_maps/index";
		if (!empty($config['refr'])){
			$menu["godgismaps"]["refr"] = $config['refr'];
		}
		else{
			$menu["godgismaps"]["refr"] = 60;
		}
		$menu["godgismaps"]["id"] = "god-gismaps";
	}
}
if (check_acl ($config['id_user'], 0, "AW") and $config['metaconsole'] == 0) {

	// Servers
	$menu["gservers"]["text"] = __('Manage servers');
	$menu["gservers"]["sec2"] = "godmode/servers/modificar_server";
	$menu["gservers"]["id"] = "god-servers";

	$sub = array ();
	$sub["godmode/servers/manage_recontask"]["text"] = __('Manage recontask');
	
	//This subtabs are only for Pandora Admin
	if (check_acl ($config['id_user'], 0, "PM")) {
		$sub["godmode/servers/plugin"]["text"] = __('Manage plugins');
		
		$sub["godmode/servers/recon_script"]["text"] = __('Manage recon script');
		
		enterprise_hook('export_target_submenu');
	}
	
	$menu["gservers"]["sub"] = $sub;
}

if (check_acl ($config['id_user'], 0, "LW") and $config['metaconsole'] == 0) {	
	enterprise_hook ('snmpconsole_menu');
}

if (check_acl ($config['id_user'], 0, "PM")) {
	// Audit
	$menu["glog"]["text"] = __('System audit log');
	$menu["glog"]["sec2"] = "godmode/admin_access_logs";
	$menu["glog"]["id"] = "god-audit";
		
	// Setup
	$menu["gsetup"]["text"] = __('Setup');
	$menu["gsetup"]["sec2"] = "godmode/setup/setup";
	$menu["gsetup"]["id"] = "god-setup";

	$sub = array ();

	$sub["godmode/setup/setup_auth"]["text"] = __('Authentication');
	$sub["godmode/setup/performance"]["text"] = __('Performance');
	$sub["godmode/setup/setup_visuals"]["text"] = __('Visual styles');
	$sub["godmode/setup/file_manager"]["text"] = __('File manager');
	if ($config['activate_gis'])
		$sub["godmode/setup/gis"]["text"] = __('Map conections GIS');
	$sub["godmode/setup/links"]["text"] = __('Links');
	$sub["godmode/setup/news"]["text"] = __('Site news');
	$sub["godmode/setup/os"]["text"] = __('Edit OS');
	enterprise_hook ('historydb_submenu');
	enterprise_hook ('enterprise_acl_submenu');
	enterprise_hook ('skins_submenu');
	$sub["extras/pandora_diag"]["text"] = __('Diagnostic info');

	$menu["gsetup"]["sub"] = $sub;
}

if (check_acl ($config['id_user'], 0, "DM") and $config['metaconsole'] == 0) {
	$menu["gdbman"]["text"] = __('DB maintenance');
	$menu["gdbman"]["sec2"] = "godmode/db/db_main";
	$menu["gdbman"]["id"] = "god-dbmaint";
	
	$sub = array ();
	$sub["godmode/db/db_info"]["text"] = __('DB information');
	$sub["godmode/db/db_purge"]["text"] = __('Database purge');
	$sub["godmode/db/db_refine"]["text"] = __('Database debug');
	$sub["godmode/db/db_audit"]["text"] = __('Database audit');
	$sub["godmode/db/db_event"]["text"] = __('Database event');
	$sub["godmode/db/db_sanity"]["text"] = __('Database sanity');

	$menu["gdbman"]["sub"] = $sub;
}

if (check_acl ($config['id_user'], 0, "PM")) {
	if (is_array ($config['extensions'])) {
		$menu["gextensions"]["text"] = __('Extensions');
		$menu["gextensions"]["sec2"] = "godmode/extensions";
		$menu["gextensions"]["id"] = "god-extensions";
	
		$sub = array ();
		foreach ($config['extensions'] as $extension) {
			//If no godmode_menu is a operation extension
			if ($extension['godmode_menu'] == '') {
				continue;
			}
			
			$extmenu = $extension['godmode_menu'];
			
			//Check the ACL for this user
			if (! check_acl ($config['id_user'], 0, $extmenu['acl'])) {
				continue;
			}
			
			//Check if was displayed inside other menu
			if ($extension['godmode_menu']["fatherId"] == '') {	
				$sub[$extmenu["sec2"]]["text"] = $extmenu["name"];
				$sub[$extmenu["sec2"]]["refr"] = 0;
			} else {
						
				if (array_key_exists('fatherId',$extmenu)) {
					// Check that extension father ID exists previously on the menu
					if (strlen($extmenu['fatherId']) > 0 and array_key_exists($extension_menu['fatherId'], $menu)) {
						$menu[$extmenu['fatherId']]['sub'][$extmenu['sec2']]["text"] = __($extmenu['name']);
						if ($extmenu["name"] != 'DB interface'){
							if (!empty($config['refr'])){
								$menu[$extmenu['fatherId']]['sub'][$extmenu['sec2']]["refr"] = $config['refr'];
							}
							else{
								$menu[$extmenu['fatherId']]['sub'][$extmenu['sec2']]["refr"] = 60;
							}
						}	
						$menu[$extmenu['fatherId']]['sub'][$extmenu['sec2']]["icon"] = $extmenu['icon'];
						$menu[$extmenu['fatherId']]['sub'][$extmenu['sec2']]["sec"] = 'gextensions';
						$menu[$extmenu['fatherId']]['sub'][$extmenu['sec2']]["extension"] = true;
						$menu[$extmenu['fatherId']]['sub'][$extmenu['sec2']]["enterprise"] = $extension['enterprise'];
						
						$menu[$extmenu['fatherId']]['hasExtensions'] = true;
					}
				}
			}
		}
	
		$menu["gextensions"]["sub"] = $sub;
	}
}

menu_print_menu ($menu);
?>
