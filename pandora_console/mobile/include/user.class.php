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

//Singleton
class User {
	private static $instance;
	
	private $user;
	private $logged = false;
	private $errorLogin = false;
	private $loginTime = false;
	private $logout_action = false;
	private $needDoubleAuth = false;
	private $errorDoubleAuth = false;
	
	public static function getInstance () {
		if (!(self::$instance instanceof self)) {
			//Check if in the session
			$system = System::getInstance();
			$user = $system->getSession('user', null);
			
			if (!empty($user)) {
				self::$instance = $user;
			}
			else {
				self::$instance = new self;
			}
		}
		
		return self::$instance;
	}
	
	public function saveLogin () {
		
		if ($this->logged) {
			$system = System::getInstance();
						
			//hack to compatibility with pandora
			global $config;
			$config['id_user'] = $this->user;
			
			$system->setSessionBase('id_usuario', $this->user);
			$system->setSession('user', $this);
		}
	}
	
	public function isLogged () {
		$system = System::getInstance();
		
		$autologin = $system->getRequest('autologin', false);
		if ($autologin) {
			$user = $system->getRequest('user', null);
			$password = $system->getRequest('password', null);
			
			$this->login($user, $password);
		}
		
		return $this->logged;
	}

	public function login ($user = null, $password = null) {
		$system = System::getInstance();
		
		if (($user == null) && ($password == null)) {
			$user = $system->getRequest('user', null);
			$user = $system->safeInput($user);
			$password = $system->getRequest('password', null);
		}
		
		if (!empty($user) && !empty($password)) {
			$user_in_db = process_user_login($user, $password);
			if ($user_in_db !== false) {
				
				$this->logged = true;
				$this->user = $user_in_db;
				$this->loginTime = time();
				$this->errorLogin = false;

				// The user login was successful, but the second step is not completed
				if ($this->isDobleAuthRequired()) {
					$this->needDoubleAuth = true;
				}
			}
			else {
				
				$this->logged = false;
				$this->loginTime = false;
				$this->errorLogin = true;
				$this->needDoubleAuth = false;
				$this->errorDoubleAuth = false;
			}
		}
		
		$this->saveLogin();
		
		return $this->logged;
	}

	public function getLoginTime () {
		return $this->loginTime;
	}
	
	public function isWaitingDoubleAuth () {
		return $this->needDoubleAuth;
	}
	
	public function isDobleAuthRequired ($user = false) {
		if (empty($user) && !empty($this->user))
			$user = $this->user;

		if (!empty($user))
			return (bool) db_get_value('id', 'tuser_double_auth', 'id_user', $user);
		else
			return false;
	}

	public function validateDoubleAuthCode ($user = null, $code = null) {

		if (!$this->needDoubleAuth) {
			return true;
		}

		$system = System::getInstance();
		require_once ($system->getConfig('homedir').'/include/auth/GAuth/Auth.php');

		$result = false;

		if (empty($user)) {
			$user = $this->user;
		}
		if (empty($code)) {
			$code = $system->getRequest('auth_code', null);
		}

		if (!empty($user) && !empty($code)) {
			$secret = db_get_value('secret', 'tuser_double_auth', 'id_user', $user);

			if ($secret === false) {
				$result = false;
				$this->errorDoubleAuth = array(
						'title_text' => __('Double authentication failed'),
						'content_text' => __('Secret code not found') .". "
							.__('Please contact the administrator to reset your double authentication')
					);
			}
			else if (!empty($secret)) {
				try {
					$gAuth = new \GAuth\Auth($secret);
					$result = $gAuth->validateCode($code);

					// Double auth success
					if ($result) {
						$this->needDoubleAuth = false;
						$this->saveLogin();
					}
					else {
						$result = false;
						$this->errorDoubleAuth = array(
								'title_text' => __('Double authentication failed'),
								'content_text' => __('Invalid code')
							);
					}
				} catch (Exception $e) {
					$result = false;
					$this->errorDoubleAuth = array(
							'title_text' => __('Double authentication failed'),
							'content_text' => __('There was an error checking the code')
						);
				}
			}
		}
		
		return $result;
	}
	
	public function logout () {
		$this->user = null;
		$this->logged = false;
		$this->loginTime = false;
		$this->errorLogin = false;
		$this->logout_action = true;
		$this->needDoubleAuth = false;
		$this->errorDoubleAuth = false;

		$system = System::getInstance();
		$system->setSession('user', null);
		$system->sessionDestroy();
	}
	
