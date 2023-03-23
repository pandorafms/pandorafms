<?php
/**
 * Widget Custom graph Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Widget Custom graph
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

use PandoraFMS\Enterprise\Metaconsole\Node;

/**
 * Custom graph Widgets
 */
class CustomGraphWidget extends Widget
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

        // Positions.
        $this->position = $this->getPositionWidget();

        // Page.
        $this->page = basename(__FILE__);

        // ClassName.
        $class = new \ReflectionClass($this);
        $this->className = $class->getShortName();

        // Title.
        $this->title = __('Defined custom graph');

        // Name.
        $this->name = 'custom_graph';

        // Don't forget to include here.
        // the headers needed for any configuration file.
        include_once $config['homedir'].'/include/functions_custom_graphs.php';

        // This forces at least a first configuration.
        $this->configurationRequired = false;
        if (empty($this->values['id_graph']) === true) {
            $this->configurationRequired = true;
        } else {
            try {
                if (is_metaconsole() === true
                    && $this->values['node'] > 0
                ) {
                    $node = new Node($this->values['node']);
                    $node->connect();
                }

                $check_exist = \db_get_value(
                    'name',
                    'tgraph',
                    'id_graph',
                    $this->values['id_graph']
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

        if (isset($decoder['id_graph']) === true) {
            $values['id_graph'] = $decoder['id_graph'];
        }

        if (isset($decoder['node']) === true) {
            $values['node'] = $decoder['node'];
        }

        if (isset($decoder['stacked']) === true) {
            $values['type'] = $decoder['stacked'];
        }

        if (isset($decoder['type']) === true) {
            $values['type'] = $decoder['type'];
        }

        if (isset($decoder['period']) === true) {
            $values['period'] = $decoder['period'];
        }

        if (isset($decoder['showLegend']) === true) {
            $values['showLegend'] = $decoder['showLegend'];
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

        // Default values.
        if (isset($values['period']) === false) {
            $values['period'] = SECONDS_1DAY;
        }

        if (isset($values['showLegend']) === false) {
            $values['showLegend'] = 1;
        }

        $return_all_group = false;

        if (users_can_manage_group_all('RM') === true) {
            $return_all_group = true;
        }

        // Custom graph.
        $fields = \custom_graphs_get_user(0, false, $return_all_group);

        if ((bool) is_metaconsole() === true) {
            $selected = $values['node'].'|'.$values['id_graph'];
        } else {
            $selected = $values['id_graph'];
        }

        // If currently selected graph is not included in fields array
        // (it belongs to a group over which user has no permissions),
        // then user has no grants over this item.
        if ($values['id_graph'] !== null
            && array_key_exists($selected, $fields) === false
        ) {
            if ((bool) is_metaconsole() === true) {
                $server_name = \db_get_value(
                    'server_name',
                    'tmetaconsole_setup',
                    'id',
                    $values['node']
                );

                metaconsole_connect(null, $values['node']);
            }

            $name = \db_get_value(
                'name',
                'tgraph',
                'id_graph',
                $values['id_graph']
            );

            if ((bool) is_metaconsole() === true) {
                metaconsole_restore_db();
            }

            if ($name === false) {
                $name = \__('This graph has been deleted.');
            } else {
                $name .= ' ('.$server_name.')';
            }

            $fields[$selected] = $name;
        }

        $inputs[] = [
            'label'     => __('Graph'),
            'arguments' => [
                'type'     => 'select',
                'fields'   => $fields,
                'name'     => 'id_graph',
                'selected' => $selected,
                'return'   => true,
            ],
        ];

        // Type charts.
        $fields = [
            CUSTOM_GRAPH_AREA         => __('Area'),
            CUSTOM_GRAPH_STACKED_AREA => __('Stacked area'),
            CUSTOM_GRAPH_LINE         => __('Line'),
            CUSTOM_GRAPH_STACKED_LINE => __('Stacked line'),
            CUSTOM_GRAPH_BULLET_CHART => __('Bullet chart'),
            CUSTOM_GRAPH_GAUGE        => __('Gauge'),
            CUSTOM_GRAPH_HBARS        => __('Horizontal Bars'),
            CUSTOM_GRAPH_VBARS        => __('Vertical Bars'),
            CUSTOM_GRAPH_PIE          => __('Pie'),
        ];

        $inputs[] = [
            'label'     => __('Type'),
            'arguments' => [
                'type'     => 'select',
                'fields'   => $fields,
                'name'     => 'type',
                'selected' => $values['type'],
                'return'   => true,
            ],
        ];

        // Show legend.
        $inputs[] = [
            'label'     => __('Show legend'),
            'arguments' => [
                'name'  => 'showLegend',
                'id'    => 'showLegend',
                'type'  => 'switch',
                'value' => $values['showLegend'],
            ],
        ];

        // Period.
        $inputs[] = [
            'label'     => __('Interval'),
            'arguments' => [
                'name'          => 'period',
                'type'          => 'interval',
                'value'         => $values['period'],
                'nothing'       => __('None'),
                'nothing_value' => 0,
                'style_icon'    => 'flex-grow: 0',
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
        $id_graph = \get_parameter('id_graph', null);

        if ($id_graph !== null) {
            $values['id_graph'] = $id_graph;

            if ((bool) is_metaconsole() === true) {
                $mc_stuff = explode('|', $values['id_graph']);
                $values['node'] = $mc_stuff[0];
                $values['id_graph'] = $mc_stuff[1];
            }

            // VERIFY ACCESS.
            $return_all_group = false;

            if (users_can_manage_group_all('RM') === true) {
                $return_all_group = true;
            }

            $availables = \custom_graphs_get_user(0, false, $return_all_group);

            if ((bool) is_metaconsole() === true) {
                $selected = $values['node'].'|'.$values['id_graph'];
            } else {
                $selected = $values['id_graph'];
            }

            // If currently selected graph is not included in fields array
            // (it belongs to a group over which user has no permissions),
            // then user has no grants over this item.
            if ($values['id_graph'] !== null
                && array_key_exists($selected, $availables) === false
            ) {
                // User has no access to this graph.
                // Keep previous definition if not grant over desired item.
                $values['node'] = $this->values['node'];
                $values['id_graph'] = $this->values['id_graph'];
            }
        } else {
            // Keep previous definition if not grant over desired item.
            $values['node'] = $this->values['node'];
            $values['id_graph'] = $this->values['id_graph'];
        }

        $values['type'] = \get_parameter('type', 0);
        $values['period'] = \get_parameter('period', 0);
        $values['showLegend'] = \get_parameter_switch('showLegend');

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

        switch ($this->values['type']) {
            case CUSTOM_GRAPH_STACKED_LINE:
            case CUSTOM_GRAPH_STACKED_AREA:
            case CUSTOM_GRAPH_AREA:
            case CUSTOM_GRAPH_LINE:
                if ($this->values['showLegend'] === 1) {
                    $sources = db_get_all_rows_field_filter(
                        'tgraph_source',
                        'id_graph',
                        $this->values['id_graph'],
                        'field_order'
                    );

                    $hackLegendHight = (30 * count($sources));
                    $operation = ($size['height'] - 10 - $hackLegendHight);
                    if ($hackLegendHight < $operation) {
                        $height = ($size['height'] - $hackLegendHight);
                    } else {
                        $height = ($size['height'] - 10);
                    }
                } else {
                    $height = ($size['height'] - 10);
                }

                $output = '<div class="container-center">';
            break;

            case CUSTOM_GRAPH_VBARS:
                $style = 'padding: 10px;';
                $height = $size['height'];
                $output = '<div class="container-center" style="'.$style.'">';
            break;

            case CUSTOM_GRAPH_GAUGE:
                $height = $size['height'];
                $output = '<div class="container-gauges-dashboard">';
            break;

            default:
                $height = $size['height'];
                $output = '<div class="container-center">';
            break;
        }

        // Not posible height < 0.
        if ($height <= 0) {
            $height = 10;
        }

        $params = [
            'period'          => $this->values['period'],
            'width'           => ($size['width']),
            'height'          => $height,
            'only_image'      => false,
            'homeurl'         => $config['homeurl'],
            'backgroundColor' => 'transparent',
            'menu'            => false,
            'show_legend'     => $this->values['showLegend'],
            'vconsole'        => true,
            'dashboard'       => true,
        ];

        $params_combined = [
            'stacked'  => (int) $this->values['type'],
            'id_graph' => (int) $this->values['id_graph'],
        ];

        $output .= graphic_combined_module(
            false,
            $params,
            $params_combined
        );
        $output .= '</div>';

        return $output;
    }


    /**
     * Get description.
     *
     * @return string.
     */
    public static function getDescription()
    {
        return __('Defined custom graph');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'custom_graph';
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
            'height' => 440,
        ];

        return $size;
    }


}
