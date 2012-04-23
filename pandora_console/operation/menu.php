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

if (! isset ($config['id_user'])) {
	return;
}

require_once ('include/functions_menu.php');

enterprise_include ('operation/menu.php');

$menu = array ();
$menu['class'] = 'operation';

// Agent read, Server read
if (check_acl ($config['id_user'], 0, "AR")) {

	enterprise_hook ('metaconsole_menu');

	//View agents
	$menu["estado"]["text"] = __('Monitoring');
	$menu["estado"]["sec2"] = "operation/agentes/tactical";
	$menu["estado"]["refr"] = 0;
	$menu["estado"]["id"] = "oper-agents";
	
	$sub = array ();
	$sub["operation/agentes/tactical"]["text"] = __('Tactical view');
	$sub["operation/agentes/tactical"]["refr"] = 0;
		
	$sub["operation/agentes/group_view"]["text"] = __('Group view');
	$sub["operation/agentes/group_view"]["refr"] = 0;
	
	$sub["operation/agentes/estado_agente"]["text"] = __('Agent detail');
	$sub["operation/agentes/estado_agente"]["refr"] = 0;
	$sub["operation/agentes/estado_agente"]["subsecs"] = array(
		"godmode/agentes/agent_conf_gis",
		"godmode/agentes/agent_disk_conf_editor",
		"godmode/agentes/agent_template",
		"godmode/agentes/configurar_agente",
		"godmode/agentes/modificar_agente",
		"godmode/agentes/module_manager",
		"godmode/agentes/module_manager_editor",
		"godmode/agentes/module_manager_editor_common",
		"godmode/agentes/module_manager_editor_data",
		"godmode/agentes/module_manager_editor_network",
		"godmode/agentes/module_manager_editor_plugin",
		"godmode/agentes/module_manager_editor_prediction",
		"godmode/agentes/module_manager_editor_wmi",
		"operation/agentes/ver_agente");
	
	$sub["operation/agentes/alerts_status"]["text"] = __('Alert detail');
	$sub["operation/agentes/alerts_status"]["refr"] = 0;
	
	$sub["operation/agentes/status_monitor"]["text"] = __('Monitor detail');
	$sub["operation/agentes/status_monitor"]["refr"] = 0;
	
	enterprise_hook ('services_menu');
	
	enterprise_hook ('inventory_menu');
	
	$sub["operation/servers/recon_view"]["text"] = __('Recon view');
	$sub["operation/servers/recon_view"]["refr"] = 0;
	
	
	//SNMP Console
	$sub["operation/snmpconsole/snmp_view"]["text"] = __('SNMP console');
	$sub["operation/snmpconsole/snmp_view"]["refr"] = 0;
	$sub["operation/snmpconsole/snmp_view"]["subsecs"] = array(
		"enterprise/godmode/snmpconsole",
		"godmode/snmpconsole/snmp_trap_editor",
		"godmode/snmpconsole/snmp_alert",
		"godmode/snmpconsole/snmp_filters");
	
	$sub2 = array();
	
	$sub2["godmode/snmpconsole/snmp_alert"]["text"] = __("SNMP alerts");
	$sub2['godmode/snmpconsole/snmp_filters']['text'] = __('SNMP filters');	
	enterprise_hook ('snmpconsole_submenu');	
	
	$sub["operation/snmpconsole/snmp_view"]["sub2"] = $sub2;
	
	$sub['godmode/snmpconsole/snmp_trap_generator']['text'] = __('SNMP trap generator');
	$sub["godmode/snmpconsole/snmp_trap_generator"]["refr"] = 0;
	
	$sub['operation/tree']['text'] = __('Tree view');
	$sub["operation/tree"]["refr"] = 0;
		
	$menu["estado"]["sub"] = $sub;
	//End of view agents
	
	//Start network view
	
	$menu["network"]["text"] = __('Network View');
	$menu["network"]["sec2"] = "operation/agentes/networkmap";
	$menu["network"]["refr"] = 0;
	$menu["network"]["id"] = "oper-networkconsole";
	
	$sub = array();
	
	$sub["operation/agentes/networkmap"]["text"] = __('Network map');
	$sub["operation/agentes/networkmap"]["refr"] = 0;
	
	enterprise_hook ('networkmap_console');
	
	$menu["network"]["sub"] = $sub;
	//End networkview
	
	// Reporting
	$menu["reporting"]["text"] = __('Reporting');
	$menu["reporting"]["sec2"] = "godmode/reporting/reporting_builder";
	$menu["reporting"]["id"] = "oper-reporting";
	$menu["reporting"]["refr"] = 60;
	
	$sub = array ();

	$sub["godmode/reporting/reporting_builder"]["text"] = __('Custom reporting');
	//Set godomode path
	$sub["godmode/reporting/reporting_builder"]["subsecs"] = array("godmode/reporting/reporting_builder",
																"operation/reporting/reporting_viewer");
	//Visual console
	$sub["godmode/reporting/map_builder"]["text"] = __('Visual console');
	//Set godomode path
	$sub["godmode/reporting/map_builder"]["subsecs"] = array(
		"godmode/reporting/map_builder",
		"godmode/reporting/visual_console_builder");
	
	if (!empty($config['vc_refr'])){
		$sub["godmode/reporting/map_builder"]["refr"] = $config['vc_refr'];
	}
	else if (!empty($config['refr'])){
		$sub["godmode/reporting/map_builder"]["refr"] = $config['refr'];
	}	
	else{
		$sub["godmode/reporting/map_builder"]["refr"] = 60;
	}
	
	$sub2 = array ();
	
	$layouts = db_get_all_rows_in_table ('tlayout', 'name');
	if ($layouts === false) {
		$layouts = array ();
	}
	$id = (int) get_parameter ('id', -1);
	
	$firstLetterNameVisualToShow = array('_', ',', '[', '(');
	
	$sub2 = array();
	
	foreach ($layouts as $layout) {
		if (! check_acl ($config["id_user"], $layout["id_group"], "AR")) {
			continue;
		}
		$name = io_safe_output($layout['name']);
		if (empty($name)) {
			$firstLetter = '';
		}
		else {
			$firstLetter = $name[0];
		}
		if (!in_array($firstLetter, $firstLetterNameVisualToShow)) {
			continue;
		}
		$sub2["operation/visual_console/render_view&amp;id=".$layout["id"]]["text"] = mb_substr ($name, 0, 19);
		$sub2["operation/visual_console/render_view&amp;id=".$layout["id"]]["title"] = $name;
		if (!empty($config['vc_refr'])){
			$sub2["operation/visual_console/render_view&amp;id=".$layout["id"]]["refr"] = $config['vc_refr'];
		}			
		elseif (!empty($config['refr'])){
			$sub2["operation/visual_console/render_view&amp;id=".$layout["id"]]["refr"] = $config['refr'];
		}
		else{
			$sub2["operation/visual_console/render_view&amp;id=".$layout["id"]]["refr"] = 0;
		}	
	}
	
	$sub["godmode/reporting/map_builder"]["sub2"] = $sub2;
	
	$sub["godmode/reporting/graphs"]["text"] = __('Custom graphs');	
	//Set godomode path
	$sub["godmode/reporting/graphs"]["subsecs"] = array(
		"operation/reporting/graph_viewer",
		"godmode/reporting/graph_builder");	
	
	enterprise_hook ('dashboard_menu');
	enterprise_hook ('reporting_godmenu');	
	
	$menu["reporting"]["sub"] = $sub;
	//End reporting
	
	//INI GIS Maps
	if ($config['activate_gis']) {
		$menu["gismaps"]["text"] = __('GIS Maps');
		$menu["gismaps"]["sec2"] = "operation/gis_maps/index";
		$menu["gismaps"]["refr"] = 0;
		$menu["gismaps"]["id"] = "oper-gismaps";
		
		$sub = array ();
		
		$gisMaps = db_get_all_rows_in_table ('tgis_map', 'map_name');
		if ($gisMaps === false) {
			$gisMaps = array ();
		}
		$id = (int) get_parameter ('id', -1);
		
		$own_info = get_user_info ($config['id_user']);
		if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM"))
			$own_groups = array_keys(users_get_groups($config['id_user'], "IR"));
		else
			$own_groups = array_keys(users_get_groups($config['id_user'], "IR", false));		
		
		foreach ($gisMaps as $gisMap) {
			$is_in_group = in_array($gisMap['group_id'], $own_groups);
			if (!$is_in_group){
				continue;
			}			
			if (! check_acl ($config["id_user"], $gisMap["group_id"], "IR")) {
				continue;
			}
			$sub["operation/gis_maps/render_view&amp;map_id=".$gisMap["id_tgis_map"]]["text"] = mb_substr (io_safe_output($gisMap["map_name"]), 0, 15);
			$sub["operation/gis_maps/render_view&amp;map_id=".$gisMap["id_tgis_map"]]["title"] = io_safe_output($gisMap["map_name"]);
			$sub["operation/gis_maps/render_view&amp;map_id=".$gisMap["id_tgis_map"]]["refr"] = 0;
		}
		
		$menu["gismaps"]["sub"] = $sub;
	}
	//END GIS Maps
}

