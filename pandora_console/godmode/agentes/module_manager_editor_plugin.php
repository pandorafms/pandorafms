<?php

// Pandora FMS - the Free Monitoring System
// ========================================
// Copyright (c) 2008 Artica Soluciones Tecnológicas, http://www.artica.es
// Copyright (c) 2008 Jorge Gonzalez <jorge.gonzalez@artica.es>
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.


// General startup for established session
if (!isset ($id_agente)) {
	die ("Not Authorized");
}

$extra_title = __('Plugin server module');

$data = array ();
$data[0] = __('Plugin');
$data[1] = print_select_from_sql ('SELECT id, name FROM tplugin ORDER BY name',
	'id_plugin', $id_plugin, '', __('None'), 0, true, false, false);
$table_simple->colspan['plugin_1'][1] = 3;

push_table_simple ($data, 'plugin_1');

$data = array ();
$data[0] = __('Target IP');
$data[1] = print_input_text ('ip_target', $ip_target, '', 15, 60, true);
$data[2] = _('Port');
$data[3] = print_input_text ('tcp_port', $tcp_port, '', 5, 20, true);

push_table_simple ($data, 'target_ip');

$data = array ();
$data[0] = __('Username');
$data[1] = print_input_text ('plugin_user', $plugin_user, '', 15, 60, true);
$data[2] = _('Password');
$data[3] = print_input_password ('plugin_pass', $plugin_pass, '', 15, 60, true);

push_table_simple ($data, 'plugin_2');

$data = array ();
$data[0] = __('Plugin parameters');
$data[0] .= pandora_help ('plugin_parameters', true);
$data[1] = print_input_text ('plugin_parameter', $plugin_parameter, '', 30, 60, true);
$table_simple->colspan['plugin_3'][1] = 3;

push_table_simple ($data, 'plugin_3');
?>
