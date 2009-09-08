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
check_login ();

if (! give_acl ($config['id_user'], 0, "PM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access massive module update");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_modules.php');

echo '<h3>'.__('Massive modules edition').'</h3>';

function process_manage_edit ($module_name) {
	if (is_int ($module_name) && $module_name <= 0) {
		echo '<h3 class="error">'.__('No modules selected').'</h3>';
		return false;
	}
	
	$agents = array_keys (get_group_agents (array_keys (get_user_groups ()), false, "none"));
	
	/* List of fields which can be updated */
	$fields = array ('min_warning', 'max_warning', 'min_critical', 'max_critical', 'min_ff_event');
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
	
	process_sql_begin ();
	
	if ($modules === false)
		return false;
	foreach ($modules as $module) {
		$result = update_agent_module ($module['id_agente_modulo'], $values);
		
		if ($result === false) {
			process_sql_rollback ();
			
			return false;
		}
	}
	
	process_sql_commit ();
	
	return true;
}

$module_type = (int) get_parameter ('module_type');
$module_name = (string) get_parameter ('module_name');

$update = (bool) get_parameter_post ('update');

if ($update) {
	$result = process_manage_edit ($module_name);
	
	print_result_message ($result,
		__('Successfully updated'),
		__('Could not be updated'));
	
}

$table->id = 'delete_table';
$table->width = '95%';
$table->data = array ();
$table->style = array ();
$table->style[0] = 'font-weight: bold; vertical-align:top';
$table->style[2] = 'font-weight: bold';
$table->rowstyle = array ();
$table->size = array ();
$table->size[0] = '15%';
$table->size[1] = '35%'; /* Fixed using javascript */
$table->size[2] = '15%';
$table->size[3] = '35%';
if (! $module_type) {
	$table->rowstyle['edit1'] = 'display: none';
	$table->rowstyle['edit2'] = 'display: none';
}
$agents = get_group_agents (array_keys (get_user_groups ()), false, "none");
$module_types = get_db_all_rows_filter ('tagente_modulo,ttipo_modulo',
	array ('tagente_modulo.id_tipo_modulo = ttipo_modulo.id_tipo',
		'id_agente' => array_keys ($agents),
		'disabled' => 0,
		'order' => 'ttipo_modulo.nombre'),
	array ('DISTINCT(id_tipo)',
		'CONCAT(ttipo_modulo.descripcion," (",ttipo_modulo.nombre,")") AS description'));

if ($module_types === false)
	$module_types = array ();

$types = '';
foreach ($module_types as $type) {
	$types[$type['id_tipo']] = $type['description'];
}

$table->data = array ();
$table->data[0][0] = __('Module type');
$table->data[0][0] .= '<span id="module_loading" class="invisible">';
$table->data[0][0] .= '<img src="images/spinner.gif" />';
$table->data[0][0] .= '</span>';
$table->data[0][1] = print_select ($types,
	'module_type', $module_type, false, __('Select'), 0, true, false, false);

$modules = array ();
if ($module_type != '') {
	$filter = array ('id_tipo_modulo' => $module_type);
}
else {
	$filter = false;
}

$names = get_agent_modules (array_keys ($agents),
	'DISTINCT(nombre)', $filter, false);
foreach ($names as $name) {
	$modules[$name['nombre']] = $name['nombre'];
}

$table->data[0][2] = __('Module name');
$table->data[0][3] = print_select ($modules, 'module_name',
	$module_name, false, __('Select'), 0, true, false, false);

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
$table->data['edit2'][0] = __('FF threshold').' '.print_help_icon ('ff_threshold', true);
$table->data['edit2'][1] = print_input_text ('min_ff_event', '', '', 5, 15, true);
$table->data['edit2'][2] = __('Historical data');
$table->data['edit2'][3] = print_checkbox ("history_data", 1, '', true);

echo '<form method="post" id="form_edit" onsubmit="if (! confirm(\''.__('Are you sure?').'\')) return false;">';
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
	$("#module_type").change (function () {
		if (this.value == '0') {
			filter = '';
		}
		else {
			filter = "id_tipo_modulo="+this.value;
		}
	
		$("#module_loading").show ();
		$("tr#delete_table-edit1, tr#delete_table-edit2").hide ();
		$("#module_name").attr ("disabled", "disabled")
		$("#module_name option[value!=0]").remove ();
		jQuery.post ("ajax.php",
			{"page" : "operation/agentes/ver_agente",
			"get_agent_modules_json" : 1,
			"filter" : filter,
			"fields" : "DISTINCT(nombre)",
			"indexed" : 0
			},
			function (data, status) {
				jQuery.each (data, function (id, value) {
					option = $("<option></option>").attr ("value", value["nombre"]).html (value["nombre"]);
					$("#module_name").append (option);
				});
				$("#module_loading").hide ();
				$("#module_name").removeAttr ("disabled");
			},
			"json"
		);
	});
	
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
