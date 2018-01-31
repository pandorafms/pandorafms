<?php

  $name_module = get_parameter('name_module',0);
  $id_cluster = get_parameter('id_cluster',0);
  $module_ap = get_parameter('module_ap',0);
  // 
  // $module_agents = db_process_sql('select id_agente_modulo,id_agente from tagente_modulo where nombre = "'.$name_module.'" and id_agente in
  //  (select id_agent from tcluster_agent where id_cluster = '.$id_cluster.')');
  //  
  //  
   $module_agents = db_process_sql('select tagente_modulo.id_agente_modulo,tagente_modulo.id_agente,utimestamp from tagente_modulo,tagente_estado where tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo and nombre = "'.$name_module.'" and tagente_modulo.id_agente in
    (select id_agent from tcluster_agent where id_cluster = '.$id_cluster.') ORDER BY utimestamp DESC');
   
  //  $module_agents = db_process_sql('select id_agente_modulo,id_agente,utimestamp from tagente_modulo,tagente_estado');

  foreach ($module_agents as $key => $value) {
    $module_agents_value[$module_agents[$key]['id_agente']] = $module_agents[$key]['id_agente_modulo'];
  }
  
  
  echo '
  <table style="width:100%;border:2px black solid" cellpadding="4" cellspacing="4" border="0" class="databox data" id="table2">
  <thead><tr>
    <th class="header c0" style="text-align: left;" scope="col">Agent alias</th>
    <th class="header c1" style="text-align: left;" scope="col">Module name</th>
    <th class="header c2" style="text-align: left;" scope="col">Status</th>
    <th class="header c3" style="text-align: left;" scope="col">Data</th>
    <th class="header c4" style="text-align: left;" scope="col">Graph</th>
    <th class="header c5" style="text-align: left;" scope="col">Last contact</th>
    <th id="modal_module_popup_close" onclick="$(\'#modal_module_popup\').css(\'display\',\'none\');" class="header c4" style="text-align: left;" scope="col"><img src="images/icono_cerrar.png"></th>
  </tr></thead>
  <tbody>
    
    ';
    foreach ($module_agents_value as $key => $value) {
      
      if ($value === reset($module_agents_value) && $module_ap) {
        echo '<tr style="background-color:#e0eec9;" class="datos2">';
      }
      else{
        echo '<tr class="datos2">';
      }
      
      echo '<td  style=" text-align:left;" class="datos2 "><a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$key.'" target="_blank">'.agents_get_alias($key).'</a></td>';
      echo '<td  style=" text-align:left;" class="datos2 ">'.modules_get_agentmodule_name($value).'</td>';
      echo '<td  style=" text-align:left;" class="datos2 ">';
      
      switch (modules_get_agentmodule_last_status($value)) {
        case 1:
        
        echo '<img src="images/status_sets/default/module_critical.png" data-title="CRITICAL: '.modules_get_last_value($value).'" data-use_title_for_force_title="1" class="forced_title" alt="CRITICAL: '.modules_get_last_value($value).'">';
        
          break;
        case 2:
        
        echo '<img src="images/status_sets/default/module_warning.png" data-title="WARNING: '.modules_get_last_value($value).'" data-use_title_for_force_title="1" class="forced_title" alt="WARNING: '.modules_get_last_value($value).'">';
        
          break;
        case 4:
        
        echo '<img src="images/status_sets/default/module_no_data.png" data-title="NO DATA: '.modules_get_last_value($value).'" data-use_title_for_force_title="1" class="forced_title" alt="NO DATA: '.modules_get_last_value($value).'">';
        
          break;
        case 3:
        
        echo '<img src="images/status_sets/default/module_unknown.png" data-title="UNKNOWN: '.modules_get_last_value($value).'" data-use_title_for_force_title="1" class="forced_title" alt="UNKNOWN: '.modules_get_last_value($value).'">';
        
          break;
        case 5:
        
        echo '<img src="images/status_sets/default/module_ok.png" data-title="NOT INIT: '.modules_get_last_value($value).'" data-use_title_for_force_title="1" class="forced_title" alt="NOT INIT: '.modules_get_last_value($value).'">';
        
          break;
        case 0:
        
          echo '<img src="images/status_sets/default/module_ok.png" data-title="NORMAL: '.modules_get_last_value($value).'" data-use_title_for_force_title="1" class="forced_title" alt="NORMAL: '.modules_get_last_value($value).'">';
        
          break;
          
        default:
        
          break;
      }
      
      
      echo '</td>';    
      
      echo '<td  style=" text-align:left;" class="datos2 ">'.modules_get_last_value($value).'</td>';
      
      echo '<td  style=" text-align:left;" class="datos2 ">';
        echo '<a href=\'javascript: show_module_detail_dialog('.$value.', '.$key.', "", 0, 86400, "'.modules_get_agentmodule_name($value).'")\'>';
        echo '<img src="images/binary.png" style="border:0px;" alt="">';
        echo '</a>&nbsp;&nbsp;&nbsp;';
        
        $nombre_tipo_modulo = modules_get_moduletype_name (modules_get_type_id($value));
  			$handle = "stat".$nombre_tipo_modulo."_".$value;
  			$url = 'include/procesos.php?agente='.$value;
  			$win_handle=dechex(crc32($value.modules_get_agentmodule_name($value)));
        
  			if ($graph_type == 'boolean') {
  				$draw_events = 1;
  			} else {
  				$draw_events = 0;
  			}
        
  			$link ="winopeng('" .
  				"operation/agentes/stat_win.php?" .
  				"type=$graph_type&amp;" .
  				"period=" . SECONDS_1DAY . "&amp;" .
  				"id=" . $value . "&amp;" .
  				"label=" . rawurlencode(
  					urlencode(
  						base64_encode($module["nombre"]))) . "&amp;" .
  				"refresh=" . SECONDS_10MINUTES . "&amp;" .
  				"draw_events=$draw_events', 'day_".$win_handle."')";
        
          echo '<a href="javascript:'.$link.'">';
          echo '<img src="images/chart_curve.png" style="border:0px;" alt="">';
          echo '</a>';
        
        
      echo '</td>';
      echo '<td  style=" text-align:left;" class="datos2 ">'.date("d/m/Y - H:i:s",modules_get_last_contact($value)).'</td>';
      echo '<td>';
      echo '</td>';
      echo '</tr>';
    }
    
    echo '
  </tbody>
</table>
';
  
  return;

?>