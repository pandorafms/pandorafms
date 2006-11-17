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

	if (isset($_GET["id_agente"])){
		$id_agente = $_GET["id_agente"];
	// Connect BBDD
		$sql1='SELECT * FROM tagente WHERE id_agente = '.$id_agente;
		$result=mysql_query($sql1);
		if ($row=mysql_fetch_array($result)){
			$intervalo = $row["intervalo"]; // Interval in seconds to receive data
			$nombre_agente = $row["nombre"];
			$direccion_agente =$row["direccion"];
			$ultima_act = $row["ultimo_contacto"];
			$ultima_act_remota =$row["ultimo_contacto_remoto"];
			$comentarios = $row["comentarios"];
			$id_grupo = $row["id_grupo"];
			$id_os= $row["id_os"];
			$os_version = $row["os_version"];
			$agent_version = $row["agent_version"];
			$disabled= $row["disabled"];
			$agent_type= $row["agent_type"];
			$server = $row["id_server"];
		} else
			{
			echo "<h3 class='error'>".$lang_label["agent_error"]."</h3>";
			echo "</table>";
				include ("general/footer.php");
				exit;
			}
	}
	
	// Load icon index from tgrupos
	$iconindex_g[]="";

	$sql_tg='SELECT id_grupo, icon FROM tgrupo';
	$result_tg=mysql_query($sql_tg);
	while ($row_tg=mysql_fetch_array($result_tg)){
		$iconindex_g[$row_tg["id_grupo"]] = $row_tg["icon"];
	}
	
	echo "<h2>".$lang_label["ag_title"]."</h2>";
	echo "<h3>".$lang_label["view_agent_general_data"]."<a href='help/".$help_code."/chap3.php#3321' target='_help' class='help'>&nbsp;<span>".$lang_label["help"]."</span></a></h3>";
	echo '<table cellspacing=3 cellpadding=3 width=750>';	
	echo '<tr>
	<td class="datos"><b>'.$lang_label["agent_name"].'</b></td>
	<td class="datos">'.salida_limpia($nombre_agente);

	echo "&nbsp;<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$id_agente."&refr=60'>
	<img src='images/refresh.gif' class='top'></a>";
	if (dame_admin($_SESSION['id_usuario'])==1 )
		echo "&nbsp;<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente=".$id_agente."'>
		<img src='images/setup.gif' width='19' class='top'></a>";
	// Data base access graph
	echo '</td>';
	echo "
	<td rowspan=4>
	<b>".$lang_label["agent_access_rate"]."</b><br><br>
	<img  src='reporting/fgraph.php?id=".$id_agente."&tipo=agentaccess&periodo=1440'>
	</td>";
	echo '</tr>';
	echo '<tr>
	<td class="datos2"><b>'.$lang_label["ip_address"].'</b></td>
	<td class="datos2">'.salida_limpia($direccion_agente);
	if ($agent_type == 0) {
		echo '<tr>
		<td class="datos"><b>'.$lang_label["os"].'</b></td>
		<td class="datos">
		<img src="images/'.dame_so_icon($id_os).'"> - '.dame_so_name($id_os).'</td>';
	} elseif ($agent_type == 1) {
		echo '<tr>
		<td class="datos2"><b>'.$lang_label["agent_type"].'</b></td>
		<td class="datos2"><img src="images/network.gif"></td>';
	}
	if ($os_version != "") echo ' v'.salida_limpia($os_version);
	echo '</tr>';
	echo '<tr>
	<td class="datos2"><b>'.$lang_label["interval"].'</b></td>
	<td class="datos2">'.$intervalo.'</td>';
	echo '</tr>';	
	echo '<tr>
	<td class="datos"><b>'.salida_limpia($lang_label["description"]).'</b></td>
	<td class="datos">'.$comentarios.'</td>';	

	echo "<td rowspan=6><b>".$lang_label["agent_module_shareout"]."</b><br><br>
	<img src='reporting/fgraph.php?id=".$id_agente."&tipo=agentmodules' >
	</td>";
	echo '</tr>';
	echo '<tr>
	<td class="datos2"><b>'.salida_limpia($lang_label["group"]).'</b></td>
	<td class="datos2">
	<img src="images/g_'.$iconindex_g[$row["id_grupo"]].'.gif" >
	( '.dame_grupo($id_grupo).' )';
	if ($agent_type == 0) {	
		echo '<tr><td class="datos"><b>'.$lang_label["agentversion"].'</b>
		<td class="datos">'.salida_limpia($agent_version).'</td>';
	}	

	// Total packets
	echo '<tr>
	<td class="datos2"><b>'.$lang_label["total_packets"].'</b></td>
	<td class="datos2">';
	$total_paketes= 0;
	$id_agente = dame_agente_id($nombre_agente);
	$sql_2='SELECT * FROM tagente_modulo WHERE id_agente = '.$id_agente;
	$result_t=mysql_query($sql_2);
	while ($row=mysql_fetch_array($result_t)){	
		$sql_3='SELECT COUNT(*) FROM tagente_datos WHERE id_agente_modulo = '.$row["id_agente_modulo"];
		$result_3=mysql_query($sql_3);
		$row3=mysql_fetch_array($result_3);
		$total_paketes = $total_paketes + $row3[0];	
	}	
	echo $total_paketes;
	echo '</td></tr>';
	// Last contact
	echo '<tr>
		<td class="datos">
		<b>'.$lang_label["last_contact"]." / ".$lang_label["remote"].'</b>
		</td>
		<td class="datosf9">';
	if ($ultima_act == "0000-00-00 00:00:00"){ 
		echo $lang_label["never"];
	} else {
		echo $ultima_act;
	}
	echo " / ";
	if ($ultima_act_remota == "0000-00-00 00:00:00"){ 
		echo $lang_label["never"];
	} else {
		echo $ultima_act_remota;
	}
	
	// Asigned/active server
	echo '<tr><td class="datos2"><b>'.$lang_label["server_asigned"].'</b></td>
	<td class="datos2">';
	if ($server == ""){ 
		echo "N/A";
	} else {
		echo give_server_name($server);
	}

	// Next contact

	$ultima = strtotime($ultima_act);
	$ahora = strtotime("now");
	$diferencia = $ahora - $ultima;

	// Get higher interval set for the set of modules from this agent
	$sql_maxi ="SELECT MAX(module_interval) FROM tagente_modulo WHERE id_agente = ".$id_agente;
	$result_maxi=mysql_query($sql_maxi);
	if ($row_maxi=mysql_fetch_array($result_maxi))
		if ($row_maxi[0] > 0 )
			$intervalo = $row_maxi[0];

	if ($intervalo > 0){
		$percentil = round($diferencia/(($intervalo*2) / 100));	
	} else {
		$percentil = -1;
	}
	echo '<tr>
	<td class="datos"><b>'.$lang_label["next_contact"].'</b>
	<td class="datos">
	<img src="reporting/fgraph.php?tipo=progress&percent='.$percentil.'&height=20&width=200">
	</td>
	</tr>';
	
	echo '</table>';
	
}

?>