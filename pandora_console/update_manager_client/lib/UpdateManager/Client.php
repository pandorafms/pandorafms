<?php
/**
 * UpdateManager Client.
 *
 * @category   Class
 * @package    Update Manager
 * @subpackage Client
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

// Begin.
namespace UpdateManager;

require_once __DIR__.'/constants.php';

// This function will be instantiated if no '__' function is defined previously.
if (function_exists('__') === false) {


    /**
     * Override for translation function if not available.
     *
     * @param string|null $str String to be translated.
     *
     * @return string
     */
    function __(?string $str)
    {
        if ($str !== null) {
            $ret = $str;
            try {
                $args = func_get_args();
                array_shift($args);
                $ret = vsprintf($str, $args);
            } catch (\Exception $e) {
                return $str;
            }

            return $ret;
        }

        return '';
    }


}

/**
 * Update manager client class.
 */
class Client
{
    /**
     * Max lock age 1 day.
     */
    const MAX_LOCK_AGE = 86400;

    /**
     * Update manager host.
     *
     * @var string
     */
    private $umHost;

    /**
     * Update manager port.
     *
     * @var integer
     */
    private $umPort;

    /**
     * Where is placed the UMS application.
     *
     * @var string
     */
    private $endPoint;

    /**
     * Update manager url.
     *
     * @var string
     */
    private $url;

    /**
     * Allow insecure connections.
     *
     * @var boolean
     */
    private $insecure;

    /**
     * Product license.
     *
     * @var string
     */
    private $license;

    /**
     * Limit count (license).
     *
     * @var integer|null
     */
    private $limitCount;

    /**
     * Language code.
     *
     * @var string|null
     */
    private $language;

    /**
     * Time zone.
     *
     * @var string|null
     */
    private $timezone;

    /**
     * Registration code.
     *
     * @var string
     */
    private $registrationCode;

    /**
     * Current packages.
     *
     * @var string
     */
    private $currentPackage;

    /**
     * Propagate updates (MC -> Nodes).
     *
     * @var boolean
     */
    private $propagateUpdates;

    /**
     * Last reror message.
     *
     * @var string|null
     */
    private $lastError;

    /**
     * Proxy settings:
     *  host
     *  port
     *  user
     *  password.
     *
     * @var array|null
     */
    private $proxy;

    /**
     * Temporary directory.
     *
     * @var string
     */
    private $tmpDir;

    /**
     * Available updates.
     *
     * @var array
     */
    private $updates;

    /**
     * Available LTS updates.
     *
     * @var array
     */
    private $updatesLTS;

    /**
     * Where is installed the product files.
     *
     * @var string
     */
    private $productPath;

    /**
     * Product DB connection.
     *
     * @var \mysqli|null
     */
    private $dbh;

    /**
     * Optional product DB connection.
     *
     * @var \mysqli|null
     */
    private $dbhHistory;

    /**
     * MR version.
     *
     * @var integer
     */
    private $MR;

    /**
     * Global percentage (for massive update).
     *
     * @var float|null
     */
    private $percentage;

    /**
     * Global next version (for massive update).
     *
     * @var string|null
     */
    private $nextUpdate;

    /**
     * Task step message.
     *
     * @var string|null
     */
    private $currentTask;

    /**
     * Global message (for massive update).
     *
     * @var string|null
     */
    private $globalTask;

    /**
     * Set maintenance mode ON.
     *
     * @var \closure
     */
    private $setMaintenanceMode;

    /**
     * Set maintenance mode OFF.
     *
     * @var \closure
     */
    private $clearMaintenanceMode;

    /**
     * Remote config directory (pandorafms only) to store server updates.
     *
     * @var string|null
     */
    private $remoteConfig;

    /**
     * Whenever if an offline usage.
     *
     * @var boolean
     */
    private $offline;

    /**
     * Lock file installation if no db is available.
     *
     * @var string
     */
    private $lockfile;

    /**
     * Search for long term support updates only.
     *
     * @var boolean
     */
    private $lts;

    /**
     * Function to be called after each package upgrade.
     *
     * @var callable|null
     */
    private $postUpdateFN;


    /**
     * Constructor.
     *
     * @param array|null $settings Update manager Client settings.
     *                             - homedir (*): where project files are placed (e.g. config['homedir])
     *                             - dbconnection: mysqli object (connected)
     *                             - historydb: mysqli object (connected to historical database)
     *                             - url (**): UMS url (update_manager_url)
     *                             - host: UMS host
     *                             - port: UMS port
     *                             - endpoint: UMS path (e.g. host:port/endpoint)
     *                             - license: License string
     *                             - registration_code: Registration code in UMS
     *                             - insecure: insecure connections (SSL allow self-signed)
     *                             - current_package: current package
     *                             - tmp: Temporary directory
     *                             - MR: current MR
     *                             - proxy
     *                             - user
     *                             - password
     *                             - host
     *                             - port
     *                             - lts
     *                             - postUpdateFN: function to be called after each upgrade.
     *                                             will receive 2 parameters, version and string
     *                                             containing 'server' if server upgrade or 'console'
     *                                             if console upgrade was performed.
     *
     *                             (*) mandatory
     *                             (**) Optionally, set full url instead host-port-endpoint.
     *
     * @throws \Exception On error.
     */
    public function __construct(?array $settings)
    {
        // Default values.
        $this->umHost = 'licensing.pandorafms.com';
        $this->umPort = 443;
        $this->endPoint = '/';
        $this->insecure = false;
        $this->tmpDir = sys_get_temp_dir();
        $this->currentPackage = null;
        $this->proxy = null;
        $this->lastError = null;
        $this->registrationCode = '';
        $this->license = '';
        $this->updates = [];
        $this->updatesLTS = [];
        $this->dbh = null;
        $this->dbhHistory = null;
        $this->MR = 0;
        $this->percentage = null;
        $this->nextUpdate = null;
        $this->currentTask = null;
        $this->globalTask = null;
        $this->setMaintenanceMode = null;
        $this->clearMaintenanceMode = null;
        $this->remoteConfig = null;
        $this->limitCount = null;
        $this->language = null;
        $this->timezone = null;
        $this->propagateUpdates = false;
        $this->offline = false;
        $this->lts = false;
        $this->postUpdateFN = null;

        if (is_array($settings) === true) {
            if (isset($settings['homedir']) === true) {
                $this->productPath = $settings['homedir'];
            }

            if (isset($settings['remote_config']) === true) {
                $this->remoteConfig = $settings['remote_config'];
            }

            if (isset($settings['limit_count']) === true) {
                $this->limitCount = $settings['limit_count'];
            }

            if (isset($settings['language']) === true) {
                $this->language = $settings['language'];
            }

            if (isset($settings['timezone']) === true) {
                $this->timezone = $settings['timezone'];
            }

            if (isset($settings['dbconnection']) === true) {
                $this->dbh = $settings['dbconnection'];
            }

            if (isset($settings['historydb']) === true) {
                $this->dbhHistory = $settings['historydb'];
            }

            if (isset($settings['host']) === true) {
                $this->umHost = $settings['host'];
            }

            if (isset($settings['port']) === true) {
                $this->umPort = $settings['port'];
            }

            if (isset($settings['license']) === true) {
                $this->license = $settings['license'];
            }

            if (isset($settings['registration_code']) === true) {
                $this->registrationCode = $settings['registration_code'];
            }

            if (isset($settings['propagate_updates']) === true) {
                $this->propagateUpdates = $settings['propagate_updates'];
            }

            if (isset($settings['proxy']) === true
                && is_array($settings['proxy']) === true
            ) {
                $this->proxy = [
                    'host'     => $settings['proxy']['host'],
                    'port'     => $settings['proxy']['port'],
                    'user'     => $settings['proxy']['user'],
                    'password' => $settings['proxy']['password'],
                ];
            }

            if (isset($settings['insecure']) === true) {
                $this->insecure = (bool) $settings['insecure'];
            }

            if (isset($settings['offline']) === true) {
                $this->offline = $settings['offline'];
            }

            if (isset($settings['tmp']) === true) {
                $this->tmpDir = $settings['tmp'];
            }

            if (isset($settings['current_package']) === true) {
                $this->currentPackage = $settings['current_package'];
            }

            if (isset($settings['MR']) === true) {
                $this->MR = $settings['MR'];
            }

            if (isset($settings['lts']) === true) {
                $this->lts = $settings['lts'];
            }

            if (isset($settings['on_update']) === true
                && is_callable($settings['on_update']) === true
            ) {
                $this->postUpdateFN = $settings['on_update'];
            }

            if (isset($settings['endpoint']) === true) {
                $this->endPoint = $settings['endpoint'];
                if (substr(
                    $this->endPoint,
                    (strlen($this->endPoint) - 1),
                    1
                ) !== '/'
                ) {
                    $this->endPoint .= '/';
                }

                if (substr($this->endPoint, 0, 1) !== '/') {
                    $this->endPoint = '/'.$this->endPoint;
                }
            }

            if (isset($settings['url']) === true) {
                $this->url = $settings['url'];
            }

            if (isset($settings['set_maintenance_mode']) === true) {
                $this->setMaintenanceMode = $settings['set_maintenance_mode'];
            }

            if (isset($settings['clear_maintenance_mode']) === true) {
                $this->clearMaintenanceMode = $settings['clear_maintenance_mode'];
            }
        }

        if (empty($this->url) === true) {
            $this->url = 'https://'.$this->umHost.':'.$this->umPort;
            $this->url .= $this->endPoint.'server.php';
        }

        if (empty($this->productPath) === true) {
            throw new \Exception('Please provide homedir path to use UMC');
        }

        if (is_dir($this->remoteConfig) === true
            && is_dir($this->remoteConfig.'/updates') === false
        ) {
            mkdir($this->remoteConfig.'/updates/');
            chmod($this->remoteConfig.'/updates/', 0770);
        }

        $this->lockfile = $this->tmpDir.'/'.hash('sha256', $this->productPath).'.umc.lock';
    }


