<?php
/**
 * Cluster entity class.
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
namespace PandoraFMS;

use PandoraFMS\Entity;
use PandoraFMS\Agent;
use PandoraFMS\Module;
use PandoraFMS\Group;

/**
 * PandoraFMS Cluster entity.
 */
class Cluster extends Entity
{

    /**
     * References cluster status Module.
     *
     * @var PandoraFMS\Module
     */
    private $clusterStatus;

    /**
     * Array of PandoraFMS\Agents members of this cluster.
     *
     * @var array
     */
    private $members = [];

    /**
     * AA modules.
     *
     * @var array
     */
    private $aaModules = [];

    /**
     * AP modules.
     *
     * @var array
     */
    private $apModules = [];

    /**
     * Removed items.
     *
     * @var array
     */
    private $removedItems = [];


    /**
     * Loads a cluster definition from target agent (rel 1-1).
     *
     * @param integer $id_agent     Agent id.
     * @param boolean $load_members Load members or not.
     *
     * @return PandoraFMS\Cluster Object.
     */
    public static function loadFromAgentId(
        int $id_agent,
        ?bool $load_members=true
    ) {
        if (is_numeric($id_agent) === true
            && $id_agent > 0
        ) {
            $cluster_id = db_get_value(
                'id',
                'tcluster',
                'id_agent',
                $id_agent
            );

            return new self($cluster_id, $load_members);
        }

        return null;
    }


    /**
     * Builds a PandoraFMS\ClusterViewer\Cluster object from a cluster id.
     *
     * @param integer $id_cluster   Cluster Id.
     * @param boolean $load_members Load members or not.
     *
     * @throws \Exception On error.
     */
    public function __construct(?int $id_cluster=null, ?bool $load_members=true)
    {
        if (is_numeric($id_cluster) === true
            && $id_cluster > 0
        ) {
            try {
                parent::__construct('tcluster', ['id' => $id_cluster]);
            } catch (\Exception $e) {
                throw new \Exception('Cluster id not found.');
            }

            if ($load_members === true) {
                // Retrieve members.
                $data = \db_get_all_rows_filter(
                    'tcluster_agent',
                    ['id_cluster' => $id_cluster]
                );

                if (is_array($data) === true) {
                    foreach ($data as $row) {
                        $this->addMember($row['id_agent']);
                    }
                }
            }

            // Retrieve items.
            $data = \db_get_all_rows_filter(
                'tcluster_item',
                ['id_cluster' => $id_cluster]
            );

            if (is_array($data) === true) {
                foreach ($data as $row) {
                    if ($row['item_type'] === 'AA') {
                        $this->aaModules[$row['name']] = new ClusterModule(
                            $row['id']
                        );
                    } else if ($row['item_type'] === 'AP') {
                        $this->apModules[$row['name']] = new ClusterModule(
                            $row['id']
                        );
                    }
                }
            }
        } else {
            parent::__construct('tcluster');
        }

        // Customize certain fields.
        try {
            $this->fields['group'] = new Group($this->group());
        } catch (\Exception $e) {
            $this->fields['group'] = new Group();
        }

        if ($this->id_agent() !== null) {
            try {
                $this->fields['agent'] = new Agent($this->id_agent(), true);
            } catch (\Exception $e) {
                $this->fields['agent'] = new Agent();
            }
        } else {
            $this->fields['agent'] = new Agent();
        }

        if ($this->id_agent() !== null) {
            $this->clusterStatus = Module::search(
                [
                    'nombre'    => io_safe_input('Cluster status'),
                    'id_agente' => $this->id_agent(),
                ],
                1
            );
        }
    }


    /**
     * Return an array of PandoraFMS\Agents as members of current cluster.
     *
     * @return array Of agents.
     */
    public function getMembers()
    {
        if (is_array($this->members) === true) {
            return $this->members;
        }

        return [];
    }


    /**
     * Cleans members from cluster object.
     *
     * @return void
     */
    public function cleanMembers()
    {
        unset($this->members);
    }


