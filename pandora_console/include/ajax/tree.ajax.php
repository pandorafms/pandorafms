<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2012 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

require_once("include/class/Tree.class.php");

$getChildren = (bool)get_parameter('getChildren', 0);

if ($getChildren) {
	$type = get_parameter('type', 'group');
	$filter = get_parameter('filter',
		array('search' => '',
			'status' => AGENT_STATUS_ALL));
	$id = (int)get_parameter('id', 0);
	$method = get_parameter('method', 'on_demand');
	
	$tree = new Tree($type, $method, $id);
	$tree->setFilter(array(
		'status' => $filter['status'],
		'search' => $filter['search']));
	echo json_encode(array('success' => 1, 'tree' => $tree->getArray()));
	return;
}
?>