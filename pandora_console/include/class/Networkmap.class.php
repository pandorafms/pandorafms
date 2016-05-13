<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.



/**
 * @package Include
 * @subpackage Networkmap
 */

class Networkmap extends Map {
	protected $filter = array();
	
	protected $source_group = 0;
	protected $source_ip_mask = "";
	
	public function __construct($id) {
		global $config;
		
		require_once($config['homedir'] . '/include/functions_os.php');
		require_once($config['homedir'] . '/include/functions_networkmap.php');
		enterprise_include_once("include/functions_networkmap_enterprise.php");
		
		parent::__construct($id);
		
		$this->requires_js[] = "include/javascript/map/NetworkmapController.js";
	}
	
	public function processDBValues($dbValues) {
		$filter = json_decode($dbValues['filter'], true);
		
		$this->filter = $filter;
		if (!isset($this->filter['only_snmp_modules']))
			$this->filter['only_snmp_modules'] = false;
		
		switch ($dbValues['source']) {
			case MAP_SOURCE_GROUP:
				$this->source_group = $dbValues['source_data'];
				$this->source_ip_mask = "";
				break;
			case MAP_SOURCE_IP_MASK:
				$this->source_ip_mask = $dbValues['source_data'];
				$this->source_group = "";
				break;
		}
		
		parent::processDBValues($dbValues);
	}
	
