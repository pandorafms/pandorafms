<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Login check
global $config;

check_login ();

// Fix: IW was the old ACL to check for report editing, now is RW
if (! check_acl ($config['id_user'], 0, "VR")) {
	db_pandora_audit("ACL Violation",
		"Trying to access report builder");
	require ("general/noaccess.php");
	exit;
}


//Fix ajax to avoid include the file, 'functions_graph.php'.
$ajax = true;


require_once('include/functions_visual_map.php');
enterprise_include_once('include/functions_visual_map.php');

$id_visual_console = get_parameter('id_visual_console', null);

$render_map = (bool)get_parameter('render_map', false);
$graph_javascript = (bool)get_parameter('graph_javascript', false);

if ($render_map) {
	$width = (int)get_parameter('width', '400');
	$height = (int)get_parameter('height', '400');
	$keep_aspect_ratio = (bool) get_parameter('keep_aspect_ratio');
	
	visual_map_print_visual_map($id_visual_console, true, true, $width,
		$height, '', false, $graph_javascript, $keep_aspect_ratio);
	return;
}

?>
