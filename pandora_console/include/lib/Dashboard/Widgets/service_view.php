<?php
/**
 * Widget Tree view Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Widget Tree view
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

/**
 * Tree view Widgets.
 */
class ServiceViewWidget extends Widget
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
     * Dashboard ID.
     *
     * @var integer
     */
    protected $dashboardId;

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

        ui_require_css_file('tree');
        ui_require_css_file('fixed-bottom-box');

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
        $this->title = __('Service View');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'service_view';
        }

        // This forces at least a first configuration.
        $this->configurationRequired = false;

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

        if (isset($decoder['type']) === true) {
            $values['type'] = $decoder['type'];
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
        // Retrieve global - common inputs.
        $inputs = parent::getFormInputs();

        $values = $this->values;
        if (empty($values['type']) === true) {
            $values['type'] = 'tree';
        }

        // Type services view.
        $fields = [
            'tree'  => __('Tree'),
            'table' => __('Table'),
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
        $values['type'] = \get_parameter('type', 'tree');

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

        $values = $this->values;

        $size = parent::getSize();
        $output = '';

        if ($values['type'] === 'tree' || empty($values['type'])) {
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

            $containerId = 'container_servicemap_'.$this->cellId;
            $output .= "<div id='".$containerId."' class='tree-controller-recipient'>";
            $output .= '</div>';

            $output .= \html_print_image(
                'images/spinner.gif',
                true,
                [
                    'class' => 'loading_tree',
                    'style' => 'display: none;',
                ]
            );

            // Css Files.
            \ui_require_css_file('tree', 'include/styles/', true);
            if ($config['style'] == 'pandora_black' && !is_metaconsole()) {
                \ui_require_css_file('pandora_black', 'include/styles/', true);
            }

            \ui_require_javascript_file(
                'TreeController',
                'include/javascript/tree/',
                true
            );

            \ui_require_javascript_file(
                'fixed-bottom-box',
                'include/javascript/',
                true
            );

            $settings['cellId'] = $this->cellId;
            $settings['baseURL'] = \ui_get_full_url('/', false, false, false);
            $settings['ajaxURL'] = \ui_get_full_url('ajax.php', false, false, false);

            // Show the modal window of an module.
            $output .= '<div id="module_details_window" class="">';
            $output .= '</div>';

            // Script.
            $output .= '<script type="text/javascript">';
            $output .= 'processServiceTree('.json_encode($settings).');';
            $output .= '</script>';
        } else {
            $own_info = \get_user_info($config['id_user']);
            if ($own_info['is_admin'] || check_acl($config['id_user'], 0, 'PM')) {
                $display_all_services = true;
            } else {
                $display_all_services = false;
            }

            $order = [
                'field'  => 'name',
                'field2' => 'name',
                'order'  => 'ASC',
            ];

            $filter['order'] = $order;

            $services = services_get_services(
                $filter,
                false,
                $display_all_services,
                'AR'
            );

            $output .= '<div class="services_table" >';
            $output .= '<div id="table_services_dashboard">';
            foreach ($services as $service) {
                switch ($service['status']) {
                    case SERVICE_STATUS_NORMAL:
                        $color = COL_NORMAL;
                    break;

                    case SERVICE_STATUS_CRITICAL:
                        $color = COL_CRITICAL;
                    break;

                    case SERVICE_STATUS_WARNING:
                        $color = COL_WARNING;
                    break;

                    case SERVICE_STATUS_UNKNOWN:
                    default:
                        $color = COL_UNKNOWN;
                    break;
                }

                $output .= '<a id="service_'.$service['id'].'" style="background-color: '.$color.'; color: #fff;" class="table_services_item_link_dashboard" href="index.php?'.'sec=network&'.'sec2=enterprise/operation/services/services&tab=service_map&'.'id_service='.$service['id'].'">
                    <div class="table_services_item">
                    <div style="width:50px; text-align:center;">';
                $output .= \ui_print_group_icon($service['id_group'], true, 'groups_small_white', '', false);

                $output .= '</div>
                    <div class="tooltip" style="color: #fff;position: relative;opacity: 1">'.$service['description'].'
                    </div>
                    <div class="tooltip" style="color: #fff;position: relative;opacity: 1">';
                $output .= \html_print_image('images/help_w.png', true, ['class' => 'img_help', 'title' => __($service['name']), 'id' => $service['id']]);

                $output .= '</div>
                    </div>
                    </a>';
            }
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
        return __('Services view');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'service_view';
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
            'height' => 270,
        ];

        return $size;
    }


}
