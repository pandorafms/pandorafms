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

$menu_godmode = array ();
$menu_godmode['class'] = 'godmode';

if (check_acl ($config['id_user'], 0, "AW") || check_acl ($config['id_user'], 0, "AD")) {
	$menu_godmode["gagente"]["text"] = __('Manage monitoring');
	$menu_godmode["gagente"]["sec2"] = "godmode/agentes/modificar_agente";
	$menu_godmode["gagente"]["id"] = "god-agents";
	
	if (check_acl ($config['id_user'], 0, "AW")) {
		$sub = array ();
		$sub['godmode/agentes/modificar_agente']['text'] = __('Manage agents');
		$sub["godmode/agentes/modificar_agente"]["subsecs"] = array(
			"godmode/agentes/configurar_agente");
		
		enterprise_hook("duplicate_confi_submenu");
		
		$sub["godmode/groups/group_list"]["text"] = __('Manage groups');
/*
		$sub["godmode/agentes/planned_downtime.list"]["text"] = __('Scheduled downtime');
*/
		
		if (check_acl ($config["id_user"], 0, "PM")) {
			$sub["godmode/agentes/fields_manager"]["text"] = __('Manage custom fields');
		}
		enterprise_hook('agents_submenu');
		
		$menu_godmode["gagente"]["sub"] = $sub;
	}
	
}

if (check_acl ($config['id_user'], 0, "AW")) {
	$menu_godmode["gmassive"]["text"] = __('Massive operations');
	$menu_godmode["gmassive"]["sec2"] = "godmode/massive/massive_operations";
	$menu_godmode["gmassive"]["id"] = "god-massive";
	
	$sub = array ();
	$sub["godmode/massive/massive_operations&amp;tab=massive_agents"]["text"] = __('Agents operations');
	$sub["godmode/massive/massive_operations&amp;tab=massive_modules"]["text"] = __('Modules operations');
	if (check_acl ($config['id_user'], 0, "PM")) {
		$sub["godmode/massive/massive_operations&amp;tab=massive_users"]["text"] = __('Users operations');
	}
	$sub["godmode/massive/massive_operations&amp;tab=massive_alerts"]["text"] = __('Alerts operations');
	$sub["godmode/massive/massive_operations&amp;tab=massive_tags"]["text"] = __('Tags operations');
	enterprise_hook('massivepolicies_submenu');
	enterprise_hook('massivesnmp_submenu');
	enterprise_hook('massivesatellite_submenu');
	
	$menu_godmode["gmassive"]["sub"] = $sub;
}

/*
if (check_acl ($config['id_user'], 0, "AW")) {
	enterprise_hook ('services_godmenu');
}
*/

if (check_acl ($config['id_user'], 0, "PM")) {
	$menu_godmode["gmodules"]["text"] = __('Manage modules');
	$menu_godmode["gmodules"]["sec2"] = "godmode/modules/manage_network_templates";
	$menu_godmode["gmodules"]["id"] = "god-modules";
	
	$sub = array ();
	$sub["godmode/modules/manage_nc_groups"]["text"] = __('Component groups');
	$sub["godmode/modules/manage_network_components"]["text"] = __('Network components');
	enterprise_hook ('components_submenu');
	$sub["godmode/modules/manage_network_templates"]["text"] = __('Module templates');
	enterprise_hook ('inventory_submenu');
	
	// Tag
	$sub["godmode/tag/tag"]["text"] = __('Manage tags');
	$sub["godmode/tag/tag"]["subsecs"] = "godmode/tag/edit_tag";
	
	// Category
	$sub["godmode/category/category"]["text"] = __('Manage categories');
	$sub["godmode/category/category"]["subsecs"] = "godmode/category/edit_category";
	
	$sub["godmode/modules/module_list"]["text"] = __('Module types');
	
	if (check_acl ($config["id_user"], 0, "PM")) {
		$sub["godmode/groups/modu_group_list"]["text"] = __('Module groups');
	}
	
	$menu_godmode["gmodules"]["sub"] = $sub;
}

