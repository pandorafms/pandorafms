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
require_once ("include/functions_incidents.php");
require_once ("include/functions_events.php"); //To get events group information
require_once ($config['homedir'] . "/include/functions_users.php");

check_login ();

if (! check_acl ($config["id_user"], 0, "IR")) {
 	// Doesn't have access to this page
	db_pandora_audit("ACL Violation", "Trying to access incident details");
	require ("general/noaccess.php");
	exit;
}

$inicio = get_system_time (); //Just inits the variable
$actualizacion = get_system_time ();

// EDITION MODE
if (isset ($_GET["id"])) {
	$id_inc = (int) get_parameter_get ("id", 0);
	
	// Obtain group of this incident
	$row = db_get_row ("tincidencia","id_incidencia",$id_inc);
	
	// Get values
	$titulo = $row["titulo"];
	$texto = $row["descripcion"];
	$inicio = strtotime ($row["inicio"]); 
	$actualizacion = strtotime ($row["actualizacion"]);
	$estado = $row["estado"];
	$prioridad = $row["prioridad"];
	$origen = $row["origen"];
	$usuario = $row["id_usuario"]; //owner
	$id_grupo = $row["id_grupo"];
	$id_creator = $row["id_creator"]; //creator
	$id_lastupdate = $row["id_lastupdate"]; //last updater
	
	// Note add - everybody that can read incidents, can add notes
	if (isset ($_GET["insertar_nota"])) {
		$nota = get_parameter_post ("nota");

		$values = array(
			'id_usuario' => $config["id_user"],
			'id_incident' => $id_inc,
			'nota' => $nota);
		$id_nota = db_process_sql_insert('tnota', $values);

		if ($id_nota !== false) {
			incidents_process_touch ($id_inc);
		}
		ui_print_result_message ($id_nota,
			__('Successfully added'),
			__('Could not be added'));
	}

	// Delete note
	if (isset ($_POST["delete_nota"])) {
		$id_nota = get_parameter_post ("delete_nota", 0);
		$note_user = incidents_get_notes_author ($id_nota);
		if (((check_acl ($config["id_user"], $id_grupo, "IM") == 1) OR ($note_user == $config["id_user"])) OR ($id_owner == $config["id_user"])) { 
		// Only admins (manage incident) or owners can modify
		// incidents notes. note authors are 
		// able to delete their own notes
			$result = incidents_delete_note ($id_nota);

			if (!empty ($result)) {
				incidents_process_touch ($id_inc);
			}
			ui_print_result_message ($id_nota,
				__('Successfully deleted'),
				__('Could not be deleted'));
		}
	}

	// Delete file
	if (((check_acl ($config["id_user"], $id_grupo, "IM")==1) OR ($id_owner == $config["id_user"])) AND isset ($_POST["delete_file"])) {
		$file_id = (int) get_parameter_post ("delete_file", 0);
		$filename = db_get_value ("filename", "tattachment", "id_attachment", $file_id);
		
		$result = db_process_sql_delete('tattachment', array('id_attachment' => $file_id));
		
		if (!empty ($result)) {
			unlink ($config["attachment_store"]."/pand".$file_id."_".$filename);
			incidents_process_touch ($id_inc);
		}
		
		ui_print_result_message ($result,
			__('Successfully deleted'),
			__('Could not be deleted'));
	}

	// Upload file
	if ((check_acl ($config["id_user"], $id_grupo, "IW") == 1) AND isset ($_GET["upload_file"]) AND ($_FILES['userfile']['name'] != "")) {
		$description = get_parameter_post ("file_description", __('No description available'));
		
		// Insert into database
		$filename = io_safe_input ($_FILES['userfile']['name']);
		$filesize = io_safe_input ($_FILES['userfile']['size']);

		//The following is if you have clamavlib installed
		//(php5-clamavlib) and enabled in php.ini
		//http://www.howtoforge.com/scan_viruses_with_php_clamavlib
		if (extension_loaded ('clamav')) {
			cl_setlimits (5, 1000, 200, 0, 10485760);
			$malware = cl_scanfile ($_FILES['file']['tmp_name']); 
			if ($malware) {
				$error = 'Malware detected: '.$malware.'<br>ClamAV version: '.clam_get_version();
				die ($error); //On malware, we die because it's not good to handle it
			}
		}
		
		$values = array(
			'id_incidencia' => $id_inc,
			'id_usuario' => $config["id_user"],
			'filename' => $filename,
			'description' => $description,
			'size' => $filesize);
		$id_attachment = db_process_sql_insert('tattachment', $values);

		// Copy file to directory and change name
		if ($id_attachment !== false) {
			$nombre_archivo = $config["attachment_store"]."/pand".$id_attachment."_".$filename;
			$result = copy ($_FILES['userfile']['tmp_name'], $nombre_archivo);
		}
		else {
			echo '<h3 class="error">'.__('File could not be saved due to database error').'</h3>';
			$result = false;
		}

		if ($result !== false) {
			unlink ($_FILES['userfile']['tmp_name']);
			incidents_process_touch ($id_inc);
		}
		else {
			db_process_sql_delete('tattachment', array('id_attachment' => $id_attachment));
		}
		
		ui_print_result_message ($result,
			__('File uploaded'),
			__('File could not be uploaded'));
	}
} // else Not given id
// Create incident from event... read event data
elseif (isset ($_GET["insert_form"])) {
	$titulo = "";
	$descripcion = "";
	$origen = "";
	$prioridad = 0;
	$id_grupo = 0;
	$estado = 0;
	$texto = "";
	$usuario = $config["id_user"];
	$id_creator = $config["id_user"];
	
	if (isset ($_GET["from_event"])) {
		$event = get_parameter_get ("from_event");
		$titulo = events_get_description ($event);
		$id_grupo = events_get_group ($event);
		$origen = "Pandora FMS event";
		unset ($event);
	}
	$prioridad = 0;
	$id_grupo = 0;
}
else {
	db_pandora_audit("HACK","Trying to get to incident details in an unusual way");
	require ("general/noaccess.php");
	exit;
}



