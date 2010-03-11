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

// Load global vars
global $config;

check_login ();

if (is_ajax ()) {
	$get_os_icon = (bool) get_parameter ('get_os_icon');
	
	if ($get_os_icon) {
		$id_os = (int) get_parameter ('id_os');
		print_os_icon ($id_os, false);
		return;
	}
	
	return;
}


if (! give_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
	audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation", "Trying to access Setup Management");
	require ("general/noaccess.php");
	return;
}
// Load enterprise extensions
enterprise_include ('godmode/setup/setup.php');

/*
 NOTICE FOR DEVELOPERS:
 
 Update operation is done in config_process.php
 This is done in that way so the user can see the changes inmediatly.
 If you added a new token, please check update_config() in functions_config.php
 to add it there.
*/

// Header
print_page_header (__('General configuration'), "", false, "", true);


$table->width = '90%';
$table->data = array ();

// Current config["language"] could be set by user, not taken from global setup !

$current_system_lang = get_db_sql ('SELECT `value` FROM tconfig WHERE `token` = "language"');

if ($current_system_lang == ""){
	$current_system_lang = "en";
}

$table->data[0][0] = __('Language code for Pandora');
$table->data[0][1] = print_select_from_sql ('SELECT id_language, name FROM tlanguage',
	'language', $current_system_lang , '', '', '', true);

$table->data[1][0] = __('Remote config directory');
$table->data[1][1] = print_input_text ('remote_config', $config["remote_config"], '', 30, 100, true);

$table->data[6][0] = __('Auto login (hash) password');
$table->data[6][1] = print_input_text ('loginhash_pwd', $config["loginhash_pwd"], '', 15, 15, true);

$table->data[8][0] = __('Timestamp or time comparation') . print_help_icon ("time_stamp-comparation", true);
$table->data[8][1] = __('Comparation in rollover').' ';
$table->data[8][1] .=  print_radio_button ('prominent_time', "comparation", '', $config["prominent_time"], true);
$table->data[8][1] .= '<br />'.__('Timestamp in rollover').' ';
$table->data[8][1] .= print_radio_button ('prominent_time', "timestamp", '', $config["prominent_time"], true);

$table->data[9][0] = __('Time source') . print_help_icon ("timesource", true);
$sources["system"] = __('System');
$sources["sql"] = __('Database');
$table->data[9][1] = print_select ($sources, 'timesource', $config["timesource"], '', '', '', true);

$table->data[10][0] = __('Automatic update check');
$table->data[10][1] = __('Yes').'&nbsp;'.print_radio_button ('autoupdate', 1, '', $config["autoupdate"], true).'&nbsp;&nbsp;';
$table->data[10][1] .= __('No').'&nbsp;'.print_radio_button ('autoupdate', 0, '', $config["autoupdate"], true);

$table->data[11][0] = __('Enforce https');
$table->data[11][1] = __('Yes').'&nbsp;'.print_radio_button ('https', 1, '', $config["https"], true).'&nbsp;&nbsp;';
$table->data[11][1] .= __('No').'&nbsp;'.print_radio_button ('https', 0, '', $config["https"], true);


$table->data[14][0] = __('Attachment store');
$table->data[14][1] = print_input_text ('attachment_store', $config["attachment_store"], '', 50, 255, true);

$table->data[15][0] = __('IP list with API access') . 
	print_help_tip (__("The list of IPs separate with carriage return."), true);
$list_ACL_IPs_for_API = get_parameter('list_ACL_IPs_for_API', implode("\n", $config['list_ACL_IPs_for_API']));
$table->data[15][1] = print_textarea('list_ACL_IPs_for_API', 2, 25, $list_ACL_IPs_for_API, 'style="height: 50px; width: 300px"', true);

$table->data[16][0] = __('Enable GIS features in Pandora Console');
$table->data[16][1] = __('Yes').'&nbsp;'.print_radio_button ('activate_gis', 1, '', $config["activate_gis"], true).'&nbsp;&nbsp;';
$table->data[16][1] .= __('No').'&nbsp;'.print_radio_button ('activate_gis', 0, '', $config["activate_gis"], true);

$table->data[19][0] = __('Timezone setup');
$table->data[19][1] = print_input_text ('timezone', $config["timezone"], '', 15, 15, true);

enterprise_hook ('setup');

echo '<form id="form_setup" method="post">';
print_input_hidden ('update_config', 1);
print_table ($table);
echo '<div class="action-buttons" style="width: '.$table->width.'">';
print_submit_button (__('Update'), 'update_button', false, 'class="sub upd"');
echo '</div>';
echo '</form>';
?>
