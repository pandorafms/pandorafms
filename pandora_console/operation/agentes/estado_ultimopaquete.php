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


require("include/config.php");
check_login();

if (isset($_GET["id_agente"])){
	$id_agente = $_GET["id_agente"];
}
	
// View last data packet		
// Get timestamp of last packet
$agent = get_db_row ('tagente', 'id_agente', $id_agente,
	array ('ultimo_contacto_remoto',
		'ultimo_contacto',
		'intervalo',
		'id_grupo'));
$timestamp_ref = $agent["ultimo_contacto_remoto"];
$timestamp_lof = $agent["ultimo_contacto"];
$intervalo_agente = $agent["intervalo"];

// Get last packet
$sql3 = 'SELECT * FROM tagente_modulo, tagente_estado
	WHERE tagente_modulo.disabled = 0
	AND tagente_modulo.id_agente = ' . $id_agente.
	' AND tagente_estado.utimestamp != 0
	AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
	ORDER BY id_module_group, nombre';
$label_group = 0;
$last_label = "";

// Title
echo "<h2>".__('Pandora Agents')." &raquo; ";
echo __('Display of last data modules received by agent');
echo "&nbsp;<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=$id_agente&tab=data'><img src='images/refresh.png'></A>";
echo "</h2>";

$modules = get_db_all_rows_filter ('tagente_modulo, tagente_estado',
	array ('tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo',
		'disabled' => 0,
		'tagente_estado.utimestamp != 0',
		'tagente_modulo.id_agente = '.$id_agente,
		'order' => 'id_module_group, nombre'));

if ($modules === false) {
	echo "<div class='nf'>".__('This agent doesn\'t have any module')."</div>";
	return;
}

