<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
function view_logfile($file_name, $toggle=false)
{
    global $config;

    $memory_limit = ini_get('memory_limit');
    $code = '';

    if (strstr($memory_limit, 'M') !== false) {
        $memory_limit = str_replace('M', '', $memory_limit);
        $memory_limit = ($memory_limit * 1024 * 1024);

        // Arbitrary size for the PHP program
        $memory_limit = ($memory_limit - (8 * 1024 * 1024));
    }

    if (!file_exists($file_name)) {
        ui_print_error_message(__('Cannot find file').'('.$file_name.')');
    } else {
        $file_size = filesize($file_name);

        if ($memory_limit < $file_size) {
            $code .= '<pre><h2>'.$file_name.' ('.__('File is too large than PHP memory allocated in the system.').')</h2>';
            $code .= '<h2>'.__('The preview file is imposible.').'</h2>';
        } else if ($file_size > ($config['max_log_size'] * 1000)) {
            $data = file_get_contents($file_name, false, null, ($file_size - ($config['max_log_size'] * 1000)));
            $code .= "<h2>$file_name (".format_numeric(filesize($file_name) / 1024).' KB) '.ui_print_help_tip(__('The folder /var/log/pandora must have pandora:apache and its content too.'), true).' </h2>';
            $code .= "<textarea class='pandora_logs' name='$file_name'>";
            $code .= '... ';
            $code .= $data;
            $code .= '</textarea><br><br>';
        } else {
            $data = file_get_contents($file_name);
            $code .= "<h2>$file_name (".format_numeric(filesize($file_name) / 1024).' KB) '.ui_print_help_tip(__('The folder /var/log/pandora must have pandora:apache and its content too.'), true).' </h2>';
            $code .= "<textarea class='pandora_logs' name='$file_name'>";
            $code .= $data;
            $code .= '</textarea><br><br></pre>';
        }

        if ($toggle === true) {
            ui_toggle(
                $code,
                '<span class="subsection_header_title">'.$file_name.'</span>',
                $file_name,
                'a',
                false,
                false,
                '',
                'white-box-content no_border',
                'filter-datatable-main box-flat white_table_graph'
            );
        } else {
            echo $code;
        }
    }
}


function pandoralogs_extension_main()
{
    global $config;

    if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access Setup Management'
        );
        include 'general/noaccess.php';
        return;
    }

    // Header.
    ui_print_standard_header(
        __('Extensions'),
        'images/extensions.png',
        false,
        '',
        true,
        [],
        [
            [
                'link'  => '',
                'label' => __('Admin tools'),
            ],
            [
                'link'  => '',
                'label' => __('Extension manager'),
            ],
            [
                'link'  => '',
                'label' => __('System logfile viewer'),
            ],
        ]
    );

    ui_print_info_message(
        __('Use this tool to view your %s logfiles directly on the console', get_product_name()).'<br>
        '.__('You can choose the amount of information shown in general setup (Log size limit in system logs viewer extension), '.($config['max_log_size'] * 1000).'B at the moment')
    );

    $logs_directory = (!empty($config['server_log_dir'])) ? io_safe_output($config['server_log_dir']) : '/var/log/pandora';

    // Do not attempt to show console log if disabled.
    if ($config['console_log_enabled']) {
        view_logfile($config['homedir'].'/log/console.log', true);
    }

    view_logfile($logs_directory.'/pandora_server.log', true);
    view_logfile($logs_directory.'/pandora_server.error', true);

}


extensions_add_godmode_menu_option(__('System logfiles'), 'PM', '', null, 'v1r1');
extensions_add_godmode_function('pandoralogs_extension_main');
