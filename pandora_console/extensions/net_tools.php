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

extensions_add_opemode_tab_agent ('network_tools','Network Tools','extensions/net_tools/nettool.png',"main_net_tools");

function whereis_the_command ($command) {

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
		echo "<h3 class=error>The agent hasn't got IP</h3>";
		return;
	}
	echo "<div>";
	echo "<form name='actionbox' method='post'>";
	echo "<table class=databox width=650>";
	echo "<tr><td>";
	echo __("Operation");
	echo "<td>";
	echo "<select name='operation'>";
	echo "<option value=1>".__("Traceroute");
	echo "<option value=2>".__("Ping host & Latency");
	echo "<option value=3>".__("SNMP Interface status");
	echo "<option value=4>".__("Basic TCP Port Scan");
	echo "<option value=5>".__("DiG/Whois Lookup");
	echo "</select>";
	echo "<td>";
	echo __("SNMP Community");
	echo "<td>";
	echo "<input name=community type=text value='public'>";
	echo "<td>";
	echo "<input name=submit type=submit class='sub next' value='".__('Execute')."'>";
	echo "</tr></table>";
	echo "</form>";


	$operation = get_parameter ("operation",0);
	$community = get_parameter ("community","public");

	switch($operation){
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
        			echo "<h4>Uptime</h4>";
                	echo "<pre>";
                	echo exec ("$snmpget -Ounv -v1 -c $community $ip .1.3.6.1.2.1.1.3.0 ");
                	echo "</pre>";
	        		echo "<h4>Device info</h4>";              
	                echo "<pre>";
	
	                echo system ("$snmpget -Ounv -v1 -c $community $ip .1.3.6.1.2.1.1.1.0 ");
	                echo "</pre>";
	
	                echo "<h4>Interface Information</h4>";                
	                echo "<table class=databox>";
	                echo "<tr><th>".__("Interface");
	                echo "<th>".__("Status");
	
	                $int_max =  exec ("$snmpget -Oqunv -v1 -c $community $ip .1.3.6.1.2.1.2.1.0 ");
	
	                for ($ax=0; $ax < $int_max; $ax++){
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

?>
