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
print_page_header (__('Performance  configuration'), "", false, "", true);

$table->width = '90%';
$table->data = array ();


$table->data[1][0] = __('Max. days before delete events');
$table->data[1][1] = print_input_text ('event_purge', $config["event_purge"], '', 5, 5, true);

$table->data[2][0] = __('Max. days before delete traps');
$table->data[2][1] = print_input_text ('trap_purge', $config["trap_purge"], '', 5, 5, true);

$table->data[3][0] = __('Max. days before delete audit events');
$table->data[3][1] = print_input_text ('audit_purge', $config["audit_purge"], '', 5, 5, true);

$table->data[4][0] = __('Max. days before delete string data');
$table->data[4][1] = print_input_text ('string_purge', $config["string_purge"], '', 5, 5, true);

$table->data[5][0] = __('Max. days before delete GIS data');
$table->data[5][1] = print_input_text ('gis_purge', $config["gis_purge"], '', 5, 5, true);

$table->data[6][0] = __('Max. days before purge');
$table->data[6][1] = print_input_text ('days_purge', $config["days_purge"], '', 5, 5, true);

$table->data[7][0] = __('Max. days before compact data');
$table->data[7][1] = print_input_text ('days_compact', $config["days_compact"], '', 5, 5, true);

$table->data[8][0] = __('Compact interpolation in hours (1 Fine-20 bad)');
$table->data[8][1] = print_input_text ('step_compact', $config["step_compact"], '', 5, 5, true);

$table->data[9][0] = __('SLA period (seconds)');
$table->data[9][1] = print_input_text ('sla_period', $config["sla_period"], '', 8, 8, true);

$table->data[10][0] = __('Default hours for event view');
$table->data[10][1] = print_input_text ('event_view_hr', $config["event_view_hr"], '', 5, 5, true);

$table->data[11][0] = __('Compact CSS and JS into header');
$table->data[11][1] = __('Yes').'&nbsp;'.print_radio_button ('compact_header', 1, '', $config["compact_header"], true).'&nbsp;&nbsp;';
$table->data[11][1] .= __('No').'&nbsp;'.print_radio_button ('compact_header', 0, '', $config["compact_header"], true);

$table->data[12][0] = __('Use realtime statistics');
$table->data[12][1] = __('Yes').'&nbsp;'.print_radio_button ('realtimestats', 1, '', $config["realtimestats"], true).'&nbsp;&nbsp;';
$table->data[12][1] .= __('No').'&nbsp;'.print_radio_button ('realtimestats', 0, '', $config["realtimestats"], true);

$table->data[13][0] = __('Batch statistics period (secs)');
$table->data[13][1] = print_input_text ('stats_interval', $config["stats_interval"], '', 5, 5, true);

$table->data[14][0] = __('Use agent access graph'). print_help_icon("agent_access", true);
$table->data[14][1] = __('Yes').'&nbsp;'.print_radio_button ('agentaccess', 1, '', $config["agentaccess"], true).'&nbsp;&nbsp;';
$table->data[14][1] .= __('No').'&nbsp;'.print_radio_button ('agentaccess', 0, '', $config["agentaccess"], true);

echo '<form id="form_setup" method="post">';
print_input_hidden ('update_config', 1);
print_table ($table);
echo '<div class="action-buttons" style="width: '.$table->width.'">';
print_submit_button (__('Update'), 'update_button', false, 'class="sub upd"');
echo '</div>';
echo '</form>';
?>


