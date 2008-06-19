<?php

// Pandora FM
// ====================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2008 Artica Soluciones Tecnoloicas S.L, info@artica.es
// Copyright (c) 2004-2006 Raul Mateos Martin, raulofpandora@gmail.com
// Copyright (c) 2008 Jorge Gonzalez <jorge.gonzalez@artica.es>
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Load global vars
require("include/config.php");

comprueba_login();

$id_user = $_SESSION["id_usuario"];
$id_group = get_parameter ("id_group",0);
$origen = get_parameter ("origen", -1);
$update_agent = get_parameter ("update_agent", -1);
$update_group = get_parameter ("update_group", -1);

if ( (give_acl($id_user, 0, "LM")==0) AND (give_acl($id_user, 0, "AW")==0) ){
	audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Agent Config Management Admin section");
	require ("general/noaccess.php");
}
		
// Operations
// ---------------
if ((isset($_GET["operacion"])) AND ($update_agent == -1) AND ($update_group == -1) ) {

	// DATA COPY
	// ---------
	if (isset($_POST["copy"])) {
		echo "<h2>".$lang_label["datacopy"]."</h2>";
		// Initial checkings

		// if selected more than 0 agents
		$destino = $_POST["destino"];
		if (count($destino) <= 0){
			echo "<h3 class='error'>ERROR: ".$lang_label["noagents_cp"]."</h3>";
			echo "</table>";
			include ("general/footer.php");
			exit;
		}
		$origen_modulo = $_POST["origen_modulo"];
		if (count($origen_modulo) <= 0){
			echo "<h3 class='error'>ERROR: ".$lang_label["nomodules_selected"]."</h3>";
			echo "</table>";
			include ("general/footer.php");
			exit;
		}

		$multiple=1;

		// Source
		$id_origen = $_POST["origen"];
		
		// If selected modules or alerts
		if (isset($_POST["modules"]))
			$modulos = 1;
		else
			$modulos = 0;
		if (isset($_POST["alerts"]))
			$alertas = 1;
		else
			$alertas = 0;
		if (($alertas + $modulos) == 0){
			echo "<h3 class='error'>ERROR: ".$lang_label["you_must_select_modules"]."</h3>";;
			echo "</table>";
			include ("general/footer.php");
			exit;
		}
		
		// Copy
		// ----	
		for ($a=0;$a <count($destino); $a++){ 
			// For every agent in destination

			$id_agente = $destino[$a];
			echo "<br><br>".$lang_label["copyage"]."<b> [".dame_nombre_agente($id_origen)."] -> [".dame_nombre_agente($id_agente)."]</b>";
			if ($multiple == 0)
					$b=-1;
			else
					$b=0;

			// Module read
			if ($modulos == 1){
				for ($b=$b; $b < count($origen_modulo); $b++){
					if ($multiple == 0)	
						$sql1='SELECT * FROM tagente_modulo WHERE id_agente = '.$id_origen;
					else
						$sql1='SELECT * FROM tagente_modulo WHERE id_agente_modulo = '.$origen_modulo[$b];
					$result1=mysql_query($sql1);
					while ($row=mysql_fetch_array($result1)){
						$o_id_agente_modulo = $row["id_agente_modulo"];
						$o_id_tipo_modulo = $row["id_tipo_modulo"];
						$o_nombre = $row["nombre"];
						$d_id_agente = $id_agente; // Rapelace with destination agent id
						// Read every module in source agent				
						$o_descripcion = $row["descripcion"];
						$o_max = $row["max"];
						$o_min = $row["min"];
						$o_module_interval = $row["module_interval"];
						$o_tcp_port = $row["tcp_port"];
						$o_tcp_send = $row["tcp_send"];
						$o_tcp_rcv = $row["tcp_rcv"];
						$o_snmp_community = $row["snmp_community"];
						$o_snmp_oid = $row["snmp_oid"];
						// Replace IP Address for main ip address of destination module
						$real_ip_address = give_agent_address ($id_agente);
						$o_ip_target = $real_ip_address;
						$o_id_module_group = $row["id_module_group"];
						// 2.0 new modules
						$o_max_timeout = $row["max_timeout"];
						$o_prediction_module = $row["prediction_module"];
						$o_post_process = $row["post_process"];
						$o_id_plugin = $row["id_plugin"];
						$o_plugin_parameter = $row["plugin_parameter"];
						$o_plugin_user = $row["plugin_user"];
						$o_plugin_pass = $row["plugin_pass"];
						$o_id_modulo = $row["id_modulo"];

						
						// Write every module in destination agent
						if ($o_nombre != "agent_keepalive") {
							$sql = "INSERT INTO tagente_modulo (
								id_agente,id_tipo_modulo,descripcion, nombre, max, min, 
								module_interval, tcp_port, tcp_send, tcp_rcv, snmp_community, 
								snmp_oid, ip_target, id_module_group, flag, 
								max_timeout, prediction_module, post_process, id_plugin, 
								plugin_parameter, plugin_user, plugin_pass, id_modulo
							) VALUES (
							$d_id_agente, '$o_id_tipo_modulo', '$o_descripcion',
							'$o_nombre', '$o_max', '$o_min', '$o_module_interval',
							'$o_tcp_port', '$o_tcp_send', '$o_tcp_rcv', '$o_snmp_community',
							'$o_snmp_oid', '$o_ip_target', $o_id_module_group, 1,
							$o_max_timeout, $o_prediction_module, '$o_post_process',
							$o_id_plugin, '$o_plugin_parameter', '$o_plugin_user',
							'$o_plugin_pass', $o_id_modulo
							)";
							$result2=mysql_query($sql);
							if (! $result2)
								echo "<h3 class=error>".lang_string("Problem updating database")."</h3>";
							$o_id_agente_modulo = mysql_insert_id();
							
							// Create with different estado if proc type or data type
							if (
							($o_id_agente_modulo != "") AND (
							($o_id_tipo_modulo == 2) ||
							($o_id_tipo_modulo == 6) || 
							($o_id_tipo_modulo == 9) ||
							($o_id_tipo_modulo == 100) || 
							($o_id_tipo_modulo == 21) || 
							($o_id_tipo_modulo == 18))){
								$sql_status_insert = "INSERT INTO tagente_estado 
								(id_agente_modulo,datos,timestamp,cambio,estado,id_agente, utimestamp) 
								VALUES (
								$o_id_agente_modulo, 0,'0000-00-00 00:00:00',0,0,'".$d_id_agente."',0
								)";
							} else { 
								$sql_status_insert = "INSERT INTO tagente_estado
								(id_agente_modulo,datos,timestamp,cambio,estado,id_agente, utimestamp) 
								VALUES (
								$o_id_agente_modulo, 0,'0000-00-00 00:00:00',0,100,'".$d_id_agente."',0
								)";
							}
							$result_status=mysql_query($sql_status_insert);
							echo "<br>&nbsp;&nbsp;".$lang_label["copymod"]." ->".$o_nombre;
						}
					}
				}
			}
			if ($multiple == 0)
					$b=-1;
			else
					$b=0;

			// Alerts
			// ------
			if ($alertas == 1){
				for ($b=$b; $b < count($origen_modulo); $b++){
					if ($multiple == 0)
						$sql1='SELECT * FROM tagente_modulo WHERE id_agente = '.$id_origen;
					else
						$sql1='SELECT * FROM tagente_modulo WHERE id_agente_modulo = '.$origen_modulo[$b];
					$result1=mysql_query($sql1);
					while ($row=mysql_fetch_array($result1)){
						$o_id_agente_modulo = $row["id_agente_modulo"];
						$o_id_tipo_modulo = $row["id_tipo_modulo"];
						$o_nombre = $row["nombre"];
						$d_id_agente = $id_agente; // destination agent id
						// For each agent module, given as $o_id_agente_modulo:
						// Searching if destination agent has a agente_modulo with same type and name that source
						$sqlp="SELECT * FROM tagente_modulo WHERE id_agente = ".$d_id_agente." AND nombre = '".$o_nombre."' AND id_tipo_modulo = ".$o_id_tipo_modulo;
						$resultp=mysql_query($sqlp);
						if ( $rowp=mysql_fetch_array($resultp)){
							// If rowp success get  ID
							$d_id_agente_modulo = $rowp["id_agente_modulo"];
							// Read every alert from source agent
							$sql2='SELECT * FROM talerta_agente_modulo WHERE id_agente_modulo = '.$o_id_agente_modulo;
							$result3=mysql_query($sql2);
							while ($row3=mysql_fetch_array($result3)){
								$o_id_alerta = $row3["id_alerta"];
								$o_al_campo1 = $row3["al_campo1"];
								$o_al_campo2 = $row3["al_campo2"];
								$o_al_campo3 = $row3["al_campo3"];
								$o_descripcion = $row3["descripcion"];
								$o_dis_max = $row3["dis_max"];
								$o_dis_min = $row3["dis_min"];
								$o_time_threshold = $row3["time_threshold"];
								$o_last_fired = "2001-01-01 00:00:00";
								$o_max_alerts = $row3["max_alerts"];
								$o_min_alerts = $row3["min_alerts"];
								$o_times_fired = 0;

								$o_alert_text = $row3["alert_text"];
								$o_time_from = $row3["time_from"];
								$o_time_to = $row3["time_to"];
								$o_monday = $row3["monday"];
								$o_tuesday = $row3["tuesday"];
								$o_wednesday = $row3["wednesday"];
								$o_thursday = $row3["thursday"];
								$o_friday = $row3["friday"];
								$o_saturday = $row3["saturday"];
								$o_sunday = $row3["sunday"];
								$o_recovery_notify = $row3["recovery_notify"];
								$o_priority = $row3["priority"];
								$o_al_f2_recovery= $row3["al_f2_recovery"];
								$o_al_f3_recovery= $row3["al_f3_recovery"];	

								// Insert
								$sql_al="INSERT INTO talerta_agente_modulo (id_agente_modulo, id_alerta, al_campo1, al_campo2, al_campo3, descripcion, dis_max, dis_min, time_threshold, last_fired, max_alerts, times_fired, min_alerts,
								alert_text, time_from, time_to, monday, tuesday, wednesday, 
								thursday, friday, saturday, sunday, recovery_notify, priority,
								al_f2_recovery, al_f3_recovery ) 
								VALUES ( $d_id_agente_modulo , $o_id_alerta, '$o_al_campo1',
								'$o_al_campo2', '$o_al_campo3', '$o_descripcion', $o_dis_max,
								$o_dis_min, $o_time_threshold, '$o_last_fired', $o_max_alerts,
								$o_times_fired, $o_min_alerts, '$o_alert_text', '$o_time_from',
								'$o_time_to', '$o_monday', '$o_tuesday', '$o_wednesday', 
								'$o_thursday', '$o_friday', '$o_saturday', '$o_sunday', 
								$o_recovery_notify, $o_priority, '$o_al_f2_recovery', 
								'$o_al_f3_recovery' )";
								$result_al=mysql_query($sql_al);
								echo "<br>&nbsp;&nbsp;".$lang_label["copyale"]." ->".$o_descripcion;
							}
						} else 
							echo "<br><h3 class='error'>ERROR: ".$lang_label["notfoundmod"].$o_nombre.$lang_label["inagent"].dame_nombre_agente($d_id_agente)."</h3>";
					} //while
				} // for
			} // Alerts
		} // for each destination agent
	} //end if copy modules or alerts


	// -----------
	// DELETE DATA
	// -----------

	elseif (isset($_POST["delete"])) {
		echo "<h2>".$lang_label["deletedata"]."</h2>";
		// Initial checkings
		
		//  if selected more than 0 agents
		$destino = $_POST["destino"];
		if (count($destino) <= 0){
			echo "<h3 class='error'>ERROR: ".$lang_label["noagents_del"]."</h3>";
			break;
		}
		
		// If selected modules or alerts
		if (isset($_POST["modules"]))
			$modulos = 1;
		else
			$modulos = 0;
		if (isset($_POST["alerts"]))
			$alertas = 1;
		else
			$alertas = 0;			
		if (($alertas + $modulos) >= 0)
		// Delete
		for ($a=0;$a <count($destino); $a++){ // for each agent
			$id_agente = $destino[$a];
			if ($modulos == 1){
				echo "<br>".$lang_label["deleting_data"]." -> ".dame_nombre_agente($id_agente);
			
				// Deleting data
				$sql1='SELECT * FROM tagente_modulo WHERE id_agente = '.$id_agente;
				$result1=mysql_query($sql1);
				while ($row=mysql_fetch_array($result1)){
					$sql_delete1="DELETE FROM tagente_datos WHERE id_agente_modulo=".$row["id_agente_modulo"];
					$sql_delete2="DELETE FROM tagente_datos_inc WHERE id_agente_modulo=".$row["id_agente_modulo"];
					$sql_delete3="DELETE FROM tagente_datos_string WHERE id_agente_modulo=".$row["id_agente_modulo"];
					$result=mysql_query($sql_delete1);
					$result=mysql_query($sql_delete2);
					$result=mysql_query($sql_delete3);
				}
			
			// Delete conf
			$sql_delete5 ="DELETE FROM tagente_modulo WHERE id_agente = ".$id_agente; // delete from table tagente_modulo
			$sql_delete6 ="DELETE FROM tagente_estado WHERE id_agente = ".$id_agente; // detele from table tagente_estado
			$result=mysql_query($sql_delete5);
			$result=mysql_query($sql_delete6);		
			}
			// delete alerts definitions
			if ($alertas == 1){ 
				// delete data
				$sql1='SELECT * FROM tagente_modulo WHERE id_agente = '.$id_agente;
				$result1=mysql_query($sql1);
				while ($row=mysql_fetch_array($result1)){
					$sql_delete1="DELETE FROM talerta_agente_modulo WHERE id_agente_modulo=".$row["id_agente_modulo"];
					$result = mysql_query($sql_delete1);
				} // while			
			}//if 
		}// for
	}//delete
	elseif (isset($_POST["delete_agent"])) {
		echo "<h2>".lang_string("delete_agents")."</h2>";
		// Initial checkings
		
		//  if selected more than 0 agents
		$destino = $_POST["destino"];
		if (count($destino) <= 0){
			echo "<h3 class='error'>ERROR: ".$lang_label["noagents_del"]."</h3>";
			break;
		}
		
		// Delete
		for ($a=0;$a <count($destino); $a++){ // for each agent
			$id_agente = $destino[$a];
			
			echo "<br>".$lang_label["deleting_data"]." -> ".dame_nombre_agente($id_agente);
		
			// Deleting data
			$sql1='SELECT * FROM tagente_modulo WHERE id_agente = '.$id_agente;
			$result1=mysql_query($sql1);
			while ($row=mysql_fetch_array($result1)){
				$sql_delete1="DELETE FROM tagente_datos WHERE id_agente_modulo=".$row["id_agente_modulo"];
				$sql_delete2="DELETE FROM tagente_datos_inc WHERE id_agente_modulo=".$row["id_agente_modulo"];
				$sql_delete3="DELETE FROM tagente_datos_string WHERE id_agente_modulo=".$row["id_agente_modulo"];
				$result=mysql_query($sql_delete1);
				$result=mysql_query($sql_delete2);
				$result=mysql_query($sql_delete3);
			}
			
			// Delete conf
			$sql_delete5 ="DELETE FROM tagente_modulo WHERE id_agente = ".$id_agente; // delete from table tagente_modulo
			$sql_delete6 ="DELETE FROM tagente_estado WHERE id_agente = ".$id_agente; // detele from table tagente_estado
			$result=mysql_query($sql_delete5);
			$result=mysql_query($sql_delete6);		
			
			// delete alerts definitions
			
			// delete data
			$sql1='SELECT * FROM tagente_modulo WHERE id_agente = '.$id_agente;
			$result1=mysql_query($sql1);
			while ($row=mysql_fetch_array($result1)){
				$sql_delete1="DELETE FROM talerta_agente_modulo WHERE id_agente_modulo=".$row["id_agente_modulo"];
				$result = mysql_query($sql_delete1);
			} // while			
			
            // delete agent
            $sql1='DELETE FROM tagente WHERE id_agente = '.$id_agente;
			$result1=mysql_query($sql1);
		}// for
	}//delete

	// ============	
	// Form view
	// ============
	} else { 
		
		// title
		echo '<h2>'.lang_string ("agent_conf"). '&gt;'. lang_string ("config_manage").'</h2>';
		echo '<form method="post" action="index.php?sec=gagente&sec2=godmode/agentes/manage_config&operacion=1">';
		echo "<table width='650' border='0' cellspacing='4' cellpadding='4' class='databox'>";
		
		// Source group
		echo '<tr><td class="datost"><b>'. lang_string ("Source group"). '</b><br><br>';
		echo '<select name="id_group" style="width:200px">';
		if ($id_group != 0)
			echo "<option value=$id_group>".dame_nombre_grupo ($id_group);
		echo "<option value=0>".lang_string ("All");
		list_group ($config["id_user"]);
		echo '</select>';
		echo '&nbsp;&nbsp;';
		echo '<input type=submit name="update_group" class="sub upd"  value="'.lang_string("Filter").'">';
		echo '<br><br>';

		// Source agent
		echo '<b>'. lang_string ("source_agent").'</b><br><br>';

		// Show combo with SOURCE agents
		if ($id_group != 0)
			$sql1 = "SELECT * FROM tagente WHERE id_grupo = $id_group ORDER BY nombre ";
		else
			$sql1 = 'SELECT * FROM tagente ORDER BY nombre';
		echo '<select name="origen" style="width:200px">';			
		if (($update_agent != 1) AND ($origen != -1))
			echo "<option value=".$_POST["origen"].">".dame_nombre_agente($origen)."</option>";
		$result=mysql_query($sql1);
		while ($row=mysql_fetch_array($result)){
			if (give_acl ($config["id_user"], $row["id_grupo"], "AR")){
				if ( $origen != $row["id_agente"])
					echo "<option value=".$row["id_agente"].">".$row["nombre"]."</option>";
			}
		}
		echo '</select>';

		echo '&nbsp;&nbsp;';
		echo '<input type=submit name="update_agent" class="sub upd" value="'.lang_string ("get_info").'">';
		echo '<br><br>';

		// Source Module(s)
		echo "<b>".$lang_label["modules"]."</b><br><br>";
		echo "<select name='origen_modulo[]' size=10 multiple=yes style='width: 250px;'>";
		if ( (isset($_POST["update_agent"])) AND (isset($_POST["origen"])) ) {
				// Populate Module/Agent combo
			$agente = $_POST["origen"];
			$sql1="SELECT * FROM tagente_modulo WHERE id_agente = ".$agente. " ORDER BY nombre";	
			$result = mysql_query($sql1);
			while ($row=mysql_fetch_array($result)){
				echo "<option value=".$row["id_agente_modulo"].">".$row["nombre"]."</option>";	
			}
		}
		echo "</select>";
		
		echo '<td class="datost">';
		echo '<b>'.lang_string ("copy_conf"). '</b><br><br>';
		echo '<table>';
		echo '<tr class=datos><td>'.lang_string ("modules");
		echo '<td><input type="checkbox" name="modules" value="1" class="chk">';
		echo '<tr class=datos><td>'.lang_string ("alerts");
		echo '<td><input type="checkbox" name="alerts" value="1" class="chk">';
		echo '</table>';
		

		// Destination agent
		echo '<tr><td class="datost">';
		echo '<b>'.$lang_label["toagent"].'</b><br><br>';
		echo "<select name=destino[] size=10 multiple=yes style='width: 250px;'>";
		$sql1='SELECT * FROM tagente ORDER BY nombre';
		$result=mysql_query($sql1);
		while ($row=mysql_fetch_array($result)){
			if (give_acl ($config["id_user"], $row["id_grupo"], "AW"))
				echo "<option value=".$row["id_agente"].">".$row["nombre"]."</option>";
		}
		echo '</select>';
		
		// Form buttons
		echo '<td align="left" class="datosb">';
        echo "<br><br>";
		echo '<input type="submit" name="copy" class="sub copy" value="'.lang_string ("copy").'" onClick="if (!confirm("'.lang_string ("are_you_sure").'")) return false;>';
        echo "<br><br>";
		echo '<input type="submit" name="delete" class="sub delete" value="'. lang_string("delete").'" onClick="if (!confirm("'.lang_string ("are_you_sure").'")) return false;>';
        echo "<br><br>";
        echo '<input type="submit" name="delete_agent" class="sub delete" value="'. lang_string("Delete Agents").'" onClick="if (!confirm("'.lang_string ("are_you_sure").'")) return false;>';

		echo '<tr><td colspan=2>';
		echo '</div></td></tr>';
		echo '</table>';

	}

?>
