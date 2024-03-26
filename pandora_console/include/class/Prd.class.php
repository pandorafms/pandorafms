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
     * Reference to tgrupo.
     *
     * @var array
     */
    private $tgrupo;

    /**
     * Reference to ttipo_modulo.
     *
     * @var array
     */
    private $ttipoModulo;

    /**
     * Reference to tmodule_group.
     *
     * @var array
     */
    private $tmoduleGroup;

    /**
     * Reference to tconfig_os.
     *
     * @var array
     */
    private $tconfigOs;

    /**
     * Reference to tcategory.
     *
     * @var array
     */
    private $tcategory;

    /**
     * Reference to ttag.
     *
     * @var array
     */
    private $ttag;

    /**
     * Reference to tagente.
     *
     * @var array
     */
    private $tagente;

    /**
     * Reference to tagente_modulo.
     *
     * @var array
     */
    private $tagenteModulo;

    /**
     * Reference to tplugin.
     *
     * @var array
     */
    private $tplugin;

    /**
     * Reference to tmodule_inventory .
     *
     * @var array
     */
    private $tmoduleInventory;

    /**
     * Reference to tpolicies.
     *
     * @var array
     */
    private $tpolicies;

    /**
     * Reference to tpolicy_modules.
     *
     * @var array
     */
    private $tpolicyModules;

    /**
     * Reference to talert_actions.
     *
     * @var array
     */
    private $talertActions;

    /**
     * Reference to talert_templates.
     *
     * @var array
     */
    private $talertTemplates;

    /**
     * Reference to tcollection.
     *
     * @var array
     */
    private $tcollection;

    /**
     * Reference to tgraph.
     *
     * @var array
     */
    private $tgraph;

    /**
     * Reference to tservice.
     *
     * @var array
     */
    private $tservice;

    /**
     * Reference to tlayout .
     *
     * @var array
     */
    private $tlayout;

    /**
     * Reference to tlayout_data .
     *
     * @var array
     */
    private $tlayoutData;

    /**
     * Reference to treport_custom_sql.
     *
     * @var array
     */
    private $treportCustomSql;

    /**
     * Reference to tserver_export.
     *
     * @var array
     */
    private $tserverExport;

    /**
     * Reference to trecon_task.
     *
     * @var array
     */
    private $treconTask;

    /**
     * Reference to tmap.
     *
     * @var array
     */
    private $tmap;

    /**
     * Reference to titem.
     *
     * @var array
     */
    private $titem;

    /**
     * Reference to tgis_map_connection.
     *
     * @var array
     */
    private $tgisMapConnection;

    /**
     * Reference to tserver.
     *
     * @var array
     */
    private $tserver;

    /**
     * Reference to twidget.
     *
     * @var array
     */
    private $twidget;

    /**
     * Reference to treport.
     *
     * @var array
     */
    private $treport;

    /**
     * Reference to tnetflow_filter.
     *
     * @var array
     */
    private $tnetflowFilter;

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
     * Current prdData.
     *
     * @var array
     */
    private $currentPrdData;


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

        $this->ttipoModulo = [
            'table'   => 'ttipo_modulo',
            'id'      => 'id_tipo',
            'columns' => ['nombre'],
        ];

        $this->tmoduleGroup = [
            'table'           => 'tmodule_group',
            'id'              => 'id_mg',
            'columns'         => ['name'],
            'autocreate_item' => 'module_groups',
        ];

        $this->tconfigOs = [
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

        $this->tagenteModulo = [
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

        $this->tmoduleInventory = [
            'table'   => 'tmodule_inventory',
            'id'      => 'id_module_inventory',
            'columns' => ['name'],
            'join'    => ['id_os' => $this->tconfigOs],
        ];

        $this->tpolicies = [
            'table'   => 'tpolicies',
            'id'      => 'id',
            'columns' => ['name'],
        ];

        $this->tpolicyModules = [
            'table'   => 'tpolicy_modules',
            'id'      => 'id',
            'columns' => ['name'],
            'join'    => ['id_policy' => $this->tpolicies],
        ];

        $this->talertActions = [
            'table'   => 'talert_actions',
            'id'      => 'id',
            'columns' => ['name'],
        ];

        $this->talertTemplates = [
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

        $this->tlayoutData = [
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

        $this->treportCustomSql = [
            'table'   => 'treport_custom_sql',
            'id'      => 'id',
            'columns' => ['name'],
        ];

        $this->tserverExport = [
            'table'   => 'tserver_export',
            'id'      => 'id',
            'columns' => ['name'],
        ];

        $this->treconTask = [
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

        $this->tgisMapConnection = [
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

        $this->tnetflowFilter = [
            'table'   => 'tnetflow_filter',
            'id'      => 'id_sg',
            'columns' => [
                'ip_dst',
                'ip_src',
                'dst_port',
                'src_port',
                'router_ip',
                'advanced_filter',
                'filter_args',
                'aggregate',
                'netflow_monitoring',
                'traffic_max',
                'traffic_critical',
                'traffic_warning',
                'netflow_monitoring_interval',
            ],
        ];

        // Define references between tables fields.
        $this->columnRefs = [
            'tlayout'                      => [
                'id_group' => ['ref' => $this->tgrupo],
            ],
            'tlayout_data'                 => [
                'id_agente_modulo' => ['ref' => $this->tagenteModulo],
                'id_agent'         => ['ref' => $this->tagente],
                'id_layout_linked' => ['ref' => $this->tlayout],
                'parent_item'      => ['ref' => $this->tlayoutData],
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
                'id_agent_module'       => ['ref' => $this->tagenteModulo],
                'id_agent'              => ['ref' => $this->tagente],
                'treport_custom_sql_id' => ['ref' => $this->treportCustomSql],
                'id_group'              => ['ref' => $this->tgrupo],
                'id_module_group'       => ['ref' => $this->tmoduleGroup],
                'ncm_agents'            => ['ref' => ($this->tagente + ['array' => true])],
                'text'                  => [
                    'conditional_refs' => [
                        [
                            'when' => ['type' => 'netflow_area'],
                            'ref'  => $this->tnetflowFilter,
                        ],
                        [
                            'when' => ['type' => 'netflow_data'],
                            'ref'  => $this->tnetflowFilter,
                        ],
                        [
                            'when' => ['type' => 'netflow_summary'],
                            'ref'  => $this->tnetflowFilter,
                        ],
                        [
                            'when' => ['type' => 'netflow_top_N'],
                            'ref'  => $this->tnetflowFilter,
                        ],
                    ],
                ],
            ],
            'treport_content_item'         => [
                'id_agent_module' => ['ref' => $this->tagenteModulo],
            ],
            'treport_content_sla_combined' => [
                'id_agent_module' => [
                    'conditional_refs' => [
                        [
                            'when' => [
                                'id_report_content' => [
                                    'table' => 'treport_content',
                                    'id'    => 'id_rc',
                                    'when'  => ['type' => 'SLA_services'],
                                ],
                            ],
                            'ref'  => $this->tservice,
                        ],
                    ],
                    'ref'              => $this->tagenteModulo,
                ],
            ],
            'tpolicies'                    => [
                'id_group' => ['ref' => $this->tgrupo],
            ],
            'tpolicy_agents'               => [
                'id_agent' => ['ref' => $this->tagente],
            ],
            'tpolicy_alerts'               => [
                'id_policy_module'  => ['ref' => $this->tpolicyModules],
                'id_alert_template' => ['ref' => $this->talertTemplates],
            ],
            'tpolicy_alerts_actions'       => [
                'id_alert_action' => ['ref' => $this->talertActions],
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
                'id_tipo_modulo'  => ['ref' => $this->ttipoModulo],
                'id_module_group' => ['ref' => $this->tmoduleGroup],
                'id_export'       => ['ref' => $this->tserverExport],
                'id_plugin'       => ['ref' => $this->tplugin],
                'id_category'     => ['ref' => $this->tcategory],
            ],
            'ttag_policy_module'           => [
                'id_tag' => ['ref' => $this->ttag],
            ],
            'tpolicy_modules_synth'        => [
                'id_agent_module_source' => ['ref' => $this->tagenteModulo],
            ],
            'tpolicy_modules_inventory'    => [
                'id_module_inventory' => ['ref' => $this->tmoduleInventory],
            ],
            'tservice'                     => [
                'id_group'                       => ['ref' => $this->tgrupo],
                'id_agent_module'                => ['ref' => ($this->tagenteModulo + ['autocreate_item' => 'service_module'])],
                'sla_id_module'                  => ['ref' => ($this->tagenteModulo + ['autocreate_item' => 'service_sla_module'])],
                'sla_value_id_module'            => ['ref' => ($this->tagenteModulo + ['autocreate_item' => 'service_sla_value_module'])],
                'id_template_alert_warning'      => ['ref' => $this->talertTemplates],
                'id_template_alert_critical'     => ['ref' => $this->talertTemplates],
                'id_template_alert_unknown'      => ['ref' => $this->talertTemplates],
                'id_template_alert_critical_sla' => ['ref' => $this->talertTemplates],
            ],
            'tservice_element'             => [
                'id_agente_modulo' => ['ref' => $this->tagenteModulo],
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
                            'ref'  => $this->treconTask,
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
                            'ref'  => $this->tagenteModulo,
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
                            'ref'  => $this->tagenteModulo,
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
                            'ref'  => $this->tagenteModulo,
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
                'tgis_map_con_id_tmap_con' => ['ref' => $this->tgisMapConnection],
            ],
            'tgraph'                       => [
                'id_group' => ['ref' => $this->tgrupo],
            ],
            'tgraph_source'                => [
                'id_server'       => ['ref' => $this->tserver],
                'id_agent_module' => ['ref' => $this->tagenteModulo],
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
            'treport_content'   => [
                'external_source' => [
                    'module'    => [
                        'ref' => ($this->tagenteModulo + ['array' => true, 'values_as_keys' => true]),
                    ],
                    'id_agents' => ['ref' => ($this->tagente + ['array' => true])],
                    'templates' => ['ref' => ($this->talertTemplates + ['array' => true])],
                    'actions'   => ['ref' => ($this->talertActions + ['array' => true])],
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
                                'ref'  => $this->tagenteModulo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'AvgSumMaxMinModule'],
                                    ],
                                ],
                                'ref'  => $this->tagenteModulo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'BasicChart'],
                                    ],
                                ],
                                'ref'  => $this->tagenteModulo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'module_icon'],
                                    ],
                                ],
                                'ref'  => $this->tagenteModulo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'graph_module_histogram'],
                                    ],
                                ],
                                'ref'  => $this->tagenteModulo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'module_table_value'],
                                    ],
                                ],
                                'ref'  => $this->tagenteModulo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'module_status'],
                                    ],
                                ],
                                'ref'  => $this->tagenteModulo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'module_value'],
                                    ],
                                ],
                                'ref'  => $this->tagenteModulo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'sla_percent'],
                                    ],
                                ],
                                'ref'  => $this->tagenteModulo,
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
                                'ref'  => $this->tagenteModulo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'wux_transaction_stats'],
                                    ],
                                ],
                                'ref'  => $this->tagenteModulo,
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
                                'ref'  => $this->tmoduleGroup,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'service_level'],
                                    ],
                                ],
                                'ref'  => $this->tmoduleGroup,
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
                    'groups[0]'                   => [
                        'conditional_refs' => [
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'AgentHive'],
                                    ],
                                ],
                                'ref'  => ($this->tgrupo + ['csv' => true, 'csv_separator' => ',']),
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'heatmap'],
                                    ],
                                ],
                                'ref'  => ($this->tgrupo + ['csv' => true, 'csv_separator' => ',']),
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
                                'ref'  => ($this->tagenteModulo + ['array' => true, 'values_as_keys' => true]),
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
                                'ref'  => ($this->tagenteModulo + ['array' => true, 'values_as_keys' => true]),
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
                                'ref'  => ($this->tagenteModulo + ['array' => true, 'values_as_keys' => true]),
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
                                'ref'  => ($this->tagenteModulo + ['array' => true, 'values_as_keys' => true]),
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
     * Initialize result
     *
     * @return void
     */
    private function initializeResult()
    {
        $this->result = [
            'status' => true,
            'items'  => [],
            'errors' => [],
            'info'   => [],
        ];
    }


    /**
     * Fills result with status.
     *
     * @param boolean $status Result status.
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
     * @param string $table  Item table.
     * @param array  $values Item values.
     *
     * @return void
     */
    public function addResultItem(string $table, array $values)
    {
        $this->result['items'][] = [
            $table,
            $values,
        ];
    }


    /**
     * Fills result with info message.
     *
     * @param string $msg Info message.
     *
     * @return void
     */
    public function addResultInfo(string $msg)
    {
        $this->result['info'][] = $msg;
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
     * @return boolean
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
     * @param array  $prd_data     PrdData.
     * @param array  $result       Empty array.
     * @param string $parent_table Parent level table name.
     *
     * @return void
     */
    private function getTablesPrdData($prd_data, &$result=[], $parent_table='')
    {
        if (isset($prd_data['items']) === true) {
            $result[] = $prd_data['items']['table'];
            $this->crossed_refs[$prd_data['items']['table']] = [
                'value'        => $prd_data['items']['value'],
                'ref'          => [],
                'parent_table' => $parent_table,
            ];
            if ($prd_data['items']['data']) {
                $this->getTablesPrdData($prd_data['items']['data'], $result, $prd_data['items']['table']);
            }
        } else {
            foreach ($prd_data as $key => $value) {
                $result[] = $value['table'];
                $this->crossed_refs[$value['table']] = [
                    'value'        => $value['value'],
                    'ref'          => isset($value['ref']) ? $value['ref'] : [],
                    'parent_table' => $parent_table,
                ];
                if (isset($value['data'])) {
                    $this->getTablesPrdData($value['data'], $result, $value['table']);
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
     * @return mixed
     */
    private function searchValue(array $columns, string $table, string $id, $value)
    {
        $sql_column = sprintf(
            'SELECT %s FROM %s WHERE %s="%s"',
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
            $value = $new_array;
        }

        return $value;
    }


    /**
     * Function that checks if a value is a base64.
     *
     * @param string $string Value to be checked.
     *
     * @return boolean
     */
    private function validateBase64(string $string): bool
    {
        // Check if the string is valid base64 by decoding it
        $decoded = base64_decode($string, true);

        // Check if decoding was successful and if the decoded string matches the original
        return ($decoded !== false && base64_encode($decoded) === $string);
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
            json_decode($json);
            return (json_last_error() === JSON_ERROR_NONE);
        } catch (Exception $e) {
            return false;
        }
    }


    /**
     * Function to traverse the array based on the reference.
     *
     * @param mixed  $data      JSON Array.
     * @param string $reference JSON key reference.
     *
     * @return mixed
     */
    private function extractJsonArrayValue($data, $reference)
    {
        $keys = explode('.', $reference);

        foreach ($keys as $key) {
            if (preg_match('/(.+)\[(\d+)\]/', $key, $matches)) {
                // Handle array access
                $data = $data[$matches[1]][$matches[2]];
            } else {
                // Handle regular key access
                $data = $data[$key];
            }
        }

        return $data;
    }


    /**
     * Function to update a value in the JSON based on the reference.
     *
     * @param mixed  $data      JSON Array.
     * @param string $reference JSON key reference.
     * @param mixed  $newValue  JSON new value.
     *
     * @return void
     */
    private function updateJsonArrayValue(&$data, $reference, $newValue)
    {
        preg_match_all('/(\w+)|\[(\d+)\]/', $reference, $matches, PREG_SET_ORDER);
        $keys = [];
        foreach ($matches as $match) {
            if (isset($match[2]) === true) {
                $keys[] = (int) $match[2];
            } else {
                $keys[] = $match[1];
            }
        }

        $lastIndex = (count($keys) - 1);
        $updated_data = &$data;

        for ($i = 0; $i < $lastIndex; $i++) {
            $updated_data = &$updated_data[$keys[$i]];
        }

        $updated_data[$keys[$lastIndex]] = $newValue;
    }


    /**
     * Get reference from value and return true if found.
     *
     * @param mixed $when_value Condition to build SQL.
     * @param array $sql_tables SQL tables.
     * @param array $sql_wheres SQL wheres.
     *
     * @return void
     */
    private function recursiveWhenSQLBuildWhere($when_value, &$sql_tables, &$sql_wheres)
    {
        $rec_when = reset($when_value['when']);
        if (is_array($rec_when) === true) {
            $sql_tables[] = '`'.$rec_when['table'].'`';
            $sql_wheres[] = '`'.$when_value['table'].'`.`'.array_key_first($when_value['when']).'` = `'.$rec_when['table'].'`.`'.$rec_when['id'].'`';

            $this->recursiveWhenSQLBuildWhere($rec_when, $sql_tables, $sql_wheres);
        } else {
            $sql_wheres[] = '`'.$when_value['table'].'`.`'.array_key_first($when_value['when']).'` = "'.$rec_when.'"';
        }
    }


    /**
     * Evals conditional references.
     *
     * @param string $compare_value Value to compare.
     * @param mixed  $when          Condition to check.
     *
     * @return boolean
     */
    private function evalConditionalRef($compare_value, $when)
    {
        if (is_array($when) === true) {
            $when_value = reset($when);
        } else {
            $when_value = $when;
        }

        if ($compare_value == $when_value) {
            return true;
        } else {
            if (is_array($when_value) === true) {
                if (isset($when_value['table']) === true
                    && isset($when_value['id']) === true
                    && isset($when_value['when']) === true
                ) {
                    if ($this->validateJSON($compare_value) === true) {
                        $json_value = json_decode($compare_value, true);
                        foreach ($when_value['when'] as $when_key => $w) {
                            if (isset($json_value[$when_value['table']][$when_key]) === true) {
                                $match_compare_value = $json_value[$when_value['table']][$when_key];
                                return $this->evalConditionalRef($match_compare_value, $w);
                            }
                        }
                    }

                    $sql_fields = [];
                    $sql_tables = [];
                    $sql_wheres = [];

                    $sql_fields[] = '`'.$when_value['table'].'`.`'.$when_value['id'].'`';
                    $sql_tables[] = '`'.$when_value['table'].'`';

                    $this->recursiveWhenSQLBuildWhere($when_value, $sql_tables, $sql_wheres);

                    $sql = sprintf(
                        'SELECT %s FROM %s WHERE %s',
                        implode(',', $sql_fields),
                        implode(',', $sql_tables),
                        implode(' AND ', $sql_wheres)
                    );

                    $sql_value = db_get_value_sql($sql);

                    $crossed_ref = $this->getItemReference($when_value['table'], $when_value['id'], $compare_value);
                    if ($crossed_ref !== false) {
                        $compare_value = $crossed_ref;
                    }

                    if ($compare_value == $sql_value) {
                        return true;
                    }
                }
            }
        }

        return false;
    }


    /**
     * Get reference from value and return true if found.
     *
     * @param string $table     Table.
     * @param string $column    Table column.
     * @param array  $reference Reference to extract value.
     * @param array  $row       Current row values.
     * @param string $value     Value to update.
     *
     * @return void
     */
    private function getReferenceFromValue($table, $column, $reference, $row, &$value)
    {
        if (isset($reference['conditional_refs']) === true) {
            // Conditional refs.
            $conditional = $reference['conditional_refs'];
            foreach ($conditional as $key => $condition) {
                if (isset($condition['when']) === true
                    && isset($condition['ref']) === true
                ) {
                    if (isset($row[array_key_first($condition['when'])]) === true) {
                        $compare_value = $row[array_key_first($condition['when'])];

                        if ($this->evalConditionalRef($compare_value, $condition['when']) === true
                            && empty($value) === false
                        ) {
                            $ref = $condition['ref'];
                            if (isset($ref['join']) === true) {
                                if (isset($ref['array']) === true
                                    && $ref['array'] === true
                                ) {
                                    if (is_array($value) === true) {
                                        $value_arr = $value;
                                    } else {
                                        $value_arr = json_decode($value, true);
                                    }

                                    if (is_array($value_arr)) {
                                        $ref_arr = [];
                                        foreach ($value_arr as $val) {
                                            $join_array = $this->recursiveJoin(
                                                $ref,
                                                $val
                                            );
                                            $ref_arr[] = [$ref['table'] => $join_array];
                                        }

                                        $value = $ref_arr;
                                    }
                                } else if (isset($ref['csv']) === true
                                    && $ref['csv'] === true
                                ) {
                                    $csv_separator = ',';
                                    if (isset($ref['csv_separator']) === true
                                        && $ref['csv_separator'] === true
                                    ) {
                                        $csv_separator = $ref['csv_separator'];
                                    }

                                    $value_arr = explode($csv_separator, $value);
                                    $ref_arr = [];
                                    foreach ($value_arr as $val) {
                                        $join_array = $this->recursiveJoin(
                                            $ref,
                                            $val
                                        );
                                        $val = [$ref['table'] => $join_array];
                                        $ref_arr[] = json_encode($val);
                                    }

                                    $value = implode($csv_separator, $ref_arr);
                                } else {
                                    $join_array = $this->recursiveJoin(
                                        $ref,
                                        $value
                                    );
                                    $value = [$ref['table'] => $join_array];
                                    $value = json_encode($value);
                                }
                            } else {
                                if (isset($ref['array']) === true
                                    && $ref['array'] === true
                                ) {
                                    if (is_array($value) === true) {
                                        $value_arr = $value;
                                    } else {
                                        $value_arr = json_decode($value, true);
                                    }

                                    if (is_array($value_arr)) {
                                        $ref_arr = [];
                                        foreach ($value_arr as $val) {
                                            $ref_val = $this->searchValue(
                                                $ref['columns'],
                                                $ref['table'],
                                                $ref['id'],
                                                $val
                                            );
                                            if ($ref_val !== false) {
                                                if (isset($ref['values_as_keys']) === true
                                                    && $ref['values_as_keys'] === true
                                                ) {
                                                    $ref_arr[$ref_val] = $ref_val;
                                                } else {
                                                    $ref_arr[] = $ref_val;
                                                }
                                            }
                                        }

                                        $value = $ref_arr;
                                    }
                                } else if (isset($ref['csv']) === true
                                    && $ref['csv'] === true
                                ) {
                                    $csv_separator = ',';
                                    if (isset($ref['csv_separator']) === true
                                        && $ref['csv_separator'] === true
                                    ) {
                                        $csv_separator = $ref['csv_separator'];
                                    }

                                    $value_arr = explode($csv_separator, $value);
                                    $ref_arr = [];
                                    foreach ($value_arr as $val) {
                                        $ref_val = $this->searchValue(
                                            $ref['columns'],
                                            $ref['table'],
                                            $ref['id'],
                                            $val
                                        );
                                        if (is_array($ref_val) === true) {
                                            $ref_val = json_encode($ref_val);
                                        }

                                        $ref_arr[] = $ref_val;
                                    }

                                    $value = implode($csv_separator, $ref_arr);
                                } else {
                                    $columns_ref = $this->getOneColumnRefs($ref['table']);

                                    $value = $this->searchValue(
                                        $ref['columns'],
                                        $ref['table'],
                                        $ref['id'],
                                        $value
                                    );

                                    // Get reference in value
                                    if ($columns_ref !== false) {
                                        foreach ($columns_ref as $col => $col_ref) {
                                            if (array_key_exists($col, $value[$ref['table']])) {
                                                $sql = sprintf(
                                                    'SELECT * FROM %s WHERE %s = "%s"',
                                                    $ref['table'],
                                                    $col,
                                                    $value[$ref['table']][$col],
                                                );
                                                $row = db_get_row_sql($sql);

                                                $this->getReferenceFromValue(
                                                    $ref['table'],
                                                    $col,
                                                    $col_ref,
                                                    $row,
                                                    $value[$ref['table']][$col]
                                                );
                                            }
                                        }
                                    }
                                }
                            }

                            return;
                        }
                    }
                }
            }
        }

        if (isset($reference['ref']) === true) {
            $ref = $reference['ref'];

            if (isset($ref['join']) === true) {
                if (isset($ref['array']) === true
                    && $ref['array'] === true
                ) {
                    if (is_array($value) === true) {
                        $value_arr = $value;
                    } else {
                        $value_arr = json_decode($value, true);
                    }

                    if (is_array($value_arr)) {
                        $ref_arr = [];
                        foreach ($value_arr as $val) {
                            $join_array = $this->recursiveJoin(
                                $ref,
                                $val
                            );
                            $ref_arr[] = [$ref['table'] => $join_array];
                        }

                        $value = $ref_arr;
                    }
                } else if (isset($ref['csv']) === true
                    && $ref['csv'] === true
                ) {
                    $csv_separator = ',';
                    if (isset($ref['csv_separator']) === true
                        && $ref['csv_separator'] === true
                    ) {
                        $csv_separator = $ref['csv_separator'];
                    }

                    $value_arr = explode($csv_separator, $value);
                    $ref_arr = [];
                    foreach ($value_arr as $val) {
                        $join_array = $this->recursiveJoin(
                            $ref,
                            $val
                        );
                        $val = [$ref['table'] => $join_array];
                        $ref_arr[] = json_encode($val);
                    }

                    $value = implode($csv_separator, $ref_arr);
                } else {
                    $join_array = $this->recursiveJoin(
                        $ref,
                        $value
                    );
                    $value = [$ref['table'] => $join_array];
                    $value = json_encode($value);
                }
            } else {
                if (isset($ref['array']) === true
                    && $ref['array'] === true
                ) {
                    if (is_array($value) === true) {
                        $value_arr = $value;
                    } else {
                        $value_arr = json_decode($value, true);
                    }

                    if (is_array($value_arr)) {
                        $ref_arr = [];
                        foreach ($value_arr as $val) {
                            $ref_val = $this->searchValue(
                                $ref['columns'],
                                $ref['table'],
                                $ref['id'],
                                $val
                            );
                            if ($ref_val !== false) {
                                if (isset($ref['values_as_keys']) === true
                                    && $ref['values_as_keys'] === true
                                ) {
                                    $ref_arr[$ref_val] = $ref_val;
                                } else {
                                    $ref_arr[] = $ref_val;
                                }
                            }
                        }

                        $value = $ref_arr;
                    }
                } else if (isset($ref['csv']) === true
                    && $ref['csv'] === true
                ) {
                    $csv_separator = ',';
                    if (isset($ref['csv_separator']) === true
                        && $ref['csv_separator'] === true
                    ) {
                        $csv_separator = $ref['csv_separator'];
                    }

                    $value_arr = explode($csv_separator, $value);
                    $ref_arr = [];
                    foreach ($value_arr as $val) {
                        $ref_val = $this->searchValue(
                            $ref['columns'],
                            $ref['table'],
                            $ref['id'],
                            $val
                        );
                        if (is_array($ref_val) === true) {
                            $ref_val = json_encode($ref_val);
                        }

                        $ref_arr[] = $ref_val;
                    }

                    $value = implode($csv_separator, $ref_arr);
                } else {
                    $columns_ref = $this->getOneColumnRefs($ref['table']);

                    $value = $this->searchValue(
                        $ref['columns'],
                        $ref['table'],
                        $ref['id'],
                        $value
                    );

                    // Get reference in value
                    if ($columns_ref !== false) {
                        foreach ($columns_ref as $col => $col_ref) {
                            if (array_key_exists($col, $value[$ref['table']])) {
                                $sql = sprintf(
                                    'SELECT * FROM %s WHERE %s = "%s"',
                                    $ref['table'],
                                    $col,
                                    $value[$ref['table']][$col],
                                );
                                $row = db_get_row_sql($sql);

                                $this->getReferenceFromValue(
                                    $ref['table'],
                                    $col,
                                    $col_ref,
                                    $row,
                                    $value[$ref['table']][$col]
                                );
                            }
                        }
                    }
                }
            }
        }
    }


    /**
     * Get value from reference and return true if found.
     *
     * @param string $table     Table.
     * @param string $column    Table column.
     * @param array  $reference Reference to extract value.
     * @param string $value     Value to update.
     *
     * @return boolean
     */
    private function getValueFromReference($table, $column, $reference, $item, &$value)
    {
        if (isset($reference['conditional_refs']) === true) {
            // Conditional refs.
            $prd_item = false;
            $conditional = $reference['conditional_refs'];
            foreach ($conditional as $key => $condition) {
                if (isset($condition['when']) === true
                    && isset($condition['ref']) === true
                ) {
                    if (isset($item[array_key_first($condition['when'])]) === true) {
                        $compare_value = $item[array_key_first($condition['when'])];

                        if ($this->evalConditionalRef($compare_value, $condition['when']) === true
                            && empty($value) === false
                        ) {
                            if (isset($condition['ref']['array']) === true
                                && $condition['ref']['array'] === true
                            ) {
                                if (is_array($value) === true) {
                                    $value_arr = $value;
                                } else {
                                    $value_arr = json_decode($value, true);
                                }

                                if (is_array($value_arr)) {
                                    $ref_arr = [];
                                    foreach ($value_arr as $val) {
                                        $ref_val = $this->findPrdItem(
                                            $condition['ref'],
                                            is_array($val) ? json_encode($val) : $val
                                        );

                                        if ($ref_val === false && $ref_val != $val) {
                                            if ($this->evalAutocreateItem($condition['ref'], is_array($val) ? json_encode($val) : $val, $column) === false) {
                                                return false;
                                            } else {
                                                return true;
                                            }
                                        }

                                        if ($ref_val !== false) {
                                            if (isset($condition['ref']['values_as_keys']) === true
                                                && $condition['ref']['values_as_keys'] === true
                                            ) {
                                                $ref_arr[$ref_val] = $ref_val;
                                            } else {
                                                $ref_arr[] = $ref_val;
                                            }
                                        }
                                    }

                                    $value = $ref_arr;
                                }
                            } else if (isset($condition['ref']['csv']) === true
                                && $condition['ref']['csv'] === true
                            ) {
                                $csv_separator = ',';
                                if (isset($condition['ref']['csv_separator']) === true
                                    && $condition['ref']['csv_separator'] === true
                                ) {
                                    $csv_separator = $condition['ref']['csv_separator'];
                                }

                                $value_arr = explode($csv_separator, $value);
                                $ref_arr = [];
                                foreach ($value_arr as $val) {
                                    $ref_val = $this->findPrdItem(
                                        $condition['ref'],
                                        $val
                                    );

                                    if ($ref_val === false && $ref_val != $val) {
                                        if ($this->evalAutocreateItem($condition['ref'], $val, $column) === false) {
                                            return false;
                                        } else {
                                            return true;
                                        }
                                    }

                                    $ref_arr[] = $ref_val;
                                }

                                $value = implode($csv_separator, $ref_arr);
                            } else {
                                $prd_item = $this->findPrdItem(
                                    $condition['ref'],
                                    is_array($value) ? json_encode($value) : $value
                                );

                                if ($prd_item === false && $prd_item != $value) {
                                    if ($this->evalAutocreateItem($condition['ref'], is_array($value) ? json_encode($value) : $value, $column) === false) {
                                        return false;
                                    } else {
                                        return true;
                                    }
                                }

                                $value = $prd_item;
                            }

                            return true;
                        }
                    }
                }
            }
        }

        if (isset($reference['ref']) === true) {
            $ref = $reference['ref'];
            if (isset($ref['array']) === true
                && $ref['array'] === true
            ) {
                if (is_array($value) === true) {
                    $value_arr = $value;
                } else {
                    $value_arr = json_decode($value, true);
                }

                if (is_array($value_arr)) {
                    $ref_arr = [];
                    foreach ($value_arr as $val) {
                        $ref_val = $this->findPrdItem(
                            $ref,
                            is_array($val) ? json_encode($val) : $val
                        );

                        if ($ref_val === false && $ref_val != $val) {
                            if ($this->evalAutocreateItem($ref, is_array($val) ? json_encode($val) : $val, $column) === false) {
                                return false;
                            } else {
                                return true;
                            }
                        }

                        if ($ref_val !== false) {
                            if (isset($ref['values_as_keys']) === true
                                && $ref['values_as_keys'] === true
                            ) {
                                $ref_arr[$ref_val] = $ref_val;
                            } else {
                                $ref_arr[] = $ref_val;
                            }
                        }
                    }

                    $value = $ref_arr;
                }
            } else if (isset($ref['csv']) === true
                && $ref['csv'] === true
            ) {
                $csv_separator = ',';
                if (isset($ref['csv_separator']) === true
                    && $ref['csv_separator'] === true
                ) {
                    $csv_separator = $ref['csv_separator'];
                }

                $value_arr = explode($csv_separator, $value);
                $ref_arr = [];
                foreach ($value_arr as $val) {
                    $ref_val = $this->findPrdItem(
                        $ref,
                        $val
                    );

                    if ($ref_val === false && $ref_val != $val) {
                        if ($this->evalAutocreateItem($ref, $val, $column) === false) {
                            return false;
                        } else {
                            return true;
                        }
                    }

                    $ref_arr[] = $ref_val;
                }

                $value = implode($csv_separator, $ref_arr);
            } else {
                $prd_item = $this->findPrdItem(
                    $ref,
                    is_array($value) ? json_encode($value) : $value
                );

                if ($prd_item === false && $prd_item != $value) {
                    if ($this->evalAutocreateItem($ref, is_array($value) ? json_encode($value) : $value, $column) === false) {
                        return false;
                    } else {
                        return true;
                    }
                }

                $value = $prd_item;
            }

            return true;
        }

        if (isset($reference['fixed_value']) === true) {
            $value = $reference['fixed_value'];
            return true;
        }

        return true;
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
        $this->currentPrdData = $prd_data;
        if (empty($prd_data) === false) {
            $result .= '[prd_data]'.LINE_BREAK.LINE_BREAK;
            $result .= 'type="'.$type.'"'.LINE_BREAK;
            $result .= 'name="'.io_safe_output($name).'"'.LINE_BREAK.LINE_BREAK;

            $prd_export_tables = [];
            $this->recursiveExportPrd([$prd_data['items']], $id, $prd_export_tables);

            foreach ($prd_export_tables as $table => $rows) {
                $result .= '['.$table.']'.LINE_BREAK.LINE_BREAK;

                foreach ($rows as $index => $row) {
                    foreach ($row as $field => $value) {
                        // Scape double quotes in all values.
                        $value = str_replace('"', '\"', $value);
                        $result .= $field.'['.$index.']="'.$value.'"'.LINE_BREAK;
                    }

                    $result .= LINE_BREAK;
                }
            }
        }

        return $result;
    }


    /**
     * Recursive function to traverse all data
     *
     * @param mixed $data   Data.
     * @param mixed $id     Id value for search.
     * @param mixed $result Result.
     *
     * @return void
     */
    private function recursiveExportPrd($data, $id, &$result=[])
    {
        foreach ($data as $key => $element) {
            if (!isset($result[$element['table']])) {
                $result[$element['table']] = [];
            }

            $columns_ref = $this->getOneColumnRefs($element['table']);
            $json_ref = $this->getOneJsonRefs($element['table']);

            $sql_field = reset($element['value']);
            if (isset($element['ref'])) {
                $sql_field = reset($element['ref']);
            }

            $sql = sprintf(
                'SELECT * FROM %s WHERE %s = "%s"',
                $element['table'],
                $sql_field,
                $id,
            );

            if (empty($id) === false && empty($element['table']) === false
                && empty(reset($element['value'])) === false
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
                    $isBase64 = false;
                    if (isset($columns_ref[$column]) === true
                        && empty($value) === false
                    ) {
                        if (is_string($value) === true && $this->validateBase64($value) === true) {
                            $value = base64_decode($value);
                            $isBase64 = true;
                        }

                        // The column is inside column refs.
                        $this->getReferenceFromValue(
                            $element['table'],
                            $column,
                            $columns_ref[$column],
                            $row,
                            $value
                        );
                    } else if (isset($json_ref[$column]) === true
                        && empty($value) === false
                    ) {
                        // Json ref.
                        $array_value = json_decode($value, true);
                        foreach ($json_ref[$column] as $json_key => $ref) {
                            $json_value = $this->extractJsonArrayValue($array_value, $json_key);
                            if (isset($json_value) === true) {
                                $isBase64 = false;
                                if (is_string($json_value) === true && $this->validateBase64($json_value) === true) {
                                    $json_value = base64_decode($json_value);
                                    $isBase64 = true;
                                }

                                $this->getReferenceFromValue(
                                    $element['table'],
                                    $column,
                                    $ref,
                                    $row,
                                    $json_value
                                );
                                if ($isBase64 === true) {
                                    if (is_array($json_value) === true) {
                                        $json_value = json_encode($json_value);
                                    }

                                    $json_value = base64_encode($json_value);
                                }

                                $this->updateJsonArrayValue($array_value, $json_key, $json_value);
                            }
                        }

                        $isBase64 = false;
                        $value = json_encode($array_value);
                    }

                    if (is_array($value) === true) {
                        $value = json_encode($value);
                    }

                    if ($isBase64 === true) {
                        $value = base64_encode($value);
                    }

                    if (!isset($result[$element['table']][$primary_key])) {
                        $result[$element['table']][$primary_key] = [];
                    }

                    $result[$element['table']][$primary_key][$column] = $value;
                }

                if (isset($element['data']) === true) {
                    $this->recursiveExportPrd($element['data'], $primary_key, $result);
                }
            }
        }
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
                'SELECT %s, %s FROM %s WHERE %s="%s"',
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
                'SELECT %s FROM %s WHERE %s="%s"',
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

        $this->initializeResult();

        if (empty($data_file['prd_data']) === false) {
            $type = $data_file['prd_data']['type'];
            $name = io_safe_input($data_file['prd_data']['name']);
            unset($data_file['prd_data']);

            $prd_data = $this->getOnePrdData($type);
            $this->currentPrdData = $prd_data;
            if ($prd_data !== false) {
                // Begin transaction.
                $db = $config['dbconnection'];
                $db->begin_transaction();

                try {
                    $tables = [];
                    $this->crossed_refs = [];
                    $tables_id = [];
                    $this->getTablesPrdData($prd_data, $tables);
                    foreach ($tables as $table) {
                        if (isset($data_file[$table]) === false) {
                            continue;
                        }

                        $internal_array = $data_file[$table];

                        $column_refs = $this->getOneColumnRefs($table);
                        $json_refs = $this->getOneJsonRefs($table);

                        $ids = reset($internal_array);
                        foreach ($ids as $id => $i) {
                            $create_item = true;
                            $this->fillCurrentItem($id, $table, $internal_array);
                            foreach ($this->currentItem['parsed'] as $column => $value) {
                                if (isset($column_refs[$column]) === true
                                    && empty($value) === false
                                ) {
                                    $isBase64 = false;
                                    if (is_string($value) === true && $this->validateBase64($value)) {
                                        $value = base64_decode($value);
                                        $isBase64 = true;
                                    }

                                    $create_item = $this->getValueFromReference(
                                        $table,
                                        $column,
                                        $column_refs[$column],
                                        $this->currentItem['parsed'],
                                        $value
                                    );

                                    if (is_array($value) === true) {
                                        $value = json_encode($value);
                                    }

                                    if ($isBase64 === true) {
                                        $value = base64_encode($value);
                                    }
                                } else if (isset($json_refs[$column]) === true
                                    && empty($value) === false
                                ) {
                                    $array_value = json_decode($value, true);
                                    foreach ($json_refs[$column] as $json_key => $json_ref) {
                                        $json_value = $this->extractJsonArrayValue($array_value, $json_key);
                                        if (isset($json_value) === true) {
                                            $isBase64 = false;
                                            if (is_string($json_value) === true && $this->validateBase64($json_value) === true) {
                                                $json_value = base64_decode($json_value);
                                                $isBase64 = true;
                                            }

                                            if ($this->getValueFromReference(
                                                $table,
                                                $column,
                                                $json_refs[$column][$json_key],
                                                $this->currentItem['parsed'],
                                                $json_value
                                            ) === true
                                            ) {
                                                if ($isBase64 === true) {
                                                    if (is_array($json_value) === true) {
                                                        $json_value = json_encode($json_value);
                                                    }

                                                    $json_value = base64_encode($json_value);
                                                }

                                                $this->updateJsonArrayValue($array_value, $json_key, $json_value);
                                            } else {
                                                $create_item = false;
                                                break;
                                            }
                                        }
                                    }

                                    $value = json_encode($array_value);
                                }

                                if ($create_item === false) {
                                    break;
                                }

                                $this->currentItem['parsed'][$column] = $value;
                            }

                            if ($create_item === true) {
                                if ($this->createItem($table) === false) {
                                    $this->setResultStatus(false);
                                    break;
                                }
                            } else {
                                $this->addResultInfo(
                                    sprintf(
                                        'Skipped item creation at least one reference not found: table => %s, item => %s',
                                        $table,
                                        $id
                                    )
                                );
                            }
                        }

                        if ($this->getResultStatus() === false) {
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

        if (isset($db)) {
            if ($this->getResultStatus() === true) {
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
                $columns_ref = $this->getOneColumnRefs($ref['table']);
                foreach ($ref['columns'] as $column_name) {
                    if (isset($array_value[$ref['table']][$column_name])) {
                        // Get value from crossed reference in current value
                        if (isset($this->crossed_refs[$ref['table']]) === true
                            && empty($this->crossed_refs[$ref['table']]['ref']) === false
                            && in_array($column_name, $this->crossed_refs[$ref['table']]['ref'])
                        ) {
                            $parent_table = $this->crossed_refs[$ref['table']]['parent_table'];
                            foreach ($this->crossed_refs[$ref['table']]['ref'] as $k => $f) {
                                $itemReference = $this->getItemReference(
                                    $parent_table,
                                    $this->crossed_refs[$parent_table]['value'][$k],
                                    $array_value[$ref['table']][$f]
                                );

                                if ($itemReference !== false) {
                                    $array_value[$ref['table']][$column_name] = $itemReference;
                                }
                            }
                        }

                        if ($columns_ref !== false) {
                            if (array_key_exists($column_name, $columns_ref)) {
                                $temp_value = $array_value[$ref['table']][$column_name];
                                $temp_value = (is_array($temp_value) ? json_encode($temp_value) : $temp_value);

                                // Get value from reference in current value
                                $ref_value = $this->getValueFromReference($ref['table'], $column_name, $columns_ref[$column_name], $array_value[$ref['table']], $temp_value);

                                if ($ref_value === true) {
                                    $array_value[$ref['table']][$column_name] = $temp_value;
                                }
                            }
                        }

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
                $result = $value;
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

                $where .= ' '.array_key_first($ref['join']).' = "'.reset($result).'"';
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
                    $ref[$key]['id'],
                    $ref[$key]['table'],
                    $where
                );

                $result = db_get_row_sql($sql);
            }
        }

        return $result;
    }


    /**
     * Eval autocreate Item.
     *
     * @param array  $ref    References.
     * @param string $value  Current value.
     * @param string $column Table column.
     *
     * @return boolean
     */
    private function evalAutocreateItem(array $ref, string $value='', string $column='')
    {
        if (isset($ref['autocreate_item']) === true) {
            $this->autocreateItem(
                $ref,
                $column,
                $value,
                $ref['autocreate_item']
            );
        } else {
            return false;
        }

        return true;
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
    private function autocreateItem(array $ref, string $field='', $ref_value, string $autocreate_key='')
    {
        $current_item = $this->currentItem['parsed'][$field];
        $current_item = json_decode($current_item, true);

        switch ($autocreate_key) {
            case 'service_module':
                $autocreate_globals = [
                    'service_module' => [
                        'id_agent' => $this->findPrdItem(
                            $this->tagente,
                            json_encode($current_item['tagente_modulo']['id_agente'])
                        ),
                        'interval' => 300,
                        'status'   => AGENT_MODULE_STATUS_NO_DATA,
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
                                'flag'              => 1,
                                'module_interval'   => $autocreate_globals[$autocreate_key]['interval'],
                                'prediction_module' => 2,
                                'id_modulo'         => MODULE_PREDICTION,
                                'id_tipo_modulo'    => MODULE_TYPE_GENERIC_DATA,
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
                                'timestamp'         => '0000-00-00 00:00:00',
                                'estado'            => $autocreate_globals[$autocreate_key]['status'],
                                'known_status'      => $autocreate_globals[$autocreate_key]['status'],
                                'id_agente'         => $autocreate_globals[$autocreate_key]['id_agent'],
                                'utimestamp'        => 0,
                                'status_changes'    => 0,
                                'last_status'       => $autocreate_globals[$autocreate_key]['status'],
                                'last_known_status' => $autocreate_globals[$autocreate_key]['status'],
                                'current_interval'  => 0,
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
                            json_encode($current_item['tagente_modulo']['id_agente'])
                        ),
                        'interval' => 300,
                        'status'   => AGENT_MODULE_STATUS_NO_DATA,
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
                                'flag'              => 1,
                                'module_interval'   => $autocreate_globals[$autocreate_key]['interval'],
                                'prediction_module' => 2,
                                'id_modulo'         => MODULE_PREDICTION,
                                'id_tipo_modulo'    => MODULE_TYPE_GENERIC_PROC,
                            ],
                        ],
                        [
                            'table'  => 'tagente_estado',
                            'id'     => ['id_agente_estado'],
                            'fields' => [
                                'id_agente_modulo'  => &$this->currentItem['last_autocreate'],
                                'datos'             => '',
                                'timestamp'         => '0000-00-00 00:00:00',
                                'estado'            => $autocreate_globals[$autocreate_key]['status'],
                                'known_status'      => $autocreate_globals[$autocreate_key]['status'],
                                'id_agente'         => $autocreate_globals[$autocreate_key]['id_agent'],
                                'utimestamp'        => 0,
                                'status_changes'    => 0,
                                'last_status'       => $autocreate_globals[$autocreate_key]['status'],
                                'last_known_status' => $autocreate_globals[$autocreate_key]['status'],
                                'current_interval'  => 0,
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
                            json_encode($current_item['tagente_modulo']['id_agente'])
                        ),
                        'interval' => 300,
                        'status'   => AGENT_MODULE_STATUS_NO_DATA,
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
                                'flag'              => 1,
                                'module_interval'   => $autocreate_globals[$autocreate_key]['interval'],
                                'prediction_module' => 2,
                                'id_modulo'         => MODULE_PREDICTION,
                                'id_tipo_modulo'    => MODULE_TYPE_GENERIC_DATA,
                                'min_critical'      => $this->currentItem['parsed']['sla_limit'],
                            ],
                        ],
                        [
                            'table'  => 'tagente_estado',
                            'id'     => ['id_agente_estado'],
                            'fields' => [
                                'id_agente_modulo'  => &$this->currentItem['last_autocreate'],
                                'datos'             => '',
                                'timestamp'         => '0000-00-00 00:00:00',
                                'estado'            => $autocreate_globals[$autocreate_key]['status'],
                                'known_status'      => $autocreate_globals[$autocreate_key]['status'],
                                'id_agente'         => $autocreate_globals[$autocreate_key]['id_agent'],
                                'utimestamp'        => 0,
                                'status_changes'    => 0,
                                'last_status'       => $autocreate_globals[$autocreate_key]['status'],
                                'last_known_status' => $autocreate_globals[$autocreate_key]['status'],
                                'current_interval'  => 0,
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
                            'fields' => ['nombre' => json_decode($ref_value, true)['tgrupo']['nombre']],
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
                            'fields' => ['name' => json_decode($ref_value, true)['tmodule_group']['name']],
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
                            'fields' => ['name' => json_decode($ref_value, true)['tconfig_os']['name']],
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
                            'fields' => ['name' => json_decode($ref_value, true)['tcategory']['name']],
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
                            'fields' => ['name' => json_decode($ref_value, true)['ttag']['name']],
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
     * @param string $table         Table.
     * @param array  $fields        Table fields.
     * @param string $old_value     Old value.
     * @param string $current_value Current value.
     *
     * @return void
     */
    private function addItemReference(string $table, array $fields, string $old_value, string $current_value)
    {
        if (count($fields) > 1) {
            $old_value     = explode('-', $old_value);
            $current_value = explode('-', $current_value);
        } else {
            $old_value     = [$old_value];
            $current_value = [$current_value];
        }

        if (!isset($this->itemsReferences[$table])) {
            $this->itemsReferences[$table] = [];
        }

        foreach ($fields as $k => $field) {
            if (!isset($this->itemsReferences[$table][$field])) {
                $this->itemsReferences[$table][$field] = [];
            }

            $this->itemsReferences[$table][$field][$old_value[$k]] = $current_value[$k];
        }
    }


    /**
     * Function to get an old ID reference.
     *
     * @param string $table     Table.
     * @param string $field     Table field.
     * @param string $old_value Old value.
     *
     * @return mixed
     */
    private function getItemReference(string $table, string $field, string $old_value)
    {
        if (isset($this->itemsReferences[$table][$field][$old_value]) === true) {
            return $this->itemsReferences[$table][$field][$old_value];
        }

        return false;
    }


    /**
     * Function to create item in database.
     *
     * @param string $table Table.
     *
     * @return mixed
     */
    private function createItem(string $table)
    {
        $id = $this->crossed_refs[$table]['value'];

        // Remove primary keys not references
        foreach ($id as $id_column) {
            if (in_array($id_column, $this->crossed_refs[$table]['ref']) === false
                && isset($this->columnRefs[$table][$id_column]) === false
            ) {
                unset($this->currentItem['parsed'][$id_column]);
            }
        }

        // Update current item crossed references.
        if (isset($this->crossed_refs[$table]) === true
            && empty($this->crossed_refs[$table]['ref']) === false
        ) {
            $parent_table = $this->crossed_refs[$table]['parent_table'];
            foreach ($this->crossed_refs[$table]['ref'] as $k => $f) {
                $itemReference = $this->getItemReference(
                    $parent_table,
                    $this->crossed_refs[$parent_table]['value'][$k],
                    $this->currentItem['parsed'][$f]
                );

                if ($itemReference === false) {
                    $this->addResultError(
                        sprintf(
                            'Failed when trying to create item (crossed references): table => %s, item => %s',
                            $table,
                            $this->currentItem['id']
                        )
                    );
                    return false;
                }

                $this->currentItem['parsed'][$f] = $itemReference;
            }
        }

        foreach ($this->currentItem['autocreate'] as $field => $values) {
            if (isset($values['pre_items']) === true) {
                foreach ($values['pre_items'] as $insert) {
                    // Run each INSERT and store each value in $this->currentItem['last_autocreate'] overwriting.
                    foreach ($insert['fields'] as $insert_f => $insert_v) {
                        if ($insert_v === false) {
                            $this->addResultError(
                                sprintf(
                                    'Failed when trying to autocreate unexisting item (dependent item not found in pre inserts): table => %s, item => %s, field => %s',
                                    $this->currentItem['table'],
                                    $this->currentItem['id'],
                                    $field
                                )
                            );
                            return false;
                        }
                    }

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

                    if ($insert_query === false || $last_autocreate === false) {
                        $this->addResultError(
                            sprintf(
                                'Failed when trying to autocreate unexisting item: table => %s, item => %s, field => %s',
                                $this->currentItem['table'],
                                $this->currentItem['id'],
                                $field
                            )
                        );
                        return false;
                    }

                    $last_autocreate = end($last_autocreate);

                    $this->addResultItem($insert['table'], $last_autocreate);

                    $this->currentItem['last_autocreate'] = implode('-', array_values($last_autocreate));
                }
            }

            $this->currentItem['parsed'][$field] = $this->findPrdItem(
                $values['ref'],
                $this->currentItem['parsed'][$field]
            );
        }

        // Create item itself with INSERT query and store its value in $this->currentItem['value'].
        $sql_fields = [];
        foreach ($this->currentItem['parsed'] as $f => $v) {
            $sql_fields['`'.$f.'`'] = $v;
        }

        $insert_query = db_process_sql_insert(
            $table,
            $sql_fields,
            false
        );

        $insert = db_get_all_rows_filter(
            $table,
            $sql_fields,
            $id
        );

        if ($insert_query === false || $insert === false) {
            $this->addResultError(
                sprintf(
                    'Failed when trying to create item: table => %s, item => %s',
                    $table,
                    $this->currentItem['id']
                )
            );
            return false;
        }

        $insert = end($insert);

        $this->addResultItem($table, $insert);

        $this->currentItem['value'] = implode('-', array_values($insert));

        if (isset($this->crossed_refs[$table]) === true) {
            $this->addItemReference(
                $table,
                $this->crossed_refs[$table]['value'],
                $this->currentItem['id'],
                $this->currentItem['value']
            );
        }

        foreach ($this->currentItem['autocreate'] as $field => $values) {
            if (isset($values['post_updates']) === true) {
                foreach ($values['post_updates'] as $update) {
                    // Run each UPDATE query.
                    foreach ($update['fields'] as $update_f => $update_v) {
                        if ($update_v === false) {
                            $this->addResultError(
                                sprintf(
                                    'Failed when trying to autocreate unexisting item (dependent item not found in post updates): table => %s, item => %s, field => %s',
                                    $this->currentItem['table'],
                                    $this->currentItem['id'],
                                    $field,
                                )
                            );
                            return false;
                        }
                    }

                    $update = db_process_sql_update(
                        $update['table'],
                        $update['fields'],
                        $update['conditions'],
                        'AND',
                        false
                    );

                    if ($update === false) {
                        $this->addResultError(
                            sprintf(
                                'Failed when trying to create item (post updates): table => %s, item => %s',
                                $table,
                                $this->currentItem['id']
                            )
                        );
                        return false;
                    }
                }
            }
        }

        return true;
    }


}
