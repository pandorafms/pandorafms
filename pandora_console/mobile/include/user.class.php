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
		$autologin = $this->system->getRequest('autologin', false);
		if ($autologin) {
			$user = $this->system->getRequest('user', null);
			$password = $this->system->getRequest('password', null);
			if ($this->checkLogin($user, $password)) {
				$this->hackinjectConfig();
			}
		}
		
		return $this->logged;
	}
	
	public function checkLogin($user = null, $password = null) {
		if (($user == null) && ($password == null)) {
			$user = $this->system->getRequest('user', null);
			$password = $this->system->getRequest('password', null);
		}
		
		if (process_user_login($user, $password) !== false) {
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
	
	public function showLogin($text = '') {
		global $pandora_version;
		
		echo "<form action='index.php' method='post'>";
		html_print_input_hidden('action', 'login');
		?>
		<div id="center_div">
			<div id="table_version_negative_position_div">
				<table cellspacing="0" style="margin: 10px;" id="table_version">
					<tr>
						<td style="height: 120px; width: 200px; background: #fff; width: 200px; height: 120px;" colspan="2" rowspan="2">
							<table id="form_table" cellspacing="0" style="border: 2px solid #6DC62D; width: 100%; height: 100%; background: url('../images/pandora_logo.png') bottom left no-repeat #fff;">
								<tr>
									<td style="color: #036A3A; height: 20px;" colspan="2" valign="top" align="left"><?php echo $pandora_version;?> <?php echo $text;?></td>
									<td style="width: 80px; height: 80px;" valign="bottom" align="left" rowspan="4">
										<?php
										html_print_submit_button('', 'login', false, 'class="login_button" alt="' . __('Login') . '" title="' . __('Login') . '"');
										?>
									</td>
								</tr>
								<tr>
									<td style="width: 20px; height: 25px;">&nbsp;</td>
									<td valign="top" align="left"><?php html_print_input_text('user', $this->user, __('User'), 10, 20);?></td>
								</tr>
								<tr>
									<td style="width: 20px; height: 15px;">&nbsp;</td>
									<td valign="top" align="left"><?php html_print_input_password('password', '', __('Password'), 10, 20);?></td>
								</tr>
								<tr>
									<td style="height: 20px;">&nbsp;</td>
									<td style="height: 20px;">&nbsp;</td>
								</tr>
								<tr>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
								</tr>
							</table>
						</td>
						<td style="height: 15px; width: 15px; background: #fff;">&nbsp;</td>
					</tr>
					<tr>
						<td style="width: 15px; height: 90px; background: #036A3A;">&nbsp;</td>
					</tr>
					<tr>
						<td style="height: 15px; width: 15px; background: #fff;">&nbsp;</td>
						<td style="width: 200px; height: 15px; background: #036A3A; color: #fff; font-size: 9px; text-align: right;" colspan="2">
							<?php echo '<b style="font-size: 9px;">' . __('Your IP').':</b>' . $this->system->getConfig("remote_addr") . '&nbsp;';?>
						</td>
					</tr>
					<tr>
						<td style="background: white; height: 0px;">&nbsp;</td>
						<td style="background: white; height: 0px;">&nbsp;</td>
						<td style="background: white; height: 0px;">&nbsp;</td>
					</tr>
				</table>
			</div>
		</div>
		<?php
		echo "</form>";
		
		/*
		?>
		<div id="center_div">
			<div id="negative_position_div">
				<div id="style_div">
					<div id="shadow">
						<p>
						<?php
						echo '<b>' . __('Your IP').':</b>' . $this->system->getConfig("remote_addr");
						?>
						</p>
					</div>
					<?php
					echo "<form id='login_box' method='post' style=''>";
					echo "<div id='version'>" . $pandora_version . "</div>";
					html_print_input_hidden('action', 'login');
					html_print_input_text('user', $this->user, __('User'), 10, 20);
					html_print_input_password('password', '', __('Password'), 10, 20);
					html_print_submit_button(__(''), 'login', false, 'onclick="javascript: click();" class="login_button" alt="' . __('Login') . '" title="' . __('Login') . '"');
					echo "</form>";
					?>
				</div>
			</div>
		</div>
		<?php
		*/
	}
	
	public function getIdUser() {
		return $this->user;
	}
}
?>
