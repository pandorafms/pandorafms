<?php
/**
 * Operation menu.
 *
 * @category   Menu
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
 * Copyright (c) 2005-2023 Artica Soluciones Tecnologicas
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

// Begin.
if (! isset($config['id_user'])) {
    return;
}

use PandoraFMS\Dashboard\Manager;

require_once 'include/functions_menu.php';
require_once $config['homedir'].'/include/functions_visual_map.php';
require_once 'include/class/MenuItem.class.php';

enterprise_include('operation/menu.php');

$menu_operation = [];
$menu_operation['class'] = 'operation';
/*
    $menuOperation = [];

    $subMenuMonitoring = [];


    $subMenuMonitoringViews = [];

    // L0. Monitoring.
    $menuOperation['monitoring'] = new MenuItem(__('Monitoring'));
    $menuOperation['monitoring']->setIcon('icon');
    $menuOperation['monitoring']->setClass('menu');
    $menuOperation['monitoring']->setACL(['AR']);
    $monitoringItems = [];

    // L1. Views.
    $monitoringItems['views'] = new MenuItem(__('Views'));
    $monitoringItems['views']->setIcon('icono');
    $monitoringItems['views']->setClass('submenu');
    $monitoringViewsItems = [];

    // L2. Tactical view.
    $monitoringViewsItems['tacticalView'] = new MenuItem(__('Tactical view'));
    $monitoringViewsItems['tacticalView']->setSec('view');
    $monitoringViewsItems['tacticalView']->setSec2('operation/agentes/tactical');
    $monitoringViewsItems['tacticalView']->setClass('submenu');

    // L2. Group View.
    $monitoringViewsItems['groupView'] = new MenuItem(__('Group view'));
    $monitoringViewsItems['groupView']->setSec('view');
    $monitoringViewsItems['groupView']->setSec2('operation/agentes/group_view');
    $monitoringViewsItems['groupView']->setClass('submenu');

    // L2. Tree View.
    $monitoringViewsItems['treeView'] = new MenuItem(__('Tree view'));
    $monitoringViewsItems['treeView']->setSec('view');
    $monitoringViewsItems['treeView']->setSec2('operation/tree');
    $monitoringViewsItems['treeView']->setClass('submenu');

    // L2. Monitor detail.
    $monitoringViewsItems['monitorDetail'] = new MenuItem(__('Monitor detail'));
    $monitoringViewsItems['monitorDetail']->setSec('view');
    $monitoringViewsItems['monitorDetail']->setSec2('operation/agentes/status_monitor');
    $monitoringViewsItems['monitorDetail']->setClass('submenu');

    // L2. Interface view.
    $monitoringViewsItems['interfaceView'] = new MenuItem(__('Interface View'));
    $monitoringViewsItems['interfaceView']->setSec('view');
    $monitoringViewsItems['interfaceView']->setSec2('operation/agentes/interface_view');
    $monitoringViewsItems['interfaceView']->setClass('submenu');

    // L2. Enterprise Tag view.
    $idTagView = 'tagView';
    $monitoringViewsItems[$idTagView] = enterprise_hook('tag_view_submenu', $idTagView);

    // L2. Alert detail view.
    $monitoringViewsItems['alertDetail'] = new MenuItem(__('Alert Detail'));
    $monitoringViewsItems['alertDetail']->setSec('view');
    $monitoringViewsItems['alertDetail']->setSec2('operation/agentes/alerts_status');
    $monitoringViewsItems['alertDetail']->setClass('submenu');

    // L2. Heatmap view.
    $monitoringViewsItems['heatmapView'] = new MenuItem(__('Heatmap view'));
    $monitoringViewsItems['heatmapView']->setSec('view');
    $monitoringViewsItems['heatmapView']->setSec2('operation/heatmap');
    $monitoringViewsItems['heatmapView']->setClass('submenu');

    $monitoringItems['views']->setSubmenu($monitoringViewsItems);

    // L1. Inventory.
    $monitoringItems['inventory'] = new MenuItem(__('Inventory'));
    $monitoringItems['inventory']->setSec('estado');
    $monitoringItems['inventory']->setSec2('enterprise/operation/inventory/inventory');
    $monitoringItems['inventory']->setClass('submenu');

    // L1. Network.
    $monitoringItems['network'] = new MenuItem();
    $monitoringItems['network']->setDisplay((bool) $config['activate_netflow'] === true);
    $monitoringItems['network']->setText(__('Network'));

    // L2. Netflow explorer.
    $monitoringNetworkItems['netflowExplorer'] = new MenuItem();
    $monitoringNetworkItems['netflowExplorer']->setText(__('Netflow Explorer'));
    $monitoringNetworkItems['netflowExplorer']->setSec('network_traffic');
    $monitoringNetworkItems['netflowExplorer']->setSec2('operation/netflow/netflow_explorer');

    // L2. Netflow Live view.
    $monitoringNetworkItems['netflowLiveView'] = new MenuItem();
    $monitoringNetworkItems['netflowLiveView']->setText(__('Netflow Live View'));
    $monitoringNetworkItems['netflowLiveView']->setSec('network_traffic');
    $monitoringNetworkItems['netflowLiveView']->setSec2('operation/netflow/nf_live_view');

    // L2. Network usage map.
    $monitoringNetworkItems['networkUsageMap'] = new MenuItem();
    $monitoringNetworkItems['networkUsageMap']->setText(__('Network usage map'));
    $monitoringNetworkItems['networkUsageMap']->setSec('network_traffic');
    $monitoringNetworkItems['networkUsageMap']->setSec2('operation/network/network_usage_map');

    $monitoringItems['network']->setSubmenu($monitoringNetworkItems);

    $menuOperation['monitoring']->setSubmenu($monitoringItems);

    // L0. Topology Maps.
    $menuOperation['topologyMaps'] = new MenuItem(__('Topology Maps'));
    $menuOperation['topologyMaps']->setIcon('icon');
    $menuOperation['topologyMaps']->setClass('menu');

    $menuOperation['topologyMaps']->setSubmenu($topologyMapsItems);
*/

