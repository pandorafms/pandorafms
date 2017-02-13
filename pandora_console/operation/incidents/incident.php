<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;
require_once ("include/functions_incidents.php");

check_login ();

$incident_r = check_acl ($config['id_user'], 0, "IR");
$incident_w = check_acl ($config['id_user'], 0, "IW");
$incident_m = check_acl ($config['id_user'], 0, "IM");
$access = ($incident_r == true) ? 'IR' : (($incident_w == true) ? 'IW' : (($incident_m == true) ? 'IM' : 'IR'));

if (!$incident_r && !$incident_w && !$incident_m) {
	db_pandora_audit("ACL Violation","Trying to access incident viewer");
	require ("general/noaccess.php");
	exit;
}

// Header
ui_print_page_header (__('Incident management'),
	"images/book_edit.png", false, "", false, "");

// Take input parameters

// Offset adjustment
if (isset($_GET["offset"])) {
	$offset = get_parameter ("offset");
}
else {
	$offset = 0;
}

// Check action. Try to get author and group
$action = get_parameter ("action");

if ($action == "mass") {
	$id_inc = get_parameter ("id_inc", array ());
	$delete_btn = get_parameter ("delete_btn", -1);
	$own_btn = get_parameter ("own_btn", -1);
	
	foreach ($id_inc as $incident) {
		if (check_acl ($config['id_user'], incidents_get_group ($incident), "IM") || incidents_get_author ($incident) == $config["id_user"] || incidents_get_owner ($incident) == $config["id_user"]) {
			continue;
		}
		db_pandora_audit("ACL Forbidden","Mass-update or deletion of incident");
		require ("general/noaccess.php");
		exit;
	}
	
	if ($delete_btn != -1) {
		$result = incidents_delete_incident ($id_inc);
		ui_print_result_message ($result,
			__('Successfully deleted'),
			__('Could not be deleted'));
	}
	if ($own_btn != -1) {
		$result = incidents_process_chown ($id_inc, $config["id_user"]);
		ui_print_result_message ($result,
			__('Successfully reclaimed ownership'),
			__('Could not reclame ownership'));
	}

}
elseif ($action == "update") {
	$id_inc = get_parameter ("id_inc", 0);
	$author = incidents_get_author ($id_inc);
	$owner = incidents_get_owner ($id_inc);
	$grupo = incidents_get_group ($id_inc);
	
	if ($author != $config["id_user"] && $owner != $config["id_user"] && !check_acl ($config['id_user'], $grupo, "IM")) { // Only admins (manage incident) or owners/creators can modify incidents
		db_pandora_audit("ACL Forbidden", "Update incident #".$id_inc, $author);
		require ("general/noaccess.php");
		exit;
	}
	
	$titulo = get_parameter ("titulo");
	$titulo = io_safe_input(strip_tags(io_safe_output($titulo)));
	$descripcion = get_parameter ("descripcion");
	$origen = get_parameter ("origen_form");
	$prioridad = get_parameter ("prioridad_form", 0);
	$estado = get_parameter ("estado_form", 0);
	$grupo = get_parameter ("grupo_form", 1);
	$usuario = get_parameter ("usuario_form", $config["id_user"]);
	$id_agent = get_parameter ("id_agent");
	
	$sql = sprintf ("UPDATE tincidencia
		SET titulo = '%s', origen = '%s', estado = %d, id_grupo = %d, id_usuario = '%s', prioridad = %d, descripcion = '%s', id_lastupdate = '%s', id_agent = %d
		WHERE id_incidencia = %d", 
		$titulo, $origen, $estado, $grupo, $usuario, $prioridad, $descripcion, $config["id_user"], $id_agent, $id_inc);
	$result = db_process_sql ($sql);
	
	if ($result !== false) {
		db_pandora_audit("Incident updated","User ".$config['id_user']." updated incident #".$id_inc);
	}
	
	ui_print_result_message ($result,
		__('Successfully updated'),
		__('Could not be updated'));
	
}
elseif ($action == "insert") {
	//Create incident
	$grupo = get_parameter ("grupo_form", 1);
	
	if (!check_acl ($config['id_user'], $grupo, "IW")) {
		db_pandora_audit("ACL Forbidden", "User ".$config["id_user"]." tried to update incident");
		require ("general/noaccess.php");
		exit;
	}
	
	// Read input variables
	$titulo = get_parameter ("titulo");
	$titulo = io_safe_input(strip_tags(io_safe_output($titulo)));
	$descripcion = get_parameter ("descripcion");
	$origen = get_parameter ("origen_form");
	$prioridad = get_parameter ("prioridad_form");
	$id_creator = $config['id_user'];
	$estado = get_parameter ("estado_form");
	$usuario = get_parameter ("usuario_form", "");
	$id_agent = get_parameter ("id_agent");
	
	$sql = sprintf ("INSERT INTO tincidencia
			(inicio, actualizacion, titulo, descripcion, id_usuario, origen, estado, prioridad, id_grupo, id_creator, id_agent, id_agente_modulo)
		VALUES 
			(NOW(), NOW(), '%s', '%s', '%s', '%s', %d, %d, '%s', '%s', %d, 0)",
			$titulo, $descripcion, $usuario, $origen, $estado, $prioridad, $grupo, $config["id_user"], $id_agent);
	$id_inc = db_process_sql ($sql, "insert_id");
	
	if ($id_inc === false) {
		ui_print_error_message(__('Error creating incident'));
	}
	else {
		ui_print_success_message(__('Incident created'));
		db_pandora_audit("Incident created", "User " . $config["id_user"] . " created incident #" . $id_inc);
	}
}

