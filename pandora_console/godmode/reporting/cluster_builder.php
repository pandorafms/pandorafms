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
require_once ('include/functions_modules.php');
require_once('include/functions_clusters.php');

$step = get_parameter('step',0);
$id_cluster = get_parameter('id_cluster',0);
$delete_cluster = get_parameter('delete_cluster',0);

if($step == 1){
	
$add_cluster = (bool) get_parameter('add_cluster', false);
$update_cluster = (bool) get_parameter('update_cluster', false);

if ($add_cluster) {
	$name = get_parameter_post ("name");
  $cluster_type = get_parameter_post ('cluster_type');
	$description = get_parameter_post ("description");
	$idGroup = get_parameter_post ('cluster_id_group');

  // Create agent
  
	$server_name = db_process_sql('select name from tserver where server_type=5 limit 1');	
	
	$server_name_agent = $server_name[0]['name'];
		
  $values_agent = array(
		'nombre' => $name,
    'alias' => $name,
		'comentarios' => $description,
		'id_grupo' => $idGroup,
		'id_os' => 21,
		'server_name' => $server_name_agent
		);
	
	if (trim($name) != "") {
		
    // $id_agent = db_process_sql_insert('tagente',$values_agent);
		
		$id_agent = agents_create_agent($values_agent['nombre'],$values_agent['id_grupo'],300,'127.0.0.1',$values_agent);
		
		// Create cluster
		$values_cluster = array(
			'name' => $name,
	    'cluster_type' => $cluster_type,
			'description' => $description,
			'group' => $idGroup,
			'id_agent' => $id_agent
			);
			
		$id_cluster = db_process_sql_insert('tcluster', $values_cluster);
		
		
		$values_module = array(
			'nombre' => 'Cluster status',
			'id_modulo' => 5,
			'prediction_module' => 5,
			'id_agente' =>$id_agent,
			'custom_integer_1' =>$id_cluster,
			'id_tipo_modulo' => 3,
			'descripcion' => 'Cluster status information module',
			'min_warning' => 1,
			'min_critical' =>	2
			);
			
		$id_module = 	modules_create_agent_module($values_module['id_agente'],$values_module['nombre'],$values_module);
			
		// $id_module = db_process_sql_insert('tagente_modulo', $values_module);
		
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

ui_print_page_header (__('Cluster')." &raquo; ".__('New'), "images/chart.png", false, "", false, $buttons);

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
	
	ui_print_page_header (__('Cluster')." &raquo; ".__('New'), "images/chart.png", false, "", false, $buttons);
	
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
									
			// $tcluster_module = db_process_sql('insert into tcluster_item (name,id_cluster,critical_limit,warning_limit) values ("'.$value.'",'.$id_cluster.',100,0)');
			
			$tcluster_module = db_process_sql_insert('tcluster_item',array('name'=>$value,'id_cluster'=>$id_cluster,'critical_limit'=>100,'warning_limit'=>0));
			
			$id_agent = db_process_sql('select id_agent from tcluster where id = '.$id_cluster);
			
			$id_parent_modulo = db_process_sql('select id_agente_modulo from tagente_modulo where id_agente = '.$id_agent[0]['id_agent'].' and nombre = "Cluster status"');
			
			$get_module_type = db_process_sql('select id_tipo_modulo,descripcion,min_warning,min_critical from tagente_modulo where nombre = "'.$value.'" limit 1');
			
			$get_module_type_value = $get_module_type[0]['id_tipo_modulo'];
			
			$get_module_description_value = $get_module_type[0]['descripcion'];
			
			$get_module_warning_value = $get_module_type[0]['min_warning'];
			
			$get_module_critical_value = $get_module_type[0]['min_critical'];
			
			$values_module = array(
				'nombre' => $value,
				'id_modulo' => 5,
				'prediction_module' => 6,
				'id_agente' => $id_agent[0]['id_agent'],
				'parent_module_id' => $id_parent_modulo[0]['id_agente_modulo'],
				'custom_integer_1' =>$id_cluster,
				'custom_integer_2' =>$tcluster_module,
				'id_tipo_modulo' =>$get_module_type_value,
				'descripcion' => $get_module_description_value,
				'min_warning' => $get_module_warning_value,
				'min_critical' => $get_module_critical_value
				);
				
				
			$id_module = 	modules_create_agent_module($values_module['id_agente'],$values_module['nombre'],$values_module);
			
			// $id_module = db_process_sql_insert('tagente_modulo', $values_module);
						
			if ($tcluster_module !== false){	
				db_pandora_audit("Report management", "Module #$value assigned to cluster #$id_cluster");
			}
			else{
				db_pandora_audit("Report management", "Fail try to assign module to cluster");
			}
				
		}
		
		foreach ($modules_preasigned as $key => $value) {
						
			if(!in_array($value['name'],$cluster_modules)){
				
				$tcluster_agent_module_delete_id = db_process_sql('select id_agente_modulo from tagente_modulo where nombre = "'.$value['name'].'" and custom_integer_1 = '.$id_cluster.' and prediction_module = 6');
				
				$tcluster_agent_module_delete_id_value = $tcluster_agent_module_delete_id[0]['id_agente_modulo'];
				
				$tcluster_agent_module_delete_result = modules_delete_agent_module($tcluster_agent_module_delete_id_value);
				
				$tcluster_module_delete = db_process_sql('delete from tcluster_item where name = "'.$value['name'].'" and id_cluster = '.$id_cluster.' and item_type = "AA"');
				
			}
			
		}
		
		header ("Location: index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&step=4&id_cluster=".$id_cluster);
		}
		
		ui_print_page_header (__('Cluster')." &raquo; ".__('New'), "images/chart.png", false, "", false, $buttons);
		
	}
	elseif ($step == 4) {
		
		$cluster_items = items_get_cluster_items_id($id_cluster);
		$assign_limits = get_parameter('assign_limits',0);
		
		if($assign_limits == 1){
		
		foreach ($cluster_items as $key => $value) {
			$critical_values[$value] = get_parameter('critical_item_'.$value,0);
			$warning_values[$value] = get_parameter('warning_item_'.$value,0);
		}
		
		// $get_module_id_limit = db_process_sql('update tagente_modulo set min_critical = '.$value.' where nombre = (select name from tcluster_item where id = '.$key.') and cutom_integer_1 = '.$id_cluster);
						
				foreach ($critical_values as $key => $value) {
					$titem_critical_limit = db_process_sql('update tcluster_item set critical_limit = '.$value.' where id = '.$key);
					
					$get_module_critical_limit = db_process_sql('update tagente_modulo set min_critical = '.$value.' where nombre = (select name from tcluster_item where id = '.$key.') and custom_integer_1 = '.$id_cluster);
					
					if ($titem_critical_limit !== false){	
						db_pandora_audit("Report management", "Critical limit #$value assigned to item #$key");
					}
					else{
						db_pandora_audit("Report management", "Fail try to assign critical limit to item #$key");
					}
						
				}
				
				foreach ($warning_values as $key => $value) {
				
					$titem_warning_limit = db_process_sql('update tcluster_item set warning_limit = '.$value.' where id = '.$key);
					
					$get_module_warning_limit = db_process_sql('update tagente_modulo set min_warning = '.$value.' where nombre = (select name from tcluster_item where id = '.$key.') and custom_integer_1 = '.$id_cluster);
							
							
						
							
					if ($titem_warning_limit !== false){	
						db_pandora_audit("Report management", "Critical limit #$value assigned to item #$key");
					}
					else{
						db_pandora_audit("Report management", "Fail try to assign warning limit to item #$key");
					}
								
				}
				
				$cluster_type = clusters_get_cluster_id_type($id_cluster);
								
				if($cluster_type[$id_cluster] == 'AP'){
					header ("Location: index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&step=5&id_cluster=".$id_cluster);	
				}
				elseif ($cluster_type[$id_cluster] == 'AA') {
					header ("Location: index.php?sec=reporting&sec2=godmode/reporting/cluster_view&id=".$id_cluster);		
				}
				
			}
			
			ui_print_page_header (__('Cluster')." &raquo; ".clusters_get_name($id_cluster), "images/chart.png", false, "", false, $buttons);
				
	}
	elseif ($step == 5) {
		
		$assign_balanced_modules = get_parameter('assign_balanced_modules',0);
		$balanced_modules = get_parameter('name_modules2',null); //abajao en assign
		
		if($assign_balanced_modules){
			
			$balanced_modules_preasigned = db_get_all_rows_sql('select id,name,item_type from tcluster_item where id_cluster ='.$id_cluster.' and item_type = "AP"');
			
			foreach ($balanced_modules as $key => $value) {
				
				$tcluster_balanced_module_duplicate_check = db_get_all_rows_sql('select name,id_cluster,item_type from tcluster_item where name = "'.$value.'" and id_cluster = '.$id_cluster.' and item_type = "AP"');
				
				if($tcluster_balanced_module_duplicate_check){
					continue;
				}
				
				$id_agent = db_process_sql('select id_agent from tcluster where id = '.$id_cluster);
				
				$id_parent_modulo = db_process_sql('select id_agente_modulo from tagente_modulo where id_agente = '.$id_agent[0]['id_agent'].' and nombre = "Cluster status"');
				
				$tcluster_balanced_module = db_process_sql_insert('tcluster_item',array('name'=>$value,'id_cluster'=>$id_cluster,'item_type'=>"AP"));
				
				$get_module_type = db_process_sql('select id_tipo_modulo,descripcion,min_warning,min_critical from tagente_modulo where nombre = "'.$value.'" limit 1');
				
				$get_module_type_value = $get_module_type[0]['id_tipo_modulo'];
				
				$get_module_description_value = $get_module_type[0]['descripcion'];
				
				$get_module_warning_value = $get_module_type[0]['min_warning'];
				
				$get_module_critical_value = $get_module_type[0]['min_critical'];
				
				$values_module = array(
					'nombre' => $value,
					'id_modulo' => 5,
					'prediction_module' => 7,
					'id_agente' => $id_agent[0]['id_agent'],
					'parent_module_id' => $id_parent_modulo[0]['id_agente_modulo'],
					'custom_integer_1' => $id_cluster,
					'custom_integer_2' => $tcluster_balanced_module,
					'id_tipo_modulo' => $get_module_type_value,
					'descripcion' => $get_module_description_value,
					'min_warning' => $get_module_warning_value,
					'min_critical' => $get_module_critical_value
					);
					
				// $id_module = db_process_sql_insert('tagente_modulo', $values_module);
				
				$id_module = 	modules_create_agent_module($values_module['id_agente'],$values_module['nombre'],$values_module);
										
				if ($tcluster_balanced_module !== false){	
					db_pandora_audit("Report management", "Module #$value assigned to cluster #$id_cluster");
				}
				else{
					db_pandora_audit("Report management", "Fail try to assign module to cluster");
				}
					
			}
			
			foreach ($balanced_modules_preasigned as $key => $value) {
							
				if(!in_array($value['name'],$balanced_modules)){
					
					$tcluster_agent_module_delete_id = db_process_sql('select id_agente_modulo from tagente_modulo where nombre = "'.$value['name'].'" and custom_integer_1 = '.$id_cluster.' and prediction_module = 7');
					
					$tcluster_agent_module_delete_id_value = $tcluster_agent_module_delete_id[0]['id_agente_modulo'];
					
					$tcluster_agent_module_delete_result = modules_delete_agent_module($tcluster_agent_module_delete_id_value);
					
					$tcluster_balanced_module_delete = db_process_sql('delete from tcluster_item where name = "'.$value['name'].'" and id_cluster = '.$id_cluster.' and item_type = "AP"');
				}
				
			}
			
			header ("Location: index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&step=6&id_cluster=".$id_cluster);
			}
		
		ui_print_page_header (__('Cluster')." &raquo; ".clusters_get_name($id_cluster), "images/chart.png", false, "", false, $buttons);
		
	}
	elseif ($step == 6) {
		
		$cluster_items = items_get_cluster_items_id($id_cluster,'AP');
		$assign_critical = get_parameter('assign_critical',0);
		
		foreach ($cluster_items as $key => $value) {
			
			$is_critical_values[$value] = get_parameter('is_critical_item_'.$value,0);
		}
		
		if($assign_critical == 1){
			
				foreach ($is_critical_values as $key => $value) {
										
				$titem_is_critical = db_process_sql('update tcluster_item set is_critical = '.$value.' where id = '.$key);
				
					if ($titem_is_critical !== false){	
						db_pandora_audit("Report management", "Module #$key critical mode is now $value");
					}
					else{
						db_pandora_audit("Report management", "Fail try to assign critical mode to item #$key");
					}
						
				}
				
			header ("Location: index.php?sec=reporting&sec2=godmode/reporting/cluster_view&id=".$id_cluster);	
				
			}
			
			ui_print_page_header (__('Cluster')." &raquo; ".__('New'), "images/chart.png", false, "", false, $buttons);
			
	}
	elseif ($delete_cluster){
	
		$temp_id_cluster = db_process_sql('select id_agent from tcluster where id ='.$delete_cluster);
		
		$tcluster_modules_delete = db_process_sql('delete from tagente_modulo where custom_integer_1 = '.$delete_cluster);
		
		$tcluster_items_delete = db_process_sql('delete from tcluster_item where id_cluster = '.$delete_cluster);
		
		$tcluster_agents_delete = db_process_sql('delete from tcluster_agent where id_cluster = '.$delete_cluster);
		
		$tcluster_delete = db_process_sql('delete from tcluster where id = '.$delete_cluster);
		
		$tcluster_agent_delete = db_process_sql('delete from tagente where id_agente = '.$temp_id_cluster[0]['id_agent']);
		
		header ("Location: index.php?sec=reporting&sec2=enterprise/operation/cluster/cluster");	
	}

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