$access_console_node = !is_reporting_console_node();
if ($access_console_node === true) {
    // Agent read, Server read.
    if (check_acl($config['id_user'], 0, 'AR')) {
        // View agents.
        $menu_operation['estado']['text'] = __('Monitoring');
        $menu_operation['estado']['sec2'] = 'operation/agentes/tactical';
        $menu_operation['estado']['refr'] = 0;
        $menu_operation['estado']['id'] = 'oper-agents';

        $sub = [];
        $sub['view']['text'] = __('Views');
        $sub['view']['id'] = 'Views';
        $sub['view']['type'] = 'direct';
        $sub['view']['subtype'] = 'nolink';
        $sub['view']['refr'] = 0;

        $sub2 = [];

        $sub2['operation/agentes/tactical']['text'] = __('Tactical view');
        $sub2['operation/agentes/tactical']['refr'] = 0;

        $sub2['operation/agentes/group_view']['text'] = __('Group view');
        $sub2['operation/agentes/group_view']['refr'] = 0;

        $sub2['operation/tree']['text'] = __('Tree view');
        $sub2['operation/tree']['refr'] = 0;

        $sub2['operation/agentes/estado_agente']['text'] = __('Agent detail');
        $sub2['operation/agentes/estado_agente']['refr'] = 0;
        $sub2['operation/agentes/estado_agente']['subsecs'] = ['operation/agentes/ver_agente'];

        $sub2['operation/agentes/status_monitor']['text'] = __('Monitor detail');
        $sub2['operation/agentes/status_monitor']['refr'] = 0;

        $sub2['operation/agentes/interface_view']['text'] = __('Interface view');
        $sub2['operation/agentes/interface_view']['refr'] = 0;

        enterprise_hook('tag_view_submenu');

        $sub2['operation/agentes/alerts_status']['text'] = __('Alert detail');
        $sub2['operation/agentes/alerts_status']['refr'] = 0;

        $sub2['operation/heatmap']['text'] = __('Heatmap view');
        $sub2['operation/heatmap']['refr'] = 0;

        $sub['view']['sub2'] = $sub2;

        if (check_acl($config['id_user'], 0, 'AR') || check_acl($config['id_user'], 0, 'AW')) {
            $sub['operation/inventory/inventory']['text'] = __('Inventory');
            $sub['operation/inventory/inventory']['id'] = 'Inventory';
            $sub['operation/inventory/inventory']['refr'] = 0;
        }

        if ($config['activate_netflow'] || $config['activate_sflow']) {
            $sub['network_traffic'] = [
                'text'    => __('Network'),
                'id'      => 'Network',
                'type'    => 'direct',
                'subtype' => 'nolink',
                'refr'    => 0,
            ];
            $netflow_sub = [
                'operation/netflow/netflow_explorer'  => [
                    'text' => __('Netflow explorer'),
                    'id'   => 'Netflow explorer',
                ],
                'operation/netflow/nf_live_view'      => [
                    'text' => __('Netflow Live View'),
                    'id'   => 'Netflow Live View',
                ],
                'operation/network/network_usage_map' => [
                    'text' => __('Network usage map'),
                    'id'   => 'Network usage map',
                ],
            ];
            $sub['network_traffic']['sub2'] = $netflow_sub;
        }

        if ($config['log_collector'] == 1) {
            enterprise_hook('log_collector_menu');
        }

        // End of view agents.
    }

    // SNMP Console.
    $sub2 = [];
    if (check_acl($config['id_user'], 0, 'AR') || check_acl($config['id_user'], 0, 'AW')) {
        $sub2['operation/snmpconsole/snmp_view']['text'] = __('SNMP console');
        $sub2['operation/snmpconsole/snmp_browser']['text'] = __('SNMP browser');
        enterprise_hook('snmpconsole_submenu');
    }

    if (check_acl($config['id_user'], 0, 'PM')) {
        $sub2['operation/snmpconsole/snmp_mib_uploader']['text'] = __('MIB uploader');
    }

    if (check_acl($config['id_user'], 0, 'LW') || check_acl($config['id_user'], 0, 'LM')) {
        $sub2['godmode/snmpconsole/snmp_filters']['text'] = __('SNMP filters');
        $sub2['godmode/snmpconsole/snmp_trap_generator']['text'] = __('SNMP trap generator');
    }

    if (empty($sub2) === false) {
        $sub['snmpconsole']['sub2'] = $sub2;
        $sub['snmpconsole']['text'] = __('SNMP');
        $sub['snmpconsole']['id'] = 'SNMP';
        $sub['snmpconsole']['refr'] = 0;
        $sub['snmpconsole']['type'] = 'direct';
        $sub['snmpconsole']['subtype'] = 'nolink';
    }

    if (check_acl($config['id_user'], 0, 'AR')) {
        $sub['operation/cluster/cluster']['text'] = __('Cluster View');
        $sub['operation/cluster/cluster']['id'] = 'cluster';
        $sub['operation/cluster/cluster']['refr'] = 0;
    }

    enterprise_hook('aws_menu');
    enterprise_hook('SAP_view');

    if (!empty($sub)) {
        $menu_operation['estado']['text'] = __('Monitoring');
        $menu_operation['estado']['sec2'] = 'operation/agentes/tactical';
        $menu_operation['estado']['refr'] = 0;
        $menu_operation['estado']['id'] = 'oper-agents';
        $menu_operation['estado']['sub'] = $sub;
    }

    // Start network view.
    $sub = [];
    if (check_acl($config['id_user'], 0, 'MR') || check_acl($config['id_user'], 0, 'MW') || check_acl($config['id_user'], 0, 'MM')) {
        // Network enterprise.
        $sub['operation/agentes/pandora_networkmap']['text'] = __('Network map');
        $sub['operation/agentes/pandora_networkmap']['id'] = 'Network_map';
        $sub['operation/agentes/pandora_networkmap']['refr'] = 0;
    }

    enterprise_hook('services_menu');


    if (check_acl($config['id_user'], 0, 'VR') || check_acl($config['id_user'], 0, 'VW') || check_acl($config['id_user'], 0, 'VM')) {
        $url_visual_console = '';
        if (!isset($config['vc_favourite_view']) || $config['vc_favourite_view'] == 0) {
            // Visual console.
            $sub['godmode/reporting/map_builder']['text'] = __('Visual console');
            $sub['godmode/reporting/map_builder']['id'] = 'Visual_console';
            $sub['godmode/reporting/map_builder']['type'] = 'direct';
            $sub['godmode/reporting/map_builder']['subtype'] = 'nolink';
            $url_visual_console = 'godmode/reporting/map_builder';
        } else {
            // Visual console favorite.
            $sub['godmode/reporting/visual_console_favorite']['text'] = __('Visual console');
            $sub['godmode/reporting/visual_console_favorite']['id'] = 'Visual_console';
            $sub['godmode/reporting/visual_console_favorite']['type'] = 'direct';
            $sub['godmode/reporting/visual_console_favorite']['subtype'] = 'nolink';
            $url_visual_console = 'godmode/reporting/visual_console_favorite';
        }

        if ($config['vc_menu_items'] != 0) {
            // Set godomode path.
            if (!isset($config['vc_favourite_view']) || $config['vc_favourite_view'] == 0) {
                $sub['godmode/reporting/map_builder']['subsecs'] = [
                    'godmode/reporting/map_builder',
                    'godmode/reporting/visual_console_builder',
                ];
            } else {
                $sub['godmode/reporting/visual_console_favorite']['subsecs'] = [
                    'godmode/reporting/map_builder',
                    'godmode/reporting/visual_console_builder',
                ];
            }

            // $layouts = db_get_all_rows_in_table ('tlayout', 'name');
            $own_info = get_user_info($config['id_user']);
            $returnAllGroups = 0;
            if ($own_info['is_admin']) {
                $returnAllGroups = 1;
            }

            $layouts = visual_map_get_user_layouts($config['id_user'], false, false, $returnAllGroups, true);
            $sub2 = [];

            $sub2[$url_visual_console] = [
                'text'  => __('Visual console list'),
                'title' => __('Visual console list'),
                'refr'  => 0,
            ];

            if ($layouts === false) {
                $layouts = [];
            } else {
                $id = (int) get_parameter('id', -1);
                $delete_layout = (bool) get_parameter('delete_layout');

                if ($delete_layout === true) {
                    $id_layout = (int) get_parameter('id_layout');
                    unset($layouts[$id_layout]);
                }

                $break_max_console = false;
                $max = $config['vc_menu_items'];
                $i = 0;
                foreach ($layouts as $layout) {
                    $i++;
                    if ($i > $max) {
                        $break_max_console = true;
                        break;
                    }

                    $name = io_safe_output($layout['name']);

                    $sub2['operation/visual_console/render_view&id='.$layout['id']]['text'] = ui_print_truncate_text($name, MENU_SIZE_TEXT, false, true, false);
                    $sub2['operation/visual_console/render_view&id='.$layout['id']]['id'] = mb_substr($name, 0, 19);
                    $sub2['operation/visual_console/render_view&id='.$layout['id']]['title'] = $name;
                    if (!empty($config['vc_refr'])) {
                        $sub2['operation/visual_console/render_view&id='.$layout['id']]['refr'] = $config['vc_refr'];
                    } else if (((int) get_parameter('refr', 0)) > 0) {
                        $sub2['operation/visual_console/render_view&id='.$layout['id']]['refr'] = (int) get_parameter('refr', 0);
                    } else {
                        $sub2['operation/visual_console/render_view&id='.$layout['id']]['refr'] = 0;
                    }
                }

                if ($break_max_console) {
                    $sub2['godmode/reporting/visual_console_favorite']['text']  = __('Show more').' >';
                    $sub2['godmode/reporting/visual_console_favorite']['id']    = 'visual_favourite_console';
                    $sub2['godmode/reporting/visual_console_favorite']['title'] = __('Show more');
                    $sub2['godmode/reporting/visual_console_favorite']['refr']  = 0;
                }

                if (!empty($sub2)) {
                    if (!isset($config['vc_favourite_view']) || $config['vc_favourite_view'] == 0) {
                        $sub['godmode/reporting/map_builder']['sub2'] = $sub2;
                    } else {
                        $sub['godmode/reporting/visual_console_favorite']['sub2'] = $sub2;
                    }
                }
            }
        }
    }

    if (check_acl($config['id_user'], 0, 'MR') || check_acl($config['id_user'], 0, 'MW') || check_acl($config['id_user'], 0, 'MM')) {
        // INI GIS Maps.
        if ($config['activate_gis']) {
            $sub['gismaps']['text'] = __('GIS Maps');
            $sub['gismaps']['id'] = 'GIS_Maps';
            $sub['gismaps']['type'] = 'direct';
            $sub['gismaps']['subtype'] = 'nolink';
            $sub2 = [];
            $sub2['operation/gis_maps/gis_map']['text'] = __('List of Gis maps');
            $sub2['operation/gis_maps/gis_map']['id'] = 'List of Gis maps';
            $gisMaps = db_get_all_rows_in_table('tgis_map', 'map_name');
            if ($gisMaps === false) {
                $gisMaps = [];
            }

            $id = (int) get_parameter('id', -1);

            $own_info = get_user_info($config['id_user']);
            if ($own_info['is_admin'] || check_acl($config['id_user'], 0, 'PM')) {
                $own_groups = array_keys(users_get_groups($config['id_user'], 'MR'));
            } else {
                $own_groups = array_keys(users_get_groups($config['id_user'], 'MR', false));
            }

            foreach ($gisMaps as $gisMap) {
                $is_in_group = in_array($gisMap['group_id'], $own_groups);
                if (!$is_in_group) {
                    continue;
                }

                $sub2['operation/gis_maps/render_view&map_id='.$gisMap['id_tgis_map']]['text'] = ui_print_truncate_text(io_safe_output($gisMap['map_name']), MENU_SIZE_TEXT, false, true, false);
                $sub2['operation/gis_maps/render_view&map_id='.$gisMap['id_tgis_map']]['id'] = mb_substr(io_safe_output($gisMap['map_name']), 0, 15);
                $sub2['operation/gis_maps/render_view&map_id='.$gisMap['id_tgis_map']]['title'] = io_safe_output($gisMap['map_name']);
                $sub2['operation/gis_maps/render_view&map_id='.$gisMap['id_tgis_map']]['refr'] = 0;
            }

            $sub['gismaps']['sub2'] = $sub2;
        }

        // END GIS Maps.
    }

    if (!empty($sub)) {
        $menu_operation['network']['text'] = __('Topology maps');
        $menu_operation['network']['sec2'] = 'operation/agentes/networkmap_list';
        $menu_operation['network']['refr'] = 0;
        $menu_operation['network']['id'] = 'oper-networkconsole';
        $menu_operation['network']['sub'] = $sub;
    }

    // End networkview.
    // Reports read.
    if (check_acl($config['id_user'], 0, 'RR') || check_acl($config['id_user'], 0, 'RW') || check_acl($config['id_user'], 0, 'RM')) {
        // Reporting.
        $menu_operation['reporting']['text'] = __('Reporting');
        $menu_operation['reporting']['sec2'] = 'godmode/reporting/reporting_builder';
        $menu_operation['reporting']['id'] = 'oper-reporting';
        $menu_operation['reporting']['refr'] = 300;

        $sub = [];

        $sub['godmode/reporting/reporting_builder']['text'] = __('Custom reporting');
        $sub['godmode/reporting/reporting_builder']['id'] = 'Custom_reporting';
        // Set godomode path.
        $sub['godmode/reporting/reporting_builder']['subsecs'] = [
            'godmode/reporting/reporting_builder',
            'operation/reporting/reporting_viewer',
        ];


        $sub['godmode/reporting/graphs']['text'] = __('Custom graphs');
        $sub['godmode/reporting/graphs']['id'] = 'Custom_graphs';
        // Set godomode path.
        $sub['godmode/reporting/graphs']['subsecs'] = [
            'operation/reporting/graph_viewer',
            'godmode/reporting/graph_builder',
        ];

        if (check_acl($config['id_user'], 0, 'RR')
            || check_acl($config['id_user'], 0, 'RW')
            || check_acl($config['id_user'], 0, 'RM')
        ) {
            $sub['operation/dashboard/dashboard']['text'] = __('Dashboard');
            $sub['operation/dashboard/dashboard']['id'] = 'Dashboard';
            $sub['operation/dashboard/dashboard']['refr'] = 0;
            $sub['operation/dashboard/dashboard']['subsecs'] = ['operation/dashboard/dashboard'];
            $sub['operation/dashboard/dashboard']['type'] = 'direct';
            $sub['operation/dashboard/dashboard']['subtype'] = 'nolink';

            $dashboards = Manager::getDashboards(-1, -1, true);

            $sub2 = [];
            $sub2['operation/dashboard/dashboard'] = [
                'text'  => __('Dashboard list'),
                'title' => __('Dashboard list'),
            ];
            foreach ($dashboards as $dashboard) {
                $name = io_safe_output($dashboard['name']);

                $sub2['operation/dashboard/dashboard&dashboardId='.$dashboard['id']] = [
                    'text'  => ui_print_truncate_text($name, MENU_SIZE_TEXT, false, true, false),
                    'title' => $name,
                ];
            }

            if (empty($sub2) === false) {
                $sub['operation/dashboard/dashboard']['sub2'] = $sub2;
            }
        }

        enterprise_hook('reporting_godmenu');

        $menu_operation['reporting']['sub'] = $sub;
        // End reporting.
    }

    // Events reading.
    if (check_acl($config['id_user'], 0, 'ER')
        || check_acl($config['id_user'], 0, 'EW')
        || check_acl($config['id_user'], 0, 'EM')
    ) {
        // Events.
        $menu_operation['eventos']['text'] = __('Events');
        $menu_operation['eventos']['refr'] = 0;
        $menu_operation['eventos']['sec2'] = 'operation/events/events';
        $menu_operation['eventos']['id'] = 'oper-events';

        $sub = [];
        $sub['operation/events/events']['text'] = __('View events');
        $sub['operation/events/events']['id'] = 'View_events';
        $sub['operation/events/events']['pages'] = ['godmode/events/events'];

        // If ip doesn't is in list of allowed IP, isn't show this options.
        include_once 'include/functions_api.php';
        if (isInACL($_SERVER['REMOTE_ADDR'])) {
            $pss = get_user_info($config['id_user']);
            $hashup = md5($config['id_user'].$pss['password']);

            $user_filter = db_get_row_sql(
                sprintf(
                    'SELECT f.id_filter, f.id_name
                FROM tevent_filter f
                INNER JOIN tusuario u
                    ON u.default_event_filter=f.id_filter
                WHERE u.id_user = "%s" ',
                    $config['id_user']
                )
            );
            if ($user_filter !== false) {
                $user_event_filter = events_get_event_filter($user_filter['id_filter']);
            } else {
                // Default.
                $user_event_filter = [
                    'status'        => EVENT_NO_VALIDATED,
                    'event_view_hr' => $config['event_view_hr'],
                    'group_rep'     => EVENT_GROUP_REP_EVENTS,
                    'tag_with'      => [],
                    'tag_without'   => [],
                    'history'       => false,
                ];
            }

            $fb64 = base64_encode(json_encode($user_event_filter));

            // RSS.
            $sub['operation/events/events_rss.php?user='.$config['id_user'].'&amp;hashup='.$hashup.'&fb64='.$fb64]['text'] = __('RSS');
            $sub['operation/events/events_rss.php?user='.$config['id_user'].'&amp;hashup='.$hashup.'&fb64='.$fb64]['id'] = 'RSS';
            $sub['operation/events/events_rss.php?user='.$config['id_user'].'&amp;hashup='.$hashup.'&fb64='.$fb64]['type'] = 'direct';
        }

        // Acoustic console.
        $data_sound = base64_encode(
            json_encode(
                [
                    'title'        => __('Acoustic console'),
                    'start'        => __('Start'),
                    'stop'         => __('Stop'),
                    'noAlert'      => __('No alert'),
                    'silenceAlarm' => __('Silence alarm'),
                    'url'          => ui_get_full_url('ajax.php'),
                    'page'         => 'include/ajax/events',
                    'urlSound'     => 'include/sounds/',
                ]
            )
        );

        $javascript = 'javascript: openSoundEventModal(`'.$data_sound.'`);';
        $sub[$javascript]['text'] = __('Acoustic console');
        $sub[$javascript]['id'] = 'Acoustic console Modal';
        $sub[$javascript]['type'] = 'direct';

        echo '<div id="modal-sound" style="display:none;"></div>';

        ui_require_javascript_file('pandora_events');

        $menu_operation['eventos']['sub'] = $sub;
    }
}

