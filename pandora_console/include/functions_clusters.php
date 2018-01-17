<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

require_once($config['homedir'] . '/include/functions.php');

function clusters_get_name ($id_cluster, $case = 'none') {
	$name = (string) db_get_value ('name', 'tcluster', 'id', (int) $id_cluster);
	
	switch ($case) {
		case 'upper':
			return mb_strtoupper($name, 'UTF-8');
		case 'lower':
			return mb_strtolower($name, 'UTF-8');
		case 'none':
		default:
			return ($name);
	}
}

function agents_get_cluster_agents ($id_cluster){
  $agents = db_get_all_rows_filter("tcluster_agent", array("id_cluster" => $id_cluster), "id_agent");
  return ($agents);
}

?>