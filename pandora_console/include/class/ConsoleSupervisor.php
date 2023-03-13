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

use PandoraFMS\Tools\Files;

global $config;

require_once $config['homedir'].'/include/functions_db.php';
require_once $config['homedir'].'/include/functions_io.php';
require_once $config['homedir'].'/include/functions_notifications.php';
require_once $config['homedir'].'/include/functions_servers.php';
require_once $config['homedir'].'/vendor/autoload.php';

// Enterprise includes.
enterprise_include_once('include/functions_metaconsole.php');
enterprise_include_once('include/functions_license.php');
enterprise_include_once('include/functions_cron.php');

/**
 * Base class to run scheduled tasks in cron extension
 */
class ConsoleSupervisor
{

    /**
     * Minimum modules to check performance.
     */
    public const MIN_PERFORMANCE_MODULES = 100;


    /**
     * Minimum queued elements in synchronization queue to be warned..
     */
    public const MIN_SYNC_QUEUE_LENGTH = 200;

    /**
     * Show if console supervisor is enabled or not.
     *
     * @var boolean
     */
    public $enabled;

    /**
     * Value of 'id' from tnotification_source
     * where description is 'System status'
     *
     * @var integer
     */
    public $sourceId;

    /**
     * Target groups to be notified.
     *
     * @var array
     */
    public $targetGroups;

    /**
     * Target users to be notified.
     *
     * @var array
     */
    public $targetUsers;

    /**
     * Targets up to date.
     *
     * @var boolean
     */
    public $targetUpdated;

    /**
     * Show messages or not.
     *
     * @var boolean
     */
    public $interactive;


    /**
     * Constructor.
     *
     * @param boolean $interactive Show output while executing or not.
     *
     * @return class This object
     */
    public function __construct(bool $interactive=true)
    {
        $source = db_get_row(
            'tnotification_source',
            'description',
            io_safe_input('System status')
        );

        $this->interactive = $interactive;

        if ($source === false) {
            $this->enabled = false;
            $this->sourceId = null;

            $this->targetGroups = null;
            $this->targetUsers = null;
        } else {
            $this->enabled = (bool) $source['enabled'];
            $this->sourceId = $source['id'];
        }

        return $this;
    }


    /**
     * Warn a message.
     *
     * @param string $msg Message.
     *
     * @return void
     */
    public function warn(string $msg)
    {
        if ($this->verbose === true) {
            echo date('M  j G:i:s').' ConsoleSupervisor: '.$msg."\n";
        }
    }


    /**
     * Manage scheduled tasks (basic).
     *
     * @return void
     */
    public function runBasic()
    {
        // Ensure functions are installed and up to date.
        enterprise_hook('cron_extension_install_functions');

        /*
         * PHP configuration warnings:
         *  NOTIF.PHP.SAFE_MODE
         *  NOTIF.PHP.INPUT_TIME
         *  NOTIF.PHP.EXECUTION_TIME
         *  NOTIF.PHP.UPLOAD_MAX_FILESIZE
         *  NOTIF.PHP.MEMORY_LIMIT
         *  NOTIF.PHP.DISABLE_FUNCTIONS
         *  NOTIF.PHP.CHROMIUM
         *  NOTIF.PHP.VERSION
         */

        $this->checkPHPSettings();

        /*
         * Check license.
         *  NOTIF.LICENSE.EXPIRATION
         */

        $this->checkLicense();

        /*
         * Check component statuses (servers down - frozen).
         *  NOTIF.SERVER.STATUS.ID_SERVER
         */

        $this->checkPandoraServers();

        /*
         * Check at least 1 server running in master mode.
         *  NOTIF.SERVER.MASTER
         */

        $this->checkPandoraServerMasterAvailable();

        /*
         * Check if CRON is running.
         *  NOTIF.CRON.CONFIGURED
         */

        if (enterprise_installed()) {
            $this->checkCronRunning();
        }

        /*
         * Check if instance is registered.
         *  NOTIF.UPDATEMANAGER.REGISTRATION
         */

        $this->checkUpdateManagerRegistration();

        /*
         * Check if there're new messages in UM.
         *  NOTIF.UPDATEMANAGER.MESSAGES
         */

        $this->getUMMessages();

        /*
         * Check if the Server and Console has
         * the same versions.
         *  NOTIF.SERVER.MISALIGNED
         */

        $this->checkConsoleServerVersions();

        /*
         * Check if AllowOverride is None or All.
         *  NOTIF.ALLOWOVERIDE.MESSAGE
         */

        $this->checkAllowOverrideEnabled();

        /*
         * Check if the Pandora Console log
         * file remains in old location.
         *  NOTIF.PANDORACONSOLE.LOG.OLD
         */

        $this->checkPandoraConsoleLogOldLocation();

        /*
         * Check if the audit log file
         * remains in old location.
         *  NOTIF.AUDIT.LOG.OLD
         */

        $this->checkAuditLogOldLocation();

        /*
         * Check if performance variables are corrects
         */
        $this->checkPerformanceVariables();

        /*
         * Checks if sync queue is longer than limits.
         *  NOTIF.SYNCQUEUE.LENGTH
         */

        if (is_metaconsole() === true) {
            $this->checkSyncQueueLength();
            $this->checkSyncQueueStatus();
        }

        /*
         * Check number of agents is equals and more than 200.
         * NOTIF.ACCESSSTASTICS.PERFORMANCE
         */

        $this->checkAccessStatisticsPerformance();

        /*
         * Checkc agent missing libraries.
         * NOTIF.AGENT.LIBRARY
         */

        if ((bool) enterprise_installed() === true) {
            $this->checkLibaryError();
        }
    }


    /**
     * Manage scheduled tasks.
     *
     * @return void
     */
    public function run()
    {
        global $config;

        $this->maintenanceOperations();

        if ($this->enabled === false) {
            // Notifications not enabled.
            return;
        }

        if ($this->sourceId === null) {
            // Source not detected.
            return;
        }

        if ($this->verbose === true) {
            if (enterprise_hook('cron_supervisor_lock') === false) {
                // Cannot continue. Locked.
                exit;
            }
        }

        // Enterprise support.
        if (file_exists($config['homedir'].'/'.ENTERPRISE_DIR.'/load_enterprise.php')) {
            include_once $config['homedir'].'/'.ENTERPRISE_DIR.'/load_enterprise.php';
        }

        // Automatic checks launched by supervisor.
        $this->warn('running.');

        /*
         * Check license.
         *  NOTIF.LICENSE.EXPIRATION
         *  NOTIF.LICENSE.LIMITED
         */

        $this->checkLicense();

        /*
         * Check number of files in attachment:
         *  NOTIF.FILES.ATTACHMENT
         */

        $this->checkAttachment();

        /*
         * Files in data_in:
         *  NOTIF.FILES.DATAIN  (>1000)
         *  NOTIF.FILES.DATAIN.BADXML (>150)
         */

        $this->checkDataIn();

        /*
         * Check module queues not growing:
         *  NOTIF.SERVER.QUEUE.ID_SERVER
         */

        $this->checkServers();

        /*
         * Check component statuses (servers down - frozen).
         *  NOTIF.SERVER.STATUS.ID_SERVER
         */

        $this->checkPandoraServers();

        /*
         * Check at least 1 server running in master mode.
         *  NOTIF.SERVER.MASTER
         */

        $this->checkPandoraServerMasterAvailable();

        /*
         * PHP configuration warnings:
         *  NOTIF.PHP.SAFE_MODE
         *  NOTIF.PHP.INPUT_TIME
         *  NOTIF.PHP.EXECUTION_TIME
         *  NOTIF.PHP.UPLOAD_MAX_FILESIZE
         *  NOTIF.PHP.MEMORY_LIMIT
         *  NOTIF.PHP.DISABLE_FUNCTIONS
         *  NOTIF.PHP.CHROMIUM
         *  NOTIF.PHP.VERSION
         */

        $this->checkPHPSettings();

        /*
         *  Check connection with historical DB (if enabled).
         *  NOTIF.HISTORYDB
         */

        $this->checkPandoraHistoryDB();

        /*
         * Check pandoradb running in main DB.
         * Check pandoradb running in historical DB.
         *  NOTIF.PANDORADB
         *  NOTIF.PANDORADB.HISTORICAL
         */

        $this->checkPandoraDBMaintenance();

        /*
         * Check historical DB MR version.
         *  NOTIF.HISTORYDB.MR
         */

        $this->checkPandoraHistoryDBMR();

        /*
         * Check external components.
         *  NOTIF.EXT.ELASTICSEARCH
         *  NOTIF.EXT.LOGSTASH
         *
         */

        $this->checkExternalComponents();

        /*
         * Check Metaconsole synchronization issues.
         *  NOTIF.METACONSOLE.DB_CONNECTION
         */

        $this->checkMetaconsole();

        /*
         * Check incoming scheduled downtimes (< 15d).
         *  NOTIF.DOWNTIME
         */

        $this->checkDowntimes();

        /*
         * Check if instance is registered.
         *  NOTIF.UPDATEMANAGER.REGISTRATION
         */

        $this->checkUpdateManagerRegistration();

        /*
         * Check if event storm protection is activated.
         *  NOTIF.MISC.EVENTSTORMPROTECTION
         */

        $this->checkEventStormProtection();

        /*
         * Check if develop_bypass is enabled.
         *  NOTIF.MISC.DEVELOPBYPASS
         */

        $this->checkDevelopBypass();

        /*
         * Check if fontpath exists.
         *  NOTIF.MISC.FONTPATH
         */

        $this->checkFont();

        /*
         * Check if default user and password exists.
         *  NOTIF.SECURITY.DEFAULT_PASSWORD
         */

        $this->checkDefaultPassword();

        /*
         * Check if there're new updates.
         *  NOTIF.UPDATEMANAGER.OPENSETUP
         *  NOTIF.UPDATEMANAGER.UPDATE
         */

        $this->checkUpdates();

        /*
         * Check if there're new minor updates available.
         *  NOTIF.UPDATEMANAGER.MINOR
         */

        $this->checkMinorRelease();

        if ((bool) enterprise_installed() === true) {
            // Release the lock.
            enterprise_hook('cron_supervisor_release_lock');
        }

        /*
         * Check if CRON is running.
         *  NOTIF.CRON.CONFIGURED
         */

        if (enterprise_installed()) {
            $this->checkCronRunning();
        }

        /*
         * Check if instance is registered.
         *  NOTIF.UPDATEMANAGER.REGISTRATION
         */

        $this->checkUpdateManagerRegistration();

        /*
         * Check if there're new messages in UM.
         *  NOTIF.UPDATEMANAGER.MESSAGES
         */

        $this->getUMMessages();

        /*
         * Check if the Server and Console has
         * the same versions.
         *  NOTIF.SERVER.MISALIGNED
         */

        $this->checkConsoleServerVersions();

        /*
         * Check if AllowOverride is None or All.
         *  NOTIF.ALLOWOVERRIDE.MESSAGE
         */

        $this->checkAllowOverrideEnabled();

        /*
         * Check if HA status.
         */

        if ((bool) enterprise_installed() === true) {
            $this->checkHaStatus();
        }

        /*
         * Check if the audit log file
         * remains in old location.
         */

        $this->checkAuditLogOldLocation();

        /*
         * Check if performance variables are corrects
         */
        $this->checkPerformanceVariables();

        /*
         * Checks if sync queue is longer than limits.
         *  NOTIF.SYNCQUEUE.LENGTH
         */

        if (is_metaconsole() === true) {
            $this->checkSyncQueueLength();
            $this->checkSyncQueueStatus();
        }

        /*
         * Check number of agents is equals and more than 200.
         * NOTIF.ACCESSSTASTICS.PERFORMANCE
         */

        $this->checkAccessStatisticsPerformance();

        /*
         * Checkc agent missing libraries.
         * NOTIF.AGENT.LIBRARY
         */

        if ((bool) enterprise_installed() === true) {
            $this->checkLibaryError();
        }

    }


