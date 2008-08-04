<?php

// Pandora FMS - the Frexible monitoring system
// ============================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development and project architecture and management
// Copyright (c) 2004-2007 Raul Mateos Martin, raulofpandora@gmail.com
// CSS and some PHP additions 
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
if (! isset ($_SESSION["id_usuario"])) {
	return;
}
?>

<div class="tit bg">:: <?= lang_string ('operation_header'); ?> ::</div>
<div class="menu-operation" id="menu-operation">
<?php
$sec = get_parameter ('sec');
$sec2 = get_parameter ('sec2');

// Agent read, Server read
if (give_acl ($_SESSION["id_usuario"], 0, "AR")) {
	if ($sec2 == "operation/agentes/tactical") {
		$selected = ' menu-selected';
	} else {
		$selected = '';
	}
	echo '<div id="op1" class="operation-menu'.$selected.'">';
	echo '<ul class="mn"><li><a href="index.php?sec=estado&amp;sec2=operation/agentes/tactical&amp;refr=60" class="mn">'.lang_string ("view_agents").'</a></li></ul></div>';

	if ($sec == "estado") {
		if ($sec2 == "operation/agentes/tactical") {
			echo "<div class='operation-submenu submenu-selected'>";
		} else {
			echo "<div class='operation-submenu'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=estado&amp;sec2=operation/agentes/tactical&refr=60' class='mn'>".lang_string ("tactical_view")."</a></li></ul></div>";
	
		if ($sec2 == "operation/agentes/estado_grupo") {
			echo "<div class='operation-submenu submenu-selected'>";
		} else {
			echo "<div class='operation-submenu'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=estado&amp;sec2=operation/agentes/estado_grupo&refr=60' class='mn'>".lang_string ("group_view_menu")."</a></li></ul></div>";

		if ($sec2 == "operation/agentes/networkmap") {
			echo "<div class='operation-submenu submenu-selected'>";
		} else {
			echo "<div class='operation-submenu'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=estado&amp;sec2=operation/agentes/networkmap' class='mn'>".lang_string("Network Map")."</a></li></ul></div>";
	
		if (($sec2 == "operation/agentes/estado_agente" || $sec2 == "operation/agentes/ver_agente" || $sec2 == "operation/agentes/datos_agente")) {
			echo "<div class='operation-submenu submenu-selected'>";
		} else {
			echo "<div class='operation-submenu'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=60' class='mn'>".lang_string ("agent_detail")."</a></li></ul></div>";

		if ($sec2 == "operation/agentes/estado_alertas"){
			echo "<div class='operation-submenu submenu-selected'>";
		} else {
			echo "<div class='operation-submenu'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=estado&amp;sec2=operation/agentes/estado_alertas&amp;refr=60' class='mn'>".lang_string ("alert_detail")."</a></li></ul></div>";

		if ($sec2 == "operation/agentes/status_monitor") {
			echo "<div class='operation-submenu submenu-selected'>";
		} else {
			echo "<div class='operation-submenu'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=60' class='mn'>".lang_string ("detailed_monitoragent_state")."</a></li></ul></div>";

		if ($sec2 == "operation/agentes/exportdata") {
			echo "<div class='operation-submenu submenu-selected'>";
		} else {
			echo "<div class='operation-submenu'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=estado&amp;sec2=operation/agentes/exportdata' class='mn'>".lang_string ("export_data")."</a></li></ul></div>";

	}

	// Visual console
	if ( $sec2 == "operation/visual_console/index") {
		$selected = ' menu-selected';
	} else {
		$selected = '';
	}
	echo '<div id="op9" class="operation-menu'.$selected.'">';
	echo '<ul class="mn"><li>';
	echo '<a href="index.php?sec=visualc&sec2=operation/visual_console/index"  class="mn">'.lang_string ("visual_console").'</a></li></ul></div>';

	if ($sec  == "visualc") {
		$sql="SELECT * FROM tlayout ORDER BY name";
		$id = get_parameter ('id');
		if ($res = mysql_query ($sql))
			while ($layout = mysql_fetch_array ($res)) {
				if ($sec2 == "operation/visual_console/render_view" && $id == $layout["id"]) {
					echo "<div class='operation-submenu submenu-selected'>";
				} else {
					echo "<div class='operation-submenu'>";
				}
				echo "<ul class='mn'><li><a href='index.php?sec=visualc&sec2=operation/visual_console/render_view&id=".$layout["id"]."' class='mn'>". substr ($layout["name"], 0, 15). "</a></li></ul></div>";
			}
	}
	

	// Server view
	if ( $sec == "estado_server") {
		$selected = ' menu-selected';
	} else {
		$selected = '';
	}
	echo '<div id="op2" class="operation-menu'.$selected.'">';
	echo '<ul class="mn"><li>';
	echo '<a href="index.php?sec=estado_server&amp;sec2=operation/servers/view_server&amp;refr=60" class="mn">'.lang_string ("view_servers").'</a></li></ul></div>';
}


// Check access for incident
if (give_acl ($_SESSION["id_usuario"], 0, "IR") == 1) {
	if (($sec2 == "operation/incidents/incident" || $sec2 == "operation/incidents/incident_detail"|| $sec2 == "operation/incidents/incident_note")) {
		$selected = ' menu-selected';
	} else {
		$selected = '';
	}
	echo '<div id="op3" class="operation-menu'.$selected.'">';
	echo '<ul class="mn"><li><a href="index.php?sec=incidencias&amp;sec2=operation/incidents/incident" class="mn">'.lang_string ("manage_incidents").'</a></li></ul></div>';

	if ($sec == "incidencias"){
		if($sec2 == "operation/incidents/incident_search") {
			echo "<div class='operation-submenu submenu-selected'>";
		} else {
			echo "<div class='operation-submenu'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=incidencias&amp;sec2=operation/incidents/incident_search' class='mn'>".lang_string ("search_incident")."</a></li></ul></div>";

		if ($sec2 == "operation/incidents/incident_statistics") {
			echo "<div class='operation-submenu submenu-selected'>";
		} else {
			echo "<div class='operation-submenu'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=incidencias&amp;sec2=operation/incidents/incident_statistics' class='mn'>".lang_string ("statistics")."</a></li></ul></div>";
	}
}


// Rest of options, all with AR privilege
if (give_acl ($_SESSION["id_usuario"], 0, "AR")) {
	// Events
	if($sec2 == "operation/events/events") {
		$selected = ' menu-selected';
	} else {
		$selected = '';
	}
	echo '<div id="op4" class="operation-menu'.$selected.'">';
	echo '<ul class="mn"><li><a href="index.php?sec=eventos&amp;sec2=operation/events/events" class="mn">'.lang_string ("view_events").'</a></li></ul></div>';
	// Event statistics submenu
	if ($sec == "eventos"){
		if($sec2 == "operation/events/event_statistics") {
			echo "<div class='operation-submenu submenu-selected'>";
		} else {
			echo "<div class='operation-submenu'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=eventos&amp;sec2=operation/events/event_statistics' class='mn'>".lang_string ("statistics")."</a></li></ul></div>";
	}

	// Event RSS
	if (isset($_GET["sec"]) && $_GET["sec"] == "eventos"){
		echo "<div class='arrow'>";
		echo "<ul class='mn'><li>";
		echo "<a target='_top' href='operation/events/events_rss.php' class='mn'>".lang_string ("RSS")."</a></li></ul></div>";
	}

	// Event CSV
	if (isset($_GET["sec"]) && $_GET["sec"] == "eventos"){
		echo "<div class='arrow'>";
		echo "<ul class='mn'><li>";
		echo "<a target='_top' href='operation/events/events_csv.php' class='mn'>".lang_string ("CSV File")."</a></li></ul></div>";
	}
	
	// Event Marquee
	if (isset($_GET["sec"]) && $_GET["sec"] == "eventos"){
		echo "<div class='arrow'>";
		echo "<ul class='mn'><li>";
		echo "<a target='_top' href='operation/events/events_marquee.php' class='mn'>".lang_string ("Marquee")."</a></li></ul></div>";
	}

	// Users
	if(($sec2 == "operation/users/user" || $sec2 == "operation/users/user_edit" )) {
		$selected = ' menu-selected';
	} else {
		$selected = '';
	}
	echo '<div id="op5" class="operation-menu'.$selected.'">';
	echo '<ul class="mn"><li><a href="index.php?sec=usuarios&amp;sec2=operation/users/user" class="mn">'.lang_string ("view_users").'</a></li></ul></div>';

	// User edit (submenu)
	if ($sec == "usuarios") {
		if(isset($_GET["ver"]) && $_GET["ver"] == $_SESSION["id_usuario"]) {
			echo "<div class='operation-submenu submenu-selected'>";
		} else {
			echo "<div class='operation-submenu'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=usuarios&amp;sec2=operation/users/user_edit&amp;ver=".$_SESSION["id_usuario"]."' class='mn'>".lang_string ("index_myuser")."</a></li></ul></div>";

		// User statistics require UM
		if (give_acl($_SESSION["id_usuario"], 0, "UM")==1) {
			if($sec2 == "operation/users/user_statistics") {
				echo "<div class='operation-submenu submenu-selected'>";
			} else {
				echo "<div class='operation-submenu'>";
			}
			echo "<ul class='mn'><li><a href='index.php?sec=usuarios&amp;sec2=operation/users/user_statistics' class='mn'>".lang_string ("statistics")."</a></li></ul></div>";
		}
	}

	// SNMP console
	if($sec2 == "operation/snmpconsole/snmp_view") {
		$selected = ' menu-selected';
	} else {
		$selected = '';
	}
	echo '<div id="op6" class="operation-menu'.$selected.'">';
	echo '<ul class="mn"><li><a href="index.php?sec=snmpconsole&amp;sec2=operation/snmpconsole/snmp_view&amp;refr=30" class="mn">'.lang_string ("SNMP_console").'</a></li></ul></div>';

	if ((give_acl($_SESSION["id_usuario"], 0, "AW")==1)){
		// SNMP Console alert (submenu)
		if ($sec == "snmpconsole"){
			if($sec2 == "operation/snmpconsole/snmp_alert") {
				echo "<div class='operation-submenu submenu-selected'>";
			} else {
				echo "<div class='operation-submenu'>";
			}
			echo "<ul class='mn'><li><a href='index.php?sec=snmpconsole&amp;sec2=operation/snmpconsole/snmp_alert' class='mn'>".lang_string ("snmp_console_alert")."</a></li></ul></div>";
		}
	}
	
	// Messages
	if($sec2 == "operation/messages/message" && !isset($_GET["nuevo_g"])) {
		$selected = ' menu-selected';
	} else {
		$selected = '';
	}
	echo '<div id="op7" class="operation-menu'.$selected.'">';
	echo '<ul class="mn"><li><a href="index.php?sec=messages&amp;sec2=operation/messages/message" class="mn">'. lang_string ("messages").'</a></li></ul></div>';

	// New message (submenu)
	if ($sec == "messages"){
		if(isset($_GET["nuevo_g"])) {
			echo "<div class='operation-submenu submenu-selected'>";
		} else {
			echo "<div class='operation-submenu'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=messages&amp;sec2=operation/messages/message&amp;nuevo_g' class='mn'>".lang_string ("messages_g")."</a></li></ul></div>";
	}

	// Reporting
	if ($sec2 == "operation/reporting/reporting") {
		$selected = ' menu-selected';
	} else {
		$selected = '';
	}
	echo '<div id="op8" class="operation-menu'.$selected.'">';
	echo '<ul class="mn">';
	
	if ($sec == "reporting" &&
		$sec2 != "operation/reporting/reporting") {
		echo '<li>';
	} else {
		echo '<li class="bb0">';
	}
	echo '<a href="index.php?sec=reporting&sec2=operation/reporting/custom_reporting" class="mn">'.
		lang_string ("reporting").'</a></li></ul></div>';

	// Custom reporting
	if ($sec == "reporting"){
		if ($sec2 == 
		"operation/reporting/custom_reporting" || $sec2 == 
		"operation/reporting/reporting_viewer") {
			echo "<div class='operation-submenu submenu-selected'>";
		} else {
			echo "<div class='operation-submenu'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=reporting&sec2=operation/reporting/custom_reporting' class='mn'>".lang_string ("custom_reporting")."</a></li></ul></div>";
	}

	// Custom graph viewer
	if ($sec == "reporting") {
		if ($sec2 == "operation/reporting/graph_viewer") {
			echo "<div class='operation-submenu submenu-selected'>";
		} else {
			echo "<div class='operation-submenu'>";
		}
		echo "<ul class='mn'><li class='bb0'><a href='index.php?sec=reporting&sec2=operation/reporting/graph_viewer' class='mn'>".lang_string ("custom_graphs")."</a></li></ul></div>";
	}

	// Extensions menu additions
	if (sizeof ($config['extensions'])) {
		if ($sec == "extensions") {
			$selected = ' menu-selected';
		} else {
			$selected = '';
		}
		echo '<div id="op-extensions" class="operation-menu'.$selected.'">';
		echo '<ul class="mn"><li><a href="index.php?sec=extensions&sec2=operation/extensions" class="mn">';
		echo lang_string ('Extensions');
		echo '</a></li></ul>';
		echo "</div>";
		if ($selected != '') {
			foreach ($config['extensions'] as $extension) {
				if ($extension['operation_menu'] == '')
					continue;
				$menu = $extension['operation_menu'];
				if ($sec2 == $menu['sec2']) {
					echo '<div class="operation-submenu submenu-selected">';
				} else {
					echo '<div class="operation-submenu">';
				}
				echo '<ul class="mn"><li>';
				echo '<a href="index.php?sec=extensions&sec2='.$menu['sec2'].'" class="mn">'.$menu['name'];
				echo '</a></li></ul></div>';
			}
		}
	}
}

?>
</div>