if (check_acl ($config['id_user'], 0, "LM") || check_acl ($config['id_user'], 0, "AD")) {
	$menu_godmode["galertas"]["text"] = __('Manage alerts');
	$menu_godmode["galertas"]["sec2"] = "godmode/alerts/alert_list";
	$menu_godmode["galertas"]["id"] = "god-alerts";
	
	if (check_acl ($config['id_user'], 0, "LM")) {
		$sub = array ();
		$sub["godmode/alerts/alert_templates"]["text"] = __('Templates');
		$sub["godmode/alerts/alert_actions"]["text"] = __('Actions');
		
		if (check_acl ($config['id_user'], 0, "PM")) {
			$sub["godmode/alerts/alert_commands"]["text"] = __('Commands');
		}
		$sub["godmode/alerts/alert_special_days"]["text"] = __('Special days list');
		enterprise_hook('eventalerts_submenu');
		
		$menu_godmode["galertas"]["sub"] = $sub;
	}
}

if (check_acl ($config['id_user'], 0, "AW")) {
	enterprise_hook ('policies_menu');
}

if (check_acl ($config['id_user'], 0, "UM")) {
	$menu_godmode["gusuarios"]["text"] = __('Manage users');
	$menu_godmode["gusuarios"]["sec2"] = "godmode/users/user_list";
	$menu_godmode["gusuarios"]["id"] = "god-users";
	
	$sub = array ();
	$sub['godmode/users/profile_list']['text'] = __('Manage profiles');
	
	$menu_godmode["gusuarios"]["sub"] = $sub;
}

// GIS
if (check_acl ($config['id_user'], 0, "IW")) {
	
	if ($config['activate_gis']) {
		$menu_godmode["godgismaps"]["text"] = __('GIS Maps builder');
		$menu_godmode["godgismaps"]["sec2"] = "godmode/gis_maps/index";
		$menu_godmode["godgismaps"]["refr"] = (int)get_parameter('refr', 60);
		$menu_godmode["godgismaps"]["id"] = "god-gismaps";
	}
}

if (check_acl ($config['id_user'], 0, "EW")) {
	// Manage events
	$menu_godmode["geventos"]["text"] = __('Manage events');
	$menu_godmode["geventos"]["sec2"] = "godmode/events/events&amp;section=filter";
	$menu_godmode["geventos"]["id"] = "god-events";
	
	// Custom event fields
	$sub = array ();
	$sub["godmode/events/events&amp;section=filter"]["text"] = __('Event filters');
	
	if (check_acl ($config['id_user'], 0, "PM")) {
		$sub["godmode/events/events&amp;section=fields"]["text"] = __('Custom events');
		$sub["godmode/events/events&amp;section=responses"]["text"] = __('Event responses');
	}
	
	$menu_godmode["geventos"]["sub"] = $sub;
}

if (check_acl ($config['id_user'], 0, "AW")) {
	
	// Servers
	$menu_godmode["gservers"]["text"] = __('Manage servers');
	$menu_godmode["gservers"]["sec2"] = "godmode/servers/modificar_server";
	$menu_godmode["gservers"]["id"] = "god-servers";
	
	$sub = array ();
	$sub["godmode/servers/manage_recontask"]["text"] = __('Manage recontask');
	
	//This subtabs are only for Pandora Admin
	if (check_acl ($config['id_user'], 0, "PM")) {
		$sub["godmode/servers/plugin"]["text"] = __('Manage plugins');
		
		$sub["godmode/servers/recon_script"]["text"] = __('Manage recon script');
		
		enterprise_hook('export_target_submenu');
	}
	
	$menu_godmode["gservers"]["sub"] = $sub;
}

if (check_acl ($config['id_user'], 0, "LW")) {
	enterprise_hook ('snmpconsole_menu');
}