    /**
     * Check if performance variables are corrects
     *
     * @return void
     */
    public function checkPerformanceVariables()
    {
        global $config;

        $names = [
            'event_purge'                      => 'Max. days before events are deleted',
            'trap_purge'                       => 'Max. days before traps are deleted',
            'audit_purge'                      => 'Max. days before audited events are deleted',
            'string_purge'                     => 'Max. days before string data is deleted',
            'gis_purge'                        => 'Max. days before GIS data is deleted',
            'days_purge'                       => 'Max. days before purge',
            'days_compact'                     => 'Max. days before data is compacted',
            'days_delete_unknown'              => 'Max. days before unknown modules are deleted',
            'days_delete_not_initialized'      => 'Max. days before delete not initialized modules',
            'days_autodisable_deletion'        => 'Max. days before autodisabled agents are deleted',
            'delete_old_network_matrix'        => 'Max. days before delete old network matrix data',
            'report_limit'                     => 'Item limit for real-time reports',
            'event_view_hr'                    => 'Default hours for event view',
            'big_operation_step_datos_purge'   => 'Big Operation Step to purge old data',
            'small_operation_step_datos_purge' => 'Small Operation Step to purge old data',
            'row_limit_csv'                    => 'Row limit in csv log',
            'limit_parameters_massive'         => 'Limit for bulk operations',
            'block_size'                       => 'Block size for pagination',
            'short_module_graph_data'          => 'Data precision',
            'graph_precision'                  => 'Data precision in graphs',
        ];

        $variables = (array) json_decode(io_safe_output($config['performance_variables_control']));

        foreach ($variables as $variable => $values) {
            if (empty($config[$variable]) === true || $config[$variable] === '') {
                continue;
            }

            $message = '';
            $limit_value = '';
            if ($config[$variable] > $values->max) {
                $message = 'Check the setting of %s, a value greater than %s is not recommended';
                $limit_value = $values->max;
            }

            if ($config[$variable] < $values->min) {
                $message = 'Check the setting of %s, a value less than %s is not recommended';
                $limit_value = $values->min;
            }

            if ($limit_value !== '' && $message !== '') {
                if (is_metaconsole() === true) {
                    $this->notify(
                        [
                            'type'    => 'NOTIF.VARIABLES.PERFORMANCE.'.$variable,
                            'title'   => __('Incorrect config value'),
                            'message' => __(
                                $message,
                                $names[$variable],
                                $limit_value
                            ),
                            'url'     => '__url__index.php?sec=advanced&sec2=advanced/metasetup',
                        ]
                    );
                } else {
                    $this->notify(
                        [
                            'type'    => 'NOTIF.VARIABLES.PERFORMANCE.'.$variable,
                            'title'   => __('Incorrect config value'),
                            'message' => __(
                                $message,
                                $names[$variable],
                                $limit_value
                            ),
                            'url'     => '__url__/index.php?sec=general&sec2=godmode/setup/setup',
                        ]
                    );
                }
            }
        }

    }


    /**
     * Executes console maintenance operations. Executed ALWAYS through CRON.
     *
     * @return void
     */
    public function maintenanceOperations()
    {

    }


    /**
     * Check number of agents and disable agentaccess token if number
     * is equals and more than 200.
     *
     * @return void
     */
    public function checkAccessStatisticsPerformance()
    {
        $total_agents = db_get_value('count(*)', 'tagente');

        if ($total_agents >= 200) {
            db_process_sql_update('tconfig', ['value' => 0], ['token' => 'agentaccess']);
            $this->notify(
                [
                    'type'    => 'NOTIF.ACCESSSTASTICS.PERFORMANCE',
                    'title'   => __('Access statistics performance'),
                    'message' => __(
                        'Usage of agent access statistics IS NOT RECOMMENDED on systems with more than 200 agents due performance penalty'
                    ),
                    'url'     => '__url__/index.php?sec=general&sec2=godmode/setup/setup&section=perf',
                ]
            );
        } else {
            $this->cleanNotifications('NOTIF.ACCESSSTASTICS.PERFORMANCE');
        }
    }


    /**
     * Update targets for given notification using object targets.
     *
     * @param array   $notification Current notification.
     * @param boolean $send_mails   Only update db targets, no email.
     *
     * @return void
     */
    public function updateTargets(
        array $notification,
        bool $send_mails=true
    ) {
        $notification_id = $notification['id_mensaje'];
        $blacklist = [];

        if (is_array($this->targetUsers) === true
            && count($this->targetUsers) > 0
        ) {
            // Process user targets.
            $insertion_string = '';
            $users_sql = 'INSERT IGNORE INTO tnotification_user(id_mensaje,id_user)';
            foreach ($this->targetUsers as $user) {
                $insertion_string .= sprintf(
                    '(%d,"%s")',
                    $notification_id,
                    $user['id_user']
                );
                $insertion_string .= ',';

                if ($send_mails === true) {
                    // Send mail.
                    if (isset($user['also_mail']) && $user['also_mail'] == 1) {
                        enterprise_hook(
                            'send_email_user',
                            [
                                $user['id_user'],
                                io_safe_output($notification['mensaje']).'<br><hl><br>'.$notification['url'],
                                io_safe_output($notification['subject']),
                            ]
                        );
                        array_push($blacklist, $user['id_user']);
                    }
                }
            }

            $insertion_string = substr($insertion_string, 0, -1);
            db_process_sql($users_sql.' VALUES '.$insertion_string);
        }

        if (is_array($this->targetGroups) === true
            && count($this->targetGroups) > 0
        ) {
            // Process group targets.
            $insertion_string = '';
            $groups_sql = 'INSERT IGNORE INTO tnotification_group(id_mensaje,id_group)';
            foreach ($this->targetGroups as $group) {
                $insertion_string .= sprintf(
                    '(%d,"%s")',
                    $notification_id,
                    $group['id_group']
                );
                $insertion_string .= ',';

                if ($send_mails === true) {
                    // Send mail.
                    if (isset($group['also_mail']) && $group['also_mail'] == 1) {
                        enterprise_hook(
                            'send_email_group',
                            [
                                $group['id_group'],
                                io_safe_output($notification['mensaje']).'<br><hl><br>'.$notification['url'],
                                io_safe_output($notification['subject']),
                                null,
                                $blacklist,
                            ]
                        );
                    }
                }
            }

            $insertion_string = substr($insertion_string, 0, -1);

            db_process_sql($groups_sql.' VALUES '.$insertion_string);
        }

    }


    /**
     * Generates notifications for target users and groups.
     *
     * @param array   $data      Message to be delivered:
     *                  - boolean status (false: notify, true: do not notify)
     *                  - string title
     *                  - string message
     *                  - string url.
     * @param integer $source_id Target source_id, by default $this->sourceId.
     * @param integer $max_age   Maximum age for generated notification.
     *
     * @return void
     */
    public function notify(
        array $data,
        int $source_id=0,
        int $max_age=SECONDS_1DAY
    ) {
        // Uses 'check failed' logic.
        if (is_array($data) === false) {
            // Skip.
            return;
        }

        if ($source_id === 0) {
            $source_id = $this->sourceId;
        }

        static $_cache_targets;
        $key = $source_id.'|'.$data['type'];

        if ($_cache_targets === null) {
            $_cache_targets = [];
        }

        if (isset($_cache_targets[$key]) === true
            && $_cache_targets[$key] !== null
        ) {
            $targets = $_cache_targets[$key];
        } else {
            $targets = get_notification_source_targets(
                $source_id,
                $data['type']
            );
            $this->targetGroups = ($targets['groups'] ?? null);
            $this->targetUsers = ($targets['users'] ?? null);

            $_cache_targets[$key] = $targets;
        }

        switch ($data['type']) {
            case 'NOTIF.LICENSE.LIMITED':
                $max_age = 0;
            break;

            case 'NOTIF.FILES.ATTACHMENT':
            case 'NOTIF.FILES.DATAIN':
            case 'NOTIF.FILES.DATAIN.BADXML':
            case 'NOTIF.PHP.SAFE_MODE':
            case 'NOTIF.PHP.INPUT_TIME':
            case 'NOTIF.PHP.EXECUTION_TIME':
            case 'NOTIF.PHP.UPLOAD_MAX_FILESIZE':
            case 'NOTIF.PHP.MEMORY_LIMIT':
            case 'NOTIF.PHP.DISABLE_FUNCTIONS':
            case 'NOTIF.PHP.CHROMIUM':
            case 'NOTIF.PHP.VERSION':
            case 'NOTIF.HISTORYDB':
            case 'NOTIF.PANDORADB':
            case 'NOTIF.PANDORADB.HISTORICAL':
            case 'NOTIF.HISTORYDB.MR':
            case 'NOTIF.EXT.ELASTICSEARCH':
            case 'NOTIF.METACONSOLE.DB_CONNECTION':
            case 'NOTIF.DOWNTIME':
            case 'NOTIF.UPDATEMANAGER.REGISTRATION':
            case 'NOTIF.MISC.EVENTSTORMPROTECTION':
            case 'NOTIF.MISC.DEVELOPBYPASS':
            case 'NOTIF.MISC.FONTPATH':
            case 'NOTIF.SECURITY.DEFAULT_PASSWORD':
            case 'NOTIF.UPDATEMANAGER.OPENSETUP':
            case 'NOTIF.UPDATEMANAGER.UPDATE':
            case 'NOTIF.UPDATEMANAGER.MINOR':
            case 'NOTIF.UPDATEMANAGER.MESSAGES':
            case 'NOTIF.CRON.CONFIGURED':
            case 'NOTIF.ALLOWOVERRIDE.MESSAGE':
            case 'NOTIF.HAMASTER.MESSAGE':

            default:
                // NOTIF.SERVER.STATUS.
                // NOTIF.SERVER.STATUS.ID_SERVER.
                // NOTIF.SERVER.QUEUE.ID_SERVER.
                // NOTIF.SERVER.MASTER.
                // NOTIF.SERVER.STATUS.ID_SERVER.
                if (preg_match('/^NOTIF.SERVER/', $data['type']) === true) {
                    // Send notification once a day.
                    $max_age = SECONDS_1DAY;
                }

                // Else ignored.
            break;
        }

        // Get previous notification.
        $prev = db_get_row(
            'tmensajes',
            'subtype',
            $data['type'],
            false,
            false
        );

        if ($prev !== false
            && (time() - $prev['timestamp']) > $max_age
        ) {
            // Clean previous notification.
            $this->cleanNotifications($data['type']);
        } else if ($prev !== false) {
            // Avoid creation. Previous notification is still valid.
            // Update message with latest information.
            $r = db_process_sql_update(
                'tmensajes',
                [
                    'mensaje' => io_safe_input($data['message']),
                    'subject' => io_safe_input($data['title']),
                ],
                ['id_mensaje' => $prev['id_mensaje']]
            );
            $this->updateTargets($prev, false);
            return;
        }

        if (isset($data['type']) === false) {
            $data['type'] = '';
        }

        // Create notification.
        $notification = [];
        $notification['timestamp'] = time();
        $notification['id_source'] = $source_id;
        $notification['mensaje'] = io_safe_input($data['message']);
        $notification['subject'] = io_safe_input($data['title']);
        $notification['subtype'] = $data['type'];
        $notification['url'] = io_safe_input($data['url']);

        $id = db_process_sql_insert('tmensajes', $notification);

        if ($id === false) {
            // Failed to generate notification.
            $this->warn('Failed to generate notification');
            return;
        }

        // Update reference to update targets.
        $notification['id_mensaje'] = $id;

        $this->updateTargets($notification);

    }


