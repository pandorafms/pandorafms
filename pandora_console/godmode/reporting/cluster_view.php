<?php

global $config;

check_login ();

if (! check_acl ($config['id_user'], 0, "AR")) {
	db_pandora_audit("ACL Violation", "Trying to access agent main list view");
	require ("general/noaccess.php");
	
	return;
}

require_once ('include/functions_agents.php');
require_once('include/functions_clusters.php');

$id_cluster = get_parameter('id',0);

$buttons['list'] = array('active' => false,
  'text' => '<a href="index.php?sec=estado&sec2=enterprise/operation/cluster/cluster">' . 
    html_print_image("images/list.png", true, array ("title" => __('Clusters list'))) .'</a>');
    
    
$buttons['editor'] = array('active' => false,
  'text' => '<a href="index.php?sec=estado&sec2=godmode/reporting/cluster_builder.cluster_editor&id='.$id_cluster.'">' . 
    html_print_image("images/gm_setup.png", true, array ("title" => __('Cluster editor'))) .'</a>');
    
    

ui_print_page_header ( __("Cluster detail"), "images/agent_mc.png", false, "agent_status", false, $buttons);

echo 'El estado del modulo del cluster '.clusters_get_name($id_cluster).' es '.modules_get_agentmodule_last_status(40);

?>