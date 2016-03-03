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
		html_debug_print($this->nodes, true);
		html_debug_print($this->edges, true);
		html_debug_print(json_encode($this->nodes), true);
		html_debug_print("", true);
		html_debug_print(json_encode($this->edges), true);
		
$this->nodes = json_decode('[
{
	"graph_id": "165",
	"id": "17",
	"type": 0,
	"x": 1033.4,
	"y": 806.38
},
{
	"graph_id": "166",
	"id": "198",
	"type": 1,
	"x": 1119.5,
	"y": 847.85
},
{
	"graph_id": 208,
	"type": 2
},
{
	"graph_id": "169",
	"id": "207",
	"type": 1,
	"x": 947.33,
	"y": 764.91
},
{
	"graph_id": 209,
	"type": 2
},
{
	"graph_id": "179",
	"id": "27",
	"type": 0,
	"x": 159.23,
	"y": 1005.9
},
{
	"graph_id": "180",
	"id": "223",
	"type": 1,
	"x": 218.82,
	"y": 931.19
},
{
	"graph_id": 210,
	"type": 2
},
{
	"graph_id": "183",
	"id": "89",
	"type": 0,
	"x": 516.77,
	"y": 557.57
},
{
	"graph_id": "184",
	"id": "418",
	"type": 1,
	"x": 430.66,
	"y": 599.03
},
{
	"graph_id": 211,
	"type": 2
},
{
	"graph_id": "196",
	"id": "412",
	"type": 1,
	"x": 602.88,
	"y": 516.1
},
{
	"graph_id": 212,
	"type": 2
}
]', true);

//~ $this->edges = json_decode('[{"to":"165","from":"166","graph_id":208},{"to":"165","from":"169","graph_id":209},{"to":"179","from":"180","graph_id":210},{"to":"183","from":"184","graph_id":211},{"to":"183","from":"196","graph_id":212}]', true);
$this->edges = json_decode('[{"to":"165","from":"166","graph_id":208}, {"to":"165","from":"169","graph_id":209}]', true);
//~ $this->edges = json_decode('[{"to":"165","from":"166","graph_id":208}]', true);
		
		
		?>
		<script type="text/javascript">
			var controller_map = null;
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
		//Tooltips spinner
		echo "<div id='spinner_tooltip' style='display:none;'>";
			html_print_image('images/spinner.gif');
		echo "</div>";
		foreach ($this->requires_js as $js) {
			echo "<script type='text/javascript' src='$js'></script>" . "\n";
		}
		
		$this->writeJSContants();
		$this->writeJSGraph();
		
		?>
		<style type="text/css">
			.title_bar {
				border-bottom: 1px solid black;
			}
			.title_bar .title {
				font-weight: bolder;
			}
			
			.title_bar .open_click {
				float: right;
				display: table-cell;
				font-weight: bolder;
				font-size: 20px;
				background: blue none repeat scroll 0% 0%;
				color: white;
				width: 17px;
				height: 17px;
				cursor: pointer;
				text-align: center;
				vertical-align: middle;
				margin: 1px;
			}
			
			.title_bar .close_click {
				float: right;
				display: table-cell;
				font-weight: bolder;
				font-size: 20px;
				background: blue none repeat scroll 0% 0%;
				color: white;
				width: 17px;
				height: 17px;
				cursor: pointer;
				text-align: center;
				vertical-align: middle;
				margin: 1px;
			}
		</style>
		<div id="map" data-id="<?php echo $this->id;?>" style="border: 1px red solid;">
			<div class="zoom_box" style="position: absolute;">
				<style type="text/css">
					.zoom_controller {
						width: 30px;
						height: 210px;
						background: blue;
						border-radius: 15px;
						
						top: 50px;
						left: 10px;
						position: absolute;
					}
					
					
					
					.vertical_range {
						padding: 0;
						-webkit-transform: rotate(270deg);
						   -moz-transform: rotate(270deg);
						        transform: rotate(270deg);
						width: 200px;
						height: 20px;
						position: relative;
						background: transparent !important;
						border: 0px !important;
					}
					
					.vertical_range {
						left: -92px;
						top: 93px;
					}
					
					@media screen and (-webkit-min-device-pixel-ratio:0)
					{
						/* Only for chrome */
						
						.vertical_range {
							left: -87px;
							top: 93px;
						}
					}
					
					.home_zoom {
						top: 310px;
						left: 10px;
						
						display: table-cell;
						position: absolute;
						font-weight: bolder;
						font-size: 20px;
						background: blue;
						color: white;
						border-radius: 15px;
						width: 30px;
						height: 30px;
						cursor:pointer;
						text-align: center;
						vertical-align: middle;
					}
					
					.zoom_in {
						top: 10px;
						left: 10px;
						
						display: table-cell;
						position: absolute;
						font-weight: bolder;
						font-size: 20px;
						background: blue;
						color: white;
						border-radius: 15px;
						width: 30px;
						height: 30px;
						cursor:pointer;
						text-align: center;
						vertical-align: middle;
					}
					
					.zoom_out {
						top: 270px;
						left: 10px;
						
						display: table-cell;
						position: absolute;
						font-weight: bolder;
						font-size: 20px;
						background: blue;
						color: white;
						border-radius: 15px;
						width: 30px;
						height: 30px;
						cursor:pointer;
						text-align: center;
						vertical-align: middle;
					}
				</style>
				<div class="zoom_controller">
					<input class="vertical_range" type="range" name="range" min="-666" max="666" step="1" value="666" />
				</div>
				<div class="zoom_in">+</div>
				<div class="zoom_out">-</div>
				<div class="home_zoom">H</div>
			</div>
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
