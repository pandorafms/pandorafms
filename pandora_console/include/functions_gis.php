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
require_once $config['homedir'].'/include/functions_groups.php';
require_once $config['homedir'].'/include/functions_agents.php';


/**
 * Print javascript to add parent lines between the agents.
 *
 * @param inteer $idMap The id of map.
 *
 * @return None
 */
function gis_add_parent_lines()
{
    gis_make_layer(__('Hierarchy of agents'));

    echo "<script type='text/javascript'>";
    echo "$(document).ready (function () {
        var layer = map.getLayersByName('".__('Hierarchy of agents')."');
        layer = layer[0];
        
        map.setLayerIndex(layer, 0);
        
        js_refreshParentLines('".__('Hierarchy of agents')."');
    });";
    echo '</script>';
}


/**
 * Return the data of last position of agent from tgis_data_status.
 *
 * @param  integer $idAgent                The id of agent.
 * @param  boolean $returnEmptyArrayInFail The set return a empty array when fail and true.
 * @return array The row of agent in tgis_data_status, and it's a associative array.
 */
function gis_get_data_last_position_agent($idAgent, $returnEmptyArrayInFail=false)
{
    $returnVar = db_get_row('tgis_data_status', 'tagente_id_agente', $idAgent);

    if (($returnVar === false) && ($returnEmptyArrayInFail)) {
        return [];
    }

    return $returnVar;
}


/**
 * Write a javascript vars for to call the js_printMap.
 *
 * @param string  $idDiv      The id of div to paint the map.
 * @param integer $iniZoom    The zoom to init the map.
 * @param float   $latCenter  The latitude center to init the map.
 * @param float   $lonCenter  The longitude center to init the map.
 * @param array   $baselayers The list of baselayer with the connection data.
 * @param array   $controls   The string list of controls.
 *
 * @return None
 */
function gis_print_map($idDiv, $iniZoom, $latCenter, $lonCenter, $baselayers, $controls=null)
{
    ui_require_javascript_file('OpenLayers/OpenLayers');

    echo "<script type='text/javascript'>";
    echo 'var controlsList = [];';
    foreach ($controls as $control) {
        echo "controlsList.push('".$control."');";
    }

    echo "var idDiv = '".$idDiv."';";
    echo 'var initialZoom = '.$iniZoom.';';
    echo 'var centerLatitude = '.$latCenter.';';
    echo 'var centerLongitude = '.$lonCenter.';';

    echo 'var baselayerList = [];';
    echo 'var baselayer = null;';

    foreach ($baselayers as $baselayer) {
        echo 'baselayer = {';
        echo 'bb_bottom: null,';
        echo 'bb_left: null,';
        echo 'bb_right: null,';
        echo 'bb_top: null,';
        echo 'gmap_type: null,';
        echo 'image_height: null,';
        echo 'image_width: null,';
        echo 'num_zoom_levels: null,';
        echo 'name: null,';
        echo 'type: null,';
        echo 'url: null';
        echo '};';

        echo "baselayer['type'] = '".$baselayer['typeBaseLayer']."';";
        echo "baselayer['name'] = '".$baselayer['name']."';";
        echo "baselayer['num_zoom_levels'] = '".$baselayer['num_zoom_levels']."';";

        switch ($baselayer['typeBaseLayer']) {
            case 'OSM':
                echo "baselayer['url'] = '".$baselayer['url']."';";
            break;

            case 'Static_Image':
                echo "baselayer['bb_left'] = '".$baselayer['bb_left']."';";
                echo "baselayer['bb_bottom'] = '".$baselayer['bb_bottom']."';";
                echo "baselayer['bb_right'] = '".$baselayer['bb_right']."';";
                echo "baselayer['bb_top'] = '".$baselayer['bb_top']."';";
                echo "baselayer['image_width'] = '".$baselayer['image_width']."';";
                echo "baselayer['image_height'] = '".$baselayer['image_height']."';";
                echo "baselayer['url'] = '".$baselayer['url']."';";
            break;

            case 'Gmap':
                echo "baselayer['gmap_type'] = '".$baselayer['gmap_type']."';";
            break;

            case 'WMS':
                echo "baselayer['url'] = '".$baselayer['url']."';";
                echo "baselayer['layers'] = '".$baselayer['layers']."';";
            break;
        }

        echo 'baselayerList.push(baselayer);';
    }

    echo 'js_printMap(idDiv, initialZoom, centerLatitude, centerLongitude, baselayerList, controlsList);';

    echo '</script>';

    ?>
    <script type="text/javascript">
        
        $(document).ready(function() {
            setInterval(
                function() {
                        
                        $("img")
                            .filter(function() { return this.src.match(/mapcnt3/);})
                            .css('display', 'none');
                            
                        $("img")
                            .filter(function() { return this.src.match(/cb_scout2/);})
                            .css('display', 'none');
                        
                        $(".gm-style-mtc").css('display', 'none');
                        $(".olControlMousePosition").css("background", "white");
                    }
                
                ,3000);
        });
        
    </script>
    <?php
}


