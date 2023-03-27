<?php
/**
 * Pandora FMS- http://pandorafms.com
 * ==================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

// Load global vars.
global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Visual Setup Management'
    );
    include 'general/noaccess.php';
    return;
}

require_once 'include/functions_gis.php';

$buttons['gis'] = [
    'text' => '<a href="'.ui_get_full_url('index.php?sec=general&sec2=godmode/setup/setup&section=gis').'">'.html_print_image(
        'images/list.png',
        true,
        [
            'title' => __('GIS Maps connections'),
            'class' => 'invert_filter',
        ]
    ).'</a>',
];

$action = get_parameter('action', 'create_connection_map');

if (is_ajax()) {
}

echo '<form action="index.php?sec=gsetup&sec2=godmode/setup/gis_step_2" method="post">';

switch ($action) {
    case 'create_connection_map':
        // Header.
        ui_print_standard_header(
            __('Create new map connection'),
            '',
            false,
            'map_connection_tab',
            true,
            $buttons,
            [
                [
                    'link'  => '',
                    'label' => __('Setup'),
                ],
                [
                    'link'  => '',
                    'label' => __('Setup').' - '.__('Gis'),
                ],
            ]
        );

        $mapConnection_name = '';
        $mapConnection_group = '';
        $mapConnection_numLevelsZoom = '19';
        $mapConnection_defaultZoom = '16';
        $mapConnection_type = 0;
        $mapConnection_defaultLatitude = '40.42056';
        $mapConnection_defaultLongitude = '-3.708187';
        $mapConnection_defaultAltitude = '0';
        $mapConnection_centerLatitude = '40.42056';
        $mapConnection_centerLongitude = '-3.708187';
        $mapConnection_centerAltitude = '0';
        $mapConnectionData = null;

        html_print_input_hidden('action', 'save_map_connection');
    break;

    case 'edit_connection_map':
        // Header.
        ui_print_standard_header(
            __('Edit map connection'),
            '',
            false,
            'map_connection_tab',
            true,
            $buttons,
            [
                [
                    'link'  => '',
                    'label' => __('Setup'),
                ],
                [
                    'link'  => '',
                    'label' => __('Setup').' - '.__('Gis'),
                ],
            ]
        );

        $idConnectionMap = get_parameter('id_connection_map');
        $mapConnection = db_get_row_sql('SELECT * FROM tgis_map_connection WHERE id_tmap_connection = '.$idConnectionMap);

        $mapConnection_name = $mapConnection['conection_name'];
        $mapConnection_group = $mapConnection['group_id'];
        $mapConnection_numLevelsZoom = $mapConnection['num_zoom_levels'];
        $mapConnection_defaultZoom = $mapConnection['default_zoom_level'];
        $mapConnection_type = $mapConnection['connection_type'];
        $mapConnection_defaultLatitude = $mapConnection['default_latitude'];
        $mapConnection_defaultLongitude = $mapConnection['default_longitude'];
        $mapConnection_defaultAltitude = $mapConnection['default_altitude'];
        $mapConnection_centerLatitude = $mapConnection['initial_latitude'];
        $mapConnection_centerLongitude = $mapConnection['initial_longitude'];
        $mapConnection_centerAltitude = $mapConnection['initial_altitude'];
        $mapConnectionData = json_decode($mapConnection['conection_data'], true);

        html_print_input_hidden('id_connection_map', $idConnectionMap);
        html_print_input_hidden('action', 'save_edit_map_connection');
    break;

    case 'save_map_connection':
    case 'save_edit_map_connection':
        $mapConnection_name = get_parameter('name');
        $mapConnection_group = get_parameter('group');
        $mapConnection_numLevelsZoom = get_parameter('num_levels_zoom');
        $mapConnection_defaultZoom = get_parameter('initial_zoom');
        $mapConnection_type = get_parameter('type');
        $mapConnection_defaultLatitude = get_parameter('default_latitude');
        $mapConnection_defaultLongitude = get_parameter('default_longitude');
        $mapConnection_defaultAltitude = get_parameter('default_altitude');
        $mapConnection_centerLatitude = get_parameter('center_latitude');
        $mapConnection_centerLongitude = get_parameter('center_longitude');
        $mapConnection_centerAltitude = get_parameter('center_altitude');

        $idConnectionMap = get_parameter('id_connection_map', null);

        switch ($mapConnection_type) {
            case 'OSM':
                $mapConnection_OSM_url = get_parameter('url');
                $mapConnectionData = [
                    'type' => 'OSM',
                    'url'  => $mapConnection_OSM_url,
                ];
            break;

            case 'Gmap':
                $gmap_type = get_parameter('gmap_type');
                $gmap_key = get_parameter('gmap_key');
                $mapConnectionData = [
                    'type'      => 'Gmap',
                    'gmap_type' => $gmap_type,
                    'gmap_key'  => $gmap_key,
                ];
            break;

            case 'Static_Image':
                $mapConnection_Image_url = get_parameter('url');
                $bb_left = get_parameter('bb_left');
                $bb_right = get_parameter('bb_right');
                $bb_top = get_parameter('bb_top');
                $bb_bottom = get_parameter('bb_bottom');
                $image_height = get_parameter('image_height');
                $image_width = get_parameter('image_width');
                $mapConnectionData = [
                    'type'         => 'Static_Image',
                    'url'          => $mapConnection_Image_url,
                    'bb_left'      => $bb_left,
                    'bb_right'     => $bb_right,
                    'bb_top'       => $bb_top,
                    'bb_bottom'    => $bb_bottom,
                    'image_width'  => $image_width,
                    'image_height' => $image_height,
                ];
            break;

            case 'WMS':
                $url = get_parameter('url');
                $layers = get_parameter('layers');
                $mapConnectionData = [
                    'type'   => 'WMS',
                    'url'    => $url,
                    'layers' => $layers,
                ];
            break;

            default:
                // Default.
            break;
        }

        // TODO VALIDATE PARAMETERS.
        if ($mapConnection_name != '' && $mapConnection_type != '') {
            gis_save_map_connection(
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
                $idConnectionMap
            );

            $errorfill = false;
        } else {
            $errorfill = true;
        }

        include_once 'gis.php';
    return;

        break;
    default:
        // Default.
    break;
}

$table = new stdClass();
$table->width = '90%';

$table->data = [];
$table->data[0][0] = __('Connection Name');
$table->data[0][1] = html_print_input_text('name', $mapConnection_name, '', 30, 60, true);

$table->data[1][0] = __('Group');
$table->data[1][1] = html_print_select_groups(false, false, false, 'group', $mapConnection_group, '', __('All'), '0', true);

$table->data[2][0] = __('Number of zoom levels');
$table->data[2][1] = html_print_input_text('num_levels_zoom', $mapConnection_numLevelsZoom, '', 4, 10, true);


$table->data[3][0] = __('Default zoom level');
$table->data[3][1] = html_print_input_text('initial_zoom', $mapConnection_defaultZoom, '', 4, 10, true);

echo '<h4>'.__('Basic configuration').'</h4>';
html_print_table($table);

$table->width = '60%';
$table->data = [];
$types['OSM'] = __('Open Street Maps');
$types['Gmap'] = __('Google Maps');
$types['Static_Image'] = __('Static Image');
$types['WMS'] = __('WMS Server');
$table->data[0][0] = __('Type');
$table->data[0][1] = html_print_select($types, 'sel_type', $mapConnection_type, 'selMapConnectionType();', __('Please select the connection type'), 0, true);

echo '<h4>'.__('Map connection type').'</h4>';
html_print_table($table);

$optionsConnectionTypeTable = '';
$mapConnectionDataUrl = '';
$gmap_type = '';
$gmap_key = '';
$bb_left = '';
$bb_right = '';
$bb_bottom = '';
$bb_top = '';
$image_width = '';
$image_height = '';
$layers = '';
if ($mapConnectionData != null) {
    switch ($mapConnection_type) {
        case 'OSM':
            $mapConnectionDataUrl = $mapConnectionData['url'];
        break;

        case 'Gmap':
            $gmap_type = $mapConnectionData['gmap_type'];
            $gmap_key = $mapConnectionData['gmap_key'];
        break;

        case 'Static_Image':
            $mapConnectionDataUrl = $mapConnectionData['url'];
            $bb_left = $mapConnectionData['bb_left'];
            $bb_right = $mapConnectionData['bb_right'];
            $bb_bottom = $mapConnectionData['bb_bottom'];
            $bb_top = $mapConnectionData['bb_top'];
            $image_width = $mapConnectionData['image_width'];
            $image_height = $mapConnectionData['image_height'];
        break;

        case 'WMS':
            $mapConnectionDataUrl = $mapConnectionData['url'];
            $layers = $mapConnectionData['layers'];
        break;

        default:
            // Default.
        break;
    }
}

    // Open Street Map Connection.
    $optionsConnectionOSMTable = '<table class="databox" border="0" cellpadding="4" cellspacing="4" width="50%"><tr class="row_0"><td>'.htmlentities(
        __('Tile Server URL'),
        ENT_QUOTES,
        'UTF-8'
    ).':</td><td><input id="type" type="hidden" name="type" value="OSM" />'.html_print_input_text(
        'url',
        $mapConnectionDataUrl,
        '',
        45,
        90,
        true
    ).'</td></tr></table>';

    // Google Maps Connection.
    $gmaps_types['G_PHYSICAL_MAP'] = __('Google Physical');
    $gmaps_types['G_HYBRID_MAP'] = __('Google Hybrid');
    $gmaps_types['G_SATELITE_MAP'] = __('Google Satelite');
    // TODO: Use label tags for the forms.
    $optionsConnectionGmapTable = '<table class="databox" border="0" cellpadding="4" cellspacing="4" width="90%"><tr class="row_0"><td>'.__('Google Map Type').':</td><td><input id="type" type="hidden" name="type" value="Gmap" />'.trim(
        html_print_select(
            $gmaps_types,
            'gmap_type',
            $gmap_type,
            '',
            '',
            0,
            true,
            false,
            true,
            '',
            false,
            false,
            false,
            false,
            false,
            '',
            false,
            false,
            false,
            false,
            false
        )
    ).'</td></tr><tr class="row_2"><td>'.__('Google Maps Key').':</td></tr><tr class="row_3"><td colspan="2">'.html_print_input_text(
        'gmap_key',
        $gmap_key,
        '',
        90,
        128,
        true
    ).'</td></tr></table>';
    // Image Map Connection.
    $optionsConnectionImageTable = '<table class="databox" border="0" cellpadding="4" cellspacing="4" width="50%"><tr class="row_0"><td>'.__('Image URL').':</td><td colspan="3"><input id="type" type="hidden" name="type" value="Static_Image" />'.html_print_input_text(
        'url',
        $mapConnectionDataUrl,
        '',
        45,
        90,
        true
    ).'</td></tr><tr class="row_1"><td colspan="4"><strong>'.__('Corners of the area of the image').':</strong></td></tr><tr class="row_2"><td>'.__('Left').':</td><td>'.html_print_input_text(
        'bb_left',
        $bb_left,
        '',
        25,
        25,
        true
    ).'</td><td>'.__('Bottom').':</td><td>'.html_print_input_text(
        'bb_bottom',
        $bb_bottom,
        '',
        25,
        25,
        true
    ).'</td></tr><tr class="row_3"><td>'.__('Right').':</td><td>'.html_print_input_text(
        'bb_right',
        $bb_right,
        '',
        25,
        25,
        true
    ).'</td><td>'.__('Top').':</td><td>'.html_print_input_text(
        'bb_top',
        $bb_top,
        '',
        25,
        25,
        true
    ).'</td></tr><tr class="row_4"><td colspan="4"><strong>'.__('Image Size').':</strong></td></tr><tr class="row_5"><td>'.__('Width').':</td><td>'.html_print_input_text(
        'image_width',
        $image_width,
        '',
        25,
        25,
        true
    ).'</td><td>'.__('Height').':</td><td>'.html_print_input_text(
        'image_height',
        $image_height,
        '',
        25,
        25,
        true
    ).'</td></tr></table>';

        // WMS Server Connection.
        $optionsConnectionWMSTable = '<table class="databox" border="0" cellpadding="4" cellspacing="4" width="50%"><tr class="row_0"><td>'.__('WMS Server URL').'</td><td><input id="type" type="hidden" name="type" value="WMS" />'.html_print_input_text(
            'url',
            $mapConnectionDataUrl,
            '',
            90,
            255,
            true
        ).'</td></tr><tr class="row_1"><td>'.__('Layers').'</td><td>'.html_print_input_text(
            'layers',
            $layers,
            '',
            90,
            255,
            true
        ).'</td></tr></table>';

        if ($mapConnectionData != null) {
            switch ($mapConnection_type) {
                case 'OSM':
                    $optionsConnectionTypeTable = $optionsConnectionOSMTable;
                break;

                case 'Gmap':
                    $optionsConnectionTypeTable = $optionsConnectionGmapTable;
                break;

                case 'Static_Image':
                    $optionsConnectionTypeTable = $optionsConnectionImageTable;
                break;

                case 'WMS':
                    $optionsConnectionTypeTable = $optionsConnectionWMSTable;
                break;

                default:
                    // Default.
                break;
            }
        }

            echo "<div id='form_map_connection_type'>".$optionsConnectionTypeTable.'</div>';

            echo '<h4>'.__('Preview to select the center of the map and the default position of an agent without gis data').'</h4><br>';
            html_print_button(__('Load preview map'), 'button_refresh', false, 'refreshMapView();', 'class="sub next"');
            echo '<br /><br />';
            echo "<div id='map' class='map_gis_step2'></div>";

            $table->width = '60%';
            $table->data = [];

            // $table->colspan[0][3] = 3;
            $table->data[0][0] = '';
            $table->data[0][1] = __('Map Center');
            $table->data[0][2] = __('Default position for agents without GIS data');

            $table->data[1][0] = __('Change in the map');
            $table->data[1][1] = html_print_radio_button_extended(
                'radio_button',
                1,
                '',
                1,
                false,
                'changeSetManualPosition(true, false)',
                '',
                true
            );
            $table->data[1][2] = html_print_radio_button_extended(
                'radio_button',
                2,
                '',
                0,
                false,
                'changeSetManualPosition(false, true)',
                '',
                true
            );

            $table->data[2][0] = __('Latitude');
            $table->data[2][1] = html_print_input_text(
                'center_latitude',
                $mapConnection_centerLatitude,
                '',
                10,
                10,
                true
            );
            $table->data[2][2] = html_print_input_text(
                'default_latitude',
                $mapConnection_defaultLatitude,
                '',
                10,
                10,
                true
            );

            $table->data[3][0] = __('Longitude');
            $table->data[3][1] = html_print_input_text(
                'center_longitude',
                $mapConnection_centerLongitude,
                '',
                10,
                10,
                true
            );
            $table->data[3][2] = html_print_input_text(
                'default_longitude',
                $mapConnection_defaultLongitude,
                '',
                10,
                10,
                true
            );

            $table->data[4][0] = __('Altitude');
            $table->data[4][1] = html_print_input_text(
                'center_altitude',
                $mapConnection_centerAltitude,
                '',
                10,
                10,
                true
            );
            $table->data[4][2] = html_print_input_text(
                'default_altitude',
                $mapConnection_defaultAltitude,
                '',
                10,
                10,
                true
            );
            html_print_table($table);

            echo '<div class="action-buttons w90p float-left">';
            html_print_submit_button(__('Save'), '', false, 'class="sub save wand"');
            echo '</div>';
            echo '</form>';

            ui_require_javascript_file('OpenLayers/OpenLayers');
            ui_require_javascript_file('openlayers.pandora');
            ?>
<script type="text/javascript">
var setCenter = true;
var centerPoint = null;
var setGISDefaultPosition = false;
var GISDefaultPositionPoint = null;

/**
 * Set the item to change, the center point or the center default.
 *
 * @param boolean stCenter Set center point for changing.
 * @param boolean stGISDefault Set GISDefault point for changing.
 *
 * @return None
 */
