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

$menu_operation = array ();
$menu_operation['class'] = 'operation';

// Agent read, Server read
if (check_acl ($config['id_user'], 0, "AR")) {
	//View agents
	$menu_operation["estado"]["text"] = __('Monitoring');
	$menu_operation["estado"]["sec2"] = "operation/agentes/tactical";
	$menu_operation["estado"]["refr"] = 0;
	$menu_operation["estado"]["id"] = "oper-agents";
	
	$sub = array ();
	$sub["view"]["text"] = __('Views');
	$sub["view"]["id"] = 'Views';
	$sub["view"]["type"] = "direct";
	$sub["view"]["subtype"] = "nolink";
	$sub["view"]["refr"] = 0;
	
	$sub2 = array ();
	
	$sub2["operation/agentes/tactical"]["text"] = __('Tactical view');
	$sub2["operation/agentes/tactical"]["refr"] = 0;
	
	$sub2["operation/agentes/group_view"]["text"] = __('Group view');
	$sub2["operation/agentes/group_view"]["refr"] = 0;
	
	$sub2['operation/tree']['text'] = __('Tree view');
	$sub2["operation/tree"]["refr"] = 0;
	
	$sub2["operation/agentes/estado_agente"]["text"] = __('Agent detail');
	$sub2["operation/agentes/estado_agente"]["refr"] = 0;
	$sub2["operation/agentes/estado_agente"]["subsecs"] = array(
		"operation/agentes/ver_agente");
	
	$sub2["operation/agentes/status_monitor"]["text"] = __('Monitor detail');
	$sub2["operation/agentes/status_monitor"]["refr"] = 0;
	
	$sub2["operation/agentes/alerts_status"]["text"] = __('Alert detail');
	$sub2["operation/agentes/alerts_status"]["refr"] = 0;
	
	$sub["view"]["sub2"] = $sub2;
	
	enterprise_hook ('inventory_menu');
		
	if ($config['activate_netflow']) {
		$sub["operation/netflow/nf_live_view"]["text"] = __('Netflow Live View');
		$sub["operation/netflow/nf_live_view"]["id"] = 'Netflow Live View';
		$sub["operation/netflow/nf_live_view"]["refr"] = 0;
	}
	
	if ($config['log_collector'] == 1) {
		enterprise_hook ('log_collector_menu');
	}
	
	//SNMP Console
	$sub["snmpconsole"]["text"] = __('SNMP');
	$sub["snmpconsole"]["id"] = 'SNMP';
	$sub["snmpconsole"]["refr"] = 0;
	$sub["snmpconsole"]["type"] = "direct";
	$sub["snmpconsole"]["subtype"] = "nolink";
	$sub2 = array();
	$sub2["operation/snmpconsole/snmp_view"]["text"] = __("SNMP console");
	$sub2["operation/snmpconsole/snmp_browser"]["text"] = __("SNMP browser");
	
	if (check_acl ($config['id_user'], 0, "PM"))
		$sub2["operation/snmpconsole/snmp_mib_uploader"]["text"] = __("MIB uploader");
	
	if (check_acl ($config['id_user'], 0, "LW")) {
		$sub2["godmode/snmpconsole/snmp_filters"]["text"] = __("SNMP filters");
		$sub2["godmode/snmpconsole/snmp_trap_generator"]["text"] = __("SNMP trap generator");
	}
	enterprise_hook ('snmpconsole_submenu');
	$sub["snmpconsole"]["sub2"] = $sub2;
	
	$menu_operation["estado"]["sub"] = $sub;
	
	//End of view agents
	
}

if (check_acl ($config['id_user'], 0, "AR") || check_acl ($config['id_user'], 0, "MR")) {
	//Start network view
	$menu_operation["network"]["text"] = __('Topology maps');
	$menu_operation["network"]["sec2"] = "operation/agentes/networkmap_list";
	$menu_operation["network"]["refr"] = 0;
	$menu_operation["network"]["id"] = "oper-networkconsole";
	$sub = array();
}

