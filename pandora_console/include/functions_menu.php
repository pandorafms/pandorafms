<?php
/**
 * Auxiliary functions to manage menu.
 *
 * @category   Include
 * @package    Pandora FMS
 * @subpackage Menu
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

// Begin.
global $config;

// Set variable to store menu type (classic or collapsed).
if (is_ajax() === true && empty(get_parameter('menuType')) === false) {
    $_SESSION['menu_type'] = get_parameter('menuType', 'classic');
    return;
}


/**
 * Prints a complete menu structure.
 *
 * @param array $menu Menu structure to print.
 *
 * @return void
 */
function menu_print_menu(&$menu)
{
    global $config;
    global $menuTypeClass;
    global $tab_active;
    global $menu1_selected;
    global $menu2_selected;
    static $idcounter = 0;

    echo '<div class="menu">';

    $sec = (string) get_parameter('sec');
    $sec2 = (string) get_parameter('sec2');
    if ($sec2 === 'operation/agentes/ver_agente') {
        $sec2 = 'operation/agentes/estado_agente';
    } else if ($sec2 === 'godmode/alerts/configure_alert_template') {
        $sec2 = 'godmode/alerts/alert_templates';
    } else if ($sec2 === 'godmode/events/events') {
        $sec2 = 'godmode/events/events&section=filter';
    } else if ($sec2 === 'godmode/alerts/configure_alert_action') {
        $sec2 = 'godmode/alerts/alert_actions';
    } else if ($sec2 === 'godmode/alerts/configure_alert_command') {
        $sec2 = 'godmode/alerts/alert_commands';
    } else if ($sec2 === 'enterprise/godmode/setup/edit_skin') {
        $sec2 = 'enterprise/godmode/setup/setup_skins';
    } else if ($sec2 === 'operation/agentes/networkmap.dinamic') {
        $sec2 = 'operation/agentes/pandora_networkmap';
    } else if ($sec2 === 'godmode/gis_maps/configure_gis_map') {
        $map_id = (string) get_parameter('map_id');
        if (empty($map_id) === false) {
            $sec2 = 'operation/gis_maps/render_view&map_id='.$map_id;
        } else {
            $sec2 = 'operation/gis_maps/gis_map';
        }
    } else if ($sec2 === 'enterprise/godmode/servers/manage_export_form') {
        $sec2 = 'enterprise/godmode/servers/manage_export';
    } else if ($sec2 === 'godmode/setup/gis_step_2') {
        $sec2 = 'godmode/setup/setup&section=gis';
    } else if ($sec2 === 'enterprise/godmode/agentes/agent_autoconfiguration.definition') {
        $sec2 = 'enterprise/godmode/agentes/agent_autoconfiguration';
    } else if ($sec2 === 'enterprise/godmode/reporting/graph_template_list') {
        $sec2 = 'godmode/reporting/graphs';
    } else if ($sec2 === 'enterprise/godmode/reporting/graph_template_wizard') {
        $sec2 = 'godmode/reporting/graphs';
    } else if ($sec2 === 'godmode/reporting/graph_container') {
        $sec2 = 'godmode/reporting/graphs';
    } else if ($sec2 === 'operation/gis_maps/render_view') {
        $map_id = (int) get_parameter('map_id');
        if (empty($map_id) === false) {
            $sec2 .= '&map_id='.$map_id;
        }
    } else if ($sec2 === 'godmode/servers/discovery') {
        $wiz = (string) get_parameter('wiz');
        $sec2 = 'godmode/servers/discovery&wiz='.$wiz;
        $mode = (string) get_parameter('mode', '');
        if (empty($mode) === false) {
            $sec2 .= '&mode='.$mode;
        }
    } else if ($sec2 === 'godmode/groups/group_list') {
        $tab = (string) get_parameter('tab');
        if ($tab === 'credbox') {
            $sec2 = 'godmode/groups/group_list&tab='.$tab;
        }
    } else if ($sec2 === 'godmode/setup/setup') {
        $section = (string) get_parameter('section');
        $sec2 = 'godmode/setup/setup&section='.$section;
    } else if ($sec2 === 'godmode/massive/massive_operations') {
        $tab = (string) get_parameter('tab');
        $sec2 = 'godmode/massive/massive_operations&tab='.$tab;
    } else if ($sec2 === 'godmode/events/events') {
        $section = (string) get_parameter('section');
        $sec2 = 'godmode/events/events&section='.$section;
    } else if ($sec2 === 'operation/dashboard/dashboard') {
        $id = (int) get_parameter('dashboardId', 0);
        if (empty($id) === false) {
            $sec2 = 'operation/dashboard/dashboard&dashboardId='.$id;
        }
    } else if ($sec2 === 'enterprise/operation/services/services') {
        $tab = (string) get_parameter('tab', '');
        $action = (string) get_parameter('action', '');
        $id_service = (int) get_parameter('id_service', 0);
        if (empty($tab) === false
            && empty($action) === false
            && empty($id_service) === false
        ) {
            $sec2 = sprintf(
                'enterprise/operation/services/services&tab=%s&action=%s&id_service=%d',
                $tab,
                $action,
                $id_service
            );
        }
    } else if ($sec2 === 'operation/visual_console/render_view') {
        $id = (int) get_parameter('id', 0);
        if (empty($id) === false) {
            $sec2 = 'operation/visual_console/render_view&id='.$id;
        }
    } else if ($sec2 === 'operation/messages/message_edit') {
        $new_msg = (int) get_parameter('new_msg', 0);
        if (empty($new_msg) === false) {
            $sec2 = 'operation/messages/message_edit&new_msg='.$new_msg;
        }
    } else {
        $sec2 = (string) get_parameter('sec2');
    }

    $menu_selected = false;

    $allsec2 = explode('sec2=', $_SERVER['REQUEST_URI']);
    if (isset($allsec2[1]) === true) {
        $allsec2 = $allsec2[1];
    } else {
        $allsec2 = $sec2;
    }

    // Open list of menu.
    echo '<ul'.((isset($menu['class']) === true) ? ' class="'.$menu['class'].'"' : '').'>';

    // Use $config because a global var is required because normal
    // and godmode menu are painted separately.
    if (isset($config['count_main_menu']) === false) {
        $config['count_main_menu'] = 0;
    }

    foreach ($menu as $mainsec => $main) {
        $extensionInMenuParameter = (string) get_parameter('extension_in_menu');

        $showSubsection = true;
        if (empty($extensionInMenuParameter) === false) {
            $showSubsection = ($extensionInMenuParameter === $mainsec);
        }

        if ($mainsec === 'class') {
            continue;
        }

        $id = (isset($main['id']) === false) ? 'menu_'.(++$idcounter) : $main['id'];
        $submenu = false;
        $classes = [
            'menu_icon',
            ($menuTypeClass === 'classic') ? 'no_hidden_menu' : 'menu_icon_collapsed',
        ];

        if (isset($main['sub']) === true) {
            $classes[] = '';
            $submenu = true;
        }

        if (isset($main['refr']) === false) {
            $main['refr'] = 0;
        }

        if (($sec === $mainsec) && ((bool) $showSubsection === true)) {
            $classes[] = '';
        } else {
            $classes[] = '';
            if ($extensionInMenuParameter === $mainsec) {
                $classes[] = '';
            }
        }

        $output = '';

        if (! $submenu) {
            $main['sub'] = [];
            // Empty array won't go through foreach.
        }

        $submenu_output = '';

        $count_sub = 0;
        $count_sub_access = 0;
        $first_sub_sec2 = '';

        foreach ($main['sub'] as $subsec2 => $sub) {
            $count_sub++;

            // Init some variables.
            $visible = false;
            $selected = false;

            $subsec2 = io_safe_output($subsec2);
            // Choose valid suboptions (sec2).
            $check_2 = true;
            if (isset($sub['sub2'])) {
                $check_2 = false;
            }

            if (enterprise_hook('enterprise_acl', [$config['id_user'], $mainsec, $subsec2, $check_2]) == false) {
                continue;
            }

            // We store the first subsection to use it if the main section has not access.
            if ($count_sub_access == 0) {
                $first_sub_sec2 = $subsec2;
            }

            $count_sub_access++;

            $class = '';

            $selected_submenu2 = false;

            // Look for submenus in level2!
            if (isset($sub['sub2'])) {
                $class .= 'has_submenu ';

                // This hacks avoid empty delimiter error when sec2 is not provided.
                if (!$sec2) {
                    $sec2 = ' ';
                }

                // Check if some submenu was selected to mark this (the parent) as selected.
                foreach (array_keys($sub['sub2']) as $key) {
                    if (strpos($key, $sec2) !== false) {
                        $selected_submenu2 = true;
                        break;
                    }
                }
            }

            // Create godmode option if submenu has godmode on.
            if (isset($sub['subsecs'])) {
                // Sometimes you need to add all paths because in the
                // same dir are code from visual console and reports
                // for example.
                if (is_array($sub['subsecs'])) {
                    // Compare each string.
                    foreach ($sub['subsecs'] as $god_path) {
                        if (strpos($sec2, $god_path) !== false) {
                            $selected_submenu2 = true;
                            break;
                        }
                    }
                } else {
                    // If there is only a string just compare.
                    if (strpos($sec2, $sub['subsecs']) !== false) {
                        $selected_submenu2 = true;
                    }
                }
            }

            // Set class.
            if (($sec2 == $subsec2 || $allsec2 == $subsec2
                || $selected_submenu2) && isset($sub[$subsec2]['options'])
                && (get_parameter_get($sub[$subsec2]['options']['name']) == $sub[$subsec2]['options']['value'])
                && $main['id'] !== 'fav-menu'
            ) {
                // If the subclass is selected and there are options and that options value is true.
                $class .= 'submenu_selected selected';
                $menu_selected = true;
                $menu2_selected = $sub['id'];
                $selected = true;
                $visible = true;
            } else if (($sec2 === $subsec2 || $allsec2 === $subsec2 || $selected_submenu2 === true) && isset($sub[$subsec2]['options']) === false && $main['id'] !== 'fav-menu') {
                $class .= 'submenu_selected selected';
                $selected = true;
                $menu_selected = true;
                $menu2_selected = $sub['id'];
                $hasExtensions = (array_key_exists('hasExtensions', $main) === true) ? $main['hasExtensions'] : false;
                if ((empty($extensionInMenuParameter) === false) && ((bool) $hasExtensions === true)) {
                    $visible = true;
                } else {
                    $visible = false;
                }
            } else {
                // Else it's not selected.
                $class .= 'submenu_not_selected';
            }

            if (isset($sub['refr']) === false) {
                $sub['refr'] = 0;
            }

            // Define submenu class to draw tree image.
            $sub_tree_class = 'submenu_text';

            if (isset($sub['type']) && $sub['type'] == 'direct') {
                // This is an external link.
                $submenu_output .= '<li title="'.$sub['id'].'" id="'.str_replace(' ', '_', $sub['id']).'" class="'.$class.'">';

                if (isset($sub['subtype']) && $sub['subtype'] == 'nolink') {
                    $submenu_output .= '<div class=" SubNoLink '.$sub_tree_class.'"><span class="w70p span_has_menu_text">'.$sub['text'].'</span><div class="w21p arrow_menu_down"></div></div>';
                } else if (isset($sub['subtype']) && $sub['subtype'] == 'new_blank') {
                    $submenu_output .= '<a href="'.$subsec2.'" target="_blank"><div class="'.$sub_tree_class.'">'.$sub['text'].'</div></a>';
                } else {
                    $submenu_output .= '<a href="'.$subsec2.'"><div class="'.$sub_tree_class.'">'.$sub['text'].'</div></a>';
                }
            } else {
                // This is an internal link.
                if (isset($sub[$subsec2]['options'])) {
                    $link_add = '&amp;'.$sub[$subsec2]['options']['name'].'='.$sub[$subsec2]['options']['value'];
                } else {
                    $link_add = '';
                }

                $submenu_output .= '<li id="'.str_replace(' ', '_', $sub['id']).'" '.($class ? ' class="'.$class.'"' : '').'>';

                // Ini Add icon extension.
                $secExtension = null;
                if (array_key_exists('extension', $sub)) {
                    $secExtensionBool = $sub['extension'];
                } else {
                    $secExtensionBool = false;
                }

                // DISABLE SUBMENU IMAGES.
                $secExtensionBool = false;

                if ($secExtensionBool) {
                    if (strlen($sub['icon']) > 0) {
                        $icon_enterprise = false;
                        if (isset($sub['enterprise'])) {
                            $icon_enterprise = (bool) $sub['enterprise'];
                        }

                        if ($icon_enterprise) {
                            $imageIcon = 'enterprise/extensions/'.$sub['icon'];
                        } else {
                            $imageIcon = 'extensions/'.$sub['icon'];
                        }

                        if (!file_exists(realpath($imageIcon))) {
                            $imageIcon = $imageIconDefault;
                        }
                    } else {
                        $imageIcon = $imageIconDefault;
                    }

                    // $submenu_output .= '<div style="background: url('.$imageIcon.') no-repeat; width: 16px; height: 16px; float: left; margin: 5px 0px 0px 3px;">&nbsp;</div>';
                }

                $secExtension = null;
                if (array_key_exists('sec', $sub)) {
                    $secExtension = $sub['sec'];
                }

                if (strlen($secExtension) > 0) {
                    $secUrl = $secExtension;
                    $extensionInMenu = 'extension_in_menu='.$mainsec.'&amp;';
                } else {
                    $secUrl = $mainsec;
                    $extensionInMenu = '';
                }

                if (isset($sub['text']) || $selected) {
                    $title = ' title="'.$sub['text'].' "';
                } else {
                    $title = '';
                }

                $submenu_output .= '<a href="index.php?'.$extensionInMenu.'sec='.$secUrl.'&amp;'.'sec2='.$subsec2.($sub['refr'] ? '&amp;refr='.$sub['refr'] : '').$link_add.'"'.$title.'>'.'<div class="'.$sub_tree_class.'">'.$sub['text'].'</div>'.'</a>';
            }

            // Print second level submenu.
            if (isset($sub['sub2'])) {
                $submenu2_list = '';

                $count_sub2 = 0;
                foreach ($sub['sub2'] as $key => $sub2) {
                    if (enterprise_hook('enterprise_acl', [$config['id_user'], $mainsec, $subsec2, false, $key]) == false) {
                        continue;
                    }

                    $count_sub2++;

                    if (isset($sub2['type']) && $sub2['type'] == 'direct') {
                        if (isset($sub2['subtype']) && $sub2['subtype'] == 'new_blank') {
                            $link = $key.'"'.'target = \'_blank\'';
                        }
                    } else {
                        $link = 'index.php?sec='.$subsec2.'&sec2='.$key;
                    }

                    $class = 'sub_subMenu';

                    if ($key == $sec2 && $main['id'] !== 'fav-menu') {
                        $class .= ' selected';
                    }

                    // Define submenu2 class to draw tree image.
                    if ($count_sub2 >= count($sub['sub2'])) {
                        $sub_tree_class = 'submenu_text submenu2_text_last';
                    } else {
                        $sub_tree_class = 'submenu_text submenu2_text_middle';
                    }

                    if (isset($sub2['title'])) {
                        $sub_title = $sub2['title'];
                    } else {
                        $sub_title = $sub2['text'];
                    }

                    $submenu2_list .= '<li class="'.$class.'"  >';
                    $submenu2_list .= '<a href="'.$link.'"><div class="'.$sub_tree_class.'" title="'.$sub_title.'" >'.$sub2['text'].'</div></a></li>';
                    $sub_title = '';
                }

                // Add submenu2 to submenu string.
                $submenu_output .= '<ul id="sub'.str_replace(' ', '_', $sub['id']).'" class="submenu2">';
                $submenu_output .= $submenu2_list;
                $submenu_output .= '</ul>';
            }

            // Submenu close list!
            $submenu_output .= '</li>';
        }

        // Choose valid section (sec).
        if ((bool) enterprise_hook(
            'enterprise_acl',
            [
                $config['id_user'],
                $mainsec,
                ($main['sec2'] ?? null),
            ]
        ) === false
        ) {
            if ($count_sub_access > 0) {
                // If any susection have access but main section not, we change main link to first subsection found
                $main['sec2'] = $first_sub_sec2;
            } else {
                continue;
            }
        }

        if ($menu_selected) {
            $seleccionado = 'selected';
            $menu1_selected = $id;
            if ($menu['class'] === 'operation') {
                $tab_active = 'display';
            } else {
                $tab_active = 'management';
            }
        } else {
            $seleccionado = '';
        }

        // Print out the first level.
        $output .= '<li title="'.$main['text'].'" class="'.implode(' ', $classes).' '.$seleccionado.' flex_li" id="icon_'.$id.'">';
        if ($menuTypeClass === 'collapsed') {
            $div = '<div class="icon_'.$id.' w100p"></div><span class="w55p" style="display: none">'.$main['text'].'</span><div class="arrow_menu_down w30p" style="display: none"></div>';
        } else {
            if ($id === 'about') {
                $div = '<div class="icon_'.$id.' w15p"></div><span class="w55p">'.$main['text'].'</span>';
            } else {
                $div = '<div class="icon_'.$id.' w15p"></div><span class="w55p">'.$main['text'].'</span><div class="arrow_menu_down w30p"></div>';
            }
        }

        $output .= sprintf('<div id="title_menu" class="title_menu_classic">%s</div>', $div);

        // Add the notification ball if defined.
        if (isset($main['notification']) === true) {
            $output .= '<div class="notification_ball">'.$main['notification'].'</div>';
        }

        if ($submenu_output != '') {
            // WARNING: IN ORDER TO MODIFY THE VISIBILITY OF MENU'S AND SUBMENU'S (eg. with cookies) YOU HAVE TO ADD TO THIS ELSEIF. DON'T MODIFY THE CSS.
            if ($visible || in_array('selected', $classes)) {
                $visible = true;
            }

            if (!$showSubsection) {
                $visible = false;
            }

            $output .= sprintf('<ul id="subicon_%s" class="submenu">%s</ul>', $id, $submenu_output);
        }

        $config['count_main_menu']++;
        $output .= '</li>';
        echo $output;
        $menu_selected = false;
    }

    // Finish menu.
    echo '</ul>';
    // Invisible UL for adding border-top.
    echo '</div>';
}


