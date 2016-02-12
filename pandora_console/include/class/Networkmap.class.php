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

require_once ('include/functions_os.php');
require_once ('include/functions_networkmap.php');
enterprise_include("include/functions_networkmap_enterprise.php");

require_once("include/class/Map.class.php");

class Networkmap extends Map {
	protected $filter = array();
	
	protected $source_group = 0;
	protected $source_ip_mask = "";
	
	public function __construct($id) {
		parent::__construct($id);
		
		$this->requires_js[] = "include/javascript/map/NetworkmapController.js";
	}
	
	public function processDBValues($dbValues) {
		$filter = json_decode($dbValues['filter'], true);
		
		$this->filter = $filter;
		
		switch ($dbValues['source_data']) {
			case MAP_SOURCE_GROUP:
				$this->source_group = $dbValues['source'];
				$this->source_ip_mask = "";
				break;
			case MAP_SOURCE_IP_MASK:
				$this->source_group = $dbValues['source'];
				$this->source_ip_mask = "";
				break;
		}
		
		parent::processDBValues($dbValues);
	}
	
	protected function generateDot() {
		// TODO
	}
	
	protected function temp_parseParameters_generateDot() {
		$return = array();
		
		$return['id_group'] = $this->source_group;
		$return['simple'] = 0; // HARD CODED
		$return['font_size'] = null;
		$return['layout'] = null;
		$return['nooverlap'] = false; // HARD CODED
		$return['zoom'] = 1; // HARD CODED
		$return['ranksep'] = 2.5; // HARD CODED
		$return['center'] = 0; // HARD CODED
		$return['regen'] = 0; // HARD CODED
		$return['pure'] = 0; // HARD CODED
		$return['id'] = $this->id;
		$return['show_snmp_modules'] = $this->filter['only_snmp_modules'];
		$return['l2_network_interfaces'] = true; // HARD CODED
		$return['ip_mask'] = $this->source_ip_mask;
		$return['dont_show_subgroups'] = false;
		$return['old_mode'] = false;
		$return['filter'] = $this->filter['text'];
		
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
				false, //cut_names
				true, // relative
				'',
				$parameters['l2_network_interfaces'],
				$parameters['ip_mask'],
				$parameters['dont_show_subgroups'],
				false,
				null,
				$parameters['old_mode']);
			
			
			
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
					$graphviz_command = "spring1";
					break;
				case MAP_GENERATION_SPRING2:
					$graphviz_command = "spring2";
					break;
			}
			
			$cmd = "$graphviz_command " .
			"-Tpng -o /tmp/caca.png -Tplain -o " . $filename_plain . " " .
				$filename_dot;
			
			system ($cmd);
			
			unlink($filename_dot);
			
			html_debug($cmd);
			html_debug($filename_plain);
			html_debug(file_get_contents($filename_plain), true);
			
			$nodes = networkmap_enterprise_loadfile($this->id,
				$filename_plain,
				$relation_nodes, $graph,
				$parameters['l2_network_interfaces']);
			html_debug_print($graph);
			//~ html_debug_print($nodes);
			//~ html_debug_print($relation_nodes);
			
			// debug image
			// Read image path, convert to base64 encoding
			$imgData = base64_encode(file_get_contents("/tmp/caca.png"));
			
			// Format the image SRC:  data:{mime};base64,{data};
			$src = 'data: '.mime_content_type("/tmp/caca.png").';base64,'.$imgData;
				
			// Echo out a sample image
			echo '<img src="'.$src.'">';
			
			// ----- END DEPRECATED CODE--------------------------------
			$this->nodes[] = array(666);
		}
	}
	
	public function show() {
		$this->getNodes();
		
		parent::show();
	}
	
	public function printJSInit() {
		echo "<h1>Networkmap</h1>";
		?>
		<script type="text/javascript">
			$(function() {
				// map = new js_networkmap();
			});
		</script>
		<?php
	}
}
?>