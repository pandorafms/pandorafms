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
     * Some error message.
     *
     * @var string
     */
    private $message;


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
        $tgrupo = [
            'table'           => 'tgrupo',
            'id'              => 'id_grupo',
            'columns'         => ['nombre'],
            'autocreate_item' => 'agent_groups',
        ];

        $ttipo_modulo = [
            'table'   => 'ttipo_modulo',
            'id'      => 'id_tipo',
            'columns' => ['nombre'],
        ];

        $tmodule_group = [
            'table'           => 'tmodule_group',
            'id'              => 'id_mg',
            'columns'         => ['name'],
            'autocreate_item' => 'module_groups',
        ];

        $tconfig_os = [
            'table'           => 'tconfig_os',
            'id'              => 'id_os',
            'columns'         => ['name'],
            'autocreate_item' => 'operating_systems',
        ];

        $tcategory = [
            'table'           => 'tcategory',
            'id'              => 'id',
            'columns'         => ['name'],
            'autocreate_item' => 'categories',
        ];

        $ttag = [
            'table'           => 'ttag',
            'id'              => 'id_tag',
            'columns'         => ['name'],
            'autocreate_item' => 'tags',
        ];

        $tagente = [
            'table'   => 'tagente',
            'id'      => 'id_agente',
            'columns' => ['nombre'],
        ];

        $tagente_modulo = [
            'table'   => 'tagente_modulo',
            'id'      => 'id_agente_modulo',
            'columns' => ['nombre'],
            'join'    => ['id_agente' => $tagente],
        ];

        $tplugin = [
            'table'   => 'tplugin',
            'id'      => 'id',
            'columns' => ['name'],
        ];

        $tmodule_inventory = [
            'table'   => 'tmodule_inventory',
            'id'      => 'id_module_inventory',
            'columns' => ['name'],
            'join'    => ['id_os' => $tconfig_os],
        ];

        $tpolicies = [
            'table'   => 'tpolicies',
            'id'      => 'id',
            'columns' => ['name'],
        ];

        $tpolicy_modules = [
            'table'   => 'tpolicy_modules',
            'id'      => 'id',
            'columns' => ['name'],
            'join'    => ['id_policy' => $tpolicies],
        ];

        $talert_actions = [
            'table'   => 'talert_actions',
            'id'      => 'id',
            'columns' => ['name'],
        ];

        $talert_templates = [
            'table'   => 'talert_templates',
            'id'      => 'id',
            'columns' => ['name'],
        ];

        $tcollection = [
            'table'   => 'tcollection',
            'id'      => 'id',
            'columns' => ['short_name'],
        ];

        $tgraph = [
            'table'   => 'tgraph',
            'id'      => 'id_graph',
            'columns' => ['name'],
        ];

        $tservice = [
            'table'   => 'tservice',
            'id'      => 'id',
            'columns' => ['name'],
        ];

        $tlayout = [
            'table'   => 'tlayout',
            'id'      => 'id',
            'columns' => ['name'],
        ];

        $tlayout_data = [
            'table'   => 'tlayout_data',
            'id'      => 'id',
            'columns' => [
                'pos_x',
                'pos_y',
                'height',
                'width',
                'type',
            ],
            'join'    => ['id_layout' => $tlayout],
        ];

        $treport_custom_sql = [
            'table'   => 'treport_custom_sql',
            'id'      => 'id',
            'columns' => ['name'],
        ];

        $tserver_export = [
            'table'   => 'tserver_export',
            'id'      => 'id',
            'columns' => ['name'],
        ];

        $trecon_task = [
            'table'   => 'trecon_task',
            'id'      => 'id_rt',
            'columns' => [
                'name',
                'type',
            ],
        ];

        $tmap = [
            'table'   => 'tmap',
            'id'      => 'id',
            'columns' => ['name'],
        ];

        $titem = [
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

        $tgis_map_connection = [
            'table'   => 'tgis_map_connection',
            'id'      => 'id_tmap_connection',
            'columns' => ['conection_name'],
        ];

        $tserver = [
            'table'   => 'tserver',
            'id'      => 'id_server',
            'columns' => [
                'name',
                'server_type',
            ],
        ];

        $twidget = [
            'table'   => 'twidget',
            'id'      => 'id',
            'columns' => ['unique_name'],
        ];

        $treport = [
            'table'   => 'treport',
            'id'      => 'id_report',
            'columns' => ['name'],
        ];

        // Define references between tables fields.
        $this->columnRefs = [
            'tlayout'                      => [
                'id_group' => ['ref' => $tgrupo],
            ],
            'tlayout_data'                 => [
                'id_agente_modulo' => ['ref' => $tagente_modulo],
                'id_agent'         => ['ref' => $tagente],
                'id_layout_linked' => ['ref' => $tlayout],
                'parent_item'      => ['ref' => $tlayout_data],
                'id_group'         => ['ref' => $tgrupo],
                'id_custom_graph'  => ['ref' => $tgraph],
                'element_group'    => ['ref' => $tgrupo],
            ],
            'treport'                      => [
                'id_group'      => ['ref' => $tgrupo],
                'id_group_edit' => ['ref' => $tgrupo],
            ],
            'treport_content'              => [
                'id_gs'                 => ['ref' => $tgraph],
                'id_agent_module'       => ['ref' => $tagente_modulo],
                'id_agent'              => ['ref' => $tagente],
                'treport_custom_sql_id' => ['ref' => $treport_custom_sql],
                'id_group'              => ['ref' => $tgrupo],
                'id_module_group'       => ['ref' => $tmodule_group],
                'ncm_agents'            => ['ref' => ($tagente + ['array' => true])],
            ],
            'treport_content_item'         => [
                'id_agent_module' => ['ref' => $tagente_modulo],
            ],
            'treport_content_sla_combined' => [
                'id_agent_module' => ['ref' => $tagente_modulo],
            ],
            'tpolicies'                    => [
                'id_group' => ['ref' => $tgrupo],
            ],
            'tpolicy_agents'               => [
                'id_agent' => ['ref' => $tagente],
            ],
            'tpolicy_alerts'               => [
                'id_policy_module'  => ['ref' => $tpolicy_modules],
                'id_alert_template' => ['ref' => $talert_templates],
            ],
            'tpolicy_alerts_actions'       => [
                'id_alert_action' => ['ref' => $talert_actions],
            ],
            'tpolicy_collections'          => [
                'id_collection' => ['ref' => $tcollection],
            ],
            'tpolicy_group_agents'         => [
                'id_agent' => ['ref' => $tagente],
            ],
            'tpolicy_groups'               => [
                'id_group' => ['ref' => $tgrupo],
            ],
            'tpolicy_modules'              => [
                'id_tipo_modulo'  => ['ref' => $ttipo_modulo],
                'id_module_group' => ['ref' => $tmodule_group],
                'id_export'       => ['ref' => $tserver_export],
                'id_plugin'       => ['ref' => $tplugin],
                'id_category'     => ['ref' => $tcategory],
            ],
            'ttag_policy_module'           => [
                'id_tag' => ['ref' => $ttag],
            ],
            'tpolicy_modules_synth'        => [
                'id_agent_module_source' => ['ref' => $tagente_modulo],
            ],
            'tpolicy_modules_inventory'    => [
                'id_module_inventory' => ['ref' => $tmodule_inventory],
            ],
            'tservice'                     => [
                'id_group'                       => ['ref' => $tgrupo],
                'id_agent_module'                => ['ref' => ($tagente_modulo + ['autocreate_item' => 'service_module'])],
                'sla_id_module'                  => ['ref' => ($tagente_modulo + ['autocreate_item' => 'service_sla_module'])],
                'sla_value_id_module'            => ['ref' => ($tagente_modulo + ['autocreate_item' => 'service_sla_value_module'])],
                'id_template_alert_warning'      => ['ref' => $talert_templates],
                'id_template_alert_critical'     => ['ref' => $talert_templates],
                'id_template_alert_unknown'      => ['ref' => $talert_templates],
                'id_template_alert_critical_sla' => ['ref' => $talert_templates],
            ],
            'tservice_element'             => [
                'id_agente_modulo' => ['ref' => $tagente_modulo],
                'id_agent'         => ['ref' => $tagente],
                'id_service_child' => ['ref' => $tservice],
            ],
            'tmap'                         => [
                'id_group'     => ['ref' => $tgrupo],
                'source_data'  => [
                    'conditional_refs' => [
                        [
                            'when' => ['source' => '0'],
                            'ref'  => ($tgrupo + ['csv' => true, 'csv_separator' => ',']),
                        ],
                        [
                            'when' => ['source' => '1'],
                            'ref'  => $trecon_task,
                        ],
                    ],
                ],
                'id_group_map' => ['ref' => $tgrupo],
            ],
            'titem'                        => [
                'source_data' => [
                    'conditional_refs' => [
                        [
                            'when' => ['type' => '0'],
                            'ref'  => $tagente,
                        ],
                        [
                            'when' => ['type' => '1'],
                            'ref'  => $tagente_modulo,
                        ],
                    ],
                ],
            ],
            'trel_item'                    => [
                'id_parent'             => ['ref' => $titem],
                'id_child'              => ['ref' => $titem],
                'id_parent_source_data' => [
                    'conditional_refs' => [
                        [
                            'when' => ['parent_type' => '0'],
                            'ref'  => $tagente,
                        ],
                        [
                            'when' => ['parent_type' => '1'],
                            'ref'  => $tagente_modulo,
                        ],
                    ],
                ],
                'id_child_source_data'  => [
                    'conditional_refs' => [
                        [
                            'when' => ['child_type' => '0'],
                            'ref'  => $tagente,
                        ],
                        [
                            'when' => ['child_type' => '1'],
                            'ref'  => $tagente_modulo,
                        ],
                    ],
                ],
            ],
            'tgis_map'                     => [
                'group_id' => ['ref' => $tgrupo],
            ],
            'tgis_map_layer'               => [
                'tgrupo_id_grupo' => ['ref' => $tgrupo],
            ],
            'tgis_map_layer_groups'        => [
                'group_id' => ['ref' => $tgrupo],
                'agent_id' => ['ref' => $tagente],
            ],
            'tgis_map_layer_has_tagente'   => [
                'tagente_id_agente' => ['ref' => $tagente],
            ],
            'tgis_map_has_tgis_map_con'    => [
                'tgis_map_con_id_tmap_con' => ['ref' => $tgis_map_connection],
            ],
            'tgraph'                       => [
                'id_group' => ['ref' => $tgrupo],
            ],
            'tgraph_source'                => [
                'id_server'       => ['ref' => $tserver],
                'id_agent_module' => ['ref' => $tagente_modulo],
            ],
            'tdashboard'                   => [
                'id_group' => ['ref' => $tgrupo],
            ],
            'twidget_dashboard'            => [
                'id_widget' => ['ref' => $twidget],
            ],
        ];

        // Define references between tables fields with JSON format.
        $this->jsonRefs = [
            'tservice_element'  => [
                'rules' => [
                    'group' => ['ref' => $tgrupo],
                ],
            ],
            'titem'             => [
                'style' => [
                    'id_group'   => ['ref' => $tgrupo],
                    'networkmap' => ['ref' => $tmap],
                    'id_agent'   => ['ref' => $tagente],
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
                                'ref'  => $tgrupo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'wux_transaction'],
                                    ],
                                ],
                                'ref'  => $tgrupo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'inventory'],
                                    ],
                                ],
                                'ref'  => $tgrupo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'service_view'],
                                    ],
                                ],
                                'ref'  => $tgrupo,
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
                                'ref'  => $tagente,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'wux_transaction'],
                                    ],
                                ],
                                'ref'  => $tagente,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'AvgSumMaxMinModule'],
                                    ],
                                ],
                                'ref'  => $tagente,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'BasicChart'],
                                    ],
                                ],
                                'ref'  => $tagente,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'module_icon'],
                                    ],
                                ],
                                'ref'  => $tagente,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'inventory'],
                                    ],
                                ],
                                'ref'  => $tagente,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'graph_module_histogram'],
                                    ],
                                ],
                                'ref'  => $tagente,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'module_table_value'],
                                    ],
                                ],
                                'ref'  => $tagente,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'module_status'],
                                    ],
                                ],
                                'ref'  => $tagente,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'module_value'],
                                    ],
                                ],
                                'ref'  => $tagente,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'sla_percent'],
                                    ],
                                ],
                                'ref'  => $tagente,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'wux_transaction_stats'],
                                    ],
                                ],
                                'ref'  => $tagente,
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
                                'ref'  => $tagente_modulo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'AvgSumMaxMinModule'],
                                    ],
                                ],
                                'ref'  => $tagente_modulo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'BasicChart'],
                                    ],
                                ],
                                'ref'  => $tagente_modulo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'module_icon'],
                                    ],
                                ],
                                'ref'  => $tagente_modulo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'graph_module_histogram'],
                                    ],
                                ],
                                'ref'  => $tagente_modulo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'module_table_value'],
                                    ],
                                ],
                                'ref'  => $tagente_modulo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'module_status'],
                                    ],
                                ],
                                'ref'  => $tagente_modulo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'module_value'],
                                    ],
                                ],
                                'ref'  => $tagente_modulo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'sla_percent'],
                                    ],
                                ],
                                'ref'  => $tagente_modulo,
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
                                'ref'  => $tagente_modulo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'wux_transaction_stats'],
                                    ],
                                ],
                                'ref'  => $tagente_modulo,
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
                                'ref'  => $tgrupo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'service_level'],
                                    ],
                                ],
                                'ref'  => $tgrupo,
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
                                'ref'  => $tmodule_group,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'service_level'],
                                    ],
                                ],
                                'ref'  => $tmodule_group,
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
                                'ref'  => ($tagente + ['csv' => true, 'csv_separator' => ',']),
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'service_level'],
                                    ],
                                ],
                                'ref'  => ($tagente + ['csv' => true, 'csv_separator' => ',']),
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
                                'ref'  => ($tgrupo + ['array' => true]),
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'heatmap'],
                                    ],
                                ],
                                'ref'  => ($tgrupo + ['array' => true]),
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
                                'ref'  => ($tagente + ['csv' => true, 'csv_separator' => ',']),
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
                                'ref'  => ($tagente_modulo + ['array' => true, 'values_as_keys' => true]),
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
                                'ref'  => ($tagente + ['csv' => true, 'csv_separator' => ',']),
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
                                'ref'  => ($tagente_modulo + ['array' => true, 'values_as_keys' => true]),
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
                                'ref'  => $treport,
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
                                'ref'  => ($tagente + ['csv' => true, 'csv_separator' => ',']),
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
                                'ref'  => ($tagente_modulo + ['array' => true, 'values_as_keys' => true]),
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
                                'ref'  => $tgraph,
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
                                'ref'  => ($tgrupo + ['array' => true]),
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'groups_status'],
                                    ],
                                ],
                                'ref'  => $tgrupo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'groups_status_map'],
                                    ],
                                ],
                                'ref'  => ($tgrupo + ['csv' => true, 'csv_separator' => ',']),
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'system_group_status'],
                                    ],
                                ],
                                'ref'  => ($tgrupo + ['array' => true]),
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'events_list'],
                                    ],
                                ],
                                'ref'  => ($tgrupo + ['array' => true]),
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'tactical'],
                                    ],
                                ],
                                'ref'  => ($tgrupo + ['array' => true]),
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'top_n_events_by_group'],
                                    ],
                                ],
                                'ref'  => ($tgrupo + ['array' => true]),
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'top_n_events_by_module'],
                                    ],
                                ],
                                'ref'  => ($tgrupo + ['array' => true]),
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'tree_view'],
                                    ],
                                ],
                                'ref'  => $tgrupo,
                            ],
                            [
                                'when' => [
                                    'id_widget' => [
                                        'table' => 'twidget',
                                        'id'    => 'id',
                                        'when'  => ['unique_name' => 'alerts_fired'],
                                    ],
                                ],
                                'ref'  => $tgrupo,
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
                                'ref'  => ($tlayout + ['array' => true]),
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
                                'ref'  => $tgrupo,
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
                                'ref'  => ($tagente + ['csv' => true, 'csv_separator' => ',']),
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
                                'ref'  => ($tagente_modulo + ['array' => true, 'values_as_keys' => true]),
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
                                'ref'  => ($ttag + ['array' => true]),
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
                                'ref'  => $tmap,
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
                                'ref'  => $tgrupo,
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
                                'ref'  => $tservice,
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
                                'ref'  => $tlayout,
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

        $this->message = '';
    }


    /**
     * Generates a JSON error.
     *
     * @param string $msg Error message.
     *
     * @return void
     */
    public function error(string $msg)
    {
        echo json_encode(
            ['error' => $msg]
        );
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
     *
     * @return void
     */
    private function getTablesPrdData($prd_data, &$result=[])
    {
        if (isset($prd_data['items']) === true) {
            $result[$prd_data['items']['table']] = '';
            if ($prd_data['items']['data']) {
                $this->getTablesPrdData($prd_data['items']['data'], $result);
            }
        } else {
            foreach ($prd_data as $key => $value) {
                $result[$value['table']] = '';
                if ($value['data']) {
                    $this->getTablesPrdData($value['data'], $result);
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
                if (isset($columns_ref[$column]) === true
                    && empty($value) === false
                ) {
                    // The column is inside column refs.
                    if (isset($columns_ref[$column]['ref']) === true) {
                        // Column refs.
                        $sql_column = sprintf(
                            'SELECT %s FROM %s WHERE %s=%s',
                            implode(
                                ',',
                                $columns_ref[$column]['ref']['columns']
                            ),
                            $columns_ref[$column]['ref']['table'],
                            $columns_ref[$column]['ref']['id'],
                            $value
                        );

                        $value = db_get_row_sql($sql_column);
                        $new_array = [];
                        $new_array[$columns_ref[$column]['ref']['table']] = io_safe_output($value);
                        $value = json_encode($new_array);
                        $value = addslashes($value);
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
                                    $sql_condition = sprintf(
                                        'SELECT %s FROM %s WHERE %s=%s',
                                        implode(
                                            ',',
                                            $condition['ref']['columns']
                                        ),
                                        $condition['ref']['table'],
                                        $condition['ref']['id'],
                                        $value
                                    );

                                    $value = db_get_row_sql($sql_condition);
                                    $new_array = [];
                                    $new_array[$condition['ref']['table']] = io_safe_output($value);
                                    $value = json_encode($new_array);
                                    $value = addslashes($value);
                                    break;
                                }
                            }
                        }
                    }

                    $result .= $column.'['.$primary_key.']="'.$value.'"'.LINE_BREAK;
                } else {
                    $result .= $column.'['.$primary_key.']="'.io_safe_output($value).'"'.LINE_BREAK;
                }
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
                    if (isset($columns_ref[$column]) === true
                        && empty($value) === false
                    ) {
                        // The column is inside column refs.
                        if (isset($columns_ref[$column]['ref']) === true) {
                            // Column ref.
                            if (isset($columns_ref[$column]['ref']['join']) === true) {
                                $sql_column = sprintf(
                                    'SELECT %s, %s FROM %s WHERE %s=%s',
                                    implode(
                                        ',',
                                        $columns_ref[$column]['ref']['columns']
                                    ),
                                    array_key_first($columns_ref[$column]['ref']['join']),
                                    $columns_ref[$column]['ref']['table'],
                                    $columns_ref[$column]['ref']['id'],
                                    $value
                                );

                                $test = io_safe_output(db_get_row_sql($sql_column));
                                $join = reset($columns_ref[$column]['ref']['join']);

                                $sql_join = sprintf(
                                    'SELECT %s FROM %s WHERE %s=%s',
                                    implode(
                                        ',',
                                        $join['columns']
                                    ),
                                    $join['table'],
                                    $join['id'],
                                    $test[array_key_first($columns_ref[$column]['ref']['join'])]
                                );

                                $test2 = io_safe_output(db_get_row_sql($sql_join));
                                $test[array_key_first($columns_ref[$column]['ref']['join'])] = io_safe_output($test2);

                                $value = [$columns_ref[$column]['ref']['table'] => io_safe_output($test)];
                                $value = json_encode($value);
                                $value = addslashes($value);
                            } else {
                                $sql_column = sprintf(
                                    'SELECT %s FROM %s WHERE %s=%s',
                                    implode(
                                        ',',
                                        $columns_ref[$column]['ref']['columns']
                                    ),
                                    $columns_ref[$column]['ref']['table'],
                                    $columns_ref[$column]['ref']['id'],
                                    $value
                                );

                                $value = db_get_row_sql($sql_column);
                                $new_array = [];
                                $new_array[$columns_ref[$column]['ref']['table']] = io_safe_output($value);
                                $value = json_encode($new_array);
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
                                        $sql_condition = sprintf(
                                            'SELECT %s FROM %s WHERE %s=%s',
                                            implode(
                                                ',',
                                                $condition['ref']['columns']
                                            ),
                                            $condition['ref']['table'],
                                            $condition['ref']['id'],
                                            $value
                                        );

                                        $value = db_get_row_sql($sql_condition);
                                        $new_array = [];
                                        $new_array[$condition['ref']['table']] = io_safe_output($value);
                                        $value = json_encode($new_array);
                                        $value = addslashes($value);
                                    }
                                }
                            }
                        }

                        $result .= $column.'['.$primary_key.']="'.$value.'"'.LINE_BREAK;
                    } else if (isset($json_ref[$column]) === true) {
                        $json_array = json_decode($value, true);
                        foreach ($json_ref[$column] as $json_key => $json_value) {
                            if (isset($json_array[$json_key]) === true) {
                                $sql_json = sprintf(
                                    'SELECT %s FROM %s WHERE %s=%s',
                                    implode(
                                        ',',
                                        $json_value['ref']['columns']
                                    ),
                                    $json_value['ref']['table'],
                                    $json_value['ref']['id'],
                                    $json_array[$json_key]
                                );

                                $value = db_get_row_sql($sql_json);
                                $new_array = [];
                                $new_array[$json_value['ref']['columns']] = io_safe_output($value);
                                $json_array[$json_key] = $new_array;
                            }
                        }

                        $value = json_encode($json_array);
                        $value = addslashes($value);

                        $result .= $column.'['.$primary_key.']="'.$value.'"'.LINE_BREAK;
                    } else {
                        $result .= $column.'['.$primary_key.']="'.io_safe_output($value).'"'.LINE_BREAK;
                    }
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
     * @param mixed $data Data.
     * @param mixed $id   Id value for search.
     *
     * @return string
     */
    private function recursiveJoin($data, $value): string
    {
        // if (empty($data['join']) === false) {
        // $sql_column = sprintf(
        // 'SELECT %s, %s FROM %s WHERE %s=%s',
        // implode(
        // ',',
        // $data['columns']
        // ),
        // array_key_first($data['join']),
        // $data['table'],
        // $data['id'],
        // $value
        // );
        // $test = io_safe_output(db_get_row_sql($sql_column));
        // $join = reset($data['join']);
        // $sql_join = sprintf(
        // 'SELECT %s FROM %s WHERE %s=%s',
        // implode(
        // ',',
        // $join['columns']
        // ),
        // $join['table'],
        // $join['id'],
        // $test[array_key_first($data['join'])]
        // );
        // }
    }


    /**
     * Converts a resource into a string.
     *
     * @return void
     */
    public function importPrd(array $data_file)
    {
        if (empty($data_file['prd_data']) === false) {
            $type = $data_file['prd_data']['type'];
            $name = io_safe_input($data_file['prd_data']['name']);
            unset($data_file['prd_data']);

            $prd_data = $this->getOnePrdData($type);
            if ($prd_data !== false) {
                // Check that the tables are the same.
                $tables = [];
                $this->getTablesPrdData($prd_data, $tables);
                $diff = array_diff_key(array_flip(array_keys($data_file)), $tables);
                if (empty($diff) === false) {
                    // Error. Hay alguna tabla que no existe en prd_data y hay que borrarla.
                    foreach ($diff as $key => $value) {
                        unset($data_file[$key]);
                    }
                }

                foreach ($data_file as $table => $internal_array) {
                    // hd('Tabla -> '.$table);
                    // hd(db_get_column_type($table));
                    // hd(db_get_($table));
                    foreach ($internal_array as $column => $value) {
                        // hd($column);
                        // hd($value);
                    }
                }
            } else {
                // Error. El tipo no existe.
            }
        } else {
            // Error, no se encuentra prd_data
        }

        // hd($data_file);
    }


}
