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



require ("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "IR")) {
	audit_db($config['id_user'],$REMOTE_ADDR, "ACL Violation","Trying to access incident viewer");
	require ("general/noaccess.php");
	exit;
}

// Take input parameters

// Offset adjustment
if (isset($_GET["offset"])) {
	$offset = get_parameter_get ("offset");
} else {
	$offset = 0;
}

// Delete incident
if (isset($_GET["quick_delete"])){
	$id_inc = get_parameter_get ("quick_delete");
	$sql = "SELECT id_usuario, id_grupo FROM tincidencia WHERE id_incidencia=".$id_inc;
	$result = get_db_row_sql ($sql);
	$usuario = give_incident_author ($id_inc);
	
	if ($result !== false) {
		if (give_acl ($config['id_user'], $result["id_grupo"], "IM") || $config["id_user"] == $result["id_usuario"]) {
			borrar_incidencia ($id_inc);
			echo '<h3 class="suc">'.__('Incident successfully deleted').'</h3>';
			audit_db ($usuario,$REMOTE_ADDR,"Incident deleted","User ".$config['id_user']." deleted incident #".$id_inc);
		} else {
			audit_db ($usuario,$REMOTE_ADDR,"ACL Forbidden","User ".$_SESSION["id_usuario"]." tried to delete incident");
			echo '<h3 class="error">'.__('There was a problem deleting incident').'</h3>';
			no_permission ();
		}
	}
}

// UPDATE incident
if ((isset ($_GET["action"])) AND ($_GET["action"] == "update")) {
	$id_inc = get_parameter_post ("id_inc");
	$usuario = give_incident_author ($id_inc);
	$grupo = get_parameter_post ("grupo_form");
	
	if (give_acl ($config['id_user'], $grupo, "IM") || $usuario == $config['id_user']) { // Only admins (manage incident) or owners can modify incidents
		$titulo = get_parameter_post ("titulo");
		$descripcion = get_parameter_post ("descripcion");
		$origen = get_parameter_post ("origen_form");
		$prioridad = get_parameter_post ("prioridad_form");
		$estado = get_parameter_post ("estado_form");
		$ahora = date ("Y/m/d H:i:s");
		
		$sql = sprintf ("UPDATE tincidencia SET actualizacion = '%s', titulo = '%s', origen = '%s', estado = %d, id_grupo = %d, id_usuario = '%s', prioridad = %d, descripcion = '%s' WHERE id_incidencia = %d",
			$ahora, $titulo, $origen, $estado, $grupo, $usuario, $prioridad, $descripcion, $id_inc);
		$result = process_sql ($sql);
	
		if ($result !== false) {
			audit_db($usuario,$REMOTE_ADDR,"Incident updated","User ".$config['id_user']." updated incident #".$id_inc);
			echo '<h3 class="suc">'.__('Incident successfully updated').'</h3>';
		} else {
			echo '<h3 class="error">'.__('There was a problem updating the incident').'</h3>';
		}
	} else {
		audit_db ($usuario,$REMOTE_ADDR,"ACL Forbidden","User ".$config['id_user']." try to update incident");
		no_permission();
	}
}

// INSERT incident
if ((isset ($_GET["action"])) AND ($_GET["action"] == "insert")) {
	$grupo = get_parameter_post ("grupo_form", 1);
	if (give_acl ($config['id_user'], $grupo, "IM")) {
		// Read input variables
		$titulo = get_parameter_post ("titulo"); 
		$descripcion = get_parameter_post ("descripcion");
		$origen = get_parameter_post ("origen_form");
		$prioridad = get_parameter_post ("prioridad_form");
		$id_creator = $config['id_user'];
		$estado = get_parameter_post ("estado_form");
		$sql = sprintf ("INSERT INTO tincidencia (inicio, actualizacion, titulo, descripcion, id_usuario, origen, estado, prioridad, id_grupo, id_creator) VALUES (NOW(), NOW(), '%s', '%s', '%s', '%s', %d, %d, '%s', '%s')", $titulo, $descripcion, $config["id_user"], $origen, $estado, $prioridad, $grupo, $config["id_user"]);
		$id_inc = process_sql ($sql, "insert_id");

		if ($id_inc === false) {
			echo '<h3 class="error">'.__('Error creating incident').'</h3>';		
		} else {
			audit_db ($config["id_user"], $REMOTE_ADDR, "Incident created", "User ".$config["id_user"]." created incident #".$id_inc);
		}
	} else {
		audit_db ($config["id_user"],$REMOTE_ADDR,"ACL Forbidden","User tried to create incident");
		no_permission ();
	}
}

// Search
$filter = "";

$texto = (string) get_parameter ("texto", "");
if ($texto != "") 
	$filter .= sprintf (" AND (titulo LIKE '%%%s%%' OR descripcion LIKE '%%%s%%')", $texto, $texto);

$usuario = (string) get_parameter ("usuario", "All");
if ($usuario != "All") 
	$filter .= sprintf (" AND id_usuario = '%s'", $usuario);

