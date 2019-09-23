<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
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
require_once $config['homedir'].'/include/functions.php';


/**
 * Show header tabs.
 *
 * @param string $active_tab Current tab or id_incident.
 *
 * @return html Print tabs in header.
 */
function integriaims_tabs($active_tab=false)
{
    $url_tabs = ui_get_full_url('index.php?sec=incident&sec2=operation/incidents/');

    $setup_tab['text'] = '<a href="'.ui_get_full_url('index.php?sec=general&sec2=godmode/setup/setup&section=integria').'">'.html_print_image('images/setup.png', true, ['title' => __('Configure Integria IMS')]).'</a>';
    $list_tab['text'] = '<a href="'.$url_tabs.'list_integriaims_incidents">'.html_print_image('images/list.png', true, ['title' => __('List incidents')]).'</a>';
    $create_tab['text'] = '<a href="'.$url_tabs.'configure_integriaims_incident">'.html_print_image('images/pencil.png', true, ['title' => __('New incident')]).'</a>';

    if ($active_tab) {
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

            case is_numeric($active_tab):
                $create_tab['text'] = '<a href="'.$url_tabs.'configure_integriaims_incident&incident_id='.$active_tab.'">'.html_print_image('images/pencil.png', true, ['title' => __('Edit incident')]).'</a>';
                $view_tab['text'] = '<a href="'.$url_tabs.'dashboard_detail_integriaims_incident&incident_id='.$active_tab.'">'.html_print_image('images/operation.png', true, ['title' => __('View incident')]).'</a>';
                $setup_tab['active'] = false;
                $list_tab['active'] = false;
                $create_tab['active'] = false;
                $view_tab['active'] = true;
            break;

            default:
                $setup_tab['active'] = false;
                $list_tab['active'] = false;
                $create_tab['active'] = false;
            break;
        }
    } else {
        $setup_tab['active'] = false;
        $list_tab['active'] = false;
        $create_tab['active'] = false;
    }

    $onheader = [
        'view'      => $view_tab,
        'configure' => $setup_tab,
        'list'      => $list_tab,
        'create'    => $create_tab,
    ];

    return $onheader;
}


/**
 * Gets all the details of Integria IMS API
 *
 * @param string $details      Type of API call.
 * @param number $detail_index Send index if you want return the text.
 *
 * @return string or array with result of API call.
 */
function integriaims_get_details($details, $detail_index=false)
{
    global $config;

    switch ($details) {
        case 'status':
            $operation = 'get_incidents_status';
        break;

        case 'group':
            $operation = 'get_groups';
        break;

        case 'priority':
            $operation = 'get_incident_priorities';
        break;

        case 'resolution':
            $operation = 'get_incidents_resolutions';
        break;

        case 'type':
            $operation = 'get_types';
        break;

        default:
            // code...
        break;
    }

    $api_call = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], $operation);
    $result = [];
    get_array_from_csv_data_pair($api_call, $result);

    if ($detail_index !== false) {
        if ($result[$detail_index] == '' || $result[$detail_index] === null) {
            return __('None');
        } else {
            return $result[$detail_index];
        }
    } else {
        return $result;
    }
}
