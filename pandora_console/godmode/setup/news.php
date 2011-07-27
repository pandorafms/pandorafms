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

check_login ();

if (! check_acl ($config['id_user'], 0, "PM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Link Management");
	require ("general/noaccess.php");
	exit;
}

// Header
ui_print_page_header (__('Site news management'), "", false, "", true);



if (isset ($_POST["create"])) { // If create
	$subject = get_parameter ("subject");
	$text = get_parameter ("text");
	$timestamp = db_get_value ('NOW()', 'tconfig_os', 'id_os', 1);
	
	$values = array(
		'subject' => $subject,
		'text' => $text,
		'author' => $config["id_user"],
		'timestamp' => $timestamp);
	$id_link = db_process_sql_insert('tnews', $values);
	
	ui_print_result_message ($id_link,
		__('Successfully created'),
		__('Could not be created'));
}

if (isset ($_POST["update"])) { // if update
	$id_news = (int) get_parameter ("id_news", 0);
	$subject = get_parameter ("subject");
	$text = get_parameter ("text");
	//NOW() column exists in any table and always displays the current date and time, so let's get the value from a row in a table which can't be deleted.
	//This way we prevent getting no value for this variable
	$timestamp = db_get_value ('NOW()', 'tconfig_os', 'id_os', 1);
	
	$values = array('subject' => $subject, 'text' => $text, 'timestamp' => $timestamp);
	$result = db_process_sql_update('tnews', $values, array('id_news' => $id_news));

	ui_print_result_message ($result,
		__('Successfully updated'),
		__('Not updated. Error updating data'));
}

if (isset ($_GET["borrar"])) { // if delete
	$id_news = (int) get_parameter ("borrar", 0);
	
	$result = db_process_sql_delete ('tnews', array ('id_news' => $id_news));
	
	ui_print_result_message ($result,
		__('Successfully deleted'),
		__('Could not be deleted'));
}

// Main form view for Links edit
if ((isset ($_GET["form_add"])) || (isset ($_GET["form_edit"]))) {
	if (isset($_GET["form_edit"])) {
		$creation_mode = 0;
		$id_news = (int) get_parameter ("id_news", 0);
		
		$result = db_get_row ("tnews", "id_news", $id_news);
		
		if ($result !== false) {
			$subject = $result["subject"];
			$text = $result["text"];
			$author = $result["author"];
			$timestamp = $result["timestamp"];
		} else {
			echo "<h3 class='error'>".__('Name error')."</h3>";
		}
	} else { // form_add
		$creation_mode = 1;
		$text = "";
		$subject = "";
		$author = $config['id_user'];
	}

	// Create news

	echo '<table class="databox" cellpadding="4" cellspacing="4" width="98%">';   
	echo '<form name="ilink" method="post" action="index.php?sec=gsetup&sec2=godmode/setup/news">';
	if ($creation_mode == 1)
		echo "<input type='hidden' name='create' value='1'>";
	else
		echo "<input type='hidden' name='update' value='1'>";
	echo "<input type='hidden' name='id_news' value='"; 
	if (isset($id_news)) {
		echo $id_news;
	} 
	echo "'>";
	echo '<tr>
	<td class="datos">'.__('Subject').'</td>
	<td class="datos"><input type="text" name="subject" size="35" value="'.$subject.'">';
	echo '<tr>
	<td class="datos2">'.__('Text').'</td>
	<td class="datos2">
	<textarea rows=4 cols=50 name="text" >';
	echo $text;
	echo '</textarea></td>';
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
else {
	$rows = db_get_all_rows_in_table("tnews", "timestamp");
	if ($rows === false) {
		$rows = array();
		echo "<div class='nf'>".__('There are no defined news')."</div>";
	} 
	else {
		// Main list view for Links editor
		echo "<table cellpadding='4' cellspacing='4' class='databox' width=98%>";
		echo "<th>".__('Subject')."</th>";
		echo "<th>".__('Author')."</th>";
		echo "<th>".__('Timestamp')."</th>";
		echo "<th>".__('Delete')."</th>";
		

		
		$color = 1;
		foreach ($rows as $row) {
			if ($color == 1) {
				$tdcolor = "datos";
				$color = 0;
			}
			else {
				$tdcolor = "datos2";
				$color = 1;
			}
			echo "<tr><td class='$tdcolor'><b><a href='index.php?sec=gsetup&sec2=godmode/setup/news&form_edit=1&id_news=".$row["id_news"]."'>".$row["subject"]."</a></b></td>";

			echo "<td class='$tdcolor'>".$row["author"]."</b></td>";
			echo "<td class='$tdcolor'>".$row["timestamp"]."</b></td>";
			
			echo '<td class="'.$tdcolor.'" align="center"><a href="index.php?sec=gsetup&sec2=godmode/setup/news&id_news='.$row["id_news"].'&borrar='.$row["id_news"].'" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">' . html_print_image("images/cross.png", true, array("border" => '0')) . '</a></td></tr>';
		}
		echo "</table>";
	}
	
	echo "<table width='98%'>";
	echo "<tr><td align='right'>";
	echo "<form method='post' action='index.php?sec=gsetup&sec2=godmode/setup/news&form_add=1'>";
	echo "<input type='submit' class='sub next' name='form_add' value='".__('Add')."'>";
	echo "</form></table>";
}
?>
