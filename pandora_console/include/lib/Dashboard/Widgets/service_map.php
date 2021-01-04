<?php
/**
 * Widget Service map Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Widget Service map
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

/**
 * Service map Widgets.
 */
class ServiceMapWidget extends Widget
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
     * Widget ID.
     *
     * @var integer
     */
    protected $widgetId;

    /**
     * Dashboard ID.
     *
     * @var integer
     */
    protected $dashboardId;


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

        // Includes.
        enterprise_include_once('/include/functions_services.php');
        enterprise_include_once('/include/functions_servicemap.php');

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

        // Widget ID.
        $this->widgetId = $widgetId;

        // Dashboard ID.
        $this->dashboardId = $dashboardId;

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
        $this->title = __('Service Map');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'service_map';
        }

        // This forces at least a first configuration.
        $this->configurationRequired = false;
        if (empty($this->values['serviceId']) === true) {
            $this->configurationRequired = true;
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

        if (isset($decoder['service_id']) === true) {
            $values['serviceId'] = $decoder['service_id'];
        }

        if (isset($decoder['serviceId']) === true) {
            $values['serviceId'] = $decoder['serviceId'];
        }

        if (isset($decoder['show_legend']) === true) {
            $values['showLegend'] = (int) $decoder['show_legend'];
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

        $services_res = services_get_services();
        $services = [0 => __('None')];
        if ($services_res !== false) {
            $fields = array_reduce(
                $services_res,
                function ($carry, $item) {
                    $carry[$item['id']] = $item['name'];
                    return $carry;
                },
                []
            );
        }

        $inputs[] = [
            'label' => \ui_print_info_message(
                __('ZOOM functionality is only available when there is only one such widget in the dashboard'),
                '',
                true
            ),
        ];

        $inputs[] = [
            'label'     => __('Service'),
            'arguments' => [
                'type'          => 'select',
                'fields'        => $fields,
                'name'          => 'serviceId',
                'selected'      => $values['serviceId'],
                'return'        => true,
                'nothing'       => __('None'),
                'nothing_value' => 0,
            ],
        ];

        // TODO refactoriced services: Hidden legend.
        /*
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
        */

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

        $values['serviceId'] = \get_parameter('serviceId', 0);
        // $values['showLegend'] = \get_parameter_switch('showLegend');
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

        if (check_acl($config['id_user'], 0, 'AR') === 0) {
            $output .= '<div class="container-center">';
            $output .= \ui_print_error_message(
                __('The user doesn\'t have permission to read agents'),
                '',
                true
            );
            $output .= '</div>';
            return $output;
        }

        if (empty($this->values['serviceId']) === true) {
            $output .= '<div class="container-center">';
            $output = ui_print_error_message(
                __('Missing Service id'),
                '',
                true
            );
            $output .= '</div>';
            return $output;
        }

        $containerId = 'container_servicemap_'.$this->values['serviceId'].'_'.$this->cellId;
        $style = 'position: relative; text-align: center;';
        $output .= "<div id='".$containerId."' style='".$style."'>";

        // TODO refactoriced services: Hidden legend.
        /*
            if ($this->values['showLegend'] === 1) {
            $output .= "<div id='container_servicemap_legend".$this->values['serviceId'].'_'.$this->cellId."'>";
            $output .= '<table>';
            $output .= "<tr class='legend_servicemap_title'><td colspan='3' style='padding-bottom: 10px; min-width: 177px;'><b>".__('Legend').'</b></td>';
            $output .= "<td><img class='legend_servicemap_toggle' style='padding-bottom: 10px;' src='images/darrowup.png'></td></tr>";

            $output .= "<tr class='legend_servicemap_item'><td>";
            $output .= "<img src='images/service.png'>";
            $output .= '</td><td>'.__('Services').'</td>';

            // Coulour legend.
            $output .= "<td rowspan='3'>";
            $output .= '<table>';
            $output .= "<tr><td class='legend_square'><div style='background-color: ".COL_CRITICAL.";'></div></td><td>".__('Critical').'</td></tr>';
            $output .= "<tr><td class='legend_square'><div style='background-color: ".COL_WARNING.";'></div></td><td>".__('Warning').'</td></tr>';
            $output .= "<tr><td class='legend_square'><div style='background-color: ".COL_NORMAL.";'></div></td><td>".__('Ok').'</td></tr>';
            $output .= "<tr><td class='legend_square'><div style='background-color: ".COL_UNKNOWN.";'></div></td><td>".__('Unknown').'</td></tr>';
            $output .= '</table>';
            $output .= '</td></tr>';

            $output .= "<tr class='legend_servicemap_item'><td>";
            $output .= "<img src='images/agent.png'>";
            $output .= '</td><td>'.__('Agents').'</td>';
            $output .= '</tr>';

            $output .= "<tr class='legend_servicemap_item'><td>";
            $output .= "<img src='images/module.png'>";
            $output .= '</td><td>'.__('Modules').'</td>';
            $output .= '</tr>';
            $output .= '</table>';
            $output .= '</div>';
            }
        */

        // TODO: removed refactoriced services. Only 1 widget Zoom.
        $sql = sprintf(
            'SELECT COUNT(*)
            FROM twidget_dashboard
            WHERE id_dashboard = %s
                AND id_widget = %s',
            $this->dashboardId,
            $this->widgetId
        );
        $countDashboardServices = \db_get_value_sql($sql);
        $disableZoom = false;
        if ($countDashboardServices > 1) {
            $disableZoom = true;
        }

        $output .= html_print_input_hidden(
            'full_url_dashboard_map',
            $config['homeurl'],
            true
        );
        // TODO:XXX fix draw service map.
        ob_start();
        servicemap_print_servicemap(
            $this->values['serviceId'],
            false,
            $size['width'],
            $size['height'],
            $this->cellId,
            $disableZoom
        );
        $output .= ob_get_clean();
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
        return __('Service map');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'service_map';
    }


}
