<?php
/**
 * MIB Uploader view
 *
 * @category   Monitoring
 * @package    Pandora FMS
 * @subpackage Community
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

check_login();

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access MIB uploader'
    );
    include 'general/noaccess.php';
    return;
}

require_once 'include/functions_filemanager.php';

// Header.
ui_print_standard_header(
    __('MIB uploader'),
    'images/op_snmp.png',
    false,
    '',
    false,
    [],
    [
        [
            'link'  => '',
            'label' => __('Monitoring'),
        ],
        [
            'link'  => '',
            'label' => __('SNMP'),
        ],
    ]
);


if (isset($config['filemanager']['message'])) {
    echo $config['filemanager']['message'];
    $config['filemanager']['message'] = null;
}

$directory = (string) get_parameter('directory');
$directory = str_replace('\\', '/', $directory);

// Add custom directories here.
$fallback_directory = SNMP_DIR_MIBS;

if (empty($directory) === true) {
    $directory = $fallback_directory;
} else {
    $directory = str_replace('\\', '/', $directory);
    $directory = filemanager_safe_directory($directory, $fallback_directory);
}

$real_directory = realpath($config['homedir'].'/'.$directory);

ui_print_info_message(__('MIB files will be installed on the system. Please note that a MIB may depend on other MIB. To customize trap definitions use the SNMP trap editor.'));

$upload_file_or_zip = (bool) get_parameter('upload_file_or_zip');
$create_text_file = (bool) get_parameter('create_text_file');

$default_real_directory = realpath($config['homedir'].'/'.$fallback_directory);

if ($upload_file_or_zip === true) {
    upload_file($upload_file_or_zip, $default_real_directory, $real_directory);
}

if ($create_text_file === true) {
    create_text_file($default_real_directory, $real_directory);
}

filemanager_file_explorer(
    $real_directory,
    $directory,
    'index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_mib_uploader',
    SNMP_DIR_MIBS,
    false,
    false,
    '',
    false,
    '',
    false,
    [
        'all'               => true,
        'denyCreateText'    => true,
        'allowCreateFolder' => true,
    ]
);
