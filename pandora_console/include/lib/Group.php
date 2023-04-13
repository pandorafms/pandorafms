<?php
/**
 * Group entity class.
 *
 * @category   Class
 * @package    Pandora FMS
 * @subpackage OpenSource
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

// Begin.
namespace PandoraFMS;

global $config;
require_once $config['homedir'].'/include/functions_groups.php';

/**
 * PandoraFMS Group entity.
 */
class Group extends Entity
{

    /**
     * List of available ajax methods.
     *
     * @var array
     */
    private static $ajaxMethods = [
        'getGroupsForSelect',
        'distributionBySoGraph',
        'groupEventsByAgent',
        'loadInfoAgent',
        'getAgentsByGroup',
    ];


    /**
     * Instances a new object using array definition.
     *
     * @param array  $data      Fields data.
     * @param string $class_str Class name.
     *
     * @return object With current definition.
     */
    public static function build(array $data=[], string $class_str=__CLASS__)
    {
        return parent::build($data, $class_str);
    }


    /**
     * Builds a PandoraFMS\Group object from a group id.
     *
     * @param integer $id_group  Group Id.
     * @param boolean $recursive Create parents as objects.
     */
    public function __construct(?int $id_group=null, bool $recursive=false)
    {
        if ($id_group === 0) {
            parent::__construct('tgrupo');

            $this->fields['id'] = 0;
            $this->fields['nombre'] = 'All';
        } else if (is_numeric($id_group) === true) {
            parent::__construct('tgrupo', ['id_grupo' => $id_group]);
            if ($recursive === true) {
                // Customize certain fields.
                $this->fields['parent'] = new Group($this->fields['parent']);
            }
        } else {
            // Empty skel.
            parent::__construct('tgrupo');
        }

    }


    /**
     * Return an array of ids with all children
     *
     * @param boolean $ids_only               Return an array of id_groups or
     *                                        entire rows.
     * @param boolean $ignorePropagationState Search all children ignoring or
     *                                        depending on propagate_acl flag.
     *
     * @return array With all children.
     */
    public function getChildren(
        bool $ids_only=false,
        bool $ignorePropagationState=false
    ):array {
        $available_groups = \groups_get_children(
            $this->id_grupo(),
            $ignorePropagationState
        );

        if (is_array($available_groups) === false) {
            return [];
        }

        if ($ids_only === true) {
            return array_keys($available_groups);
        }

        return $available_groups;

    }


    /**
     * Alias of 'nombre'.
     *
     * @param string|null $name Name of group.
     *
     * @return string|void Name assigned or void if set operation.
     */
    public function name(?string $name=null)
    {
        if ($name === null) {
            return $this->nombre();
        }

        return $this->nombre($name);
    }


    /**
     * Retrieves a list of groups fitered.
     *
     * @param array $filter Filters to be applied.
     *
     * @return array With all results or false if error.
     * @throws Exception On error.
     */
    private static function search(array $filter):array
    {
        // Default values.
        if (empty($filter['id_user']) === true) {
            // By default query current user groups.
            $filter['id_user'] = false;
        } else if ((bool) \users_is_admin() === false) {
            // Override user queried if user is not an admin.
            $filter['id_user'] = false;
        }

        if (empty($filter['id_user']) === true) {
            $filter['id_user'] = false;
        }

        if (empty($filter['keys_field']) === true) {
            $filter['keys_field'] = 'id_grupo';
        }

        if (isset($filter['returnAllColumns']) === false) {
            $filter['returnAllColumns'] = true;
        }

        $groups = \users_get_groups(
            $filter['id_user'],
            $filter['privilege'],
            $filter['returnAllGroup'],
            // Return all columns.
            $filter['returnAllColumns'],
            // Field id_groups is not being used anymore.
            null,
            $filter['keys_field'],
            // Cache.
            true,
            // Search term.
            $filter['search']
        );

        if (is_array($groups) === false) {
            return [];
        }

        return $groups;

    }


