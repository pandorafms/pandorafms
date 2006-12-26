<?php

// Pandora - the Free monitoring system
// ====================================
// Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2006 Artica Soluciones Tecnologicas S.L, info@artica.es
// Copyright (c) 2004-2006 Raul Mateos Martin, raulofpandora@gmail.com
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

if (comprueba_login() == 0) {
 	if ((give_acl($id_user, 0, "AR")==1) or (give_acl($id_user,0,"AW"))
	or (dame_admin($id_user)==1)) {

 	if (isset($_POST["ag_group"]))
			$ag_group = $_POST["ag_group"];
		elseif (isset($_GET["group_id"]))
		$ag_group = $_GET["group_id"];
	else
		$ag_group = -1;

	if (isset($_GET["ag_group_refresh"])){
		$ag_group = $_GET["ag_group_refresh"];
	}
	echo "<h2>".$lang_label["ag_title"]."</h2>";
	echo "<h3>".$lang_label["summary"]."
	<a href='help/".$help_code."/chap3.php#331' target='_help' class='help'>
	&nbsp;<span>".$lang_label["help"]."</span></a></h3>";
	
	// Show group selector

	if (isset($_POST["ag_group"])){
		$ag_group = $_POST["ag_group"];
		echo "<form method='post' 
		action='index.php?sec=estado&sec2=operation/agentes/estado_agente
		&refr=60&ag_group_refresh=".$ag_group."'>";
	} else {
		echo "<form method='post'
		action='index.php?sec=estado&sec2=operation/agentes/estado_agente
		&refr=60'>";
	}

	echo "<table cellpadding='3' cellspacing='3'><tr>";
	echo "<td>".$lang_label["group"]."</td>";
	echo "<td valign='middle'>";
	echo "<select name='ag_group' onChange='javascript:this.form.submit();' 
	class='w130'>";

	if ( $ag_group > 1 ){
		echo "<option value='".$ag_group."'>".dame_nombre_grupo($ag_group).
		"</option>";
	}
	echo "<option value=1>".dame_nombre_grupo(1)."</option>"; // Group all is always active 
	// Group 1 (ALL) gives A LOT of problems, be careful with this code:
	// Run query on all groups and show data only if ACL check its ok: $iduser_temp is user, and acl is AR (agent read)
	$mis_grupos[]=""; // Define array mis_grupos to put here all groups with Agent Read permission
	
	$sql='SELECT id_grupo FROM tgrupo';
	$result=mysql_query($sql);
	while ($row=mysql_fetch_array($result)){
	if ($row["id_grupo"] != 1){
		if (give_acl($id_user,$row["id_grupo"], "AR") == 1){
			echo "<option value='".$row["id_grupo"]."'>".
			dame_nombre_grupo($row["id_grupo"])."</option>";
			$mis_grupos[]=$row["id_grupo"]; //Put in  an array all the groups the user belongs
		}
	}
	}
	echo "</select>";
	echo "<td valign='middle'>
	<noscript>
	<input name='uptbutton' type='submit' class='sub' 
	value='".$lang_label["show"]."'>
	</noscript>
	</td>
	</form>";
	// Show only selected groups	

	if ($ag_group > 1)
		$sql='SELECT * FROM tagente WHERE id_grupo='.$ag_group.' 
		AND disabled = 0 ORDER BY nombre';
	else 
		$sql='SELECT * FROM tagente WHERE disabled = 0 
		ORDER BY id_grupo, nombre';	

	$result=mysql_query($sql);
	if (mysql_num_rows($result)){
		// Load icon index from tgrupos
		$iconindex_g[]="";
		$sql_g='SELECT id_grupo, icon FROM tgrupo';
		$result_g=mysql_query($sql_g);
		while ($row_g=mysql_fetch_array($result_g)){
			$iconindex_g[$row_g["id_grupo"]] = $row_g["icon"];
		}
		echo "<td class='f9l30'>";
		echo "<img src='images/dot_red.gif'> - ".$lang_label["fired"];
		echo "&nbsp;&nbsp;</td>";
		echo "<td>";
		echo "<img src='images/dot_green.gif'> - ".$lang_label["not_fired"];
		echo "</td></tr></table>";
		echo "<br>";
		echo "<table cellpadding='3' cellspacing='3' width='700'>";
		echo "<th>".$lang_label["agent"]."</th>";
		echo "<th>".$lang_label["os"]."</th>";
		echo "<th>".$lang_label["interval"]."</th>";
		echo "<th>".$lang_label["group"]."</th>";
		echo "<th>".$lang_label["modules"]."</th>";
		echo "<th>".$lang_label["status"]."</th>";
		echo "<th>".$lang_label["alerts"]."</th>";
		echo "<th>".$lang_label["last_contact"]."</th>";
		// For every agent defined in the agent table
		$color = 1;
		while ($row=mysql_fetch_array($result)){
			$intervalo = $row["intervalo"]; // Interval in seconds
			$id_agente = $row['id_agente'];	
			$nombre_agente = $row["nombre"];
			$direccion_agente =$row["direccion"];
			$id_grupo=$row["id_grupo"];
			$id_os = $row["id_os"];
			$agent_type = $row["agent_type"];
			$ultimo_contacto = $row["ultimo_contacto"];
			$biginterval=$intervalo;
			foreach ($mis_grupos as $migrupo){	//Verifiy if the group this agent begins is one of the user groups
				if (($migrupo ==1) || ($id_grupo==$migrupo)){
					$pertenece = 1;
					break;
				}
				else
					$pertenece = 0;
			}
			if ($pertenece == 1) { // Si el agente pertenece a uno de los grupos que el usuario puede visualizar
				// Obtenemos la lista de todos los modulos de cada agente
				$sql_t="SELECT * FROM tagente_estado, tagente_modulo 
				WHERE tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo 
				AND tagente_modulo.id_agente=".$id_agente;
				$result_t=mysql_query($sql_t);
				$estado_general = 0; 
				$numero_modulos = 0; 
				$numero_monitor = 0; 
				$est_timestamp = ""; 
				$monitor_bad=0; 
				$monitor_ok = 0; 
				$monitor_down=0; 
				$numero_datamodules=0;
				$estado_cambio=0; // Oops, I forgot initialize this fucking var... many problems due it
				$ahora=date("Y/m/d H:i:s");
				// Calculate module/monitor totals  for this agent
				while ($row_t=mysql_fetch_array($result_t)){
					$est_modulo = $row_t["estado"]; 
					$ultimo_contacto_modulo = $row_t["timestamp"];
					$module_interval = $row_t["module_interval"];
					if ($module_interval > $biginterval)
						$biginterval = $module_interval;
					if ($module_interval !=0)
						$intervalo_comp = $module_interval;
					else
						$intervalo_comp = $intervalo;
					if ($ultimo_contacto <> "")
						$seconds = strtotime($ahora) - strtotime($ultimo_contacto_modulo);
					else 
						$seconds = -1;
			
					# Defines if Agent is down (interval x 2 > time last contact	
					if ($seconds >= ($intervalo_comp*2)){ // If (intervalx2) secs. ago we don't get anything, show alert
						if ($est_modulo != 100)
							$numero_monitor++;
						$monitor_down++;
					}
					elseif ($est_modulo <> 100) { // estado=100 are data modules
						$estado_general = $estado_general + $est_modulo;
						$estado_cambio = $estado_cambio + $row_t["cambio"]; 
						$numero_monitor ++;
						if ($est_modulo <> 0)
							$monitor_bad++;			
						else
							$monitor_ok++;
					} elseif ($est_modulo == 100){ // Data modules
						$numero_datamodules++;
					}
					$numero_modulos++;
				}					
				// Color change for each line (1.2 beta2)
				if ($color == 1){
					$tdcolor = "datos";
					$color = 0;
				}
				else {
					$tdcolor = "datos2";
					$color = 1;
				}
				echo "<tr>";
				echo "<td class='$tdcolor'>";
				$id_grupo=dame_id_grupo($id_agente);
				if (give_acl($id_user, $id_grupo, "AW")==1){
					echo "<a href='index.php?sec=gagente&amp;
					sec2=godmode/agentes/configurar_agente&amp;
					id_agente=".$id_agente."'>
					<img src='images/setup.gif' border=0 width=15></a>";
				}
				echo "&nbsp;&nbsp;<a href='index.php?sec=estado&amp;
				sec2=operation/agentes/ver_agente&amp;id_agente=".$id_agente."'>
				<b>".$nombre_agente."</b></a></td>";
				if ( $agent_type == 0) {
					// Show SO icon :)
					echo "<td class='$tdcolor' align='center'>
					<img border=0 src='images/".dame_so_icon($id_os)."' 
					height=18 alt='".dame_so_name($id_os)."'></td>";
				} elseif ($agent_type == 1) {
					// Show network icon
					echo "<td class='$tdcolor' align='center'>
					<img border=0 src='images/network.gif' height=18 
					alt='Network Agent'></td>";
				}
				// If there are a module interval bigger than agent interval
				if ($biginterval > $intervalo) {
					echo "<td class='$tdcolor'>
					<span class='green'>".$biginterval."</span></td>";
				} else {
					echo "<td class='$tdcolor'>".$intervalo."</td>";
				}
				echo '<td class="'.$tdcolor.'">
				<img src="images/g_'.$iconindex_g[$id_grupo].'.gif"> 
				( '.dame_grupo($id_grupo).' )</td>';
				echo "<td class='$tdcolor'> ".
				$numero_modulos." <b>/</b> ".$numero_monitor;
				if ($monitor_bad <> 0) {
					echo " <b>/</b> <span class='red'>".$monitor_bad."</span>";
				}
				if ($monitor_down <> 0){
					echo " <b>/</b> <span class='grey'>".$monitor_down."</span>";
				}
				echo "</td>
				<td class='$tdcolor' align='center'>";	
				if ($numero_monitor <> 0){
					if ($estado_general <> 0){
						if ($estado_cambio == 0){
							echo "<img src='images/b_red.gif'>";
						} else {
							echo "<img src='images/b_yellow.gif'>";
						}
					} elseif ($monitor_ok > 0) {
						echo "<img src='images/b_green.gif'>";
					}
					elseif ($numero_datamodules > 0) {
						echo "<img src='images/b_white.gif'>";
					}
					elseif ($monitor_down > 0) {
						echo "<img src='images/b_down.gif'>"; 
					}
				} else {
					echo "<img src='images/b_blue.gif'>";
				}
			// checks if an alert was fired recently
				echo "<td class='$tdcolor' align='center'>";
				if (check_alert_fired($id_agente) == 1) {
					echo "<img src='images/dot_red.gif'>";
				} else {
					echo "<img src='images/dot_green.gif'>";
				}
				echo "</td>";
				echo "<td class='$tdcolor'>";
				if ( $ultimo_contacto == "0000-00-00 00:00:00"){
					echo $lang_label["never"];
				} else {
					$ultima = strtotime($ultimo_contacto);
					$ahora = strtotime("now");
					$diferencia = $ahora - $ultima;
					if ($biginterval > 0){
						$percentil = round($diferencia/(($biginterval*2) / 100));	
					} else {
						echo "N/A";
					}
					echo "<a href='#' class='info2'>
					<img src='reporting/fgraph.php?tipo=progress&amp;percent=".
					$percentil."&amp;height=15&amp;width=80' border='0'>
					&nbsp;<span>$ultimo_contacto</span></a>";
				}
				
			} // If pertenece/belongs to group
		}
		echo "<tr><td colspan='8'><div class='raya'></div></td></tr>";
		echo "</table><br>";
		require "bulbs.php";
	}
	else {
		echo '</table><br><div class="nf">'.$lang_label["no_agent"].'</div>';
	}

} else {
	audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Agent view");
		require ("general/noaccess.php");
}
}
?>