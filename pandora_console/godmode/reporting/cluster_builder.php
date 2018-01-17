<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
global $config;

check_login ();

if (! check_acl ($config['id_user'], 0, "RW") && ! check_acl ($config['id_user'], 0, "RM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access graph builder");
	include ("general/noaccess.php");
	exit;
}

require_once ('include/functions_agents.php');
require_once('include/functions_clusters.php');

$step = get_parameter('step',1);
$id_cluster = get_parameter('id_cluster',0);

if($step == 1){

$add_cluster = (bool) get_parameter('add_cluster', false);
$update_graph = (bool) get_parameter('update_cluster', false);
$id_cluster = (int) get_parameter('id', 0);

if ($add_cluster) {
	$name = get_parameter_post ("name");
  $cluster_type = get_parameter_post ('cluster_type');
	$description = get_parameter_post ("description");
	$idGroup = get_parameter_post ('cluster_id_group');

	// Create cluster
	$values_cluster = array(
		'name' => $name,
    'cluster_type' => $cluster_type,
		'description' => $description,
		'group' => $idGroup,
		);
    
  // Create agent
    
  $values_agent = array(
		'nombre' => hash("sha256",$name . "|" ."127.0.0.1" ."|". time() ."|". sprintf("%04d", rand(0,10000))),
    // 'nombre' => $name,
    'alias' => $name,
		'comentarios' => $description,
		'id_grupo' => $idGroup
		);
	
	if (trim($name) != "") {
		$id_cluster = db_process_sql_insert('tcluster', $values_cluster);
    
    $id_agent = db_process_sql_insert('tagente',$values_agent);
    
		if ($id_cluster !== false)
			db_pandora_audit("Report management", "Create cluster #$id_cluster");
		else
			db_pandora_audit("Report management", "Fail try to create cluster");
      
    if ($id_agent !== false)
			db_pandora_audit("Report management", "Create cluster #$id_agent");
		else
			db_pandora_audit("Report management", "Fail try to create agent");
      
      header ("Location: index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&step=2&id_cluster=".$id_cluster);
      
	}
	else {
		$id_cluster = false;
	}
	
	if(!$id_cluster)
		$edit_cluster = false;
}

}

ui_print_page_header (__('Cluster')." &raquo; ".__('New'), "images/chart.png", false, "", false, $buttons);


$active_tab = get_parameter('tab', 'main');




switch ($active_tab) {
	case 'main':
    require_once('godmode/reporting/cluster_builder.main.php');  
    break;
	case 'cluster_editor':
		require_once('godmode/reporting/cluster_builder.cluster_editor.php');
		break;
}
?>
