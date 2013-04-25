<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $result;

$table->width = "98%";
$table->class = "databox";

$table->head[0] = __('Description');
$table->head[1] = __('User');
$table->head[2] = __('Date');

$table->data = array();

if (isset($result['tracking'][0]) && is_array($result['tracking'][0])) {
	$tracking = $result['tracking'];
}
else {
	$tracking = $result;
}

$row = 0;
foreach ($tracking as $value) {
	
	$table->data[$row][0] = $value['description'];
	$table->data[$row][1] = $value['id_user'];
	$table->data[$row][2] = $value['timestamp'];
	$row++;
}

html_print_table($table);

?>
