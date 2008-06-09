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
	$id_usuario =$_SESSION["id_usuario"];
        if (give_acl($id_usuario, 0, "AR")==1) {
		echo "<h2>".$lang_label["events"]." &gt; ";
		echo $lang_label["event_statistics"]."</h2>";
		echo "<br><br>";
		echo "<table width=95%>";
		echo "<tr><td valign='top'>";
		echo "<h3>".$lang_label["graph_event_total"]."</h3>";
		echo '<img src="reporting/fgraph.php?tipo=total_events&width=300&height=200" border=0>';
		echo "<td valign='top'>";
		echo "<h3>".$lang_label["graph_event_user"]."</h3>";
		echo '<img src="reporting/fgraph.php?tipo=user_events&width=300&height=200" border=0>';
		echo "<tr><td>";
		echo "<h3>".$lang_label["graph_event_group"]."</h3>";
		echo '<img src="reporting/fgraph.php?tipo=group_events&width=300&height=200" border=0>';
		echo "</table>";
 	} else {
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access event viewer");
		require ("general/noaccess.php");
	}
}
?>