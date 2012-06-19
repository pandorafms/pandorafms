<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
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

check_login();

if (! check_acl ($config["id_user"], 0, "DM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Database cure section");
	require ("general/noaccess.php");
	return;
}

ui_print_page_header (__('Database maintenance').' &raquo; '.__('Database sanity tool'), "images/god8.png", false, "", true);

$sanity = get_parameter ("sanity", 0);

if ($sanity == 1) {
	// Create tagente estado when missing
	echo "<h2>".__('Checking tagente_estado table')."</h2>";
	
	$rows = db_get_all_rows_in_table('tagente_modulo');
	if ($rows === false) {
		$rows = array();
	}
	
	foreach ($rows as $row) {
		$id_agente_modulo = $row['id_agente_modulo'];
		$id_agente = $row["id_agente"];
		// check if exist in tagente_estado and create if not
		$sql = "SELECT COUNT(*) FROM tagente_estado 
			WHERE id_agente_modulo = $id_agente_modulo";
		$total = db_get_sql ($sql);
		if ($total == 0) {
			$sql = "INSERT INTO tagente_estado (id_agente_modulo, datos, timestamp, estado, id_agente, last_try, utimestamp, current_interval, running_by, last_execution_try) VALUE ($id_agente_modulo, 0, '01-01-1970 00:00:00', 0, 100, $id_agente, '01-01-1970 00:00:00', 0, 0, 0)";
			echo "Inserting module $id_agente_modulo in state table <br>";
			db_process_sql ($sql);
		}
	}
	ui_print_message(__('Check tagente_estado table: Done'));
	
	echo "<h3>".__('Checking database consistency')."</h2>";

	$rows = db_get_all_rows_in_table('tagente_estado');
	if ($rows === false) {
		$rows = array();
	}
	
	foreach ($rows as $row) {
		$id_agente_modulo = $row['id_agente_modulo'];
		# check if exist in tagente_estado and create if not
		
		$rows = db_get_all_rows_sql("SELECT COUNT(*) AS count FROM tagente_modulo WHERE id_agente_modulo = $id_agente_modulo");
		
		if ($rows !== false) {
			$row = reset($rows);
			$count = $row['count'];
			
			if ($count == 0) {
				echo "Deleting non-existing module $id_agente_modulo in state table <br>";
				
				db_process_sql_delete('tagente_estado', array('id_agente_modulo' => $id_agente_modulo));
			}
		}
	}
	ui_print_message(__('Check database consistency: Done'));
}
elseif ($sanity == 2) {
	echo "<h3>".__('Deleting non-init data')."</h2>";
	
	$rows = db_get_all_rows_filter("tagente_estado", array("utimestamp" => 0));
	if ($rows === false) {
		$rows = array();
	}
	
	foreach ($rows as $row) {
		echo "Deleting non init module " . $row['id_agente_modulo'] ." <br>";
		
		modules_delete_agent_module($row['id_agente_modulo']);
	}
	echo "Deleting bad module (id 0)<br>";
	
	db_process_sql_delete('tagente_modulo', array('id_modulo' => 0));
} 

echo "<br>";
echo "<div style='width:98%'>";
echo __('Pandora FMS Sanity tool is used to remove bad database structure data, created modules with missing status, or modules that cannot be initialized (and don\'t report any valid data) but retry each its own interval to get data. This kind of bad modules could degrade performance of Pandora FMS. This database sanity tool is also implemented in the <b>pandora_db.pl</b> that you should be running each day or week. This console sanity DONT compact your database, only delete bad structured data.');
	
echo "<br><br>";
echo "<b><a href='index.php?sec=gdbman&sec2=godmode/db/db_sanity&sanity=1'>";
echo html_print_image('images/status_away.png', true) . "&nbsp;&nbsp;";
echo __('Sanitize my database now');
echo "</a></b>";


echo "<br><br>";
echo "<b><a href='index.php?sec=gdbman&sec2=godmode/db/db_sanity&sanity=2'>";
echo html_print_image('images/status_away.png', true) . "&nbsp;&nbsp;";
echo __('Delete non-initialized modules now');
echo "</a></b>";

echo "</div>";
?>
