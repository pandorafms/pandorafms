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
	static $url = false;
	
	// Get the base URL for images
	if ($url === false) {
		$url = ui_get_full_url('operation/tree');
	}
	
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
				echo '<img src="' . $url . '/no_branch.png" style="vertical-align: middle;">';
			}
			else {
				echo '<img src="' . $url . '/branch.png" style="vertical-align: middle;">';
			}
		}
		
		// Branch
		if (! empty ($sub_level['__LEAVES__'])) {
			echo "<a id='anchor_$sub_id' onfocus='javascript: this.blur();' href='javascript: toggleTreeNode(\"$sub_id\", \"$id\");'>";	
			if ($depth == 0 && $count == 0) {
				if ($count == $total) {
					echo '<img src="' . $url . '/one_closed.png" style="vertical-align: middle;">';
				}
				else {
					echo '<img src="' . $url . '/first_closed.png" style="vertical-align: middle;">';
				}
			}
			else if ($count == $total) {
				echo '<img src="' . $url . '/last_closed.png" style="vertical-align: middle;">';
			}
			else {
				echo '<img src="' . $url . '/closed.png" style="vertical-align: middle;">';
			}
			echo "</a>";
		}
		// Leave
		else {
			if ($depth == 0 && $count == 0) {
				if ($count == $total) {
					echo '<img src="' . $url . '/no_branch.png" style="vertical-align: middle;">';
				}
				else {
					echo '<img src="' . $url . '/first_leaf.png" style="vertical-align: middle;">';
				}
			}
			else if ($count == $total) {
				echo '<img src="' . $url . '/last_leaf.png" style="vertical-align: middle;">';
			}
			else {
				echo '<img src="' . $url . '/leaf.png" style="vertical-align: middle;">';
			}
		}
		
		// Branch or leave with branches!
		if (isset ($sub_level['__OID__'])) {
			echo "<a onfocus='javascript: this.blur();' href='javascript: snmpGet(\"" . addslashes($sub_level['__OID__']) . "\");'>";
			echo '<img src="' . $url . '/../../images/eye.png" style="vertical-align: middle;">';
			echo "</a>";
		}
		
		echo '&nbsp;<span>' . $level . '</span>'. html_print_checkbox("create_$sub_id", 0, false, true, false, '');;
		if (isset ($sub_level['__VALUE__'])) {
			echo '<span class="value" style="display: none;">&nbsp;=&nbsp;' . $sub_level['__VALUE__'] . '</span>';
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
function snmp_browser_get_tree ($target_ip, $community, $starting_oid = '.', $version = '2c', $snmp3_auth_user = '', $snmp3_security_level = '', $snmp3_auth_method = '', $snmp3_auth_pass = '', $snmp3_privacy_method = '', $snmp3_privacy_pass = '') {
	global $config;
	
	if ($target_ip == '') {
		return __('Target IP cannot be blank.');
	}
	
	// Call snmpwalk
	if (empty($config['snmpwalk'])) {
		switch (PHP_OS) {
			case "FreeBSD":
				$snmpwalk_bin = '/usr/local/bin/snmpwalk';
				break;
			case "NetBSD":
				$snmpwalk_bin = '/usr/pkg/bin/snmpwalk';
				break;
			default:
				$snmpwalk_bin = 'snmpwalk';
				break;
		}
	}
	else {
		$snmpwalk_bin = $config['snmpwalk'];
	}

	switch (PHP_OS) {
		case "WIN32":
		case "WINNT":
		case "Windows":
			$error_redir_dir = 'NUL';
			break;
		default:
			$error_redir_dir = '/dev/null';
			break;
	}

	$oid_tree = array('__LEAVES__' => array());
	if ($version == "3") {
		switch ($snmp3_security_level) {
			case "authPriv":
				exec ($snmpwalk_bin . ' -m ALL -v 3 -u ' . escapeshellarg($snmp3_auth_user) . ' -A ' . escapeshellarg($snmp3_auth_pass) . ' -l ' . escapeshellarg($snmp3_security_level) . ' -a ' . escapeshellarg($snmp3_auth_method) . ' -x ' . escapeshellarg($snmp3_privacy_method) . ' -X ' . escapeshellarg($snmp3_privacy_pass) . ' ' . escapeshellarg($target_ip)  . ' ' . escapeshellarg($starting_oid) . ' 2> ' . $error_redir_dir, $output, $rc);
				break;
			case "authNoPriv":
				exec ($snmpwalk_bin . ' -m ALL -v 3 -u ' . escapeshellarg($snmp3_auth_user) . ' -A ' . escapeshellarg($snmp3_auth_pass) . ' -l ' . escapeshellarg($snmp3_security_level) . ' -a ' . escapeshellarg($snmp3_auth_method) . ' ' . escapeshellarg($target_ip)  . ' ' . escapeshellarg($starting_oid) . ' 2> ' . $error_redir_dir, $output, $rc);
				break;
			case "noAuthNoPriv":
				exec ($snmpwalk_bin . ' -m ALL -v 3 -u ' . escapeshellarg($snmp3_auth_user) . ' -l ' . escapeshellarg($snmp3_security_level) . ' ' . escapeshellarg($target_ip)  . ' ' . escapeshellarg($starting_oid) . ' 2> ' . $error_redir_dir, $output, $rc);
				break;
		}
	}
	else {
		exec ($snmpwalk_bin . ' -m ALL -M +' . escapeshellarg($config['homedir'] . '/attachment/mibs') . ' -Cc -c ' . escapeshellarg($community) . ' -v ' . escapeshellarg($version) . ' ' . escapeshellarg($target_ip) . ' ' . escapeshellarg($starting_oid) . ' 2> ' . $error_redir_dir, $output, $rc);
	}
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
			if ($group == 0 && ($oid[$i] == '.' || ($oid[$i] == ':' && $oid[$i + 1] == ':'))) {
				
				// Skip the next :
				if ($oid[$i] == ':') {
					$i++;
				}
				
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
	
	return $oid_tree;
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
function snmp_browser_get_oid ($target_ip, $community, $target_oid, $version = '2c', $snmp3_auth_user = '', $snmp3_security_level = '', $snmp3_auth_method = '', $snmp3_auth_pass = '', $snmp3_privacy_method = '', $snmp3_privacy_pass = '') {
	global $config;
	
	if ($target_oid == '') {
		return;
	}
	
	$oid_data['oid'] = $target_oid;
	if (empty($config['snmpget'])) {
		switch (PHP_OS) {
			case "FreeBSD":
				$snmpget_bin = '/usr/local/bin/snmpget';
				break;
			case "NetBSD":
				$snmpget_bin = '/usr/pkg/bin/snmpget';
				break;
			default:
				$snmpget_bin = 'snmpget';
				break;
		}
	}
	else {
		$snmpget_bin = $config['snmpget'];
	}

	switch (PHP_OS) {
		case "WIN32":
		case "WINNT":
		case "Windows":
			$error_redir_dir = 'NUL';
			break;
		default:
			$error_redir_dir = '/dev/null';
			break;
	}

	if ($version == "3") {
		exec ($snmpget_bin  . ' -m ALL -v 3 -u ' . escapeshellarg($snmp3_auth_user) . ' -A ' . escapeshellarg($snmp3_auth_pass) . ' -l ' . escapeshellarg($snmp3_security_level) . ' -a ' . escapeshellarg($snmp3_auth_method) . ' -x ' . escapeshellarg($snmp3_privacy_method) . ' -X ' . escapeshellarg($snmp3_privacy_pass) . ' ' . escapeshellarg($target_ip)  . ' ' . escapeshellarg($target_oid) . ' 2> ' . $error_redir_dir, $output, $rc);
	}
	else {
		exec ($snmpget_bin . ' -m ALL -M +' . escapeshellarg($config['homedir'] . '/attachment/mibs') . ' -On -c ' . escapeshellarg($community) . ' -v ' . escapeshellarg($version) . ' ' . escapeshellarg($target_ip) . ' ' . escapeshellarg($target_oid) . ' 2> ' . $error_redir_dir, $output, $rc);
	}
	
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
		if (empty($config['snmptranslate'])) {
			switch (PHP_OS) {
				case "FreeBSD":
					$snmptranslate_bin = '/usr/local/bin/snmptranslate';
					break;
				case "NetBSD":
					$snmptranslate_bin = '/usr/pkg/bin/snmptranslate';
					break;
				default:
					$snmptranslate_bin = 'snmptranslate';
					break;
			}
		}
		else {
			$snmptranslate_bin = $config['snmptranslate'];
		}
		exec ($snmptranslate_bin . " -Td " .  escapeshellarg($oid),
			$translate_output);
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
 * @param oid array OID data.
 * @param custom_action string A custom action added to next to the close button.
 * @param bool return The result is printed if set to true or returned if set to false.
 * 
 * @return string The OID data.
 */
function snmp_browser_print_oid ($oid = array(), $custom_action = '',
	$return = false, $community = '', $snmp_version = 1) {
	
	$output = '';
	
	// OID information table
	$table->width = '100%';
	$table->size = array ();
	$table->data = array ();
	
	foreach (array('oid', 'numeric_oid', 'value') as $key) {
		if (! isset ($oid[$key])) {
			$oid[$key] = '';
		}
	}
	
	$table->data[0][0] = '<strong>' . __('OID') . '</strong>';
	$table->data[0][1] = $oid['oid'];
	$table->data[1][0] = '<strong>' . __('Numeric OID') . '</strong>';
	$table->data[1][1] = '<span id="snmp_selected_oid">' .
		$oid['numeric_oid'] . '</span>';
	$table->data[2][0] = '<strong>' . __('Value') . '</strong>';
	$table->data[2][1] = $oid['value'];
	$i = 3;
	if (isset ($oid['type'])) {
		$table->data[$i][0] = '<strong>' . __('Type') . '</strong>';
		$table->data[$i][1] = $oid['type'];
		$i++;
	}
	if (isset ($oid['description'])) {
		$table->data[$i][0] = '<strong>' . __('Description') . '</strong>';
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
	
	$closer = '<a href="javascript:" onClick="hideOIDData();">';
	$closer .= html_print_image ("images/blade.png", true, array ("title" => __('Close'), "style" => 'vertical-align: middle;'), false);
	$closer .= '</a>';
	
	$table->head[0] = $closer;
	$table->head[1] = __('OID Information');
	
	// Add a span for custom actions
	if ($custom_action != '') {
		$output .= '<span id="snmp_custom_action">' . $closer . $custom_action . '</span>';
	}
	
	$output .= html_print_table($table, true);
	
	$url = "index.php?" .
		"sec=gmodules&" .
		"sec2=godmode/modules/manage_network_components";
	
	$output .= '<form style="text-align: center;" method="post" action="' . $url . '">';
	$output .= html_print_input_hidden('create_network_from_snmp_browser', 1, true);
	$output .= html_print_input_hidden('id_component_type', 2, true);
	$output .= html_print_input_hidden('type', 17, true);
	$name = '';
	if (!empty($oid['oid'])) {
		$name = $oid['oid'];
	}
	$output .= html_print_input_hidden('name', $name, true);
	$description = '';
	if (!empty($oid['description'])) {
		$description = $oid['description'];
	}
	$output .= html_print_input_hidden('description', $description, true);
	$output .= html_print_input_hidden('snmp_oid', $oid['numeric_oid'], true);
	$output .= html_print_input_hidden('snmp_community', $community, true);
	$output .= html_print_input_hidden('snmp_version', $snmp_version, true);
	$output .= html_print_submit_button(__('Create network component'),
		'', false, '', true);
	$output .= '</form>';
	
	if ($return) {
		return $output;
	}
	
	echo $output;
}

/**
 * Print the div that contains the SNMP browser.
 *
 * @param bool return The result is printed if set to true or returned if set to false.
 * @param string width Width of the SNMP browser. Units must be specified.
 * @param string height Height of the SNMP browser. Units must be specified.
 * @param string display CSS display value for the container div. Set to none to hide the div.
 * 
 * @return string The container div.
 * 
 */
function snmp_browser_print_container ($return = false, $width = '100%', $height = '500px', $display = '') {
	
	// Target selection
	$table = new stdClass();
	$table->width = '100%';
	$table->class = 'databox filters';
	$table->size = array ();
	$table->data = array ();
	
	$table->data[0][0] = '<strong>'.__('Target IP').'</strong> &nbsp;&nbsp;';
	$table->data[0][0] .= html_print_input_text ('target_ip', '', '', 25, 0, true);
	$table->data[0][1] = '<strong>'.__('Community').'</strong> &nbsp;&nbsp;';
	$table->data[0][1] .= html_print_input_text ('community', '', '', 25, 0, true);
	$table->data[0][2] = '<strong>'.__('Starting OID').'</strong> &nbsp;&nbsp;';
	$table->data[0][2] .= html_print_input_text ('starting_oid', '.1.3.6.1.2', '', 25, 0, true);
	
	$table->data[0][3] = '<strong>' . __('Version') . '</strong> &nbsp;&nbsp;';
	$table->data[0][3] .= html_print_select (
		array ('1' => 'v. 1',
			'2' => 'v. 2',
			'2c' => 'v. 2c',
			'3' => 'v. 3'),
		'snmp_browser_version', '', 'checkSNMPVersion();', '', '', true, false, false, '');
	
	$table->data[0][4] = html_print_button(__('Browse'), 'browse', false, 'snmpBrowse()', 'class="sub search" style="margin-top:0px;"', true);
	
	// SNMP v3 options
	$table3 = new stdClass();
	$table3->width = '100%';
	
	$table3->valign[0] = '';
	$table3->valign[1] = '';
	
	$table3->data[2][1] = '<b>'.__('Auth user').'</b>';
	$table3->data[2][2] = html_print_input_text ('snmp3_browser_auth_user', '', '', 15, 60, true);
	$table3->data[2][3] = '<b>'.__('Auth password').'</b>';
	$table3->data[2][4] = html_print_input_password ('snmp3_browser_auth_pass', '', '', 15, 60, true);
	$table3->data[2][4] .= html_print_input_hidden('active_snmp_v3', 0, true);
	
	$table3->data[5][0] = '<b>'.__('Privacy method').'</b>';
	$table3->data[5][1] = html_print_select(array('DES' => __('DES'), 'AES' => __('AES')), 'snmp3_browser_privacy_method', '', '', '', '', true);
	$table3->data[5][2] = '<b>'.__('Privacy pass').'</b>';
	$table3->data[5][3] = html_print_input_password ('snmp3_browser_privacy_pass', '', '', 15, 60, true);
	
	$table3->data[6][0] = '<b>'.__('Auth method').'</b>';
	$table3->data[6][1] = html_print_select(array('MD5' => __('MD5'), 'SHA' => __('SHA')), 'snmp3_browser_auth_method', '', '', '', '', true);
	$table3->data[6][2] = '<b>'.__('Security level').'</b>';
	$table3->data[6][3] = html_print_select(array('noAuthNoPriv' => __('Not auth and not privacy method'),
		'authNoPriv' => __('Auth and not privacy method'), 'authPriv' => __('Auth and privacy method')), 'snmp3_browser_security_level', '', '', '', '', true);
	
	// Search tools
	$table2 = new stdClass();
	$table2->width = '100%';
	$table2->class = 'databox filters';
	$table2->size = array ();
	$table2->data = array ();
	
	$table2->data[0][0] = html_print_input_text ('search_text', '', '', 25, 0, true);
	$table2->data[0][0] .= '<a href="javascript:">' .
		html_print_image ("images/zoom.png", true, array ('title' => __('Search'), 'style' => 'vertical-align: middle;', 'onclick' => 'searchText();')) . '</a>';
	$table2->data[0][1] = '&nbsp;' . '<a href="javascript:">' .
		html_print_image ("images/go_first.png", true, array ('title' => __('First match'), 'style' => 'vertical-align: middle;', 'onclick' => 'searchFirstMatch();')) . '</a>';
	$table2->data[0][1] .= '&nbsp;' . '<a href="javascript:">' .
		html_print_image ("images/go_previous.png", true, array ('title' => __('Previous match'), 'style' => 'vertical-align: middle;', 'onclick' => 'searchPrevMatch();')) . '</a>';
	$table2->data[0][1] .= '&nbsp;' . '<a href="javascript:">' .
		html_print_image ("images/go_next.png", true, array ('title' => __('Next match'), 'style' => 'vertical-align: middle;', 'onclick' => 'searchNextMatch();')) . '</a>';
	$table2->data[0][1] .= '&nbsp;' . '<a href="javascript:">' .
		html_print_image ("images/go_last.png", true, array ('title' => __('Last match'), 'style' => 'vertical-align: middle;', 'onclick' => 'searchLastMatch();')) . '</a>';
	$table2->cellstyle[0][1] = 'text-align:center;';
	
	$table2->data[0][2] = '&nbsp;' . '<a href="javascript:">' .
		html_print_image("images/expand.png", true,
			array('title' => __('Expand the tree (can be slow)'),
				'style' => 'vertical-align: middle;', 'onclick' => 'expandAll();')) . '</a>';
	$table2->data[0][2] .= '&nbsp;' . '<a href="javascript:">' . html_print_image ("images/collapse.png", true, array ('title' => __('Collapse the tree'), 'style' => 'vertical-align: middle;', 'onclick' => 'collapseAll();')) . '</a>';
	$table2->cellstyle[0][2] = 'text-align:center;';
	
	// This extra div that can be handled by jquery's dialog
	$output =  '<div id="snmp_browser_container" style="display:' . $display . '">';
	$output .= '<div style="text-align: left; width: ' . $width . '; height: ' . $height . ';">';
	$output .=   '<div style="width: 100%">';
	$output .=   html_print_table($table, true);
	$output .=   '</div>';
	if (!isset($snmp_version)) {
		$snmp_version = null;
	}
	if ($snmp_version == 3) {
		$output .= '<div id="snmp3_browser_options">';
	}
	else {
		$output .= '<div id="snmp3_browser_options" style="display: none;">';
	}
	
	$output .= ui_toggle(html_print_table($table3, true), __('SNMP v3 options'), '', true, true);
	$output .= '</div>';
	$output .=   '<div style="width: 100%; padding-top: 10px;">';
	$output .=   ui_toggle(html_print_table($table2, true), __('Search options'), '', true, true);
	$output .=   '</div>';
	
	// SNMP tree container
	$output .=   '<div style="width: 100%; height: 100%; margin-top: 5px; position: relative;">';
	$output .=   html_print_input_hidden ('search_count', 0, true);
	$output .=   html_print_input_hidden ('search_index', -1, true);
	
	// Save some variables for javascript functions
	$output .=   html_print_input_hidden ('ajax_url', ui_get_full_url("ajax.php"), true);
	$output .=   html_print_input_hidden ('search_matches_translation', __("Search matches"), true);
	
	$output .=     '<div id="search_results" style="display: none; padding: 5px; background-color: #EAEAEA; border: 1px solid #E2E2E2; border-radius: 4px;"></div>';
	$output .=     '<div id="spinner" style="position: absolute; top:0; left:0px; display:none; padding: 5px;">' . html_print_image ("images/spinner.gif", true) . '</div>';
	$output .=     '<div id="snmp_browser" style="height: 100%; overflow: auto; background-color: #F4F5F4; border: 1px solid #E2E2E2; border-radius: 4px; padding: 5px;"></div>';
	$output .=     '<div id="snmp_data" style="margin: 5px;"></div>';
	$output .=   '</div>';
	$output .= '</div>';
	$output .= '</div>';
	
	if ($return) {
		return $output;
	}
	
	echo $output;
}

?>

<script type="text/javascript">
	$(document).ready(function () {
		$('input[name*=create_network_component]').click(function () {
			var prueba = $('#ul_0').find('input').map(function(){ 
				if(this.id.indexOf('checkbox-create_')!=-1){
					if($(this).is(':checked')){
						return this.id;
					}
					
				} }).get();
			var target_ip = $('#text-target_ip').val();
			
			
			prueba.forEach(function(product, index) {
				var oid = $("#"+product).siblings('a').attr('href');
				if(oid.indexOf('javascript: snmpGet("')!=-1){
					oid = oid.replace('javascript: snmpGet("',"");
					oid = oid.replace('");',"");
					var target_ip = $('#text-target_ip').val();
					var community = $('#text-community').val();
					var snmp_version = $('#snmp_browser_version').val();
					var snmp3_auth_user = $('#text-snmp3_browser_auth_user').val();
					var snmp3_security_level = $('#snmp3_browser_security_level').val();
					var snmp3_auth_method = $('#snmp3_browser_auth_method').val();
					var snmp3_auth_pass = $('#password-snmp3_browser_auth_pass').val();
					var snmp3_privacy_method = $('#snmp3_browser_privacy_method').val();
					var snmp3_privacy_pass = $('#password-snmp3_browser_privacy_pass').val();
					var ajax_url = $('#hidden-ajax_url').val();
					
					var custom_action = $('#hidden-custom_action').val();
					if (custom_action == undefined) {
						custom_action = '';
					}
					
					// Prepare the AJAX call
					var params = [
						"target_ip=" + target_ip,
						"community=" + community,
						"oid=" + oid,
						"snmp_browser_version=" + snmp_version,
						"snmp3_browser_auth_user=" + snmp3_auth_user,
						"snmp3_browser_security_level=" + snmp3_security_level,
						"snmp3_browser_auth_method=" + snmp3_auth_method,
						"snmp3_browser_auth_pass=" + snmp3_auth_pass,
						"snmp3_browser_privacy_method=" + snmp3_privacy_method,
						"snmp3_browser_privacy_pass=" + snmp3_privacy_pass,
						"action=" + "snmpget",
						"custom_action=" + custom_action,
						"page=include/ajax/snmp_browser.ajax"
					];
					
					$.ajax({
        				type: "GET",
        				url: "ajax.php",
        				data: params.join ("&"),
						dataType: "html",
        				success: function(data) {
							console.log(data);
						}
					});
				}
			});
		});
		
		$('input[id^=checkbox-create]').change(function () {
			if ($(this).is(':checked') ) {
				var id_input = $(this).attr("id");
				id_input = id_input.split("checkbox-create_");
				var checks = $('#ul_'+id_input[1]).find('input').map(function(){ 
					if(this.id.indexOf('checkbox-create_')!=-1){
						return this.id;
					} }).get();
				
				checks.forEach(function(product, index) {
					$("#"+product).prop('checked', "true");
				});
				
			} else {
				var id_input = $(this).attr("id");
				
				id_input = id_input.split("checkbox-create_");
				var checks = $('#ul_'+id_input[1]).find('input').map(function(){ 
					if(this.id.indexOf('checkbox-create_')!=-1){
						return this.id;
					} }).get();
					
				checks.forEach(function(product, index) {
					$("#"+product).prop('checked', false);
				});
			}
		});
	});
	
</script>
