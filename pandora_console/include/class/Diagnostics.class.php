<?php
/**
 * Extension to self monitor Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Diagnostics
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
     * Ajax controller page.
     *
     * @var string
     */
    public $ajaxController;


    /**
     * Constructor
     *
     * @param string $page Page.
     *
     * @return void
     */
    public function __construct(string $page)
    {
        global $config;

        // Check access.
        check_login();

        // Check Acl.
        if (!check_acl($config['id_user'], 0, 'PM')) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access diagnostic info'
            );

            if (is_ajax()) {
                echo json_encode(['error' => 'noaccess']);
            }

            include 'general/noaccess.php';
            exit;
        }

        $this->ajaxController = $page;
    }


    /**
     * Allowed methods to be called using AJAX request.
     *
     * @var array
     */
    public $AJAXMethods = [
        'getStatusInfo',
        'getPHPSetup',
        'getDatabaseSizeStats',
        'getDatabaseHealthStatus',
        'getDatabaseStatusInfo',
        'getSystemInfo',
        'getMySQLPerformanceMetrics',
        'getTablesFragmentation',
        'getPandoraFMSLogsDates',
        'getLicenceInformation',
        'getAttachmentFolder',
        'getInfoTagenteDatos',
        'getServerThreads',
        'datatablesDraw',
        'getChartAjax',
    ];


    /**
     * Checks if target method is available to be called using AJAX.
     *
     * @param string $method Target method.
     *
     * @return boolean True allowed, false not.
     */
    public function ajaxMethod(string $method):bool
    {
        return in_array($method, $this->AJAXMethods);
    }


    /**
     * Show view diagnostics.
     *
     * @return void
     */
    public function run()
    {
        global $config;

        $textPdf = '<a href="index.php?sec=gextensions&sec2='.$this->ajaxController.'">';
        $textPdf .= html_print_image(
            'images/pdf.png',
            true,
            ['title' => __('PDF Report')]
        );
        $textPdf .= '</a>';

        $textCsv = '<a href="index.php?sec=gextensions&sec2='.$this->ajaxController.'">';
        $textCsv .= html_print_image(
            'images/csv.png',
            true,
            ['title' => __('Csv Report')]
        );
        $textCsv .= '</a>';

        $buttonsHeader = [
            'diagnosticsPdf' => [
                'text'   => $textPdf,
                'active' => false,
            ],
            'diagnosticsCsv' => [
                'text'   => $textCsv,
                'active' => false,
            ],
        ];

        // Header.
        ui_print_page_header(
            __('Pandora FMS Diagnostic tool'),
            'images/gm_massive_operations.png',
            false,
            'diagnostic_tool_tab',
            true,
            $buttonsHeader,
            true
        );

        /*
         * Info status pandoraFms.
         * PHP setup.
         * Database size stats.
         * Database health status.
         * Database status info.
         * System Info.
         * MySQL Performance metrics.
         * Tables fragmentation in the Pandora FMS database.
         * Pandora FMS logs dates.
         * Pandora FMS Licence Information.
         * Status of the attachment folder.
         * Information from the tagente_datos table.
         * Pandora FMS server threads.
         */

        foreach ($this->AJAXMethods as $key => $method) {
            switch ($method) {
                case 'getStatusInfo':
                    $title = __('Info status pandoraFms');
                break;

                case 'getPHPSetup':
                    $title = __('PHP setup');
                break;

                case 'getDatabaseSizeStats':
                    $title = __('Database size stats');
                break;

                case 'getDatabaseHealthStatus':
                    $title = __('Database health status');
                break;

                case 'getDatabaseStatusInfo':
                    $title = __('Database status info');
                break;

                case 'getSystemInfo':
                    $title = __('System Info');
                break;

                case 'getMySQLPerformanceMetrics':
                    $title = __('MySQL Performance metrics');
                break;

                case 'getTablesFragmentation':
                    $title = __(
                        'Tables fragmentation in the Pandora FMS database'
                    );
                break;

                case 'getPandoraFMSLogsDates':
                    $title = __('Pandora FMS logs dates');
                break;

                case 'getLicenceInformation':
                    $title = __('Pandora FMS Licence Information');
                break;

                case 'getAttachmentFolder':
                    $title = __('Status of the attachment folder');
                break;

                case 'getInfoTagenteDatos':
                    $title = __('Information from the tagente_datos table');
                break;

                case 'getServerThreads':
                    $title = __('Pandora FMS server threads');
                break;

                default:
                    // Not possible.
                    $title = '';
                break;
            }

            if ($method !== 'datatablesDraw' && $method !== 'getChartAjax') {
                echo '<div style="margin-bottom: 30px;">';
                    $this->printData($method, $title);
                echo '</div>';
            }
        }

        /*
         * Agent id with name Master Server.
         */

        $agentIdMasterServer = $this->getAgentIdMasterServer();

        if ($agentIdMasterServer !== 0) {
            $agentMonitoring = [
                'chartAgentsUnknown'    => [
                    'title'      => __(
                        'Graph of the Agents Unknown module.'
                    ),
                    'nameModule' => 'Agents_Unknown',
                    'idAgent'    => $agentIdMasterServer,
                ],
                'chartDatabaseMain'     => [
                    'title'      => __(
                        'Graph of the Database Maintenance module.'
                    ),
                    'nameModule' => 'Database&#x20;Maintenance',
                    'idAgent'    => $agentIdMasterServer,
                ],
                'chartFreeDiskSpoolDir' => [
                    'title'      => __(
                        'Graph of the Free Disk Spool Dir module.'
                    ),
                    'nameModule' => 'FreeDisk_SpoolDir',
                    'idAgent'    => $agentIdMasterServer,
                ],
                'chartFreeRAM'          => [
                    'title'      => __('Graph of the Free RAM module.'),
                    'nameModule' => 'Free_RAM',
                    'idAgent'    => $agentIdMasterServer,
                ],
                'chartQueuedModules'    => [
                    'title'      => __(
                        'Graph of the Queued Modules module.'
                    ),
                    'nameModule' => 'Queued_Modules',
                    'idAgent'    => $agentIdMasterServer,
                ],
                'chartStatus'           => [
                    'title'      => __('Graph of the Status module.'),
                    'nameModule' => 'Status',
                    'idAgent'    => $agentIdMasterServer,
                ],
                'chartSystemLoadAVG'    => [
                    'title'      => __(
                        'Graph of the System Load AVG module.'
                    ),
                    'nameModule' => 'System_Load_AVG',
                    'idAgent'    => $agentIdMasterServer,
                ],
                'chartExecutionTime'    => [
                    'title'      => __(
                        'Graph of the Execution Time module.'
                    ),
                    'nameModule' => 'Execution_time',
                    'idAgent'    => $agentIdMasterServer,
                ],
            ];

            /*
             * Print table graps:
             * Graph of the Agents Unknown module.
             * Graph of the Database Maintenance module.
             * Graph of the Free Disk Spool Dir module.
             * Graph of the Free RAM module.
             * Graph of the Queued Modules module.
             * Graph of the Status module.
             * Graph of the System Load AVG module.
             * Graph of the Execution Time module.
             */

            echo '<div class="title-self-monitoring">';
            echo __('Graphs modules that represent the self-monitoring system');
            echo '</div>';
            echo '<div class="container-self-monitoring">';
            foreach ($agentMonitoring as $key => $value) {
                $this->printDataCharts($value);
            }

            echo '</div>';
        }

        echo '<div class="footer-self-monitoring">';
        echo $this->checkPandoraDB();
        echo '</div>';

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

        $result = [];
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
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
        }

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

        $variablesMsql = db_get_all_rows_sql('SHOW VARIABLES');
        $variablesMsql = array_reduce(
            $variablesMsql,
            function ($carry, $item) {
                $bytes = 1048576;
                $mega = 1024;
                switch ($item['Variable_name']) {
                    case 'sql_mode':
                        $name = __('Sql mode');
                        $value = ($item['Value']);
                        $status = (empty($item['Value']) === true) ? 1 : 0;
                        $message = __('Must be empty');
                    break;

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
                        $message = __('Min. Recommended Value').' 32M';
                    break;

                    case 'innodb_buffer_pool_size':
                        $name = __('InnoDB buffer pool size');
                        $value = ($item['Value'] / $mega);
                        $status = (($item['Value'] / $mega) >= 250) ? 1 : 0;
                        $message = __(
                            'It has to be 40% of the server memory not recommended to be greater or less'
                        );
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
                        $status = (($item['Value'] / $bytes) >= 32) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 32MB';
                    break;

                    case 'query_cache_limit':
                        $name = __('Query cache limit');
                        $value = ($item['Value'] / $bytes);
                        $status = (($item['Value'] / $bytes) >= 256) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 256K';
                    break;

                    case 'innodb_lock_wait_timeout':
                        $name = __('InnoDB lock wait timeout');
                        $value = $item['Value'];
                        $status = (($item['Value'] / $bytes) >= 90) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 90s';
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
                        $message = __('Min. Recommended Value').' 256K';
                    break;

                    case 'max_connections':
                        $name = __('Maximun connections');
                        $value = $item['Value'];
                        $status = (($item['Value'] / $bytes) >= 90) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 90';
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
                        $message = __('Min. Recommended Value').' 2k';
                    break;

                    case 'innodb_file_per_table':
                        $name = __('InnoDB file per table');
                        $value = $item['Value'];
                        $status = ($item['Value'] === 'ON') ? 1 : 0;
                        $message = __('Recommended ON');
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

        $averageTime = 0;
        if ($totalModuleIntervalTime !== false) {
            $averageTime = number_format(
                ((int) $totalModuleIntervalTime / (int) $totalNetworkModules),
                3
            );
        }

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

        $result = [
            'error' => false,
            'data'  => [
                'agentDataCount'  => [
                    'name'  => __('Total data in tagente_datos table'),
                    'value' => $agentDataCount,
                ],
                'agentDataStatus' => [
                    'name'   => __('Tagente_datos table status'),
                    'value'  => $taMsg,
                    'status' => $taStatus,
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

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return [];
        }

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
                [
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
     * Agent Id whit name is equal to Server Name.
     *
     * @return integer Id agent module.
     */
    public function getAgentIdMasterServer(): int
    {
        global $config;

        $serverName = db_get_value_sql(
            'SELECT `name`
            FROM tserver
            WHERE `name` IS NOT NULL
            AND `master` > 0
            ORDER BY `master` DESC'
        );
        $agentId = (int) db_get_value_sql(
            sprintf(
                'SELECT id_agente
                FROM tagente
                WHERE nombre = "%s"',
                $serverName
            )
        );

        if (isset($agentId) === false || is_numeric($agentId) === false) {
            $agentId = 0;
        }

        return $agentId;
    }


    /**
     * Graph.
     *
     * @param integer $id     Id agent.
     * @param string  $name   Name module.
     * @param boolean $image  Chart interactive or only image.
     * @param boolean $base64 Image or base64.
     *
     * @return string
     */
    public function getChart(
        int $id,
        string $name,
        bool $image=false,
        bool $base64=false
    ): string {
        global $config;

        include_once $config['homedir'].'/include/functions_graph.php';
        $data = modules_get_agentmodule_id($name, $id);
        $params = [
            'agent_module_id'    => $data['id_agente_modulo'],
            'period'             => SECONDS_1MONTH,
            'date'               => time(),
            'height'             => '200',
            'only_image'         => $image,
            'return_img_base_64' => $base64,
        ];

        return grafico_modulo_sparse($params);
    }


    /**
     * Check pandoradb installed.
     *
     * @return string
     */
    public function checkPandoraDB(): string
    {
        global $config;
        $result = '';

        if (isset($config['db_maintenance']) === false) {
            $result .= '(*) ';
            $result .= __(
                'Please check your Pandora Server setup and make sure that the database maintenance daemon is running.'
            );
            $result .= ' ';
            $result .= __(
                'It\' is very important to keep the database up-to-date to get the best performance and results in Pandora'
            );
        }

        return $result;
    }


    /**
     * Draw table.
     *
     * @param string $method Method.
     * @param string $title  Title.
     *
     * @return void
     */
    public function printData(string $method, string $title): void
    {
        global $config;

        if (is_ajax()) {
            // TODO: Call method.
            echo $method;
        } else {
            // Datatables list.
            try {
                $columns = [
                    [
                        'class' => 'datatables-td-title',
                        'text'  => 'name',
                    ],
                    [
                        'class' => 'datatables-td-max',
                        'text'  => 'value',
                    ],
                    'message',
                ];

                $columnNames = [
                    [
                        'style' => 'display:none;',
                        'text'  => '',
                    ],
                    [
                        'style' => 'display:none',
                        'text'  => '',
                    ],
                    [
                        'style' => 'display:none',
                        'text'  => '',
                    ],
                ];

                $tableId = $method.'_'.uniqid();
                // Load datatables user interface.
                ui_print_datatable(
                    [
                        'id'                  => $tableId,
                        'class'               => 'info_table caption_table',
                        'style'               => 'width: 100%',
                        'columns'             => $columns,
                        'column_names'        => $columnNames,
                        'ajax_data'           => [
                            'method' => 'datatablesDraw',
                            'name'   => $method,
                        ],
                        'ajax_url'            => $this->ajaxController,
                        'paging'              => 0,
                        'no_sortable_columns' => [-1],
                        'caption'             => $title,
                    ]
                );
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
    }


    /**
     * Prepare params for getCarts.
     *
     * @return void
     */
    public function getChartAjax():void
    {
        global $config;

        $params = json_decode(
            io_safe_output(get_parameter('params', '')),
            true
        );

        $return = '';
        if (isset($params['idAgent']) === true
            && empty($params['idAgent']) === false
            && isset($params['nameModule'])
            && empty($params['nameModule']) === false
        ) {
            $return = $this->getChart(
                $params['idAgent'],
                $params['nameModule']
            );
        }

        exit($return);
    }


    /**
     * Paint table with charts.
     *
     * @param array $params Info charts.
     *
     * @return void
     */
    public function printDataCharts(array $params): void
    {
        global $config;

        if (!$params) {
            $params = get_parameter('params');
        }

        if (is_ajax()) {
            // TODO: Call method.
            echo $method;
        } else {
            // Datatables list.
            try {
                $id = str_replace(
                    ' ',
                    '',
                    io_safe_output($params['nameModule'])
                );
                echo '<div id="'.$id.'" class="element-self-monitoring"></div>';
                $settings = [
                    'type'     => 'POST',
                    'dataType' => 'html',
                    'url'      => ui_get_full_url(
                        'ajax.php',
                        false,
                        false,
                        false
                    ),
                    'data'     => [
                        'page'   => $this->ajaxController,
                        'method' => 'getChartAjax',
                        'params' => json_encode($params),
                    ],
                ];

                ?>
                    <script type="text/javascript">
                        ajaxRequest(
                            '<?php echo $id; ?>',
                            <?php echo json_encode($settings); ?>
                        );
                    </script>
                <?php
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
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


    /**
     * Transforms a json object into a Datatables format.
     *
     * @return void
     */
    public function datatablesDraw()
    {
        $method = get_parameter('name', '');
        if (method_exists($this, $method) === true) {
            $data = json_decode($this->{$method}(), true);
        }

        if (isset($data) === true && is_array($data) === true) {
            $items = $data['data'];
            $dataReduce = array_reduce(
                array_keys($data['data']),
                function ($carry, $key) use ($items) {
                    // Transforms array of arrays $data into an array
                    // of objects, making a post-process of certain fields.
                    if (isset($items[$key]['status']) === true) {
                        $acumValue = $items[$key]['value'];
                        if ($items[$key]['status'] === 1) {
                            $items[$key]['value'] = html_print_image(
                                'images/exito.png',
                                true,
                                [
                                    'title' => __('Succesfuly'),
                                    'style' => 'width:15px;',
                                ]
                            );
                        } else {
                            $items[$key]['value'] = html_print_image(
                                'images/error_1.png',
                                true,
                                [
                                    'title' => __('Error'),
                                    'style' => 'width:15px;',
                                ]
                            );
                        }

                        $items[$key]['value'] .= ' '.$acumValue;
                    }

                    // FIX for customer key.
                    if ($key === 'customerKey') {
                        $spanValue = '<span>'.$items[$key]['value'].'</span>';
                        $items[$key]['value'] = $spanValue;
                    }

                    if (isset($items[$key]['message']) === false) {
                        $items[$key]['message'] = '';
                    }

                    $carry[] = (object) $items[$key];
                    return $carry;
                }
            );
        }

        // Datatables format: RecordsTotal && recordsfiltered.
        echo json_encode(
            [
                'data'            => $dataReduce,
                'recordsTotal'    => count($dataReduce),
                'recordsFiltered' => count($dataReduce),
            ]
        );
    }


    /**
     * Transforms a json object into a Datatables format.
     *
     * @return void
     */
    public function exportPDF()
    {
        // TODO: TO BE CONTINUED.
        $pdf = new PDFTranslator();

        // Set font from font defined in report.
        $pdf->custom_font = $report['custom_font'];

        $product_name = io_safe_output(get_product_name());
        $pdf->setMetadata(
            __('Diagnostics Info'),
            $product_name.' Enteprise',
            $product_name,
            __('Automated %s report for user defined report', $product_name)
        );

        $filename = '';

        if ($filename !== '') {
            $pdfObject->writePDFfile($filename);
        } else {
            $pdfObject->showPDF();
        }

    }


}
