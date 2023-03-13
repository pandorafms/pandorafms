<?php
/**
 * Cluster view main class.
 *
 * @category   Class
 * @package    Pandora FMS
 * @subpackage Community
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
namespace PandoraFMS\ClusterViewer;

use PandoraFMS\View;
use PandoraFMS\Group;
use PandoraFMS\Cluster;

/**
 * Class to handle Cluster view operations.
 */
class ClusterManager
{

    /**
     * Url of controller.
     *
     * @var string
     */
    public $ajaxController;

    /**
     * Url (main).
     *
     * @var string
     */
    public $url;

    /**
     * Number of clusters defined.
     *
     * @var integer
     */
    private static $count;


    /**
     * Constructor
     *
     * @param string $ajax_page Path to ajax controller.
     * @param string $url       Url.
     */
    public function __construct(
        string $ajax_page='operation/cluster/cluster',
        string $url='index.php?sec=estado&sec2=operation/cluster/cluster'
    ) {
        global $config;

        check_login();

        if (! check_acl($config['id_user'], 0, 'AR')
            && ! check_acl($config['id_user'], 0, 'AW')
        ) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access cluster viewer'
            );

            if (is_ajax()) {
                echo json_encode(['error' => 'noaccess']);
            } else {
                include 'general/noaccess.php';
            }

