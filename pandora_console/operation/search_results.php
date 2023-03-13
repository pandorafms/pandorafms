<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
global $config;
require_once $config['homedir'].'/include/functions_reporting.php';

// Load enterprise extensions
enterprise_include('operation/reporting/custom_reporting.php');

$searchAgents = $searchAlerts = $searchModules = check_acl($config['id_user'], 0, 'AR');
$searchUsers = (check_acl($config['id_user'], 0, 'AR'));
$searchPolicies = (check_acl($config['id_user'], 0, 'AR') && enterprise_installed());
$searchReports = $searchGraphs = check_acl($config['id_user'], 0, 'RR');
$searchMaps = check_acl($config['id_user'], 0, 'VR');
$searchMain = true;
$searchHelps = true;

$arrayKeywords = explode('&#x20;', $config['search_keywords']);

$temp = [];
foreach ($arrayKeywords as $keyword) {
    // Remember, $keyword is already pass a safeinput filter.
    array_push($temp, '%'.$keyword.'%');
}

$stringSearchSQL = implode('&#x20;', $temp);
$stringSearchSQL = str_replace('_', '\_', $stringSearchSQL);

if ($config['search_category'] == 'all') {
    $searchTab = 'main';
} else {
    $searchTab = $config['search_category'];
}

// INI SECURITY ACL
if ((!$searchAgents && !$searchUsers && !$searchMaps)
    || (!$searchUsers && $searchTab == 'users')
    || (!$searchPolicies && $searchTab == 'policies')
    || (!$searchAgents && ($searchTab == 'agents' || $searchTab == 'alerts'))
    || (!$searchGraphs && ($searchTab == 'graphs' || $searchTab == 'maps' || $searchTab == 'reports'))
) {
    $searchTab = '';
}

// END SECURITY ACL
$offset = get_parameter('offset', 0);
$order = null;

$sortField = get_parameter('sort_field');
$sort = get_parameter('sort', 'none');
$selected = 'border: 1px solid black;';

if ($searchMain) {
    $main_tab = [
        'text'   => "<a href='index.php?search_category=main&keywords=".$config['search_keywords']."&head_search_keywords=Search'>".html_print_image(
            'images/zoom_mc.png',
            true,
            ['title' => __('Global search')]
        ).'</a>',
        'active' => $searchTab == 'main',
    ];
} else {
    $main_tab = '';
}

if ($searchAgents) {
    $agents_tab = [
        'text'   => "<a href='index.php?search_category=agents&keywords=".$config['search_keywords']."&head_search_keywords=Search'>".html_print_image(
            'images/op_monitoring.png',
            true,
            ['title' => __('Agents')]
        ).'</a>',
        'active' => $searchTab == 'agents',
    ];
} else {
    $agents_tab = '';
}

if ($searchUsers) {
    $users_tab = [
        'text'   => "<a href='index.php?search_category=users&keywords=".$config['search_keywords']."&head_search_keywords=Search'>".html_print_image(
            'images/op_workspace.png',
            true,
            ['title' => __('Users')]
        ).'</a>',
        'active' => $searchTab == 'users',
    ];
} else {
    $users_tab = '';
}

if ($searchAlerts) {
    $alerts_tab = [
        'text'   => "<a href='index.php?search_category=alerts&keywords=".$config['search_keywords']."&head_search_keywords=Search'>".html_print_image(
            'images/op_alerts.png',
            true,
            ['title' => __('Alerts')]
        ).'</a>',
        'active' => $searchTab == 'alerts',
    ];
} else {
    $alerts_tab = '';
}

if ($searchGraphs) {
    $graphs_tab = [
        'text'   => "<a href='index.php?search_category=graphs&keywords=".$config['search_keywords']."&head_search_keywords=Search'>".html_print_image(
            'images/chart.png',
            true,
            ['title' => __('Graphs')]
        ).'</a>',
        'active' => $searchTab == 'graphs',
    ];
} else {
    $graphs_tab = '';
}