function changeSetManualPosition(stCenter, stGISDefault) {
    setCenter = stCenter;
    setGISDefaultPosition = stGISDefault;
}

/**
 * The callback function when click the map. And make or move the points.
 *
 * @param object e The object of openlayer, that it has the parammeters of click.
 *
 * @return None
 */
function changePoints(e) {
    var lonlat = map.getLonLatFromViewPortPx(e.xy);
    lonlat.transform(map.getProjectionObject(), map.displayProjection); //transform the lonlat in object proyection to "standar proyection"
    
    if (setCenter) {
        //Change the fields
        center_latitude = $('input[name=center_latitude]').val(lonlat.lat);
        center_longitude = $('input[name=center_longitude]').val(lonlat.lon);
        
        if (centerPoint == null) {
            centerPoint = js_addPointExtent('temp_layer', '<?php echo __('Center'); ?>', lonlat.lon, lonlat.lat, <?php echo 'images/dot_green.png'; ?>, 11, 11, 'center', '');
        }
        else  {
            //return to no-standar the proyection for to move
            centerPoint.move(lonlat.transform(map.displayProjection, map.getProjectionObject()));
        }
    }
    
    if (setGISDefaultPosition) {
        //Change the fields
        center_latitude = $('input[name=default_latitude]').val(lonlat.lat);
        center_longitude = $('input[name=default_longitude]').val(lonlat.lon);
        
        if (GISDefaultPositionPoint == null) {
            GISDefaultPositionPoint = js_addPointExtent('temp_layer', '<?php echo __('Default'); ?>', lonlat.lon, lonlat.lat, <?php echo 'images/dot_red.png'; ?>, 11, 11, 'default', '');
        }
        else  {
            //return to no-standar the proyection for to move
            GISDefaultPositionPoint.move(lonlat.transform(map.displayProjection, map.getProjectionObject()));
        }
    }
}

