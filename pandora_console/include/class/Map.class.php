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
	protected $id = null;
	
	protected $type = null;
	protected $subtype = null;
	
	protected $requires_js = null;
	
	public function __construct($id) {
		$this->id = $id;
		
		$this->loadDB();
		
		$this->requires_js = array();
		$this->requires_js[] = "include/javascript/d3.3.5.14.js";
		//~ $this->requires_js[] = "include/javascript/map/MapController.js";
	}
	
	private function loadDB() {
		$row = db_get_row_filter('tmap', array('id' => $this->id));
		
		$this->type = $row['type'];
		$this->subtype = $row['subtype'];
	}
	
	abstract function print_js_init();
	
	public function show() {
		
		foreach ($this->requires_js as $js) {
			echo "<script type='text/javascript' src='$js'></script>" . "\n";
		}
		
		?>
		
		<div id="map" data-id="<?php echo $this->id;?>" >
			<svg style="border: 2px solid red;" pointer-events="all" width="800" height="800"><g><circle id="node_10" class="node" cx="100" cy="100" style="fill: rgb(128, 186, 39);" r="5">
			</svg>
		</div>
		
		<?php
		$this->print_js_init();
	}
}
?>