function gis_make_layer($name, $visible=true, $dot=null, $idLayer=null, $public_console=0, $id_map=0)
{
    global $config;

    if ($dot == null) {
        $dot['url'] = 'images/dot_green.png';
        $dot['width'] = 20;
        // 11;
        $dot['height'] = 20;
        // 11;
    }

    $hash = '';
    if ($public_console) {
        $hash = md5($config['dbpass'].$id_map.$config['id_user']);
    }

    $visible = (bool) $visible;

    $ajax_url = $public_console ? ui_get_full_url('operation/gis_maps/ajax.php', false, false, false, false) : ui_get_full_url('ajax.php', false, false, false, false);
    ?>
    <script type="text/javascript">
    $(document).ready (
        function () {
            //Creamos el estilo
            var style = new OpenLayers.StyleMap(
                {fontColor: "#ff0000",
                    labelYOffset: -<?php echo $dot['height']; ?>,
                    graphicHeight: <?php echo $dot['height']; ?>, 
                    graphicWidth: <?php echo $dot['width']; ?>, 
                    externalGraphic: "<?php echo $dot['url']; ?>", label:"${nombre}"
                }
            );
            
            //Creamos la capa de tipo vector
            var layer = new OpenLayers.Layer.Vector(
            '<?php echo $name; ?>', {styleMap: style}
            );
            
            layer.data = {};
            layer.data.id = '<?php echo $idLayer; ?>';
            
             layer.setVisibility(<?php echo $visible; ?>);
                    map.addLayer(layer);
            
            layer.events.on({
                 "featureselected": function(e) {
                    if (e.feature.geometry.CLASS_NAME == "OpenLayers.Geometry.Point") {
                        var featureData = e.feature.data;

                        var img_src = "<?php echo ui_get_full_url('images/spinner.gif', false, false, false, false); ?>";
                        var $details = $('<div />');

                        $details
                            .prop("id", 'cloudContent_' + featureData.id)
                            .css("text-align", "center")
                            .html('<img src="' + img_src + '" />')
                            .dialog({
                                title: featureData.type == "point_group_info"
                                    ? "<?php echo __('Group'); ?> #" + featureData.id_parent
                                    : "<?php echo __('Agent'); ?> #" + featureData.id,
                                resizable: true,
                                draggable: true,
                                modal: true,
                                overlay: {
                                    opacity: 0.5,
                                    background: "black"
                                },
                                close: function () {
                                    $details.remove();
                                }
                            });
                        
                        jQuery.ajax ({
                            url: "<?php echo $ajax_url; ?>",
                            data: {
                                page: "operation/gis_maps/ajax",
                                opt: featureData.type,
                                id: featureData.id,
                                id_parent: featureData.id_parent,
                                hash: "<?php echo $hash; ?>",
                                id_user: "<?php echo $config['id_user']; ?>",
                                map_id: <?php echo (int) $id_map; ?>
                            },
                            type: "GET",
                            dataType: "json",
                            success: function (data) {
                                if (data.correct) {
                                    $details.css("text-align", "left").html(data.content);
                                }
                            }
                        });
                    }
                }
            });
        }
    );
    </script>
    <?php
}


function gis_activate_select_control($layers=null)
{
    ?>
    <script type="text/javascript">
    $(document).ready (
        function () {
            var layers = map.getLayersByClass("OpenLayers.Layer.Vector");
            var select = new OpenLayers.Control.SelectFeature(layers, {
                clickout: true,
                multiple: true,
                toggle: true,
                autoActivate: true,
                onSelect: function() {
                    OpenLayers.Control.SelectFeature.prototype.unselectAll.apply(
                        select);
                }
            });
            map.addControl(select);
            select.activate();
        }
    );
    </script>
    <?php
}


/**
 * Activate the feature refresh by ajax.
 *
 * @param array   $layers         Its a rows of table "tgis_map_layer" or None is all.
 * @param integer $lastTimeOfData The time in unix timestamp of last query of data GIS in DB.
 *
 * @return None
 */
function gis_activate_ajax_refresh($layers=null, $lastTimeOfData=null, $public_console=0, $id_map=0)
{
    global $config;

    if ($lastTimeOfData === null) {
        $lastTimeOfData = time();
    }

    $hash = '';
    if ($public_console) {
        $hash = md5($config['dbpass'].$id_map.$config['id_user']);
    }

    ui_require_jquery_file('json');
    ?>
    <script type="text/javascript">
        var last_time_of_data = <?php echo $lastTimeOfData; ?>; //This time use in the ajax query to next recent points.
        var refreshAjaxIntervalSeconds = 60000;
        var idIntervalAjax = null;
        var oldRefreshAjaxIntervalSeconds = 60000;
        
        <?php
        if ($layers === null) {
            echo 'var agentView = 1;';
        } else {
            echo 'var agentView = 0;';
        }
        ?>
        
        function refreshAjaxLayer(layer) {
            var featureIdArray = Array();
            
            for (featureIndex = 0; featureIndex < layer.features.length; featureIndex++) {
                feature = layer.features[featureIndex];
                
                if (feature.data.type != 'point_path_info') {
                    if (feature.geometry.CLASS_NAME == "OpenLayers.Geometry.Point") {
                        featureIdArray.push(feature.data.id);
                    }
                }
            }
            
            if (featureIdArray.length > 0) {
                jQuery.ajax ({
                data: "page=operation/gis_maps/ajax"
                    + "&opt=get_new_positions"
                    + "&id_features=" + featureIdArray.toString()
                    + "&last_time_of_data=" + last_time_of_data
                    + "&layer_id=" + layer.data.id
                    + "&agent_view=" + agentView
                    + "&hash=<?php echo $hash; ?>"
                    + "&id_user=<?php echo $config['id_user']; ?>"
                    + "&map_id=<?php echo $id_map; ?>",
                type: "GET",
                dataType: 'json',
                url: "ajax.php",
                success: function (data) {
                    if (data.correct) {
                        content = $.evalJSON(data.content);
                        
                        if (content != null) {
                            for (var idAgent in content) {
                                if (isInt(idAgent)) {
                                    agentDataGIS = content[idAgent];
                                    feature = searchPointAgentById(idAgent);
                                    layer.removeFeatures(feature);
                                    
                                    delete feature;
                                    feature = null
                                    status = parseInt(agentDataGIS['status']);
                                    
                                    js_addAgentPointExtent(layer.name,
                                        agentDataGIS['name'],
                                        agentDataGIS['stored_longitude'],
                                        agentDataGIS['stored_latitude'],
                                        agentDataGIS['icon_path'],
                                        agentDataGIS['icon_width'],
                                        agentDataGIS['icon_height'],
                                        idAgent,
                                        'point_agent_info', status,
                                        agentDataGIS['id_parent']);
                                    
                                    if (!agentView) {
                                        //TODO: Optimize, search a new position to call for all agent in the layer and or optimice code into function.
                                        js_refreshParentLines();
                                    }
                                }
                            }
                        }
                    }
                    EventZoomEnd(null,map.zoom);
                }
                });
            }
        }
        
        function clock_ajax_refresh() {
            //console.log(new Date().getHours() + ":" + new Date().getMinutes() + ":" + new Date().getSeconds());
            for (layerIndex = 0; layerIndex < map.getNumLayers(); layerIndex++) {
                layer = map.layers[layerIndex];
                <?php
                if ($layers === null) {
                    ?>
                    if (layer.isVector) {
                        refreshAjaxLayer(layer);
                    }
                    <?php
                } else {
                    foreach ($layers as $layer) {
                        ?>
                        if (layer.name == '<?php echo $layer['layer_name']; ?>') {
                            refreshAjaxLayer(layer);
                        }
                        <?php
                    }
                }
                ?>
            }
                        
            last_time_of_data = Math.round(new Date().getTime() / 1000); //Unixtimestamp
            
            //Test if the user change the refresh time.
            if (oldRefreshAjaxIntervalSeconds != refreshAjaxIntervalSeconds) {
                clearInterval(idIntervalAjax);
                idIntervalAjax = setInterval("clock_ajax_refresh()", refreshAjaxIntervalSeconds);
                oldRefreshAjaxIntervalSeconds = refreshAjaxIntervalSeconds;
            }
            
            EventZoomEnd(null,map.zoom);
            
            
        }
        
        $(document).ready (
            function () {
                idIntervalAjax = setInterval("clock_ajax_refresh()", refreshAjaxIntervalSeconds);
                EventZoomEnd(null,map.zoom);
            }
        );
    </script>
    <?php
}