    /**
     * Returns an hierarchical ordered array.
     *
     * @param array $groups All groups available.
     *
     * @return array Groups ordered.
     */
    private static function prepareGroups(array $groups):array
    {
        $return = [];
        $tree_groups = \groups_get_groups_tree_recursive($groups);
        foreach ($tree_groups as $k => $v) {
            $return[] = [
                'id'    => $k,
                'text'  => \io_safe_output(
                    \ui_print_truncate_text(
                        $v['nombre'],
                        GENERIC_SIZE_TEXT,
                        false,
                        true,
                        false
                    )
                ),
                'level' => $v['deep'],
            ];
        }

        $unassigned = [];
        $processed = array_keys($tree_groups);
        foreach ($groups as $k => $v) {
            if (in_array($k, $processed) === true) {
                continue;
            }

            $unassigned[] = [
                'id'    => $k,
                'text'  => \io_safe_output(
                    \ui_print_truncate_text(
                        $v,
                        GENERIC_SIZE_TEXT,
                        false,
                        true,
                        false
                    )
                ),
                'level' => 0,
            ];
        }

        return array_merge($unassigned, $return);
    }


    /**
     * Saves current group definition to database.
     *
     * @return mixed Affected rows of false in case of error.
     * @throws \Exception On error.
     */
    public function save()
    {
        if (\is_management_allowed() !== true) {
            $msg = 'cannot be modified in a centralized management environment';
            throw new \Exception(
                get_class($this).' error, '.$msg
            );
        }

        $updates = $this->fields;

        if (is_numeric($updates['parent']) === false) {
            $updates['parent'] = $this->parent()->id_grupo();
        }

        // Clean null fields.
        foreach ($updates as $k => $v) {
            if ($v === null) {
                unset($updates[$k]);
            }
        }

        if (isset($updates['propagate']) === false) {
            $updates['propagate'] = 0;
        }

        if (isset($updates['disabled']) === false) {
            $updates['disabled'] = 0;
        }

        if ($this->fields['id_grupo'] > 0) {
            return \db_process_sql_update(
                'tgrupo',
                $updates,
                ['id_grupo' => $this->fields['id_grupo']]
            );
        } else {
            // Create new group.
            $this->fields['id_grupo'] = \db_process_sql_insert(
                '\tgrupo',
                $updates
            );

            if ($this->fields['id_grupo'] === false) {
                global $config;
                $msg = __(
                    'Failed to save group %s',
                    $config['dbconnection']->error
                );
                throw new \Exception(
                    get_class($this).' error, '.$msg
                );
            } else {
                return true;
            }
        }

        return false;
    }


    /**
     * Delete this group.
     *
     * @return void
     */
    public function delete()
    {
        // Propagate parents.
        \db_process_sql_update(
            'tgrupo',
            ['parent' => $this->parent()->id_grupo()],
            ['parent' => $this->id_grupo()]
        );

        // Remove stats.
        \db_process_sql_delete(
            'tgroup_stat',
            ['id_group' => $this->id_grupo()]
        );

        // Remove group.
        \db_process_sql_delete(
            'tgrupo',
            ['id_grupo' => $this->id_grupo()]
        );

        unset($this->fields['id_grupo']);
    }


    /**
     * Return error message to target.
     *
     * @param string $msg Error message.
     *
     * @return void
     */
    public static function error(string $msg)
    {
        echo json_encode(['error' => $msg]);
    }


    /**
     * Verifies target method is allowed to be called using AJAX call.
     *
     * @param string $method Method to be invoked via AJAX.
     *
     * @return boolean Available (true), or not (false).
     */
    public static function ajaxMethod(string $method):bool
    {
        return in_array($method, self::$ajaxMethods) === true;
    }


