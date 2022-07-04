<?php
/**
 * Widget Tactical View Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Widget Tactical View
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
 * Tactical View Widgets.
 */
class TacticalWidget extends Widget
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
     * PM access.
     *
     * @var boolean
     */
    protected $pmAccess;


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
        include_once $config['homedir'].'/include/functions_reporting.php';
        include_once $config['homedir'].'/include/functions_reporting_html.php';

        include_once $config['homedir'].'/include/functions_servers.php';
        include_once $config['homedir'].'/include/functions_tactical.php';
        include_once $config['homedir'].'/include/functions_graph.php';

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

        // PM Access.
        $this->pmAccess = \users_can_manage_group_all('PM');

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
        $this->title = __('Tactical view');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'tactical';
        }

        // This forces at least a first configuration.
        $this->configurationRequired = false;
        if (empty($this->values['statusMonitor']) === true
            && empty($this->values['serverPerformance']) === true
            && empty($this->values['summary']) === true
            && empty($this->values['groupId']) === true
        ) {
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

        if (isset($decoder['statusmonitors']) === true) {
            $values['statusMonitor'] = $decoder['statusmonitors'];
        }

        if (isset($decoder['statusMonitor']) === true) {
            $values['statusMonitor'] = $decoder['statusMonitor'];
        }

        if (isset($decoder['serverperf']) === true) {
            $values['serverPerformance'] = $decoder['serverperf'];
        }

        if (isset($decoder['serverPerformance']) === true) {
            $values['serverPerformance'] = $decoder['serverPerformance'];
        }

        if (isset($decoder['summary']) === true) {
            $values['summary'] = $decoder['summary'];
        }

        if (isset($decoder['id_groups']) === true) {
            if (is_array($decoder['id_groups']) === true) {
                $decoder['id_groups'][0] = implode(',', $decoder['id_groups']);
            }

            $values['groupId'] = $decoder['id_groups'];
        }

        if (isset($decoder['groupId']) === true) {
            $values['groupId'] = $decoder['groupId'];
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
        if (isset($values['statusMonitor']) === false) {
            $values['statusMonitor'] = 1;
        }

        if (isset($values['serverPerformance']) === false) {
            $values['serverPerformance'] = 1;
        }

        if (isset($values['summary']) === false) {
            $values['summary'] = 1;
        }

        // Status and Monitor checks.
        $inputs[] = [
            'label'     => __('Status and Monitor checks'),
            'arguments' => [
                'name'  => 'statusMonitor',
                'id'    => 'statusMonitor',
                'type'  => 'switch',
                'value' => $values['statusMonitor'],
            ],
        ];

        if ($this->pmAccess === true) {
            // Server performance.
            $inputs[] = [
                'label'     => __('Server performance'),
                'arguments' => [
                    'name'  => 'serverPerformance',
                    'id'    => 'serverPerformance',
                    'type'  => 'switch',
                    'value' => $values['serverPerformance'],
                ],
            ];
        }

        // Summary.
        $inputs[] = [
            'label'     => __('Summary'),
            'arguments' => [
                'name'  => 'summary',
                'id'    => 'summary',
                'type'  => 'switch',
                'value' => $values['summary'],
            ],
        ];

        // Groups.
        $return_all_group = false;

        // Restrict access to group.
        $selected_groups = [];
        if ($values['groupId']) {
            $selected_groups = explode(',', $values['groupId'][0]);

            if (users_can_manage_group_all('AR') === true
                || ($selected_groups[0] !== ''
                && in_array(0, $selected_groups) === true)
            ) {
                // Return all group if user has permissions
                // or it is a currently selected group.
                $return_all_group = true;
            }
        } else {
            if (users_can_manage_group_all('AR') === true) {
                $return_all_group = true;
            }
        }

        $inputs[] = [
            'label'     => __('Groups'),
            'arguments' => [
                'type'           => 'select_groups',
                'name'           => 'groupId[]',
                'returnAllGroup' => true,
                'privilege'      => 'AR',
                'selected'       => $selected_groups,
                'return'         => true,
                'multiple'       => true,
                'returnAllGroup' => $return_all_group,
                'required'       => true,
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

        $values['statusMonitor'] = \get_parameter_switch('statusMonitor');
        $values['serverPerformance'] = \get_parameter_switch(
            'serverPerformance'
        );
        $values['summary'] = \get_parameter_switch('summary');
        $values['groupId'] = \get_parameter('groupId', []);

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

        $output = '';

        $all_data = \tactical_status_modules_agents($config['id_user'], false, 'AR', $this->values['groupId'][0]);

        $data = [];

        $data['monitor_not_init'] = (int) $all_data['_monitors_not_init_'];
        $data['monitor_unknown'] = (int) $all_data['_monitors_unknown_'];
        $data['monitor_ok'] = (int) $all_data['_monitors_ok_'];
        $data['monitor_warning'] = (int) $all_data['_monitors_warning_'];
        $data['monitor_critical'] = (int) $all_data['_monitors_critical_'];
        $data['monitor_not_normal'] = (int) $all_data['_monitor_not_normal_'];
        $data['monitor_alerts'] = (int) $all_data['_monitors_alerts_'];
        $data['monitor_alerts_fired'] = (int) $all_data['_monitors_alerts_fired_'];
        $data['monitor_total'] = (int) $all_data['_monitor_total_'];

        $data['total_agents'] = (int) $all_data['_total_agents_'];

        $data['monitor_checks'] = (int) $all_data['_monitor_checks_'];

        // Percentages.
        if (empty($all_data) === false) {
            if ($data['monitor_not_normal'] > 0
                && $data['monitor_checks'] > 0
            ) {
                $data['monitor_health'] = \format_numeric(
                    (100 - ($data['monitor_not_normal'] / ($data['monitor_checks'] / 100))),
                    1
                );
            } else {
                $data['monitor_health'] = 100;
            }

            if ($data['monitor_not_init'] > 0
                && $data['monitor_checks'] > 0
            ) {
                $data['module_sanity'] = \format_numeric(
                    (100 - ($data['monitor_not_init'] / ($data['monitor_checks'] / 100))),
                    1
                );
            } else {
                $data['module_sanity'] = 100;
            }

            if (isset($data['alerts']) === true) {
                if ($data['monitor_alerts_fired'] > 0
                    && $data['alerts'] > 0
                ) {
                    $data['alert_level'] = \format_numeric(
                        (100 - ($data['monitor_alerts_fired'] / ($data['alerts'] / 100))),
                        1
                    );
                } else {
                    $data['alert_level'] = 100;
                }
            } else {
                $data['alert_level'] = 100;
                $data['alerts'] = 0;
            }

            $data['monitor_bad'] = ($data['monitor_critical'] + $data['monitor_warning']);

            if ($data['monitor_bad'] > 0
                && $data['monitor_checks'] > 0
            ) {
                $data['global_health'] = \format_numeric(
                    (100 - ($data['monitor_bad'] / ($data['monitor_checks'] / 100))),
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

        if ((int) $this->values['statusMonitor'] === 1) {
            $table = new \stdClass();
            $table->width = '100%';

            $table->size[0] = '220px';

            $table->align[0] = 'center';

            $table->colspan = [];
            $table->colspan[0][1] = 2;

            $table->rowclass = \array_fill(0, 9, '');

            $table->data[0][0] = \reporting_get_stats_indicators(
                $data,
                120,
                25
            );
            $table->data[0][0] .= \reporting_get_stats_alerts($data);
            $table->cellstyle[0][0] = 'vertical-align: top;';

            $table->data[0][1] = \reporting_get_stats_modules_status($data);
            $table->data[0][1] .= '<br>';
            $table->data[0][1] .= \reporting_get_stats_agents_monitors($data);
            $table->data[0][1] .= '<br>';
            $table->cellstyle[0][1] = 'vertical-align: top;';

            $output .= \html_print_table($table, true);
        }

        if ((int) $this->values['serverPerformance'] === 1
            && $this->pmAccess === true
        ) {
            $table = new \stdClass();
            $table->width = '100%';
            $table->class = '';
            $table->cellpadding = 4;
            $table->cellspacing = 4;
            $table->border = 0;
            $table->head = [];
            $table->data = [];
            $table->style = [];

            $table->data[0][0] = \reporting_get_stats_servers();

            $output .= \html_print_table($table, true);
        }

        if ((int) $this->values['summary'] === 1) {
            $table = new \stdClass();
            $table->width = '100%';
            $table->class = '';
            $table->cellpadding = 4;
            $table->cellspacing = 4;
            $table->border = 0;
            $table->head = [];
            $table->data = [];
            $table->style = [];

            $table->data[0][0] = \reporting_get_stats_summary($data, 150, 100);

            $output .= \html_print_table($table, true);
        }

        return $output;
    }


    /**
     * Get description.
     *
     * @return string.
     */
    public static function getDescription():string
    {
        return __('Tactical view');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'tactical';
    }


    /**
     * Get size Modal Configuration.
     *
     * @return array
     */
    public function getSizeModalConfiguration(): array
    {
        $size = [
            'width'  => 450,
            'height' => 515,
        ];

        return $size;
    }


}
