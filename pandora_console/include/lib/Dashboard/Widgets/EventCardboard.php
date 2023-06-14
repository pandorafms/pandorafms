<?php
/**
 * Widget Event cardboard Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Widget Event cardboard
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Artica Soluciones Tecnologicas
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
 * Event cardboard Widgets.
 */
class EventCardboard extends Widget
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

        include_once $config['homedir'].'/include/functions_agents.php';
        include_once $config['homedir'].'/include/functions_modules.php';
        include_once $config['homedir'].'/include/functions_events.php';

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
        $this->title = __('Event cardboard');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'EventCardboard';
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

        if (isset($decoder['eventType']) === true) {
            $values['eventType'] = $decoder['eventType'];
        }

        if (isset($decoder['maxHours']) === true) {
            $values['maxHours'] = $decoder['maxHours'];
        }

        if (isset($decoder['eventStatus']) === true) {
            $values['eventStatus'] = $decoder['eventStatus'];
        }

        if (isset($decoder['severity']) === true) {
            $values['severity'] = $decoder['severity'];
        }

        if (isset($decoder['groupId']) === true) {
            $values['groupId'] = $decoder['groupId'];
        }

        if (isset($decoder['nodes']) === true) {
            $values['nodes'] = $decoder['nodes'];
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

        // Remove background field, this widget doesn't use it.
        foreach ($inputs as $kIn => $vIn) {
            if ($vIn['label'] === 'Background') {
                unset($inputs[$kIn]);
            }
        }

        $blocks = [
            'row1',
            'row2',
        ];

        $inputs['blocks'] = $blocks;

        foreach ($inputs as $kInput => $vInput) {
            $inputs['inputs']['row1'][] = $vInput;
        }

        // Event Type.
        $fields = get_event_types();
        $fields['not_normal'] = __('Not normal');

        $inputs['inputs']['row1'][] = [
            'label'     => __('Event type'),
            'arguments' => [
                'type'          => 'select',
                'fields'        => $fields,
                'class'         => 'event-widget-input',
                'name'          => 'eventType',
                'selected'      => $values['eventType'],
                'return'        => true,
                'nothing'       => __('Any'),
                'nothing_value' => 0,
            ],
        ];

        // Max. hours old. Default 8.
        if (isset($values['maxHours']) === false) {
            $values['maxHours'] = 8;
        }

        $inputs['inputs']['row1'][] = [
            'label'     => __('Max. hours old'),
            'arguments' => [
                'name'   => 'maxHours',
                'type'   => 'number',
                'class'  => 'event-widget-input',
                'value'  => $values['maxHours'],
                'return' => true,
                'min'    => 0,
            ],
        ];

        // Event status.
        $fields = [
            -1 => __('All event'),
            1  => __('Only validated'),
            0  => __('Only pending'),
        ];

        $inputs['inputs']['row1'][] = [
            'label'     => __('Event status'),
            'arguments' => [
                'type'     => 'select',
                'fields'   => $fields,
                'class'    => 'event-widget-input',
                'name'     => 'eventStatus',
                'selected' => $values['eventStatus'],
                'return'   => true,
            ],
        ];

        // Groups.
        $return_all_group = false;
        $selected_groups_array = explode(',', $values['groupId'][0]);

        if (empty($values['groupId'][0]) === true) {
            $selected_groups_array = [0];
        }

        if ((bool) \users_can_manage_group_all('RM') === true
            || ($selected_groups_array[0] !== ''
            && in_array(0, $selected_groups_array) === true)
        ) {
            // Return all group if user has permissions or it is a currently
            // selected group.
            $return_all_group = true;
        }

        $inputs['inputs']['row1'][] = [
            'label'     => __('Groups'),
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
                'required'       => true,
            ],
        ];

        // Nodes.
        if (is_metaconsole() === true) {
            $nodes_fields = [];
            $servers_ids = metaconsole_get_servers();

            foreach ($servers_ids as $server) {
                $nodes_fields[$server['id']] = $server['server_name'];
            }

            $nodes_fields[0] = __('Metaconsola');

            $nodes_selected = explode(',', $values['nodes']);

            (isset($values['nodes']) === false) ? $nodes_selected = $servers_ids : '';

            $nodes_height = count($nodes_fields);
            if (count($nodes_fields) > 5) {
                $nodes_height = 5;
            }

            $inputs['inputs']['row2'][] = [
                'label'     => __('Servers'),
                'arguments' => [
                    'name'       => 'nodes',
                    'type'       => 'select',
                    'fields'     => $nodes_fields,
                    'selected'   => $nodes_selected,
                    'return'     => true,
                    'multiple'   => true,
                    'class'      => 'overflow-hidden',
                    'size'       => $nodes_height,
                    'select_all' => false,
                    'required'   => true,
                ],
            ];
        }

        // Severity.
        $fields = get_priorities();

        $severity_selected = explode(',', $values['severity']);

        if (isset($values['severity']) === false) {
            $severity_selected = array_keys($fields);
        }

        $inputs['inputs']['row2'][] = [
            'label'     => __('Severity'),
            'arguments' => [
                'type'     => 'select',
                'fields'   => $fields,
                'class'    => 'event-widget-input',
                'name'     => 'severity',
                'selected' => $severity_selected,
                'return'   => true,
                'multiple' => true,
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
        $values['eventStatus'] = \get_parameter('eventStatus', -1);
        $values['groupId'] = \get_parameter('groupId', []);
        $values['severity'] = \get_parameter('severity', -1);
        $values['nodes'] = \get_parameter('nodes', 0);

        return $values;
    }


    /**
     * Draw widget.
     *
     * @return string;
     */
    public function load()
    {
        $output = '';

        ui_require_css_file('events', 'include/styles/', true);
        ui_require_javascript_file('pandora_events', 'include/javascript/', true);

        $eventType = $this->values['eventType'];
        $groupId = implode(',', $this->values['groupId']);
        $utimestamp = strtotime('-'.$this->values['maxHours'].' hours');
        $eventStatus = $this->values['eventStatus'];
        $severity = $this->values['severity'];

        $priorities = explode(',', $severity);
        // Sort criticity array.
        asort($priorities);

        $count_meta = [];
        $count_meta_tmp = [];
        if (is_metaconsole() === true) {
            $meta = false;
            $nodes = $this->values['nodes'];

            if (isset($nodes) === true) {
                $servers_ids = explode(',', $nodes);
            }

            if (in_array(0, $servers_ids) === true) {
                $meta = true;
                unset($servers_ids[0]);
            }

            if (is_metaconsole() === true && $meta === true) {
                $events_meta_rows = get_count_event_criticity(
                    $utimestamp,
                    $eventType,
                    $groupId,
                    $eventStatus,
                    $severity
                );

                array_push($count_meta_tmp, $events_meta_rows);
            }

            foreach ($servers_ids as $server_id) {
                try {
                    $node = new Node((int) $server_id);
                    $node->connect();

                    $events_meta_rows = get_count_event_criticity(
                        $utimestamp,
                        $eventType,
                        $groupId,
                        $eventStatus,
                        $severity
                    );

                    array_push($count_meta_tmp, $events_meta_rows);
                    $node->disconnect();
                } catch (\Exception $e) {
                    // Unexistent envents.
                    $node->disconnect();
                }
            }

            foreach ($count_meta_tmp as $tmpValue) {
                foreach ($tmpValue as $value) {
                    array_push($count_meta, $value);
                }
            }

            $events_rows = [];
            foreach ($priorities as $pKey) {
                $count = 0;
                $tmp['criticity'] = $pKey;
                foreach ($count_meta as $kEventMeta => $vEventMeta) {
                    if ((int) $pKey === (int) $vEventMeta['criticity']) {
                        $count += (int) $vEventMeta['count'];
                    }
                }

                $tmp['count'] = $count;
                array_push($events_rows, $tmp);
            }
        } else {
            $events_rows = get_count_event_criticity(
                $utimestamp,
                $eventType,
                $groupId,
                $eventStatus,
                $severity
            );
        }

        $output .= '<table class="w100p h100p table-border-0"><tbody><tr>';

        $width_td = (100 / count(explode(',', $severity)));

        $td_count = 0;
        foreach ($priorities as $key) {
            $count = 0;
            foreach ($events_rows as $event) {
                if ((int) $key === (int) $event['criticity']) {
                    $count = $event['count'];
                }
            }

            switch ((int) $key) {
                case 0:
                    $text = __('Maintenance');
                    $color = get_priority_class((int) $key);
                break;

                case 1:
                    $text = __('Informational');
                    $color = get_priority_class((int) $key);
                break;

                case 2:
                    $text = __('Normal');
                    $color = get_priority_class((int) $key);
                break;

                case 3:
                    $text = __('Warning');
                    $color = get_priority_class((int) $key);
                break;

                case 4:
                    $text = __('Critical');
                    $color = get_priority_class((int) $key);
                break;

                case 5:
                    $text = __('Minor');
                    $color = get_priority_class((int) $key);
                break;

                case 6:
                    $text = __('Major');
                    $color = get_priority_class((int) $key);
                break;

                case 20:
                    $text = __('Not normal');
                    $color = get_priority_class((int) $key);
                break;

                case 21:
                    $text = __('Critical').'/'.__('Normal');
                    $color = get_priority_class((int) $key);
                break;

                case 34:
                    $text = __('Warning').'/'.__('Critical');
                    $color = get_priority_class((int) $key);
                break;

                default:
                return false;
            }

            $border = '';
            $td_count++;
            if (count($priorities) > $td_count) {
                $border = ' border-right: 1px solid white; border-collapse: collapse;';
            }

            $output .= '<td class="'.$color.'" style="width: '.$width_td.'%;'.$border.'"><span class="med_data">';
            $output .= $count;
            $output .= '</span><br>';
            $output .= $text;
            $output .= '</td>';
        }

        $output .= '</tr></tbody></table>';

        return $output;
    }


    /**
     * Get description.
     *
     * @return string.
     */
    public static function getDescription()
    {
        return __('Event cardboard');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'EventCardboard';
    }


    /**
     * Get size Modal Configuration.
     *
     * @return array
     */
    public function getSizeModalConfiguration(): array
    {
        if (is_metaconsole() === true) {
            $size = [
                'width'  => 950,
                'height' => 450,
            ];
        } else {
            $size = [
                'width'  => 900,
                'height' => 450,
            ];
        }

        return $size;
    }


}