// Rest of options, all with AR privilege (or should events be with incidents?)
if (check_acl ($config['id_user'], 0, "AR")) {
	// Events
	$menu["eventos"]["text"] = __('View events'); 
	$menu["eventos"]["refr"] = 0;
	$menu["eventos"]["sec2"] = "operation/events/events";
	$menu["eventos"]["id"] = "oper-events";
	
	$sub = array ();
	$sub["operation/events/event_statistics"]["text"] = __('Statistics');
	
	//RSS
	$pss = get_user_info($config['id_user']);
	$hashup = md5($config['id_user'].$pss['password']);
	
	$sub["operation/events/events_rss.php?user=".$config['id_user']."&amp;hashup=".$hashup]["text"] = __('RSS');
	$sub["operation/events/events_rss.php?user=".$config['id_user']."&amp;hashup=".$hashup]["type"] = "direct";
	
	//CSV
	$sub["operation/events/export_csv.php"]["text"] = __('CSV File');
	$sub["operation/events/export_csv.php"]["type"] = "direct";
	
	//Marquee
	$sub["operation/events/events_marquee.php"]["text"] = __('Marquee');
	$sub["operation/events/events_marquee.php"]["type"] = "direct";
	
	//Sound Events
	$javascript = "javascript: window.open('operation/events/sound_events.php');";
	$javascript = 'javascript: alert(111);';
	$javascript = 'javascript: openSoundEventWindow();';
	$sub[$javascript]["text"] = __('Sound Events');
	$sub[$javascript]["type"] = "direct";
	
	?>
	<script type="text/javascript">
	function openSoundEventWindow() {
		<?php
			$url = ui_get_full_url(false);
		?>
		url = '<?php echo $url . 'operation/events/sound_events.php'; ?>';
		
		window.open(url, '<?php __('Sound Alerts'); ?>','width=475, height=275, toolbar=no, location=no, directories=no, status=no, menubar=no'); 
	}
	</script>
	<?php
	
	$menu["eventos"]["sub"] = $sub;
}

