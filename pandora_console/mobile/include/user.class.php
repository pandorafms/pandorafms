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

require_once("system.class.php");

class User {
	private $user;
	private $logged;
	private $system;
	
	public function __construct($user = null, $password = null) {
		global $system;
		
		$this->user = $user;
		$this->system = &$system;
		
		//$this->system->debug($this->system);
		
		if (process_user_login($this->user, $password)) {
			$this->logged = true;
		}
		else {
			$this->logged = false;
		}
	}
	
	public function hackinjectConfig() {
		if ($this->logged) {
			//hack to compatibility with pandora
			global $config;
			$config['id_user'] = $this->user;
			$this->system->setSessionBase('id_usuario', $this->user);		
		}
	}
	
	public function isLogged() {
		return $this->logged;
	}
	
	public function checkLogin($user = null, $password = null) {
		if (($user == null) && ($password == null)) {
			$user = $this->system->getRequest('user', null);
			$password = $this->system->getRequest('password', null);
		}
		
		if (process_user_login($user, $password)) {
			$this->logged = true;
			$this->user = $user;
		}
		else {
			$this->logged = false;
		}
		
		return true;
	}
	
	public function logout() {
		$this->user = null;
		$this->logged = false;
	}
	
	public function login() {
		echo "<form method='post'>";
		print_input_hidden('action', 'login');
		print_input_text('user', $this->user, __('User'), 10, 20);
		print_input_password('password', '', __('Password'), 10, 20);
		print_submit_button(__('Login'), 'login', false, 'class="sub next"');
		echo "</form>";		
	}
	
	public function getIdUser() {
		return $this->user;
	}
}
?>