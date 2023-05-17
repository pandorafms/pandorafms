<?php
/**
 * Widget Event list Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Widget Event list
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
 * Event list Widgets
 */
class EventsListWidget extends Widget
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
     * Public Link.
     *
     * @var boolean
     */
    protected $publicLink;


    /**
     * Construct.
     *
     * @param integer      $cellId      Cell ID.
     * @param integer      $dashboardId Dashboard ID.
     * @param integer      $widgetId    Widget ID.
     * @param integer|null $width       New width.
     * @param integer|null $height      New height.
     * @param integer|null $gridWidth   Grid width.
     * @param boolean|null $publicLink  Public Link.
     */
    public function __construct(
        int $cellId,
        int $dashboardId=0,
        int $widgetId=0,
        ?int $width=0,
        ?int $height=0,
        ?int $gridWidth=0,
        ?bool $publicLink=false
    ) {
        global $config;

        // Includes.
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

        // Grid Width.
        $this->publicLink = $publicLink;

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
        $this->title = \__('List of latest events');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'events_list';
        }

        // This forces at least a first configuration.
        $this->configurationRequired = false;
        if (isset($this->values['groupId']) === false) {
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

        if (isset($decoder['type']) === true) {
            $values['eventType'] = $decoder['type'];
        }

        if (isset($decoder['eventType']) === true) {
            $values['eventType'] = $decoder['eventType'];
        }

        if (isset($decoder['event_view_hr']) === true) {
            $values['maxHours'] = $decoder['event_view_hr'];
        }

        if (isset($decoder['maxHours']) === true) {
            $values['maxHours'] = $decoder['maxHours'];
        }

        if (isset($decoder['limit']) === true) {
            $values['limit'] = $decoder['limit'];
        }

        if (isset($decoder['status']) === true) {
            $values['eventStatus'] = $decoder['status'];
        }

        if (isset($decoder['eventStatus']) === true) {
            $values['eventStatus'] = $decoder['eventStatus'];
        }

        if (isset($decoder['severity']) === true) {
            $values['severity'] = $decoder['severity'];
        }

        if (isset($decoder['id_groups']) === true) {
            if (is_array($decoder['id_groups']) === true) {
                $decoder['id_groups'][0] = implode(',', $decoder['id_groups']);
            }

            $values['groupId'] = $decoder['id_groups'];
        }

        if (isset($decoder['groupRecursion']) === true) {
            $values['groupRecursion'] = $decoder['groupRecursion'];
        }

        if (isset($decoder['customFilter']) === true) {
            $values['customFilter'] = $decoder['customFilter'];
        }

        if (isset($decoder['groupId']) === true) {
            $values['groupId'] = $decoder['groupId'];
        }

        if (isset($decoder['tagsId']) === true) {
            $values['tagsId'] = $decoder['tagsId'];
        }

        return $values;
    }


    /**
     * Aux javascript to be run after form load.
     *
     * @return string
     */
    public function getFormJS(): string
    {
        return '$( document ).ready(function() {event_widget_options();});';
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

        $values = $this->values;

        // Retrieve global - common inputs.
        $inputs = parent::getFormInputs();

        $blocks = [
            'row1',
            'row2',
        ];

        $inputs['blocks'] = $blocks;

        foreach ($inputs as $kInput => $vInput) {
            $inputs['inputs']['row1'][] = $vInput;
        }

        // Select pre built filter.
        $inputs['inputs']['row1'][] = [
            'label'     => \__('Custom filters'),
            'arguments' => [
                'type'          => 'select',
                'id'            => 'select-custom-filter',
                'fields'        => \events_get_event_filter_select(false),
                'name'          => 'customFilter',
                'script'        => 'event_widget_options();',
                'nothing'       => \__('None'),
                'nothing_value' => -1,
                'selected'      => $this->values['customFilter'],
            ],
        ];

        $fields = \get_event_types();
        $fields['not_normal'] = \__('Not normal');

        // Default values.
        if (isset($values['maxHours']) === false) {
            $values['maxHours'] = 8;
        }

        if (isset($values['limit']) === false) {
            $values['limit'] = $config['block_size'];
        }

        // Event Type.
        $inputs['inputs']['row1'][] = [
            'label'     => \__('Event type'),
            'arguments' => [
                'type'          => 'select',
                'fields'        => $fields,
                'class'         => 'event-widget-input',
                'name'          => 'eventType',
                'selected'      => $values['eventType'],
                'return'        => true,
                'nothing'       => \__('Any'),
                'nothing_value' => 0,
            ],
        ];

        // Max. hours old. Default 8.
        $inputs['inputs']['row1'][] = [
            'label'     => \__('Max. hours old'),
            'arguments' => [
                'name'   => 'maxHours',
                'type'   => 'number',
                'class'  => 'event-widget-input',
                'value'  => $values['maxHours'],
                'return' => true,
                'min'    => 0,
            ],
        ];

        // Limit Default block_size.
        $blockSizeD4 = \format_integer_round(($config['block_size'] / 4));
        $blockSizeD2 = \format_integer_round(($config['block_size'] / 2));
        $fields = [
            $config['block_size']       => $config['block_size'],
            $blockSizeD4                => $blockSizeD4,
            $blockSizeD2                => $blockSizeD2,
            ($config['block_size'] * 2) => ($config['block_size'] * 2),
            ($config['block_size'] * 3) => ($config['block_size'] * 3),
        ];

        $inputs['inputs']['row1'][] = [
            'label'     => \__('Limit'),
            'arguments' => [
                'type'     => 'select',
                'fields'   => $fields,
                'class'    => 'event-widget-input',
                'name'     => 'limit',
                'selected' => $values['limit'],
                'return'   => true,
            ],
        ];

        // Event status.
        $fields = [
            -1 => \__('All event'),
            1  => \__('Only validated'),
            0  => \__('Only pending'),
        ];

        $inputs['inputs']['row1'][] = [
            'label'     => \__('Event status'),
            'arguments' => [
                'type'     => 'select',
                'fields'   => $fields,
                'class'    => 'event-widget-input',
                'name'     => 'eventStatus',
                'selected' => $values['eventStatus'],
                'return'   => true,
            ],
        ];

        // Severity.
        $fields = \get_priorities();

        $inputs['inputs']['row1'][] = [
            'label'     => \__('Severity'),
            'arguments' => [
                'type'          => 'select',
                'fields'        => $fields,
                'class'         => 'event-widget-input',
                'name'          => 'severity',
                'selected'      => $values['severity'],
                'return'        => true,
                'nothing'       => \__('All'),
                'nothing_value' => -1,
            ],
        ];

        $return_all_group = false;
        $selected_groups_array = explode(',', $values['groupId'][0]);

        if ((bool) \users_can_manage_group_all('RM') === true
            || ($selected_groups_array[0] !== ''
            && in_array(0, $selected_groups_array) === true)
        ) {
            // Return all group if user has permissions or it is a currently
            // selected group.
            $return_all_group = true;
        }

        // Groups.
        $inputs['inputs']['row2'][] = [
            'label'     => \__('Groups'),
            'arguments' => [
                'type'           => 'select_groups',
                'name'           => 'groupId[]',
                'class'          => 'event-widget-input',
                'returnAllGroup' => true,
                'privilege'      => 'AR',
                'selected'       => $selected_groups_array,
                'return'         => true,
                'multiple'       => true,
                'returnAllGroup' => $return_all_group,
            ],
        ];

        // Group recursion.
        $inputs['inputs']['row2'][] = [
            'label'     => \__('Group recursion'),
            'arguments' => [
                'type'   => 'switch',
                'name'   => 'groupRecursion',
                'class'  => 'event-widget-input',
                'value'  => $values['groupRecursion'],
                'return' => true,
            ],
        ];

        // Tags.
        $fields = \tags_get_user_tags($config['id_user'], 'AR');

        $inputs['inputs']['row2'][] = [
            'label'     => \__('Tags'),
            'arguments' => [
                'type'          => 'select',
                'fields'        => $fields,
                'class'         => 'event-widget-input',
                'name'          => 'tagsId[]',
                'selected'      => explode(',', $values['tagsId'][0]),
                'return'        => true,
                'multiple'      => true,
                'nothing'       => __('None'),
                'nothing_value' => 0,
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

        $values['eventType'] = \get_parameter('eventType', 0);
        $values['maxHours'] = \get_parameter('maxHours', 8);
        $values['limit'] = \get_parameter('limit', 20);
        $values['eventStatus'] = \get_parameter('eventStatus', -1);
        $values['severity'] = \get_parameter_switch('severity', -1);
        $values['groupId'] = \get_parameter_switch('groupId', []);
        $values['tagsId'] = \get_parameter_switch('tagsId', []);
        $values['groupRecursion'] = \get_parameter_switch('groupRecursion', 0);
        $values['customFilter'] = \get_parameter('customFilter', -1);

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

        \ui_require_css_file('events', 'include/styles/', true);
        \ui_require_javascript_file('pandora_events', 'include/javascript/', true);

        $this->values['groupId'] = explode(',', $this->values['groupId'][0]);
        $this->values['tagsId'] = explode(',', $this->values['tagsId'][0]);

        if (empty($this->values['groupId']) === true) {
            $output .= \__('You must select some group');
            return $output;
        }

        $useTags = (bool) \tags_has_user_acl_tags($config['id_user']);
        if ($useTags === true) {
            if (empty($this->values['tagsId']) === true) {
                $output .= \__('You don\'t have access');
                return;
            }
        }

        // Put hours in seconds.
        $filter = [];
        $order = [];

        $customFilter = \events_get_event_filter($this->values['customFilter']);
        if ($customFilter !== false) {
            $filter = $customFilter;
            $filter['tag_with'] = base64_encode(
                io_safe_output($filter['tag_with'])
            );

            $filter['tag_without'] = base64_encode(
                io_safe_output($filter['tag_without'])
            );

            if (empty($filter['id_agent_module']) === false) {
                $name = \modules_get_modules_name(
                    ' FROM tagente_modulo',
                    ' WHERE id_agente_modulo = '.$filter['id_agent_module'],
                    is_metaconsole()
                );
                $filter['module_search'] = $name[0]['nombre'];
            }
        } else if (empty($this->values['customFilter']) === false
            && (int) $this->values['customFilter'] !== -1
        ) {
            $output = '<div class="container-center">';
            $output .= \ui_print_error_message(
                __('Widget cannot be loaded').'. '.__('Please, event filter has been removed.'),
                '',
                true
            );
            $output .= '</div>';
            echo $output;
            return;
        } else {
            // Filtering.
            $filter['event_view_hr'] = $this->values['maxHours'];

            // Group.
            $filter['id_group_filter'] = $this->values['groupId'];
            if (empty($filter['id_group_filter']) === true
                || $filter['id_group_filter'][0] === ''
                || $filter['id_group_filter'][0] === '0'
            ) {
                // No filter specified. Don't filter at all...
                $filter['id_group_filter'] = null;
            }

            // Tags.
            if (empty($this->values['tagsId']) === false) {
                $filter['tag_with'] = base64_encode(
                    json_encode($this->values['tagsId'])
                );
            }

            // Severity.
            if (isset($this->values['severity']) === true) {
                $filter['severity'] = $this->values['severity'];
            }

            // Event types.
            if (empty($this->values['eventType']) === false) {
                $filter['event_type'] = $this->values['eventType'];
            }

            // Event status.
            if ((int) $this->values['eventStatus'] !== -1) {
                $filter['status'] = $this->values['eventStatus'];
            }
        }

        $default_fields = [
            [
                'text'  => 'evento',
                'class' => 'mw120px',
            ],
            [
                'text'  => 'mini_severity',
                'class' => 'no-padding',
            ],
            'id_evento',
            'agent_name',
            'timestamp',
            'event_type',
            [
                'text'  => 'options',
                'class' => 'table_action_buttons w120px',
            ],
        ];
        $fields = explode(',', $config['event_fields']);

        // Always check something is shown.
        if (empty($fields) === true) {
            $fields = $default_fields;
        }

        if (empty($filter['search']) === false || empty($filter['user_comment']) === false) {
            $fields[] = 'user_comment';
        }

        // Get column names.
        $column_names = events_get_column_names($fields, true);

        // AJAX call options responses.
        $output .= '<div id="event_details_window" style="display:none"></div>';
        $output .= '<div id="event_response_window" style="display:none"></div>';
        $output .= '<div id="event_response_command_window" title="'.__('Parameters').'" style="display:none"></div>';
        $output .= \html_print_input_hidden(
            'ajax_file',
            \ui_get_full_url('ajax.php', false, false, false),
            true
        );

        $output .= \html_print_input_hidden(
            'meta',
            is_metaconsole(),
            true
        );

        $output .= \html_print_input_hidden(
            'delete_confirm_message',
            __('Are you sure?'),
            true
        );

        $table_id = 'dashboard_list_events_'.$this->cellId;

        // Public dashboard.
        $hash = get_parameter('auth_hash', '');
        $id_user = get_parameter('id_user', '');

        // Print datatable.
        $output .= ui_print_datatable(
            [
                'id'                             => $table_id,
                'class'                          => 'info_table events',
                'style'                          => 'width: 99%;',
                'ajax_url'                       => 'operation/events/events',
                'ajax_data'                      => [
                    'get_events'     => 1,
                    'table_id'       => $table_id,
                    'filter'         => $filter,
                    'length'         => $this->values['limit'],
                    'groupRecursion' => (bool) $this->values['groupRecursion'],
                    'auth_hash'      => $hash,
                    'auth_class'     => 'PandoraFMS\Dashboard\Manager',
                    'id_user'        => $id_user,
                ],
                'default_pagination'             => $this->values['limit'],
                'pagination_options'             => [
                    [
                        $this->values['limit'],
                        10,
                        25,
                        100,
                    ],
                    [
                        $this->values['limit'],
                        10,
                        25,
                        100,
                    ],
                ],
                'order'                          => [
                    'field'     => 'timestamp',
                    'direction' => 'desc',
                ],
                'column_names'                   => $column_names,
                'columns'                        => $fields,
                'ajax_return_operation'          => 'buffers',
                'ajax_return_operation_function' => 'process_buffers',
                'return'                         => true,
                'csv'                            => 0,
                'dom_elements'                   => 'frtilp',
            ]
        );

        return $output;
    }


    /**
     * Get description.
     *
     * @return string.
     */
    public static function getDescription()
    {
        return \__('List of latest events');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'events_list';
    }


    /**
     * Get size Modal Configuration.
     *
     * @return array
     */
    public function getSizeModalConfiguration(): array
    {
        $size = [
            'width'  => 800,
            'height' => (is_metaconsole() === true) ? 600 : 550,
        ];

        return $size;
    }


}