    /**
     * True if insecure is enabled, false otherwise.
     *
     * @return boolean
     */
    public function isInsecure()
    {
        return (bool) $this->insecure;
    }


    /**
     * Should propagate updates?
     *
     * @return boolean
     */
    public function isPropagatingUpdates()
    {
        return (bool) $this->propagateUpdates;
    }


    /**
     * Return last error.
     *
     * @return string|null Last error or null if no errors.
     */
    public function getLastError():?string
    {
        return $this->lastError;
    }


    /**
     * Return true if registered (having a registration code), false if not.
     *
     * @return boolean
     */
    public function isRegistered():bool
    {
        if (isset($this->registrationCode) === false
            || $this->registrationCode === 'OFFLINE'
        ) {
            return false;
        }

        return (bool) $this->registrationCode;
    }


    /**
     * Registrates this console into update manager.
     *
     * @param string $email Target email.
     *
     * @return string|null Registration code or null if not registered.
     */
    public function register(string $email):?string
    {
        if ($this->isRegistered() === false) {
            $rc = $this->post(
                [
                    'action'    => 'new_register',
                    'arguments' => [ 'email' => $email ],
                ]
            );

            if (is_array($rc) === true) {
                $this->registrationCode = $rc['pandora_uid'];
            }
        }

        if ($this->dbh !== null) {
            $stm = $this->dbh->query(
                'SELECT `value` FROM `tconfig` WHERE `token`="pandora_uid"'
            );

            $reg_code = null;
            if ($stm !== false) {
                $reg_code = $stm->fetch_row()[0];
            }

            if (empty($reg_code) === true) {
                $this->dbh->query(
                    sprintf(
                        'INSERT INTO `tconfig` (`token`,`value`)
                         VALUES ("pandora_uid", "%s")',
                        ($this->getRegistrationCode() ?? 'OFFLINE')
                    )
                );
            } else {
                $this->dbh->query(
                    sprintf(
                        'UPDATE `tconfig` SET `value` = \'%s\'
                            WHERE `token` = "pandora_uid"',
                        ($this->getRegistrationCode() ?? 'OFFLINE')
                    )
                );
            }
        }

        return $this->getRegistrationCode();
    }


    /**
     * Unregistrates this console from update manager.
     *
     * @return void
     */
    public function unRegister():void
    {
        if ($this->isRegistered() === true) {
            $this->post(
                [ 'action' => 'unregister' ]
            );

            $this->registrationCode = 'OFFLINE';
        }

        if ($this->dbh !== null) {
            $this->dbh->query(
                sprintf(
                    'UPDATE `tconfig` SET `value` = \'%s\'
                        WHERE `token` = "pandora_uid"',
                    'OFFLINE'
                )
            );
        }
    }


    /**
     * Return registration code if this console is registered into UMS
     *
     * @return string|null
     */
    public function getRegistrationCode():?string
    {
        if ($this->isRegistered() === true) {
            return $this->registrationCode;
        }

        return null;
    }


