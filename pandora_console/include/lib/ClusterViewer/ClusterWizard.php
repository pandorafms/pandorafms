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

global $config;

require_once $config['homedir'].'/include/class/HTML.class.php';

use \HTML;
use PandoraFMS\View;
use PandoraFMS\Cluster;

/**
 * Class to handle Cluster view operations.
 */
class ClusterWizard extends \HTML
{

    /**
     * Breadcrum titles.
     *
     * @var array
     */
    public $labels = [
        'Definition',
        'Members',
    ];

    /**
     * Label set for AA clusters.
     *
     * @var array
     */
    public $AALabels = [
        2 => 'A-A Modules',
        3 => 'A-A thresholds',
        6 => 'Alerts',
    ];

    /**
     * Label set for AP clusters.
     *
     * @var array
     */
    public $APLabels = [
        2 => 'A-P Modules',
        3 => 'A-P thresholds',
        4 => 'A-P module',
        5 => 'Critical A-P modules',
        6 => 'Alerts',
    ];

    /**
     * Variable to store error messages while parsing
     * different steps.
     *
     * @var string
     */
    public $errMessages = [];

    /**
     * Current operation (New | Update).
     *
     * @var string
     */
    public $operation;

    /**
     * Parent url (for go back forms).
     *
     * @var string
     */
    public $parentUrl;

    /**
     * Current cluster definition (if any).
     *
     * @var PandoraFMS\ClusterViewer\Cluster
     */
    private $cluster;

    /**
     * Current cluster agent definition (if any).
     *
     * @var array
     */
    private $agent;


    /**
     * Builds a Cluster Wizard.
     *
     * @param string $url       Main url.
     * @param string $operation Operation (new|update).
     */
    public function __construct(string $url, string $operation=null)
    {
        // Check access.
        check_login();

        ui_require_css_file('wizard');
        ui_require_css_file('discovery');

        $this->access = 'AW';
        $this->operation = $operation;
        $this->url = $url;
        $this->parentUrl = $url;
        $this->page = (int) get_parameter('page', 0);
        $this->id = (int) get_parameter('id', 0);
    }


    /**
     * Run wizard.
     *
     * @return void
     * @throws \Exception On error.
     */
    public function run()
    {
        global $config;

        ui_require_css_file('cluster_wizard');

        $this->operation = get_parameter('op', '');
        $cluster_id = get_parameter('id', '');
        $name = get_parameter('name', '');

        $this->errMessages = [];

        // Cluster initialization. Load.
        $load_success = true;

        try {
            if (empty($cluster_id) === false) {
                // Load data.
                $this->cluster = new Cluster($cluster_id);

                if ($this->cluster->agent()->id_agente() === null) {
                    $this->errMessages['noagent'] = 'Agent associated to cluster does not exist. Please update to create it.';
                }

                if ($this->cluster->group()->id_grupo() === null) {
                    throw new \Exception(
                        'Group associated to cluster does not exist.'
                    );
                }
            } else if (empty($name) === false) {
                $cluster_data = Cluster::search(['name' => $name]);

                if ($cluster_data !== false) {
                    $init_failed = true;
                    $this->page--;
                    throw new \Exception(
                        __('Cluster already defined, please use another name.')
                    );
                }
            }
        } catch (\Exception $e) {
            $this->errMessages[] = $e->getMessage();
            $load_success = false;
        }

        if (empty($this->cluster) === true) {
            // Empty cluster. Initialize.
            $this->cluster = new Cluster();
        } else {
            // Cluster already exists. Update operation.
            $this->operation = 'update';
        }

        try {
            // Check user has grants to edit this cluster.
            if ($this->operation !== 'new'
                && (!check_acl(
                    $config['id_user'],
                    $this->cluster->group()->id_grupo(),
                    'AW'
                ))
            ) {
                // User has no grants to edit this cluster.
                throw new \Exception(
                    'You have no permission to edit this cluster.'
                );
            }
        } catch (\Exception $e) {
            $this->errMessages[] = $e->getMessage();
            $load_success = false;
        }

        if ($load_success === true) {
            if ($this->cluster->id() === null
                && $this->page > 1
            ) {
                $load_success = false;
                $this->errMessages[] = 'Please define this cluster first';
            } else {
                try {
                    // Parse results (previous step).
                    $status = $this->parseForm();
                } catch (\Exception $e) {
                    $this->errMessages[] = $e->getMessage();
                    if ($this->page > 0) {
                        $this->page--;
                    }
                }
            }
        }

        // Get form structure (current page).
        $form = $this->getForm($load_success);

        // Force Cluster calculation. Cluster defined.
        $this->cluster->force(false);

        View::render(
            'cluster/edit',
            [
                'config'  => $config,
                'wizard'  => $this,
                'model'   => $this,
                'cluster' => $this->cluster,
                'form'    => $form,
            ]
        );
    }


