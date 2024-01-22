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
                    'value' => 'id',
                    'show'  => 'name',
                ],
                'data'  => [
                    [
                        'table' => 'tlayout',
                        'ref'   => 'id',
                    ],
                    [
                        'table' => 'tlayout_data',
                        'ref'   => 'id_layout',
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
     * Export prd.
     *
     * @return array
     */
    public function exportPrd(string $type, $value, $name) :array
    {
        $result = [];

        $prd_data = $this->getOnePrdData($type);
        if (empty($prd_data) === false) {
            $result['prd_data'] = [
                'type' => $type,
                'name' => $name,
            ];

            foreach ($prd_data['data'] as $key => $element) {
                $sql = sprintf(
                    'SELECT * FROM %s WHERE %s = %s',
                    $element['table'],
                    $element['ref'],
                    $value,
                );

                $test = db_get_all_rows_sql($sql);

                // $result[$element['table']]
                // hd($test, true);
            }
        }

        return $result;

    }


}
