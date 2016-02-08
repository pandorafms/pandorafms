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
	protected $show_snmp_modules = false;
	
	public function __construct($id) {
		parent::__construct($id);
		
		$this->requires_js[] = "include/javascript/map/NetworkmapController.js";
	}
	
	public function processDBValues($dbValues) {
		$filter = json_decode($dbValues, true);
		
		$this->show_snmp_modules = true;
		
		parent::processDBValues($dbValues);
	}
	
	protected function generateDot() {
		// TODO
	}
	
	protected function temp_parseParameters_generateDot() {
		$return = array();
		
		$return['id_group'] = $this->id_group;
		$return['simple'] = 12; // HARD CODED
		$return['font_size'] = null;
		$return['layout'] = null;
		$return['nooverlap'] = false; // HARD CODED
		$return['zoom'] = 1; // HARD CODED
		$return['ranksep'] = 2.5; // HARD CODED
		$return['center'] = 0; // HARD CODED
		$return['regen'] = 0; // HARD CODED
		$return['pure'] = 0; // HARD CODED
		$return['id'] = $this->id;
		$return['show_snmp_modules'] = $this->show_snmp_modules;
		$return['l2_network_interfaces'] = null;
		$return['ip_mask'] = null;
		$return['dont_show_subgroups'] = null;
		$return['old_mode'] = null;
		$return['filter'] = null;
		$return['simple'] = 0; // HARD CODED
		
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
			
			
			$filename_dot = sys_get_temp_dir() . "/networkmap_" . $parameters['filter'];
			if ($parameters['simple']) {
				$filename_dot .= "_simple";
			}
			if ($nooverlap) {
				$filename_dot .= "_nooverlap";
			}
			$filename_dot .= "_" . $id . ".dot";
			
			file_put_contents($filename_dot, $graph);
			
			$filename_plain = sys_get_temp_dir() . "/plain.txt";
			
			$cmd = "$filter -Tplain -o " . $filename_plain . " " .
				$filename_dot;
			
			system ($cmd);
			
			unlink($filename_dot);
			
			$nodes = networkmap_enterprise_loadfile($id, $filename_plain,
				$relation_nodes, $graph, $l2_network_interfaces);
			//~ html_debug_print($graph);
			//~ html_debug_print($nodes);
			//~ html_debug_print($relation_nodes);
			
			// ----- END DEPRECATED CODE--------------------------------
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