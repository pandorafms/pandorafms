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
	
	if (! isset($config["show_lastalerts"]))
		$config["show_lastalerts"]=1;

	if (give_acl ($id_user, 0, "AR") != 1) {
		audit_db ($id_user, $REMOTE_ADDR, "ACL Violation", 
		"Trying to access Agent view (Grouped)");
		require ("general/noaccess.php");
		exit;
	}
	echo "<h2>".$lang_label["ag_title"]." &gt; ".$lang_label["group_view"]."</h2>";

	// Get group list that user has access
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
	$ahora=date("Y/m/d H:i:s");
	$ahora_sec = strtotime($ahora);
	// Prepare data to show
	// For each valid group for this user, take data from agent and modules
	foreach ($mis_grupos as $migrupo) {
		if (($migrupo != "") && ($migrupo != 1)) {
			$existen_agentes = 0;	
			$grupo[$array_index]["agent"]    = 0;
			$grupo[$array_index]["ok"]       = 0;
			$grupo[$array_index]["bad"]      = 0;
			$grupo[$array_index]["alerts"]   = 0;
			$grupo[$array_index]["down"]   = 0;
			$grupo[$array_index]["icon"]     = dame_grupo_icono ($migrupo);
			$grupo[$array_index]["id_grupo"] = $migrupo;
			$grupo[$array_index]["group"] = dame_nombre_grupo ($migrupo);

			$sql0 = "SELECT COUNT(id_agente) FROM tagente WHERE id_grupo = $migrupo AND disabled = 0";
			$result0 = mysql_query ($sql0);
			$row0 = mysql_fetch_array ($result0);
			$contador_agente = $contador_agente + $row0[0];
			$grupo[$array_index]["agent"] = $row0[0];
			if ($row0[0] > 0)
				$existen_agentes = 1;

			// SQL Join to get monitor status for agents belong this group
			$sql1 = "SELECT 
					tagente.id_agente, tagente_estado.estado, tagente_estado.datos, tagente_estado.current_interval, 
					tagente_estado.utimestamp, tagente_estado.id_agente_modulo 
				 FROM tagente, tagente_estado 
				 WHERE tagente.disabled = 0 AND tagente.id_grupo = $migrupo AND 
					tagente.id_agente = tagente_estado.id_agente AND tagente_estado.estado != 100 AND 
					tagente_estado.utimestamp != 0";
					
			if ($result1 = mysql_query ($sql1)){
				while ($row1 = mysql_fetch_array ($result1)) {
					$id_agente = $row1[0];
					$estado = $row1[1];
					$datos = $row1[2];
					$module_interval = $row1[3];
					$seconds = $ahora_sec - $row1[4];
					$id_agente_modulo = $row1[5];
					

					if ($config["show_unknown"] > 0){
                        if ($seconds >= ($module_interval*2)){
						    $grupo[$array_index]["down"]++;
                        }
                    } elseif ($datos != 0) {
						$grupo[$array_index]["ok"]++;
					} else {
						$grupo[$array_index]["bad"]++;
					}
				}
			}
	
			if ($config["show_lastalerts"] == 1){
				// How many alerts has been fired recently for this group:
				// SQL Join to get alert status for agents belong this group
				$sql1 = "SELECT SUM(talerta_agente_modulo.times_fired)
				FROM tagente_modulo, talerta_agente_modulo, tagente WHERE tagente.disabled = 0 AND tagente.id_grupo = $migrupo AND tagente.id_agente = tagente_modulo.id_agente AND talerta_agente_modulo.id_agente_modulo = tagente_modulo.id_agente_modulo";
				if ($result1 = mysql_query ($sql1)){
					$row1 = mysql_fetch_array ($result1);
					$grupo[$array_index]["alerts"] = $row1[0];
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
		echo "<table cellpadding=10 cellspacing=10 border=0>";
		for ($table=0; $table < $ancho; $table++) {
			echo "<tr class='bot'>";
			for ($table_row=0; $table_row < $ancho; $table_row++) {
				if ($real_count < $array_index) {
					$group_name  = $grupo[$real_count]["group"];
					$icono_grupo = $grupo[$real_count]["icon"];
					$icono_type  = "";
					if ($grupo[$real_count]["bad"] > 0) {
						$icono_type = $icono_type."
						<img src='images/dot_red.png' alt=''>";
					}
					if ($grupo[$real_count]["ok"] > 0) {
						$icono_type = $icono_type."
						<img src='images/dot_green.png' alt=''>";
					}
					// Show yellow light if there are recent alerts fired for this group
					if ($grupo[$real_count]["alerts"] > 0 ){
						$icono_type=$icono_type."
						<img src='images/dot_yellow.png' alt=''>";
					}
					
					// Show grey light if there are agent down for this group
					if ($grupo[$real_count]["down"] > 0 ){
						$icono_type=$icono_type."
						<img src='images/dot_white.png' alt=''>";
					}
			
					// Show red flag is group has disabled alert system
					if (give_disabled_group($grupo[$real_count]["id_grupo"]) == 1)
						$icono_type = $icono_type."&nbsp;
						<img src='images/flag_red.png' alt='".$lang_label["disabled"]."'>";

					// By default green border
					$celda = "<td class='top' style='border: 5px solid #aeff21;' width='100'>";

					// Grey border if agent down
                    if ($config["show_unknown"] > 0){
      					if ($grupo[$real_count]["down"] > 0)
						$celda = "<td class='top' style='border: 5px solid #aabbaa;' width='100'>";
					}

					// Yellow border if agents with alerts
					if ($grupo[$real_count]["alerts"] > 0)
						$celda = "<td class='top' style='border: 5px solid #ffea00;' width='100'>";

					// Red border if agents bad
					if ($grupo[$real_count]["bad"] > 0)
						$celda = "<td class='top' style='border: 5px solid #ff0000;' width='100'>";

      					// Orange if alerts and down modules
      					if (($grupo[$real_count]["bad"] > 0) && ($grupo[$real_count]["alerts"] > 0))
						$celda = "<td class='top' style='border: 5px solid #ffbb00;' width='100'>";

						
					$celda .= "<a href='index.php?sec=estado&amp;
					sec2=operation/agentes/estado_agente&amp;
					refr=60&amp;
					group_id=".$grupo[$real_count]["id_grupo"]."'
					class='info'>";

					// Add group icon
					$celda .= "<img class='top'
					src='images/groups_small/".$icono_grupo.".png' height='32'  width='32' alt=''>";

					// Add float info table
					$celda .= "
						<span>
						<table cellspacing='2' cellpadding='0'
						style='margin-left:2px;'>
							<tr><th colspan='2' width='91'>".
							$lang_label["agents"].": </th></tr>
							<tr><td colspan='2' class='datos' align='center'><b>".
							$grupo[$real_count]["agent"]."</b></td></tr>
						</table>
						<table cellspacing='2' cellpadding='0'
						style='margin-left:2px'>
							<tr>
							<th colspan='2' width='90'>".
							ucfirst($lang_label["monitors"]).":</th>
							</tr>
							<tr>
							<td class='datos'>
							<img src='images/b_green.png' align='top' alt='' >
							".$lang_label["ok"].": </td>
							<td class='datos'>
							<font class='greenb'>".$grupo[$real_count]["ok"]."</font>
							</td>
							</tr>
							<tr>
							<td class='datos'>
							<img src='images/b_red.png' align='top' alt=''>
							".$lang_label["fail"].": </td>
							<td class='datos'><font class='redb'>".
							$grupo[$real_count]["bad"]."</font></td>
							</tr>";
                    if ($config["show_unknown"] > 0){
                        $celda .= "
                            <tr>
							<td class='datos'>
							<img src='images/b_white.png' align='top' alt=''>
							".$lang_label["down"].": </td>
							<td class='datos'><font class='redb'>".
							$grupo[$real_count]["down"]."</font></td></tr>";
                    }
					if ($config["show_lastalerts"] == 1)
						$celda .= "<tr>
						<td class='datos'>
						<img src='images/b_yellow.png' align='top' alt=''>
						".$lang_label["alerts"].": </td>
						<td class='datos'><font class='grey'>".
						$grupo[$real_count]["alerts"]."</font></td>
						</tr>";
					$celda .= "</table></span></a>";
			
					// Render network exec module button, only when this group is writtable by user
					if (give_acl ($id_user, $grupo[$real_count]["id_grupo"], "AW") == 1) {
						$celda .= "&nbsp;<a href='index.php?
						sec=estado&
						sec2=operation/agentes/estado_grupo&
						update_netgroup=".$grupo[$real_count]["id_grupo"]."'>
						<img src='images/target.png'></a>";
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
	      	$id_user = $_SESSION["id_usuario"];
	if ( (give_acl($id_user, 0, "LM")==1) OR (give_acl($id_user, 0, "AW")==1 ) OR (give_acl($id_user, 0, "PM")==1) OR (give_acl($id_user, 0, "DM")==1) OR (give_acl($id_user, 0, "UM")==1 )){
	      echo "&nbsp;<form method='post' action='index.php?sec=gagente&
	sec2=godmode/agentes/configurar_agente&create_agent=1'><input type='submit' class='sub next' name='crt'
	value='".$lang_label["create_agent"]."'></form>";
	}
	}

?>
