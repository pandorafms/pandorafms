<?PHP 
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
?>

<!-- Javascript  -->
<script language="javascript1.2" type="text/javascript">
<!--
function winopeng(url,wid) {
	nueva_ventana=open(url,wid,"width=580,height=250,status=no,toolbar=no,menubar=no");
	// WARNING !! Internet Explorer DOESNT SUPPORT "-" CARACTERS IN WINDOW HANDLE VARIABLE
	status =wid;
}
function help_popup(help_id) {
	nueva_ventana=open("general/pandora_help.php?id=1","width=300,height=100,status=no,toolbar=no,menubar=no");
}
-->
</script>

<?php

require("include/config.php");
if (comprueba_login() == 0) {

	if (isset($_GET["id_agente"])){
		$id_agente = $_GET["id_agente"];
	}
	
	// View last data packet		
	// Get timestamp of last packet
	$sql_t='SELECT * FROM tagente WHERE id_agente = '.$id_agente;
	$result_t=mysql_query($sql_t);
	$row_t=mysql_fetch_array($result_t);
	$timestamp_ref = $row_t["ultimo_contacto_remoto"];
	$timestamp_lof = $row_t["ultimo_contacto"];
	$intervalo_agente = $row_t["intervalo"];

	// Get last packet
	$sql3='SELECT * FROM tagente_modulo, tagente_estado WHERE tagente_modulo.id_agente = '.$id_agente.' AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo ORDER BY id_module_group, nombre';
	$label_group=0;
	$last_label = "";
	echo "<h3>".$lang_label["last_data_chunk"]."<a href='help/".$help_code."/chap3.php#3322' target='_help' class='help'>&nbsp;<span>".$lang_label["help"]."</span></a></h3>";
	$result3=mysql_query($sql3);
	if (mysql_num_rows ($result3)) {
	echo "<table width='750' cellpadding='3' cellspacing='3'>";
	echo "<th>X</th>";
	echo "<th>".$lang_label["module_name"]."</th>";
	echo "<th>".$lang_label["type"]."</th>";
	echo "<th>".$lang_label["int"]."</th>";
	echo "<th>".$lang_label["description"]."</th>";
	echo "<th>".$lang_label["data"]."</th>";
	echo "<th>".$lang_label["graph"]."</th>";
	echo "<th>".$lang_label["raw_data"]."</th>";
	echo "<th>".$lang_label["timestamp"]."</th>";
	$texto=''; $last_modulegroup = 0;
	$color = 1;
	while ($row3=mysql_fetch_array($result3)){
			// Calculate table line color
			if ($color == 1){
				$tdcolor = "datos";
				$color = 0;
			}
			else {
				$tdcolor = "datos2";
				$color = 1;
			}

			if ($row3["id_module_group"] != $last_modulegroup ){
				// Render module group names  (fixed code)
				$nombre_grupomodulo = dame_nombre_grupomodulo ($row3["id_module_group"]);
				$last_modulegroup = $row3["id_module_group"];
				echo "<tr><td class='datos3' align='center' colspan=9><b>".$nombre_grupomodulo."</b>";
			}
			
			// Begin to render data ...
			echo "<tr><td class='$tdcolor'>";
			// Render network exec module button, only when
			// Agent Write for this module and group, is given
			// Is a network module 
			// Has flag = 0
			$id_grupo = $row_t["id_grupo"];
			$id_usuario=$_SESSION["id_usuario"];
			if (give_acl($id_usuario, $id_grupo, "AW")==1){
				if ($row3["id_tipo_modulo"] > 4){
					if ($row3["flag"] == 0){
						echo "<a href='index.php?sec=estado&
						sec2=operation/agentes/ver_agente&
						id_agente=".$id_agente."&
						id_agente_modulo=".$row3["id_agente_modulo"]."&
						flag=1&
						tab=data&
						refr=60'>
						<img src='images/target.gif' border=0></a>";
					} else {
						echo "<a href='index.php?sec=estado&
						sec2=operation/agentes/ver_agente&
						id_agente=".$id_agente."&
						id_agente_modulo=".$row3["id_agente_modulo"]."&
						tab=data&
						refr=60'>
						<img src='images/refresh.gif' border=0></a>";
					}
				} 				
			} 
			$nombre_grupomodulo = dame_nombre_grupomodulo ($row3["id_module_group"]);
			if ($nombre_grupomodulo != ""){
				if (($label_group == 0) || ($last_label != $nombre_grupomodulo)){	// Show label module group
					$label_group = -1;
					$last_label = $nombre_grupomodulo;
					$texto = $texto. "
					<td class='$tdcolor' align='center' colspan=7>
					<b>".$nombre_grupomodulo."</b>";
				}
			}
			
			$nombre_tipo_modulo = dame_nombre_tipo_modulo($row3["id_tipo_modulo"]);
			echo "<td class='".$tdcolor."_id'>";
			echo salida_limpia(substr($row3["nombre"],0,15));
			echo "<td class='".$tdcolor."'>";
			echo "<img src='images/".show_icon_type($row3["id_tipo_modulo"])."' border=0>";
			echo "<td class='".$tdcolor."'>";
			if ($row3["module_interval"] != 0){
				echo $row3["module_interval"];
				$real_interval = $row3["module_interval"];
			} else {
				echo $intervalo_agente;
				$real_interval = $intervalo_agente;
			}
			//echo $nombre_tipo_modulo;
			echo "<td class='".$tdcolor."f9' title='".$row3["descripcion"]."'>"; 
			echo salida_limpia(substr($row3["descripcion"],0,32));
			if (strlen($row3["descripcion"]) > 32){
				echo "...";
			}
			// For types not string type (3 data_string, 9 tcp_string, 14 snmp_string)
			if (($row3["id_tipo_modulo"] != 3) 
			AND ($row3["id_tipo_modulo"] != 10) 
			AND ($row3["id_tipo_modulo"] != 17)){
				echo "<td class=".$tdcolor.">";
				if (($row3["datos"] != 0) AND (is_numeric($row3["datos"]))) {
					$mytempdata = fmod($row3["datos"], 1);
					if ($mytempdata == "0")
						$myvalue = intval($row3["datos"]);
					else
						$myvalue = $row3["datos"];
					if ($myvalue > 1000000) { // Add sufix "M" for millions
						$mytempdata = $myvalue / 1000000;
						echo format_numeric($mytempdata)." M";
					} elseif ( $myvalue > 1000){ // Add sufix "K" for thousands
                                                $mytempdata = $myvalue / 1000;
                                                echo format_numeric ($mytempdata)." K";
					} else
						echo substr($myvalue,0,12);
				} elseif ($row3["datos"] == 0)
					echo "0";
				else
					echo substr($row3["datos"],0,12);
					
				$handle = "stat".$nombre_tipo_modulo."_".$row3["id_agente_modulo"];
				$url = 'reporting/procesos.php?agente='.$row3["id_agente_modulo"];
				$win_handle=dechex(crc32($row3["id_agente_modulo"].$row3["nombre"]));
				echo "<td class=".$tdcolor." width='78'>";
				$graph_label = entrada_limpia($row3["nombre"]." - ".$row3["id_agente_modulo"]);
				
				echo "<a href='javascript:winopeng(\"reporting/stat_win.php?period=2419200&id=".$row3["id_agente_modulo"]."&label=".$graph_label."refresh=180000\", \"month_".$win_handle."\")'><img  src='images/grafica_m.gif' border=0></a>&nbsp;";
				
				$link ="winopeng('reporting/stat_win.php?period=604800&id=".$row3["id_agente_modulo"]."&label=".$graph_label."&refresh=6000','week_".$win_handle."')";
				echo '<a href="javascript:'.$link.'"><img src="images/grafica_w.gif" border=0></a>&nbsp;';
				
				$link ="winopeng('reporting/stat_win.php?period=86400&id=".$row3["id_agente_modulo"]."&label=".$graph_label."&refresh=600','day_".$win_handle."')";
				echo '<a href="javascript:'.$link.'"><img src="images/grafica_d.gif" border=0></a>&nbsp;';

				$link ="winopeng('reporting/stat_win.php?period=3600&id=".$row3["id_agente_modulo"]."&label=".$graph_label."&refresh=60','hour_".$win_handle."')";
				echo '<a href="javascript:'.$link.'"><img src="images/grafica_h.gif" border=0></a>';
			}
			// STRING DATA
			else { # Writing string data in different way :)
				echo "<td class='".$tdcolor."f9' colspan='2' title='".$row3["datos"]."'>";
				echo salida_limpia(substr($row3["datos"],0,42));
			}
			
			echo "<td class=".$tdcolor." width=70>";
			echo "<a href='index.php?sec=estado&sec2=operation/agentes/datos_agente&tipo=mes&id=".$row3["id_agente_modulo"]."'><img border=0 src='images/data_m.gif'></a>&nbsp;&nbsp;";
			echo "<a href='index.php?sec=estado&sec2=operation/agentes/datos_agente&tipo=semana&id=".$row3["id_agente_modulo"]."'><img border=0 src='images/data_w.gif'></a>&nbsp;&nbsp;";
			echo "<a href='index.php?sec=estado&sec2=operation/agentes/datos_agente&tipo=dia&id=".$row3["id_agente_modulo"]."'><img border=0 src='images/data_d.gif'></a>";
			echo "<td class='".$tdcolor."f9'>";
				if ($row3["timestamp"] == "0000-00-00 00:00:00"){ 
					echo $lang_label["never"];
				} else {
					$ahora = time();
					if ( ($ahora - $row3["utimestamp"]) > ($real_interval*2)) {
						echo "<font color='red'>";
						echo $row3["timestamp"];
						echo "</font>";
					} else
						echo $row3["timestamp"];
				}
			echo "</td></tr>";
 		//}
	}
	echo '<tr><td colspan="9"><div class="raya"></div></td></tr></table>';
}
else {
	echo "<div class='nf'>".$lang_label["no_modules"]."</div>";
}	
	}
?>
