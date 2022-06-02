<?php

/**
 * Pandora FMS - http://pandorafms.com
 * ==================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

if (isset($_SERVER['REQUEST_TIME'])) {
    $time = $_SERVER['REQUEST_TIME'];
} else {
    $time = get_system_time();
}

ui_require_css_file('footer');

$license_file = 'general/license/pandora_info_'.$config['language'].'.html';
if (! file_exists($config['homedir'].$license_file)) {
    $license_file = 'general/license/pandora_info_en.html';
}

if (!$config['MR']) {
    $config['MR'] = 0;
}

if (isset($config['lts_name']) === false) {
    $config['lts_name'] = '';
}

echo '<a class="footer"target="_blank" href="'.$config['homeurl'].$license_file.'">';

require_once $config['homedir'].'/include/functions_update_manager.php';

$current_package = update_manager_get_current_package();

if ($current_package === null) {
    $build_package_version = 'Build '.$build_version;
} else {
    $build_package_version = 'OUM '.$current_package;
}

echo __(
    '%s %s - %s - MR %s',
    get_product_name(),
    $pandora_version.' '.$config['lts_name'],
    $build_package_version,
    $config['MR']
).'</a><br><span>'.__('Page generated on %s', date('Y-m-d H:i:s')).'</span><br>';



if (isset($config['debug'])) {
    $cache_info = [];
    $cache_info = db_get_cached_queries();
    echo ' - Saved '.$cache_info[0].' Queries';
}
