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
	
	public function show($error = null) {
		global $config;
		
		require_once ($config["homedir"] . '/include/functions_graph.php');
		
		$ui = Ui::getInstance();
		
		$ui->createPage();
			if (isset($error)) {
				$options['type'] = 'onStart';
				$options['title_text'] = $error['title_text'];
				$options['content_text'] = $error['content_text'];
				$ui->addDialog($options);
			}
		$ui->createDefaultHeader(__("PandoraFMS: Home"));
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
			$options = array('icon' => 'gear',
					'pos' => 'right',
					'text' => __('Tactical'),
					'href' => 'index.php?page=tactical');
			$ui->contentAddHtml($ui->createButton($options));
			$options = array('icon' => 'info',
					'pos' => 'right',
					'text' => __('Events'),
					'href' => 'index.php?page=events');
			$ui->contentAddHtml($ui->createButton($options));
			$options = array('icon' => 'arrow-u',
					'pos' => 'right',
					'text' => __('Groups'),
					'href' => 'index.php?page=groups');
			$ui->contentAddHtml($ui->createButton($options));
			$options = array('icon' => 'alert',
					'pos' => 'right',
					'text' => __('Alerts'),
					'href' => 'index.php?page=alerts');
			$ui->contentAddHtml($ui->createButton($options));
			$options = array('icon' => 'grid',
					'pos' => 'right',
					'text' => __('Agents'),
					'href' => 'index.php?page=agents');
			$ui->contentAddHtml($ui->createButton($options));
			$options = array('icon' => 'check',
					'pos' => 'right',
					'text' => __('Modules'),
					'href' => 'index.php?page=modules');
			$ui->contentAddHtml($ui->createButton($options));
		$ui->endContent();
		$ui->showPage();
		return;
	}
}
?>