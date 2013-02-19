<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2013 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


include_once($config['homedir'] . "/include/functions_config.php");
enterprise_include_once ($config['homedir'] . '/enterprise/include/pdf_translator.php');
enterprise_include_once ($config['homedir'] . '/enterprise/include/functions_metaconsole.php');

// Date format for nfdump
global $nfdump_date_format;
$nfdump_date_format = 'Y/m/d.H:i:s';

/**
 * Selects all netflow filters (array (id_name => id_name)) or filters filtered
 *
 * @param tree string SNMP tree returned by snmp_broser_get_tree.
 * @param id string Level ID. Do not set, used for recursion.
 * @param depth string Branch depth. Do not set, used for recursion.
 * 
 */
function snmp_browser_print_tree ($tree, $id = 0, $depth = 0, $last = 0, $last_array = array()) {
	
	// Leaf
	if (empty ($tree['__LEAVES__'])) {
		return;
	}
	
	$count = 0;
	$total = sizeof (array_keys ($tree['__LEAVES__'])) - 1;
	$last_array[$depth] = $last;
	
	if ($depth > 0) {
		echo "<ul id='ul_$id' style='margin: 0; padding: 0; display: none'>\n";
	}
	else {
		echo "<ul id='ul_$id' style='margin: 0; padding: 0;'>\n";
	}
	foreach ($tree['__LEAVES__'] as $level => $sub_level) {
		
		// Id used to expand leafs
		$sub_id = time() . rand(0, getrandmax());
		
		// Display the branch
		echo "<li id='li_$sub_id' style='margin: 0; padding: 0;'>";
		
		// Indent sub branches
		for ($i = 1; $i <= $depth; $i++) {
			if ($last_array[$i] == 1) {
				html_print_image ("operation/tree/no_branch.png", false, array ("style" => 'vertical-align: middle;'));
			} else {
				html_print_image ("operation/tree/branch.png", false, array ("style" => 'vertical-align: middle;'));
			}
		}
	
		// Branch
		if (! empty ($sub_level['__LEAVES__'])) {
			echo "<a id='anchor_$sub_id' onfocus='javascript: this.blur();' href='javascript: toggleTreeNode(\"$sub_id\", \"$id\");'>";	
			if ($depth == 0 && $count == 0) {
				if ($count == $total) {
					html_print_image ("operation/tree/one_closed.png", false, array ("style" => 'vertical-align: middle;'));
				}
				else {
					html_print_image ("operation/tree/first_closed.png", false, array ("style" => 'vertical-align: middle;'));
				}
			}
			else if ($count == $total) {
				html_print_image ("operation/tree/last_closed.png", false, array ("style" => 'vertical-align: middle;'));
			}
			else {
				html_print_image ("operation/tree/closed.png", false, array ("style" => 'vertical-align: middle;'));
			}
			echo "</a>";
		}
		// Leave
		else {
			if ($depth == 0 && $count == 0) {
				if ($count == $total) {
					html_print_image ("operation/tree/no_branch.png", false, array ("style" => 'vertical-align: middle;'));
				}
				else {
					html_print_image ("operation/tree/first_leaf.png", false, array ("style" => 'vertical-align: middle;'));
				}
			}
			else if ($count == $total) {
				html_print_image ("operation/tree/last_leaf.png", false, array ("style" => 'vertical-align: middle;'));
			}
			else {
				html_print_image ("operation/tree/leaf.png", false, array ("style" => 'vertical-align: middle;'));
			}
		}
		
		// Branch or leave with branches!
		if (isset ($sub_level['__OID__'])) {
			echo "<a onfocus='javascript: this.blur();' href='javascript: snmpGet(\"" . addslashes($sub_level['__OID__']) . "\");'>";
			html_print_image ("images/computer_error.png", false, array ("style" => 'vertical-align: middle;'));
			echo "</a>";
		}
		
		echo '<span>' . $level . '</span>';
		if (isset ($sub_level['__VALUE__'])) {
			echo '<span class="value" style="display: none;"> = ' . $sub_level['__VALUE__'] . '</span>';
		}
		echo "</li>";
		
		// Recursively print sub levels
		snmp_browser_print_tree ($sub_level, $sub_id, $depth + 1, ($count == $total ? 1 : 0), $last_array);
		
		$count++;
	}
	echo "</ul>";
}

/**
 * Build the SNMP tree for the given SNMP agent.
 *
 * @param target_ip string IP of the SNMP agent.
 * @param community string SNMP community to use.
 *
 * @return array The SNMP tree.
 */
function snmp_browser_get_tree ($target_ip, $community, $starting_oid = '.') {
	global $config;
	
	if ($target_ip == '') {
		return __('Target IP cannot be blank.');
	}
	
	// Call snmpwalk
	$oid_tree = array('__LEAVES__' => array());
	exec ('snmpwalk -m ALL -M +' . escapeshellarg($config['homedir'] . '/attachment/mibs') . ' -Cc -c ' . escapeshellarg($community) . ' -v 1 ' . escapeshellarg($target_ip) . ' ' . escapeshellarg($starting_oid), $output, $rc);
	
	//if ($rc != 0) {
	//	return __('No data');
	//}
	foreach ($output as $line) {
		
		// Separate the OID from the value
		$full_oid = explode ('=', $line);
		if (! isset ($full_oid[1])) {
			continue;
		}
		$oid = trim($full_oid[0]);
		$value = trim ($full_oid[1]);
		
		// Parse the OID
		$group = 0;
		$sub_oid = "";
		$ptr = &$oid_tree['__LEAVES__'];
		
		for ($i = 0; $i < strlen ($oid); $i++) {
			
			// "X.Y.Z"
			if ($oid[$i] == '"') {
				$group = $group ^ 1;
			}
			
			// Move to the next element of the OID
			if ($group == 0 && $oid[$i] == '.') {
				
				// Starting dot
				if ($sub_oid == '') {
					continue;
				}
				
				if (! isset ($ptr[$sub_oid]) || ! isset ($ptr[$sub_oid]['__LEAVES__'])) {
					$ptr[$sub_oid]['__LEAVES__'] = array();
				}
				
				$ptr = &$ptr[$sub_oid]['__LEAVES__'];
				$sub_oid = '';
			}
			else {
				if ($oid[$i] != '"') {
					$sub_oid .= $oid[$i];
				}
			}
		}
		
		// The last element will contain the full OID
		$ptr[$sub_oid] = array('__OID__' => $oid, '__VALUE__' => $value);
		$ptr = &$ptr[$sub_oid];
		$sub_oid = "";
	}
	
	return$oid_tree;
}

