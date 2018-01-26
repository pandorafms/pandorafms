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



echo "<table style='width:100%;'>";
  echo "<tr>";

    echo "<td style='border:1px solid black;min-width:800px;min-height:500px;vertical-align: top;width:55%';>";
      echo "<div id='cluster_map' style='min-width:800px;width:100%;height:500px;'></div>";
    echo "</td>";

    echo "<td style='width:45%;min-width:600px;min-height:500pxpx;vertical-align: top;background-color:blue;'>";

      echo "<div style='width:50%;min-width:300px;background-color:lightblue;float:left;'>";
        
        echo "<div style='float:left;width:100px;margin-left:20px;margin-top:20px;font-size:2em;text-align:center;'>".__('CLUSTER STATUS')."</div>";
				
				$cluster_module = db_process_sql('select id_agente_modulo from tagente_modulo where id_agente = (select id_agent from tcluster where id = '.$id_cluster.') and nombre = "Cluster status"');
				
				$cluster_module_status = modules_get_agentmodule_last_status($cluster_module[0]['id_agente_modulo']);
				
				switch ($cluster_module_status) {
					case 1:
					
						echo "<div style='float:left;width:100px;margin-left:20px;margin-top:20px;height:50px;background-color:red;'></div>";
					
						break;
					case 2:
					
						echo "<div style='float:left;width:100px;margin-left:20px;margin-top:20px;height:50px;background-color:yellow;'></div>";
					
						break;
					case 4:
					
						echo "<div style='float:left;width:100px;margin-left:20px;margin-top:20px;height:50px;background-color:blue;'></div>";
					
						break;
					case 3:
					
						echo "<div style='float:left;width:100px;margin-left:20px;margin-top:20px;height:50px;background-color:gray;'></div>";
					
						break;
					case 5:
					
						echo "<div style='float:left;width:100px;margin-left:20px;margin-top:20px;height:50px;background-color:blue;'></div>";
					
						break;
					case 0:
					
						echo "<div style='float:left;width:100px;margin-left:20px;margin-top:20px;height:50px;background-color:green;'></div>";
					
						break;
						
					default:
					
						break;
				}
        
        echo "<div style='border:1px solid lightgray;float:left;width:350px;margin-left:20px;margin-right:20px;margin-top:20px;height:200px;margin-bottom:20px;'>";
					
					echo "<div style='float:left;width:100%;height:25px;background-color:#373737;text-align:center;'><span style='color:#e7e9ea;display:block;margin:5px;font-size:1.5em;'>".__('Balanced modules')."</span></div>";
					
					echo "<div style='float:left;width:100%;height:175px;background-color:orange;text-align:center;overflow-y:auto;overflow-x:hidden;'>";
					
						$balanced_modules_in = items_get_cluster_items_id_name($id_cluster,'AP');
						
						foreach ($balanced_modules_in as $key => $value) {
							$cluster_module = db_process_sql('select id_agente_modulo from tagente_modulo where custom_integer_2 = '.$key);
							
							$cluster_module_status = modules_get_agentmodule_last_status($cluster_module[0]['id_agente_modulo']);
														
							echo "<div style='float:left;margin-left:20px;margin-top:10px;width:330px;'>";
														
							if($cluster_module_status == 5){
								echo '<div style="float:left;"><img style="width:18px;height:18px;margin-right:5px;vertical-align:middle;" src="images/exito.png">'.ui_print_truncate_text($value, 40,false).'</div>';
							}
							else{
								echo '<div style="float:left;"><img style="width:18px;height:18px;margin-right:5px;vertical-align:middle;" src="images/error_1.png">'.ui_print_truncate_text($value, 40,false).'</div>';
							}
							
							echo '</div>';
							
						}
						
					echo "</div>";
					
				echo "</div>";

      echo "</div>";

      
			echo "<div style='width:50%;min-width:300px;background-color:red;float:left;'>";
			
			$last_update = db_process_sql('select timestamp from tagente_estado where id_agente_modulo = '.$cluster_module[0]['id_agente_modulo']);
      
			$last_update_value = $last_update[0]['timestamp'];
				
			echo "<div style='float:left;width:100px;px;margin-top:20px;font-size:2em;text-align:center;'>".__('LAST UPDATE')."</div>";
			echo "<div style='float:left;width:220px;margin-left:20px;margin-top:30px;font-size:1.5em;text-align:center;'>".$last_update_value."</div>";
        
        echo "<div style='border:1px solid lightgray;float:left;width:350px;margin-left:20px;margin-right:20px;margin-top:20px;height:200px;margin-bottom:20px;'>";
					
					echo "<div style='float:left;width:100%;height:25px;background-color:#373737;text-align:center;'><span style='color:#e7e9ea;display:block;margin:5px;font-size:1.5em;'>".__('Common modules')."</span></div>";
					
					echo "<div style='float:left;width:100%;height:175px;background-color:orange;text-align:center;overflow-y:auto;overflow-x:hidden;'>";
					
						$modules_in = items_get_cluster_items_id_name($id_cluster,'AA');
						
						foreach ($modules_in as $key => $value) {
							$cluster_module = db_process_sql('select id_agente_modulo from tagente_modulo where custom_integer_2 = '.$key);
							
							$cluster_module_status = modules_get_agentmodule_last_status($cluster_module[0]['id_agente_modulo']);
														
							echo "<div style='float:left;margin-left:20px;margin-top:10px;width:330px;'>";
														
							if($cluster_module_status == 5){
								echo '<div style="float:left;"><img style="width:18px;height:18px;margin-right:5px;vertical-align:middle;" src="images/exito.png">'.ui_print_truncate_text($value, 40,false).'</div>';
							}
							else{
								echo '<div style="float:left;"><img style="width:18px;height:18px;margin-right:5px;vertical-align:middle;" src="images/error_1.png">'.ui_print_truncate_text($value, 40,false).'</div>';
							}
							
							echo '</div>';
							
						}
						
					echo "</div>";
					
				echo "</div>";
			
      echo "</div>";
			
			echo "<div style='width:100%;height:140px;min-width:600px;background-color:orange;float:left;margin-top:50px;'>";
			
			$id_agent = db_process_sql('select id_agent from tcluster where id = '.$id_cluster);
			
			$id_agent_value = $id_agent[0]['id_agent'];
			
			$table = new stdClass();
			$table->id = 'agent_details';
			$table->width = '100%';
			$table->cellspacing = 0;
			$table->cellpadding = 0;
			$table->class = 'agents';
			$table->style = array_fill(0, 3, 'vertical-align: top;');

			$data = array();
			$data[0][0] = html_print_table($table_agent, true);
			$data[0][0] .=
				'<br /> <table width=90% class="databox agente" style="margin-left:5%;">
					<tr><th>' .
						__('Events (24h)') .
					'</th></tr>' .
					'<tr><td style="text-align:center;padding-left:20px;padding-right:20px;"><br />' .
					graph_graphic_agentevents ($id_agent_value, 450, 40, SECONDS_1DAY, '', true, true) . 
					'<br /></td></tr>' . 
				'</table>';

			$table->style[0] = 'width:100%; vertical-align:top;';
			$data[0][1] = html_print_table($table_contact, true);
			$data[0][1] .= empty($table_data->data) ?
				'' :
				'<br>' . html_print_table($table_data, true);
			$data[0][1] .= !isset($table_incident) ?
				'' :
				'<br>' . html_print_table($table_incident, true);

			$table->rowspan[1][0] = 0;
			
			$table->data = $data;
			$table->rowclass[] = '';

			$table->cellstyle[1][0] = 'text-align:center;';

			html_print_table($table);
			$data2[1][0] = !isset($table_interface) ?
				'' :
				html_print_table($table_interface, true);
			$table->data = $data2;
			$table->styleTable = '';
			html_print_table($table);

			unset($table);
			
			echo "</div>";


    echo "</td>";

    echo "</tr>";

    echo "<tr>";
    echo "<td colspan='2' style='min-width:600px;min-height:600px;vertical-align: top;border:1px solid black;'>";
	    echo "<div id='cluster_modules' style='min-height:150px;'>";
			
				
			
			echo "</div>";
    echo "</td>";
  echo "</tr>";
echo "</table>";

?>