    /**
     * Deletes useless notifications.
     *
     * @param string $subtype Subtype to be deleted.
     *
     * @return mixed False in case of error or invalid values passed.
     *               Affected rows otherwise
     */
    public function cleanNotifications(string $subtype)
    {
        $not_count = db_get_value_sql(
            sprintf(
                'SELECT count(*) as n
                FROM tmensajes
                WHERE subtype like "%s"',
                $subtype
            )
        );

        if ($not_count > 0) {
            return db_process_sql_delete(
                'tmensajes',
                sprintf('subtype like "%s"', $subtype)
            );
        }

        return true;
    }


    /**
     * Check license status and validity.
     *
     * @return boolean Return true if license is valid, false if not.
     */
    public function checkLicense()
    {
        global $config;

        $license = enterprise_hook('license_get_info');
        if ($license === ENTERPRISE_NOT_HOOK) {
            return false;
        }

        $days_to_expiry = ((strtotime($license['expiry_date']) - time()) / (60 * 60 * 24));

        // Limited mode.
        if (isset($config['limited_mode'])) {
            // Warn user if license is going to expire in 15 days or less.
            $this->notify(
                [
                    'type'    => 'NOTIF.LICENSE.LIMITED',
                    'title'   => __('Limited mode.'),
                    'message' => io_safe_output($config['limited_mode']),
                    'url'     => '__url__/index.php?sec=gsetup&sec2=godmode/setup/license',
                ]
            );
        } else {
            $this->cleanNotifications('NOTIF.LICENSE.LIMITED');
        }

        // Expiry.
        if (($days_to_expiry <= 15) && ($days_to_expiry > 0)
            && ((is_user_admin($config['id_user'])) || (check_acl($config['id_user'], 0, 'PM')))
        ) {
            if ($config['license_mode'] == 1) {
                $title = __('License is about to expire');
                $msg = 'Your license will expire in %d days. Please, contact our sales department.';
            } else {
                $title = __('Support is about to expire');
                $msg = 'Your support license will expire in %d days. Please, contact our sales department.';
            }

            // Warn user if license is going to expire in 15 days or less.
            $this->notify(
                [
                    'type'    => 'NOTIF.LICENSE.EXPIRATION',
                    'title'   => $title,
                    'message' => __(
                        $msg,
                        $days_to_expiry
                    ),
                    'url'     => '__url__/index.php?sec=gsetup&sec2=godmode/setup/license',
                ]
            );
        } else if (($days_to_expiry <= 0) && ((is_user_admin($config['id_user'])) || (check_acl($config['id_user'], 0, 'PM')))) {
            if ($config['license_mode'] == 1) {
                $title = __('Expired license');
                $msg = __('Your license has expired. Please, contact our sales department.');
            } else {
                $title = __('Support expired');
                $msg = __('This license is outside of support. Please, contact our sales department.');
            }

            // Warn user, license has expired.
            $this->notify(
                [
                    'type'    => 'NOTIF.LICENSE.EXPIRATION',
                    'title'   => $title,
                    'message' => $msg,
                    'url'     => '__url__/index.php?sec=gsetup&sec2=godmode/setup/license',
                ]
            );
            return false;
        } else {
            $this->cleanNotifications('NOTIF.LICENSE.EXPIRATION');
        }

        return true;

    }


    /**
     * Count files in target path.
     *
     * @param string  $path      Path to be checked.
     * @param string  $regex     Regular expression to find files.
     * @param integer $max_files Maximum number of files to find.
     *
     * @return integer Number of files in target path.
     */
    public function countFiles(
        string $path='',
        string $regex='',
        int $max_files=500
    ) {
        if (empty($path) === true) {
            return -1;
        }

        $nitems = 0;

        // Count files up to max_files.
        $dir = opendir($path);

        if ($dir !== false) {
            // Used instead of glob to avoid check directories with
            // more than 1M files.
            while (false !== ($file = readdir($dir)) && $nitems <= $max_files) {
                if ($file != '.' && $file != '..') {
                    if (empty($regex) === false) {
                        if (preg_match($regex, $file) === 1) {
                            $nitems++;
                            continue;
                        }
                    } else {
                        $nitems++;
                    }
                }
            }

            closedir($dir);
        }

        return $nitems;
    }


    /**
     * Check excesive files in attachment directory.
     *
     * @return void
     */
    public function checkAttachment()
    {
        global $config;

        if (is_writable($config['attachment_store']) !== true) {
            $this->notify(
                [
                    'type'    => 'NOTIF.WRITABLE.ATTACHMENT',
                    'title'   => __('Attachment directory is not writable'),
                    'message' => __(
                        'Directory %s is not writable. Please, configure corresponding permissions.',
                        $config['attachment_store']
                    ),
                    'url'     => '__url__/index.php?sec=general&sec2=godmode/setup/setup&section=general',
                ]
            );
            return;
        } else {
            $this->cleanNotifications('NOTIF.WRITABLE.ATTACHMENT');
        }

        $filecount = $this->countFiles(
            $config['attachment_store'],
            '',
            $config['num_files_attachment']
        );
        if ($filecount > $config['num_files_attachment']) {
            $this->notify(
                [
                    'type'    => 'NOTIF.FILES.ATTACHMENT',
                    'title'   => __('There are too many files in attachment directory'),
                    'message' => __(
                        'There are more than %d files in attachment, consider cleaning up attachment directory manually.',
                        $config['num_files_attachment']
                    ),
                    'url'     => '__url__/index.php?sec=general&sec2=godmode/setup/setup&section=perf',
                ]
            );
        } else {
            $this->cleanNotifications('NOTIF.FILES.ATTACHMENT');
        }

    }


    /**
     * Check excesive files in data_in directory.
     *
     * @return void
     */
    public function checkDataIn()
    {
        global $config;

        $remote_config_dir = (string) io_safe_output($config['remote_config']);

        if (enterprise_installed()
            && isset($config['license_nms'])
            && $config['license_nms'] != 1
        ) {
            if (is_readable($remote_config_dir) !== true) {
                $this->notify(
                    [
                        'type'    => 'NOTIF.PERMISSIONS.REMOTE_CONFIG',
                        'title'   => __('Remote configuration directory is not readable'),
                        'message' => __(
                            'Remote configuration directory %s is not readable. Please, adjust configuration.',
                            $remote_config_dir
                        ),
                        'url'     => '__url__/index.php?sec=general&sec2=godmode/setup/setup&section=general',
                    ]
                );
                return;
            } else {
                $this->cleanNotifications(
                    'NOTIF.PERMISSIONS.REMOTE_CONFIG'
                );
            }

            if (is_writable($remote_config_dir.'/conf') !== true) {
                $this->notify(
                    [
                        'type'    => 'NOTIF.PERMISSIONS.REMOTE_CONFIG.CONF',
                        'title'   => __('Remote configuration directory is not writable'),
                        'message' => __(
                            'Remote configuration directory %s is not writable. Please, adjust configuration.',
                            $remote_config_dir.'/conf'
                        ),
                        'url'     => '__url__/index.php?sec=general&sec2=godmode/setup/setup&section=general',
                    ]
                );
            } else {
                $this->cleanNotifications(
                    'NOTIF.PERMISSIONS.REMOTE_CONFIG.CONF'
                );
            }

            if (is_writable($remote_config_dir.'/collections') !== true) {
                $this->notify(
                    [
                        'type'    => 'NOTIF.PERMISSIONS.REMOTE_CONFIG.COLLECTIONS',
                        'title'   => __('Remote collections directory is not writable'),
                        'message' => __(
                            'Collections directory %s is not writable. Please, adjust configuration.',
                            $remote_config_dir.'/collections'
                        ),
                        'url'     => '__url__/index.php?sec=general&sec2=godmode/setup/setup&section=general',
                    ]
                );
            } else {
                $this->cleanNotifications(
                    'NOTIF.PERMISSIONS.REMOTE_CONFIG.COLLECTIONS'
                );
            }

            if (is_writable($remote_config_dir.'/md5') !== true) {
                $this->notify(
                    [
                        'type'    => 'NOTIF.PERMISSIONS.REMOTE_CONFIG.MD5',
                        'title'   => __('Remote md5 directory is not writable'),
                        'message' => __(
                            'MD5 directory %s is not writable. Please, adjust configuration.',
                            $remote_config_dir.'/md5'
                        ),
                        'url'     => '__url__/index.php?sec=general&sec2=godmode/setup/setup&section=general',
                    ]
                );
            } else {
                $this->cleanNotifications(
                    'NOTIF.PERMISSIONS.REMOTE_CONFIG.MD5'
                );
            }
        } else {
            $this->cleanNotifications('NOTIF.PERMISSIONS.REMOTE_CONF%');
        }

        $MAX_FILES_DATA_IN = 1000;
        $MAX_BADXML_FILES_DATA_IN = 150;

        $filecount = $this->countFiles(
            $remote_config_dir,
            '',
            $MAX_FILES_DATA_IN
        );
        // If cannot open directory, count is '-1', skip.
        if ($filecount > $MAX_FILES_DATA_IN) {
            $this->notify(
                [
                    'type'    => 'NOTIF.FILES.DATAIN',
                    'title'   => __('There are too much files in spool').'.',
                    'message' => __(
                        'There are more than %d files in %s. Consider checking DataServer performance',
                        $MAX_FILES_DATA_IN,
                        $remote_config_dir
                    ),
                    'url'     => '__url__/index.php?sec=general&sec2=godmode/setup/setup&section=perf',
                ]
            );
        } else {
            $this->cleanNotifications('NOTIF.FILES.DATAIN');
        }

        $filecount = $this->countFiles(
            $remote_config_dir,
            '/^.*BADXML$/',
            $MAX_BADXML_FILES_DATA_IN
        );
        // If cannot open directory, count is '-1', skip.
        if ($filecount > $MAX_BADXML_FILES_DATA_IN) {
            $this->notify(
                [
                    'type'    => 'NOTIF.FILES.DATAIN.BADXML',
                    'title'   => __('There are too many BADXML files in spool'),
                    'message' => __(
                        'There are more than %d files in %s. Consider checking software agents.',
                        $MAX_BADXML_FILES_DATA_IN,
                        $remote_config_dir
                    ),
                    'url'     => '__url__/index.php?sec=general&sec2=godmode/setup/setup&section=perf',
                ]
            );
        } else {
            $this->cleanNotifications('NOTIF.FILES.DATAIN.BADXML');
        }
    }


