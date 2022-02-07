<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
$get_sec_pages = get_parameter('get_sec_pages');
$get_sec_pages2 = get_parameter('get_sec_pages2');
require_once 'include/functions_menu.php';
require_once 'include/functions_html.php';

if ($get_sec_pages) {
    $sec = get_parameter('sec');
    $menu_hash = get_parameter('menu_hash');

    // WARNING: 'mobile' is a very special section
    if ($sec === 'mobile') {
        global $config;
        include_once $config['homedir'].'/mobile/operation/home.php';

        $home = new Home();
        $pagesItems = $home->getPagesItems();

        if (empty($pagesItems)) {
            $pagesItems = [];
        } else {
            ksort($pagesItems);
        }

        $pages = [];
        foreach ($pagesItems as $page => $data) {
            $pages[$page] = $data['name'];
        }
    } else {
        $pages = menu_get_sec_pages($sec, $menu_hash);
    }

    $pages = menu_pepare_acl_select_data($pages, $sec);

    echo json_encode($pages);
    return;
}

if ($get_sec_pages2) {
    $sec2 = get_parameter('sec2');
    $sec = get_parameter('sec');
    $menu_hash = get_parameter('menu_hash');

    $pages = menu_get_sec2_pages($sec, $sec2, $menu_hash);

    echo json_encode($pages);
    return;
}
