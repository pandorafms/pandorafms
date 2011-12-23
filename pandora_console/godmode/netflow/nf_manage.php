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
		"Trying to access event viewer");
	require ("general/noaccess.php");
	return;
}

//Header
ui_print_page_header (__('Netflow Manager'), "images/networkmap/so_cisco_new.png", false, "", true);

$update = (bool) get_parameter ("update");

if ($update) {
	
	$config['netflow_path'] = (string)get_parameter('netflow_path');
	
	if (db_get_value('token', 'tconfig', 'token', 'netflow_path') === false) {
		config_create_value('netflow_path', $config['netflow_path']);
	} else {
		db_process_sql_update ('tconfig', 
				array ('value' => $config['netflow_path']),
				array ('token' => 'netflow_path'));
	}
}

$table->width = '70%';
$table->border = 0;
$table->cellspacing = 3;
$table->cellpadding = 5;
$table->class = "databox_color";
$table->style[0] = 'vertical-align: top;';

$table->data = array ();
	
$table->data[0][0] = '<b>'.__('Path').'</b>'. ui_print_help_tip (__("Read input from a sequence of files in the same directory."), true);
$table->data[0][1] = html_print_input_text ('netflow_path', $config['netflow_path'], false, 50, 200, true);
	
echo '<form id="netflow_setup" method="post">';
			
html_print_table ($table);

// Update button
echo '<div class="action-buttons" style="width:70%;">';
	html_print_input_hidden ('update', 1);
	html_print_submit_button (__('Update'), 'upd_button', false, 'class="sub upd"');
echo '</div></form>';
	
?>

<script type="text/javascript">

	
$(document).ready (function () {
	$("textarea").TextAreaResizer ();
});

</script>
