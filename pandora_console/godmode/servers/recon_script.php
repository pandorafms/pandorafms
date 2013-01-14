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


if (is_ajax ()) {
	$get_reconscript_description = get_parameter('get_reconscript_description');
	$id_reconscript = get_parameter('id_reconscript');
	
	$description = db_get_value_filter('description', 'trecon_script', array('id_recon_script' => $id_reconscript));
	
	echo htmlentities (io_safe_output($description), ENT_QUOTES, "UTF-8", true);
	return;
}

// Load global vars
global $config;

check_login ();

if (! check_acl ($config['id_user'], 0, "LM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access recon script Management");
	require ("general/noaccess.php");
	return;
}

if (!check_refererer()) {
	require ("general/noaccess.php");

	return;
}

$view = get_parameter ("view", "");
$create = get_parameter ("create", "");

if ($view != ""){
	$form_id = $view;
	$reconscript = db_get_row ("trecon_script", "id_recon_script", $form_id);
	$form_name = $reconscript["name"];
	$form_description = $reconscript["description"];
	$form_script = $reconscript ["script"];
} 
if ($create != ""){
	$form_name = "";
	$form_description = "";
	$form_script = "";
}

// SHOW THE FORM
// =================================================================

if (($create != "") OR ($view != "")){
	
	if ($create != "")
		ui_print_page_header (__('Recon script creation') . ui_print_help_icon("reconscript_definition", true), "", false, "", true);
	else {
		ui_print_page_header (__('Recon script update') . ui_print_help_icon("reconscript_definition", true), "", false, "", true);
		$id_recon_script = get_parameter ("view","");
	}


	if ($create == "") 
		echo "<form name=reconscript method='post' action='index.php?sec=gservers&sec2=godmode/servers/recon_script&update_reconscript=$id_recon_script'>";
	else
		echo "<form name=reconscript method='post' action='index.php?sec=gservers&sec2=godmode/servers/recon_script&create_reconscript=1'>";

	echo '<table width="98%" cellspacing="4" cellpadding="4" class="databox_color">';
	
	echo '<tr><td class="datos">' . __('Name') . '</td>';
	echo '<td class="datos">';
	echo '<input type="text" name="form_name" size=30 value="'.$form_name.'"></td>';
	
	echo '<tr><td class="datos2">' . __('Script fullpath') . '</td>';
	echo '<td class="datos2">';
	echo '<input type="text" name="form_script" size=70 value="'.$form_script.'"></td>';

	echo '<tr><td class="datos2">'.__('Description').'</td>';
	echo '<td class="datos2"><textarea name="form_description" cols="50" rows="4">';
	echo $form_description;
	echo '</textarea></td></tr>';

	echo '</table>';
	echo '<table width=98%>';
	echo '<tr><td align="right">';
	
	if ($create != ""){
		echo "<input name='crtbutton' type='submit' class='sub wand' value='".__('Create')."'>";
	}
	else {
		echo "<input name='uptbutton' type='submit' class='sub upd' value='".__('Update')."'>";
	}
	echo '</form></table>';
}
else {
	ui_print_page_header (__('Recon scripts registered in Pandora FMS'), "", false, "", true);

	// Update reconscript
	if (isset($_GET["update_reconscript"])) { // if modified any parameter
		$id_recon_script = get_parameter ("update_reconscript", 0);
		$reconscript_name = get_parameter ("form_name", "");
		$reconscript_description = get_parameter ("form_description", "");
		$reconscript_script = get_parameter ("form_script", "");		
	
		$sql_update ="UPDATE trecon_script SET 
		name = '$reconscript_name',  
		description = '$reconscript_description', 
		script = '$reconscript_script' 
		WHERE id_recon_script = $id_recon_script";
		$result = false;
		if ($reconscript_name != '' && $reconscript_script != '')
			$result = db_process_sql ($sql_update);	
		if (! $result) {
			echo "<h3 class='error'>".__('Problem updating')."</h3>";
		} else {
			echo "<h3 class='suc'>".__('Updated successfully')."</h3>";
		}
	}

	// Create reconscript
	if (isset($_GET["create_reconscript"])) {	 
		$reconscript_name = get_parameter ("form_name", "");
		$reconscript_description = get_parameter ("form_description", "");
		$reconscript_script = get_parameter ("form_script", "");
		
		$values = array(
			'name' => $reconscript_name,
			'description' => $reconscript_description,
			'script' => $reconscript_script);
		$result = false;
		if ($values['name'] != '' && $values['script'] != '')
			$result = db_process_sql_insert('trecon_script', $values);
		if (! $result){
			echo "<h3 class='error'>".__('Problem creating')."</h3>";
		}
		else {
			echo "<h3 class='suc'>".__('Created successfully')."</h3>";
		}
	}

	if (isset($_GET["kill_reconscript"])){ // if delete alert
		$reconscript_id = get_parameter ("kill_reconscript", 0);
		
		$result = db_process_sql_delete('trecon_script',
			array('id_recon_script' => $reconscript_id));
		
		if (! $result){
			echo "<h3 class='error'>".__('Problem deleting reconscript')."</h3>";
		}
		else {
			echo "<h3 class='suc'>".__('reconscript deleted successfully')."</h3>";
		}
		if ($reconscript_id != 0){
			$result = db_process_sql_delete('trecon_task',
				array('id_recon_script' => $reconscript_id));
		}
	}

	// If not edition or insert, then list available reconscripts
	
	$rows = db_get_all_rows_in_table('trecon_script');
	
	if ($rows !== false) {
		echo '<table width="98%" cellspacing="4" cellpadding="4" class="databox">';
		echo "<th>".__('Name')."</th>";
		echo "<th>".__('Command')."</th>";
		echo "<th>".__('Description')."</th>";
		echo "<th>".__('Delete')."</th>";
		$color = 0;
		foreach ($rows as $row) {
			if ($color == 1){
				$tdcolor = "datos";
				$color = 0;
				}
			else {
				$tdcolor = "datos2";
				$color = 1;
			}
			echo "<tr>";
			echo "<td class='$tdcolor'>";
			echo "<b><a href='index.php?sec=gservers&sec2=godmode/servers/recon_script&view=".$row["id_recon_script"]."'>";
			echo $row["name"];
			echo "</a></b></td>";
			echo "</td><td class='$tdcolor'>";
			echo $row["script"];
			echo "</td><td class='$tdcolor'>";
			echo $row["description"];
			echo "</td><td align='center' class='$tdcolor'>";
			echo "<a href='index.php?sec=gservers&sec2=godmode/servers/recon_script&kill_reconscript=".$row["id_recon_script"]."'>" . html_print_image("images/cross.png", true, array("border" => '0')) . "</a>";
			echo "</td></tr>";
		}
		echo "</table>";
	}
	else {
		echo '<div class="nf">'.
			__('There are no recon scripts in the system') . '</div>';
		echo "<br>";
	}
	echo "<table width=98%>";
	echo "<tr><td align=right>";
	echo "<form name=reconscript method='post' action='index.php?sec=gservers&sec2=godmode/servers/recon_script&create=1'>";
	echo "<input name='crtbutton' type='submit' class='sub next' value='".__('Add')."'>";
	echo "</td></tr></table>";
}

?>
