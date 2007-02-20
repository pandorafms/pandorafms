<?php

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@openideas.info
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas
// Copyright (c) 2004-2007 Raul Mateos Martin, raulofpandora@gmail.com
// Copyright (c) 2006-2007 Jose Navarro jose@jnavarro.net
// Copyright (c) 2006-2007 Jonathan Barajas, jonathan.barajas[AT]gmail[DOT]com

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
// Load global vars
require("include/config.php");

if (comprueba_login() == 0) {
	$iduser=$_SESSION['id_usuario'];
	if (give_acl($id_user, 0, "IR")==1) {	
		echo "<h2>".$lang_label["incident_manag"]."</h2>";
		echo "<h3>".$lang_label["statistics"]."<a href='help/".$help_code."/chap4.php#44' target='_help' class='help'>&nbsp;<span>".$lang_label["help"]."</span></a></h3>";
?>
<img src="reporting/fgraph.php?tipo=estado_incidente" border=0>
<br><br>
<img src="reporting/fgraph.php?tipo=prioridad_incidente" border=0>
<br><br>
<img src="reporting/fgraph.php?tipo=group_incident" border=0>
<br><br>
<img src="reporting/fgraph.php?tipo=user_incident" border=0>
<br><br>
<img src="reporting/fgraph.php?tipo=source_incident" border=0>
<br><br>
<?php
	} else {
			require ("general/noaccess.php");
			audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Incident section");
        }
}
?>