function gis_add_agent_point(
    $layerName,
    $pointName,
    $lat,
    $lon,
    $icon=null,
    $width=20,
    $height=20,
    $point_id='',
    $status=-1,
    $type_string='',
    $idParent=0
) {
    global $config;
    if (!$config['gis_label']) {
        $pointName = '';
    }
    ?>
    <script type="text/javascript">
        $(document).ready (
            function () {
                <?php
                if ($icon != null) {
                    // echo "js_addPointExtent('$layerName', '$pointName', $lon, $lat, '$icon', $width, $height, $point_id, '$type_string', $status);";
                    echo "js_addAgentPointExtent('$layerName', '$pointName', $lon, $lat, '$icon', $width, $height, $point_id, '$type_string', $status, $idParent);";
                } else {
                    // echo "js_addPoint('$layerName', '$pointName', $lon, $lat, $point_id, '$type_string', $status);";
                    echo "js_addAgentPoint('$layerName', '$pointName', $lon, $lat, $point_id, '$type_string', $status, $idParent);";
                }
                ?>
            }
        );
    </script>
    <?php
}


/**
 * Get the agents in layer but not by group in layer.
 *
 * @param integer $idLayer Layer ID.
 *
 * @return array The array rows of tagente of agents in the layer.
 */
function gis_get_agents_layer($idLayer)
{
    $sql = "SELECT id_agente, nombre
            FROM tagente
            WHERE id_agente IN (
                SELECT tagente_id_agente
                FROM tgis_map_layer_has_tagente
                WHERE tgis_map_layer_id_tmap_layer = $idLayer)";
    $agents = db_get_all_rows_sql($sql);

    $returned_agents = [];
    if ($agents !== false) {
        foreach ($agents as $index => $agent) {
            $returned_agents[$agent['id_agente']] = $agent['nombre'];
        }
    }

    return $returned_agents;
}


/**
 * Get the groups into the layer by agent Id.
 *
 * @param integer $idLayer Layer Id.
 *
 * @return array.
 */
function gis_get_groups_layer_by_agent_id($idLayer)
{
    $sql = sprintf(
        'SELECT
            tg.id_grupo AS id,
            tg.nombre AS name,
            ta.id_agente AS agent_id,
            ta.alias AS agent_alias, 
            ta.nombre AS agent_name
        FROM tgis_map_layer_groups tgmlg
        INNER JOIN tgrupo tg
            ON tgmlg.group_id = tg.id_grupo
        INNER JOIN tagente ta
            ON tgmlg.agent_id = ta.id_agente
        WHERE tgmlg.layer_id = %d',
        $idLayer
    );
    $groups = db_get_all_rows_sql($sql);
    if ($groups === false) {
        $groups = [];
    }

    return array_reduce(
        $groups,
        function ($all, $item) {
            $all[$item['agent_id']] = $item;
            return $all;
        },
        []
    );
}


function gis_add_point_path($layerName, $lat, $lon, $color, $manual, $id)
{
    $manual = ($manual ?? 1);
    ?>
    <script type="text/javascript">
    $(document).ready (
        function () {
            js_addPointPath('<?php echo $layerName; ?>', <?php echo $lon; ?>, <?php echo $lat; ?>, '<?php echo $color; ?>', <?php echo $manual; ?>, <?php echo $id; ?>);
        }
    );
    </script>
    <?php
}


function gis_get_maps()
{
    return db_get_all_rows_in_table('tgis_map', 'map_name');
}


/**
 * Gets the configuration of all the base layers of a map
 *
 * @param $idMap: Map identifier of the map to get the configuration
 *
 * @return An array of arrays of configuration parameters
 */
function gis_get_map_conf($idMap)
{
    $sql = 'SELECT tconn.*, trel.default_map_connection
            FROM tgis_map_connection tconn, tgis_map_has_tgis_map_con trel
            WHERE trel.tgis_map_con_id_tmap_con = tconn.id_tmap_connection
                AND trel.tgis_map_id_tgis_map = '.$idMap;
    $mapConfs = db_get_all_rows_sql($sql);

    return $mapConfs;
}


function get_good_con()
{
    $good_map = db_get_row('tgis_map_connection', 'id_tmap_connection', 2);
    // Try to open the default OpenStreetMap
    if ($good_map !== false) {
        return $good_map;
    }

    return db_get_row('tgis_map_connection', 'connection_type', 'OSM');
}


function gis_get_map_connection($idMapConnection)
{
    return db_get_row('tgis_map_connection', 'id_tmap_connection', $idMapConnection);
}


function gis_get_layers($idMap)
{
    $sql = 'SELECT *
            FROM tgis_map_layer
            WHERE tgis_map_id_tgis_map = '.$idMap;
    $layers = db_get_all_rows_sql($sql);

    return $layers;
}


function gis_get_agent_icon_map($idAgent, $state=false, $status=null)
{
    global $config;

    $sql = 'SELECT id_grupo, icon_path
            FROM tagente
            WHERE id_agente = '.$idAgent;
    $row = db_get_row_sql($sql);

    if ($row['icon_path'] === null || strlen($row['icon_path']) == 0) {
        if ($config['gis_default_icon'] != '') {
            $icon = 'images/gis_map/icons/'.$config['gis_default_icon'];
        } else {
            $icon = 'images/groups_small/'.groups_get_icon($row['id_grupo']);
        }
    } else {
        $icon = 'images/gis_map/icons/'.$row['icon_path'];
    }

    if ($state === false) {
        return $icon.'.png';
    } else {
        if ($status === null) {
            $status = agents_get_status($idAgent);
            if ($status === null) {
                $status = -1;
            }
        }

        switch ($status) {
            case 1:
            case 4:
            case 100:
                // Critical (BAD or ALERT)
                $state = '.bad';
            break;

            case 0:
            case 300:
                // Normal (OK)
                $state = '.ok';
            break;

            case 2:
            case 200:
                // Warning
                $state = '.warning';
            break;

            default:
                // Default is Grey (Other)
                $state = '.default';
            break;
        }

        $returnIcon = $icon.$state.'.png';

        return $returnIcon;
    }
}


