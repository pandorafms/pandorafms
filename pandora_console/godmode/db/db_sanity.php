<?php
// Pandora FMS
// ========================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@openideas.info
// Copyright (c) 2005-2008 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, U6

// Load global vars
require("include/config.php");
check_login();

if ((give_acl($config["id_user"], 0, "DM")==0) AND (dame_admin($config["id_user"])==0)) {
	audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Database cure section");
	require ("general/noaccess.php");
}

echo "<h2>".lang_string("Database sanity tool")."</h2>";

$sanity = get_parameter ("sanity", 0);

if ($sanity == 1) {
	// Create tagente estado when missing
	// ----------------------------------
	echo "<h2>".lang_string ("Checking tagente_estado table")."</h2>";
	$sql = "SELECT * FROM tagente_modulo";
	$result = mysql_query ($sql);
	while ($row = mysql_fetch_array ($result)) {
		$id_agente_modulo = $row[0];
		$id_agente = $row["id_agente"];
		// check if exist in tagente_estado and create if not
		$sql2 = "SELECT COUNT(*) FROM tagente_estado WHERE id_agente_modulo = $id_agente_modulo";
		$result2=mysql_query($sql2);
		$row2=mysql_fetch_array($result2);
		if ($row2[0] == 0){
			$sql3 = "INSERT INTO tagente_estado (id_agente_modulo, datos, timestamp, cambio, estado, id_agente, last_try, utimestamp, current_interval, running_by, last_execution_try) VALUE ($id_agente_modulo, 0, '0000-00-00 00:00:00', 0, 100, $id_agente, '0000-00-00 00:00:00', 0, 0, 0, 0)";
			echo "Inserting module $id_agente_modulo in state table <br>";
			mysql_query($sql3);
		}
	}
	
	echo "<h3>".lang_string("Checking database consistency")."</h2>";
	$query1 = "SELECT * FROM tagente_estado";
	$result = mysql_query($query1);
	while ($row = mysql_fetch_array ($result)) {
		$id_agente_modulo = $row[1];
		# check if exist in tagente_estado and create if not
		$query2 = "SELECT COUNT(*) FROM tagente_modulo WHERE id_agente_modulo = $id_agente_modulo";
		$result2 = mysql_query ($query2);
		$row2 = mysql_fetch_array ($result2);
		if ($row2[0] == 0) {
			$query3 = "DELETE FROM tagente_estado WHERE id_agente_modulo = $id_agente_modulo";
			echo "Deleting non-existing module $id_agente_modulo in state table <br>";
			mysql_query($query3);
		}
	}
} elseif ($sanity == 2) {
	echo "<h3>".lang_string("Deleting non-init data")."</h2>";
	$query1 = "SELECT * FROM tagente_estado WHERE utimestamp = 0";
	$result = mysql_query ($query1);
	while ($row = mysql_fetch_array ($result)) {
		$id_agente_modulo = $row[1];
		echo "Deleting non init module $id_agente_modulo <br>";
		$sql = "DELETE FROM tagente_modulo WHERE id_agente_modulo = $id_agente_modulo";
		mysql_query ($sql);
		$sql = "DELETE FROM tagente_estado WHERE id_agente_modulo = $id_agente_modulo";
		mysql_query ($sql);
	}
	echo "Deleting bad module (id 0)<br>";
	$sql = "DELETE FROM tagente_modulo WHERE id_modulo = 0";
	mysql_query ($sql);
} 

echo "<br>";
echo "<div style='width:520px'>";
echo lang_string ("Pandora FMS Sanity tool is used to remove bad database structure data, created modules with missing status, or modules that cannot be initialized (and don't report any valid data) but retry each it's own interval to get data. This kind of bad modules could degrade performance of Pandora FMS. This database sanity tool is also implemented in the <b>pandora_db.pl</b> that you should be running each day or week. This console sanity DONT compact your database, only delete bad structured data.");
	
echo "<br><br>";
echo "<b><a href='index.php?sec=gdbman&sec2=godmode/db/db_sanity&sanity=1'>";
echo "<img src='images/status_away.png'> &nbsp;";
echo lang_string("Sanitize my database now");
echo "</a></b>";


echo "<br><br>";
echo "<b><a href='index.php?sec=gdbman&sec2=godmode/db/db_sanity&sanity=2'>";
echo "<img src='images/status_away.png'> &nbsp;";
echo lang_string ("Delete non-initialized modules now");
echo "</a></b>";

echo "</div>";



?>
