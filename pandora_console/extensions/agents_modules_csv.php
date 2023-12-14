<?php
/**
 * Export data.
 *
 * @category   Tools
 * @package    Pandora FMS
 * @subpackage Operation
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 * |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 * |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Pandora FMS
 * Please see https://pandorafms.com/community/ for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

global $config;
if ((bool) $config['metaconsole']) {
    include_once $config['homedir'].'/include/config.php';
    include_once $config['homedir'].'/include/functions_agents.php';
    include_once $config['homedir'].'/include/functions_reporting.php';
    include_once $config['homedir'].'/include/functions_modules.php';
    include_once $config['homedir'].'/include/functions_users.php';
} else {
    include_once __DIR__.'/../include/config.php';
    include_once __DIR__.'/../include/functions_agents.php';
    include_once __DIR__.'/../include/functions_reporting.php';
    include_once __DIR__.'/../include/functions_modules.php';
    include_once __DIR__.'/../include/functions_users.php';
}


check_login();

// ACL Check.
if (! check_acl($config['id_user'], 0, 'AR')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Agent view (Grouped)'
    );
    include 'general/noaccess.php';
    exit;
}


$get_agents_module_csv = get_parameter('get_agents_module_csv', 0);


if ($get_agents_module_csv === '1') {
    // ***************************************************
    // Header output
    // ***************************************************
    $config['ignore_callback'] = true;
    while (@ob_end_clean()) {
    }

    $filename = 'agents_module_view_'.date('Ymd').'-'.date('His');

    // Set cookie for download control.
    setDownloadCookieToken();

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="'.$filename.'.csv"');
    // ***************************************************
    // Data processing
    // ***************************************************
    echo pack('C*', 0xEF, 0xBB, 0xBF);

    $json_filters = get_parameter('filters', '');

    $filters = json_decode(
        base64_decode(
            get_parameter('filters', '')
        ),
        true
    );

    $results = export_agents_module_csv($filters);

    $divider = $config['csv_divider'];
    $dataend = PHP_EOL;

    $header_fields = [
        __('Agent'),
        __('Module'),
        __('Data'),
    ];

    $out_csv = '';
    foreach ($header_fields as $key => $value) {
        $out_csv .= $value.$divider;
    }

    $out_csv .= "\n";

    foreach ($results as $result) {
        foreach ($result as $key => $value) {
            if (preg_match('/Linux/i', $_SERVER['HTTP_USER_AGENT'])) {
                $value = preg_replace(
                    '/\s+/',
                    ' ',
                    io_safe_output($value)
                );
            } else {
                $value = mb_convert_encoding(
                    preg_replace(
                        '/\s+/',
                        '',
                        io_safe_output($value)
                    ),
                    'UTF-16LE',
                    'UTF-8'
                );
            }

            $out_csv .= $value.$divider;
        }

        $out_csv .= "\n";
    }

    echo io_safe_output($out_csv);

    exit;
}
