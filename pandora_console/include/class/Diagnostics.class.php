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

global $config;

require_once $config['homedir'].'/include/functions_db.php';
require_once $config['homedir'].'/include/functions_io.php';
require_once $config['homedir'].'/godmode/wizards/Wizard.main.php';

/**
 * Base class Diagnostics.
 */
class Diagnostics extends Wizard
{

    const INNODB_FLUSH_LOG_AT_TRX_COMMIT = 2;

    /**
     * Ajax controller page.
     *
     * @var string
     */
    public $ajaxController;

    /**
     * Print Html or Pdf view.
     *
     * @var boolean
     */
    public $pdf;


    /**
     * Constructor.
     *
     * @param string  $page Page.
     * @param boolean $pdf  PDF View.
     */
    public function __construct(
        string $page='tools/diagnostics',
        bool $pdf=false
    ) {
        global $config;

        // Check access.
        check_login();

        $this->url = ui_get_full_url(
            'index.php?sec=gextensions&sec2=tools/diagnostics'
        );

        $this->ajaxController = $page;
        $this->pdf = $pdf;
        $this->product_name = io_safe_output(get_product_name());
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
        'getShowEngine',
        'datatablesDraw',
        'getChartAjax',
        'formFeedback',
        'createdScheduleFeedbackTask',
        'getSystemDate',
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

        if ($this->pdf === true) {
            $this->exportPDF();
            exit;
        }

        ui_require_css_file('diagnostics');

        $pdf_url = $this->url.'&pdf=true';
        $pdf_img = html_print_image(
            'images/file-pdf.svg',
            true,
            [
                'title'   => __('Export to PDF'),
                'class'   => 'main_menu_icon invert_filter',
                'onclick' => 'blockResubmit($(this))',
            ]
        );
        $header_buttons = [
            'csv' => [
                'active' => false,
                'text'   => '<a target="_new" href="'.$pdf_url.'">'.$pdf_img.'</a>',
            ],
        ];

        // Header.
        ui_print_standard_header(
            __('Admin tools'),
            'images/gm_massive_operations.png',
            false,
            '',
            true,
            $header_buttons,
            [
                [
                    'link'  => '',
                    'label' => __('%s Diagnostic tool', $this->product_name),
                ],
            ]
        );

        // Print all Methods Diagnostic Info.
        echo $this->printMethodsDiagnostigsInfo();

        // Print all charts Monitoring.
        echo $this->printCharts();

        echo '<div class="footer-self-monitoring">';
        echo $this->checkPandoraDB();
        echo '</div>';
    }


    /**
     * Print Methods:
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
     *
     * @return string Html.
     */
    public function printMethodsDiagnostigsInfo():string
    {
        $infoMethods = [
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
            'getSystemDate',
        ];

        if ($this->pdf === true) {
            $infoMethods[] = 'getShowEngine';
        }

        $return = '';

        foreach ($infoMethods as $key => $method) {
            switch ($method) {
                case 'getStatusInfo':
                    $title = __('Info status %s', $this->product_name);
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
                        'Tables fragmentation in the %s database',
                        $this->product_name
                    );
                break;

                case 'getPandoraFMSLogsDates':
                    $title = __('%s logs dates', $this->product_name);
                break;

                case 'getLicenceInformation':
                    $title = __('%s Licence Information', $this->product_name);
                break;

                case 'getAttachmentFolder':
                    $title = __('Status of the attachment folder');
                break;

                case 'getInfoTagenteDatos':
                    $title = __('Information from the tagente_datos table');
                break;

                case 'getServerThreads':
                    $title = __('%s server threads', $this->product_name);
                break;

                case 'getShowEngine':
                    $title = __('SQL show engine innodb status');
                break;

                case 'getSystemDate':
                    $title = __('Date system');
                break;

                default:
                    // Not possible.
                    $title = '';
                break;
            }

            $return .= '<div class="mrgn_btn_30px">';
            $return .= $this->printData($method, $title);
            $return .= '</div>';
        }