if (check_acl ($config['id_user'], 0, "MR")) {
	$sub["operation/agentes/networkmap_list"]["text"] = __('Network map');
	$sub["operation/agentes/networkmap_list"]["id"] = 'Network map';
	$sub["operation/agentes/networkmap_list"]["refr"] = 0;
	$sub["operation/agentes/networkmap_list"]["pages"] = array(
		"operation/agentes/networkmap"
		);
	
	enterprise_hook ('transmap_console');
	
	$sub["operation/maps/networkmap_list"]["text"] = __('(Temp) Network map');
	$sub["operation/maps/networkmap_list"]["id"] = '(Temp) Network map';
	$sub["operation/maps/networkmap_list"]["refr"] = 0;
	$sub["operation/maps/networkmap_list"]["pages"] = array(
		"operation/maps/networkmap"
		);
}

enterprise_hook ('networkmap_console');

enterprise_hook ('services_menu');

if (check_acl ($config['id_user'], 0, "VR")) {		
	//Visual console
	$sub["godmode/reporting/map_builder"]["text"] = __('Visual console');
	$sub["godmode/reporting/map_builder"]["id"] = 'Visual console';
	//Set godomode path
	$sub["godmode/reporting/map_builder"]["subsecs"] = array(
		"godmode/reporting/map_builder",
		"godmode/reporting/visual_console_builder");
	
	$layouts = db_get_all_rows_in_table ('tlayout', 'name');
	$sub2 = array ();

	if ($layouts === false) {
		$layouts = array ();
	}
	else {
		
		$id = (int) get_parameter ('id', -1);
		
		$firstLetterNameVisualToShow = array('_', ',', '[', '(');
		
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
			$sub2["operation/visual_console/render_view&amp;id=".$layout["id"]]["id"] = mb_substr ($name, 0, 19);
			$sub2["operation/visual_console/render_view&amp;id=".$layout["id"]]["title"] = $name;
			if (!empty($config['vc_refr'])) {
				$sub2["operation/visual_console/render_view&amp;id=".$layout["id"]]["refr"] = $config['vc_refr'];
			}
			elseif (((int)get_parameter('refr', 0)) > 0) {
				$sub2["operation/visual_console/render_view&amp;id=".$layout["id"]]["refr"] = (int)get_parameter('refr', 0);
			}
			else {
				$sub2["operation/visual_console/render_view&amp;id=".$layout["id"]]["refr"] = 0;
			}
		}
		if (!empty($sub2))
			$sub["godmode/reporting/map_builder"]["sub2"] = $sub2;
	}
}
// Agent read, Server read
if (check_acl ($config['id_user'], 0, "AR")) {
	//INI GIS Maps
	if ($config['activate_gis']) {
		$sub["gismaps"]["text"] = __('GIS Maps');
		$sub["gismaps"]["id"] = 'GIS Maps';
		$sub["gismaps"]["type"] = "direct";
		$sub["gismaps"]["subtype"] = "nolink";
		$sub2 = array ();
		$sub2["operation/gis_maps/gis_map"]["text"] = __("List of Gis maps");
		$sub2["operation/gis_maps/gis_map"]["id"] = "List of Gis maps";
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
			if (!$is_in_group) {
				continue;
			}
			if (! check_acl ($config["id_user"], $gisMap["group_id"], "IR")) {
				continue;
			}
			$sub2["operation/gis_maps/render_view&amp;map_id=".$gisMap["id_tgis_map"]]["text"] = mb_substr (io_safe_output($gisMap["map_name"]), 0, 15);
			$sub2["operation/gis_maps/render_view&amp;map_id=".$gisMap["id_tgis_map"]]["id"] = mb_substr (io_safe_output($gisMap["map_name"]), 0, 15);
			$sub2["operation/gis_maps/render_view&amp;map_id=".$gisMap["id_tgis_map"]]["title"] = io_safe_output($gisMap["map_name"]);
			$sub2["operation/gis_maps/render_view&amp;map_id=".$gisMap["id_tgis_map"]]["refr"] = 0;
		}
		
		$sub["gismaps"]["sub2"] = $sub2;
	}
	//END GIS Maps	
}

if (check_acl ($config['id_user'], 0, "AR") || check_acl ($config['id_user'], 0, "MR"))
	$menu_operation["network"]["sub"] = $sub;
//End networkview

