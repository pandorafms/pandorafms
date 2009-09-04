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

// Load global variables
require_once ('include/config.php');

check_login ();

echo "<h2>".__('Module management')." &raquo; ";
echo __('Module component management')."</h2>";
echo "<h3>".__('Plugin component').'</h3>';

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
$data[0] .= print_help_icon ('plugin_parameters', true);
$data[1] = print_input_text ('plugin_parameter', $plugin_parameter, '', 30, 60, true);
$table->colspan['plugin_3'][1] = 3;

push_table_row ($data, 'plugin_3');

?>