        if ($this->pdf === true) {
            return $return;
        } else {
            return false;
        }
    }


    /**
     * Print table graps:
     * Graph of the Agents Unknown module.
     * Graph of the Database Maintenance module.
     * Graph of the Free Disk Spool Dir module.
     * Graph of the Free RAM module.
     * Graph of the Queued Modules module.
     * Graph of the Status module.
     * Graph of the System Load AVG module.
     * Graph of the Execution Time module.
     *
     * @return string
     */
    public function printCharts()
    {
        /*
         * Agent id with name Master Server.
         */

        $agentIdMasterServer = $this->getAgentIdMasterServer();

        $result = '';
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

            $return .= '<div class="title-self-monitoring">';
            $return .= __(
                'Graphs modules that represent the self-monitoring system'
            );
            $return .= '</div>';
            $return .= '<div class="container-self-monitoring">';
            foreach ($agentMonitoring as $key => $value) {
                $return .= $this->printDataCharts($value);
            }

            $return .= '</div>';
        }

        return $return;
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
                    'name'  => __('%s Build', $this->product_name),
                    'value' => $build_version,
                ],
                'version'       => [
                    'name'  => __('%s Version', $this->product_name),
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
     * Date system
     *
     * @return string
     */
    public function getSystemDate(): string
    {
        $result = [
            'error' => false,
            'data'  => [
                'date' => [
                    'name'  => __('System Date (Console)'),
                    'value' => date('H:i:s Y-m-d'),
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
                    'name'  => __('Total agents'),
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
        if ($dateDbMantenaince !== false && empty($dateDbMantenaince) === false) {
            $difference = ($currentTime - $dateDbMantenaince);
            $pandoraDbLastRun = human_time_description_raw(
                $difference,
                true
            );
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
                    'name'  => __('Pandora DB Last run'),
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

            exec(
                "ifconfig | awk '{ print $2}' | grep -E -o '([0-9]{1,3}[\.]){3}[0-9]{1,3}'",
                $output
            );

            $ips = implode(', ', $output);

            $result = [
                'error' => false,
                'data'  => [
                    'cpuInfo'      => [
                        'name'  => __('CPU'),
                        'value' => exec($cpuModelName).' x '.exec($cpuProcessor),
                    ],
                    'ramInfo'      => [
                        'name'  => __('RAM'),
                        'value' => exec($ramMemTotal),
                    ],
                    'osInfo'       => [
                        'name'  => __('Os'),
                        'value' => exec('uname -a'),
                    ],
                    'hostnameInfo' => [
                        'name'  => __('Hostname'),
                        'value' => exec('hostname'),
                    ],
                    'ipInfo'       => [
                        'name'  => __('Ip'),
                        'value' => $ips,
                    ],
                ],
            ];
        } else {
            $result = [
                'error' => false,
                'data'  => [
                    'osInfo'       => [
                        'name'  => __('OS'),
                        'value' => exec('ver'),
                    ],
                    'hostnameInfo' => [
                        'name'  => __('Hostname'),
                        'value' => exec('hostname'),
                    ],
                    'ipInfo'       => [
                        'name'  => __('Ip'),
                        'value' => exec('ipconfig | findstr IPv4'),
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
                    case 'innodb_buffer_pool_size':
                        $name = __('InnoDB buffer pool size');
                        $value = ($item['Value'] / $bytes);
                        $status = (($item['Value'] / $bytes) >= 250) ? 1 : 0;
                        $message = __(
                            'It has to be 40% of the server memory not recommended to be greater or less'
                        );
                    break;

                    case 'innodb_file_per_table':
                        $name = __('InnoDB file per table');
                        $value = $item['Value'];
                        $status = ($item['Value'] === 'ON') ? 1 : 0;
                        $message = __('Recommended ON');
                    break;

                    case 'innodb_flush_log_at_trx_commit':
                        $name = __('InnoDB flush log at trx-commit');
                        $value = $item['Value'];
                        $status = ((int) $item['Value'] === self::INNODB_FLUSH_LOG_AT_TRX_COMMIT) ? 1 : 0;
                        $message = __('Recommended Value %d', self::INNODB_FLUSH_LOG_AT_TRX_COMMIT);
                    break;

                    case 'innodb_lock_wait_timeout':
                        $name = __('InnoDB lock wait timeout');
                        $value = $item['Value'];
                        $status = ($item['Value'] >= 90) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 90s';
                    break;

                    case 'innodb_log_buffer_size':
                        $name = __('InnoDB log buffer size');
                        $value = ($item['Value'] / $bytes);
                        $status = (($item['Value'] / $bytes) >= 16) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 16M';
                    break;

                    case 'innodb_log_file_size':
                        $name = __('InnoDB log file size');
                        $value = ($item['Value'] / $bytes);
                        $status = (($item['Value'] / $bytes) >= 64) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 64M';
                    break;

                    case 'max_allowed_packet':
                        $name = __('Maximun allowed packet');
                        $value = ($item['Value'] / $bytes);
                        $status = (($item['Value'] / $bytes) >= 32) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 32M';
                    break;

                    case 'max_connections':
                        $name = __('Maximun connections');
                        $value = $item['Value'];
                        $status = (($item['Value']) >= 90) ? 1 : 0;
                        $message = __('Min. Recommended Value');
                        $message .= ' 90 ';
                        $message .= __('conections');
                    break;

                    case 'query_cache_limit':
                        $name = __('Query cache limit');
                        $value = ($item['Value'] / $bytes);
                        $status = (($item['Value'] / $bytes) >= 8) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 8M';
                    break;

                    case 'query_cache_min_res_unit':
                        $name = __('Query cache min-res-unit');
                        $value = ($item['Value'] / $mega);
                        $status = (($item['Value'] / $mega) >= 2) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 2M';
                    break;

                    case 'query_cache_size':
                        $name = __('Query cache size');
                        $value = ($item['Value'] / $bytes);
                        $status = (($item['Value'] / $bytes) >= 32) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 32M';
                    break;

                    case 'query_cache_type':
                        $name = __('Query cache type');
                        $value = $item['Value'];
                        $status = ($item['Value'] === 'ON') ? 1 : 0;
                        $message = __('Recommended ON');
                    break;

                    case 'read_buffer_size':
                        $name = __('Read buffer size');
                        $value = ($item['Value'] / $mega);
                        $status = (($item['Value'] / $mega) >= 32) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 32K';
                    break;

                    case 'read_rnd_buffer_size':
                        $name = __('Read rnd-buffer size');
                        $value = ($item['Value'] / $mega);
                        $status = (($item['Value'] / $mega) >= 32) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 32K';
                    break;

                    case 'sort_buffer_size':
                        $name = __('Sort buffer size');
                        $value = ($item['Value'] / $mega);
                        $status = (($item['Value'] / $mega) >= 32) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 32K';
                    break;

                    case 'sql_mode':
                        $name = __('Sql mode');
                        $value = ($item['Value']);
                        $status = (empty($item['Value']) === true) ? 1 : 0;
                        $message = __('Must be empty');
                    break;

                    case 'thread_cache_size':
                        $name = __('Thread cache size');
                        $value = $item['Value'];
                        $status = ($item['Value'] >= 8) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 8';
                    break;

                    case 'thread_stack':
                        $name = __('Thread stack');
                        $value = ($item['Value'] / $mega);
                        $status = (($item['Value'] / $mega) >= 256) ? 1 : 0;
                        $message = __('Min. Recommended Value').' 256';
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
                    'value' => number_format($tFragmentationValue, 2, $config['decimal_separator'], $config['thousand_separator']).'%',
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

        $logs_directory = (empty($config['server_log_dir']) === false)
            ? io_safe_output($config['server_log_dir'])
            : '/var/log/pandora';

        $pathServerLogs = $logs_directory.'/pandora_server.log';
        $servers = $this->getLogInfo($pathServerLogs);

        $pathErrLogs = $logs_directory.'/pandora_server.error';
        $errors = $this->getLogInfo($pathErrLogs);

        $pathConsoleLogs = $config['homedir'].'/log/console.log';
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
        enterprise_include_once('include/functions_license.php');
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
                ((int) $totalNetworkModules / (int) $totalModuleIntervalTime),
                3,
                $config['decimal_separator'],
                $config['thousand_separator']
            );
        }

        $moduleNetworkmsg = __(
            sprintf(
                'The system is not overloaded (average time %f)',
                $averageTime
            )
        );
        $moduleNetworkst = 1;
        if ($averageTime === 0) {
            $moduleNetworkmsg = __('The system has no load');
            $moduleNetworkst = 0;
        } else if ($averageTime > 180) {
            $moduleNetworkmsg = __(
                sprintf(
                    'The system is overloaded (average time %f) and a very fine configuration is required',
                    $averageTime
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

        $modulesDataCount = db_get_value_sql(
            'SELECT count(*) * 300 FROM (SELECT * FROM tagente_datos GROUP BY id_agente_modulo) AS totalmodules'
        );
        $modulesDataCount = ($modulesDataCount >= 500000) ? $modulesDataCount : 500000;

        $taMsg = __(
            'The tagente_datos table contains too much data. A historical database is recommended.'
        );
        $taStatus = 0;
        if ($agentDataCount <= $modulesDataCount) {
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

        $result = [];
        $totalServerThreads = 0;
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            $totalServerThreads = shell_exec(
                'ps -T aux | grep pandora_server | grep -v grep | wc -l'
            );
        }

        include_once $config['homedir'].'/include/functions_servers.php';
        $sql = 'SELECT `name`, server_type, threads FROM tserver';
        $servers = db_get_all_rows_sql($sql);

        if (isset($servers) === true && is_array($servers) === true) {
            $sum_threads = 0;
            foreach ($servers as $key => $value) {
                $result['data']['threads_server_'.$value['server_type']] = [
                    'name'  => __('Threads').' '.\servers_get_server_string_name(
                        $value['server_type']
                    ),
                    'value' => $value['threads'],
                ];

                $sum_threads += $value['threads'];
            }

            $result['data']['total_threads'] = [
                'name'   => __('Total threads'),
                'value'  => $sum_threads,
                'status' => ($sum_threads < $totalServerThreads) ? 2 : 1,
            ];

            if ($sum_threads < $totalServerThreads) {
                $result['data']['total_threads']['message'] = __(
                    'Current pandora_server running threads'
                );
            } else {
                __(
                    'There\'s more pandora_server threads than configured, are you running multiple servers simultaneusly?.'
                );
            }
        }

        return json_encode($result);
    }


    /**
     * SQL show engine innodb status.
     *
     * @return string
     */
    public function getShowEngine(): string
    {
        global $config;

        try {
            // Trick to avoid showing error in case
            // you don't have enough permissions.
            $backup = error_reporting();
            error_reporting(0);
            $innodb = db_get_all_rows_sql('show engine innodb status');
            error_reporting($backup);
        } catch (Exception $e) {
            $innodb['Status'] = $e->getMessage();
        }

        $result = [];
        if (isset($innodb[0]['Status']) === true
            && $innodb[0]['Status'] !== false
        ) {
            $lenght = strlen($innodb[0]['Status']);

            $data = [];
            for ($i = 0; $i < $lenght; $i = ($i + 300)) {
                $str = substr($innodb[0]['Status'], $i, ($i + 300));
                $data['showEngine-'.$i] = [
                    'name'  => '',
                    'value' => '<pre>'.$str.'</pre>',
                ];
            }

            $result = [
                'error' => false,
                'data'  => $data,
                'id'    => 'showEngine',
            ];
        }

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
     * @return string Return html.
     */
    public function printData(string $method, string $title): string
    {
        global $config;

        if (is_ajax()) {
            // TODO: Call method.
            $result = $method;
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
                if ($this->pdf === false) {
                    $result = ui_print_datatable(
                        [
                            'id'                  => $tableId,
                            'class'               => 'info_table caption_table',
                            'style'               => 'width: 99%',
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
                            'print'               => true,
                        ]
                    );
                } else {
                    $data = json_decode(
                        $this->datatablesDraw($method, true),
                        true
                    );

                    $table = new stdClass();
                    $table->width = '100%';
                    $table->class = 'pdf-report';
                    $table->style = [];
                    $table->style[0] = 'font-weight: bolder;';

                    // FIX tables break content.
                    if ($data['idTable'] === 'showEngine') {
                        $table->styleTable = 'page-break-inside: auto;';
                    } else {
                        $table->autosize = 1;
                    }

                    $table->head = [];
                    $table->head_colspan[0] = 3;
                    $table->head[0] = $title;
                    $table->data = [];

                    if (isset($data) === true
                        && is_array($data) === true
                        && count($data) > 0
                    ) {
                        $i = 0;
                        foreach ($data['data'] as $key => $value) {
                            $table->data[$i][0] = $value['name'];
                            $table->data[$i][1] = $value['value'];
                            $table->data[$i][2] = $value['message'];
                            $i++;
                        }
                    }

                    $result = html_print_table($table, true);
                }
            } catch (Exception $e) {
                $result = $e->getMessage();
            }
        }

        return $result;
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
     * @return string Html.
     */
    public function printDataCharts(array $params): string
    {
        global $config;

        if (!$params) {
            $params = get_parameter('params');
        }

        if (is_ajax()) {
            // TODO: Call method.
            $return = $method;
        } else {
            // Datatables list.
            try {
                $id = str_replace(
                    ' ',
                    '',
                    io_safe_output($params['nameModule'])
                );

                if ($this->pdf === false) {
                    $return = '<div id="'.$id.'" class="element-self-monitoring"></div>';
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
                } else {
                    $table = new stdClass();
                    $table->width = '100%';
                    $table->class = 'pdf-report';
                    $table->style = [];
                    $table->style[0] = 'font-weight: bolder;';
                    $table->autosize = 1;

                    $table->head = [];
                    $table->head[0] = $params['nameModule'];

                    $table->data = [];
                    $table->data[0] = $this->getChart(
                        $params['idAgent'],
                        $params['nameModule'],
                        true,
                        false
                    );

                    $return = html_print_table($table, true);
                }
            } catch (Exception $e) {
                $return = $e->getMessage();
            }
        }

        return $return;
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

        if (file_exists($path) === true) {
            $fileSize = filesize($path);
            $sizeServerLog = number_format($fileSize);
            $sizeServerLog = (0 + str_replace(',', '', $sizeServerLog));

            $value = number_format(($fileSize / $mega), 3, $config['decimal_separator'], $config['thousand_separator']);
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
     * Undocumented function
     *
     * @param string|null $method Method data requested.
     * @param boolean     $return Type return.
     *
     * @return string|null
     */
    public function datatablesDraw(
        ?string $method=null,
        bool $return=false
    ):?string {
        if (isset($method) === false) {
            $method = get_parameter('name', '');
        }

        if (method_exists($this, $method) === true) {
            $data = json_decode($this->{$method}(), true);
        }

        $result = [];
        if (isset($data) === true
            && is_array($data) === true
            && count($data) > 0
        ) {
            $items = $data['data'];
            $dataReduce = array_reduce(
                array_keys($data['data']),
                function ($carry, $key) use ($items) {
                    // Transforms array of arrays $data into an array
                    // of objects, making a post-process of certain fields.
                    if (isset($items[$key]['status']) === true) {
                        $acumValue = $items[$key]['value'];

                        if ($items[$key]['status'] === 2) {
                            $items[$key]['value'] = html_print_image(
                                'images/alert-yellow@svg.svg',
                                true,
                                [
                                    'title' => __('Warning'),
                                    'style' => 'width:15px;',
                                ]
                            );
                        } else if ($items[$key]['status'] === 1) {
                            $items[$key]['value'] = html_print_image(
                                'images/validate.svg',
                                true,
                                [
                                    'title' => __('Successfully'),
                                    'style' => 'width:15px;',
                                ]
                            );
                        } else {
                            $items[$key]['value'] = html_print_image(
                                'images/fail@svg.svg',
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
                        $customerKey = ui_print_truncate_text(
                            $items[$key]['value'],
                            30,
                            false,
                            true,
                            false
                        );
                        $spanValue = '<span>'.$customerKey.'</span>';
                        $items[$key]['value'] = $spanValue;
                    }

                    if (isset($items[$key]['message']) === false) {
                        $items[$key]['message'] = '';
                    }

                    $carry[] = (object) $items[$key];
                    return $carry;
                }
            );

            $result = [
                'data'            => $dataReduce,
                'recordsTotal'    => count($dataReduce),
                'recordsFiltered' => count($dataReduce),
                'idTable'         => (isset($data['id']) === true) ? $data['id'] : '',
            ];
        }

        // Datatables format: RecordsTotal && recordsfiltered.
        if ($return === false) {
            echo json_encode($result);
            return null;
        } else {
            return json_encode($result);
        }
    }


    /**
     * Print Diagnostics Form feedback.
     *
     * @return void
     */
    public function formFeedback(): void
    {
        $form = [
            'action'   => '#',
            'id'       => 'modal_form_feedback',
            'onsubmit' => 'return false;',
            'class'    => 'modal',
            'extra'    => 'novalidate',
        ];

        $inputs = [];

        $inputs[] = [
            'label'     => __('What happened?'),
            'id'        => 'div-what-happened',
            'class'     => 'flex-row',
            'arguments' => [
                'name'       => 'what-happened',
                'type'       => 'textarea',
                'value'      => '',
                'return'     => true,
                'rows'       => 1,
                'columns'    => 1,
                'size'       => 25,
                'attributes' => 'required="required"',
            ],
        ];

        $inputs[] = [
            'label'     => __('Your email'),
            'class'     => 'flex-row-baseline',
            'arguments' => [
                'name'     => 'email',
                'id'       => 'email',
                'type'     => 'email',
                'size'     => 42,
                'required' => 'required',
            ],
        ];

        $inputs[] = [
            'label'     => __('Include installation data'),
            'class'     => 'flex-row-vcenter',
            'arguments' => [
                'name'  => 'include_installation_data',
                'id'    => 'include_installation_data',
                'type'  => 'switch',
                'value' => 1,
            ],
        ];

        exit(
            $this->printForm(
                [
                    'form'   => $form,
                    'inputs' => $inputs,
                ],
                true
            )
        );
    }


    /**
     * Create cron task form feedback.
     *
     * @return void Json result AJAX request.
     */
    public function createdScheduleFeedbackTask():void
    {
        global $config;

        $mail_feedback = 'feedback@artica.es';
        $email = $mail_feedback;
        $subject = $this->product_name.' Report '.$config['pandora_uid'];
        $text = get_parameter('what-happened', '');
        $attachment = get_parameter_switch('include_installation_data', 0);
        $email_from = get_parameter_switch('email', '');
        $title = __('Hello Feedback-Men');

        $product_name = io_safe_output(get_product_name());

        if (check_acl($config['id_user'], 0, 'PM') !== 1) {
            $email = get_mail_admin();
            $name_admin = get_name_admin();

            $subject = __('Feedback').' '.$product_name.' '.$config['pandora_uid'];

            $title = __('Hello').' '.$name_admin;
        }

        $p1 = __(
            'User %s is reporting an issue in its %s experience',
            $email_from,
            $product_name
        );
        $p1 .= ':';

        $p2 = $text;

        if ($attachment === 1) {
            $msg_attch = __('Find some files attached to this mail');
            $msg_attch .= '. ';
            $msg_attch .= __(
                'PDF is the diagnostic information retrieved at report time'
            );
            $msg_attch .= '. ';
            $msg_attch .= __('CSV contains the statuses of every product file');
            $msg_attch .= '. ';
        }

        $p3 = __(
            'If you think this report must be escalated, feel free to forward this mail to "%s"',
            $mail_feedback
        );

        $legal = __('LEGAL WARNING');
        $legal1 = __(
            'The information contained in this transmission is privileged and confidential information intended only for the use of the individual or entity named above'
        );
        $legal1 .= '. ';
        $legal2 = __(
            'If the reader of this message is not the intended recipient, you are hereby notified that any dissemination, distribution or copying of this communication is strictly prohibited'
        );
        $legal2 .= '. ';
        $legal3 = __(
            'If you have received this transmission in error, do not read it'
        );
        $legal3 .= '. ';
        $legal4 = __(
            'Please immediately reply to the sender that you have received this communication in error and then delete it'
        );
        $legal4 .= '.';

        $patterns = [
            '/__title__/',
            '/__p1__/',
            '/__p2__/',
            '/__attachment__/',
            '/__p3__/',
            '/__legal__/',
            '/__legal1__/',
            '/__legal2__/',
            '/__legal3__/',
            '/__legal4__/',
        ];

        $substitutions = [
            $title,
            $p1,
            $p2,
            $msg_attch,
            $p3,
            $legal,
            $legal1,
            $legal2,
            $legal3,
            $legal4,
        ];

        $html_template = file_get_contents(
            $config['homedir'].'/include/templates/feedback_send_mail.html'
        );

        $text = preg_replace($patterns, $substitutions, $html_template);

        $idUserTask = db_get_value(
            'id',
            'tuser_task',
            'function_name',
            'cron_task_feedback_send_mail'
        );

        // Params for send mail with cron.
        $parameters = [
            0                 => '0',
            1                 => $email,
            2                 => $subject,
            3                 => $text,
            4                 => $attachment,
            'first_execution' => strtotime('now'),
        ];

        // Values insert task cron.
        $values = [
            'id_usuario'   => $config['id_user'],
            'id_user_task' => $idUserTask,
            'args'         => serialize($parameters),
            'scheduled'    => 'no',
            'id_grupo'     => 0,
        ];

        $result = db_process_sql_insert(
            'tuser_task_scheduled',
            $values
        );

        $error = 1;
        if ($result === false) {
            $error = 0;
        }

        $return = [
            'error' => $error,
            'title' => [
                __('Failed'),
                __('Success'),
            ],
            'text'  => [
                ui_print_error_message(__('Invalid cron task'), '', true),
                ui_print_success_message(__('Sending of information has been processed'), '', true),
            ],
        ];

        exit(json_encode($return));
    }


    /**
     * Print Diagnostics PDF report.
     *
     * @param string|null $filename Filename.
     *
     * @return mixed
     */
    public function exportPDF(?string $filename=null)
    {
        global $config;

        $this->pdf = true;

        enterprise_include_once('/include/class/Pdf.class.php');
        $mpdf = new Pdf([]);

        // Ignore pending HTML outputs.
        while (@ob_end_clean()) {
            $ignore_me;
        }

        // ADD style.
        $mpdf->addStyle($config['homedir'].'/include/styles/diagnostics.css');

        // ADD Metadata.
        $product_name = io_safe_output(get_product_name());
        $mpdf->setMetadata(
            __('Diagnostics Info'),
            $product_name.' Enteprise',
            $product_name,
            __(
                'Automated %s report for user defined report',
                $product_name
            )
        );

        // ADD Header.
        $mpdf->setHeaderHTML(__('Diagnostics Info'));

        // ADD content to report.
        $mpdf->addHTML(
            $this->printMethodsDiagnostigsInfo()
        );

        $mpdf->addHTML(
            $this->printCharts()
        );

        // ADD Footer.
        $mpdf->setFooterHTML();

        // Write html filename.
        $mpdf->writePDFfile($filename);

        return;
    }


    /**
     * Send Csv md5 files.
     *
     * @return string
     */
    public function csvMd5Files():string
    {
        global $config;

        // Extract files.
        $files = $this->recursiveDirValidation($config['homedir']);

        // Type divider.
        $divider = html_entity_decode($config['csv_divider']);

        // BOM.
        $result = pack('C*', 0xEF, 0xBB, 0xBF);

        $result .= __('Path').$divider.__('MD5')."\n";
        foreach ($files as $key => $value) {
            $result .= $key.$divider.$value."\n";
        }

        return $result;
    }


    /**
     * Function to return array with name file -> MD%.
     *
     * @param string $dir Directory.
     *
     * @return array Result all files in directory recursively.
     */
    private function recursiveDirValidation(string $dir):array
    {
        $result = [];

        $dir_content = scandir($dir);

        // Dont check attachment.
        if (strpos($dir, $config['homedir'].'/attachment') === false) {
            if (is_array($dir_content) === true) {
                foreach (scandir($dir) as $file) {
                    if ('.' === $file || '..' === $file) {
                        continue;
                    }

                    if (is_dir($dir.'/'.$file) === true) {
                        $result += $this->recursiveDirValidation(
                            $dir.'/'.$file
                        );
                    } else {
                        $result[$dir.'/'.$file] = md5_file($dir.'/'.$file);
                    }
                }
            }
        }

        return $result;
    }


    /**
     * Send PHP info in report.
     *
     * @param string $filename Download dir report.
     *
     * @return void
     */
    public function phpInfoReports(string $filename)
    {
        global $config;

        $this->pdf = true;

        // Extract info PHP.
        ob_start();
        phpinfo();
        $php_info = ob_get_clean();

        enterprise_include_once('/include/class/Pdf.class.php');
        $mpdf = new Pdf([]);

        // ADD Metadata.
        $product_name = io_safe_output(get_product_name());
        $mpdf->setMetadata(
            __('PHP Info'),
            $product_name.' Enteprise',
            $product_name,
            __(
                'Automated %s report for user defined report',
                $product_name
            )
        );

        // ADD Header.
        $mpdf->setHeaderHTML(__('PHP Info'));

        // ADD content to report.
        $mpdf->addHTML($php_info);

        // ADD Footer.
        $mpdf->setFooterHTML();

        // Write html filename.
        $mpdf->writePDFfile($filename);
    }


}
