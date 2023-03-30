<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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

    $api_call = integria_api_call(null, null, null, null, $operation);
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


// Parse CSV consisting of one or more lines of the form key-value pair into an array.
function get_array_from_csv_data_pair($csv_data, &$array_values)
{
    $csv_array = explode("\n", $csv_data);

    foreach ($csv_array as $csv_value) {
        if (empty($csv_value)) {
            continue;
        }

        $new_csv_value = str_getcsv($csv_value);

        $array_values[$new_csv_value[0]] = $new_csv_value[1];
    }
}


/**
 * Parse CSV consisting of all lines into an array.
 *
 * @param string $csv_data     Data returned of csv api call.
 * @param string $array_values Returned array.
 * @param array  $index        Array to create an associative index (opcional).
 */
function get_array_from_csv_data_all($csv_data, &$array_values, $index=false)
{
    $csv_array = explode("\n", $csv_data);

    foreach ($csv_array as $csv_value) {
        if (empty($csv_value)) {
            continue;
        }

        $new_csv_value = str_getcsv($csv_value);

        if ($index !== false) {
            foreach ($new_csv_value as $key => $value) {
                $new_csv_value_index[$index[$key]] = str_replace(':::', ',', $value);
                ;
            }

            $array_values[$new_csv_value[0]] = $new_csv_value_index;
        } else {
            $new_csv_value_comma = array_map(
                function ($item) {
                    return str_replace(':::', ',', $item);
                },
                $new_csv_value
            );
            $array_values[$new_csv_value[0]] = $new_csv_value_comma;
        }
    }
}


/**
 * Print priority for Integria IMS with colors.
 *
 * @param string $priority       value of priority in Integria IMS.
 * @param string $priority_label text shown in color box.
 *
 * @return string HTML code.  code to print the color box.
 */
function ui_print_integria_incident_priority($priority, $priority_label)
{
    global $config;

    $output = '';
    switch ($priority) {
        case 0:
            $color = COL_UNKNOWN;
        break;

        case 1:
            $color = COL_NORMAL;
        break;

        case 10:
            $color = COL_NOTINIT;
        break;

        case 2:
            $color = COL_WARNING;
        break;

        case 3:
            $color = COL_ALERTFIRED;
        break;

        case 4:
            $color = COL_CRITICAL;
        break;
    }

    $output = '<div class="priority" style="background: '.$color.'">';
    $output .= $priority_label;
    $output .= '</div>';

    return $output;
}


/**
 * Get tickets from Integria IMS.
 *
 * @param array $tickets_filters Filters to send to API.
 *
 * @return array  Tickets returned by API call.
 */
function get_tickets_integriaims($tickets_filters)
{
    global $config;

    // Filters.
    $incident_text = $tickets_filters['incident_text'];
    $incident_status = $tickets_filters['incident_status'];
    $incident_group = $tickets_filters['incident_group'];
    $incident_owner = $tickets_filters['incident_owner'];
    $incident_creator = $tickets_filters['incident_creator'];
    $incident_priority = $tickets_filters['incident_priority'];
    $incident_resolution = $tickets_filters['incident_resolution'];
    $created_from = $tickets_filters['created_from'];
    $created_to = $tickets_filters['created_to'];

    // API call.
    $result_api_call_list = integria_api_call(
        null,
        null,
        null,
        null,
        'get_incidents',
        [
            $incident_text,
            $incident_status,
            $incident_group,
            $incident_priority,
            '0',
            $incident_owner,
            $incident_creator,
        ],
        false,
        '',
        ','
    );

    // Return array of api call 'get_incidents'.
    $array_get_incidents = [];
    get_array_from_csv_data_all($result_api_call_list, $array_get_incidents);

    // Modify $array_get_incidents if filter for resolution exists.
    $filter_resolution = [];
    foreach ($array_get_incidents as $key => $value) {
        if ($incident_resolution !== '' && ($array_get_incidents[$key][12] == $incident_resolution)) {
            $filter_resolution[$key] = $array_get_incidents[$key];
            continue;
        }
    }

    if ($incident_resolution !== '') {
        $array_get_incidents = $filter_resolution;
    }

    // Modify $array_get_incidents if filter for date is selected.
    if ($created_from !== '' && $created_to !== '') {
        $date = [];
        $date_utimestamp = [];
        foreach ($array_get_incidents as $key => $value) {
            // Change format date / to -.
            $date[$key] = date('Y-m-d', strtotime($array_get_incidents[$key][9]));
            // Covert date to utimestamp.
            $date_utimestamp[$key] = strtotime($date[$key]);
        }

        // Change format date / to -.
        $created_from_date = date('Y-m-d', strtotime($created_from));
        $created_to_date = date('Y-m-d', strtotime($created_to));

        // Covert date to utimestamp.
        $created_from_timestamp = strtotime($created_from_date);
        $created_to_timestamp = strtotime($created_to_date);

        // Dates within the selected period.
        $selected_period = array_filter(
            $date_utimestamp,
            function ($value) use ($created_from_timestamp, $created_to_timestamp) {
                return ($value >= $created_from_timestamp && $value <= $created_to_timestamp);
            }
        );

        // Return incidents with the correct dates.
        $filter_date = [];
        foreach ($array_get_incidents as $key => $value) {
            foreach ($selected_period as $index => $value) {
                if ($array_get_incidents[$key][0] == $index) {
                    $filter_date[$key] = $array_get_incidents[$key];
                    continue;
                }
            }
        }

        $array_get_incidents = $filter_date;
    }

    return $array_get_incidents;
}


function integriaims_upload_file($filename, $incident_id, $file_description)
{
    if ($_FILES[$filename]['name'] != '') {
        $filename = io_safe_input($_FILES[$filename]['name']);
        $filesize = io_safe_input($_FILES[$filename]['size']);

        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $invalid_extensions = '/^(bat|exe|cmd|sh|php|php1|php2|php3|php4|php5|pl|cgi|386|dll|com|torrent|js|app|jar|iso|
            pif|vb|vbscript|wsf|asp|cer|csr|jsp|drv|sys|ade|adp|bas|chm|cpl|crt|csh|fxp|hlp|hta|inf|ins|isp|jse|htaccess|
            htpasswd|ksh|lnk|mdb|mde|mdt|mdw|msc|msi|msp|mst|ops|pcd|prg|reg|scr|sct|shb|shs|url|vbe|vbs|wsc|wsf|wsh)$/i';

        if (!preg_match($invalid_extensions, $extension)) {
            // The following is if you have clamavlib installed.
            // (php5-clamavlib) and enabled in php.ini
            // http://www.howtoforge.com/scan_viruses_with_php_clamavlib
            if (extension_loaded('clamav')) {
                cl_setlimits(5, 1000, 200, 0, 10485760);
                $malware = cl_scanfile($_FILES['file']['tmp_name']);
                if ($malware) {
                    $error = 'Malware detected: '.$malware.'<br>ClamAV version: '.clam_get_version();
                    die($error);
                }
            }

            $filecontent = base64_encode(file_get_contents($_FILES[$filename]['tmp_name']));

            $result_api_call = integria_api_call(null, null, null, null, 'attach_file', [$incident_id, $filename, $filesize, $file_description, $filecontent], false, '', '|;|');

            // API method returns '0' string if success.
            $file_added = ($result_api_call === '0') ? true : false;

            ui_print_result_message(
                $file_added,
                __('File successfully added'),
                __('File could not be added')
            );
        } else {
            ui_print_error_message(__('File has an invalid extension'));
        }
    }
}
