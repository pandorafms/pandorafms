<?php
/**
 * Tips to Pandora FMS feature.
 *
 * @category   Class
 * @package    Pandora FMS
 * @subpackage Tips Window
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 * |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 * |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2024 Pandora FMS
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

// Begin.
global $config;

/**
 * Class Prd.
 */
class Prd
{

    /**
     * Prd data.
     *
     * @var array
     */
    private $prdData;

    /**
    * tgrupo reference.
    *
    * @var array
    */
    private $tgrupo;

    /**
    * ttipo_modulo reference.
    *
    * @var array
    */
    private $ttipo_modulo;

    /**
    * tmodule_group reference.
    *
    * @var array
    */
    private $tmodule_group;

    /**
    * tconfig_os reference.
    *
    * @var array
    */
    private $tconfig_os;

    /**
    * tcategory reference.
    *
    * @var array
    */
    private $tcategory;

    /**
    * ttag reference.
    *
    * @var array
    */
    private $ttag;

    /**
    * tagente reference.
    *
    * @var array
    */
    private $tagente;

    /**
    * tagente_modulo reference.
    *
    * @var array
    */
    private $tagente_modulo;

    /**
    * tplugin reference.
    *
    * @var array
    */
    private $tplugin;

    /**
    * tmodule_inventory reference.
    *
    * @var array
    */
    private $tmodule_inventory;

    /**
    * tpolicies reference.
    *
    * @var array
    */
    private $tpolicies;

    /**
    * tpolicy_modules reference.
    *
    * @var array
    */
    private $tpolicy_modules;

    /**
    * talert_actions reference.
    *
    * @var array
    */
    private $talert_actions;

    /**
    * talert_templates reference.
    *
    * @var array
    */
    private $talert_templates;

    /**
    * tcollection reference.
    *
    * @var array
    */
    private $tcollection;

    /**
    * tgraph reference.
    *
    * @var array
    */
    private $tgraph;

    /**
    * tservice reference.
    *
    * @var array
    */
    private $tservice;

    /**
    * tlayout reference.
    *
    * @var array
    */
    private $tlayout;

    /**
    * tlayout_data reference.
    *
    * @var array
    */
    private $tlayout_data;

    /**
    * treport_custom_sql reference.
    *
    * @var array
    */
    private $treport_custom_sql;

    /**
    * tserver_export reference.
    *
    * @var array
    */
    private $tserver_export;

    /**
    * trecon_task reference.
    *
    * @var array
    */
    private $trecon_task;

    /**
    * tmap reference.
    *
    * @var array
    */
    private $tmap;

    /**
    * titem reference.
    *
    * @var array
    */
    private $titem;

    /**
    * tgis_map_connection reference.
    *
    * @var array
    */
    private $tgis_map_connection;

    /**
    * tserver reference.
    *
    * @var array
    */
    private $tserver;

    /**
    * twidget reference.
    *
    * @var array
    */
    private $twidget;

    /**
    * treport reference.
    *
    * @var array
    */
    private $treport;

    /**
     * Column references.
     *
     * @var array
     */
    private $columnRefs;

    /**
     * Json references.
     *
     * @var array
     */
    private $jsonRefs;

    /**
     * Base64 references.
     *
     * @var array
     */
    private $base64Refs;

    /**
     * Current item.
     *
     * @var array
     */
    private $currentItem;

    /**
     * Import return result
     *
     * @var array
     */
    private $result;

    /**
     * Crossed items references.
     *
     * @var array
     */
    private $itemsReferences;