    /**
     * Executes a curl request.
     *
     * @param string $url     Url to be called.
     * @param array  $request Options.
     *
     * @return mixed Response given by curl.
     */
    private function curl(string $url, array $request)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            $request
        );
        if ($this->insecure === true) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        if (is_array($this->proxy) === true) {
            curl_setopt($ch, CURLOPT_PROXY, $this->proxy['host']);
            if (isset($this->proxy['port']) === true) {
                curl_setopt($ch, CURLOPT_PROXYPORT, $this->proxy['port']);
            }

            if (isset($this->proxy['user']) === true) {
                    curl_setopt(
                        $ch,
                        CURLOPT_PROXYUSERPWD,
                        $this->proxy['user'].':'.$this->proxy['password']
                    );
            }
        }

        // Track progress.
        if ((empty($request) === true
            || $request['action'] === 'get_package'
            || $request['action'] === 'get_server_package')
        ) {
            curl_setopt(
                $ch,
                CURLOPT_NOPROGRESS,
                false
            );
        }

        $target = '';
        if ($request['action'] === 'get_server_package') {
            $target = __('server update %d', $request['version']);
        } else if ($request['action'] === 'get_package') {
            $target = __('console update %d', $request['version']);
        }

        // phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.Found
        // phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundBeforeLastUsed
        // phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
        curl_setopt(
            $ch,
            CURLOPT_PROGRESSFUNCTION,
            function (
                $ch,
                $total_bytes,
                $current_bytes,
                $total_sent_bytes,
                $current_sent_bytes
            ) use ($target) {
                if ($total_bytes > 0) {
                    $this->notify(
                        (100 * $current_bytes / $total_bytes),
                        __(
                            'Downloading %s %.2f/ %.2f MB.',
                            $target,
                            ($current_bytes / (1024 * 1024)),
                            ($total_bytes / (1024 * 1024))
                        ),
                        true
                    );
                } else {
                    $this->notify(
                        0,
                        __(
                            'Downloading %.2f MB',
                            ($current_bytes / (1024 * 1024))
                        ),
                        true
                    );
                }
            }
        );

        // Call.
        $response = curl_exec($ch);
        $erro_no = curl_errno($ch);
        if ($erro_no > 0) {
            $this->lastError = $erro_no.':'.curl_error($ch);
            return null;
        }

        return $response;
    }


    /**
     * Make a request to Update manager.
     *
     * @param array   $request Request:
     *                         action: string
     *                         arguments: array.
     * @param boolean $literal Literal response, do not decode.
     *
     * @return mixed|null Parsed response if valid, false if error.
     */
    private function post(array $request, bool $literal=false)
    {
        global $pandora_version;

        $this->lastError = null;
        $default = [
            'current_package' => $this->currentPackage,
            'license'         => $this->license,
            'limit_count'     => $this->limitCount,
            'language'        => $this->language,
            'timezone'        => $this->timezone,
            'lts'             => $this->lts,
            // Retrocompatibility token.
            'version'         => $pandora_version,
            'puid'            => $this->registrationCode,
            'email'           => null,
            'format'          => 'oum',
            'build'           => $this->currentPackage,
        ];

        foreach ([
            'build',
            'current_package',
            'license',
            'limit_count',
            'language',
            'timezone',
            'version',
            'email',
            'format',
            'puid',
        ] as $var_name) {
            if (isset($request['arguments'][$var_name]) === false) {
                $request['arguments'][$var_name] = $default[$var_name];
            }
        }

        // Initialize.
        $response = $this->curl(
            $this->url,
            array_merge(
                ['action' => $request['action']],
                $request['arguments']
            )
        );

        if ($literal === true) {
            return $response;
        }

        if ($response !== null) {
            $decoded = json_decode($response, JSON_OBJECT_AS_ARRAY);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->lastError = $response;
                return null;
            }

            return $decoded;
        }

        // Request has failed.
        return null;
    }


    /**
     * Test update manager server availability.
     *
     * @return boolean Success or not.
     */
    public function test():bool
    {
        $rc = $this->post([ 'action' => 'test' ], true);
        if ($rc === 'Test response.') {
            return true;
        }

        $this->lastError = $rc;
        return false;
    }


    /**
     * Translate Open and Enterprise oum updates into rigth format.
     *
     * @param array   $updates Raw updates retrieved from UMS.
     * @param boolean $lts     LTS updates or generic.
     *
     * @return array Translated updates.
     */
    private function translateUpdatePackages(array $updates, bool $lts)
    {
        $lts_ones = $this->updatesLTS;
        return array_reduce(
            $updates,
            function ($carry, $item) use ($lts, $lts_ones) {
                if (is_array($item) !== true
                    && preg_match('/([\d\.\d]+?)\.tar/', $item, $matches) > 0
                ) {
                    $carry[] = [
                        'version'     => $matches[1],
                        'file_name'   => $item,
                        'description' => '',
                        'lts'         => ($lts === true) ? $lts : isset($lts_ones[$matches[1]]),
                    ];
                } else {
                    $carry[] = array_merge(
                        $item,
                        ['lts' => ($lts === true) ? $lts : isset($lts_ones[$item['version']])]
                    );
                }

                return $carry;
            },
            []
        );
    }


    /**
     * Retrieves a list of updates available in target UMS.
     *
     * @return array|null Results:
     * [
     *   [
     *     'version'     => Version id.
     *     'file_name'   => File name.
     *     'description' => description.
     *     'lts'         => Lts update or not.
     *   ]
     * ];
     */
    public function listUpdates():?array
    {
        $this->nextUpdate = null;
        if (empty($this->updates) === true) {
            $rc = $this->post(
                [
                    'action'    => 'newer_packages',
                    'arguments' => ['lts' => true],
                ]
            );

            if (is_array($rc) !== true) {
                // Propagate last error from request.
                return null;
            }

            // Translate response.
            $updates = $this->translateUpdatePackages($rc, true);
            $lts_updates = $updates;
            $this->updatesLTS = array_reduce(
                $updates,
                function ($carry, $item) {
                    $carry[$item['version']] = 1;
                    return $carry;
                },
                []
            );

            $rc = $this->post(
                [
                    'action'    => 'newer_packages',
                    'arguments' => ['lts' => false],
                ]
            );

            if (is_array($rc) !== true) {
                // Propagate last error from request.
                return null;
            }

            // Translate response.
            $all_updates = $this->translateUpdatePackages($rc, false);

            $this->updates = $all_updates;
        } else {
            $lts_updates = array_filter(
                $this->updates,
                function ($item) {
                    if ($item['lts'] === true) {
                        return true;
                    }

                    return false;
                }
            );
        }

        // Allows 'notify' follow current operation.
        if (is_array(current($this->updates)) === true) {
            foreach ($this->updates as $update) {
                $this->nextUpdate = $update['version'];
                if ($this->nextUpdate > $this->currentPackage) {
                    break;
                }
            }
        }

        if ($this->lts === true) {
            return $lts_updates;
        }

        return $this->updates;
    }


    /**
     * Update database given MR update.
     *
     * @param string       $mr_file    Target mr file to apply.
     * @param \mysqli|null $dbh        Target DBH.
     * @param integer|null $mr_version Current MR version (if custom dbh).
     *
     * @return void
     * @throws \Exception On error.
     */
    public function updateMR(
        string $mr_file,
        ?\mysqli $dbh=null,
        ?int $mr_version=null
    ):void {
        if ($dbh === null) {
            $dbh = $this->dbh;
        }

        if ((bool) $dbh === false) {
            throw new \Exception(
                'A database connection is needed in order to apply MR updates.'
            );
        }

        if ($mr_version === null) {
            $mr_version = $this->MR;
        }

        if ($mr_version === 0) {
            // PandoraFMS.
            $sth = $dbh->query(
                'SELECT `value`
                 FROM `tconfig`
                 WHERE `token` = "MR"'
            );

            if ($sth === false) {
                // IntegriaIMS.
                $sth = $dbh->query(
                    'SELECT `value`
                     FROM `tconfig`
                     WHERE `token` = "minor_release"'
                );
            }

            if ($sth !== false) {
                $result = $sth->fetch_array();
                if ($result !== null) {
                    $mr_version = $result[0][0];
                }
            }
        }

        if (is_file($mr_file) !== true || is_readable($mr_file) !== true) {
            throw new \Exception('Cannot access MR file ('.$mr_file.')');
        }

        $target_mr = null;
        $matches = [];
        if (preg_match('/(\d+).sql$/', $mr_file, $matches) > 0) {
            $target_mr = $matches[1];
            if ($target_mr <= $mr_version) {
                return;
            }
        }

        $sql = file_get_contents($mr_file);
        try {
            $dbh->query('SET sql_mode=""');
            $dbh->autocommit(false);
            $dbh->begin_transaction();

            $queries = preg_split("/(;\n)|(;\n\r)/", $sql);
            foreach ($queries as $query) {
                if (empty($query) !== true) {
                    if (preg_match('/^\s*SOURCE\s+(.*)$/i', $query, $matches) > 0) {
                        $filepath = dirname($mr_file).'/'.$matches[1];
                        if (file_exists($filepath) === true) {
                            $query = file_get_contents($filepath);
                        } else {
                            throw new \Exception('Cannot load file: '.$filepath);
                        }
                    }

                    if ($dbh->query($query) === false) {
                        // 1022: Duplicate key in table.
                        // 1050: Table already defined.
                        // 1060: Duplicated column name, ignore.
                        // 1061: Duplicated key name, ignore.
                        // 1062: Duplicated data, ignore.
                        // 1065: Query was empty, ignore.
                        // 1091: Column already dropped.
                        if ($dbh->errno !== 1022
                            && $dbh->errno !== 1050
                            && $dbh->errno !== 1060
                            && $dbh->errno !== 1061
                            && $dbh->errno !== 1062
                            && $dbh->errno !== 1065
                            && $dbh->errno !== 1091
                        ) {
                            // More serious issue, stop.
                            $err = '(MR:'.$target_mr.') ';
                            $err .= $dbh->errno.': '.$dbh->error;
                            $err .= "\n".$query;

                            throw new \Exception($err);
                        }
                    }
                }
            }

            // Success.
            $dbh->commit();
        } catch (\Exception $e) {
            // Error.
            $dbh->rollback();

            throw $e;
        } finally {
            $dbh->autocommit(true);
        }

        if ($dbh === $this->dbh) {
            $this->MR = $target_mr;
        } else {
            // Update product version on target database.
            $this->updateLocalDatabase($dbh, $target_mr);
        }
    }


    /**
     * Return given field from table matching filters.
     *
     * @param string       $field   Field to retrieve.
     * @param string       $table   Table.
     * @param array        $filters Array of filters.
     * @param \mysqli|null $dbh     Database handler or use default.
     *
     * @return mixed|false
     */
    private function getValue(
        string $field,
        string $table,
        array $filters,
        $dbh=null
    ) {
        // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        if ($dbh === null) {
            $dbh = $this->dbh;
        }

        $sql_filters = '';
        foreach ($filters as $k => $v) {
            $sql_filters .= sprintf(' AND `%s` = "%s" ', $k, $v);
        }

        $stm = $dbh->query(
            sprintf(
                'SELECT `%s` FROM `%s` WHERE 1=1 AND %s LIMIT 1',
                $field,
                $table,
                $sql_filters
            )
        );
        if ($stm !== false && $stm->num_rows > 0) {
            $rs = $stm->fetch_array();
            if (is_array($rs) === true) {
                $rs = array_shift($rs);
                if (is_array($rs) === true) {
                    return array_shift($rs);
                }
            }
        }

        return false;
    }


    /**
     * Applies all MR updates pending from path.
     *
     * @return boolean
     */
    public function applyAllMRPending()
    {
        // Apply MR.
        try {
            $mr_files = $this->getMRFiles($this->productPath);
            // Aprox 50%.
            if (count($mr_files) > 0) {
                foreach ($mr_files as $mr) {
                    $this->updateMR($this->productPath.'/extras/mr/'.$mr);
                    if ($this->dbhHistory !== null) {
                        $historical_MR = $this->getValue(
                            'value',
                            'tconfig',
                            ['token' => 'MR'],
                            $this->dbhHistory
                        );

                        $this->updateMR(
                            $this->productPath.'/extras/mr/'.$mr,
                            $this->dbhHistory,
                            $historical_MR
                        );
                    }

                    // Update versions.
                    $this->updateLocalDatabase();
                }
            }
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage()."\n";
            return false;
        }

        return true;
    }


    /**
     * Retrieve a list of MR files given path.
     *
     * @param string $path Patch files path.
     *
     * @return array
     * @throws \Exception On error.
     */
    private function getMRFiles(string $path):array
    {
        $mr_files = [];

        if (is_dir($path) !== true || is_readable($path) !== true) {
            throw new \Exception('Path ['.$path.'] is not readable');
        }

        if (is_dir($path.'/extras/mr/') === false) {
            // No directory, no MR.
            return [];
        }

        $pd = opendir($path.'/extras/mr/');
        if ((bool) $pd !== true) {
            throw new \Exception('extras/mr is not readable');
        }

        while (false !== ($pf = readdir($pd))) {
            $matches = [];
            if (preg_match('/(\d+).sql$/', $pf, $matches) > 0) {
                if ($matches[1] > $this->MR) {
                    $mr_files[(int) $matches[1]] = $pf;
                }
            }
        }

        closedir($pd);

        // Sort mr files.
        ksort($mr_files);

        return $mr_files;
    }


    /**
     * Update files.
     *
     * @param string  $version Used to translate paths.
     * @param string  $from    Update all files from path into product.
     * @param string  $to      Product path.
     * @param boolean $test    Test operation, do not perform.
     * @param boolean $classic Process classic opensource package.
     *
     * @return void
     * @throws \Exception On error.
     */
    private function updateFiles(
        string $version,
        string $from,
        string $to,
        bool $test=false,
        bool $classic=false
    ) :void {
        if (is_dir($from) !== true || is_readable($from) !== true) {
            throw new \Exception('Cannot access patch files '.$from);
        }

        if (is_dir($to) !== true || is_writable($to) !== true) {
            throw new \Exception('Cannot write to installation files '.$to);
        }

        // Used to translate paths.
        $substr = $this->tmpDir.'/downloads/'.$version;
        if ($classic === true) {
            $substr .= '/pandora_console';
        }

        $pd = opendir($from);
        if ((bool) $pd !== true) {
            throw new \Exception('Files are not readable');
        }

        $created_directories = [];

        while (($pf = readdir($pd)) !== false) {
            if ($pf !== '.' && $pf !== '..') {
                $pf = $from.$pf;
                $dest = $to.str_replace($substr, '', $pf);
                $target_folder = dirname($dest);

                if (is_dir($pf) === true) {
                    // It's a directory.
                    if (is_dir($dest) === true) {
                        // Target directory already exists.
                        if ($test === true && is_writable($dest) !== true) {
                            throw new \Exception($dest.' is not writable');
                        }
                    } else if (is_file($dest) === true) {
                        $err = '['.$dest.'] $err is expected to be ';
                        $err .= 'a directory, please remove it.';
                        throw new \Exception($err);
                    } else {
                        mkdir($dest);
                        $created_directories[] = $dest;
                    }

                    $this->updateFiles($version, $pf.'/', $to, $test, $classic);
                } else {
                    // It's a file.
                    if ($test === true) {
                        if (is_writable($target_folder) !== true) {
                            throw new \Exception($dest.' is not writable');
                        }
                    } else {
                        // Rename file.
                        rename($pf, $dest);
                    }
                }
            }
        }

        closedir($pd);

        if ($test === true) {
            $created_directories = array_reverse($created_directories);
            foreach ($created_directories as $dir) {
                rmdir($dir);
            }
        }
    }


    /**
     * Completely deletes a folder.
     *
     * @param string $folder Folder to delete.
     *
     * @return void
     */
    private function rmrf(string $folder):void
    {
        if (is_dir($folder) !== true || is_readable($folder) !== true) {
            return;
        }

        $pd = opendir($folder);
        if ((bool) $pd === true) {
            while (($pf = readdir($pd)) !== false) {
                if ($pf !== '.' && $pf !== '..') {
                    $pf = $folder.$pf;

                    if (is_dir($pf) === true) {
                        // It's a directory.
                        $this->rmrf($pf.'/');
                    } else {
                        // It's a file.
                        unlink($pf);
                    }
                }
            }

            closedir($pd);
            rmdir($folder);
        }
    }


    /**
     * Process files deletion given a list.
     *
     * @param string $delete_files_txt Path to delete_files.txt.
     *
     * @return array Paths processed.
     */
    private function deleteFiles(string $delete_files_txt):array
    {
        if (is_readable($delete_files_txt) === false) {
            return [];
        }

        $content = file_get_contents($delete_files_txt);
        $files = explode("\n", $content);
        $processed = [];
        foreach ($files as $file) {
            $file = trim(str_replace("\0", '', $this->productPath.'/'.$file));
            if (file_exists($file) === true
                && is_file($delete_files_txt) === true
                && is_dir($file) === false
            ) {
                unlink($file);
                $processed[$file] = 'removed';
            } else if (is_dir($file) === true) {
                $processed[$file] = 'skipped, is a directory';
            } else {
                $processed[$file] = 'skipped. Unreachable.';
            }
        }

        return $processed;
    }


    /**
     * Retrieves a list of files included in given OUM.
     *
     * @param string $filename File to be analyzed.
     *
     * @return array|null With the results or null if error.
     */
    public static function checkOUMContent(string $filename):?array
    {
        $files = [];

        if (class_exists('\ZipArchive') === false) {
            return null;
        }

        $zip = new \ZipArchive;
        $res = $zip->open($filename);
        if ($res === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $files[] = $stat['name'];
            }

            $zip->close();
        } else {
            return null;
        }

        return $files;
    }


    /**
     * Retrieves a list of files included in given file, used for server updates.
     *
     * @param string $filename File to be analyzed.
     *
     * @return array|null With the results or null if error.
     */
    public static function checkTGZContent(string $filename):?array
    {
        $files = [];

        if (class_exists('\PharData') === false) {
            return null;
        }

        $er = error_reporting();
        error_reporting(E_ALL ^ E_NOTICE);
        set_error_handler(
            function ($errno, $errstr) {
                throw new \Exception($errstr, $errno);
            },
            (E_ALL ^ E_NOTICE)
        );

        register_shutdown_function(
            function () {
                $error = error_get_last();
                if (null !== $error
                    && $error['type'] === (E_ALL ^ E_NOTICE)
                ) {
                    echo __('Failed to analyze package: %s', $error['message']);
                }
            }
        );

        try {
            $phar = new \PharData($filename);
            $files = self::recursiveFileList($phar, '');
        } catch (\Exception $e) {
            return null;
        }

        // Restore.
        error_reporting($er);
        restore_error_handler();

        return $files;
    }


    /**
     * Retrieve a list of files given item (could be a directory or file) from
     * pharData item.
     *
     * @param \PharData|\PharFileInfo $item Item to analyse.
     * @param string                  $path Anidated path.
     *
     * @return array Of file paths.
     */
    private static function recursiveFileList($item, string $path=''):array
    {
        $return = [];
        if ($item->isDir() === true) {
            $pd = new \PharData($item->getPathname());
            $return[] = $path.'/'.$item->getFilename();
            foreach ($pd as $child) {
                $return = array_merge(
                    $return,
                    self::recursiveFileList(
                        $child,
                        $path.'/'.$item->getFilename()
                    )
                );
            }
        } else {
            $return[] = $path.'/'.$item->getFilename();
        }

        return $return;
    }


    /**
     * Update product to next version available.
     *
     * @param array|null $package Update to manually uploaded package.
     *                            Format:
     *                            [
     *                            version
     *                            file_path
     *                            ]
     *                            Version: version to install, file_path where's stored the oum package.
     *
     * @return boolean|null True if success, false if not, null if already
     *                      up to date.
     * @throws \Exception No exception is thrown, is handled using lastError
     *                    and returned value.
     */
    public function updateNextVersion(?array $package=null):?bool
    {
        if ($this->lock() !== true) {
            return null;
        }

        // Some transfer errors (e.g.  failed to open stream: Connection timed
        // out) or OS issues (No space left on device) should be captured as
        // php errors.
        $er = error_reporting();
        error_reporting(E_ALL ^ E_NOTICE);
        set_error_handler(
            function ($errno, $errstr, $at, $line) {
                throw new \Exception($errstr.' '.$at.':'.$line, $errno);
            },
            (E_ALL ^ E_NOTICE)
        );

        if ($package === null) {
            // Use online update.
            if ($this->globalTask === null) {
                // Single update.
                $this->percentage = 0;
                $this->currentTask = __('Searching update package');
            }

            // 1. List updates and get next one.
            $this->notify(0, 'Retrieving updates.');
            // Reload if needed.
            $this->listUpdates();
            // Work over all upgrades not LTS only.
            $updates = $this->updates;
            $nextUpdate = null;

            if (is_array($updates) === true) {
                $nextUpdate = array_shift($updates);
            }

            while ($nextUpdate !== null
                && $nextUpdate['version'] <= $this->currentPackage
            ) {
                $nextUpdate = array_shift($updates);
            }

            if ($nextUpdate === null) {
                // No more updates pending.
                $this->notify(100, 'No more updates pending.');
                $this->unlock();
                return null;
            }

            // 2. Retrieve file.
            if ($this->globalTask === null) {
                // Single update.
                $this->percentage = 10;
                $this->currentTask = __('Retrieving update');
            }

            $this->notify(0, 'Downloading update '.$nextUpdate['version'].'.');
            $file = $this->post(
                [
                    'action'    => 'get_package',
                    'arguments' => ['package' => $nextUpdate['file_name']],
                ],
                true
            );

            $file_path = $this->tmpDir.'/';
            $classic_open_packages = false;
            try {
                if (substr($nextUpdate['file_name'], 0, 7) === 'http://') {
                    $file_path .= 'package_'.$nextUpdate['version'].'.tar.gz';
                    $file = $this->curl($nextUpdate['file_name'], []);
                    file_put_contents(
                        $file_path,
                        $file
                    );
                    $classic_open_packages = true;
                } else {
                    if (preg_match('/Error\: file not found./', $file) > 0) {
                        $this->lastError = $file;
                        $this->unlock();
                        return false;
                    }

                    $file_path .= basename($nextUpdate['file_name']);
                    file_put_contents(
                        $file_path,
                        $file
                    );
                }
            } catch (\Exception $e) {
                error_reporting($er);
                $this->lastError = $e->getMessage();
                $this->notify(10, $this->lastError, false);
                $this->unlock();
                return false;
            }

            $signature = $this->post(
                [
                    'action'    => 'get_package_signature',
                    'arguments' => ['package' => $nextUpdate['file_name']],
                ],
                1
            );

            if ($this->insecure === false
                && $this->validateSignature($file_path, $signature) !== true
            ) {
                $this->lastError = 'Signatures does not match.';
                $this->notify(10, $this->lastError, false);
                $this->unlock();
                return false;
            }

            if ($this->propagateUpdates === true) {
                $this->saveSignature(
                    $signature,
                    $nextUpdate['file_name']
                );
            }
        } else {
            // Manually uploaded package.
            if (is_numeric($package['version']) !== true) {
                $this->lastError = 'Version does not match required format (numeric)';
                $this->notify(10, $this->lastError, false);
                $this->unlock();
                return false;
            }

            $classic_open_packages = false;
            $nextUpdate = [ 'version' => $package['version'] ];
            $file_path = $package['file_path'];
        }

        $version = $nextUpdate['version'];
        $extract_to = $this->tmpDir.'/downloads/'.$version.'/';

        // Cleanup download target.
        $this->rmrf($extract_to);

        // Uncompress.
        if ($this->globalTask === null) {
            // Single update.
            $this->percentage = 15;
            $this->currentTask = __('Extracting package');
        }

        $this->notify(0, 'Extracting...');

        if ($classic_open_packages === true) {
            try {
                $phar = new \PharData($file_path);
                if ($phar->extractTo(
                    $extract_to,
                    // Extract all files.
                    null,
                    // Overwrite if exist.
                    true
                ) !== true
                ) {
                    // When PharData failes because of no space left on device
                    // a PHP Notice is received instead of a PharData\Exception.
                    $err = error_get_last();
                    if ($err !== null) {
                        throw new \Exception($err['message']);
                    }
                }
            } catch (\Exception $e) {
                error_reporting($er);
                $this->lastError = $e->getMessage();
                $this->notify(15, $this->lastError, false);
                $this->unlock();
                return false;
            }
        } else {
            if (class_exists('\ZipArchive') === false) {
                error_reporting($er);
                $this->lastError = 'Unable to unzip OUM package. Please install php-zip.';
                $this->notify(15, $this->lastError, false);
                $this->unlock();
                return false;
            }

            $zip = new \ZipArchive;
            $res = $zip->open($file_path);
            if ($res === true) {
                $zip->extractTo($extract_to);
                $zip->close();
            } else {
                error_reporting($er);
                $this->lastError = 'Unable to unzip OUM package.';
                $this->notify(15, $this->lastError, false);
                $this->unlock();
                return false;
            }
        }

        // Restore previous reporting level.
        error_reporting($er);
        restore_error_handler();

        $downloaded_package_files = $extract_to;
        if ($classic_open_packages === true) {
            // Fix to avoid extra directories in classic OpenSource packages.
            $extract_to .= 'pandora_console/';
        }

        // Test files update.
        if ($this->globalTask === null) {
            // Single update.
            $this->percentage = 35;
            $this->currentTask = __('Testing files');
        }

        $this->notify(0, 'Testing files from udpate...');
        try {
            $this->updateFiles(
                $version,
                $extract_to,
                $this->productPath,
                // Test only.
                true,
                $classic_open_packages
            );
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->notify(25, $this->lastError, false);
            $this->unlock();
            return false;
        }

        // Apply MR.
        try {
            $mr_files = $this->getMRFiles($extract_to);
            // Aprox 50%.
            if (count($mr_files) > 0) {
                $pct = floor(20 / count($mr_files));
                $step = floor(100 / count($mr_files));
                $percentage = 0;
                foreach ($mr_files as $mr) {
                    if ($this->globalTask === null) {
                        // Single update.
                        $this->percentage = (55 + $pct);
                        $this->currentTask = __('Applying MR %s', $mr);
                    }

                    $this->notify($percentage, 'Applying MR update '.$mr.'.');
                    $this->updateMR($extract_to.'/extras/mr/'.$mr);
                    if ($this->dbhHistory !== null) {
                        $this->notify($percentage, 'Applying MR update '.$mr.' on history database.');
                        $historical_MR = $this->getValue(
                            'value',
                            'tconfig',
                            ['token' => 'MR'],
                            $this->dbhHistory
                        );

                        $this->updateMR(
                            $extract_to.'/extras/mr/'.$mr,
                            $this->dbhHistory,
                            $historical_MR
                        );
                    }

                    // Update versions.
                    $this->updateLocalDatabase();
                    $percentage += $step;
                }
            }
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->notify(50, $this->lastError, false);
            $this->unlock();
            return false;
        }

        // Apply files update.
        if ($this->globalTask === null) {
            // Single update.
            $this->percentage = 75;
            $this->currentTask = __('Applying file updates');
        }

        try {
            $this->notify(0, 'Updating files...');
            $this->updateFiles(
                $version,
                $extract_to,
                $this->productPath,
                // Effective application.
                false,
                $classic_open_packages
            );
        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
            $this->notify(75, $this->lastError, false);
            $this->unlock();
            return false;
        }

        if ($this->globalTask === null && $this->offline === false) {
            $this->percentage = 90;
            $this->currentTask = __('Retrieving server update');
            // Schedule server update.
            $this->updateServerPackage(null, $version);
        }

        $this->notify(95, 'Cleaning downloaded files...');

        // Save package update if propagating updates.
        if ($this->propagateUpdates === true) {
            $this->savePackageToRepo($file_path);
        }

        if ($this->globalTask === null) {
            // Single update.
            $this->percentage = 95;
            $this->currentTask = __('Cleaning');
        }

        // Remove already extracted files (oum/tar.gz).
        $this->rmrf($file_path);

        // Cleanup downloaded files.
        $this->rmrf($downloaded_package_files);

        // Process file eliminations (file: delete_files.txt).
        $cleaned = $this->deleteFiles(
            $this->productPath.'/extras/delete_files/delete_files.txt'
        );

        // Success.
        if ($this->globalTask === null) {
            // Single update.
            $this->percentage = 100;
            $this->currentTask = __('Completed');
        }

        $this->notify(
            100,
            'Updated to '.$version,
            true,
            ['deleted_files' => $cleaned]
        );

        $this->currentPackage = $version;

        if ($this->dbh !== null) {
            $this->updateLocalDatabase();
        }

        if (is_callable($this->postUpdateFN) === true) {
            call_user_func(
                $this->postUpdateFN,
                $this->currentPackage,
                'console'
            );
        }

        $this->unlock();
        return true;
    }


    /**
     * Return next LTS version available.
     *
     * @return string|null Next version string or null if no version present.
     */
    public function getNextLTSVersion():?string
    {
        $lts = $this->listUpdates();
        if ($lts === null) {
            return null;
        }

        $target = array_shift($lts);

        return $target['version'];
    }


    /**
     * Return latest LTS version available.
     *
     * @return string|null Latest version string or null if no version present.
     */
    public function getLastLTSVersion():?string
    {
        $lts = $this->listUpdates();
        if ($lts === null) {
            return null;
        }

        $target = array_pop($lts);

        return $target['version'];
    }


    /**
     * Update product to latest version available or target if specified.
     *
     * @param string|null $target_version Target version if needed.
     *
     * @return string Last version reached.
     */
    public function updateLastVersion(?string $target_version=null):string
    {
        $this->percentage = 0;
        $this->listUpdates();

        if ($target_version !== null) {
            // Update to target version.
            $total_updates = 0;
            foreach ($this->updates as $update) {
                $total_updates++;
                if ($update['version'] === $target_version) {
                    break;
                }
            }
        } else {
            // All updates.
            $total_updates = count($this->updates);
        }

        if ($total_updates > 0) {
            $pct = (90 / $total_updates);
            do {
                $this->listUpdates();
                $this->globalTask = __('Updating to '.$this->nextUpdate);
                $rc = $this->updateNextVersion();
                if ($rc === false) {
                    // Failed to upgrade to next version.
                    break;
                }

                if ($this->nextUpdate === $target_version) {
                    // Reached end.
                    $rc = null;
                }

                // If rc is null, latest version available is applied.
                if ($rc !== null) {
                    $this->percentage += $pct;
                }
            } while ($rc !== null);
        }

        $last_error = $this->lastError;
        $this->updateServerPackage(null, $this->currentPackage);

        if ($this->lastError === null) {
            // Populate latest console error.
            $this->lastError = $last_error;
        }

        $this->percentage = 100;
        $this->notify(100, 'Updated to '.$this->getVersion().'.');

        return $this->currentPackage;
    }


    /**
     * Retrieve current package version installed.
     *
     * @return string Version.
     */
    public function getVersion():string
    {
        return ($this->currentPackage ?? '');
    }


    /**
     * Retrieve current MR version installed.
     *
     * @return integer Version.
     */
    public function getMR():int
    {
        return $this->MR;
    }


    /**
     * Return database link.
     *
     * @return \mysqli
     */
    public function getDBH():\mysqli
    {
        return $this->dbh;
    }


    /**
     * Store into dbh (if any) current percentage of completion and a message.
     *
     * @param float   $percent Current completion percentage.
     * @param string  $msg     Mesage to be displayed to user.
     * @param boolean $status  Status of current operation (true: healthy,
     *                         false: failed).
     * @param array   $extra   Extra messages, for instance, files cleaned.
     *
     * @return void
     */
    private function notify(
        float $percent,
        string $msg,
        bool $status=true,
        array $extra=[]
    ):void {
        if ($this->dbh !== null) {
            static $field_exists;
            $stm = $this->dbh->query(
                'SELECT count(*) FROM `tconfig`
                 WHERE `token` = "progress_update"'
            );

            if ($stm !== false) {
                $field_exists = $stm->fetch_row();
                $field_exists = (bool) $field_exists[0];
            }

            $task_msg = null;
            if ($this->globalTask !== null) {
                $task_msg = $this->globalTask;
            } else if ($this->currentTask !== null) {
                $task_msg = $this->currentTask;
            }

            $updates = json_encode(
                [
                    'global_percent' => $this->percentage,
                    'processing'     => $task_msg,
                    'percent'        => $percent,
                    'status'         => $status,
                    'message'        => $msg,
                    'extra'          => $extra,
                ],
                JSON_UNESCAPED_UNICODE
            );

            if ($field_exists === false) {
                $q = sprintf(
                    'INSERT INTO `tconfig`(`token`, `value`)
                     VALUES ("progress_update", \'%s\')',
                    $updates
                );
                $r = $this->dbh->query($q);
                $field_exists = true;
            } else {
                $this->dbh->query(
                    sprintf(
                        'UPDATE `tconfig` SET `value` = \'%s\'
                         WHERE `token` = "progress_update"',
                        $updates
                    )
                );
            }
        }
    }


    /**
     * Deploy server update.
     *
     * @param array|null $package Server package to be deployed.
     * @param float|null $version Target version to aquire.
     *
     * @return boolean Success or not.
     */
    public function updateServerPackage(
        ?array $package=null,
        ?float $version=null
    ):bool {
        if (empty($this->remoteConfig) === true) {
            $this->lastError = 'Remote configuration directory not defined, please define \'remoteConfig\' in call';
            return false;
        }

        $file_path = $this->tmpDir.'/';
        if ($version === null) {
            $version = $this->getVersion();
        }

        if ($this->propagateUpdates === true) {
            $updatesRepo = $this->remoteConfig.'/updates/repo';
            if (is_dir($updatesRepo) === false) {
                mkdir($updatesRepo, 0777, true);
                chmod($updatesRepo, 0770);
            }
        }

        if ($package === null) {
            // Retrieve package from UMS.
            $this->notify(0, 'Downloading server update '.$version.'.');
            $file = $this->post(
                [
                    'action'    => 'get_server_package',
                    'arguments' => ['version' => $version],
                ],
                1
            );

            if (empty($file) === true) {
                // No content.
                return false;
            }

            $file_name = 'pandorafms_server-'.$version.'.tar.gz';
            $official_name = 'pandorafms_server_enterprise-7.0NG.%s_x86_64.tar.gz';
            $filename_repo = sprintf($official_name, $version);
            $official_path = $file_path.$filename_repo;

            if (file_put_contents($official_path, $file) === false) {
                $this->lastError = 'Failed to store server update package.';
                return false;
            }

            $signature = $this->post(
                [
                    'action'    => 'get_server_package_signature',
                    'arguments' => ['version' => $version],
                ],
                1
            );

            if ($this->insecure === false
                && $this->validateSignature($official_path, $signature) !== true
            ) {
                $this->lastError = 'Signatures does not match';
                return false;
            }

            if ($this->propagateUpdates === true) {
                $this->saveSignature(
                    $signature,
                    $filename_repo
                );
            }

            $file_path .= $file_name;
            rename($official_path, $file_path);
        } else {
            $file_path = $package['file_path'];
            $version = $package['version'];
        }

        // Save package update if propagating updates.
        if ($this->propagateUpdates === true) {
            $this->savePackageToRepo(
                $file_path,
                $filename_repo
            );
        }

        // Target file name.
        $file_name = 'pandorafms_server.tar.gz';

        $this->notify(90, 'Scheduling server update '.$version.' to pandora_ha');

        $serverRepo = $this->remoteConfig.'/updates/server';

        // Clean old repo files.
        $this->rmrf($serverRepo.'/');

        if (is_dir($serverRepo) === false) {
            mkdir($serverRepo, 0777, true);
            chmod($serverRepo, 0770);
        }

        $rc = rename($file_path, $serverRepo.'/'.$file_name);
        chmod($serverRepo.'/'.$file_name, 0660);

        if ($rc === false) {
            $this->lastError = 'Unable to deploy server update from '.$file_path;
            $this->lastError .= ' to '.$serverRepo.'/'.$file_name;
            return false;
        }

        file_put_contents($serverRepo.'/version.txt', $version);
        chmod($serverRepo.'/version.txt', 0660);

        // Success.
        $this->notify(100, 'Server update scheduled.');
        $this->lastError = null;

        if (is_callable($this->postUpdateFN) === true) {
            call_user_func(
                $this->postUpdateFN,
                (string) $version,
                'server'
            );
        }

        return true;
    }


    /**
     * Validate received signature.
     *
     * @param string $file      File path.
     * @param string $signature Received signature in base64 format.
     *
     * @return boolean
     */
    public function validateSignature(string $file, string $signature)
    {
        if (empty($signature) === true) {
            return false;
        }

        // Compute the hash of the data.
        $hex_hash = hash_file('sha512', $file);
        if ($hex_hash === false) {
            return false;
        }

        // Verify the signature.
        if (openssl_verify($hex_hash, base64_decode($signature), PUB_KEY, 'sha512') === 1) {
            return true;
        }

        return false;
    }


    /**
     * Save signature to file.
     *
     * @param string $signature File signature.
     * @param string $filename  Signed filename (i.e. package_NUMBER.oum).
     *
     * @return boolean Success or not.
     */
    public function saveSignature(
        string $signature,
        string $filename
    ) {
        $updatesRepo = $this->remoteConfig.'/updates/repo';
        if (is_dir($updatesRepo) === false) {
            mkdir($updatesRepo, 0777, true);
            chmod($updatesRepo, 0770);
        }

        $target = $updatesRepo.'/'.basename($filename).SIGNATURE_EXTENSION;

        if (file_exists($target) === true) {
            unlink($target);
        }

        // Save signature to file (MC -> nodes).
        $rc = (bool) file_put_contents($target, $signature);
        chmod($target, 0660);
        return $rc;
    }


    /**
     * Save update package to repository.
     *
     * @param string      $filename Target filename.
     * @param string|null $target   Store as target in repo folder.
     *
     * @return void
     */
    private function savePackageToRepo(string $filename, ?string $target=null)
    {
        if ($this->propagateUpdates === true) {
            if (file_exists($filename) === false) {
                return;
            }

            $updatesRepo = $this->remoteConfig.'/updates/repo';
            if (is_dir($updatesRepo) === false) {
                mkdir($updatesRepo, 0777, true);
                chmod($updatesRepo, 0770);
            }

            $new_name = $target;
            if ($new_name === null) {
                $new_name = basename($filename);
            }

            if (file_exists($updatesRepo.'/'.$new_name) === true) {
                unlink($updatesRepo.'/'.$new_name);
            }

            copy(
                $filename,
                $updatesRepo.'/'.$new_name
            );
            chmod($updatesRepo.'/'.$new_name, 0660);
        }
    }


    /**
     * Updates tokens current_package and MR in current database.
     *
     * @param \mysqli|null $dbh Target dbh where apply changes.
     * @param integer|null $mr  MR version to update.
     *
     * @return void
     */
    private function updateLocalDatabase(?\mysqli $dbh=null, ?int $mr=null):void
    {
        // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        if ($dbh === null) {
            $dbh = $this->dbh;
        }

        $create_current_package_field = false;
        $create_mr_field = false;

        $stm = $dbh->query(
            'SELECT `value` FROM `tconfig`
             WHERE `token`="current_package"
             LIMIT 1'
        );
        if ($stm !== false && $stm->num_rows === 0) {
            $create_current_package_field = true;
        }

        $stm = $dbh->query(
            'SELECT `value` FROM `tconfig`
             WHERE `token`="MR"
             LIMIT 1'
        );
        if ($stm !== false && $stm->num_rows === 0) {
            $create_mr_field = true;
        }

        // Current package.
        if ($create_current_package_field === true) {
            $q = sprintf(
                'INSERT INTO `tconfig`(`token`, `value`)
                 VALUES ("current_package", "%s")',
                $this->getVersion()
            );
        } else {
            $q = sprintf(
                'UPDATE `tconfig` SET `value`= "%s"
                 WHERE `token` = "current_package"',
                $this->getVersion()
            );
        }

        if ($dbh->query($q) === false) {
            $this->lastError = $dbh->error;
        }

        // MR.
        if ($create_mr_field === true) {
            $q = sprintf(
                'INSERT INTO `tconfig`(`token`, `value`)
                 VALUES ("MR", %d)',
                ($mr ?? $this->getMR())
            );
        } else {
            $q = sprintf(
                'UPDATE `tconfig` SET `value`= %d
                 WHERE `token` = "MR"',
                ($mr ?? $this->getMR())
            );
        }

        if ($dbh->query($q) === false) {
            $this->lastError = $dbh->error;
        }
    }


    /**
     * Retrieves current update progress.
     *
     * @return array.
     */
    public function getUpdateProgress():array
    {
        if ($this->dbh === null) {
            return [];
        }

        // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $stm = $this->dbh->query(
            'SELECT `value` FROM `tconfig`
             WHERE `token`="progress_update"
             LIMIT 1'
        );
        if ($stm !== false && $stm->num_rows === 0) {
            return [];
        }

        $progress = $stm->fetch_array();
        $progress = $progress[0];
        $progress = json_decode($progress, JSON_OBJECT_AS_ARRAY);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->lastError = json_last_error_msg();
            return [];
        }

        if ($progress === null) {
            return [];
        }

        if (is_array($progress) === false) {
            // Old data.
            return [];
        }

        return $progress;
    }


    /**
     * Retrieves campaign messages from UMS.
     *
     * @return array|null Array of campaign messages.
     */
    public function getMessages()
    {
        $rc = $this->post(
            [
                'action'    => 'get_messages',
                'arguments' => ['puid' => $this->registrationCode],
            ]
        );

        return $rc;
    }


    /**
     * Get a lock to process update.
     *
     * @return boolean True (allowed), false if not.
     */
    private function lock():bool
    {
        if ($this->dbh !== null) {
            $stm = $this->dbh->query(
                'SELECT IS_FREE_LOCK("umc_lock")'
            );

            if ($stm !== false) {
                $lock_status = $stm->fetch_row()[0];
            }

            if ((bool) $lock_status === true) {
                // No registers.
                $this->dbh->query(
                    sprintf(
                        'SELECT GET_LOCK("umc_lock", %d)',
                        self::MAX_LOCK_AGE
                    )
                );

                if (is_callable($this->setMaintenanceMode) === true) {
                    call_user_func($this->setMaintenanceMode);
                }

                // Available.
                return true;
            }

            // Locked.
            return false;
        }

        // No database available, use files.
        if (file_exists($this->lockfile) === true) {
            $lock_age = file_get_contents($this->lockfile);

            if ((time() - $lock_age) > self::MAX_LOCK_AGE) {
                unlink($this->lockfile);
            } else {
                // Locked.
                return false;
            }
        }

        file_put_contents($this->lockfile, time());

        if (is_callable($this->setMaintenanceMode) === true) {
            call_user_func($this->setMaintenanceMode);
        }

        // Available.
        return true;
    }


    /**
     * Unlock.
     *
     * @return void
     */
    private function unlock():void
    {
        if ($this->dbh !== null) {
            $this->dbh->query('SELECT RELEASE_LOCK("umc_lock")');
        } else {
            if (file_exists($this->lockfile) === true) {
                unlink($this->lockfile);
            }
        }

        if (is_callable($this->clearMaintenanceMode) === true) {
            call_user_func($this->clearMaintenanceMode);
        }
    }


    /**
     * Checking progress, check if any instance is running.
     *
     * @return boolean|null if no DB is set.
     */
    public function isRunning():?bool
    {
        if ($this->dbh === null) {
            return file_exists($this->lockfile);
        }

        $stm = $this->dbh->query(
            'SELECT IS_FREE_LOCK("umc_lock")'
        );

        if ($stm !== false) {
            $lock_status = $stm->fetch_row()[0];
        }

        if ((bool) $lock_status === true) {
            // Not running.
            return false;
        }

        // Running, locked.
        return true;
    }


}
