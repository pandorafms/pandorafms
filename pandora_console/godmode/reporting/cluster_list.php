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
  $table->head[0] = __('Cluster name');
  $table->head[1] = __('Description');
  $table->head[2] = __('Cluster type');
  $table->head[3] = __('Number of A/A modules');
  $table->head[4] = __('Number of A/P modules');
  $table->head[5] = __('Group');
  $table->head[6] = __('Actions');
  $table->size[0] = '25%';
  $table->size[1] = '25%';
  $table->size[2] = '10%';
  $table->size[3] = '10%';
  $table->size[4] = '15%';
  $table->size[5] = '10%';
  $table->size[5] = '5%';
  $table->align[2] = 'left';
  $table->align[3] = 'left';
  
  $table->data = array ();
  
  foreach ($clusters as $cluster) {
    $data = array ();
    
    $data[0] = '<a href="index.php?sec=reporting&sec2=godmode/reporting/cluster_view&id='.$cluster["id"].'">'.$cluster["name"].'</a>';
    $data[1] = ui_print_truncate_text($cluster["description"], 70);
    $data[2] = $cluster["cluster_type"];
    
    $aa_modules_cluster = db_process_sql('select count(*) as number from tcluster_item where id_cluster = '.$cluster['id'].' and item_type = "AA"');    
        
    $data[3] = $aa_modules_cluster[0]['number'];
    
    if($cluster['cluster_type'] == 'AP'){
      $ap_modules_cluster = db_process_sql('select count(*) as number from tcluster_item where id_cluster = '.$cluster['id'].' and item_type = "AP"');    
          
      $data[4] = $ap_modules_cluster[0]['number'];
    }
    else{
      $data[4] = 'AA clusters do not have AP modules';
    }
    
    $data[5] = ui_print_group_icon($cluster['group'],true);
    
    $data[6] = "<a href='index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&step=4&delete_module=".$key."&id_cluster=".$id_cluster."'><img src='images/cross.png'></a>
                <a href='index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&step=4&delete_module=".$key."&id_cluster=".$id_cluster."'><img src='images/builder.png'></a>";
    
    array_push ($table->data, $data);
  }
  
  html_print_table($table);

      echo '<form method="post" style="float:right;" action="index.php?sec=reporting&sec2=godmode/reporting/cluster_builder">';
        html_print_submit_button (__('Create cluster'), 'create', false, 'class="sub next" style="margin-right:5px;"');
      echo "</form>";  
  
  
?>