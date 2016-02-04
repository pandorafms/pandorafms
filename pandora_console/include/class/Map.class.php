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

class Map {
	protected $id = null;
	
	public function __construct($id) {
		$this->id = $id;
	}
	
	public function show() {
		?>
		<div class="map">
			<svg width="800" height="800" pointer-events="all" style="border: 2px solid red;">
				<g>
					<circle id="node_10" class="node" r="5" style="fill: rgb(128, 186, 39);" cx="100" cy="100"/>
				</g>
			</svg>
		</div>
		<?php
	}
}
?>
