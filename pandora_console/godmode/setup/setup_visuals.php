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

if (! give_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to access Visual Setup Management");
	require ("general/noaccess.php");
	return;
}

// Load enterprise extensions
enterprise_include ('godmode/setup/setup_visuals.php');

/*
 NOTICE FOR DEVELOPERS:
 
 Update operation is done in config_process.php
 This is done in that way so the user can see the changes inmediatly.
 If you added a new token, please check update_config() in functions_config.php
 to add it there.
*/

require_once ('include/functions_themes.php');

echo "<h2>".__('Setup')." &gt; ";
echo __('Visual configuration')."</h2>";

$table->width = '90%';
$table->data = array ();

$table->data[1][0] = __('Date format string') . print_help_icon("date_format", true);
$table->data[1][1] = '<em>'.__('Example').'</em> '.date ($config["date_format"]);
$table->data[1][1] .= print_input_text ('date_format', $config["date_format"], '', 30, 100, true);

$table->data[2][0] = __('Graph color (min)');
$table->data[2][1] = print_input_text ('graph_color1', $config["graph_color1"], '', 8, 8, true);

$table->data[3][0] = __('Graph color (avg)');
$table->data[3][1] = print_input_text ('graph_color2', $config["graph_color2"], '', 8, 8, true);

$table->data[4][0] = __('Graph color (max)');
$table->data[4][1] = print_input_text ('graph_color3', $config["graph_color3"], '', 8, 8, true);

$table->data[5][0] = __('Graphic resolution (1-low, 5-high)');
$table->data[5][1] = print_input_text ('graph_res', $config["graph_res"], '', 5, 5, true);

$table->data[6][0] = __('Style template');
$table->data[6][1] = print_select (get_css_themes (), 'style', $config["style"].'.css', '', '', '', true);

$table->data[7][0] = __('Block size for pagination');
$table->data[7][1] = print_input_text ('block_size', $config["block_size"], '', 5, 5, true);

$table->data[8][0] = __('Use round corners');
$table->data[8][1] = print_checkbox ('round_corner', 1, $config["round_corner"], true);

echo '<form id="form_setup" method="POST" action="index.php?sec=gsetup&amp;sec2=godmode/setup/setup_visuals">';
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
