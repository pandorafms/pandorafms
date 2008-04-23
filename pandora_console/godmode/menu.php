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

if (comprueba_login() == 0){
	$id_user = $_SESSION["id_usuario"];
	if ( (give_acl($id_user, 0, "LM")==1) OR (give_acl($id_user, 0, "AW")==1 ) OR (give_acl($id_user, 0, "PM")==1) OR (give_acl($id_user, 0, "DM")==1) OR (give_acl($id_user, 0, "UM")==1 )){

?>

<div class="tit bg3">:: <?php echo $lang_label["godmode_header"] ?> ::</div>
<div class="menug" id="god">
	
<?PHP
	if ((give_acl($id_user, 0, "AW")==1)){
		if (isset($_GET["sec2"]) && ($_GET["sec2"] == "godmode/agentes/modificar_agente" || $_GET["sec2"] == "godmode/agentes/configurar_agente")){
			echo '<div id="god1s">';
		}
		else
			echo '<div id="god1">';
		echo '<ul class="mn"><li><a href="index.php?sec=gagente&amp;sec2=godmode/agentes/modificar_agente" class="mn">'.$lang_label["manage_agents"].'</a></li></ul></div>';
		
		if (isset($_GET["sec"]) && $_GET["sec"] == "gagente"){
			if (isset($_GET["sec2"]) && $_GET["sec2"] == "godmode/agentes/manage_config"){
				echo "<div class='arrowgs'>";
			}
			else echo "<div class='arrowg'>";
			echo "<ul class='mn'><li><a href='index.php?sec=gagente&amp;sec2=godmode/agentes/manage_config' class='mn'>".$lang_label["manage_config"]."</a></li></ul></div>";
			
			if (isset($_GET["sec2"]) && ($_GET["sec2"] == "godmode/groups/group_list" || $_GET["sec2"] == "godmode/groups/configure_group")){
				echo "<div class='arrowgs'>";
			}
			else
				echo "<div class='arrowg'>";
			echo "<ul class='mn'><li><a href='index.php?sec=gagente&amp;sec2=godmode/groups/group_list' class='mn'>".$lang_label["manage_groups"]."</a></li></ul></div>";
		}
	}

	if ((give_acl($id_user, 0, "AW")==1)){
		if (isset($_GET["sec"]) && ($_GET["sec"] == "gmodules"))
			echo '<div id="god_module_sel">';
		else
			echo '<div id="god_module">';
		echo '<ul class="mn"><li><a href="index.php?sec=gmodules&sec2=godmode/modules/module_list" class="mn">'.$lang_label["manage_modules"].'</a></li></ul></div>';

		if (isset($_GET["sec"]) && $_GET["sec"] == "gmodules"){
			if (isset($_GET["sec2"]) && $_GET["sec2"] == "godmode/modules/manage_nc_groups" || $_GET["sec2"] == "godmode/modules/manage_nc_groups_form")
				echo "<div class='arrowgs'>";
			else
				echo "<div class='arrowg'>";
			echo "<ul class='mn'><li><a href='index.php?sec=gmodules&sec2=godmode/modules/manage_nc_groups' class='mn'>".$lang_label["nc_groups"]."</a></li></ul></div>";
		}
		
		if (isset($_GET["sec"]) && $_GET["sec"] == "gmodules"){
			if (isset($_GET["sec2"]) && ( $_GET["sec2"] == "godmode/modules/manage_network_components" || $_GET["sec2"] == "godmode/modules/manage_network_components_form") )
				echo "<div class='arrowgs'>";
			else
				echo "<div class='arrowg'>";
			echo "<ul class='mn'><li><a href='index.php?sec=gmodules&sec2=godmode/modules/manage_network_components' class='mn'>".$lang_label["network_components"]."</a></li></ul></div>";
		}
		// Network Profiles
		if (isset($_GET["sec"]) && $_GET["sec"] == "gmodules"){
			if (isset($_GET["sec2"]) && ($_GET["sec2"] == "godmode/modules/manage_network_templates" || $_GET["sec2"] == "godmode/modules/manage_network_templates_form" ))
				echo "<div class='arrowgs'>";
			else
				echo "<div class='arrowg'>";
			echo "<ul class='mn'><li><a href='index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates' class='mn'>".$lang_label["network_templates"]."</a></li></ul></div>";
		}
	}
	
	if ((give_acl($id_user, 0, "LM")==1)){
		if (isset($_GET["sec2"]) && ($_GET["sec2"] == "godmode/alerts/modify_alert" || $_GET["sec2"] == "godmode/alerts/configure_alert")){
			echo '<div id="god2s">';
		}
		else 
            echo '<div id="god2">';
		echo '<ul class="mn"><li><a href="index.php?sec=galertas&amp;sec2=godmode/alerts/modify_alert" class="mn">'.$lang_label["manage_alerts"].'</a></li></ul></div>';

        if (isset($_GET["sec"]) && $_GET["sec"] == "galertas"){
            if (isset($_GET["sec2"]) && $_GET["sec2"] == "godmode/alerts/plugin"){
                echo "<div class='arrowgs'>";
            }
            else echo "<div class='arrowg'>";

            echo "<ul class='mn'><li><a href='index.php?sec=galertas&sec2=godmode/alerts/plugin' class='mn'>".lang_string("Manage plugins")."</a></li></ul></div>";
        }
	}
	if ((give_acl($id_user, 0, "UM")==1)){
		if (isset($_GET["sec2"]) && ($_GET["sec2"] == "godmode/users/user_list" || $_GET["sec2"] == "godmode/users/configure_user")){
			echo '<div id="god3s">';
		}
		else echo '<div id="god3">';
		echo '<ul class="mn"><li><a href="index.php?sec=gusuarios&amp;sec2=godmode/users/user_list" class="mn">'.$lang_label["manage_users"].'</a></li></ul></div>';
	}
	// Reporting
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	if ((give_acl($id_user, 0, "AW")==1)){
			
			echo '<div id="god51">';
		
			echo '<ul class="mn"><li><a href="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder" class="mn">'. $lang_label["manage_reporting"].'</a></li></ul></div>';

			// Custom report builder
			if (isset($_GET["sec"]) && $_GET["sec"] == "greporting"){
				if (isset($_GET["sec2"]) && $_GET["sec2"] == "godmode/reporting/reporting_builder"){
					echo "<div class='arrowgs'>";
				} else {
					echo "<div class='arrowg'>";
				}
				echo "<ul class='mn'><li><a href='index.php?sec=greporting&sec2=godmode/reporting/reporting_builder' class='mn'>".$lang_label["report_builder"]."</a></li></ul></div>";
			}
			
			// Custom graph builder
			if (isset($_GET["sec"]) && $_GET["sec"] == "greporting"){
				if (isset($_GET["sec2"]) && $_GET["sec2"] == "godmode/reporting/graph_builder"){
					echo "<div class='arrowgs'>";
				} else {
					echo "<div class='arrowg'>";
				}
				echo "<ul class='mn'><li><a href='index.php?sec=greporting&sec2=godmode/reporting/graph_builder' class='mn'>".$lang_label["graph_builder"]."</a></li></ul></div>";
			}

			// Custom map builder
			if (isset($_GET["sec"]) && $_GET["sec"] == "greporting"){
				if (isset($_GET["sec2"]) && $_GET["sec2"] == "godmode/reporting/map_builder"){
					echo "<div class='arrowgs'>";
				} else {
					echo "<div class='arrowg'>";
				}
				echo "<ul class='mn'><li><a href='index.php?sec=greporting&sec2=godmode/reporting/map_builder' class='mn'>".$lang_label["map_builder"]."</a></li></ul></div>";
			}
	}

	// Manage profiles
	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	
	if ( (give_acl($id_user, 0, "PM")==1)){
		if (isset($_GET["sec2"]) && $_GET["sec2"] == "godmode/profiles/profile_list"){
			echo '<div id="god4s">';
		}
		else echo '<div id="god4">';
		echo '<ul class="mn"><li><a href="index.php?sec=gperfiles&amp;sec2=godmode/profiles/profile_list" class="mn">'.$lang_label["manage_profiles"].'</a></li></ul></div>';

		// SERVERS
		// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

		if (isset($_GET["sec2"]) && $_GET["sec2"] == "godmode/servers/modificar_server"){
			echo '<div id="god5s">';
        	} else
			echo '<div id="god5">';
			
		echo '<ul class="mn"><li><a href="index.php?sec=gservers&amp;sec2=godmode/servers/modificar_server" class="mn">'.$lang_label["manage_servers"].'</a></li></ul></div>';
		
		if (isset($_GET["sec"]) && $_GET["sec"] == "gservers"){
			if (isset($_GET["sec2"]) && $_GET["sec2"] == "godmode/servers/manage_recontask"|| $_GET["sec2"] == "godmode/servers/manage_recontask_form"){
				echo "<div class='arrowgs'>";
			} else
				echo "<div class='arrowg'>";
			echo "<ul class='mn'><li><a href='index.php?sec=gservers&sec2=godmode/servers/manage_recontask' class='mn'>".$lang_label["manage_recontask"]."</a></li></ul></div>";
		}
       	// AUDIT
       	// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
		if (isset($_GET["sec2"]) && $_GET["sec2"] == "godmode/admin_access_logs"){
			echo '<div id="god6s">';
		}
		else echo '<div id="god6">';
		echo '<ul class="mn"><li><a href="index.php?sec=glog&amp;sec2=godmode/admin_access_logs" class="mn">'.$lang_label["system_audit"].'</a></li></ul></div>';
		
		// Main SETUP
		// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
		if (isset($_GET["sec2"]) && $_GET["sec2"] == "godmode/setup/setup"){
			echo '<div id="god7s">';
		}
		else echo '<div id="god7">';
		echo '<ul class="mn"><li><a href="index.php?sec=gsetup&amp;sec2=godmode/setup/setup" class="mn">'.$lang_label["setup_screen"].'</a></li></ul></div>';
		
		if (isset($_GET["sec"]) && $_GET["sec"] == "gsetup"){
			if (isset($_GET["sec2"]) && $_GET["sec2"] == "godmode/setup/links"){
				echo "<div class='arrowgs'>";
			}
			else echo "<div class='arrowg'>";
			echo "<ul class='mn'><li><a href='index.php?sec=gsetup&amp;sec2=godmode/setup/links' class='mn'>".$lang_label["setup_links"]."</a></li></ul></div>";
		}
		
		if (isset($_GET["sec"]) && $_GET["sec"] == "gsetup"){
			if (isset($_GET["sec2"]) && $_GET["sec2"] == "godmode/setup/news"){
				echo "<div class='arrowgs'>";
			}
			else echo "<div class='arrowg'>";
			echo "<ul class='mn'><li><a href='index.php?sec=gsetup&amp;sec2=godmode/setup/news' class='mn'>".$lang_label["site_news"]."</a></li></ul></div>";
		}
	}
	if ((give_acl($id_user, 0, "DM")==1)){
		if (isset($_GET["sec2"]) && $_GET["sec2"] == "godmode/db/db_main"){
			echo '<div id="god8s">';
		} else 
			echo '<div id="god8">';
		echo '<ul class="mn">';
		if (isset($_GET["sec"]) && $_GET["sec"] == "gdbman" && 
		isset($_GET["sec2"]) && $_GET["sec2"] != "godmode/db/db_main"){
			echo '<li>';
		} else {
			echo '<li class="bb0">';
		}
		echo '<a href="index.php?sec=gdbman&amp;sec2=godmode/db/db_main" class="mn">'.$lang_label["db_maintenance"].'</a></li></ul></div>';
		
		if (isset($_GET["sec"]) && $_GET["sec"] == "gdbman"){
		
		
			if (isset($_GET["sec2"]) && ($_GET["sec2"] == "godmode/db/db_info" || $_GET["sec2"] == "godmode/db/db_info_data")){
				echo "<div class='arrowgs'>";
			} else 
				echo "<div class='arrowg'>";
			echo "<ul class='mn'><li><a href='index.php?sec=gdbman&amp;sec2=godmode/db/db_info' class='mn'>".$lang_label["db_info"]."</a></li></ul></div>";
			
			if (isset($_GET["sec2"]) && $_GET["sec2"] == "godmode/db/db_purge"){
				echo "<div class='arrowgs'>";
			} else 
				echo "<div class='arrowg'>";			
			echo "<ul class='mn'><li><a href='index.php?sec=gdbman&amp;sec2=godmode/db/db_purge' class='mn'>".$lang_label["db_purge"]."</a></li></ul></div>";
			
			if (isset($_GET["sec2"]) && $_GET["sec2"] == "godmode/db/db_refine"){
				echo "<div class='arrowgs'>";
			} else 
				echo "<div class='arrowg'>";			
			echo "<ul class='mn'><li><a href='index.php?sec=gdbman&amp;sec2=godmode/db/db_refine' class='mn'>".$lang_label["db_refine"]."</a></li></ul></div>";
			
			if (isset($_GET["sec2"]) && $_GET["sec2"] == "godmode/db/db_audit"){
				echo "<div class='arrowgs'>";
			} else 
				echo "<div class='arrowg'>";			
			echo "<ul class='mn'><li><a href='index.php?sec=gdbman&amp;sec2=godmode/db/db_audit' class='mn'>".$lang_label["db_audit"]."</a></li></ul></div>";
			
			if (isset($_GET["sec2"]) && $_GET["sec2"] == "godmode/db/db_event"){
				echo "<div id='arrowgls'>";
			} else 
				echo "<div id='arrowgl'>";			
			echo "<ul class='mn'><li class='bb0'><a href='index.php?sec=gdbman&amp;sec2=godmode/db/db_event' class='mn'>".$lang_label["db_event"]."</a></li></ul></div>";
		}
	}
	?>
</div>
<?php
	} // end verify access to this menu
}
?>