/**
 * Print the path of agent.
 *
 * @param string  $layerName    String of layer.
 * @param integer $idAgent      The id of agent
 * @param integer $history_time Number of seconds in the past to show from where to start the history path.
 * @param array   $lastPosition The last position of agent that is not in history table.
 *
 * @return None
 */
function gis_add_path($layerName, $idAgent, $lastPosition=null, $history_time=null)
{
    global $config;

    if ($history_time === null) {
        $where = '1 = 1';
    } else {
        switch ($config['dbtype']) {
            case 'mysql':
                $where = 'start_timestamp >= FROM_UNIXTIME(UNIX_TIMESTAMP() - '.$history_time.')';
            break;

            case 'postgresql':
                $where = 'start_timestamp >= to_timestamp(ceil(date_part("epoch", CURRENT_TIMESTAMP)) - '.$history_time.')';
            break;

            case 'oracle':
                $where = 'start_timestamp >= to_timestamp(\'01-01-1970 00:00:00\', \'DD-MM-YYYY HH24:MI:SS\') + NUMTODSINTERVAL((ceil((sysdate - to_date(\'19700101000000\',\'YYYYMMDDHH24MISS\')) * ('.SECONDS_1DAY.')) - '.$history_time.'),\'SECOND\')';
            break;
        }
    }

    $sql = "SELECT *
            FROM tgis_data_history
            WHERE tagente_id_agente = $idAgent
                AND $where
            ORDER BY end_timestamp ASC";
    $listPoints = db_get_all_rows_sql($sql);

    // If the agent is empty the history
    if ($listPoints === false) {
        return;
    }

    $avaliableColors = [
        '#ff0000',
        '#00ff00',
        '#0000ff',
        '#000000',
    ];

    $randomIndex = array_rand($avaliableColors);
    $color = $avaliableColors[$randomIndex];
    ?>
    <script type="text/javascript">
        $(document).ready (
            function () {
                <?php
                if ($listPoints != false) {
                    $listPoints = (array) $listPoints;
                    $first = true;

                    echo 'var points = [';
                    foreach ($listPoints as $point) {
                        if (!$first) {
                            echo ',';
                        }

                        $first = false;
                        echo 'new OpenLayers.Geometry.Point('.$point['longitude'].', '.$point['latitude'].')';
                    }

                    if ($lastPosition !== null) {
                        echo ', new OpenLayers.Geometry.Point('.$lastPosition['longitude'].', '.$lastPosition['latitude'].')';
                    }

                    echo '];';
                }
                ?>
                
                js_addLineString('<?php echo $layerName; ?>', points, '<?php echo $color; ?>');
            }
        );
    </script>
    <?php
    if ($listPoints != false) {
        foreach ($listPoints as $point) {
            if ((end($listPoints) != $point) || (($lastPosition !== null))) {
                gis_add_point_path($layerName, $point['latitude'], $point['longitude'], $color, (int) $point['manual_placement'], $point['id_tgis_data']);
            }
        }
    }
}


function gis_delete_map_connection($idConectionMap)
{
    db_process_sql_delete('tgis_map_connection', ['id_tmap_connection' => $idConectionMap]);

    // TODO DELETE IN OTHER TABLES
}


function gis_save_map_connection(
    $mapConnection_name,
    $mapConnection_group,
    $mapConnection_numLevelsZoom,
    $mapConnection_defaultZoom,
    $mapConnection_defaultLatitude,
    $mapConnection_defaultLongitude,
    $mapConnection_defaultAltitude,
    $mapConnection_centerLatitude,
    $mapConnection_centerLongitude,
    $mapConnection_centerAltitude,
    $mapConnectionData,
    $idConnectionMap=null
) {
    if ($idConnectionMap !== null) {
        $returnQuery = db_process_sql_update(
            'tgis_map_connection',
            [
                'conection_name'     => $mapConnection_name,
                'connection_type'    => $mapConnectionData['type'],
                'conection_data'     => json_encode($mapConnectionData),
                'num_zoom_levels'    => $mapConnection_numLevelsZoom,
                'default_zoom_level' => $mapConnection_defaultZoom,
                'default_longitude'  => $mapConnection_defaultLongitude,
                'default_latitude'   => $mapConnection_defaultLatitude,
                'default_altitude'   => $mapConnection_defaultAltitude,
                'initial_longitude'  => $mapConnection_centerLongitude,
                'initial_latitude'   => $mapConnection_centerLatitude,
                'initial_altitude'   => $mapConnection_centerAltitude,
                'group_id'           => $mapConnection_group,
            ],
            ['id_tmap_connection' => $idConnectionMap]
        );
    } else {
        $returnQuery = db_process_sql_insert(
            'tgis_map_connection',
            [
                'conection_name'     => $mapConnection_name,
                'connection_type'    => $mapConnectionData['type'],
                'conection_data'     => json_encode($mapConnectionData),
                'num_zoom_levels'    => $mapConnection_numLevelsZoom,
                'default_zoom_level' => $mapConnection_defaultZoom,
                'default_longitude'  => $mapConnection_defaultLongitude,
                'default_latitude'   => $mapConnection_defaultLatitude,
                'default_altitude'   => $mapConnection_defaultAltitude,
                'initial_longitude'  => $mapConnection_centerLongitude,
                'initial_latitude'   => $mapConnection_centerLatitude,
                'initial_altitude'   => $mapConnection_centerAltitude,
                'group_id'           => $mapConnection_group,
            ]
        );
    }

    return $returnQuery;
}


/**
 * Delete the map in the all tables are related.
 *
 * @param  $idMap integer The id of map.
 * @return None
 */
function gis_delete_map($idMap)
{
    $listIdLayers = db_get_all_rows_sql(
        'SELECT id_tmap_layer
        FROM tgis_map_layer
        WHERE tgis_map_id_tgis_map = '.$idMap
    );

    if ($listIdLayers !== false) {
        foreach ($listIdLayers as $idLayer) {
            db_process_sql_delete(
                'tgis_map_layer_has_tagente',
                ['tgis_map_layer_id_tmap_layer' => $idLayer]
            );
        }

        $correct = (bool) db_process_sql_delete(
            'tgis_map_layer',
            ['tgis_map_id_tgis_map' => $idMap]
        );
    } else {
        $correct = true;
    }

    if ($correct) {
        $correct = db_process_sql_delete(
            'tgis_map_has_tgis_map_con',
            ['tgis_map_id_tgis_map' => $idMap]
        );
    }

    if ($correct) {
        $correct = db_process_sql_delete(
            'tgis_map',
            ['id_tgis_map' => $idMap]
        );
    }

    $numMaps = db_get_num_rows('SELECT * FROM tgis_map');

    db_clean_cache();

    return $correct;
}


