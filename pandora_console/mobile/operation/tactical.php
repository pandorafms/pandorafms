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

include("../include/functions_reporting.php");
include("../include/functions_servers.php");

class Tactical {
	private $correct_acl = false;
	
	function __construct() {
		$system = System::getInstance();
		
		if ($system->checkACL()) {
			$this->correct_acl = true;
		}
		else {
			$this->correct_acl = false;
		}
	}
	
	public function show() {
		if (!$this->correct_acl) {
			$this->show_fail_acl();
		}
		else {
			$this->show_tactical();
		}
	}
	
	private function show_fail_acl() {
		$error['title_text'] = __('You don\'t have access to this page');
		$error['content_text'] = __('Access to this page is restricted to authorized users only, please contact system administrator if you need assistance. <br><br>Please know that all attempts to access this page are recorded in security logs of Pandora System Database');
		$home = new Home();
		$home->show($error);
	}
	
	private function show_tactical() {
		$ui = Ui::getInstance();
		
		$ui->createPage();
		$ui->createDefaultHeader(__("PandoraFMS: Tactical"));
		$ui->showFooter(false);
		$ui->beginContent();
			$ui->contentBeginGrid('responsive');
				$data = reporting_get_group_stats();
				$data['mobile'] = true;
				
				$formatted_data = reporting_get_stats_indicators($data, 200, 10, false);
				
				$overview = '<table>
						<tr>
							<td>' . $formatted_data['server_health']['title'] . '</td>
							<td>' . $formatted_data['server_health']['graph'] . '</td>
						</tr>
						<tr>
							<td>' . $formatted_data['monitor_health']['title'] . '</td>
							<td>' . $formatted_data['monitor_health']['graph'] . '</td>
						</tr>
						<tr>
							<td>' . $formatted_data['module_sanity']['title'] . '</td>
							<td>' . $formatted_data['module_sanity']['graph'] . '</td>
						</tr>
						<tr>
							<td>' . $formatted_data['alert_level']['title'] . '</td>
							<td>' . $formatted_data['alert_level']['graph'] . '</td>
						</tr>
					</table>';
				
				$ui->contentGridAddCell($overview);
				
				$formatted_data = reporting_get_stats_alerts($data);
				ob_start();
				$links = array();
				$links['monitor_critical'] = "index.php?page=modules&status=1";
				$links['monitor_warning'] = "index.php?page=modules&status=2";
				$links['monitor_ok'] = "index.php?page=modules&status=0";
				$links['monitor_unknown'] = "index.php?page=modules&status=3";
				$links['monitor_not_init'] = "index.php?page=modules&status=5";
				$formatted_data .= reporting_get_stats_modules_status($data, 250, 150, $links) . "<br />\n" .
					reporting_get_stats_agents_monitors($data);
				$graph_js = ob_get_clean();
				$formatted_data = $graph_js . $formatted_data;
				$ui->contentGridAddCell($formatted_data, 'tactical');
			$ui->contentEndGrid();
			
			$this->getLastActivity();
			$ui->contentBeginCollapsible(__('Last activity'));
				
				$table = new Table();
				$table->importFromHash($this->getLastActivity());
				$ui->contentCollapsibleAddItem($table->getHTML());
			$ui->contentEndCollapsible();
		$ui->endContent();
		$ui->showPage();
	}
	
	private function getLastActivity() {
		global $config;
		
		switch ($config["dbtype"]) {
			case "mysql":
				$sql = sprintf ("SELECT id_usuario,accion,fecha,ip_origen,descripcion,utimestamp
					FROM tsesion
					WHERE (`utimestamp` > UNIX_TIMESTAMP(NOW()) - " . SECONDS_1WEEK . ") 
						AND `id_usuario` = '%s' ORDER BY `utimestamp` DESC LIMIT 10", $config["id_user"]);
				break;
			case "postgresql":
				$sql = sprintf ("SELECT \"id_usuario\", accion, fecha, \"ip_origen\", descripcion, utimestamp
					FROM tsesion
					WHERE (\"utimestamp\" > ceil(date_part('epoch', CURRENT_TIMESTAMP)) - " . SECONDS_1WEEK . ") 
						AND \"id_usuario\" = '%s' ORDER BY \"utimestamp\" DESC LIMIT 10", $config["id_user"]);
				break;
			case "oracle":
				$sql = sprintf ("SELECT id_usuario, accion, fecha, ip_origen, descripcion, utimestamp
					FROM tsesion
					WHERE ((utimestamp > ceil((sysdate - to_date('19700101000000','YYYYMMDDHH24MISS')) * (" . SECONDS_1DAY . ")) - " . SECONDS_1WEEK . ") 
						AND id_usuario = '%s') AND rownum <= 10 ORDER BY utimestamp DESC", $config["id_user"]);
				break;
		}
		
		$sessions = db_get_all_rows_sql ($sql);
		
		if ($sessions === false)
			$sessions = array (); 
		
		$return = array();
		foreach ($sessions as $session) {
			$data = array();
			
			switch ($config["dbtype"]) {
				case "mysql":
				case "oracle":
					$session_id_usuario = $session['id_usuario'];
					$session_ip_origen = $session['ip_origen'];
					break;
				case "postgresql":
					$session_id_usuario = $session['id_usuario'];
					$session_ip_origen = $session['ip_origen'];
					break;
			}
			
			$data[__("User")] = '<strong>' . $session_id_usuario . '</strong>';
			$data[__("Action")] = $session['accion'];
			$data[__("Date")] =  human_time_comparation($session['utimestamp']);
			$data[__("Source IP")] = $session_ip_origen;
			$data[__("Description")] = io_safe_output ($session['descripcion']);
			
			$return[] = $data;
		}
		
		return $return;
	}
}
?>