$estado = (int) get_parameter ("estado", -1);
if ($estado != -1) //-1 = All
	$filter .= sprintf (" AND estado = %d", $estado);

$grupo = (int) get_parameter ("grupo", 1);
if ($grupo != 1) {
	$filter .= sprintf (" AND id_grupo = %d", $grupo);
	if (give_acl ($config['id_user'], $grupo, "IM") == 0) {
		audit_db ($config["id_user"],$REMOTE_ADDR,"ACL Forbidden","User tried to read incidents from group without access");
		no_permission ();
	}
}

$prioridad = (int) get_parameter ("prioridad", -1);
if ($prioridad != -1) //-1 = All
	$filter .= sprintf (" AND prioridad = %d", $prioridad);

$offset = (int) get_parameter ("offset", 0);
$groups = get_user_groups ($config["id_user"], "IR");


//Select incidencts where the user has access to ($groups from
//get_user_groups), array_keys for the id, implode to pass to SQL
$sql = "SELECT * FROM tincidencia WHERE 
	id_grupo IN (".implode (",",array_keys ($groups)).")".$filter." 
	ORDER BY actualizacion DESC LIMIT ".$offset.",".$config["block_size"];

$result = get_db_all_rows_sql ($sql);
if (empty ($result)) {
	$result = array ();
	$count = 0;
} else {
	$count = count ($result);
}

echo '<h2>'.__('Incident management').' &gt; '.__('Manage incidents').'</h2>
<form name="visualizacion" method="POST" action="index.php?sec=incidencias&sec2=operation/incidents/incident">
<table class="databox" cellpadding="4" cellspacing="4" width="700px"><tr>
<td valign="middle">
<h3>'.__('Filter').'</h3>';

$fields = array(); //Reset empty array
$fields[-1] = __('All incidents');
$fields[0] = __('Active incidents');
$fields[2] = __('Rejected incidents');
$fields[3] = __('Expired incidents');
$fields[13] = __('Closed incidents');

print_select ($fields, "estado", $estado, 'javascript:this.form.submit();', '', '', false, false, false, 'w155');

//Legend
echo '</td><td valign="middle"><noscript>';
print_submit_button (__('Show'), 'submit-estado', false, 'class="sub" border="0"');
echo '</noscript></td>
	<td rowspan="5" class="f9" style="padding-left: 30px; vertical-align: top;"><h3>'.__('Status').'</h3>
	<img src="images/dot_red.png" /> - '.__('Active incidents').'<br />
	<img src="images/dot_yellow.png" /> - '.__('Active incidents, with comments').'<br />
	<img src="images/dot_blue.png" /> - '.__('Rejected incidents').'<br />
	<img src="images/dot_green.png" /> - '.__('Closed incidents').'<br />
	<img src="images/dot_white.png" /> - '.__('Expired incidents').'</td>
	<td rowspan="5" class="f9" style="padding-left: 30px; vertical-align: top;"><h3>'.__('Priority').'</h3>
	<img src="images/dot_red.png" /><img src="images/dot_red.png" /><img src="images/dot_red.png" /> - '.__('Very Serious').'<br />
	<img src="images/dot_yellow.png" /><img src="images/dot_red.png" /><img src="images/dot_red.png" /> - '.__('Serious').'<br />
	<img src="images/dot_yellow.png" /><img src="images/dot_yellow.png" /><img src="images/dot_red.png" /> - '.__('Medium').'<br />
	<img src="images/dot_green.png" /><img src="images/dot_yellow.png" /><img src="images/dot_yellow.png" /> - '.__('Low').'<br />
	<img src="images/dot_green.png" /><img src="images/dot_green.png" /><img src="images/dot_yellow.png" /> - '.__('Informative').'<br />
	<img src="images/dot_green.png" /><img src="images/dot_green.png" /><img src="images/dot_green.png" /> - '.__('Maintenance').'<br />
	</td></tr>
	<tr><td>';

$fields = array(); //Reset empty array
$fields[-1] = __('All priorities');
$fields[0] = __('Informative');
$fields[1] = __('Low');
$fields[2] = __('Medium');
$fields[3] = __('Serious');
$fields[4] = __('Very Serious');
$fields[10] = __('Maintenance');

print_select ($fields, "prioridad", $prioridad, 'javascript:this.form.submit();', '','',false,false,false,'w155');

echo '</td><td valign="middle"><noscript>';
print_submit_button (__('Show'), 'submit-prioridad', false, 'class="sub" border="0"');
echo '</noscript></td></tr><tr><td>';

print_select ($groups, "grupo", $grupo, 'javascript:this.form.submit();','','',false,false,false,'w155');

echo '</td><td valign="middle"><noscript>';
print_submit_button (__('Show'), 'submit-grupo', false, 'class="sub" border="0"');
echo '</noscript>';

// Pass search parameters for possible future filter searching by user
print_input_hidden ("usuario", $usuario);
print_input_hidden ("texto", $texto);

