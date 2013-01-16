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

/* You can redefine $url and unset $id_agente to reuse the form. Dirty (hope temporal) hack */
if (isset ($id_agente)) {
	$url = 'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&id_agente='.$id_agente;

}

enterprise_include ('godmode/agentes/module_manager.php');
$isFunctionPolicies = enterprise_include_once ('include/functions_policies.php');
require_once ('include/functions_modules.php');
require_once ('include/functions_agents.php');
require_once ('include/functions_servers.php');

// Create module/type combo
echo '<table width="98%" cellpadding="2" cellspacing="2" class="databox" >';
echo '<form id="create_module_type" method="post" action="'.$url.'">';
echo "<tr><td class='datos' style='width:50%'>";

// Check if there is at least one server of each type available to assign that
// kind of modules. If not, do not show server type in combo

$network_available = db_get_sql ("SELECT count(*) from tserver where server_type = 1"); //POSTGRESQL AND ORACLE COMPATIBLE
$wmi_available = db_get_sql ("SELECT count(*) from tserver where server_type = 6"); //POSTGRESQL AND ORACLE COMPATIBLE
$plugin_available = db_get_sql ("SELECT count(*) from tserver where server_type = 4"); //POSTGRESQL AND ORACLE COMPATIBLE
$prediction_available = db_get_sql ("SELECT count(*) from tserver where server_type = 5"); //POSTGRESQL AND ORACLE COMPATIBLE

// Development mode to use all servers
if ($develop_bypass) {
	$network_available = 1;
	$wmi_available = 1;
	$plugin_available = 1;
	$prediction_available = 1;
}

$modules = array ();
$modules['dataserver'] = __('Create a new data server module');
if ($network_available)
	$modules['networkserver'] = __('Create a new network server module');
if ($plugin_available)
	$modules['pluginserver'] = __('Create a new plugin server module');
if ($wmi_available)
	$modules['wmiserver'] = __('Create a new WMI server module');
if ($prediction_available)
	$modules['predictionserver'] = __('Create a new prediction server module');

enterprise_hook ('set_enterprise_module_types', array (&$modules));

$sec2 = get_parameter('sec2', '');
if (strstr($sec2, "enterprise/godmode/policies/policies") !== false) {
	unset($modules['predictionserver']);
}

html_print_select ($modules, 'moduletype', '', '', '', '', false, false, false, '', false, 'max-width:300px;' );
html_print_input_hidden ('edit_module', 1);
echo '</td>';
echo '<td class="datos">';
echo '<input align="right" name="updbutton" type="submit" class="sub next" value="'.__('Create').'">';
echo '</td>';
echo '<td class="datos" style="text-align:center;">';
echo "<strong>";
	echo "<a style='color: #004A1B;' target='_blank' href='http://pandorafms.com/Library/Library/'>".__("Get more modules in Pandora FMS Library")."</a>";
echo "</strong>";
echo '</td>';
echo '</tr>';
echo "</form>";
echo "</table>";

if (! isset ($id_agente))
	return;


$multiple_delete = (bool) get_parameter('multiple_delete');