$favorite_menu = db_get_all_rows_sql(
    sprintf(
        'SELECT id_element, url, label, section
        FROM tfavmenu_user
        WHERE id_user = "%s"
        ORDER BY section DESC',
        $config['id_user']
    )
);
// Favorite.
if ($favorite_menu !== false) {
    $menu_operation['favorite']['text'] = __('Favorite');
    $menu_operation['favorite']['id'] = 'fav-menu';

    $section = '';
    $sub = [];
    $sub2 = [];
    foreach ($favorite_menu as $key => $row) {
        if ($row['section'] !== $section) {
            $section = $row['section'];
            $sub2 = [];
        }

        $sub[$section]['text'] = __(str_replace('_', ' ', $section));
        $sub[$section]['type'] = 'direct';
        $sub[$section]['subtype'] = 'nolink';
        $sub[$section]['id'] = $row['section'].'-fav-menu';

        $sub2[$row['url']]['text'] = io_safe_output($row['label']);
        $sub[$section]['sub2'] = $sub2;
    }

    $menu_operation['favorite']['sub'] = $sub;
}




// Workspace.
$menu_operation['workspace']['text'] = __('Workspace');
$menu_operation['workspace']['sec2'] = 'operation/users/user_edit';
$menu_operation['workspace']['id'] = 'oper-users';

