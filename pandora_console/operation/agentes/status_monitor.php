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



// Load global vars
require_once ("include/config.php");

check_login();

if (! give_acl ($config['id_user'], 0, "AR") && ! give_acl ($config['id_user'], 0, "AW")) {
	audit_db ($config['id_user'],$REMOTE_ADDR, "ACL Violation",
		"Trying to access Agent Management");
	require ("general/noaccess.php");
	exit;
}

echo "<h2>".__('Pandora Agents')." &gt; ".__('Full list of Monitors')."</h2>";

$ag_freestring = get_parameter ("ag_freestring", "");
$ag_modulename = get_parameter ("ag_modulename", "");
$ag_group = get_parameter ("ag_group", -1);
$offset = get_parameter ("offset", 0);
$status = get_parameter ("status", 0);

$url = '';
if ($ag_group > 0) {
	$url .= "&ag_group=".$ag_group;
}
if ($ag_modulename != "") {
	$url .= "&ag_modulename=".$ag_modulename;
}
if ($ag_freestring != "") {
	$url .= "&ag_freestring=".$ag_freestring;
}
if ($status != 0) {
	$url .= "&status=".$status;
}

echo '<form method="post" action="index.php?sec=estado&sec2=operation/agentes/status_monitor&refr=60'.$url.'">';

echo '<table cellspacing="4" cellpadding="4" width="600" class="databox">';
echo '<tr><td valign="middle">'.__('Group').'</td>';
echo '<td valign="middle">';

print_select (get_user_groups (), "ag_group", $ag_group, 'this.form.submit();', '', '0', false, false, false, 'w130');

echo "</td>";
echo "<td>".__('Monitor status')."</td><td>";

$fields = array ();
$fields[0] = __('Monitors down'); //default
$fields[1] = __('Monitors up');
$fields[2] = __('Monitors unknown');

print_select ($fields, "status", $status, 'this.form.submit();', __('All'), -1);

echo '</td></tr><tr><td valign="middle">'.__('Module name').'</td>';
echo '<td valign="middle">';

$result = get_db_all_rows_sql ("SELECT DISTINCT(nombre) FROM tagente_modulo WHERE id_tipo_modulo IN (2, 6, 9, 18, 21, 100) ORDER BY nombre");
if ($result === false) {
	$result = array ();
}

$fields = array ();
foreach ($result as $row) {
	$fields[$row["nombre"]] = $row["nombre"];
}

print_select ($fields, "ag_modulename", $ag_modulename, 'this.form.submit();', __('All'), "");

echo '</td><td valign="middle">'.__('Free text').'</td>';

echo '<td valign="middle">';
print_input_text ("ag_freestring", $ag_freestring, '', 15);
echo '</td><td valign="middle">';
print_submit_button (__('Show'), "uptbutton", false, 'class="sub"');

echo "</form>";
echo "</table>";

// Begin Build SQL sentences
$sql = " FROM tagente, tagente_modulo, tagente_estado 
	WHERE tagente.id_agente = tagente_modulo.id_agente 
	AND tagente_modulo.disabled = 0 
	AND tagente.disabled = 0 
	AND tagente_modulo.id_tipo_modulo IN (2, 9, 12, 18, 6, 100) 
	AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo";

// Agent group selector
if ($ag_group > 1 && give_acl ($config["id_user"], $ag_group, "AR")) {
    $sql .= sprintf (" AND tagente.id_grupo = %d", $ag_group);
} else {
	// User has explicit permission on group 1 ?
	$sql .= " AND tagente.id_grupo IN (".implode (",", array_keys (get_user_groups ())).")";
}

// Module name selector
if ($ag_modulename != "") {
	$sql .= sprintf (" AND tagente_modulo.nombre = '%s'", $ag_modulename);
}

// Freestring selector
if ($ag_freestring != "") {
	$sql .= sprintf (" AND (tagente.nombre LIKE '%%%s%%' OR tagente_modulo.nombre LIKE '%%%s%%' OR tagente_modulo.descripcion LIKE '%%%s%%')", $ag_freestring, $ag_freestring, $ag_freestring);
}

