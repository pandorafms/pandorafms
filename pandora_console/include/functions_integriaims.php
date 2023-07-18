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


/**
 * Perform an API call to Integria IMS.
 *
 * @param string|null $api_hostname               API host URL.
 * @param string|null $user                       User name.
 * @param string|null $user_pass                  User password.
 * @param string|null $api_pass                   API password.
 * @param string|null $operation                  API Operation.
 * @param mixed       $params                     String or array with parameters required by the API function.
 * @param mixed       $show_credentials_error_msg Show_credentials_error_msg.
 * @param mixed       $return_type                Return_type.
 * @param mixed       $token                      Token.
 * @param mixed       $user_level_conf            User_level_conf.
 *
 * @return boolean True if API request succeeded, false if API request failed.
 */
function integria_api_call(
    $api_hostname=null,
    $user=null,
    $user_pass=null,
    $api_pass=null,
    $operation=null,
    $params='',
    $show_credentials_error_msg=false,
    $return_type='',
    $token='',
    $user_level_conf=null
) {
    global $config;

    if (is_metaconsole()) {
        $servers = metaconsole_get_connection_names();
        foreach ($servers as $key => $server) {
            $connection = metaconsole_get_connection($server);
            if (metaconsole_connect($connection) != NOERR) {
                continue;
            }

            $integria_enabled = db_get_sql(
                'SELECT `value` FROM tconfig WHERE `token` = "integria_enabled"'
            );

            if (!$integria_enabled) {
                metaconsole_restore_db();
                continue;
            }

            // integria_user_level_conf, integria_hostname, integria_api_pass, integria_user, integria_user_level_user, integria_pass, integria_user_level_pass
            $config_aux = db_get_all_rows_sql('SELECT `token`, `value` FROM `tconfig` WHERE `token` IN ("integria_user_level_conf", "integria_hostname", "integria_api_pass", "integria_user", "integria_user_level_user", "integria_pass", "integria_user_level_pass")');
            $user_info = users_get_user_by_id($config['id_user']);
            foreach ($config_aux as $key => $conf) {
                if ($conf['token'] === 'integria_user_level_conf') {
                    $user_level_conf = $conf['value'];
                }

                if ($conf['token'] === 'integria_hostname') {
                    $api_hostname = $conf['value'];
                }

                if ($conf['token'] === 'integria_api_pass') {
                    $api_pass = $conf['value'];
                }

                if ($conf['token'] === 'integria_user') {
                    $user = $conf['value'];
                }

                if ($conf['token'] === 'integria_pass') {
                    $user_pass = $conf['value'];
                }
            }

            if ($user_level_conf == true) {
                $user = $user_info['integria_user_level_user'];
                $user_pass = $user_info['integria_user_level_pass'];
            }

            metaconsole_restore_db();
        }
    } else {
        if ($user_level_conf === null) {
            $user_level_conf = (bool) $config['integria_user_level_conf'];
        }

        $user_info = users_get_user_by_id($config['id_user']);

        // API access data.
        if ($api_hostname === null) {
            $api_hostname = $config['integria_hostname'];
        }

        if ($api_pass === null) {
            $api_pass = $config['integria_api_pass'];
        }

        // Integria user and password.
        if ($user === null || $user_level_conf === true) {
            $user = $config['integria_user'];

            if ($user_level_conf === true) {
                $user = $user_info['integria_user_level_user'];
            }
        }

        if ($user_pass === null || $user_level_conf === true) {
            $user_pass = $config['integria_pass'];

            if ($user_level_conf === true) {
                $user_pass = $user_info['integria_user_level_pass'];
            }
        }
    }

    if (is_array($params)) {
        $params = implode($token, $params);
    }

    $url_data = [
        'user'      => $user,
        'user_pass' => $user_pass,
        'pass'      => $api_pass,
        'op'        => $operation,
        'params'    => io_safe_output($params),
    ];

    if ($return_type !== '') {
        $url_data['return_type'] = $return_type;
    }

    if ($token !== '') {
        $url_data['token'] = $token;
    }

    // Build URL for API request.
    $url = $api_hostname.'/include/api.php';

    // ob_start();
    // $out = fopen('php://output', 'w');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $url_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_STDERR, $out);
    $result = curl_exec($ch);

    // fclose($out);
    // $debug = ob_get_clean();
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    $error = false;

    if ($result === false) {
        $error = curl_error($ch);
    }

    curl_close($ch);

    if ($error === true || $http_status !== 200) {
        if ($show_credentials_error_msg === true) {
            ui_print_error_message(__('API request failed. Please check Integria IMS\' access credentials in Pandora setup.'));
        }

        return false;
    } else {
        return $result;
    }
}
