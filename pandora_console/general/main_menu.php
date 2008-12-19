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

if (! isset ($config["id_user"])) {
	require ("general/login_page.php");
	exit();	
}

//This is a helper function to print menu items
function temp_print_menu ($menu, $type) {
	echo '<div class="menu '.$type.'">';
	
	$sec = get_parameter ('sec');
	$sec2 = get_parameter ('sec2');
	
	foreach ($menu as $mainsec => $main) {
		//Set class
		if (!isset ($main["sub"])) {
			$main["sub"] = array ();
		} 
		
		if ($sec == $mainsec) {
			$class = 'selected';
			$selected = 1;
		} else {
			$class = '';
			$selected = 0;
			$style = "";
		}
		
		//Print out the first level
		echo '<ul class="'.$class.'"><li class="mainmenu '.$class.'" id="'.$main["id"].'"><a href="index.php?sec='.$mainsec.'&amp;sec2='.$main["sec2"].'&amp;refr='.$main["refr"].'">'.$main["text"].'</a></li>';
		
		foreach ($main["sub"] as $subsec2 => $sub) {
			//Set class
			if (($sec2 == $subsec2) && (isset ($sub[$subsec2]["options"])) && (get_parameter_get ($sub[$subsec2]["options"]["name"]) == $sub[$subsec2]["options"]["value"])) {
				//If the subclass is selected and there are options and that options value is true 
				$class = 'submenu selected';
			} elseif ($sec2 == $subsec2 && (!isset ($sub[$subsec2]["options"]))) {
				//If the subclass is selected and there are no options
				$class = 'submenu selected';
			} elseif ($selected == 1) {
				//If the mainclass is selected
				$class = 'submenu';
			} else {
				//Else it's invisible
				$class = 'submenu invisible';
			}
			
			if (isset ($sub["type"]) && $sub["type"] == "direct") {
				//This is an external link
				echo '<li class="'.$class.'"><a href="'.$subsec2.'">'.$sub["text"].'</a></li>';
			} else {
				//This is an internal link
				if (isset($sub[$subsec2]["options"])) {
					$link_add = "&amp;".$sub[$subsec2]["options"]["name"]."=".$sub[$subsec2]["options"]["value"];
				} else {
					$link_add = "";
				}
				echo '<li class="'.$class.'"><a href="index.php?sec='.$mainsec.'&amp;sec2='.$subsec2.'&amp;refr='.$sub["refr"].$link_add.'">'.$sub["text"].'</a></li>';
			}
		}
		echo '</ul>';
	}
	//Invisible UL for adding border-top
	echo '<ul style="height: 0px;">&nbsp;</ul></div>';
}

echo '<div class="tit bg">:: '.__('Operation').' ::</div>';
$menu = array ();
require ("operation/menu.php");
temp_print_menu ($menu, "int");

echo '<div class="tit bg3">:: '.__('Administration').' ::</div>';
$menu = array ();
require ("godmode/menu.php");
temp_print_menu ($menu, "int");
unset ($menu);

require ("links_menu.php");
?>