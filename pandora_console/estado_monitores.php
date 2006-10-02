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

// Load globar vars
require("include/config.php");
if (comprueba_login() == 0) {

	// $id_agente can be obtained as global variable or GET param.
	if (isset($_GET["id_agente"])){
		$id_agente = $_GET["id_agente"];
	}
	echo "<h3>".$lang_label["monitor_listing"]."<a href='help/".$help_code."/chap3.php#3323' target='_help' class='help'>&nbsp;<span>".$lang_label["help"]."</span></a></h3>";
	// Get all module from agent
	$sql_t='SELECT * FROM tagente_estado, tagente_modulo WHERE tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.id_agente='.$id_agente.' and tagente_estado.estado != 100 order by tagente_modulo.nombre';
	$result_t=mysql_query($sql_t);
	if (mysql_num_rows ($result_t)) {
		
		echo "<table width='750' cellpadding=3 cellspacing=3>";
		echo "<tr><th>".$lang_label["type"]."<th>".$lang_label["module_name"]."<th>".$lang_label["description"]."<th>".$lang_label["status"]."<th>".$lang_label["interval"]."<th>".$lang_label["last_contact"];
		$color=0;
		while ($row_t=mysql_fetch_array($result_t)){
			# For evey module in the status table
			$est_modulo = $row_t["nombre"];
			$est_tipo = dame_nombre_tipo_modulo($row_t["id_tipo_modulo"]);
			$est_description = $row_t["descripcion"];
			$est_timestamp = $row_t["timestamp"];
			$est_estado = $row_t["estado"];
			$est_datos = $row_t["datos"];
			$est_cambio = $row_t["cambio"];
			$est_interval = $row_t["module_interval"];
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
				$ahora=date("Y/m/d H:i:s");
				$seconds = strtotime($ahora) - strtotime($row_t["timestamp"]);
				if ($seconds >= ($temp_interval*2)) // If every interval x 2 secs. we get nothing, there's and alert
					$agent_down = 1;
				else
					$agent_down = 0;
				
				echo "<tr><td class='".$tdcolor."'>".$est_tipo;
				echo "<td class='".$tdcolor."'>".$est_modulo;
				echo "<td class='".$tdcolor."f9'>".substr($est_description,0,32);
				// echo "<td class='datos'>".$row3["datos"];
				if ($agent_down == 1)
					echo  "<td class='".$tdcolor."' align='center'><img src='images/b_down.gif'>";
				else	
					if ($est_estado == 1)
						if ($est_cambio ==1)
							echo "<td class='".$tdcolor."' align='center'><img src='images/b_yellow.gif'>";
						else
							echo  "<td class='".$tdcolor."' align='center'><img src='images/b_red.gif'>";
					else
						echo  "<td class='".$tdcolor."' align='center'><img src='images/b_green.gif'>";
				echo "<td class='".$tdcolor."'>";
				echo  $temp_interval;
				echo  "<td class='".$tdcolor."f9'>";
				if ($agent_down == 1) // Si el agente esta down, lo mostramos en negrita y en rojo
					echo  "<b><font color='red'>";
	
				echo  $row_t["timestamp"];
			}
		}
		echo '<tr><td colspan="7"><div class="raya"></div></td></tr></table>';
	}
	else 
		echo "<font class='red'>".$lang_label["no_monitors"]."</font>";
}
?>