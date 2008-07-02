<?php

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development and project architecture and management
// Copyright (c) 2004-2007 Raul Mateos Martin, raulofpandora@gmail.com
// CSS and some PHP additions
// Copyright (c) 2006 Jose Navarro <contacto@indiseg.net>
// Additions to Pandora FMS 1.2 graph code 
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas, info@artica.es
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
?>

<?php
if (! isset($_SESSION["id_usuario"])) {
	return;
} 

?>
<div class="tit bg">:: <?php echo lang_string ("operation_header") ?> ::</div>
<div class="menuop" id="op">
<?php

// Agent read, Server read
if (give_acl($_SESSION["id_usuario"], 0, "AR")) {
	if (isset($_GET["sec2"]) && $_GET["sec2"] == "operation/agentes/tactical") {
		echo '<div id="op1s">';
	} else {
		echo '<div id="op1">';
	}
	echo '<ul class="mn"><li><a href="index.php?sec=estado&amp;sec2=operation/agentes/tactical&amp;refr=60" class="mn">'.lang_string ("view_agents").'</a></li></ul></div>';

	if (isset($_GET["sec"]) && $_GET["sec"] == "estado"){

		if (isset($_GET["sec2"]) && $_GET["sec2"] == "operation/agentes/tactical"){
			echo "<div class='arrows'>";
		} else {
			echo "<div class='arrow'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=estado&amp;sec2=operation/agentes/tactical&refr=60' class='mn'>".lang_string ("tactical_view")."</a></li></ul></div>";
	
		if (isset($_GET["sec2"]) && $_GET["sec2"] == "operation/agentes/estado_grupo"){
			echo "<div class='arrows'>";
		} else {
			echo "<div class='arrow'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=estado&amp;sec2=operation/agentes/estado_grupo&refr=60' class='mn'>".lang_string ("group_view_menu")."</a></li></ul></div>";

		if (isset($_GET["sec2"]) && $_GET["sec2"] == "operation/agentes/networkmap"){
			echo "<div class='arrows'>";
		} else {
			echo "<div class='arrow'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=estado&amp;sec2=operation/agentes/networkmap' class='mn'>".lang_string("Network Map")."</a></li></ul></div>";
	
		if (isset($_GET["sec2"]) && ($_GET["sec2"] == "operation/agentes/estado_agente" || $_GET["sec2"] == "operation/agentes/ver_agente" || $_GET["sec2"] == "operation/agentes/datos_agente")) {
			echo "<div class='arrows'>";
		} else {
			echo "<div class='arrow'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=60' class='mn'>".lang_string ("agent_detail")."</a></li></ul></div>";

		if (isset($_GET["sec2"]) && $_GET["sec2"] == "operation/agentes/estado_alertas"){
			echo "<div class='arrows'>";
		} else {
			echo "<div class='arrow'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=estado&amp;sec2=operation/agentes/estado_alertas&amp;refr=60' class='mn'>".lang_string ("alert_detail")."</a></li></ul></div>";

		if (isset($_GET["sec2"]) && $_GET["sec2"] == "operation/agentes/status_monitor") {
			echo "<div class='arrows'>";
		} else {
			echo "<div class='arrow'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=60' class='mn'>".lang_string ("detailed_monitoragent_state")."</a></li></ul></div>";

		if (isset($_GET["sec2"]) && $_GET["sec2"] == "operation/agentes/exportdata") {
			echo "<div class='arrows'>";
		} else {
			echo "<div class='arrow'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=estado&amp;sec2=operation/agentes/exportdata' class='mn'>".lang_string ("export_data")."</a></li></ul></div>";

	}

	// Visual console
	if ( isset($_GET["sec2"]) && $_GET["sec2"] == "operation/visual_console/index") {
		echo '<div id="op9s">';
	} else {
		echo '<div id="op9">';
	}
	echo '<ul class="mn"><li>';
	echo '<a href="index.php?sec=visualc&sec2=operation/visual_console/index"  class="mn">'.lang_string ("visual_console").'</a></li></ul></div>';

	if ( isset($_GET["sec"]) && $_GET["sec"]  == "visualc") {
		$sql="SELECT * FROM tlayout";
		if($res=mysql_query($sql))
		while ($row = mysql_fetch_array($res)){
			if (isset($_GET["sec2"]) && $_GET["sec2"] == "operation/visual_console/render_view") {
				if (isset($_GET["id"]) && $_GET["id"] == $row["id"])
					echo "<div class='arrows'>";
				else 
					echo "<div class='arrow'>";
			} else {
				echo "<div class='arrow'>";
			}
			echo "<ul class='mn'><li><a href='index.php?sec=visualc&sec2=operation/visual_console/render_view&id=".$row["id"]."' class='mn'>". substr($row["name"],0,15). "</a></li></ul></div>";
		}
	}
	

	// Server view
	if ( isset($_GET["sec"]) && $_GET["sec"] == "estado_server") {
		echo '<div id="op2s">';
	} else {
		echo '<div id="op2">';
	}
	echo '<ul class="mn"><li>';
	echo '<a href="index.php?sec=estado_server&amp;sec2=operation/servers/view_server&amp;refr=60" class="mn">'.lang_string ("view_servers").'</a></li></ul></div>';
}


// Check access for incident
if (give_acl($_SESSION["id_usuario"], 0, "IR")==1) {
	if(isset($_GET["sec2"]) && ($_GET["sec2"] == "operation/incidents/incident" || $_GET["sec2"] == "operation/incidents/incident_detail"|| $_GET["sec2"] == "operation/incidents/incident_note")) {
		echo '<div id="op3s">';
	} else {
		echo '<div id="op3">';
	}
	echo '<ul class="mn"><li><a href="index.php?sec=incidencias&amp;sec2=operation/incidents/incident" class="mn">'.lang_string ("manage_incidents").'</a></li></ul></div>';

	if (isset($_GET["sec"]) && $_GET["sec"] == "incidencias"){
		if(isset($_GET["sec2"]) && $_GET["sec2"] == "operation/incidents/incident_search") {
			echo "<div class='arrows'>";
		} else {
			echo "<div class='arrow'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=incidencias&amp;sec2=operation/incidents/incident_search' class='mn'>".lang_string ("search_incident")."</a></li></ul></div>";

		if (isset($_GET["sec2"]) && $_GET["sec2"] == "operation/incidents/incident_statistics") {
			echo "<div class='arrows'>";
		} else {
			echo "<div class='arrow'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=incidencias&amp;sec2=operation/incidents/incident_statistics' class='mn'>".lang_string ("statistics")."</a></li></ul></div>";
	}
}


// Rest of options, all with AR privilege
if (give_acl($_SESSION["id_usuario"], 0, "AR")==1) {

	// Events
	if(isset($_GET["sec2"]) && $_GET["sec2"] == "operation/events/events") {
		echo '<div id="op4s">';
	} else {
		echo '<div id="op4">';
	}
	echo '<ul class="mn"><li><a href="index.php?sec=eventos&amp;sec2=operation/events/events" class="mn">'.lang_string ("view_events").'</a></li></ul></div>';
	// Event statistics submenu
	if (isset($_GET["sec"]) && $_GET["sec"] == "eventos"){
		if(isset($_GET["sec2"]) && $_GET["sec2"] == "operation/events/event_statistics") {
			echo "<div class='arrows'>";
		} else {
			echo "<div class='arrow'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=eventos&amp;sec2=operation/events/event_statistics' class='mn'>".lang_string ("statistics")."</a></li></ul></div>";
	}

	// Users
	if(isset($_GET["sec2"]) && ($_GET["sec2"] == "operation/users/user" || $_GET["sec2"] == "operation/users/user_edit" )) {
		echo '<div id="op5s">';
	} else {
		echo '<div id="op5">';
	}
	echo '<ul class="mn"><li><a href="index.php?sec=usuarios&amp;sec2=operation/users/user" class="mn">'.lang_string ("view_users").'</a></li></ul></div>';

	// User edit (submenu)
	if (isset($_GET["sec"]) && $_GET["sec"] == "usuarios") {
		if(isset($_GET["ver"]) && $_GET["ver"] == $_SESSION["id_usuario"]) {
			echo "<div class='arrows'>";
		} else {
			echo "<div class='arrow'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=usuarios&amp;sec2=operation/users/user_edit&amp;ver=".$_SESSION["id_usuario"]."' class='mn'>".lang_string ("index_myuser")."</a></li></ul></div>";

		// User statistics require UM
		if (give_acl($_SESSION["id_usuario"], 0, "UM")==1) {
			if(isset($_GET["sec2"]) && $_GET["sec2"] == "operation/users/user_statistics") {
				echo "<div class='arrows'>";
			} else {
				echo "<div class='arrow'>";
			}
			echo "<ul class='mn'><li><a href='index.php?sec=usuarios&amp;sec2=operation/users/user_statistics' class='mn'>".lang_string ("statistics")."</a></li></ul></div>";
		}
	}

	// SNMP console
	if(isset($_GET["sec2"]) && $_GET["sec2"] == "operation/snmpconsole/snmp_view") {
		echo '<div id="op6s">';
	} else {
		echo '<div id="op6">';
	}
	echo '<ul class="mn"><li><a href="index.php?sec=snmpconsole&amp;sec2=operation/snmpconsole/snmp_view&amp;refr=30" class="mn">'.lang_string ("SNMP_console").'</a></li></ul></div>';

	if ((give_acl($_SESSION["id_usuario"], 0, "AW")==1)){
		// SNMP Console alert (submenu)
		if (isset($_GET["sec"]) && $_GET["sec"] == "snmpconsole"){
			if(isset($_GET["sec2"]) && $_GET["sec2"] == "operation/snmpconsole/snmp_alert") {
				echo "<div class='arrows'>";
			} else {
				echo "<div class='arrow'>";
			}
			echo "<ul class='mn'><li><a href='index.php?sec=snmpconsole&amp;sec2=operation/snmpconsole/snmp_alert' class='mn'>".lang_string ("snmp_console_alert")."</a></li></ul></div>";
		}
	}
	
	// Messages
	if(isset($_GET["sec2"]) && $_GET["sec2"] == "operation/messages/message" && !isset($_GET["nuevo_g"])) {
		echo '<div id="op7s">';
	} else {
		echo '<div id="op7">';
	}
	echo '<ul class="mn"><li><a href="index.php?sec=messages&amp;sec2=operation/messages/message" class="mn">'. lang_string ("messages").'</a></li></ul></div>';

	// New message (submenu)
	if (isset($_GET["sec"]) && $_GET["sec"] == "messages"){
		if(isset($_GET["sec2"]) && isset($_GET["nuevo_g"])) {
			echo "<div class='arrows'>";
		} else {
			echo "<div class='arrow'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=messages&amp;sec2=operation/messages/message&amp;nuevo_g' class='mn'>".lang_string ("messages_g")."</a></li></ul></div>";
	}

	// Reporting
	if (isset($_GET["sec2"]) && $_GET["sec2"] == "operation/reporting/reporting"){
		echo '<div id="op8s">';
	} else {
		echo '<div id="op8">';
	}
	echo '<ul class="mn">';
	
	if (isset($_GET["sec"]) && $_GET["sec"] == "reporting" &&
	isset($_GET["sec2"]) && $_GET["sec2"] != "operation/reporting/reporting"){
		echo '<li>';
	} else {
		echo '<li class="bb0">';
	}
	echo '<a href="index.php?sec=reporting&sec2=operation/reporting/custom_reporting" class="mn">'.
		lang_string ("reporting").'</a></li></ul></div>';

	// Custom reporting
	if (isset($_GET["sec"]) && $_GET["sec"] == "reporting"){
		if (isset($_GET["sec2"]) && $_GET["sec2"] == 
		"operation/reporting/custom_reporting" || $_GET["sec2"] == 
		"operation/reporting/reporting_viewer"){
			echo "<div class='arrows'>";
		} else {
			echo "<div class='arrow'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=reporting&sec2=operation/reporting/custom_reporting' class='mn'>".lang_string ("custom_reporting")."</a></li></ul></div>";
	}

	// Custom graph viewer
	if (isset($_GET["sec"]) && $_GET["sec"] == "reporting"){
		if (isset($_GET["sec2"]) && $_GET["sec2"] == "operation/reporting/graph_viewer"){
			echo "<div class='arrows'>";
		} else {
			echo "<div class='arrow'>";
		}
		echo "<ul class='mn'><li class='bb0'><a href='index.php?sec=reporting&sec2=operation/reporting/graph_viewer' class='mn'>".lang_string ("custom_graphs")."</a></li></ul></div>";
	}

}

?>
</div>
