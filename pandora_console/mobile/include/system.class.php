<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// Singleton
class System
{

    private static $instance;

    private $session;

    private $config;


    function __construct()
    {
        $this->loadConfig();
        $session_id = session_id();
        DB::getInstance($this->getConfig('db_engine', 'mysql'));
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->session = $_SESSION;
        session_write_close();

        include_once $this->getConfig('homedir').'/include/functions.php';
        include_once $this->getConfig('homedir').'/include/functions_io.php';
    }


    public static function getInstance()
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self;
        }

        return self::$instance;
    }


    private function loadConfig()
    {
        global $config;

        $config['mobile'] = true;

        $this->config = &$config;
    }


    public function getRequest($name, $default=null)
    {
        return get_parameter($name, $default);
    }


    public function safeOutput($value)
    {
        return io_safe_output($value);
    }


    public function safeInput($value)
    {
        return io_safe_input($value);
    }


    public function getConfig($name, $default=null)
    {
        if (!isset($this->config[$name])) {
            return $default;
        } else {
            return $this->config[$name];
        }
    }


    public function setSessionBase($name, $value)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION[$name] = $value;
        session_write_close();
    }


    public function setSession($name, $value)
    {
        $this->session[$name] = $value;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = $this->session;
        session_write_close();
    }


    public function getSession($name, $default=null)
    {
        if (!isset($this->session[$name])) {
            return $default;
        } else {
            return $this->session[$name];
        }
    }


    public function sessionDestroy()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_destroy();
    }


    public function getPageSize()
    {
        return 10;
    }


    public function checkACL($access='AR', $group_id=0)
    {
        if (check_acl($this->getConfig('id_user'), $group_id, $access)) {
            return true;
        } else {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access to Mobile Page'
            );

            return false;
        }
    }


    public function checkEnterprise($page='')
    {
        if ((int) $this->getConfig('enterprise_installed', false) === 1) {
            return true;
        } else {
            if (empty($this->getRequest('page', false)) === false && $page === '') {
                $page = $this->getRequest('page', false);
            }

            db_pandora_audit(
                AUDIT_LOG_ENTERPRISE_VIOLATION,
                'Trying to access to Mobile Page: '.$page
            );

            return false;
        }
    }


    public static function getDefaultACLFailText()
    {
        return __('Access to this page is restricted to authorized users only, please contact your system administrator if you should need help.').'<br><br>'.__('Please remember that any attempts to access this page will be recorded on the %s System Database.', get_product_name());
    }


    public static function getDefaultLicenseFailText()
    {
        return __('Invalid license, please contact your system administrator if you should need help.').'<br><br>'.__('Please remember that any attempts to access this page will be recorded on the %s System Database.', get_product_name());
    }


}
