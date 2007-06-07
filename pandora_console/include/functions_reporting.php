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


?>
