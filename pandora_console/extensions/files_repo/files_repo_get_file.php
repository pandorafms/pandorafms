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
require_once '../../include/config.php';

$file_hash = (string) get_parameter('file');

// Only allow 1 parameter in the request
$check_request = (count($_REQUEST) === 1) ? true : false;
$check_get = (count($_GET) === 1) ? true : false;
$check_post = (count($_POST) === 0) ? true : false;
// Only allow the parameter 'file'
$check_parameter = (!empty($file_hash)) ? true : false;
$check_string = (preg_match('/^[0-9a-zA-Z]{8}$/', $file_hash) === 1) ? true : false;

$checks = ($check_request && $check_get && $check_post && $check_parameter && $check_string);
if (!$checks) {
    throw_error(15);
    // ERROR
}

// Get the db file row
$file = db_get_row_filter('tfiles_repo', ['hash' => $file_hash]);
if (!$file) {
    throw_error(10);
    // ERROR
}

// Case sensitive check
$check_hash = ($file['hash'] == $file_hash) ? true : false;
if (!$check_hash) {
    throw_error(10);
    // ERROR
}

// Get the location
$files_repo_path = io_safe_output($config['attachment_store']).'/files_repo';
$location = $files_repo_path.'/'.$file['id'].'_'.$file['name'];
if (!file_exists($location) || !is_readable($location) || !is_file($location)) {
    throw_error(5);
    // ERROR
}

// All checks are fine. Download the file!
header('Content-type: aplication/octet-stream;');
header('Content-Length: '.filesize($location));
header('Content-Disposition: attachment; filename="'.$file['name'].'"');
readfile($location);


function throw_error($time=15)
{
    sleep($time);

    $styleError = 'background:url("../images/err.png") no-repeat scroll 0 0 transparent; padding:4px 1px 6px 30px; color:#CC0000;';
    echo "<h3 style='".$styleError."'>".__('Unreliable petition').'. '.__('Please contact the administrator').'</h3>';
    exit;
}