/**
 * Save the map into DB, tgis_map and with id_map save the connetions in
 * tgis_map_has_tgis_map_con, and with id_map save the layers in
 * tgis_map_layer and witch each id_layer save the agent in this layer in
 * table tgis_map_layer_has_tagente.
 *
 * @param $map_name
 * @param $map_initial_longitude
 * @param $map_initial_latitude
 * @param $map_initial_altitude
 * @param $map_zoom_level
 * @param $map_background
 * @param $map_default_longitude
 * @param $map_default_latitude
 * @param $map_default_altitude
 * @param $map_group_id
 * @param $map_connection_list
 * @param $arrayLayers
 */
function gis_save_map(
    $map_name,
    $map_initial_longitude,
    $map_initial_latitude,
    $map_initial_altitude,
    $map_zoom_level,
    $map_background,
    $map_default_longitude,
    $map_default_latitude,
    $map_default_altitude,
    $map_group_id,
    $map_connection_list,
    $arrayLayers
) {
    $idMap = db_process_sql_insert(
        'tgis_map',
        [
            'map_name'          => $map_name,
            'initial_longitude' => $map_initial_longitude,
            'initial_latitude'  => $map_initial_latitude,
            'initial_altitude'  => $map_initial_altitude,
            'zoom_level'        => $map_zoom_level,
            'map_background'    => $map_background,
            'default_longitude' => $map_default_longitude,
            'default_latitude'  => $map_default_latitude,
            'default_altitude'  => $map_default_altitude,
            'group_id'          => $map_group_id,
        ]
    );

    $numMaps = db_get_num_rows('SELECT * FROM tgis_map');

    if ($numMaps == 1) {
        db_process_sql_update('tgis_map', ['default_map' => 1], ['id_tgis_map' => $idMap]);
    }

    foreach ($map_connection_list as $map_connection) {
        db_process_sql_insert(
            'tgis_map_has_tgis_map_con',
            [
                'tgis_map_id_tgis_map'     => $idMap,
                'tgis_map_con_id_tmap_con' => $map_connection['id_conection'],
                'default_map_connection'   => $map_connection['default'],
            ]
        );
    }

    foreach ($arrayLayers as $index => $layer) {
        $idLayer = db_process_sql_insert(
            'tgis_map_layer',
            [
                'layer_name'           => $layer['layer_name'],
                'view_layer'           => $layer['layer_visible'],
                'layer_stack_order'    => $index,
                'tgis_map_id_tgis_map' => $idMap,
                'tgrupo_id_grupo'      => $layer['layer_group'],
            ]
        );
        // Angent
        if ((isset($layer['layer_agent_list'])) && (count($layer['layer_agent_list']) > 0)) {
            foreach ($layer['layer_agent_list'] as $agent) {
                db_process_sql_insert(
                    'tgis_map_layer_has_tagente',
                    [
                        'tgis_map_layer_id_tmap_layer' => $idLayer,
                        'tagente_id_agente'            => $agent['id'],
                    ]
                );
            }
        }

        // Group
        if ((isset($layer['layer_group_list'])) && (count($layer['layer_group_list']) > 0)) {
            foreach ($layer['layer_group_list'] as $group) {
                db_process_sql_insert(
                    'tgis_map_layer_groups',
                    [
                        'layer_id' => $idLayer,
                        'group_id' => $group['id'],
                        'agent_id' => $group['agent_id'],
                    ]
                );
            }
        }
    }

    return $idMap;
}


function gis_update_map(
    $idMap,
    $map_name,
    $map_initial_longitude,
    $map_initial_latitude,
    $map_initial_altitude,
    $map_zoom_level,
    $map_background,
    $map_default_longitude,
    $map_default_latitude,
    $map_default_altitude,
    $map_group_id,
    $map_connection_list,
    $arrayLayers
) {
    db_process_sql_update(
        'tgis_map',
        [
            'map_name'          => $map_name,
            'initial_longitude' => $map_initial_longitude,
            'initial_latitude'  => $map_initial_latitude,
            'initial_altitude'  => $map_initial_altitude,
            'zoom_level'        => $map_zoom_level,
            'map_background'    => $map_background,
            'default_longitude' => $map_default_longitude,
            'default_latitude'  => $map_default_latitude,
            'default_altitude'  => $map_default_altitude,
            'group_id'          => $map_group_id,
        ],
        ['id_tgis_map' => $idMap]
    );

    db_process_sql_delete('tgis_map_has_tgis_map_con', ['tgis_map_id_tgis_map' => $idMap]);

    foreach ($map_connection_list as $map_connection) {
        db_process_sql_insert(
            'tgis_map_has_tgis_map_con',
            [
                'tgis_map_id_tgis_map'     => $idMap,
                'tgis_map_con_id_tmap_con' => $map_connection['id_conection'],
                'default_map_connection'   => $map_connection['default'],
            ]
        );
    }

    $sql = 'SELECT id_tmap_layer
            FROM tgis_map_layer
            WHERE tgis_map_id_tgis_map = '.$idMap;
    $listOldIdLayers = db_get_all_rows_sql($sql);
    if ($listOldIdLayers == false) {
        $listOldIdLayers = [];
    }

    $list_onlyIDsLayers = [];
    foreach ($listOldIdLayers as $idLayer) {
        db_process_sql_delete(
            'tgis_map_layer_has_tagente',
            ['tgis_map_layer_id_tmap_layer' => $idLayer['id_tmap_layer']]
        );
        db_process_sql_delete(
            'tgis_map_layer_groups',
            ['layer_id' => $idLayer['id_tmap_layer']]
        );

        $list_onlyIDsLayers[$idLayer['id_tmap_layer']] = 0;
    }

    foreach ($arrayLayers as $index => $layer) {
        if ($layer['id'] != 0) {
            $idLayer = $layer['id'];
            unset($list_onlyIDsLayers[$idLayer]);

            db_process_sql_update(
                'tgis_map_layer',
                [
                    'layer_name'           => $layer['layer_name'],
                    'view_layer'           => $layer['layer_visible'],
                    'layer_stack_order'    => $index,
                    'tgis_map_id_tgis_map' => $idMap,
                    'tgrupo_id_grupo'      => $layer['layer_group'],
                ],
                ['id_tmap_layer' => $idLayer]
            );
        } else {
            $idLayer = db_process_sql_insert(
                'tgis_map_layer',
                [
                    'layer_name'           => $layer['layer_name'],
                    'view_layer'           => $layer['layer_visible'],
                    'layer_stack_order'    => $index,
                    'tgis_map_id_tgis_map' => $idMap,
                    'tgrupo_id_grupo'      => $layer['layer_group'],
                ]
            );
        }

        if (array_key_exists('layer_agent_list', $layer)) {
            if (empty($layer['layer_agent_list']) === false && count($layer['layer_agent_list']) > 0) {
                foreach ($layer['layer_agent_list'] as $agent) {
                    $id = db_process_sql_insert(
                        'tgis_map_layer_has_tagente',
                        [
                            'tgis_map_layer_id_tmap_layer' => $idLayer,
                            'tagente_id_agente'            => $agent['id'],
                        ]
                    );
                }
            }
        }

        if (array_key_exists('layer_group_list', $layer)) {
            if (empty($layer['layer_group_list']) === false && count($layer['layer_group_list']) > 0) {
                foreach ($layer['layer_group_list'] as $group) {
                    $id = db_process_sql_insert(
                        'tgis_map_layer_groups',
                        [
                            'layer_id' => $idLayer,
                            'group_id' => $group['id'],
                            'agent_id' => $group['agent_id'],
                        ]
                    );
                }
            }
        }
    }

    // Delete layers that not carry the $arrayLayers
    foreach ($list_onlyIDsLayers as $idLayer => $trash) {
        db_process_sql_delete(
            'tgis_map_layer',
            ['id_tmap_layer' => $idLayer]
        );
    }
}


