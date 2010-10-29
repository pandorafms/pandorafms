<?php

//Pandora FMS- http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

function dbmanager_query ($sql, &$error) {
	global $config;
	
	$retval = array();

	if ($sql == '')
		return false;
		
	$sql = html_entity_decode($sql, ENT_QUOTES);

	$result = mysql_query ($sql);
	if ($result === false) {
		$backtrace = debug_backtrace ();
		$error = mysql_error ();
		return false;
	}
	
	if ($result === true) {
		return mysql_affected_rows ();
	}
	
	while ($row = mysql_fetch_array ($result, MYSQL_ASSOC)) {
		array_push ($retval, $row);
	}
	mysql_free_result ($result);
	
	if (! empty ($retval))
		return $retval;

	//Return false, check with === or !==
	return "Empty";
}


function dbmgr_extension_main () {
	require_css_file ('dbmanager', 'extensions/dbmanager/');

    global $config;
    if (! give_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
	    pandora_audit("ACL Violation", "Trying to access Setup Management");
	    require ("general/noaccess.php");
	    return;
    }


	$sql = (string) get_parameter ('sql');

	print_page_header (__('Database interface'), "", false, false, true);

	echo '<div class="notify">';
	echo "This is an advanced extension to interface with Pandora FMS database directly from WEB console using native SQL sentences. Please note that <b>you can damage</b> your Pandora FMS installation if you don't know </b>exactly</b> what are you doing, this means that you can severily damage your setup using this extension. This extension is intended to be used <b>only by experienced users</b> with a depth knowledgue of Pandora FMS internals.";
	echo '</div>';

	echo "<br />";
	echo "Some samples of usage: <blockquote><em>SHOW STATUS;<br />DESCRIBE tagente<br />SELECT * FROM tserver<br />UPDATE tagente SET id_grupo = 15 WHERE nombre LIKE '%194.179%'</em></blockquote>";

	echo "<br /><br />";
	echo "<form method='post' action=''>";
	print_textarea ('sql', 5, 40, html_entity_decode($sql, ENT_QUOTES));
	echo '<br />';
	echo '<div class="action-buttons" style="width: 96%">';
	print_submit_button (__('Execute SQL'), '', false, 'class="sub next"');
	echo '</div>';
	echo "</form>";

	// Processing SQL Code
	if ($sql == '')
		return;

	echo "<br />";
	echo "<hr />";
	echo "<br />";
	
	$error = '';
	$result = dbmanager_query ($sql, $error);
	
	if ($result === false) {
		echo '<strong>An error has occured when querying the database.</strong><br />';
		echo $error;
		
		pandora_audit("Extension DB inface", "Error in SQL", false, false, $sql);
		
		return;
	}
	
	if (! is_array ($result)) {
		echo "<strong>Output: <strong>".$result;
		
		pandora_audit("Extension DB inface", "SQL", false, false, $sql);
		
		return;
	}
	
	$table->width = '90%';
	$table->class = 'dbmanager';
	$table->head = array_keys ($result[0]);
	
	$table->data = $result;
	
	print_table ($table);
}

/* This adds a option in the operation menu */
add_godmode_menu_option (__('DB interface'), 'PM','gdbman',"dbmanager/icon.png");

/* This sets the function to be called when the extension is selected in the operation menu */
add_extension_godmode_function ('dbmgr_extension_main');

?>
