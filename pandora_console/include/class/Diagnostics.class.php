<?php
/**
 * Extension to self monitor Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Supervisor
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
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

global $config;

require_once $config['homedir'].'/include/functions_db.php';
require_once $config['homedir'].'/include/functions_io.php';

/**
 * Base class Diagnostics.
 */
class Diagnostics
{


    /**
     * Constructor.
     *
     * @return class This object
     */
    public function __construct()
    {
        echo 'hola';
        return $this;
    }


    /**
     * Show view diagnostics.
     *
     * @return void
     */
    public function run()
    {
        global $config;

        /*
         * Info status pandoraFms.
         */

        $statusInfo = $this->getStatusInfo();

        /*
         * Print table in this case info.
         */

        $this->printTable($statusInfo);

        /*
         * PHP setup.
         */

        $phpSetup = $this->getPHPSetup();

        /*
         * Print table in this case PHP SETUP.
         */

        $this->printTable($phpSetup);

        /*
         * Database size stats.
         */

        $dataBaseSizeStats = $this->getDatabaseSizeStats();

        /*
         * Print table in this case Database size stats.
         */

        $this->printTable($dataBaseSizeStats);
    }


    /**
     * Info status pandoraFms.
     *
     * @return string
     */
    public function getStatusInfo(): string
    {
        global $config;
        global $build_version;
        global $pandora_version;

        $sql = sprintf(
            "SELECT `key`, `value`
            FROM `tupdate_settings`
            WHERE `key` = '%s'
            OR `key` = '%s'
            OR `key` = '%s'",
            'current_update',
            'customer_key',
            'updating_code_path'
        );

        $values_key = db_get_all_rows_sql($sql);
        $values_key = array_reduce(
            $values_key,
            function ($carry, $item) {
                if ($item['key'] === 'customer_key') {
                    $customer = substr($item['value'], 0, 5);
                    $customer .= '...';
                    $customer .= substr($item['value'], -5);
                    $item['value'] = $customer;
                }

                $carry[$item['key']] = $item['value'];
                return $carry;
            }
        );

        $result = [
            'error' => false,
            'data'  => [
                'buildVersion'  => [
                    'name'  => __('Pandora FMS Build'),
                    'value' => $build_version,
                ],
                'version'       => [
                    'name'  => __('Pandora FMS Version'),
                    'value' => $pandora_version,
                ],
                'mr'            => [
                    'name'  => __('Minor Release'),
                    'value' => $config['MR'],
                ],
                'homeDir'       => [
                    'name'  => __('Homedir'),
                    'value' => $config['homedir'],
                ],
                'homeUrl'       => [
                    'name'  => __('HomeUrl'),
                    'value' => $config['homeurl'],
                ],
                'isEnterprise'  => [
                    'name'  => __('Enterprise installed'),
                    'value' => (enterprise_installed()) ? __('true') : __('false'),
                ],
                'customerKey'   => [
                    'name'  => __('Update Key'),
                    'value' => $values_key['customer_key'],
                ],
                'updatingCode'  => [
                    'name'  => __('Updating code path'),
                    'value' => $values_key['updating_code_path'],
                ],
                'currentUpdate' => [
                    'name'  => __('Current Update #'),
                    'value' => $values_key['current_update'],
                ],

            ],
        ];

        return json_encode($result);
    }


    /**
     * PHP Status.
     *
     * @return string
     */
    public function getPHPSetup(): string
    {
        global $config;

        $result = [
            'error' => false,
            'data'  => [
                'phpVersion'       => [
                    'name'  => __('PHP Version'),
                    'value' => phpversion(),
                ],
                'maxExecutionTime' => [
                    'name'  => __('PHP Max execution time'),
                    'value' => ini_get('max_execution_time'),
                ],
                'maxInputTime'     => [
                    'name'  => __('PHP Max input time'),
                    'value' => ini_get('max_input_time'),
                ],
                'memoryLimit'      => [
                    'name'  => __('PHP Memory limit'),
                    'value' => ini_get('memory_limit'),
                ],
                'sessionLifetime'  => [
                    'name'  => __('Session cookie lifetime'),
                    'value' => ini_get('session.cookie_lifetime'),
                ],
            ],
        ];

        return json_encode($result);
    }


    /**
     * Database size stats.
     *
     * @return string
     */
    public function getDatabaseSizeStats(): string
    {
        global $config;

        $countAgents = db_get_value_sql('SELECT COUNT(*) FROM tagente');
        $countModules = db_get_value_sql('SELECT COUNT(*) FROM tagente_modulo');
        $countGroups = db_get_value_sql('SELECT COUNT(*) FROM tgrupo');
        $countModuleData = db_get_value_sql(
            'SELECT COUNT(*) FROM tagente_datos'
        );
        $countAgentAccess = db_get_value_sql(
            'SELECT COUNT(*) FROM tagent_access'
        );
        $countEvents = db_get_value_sql('SELECT COUNT(*) FROM tevento');

        if (enterprise_installed() === true) {
            $countTraps = db_get_value_sql('SELECT COUNT(*) FROM ttrap');
        }

        $countUsers = db_get_value_sql('SELECT COUNT(*) FROM tusuario');
        $countSessions = db_get_value_sql('SELECT COUNT(*) FROM tsesion');

        $result = [
            'error' => false,
            'data'  => [
                'countAgents'      => [
                    'name'  => __('Total agentsy'),
                    'value' => $countAgents,
                ],
                'countModules'     => [
                    'name'  => __('Total modules'),
                    'value' => $countModules,
                ],
                'countGroups'      => [
                    'name'  => __('Total groups'),
                    'value' => $countGroups,
                ],
                'countModuleData'  => [
                    'name'  => __('Total module data records'),
                    'value' => $countModuleData,
                ],
                'countAgentAccess' => [
                    'name'  => __('Total agent access record'),
                    'value' => $countAgentAccess,
                ],
                'countEvents'      => [
                    'name'  => __('Total events'),
                    'value' => $countEvents,
                ],
                'countTraps'       => [
                    'name'  => __('Total traps'),
                    'value' => $countTraps,
                ],
                'countUsers'       => [
                    'name'  => __('Total users'),
                    'value' => $countUsers,
                ],
                'countSessions'    => [
                    'name'  => __('Total sessions'),
                    'value' => $countSessions,
                ],
            ],
        ];

        return json_encode($result);
    }


    /**
     * Paint table.
     *
     * @param string $statusInfo Json width status info.
     *
     * @return void
     */
    public function printTable(string $statusInfo): void
    {
        global $config;

        hd($statusInfo);
    }


}
