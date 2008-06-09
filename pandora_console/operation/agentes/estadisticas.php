<?php
// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2008 Sancho Lerena, <slerena@gmail.com>
// Copyright (c) 2005-2008 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


// Load global vars
require("include/config.php");
if (comprueba_login() == 0){ 
	$iduser_temp=$_SESSION['id_usuario'];
	if (give_acl($iduser_temp, 0, "AR") == 1){
		echo "<h2>".$lang_label["ag_title"]." &gt; ";
		echo $lang_label["db_stat_agent"]."</h2>";
		echo "<table border=0>";
		echo "<tr><td><img src='reporting/fgraph.php?tipo=db_agente_modulo'><br>";
		echo "<tr><td><br>";
		echo "<tr><td><img src='reporting/fgraph.php?tipo=db_agente_paquetes'><br>";
		echo "</table>";
	}
 	else {
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Agent estatistics");
		require ("general/noaccess.php");
	}
}
?>