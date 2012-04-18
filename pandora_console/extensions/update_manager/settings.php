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
	db_pandora_audit("ACL Violation", "Trying to use Open Update Manager extension");
	include ("general/noaccess.php");
	return;
}

include_once ("extensions/update_manager/lib/functions.php");

um_db_connect ('mysql', $config['dbhost'], $config['dbuser'],
			$config['dbpass'], $config['dbname']);

$update_settings = (bool) get_parameter_post ('update_settings');

$buttons = array(
	'admin' => array(
	'active' => false,
	'text' => '<a href="index.php?sec=extensions&sec2=extensions/update_manager">' . 
		html_print_image ("images/eye.png",
			true, array ("title" => __('Update manager'))) .'</a>'));

ui_print_page_header (__('Update manager').' - '. __('Settings'),
	"images/extensions.png", false, "", true, $buttons);

if ($update_settings) {
	foreach ($_POST['keys'] as $key => $value) {
		um_db_update_setting ($key, $value);
	}
	
	if (!enterprise_installed()) {
		global $conf_update_pandora;
		if (empty($conf_update_pandora))
			$conf_update_pandora = update_pandora_get_conf();
		
		$conf_update_pandora['download_mode'] =
			get_parameter('download_mode', 'curl');
		
		update_pandora_update_conf();
	}
	
	echo "<h3 class=suc>".__('Update manager settings updated')."</h3>";
}

$settings = um_db_load_settings ();

echo '<form method="post">';

$table->width = '95%';
$table->data = array ();

$table->data[0][0] = '<strong>'.__('Customer key').'</strong>';
$table->data[0][1] = html_print_input_text ('keys[customer_key]', $settings->customer_key, '', 40, 255, true);

$table->data[0][1] .= '&nbsp;<a id="dialog_license_info" title="'.__("License Info").'" href="#">'.html_print_image('images/lock.png', true, array('class' => 'bot', 'title' => __('License info'))).'</a>';
$table->data[0][1] .= '<div id="dialog_show_license" style="display:none"></div>';

$table->data[1][0] = '<strong>'.__('Update server host').'</strong>';
$table->data[1][1] = html_print_input_text ('keys[update_server_host]', $settings->update_server_host, '', 20, 255, true);

$table->data[2][0] = '<strong>'.__('Update server path').'</strong>';
$table->data[2][1] = html_print_input_text ('keys[update_server_path]', $settings->update_server_path, '', 40, 255, true);

$table->data[3][0] = '<strong>'.__('Update server port').'</strong>';
$table->data[3][1] = html_print_input_text ('keys[update_server_port]', $settings->update_server_port, '', 5, 5, true);

$table->data[4][0] = '<strong>'.__('Binary input path').'</strong>';
$table->data[4][1] = html_print_input_text ('keys[updating_binary_path]', $settings->updating_binary_path, '', 40, 255, true);

$table->data[5][0] = '<strong>'.__('Keygen path').'</strong>';
$table->data[5][1] = html_print_input_text ('keys[keygen_path]', $settings->keygen_path, '', 40, 255, true);

$table->data[6][0] = '<strong>'.__('Proxy server').'</strong>';
$table->data[6][1] = html_print_input_text ('keys[proxy]', $settings->proxy, '', 40, 255, true);

$table->data[7][0] = '<strong>'.__('Proxy port').'</strong>';
$table->data[7][1] = html_print_input_text ('keys[proxy_port]', $settings->proxy_port, '', 40, 255, true);

$table->data[8][0] = '<strong>'.__('Proxy user').'</strong>';
$table->data[8][1] = html_print_input_text ('keys[proxy_user]', $settings->proxy_user, '', 40, 255, true);

$table->data[9][0] = '<strong>'.__('Proxy password').'</strong>';
$table->data[9][1] = html_print_input_password ('keys[proxy_pass]', $settings->proxy_pass, '', 40, 255, true);

if (!enterprise_installed()) {
	global $conf_update_pandora;
	if (empty($conf_update_pandora))
		$conf_update_pandora = update_pandora_get_conf();
	
	$methods = array(
		'wget' => __('WGET, no interactive, external command, fast'),
		'curl' =>__('CURL, interactive, internal command, slow'));
	
	$table->data[10][0] = '<strong>' . __('Download Method') . '</strong>';
	$table->data[10][1] = html_print_select($methods,
		'download_mode', $conf_update_pandora['download_mode'], '', '',
		0, true);
}

html_print_table ($table);
echo '<div class="action-buttons" style="width: '.$table->width.'">';
html_print_input_hidden ('update_settings', 1);
html_print_submit_button (__('Update'), 'update_button', false, 'class="sub upd"');
echo '</div>';
echo '</form>';

?>