    /**
     * Check growing queues in servers.
     *
     * @return void
     */
    public function checkServers()
    {
        global $config;

        include_once $config['homedir'].'/include/functions_servers.php';

        $idx_file = $config['attachment_store'].'/.cron.supervisor.servers.idx';

        $MAX_QUEUE = 1500;
        $total_modules = servers_get_total_modules();

        $queue_state = [];
        $previous = [];
        $new = [];

        if (file_exists($idx_file) === true) {
            // Read previous values from file.
            $previous = json_decode(file_get_contents($idx_file), true);
        }

        // DataServer queue status.
        $queue_state = db_get_all_rows_sql(
            'SELECT id_server,name,server_type,queued_modules,status
            FROM tserver ORDER BY 1'
        );

        $time = time();
        if (is_array($queue_state) === true) {
            foreach ($queue_state as $queue) {
                $key = $queue['id_server'];
                $type = $queue['server_type'];
                $new_data[$key] = $queue['queued_modules'];
                $max_grown = 0;

                if (is_array($total_modules)
                    && isset($total_modules[$queue['server_type']])
                ) {
                    $max_grown = ($total_modules[$queue['server_type']] * 0.40);
                }

                if ($total_modules[$queue['server_type']] < self::MIN_PERFORMANCE_MODULES) {
                    $this->cleanNotifications('NOTIF.SERVER.QUEUE.'.$key);
                    // Skip.
                    continue;
                }

                // Compare queue increments in a not over 900 seconds.
                if (empty($previous[$key]['modules'])
                    || ($time - $previous[$key]['utime']) > 900
                ) {
                    $previous[$key]['modules'] = 0;
                }

                $modules_queued = ($queue['queued_modules'] - $previous[$key]['modules']);

                // 40% Modules queued since last check. If any.
                if ($max_grown > 0
                    && $modules_queued > $max_grown
                ) {
                    $msg = 'Queue has grown %d modules. Total %d';
                    if ($modules_queued <= 0) {
                        $msg = 'Queue is decreasing in %d modules. But there are %d queued.';
                        $modules_queued *= -1;
                    }

                    $this->notify(
                        [
                            'type'    => 'NOTIF.SERVER.QUEUE.'.$key,
                            'title'   => __(
                                '%s (%s) is lacking performance.',
                                servers_get_server_string_name($type),
                                $queue['name']
                            ),
                            'message' => __(
                                $msg,
                                $modules_queued,
                                $queue['queued_modules']
                            ),
                            'url'     => '__url__/index.php?sec=gservers&sec2=godmode/servers/modificar_server&refr=60',
                        ]
                    );
                } else {
                    $this->cleanNotifications('NOTIF.SERVER.QUEUE.'.$key);
                }

                $new[$key]['modules'] = $queue['queued_modules'];
                $new[$key]['utime'] = $time;
            }

            // Update file content.
            file_put_contents($idx_file, json_encode($new));
        } else {
            // No queue data, ignore.
            unlink($idx_file);

            // Clean notifications.
            $this->cleanNotifications('NOTIF.SERVER.QUEUE.%');
        }
    }


    /**
     * Check Pandora component statuses.
     *
     * @return void
     */
    public function checkPandoraServers()
    {
        global $config;

        $servers = db_get_all_rows_sql(
            'SELECT
                id_server,
                name,
                server_type,
                server_keepalive,
                status,
                unix_timestamp() - unix_timestamp(keepalive) as downtime
            FROM tserver
            WHERE 
                unix_timestamp() - unix_timestamp(keepalive) > server_keepalive'
        );

        if ($servers === false) {
            $nservers = db_get_value_sql(
                'SELECT count(*) as nservers
                 FROM tserver'
            );
            if ($nservers == 0) {
                $url = 'https://pandorafms.com/manual/en/documentation/02_installation/04_configuration';
                if ($config['language'] == 'es') {
                    $url = 'https://pandorafms.com/manual/es/documentation/02_installation/04_configuration';
                }

                $this->notify(
                    [
                        'type'    => 'NOTIF.SERVER.STATUS',
                        'title'   => __('No servers available.'),
                        'message' => __('There are no servers registered in this console. Please, check installation guide.'),
                        'url'     => $url,
                    ]
                );
            }

            // At this point there's no servers with issues.
            $this->cleanNotifications('NOTIF.SERVER.STATUS%');
            return;
        } else {
            // Clean notifications. Only show notif for down servers
            // ONLY FOR RECOVERED ONES.
            $servers_working = db_get_all_rows_sql(
                'SELECT
                    id_server,
                    name,
                    server_type,
                    server_keepalive,
                    status,
                    unix_timestamp() - unix_timestamp(keepalive) as downtime
                FROM tserver
                WHERE 
                    unix_timestamp() - unix_timestamp(keepalive) <= server_keepalive
                    AND status = 1'
            );
            if (is_array($servers_working) === true) {
                foreach ($servers_working as $server) {
                    $this->cleanNotifications(
                        'NOTIF.SERVER.STATUS.'.$server['id_server']
                    );
                }
            }
        }

        foreach ($servers as $server) {
            if ($server['server_type'] == SERVER_TYPE_ENTERPRISE_SATELLITE) {
                if ($server['downtime'] < ($server['server_keepalive'] * 2)) {
                    // Satellite uses different keepalive mode.
                    continue;
                }
            }

            if ($server['status'] == 1) {
                // Fatal error. Component has die.
                $msg = __(
                    '%s (%s) has crashed.',
                    servers_get_server_string_name($server['server_type']),
                    $server['name']
                );

                $description = __(
                    '%s (%s) has crashed, please check log files.',
                    servers_get_server_string_name($server['server_type']),
                    $server['name']
                );
            } else {
                // Non-fatal error. Controlated exit. Component is not running.
                $msg = __(
                    '%s (%s) is not running.',
                    servers_get_server_string_name($server['server_type']),
                    $server['name']
                );
                $description = __(
                    '%s (%s) is not running. Please, check configuration file or remove this server from server list.',
                    servers_get_server_string_name($server['server_type']),
                    $server['name']
                );
            }

            $this->notify(
                [
                    'type'    => 'NOTIF.SERVER.STATUS.'.$server['id_server'],
                    'title'   => $msg,
                    'message' => $description,
                    'url'     => '__url__/index.php?sec=gservers&sec2=godmode/servers/modificar_server&refr=60',
                ]
            );
        }
    }


    /**
     * Checks if there's at last one server running in master mode.
     *
     * @return void
     */
    public function checkPandoraServerMasterAvailable()
    {
        global $config;

        $n_masters = db_get_value_sql(
            'SELECT
                count(*) as n
            FROM tserver
            WHERE 
                unix_timestamp() - unix_timestamp(keepalive) <= server_keepalive
                AND master > 0
                AND status = 1'
        );

        if ($n_masters === false) {
            // Failed to retrieve server list.
            return;
        }

        if ($n_masters <= 0) {
            // No server running in master.
            $url = 'https://pandorafms.com/manual/en/documentation/02_installation/04_configuration#master';
            if ($config['language'] == 'es') {
                $url = 'https://pandorafms.com/manual/es/documentation/02_installation/04_configuration#master';
            }

            $this->notify(
                [
                    'type'    => 'NOTIF.SERVER.MASTER',
                    'title'   => __('No master servers found.'),
                    'message' => __('At least one server must be defined to run as master. Please, check documentation.'),
                    'url'     => $url,
                ]
            );
        } else {
            $this->cleanNotifications('NOTIF.SERVER.MASTER%');
        }

    }


