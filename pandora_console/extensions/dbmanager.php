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

	// This following two lines are for real clean the string coming from the PHP
	// because add &#039; for single quote and &quot; for the double, you cannot 
	// see with a simple echo and mysql reject it, so dont forget to do this.

	$sql = unsafe_string ($sql);
	$sql = htmlspecialchars_decode ($sql, ENT_QUOTES);

	$result = mysql_query ($sql);
	if ($result === false) {
		$backtrace = debug_backtrace ();
		$error = mysql_error ();
		return false;
	}
	
	if ($result === true) {
		if ($rettype == "insert_id") {
			return mysql_insert_id ();
		} elseif ($rettype == "info") {
			return mysql_info ();
		}
		return mysql_affected_rows ();
	}
	
	while ($row = mysql_fetch_array ($result, MYSQL_ASSOC)) {
		array_push ($retval, $row);
	}
	mysql_free_result ($result);
	
	if (! empty ($retval))
		return $retval;
	//Return false, check with === or !==
	return false;
}


function dbmgr_extension_main () {
	require_css_file ('dbmanager', 'extensions/dbmanager/');

	$sql = (string) get_parameter ('sql');

	echo "<h1>Database interface</h1>";
	echo '<div class="notify">';
	echo "This is an advanced extension to interface with Pandora FMS database directly from WEB console using native SQL sentences. Please note that <b>you can damage</b> your Pandora FMS installation if you don't know </b>exactly</b> what are you doing, this means that you can severily damage your setup using this extension. This extension is intended to be used <b>only by experienced users</b> with a depth knowledgue of Pandora FMS internals.";
	echo '</div>';

	echo "<br />";
	echo "Some samples of usage: <blockquote><em>SHOW STATUS;<br />DESCRIBE tagente<br />SELECT * FROM tserver<br />UPDATE tagente SET id_grupo = 15 WHERE nombre LIKE '%194.179%'</em></blockquote>";


	echo "<br /><br />";
	echo "<form method='post' action=''>";
	print_textarea ('sql', 5, 50, unsafe_string ($sql));
	echo '<br />';
	echo '<div class="action-buttons" style="width: 100%">';
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
		return;
	}
	
	if (! is_array ($result)) {
		echo "<strong>Output: <strong>".$result;
		return;
	}
	
	$table->width = '90%';
	$table->class = 'dbmanager';
	$table->head = array_keys ($result[0]);
	
	$table->data = $result;
	
	print_table ($table);
}

/* This adds a option in the operation menu */
add_godmode_menu_option (__('DB interface'), 'PM');

/* This sets the function to be called when the extension is selected in the operation menu */
add_extension_godmode_function ('dbmgr_extension_main');

?>
