<?php
/**
 * Update Manager client
 *
 * @category   Class
 * @package    Update Manager
 * @subpackage Client UI
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
namespace UpdateManager\UI;

require_once __DIR__.'/../../../resources/helpers.php';

use UpdateManager\Client;

/**
 * Undocumented class
 */
class Manager
{

    const MODE_ONLINE = 0;
    const MODE_OFFLINE = 1;
    const MODE_REGISTER = 2;
    const PANDORA_TRIAL_ISSUER = 'Enterprise Trial License(Pandora FMS <info@pandorafms.com>)';

    /**
     * Current mode (view).
     *
     * @var integer
     */
    private $mode;

    /**
     * Update Manager Client.
     *
     * @var Client
     */
    private $umc;

    /**
     * Public url
     *
     * @var string
     */
    private $publicUrl;

    /**
     * Public url for Ajax calls.
     *
     * @var string
     */
    private $ajaxUrl;

    /**
     * Ajax page.
     *
     * @var string
     */
    private $ajaxPage;

    /**
     * AJAX auth code to avoid direct calls.
     *
     * @var string
     */
    private $authCode;

    /**
     * Allow install offline packages not following current version.
     *
     * @var boolean
     */
    private $allowOfflinePatches = false;

    /**
     * Affects getUrl in order to map resources.
     *
     * @var boolean
     */
    private $composer = false;

    /**
     * Working in LTS mode.
     *
     * @var boolean
     */
    private $lts = false;