    /**
     * Register a new agent in the cluster.
     *
     * @param integer $id_agent New id_agent to be added.
     *
     * @return mixed
     * @throws \Exception On error.
     */
    public function addMember(int $id_agent)
    {
        if (isset($this->members[$id_agent]) === true) {
            // Already joining.
            return;
        }

        try {
            $agent = new Agent($id_agent);
        } catch (\Exception $e) {
            return;
        }

        if ($agent->id_agente() === null) {
            throw new \Exception('Invalid agent id.');
        }

        $this->members[$agent->id_agente()] = $agent;

        return $agent;
    }


    /**
     * Remove an agent from the cluster.
     *
     * @param integer $id_agent New id_agent to be removed.
     *
     * @return void
     * @throws \Exception On error.
     */
    public function removeMember(int $id_agent)
    {
        if (isset($this->members[$id_agent]) === false) {
            return;
        }

        unset($this->members[$id_agent]);

        $rs = \db_process_sql_delete(
            'tcluster_agent',
            [
                'id_cluster' => $this->fields['id'],
                'id_agent'   => $id_agent,
            ]
        );

        return true;
    }


    /**
     * Return AA modules associated to current cluster.
     *
     * @param integer $type AA or AP (use constants)
     *                      MODULE_PREDICTION_CLUSTER_AA
     *                      MODULE_PREDICTION_CLUSTER_AP.
     *
     * @return array Of items.
     */
    public function getItems(?int $type=null)
    {
        $items = [];

        if ($type === MODULE_PREDICTION_CLUSTER_AA) {
            if (is_array($this->aaModules) === true) {
                return $this->aaModules;
            }
        }

        if ($type === MODULE_PREDICTION_CLUSTER_AP) {
            if (is_array($this->apModules) === true) {
                return $this->apModules;
            }
        }

        if (is_array($this->apModules) === true
        ) {
            $items = array_merge($items, $this->apModules);
        }

        if (is_array($this->aaModules) === true
        ) {
            $items = array_merge($items, $this->aaModules);
        }

        return $items;
    }


    /**
     * Retrieve AA modules.
     *
     * @return array Of ClusterItem definition.
     */
    public function getAAModules()
    {
        return $this->getItems(MODULE_PREDICTION_CLUSTER_AA);
    }


    /**
     * Retrieve AP modules.
     *
     * @return array Of ClusterItem definition.
     */
    public function getAPModules()
    {
        return $this->getItems(MODULE_PREDICTION_CLUSTER_AP);
    }


    /**
     * Retrieves module definition from current members matching name.
     *
     * @param string $name Target name to retrieve.
     *
     * @return array Module fields.
     * @throws \Exception On error.
     */
    public function getModuleSkel(string $name)
    {
        foreach ($this->members as $member) {
            $module = $member->searchModules(
                ['nombre' => $name]
            );

            if ($module !== null && empty($module) === false) {
                if (count($module) > 1) {
                    $msg = __METHOD__.' error: Multiple occurrences of "';
                    $msg .= $name.'", please remove duplicates from agent "';
                    $msg .= $member->alias().'".';
                    throw new \Exception(
                        $msg
                    );
                }

                // Method searchModules returns multiple occurrences.
                $module = $module[0];
                $module = $module->toArray();
                break;
            }
        }

        // Remove specific fields.
        unset($module['id_agente_modulo']);
        unset($module['id_agente']);

        return $module;

    }


