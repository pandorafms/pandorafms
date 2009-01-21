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

if (! give_acl ($config['id_user'], 0, "PM") || ! dame_admin ($config['id_user'])) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to access Setup Management");
	require ("general/noaccess.php");
	exit;
}

// Load enterprise extensions
if (file_exists( $config["homedir"] . "/enterprise/godmode/setup/setup.php")) {
	include $config["homedir"] . "/enterprise/godmode/setup/setup.php";
}

$update_settings = (bool) get_parameter ('update_settings');

if ($update_settings) {
	$config["block_size"] = (int) get_parameter ('block_size', $config["block_size"]);
	$config["language"] = (string) get_parameter ('language_code', $config["language"]);
	$config["days_compact"] = (int) get_parameter ('days_compact', $config["days_compact"]);
	$config["days_purge"] = (int) get_parameter ('days_purge', $config["days_purge"]);
	$config["graph_res"] = (int) get_parameter ('graph_res', $config["graph_res"]);
	$config["step_compact"] = (int) get_parameter ('step_compact', $config["step_compact"]);
	$config["style"] = (string) get_parameter ('style', $config["style"]);
	$config["remote_config"] = (string) get_parameter ('remote_config', $config["remote_config"]);
	$config["graph_color1"] = (string) get_parameter ('graph_color1', $config["graph_color1"]);
	$config["graph_color2"] = (string) get_parameter ('graph_color2', $config["graph_color2"]);
	$config["graph_color3"] = (string) get_parameter ('graph_color3', $config["graph_color3"]);	
	$config["sla_period"] = (int) get_parameter ('sla_period', $config["sla_period"]);
	$config["date_format"] = (string) get_parameter ('date_format', $config["date_format"]);
	$config["trap2agent"] = (string) get_parameter ('trap2agent', $config["trap2agent"]);
	$config["autoupdate"] = (string) get_parameter ('autoupdate', $config["autoupdate"]);
	$config["prominent_time"] = (string) get_parameter ('prominent_time', $config["prominent_time"]);
	$config["loginhash_pwd"] = (string) get_parameter ('loginhash_pwd', $config["loginhash_pwd"]);	
	
	$config["timesource"] = (string) get_parameter ('timesource', $config["timesource"]);
	$config["event_view_hr"] = (int) get_parameter ('event_view_hr', $config["event_view_hr"]);
	$config["style"] = substr ($config["style"], 0, strlen ($config["style"]) - 4);

	process_sql ("UPDATE tconfig SET VALUE='".$config["remote_config"]."' WHERE token = 'remote_config'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["block_size"]."' WHERE token = 'block_size'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["language"]."' WHERE token = 'language_code'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["days_purge"]."' WHERE token = 'days_purge'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["days_compact"]." ' WHERE token = 'days_compact'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["graph_res"]."' WHERE token = 'graph_res'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["step_compact"]."' WHERE token = 'step_compact'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["style"]."' WHERE token = 'style'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["graph_color1"]."' WHERE token = 'graph_color1'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["graph_color2"]."' WHERE token = 'graph_color2'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["graph_color3"]."' WHERE token = 'graph_color3'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["sla_period"]."' WHERE token = 'sla_period'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["date_format"]."' WHERE token = 'date_format'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["trap2agent"]."' WHERE token = 'trap2agent'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["autoupdate"]."' WHERE token = 'autoupdate'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["prominent_time"]."' WHERE token = 'prominent_time'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["timesource"]."' WHERE token = 'timesource'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["event_view_hr"]."' WHERE token = 'event_view_hr'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["loginhash_pwd"]."' WHERE token = 'loginhash_pwd'");
}

echo "<h2>".__('Setup')." &gt; ";
echo __('General configuration')."</h2>";

$file_styles = list_files('include/styles/', "pandora", 1, 0);

$table->width = '90%';
$table->data = array ();
$table->data[0][0] = __('Language code for Pandora');
$table->data[0][1] = print_select_from_sql ('SELECT id_language, name FROM tlanguage', 'language_code', $config["language"], '', '', '', true);

$table->data[1][0] = __('Date format string') . pandora_help("date_format", true);
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
$table->data[13][1] = print_select ($file_styles, 'style', $config["style"], '', '', '', true);

$table->data[14][0] = __('Block size for pagination');
$table->data[14][1] = print_input_text ('block_size', $config["block_size"], '', 5, 5, true);

$table->data[14][0] = __('Default hours for event view');
$table->data[14][1] = print_input_text ('event_view_hr', $config["event_view_hr"], '', 5, 5, true);

$table->data[15][0] = __('Timestamp or time comparation') . pandora_help("time_stamp-comparation", true);
$table->data[15][1] = __('Comparation in rollover').' ';
$table->data[15][1] .=  print_radio_button ('prominent_time', "timestamp", '', $config["prominent_time"], true);
$table->data[15][1] .= '<br />'.__('Timestamp in rollover').' ';
$table->data[15][1] .= print_radio_button ('prominent_time', "comparation", '', $config["prominent_time"], true);

$table->data[16][0] = __('Time source') . pandora_help("timesource", true);
$sources["system"] = __('System');
$sources["sql"] = __('Database');
$table->data[16][1] = print_select ($sources, 'timesource', $config["timesource"], '', '', '', true);

$table->data[17][0] = __('Automatic update check');
$table->data[17][1] = print_checkbox ('autoupdate', 1, $config["autoupdate"], true);

// 18
enterprise_hook ('load_snmpforward_enterprise');

echo '<form id="form_setup" method="POST" action="index.php?sec=gsetup&amp;sec2=godmode/setup/setup">';
print_input_hidden ('update_settings', 1);
print_table ($table);
echo '<div class="action-buttons" style="width: '.$table->width.'">';
print_submit_button (__('Update'), 'update_button', false, 'class="sub upd"');
echo '</div>';
echo '</form>';
?>

<link rel="stylesheet" href="include/styles/color-picker.css" type="text/css" />
<script type="text/javascript" src="include/javascript/jquery.colorpicker.js"></script>
<script language="javascript" type="text/javascript">
$(document).ready (function () {
	$("#form_setup #text-graph_color1").attachColorPicker();
	$("#form_setup #text-graph_color2").attachColorPicker();
	$("#form_setup #text-graph_color3").attachColorPicker();
});
</script>
