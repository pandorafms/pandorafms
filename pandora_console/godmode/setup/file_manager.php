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

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit('ACL Violation', 'Trying to access File manager');
    include 'general/noaccess.php';
    return;
}

require_once 'include/functions_filemanager.php';

// Header.
ui_print_page_header(__('File manager'), '', false, '', true);

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

// Banned directories.
$banned_directories['include']      = true;
$banned_directories['godmode']      = true;
$banned_directories['operation']    = true;
$banned_directories['reporting']    = true;
$banned_directories['general']      = true;
$banned_directories[ENTERPRISE_DIR] = true;

if (isset($banned_directories[$directory]) === true) {
    $directory = $fallback_directory;
}

$real_directory = realpath($config['homedir'].'/'.$directory);

echo '<h4>'.__('Index of %s', $directory).'</h4>';

$upload_file_or_zip = (bool) get_parameter('upload_file_or_zip');
$create_text_file   = (bool) get_parameter('create_text_file');

$default_real_directory = realpath($config['homedir'].'/');

if ($upload_file_or_zip === true) {
    upload_file($upload_file_or_zip, $default_real_directory);
}

if ($create_text_file === true) {
    create_text_file($default_real_directory);
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
    false
);