if (check_acl ($config['id_user'], 0, "PM")) {
	// Audit
	$menu_godmode["glog"]["text"] = __('System audit log');
	$menu_godmode["glog"]["sec2"] = "godmode/admin_access_logs";
	$menu_godmode["glog"]["id"] = "god-audit";
	
	// Setup
	$menu_godmode["gsetup"]["text"] = __('Setup');
	$menu_godmode["gsetup"]["sec2"] = "godmode/setup/setup&section=general";
	$menu_godmode["gsetup"]["id"] = "god-setup";
	
	$sub = array ();
	
	$sub["godmode/setup/file_manager"]["text"] = __('File manager');
	
	if ($config['activate_gis'])
		$sub["godmode/setup/gis"]["text"] = __('Map conections GIS');
	$sub["godmode/setup/links"]["text"] = __('Links');
	$sub["godmode/setup/news"]["text"] = __('Site news');
	$sub["godmode/setup/os"]["text"] = __('Edit OS');
	$sub["godmode/setup/license"]["text"] = __('License');
	
	$sub["godmode/update_manager/update_manager"]["text"] = __('Update manager');
	
	enterprise_hook ('enterprise_acl_submenu');
	enterprise_hook ('skins_submenu');
	$sub["extras/pandora_diag"]["text"] = __('Diagnostic info');
	
	$menu_godmode["gsetup"]["sub"] = $sub;
}

if (check_acl ($config['id_user'], 0, "AW")) {
	if ($config['activate_netflow']) {
		//Netflow
		$menu_godmode["netf"]["text"] = __('Netflow filters');
		$menu_godmode["netf"]["sec2"] = "godmode/netflow/nf_edit";
		$menu_godmode["netf"]["id"] = "god-netflow";
	}
}

if (check_acl ($config['id_user'], 0, "DM")) {
	$menu_godmode["gdbman"]["text"] = __('DB maintenance');
	$menu_godmode["gdbman"]["sec2"] = "godmode/db/db_main";
	$menu_godmode["gdbman"]["id"] = "god-dbmaint";
	
	$sub = array ();
	$sub["godmode/db/db_info"]["text"] = __('DB information');
	$sub["godmode/db/db_purge"]["text"] = __('Database purge');
	$sub["godmode/db/db_refine"]["text"] = __('Database debug');
	$sub["godmode/db/db_audit"]["text"] = __('Database audit');
	$sub["godmode/db/db_event"]["text"] = __('Database event');
	
	$menu_godmode["gdbman"]["sub"] = $sub;
}

if (check_acl ($config['id_user'], 0, "PM")) {
	if (is_array ($config['extensions'])) {
		$menu_godmode["gextensions"]["text"] = __('Extensions');
		$menu_godmode["gextensions"]["sec2"] = "godmode/extensions";
		$menu_godmode["gextensions"]["id"] = "god-extensions";
		
		$sub = array ();
		foreach ($config['extensions'] as $extension) {
			//If no godmode_menu is a operation extension
			if ($extension['godmode_menu'] == '') {
				continue;
			}
			
			$extmenu = $extension['godmode_menu'];
			
			if ($extmenu["name"] == 'DB interface' && !check_acl ($config['id_user'], 0, "DM")) { 
				continue;
			}
			
			//Check the ACL for this user
			if (! check_acl ($config['id_user'], 0, $extmenu['acl'])) {
				continue;
			}
			
			//Check if was displayed inside other menu
			if ($extension['godmode_menu']["fatherId"] == '') {
				$sub[$extmenu["sec2"]]["text"] = $extmenu["name"];
				$sub[$extmenu["sec2"]]["refr"] = 0;
			}
			else {
				if (array_key_exists('fatherId',$extmenu)) {
					if (strlen($extmenu['fatherId']) > 0) {
						$menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]["text"] = __($extmenu['name']);
						if ($extmenu["name"] != 'DB interface') {
							if (!empty($config['refr'])) {
								$menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]["refr"] = $config['refr'];
							}
							else {
								$menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]["refr"] = 0;
							}
						}
						$menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]["icon"] = $extmenu['icon'];
						$menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]["sec"] = 'gextensions';
						$menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]["extension"] = true;
						$menu_godmode[$extmenu['fatherId']]['sub'][$extmenu['sec2']]["enterprise"] = $extension['enterprise'];
						
						$menu_godmode[$extmenu['fatherId']]['hasExtensions'] = true;
					}
				}
			}
		}
		
		$menu_godmode["gextensions"]["sub"] = $sub;
	}
}


if (!$config['pure']) {
	menu_print_menu ($menu_godmode);
}
?>
