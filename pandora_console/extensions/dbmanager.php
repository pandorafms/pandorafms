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


function string_decompose ($mystring){

	$output = "";
	for ($a=0; $a < strlen($mystring); $a++){
		$output .= substr($mystring, $a, 1)."|";
//		$output .= ord(substr($mystring, $a, 1)).":".substr($mystring, $a, 1)."|";
//		$output .= ord(substr($mystring, $a, 1))."|";
	}
	return $output;
}

function dbmanager_query ($sql, $rettype = "affected_rows") {
	global $config;
	
	$retval = array();

	if ($sql == '')
		return false;

	// This following two lines are for real clean the string coming from the PHP
	// because add &#039; for single quote and &quot; for the double, you cannot 
	// see with a simple echo and mysql reject it, so dont forget to do this.

	$sql = unsafe_string ($sql);
	$sql = htmlspecialchars_decode ($sql, ENT_QUOTES );

	$result = mysql_query ($sql);
	if ($result === false) {
		$backtrace = debug_backtrace ();
		$error = sprintf ('%s (\'%s\') in <strong>%s</strong> on line %d',
			mysql_error (), $sql, $backtrace[0]['file'], $backtrace[0]['line']);
		set_error_handler ('sql_error_handler');
		trigger_error ($error);
		restore_error_handler ();
		return false;
	} elseif ($result === true) {
		if ($rettype == "insert_id") {
			return mysql_insert_id ();
		} elseif ($rettype == "info") {
			return mysql_info ();
		}
		return mysql_affected_rows (); //This happens in case the statement was executed but didn't need a resource
	} else {
		while ($row = mysql_fetch_array ($result)) {
			array_push ($retval, $row);
		}
		mysql_free_result ($result);
	}
	if (! empty ($retval))
		return $retval;
	//Return false, check with === or !==
	return false;
}


function dbmgr_extension_main () {

	echo '<link rel="stylesheet" href="extensions/dbmanager/dbmanager.css" type="text/css" />';

	$sqlcode = get_parameter ("sqlcode", "");

	echo "<h1>Database Interface</h1>";
	echo "<p>This is an advanced extension to interface with Pandora FMS database directly from WEB console using native SQL sentences. Please note that <b>you can damage</b> your Pandora FMS installation if you don't know </b>exactly</b> what are you doing, this means that you can severily damage your setup using this extension. This extension is intended to be used <b>only by experienced users</b> with a depth knowledgue of Pandora FMS internals.</p>";

	echo "<br>";
	echo "Some samples of usage: <i><blockquote>SHOW STATUS;<br>DESCRIBE tagente<br>SELECT * FROM tserver<br>UPDATE tagente SET id_grupo = 15 WHERE nombre LIKE '%194.179%'</blockquote></i>";


	echo "<br><br>";
	echo "<form method='post' action=''>";
	echo "<textarea class='dbmanager' name='sqlcode'>";
	echo unsafe_string ($sqlcode);
	echo "</textarea>";
	echo "<br><br>";
	print_submit_button (__('Execute SQL'), '', false, 'class="sub next"',false);
	echo "</form>";

	// Processing SQL Code
        if ($sqlcode != ""){
		echo "<br>";
		echo "<hr>";
		echo "<br>";
		$result = dbmanager_query ($sqlcode);
		if (!is_array($result)){
			echo "<b>Result: <b>".$result;
		}
		else {
		 	$header = "";
		        $header_printed = 0;
			echo '<table width=90% class="dbmanager">';
			foreach ($result as $item => $value){
				$data = "";
				foreach ($value as $row => $value2){
					if ($header_printed ==0)
						if (!is_numeric($row))
							$header .= "<th class='dbmanager'>" . $row;
					if (!is_numeric($row)){
						$data .= "<td class='dbmanager'>" . $value2;
					}
				}
				if ($header_printed == 0){
					echo $header;
					echo "<tr class='dbmanager'>";
					$header_printed = 1;
				}
				echo $data;
				echo "<tr class='dbmanager'>";
			}
			echo "</table>";
		}
	}	
			
}

/* This adds a option in the operation menu */
add_godmode_menu_option (__('DB Interface'), 'PM');

/* This sets the function to be called when the extension is selected in the operation menu */
add_extension_godmode_function ('dbmgr_extension_main');

?>