/**
 * Function to show and refresh the map. The function give the params for map of
 * fields. And make two points, center and default.
 */
function refreshMapView() {
    switch ($('#sel_type :selected').val()) {
        case 'Gmap':
            //TODO: Validate there is a key, and use it
            gmap_key = $('input[name=gmap_key]').val();
            var script = document.createElement("script");
            script.type = "text/javascript";
            script.src = 'http://www.google.com/jsapi?key='+gmap_key+'&callback=loadGoogleMap';
                //script.src = 'http://www.google.com/jsapi?key=ABQIAAAAjpkAC9ePGem0lIq5XcMiuhT2yXp_ZAY8_ufC3CFXhHIE1NvwkxTS6gjckBmeABOGXIUiOiZObZESPg&callback=loadGoogleMap';
            document.getElementsByTagName("head")[0].appendChild(script);
            
            //TODO: paint the gif clock for waiting the request.
            break;
        default:
            refreshMapViewSecondStep();
            break;
    }

}

/**
 * Function to show and refresh the map. The function give the params for map of
 * fields. And make two points, center and default.
 */
function refreshMapViewSecondStep() {
    //Clear the previous map.
    map = null;
    $("#map").html('');
    //Clear the points.
    centerPoint = null;
    GISDefaultPositionPoint = null;
    
    //Change the text to button.
    $("input[name=button_refresh]").val('<?php echo __('Refresh preview map'); ?>');
    
    //Obtain data of map of fields.
    inital_zoom = $('input[name=initial_zoom]').val();
    num_levels_zoom =$('input[name=num_levels_zoom]').val();
    center_latitude = $('input[name=center_latitude]').val();
    center_longitude = $('input[name=center_longitude]').val();
    center_altitude = $('input[name=center_altitude]').val();
    
    var objBaseLayers = Array();
    objBaseLayers[0] = {};
    objBaseLayers[0]['type'] = $('select[name=sel_type] :selected').val();
    objBaseLayers[0]['name'] = $('input[name=name]').val();
    objBaseLayers[0]['url'] = $('input[name=url]').val();
    // type Gmap
    objBaseLayers[0]['gmap_type'] = $('select[name=gmap_type] option:selected').val();
    objBaseLayers[0]['gmap_key'] = $('input[name=gmap_key]').val();
    // type Static Image
    objBaseLayers[0]['bb_left'] = $('input[name=bb_left]').val();
    objBaseLayers[0]['bb_right'] = $('input[name=bb_right]').val();
    objBaseLayers[0]['bb_bottom'] = $('input[name=bb_bottom]').val();
    objBaseLayers[0]['bb_top'] = $('input[name=bb_top]').val();
    objBaseLayers[0]['image_width'] = $('input[name=image_width]').val();
    objBaseLayers[0]['image_height'] = $('input[name=image_height]').val();
    // type WMS
    objBaseLayers[0]['layers'] = $('input[name=layers]').val();
    
    arrayControls = null;
    arrayControls = Array('Navigation', 'PanZoom', 'MousePosition');
    
    js_printMap('map', inital_zoom, center_latitude, center_longitude, objBaseLayers, arrayControls);
    
    layer = js_makeLayer('temp_layer', true, null);
    
    centerPoint = js_addPointExtent('temp_layer',
        '<?php echo __('Center'); ?>',
        $('input[name=center_longitude]').val(),
        $('input[name=center_latitude]').val(),
        'images/gis_map/icons/circle.default.png', 11, 11, 'center', '');
    GISDefaultPositionPoint = js_addPointExtent('temp_layer',
        '<?php echo __('Default'); ?>',
        $('input[name=default_longitude]').val(),
        $('input[name=default_latitude]').val(),
        'images/gis_map/icons/cross.default.png', 11, 11, 'default', '');
    
    js_activateEvents(changePoints);
}

