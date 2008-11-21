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

// Load globar vars
require_once ("include/config.php");

if (!isset ($id_agente)) {
	//This page is included, $id_agente should be passed to it.
	audit_db ($config['id_user'], $config['remote_addr'], "HACK Attempt",
			  "Trying to get to monitor list without id_agent passed");
	include ("general/noaccess.php");
	exit;
}

// Get all module from agent
$sql = sprintf ("SELECT * FROM tagente_estado, tagente_modulo WHERE tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo 
				AND tagente_modulo.id_agente = %d 
				AND tagente_modulo.disabled = 0
				AND tagente_estado.utimestamp != 0 
				ORDER BY tagente_modulo.nombre", $id_agente);

$modules = get_db_all_rows_sql ($sql);
if (empty ($modules)) {
	$modules = array ();
}
$table->width = 750;
$table->cellpadding = 4;
$table->cellspacing = 4;
$table->class = "databox";
$table->head = array ();
$table->data = array ();

$table->head[0] = "X";
$table->head[1] = __('Type');
$table->head[2] = __('Module name');
$table->head[3] = __('Description');
$table->head[4] = __('Status');
$table->head[5] = __('Interval');
$table->head[6] = __('Last contact');

//Since PHP's Zend optimizer references $var2 in case you do $var = $var2, objects get referenced too so we can't do $table = $table_data 
$table_data->head = $table->head; //Duplicate table for data modules
$table_data->class = $table->class;
$table_data->data = array ();
$table_data->cellspacing = $table->cellspacing;
$table_data->cellpadding = $table->cellpadding;
$table_data->width = $table->width;

foreach ($modules as $module) {
	$data = array ();
	if (($module["id_modulo"] != 1) && ($module["id_tipo_modulo"] != 100)) {
		if ($module["flag"] == 0) {
			$data[0] = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$id_agente.'&id_agente_modulo='.$module["id_agente_modulo"].'&flag=1&refr=60"><img src="images/target.png" border="0" /></a>';
		} else {
			$data[0] = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$id_agente.'&id_agente_modulo='.$module["id_agente_modulo"].'&refr=60"><img src="images/refresh.png" border="0"></a>';
		}
	} else {
		$data[0] = '';
	}
	
	$data[1] = '<img src="images/'.show_icon_type ($module["id_tipo_modulo"]).'" border="0">';
	$data[2] = substr ($module["nombre"], 0, 25);
	$data[3] = substr ($module["descripcion"], 0, 35);
	
	if ($module["estado"] == 1 && $module["cambio"] == 1) {
		$data[4] = '<img src="images/pixel_yellow.png" width="40" height="18" title="'.__('Change between Green/Red state').'">';
	} elseif ($module["estado"] == 1) {
		$data[4] = '<img src="images/pixel_red.png" width="40" height="18" title="'.__('At least one monitor fails').'">';
	} else {
		$data[4] = '<img src="images/pixel_green.png" width="40" height="18" title="'.__('All Monitors OK').'">';
	}
	
	if ($module["module_interval"] > 0) {
		$data[5] = $module["module_interval"];
	} else {
		$data[5] = "--";
	}
	
	$seconds = time () - $module["utimestamp"];
	if ($module["current_interval"] > 0 && $module["utimestamp"] > 0 && $seconds >= ($module["current_interval"] * 2)) {
		$data[6] = '<span class="redb">';
	} else {
		$data[6] = '<span>';
	}
	$data[6] .= print_timestamp ($module["utimestamp"], '', 'span', true);
	$data[6] .= '</span>';
	
	if ($module["estado"] != 100) {
		array_push ($table->data, $data);
		//Monitor modules go on $table
	} else {
		array_push ($table_data->data, $data);
		//Data modules go on $table_data
	}	
}		

if (empty ($table->data)) {
	echo '<div class="nf">'.__('This agent doesn\'t have any active monitors').'</div>';
} else {
	echo "<h3>".__('Full list of Monitors')."</h3>";
	print_table ($table);
}

if (empty ($table_data->data)) {
	echo '<div class="nf">'.__('This agent doesn\'t have any active data modules').'</div>';
} else {
	echo "<h3>".__('Full list of Data Modules')."</h3>";
	print_table ($table_data);
}
unset ($table);
unset ($table_data);
?>