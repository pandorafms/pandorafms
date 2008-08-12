<?php
// Pandora FMS
// ====================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2008 Artica Soluciones Tecnologicas S.L, info@artica.es

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

check_login();

if (! give_acl ($config['id_user'], 0, "AR") && ! give_acl ($config['id_user'], 0, "AW")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access SLA View");
	require ("general/noaccess.php");
	exit;
}

require ("include/functions_reporting.php");

echo "<h2>".__('SLA view')."</h2>";
$id_agent = get_parameter ("id_agente", "0");

// Get all module from agent
$sql_t='SELECT * FROM tagente_estado, tagente_modulo WHERE tagente_modulo.disabled = 0 AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.id_agente='.$id_agent.' AND tagente_estado.estado != 100 AND tagente_estado.utimestamp != 0 ORDER BY tagente_modulo.nombre';
$result_t=mysql_query($sql_t);
if (mysql_num_rows ($result_t)) {
	echo "<h3>".__('Automatic SLA for monitors')."</h3>";
	echo "<table width='750' cellpadding=4 cellspacing=4 class='databox'>";
	echo "<tr><th>X</th>";
	echo "<th>".__('Type')."</th>
	<th>".__('Module name')."</th>
	<th>".__('S.L.A')."</th>
	<th>".__('Status')."</th>
	<th>".__('Interval')."</th>
	<th>".__('Last contact')."</th>";
	$color=0;
	while ($module_data=mysql_fetch_array($result_t)){
		# For evey module in the status table
		$est_modulo = substr($module_data["nombre"],0,25);
		$est_tipo = dame_nombre_tipo_modulo($module_data["id_tipo_modulo"]);
		$est_description = $module_data["descripcion"];
		$est_timestamp = $module_data["timestamp"];
		$est_estado = $module_data["estado"];
		$est_datos = $module_data["datos"];
		$est_cambio = $module_data["cambio"];
		$est_interval = $module_data["module_interval"];
		if (($est_interval != $intervalo) && ($est_interval > 0)) {
			$temp_interval = $est_interval;
		} else {
			$temp_interval = $intervalo;
		}
		if ($est_estado <>100){ # si no es un modulo de tipo datos
			# Determinamos si se ha caido el agente (tiempo de intervalo * 2 superado)
			if ($color == 1){
				$tdcolor = "datos";
				$color = 0;
			}
			else {
				$tdcolor = "datos2";
				$color = 1;
			}
			$seconds = time() - $module_data["utimestamp"];
			if ($seconds >= ($temp_interval*2)) // If every interval x 2 secs. we get nothing, there's and alert
				$agent_down = 1;
			else
				$agent_down = 0;
		
			echo "<tr><td class='".$tdcolor."'>";

			if (($module_data["id_modulo"] != 1) AND ($module_data["id_tipo_modulo"] < 100)) {
				if ($module_data["flag"] == 0){
					echo "<a href='index.php?sec=estado& sec2=operation/agentes/ver_agente& id_agente=".$id_agente."&id_agente_modulo=".$module_data["id_agente_modulo"]."&flag=1& tab=main&refr=60'><img src='images/target.png' border='0'></a>";
				} else {
					echo "<a href='index.php?sec=estado& sec2=operation/agentes/ver_agente&id_agente=".$id_agente."&id_agente_modulo=".$module_data["id_agente_modulo"]."&tab=main&refr=60'><img src='images/refresh.png' border='0'></a>";
				}
			}
			echo "<td class='".$tdcolor."'>";
			echo "<img src='images/".show_icon_type($module_data["id_tipo_modulo"])."' border=0>";	
			echo "<td class='".$tdcolor."' title='".$est_description."'>".$est_modulo."</td>";
			echo "<td class='$tdcolor'>";

			$temp = get_agent_module_sla ($module_data["id_agente_modulo"], $config["sla_period"], 1, 2147483647);
			if ($temp === false)
				echo __('N/A');
			else {
				echo format_numeric ($temp)." %</td>";;
			}

			echo "<td class='".$tdcolor."' align='center'>";
			if ($est_estado == 1){
				if ($est_cambio == 1) 
					echo "<img src='images/pixel_yellow.png' width=40 height=18 title='" . __('Change between Green/Red state') . "'>";
				else
					echo "<img src='images/pixel_red.png' width=40 height=18 title='". __('At least one monitor fails') . "'>";
			} else
				echo "<img src='images/pixel_green.png' width=40 height=18 title='". __('All Monitors OK') . "'>";

			echo "<td align='center' class='".$tdcolor."'>";
			if ($temp_interval != $intervalo)
				echo $temp_interval."</td>";
			else
				echo "--";
			echo  "<td class='".$tdcolor."f9'>";
			if ($agent_down == 1) { // If agent down, it's shown red and bold
				echo  "<span class='redb'>";
			}
			else {
				echo "<span>";
			}
			if ($module_data["timestamp"] == '0000-00-00 00:00:00') {
				echo __('Never');
			} else {
				echo human_time_comparation($module_data["timestamp"]);
			}
			echo "</span></td>";
		}
	}
	echo '</table>';
} 


