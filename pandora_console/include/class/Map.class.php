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
 * @subpackage Maps
 */

abstract class Map {
	protected $status = STATUS_OK;
	
	protected $id = null;
	
	protected $type = null;
	protected $subtype = null;
	protected $id_group = null;
	protected $generation_method = null;
	
	protected $width = null;
	protected $height = null;
	
	protected $nodes = array();
	protected $edges = array();
	
	protected $requires_js = null;
	
	public static function getName($id = null) {
		if (empty($id)) {
			return null;
		}
		else {
			return db_get_value('name', 'tmap', 'id', $id);
		}
	}
	
	public function __construct($id) {
		$this->id = $id;
		
		$this->requires_js = array();
		$this->requires_js[] = "include/javascript/d3.3.5.14.js";
		$this->requires_js[] = "include/javascript/map/MapController.js";
		$this->requires_js[] = "include/javascript/jquery.tooltipster.js";
		$this->requires_js[] = "include/javascript/jquery.svg.js";
		$this->requires_js[] = "include/javascript/jquery.svgdom.js";
		
		if (!$this->loadDB()) {
			$this->status = STATUS_ERROR;
		}
	}
	
	protected function processDBValues($dbValues) {
		$this->type = (int)$dbValues['type'];
		$this->subtype = (int)$dbValues['subtype'];
		
		$this->id_group = (int)$dbValues['id_group'];
		$this->generation_method = (int)$dbValues['generation_method'];
		
		$this->width = (int)$dbValues['width'];
		$this->height = (int)$dbValues['height'];
	}
	
	private function loadDB() {
		$row = db_get_row_filter('tmap', array('id' => $this->id));
		
		if (empty($row))
			return false;
		
		switch (get_class($this)) {
			case 'Networkmap':
				Networkmap::processDBValues($row);
				break;
			case 'NetworkmapEnterprise':
				NetworkmapEnterprise::processDBValues($row);
				break;
			default:
				$this->processDBValues($row);
				break;
		}
	}
	
	abstract function printJSInit();
	
	public function writeJSGraph() {
		?>
		<script type="text/javascript">
			<?php
			echo "var nodes = " . json_encode($this->nodes) . ";";
			echo "var edges = " . json_encode($this->edges) . ";";
			?>
			var temp = [];
			for (var i in nodes) { temp[parseInt(i)] = nodes[i];}
			nodes = temp;
			
			temp = [];
			for (var i in edges) { temp[parseInt(i)] = edges[i];}
			edges = temp;
		</script>
		<?php
	}
	
	public function show() {
		// Tooltip css
		echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"include/styles/tooltipster.css\"/>" . "\n";
		echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"include/styles/tooltipster-punk.css\"/>" . "\n";
		echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"include/styles/tooltipster-shadow.css\"/>" . "\n";
		echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"include/styles/tooltipster-noir.css\"/>" . "\n";
		echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"include/styles/tooltipster-light.css\"/>" . "\n";
		foreach ($this->requires_js as $js) {
			echo "<script type='text/javascript' src='$js'></script>" . "\n";
		}
		
		$this->writeJSContants();
		$this->writeJSGraph();
		
		?>
		
		<div id="map" data-id="<?php echo $this->id;?>" style="border: 1px red solid;">
			<?php
			if ($this->width == 0) {
				$width = "100%";
			}
			else {
				$width = $this->width . "px";
			}
			if ($this->height == 0) {
				$height = "500px";
			}
			else {
				$height = $this->height . "px";
			}
			?>
			<svg xmlns="http://www.w3.org/2000/svg" pointer-events="all" width="<?php echo $width;?>" height="<?php echo $height;?>">
				<g class="viewport2">
					<g class="first">
					  <circle cx="100" cy="100" r="90" fill="green"></circle>
					</g>
					<g class="second">
					  <rect x="200" y="200" height="90" width="90" fill="blue"></rect>
					</g>
				</g>
			</svg>
		</div>
		
		<?php
		$this->printJSInit();
	}
	
	public function writeJSContants() {
		$contants = array();
		$contants["ITEM_TYPE_AGENT_NETWORKMAP"] = ITEM_TYPE_AGENT_NETWORKMAP;
		$contants["ITEM_TYPE_MODULE_NETWORKMAP"] = ITEM_TYPE_MODULE_NETWORKMAP;
		$contants["ITEM_TYPE_EDGE_NETWORKMAP"] = ITEM_TYPE_EDGE_NETWORKMAP;
		?>
		<script type="text/javascript">
			<?php
			foreach ($contants as $name => $value) {
				echo "var $name = $value \n";
			}
			?>
		</script>
		<?php
	}
	
	public function getType() {
		return $this->type;
	}
}
?>
