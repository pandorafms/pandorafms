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

// Load global vars
if (!isset ($id_agente)) {
	die ("Not Authorized");
}

// ==========================
// TEMPLATE ASSIGMENT LOGIC
// ==========================
if (isset ($_POST["template_id"])) {
	// Take agent data
	$row = get_db_row ("tagente", "id_agente", $id_agente);
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
	$npc = get_db_all_rows_field_filter ("tnetwork_profile_component", "id_np", $id_np);
	if ($npc === false) {
		$npc = array ();
	}
	foreach ($npc as $row) {
		$nc = get_db_all_rows_field_filter ("tnetwork_component", "id_nc", $row["id_nc"]);
		if ($nc === false) {
			$nc = array ();
		}
		foreach ($nc as $row2) {
			// Insert each module from tnetwork_component into agent
			$sql = sprintf ("INSERT INTO tagente_modulo
			(id_agente, id_tipo_modulo, descripcion, nombre, max, min, module_interval, 
			tcp_port, tcp_send, tcp_rcv, snmp_community, snmp_oid, ip_target, id_module_group, id_modulo, 
			plugin_user, plugin_pass, plugin_parameter, max_timeout)
			VALUES (%d, %d, '%s', '%s', %d, %d, %d, %d, '%s', '%s', '%s', '%s', '%s', %d, %d, '%s', '%s', '%s', %d)", 
			$id_agente, $row2["type"], $row2["description"], $row2["name"], $row2["max"], $row2["min"], $row2["module_interval"], 
			$row2["tcp_port"], $row2["tcp_send"], $row2["tcp_rcv"], $row2["snmp_community"], $row2["snmp_oid"], $direccion_agente, $row2["id_module_group"], $row2["id_modulo"], 
			$row2["plugin_user"], $row2["plugin_pass"], $row2["plugin_parameter"], $row2["max_timeout"]);
			
			$id_agente_modulo = process_sql ($sql, "insert_id");
			
			// Create with different estado if proc type or data type
			if ($id_agente_modulo !== false && ($row2["type"] == 2) || ($row2["type"] == 6) || ($row2["type"] == 9) || ($row2["type"] == 12) || ($row2["type"] == 18)) {
				$sql = sprintf ("INSERT INTO tagente_estado (id_agente_modulo,datos,timestamp,estado,id_agente, utimestamp) 
								VALUES (%d, 0,'0000-00-00 00:00:00',0, %d, 0)", $id_agente_modulo, $id_agente);
				process_sql ($sql);
			} elseif ($id_agente_modulo !== false) { 
				$sql = sprintf ("INSERT INTO tagente_estado (id_agente_modulo,datos,timestamp,estado,id_agente, utimestamp) 
								VALUES (%d, 0,'0000-00-00 00:00:00',100, %d, 0)", $id_agente_modulo, $id_agente);
				process_sql ($sql);
			} else {
				echo '<h3 class="error">'.__('Error adding module').'</h3>';
			}
		}
	}
	echo '<h3 class="suc">'.__('Modules successfully added ').'</h3>';
}

// Main header

echo "<h2>".__('Agent configuration')." &gt; ".__('Module templates')."</h2>";

// ==========================
// TEMPLATE ASSIGMENT FORM
// ==========================

echo "<h3>".__('Available templates')."</h3>";
echo '<form method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=template&id_agente='.$id_agente.'">';

$nps = get_db_all_fields_in_table ("tnetwork_profile", "name");
if ($nps === false) {
	$nps = array ();
}

$select = array ();
foreach ($nps as $row) {
	$select[$row["id_np"]] = $row["name"];
}

echo '<div>'.__('Template');
print_select ($select, "template_id");
print_submit_button (__('Assign'), 'crt', false, 'class="sub next"');
echo '</div></form>';

// ==========================
// MODULE VISUALIZATION TABLE
// ==========================
echo "<h3>".__('Assigned modules')."</h3>";

$sql = sprintf ("SELECT * FROM tagente_modulo WHERE id_agente = %d ORDER BY id_module_group, nombre", $id_agente);
$result = get_db_all_rows_sql ($sql);
if ($result === false) {
	$result = array ();
}

$table->width = 700;
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
	
	$data[0] = $row["nombre"];
	if ($row["id_tipo_modulo"] > 0) {
		$data[1] = '<img src="images/'.show_icon_type ($row["id_tipo_modulo"]).'" border="0" />';
	} else {
		$data[1] = '';
	}
	$data[2] = mb_substr ($row["descripcion"], 0, 60);
	
	$data[3] = '<a href="index.php?sec=gagente&tab=module&sec2=godmode/agentes/configurar_agente&tab=template&id_agente='.$id_agente.'&delete_module='.$row["id_agente_modulo"].'"><img src="images/cross.png" border="0" alt="'.__('Delete').'" onclick="if (!confirm(\''.__('Are you sure?').'\')) return false;" /></a>&nbsp;';
	$data[3] .= '<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$id_agente.'&tab=module&update_module='.$row["id_agente_modulo"].'&moduletype='.$row["id_modulo"].'#modules"><img src="images/config.png" border="0" alt="'.__('Update').'" /></a>';
	
	array_push ($table->data, $data);
}

if (!empty ($table->data)) {
	print_table ($table);
	unset ($table);
} else {
	echo '<div class="nf">No modules</div>';
}

?>