<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
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

require_once ('include/config.php');

if (! give_acl ($config['id_user'], 0, "PM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Agent Management");
	require ("general/noaccess.php");
	return;
}

function push_table_row ($row, $id = false) {
	global $table;
	
	if ($id)
		$data = array ($id => $row);
	else
		$data = array ($row);
	
	$table->data = array_merge ($table->data, $data);
}


$table->id = 'network_component';
$table->width = '90%';
$table->class = 'databox_color';
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->style[2] = 'font-weight: bold';
$table->colspan = array ();
$table->colspan[0][1] = 3;
$table->data = array ();

$table->data[0][0] = __('Name');
$table->data[0][1] = print_input_text ('name', $name, '', 55, 255, true);

$table->data[1][0] = __('Type').' '.print_help_icon ('module_type', true);
$sql = sprintf ('SELECT id_tipo, descripcion
	FROM ttipo_modulo
	WHERE categoria IN (%s)
	ORDER BY descripcion',
	implode (',', $categories));
$table->data[1][1] = print_select_from_sql ($sql, 'type',
	$type, ($id_component_type == 2 ? 'type_change()' : ''), '', '', true,
	false, false);

$table->data[1][2] = __('Module group');
$table->data[1][3] = print_select_from_sql ('SELECT id_mg, name FROM tmodule_group ORDER BY name',
	'id_module_group', $id_module_group, '', '', '', true, false, false);

$table->data[2][0] = __('Group');
$table->data[2][1] = print_select (get_network_component_groups (),
	'id_group', $id_group, '', '', '', true, false, false);
$table->data[2][2] = __('Interval');
$table->data[2][3] = print_input_text ('module_interval', $module_interval, '',
	5, 15, true);

$table->data[3][0] = __('Warning status');
$table->data[3][1] = '<em>'.__('Min.').'</em>';
$table->data[3][1] .= print_input_text ('min_warning', $min_warning,
	'', 5, 15, true);
$table->data[3][1] .= '<br /><em>'.__('Max.').'</em>';
$table->data[3][1] .= print_input_text ('max_warning', $max_warning,
	'', 5, 15, true);
$table->data[3][2] = __('Critical status');
$table->data[3][3] = '<em>'.__('Min.').'</em>';
$table->data[3][3] .= print_input_text ('min_critical', $min_critical,
	'', 5, 15, true);
$table->data[3][3] .= '<br /><em>'.__('Max.').'</em>';
$table->data[3][3] .= print_input_text ('max_critical', $max_critical,
	'', 5, 15, true);

$table->data[4][0] = __('FF threshold').' '.print_help_icon ('ff_threshold', true);
$table->data[4][1] = print_input_text ('ff_event', $ff_event,
	'', 5, 15, true);
$table->data[4][2] = __('Historical data');
$table->data[4][3] = print_checkbox ("history_data", 1, $history_data, true);

$table->data[5][0] = __('Min. Value');
$table->data[5][1] = print_input_text ('min', $min, '', 5, 15, true);
$table->data[5][2] = __('Max. Value');
$table->data[5][3] = print_input_text ('max', $max, '', 5, 15, true);
?>
