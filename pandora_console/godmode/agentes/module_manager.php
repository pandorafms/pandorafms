<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
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

/* You can redefine $url and unset $id_agente to reuse the form. Dirty (hope temporal) hack */
if (isset ($id_agente)) {
	$url = 'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&id_agente='.$id_agente;
	echo "<h2>".__('Agent configuration')." &raquo; ".__('Modules')."</h2>"; 
}

enterprise_include ('godmode/agentes/module_manager.php');

// Create module/type combo
echo '<table width="300" cellpadding="4" cellspacing="4" class="databox">';
echo '<form id="create_module_type" method="post" action="'.$url.'">';
echo "<tr><td class='datos'>";

// Check if there is at least one server of each type available to assign that
// kind of modules. If not, do not show server type in combo

$network_available = get_db_sql ("SELECT count(*) from tserver where server_type = 2");
$wmi_available =  get_db_sql ("SELECT count(*) from tserver where server_type = 6");
$plugin_available =  get_db_sql ("SELECT count(*) from tserver where server_type = 4");
$prediction_available =  get_db_sql ("SELECT count(*) from tserver where server_type = 5");

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
	$modules['pluginserver'] = __('Create a new plugin Server module');
if ($wmi_available)
	$modules['wmiserver'] = __('Create a new WMI Server module');
if ($prediction_available)
	$modules['predictionserver'] = __('Create a new prediction Server module');

enterprise_hook ('set_enterprise_module_types', array (&$modules));
print_select ($modules, 'moduletype', '', '', '', '', false, false, false);
print_input_hidden ('edit_module', 1);
echo '</td>';
echo '<td class="datos">';
echo '<input align="right" name="updbutton" type="submit" class="sub wand" value="'.__('Create').'">';
echo "</form>";
echo "</table>";

if (! isset ($id_agente))
	return;
// ==========================
// MODULE VISUALIZATION TABLE
// ==========================

echo "<h3>".__('Assigned modules')."</h3>";

$modules = get_db_all_rows_filter ('tagente_modulo',
	array ('delete_pending' => 0,
		'id_agente' => $id_agente,
		'order' => 'id_module_group, nombre'),
	array ('id_agente_modulo', 'id_tipo_modulo', 'descripcion', 'nombre',
		'max', 'min', 'module_interval', 'id_modulo', 'id_module_group',
		'disabled',));

if ($modules === false) {
	echo "<div class='nf'>".__('No available data to show')."</div>";
	return;
}

$table->width = '95%';
$table->head = array ();
$table->head[0] = __('Name');
/* S stands for "Server" */;
$table->head[1] = __('S');
$table->head[2] = __('Type');
$table->head[3] = __('Interval');
$table->head[4] = __('Description');
$table->head[5] = __('Max/Min');
$table->head[6] = __('Action');

$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->size = array ();
$table->size[6] = '65px';
$table->align = array ();
$table->align[1] = 'center';
$table->align[6] = 'center';
$table->data = array ();

$agent_interval = get_agent_interval ($id_agente);
$last_modulegroup = "0";
foreach ($modules as $module) {
	$type = $module["id_tipo_modulo"];
	$id_module  = $module["id_modulo"];
	$nombre_modulo = $module["nombre"];
	$descripcion = $module["descripcion"];
	$module_max = $module["max"];
	$module_min = $module["min"];
	$module_interval2 = $module["module_interval"];
	$module_group2 = $module["id_module_group"];
	
	$data = array ();
	if ($module['id_module_group'] != $last_modulegroup) {
		$last_modulegroup = $module['id_module_group'];
		
		$data[0] = '<strong>'.get_modulegroup_name ($last_modulegroup).'</strong>';
		$i = array_push ($table->data, $data);
		$table->rowclass[$i - 1] = 'datos3';
		$table->colspan[$i - 1][0] = 7;
		
		$data = array ();
	}

	$data[0] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'&tab=module&edit_module=1&id_agent_module='.$module['id_agente_modulo'].'">';
	if ($module["disabled"])
		$data[0] .= '<em>'.$module['nombre'].'</em>';
	else
		$data[0] .= $module['nombre'];
	$data[0] .= '</a>';
	
	// Module type (by server type )
	$data[1] = '';
	if ($module['id_modulo'] > 0) {
		$data[1] = show_server_type ($module['id_modulo']);
	}

	// This module is initialized ? (has real data)
        $module_init = get_db_value ('utimestamp', 'tagente_estado', 'id_agente_modulo', $module['id_agente_modulo']);
        if ($module_init == 0)
                $data[1] .= print_image ('images/error.png', true, array ('title' => __('Non initialized module')));
	
	// Module type (by data type)
	$data[2] = '';
	if ($type) {
		$data[2] = print_moduletype_icon ($type, true);
	}

	// Module interval
	if ($module['module_interval']) {
		$data[3] = $module['module_interval'];
	} else {
		$data[3] = $agent_interval;
	}
	
	$data[4] = substr ($module['descripcion'], 0, 30);
	
	// MAX / MIN values
	$data[5] = $module["max"] ? $module["max"] : __('N/A');
	$data[5] .= ' / '.($module["min"] != $module['max']? $module["min"] : __('N/A'));

	// Delete module
	$data[6] = '<a href="index.php?sec=gagente&tab=module&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'&delete_module='.$module['id_agente_modulo'].'"
		onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
	$data[6] .= print_image ('images/cross.png', true,
		array ('title' => __('Delete')));
	$data[6] .= '</a> ';
	
	// Make a data normalization
	if (($type == 22) OR ($type == 1) OR ($type == 4) OR ($type == 7) OR
		($type == 8) OR ($type == 11) OR ($type == 16) OR ($type == 22)) {
		$data[6] .= '<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'&tab=module&fix_module='.$module['id_agente_modulo'].'" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
		$data[6] .= print_image ('images/chart_curve.png', true,
			array ('title' => __('Normalize')));
		$data[6] .= '</a>';
	}

	array_push ($table->data, $data);
}

print_table ($table);
?>
