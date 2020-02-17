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
require_once $config['homedir'].'/include/functions_notifications.php';
require_once $config['homedir'].'/include/functions_servers.php';
require_once $config['homedir'].'/include/functions_update_manager.php';

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
    public $verbose;


    /**
     * Constructor.
     *
     * @param boolean $verbose Show output while executing or not.
     *
     * @return class This object
     */
    public function __construct(bool $verbose=true)
    {
        $source = db_get_row(
            'tnotification_source',
            'description',
            io_safe_input('System status')
        );

        $this->verbose = $verbose;

        if ($source === false) {
            $this->enabled = false;
            $this->sourceId = null;

            $this->targetGroups = null;
            $this->targetUsers = null;
        } else {
            $this->enabled = (bool) $source['enabled'];
            $this->sourceId = $source['id'];

            // Assign targets.
            $targets = get_notification_source_targets($this->sourceId);
            $this->targetGroups = $targets['groups'];
            $this->targetUsers = $targets['users'];
            $this->targetUpdated = true;
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
        global $config;

        /*
         * PHP configuration warnings:
         *   NOTIF.PHP.SAFE_MODE
         *   NOTIF.PHP.INPUT_TIME
         *   NOTIF.PHP.EXECUTION_TIME
         *   NOTIF.PHP.UPLOAD_MAX_FILESIZE
         *   NOTIF.PHP.MEMORY_LIMIT
         *   NOTIF.PHP.DISABLE_FUNCTIONS
         *   NOTIF.PHP.PHANTOMJS
         *   NOTIF.PHP.VERSION
         */

        $this->checkPHPSettings();

        /*
         * Check license.
         *   NOTIF.LICENSE.EXPIRATION
         */

        $this->checkLicense();

        /*
         * Check component statuses (servers down - frozen).
         *    NOTIF.SERVER.STATUS.ID_SERVER
         */

        $this->checkPandoraServers();

        /*
         * Check at least 1 server running in master mode.
         *   NOTIF.SERVER.MASTER
         */

        $this->checkPandoraServerMasterAvailable();

        /*
         * Check if CRON is running.
         *    NOTIF.CRON.CONFIGURED
         */

        if (enterprise_installed()) {
            $this->checkCronRunning();
        }

        /*
         * Check if instance is registered.
         *     NOTIF.UPDATEMANAGER.REGISTRATION
         */

        $this->checkUpdateManagerRegistration();

        /*
         * Check if there're new messages in UM.
         *     NOTIF.UPDATEMANAGER.MESSAGES
         */

        $this->getUMMessages();

        /*
         * Check if the Server and Console has
         * the same versions.
         */
        $this->checkConsoleServerVersions();

    }


    /**
     * Manage scheduled tasks.
     *
     * @return void
     */
    public function run()
    {
        global $config;

        if ($this->enabled === false) {
            // Feature not enabled.
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
         *   NOTIF.LICENSE.EXPIRATION
         *   NOTIF.LICENSE.LIMITED
         */

        $this->checkLicense();

        /*
         * Check number of files in attachment:
         *   NOTIF.FILES.ATTACHMENT
         */

        $this->checkAttachment();

        /*
         * Files in data_in:
         *   NOTIF.FILES.DATAIN  (>1000)
         *   NOTIF.FILES.DATAIN.BADXML (>150)
         */

        $this->checkDataIn();

        /*
         * Check module queues not growing:
         *   NOTIF.SERVER.QUEUE.ID_SERVER
         */

        $this->checkServers();

        /*
         * Check component statuses (servers down - frozen).
         *    NOTIF.SERVER.STATUS.ID_SERVER
         */

        $this->checkPandoraServers();

        /*
         * Check at least 1 server running in master mode.
         *   NOTIF.SERVER.MASTER
         */

        $this->checkPandoraServerMasterAvailable();

        /*
         * PHP configuration warnings:
         *   NOTIF.PHP.SAFE_MODE
         *   NOTIF.PHP.INPUT_TIME
         *   NOTIF.PHP.EXECUTION_TIME
         *   NOTIF.PHP.UPLOAD_MAX_FILESIZE
         *   NOTIF.PHP.MEMORY_LIMIT
         *   NOTIF.PHP.DISABLE_FUNCTIONS
         *   NOTIF.PHP.PHANTOMJS
         *   NOTIF.PHP.VERSION
         */

        $this->checkPHPSettings();

        /*
         *  Check connection with historical DB (if enabled).
         *    NOTIF.HISTORYDB
         */

        $this->checkPandoraHistoryDB();

        /*
         * Check pandoradb running in main DB.
         * Check pandoradb running in historical DB.
         *   NOTIF.PANDORADB
         *   NOTIF.PANDORADB.HISTORICAL
         */

        $this->checkPandoraDBMaintenance();

        /*
         * Check historical DB MR version.
         *    NOTIF.HISTORYDB.MR
         */

        $this->checkPandoraHistoryDBMR();

        /*
         * Check external components.
         *    NOTIF.EXT.ELASTICSEARCH
         *    NOTIF.EXT.LOGSTASH
         *
         */

        $this->checkExternalComponents();

        /*
         * Check Metaconsole synchronization issues.
         *    NOTIF.METACONSOLE.DB_CONNECTION
         */

        $this->checkMetaconsole();

        /*
         * Check incoming scheduled downtimes (< 15d).
         *    NOTIF.DOWNTIME
         */

        $this->checkDowntimes();

        /*
         * Check if instance is registered.
         *     NOTIF.UPDATEMANAGER.REGISTRATION
         */

        $this->checkUpdateManagerRegistration();

        /*
         * Check if event storm protection is activated.
         *    NOTIF.MISC.EVENTSTORMPROTECTION
         */

        $this->checkEventStormProtection();

        /*
         * Check if develop_bypass is enabled.
         *    NOTIF.MISC.DEVELOPBYPASS
         */

        $this->checkDevelopBypass();

        /*
         * Check if fontpath exists.
         *    NOTIF.MISC.FONTPATH
         */

        $this->checkFont();

        /*
         * Check if default user and password exists.
         *    NOTIF.SECURITY.DEFAULT_PASSWORD
         */

        $this->checkDefaultPassword();

        /*
         * Check if there're new updates.
         *    NOTIF.UPDATEMANAGER.OPENSETUP
         *    NOTIF.UPDATEMANAGER.UPDATE
         */

        $this->checkUpdates();

        /*
         * Check if there're new minor updates available.
         *    NOTIF.UPDATEMANAGER.MINOR
         */

        $this->checkMinorRelease();

        if (enterprise_installed()) {
            // Release the lock.
            enterprise_hook('cron_supervisor_release_lock');
        }

        /*
         * Check if CRON is running.
         *    NOTIF.CRON.CONFIGURED
         */

        if (enterprise_installed()) {
            $this->checkCronRunning();
        }

        /*
         * Check if instance is registered.
         *     NOTIF.UPDATEMANAGER.REGISTRATION
         */

        $this->checkUpdateManagerRegistration();

        /*
         * Check if there're new messages in UM.
         *     NOTIF.UPDATEMANAGER.MESSAGES
         */

        $this->getUMMessages();

        /*
         * Check if the Server and Console has
         * the same versions.
         */
        $this->checkConsoleServerVersions();

    }


    /**
     * Update targets for given notification using object targets.
     *
     * @param array   $notification Current notification.
     * @param boolean $update       Only update db targets, no email.
     *
     * @return void
     */
    public function updateTargets(
        array $notification,
        bool $update=false
    ) {
        $notification_id = $notification['id_mensaje'];
        $blacklist = [];

        if (is_array($this->targetUsers) === true
            && count($this->targetUsers) > 0
        ) {
            // Process user targets.
            $insertion_string = '';
            $users_sql = 'INSERT INTO tnotification_user(id_mensaje,id_user)';
            foreach ($this->targetUsers as $user) {
                $insertion_string .= sprintf(
                    '(%d,"%s")',
                    $notification_id,
                    $user['id_user']
                );
                $insertion_string .= ',';

                if ($update === false) {
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
            $groups_sql = 'INSERT INTO tnotification_group(id_mensaje,id_group)';
            foreach ($this->targetGroups as $group) {
                $insertion_string .= sprintf(
                    '(%d,"%s")',
                    $notification_id,
                    $group['id_group']
                );
                $insertion_string .= ',';

                if ($update === false) {
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
        int $max_age=86400
    ) {
        // Uses 'check failed' logic.
        if (is_array($data) === false) {
            // Skip.
            return;
        }

        if ($this->targetUpdated === false) {
            $targets = get_notification_source_targets($this->sourceId);
            $this->targetGroups = $targets['groups'];
            $this->targetUsers = $targets['users'];
            $this->targetUpdated = false;
        }

        if ($source_id === 0) {
            $source_id = $this->sourceId;
            // Assign targets.
            $targets = get_notification_source_targets($source_id);
            $this->targetGroups = $targets['groups'];
            $this->targetUsers = $targets['users'];
            $this->targetUpdated = false;
        }

        switch ($data['type']) {
            case 'NOTIF.LICENSE.LIMITED':
                $max_age = 0;
            break;

            case 'NOTIF.LICENSE.EXPIRATION':
            case 'NOTIF.FILES.ATTACHMENT':
            case 'NOTIF.FILES.DATAIN':
            case 'NOTIF.FILES.DATAIN.BADXML':
            case 'NOTIF.PHP.SAFE_MODE':
            case 'NOTIF.PHP.INPUT_TIME':
            case 'NOTIF.PHP.EXECUTION_TIME':
            case 'NOTIF.PHP.UPLOAD_MAX_FILESIZE':
            case 'NOTIF.PHP.MEMORY_LIMIT':
            case 'NOTIF.PHP.DISABLE_FUNCTIONS':
            case 'NOTIF.PHP.PHANTOMJS':
            case 'NOTIF.PHP.VERSION':
            case 'NOTIF.HISTORYDB':
            case 'NOTIF.PANDORADB':
            case 'NOTIF.PANDORADB.HISTORICAL':
            case 'NOTIF.HISTORYDB.MR':
            case 'NOTIF.EXT.ELASTICSEARCH':
            case 'NOTIF.EXT.LOGSTASH':
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
            default:
                // NOTIF.SERVER.STATUS.
                // NOTIF.SERVER.STATUS.ID_SERVER.
                // NOTIF.SERVER.QUEUE.ID_SERVER.
                // NOTIF.SERVER.MASTER.
                // NOTIF.SERVER.STATUS.ID_SERVER.
                if (preg_match('/^NOTIF.SERVER/', $data['type']) === true) {
                    // Component notifications require be inmediate.
                    $max_age = 0;
                }

                // Else ignored.
            break;
        }

        // Get previous notification.
        $prev = db_get_row(
            'tmensajes',
            'subtype',
            $data['type']
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
            $this->updateTargets($prev, true);
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
                    'url'     => ui_get_full_url('index.php?sec=gsetup&sec2=godmode/setup/license'),
                ]
            );
        } else {
            $this->cleanNotifications('NOTIF.LICENSE.LIMITED');
        }

        // Expiry.
        if (($days_to_expiry <= 15) && ($days_to_expiry > 0)) {
            // Warn user if license is going to expire in 15 days or less.
            $this->notify(
                [
                    'type'    => 'NOTIF.LICENSE.EXPIRATION',
                    'title'   => __('License is about to expire'),
                    'message' => __(
                        'Your license will expire in %d days. Please, contact our sales department.',
                        $days_to_expiry
                    ),
                    'url'     => ui_get_full_url('index.php?sec=gsetup&sec2=godmode/setup/license'),
                ]
            );
        } else if ($days_to_expiry < 0) {
            // Warn user, license has expired.
            $this->notify(
                [
                    'type'    => 'NOTIF.LICENSE.EXPIRATION',
                    'title'   => __('Expired license'),
                    'message' => __('Your license has expired. Please, contact our sales department.'),
                    'url'     => ui_get_full_url('index.php?sec=gsetup&sec2=godmode/setup/license'),
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
                    'url'     => ui_get_full_url('index.php?sec=general&sec2=godmode/setup/setup&section=general'),
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
                    'url'     => ui_get_full_url('index.php?sec=general&sec2=godmode/setup/setup&section=perf'),
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

        $remote_config_dir = io_safe_output($config['remote_config']);

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
                        'url'     => ui_get_full_url('index.php?sec=general&sec2=godmode/setup/setup&section=general'),
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
                        'url'     => ui_get_full_url('index.php?sec=general&sec2=godmode/setup/setup&section=general'),
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
                        'url'     => ui_get_full_url('index.php?sec=general&sec2=godmode/setup/setup&section=general'),
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
                        'url'     => ui_get_full_url('index.php?sec=general&sec2=godmode/setup/setup&section=general'),
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
                    'url'     => ui_get_full_url('index.php?sec=general&sec2=godmode/setup/setup&section=perf'),
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
                    'url'     => ui_get_full_url('index.php?sec=general&sec2=godmode/setup/setup&section=perf'),
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
                            'url'     => ui_get_full_url('index.php?sec=gservers&sec2=godmode/servers/modificar_server&refr=60'),
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
                unix_timestamp() - unix_timestamp(keepalive) > server_keepalive
                OR status = 0'
        );

        if ($servers === false) {
            $nservers = db_get_value_sql(
                'SELECT count(*) as nservers
                 FROM tserver'
            );
            if ($nservers == 0) {
                $url = 'https://wiki.pandorafms.com/index.php?title=Pandora:Documentation_en:Configuration';
                if ($config['language'] == 'es') {
                    $url = 'https://wiki.pandorafms.com/index.php?title=Pandora:Documentation_es:Configuracion';
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
                    'url'     => ui_get_full_url('index.php?sec=gservers&sec2=godmode/servers/modificar_server&refr=60'),
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
            $url = 'https://wiki.pandorafms.com/index.php?title=Pandora:Documentation_en:Configuration#master';
            if ($config['language'] == 'es') {
                $url = 'https://wiki.pandorafms.com/index.php?title=Pandora:Documentation_es:Configuracion#master';
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
        $PHPmemory_limit_min = config_return_in_bytes('500M');
        $PHPSerialize_precision = ini_get('serialize_precision');

        // PhantomJS status.
        $phantomjs_dir = io_safe_output($config['phantomjs_bin']);
        $result_ejecution = exec($phantomjs_dir.'/phantomjs --version');

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
                        __("'%s' value in PHP configuration is not recommended"),
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

        if ($PHPmax_execution_time !== '0') {
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

        if ($PHPmemory_limit < $PHPmemory_limit_min && $PHPmemory_limit !== '-1') {
            $url = 'http://php.net/manual/en/ini.core.php#ini.memory-limit';
            if ($config['language'] == 'es') {
                $url = 'http://php.net/manual/es/ini.core.php#ini.memory-limit';
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
                        sprintf(__('%s or greater'), '500M')
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
            $url = 'https://wiki.pandorafms.com/index.php?title=Pandora:Documentation_en:Configuration#Phantomjs';
            if ($config['language'] == 'es') {
                $url = 'https://wiki.pandorafms.com/index.php?title=Pandora:Documentation_es:Configuracion#Phantomjs';
            }

            $this->notify(
                [
                    'type'    => 'NOTIF.PHP.PHANTOMJS',
                    'title'   => __('PhantomJS is not installed'),
                    'message' => __('To be able to create images of the graphs for PDFs, please install the PhantomJS extension. For that, it is necessary to follow these steps:'),
                    'url'     => $url,
                ]
            );
        } else {
            $this->cleanNotifications('NOTIF.PHP.PHANTOMJS');
        }

        if ($php_version_array[0] < 7) {
            $url = 'https://wiki.pandorafms.com/index.php?title=Pandora:Documentation_en:_PHP_7';
            if ($config['language'] == 'es') {
                $url = 'https://wiki.pandorafms.com/index.php?title=Pandora:Documentation_es:Instalaci%C3%B3n_y_actualizaci%C3%B3n_PHP_7';
            }

            $this->notify(
                [
                    'type'    => 'NOTIF.PHP.VERSION',
                    'title'   => __('PHP UPDATE REQUIRED'),
                    'message' => __('For a correct operation of PandoraFMS, PHP must be updated to version 7.0 or higher.').'<br>'.__('Otherwise, functionalities will be lost.').'<br>'."<ol><li style='color: #676767'>".__('Report download in PDF format').'</li>'."<li style='color: #676767'>".__('Emails Sending').'</li><li style="color: #676767">'.__('Metaconsole Collections').'</li><li style="color: #676767">...</li></ol>',
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
                        'url'     => ui_get_full_url('index.php?sec=general&sec2=godmode/setup/setup&section=hist_db'),
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
                    'url'     => ui_get_full_url('index.php?sec=general&sec2=godmode/setup/setup&section=perf'),
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
                        'url'     => ui_get_full_url('index.php?sec=general&sec2=godmode/setup/setup&section=perf'),
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
                        'url'     => ui_get_full_url('index.php?sec=general&sec2=godmode/setup/setup&section=hist_db'),
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

        // Cannot check logstash, configuration is only available from server.
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
                        'url'     => ui_get_full_url('index.php?sec=general&sec2=godmode/setup/setup&section=log'),
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
                    'url'     => ui_get_full_url('index.php?sec=general&sec2=godmode/setup/setup&section=enterprise'),
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
                    'url'     => ui_get_full_url('index.php?sec=gagente&sec2=godmode/agentes/planned_downtime.list'),
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
                        'url'     => ui_get_full_url('index.php?sec=gagente&sec2=godmode/agentes/planned_downtime.list'),
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
                    'message' => __('Click <a style="font-weight:bold; text-decoration:underline" href="javascript: force_run_register();"> here</a> to start the registration process'),
                    'url'     => 'javascript: force_run_register();',
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
                    'url'     => ui_get_full_url('index.php?sec=gusuarios&sec2=godmode/users/user_list'),
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

        if (($fontpath == '')
            || (file_exists($fontpath) === false)
        ) {
            $this->notify(
                [
                    'type'    => 'NOTIF.MISC.FONTPATH',
                    'title'   => __('Default font doesn\'t exist'),
                    'message' => __('Your defined font doesn\'t exist or is not defined. Please, check font parameters in your config'),
                    'url'     => ui_get_full_url('index.php?sec=gsetup&sec2=godmode/setup/setup&section=vis'),
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
                    'url'     => ui_get_full_url('index.php'),
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
                    'url'     => ui_get_full_url('index.php?sec=gsetup&sec2=godmode/setup/setup&section=general'),
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
                                'url'     => ui_get_full_url('index.php?sec=gsetup&sec2=godmode/setup/setup&section=general'),
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
                        'message' => __('There is a new update available. Please<a style="font-weight:bold;" href="'.ui_get_full_url('index.php?sec=gsetup&sec2=godmode/update_manager/update_manager&tab=online').'"> go to Administration:Setup:Update Manager</a> for more details.'),
                        'url'     => ui_get_full_url('index.php?sec=gsetup&sec2=godmode/update_manager/update_manager&tab=online'),
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
            $url = 'http://wiki.pandorafms.com/index.php?title=Pandora:Documentation_en:Anexo_Upgrade#Version_7.0NG_.28_Rolling_Release_.29';
            if ($config['language'] == 'es') {
                $url = 'http://wiki.pandorafms.com/index.php?title=Pandora:Documentation_es:Actualizacion#Versi.C3.B3n_7.0NG_.28_Rolling_Release_.29';
            }

            $this->notify(
                [
                    'type'    => 'NOTIF.UPDATEMANAGER.MINOR',
                    'title'   => __('Minor release/s available'),
                    'message' => __(
                        'There is one or more minor releases available. <a style="font-size:8pt;font-style:italic;" target="blank" href="%s">.About minor release update</a>.',
                        $url
                    ),
                    'url'     => ui_get_full_url('index.php?sec=messages&sec2=godmode/update_manager/update_manager&tab=online'),
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
            || (get_system_time() - $config['cron_last_run']) > 3600
        ) {
            $message_conf_cron = __('DiscoveryConsoleTasks is not running properly');
            if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN') {
                $message_conf_cron .= __('Discovery relies on an appropriate cron setup.');
                $message_conf_cron .= '. '.__('Please, add the following line to your crontab file:');
                $message_conf_cron .= '<pre>* * * * * &lt;user&gt; wget -q -O - --no-check-certificate ';
                $message_conf_cron .= str_replace(
                    ENTERPRISE_DIR.'/meta/',
                    '',
                    ui_get_full_url(false)
                );
                $message_conf_cron .= ENTERPRISE_DIR.'/'.EXTENSIONS_DIR;
                $message_conf_cron .= '/cron/cron.php &gt;&gt; ';
                $message_conf_cron .= $config['homedir'].'/pandora_console.log</pre>';
            }

            if (isset($config['cron_last_run']) === true) {
                $message_conf_cron .= __('Last execution').': ';
                $message_conf_cron .= date('Y/m/d H:i:s', $config['cron_last_run']);
                $message_conf_cron .= __('Please, make sure process is not locked.');
            }

            $this->notify(
                [
                    'type'    => 'NOTIF.CRON.CONFIGURED',
                    'title'   => __('DiscoveryConsoleTasks is not configured.'),
                    'message' => __($message_conf_cron),
                    'url'     => ui_get_full_url('index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=tasklist'),
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
        if (isset($config['last_um_check'])
            && time() < $config['last_um_check']
        ) {
            return;
        }

        // Only ask for messages once a day.
        $future = (time() + 2 * SECONDS_1HOUR);
        config_update_value('last_um_check', $future);

        $params = [
            'pandora_uid' => $config['pandora_uid'],
            'timezone'    => $config['timezone'],
            'language'    => $config['language'],
        ];

        $result = update_manager_curl_request('get_messages', $params);

        try {
            if ($result['success'] === true) {
                $messages = json_decode($result['update_message'], true);
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
        };

        if (is_array($messages)) {
            $source_id = get_notification_source_id(
                'Official&#x20;communication'
            );
            foreach ($messages as $message) {
                if (!isset($message['url'])) {
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
                    $config['current_package_enterprise']
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
                            'url'     => ui_get_full_url('index.php?sec=messages&sec2=godmode/update_manager/update_manager&tab=online'),
                        ]
                    );
                }
            }
        }

        // Cleanup notifications if exception is recovered.
        if ($missed == 0) {
            $this->cleanNotifications('NOTIF.SERVER.MISALIGNED');
        }
    }


}