// Reports read
if (check_acl ($config['id_user'], 0, "RR")) {
	// Reporting
	$menu_operation["reporting"]["text"] = __('Reporting');
	$menu_operation["reporting"]["sec2"] = "godmode/reporting/reporting_builder";
	$menu_operation["reporting"]["id"] = "oper-reporting";
	$menu_operation["reporting"]["refr"] = 300;
	
	$sub = array ();
	
	$sub["godmode/reporting/reporting_builder"]["text"] = __('Custom reporting');
	$sub["godmode/reporting/reporting_builder"]["id"] = 'Custom reporting';
	//Set godomode path
	$sub["godmode/reporting/reporting_builder"]["subsecs"] = array("godmode/reporting/reporting_builder",
		"operation/reporting/reporting_viewer");
	
	
	$sub["godmode/reporting/graphs"]["text"] = __('Custom graphs');
	$sub["godmode/reporting/graphs"]["id"] = 'Custom graphs';
	//Set godomode path
	$sub["godmode/reporting/graphs"]["subsecs"] = array(
		"operation/reporting/graph_viewer",
		"godmode/reporting/graph_builder");
	
	enterprise_hook ('dashboard_menu');
	enterprise_hook ('reporting_godmenu');
	
	$menu_operation["reporting"]["sub"] = $sub;
	//End reporting
}

// Events reading
if (check_acl ($config['id_user'], 0, "ER") 
	|| check_acl ($config['id_user'], 0, "EW") 
		|| check_acl ($config['id_user'], 0, "EM")) {
	// Events
	$menu_operation["eventos"]["text"] = __('Events');
	$menu_operation["eventos"]["refr"] = 0;
	$menu_operation["eventos"]["sec2"] = "operation/events/events";
	$menu_operation["eventos"]["id"] = "oper-events";
	
	$sub = array ();
	$sub["operation/events/events"]["text"] = __('View events');
	$sub["operation/events/events"]["id"] = 'View events';
	$sub["operation/events/events"]["pages"] =
		array("godmode/events/events");
	$sub["operation/events/event_statistics"]["text"] = __('Statistics');
	$sub["operation/events/event_statistics"]["id"] = 'Statistics';
	
	//RSS
	require_once ('include/functions_api.php');
	if (isInACL($_SERVER['REMOTE_ADDR'])) {
		$pss = get_user_info($config['id_user']);
		$hashup = md5($config['id_user'].$pss['password']);
		
		$sub["operation/events/events_rss.php?user=".$config['id_user']."&amp;hashup=".$hashup."&search=&event_type=&severity=-1&status=3&id_group=0&refr=0&id_agent=0&pagination=20&group_rep=1&event_view_hr=8&id_user_ack=0&tag_with=&tag_without=&filter_only_alert-1&offset=0&toogle_filter=no&filter_id=0&id_name=&id_group=0&history=0&section=list&open_filter=0&pure="]["text"] = __('RSS');
		$sub["operation/events/events_rss.php?user=".$config['id_user']."&amp;hashup=".$hashup."&search=&event_type=&severity=-1&status=3&id_group=0&refr=0&id_agent=0&pagination=20&group_rep=1&event_view_hr=8&id_user_ack=0&tag_with=&tag_without=&filter_only_alert-1&offset=0&toogle_filter=no&filter_id=0&id_name=&id_group=0&history=0&section=list&open_filter=0&pure="]["id"] = 'RSS';
		$sub["operation/events/events_rss.php?user=".$config['id_user']."&amp;hashup=".$hashup."&search=&event_type=&severity=-1&status=3&id_group=0&refr=0&id_agent=0&pagination=20&group_rep=1&event_view_hr=8&id_user_ack=0&tag_with=&tag_without=&filter_only_alert-1&offset=0&toogle_filter=no&filter_id=0&id_name=&id_group=0&history=0&section=list&open_filter=0&pure="]["type"] = "direct";
	}

	//CSV
	$sub["operation/events/export_csv.php?search=&event_type=&severity=-1&status=3&id_group=0&refr=0&id_agent=0&pagination=20&group_rep=1&event_view_hr=8&id_user_ack=0&tag_with=&tag_without=&filter_only_alert-1&offset=0&toogle_filter=no&filter_id=0&id_name=&id_group=0&history=0&section=list&open_filter=0&pure="]["text"] = __('CSV File');
	$sub["operation/events/export_csv.php?search=&event_type=&severity=-1&status=3&id_group=0&refr=0&id_agent=0&pagination=20&group_rep=1&event_view_hr=8&id_user_ack=0&tag_with=&tag_without=&filter_only_alert-1&offset=0&toogle_filter=no&filter_id=0&id_name=&id_group=0&history=0&section=list&open_filter=0&pure="]["id"] = 'CSV File';
	$sub["operation/events/export_csv.php?search=&event_type=&severity=-1&status=3&id_group=0&refr=0&id_agent=0&pagination=20&group_rep=1&event_view_hr=8&id_user_ack=0&tag_with=&tag_without=&filter_only_alert-1&offset=0&toogle_filter=no&filter_id=0&id_name=&id_group=0&history=0&section=list&open_filter=0&pure="]["type"] = "direct";
	
	//Marquee
	$sub["operation/events/events_marquee.php"]["text"] = __('Marquee');
	$sub["operation/events/events_marquee.php"]["id"] = 'Marquee';
	$sub["operation/events/events_marquee.php"]["type"] = "direct";
	
	//Sound Events
	$javascript = "javascript: window.open('operation/events/sound_events.php');";
	$javascript = 'javascript: alert(111);';
	$javascript = 'javascript: openSoundEventWindow();';
	$sub[$javascript]["text"] = __('Sound Events');
	$sub[$javascript]["id"] = 'Sound Events';
	$sub[$javascript]["type"] = "direct";
	
	?>
	<script type="text/javascript">
	function openSoundEventWindow() {
		url = '<?php
			echo ui_get_full_url('operation/events/sound_events.php');
			?>';
		
		window.open(url,
			'<?php __('Sound Alerts'); ?>',
			'width=475, height=275, resizable=yes, toolbar=no, location=no, directories=no, status=no, menubar=no');
	}
	</script>
	<?php
	
	$menu_operation["eventos"]["sub"] = $sub;
}

