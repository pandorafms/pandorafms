<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

include_once("include/functions_modules.php");
include_once("include/functions_events.php");
include_once ('include/functions_groups.php');
include_once ('include/functions_netflow.php');

//xml con los datos de un agente
function xml_file_agent_data ($agent_data = array(), $file_temp) {
	$file = fopen($file_temp, 'a+');
		
	$content_report = "		<name>". $agent_data['nombre']."</name>\n";
	$content_report .= "		<description>". $agent_data['comentarios']."</description>\n";
	$content_report .= "		<main_ipaddress>".$agent_data['direccion']."</main_ipaddress>\n";
	$content_report .= "		<group>".$agent_data['id_grupo']."</group>\n";
	$content_report .= "		<interval>". $agent_data['intervalo']."</interval>\n";
	
	$sql = "SELECT t1.description, t2.name 
			FROM tagent_custom_data t1, tagent_custom_fields t2
			WHERE t1.id_agent=".$agent_data['id_agente']."
			AND t1.id_field=t2.id_field";
	$custom_fields = db_get_all_rows_sql($sql);
	
	if ($custom_fields !== false) {
		foreach ($custom_fields as $field) {
			$field['name'] = io_safe_output($field['name']);
			//remove blank
			$field['name'] = preg_replace('/\s/', '_', $field['name']);
			$content_report .= "		<".$field['name'].">".$field['description']."</".$field['name'].">\n";
		}
	}
	$content_report .= "		<os_type>".$agent_data['id_os']."</os_type>\n";
	$content_report .= "		<parent>". agents_get_name ($agent_data['id_parent'])."</parent>\n";
	$content_report .= "		<extra_id>".$agent_data['id_extra']."</extra_id>\n";
	$content_report .= "		<disabled>".$agent_data['disabled']."</disabled>\n";
	
	$result = fwrite($file, $content_report);
	$position++;

	fclose($file);
	return $position;
}

//xml con los datos de módulos de un agente
function xml_file_agent_conf ($modules = array(), $file_temp, $position = 0, $id_agent) {
	
	$file = fopen($file_temp, 'a+');
	
	foreach ($modules as $module) {
		
		$content_report = "	<object id=\"$position\">\n";
	
		$content_report .= "		<name>".$module['nombre']."</name>\n";
		$content_report .= "		<id>".$module['id_agente_modulo']."</id>\n";
		$content_report .= "		<type>".$module['id_tipo_modulo']."</type>\n";
		$content_report .= "		<description>".$module['descripcion']."</description>\n";
		$content_report .= "		<extended_info>". $module['extended_info']."</extended_info>\n";
		$content_report .= "		<unit>". $module['unit']."</unit>\n";
		$content_report .= "		<max>". $module['max']."</max>\n";
		$content_report .= "		<min>".$module['min']."</min>\n";
		$content_report .= "		<interval>". $module['module_interval']."</interval>\n";
		$content_report .= "		<ff_interval>". $module['module_ff_interval']."</ff_interval>\n";
		$content_report .= "		<tcp_port>". $module['tcp_port']."</tcp_port>\n";
		$content_report .= "		<tcp_send>". $module['tcp_send']."</tcp_send>\n";
		$content_report .= "		<tcp_rcv>". $module['tcp_rcv']."</tcp_rcv>\n";
		$content_report .= "		<snmp_community>". $module['snmp_community']."</snmp_community>\n";
		$content_report .= "		<snmp_oid>".$module['snmp_oid']."</snmp_oid>\n";
		$content_report .= "		<ip>". $module['ip_target']."</ip>\n";
		$content_report .= "		<module_group>".$module['id_module_group']."</module_group>\n";
		$content_report .= "		<disabled>". $module['disabled']."</disabled>\n";
		$content_report .= "		<id_plugin>".$module['id_plugin']."</id_plugin>\n";
		$content_report .= "		<post_process>". $module['post_process']."</post_process>\n";
		$content_report .= "		<min_warning>". $module['min_warning']."</min_warning>\n";
		$content_report .= "		<max_warning>". $module['max_warning']."</max_warning>\n";
		$content_report .= "		<str_warning>". $module['str_warning']."</str_warning>\n";
		$content_report .= "		<min_critical>". $module['min_critical']."</min_critical>\n";
		$content_report .= "		<max_critical>".$module['max_critical']."</max_critical>\n";
		$content_report .= "		<str_critical>". $module['str_critical']."</str_critical>\n";
		$content_report .= "		<id_policy_module>". $module['id_policy_module']."</id_policy_module>\n";
		$content_report .= "		<wizard_level>".$module['wizard_level']."</wizard_level>\n";
		$content_report .= "		<critical_instructions>". $module['critical_instructions']."</critical_instructions>\n";
		$content_report .= "		<warning_instructions>". $module['warning_instructions']."</warning_instructions>\n";
		$content_report .= "		<unknown_instructions>".$module['unknown_instructions']."</unknown_instructions>\n";
		
		$content_report .= "	</object>\n";
		
		$result = fwrite($file, $content_report);
		$position++;
	}
	fclose($file);
	return $position;
}

// xml eventos
function xml_file_event ($events = array(), $file_temp, $position = 0, $id_agent) {
	
	$file = fopen($file_temp, 'a+');

	foreach ($events as $event) {
		
		$content_report = "	<object id=\"$position\">\n";
		$content_report .= "	<event>".$event['evento']."</event>\n";
		$content_report .= "	<event_type>".$event['event_type']."</event_type>\n";
		$content_report .= "	<criticity>".get_priority_name($event['criticity'])."</criticity>\n";
		$content_report .= "	<count>".$event['count_rep']."</count>\n";
		$content_report .= "	<timestamp>".$event['time2']."</timestamp>\n";
		$content_report .= "	<module_name>".modules_get_agentmodule_name ($event['id_agentmodule'])."</module_name>\n";
		$content_report .= "	<agent_name>".agents_get_name ($id_agent)."</agent_name>\n";
		
		if ($event['estado'] == 0)
			$status = __('New');
		else if ($event['estado'] == 1)
			$status = __('Validated');
		else if ($event['estado'] == 2)
			$status = __('In process');
		else 
			$status = "";
			
		$content_report .= "	<event_status>".$status."</event_status>\n";
		$content_report .= "	<user_comment>".$event['user_comment']."</user_comment>\n";
		$content_report .= "	<tags>".$event['tags']."</tags>\n";
		$content_report .= "	<event_source>".$event['source']."</event_source>\n";
		$content_report .= "	<extra_id>".$event['id_extra']."</extra_id>\n";
		$content_report .= "	<user_validation>".$event['owner_user']."</user_validation>\n";
		$content_report .= "	</object>\n";
		
		$result = fwrite($file, $content_report);
		$position++;
		
	}
	fclose($file);
	return $position;
}

//xml graph
function xml_file_graph ($data_module = array(), $file_temp, $position = 0) {
	
	$file = fopen($file_temp, 'a+');

	foreach ($data_module as $data_m) {
		
		$content_report = "	<object id=\"$position\">\n";
		$content_report .= "		<timestamp>".date ('Y-m-d H:i:s', $data_m['utimestamp'])."</timestamp>\n";
		$content_report .= "		<utimestamp>".$data_m['utimestamp']."</utimestamp>\n";
		$content_report .= "		<data>".$data_m['datos']."</data>\n";
		$content_report .= "	</object>\n";

		$result = fwrite($file, $content_report);
		$position++;
	}

	fclose($file);
	return $position;
}
?>