	protected function generateDot($graph, $positions) {
		
		
		$graph = preg_replace('/^graph .*/', '', $graph);
		
		$nodes_and_edges = explode("];", $graph);
		
		$nodes = array();
		$edges = array();
		$last_graph_id = 0;
		foreach ($nodes_and_edges as $node_or_edge) {
			$node_or_edge = trim($node_or_edge);
			
			$chunks = explode("[", $node_or_edge);
			
			if (strstr($chunks[0], "--") !== false) {
				// EDGE
				$graphviz_ids = explode("--", $chunks[0]);
				
				if (!is_numeric(trim($graphviz_ids[0])) ||
					!is_numeric(trim($graphviz_ids[1]))) {
					
					continue;
				}
				
				
				$edges[] = array(
					'to' => trim($graphviz_ids[0]),
					'from' => trim($graphviz_ids[1]));
			}
			else {
				// NODE
				$graphviz_id = trim($chunks[0]);
				
				// Avoid the weird nodes.
				if (!is_numeric($graphviz_id))
					continue;
				
				$is_node_module_group = false;
				if ($this->filter['show_module_group']) {
					if (strstr($chunks[1], 'URL=""') !== false) {
						$is_node_module_group = true;
					}
				}
				
				$other_chunks = explode("ajax.php?", $chunks[1]);
				
				$is_node_group = false;
				if ($this->subtype == MAP_SUBTYPE_GROUPS) {
					if (strstr($other_chunks[1], "&id_group=") !== false) {
						$is_node_group = true;
					}
				}
				
				$is_node_policy = false;
				if ($this->subtype == MAP_SUBTYPE_POLICIES) {
					if (strstr($other_chunks[1], "&id_policy=") !== false) {
						$is_node_policy = true;
					}
				}
				
				
				$id_agent = null;
				$status = null;
				$title = "";
				$width = DEFAULT_NODE_WIDTH;
				$height = DEFAULT_NODE_HEIGHT;
				$shape = DEFAULT_NODE_SHAPE;
				$color = DEFAULT_NODE_COLOR;
				$image = DEFAULT_NODE_IMAGE;
				
				if ($is_node_policy) {
					preg_match("/<TR><TD>(.*)<\/TD><\/TR><\/TABLE>>/", $other_chunks[0], $matches);
					$title = io_safe_output($matches[1]);
					preg_match("/id_policy=([0-9]*)/", $other_chunks[1], $matches);
					$id = $matches[1];
					$type = ITEM_TYPE_POLICY_NETWORKMAP;
					preg_match("/data-status=\"([0-9]*)\"/", $other_chunks[1], $matches);
					$status = $matches[1];
					$shape = "rhombus";
					
					preg_match("/<img src=\"(.*)\" \/>/", $other_chunks[0], $matches);
					$image = $matches[1];
				}
				elseif ($is_node_group) {
					preg_match("/<TR><TD>(.*)<\/TD><\/TR><\/TABLE>>/", $other_chunks[0], $matches);
					$title = $matches[1];
					preg_match("/id_group=([0-9]*)/", $other_chunks[1], $matches);
					$id = $matches[1];
					$type = ITEM_TYPE_GROUP_NETWORKMAP;
					preg_match("/data-status=\"([0-9]*)\"/", $other_chunks[1], $matches);
					$status = $matches[1];
					$shape = "rhombus";
					
					preg_match("/<img src=\"(.*)\" \/>/", $other_chunks[0], $matches);
					$image = $matches[1];
				}
				elseif ($is_node_module_group) {
					preg_match("/<TR><TD>(.*)<\/TD><\/TR><\/TABLE>>/", $chunks[1], $matches);
					$title = $matches[1];
					$id = db_get_value('id_mg', 'tmodule_group',
						'name', $title);
					$type = ITEM_TYPE_MODULEGROUP_NETWORKMAP;
					preg_match("/data-status=\"([0-9]*)\"/", $chunks[1], $matches);
					$status = $matches[1];
					preg_match("/data-id_agent=\"([0-9]*)\"/", $chunks[1], $matches);
					$id_agent = $matches[1];
					$shape = "rhombus";
					
					//The module group has not icon.
					$image = "";
				}
				elseif (strstr($other_chunks[1], "&id_module=") !== false) {
					// MODULE
					preg_match("/id_module=([0-9]*)/", $other_chunks[1], $matches);
					$id = $matches[1];
					$id_agent = agents_get_module_id($id);
					$status = modules_get_agentmodule_status($id);
					$title = modules_get_agentmodule_name($id);
					$type = ITEM_TYPE_MODULE_NETWORKMAP;
					$shape = "square";
					
					preg_match("/<img src=\"(.*)\" \/>/", $other_chunks[0], $matches);
					$image = $matches[1];
				}
				else {
					// AGENT
					preg_match("/id_agent=([0-9]*)/", $other_chunks[1], $matches);
					$id_agent = $id = $matches[1];
					$status = agents_get_status($id);
					$title = agents_get_name($id);
					$type = ITEM_TYPE_AGENT_NETWORKMAP;
					
					preg_match("/<img src=\"(.*)\" \/>/", $other_chunks[0], $matches);
					$image = $matches[1];
				}
				
				// Set node status
				switch ($status) {
					case AGENT_STATUS_NORMAL: 
						$color = COL_NORMAL;
						break;
					case AGENT_STATUS_CRITICAL:
						$color = COL_CRITICAL;
						break;
					case AGENT_STATUS_WARNING:
						$color = COL_WARNING;
						break;
					case AGENT_STATUS_ALERT_FIRED:
						$color = COL_ALERTFIRED;
						break;
					# Juanma (05/05/2014) Fix: Correct color for not init agents!
					case AGENT_STATUS_NOT_INIT:
						$color = COL_NOTINIT;
						break;
					default:
						//Unknown monitor
						$color = COL_UNKNOWN;
						break;
				}
				
				
				
				$nodes[] = array('graph_id' => $graphviz_id,
					'id' => $id,
					'id_agent' => $id_agent,
					'type' => $type,
					'status' => $status,
					'title' => $title,
					'width' => $width,
					'height' => $height,
					'shape' => $shape,
					'color' => $color,
					'image' => $image);
				
				if ($last_graph_id < $graphviz_id)
					$last_graph_id = $graphviz_id;
			}
		}
		
		
		
		foreach ($positions as $line) {
			//clean line a long spaces for one space caracter
			$line = preg_replace('/[ ]+/', ' ', $line);
			
			if (preg_match('/^node.*$/', $line) != 0) {
				$items = explode(' ', $line);
				$graphviz_id = $items[1];
				
				// We need a binary tree...in some future time.
				
				foreach ($nodes as $i => $node) {
					if ($node['graph_id'] == $graphviz_id) {
						$nodes[$i]['x'] = $items[2];
						$nodes[$i]['y'] = $items[3];
					}
				}
			}
		}
		
		if (($this->width > 0) && ($this->height > 0)) {
			
			$max_x = 0;
			$max_y = 0;
			
			foreach ($nodes as $node) {
				if ($max_x < $node['x']) {
					$max_x = $node['x'];
				}
				
				if ($max_y < $node['y']) {
					$max_y = $node['y'];
				}
			}
			
			$sep_x = $this->width / $max_x;
			$sep_y = $this->height / $max_y;
		}
		else {
			$sep_x = $sep_y = 100;
		}
		
		foreach ($nodes as $i => $node) {
			$nodes[$i]['x'] *= $sep_x;
			$nodes[$i]['y'] *= $sep_y;
		}
		
		foreach ($edges as $i => $edge) {
			$graph_id = ++$last_graph_id;
			
			$nodes[] = array(
				'graph_id' => $graph_id,
				'type' => ITEM_TYPE_EDGE_NETWORKMAP);
			$edges[$i]['graph_id'] = $graph_id;
			
		}
		
		$this->nodes = $nodes;
		$this->edges = $edges;
		
		html_debug("--------------nodes------------------------", true);
		$lines = "";
		foreach ($nodes as $n) {
			$lines .= $n['graph_id']."|".$n['id']."(".$n['id_agent'].")"."|".$n['type']."|".$n['title']."\n";
		}
		html_debug($lines, true);
		html_debug("--------------edges------------------------", true);
		$lines = "";
		foreach ($edges as $e) {
			$lines .= $e['to']."|".$e['from']."\n";
		}
		html_debug($lines, true);
	}
	
	public function getSourceGroup() {
		return $this->source_group;
	}
	
