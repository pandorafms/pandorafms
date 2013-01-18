<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.


global $config;

//include_once("include/functions_graph.php");
include_once("include/functions_ui.php");
//ui_require_javascript_file ('calendar');

check_login ();

if (! check_acl ($config["id_user"], 0, "IR")) {
	db_pandora_audit("ACL Violation",
		"Trying to access netflow setup");
	require ("general/noaccess.php");
	return;
}

$update = (bool) get_parameter ("update");

$table->width = '70%';
$table->border = 0;
$table->cellspacing = 3;
$table->cellpadding = 5;
$table->class = "databox_color";
$table->style[0] = 'vertical-align: top;';

$table->data = array ();
	
$table->data[0][0] = '<b>'.__('Data storage path').'</b>'. ui_print_help_tip (__("Directory where netflow data will be stored."), true);
$table->data[0][1] = html_print_input_text ('netflow_path', $config['netflow_path'], false, 50, 200, true);
$table->data[1][0] = '<b>'.__('Daemon interval').'</b>';
$table->data[1][1] = html_print_input_text ('netflow_interval', $config['netflow_interval'], false, 50, 200, true);
$table->data[2][0] = '<b>'.__('Daemon binary path').'</b>';
$table->data[2][1] = html_print_input_text ('netflow_daemon', $config['netflow_daemon'], false, 50, 200, true);
$table->data[3][0] = '<b>'.__('Nfdump binary path').'</b>';
$table->data[3][1] = html_print_input_text ('netflow_nfdump', $config['netflow_nfdump'], false, 50, 200, true);
$table->data[4][0] = '<b>'.__('Maximum chart resolution').'</b>' . ui_print_help_tip (__("Maximum number of points that a netflow area chart will display. The higher the resolution the performance. Values between 50 and 100 are recommended."), true);
$table->data[4][1] = html_print_input_text ('netflow_max_resolution', $config['netflow_max_resolution'], false, 50, 200, true);

echo '<form id="netflow_setup" method="post">';
			
html_print_table ($table);

// Update button
echo '<div class="action-buttons" style="width:70%;">';
	html_print_input_hidden ('update_config', 1);
	html_print_submit_button (__('Update'), 'upd_button', false, 'class="sub upd"');
echo '</div></form>';
	
?>

<script type="text/javascript">

	
$(document).ready (function () {
	$("textarea").TextAreaResizer ();
});

</script>
