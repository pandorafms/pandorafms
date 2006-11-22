<?PHP 
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

	// Load icon index from ttipo_modulo
	$iconindex[]="";

	$sql_tm='SELECT id_tipo, icon FROM ttipo_modulo';
	$result_tm=mysql_query($sql_tm);
	while ($row_tm=mysql_fetch_array($result_tm)){
		$iconindex[$row_tm["id_tipo"]] = $row_tm["icon"];
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
	$texto='';
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
			// Render module group names  (fixed code)
			$nombre_grupomodulo = dame_nombre_grupomodulo ($row3["id_module_group"]);
			if ($nombre_grupomodulo != ""){
				if (($label_group == 0) || ($last_label != $nombre_grupomodulo)){
				// Show label module group
					$label_group = -1;
					$last_label = $nombre_grupomodulo;
					echo "<tr><td class='datos3' align='center' colspan=9><b>".$nombre_grupomodulo."</b>";
				}
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
						echo "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$id_agente."&id_agente_modulo=".$row3["id_agente_modulo"]."&flag=1&refr=60'><img src='images/target.gif' border=0></a>";
					} else {
						echo "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$id_agente."&id_agente_modulo=".$row3["id_agente_modulo"]."&flag=1&refr=60'><img src='images/refresh.gif' border=0></a>";
					}
				} 				
			} 
			$nombre_grupomodulo = dame_nombre_grupomodulo ($row3["id_module_group"]);
			if ($nombre_grupomodulo != ""){
				if (($label_group == 0) || ($last_label != $nombre_grupomodulo)){	// Show label module group
					$label_group = -1;
					$last_label = $nombre_grupomodulo;
					$texto = $texto. "<td class='$tdcolor' align='center' colspan=7><b>".$nombre_grupomodulo."</b>";
				}
			}
			
			$nombre_tipo_modulo = dame_nombre_tipo_modulo($row3["id_tipo_modulo"]);
			echo "<td class='".$tdcolor."_id'>";
			echo salida_limpia(substr($row3["nombre"],0,15));
			echo "<td class='".$tdcolor."'>";
			echo "<img src='images/".$iconindex[$row3["id_tipo_modulo"]]."' border=0>";
			echo "<td class='".$tdcolor."'>";
			if ($row3["module_interval"] != 0)
				echo $row3["module_interval"];
			else
				echo $intervalo_agente;
			//echo $nombre_tipo_modulo;
			echo "<td class='".$tdcolor."f9' title='".$row3["descripcion"]."'>"; 
			echo salida_limpia(substr($row3["descripcion"],0,32));
			if (strlen($row3["descripcion"]) > 32){
				echo "...";
			}
			// For types not string type (3 data_string, 9 tcp_string, 14 snmp_string)
			if (($row3["id_tipo_modulo"] != 3) AND ($row3["id_tipo_modulo"] != 10) AND ($row3["id_tipo_modulo"] != 17)){
				echo "<td class=".$tdcolor.">";
				if (($row3["datos"] != 0) AND (is_numeric($row3["datos"]))){
					$mytempdata = fmod($row3["datos"], $row3["datos"]);
					if ($mytempdata == 0)
						$myvalue = intval($row3["datos"]);
					else
						$myvalue = $row3["datos"];
					if ($myvalue > 1000){ // Add sufix "M" for thousands
						$mytempdata = $myvalue / 1000;
						echo $mytempdata." M";
					} else 
					echo substr($myvalue,0,12);
				} elseif ($row3["datos"] == 0)
					echo "0";
				else
					echo substr($row3["datos"],0,12);
				$handle = "stat".$nombre_tipo_modulo."_".$nombre_agente;
				$url = 'reporting/procesos.php?agente='.$nombre_agente;
				$win_handle=dechex(crc32($nombre_agente.$row3["nombre"]));
				echo "<td class=".$tdcolor." width='78'>";
				
				echo "<a href='javascript:winopeng(\"reporting/stat_win.php?tipo=mes&id=".$row3["id_agente_modulo"]."&refresh=180000\", \"mes_".$win_handle."\")'><img  src='images/grafica_m.gif'></a>&nbsp;";
				
				$link ="winopeng('reporting/stat_win.php?tipo=semana&id=".$row3["id_agente_modulo"]."&refresh=6000','sem_".$win_handle."')";
				echo '<a href="javascript:'.$link.'"><img src="images/grafica_w.gif"></a>&nbsp;';
				
				$link ="winopeng('reporting/stat_win.php?tipo=dia&id=".$row3["id_agente_modulo"]."&refresh=800','dia_".$win_handle."')";
				echo '<a href="javascript:'.$link.'"><img src="images/grafica_d.gif"></a>&nbsp;';

				$link ="winopeng('reporting/stat_win.php?tipo=hora&id=".$row3["id_agente_modulo"]."&refresh=30','hora_".$win_handle."')";
				echo '<a href="javascript:'.$link.'"><img src="images/grafica_h.gif"</a>';
			}
			else { # Writing string data in different way :)
				echo "<td class='".$tdcolor."f9' colspan='2' title='".$row3["datos"]."'>";
				echo salida_limpia(substr($row3["datos"],0,42));
			}
			
			echo "<td class=".$tdcolor." width=70>";
			echo "<a href='index.php?sec=estado&sec2=operation/agentes/datos_agente&tipo=mes&id=".$row3["id_agente_modulo"]."'><img border=0 src='images/data_m.gif'>&nbsp;&nbsp;";
			echo "<a href='index.php?sec=estado&sec2=operation/agentes/datos_agente&tipo=semana&id=".$row3["id_agente_modulo"]."'><img border=0 src='images/data_w.gif'>&nbsp;&nbsp;";
			echo "<a href='index.php?sec=estado&sec2=operation/agentes/datos_agente&tipo=dia&id=".$row3["id_agente_modulo"]."'><img border=0 src='images/data_d.gif'>";
			echo "<td class='".$tdcolor."f9'>".$row3["timestamp"];

 		//}
	}
	echo '<tr><td colspan="9"><div class="raya"></div></td></tr></table>';
}
else echo "<font class='red'>".$lang_label["no_modules"]."</font>";
}
?>
