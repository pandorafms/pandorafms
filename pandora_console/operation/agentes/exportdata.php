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

// Copyright (c) 2007 Leandro Doctors, ldoctors@gusila.org.ar
// For code belongs to average_per_hourday matrix report code

// Load global vars
require("include/config.php");

check_login();

if (! give_acl ($config['id_user'], 0, "AR") && ! give_acl ($config['id_user'], 0, "AW")) {
	require ("general/noaccess.php");
	return;
}

function give_average_from_module ($id_agente, $id_agente_modulo, $hour, $day, $start_date, $end_date){
// Return average value from an agentmodule, for a specific hour of specific day of week,
// Only valid for non-string kind of data.
	require ("include/config.php");
	$query1 = "SELECT AVG(datos)
			FROM tagente_datos
			WHERE id_agente_modulo = ". $id_agente_modulo."
					AND HOUR(timestamp) = ".$hour."
					AND WEEKDAY(timestamp) = ".$day."
					AND timestamp >= '$start_date' 
					AND timestamp <= '$end_date'";

	if (($resq1 = mysql_query($query1)) AND ($row=mysql_fetch_array($resq1))) 
		return $row[0];
	else
		return 0;
}

function generate_average_table ($id_de_mi_agente, $id_agente_modulo, $fecha_inicio, $fecha_fin){
// Genera una tabla con los promedios de los datos de un módulo no-string
	require ("include/config.php");
	
	$dias_de_la_semana = array (__('Sunday'),__('Monday'), __('Tuesday'), __('Wednesday'), __('Thurdsday'), __('Friday'), __('Saturday'));
	$nombre_modulo = get_agentmodule_name ($id_agente_modulo);
	
	// Table header
	echo "<table border=0 cellpadding=4 cellspacing=4 width=600 class='databox'>";
	echo "<tr>
	<th rowspan='2'>".__('One hour')."</th>";
	echo "<th colspan='7'>".__('day')."</th>
	</tr>";
	echo "<tr>";
	for ($dia=0;$dia<7;++$dia)
		echo "<th>".$dias_de_la_semana[$dia]."</th>";
	echo "</tr>";
	$color = 0;	
	for ($hora=0;$hora<24;++$hora){
		if ($color == 1){
			$tdcolor = "datos";
			$color = 0;
		} else {
			$tdcolor = "datos2";
			$color = 1;
		}
		echo "<tr><th class='datos3'> $hora ".__('Hr')."</th>";
		for ($dia=0; $dia<7; ++$dia){
			echo "<td class='$tdcolor'>"; 
			echo format_numeric (give_average_from_module ($id_de_mi_agente, $id_agente_modulo, $hora, $dia, $fecha_inicio, $fecha_fin));
			echo "</td>";
		}
		echo "</tr>";
	}
	echo "</table>";
}