function validateGmapsParamtres () {
    gmap_key = $('input[name=gmap_key]').val();
    if (gmap_key == "") {
        $('input[name=gmap_key]').css('background-color', 'red');
    }
    else {
        refreshMapViewSecondStep();
    }
}

function loadGoogleMap() {
    google.load("maps", "2", {"callback" : validateGmapsParamtres});
}

/**
 * Dinamic write the fields in form when select a type of connection.
 */
function selMapConnectionType() {
    $('#form_map_connection_type').fadeOut("normal");
    
    switch ($('#sel_type :selected').val()) {
        case 'OSM':
            $('#form_map_connection_type').html('<?php echo $optionsConnectionOSMTable; ?>').hide();
            break;
        case 'Gmap':
            $('#form_map_connection_type').html('<?php echo $optionsConnectionGmapTable; ?>').hide();
            break;
        case 'Static_Image':
            $('#form_map_connection_type').html('<?php echo $optionsConnectionImageTable; ?>').hide();
            break;
        case 'WMS':
            $('#form_map_connection_type').html('<?php echo $optionsConnectionWMSTable; ?>').hide();
            break;
        default:
            $('#form_map_connection_type').html('').hide();
            break;
    }
    $('#form_map_connection_type').fadeIn("normal");
}
</script>
