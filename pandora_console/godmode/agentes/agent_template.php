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

global $config;

// Load global vars
if (!isset ($id_agente)) {
	die ("Not Authorized");
}

require_once($config['homedir'] . "/include/functions_modules.php");

// ==========================
// TEMPLATE ASSIGMENT LOGIC
// ==========================
if (isset ($_POST["template_id"])) {
	// Take agent data
	$row = db_get_row ("tagente", "id_agente", $id_agente);
	if ($row !== false) {
		$intervalo = $row["intervalo"]; 
		$nombre_agente = $row["nombre"];
		$direccion_agente =$row["direccion"];
		$ultima_act = $row["ultimo_contacto"];
		$ultima_act_remota =$row["ultimo_contacto_remoto"];
		$comentarios = $row["comentarios"];
		$id_grupo = $row["id_grupo"];
		$id_os= $row["id_os"];
		$os_version = $row["os_version"];
		$agent_version = $row["agent_version"];
		$disabled= $row["disabled"];
	} else {
		return;
	}

	$id_np = get_parameter_post ("template_id");
	$npc = db_get_all_rows_field_filter ("tnetwork_profile_component", "id_np", $id_np);
	if ($npc === false) {
		$npc = array ();
	}
	foreach ($npc as $row) {
		$nc = db_get_all_rows_field_filter ("tnetwork_component", "id_nc", $row["id_nc"]);
		if ($nc === false) {
			$nc = array ();
		}
		foreach ($nc as $row2) {
			// Insert each module from tnetwork_component into agent
			$values = array(
				'id_agente' => $id_agente,
				'id_tipo_modulo' => $row2["type"],
				'descripcion' => $row2["description"],
				'nombre' => $row2["name"],
				'max' => $row2["max"],
				'min' => $row2["min"],
				'module_interval' => $row2["module_interval"],
				'tcp_port' => $row2["tcp_port"],
				'tcp_send' => $row2["tcp_send"],
				'tcp_rcv' => $row2["tcp_rcv"],
				'snmp_community' => $row2["snmp_community"],
				'snmp_oid' => $row2["snmp_oid"],
				'ip_target' => $direccion_agente,
				'id_module_group' => $row2["id_module_group"],
				'id_modulo' => $row2["id_modulo"], 
				'plugin_user' => $row2["plugin_user"],
				'plugin_pass' => $row2["plugin_pass"],
				'plugin_parameter' => $row2["plugin_parameter"],
				'max_timeout' => $row2["max_timeout"],
				'id_plugin' => $row2['id_plugin'],
				'post_process' => $row2['post_process'],
				'min_warning' => $row2['min_warning'],
				'max_warning' => $row2['max_warning'],
				'str_warning' => $row2['str_warning'],
				'min_critical' => $row2['min_critical'],
				'max_critical' => $row2['max_critical'],
				'str_critical' => $row2['str_critical']
				);
			$id_agente_modulo = db_process_sql_insert('tagente_modulo', $values);
			
			// Create with different estado if proc type or data type
			if ($id_agente_modulo !== false) {
				$values = array(
					'id_agente_modulo' => $id_agente_modulo,
					'datos' => 0,
					'timestamp' => '01-01-1970 00:00:00',
					'estado' => 0,
					'id_agente' => $id_agente,
					'utimestamp' => 0);
				db_process_sql_insert('tagente_estado', $values);
			}
			else {
				echo '<h3 class="error">'.__('Error adding module').'</h3>';
			}
		}
	}
	echo '<h3 class="suc">'.__('Modules successfully added ').'</h3>';
}

// Main header


// ==========================
// TEMPLATE ASSIGMENT FORM
// ==========================

echo "<br>";
echo '<form method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=template&id_agente='.$id_agente.'">';

$nps = db_get_all_fields_in_table ("tnetwork_profile", "name");
if ($nps === false) {
	$nps = array ();
}

$select = array ();
foreach ($nps as $row) {
	$select[$row["id_np"]] = $row["name"];
}

echo '<table width="98%" cellpadding="2" cellspacing="2" class="databox" >';
echo "<tr><td class='datos' style='width:50%'>";
html_print_select ($select, "template_id", '', '', '', 0, false, false, true, '', false, 'max-width: 200px !important');
echo '</td>';
echo '<td class="datos">';
html_print_submit_button (__('Assign'), 'crt', false, 'class="sub next"');
echo '</td>';
echo '</tr>';
echo "</form>";
echo "</table>";
echo '</form>';

// ==========================
// MODULE VISUALIZATION TABLE
// ==========================

	switch ($config["dbtype"]) {
		case "mysql":
		case "postgresql":
			$sql = sprintf ("SELECT * FROM tagente_modulo WHERE id_agente = %d AND delete_pending = false ORDER BY id_module_group, nombre", $id_agente);
			break;
		case "oracle":
			$sql = sprintf ("SELECT * FROM tagente_modulo WHERE id_agente = %d AND (delete_pending <> 1 AND delete_pending IS NOT NULL) ORDER BY id_module_group, dbms_lob.substr(nombre,4000,1)", $id_agente);
			break;
	}
$result = db_get_all_rows_sql ($sql);
if ($result === false) {
	$result = array ();
}

$table->width = '98%';
$table->cellpadding = 4;
$table->cellspacing = 4;
$table->class = "databox";
$table->head = array ();
$table->data = array ();
$table->align = array ();

$table->head[0] = __('Module name');
$table->head[1] = __('Type');
$table->head[2] = __('Description');
$table->head[3] = __('Action');

$table->align[1] = "center";
$table->align[3] = "center";

foreach ($result as $row) {
	$data = array ();
	
	$data[0] = '<span style="font-size: 7.2pt">' . $row["nombre"];
	if ($row["id_tipo_modulo"] > 0) {
		$data[1] = html_print_image("images/" . modules_show_icon_type ($row["id_tipo_modulo"]), true, array("border" => "0"));
	} else {
		$data[1] = '';
	}
	$data[2] = mb_substr ($row["descripcion"], 0, 60);
	
	$data[3] = '<a href="index.php?sec=gagente&tab=module&sec2=godmode/agentes/configurar_agente&tab=template&id_agente='.$id_agente.'&delete_module='.$row["id_agente_modulo"].'">' . html_print_image("images/cross.png", true, array("border" => "0", "alt" => __('Delete'), "onclick" => "if (!confirm('".__('Are you sure?') . "')) return false;")) . '</a>&nbsp;&nbsp;';
	
	$data[3] .= '&nbsp;&nbsp;<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'&tab=module&edit_module=1&id_agent_module='.$row["id_agente_modulo"].'">' . html_print_image("images/config.png", true, array("border" => '0', "alt" => __('Update')))  . '</a>';
	
	array_push ($table->data, $data);
}

if (!empty ($table->data)) {
	html_print_table ($table);
	unset ($table);
} else {
	echo '<div class="nf">No modules</div>';
}

?>