if ($multiple_delete) {
	$id_agent_modules_delete = (array)get_parameter('id_delete');
	
	$count_correct_delete_modules = 0;
	foreach($id_agent_modules_delete as $id_agent_module_del) {
		$id_grupo = (int) agents_get_agent_group($id_agente);
		
		if (! check_acl ($config["id_user"], $id_grupo, "AW")) {
			db_pandora_audit("ACL Violation",
			"Trying to delete a module without admin rights");
			require ("general/noaccess.php");
			exit;
		}
		
		if ($id_agent_module_del < 1) {
			db_pandora_audit("HACK Attempt",
			"Expected variable from form is not correct");
			die (__("Nice try buddy"));
			exit;
		}
		
		enterprise_include_once('include/functions_config_agents.php');
		enterprise_hook('config_agents_delete_module_in_conf', array(modules_get_agentmodule_agent($id_agent_module_del), modules_get_agentmodule_name($id_agent_module_del)));
		
		//Init transaction
		$error = 0;
		db_process_sql_begin ();
		
		// First delete from tagente_modulo -> if not successful, increment
		// error. NOTICE that we don't delete all data here, just marking for deletion
		// and delete some simple data.
		$status = '';
		if (db_process_sql("UPDATE tagente_modulo
			SET nombre = 'pendingdelete', disabled = 1, delete_pending = 1 WHERE id_agente_modulo = ".$id_agent_module_del, "affected_rows", '', true, $status, false) === false)
			$error++;
		
		switch ($config["dbtype"]) {
			case "mysql":
			case "postgresql":
				$result = db_process_sql_delete('tagente_estado', array('id_agente_modulo' => $id_agent_module_del)); 
				if ($result === false)
					$error++;
				
				$result = db_process_sql_delete('tagente_datos_inc', array('id_agente_modulo' => $id_agent_module_del));
				if ($result === false)
					$error++;
				break;
			case "oracle":
				$result = db_process_delete_temp('tagente_estado', 'id_agente_modulo', $id_agent_module_del);
				if ($result === false)
					$error++;
				$result = db_process_delete_temp('tagente_datos_inc', 'id_agente_modulo', $id_agent_module_del);		
				if ($result === false)
					$error++;
				break;
		}
		
		// Trick to detect if we are deleting a synthetic module (avg or arithmetic)
		// If result is empty then module doesn't have this type of submodules
		$ops_json = enterprise_hook('modules_get_synthetic_operations', array($id_agent_module_del));
		$result_ops_synthetic = json_decode($ops_json);
		if (!empty($result_ops_synthetic)){
			$result = enterprise_hook('modules_delete_synthetic_operations', array($id_agent_module_del));
			if ($result === false)
				$error++;
		} // Trick to detect if we are deleting components of synthetics modules (avg or arithmetic)
		else{
			$result_components = enterprise_hook('modules_get_synthetic_components', array($id_agent_module_del));
			$count_components = 1;
			if (!empty($result_components)){
				// Get number of components pending to delete to know when it's needed to update orders 
				$num_components = count($result_components);
				$last_target_module = 0;
				foreach ($result_components as $id_target_module){
					// Detects change of component or last component to update orders
					if (($count_components == $num_components) or ($last_target_module != $id_target_module))
						$update_orders = true;
					else
						$update_orders = false;
					$result = enterprise_hook('modules_delete_synthetic_operations', array($id_target_module, $id_agent_module_del, $update_orders));
					if ($result === false)
						$error++;
					$count_components++;
					$last_target_module = $id_target_module;
				}
			}
		}
		
		
		//Check for errors
		if ($error != 0) {
			db_process_sql_rollback ();
		}
		else {
			db_process_sql_commit ();
			$count_correct_delete_modules++;
		}
	}
	
	$count_modules_to_delete = count($id_agent_modules_delete);
	if ($count_correct_delete_modules == 0) {
		ui_print_error_message(
			sprintf(__('There was a problem deleting %s modules, none deleted.'),
			$count_modules_to_delete));
	}
	else {
		if ($count_correct_delete_modules == $count_modules_to_delete) {
			ui_print_success_message (__('All Modules deleted succesfully'));
		}
		else {
			ui_print_error_message(
			sprintf(__('There was a problem only deleted %s modules of %s total.'),
				count_correct_delete_modules, $count_modules_to_delete));
		}
	}
}


// ==========================
// MODULE VISUALIZATION TABLE
// ==========================

$url = 'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&id_agente=' . $id_agente;
$selectNameUp = '';
$selectNameDown = '';
$selectServerUp = '';
$selectServerDown = '';
$selectTypeUp = '';
$selectTypeDown = '';
$selectIntervalUp = '';
$selectIntervalDown = '';
$sortField = get_parameter('sort_field');
$sort = get_parameter('sort', 'none');
$selected = 'border: 1px solid black;';

$order[] = array('field' => 'id_module_group', 'order' => 'ASC');

switch ($sortField) {
	case 'name':
		switch ($sort) {
			case 'up':
				$selectNameUp = $selected;
				switch ($config["dbtype"]) {
					case "mysql":
					case "postgresql":				
						$order[] = array('field' => 'tagente_modulo.nombre', 'order' => 'ASC');
						break;
					case "oracle":
						$order[] = array('field' => 'dbms_lob.substr(tagente_modulo.nombre,4000,1)', 'order' => 'ASC');
						break;
				}
				break;
			case 'down':
				$selectNameDown = $selected;
				switch ($config["dbtype"]) {
					case "mysql":
					case "postgresql":
						$order[] = array('field' => 'tagente_modulo.nombre', 'order' => 'DESC');
						break;
					case "oracle":	
						$order[] = array('field' => 'dbms_lob.substr(tagente_modulo.nombre,4000,1)', 'order' => 'DESC');
						break;
				}		
				break;
		}
		break;
	case 'server':
		switch ($sort) {
			case 'up':
				$selectServerUp = $selected;
				$order[] = array('field' => 'id_modulo', 'order' => 'ASC');
				break;
			case 'down':
				$selectServerDown = $selected;
				$order[] = array('field' => 'id_modulo', 'order' => 'DESC');
				break;
		}
		break;
	case 'type':
		switch ($sort) {
			case 'up':
				$selectTypeUp = $selected;
				$order[] = array('field' => 'id_tipo_modulo', 'order' => 'ASC');
				break;
			case 'down':
				$selectTypeDown = $selected;
				$order[] = array('field' => 'id_tipo_modulo', 'order' => 'DESC');
				break;
		}
		break;
	case 'interval':
		switch ($sort) {
			case 'up':
				$selectIntervalUp = $selected;
				$order[] = array('field' => 'module_interval', 'order' => 'ASC');
				break;
			case 'down':
				$selectIntervalDown = $selected;
				$order[] = array('field' => 'module_interval', 'order' => 'DESC');
				break;
		}
		break;
	default:
		$selectNameUp = $selected;
		$selectNameDown = '';
		$selectServerUp = '';
		$selectServerDown = '';
		$selectTypeUp = '';
		$selectTypeDown = '';
		$selectIntervalUp = '';
		$selectIntervalDown = '';
		switch ($config["dbtype"]) {
			case "mysql":
			case "postgresql":
				$order[] = array('field' => 'nombre', 'order' => 'ASC');
				break;
			case "oracle":
				$order[] = array('field' => 'dbms_lob.substr(nombre,4000,1)', 'order' => 'ASC');
				break;
		}
		break;
}

// TODO: CLEAN extra_sql
$extra_sql = '';
	
// Build the order sql
if(!empty($order)) {
	$order_sql = ' ORDER BY ';
}
$first = true;
foreach($order as $ord) {
	if($first) {
		$first = false;
	}
	else {
		$order_sql .= ',';
	}
	
	$order_sql .= $ord['field'].' '.$ord['order'];
}

// Get limit and offset parameters
$limit = (int) $config["block_size"];
$offset = (int) get_parameter ('offset');

$params = implode(',', array ('id_agente_modulo', 'id_tipo_modulo', 'descripcion', 'nombre',
		'max', 'min', 'module_interval', 'id_modulo', 'id_module_group',
		'disabled','max_warning', 'min_warning', 'str_warning',
		'max_critical', 'min_critical', 'str_critical'));
		
$where = sprintf("delete_pending = 0 AND id_agente = %s", $id_agente);		
switch ($config["dbtype"]) {
	case "postgresql":
		$limit_sql = " LIMIT $limit OFFSET $offset ";
	case "mysql":
		if(!isset($limit_sql)) {
			$limit_sql = " LIMIT $offset, $limit ";
		}
		$sql = sprintf("SELECT %s FROM tagente_modulo WHERE %s (%s) %s %s", 
					$params, $extra_sql, $where, $order_sql, $limit_sql);

		$modules = db_get_all_rows_sql($sql);
		break;
	case "oracle":	
		$set = array();
		$set['limit'] = $limit;
		$set['offset'] = $offset;	
		$sql = sprintf("SELECT %s FROM tagente_modulo WHERE %s (%s) %s", 
					$params, $extra_sql, $where, $order_sql);
		$modules = oracle_recode_query ($sql, $set, 'AND', false);
		break;
}
	
$sql_total_modules = sprintf("SELECT count(*) FROM tagente_modulo WHERE %s (%s)", $extra_sql, $where);

$total_modules = db_get_value_sql($sql_total_modules);

$total_modules = isset ($total_modules) ? $total_modules : 0;	

if ($modules === false) {
	echo "<div class='nf'>".__('No available data to show')."</div>";
	return;
}

// Prepare pagination
ui_pagination ($total_modules, ui_get_url_refresh (array ('id_agente' => $id_agente,'sort_field' => $sortField, 'sort' => $sort, 'id_agent_module' => false, 'edit_module' => false), true, false));

$table->width = '98%';
$table->head = array ();
$table->head[0] = __('Name') . ' ' .
	'<a href="' . $url . '&sort_field=name&sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectNameUp)) . '</a>' .
	'<a href="' . $url . '&sort_field=name&sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectNameDown)) . '</a>';
if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK)
	$table->head[1] = "<span title='" . __('Policy') . "'>" . __('P.') . "</span>";
$table->head[2] = "<span title='" . __('Server') . "'>" . __('S.') . "</span>" . ' ' .
	'<a href="' . $url . '&sort_field=server&sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectServerUp)) . '</a>' .
	'<a href="' . $url . '&sort_field=server&sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectServerDown)) . '</a>';
$table->head[3] = __('Type') . ' ' .
	'<a href="' . $url . '&sort_field=type&sort=up">' .  html_print_image("images/sort_up.png", true, array("style" => $selectTypeUp)) .'</a>' .
	'<a href="' . $url . '&sort_field=type&sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectTypeDown)) . '</a>';
$table->head[4] = __('Interval') . ' ' .
	'<a href="' . $url . '&sort_field=interval&sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectIntervalUp)) . '</a>' .
	'<a href="' . $url . '&sort_field=interval&sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectIntervalDown)) . '</a>';
$table->head[5] = __('Description');
$table->head[6] = __('Status');
$table->head[7] = __('Warn');


$table->head[8] = __('Action');

$table->rowstyle = array();
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->size = array ();
$table->size[2] = '55px';
$table->size[8] = '100px';
$table->align = array ();
$table->align[2] = 'center';
$table->align[8] = 'left';
$table->data = array ();

$agent_interval = agents_get_interval ($id_agente);
$last_modulegroup = "0";

//Extract the ids only numeric modules for after show the normalize link. 
$tempRows = db_get_all_rows_sql("SELECT *
	FROM ttipo_modulo
	WHERE nombre NOT LIKE '%string%' AND nombre NOT LIKE '%proc%'");
$numericModules = array();
foreach($tempRows as $row) {
	$numericModules[$row['id_tipo']] = true;
}

foreach ($modules as $module) {
	$is_extra = enterprise_hook('policies_is_module_extra_policy', array($module["id_agente_modulo"]));

	if($is_extra === ENTERPRISE_NOT_HOOK) {
		$is_extra = false;
	}

	if (! check_acl ($config["id_user"], $group, "AW", $id_agente) && !$is_extra) {
		continue;
	}
	
	$type = $module["id_tipo_modulo"];
	$id_module = $module["id_modulo"];
	$nombre_modulo = $module["nombre"];
	$descripcion = $module["descripcion"];
	$module_max = $module["max"];
	$module_min = $module["min"];
	$module_interval2 = $module["module_interval"];
	$module_group2 = $module["id_module_group"];
	
	$data = array ();
	if ($module['id_module_group'] != $last_modulegroup) {
		$last_modulegroup = $module['id_module_group'];
		$data[0] = '<strong>'.modules_get_modulegroup_name ($last_modulegroup).'</strong>';
		$i = array_push ($table->data, $data);
		$table->rowstyle[$i - 1] = 'text-align: center';
		$table->rowclass[$i - 1] = 'datos3';
		if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK)
				$table->colspan[$i - 1][0] = 9;
		else
			$table->colspan[$i - 1][0] = 8;
		
		$data = array ();
	}
	
	$data[0] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente=' . $id_agente . '&tab=module&edit_module=1&id_agent_module='.$module['id_agente_modulo'].'">';
	if ($module["disabled"])
		$data[0] .= '<em class="disabled_module">' .
			ui_print_truncate_text($module['nombre'], 'module_medium', false, true, true, '[&hellip;]', 'font-size: 7.2pt').'</em>';
	else
		$data[0] .= ui_print_truncate_text($module['nombre'], 'module_medium', false, true, true, '[&hellip;]', 'font-size: 7.2pt');
	$data[0] .= '</a>';
	
	if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
		$policyInfo = policies_info_module_policy($module['id_agente_modulo']);
		if ($policyInfo === false)
			$data[1] = '';
		else {
			$linked = policies_is_module_linked($module['id_agente_modulo']);
			
			$adopt = false;
			if (policies_is_module_adopt($module['id_agente_modulo'])) {
				$adopt = true;
			}
			
			if ($linked) {
				if ($adopt) {
					$img = 'images/policies_brick.png';
					$title = __('(Adopt) ') . $policyInfo['name_policy'];
				}
				else {
					$img = 'images/policies.png';
					$title = $policyInfo['name_policy'];
				}
			}
			else {
				if ($adopt) {
					$img = 'images/policies_not_brick.png';
					$title = __('(Adopt) (Unlinked) ') . $policyInfo['name_policy'];
				}
				else {
					$img = 'images/unlinkpolicy.png';
					$title = __('(Unlinked) ') . $policyInfo['name_policy'];
				}
			}
				
			$data[1] = '<a href="?sec=gpolicies&sec2=enterprise/godmode/policies/policies&id=' . $policyInfo['id_policy'] . '">' . 
				html_print_image($img,true, array('title' => $title)) .
				'</a>';
		}
	}
	
	// Module type (by server type )
	$data[2] = '';
	if ($module['id_modulo'] > 0) {
		$data[2] = servers_show_type ($module['id_modulo']);
	}

	$module_status = db_get_row('tagente_estado', 'id_agente_modulo', $module['id_agente_modulo']);
	
	modules_get_status($module['id_agente_modulo'], $module_status['estado'], $module_status['datos'], $status, $title);
	
	// This module is initialized ? (has real data)
	if ($status == STATUS_MODULE_NO_DATA)
		$data[2] .= html_print_image ('images/error.png', true, array ('title' => __('Non initialized module')));
	
	// Module type (by data type)
	$data[3] = '';
	if ($type) {
		$data[3] = ui_print_moduletype_icon ($type, true);
	}
	
	// Module interval
	if ($module['module_interval']) {
		$data[4] = human_time_description_raw($module['module_interval']);
	}
	else {
		$data[4] = human_time_description_raw($agent_interval);
	}
	
	$data[5] = ui_print_truncate_text($module['descripcion'], 'description', false);
	
	$data[6] = ui_print_status_image($status, $title, true);
	
	// MAX / MIN values
	$data[7] = ui_print_module_warn_value ($module["max_warning"], $module["min_warning"], $module["str_warning"], $module["max_critical"], $module["min_critical"], $module["str_critical"]); 
	
	// Delete module
	$data[8] = html_print_checkbox('id_delete[]', $module['id_agente_modulo'], false, true);
	$data[8] .= '&nbsp;<a href="index.php?sec=gagente&tab=module&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'&delete_module='.$module['id_agente_modulo'].'"
		onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
	$data[8] .= html_print_image ('images/cross.png', true,
		array ('title' => __('Delete')));
	$data[8] .= '</a> ';
	$data[8] .= '&nbsp;<a href="index.php?sec=gagente&tab=module&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'&duplicate_module='.$module['id_agente_modulo'].'"
		onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
	$data[8] .= html_print_image ('images/copy.png', true,
		array ('title' => __('Duplicate')));
	$data[8] .= '</a> ';
	
	// Make a data normalization
	
	if (isset($numericModules[$type])) {
		if ($numericModules[$type] === true) {
			$data[8] .= '&nbsp;<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'&tab=module&fix_module='.$module['id_agente_modulo'].'" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
			$data[8] .= html_print_image ('images/chart_curve.png', true,
				array ('title' => __('Normalize')));
			$data[8] .= '</a>';
		}
	}
	
	array_push ($table->data, $data);
}

echo '<form method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'&tab=module"
	onsubmit="if (! confirm (\''.__('Are you sure?').'\')) return false">';
html_print_table ($table);
echo '<div class="action-buttons" style="width: '.$table->width.'">';
html_print_input_hidden ('multiple_delete', 1);
html_print_submit_button (__('Delete'), 'multiple_delete', false, 'class="sub delete"');
echo '</div>';
echo '</form>'
?>
