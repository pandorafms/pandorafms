<?php
/**
 * Get File script
 *
 * @category   File manager
 * @package    Pandora FMS
 * @subpackage Community
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

// Begin.
require_once 'config.php';
require_once 'functions.php';
require_once 'functions_ui.php';
require_once 'functions_filemanager.php';

global $config;

check_login();

$auth_method = db_get_value('value', 'tconfig', 'token', 'auth');

if ($auth_method !== 'ad' && $auth_method !== 'ldap') {
    include_once 'auth/'.$auth_method.'.php';
}

$hash = get_parameter('hash');
$file_raw = get_parameter('file');

$file = base64_decode(urldecode($file_raw));
$secure_extension = true;
$extension = pathinfo($file, PATHINFO_EXTENSION);
if ($extension === 'php' || $extension === 'js') {
    $secure_extension = false;
}

$parse_all_queries = explode('&', parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY));
$parse_sec2_query = explode('=', $parse_all_queries[1]);
$dirname = dirname($file);

$path_traversal = strpos($file, '../');

// Avoid possible inifite loop with referer.
if (isset($_SERVER['HTTP_ORIGIN']) === false || (isset($_SERVER['HTTP_ORIGIN']) === true && $_SERVER['HTTP_REFERER'] === $_SERVER['HTTP_ORIGIN'].$_SERVER['REQUEST_URI'])) {
    $refererPath = ui_get_full_url('index.php');
} else {
    $refererPath = $_SERVER['HTTP_REFERER'];
}

if (empty($file) === true || empty($hash) === true || $hash !== md5($file_raw.$config['server_unique_identifier'])
    || isset($_SERVER['HTTP_REFERER']) === false || $path_traversal !== false || $secure_extension === false
) {
    $errorMessage = __('Security error. Please contact the administrator.');
} else {
    $downloadable_file = '';

    // Metaconsole have a route distinct than node.
    $main_file_manager = (is_metaconsole() === true) ? 'advanced/metasetup' : 'godmode/setup/file_manager';
    $main_collections = (is_metaconsole() === true) ? 'advanced/collections' : 'enterprise/godmode/agentes/collections';
    if ($parse_sec2_query[0] === 'sec2') {
        switch ($parse_sec2_query[1]) {
            case $main_file_manager:
            case 'operation/snmpconsole/snmp_mib_uploader':
                $downloadable_file = $_SERVER['DOCUMENT_ROOT'].'/pandora_console/'.$file;
            break;

            case 'godmode/files_repo/files_repo':
                $attachment_path = io_safe_output($config['attachment_store']);
                $downloadable_file = $attachment_path.'/files_repo/'.$file;
            break;

            case 'godmode/servers/plugin':
                $downloadable_file = $_SERVER['DOCUMENT_ROOT'].'/pandora_console/attachment/plugin/'.$file;
            break;

            case $main_collections:
                $downloadable_file = $_SERVER['DOCUMENT_ROOT'].'/pandora_console/attachment/collection/'.$file;
            break;

            case 'godmode/setup/file_manager':
                $downloadable_file = ($dirname === 'image') ? $_SERVER['DOCUMENT_ROOT'].'/pandora_console/'.$file : '';

            default:
                // Wrong action.
                $downloadable_file = '';
            break;
        }
    }

    if (empty($downloadable_file) === true || file_exists($downloadable_file) === false) {
        $errorMessage = __('File is missing in disk storage. Please contact the administrator.');
    } else {
        // Everything went well.
        header('Content-type: aplication/octet-stream;');
        header('Content-type: '.mime_content_type($downloadable_file).';');
        header('Content-Length: '.filesize($downloadable_file));
        header('Content-Disposition: attachment; filename="'.basename($downloadable_file).'"');
        readfile($downloadable_file);
        return;
    }
}

?>

<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {
        var refererPath = '<?php echo $refererPath; ?>';
        var errorFileOutput = '<?php echo $errorMessage; ?>';
        if(refererPath != ''){
        document.body.innerHTML = `<form action="` + refererPath + `" name="failedReturn" method="post" style="display:none;">
                    <input type="hidden" name="errorFileOutput" value="` + errorFileOutput + `" />
                    </form>`;

        document.forms['failedReturn'].submit();
        }
    }, false);
</script>
