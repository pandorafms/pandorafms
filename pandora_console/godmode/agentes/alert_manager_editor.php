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



$add_component = get_parameter ("add_component",0);
echo "<h3>".__('Alert association form');
pandora_help ("alerts");
echo "</h3>";

echo '<form name="agente" method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=alert&id_agente='.$id_agente.'">';

if ($form_alerttype == "combined")
	echo "<input type='hidden' name='combined' value ='1'>";
else
	echo "<input type='hidden' name='combined' value ='0'>";

if (! isset($update_alert))
	$update_alert = -1;

if ($update_alert != 1) {
	echo '<input type="hidden" name="insert_alert" value=1>';
} else {
	echo '<input type="hidden" name="update_alert" value=1>';
	echo '<input type="hidden" name="id_aam" value="'.$alerta_id_aam.'">';
}
echo '<input type="hidden" name="id_agente" value="'.$id_agente.'">';

echo '<table width=600 cellpadding="4" cellspacing="4" class="databox_color" border=0>';

// AgentModule association
echo '<tr><td class="datos3">'.__('Assigned module');
echo '<td class="datos3">';
if ($form_alerttype != "combined"){
	if ($update_alert != 1) {
		echo '<select name="agente_modulo" style="width:210px;"> ';
		$sql2 = "SELECT id_agente_modulo, id_tipo_modulo, nombre FROM tagente_modulo WHERE id_agente = $id_agente ORDER BY nombre";
		$result2=mysql_query($sql2);
		while ($row2=mysql_fetch_array($result2)){
			if ($row2["id_tipo_modulo"] != -1) {
			$sql1='SELECT nombre FROM ttipo_modulo WHERE id_tipo = '.$row2["id_tipo_modulo"];
			$result=mysql_query($sql1);
			while ($row=mysql_fetch_array($result)){
			echo "<option value='".$row2["id_agente_modulo"]."'>".$row2["nombre"]." ( ".$row["nombre"]." )</option>";
			}
		} else // for -1, is a special module, keep alive monitor !!
			echo "<option value='".$row2["id_agente_modulo"]."'>".$row2["nombre"]."</option>";
		}
		echo "</select>";
	} else {
		$agentmodule_name = get_db_sql ("SELECT nombre FROM tagente_modulo WHERE id_agente_modulo = $alerta_id_agentemodulo");
		echo $agentmodule_name;
	}
} else {
	echo __('N/A');
}
echo '<td class="datos3">';
echo __('Priority');
echo '<td class="datos3">';

if (! isset($alert_priority)) {
	$alert_priority = 3; // Warning by default
}
print_select (get_priorities (), "alert_priority", $alert_priority, '', '', '');

// Alert type
echo '<tr><td class="datos">'. __('Alert type');
pandora_help ("alert_type");
echo '</td>';
echo '<td class="datos"><select name="tipo_alerta">';
if (isset($tipo_alerta)){
	echo "<option value='".$tipo_alerta."'>".dame_nombre_alerta($tipo_alerta)."</option>";
}
$sql1 = 'SELECT id_alerta, nombre FROM talerta ORDER BY nombre';
$result = mysql_query ($sql1);
while ($row = mysql_fetch_array ($result)){
	echo "<option value='".$row["id_alerta"]."'>".$row["nombre"]."</option>";
}
echo "</select>";

// Alert disable / enable
echo "<td class='datos'>";
echo __('Alert status');
echo "<td class='datos'>";
echo '<select name="disable_alert">';
if ((isset($alerta_disable)) AND ($alerta_disable == "1")) {
	echo "<option value='1'>".__('Disabled');
	echo "<option value='0'>".__('Enabled');
} else {
	echo "<option value='0'>".__('Enabled');
	echo "<option value='1'>".__('Disabled');
}
echo "</select>";

// Descripcion
echo '<tr><td class="datos2">'.__('Description');
echo '<td class="datos2" colspan=4><input type="text" name="descripcion" size="60" value ="'.$alerta_descripcion.'">';

