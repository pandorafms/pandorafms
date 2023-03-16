<?php
/**
 * Widget Network map Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Widget Network map
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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

use PandoraFMS\Dashboard\Manager;
use PandoraFMS\Enterprise\Metaconsole\Node;

/**
 * Network map Widgets.
 */
class NetworkMapWidget extends Widget
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
     * Grid Width.
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

        include_once $config['homedir'].'/include/functions_networkmap.php';

        // WARNING: Do not edit. This chunk must be in the constructor.
        parent::__construct(
            $cellId,
            $dashboardId,
            $widgetId
        );

        // Cell Id.
        $this->cellId = $cellId;

        // Width.
        $this->width = $width;

        // Height.
        $this->height = $height;

        // Grid Width.
        $this->gridWidth = $gridWidth;

        // Options.
        $this->values = $this->decoders($this->getOptionsWidget());

        // Positions.
        $this->position = $this->getPositionWidget();

        // Page.
        $this->page = basename(__FILE__);

        // ClassName.
        $class = new \ReflectionClass($this);
        $this->className = $class->getShortName();

        // Title.
        $this->title = __('Network map');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'network_map';
        }

        // This forces at least a first configuration.
        $this->configurationRequired = false;
        if (empty($this->values['networkmapId']) === true) {
            $this->configurationRequired = true;
        } else {
            try {
                if (is_metaconsole() === true
                    && $this->values['node'] > 0
                ) {
                    $node = new Node($this->values['node']);
                    $node->connect();
                }

                // Reports.
                $check_exist = db_get_value(
                    'id',
                    'tmap',
                    'id',
                    $this->values['networkmapId']
                );
            } catch (\Exception $e) {
                // Unexistent agent.
                if (is_metaconsole() === true
                    && $this->values['node'] > 0
                ) {
                    $node->disconnect();
                }

                $check_exist = false;
            } finally {
                if (is_metaconsole() === true
                    && $this->values['node'] > 0
                ) {
                    $node->disconnect();
                }
            }

            if ($check_exist === false) {
                $this->loadError = true;
            }
        }

        $this->overflow_scrollbars = false;
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

        if (isset($decoder['networkmaps']) === true) {
            $values['networkmapId'] = $decoder['networkmaps'];
        }

        if (isset($decoder['node']) === true) {
            $values['node'] = $decoder['node'];
        }

        if (isset($decoder['networkmapId']) === true) {
            $values['networkmapId'] = $decoder['networkmapId'];
        }

        if (isset($decoder['map_translate_x']) === true) {
            $values['xOffset'] = $decoder['map_translate_x'];
        }

        if (isset($decoder['xOffset']) === true) {
            $values['xOffset'] = $decoder['xOffset'];
        }

        if (isset($decoder['map_translate_y']) === true) {
            $values['yOffset'] = $decoder['map_translate_y'];
        }

        if (isset($decoder['yOffset']) === true) {
            $values['yOffset'] = $decoder['yOffset'];
        }

        if (isset($decoder['zoom_level_dash']) === true) {
            $values['zoomLevel'] = $decoder['zoom_level_dash'];
        }

        if (isset($decoder['zoomLevel']) === true) {
            $values['zoomLevel'] = $decoder['zoomLevel'];
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
        $values = $this->values;

        // Retrieve global - common inputs.
        $inputs = parent::getFormInputs();

        $inputs[] = [
            'label' => \ui_print_info_message(
                __('It is recommended to have only one such widget in the control panel.'),
                '',
                true
            ),
        ];

        // Default values.
        if (isset($values['xOffset']) === false) {
            $values['xOffset'] = 0;
        }

        if (isset($values['yOffset']) === false) {
            $values['yOffset'] = 0;
        }

        if (isset($values['zoomLevel']) === false) {
            $values['zoomLevel'] = 0.5;
        }

        $return_all_group = false;

        if (users_can_manage_group_all('RM')) {
            $return_all_group = true;
        }

        // Selected.
        $selected = $values['networkmapId'];
        if ((bool) is_metaconsole() === true) {
            $selected = $values['node'].'_'.$values['networkmapId'];
        } else {
            $selected = $values['networkmapId'];
        }

        // Map.
        $fields = \networkmap_get_networkmaps(
            null,
            null,
            true,
            $return_all_group
        );

        // If currently selected networkmap is not included in fields array
        // (it belongs to a group over which user has no permissions), then add
        // it to fields array.
        if ($values['networkmapId'] !== null
            && array_key_exists($selected, $fields) === false
        ) {
            if ((bool) is_metaconsole() === true) {
                metaconsole_connect(null, $values['nodeId']);
            }

            $selected_networkmap = db_get_value(
                'id',
                'tmap',
                $values['networkmapId']
            );

            if ((bool) is_metaconsole() === true) {
                metaconsole_restore_db();
            }
        }

        $inputs[] = [
            'label'     => __('Map'),
            'arguments' => [
                'type'          => 'select',
                'fields'        => $fields,
                'name'          => 'networkmapId',
                'selected'      => $selected,
                'return'        => true,
                'nothing'       => __('None'),
                'nothing_value' => 0,
            ],
        ];

        // X offset.
        $help = ui_print_help_tip(
            __('Introduce x-axis data. Right=positive Left=negative'),
            true
        );
        $inputs[] = [
            'label'     => __('X offset').$help,
            'arguments' => [
                'name'   => 'xOffset',
                'type'   => 'number',
                'value'  => $values['xOffset'],
                'return' => true,
            ],
        ];

        // Y offset.
        $help = ui_print_help_tip(
            __('Introduce Y-axis data. Top=positive Bottom=negative'),
            true
        );
        $inputs[] = [
            'label'     => __('Y offset').$help,
            'arguments' => [
                'name'   => 'yOffset',
                'type'   => 'number',
                'value'  => $values['yOffset'],
                'return' => true,
            ],
        ];

        // Zoom level.
        $fields = [
            '0.1' => 'x1',
            '0.2' => 'x2',
            '0.3' => 'x3',
            '0.4' => 'x4',
            '0.5' => 'x5',
            '0.6' => 'x6',
            '0.7' => 'x7',
            '0.8' => 'x8',
            '0.9' => 'x9',
            '1'   => 'x10',
        ];

        $inputs[] = [
            'label'     => __('Zoom level').$help,
            'arguments' => [
                'type'     => 'select',
                'fields'   => $fields,
                'name'     => 'zoomLevel',
                'selected' => $values['zoomLevel'],
                'return'   => true,
            ],
        ];

        return $inputs;
    }


    /**
     * Get Post for widget.
     *
     * @return array
     */
    public function getPost():array
    {
        // Retrieve global - common inputs.
        $values = parent::getPost();

        $nmId = \get_parameter('networkmapId', null);

        if ($nmId !== null) {
            if ((bool) is_metaconsole() === true) {
                $mc_stuff = explode('_', $nmId);
                $values['node'] = $mc_stuff[0];
                $values['networkmapId'] = $mc_stuff[1];
            } else {
                $values['networkmapId'] = $nmId;
            }
        }

        $values['xOffset'] = \get_parameter('xOffset', 0);
        $values['yOffset'] = \get_parameter('yOffset', 0);
        $values['zoomLevel'] = (float) \get_parameter('zoomLevel', 0.5);

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

        $size = parent::getSize();

        $id_networkmap = $this->values['networkmapId'];
        $x_offset = $this->values['xOffset'];
        $y_offset = $this->values['yOffset'];
        $zoom_dash = $this->values['zoomLevel'];
        $node = ($this->values['node'] ?? '');

        $hash = md5($config['dbpass'].$id_networkmap.$config['id_user'].$node);

        $style = 'width:'.$size['width'].'px; height:'.$size['height'].'px;';
        $id = 'body_cell-'.$this->cellId;
        $output = '<div class="body_cell" id="'.$id.'" style="'.$style.'"><div>';

        $settings = \json_encode(
            [
                'cellId'        => $this->cellId,
                'page'          => 'include/ajax/map_enterprise.ajax',
                'url'           => ui_get_full_url('ajax.php'),
                'networkmap_id' => $id_networkmap,
                'x_offset'      => $x_offset,
                'y_offset'      => $y_offset,
                'zoom_dash'     => $zoom_dash,
                'id_user'       => $config['id_user'],
                'auth_class'    => 'PandoraFMS\Dashboard\Manager',
                'auth_hash'     => Manager::generatePublicHash(),
                'node'          => $node,
                'size'          => $size,
            ]
        );

        $output .= '<script type="text/javascript">';
        $output .= '$(document).ready(function () {';
        $output .= 'dashboardLoadNetworkMap('.$settings.');';
        $output .= '})';
        $output .= '</script>';

        return $output;
    }


    /**
     * Get description.
     *
     * @return string.
     */
    public static function getDescription()
    {
        return __('Network map');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'network_map';
    }


    /**
     * Get size Modal Configuration.
     *
     * @return array
     */
    public function getSizeModalConfiguration(): array
    {
        $size = [
            'width'  => 400,
            'height' => 430,
        ];

        return $size;
    }


}
