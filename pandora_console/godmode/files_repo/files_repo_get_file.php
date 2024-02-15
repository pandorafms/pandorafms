<?php
/**
 * Get public file repository.
 *
 * @category   Files repository
 * @package    Pandora FMS
 * @subpackage Enterprise
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2007-2023 Artica Soluciones Tecnologicas, http://www.artica.es
 * This code is NOT free software. This code is NOT licenced under GPL2 licence
 * You cannnot redistribute it without written permission of copyright holder.
 * ============================================================================
 */

require_once '../../include/config.php';

$file_hash = (string) get_parameter('file');

// Only allow 1 parameter in the request.
$check_request = (count($_REQUEST) === 1) ? true : false;
$check_get = (count($_GET) === 1) ? true : false;
$check_post = (count($_POST) === 0) ? true : false;

// Only allow the parameter 'file'.
$check_parameter = (empty($file_hash) === false) ? true : false;
$check_string = (preg_match('/^[0-9a-zA-Z]{8}$/', $file_hash) === 1) ? true : false;

$checks = ($check_request && $check_get && $check_post && $check_parameter && $check_string);
if (!$checks) {
    throw_error(15);
}

// Get the db file row.
$file = db_get_row_filter('tfiles_repo', ['hash' => $file_hash]);
if (!$file) {
    throw_error(10);
}

// Case sensitive check.
$check_hash = ($file['hash'] == $file_hash) ? true : false;
if (!$check_hash) {
    throw_error(10);
}

// Get the location.
$files_repo_path = io_safe_output($config['attachment_store']).'/files_repo';
$location = $files_repo_path.'/'.$file['id'].'_'.$file['name'];
if (!file_exists($location) || !is_readable($location) || !is_file($location)) {
    throw_error(5);
}

// All checks are fine. Download the file!
header('Content-type: aplication/octet-stream;');
header('Content-Length: '.filesize($location));
header('Content-Disposition: attachment; filename="'.$file['name'].'"');
readfile($location);


/**
 * Show errors
 *
 * @param integer $time Sleep.
 *
 * @return void
 */
function throw_error($time=15)
{
    sleep($time);

    $styleError = 'background:url("../images/err.png") no-repeat scroll 0 0 transparent; padding:4px 1px 6px 30px; color:#CC0000;';
    echo "<h3 style='".$styleError."'>".__('Unreliable petition').'. '.__('Please contact the administrator').'</h3>';
    exit;
}
