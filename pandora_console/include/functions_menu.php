<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package Include
 * @subpackage Menu
 */

/**
 * Prints a complete menu structure.
 *
 * @param array Menu structure to print.
 */
function menu_print_menu (&$menu) {
	global $config;
	static $idcounter = 0;

	echo '<div class="menu">';
	
	$sec = (string) get_parameter ('sec');
	$sec2 = (string) get_parameter ('sec2');
	
	$allsec2 = explode('sec2=', $_SERVER['REQUEST_URI']);
	if(isset($allsec2[1])) {
		$allsec2 = $allsec2[1];
	}
	else {
		$allsec2 = $sec2;
	}

	echo '<ul'.(isset ($menu['class']) ? ' class="'.$menu['class'].'"' : '').'>';
	
	foreach ($menu as $mainsec => $main) {
		$extensionInMenuParameter = (string) get_parameter ('extension_in_menu','');
		$showSubsection = true;
		if ($extensionInMenuParameter != '') {
			if ($extensionInMenuParameter == $mainsec)
				$showSubsection = true;
			else
				$showSubsection = false;
		}
		
		if ($mainsec == 'class')
			continue;
		
		if (! isset ($main['id'])) {
			$id = 'menu_'.++$idcounter;
		} else {
			$id = $main['id'];
		}
		
		$submenu = false;
		$classes = array ('menu_icon');
		if (isset ($main["sub"])) {
			$classes[] = 'has_submenu';
			$submenu = true;
		}
		if (!isset ($main["refr"]))
			$main["refr"] = 0;
		
		if (($sec == $mainsec) && ($showSubsection)) {
			$classes[] = 'selected';
		} else {
			$classes[] = 'not_selected';
			if ($extensionInMenuParameter == $mainsec)
				$classes[] = 'selected';
		}
		
		$output = '';
		
		if (! $submenu) {
			$main["sub"] = array (); //Empty array won't go through foreach
		}
		
		$submenu_output = '';
		
		foreach ($main["sub"] as $subsec2 => $sub) {
			//Init some variables
			$visible = false;
			$selected = false;
			
			$subsec2 = io_safe_output($subsec2);
			// Choose valid suboptions (sec2)
			if (enterprise_hook ('enterprise_acl', array ($config['id_user'], $mainsec, $subsec2)) == false){
				continue;
			}
			
			$class = '';
			
			$selected_submenu2 = false;
			$submenu2 = true;
			//Look for submenus in level2!
			if(isset($sub['sub2'])) {
				$class .= 'has_submenu ';
				
				//This hacks avoid empty delimiter error when sec2 is not provided.
				if (!$sec2) {
					$sec2=" ";
				}
				
				//Check if some submenu was selected to mark this (the parent) as selected
				foreach (array_keys($sub['sub2']) as $key) {
					
					if (strpos($key, $sec2) !== false) {
						$selected_submenu2 = true;
						break;
					}
				}
			}
			
			
			//Create godmode option if submenu has godmode on
			if (isset($sub['subsecs'])) {
				
				//Sometimes you need to add all paths because in the 
				//same dir are code from visual console and reports
				//for example
				if (is_array($sub['subsecs'])) {
				
					//Compare each string
					foreach ($sub['subsecs'] as $god_path) {
						
						if (strpos($sec2, $god_path) !== false) {
							$selected_submenu2=true;	
							break;
						}
					} 
				} else {
					//If there is only a string just compare
					if (strpos($sec2, $sub['subsecs']) !== false) {
							$selected_submenu2=true;	
					}
				}
			}

			//Set class
			if (($sec2 == $subsec2 || $allsec2 == $subsec2 || $selected_submenu2) && isset ($sub[$subsec2]["options"])
				&& (get_parameter_get ($sub[$subsec2]["options"]["name"]) == $sub[$subsec2]["options"]["value"])) {
				//If the subclass is selected and there are options and that options value is true
				$class .= 'submenu_selected selected';
				$selected = true;
				$visible = true;
			}
			elseif (($sec2 == $subsec2 || $allsec2 == $subsec2|| $selected_submenu2) && !isset ($sub[$subsec2]["options"])) {
				$class .= 'submenu_selected selected';
				$selected = true;

				$hasExtensions = (array_key_exists('hasExtensions',$main)) ? $main['hasExtensions'] : false;
				if (($extensionInMenuParameter != '') && ($hasExtensions))
					$visible = true;
				else
					$visible = false;
			}
			else {
				//Else it's not selected
				$class .= 'submenu_not_selected';
			}
			if (! isset ($sub["refr"])) {
				$sub["refr"] = 0;
			} 
			
			if (isset ($sub["type"]) && $sub["type"] == "direct") {
				//This is an external link
				$submenu_output .= '<li class="'.$class.'"><a href="'.$subsec2.'">'.$sub["text"]."</a>";
				
				if(isset($sub['sub2']) || $selected) {
					$submenu_output .= html_print_image("include/styles/images/toggle.png", true, array("class" => "toggle", "alt" => "toogle"));
				}
			}
			else {
				//This is an internal link
				if (isset ($sub[$subsec2]["options"])) {
					$link_add = "&amp;".$sub[$subsec2]["options"]["name"]."=".$sub[$subsec2]["options"]["value"];
				}
				else {
					$link_add = "";
				}
				
				$submenu_output .= '<li'.($class ? ' class="'.$class.'"' : '').'>';
				
				//Ini Add icon extension
				$secExtension = null;
				if (array_key_exists('extension',$sub)) $secExtensionBool = $sub["extension"];
				else $secExtensionBool = false;
				
				if ($secExtensionBool) {
					$imageIconDefault = 'images/extensions.png';
					if (strlen($sub["icon"]) > 0) {
						$icon_enterprise = false;
						if (isset($sub['enterprise'])) {
							$icon_enterprise = (bool)$sub['enterprise'];
						}
					
						if ($icon_enterprise) {
							$imageIcon ='enterprise/extensions/'.$sub["icon"];
						}
						else {
							$imageIcon ='extensions/'.$sub["icon"];
						}
						
						if (!file_exists(realpath($imageIcon)))
							$imageIcon = $imageIconDefault;
					}
					else {
						$imageIcon = $imageIconDefault;
					}
					//Delete extension Icon before the was a style with background: url('.$imageIcon.') no-repeat; 
					$submenu_output .= '<div style="width: 16px; height: 16px; float: left; margin: 5px 0px 0px 3px;">&nbsp;</div>';
				}
				
				
				$secExtension = null;
				if (array_key_exists('sec',$sub)) $secExtension = $sub["sec"];
				if (strlen($secExtension) > 0) {
					$secUrl = $secExtension;
					$extensionInMenu = 'extension_in_menu='.$mainsec.'&amp;';
				}
				else {
					$secUrl = $mainsec;
					$extensionInMenu = '';
				}
				
				if (isset ($sub["text"]) || $selected) {
					$title = ' title="' . $sub["text"] . ' "';
				} else {
					$title = '';
				}
				
				//Check if we must mark the menu 
				if (isset($sub['sub2'])) {
					$submenu_output .= '<a class="is_submenu2" href="index.php?'.$extensionInMenu.'sec='.$secUrl.'&amp;sec2='.$subsec2.($sub["refr"] ? '&amp;refr=' . $sub["refr"] : '').$link_add.'"' . $title . '>'.$sub["text"].'</a>';
				} else {
 				
					$submenu_output .= '<a href="index.php?'.$extensionInMenu.'sec='.$secUrl.'&amp;sec2='.$subsec2.($sub["refr"] ? '&amp;refr=' . $sub["refr"] : '').$link_add.'"' . $title . '>'.$sub["text"].'</a>';
				}
				
				if(isset($sub['sub2'])) {
					$submenu_output .= html_print_image("include/styles/images/toggle.png", true, array("class" => "toggle", "alt" => "toogle"));
				}
			
			}
		
			//Print second level submenu
			if(isset($sub['sub2'])) {
				
				//Display if father is selected
				$display = "style='display:none'";
				
				if ($selected) {
					$display = "";
				}
				
				$submenu2_list = '';
				
				foreach ($sub['sub2'] as $key => $sub2) {
					$link = "index.php?sec=".$sec."&sec2=".$key;
					
					//Display if one submenu2 was selected!
					if (strpos($key, $sec2) !== false) {
						$display = "";	
					}
					
					$class = "submenu2";
					
					$submenu2_list .= '<li class="'.$class.'" style="font-weight: normal;">';
					$submenu2_list .= '<a style="font-weight:normal;" href="'.$link.'">'.$sub2["text"].'</a></li>';
				}
				
				//Add submenu2 to submenu string
				$submenu_output .= "<ul class=submenu2 $display>";
				$submenu_output .= $submenu2_list;
				$submenu_output .= "</ul>";
			}
	
			//Submenu close list!
			$submenu_output .= '</li>';		
		}
		
		// Choose valid section (sec)
		if (enterprise_hook ('enterprise_acl', array ($config['id_user'], $mainsec, $main["sec2"])) == false){
			continue;
		} 

		//Print out the first level
		$output .= '<li class="'.implode (" ", $classes).'" id="icon_'.$id.'">';
		$output .= '<a href="index.php?sec='.$mainsec.'&amp;sec2='.$main["sec2"].($main["refr"] ? '&amp;refr='.$main["refr"] : '').'">'.$main["text"].'</a>' . html_print_image("include/styles/images/toggle.png", true, array("class" => "toggle", "alt" => "toogle"));
		if ($submenu_output != '') {
			//WARNING: IN ORDER TO MODIFY THE VISIBILITY OF MENU'S AND SUBMENU'S (eg. with cookies) YOU HAVE TO ADD TO THIS ELSEIF. DON'T MODIFY THE CSS
			if ($visible || in_array ("selected", $classes)) {
				$visible = true;
			}
			if (!$showSubsection) {
				$visible = false;
			}
			
			$output .= '<ul class="submenu'.($visible ? '' : ' invisible').'">';
			$output .= $submenu_output;
			$output .= '</ul>';
		}
		$output .= '</li>';
		echo $output;
	}
	echo '</ul>';
	//Invisible UL for adding border-top
	echo '<ul style="height: 0px;"><li>&nbsp;</li></ul></div>';
}