    /**
     * Add an item to the cluster.
     *
     * @param string  $name       Target name.
     * @param integer $type       Item type.
     * @param array   $definition Module definition.
     *
     * @return ClusterModule Created module.
     * @throws \Exception On error.
     */
    public function addItem(string $name, int $type, array $definition)
    {
        $item = new ClusterModule();
        $item->name($name);
        $item->id_cluster($this->id());

        // Skel values.
        $module_skel = $this->getModuleSkel($name);

        // Customize definition.
        $definition = array_merge($module_skel, $definition);

        // Store in cluster agent.
        $definition['id_agente'] = $this->id_agent();

        if ($type === MODULE_PREDICTION_CLUSTER_AA) {
            $item->item_type('AA');
        } else if ($type === MODULE_PREDICTION_CLUSTER_AP) {
            $item->item_type('AP');
        } else {
            throw new \Exception(__METHOD__.' error: Invalid item type');
        }

        // Set module definition.
        $item->setModule($definition);

        // Default values.
        $item->critical_limit(0);
        $item->warning_limit(0);
        $item->is_critical(0);

        return $item;
    }


    /**
     * Add AA module to the cluster.
     *
     * @param string $name Target name.
     *
     * @return void
     */
    public function addAAModule(string $name)
    {
        if (empty($this->aaModules[$name]) === true) {
            $main_id = $this->clusterStatus->id_agente_modulo();

            // Register module in agent.
            // id_modulo = 0,
            // tcp_port = 1,
            // prediction_moddule = 6.
            // Set thresholds while updating.
            $this->aaModules[$name] = $this->addItem(
                $name,
                MODULE_PREDICTION_CLUSTER_AA,
                [
                    'nombre'            => $name,
                    'id_modulo'         => 0,
                    'prediction_module' => 6,
                    'tcp_port'          => 1,
                    'id_tipo_modulo'    => 1,
                    'custom_integer_1'  => $this->id(),
                    'parent_module_id'  => $main_id,
                ]
            );

            \db_pandora_audit(
                AUDIT_LOG_AGENT_MANAGEMENT,
                'Module '.io_safe_output(
                    $name
                ).' added to cluster'.io_safe_output(
                    $this->fields['name']
                ).' as Active-Active module'
            );
        }
    }


    /**
     * Add AP module to the cluster.
     *
     * @param string $name Target name.
     *
     * @return void
     */
    public function addAPModule(string $name)
    {
        if (empty($this->apModules[$name]) === true) {
            $main_id = $this->clusterStatus->id_agente_modulo();

            $type = db_get_value(
                'id_tipo_modulo',
                'tagente_modulo',
                'nombre',
                $name
            );

            if (empty($type) === true) {
                $type = 1;
            }

            // Register module in agent.
            // id_modulo = 5,
            // tcp_port = 1,
            // prediction_moddule = 7.
            // Set thresholds while updating.
            $this->apModules[$name] = $this->addItem(
                $name,
                MODULE_PREDICTION_CLUSTER_AP,
                [
                    'nombre'            => $name,
                    'id_modulo'         => 5,
                    'prediction_module' => 7,
                    'tcp_port'          => 1,
                    'id_tipo_modulo'    => $type,
                    'custom_integer_1'  => $this->id(),
                    'parent_module_id'  => $main_id,
                ]
            );

            \db_pandora_audit(
                AUDIT_LOG_AGENT_MANAGEMENT,
                'Module '.io_safe_output(
                    $name
                ).' added to cluster'.io_safe_output(
                    $this->fields['name']
                ).' as Active-Passive module'
            );
        }
    }


    /**
     * Removes AA module from the cluster.
     *
     * @param string $name Target name.
     *
     * @return void
     */
    public function removeAAModule(string $name)
    {
        if (empty($this->aaModules[$name]) === false) {
            // Mark item for db elimination.
            $this->removedItems[] = [
                'id'        => $this->aaModules[$name]->id(),
                'item_type' => $this->aaModules[$name]->item_type(),
            ];
            $this->aaModules[$name]->delete();
            unset($this->aaModules[$name]);
        }
    }


    /**
     * Removes AP module from the cluster.
     *
     * @param string $name Target name.
     *
     * @return void
     */
    public function removeAPModule(string $name)
    {
        if (empty($this->apModules[$name]) === false) {
            // Mark item for db elimination.
            $this->removedItems[] = [
                'id'        => $this->apModules[$name]->id(),
                'item_type' => $this->apModules[$name]->item_type(),
            ];
            $this->apModules[$name]->delete();
            unset($this->apModules[$name]);
        }
    }


