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
	$monitor_not_init=0;
	$total_agents = 0;
	$data_checks = 0;
	$data_unknown =0;
	$data_not_init = 0;
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
			$sql1 = "SELECT tagente.id_agente, tagente_estado.estado, tagente_estado.datos, tagente_estado.current_interval, tagente_estado.utimestamp, tagente_estado.id_agente_modulo FROM tagente, tagente_estado WHERE tagente.disabled = 0 AND tagente.id_grupo = $migrupo AND tagente.id_agente = tagente_estado.id_agente ";
			if ($result1 = mysql_query ($sql1)){
				while ($row1 = mysql_fetch_array ($result1)) {
					$id_agente = $row1[0];
					$estado = $row1[1];
					$datos = $row1[2];
					$module_interval = $row1[3];
					$utimestamp = $row1[4];
					$seconds = $ahora_sec - $utimestamp;
					$id_agente_modulo = $row1[5];
					if ($estado != 100){
						// Monitor check
						$monitor_checks++;
						if ($utimestamp == 0)
							$monitor_not_init++;
						elseif ($seconds >= ($module_interval*2))
							$monitor_unknown++;
						elseif ($datos != 0) {
							$monitor_ok++;
						} else {
							$monitor_bad++;
						}
						// Alert
						if ($utimestamp != 0){
							$sql2 = "SELECT times_fired FROM talerta_agente_modulo WHERE id_agente_modulo = $id_agente_modulo";
							if ($result2 = mysql_query ($sql2)){
								if ($row2 = mysql_fetch_array ($result2)){
									$monitor_alert_total++;
									if ($row2[0] > 0)
										$monitor_alert++;
								}
							}
						}
					} else {
						// Data check
						if ($utimestamp == 0)
							$data_not_init++;
						elseif ($seconds >= ($module_interval*2))
							$data_unknown++;
						$data_checks++;
						// Alert
						if ($utimestamp != 0){
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
	$data[11] = $data_not_init;
	$data[12] = $monitor_not_init;
	return $data;
}

function event_reporting ($id_agent, $period){
	require("config.php");
	require ("include/languages/language_".$config["language"].".php");
	$id_user=$_SESSION["id_usuario"];
	global $REMOTE_ADDR;
	$ahora = date("U");
	$mytimestamp = $ahora - $period;
	
	echo "<table cellpadding='4' cellspacing='4' width='100%' class='databox'>";
	echo "<tr>";
	echo "<th>".$lang_label["status"]."</th>";
	echo "<th>".$lang_label["event_name"]."</th>";
	echo "<th>".$lang_label["id_user"]."</th>";
	echo "<th>".$lang_label["timestamp"]."</th>";
	$color = 1;
	$id_evento = 0;
	
	$sql2="SELECT * FROM tevento WHERE id_agente = $id_agent AND utimestamp > '$mytimestamp'";
	
	// Make query for data (all data, not only distinct).
	$result2=mysql_query($sql2);
	while ($row2=mysql_fetch_array($result2)){
		$id_grupo = $row2["id_grupo"];
		if (give_acl($id_user, $id_grupo, "IR") == 1){ // Only incident read access to view data !
			$id_group = $row2["id_grupo"];
			if ($color == 1){
				$tdcolor = "datos";
				$color = 0;
			}
			else {
				$tdcolor = "datos2";
				$color = 1;
			}
			echo "<tr><td class='$tdcolor' align='center'>";
			if ($row2["estado"] == 0)
				echo "<img src='images/dot_red.png'>";
			else
				echo "<img src='images/dot_green.png'>";
			echo "<td class='$tdcolor'>".$row2["evento"];
			echo "<td class='$tdcolor'>";
			if ($row2["estado"] <> 0)
				echo substr($row2["id_usuario"],0,8)."<a href='#' class='tip'> <span>".dame_nombre_real($row2["id_usuario"])."</span></a>";
			echo "<td class='".$tdcolor."f9'>".$row2["timestamp"];
			echo "</td></tr>";
		}
	}
	echo "</table>";
}

function alert_reporting ($id_agent_module){
	global $config;
	require ("include/languages/language_".$config["language"].".php");

	$query_gen='SELECT talerta_agente_modulo.alert_text, talerta_agente_modulo.id_alerta, talerta_agente_modulo.descripcion, talerta_agente_modulo.last_fired, talerta_agente_modulo.times_fired, tagente_modulo.nombre, talerta_agente_modulo.dis_max, talerta_agente_modulo.dis_min, talerta_agente_modulo.max_alerts, talerta_agente_modulo.time_threshold, talerta_agente_modulo.min_alerts, talerta_agente_modulo.id_agente_modulo, tagente_modulo.id_agente_modulo FROM tagente_modulo, talerta_agente_modulo WHERE tagente_modulo.id_agente_modulo = talerta_agente_modulo.id_agente_modulo and talerta_agente_modulo.id_agente_modulo  = '.$id_agent_module.' ORDER BY tagente_modulo.nombre';
	$result_gen=mysql_query($query_gen);
	if (mysql_num_rows ($result_gen)) {
		echo "<table cellpadding='4' cellspacing='4' width='100%' border=0 class='databox'>";
		echo "<tr>
		<th>".$lang_label["status"]."</th>
		<th>".$lang_label["description"]."</th>
		<th>".$lang_label["time_threshold"]."</th>
		<th>".$lang_label["last_fired"]."</th>
		<th>".$lang_label["times_fired"]."</th>";
		
		$color=1;
		while ($data=mysql_fetch_array($result_gen)){
			if ($color == 1){
				$tdcolor = "datos";
				$color = 0;
			}
			else {
				$tdcolor = "datos2";
				$color = 1;
			}
			echo "<tr>";
			if ($data["times_fired"] <> 0)
				echo "<td class='".$tdcolor."' align='center'><img src='images/dot_red.png'></td>";
			else
				echo "<td class='".$tdcolor."' align='center'><img src='images/dot_green.png'></td>";
			echo "<td class='".$tdcolor."'>".$data["descripcion"]."</td>";
			echo "<td  align='center' class='".$tdcolor."'>".human_time_description($data["time_threshold"]);
			if ($data["last_fired"] == "0000-00-00 00:00:00") {
				echo "<td align='center' class='".$tdcolor."f9'>".$lang_label["never"]."</td>";
			}
			else {
				echo "<td align='center' class='".$tdcolor."f9'>".human_time_comparation ($data["last_fired"])."</td>";
			}
			echo "<td align='center' class='".$tdcolor."'>".$data["times_fired"]."</td>";

		}
		echo '</table>';
	}
}

?>