/**
 * Get all the data structure of menu. Operation and Godmode
 *
 * @return array Menu structure.
 */
function menu_get_full_sec()
{
    global $menu_operation;
    global $menu_godmode;

    if ($menu_godmode == null || $menu_operation == null) {
        return [];
    } else {
        $menu = ($menu_operation + $menu_godmode);
    }

    unset($menu['class']);

    menu_add_extras($menu);

    return $menu;
}


/**
 * Build an extra access pages array and merge it with menu
 *
 * @param menu array (pass by reference)
 */
function menu_add_extras(&$menu)
{
    global $config;

    $menu_extra = [];
    $menu_extra['gusuarios']['sub']['godmode/users/configure_user']['text'] = __('Configure user');
    $menu_extra['gusuarios']['sub']['godmode/users/configure_profile']['text'] = __('Configure profile');

    $menu_extra['gmodules']['sub']['godmode/modules/manage_network_templates_form']['text'] = __('Module templates management');
    $menu_extra['gmodules']['sub']['godmode/modules/manage_inventory_modules_form']['text'] = __('Inventory modules management');

    $menu_extra['gagente']['sub']['godmode/agentes/configurar_agente']['text'] = __('Agents management');

    $menu_extra['galertas']['sub']['godmode/alerts/configure_alert_template']['text'] = __('Configure alert template');

    $menu_extra['network']['sub']['operation/visual_console/render_view']['text'] = __('View visual console');
    $menu_extra['network']['sub']['godmode/reporting/visual_console_builder']['text'] = __('Builder visual console');

    $menu_extra['reporting']['sub']['operation/reporting/reporting_viewer']['text'] = __('View reporting');
    $menu_extra['reporting']['sub']['operation/reporting/graph_viewer']['text'] = __('Graph viewer');

    $menu_extra['reporting']['sub']['godmode/reporting/graph_builder']['text'] = __('Manage custom graphs');
    $menu_extra['reporting']['sub']['godmode/reporting/graph_container']['text'] = __('View graph containers');
    $menu_extra['reporting']['sub']['godmode/reporting/create_container']['text'] = __('Manage graph containers');
    $menu_extra['reporting']['sub']['enterprise/godmode/reporting/graph_template_list']['text'] = __('View graph templates');
    $menu_extra['reporting']['sub']['enterprise/godmode/reporting/graph_template_editor']['text'] = __('Manage graph templates');
    $menu_extra['reporting']['sub']['enterprise/godmode/reporting/graph_template_item_editor']['text'] = __('Graph template items');
    $menu_extra['reporting']['sub']['enterprise/godmode/reporting/graph_template_wizard']['text'] = __('Graph template wizard');
    $menu_extra['reporting']['sub']['godmode/reporting/reporting_builder&tab=wizard&action=wizard']['text'] = __('Templates wizard');
    $menu_extra['reporting']['sub']['godmode/reporting/reporting_builder&tab=template&action=list_template']['text'] = __('Templates');
    $menu_extra['reporting']['sub']['godmode/reporting/reporting_builder&action=new']['text'] = __('New custom reports');
    $menu_extra['reporting']['sub']['godmode/reporting/reporting_builder&action=edit']['text'] = __('Edit custom reports');
    $menu_extra['reporting']['sub']['godmode/reporting/reporting_builder&action=delete_report']['text'] = __('Remove custom reports');
    $menu_extra['reporting']['sub']['godmode/reporting/reporting_builder&tab=list_items&action=edit']['text'] = __('List items');
    $menu_extra['reporting']['sub']['godmode/reporting/reporting_builder&tab=item_editor&action=new']['text'] = __('Edit item');
    $menu_extra['reporting']['sub']['godmode/reporting/reporting_builder&tab=wizard&action=edit']['text'] = __('Wizard');
    $menu_extra['reporting']['sub']['godmode/reporting/reporting_builder&tab=wizard_sla&action=edit']['text'] = __('Wizard sla');
    $menu_extra['reporting']['sub']['godmode/reporting/reporting_builder&tab=global&action=edit']['text'] = __('Global custom reports');
    $menu_extra['reporting']['sub']['godmode/reporting/reporting_builder&tab=advanced&action=edit']['text'] = __('Avanced options');

    if ($config['activate_gis']) {
        $menu_extra['godgismaps']['sub']['godmode/gis_maps/configure_gis_map']['text'] = __('Manage GIS Maps');
    }

    $menu_extra['workspace']['sub']['operation/messages/message_edit']['text'] = __('Manage messages');

    $menu_extra['gagente']['sub']['godmode/groups/configure_modu_group']['text'] = __('Manage module groups');
    $menu_extra['gagente']['sub']['godmode/agentes/configure_field']['text'] = __('Manage custom field');

    $menu_extra['galertas']['sub']['godmode/alerts/configure_alert_action']['text'] = __('Manage alert actions');
    $menu_extra['galertas']['sub']['godmode/alerts/configure_alert_command']['text'] = __('Manage commands');

    $menu_extra['gservers']['sub']['enterprise/godmode/servers/manage_export_form']['text'] = __('Manage export targets');

    $menu_extra['estado']['sub']['snmpconsole']['sub2']['godmode/snmpconsole/snmp_trap_generator']['text'] = __('SNMP trap generator');
    $menu_extra['estado']['sub']['snmpconsole']['sub2']['operation/snmpconsole/snmp_view']['text'] = __('SNMP console');

    $menu_extra['workspace']['sub']['operation/incidents/incident_detail']['text'] = __('Manage incident');

    $menu_extra['gusuarios']['sub']['godmode/groups/configure_group']['text'] = __('Manage groups');
    $menu_extra['gusuarios']['sub']['godmode/tag/edit_tag']['text'] = __('Tags management');

    // Duplicate extensions as sec=extension to check it from url.
    foreach ($menu as $k => $m) {
        if (!isset($m['sub'])) {
            continue;
        }

        foreach ($m['sub'] as $kk => $mm) {
            if (isset($mm['sec'])) {
                if (empty($menu[$mm['sec']]['sub'][$kk]['text']) === true) {
                    $menu_extra[$mm['sec']]['sub'][$kk]['text'] = $mm['text'];
                }
            }
        }
    }

    $menu = array_merge_recursive($menu, $menu_extra);

    // Remove the duplicate the text entries.
    foreach ($menu as $k => $m) {
        if (!empty($m['text'])) {
            if (is_array($m['text'])) {
                $menu[$k]['text'] = reset($m['text']);
            }
        }
    }
}


