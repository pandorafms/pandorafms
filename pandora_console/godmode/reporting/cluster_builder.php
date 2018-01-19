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
$update_cluster = (bool) get_parameter('update_cluster', false);

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
elseif ($step == 2) {
	
	$assign_agents = get_parameter('assign_agents',0);
	$cluster_agents = get_parameter('id_agents2',null); //abajao en assign
	
	if($assign_agents){
		
		$values_cluster_agents = array(
			'id_cluster' => $id_cluster,
			'id_agent' => $cluster_agents
			);
			
		$agents_preasigned = db_get_all_rows_sql('select id_agent from tcluster_agent where id_cluster ='.$id_cluster);
		
		foreach ($values_cluster_agents['id_agent'] as $key => $value) {
						
			$tcluster_agent = db_process_sql('insert into tcluster_agent values ('.$id_cluster.','.$value.')');
			
			if ($tcluster_agent !== false){	
				db_pandora_audit("Report management", "Agent #$value assigned to cluster #$id_cluster");
			}
			else{
				db_pandora_audit("Report management", "Fail try to assign agent to cluster");
			}
				
		}
		
		foreach ($agents_preasigned as $key => $value) {
			
			if(!in_array($value['id_agent'],$values_cluster_agents['id_agent'])){
				$tcluster_agent_delete = db_process_sql('delete from tcluster_agent where id_agent = '.$value['id_agent'].' and id_cluster = '.$id_cluster);
			}
			
		}
		
		header ("Location: index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&step=3&id_cluster=".$id_cluster);
	}
	
	
}
elseif ($step == 3) {
	
	$assign_modules = get_parameter('assign_modules',0);
	$cluster_modules = get_parameter('name_modules2',null); //abajao en assign
	
	if($assign_modules){
		
		$modules_preasigned = db_get_all_rows_sql('select id,name from tcluster_item where id_cluster ='.$id_cluster);
		
		foreach ($cluster_modules as $key => $value) {
			
			$tcluster_module_duplicate_check = db_get_all_rows_sql('select name,id_cluster from tcluster_item where name = "'.$value.'" and id_cluster = '.$id_cluster);
			
			if($tcluster_module_duplicate_check){
				continue;
			}
									
			$tcluster_module = db_process_sql('insert into tcluster_item (name,id_cluster) values ("'.$value.'",'.$id_cluster.')');
			
			if ($tcluster_module !== false){	
				db_pandora_audit("Report management", "Module #$value assigned to cluster #$id_cluster");
			}
			else{
				db_pandora_audit("Report management", "Fail try to assign module to cluster");
			}
				
		}
		
		foreach ($modules_preasigned as $key => $value) {
						
			if(!in_array($value['name'],$cluster_modules)){
				$tcluster_module_delete = db_process_sql('delete from tcluster_item where name = "'.$value['name'].'" and id_cluster = '.$id_cluster);
			}
			
		}
		
		header ("Location: index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&step=4&id_cluster=".$id_cluster);
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
