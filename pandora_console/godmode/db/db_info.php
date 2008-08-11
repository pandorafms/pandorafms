<?php 
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandora@gmail.com>, 2005-2006
// Evi Vanoost <vanooste@rcbi.rochester.edu> 2008

// Load global vars
require_once ("include/config.php");

check_login ();
	
if (! give_acl ($config['id_user'], 0, "DM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Database Management Info");
	require ("general/noaccess.php");
	return;
}
// Todo for a good DB maintenance 
/* 
	- Delete too on datos_string and and datos_inc tables 
	
	- A function to "compress" data, and interpolate big chunks of data (1 month - 60000 registers) 
 	  onto a small chunk of interpolated data (1 month - 600 registers)
 	
	- A more powerful selection (by Agent, by Module, etc).
 */

echo "<h2>".__('dbmain_title')." &gt; ";
echo __('db_info2')."</h2>";
echo "<table border=0>";
echo "<tr><td>";
echo '<h3>'.__('db_agente_modulo').'</h3>';
echo "<img src='reporting/fgraph.php?tipo=db_agente_modulo&width=600&height=200'><br>";
echo "<tr><td><br>";
echo "<tr><td>";
echo '<h3>'.__('db_agente_paquetes').'</h3>';
echo "<img src='reporting/fgraph.php?tipo=db_agente_paquetes&width=600&height=200'><br>";
echo "<br><br><a href='index.php?sec=gdbman&sec2=godmode/db/db_info_data'>".__('press_db_info')."</a>";
echo "</table>";
?>
