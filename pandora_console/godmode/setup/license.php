<?php
//Pandora FMS- http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas

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

if (! check_acl ($config['id_user'], 0, 'PM')) {
	db_pandora_audit("ACL Violation", "Trying to change License settings");
	include ("general/noaccess.php");
	return;
}

$update_settings = (bool) get_parameter_post ('update_settings');

ui_print_page_header (__('License management'), "images/extensions.png", false, "", true);

if ($update_settings) {
	foreach ($_POST['keys'] as $key => $value) {
		db_process_sql_update('tupdate_settings',
			array('value' => $value), array('key' => $key));
	}
	
	ui_print_success_message(__('License updated'));
}

ui_require_javascript_file_enterprise('load_enterprise');
enterprise_include_once('include/functions_license.php');
$license = enterprise_hook('license_get_info');

$rows = db_get_all_rows_in_table('tupdate_settings');

$settings = new StdClass;
foreach ($rows as $row) {
	$settings->$row['key'] = $row['value'];
}

echo '<script type="text/javascript">';
if (enterprise_installed())
	print_js_var_enteprise();
echo '</script>';

echo '<form method="post">';

$table->width = '95%';
$table->data = array ();

$table->data[0][0] = '<strong>'.__('Customer key').'</strong>';
$table->data[0][1] = html_print_input_text ('keys[customer_key]', $settings->customer_key, '', 81, 255, true);

$table->data[1][0] = '<strong>'.__('Expires').'</strong>';
$table->data[1][1] = html_print_input_text('expires', $license['expiry_date'], '', 10, 255, true, true);

$table->data[2][0] = '<strong>'.__('Platform Limit').'</strong>';
$table->data[2][1] = html_print_input_text('expires', $license['max_agents'], '', 10, 255, true, true);

$table->data[3][0] = '<strong>'.__('Current Platform Count').'</strong>';
$table->data[3][1] = html_print_input_text('expires', $license['agent_count'], '', 10, 255, true, true);

$table->data[4][0] = '<strong>'.__('License Mode').'</strong>';
$table->data[4][1] = html_print_input_text('expires', $license['license_mode'], '', 10, 255, true, true);

html_print_table ($table);
if (enterprise_installed()) {
	echo '<div class="action-buttons" style="width: '.$table->width.'">';
	html_print_input_hidden ('update_settings', 1);
	html_print_submit_button (__('Validate'), 'update_button', false, 'class="sub upd"');
	html_print_button(__('Request new license'), '', false, 'generate_request_code()', 'class="ui-button-dialog ui-widget ui-state-default ui-corner-all ui-button-text-only sub next"');
	echo '</div>';
}
echo '</form>';
echo '<div id="code_license_dialog" style="display: none; text-align: left;" title="' . __('Request new license') . '">';
echo '<div id="logo">';
html_print_image('images/pandora_tinylogo.png');
echo '</div>';
echo '' . __('To get your <b>Pandora FMS Enterprise License</b>:') . '<br />';
echo '<ul>';
echo '<li>';
echo '' . sprintf(__('Go to %s'), "<a target=\"_blank\" href=\"http://artica.es/pandoraupdate51/index.php?section=generate_key_client\">http://artica.es/pandoraupdate51/index.php?section=generate_key_client</a>");
echo '</li>';
echo '<li>';
echo '' .__('Enter the <b>auth key</b> and the following <b>request key</b>:');
echo '</li>';
echo '</ul>';
echo '<div id="code"></div>';
echo '<ul>';
echo '<li>';
echo '' . __('Enter your name (or a company name) and a contact email address.');
echo '</li>';
echo '<li>';
echo '' .__('Click on <b>Generate</b>.');
echo '</li>';
echo '<li>';
echo '' . __('Click <a href="javascript: close_code_license_dialog();">here</a>, enter the generated license key and click on <b>Validate</b>.');
echo '</li>';
echo '</ul>';
echo '</div>';

?>
