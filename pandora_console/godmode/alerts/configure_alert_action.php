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
require_once ('include/config.php');
require_once ('include/functions_alerts.php');

check_login ();

if (! give_acl ($config['id_user'], 0, "LM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Alert Management");
	require ("general/noaccess.php");
	exit;
}

$id = (int) get_parameter ('id');

$name = '';
$id_command = '';
$field1 = '';
$field2 = '';
$field3 = '';
if ($id) {
	$action = get_alert_action ($id);
	$name = $action['name'];
	$id_command = $action['id_alert_command'];
	$field1 = $action['field1'];
	$field2 = $action['field2'];
	$field3 = $action['field3'];
}

echo '<h1>'.__('Configure alert action').'</h1>';

$table->width = '90%';
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->size = array ();
$table->size[0] = '20%';
$table->data = array ();
$table->data[0][0] = __('Name');
$table->data[0][1] = print_input_text ('name', $name, '', 35, 255, true);

$table->data[1][0] = __('Command');
$table->data[1][1] = print_select_from_sql ('SELECT id, name FROM talert_commands',
	'id_command', $id_command, '', __('None'), 0, true);

$table->data[2][0] = __('Field 1');
$table->data[2][1] = print_input_text ('field1', $field1, '', 35, 255, true);

$table->data[3][0] = __('Field 2');
$table->data[3][1] = print_input_text ('field2', $field2, '', 35, 255, true);

$table->data[4][0] = __('Field 3');
$table->data[4][1] = print_textarea ('field3', 10, 30, $field3, '', true);

$table->data[6][0] = __('Command preview');
$table->data[6][1] = print_textarea ('command_preview', 10, 30, '', 'disabled="disabled"', true);

echo '<form method="post" action="index.php?sec=galertas&sec2=godmode/alerts/alert_actions">';
print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
if ($id) {
	print_input_hidden ('id', $id);
	print_input_hidden ('update_action', 1);
	print_submit_button (__('Update'), 'create', false, 'class="sub upd"');
} else {
	print_input_hidden ('create_action', 1);
	print_submit_button (__('Create'), 'create', false, 'class="sub next"');
}
echo '</div>';
echo '</form>';

require_javascript_file ('pandora_alerts');
?>

<script type="text/javascript">
$(document).ready (function () {
<?php if ($id_command) : ?>
	original_command = "<?php echo get_alert_command_command ($id_command); ?>";
	render_command_preview ();
<?php endif; ?>
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
				original_command = html_entity_decode (data["command"]);
				render_command_preview (original_command);
			},
			"json"
		);
	});
	
	$("#text-field1").keyup (render_command_preview);
	$("#text-field2").keyup (render_command_preview);
	$("#text-field3").keyup (render_command_preview);
});
</script>