// Trigger values for alert
if ($form_alerttype != "combined"){
	echo '<tr><td class="datos">'.__('Min. Value');
	echo "<a href='#' class='tip'>&nbsp;<span>";echo __('Min. possible value to consider \'valid\' values, below this limit, Pandora FMS will fire the alert')."</span></a>";
	echo '<td class="datos"><input type="text" name="minimo" size="5" value="'.$alerta_dis_min.'" style="margin-right: 70px;">';

	echo "<td class='datos'>";
	echo __('Max. Value');
	echo "<a href='#' class='tip'>&nbsp;<span>";
	echo __('Max. possible value to consider \'valid\' values, above this limit, Pandora FMS will fire the alert');
	echo "</span></a>";
	echo "<td class='datos'>";
	echo "<input type='text' name='maximo' size='5' value='$alerta_dis_max'>";

	// <!-- FREE TEXT ALERT -->

	echo '<tr><td class="datos2">'.__('Alert text')."<a href='#' class='tip'>&nbsp;<span>NOTE: This field is for matching text on data. Regular Expression Supported </span></a>";
	echo '<td class="datos2" colspan=4><input type="text" name="alert_text" size="60" value ="'.$alert_text.'">';
}

// Time Threshold (TT)    
echo '<tr><td class="datos">'.__('Time threshold');
echo "<a href='#' class='tip'>&nbsp;<span>".__('This value must be al least Module Interval * (Min.Number of Alerts + 1)')."</span></a>";
echo '<td class="datos">';
echo '<select name="time_threshold" style="margin-right: 60px;">';
if ($alerta_time_threshold != ""){ 
	echo "<option value='".$alerta_time_threshold."'>".human_time_description($alerta_time_threshold)."</option>";
}
echo '
<option value=300>5 Min.</option>
<option value=600>10 Min.</option>
<option value=900>15 Min.</option>
<option value=1800>30 Min.</option>
<option value=3600>1 Hour</option>
<option value=7200>2 Hour</option>
<option value=18000>5 Hour</option>
<option value=43200>12 Hour</option>
<option value=86400>1 Day</option>
<option value=604800>1 Week</option>
<option value=-1>Other value</option>
</select>';

// Other TT
echo '<td class="datos">';
echo __('Other');
echo '<td class="datos">';
echo '<input type="text" name="other" size="5">';

// Max / Min alerts 
echo "<tr><td class='datos2'>".__('Min. number of alerts');
echo '<td class="datos2">';
echo '<input type="text" name="min_alerts" size="5" value="';
if (isset($alerta_min_alerts)) 
	echo $alerta_min_alerts;
else
	echo 0;
echo '" style="margin-right: 10px;">';

echo '<td class="datos2">';
echo __('Max. number of alerts');
echo '<td class="datos2">';
echo '<input type="text" name="max_alerts" size="5" value="';
if (isset($alerta_max_alerts)) 
	echo $alerta_max_alerts;
else
	echo 1;
echo '" style="margin-right: 10px;">';

// Field1
echo '<tr><td class="datos">'.__('Field #1 (Alias, name)');
echo '<td class="datos" colspan=4><input type="text" name="campo_1" size="39" value="'.$alerta_campo1.'">';
echo "<a href='#' class='tip'><span><b>Macros:</b><br>_agent_<br>";
echo '_timestamp_<br>_data_<br></span></a>';

// Field2
echo '<tr><td class="datos2">'.__('Field #2 (Single Line)');
echo '<td class="datos2" colspan=4>';
echo '<input type="text" name="campo_2" size="39" value="'.$alerta_campo2.'">';
echo "<a href='#' class='tip'><span>";
echo '<b>Macros:</b><br>_agent_<br>_timestamp_<br>_data_<br></span></a>';

//Field3
echo '<tr><td class="datos">'.__('Field #3 (Full Text)');
echo '<td class="datos" colspan=4>';
echo '<textarea name="campo_3" style="height:85px; width: 380px" rows="4">';
echo $alerta_campo3;
echo '</textarea><a href="#" class="tip"><span><b>Macros:</b><br>_agent_<br>';
echo '_timestamp_<br>_data_<br></span></a>';

// Time for alerting
echo "<tr><td class='datos2'>".__('Time from');    
echo "<td class='datos2'><select name='time_from'>";
if ($time_from != ""){
	echo "<option value='$time_from'>".substr($time_from,0,5);
}

