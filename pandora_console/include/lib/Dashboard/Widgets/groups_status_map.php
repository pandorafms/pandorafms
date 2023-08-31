<?php
/**
 * Widget Group status map Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Widget Group status
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 * |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 * |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Pandora FMS
 * Please see https://pandorafms.com/community/ for full contribution list
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
 * Group status map Widgets.
 */
class GroupsStatusMapWidget extends Widget
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
        $this->title = __('Group status map');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'groups_status_map';
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

        if (isset($decoder['groups']) === true) {
            $values['groupId'] = $decoder['groups'];
        }

        if (isset($decoder['groupId']) === true) {
            $values['groupId'] = $decoder['groupId'];
        }

        if (isset($decoder['groupRecursion']) === true) {
            $values['groupRecursion'] = $decoder['groupRecursion'];
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
        $values['groupId'] = explode(',', $values['groupId']);
        // Restrict access to group.
        $inputs[] = [
            'label'     => __('Groups'),
            'arguments' => [
                'type'           => 'select_groups',
                'name'           => 'groupId',
                'returnAllGroup' => true,
                'privilege'      => 'AR',
                'multiple'       => true,
                'selected'       => $values['groupId'],
                'return'         => true,
            ],
        ];

        $inputs[] = [
            'label'     => __('Group recursion'),
            'arguments' => [
                'name'  => 'groupRecursion',
                'id'    => 'groupRecursion',
                'type'  => 'switch',
                'value' => $values['groupRecursion'],
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

        $values['groupId'] = \get_parameter('groupId', 0);
        $values['groupRecursion'] = \get_parameter_switch('groupRecursion', 0);

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
        include_once $config['homedir'].'/include/graphs/functions_d3.php';
        include_once $config['homedir'].'/include/functions_reporting.php';
        include_once $config['homedir'].'/include/functions_graph.php';
        $groups_array = (empty($this->values['groupId']) === false) ? explode(',', $this->values['groupId']) : [];

        if ((bool) $this->values['groupRecursion'] === true && in_array('0', $groups_array) === false) {
            foreach ($groups_array as $key => $group) {
                $children = groups_get_children($group, false, 'AR', false);
                foreach ($children as $key => $child) {
                    $groups_array[] = $child['id_grupo'];
                }
            }
        }

        if (is_metaconsole() === true) {
            $where = '';
            if (in_array('0', $groups_array) === false && count($groups_array) > 0) {
                $where = ' WHERE g.id_grupo IN ('.implode(',', $groups_array).') ';
            }

            $servers = metaconsole_get_servers();
            $rows = [];
            if (isset($servers) === true && is_array($servers) === true
            ) {
                foreach ($servers as $server) {
                    if (metaconsole_connect($server) == NOERR) {
                        $sql = 'SELECT g.id_grupo, g.nombre, estado, count(*) AS total_modules
                        FROM tagente a
                        LEFT JOIN tgrupo g ON g.id_grupo = a.id_grupo
                        LEFT JOIN tagente_modulo m ON a.id_agente = m.id_agente
                        LEFT JOIN tagente_estado es ON es.id_agente_modulo = m.id_agente_modulo
                        '.$where.'
                        GROUP BY a.id_grupo, estado';
                        $result = db_process_sql($sql);
                        if ($result !== false) {
                            foreach ($result as $key => $group) {
                                $not_exist = true;
                                foreach ($rows as $key2 => $row) {
                                    if ($group['id_grupo'] === $row['id_grupo']
                                        && $group['estado'] === $row['estado']
                                    ) {
                                        $rows[$key2]['total_modules'] += $group['total_modules'];
                                        $not_exist = false;
                                        break;
                                    }
                                }

                                if ($not_exist === true) {
                                    $rows[] = $group;
                                }
                            }
                        }
                    }

                    metaconsole_restore_db();
                }
            }
        } else {
            $where = '';
            if (in_array('0', $groups_array) === false && count($groups_array) > 0) {
                $where = ' WHERE g.id_grupo IN ('.implode(',', $groups_array).') ';
            }

            $sql = 'SELECT g.id_grupo, g.nombre, estado, count(*) AS total_modules
            FROM tagente a
            LEFT JOIN tgrupo g ON g.id_grupo = a.id_grupo
            LEFT JOIN tagente_modulo m ON a.id_agente = m.id_agente
            LEFT JOIN tagente_estado es ON es.id_agente_modulo = m.id_agente_modulo
            '.$where.'
            GROUP BY a.id_grupo, estado';

            $rows = db_process_sql($sql);
        }

        if ($rows === false || (is_array($rows) === true && count($rows) === 0)) {
            $output = ui_print_info_message(
                [
                    'no_close' => true,
                    'message'  => __('No data found.'),
                ]
            );
            return $output;
        }

        $level1 = [
            'name'     => __('Module status map'),
            'children' => [],
        ];

        $names = [];
        foreach ($rows as $key => $row) {
            $color = '';
            $name_status = '';
            switch ($row['estado']) {
                case '1':
                    $color = '#e63c52';
                    $name_status = __('Critical');
                break;

                case '2':
                    $color = '#FFB900';
                    $name_status = __('Warning');
                break;

                case '0':
                    $color = '#82b92e';
                    $name_status = __('Normal');
                break;

                case '3':
                    $color = '#B2B2B2';
                    $name_status = __('Unknown');
                break;

                case '4':
                    $color = '#4a83f3';
                    $name_status = __('No data');
                    $row['estado'] = 6;
                break;

                default:
                    $row['estado'] = 6;
                    $color = '#B2B2B2';
                    $name_status = __('Unknown');
                continue;
            }

            $level1['children'][$row['id_grupo']][] = [
                'id'              => uniqid(),
                'name'            => $row['estado'],
                'value'           => $row['total_modules'],
                'color'           => $color,
                'tooltip_content' => $row['total_modules'].__(' Modules(%s)', $name_status),
                'link'            => 'index.php?sec=view&sec2=operation/agentes/status_monitor&refr=0&ag_group='.$row['id_grupo'].'&ag_freestring=&module_option=1&ag_modulename=&moduletype=&datatype=&status='.$row['estado'].'&sort_field=&sort=none&pure=',
            ];
            $names[$row['id_grupo']] = $row['nombre'];
        }

        $level2 = [
            'children' => [],
        ];
        foreach ($level1['children'] as $id_grupo => $group) {
            $level2['children'][] = [
                'id'       => uniqid(),
                'name'     => io_safe_output($names[$id_grupo]),
                'children' => $group,
            ];
        }

        $id_container = 'tree_map_'.uniqid();
        $output = d3_tree_map_graph($level2, $size['width'], $size['height'], true, $id_container, true);
        return $output;
    }


    /**
     * Get description.
     *
     * @return string.
     */
    public static function getDescription()
    {
        return __('Group status map');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'groups_status_map';
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
            'height' => 330,
        ];

        return $size;
    }


}