/**
 * Get the sec list built in menu
 *
 * @param bool If true, the array returned will have the structure
 * to combo categories (optgroup)
 *
 * @return array Sections list
 */
function menu_get_sec($with_categories=false)
{
    $menu = menu_get_full_sec();
    unset($menu['class']);

    $in_godmode = false;
    foreach ($menu as $k => $v) {
        if ($with_categories) {
            if (!$in_godmode && $k[0] == 'g') {
                // Hack to dont confuse with gis activated because godmode
                // sec starts with g (like gismaps).
                if ($k != 'gismaps') {
                    $in_godmode = true;
                }
            }

            if ($k === 'discovery') {
                $in_godmode = true;
            }

            if ($in_godmode) {
                $category = __('Administration');
            } else {
                $category = __('Operation');
            }

            $sec_array[$k]['optgroup'] = $category;
            $sec_array[$k]['name'] = $v['text'];
        } else {
            $sec_array[$k] = $v['text'];
        }
    }

    return $sec_array;
}


/**
 * Get the sec list built in menu
 *
 * @param bool If true, the array returned will have the structure
 * to combo categories (optgroup)
 *
 * @return array Sections list
 */
function get_sec($sec=false)
{
    $menu = menu_get_full_sec();
    unset($menu['class']);

    $in_godmode = false;
    foreach ($menu as $k => $v) {
        if (isset($v['sub'][$sec])) {
            return $k;
        }
    }

    return false;
}


