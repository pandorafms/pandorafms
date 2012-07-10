<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

if (!isset($config)) {
	require_once('../include/config.php');
}
require_once('db.class.php');

class System {
	private $session;
	private $config;
	private $db;
	
	function __construct() {
		$this->loadConfig();
		$this->db = new DB($this, $this->getConfig('db_engine', 'mysql'));
		
		session_start();
		$this->session = $_SESSION;
		session_write_close();
	}
	
	private function loadConfig() {
		global $config;
		
		$this->config = &$config;
	}
	
	public function getRequest($name, $default = null) {
		$return = $default;
		
		if (isset($_POST[$name])) {
			$return = $_POST[$name];
		}
		else {
			if (isset($_GET[$name])) {
				$return = $_GET[$name];
			}
		}
		
		return $return;
	}
	
	public function getConfig($name, $default = null) {
		if (!isset($this->config[$name])) {
			return $default;
		}
		else {
			return $this->config[$name];
		}
	}
	
	public function setSessionBase($name, $value) {
		session_start();
		$_SESSION[$name] = $value;
		session_write_close();
	}
	
	public function setSession($name, $value) {
		$this->session[$name] = $value;
		
		session_start();
		$_SESSION = $this->session;
		session_write_close();
	}
	
	public function getSession($name, $default = null) {
		if (!isset($this->session[$name])) {
			return $default;
		}
		else {
			return $this->session[$name];
		}
	}
	
	public function debug($var) {
		echo "<pre>";
		var_dump($var);
		echo "</pre>";
	}
	
	public function sessionDestroy() {
		session_start();
		session_destroy();
	}
	
	public function getPageSize() {
		return 10;
	}
}
?>