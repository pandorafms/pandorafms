<?php

global $config;

check_login ();

if (! check_acl ($config['id_user'], 0, "AR")) {
	db_pandora_audit("ACL Violation", "Trying to access agent main list view");
	require ("general/noaccess.php");
	
	return;
}

require_once ('include/functions_agents.php');
require_once ('include/functions_modules.php');
require_once('include/functions_clusters.php');

$id_cluster = get_parameter('id',0);

$buttons['list'] = array('active' => false,
  'text' => '<a href="index.php?sec=estado&sec2=enterprise/operation/cluster/cluster">' . 
    html_print_image("images/list.png", true, array ("title" => __('Clusters list'))) .'</a>');
    
    
$buttons['editor'] = array('active' => false,
  'text' => '<a href="index.php?sec=estado&sec2=godmode/reporting/cluster_builder.cluster_editor&id='.$id_cluster.'">' . 
    html_print_image("images/gm_setup.png", true, array ("title" => __('Cluster editor'))) .'</a>');
    
    
ui_print_page_header ( __("Cluster detail").' » '.clusters_get_name($id_cluster), "images/agent_mc.png", false, "agent_status", false, $buttons);



echo "<table style='width:100%;border: 1px black solid;'>";
  echo "<tr>";

    echo "<td style='border:1px solid black;min-width:800px;min-height:800px;vertical-align: top;'>";
      echo "<div id='cluster_map' style='width:600px;height:600px;'></div>";
    echo "</td>";




    echo "<td style='min-width:600px;min-height:600px;vertical-align: top;background-color:blue;'>";

      echo "<div style='width:50%;min-width:400px;background-color:red;float:left;'>";
        
        echo "<div style='float:left;width:100px;margin-left:20px;margin-top:20px;font-size:2em;text-align:center;'>".__('CLUSTER STATUS')."</div>";
        echo "<div style='float:left;width:100px;margin-left:20px;margin-top:20px;height:50px;background-color:#82b92e;'></div>";
        
        echo "<div style='border:1px solid lightgray;float:left;width:350px;margin-left:20px;margin-top:20px;height:200px;margin-bottom:20px;'>";
					
					echo "<div style='float:left;width:100%;height:25px;background-color:#373737;text-align:center;'><span style='color:#e7e9ea;display:block;margin:5px;font-size:1.5em;'>".__('Balanced modules')."</span></div>";
					
					echo "<div style='float:left;width:100%;height:175px;background-color:orange;text-align:center;'>";
					
						$balanced_modules_in = items_get_cluster_items_id_name($id_cluster,'AP');
						
						foreach ($balanced_modules_in as $key => $value) {
							$cluster_module = db_process_sql('select id_agente_modulo from tagente_modulo where custom_integer_2 = '.$key);
							
							html_debug($cluster_module[0]['id_agente_modulo']);
							
							$cluster_module_status = modules_get_status($cluster_module[0]['id_agente_modulo']);
							
							
							html_debug(modules_get_status($cluster_module_status));
							
							
							echo "<div style='float:left;'>";
							
							if($cluster_module_status == 5){
								echo '<img style="width:18px;height:18px;" src="images/exito.png">'.$value;
							}
							else{
								echo '<img style="width:18px;height:18px;" src="images/error_1.png">'.$value;
							}
							
							echo "</div>";
							
						}
						
						
						// 
						// $cluster_module_status = modules_get_status($cluster_module[0]['id_agente_modulo']);
					
						// html_debug($balanced_modules_in);
					
					echo "</div>";
					
				echo "</div>";

      echo "</div>";

      echo "<div style='width:50%;background-color:orange;float:right;'>";
        // 
        // echo "<div style='float:left;width:100px;px;margin-top:20px;font-size:2em;text-align:center;'>".__('LAST UPDATE')."</div>";
        // echo "<div style='float:left;width:220px;margin-left:20px;margin-top:30px;font-size:1.5em;text-align:center;'>88 Hours 88 Min 88 sec ago</div>";

      echo "</div>";


          
      

    echo "</td>";

    echo "</tr>";

    echo "<tr>";
    echo "<td colspan='2' style='min-width:600px;min-height:600px;vertical-align: top;'>
    <div id='cluster_modules' style='min-height:150px;'></div>
    </td>";
  echo "</tr>";
echo "</table>";


echo 'El estado del modulo del cluster '.clusters_get_name($id_cluster).' es '.agents_get_status(40);

?>