for ($a=0; $a < 48; $a++){
	echo "<option value='";
	echo render_time ($a);
	echo "'>";
	echo render_time ($a);
}
echo "<option value='23:59'>23:59";
echo "</select>";

echo "<td class='datos2'>".__('Time to');
echo "<td class='datos2'><select name='time_to'>";
if ($time_from != ""){
	echo "<option value='$time_to'>".substr($time_to,0,5);
}

for ($a=0; $a < 48; $a++){
	echo "<option value='";
	echo render_time ($a);
	echo "'>";
	echo render_time ($a);
}
echo "<option value='23:59'>23:59";
echo "</select>";

// Days of week
echo "<tr><td class='datos'>".__('Days of week');
echo "<td class='datos' colspan=4>";
echo __('Mon');
print_checkbox ("alert_d1", 1, $alert_d1);
echo "&nbsp;&nbsp;";
echo __('Tue');
print_checkbox ("alert_d2", 1, $alert_d2);
echo "&nbsp;&nbsp;";
echo __('Wed');
print_checkbox ("alert_d3", 1, $alert_d3);
echo "&nbsp;&nbsp;";
echo __('Thu');
print_checkbox ("alert_d4", 1, $alert_d4);
echo "&nbsp;&nbsp;";
echo __('Fri');
print_checkbox ("alert_d5", 1, $alert_d5);
echo "&nbsp;&nbsp;";
echo __('Sat');
print_checkbox ("alert_d6", 1, $alert_d6);
echo "&nbsp;&nbsp;";
echo __('Sun');
print_checkbox ("alert_d7", 1, $alert_d7);

// Field2 Recovery
echo '<tr><td class="datos2">'.__('Field #2 (Rec)');
echo '<td class="datos2">';
echo '<input type="text" name="campo_2_rec" size="20" value="'.$alerta_campo2_rec.'">';

// Alert recovery disable / enable
echo '<td class="datos2">'. __('Alert recovery');
pandora_help ("alert_recovery");
echo '</td>';
echo "<td class='datos2'>";
echo '<select name="alert_recovery">';
if ((isset($alert_recovery)) AND ($alert_recovery == "1")) {
	echo "<option value='1'>".__('Enabled');
	echo "<option value='0'>".__('Disabled');
} else {
	echo "<option value='0'>".__('Disabled');
	echo "<option value='1'>".__('Enabled');
}
echo "</select>";


//Field3 - Recovery
echo '<tr><td class="datos">'.__('Field #3 (Rec)');
echo '<td class="datos" colspan=4>';
echo '<input type="text" name="campo_3_rec" size="60" value="'.$alerta_campo3_rec.'">';

 // End block only if $creacion_agente != 1;

echo "</td></tr></table>";
echo '<table width=605>';
echo '<tr><td align="right">';
if ($update_alert== "1"){
	echo '<input name="updbutton" type="submit" class="sub upd" value="'.__('Update').'">';
} else {
	echo '<input name="crtbutton" type="submit" class="sub wand" value="'.__('Add').'">';
}
echo '</form>';
echo '</td></tr></table>';


