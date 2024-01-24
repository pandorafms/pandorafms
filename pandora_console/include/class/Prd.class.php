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

        $this->columnRefs = [
            'tlayout_data' => [
                'id_agent'         => [
                    'table'  => 'tagente',
                    'id'     => 'id_agente',
                    'column' => 'nombre',
                ],
                'id_agente_modulo' => [
                    'table'  => 'tagente_modulo',
                    'id'     => 'id_agente_modulo',
                    'column' => 'nombre',
                    'join'   => [
                        'id_agente' => [
                            'table'  => 'tagente',
                            'id'     => 'id_agente',
                            'column' => 'nombre',
                        ],
                    ],
                ],
            ],
        ];

        $this->jsonRefs = [
            'twidget_dashboard' => [
                'options' => [
                    'agent'  => [
                        'array'  => false,
                        'table'  => 'tagente',
                        'id'     => 'id_agente',
                        'column' => 'nombre',
                    ],
                    'module' => [
                        'array'  => false,
                        'table'  => 'tagente_modulo',
                        'id'     => 'id_agente_modulo',
                        'column' => 'nombre',
                        'join'   => [
                            'id_agente' => [
                                'table'  => 'tagente',
                                'id'     => 'id_agente',
                                'column' => 'nombre',
                            ],
                        ],
                    ],
                ],
            ],
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
            $result .= 'name="'.$name.'"'.LINE_BREAK.LINE_BREAK;

            $result .= '['.$prd_data['items']['table'].']'.LINE_BREAK.LINE_BREAK;

            $sql = sprintf(
                'SELECT * FROM %s WHERE %s = %s',
                $prd_data['items']['table'],
                reset($prd_data['items']['value']),
                $id,
            );

            $row = db_get_row_sql($sql);
            $primary_key = $row[reset($prd_data['items']['value'])];
            foreach ($row as $column => $value) {
                $result .= $column.'['.$primary_key.']="'.$value.'"'.LINE_BREAK;
            }

            $result .= LINE_BREAK;

            $result .= $this->recursiveExport($prd_data['items']['data'], $id);
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
    private function recursiveExport($data, $id): string
    {
        $result = '';

        foreach ($data as $key => $element) {
            $result .= '['.$element['table'].']'.LINE_BREAK.LINE_BREAK;
            $sql = sprintf(
                'SELECT * FROM %s WHERE %s = %s',
                $element['table'],
                reset($element['ref']),
                $id,
            );

            $rows = db_get_all_rows_sql($sql);
            foreach ($rows as $row) {
                if (count($element['value']) > 1) {
                    $primary_key = '';
                    foreach ($element['value'] as $value) {
                        $primary_key .= $row[$value].'-';
                    }

                    $primary_key = substr($primary_key, 0, -1);
                    hd($primary_key, true);
                } else {
                    $primary_key = $row[reset($element['value'])];
                }

                foreach ($row as $column => $value) {
                    $result .= $column.'['.$primary_key.']="'.$value.'"'.LINE_BREAK;
                }

                $result .= LINE_BREAK;
            }

            if (isset($element['data']) === true) {
                $result .= $this->recursiveExport($element['data'], $primary_key);
            }
        }

        return $result;
    }


}