//Workspace
$menu["workspace"]["text"] = __('Workspace');
$menu["workspace"]["sec2"] = "operation/users/user_edit";
$menu["workspace"]["id"] = "oper-users";

// ANY user can view him/herself !
// Users
$sub = array();
$sub["operation/users/user_edit"]["text"] = __('Edit my user');
$sub["operation/users/user_edit"]["refr"] = 0;

// ANY user can chat with other user and dogs.
// Users
$sub = array();
$sub["operation/users/webchat"]["text"] = __('WebChat');
$sub["operation/users/webchat"]["refr"] = 0;

//Incidents
if (check_acl ($config['id_user'], 0, "IR") == 1) {
	$temp_sec2 = $sec2; 
	if($config['integria_enabled']) {
		$sec2 = "operation/integria_incidents/incident";
	}
	else {
		$sec2 = "operation/incidents/incident";
	}
	
	$sub[$sec2]["text"] = __('Incidents');
	$sub[$sec2]["refr"] = 0;
	$sub[$sec2]["subsecs"] = array(
		"operation/incidents/incident_detail",
		"operation/integria_incidents");
	
	$sub2 = array ();
	$sub2["operation/incidents/incident_statistics"]["text"] = __('Statistics');
	
	$sub[$sec2]["sub2"] = $sub2;
	$sec2 = $temp_sec2;
}

if (check_acl ($config['id_user'], 0, "AR")) {

	// Messages
	$sub["operation/messages/message_list"]["text"] = __('Messages');
	$sub["operation/messages/message_list"]["refr"] = 0;	
	
	$sub2 = array ();
	$sub2["operation/messages/message_edit&amp;new_msg=1"]["text"] = __('New message');
	
	$sub["operation/messages/message_list"]["sub2"] = $sub2;
}

$menu["workspace"]["sub"] = $sub;

//End Workspace

if (check_acl ($config['id_user'], 0, "AR")) {
	// Extensions menu additions
	if (is_array ($config['extensions'])) {
		$menu["extensions"]["text"] = __('Extensions');
		$menu["extensions"]["sec2"] = "operation/extensions";
		$menu["extensions"]["id"] = "oper-extensions";
		
		$sub = array ();
		
		$sub["operation/agentes/exportdata"]["text"] = __('Export data');
		$sub["operation/agentes/exportdata"]["refr"] = 0;
		
		foreach ($config["extensions"] as $extension) {
			//If no operation_menu is a godmode extension
			if ($extension["operation_menu"] == '') {
				continue;
			}
			
			$extension_menu = $extension["operation_menu"];
			
			//Check if was displayed inside other menu
			if ($extension["operation_menu"]["fatherId"] == '') {
				$sub[$extension_menu["sec2"]]["text"] = $extension_menu["name"];
				$sub[$extension_menu["sec2"]]["refr"] = 0;
			}
			else {
				if (array_key_exists('fatherId',$extension_menu)) {
					if (strlen($extension_menu['fatherId']) > 0) {
						$menu[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]["text"] = __($extension_menu['name']);
						$menu[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]["refr"] = 0;
						$menu[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]["icon"] = $extension_menu['icon'];
						$menu[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]["sec"] = 'extensions';
						$menu[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]["extension"] = true;
						$menu[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]["enterprise"] = $extension['enterprise'];
						$menu[$extension_menu['fatherId']]['hasExtensions'] = true;
					}
				}
			}
		}
		
		$menu["extensions"]["sub"] = $sub;
	}
}


menu_print_menu ($menu, true);
?>
