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

class ModuleGraph {
	private $correct_acl = false;
	private $acl = "AR";
	
	private $id = 0;
	private $graph_type = "sparse";
	private $period = SECONDS_1DAY;
	private $draw_events = 0;
	private $width = 0;
	private $height = 0;
	private $draw_alerts = 0;
	private $avg_only = 0;
	private $start_date = 0;
	private $time_compare_separated = 0;
	private $time_compare_overlapped = 0;
	private $unknown_graph = 1;
	private $zoom = 1;
	private $baseline = 0;
	
	private $module = null;
	
	function __construct() {
		$system = System::getInstance();
		
		$this->start_date = date("Y-m-d");
		
		if ($system->checkACL($this->acl)) {
			$this->correct_acl = true;
		}
		else {
			$this->correct_acl = false;
		}
	}
	
	private function getFilters() {
		$system = System::getInstance();
		
		$this->id = (int)$system->getRequest('id', 0);
		$this->module = modules_get_agentmodule($this->id);
		$this->graph_type = return_graphtype($this->module["id_tipo_modulo"]);
		
		$period_hours = $system->getRequest('period_hours', false);
		if ($period_hours === false) {
			$this->period = SECONDS_1DAY;
		}
		else {
			$this->period = $period_hours * SECONDS_1HOUR;
		}
		$this->draw_events = (int)$system->getRequest('draw_events', 0);
		$this->draw_alerts = (int)$system->getRequest('draw_alerts', 0);
		$this->avg_only = (int)$system->getRequest('avg_only', 0);
		$this->start_date = $system->getRequest('start_date', false);
		if ($this->start_date === false) {
			$this->start_date = date("Y-m-d");
		}
		else {
			$this->start_date = date("Y-m-d", strtotime($this->start_date));
		}
		$this->time_compare_separated = (int)$system->getRequest('time_compare_separated', 0);
		$this->time_compare_overlapped = (int)$system->getRequest('time_compare_overlapped', 0);
		$this->unknown_graph = (int)$system->getRequest('unknown_graph', 0);
		$this->zoom = (int)$system->getRequest('zoom', 1);
		$this->baseline = (int)$system->getRequest('baseline', 0);
		
		$this->width = (int)$system->getRequest('width', 0);
		$this->width -= 20; //Correct the width
		$this->height = (int)$system->getRequest('height', 0);
		
		//Sancho says "put the height to 1/2 for to make more beautyful"
		//$this->height = $this->height / 2;
		
		$this->height -= 80; //Correct the height
		
	}
	
	public function ajax($parameter2 = false) {
		$system = System::getInstance();
		
		if (!$this->correct_acl) {
			return;
		}
		else {
			switch ($parameter2) {
				case 'get_graph':
					$this->getFilters();
					$correct = 0;
					$graph = '';
					
					$correct = 1;
					
					$label = $this->module["nombre"];
					$unit = db_get_value('unit', 'tagente_modulo',
						'id_agente_modulo', $this->id);
					
					$utime = get_system_time ();
					$current = date("Y-m-d", $utime);
					
					if ($this->start_date != $current)
						$date = strtotime($this->start_date);
					else
						$date = $utime;
					
					$urlImage = ui_get_full_url(false);
					
					$time_compare = false;
					if ($this->time_compare_separated) {
						$time_compare = 'separated';
					}
					else if ($this->time_compare_overlapped) {
						$time_compare = 'overlapped';
					}
					
					
					ob_start();
					switch ($this->graph_type) {
						case 'boolean':
							$graph = grafico_modulo_boolean (
								$this->id,
								$this->period,
								$this->draw_events,
								$this->width,
								$this->height,
								$label,
								$unit,
								$this->draw_alerts,
								$this->avg_only,
								false,
								$date,
								true,
								$urlImage,
								$time_compare);
							break;
						case 'sparse':
							$graph = grafico_modulo_sparse(
								$this->id,
								$this->period,
								$this->draw_events,
								$this->width,
								$this->height,
								$label,
								null,
								$this->draw_alerts,
								$this->avg_only,
								false,
								$date,
								$unit,
								$this->baseline,
								0,
								true,
								true,
								$urlImage,
								1,
								false,
								$time_compare);
							break;
						case 'string':
							$graph = grafico_modulo_string(
								$this->id,
								$this->period,
								$this->draw_events,
								$this->width,
								$this->height,
								$label,
								null,
								$this->draw_alerts,
								1,
								true,
								$date,
								false,
								$urlImage);
							break;
						case 'log4x':
							$graph = grafico_modulo_log4x(
								$this->id,
								$this->period,
								$this->draw_events,
								$this->width,
								$this->height,
								$label,
								$unit_name,
								$this->draw_alerts,
								true,
								$pure,
								$date);
							break;
						default:
							$graph .= fs_error_image ('../images');
							break;
					}
					$graph = ob_get_clean() . $graph;
					
					echo json_encode(array('correct' => $correct, 'graph' => $graph));
					break;
			}
		}
	}
	
	public function show() {
		if (!$this->correct_acl) {
			$this->show_fail_acl();
		}
		else {
			$this->getFilters();
			$this->showModuleGraph();
		}
	}
	
	private function show_fail_acl() {
		$ui = Ui::getInstance();
		
		$ui->createPage();
		
		$options['type'] = 'onStart';
		$options['title_text'] = __('You don\'t have access to this page');
		$options['content_text'] = __('Access to this page is restricted to authorized users only, please contact system administrator if you need assistance. <br><br>Please know that all attempts to access this page are recorded in security logs of Pandora System Database');
		$ui->addDialog($options);
		
		$ui->showPage();
	}
	
