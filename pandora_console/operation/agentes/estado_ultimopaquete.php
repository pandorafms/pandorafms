<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

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
echo "<h3>".__('Display of last data modules received by agent');
echo "&nbsp;<a href='index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente=$id_agente&amp;tab=data'><img src='images/refresh.png' alt='' /></a>";
echo "</h3>";

$sortField = get_parameter('sort_field');
$sort = get_parameter('sort', 'none');
$selected = 'border: 1px solid black;';
$url = 'index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;tab=data&amp;id_agente=' . $id_agente;
$selectNameUp = '';
$selectNameDown = '';
$selectTypeUp = '';
$selectTypeDown = '';
$selectIntervalUp = '';
$selectIntervalDown = '';
$selectTimestampUp = '';
$selectTimestampDown = '';
$selectDataUp = '';
$selectDataDown = '';

$order[] = array('field' => 'id_module_group', 'order' => 'ASC');

switch ($sortField) {
	case 'name':
		switch ($sort) {
			case 'up':
				$selectNameUp = $selected;
				$order[] = array('field' => 'tagente_modulo.nombre', 'order' => 'ASC');
				break;
			case 'down':
				$selectNameDown = $selected;
				$order[] = array('field' => 'tagente_modulo.nombre', 'order' => 'DESC');
				break;
		}
		break;
	case 'type':
		switch ($sort) {
			case 'up':
				$selectTypeUp = $selected;
				$order[] = array('field' => 'tagente_modulo.id_tipo_modulo', 'order' => 'ASC');
				break;
			case 'down':
				$selectTypeDown = $selected;
				$order[] = array('field' => 'tagente_modulo.id_tipo_modulo', 'order' => 'DESC');
				break;
		}
		break;
	case 'interval':
		switch ($sort) {
			case 'up':
				$selectIntervalUp = $selected;
				$order[] = array('field' => 'tagente_modulo.module_interval', 'order' => 'ASC');
				break;
			case 'down':
				$selectIntervalDown = $selected;
				$order[] = array('field' => 'tagente_modulo.module_interval', 'order' => 'DESC');
				break;
		}
		break;
	case 'timestamp':
		switch ($sort) {
			case 'up':
				$selectTimestampUp = $selected;
				$order[] = array('field' => 'tagente_estado.utimestamp', 'order' => 'ASC');
				break;
			case 'down':
				$selectTimestampDown = $selected;
				$order[] = array('field' => 'tagente_estado.utimestamp', 'order' => 'DESC');
				break;
		}
		break;
	case 'data':
		switch ($sort) {
			case 'up':
				$selectDataUp = $selected;
				$order[] = array('field' => 'tagente_estado.datos', 'order' => 'ASC');
				break;
			case 'down':
				$selectDataDown = $selected;
				$order[] = array('field' => 'tagente_estado.datos', 'order' => 'DESC');
				break;
		}
		break;
		break;
	default:
		$selectNameUp = $selected;
		$selectNameDown = '';
		$selectTypeUp = '';
		$selectTypeDown = '';
		$selectIntervalUp = '';
		$selectIntervalDown = '';
		$selectTimestampUp = '';
		$selectTimestampDown = '';
		$order[] = array('field' => 'tagente_modulo.nombre', 'order' => 'ASC');
		break;
}

$modules = get_db_all_rows_filter ('tagente_modulo, tagente_estado',
	array ('tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo',
		'disabled' => 0,
		'tagente_estado.utimestamp != 0',
		'tagente_modulo.id_agente = '.$id_agente,
		'order' => $order));

if ($modules === false) {
	echo "<div class='nf'>".__('This agent doesn\'t have any module')."</div>";
	return;
}

$isFunctionPolicies = enterprise_include_once ('include/functions_policies.php');

echo "<table width='98%' cellpadding='3' cellspacing='3' class='databox'>";
echo "<th><span title='" . __('Force execution') . "'>".__('F.')."</span></th>";
if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
	echo "<th><span title='" . __('Policy') . "'>".__('P.')."</span></th>";
}
echo "<th>".__('Module name') . ' ' .
			'<a href="' . $url . '&amp;sort_field=name&amp;sort=up"><img src="images/sort_up.png" style="' . $selectNameUp . '" alt="up" /></a>' .
			'<a href="' . $url . '&amp;sort_field=name&amp;sort=down"><img src="images/sort_down.png" style="' . $selectNameDown . '" alt="down" /></a>';
echo "</th>";
echo "<th>".__('Type') . ' ' .
			'<a href="' . $url . '&amp;sort_field=type&amp;sort=up"><img src="images/sort_up.png" style="' . $selectTypeUp . '" alt="up" /></a>' .
			'<a href="' . $url . '&amp;sort_field=type&amp;sort=down"><img src="images/sort_down.png" style="' . $selectTypeDown . '" alt="down" /></a>';