            exit;
        }

        $this->ajaxController = $ajax_page;
        $this->url = $url;

    }


    /**
     * Main program starts here.
     *
     * @return void
     */
    public function run()
    {
        $operation = get_parameter('op', '');

        switch ($operation) {
            case 'new':
            case 'update':
                $this->showClusterEditor($operation);
            break;

            case 'view':
                $this->showCluster();
            break;

            case 'delete':
                $this->deleteCluster();
            break;

            case 'force':
                $this->forceCluster();
            break;

            default:
                $n_clusters = $this->getCount();

                if ($n_clusters > 0) {
                    $this->showList();
                } else {
                    $this->showWelcome();
                }
            break;
        }
    }


    /**
     * Prints error message
     *
     * @param string $msg Message.
     *
     * @return void
     */
    public function error(string $msg)
    {
        if (is_ajax()) {
            echo json_encode(
                ['error' => $msg]
            );
        } else {
            ui_print_error_message($msg);
        }
    }


    /**
     * Loads view 'first tasks' for cluster view.
     * Old style.
     *
     * @return void
     */
    public function showWelcome()
    {
        global $config;
        include_once $config['homedir'].'/general/first_task/cluster_builder.php';
    }


    /**
     * Prepares available clusters definition for current users and loads view.
     *
     * @param string|null $msg Message (if any).
     *
     * @return void
     */
    public function showList(?string $msg='')
    {
        global $config;

        // Extract data.
        $n_clusters = $this->getCount();

        if ($n_clusters > 0) {
            $clusters = $this->getAll();
        } else {
            $clusters = [];
        }

        View::render(
            'cluster/list',
            [
                'message'    => $msg,
                'config'     => $config,
                'model'      => $this,
                'n_clusters' => $n_clusters,
                'clusters'   => $clusters,
            ]
        );
    }


    /**
     * Show cluster information.
     *
     * @param string|null $msg Message (if any).
     *
     * @return void
     */
    public function showCluster(?string $msg=null)
    {
        global $config;

        $err = '';
        $id = get_parameter('id', null);

        try {
            $cluster = new Cluster($id);
        } catch (\Exception $e) {
            $err = ui_print_error_message(
                __('Cluster not found: '.$e->getMessage()),
                '',
                true
            );
        }

        if ($cluster->agent()->id_agente() === null) {
            // Failed.
            $err = ui_print_error_message(
                __('Cluster agent not found: '),
                '',
                true
            );
            $critical = true;
        }

        View::render(
            'cluster/view',
            [
                'message'  => $msg,
                'error'    => $err,
                'config'   => $config,
                'model'    => $this,
                'cluster'  => $cluster,
                'critical' => $critical,
            ]
        );
    }


    /**
     * Removes a cluster from db.
     *
     * @return void
     */
    public function deleteCluster()
    {
        $msg = '';
        $id = get_parameter('id', null);

        try {
            $cluster = new Cluster($id);
            $cluster->delete();
            unset($cluster);
        } catch (\Exception $e) {
            $msg = ui_print_error_message(
                __('Error while deleting, reason: %s', $e->getMessage()),
                '',
                true
            );
        }

        if (empty($msg) === true) {
            $msg = ui_print_success_message(
                __('Cluster successfully deleted.'),
                '',
                true
            );
        }

        $this->showList($msg);
    }


    /**
     * Force cluster execution.
     *
     * @return void
     */
    public function forceCluster()
    {
        $msg = '';
        $id = get_parameter('id', null);

        try {
            $cluster = new Cluster($id);
            $cluster->force();
            unset($cluster);
        } catch (\Exception $e) {
            $msg = ui_print_error_message(
                __('Error while forcing, reason: %s', $e->getMessage()),
                '',
                true
            );
        }

        if (empty($msg) === true) {
            $msg = ui_print_success_message(
                __('Cluster successfully forced.'),
                '',
                true
            );
        }

        $this->showCluster($msg);
    }


    /**
     * Shows editor for target cluster (or new one).
     *
     * @param string $operation Current operation.
     *
     * @return void
     */
    public function showClusterEditor(string $operation)
    {
        global $config;
        if (!check_acl($config['id_user'], 0, 'AW')) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to create clusters'
            );
            include 'general/noaccess.php';
        } else {
            $wizard = new ClusterWizard(
                $this->url,
                $operation
            );

            $wizard->run();
        }
    }


    /**
     * Returns number of clusters registered.
     *
     * @return integer
     */
    public function getCount()
    {
        if (isset($this->count) !== true) {
            $this->count = $this->getAll('count');
        }

        return $this->count;
    }


    /**
     * Return all cluster definitons matching given filters.
     *
     * @param mixed   $fields     Fields array or 'count' keyword to retrieve
     *                            count, null or default to use default ones.
     * @param array   $filter     Filters to be applied.
     * @param integer $offset     Offset (pagination).
     * @param integer $limit      Limit (pagination).
     * @param string  $order      Sort order.
     * @param string  $sort_field Sort field.
     *
     * @return array With all results or false if error.
     * @throws \Exception On error.
     */
    public static function getAll(
        $fields=null,
        array $filter=[],
        ?int $offset=null,
        ?int $limit=null,
        ?string $order=null,
        ?string $sort_field=null
    ) {
        $sql_filters = [];
        $order_by = '';
        $pagination = '';

        global $config;

        if (is_array($filter) === false) {
            throw new \Exception('[ClusterManager::getAll] Filter must be an array.');
        }

        if (empty($filter['id_group']) === false
            && (int) $filter['id_group'] !== 0
        ) {
            $sql_filters[] = sprintf(
                ' AND tc.`group` = %d',
                $filter['id_group']
            );
        }

        if (empty($filter['free_search']) === false) {
            $topic = io_safe_input($filter['free_search']);
            $sql_filters[] = sprintf(
                ' AND (lower(tc.name) like lower("%%%s%%")
                  OR lower(tc.description) like lower("%%%s%%") ) ',
                $topic,
                $topic
            );
        }

        $count = false;
        if (is_array($fields) === false && $fields === 'count') {
            $fields = ['tc.*'];
            $count = true;
        } else if (is_array($fields) === false) {
            // Default values.
            $fields = [
                'tc.*',
                '(SELECT COUNT(*) FROM `tcluster_agent` WHERE `id_cluster` = tc.`id`) as `nodes`',
                'tas.known_status',
            ];
        }

        if (isset($order) === true) {
            $dir = 'asc';
            if ($order === 'desc') {
                $dir = 'desc';
            };

            if ($sort_field === 'type') {
                $sort_field = 'cluster_type';
            }

            if (in_array(
                $sort_field,
                [
                    'name',
                    'description',
                    'group',
                    'cluster_type',
                    'nodes',
                    'known_status',
                ]
            ) === true
            ) {
                $order_by = sprintf(
                    'ORDER BY `%s` %s',
                    $sort_field,
                    $dir
                );
            }
        }

        if (isset($limit) === true && $limit > 0
            && isset($offset) === true && $offset >= 0
        ) {
            $pagination = sprintf(
                ' LIMIT %d OFFSET %d ',
                $limit,
                $offset
            );
        }

        $sql = sprintf(
            'SELECT %s
        FROM tcluster tc
        LEFT JOIN tagente ta
            ON tc.id_agent = ta.id_agente
        LEFT JOIN tagente_modulo tam
            ON tam.id_agente = tc.id_agent
            AND tam.nombre = "%s"
        LEFT JOIN tagente_estado tas
            ON tam.id_agente_modulo=tas.id_agente_modulo
        WHERE 1=1
        %s
        %s
        %s',
            join(',', $fields),
            io_safe_input('Cluster status'),
            join(' ', $sql_filters),
            $order_by,
            $pagination
        );

        if ($count === true) {
            $sql = sprintf('SELECT count(*) as n FROM ( %s ) tt', $sql);

            // Counter.. All data.
            return db_get_value_sql($sql);
        }

        return db_get_all_rows_sql($sql);

    }


    /**
     * Return data for datatables painting.
     *
     * @return void
     * @throws \Exception On Error.
     */
    public function draw()
    {
        global $config;

        // Datatables offset, limit and order.
        $filter = get_parameter('filter', []);
        $start = get_parameter('start', 0);
        $length = get_parameter('length', $config['block_size']);
        $order = get_datatable_order(true);
        try {
            ob_start();

            $fields = [
                'tc.*',
                '(SELECT COUNT(*) FROM `tcluster_agent` WHERE `id_cluster` = tc.`id`) as `nodes`',
                'tas.known_status',
            ];

            // Retrieve data.
            $data = self::getAll(
                // Fields.
                $fields,
                // Filter.
                $filter,
                // Offset.
                $start,
                // Limit.
                $length,
                // Order.
                $order['direction'],
                // Sort field.
                $order['field']
            );

            // Retrieve counter.
            $count = self::getAll(
                'count',
                $filter
            );

            if ($data) {
                $data = array_reduce(
                    $data,
                    function ($carry, $item) {
                        global $config;
                        // Transforms array of arrays $data into an array
                        // of objects, making a post-process of certain fields.
                        $tmp = (object) $item;

                        $manage = check_acl(
                            $config['id_user'],
                            $tmp->group,
                            'AW',
                            true
                        );

                        $tmp->name = '<b><a href="'.ui_get_full_url(
                            $this->url.'&op=view&id='.$tmp->id
                        ).'">'.$tmp->name.'</a></b>';

                        if (empty($tmp->group) === true) {
                            $tmp->group = __('Not set');
                        } else {
                            $tmp->group = ui_print_group_icon(
                                $tmp->group,
                                true
                            );
                        }

                        // Type.
                        if ($tmp->cluster_type === 'AA') {
                            $tmp->type = __('Active-Active');
                        } else if ($tmp->cluster_type === 'AP') {
                            $tmp->type = __('Active-Passive');
                        } else {
                            $tmp->type = __('Unknown');
                        }

                        // Status.
                        $tmp->known_status = ui_print_module_status(
                            $tmp->known_status,
                            true
                        );

                        // Options. View.
                        $tmp->options = '<a href="';
                        $tmp->options .= ui_get_full_url(
                            $this->url.'&op=view&id='.$tmp->id
                        );
                        $tmp->options .= '">';
                        $tmp->options .= html_print_image(
                            'images/details.svg',
                            true,
                            [
                                'title' => __('View'),
                                'class' => 'main_menu_icon invert_filter',
                            ]
                        );
                        $tmp->options .= '</a>';

                        if ($manage) {
                            // Options. Edit.
                            $tmp->options .= '<a href="';
                            $tmp->options .= ui_get_full_url(
                                $this->url.'&op=update&id='.$tmp->id
                            );
                            $tmp->options .= '">';
                            $tmp->options .= html_print_image(
                                'images/edit.svg',
                                true,
                                [
                                    'title' => __('Edit'),
                                    'class' => 'main_menu_icon invert_filter',
                                ]
                            );
                            $tmp->options .= '</a>';

                            // Options. Delete.
                            $tmp->options .= '<a href="';
                            $tmp->options .= ui_get_full_url(
                                $this->url.'&op=delete&id='.$tmp->id
                            );
                            $tmp->options .= '">';
                            $tmp->options .= html_print_image(
                                'images/delete.svg',
                                true,
                                [
                                    'title' => __('Delete'),
                                    'class' => 'main_menu_icon invert_filter',
                                ]
                            );
                            $tmp->options .= '</a>';
                        }

                        $carry[] = $tmp;
                        return $carry;
                    }
                );
            }

            // Datatables format: RecordsTotal && recordsfiltered.
            echo json_encode(
                [
                    'data'            => $data,
                    'recordsTotal'    => $count,
                    'recordsFiltered' => $count,
                ]
            );
            // Capture output.
            $response = ob_get_clean();
        } catch (\Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }

        // If not valid, show error with issue.
        json_decode($response);
        if (json_last_error() == JSON_ERROR_NONE) {
            // If valid dump.
            echo $response;
        } else {
            echo json_encode(
                ['error' => $response]
            );
        }
    }


    /**
     * Provides data for wizard. Ajax method.
     *
     * @return void
     */
    public function getAgentsFromGroup()
    {
        $side = get_parameter('side', null);
        $id = get_parameter('id', null);
        $group_id = get_parameter('group_id', 0);
        $group_recursion = (bool) get_parameter('group_recursion', 0);

        $groups = [];
        if ($group_recursion === true) {
            $groups = groups_get_children_ids($group_id, true);
        } else {
            $groups = $group_id;
        }

        if ($side === 'left') {
            // Available agents.
            $agents = agents_get_agents(
                [ 'id_grupo' => $groups ],
                [
                    'id_agente',
                    'alias',
                ]
            );

            $agents = array_reduce(
                $agents,
                function ($carry, $item) {
                    $carry[$item['id_agente']] = io_safe_output($item['alias']);
                    return $carry;
                }
            );
        } else if ($side === 'right') {
            // Selected agents.
            $cluster = new Cluster($id);
            $agents = $cluster->getMembers();
            $agents = array_reduce(
                $agents,
                function ($carry, $item) use ($groups) {
                    if (in_array($item->id_grupo(), $groups) === true) {
                        $carry[$item->id_agente()] = io_safe_output(
                            $item->alias()
                        );
                    }

                    return $carry;
                }
            );
        }

        if (empty($agents) === true) {
            echo '[]';
        } else {
            // Dump response.
            echo json_encode($agents);
        }
    }


    /**
     * Returns a goBack form structure.
     *
     * @return array Form structure.
     */
    public function getGoBackForm()
    {
        $form['form']['action'] = $this->url;
        $form['form']['method'] = 'POST';
        $form['form']['id'] = 'go-back-form';
        $form['inputs'] = [
            [
                'arguments' => [
                    'name'       => 'submit',
                    'label'      => __('Go back'),
                    'type'       => 'submit',
                    'attributes' => [
                        'icon' => 'back',
                        'mode' => 'secondary',
                    ],
                    'return'     => true,
                ],
            ],
        ];

        return $form;
    }


}