/**
 * Get the pages in a section
 *
 * @param string sec code
 * @param string menu hash. All the menu structure (For example
 *         returned by menu_get_full_sec(), json encoded and after that
 *         base64 encoded. If this value is false this data is obtained from
 *         menu_get_full_sec();
 *
 * @return array Sections list
 */
function menu_get_sec_pages($sec, $menu_hash=false)
{
    if (!$menu_hash) {
        $menu = menu_get_full_sec();
    } else {
        $menu = json_decode(base64_decode($menu_hash), true);
    }

    $sec2_array = [];

    if (isset($sec)) {
        // Get the sec2 of the main section.
        $sec2_array[$menu[$sec]['sec2']] = $menu[$sec]['text'];

        // Get the sec2 of the subsections.
        foreach ($menu[$sec]['sub'] as $k => $v) {
            // Avoid special cases of standalone windows.
            if (preg_match('/^javascript:/', $k) || preg_match('/\.php/', $k)) {
                if ($sec !== 'links' && $sec !== 'eventos') {
                    continue;
                }
            }

            $sec2_array[$k] = $v['text'];
        }
    }

    $sec2_array = array_unique($sec2_array);
    return $sec2_array;
}


/**
 * Get the pages in a section2
 * $menu
 *
 * @param string sec code
 * @param string menu hash. All the menu structure (For example
 *         returned by menu_get_full_sec(), json encoded and after that
 *         base64 encoded. If this value is false this data is obtained from
 *         menu_get_full_sec();
 *
 * @return array Sections list
 */