    /**
     * Checks PHP settings to be correct. Generates system notifications if not.
     *
     * @return void
     */
    public function checkPHPSettings()
    {
        global $config;

        $PHPupload_max_filesize = config_return_in_bytes(
            ini_get('upload_max_filesize')
        );

        // PHP configuration.
        $PHPmax_input_time = ini_get('max_input_time');
        $PHPmemory_limit = config_return_in_bytes(ini_get('memory_limit'));
        $PHPmax_execution_time = ini_get('max_execution_time');
        $PHPsafe_mode = ini_get('safe_mode');
        $PHPdisable_functions = ini_get('disable_functions');
        $PHPupload_max_filesize_min = config_return_in_bytes('800M');
        $PHPmemory_limit_min = config_return_in_bytes('800M');
        $PHPSerialize_precision = ini_get('serialize_precision');

        if (is_metaconsole() === true) {
            $PHPmemory_limit_min = config_return_in_bytes('-1');
        }

        // Chromium status.
        $chromium_dir = io_safe_output($config['chromium_path']);
        $result_ejecution = exec($chromium_dir.' --version');

        // PHP version checks.
        $php_version = phpversion();
        $php_version_array = explode('.', $php_version);

        if ($PHPsafe_mode === '1') {
            $url = 'http://php.net/manual/en/features.safe-mode.php';
            if ($config['language'] == 'es') {
                $url = 'http://php.net/manual/es/features.safe-mode.php';
            }

            $this->notify(
                [
                    'type'    => 'NOTIF.PHP.SAFE_MODE',
                    'title'   => __('PHP safe mode is enabled. Some features may not work properly'),
                    'message' => __('To disable it, go to your PHP configuration file (php.ini) and put safe_mode = Off (Do not forget to restart apache process after changes)'),
                    'url'     => $url,
                ]
            );
        } else {
            $this->cleanNotifications('NOTIF.PHP.SAFE_MODE');
        }

        if ($PHPmax_input_time !== '-1') {
            $url = 'http://php.net/manual/en/info.configuration.php#ini.max-input-time';
            if ($config['language'] == 'es') {
                $url = 'http://php.net/manual/es/info.configuration.php#ini.max-input-time';
            }

            $this->notify(
                [
                    'type'    => 'NOTIF.PHP.INPUT_TIME',
                    'title'   => sprintf(
                        __('%s value in PHP configuration is not recommended'),
                        'max_input_time'
                    ),
                    'message' => sprintf(
                        __('Recommended value is %s'),
                        '-1 ('.__('Unlimited').')'
                    ).'<br><br>'.__('Please, change it on your PHP configuration file (php.ini) or contact with administrator (Do not forget to restart Apache process after)'),
                    'url'     => $url,
                ]
            );
        } else {
            $this->cleanNotifications('NOTIF.PHP.INPUT_TIME');
        }

        if ((int) $PHPmax_execution_time !== 0) {
            $url = 'http://php.net/manual/en/info.configuration.php#ini.max-execution-time';
            if ($config['language'] == 'es') {
                $url = 'http://php.net/manual/es/info.configuration.php#ini.max-execution-time';
            }

            $this->notify(
                [
                    'type'    => 'NOTIF.PHP.EXECUTION_TIME',
                    'title'   => sprintf(
                        __("Not recommended '%s' value in PHP configuration"),
                        'max_execution_time'
                    ),
                    'message' => sprintf(
                        __('Recommended value is: %s'),
                        '0 ('.__('Unlimited').')'
                    ).'<br><br>'.__('Please, change it on your PHP configuration file (php.ini) or contact with administrator (Dont forget restart apache process after changes)'),
                    'url'     => $url,
                ]
            );
        } else {
            $this->cleanNotifications('NOTIF.PHP.EXECUTION_TIME');
        }

        if ($PHPupload_max_filesize < $PHPupload_max_filesize_min) {
            $url = 'http://php.net/manual/en/ini.core.php#ini.upload-max-filesize';
            if ($config['language'] == 'es') {
                $url = 'http://php.net/manual/es/ini.core.php#ini.upload-max-filesize';
            }

            $this->notify(
                [
                    'type'    => 'NOTIF.PHP.UPLOAD_MAX_FILESIZE',
                    'title'   => sprintf(
                        __("Not recommended '%s' value in PHP configuration"),
                        'upload_max_filesize'
                    ),
                    'message' => sprintf(
                        __('Recommended value is: %s'),
                        sprintf(__('%s or greater'), '800M')
                    ).'<br><br>'.__('Please, change it on your PHP configuration file (php.ini) or contact with administrator (Dont forget restart apache process after changes)'),
                    'url'     => $url,
                ]
            );
        } else {
            $this->cleanNotifications('NOTIF.PHP.UPLOAD_MAX_FILESIZE');
        }

        if ($PHPmemory_limit < $PHPmemory_limit_min && (int) $PHPmemory_limit !== -1) {
            $url = 'http://php.net/manual/en/ini.core.php#ini.memory-limit';
            if ($config['language'] == 'es') {
                $url = 'http://php.net/manual/es/ini.core.php#ini.memory-limit';
            }

            $recommended_memory = '800M';
            if (is_metaconsole() === true) {
                $recommended_memory = '-1';
            }

            $this->notify(
                [
                    'type'    => 'NOTIF.PHP.MEMORY_LIMIT',
                    'title'   => sprintf(
                        __("Not recommended '%s' value in PHP configuration"),
                        'memory_limit'
                    ),
                    'message' => sprintf(
                        __('Recommended value is: %s'),
                        sprintf(__('%s or greater'), $recommended_memory)
                    ).'<br><br>'.__('Please, change it on your PHP configuration file (php.ini) or contact with administrator'),
                    'url'     => $url,
                ]
            );
        } else {
            $this->cleanNotifications('NOTIF.PHP.MEMORY_LIMIT');
        }

        if (preg_match('/system/', $PHPdisable_functions) || preg_match('/exec/', $PHPdisable_functions)) {
            $url = 'http://php.net/manual/en/ini.core.php#ini.disable-functions';
            if ($config['language'] == 'es') {
                $url = 'http://php.net/manual/es/ini.core.php#ini.disable-functions';
            }

            $this->notify(
                [
                    'type'    => 'NOTIF.PHP.DISABLE_FUNCTIONS',
                    'title'   => __('Problems with disable_functions in php.ini'),
                    'message' => __('The variable disable_functions contains functions system() or exec() in PHP configuration file (php.ini)').'<br /><br />'.__('Please, change it on your PHP configuration file (php.ini) or contact with administrator (Dont forget restart apache process after changes)'),
                    'url'     => $url,
                ]
            );
        } else {
            $this->cleanNotifications('NOTIF.PHP.DISABLE_FUNCTIONS');
        }

        if (!isset($result_ejecution) || $result_ejecution == '') {
            $url = 'https://www.chromium.org/getting-involved/download-chromium/';
            // if ($config['language'] == 'es') {
            // $url = 'https://pandorafms.com/manual/es/documentation/02_installation/04_configuration#Phantomjs';
            // }
            $this->notify(
                [
                    'type'    => 'NOTIF.PHP.CHROMIUM',
                    'title'   => __('chromium is not installed'),
                    'message' => __('To be able to create images of the graphs for PDFs, please install the chromium extension. For that, it is necessary to follow these steps:'),
                    'url'     => $url,
                ]
            );
        } else {
            $this->cleanNotifications('NOTIF.PHP.CHROMIUM');
        }

        if ($php_version_array[0] < 8) {
            $url = 'https://pandorafms.com/manual/en/documentation/07_technical_annexes/18_php_8';
            if ($config['language'] == 'es') {
                $url = 'https://pandorafms.com/manual/es/documentation/07_technical_annexes/18_php_8';
            }

            if ($config['language'] == 'ja') {
                $url = 'https://pandorafms.com/manual/ja/documentation/07_technical_annexes/18_php_8';
            }

            $this->notify(
                [
                    'type'    => 'NOTIF.PHP.VERSION',
                    'title'   => __('PHP UPDATE REQUIRED'),
                    'message' => __('For a correct operation of PandoraFMS, PHP must be updated to version 8.0 or higher.').'<br>'.__('Otherwise, functionalities will be lost.').'<br>'."<ol><li class='color_67'>".__('Report download in PDF format').'</li>'."<li class='color_67'>".__('Emails Sending').'</li><li class="color_67">'.__('Metaconsole Collections').'</li><li class="color_67">...</li></ol>',
                    'url'     => $url,
                ]
            );
        } else {
            $this->cleanNotifications('NOTIF.PHP.VERSION');
        }

        if ($PHPSerialize_precision != -1) {
            $url = 'https://www.php.net/manual/en/ini.core.php#ini.serialize-precision';
            if ($config['language'] == 'es') {
                $url = 'https://www.php.net/manual/es/ini.core.php#ini.serialize-precision';
            }

            $this->notify(
                [
                    'type'    => 'NOTIF.PHP.SERIALIZE_PRECISION',
                    'title'   => sprintf(
                        __("Not recommended '%s' value in PHP configuration"),
                        'serialize_precision'
                    ),
                    'message' => sprintf(
                        __('Recommended value is: %s'),
                        sprintf('-1')
                    ).'<br><br>'.__('Please, change it on your PHP configuration file (php.ini) or contact with administrator'),
                    'url'     => $url,
                ]
            );
        } else {
            $this->cleanNotifications('NOTIF.PHP.SERIALIZE_PRECISION');
        }

    }


    /**
     * Checks if history DB is available.
     *
     * @return void
     */
    public function checkPandoraHistoryDB()
    {
        global $config;

        if (isset($config['history_db_enabled'])
            && $config['history_db_enabled'] == 1
        ) {
            if (! isset($config['history_db_connection'])
                || $config['history_db_connection'] === false
            ) {
                ob_start();
                $config['history_db_connection'] = db_connect(
                    $config['history_db_host'],
                    $config['history_db_name'],
                    $config['history_db_user'],
                    io_output_password($config['history_db_pass']),
                    $config['history_db_port'],
                    false
                );
                ob_get_clean();
            }

            if ($config['history_db_connection'] === false) {
                $this->notify(
                    [
                        'type'    => 'NOTIF.HISTORYDB',
                        'title'   => __('Historical database not available'),
                        'message' => __('Historical database is enabled, though not accessible with the current configuration.'),
                        'url'     => '__url__/index.php?sec=general&sec2=godmode/setup/setup&section=hist_db',
                    ]
                );
            } else {
                $this->cleanNotifications('NOTIF.HISTORYDB');
            }
        } else {
            $this->cleanNotifications('NOTIF.HISTORYDB');
        }
    }