	private function javascript_code() {
		ob_start();
		?>
		<script type="text/javascript">
			$(document).bind('ready', function() {
				$("#graph_content")
					.height(($(window).height()
						- $(".ui-header").height()
						- $(".ui-collapsible").height()
						- 55) + "px");
				
				ajax_get_graph($("#id_module").val());
			});
			
			function ajax_get_graph(id) {
				postvars = {};
				postvars["action"] = "ajax";
				postvars["parameter1"] = "module_graph";
				postvars["parameter2"] = "get_graph";
				postvars["width"] = $("#graph_content").width();
				postvars["height"] = $("#graph_content").height();
				
				postvars["draw_alerts"] = ($("input[name = 'draw_alerts']").is(":checked"))?1:0;
				postvars["draw_events"] = ($("input[name = 'draw_events']").is(":checked"))?1:0;
				postvars["time_compare_separated"] = ($("input[name = 'time_compare_separated']").is(":checked"))?1:0;
				postvars["time_compare_overlapped"] = ($("input[name = 'time_compare_overlapped']").is(":checked"))?1:0;
				postvars["unknown_graph"] = ($("input[name = 'unknown_graph']").is(":checked"))?1:0;;
				postvars["avg_only"] = ($("input[name = 'avg_only']").is(":checked"))?1:0;;
				
				postvars["period_hours"] = $("input[name = 'period_hours']").val();
				postvars["zoom"] = $("input[name = 'zoom']").val();
				postvars["start_date"] = $("input[name = 'start_date']").val();
				
				postvars["id"] = id;
				
				$.ajax ({
					type: "POST",
					url: "index.php",
					dataType: "json",
					data: postvars,
					success:
					function (data) {
						$("#loading_graph").hide();
						if (data.correct) {
							$("#graph_content").show();
							$("#graph_content").html(data.graph);
						}
						else {
							$("#error_graph").show();
						}
					},
					error:
					function (jqXHR, textStatus, errorThrown) {
						$("#loading_graph").hide();
						$("#error_graph").show();
					}
					});
			}
		</script>
		<?php
		$javascript_code = ob_get_clean();
		
		return $javascript_code;
	}
	
	private function showModuleGraph() {
		$ui = Ui::getInstance();
		
		$ui->createPage();
		
		$ui->createDefaultHeader(sprintf(__("PandoraFMS: %s"), $this->module["nombre"]));
		$ui->showFooter(false);
		$ui->beginContent();
			$ui->contentAddHtml($ui->getInput(array(
				'id' => 'id_module',
				'value' => $this->id,
				'type' => 'hidden'
				)));
			$ui->contentBeginCollapsible("Options");
				$ui->beginForm("index.php?page=module_graph&id=" . $this->id);
					$options = array(
						'name' => 'draw_alerts',
						'value' => 1,
						'checked' => (bool)$this->draw_alerts,
						'label' => __('Show Alerts')
						);
					$ui->formAddCheckbox($options);
					
					$options = array(
						'name' => 'draw_events',
						'value' => 1,
						'checked' => (bool)$this->draw_events,
						'label' => __('Show Events')
						);
					$ui->formAddCheckbox($options);
					
					$options = array(
						'name' => 'time_compare_separated',
						'value' => 1,
						'checked' => (bool)$this->time_compare_separated,
						'label' => __('Time compare (Separated)')
						);
					$ui->formAddCheckbox($options);
					
					$options = array(
						'name' => 'time_compare_overlapped',
						'value' => 1,
						'checked' => (bool)$this->time_compare_overlapped,
						'label' => __('Time compare (Overlapped)')
						);
					$ui->formAddCheckbox($options);
					
					$options = array(
						'name' => 'unknown_graph',
						'value' => 1,
						'checked' => (bool)$this->unknown_graph,
						'label' => __('Show unknown graph')
						);
					$ui->formAddCheckbox($options);
					
					$options = array(
						'name' => 'avg_only',
						'value' => 1,
						'checked' => (bool)$this->avg_only,
						'label' => __('Avg Only')
						);
					$ui->formAddCheckbox($options);
					
					$options = array(
						'label' => __('Time range (hours)'),
						'name' => 'period_hours',
						'value' => ($this->period / SECONDS_1HOUR),
						'min' => 0,
						'max' => 24 * 30,
						'step' => 4
						);
					$ui->formAddSlider($options);
					
					/*
					$items = array('1' => __('x1'),
						'2' => __('x2'),
						'3' => __('x3'),
						'4' => __('x4'));
					$options = array(
						'name' => 'zoom',
						'title' => __('Zoom'),
						'label' => __('Zoom'),
						'items' => $items,
						'selected' => $this->zoom
						);
					$ui->formAddSelectBox($options);
					*/
					
					$options = array(
						'name' => 'start_date',
						'value' => $this->start_date,
						'label' => __('Begin date')
						);
					$ui->formAddInpuDate($options);
					
					$options = array(
						'icon' => 'refresh',
						'icon_pos' => 'right',
						'text' => __('Update graph')
						);
					$ui->formAddSubmitButton($options);
					
				$html = $ui->getEndForm();
				$ui->contentCollapsibleAddItem($html);
			$ui->contentEndCollapsible();
			$ui->contentAddHtml('<div id="graph_content" style="display: none; width: 100%; height: 100%;"></div>
				<div id="loading_graph" style="width: 100%; text-align: center;">' . __('Loading...') . '<br /><img src="images/ajax-loader.gif" /></div>
				<div id="error_graph" style="display: none; color: red; width: 100%; text-align: center;">' . __('Error get the graph') . '</div>');
			$ui->contentAddHtml($this->javascript_code());
		$ui->endContent();
		$ui->showPage();
	}
	/*
	 */
}