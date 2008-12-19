<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnológicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

enterprise_include ('godmode/menu.php');

check_login ();

if ((! give_acl ($config['id_user'], 0, "LM")) && (! give_acl ($config['id_user'], 0, "AW")) && (! give_acl ($config['id_user'], 0, "PM")) && (! give_acl ($config['id_user'], 0, "DM")) && (! give_acl ($config['id_user'], 0, "UM"))) {
	return;
}

if (give_acl ($config['id_user'], 0, "AW")) {
	
	$menu["gagente"]["text"] = __('Manage agents');
	$menu["gagente"]["sec2"] = "godmode/agentes/modificar_agente";
	$menu["gagente"]["refr"] = 0;
	$menu["gagente"]["id"] = "god-agents";
		
	$sub = array ();
	$sub["godmode/agentes/manage_config"]["text"] = __('Manage config');
	$sub["godmode/agentes/manage_config"]["refr"] = 0;
	
	$sub["godmode/agentes/manage_config_remote"]["text"] = __('Duplicate config');
	$sub["godmode/agentes/manage_config_remote"]["refr"] = 0;
	
	if (give_acl ($config["id_user"], 0, "PM")) {
		$sub["godmode/groups/group_list"]["text"] = __('Manage groups');
		$sub["godmode/groups/group_list"]["refr"] = 0;
	}
	
	$sub["godmode/agentes/planned_downtime"]["text"] = __('Scheduled downtime');
	$sub["godmode/agentes/planned_downtime"]["refr"] = 0;

	$menu["gagente"]["sub"] = $sub;
}
if (give_acl ($config['id_user'], 0, "PM")) {
	$menu["gmodules"]["text"] = __('Manage modules');
	$menu["gmodules"]["sec2"] = "godmode/agentes/modificar_agente";
	$menu["gmodules"]["refr"] = 0;
	$menu["gmodules"]["id"] = "god-modules";
	
	$sub = array ();
	$sub["godmode/modules/manage_nc_groups"]["text"] = __('Component groups');
	$sub["godmode/modules/manage_nc_groups"]["refr"] = 0;
	
	$sub["godmode/modules/manage_network_components"]["text"] = __('Module components');
	$sub["godmode/modules/manage_network_components"]["refr"] = 0;
	
	$sub["godmode/modules/manage_network_templates"]["text"] = __('Module templates');
	$sub["godmode/modules/manage_network_templates"]["refr"] = 0;

	enterprise_hook ('inventory_submenu');

	$menu["gmodules"]["sub"] = $sub;
}

if (give_acl ($config['id_user'], 0, "LM")) {
	$menu["galertas"]["text"] = __('Manage alerts');
	$menu["galertas"]["sec2"] = "godmode/alerts/modify_alert";
	$menu["galertas"]["refr"] = 0;
	$menu["galertas"]["id"] = "god-alerts";
}

if (give_acl ($config['id_user'], 0, "UM")) {
	$menu["gusuarios"]["text"] = __('Manage users');
	$menu["gusuarios"]["sec2"] = "godmode/users/user_list";
	$menu["gusuarios"]["refr"] = 0;
	$menu["gusuarios"]["id"] = "god-users";
}

// SNMP console
if (give_acl($config['id_user'], 0, "AW")) {
	$menu["gsnmpconsole"]["text"] = __('Manage SNMP console');
	$menu["gsnmpconsole"]["sec2"] = "godmode/snmpconsole/snmp_alert";
	$menu["gsnmpconsole"]["refr"] = 0;
	$menu["gsnmpconsole"]["id"] = "god-snmpc";
	
	//SNMP Console alert
	$sub = array ();
	$sub["godmode/snmpconsole/snmp_alert"]["text"] = __('Component groups');
	$sub["godmode/snmpconsole/snmp_alert"]["refr"] = 0;
	
	enterprise_hook ('snmpconsole_submenu');

	$menu["gsnmpconsole"]["sub"] = $sub;
}

