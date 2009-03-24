<?php 

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnológicas, http://www.artica.es
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

// Load global vars
require_once ("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "PM") || ! is_user_admin ($config['id_user'])) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to access Setup Management");
	require ("general/noaccess.php");
	exit;
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

require_once ('include/functions_themes.php');

echo "<h2>".__('Setup')." &gt; ";
echo __('General configuration')."</h2>";

$table->width = '90%';
$table->data = array ();
$table->data[0][0] = __('Language code for Pandora');
$table->data[0][1] = print_select_from_sql ('SELECT id_language, name FROM tlanguage',
	'language', $config["language"], '', '', '', true);

$table->data[1][0] = __('Date format string') . print_help_icon("date_format", true);
$table->data[1][1] = '<em>'.__('Example').'</em> '.date ($config["date_format"]);
$table->data[1][1] .= print_input_text ('date_format', $config["date_format"], '', 30, 100, true);

$table->data[2][0] = __('Remote config directory');
$table->data[2][1] = print_input_text ('remote_config', $config["remote_config"], '', 30, 100, true);

$table->data[3][0] = __('Graph color (min)');
$table->data[3][1] = print_input_text ('graph_color1', $config["graph_color1"], '', 8, 8, true);

$table->data[4][0] = __('Graph color (avg)');
$table->data[4][1] = print_input_text ('graph_color2', $config["graph_color2"], '', 8, 8, true);

$table->data[5][0] = __('Graph color (max)');
$table->data[5][1] = print_input_text ('graph_color3', $config["graph_color3"], '', 8, 8, true);

$table->data[6][0] = __('SLA period (seconds)');
$table->data[6][1] = print_input_text ('sla_period', $config["sla_period"], '', 8, 8, true);

$table->data[7][0] = __('Max. days before compact data');
$table->data[7][1] = print_input_text ('days_compact', $config["days_compact"], '', 5, 5, true);

$table->data[8][0] = __('Max. days before purge');
$table->data[8][1] = print_input_text ('days_purge', $config["days_purge"], '', 5, 5, true);

$table->data[9][0] = __('Graphic resolution (1-low, 5-high)');
$table->data[9][1] = print_input_text ('graph_res', $config["graph_res"], '', 5, 5, true);

$table->data[10][0] = __('Compact interpolation in hours (1 Fine-20 bad)');
$table->data[10][1] = print_input_text ('step_compact', $config["step_compact"], '', 5, 5, true);

$table->data[11][0] = __('Auto login (Hash) password');
$table->data[11][1] = print_input_text ('loginhash_pwd', $config["loginhash_pwd"], '', 15, 15, true);

$table->data[13][0] = __('Style template');
$table->data[13][1] = print_select (get_css_themes (), 'style', $config["style"].'.css', '', '', '', true);

$table->data[14][0] = __('Block size for pagination');
$table->data[14][1] = print_input_text ('block_size', $config["block_size"], '', 5, 5, true);

$table->data[15][0] = __('Default hours for event view');
$table->data[15][1] = print_input_text ('event_view_hr', $config["event_view_hr"], '', 5, 5, true);

$table->data[16][0] = __('Timestamp or time comparation') . print_help_icon ("time_stamp-comparation", true);
$table->data[16][1] = __('Comparation in rollover').' ';
$table->data[16][1] .=  print_radio_button ('prominent_time', "timestamp", '', $config["prominent_time"], true);
$table->data[16][1] .= '<br />'.__('Timestamp in rollover').' ';
$table->data[16][1] .= print_radio_button ('prominent_time', "comparation", '', $config["prominent_time"], true);

$table->data[17][0] = __('Time source') . print_help_icon ("timesource", true);
$sources["system"] = __('System');
$sources["sql"] = __('Database');
$table->data[17][1] = print_select ($sources, 'timesource', $config["timesource"], '', '', '', true);

$table->data[18][0] = __('Automatic update check');
$table->data[18][1] = print_checkbox ('autoupdate', 1, $config["autoupdate"], true);

$table->data[19][0] = __('Enforce https');
$table->data[19][1] = print_checkbox ('https', 1, $config["https"], true);

$table->data[20][0] = __('Compact CSS and JS into header');
$table->data[20][1] = print_checkbox ('compact_header', 1, $config["compact_header"], true);

enterprise_hook ('load_snmpforward_enterprise');

echo '<form id="form_setup" method="POST" action="index.php?sec=gsetup&amp;sec2=godmode/setup/setup">';
print_input_hidden ('update_config', 1);
print_table ($table);
echo '<div class="action-buttons" style="width: '.$table->width.'">';
print_submit_button (__('Update'), 'update_button', false, 'class="sub upd"');
echo '</div>';
echo '</form>';

require_css_file ("color-picker");
require_jquery_file ("colorpicker");
?>
<script language="javascript" type="text/javascript">
$(document).ready (function () {
	$("#form_setup #text-graph_color1").attachColorPicker();
	$("#form_setup #text-graph_color2").attachColorPicker();
	$("#form_setup #text-graph_color3").attachColorPicker();
});
</script>
