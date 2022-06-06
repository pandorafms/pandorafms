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
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2022 Artica Soluciones Tecnologicas
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
require_once 'config.php';
require_once 'functions.php';
require_once 'functions_filemanager.php';

global $config;

check_login();

$auth_method = db_get_value('value', 'tconfig', 'token', 'auth');

if ($auth_method !== 'ad' && $auth_method !== 'ldap') {
    include_once 'auth/'.$auth_method.'.php';
}


$styleError = 'background:url("../images/err.png") no-repeat scroll 0 0 transparent; padding:4px 1px 6px 30px; color:#CC0000;';

$file_raw = get_parameter('file', null);

$file = base64_decode(urldecode($file_raw));

$hash = get_parameter('hash', null);

if ($file === '' || $hash === '' || $hash !== md5($file_raw.$config['server_unique_identifier']) || !isset($_SERVER['HTTP_REFERER'])) {
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

            case 'godmode/servers/plugin':
                $downloadable_file = $_SERVER['DOCUMENT_ROOT'].'/pandora_console/attachment/plugin/'.$file;
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

    if (empty($downloadable_file) === true || file_exists($downloadable_file) === false) {
        ?>
            <div id="mainDiv"></div>
            <script type="text/javascript">
                var refererPath = '<?php echo $_SERVER['HTTP_REFERER']; ?>';
                var errorOutput = '<?php echo __('File is missing in disk storage. Please contact the administrator.'); ?>';
                document.addEventListener('DOMContentLoaded', function () {
                    document.getElementById('mainDiv').innerHTML = `<form action="` + refererPath + `" name="failedReturn" method="post" style="display:none;">
                        <input type="hidden" name="errorOutput" value="` + errorOutput + `" />
                        </form>`;

                    document.forms['failedReturn'].submit();
                }, false);
            </script>
        <?php
    } else {
        header('Content-type: aplication/octet-stream;');
        header('Content-type: '.mime_content_type($downloadable_file).';');
        header('Content-Length: '.filesize($downloadable_file));
        header('Content-Disposition: attachment; filename="'.basename($downloadable_file).'"');
        readfile($downloadable_file);
    }
}
