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

// -----------------------
// CREATE/EDIT CLUSTER FORM
// -----------------------

if($step == 1){

  if ($edit_cluster)
  	echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&edit_cluster=1&update_cluster=1&id=" . $id_cluster . "'>";
  else
  	echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&edit_cluster=1&add_cluster=1'>";

  echo "<table width='40%' cellpadding=4 cellspacing=4 class='databox filters'>";
  echo "<tr>";
  echo "<td class='datos'><b>".__('Cluster name')."</b>".ui_print_help_tip (__("An agent with the same name of the cluster will be created, as well a special service with the same name"), true)."</td>";
  echo "<td class='datos'><input type='text' name='name' size='50'></td>";
  echo "<tr>";
  echo "<tr>";
  echo "<td class='datos'><b>".__('Cluster type')."</b>";
  echo "<td class='datos'>";

  $cluster_types = array(
  	'AA' => __('Ative - Active'),
  	'AP' => __('Active - Pasive')
  	);
    
  html_print_select ($cluster_types, 'cluster_type', 'AA');

  echo "</td>";
  echo "<tr>";
  echo "</td>";
  echo "<tr>";
  echo "<td class='datos'><b>".__('Description')."</b></td>";
  echo "<td class='datos'><input type='text' name='description' size='50'></td>";
  echo "</tr>";

  echo "<td class='datos'><b>".__('Group')."</b></td>";
  echo" <td class='datos'>";
  	if (check_acl ($config['id_user'], 0, "RW"))
  		echo html_print_select_groups($config['id_user'], 'RW', $return_all_groups, 'cluster_id_group', $id_group, '', '', '', true);
  	elseif (check_acl ($config['id_user'], 0, "RM"))
  		echo html_print_select_groups($config['id_user'], 'RM', $return_all_groups, 'cluster_id_group', $id_group, '', '', '', true);
  echo "</td></tr>";

  echo "</table>";

  if ($edit_cluster) {
  	echo "<div style='width:40%'><input style='float:right;' type=submit name='store' class='sub upd' value='".__('Update')."'></div>";
  }
  else {
  	echo "<div style='width:40%'><input style='float:right;' type=submit name='store' class='sub next' value='".__('Create')."'></div>";
  }
  echo "</form>";
  
}
elseif($step == 2){
  
  echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&step=2&id_cluster='".$id_cluster.">";

  echo "<table width='50%' cellpadding=4 cellspacing=4 class='databox filters'>";

  echo "<tr>";
  echo "<td class='datos'><b>".__('Cluster name')."</b></td>";
  echo "<td class='datos'>".clusters_get_name($id_cluster)."</td>";
  echo "</tr>";
  
  echo "<tr>";
  echo "<td class='datos'><b>".__('Adding agents to the cluster')."</b></td>";
  echo "</tr>";
  
  echo "<tr>";
  echo "<td  class='datos'>";
    // $attr = array('id' => 'image-select_all_available', 'title' => __('Select all'), 'style' => 'cursor: pointer;');
  echo "<b>" . __('Agents')."</b>&nbsp;&nbsp;" . html_print_image ('images/tick.png', true, $attr, false, true);
  echo "</td>";
  
  echo "<td class='datos'>";
  echo "</td>";
  
  echo "<td  class='datos'>";
    // $attr = array('id' => 'image-select_all_apply', 'title' => __('Select all'), 'style' => 'cursor: pointer;');
  echo "<b>" . __('Agents in Cluster')."</b>&nbsp;&nbsp;" . html_print_image ('images/tick.png', true, $attr, false, true);
  echo "</td>";
  echo "<tr>";
  echo "<td class='datos'>";
  
  $option_style = array();
  $cluster_agents_in = agents_get_cluster_agents(8);  
  
  html_debug($cluster_agents_in);
  
  $cluster_agents_all = agents_get_group_agents(0, false, '');
  $cluster_agents_out = array();
  $cluster_agents_out = array_diff_key($template_agents_all, $template_agents_in);
  
  $cluster_agents_in_keys = array_keys($template_agents_in);
  $cluster_agents_out_keys = array_keys($template_agents_out);
  
  html_print_select ($cluster_agents_all, 'id_agents[]', 0, false, '', '', false, true, true, '', false, 'width: 100%;', $option_style);
  
  echo "</td>";
  
  echo "<td class='datos'>";
  echo "<br />";
  html_print_image ('images/darrowright.png', false, array ('id' => 'right', 'title' => __('Add agents to cluster')));
  echo "<br /><br /><br />";
  html_print_image ('images/darrowleft.png', false, array ('id' => 'left', 'title' => __('Drop agents to cluster')));
  echo "<br /><br /><br />";
  echo "</td>";
  
  echo "<td class='datos'>";
  
  html_print_select ($cluster_agents_in, 'id_agents2[]', 0, false, '', '', false, true, true, '', false, 'width: 100%;', $option_style);
  
  echo "</td>";
  echo "</tr>";
  echo "</table>";

  echo "<div style='width:50%'><input style='float:right;' type=submit name='store' class='sub upd' value='".__('Next')."'></div>";
  echo "</form>";
  
}

?>