echo "</th>";
echo "<th>".__('int') . ' ' .
			'<a href="' . $url . '&amp;sort_field=interval&amp;sort=up"><img src="images/sort_up.png" style="' . $selectIntervalUp . '" alt="up" /></a>' .
			'<a href="' . $url . '&amp;sort_field=interval&amp;sort=down"><img src="images/sort_down.png" style="' . $selectIntervalDown . '" alt="down" /></a>';
echo "</th>";
echo "<th>".__('Description') . "</th>";
echo "<th>".__('Data') . ' ' .
	'<a href="' . $url . '&amp;sort_field=data&amp;sort=up"><img src="images/sort_up.png" style="' . $selectDataUp . '" alt="up" /></a>' .
	'<a href="' . $url . '&amp;sort_field=data&amp;sort=down"><img src="images/sort_down.png" style="' . $selectDataDown . '" alt="down" /></a>';
echo "</th>";
echo "<th>".__('Graph')."</th>";
echo "<th>".__('Raw Data')."</th>";
echo "<th>".__('Timestamp') . ' ' .
	'<a href="' . $url . '&amp;sort_field=timestamp&amp;sort=up"><img src="images/sort_up.png" style="' . $selectTimestampUp . '" alt="up" /></a>' .
	'<a href="' . $url . '&amp;sort_field=timestamp&amp;sort=down"><img src="images/sort_down.png" style="' . $selectTimestampDown . '" alt="down" /></a>';