    /**
     * Undocumented function
     *
     * @param string       $public_url Url to access resources, ui_get_full_url.
     * @param string       $ajax_url   Ajax url, ui_get_full_url('ajax.php').
     * @param string|null  $page       Ajax page (Artica style).
     * @param array        $settings   UMC settings.
     * @param integer|null $mode       Update Manager mode (online, offline or
     *                                 register).
     * @param boolean      $composer   Included from composer package or direct library.
     */
    public function __construct(
        string $public_url,
        ?string $ajax_url=null,
        ?string $page=null,
        array $settings,
        ?int $mode=null,
        bool $composer=false
    ) {
        $this->mode = 0;
        $this->publicUrl = '/';
        $this->ajaxUrl = '#';
        $this->mode = self::MODE_ONLINE;
        $this->composer = $composer;
        $this->lts = false;

        if (empty($public_url) === false) {
            $this->publicUrl = $public_url;
        }

        if (empty($ajax_url) === false) {
            $this->ajaxUrl = $ajax_url;
        } else {
            $this->ajaxUrl = '#';
        }

        if (empty($page) === false) {
            $this->ajaxPage = $page;
        }

        if (empty($mode) === false) {
            $this->mode = $mode;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (isset($_SESSION['cors-auth-code']) === true) {
            $this->authCode = $_SESSION['cors-auth-code'];
        }

        if (empty($this->authCode) === true) {
            $this->authCode = hash('sha256', session_id().time().rand());
            $_SESSION['cors-auth-code'] = $this->authCode;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        if ($mode === self::MODE_OFFLINE) {
            $settings['offline'] = true;
        }

        if (isset($settings['lts']) === true) {
            $this->lts = $settings['lts'];
        }

        if (isset($settings['allowOfflinePatches']) === true) {
            $this->allowOfflinePatches = (bool) $settings['allowOfflinePatches'];
        }

        $this->umc = new Client($settings);
    }


    /**
     * Run view.
     *
     * @return void
     */
    public function run()
    {
        if (isset($_REQUEST['data']) === true) {
            $data = json_decode($_REQUEST['data'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                foreach ($data as $k => $v) {
                    $_REQUEST[$k] = $v;
                }
            }
        }

        if (isset($_REQUEST['ajax']) === true
            && (int) $_REQUEST['ajax'] === 1
        ) {
            $this->ajax();
            return;
        }

        switch ($this->mode) {
            case self::MODE_OFFLINE:
                $this->offline();
            break;

            case self::MODE_REGISTER:
                $this->register();
            break;

            default:
            case self::MODE_ONLINE:
                $this->online();
            break;
        }
    }


    /**
     * Update Manager Client Online Mode page.
     *
     * @return void
     */
    public function online()
    {
        if ($this->umc->isRegistered() === false) {
            $this->register();
        } else {
            View::render(
                'online',
                [
                    'version'  => $this->umc->getVersion(),
                    'mr'       => $this->umc->getMR(),
                    'error'    => $this->umc->getLastError(),
                    'asset'    => function ($rp) {
                        echo $this->getUrl($rp);
                    },
                    'authCode' => $this->authCode,
                    'ajax'     => $this->ajaxUrl,
                    'ajaxPage' => $this->ajaxPage,
                    'progress' => $this->umc->getUpdateProgress(),
                    'running'  => $this->umc->isRunning(),
                    'mode'     => self::MODE_ONLINE,
                ]
            );
        }
    }


    /**
     * Update Manager Client Offline Mode page.
     *
     * @return void
     */
    public function offline()
    {
        View::render(
            'offline',
            [
                'version'             => $this->umc->getVersion(),
                'mr'                  => $this->umc->getMR(),
                'error'               => $this->umc->getLastError(),
                'asset'               => function ($rp) {
                    echo $this->getUrl($rp);
                },
                'authCode'            => $this->authCode,
                'ajax'                => $this->ajaxUrl,
                'ajaxPage'            => $this->ajaxPage,
                'progress'            => $this->umc->getUpdateProgress(),
                'running'             => $this->umc->isRunning(),
                'insecure'            => $this->umc->isInsecure(),
                'allowOfflinePatches' => $this->allowOfflinePatches,
                'mode'                => self::MODE_OFFLINE,
            ]
        );
    }


    /**
     * Update Manager Client Registration page.
     *
     * @return void
     */
    public function register()
    {
        View::render(
            'register',
            [
                'version'  => $this->umc->getVersion(),
                'mr'       => $this->umc->getMR(),
                'error'    => $this->umc->getLastError(),
                'asset'    => function ($rp) {
                    echo $this->getUrl($rp);
                },
                'authCode' => $this->authCode,
                'ajax'     => $this->ajaxUrl,
                'ajaxPage' => $this->ajaxPage,
                'mode'     => self::MODE_REGISTER,
            ]
        );
    }


    /**
     * Retrieve full url to a relative path if given.
     *
     * @param string|null $relative_path Relative path to reach publicly.
     *
     * @return string Url.
     */
    public function getUrl(?string $relative_path)
    {
        $path = 'vendor/articapfms/update_manager_client/';
        if ($this->composer === false) {
            $path = '';
        }

        return $this->publicUrl.$path.$relative_path;
    }


    /**
     * Return ajax response.
     *
     * @return void
     */
    public function ajax()
    {
        if (function_exists('getallheaders') === true) {
            $headers = getallheaders();
        } else {
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) === 'HTTP_') {
                    $headers[str_replace(
                        ' ',
                        '-',
                        ucwords(
                            strtolower(
                                str_replace(
                                    '_',
                                    ' ',
                                    substr($name, 5)
                                )
                            )
                        )
                    )] = $value;
                }
            }
        }

        if ($this->authCode !== $_REQUEST['cors']) {
            header('HTTP/1.1 401 Unauthorized');
            exit;
        }

        try {
            // Execute target action.
            switch ($_REQUEST['action']) {
                case 'nextUpdate':
                    if ($this->lts === true) {
                        $next_version = $this->umc->getNextLTSVersion();
                        $result = $this->umc->updateLastVersion($next_version);
                    } else {
                        $result = $this->umc->updateNextVersion();
                    }

                    if ($result !== true) {
                        $error = $this->umc->getLastError();
                    }

                    $return = [
                        'version'  => $this->umc->getVersion(),
                        'mr'       => $this->umc->getMR(),
                        'messages' => $this->umc->getLastError(),
                    ];
                break;

                case 'latestUpdate':
                    if ($this->lts === true) {
                        $latest_version = $this->umc->getLastLTSVersion();
                        $result = $this->umc->updateLastVersion($latest_version);
                    } else {
                        $result = $this->umc->updateLastVersion();
                    }

                    $return = [
                        'version'  => $this->umc->getVersion(),
                        'mr'       => $this->umc->getMR(),
                        'messages' => $this->umc->getLastError(),
                    ];
                break;

                case 'status':
                    $return = $this->umc->getUpdateProgress();
                break;

                case 'getUpdates':
                    $return = $this->getUpdatesList();
                break;

                case 'uploadOUM':
                    $return = $this->processOUMUpload();
                break;

                case 'validateUploadedOUM':
                    $return = $this->validateUploadedOUM();
                break;

                case 'installUploadedOUM':
                    $return = $this->installOUMUpdate();
                break;

                case 'register':
                    $return = $this->registerConsole();
                break;

                case 'unregister':
                    $return = $this->unRegisterConsole();
                break;

                default:
                    $error = 'Unknown action '.$_REQUEST['action'];
                    header('HTTP/1.1 501 Unknown action');
                break;
            }
        } catch (\Exception $e) {
            $error = 'Error '.$e->getMessage();
            $error .= ' in '.$e->getFile().':'.$e->getLine();
            header('HTTP/1.1 500 '.$error);
        }

        // Response.
        if (empty($error) === false) {
            if ($headers['Accept'] === 'application/json') {
                echo json_encode(
                    ['error' => $error]
                );
            } else {
                echo $error;
            }

            return;
        }

        if ($headers['Accept'] === 'application/json') {
            echo json_encode(
                ['result' => $return]
            );
        } else {
            echo $return;
        }
    }