    /**
     * Return found cluster definitions.
     *
     * @param array $filter Conditions.
     *
     * @return mixed Array or false.
     */
    public static function search(array $filter)
    {
        return \db_get_all_rows_filter(
            'tcluster',
            $filter
        );
    }


    /**
     * Operates with group.
     *
     * @param integer|null $id_group Target group to update. Retrieve group obj
     *                               if null.
     *
     * @return mixed Void if set, PandoraFMS\Group if argument is null.
     */
    public function group(?int $id_group=null)
    {
        if (is_numeric($id_group) === true && $id_group > 0) {
            $this->fields['group'] = new Group($id_group);
        } else {
            return $this->fields['group'];
        }
    }


    /**
     * Returns AA modules as nodes for a map if any, if not, retrieves members.
     *
     * @return array Of PandoraFMS\Networkmap nodes.
     */
    public function getNodes()
    {
        // Parse agents.
        $nodes = [];
        $node_count = 0;
        $parent = $node_count;
        $id_node = $node_count++;
        $status = \agents_get_status_from_counts($this->agent()->toArray());
        $image = 'images/networkmap/'.os_get_icon($this->agent()->id_os());

        if (empty($this->aaModules) === true) {
            // No AA modules, use members.
            $parent = $this->agent()->id_agente();

            // Add node.
            foreach ($this->members as $agent) {
                $node = [];

                foreach ($agent->toArray() as $k => $v) {
                    $node[$k] = $v;
                }

                $node['id_agente'] = $agent->id_agente();
                $node['id_parent'] = $parent;
                $node['id_node'] = $node_count;
                $node['image'] = 'images/networkmap/'.os_get_icon(
                    $agent->id_os()
                );
                $node['status'] = \agents_get_status_from_counts(
                    $agent->toArray()
                );

                $nodes[$node_count++] = $node;
            }
        } else {
            foreach ($this->aaModules as $cl_item) {
                $cl_module = $cl_item->getModule();

                if ($cl_module === null) {
                    continue;
                }

                foreach ($this->members as $agent) {
                    $module = $agent->searchModules(
                        ['nombre' => $cl_module->nombre()]
                    );

                    if (empty($module) === true) {
                        // AA Module not found in member.
                        continue;
                    }

                    // Transform multi array to get first occurrence.
                    // Warning. Here must only be 1 result.
                    $module = array_shift($module);

                    $node = [];

                    $node['type'] = NODE_GENERIC;
                    $node['label'] = $agent->alias().' &raquo; ';
                    $node['label'] .= $module->nombre();
                    $node['id_agente'] = $module->id_agente();
                    $node['id_agente_modulo'] = $module->id_agente_modulo();
                    $node['id_parent'] = $parent;
                    $node['id_node'] = $node_count;
                    $node['image'] = 'images/networkmap/'.os_get_icon(
                        $agent->id_os()
                    );
                    $node['status'] = $module->getStatus()->last_known_status();

                    $nodes[$node_count++] = $node;
                }
            }
        }

        $nodes[$parent] = $this->agent()->toArray();
        $nodes[$parent] = ($nodes[$parent] + [
            'id_parent' => $parent,
            'id_node'   => $id_node,
            'status'    => $status,
            'id_agente' => $this->agent()->id_agente(),
            'image'     => $image,
        ]);

        return $nodes;
    }