// Status selector
if ($status == 1) { //Up
	$sql .= " AND tagente_estado.estado = 0 AND (UNIX_TIMESTAMP(NOW()) - tagente_estado.utimestamp) < (tagente_estado.current_interval * 2)";
} elseif ($status == 0) { //Down
	$sql .= " AND tagente_estado.estado = 1";
} elseif ($status == 2) { //Unknown
	$sql .= " AND (UNIX_TIMESTAMP(NOW()) - tagente_estado.utimestamp) >= (tagente_estado.current_interval * 2)";
}

$sql .= " ORDER BY tagente.id_grupo, tagente.nombre";

// Build final SQL sentences
$count = get_db_sql ("SELECT COUNT(tagente_modulo.id_agente_modulo)".$sql);
$sql = "SELECT tagente_modulo.id_agente_modulo,
	tagente.intervalo AS agent_interval,
	tagente.nombre AS agent_name, 
	tagente_modulo.nombre AS module_name,
	tagente_modulo.flag AS flag,
	tagente.id_grupo AS id_group, 
	tagente.id_agente AS id_agent, 
	tagente_modulo.id_tipo_modulo AS module_type,
	tagente_modulo.module_interval, 
	tagente_estado.datos, 
	tagente_estado.utimestamp AS utimestamp".$sql." LIMIT ".$offset.",".$config["block_size"];
$result = get_db_all_rows_sql ($sql);

if ($count > $config["block_size"]) {
	pagination ($count, "index.php?sec=estado&sec2=operation/agentes/status_monitor&refr=60".$url, $offset);
}

if ($result === false) {
	$result = array ();
}

$table->cellpadding = 4;
$table->cellspacing = 4;
$table->width = 750;
$table->class = "databox";

$table->head = array ();
$table->data = array ();
$table->size = array ();
$table->align = array ();

$table->head[0] = "";
$table->align[0] = "center";

$table->head[1] = __('Agent');

$table->head[2] = __('Type');
$table->align[2] = "center";

$table->head[3] = __('Module Name');

$table->head[4] = __('Interval');
$table->align[4] = "center";

$table->head[5] = __('Status');
$table->align[5] = "center";

$table->head[6] = __('Timestamp');
$table->algin[6] = "center";

foreach ($result as $row) {
	$data = array ();
	//This should be processed locally. Don't rely on other URL's to do our dirty work. Maybe a process_agentmodule_flag function
	$data[0] = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$row["id_agent"].'&id_agente_modulo='.$row["id_agente_modulo"].'&flag=1&refr=60">';
	if ($row["flag"] == 0) {
		$data[0] .= '<img src="images/target.png" />';
	} else {
		$data[0] .= '<img src="images/refresh.png" />';
	}
	$data[0] .= '</a>';
	
	$data[1] = '<strong><a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$row["id_agent"].'">';
	$data[1] .= strtoupper (substr ($row["agent_name"], 0, 25));
	$data[1] .= '</a></strong>';
	
	$data[2] = '<img src="images/'.show_icon_type ($row["module_type"]).'" border="0" />';
	
	$data[3] = substr ($row["module_name"], 0, 30);
	
	$data[4] = $row["agent_interval"];
	
	if ($row["datos"] > 0) {
		$data[5] = '<img src="images/pixel_green.png" width="40" height="18" title="'.__('Monitor up').'">';
	} else {
		$data[5] = '<img src="images/pixel_red.png" width="40" height="18" title="'.__('Monitor down').'">';
	}

	$seconds = time () - $row["utimestamp"];
	
	if ($seconds >= ($row["agent_interval"] * 2)) {
		$data[6] = '<span class="redb">';
	} else {
		$data[6] = '<span>';
	}
	
	$data[6] .= human_time_comparation ($row["utimestamp"]);
	$data[6] .= "</span>";
	
	array_push ($table->data, $data);
}
if (!empty ($table->data)) {
	print_table ($table);
	echo '<div style="width:700px;"><img src="images/pixel_green.png" width="40" height="18">&nbsp;&nbsp;'.__('Monitor up').'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src="images/pixel_red.png" width="40" height="18">&nbsp;&nbsp;'.__('Monitor down').'</div>';
} else {
	echo '<div class="nf">'.__('This group doesn\'t have any monitor').'</div>';
}
?>
