<?php

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@openideas.info
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas
// Copyright (c) 2004-2007 Raul Mateos Martin, raulofpandora@gmail.com
// Copyright (c) 2006-2007 Jose Navarro jose@jnavarro.net
// Copyright (c) 2006-2007 Jonathan Barajas, jonathan.barajas[AT]gmail[DOT]com

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

	// Load global vars
	require("include/config.php");

	if (give_acl ($id_user, 0, "AR") != 1) {
		audit_db ($id_user, $REMOTE_ADDR, "ACL Violation", 
		"Trying to access Agent view (Grouped)");
		require ("general/noaccess.php");
		exit;
	}
	echo "<h2>".$lang_label["ag_title"]." &gt; ".$lang_label["group_view"]."
	<a href='help/".$help_code."/chap3.php#324' target='_help' class='help'>
	<span>".$lang_label["help"]."</span>
	</a></h2>";

	$mis_grupos = list_group2 ($id_user);

	// Update network modules for this group
	// Check for Network FLAG change request
	if (isset ($_GET["update_netgroup"])) {
		if (give_acl ($id_user, $_GET["update_netgroup"], "AW") == 1) {
			$sql = "SELECT * FROM tagente WHERE id_grupo = ".
			$_GET["update_netgroup"];
			$result = mysql_query ($sql);
			while ($row = mysql_fetch_array ($result)) {
				$id_agente = $row["id_agente"];
				$query2 ="UPDATE tagente_modulo SET flag=1
				WHERE id_agente = ".$id_agente;
				$res = mysql_query ($query2);
			}
		}
	}

	$contador_grupo  = 0;
	$contador_agente = 0;
	$array_index     = 0;

	// For each valid group for this user, take data from agent and modules
	foreach ($mis_grupos as $migrupo) {
		if ($migrupo != "") {
			$grupo[$array_index]["agent"]    = 0;
			$grupo[$array_index]["ok"]       = 0;
			$grupo[$array_index]["down"]     = 0;
			$grupo[$array_index]["bad"]      = 0;
			$grupo[$array_index]["alerts"]   = 0;
			$grupo[$array_index]["data"]     = 0;
			$grupo[$array_index]["icon"]     = dame_grupo_icono ($migrupo);
			$grupo[$array_index]["id_grupo"] = $migrupo;
			$existen_agentes =0;

			$sql1 = "SELECT intervalo, id_agente
			FROM tagente WHERE disabled=0
			AND id_grupo = ".$migrupo;
			if ($result1 = mysql_query ($sql1)) {
				while ($row1 = mysql_fetch_array ($result1)) {
					$existen_agentes = 1;
					$intervalo = $row1["intervalo"];
					$id_agente = $row1["id_agente"];

					// Check for recent alerts
					if (check_alert_fired($id_agente) == 1) {
						$grupo[$array_index]["alerts"]++;
					}

					$grupo[$array_index]["agent"]++;
					$grupo[$array_index]["group"] = dame_nombre_grupo ($migrupo);
					//  Estado grupo, agent
					$contador_agente++;
					$sql3 = "SELECT estado, timestamp, id_agente_modulo,
					datos FROM tagente_estado
					WHERE id_agente = ".$row1["id_agente"];
					$result3 = mysql_query ($sql3);
					while ($row3 = mysql_fetch_array ($result3)) {
						$estado = $row3["estado"];
						// Get module interval
						$ahora = date ("Y/m/d H:i:s");
						$sql4 = "SELECT module_interval
						FROM tagente_modulo
						WHERE id_agente_modulo = ".$row3["id_agente_modulo"];
						$result4 = mysql_query ($sql4);
						if ($row4 = mysql_fetch_array ($result4)) {
							$module_interval = $row4["module_interval"];
							if ($module_interval > 0) {
								$intervalo_comp = $module_interval;
							} else {
								$intervalo_comp = $intervalo;
							}
						}

						$ultimo_contacto_modulo = $row3["timestamp"];

						// Defines if module is down (interval x 2 > time last contact)
						if ($ultimo_contacto_modulo != "0000-00-00 00:00:00") {
							$seconds = strtotime ($ahora) -
							strtotime ($ultimo_contacto_modulo);
							if ($seconds >= ($intervalo_comp * 2)) {
								$grupo[$array_index]["down"]++;
							} elseif ($estado != 100) {
								if ($row3["datos"] != 0) {
									$grupo[$array_index]["ok"]++;
								} else {
									$grupo[$array_index]["bad"]++;
								}
							} elseif ($estado == 100) // For data module, not monitors
									$grupo[$array_index]["data"]++; // Data module
						}
					}
				}
			}
			if ($existen_agentes == 1){
				$array_index++;
			}
		}
	}

	// Draw data

	if ($contador_agente != 0) {
		$ancho = ceil(sqrt($array_index+1));
		$real_count =0;
		echo "<table cellpadding=10 cellspacing=10>";
		for ($table=0; $table < $ancho; $table++) {
			echo "<tr class='bot'>";
			for ($table_row=0; $table_row < $ancho; $table_row++) {
				if ($real_count < $array_index) {

					$group_name  = $grupo[$real_count]["group"];
					$icono_grupo = $grupo[$real_count]["icon"];
					$icono_type  = "";

					if ($grupo[$real_count]["down"] > 0) {
						$icono_type = "
						<img src='images/dot_down.gif' alt=''>";
					}
					if ($grupo[$real_count]["bad"] > 0) {
						$icono_type = $icono_type."
						<img src='images/dot_red.gif' alt=''>";
					}
					if ($grupo[$real_count]["ok"] > 0) {
						$icono_type = $icono_type."
						<img src='images/dot_green.gif' alt=''>";
					}
					if ($grupo[$real_count]["data"] > 0) {
						$icono_type = $icono_type."
						<img src='images/dot_white.gif' alt=''>";
					}
					// Show yellow light if there are recent alerts fired for this group
					if ($grupo[$real_count]["alerts"] > 0 ){
						$icono_type=$icono_type."
						<img src='images/dot_yellow.gif' alt=''>";
					}

					// TOOLTIP.
					$celda = "<td class='top' width='100'>
					<a href='index.php?sec=estado&amp;
					sec2=operation/agentes/estado_agente&amp;
					refr=60&amp;
					group_id=".$grupo[$real_count]["id_grupo"]."'
					class='info'>
					<img class='top'
					src='images/groups_small/".$icono_grupo.".png' height='32'  width='32' alt=''>
			<span>
			<table cellspacing='2' cellpadding='0'
			style='margin-left:20px'>
				<tr><td colspan='2' width='91' class='lb'>".
				$lang_label["agents"].": </td></tr>
				<tr><td colspan='2' class='datos' align='center'><b>".
				$grupo[$real_count]["agent"]."</b></td></tr>
			</table>
			<table cellspacing='2' cellpadding='0'
			style='margin-left:20px'>
				<tr>
				<td colspan='2' width='90' class='lb'>".
				ucfirst($lang_label["monitors"]).":</td>
				</tr>
				<tr>
				<td class='datos'>
				<img src='images/b_green.gif' align='top' alt='' >
				".$lang_label["ok"].": </td>
				<td class='datos'>
				<font class='greenb'>".$grupo[$real_count]["ok"]."</font>
				</td>
				</tr>
				<tr>
				<td class='datos'>
				<img src='images/b_down.gif' align='top' alt=''>
				".$lang_label["down"].": </td>
				<td class='datos'><font class='#a9aa9a'>".
				$grupo[$real_count]["down"]."</font></td>
				</tr>
				<tr>
				<td class='datos'>
				<img src='images/b_red.gif' align='top' alt=''>
				".$lang_label["fail"].": </td>
				<td class='datos'><font class='redb'>".
				$grupo[$real_count]["bad"]."</font></td>
				</tr>
				<tr>
				<td class='datos'>
				<img src='images/b_yellow.gif' align='top' alt=''>
				".$lang_label["alerts"].": </td>
				<td class='datos'><font class='grey'>".
				$grupo[$real_count]["alerts"]."</font></td>
				</tr>
			</table>
			</span></a>";
					// Render network exec module button, only when this group is writtable by user
					if (give_acl ($id_user, $grupo[$real_count]["id_grupo"], "AW") == 1) {
						$celda .= "&nbsp;<a href='index.php?
						sec=estado&
						sec2=operation/agentes/estado_grupo&
						update_netgroup=".$grupo[$real_count]["id_grupo"]."'>
						<img src='images/target.gif'></a>";
					}
					$celda .= "<br><br>".
					$icono_type."<br><br>
					<span class='gr'>".$group_name."</span>";
					echo $celda;
				}
				$real_count++;
			}
			echo "</tr>";
		}

		echo "</table>";
	} else {
		echo "<div class='nf'>".$lang_label["no_agent_def"]."</div>";
	}



?>
