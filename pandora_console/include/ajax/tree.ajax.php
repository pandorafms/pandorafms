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

require_once("include/class/tree.class.php");

$get_data = (bool)get_parameter('get_data', 0);

if ($get_data) {
	$tab = get_parameter('type', 'group');
	$search = get_parameter('search', '');
	$status = (int)get_parameter('status', AGENT_STATUS_ALL);
	$root = (int)get_parameter('root', 0);
	
	$tree = new Tree($tab);
	$tree->set_filter(array(
		'status' => $status,
		'search' => $search));
	echo $tree->get_json();
	return;
}
?>