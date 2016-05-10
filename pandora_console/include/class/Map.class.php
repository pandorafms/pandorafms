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
	protected $source_period = MAP_REFRESH_TIME;
	
	protected $width = null;
	protected $height = null;
	
	protected $nodes = array();
	protected $edges = array();
	
	protected $requires_js = null;
	
	protected $is_buggy_firefox = false;
	
	public function getId() {
		return $this->id;
	}
	public function getGroup() {
		return $this->id_group;
	}
	
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
		$this->requires_js[] = "include/javascript/d3-context-menu.js";
		
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
		
		$this->source_period = (int)$dbValues['source_period'];
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
		
		$nodes_name = "nodes_" . $this->id;
		$edges_name = "edges_" . $this->id;
		$filter_name = "filter_" . $this->id;
		
		?>
		<script type="text/javascript">
			<?php
			echo "var " . $nodes_name . " = " . json_encode($this->nodes) . ";";
			echo "var " . $edges_name . " = " . json_encode($this->edges) . ";";
			echo "var " . $filter_name . " = " . json_encode($this->filter) . ";";
			?>
		</script>
		<?php
	}
	
	private function check_browser() {
		global $config;
		
		//~ $browser = get_browser_local(null, true, $config['homedir'] . '/include/browscap/php_browscap.ini');
		
		//~ switch ($browser['browser']) {
			//~ case 'Firefox':
				//~ // Firefox BUG
				//~ // https://bugzilla.mozilla.org/show_bug.cgi?id=1254159
				//~ 
				//~ $this->is_buggy_firefox = true;
				//~ break;
			//~ case 'Microsoft':
				//~ // Do install a GNU/Linux.
				//~ break;
			//~ default:
				//~ // The world is a wonderful place.
				//~ break;
		//~ }
	}
	
	public function show() {
		// Tooltip css
		echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"include/styles/tooltipster.css\"/>" . "\n";
		echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"include/styles/tooltipster-punk.css\"/>" . "\n";
		echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"include/styles/tooltipster-shadow.css\"/>" . "\n";
		echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"include/styles/tooltipster-noir.css\"/>" . "\n";
		echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"include/styles/tooltipster-light.css\"/>" . "\n";
		echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"include/styles/d3-context-menu.css\"/>" . "\n";
		//Tooltips spinner
		echo "<div id='spinner_tooltip' style='display:none;'>";
			html_print_image('images/spinner.gif');
		echo "</div>";
		foreach ($this->requires_js as $js) {
			echo "<script type='text/javascript' src='$js'></script>" . "\n";
		}
		
		$this->check_browser();
		
		$this->writeJSConstants();
		$this->writeJSGraph();
		
		?>
		<div id="map_<?php echo $this->id;?>" data-id="<?php echo $this->id;?>" style="border: 1px red solid;">
			<div class="zoom_box" style="position: absolute;">
				<div class="zoom_controller">
					<input class="vertical_range" type="range" name="range" min="-666" max="666" step="1" value="666" />
				</div>
				<div class="zoom_in">+</div>
				<div class="zoom_out">-</div>
				<div class="home_zoom">H</div>
			</div>
			<?php
			$width = "100%";
			$height = "500px";
			
			
			?>
			<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" pointer-events="all" width="<?php echo $width;?>" height="<?php echo $height;?>">
			<?php
			$this->embedded_symbols_for_firefox();
			?>
			</svg>
		</div>
		
		<?php
		$this->printJSInit();
	}
	
	private function embedded_symbols_for_firefox() {
		global $config;
		
		if ($this->is_buggy_firefox) {
			echo "<defs>";
			// Firefox BUG
			// https://bugzilla.mozilla.org/show_bug.cgi?id=1254159
			
			$this->is_buggy_firefox = true;
			
			$dir_string = $config['homedir'] . '/images/maps/';
			if ($dir = opendir($dir_string)) {
				
				while (false !== ($file = readdir($dir))) {
					if (is_file($dir_string . $file)) {
						$xml = simplexml_load_file($dir_string . $file);
						$xml->registerXPathNamespace("x", "http://www.w3.org/2000/svg");
						
						$symbols = $xml->xpath("//x:symbol");
						//~ $symbols = $symbols->xpath("//symbol");
						echo $symbols[0]->asXML();
					}
				}
				closedir($dir);
			}
			echo "</defs>";
		}
	}
	
	public function writeJSConstants() {
		$contants = array();
		$contants["MAP_REFRESH_TIME"] = MAP_REFRESH_TIME;
		
		$contants["MAP_SUBTYPE_GROUPS"] = MAP_SUBTYPE_GROUPS;
		$contants["MAP_SUBTYPE_POLICIES"] = MAP_SUBTYPE_POLICIES;
		
		$contants["ITEM_TYPE_AGENT_NETWORKMAP"] = ITEM_TYPE_AGENT_NETWORKMAP;
		$contants["ITEM_TYPE_MODULE_NETWORKMAP"] = ITEM_TYPE_MODULE_NETWORKMAP;
		$contants["ITEM_TYPE_EDGE_NETWORKMAP"] = ITEM_TYPE_EDGE_NETWORKMAP;
		$contants["ITEM_TYPE_FICTIONAL_NODE"] = ITEM_TYPE_FICTIONAL_NODE;
		$contants["ITEM_TYPE_MODULEGROUP_NETWORKMAP"] = ITEM_TYPE_MODULEGROUP_NETWORKMAP;
		$contants["ITEM_TYPE_GROUP_NETWORKMAP"] = ITEM_TYPE_GROUP_NETWORKMAP;
		$contants["ITEM_TYPE_POLICY_NETWORKMAP"] = ITEM_TYPE_POLICY_NETWORKMAP;
		
		$contants["AGENT_MODULE_STATUS_ALL"] = AGENT_MODULE_STATUS_ALL;
		$contants["AGENT_MODULE_STATUS_CRITICAL_BAD"] =  AGENT_MODULE_STATUS_CRITICAL_BAD;
		$contants["AGENT_MODULE_STATUS_CRITICAL_ALERT"] = AGENT_MODULE_STATUS_CRITICAL_ALERT;
		$contants["AGENT_MODULE_STATUS_NO_DATA"] = AGENT_MODULE_STATUS_NO_DATA;
		$contants["AGENT_MODULE_STATUS_NORMAL"] = AGENT_MODULE_STATUS_NORMAL;
		$contants["AGENT_MODULE_STATUS_NORMAL_ALERT"] = AGENT_MODULE_STATUS_NORMAL_ALERT;
		$contants["AGENT_MODULE_STATUS_NOT_NORMAL"] = AGENT_MODULE_STATUS_NOT_NORMAL;
		$contants["AGENT_MODULE_STATUS_WARNING"] = AGENT_MODULE_STATUS_WARNING;
		$contants["AGENT_MODULE_STATUS_WARNING_ALERT"] = AGENT_MODULE_STATUS_WARNING_ALERT;
		$contants["AGENT_MODULE_STATUS_UNKNOWN"] = AGENT_MODULE_STATUS_UNKNOWN;
		$contants["AGENT_MODULE_STATUS_NOT_INIT"] = AGENT_MODULE_STATUS_NOT_INIT;
		
		$contants["NODE_IMAGE_PADDING"] = NODE_IMAGE_PADDING;
		
		
		
		$contants["DEFAULT_NODE_WIDTH"] = DEFAULT_NODE_WIDTH;
		$contants["DEFAULT_NODE_HEIGHT"] = DEFAULT_NODE_HEIGHT;
		
		
		$contants["GENERIC_SIZE_TEXT"] = GENERIC_SIZE_TEXT;
		
		?>
		<script type="text/javascript">
			<?php
			foreach ($contants as $name => $value) {
				echo "var $name = $value \n";
			}
			echo "var is_buggy_firefox = " . ((int)$this->is_buggy_firefox) . ";\n";
			?>
		</script>
		<?php
	}
	
	public function getType() {
		return $this->type;
	}
	
	public function getSubtype() {
		return $this->subtype;
	}
}
?>
