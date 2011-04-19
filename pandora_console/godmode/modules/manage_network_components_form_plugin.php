<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Load global variables
global $config;

check_login ();

echo "<h3>".__('Plugin component').'</h3>';

$data = array ();
$data[0] = __('Port');
$data[1] = print_input_text ('tcp_port', $tcp_port, '', 5, 20, true);
$table->colspan['plugin_0'][1] = 3;

push_table_row ($data, 'plugin_0');

$data = array ();
$data[0] = __('Plugin');
$data[1] = print_select_from_sql ('SELECT id, name FROM tplugin ORDER BY name',
	'id_plugin', $id_plugin, '', __('None'), 0, true, false, false);
$table->colspan['plugin_1'][1] = 3;

push_table_row ($data, 'plugin_1');

$data = array ();
$data[0] = __('Username');
$data[1] = print_input_text ('plugin_user', $plugin_user, '', 15, 60, true);
$data[2] = _('Password');
$data[3] = print_input_password ('plugin_pass', $plugin_pass, '', 15, 60, true);

push_table_row ($data, 'plugin_2');

$data = array ();
$data[0] = __('Plugin parameters');
$data[0] .= ui_print_help_icon ('plugin_parameters', true);
$data[1] = print_input_text ('plugin_parameter', $plugin_parameter, '', 30, 255, true);
$data[2] = __('Post process') . ' ' . ui_print_help_icon ('postprocess', true);
$data[3] = print_input_text ('post_process', $post_process, '', 12, 25, true);

push_table_row ($data, 'plugin_3');

?>

