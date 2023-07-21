<?php

// Pandora FMS - https://pandorafms.com
// ==================================================
// Copyright (c) 2005-2023 Pandora FMS
// Please see https://pandorafms.com/community/ for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package    Include
 * @subpackage Incidents
 */

global $config;

require_once $config['homedir'].'/include/functions_ui.php';
require_once $config['homedir'].'/include/functions_html.php';
require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions.php';


/**
 * Show header tabs.
 *
 * @param string $active_tab Current tab or false for View page.
 * @param number $view       Id of incident. Show View tab.
 *
 * @return array HTML code. Print tabs in header.
 */
function integriaims_tabs($active_tab, $view=false)
{
    global $config;

    $url_tabs = ui_get_full_url('index.php?sec=incident&sec2=operation/incidents/');

    $setup_tab['text'] = '<a href="'.ui_get_full_url('index.php?sec=general&sec2=godmode/setup/setup&section=integria').'">'.html_print_image('images/configuration@svg.svg', true, ['title' => __('Configure Integria IMS'), 'class' => 'main_menu_icon invert_filter']).'</a>';
    $list_tab['text'] = '<a href="'.$url_tabs.'list_integriaims_incidents">'.html_print_image('images/logs@svg.svg', true, ['title' => __('Ticket list'), 'class' => 'main_menu_icon invert_filter']).'</a>';
    $create_tab['text'] = '<a href="'.$url_tabs.'configure_integriaims_incident">'.html_print_image('images/edit.svg', true, ['title' => __('New ticket'), 'class' => 'main_menu_icon invert_filter']).'</a>';

    switch ($active_tab) {
        case 'setup_tab':
            $setup_tab['active'] = true;
            $list_tab['active'] = false;
            $create_tab['active'] = false;
        break;

        case 'list_tab':
            $setup_tab['active'] = false;
            $list_tab['active'] = true;
            $create_tab['active'] = false;
        break;

        case 'create_tab':
            $setup_tab['active'] = false;
            $list_tab['active'] = false;
            $create_tab['active'] = true;
        break;

        default:
            $setup_tab['active'] = false;
            $list_tab['active'] = false;
            $create_tab['active'] = false;
        break;
    }

    if ($view) {
        $create_tab['text'] = '<a href="'.$url_tabs.'configure_integriaims_incident&incident_id='.$view.'">'.html_print_image('images/edit.svg', true, ['title' => __('Edit ticket'), 'class' => 'main_menu_icon invert_filter']).'</a>';
        $view_tab['text'] = '<a href="'.$url_tabs.'dashboard_detail_integriaims_incident&incident_id='.$view.'">'.html_print_image('images/details.svg', true, ['title' => __('View ticket'), 'class' => 'main_menu_icon invert_filter']).'</a>';
        // When the current page is the View page.
        if (!$active_tab) {
            $view_tab['active'] = true;
        }
    }

    $onheader = [];
    $onheader['view'] = $view_tab;
    $onheader['configure'] = $setup_tab;
    $onheader['list'] = $list_tab;
    $onheader['create'] = $create_tab;

    return $onheader;
}
