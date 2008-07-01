<?php
// Pandora FMS - the Free Monitoring System
// ========================================
// Copyright (c) 2008 Artica Soluciones Tecnológicas, http://www.artica.es
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
require("include/config.php");


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
		$agent_modules = get_db_all_rows_field_filter ('tagente_modulo', 'id_agente', $id_agent);
		
		echo json_encode ($agent_modules);
		exit ();
	}
	
	if ($get_agent_status_tooltip) {
		$id_agent = (int) get_parameter ('id_agent');
		$agent = get_db_row ('tagente', 'id_agente', $id_agent);
		echo '<h3>'.$agent['nombre'].'</h3>';
		echo '<strong>'.lang_string ('IP').':</strong> '.$agent['direccion'].'<br />';
		echo '<strong>'.lang_string ('Last contact').':</strong> '.$agent['ultimo_contacto'].'<br />';
		echo '<strong>'.lang_string ('Last remote contact').':</strong> '.$agent['ultimo_contacto_remoto'].'<br />';
		echo '<strong>'.lang_string ('Group').':</strong> ';
		echo '<img src="images/groups_small/'.dame_grupo_icono ($agent['id_grupo']).'.png" /> ';
		echo dame_nombre_grupo ($agent['id_grupo']).'<br />';
		
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
				AND id_tipo_modulo in (2, 6, 9, 18, 21, 100)', $id_agent);
		$total_modules = get_db_sql ($sql);
		echo '<strong>'.lang_string ('Monitors down').':</strong> '.sizeof ($bad_modules).' / '.$total_modules;
		if (sizeof ($bad_modules)) {
			echo '<ul>';
			foreach ($bad_modules as $module) {
				echo '<li>';
				if ($module['descripcion'] != '')
					$name = $module['descripcion'];
				else
					$name = $module['nombre'];
				echo substr ($name, 0, 25);
				if (strlen ($name) > 25)
					echo '(...)';
				echo '</li>';
			}
			echo '</ul>';
		}
		echo '<br />';
		
		exit ();
	}

	exit ();
}