    /**
     * This method is being invoked by select2 to improve performance while
     * installation has a lot of groups (more than 5k).
     *
     * Security applied in controller include/ajax/group.php.
     *
     * @return void
     * @throws \Exception On error.
     */
    public static function getGroupsForSelect()
    {
        $id_user = get_parameter('id_user', false);
        $privilege = get_parameter('privilege', 'AR');
        $returnAllGroup = get_parameter('returnAllGroup', false);
        $id_group = get_parameter('id_group', false);
        $keys_field = get_parameter('keys_field', 'id_grupo');
        $search = get_parameter('search', '');
        $step = get_parameter('step', 1);
        $limit = get_parameter('limit', false);
        $not_condition = get_parameter('not_condition', false);
        $exclusions = get_parameter('exclusions', '[]');
        $inclusions = get_parameter('inclusions', '[]');

        if (empty($id_user) === true) {
            $id_user = false;
        }

        $groups = self::search(
            [
                'id_user'          => $id_user,
                'privilege'        => $privilege,
                'returnAllGroup'   => $returnAllGroup,
                'returnAllColumns' => true,
                'id_group'         => $id_group,
                'keys_field'       => $keys_field,
                'search'           => $search,
            ]
        );

        $exclusions = json_decode(\io_safe_output($exclusions), true);
        if (empty($exclusions) === false) {
            foreach ($exclusions as $ex) {
                unset($groups[$ex]);
            }
        }

        $inclusions = json_decode(\io_safe_output($inclusions), true);
        if (empty($inclusions) === false) {
            foreach ($inclusions as $k => $g) {
                if (empty($groups[$k]) === true) {
                    if (is_numeric($g) === true) {
                        $groups[$k] = \groups_get_name($k);
                    }

                    if (empty($groups[$k]) === true) {
                        // Group does not exist, direct value assigned.
                        $groups[$k] = $g;
                    }
                }
            }
        }

        $return = self::prepareGroups($groups);

        // When not_condition is select firts option text change All to None.
        if ($not_condition === 'true') {
            $return[0]['text'] = 'None';
        }

        if (is_array($return) === false) {
            return;
        }

        // Use global block size configuration.
        global $config;
        $limit = $config['block_size'];
        $offset = (($step - 1) * $limit);

        // Pagination over effective groups retrieved.
        // Calculation is faster than transference.
        $count = count($return);
        if (is_numeric($offset) === true && $offset >= 0) {
            if (is_numeric($limit) === true && $limit > 0) {
                $return = array_splice($return, $offset, $limit);
            }
        }

        if ($step > 2) {
            $processed = (($step - 2) * $limit);
        } else {
            $processed = 0;
        }

        $current_ammount = (count($return) + $processed);

        echo json_encode(
            [
                'results'    => $return,
                'pagination' => [
                    'more' => $current_ammount < $count,
                ],
            ]
        );
    }


    /**
     * Draw a graph distribution so by group.
     *
     * @return void
     */
    public static function distributionBySoGraph()
    {
        global $config;
        $id_group = get_parameter('id_group', '');
        include_once $config['homedir'].'/include/functions_graph.php';

        $out = '<div style="flex: 0 0 300px; width:99%; height:100%;">';
        $out .= graph_so_by_group($id_group, 300, 200, false, false);
        $out .= '<div>';
        echo $out;
        return;
    }


    /**
     * Draw a graph events agent by group.
     *
     * @return void
     */
    public static function groupEventsByAgent()
    {
        global $config;
        $id_group = get_parameter('id_group', '');
        include_once $config['homedir'].'/include/functions_graph.php';

        $out = '<div style="flex: 0 0 300px; width:99%; height:100%;">';
        $out .= graph_events_agent_by_group($id_group, 300, 200, false, true, true);
        $out .= '<div>';
        echo $out;
        return;
    }


    /**
     * Draw in modal a agent info
     *
     * @return void
     */
    public static function loadInfoAgent()
    {
        $extradata = get_parameter('extradata', '');
        echo '<div class="info-agent">';

        if (empty($extradata) === false) {
            $extradata = json_decode(io_safe_output($extradata), true);
            $agent = agents_get_agent($extradata['idAgent']);

            if (is_array($agent)) {
                $status_img = agents_tree_view_status_img(
                    $agent['critical_count'],
                    $agent['warning_count'],
                    $agent['unknown_count'],
                    $agent['total_count'],
                    $agent['notinit_count']
                );
                $table = new \stdClass();
                $table->class = 'table_modal_alternate';
                $table->data = [
                    [
                        __('Id'),
                        $agent['id_agente'],
                    ],
                    [
                        __('Agent name'),
                        '<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=main&id_agente='.$agent['id_agente'].'"><b>'.$agent['nombre'].'</b></a>',
                    ],
                    [
                        __('Alias'),
                        $agent['alias'],
                    ],
                    [
                        __('Ip Address'),
                        $agent['direccion'],
                    ],
                    [
                        __('Status'),
                        $status_img,
                    ],
                    [
                        __('Group'),
                        groups_get_name($agent['id_grupo']),
                    ],
                    [
                        __('Interval'),
                        $agent['intervalo'],
                    ],
                    [
                        __('Operative system'),
                        get_os_name($agent['id_os']),
                    ],
                    [
                        __('Server name'),
                        $agent['server_name'],
                    ],
                    [
                        __('Description'),
                        $agent['comentarios'],
                    ],
                ];

                html_print_table($table);
            }
        }

        echo '</div>';
    }


