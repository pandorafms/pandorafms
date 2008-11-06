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
require ("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "LM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Agent Config Management Admin section");
	require ("general/noaccess.php");
	return;
}


$id_group = get_parameter ("id_group", 0);
$origen = get_parameter ("origen", -1);
$update_agent = get_parameter ("update_agent", -1);
$update_group = get_parameter ("update_group", -1);
$destino = get_parameter_post ("destino",array ());
$origen_modulo = get_parameter_post ("origen_modulo", array ());

// Operations
// ---------------

// DATA COPY
// ---------
if (isset($_POST["copy"])) {
	echo "<h2>".__('Data Copy')."</h2>";
	
	if (empty ($destino)) {
		echo '<h3 class="error">ERROR: '.__('No selected agents to copy').'</h3>';
		return;
	} 
	
	if (empty ($origen_modulo)) {
		echo '<h3 class="error">ERROR: '.__('No modules have been selected').'</h3>';
		return;
	}

	// If selected modules or alerts
	if (isset($_POST["modules"])) {
		$modulos = 1;
	} else {
		$modulos = 0;
	}
		
	if (isset($_POST["alerts"])) {
		$alertas = 1;
	} else {
		$alertas = 0;
	}
	
	if (($alertas + $modulos) == 0){
		echo '<h3 class="error">ERROR: '.__('You must check modules and/or alerts to be copied').'</h3>';
		return;
	}
		
	// Copy
	// ----	
	$errors = 0;
	$id_new_module = 0;
	process_sql ("SET AUTOCOMMIT = 0;");
	process_sql ("START TRANSACTION;"); //Start a transaction
	
	foreach ($origen_modulo as $id_module) {
		//For each selected module
		$module = get_db_row ("tagente_modulo", "id_agente_modulo", $id_module);
		
		foreach ($destino as $id_agent_dest) {
			//For each destination agent
			
			if ($modulos == 1) {
				echo '<br /><br />'.__('Copying module').'<b> ['.dame_nombre_agente ($origen).' - '.$module["nombre"].'] -> ['.dame_nombre_agente ($id_agent_dest).']</b>';
				$sql = sprintf ("INSERT INTO tagente_modulo 
					(id_agente, id_tipo_modulo, descripcion, nombre, max, min, module_interval, tcp_port, tcp_send, tcp_rcv, 
					snmp_community, snmp_oid, ip_target, id_module_group, flag, id_modulo, disabled, id_export, 
					plugin_user, plugin_pass, plugin_parameter, id_plugin, post_process, prediction_module, max_timeout) 
					VALUES (%d,".$module["id_tipo_modulo"].",'".$module["descripcion"]."','".$module["nombre"]."',".$module["max"].",".$module["min"].",".$module["module_interval"].",".$module["tcp_port"].",'".$module["tcp_send"]."','".$module["tcp_rcv"]."',
					'".$module["snmp_community"]."','".$module["snmp_oid"]."','%s',".$module["id_module_group"].",".$module["flag"].",".$module["id_modulo"].",".$module["disabled"].",".$module["id_export"].",
					'".$module["plugin_user"]."','".$module["plugin_pass"]."','".$module["plugin_parameter"]."',".$module["id_plugin"].",'".$module["post_process"]."',".$module["prediction_module"].",".$module["max_timeout"].")",
					$id_agent_dest,get_agent_address ($id_agent_dest));

				$id_new_module = process_sql ($sql, "insert_id");
				if (empty ($id_new_module)) {
					$errors++;
				} else {
					switch ($module["id_tipo_modulo"]) {
						case 2:
						case 6:
						case 9:
						case 100:
						case 21:
						case 18:
							$sql = sprintf ("INSERT INTO tagente_estado (id_agente_modulo, datos, timestamp, cambio, estado, id_agente, utimestamp) 
								VALUES (%d, 0,'0000-00-00 00:00:00',0,0, %d, 0)", $id_new_module, $id_agent_dest);
						default:
							$sql = sprintf ("INSERT INTO tagente_estado (id_agente_modulo, datos, timestamp, cambio, estado, id_agente, utimestamp) 
								VALUES (%d, 0,'0000-00-00 00:00:00',0,100, %d, 0)", $id_new_module, $id_agent_dest);
					}
					$result = process_sql ($sql);
					if ($result === false)
						$errors++;
				}//If empty id_new_module
			} //If modulos
			
			if ($alertas == 1) {
				if (empty ($id_new_module)) {
					//If we didn't copy modules or if we
					//didn't create new modules we have to
					//look for the module id
					$sql = sprintf ("SELECT id_agente_modulo FROM tagente_modulo WHERE nombre = '%s' AND id_agente = %d", $module["nombre"], $id_agent_dest);
					$id_new_module = get_db_sql ($sql);
					if (empty ($id_new_module)) {
						continue; //If we can't find a module belonging to this agent with the same name, skip the loop
					}
				}

				
				$module_alerts = get_db_all_rows_field_filter ("talerta_agente_modulo", "id_agente_modulo", $id_module);	
				if (empty ($module_alerts)) {
					$module_alerts = array ();
				}
				
				foreach ($module_alerts as $alert) {
					echo '<br /><br />'.__('Copying alert').'<b> ['.dame_nombre_agente ($origen).' - '.$module["nombre"].'] -> ['.dame_nombre_agente ($id_agent_dest).']</b>';
					if (!empty ($alert["id_agent"])) {
						//Compound alert
						$alert["id_agent"] = $id_agent_dest;
					}
					$sql = sprintf ("INSERT INTO talerta_agente_modulo 
						(id_agente_modulo, id_alerta, al_campo1, al_campo2, al_campo3, descripcion, dis_max, dis_min,
						time_threshold, max_alerts, module_type, min_alerts, alert_text, disable, time_from,
						time_to, id_agent, monday, tuesday, wednesday, thursday, friday, saturday, sunday,
						recovery_notify, priority, al_f2_recovery, al_f3_recovery) 
						VALUES (
						%d,%d,'%s','%s','%s','%s',%d,%d,
						%d,%d,%d,%d,'%s',%d,'%s',
						'%s',%d,%d,%d,%d,%d,%d,%d,%d,
						%d,%d,'%s','%s')",
						$id_new_module,$alert["id_alerta"],$alert["al_campo1"],$alert["al_campo2"],$alert["al_campo3"],$alert["descripcion"],$alert["dis_max"],$alert["dis_min"],
						$alert["time_threshold"],$alert["max_alerts"],$alert["module_type"],$alert["min_alerts"],$alert["alert_text"],$alert["disable"],$alert["time_from"],
						$alert["time_to"],$alert["id_agent"],$alert["monday"],$alert["tuesday"],$alert["wednesday"],$alert["thursday"],$alert["friday"],$alert["saturday"],$alert["sunday"],
						$alert["recovery_notify"],$alert["priority"],$alert["al_f2_recovery"],$alert["al_f3_recovery"]);
					
					$new_alert = process_sql ($sql, "insert_id");
					
					if ($new_alert === false) {
						$errors++;
					} elseif (!empty ($alert["id_agent"])) {
						$sql = sprintf ("SELECT operation FROM tcompound_alert WHERE id_aam = %d", $alert["id_aam"]);
						$result = get_db_all_row_sql ($sql);
						
						if ($result === false)
							continue; // This alert is supposed to be part of a 
								  // compound alert but there is no entry for 
								  // it in the tcompound_alert table so we skip this																			            
						
						foreach ($result as $comp_alert) {
							$sql = sprintf ("INSERT INTO tcompound_alert (id_aam, operation) VALUES (%d, '%s')",$new_alert,$comp_alert["operation"]);
							$result = process_sql ($sql);
							if ($result === false)
								$errors++;
						} //foreach compound alert
					} //if-elseif compound alert
				} //foreach alert
			} //if alerts
		} //Foreach destino
	} //Foreach origen_modulo

	if ($errors > 1) {
		echo '<h3 class="error">'.__('There was an error copying the module, the copy has been cancelled').'</h3>';
		process_sql ("ROLLBACK;");
	} else {
		echo '<h3 class="suc">'.__('Successfully copied module').'</h3>';
		process_sql ("COMMIT;");
	}
	process_sql ("SET AUTOCOMMIT = 1;");
	return; //Page shouldn't continue anymore
} //end of copy modules or alerts

// -----------
// DELETE DATA
// -----------
if (isset ($_POST["delete"])) {
        echo "<h2>".__('Agent Module Data Deletion')."</h2>";

        if (empty ($destino)) {
                echo '<h3 class="error">ERROR: '.__('No selected agents to copy').'</h3>';
                return;
        }

        if (empty ($origen_modulo)) {
                echo '<h3 class="error">ERROR: '.__('No modules have been selected').'</h3>';
                return;
        }


	
        // If selected modules or alerts
	if (isset($_POST["alerts"])) {
		$alertas = 1;
	} else {
		$alertas = 0;
	}
	
	if (isset($_POST["modules"])) {
                $modulos = 1;
		$alertas = 1;
        } else {
                $modulos = 0;
        }

        if (($alertas + $modulos) == 0){
                echo '<h3 class="error">ERROR: '.__('You must check modules and/or alerts to be deleted').'</h3>';
                return;
        }

        // Deletion
        // ---- 
        $errors = 0;
        
	process_sql ("SET AUTOCOMMIT = 0;");
        process_sql ("START TRANSACTION;"); //Start a transaction

	function temp_sql_delete ($table, $row, $value) {
		global $errors; //Globalize the errors variable
		$sql = sprintf ("DELETE FROM %s WHERE %s = %s", $table, $row, $value);
		
		$result = process_sql ($sql);
		
		if ($result === false)
			$errors++;
	}

        foreach ($origen_modulo as $id_module_src) {
		$nombre_src = get_db_value ("nombre", "tagente_modulo", "id_agente_modulo", $id_module_src);
		
		foreach ($destino as $agent_dest) {
			$sql = sprintf ("SELECT id_agente_modulo FROM tagente_modulo WHERE nombre = '%s' AND id_agente = %d", $nombre_src, $agent_dest);
			$id_module_dest = get_db_sql ($sql);
			if ($id_module_dest === false)
				continue; //If we don't have a module like that in the agent, then don't try deleting

			if ($alertas == 1) {
				//Alert
				temp_sql_delete ("tcompound_alert", "id_aam", "ANY(SELECT id_aam FROM talerta_agente_modulo WHERE id_agente_modulo = ".$id_module_dest.")");
				temp_sql_delete ("talerta_agente_modulo", "id_agente_modulo", $id_module_dest);
			}
			
			if ($modulos == 1) {
				//Standard data
				temp_sql_delete ("tagente_datos", "id_agente_modulo", $id_module_dest);
	
	                	//Incremental Data
				temp_sql_delete ("tagente_datos_inc", "id_agente_modulo", $id_module_dest);

				//String data
				temp_sql_delete ("tagente_datos_string", "id_agente_modulo", $id_module_dest);
			
				//Data image
				temp_sql_delete ("tagent_data_image", "id_agent_module", $id_module_dest);
			
				//Events (up/down monitors)
				temp_sql_delete ("tevento", "id_agentmodule", $id_module_dest);
			
				//Graphs, layouts & reports
				temp_sql_delete ("tgraph_source", "id_agent_module", $id_module_dest);
				temp_sql_delete ("tlayout_data", "id_agente_modulo", $id_module_dest);
				temp_sql_delete ("treport_content", "id_agent_module", $id_module_dest);

				//The status of the module
				temp_sql_delete ("tagente_estado", "id_agente_modulo", $id_module_dest);

				//The actual modules, don't put anything based on
				//tagente_modulo after this
				temp_sql_delete ("tagente_modulo", "id_agente_modulo", $id_module_dest);
			} //if modulos
		} //foreach destino
	} //foreach origen_modulo
	
	if ($errors > 1) {
		echo '<h3 class="error">'.__('There was an error removing the module data, the removal has been cancelled').'</h3>';
		process_sql ("ROLLBACK;");
	} else {
		echo '<h3 class="suc">'.__('Successfully removed module data').'</h3>';
		process_sql ("COMMIT;");
	}
	process_sql ("SET AUTOCOMMIT = 1;");
	return; //Page shouldn't continue anymore															
} //if $_POST['delete']

// -----------
// DELETE AGENT
// -----------

if (isset ($_POST["delete_agent"])) {
	echo "<h2>".__('Deleting Agent')."</h2>";
	// Initial checkings
	
	//  if selected more than 0 agents
	$destino = get_parameter_post ("destino", array ());
	
	if (empty ($destino)) {
		echo '<h3 class="error">ERROR: '.__('You must select at least one agent to be removed').'</h3>';
		return;
	}
	
	$result = delete_agent ($destino);	
	
	if ($result === false) {
		echo '<h3 class="error">'.__('There was an error removing the agents. Removal has been cancelled').'</h3>';
	} else {
		echo '<h3 class="suc">'.__('Successfully removed agents').'</h3>';
	}

	return;
}
																		
	
// ============	
// Form view
// ============
		
// title
echo '<h2>'.__('Agent configuration'). ' &gt; '. __('Configuration Management').'</h2>';
echo '<form method="post" action="index.php?sec=gagente&sec2=godmode/agentes/manage_config&operacion=1">';
echo '<table width="650" border="0" cellspacing="4" cellpadding="4" class="databox">';
	
// Source group
echo '<tr><td class="datost"><b>'. __('Source group'). '</b><br /><br />';
$groups = get_user_groups ($config['id_user']);
	
print_select ($groups, "id_group", $id_group, 'javascript:this.form.submit();', '', 0, false, false, false, '" style="width:200px');
echo '<noscript>&nbsp;&nbsp;';
print_submit_button (__('Filter'), "update_group", false, 'class="sub upd"');
echo '</noscript><br /><br />';
	
// Source agent
echo '<b>'. __('Source agent').'</b><br /><br />';

// Show combo with SOURCE agents
if ($id_group > 1) { //Group -1, 0 and 1 all mean that we should select ALL
	$result = get_db_all_rows_field_filter ("tagente", "id_grupo", $id_group, "nombre");
} else {
	$result = get_db_all_rows_in_table ("tagente", "nombre");
}
	
if ($result === false) {
	$result = array ();
	$result[0]["id_grupo"] = 0;
	$result[0]["id_agente"] = 0;
	$result[0]["nombre"] = __('No Agents in this Group');
}

$agents = array ();
foreach ($result as $row) {
	if (give_acl ($config["id_user"], $row["id_grupo"], "AR"))
		$agents[$row["id_agente"]] = $row["nombre"];
}

if ($origen == -1 || ($id_group > 1 && dame_id_grupo ($origen) != $id_group)) {
	$origen = $result[0]["id_agente"]; 
	//If the agent selected is not in the group selected (that
	//happens if an agent was selected and then the group was changed) 
}

print_select ($agents, "origen", $origen, 'javascript:this.form.submit();', '', 0, false, false, false, '" style="width:200px');
echo '<noscript>&nbsp;&nbsp;';
print_submit_button (__('Get Info'), "update_agent", false, 'class="sub upd"');
echo '</noscript><br /><br />';
	
// Source Module(s)
$result = get_db_all_rows_field_filter ("tagente_modulo", "id_agente", $origen, "nombre");
$modules = array ();

if ($result === false) {
	$result = array ();
	$result[0]["id_agente_modulo"] = -1;
	if ($origen > 0) {
		$result[0]["nombre"] = __('No modules for this agent');
	} else {
		$result[0]["nombre"] = __('No agent selected');
	}
}     
foreach ($result as $row) {
	$modules[$row["id_agente_modulo"]] = $row["nombre"];
}
	
echo '<b>'.__('Modules').'</b><br /><br />';
print_select ($modules, "origen_modulo[]", '', '', '', 0, false, true, false, '" style="width:250px'); 
echo '</td>';
		
echo '<td class="datost">';
echo '<b>'.__('Targets'). '</b>';
pandora_help ('manageconfig');
echo '<br /><br />';
echo '<table>';
echo '<tr><td class="datos">'.__('Modules').'</td><td class="datos">';
print_checkbox_extended ("modules", "1", false, false, '', 'class="chk"');

echo '</td></tr><tr><td class="datos">'.__('Alerts').'<td class="datos">';
print_checkbox_extended ("alerts", "1", false, false, '', 'class="chk"');
echo '</td></tr></table></td></tr>';
		

// Destination agent
$result = get_db_all_rows_in_table ("tagente", "nombre");
$agents = array ();
if ($result === false) {
	$result = array ();
}

foreach ($result as $row) {
	if (give_acl ($config["id_user"], $row["id_grupo"], "AW"))
		$agents[$row["id_agente"]] = $row["nombre"];
}

echo '<tr><td class="datost">';
echo '<b>'.__('To Agent(s):').'</b><br /><br />';
print_select ($agents, "destino[]", $destino, '', '', 0, false, true, false, '" style="width:250px');
echo '</td>';

// Form buttons
echo '<td align="left" class="datosb">';
echo "<br /><br />";
print_submit_button (__('Copy Modules/Alerts'), "copy", false, 'class="sub copy" onClick="if (!confirm("'.__('Are you sure?').'")) return false;"');
pandora_help ('manageconfig');
echo "<br /><br />";
print_submit_button (__('Delete Modules/Alerts'), "delete", false, 'class="sub delete" onClick="if (!confirm("'.__('Are you sure you want to delete these modules and alerts?').'")) return false;"');
pandora_help ('manageconfig');
echo "<br /><br />";
print_submit_button (__('Delete Agents'), "delete_agent", false, 'class="sub delete" onClick="if (!confirm("'.__('Are you sure you want to delete these agents?').'")) return false;"');
pandora_help ('manageconfig');
echo '</td></tr>';
echo '</table>';

?>
