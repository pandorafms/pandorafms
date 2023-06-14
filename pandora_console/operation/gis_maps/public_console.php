<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 20012 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// Don't start a session before this import.
// The session is configured and started inside the config process.
require_once '../../include/config.php';

// Set root on homedir, as defined in setup
chdir($config['homedir']);

ob_start();
echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
echo '<html xmlns="http://www.w3.org/1999/xhtml">'."\n";
echo '<head>';

global $vc_public_view;
$vc_public_view = true;
// This starts the page head. In the call back function,
// things from $page['head'] array will be processed into the head
ob_start('ui_process_page_head');


require_once 'include/functions_gis.php';
require_once $config['homedir'].'/include/functions_agents.php';

ui_require_javascript_file('openlayers.pandora');

$config['remote_addr'] = $_SERVER['REMOTE_ADDR'];

$hash = get_parameter('hash');
$idMap = (int) get_parameter('map_id');
$config['id_user'] = get_parameter('id_user');

$myhash = md5($config['dbpass'].$idMap.$config['id_user']);

// Check input hash
if ($myhash != $hash) {
    exit;
}


$show_history = get_parameter('show_history', 'n');

$map = db_get_row('tgis_map', 'id_tgis_map', $idMap);
$confMap = gis_get_map_conf($idMap);

// Default open map (used to overwrite unlicensed google map view)
$confMapDefault = get_good_con();
$confMapUrlDefault = json_decode($confMapDefault['conection_data'], true);

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
                    $baselayers[$num_baselayer]['url'] = $confMapUrlDefault['url'];
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

echo '<div class="gis_layers">';
echo '<h1>'.$map['map_name'].'</h1>';
echo '<br />';

echo "<div id='map' class='map_gis' ></div>";

echo '</div>';

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
            $layer['id_tmap_layer'],
            1,
            $idMap
        );

        // calling agents_get_group_agents with none to obtain the names in the same case as they are in the DB.
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

        foreach ($agentNames as $agentName) {
            $idAgent = agents_get_agent_id($agentName);
            if (!$idAgent) {
                $idAgent = agents_get_agent_id_by_alias($agentName);
                $idAgent = (!empty($idAgent)) ? $idAgent[0]['id_agente'] : 0;
            }

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

            $status = agents_get_status($idAgent);
            $icon = gis_get_agent_icon_map($idAgent, true, $status);
            $icon_size = getimagesize($icon);
            $icon_width = $icon_size[0];
            $icon_height = $icon_size[1];
            $icon = ui_get_full_url($icon);

            // Is a group item
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
    gis_activate_ajax_refresh($layers, $timestampLastOperation, 1, $idMap);

    // Connection lost alert.
    ui_require_css_file('register', 'include/styles/', true);
    $conn_title = __('Connection with server has been lost');
    $conn_text = __('Connection to the server has been lost. Please check your internet connection or contact with administrator.');
    ui_require_javascript_file('connection_check');
    set_js_value('absolute_homeurl', ui_get_full_url(false, false, false, false));
    ui_print_message_dialog($conn_title, $conn_text, 'connection', '/images/fail@svg.svg');
}

// Resize GIS map on fullscreen
?>
<script type="text/javascript">
    $(document).ready(function() {
        $("#map").css("height", $(document).height() - 100);
    });
</script>