    /**
     * Check if pandora_db is running in all available DB instances.
     * Generating notifications.
     *
     * @return void
     */
    public function checkPandoraDBMaintenance()
    {
        global $config;

        // Main DB db_maintenance value.
        $db_maintance = db_get_value(
            'value',
            'tconfig',
            'token',
            'db_maintance'
        );

        // If never was executed, it means we are in the first Pandora FMS execution. Set current timestamp.
        if (empty($db_maintance)) {
            config_update_value('db_maintance', date('U'));
        }

        $last_maintance = (date('U') - $db_maintance);

        // Limit 48h.
        if ($last_maintance > 172800) {
            $this->notify(
                [
                    'type'    => 'NOTIF.PANDORADB',
                    'title'   => __('Database maintenance problem'),
                    'message' => __(
                        'Your database hasn\'t been through maintenance for 48hrs. Please, check documentation on how to perform this maintenance process on %s and enable it as soon as possible.',
                        io_safe_output(get_product_name())
                    ),
                    'url'     => '__url__/index.php?sec=general&sec2=godmode/setup/setup&section=perf',
                ]
            );
        } else {
            $this->cleanNotifications('NOTIF.PANDORADB');
        }

        if (isset($config['history_db_enabled'])
            && $config['history_db_enabled'] == 1
        ) {
            // History DB db_maintenance value.
            $db_maintenance = db_get_value(
                'value',
                'tconfig',
                'token',
                'db_maintenance',
                true
            );

            // History db connection is supossed to be enabled since we use
            // db_get_value, wich initializes target db connection.
            if (empty($db_maintance)) {
                $sql = sprintf(
                    'UPDATE tconfig SET `value`=%d WHERE `token`="%s"',
                    date('U'),
                    'db_maintenance'
                );
                $affected_rows = db_process_sql(
                    $sql,
                    $rettype = 'affected_rows',
                    $dbconnection = $config['history_db_connection']
                );

                if ($affected_rows == 0) {
                        // Failed to update. Maybe the row does not exist?
                        $sql = sprintf(
                            'INSERT INTO tconfig(`token`,`value`) VALUES("%s",%d)',
                            'db_maintenance',
                            date('U')
                        );

                        $affected_rows = db_process_sql(
                            $sql,
                            $rettype = 'affected_rows',
                            $dbconnection = $config['history_db_connection']
                        );
                }
            }

            $last_maintance = (date('U') - $db_maintance);

            // Limit 48h.
            if ($last_maintance > 172800) {
                $this->notify(
                    [
                        'type'    => 'NOTIF.PANDORADB.HISTORY',
                        'title'   => __(
                            'Historical database maintenance problem.'
                        ),
                        'message' => __('Your historical database hasn\'t been through maintenance for 48hrs. Please, check documentation on how to perform this maintenance process on %s and enable it as soon as possible.', get_product_name()),
                        'url'     => '__url__/index.php?sec=general&sec2=godmode/setup/setup&section=perf',
                    ]
                );
            } else {
                // Historical db working fine.
                $this->cleanNotifications('NOTIF.PANDORADB.HISTORY');
            }
        } else {
            // Disabled historical db.
            $this->cleanNotifications('NOTIF.PANDORADB.HISTORY');
        }
    }


    /**
     * Check MR package applied in historical DB
     *
     * @return void
     */
    public function checkPandoraHistoryDBMR()
    {
        global $config;

        if (isset($config['history_db_enabled'])
            && $config['history_db_enabled'] == 1
        ) {
            $mrh_version = db_get_value(
                'value',
                'tconfig',
                'token',
                'MR',
                true
            );
            if ($mrh_version != $config['MR']) {
                $this->notify(
                    [
                        'type'    => 'NOTIF.HISTORYDB.MR',
                        'title'   => __('Historical database MR mismatch'),
                        'message' => __('Your historical database is not using the same schema as the main DB. This could produce anomalies while storing historical data.'),
                        'url'     => '__url__/index.php?sec=general&sec2=godmode/setup/setup&section=hist_db',
                    ]
                );
            } else {
                // MR version OK.
                $this->cleanNotifications('NOTIF.HISTORYDB.MR');
            }
        } else {
            // Disabled historical db.
            $this->cleanNotifications('NOTIF.HISTORYDB.MR');
        }
    }


    /**
     * Check if elasticsearch is available.
     *
     * @return void
     */
    public function checkExternalComponents()
    {
        global $config;

        // Cannot check selenium, configuration is only available from server.
        if (isset($config['log_collector'])
            && $config['log_collector'] == 1
        ) {
            $elasticsearch = @fsockopen(
                $config['elasticsearch_ip'],
                $config['elasticsearch_port'],
                $errno,
                $errstr,
                5
            );

            if ($elasticsearch === false) {
                $this->notify(
                    [
                        'type'    => 'NOTIF.EXT.ELASTICSEARCH',
                        'title'   => __('Log collector cannot connect to ElasticSearch'),
                        'message' => __('ElasticSearch is not available using current configuration.'),
                        'url'     => '__url__/index.php?sec=general&sec2=godmode/setup/setup&section=log',
                    ]
                );
            } else {
                fclose($elasticsearch);
                $this->cleanNotifications('NOTIF.EXT.ELASTICSEARCH');
            }
        } else {
            $this->cleanNotifications('NOTIF.EXT.ELASTICSEARCH');
        }

    }


    /**
     * Checks if metaconsole DB connection is ready.
     *
     * @return void
     */
    public function checkMetaconsole()
    {
        global $config;

        $check_ok = true;

        if (license_free()
            && is_metaconsole() === false
            && isset($config['node_metaconsole'])
            && $config['node_metaconsole'] == 1
        ) {
            // Check if node is successfully registered in MC.
            $server_name = db_get_value(
                'distinct(name)',
                'tserver',
                'server_type',
                1
            );

            $mc_db_conn = enterprise_hook(
                'metaconsole_load_external_db',
                [
                    [
                        'dbhost' => $config['replication_dbhost'],
                        'dbuser' => $config['replication_dbuser'],
                        'dbpass' => io_output_password(
                            $config['replication_dbpass']
                        ),
                        'dbname' => $config['replication_dbname'],
                    ],
                ]
            );

            if ($mc_db_conn === NOERR) {
                $check_ok = true;
            } else {
                $check_ok = false;
            }

            // Restore the default connection.
            enterprise_hook('metaconsole_restore_db');
        }

        if ($check_ok === true) {
            $this->cleanNotifications('NOTIF.METACONSOLE.DB_CONNECTION');
        } else {
            $this->notify(
                [
                    'type'    => 'NOTIF.METACONSOLE.DB_CONNECTION',
                    'title'   => __('Metaconsole DB is not available.'),
                    'message' => __('Cannot connect with Metaconsole DB using current configuration.'),
                    'url'     => '__url__/index.php?sec=general&sec2=godmode/setup/setup&section=enterprise',
                ]
            );
        }
    }


    /**
     * Check if there are any incoming scheduled downtime in less than 15d.
     *
     * @return void
     */
    public function checkDowntimes()
    {
        // 15 Days.
        $THRESHOLD_SECONDS = (15 * 3600 * 24);

        // Check first if any planned runtime is running.
        $currently_running = (int) db_get_value_sql(
            'SELECT count(*) as "n" FROM tplanned_downtime
            WHERE executed = 1'
        );

        if ($currently_running > 0) {
            $this->notify(
                [
                    'type'    => 'NOTIF.DOWNTIME',
                    'title'   => __('Scheduled downtime running.'),
                    'message' => __('A scheduled downtime is running. Some monitoring data won\'t be available while downtime is taking place.'),
                    'url'     => '__url__/index.php?sec=gagente&sec2=godmode/agentes/planned_downtime.list',
                ]
            );
            return;
        } else {
            // Retrieve downtimes.
            $downtimes = db_get_all_rows_sql(
                'SELECT * FROM tplanned_downtime
                WHERE 
                (type_execution="once" AND date_from > now())
                OR type_execution!="once" ORDER BY `id` DESC'
            );

            // Initialize searchers.
            $next_downtime_begin = PHP_INT_MAX;
            $now = time();

            if ($downtimes === false) {
                $this->cleanNotifications('NOTIF.DOWNTIME');
                return;
            }

            $weekdays = [
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
                'saturday',
                'sunday',
            ];

            foreach ($downtimes as $dt) {
                if ($dt['type_execution'] == 'once'
                    && ($dt['date_from'] - $now) < $THRESHOLD_SECONDS
                ) {
                    if ($next_downtime_begin > $dt['date_from']) {
                        // Store datetime for next downtime.
                        $next_downtime_begin = $dt['date_from'];
                        $next_downtime_end = $dt['date_to'];
                    }
                } else if ($dt['type_periodicity'] == 'monthly') {
                    $schd_time_begin = explode(
                        ':',
                        $dt['periodically_time_from']
                    );
                    $schd_time_end = explode(
                        ':',
                        $dt['periodically_time_to']
                    );

                    $begin = mktime(
                        // Hour.
                        $schd_time_begin[0],
                        // Minute.
                        $schd_time_begin[1],
                        // Second.
                        $schd_time_begin[2],
                        // Month.
                        date('n', $now),
                        // Day.
                        $dt['periodically_day_from'],
                        // Year.
                        date('Y', $now)
                    );

                    $end = mktime(
                        // Hour.
                        $schd_time_end[0],
                        // Minute.
                        $schd_time_end[1],
                        // Second.
                        $schd_time_end[2],
                        // Month.
                        date('n', $now),
                        // Day.
                        $dt['periodically_day_to'],
                        // Year.
                        date('Y', $now)
                    );

                    if ($next_downtime_begin > $begin) {
                        $next_downtime_begin = $begin;
                        $next_downtime_end = $end;
                    }
                } else if ($dt['type_periodicity'] == 'weekly') {
                    // Always applies.
                    $current_week_day = date('N', $now);

                    $schd_time_begin = explode(
                        ':',
                        $dt['periodically_time_from']
                    );
                    $schd_time_end = explode(
                        ':',
                        $dt['periodically_time_to']
                    );

                    $i = 0;
                    $max = 7;
                    while ($dt[$weekdays[(($current_week_day + $i) % 7)]] != 1
                    && $max-- >= 0
                    ) {
                        // Calculate day of the week matching downtime
                        // definition.
                        $i++;
                    }

                    if ($max < 0) {
                        // No days set.
                        continue;
                    }

                    // Calculate utimestamp.
                    $begin = mktime(
                        // Hour.
                        $schd_time_begin[0],
                        // Minute.
                        $schd_time_begin[1],
                        // Second.
                        $schd_time_begin[2],
                        // Month.
                        date('n', $now),
                        // Day.
                        (date('j', $now) + $i + 1),
                        // Year.
                        date('Y', $now)
                    );

                    $end = mktime(
                        // Hour.
                        $schd_time_end[0],
                        // Minute.
                        $schd_time_end[1],
                        // Second.
                        $schd_time_end[2],
                        // Month.
                        date('n', $now),
                        // Day.
                        (date('j', $now) + $i + 1),
                        // Year.
                        date('Y', $now)
                    );

                    if ($next_downtime_begin > $begin) {
                        $next_downtime_begin = $begin;
                        $next_downtime_end = $end;
                    }
                }
            }

            if ($next_downtime_begin != PHP_INT_MAX) {
                $this->notify(
                    [
                        'type'    => 'NOTIF.DOWNTIME',
                        'title'   => __('Downtime scheduled soon.'),
                        'message' => __(
                            'A scheduled downtime is going to be executed from %s to %s. Some monitoring data won\'t be available while downtime is taking place.',
                            date('M j, G:i:s ', $next_downtime_begin),
                            date('M j, G:i:s ', $next_downtime_end)
                        ),
                        'url'     => '__url__/index.php?sec=gagente&sec2=godmode/agentes/planned_downtime.list',
                    ]
                );
                return;
            } else {
                $this->cleanNotifications('NOTIF.DOWNTIME');
            }
        }
    }


