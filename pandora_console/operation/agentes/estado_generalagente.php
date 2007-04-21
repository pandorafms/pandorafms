<?php
// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development and project architecture and management
// Copyright (c) 2004-2007 Raul Mateos Martin, raulofpandora@gmail.com
// CSS and some PHP additions
// Copyright (c) 2006-2007 Jonathan Barajas, jonathan.barajas[AT]gmail[DOT]com
// Javascript Active Console code.
// Copyright (c) 2006 Jose Navarro <contacto@indiseg.net>
// Additions to Pandora FMS 1.2 graph code and new XML reporting template management
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas, info@artica.es
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
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

	echo "<h2>".$lang_label["ag_title"]." &gt; ".$lang_label["view_agent_general_data"]."<a href='help/".$help_code."/chap3.php#3321' target='_help' class='help'>&nbsp;<span>".$lang_label["help"]."</span></a></h2>";

	// Blank space below title
	echo "<div style='height: 10px'> </div>";
	
	echo '<table cellspacing="0" cellpadding="0" width="800" border=0>';
	echo "<tr><td>";
	echo '<table cellspacing="4" cellpadding="4" border=0>';
	echo "<tr><td class='lb_view' rowspan='12' width='1'>";
	echo '<tr>
	<td class="datos"><b>'.$lang_label["agent_name"].'</b></td>
	<td class="datos"><b>'.strtoupper(salida_limpia($nombre_agente));

	echo "<td class='datos2' width='40'><a class='info' href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$id_agente."&refr=60'><span>".$lang_label["refresh_data"]."</span>"."<img src='images/refresh.gif' class='top' border=0></a>&nbsp;&nbsp;";
	// Data base access graph
	echo '</td>';
	
	echo '</tr>';
	echo '<tr>
	<td class="datos2"><b>'.$lang_label["ip_address"].'</b></td>
	<td class="datos2" colspan=2>';

	
	// Show all address for this agent, show first the main IP (taken from tagente table)
	echo "<select style='padding:0px' name='notused' size=1>";
	echo "<option>".salida_limpia($direccion_agente);
	$sql_2='SELECT id_a FROM taddress_agent WHERE id_agent = '.$id_agente;
	$result_t=mysql_query($sql_2);
	while ($row=mysql_fetch_array($result_t)){	
		$sql_3='SELECT ip FROM taddress WHERE id_a = '.$row[0];
		$result_3=mysql_query($sql_3);
		$row3=mysql_fetch_array($result_3);
		if ($direccion_agente != $row3[0])
			echo "<option value='".salida_limpia($row3[0])."'>".salida_limpia($row3[0])."&nbsp;&nbsp;";
	}
	echo "</select>";

	
	//if ($agent_type == 0) {
		echo '<tr>
		<td class="datos"><b>'.$lang_label["os"].'</b></td>
		<td class="datos" colspan=2>
		<img src="images/'.dame_so_icon($id_os).'"> - '.dame_so_name($id_os);
		if ($os_version != "")
			echo ' '.salida_limpia($os_version);
	/*
	} elseif ($agent_type == 1) {
		echo '<tr>
		<td class="datos"><b>'.$lang_label["agent_type"].'</b></td>
		<td class="datos" colspan=2><img src="images/network.gif">';
	}*/
	echo '</td>';
	echo '</tr>';
	echo '<tr>
	<td class="datos2"><b>'.$lang_label["interval"].'</b></td>
	<td class="datos2" colspan=2>'.$intervalo.'</td>';
	echo '</tr>';	
	echo '<tr>
	<td class="datos"><b>'.salida_limpia($lang_label["description"]).'</b></td>
	<td class="datos" colspan=2>'.$comentarios.'</td>';

	echo '</tr>';
	echo '<tr>
	<td class="datos2"><b>'.salida_limpia($lang_label["group"]).'</b></td>
	<td class="datos2" colspan="2">
	<img class="bot" src="images/groups_small/'.show_icon_group($id_grupo).'.png" >&nbsp;&nbsp; '.dame_grupo($id_grupo).'</td></tr>';
	if ($agent_type == 0) {	
		echo '<tr><td class="datos"><b>'.$lang_label["agentversion"].'</b>
		<td class="datos" colspan=2>'.salida_limpia($agent_version).'</td>';
	} else {
		echo '<tr><td class="datos"><b>'.$lang_label["agentversion"].'</b>
		<td class="datos" colspan=2>N/A</td>';
	}

	// Total packets
	echo '<tr>
	<td class="datos2"><b>'.$lang_label["total_packets"].'</b></td>
	<td class="datos2" colspan=2>';
	$total_paketes= 0;

	$sql_3='SELECT COUNT(*) FROM tagente_datos WHERE id_agente = '.$id_agente;
	$result_3=mysql_query($sql_3);
	$row3=mysql_fetch_array($result_3);
	$total_paketes = $row3[0];

	echo $total_paketes;
	echo '</td></tr>';
	// Last contact
	echo '<tr>
		<td class="datos">
		<b>'.$lang_label["last_contact"]." / ".$lang_label["remote"].'</b>
		</td>
		<td class="datosf9" colspan="2">';
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
	<td class="datos2" colspan=2">';
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
	echo "<tr>
	<td class='datos'><b>".$lang_label['next_contact']."</b>
	<td class='datos' colspan=2>
	<img src='reporting/fgraph.php?tipo=progress&percent=".$percentil."&height=20&width=200'>
	</td>
	</tr>
	<tr><td colspan='4'><div class='raya'></div></td></tr>
	</table>

	<td valign='top'>
	
	<table border=0>
	<tr>
	<td>
		<b>".$lang_label["agent_access_rate"]."</b><br><br>
		<img border=1 src='reporting/fgraph.php?id=".$id_agente."&tipo=agentaccess&periodo=1440&height=70&width=280'>
		</td>
	</tr><tr>
		<td><div style='height:25px'> </div>
		<b>".$lang_label["agent_module_shareout"]."</b><br><br>
		<img src='reporting/fgraph.php?id=".$id_agente."&tipo=agentmodules&height=150&width=280' >
		</td></tr>
	</table></td></tr>
	</table>
	";
	
}

?>
