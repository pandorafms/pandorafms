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



if (! check_acl ($config['id_user'], 0, 'PM')) {
	db_pandora_audit("ACL Violation", "Trying to use Open Update Manager extension");
	include ("general/noaccess.php");
	return;
}

function update_manage_license_info_main() {
	update_manage_license_info();
}

function update_manage_license_info(){

	ui_print_page_header (__('Update manager').' - '. __('License info'), "images/extensions.png", false, "", true, "" );

	global $config;
	include_once("include/functions_db.php");

	// Return the order of the given char in ("A".."Z", 0-9)
	function license_char_ord ($char) {

		$ascii_ord = ord ($char);
		if ($ascii_ord >= ord ('A') && $ascii_ord <= ord ('Z')) {
			return $ascii_ord - ord ('A');
		}
		if ($ascii_ord >= ord ('0') && $ascii_ord <= ord ('9')) {
			return sizeof (range ('A','Z')) + $ascii_ord - ord ('0');
		}

		return -1;
	}

	// Un-shift a string given a key: string[i] = shifted_string[i] - key[i] mod |Alphabet|
	$Alphabet = array_merge (range ('A','Z'), range('0', '9'));
	$AlphabetSize = sizeof ($Alphabet);
	
	function license_unshift_string ($string, $key) {
		global $Alphabet;
		global $AlphabetSize;

		// Get the minimum length
		$string_length = strlen ($string);
		$key_length = strlen ($key);
		if ($string_length < $key_length) {
			$min_length = $string_length;
		} else {
			$min_length = $key_length;
		}

		// Shift the string
		$unshifted_string = '';
		for ($i = 0; $i < $min_length; $i++) {
			$unshifted_string .= $Alphabet[($AlphabetSize + license_char_ord ($string[$i]) - license_char_ord ($key[$i])) % $AlphabetSize];
		}

		return $unshifted_string;
	}
	
	function show_check_pandora_license ($license) {

			if (strlen ($license) != 32) {
					return array ("Invalid license!", '', '', '', '', '', '');
			}

			$company_name = trim (substr ($license, 0, 4), "0");
			$random_string = substr ($license, 4, 8);
			$max_agents = (int) license_unshift_string (substr ($license, 12, 6), $random_string);
			$license_mode_string = license_unshift_string (substr ($license, 18, 6), $random_string);
			$license_mode = (int) substr ($license_mode_string, 0, 1);
			$expiry_date_string = license_unshift_string (substr ($license, 24, 8), $random_string);
			$expiry_year = substr ($expiry_date_string, 0, 4);
			$expiry_month = substr ($expiry_date_string, 4, 2);
			$expiry_day = substr ($expiry_date_string, 6, 2);
			return array ("Valid license.", $company_name, $max_agents, $expiry_day, $expiry_month, $expiry_year, $license_mode);
	}

	$license = db_get_value_sql ('SELECT value FROM tupdate_settings WHERE `key`="customer_key"');
	
	if ($license === false) {
		echo "<p>License not available</p>";
		return;
	}

	$license_info = array();	
	$license_info = show_check_pandora_license($license);

	$table->width = '98%';
	$table->data = array ();

	$table->data[0][0] = '<strong>'.__('Company').'</strong>';
	$table->data[0][1] = $license_info[1];
	$table->data[1][0] = '<strong>'.__('Expires').'</strong>';
	$table->data[1][1] = $license_info[3] . ' / ' . $license_info[4] . ' / ' . $license_info[5];
	$table->data[2][0] = '<strong>'.__('Platform Limit').'</strong>';
	$table->data[2][1] = $license_info[2];
	$table->data[3][0] = '<strong>'.__('Current Platform Count').'</strong>';
	$count_agents = db_get_value_sql ('SELECT count(*) FROM tagente');
	$table->data[3][1] = $count_agents;
	$table->data[4][0] = '<strong>'.__('License Mode').'</strong>';
	if ($license_info[6] == 1)
		$license_mode_string = 'Client';
	else
		$license_mode_string = 'Trial';
	$table->data[4][1] = $license_mode_string;	
	
	html_print_table ($table);

}

extensions_add_godmode_menu_option (__('Update manager license info'), 'PM');
extensions_add_godmode_function ('update_manage_license_info_main');