    /**
     * Check if current instance of Pandora FMS is registered in Update Manager.
     *
     * @return void
     */
    public function checkUpdateManagerRegistration()
    {
        global $config;
        include_once $config['homedir'].'/include/functions_update_manager.php';
        $login = get_parameter('login', false);

        if (update_manager_verify_registration() === false) {
            $this->notify(
                [
                    'type'    => 'NOTIF.UPDATEMANAGER.REGISTRATION',
                    'title'   => __('This instance is not registered in the Update manager section'),
                    'message' => __('Click here to start the registration process'),
                    'url'     => '__url__/index.php?sec=messages&sec2=godmode/update_manager/update_manager&tab=online',
                ]
            );
        } else {
            $this->cleanNotifications('NOTIF.UPDATEMANAGER.REGISTRATION');
        }
    }


    /**
     * Check if user 'admin' is enabled and using default password.
     *
     * @return void
     */
    public function checkDefaultPassword()
    {
        global $config;
        // Check default password for "admin".
        $admin_with_default_pass = db_get_value_sql(
            'SELECT count(*) FROM tusuario
            WHERE
                id_user="admin"
                AND password="1da7ee7d45b96d0e1f45ee4ee23da560"
                AND is_admin=1
                and disabled!=1'
        );

        if ($admin_with_default_pass > 0) {
            $this->notify(
                [
                    'type'    => 'NOTIF.SECURITY.DEFAULT_PASSWORD',
                    'title'   => __('Default password for "Admin" user has not been changed'),
                    'message' => __('Please, change the default password since it is a commonly reported vulnerability.'),
                    'url'     => '__url__/index.php?sec=gusuarios&sec2=godmode/users/user_list',
                ]
            );
        } else {
            $this->cleanNotifications('NOTIF.SECURITY.DEFAULT_PASSWORD');
        }
    }


    /**
     * Undocumented function
     *
     * @return void
     */
    public function checkFont()
    {
        global $config;

        $fontpath = io_safe_output($config['fontpath']);

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows.
            $fontpath = $config['homedir'].'\include\fonts\\'.$fontpath;
        } else {
            $home = str_replace('\\', '/', $config['homedir']);
            $fontpath = $home.'/include/fonts/'.$fontpath;
        }

