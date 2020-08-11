<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// Don't start a session before this import.
// The session is configured and started inside the config process.
require_once 'config.php';
require_once 'functions.php';
require_once 'functions_filemanager.php';

global $config;

check_login();

$auth_method = db_get_value('value', 'tconfig', 'token', 'auth');

if ($auth_method != 'ad' && $auth_method != 'ldap') {
    include_once 'auth/'.$auth_method.'.php';
}


$styleError = 'background:url("../images/err.png") no-repeat scroll 0 0 transparent; padding:4px 1px 6px 30px; color:#CC0000;';

$file_raw = get_parameter('file', null);

$file = base64_decode(urldecode($file_raw));

$hash = get_parameter('hash', null);

if ($file === '' || $hash === '' || $hash !== md5($file_raw.$config['dbpass']) || !isset($_SERVER['HTTP_REFERER'])) {
    echo "<h3 style='".$styleError."'>".__('Security error. Please contact the administrator.').'</h3>';
} else {
    $downloadable_file = '';
    $parse_all_queries = explode('&', parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY));
    $parse_sec2_query = explode('=', $parse_all_queries[1]);
    // Metaconsole have a route distinct than node.
    $main_file_manager = (is_metaconsole() === true) ? 'advanced/metasetup' : 'godmode/setup/file_manager';
    $main_collections = (is_metaconsole() === true) ? 'advanced/collections' : 'enterprise/godmode/agentes/collections';
    if ($parse_sec2_query[0] === 'sec2') {
        switch ($parse_sec2_query[1]) {
            case $main_file_manager:
            case 'operation/snmpconsole/snmp_mib_uploader':
                $downloadable_file = $_SERVER['DOCUMENT_ROOT'].'/pandora_console/'.$file;
            break;

            case 'extensions/files_repo':
                $downloadable_file = $_SERVER['DOCUMENT_ROOT'].'/pandora_console/attachment/files_repo/'.$file;
            break;

            case $main_collections:
                $downloadable_file = $_SERVER['DOCUMENT_ROOT'].'/pandora_console/attachment/collection/'.$file;
            break;

            default:
                $downloadable_file = '';
                // Do nothing
            break;
        }
    }

    if ($downloadable_file === '' || !file_exists($downloadable_file)) {
        echo "<h3 style='".$styleError."'>".__('File is missing in disk storage. Please contact the administrator.').'</h3>';
    } else {
        header('Content-type: aplication/octet-stream;');
        header('Content-type: '.mime_content_type($downloadable_file).';');
        header('Content-Length: '.filesize($downloadable_file));
        header('Content-Disposition: attachment; filename="'.basename($downloadable_file).'"');
        readfile($downloadable_file);
    }
}
