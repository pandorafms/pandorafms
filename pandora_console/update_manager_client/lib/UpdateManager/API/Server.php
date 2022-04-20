<?php
/**
 * UpdateManager API Server.
 *
 * @category   Class
 * @package    Update Manager
 * @subpackage API Server
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
namespace UpdateManager\API;

use UpdateManager\RepoMC;

/**
 * Microserver to populate updates to nodes.
 */
class Server
{

    /**
     * Repository  path.
     *
     * @var string
     */
    private $repoPath;

    /**
     * MC License to use as verification.
     *
     * @var string
     */
    private $licenseToken;

    /**
     * Disk repository.
     *
     * @var RepoMC
     */
    private $repository;

    /**
     * Registration code.
     *
     * @var string
     */
    private $registrationCode;


    /**
     * Initializes a micro server object.
     *
     * @param array $settings Values.
     */
    public function __construct(array $settings)
    {
        $this->repoPath = '';
        $this->licenseToken = '';

        if (isset($settings['repo_path']) === true) {
            $this->repoPath = $settings['repo_path'];
        }

        if (isset($settings['license']) === true) {
            $this->licenseToken = $settings['license'];
        }

        if (isset($settings['registration_code']) === true) {
            $this->registrationCode = $settings['registration_code'];
        }
    }


    /**
     * Handle requests and reponse as UMS.
     *
     * @return void
     * @throws \Exception On error.
     */
    public function run()
    {
        // Requests are handled via POST to hide the user.
        self::assertPost();

        $action = self::input('action');

        try {
            if ($action === 'get_server_package'
                || $action === 'get_server_package_signature'
            ) {
                $this->repository = new RepoMC(
                    $this->repoPath,
                    'tar.gz'
                );
            } else {
                $this->repository = new RepoMC(
                    $this->repoPath,
                    'oum'
                );
            }
        } catch (\Exception $e) {
            self::error(500, $e->getMessage());
        }

        try {
            $this->validateRequest();

            switch ($action) {
                case 'newest_package':
                    echo json_encode(
                        $this->repository->newest_package(
                            self::input('current_package')
                        )
                    );
                break;

                case 'newer_packages':
                    echo json_encode(
                        $this->repository->newer_packages(
                            self::input('current_package')
                        )
                    );
                break;

                case 'get_package':
                    $this->repository->send_package(
                        self::input('package')
                    );
                break;

                case 'get_server_package':
                    $this->repository->send_server_package(
                        self::input('version')
                    );
                break;

                case 'new_register':
                    echo json_encode(
                        [
                            'success'     => 1,
                            'pandora_uid' => $this->registrationCode,
                        ]
                    );
                break;

                // Download a package signature.
                case 'get_package_signature':
                    echo $this->repository->send_package_signature(
                        self::input('package')
                    );
                break;

                // Download a server package signature.
                case 'get_server_package_signature':
                    echo $this->repository->send_server_package_signature(
                        self::input('version')
                    );
                break;

                default:
                throw new \Exception('invalid action');
            }
        } catch (\Exception $e) {
            if ($e->getMessage() === 'file not found in repository') {
                self::error(404, $e->getMessage());
            } else {
                self::error(500, 'Error: '.$e->getMessage());
            }
        }
    }


    /**
     * Exit if not a POST request.
     *
     * @return void
     * @throws \Exception If not running using POST.
     */
    private static function assertPost()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            self::error(500, 'Error: only POST requests are accepted.');
            throw new \Exception('use POST');
        }
    }


    /**
     * Validate request.
     *
     * @return void
     * @throws \Exception On error.
     */
    private function validateRequest()
    {
        $reported_license = self::input('license', null);

        if ($reported_license !== $this->licenseToken) {
            throw new \Exception('Invalid license', 1);
        }
    }


    /**
     * Return headers with desired error.
     *
     * @param integer $err_code Error code.
     * @param string  $msg      Message.
     *
     * @return void
     */
    private static function error(int $err_code, string $msg='')
    {
        header('HTTP/1.1 '.$err_code.' '.$msg);
        if (empty($msg) !== false) {
            echo $msg."\r\n";
        }
    }


    /**
     * Retrieve fields from request.
     *
     * @param string $name    Variable name.
     * @param mixed  $default Default value.
     *
     * @return mixed Variable value.
     */
    private static function input(string $name, $default=null)
    {
        if (isset($_REQUEST[$name]) === true) {
            return $_REQUEST[$name];
        }

        return $default;
    }


}
