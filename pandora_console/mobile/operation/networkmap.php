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

require_once ('../include/functions_networkmap.php');

class Networkmap {
	private $correct_acl = false;
	private $acl = "AR";
	
	private $id = 0;
	private $network_map = null;
	
	function __construct() {
		$system = System::getInstance();
		
		if ($system->checkACL($this->acl)) {
			$this->correct_acl = true;
		}
		else {
			$this->correct_acl = false;
		}
	}
	
	public function ajax($parameter2 = false) {
		$system = System::getInstance();
		
		if (!$this->correct_acl) {
			return;
		}
		else {
			switch ($parameter2) {
				case 'xxx':
					//$this->getFilters();
					//$page = $system->getRequest('page', 0);
					break;
			}
		}
	}
	
	private function getFilters() {
		$system = System::getInstance();
		
		$this->id = (int)$system->getRequest('id', 0);
	}
	
	public function show() {
		if (!$this->correct_acl) {
			$this->show_fail_acl();
		}
		else {
			$this->getFilters();
			
			$this->networkmap = db_get_row('tnetwork_map',
				'id_networkmap', $this->id);
			
			$this->show_networkmap();
		}
	}
	
	private function show_fail_acl() {
		$error['title_text'] = __('You don\'t have access to this page');
		$error['content_text'] = __('Access to this page is restricted to authorized users only, please contact system administrator if you need assistance. <br><br>Please know that all attempts to access this page are recorded in security logs of Pandora System Database');
		$home = new Home();
		$home->show($error);
	}
	
	private function show_networkmap() {
		$ui = Ui::getInstance();
		$system = System::getInstance();
		
		$ui->createPage();
		$ui->createDefaultHeader(sprintf(__("PandoraFMS: Networkmap %s"),
			$this->network_map['name']));
		$ui->showFooter(false);
		$ui->beginContent();
			
			//Hack for mobile
			global $hack_networkmap_mobile;
			$hack_networkmap_mobile = true;
			
			switch ($this->networkmap['type']) {
				case 'groups':
					$graph = networkmap_generate_dot_groups (
						__('Pandora FMS'),
						$this->networkmap['id_group'],
						$this->networkmap['simple'],
						$this->networkmap['font_size'],
						$this->networkmap['layout'],
						(bool)$this->networkmap['nooverlap'],
						$this->networkmap['zoom'],
						$this->networkmap['distance_nodes'],
						$this->networkmap['center'], 1, 0,
						$this->networkmap['only_modules_with_alerts'],
						$this->networkmap['id_module_group'],
						$this->networkmap['hide_policy_modules'],
						$this->networkmap['depth'],
						$this->networkmap['id_networkmap']);
					break;
				case 'policies':
					$enterprise = enterprise_include('/include/functions_policies.php');
					
					if ($enterprise != ENTERPRISE_NOT_HOOK) {
						$graph = policies_generate_dot_graph (__('Pandora FMS'),
							$this->networkmap['id_group'],
							$this->networkmap['simple'],
							$this->networkmap['font_size'],
							$this->networkmap['layout'],
							(bool)$this->networkmap['nooverlap'],
							$this->networkmap['zoom'],
							$this->networkmap['distance_nodes'],
							$this->networkmap['center'], 1, 0,
							$this->networkmap['only_modules_with_alerts'],
							$this->networkmap['id_module_group'],
							$this->networkmap['depth'],
							$this->networkmap['id_networkmap']);
					}
					break;
				default:
				case 'topology':
					$graph = networkmap_generate_dot (__('Pandora FMS'),
						$this->networkmap['id_group'],
						$this->networkmap['simple'],
						$this->networkmap['font_size'],
						$this->networkmap['layout'],
						(bool)$this->networkmap['nooverlap'],
						$this->networkmap['zoom'],
						$this->networkmap['distance_nodes'],
						$this->networkmap['center'], 1, 0,
						$this->networkmap['id_networkmap'],
						$this->networkmap['show_snmp_modules'], true,
						true);
					break;
			}
			
			
			
			if ($graph === false) {
				$ui->contentAddHtml('<p style="color: #ff0000;">' . __('No networkmaps') . '</p>');
				
				$ui->endContent();
				$ui->showPage();
				
				return;
			}
			
			
			$filter = networkmap_get_filter($this->networkmap['layout']);
			
			// Generate image and map
			// If image was generated just a few minutes ago, then don't regenerate (it takes long) unless regen checkbox is set
			$filename_map = safe_url_extraclean (
				$system->getConfig('attachment_store')) . "/networkmap_" . $filter;
			$filename_img =  safe_url_extraclean (
				$system->getConfig('attachment_store')) . "/networkmap_" .
				$filter . "_" . $this->networkmap['font_size'];
			$url_img =   "../attachment/networkmap_" .
				$filter . "_" . $this->networkmap['font_size'];
			$filename_dot = safe_url_extraclean(
				$system->getConfig("attachment_store")) . "/networkmap_" . $filter;
			if ($this->networkmap['simple']) {
				$filename_map .= "_simple";
				$filename_img .= "_simple";
				$url_img .= "_simple";
				$filename_dot .= "_simple";
			}
			if ($this->networkmap['nooverlap']) {
				$filename_map .= "_nooverlap";
				$filename_img .= "_nooverlap";
				$url_img .= "_nooverlap";
				$filename_dot .= "_nooverlap";
			}
			$filename_map .= "_" . $this->networkmap['id_networkmap'] . ".map";
			$filename_img .= "_" . $this->networkmap['id_networkmap'] . ".png";
			$url_img .= "_" . $this->networkmap['id_networkmap'] . ".png";
			$filename_dot .= "_" . $this->networkmap['id_networkmap'] . ".dot";
			
			if ($this->networkmap['regenerate'] != 1 && file_exists($filename_img) && filemtime($filename_img) > get_system_time () - 300) {
				$result = true;
			}
			else {
				$fh = @fopen ($filename_dot, 'w');
				if ($fh === false) {
					$result = false;
				}
				else {
					fwrite ($fh, $graph);
					$cmd = $filter . " -Tcmapx -o" . $filename_map." -Tpng -o".$filename_img." ".$filename_dot;
					$result = system ($cmd);
					html_debug_print($cmd, true);
					fclose ($fh);
					//unlink ($filename_dot);
				}
			}
			
			if ($result !== false) {
				if (! file_exists ($filename_map)) {
					$ui->contentAddHtml('<p style="color: #ff0000;">' . __('Map could not be generated') . '</p>');
					
					$ui->endContent();
					$ui->showPage();
					
					return;
				}
				$ui->contentAddHtml('<div style="width: auto; overflow-x: auto; text-align: center;">');
				$ui->contentAddHtml('<img src="' . $url_img . '" />');
				$ui->contentAddHtml('</div>');
			}
			else {
				$ui->contentAddHtml('<p style="color: #ff0000;">' . __('Map could not be generated') . '</p>');
				
				$ui->endContent();
				$ui->showPage();
				
				return;
			}
			
		$ui->endContent();
		$ui->showPage();
	}
}
?>