function menu_get_sec2_pages($sec, $sec2, $menu_hash=false)
{
    if ($menu_hash === false) {
        $menu = menu_get_full_sec();
    } else {
        $menu = json_decode(base64_decode($menu_hash), true);
    }

    $sec3_array = [];
    $sec2 = io_safe_output($sec2);

    if (isset($menu[$sec]['sub']) && isset($menu[$sec]['sub'][$sec2]['sub2'])) {
        // Get the sec2 of the subsections.
        foreach ($menu[$sec]['sub'][$sec2]['sub2'] as $k => $v) {
            $sec3_array[$k] = $v['text'];
        }
    }

    return $sec3_array;
}


/**
 * Check if a page (sec2) is in a section (sec)
 *
 * @param string section (sec) code
 * @param string page (sec2)code
 *
 * @return true if the page is in section, false otherwise
 */
function menu_sec2_in_sec($sec, $sec2)
{
    $sec2_array = menu_get_sec_pages($sec);

    if ($sec2_array != null && in_array($sec2, array_keys($sec2_array))) {
        return true;
    }

    return false;
}


function menu_sec3_in_sec2($sec, $sec2, $sec3)
{
    $sec3_array = menu_get_sec2_pages($sec, $sec2, $menu_hash = false);

    if ($sec3_array != null && in_array($sec3, array_keys($sec3_array))) {
        return true;
    }

    return false;
}


