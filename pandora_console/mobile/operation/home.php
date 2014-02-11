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

class Home {
	private $global_search = '';
	
	function __construct() {
		$this->global_search = '';
	}
	
	public function show() {
		global $config;
		
		require_once ($config["homedir"] . '/include/functions_graph.php');
		
		$ui = Ui::getInstance();
		$system = System::getInstance();	
			
		$ui->createPage();
		if ($system->getRequest('hide_logout', 0)) {
			$left_button = null;
		} else {
			$left_button = $ui->createHeaderButton(
				array('icon' => 'back',
					'pos' => 'left',
					'text' => __('Logout'),
					'href' => 'index.php?action=logout'));
		}

		$user_logged = '';
		if (isset($config['id_user'])) {
			$user_logged = '<span id="user_logged">' . $config['id_user'] . '</span>';
		}
		
		$ui->createHeader(__("Home") . $user_logged, $left_button);
		$ui->showFooter(false);
		$ui->beginContent();
			$ui->beginForm("index.php?page=agents");
			$options = array(
				'name' => 'free_search',
				'value' => $this->global_search,
				'placeholder' => __('Global search')
				);
			$ui->formAddInputSearch($options);
			$ui->endForm();
			
			//List of buttons
			$options = array('icon' => 'tactical_view',
					'pos' => 'right',
					'text' => __('Tactical view'),
					'href' => 'index.php?page=tactical');
			$ui->contentAddHtml($ui->createButton($options));
			$options = array('icon' => 'events',
					'pos' => 'right',
					'text' => __('Events'),
					'href' => 'index.php?page=events');
			$ui->contentAddHtml($ui->createButton($options));
			$options = array('icon' => 'groups',
					'pos' => 'right',
					'text' => __('Groups'),
					'href' => 'index.php?page=groups');
			$ui->contentAddHtml($ui->createButton($options));
			$options = array('icon' => 'alerts',
					'pos' => 'right',
					'text' => __('Alerts'),
					'href' => 'index.php?page=alerts');
			$ui->contentAddHtml($ui->createButton($options));
			$options = array('icon' => 'agents',
					'pos' => 'right',
					'text' => __('Agents'),
					'href' => 'index.php?page=agents');
			$ui->contentAddHtml($ui->createButton($options));
			$options = array('icon' => 'modules',
					'pos' => 'right',
					'text' => __('Modules'),
					'href' => 'index.php?page=modules');
			$ui->contentAddHtml($ui->createButton($options));
			$options = array('icon' => 'network_maps',
					'pos' => 'right',
					'text' => __('Networkmaps'),
					'href' => 'index.php?page=networkmaps');
			$ui->contentAddHtml($ui->createButton($options));
			$options = array('icon' => 'visual_console',
					'pos' => 'right',
					'text' => __('Visual consoles'),
					'href' => 'index.php?page=visualmaps');
			$ui->contentAddHtml($ui->createButton($options));
		$ui->endContent();
		$ui->showPage();
		return;
	}
}
?>