/**
 * Get the configuration parameters of a map connection
 *
 * @param idConnection: connection identifier for the map
 *
 * @result: An array with all the configuration parameters
 */
function gis_get_conection_conf($idConnection)
{
    $sql = 'SELECT *
            FROM tgis_map_connection
            WHERE id_tmap_connection = '.$idConnection;
    $confParameters = db_get_row_sql($sql);

    return $confParameters;
}


/**
 * Shows the map of an agent in a div with the width and heigth given and the history if asked
 *
 * @param $agent_id: id of the agent as in the table tagente;
 * @param $height: heigth in a string in css format
 * @param $width: width in a string in css format
 * @param $show_history: by default or when this parameter is false in the map the path with the
 * @param $centerInAgent boolean Default is true, set the map center in the icon agent.
 * @param $history_time: Number of seconds in the past to show from where to start the history path.
 *
 * @return boolean True ok and false fail.
 */
function gis_get_agent_map($agent_id, $heigth, $width, $show_history=false, $centerInAgent=true, $history_time=SECONDS_1DAY)
{
    $sql = 'SELECT t1.*, t3.conection_name, t3.connection_type,
                t3.conection_data, t3.num_zoom_levels
            FROM tgis_map t1, 
                tgis_map_has_tgis_map_con t2, 
                tgis_map_connection t3
            WHERE t1.default_map = 1
                AND t2.tgis_map_id_tgis_map = t1.id_tgis_map
                AND t2.default_map_connection = 1
                AND t3.id_tmap_connection = t2.tgis_map_con_id_tmap_con';
    $defaultMap = db_get_all_rows_sql($sql);

    if ($defaultMap === false) {
        return false;
    }

    $defaultMap = $defaultMap[0];

    $agent_position = gis_get_data_last_position_agent($agent_id);
    if ($agent_position === false) {
        $agentPositionLongitude = $defaultMap['default_longitude'];
        $agentPositionLatitude = $defaultMap['default_latitude'];
        $agentPositionAltitude = $defaultMap['default_altitude'];
    } else {
        $agentPositionLongitude = $agent_position['stored_longitude'];
        $agentPositionLatitude = $agent_position['stored_latitude'];
        $agentPositionAltitude = $agent_position['stored_altitude'];
    }

    $agent_name = agents_get_name($agent_id);

    $clean_agent_name = $agent_name;
    // Avoid the agents with characters that fails the div.
    $agent_name = md5($agent_name);

    $baselayers[0]['name'] = $defaultMap['conection_name'];
    $baselayers[0]['num_zoom_levels'] = $defaultMap['num_zoom_levels'];

    $conectionData = json_decode($defaultMap['conection_data'], true);
    $baselayers[0]['typeBaseLayer'] = $conectionData['type'];
    $controls = [
        'PanZoomBar',
        'ScaleLine',
        'Navigation',
        'MousePosition',
    ];
    $gmap_layer = false;

    switch ($conectionData['type']) {
        case 'OSM':
            $baselayers[0]['url'] = $conectionData['url'];
        break;

        case 'Gmap':
            $baselayers[0]['gmap_type'] = $conectionData['gmap_type'];
            $baselayers[0]['gmap_key'] = $conectionData['gmap_key'];
            $gmap_key = $conectionData['gmap_key'];
            // Onece a Gmap base layer is found we mark it to import the API
            $gmap_layer = true;
        break;

        case 'Static_Image':
            $baselayers[0]['url'] = $conectionData['url'];
            $baselayers[0]['bb_left'] = $conectionData['bb_left'];
            $baselayers[0]['bb_right'] = $conectionData['bb_right'];
            $baselayers[0]['bb_bottom'] = $conectionData['bb_bottom'];
            $baselayers[0]['bb_top'] = $conectionData['bb_top'];
            $baselayers[0]['image_width'] = $conectionData['image_width'];
            $baselayers[0]['image_height'] = $conectionData['image_height'];
        break;

        case 'WMS':
            $baselayers[0]['url'] = $conectionData['url'];
            $baselayers[0]['layers'] = $conectionData['layers'];
        break;
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

    gis_print_map(
        $agent_name.'_agent_map',
        $defaultMap['zoom_level'],
        $defaultMap['initial_latitude'],
        $defaultMap['initial_longitude'],
        $baselayers,
        $controls
    );

    gis_make_layer('layer_for_agent_'.$agent_name);

    $agent_icon = gis_get_agent_icon_map($agent_id, true);
    $agent_icon_size = getimagesize($agent_icon);
    $agent_icon_width = $agent_icon_size[0];
    $agent_icon_height = $agent_icon_size[1];
    $status = agents_get_status($agent_id);

    // If show_history is true, show the path of the agent
    if ($show_history) {
        $lastPosition = [
            'longitude' => $agentPositionLongitude,
            'latitude'  => $agentPositionLatitude,
        ];
        gis_add_path(
            'layer_for_agent_'.$agent_name,
            $agent_id,
            $lastPosition,
            $history_time
        );
    }

    gis_add_agent_point(
        'layer_for_agent_'.$agent_name,
        io_safe_output(agents_get_alias_by_name($clean_agent_name)),
        $agentPositionLatitude,
        $agentPositionLongitude,
        $agent_icon,
        $agent_icon_width,
        $agent_icon_height,
        $agent_id,
        $status,
        'point_agent_info'
    );

    if ($centerInAgent) {
        ?>
        <script type="text/javascript">
        $(document).ready (
            function () { 
                var lonlat = new OpenLayers.LonLat(<?php echo $agentPositionLongitude; ?>, <?php echo $agentPositionLatitude; ?>)
                    .transform(map.displayProjection, map.getProjectionObject());
                map.setCenter(lonlat, <?php echo $defaultMap['zoom_level']; ?>, false, false);
            });
        </script>
        <?php
    }

    return true;
}


/**
 * Return a array of images as icons in the /pandora_console/images/gis_map/icons.
 *
 * @param boolean $fullpath Return as image.png or full path.
 *
 * @return array The array is [N][3], where the N index is name base of icon and [N]['ok'] ok image, [N]['bad'] bad image, [N]['warning'] warning image and [N]['default] default image
 */
function gis_get_array_list_icons($fullpath=true)
{
    $return = [];
    $validExtensions = [
        'jpg',
        'jpeg',
        'gif',
        'png',
    ];

    $path = '';
    if ($fullpath) {
        $path = 'images/gis_map/icons/';
    }

    $dir = scandir('images/gis_map/icons/');

    foreach ($dir as $index => $item) {
        $chunks = explode('.', $item);

        $extension = end($chunks);

        if (!in_array($extension, $validExtensions)) {
            unset($dir[$index]);
        }
    }

    foreach ($dir as $item) {
        $chunks = explode('.', $item);
        $extension = end($chunks);

        $nameWithoutExtension = str_replace('.'.$extension, '', $item);
        $nameClean = str_replace(['.bad', '.ok', '.warning', '.default'], '', $nameWithoutExtension);

        $return[$nameClean]['ok'] = $path.$nameClean.'.ok.png';
        $return[$nameClean]['bad'] = $path.$nameClean.'.bad.png';
        $return[$nameClean]['warning'] = $path.$nameClean.'.warning.png';
        $return[$nameClean]['default'] = $path.$nameClean.'.default.png';
    }

    return $return;
}


function gis_validate_map_data(
    $map_name,
    $map_zoom_level,
    $map_initial_longitude,
    $map_initial_latitude,
    $map_initial_altitude,
    $map_default_longitude,
    $map_default_latitude,
    $map_default_altitude,
    $map_connection_list,
    $map_levels_zoom
) {
    $invalidFields = [];

    echo "<style type='text/css'>";

    // ValidateMap.
    if ($map_name == '') {
        echo 'input[name=map_name] {background: #FF5050;}';
        $invalidFields['map_name'] = true;
    }

    // Validate zoom level.
    if (($map_zoom_level == '') || ($map_zoom_level > $map_levels_zoom)) {
        echo 'input[name=map_zoom_level] {background: #FF5050;}';
        $invalidFields['map_zoom_level'] = true;
    }

    // Validate map_initial_longitude.
    if ($map_initial_longitude == '') {
        echo 'input[name=map_initial_longitude] {background: #FF5050;}';
        $invalidFields['map_initial_longitude'] = true;
    }

    // Validate map_initial_latitude.
    if ($map_initial_latitude == '') {
        echo 'input[name=map_initial_latitude] {background: #FF5050;}';
        $invalidFields['map_initial_latitude'] = true;
    }

    // Validate map_initial_altitude.
    if ($map_initial_altitude == '') {
        echo 'input[name=map_initial_altitude] {background: #FF5050;}';
        $invalidFields['map_initial_altitude'] = true;
    }

    // Validate map_default_longitude.
    if ($map_default_longitude == '') {
        echo 'input[name=map_default_longitude] {background: #FF5050;}';
        $invalidFields['map_default_longitude'] = true;
    }

    // Validate map_default_latitude.
    if ($map_default_latitude == '') {
        echo 'input[name=map_default_latitude] {background: #FF5050;}';
        $invalidFields['map_default_latitude'] = true;
    }

    // Validate map_default_altitude.
    if ($map_default_altitude == '') {
        echo 'input[name=map_default_altitude] {background: #FF5050;}';
        $invalidFields['map_default_altitude'] = true;
    }

    // Validate map_default_altitude.
    if ($map_connection_list == '') {
        $invalidFields['map_connection_list'] = true;
    }

    echo '</style>';

    return $invalidFields;
}


/**
 * Get all data (connections, layers with agents) of a map passed as id.
 *
 * @param integer $idMap The id of map in database.
 *
 * @return array Return a asociative array whith the items 'map', 'connections' and 'layers'. And in 'layers' has data and item 'agents'.
 */
function gis_get_map_data($idMap)
{
    global $config;

    $idMap = (int) $idMap;
    $returnVar = [];

    $map = db_get_row('tgis_map', 'id_tgis_map', $idMap);

    if (empty($map)) {
        return $returnVar;
    }

    $connections = false;
    switch ($config['dbtype']) {
        case 'mysql':
            $sql = "SELECT t1.tgis_map_con_id_tmap_con AS id_conection, 
                        t1.default_map_connection AS `default`,
                        SUM(t2.num_zoom_levels) AS num_zoom_levels
                    FROM tgis_map_has_tgis_map_con t1
                    INNER JOIN tgis_map_connection t2
                        ON t1.tgis_map_con_id_tmap_con = t2.id_tmap_connection
                    WHERE t1.tgis_map_id_tgis_map = $idMap
                    GROUP BY t1.tgis_map_con_id_tmap_con, t1.default_map_connection";
            $connections = db_get_all_rows_sql($sql);
        break;

        case 'postgresql':
        case 'oracle':
            $sql = "SELECT t1.tgis_map_con_id_tmap_con AS id_conection, 
                        t1.default_map_connection AS \"default\",
                        SUM(t2.num_zoom_levels) AS num_zoom_levels
                    FROM tgis_map_has_tgis_map_con t1
                    INNER JOIN tgis_map_connection t2
                        ON t1.tgis_map_con_id_tmap_con = t2.id_tmap_connection
                    WHERE t1.tgis_map_id_tgis_map = $idMap
                    GROUP BY t1.tgis_map_con_id_tmap_con, t1.default_map_connection";
            $connections = db_get_all_rows_sql($sql);
        break;
    }

    $sql = "SELECT id_tmap_layer AS id,
                layer_name,
                tgrupo_id_grupo AS layer_group,
                view_layer AS layer_visible
            FROM tgis_map_layer
            WHERE tgis_map_id_tgis_map = $idMap
            ORDER BY layer_stack_order ASC";
    $layers = db_get_all_rows_sql($sql);
    if ($layers === false) {
        $layers = [];
    }

    foreach ($layers as $index => $layer) {
        if (!isset($layer['id'])) {
            continue;
        }

        $id_tmap_layer = (int) $layer['id'];

        // Agent list
        $sql = "SELECT id_agente AS id, alias
                FROM tagente
                WHERE id_agente IN (
                    SELECT tagente_id_agente
                    FROM tgis_map_layer_has_tagente
                    WHERE tgis_map_layer_id_tmap_layer = $id_tmap_layer)";
        $agents = db_get_all_rows_sql($sql);
        if ($agents === false) {
            $agents = [];
        }

        $layers[$index]['layer_agent_list'] = $agents;

        // Group list
        $sql = sprintf(
            'SELECT
                tg.id_grupo AS id,
                tg.nombre AS name,
                ta.id_agente AS agent_id, 
                ta.alias AS agent_alias
            FROM tgis_map_layer_groups tgmlg
            INNER JOIN tgrupo tg
                ON tgmlg.group_id = tg.id_grupo
            INNER JOIN tagente ta
                ON tgmlg.agent_id = ta.id_agente
            WHERE tgmlg.layer_id = %d',
            $id_tmap_layer
        );
        $groups = db_get_all_rows_sql($sql);
        if ($groups === false) {
            $groups = [];
        }

        $layers[$index]['layer_group_list'] = $groups;
    }

    $returnVar['map'] = $map;
    $returnVar['connections'] = $connections;
    $returnVar['layers'] = $layers;

    return $returnVar;
}


/**
 * This function use in form the "pandora_console/godmode/gis_maps/configure_gis_map.php"
 * in the case of edit a map or when there are any error in save new map. Because this function
 * return a html code that it has the rows of connections.
 *
 * @param array $map_connection_list The list of map connections for convert a html.
 *
 * @return string The html source code.
 */
function gis_add_conection_maps_in_form($map_connection_list)
{
    $returnVar = '';

    foreach ($map_connection_list as $mapConnection) {
        $mapConnectionRowDB = gis_get_map_connection($mapConnection['id_conection']);

        if ($mapConnection['default']) {
            $radioButton = html_print_radio_button_extended('map_connection_default', $mapConnection['id_conection'], '', $mapConnection['id_conection'], false, 'changeDefaultConection(this.value)', '', true);
        } else {
            $radioButton = html_print_radio_button_extended('map_connection_default', $mapConnection['id_conection'], '', null, false, 'changeDefaultConection(this.value)', '', true);
        }

        $returnVar .= '
            <tbody id="map_connection_'.$mapConnection['id_conection'].'">
                <tr class="row_0">
                    <td>'.html_print_input_text('map_connection_name_'.$mapConnection['id_conection'], $mapConnectionRowDB['conection_name'], '', 20, 40, true, true).'</td>
                    <td>'.$radioButton.'</td>
                    <td><a id="delete_row" href="javascript: deleteConnectionMap(\''.$mapConnection['id_conection'].'\')">'.html_print_image('images/delete.svg', true, ['alt' => '', 'class' => 'invert_filter']).'</a></td>
                </tr>
            </tbody>
            <script type="text/javascript">
            connectionMaps.push('.$mapConnection['id_conection'].');
            </script>
            ';
    }

    return $returnVar;
}


/**
 * From a list of connection maps, extract the num levels zooom
 *
 * @param array $map_connection_list The list of connections maps.
 *
 * @return integer The num zoom levels.
 */
function gis_get_num_zoom_levels_connection_default($map_connection_list)
{
    foreach ($map_connection_list as $connection) {
        if ($connection['default']) {
            return $connection['num_zoom_levels'];
        }
    }
}


function gis_calculate_distance($lat_start, $lon_start, $lat_end, $lon_end)
{
    // Use 3958.9=miles, 6371.0=Km;
    $earthRadius = 6371;

    $distance = 0;
    $azimuth = 0;
    $beta = 0;
    $cosBeta = 0;
    $cosAzimuth = 0;

    $lat_start = deg2rad($lat_start);
    $lon_start = deg2rad($lon_start);
    $lat_end = deg2rad($lat_end);
    $lon_end = deg2rad($lon_end);

    if (abs($lat_start) < 90.0) {
        $cosBeta = ((sin($lat_start) * sin($lat_end)) + ((cos($lat_start) * cos($lat_end)) * cos($lon_end - $lon_start)));

        if ($cosBeta >= 1.0) {
            return 0.0;
        }

        /*
            Antipodes  (return miles, 0 degrees)
        */
        if ($cosBeta <= -1.0) {
            return (floor($earthRadius * pi() * 100.0) / 100.0);
        }

        $beta = acos($cosBeta);
        $distance = ($beta * $earthRadius);
        $cosAzimuth = ((sin($lat_end) - sin($lat_start) * cos($beta)) / (cos($lat_start) * sin($beta)));

        if ($cosAzimuth >= 1.0) {
            $azimuth = 0.0;
        } else if ($cosAzimuth <= -1.0) {
            $azimuth = 180.0;
        } else {
            $azimuth = rad2deg(acos($cosAzimuth));
        }

        if (sin($lon_end - $lon_start) < 0.0) {
            $azimuth = (360.0 - $azimuth);
        }

        return (floor($distance * 100.0) / 100.0);
    }

    // If P1 Is North Or South Pole, Then Azimuth Is Undefined
    if (gis_sgn($lat_start) == gis_sgn($lat_end)) {
        $distance = ($earthRadius * (pi() / 2 - abs($lat_end)));
    } else {
        $distance = ($earthRadius * (pi() / 2 + abs($lat_end)));
    }

    return (floor($distance * 100.0) / 100.0);
}


function gis_sgn($number)
{
    if ($number == 0) {
        return 0;
    } else {
        if ($number < 0) {
            return -1;
        } else {
            return 1;
        }
    }
}