// ********************************************************************************************************
// ********************************************************************************************************
// Show the form
// ********************************************************************************************************

//This is for the pretty slide down attachment form
echo "<script type=\"text/javascript\">
	$(document).ready(function() {
		$('#file_control').hide();
		$('#add_note').hide();
		$('a.attachment').click(function() {
			$('a.attachment').fadeOut('fast');
			$('#file_control').slideDown('slow');
			return false;
		});
		$('a.note_control').click(function() {
			$('a.note_control').fadeOut('fast');
			$('#add_note').slideDown('slow');
			return false;
		});
	});</script>";

if (isset ($id_inc)) { //If $id_inc is set (when $_GET["id"] is set, not $_GET["insert_form"]
	ui_print_page_header (__('Incident details'). ' #'.$id_inc, "images/book_edit.png", false, "", false, "");
	echo '<form name="accion_form" method="POST" action="index.php?sec=incidencias&sec2=operation/incidents/incident&action=update">';
	echo '<input type="hidden" name="id_inc" value="'.$id_inc.'">';
}
else {
	ui_print_page_header (__('Create incident'), "images/book_edit.png", false, "", false, "");
	echo '<form name="accion_form" method="POST" action="index.php?sec=incidencias&sec2=operation/incidents/incident&action=insert">';
}

echo '<table cellpadding="4" cellspacing="4" class="databox" width="650px">';
echo '<tr><td class="datos"><b>'.__('Incident').'</b></td><td colspan="3" class="datos">';

if ((check_acl ($config["id_user"], $id_grupo, "IM") == 1) OR ($usuario == $config["id_user"])) {
	html_print_input_text ("titulo", $titulo,'', 70);
}
else {
	html_print_input_text_extended ("titulo", $titulo, "", "", 70, "", false, "", "readonly"); 
}

echo '</td></tr>';

echo '<tr><td class="datos2"><b>'.__('Opened at').'</b></td><td class="datos2"><i>'.date ($config['date_format'], $inicio).'</i></td>';
echo '<td class="datos2"><b>'.__('Updated at').'</b><td class="datos2"><i>'.date ($config['date_format'], $actualizacion).'</i></td></tr>';

echo '<tr><td class="datos"><b>'.__('Owner').'</b></td><td class="datos">';

if ((check_acl ($config["id_user"], $id_grupo, "IM") == 1) OR ($usuario == $config["id_user"])) {
	html_print_select (users_get_info (), "usuario_form", $usuario, '', 'SYSTEM', '', false, false, true, "w135");
}
else {
	html_print_select (users_get_info (), "usuario_form", $usuario, '', 'SYSTEM', '', false, false, true, "w135", true);
}
echo '</td><td class="datos"><b>'.__('Status').'</b></td><td class="datos">';