    /**
     * Prints a pretty list of updates.
     *
     * @return string HTML code.
     */
    private function getUpdatesList()
    {
        $updates = $this->umc->listUpdates();
        if (empty($updates) === true) {
            if ($updates === null) {
                header('HTTP/1.1 403 '.$this->umc->getLastError());
                return $this->umc->getLastError();
            }

            return '';
        }

        if (count($updates) > 0) {
            $next = $updates[0];
            $return = '<p><b>'.\__('Next update').': </b><span id="next-version" onclick="changelog()" style="cursor:pointer">';
            $return .= $next['version'].'</span>';
            $return .= ' - <a id="um-package-details-next" ';
            $return .= ' class="um-package-details" ';
            $return .= 'href="javascript: umShowUpdateDetails(\''.$next['version'].'\');">';
            $return .= \__('Show details').'</a></p>';

            $updates = array_reduce(
                $updates,
                function ($carry, $item) {
                    $carry[$item['version']] = $item;
                    return $carry;
                },
                []
            );

            // This var stores all update descriptions retrieved from UMS.
            $return .= '<script type="text/javascript">';
            $return .= 'var nextUpdateVersion = "'.$next['version'].'";';
            $return .= 'var lastUpdateVersion = "';
            $return .= end($updates)['version'].'";';
            $return .= 'var updates = '.json_encode($updates).';';
            $return .= '</script>';
            $return .= '</div>';

            array_shift($updates);

            if (count($updates) > 0) {
                $return .= '<a href="#" onclick="umToggleUpdateList();">';
                $return .= \__(
                    '%s update(s) available more',
                    '<span id="updates_left">'.count($updates).'</span>'
                );
                $return .= '</a>';
                $return .= '<div id="update-list">';
                foreach ($updates as $update) {
                    $return .= '<div class="update">';
                    $return .= '<div class="version">';
                    $return .= $update['version'];
                    $return .= '</div>';
                    $return .= '<a class="um-package-details" ';
                    $return .= 'href="javascript: umShowUpdateDetails(\''.$update['version'].'\');">';
                    $return .= \__('details').'</a></p>';
                    $return .= '</div>';
                }

                $return .= '</div>';
            }
        }

        return $return;
    }


    /**
     * Validates OUM uploaded by user.
     *
     * @return string JSON response.
     */
    private function processOUMUpload()
    {
        $return = [];

        if (isset($_FILES['upfile']) === true
            && $_FILES['upfile']['error'] === 0
        ) {
            $file_data = pathinfo($_FILES['upfile']['name']);
            $server_update = false;

            $extension = $file_data['extension'];
            $version = $file_data['filename'];

            if (preg_match('/pandorafms_server/', $file_data['filename']) > 0) {
                $tgz = pathinfo($file_data['filename']);
                if ($tgz !== null) {
                    $extension = $tgz['extension'].'.'.$extension;
                    $server_update = true;
                    $matches = [];
                    if (preg_match(
                        '/pandorafms_server(.*?)-.*NG\.(\d+\.{0,1}\d*?)_.*/',
                        $tgz['filename'],
                        $matches
                    ) > 0
                    ) {
                        $version = $matches[2];
                        if (empty($matches[1]) === true) {
                            $version .= ' OpenSource';
                        } else {
                            $version .= ' Enterprise';
                        }
                    } else {
                        $version = $tgz['filename'];
                    }
                }
            } else {
                if (preg_match(
                    '/package_(\d+\.{0,1}\d*)/',
                    $file_data['filename'],
                    $matches
                ) > 0
                ) {
                    $version = $matches[1];
                } else {
                    $version = $file_data['filename'];
                }
            }

            // The package extension should be .oum.
            if (strtolower($extension) === 'oum'
                || strtolower($extension) === 'tar.gz'
            ) {
                $path = $_FILES['upfile']['tmp_name'];

                // The package files will be saved in [user temp dir]/pandora_oum/package_name.
                if (is_dir(sys_get_temp_dir().'/pandora_oum/') !== true) {
                    mkdir(sys_get_temp_dir().'/pandora_oum/');
                }

                if (is_dir(sys_get_temp_dir().'/pandora_oum/') !== true) {
                    $return['status'] = 'error';
                    $return['message'] = __('Failed creating temporary directory.');
                    return json_encode($return);
                }

                $file_path = sys_get_temp_dir();
                $file_path .= '/pandora_oum/'.$_FILES['upfile']['name'];
                move_uploaded_file($_FILES['upfile']['tmp_name'], $file_path);

                if (is_file($file_path) === false) {
                    $return['status'] = 'error';
                    $return['message'] = __('Failed storing uploaded file.');
                    return json_encode($return);
                }

                $return['status'] = 'success';
                $return['packageId'] = hash('sha256', $file_path);
                $return['version'] = $version;
                $return['server_update'] = $server_update;

                if ($server_update === false) {
                    $return['files'] = Client::checkOUMContent($file_path);
                } else {
                    // Commented line for memory limit problems.
                    // $return['files'] = Client::checkTGZContent($file_path);
                    $return['files'] = null;
                }

                if (session_status() !== PHP_SESSION_ACTIVE) {
                    session_start();
                }

                $_SESSION['umc-uploaded-file-id'] = $return['packageId'];
                $_SESSION['umc-uploaded-file-version'] = $version;
                $_SESSION['umc-uploaded-file-path'] = $file_path;
                $_SESSION['umc-uploaded-type-server'] = $server_update;

                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_write_close();
                }

                return json_encode($return);
            } else {
                $return['status'] = 'error';
                $return['message'] = __(
                    'Invalid extension. The package needs to be in `%s` or `%s` format.',
                    '.oum',
                    '.tar.gz'
                );
                return json_encode($return);
            }
        }