//Workspace
$menu_operation["workspace"]["text"] = __('Workspace');
$menu_operation["workspace"]["sec2"] = "operation/users/user_edit";
$menu_operation["workspace"]["id"] = "oper-users";

// ANY user can view him/herself !
// Users
$sub = array();
$sub["operation/users/user_edit"]["text"] = __('Edit my user');
$sub["operation/users/user_edit"]["id"] = 'Edit my user';
$sub["operation/users/user_edit"]["refr"] = 0;

// ANY user can chat with other user and dogs.
// Users
$sub["operation/users/webchat"]["text"] = __('WebChat');
$sub["operation/users/webchat"]["id"] = 'WebChat';
$sub["operation/users/webchat"]["refr"] = 0;


//Incidents
if (check_acl ($config['id_user'], 0, "IR")) {
	$temp_sec2 = $sec2;
	if($config['integria_enabled']) {
		$sec2 = "incident";
		$sec2sub = "operation/integria_incidents/incident_statistics";
	}
	else {
		$sec2 = "incident";
		$sec2sub = "operation/incidents/incident_statistics";
	}
	
	$sub[$sec2]["text"] = __('Incidents');
	$sub[$sec2]["id"] = 'Incidents';
	$sub[$sec2]["type"] = "direct";
	$sub[$sec2]["subtype"] = "nolink";
	$sub[$sec2]["refr"] = 0;
	$sub[$sec2]["subsecs"] = array(
		"operation/incidents/incident_detail",
		"operation/integria_incidents");
	
	$sub2 = array ();
	$sub2['operation/incidents/incident']["text"] = __("List of Incidents");
	$sub2[$sec2sub]["text"] = __('Statistics');
	
	$sub[$sec2]["sub2"] = $sub2;
	$sec2 = $temp_sec2;
}


// Messages
$sub["message_list"]["text"] = __('Messages');
$sub["message_list"]["id"] = 'Messages';
$sub["message_list"]["refr"] = 0;
$sub["message_list"]["type"] = "direct";
$sub["message_list"]["subtype"] = "nolink";
$sub2 = array ();
$sub2["operation/messages/message_list"]["text"] = __('Messages List');
$sub2["operation/messages/message_edit&amp;new_msg=1"]["text"] = __('New message');

$sub["message_list"]["sub2"] = $sub2;

$menu_operation["workspace"]["sub"] = $sub;

//End Workspace


// Rest of options, all with AR privilege (or should events be with incidents?)
//~ if (check_acl ($config['id_user'], 0, "AR")) {
	