if ($searchReports) {
    $reports_tab = [
        'text'   => "<a href='index.php?search_category=reports&keywords=".$config['search_keywords']."&head_search_keywords=Search'>".html_print_image(
            'images/op_reporting.png',
            true,
            ['title' => __('Reports')]
        ).'</a>',
        'active' => $searchTab == 'reports',
    ];
} else {
    $reports_tab = '';
}

if ($searchMaps) {
    $maps_tab = [
        'text'   => "<a href='index.php?search_category=maps&keywords=".$config['search_keywords']."&head_search_keywords=Search'>".html_print_image(
            'images/visual_console.png',
            true,
            ['title' => __('Visual consoles')]
        ).'</a>',
        'active' => $searchTab == 'maps',
    ];
} else {
    $maps_tab = '';
}

if ($searchModules) {
    $modules_tab = [
        'text'   => "<a href='index.php?search_category=modules&keywords=".$config['search_keywords']."&head_search_keywords=Search'>".html_print_image(
            'images/brick.png',
            true,
            ['title' => __('Modules')]
        ).'</a>',
        'active' => $searchTab == 'modules',
    ];
} else {
    $modules_tab = '';
}

if ($searchPolicies) {
    $policies_tab = [
        'text'   => "<a href='index.php?search_category=policies&keywords=".$config['search_keywords']."&head_search_keywords=Search'>".html_print_image(
            'images/policies_mc.png',
            true,
            ['title' => __('Policies')]
        ).'</a>',
        'active' => $searchTab == 'policies',
    ];
} else {
    $policies_tab = '';
}

$onheader = [
    'main'     => $main_tab,
    'agents'   => $agents_tab,
    'modules'  => $modules_tab,
    'alerts'   => $alerts_tab,
    'users'    => $users_tab,
    'graphs'   => $graphs_tab,
    'reports'  => $reports_tab,
    'maps'     => $maps_tab,
    'policies' => $policies_tab,
];

ui_print_standard_header(
    __('Search').': "'.$config['search_keywords'].'"',
    'images/zoom_mc.png',
    false,
    '',
    false,
    $onheader,
    [
        [
            'link'  => '',
            'label' => __('Search'),
        ],
    ]
);

$only_count = false;
switch ($searchTab) {
    case 'main':
        $only_count = true;

        include_once 'search_agents.getdata.php';
        include_once 'search_agents.php';
        include_once 'search_users.getdata.php';

        // ------------------- DISABLED FOR SOME INSTALLATIONS----------
        // ~ require_once('search_alerts.getdata.php');
        // -------------------------------------------------------------
        include_once 'search_graphs.getdata.php';
        include_once 'search_reports.getdata.php';
        include_once 'search_maps.getdata.php';
        include_once 'search_modules.getdata.php';
        include_once 'search_helps.getdata.php';
        include_once 'search_policies.getdata.php';

        include_once 'search_main.php';
    break;

    case 'agents':
        include_once 'search_agents.getdata.php';
        include_once 'search_agents.php';
    break;

    case 'users':
        include_once 'search_users.getdata.php';
        include_once 'search_users.php';
    break;

    case 'alerts':
        include_once 'search_alerts.getdata.php';
        include_once 'search_alerts.php';
    break;

    case 'graphs':
        include_once 'search_graphs.getdata.php';
        include_once 'search_graphs.php';
    break;

    case 'reports':
        include_once 'search_reports.getdata.php';
        include_once 'search_reports.php';
    break;

    case 'maps':
        include_once 'search_maps.getdata.php';
        include_once 'search_maps.php';
    break;

    case 'modules':
        include_once 'search_modules.getdata.php';
        include_once 'search_modules.php';
    break;

    case 'policies':
        include_once 'search_policies.getdata.php';
        include_once 'search_policies.php';

    break;
}
