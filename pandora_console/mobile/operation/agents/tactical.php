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

class Tactical {
	private $system;
	
	function __construct() {
		global $system;
		
		$this->system = $system;
	}
	
	function show() {
		$img = "../include/fgraph.php?tipo=progress&height=8&width=70&mode=0&percent=";
		
		$data = get_group_stats();
		
		$table = null;
		//$table->width = '100%';
		
		$table->size[0] = '10px';
		
		$table->align[0] = 'center';
		$table->align[3] = 'right';
		
		$table->colspan = array();
		$table->colspan[0][0] = 2;
		$table->colspan[0][1] = 2;
		
		$table->data[0][0] = "<h3 class='title_h3_server'>" . __('Status') . "</h3>" ;
		$table->data[1][0] = '<span title="' . __('Global health') . '" alt="' . __('Global health') . '">' . __('G') . '</span> ';
		$table->data[1][1] = print_image ($img.$data["global_health"], true);
		$table->data[2][0] = '<span title="' . __('Monitor health') . '" alt="' . __('Monitor health') . '">' . __('M') . '</span> ';
		$table->data[2][1] = print_image ($img.$data["monitor_health"], true);
		$table->data[3][0] = '<span title="' . __('Module sanity') . '" alt="' . __('Module sanity') . '">' . __('M') . '</span> ';
		$table->data[3][1] = print_image ($img.$data["module_sanity"], true);
		$table->data[4][0] = '<span title="' . __('Alert level') . '" alt="' . __('Alert level') . '">' . __('A') . '</span> ';
		$table->data[4][1] = print_image ($img.$data["alert_level"], true);
		$table->data[5][0] = $table->data[5][1] = '';
		$table->data[6][0] = $table->data[6][1] = '';
		$table->data[7][0] = $table->data[7][1] = '';
		$table->data[8][0] = $table->data[8][1] = '';
		
		$table->data[0][1] = "<h3 class='title_h3_server'>" . __('Monitor checks') . "</h3>";
		$table->data[1][2] = __('Monitor checks');
		$table->data[1][3] = $data["monitor_checks"];
		$table->data[2][2] = '<span style="color: #c00;">' . __('Monitors critical') . '</span>';
		$table->data[2][3] = '<span style="color: #c00;">' . $data["monitor_critical"] . '</span>';
		$table->data[3][2] = '<span style="color: #ffcc00;">' . __('Monitors warning') .'</span>';
		$table->data[3][3] = '<span style="color: #ffcc00;">' . $data["monitor_warning"] . '</span>';
		$table->data[4][2] = '<span style="color: #8ae234;">' . __('Monitors normal') . '</span>';
		$table->data[4][3] = '<span style="color: #8ae234;">' . $data["monitor_ok"] . '</span>';
		$table->data[5][2] = '<span style="color: #aaa;">' . __('Monitors unknown') . '</span>';
		$table->data[5][3] = '<span style="color: #aaa;">' . $data["monitor_unknown"] . '</span>';
		$table->data[6][2] = '<span style="color: #ef2929;">' . __('Monitors not init') . '</span>';
		$table->data[6][3] = '<span style="color: #ef2929;">' . $data["monitor_not_init"] . '</span>';
		$table->data[7][2] = '<span style="color: #000;">' . __('Alerts defined') . '</span>';
		$table->data[7][3] = '<span style="color: #000;">' . $data["monitor_alerts"] . '</span>';
		$table->data[8][2] = '<span style="color: #ff8800;">' . __('Alerts fired') . '</span>';
		$table->data[8][3] = '<span style="color: #ff8800;">' . $data["monitor_alerts_fired"] . '</span>';
		
		print_table($table);
		
		echo "<h3 class='title_h3_server'>" . __('Server performance') . "</h3>";
		
		$server_performance = get_server_performance();
		
		$table = null;
		//$table->width = '100%';
		
		$table->align = array();
		$table->align[1] = 'right';
		
		$table->data[0][0] = '<span style="color: #729fcf;">' . __('Local modules rate') . '</span>';
		$table->data[0][1] = '<span style="color: #729fcf;">' . format_numeric($server_performance ["local_modules_rate"]) . '</span>';
		$table->data[1][0] = '<span style="color: #729fcf;">' . __('Remote modules rate') . '</span>';
		$table->data[1][1] = '<span style="color: #729fcf;">' . format_numeric($server_performance ["remote_modules_rate"]) . '</span>';
		$table->data[2][0] = '<span style="color: #3465a4;">' . __('Local modules') . '</span>';
		$table->data[2][1] = '<span style="color: #3465a4;">' . format_numeric($server_performance ["total_local_modules"]) . '</span>';
		$table->data[3][0] = '<span style="color: #3465a4;">' . __('Remote modules') . '</span>';
		$table->data[3][1] = '<span style="color: #3465a4;">' . format_numeric($server_performance ["total_remote_modules"]) . '</span>';
		$table->data[4][0] = '<span style="color: #000;">' . __('Total running modules') . '</span>';
		$table->data[4][1] = '<span style="color: #000;">' . format_numeric($server_performance ["total_modules"]) . '</span>';
		
		print_table($table);
		
		echo "<h3 class='title_h3_server'>" . __('Summary') . "</h3>";
		
		$table = null;
		//$table->width = '100%';
		$table->align[1] = 'right';
		$table->data[0][0] = '<span style="color: #000;">' . __('Total agents') . '</span>';
		$table->data[0][1] = '<span style="color: #000;">' . $data["total_agents"] . '</span>';
		$table->data[1][0] = '<span style="color: #ef2929;">' . __('Uninitialized modules') . '</span>';
		$table->data[1][1] = '<span style="color: #ef2929;">' . $data["server_sanity"] . '</span>';
		$table->data[2][0] = '<span style="color: #aaa;">' . __('Agents unknown') . '</span>';
		$table->data[2][1] = '<span style="color: #aaa;">' . $data["agents_unknown"] . '</span>';
		
		print_table($table);
	}
}
?>