if ((check_acl ($config["id_user"], $id_grupo, "IM") == 1) OR ($usuario == $config["id_user"])) {
	html_print_select (incidents_get_status (), "estado_form", $estado, '', '', '', false, false, false, 'w135');
}
else {
	html_print_select (incidents_get_status (), "estado_form", $estado, '', '', '', false, false, false, 'w135', true);
}
echo '</td></tr>';

echo '<tr><td class="datos2"><b>'.__('Source').'</b></td><td class="datos2">';

$fields = array ();
$return = db_get_all_rows_sql ("SELECT origen FROM torigen ORDER BY origen");
if ($return === false)
	$return[0] = $estado; //Something must be displayed

foreach ($return as $row) {
	$fields[$row["origen"]] = $row["origen"];
}

// Only owner could change source or user with Incident management privileges
if ((check_acl ($config["id_user"], $id_grupo, "IM") == 1) OR ($usuario == $config["id_user"])) {
	html_print_select ($fields, "origen_form", $estado, '', '', '', false, false, false, 'w135');
}
else {
	html_print_select ($fields, "origen_form", $estado, '', '', '', false, false, false, 'w135', true);
}
echo '</td><td class="datos2"><b>'.__('Group').'</b></td><td class="datos2">';

// Group combo
if ((check_acl ($config["id_user"], $id_grupo, "IM") == 1) OR ($usuario == $config["id_user"])) {
	html_print_select_groups($config["id_user"], "IR", true, "grupo_form", $id_grupo, '', '', '', false, false, false, 'w135');
}
else {
	html_print_select_groups($config["id_user"], "IR", true, "grupo_form", $id_grupo, '', '', '', false, false, true, 'w135', true);
}

echo '</td></tr><tr><td class="datos"><b>'.__('Priority').'</b></td><td class="datos">';

if ((check_acl ($config["id_user"], $id_grupo, "IM") == 1) OR ($usuario == $config["id_user"])) {
	html_print_select (incidents_get_priorities (), "prioridad_form", $prioridad, '', '', '', false, false, false, 'w135');
}
else {
	html_print_select (incidents_get_priorities (), "prioridad_form", $prioridad, '', '', '', false, false, false, 'w135', true);
}

echo '</td><td class="datos"><b>'.__('Creator').'</b></td><td class="datos">';
if (empty ($id_creator)) {
	echo 'SYSTEM';
}
else {
	echo $id_creator.' (<i>'.get_user_fullname($id_creator).'</i>)';
}

echo '</td></tr><tr><td class="datos2" colspan="4">';

if ((check_acl ($config["id_user"], $id_grupo, "IM") == 1) OR ($usuario == $config["id_user"])) {
	html_print_textarea ("descripcion", 15, 80, $texto, 'style="height:200px;"');
}
else {
	html_print_textarea ("descripcion", 15, 80, $texto, 'style="height:200px;" disabled');
}

echo '</td></tr></table><div style="width: 650px; text-align:right;">';

// Only if user is the used who opened incident or (s)he is admin
if (isset ($id_inc) AND ((check_acl ($config["id_user"], $id_grupo, "IM") == 1) OR ($usuario == $config["id_user"]))) {
	html_print_submit_button (__('Update incident'), "accion", false, 'class="sub upd"');
}
elseif (check_acl ($config["id_user"], $id_grupo, "IW")) {
	html_print_submit_button (__('Create'), "accion", false, 'class="sub wand"');
}
else {
	html_print_submit_button (__('Submit'), "accion", true, 'class="sub upd"');
}
echo "</div></form>";

