<?php

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development and project architecture and management

function return_module_SLA ($id_agent_module, $period, $min_value, $max_value){
	require("config.php");
	$datelimit = time() - $period; // limit date
	$id_agent = give_db_value ("id_agente", "tagente_modulo", "id_agente_modulo", $id_agent_module);
	// Get the whole interval of data
	$query1="SELECT * FROM tagente_datos WHERE id_agente = $id_agent AND id_agente_modulo = $id_agent_module AND utimestamp > $datelimit";
	$resq1=mysql_query($query1);
	$last_data = "";

	$total_badtime = 0;
	$interval_begin = 0;
	$interval_last = 0;
	
	if ($resq1 != 0){
		while ($row=mysql_fetch_array($resq1)){
			if ( ($row["datos"] > $max_value) OR ($row["datos"] < $min_value)){
				if ($interval_begin == 0){
					$interval_begin = $row["utimestamp"];
				}
			} elseif ($interval_begin != 0){
				// Here ends interval with data outside valid values,
				// Need to add this time to counter
				$interval_last = $row["utimestamp"];
				$temp_time = $interval_last - $interval_begin;
				$total_badtime = $total_badtime + $temp_time;
				$interval_begin = 0;
				$interval_last = 0;
			}
		}
	} else
		return 100;
	$result = 100 - ($total_badtime / $period ) * 100;
	return $result;
}

function general_stats ( $id_user, $id_group = 0) {
	if ($id_group <= 0)
		// Get group list that user has access
		$mis_grupos = list_group2 ($id_user);
	else
		$mis_grupos[0] = $id_group;
		
	$contador_grupo  = 0;
	$contador_agente = 0;
	$array_index     = 0;
	
	$monitor_checks = 0;
	$monitor_ok = 0;
	$monitor_bad = 0;
	$monitor_unknown =0;
	$monitor_alert = 0;
	$total_agents = 0;
	$data_checks = 0;
	$data_unknown =0;
	$data_alert = 0;
	$data_alert_total = 0;
	$monitor_alert_total = 0;
	$ahora=date("Y/m/d H:i:s");
	$ahora_sec = strtotime($ahora);
	
	// Prepare data to show
	// For each valid group for this user, take data from agent and modules
	foreach ($mis_grupos as $migrupo) {
		if ($migrupo != "") {
			$existen_agentes = 0;
			$sql0 = "SELECT COUNT(id_agente) FROM tagente WHERE id_grupo = $migrupo AND disabled = 0";
			$result0 = mysql_query ($sql0);
			$row0 = mysql_fetch_array ($result0);
			$total_agents = $total_agents + $row0[0];
			if ($row0[0] > 0)
				$existen_agentes = 1;

			// SQL Join to get monitor status for agents belong this group
			$sql1 = "SELECT tagente.id_agente, tagente_estado.estado, tagente_estado.datos, tagente_estado.current_interval, tagente_estado.utimestamp, tagente_estado.id_agente_modulo FROM tagente, tagente_estado WHERE tagente.disabled = 0 AND tagente.id_grupo = $migrupo AND tagente.id_agente = tagente_estado.id_agente";
			if ($result1 = mysql_query ($sql1)){
				while ($row1 = mysql_fetch_array ($result1)) {
					$id_agente = $row1[0];
					$estado = $row1[1];
					$datos = $row1[2];
					$module_interval = $row1[3];
					$seconds = $ahora_sec - $row1[4];
					$id_agente_modulo = $row1[5];
					if ($estado != 100){
						// Monitor check
						$monitor_checks++;
						if ($seconds >= ($module_interval*2))
							$monitor_unknown++;
						elseif ($datos != 0) {
							$monitor_ok++;
						} else {
							$monitor_bad++;
						}
						// Alert
						$sql2 = "SELECT times_fired FROM talerta_agente_modulo WHERE id_agente_modulo = $id_agente_modulo";
						if ($result2 = mysql_query ($sql2)){
							if ($row2 = mysql_fetch_array ($result2)){
								$monitor_alert_total++;
								if ($row2[0] > 0)
									$monitor_alert++;
							}
						}
					} else {
						// Data check
						if ($seconds >= ($module_interval*2))
							$data_unknown++;
						$data_checks++;
						// Alert
						$sql2 = "SELECT times_fired FROM talerta_agente_modulo WHERE id_agente_modulo = $id_agente_modulo";
						if ($result2 = mysql_query ($sql2)){
							if ($row2 = mysql_fetch_array ($result2)) {
								$data_alert_total++;
								if ($row2[0] > 0)
									$data_alert++;
							}
						}
					}
				}
			}
		}
	}
	$data =  array();
	$data[0] = $monitor_checks;
	$data[1] = $monitor_ok;
	$data[2] = $monitor_bad;
	$data[3] = $monitor_unknown;
	$data[4] = $monitor_alert;
	$data[5] = $total_agents;
	$data[6] = $data_checks;
	$data[7] = $data_unknown;
	$data[8] = $data_alert;
	$data[9] = $data_alert_total;
	$data[10] = $monitor_alert_total;
	return $data;
}

?>