        if (($fontpath == '')
            || (file_exists($fontpath) === false)
        ) {
            $this->notify(
                [
                    'type'    => 'NOTIF.MISC.FONTPATH',
                    'title'   => __('Default font doesn\'t exist'),
                    'message' => __('Your defined font doesn\'t exist or is not defined. Please, check font parameters in your config'),
                    'url'     => is_metaconsole() === false
                                    ? '__url__/index.php?sec=gsetup&sec2=godmode/setup/setup&section=vis'
                                    : '__url__/index.php?sec=advanced&sec2=advanced/metasetup&tab=visual',
                ]
            );
        } else {
            $this->cleanNotifications('NOTIF.MISC.FONTPATH');
        }
    }


    /**
     * Checks if develop_bypass is enabbled.
     *
     * @return void
     */
    public function checkDevelopBypass()
    {
        global $develop_bypass;

        if ($develop_bypass == 1) {
            $this->notify(
                [
                    'type'    => 'NOTIF.MISC.DEVELOPBYPASS',
                    'title'   => __('Developer mode is enabled'),
                    'message' => __(
                        'Your %s has the "develop_bypass" mode enabled. This is a developer mode and should be disabled in a production environment. This value is located in the main index.php file',
                        get_product_name()
                    ),
                    'url'     => '__url__/index.php',
                ]
            );
        } else {
            $this->cleanNotifications('NOTIF.MISC.DEVELOPBYPASS');
        }
    }


    /**
     * Check if event storm protection is enabled.
     *
     * @return void
     */
    public function checkEventStormProtection()
    {
        global $config;
        if ($config['event_storm_protection']) {
            $this->notify(
                [
                    'type'    => 'NOTIF.MISC.EVENTSTORMPROTECTION',
                    'title'   => __('Event storm protection is enabled.'),
                    'message' => __('Some events may get lost while this mode is enabled. The server must be restarted after altering this setting.'),
                    'url'     => '__url__/index.php?sec=gsetup&sec2=godmode/setup/setup&section=general',
                ]
            );
        } else {
            $this->cleanNotifications('NOTIF.MISC.EVENTSTORMPROTECTION');
        }
    }


    /**
     * Check if there're new updates available.
     *
     * @return void
     */
    public function checkUpdates()
    {
        global $config;

        if (isset($_SESSION['new_update'])) {
            if (!empty($_SESSION['return_installation_open'])) {
                if (!$_SESSION['return_installation_open']['return']) {
                    foreach ($_SESSION['return_installation_open']['text'] as $message) {
                        $this->notify(
                            [
                                'type'    => 'NOTIF.UPDATEMANAGER.OPENSETUP',
                                'title'   => __('Failed to retrieve updates, please configure utility'),
                                'message' => $message,
                                'url'     => '__url__/index.php?sec=gsetup&sec2=godmode/setup/setup&section=general',
                            ]
                        );
                    }
                } else {
                    $this->cleanNotifications('NOTIF.UPDATEMANAGER.OPENSETUP');
                }
            } else {
                $this->cleanNotifications('NOTIF.UPDATEMANAGER.OPENSETUP');
            }

            if ($_SESSION['new_update'] == 'new') {
                $this->notify(
                    [
                        'type'    => 'NOTIF.UPDATEMANAGER.UPDATE',
                        'title'   => __(
                            'New %s Console update',
                            get_product_name()
                        ),
                        'message' => __('There is a new update available. Please<a class="bolder" href="'.ui_get_full_url('index.php?sec=gsetup&sec2=godmode/update_manager/update_manager&tab=online').'"> go to Administration:Setup:Update Manager</a> for more details.'),
                        'url'     => '__url__/index.php?sec=gsetup&sec2=godmode/update_manager/update_manager&tab=online',
                    ]
                );
            } else {
                $this->cleanNotifications('NOTIF.UPDATEMANAGER.UPDATE');
            }
        } else {
            $this->cleanNotifications('NOTIF.UPDATEMANAGER.OPENSETUP');
            $this->cleanNotifications('NOTIF.UPDATEMANAGER.UPDATE');
        }
    }


    /**
     * Check if there're minor updates available.
     *
     * @return void
     */
    public function checkMinorRelease()
    {
        global $config;

        $check_minor_release_available = db_check_minor_relase_available();

        if ($check_minor_release_available) {
            $url = 'https://pandorafms.com/manual/es/documentation/02_installation/02_anexo_upgrade#version_70ng_rolling_release';
            if ($config['language'] == 'es') {
                $url = 'https://pandorafms.com/manual/en/documentation/02_installation/02_anexo_upgrade#version_70ng_rolling_release';
            }

            $this->notify(
                [
                    'type'    => 'NOTIF.UPDATEMANAGER.MINOR',
                    'title'   => __('Minor release/s available'),
                    'message' => __(
                        'There is one or more minor releases available. <a id="aviable_updates" target="blank" href="%s">.About minor release update</a>.',
                        $url
                    ),
                    'url'     => '__url__/index.php?sec=messages&sec2=godmode/update_manager/update_manager&tab=online',
                ]
            );
        } else {
            $this->cleanNotifications('NOTIF.UPDATEMANAGER.MINOR');
        }

    }


    /**
     * Check if CRON utility has been configured.
     *
     * @return void
     */
    public function checkCronRunning()
    {
        global $config;

        // Check if DiscoveryCronTasks is running. Warn user if not.
        if ($config['cron_last_run'] == 0
            || (get_system_time() - $config['cron_last_run']) > SECONDS_10MINUTES
        ) {
            $message_conf_cron = __('DiscoveryConsoleTasks is not running properly');
            if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN') {
                $message_conf_cron .= __('Discovery relies on an appropriate cron setup.');
                $message_conf_cron .= '. '.__('Please, add the following line to your crontab file:');
                $message_conf_cron .= '<b><pre class=""ui-dialog>* * * * * &lt;user&gt; wget -q -O - --no-check-certificate --load-cookies /tmp/cron-session-cookies --save-cookies /tmp/cron-session-cookies --keep-session-cookies ';
                $message_conf_cron .= str_replace(
                    ENTERPRISE_DIR.'/meta/',
                    '',
                    ui_get_full_url(false)
                );
                $message_conf_cron .= ENTERPRISE_DIR.'/'.EXTENSIONS_DIR;
                $message_conf_cron .= '/cron/cron.php &gt;&gt; </pre>';
                $message_conf_cron .= $config['homedir'].'/log/cron.log</pre>';
            }

            if (isset($config['cron_last_run']) === true) {
                $message_conf_cron .= __('Last execution').': ';
                $message_conf_cron .= date('Y/m/d H:i:s', $config['cron_last_run']);
                $message_conf_cron .= __('Please, make sure process is not locked.');
            }

            $url = '__url__/index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=tasklist';
            if (is_metaconsole() === true) {
                $url = '__url__index.php?sec=extensions&sec2=enterprise/extensions/cron';
            }

            $this->notify(
                [
                    'type'    => 'NOTIF.CRON.CONFIGURED',
                    'title'   => __('DiscoveryConsoleTasks is not configured.'),
                    'message' => __($message_conf_cron),
                    'url'     => $url,
                ]
            );
        } else {
            $this->cleanNotifications('NOTIF.CRON.CONFIGURED');
        }

    }


    /**
     * Search for messages.
     *
     * @return void
     */
    public function getUMMessages()
    {
        global $config;
        include_once $config['homedir'].'/include/functions_update_manager.php';

        if (update_manager_verify_registration() === false) {
            // Console not subscribed.
            return;
        }

        // Avoid contact for messages too much often.
        if (isset($config['last_um_check']) === true
            && time() < $config['last_um_check']
        ) {
            return;
        }

        // Only ask for messages once every 2 hours.
        $future = (time() + 2 * SECONDS_1HOUR);
        config_update_value('last_um_check', $future, true);

        $messages = update_manager_get_messages();

        if (is_array($messages) === true) {
            $source_id = get_notification_source_id(
                'Official&#x20;communication'
            );
            foreach ($messages as $message) {
                if (isset($message['url']) === false) {
                    $message['url'] = '#';
                }

                $this->notify(
                    [
                        'type'    => 'NOTIF.UPDATEMANAGER.MESSAGES.'.$message['id'],
                        'title'   => $message['subject'],
                        'message' => base64_decode($message['message_html']),
                        'url'     => $message['url'],
                    ],
                    $source_id
                );
            }
        }
    }


    /**
     * Check if all servers and console versions are the same
     *
     * @return void
     */
    public function checkConsoleServerVersions()
    {
        global $config;
        // List all servers except satellite server.
        $server_version_list = db_get_all_rows_sql(
            sprintf(
                'SELECT `name`, `version`
                FROM tserver
                WHERE server_type != %d
                GROUP BY `version`',
                SERVER_TYPE_ENTERPRISE_SATELLITE
            )
        );
        $missed = 0;
        if (is_array($server_version_list) === true) {
            foreach ($server_version_list as $server) {
                if (strpos(
                    $server['version'],
                    (string) floor((int) $config['current_package'])
                ) === false
                ) {
                    $missed++;
                    $title_ver_misaligned = __(
                        '%s version misaligned with Console',
                        $server['name']
                    );
                    $message_ver_misaligned = __(
                        'Server %s and this console have different versions. This might cause several malfunctions. Please, update this server.',
                        $server['name']
                    );

                    $this->notify(
                        [
                            'type'    => 'NOTIF.SERVER.MISALIGNED',
                            'title'   => __($title_ver_misaligned),
                            'message' => __($message_ver_misaligned),
                            'url'     => '__url__/index.php?sec=messages&sec2=godmode/update_manager/update_manager&tab=online',
                        ]
                    );

                    break;
                }
            }
        }

        // Cleanup notifications if exception is recovered.
        if ($missed == 0) {
            $this->cleanNotifications('NOTIF.SERVER.MISALIGNED');
        }
    }


    /**
     * Check if AllowOveride is None or All.
     *
     * @return void
     */
    public function checkAllowOverrideEnabled()
    {
        global $config;

        $message = 'If AllowOverride is disabled, .htaccess will not works.';
        if (PHP_OS == 'FreeBSD') {
            $message .= '<pre>Please check /usr/local/etc/apache24/httpd.conf to resolve this problem.';
        } else {
            $message .= '<pre>Please check /etc/httpd/conf/httpd.conf to resolve this problem.';
        }

        // Get content file.
        if (PHP_OS == 'FreeBSD') {
            $file = file_get_contents('/usr/local/etc/apache24/httpd.conf');
        } else {
            $file = file_get_contents('/etc/httpd/conf/httpd.conf');
        }

        $file_lines = preg_split("#\r?\n#", $file, -1, PREG_SPLIT_NO_EMPTY);
        $is_none = false;

        $i = 0;
        foreach ($file_lines as $line) {
            $i++;

            // Check Line and content.
            if (preg_match('/ AllowOverride/', $line) && $i === 311) {
                $result = explode(' ', $line);
                if ($result[5] == 'None') {
                    $is_none = true;
                    $this->notify(
                        [
                            'type'    => 'NOTIF.ALLOWOVERRIDE.MESSAGE',
                            'title'   => __('AllowOverride is disabled'),
                            'message' => __($message),
                            'url'     => '__url__/index.php',
                        ]
                    );
                }
            }
        }

        // Cleanup notifications if AllowOverride is All.
        if (!$is_none) {
            $this->cleanNotifications('NOTIF.ALLOWOVERRIDE.MESSAGE');
        }

    }


    /**
     * Check if AllowOveride is None or All.
     *
     * @return void
     */
    public function checkHaStatus()
    {
        global $config;
        enterprise_include_once('include/class/DatabaseHA.class.php');

        $cluster = new DatabaseHA();
        $nodes = $cluster->getNodes();

        foreach ($nodes as $node) {
            if ($node['status'] == HA_DISABLED) {
                continue;
            }

            $cluster_master = $cluster->isClusterMaster($node);
            $db_master = $cluster->isDBMaster($node);

            $message = '<pre>The roles played by node '.$node['host'].' are out of sync:
            Role in the cluster: Master
            Role in the database: Slave Desynchronized operation in the node';

            if ((int) $db_master !== (int) $cluster_master) {
                $this->notify(
                    [
                        'type'    => 'NOTIF.HAMASTER.MESSAGE',
                        'title'   => __('Desynchronized operation on the node '.$node['host']),
                        'message' => __($message),
                        'url'     => '__url__/index.php?sec=gservers&sec2=enterprise/godmode/servers/HA_cluster',
                    ]
                );
            } else {
                $this->cleanNotifications('NOTIF.HAMASTER.MESSAGE');
            }
        }
    }


    /**
     * Check if Pandora console log file remains in old location.
     *
     * @return void
     */
    public function checkPandoraConsoleLogOldLocation()
    {
        global $config;

        if (file_exists($config['homedir'].'/pandora_console.log')) {
            $title_pandoraconsole_old_log = __(
                'Pandora FMS console log file changed location',
                $config['homedir']
            );
            $message_pandoraconsole_old_log = __(
                'Pandora FMS console log file has been moved to new location %s/log. Currently you have an outdated and inoperative version of this file at %s. Please, consider deleting it.',
                $config['homedir'],
                $config['homedir']
            );

            $url = 'https://pandorafms.com/manual/en/quickguides/general_quick_guide#solving_problems_where_to_look_and_who_to_ask';
            if ($config['language'] == 'es') {
                $url = 'https://pandorafms.com//manual/es/quickguides/general_quick_guide#solucion_de_problemas_donde_mirar_a_quien_preguntar';
            }

            $this->notify(
                [
                    'type'    => 'NOTIF.PANDORACONSOLE.LOG.OLD',
                    'title'   => __($title_pandoraconsole_old_log),
                    'message' => __($message_pandoraconsole_old_log),
                    'url'     => $url,
                ]
            );
        } else {
            $this->cleanNotifications('NOTIF.PANDORACONSOLE.LOG.OLD');
        }
    }


    /**
     * Check if audit log file remains in old location.
     *
     * @return void
     */
    public function checkAuditLogOldLocation()
    {
        global $config;

        if (file_exists($config['homedir'].'/audit.log')) {
            $title_audit_old_log = __(
                'Pandora FMS audit log file changed location',
                $config['homedir']
            );
            $message_audit_old_log = __(
                'Pandora FMS audit log file has been moved to new location %s/log. Currently you have an outdated and inoperative version of this file at %s. Please, consider deleting it.',
                $config['homedir'],
                $config['homedir']
            );

            $this->notify(
                [
                    'type'    => 'NOTIF.AUDIT.LOG.OLD',
                    'title'   => __($title_audit_old_log),
                    'message' => __($message_audit_old_log),
                    'url'     => '#',
                ]
            );
        } else {
            $this->cleanNotifications('NOTIF.AUDIT.LOG.OLD');
        }
    }


    /**
     * Verifies the status of synchronization queue and warns if something is
     * not working as expected.
     *
     * @return void
     */
    public function checkSyncQueueLength()
    {
        global $config;

        if (is_metaconsole() !== true) {
            return;
        }

        $sync = new PandoraFMS\Enterprise\Metaconsole\Synchronizer(true);
        $counts = $sync->getQueues(true);

        if (count($counts) === 0) {
            // Clean all.
            $this->cleanNotifications('NOTIF.SYNCQUEUE.LENGTH.%');
        }

        $items_min = (isset($config['sync_queue_items_max']) === true) ? $config['sync_queue_items_max'] : 0;
        if (is_numeric($items_min) !== true && $items_min <= 0) {
            $items_min = self::MIN_SYNC_QUEUE_LENGTH;
        }

        foreach ($counts as $node_id => $count) {
            if ($count < $items_min) {
                $this->cleanNotifications('NOTIF.SYNCQUEUE.LENGTH.'.$node_id);
            } else {
                try {
                    $node = new PandoraFMS\Enterprise\Metaconsole\Node($node_id);

                    $url = '__url__/index.php?sec=advanced&sec2=advanced/metasetup&tab=consoles';

                    $this->notify(
                        [
                            'type'    => 'NOTIF.SYNCQUEUE.LENGTH.'.$node_id,
                            'title'   => __('Node %s sync queue length exceeded, ', $node->server_name()),
                            'message' => __(
                                'Synchronization queue lenght for node %s is %d items, this value should be 0 or lower than %d, please check the queue status.',
                                $node->server_name(),
                                $count,
                                $items_min
                            ),
                            'url'     => $url,
                        ]
                    );
                } catch (\Exception $e) {
                    // Clean, exception in node finding.
                    $this->cleanNotifications('NOTIF.SYNCQUEUE.LENGTH.'.$node_id);
                }
            }
        }

    }


    /**
     * Verifies the status of synchronization queue and warns if something is
     * not working as expected.
     *
     * @return void
     */
    public function checkSyncQueueStatus()
    {
        if (is_metaconsole() !== true) {
            return;
        }

        $sync = new PandoraFMS\Enterprise\Metaconsole\Synchronizer(true);
        $queues = $sync->getQueues();
        if (count($queues) === 0) {
            // Clean all.
            $this->cleanNotifications('NOTIF.SYNCQUEUE.STATUS.%');
        }

        foreach ($queues as $node_id => $queue) {
            if (count($queue) === 0) {
                $this->cleanNotifications('NOTIF.SYNCQUEUE.STATUS.'.$node_id);
                continue;
            }

            $item = $queue[0];

            if (empty($item->error()) === false) {
                try {
                    $node = new PandoraFMS\Enterprise\Metaconsole\Node($node_id);
                    $url = '__url__/index.php?sec=advanced&sec2=advanced/metasetup&tab=consoles';

                    $this->notify(
                        [
                            'type'    => 'NOTIF.SYNCQUEUE.STATUS.'.$node_id,
                            'title'   => __('Node %s sync queue failed, ', $node->server_name()),
                            'message' => __(
                                'Node %s cannot process synchronization queue due %s, please check the queue status.',
                                $node->server_name(),
                                $item->error()
                            ),
                            'url'     => $url,
                        ]
                    );
                } catch (\Exception $e) {
                    // Clean, exception in node finding.
                    $this->cleanNotifications('NOTIF.SYNCQUEUE.STATUS.'.$node_id);
                }
            }
        }

    }


    /**
     * Chechs if an agent has a dependency eror on omnishell
     *
     * @return void
     */
    public function checkLibaryError()
    {
        $sql = 'SELECT COUNT(errorlevel) from tremote_command_target WHERE errorlevel = 2';

        $error_dependecies = db_get_sql($sql);
        if ($error_dependecies > 0) {
            $this->notify(
                [
                    'type'    => 'NOTIF.AGENT.LIBRARY',
                    'title'   => __('Agent dependency error'),
                    'message' => __(
                        'There are omnishell agents with dependency errors',
                    ),

                    'url'     => '__url__/index.php?sec=gextensions&sec2=enterprise/tools/omnishell',
                ]
            );
        }
    }


}
