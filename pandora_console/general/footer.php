<?php

/**
 * Pandora FMS - http://pandorafms.com
 * ==================================================
 * Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
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

echo '<a class="footer" target="_blank" href="'.$config['homeurl'].$license_file.'">';

require_once $config['homedir'].'/include/functions_update_manager.php';

$current_package = update_manager_get_current_package();

if ($current_package == 0) {
    $build_package_version = $build_version;
} else {
    $build_package_version = $current_package;
}

echo __(
    '%s %s - Build %s - MR %s',
    get_product_name(),
    $pandora_version,
    $build_package_version,
    $config['MR']
);
echo '</a><br />';
echo '<small><span>'.__('Page generated on %s', date('Y-m-d H:i:s')).'</span></small>';



if (isset($config['debug'])) {
    $cache_info = [];
    $cache_info = db_get_cached_queries();
    echo ' - Saved '.$cache_info[0].' Queries';
}