    /**
     * Retrieve page value.
     *
     * @return integer Page value.
     */
    public function getPage()
    {
        return $this->page;
    }


    /**
     * Set page to target value.,
     *
     * @param integer $page New page value.
     *
     * @return void
     */
    public function setPage(int $page)
    {
        $this->page = $page;
    }


    /**
     * Return current operation.
     *
     * @return string New or Update.
     */
    public function getOperation()
    {
        return $this->operation;
    }


    /**
     * Retrieve labels.
     *
     * @return array With labels (breadcrum).
     */
    public function getLabels()
    {
        $labels = $this->labels;
        if ($this->cluster->cluster_type() !== null) {
            if ($this->cluster->cluster_type() === 'AA') {
                $labels = ($this->labels + $this->AALabels);
            } else {
                $labels = ($this->labels + $this->APLabels);
            }
        }

        return $labels;
    }


    /**
     * Returns a goBack form structure.
     *
     * @param boolean $main Go to main page.
     *
     * @return array Form structure.
     */
    public function getGoBackForm(?bool $main=false)
    {
        $url = $this->url;

        if ($main === false) {
            $page = ($this->page - 1);

            if ($page >= 0) {
                $extra_url = '';
                if ($this->cluster->id() !== null) {
                    $extra_url = '&id='.$this->cluster->id();
                    if ($this->cluster->cluster_type() === 'AA') {
                        // Jump from Alerts back to A-A Thresholds.
                        if ($page === 5) {
                            $page = 3;
                        }
                    }
                }

                $url = $this->url.'&op='.$this->operation;
                $url .= '&page='.$page.$extra_url;
            }
        }

        $form['form']['action'] = $url;
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


    /**
     * Parse responses from previous steps.
     *
     * @return void
     * @throws \Exception On error.
     */
    private function parseForm()
    {
        global $config;

        // Parse user responses.
        if ($this->page <= 0) {
            // No previous steps, return OK.
            return;
        }

        if ($this->page === 1) {
            /*
             *
             * PARSE DEFINITION.
             *
             */

            $name = get_parameter('name', '');
            $type = get_parameter('type', null);
            $description = get_parameter('description', '');
            $id_group = get_parameter('id_group', '');
            $server_name = get_parameter('server_name', '');

            if ($name === ''
                && $type === null
                && $description === ''
                && $id_group === ''
                && $server_name === ''
            ) {
                if ($this->cluster->id() === null) {
                    throw new \Exception(
                        'Please fulfill all required fields.'
                    );
                }

                // Default values, show page.
                return;
            }

            if (empty($name) === true
                || empty($type) === true
                || empty($id_group) === true
                || empty($server_name) === true
            ) {
                if (empty($server_name) === true) {
                        throw new \Exception(
                            'Please select a valid Prediction server'
                        );
                }

                throw new \Exception('Please fulfill all required fields');
            }

            // Verify cluster type is one from the list.
            if (in_array($type, ['AA', 'AP']) === false) {
                throw new \Exception('Invalid cluster type selected');
            }

            if ($this->cluster->id() === null) {
                // Create.
                // 1. Create agent.
                $this->cluster->agent()->alias($name);
                $this->cluster->agent()->comentarios($description);
                $this->cluster->agent()->intervalo(300);
                $this->cluster->agent()->id_grupo($id_group);
                $this->cluster->agent()->id_os(CLUSTER_OS_ID);
                $this->cluster->agent()->server_name($server_name);
                $this->cluster->agent()->modo(1);

                $this->cluster->agent()->save();

                if ($this->cluster->agent()->id_agente() === false) {
                    throw new \Exception(
                        'Failed to create agent: '.$config['dbconnection']->error
                    );
                }

                // 2. Create cluster entry.
                $this->cluster->name($name);
                $this->cluster->cluster_type($type);
                $this->cluster->description($description);
                $this->cluster->group($id_group);
                $this->cluster->id_agent($this->cluster->agent()->id_agente());

                $this->cluster->save();

                if ($this->cluster->id() === null) {
                    // Delete agent created in previous step.
                    \agents_delete_agent($this->cluster->agent()->id());

                    throw new \Exception(
                        'Failed to create cluster: '.$config['dbconnection']->error
                    );
                }

                // 3. Create cluster module in agent.
                $this->cluster->agent()->addModule(
                    [
                        'nombre'            => io_safe_input('Cluster status'),
                        'id_modulo'         => 5,
                        'prediction_module' => 5,
                        'custom_integer_1'  => $this->cluster->id(),
                        'id_tipo_modulo'    => 1,
                        'descripcion'       => io_safe_input(
                            'Cluster status information module'
                        ),
                        'min_warning'       => 1,
                        'min_critical'      => 2,
                    ]
                );
            } else {
                // Update.
                $this->cluster->name($name);
                $this->cluster->cluster_type($type);
                $this->cluster->description($description);
                $this->cluster->group($id_group);

                $this->cluster->agent()->alias($name);
                $this->cluster->agent()->comentarios($description);
                $this->cluster->agent()->intervalo(300);
                $this->cluster->agent()->id_grupo($id_group);
                $this->cluster->agent()->id_os(CLUSTER_OS_ID);
                $this->cluster->agent()->server_name($server_name);
                $this->cluster->agent()->modo(1);
                $this->cluster->agent()->save();

                // 2. Re link.
                $this->cluster->id_agent($this->cluster->agent()->id_agente());
                $this->cluster->save();

                // If agent has been deleted, recreate module.
                if ($this->errMessages['noagent'] !== null) {
                    // 3. Create cluster module in agent.
                    $this->cluster->agent()->addModule(
                        [
                            'nombre'            => io_safe_input('Cluster status'),
                            'id_modulo'         => 5,
                            'prediction_module' => 5,
                            'custom_integer_1'  => $this->cluster->id(),
                            'id_tipo_modulo'    => 1,
                            'descripcion'       => io_safe_input(
                                'Cluster status information module'
                            ),
                            'min_warning'       => 1,
                            'min_critical'      => 2,
                        ]
                    );
                }

                unset($this->errMessages['noagent']);
            }

            return;
        }

        if ($this->page === 2) {
            /*
             *
             * PARSE MEMBERS.
             *
             */

            // Parse responses from page 1.
            $agents_selected = get_parameter('selected-select-members', null);

            if ($agents_selected === null) {
                // Direct access.
                return;
            }

            // Clear members. Reparse.
            $this->cluster->cleanMembers();

            // Remove 'None' field.
            if (array_search(0, $agents_selected) === 0) {
                unset($agents_selected[0]);
            }

            if (empty($agents_selected) === true) {
                throw new \Exception('No members selected');
            }

            foreach ($agents_selected as $id_agent) {
                $agent = $this->cluster->addMember($id_agent);

                \db_pandora_audit(
                    AUDIT_LOG_AGENT_MANAGEMENT,
                    'Agent '.io_safe_output($agent->alias()).' added to cluster '.io_safe_output(
                        $this->cluster->name()
                    )
                );
            }

            $this->cluster->save();

            return;
        }

        if ($this->page === 3) {
            /*
             *
             * PARSE AA MODULES.
             *
             */

            $aa_modules = get_parameter('selected-select-aa-modules', null);

            if (is_array($aa_modules) === true) {
                if ($aa_modules[0] === '0') {
                    unset($aa_modules[0]);
                }

                $current = array_keys($this->cluster->getAAModules());
                $removed = array_diff($current, $aa_modules);
                $changes = false;

                foreach ($aa_modules as $m) {
                    $this->cluster->addAAModule($m);
                    $changes = true;
                }

                foreach ($removed as $m) {
                    $this->cluster->removeAAModule($m);
                    $changes = true;
                }

                if ($changes === true) {
                    $this->cluster->save();
                }
            }

            return;
        }

        if ($this->page === 4) {
            /*
             *
             * PARSE AA THRESHOLDS
             *
             */

            $modules = $this->cluster->getAAModules();

            $changes = false;

            foreach ($modules as $item) {
                $value_warning = get_parameter(
                    'warning-'.md5($item->name()),
                    null
                );

                $value_critical = get_parameter(
                    'critical-'.md5($item->name()),
                    null
                );

                if ($value_warning !== null) {
                    $item->warning_limit($value_warning);
                    $changes = true;
                }

                if ($value_critical !== null) {
                    $item->critical_limit($value_critical);
                    $changes = true;
                }
            }

            if ($changes === true) {
                $this->cluster->save();
            }

            if ($this->cluster->cluster_type() === 'AA') {
                // Force next page '6' (alerts).
                $this->page = 6;
            }

            return;
        }

        if ($this->page === 5) {
            /*
             *
             * PARSE AP MODULES
             *
             */

            if ($this->cluster->cluster_type() === 'AA') {
                // Direct access. Accessed by URL.
                $this->page = 0;
                throw new \Exception(
                    'Unavailable page for this cluster type, please follow this wizard.'
                );
            }

            $ap_modules = get_parameter('selected-select-ap-modules', null);
            if (is_array($ap_modules) === true) {
                if ($ap_modules[0] === '0') {
                    unset($ap_modules[0]);
                }

                $current = array_keys($this->cluster->getAPModules());
                $removed = array_diff($current, $ap_modules);
                $changes = false;

                foreach ($ap_modules as $m) {
                    $this->cluster->addAPModule($m);
                    $changes = true;
                }

                foreach ($removed as $m) {
                    $this->cluster->removeAPModule($m);
                    $changes = true;
                }

                if ($changes === true) {
                    $this->cluster->save();
                }
            }

            return;
        }

        if ($this->page === 6) {
            /*
             *
             * PARSE AP MODULES CRITICAL
             *
             */

            if ($this->cluster->cluster_type() === 'AA') {
                // Direct access.
                return;
            }

            $modules = $this->cluster->getAPModules();
            $changes = false;

            foreach ($modules as $item) {
                $value = get_parameter_switch(
                    'switch-'.md5($item->name()),
                    null
                );

                if ($value !== null) {
                    // Unchecked.
                    $item->is_critical($value);
                    $changes = true;
                }
            }

            if ($changes === true) {
                $this->cluster->save();
            }

            return;
        }

        if ($this->page === 7) {
            /*
             *
             * PARSE ALERTS
             *
             */

            // There is no need to parse anything. Already managed by alert
            // builder.
            header('Location: '.$this->url.'&op=view&id='.$this->cluster->id());
        }

        throw new \Exception('Unexpected error');
    }


    /**
     * Retrieves form estructure for current step.
     *
     * @param boolean $load_success Load process has been success or not.
     *
     * @return array Form.
     */
    private function getForm(?bool $load_success=true)
    {
        $form = [];
        $final = false;

        $extra_url = '';
        if ($this->cluster->id() !== null) {
            $extra_url = '&id='.$this->cluster->id();
        }

        $url = $this->url.'&op='.$this->operation;
        $target_url .= $url.'&page='.($this->page + 1).$extra_url;

        $form['form'] = [
            'action' => $target_url,
            'method' => 'POST',
            'extra'  => 'autocomplete="false"',
            'id'     => 'cluster-edit-'.($this->page + 1),
        ];

        if ($load_success === false && $this->page !== 0) {
            return [];
        }

        if ($this->page === 0) {
            /*
             *
             * Page: Cluster Definition.
             *
             */

            // Input cluster name.
            $form['inputs'][] = [
                'label'     => '<b>'.__('Cluster name').'</b>'.ui_print_help_tip(
                    __('An agent with the same name of the cluster will be created, as well a special service with the same name'),
                    true
                ),
                'arguments' => [
                    'name'     => 'name',
                    'value'    => $this->cluster->name(),
                    'type'     => 'text',
                    'size'     => 25,
                    'required' => true,
                ],
            ];

            // Input cluster type.
            $form['inputs'][] = [
                'label'     => '<b>'.__('Cluster type').'</b>'.ui_print_help_tip(
                    __('AA is a cluster where all members are working. In AP cluster only master member is working'),
                    true
                ),
                'arguments' => [
                    'name'     => 'type',
                    'selected' => $this->cluster->cluster_type(),
                    'type'     => 'select',
                    'fields'   => [
                        'AA' => __('Active - Active'),
                        'AP' => __('Active - Pasive'),
                    ],
                    'required' => true,
                ],
            ];

            // Input cluster description.
            $form['inputs'][] = [
                'label'     => '<b>'.__('Description').'</b>',
                'arguments' => [
                    'name'  => 'description',
                    'value' => $this->cluster->description(),
                    'type'  => 'text',
                    'size'  => 25,
                ],
            ];

            // Input Group.
            $form['inputs'][] = [
                'label'     => '<b>'.__('Group').'</b>'.ui_print_help_tip(
                    __('Target cluster agent will be stored under this group'),
                    true
                ),
                'arguments' => [
                    'name'           => 'id_group',
                    'returnAllGroup' => false,
                    'privilege'      => $this->access,
                    'type'           => 'select_groups',
                    'selected'       => $this->cluster->group()->id_grupo(),
                    'return'         => true,
                    'required'       => true,
                ],
            ];

            // Input. Servername.
            $form['inputs'][] = [
                'label'     => '<b>'.__('Prediction server').':</b>'.ui_print_help_tip(
                    __('You must select a Prediction Server to perform all cluster status calculations'),
                    true
                ),
                'arguments' => [
                    'type'     => 'select_from_sql',
                    'sql'      => sprintf(
                        'SELECT name as k, name as v
                                    FROM tserver
                                    WHERE server_type = %d
                                    ORDER BY name',
                        SERVER_TYPE_PREDICTION
                    ),
                    'name'     => 'server_name',
                    'selected' => $this->cluster->agent()->server_name(),
                    'return'   => true,
                    'required' => true,
                ],
            ];
        } else if ($this->page === 1) {
            /*
             *
             * Page: Cluster members.
             *
             */

            $all_agents = agents_get_agents(
                false,
                [
                    'id_agente',
                    'alias',
                ]
            );

            if ($all_agents === false) {
                $all_agents = [];
            }

            $all_agents = array_reduce(
                $all_agents,
                function ($carry, $item) {
                    $carry[$item['id_agente']] = $item['alias'];
                    return $carry;
                },
                []
            );

            $selected = $this->cluster->getMembers();

            $selected = array_reduce(
                $selected,
                function ($carry, $item) use (&$all_agents) {
                    $carry[$item->id_agente()] = $item->alias();
                    unset($all_agents[$item->id_agente()]);
                    return $carry;
                },
                []
            );

            $form['inputs'][] = [
                'arguments' => [
                    'type'         => 'select_multiple_filtered',
                    'class'        => 'w80p mw600px',
                    'name'         => 'members',
                    'available'    => $all_agents,
                    'selected'     => $selected,
                    'group_filter' => [
                        'page'   => 'operation/cluster/cluster',
                        'method' => 'getAgentsFromGroup',
                        'id'     => $this->cluster->id(),
                    ],
                    'texts'        => [
                        'title-left'  => 'Available agents',
                        'title-right' => 'Selected cluster members',
                    ],
                ],
            ];
        } else if ($this->page === 2) {
            /*
             *
             * Page: A-A modules.
             *
             */

            $selected = $this->cluster->getAAModules();

            $selected = array_reduce(
                $selected,
                function ($carry, $item) {
                    $name = io_safe_output($item->name());
                    $carry[$name] = $name;
                    return $carry;
                },
                []
            );

            $members = $this->cluster->getMembers();

            // Agent ids are stored in array keys.
            $members = array_keys($members);

            // Get common modules.
            $modules = \select_modules_for_agent_group(
                // Module group. 0 => All.
                0,
                // Agents.
                $members,
                // Show all modules or common ones.
                false,
                // Return.
                false,
                // Group by name.
                true
            );

            // Escape html special chars on array keys for select value.
            $modules = array_combine(
                array_map(
                    function ($k) {
                        return htmlspecialchars($k);
                    },
                    array_keys($modules)
                ),
                $modules
            );

            $selected = array_combine(
                array_map(
                    function ($k) {
                        return htmlspecialchars($k);
                    },
                    array_keys($selected)
                ),
                $selected
            );

            $modules = array_diff_key($modules, $selected);
            if ($this->cluster->cluster_type() === 'AP') {
                $form['inputs'][] = [
                    'arguments' => [
                        'type'      => 'select_multiple_filtered',
                        'class'     => 'w80p mw600px',
                        'name'      => 'aa-modules',
                        'available' => $modules,
                        'selected'  => $selected,
                        'texts'     => [
                            'title-left'  => 'Available modules (common)',
                            'title-right' => 'Selected active-passive modules',
                            'filter-item' => 'Filter options by module name',
                        ],
                        'sections'  => [
                            'filters'               => 1,
                            'item-available-filter' => 1,
                            'item-selected-filter'  => 1,
                        ],
                    ],
                ];
            } else if ($this->cluster->cluster_type() === 'AA') {
                    $form['inputs'][] = [
                        'arguments' => [
                            'type'      => 'select_multiple_filtered',
                            'class'     => 'w80p mw600px',
                            'name'      => 'aa-modules',
                            'available' => $modules,
                            'selected'  => $selected,
                            'texts'     => [
                                'title-left'  => 'Available modules (common)',
                                'title-right' => 'Selected active-active modules',
                                'filter-item' => 'Filter options by module name',
                            ],
                            'sections'  => [
                                'filters'               => 1,
                                'item-available-filter' => 1,
                                'item-selected-filter'  => 1,
                            ],
                        ],
                    ];
            }
        } else if ($this->page === 3) {
            /*
             *
             * Page: A-A module limits.
             *
             */

            $aa_modules = $this->cluster->getAAModules();
            $inputs = [];
            foreach ($aa_modules as $module) {
                $inputs[] = [
                    'block_id'      => 'from-to-threshold',
                    'label'         => '<b>'.$module->name().'</b>',
                    'class'         => 'flex-row line w100p',
                    'direct'        => 1,
                    'block_content' => [
                        [
                            'label' => '<i>'.$module->name().'</i>',
                        ],
                        [
                            'label'     => '<b class="state">'.__('critical if').'</b>',
                            'arguments' => [
                                'name'     => 'critical-'.md5($module->name()),
                                'type'     => 'number',
                                'value'    => $module->critical_limit(),
                                'required' => true,
                            ],
                        ],
                        [
                            'label' => __('% of balanced modules are down (equal or greater).'),
                        ],
                    ],
                ];

                $inputs[] = [
                    'block_id'      => 'from-to-threshold',
                    'class'         => 'flex-row line w100p',
                    'direct'        => 1,
                    'block_content' => [
                        [
                            'label' => '<i>'.$module->name().'</i>',
                        ],
                        [
                            'label'     => '<b class="state">'.('warning if').'</b>',
                            'arguments' => [
                                'name'     => 'warning-'.md5($module->name()),
                                'type'     => 'number',
                                'value'    => $module->warning_limit(),
                                'required' => true,
                            ],
                        ],
                        [
                            'label' => __('% of balanced modules are down (equal or greater).'),
                        ],
                    ],
                ];

                $inputs[] = [
                    'block_id'      => 'from-to-threshold',
                    'class'         => 'flex-row line w100p',
                    'direct'        => 1,
                    'block_content' => [],
                ];
            }

            if ($this->cluster->cluster_type() === 'AP') {
                $form['inputs'][] = [
                    'label'         => __(
                        'Please, set thresholds for all active-passive modules'.ui_print_help_tip(
                            'If you want your cluster module to be critical when 3 of 6 instances are down, set critical to \'50%\'',
                            true
                        )
                    ),
                    'class'         => 'indented',
                    'block_content' => $inputs,
                ];
            } else if ($this->cluster->cluster_type() === 'AA') {
                $form['inputs'][] = [
                    'label'         => __(
                        'Please, set thresholds for all active-active modules'.ui_print_help_tip(
                            'If you want your cluster module to be critical when 3 of 6 instances are down, set critical to \'50%\'',
                            true
                        )
                    ),
                    'class'         => 'indented',
                    'block_content' => $inputs,
                ];
            }
        } else if ($this->page === 4) {
            /*
             *
             * Page: A-P modules.
             *
             */

            $selected = $this->cluster->getAPModules();
            $aa = $this->cluster->getAAModules();

            $selected = array_reduce(
                $selected,
                function ($carry, $item) {
                    $name = io_safe_output($item->name());
                    $carry[$name] = $name;
                    return $carry;
                },
                []
            );

            $aa = array_reduce(
                $aa,
                function ($carry, $item) {
                    $name = io_safe_output($item->name());
                    $carry[$name] = $name;
                    return $carry;
                },
                []
            );

            $members = $this->cluster->getMembers();

            // Agent ids are stored in array keys.
            $members = array_keys($members);

            // Get common modules.
            $modules = \select_modules_for_agent_group(
                // Module group. 0 => All.
                0,
                // Agents.
                $members,
                // Show all modules or common ones.
                true,
                // Return.
                false,
                // Group by name.
                true
            );

            // Exclude AA modules from available options.
            $modules = array_diff_key($modules, $aa);

            // Exclude already used from available options.
            $modules = array_diff_key($modules, $selected);

            $form['inputs'][] = [
                'arguments' => [
                    'type'      => 'select_multiple_filtered',
                    'class'     => 'w80p mw600px',
                    'name'      => 'ap-modules',
                    'available' => $modules,
                    'selected'  => $selected,
                    'texts'     => [
                        'title-left'  => 'Available modules (any)',
                        'title-right' => 'Selected active-passive modules',
                        'filter-item' => 'Filter options by module name',
                    ],
                    'sections'  => [
                        'filters'               => 1,
                        'item-available-filter' => 1,
                        'item-selected-filter'  => 1,
                    ],
                ],
            ];
        } else if ($this->page === 5) {
            /*
             *
             * Page: A-P critical modules.
             *
             */

            $ap_modules = $this->cluster->getAPModules();
            $inputs = [];
            foreach ($ap_modules as $module) {
                $inputs[] = [
                    'label'     => $module->name(),
                    'arguments' => [
                        'type'  => 'switch',
                        'name'  => 'switch-'.md5($module->name()),
                        'value' => $module->is_critical(),
                    ],
                ];
            }

            $form['inputs'][] = [
                'label'         => __(
                    'Please, check all active-passive modules critical for this cluster'
                ).ui_print_help_tip(
                    __('If a critical balanced module is going to critical status, then cluster will be critical.'),
                    true
                ),
                'class'         => 'indented',
                'block_content' => $inputs,
            ];
        } else if ($this->page === 6) {
            /*
             *
             * Page: Alerts.
             *
             */

            ob_start();
            global $config;

            $id_agente = $this->cluster->agent()->id_agente();
            $dont_display_alert_create_bttn = true;
            include_once $config['homedir'].'/godmode/alerts/alert_list.php';
            include_once $config['homedir'].'/godmode/alerts/alert_list.builder.php';

            // XXX: Please do not use this kind of thing never more.
            $hack = ob_get_clean();

            // TODO: Alert form.
            $form['pre-content'] = $hack;

            $final = true;
        }

        // Submit.
        $str = __('Next');
        if ($this->cluster->id() !== null) {
            $str = __('Update and continue');
        }

        if ($final === true) {
            $str = __('Finish');
        }

        // Submit button.
        $form['submit-external-input'] = [
            'name'       => 'next',
            'label'      => $str,
            'type'       => 'submit',
            'attributes' => [
                'icon' => 'wand',
                'mode' => 'primary',
                'form' => 'cluster-edit-'.($this->page + 1),
            ],
            'return'     => true,
        ];

        return $form;
    }


}
