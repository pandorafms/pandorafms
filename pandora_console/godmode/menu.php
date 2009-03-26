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

require_once ('include/config.php');

check_login ();

if ((! give_acl ($config['id_user'], 0, "LM")) && (! give_acl ($config['id_user'], 0, "AW")) && (! give_acl ($config['id_user'], 0, "PM")) && (! give_acl ($config['id_user'], 0, "DM")) && (! give_acl ($config['id_user'], 0, "UM"))) {
	return;
}

enterprise_include ('godmode/menu.php');
require_once ('include/functions_menu.php');

$menu = array ();
$menu['class'] = 'godmode';

if (give_acl ($config['id_user'], 0, "AW")) {
	$menu["gagente"]["text"] = __('Manage agents');
	$menu["gagente"]["sec2"] = "godmode/agentes/modificar_agente";
	$menu["gagente"]["id"] = "god-agents";
		
	$sub = array ();
	$sub["godmode/agentes/massive_operations"]["text"] = __('Massive operations');
	
	$sub["godmode/agentes/manage_config_remote"]["text"] = __('Duplicate config');
	
	if (give_acl ($config["id_user"], 0, "PM")) {
		$sub["godmode/groups/group_list"]["text"] = __('Manage groups');
	}
	
	$sub["godmode/agentes/planned_downtime"]["text"] = __('Scheduled downtime');

	$menu["gagente"]["sub"] = $sub;
}
if (give_acl ($config['id_user'], 0, "PM")) {
	$menu["gmodules"]["text"] = __('Manage modules');
	$menu["gmodules"]["sec2"] = "godmode/modules/module_list";
	$menu["gmodules"]["id"] = "god-modules";
	
	$sub = array ();
	$sub["godmode/modules/manage_nc_groups"]["text"] = __('Component groups');
	
	$sub["godmode/modules/manage_network_components"]["text"] = __('Module components');
	
	$sub["godmode/modules/manage_network_templates"]["text"] = __('Module templates');
	
	enterprise_hook ('inventory_submenu');
	
	$menu["gmodules"]["sub"] = $sub;
}

if (give_acl ($config['id_user'], 0, "LM")) {
	$menu["galertas"]["text"] = __('Manage alerts');
	$menu["galertas"]["sec2"] = "godmode/alerts/alert_list";
	$menu["galertas"]["id"] = "god-alerts";
	
	$sub = array ();
	$sub["godmode/alerts/alert_templates"]["text"] = __('Templates');
	
	$sub["godmode/alerts/alert_actions"]["text"] = __('Actions');
	
	$sub["godmode/alerts/alert_commands"]["text"] = __('Commands');
	
	$sub["godmode/alerts/alert_compounds"]["text"] = __('Compounds');
	
	$menu["galertas"]["sub"] = $sub;
}

if (give_acl ($config['id_user'], 0, "UM")) {
	$menu["gusuarios"]["text"] = __('Manage users');
	$menu["gusuarios"]["sec2"] = "godmode/users/user_list";
	$menu["gusuarios"]["id"] = "god-users";
}

// SNMP console
if (give_acl($config['id_user'], 0, "AW")) {
	$menu["gsnmpconsole"]["text"] = __('Manage SNMP console');
	$menu["gsnmpconsole"]["sec2"] = "godmode/snmpconsole/snmp_alert";
	$menu["gsnmpconsole"]["id"] = "god-snmpc";
	
	$sub = array ();
	//$sub["godmode/snmpconsole/snmp_alert"]["text"] = __('Component groups');
	
	enterprise_hook ('snmpconsole_submenu');

	$menu["gsnmpconsole"]["sub"] = $sub;
}

// Reporting
if (give_acl ($config['id_user'], 0, "PM")) {
	$menu["greporting"]["text"] = __('Manage reports');
	$menu["greporting"]["sec2"] = "godmode/reporting/reporting_builder";
	$menu["greporting"]["id"] = "god-reporting";

	// Custom report builder
	$sub = array ();
	$sub["godmode/reporting/reporting_builder"]["text"] = __('Report builder');

	// Custom graph builder
	$sub["godmode/reporting/graph_builder"]["text"] = __('Graph builder');
	
	// Custom map builder
	$sub["godmode/reporting/map_builder"]["text"] = __('Map builder');
	
	$menu["greporting"]["sub"] = $sub;
	
	// Manage profiles
	$menu["gperfiles"]["text"] = __('Manage profiles');
	$menu["gperfiles"]["sec2"] = "godmode/profiles/profile_list";
	$menu["gperfiles"]["id"] = "god-profiles";
	
	// Servers
	$menu["gservers"]["text"] = __('Manage servers');
	$menu["gservers"]["sec2"] = "godmode/servers/modificar_server";
	$menu["gservers"]["id"] = "god-servers";
	
	$sub = array ();
	$sub["godmode/servers/manage_recontask"]["text"] = __('Manage recontask');
	
	$sub["godmode/servers/plugin"]["text"] = __('Manage plugins');
	
	$sub["godmode/servers/manage_export_form"]["text"] = __('Export targets');
	
	$menu["gservers"]["sub"] = $sub;
	
	enterprise_hook ('snmpconsole_menu');

	// Audit
	$menu["glog"]["text"] = __('System audit log');
	$menu["glog"]["sec2"] = "godmode/admin_access_logs";
	$menu["glog"]["id"] = "god-audit";
	
	// Setup
	$menu["gsetup"]["text"] = __('Setup');
	$menu["gsetup"]["sec2"] = "godmode/setup/setup";
	$menu["gsetup"]["id"] = "god-setup";
	
	$sub = array ();
	
	$sub["godmode/setup/filemgr"]["text"] = __('File Manager');
	
	$sub["godmode/setup/links"]["text"] = __('Links');
	
	$sub["godmode/setup/news"]["text"] = __('Site news');
	
	$menu["gsetup"]["sub"] = $sub;
}

if (give_acl ($config['id_user'], 0, "DM")) {
	$menu["gdbman"]["text"] = __('DB Maintenance');
	$menu["gdbman"]["sec2"] = "godmode/db/db_main";
	$menu["gdbman"]["id"] = "god-dbmaint";
	
	$sub = array ();
	$sub["godmode/db/db_info"]["text"] = __('DB Information');
	
	$sub["godmode/db/db_purge"]["text"] = __('Database purge');
	
	$sub["godmode/db/db_refine"]["text"] = __('Database debug');
	
	$sub["godmode/db/db_audit"]["text"] = __('Database audit');

	$sub["godmode/db/db_event"]["text"] = __('Database event');

	$sub["godmode/db/db_sanity"]["text"] = __('Database sanity');

	$menu["gdbman"]["sub"] = $sub;
}

if (is_array ($config['extensions'])) {
	$menu["gextensions"]["text"] = __('Extensions');
	$menu["gextensions"]["sec2"] = "godmode/extensions";
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

print_menu ($menu);
?>
