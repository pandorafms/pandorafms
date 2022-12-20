<?php
/**
 * Widget Monitor health Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Widget Monitor health
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
 * Monitor health Widgets.
 */
class MonitorHealthWidget extends Widget
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
        $this->title = __('Global health info');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'monitor_health';
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

        include_once $config['homedir'].'/include/functions_reporting.php';
        include_once $config['homedir'].'/include/functions_graph.php';
        include_once $config['homedir'].'/include/functions_servers.php';
        include_once $config['homedir'].'/include/functions_tactical.php';

        $size = parent::getSize();

        $all_data = tactical_status_modules_agents(
            $config['id_user'],
            false,
            'AR'
        );

        $data = [];

        $data['mNI'] = (int) $all_data['_monitors_not_init_'];
        $data['monitor_unknown'] = (int) $all_data['_monitors_unknown_'];
        $data['monitor_ok'] = (int) $all_data['_monitors_ok_'];
        $data['mW'] = (int) $all_data['_monitors_warning_'];
        $data['mC'] = (int) $all_data['_monitors_critical_'];
        $data['mNN'] = (int) $all_data['_monitor_not_normal_'];
        $data['monitor_not_normal'] = (int) $all_data['_monitor_not_normal_'];
        $data['monitor_not_init'] = (int) $all_data['_monitors_not_init_'];
        $data['monitor_alerts'] = (int) $all_data['_monitors_alerts_'];
        $data['mAFired'] = (int) $all_data['_monitors_alerts_fired_'];
        $data['monitor_alerts_fired'] = (int) $all_data['_monitors_alerts_fired_'];

        $data['total_agents'] = (int) $all_data['_total_agents_'];

        $data['mChecks'] = (int) $all_data['_monitor_checks_'];
        if (empty($all_data) === false) {
            if ($data['mNN'] > 0 && $data['mChecks'] > 0) {
                $data['monitor_health'] = \format_numeric(
                    (100 - ($data['mNN'] / ($data['mChecks'] / 100))),
                    1
                );
            } else {
                $data['monitor_health'] = 100;
            }

            if ($data['mNI'] > 0 && $data['mChecks'] > 0) {
                $data['module_sanity'] = \format_numeric(
                    (100 - ($data['mNI'] / ($data['mChecks'] / 100))),
                    1
                );
            } else {
                $data['module_sanity'] = 100;
            }

            if (isset($data['alerts']) === true) {
                if ($data['mAfired'] > 0 && $data['alerts'] > 0) {
                    $data['alert_level'] = \format_numeric(
                        (100 - ($data['mAfired'] / ($data['alerts'] / 100))),
                        1
                    );
                } else {
                    $data['alert_level'] = 100;
                }
            } else {
                $data['alert_level'] = 100;
                $data['alerts'] = 0;
            }

            $data['monitor_bad'] = ($data['mC'] + $data['mW']);

            if ($data['monitor_bad'] > 0 && $data['mChecks'] > 0) {
                $data['global_health'] = \format_numeric(
                    (100 - ($data['monitor_bad'] / ($data['mChecks'] / 100))),
                    1
                );
            } else {
                $data['global_health'] = 100;
            }

            $data['server_sanity'] = \format_numeric(
                (100 - $data['module_sanity']),
                1
            );
        }

        $table = new \stdClass;
        $table->width = '90%';
        $table->class = 'nothing';

        $table->align[0] = 'center';

        $table->data[0][0] = \reporting_get_stats_indicators(
            $data,
            ((int) $size['width'] - 100),
            20
        );

        $output = '<div>';
        $output .= \html_print_table($table, true);
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
        return __('Global health info');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'monitor_health';
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
            'height' => 220,
        ];

        return $size;
    }


}