if ((isset($_POST["export"])) AND (! isset($_POST["update_agent"]))){
		
	if (isset($_POST["export_type"]))
		$export_type = $_POST["export_type"];
	else
		$export_type = 3; // Standard table;

	// Header
	echo "<h2>".__('Pandora Agents')." &gt; ";
	echo __('Database export results')."</h2>";

	if ($export_type == 1) { // CSV

		if (isset ($_POST["origen_modulo"])){
			$origen = $_POST["origen"];
			if (! give_acl ($config['id_user'], dame_id_grupo ($origen), "AR")) {
				audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
					"Trying to access Agent Export Data");
				require ("general/noaccess.php");
			}
			$origen_modulo = $_POST["origen_modulo"];
			$id_agentemodulo = $origen_modulo[0];
			$start_date =$_POST["start_date"];
			$end_date=$_POST["end_date"];
			$start_time =$_POST["start_time"];
			$end_time=$_POST["end_time"];
			$from_date = $start_date." ".$start_time;
			$to_date = $end_date." ".$end_time;
			
			$agentmodule_name = get_agentmodule_name ($origen_modulo[0]);
			echo __('Data from agent '). "<b>" . get_agent_name($origen). "-  $agentmodule_name</b> ". __('from'). " <b>". $from_date. "</b> ". __('to'). " <b>". $to_date. "</b><br>";

			echo "<a href='operation/agentes/export_csv.php?from_date=$from_date&to_date=$to_date&agent=$origen&agentmodule=$id_agentemodulo'><img src='images/disk.png'> ".__('Download file')."</a> pandora_export_$agentmodule_name.txt";
		} else
			echo "<b class='error'>".__('No module has been selected')."</b>";
	}

	if ($export_type == 2){ // Avarage day/hour matrix
		if (isset ($_POST["origen_modulo"])){
			$origen = $_POST["origen"];
			$origen_modulo = $_POST["origen_modulo"];
			$start_date =$_POST["start_date"];
			$end_date=$_POST["end_date"];
			$start_time =$_POST["start_time"];
			$end_time=$_POST["end_time"];
	
			$agentmodule_name = get_agentmodule_name ($origen_modulo[0]);
			$from_date = $start_date." ".$start_time;
			$to_date = $end_date." ".$end_time;

			echo __('Data from agent '). "<b>" . get_agent_name ($origen). "-  $agentmodule_name</b> ". __('from'). " <b>". $from_date. "</b> ". __('to'). " <b>". $to_date. "</b><br>";
			echo "<br>";

			// For each module
			for ($a=0;$a <count($origen_modulo); $a++){
				$id_modulo = $origen_modulo[$a];
				$tipo = get_moduletype_name (get_agentmodule_type ($id_modulo));

				if ($tipo != "generic_data_string")
					echo "<br>". generate_average_table  ($origen,$id_modulo,$from_date,$to_date);
			}
		}
	}
	
	if ($export_type == 3) { // Standard table
		if (isset ($_POST["origen_modulo"])){
			$origen = $_POST["origen"];
			if (! give_acl ($config['id_user'], dame_id_grupo ($origen), "AR")) {
				audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
					"Trying to access Agent Export Data");
				require ("general/noaccess.php");
			}
			$origen_modulo = $_POST["origen_modulo"];
			$agentmodule_name = get_agentmodule_name ($origen_modulo[0]);
			$start_date =$_POST["start_date"];
			$end_date=$_POST["end_date"];
			$start_time =$_POST["start_time"];
			$end_time=$_POST["end_time"];
	
			$from_date = $start_date." ".$start_time;
			$to_date = $end_date." ".$end_time;

			echo __('Data from agent '). "<b>" . get_agent_name($origen). "-  $agentmodule_name</b>". __(' from '). "<b>". $from_date. "</b>". __(' to '). "<b>". $to_date. "</b><br>";

			echo "<br><table cellpadding='4' cellspacing='4' width='600' class='databox'>";
			echo "<tr>
			<th class='datos'>".__('Module')."</th>
			<th class='datos'>".__('Data')."</th>
			<th class='datos'>Timestamp</th>";
	
			// Begin the render !	
			for ($a=0; $a <count($origen_modulo); $a++){ // For each module (not used multiple modules yet!)
				$id_modulo = $origen_modulo[$a];
				$sql1='SELECT * FROM tdatos WHERE id_agente = '.$origen;
				$tipo = get_moduletype_name (get_agentmodule_type ($id_modulo));
				if ($tipo == "generic_data_string")
					$sql1 = 'SELECT * FROM tagente_datos_string WHERE timestamp > "'.$from_date.'" AND timestamp < "'.$to_date.'" AND id_agente_modulo ='.$id_modulo.' ORDER BY timestamp DESC';
				else
					$sql1 = 'SELECT * FROM tagente_datos WHERE timestamp > "'.$from_date.'" AND timestamp < "'.$to_date.'" AND id_agente_modulo ='.$id_modulo.' ORDER BY timestamp DESC';
				$result1 = mysql_query ($sql1);
				$color=1;
				while ($row = mysql_fetch_array ($result1)){
					if ($color == 1){
						$tdcolor = "datos";
						$color = 0;
					} else {
						$tdcolor = "datos2";
						$color = 1;
					}
					echo "<tr><td class='$tdcolor'>";
					echo $agentmodule_name;
					echo "</td><td class='$tdcolor'>";
					echo $row["datos"];
					echo "</td><td class='$tdcolor'>";
					echo $row["timestamp"];
					echo "</td></tr>";
				}
			}
			echo "</table>";
		} else
			echo "<b class='error'>".__('No module has been selected')."</b>";

	}

} else {
	// Option B: Print Form
	// Form view
	$ahora=date("Y/m/d H:i:s");
	$ahora_s = date("U");
	$ayer = date ("Y/m/d H:i:s", $ahora_s - 86400);
	if (isset($_GET["date_from"])) 
		$date_from=$_GET["date_from"];
	else 
		if (isset($_POST["from_date"])) 
			$date_from = $_POST["from_date"];
		else 
			$date_from = $ayer;

	if (isset($_GET["date_to"])) 
		$date_to = $_GET["date_to"];
	else
		if (isset($_POST["to_date"])) 
			$date_to = $_POST["to_date"];
		else 
			$date_to = $ahora;
		
	echo "<script type='text/javaScript' src='include/javascript/calendar.js'></script>";
	echo "<h2>".__('Pandora Agents')." &gt; ";
	echo __('Export data')."</h2>";

	echo '<form method="post" action="index.php?sec=estado&sec2=operation/agentes/exportdata" name="export_form">';
	echo '<table width=550 border=0 cellspacing=3 cellpadding=5 class=databox_color>';
	echo '<tr>';
	echo "<td class='datos'><b>".__('Source agent')."</b></td>";
	echo "<td class='datos'>";	


	// Show combo with agents
	echo '<select name="origen" class="w130">';
	if ( (isset($_POST["update_agent"])) AND (isset($_POST["origen"])) ) {
		echo "<option value=".$_POST["origen"].">".get_agent_name($_POST["origen"])."</option>";
	}
	$sql1='SELECT * FROM tagente';
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		if ( (isset($_POST["update_agent"])) AND (isset($_POST["origen"])) ){
			if (give_acl($config['id_user'], $row["id_grupo"], "AR"))
				if ( $_POST["origen"] != $row["id_agente"])
					echo "<option value=".$row["id_agente"].">".$row["nombre"]."</option>";
		} else {
			if (give_acl($config['id_user'], $row["id_grupo"], "AR"))
				echo "<option value=".$row["id_agente"].">".$row["nombre"]."</option>";
		}
	}
	echo "</select> &nbsp;&nbsp;";
	echo "<input type=submit name='update_agent' class='sub upd' value='".__('Get Info')."'>";

	
	echo '<tr>';
	echo "<td class='datos2'>";
	echo "<b>".__('Modules')."</b>";
	echo "<td class='datos2'>";
	
	// Combo with modules
	echo "<select name='origen_modulo[]' size=8 class='w130'>";
	if ((isset($_POST["update_agent"])) && (isset($_POST["origen"])) ) {
		// Populate Module/Agent combo
		$agente_modulo = $_POST["origen"];
		$sql = "SELECT * FROM tagente_modulo WHERE id_agente = ".$agente_modulo;
		$result = mysql_query ($sql);
		while ($row=mysql_fetch_array($result)) {
			echo "<option value=".$row["id_agente_modulo"].">".$row["nombre"];
		}
	} else {
		echo "<option value=-1>".__('N/A')."</option>";
	}
	echo "</select>";
	
	echo "<tr><td class='datos'>";
	echo "<b>".__('Begin date (*)')."</b>";
	echo "<td class='datos'>";
	echo "<input type='text' id='start_date' name='start_date' size=10 value='".substr ($date_from, 0, 10)."'> <img src='images/calendar_view_day.png' onclick='scwShow(scwID(\"start_date\"),this);'> ";
	echo "<input type='text' name='start_time' size=10 value='".substr ($date_from, 11, 8)."'>";
	

	echo "<tr><td class='datos2'>";
	echo "<b>".__('End date (*)')."</b>";
	
	echo "<td class='datos2'>";	
	echo "<input type='text' id='end_date' name='end_date' size=10 value='".substr ($date_to, 0, 10)."'> <img src='images/calendar_view_day.png' onclick='scwShow(scwID(\"end_date\"),this);'> ";
	echo "<input type='text' name='end_time' size=10 value='".substr ($date_to, 11, 8)."'>";

	echo "<tr><td class='datos'>";
	echo "<b>".__('Export type')."</b>";
	echo "<td class='datos'>";
	// Combo for data export type
	echo "<select name='export_type'>";
	echo "<option value=3>".__('Data table')."</option>";
	echo "<option value=1>".__('Standalone CSV ascii file')."</option>";
	echo "<option value=2>".__('Average per hour/day')."</option>";
	echo "</select>";
	echo "</table>";
	
	// Submit button
	echo '<div class="action-buttons" style="width: 550">';
	print_submit_button (__('Export'), 'export', false, 'class="sub wand"');
	echo "</div>";
	echo "</form>";
}

?>