// Search
$filter = "";

$texto = (string) get_parameter ("texto", "");
if ($texto != "") 
	$filter .= sprintf (" AND (titulo LIKE '%%%s%%' OR descripcion LIKE '%%%s%%')", $texto, $texto);

$usuario = (string) get_parameter ("usuario", "");
if ($usuario != "") 
	$filter .= sprintf (" AND id_usuario = '%s'", $usuario);

$estado = (int) get_parameter ("estado", -1);
if ($estado >= 0) //-1 = All
	$filter .= sprintf (" AND estado = %d", $estado);

$grupo = (int) get_parameter ("grupo", 0);
if ($grupo > 0) {
	$filter .= sprintf (" AND id_grupo = %d", $grupo);
	if (check_acl ($config['id_user'], $grupo, "IM") == 0) {
		db_pandora_audit("ACL Forbidden","User tried to read incidents from group without access");
		include ("general/noaccess.php");
		exit;
	}
}

$prioridad = (int) get_parameter ("prioridad", -1);
if ($prioridad != -1) //-1 = All
	$filter .= sprintf (" AND prioridad = %d", $prioridad);

$agent_search = (int) get_parameter("agent_search");
if ($agent_search != 0) {
	$filter .= sprintf(" AND id_agent = %d", $agent_search);
}

$offset = (int) get_parameter ("offset", 0);
$groups = users_get_groups ($config["id_user"], "IR");

//Select incidencts where the user has access to ($groups from
//get_user_groups), array_keys for the id, implode to pass to SQL
switch ($config["dbtype"]) {
	case "mysql":
		$sql = "SELECT * FROM tincidencia WHERE 
			id_grupo IN (".implode (",",array_keys ($groups)).")".$filter." 
			ORDER BY actualizacion DESC LIMIT ".$offset.",".$config["block_size"];
		$count_sql = "SELECT count(*) FROM tincidencia WHERE
			id_grupo IN (".implode (",",array_keys ($groups)).")".$filter."
			ORDER BY actualizacion DESC";
		$total_sql = "SELECT count(*) FROM tincidencia WHERE
			id_grupo IN (".implode (",",array_keys ($groups)).")
			ORDER BY actualizacion DESC";
		break;
	case "postgresql":
	case "oracle":
		$set = array();
		$set['limit'] = $config["block_size"];
		$set['offset'] = $offset;
		$sql = "SELECT * FROM tincidencia WHERE
			id_grupo IN (".implode (",",array_keys ($groups)).")".$filter."
			ORDER BY actualizacion DESC";
		$sql = oracle_recode_query ($sql, $set);
		$count_sql = "SELECT count(*) FROM tincidencia WHERE
			id_grupo IN (".implode (",",array_keys ($groups)).")".$filter;
		$total_sql = "SELECT count(*) FROM tincidencia WHERE
			id_grupo IN (".implode (",",array_keys ($groups)).")";
		break;
}

