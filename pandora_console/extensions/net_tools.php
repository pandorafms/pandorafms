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

$id_agente = get_parameter ("id_agente");

// This extension is usefull only if the agent has associated IP
$address = agents_get_address($id_agente);

if (!empty($address) || empty($id_agente)) {
	extensions_add_opemode_tab_agent ('network_tools','Network Tools','extensions/net_tools/nettool.png',"main_net_tools", "v1r1");
}

function whereis_the_command ($command) {
	global $config;
	
	if (isset($config['network_tools_config'])) {
		$network_tools_config = json_decode($config['network_tools_config'], true);
		$traceroute_path = $network_tools_config['traceroute_path'];
		$ping_path = $network_tools_config['ping_path'];
		$nmap_path = $network_tools_config['nmap_path'];
		$dig_path = $network_tools_config['dig_path'];
		$snmpget_path = $network_tools_config['snmpget_path'];
		
		switch ($command) {
			case 'traceroute':
				if (!empty($traceroute_path))
					return $traceroute_path;
				break;
			case 'ping':
				if (!empty($ping_path))
					return $ping_path;
				break;
			case 'nmap':
				if (!empty($nmap_path))
					return $nmap_path;
				break;
			case 'dig':
				if (!empty($dig_path))
					return $dig_path;
				break;
			case 'snmpget':
				if (!empty($snmpget_path))
					return $snmpget_path;
				break;
		}
	}
	
	
	
	ob_start();
	system('whereis '. $command);
	$output = ob_get_clean();
	$result = explode(':', $output);
	$result = trim($result[1]);
	
	if ( empty($result)) {
		return NULL;
	}
	
	$result = explode(' ', $result);
	$fullpath = trim($result[0]);
	
	if (! file_exists($fullpath)) {
		return NULL;
	}
	
	return $fullpath;
}

function main_net_tools () {
	
	$id_agente = get_parameter ("id_agente");
	$ip = db_get_sql ("SELECT direccion FROM tagente WHERE id_agente = $id_agente");
	if ($ip == "") {
		echo "<div class='error' style='margin-top:5px'>The agent hasn't got IP</div>";
		return;
	}
	echo "<div>";
	echo "<form name='actionbox' method='post'>";
	echo "<table class=databox width=650>";
	echo "<tr><td>";
	echo __("Operation");
	ui_print_help_tip(__('You can set the command path in the menu Administration -&gt; Extensions -&gt; Config Network Tools'));
	echo "<td>";
	echo "<select name='operation'>";
	echo "<option value='1'>" . __("Traceroute");
	echo "<option value='2'>" . __("Ping host & Latency");
	echo "<option value='3'>" . __("SNMP Interface status");
	echo "<option value='4'>" . __("Basic TCP Port Scan");
	echo "<option value='5'>" . __("DiG/Whois Lookup");
	echo "</select>";
	echo "<td>";
	echo __("SNMP Community");
	echo "<td>";
	echo "<input name=community type=text value='public'>";
	echo "<td>";
	echo "<input name=submit type=submit class='sub next' value='".__('Execute')."'>";
	echo "</tr></table>";
	echo "</form>";
	
	
	$operation = get_parameter ("operation", 0);
	$community = get_parameter ("community", "public");
	
	switch($operation) {
		case 1:
			$traceroute = whereis_the_command ('traceroute');
			if (empty($traceroute)) {
				ui_print_error_message(__('Traceroute executable does not exist.'));
			}
			else {
				echo "<h3>".__("Traceroute to "). $ip. "</h3>";
				echo "<pre>";
				echo system ("$traceroute $ip");
				echo "</pre>";
			}
			break;
		case 2: 
			$ping = whereis_the_command ('ping');
			if (empty($ping)) {
				ui_print_error_message(__('Ping executable does not exist.'));
			}
			else {
				echo "<h3>".__("Ping to "). $ip. "</h3>";
				echo "<pre>";
				echo system ("$ping -c 5 $ip");
				echo "</pre>";
			}
			break;
		case 4:
			$nmap = whereis_the_command ('nmap');
			if (empty($nmap)) {
				ui_print_error_message(__('Nmap executable does not exist.'));
			}
			else {
				echo "<h3>".__("Basic TCP Scan on "). $ip. "</h3>";
				echo "<pre>";
				echo system ("$nmap -F $ip");
				echo "</pre>";
			}
			break;
		case 5: 
			echo "<h3>".__("Domain and IP information for "). $ip. "</h3>";
			
			$dig = whereis_the_command ('dig');
			if (empty($dig)) {
				ui_print_error_message(__('Dig executable does not exist.'));
			}
			else {
				echo "<pre>";
				echo system ("dig $ip");
				echo "</pre>";
			}
			
			$whois = whereis_the_command ('whois');
			if (empty($whois)) {
				ui_print_error_message(__('Whois executable does not exist.'));
			}
			else {
				echo "<pre>";
				echo system ("whois $ip");
				echo "</pre>";
			}
			break;
		case 3:
			echo "<h3>".__("SNMP information for "). $ip. "</h3>";
			
			$snmpget = whereis_the_command ('snmpget');
			if (empty($snmpget)) {
				ui_print_error_message(__('SNMPget executable does not exist.'));
			}
			else {
				echo "<h4>" . __("Uptime") . "</h4>";
				echo "<pre>";
				echo exec ("$snmpget -Ounv -v1 -c $community $ip .1.3.6.1.2.1.1.3.0 ");
				echo "</pre>";
				echo "<h4>" . __("Device info") . "</h4>";
				echo "<pre>";
				
				echo system ("$snmpget -Ounv -v1 -c $community $ip .1.3.6.1.2.1.1.1.0 ");
				echo "</pre>";
				
				echo "<h4>Interface Information</h4>";
				echo "<table class=databox>";
				echo "<tr><th>".__("Interface");
				echo "<th>".__("Status");
				
				$int_max =  exec ("$snmpget -Oqunv -v1 -c $community $ip .1.3.6.1.2.1.2.1.0 ");
				
				for ($ax=0; $ax < $int_max; $ax++) {
					$interface = exec ("$snmpget -Oqunv -v1 -c $community $ip .1.3.6.1.2.1.2.2.1.2.$ax ");
					$estado = exec ("$snmpget -Oqunv -v1 -c $community $ip .1.3.6.1.2.1.2.2.1.8.$ax ");
					echo "<tr><td>$interface<td>$estado";
				}
				echo "</table>";
			}
			break;
	}
	
	echo "</div>";
}