/**
 * Retrieve data for the specified OID.
 *
 * @param target_ip string IP of the SNMP agent.
 * @param community string SNMP community to use.
 * @param target_oid SNMP OID to query.
 * 
 * @return array OID data.
 * 
 */
function snmp_browser_get_oid ($target_ip, $community, $target_oid) {
	global $config;
	
	if ($target_oid == '') {
		return;
	}
	
	$oid_data['oid'] = $target_oid;
	exec ('snmpget -m ALL -M +' . escapeshellarg($config['homedir'] . '/attachment/mibs') . ' -On -v1 -c ' .  escapeshellarg($community) . " " . escapeshellarg($target_ip) . ' ' . escapeshellarg($target_oid), $output, $rc);
	if ($rc != 0) {
		return $oid_data;
	}
	
	foreach ($output as $line) {
		
		// Separate the OID from the value
		$full_oid = explode ('=', $line);
		if (! isset ($full_oid[1])) {
			break;
		}
		
		$oid = trim($full_oid[0]);
		$oid_data['numeric_oid'] = $oid;
		
		// Translate the OID
		exec ("snmptranslate -Td " .  escapeshellarg($oid), $translate_output);
		foreach ($translate_output as $line) {
			if (preg_match ('/SYNTAX\s+(.*)/', $line, $matches) == 1) {
				$oid_data['syntax'] = $matches[1];
			}
			else if (preg_match ('/MAX-ACCESS\s+(.*)/', $line, $matches) == 1) {
				$oid_data['max_access'] = $matches[1];
			}
			else if (preg_match ('/STATUS\s+(.*)/', $line, $matches) == 1) {
				$oid_data['status'] = $matches[1];
			}
			else if (preg_match ('/DISPLAY\-HINT\s+(.*)/', $line, $matches) == 1) {
				$oid_data['display_hint'] = $matches[1];
			}
		}
		
		// Parse the description
		$translate_output = implode ('', $translate_output);
		if (preg_match ('/DESCRIPTION\s+\"(.*)\"/', $translate_output, $matches) == 1) {
			$oid_data['description'] = $matches[1];
		}
		
		$full_value = explode (':', trim ($full_oid[1]));
		if (! isset ($full_value[1])) {
			$oid_data['value'] = trim ($full_oid[1]);
		}
		else {
			$oid_data['type'] = trim($full_value[0]);
			$oid_data['value'] = trim($full_value[1]);
		}
		
		return $oid_data;
	}
}

/**
 * Print the given OID data.
 *
 * @param $oid array OID data.
 * 
 */
function snmp_browser_print_oid ($oid = array()) {
	
	// OID information table
	$table->width = '100%';
	$table->size = array ();
	$table->data = array ();
	
	foreach (array('oid', 'numeric_oid', 'value') as $key) {
		if (! isset ($oid[$key])) {
			$oid[$key] = '';
		}
	}
	
	$table->data[0][0] = '<strong>'.__('OID').'</strong>';
	$table->data[0][1] = $oid['oid'];
	$table->data[1][0] = '<strong>'.__('Numeric OID').'</strong>';
	$table->data[1][1] = $oid['numeric_oid'];
	$table->data[2][0] = '<strong>'.__('Value').'</strong>';
	$table->data[2][1] = $oid['value'];
	$i = 3;
	if (isset ($oid['type'])) {
		$table->data[$i][0] = '<strong>'.__('Type').'</strong>';
		$table->data[$i][1] = $oid['type'];
		$i++;
	}
	if (isset ($oid['description'])) {
		$table->data[$i][0] = '<strong>'.__('Description').'</strong>';
		$table->data[$i][1] = $oid['description'];
		$i++;
	}
	if (isset ($oid['syntax'])) {
		$table->data[$i][0] = '<strong>'.__('Syntax').'</strong>';
		$table->data[$i][1] = $oid['syntax'];
		$i++;
	}
	if (isset ($oid['display_hint'])) {
		$table->data[$i][0] = '<strong>'.__('Display hint').'</strong>';
		$table->data[$i][1] = $oid['display_hint'];
		$i++;
	}
	if (isset ($oid['max_access'])) {
		$table->data[$i][0] = '<strong>'.__('Max access').'</strong>';
		$table->data[$i][1] = $oid['max_access'];
		$i++;
	}
	if (isset ($oid['status'])) {
		$table->data[$i][0] = '<strong>'.__('Status').'</strong>';
		$table->data[$i][1] = $oid['status'];
		$i++;
	}
	
	echo '<a href="#" onClick="hideOIDData();">';
	html_print_image ("images/cancel.png", false, array ("style" => 'vertical-align: middle;'), false);
	echo '</a>';
	html_print_table($table, false);
}
?>