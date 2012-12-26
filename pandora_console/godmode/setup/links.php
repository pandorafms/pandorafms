<?PHP

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

check_login ();

if (! check_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
	db_pandora_audit("ACL Violation", "Trying to access Link Management");
	require ("general/noaccess.php");
	exit;
}

// Header
ui_print_page_header (__('Link management'), "images/extensions.png", false, "", false, "" );


if (isset($_POST["create"])){ // If create
	$name = get_parameter_post ("name");
	$link = get_parameter_post ("link");
	
	$result = false;
	if ($name != '')
		$result = db_process_sql_insert("tlink", array('name' => $name, 'link' => $link));
	
	if (! $result)
		echo "<h3 class='error'>".__('There was a problem creating link')."</h3>";
	else {
		echo "<h3 class='suc'>".__('Successfully created')."</h3>"; 
		$id_link = $result;
	}
}

if (isset($_POST["update"])){ // if update
	$id_link = io_safe_input($_POST["id_link"]);
	$name = io_safe_input($_POST["name"]);
	$link = io_safe_input($_POST["link"]);

	$result = false;
        if ($name != '')
		$result = db_process_sql_update("tlink", array('name' => $name, 'link' => $link), array('id_link' => $id_link));
	
	if (! $result)
		echo "<h3 class='error'>".__('There was a problem modifying link')."</h3>";
	else
		echo "<h3 class='suc'>".__('Successfully updated')."</h3>";
}
	
if (isset($_GET["borrar"])){ // if delete
	$id_link = io_safe_input($_GET["borrar"]);
	
	$result = db_process_sql_delete("tlink", array("id_link" => $id_link));
	
	if (! $result)
		echo "<h3 class='error'>".__('There was a problem deleting link')."</h3>";
	else
		echo "<h3 class='suc'>".__('Successfully deleted')."</h3>"; 

}

// Main form view for Links edit
if ((isset($_GET["form_add"])) or (isset($_GET["form_edit"]))){
	if (isset($_GET["form_edit"])){
		$creation_mode = 0;
			$id_link = io_safe_input($_GET["id_link"]);
			
			$row = db_get_row("tlink", "id_link", $id_link);
			
			if ($row !== false) {
				$nombre = $row["name"];
				$link = $row["link"];
        	}
			else echo "<h3 class='error'>".__('Name error')."</h3>";
	}
	else { // form_add
		$creation_mode =1;
		$nombre = "";
		$link = "";
	}
	echo '<table class="databox" cellpadding="4" cellspacing="4" width="98%">';
	echo '<form name="ilink" method="post" action="index.php?sec=gsetup&sec2=godmode/setup/links">';
	if ($creation_mode == 1)
		echo "<input type='hidden' name='create' value='1'>";
	else
		echo "<input type='hidden' name='update' value='1'>";
	echo "<input type='hidden' name='id_link' value='";
	if (isset($id_link)) {
		echo $id_link;
	}
	echo "'>";
	echo '<tr>
	<td class="datos">'.__('Link name').'</td>
	<td class="datos"><input type="text" name="name" size="35" value="'.$nombre.'"></td>';
	echo '</tr><tr>
	<td class="datos2">'.__('Link').'</td>
	<td class="datos2">
	<input type="text" name="link" size="35" value="'.$link.'"></td>';
	echo '</tr>';	
	echo "</table>";
	echo "<table width='98%'>";
	echo "<tr><td align='right'>";
	if (isset($_GET["form_add"])) {
		echo "<input name='crtbutton' type='submit' class='sub wand' value='".__('Create')."'>";
	}
	else {
		echo "<input name='crtbutton' type='submit' class='sub upd' value='".__('Update')."'>";
	}
	echo '</form></td></tr></table>';
}
else {  // Main list view for Links editor
	
	$rows = db_get_all_rows_in_table('tlink', 'name');
	if ($rows === false) {
		$rows = array();
	}

	if (!empty($rows)) {

		echo "<table cellpadding='4' cellspacing='4' class='databox' style='width:98%'>";
		echo "<th width='180px'>".__('Link name')."</th>";
		echo "<th width='80px'>".__('Delete')."</th>";
	
		$color=1;
		foreach ($rows as $row) {
			if ($color == 1){
				$tdcolor = "datos";
				$color = 0;
			}
			else {
				$tdcolor = "datos2";
				$color = 1;
			}
			echo "<tr><td class='$tdcolor'><b><a href='index.php?sec=gsetup&sec2=godmode/setup/links&form_edit=1&id_link=".$row["id_link"]."'>".$row["name"]."</a></b></td>";
			echo '<td class="'.$tdcolor.'" align="center"><a href="index.php?sec=gsetup&sec2=godmode/setup/links&id_link='.$row["id_link"].'&borrar='.$row["id_link"].'" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">' . html_print_image("images/cross.png", true) . '</a></td></tr>';
		}
		echo "</table>";
	}

	echo "<table width='98%'>";
	echo "<tr><td align='right'>";
	echo "<form method='post' action='index.php?sec=gsetup&sec2=godmode/setup/links&form_add=1'>";
	echo "<input type='submit' class='sub next' name='form_add' value='".__('Add')."'>";
	echo "</form></table>";
}
?>
