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
// Load global vars
global $config;

check_login();

require_once 'include/functions_gis.php';
require_once $config['homedir'].'/include/functions_agents.php';

ui_require_javascript_file('openlayers.pandora');

$idMap = (int) get_parameter('map_id');
$show_history = get_parameter('show_history', 'n');

$map = db_get_row('tgis_map', 'id_tgis_map', $idMap);
$confMap = gis_get_map_conf($idMap);

// Default open map (used to overwrite unlicensed google map view)
$confMapDefault = get_good_con();
$confMapDefaultFull = [];
$confMapDefaultUrlFull = json_decode($confMapDefault['conection_data'], true);
$confMapUrlDefault = $confMapDefaultFull['url'];

if (! check_acl($config['id_user'], $map['group_id'], 'MR') && ! check_acl($config['id_user'], $map['group_id'], 'MW') && ! check_acl($config['id_user'], $map['group_id'], 'MM')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access map builder'
    );
    include 'general/noaccess.php';
    return;
}

$num_baselayer = 0;
// Initialy there is no Gmap base layer.
$gmap_layer = false;
if ($confMap !== false) {
    foreach ($confMap as $mapC) {
        $baselayers[$num_baselayer]['typeBaseLayer'] = $mapC['connection_type'];
        $baselayers[$num_baselayer]['name'] = $mapC['conection_name'];
        $baselayers[$num_baselayer]['num_zoom_levels'] = $mapC['num_zoom_levels'];
        $decodeJSON = json_decode($mapC['conection_data'], true);

        switch ($mapC['connection_type']) {
            case 'OSM':
                $baselayers[$num_baselayer]['url'] = $decodeJSON['url'];
            break;

            case 'Gmap':
                if (!isset($decodeJSON['gmap_key']) || empty($decodeJSON['gmap_key'])) {
                    // If there is not gmap_key, show the default view
                    $baselayers[$num_baselayer]['url'] = $confMapUrlDefault;
                    $baselayers[$num_baselayer]['typeBaseLayer'] = 'OSM';
                } else {
                    $baselayers[$num_baselayer]['gmap_type'] = $decodeJSON['gmap_type'];
                    $baselayers[$num_baselayer]['gmap_key'] = $decodeJSON['gmap_key'];
                    $gmap_key = $decodeJSON['gmap_key'];
                    // Once a Gmap base layer is found we mark it to import the API
                    $gmap_layer = true;
                }
            break;

            case 'Static_Image':
                $baselayers[$num_baselayer]['url'] = $decodeJSON['url'];
                $baselayers[$num_baselayer]['bb_left'] = $decodeJSON['bb_left'];
                $baselayers[$num_baselayer]['bb_right'] = $decodeJSON['bb_right'];
                $baselayers[$num_baselayer]['bb_bottom'] = $decodeJSON['bb_bottom'];
                $baselayers[$num_baselayer]['bb_top'] = $decodeJSON['bb_top'];
                $baselayers[$num_baselayer]['image_width'] = $decodeJSON['image_width'];
                $baselayers[$num_baselayer]['image_height'] = $decodeJSON['image_height'];
            break;

            case 'WMS':
                $baselayers[$num_baselayer]['url'] = $decodeJSON['url'];
                $baselayers[$num_baselayer]['layers'] = $decodeJSON['layers'];
            break;
        }

        $num_baselayer++;
        if ($mapC['default_map_connection'] == 1) {
            $numZoomLevels = $mapC['num_zoom_levels'];
        }
    }
}

if ($gmap_layer === true) {
    if (https_is_running()) {
        ?>
    <script type="text/javascript" src="https://maps.google.com/maps?file=api&v=2&sensor=false&key=<?php echo $gmap_key; ?>" ></script>
        <?php
    } else {
        ?>
    <script type="text/javascript" src="http://maps.google.com/maps?file=api&v=2&sensor=false&key=<?php echo $gmap_key; ?>" ></script>
        <?php
    }
}

$controls = [
    'PanZoomBar',
    'ScaleLine',
    'Navigation',
    'MousePosition',
    'layerSwitcher',
];

$layers = gis_get_layers($idMap);