$result = db_get_all_rows_sql ($sql);
$count = db_get_value_sql ($count_sql);
$count_total = db_get_value_sql ($total_sql);

if (empty ($result)) {
	$result = array ();
	$count = 0;
}
if ($count_total >= 1) {
	echo '<form name="visualizacion" method="post" action="index.php?sec=workspace&amp;sec2=operation/incidents/incident">';

	echo '<table class="databox filters" cellpadding="4" cellspacing="4" width="100%"><tr>
	<td valign="middle"><h3>'.__('Filter').'</h3>';

	$fields = incidents_get_status ();
	echo "<b>" . __("Incidents:") . "</b>" . '&nbsp;&nbsp;';
	 html_print_select ($fields, "estado", $estado, 'javascript:this.form.submit();', __('All incidents'), -1, false, false, false, 'w155');

	//Legend
	echo '</td><td valign="middle"><noscript>';
	html_print_submit_button (__('Show'), 'submit-estado', false, array ("class" => "sub"));

	echo '</noscript></td><td rowspan="7" class="f9" style="padding-left: 30px; vertical-align: top;"><h3>'.__('Status').'</h3>';
	foreach (incidents_get_status () as $id => $str) {
		incidents_print_status_img ($id);
		echo ' - ' . $str . '<br />';
	}

	echo '</td><td rowspan="7" class="f9" style="padding-left: 30px; vertical-align: top;"><h3>'.__('Priority').'</h3>';
	foreach (incidents_get_priorities () as $id => $str) {
		incidents_print_priority_img ($id);
		echo ' - ' . $str . '<br />';
	}

	echo '</td></tr><tr><td>';

	$fields = incidents_get_priorities ();

	echo "<b>" . __("Priorities:") . "</b>" . '&nbsp;&nbsp;';
	html_print_select ($fields, "prioridad", $prioridad, 'javascript:this.form.submit();', __('All priorities'), -1,false,false,false,'w155');

	echo '</td></tr><tr><td>';

	echo "<b>" . __("Users:") . "</b>" . '&nbsp;&nbsp;';
	html_print_select (users_get_info (), "usuario", $usuario, 'javascript:this.form.submit();', __('All users'), "", false, false, false, "w155");

	echo '</td></tr><tr><td>';

	$agents_incidents = agents_get_agents(false, array('id_agente', 'nombre'));

	if ($agents_incidents === false) {
		$agents_incidents = array();
	}

	$result_agent_incidents = array();
	foreach ($agents_incidents as $agent_incident) {
		$result_agent_incidents[$agent_incident['id_agente']] = $agent_incident['nombre'];
	}

	echo "<b>" . __("Agents:") .  "</b>" . '&nbsp;&nbsp;';
	html_print_select ($result_agent_incidents, "agent_search",
		$agent_search, 'javascript:this.form.submit();', __('All agents'),
		"", false, false, false, "w155");

	echo '</td></tr><tr><td colspan=3>';

	echo "<b>" . __("Groups:") . "</b>" . '&nbsp;&nbsp;';
	html_print_select_groups($config["id_user"], "IR", true, "grupo", $grupo, 'javascript:this.form.submit();', '', '',false,false,false,'w155');

	//echo "&nbsp;&nbsp;&nbsp;&nbsp;";
	echo '</td></tr><tr><td colspan=3>';

	echo "<b>" . __("Free text:") . "</b>". ui_print_help_tip(
	__('Search by incident name or description, list matches.'),true) . '&nbsp;&nbsp;';
	html_print_input_text ('texto', $texto, '', 45);
	echo '&nbsp;';
	html_print_input_image ("submit", "images/zoom.png", __('Search'), 'padding:0;', false, array ("alt" => __('Search'))); 

	echo "</td></tr></table>";
	echo '</form>';
}

