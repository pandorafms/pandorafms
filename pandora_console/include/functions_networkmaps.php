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
 * @subpackage Networkmaps
 */

function networkmaps_show($id) {
	$networkmap_items = db_get_all_rows_sql("SELECT * FROM titem WHERE id_map = " . $id);
	$networkmap = db_get_all_rows_sql("SELECT * FROM tmap WHERE id = " . $id);

	if ($networkmap === false) {
		ui_print_error_message(__('Not found networkmap'));
	}
	else {
		
	}
}
?>