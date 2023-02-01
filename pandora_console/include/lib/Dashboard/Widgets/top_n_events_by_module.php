<?php
/**
 * Widget Top N events by module Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Widget Top N events by module
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
 * Top N events by module Widgets.
 */
class TopNEventByModuleWidget extends Widget
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

        // Include.
        include_once $config['homedir'].'/include/functions_events.php';
        include_once $config['homedir'].'/include/functions_users.php';
        include_once $config['homedir'].'/include/functions_agents.php';

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
        $this->title = __('Top N events by module');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'top_n_events_by_module';
        }

        // This forces at least a first configuration.
        $this->configurationRequired = false;
        if (empty($this->values['groupId']) === true) {
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

        if (isset($decoder['amount']) === true) {
            $values['amountShow'] = $decoder['amount'];
        }

        if (isset($decoder['amountShow']) === true) {
            $values['amountShow'] = $decoder['amountShow'];
        }

        if (isset($decoder['event_view_hr']) === true) {
            $values['maxHours'] = $decoder['event_view_hr'];
        }

        if (isset($decoder['maxHours']) === true) {
            $values['maxHours'] = $decoder['maxHours'];
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

        if (isset($decoder['legend_position']) === true) {
            $values['legendPosition'] = $decoder['legend_position'];
        }

        if (isset($decoder['legendPosition']) === true) {
            $values['legendPosition'] = $decoder['legendPosition'];
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
        if (isset($values['amountShow']) === false) {
            $values['amountShow'] = 10;
        }

        if (isset($values['maxHours']) === false) {
            $values['maxHours'] = 8;
        }

        // Text size of value in px.
        $inputs[] = [
            'label'     => __('Amount to show'),
            'arguments' => [
                'name'   => 'amountShow',
                'type'   => 'number',
                'value'  => $values['amountShow'],
                'return' => true,
                'min'    => 0,
            ],
        ];

        // Text size of value in px.
        $inputs[] = [
            'label'     => __('Max. hours old'),
            'arguments' => [
                'name'   => 'maxHours',
                'type'   => 'number',
                'value'  => $values['maxHours'],
                'return' => true,
                'min'    => 0,
            ],
        ];

        $return_all_group = false;

        $selected_groups = [];
        if ($values['groupId']) {
            $selected_groups = explode(',', $values['groupId'][0]);

            if (users_can_manage_group_all('RM') === true
                || in_array(0, $selected_groups) === true
            ) {
                // Return all group if user has permissions
                // or it is a currently selected group.
                $return_all_group = true;
            }
        } else {
            if (users_can_manage_group_all('RM') === true) {
                $return_all_group = true;
            }
        }

        // Groups.
        $inputs[] = [
            'label'     => __('Groups'),
            'arguments' => [
                'type'           => 'select_groups',
                'name'           => 'groupId[]',
                'returnAllGroup' => true,
                'privilege'      => 'AR',
                'selected'       => (empty($selected_groups) === true) ? [0] : $selected_groups,
                'return'         => true,
                'multiple'       => true,
                'returnAllGroup' => $return_all_group,
                'required'       => true,
            ],
        ];

        // Legend Position.
        $fields = [
            'bottom' => __('Bottom'),
            'hidden' => __('No legend'),
        ];

        $inputs[] = [
            'label'     => __('Legend Position'),
            'arguments' => [
                'type'     => 'select',
                'fields'   => $fields,
                'name'     => 'legendPosition',
                'selected' => $values['legendPosition'],
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

        $values['amountShow'] = \get_parameter('amountShow', 0);
        $values['maxHours'] = \get_parameter('maxHours', 0);
        $values['groupId'] = \get_parameter('groupId', []);
        $values['legendPosition'] = \get_parameter('legendPosition', 0);

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

        $this->values['groupId'] = explode(',', $this->values['groupId'][0]);

        if (empty($this->values['groupId']) === true) {
            $output = '<div class="container-center">';
            $output .= \ui_print_info_message(
                __('Please select one or more groups.'),
                '',
                true
            );
            $output .= '</div>';
            return $output;
        } else {
            $timestamp = (time() - SECONDS_1HOUR * $this->values['maxHours']);

            $all_group = false;
            // Search all.
            if (array_search('0', $this->values['groupId']) !== false) {
                $all_group = true;
            }

            if ($all_group === false) {
                $sql = sprintf(
                    'SELECT id_agente,
                        id_agentmodule,
                        event_type,
                        COUNT(*) AS count
                    FROM tevento
                    WHERE utimestamp >= %d
                        AND id_grupo IN (%s)
                    GROUP BY id_agentmodule, event_type
                    ORDER BY count DESC
                    LIMIT %d',
                    $timestamp,
                    implode(',', $this->values['groupId']),
                    $this->values['amountShow']
                );
            } else {
                $sql = sprintf(
                    'SELECT id_agente,
                        id_agentmodule,
                        event_type,
                        COUNT(*) AS count
                    FROM tevento
                    WHERE utimestamp >= %d
                    GROUP BY id_agentmodule, event_type
                    ORDER BY count DESC
                    LIMIT %d',
                    $timestamp,
                    $this->values['amountShow']
                );
            }

            $result = db_get_all_rows_sql($sql);

            if (empty($result) === true) {
                $output = '<div class="container-center">';
                $output .= \ui_print_error_message(
                    __('There is not data to show.'),
                    '',
                    true
                );
                $output .= '</div>';
                return $output;
            } else {
                $labels = [];
                $data_pie = [];
                foreach ($result as $row) {
                    if ($row['id_agentmodule'] == 0) {
                        $name = __('System');
                    } else {
                        $name_agent = io_safe_output(
                            agents_get_alias($row['id_agente'])
                        );

                        $name_module = io_safe_output(
                            modules_get_agentmodule_name($row['id_agentmodule'])
                        );
                        if ($size['width'] < 400) {
                            $name_agent = ui_print_truncate_text(
                                $name_agent,
                                15,
                                false,
                                true,
                                false,
                                '&hellip;',
                                false
                            );
                            $name_module = ui_print_truncate_text(
                                $name_module,
                                15,
                                false,
                                true,
                                false,
                                '&hellip;',
                                false
                            );
                        }

                        $name = $name_agent.' - '.$name_module;
                    }

                    $event_name = events_print_type_description(
                        $row['event_type'],
                        true
                    );
                    if ($size['width'] < 400) {
                        $event_name = ui_print_truncate_text(
                            $event_name,
                            20,
                            false,
                            true,
                            false,
                            '&hellip;',
                            false
                        );
                    }

                    $labels[] = $event_name.' [ '.$name.' ] ('.$row['count'].')';
                    $data_pie[] = $row['count'];
                }
            }

            $width = $size['width'];
            $height = $size['height'];

            switch ($this->values['legendPosition']) {
                case 'hidden':
                    $height = ($height - 50);
                break;

                default:
                case 'bottom':
                    $numleg = count($data_pie);
                    if ($numleg >= 4) {
                        $numleg = 4;
                    } else if ($numleg < 4 && $numleg > 1) {
                        $numleg = 2;
                    } else if ($numleg == 1) {
                        $numleg = 1.5;
                    }

                    // % is for the pie group the slices and show only 5
                    $height = ($height - (65 * ($numleg)));
                break;
            }

            $output = pie_graph(
                $data_pie,
                [
                    'legend' => [
                        'display'  => true,
                        'position' => 'right',
                        'align'    => 'center',
                    ],
                    'labels' => $labels,
                ]
            );
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
        return __('Top N events by module');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'top_n_events_by_module';
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
            'height' => 540,
        ];

        return $size;
    }


}
