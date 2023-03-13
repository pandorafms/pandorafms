<?php
/**
 * Images File Manager
 *
 * @category   File manager
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

// Get global data.
global $config;

check_login();

if ((bool) check_acl($config['id_user'], 0, 'PM') === false) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access File manager'
    );
    include 'general/noaccess.php';
    return;
}

require_once 'include/functions_filemanager.php';

// Header.
ui_print_standard_header(
    __('File manager'),
    '',
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
            'label' => __('File manager'),
        ],
    ]
);

if (isset($config['filemanager']['message']) === true) {
    echo $config['filemanager']['message'];
    $config['filemanager']['message'] = null;
}

// Add custom directories here.
$fallback_directory = 'images';
// Get directory.
$directory = (string) get_parameter('directory');
if (empty($directory) === true) {
    $directory = $fallback_directory;
} else {
    $directory = str_replace('\\', '/', $directory);
    $directory = filemanager_safe_directory($directory, $fallback_directory);
}

$real_directory = realpath($config['homedir'].'/'.$directory);

echo '<h4 class="mrgn_0px">'.__('Index of %s', io_safe_input($directory)).'</h4>';

$upload_file = (bool) get_parameter('upload_file');
$create_text_file = (bool) get_parameter('create_text_file');

$default_real_directory = realpath($config['homedir'].'/');

// Remove double dot in filename path.
$file_name = $_FILES['file']['name'];
$path_parts = explode('/', $file_name);

$stripped_parts = array_filter(
    $path_parts,
    function ($value) {
        return $value !== '..';
    }
);

$stripped_path = implode('/', $stripped_parts);
$_FILES['file']['name'] = $stripped_path;

if ($upload_file === true) {
    upload_file(
        $upload_file,
        $default_real_directory,
        $real_directory,
        [
            MIME_TYPES['jpg'],
            MIME_TYPES['png'],
            MIME_TYPES['gif'],
        ]
    );
}

if ($create_text_file === true) {
    create_text_file($default_real_directory, $real_directory);
}

filemanager_file_explorer(
    $real_directory,
    $directory,
    'index.php?sec=gsetup&sec2=godmode/setup/file_manager',
    '',
    false,
    false,
    '',
    false,
    '',
    false,
    false,
    []
);
