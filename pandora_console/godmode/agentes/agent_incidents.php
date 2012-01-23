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

// Load global vars
global $config;

require_once ('include/functions_incidents.php');

check_login ();

$group = $id_grupo;

if (! check_acl ($config["id_user"], $group, "AW", $id_agente)) {
	db_pandora_audit("ACL Violation",
		"Trying to access agent manager");
	require ("general/noaccess.php");
	return;
}

$offset = (int) get_parameter("offset", 0);


//See if id_agente is set (either POST or GET, otherwise -1
$id_agent = (int) get_parameter ("id_agente");
$groups = users_get_groups ($config["id_user"], "IR");
$filter = ' AND id_agent = ' . $id_agent;
$url = "index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=incident&id_agente=" . $id_agent;

//Select incidencts where the user has access to ($groups from
//get_user_groups), array_keys for the id, implode to pass to SQL
$sql = "SELECT * FROM tincidencia WHERE 
	id_grupo IN (".implode (",",array_keys ($groups)).")".$filter." 
	ORDER BY actualizacion DESC LIMIT ".$offset.",".$config["block_size"];

$result = db_get_all_rows_sql ($sql);

$count_sql = "SELECT count(*) FROM tincidencia WHERE 
	id_grupo IN (".implode (",",array_keys ($groups)).")".$filter." 
	ORDER BY actualizacion DESC";

$count = db_get_value_sql ($count_sql);

if (empty ($result)) {
	$result = array ();
	$count = 0;
	echo '<div class="nf">'.__('No incidents associated to this agent').'</div><br />';
	return;
}

// Show pagination
ui_pagination ($count, $url, $offset, 0, false, 'offset');	//($count + $offset) it's real count of incidents because it's use LIMIT $offset in query.
echo '<br />';

// Show headers
$table->width = "100%";
$table->class = "databox";
$table->cellpadding = 4;
$table->cellspacing = 4;
$table->head = array ();
$table->data = array ();
$table->size = array ();
$table->align = array ();

$table->head[0] = __('ID');
$table->head[1] = __('Status');
$table->head[2] = __('Incident');
$table->head[3] = __('Priority');
$table->head[4] = __('Group');
$table->head[5] = __('Updated');
$table->head[6] = __('Source');
$table->head[7] = __('Owner');

$table->size[0] = 43;
$table->size[7] = 50;

$table->align[1] = "center";
$table->align[3] = "center";
$table->align[4] = "center";
	
$rowPair = true;
$iterator = 0;
foreach ($result as $row) {
	if ($rowPair)
		$table->rowclass[$iterator] = 'rowPair';
	else
		$table->rowclass[$iterator] = 'rowOdd';
	$rowPair = !$rowPair;
	$iterator++;
	
	$data = array();

	$data[0] = '<a href="index.php?sec=incidencias&amp;sec2=operation/incidents/incident_detail&amp;id='.$row["id_incidencia"].'">'.$row["id_incidencia"].'</a>';
	$attach = incidents_get_attach ($row["id_incidencia"]);
	
	if (!empty ($attach))
		$data[0] .= '&nbsp;&nbsp;'.html_print_image ("images/attachment.png", true, array ("style" => "align:middle;"));
	
	$data[1] = incidents_print_status_img ($row["estado"], true);
	$data[2] = '<a href="index.php?sec=incidencias&amp;sec2=operation/incidents/incident_detail&amp;id='.$row["id_incidencia"].'">'.substr(io_safe_output($row["titulo"]),0,45).'</a>';
	$data[3] = incidents_print_priority_img ($row["prioridad"], true);
	$data[4] = ui_print_group_icon ($row["id_grupo"], true);
	$data[5] = ui_print_timestamp ($row["actualizacion"], true);
	$data[6] = $row["origen"];
	$data[7] = ui_print_username ($row["id_usuario"], true);

	array_push ($table->data, $data);
}
html_print_table ($table);

echo '</div>';
unset ($table);
echo '<br><br>';

echo '<div style="clear:both">&nbsp;</div>';
?>