    /**
     * Saves current group definition to database.
     *
     * @return mixed Affected rows of false in case of error.
     * @throws \Exception On error.
     */
    public function save()
    {
        $values = $this->fields;

        unset($values['agent']);
        $values['group'] = $this->group()->id_grupo();
        if (isset($values['id']) === true && $values['id'] > 0) {
            // Update.
            $rs = \db_process_sql_update(
                'tcluster',
                $values,
                ['id' => $this->fields['id']]
            );

            if ($rs === false) {
                global $config;
                throw new \Exception(
                    __METHOD__.' error: '.$config['dbconnection']->error
                );
            }

            \db_pandora_audit(
                AUDIT_LOG_AGENT_MANAGEMENT,
                'Cluster '.io_safe_output($this->fields['name']).' modified'
            );
        } else {
            // New.
            $rs = \db_process_sql_insert(
                'tcluster',
                $values
            );

            if ($rs === false) {
                global $config;
                throw new \Exception(
                    __METHOD__.' error: '.$config['dbconnection']->error
                );
            }

            $this->fields['id'] = $rs;
            \db_pandora_audit(
                AUDIT_LOG_AGENT_MANAGEMENT,
                'Cluster '.io_safe_output($this->fields['name']).' created'
            );
        }

        $this->saveMembers();
        $this->saveItems();

        return true;
    }


    /**
     * Updates entries in tcluster_agent.
     *
     * @return void
     * @throws \Exception On error.
     */
    public function saveMembers()
    {
        $err = __METHOD__.' error: ';

        $values = [];
        foreach ($this->members as $agent) {
            $values[$agent->id_agente()] = [
                'id_cluster' => $this->fields['id'],
                'id_agent'   => $agent->id_agente(),
            ];
        }

        if (empty($values) === true) {
            return;
        }

        // Clean previous relationships.
        $rs = \db_process_sql_delete(
            'tcluster_agent',
            [ 'id_cluster' => $this->fields['id'] ]
        );

        foreach ($values as $set) {
            // Add current relationships.
            $rs = \db_process_sql_insert(
                'tcluster_agent',
                $set
            );

            if ($rs === false) {
                global $config;
                throw new \Exception(
                    $err.$config['dbconnection']->error
                );
            }
        }
    }


    /**
     * Undocumented function
     *
     * @return void
     */
    public function saveItems()
    {
        $items = $this->getItems();

        foreach ($this->removedItems as $item) {
            \db_process_sql_delete(
                'tcluster_item',
                $item
            );
        }

        // Save cluster modules.
        foreach ($items as $item) {
            $item->save();
        }

    }


    /**
     * Force cluster status module to be executed.
     *
     * @param boolean $get_informed Throw exception if clusterStatus is null.
     *
     * @return void
     * @throws \Exception On error.
     */
    public function force(?bool $get_informed=true)
    {
        if ($this->clusterStatus === null) {
            if ($get_informed === true) {
                throw new \Exception(
                    __METHOD__.' error: Cluster status module does not exist'
                );
            }
        } else {
            $this->clusterStatus->flag(1);
            $this->clusterStatus->save();
        }
    }


    /**
     * Delete cluster from db.
     *
     * @return void
     * @throws \Exception On error.
     */
    public function delete()
    {
        global $config;

        if ($this->agent() !== null) {
            // Delete agent and modules.
            $this->agent()->delete();
        }

        // Remove entries from db.
        // Table tcluster_agent.
        $rs = \db_process_sql_delete(
            'tcluster_agent',
            ['id_cluster' => $this->fields['id']]
        );

        if ($rs === false) {
            throw new \Exception(
                __METHOD__.' error: '.$config['dbconnection']->error
            );
        }

        // Table tcluster_item.
        $rs = \db_process_sql_delete(
            'tcluster_item',
            ['id_cluster' => $this->fields['id']]
        );

        if ($rs === false) {
            throw new \Exception(
                __METHOD__.' error: '.$config['dbconnection']->error
            );
        }

        // Table tcluster.
        $rs = \db_process_sql_delete(
            'tcluster',
            ['id' => $this->fields['id']]
        );

        if ($rs === false) {
            throw new \Exception(
                __METHOD__.' error: '.$config['dbconnection']->error
            );
        }

        \db_pandora_audit(
            AUDIT_LOG_AGENT_MANAGEMENT,
            'Cluster '.io_safe_output($this->fields['name']).' deleted'
        );

        unset($this->aaModules);
        unset($this->apModules);
        unset($this->fields);

    }


}