// Render map.
$has_management_acl = check_acl_restricted_all($config['id_user'], $map['group_id'], 'MW')
    || check_acl_restricted_all($config['id_user'], $map['group_id'], 'MM');

$buttons = [];

$buttons['gis_maps_list'] = [
    'active' => false,
    'text'   => '<a href="index.php?sec=godgismaps&sec2=operation/gis_maps/gis_map">'.html_print_image(
        'images/logs@svg.svg',
        true,
        [
            'title' => __('GIS Maps list'),
            'class' => 'main_menu_icon invert_filter',
        ]
    ).'</a>',
];
if ($config['pure'] == 0) {
    $buttons[]['text'] = '<a href="index.php?sec=gismaps&amp;sec2=operation/gis_maps/render_view&amp;map_id='.$idMap.'&amp;refr='.((int) get_parameter('refr', 0)).'&amp;pure=1">'.html_print_image('images/fullscreen@svg.svg', true, ['title' => __('Full screen mode'), 'class' => 'main_menu_icon invert_filter']).'</a>';
} else {
    $buttons[]['text'] = '<a href="index.php?sec=gismaps&amp;sec2=operation/gis_maps/render_view&amp;map_id='.$idMap.'&amp;refr='.((int) get_parameter('refr', 0)).'">'.html_print_image('images/exit_fullscreen@svg.svg', true, ['title' => __('Back to normal mode'), 'class' => 'main_menu_icon invert_filter']).'</a>';
}

if ($has_management_acl === true) {
    $hash = md5($config['dbpass'].$idMap.$config['id_user']);
    $buttons['public_link']['text'] = '<a href="'.ui_get_full_url(
        'operation/gis_maps/public_console.php?hash='.$hash.'&map_id='.$idMap.'&id_user='.$config['id_user']
    ).'" target="_blank">'.html_print_image('images/item-icon.svg', true, ['title' => __('Show link to public GIS map'), 'class' => 'main_menu_icon invert_filter']).'</a>';
}

$times = [
    5                 => __('5 seconds'),
    10                => __('10 seconds'),
    30                => __('30 seconds'),
    SECONDS_1MINUTE   => __('1 minute'),
    SECONDS_2MINUTES  => __('2 minutes'),
    SECONDS_5MINUTES  => __('5 minutes'),
    SECONDS_10MINUTES => __('10 minutes'),
    SECONDS_1HOUR     => __('1 hour'),
    SECONDS_2HOUR     => __('2 hours'),
];

$buttons[]['text'] = "<div class='mrgn_top_6px'>".__('Refresh').': '.html_print_select($times, 'refresh_time', 60, 'changeRefreshTime(this.value);', '', 0, true, false, false).'</div>';

$status = [
    'all'     => __('None'),
    'bad'     => __('Critical'),
    'warning' => __('Warning'),
    'ok'      => __('Ok'),
    'default' => __('Other'),
];

$buttons[]['text'] = "<div class='mrgn_top_6px'>".__('Filter by status').': '.html_print_select($status, 'show_status', 'all', 'changeShowStatus(this.value);', '', 0, true, false, false).'</div>';

if ($has_management_acl) {
    $buttons['setup']['text'] = '<a href="index.php?sec=godgismaps&sec2=godmode/gis_maps/configure_gis_map&action=edit_map&map_id='.$idMap.'">'.html_print_image('images/configuration@svg.svg', true, ['title' => __('Setup'), 'class' => 'main_menu_icon invert_filter']).'</a>';
    $buttons['setup']['godmode'] = 1;
}

// Header.
ui_print_standard_header(
    __('Map').': '.$map['map_name'],
    'images/op_snmp.png',
    false,
    '',
    false,
    $buttons,
    [
        [
            'link'  => '',
            'label' => __('Topology maps'),
        ],
        [
            'link'  => '',
            'label' => __('GIS Maps'),
        ],
    ]
);

$map_inline_style = 'width: 100%; min-height:500px; height: calc(100vh - 80px);';
$map_inline_style .= $config['pure'] ? 'position:absolute; top: 80px; left: 0px;' : 'border: 1px solid black;';

echo '<div id="map" style="'.$map_inline_style.'" />';

