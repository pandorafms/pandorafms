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

if (comprueba_login() == 0) {
	$iduser=$_SESSION['id_usuario'];
	if (give_acl($id_user, 0, "IR")==1) {
		echo "<h2>".$lang_label["incident_manag"]." &gt; ";
		echo $lang_label["statistics"]."</h2>";

	echo "<table width = 90%>";
	echo "<tr><td valign='top'>";
	echo '<h3>'.$lang_label["inc_stat_status"].'</h3>';
	echo '<img src="reporting/fgraph.php?tipo=estado_incidente" border=0>';
	echo "<td valign='top'>";
	echo '<h3>'.$lang_label["inc_stat_priority"].'</h3>';
	echo '<img src="reporting/fgraph.php?tipo=prioridad_incidente" border=0>';
	echo "<tr><td>";
	echo '<h3>'.$lang_label["inc_stat_group"].'</h3>';
	echo '<img src="reporting/fgraph.php?tipo=group_incident" border=0>';
	echo "<td>";
	echo '<h3>'.$lang_label["inc_stat_user"].'</h3>';
	echo '<img src="reporting/fgraph.php?tipo=user_incident" border=0>';
	echo "<tr><td>";
	echo '<h3>'.$lang_label["inc_stat_source"].'</h3>';
	echo '<img src="reporting/fgraph.php?tipo=source_incident" border=0>';
	echo "<td>";
	echo "</table>";
	} else {
			require ("general/noaccess.php");
			audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Incident section");
        }
}
?>