        $return['status'] = 'error';
        $return['message'] = __('Failed uploading file.');

        return json_encode($return);
    }


    /**
     * Verifies uploaded file signature against given one.
     *
     * @return string JSON result.
     */
    private function validateUploadedOUM()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $file_path = ($_SESSION['umc-uploaded-file-path'] ?? null);
        $packageId = ($_SESSION['umc-uploaded-file-id'] ?? null);
        $signature = ($_REQUEST['signature'] ?? '');
        $version = $_SESSION['umc-uploaded-file-version'];
        $server_update = $_SESSION['umc-uploaded-type-server'];

        if ($packageId !== $_REQUEST['packageId']) {
            header('HTTP/1.1 401 Unauthorized');
            exit;
        }

        $valid = $this->umc->validateSignature(
            $file_path,
            $signature
        );

        if ($valid !== true) {
            $return['status'] = 'error';
            $return['message'] = __('Signatures does not match.');
        } else {
            $return['status'] = 'success';
            if ($this->umc->isPropagatingUpdates() === true) {
                $this->umc->saveSignature($signature, $file_path);
            }
        }

        return $return;
    }


    /**
     * Process installation of manually uploaded file.
     *
     * @return string JSON response.
     */
    private function installOUMUpdate()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $file_path = $_SESSION['umc-uploaded-file-path'];
        $packageId = $_SESSION['umc-uploaded-file-id'];
        $version = $_SESSION['umc-uploaded-file-version'];
        $server_update = $_SESSION['umc-uploaded-type-server'];

        if ($packageId !== $_REQUEST['packageId']) {
            header('HTTP/1.1 401 Unauthorized');
            exit;
        }

        unset($_SESSION['umc-uploaded-type-server']);
        unset($_SESSION['umc-uploaded-file-path']);
        unset($_SESSION['umc-uploaded-file-version']);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        if ($server_update === true) {
            // Server update.
            $result = $this->umc->updateServerPackage(
                [
                    'version'   => $version,
                    'file_path' => $file_path,
                ]
            );
        } else {
            // Console update.
            $result = $this->umc->updateNextVersion(
                [
                    'version'   => $version,
                    'file_path' => $file_path,
                ]
            );
        }

        $message = \__('Update %s successfully installed.', $version);
        if ($result !== true) {
            $message = \__(
                'Failed while updating: %s',
                $this->umc->getLastError()
            );
        }

        return [
            'result'  => $message,
            'error'   => $this->umc->getLastError(),
            'version' => $this->umc->getVersion(),
        ];
    }


    /**
     * Register console into UMS
     *
     * @return array Result.
     */
    private function registerConsole()
    {
        $email = $_REQUEST['email'];
        $rc = $this->umc->getRegistrationCode();
        if ($rc === null) {
            // Register.
            $rc = $this->umc->register($email);
        }

        return [
            'result' => $rc,
            'error'  => $this->umc->getLastError(),
        ];
    }


    /**
     * Unregister this console from UMS
     *
     * @return array Result.
     */
    private function unRegisterConsole()
    {
        $this->umc->unRegister();

        return [
            'result' => true,
            'error'  => $this->umc->getLastError(),
        ];
    }


}