/**
 * Get all the data structure of menu. Operation and Godmode
 *
 * @return array Menu structure.
 */
function menu_get_full_sec() {
	global $menu_operation;
	global $menu_godmode;
	
	if($menu_godmode == null || $menu_operation == null) {
		return array();
	}
	else {
		$menu = $menu_operation + $menu_godmode;
	}
	
	unset($menu['class']);
	
	menu_add_extras($menu);
	
	return $menu;
}

/**
 * Build an extra access pages array and merge it with menu
 *
 * @param menu array (pass by reference)
 * 
 */
function menu_add_extras(&$menu) {
	$menu_extra = array();
	$menu_extra['gusuarios']['sub']['godmode/users/configure_user']['text'] = __('Configure user');
	$menu_extra['gusuarios']['sub']['godmode/users/configure_profile']['text'] = __('Configure profile');
	$menu_extra['gservers']['sub']['godmode/servers/manage_recontask_form']['text'] = __('Manage recontask');
	$menu_extra['gmodules']['sub']['godmode/modules/manage_network_templates_form']['text'] = __('Module templates management');
	$menu_extra['gmodules']['sub']['enterprise/godmode/modules/manage_inventory_modules_form']['text'] = __('Inventory modules management');
	$menu_extra['gmodules']['sub']['godmode/tag/edit_tag']['text'] = __('Tags management');
	$menu_extra['gagente']['sub']['godmode/agentes/configurar_agente']['text'] = __('Agents management');
	$menu_extra['estado']['sub']['operation/agentes/ver_agente']['text'] = __('View agent');
	$menu_extra['galertas']['sub']['godmode/alerts/configure_alert_template']['text'] = __('Configure alert template');
	$menu_extra['network']['sub']['operation/agentes/networkmap']['text'] = __('Manage network map');
	$menu_extra['reporting']['sub']['operation/reporting/reporting_viewer']['text'] = __('View reporting');
	$menu_extra['reporting']['sub']['operation/visual_console/render_view']['text'] = __('View visual console');
	$menu_extra['reporting']['sub']['godmode/reporting/graph_builder']['text'] = __('Manage custom graphs');
	$menu_extra['reporting']['sub']['enterprise/dashboard/dashboard_replicate']['text'] = __('Copy dashboard');
	$menu_extra['godgismaps']['sub']['godmode/gis_maps/configure_gis_map']['text'] = __('Manage GIS Maps');
	$menu_extra['workspace']['sub']['operation/incidents/incident_statistics']['text'] = __('Incidents statistics');
	$menu_extra['workspace']['sub']['operation/messages/message_edit']['text'] = __('Manage messages');
	$menu_extra['gagente']['sub']['godmode/groups/configure_group']['text'] = __('Manage groups');
	$menu_extra['gagente']['sub']['godmode/groups/configure_modu_group']['text'] = __('Manage module groups');
	$menu_extra['gagente']['sub']['godmode/agentes/configure_field']['text'] = __('Manage custom field');
	$menu_extra['galertas']['sub']['godmode/alerts/configure_alert_action']['text'] = __('Manage alert actions');
	$menu_extra['galertas']['sub']['godmode/alerts/configure_alert_command']['text'] = __('Manage commands');
	$menu_extra['galertas']['sub']['godmode/alerts/configure_alert_compound']['text'] = __('Manage compound alerts');
	$menu_extra['galertas']['sub']['enterprise/godmode/alerts/alert_events']['text'] = __('Manage event alerts');
	$menu_extra['gservers']['sub']['enterprise/godmode/servers/manage_export_form']['text'] = __('Manage export targets');
	$menu_extra['estado']['sub']['enterprise/godmode/services/manage_services']['text'] = __('Manage services');
	$menu_extra['estado']['sub']['godmode/snmpconsole/snmp_alert']['text'] = __('SNMP alerts');
	$menu_extra['estado']['sub']['godmode/snmpconsole/snmp_filters']['text'] = __('SNMP filters');
	$menu_extra['estado']['sub']['enterprise/godmode/snmpconsole/snmp_trap_editor']['text'] = __('SNMP trap editor');
	$menu_extra['estado']['sub']['godmode/snmpconsole/snmp_trap_generator']['text'] = __('SNMP trap generator');
	$menu_extra['workspace']['sub']['operation/incidents/incident_detail']['text'] = __('Manage incident');
	
	// Duplicate extensions as sec=extension to check it from url
	foreach ($menu as $k => $m) {
		if(!isset($m['sub'])) {
			continue;
		}
		foreach($m['sub'] as $kk => $mm) {
			if(isset($mm['sec'])) {
				$menu_extra[$mm['sec']]['sub'][$kk]['text'] = $mm['text'];
			}
		}
	}	

	$menu = array_merge_recursive($menu, $menu_extra);
}