/**
 * Prepare menu data for enterprise acl conf.
 *
 * @param  array  $pages
 * @param  string $sec
 * @return string $pages
 */
function menu_pepare_acl_select_data($pages, $sec)
{
    $exclude_pages = [
        'estado'     => 'operation/agentes/tactical',
        'network'    => 'operation/agentes/networkmap_list',
        'extensions' => [
            'operation/extensions',
            'enterprise/extensions/vmware',
            'extensions/users_connected',
        ],
        'gmodules'   => 'godmode/modules/manage_network_templates',
        'geventos'   => 'godmode/events/events&amp;section=filter',
        'gsetup'     => 'godmode/setup/setup&section=general',
    ];

    foreach ($exclude_pages as $exclude_sec => $sec2) {
        if ($sec === $exclude_sec) {
            if (is_array($sec2) === true) {
                foreach ($sec2 as $value) {
                    unset($pages[$value]);
                }
            }

            unset($pages[$sec2]);
        }
    }

    return $pages;
}


if (is_ajax()) {
    $about = (bool) get_parameter('about');
    if ($about) {
        global $config;
        global $pandora_version;
        global $build_version;
        $product_name = io_safe_output(get_product_name());
        $license_expiry_date = substr($config['license_expiry_date'], 0, 4).'/'.substr($config['license_expiry_date'], 4, 2).'/'.substr($config['license_expiry_date'], 6, 2);

        include_once $config['homedir'].'/include/class/Diagnostics.class.php';
        $d = new Diagnostics;
        $db_health = json_decode($d->getDatabaseHealthStatus());
        $db_info = json_decode($d->getDatabaseStatusInfo());
        $db_fragmentation = json_decode($d->getTablesFragmentation());
        $sys_info = json_decode($d->getSystemInfo());
        $php_sys = json_decode($d->getPHPSetup());
        $system_date = json_decode($d->getSystemDate());

        $lts_name = '';
        if (empty($config['lts_name']) === false) {
            $lts_name = ' <i>'.$config['lts_name'].'</i>';
        }

        $fragmentation_status = '';
        if ($db_fragmentation->data->tablesFragmentationStatus->status === 1) {
            $fragmentation_status = html_print_image(
                'images/validate.svg',
                true,
                [
                    'title' => __('Successfully'),
                    'style' => 'width:15px;',
                ]
            );
        } else {
            $fragmentation_status = html_print_image(
                'images/fail@svg.svg',
                true,
                [
                    'title' => __('Error'),
                    'style' => 'width:15px;',
                ]
            );
        }

        $image_about = ui_get_full_url('/images/custom_logo/logo-default-pandorafms.png', false, false, false);
        if (enterprise_installed() === false) {
            if ($config['style'] === 'pandora_black') {
                $image_about = 'images/custom_logo/'.HEADER_LOGO_BLACK_CLASSIC;
            } else if ($config['style'] === 'pandora') {
                $image_about = 'images/custom_logo/'.HEADER_LOGO_DEFAULT_CLASSIC;
            }
        } else {
            if ($config['style'] === 'pandora_black' && $config['custom_logo'] === HEADER_LOGO_DEFAULT_CLASSIC) {
                $config['custom_logo'] = HEADER_LOGO_BLACK_CLASSIC;
            } else if ($config['style'] === 'pandora' && $config['custom_logo'] === HEADER_LOGO_BLACK_CLASSIC) {
                $config['custom_logo'] = HEADER_LOGO_DEFAULT_CLASSIC;
            }

            $image_about = 'images/custom_logo/'.$config['custom_logo'];

            if (file_exists(ENTERPRISE_DIR.'/'.$image_about) === true) {
                $image_about = ENTERPRISE_DIR.'/'.$image_about;
            }
        }

        if (is_metaconsole() === true) {
            $image_about = ui_get_full_url('/enterprise/images/custom_logo/pandoraFMS_metaconsole_full.svg', false, false, false);

            if ($config['meta_custom_logo'] === 'pandoraFMS_metaconsole_full.svg') {
                $image_about = 'images/custom_logo/'.$config['meta_custom_logo'];
            } else {
                $image_about = '../images/custom_logo/'.$config['meta_custom_logo'];
            }

            if (file_exists(ENTERPRISE_DIR.'/'.$image_about) === true) {
                $image_about = $image_about;
            }
        }

        $dialog = '
            <div id="about-tabs" class="invisible overflow-hidden">
                <ul>
                    <li><a href="#tab-general-view">'.__('Information').'</a></li>
                    <li><a href="#tab-database">'.__('Database').'</a></li>
                    <li><a href="#tab-system-info">'.__('System Info').'</a></li>
                    <li><a href="#tab-php-system">'.__('PHP System').'</a></li>
                    <li class="ui-tabs-close-button" style="float:right!important;">
                        <img id="about-close" style="cursor: pointer;" src="'.ui_get_full_url('/include/styles/images/dialog-titlebar-close.png', false, false, false).'" alt="'.__('Close').'" title="'.__('Close').'" width="25px">
                    </li>
                </ul>
                <div id="tab-general-view">
                    <table class="table-about">
                        <tbody>
                            <tr>
                                <th style="width: 40%; border: 0px;">
                                    <img src="'.$image_about.'" alt="logo" width="70%">
                                </th>
                                <th style="width: 60%; text-align: left; border: 0px;">
                                    <h1>'.$product_name.'</h1>
                                    <p><span>'.__('Version').' '.$pandora_version.$lts_name.' - '.(enterprise_installed() ? 'Enterprise' : 'Community').'</span></p>
                                    <p><span>'.__('MR version').'</span> MR'.$config['MR'].'</p>
                                    <p><span>Build</span>'.$build_version.'</p>
                                    <p style="margin-bottom: 20px!important;"><span>'.__('Support expires').'</span>'.$license_expiry_date.'</p>';

        if (((bool) check_acl($config['id_user'], 0, 'PM') === true) && (is_metaconsole() === false)) {
            $dialogButtons = [];

            $dialogButtons[] = html_print_button(
                __('Update manager'),
                'update_manager',
                false,
                'location.href="'.ui_get_full_url('/index.php?sec=gsetup&sec2=godmode/update_manager/update_manager&tab=history', false, false, false).'"',
                [
                    'icon' => 'cog',
                    'mode' => 'secondary',
                ],
                true
            );

            $dialogButtons[] = html_print_button(
                __('System report'),
                'system_report',
                false,
                'location.href="'.ui_get_full_url('/index.php?sec=gextensions&sec2=tools/diagnostics', false, false, false).'"',
                [
                    'icon' => 'info',
                    'mode' => 'secondary',
                ],
                true
            );

            $dialog .= html_print_div(
                [
                    'style'   => 'flex-direction: row;',
                    'class'   => 'action-buttons',
                    'content' => implode('', $dialogButtons),
                ],
                true
            );
        }

        $dialog .= '</th>
                            </tr>
                        </tbody>
                    </table>
                    <p class="trademark-copyright">Trademark and copyright 2004 - '.date('Y').' Pandora FMS. All rights reserved</p>
                </div>
                <div id="tab-database" class="div-scroll">
                    <table class="table-about">
                        <tbody style="text-align: left;">
                            <tr>
                                <th colspan="2">
                                    <h2><span> - </span>'.__('Database health status').'</h2>
                                </th>
                            </tr>
                            <tr>
                                <th style="width: 50%;">
                                    <p><span>'.$db_health->data->unknownAgents->name.'</span></p>
                                </th>
                                <th style="width: 50%;">
                                    <p style="font-size: 10pt;">'.$db_health->data->unknownAgents->value.'</p>
                                </th>
                            </tr>
                            <tr>
                                <th style="width: 50%;">
                                    <p><span>'.$db_health->data->notInitAgents->name.'</span></p>
                                </th>
                                <th style="width: 50%;">
                                    <p style="font-size: 10pt;">'.$db_health->data->notInitAgents->value.'</p>
                                </th>
                            </tr>
                            <tr class="about-last-tr">
                                <th style="width: 50%;">
                                    <p class="about-last-p"><span>'.$db_health->data->pandoraDbLastRun->name.'</span></p>
                                </th>
                                <th style="width: 50%;">
                                    <p class="about-last-p" style="font-size: 10pt;">'.$db_health->data->pandoraDbLastRun->value.'</p>
                                </th>
                            </tr>

                            <tr>
                                <th colspan="2">
                                    <h2><span> - </span>'.__('Database status info').'</h2>
                                </th>
                            </tr>
                            <tr>
                                <th style="width: 50%;">
                                    <p><span>'.$db_info->data->dbSchemeFirstVersion->name.'</span></p>
                                </th>
                                <th style="width: 50%;">
                                    <p style="font-size: 10pt;">'.$db_info->data->dbSchemeFirstVersion->value.'</p>
                                </th>
                            </tr>
                            <tr>
                                <th style="width: 50%;">
                                    <p><span>'.$db_info->data->dbSchemeVersion->name.'</span></p>
                                </th>
                                <th style="width: 50%;">
                                    <p style="font-size: 10pt;">'.$db_info->data->dbSchemeVersion->value.'</p>
                                </th>
                            </tr>
                            <tr>
                                <th style="width: 50%;">
                                    <p><span>'.$db_info->data->dbSchemeBuild->name.'</span></p>
                                </th>
                                <th style="width: 50%;">
                                    <p style="font-size: 10pt;">'.$db_info->data->dbSchemeBuild->value.'</p>
                                </th>
                            </tr>
                            <tr class="about-last-tr">
                                <th style="width: 50%;">
                                    <p class="about-last-p"><span>'.$db_info->data->dbSize->name.'</span></p>
                                </th>
                                <th style="width: 50%;">
                                    <p class="about-last-p" style="font-size: 10pt;">'.$db_info->data->dbSize->value.'</p>
                                </th>
                            </tr>

                            <tr>
                                <th colspan="2">
                                    <h2><span> - </span>'.__('Tables fragmentation in the %s database', $product_name).'</h2>
                                </th>
                            </tr>
                            <tr>
                                <th style="width: 50%;">
                                    <p><span>'.$db_fragmentation->data->tablesFragmentationMax->name.'</span></p>
                                </th>
                                <th style="width: 50%;">
                                    <p style="font-size: 10pt;">'.$db_fragmentation->data->tablesFragmentationMax->value.'</p>
                                </th>
                            </tr>
                            <tr>
                                <th style="width: 50%;">
                                    <p><span>'.$db_fragmentation->data->tablesFragmentationValue->name.'</span></p>
                                </th>
                                <th style="width: 50%;">
                                    <p style="font-size: 10pt;">'.$db_fragmentation->data->tablesFragmentationValue->value.'</p>
                                </th>
                            </tr>
                            <tr>
                                <th style="width: 50%;">
                                    <p><span>'.$db_fragmentation->data->tablesFragmentationStatus->name.'</span></p>
                                </th>
                                <th style="width: 50%;">
                                    <p>'.$fragmentation_status.'</p>
                                </th>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div id="tab-system-info">
                    <table class="table-about">
                        <tbody style="text-align: left;">
                            <tr>
                                <th colspan="2">
                                    <h2><span> - </span>'.__('System Info').'</h2>
                                </th>
                            </tr>
                            <tr>
                                <th style="width: 30%;">
                                    <p><span>'.$sys_info->data->cpuInfo->name.'</span></p>
                                </th>
                                <th style="width: 85%;">
                                    <p style="font-size: 10pt;">'.$sys_info->data->cpuInfo->value.'</p>
                                </th>
                            </tr>
                            <tr>
                                <th style="width: 15%;">
                                    <p><span>'.$sys_info->data->ramInfo->name.'</span></p>
                                </th>
                                <th style="width: 85%;">
                                    <p style="font-size: 10pt;">'.$sys_info->data->ramInfo->value.'</p>
                                </th>
                            </tr>
                            <tr>
                                <th style="width: 15%;">
                                    <p><span>'.$sys_info->data->osInfo->name.'</span></p>
                                </th>
                                <th style="width: 85%;">
                                    <p style="font-size: 10pt;">'.$sys_info->data->osInfo->value.'</p>
                                </th>
                            </tr>
                            <tr>
                                <th style="width: 15%;">
                                    <p><span>'.$sys_info->data->hostnameInfo->name.'</span></p>
                                </th>
                                <th style="width: 85%;">
                                    <p style="font-size: 10pt;">'.$sys_info->data->hostnameInfo->value.'</p>
                                </th>
                            </tr>
                            <tr>
                                <th style="width: 15%;">
                                    <p><span>'.$sys_info->data->ipInfo->name.'</span></p>
                                </th>
                                <th style="width: 85%;">
                                    <p style="font-size: 10pt;">'.$sys_info->data->ipInfo->value.'</p>
                                </th>
                            </tr>
                            <tr>
                                <th style="width: 15%;">
                                    <p><span>'.$system_date->data->date->name.'</span></p>
                                </th>
                                <th style="width: 85%;">
                                    <p style="font-size: 10pt;">'.$system_date->data->date->value.'</p>
                                </th>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div id="tab-php-system">
                    <table class="table-about">
                        <tbody style="text-align: left;">
                            <tr>
                                <th colspan="2">
                                    <h2><span> - </span>'.__('PHP system').'</h2>
                                </th>
                            </tr>
                            <tr>
                                <th style="width: 50%;">
                                    <p><span>'.$php_sys->data->phpVersion->name.'</span></p>
                                </th>
                                <th style="width: 65%;">
                                    <p style="font-size: 10pt;">'.$php_sys->data->phpVersion->value.'</p>
                                </th>
                            </tr>
                            <tr>
                                <th style="width: 35%;">
                                    <p><span>'.$php_sys->data->maxExecutionTime->name.'</span></p>
                                </th>
                                <th style="width: 65%;">
                                    <p style="font-size: 10pt;">'.$php_sys->data->maxExecutionTime->value.'</p>
                                </th>
                            </tr>
                            <tr>
                                <th style="width: 35%;">
                                    <p><span>'.$php_sys->data->maxInputTime->name.'</span></p>
                                </th>
                                <th style="width: 65%;">
                                    <p style="font-size: 10pt;">'.$php_sys->data->maxInputTime->value.'</p>
                                </th>
                            </tr>
                            <tr>
                                <th style="width: 35%;">
                                    <p><span>'.$php_sys->data->memoryLimit->name.'</span></p>
                                </th>
                                <th style="width: 65%;">
                                    <p style="font-size: 10pt;">'.$php_sys->data->memoryLimit->value.'</p>
                                </th>
                            </tr>
                            <tr>
                                <th style="width: 35%;">
                                    <p><span>'.$php_sys->data->sessionLifetime->name.'</span></p>
                                </th>
                                <th style="width: 65%;">
                                    <p style="font-size: 10pt;">'.$php_sys->data->sessionLifetime->value.'</p>
                                </th>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        ';

        echo $dialog;
    }
}