// Extensions menu additions
if (is_array ($config['extensions'])) {
	
	
	$sub = array ();
	$sub2 = array ();
	
	if (check_acl ($config['id_user'], 0, "RR")) {
		$sub["operation/agentes/exportdata"]["text"] = __('Export data');
		$sub["operation/agentes/exportdata"]["id"] = 'Export data';
		$sub["operation/agentes/exportdata"]["subsecs"] =  array("operation/agentes/exportdata");
	}
	
	if (check_acl ($config['id_user'], 0, "AR") || check_acl ($config['id_user'], 0, "AD")) {
		$sub["godmode/agentes/planned_downtime.list"]["text"] = __('Scheduled downtime');
		$sub["godmode/agentes/planned_downtime.list"]["id"] = 'Scheduled downtime';
	}
	
	if (check_acl ($config['id_user'], 0, "PM")) {
		$sub["operation/servers/recon_view"]["text"] = __('Recon view');
		$sub["operation/servers/recon_view"]["id"] = 'Recon view';
		$sub["operation/servers/recon_view"]["refr"] = 0;
	}
	
	foreach ($config["extensions"] as $extension) {
		//If no operation_menu is a godmode extension
		if ($extension["operation_menu"] == '') {
			continue;
		}
		
		//Check the ACL for this user
		if (! check_acl ($config['id_user'], 0, $extension['operation_menu']['acl'])) {
			continue;
		}
		
		$extension_menu = $extension["operation_menu"];
		if ($extension["operation_menu"]["name"] == 'Matrix' && 
			( !check_acl ($config['id_user'], 0, "ER") || 
			!check_acl ($config['id_user'], 0, "EW") ||
			 !check_acl ($config['id_user'], 0, "EM") )) {
			continue;
		}
		//Check if was displayed inside other menu
		if ($extension["operation_menu"]["fatherId"] == '') {
			if ($extension_menu['name'] == 'Update manager') {
				continue;
			}
			$sub[$extension_menu["sec2"]]["text"] = $extension_menu["name"];
			$sub[$extension_menu["sec2"]]["id"] = $extension_menu["name"];
			$sub[$extension_menu["sec2"]]["refr"] = 0;
		}
		else {
			if (array_key_exists('fatherId',$extension_menu)) {
				// Check that extension father ID exists previously on the menu
				if ((strlen($extension_menu['fatherId']) > 0)) {
					if (array_key_exists('subfatherId',$extension_menu)) {
						if ((strlen($extension_menu['subfatherId']) > 0)) {
							$menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['subfatherId']]['sub2'][$extension_menu['sec2']]["text"] = __($extension_menu['name']);
							$menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['subfatherId']]['sub2'][$extension_menu['sec2']]["id"] = $extension_menu['name'];
							$menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['subfatherId']]['sub2'][$extension_menu['sec2']]["refr"] = 0;
							$menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['subfatherId']]['sub2'][$extension_menu['sec2']]["icon"] = $extension_menu['icon'];
							$menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['subfatherId']]['sub2'][$extension_menu['sec2']]["sec"] = 'extensions';
							$menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['subfatherId']]['sub2'][$extension_menu['sec2']]["extension"] = true;
							$menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['subfatherId']]['sub2'][$extension_menu['sec2']]["enterprise"] = $extension['enterprise'];
							$menu_operation[$extension_menu['fatherId']]['hasExtensions'] = true;
						}
						else {
							$menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]["text"] = __($extension_menu['name']);
							$menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]["id"] = $extension_menu['name'];
							$menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]["refr"] = 0;
							$menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]["icon"] = $extension_menu['icon'];
							$menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]["sec"] = 'extensions';
							$menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]["extension"] = true;
							$menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]["enterprise"] = $extension['enterprise'];
							$menu_operation[$extension_menu['fatherId']]['hasExtensions'] = true;
						}
					}
					else {
						$menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]["text"] = __($extension_menu['name']);
						$menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]["id"] = $extension_menu['name'];
						$menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]["refr"] = 0;
						$menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]["icon"] = $extension_menu['icon'];
						$menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]["sec"] = 'extensions';
						$menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]["extension"] = true;
						$menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]["enterprise"] = $extension['enterprise'];
						$menu_operation[$extension_menu['fatherId']]['hasExtensions'] = true;
					}
				}
			}
		}
	}
	
	
	if (!empty($sub)) {
		$menu_operation["extensions"]["text"] = __('Tools');
		$menu_operation["extensions"]["sec2"] = "operation/extensions";
		$menu_operation["extensions"]["id"] = "oper-extensions";
		$menu_operation["extensions"]["sub"] = $sub;
		
	}
}
//~ }

// Save operation menu array to use in operation/extensions.php view
$operation_menu_array = $menu_operation;


if(!$config['pure']) {
	menu_print_menu ($menu_operation, true);
}
?>