/**
 * Get the sec list built in menu
 *
 * @param bool If true, the array returned will have the structure
 * 		to combo categories (optgroup)
 * 
 * @return array Sections list
 */
function menu_get_sec($with_categories = false) {
	$menu = menu_get_full_sec();
	unset($menu['class']);

	$in_godmode = false;
	foreach($menu as $k => $v) {
		if($with_categories) {
			if(!$in_godmode && $k[0] == 'g') {
				// Hack to dont confuse with gis activated because godmode 
				// sec starts with g (like gismaps)
				if($k != 'gismaps') {
					$in_godmode = true;
				}
			}
			
			if($in_godmode) {
				$category = __('Administration');
			}
			else {
				$category = __('Operation');
			}
			
			$sec_array[$k]['optgroup'] = $category;
			$sec_array[$k]['name'] = $v['text'];
		}
		else {
			$sec_array[$k] = $v['text'];
		}
	}

	return $sec_array;
}

/**
 * Get the pages in a section
 *
 * @param string sec code
 * @param string menu hash. All the menu structure (For example
 * 		returned by menu_get_full_sec(), json encoded and after that 
 * 		base64 encoded. If this value is false this data is obtained from
 * 		menu_get_full_sec();
 * 
 * @return array Sections list
 */
function menu_get_sec_pages($sec,$menu_hash = false) {
	if($menu_hash === false) {
		$menu = menu_get_full_sec();
	}
	else {
		$menu = json_decode(base64_decode($menu_hash),true);
	}
	
	// Get the sec2 of the main section
	$sec2_array[$menu[$sec]['sec2']] = $menu[$sec]['text'];

	// Get the sec2 of the subsections
	foreach($menu[$sec]['sub'] as $k => $v) {
		// Avoid special cases of standalone windows
		if(preg_match('/^javascript:/',$k) || preg_match('/\.php/',$k)) {
			continue;
		}
		
		// If this value has various parameters, we only get the first
		$k = explode('&',$k);
		$k = $k[0];
		
		$sec2_array[$k] = $v['text'];
	}

	return $sec2_array;
}

/**
 * Check if a page (sec2) is in a section (sec)
 *
 * @param string section (sec) code
 * @param string page (sec2)code
 * 
 * @return true if the page is in section, false otherwise
 */
function menu_sec2_in_sec($sec,$sec2) {	
	$sec2_array = menu_get_sec_pages($sec);

	// If this value has various parameters, we only get the first
	$sec2 = explode('&',$sec2);
	$sec2 = $sec2[0];
		
	if($sec2_array != null && in_array($sec2,array_keys($sec2_array))) {
		return true;
	}
	
	return false;	
}

?>
