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
require_once ("include/config.php");

enterprise_include ('operation/agentes/ver_agente.php');

check_login ();

if (defined ('AJAX')) {
	$get_agent_json = (bool) get_parameter ('get_agent_json');
	$get_agent_modules_json = (bool) get_parameter ('get_agent_modules_json');
	$get_agent_status_tooltip = (bool) get_parameter ("get_agent_status_tooltip");

	if ($get_agent_json) {
		$id_agent = (int) get_parameter ('id_agent');
		$agent = get_db_row ('tagente', 'id_agente', $id_agent);
		
		echo json_encode ($agent);
		exit ();
	}

	if ($get_agent_modules_json) {
		$id_agent = (int) get_parameter ('id_agent');
		$agent_modules = get_db_all_rows_field_filter ('tagente_modulo', 'id_agente', $id_agent, "nombre");
		
		echo json_encode ($agent_modules);
		exit ();
	}
	
	if ($get_agent_status_tooltip) {
		$id_agent = (int) get_parameter ('id_agent');
		$agent = get_db_row ('tagente', 'id_agente', $id_agent);
		echo '<h3>'.$agent['nombre'].'</h3>';
		echo '<strong>'.__('Main IP').':</strong> '.$agent['direccion'].'<br />';
		echo '<strong>'.__('Group').':</strong> ';
		echo '<img src="images/groups_small/'.dame_grupo_icono ($agent['id_grupo']).'.png" /> ';
		echo get_group_name ($agent['id_grupo']).'<br />';

		echo '<strong>'.__('Last contact').':</strong> '.human_time_comparation($agent['ultimo_contacto']).'<br />';
		echo '<strong>'.__('Last remote contact').':</strong> '.human_time_comparation($agent['ultimo_contacto_remoto']).'<br />';
		
		$sql = sprintf ('SELECT tagente_modulo.descripcion, tagente_modulo.nombre
				FROM tagente_estado, tagente_modulo 
				WHERE tagente_modulo.id_agente = %d
				AND tagente_modulo.id_tipo_modulo in (2, 6, 9, 18, 21, 100)
				AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
				AND tagente_modulo.disabled = 0 
				AND tagente_estado.estado = 1', $id_agent);
		$bad_modules = get_db_all_rows_sql ($sql);
		$sql = sprintf ('SELECT COUNT(*)
				FROM tagente_modulo
				WHERE id_agente = %d
				AND disabled = 0 
				AND id_tipo_modulo in (2, 6, 9, 18, 21, 100)', $id_agent);
		$total_modules = get_db_sql ($sql);
		
		if ($bad_modules === false)
			$size_bad_modules = 0;
		else
			$size_bad_modules = sizeof ($bad_modules);

		// Modules down
		if ($size_bad_modules > 0) {
			echo '<strong>'.__('Monitors down').':</strong> '.$size_bad_modules.' / '.$total_modules;
			echo '<ul>';
			foreach ($bad_modules as $module) {
				echo '<li>';
				$name = $module['nombre'];
				echo substr ($name, 0, 25);
				if (strlen ($name) > 25)
					echo '(...)';
				echo '</li>';
			}
			echo '</ul>';
		}

		// Alerts (if present)
		$sql = sprintf ('SELECT COUNT(talerta_agente_modulo.id_aam)
				FROM talerta_agente_modulo, tagente_modulo, tagente
				WHERE tagente.id_agente = %d
				AND tagente.disabled = 0
				AND tagente.id_agente = tagente_modulo.id_agente
				AND tagente_modulo.disabled = 0
				AND tagente_modulo.id_agente_modulo = talerta_agente_modulo.id_agente_modulo
				AND talerta_agente_modulo.times_fired > 0 ',
				$id_agent);
		$alert_modules = get_db_sql ($sql);
		if ($alert_modules > 0){
			$sql = sprintf ('SELECT tagente_modulo.nombre, talerta_agente_modulo.last_fired FROM talerta_agente_modulo, tagente_modulo, tagente WHERE tagente.id_agente = %d AND tagente.disabled = 0 AND tagente.id_agente = tagente_modulo.id_agente AND tagente_modulo.disabled = 0 AND tagente_modulo.id_agente_modulo = talerta_agente_modulo.id_agente_modulo AND talerta_agente_modulo.times_fired > 0 ', $id_agent);
			$alerts = get_db_all_rows_sql ($sql);
			echo '<strong>'.__('Alerts fired').':</strong>';
			echo "<ul>";
			foreach ($alerts as $alert_item) {
				echo '<li>';
				$name = $alert_item[0];
				echo substr ($name, 0, 25);
				if (strlen ($name) > 25)
					echo '(...)';
				echo "&nbsp;";
				echo human_time_comparation($alert_item[1]);
				echo '</li>';
			}
			echo '</ul>';
		}
		
		exit ();
	}

	exit ();
}

$id_agente = (int) get_parameter ("id_agente", 0);
if (empty ($id_agente)) {
	return;
}

// get group for this id_agente
$id_grupo = get_db_value ('id_grupo', 'tagente', 'id_agente', $id_agente);
if (! give_acl ($config['id_user'], $id_grupo, "AR")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access (read) to agent ".get_agent_name($id_agente));
	include ("general/noaccess.php");
	return;
}

// Check for Network FLAG change request
if (isset($_GET["flag"])) {
	if ($_GET["flag"] == 1 && give_acl ($config['id_user'], $id_grupo, "AW")) {
		$sql = "UPDATE tagente_modulo SET flag=1 WHERE id_agente_modulo = ".$_GET["id_agente_modulo"];
		process_sql ($sql);
	}
}
// Check for Network FLAG change request
if (isset($_GET["flag_agent"])){
	if ($_GET["flag_agent"] == 1 && give_acl ($config['id_user'], $id_grupo, "AW")) {
		$sql ="UPDATE tagente_modulo SET flag=1 WHERE id_agente = ". $id_agente;
		process_sql ($sql);
	}
}

echo "<div id='menu_tab_frame_view'>";
echo "<div id='menu_tab_left'><ul class='mn'><li class='view'>
<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=$id_agente'><img src='images/bricks.png' class='top' border=0>&nbsp; ".substr(get_agent_name($id_agente),0,21)."</a>";
echo "</li>";
echo "</ul></div>";
$tab = get_parameter ("tab", "main");
echo "<div id='menu_tab'><ul class='mn'>";
if (give_acl ($config['id_user'],$id_grupo, "AW")) {
	if ($tab == "manage") {
		echo "<li class='nomn_high'>";
	} else {
		echo "<li class='nomn'>";
	}
	// Manage agent
	echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente=$id_agente'><img src='images/setup.png' width='16' class='top' border=0> ".__('Manage')." </a>";
	echo "</li>";
}

// Main view
if ($tab == "main") {
	echo "<li class='nomn_high'>";
} else {
	echo "<li class='nomn'>";
}
echo "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=$id_agente'><img src='images/monitor.png' class='top' border=0> ".__('Main')." </a>";
echo "</li>";

// Data
if (($tab == "data") OR ($tab == "data_view")) {
	echo "<li class='nomn_high'>";
} else {
	echo "<li class='nomn'>";
}
echo "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=$id_agente&tab=data'><img src='images/lightbulb.png' class='top' border=0> ".__('Data')." </a>";
echo "</li>";

// Alerts
if ($tab == "alert") {
	echo "<li class='nomn_high'>";
} else {
	echo "<li class='nomn'>";
}
echo "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=$id_agente&tab=alert'><img src='images/bell.png' class='top' border=0> ".__('Alerts')." </a>";
echo "</li>";

// Go to SLA view
if ($tab == "sla") {
	echo "<li class='nomn_high'>";
} else {
	echo "<li class='nomn'>";
}
echo "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&tab=sla&id_agente=$id_agente'><img src='images/images.png' class='top' border=0> ".__('S.L.A')." </a>";
echo "</li>";

// Group tab
echo "<li class='nomn'>";
echo "<a href='index.php?sec=estado&sec2=operation/agentes/estado_agente&group_id=$id_grupo'>";
echo "<img src='images/god4.png' class='top' border=0>&nbsp;";
echo __("Group");
echo "</a></li>";

// Inventory
enterprise_hook ('inventory_tab');

echo "</ul>";
echo "</div>";
echo "</div>";
echo "<div style='height: 25px'> </div>";

switch ($tab) {
case "sla":
	require ("sla_view.php");
	break;
case "manage":	
	require ("estado_generalagente.php");
	break;
case "main":	
	require ("estado_generalagente.php");
	require ("estado_monitores.php");
	require ("alerts_status.php");
	require ("status_events.php");
	break;
case "data_view":
	require ("datos_agente.php");
	break;
case "data":
	require ("estado_ultimopaquete.php");
	break;
case "alert":
	require ("alerts_status.php");
	break;
case "inventory":
	enterprise_include ('operation/agentes/agent_inventory.php');
	break;
}

?>
