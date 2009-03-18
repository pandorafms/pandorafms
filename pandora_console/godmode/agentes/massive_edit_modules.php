<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributepd in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Load global vars
check_login ();

if (! give_acl ($config['id_user'], 0, "LM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Agent Config Management Admin section");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_modules.php');

echo '<h3>'.__('Massive alerts deletion').'</h3>';

function process_manage_edit ($module_name) {
	if (is_int ($module_name) && $module_name <= 0) {
		echo '<h3 class="error">'.__('No modules selected').'</h3>';
		return false;
	}
	
	$agents = array_keys (get_group_agents (array_keys (get_user_groups ()), false, "none"));
	
	/* List of fields which can be updated */
	$fields = array ('min_warning', 'max_warning', 'min_critical', 'max_critical', 'ff_event');
	$values = array ();
	foreach ($fields as $field) {
		$value = get_parameter ($field);
		if ($value != '')
			$values[$field] = $value;
	}
	
	
	$modules = get_db_all_rows_filter ('tagente_modulo',
		array ('id_agente' => $agents,
			'nombre' => $module_name),
		array ('id_agente_modulo'));
	
	process_sql ('SET AUTOCOMMIT = 0');
	process_sql ('START TRANSACTION');
	if ($modules === false)
		return false;
	foreach ($modules as $module) {
		update_agent_module ($module['id_agente_modulo'], $values);
	}
	
	echo '<h3 class="suc">'.__('Successfully updated').'</h3>';
	process_sql ('COMMIT');
	process_sql ('SET AUTOCOMMIT = 1');
}

$module_name = (string) get_parameter ('module_name');

$update = (bool) get_parameter_post ('update');

if ($update) {
	process_manage_edit ($module_name);
}


$table->id = 'delete_table';
$table->width = '95%';
$table->data = array ();
$table->style = array ();
$table->style[0] = 'font-weight: bold; vertical-align:top';
$table->style[2] = 'font-weight: bold';
$table->rowstyle = array ();
$table->rowstyle['edit1'] = 'display: none';
$table->rowstyle['edit2'] = 'display: none';
$table->size = array ();
$table->size[0] = '15%';
$table->size[1] = '85%'; /* Fixed using javascript */
$table->size[2] = '15%';
$table->size[3] = '35%';
$table->colspan = array ();
$table->colspan[0][1] = '3';

$agents = get_group_agents (array_keys (get_user_groups ()), false, "none");
$all_modules = get_db_all_rows_filter ('tagente_modulo',
	array ('id_agente' => array_keys ($agents),
		'group' => 'nombre',
		'order' => 'id_tipo_modulo,nombre'),
	array ('DISTINCT(nombre)', 'id_tipo_modulo'));

if ($all_modules === false)
	$all_modules = array ();

$modules = array ();
$latest_type = -1;
$i = -1;
$prefix = str_repeat ('&nbsp;', 3);
foreach ($all_modules as $module) {
	if ($latest_type != $module['id_tipo_modulo']) {
		$modules[$i--] = get_moduletype_description ($module['id_tipo_modulo']);
		$latest_type = $module['id_tipo_modulo'];
	}
	$modules[$module['nombre']] = $prefix.$module['nombre'];
}

$table->data = array ();
$table->data[0][0] = __('Module');
$table->data[0][0] .= '<span id="agent_loading" class="invisible">';
$table->data[0][0] .= '<img src="images/spinner.gif" />';
$table->data[0][0] .= '</span>';
$table->data[0][1] = print_select ($modules,
	'module_name', 0, false, __('Select'), 0, true, false, false);

$table->data['edit1'][0] = __('Warning status');
$table->data['edit1'][1] = '<em>'.__('Min.').'</em>';
$table->data['edit1'][1] .= print_input_text ('min_warning', '', '', 5, 15, true);
$table->data['edit1'][1] .= '<br /><em>'.__('Max.').'</em>';
$table->data['edit1'][1] .= print_input_text ('max_warning', '', '', 5, 15, true);
$table->data['edit1'][2] = __('Critical status');
$table->data['edit1'][3] = '<em>'.__('Min.').'</em>';
$table->data['edit1'][3] .= print_input_text ('min_critical', '', '', 5, 15, true);
$table->data['edit1'][3] .= '<br /><em>'.__('Max.').'</em>';
$table->data['edit1'][3] .= print_input_text ('max_critical', '', '', 5, 15, true);

/* FF stands for Flip-flop */
$table->data['edit2'][0] = __('FF threshold').' '.pandora_help ('ff_threshold', true);
$table->data['edit2'][1] = print_input_text ('ff_event', '', '', 5, 15, true);
$table->data['edit2'][2] = __('Historical data');
$table->data['edit2'][3] = print_checkbox ("history_data", 1, '', true);

echo '<form method="post" id="form_edit" onsubmit="if (! confirm(\''.__('Are you sure').'\')) return false;">';
print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'" onsubmit="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
print_input_hidden ('update', 1);
print_submit_button (__('Update'), 'go', false, 'class="sub upd"');
echo '</div>';
echo '</form>';

echo '<h3 class="error invisible" id="message"> </h3>';

require_jquery_file ('pandora.controls');
?>

<script type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
	$("#module_name").change (function () {
		if (this.value <= 0) {
			$("td#delete_table-0-1").css ("width", "85%");
			$("tr#delete_table-edit1, tr#delete_table-edit2").hide ();
			return;
		}
		$("td#delete_table-0-1, td#delete_table-edit1-1, td#delete_table-edit2-1").css ("width", "35%");
		$("#form_edit input[type=text]").attr ("value", "");
		$("#form_edit input[type=checkbox]").removeAttr ("checked");
		$("tr#delete_table-edit1, tr#delete_table-edit2").show ();
	});
});
/* ]]> */
</script>