    /**
     * Get agents by group  for datatable.
     *
     * @return void
     */
    public static function getAgentsByGroup()
    {
        global $config;

        $data = [];
        $id_group = get_parameter('id_group', '');
        $id_groups = [$id_group];
        $groups = groups_get_children($id_group);

        if (count($groups) > 0) {
            $id_groups = [];
            foreach ($groups as $key => $value) {
                $id_groups[] = $value['id_grupo'];
            }
        }

        $start  = get_parameter('start', 0);
        $length = get_parameter('length', $config['block_size']);
        $orderDatatable = get_datatable_order(true);
        $pagination = '';
        $order = '';

        try {
            ob_start();
            if (isset($orderDatatable)) {
                switch ($orderDatatable['field']) {
                    case 'alerts':
                        $orderDatatable['field'] = 'fired_count';
                    break;

                    case 'status':
                        $orderDatatable['field'] = 'total_count';

                    default:
                        $orderDatatable['field'] = $orderDatatable['field'];
                    break;
                }

                $order = sprintf(
                    ' ORDER BY %s %s',
                    $orderDatatable['field'],
                    $orderDatatable['direction']
                );
            }

            if (isset($length) && $length > 0
                && isset($start) && $start >= 0
            ) {
                $pagination = sprintf(
                    ' LIMIT %d OFFSET %d ',
                    $length,
                    $start
                );
            }

            $sql = sprintf(
                'SELECT id_agente,
                        alias,
                        critical_count,
                        warning_count,
                        unknown_count,
                        total_count,
                        notinit_count,
                        ultimo_contacto_remoto,
                        fired_count
                FROM tagente t
                WHERE disabled = 0 AND
                total_count <> notinit_count AND
                id_grupo IN (%s)
                %s %s',
                implode(',', $id_groups),
                $order,
                $pagination
            );

            $data = db_get_all_rows_sql($sql);

            $sql = sprintf(
                'SELECT
                        id_agente,
                        alias,
                        critical_count,
                        warning_count,
                        unknown_count,
                        total_count,
                        notinit_count,
                        ultimo_contacto_remoto,
                        fired_count
                FROM tagente t
                WHERE disabled = 0 AND
                total_count <> notinit_count AND
                id_grupo IN (%s)
                %s',
                implode(',', $id_groups),
                $order,
            );

            $count_agents = db_get_num_rows($sql);

            foreach ($data as $key => $agent) {
                $status_img = agents_tree_view_status_img(
                    $agent['critical_count'],
                    $agent['warning_count'],
                    $agent['unknown_count'],
                    $agent['total_count'],
                    $agent['notinit_count']
                );
                $data[$key]['alias'] = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$agent['id_agente'].'"><b>'.$agent['alias'].'</b></a>';
                $data[$key]['status'] = $status_img;
                $data[$key]['alerts'] = agents_tree_view_alert_img($agent['fired_count']);
            }

            if (empty($data) === true) {
                $total = 0;
                $data = [];
            } else {
                $total = $count_agents;
            }

            echo json_encode(
                [
                    'data'            => $data,
                    'recordsTotal'    => $total,
                    'recordsFiltered' => $total,
                ]
            );
            // Capture output.
            $response = ob_get_clean();
        } catch (\Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }

        json_decode($response);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo $response;
        } else {
            echo json_encode(
                [
                    'success' => false,
                    'error'   => $response,
                ]
            );
        }

        exit;
    }


}