	protected function temp_parseParameters_generateDot() {
		$return = array();
		
		$return['id_group'] = $this->source_group;
		$return['simple'] = 0; // HARD CODED
		$return['font_size'] = 12; // HARD CODED
		$return['layout'] = "radial"; // HARD CODED
		$return['nooverlap'] = false; // HARD CODED
		$return['zoom'] = 1; // HARD CODED
		$return['ranksep'] = 2.5; // HARD CODED
		$return['center'] = 0; // HARD CODED
		$return['regen'] = 1; // HARD CODED
		$return['pure'] = 0; // HARD CODED
		$return['id'] = $this->id;
		$return['show_snmp_modules'] = $this->filter['only_snmp_modules'];
		$return['cut_names'] = 0; // HARD CODED
		$return['l2_network_interfaces'] = true; // HARD CODED
		$return['ip_mask'] = $this->source_ip_mask;
		$return['dont_show_subgroups'] = false;
		$return['old_mode'] = false;
		$return['filter'] = $this->filter['text'];
		$return['id_tag'] = $this->filter['id_tag'];
		$return['show_modules'] = $this->filter['show_modules'];
		$return['only_modules_alerts'] = $this->filter['only_modules_with_alerts'];
		$return['module_group'] = $this->filter['module_group'];
		$return['show_modulegroup'] = $this->filter['show_module_group'];
		
		switch ($this->subtype) {
			case MAP_SUBTYPE_GROUPS:
				$return['show_policies'] = false;
				$return['show_groups'] = true;
				$return['show_agents'] = $this->filter['show_agents'];
				break;
			case MAP_SUBTYPE_POLICIES:
				$return['show_policies'] = true;
				$return['show_groups'] = false;
				$return['show_agents'] = $this->filter['show_agents'];
				break;
			default:
				$return['show_policies'] = false;
				$return['show_groups'] = false;
				$return['show_agents'] = true;
				break;
		}
		
		return $return;
	}
	
	protected function getNodes() {
		if (empty($this->nodes)) {
			
			// ----- INI DEPRECATED CODE--------------------------------
			//  I hope this code to change for any some better and
			//  rewrote the old function.
			
			$parameters = $this->temp_parseParameters_generateDot();
			
			
			// Generate dot file
			$graph = networkmap_generate_dot (__('Pandora FMS'),
				$parameters['id_group'],
				$parameters['simple'],
				$parameters['font_size'],
				$parameters['layout'],
				$parameters['nooverlap'],
				$parameters['zoom'],
				$parameters['ranksep'],
				$parameters['center'],
				$parameters['regen'],
				$parameters['pure'],
				$parameters['id'],
				$parameters['show_snmp_modules'],
				$parameters['cut_names'],
				true, // relative
				$this->filter['text'],
				'mix_l2_l3',
				$parameters['ip_mask'],
				$parameters['dont_show_subgroups'],
				false,
				null,
				$parameters['old_mode'],
				$parameters['id_tag'],
				$parameters['show_modules'],
				$parameters['only_modules_alerts'],
				$parameters['module_group'],
				$parameters['show_modulegroup'],
				$parameters['show_groups'],
				$parameters['show_agents'],
				$parameters['show_policies']);
			
			
			$filename_dot = sys_get_temp_dir() . "/networkmap" . uniqid() . ".dot";
			
			file_put_contents($filename_dot, $graph);
			
			$filename_plain = sys_get_temp_dir() . "/plain.txt";
			
			
			
			switch ($this->generation_method) {
				case MAP_GENERATION_CIRCULAR:
					$graphviz_command = "circo";
					break;
				case MAP_GENERATION_PLANO:
					$graphviz_command = "dot";
					break;
				case MAP_GENERATION_RADIAL:
					$graphviz_command = "twopi";
					break;
				case MAP_GENERATION_SPRING1:
					$graphviz_command = "neato";
					break;
				case MAP_GENERATION_SPRING2:
					$graphviz_command = "fdp";
					break;
			}
			
			$cmd = "$graphviz_command " .
			" -Tplain -o " . $filename_plain . " " .
				$filename_dot;
			
			system ($cmd);
			
			
			
			$this->generateDot($graph, file($filename_plain));
			
			unlink($filename_dot);
			unlink($filename_plain);
			// ----- END DEPRECATED CODE--------------------------------
		}
	}
	
	public function writeJSGraph() {
		parent::writeJSGraph();
		
		$name_subtype = "controller_" . $this->id;
		
		?>
		<script type="text/javascript">
			<?php
			echo "var " . $name_subtype . " = " . $this->subtype . ";";
			?>
		</script>
		<?php
	}
	
	public function show() {
		$this->getNodes();
		
		
		
		foreach ($this->nodes as $i => $node) {
			$this->nodes[$i]['title'] = 
				ui_print_truncate_text(
					$node['title'],
					GENERIC_SIZE_TEXT,
					false,
					true,
					false,
					'...',
					false);
		}
		
		parent::show();
	}
	
	public function printJSInit() {
		$name_object = "controller_" . $this->id;
		$target = "#map_" . $this->id;
		
		?>
		<script type="text/javascript">
			var <?php echo $name_object;?> = null
			$(function() {
				<?php echo $name_object;?> = new NetworkmapController(
					"<?php echo $target;?>",
					<?php echo $this->source_period;?>);
				
				<?php echo $name_object;?>.init_map();
			});
		</script>
		<?php
	}
}
?>