echo "</th>";
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
		// Render module group names (fixed code)
		$nombre_grupomodulo = get_modulegroup_name ($module["id_module_group"]);
		$last_modulegroup = $module["id_module_group"];
		$colspan = 9 + (int)$isFunctionPolicies;
		echo "<tr><td class='datos3' align='center' colspan='".$colspan."'>
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
			echo "<a href='index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente=".$id_agente."&amp;id_agente_modulo=".$module["id_agente_modulo"]."&amp;flag=1&amp;tab=data&amp;refr=60'><img src='images/target.png' border='0' alt='' /></a>";
		} else {
			echo "<a href='index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente=".$id_agente."&amp;id_agente_modulo=".$module["id_agente_modulo"]."&amp;tab=data&amp;refr=60'><img src='images/refresh.png' border='0' alt='' /></a>";
		}
	}
	echo "</td>";
	
	if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
		if($module["id_policy_module"] != 0) {
			$linked = isModuleLinked($module['id_agente_modulo']);
			$id_policy = get_db_value_sql('SELECT id_policy FROM tpolicy_modules WHERE id = '.$module["id_policy_module"]);
			$name_policy = get_db_value_sql('SELECT name FROM tpolicies WHERE id = '.$id_policy);
			$policyInfo = infoModulePolicy($module["id_policy_module"]);
			
			$adopt = false;
			if (isModuleAdopt($module['id_agente_modulo'])) {
				$adopt = true;
			}
			
			if ($linked) {
				if ($adopt) {
					$img = 'images/policies_brick.png';
					$title = __('(Adopt) ') . $name_policy;
				}
				else {
					$img = 'images/policies.png';
					$title = $name_policy;
				}
			}
			else {
				if ($adopt) {
					$img = 'images/policies_not_brick.png';
					$title = __('(Unlinked) (Adopt) ') . $name_policy;
				}
				else {
					$img = 'images/unlinkpolicy.png';
					$title = __('(Unlinked) ') . $name_policy;
				}
			}
			
			echo "<td>";
			
			echo'<a href="?sec=gpolicies&amp;sec2=enterprise/godmode/policies/policies&amp;id=' . $id_policy . '">' . 
				print_image($img,true, array('title' => $title)) .
				'</a>';
			echo "</td>";
		}
		else {
			echo "<td></td>";
		}
	}
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
	echo "<td class='".$tdcolor."_id' title='".safe_output($module["nombre"])."'>";
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
		echo "</td><td class='".$tdcolor."f9' title='".safe_output($module["descripcion"])."'>"; 
		echo safe_output(substr($module["descripcion"],0,32));
		if (strlen($module["descripcion"]) > 32){
			echo "...";
		}
		echo "</td>";
	} 
	
	if ($module["id_tipo_modulo"] == 24) { // Log4x
		echo "<td class='".$tdcolor."f9' colspan='1'>&nbsp;</td>";
		echo "<td class='".$tdcolor."f9' colspan='1'>&nbsp;x</td>";
		
		switch($module["datos"]){
			case 10: echo "<td class=$tdcolor style='color:darkgreen; font-weight:bold;'>".__('TRACE')."</td>"; break;
			case 20: echo "<td class=$tdcolor style='color:darkgreen; font-weight:bold;'>".__('DEBUG')."</td>"; break;
			case 30: echo "<td class=$tdcolor style='color:darkgreen; font-weight:bold;'>".__('INFO')."</td>"; break;
			case 40: echo "<td class=$tdcolor style='color:darkorange; font-weight:bold;'>".__('WARN')."</td>"; break;
			case 50: echo "<td class=$tdcolor style='color:red; font-weight:bold;'>".__('ERROR')."</td>"; break;
			case 60: echo "<td class=$tdcolor style='color:red; font-weight:bold;'>".__('FATAL')."</td>"; break;
		}

	} else if (($module["id_tipo_modulo"] == 100) OR ($module['history_data'] == 0)) {
		echo "<td class='".$tdcolor."f9' colspan='2' title='".$module["datos"]."'>";
		echo substr(safe_output($module["datos"]),0,12);
	} else {

		$graph_type = return_graphtype ($module["id_tipo_modulo"]);

		if (is_numeric($module["datos"])){
			echo "<td class=".$tdcolor.">";
			echo format_for_graph($module["datos"] );
		}
		else {
			if (strlen($module["datos"]) > 0 ) $colspan = 2;
			else $colspan= 1;
			echo "<td class='".$tdcolor."f9' colspan='" . $colspan . "' title='".safe_output($module["datos"])."'>";
			echo substr(safe_output($module["datos"]),0,42);
		}
		echo "</td>";
			
		$handle = "stat".$nombre_tipo_modulo."_".$module["id_agente_modulo"];
		$url = 'include/procesos.php?agente='.$module["id_agente_modulo"];
		$win_handle=dechex(crc32($module["id_agente_modulo"].$module["nombre"]));
		echo "<td class=".$tdcolor." width='78'>";
		$graph_label = output_clean_strict ($module["nombre"]);
		
		echo "<a href='javascript:winopeng(\"operation/agentes/stat_win.php?type=$graph_type&period=2419200&id=".$module["id_agente_modulo"]."&label=".$graph_label."&refresh=180000\", \"month_".$win_handle."\")'><img src='images/grafica_m.png' border='0' alt='' /></a>&nbsp;";
		
		$link ="winopeng('operation/agentes/stat_win.php?type=$graph_type&period=604800&id=".$module["id_agente_modulo"]."&label=".$graph_label."&refresh=6000','week_".$win_handle."')";
		echo '<a href="javascript:'.$link.'"><img src="images/grafica_w.png" border='0' alt='' /></a>&nbsp;';
		
		$link ="winopeng('operation/agentes/stat_win.php?type=$graph_type&period=86400&id=".$module["id_agente_modulo"]."&label=".$graph_label."&refresh=600','day_".$win_handle."')";
		echo '<a href="javascript:'.$link.'"><img src="images/grafica_d.png" border='0' alt='' /></a>&nbsp;';

		$link ="winopeng('operation/agentes/stat_win.php?type=$graph_type&period=3600&id=".$module["id_agente_modulo"]."&label=".$graph_label."&refresh=60','hour_".$win_handle."')";
		echo '<a href="javascript:'.$link.'"><img src="images/grafica_h.png" border='0' alt='' /></a>';

	}
	
	
	if ($module['history_data'] == 1){
	// RAW Table data
		echo "<td class=".$tdcolor." width=70>";
		echo "<a href='index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente=$id_agente&amp;tab=data_view&amp;period=2592000&amp;id=".$module["id_agente_modulo"]."'><img src='images/data_m.png'border='0' alt='' /></a>&nbsp;&nbsp;";
		echo "<a href='index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente=$id_agente&amp;tab=data_view&amp;period=604800&amp;id=".$module["id_agente_modulo"]."'><img src='images/data_w.png'border='0' alt='' /></a>&nbsp;&nbsp;";
		echo "<a href='index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente=$id_agente&amp;tab=data_view&amp;period=86400&amp;id=".$module["id_agente_modulo"]."'><img src='images/data_d.png'border='0' alt='' /></a>";
	} else {
		echo "<td class=".$tdcolor."></td>";
	}

	echo "<td class='".$tdcolor."f9'>";
	if ($module["utimestamp"] == 0){ 
		echo __('Never');
	} else {
		$seconds = get_system_time () - $module["utimestamp"];
		if ($module['id_tipo_modulo'] < 21 && $module["module_interval"] > 0 && $module["utimestamp"] > 0 && $seconds >= ($module["module_interval"] * 2)) {
			echo '<span class="redb">';
		} else {
			echo '<span>';
		}
	}
	print_timestamp ($module["utimestamp"], false);
	echo '</span>';
	echo "</td></tr>";
}
echo '</table>';

?>
