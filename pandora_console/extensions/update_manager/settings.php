<?php
//Pandora FMS- http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Load global vars
require_once ("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, 'PM')) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to use Open Update Manager extension");
	include ("general/noaccess.php");
	return;
}

$db =& um_db_connect ('mysql', $config['dbhost'], $config['dbuser'],
			$config['dbpass'], $config['dbname']);

$update_settings = (bool) get_parameter_post ('update_settings');

if ($update_settings) {
	foreach ($_POST['keys'] as $key => $value) {
		um_db_update_setting ($key, $value);
	}
	echo "<h3 class=suc>".__('Update manager settings updated')."</h3>";
}

$settings = um_db_load_settings ();

echo '<h3>'.__('Settings').'</h3>';
echo '<form method="post">';

$table->width = '95%';
$table->data = array ();

$table->data[0][0] = '<strong>'.__('Customer key').'</strong>';
$table->data[0][1] = print_input_text ('keys[customer_key]', $settings->customer_key, '', 40, 255, true);

$table->data[1][0] = '<strong>'.__('Update server host').'</strong>';
$table->data[1][1] = print_input_text ('keys[update_server_host]', $settings->update_server_host, '', 20, 255, true);

$table->data[2][0] = '<strong>'.__('Update server path').'</strong>';
$table->data[2][1] = print_input_text ('keys[update_server_path]', $settings->update_server_path, '', 40, 255, true);

$table->data[3][0] = '<strong>'.__('Update server port').'</strong>';
$table->data[3][1] = print_input_text ('keys[update_server_port]', $settings->update_server_port, '', 5, 5, true);

$table->data[4][0] = '<strong>'.__('Binary input path').'</strong>';
$table->data[4][1] = print_input_text ('keys[updating_binary_path]', $settings->updating_binary_path, '', 40, 255, true);

$table->data[5][0] = '<strong>'.__('Keygen path').'</strong>';
$table->data[5][1] = print_input_text ('keys[keygen_path]', $settings->keygen_path, '', 40, 255, true);

$table->data[6][0] = '<strong>'.__('Proxy server').'</strong>';
$table->data[6][1] = print_input_text ('keys[proxy]', $settings->proxy, '', 40, 255, true);

$table->data[7][0] = '<strong>'.__('Proxy port').'</strong>';
$table->data[7][1] = print_input_text ('keys[proxy_port]', $settings->proxy_port, '', 40, 255, true);

$table->data[8][0] = '<strong>'.__('Proxy user').'</strong>';
$table->data[8][1] = print_input_text ('keys[proxy_user]', $settings->proxy_user, '', 40, 255, true);

$table->data[9][0] = '<strong>'.__('Proxy password').'</strong>';
$table->data[9][1] = print_input_password ('keys[proxy_pass]', $settings->proxy_pass, '', 40, 255, true);

print_table ($table);
echo '<div class="action-buttons" style="width: '.$table->width.'">';
print_input_hidden ('update_settings', 1);
print_submit_button (__('Update'), 'update_button', false, 'class="sub upd"');
echo '</div>';
echo '</form>';

?>
