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
require("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "PM") || ! dame_admin ($config['id_user'])) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to access Setup Management");
	require ("general/noaccess.php");
	exit;
}

$update_settings = (bool) get_parameter ('update_settings');

if ($update_settings) {
	$config["block_size"] = (int) get_parameter ('block_size');
	$config["language"] = (string) get_parameter ('language_code');
	$config["days_compact"] = (int) get_parameter ('days_compact');
	$config["days_purge"] = (int) get_parameter ('days_purge');
	$config["graph_res"] = (int) get_parameter ('graph_res');
	$config["step_compact"] = (int) get_parameter ('step_compact');
	$config["show_unknown"] = (int) get_parameter ('show_unknown');
	$config["show_lastalerts"] = (int) get_parameter ('show_lastalerts');
	$config["style"] = (string) get_parameter ('style', 'pandora.css');
	$config["remote_config"] = (string) get_parameter ('remote_config');
	$config["graph_color1"] = (string) get_parameter ('graph_color1');
	$config["graph_color2"] = (string) get_parameter ('graph_color2');
	$config["graph_color3"] = (string) get_parameter ('graph_color3');	
	$config["sla_period"] = (int) get_parameter ("sla_period");
	$config["date_format"] = (string) get_parameter ("date_format");
	$config["style"] = substr ($config["style"], 0, strlen ($config["style"]) - 4);

	process_sql ("UPDATE tconfig SET VALUE='".$config["remote_config"]."' WHERE token = 'remote_config'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["block_size"]."' WHERE token = 'block_size'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["language"]."' WHERE token = 'language_code'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["days_purge"]."' WHERE token = 'days_purge'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["days_compact"]." ' WHERE token = 'days_compact'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["graph_res"]."' WHERE token = 'graph_res'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["step_compact"]."' WHERE token = 'step_compact'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["show_unknown"]."' WHERE token = 'show_unknown'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["show_lastalerts"]."' WHERE token = 'show_lastalerts'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["style"]."' WHERE token = 'style'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["graph_color1"]."' WHERE token = 'graph_color1'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["graph_color2"]."' WHERE token = 'graph_color2'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["graph_color3"]."' WHERE token = 'graph_color3'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["sla_period"]."' WHERE token = 'sla_period'");
	process_sql ("UPDATE tconfig SET VALUE='".$config["date_format"]."' WHERE token = 'date_format'");
}

echo "<h2>".__('Pandora Setup')." &gt; ";
echo __('General configuration')."</h2>";

$file_styles = list_files('include/styles/', "pandora", 1, 0);

$table->width = '500px';
$table->data = array ();
$table->data[0][0] = __('Language Code for Pandora');
$table->data[0][1] = print_select_from_sql ('SELECT id_language, name FROM tlanguage', 'language_code', $config["language"], '', '', '', true);

$table->data[1][0] = __('Date format string') . pandora_help("date_format", true);
$table->data[1][1] = print_input_text ('date_format', $config["date_format"], '', 30, 100, true);

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

$table->data[11][0] = __('Show unknown modules in global view');
$table->data[11][1] = print_checkbox ('show_unknown', 1, $config["show_unknown"], true);

$table->data[12][0] = __('Show last fired alerts in global view');
$table->data[12][1] = print_checkbox ('show_lastalerts', 1, $config["show_lastalerts"], true);

$table->data[13][0] = __('Style template');
$table->data[13][1] = print_select ($file_styles, 'style', $config["style"], '', '', '', true);

$table->data[14][0] = __('Block size for pagination');
$table->data[14][1] = print_input_text ('block_size', $config["block_size"], '', 5, 5, true);

echo '<form id="form_setup" method="POST" action="index.php?sec=gsetup&amp;sec2=godmode/setup/setup">';
print_input_hidden ('update_settings', 1);
print_table ($table);
echo '<div class="action-buttons" style="width: '.$table->width.'">';
print_submit_button (__('Update'), 'update_button', false, 'class="sub upd"');
echo '</div>';
echo '</form>';
?>

<link rel="stylesheet" href="include/styles/color-picker.css" type="text/css" />
<script type="text/javascript" src="include/javascript/jquery.js"></script>
<script type="text/javascript" src="include/javascript/jquery.colorpicker.js"></script>
<script language="javascript" type="text/javascript">
$(document).ready (function () {
	$("#form_setup #text-graph_color1").attachColorPicker();
	$("#form_setup #text-graph_color2").attachColorPicker();
	$("#form_setup #text-graph_color3").attachColorPicker();
});
</script>
