<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnológicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list
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

if (comprueba_login ()) {
	return;
}

if ((! give_acl ($config['id_user'], 0, "LM")) && (! give_acl ($config['id_user'], 0, "AW")) && (! give_acl ($config['id_user'], 0, "PM")) && (! give_acl ($config['id_user'], 0, "DM")) && (! give_acl ($config['id_user'], 0, "UM"))) {
	return;
}
?>

<div class="tit bg3">:: <?php echo __('Administration') ?> ::</div>
<div class="menug" id="god">
	
<?php
if (give_acl ($config['id_user'], 0, "AW")) {
	if ($sec2 == "godmode/agentes/modificar_agente" || $sec2 == "godmode/agentes/configurar_agente") {
		echo '<div id="god1s">';
	}
	else
		echo '<div id="god1">';
	echo '<ul class="mn"><li><a href="index.php?sec=gagente&amp;sec2=godmode/agentes/modificar_agente" class="mn">'.__('Manage agents').'</a></li></ul></div>';
	
	if ($sec == "gagente") {
		if ($sec2 == "godmode/agentes/manage_config") {
			echo "<div class='arrowgs'>";
		} else {
			echo "<div class='arrowg'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=gagente&amp;sec2=godmode/agentes/manage_config' class='mn'>".__('Manage config.')."</a></li></ul></div>";
		
		if ($sec2 == "godmode/agentes/manage_config_remote") {
			echo "<div class='arrowgs'>";
		} else {
			echo "<div class='arrowg'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=gagente&amp;sec2=godmode/agentes/manage_config_remote' class='mn'>".__('Duplicate config')."</a></li></ul></div>";
		
		// Manage groups
		if (give_acl($config['id_user'], 0, "PM")) {
			if ($sec2 == "godmode/groups/group_list" || $sec2 == "godmode/groups/configure_group") {
				echo "<div class='arrowgs'>";
			} else {
				echo "<div class='arrowg'>";
			}
			echo "<ul class='mn'><li><a href='index.php?sec=gagente&amp;sec2=godmode/groups/group_list' class='mn'>".__('Manage groups')."</a></li></ul></div>";
		}

		// Planned downtimes
		if ((give_acl($config['id_user'], 0, "AW")==1)){
			if ($sec2 == "godmode/agentes/planned_downtime" || $sec2 == "godmode/agentes/planned_downtime") {
				echo "<div class='arrowgs'>";
			}
			else
				echo "<div class='arrowg'>";
			echo "<ul class='mn'><li><a href='index.php?sec=gagente&sec2=godmode/agentes/planned_downtime' class='mn'>".__('Scheduled downtime')."</a></li></ul></div>";
		}
	}
}

if ((give_acl($config['id_user'], 0, "PM")==1)){
	if ($sec == "gmodules")
		echo '<div id="god_module_sel">';
	else
		echo '<div id="god_module">';
	echo '<ul class="mn"><li><a href="index.php?sec=gmodules&sec2=godmode/modules/module_list" class="mn">'.__('Manage modules').'</a></li></ul></div>';

	if ($sec == "gmodules") {
		if ($sec2 == "godmode/modules/manage_nc_groups" || $sec2 == "godmode/modules/manage_nc_groups_form")
			echo "<div class='arrowgs'>";
		else
			echo "<div class='arrowg'>";
		echo "<ul class='mn'><li><a href='index.php?sec=gmodules&sec2=godmode/modules/manage_nc_groups' class='mn'>".__('Component groups')."</a></li></ul></div>";
	}
	
	if ($sec == "gmodules") {
		if ($sec2 == "godmode/modules/manage_network_components" || $sec2 == "godmode/modules/manage_network_components_form")
			echo "<div class='arrowgs'>";
		else
			echo "<div class='arrowg'>";
		echo "<ul class='mn'><li><a href='index.php?sec=gmodules&sec2=godmode/modules/manage_network_components' class='mn'>".__('Module components')."</a></li></ul></div>";
	}
	// Network Profiles
	if ($sec == "gmodules") {
		if ($sec2 == "godmode/modules/manage_network_templates" || $sec2 == "godmode/modules/manage_network_templates_form")
			echo "<div class='arrowgs'>";
		else
			echo "<div class='arrowg'>";
		echo "<ul class='mn'><li><a href='index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates' class='mn'>".__('Module templates')."</a></li></ul></div>";
	}
}

if (give_acl ($config['id_user'], 0, "LM")) {
	if ($sec2 == "godmode/alerts/modify_alert" || $sec2 == "godmode/alerts/configure_alert") {
		echo '<div id="god2s">';
	}
	else 
	echo '<div id="god2">';
	echo '<ul class="mn"><li><a href="index.php?sec=galertas&amp;sec2=godmode/alerts/modify_alert" class="mn">'.__('Manage alerts').'</a></li></ul></div>';
}

if (give_acl ($config['id_user'], 0, "UM")) {
	if ($sec2 == "godmode/users/user_list" || $sec2 == "godmode/users/configure_user") {
		echo '<div id="god3s">';
	}
	else echo '<div id="god3">';
	echo '<ul class="mn"><li><a href="index.php?sec=gusuarios&amp;sec2=godmode/users/user_list" class="mn">'.__('Manage users').'</a></li></ul></div>';
}

// Reporting
if (give_acl ($config['id_user'], 0, "PM")) {
	echo '<div id="god51">';

	echo '<ul class="mn"><li><a href="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder" class="mn">'. __('Manage reports').'</a></li></ul></div>';

	// Custom report builder
	if ($sec == "greporting") {
		if ($sec2 == "godmode/reporting/reporting_builder") {
			echo "<div class='arrowgs'>";
		} else {
			echo "<div class='arrowg'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=greporting&sec2=godmode/reporting/reporting_builder' class='mn'>".__('Report builder')."</a></li></ul></div>";
	}
	
	// Custom graph builder
	if ($sec == "greporting") {
		if ($sec2 == "godmode/reporting/graph_builder"){
			echo "<div class='arrowgs'>";
		} else {
			echo "<div class='arrowg'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=greporting&sec2=godmode/reporting/graph_builder' class='mn'>".__('Graph builder')."</a></li></ul></div>";
	}

	// Custom map builder
	if ($sec == "greporting") {
		if ($sec2 == "godmode/reporting/map_builder") {
			echo "<div class='arrowgs'>";
		} else {
			echo "<div class='arrowg'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=greporting&sec2=godmode/reporting/map_builder' class='mn'>".__('Map builder')."</a></li></ul></div>";
	}
}

// Manage profiles

if (give_acl ($config['id_user'], 0, "PM")) {
	if ($sec2 == "godmode/profiles/profile_list") {
		echo '<div id="god4s">';
	}
	else echo '<div id="god4">';
	echo '<ul class="mn"><li><a href="index.php?sec=gperfiles&amp;sec2=godmode/profiles/profile_list" class="mn">'.__('Manage profiles').'</a></li></ul></div>';

	// SERVERS
	if ($sec2 == "godmode/servers/modificar_server"){
		echo '<div id="god5s">';
	} else
		echo '<div id="god5">';
		
	echo '<ul class="mn"><li><a href="index.php?sec=gservers&amp;sec2=godmode/servers/modificar_server" class="mn">'.__('Manage servers').'</a></li></ul></div>';
	
	if ($sec == "gservers") {
		if ($sec2 == "godmode/servers/manage_recontask"|| $sec2 == "godmode/servers/manage_recontask_form") {
			echo "<div class='arrowgs'>";
		} else
			echo "<div class='arrowg'>";
		echo "<ul class='mn'><li><a href='index.php?sec=gservers&sec2=godmode/servers/manage_recontask' class='mn'>".__('Manage recontask')."</a></li></ul></div>";
	}
	if ($sec == "gservers") {
		if ($sec2 == "godmode/servers/plugin") {
			echo "<div class='arrowgs'>";
		} else {
			echo "<div class='arrowg'>";
		}

		echo "<ul class='mn'><li><a href='index.php?sec=gservers&sec2=godmode/servers/plugin' class='mn'>".__('Manage plugins')."</a></li></ul></div>";
	}

	// AUDIT
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	if ($sec2 == "godmode/admin_access_logs") {
		echo '<div id="god6s">';
	}
	else echo '<div id="god6">';
	echo '<ul class="mn"><li><a href="index.php?sec=glog&amp;sec2=godmode/admin_access_logs" class="mn">'.__('System Audit Log').'</a></li></ul></div>';
	
	// Main SETUP
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	if ($sec2 == "godmode/setup/setup") {
		echo '<div id="god7s">';
	}
	else echo '<div id="god7">';
	echo '<ul class="mn"><li><a href="index.php?sec=gsetup&amp;sec2=godmode/setup/setup" class="mn">'.__('Pandora Setup').'</a></li></ul></div>';
	
	if ($sec == "gsetup") {
		if ($sec2 == "godmode/setup/links") {
			echo "<div class='arrowgs'>";
		}
		else echo "<div class='arrowg'>";
		echo "<ul class='mn'><li><a href='index.php?sec=gsetup&amp;sec2=godmode/setup/links' class='mn'>".__('Links')."</a></li></ul></div>";
	}
	
	if ($sec == "gsetup"){
		if ($sec2 == "godmode/setup/news") {
			echo "<div class='arrowgs'>";
		}
		else echo "<div class='arrowg'>";
		echo "<ul class='mn'><li><a href='index.php?sec=gsetup&amp;sec2=godmode/setup/news' class='mn'>".__('Site news')."</a></li></ul></div>";
	}
}
if (give_acl ($config['id_user'], 0, "DM")) {
	if ($sec2 == "godmode/db/db_main") {
		echo '<div id="god8s">';
	} else 
		echo '<div id="god8">';
	echo '<ul class="mn">';
	if ($sec == "gdbman" && $sec2 != "godmode/db/db_main") {
		echo '<li>';
	} else {
		echo '<li class="bb0">';
	}
	echo '<a href="index.php?sec=gdbman&amp;sec2=godmode/db/db_main" class="mn">'.__('DB Maintenance').'</a></li></ul></div>';
	
	if ($sec == "gdbman") {
		if ($sec2 == "godmode/db/db_info" || $sec2 == "godmode/db/db_info_data") {
			echo "<div class='arrowgs'>";
		} else {
			echo "<div class='arrowg'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=gdbman&amp;sec2=godmode/db/db_info' class='mn'>".__('DB Information')."</a></li></ul></div>";
		
		if ($sec2 == "godmode/db/db_purge") {
			echo "<div class='arrowgs'>";
		} else {
			echo "<div class='arrowg'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=gdbman&amp;sec2=godmode/db/db_purge' class='mn'>".__('Database purge')."</a></li></ul></div>";
		
		if ($sec2 == "godmode/db/db_refine") {
			echo "<div class='arrowgs'>";
		} else {
			echo "<div class='arrowg'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=gdbman&amp;sec2=godmode/db/db_refine' class='mn'>".__('Database debug')."</a></li></ul></div>";
		
		if ($sec2 == "godmode/db/db_audit") {
			echo "<div class='arrowgs'>";
		} else {
			echo "<div class='arrowg'>";
		}
		echo "<ul class='mn'><li><a href='index.php?sec=gdbman&amp;sec2=godmode/db/db_audit' class='mn'>".__('Database audit')."</a></li></ul></div>";
		
		if ($sec2 == "godmode/db/db_event") {
			echo "<div id='arrowgls'>";
		} else {
			echo "<div id='arrowgl'>";
		}
		echo "<ul class='mn'><li class='bb0'><a href='index.php?sec=gdbman&amp;sec2=godmode/db/db_event' class='mn'>".__('Database event')."</a></li></ul></div>";

		if ($sec2 == "godmode/db/db_sanity") {
			echo "<div id='arrowgls'>";
		} else {
			echo "<div id='arrowgl'>";
		}
		echo "<ul class='mn'><li class='bb0'><a href='index.php?sec=gdbman&sec2=godmode/db/db_sanity' class='mn'>".__('Database sanity')."</a></li></ul></div>";
	}
}

if (is_array ($config['extensions'])) {
	if ($sec == "gextensions") {
		$selected = ' menu-selected';
	} else {
		$selected = '';
	}
	echo '<div id="op-extensions" class="operation-menu'.$selected.'">';
	echo '<ul class="mn"><li><a href="index.php?sec=gextensions&sec2=godmode/extensions" class="mn">';
	echo __('Extensions');
	echo '</a></li></ul>';
	echo "</div>";
	if ($selected != '') {
		foreach ($config['extensions'] as $extension) {
			if ($extension['godmode_menu'] == '')
				continue;
			$menu = $extension['godmode_menu'];
			if (! give_acl ($config['id_user'], 0, $menu['acl']))
				continue;
			if ($sec2 == $menu['sec2']) {
				echo '<div class="operation-submenu submenu-selected">';
			} else {
				echo '<div class="operation-submenu">';
			}
			echo '<ul class="mn"><li>';
			echo '<a href="index.php?sec=gextensions&sec2='.$menu['sec2'].'" class="mn">'.$menu['name'];
			echo '</a></li></ul></div>';
		}
	}
}
echo '</div>';
?>
