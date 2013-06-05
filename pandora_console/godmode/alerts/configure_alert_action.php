<?php 

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
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

require_once ('include/functions_alerts.php');
require_once ('include/functions_users.php');

check_login ();

if (! check_acl ($config['id_user'], 0, "LM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Alert Management");
	require ("general/noaccess.php");
	exit;
}

$id = (int) get_parameter ('id');

$al_action = alerts_get_alert_action ($id);

if ($al_action !== false) {
		$own_info = get_user_info ($config['id_user']);
		if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM"))
			$own_groups = array_keys(users_get_groups($config['id_user'], "LM"));
		else
			$own_groups = array_keys(users_get_groups($config['id_user'], "LM", false));
		$is_in_group = in_array($al_action['id_group'], $own_groups);
		
		// Header
		ui_print_page_header (__('Alerts').' &raquo; '.__('Configure alert action'), "images/god2.png", false, "", true);
}
else {
	// Header
	ui_print_page_header (__('Alerts').' &raquo; '.__('Configure alert action'), "images/god2.png", false, "", true);	
}
$name = '';
$id_command = '';
$field1 = '';
$field2 = '';
$field3 = '';
$group = 0; //All group is 0
$action_threshold = 0; //All group is 0

if ($id) {
	$action = alerts_get_alert_action ($id);
	$name = $action['name'];
	$id_command = $action['id_alert_command'];
	$field1 = $action['field1'];
	$field2 = $action['field2'];
	$field3 = $action['field3'];
	$group = $action ['id_group'];
	$action_threshold = $action ['action_threshold'];
}

$table->width = '98%';
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->size = array ();
$table->size[0] = '20%';
$table->data = array ();
$table->data[0][0] = __('Name');
$table->data[0][1] = html_print_input_text ('name', $name, '', 35, 255, true);

$table->data[1][0] = __('Group');

$groups = users_get_groups ();
$own_info = get_user_info ($config['id_user']);
// Only display group "All" if user is administrator or has "PM" privileges
if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM"))
	$display_all_group = true;
else
	$display_all_group = false;
$table->data[1][1] = html_print_select_groups(false, "LW", $display_all_group, 'group', $group, '', '', 0, true);

$table->data[2][0] = __('Command');
$table->data[2][1] = html_print_select_from_sql ('SELECT id, name
	FROM talert_commands',
	'id_command', $id_command, '', __('None'), 0, true);
$table->data[2][1] .= ' ';
if (check_acl ($config['id_user'], 0, "PM")) {
	$table->data[2][1] .= html_print_image ('images/add.png', true);
	$table->data[2][1] .= '<a href="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_command">';
	$table->data[2][1] .= __('Create Command');
	$table->data[2][1] .= '</a>';
}
$table->data[3][0] = __('Threshold');
$table->data[3][1] = html_print_input_text ('action_threshold', $action_threshold, '', 5, 7, true);
$table->data[3][1] .= ' ' . __('seconds') . ui_print_help_icon ('action_threshold', true);
$table->data[4][0] = __('Field 1');
$table->data[4][1] = html_print_input_text ('field1', $field1, '', 35, 255, true) . ui_print_help_icon ('alert_macros', true);

$table->data[5][0] = __('Field 2');
$table->data[5][1] = html_print_input_text ('field2', $field2, '', 80, 255, true);

$table->data[6][0] = __('Field 3');
$table->data[6][1] = html_print_textarea ('field3', 10, 30, $field3, '', true);

$table->data[7][0] = __('Command preview');
$table->data[7][1] = html_print_textarea ('command_preview', 10, 30, '', 'disabled="disabled"', true);

echo '<form method="post" action="index.php?sec=galertas&sec2=godmode/alerts/alert_actions">';
html_print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
if ($id) {
	html_print_input_hidden ('id', $id);
	if ($al_action['id_group'] == 0) {
		// then must have "PM" access privileges
		if (check_acl ($config['id_user'], 0, "PM")) {
			html_print_input_hidden ('update_action', 1);
			html_print_submit_button (__('Update'), 'create', false, 'class="sub upd"');
		}
	}
}
else {
	html_print_input_hidden ('create_action', 1);
	html_print_submit_button (__('Create'), 'create', false, 'class="sub wand"');
}
echo '</div>';
echo '</form>';

ui_require_javascript_file ('pandora_alerts');
?>

<script type="text/javascript">
$(document).ready (function () {
	<?php
	if ($id_command) {
	?>
		original_command = "<?php
			$command = alerts_get_alert_command_command ($id_command);
			$command = io_safe_output($command);
			echo addslashes($command);
			?>";
		render_command_preview ();
	<?php
	}
	?>
	
	$("#id_command").change (function () {
		values = Array ();
		values.push ({name: "page",
			value: "godmode/alerts/alert_commands"});
		values.push ({name: "get_alert_command",
			value: "1"});
		values.push ({name: "id",
			value: this.value});
		jQuery.get ("ajax.php",
			values,
			function (data, status) {
				original_command = js_html_entity_decode (data["command"]);
				render_command_preview (original_command);
			},
			"json"
		);
	});
	
	$("#text-field1").keyup (render_command_preview);
	$("#text-field2").keyup (render_command_preview);
	$("#textarea_field3").keyup (render_command_preview);
});

</script>
