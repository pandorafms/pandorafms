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

if (! isset ($config['id_user'])) {
	return;
}
?>

<div class="tit bg">:: <?php echo __('Operation'); ?> ::</div>
<div class="menu-operation" id="menu-operation">
<?php
$sec = get_parameter ('sec');
$sec2 = get_parameter ('sec2');

// Agent read, Server read
if (give_acl ($config['id_user'], 0, "AR")) {
	if ($sec2 == "operation/agentes/tactical") {
		$selected = ' menu-selected';
	} else {
		$selected = '';
	}
	echo '<div id="op1" class="operation-menu'.$selected.'">';
	echo '<ul class="mn"><li><a href="index.php?sec=estado&amp;sec2=operation/agentes/tactical&amp;refr=60" class="mn">'.__('View agents').'</a></li></ul></div>';

	if ($sec == "estado") {
		if ($sec2 == "operation/agentes/tactical") {
			echo "<div class='operation-submenu submenu-selected'>";
		} else {
			echo "<div class='operation-submenu'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=estado&amp;sec2=operation/agentes/tactical&refr=60' class='mn'>".__('Tactical view')."</a></li></ul></div>";
	
		if ($sec2 == "operation/agentes/estado_grupo") {
			echo "<div class='operation-submenu submenu-selected'>";
		} else {
			echo "<div class='operation-submenu'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=estado&amp;sec2=operation/agentes/estado_grupo&refr=60' class='mn'>".__('Group view')."</a></li></ul></div>";

		if ($sec2 == "operation/agentes/networkmap") {
			echo "<div class='operation-submenu submenu-selected'>";
		} else {
			echo "<div class='operation-submenu'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=estado&amp;sec2=operation/agentes/networkmap' class='mn'>".__('Network Map')."</a></li></ul></div>";
	
		if (($sec2 == "operation/agentes/estado_agente" || $sec2 == "operation/agentes/ver_agente" || $sec2 == "operation/agentes/datos_agente")) {
			echo "<div class='operation-submenu submenu-selected'>";
		} else {
			echo "<div class='operation-submenu'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=60' class='mn'>".__('Agent detail')."</a></li></ul></div>";

		if ($sec2 == "operation/agentes/estado_alertas"){
			echo "<div class='operation-submenu submenu-selected'>";
		} else {
			echo "<div class='operation-submenu'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=estado&amp;sec2=operation/agentes/estado_alertas&amp;refr=60' class='mn'>".__('Alert detail')."</a></li></ul></div>";

		if ($sec2 == "operation/agentes/status_monitor") {
			echo "<div class='operation-submenu submenu-selected'>";
		} else {
			echo "<div class='operation-submenu'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=60' class='mn'>".__('Monitor detail')."</a></li></ul></div>";

		if ($sec2 == "operation/agentes/exportdata") {
			echo "<div class='operation-submenu submenu-selected'>";
		} else {
			echo "<div class='operation-submenu'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=estado&amp;sec2=operation/agentes/exportdata' class='mn'>".__('Export data')."</a></li></ul></div>";

	}

	// Visual console
	if ( $sec2 == "operation/visual_console/index") {
		$selected = ' menu-selected';
	} else {
		$selected = '';
	}
	echo '<div id="op9" class="operation-menu'.$selected.'">';
	echo '<ul class="mn"><li>';
	echo '<a href="index.php?sec=visualc&sec2=operation/visual_console/index"  class="mn">'.__('Visual console').'</a></li></ul></div>';

	if ($sec  == "visualc") {
		$result = get_db_all_rows_in_table ('tlayout','name');
		$id = get_parameter ('id');
		foreach ($result as $layout) {
			if (!give_acl ($config["id_user"], $layout["id_group"], "AR")) {
				continue;
			} elseif ($sec2 == "operation/visual_console/render_view" && $id == $layout["id"]) {
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
	echo '<a href="index.php?sec=estado_server&amp;sec2=operation/servers/view_server&amp;refr=60" class="mn">'.__('Pandora servers').'</a></li></ul></div>';
}

// Check access for incident
if (give_acl ($config['id_user'], 0, "IR") == 1) {
	if (($sec2 == "operation/incidents/incident" || $sec2 == "operation/incidents/incident_detail"|| $sec2 == "operation/incidents/incident_note")) {
		$selected = ' menu-selected';
	} else {
		$selected = '';
	}
	echo '<div id="op3" class="operation-menu'.$selected.'">';
	echo '<ul class="mn"><li><a href="index.php?sec=incidencias&amp;sec2=operation/incidents/incident" class="mn">'.__('Manage incidents').'</a></li></ul></div>';

	if ($sec == "incidencias"){
		if($sec2 == "operation/incidents/incident_search") {
			echo "<div class='operation-submenu submenu-selected'>";
		} else {
			echo "<div class='operation-submenu'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=incidencias&amp;sec2=operation/incidents/incident_search' class='mn'>".__('Search incident')."</a></li></ul></div>";

		if ($sec2 == "operation/incidents/incident_statistics") {
			echo "<div class='operation-submenu submenu-selected'>";
		} else {
			echo "<div class='operation-submenu'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=incidencias&amp;sec2=operation/incidents/incident_statistics' class='mn'>".__('Statistics')."</a></li></ul></div>";
	}
}


// Rest of options, all with AR privilege
if (give_acl ($config['id_user'], 0, "AR")) {
	// Events
	if($sec2 == "operation/events/events") {
		$selected = ' menu-selected';
	} else {
		$selected = '';
	}
	echo '<div id="op4" class="operation-menu'.$selected.'">';
	echo '<ul class="mn"><li><a href="index.php?sec=eventos&amp;sec2=operation/events/events" class="mn">'.__('View events').'</a></li></ul></div>';
	// Event statistics submenu
	if ($sec == "eventos") {
		if($sec2 == "operation/events/event_statistics") {
			echo "<div class='operation-submenu submenu-selected'>";
		} else {
			echo "<div class='operation-submenu'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=eventos&amp;sec2=operation/events/event_statistics' class='mn'>".__('Statistics')."</a></li></ul></div>";
		
		// Event RSS
		echo "<div class='operation-submenu'>";
		echo "<ul class='mn'><li>";
		echo "<a target='_top' href='operation/events/events_rss.php' class='mn'><img src='images/rss.png' /> ".__('RSS');
		echo "</a></li></ul></div>";
		
		// Event CSV
		echo "<div class='operation-submenu'>";
		echo "<ul class='mn'><li>";
		echo "<a target='_top' href='operation/events/export_csv.php' class='mn'>".__('CSV File')."</a></li></ul></div>";
		
		// Event Marquee
		echo "<div class='operation-submenu'>";
		echo "<ul class='mn'><li>";
		echo "<a target='_top' href='operation/events/events_marquee.php' class='mn'>".__('Marquee')."</a></li></ul></div>";
	}
	
	// Users
	if (($sec2 == "operation/users/user" || $sec2 == "operation/users/user_edit" )) {
		$selected = ' menu-selected';
	} else {
		$selected = '';
	}
	echo '<div id="op5" class="operation-menu'.$selected.'">';
	echo '<ul class="mn"><li><a href="index.php?sec=usuarios&amp;sec2=operation/users/user" class="mn">'.__('View users').'</a></li></ul></div>';

	// User edit (submenu)
	if ($sec == "usuarios") {
		if (isset($_GET["ver"]) && $_GET["ver"] == $config['id_user']) {
			echo "<div class='operation-submenu submenu-selected'>";
		} else {
			echo "<div class='operation-submenu'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=usuarios&amp;sec2=operation/users/user_edit&amp;ver=".$config['id_user']."' class='mn'>".__('Edit my user')."</a></li></ul></div>";

		// User statistics require UM
		if (give_acl ($config['id_user'], 0, "UM")) {
			if($sec2 == "operation/users/user_statistics") {
				echo "<div class='operation-submenu submenu-selected'>";
			} else {
				echo "<div class='operation-submenu'>";
			}
			echo "<ul class='mn'><li><a href='index.php?sec=usuarios&amp;sec2=operation/users/user_statistics' class='mn'>".__('Statistics')."</a></li></ul></div>";
		}
	}

	// SNMP console
	if ($sec2 == "operation/snmpconsole/snmp_view") {
		$selected = ' menu-selected';
	} else {
		$selected = '';
	}
	echo '<div id="op6" class="operation-menu'.$selected.'">';
	echo '<ul class="mn"><li><a href="index.php?sec=snmpconsole&amp;sec2=operation/snmpconsole/snmp_view&amp;refr=30" class="mn">'.__('SNMP console').'</a></li></ul></div>';

	if (give_acl($config['id_user'], 0, "AW")) {
		// SNMP Console alert (submenu)
		if ($sec == "snmpconsole") {
			if ($sec2 == "operation/snmpconsole/snmp_alert") {
				echo "<div class='operation-submenu submenu-selected'>";
			} else {
				echo "<div class='operation-submenu'>";
			}
			echo "<ul class='mn'><li><a href='index.php?sec=snmpconsole&amp;sec2=operation/snmpconsole/snmp_alert' class='mn'>".__('SNMP alerts')."</a></li></ul></div>";
		}
	}
	
	// Messages
	if($sec2 == "operation/messages/message" && !isset ($_GET["nuevo_g"])) {
		$selected = ' menu-selected';
	} else {
		$selected = '';
	}
	echo '<div id="op7" class="operation-menu'.$selected.'">';
	echo '<ul class="mn"><li><a href="index.php?sec=messages&amp;sec2=operation/messages/message" class="mn">'. __('Messages').'</a></li></ul></div>';

	// New message (submenu)
	if ($sec == "messages") {
		if (isset ($_GET["nuevo_g"])) {
			echo "<div class='operation-submenu submenu-selected'>";
		} else {
			echo "<div class='operation-submenu'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=messages&amp;sec2=operation/messages/message&amp;nuevo_g' class='mn'>".__('Messages to groups')."</a></li></ul></div>";
	}

	// Reporting
	if ($sec2 == "operation/reporting/reporting") {
		$selected = ' menu-selected';
	} else {
		$selected = '';
	}
	echo '<div id="op8" class="operation-menu'.$selected.'">';
	echo '<ul class="mn"><li>';
	echo '<a href="index.php?sec=reporting&sec2=operation/reporting/custom_reporting" class="mn">'.
		__('Reporting').'</a></li></ul></div>';

	// Custom reporting
	if ($sec == "reporting") {
		if ($sec2 == "operation/reporting/custom_reporting"
			|| $sec2 == "operation/reporting/reporting_viewer") {
			echo "<div class='operation-submenu submenu-selected'>";
		} else {
			echo "<div class='operation-submenu'>";
		}
		echo "<ul class='mn'><li>
		<a href='index.php?sec=reporting&sec2=operation/reporting/custom_reporting' class='mn'>".__('Custom reporting')."</a></li></ul></div>";
	}

	// Custom graph viewer
	if ($sec == "reporting") {
		if ($sec2 == "operation/reporting/graph_viewer") {
			echo "<div class='operation-submenu submenu-selected'>";
		} else {
			echo "<div class='operation-submenu'>";
		}
		echo "<ul class='mn'><li>
		<a href='index.php?sec=reporting&sec2=operation/reporting/graph_viewer' class='mn'>".__('Custom graphs')."</a></li></ul></div>";
	}

	// Extensions menu additions
	if (is_array ($config['extensions'])) {
		if ($sec == "extensions") {
			$selected = ' menu-selected';
		} else {
			$selected = '';
		}
		echo '<div id="op-extensions" class="operation-menu'.$selected.'">';
		echo '<ul class="mn"><li class="bb0">
		<a href="index.php?sec=extensions&sec2=operation/extensions" class="mn">';
		echo __('Extensions');
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