if ($count_total < 1) {
	require_once ($config['homedir'] . "/general/firts_task/incidents.php");
}
else {
	// TOTAL incidents
	$url = "index.php?sec=workspace&amp;sec2=operation/incidents/incident";
	
	$estado = -1;
	
	// add form filter values for group, priority, state, and search fields: user and text
	if ($grupo != -1)
		$url .= "&amp;grupo=".$grupo;
	if ($prioridad != -1)
		$url .= "&amp;prioridad=".$prioridad;
	if ($estado != -1)
		$url .= "&amp;estado=".$estado;
	if ($usuario != '')
		$url .= "&amp;usuario=".$usuario;
	if ($texto != '')
		$url .= "&amp;texto=".$texto;
	
	// Show pagination
	ui_pagination ($count, $url, $offset, 0, false); //($count + $offset) it's real count of incidents because it's use LIMIT $offset in query.
	echo '<br />';
	
	// Show headers
	$table->width = "100%";
	$table->class = "databox data";
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
	$table->head[8] = __('Action');
	
	$table->size[0] = 43;
	$table->size[7] = 50;
	
	$table->align[1] = "left";
	$table->align[3] = "left";
	$table->align[4] = "left";
	$table->align[8] = "left";
	
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
		
		$data[0] = '<a href="index.php?sec=workspace&amp;sec2=operation/incidents/incident_detail&amp;id='.$row["id_incidencia"].'">'.$row["id_incidencia"].'</a>';
		$attach = incidents_get_attach ($row["id_incidencia"]);
		
		if (!empty ($attach))
			$data[0] .= '&nbsp;&nbsp;'.html_print_image ("images/attachment.png", true, array ("style" => "align:middle;"));
		
		$data[1] = incidents_print_status_img ($row["estado"], true);
		$data[2] = '<a href="index.php?sec=workspace&amp;sec2=operation/incidents/incident_detail&amp;id='.$row["id_incidencia"].'">' .
			ui_print_truncate_text(io_safe_output($row["titulo"]), 'item_title').'</a>';
		$data[3] = incidents_print_priority_img ($row["prioridad"], true);
		$data[4] = ui_print_group_icon ($row["id_grupo"], true);
		$data[5] = ui_print_timestamp ($row["actualizacion"], true);
		$data[6] = $row["origen"];
		if (empty($row["id_usuario"])) {
			$data[7] = 'SYSTEM';
		}
		else {
			$data[7] = ui_print_username ($row["id_usuario"], true);
		}
		
		if (check_acl ($config["id_user"], $row["id_grupo"], "IM") || $config["id_user"] == $row["id_usuario"] || $config["id_user"] == $row["id_creator"]) {
			$data[8] = html_print_checkbox ("id_inc[]", $row["id_incidencia"], false, true);
		}
		else {
			$data[8] = '';
		}
		
		array_push ($table->data, $data);
	}
	
	echo '<form method="post" action="'.$url.'&amp;action=mass" style="margin-bottom: 0px;">';
	html_print_table ($table);
	echo '<div style="text-align:right; float:right;">';
	if (check_acl ($config["id_user"], 0, "IW")) {
		html_print_submit_button (__('Delete incidents'), 'delete_btn', false, 'class="sub delete" style="margin-right: 5px;"');
	}
	
	if (check_acl ($config["id_user"], 0, "IM")) {
		html_print_submit_button (__('Become owner'), 'own_btn', false, 'class="sub upd"');
	}
	echo '</div>';
	echo '</form>';
	unset ($table);
	if (check_acl ($config["id_user"], 0, "IW")) {
		echo '<div style="text-align:right; float:right; padding-right: 5px;">';
		echo '<form method="post" action="index.php?sec=workspace&amp;sec2=operation/incidents/incident_detail&amp;insert_form=1">';
		html_print_submit_button (__('Create incident'), 'crt', false, 'class="sub next"');
		echo '</form>';
		echo '</div>';
	}
}


echo '<div style="clear:both">&nbsp;</div>';
?>
