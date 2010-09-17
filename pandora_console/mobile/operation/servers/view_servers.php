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

require_once('../include/functions_servers.php');

class ViewServers {
	private $system;
	
	public function __construct() {
		global $system;
		
		$this->system = $system;
	}
	
	public function show() {
		$servers = get_server_info ();
		
		if ($servers === false) $servers = array();
		
		$table = null;
		$table->width = '100%';
		
		$table->align = array();
		$table->align[1] = 'center';
		$table->align[4] = 'center';
		
		$table->head = array();
		$table->head[0] = __('Server');
		$table->head[1] = '<span title="' . __('Type') . '" alt="' . __('Type') . '">' . __('T') . '</span>';
		$table->head[2] = '<span title="' . __('Started') . '" alt="' . __('Started') . '">' . __('S') . '</span>';
		$table->head[3] = '<span title="' . __('Updated') . '" alt="' . __('Updated') . '">' . __('U') . '</span>';
		$table->head[4] = '<span title="' . __('Status') . '" alt="' . __('Status') . '">' . __('S') . '</span>';
		
		$table->data = array(); //$this->system->debug($servers);
		foreach ($servers as $server) {
			$data = array();
			
			if ($server['status'] == 0) {
				$server_status = print_status_image(STATUS_SERVER_DOWN, '', true);
			}
			else {
				$server_status = print_status_image(STATUS_SERVER_OK, '', true);
			}
			$data[] = strip_tags($server["name"]);
			$data[] = str_replace('images/', '../images/', $server['img']);
			$data[] = human_time_comparation ($server["laststart"], 'tiny');
			$data[] = human_time_comparation ($server["keepalive"], 'tiny');
			$data[] = str_replace('.png', '_ball.png', str_replace('images/status_sets', 
				'../images/status_sets', $server_status));
//			$this->system->debug($server["name"]);
			
			$table->data[] = $data;
		}
		
		print_table($table);
	}
}

?>