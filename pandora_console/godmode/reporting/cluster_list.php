<?php
// ______                 __                     _______ _______ _______
//|   __ \.---.-.-----.--|  |.-----.----.---.-. |    ___|   |   |     __|
//|    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
//|___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
//
// ============================================================================
// Copyright (c) 2007-2017 Artica Soluciones Tecnologicas, http://www.artica.es
// This code is NOT free software. This code is NOT licenced under GPL2 licence
// No he usado un cluster en mi vida huliooo.
// You cannnot redistribute it without written permission of copyright holder.
// ================================

global $config;

check_login ();

if (! check_acl ($config['id_user'], 0, "AR")) {
	db_pandora_audit("ACL Violation", "Trying to access agent main list view");
	require ("general/noaccess.php");
	
	return;
}

ui_pagination (count($clusters));

// $graphs = custom_graphs_get_user ($config['id_user'], false, true, $access);
// $offset = (int) get_parameter ("offset");

// ui_pagination (count($graphs));

  $table = new stdClass();
  $table->width = '100%';
  $table->class = 'databox data';
  $table->align = array ();
  $table->head = array ();
  $table->head[0] = __('Cluster name') . ' ' . 
	'<a href="index.php?sec=estado&sec2=enterprise/operation/cluster/cluster&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=name&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectNameUp, "alt" => "up"))  . '</a>' .
	'<a href="index.php?sec=estado&sec2=enterprise/operation/cluster/cluster&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=name&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectNameDown, "alt" => "down")) . '</a>';
  $table->head[1] = __('Description') . ' ' . 
	'<a href="index.php?sec=estado&sec2=enterprise/operation/cluster/cluster&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=description&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectNameUp, "alt" => "up"))  . '</a>' .
	'<a href="index.php?sec=estado&sec2=enterprise/operation/cluster/cluster&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=description&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectNameDown, "alt" => "down")) . '</a>';
  
	$table->head[2] = __('Group') . ' ' . 
	'<a href="index.php?sec=estado&sec2=enterprise/operation/cluster/cluster&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=group&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectNameUp, "alt" => "up"))  . '</a>' .
	'<a href="index.php?sec=estado&sec2=enterprise/operation/cluster/cluster&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=group&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectNameDown, "alt" => "down")) . '</a>';
	
	$table->head[3] = __('Type') . ' ' . 
	'<a href="index.php?sec=estado&sec2=enterprise/operation/cluster/cluster&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=type&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectNameUp, "alt" => "up"))  . '</a>' .
	'<a href="index.php?sec=estado&sec2=enterprise/operation/cluster/cluster&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=type&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectNameDown, "alt" => "down")) . '</a>';
	
	
  $table->head[4] = __('Nodes') . ' ' . 
	'<a href="index.php?sec=estado&sec2=enterprise/operation/cluster/cluster&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=name&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectNameUp, "alt" => "up"))  . '</a>' .
	'<a href="index.php?sec=estado&sec2=enterprise/operation/cluster/cluster&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=name&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectNameDown, "alt" => "down")) . '</a>';
  
	$table->head[5] = __('Status') . ' ' . 
	'<a href="index.php?sec=estado&sec2=enterprise/operation/cluster/cluster&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=name&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectNameUp, "alt" => "up"))  . '</a>' .
	'<a href="index.php?sec=estado&sec2=enterprise/operation/cluster/cluster&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=name&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectNameDown, "alt" => "down")) . '</a>';
  
  $table->head[6] = __('Actions') . ' ' . 
	'<a href="index.php?sec=estado&sec2=enterprise/operation/cluster/cluster&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=actions&amp;sort=up">' . html_print_image("images/sort_up.png", true, array("style" => $selectNameUp, "alt" => "up"))  . '</a>' .
	'<a href="index.php?sec=estado&sec2=enterprise/operation/cluster/cluster&amp;offset=' . $offset . '&amp;group_id=' . $group_id . '&amp;recursion=' . $recursion . '&amp;search=' . $search . '&amp;status='. $status . '&amp;sort_field=action&amp;sort=down">' . html_print_image("images/sort_down.png", true, array("style" => $selectNameDown, "alt" => "down")) . '</a>';
  
	$table->size[0] = '25%';
  $table->size[1] = '25%';
  $table->size[2] = '10%';
  $table->size[3] = '10%';
  $table->size[4] = '15%';
  $table->size[5] = '10%';
  $table->size[6] = '5%';
  $table->align[2] = 'left';
  $table->align[3] = 'left';
  
  $table->data = array ();
  
  foreach ($clusters as $cluster) {
    $data = array ();
    
    $data[0] = '<a href="index.php?sec=reporting&sec2=godmode/reporting/cluster_view&id='.$cluster["id"].'">'.$cluster["name"].'</a>';
    $data[1] = ui_print_truncate_text($cluster["description"], 70);
		
		$data[2] = ui_print_group_icon($cluster['group'],true);
		
    $data[3] = $cluster["cluster_type"];
    
    $nodes_cluster = db_process_sql('select count(*) as number from tcluster_agent where id_cluster = '.$cluster['id']);    
      
    $data[4] = $nodes_cluster[0]['number'];
		
		//agent status - open
		
		$cluster_agent = db_process_sql('select id_agente from tagente where id_agente = (select id_agent from tcluster where id = '.$cluster['id'].')');
		
		$cluster_agent_status = agents_get_status($cluster_agent[0]['id_agente']);
		
		//agent status - close
		
		
		//cluster module status - open
		
		$cluster_module = db_process_sql('select id_agente_modulo from tagente_modulo where id_agente = (select id_agent from tcluster where id = '.$cluster['id'].') and nombre = "Cluster status"');
		
		$cluster_module_status = modules_get_agentmodule_last_status($cluster_module[0]['id_agente_modulo']);
		
		//cluster module status - close
		
		switch ($cluster_module_status) {
			case 1:
			
				$data[5] = '<div title="'.__('Critical').'" style="width:35px;height:20px;background-color:red;"></div>';
	    
				break;
			case 2:
			
				$data[5] = '<div title="'.__('Warning').'" style="width:35px;height:20px;background-color:yellow;"></div>';
	    
				break;
			case 3:
			
				$data[5] = '<div title="'.__('Unknown').'" style="width:35px;height:20px;background-color:gray;"></div>';
			
				break;
			case 4:
			
				$data[5] = '<div title="'.__('No data').'" style="width:35px;height:20px;background-color:gray;"></div>';
			
				break;
			case 5:
			
				$data[5] = '<div title="'.__('Not init').'" style="width:35px;height:20px;background-color:blue;"></div>';
			
				break;
			case 0:
			
				$data[5] = '<div title="'.__('Normal').'" style="width:35px;height:20px;background-color:green;"></div>';
			
				break;
				
			default:
			
				break;
		}
		
		$data[6] = "<a href='index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&delete_cluster=".$cluster["id"]."' onclick='javascript: if (!confirm(\"Are you sure to delete?\")) return false;'><img src='images/cross.png'></a>
                <a href='index.php?sec=reporting&sec2=godmode/reporting/cluster_builder.cluster_editor&id=".$cluster["id"]."'><img src='images/builder.png'></a>";
    
    array_push ($table->data, $data);
  }
  
  html_print_table($table);

      echo '<form method="post" style="float:right;" action="index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&step=1">';
        html_print_submit_button (__('Create cluster'), 'create', false, 'class="sub next" style="margin-right:5px;"');
      echo "</form>";  
  
  
?>