// ANY user can view him/herself !
// Users.
$query_paramameters_user = '&edit_user=1&pure=0';

$sub = [];
$sub['godmode/users/configure_user'.$query_paramameters_user]['text'] = __('Edit my user');
$sub['godmode/users/configure_user'.$query_paramameters_user]['id'] = 'Edit_my_user';
$sub['godmode/users/configure_user'.$query_paramameters_user]['refr'] = 0;

// Users.
$sub['operation/users/user_edit_notifications']['text'] = __('Configure user notifications');
$sub['operation/users/user_edit_notifications']['id'] = 'Configure_user_notifications';
$sub['operation/users/user_edit_notifications']['refr'] = 0;

if ($access_console_node === true) {
    // Incidents.
    $temp_sec2 = $sec2;
    $sec2 = 'incident';
    $sec2sub = 'operation/incidents/incident_statistics';
    $sub[$sec2]['text'] = __('Incidents');
    $sub[$sec2]['id'] = 'Incidents';
    $sub[$sec2]['type'] = 'direct';
    $sub[$sec2]['subtype'] = 'nolink';
    $sub[$sec2]['refr'] = 0;
    $sub[$sec2]['subsecs'] = [
        'operation/incidents/incident_detail',
        'operation/integria_incidents',
    ];

    $sub2 = [];
    $sub2[$sec2sub]['text'] = __('Integria IMS statistics');
    $sub2['operation/incidents/list_integriaims_incidents']['text'] = __('Integria IMS ticket list');

    $sub[$sec2]['sub2'] = $sub2;
    $sec2 = $temp_sec2;


    // Messages.
    $sub['message_list']['text'] = __('Messages');
    $sub['message_list']['id'] = 'Messages';
    $sub['message_list']['refr'] = 0;
    $sub['message_list']['type'] = 'direct';
    $sub['message_list']['subtype'] = 'nolink';
    $sub2 = [];
    $sub2['operation/messages/message_list']['text'] = __('Messages List');
    $sub2['operation/messages/message_edit&new_msg=1']['text'] = __('New message');

    $sub['message_list']['sub2'] = $sub2;
}

