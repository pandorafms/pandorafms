<?php
/**
 * Widget agiss map Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Widget
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2024 Artica Soluciones Tecnologicas
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

namespace PandoraFMS\Dashboard;

use PandoraFMS\Enterprise\Metaconsole\Node;

global $config;

/**
 * URL Widgets
 */
class GisMap extends Widget
{

    /**
     * Name widget.
     *
     * @var string
     */
    protected $name;

    /**
     * Title widget.
     *
     * @var string
     */
    protected $title;

    /**
     * Page widget;
     *
     * @var string
     */
    protected $page;

    /**
     * Class name widget.
     *
     * @var [type]
     */
    protected $className;

    /**
     * Values options for each widget.
     *
     * @var [type]
     */
    protected $values;

    /**
     * Configuration required.
     *
     * @var boolean
     */
    protected $configurationRequired;

    /**
     * Error load widget.
     *
     * @var boolean
     */
    protected $loadError;

    /**
     * Width.
     *
     * @var integer
     */
    protected $width;

    /**
     * Heigth.
     *
     * @var integer
     */
    protected $height;

    /**
     * Grid Width.
     *
     * @var integer
     */
    protected $gridWidth;

    /**
     * Cell ID.
     *
     * @var integer
     */
    protected $cellId;


    /**
     * Construct.
     *
     * @param integer      $cellId      Cell ID.
     * @param integer      $dashboardId Dashboard ID.
     * @param integer      $widgetId    Widget ID.
     * @param integer|null $width       New width.
     * @param integer|null $height      New height.
     * @param integer|null $gridWidth   Grid width.
     */
    public function __construct(
        int $cellId,
        int $dashboardId=0,
        int $widgetId=0,
        ?int $width=0,
        ?int $height=0,
        ?int $gridWidth=0
    ) {
        global $config;

        // WARNING: Do not edit. This chunk must be in the constructor.
        parent::__construct(
            $cellId,
            $dashboardId,
            $widgetId
        );

        // Width.
        $this->width = $width;

        // Height.
        $this->height = $height;

        // Grid Width.
        $this->gridWidth = $gridWidth;

        // Cell Id.
        $this->cellId = $cellId;

        // Options.
        $this->values = $this->decoders($this->getOptionsWidget());

        // Page.
        $this->page = basename(__FILE__);

        // ClassName.
        $class = new \ReflectionClass($this);
        $this->className = $class->getShortName();

        // Title.
        $this->title = __('Gis map');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'GisMap';
        }

        // This forces at least a first configuration.
        $this->configurationRequired = false;
        if (empty($this->values['gis_map']) === true) {
            $this->configurationRequired = true;
        }
    }


    /**
     * Decoders hack for retrocompability.
     *
     * @param array $decoder Values.
     *
     * @return array Returns the values ​​with the correct key.
     */
    public function decoders(array $decoder): array
    {
        $values = [];
        // Retrieve global - common inputs.
        $values = parent::decoders($decoder);

        if (isset($decoder['gis_map']) === true) {
            $values['gis_map'] = $decoder['gis_map'];
        }

        return $values;
    }


    /**
     * Generates inputs for form (specific).
     *
     * @return array Of inputs.
     *
     * @throws Exception On error.
     */
    public function getFormInputs(): array
    {
        global $config;

        include_once $config['homedir'].'/include/functions_gis.php';

        // Retrieve global - common inputs.
        $inputs = parent::getFormInputs();

        if ((bool) $config['activate_gis'] === true) {
            $maps = gis_get_maps();
        }

        $array_map = [];
        foreach ($maps as $map) {
            if (check_acl($config['id_user'], $map['group_id'], 'MR') === false
                && check_acl($config['id_user'], $map['group_id'], 'MW') === false
                && check_acl($config['id_user'], $map['group_id'], 'MM') === false
            ) {
                continue;
            }

            $array_map[$map['id_tgis_map']] = $map['map_name'];
        }

        // Filters.
        $inputs[] = [
            'class'     => 'flex flex-row',
            'label'     => __('GIS maps'),
            'arguments' => [
                'type'     => 'select',
                'fields'   => $array_map,
                'name'     => 'gis_map',
                'return'   => true,
                'selected' => ($this->values['gis_map'] === null) ? 0 : $this->values['gis_map'],
            ],
        ];

        return $inputs;
    }


    /**
     * Get Post for widget.
     *
     * @return array
     */
    public function getPost(): array
    {
        // Retrieve global - common inputs.
        $values = parent::getPost();

        $values['gis_map'] = \get_parameter('gis_map', 0);

        return $values;
    }


    /**
     * Draw widget.
     *
     * @return string;
     */
    public function load()
    {
        global $config;

        include_once $config['homedir'].'/include/functions_gis.php';
        include_once $config['homedir'].'/include/functions_agents.php';

        \ui_require_javascript_file('openlayers.pandora', 'include/javascript/', true);
        \ui_require_javascript_file('OpenLayers/OpenLayers', 'include/javascript/', true);

        $map = db_get_row('tgis_map', 'id_tgis_map', $this->values['gis_map']);

        $output = '';
        if (check_acl($config['id_user'], $map['group_id'], 'MR') === false
            && check_acl($config['id_user'], $map['group_id'], 'MW') === false
            && check_acl($config['id_user'], $map['group_id'], 'MM') === false
        ) {
            $output .= '<div class="container-center">';
            $output .= ui_print_error_message(
                __('You don\'t have access'),
                '',
                true
            );
            $output .= '</div>';
            return $output;
        }

        $confMap = gis_get_map_conf($this->values['gis_map']);

        // Default open map (used to overwrite unlicensed google map view).
        $confMapDefault = get_good_con();
        $confMapDefaultFull = json_decode($confMapDefault['conection_data'], true);
        $confMapUrlDefault = $confMapDefaultFull['url'];

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
                            // If there is not gmap_key, show the default view.
                            $baselayers[$num_baselayer]['url'] = $confMapUrlDefault;
                            $baselayers[$num_baselayer]['typeBaseLayer'] = 'OSM';
                        } else {
                            $baselayers[$num_baselayer]['gmap_type'] = $decodeJSON['gmap_type'];
                            $baselayers[$num_baselayer]['gmap_key'] = $decodeJSON['gmap_key'];
                            $gmap_key = $decodeJSON['gmap_key'];
                            // Once a Gmap base layer is found we mark it to import the API.
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

                    default:
                        // Do nothing.
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

        $layers = gis_get_layers($this->values['gis_map']);

        $output .= '<div id="map_'.$this->cellId.'" style="width: 100%; height: 100%" />';
        gis_print_map(
            'map_'.$this->cellId,
            $map['zoom_level'],
            $map['initial_latitude'],
            $map['initial_longitude'],
            $baselayers,
            $controls
        );
        $output .= '</div>';

        if (empty($layers) === false) {
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
                    if (empty($groupsByAgentId[$idAgent]) === false) {
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

            $timestampLastOperation = db_get_value_sql('SELECT UNIX_TIMESTAMP()');

            gis_activate_select_control();
            gis_activate_ajax_refresh($layers, $timestampLastOperation);
        }

        return $output;
    }


    /**
     * Get description.
     *
     * @return string.
     */
    public static function getDescription()
    {
        return __('GIS map');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'GisMap';
    }


    /**
     * Get size Modal Configuration.
     *
     * @return array
     */
    public function getSizeModalConfiguration(): array
    {
        $size = [
            'width'  => 500,
            'height' => 300,
        ];

        return $size;
    }


}