function godmode_net_tools() {
	global $config;
	
	ui_print_page_header (__('Config Network Tools'));
	
	$update_traceroute = (bool)get_parameter('update_traceroute', 0);
	
	$traceroute_path = (string)get_parameter('traceroute_path', '');
	$ping_path = (string)get_parameter('ping_path', '');
	$nmap_path = (string)get_parameter('nmap_path', '');
	$dig_path = (string)get_parameter('dig_path', '');
	$snmpget_path = (string)get_parameter('snmpget_path', '');
	
	
	if ($update_traceroute) {
		$network_tools_config = array();
		$network_tools_config['traceroute_path'] = $traceroute_path;
		$network_tools_config['ping_path'] = $ping_path;
		$network_tools_config['nmap_path'] = $nmap_path;
		$network_tools_config['dig_path'] = $dig_path;
		$network_tools_config['snmpget_path'] = $snmpget_path;
		
		$result = config_update_value('network_tools_config', json_encode($network_tools_config));
		
		ui_print_result_message($result, __('Set the paths.'),
			__('Set the paths.'));
	}
	else {
		
		if (isset($config['network_tools_config'])) {
			$network_tools_config = json_decode($config['network_tools_config'], true);
			$traceroute_path = $network_tools_config['traceroute_path'];
			$ping_path = $network_tools_config['ping_path'];
			$nmap_path = $network_tools_config['nmap_path'];
			$dig_path = $network_tools_config['dig_path'];
			$snmpget_path = $network_tools_config['snmpget_path'];
		}
	}
	
	$table = null;
	$table->width = "80%";
	
	$table->data = array();
	
	$table->data[0][0] = __("Traceroute path");
	$table->data[0][0] .= ui_print_help_tip(__('If it is empty, Pandora searchs the traceroute system.'), true);
	$table->data[0][1] = html_print_input_text('traceroute_path', $traceroute_path, '', 40, 255, true);
	
	$table->data[1][0] = __("Ping path");
	$table->data[1][0] .= ui_print_help_tip(__('If it is empty, Pandora searchs the ping system.'), true);
	$table->data[1][1] = html_print_input_text('ping_path', $ping_path, '', 40, 255, true);
	
	$table->data[2][0] = __("Nmap path");
	$table->data[2][0] .= ui_print_help_tip(__('If it is empty, Pandora searchs the nmap system.'), true);
	$table->data[2][1] = html_print_input_text('nmap_path', $nmap_path, '', 40, 255, true);
	
	$table->data[3][0] = __("Dig path");
	$table->data[3][0] .= ui_print_help_tip(__('If it is empty, Pandora searchs the dig system.'), true);
	$table->data[3][1] = html_print_input_text('dig_path', $dig_path, '', 40, 255, true);
	
	$table->data[4][0] = __("Snmpget path");
	$table->data[4][0] .= ui_print_help_tip(__('If it is empty, Pandora searchs the snmpget system.'), true);
	$table->data[4][1] = html_print_input_text('snmpget_path', $snmpget_path, '', 40, 255, true);
	
	echo '<form id="form_setup" method="post" >';
	echo "<fieldset>";
	echo "<legend>" . __('Options') . "</legend>";
	html_print_input_hidden('update_traceroute', 1);
	html_print_table($table);
	echo "</fieldset>";
	
	echo '<div class="action-buttons" style="width: '.$table->width.'">';
	html_print_submit_button (__('Update'), 'update_button', false, 'class="sub upd"');
	echo '</div>';
	echo '</form>';
}

extensions_add_godmode_menu_option (__('Config Network Tools'), 'PM');
extensions_add_godmode_function ('godmode_net_tools');
?>
