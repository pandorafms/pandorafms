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
	
	protected $nodes = null;
	protected $edges = null;
	
	protected $requires_js = null;
	
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
		
		<div id="map" data-id="<?php echo $this->id;?>" >
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
			<svg style="border: 2px solid red;" pointer-events="all" width="<?php echo $width;?>" height="<?php echo $height;?>">
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
		
<!--
		
		
		<style>

		svg {
		  font: 10px sans-serif;
		  shape-rendering: crispEdges;
		}

		rect {
		  fill: #ddd;
		}

		.axis path,
		.axis line {
		  fill: none;
		  stroke: #fff;
		}

		</style>
		
		
		<div id="test"></div>
		<script>
			
			var margin = {top: 20, right: 20, bottom: 30, left: 40},
				width = 960 - margin.left - margin.right,
				height = 500 - margin.top - margin.bottom;

			var x = d3.scale.linear()
				.domain([-width / 2, width / 2])
				.range([0, width]);

			var y = d3.scale.linear()
				.domain([-height / 2, height / 2])
				.range([height, 0]);

			var xAxis = d3.svg.axis()
				.scale(x)
				.orient("bottom")
				.tickSize(-height);

			var yAxis = d3.svg.axis()
				.scale(y)
				.orient("left")
				.ticks(5)
				.tickSize(-width);

			var zoom = d3.behavior.zoom()
				.x(x)
				.y(y)
				.scaleExtent([1, 32])
				.on("zoom", zoomed);

			var svg = d3.select("#test").append("svg")
				.attr("width", width + margin.left + margin.right)
				.attr("height", height + margin.top + margin.bottom)
			  .append("g")
				.attr("transform", "translate(" + margin.left + "," + margin.top + ")")
				.call(zoom);

			svg.append("rect")
				.attr("width", width)
				.attr("height", height);

			svg.append("g")
				.attr("class", "x axis")
				.attr("transform", "translate(0," + height + ")")
				.call(xAxis);

			svg.append("g")
				.attr("class", "y axis")
				.call(yAxis);
			
			svg.append("g").append("circle")
		.attr("id", "node_10")
		.attr("class", "node")
		.attr("cx", "20")
		.attr("cy", "20")
		.attr("style", "fill: rgb(128, 186, 39);")
		.attr("r", "5");

			function zoomed() {
			  svg.select(".x.axis").call(xAxis);
			  svg.select(".y.axis").call(yAxis);
			}

		</script>
-->
		<?php
	}
	
	public function getType() {
		return $this->type;
	}
}
?>