echo "</td></tr></table></form>";

if ($count < 1) {
	echo '<div class="nf">'.__('No incidents match your search filter').'</div><br />';
} else {
	// TOTAL incidents
	$url = "index.php?sec=incidencias&sec2=operation/incidents/incident";

	$estado = -1;

	// add form filter values for group, priority, state, and search fields: user and text
	if ($grupo != -1)
		$url .= "&grupo=".$grupo;
	if ($prioridad != -1)
		$url .= "&prioridad=".$prioridad;
	if ($estado != -1)
		$url .= "&estado=".$estado;
	if ($usuario != '')
		$url .= "&usuario=".$usuario;
	if ($texto != '')
		$url .= "&texto=".$texto;

	// Show pagination
	pagination ($count, $url, $offset);
	echo '<br />';
	
	// Show headers
	$table->width = 750;
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
	$table->head[8] = __('Delete');
	
	$table->size[0] = 43;
	$table->size[7] = 50;
	
	$table->align[1] = "center";
	$table->align[3] = "center";
	$table->align[4] = "center";
	$table->align[8] = "center";
	
	foreach ($result as $row) {
		$data = array();

		$data[0] = '<a href="index.php?sec=incidencias&sec2=operation/incidents/incident_detail&id='.$row["id_incidencia"].'">'.$row["id_incidencia"].'</a>';
		$attnum = get_db_value ('COUNT(*)', 'tattachment', 'id_incidencia', $row["id_incidencia"]);
		$notenum = dame_numero_notas ($row["id_incidencia"]);
		
		if ($attnum > 0)
			$data[0] .= '&nbsp;&nbsp;<img src="images/file.png" align="middle" />';
		
		if ($notenum > 0 && $row["estado"] == 0)
			$row["estado"] = 1;
		
		switch ($row["estado"]) {
			case 0: 
				$data[1] = '<img src="images/dot_red.png" />';
				break;
			case 1: 
				$data[1] = '<img src="images/dot_yellow.png" />';
				break;
		        case 2: 
				$data[1] = '<img src="images/dot_blue.png" />';
				break;
			case 3: 
				$data[1] = '<img src="images/dot_white.png">';
				break;
			case 13: 
				$data[1] = '<img src="images/dot_green.png">';
				break;
		}
		
		$data[2] = '<a href="index.php?sec=incidencias&sec2=operation/incidents/incident_detail&id='.$row["id_incidencia"].'">'.safe_input (substr ($row["titulo"],0,45)).'</a>';
																																																															
		switch ($row["prioridad"]) {
		        case 4: 
				$data[3] = '<img src="images/dot_red.png" /><img src="images/dot_red.png" /><img src="images/dot_red.png" />';
				break;
			case 3:
				$data[3] = '<img src="images/dot_yellow.png" /><img src="images/dot_red.png" /><img src="images/dot_red.png" />';
				break;
			case 2: 
				$data[3] = '<img src="images/dot_yellow.png" /><img src="images/dot_yellow.png" /><img src="images/dot_red.png" />';
				break;
			case 1: 
				$data[3] = '<img src="images/dot_green.png" /><img src="images/dot_yellow.png" /><img src="images/dot_yellow.png" />';
				break;
			case 0: 
				$data[3] = '<img src="images/dot_green.png" /><img src="images/dot_green.png" /><img src="images/dot_yellow.png" />';
				break;
			case 10:
				$data[3] = '<img src="images/dot_green.png" /><img src="images/dot_green.png" /><img src="images/dot_green.png" />';
				break;	
		}
		
		$data[4] = '<img src="images/groups_small/'.show_icon_group ($row["id_grupo"]).'.png" title="'.dame_grupo ($row["id_grupo"]).'" />';
	
		$data[5] = human_time_comparation ($row["actualizacion"]);
		
		$data[6] = $row["origen"];	
		
		$data[7] = '<a href="index.php?sec=usuario&sec2=operation/users/user_edit&ver='.$row["id_usuario"].'">'.$row["id_usuario"].'</a>'; 
		
		if (give_acl ($config["id_user"], $row["id_grupo"], "IM") || $config["id_user"] == $row["id_usuario"]) {
			$data[8] = '<a href="index.php?sec=incidencias&sec2=operation/incidents/incident&quick_delete='.$row["id_incidencia"].'" onClick="if (!confirm(\''.__('Are you sure?').'\')) return false;"><img src="images/cross.png" border="0"></a>';
		} else {
			$data[8] = '';
		}
		
		array_push ($table->data, $data);
	}
	
	print_table ($table);
	unset ($table);
}

if (give_acl ($config["id_user"], 0, "IW")) {
	echo '<div style="text-align:right; width:750px"><form method="post" action="index.php?sec=incidencias&sec2=operation/incidents/incident_detail&insert_form">';
	print_submit_button (__('Create incident'), 'crt', false, 'class="sub next"');
	echo '</form></div>';
}
?>