//If we're actually working on an incident
if (isset ($id_inc)) {
	// ********************************************************************
	// Notes 
	// ********************************************************************
	
	echo '<div>';

	echo '<a class="note_control" href="#">';
	echo html_print_image ('images/add.png', true);
	echo __('Add note');
	echo '</a>';
	echo '</div><div>';
	echo '<form id="add_note" name="nota" method="POST" action="index.php?sec=incidencias&sec2=operation/incidents/incident_detail&insertar_nota=1&id='.$id_inc.'"><h3>'.__('Add note').'</h3>';
	echo '<table cellpadding="4" cellspacing="4" class="databox" width="600px">
		<tr><td class="datos2"><textarea name="nota" rows="5" cols="70" style="height: 100px;"></textarea></td>
		<td valign="bottom"><input name="addnote" type="submit" class="sub wand" value="'.__('Add').'"></td></tr>
		</table></form></div><div>';

	$result = incidents_get_notes ($id_inc);
	
	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->class = "databox";
	$table->width = 600;
	$table->data = array ();
	$table->head = array ();
	
	foreach ($result as $row) {
		$data = array ();
		$data[0] = html_print_image("images/page_white_text.png", true, array("border" => '0')); 
		$data[1] = __('Author').': ' . ui_print_username ($row["id_usuario"], true).' (' . ui_print_timestamp ($row["timestamp"], true).')';
		array_push ($table->data, $data);
		
		$data = array ();
		$data[0] = '';
		if ((check_acl ($config["id_user"], $id_grupo, "IM") == 1) OR ($row["id_usuario"] == $config["id_user"])) {
			$data[0] .= html_print_input_image ("delete_nota", "images/cross.png", $row["id_nota"], 'border:0px;" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;', true);
		}
		$data[1] = $row["nota"];
		array_push ($table->data, $data);
	}
	
	if (!empty ($table->data)) {
		echo "<h3>".__('Notes attached to incident').'</h3>';
		echo '<form method="POST" action="index.php?sec=incidencias&sec2=operation/incidents/incident_detail&id='.$id_inc.'">';
		html_print_table ($table);
		echo '</form>';
	}
	unset ($table);
	

	// ************************************************************
	// Files attached to this incident
	// ************************************************************

	$result = incidents_get_attach ($id_inc);

	$table->cellpadding = 4;
	$table->cellspacing = 4;
	$table->class = "databox";
	$table->width = 650;
	$table->head = array ();
	$table->data = array ();

	$table->head[0] = __('Filename');
	$table->head[1] = __('Description');
	$table->head[2] = __('Size');
	$table->head[3] = __('Delete');

	$table->align[2] = "center";
	$table->align[3] = "center";

	foreach ($result as $row) {
		$data[0] = html_print_image("images/disk.png", true, array("border" => '0', "align" => "top")) . '&nbsp;&nbsp;<a target="_new" href="attachment/pand'.$row["id_attachment"].'_'.$row["filename"].'"><b>'.$row["filename"].'</b></a>';
		$data[1] = $row["description"];
		$data[2] = format_for_graph ($row["size"])."B";
		if ((check_acl ($config["id_user"], $id_grupo, "IM") == 1) OR ($usuario == $config["id_user"])) {
			$data[3] = html_print_input_image ("delete_file", "images/cross.png", $row["id_attachment"], 'border:0px;" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;', true);
		}
		else {
			$data[3] = '';
		}
		array_push ($table->data, $data);
	}
	
	if (!empty ($table->data)) {
		echo "<h3>".__('Attached files')."</h3>";
		echo '<form method="POST" action="index.php?sec=incidencias&sec2=operation/incidents/incident_detail&id='.$id_inc.'">';
		html_print_table ($table);
		echo '</form>';
	}
	unset ($table);
	
	// ************************************************************
	// Upload control
	// ************************************************************
	
	
	// Upload control
	if ((check_acl($config["id_user"], $id_grupo, "IW")==1)) {
		
		echo '<div>';
		echo '<a class="attachment" href="#">';
		echo html_print_image ('images/add.png', true);
		echo __('Add attachment');
		echo '</a>';
		echo '</div>';
		
		echo '<div><form method="post" id="file_control" action="index.php?sec=incidencias&sec2=operation/incidents/incident_detail&id='.$id_inc.'&upload_file=1" enctype="multipart/form-data"><h3>'.__('Add attachment').'</h3>';
		echo '<table cellpadding="4" cellspacing="3" class="databox" width="400">
			<tr><td class="datos">'.__('Filename').'</td><td class="datos"><input type="file" name="userfile" value="userfile" class="sub" size="40" /></td></tr>
			<tr><td class="datos2">'.__('Description').'</td><td class="datos2" colspan="3"><input type="text" name="file_description" size="47"></td></tr>
			<tr><td colspan="2" style="text-align: right;">	<input type="submit" name="upload" value="'.__('Upload').'" class="sub wand"></td></tr>
			</table></form></div>';
	}
}
?>