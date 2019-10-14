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

        /*
         * Database health status.
         */

        $databaseHealthStatus = $this->getDatabaseHealthStatus();

        /*
         * Print table in this case Database health status.
         */

        $this->printTable($databaseHealthStatus);

        /*
         * Database health status.
         */

        $getDatabaseStatusInfo = $this->getDatabaseStatusInfo();

        /*
         * Print table in this case Database status info.
         */

        $this->printTable($getDatabaseStatusInfo);

        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            /*
             * System Info.
             */

            $getSystemInfo = $this->getSystemInfo();

            /*
             * Print table in this case System Info.
             */

            $this->printTable($getSystemInfo);
        }

        /*
         * System Info.
         */

        $getMySQLPerformanceMetrics = $this->getMySQLPerformanceMetrics();

        /*
         * Print table in this case System Info.
         */

        $this->printTable($getMySQLPerformanceMetrics);

        /*
         * Tables fragmentation in the Pandora FMS database.
         */

        $getTablesFragmentation = $this->getTablesFragmentation();

        /*
         * Print table in this case Tables fragmentation in
         * the Pandora FMS database.
         */

        $this->printTable($getTablesFragmentation);

        /*
         * Tables fragmentation in the Pandora FMS database.
         */

        $getPandoraFMSLogsDates = $this->getPandoraFMSLogsDates();

        /*
         * Print table in this case Tables fragmentation in
         * the Pandora FMS database.
         */

        $this->printTable($getPandoraFMSLogsDates);

        /*
         * Pandora FMS Licence Information.
         */

        $getLicenceInformation = $this->getLicenceInformation();

        /*
         * Print table in this case Pandora FMS Licence Information.
         */

        $this->printTable($getLicenceInformation);

        /*
         * Status of the attachment folder.
         */

        $getAttachmentFolder = $this->getAttachmentFolder();

        /*
         * Print table in this case Status of the attachment folder.
         */

        $this->printTable($getAttachmentFolder);

        /*
         * Information from the tagente_datos table.
         */

        $getInfoTagenteDatos = $this->getInfoTagenteDatos();

        /*
         * Print table in this case Information from the tagente_datos table.
         */

        $this->printTable($getInfoTagenteDatos);

        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            /*
             * Pandora FMS server threads.
             */

            $getServerThreads = $this->getServerThreads();

            /*
             * Print table in this case Pandora FMS server threads.
             */

            $this->printTable($getServerThreads);
        }
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
     * Database health status.
     *
     * @return string
     */
    public function getDatabaseHealthStatus(): string
    {
        global $config;

        // Count agents unknowns.
        $sqlUnknownAgents = 'SELECT COUNT( DISTINCT tagente.id_agente)
        FROM tagente_estado, tagente, tagente_modulo
        WHERE tagente.disabled = 0
        AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
        AND tagente_modulo.disabled = 0
        AND tagente_estado.id_agente = tagente.id_agente
        AND tagente_estado.estado = 3';
        $unknownAgents = db_get_sql($sqlUnknownAgents);

        // Count modules not initialize.
        $sqlNotInitAgents = 'SELECT COUNT(tagente_estado.estado)
            FROM tagente_estado
            WHERE tagente_estado.estado = 4';
        $notInitAgents = db_get_sql($sqlNotInitAgents);

        $dateDbMantenaince = $config['db_maintance'];

        $currentTime = time();

        $pandoraDbLastRun = __('Pandora DB has never been executed');
        if ($dateDbMantenaince !== false) {
            $difference = ($currentTime - $dateDbMantenaince);
            $pandoraDbLastRun = human_time_comparation($difference);
            $pandoraDbLastRun .= ' '.__('Ago');
        }

        $result = [
            'error' => false,
            'data'  => [
                'unknownAgents'    => [
                    'name'  => __('Total unknown agents'),
                    'value' => $unknownAgents,
                ],
                'notInitAgents'    => [
                    'name'  => __('Total not-init modules'),
                    'value' => $notInitAgents,
                ],
                'pandoraDbLastRun' => [
                    'name'  => __('PandoraDB Last run'),
                    'value' => $pandoraDbLastRun,
                ],
            ],
        ];

        return json_encode($result);
    }


    /**
     * Database status info.
     *
     * @return string
     */
    public function getDatabaseStatusInfo(): string
    {
        global $config;

        // Size BBDD.
        $dbSizeSql = db_get_value_sql(
            'SELECT ROUND(SUM(data_length+index_length)/1024/1024,3)
            FROM information_schema.TABLES'
        );

        // Add unit size.
        $dbSize = $dbSizeSql.' M';

        $result = [
            'error' => false,
            'data'  => [
                'dbSchemeFirstVersion' => [
                    'name'  => __('DB Schema Version (first installed)'),
                    'value' => $config['db_scheme_first_version'],
                ],
                'dbSchemeVersion'      => [
                    'name'  => __('DB Schema Version (actual)'),
                    'value' => $config['db_scheme_version'],
                ],
                'dbSchemeBuild'        => [
                    'name'  => __('DB Schema Build'),
                    'value' => $config['db_scheme_build'],
                ],
                'dbSize'               => [
                    'name'  => __('DB Size'),
                    'value' => $dbSize,
                ],
            ],
        ];

        return json_encode($result);
    }


    /**
     * Database status info.
     *
     * @return string
     */
    public function getSystemInfo(): string
    {
        global $config;

        $cpuModelName = 'cat /proc/cpuinfo | grep "model name" | tail -1 | cut -f 2 -d ":"';
        $cpuProcessor = 'cat /proc/cpuinfo | grep "processor" | wc -l';
        $ramMemTotal = 'cat /proc/meminfo | grep "MemTotal"';

        $result = [
            'error' => false,
            'data'  => [
                'cpuInfo' => [
                    'name'  => __('CPU'),
                    'value' => exec($cpuModelName).' x '.exec($cpuProcessor),
                ],
                'ramInfo' => [
                    'name'  => __('RAM'),
                    'value' => exec($ramMemTotal),
                ],
            ],
        ];

        return json_encode($result);
    }


    /**
     * MySQL Performance metrics.
     *
     * @return string
     */
    public function getMySQLPerformanceMetrics(): string
    {
        global $config;

        $variablesMsql = db_get_all_rows_sql('SHOW variables');
        $variablesMsql = array_reduce(
            $variablesMsql,
            function ($carry, $item) {
                $bytes = 1048576;
                $mega = 1024;
                switch ($item['Variable_name']) {
                    case 'innodb_log_file_size':
                        $name = __('InnoDB log file size');
                        $value = ($item['Value'] / $bytes);
                        $status = (($item['Value'] / $bytes) >= 64) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 64M';
                    break;

                    case 'innodb_log_buffer_size':
                        $name = __('InnoDB log buffer size');
                        $value = ($item['Value'] / $bytes);
                        $status = (($item['Value'] / $bytes) >= 16) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 16M';
                    break;

                    case 'innodb_flush_log_at_trx_commit':
                        $name = __('InnoDB flush log at trx-commit');
                        $value = $item['Value'];
                        $status = (($item['Value'] / $bytes) >= 0) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 0';
                    break;

                    case 'max_allowed_packet':
                        $name = __('Maximun allowed packet');
                        $value = ($item['Value'] / $bytes);
                        $status = (($item['Value'] / $bytes) >= 32) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 32';
                    break;

                    case 'innodb_buffer_pool_size':
                        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                            $min = shell_exec(
                                "cat /proc/meminfo | grep -i total | head -1 | awk '{print $(NF-1)*0.4/1024}'"
                            );
                        }

                        $name = __('InnoDB buffer pool size');
                        $value = ($item['Value'] / $mega);
                        $status = (($item['Value'] / $bytes) >= $min) ? 1 : 0;
                        $message = __('Min. Recommended Value').' '.$min;
                    break;

                    case 'sort_buffer_size':
                        $name = __('Sort buffer size');
                        $value = number_format(($item['Value'] / $mega), 2);
                        $status = (($item['Value'] / $bytes) >= 32) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 32';
                    break;

                    case 'join_buffer_size':
                        $name = __('Join buffer size');
                        $value = ($item['Value'] / $mega);
                        $status = (($item['Value'] / $bytes) >= 265) ? 1 : 0;
                        $message = __('Min. Recommended Value 265');
                    break;

                    case 'query_cache_type':
                        $name = __('Query cache type');
                        $value = $item['Value'];
                        $status = ($item['Value'] === 'ON') ? 1 : 0;
                        $message = __('Recommended ON');
                    break;

                    case 'query_cache_size':
                        $name = __('Query cache size');
                        $value = ($item['Value'] / $bytes);
                        $status = (($item['Value'] / $bytes) >= 24) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 24';
                    break;

                    case 'query_cache_limit':
                        $name = __('Query cache limit');
                        $value = ($item['Value'] / $bytes);
                        $status = (($item['Value'] / $bytes) >= 2) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 2';
                    break;

                    case 'innodb_lock_wait_timeout':
                        $name = __('InnoDB lock wait timeout');
                        $value = $item['Value'];
                        $status = (($item['Value'] / $bytes) >= 120) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 120';
                    break;

                    case 'thread_cache_size':
                        $name = __('Thread cache size');
                        $value = $item['Value'];
                        $status = (($item['Value'] / $bytes) >= 8) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 8';
                    break;

                    case 'thread_stack':
                        $name = __('Thread stack');
                        $value = ($item['Value'] / $bytes);
                        $status = (($item['Value'] / $bytes) >= 256) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 256';
                    break;

                    case 'max_connections':
                        $name = __('Maximun connections');
                        $value = $item['Value'];
                        $status = (($item['Value'] / $bytes) >= 150) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 150';
                    break;

                    case 'key_buffer_size':
                        $name = __('Key buffer size');
                        $value = ($item['Value'] / $bytes);
                        $status = (($item['Value'] / $bytes) >= 256) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 256';
                    break;

                    case 'read_buffer_size':
                        $name = __('Read buffer size');
                        $value = ($item['Value'] / $bytes);
                        $status = (($item['Value'] / $bytes) >= 32) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 32';
                    break;

                    case 'read_rnd_buffer_size':
                        $name = __('Read rnd-buffer size');
                        $value = ($item['Value'] / $bytes);
                        $status = (($item['Value'] / $bytes) >= 32) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 32';
                    break;

                    case 'query_cache_min_res_unit':
                        $name = __('Query cache min-res-unit');
                        $value = ($item['Value'] / $bytes);
                        $status = (($item['Value'] / $bytes) >= 2) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 2';
                    break;

                    case 'innodb_file_per_table':
                        $name = __('InnoDB file per table');
                        $value = $item['Value'];
                        $status = (($item['Value'] / $bytes) >= 1) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 1';
                    break;

                    default:
                        $name = '';
                        $value = 0;
                    break;
                }

                if (empty($name) !== true) {
                    $carry[$item['Variable_name']] = [
                        'name'    => $name,
                        'value'   => $value,
                        'status'  => $status,
                        'message' => $message,
                    ];
                }

                return $carry;
            },
            []
        );

        $result = [
            'error' => false,
            'data'  => $variablesMsql,
        ];

        return json_encode($result);
    }


    /**
     * Tables fragmentation in the Pandora FMS database.
     *
     * @return string
     */
    public function getTablesFragmentation(): string
    {
        global $config;

        // Estimated fragmentation percentage as maximum.
        $tFragmentationMax = 10;

        // Extract the fragmentation value.
        $tFragmentationValue = db_get_sql(
            sprintf(
                "SELECT (data_free/(index_length+data_length)) as frag_ratio
                FROM information_schema.tables
                WHERE  DATA_FREE > 0
                AND table_name='tagente_datos'
                AND table_schema='%s'",
                $config['dbname']
            )
        );

        // Check if it meets the fragmentation value.
        $status_tables_frag = '';
        if ($tFragmentationValue > $tFragmentationMax) {
            $tFragmentationMsg = __(
                'Table fragmentation is higher than recommended. They should be defragmented.'
            );
            $tFragmentationStatus = 0;
        } else {
            $tFragmentationMsg = __('Table fragmentation is correct.');
            $tFragmentationStatus = 1;
        }

        $result = [
            'error' => false,
            'data'  => [
                'tablesFragmentationMax'    => [
                    'name'  => __(
                        'Tables fragmentation (maximum recommended value)'
                    ),
                    'value' => $tFragmentationMax.'%',
                ],
                'tablesFragmentationValue'  => [
                    'name'  => __('Tables fragmentation (current value)'),
                    'value' => number_format($tFragmentationValue, 2).'%',
                ],
                'tablesFragmentationStatus' => [
                    'name'   => __('Table fragmentation status'),
                    'value'  => $status_tables_frag,
                    'status' => $tFragmentationStatus,
                ],
            ],
        ];

        return json_encode($result);
    }


    /**
     * Pandora FMS logs dates.
     *
     * @return string
     */
    public function getPandoraFMSLogsDates(): string
    {
        global $config;

        $unit = 'M';

        $pathServerLogs = 'var/log/pandora/pandora_server.log';
        $servers = $this->getLogInfo($pathServerLogs);

        $pathErrLogs = 'var/log/pandora/pandora_server.error';
        $errors = $this->getLogInfo($pathErrLogs);

        $pathConsoleLogs = $config['homedir'].'/pandora_console.log';
        $console = $this->getLogInfo($pathConsoleLogs);

        $result = [
            'error' => false,
            'data'  => [
                'sizeServerLog'    => [
                    'name'  => __('Size server logs (current value)'),
                    'value' => $servers['value'].' '.$unit,
                ],
                'statusServerLog'  => [
                    'name'   => __('Status server logs'),
                    'value'  => $servers['message'],
                    'status' => $servers['status'],
                ],
                'sizeErrorLog'     => [
                    'name'  => __('Size error logs (current value)'),
                    'value' => $errors['value'].' '.$unit,
                ],
                'statusErrorLog'   => [
                    'name'   => __('Status error logs'),
                    'value'  => $errors['message'],
                    'status' => $errors['status'],
                ],
                'sizeConsoleLog'   => [
                    'name'  => __('Size console logs (current value)'),
                    'value' => $console['value'].' '.$unit,
                ],
                'statusConsoleLog' => [
                    'name'   => __('Status console logs'),
                    'value'  => $console['message'],
                    'status' => $console['status'],
                ],
            ],
        ];

        return json_encode($result);
    }


    /**
     * Pandora FMS Licence Information.
     *
     * @return string
     */
    public function getLicenceInformation(): string
    {
        global $config;

        // Extract customer key.
        $sql = sprintf(
            "SELECT `value`
            FROM `tupdate_settings`
            WHERE `key` = '%s'",
            'customer_key'
        );
        $customerKey = db_get_value_sql($sql);

        // Extract Info license.
        $license = enterprise_hook('license_get_info');

        // Agent Capacity.
        $agentCount = db_get_value_sql('SELECT count(*) FROM tagente');
        $agentsCapacity = __('License capacity is less than 90 percent');
        $agentsCapacitySt = 1;
        if ($agentCount > ($license['limit'] * 90 / 100)) {
            $agentsCapacity = __('License capacity exceeds 90 percent');
            $agentsCapacitySt = 0;
        }

        // Modules average.
        $modulesCount = db_get_value_sql('SELECT count(*) FROM tagente_modulo');
        $average = ($modulesCount / $agentCount);
        $averageMsg = __(
            'The average of modules per agent is more than 40. You can have performance problems'
        );
        $averageSt = 0;
        if ($average <= 40) {
            $averageMsg = __(
                'The average of modules per agent is less than 40'
            );
            $averageSt = 1;
        }

        // Modules Networks average.
        $totalNetworkModules = db_get_value_sql(
            'SELECT count(*)
            FROM tagente_modulo
            WHERE id_tipo_modulo
            BETWEEN 6 AND 18'
        );
        $totalModuleIntervalTime = db_get_value_sql(
            'SELECT SUM(module_interval)
            FROM tagente_modulo
            WHERE id_tipo_modulo
            BETWEEN 6 AND 18'
        );
        $averageTime = number_format(
            ((int) $totalModuleIntervalTime / (int) $totalNetworkModules),
            3
        );
        $moduleNetworkmsg = __(
            sprintf(
                'The system is not overloaded (average time %d)',
                $average_time
            )
        );
        $moduleNetworkst = 1;
        if ($average_time === 0) {
            $moduleNetworkmsg = __('The system has no load');
            $moduleNetworkst = 0;
        } else if ($averageTime < 180) {
            $moduleNetworkmsg = __(
                sprintf(
                    'The system is overloaded (average time %d) and a very fine configuration is required',
                    $average_time
                )
            );
            $moduleNetworkst = 0;
        }

        $result = [
            'error' => false,
            'data'  => [
                'customerKey'             => [
                    'name'  => __('Customer key'),
                    'value' => $customerKey,
                ],
                'customerExpires'         => [
                    'name'  => __('Support expires'),
                    'value' => $license['expiry_date'],
                ],
                'customerLimit'           => [
                    'name'  => __('Platform Limit'),
                    'value' => $license['limit'].' '.__('Agents'),
                ],
                'customerPfCount'         => [
                    'name'  => __('Current Platform Count'),
                    'value' => $license['count'].' '.__('Agents'),
                ],
                'customerPfCountEnabled'  => [
                    'name'  => __('Current Platform Count (enabled: items)'),
                    'value' => $license['count_enabled'].' '.__('Agents'),
                ],
                'customerPfCountDisabled' => [
                    'name'  => __('Current Platform Count (disabled: items)'),
                    'value' => $license['count_disabled'].' '.__('Agents'),
                ],
                'customerMode'            => [
                    'name'  => __('License Mode'),
                    'value' => $license['license_mode'],
                ],
                'customerNMS'             => [
                    'name'  => __('Network Management System'),
                    'value' => ($license['nms'] > 0) ? __('On') : __('Off'),
                ],
                'customerSatellite'       => [
                    'name'  => __('Satellite'),
                    'value' => ($license['dhpm'] > 0) ? __('On') : __('Off'),
                ],
                'customerLicenseTo'       => [
                    'name'  => __('Licensed to'),
                    'value' => $license['licensed_to'],
                ],
                'customerCapacity'        => [
                    'name'   => __('Status of agents capacity'),
                    'value'  => $agentsCapacity,
                    'status' => $agentsCapacitySt,
                ],
                'customerAverage'         => [
                    'name'   => __('Status of average modules per agent'),
                    'value'  => $averageMsg,
                    'status' => $averageSt,
                ],

                'customerAverageNetwork'  => [
                    'name'   => __('Interval average of the network modules'),
                    'value'  => $moduleNetworkmsg,
                    'status' => $moduleNetworkst,
                ],
            ],
        ];

        return json_encode($result);
    }


    /**
     * Status of the attachment folder.
     *
     * @return string
     */
    public function getAttachmentFolder(): string
    {
        global $config;

        // Count files in attachment.
        $attachmentFiles = count(
            glob(
                $config['homedir'].'/attachment/{*.*}',
                GLOB_BRACE
            )
        );

        // Check status attachment.
        $attachmentMsg = __(
            'The attached folder contains more than 700 files.'
        );
        $attachmentSt = 0;
        if ($attachmentFiles <= 700) {
            $attachmentMsg = __(
                'The attached folder contains less than 700 files.'
            );
            $attachmentSt = 1;
        }

        $result = [
            'error' => false,
            'data'  => [
                'attachFiles'  => [
                    'name'  => __('Total files in the attached folder'),
                    'value' => $attachmentFiles,
                ],
                'attachStatus' => [
                    'name'   => __('Status of the attachment folder'),
                    'value'  => $attachmentMsg,
                    'status' => $attachmentSt,
                ],
            ],
        ];

        return json_encode($result);
    }


    /**
     * Information from the tagente_datos table.
     *
     * @return string
     */
    public function getInfoTagenteDatos(): string
    {
        global $config;

        $agentDataCount = db_get_value_sql(
            'SELECT COUNT(*)
            FROM tagente_datos'
        );

        $taMsg = __(
            'The tagente_datos table contains too much data. A historical database is recommended.'
        );
        $taStatus = 0;
        if ($agentDataCount <= 3000000) {
            $taMsg = __(
                'The tagente_datos table contains an acceptable amount of data.'
            );
            $taStatus = 1;
        }

        /*
            $times = db_get_all_rows_sql('SELECT datos FROM tagente_datos WHERE id_agente_modulo = 29 ORDER BY utimestamp DESC LIMIT 2');
            hd($times);
            if ($times[0]['datos'] > ($times[1]['datos'] * 1.2)) {
            __('The execution time could be degrading. For a more extensive information of this data consult the  Execution Time graph');
            } else {
            __('The execution time is correct. For more information about this data, check the Execution Time graph');
            }
        */

        $result = [
            'error' => false,
            'data'  => [
                'agentDataCount'     => [
                    'name'  => __('Total data in tagente_datos table'),
                    'value' => $agentDataCount,
                ],
                'agentDataStatus'    => [
                    'name'   => __('Tagente_datos table status'),
                    'value'  => $taMsg,
                    'status' => $taStatus,
                ],
                'agentDataExecution' => [
                    'name'  => __('Execution time degradation when executing a count'),
                    'value' => 1,
                ],
            ],
        ];

        return json_encode($result);
    }


    /**
     * Pandora FMS server threads.
     *
     * @return string
     */
    public function getServerThreads(): string
    {
        global $config;

        $totalServerThreads = shell_exec(
            'ps -T aux | grep pandora_server | grep -v grep | wc -l'
        );
        $percentageThreadsRam = shell_exec(
            "ps axo pmem,cmd | grep pandora_server | awk '{sum+=$1} END {print sum}'"
        );
        $percentageThreadsCpu = shell_exec(
            "ps axo pcpu,cmd | grep pandora_server | awk '{sum+=$1} END {print sum}'"
        );

        $result = [
            'error' => false,
            'data'  => [
                'totalServerThreads'   => [
                    'name'  => __('Total server threads'),
                    'value' => $totalServerThreads,
                ],
                'percentageThreadsRam' => [
                    'name'  => __('Percentage of threads used by the RAM'),
                    'value' => $percentageThreadsRam.' %',
                ],
                'percentageThreadsCpu' => [
                    'name'  => __('Percentage of threads used by the CPU'),
                    'value' => $percentageThreadsCpu.' %',
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


    /**
     * Private function for Info size path.
     *
     * @param string $path Route file.
     *
     * @return array With values size file and message and status.
     */
    private function getLogInfo(string $path): array
    {
        global $config;

        // Vars.
        $mega = 1048576;
        $tenMega = 10485760;

        $result = [
            'value'   => 0,
            'message' => '',
            'status'  => 0,
        ];

        if (is_file($path) === true) {
            $fileSize = filesize($path);
            $sizeServerLog = number_format($fileSize);
            $sizeServerLog = (0 + str_replace(',', '', $sizeServerLog));

            $value = number_format(($fileSize / $mega), 3);
            $message = __('You have more than 10 MB of logs');
            $status = 0;
            if ($sizeServerLog <= $tenMega) {
                $message = __('You have less than 10 MB of logs');
                $status = 1;
            }

            $result = [
                'value'   => $value,
                'message' => $message,
                'status'  => $status,
            ];
        }

        return $result;
    }


}