    /**
     * Constructor.
     *
     * @throws Exception On error.
     */
    public function __construct()
    {
        $this->prdData = [
            'visual_console' => [
                'label' => __('Visual console'),
                'items' => [
                    'table' => 'tlayout',
                    'value' => ['id'],
                    'show'  => ['name'],
                    'data'  => [
                        [
                            'table' => 'tlayout_data',
                            'ref'   => ['id_layout'],
                            'value' => ['id'],
                        ],
                    ],
                ],
            ],
            'custom_report'  => [
                'label' => __('Custom report'),
                'items' => [
                    'table' => 'treport',
                    'value' => ['id_report'],
                    'show'  => ['name'],
                    'data'  => [
                        [
                            'table' => 'treport_content',
                            'ref'   => ['id_report'],
                            'value' => ['id_rc'],
                            'data'  => [
                                [
                                    'table' => 'treport_content_item',
                                    'ref'   => ['id_report_content'],
                                    'value' => ['id'],
                                ],
                                [
                                    'table' => 'treport_content_sla_combined',
                                    'ref'   => ['id_report_content'],
                                    'value' => ['id'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'policy'         => [
                'label' => __('Policy'),
                'items' => [
                    'table' => 'tpolicies',
                    'value' => ['id'],
                    'show'  => ['name'],
                    'data'  => [
                        [
                            'table' => 'tpolicy_agents',
                            'ref'   => ['id_policy'],
                            'value' => ['id'],
                        ],
                        [
                            'table' => 'tpolicy_alerts',
                            'ref'   => ['id_policy'],
                            'value' => ['id'],
                            'data'  => [
                                [
                                    'table' => 'tpolicy_alerts_actions',
                                    'ref'   => ['id_policy_alert'],
                                    'value' => ['id'],
                                ],
                            ],
                        ],
                        [
                            'table' => 'tpolicy_collections',
                            'ref'   => ['id_policy'],
                            'value' => ['id'],
                        ],
                        [
                            'table' => 'tpolicy_group_agents',
                            'ref'   => ['id_policy'],
                            'value' => ['id'],
                        ],
                        [
                            'table' => 'tpolicy_groups',
                            'ref'   => ['id_policy'],
                            'value' => ['id'],
                        ],
                        [
                            'table' => 'tpolicy_modules',
                            'ref'   => ['id_policy'],
                            'value' => ['id'],
                            'data'  => [
                                [
                                    'table' => 'ttag_policy_module',
                                    'ref'   => ['id_policy_module'],
                                    'value' => [
                                        'id_tag',
                                        'id_policy_module',
                                    ],
                                ],
                                [
                                    'table' => 'tpolicy_modules_synth',
                                    'ref'   => ['id_agent_module_target'],
                                    'value' => ['id'],
                                ],
                            ],
                        ],
                        [
                            'table' => 'tpolicy_modules_inventory',
                            'ref'   => ['id_policy'],
                            'value' => ['id'],
                        ],
                        [
                            'table' => 'tpolicy_plugins',
                            'ref'   => ['id_policy'],
                            'value' => ['id'],
                        ],
                    ],
                ],
            ],
            'service'        => [
                'label' => __('Service'),
                'items' => [
                    'table' => 'tservice',
                    'value' => ['id'],
                    'show'  => ['name'],
                    'data'  => [
                        [
                            'table' => 'tservice_element',
                            'ref'   => ['id_service'],
                            'value' => ['id'],
                        ],
                    ],
                ],
            ],
            'network_map'    => [
                'label' => __('Network map'),
                'items' => [
                    'table' => 'tmap',
                    'value' => ['id'],
                    'show'  => ['name'],
                    'data'  => [
                        [
                            'table' => 'titem',
                            'ref'   => ['id_map'],
                            'value' => ['id'],
                        ],
                        [
                            'table' => 'trel_item',
                            'ref'   => ['id_map'],
                            'value' => ['id'],
                        ],
                    ],
                ],
            ],
            'gis_map'        => [
                'label' => __('GIS map'),
                'items' => [
                    'table' => 'tgis_map',
                    'value' => ['id_tgis_map'],
                    'show'  => ['map_name'],
                    'data'  => [
                        [
                            'table' => 'tgis_map_layer',
                            'ref'   => ['tgis_map_id_tgis_map'],
                            'value' => ['id_tmap_layer'],
                            'data'  => [
                                [
                                    'table' => 'tgis_map_layer_groups',
                                    'ref'   => ['layer_id'],
                                    'value' => [
                                        'layer_id',
                                        'group_id',
                                    ],
                                ],
                                [
                                    'table' => 'tgis_map_layer_has_tagente',
                                    'ref'   => ['tgis_map_layer_id_tmap_layer'],
                                    'value' => [
                                        'tgis_map_layer_id_tmap_layer',
                                        'tagente_id_agente',
                                    ],
                                ],
                            ],
                        ],
                        [
                            'table' => 'tgis_map_has_tgis_map_con',
                            'ref'   => ['tgis_map_id_tgis_map'],
                            'value' => [
                                'tgis_map_id_tgis_map',
                                'tgis_map_con_id_tmap_con',
                            ],
                        ],
                    ],
                ],
            ],
            'custom_graph'   => [
                'label' => __('Custom graph'),
                'items' => [
                    'table' => 'tgraph',
                    'value' => ['id_graph'],
                    'show'  => ['name'],
                    'data'  => [
                        [
                            'table' => 'tgraph_source',
                            'ref'   => ['id_graph'],
                            'value' => ['id_gs'],
                        ],
                    ],
                ],
            ],
            'dashboard'      => [
                'label' => __('Dashboard'),
                'items' => [
                    'table' => 'tdashboard',
                    'value' => ['id'],
                    'show'  => ['name'],
                    'data'  => [
                        [
                            'table' => 'twidget_dashboard',
                            'ref'   => ['id_dashboard'],
                            'value' => ['id'],
                        ],
                    ],
                ],
            ],
        ];

        // Define variables for tables references
        // Variables order is very important due to hierarchy.
        $this->tgrupo = [
            'table'           => 'tgrupo',
            'id'              => 'id_grupo',
            'columns'         => ['nombre'],
            'autocreate_item' => 'agent_groups',
        ];

        $this->ttipo_modulo = [
            'table'   => 'ttipo_modulo',
            'id'      => 'id_tipo',
            'columns' => ['nombre'],
        ];

        $this->tmodule_group = [
            'table'           => 'tmodule_group',
            'id'              => 'id_mg',
            'columns'         => ['name'],
            'autocreate_item' => 'module_groups',
        ];

        $this->tconfig_os = [
            'table'           => 'tconfig_os',
            'id'              => 'id_os',
            'columns'         => ['name'],
            'autocreate_item' => 'operating_systems',
        ];

        $this->tcategory = [
            'table'           => 'tcategory',
            'id'              => 'id',
            'columns'         => ['name'],
            'autocreate_item' => 'categories',
        ];

        $this->ttag = [
            'table'           => 'ttag',
            'id'              => 'id_tag',
            'columns'         => ['name'],
            'autocreate_item' => 'tags',
        ];

        $this->tagente = [
            'table'   => 'tagente',
            'id'      => 'id_agente',
            'columns' => ['nombre'],
        ];

        $this->tagente_modulo = [
            'table'   => 'tagente_modulo',
            'id'      => 'id_agente_modulo',
            'columns' => ['nombre'],
            'join'    => ['id_agente' => $this->tagente],
        ];

        $this->tplugin = [
            'table'   => 'tplugin',
            'id'      => 'id',
            'columns' => ['name'],
        ];

        $this->tmodule_inventory = [
            'table'   => 'tmodule_inventory',
            'id'      => 'id_module_inventory',
            'columns' => ['name'],
            'join'    => ['id_os' => $this->tconfig_os],
        ];

        $this->tpolicies = [
            'table'   => 'tpolicies',
            'id'      => 'id',
            'columns' => ['name'],
        ];

        $this->tpolicy_modules = [
            'table'   => 'tpolicy_modules',
            'id'      => 'id',
            'columns' => ['name'],
            'join'    => ['id_policy' => $this->tpolicies],
        ];

        $this->talert_actions = [
            'table'   => 'talert_actions',
            'id'      => 'id',
            'columns' => ['name'],
        ];

        $this->talert_templates = [
            'table'   => 'talert_templates',
            'id'      => 'id',
            'columns' => ['name'],
        ];

        $this->tcollection = [
            'table'   => 'tcollection',
            'id'      => 'id',
            'columns' => ['short_name'],
        ];

        $this->tgraph = [
            'table'   => 'tgraph',
            'id'      => 'id_graph',
            'columns' => ['name'],
        ];

        $this->tservice = [
            'table'   => 'tservice',
            'id'      => 'id',
            'columns' => ['name'],
        ];

        $this->tlayout = [
            'table'   => 'tlayout',
            'id'      => 'id',
            'columns' => ['name'],
        ];

        $this->tlayout_data = [
            'table'   => 'tlayout_data',
            'id'      => 'id',
            'columns' => [
                'pos_x',
                'pos_y',
                'height',
                'width',
                'type',
            ],
            'join'    => ['id_layout' => $this->tlayout],
        ];

        $this->treport_custom_sql = [
            'table'   => 'treport_custom_sql',
            'id'      => 'id',
            'columns' => ['name'],
        ];

        $this->tserver_export = [
            'table'   => 'tserver_export',
            'id'      => 'id',
            'columns' => ['name'],
        ];

        $this->trecon_task = [
            'table'   => 'trecon_task',
            'id'      => 'id_rt',
            'columns' => [
                'name',
                'type',
            ],
        ];

        $this->tmap = [
            'table'   => 'tmap',
            'id'      => 'id',
            'columns' => ['name'],
        ];

        $this->titem = [
            'table'   => 'titem',
            'id'      => 'id',
            'columns' => [
                'id_map',
                'type',
                'source_data',
                'x',
                'y',
                'z',
            ],
        ];

        $this->tgis_map_connection = [
            'table'   => 'tgis_map_connection',
            'id'      => 'id_tmap_connection',
            'columns' => ['conection_name'],
        ];

        $this->tserver = [
            'table'   => 'tserver',
            'id'      => 'id_server',
            'columns' => [
                'name',
                'server_type',
            ],
        ];

        $this->twidget = [
            'table'   => 'twidget',
            'id'      => 'id',
            'columns' => ['unique_name'],
        ];

        $this->treport = [
            'table'   => 'treport',
            'id'      => 'id_report',
            'columns' => ['name'],
        ];

        // Define references between tables fields.
        $this->columnRefs = [
            'tlayout'                      => [
                'id_group' => ['ref' => $this->tgrupo],
            ],
            'tlayout_data'                 => [
                'id_agente_modulo' => ['ref' => $this->tagente_modulo],
                'id_agent'         => ['ref' => $this->tagente],
                'id_layout_linked' => ['ref' => $this->tlayout],
                'parent_item'      => ['ref' => $this->tlayout_data],
                'id_group'         => ['ref' => $this->tgrupo],
                'id_custom_graph'  => ['ref' => $this->tgraph],
                'element_group'    => ['ref' => $this->tgrupo],
            ],
            'treport'                      => [
                'id_group'      => ['ref' => $this->tgrupo],
                'id_group_edit' => ['ref' => $this->tgrupo],
            ],
            'treport_content'              => [
                'id_gs'                 => ['ref' => $this->tgraph],
                'id_agent_module'       => ['ref' => $this->tagente_modulo],
                'id_agent'              => ['ref' => $this->tagente],
                'treport_custom_sql_id' => ['ref' => $this->treport_custom_sql],
                'id_group'              => ['ref' => $this->tgrupo],
                'id_module_group'       => ['ref' => $this->tmodule_group],
                'ncm_agents'            => ['ref' => ($this->tagente + ['array' => true])],
            ],
            'treport_content_item'         => [
                'id_agent_module' => ['ref' => $this->tagente_modulo],
            ],
            'treport_content_sla_combined' => [
                'id_agent_module' => ['ref' => $this->tagente_modulo],
            ],
            'tpolicies'                    => [
                'id_group' => ['ref' => $this->tgrupo],
            ],
            'tpolicy_agents'               => [
                'id_agent' => ['ref' => $this->tagente],
            ],
            'tpolicy_alerts'               => [
                'id_policy_module'  => ['ref' => $this->tpolicy_modules],
                'id_alert_template' => ['ref' => $this->talert_templates],
            ],
            'tpolicy_alerts_actions'       => [
                'id_alert_action' => ['ref' => $this->talert_actions],
            ],
            'tpolicy_collections'          => [
                'id_collection' => ['ref' => $this->tcollection],
            ],
            'tpolicy_group_agents'         => [
                'id_agent' => ['ref' => $this->tagente],
            ],
            'tpolicy_groups'               => [
                'id_group' => ['ref' => $this->tgrupo],
            ],
            'tpolicy_modules'              => [
                'id_tipo_modulo'  => ['ref' => $this->ttipo_modulo],
                'id_module_group' => ['ref' => $this->tmodule_group],
                'id_export'       => ['ref' => $this->tserver_export],
                'id_plugin'       => ['ref' => $this->tplugin],
                'id_category'     => ['ref' => $this->tcategory],
            ],
            'ttag_policy_module'           => [
                'id_tag' => ['ref' => $this->ttag],
            ],
            'tpolicy_modules_synth'        => [
                'id_agent_module_source' => ['ref' => $this->tagente_modulo],
            ],
            'tpolicy_modules_inventory'    => [
                'id_module_inventory' => ['ref' => $this->tmodule_inventory],
            ],
            'tservice'                     => [
                'id_group'                       => ['ref' => $this->tgrupo],
                'id_agent_module'                => ['ref' => ($this->tagente_modulo + ['autocreate_item' => 'service_module'])],
                'sla_id_module'                  => ['ref' => ($this->tagente_modulo + ['autocreate_item' => 'service_sla_module'])],
                'sla_value_id_module'            => ['ref' => ($this->tagente_modulo + ['autocreate_item' => 'service_sla_value_module'])],
                'id_template_alert_warning'      => ['ref' => $this->talert_templates],
                'id_template_alert_critical'     => ['ref' => $this->talert_templates],
                'id_template_alert_unknown'      => ['ref' => $this->talert_templates],
                'id_template_alert_critical_sla' => ['ref' => $this->talert_templates],
            ],
            'tservice_element'             => [
                'id_agente_modulo' => ['ref' => $this->tagente_modulo],
                'id_agent'         => ['ref' => $this->tagente],
                'id_service_child' => ['ref' => $this->tservice],
            ],
            'tmap'                         => [
                'id_group'     => ['ref' => $this->tgrupo],
                'source_data'  => [
                    'conditional_refs' => [
                        [
                            'when' => ['source' => '0'],
                            'ref'  => ($this->tgrupo + ['csv' => true, 'csv_separator' => ',']),
                        ],
                        [
                            'when' => ['source' => '1'],
                            'ref'  => $this->trecon_task,
                        ],
                    ],
                ],
                'id_group_map' => ['ref' => $this->tgrupo],
            ],
            'titem'                        => [
                'source_data' => [
                    'conditional_refs' => [
                        [
                            'when' => ['type' => '0'],
                            'ref'  => $this->tagente,
                        ],
                        [
                            'when' => ['type' => '1'],
                            'ref'  => $this->tagente_modulo,
                        ],
                    ],
                ],
            ],
            'trel_item'                    => [
                'id_parent'             => ['ref' => $this->titem],
                'id_child'              => ['ref' => $this->titem],
                'id_parent_source_data' => [
                    'conditional_refs' => [
                        [
                            'when' => ['parent_type' => '0'],
                            'ref'  => $this->tagente,
                        ],
                        [
                            'when' => ['parent_type' => '1'],
                            'ref'  => $this->tagente_modulo,
                        ],
                    ],
                ],
                'id_child_source_data'  => [
                    'conditional_refs' => [
                        [
                            'when' => ['child_type' => '0'],
                            'ref'  => $this->tagente,
                        ],
                        [
                            'when' => ['child_type' => '1'],
                            'ref'  => $this->tagente_modulo,
                        ],
                    ],
                ],
            ],
            'tgis_map'                     => [
                'group_id' => ['ref' => $this->tgrupo],
            ],
            'tgis_map_layer'               => [
                'tgrupo_id_grupo' => ['ref' => $this->tgrupo],
            ],
            'tgis_map_layer_groups'        => [
                'group_id' => ['ref' => $this->tgrupo],
                'agent_id' => ['ref' => $this->tagente],
            ],
            'tgis_map_layer_has_tagente'   => [
                'tagente_id_agente' => ['ref' => $this->tagente],
            ],
            'tgis_map_has_tgis_map_con'    => [
                'tgis_map_con_id_tmap_con' => ['ref' => $this->tgis_map_connection],
            ],
            'tgraph'                       => [
                'id_group' => ['ref' => $this->tgrupo],
            ],
            'tgraph_source'                => [
                'id_server'       => ['ref' => $this->tserver],
                'id_agent_module' => ['ref' => $this->tagente_modulo],
            ],
            'tdashboard'                   => [
                'id_group' => ['ref' => $this->tgrupo],
            ],
            'twidget_dashboard'            => [
                'id_widget' => ['ref' => $this->twidget],
            ],
        ];

        // Define references between tables fields with JSON format.
        $this->jsonRefs = [
            'tservice_element'  => [
                'rules' => [
                    'group' => ['ref' => $this->tgrupo],
                ],
            ],
            'titem'             => [
                'style' => [
                    'id_group'   => ['ref' => $this->tgrupo],
                    'networkmap' => ['ref' => $this->tmap],
                    'id_agent'   => ['ref' => $this->tagente],
                ],
            ],
            'twidget_dashboard' => [
                'options' => [
                    'id_group'                    => [
                        'conditional_refs' => [
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'single_graph'],
                                    ],
                                ],
                                'ref'  => $this->tgrupo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'wux_transaction'],
                                    ],
                                ],
                                'ref'  => $this->tgrupo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'inventory'],
                                    ],
                                ],
                                'ref'  => $this->tgrupo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'service_view'],
                                    ],
                                ],
                                'ref'  => $this->tgrupo,
                            ],
                        ],
                    ],
                    'agentId'                     => [
                        'conditional_refs' => [
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'single_graph'],
                                    ],
                                ],
                                'ref'  => $this->tagente,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'wux_transaction'],
                                    ],
                                ],
                                'ref'  => $this->tagente,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'AvgSumMaxMinModule'],
                                    ],
                                ],
                                'ref'  => $this->tagente,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'BasicChart'],
                                    ],
                                ],
                                'ref'  => $this->tagente,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'module_icon'],
                                    ],
                                ],
                                'ref'  => $this->tagente,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'inventory'],
                                    ],
                                ],
                                'ref'  => $this->tagente,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'graph_module_histogram'],
                                    ],
                                ],
                                'ref'  => $this->tagente,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'module_table_value'],
                                    ],
                                ],
                                'ref'  => $this->tagente,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'module_status'],
                                    ],
                                ],
                                'ref'  => $this->tagente,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'module_value'],
                                    ],
                                ],
                                'ref'  => $this->tagente,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'sla_percent'],
                                    ],
                                ],
                                'ref'  => $this->tagente,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'wux_transaction_stats'],
                                    ],
                                ],
                                'ref'  => $this->tagente,
                            ],
                        ],
                    ],
                    'moduleId'                    => [
                        'conditional_refs' => [
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'single_graph'],
                                    ],
                                ],
                                'ref'  => $this->tagente_modulo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'AvgSumMaxMinModule'],
                                    ],
                                ],
                                'ref'  => $this->tagente_modulo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'BasicChart'],
                                    ],
                                ],
                                'ref'  => $this->tagente_modulo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'module_icon'],
                                    ],
                                ],
                                'ref'  => $this->tagente_modulo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'graph_module_histogram'],
                                    ],
                                ],
                                'ref'  => $this->tagente_modulo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'module_table_value'],
                                    ],
                                ],
                                'ref'  => $this->tagente_modulo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'module_status'],
                                    ],
                                ],
                                'ref'  => $this->tagente_modulo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'module_value'],
                                    ],
                                ],
                                'ref'  => $this->tagente_modulo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'sla_percent'],
                                    ],
                                ],
                                'ref'  => $this->tagente_modulo,
                            ],
                        ],
                    ],
                    'transactionId'               => [
                        'conditional_refs' => [
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'wux_transaction'],
                                    ],
                                ],
                                'ref'  => $this->tagente_modulo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'wux_transaction_stats'],
                                    ],
                                ],
                                'ref'  => $this->tagente_modulo,
                            ],
                        ],
                    ],
                    'mGroup'                      => [
                        'conditional_refs' => [
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'agent_module'],
                                    ],
                                ],
                                'ref'  => $this->tgrupo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'service_level'],
                                    ],
                                ],
                                'ref'  => $this->tgrupo,
                            ],
                        ],
                    ],
                    'mModuleGroup'                => [
                        'conditional_refs' => [
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'agent_module'],
                                    ],
                                ],
                                'ref'  => $this->tmodule_group,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'service_level'],
                                    ],
                                ],
                                'ref'  => $this->tmodule_group,
                            ],
                        ],
                    ],
                    'mAgents'                     => [
                        'conditional_refs' => [
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'agent_module'],
                                    ],
                                ],
                                'ref'  => ($this->tagente + ['csv' => true, 'csv_separator' => ',']),
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'service_level'],
                                    ],
                                ],
                                'ref'  => ($this->tagente + ['csv' => true, 'csv_separator' => ',']),
                            ],
                        ],
                    ],
                    'groups'                      => [
                        'conditional_refs' => [
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'AgentHive'],
                                    ],
                                ],
                                'ref'  => ($this->tgrupo + ['array' => true]),
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'heatmap'],
                                    ],
                                ],
                                'ref'  => ($this->tgrupo + ['array' => true]),
                            ],
                        ],
                    ],
                    'agentsBlockHistogram[0]'     => [
                        'conditional_refs' => [
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'BlockHistogram'],
                                    ],
                                ],
                                'ref'  => ($this->tagente + ['csv' => true, 'csv_separator' => ',']),
                            ],
                        ],
                    ],
                    'moduleBlockHistogram'        => [
                        'conditional_refs' => [
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'BlockHistogram'],
                                    ],
                                ],
                                'ref'  => ($this->tagente_modulo + ['array' => true, 'values_as_keys' => true]),
                            ],
                        ],
                    ],
                    'agentsColorModuleTabs[0]'    => [
                        'conditional_refs' => [
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'ColorModuleTabs'],
                                    ],
                                ],
                                'ref'  => ($this->tagente + ['csv' => true, 'csv_separator' => ',']),
                            ],
                        ],
                    ],
                    'moduleColorModuleTabs'       => [
                        'conditional_refs' => [
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'ColorModuleTabs'],
                                    ],
                                ],
                                'ref'  => ($this->tagente_modulo + ['array' => true, 'values_as_keys' => true]),
                            ],
                        ],
                    ],
                    'reportId'                    => [
                        'conditional_refs' => [
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'reports'],
                                    ],
                                ],
                                'ref'  => $this->treport,
                            ],
                        ],
                    ],
                    'agentsDataMatrix[0]'         => [
                        'conditional_refs' => [
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'DataMatrix'],
                                    ],
                                ],
                                'ref'  => ($this->tagente + ['csv' => true, 'csv_separator' => ',']),
                            ],
                        ],
                    ],
                    'moduleDataMatrix'            => [
                        'conditional_refs' => [
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'DataMatrix'],
                                    ],
                                ],
                                'ref'  => ($this->tagente_modulo + ['array' => true, 'values_as_keys' => true]),
                            ],
                        ],
                    ],
                    'id_graph'                    => [
                        'conditional_refs' => [
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'custom_graph'],
                                    ],
                                ],
                                'ref'  => $this->tgraph,
                            ],
                        ],
                    ],
                    'groupId'                     => [
                        'conditional_refs' => [
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'EventCardboard'],
                                    ],
                                ],
                                'ref'  => ($this->tgrupo + ['array' => true]),
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'groups_status'],
                                    ],
                                ],
                                'ref'  => $this->tgrupo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'groups_status_map'],
                                    ],
                                ],
                                'ref'  => ($this->tgrupo + ['csv' => true, 'csv_separator' => ',']),
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'system_group_status'],
                                    ],
                                ],
                                'ref'  => ($this->tgrupo + ['array' => true]),
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'events_list'],
                                    ],
                                ],
                                'ref'  => ($this->tgrupo + ['array' => true]),
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'tactical'],
                                    ],
                                ],
                                'ref'  => ($this->tgrupo + ['array' => true]),
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'top_n_events_by_group'],
                                    ],
                                ],
                                'ref'  => ($this->tgrupo + ['array' => true]),
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'top_n_events_by_module'],
                                    ],
                                ],
                                'ref'  => ($this->tgrupo + ['array' => true]),
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'tree_view'],
                                    ],
                                ],
                                'ref'  => $this->tgrupo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'alerts_fired'],
                                    ],
                                ],
                                'ref'  => $this->tgrupo,
                            ],
                        ],
                    ],
                    'maps'                        => [
                        'conditional_refs' => [
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'maps_status'],
                                    ],
                                ],
                                'ref'  => ($this->tlayout + ['array' => true]),
                            ],
                        ],
                    ],
                    'idGroup'                     => [
                        'conditional_refs' => [
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'inventory'],
                                    ],
                                ],
                                'ref'  => $this->tgrupo,
                            ],
                        ],
                    ],
                    'agentsGroupedMeterGraphs[0]' => [
                        'conditional_refs' => [
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'GroupedMeterGraphs'],
                                    ],
                                ],
                                'ref'  => ($this->tagente + ['csv' => true, 'csv_separator' => ',']),
                            ],
                        ],
                    ],
                    'moduleGroupedMeterGraphs'    => [
                        'conditional_refs' => [
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'GroupedMeterGraphs'],
                                    ],
                                ],
                                'ref'  => ($this->tagente_modulo + ['array' => true, 'values_as_keys' => true]),
                            ],
                        ],
                    ],
                    'tagsId'                      => [
                        'conditional_refs' => [
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'events_list'],
                                    ],
                                ],
                                'ref'  => ($this->ttag + ['array' => true]),
                            ],
                        ],
                    ],
                    'networkmapId'                => [
                        'conditional_refs' => [
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'network_map'],
                                    ],
                                ],
                                'ref'  => $this->tmap,
                            ],
                        ],
                    ],
                    'group'                       => [
                        'conditional_refs' => [
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'security_hardening'],
                                    ],
                                ],
                                'ref'  => $this->tgrupo,
                            ],
                        ],
                    ],
                    'serviceId'                   => [
                        'conditional_refs' => [
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'service_map'],
                                    ],
                                ],
                                'ref'  => $this->tservice,
                            ],
                        ],
                    ],
                    'vcId'                        => [
                        'conditional_refs' => [
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'maps_made_by_user'],
                                    ],
                                ],
                                'ref'  => $this->tlayout,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Define table fields encoded as base64 in database.
        $this->base64Refs = [
            'tservice_element' => ['rules'],
        ];

        $this->result = [
            'status' => true,
            'items'  => [],
            'errors' => []
        ];

        $this->currentItem = [
            'table'           => '',
            'value'           => '',
            'last_autocreate' => '',
            'autocreate'      => [],
            'parsed'          => [],
        ];

        $this->itemsReferences = [];
    }

    /**
     * Fills result with status.
     *
     * @param bool $status Result status.
     *
     * @return void
     */
    public function setResultStatus(bool $status)
    {
        $this->result['status'] = $status;
    }


    /**
     * Fills result with item.
     *
     * @param string $table Item table.
     * @param array $columns Table columns.
     * @param array $values Item values.
     *
     * @return void
     */
    public function addResultItem(string $table, array $values)
    {
        $this->result['items'][] = [$table, $values];
    }


    /**
     * Fills result with error message.
     *
     * @param string $msg Error message.
     *
     * @return void
     */
    public function addResultError(string $msg)
    {
        $this->result['errors'][] = $msg;
    }


    /**
     * Get current result status.
     *
     * @return bool
     */
    public function getResultStatus()
    {
        return $this->result['status'];
    }

    /**
     * Get $prdData.
     *
     * @return array
     */
    public function getPrdData(): array
    {
        return $this->prdData;
    }


    /**
     * Get one $prdData.
     *
     * @param string $item Item to be searched in array.
     *
     * @return boolean|array
     */
    public function getOnePrdData(string $item): bool|array
    {
        if (isset($this->prdData[$item]) === false) {
            return false;
        }

        return $this->prdData[$item];
    }


    /**
     * Recursive function to extract tables.
     *
     * @param array $prd_data PrdData.
     * @param array $result   Empty array.
     * @param array $crossed_refs   Empty array.
     * @param string $parent_table Parent level table name.
     *
     * @return void
     */
    private function getTablesPrdData($prd_data, &$result=[], &$crossed_refs=[], $parent_table='')
    {
        if (isset($prd_data['items']) === true) {
            $result[] = $prd_data['items']['table'];
            $crossed_refs[$prd_data['items']['table']] = [
                'value'        => $prd_data['items']['value'],
                'ref'          => [],
                'parent_table' => $parent_table
            ];
            if ($prd_data['items']['data']) {
                $this->getTablesPrdData($prd_data['items']['data'], $result, $crossed_refs, $prd_data['items']['table']);
            }
        } else {
            foreach ($prd_data as $key => $value) {
                $result[] = $value['table'];
                $crossed_refs[$value['table']] = [
                    'value'        => $value['value'],
                    'ref'          => isset($value['ref']) ? $value['ref'] : [],
                    'parent_table' => $parent_table
                ];
                if (isset($value['data'])) {
                    $this->getTablesPrdData($value['data'], $result, $crossed_refs, $value['table']);
                }
            }
        }
    }


    /**
     * Get $columnRefs.
     *
     * @return array
     */
    public function getColumnRefs(): array
    {
        return $this->columnRefs;
    }


    /**
     * Get one $columnRefs.
     *
     * @param string $item Item to be searched in array.
     *
     * @return boolean|array
     */
    public function getOneColumnRefs(string $item): bool|array
    {
        if (isset($this->columnRefs[$item]) === false) {
            return false;
        }

        return $this->columnRefs[$item];
    }


    /**
     * Get $jsonRefs.
     *
     * @return array
     */
    public function getJsonRefs(): array
    {
        return $this->jsonRefs;
    }


    /**
     * Get one $jsonRefs.
     *
     * @param string $item Item to be searched in array.
     *
     * @return boolean|array
     */
    public function getOneJsonRefs(string $item): bool|array
    {
        if (isset($this->jsonRefs[$item]) === false) {
            return false;
        }

        return $this->jsonRefs[$item];
    }


    /**
     * Get types of prd.
     *
     * @return array
     */
    public function getTypesPrd(): array
    {
        $result = [];
        foreach ($this->prdData as $key => $value) {
            $result[$key] = $value['label'];
        }

        return $result;
    }


    /**
     * Search value in database.
     *
     * @param array          $columns Column.
     * @param string         $table   Table.
     * @param string         $id      Id.
     * @param integer|string $value   Value.
     *
     * @return string
     */
    private function searchValue(array $columns, string $table, string $id, $value):string
    {
        $sql_column = sprintf(
            'SELECT %s FROM %s WHERE %s IN (%s)',
            implode(
                ',',
                $columns
            ),
            $table,
            $id,
            $value
        );

        $result = db_get_row_sql($sql_column);
        if ($result !== false) {
            $value = $result;
            $new_array = [];
            $new_array[$table] = $value;
            $value = json_encode($new_array);
        }

        return $value;
    }


    /**
     * Function that checks if a value is a json.
     *
     * @param string $json Value to be checked.
     *
     * @return boolean
     */
    private function validateJSON(string $json): bool
    {
        try {
            $check = json_decode($json, null, JSON_THROW_ON_ERROR);
            if (is_object($check) === true) {
                return true;
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }


    /**
     * Converts a resource into a string.
     *
     * @param string $type Item type.
     * @param mixed  $id   Item value.
     * @param string $name Item name.
     *
     * @return string
     */
    public function exportPrd(string $type, mixed $id, string $name) :string
    {
        $result = '';

        $prd_data = $this->getOnePrdData($type);
        if (empty($prd_data) === false) {
            $result .= '[prd_data]'.LINE_BREAK.LINE_BREAK;
            $result .= 'type="'.$type.'"'.LINE_BREAK;
            $result .= 'name="'.io_safe_output($name).'"'.LINE_BREAK.LINE_BREAK;

            $result .= '['.$prd_data['items']['table'].']'.LINE_BREAK.LINE_BREAK;

            $columns_ref = $this->getOneColumnRefs($prd_data['items']['table']);

            $sql = sprintf(
                'SELECT * FROM %s WHERE %s = %s',
                $prd_data['items']['table'],
                reset($prd_data['items']['value']),
                $id,
            );

            $row = db_get_row_sql($sql);
            $primary_key = $row[reset($prd_data['items']['value'])];
            foreach ($row as $column => $value) {
                if (isset($this->base64Refs[$prd_data['items']['table']]) === true
                    && empty($value) === false
                    && reset($this->base64Refs[$prd_data['items']['table']]) === $column
                ) {
                    // Base64 ref.
                    $value = base64_decode($value);
                }

                if (isset($columns_ref[$column]) === true
                    && empty($value) === false
                ) {
                    // The column is inside column refs.
                    if (isset($columns_ref[$column]['ref']) === true) {
                        // Column refs.
                        if (isset($columns_ref[$column]['ref']['join']) === true) {
                            // Has join.
                            $join_array = $this->recursiveJoin(
                                $columns_ref[$column]['ref'],
                                $value
                            );
                            $value = [$columns_ref[$column]['ref']['table'] => $join_array];
                            $value = json_encode($value);
                        } else {
                            $value = $this->searchValue(
                                $columns_ref[$column]['ref']['columns'],
                                $columns_ref[$column]['ref']['table'],
                                $columns_ref[$column]['ref']['id'],
                                $value
                            );
                        }
                    } else if (isset($columns_ref[$column]['conditional_refs']) === true) {
                        // Conditional refs.
                        foreach ($columns_ref[$column]['conditional_refs'] as $key => $condition) {
                            if (isset($condition['when']) === true) {
                                $control = false;
                                if ($row[array_key_first($condition['when'])] == reset($condition['when'])
                                    && empty($value) === false
                                ) {
                                    $control = true;
                                }

                                if ($control === true) {
                                    $value = $this->searchValue(
                                        $condition['ref']['columns'],
                                        $condition['ref']['table'],
                                        $condition['ref']['id'],
                                        $value
                                    );
                                    break;
                                }
                            }
                        }
                    }
                }
                // Scape double quotes in all values
                $value = str_replace('"', '\"', $value);
                $result .= $column.'['.$primary_key.']="'.$value.'"'.LINE_BREAK;
            }

            $result .= LINE_BREAK;

            $result .= $this->recursiveExportPrd($prd_data['items']['data'], $id);
        }

        return $result;
    }


    /**
     * Recursive function to traverse all data
     *
     * @param mixed $data Data.
     * @param mixed $id   Id value for search.
     *
     * @return string
     */
    private function recursiveExportPrd($data, $id): string
    {
        $result = '';

        foreach ($data as $key => $element) {
            $result .= '['.$element['table'].']'.LINE_BREAK.LINE_BREAK;

            $columns_ref = $this->getOneColumnRefs($element['table']);
            $json_ref = $this->getOneJsonRefs($element['table']);

            $sql = sprintf(
                'SELECT * FROM %s WHERE %s = %s',
                $element['table'],
                reset($element['ref']),
                $id,
            );

            if (empty($id) === false && empty($element['table']) === false
                && empty(reset($element['ref'])) === false
            ) {
                $rows = db_get_all_rows_sql($sql);
            } else {
                $rows = [];
            }

            foreach ($rows as $row) {
                if (count($element['value']) > 1) {
                    $primary_key = '';
                    foreach ($element['value'] as $value) {
                        $primary_key .= $row[$value].'-';
                    }

                    $primary_key = substr($primary_key, 0, -1);
                } else {
                    $primary_key = $row[reset($element['value'])];
                }

                foreach ($row as $column => $value) {
                    if (isset($this->base64Refs[$element['table']]) === true
                        && empty($value) === false
                        && reset($this->base64Refs[$element['table']]) === $column
                    ) {
                        // Base64 ref.
                        $value = base64_decode($value);
                    }

                    if (isset($columns_ref[$column]) === true
                        && empty($value) === false
                    ) {
                        // The column is inside column refs.
                        if (isset($columns_ref[$column]['ref']) === true) {
                            // Column ref.
                            if (isset($columns_ref[$column]['ref']['join']) === true) {
                                $join_array = $this->recursiveJoin(
                                    $columns_ref[$column]['ref'],
                                    $value
                                );
                                $value = [$columns_ref[$column]['ref']['table'] => $join_array];
                                $value = json_encode($value);
                            } else {
                                $value = $this->searchValue(
                                    $columns_ref[$column]['ref']['columns'],
                                    $columns_ref[$column]['ref']['table'],
                                    $columns_ref[$column]['ref']['id'],
                                    $value
                                );
                            }
                        } else if (isset($columns_ref[$column]['conditional_refs']) === true) {
                            // Conditional refs.
                            foreach ($columns_ref[$column]['conditional_refs'] as $key => $condition) {
                                if (isset($condition['when']) === true) {
                                    $control = false;
                                    if ($row[array_key_first($condition['when'])] == reset($condition['when'])
                                        && empty($value) === false
                                    ) {
                                        $control = true;
                                    }

                                    if ($control === true) {
                                        $value = $this->searchValue(
                                            $condition['ref']['columns'],
                                            $condition['ref']['table'],
                                            $condition['ref']['id'],
                                            $value
                                        );
                                        break;
                                    }
                                }
                            }
                        }
                    } else if (isset($json_ref[$column]) === true) {
                        // Json ref.
                        $json_array = json_decode($value, true);
                        foreach ($json_ref[$column] as $json_key => $json_values) {
                            if (empty($json_array[$json_key]) === false) {
                                if (isset($json_values['conditional_refs']) === true) {
                                    foreach ($json_values['conditional_refs'] as $key => $condition) {
                                        $when = reset($condition['when']);

                                        $sql_check = sprintf(
                                            'SELECT * FROM %s WHERE %s=%s AND %s like "%s"',
                                            $when['table'],
                                            $when['id'],
                                            $row[array_key_first($condition['when'])],
                                            array_key_first($when['when']),
                                            reset($when['when'])
                                        );

                                        $check = db_get_row_sql($sql_check);
                                        if ($check !== false && isset($condition['ref']) === true) {
                                            if (isset($condition['ref']['join']) === true) {
                                                $join_array = $this->recursiveJoin(
                                                    $condition['ref'],
                                                    $json_array[$json_key]
                                                );
                                                $json_array[$json_key] = [$condition['ref']['table'] => $join_array];
                                            } else {
                                                if (is_array($json_array[$json_key]) === true) {
                                                    $implode_where = implode(',', $json_array[$json_key]);
                                                    if (empty($implode_where) === false) {
                                                        $sql_json = sprintf(
                                                            'SELECT %s FROM %s WHERE %s IN (%s)',
                                                            implode(
                                                                ',',
                                                                $condition['ref']['columns']
                                                            ),
                                                            $condition['ref']['table'],
                                                            $condition['ref']['id'],
                                                            $implode_where
                                                        );

                                                        $value = db_get_row_sql($sql_json);
                                                    } else {
                                                        $value = false;
                                                    }
                                                } else {
                                                    $sql_json = sprintf(
                                                        'SELECT %s FROM %s WHERE %s=%s',
                                                        implode(
                                                            ',',
                                                            $condition['ref']['columns']
                                                        ),
                                                        $condition['ref']['table'],
                                                        $condition['ref']['id'],
                                                        $json_array[$json_key]
                                                    );

                                                    $value = db_get_row_sql($sql_json);
                                                }

                                                if ($value !== false) {
                                                    $new_array = [];
                                                    $new_array[$condition['ref']['table']] = $value;
                                                    $json_array[$json_key] = $new_array;
                                                }
                                            }

                                            break;
                                        }
                                    }
                                } else {
                                    $sql_json = sprintf(
                                        'SELECT %s FROM %s WHERE %s=%s',
                                        implode(
                                            ',',
                                            $json_values['ref']['columns']
                                        ),
                                        $json_values['ref']['table'],
                                        $json_values['ref']['id'],
                                        $json_array[$json_key]
                                    );

                                    $value = db_get_row_sql($sql_json);
                                    $new_array = [];
                                    $new_array[$json_values['ref']['table']] = $value;
                                    $json_array[$json_key] = $new_array;
                                }
                            }
                        }
                    }
                    // Scape double quotes in all values
                    $value = str_replace('"', '\"', $value);
                    $result .= $column.'['.$primary_key.']="'.$value.'"'.LINE_BREAK;
                }

                $result .= LINE_BREAK;
            }

            if (isset($element['data']) === true) {
                $result .= $this->recursiveExportPrd($element['data'], $primary_key);
            }
        }

        return $result;
    }


    /**
     * Recursive function to traverse all join data
     *
     * @param array $data  Data.
     * @param mixed $value Value for search.
     *
     * @return array
     */
    private function recursiveJoin(array $data, $value):array
    {
        $result = [];
        if (empty($data['join']) === false) {
            $sql = sprintf(
                'SELECT %s, %s FROM %s WHERE %s=%s',
                implode(
                    ',',
                    $data['columns']
                ),
                array_key_first($data['join']),
                $data['table'],
                $data['id'],
                $value
            );

            $result = db_get_row_sql($sql);
            $join = reset($data['join']);
            $result_deep = $this->recursiveJoin($join, $result[array_key_first($data['join'])]);

            $result[array_key_first($data['join'])] = $result_deep;
        } else {
            $sql = sprintf(
                'SELECT %s FROM %s WHERE %s=%s',
                implode(
                    ',',
                    $data['columns']
                ),
                $data['table'],
                $data['id'],
                $value
            );

            $result_sql = db_get_row_sql($sql);
            $result[$data['table']] = $result_sql;
        }

        return $result;
    }


    /**
     * Function to fill current item.
     *
     * @param string|integer $id    Id.
     * @param string         $table Table.
     * @param array          $data  Array with data.
     *
     * @return void
     */
    private function fillCurrentItem($id, string $table, array $data)
    {
        $this->currentItem['table'] = $table;
        $this->currentItem['id'] = $id;
        $this->currentItem['value'] = '';
        $this->currentItem['last_autocreate'] = '';
        $this->currentItem['autocreate'] = [];
        $this->currentItem['parsed'] = [];
        foreach ($data as $column => $value) {
            $this->currentItem['parsed'][$column] = $value[$id];
        }
    }


    /**
     * Converts a resource into a string.
     *
     * @param array $data_file Array with import data.
     *
     * @return array
     */
    public function importPrd($data_file): array
    {
        global $config;

        if (empty($data_file['prd_data']) === false) {
            $type = $data_file['prd_data']['type'];
            $name = io_safe_input($data_file['prd_data']['name']);
            unset($data_file['prd_data']);

            $prd_data = $this->getOnePrdData($type);
            if ($prd_data !== false) {
                // Begin transaction.
                $db = $config['dbconnection'];
                $db->begin_transaction();

                try {
                    $tables = [];
                    $crossed_refs = [];
                    $tables_id = [];
                    $this->getTablesPrdData($prd_data, $tables, $crossed_refs);
                    foreach ($tables as $table) {
                        if (isset($data_file[$table]) === false) {
                            continue;
                        }
                        $internal_array = $data_file[$table];

                        $column_refs = $this->getOneColumnRefs($table);
                        $json_refs = $this->getOneJsonRefs($table);

                        $ids = array_shift($internal_array);
                        foreach ($ids as $id) {
                            $skip_item = false;
                            $this->fillCurrentItem($id, $table, $internal_array);
                            foreach ($this->currentItem['parsed'] as $column => $value) {
                                if (isset($column_refs[$column]) === true
                                    && empty($value) === false
                                ) {

                                    if (isset($column_refs[$column]['conditional_refs']) === true) {
                                        // Conditional refs.
                                        $prd_item = false;
                                        $conditional = $column_refs[$column]['conditional_refs'];
                                        foreach ($conditional as $key => $condition) {
                                            if (isset($condition['when']) === true) {
                                                $control = false;
                                                if ($this->currentItem['parsed'][array_key_first($condition['when'])] == reset($condition['when'])
                                                    && empty($value) === false
                                                ) {
                                                    $control = true;
                                                }

                                                if ($control === true) {
                                                    $prd_item = $this->findPrdItem(
                                                        $condition['ref'],
                                                        $value
                                                    );
                                                    break;
                                                }
                                            }
                                        }

                                        if (isset($condition['ref']['autocreate_item']) === true
                                            && $prd_item === false
                                        ) {
                                            $this->autocreateItem(
                                                $condition['ref'],
                                                $column,
                                                $condition['ref']['autocreate_item']
                                            );
                                        } else if (empty($prd_item) === false) {
                                            if (isset($this->base64Refs[$table]) === true
                                                && reset($this->base64Refs[$table]) === $column
                                            ) {
                                                // Base64 ref.
                                                $prd_item = base64_encode($prd_item);
                                            }

                                            $this->currentItem['parsed'][$column] = $prd_item;
                                        } else {
                                            $skip_item = true;
                                            break;
                                        }

                                        continue;
                                    }

                                    if (isset($column_refs[$column]['ref']) === true) {
                                        $ref = $column_refs[$column]['ref'];
                                        $prd_item = $this->findPrdItem($ref, $value);
                                        if (isset($ref['autocreate_item']) === true && $prd_item === false) {
                                            $this->autocreateItem($ref, $column, $ref['autocreate_item']);
                                        } else if (empty($prd_item) === false) {
                                            if (isset($this->base64Refs[$table]) === true
                                                && reset($this->base64Refs[$table]) === $column
                                            ) {
                                                // Base64 ref.
                                                $prd_item = base64_encode($prd_item);
                                            }

                                            $this->currentItem['parsed'][$column] = $prd_item;
                                        } else {
                                            $skip_item = true;
                                            break;
                                        }

                                        continue;
                                    }

                                    if (isset($column_refs[$column]['fixed_value']) === true) {
                                        $this->currentItem['parsed'][$column] = $column_refs[$column]['fixed_value'];
                                        continue;
                                    }

                                } else if (isset($json_refs[$column]) === true
                                    && empty($value) === false
                                ) {
                                    // Json ref.
                                    $array_value = json_decode($value, true);
                                    foreach ($array_value as $key => $val) {
                                        if (isset($json_refs[$column][$key]) === true) {
                                            $ref = $json_refs[$column][$key]['ref'];
                                            $prd_item = $this->findPrdItem(
                                                $ref,
                                                json_encode($val)
                                            );

                                            if (isset($ref['autocreate_item']) === true
                                                && $prd_item === false
                                            ) {
                                                $this->autocreateItem($ref, $column, $ref['autocreate_item']);
                                            }

                                            if (empty($prd_item) === false) {
                                                $array_value[$key] = $prd_item;
                                            }
                                        }
                                    }

                                    $value = json_encode($array_value);
                                    if (isset($this->base64Refs[$table]) === true
                                        && empty($value) === false
                                        && reset($this->base64Refs[$table]) === $column
                                    ) {
                                        // Base64 ref.
                                        $value = base64_encode($value);
                                    }
                                } else {
                                    $this->currentItem['parsed'][$column] = $value;
                                }
                            }

                            if($skip_item) {
                                continue;
                            }

                            if($this->createItem($table, $crossed_refs) === false) {
                                $this->setResultStatus(false);
                                break;
                            }
                        }

                        if($this->getResultStatus() === false){
                            break;
                        }
                    }
                } catch (\Throwable $th) {
                    $this->setResultStatus(false);
                    $this->addResultError('Unexpected error: '.$th->getMessage());
                }

            } else {
                $this->setResultStatus(false);
                $this->addResultError('[prd_data] => "type" not valid to import: '.$type);
            }
        } else {
            $this->setResultStatus(false);
            $this->addResultError('[prd_data] not found in PRD file.');
        }

        if(isset($db)){
            if($this->getResultStatus() === true){
                $db->commit();
            } else {
                $db->rollback();
            }
        }

        return $this->result;
    }


    /**
     * Finds value in database.
     *
     * @param array $ref   Reference.
     * @param mixed $value Value.
     *
     * @return mixed
     */
    private function findPrdItem($ref, $value)
    {
        $result = false;
        $array_value = json_decode($value, true);
        if (isset($ref['join']) === true) {
            $result = $this->inverseRecursiveJoin(
                $ref,
                $array_value
            );
        } else {
            if (empty($array_value) === false
                && empty($array_value[$ref['table']]) === false
            ) {
                $where = '';
                foreach ($ref['columns'] as $column_name) {
                    if (isset($array_value[$ref['table']][$column_name])) {
                        $where .= sprintf(
                            "%s = '%s' AND ",
                            $column_name,
                            $array_value[$ref['table']][$column_name]
                        );
                    }
                }

                $where = rtrim($where, 'AND ');
                $sql_column = sprintf(
                    'SELECT %s FROM %s WHERE %s',
                    $ref['id'],
                    $ref['table'],
                    $where,
                );

                $result = db_get_value_sql($sql_column);
            } else {
                // Empty json.
                $result = '';
            }
        }

        return $result;
    }


    /**
     * Recursive function to traverse all join data
     *
     * @param array $ref   Data.
     * @param mixed $value Value for search.
     *
     * @return mixed
     */
    private function inverseRecursiveJoin($ref, $value)
    {
        $result = '';
        if (empty($ref['join']) === false) {
            $result = $this->inverseRecursiveJoin(
                $ref['join'],
                $value[$ref['table']][array_key_first($ref['join'])]
            );

            if (empty($result) === false) {
                $where = '';
                foreach ($ref['columns'] as $column_name) {
                    if (isset($value[$ref['table']][$column_name]) === true) {
                        $where .= sprintf(
                            "%s = '%s' AND ",
                            $column_name,
                            $value[$ref['table']][$column_name]
                        );
                    }
                }

                $where .= ' '.array_key_first($result).' = "'.reset($result).'"';
                $where = rtrim($where, 'AND ');

                $sql = sprintf(
                    'SELECT %s FROM %s WHERE %s',
                    $ref['id'],
                    $ref['table'],
                    $where
                );

                $result = db_get_value_sql($sql);
            }
        } else {
            $key = array_key_first($ref);
            $where = '';
            foreach ($ref[$key]['columns'] as $column_name) {
                if (isset($value[$ref[$key]['table']][$column_name]) === true) {
                    $where .= sprintf(
                        "%s = '%s' AND ",
                        $column_name,
                        $value[$ref[$key]['table']][$column_name]
                    );
                }
            }

            if (empty($where) === false) {
                $where = rtrim($where, 'AND ');

                $sql = sprintf(
                    'SELECT %s FROM %s WHERE %s',
                    $key,
                    $ref[$key]['table'],
                    $where
                );

                $result = db_get_row_sql($sql);
            }
        }

        return $result;
    }


    /**
     * Autocreate Item.
     *
     * @param array  $ref            References.
     * @param string $field          Field.
     * @param string $autocreate_key Key.
     *
     * @return void
     */
    private function autocreateItem(array $ref, string $field='', string $autocreate_key='')
    {
        $current_item = $this->currentItem['parsed'][$field];
        $current_item = json_decode($current_item, true);

        switch ($autocreate_key) {
            case 'service_module':
                $autocreate_globals = [
                    'service_module' => [
                        'id_agent' => $this->findPrdItem(
                            $this->tagente,
                            $current_item['tagente_modulo']['id_agente']
                        ),
                        'interval' => 300,
                        'status'   => AGENT_MODULE_STATUS_NORMAL,
                    ],
                ];

                $autocreate_pre_items = [
                    'service_module' => [
                        [
                            'table'  => 'tagente_modulo',
                            'id'     => ['id_agente_modulo'],
                            'fields' => [
                                'id_agente'         => $autocreate_globals[$autocreate_key]['id_agent'],
                                'nombre'            => $this->currentItem['parsed']['name'].'_service',
                                'flag'              => 0,
                                'module_interval'   => $autocreate_globals[$autocreate_key]['interval'],
                                'prediction_module' => 2,
                                'id_modulo'         => MODULE_PREDICTION,
                                'id_tipo_modulo'    => MODULE_TYPE_ASYNC_DATA,
                                'min_warning'       => $this->currentItem['parsed']['warning'],
                                'min_critical'      => $this->currentItem['parsed']['critical'],
                            ],
                        ],
                        [
                            'table'  => 'tagente_estado',
                            'id'     => ['id_agente_estado'],
                            'fields' => [
                                'id_agente_modulo'  => &$this->currentItem['last_autocreate'],
                                'datos'             => '',
                                'timestamp'         => '01-01-1970 00:00:00',
                                'estado'            => $autocreate_globals[$autocreate_key]['status'],
                                'known_status'      => $autocreate_globals[$autocreate_key]['status'],
                                'id_agente'         => $autocreate_globals[$autocreate_key]['id_agent'],
                                'utimestamp'        => (time() - (int) $autocreate_globals[$autocreate_key]['interval']),
                                'status_changes'    => 0,
                                'last_status'       => $autocreate_globals[$autocreate_key]['status'],
                                'last_known_status' => $autocreate_globals[$autocreate_key]['status'],
                                'current_interval'  => (int) $autocreate_globals[$autocreate_key]['interval'],
                            ],
                        ],
                    ],
                ];

                $autocreate_post_updates = [
                    'service_module' => [
                        [
                            'table'      => 'tagente_modulo',
                            'fields'     => [
                                'custom_integer_1' => &$this->currentItem['value'],
                            ],
                            'conditions' => [
                                'id_agente_modulo' => &$this->currentItem['parsed'][$field],
                            ],
                        ],
                        [
                            'table'      => 'tagente',
                            'fields'     => ['update_module_count' => '1'],
                            'conditions' => [
                                'id_agente' => $autocreate_globals[$autocreate_key]['id_agent'],
                            ],
                        ],
                    ],
                ];
            break;

            case 'service_sla_module':
                $autocreate_globals = [
                    'service_sla_module' => [
                        'id_agent' => $this->findPrdItem(
                            $this->tagente,
                            $current_item['tagente_modulo']['id_agente']
                        ),
                        'interval' => 300,
                        'status'   => AGENT_MODULE_STATUS_NORMAL,
                    ],
                ];

                $autocreate_pre_items = [
                    'service_sla_module' => [
                        [
                            'table'  => 'tagente_modulo',
                            'id'     => ['id_agente_modulo'],
                            'fields' => [
                                'id_agente'         => $autocreate_globals[$autocreate_key]['id_agent'],
                                'nombre'            => $this->currentItem['parsed']['name'].'_SLA_service',
                                'flag'              => 0,
                                'module_interval'   => $autocreate_globals[$autocreate_key]['interval'],
                                'prediction_module' => 2,
                                'id_modulo'         => MODULE_PREDICTION,
                                'id_tipo_modulo'    => MODULE_TYPE_ASYNC_PROC,
                            ],
                        ],
                        [
                            'table'  => 'tagente_estado',
                            'id'     => ['id_agente_estado'],
                            'fields' => [
                                'id_agente_modulo'  => &$this->currentItem['last_autocreate'],
                                'datos'             => '',
                                'timestamp'         => '01-01-1970 00:00:00',
                                'estado'            => $autocreate_globals[$autocreate_key]['status'],
                                'known_status'      => $autocreate_globals[$autocreate_key]['status'],
                                'id_agente'         => $autocreate_globals[$autocreate_key]['id_agent'],
                                'utimestamp'        => (time() - (int) $autocreate_globals[$autocreate_key]['interval']),
                                'status_changes'    => 0,
                                'last_status'       => $autocreate_globals[$autocreate_key]['status'],
                                'last_known_status' => $autocreate_globals[$autocreate_key]['status'],
                                'current_interval'  => (int) $autocreate_globals[$autocreate_key]['interval'],
                            ],
                        ],
                    ],
                ];

                $autocreate_post_updates = [
                    'service_sla_module' => [
                        [
                            'table'      => 'tagente_modulo',
                            'fields'     => [
                                'custom_integer_1' => &$this->currentItem['value'],
                            ],
                            'conditions' => [
                                'id_agente_modulo' => &$this->currentItem['parsed'][$field],
                            ],
                        ],
                        [
                            'table'      => 'tagente',
                            'fields'     => ['update_module_count' => '1'],
                            'conditions' => [
                                'id_agente' => $autocreate_globals[$autocreate_key]['id_agent'],
                            ],
                        ],
                    ],
                ];
            break;

            case 'service_sla_value_module':
                $autocreate_globals = [
                    'service_sla_value_module' => [
                        'id_agent' => $this->findPrdItem(
                            $this->tagente,
                            $current_item['tagente_modulo']['id_agente']
                        ),
                        'interval' => 300,
                        'status'   => AGENT_MODULE_STATUS_NORMAL,
                    ],
                ];

                $autocreate_pre_items = [
                    'service_sla_value_module' => [
                        [
                            'table'  => 'tagente_modulo',
                            'id'     => ['id_agente_modulo'],
                            'fields' => [
                                'id_agente'         => $autocreate_globals[$autocreate_key]['id_agent'],
                                'nombre'            => $this->currentItem['parsed']['name'].'_SLA_Value_service',
                                'flag'              => 0,
                                'module_interval'   => $autocreate_globals[$autocreate_key]['interval'],
                                'prediction_module' => 2,
                                'id_modulo'         => MODULE_PREDICTION,
                                'id_tipo_modulo'    => MODULE_TYPE_ASYNC_DATA,
                                'min_critical'      => $this->currentItem['parsed']['sla_limit'],
                            ],
                        ],
                        [
                            'table'  => 'tagente_estado',
                            'id'     => ['id_agente_estado'],
                            'fields' => [
                                'id_agente_modulo'  => &$this->currentItem['last_autocreate'],
                                'datos'             => '',
                                'timestamp'         => '01-01-1970 00:00:00',
                                'estado'            => $autocreate_globals[$autocreate_key]['status'],
                                'known_status'      => $autocreate_globals[$autocreate_key]['status'],
                                'id_agente'         => $autocreate_globals[$autocreate_key]['id_agent'],
                                'utimestamp'        => (time() - (int) $autocreate_globals[$autocreate_key]['interval']),
                                'status_changes'    => 0,
                                'last_status'       => $autocreate_globals[$autocreate_key]['status'],
                                'last_known_status' => $autocreate_globals[$autocreate_key]['status'],
                                'current_interval'  => (int) $autocreate_globals[$autocreate_key]['interval'],
                            ],
                        ],
                    ],
                ];

                $autocreate_post_updates = [
                    'service_sla_value_module' => [
                        [
                            'table'      => 'tagente_modulo',
                            'fields'     => [
                                'custom_integer_1' => &$this->currentItem['value'],
                            ],
                            'conditions' => [
                                'id_agente_modulo' => &$this->currentItem['parsed'][$field],
                            ],
                        ],
                        [
                            'table'      => 'tagente',
                            'fields'     => ['update_module_count' => '1'],
                            'conditions' => [
                                'id_agente' => $autocreate_globals[$autocreate_key]['id_agent'],
                            ],
                        ],
                    ],
                ];
            break;

            case 'agent_groups':
                $autocreate_pre_items = [
                    'agent_groups' => [
                        [
                            'table'  => 'tgrupo',
                            'id'     => ['id_grupo'],
                            'fields' => [
                                'nombre' => $current_item['tgrupo']['nombre'],
                            ],
                        ],
                    ],
                ];
            break;

            case 'module_groups':
                $autocreate_pre_items = [
                    'module_groups' => [
                        [
                            'table'  => 'tmodule_group',
                            'id'     => ['id_mg'],
                            'fields' => [
                                'name' => $current_item['tmodule_group']['name'],
                            ],
                        ],
                    ],
                ];
            break;

            case 'operating_systems':
                $autocreate_pre_items = [
                    'operating_systems' => [
                        [
                            'table'  => 'tconfig_os',
                            'id'     => ['id_os'],
                            'fields' => [
                                'name' => $current_item['tconfig_os']['name'],
                            ],
                        ],
                    ],
                ];
            break;

            case 'categories':
                $autocreate_pre_items = [
                    'categories' => [
                        [
                            'table'  => 'tcategory',
                            'id'     => ['id'],
                            'fields' => [
                                'name' => $current_item['tcategory']['name'],
                            ],
                        ],
                    ],
                ];
            break;

            case 'tags':
                $autocreate_pre_items = [
                    'tags' => [
                        [
                            'table'  => 'ttag',
                            'id'     => ['id_tag'],
                            'fields' => [
                                'name' => $current_item['ttag']['name'],
                            ],
                        ],
                    ],
                ];
            break;

            default:
                // Empty.
            break;
        }

        $this->currentItem['autocreate'][$field]['ref'] = $ref;
        $this->currentItem['autocreate'][$field]['pre_items']
            = (isset($autocreate_pre_items[$autocreate_key]) === true
                ? $autocreate_pre_items[$autocreate_key]
                : []
            );
        $this->currentItem['autocreate'][$field]['post_updates']
            = (isset($autocreate_post_updates[$autocreate_key]) === true
                ? $autocreate_post_updates[$autocreate_key]
                : []
            );
    }


    /**
     * Function to add an old ID reference.
     *
     * @param string $table Table.
     * @param array $fields Table fields.
     * @param string $old_value Old value.
     * @param string $current_value Current value.
     *
     * @return void
     */
    private function addItemReference(string $table, array $fields, string $old_value, string $current_value)
    {
        if(count($fields) > 1) {
            $old_value     = explode('-', $old_value);
            $current_value = explode('-', $current_value);
        } else {
            $old_value     = [$old_value];
            $current_value = [$current_value];
        }
        
        if(!isset($this->itemsReferences[$table])) {
            $this->itemsReferences[$table] = [];
        }

        foreach($fields as $k => $field) {

            if(!isset($this->itemsReferences[$table][$field])) {
                $this->itemsReferences[$table][$field] = [];
            }

            $this->itemsReferences[$table][$field][$old_value[$k]] = $current_value[$k];
        }
    }

    /**
     * Function to get an old ID reference.
     *
     * @param string $table Table.
     * @param string $field Table field.
     * @param string $old_value Old value.
     *
     * @return mixed
     */
    private function getItemReference(string $table, string $field, string $old_value)
    {
        if(isset($this->itemsReferences[$table][$field][$old_value])) {
            return $this->itemsReferences[$table][$field][$old_value];
        }

        return false;
    }

    /**
     * Function to create item in database.
     *
     * @param string $table Table.
     * @param array $crossed_refs Tables info.
     *
     * @return mixed
     */
    private function createItem(string $table, array $crossed_refs)
    {
        $id = $crossed_refs[$table]['value'];

        // Update current item crossed references
        if(
            isset($crossed_refs[$table]) &&
            !empty($crossed_refs[$table]['ref'])
        ) {
            $parent_table = $crossed_refs[$table]['parent_table'];
            foreach($crossed_refs[$table]['ref'] as $k => $f) {
                $itemReference = $this->getItemReference(
                    $parent_table,
                    $crossed_refs[$parent_table]['value'][$k],
                    $this->currentItem['parsed'][$f]
                );

                if($itemReference === false) {
                    $this->addResultError(sprintf(
                        'Failed when trying to create item (crossed references): table => %s, item => %s',
                        $table,
                        $this->currentItem['id']
                    ));
                    return false;
                }

                $this->currentItem['parsed'][$f] = $itemReference;
            }
        }

        foreach ($this->currentItem['autocreate'] as $field => $values) {
            if (isset($values['pre_items']) === true) {
                foreach ($values['pre_items'] as $insert) {
                    // Run each INSERT and store each value in $this->currentItem['last_autocreate'] overwriting.
                    $insert_query = db_process_sql_insert(
                        $insert['table'],
                        $insert['fields'],
                        false
                    );

                    $last_autocreate = db_get_all_rows_filter(
                        $insert['table'],
                        $insert['fields'],
                        $insert['id']
                    );

                    if($insert_query === false || $last_autocreate === false) {
                        $this->addResultError(sprintf(
                            'Failed when trying to autocreate unexisting item: table => %s, item => %s, field => %s',
                            $this->currentItem['table'],
                            $field,
                            $this->currentItem['id']
                        ));
                        return false;
                    }
                    $last_autocreate = end($last_autocreate);

                    $this->addResultItem($insert['table'], $last_autocreate);

                    $this->currentItem['last_autocreate'] = implode('-',array_values($last_autocreate));
                }
            }

            $this->currentItem['parsed'][$field] = $this->findPrdItem(
                $values['ref'],
                $this->currentItem['parsed'][$field]
            );
        }

        // Create item itself with INSERT query and store its value in $this->currentItem['value'].
        $insert_query = db_process_sql_insert(
            $table,
            $this->currentItem['parsed'],
            false
        );

        $insert = db_get_all_rows_filter(
            $table,
            $this->currentItem['parsed'],
            $id
        );

        if($insert_query === false || $insert === false) {
            $this->addResultError(sprintf(
                'Failed when trying to create item: table => %s, item => %s',
                $table,
                $this->currentItem['id']
            ));
            return false;
        }
        $insert = end($insert);

        $this->addResultItem($table, $insert);

        $this->currentItem['value'] = implode('-',array_values($insert));

        if(isset($crossed_refs[$table])) {
            $this->addItemReference(
                $table,
                $crossed_refs[$table]['value'],
                $this->currentItem['id'],
                $this->currentItem['value']
            );
        }

        foreach ($this->currentItem['autocreate'] as $field => $values) {
            if (isset($values['post_updates']) === true) {
                foreach ($values['post_updates'] as $update) {
                    // Run each UPDATE query.
                    $update = db_process_sql_update(
                        $update['table'],
                        $update['fields'],
                        $update['conditions'],
                        'AND',
                        false
                    );
                    
                    if($update === false) {
                        $this->addResultError(sprintf(
                            'Failed when trying to create item (post updates): table => %s, item => %s',
                            $table,
                            $this->currentItem['id']
                        ));
                        return false;
                    }
                }
            }
        }

        return true;
    }


}