// Reporting
if (give_acl ($config['id_user'], 0, "PM")) {
	$menu["greporting"]["text"] = __('Manage reports');
	$menu["greporting"]["sec2"] = "godmode/reporting/reporting_builder";
	$menu["greporting"]["refr"] = 0;
	$menu["greporting"]["id"] = "god-reporting";

	// Custom report builder
	$sub = array ();
	$sub["godmode/reporting/reporting_builder"]["text"] = __('Report builder');
	$sub["godmode/reporting/reporting_builder"]["refr"] = 0;

	// Custom graph builder
	$sub["godmode/reporting/graph_builder"]["text"] = __('Graph builder');
	$sub["godmode/reporting/graph_builder"]["refr"] = 0;
	
	// Custom map builder
	$sub["godmode/reporting/map_builder"]["text"] = __('Map builder');
	$sub["godmode/reporting/map_builder"]["refr"] = 0;
	
	$menu["greporting"]["sub"] = $sub;
	
	// Manage profiles
	$menu["gperfiles"]["text"] = __('Manage profiles');
	$menu["gperfiles"]["sec2"] = "godmode/profiles/profile_list";
	$menu["gperfiles"]["refr"] = 0;
	$menu["gperfiles"]["id"] = "god-profiles";
	
	// Servers
	$menu["gservers"]["text"] = __('Manage servers');
	$menu["gservers"]["sec2"] = "godmode/servers/modificar_server";
	$menu["gservers"]["refr"] = 0;
	$menu["gservers"]["id"] = "god-servers";
	
	$sub = array ();
	$sub["godmode/servers/manage_recontask"]["text"] = __('Manage recontask');
	$sub["godmode/servers/manage_recontask"]["refr"] = 0;
	
	$sub["godmode/servers/plugin"]["text"] = __('Manage plugins');
	$sub["godmode/servers/plugin"]["refr"] = 0;
	
	$sub["godmode/servers/manage_export_form"]["text"] = __('Export targets');
	$sub["godmode/servers/manage_export_form"]["refr"] = 0;
	
	$menu["gservers"]["sub"] = $sub;
	
	enterprise_hook ('snmpconsole_menu');

	// Audit
	$menu["glog"]["text"] = __('System audit log');
	$menu["glog"]["sec2"] = "godmode/admin_access_logs";
	$menu["glog"]["refr"] = 0;
	$menu["glog"]["id"] = "god-audit";
	
	// Setup
	$menu["gsetup"]["text"] = __('Pandora setup');
	$menu["gsetup"]["sec2"] = "godmode/setup/setup";
	$menu["gsetup"]["refr"] = 0;
	$menu["gsetup"]["id"] = "god-setup";
	
	$sub = array ();
	$sub["godmode/setup/links"]["text"] = __('Links');
	$sub["godmode/setup/links"]["refr"] = 0;
	
	$sub["godmode/setup/news"]["text"] = __('Site news');
	$sub["godmode/setup/news"]["refr"] = 0;
	
	$menu["gsetup"]["sub"] = $sub;
}

if (give_acl ($config['id_user'], 0, "DM")) {
	$menu["gdbman"]["text"] = __('DB Maintenance');
	$menu["gdbman"]["sec2"] = "godmode/db/db_main";
	$menu["gdbman"]["refr"] = 0;
	$menu["gdbman"]["id"] = "god-dbmaint";
	
	$sub = array ();
	$sub["godmode/db/db_info"]["text"] = __('DB Information');
	$sub["godmode/db/db_info"]["refr"] = 0;
	
	$sub["godmode/db/db_purge"]["text"] = __('Database purge');
	$sub["godmode/db/db_purge"]["refr"] = 0;
	
	$sub["godmode/db/db_refine"]["text"] = __('Database debug');
	$sub["godmode/db/db_refine"]["refr"] = 0;
	
	$sub["godmode/db/db_audit"]["text"] = __('Database audit');
	$sub["godmode/db/db_audit"]["refr"] = 0;

	$sub["godmode/db/db_event"]["text"] = __('Database event');
	$sub["godmode/db/db_event"]["refr"] = 0;

	$sub["godmode/db/db_sanity"]["text"] = __('Database sanity');
	$sub["godmode/db/db_sanity"]["refr"] = 0;		

	$menu["gdbman"]["sub"] = $sub;
}

if (is_array ($config['extensions'])) {
	$menu["gextensions"]["text"] = __('Extensions');
	$menu["gextensions"]["sec2"] = "godmode/extensions";
	$menu["gextensions"]["refr"] = 0;
	$menu["gextensions"]["id"] = "god-extensions";
	
	$sub = array ();
	foreach ($config['extensions'] as $extension) {
		$extmenu = $extension['godmode_menu'];
		if ($extension['godmode_menu'] == '' || ! give_acl ($config['id_user'], 0, $extmenu['acl'])) {
			continue;
		}

		$sub[$extmenu["sec2"]]["text"] = $extmenu["name"];
		$sub[$extmenu["sec2"]]["refr"] = 0;
	}
	
	$menu["gextensions"]["sub"] = $sub;
}
?>