$menu_operation['workspace']['sub'] = $sub;

if ($access_console_node === true) {
    // Rest of options, all with AR privilege (or should events be with incidents?)
    // ~ if (check_acl ($config['id_user'], 0, "AR")) {
    // Extensions menu additions.
    if (is_array($config['extensions'])) {
        $sub = [];
        $sub2 = [];

        if (check_acl($config['id_user'], 0, 'RR') || check_acl($config['id_user'], 0, 'RW') || check_acl($config['id_user'], 0, 'RM')) {
            $sub['operation/agentes/exportdata']['text'] = __('Export data');
            $sub['operation/agentes/exportdata']['id'] = 'Export_data';
            $sub['operation/agentes/exportdata']['subsecs'] = ['operation/agentes/exportdata'];
        }

        if (check_acl($config['id_user'], 0, 'AR') || check_acl($config['id_user'], 0, 'AD') || check_acl($config['id_user'], 0, 'AW')) {
            $sub['godmode/agentes/planned_downtime.list']['text'] = __('Scheduled downtime');
            $sub['godmode/agentes/planned_downtime.list']['id'] = 'Scheduled_downtime';
        }

        foreach ($config['extensions'] as $extension) {
            // If no operation_menu is a godmode extension.
            if ($extension['operation_menu'] == '') {
                continue;
            }

            // Check the ACL for this user.
            if (! check_acl($config['id_user'], 0, $extension['operation_menu']['acl'])) {
                continue;
            }

            $extension_menu = $extension['operation_menu'];
            if ($extension['operation_menu']['name'] == 'Matrix'
                && ( !check_acl($config['id_user'], 0, 'ER')
                || !check_acl($config['id_user'], 0, 'EW')
                || !check_acl($config['id_user'], 0, 'EM') )
            ) {
                continue;
            }

            // Check if was displayed inside other menu.
            if ($extension['operation_menu']['fatherId'] == '') {
                if ($extension_menu['name'] == 'Update manager') {
                    continue;
                }

                $sub[$extension_menu['sec2']]['text'] = $extension_menu['name'];
                $sub[$extension_menu['sec2']]['id'] = str_replace(' ', '_', $extension_menu['name']);
                $sub[$extension_menu['sec2']]['refr'] = 0;
            } else {
                if (array_key_exists('fatherId', $extension_menu)) {
                    // Check that extension father ID exists previously on the menu.
                    if ((strlen($extension_menu['fatherId']) > 0)) {
                        if (array_key_exists('subfatherId', $extension_menu) && empty($extension_menu['subfatherId']) === false) {
                            if ((strlen($extension_menu['subfatherId']) > 0)) {
                                $menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['subfatherId']]['sub2'][$extension_menu['sec2']]['text'] = __($extension_menu['name']);
                                $menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['subfatherId']]['sub2'][$extension_menu['sec2']]['id'] = str_replace(' ', '_', $extension_menu['name']);
                                $menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['subfatherId']]['sub2'][$extension_menu['sec2']]['refr'] = 0;
                                $menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['subfatherId']]['sub2'][$extension_menu['sec2']]['icon'] = $extension_menu['icon'];
                                $menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['subfatherId']]['sub2'][$extension_menu['sec2']]['sec'] = 'extensions';
                                $menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['subfatherId']]['sub2'][$extension_menu['sec2']]['extension'] = true;
                                $menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['subfatherId']]['sub2'][$extension_menu['sec2']]['enterprise'] = $extension['enterprise'];
                                $menu_operation[$extension_menu['fatherId']]['hasExtensions'] = true;
                            } else {
                                $menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]['text'] = __($extension_menu['name']);
                                $menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]['id'] = str_replace(' ', '_', $extension_menu['name']);
                                $menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]['refr'] = 0;
                                $menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]['icon'] = $extension_menu['icon'];
                                $menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]['sec'] = 'extensions';
                                $menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]['extension'] = true;
                                $menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]['enterprise'] = $extension['enterprise'];
                                $menu_operation[$extension_menu['fatherId']]['hasExtensions'] = true;
                            }
                        } else {
                            $menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]['text'] = __($extension_menu['name']);
                            $menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]['id'] = str_replace(' ', '_', $extension_menu['name']);
                            $menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]['refr'] = 0;
                            $menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]['icon'] = $extension_menu['icon'];
                            $menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]['sec'] = 'extensions';
                            $menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]['extension'] = true;
                            $menu_operation[$extension_menu['fatherId']]['sub'][$extension_menu['sec2']]['enterprise'] = $extension['enterprise'];
                            $menu_operation[$extension_menu['fatherId']]['hasExtensions'] = true;
                        }
                    }
                }
            }
        }

        if (!empty($sub)) {
            $menu_operation['extensions']['text'] = __('Tools');
            $menu_operation['extensions']['sec2'] = 'operation/extensions';
            $menu_operation['extensions']['id'] = 'oper-extensions';
            $menu_operation['extensions']['sub'] = $sub;
        }
    }

    // ~ }
}

// Save operation menu array to use in operation/extensions.php view
$operation_menu_array = $menu_operation;


if (!$config['pure']) {
    menu_print_menu($menu_operation, true);
}