echo "<table width='95%' cellpadding='3' cellspacing='3' class='databox'>";
echo "<th></th>";
echo "<th>".__('Module name')."</th>";
echo "<th>".__('Type')."</th>";
echo "<th>".__('int')."</th>";
echo "<th>".__('Description')."</th>";
echo "<th>".__('Data')."</th>";
echo "<th>".__('Graph')."</th>";
echo "<th>".__('Raw Data')."</th>";
echo "<th>".__('Timestamp')."</th>";
$texto=''; $last_modulegroup = 0;
$color = 1;
$write = give_acl ($config['id_user'], $agent['id_grupo'], "AW");
foreach ($modules as $module) {
	// Calculate table line color
	if ($color == 1){
		$tdcolor = "datos";
		$color = 0;
	}
	else {
		$tdcolor = "datos2";
		$color = 1;
	}

	if ($module["id_module_group"] != $last_modulegroup ){
		// Render module group names  (fixed code)
		$nombre_grupomodulo = get_modulegroup_name ($module["id_module_group"]);
		$last_modulegroup = $module["id_module_group"];
		echo "<tr><td class='datos3' align='center' colspan='9'>
		<b>".$nombre_grupomodulo."</b></td></tr>";
	}
	
	// Begin to render data ...
	echo "<tr><td class='$tdcolor'>";
	// Render network exec module button, only when
	// Agent Write for this module and group, is given
	// Is a network module 
	// Has flag = 0
	if ($write && $module["id_modulo"] > 1 && $module["id_tipo_modulo"] < 100) {
		if ($module["flag"] == 0) {
			echo "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$id_agente."&id_agente_modulo=".$module["id_agente_modulo"]."&flag=1&tab=data&refr=60'><img src='images/target.png' border='0'></a>";
		} else {
			echo "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$id_agente."&id_agente_modulo=".$module["id_agente_modulo"]."&tab=data&refr=60'><img src='images/refresh.png' border='0'></a>";
		}
	}
	echo "</td>";
	$nombre_grupomodulo = get_modulegroup_name ($module["id_module_group"]);
	if ($nombre_grupomodulo != ""){
		if (($label_group == 0) || ($last_label != $nombre_grupomodulo)){	// Show label module group
			$label_group = -1;
			$last_label = $nombre_grupomodulo;
			$texto = $texto. "
			<td class='$tdcolor' align='center' colspan='7'>
			<b>".$nombre_grupomodulo."</b></td>";
		}
	}
	$nombre_tipo_modulo = get_moduletype_name ($module["id_tipo_modulo"]);
	echo "<td class='".$tdcolor."_id' title='".salida_limpia($module["nombre"])."'>";
	print_string_substr ($module["nombre"]);
	echo "</td><td class='".$tdcolor."'> ";
	
	print_moduletype_icon ($module["id_tipo_modulo"]);
	echo "</td><td class='".$tdcolor."'>";
	if ($module["module_interval"] != 0){
		echo $module["module_interval"];
		$real_interval = $module["module_interval"];
	} else {
		echo $intervalo_agente;
		$real_interval = $intervalo_agente;
	}

	if (($module["id_tipo_modulo"] != 3)
	AND ($module["id_tipo_modulo"] != 10)
	AND ($module["id_tipo_modulo"] != 17)
	AND ($module["id_tipo_modulo"] != 23)){
		echo "</td><td class='".$tdcolor."f9' title='".salida_limpia($module["descripcion"])."'>"; 
		echo salida_limpia(substr($module["descripcion"],0,32));
		if (strlen($module["descripcion"]) > 32){
			echo "...";
		}
		echo "</td>";
	} 
	if (($module["id_tipo_modulo"] == 100) OR ($module['history_data'] == 0)) {
		echo "<td class='".$tdcolor."f9' colspan='2' title='".$module["datos"]."'>";
		echo substr(salida_limpia($module["datos"]),0,12);
	} else {
		// String uses colspan2 and different graphtype
		if (($module["id_tipo_modulo"] == 3)
		OR ($module["id_tipo_modulo"] == 10)
		OR ($module["id_tipo_modulo"] == 17)
		OR ($module["id_tipo_modulo"] == 23)){
			$graph_type = "string";
			echo "<td class='".$tdcolor."f9' colspan=2 title='".salida_limpia($module["datos"])."'>";
		}
		elseif (($module["id_tipo_modulo"] == 2)
		OR ($module["id_tipo_modulo"] == 6)
		OR ($module["id_tipo_modulo"] == 21)
		OR ($module["id_tipo_modulo"] == 18)
		OR ($module["id_tipo_modulo"] == 9)) {
			$graph_type = "boolean";
			echo "<td class=".$tdcolor.">";
		}
		else {
			$graph_type = "sparse";
			echo "<td class=".$tdcolor.">";
		}

		// Kind of data
		if (is_numeric($module["datos"])) {
			echo format_for_graph($module["datos"] );
		} else
			echo substr(salida_limpia($module["datos"]),0,42);
		
			
		$handle = "stat".$nombre_tipo_modulo."_".$module["id_agente_modulo"];
		$url = 'reporting/procesos.php?agente='.$module["id_agente_modulo"];
		$win_handle=dechex(crc32($module["id_agente_modulo"].$module["nombre"]));
		echo "<td class=".$tdcolor." width='78'>";
		$graph_label = output_clean_strict ($module["nombre"]);
		
		echo "<a href='javascript:winopeng(\"reporting/stat_win.php?type=$graph_type&period=2419200&id=".$module["id_agente_modulo"]."&label=".$graph_label."refresh=180000\", \"month_".$win_handle."\")'><img src='images/grafica_m.png' border=0></a>&nbsp;";
		
		$link ="winopeng('reporting/stat_win.php?type=$graph_type&period=604800&id=".$module["id_agente_modulo"]."&label=".$graph_label."&refresh=6000','week_".$win_handle."')";
		echo '<a href="javascript:'.$link.'"><img src="images/grafica_w.png" border=0></a>&nbsp;';
		
		$link ="winopeng('reporting/stat_win.php?type=$graph_type&period=86400&id=".$module["id_agente_modulo"]."&label=".$graph_label."&refresh=600','day_".$win_handle."')";
		echo '<a href="javascript:'.$link.'"><img src="images/grafica_d.png" border=0></a>&nbsp;';

		$link ="winopeng('reporting/stat_win.php?type=$graph_type&period=3600&id=".$module["id_agente_modulo"]."&label=".$graph_label."&refresh=60','hour_".$win_handle."')";
		echo '<a href="javascript:'.$link.'"><img src="images/grafica_h.png" border=0></a>';
	}
	
	
	if ($module['history_data'] == 1){
	  // RAW Table data
	  echo "<td class=".$tdcolor." width=70>";
	  echo "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=$id_agente&tab=data_view&period=2592000&id=".$module["id_agente_modulo"]."'><img border=0 src='images/data_m.png'></a>&nbsp;&nbsp;";
	  echo "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=$id_agente&tab=data_view&period=604800&id=".$module["id_agente_modulo"]."'><img border=0 src='images/data_w.png'></a>&nbsp;&nbsp;";
	  echo "<a href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=$id_agente&tab=data_view&period=86400&id=".$module["id_agente_modulo"]."'><img border=0 src='images/data_d.png'></a>";
	} else {
		echo "<td class=".$tdcolor."></td>";
	}
	  

	echo "<td class='".$tdcolor."f9'>";
	if ($module["timestamp"] == "0000-00-00 00:00:00"){ 
		echo __('Never');
	} else {
		$ahora = get_system_time ();
		// Async modules
		if (($module["id_tipo_modulo"] > 20) AND ($module["id_tipo_modulo"] < 100)){
			 echo human_time_comparation($module["timestamp"]);
		} else {
			if ( ($ahora - $module["utimestamp"]) > ($real_interval*2)) {
				echo "<font color='red'>";
				echo human_time_comparation($module["timestamp"]);
				echo "</font>";
			} else
				echo human_time_comparation($module["timestamp"]);
		}
	}
	echo "</td></tr>";
}
echo '</table>';

?>