// Get all SLA report components
$sql = "SELECT tagente_modulo.id_agente_modulo, sla_max, sla_min, sla_limit, tagente_modulo.id_tipo_modulo, tagente_modulo.nombre, tagente_modulo.descripcion FROM treport_content_sla_combined, tagente_modulo WHERE tagente_modulo.id_agente = $id_agent AND tagente_modulo.id_agente_modulo = treport_content_sla_combined.id_agent_module AND tagente_modulo.id_tipo_modulo IN (1,4,7,8,11,15,16,22,24)";
$result_t = mysql_query ($sql);
if (mysql_num_rows ($result_t)) {
	$color=0;
	echo "<h3>".__('User-defined SLA items')." - ";
	echo human_time_description_raw($config["sla_period"]). " </h3>";
	echo "<table width='750' cellpadding=4 cellspacing=4 class='databox'>";
	echo "<tr>";
	echo "<th>" . __('Type') . "</th>";
	echo "<th>" . __('Module name') . "</th>";
	echo "<th>" . __('S.L.A') . "</th>";
	echo "<th>" . __('Status') . "</th>";
	
	while ($module_data = mysql_fetch_array($result_t)){
		if ($color == 1){
			$tdcolor = "datos";
			$color = 0;
		}
		else {
			$tdcolor = "datos2";
			$color = 1;
		}

		# For evey module in the status table
		$id_agent_module = $module_data[0];
		$sla_max = $module_data[1];
		$sla_min = $module_data[2];
		$sla_limit = $module_data[3];
		$id_tipo_modulo = $module_data[4];
		$name = $module_data[5];
		$description = $module_data[6];
		$est_tipo = dame_nombre_tipo_modulo ($id_tipo_modulo);
		
		echo "<tr>";	
		echo "<td class='" . $tdcolor . "'>";
		echo "<img src='images/" . show_icon_type ($id_tipo_modulo) . "' border=0>";	
		echo "<td class='" . $tdcolor . "' title='" . $description . "'>" . $name;
		echo " ($sla_min / $sla_max / $sla_limit) </td>";
		echo "<td class='$tdcolor'>";

		$temp = get_agent_module_sla ($id_agent_module, $config["sla_period"], $sla_min, $sla_max);
		if ($temp === false){
			echo __('N/A');
			echo "<td class='$tdcolor'>";
		} else {
			echo format_numeric($temp)." %</td>";
			echo "<td class='$tdcolor'>";
			if ($temp > $sla_limit)
				echo "<img src='images/pixel_green.png' width=40 height=18 title='" . __('All Monitors OK') . "'>";
			else
				echo "<img src='images/pixel_red.png' width=40 height=18 title='" . __('At least one monitor fails') . "'>";
		}
	}
	echo '</table>';
}

?>