	public function showLoginPage () {
		global $pandora_version;
		
		$ui = Ui::getInstance();
		$system = System::getInstance();
		
		$ui->createPage();
		if ($this->errorLogin) {
			
			$options['type'] = 'onStart';
			$options['title_text'] = __('Login Failed');
			$options['content_text'] = __('User not found in database or incorrect password.');
			$ui->addDialog($options);
			
		}
		if ($this->logout_action) {
			
			$options['dialog_id'] = 'logout_dialog';
			$options['type'] = 'onStart';
			$options['title_text'] = __('Login out');
			$options['content_text'] = __('Your session is over. Please close your browser window to close this Pandora session.');
			$ui->addDialog($options);
			
		}
		$ui->createHeader();
		$ui->showFooter(false);
		$ui->beginContent();
		
		if (!$system->getConfig('metaconsole'))
			$logo_image = html_print_image ("mobile/images/pandora_mobile_console.png",
						true, array ("alt" => "logo", "border" => 0));
		else
			$logo_image = html_print_image ("mobile/images/metaconsole_mobile.png",
						true, array ("alt" => "logo", "border" => 0),false, false, false, true);
		
		$ui->contentAddHtml('<div style="text-align: center;" class="login_logo">' .
			$logo_image . '</div>');
		$ui->contentAddHtml('<div id="login_container">');
		$ui->beginForm();
		$ui->formAddHtml(html_print_input_hidden('action', 'login', true));
		$options = array(
			'name' => 'user',
			'value' => $this->user,
			'placeholder' => __('user'),
			'label' => __('User')
			);
		$ui->formAddInputText($options);
		$options = array(
			'name' => 'password',
			'value' => '',
			'placeholder' => __('password'),
			'label' => __('Password')
			);
		$ui->formAddInputPassword($options);
		$options = array(
			'value' => __('Login'),
			'icon' => 'arrow-r',
			'icon_pos' => 'right',
			'name' => 'login_btn'
			);
		$ui->formAddSubmitButton($options);
		$ui->endForm();
		$ui->contentAddHtml('</div>');
		$ui->endContent();
		$ui->showPage();
		
		$this->errorLogin = false;
		$this->logout_action = false;
	}

	public function showDoubleAuthPage () {
		global $pandora_version;
		
		$ui = Ui::getInstance();
		
		$ui->createPage();
		if (!empty($this->errorDoubleAuth)) {
			$options['type'] = 'onStart';
			$options['title_text'] = $this->errorDoubleAuth['title_text'];
			$options['content_text'] = $this->errorDoubleAuth['content_text'] . "<br>";
			$ui->addDialog($options);
		}
		$left_button = $ui->createHeaderButton(
				array('icon' => 'back',
					'pos' => 'left',
					'text' => __('Logout'),
					'href' => 'index.php?action=logout'));
		$ui->createHeader('', $left_button);
		$ui->showFooter(false);
		$ui->beginContent();
			$ui->contentAddHtml('<div style="text-align: center;" class="login_logo">' .
				html_print_image ("mobile/images/pandora_mobile_console.png",
					true, array ("alt" => "logo", "border" => 0)) .
					'</div>');
			$ui->contentAddHtml('<div id="login_container">');
			$ui->beginForm();
			$ui->formAddHtml(html_print_input_hidden('action', 'double_auth', true));
			$options = array(
				'name' => 'auth_code',
				'value' => '',
				'placeholder' => __('Authenticator code'),
				'label' => __('Authenticator code')
				);
			$ui->formAddInputPassword($options);
			$options = array(
				'value' => __('Check code'),
				'icon' => 'arrow-r',
				'icon_pos' => 'right',
				'name' => 'auth_code_btn'
				);
			$ui->formAddSubmitButton($options);
			$ui->endForm();
			$ui->contentAddHtml('</div>');
		$ui->endContent();
		$ui->showPage();
		
		$this->errorDoubleAuth = false;
	}

	public function getIdUser () {
		return $this->user; //Oldies methods
	}
	
	public function isInGroup ($access = "AR", $id_group = 0, $name_group = false) {
		return (bool)check_acl($this->user, $id_group, $access);
	}
	
	public function getIdGroups ($access = "AR", $all = false) {
		return array_keys(users_get_groups($this->user, $access, $all));
	}
}
?>