gis_print_map(
    'map',
    $map['zoom_level'],
    $map['initial_latitude'],
    $map['initial_longitude'],
    $baselayers,
    $controls
);

if ($layers != false) {
    foreach ($layers as $layer) {
        gis_make_layer(
            $layer['layer_name'],
            $layer['view_layer'],
            null,
            $layer['id_tmap_layer']
        );

        // Calling agents_get_group_agents with none to obtain the names in the same case as they are in the DB.
        $agentNamesByGroup = [];
        if ($layer['tgrupo_id_grupo'] >= 0) {
            $agentNamesByGroup = agents_get_group_agents(
                $layer['tgrupo_id_grupo'],
                false,
                'none',
                true,
                true,
                false
            );
        }

        $agentNamesByLayer = gis_get_agents_layer($layer['id_tmap_layer']);

        $groupsByAgentId = gis_get_groups_layer_by_agent_id($layer['id_tmap_layer']);
        $agentNamesOfGroupItems = [];
        foreach ($groupsByAgentId as $agentId => $groupInfo) {
            $agentNamesOfGroupItems[$agentId] = $groupInfo['agent_name'];
        }

        $agentNames = array_unique($agentNamesByGroup + $agentNamesByLayer + $agentNamesOfGroupItems);

        foreach ($agentNames as $key => $agentName) {
            $idAgent = $key;
            $coords = gis_get_data_last_position_agent($idAgent);

            if ($coords === false) {
                $coords['stored_latitude'] = $map['default_latitude'];
                $coords['stored_longitude'] = $map['default_longitude'];
            } else {
                if ($show_history == 'y') {
                    $lastPosition = [
                        'longitude' => $coords['stored_longitude'],
                        'latitude'  => $coords['stored_latitude'],
                    ];
                    gis_add_path($layer['layer_name'], $idAgent, $lastPosition);
                }
            }

            $status = agents_get_status($idAgent, true);
            $icon = gis_get_agent_icon_map($idAgent, true, $status);
            $icon_size = getimagesize($icon);
            $icon_width = $icon_size[0];
            $icon_height = $icon_size[1];

            // Is a group item.
            if (!empty($groupsByAgentId[$idAgent])) {
                $groupId = (int) $groupsByAgentId[$idAgent]['id'];
                $groupName = $groupsByAgentId[$idAgent]['name'];

                gis_add_agent_point(
                    $layer['layer_name'],
                    io_safe_output($groupName),
                    $coords['stored_latitude'],
                    $coords['stored_longitude'],
                    $icon,
                    $icon_width,
                    $icon_height,
                    $idAgent,
                    $status,
                    'point_group_info',
                    $groupId
                );
            } else {
                $parent = db_get_value('id_parent', 'tagente', 'id_agente', $idAgent);

                gis_add_agent_point(
                    $layer['layer_name'],
                    io_safe_output($agentName),
                    $coords['stored_latitude'],
                    $coords['stored_longitude'],
                    $icon,
                    $icon_width,
                    $icon_height,
                    $idAgent,
                    $status,
                    'point_agent_info',
                    $parent
                );
            }
        }
    }

    gis_add_parent_lines();

    switch ($config['dbtype']) {
        case 'mysql':
            $timestampLastOperation = db_get_value_sql('SELECT UNIX_TIMESTAMP()');
        break;

        case 'postgresql':
            $timestampLastOperation = db_get_value_sql(
                "SELECT ceil(date_part('epoch', CURRENT_TIMESTAMP))"
            );
        break;

        case 'oracle':
            $timestampLastOperation = db_get_value_sql(
                "SELECT ceil((sysdate - to_date('19700101000000','YYYYMMDDHH24MISS')) * (".SECONDS_1DAY.')) FROM dual'
            );
        break;
    }

    gis_activate_select_control();
    gis_activate_ajax_refresh($layers, $timestampLastOperation);
}

?>

<script type="text/javascript">
    $(document).ready(function() {
        var $map = $("#map");
        $map.css("height", "calc(100vh - " + $map.offset().top + "px - 20px)");

        $('#select2-show_status-container').parent().parent().parent().removeClass('select2');
        $('#select2-refresh_time-container').parent().parent().parent().removeClass('select2');
    });
</script>
