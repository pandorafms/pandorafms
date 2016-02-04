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
	public function __construct($id) {
		parent::__construct($id);
		
		//~ $this->requires_js[] = "include/javascript/map/NetworkMapController.js";
	}
	
	public function show() {
		parent::show();
	}
	
	public function print_js_init() {
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