if (comprueba_login() == 0) {
	$id_agente = get_parameter("id_agente",-1);
	if ($id_agente != -1){
		// get group for this id_agente
		$query="SELECT * FROM tagente WHERE id_agente = ".$id_agente;
		$res=mysql_query($query);
		$row=mysql_fetch_array($res); 
		$id_grupo = $row["id_grupo"];
		$id_usuario=$config["id_user"];
		if (give_acl($id_usuario, $id_grupo, "AR")==1){
		
			// Check for validate alert request
			$validate_alert = get_parameter ("validate_alert");
			if ($validate_alert != ""){
				if (give_acl($id_usuario, $id_grupo, "AW")==1){
					$alert_row = get_db_row ("talerta_agente_modulo", "id_aam", $validate_alert);
					if ($alert_row["id_agente_modulo"] != 0){
                    	$am_row = get_db_row ("tagente_modulo", "id_agente_modulo", $alert_row["id_agente_modulo"]);
						$ag_row = get_db_row ("tagente", "id_agente", $am_row["id_agente"]);
					} else {
						$ag_row = get_db_row ("tagente", "id_agente", $alert_row ["id_agent"]);
					}
                    $alert_name = $alert_row["descripcion"];

					// Single alerts
					if ($alert_row["id_agente_modulo"] != 0){
                    	event_insert("Manual validation of alert for '$alert_name'", $ag_row["id_grupo"], $am_row["id_agente"], 1, $config["id_user"], "alert_manual_validation", 1, $alert_row["id_agente_modulo"], $validate_alert);

					// Combined alerts
					} else {
						event_insert("Manual validation of alert for '$alert_name'", $ag_row["id_grupo"], $alert_row ["id_agent"], 1, $config["id_user"], "alert_manual_validation", 1, 0, $validate_alert);
					}
					$sql='UPDATE talerta_agente_modulo SET times_fired = 0, internal_counter = 0 WHERE id_aam = '.$validate_alert;
					$result=mysql_query($sql);
				}
			}

			// Check for Network FLAG change request
			if (isset($_GET["flag"])){
				if ($_GET["flag"]==1){
					if (give_acl($id_usuario, $id_grupo, "AW")==1){
						$query ="UPDATE tagente_modulo SET flag=1 WHERE id_agente_modulo = ".$_GET["id_agente_modulo"];
						$res=mysql_query($query);
					}
				}
			}
			// Check for Network FLAG change request
			if (isset($_GET["flag_agent"])){
				if ($_GET["flag_agent"]==1){
					if (give_acl($id_usuario, $id_grupo, "AW")==1){
						$query ="UPDATE tagente_modulo SET flag=1 WHERE id_agente = ". $id_agente;
						$res=mysql_query($query);
					}
				}
			}
			if (give_acl($id_usuario,$id_grupo, "AR") == 1){
                echo "<div id='menu_tab_frame_view'>";
				echo "<div id='menu_tab_left'>
				<ul class='mn'>
				<li class='view'>
				<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=$id_agente'><img src='images/bricks.png' class='top' border=0>&nbsp; ".substr(dame_nombre_agente($id_agente),0,15)." - ".$lang_label["view_mode"]."</a>";
				echo "</li>";
				echo "</ul></div>";
			
				$tab = get_parameter ("tab", "main");
				echo "<div id='menu_tab'><ul class='mn'>";
				if (give_acl($id_usuario,$id_grupo, "AW") == 1){
                    if ($tab == "manage")
                        echo "<li class='nomn_high'>";
                    else
                        echo "<li class='nomn'>";
					// Manage agent
					echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente=$id_agente'><img src='images/setup.png' width='16' class='top' border=0> ".$lang_label["Manage"]." </a>";
					echo "</li>";
				}
				// Main view
                if ($tab == "main")
                    echo "<li class='nomn_high'>";
                else
                    echo "<li class='nomn'>";
				echo "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=$id_agente'><img src='images/monitor.png' class='top' border=0> ".$lang_label["Main"]." </a>";
				echo "</li>";

				// Data
                if ($tab == "data")
                    echo "<li class='nomn_high'>";
                else
                    echo "<li class='nomn'>";
				echo "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=$id_agente&tab=data'><img src='images/lightbulb.png' class='top' border=0> ".$lang_label["Data"]." </a>";
				echo "</li>";

				// Alerts
				if ($tab == "alert")
                    echo "<li class='nomn_high'>";
                else
                    echo "<li class='nomn'>";
				echo "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=$id_agente&tab=alert'><img src='images/bell.png' class='top' border=0> ".$lang_label["Alerts"]." </a>";
				echo "</li>";

				// Go to SLA view
				echo "<li class='nomn'>";
				echo "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&tab=sla&id_agente=$id_agente'><img src='images/images.png' class='top' border=0> ".lang_string("SLA")." </a>";
				echo "</li>";
				
				echo "</ul>";
				echo "</div>";
                echo "</div>";
				echo "<div style='height: 25px'> </div>";
				switch ($tab) {
				case "sla":
					require "sla_view.php";
					break;
				case "manage":	
                    require "estado_generalagente.php";
					break;
				case "main":	
                    require "estado_generalagente.php";
					require "estado_monitores.php";
					require "estado_alertas.php";
                    require "status_events.php";
					break;
						
				case "data": 	
                    require "estado_ultimopaquete.php";
					break;
						
				case "alert": 	
                    require "estado_alertas.php";
					break;
				}
			} else {
				audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to read data from agent ".dame_nombre_agente($id_agente));
				require ("general/noaccess.php");
			}
		} else {
			audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to access (read) to agent ".dame_nombre_agente($id_agente));
			include ("general/noaccess.php");
		}
	}
}
?>