if (($form_alerttype == "combined") AND ($update_alert != -1)){
	echo "<h3>".__('Combined alert components')."</h3>";
	echo '<table width=605 class="databox" border=0 cellpadding=4 cellspacing=4>';
	echo '<form method=POST action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=alert&id_agente='.$id_agente.'&update_alert='.$alerta_id_aam.'&add_component=1&form_alerttype=combined">';

	if ($form_alerttype == "combined")
		echo "<input type='hidden' name='combined' value ='1'>";
	else
		echo "<input type='hidden' name='combined' value ='0'>";

	echo '<input type="hidden" name="add_alert_combined" value="1">';
	echo '<input type="hidden" name="id_agente" value="'.$id_agente.'">';

	echo "<tr><td>";
	echo __('Source Agent/Alert');
	echo "<td>";
	echo "<select name='component_item'>";

	// Add to combo single alerts
$result_alert = mysql_query("SELECT tagente_modulo.id_agente_modulo, tagente.nombre, tagente_modulo.nombre, id_aam, tagente.id_grupo FROM talerta_agente_modulo, tagente_modulo, tagente WHERE talerta_agente_modulo.id_agente_modulo = tagente_modulo.id_agente_modulo AND tagente_modulo.id_agente = tagente.id_agente");
	while ($alertrow = mysql_fetch_array($result_alert)){ 
		if (give_acl ($config["id_user"], $alertrow[4], "AR") == 1)
		echo "<option value='".$alertrow[3]."'>(S) ".$alertrow[1]." - ".$alertrow[2];
	}

	// Add to combo combined alerts
	$result_alert = mysql_query("SELECT tagente.id_grupo, tagente.nombre, talerta_agente_modulo.id_aam, talerta_agente_modulo.descripcion FROM talerta_agente_modulo, tagente WHERE talerta_agente_modulo.id_agent = tagente.id_agente AND tagente.id_agente != '' AND tagente.id_agente > 0");


	while ($alertrow = mysql_fetch_array($result_alert)){ 
		if (give_acl ($config["id_user"], $alertrow[0], "AR"))
		echo "<option value='".$alertrow[2]."'>(C) ".$alertrow[1]." - ".$alertrow[3];
	}

	echo "</select>";


	// there is any component already in this alert ?

 	$result = mysql_query ("SELECT COUNT(*) FROM tcompound_alert, talerta_agente_modulo WHERE tcompound_alert.id = $id_aam AND talerta_agente_modulo.id_aam = tcompound_alert.id_aam");
	$row=mysql_fetch_array($result);
	if ($row[0] > 0){
		echo "<td>";
		echo __('Operation');
		echo "<td>";
		echo "<select name='component_operation'>";
		echo "<option>OR";
		echo "<option>AND";
		echo "<option>XOR";
		echo "<option>NOR";
		echo "<option>NAND";
		echo "<option>NXOR";
		echo "</select>";
	} else {
		echo "<input type=hidden name='component_operation' value='NOP'>";
	}
	echo "<td>";
	echo '<input name="crtbutton" type="submit" class="sub wand" value="'.__('Add').'">';
	echo "</form>";
	echo "</table>";

	echo '<table width=750 cellpadding="4" cellspacing="4" class="databox" border=0>';
	echo '<tr>';
	echo '<th>'.__('Agent');
	echo '<th>'.__('Module');
	echo "<th>".__('Type')."</th>
		<th>".__('Oper')."</th>
		<th>".__('Threshold')."</th>
		<th>".__('Min.')."</th>
		<th>".__('Max.')."</th>
		<th>".__('Time')."</th>
		<th>".__('Description')."</th>
		<th>".__('info')."</th>
		<th width='50'>".__('Action')."</th></tr>";

	$id_aam = $alerta_id_aam;
	$sql2 = "SELECT * FROM tcompound_alert, talerta_agente_modulo WHERE tcompound_alert.id = $id_aam AND talerta_agente_modulo.id_aam = tcompound_alert.id_aam";
	$result2=mysql_query($sql2);
	$string = "";
	$color = 1;

	while ($row2=mysql_fetch_array($result2)) {
		// Show data for each component of this combined alert
		if ($color == 1){
			$tdcolor="datos";
			$color =0;
		} else {
			$tdcolor="datos2";
			$color =1;
		}
		$module = get_db_row ("tagente_modulo", "id_agente_modulo", $row2["id_agente_modulo"]);
		$description = $row2["descripcion"];
		$alert_mode = $row2["operation"];
		$id_agente_name = get_db_value ("nombre", "tagente", "id_agente", $module["id_agente"]);

		echo "<tr>";
		echo "<td class='$tdcolor'>".$id_agente_name;
		echo "<td class='$tdcolor'>".$module["nombre"];
		echo show_alert_row_edit ($row2, $tdcolor, $module["id_tipo_modulo"],1);
		echo "</td><td class='$tdcolor'>";
		$id_grupo = dame_id_grupo($id_agente);
		if (give_acl ($config['id_user'], $id_grupo, "LW")) {
			echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=alert&id_agente=".$id_agente."&delete_alert_comp=".$row2["id_aam"]."'> <img src='images/cross.png' border=0 alt='".__('Delete')."'></a>  &nbsp; ";
			echo "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=alert&id_agente=".$id_agente."&update_alert=".$row2["id_aam"]."'>
			<img src='images/config.png' border=0 alt='".__('Update')."'></a>";        
		}
		echo "</td>";
	}
	echo "</table>";
}
?>
