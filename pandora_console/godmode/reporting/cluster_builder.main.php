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

echo '<ol class="steps">';

$pure = get_parameter('pure', 0);

/* Step 1 */
if ($step == 1)
  echo '<li class="first current">';
elseif ($step > 1)
  echo '<li class="visited">';
else
  echo '<li class="first">';

if ($step > 1 && !$update) {
  
  echo __('Step') . ' 1 &raquo; ';
  echo '<span>' . __('Cluster settings') . '</span>';
  
}
elseif ($update) {
  echo '<a href="index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&id_cluster='.$id_cluster.'&step=1&update=1">';
  echo __('Step') . ' 1 &raquo; ';
  echo '<span>' . __('Cluster settings') . '</span>';
  echo '</a>';
}
else {
  echo __('Step') . ' 1 &raquo; ';
  echo '<span>' . __('Cluster settings') . '</span>';
}
echo '</li>';





/* Step 2 */
if ($step == 2)
  echo '<li class="first current">';
elseif ($step > 2)
  echo '<li class="visited">';
else
  echo '<li class="first">';

if ($step > 2 && !$update) {
  echo '<a href="index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&id_cluster='.$id_cluster.'&step=2">';
  echo __('Step') . ' 2 &raquo; ';
  echo '<span>' . __('Cluster agents') . '</span>';
  echo '</a>';
}
elseif ($update) {
  echo '<a href="index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&id_cluster='.$id_cluster.'&step=2&update=1">';
  echo __('Step') . ' 2 &raquo; ';
  echo '<span>' . __('Cluster agents') . '</span>';
  echo '</a>';
}
else {
  echo __('Step') . ' 2 &raquo; ';
  echo '<span>' . __('Cluster agents') . '</span>';
}
echo '</li>';






/* Step 3 */
if ($step == 3)
  echo '<li class="first current">';
elseif ($step > 3)
  echo '<li class="visited">';
else
  echo '<li class="first">';

if ($step > 3 && !$update) {
  echo '<a href="index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&id_cluster='.$id_cluster.'&step=3">';
  echo __('Step') . ' 3 &raquo; ';
  echo '<span>' . __('A/A modules') . '</span>';
  echo '</a>';
}
elseif ($update) {
  echo '<a href="index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&id_cluster='.$id_cluster.'&step=3&update=1">';
  echo __('Step') . ' 3 &raquo; ';
  echo '<span>' . __('A/A modules') . '</span>';
  echo '</a>';
}
else {
  echo __('Step') . ' 3 &raquo; ';
  echo '<span>' . __('A/A modules') . '</span>';
}
echo '</li>';








/* Step 3 */
if ($step == 4)
  echo '<li class="first current">';
elseif ($step > 4)
  echo '<li class="visited">';
else
  echo '<li class="first">';

if ($step > 4 && !$update) {
  echo '<a href="index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&id_cluster='.$id_cluster.'&step=4">';
  echo __('Step') . ' 4 &raquo; ';
  echo '<span>' . __('A/A modules limits') . '</span>';
  echo '</a>';
}
elseif ($update) {
  echo '<a href="index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&id_cluster='.$id_cluster.'&step=4&update=1">';
  echo __('Step') . ' 4 &raquo; ';
  echo '<span>' . __('A/A modules limits') . '</span>';
  echo '</a>';
}
else {
  echo __('Step') . ' 4 &raquo; ';
  echo '<span>' . __('A/A modules limits') . '</span>';
}
echo '</li>';







$type_cluster = clusters_get_cluster_id_type($id_cluster);

if($type_cluster[$id_cluster] == 'AP'){

/* Step 3 */
if ($step == 5)
  echo '<li class="first current">';
elseif ($step > 5)
  echo '<li class="visited">';
else
  echo '<li class="first">';

if ($step > 5 && !$update) {
  echo '<a href="index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&id_cluster='.$id_cluster.'&step=5">';
  echo __('Step') . ' 5 &raquo; ';
  echo '<span>' . __('A/P modules') . '</span>';
  echo '</a>';
}
elseif ($update) {
  echo '<a href="index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&id_cluster='.$id_cluster.'&step=5&update=1">';
  echo __('Step') . ' 5 &raquo; ';
  echo '<span>' . __('A/P modules') . '</span>';
  echo '</a>';
}
else {
  echo __('Step') . ' 5 &raquo; ';
  echo '<span>' . __('A/P modules') . '</span>';
}
echo '</li>';







/* Step 3 */
if ($step == 6)
  echo '<li class="first current">';
elseif ($step > 6)
  echo '<li class="visited">';
else
  echo '<li class="first">';

if ($step > 6 && !$update) {
  echo '<a href="index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&id_cluster='.$id_cluster.'&step=6">';
  echo __('Step') . ' 6 &raquo; ';
  echo '<span>' . __('Critical A/P modules') . '</span>';
  echo '</a>';
}
elseif ($update) {
  echo '<a href="index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&id_cluster='.$id_cluster.'&step=6&update=1">';
  echo __('Step') . ' 6 &raquo; ';
  echo '<span>' . __('Critical A/P modules') . '</span>';
  echo '</a>';
}
else {
  echo __('Step') . ' 6 &raquo; ';
  echo '<span>' . __('Critical A/P modules') . '</span>';
}
echo '</li>';


}

echo '</ol>';
echo '<div id="steps_clean"> </div>';

if($step == 1){

  if(!$update){
    echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&add_cluster=1&step=1'>";
  }
  else{
    echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&update=1&edit_cluster=1&step=1&id_cluster=".$id_cluster."'>";
  }

  echo "<table width='40%' cellpadding=4 cellspacing=4 class='databox filters'>";
  echo "<tr>";
  echo "<td class='datos'><b>".__('Cluster name')."</b>".ui_print_help_tip (__("An agent with the same name of the cluster will be created, as well a special service with the same name"), true)."</td>";
  echo "<td class='datos'><input type='text' class='check_agent' name='name' size='50' value='".clusters_get_name($id_cluster)."'>";
  
  if(!$update){
  echo "<img class='check_image_agent' style='width:18px;height:18px;margin-right:5px;' src='images/error_1.png'>
  <label class='check_image_label'>".__('Should not be empty')."</label>";  
  }
  
  echo "</td>";
  echo "</tr>";
  
  if(!$update){
  
  echo "<tr>";
  echo "<td class='datos'><b>".__('Cluster type')."</b>";
  echo "</td>";
  echo "<td class='datos'>";

  $cluster_types = array(
  	'AA' => __('Ative - Active'),
  	'AP' => __('Active - Pasive')
  	);
    
  html_print_select ($cluster_types, 'cluster_type', 'AA');

  echo "</td>";
  echo "</tr>";
  
  }
    
  echo "<tr>";
  echo "<td class='datos'><b>".__('Description')."</b></td>";
  echo "<td class='datos'><input type='text' name='description' size='50' value='".clusters_get_description($id_cluster)."'></td>";
  echo "</tr>";
  
  echo "<tr>";

  echo "<td class='datos'><b>".__('Group')."</b></td>";
  echo" <td class='datos'>";
  	if (check_acl ($config['id_user'], 0, "RW"))
  		echo html_print_select_groups($config['id_user'], 'RW', $return_all_groups, 'cluster_id_group', clusters_get_group($id_cluster), '', '', '', true);
  	elseif (check_acl ($config['id_user'], 0, "RM"))
  		echo html_print_select_groups($config['id_user'], 'RM', $return_all_groups, 'cluster_id_group', clusters_get_group($id_cluster), '', '', '', true);
  echo "</td></tr>";

  echo "</table>";

  if(!$update){
	  echo "<div style='width:40%'><input style='float:right;' disabled type=submit name='store' class='sub next create_agent_check' value='".__('Create')."'></div>";
  }
  else{
    echo "<div style='width:40%'><input style='float:right;' type=submit name='store' class='sub next' value='".__('Update and view cluster')."'></div>";
  }
  echo "</form>";
  
}
elseif($step == 2){
  
  if(!$update){
    echo "<form method='post' id='form_step_2' action='index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&step=2&assign_agents=1&id_cluster=".$id_cluster."'>";
  }
  else{
    echo "<form method='post' id='form_step_2' action='index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&step=2&assign_agents=1&id_cluster=".$id_cluster."&update=1'>";
  }
  
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
    $attr = array('id' => 'select_all_left_agents', 'title' => __('Select all'), 'style' => 'cursor: pointer;');
  echo "<b>" . __('Agents')."</b>&nbsp;&nbsp;" . html_print_image ('images/tick.png', true, $attr, false, true);
  echo "</td>";
  
  echo "<td class='datos'>";
  echo "</td>";
  
  echo "<td  class='datos'>";
    $attr = array('id' => 'select_all_right_agents', 'title' => __('Select all'), 'style' => 'cursor: pointer;');
  echo "<b>" . __('Agents in Cluster')."</b>&nbsp;&nbsp;" . html_print_image ('images/tick.png', true, $attr, false, true);
  echo "</td>";
  echo "<tr>";
  echo "<td class='datos'>";
  
  $option_style = array();
  $cluster_agents_in = agents_get_cluster_agents_alias($id_cluster); 
  // $cluster_agents_all = agents_get_group_agents(0, false, '');
  
  $cluster_agents_all_pre = db_get_all_rows_sql('select * from tagente where id_os != 21');
  
  foreach ($cluster_agents_all_pre as $key => $value) {
    $cluster_agents_all[$value['id_agente']] = $value['alias'];
  }
  
  $cluster_agents_out = array_diff_key($cluster_agents_all,$cluster_agents_in);
  
  html_print_select ($cluster_agents_out, 'id_agents[]', 0, false, '', '', false, true, true, '', false, 'width: 100%;', $option_style);
  
  echo "</td>";
  
  echo "<td class='datos'>";
  echo "<br />";
  html_print_image ('images/darrowright.png', false, array ('id' => 'agent_right', 'title' => __('Add agents to cluster')));
  echo "<br /><br /><br />";
  html_print_image ('images/darrowleft.png', false, array ('id' => 'agent_left', 'title' => __('Drop agents to cluster')));
  echo "<br /><br /><br />";
  echo "</td>";
  
  echo "<td class='datos'>";
  
  html_print_select ($cluster_agents_in, 'id_agents2[]', 0, false, '', '', false, true, true, '', false, 'width: 100%;', $option_style);
  
  echo "</td>";
  echo "</tr>";
  echo "</table>";

  if(!$update){
    echo "<div style='width:50%'><input style='float:right;' type=submit name='store' class='sub upd' value='".__('Update and go next')."'></div>";
  }
  else{
    echo "<div style='width:50%'><input style='float:right;' type=submit name='store' class='sub upd' value='".__('Update and view cluster')."'></div>";
  }
  
  echo "</form>";
  
}
elseif ($step == 3) {
  
  if(!$update){
    echo "<form method='post' id='form_step_3' action='index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&step=3&assign_modules=1&id_cluster=".$id_cluster."'>";
  }
  else{
    echo "<form method='post' id='form_step_3' action='index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&step=3&assign_modules=1&id_cluster=".$id_cluster."&update=1'>";
  }
  

  echo "<table width='50%' cellpadding=4 cellspacing=4 class='databox filters'>";

  echo "<tr>";
  echo "<td class='datos'><b>".__('Cluster name')."</b></td>";
  echo "<td class='datos'>".clusters_get_name($id_cluster)."</td>";
  echo "</tr>";
  
  echo "<tr>";
  echo "<td class='datos'><b>".__('Adding common modules').ui_print_help_tip('Attention: the cluster is configured using module names,<br> so that in order to add an agent to a cluster later on,<br> you will not have to select its modules one by one,<br> but instead, you only need to have a series of modules<br> that are called the same, so do not rename the names<br> of a module once you configure the cluster.', true)."</b></td>";
  echo "</tr>";
  
  echo "<tr>";
  echo "<td  class='datos'>";
    $attr = array('id' => 'select_all_left_modules', 'title' => __('Select all'), 'style' => 'cursor: pointer;');
  echo "<b>" . __('Common in agents')."</b>&nbsp;&nbsp;" . html_print_image ('images/tick.png', true, $attr, false, true);
  echo "</td>";
  
  echo "<td class='datos'>";
  echo "</td>";
  
  echo "<td  class='datos'>";
    $attr = array('id' => 'select_all_right_modules', 'title' => __('Select all'), 'style' => 'cursor: pointer;');
  echo "<b>" . __('Added common modules')."</b>&nbsp;&nbsp;" . html_print_image ('images/tick.png', true, $attr, false, true);
  echo "</td>";
  echo "<tr>";
  echo "<td class='datos'>";
  
  
  $cluster_agents_in_id = agents_get_cluster_agents_id($id_cluster);
  
  $serialize_agents = '';
  
  foreach ($cluster_agents_in_id as $value) {
    
    if ($value === end($cluster_agents_in_id)) {
        $serialize_agents .= $value;
    }
    else{
      $serialize_agents .= $value.',';
    }
    
  }
  $cluster_modules_in = items_get_cluster_items_name($id_cluster);
  
   $cluster_modules_all = db_get_all_rows_sql('select count(nombre) as total ,nombre, id_agente_modulo,id_agente FROM
    tagente_modulo where id_agente in ('.$serialize_agents.') group by nombre having total = '.count($cluster_agents_in_id));
    
   foreach ($cluster_modules_all as $key => $value) {
     $cluster_modules_all_name[$value['nombre']] = $value['nombre'];
   }
   
   $cluster_modules_all_diff = array_diff($cluster_modules_all_name,$cluster_modules_in);
           
  html_print_select ($cluster_modules_all_diff, 'name_modules[]', 0, false, '', '', false, true, true, '', false, 'width: 100%;', $option_style);
  
  echo "</td>";
  
  echo "<td class='datos'>";
  echo "<br />";
  html_print_image ('images/darrowright.png', false, array ('id' => 'module_right', 'title' => __('Add modules to cluster')));
  echo "<br /><br /><br />";
  html_print_image ('images/darrowleft.png', false, array ('id' => 'module_left', 'title' => __('Drop modules to cluster')));
  echo "<br /><br /><br />";
  echo "</td>";
  
  echo "<td class='datos'>";
  
  html_print_select ($cluster_modules_in, 'name_modules2[]', 0, false, '', '', false, true, true, '', false, 'width: 100%;', $option_style);
  
  echo "</td>";
  echo "</tr>";
  echo "</table>";
  
  if(!$update){
    echo "<div style='width:50%'><input style='float:right;' type=submit name='store' class='sub upd' value='".__('Update and Next')."'></div>";
  }
  else{
    echo "<div style='width:50%'><input style='float:right;' type=submit name='store' class='sub upd' value='".__('Update and view cluster')."'></div>";
  }
  
  echo "</form>";
      
}
elseif ($step == 4) {
  
  if(!$update){
    echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&step=4&assign_limits=1&id_cluster=".$id_cluster."'>";
  }
  else{
    echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&step=4&assign_limits=1&id_cluster=".$id_cluster."&update=1'>";
  }
  

  echo "<table width='50%' cellpadding=4 cellspacing=4 class='databox filters'>";

  echo "<tr>";
  echo "<th><b>".__('Common modules')."</b></th>";
  echo "<th><b>".__('Critical if equal or greater than')."</b></th>";
  echo "<th><b>".__('Warning if equal or greater than')."</b></th>";
  echo "<th><b>".__('Actions')."</b></th>";
  echo "</tr>";
  
  $cluster_items = items_get_cluster_items_id_name($id_cluster);
  
  foreach ($cluster_items as $key => $value) {
    echo "<tr>";
    echo "<td class='datos'>".$value."</td>";
    echo "<td class='datos'><input class='zero_hundred' value='".get_item_critical_limit_by_item_id($key)."' name='critical_item_".$key."' type='number' max='100' min='0' style='width:60%;' onkeydown='javascript: return event.keyCode == 69 ? false : true'> &nbsp;&nbsp;% are down</td>";
    echo "<td class='datos'><input class='zero_hundred' value='".get_item_warning_limit_by_item_id($key)."' name='warning_item_".$key."' type='number' max='100' min='0' style='width:60%;' onkeydown='javascript: return event.keyCode == 69 ? false : true'> &nbsp;&nbsp;% are down</td>";
    echo "<td class='datos'>";
    
    if(!$update){
      echo "<a href='index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&delete_module_aa=".$key."&id_cluster=".$id_cluster."'>";
    }
    else{
      echo "<a href='index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&delete_module_aa=".$key."&id_cluster=".$id_cluster."&update=1'>";  
    }
    
    
    echo "<img src='images/cross.png'></a></td>";
    echo "</tr>";
    
  }
  
  
  echo "</table>";
  
  if(!$update){
    echo "<div style='width:50%'><input style='float:right;' type=submit name='store' class='sub upd' value='".__('Update and Next')."'></div>";  
  }
  else{
    echo "<div style='width:50%'><input style='float:right;' type=submit name='store' class='sub upd' value='".__('Update and view cluster')."'></div>";
  }  
  
  echo "</form>";
  
  if(!$update){
    echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&step=3&id_cluster=".$id_cluster."'>";
  }
  else{
    echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&step=3&id_cluster=".$id_cluster."&update=1'>";
  }
  
  echo "<div style='width:50%'><input style='float:left;' type=submit name='add' class='sub add' value='".__('Add')."'></div>";
  
  echo "</form>";
  
}
elseif ($step == 5) {
  
  if(!$update){
    echo "<form method='post' id='form_step_3' action='index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&step=5&assign_balanced_modules=1&id_cluster=".$id_cluster."'>";
  }
  else{
    echo "<form method='post' id='form_step_3' action='index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&step=5&assign_balanced_modules=1&id_cluster=".$id_cluster."&update=1'>";
  }
  

  echo "<table width='50%' cellpadding=4 cellspacing=4 class='databox filters'>";

  echo "<tr>";
  echo "<td class='datos'><b>".__('Cluster name')."</b></td>";
  echo "<td class='datos'>".clusters_get_name($id_cluster)."</td>";
  echo "</tr>";
  
  echo "<tr>";
  echo "<td class='datos'><b>".__('Adding balanced modules')."</b></td>";
  echo "</tr>";
  
  echo "<tr>";
  echo "<td  class='datos'>";
    $attr = array('id' => 'select_all_left_modules', 'title' => __('Select all'), 'style' => 'cursor: pointer;');
  echo "<b>" . __('Modules')."</b>&nbsp;&nbsp;" . html_print_image ('images/tick.png', true, $attr, false, true);
  echo "</td>";
  
  echo "<td class='datos'>";
  echo "</td>";
  
  echo "<td  class='datos'>";
    $attr = array('id' => 'select_all_right_modules', 'title' => __('Select all'), 'style' => 'cursor: pointer;');
  echo "<b>" . __('Added balanced modules')."</b>&nbsp;&nbsp;" . html_print_image ('images/tick.png', true, $attr, false, true);
  echo "</td>";
  echo "<tr>";
  echo "<td class='datos'>";
  
  
  $cluster_agents_in_id = agents_get_cluster_agents_id($id_cluster);
  
  $serialize_agents = '';
  
  foreach ($cluster_agents_in_id as $value) {
    
    if ($value === end($cluster_agents_in_id)) {
        $serialize_agents .= $value;
    }
    else{
      $serialize_agents .= $value.',';
    }
    
  }
  
  $balanced_modules_all = db_get_all_rows_sql('select nombre from tagente_modulo where id_agente in ('.$serialize_agents.')');
  
  foreach ($balanced_modules_all as $key => $value) {
    $balanced_modules_all_name[$value['nombre']] = $value['nombre'];
  }
  
  $balanced_modules_in = items_get_cluster_items_name($id_cluster,'AP');
    
  $balanced_modules_all_diff = array_diff($balanced_modules_all_name,$balanced_modules_in);
         
  html_print_select ($balanced_modules_all_diff, 'name_modules[]', 0, false, '', '', false, true, true, '', false, 'width: 100%;', $option_style);
  
  echo "</td>";
  
  echo "<td class='datos'>";
  echo "<br />";
  html_print_image ('images/darrowright.png', false, array ('id' => 'module_right', 'title' => __('Add modules to cluster')));
  echo "<br /><br /><br />";
  html_print_image ('images/darrowleft.png', false, array ('id' => 'module_left', 'title' => __('Drop modules to cluster')));
  echo "<br /><br /><br />";
  echo "</td>";
  
  echo "<td class='datos'>";
  
  html_print_select ($balanced_modules_in, 'name_modules2[]', 0, false, '', '', false, true, true, '', false, 'width: 100%;', $option_style);
  
  echo "</td>";
  echo "</tr>";
  echo "</table>";

  if(!$update){
    echo "<div style='width:50%'><input style='float:right;' type=submit name='store' class='sub upd' value='".__('Update and Next')."'></div>";
  }
  else{
    echo "<div style='width:50%'><input style='float:right;' type=submit name='store' class='sub upd' value='".__('Update and view cluster')."'></div>";
  }
  
  
  echo "</form>";
  
}
elseif ($step == 6) {
  
  $cluster_agents_in_id = agents_get_cluster_agents_id($id_cluster);
  
  $serialize_agents = '';
  
  foreach ($cluster_agents_in_id as $value) {
    
    if ($value === end($cluster_agents_in_id)) {
        $serialize_agents .= $value;
    }
    else{
      $serialize_agents .= $value.',';
    }
    
  }
  
  $balanced_modules_critical = items_get_cluster_items_id_critical($id_cluster);
  
  if(!$update){
    echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&step=6&assign_critical=1&id_cluster=".$id_cluster."'>";
  }
  else{
    echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&step=6&assign_critical=1&id_cluster=".$id_cluster."&update=1'>";
  }
  

  echo "<h2><b>".__('Balanced modules settings')."</b></h2>";

  echo "<table width='40%' cellpadding=4 cellspacing=4 class='databox filters'>";
    
  echo "<tr>";
  echo "<th><b>".__('Balanced module')."</b></th>";
  echo "<th><b>".__('is critical module')."</b></th>";
  echo "<th><b>".__('Actions')."</b></th>";
  echo "</tr>";
  
  foreach ($balanced_modules_critical as $key => $value) {
    echo "<tr>";
    echo "<td class='datos'>".items_get_name($key)."</td>";
    
    
    if($value){
      echo "<td class='datos'><input class='is_critical_check' name='is_critical_item_".$key."' type='checkbox' checked value='1'></td>";
    }
    else{
      echo "<td class='datos'><input class='is_critical_check' name='is_critical_item_".$key."' type='checkbox' value='1'></td>";
    }
    
    if(!$update){
      echo "<td class='datos'><a href='index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&delete_module_ap=".$key."&id_cluster=".$id_cluster."'><img src='images/cross.png'></a></td>";
    }
    else{
      echo "<td class='datos'><a href='index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&delete_module_ap=".$key."&id_cluster=".$id_cluster."&update=1'><img src='images/cross.png'></a></td>";
    }
    
    
    echo "</tr>";
    
  }
  
  echo "</table>";
  
  if(!$update){
    echo "<div style='width:40%'><input style='float:right;' type=submit name='store' class='sub upd' value='".__('Update and Finish')."'></div>";
  }
  else{
    echo "<div style='width:40%'><input style='float:right;' type=submit name='store' class='sub upd' value='".__('Update and view cluster')."'></div>";
  }
  
  
  echo "</form>";
  
  if(!$update){
    echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&step=5&id_cluster=".$id_cluster."'>";
  }
  else{
    echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&step=5&id_cluster=".$id_cluster."&update=1'>";
  } 
  
  echo "<div style='width:50%'><input style='float:left;' type=submit name='add' class='sub add' value='".__('Add')."'></div>";
  
  echo "</form>";
}

?>

<script type="text/javascript">
	
	
	// function filterByGroup(idGroup, idSelect) {
	// 	$('#loading_group').show();
	// 	
	// 	$('#id_agents' + idSelect).empty ();
	// 	search = $("#text-agent_filter" + idSelect).val();
	// 	
	// 	jQuery.post (
	// 		<?php
	// 		echo "'" . ui_get_full_url(false, false, false, false) . "'";
	// 		?>
	// 		+ "/ajax.php", {
	// 			"page" : "godmode/groups/group_list",
	// 			"get_group_agents" : 1,
	// 			"search" : search,
	// 			"id_group" : idGroup,
	// 			// Add a key prefix to avoid auto sorting in js object conversion
	// 			"keys_prefix" : "_",
	// 			// Juanma (22/05/2014) Fix: Dont show void agents in template wizard
	// 			"show_void_agents" : 0
	// 		},
	// 		function (data, status) {
	// 			
	// 			var group_agents = new Array();
	// 			var group_agents_keys = new Array();
	// 			
	// 			jQuery.each (data, function (id, value) {
	// 				// Remove keys_prefix from the index
	// 				id = id.substring(1);
	// 				
	// 				group_agents.push(value);
	// 				group_agents_keys.push(id);
	// 			});
	// 			
	// 			if(idSelect == '') {
	// 				agents_out_keys = group_agents_keys; 
	// 				agents_out = group_agents; 
	// 			}
	// 			else {
	// 				agents_in_keys = group_agents_keys; 
	// 				agents_in = group_agents; 
	// 			}
	// 			
	// 			refresh_agents($("#text-agent_filter"+idSelect).attr('value'), agents_out_keys, agents_out, $("#id_agents"+idSelect), <?php if (defined('METACONSOLE')) echo 1; else echo 0; ?>);		
	// 		},
	// 		"json"
	// 	);
	// }
	
	function filterByGroupMetaconsole(groupName, idSelect) {
		$('#loading_group_filter_group').show();
		
		$('#id_agents'+idSelect).empty ();
		search = $("#text-agent_filter"+idSelect).val();
		
		jQuery.post (<?php echo "'" . ui_get_full_url(false, false, false, false) . "'"; ?> + "/ajax.php",
			{
				"page" : "enterprise/meta/include/ajax/wizard.ajax",
				"action" : "get_group_agents",
				"separator" : "|",
				"only_meta" : 0,
				"agent_search" : search,
				"no_filter_tag" : true,
				"acl_access": "AR",
				<?php
				if ($strict_user)	
					echo '"id_group" : groupName';
				else
					echo '"group_name" : groupName';
				?>
			},
			function (data, status) {
				$('#loading_group_filter_group').hide();
				
				var group_agents = new Array();
				var group_agents_keys = new Array();
				
				jQuery.each (data, function (id, value) {
					group_agents.push(value);
					group_agents_keys.push(id);
				});
				
				if(idSelect == '') {
					agents_out_keys = group_agents_keys; 
					agents_out = group_agents; 
				}
				else {
					agents_in_keys = group_agents_keys; 
					agents_in = group_agents; 
				}
				
				refresh_agents($("#text-agent_filter"+idSelect).attr('value'), agents_out_keys, agents_out, $("#id_agents"+idSelect), <?php if (is_metaconsole()) echo 1; else echo 0; ?>);		
			},
			"json"
		);
	}
	
	function refresh_agents(start_search, keys, values, select, metaconsole) {
		var n = 0;
		var i = 0;
		select.empty();
		
		// Fix: Remove agents inside the template from agent selector		
		$('#id_agents2 option').each(function(){
			
			var out_agent = $(this).val();
			
			if (metaconsole) {
				var out_split = out_agent.split('|');
				
				if (out_split[0].length > 0)
					var out_agent = out_split[0] + '|' + out_split[1];
			}
			
			if (out_agent) {
				
				keys.forEach(function(it) {
					
					var it_data = it;
					if (metaconsole) {
						var it_split = it.split('|');
						var it_data = it_split[0] + '|' + it_split[1];
					}
					
					if (it_data == out_agent) {
						
						var index = keys.indexOf(it);
						
						// Remove from array!
						values.splice(index, 1);
						keys.splice(index, 1);
						
						
					}
					
				});
				
			}
			
		});
		
		values.forEach(function(item) {
			var re = new RegExp(start_search,"gi");
			
			match = item.match(re);
			
			if (match != null) {
				select.append ($("<option></option>").attr("value", keys[n]).html(values[n]));
				i++;
			}
			n++;
		});
		if (i == 0) {
			$(select).empty ();
			$(select).append ($("<option></option>").attr ("value", 0).html ('<?php echo __('None');?>'));
		}
		
		$('.loading_div').hide();
	}
	
	$(document).ready (function () {
		// if ($('#hidden-metaconsole_activated').val() == 1) {
		// 	filterByGroupMetaconsole($("#group").val(), '');
		// }
		// else {
		// 	filterByGroup($("#group").val(), '');
		// }
		// 
		// $("select[name='group']").change(function(){
		// 	if ($('#hidden-metaconsole_activated').val() == 1) {
		// 		filterByGroupMetaconsole($(this).val(), '');
		// 	}
		// 	else {
		// 		filterByGroup($(this).val(), '');
		// 	}
		// });
		
		$("#text-agent_filter").keyup (function () {
			$('#loading_filter').show();
			refresh_agents($(this).val(), agents_out_keys, agents_out, $("#id_agents"), <?php if (is_metaconsole()) echo 1; else echo 0; ?>);
		});
		
		$("#select_all_left_agents").click(function () {
			$('#id_agents option').map(function() {
				$(this).prop('selected', true);
			});
			
			return false;
		});
		$("#select_all_right_agents").click(function () {
			$('#id_agents2 option').map(function() {
				$(this).prop('selected', true);
			});
			
			return false;
		});
    
    $("#select_all_left_modules").click(function () {
			$('#name_modules option').map(function() {
				$(this).prop('selected', true);
			});
			
			return false;
		});
		$("#select_all_right_modules").click(function () {
			$('#name_modules2 option').map(function() {
				$(this).prop('selected', true);
			});
			
			return false;
		});
		
		$("#agent_right").click (function () {
			jQuery.each($("select[name='id_agents[]'] option:selected"), function (key, value) {
				
				
				agent_name = $(value).html();
				if (agent_name != <?php echo "'".__('None')."'"; ?>){
					id_agent = $(value).attr('value');
					
					//Remove the none value
					$("#id_agents2").find("option[value='']").remove();
					
					$("select[name='id_agents2[]']").append($("<option>").val(id_agent).html('<i>' + agent_name + '</i>'));
					$("#id_agents").find("option[value='" + id_agent + "']").remove();
				}
			});
		});
		
		$("#agent_left").click(function() {
			jQuery.each($("select[name='id_agents2[]'] option:selected"), function (key, value) {
				agent_name = $(value).html();
				if (agent_name != <?php echo "'".__('None')."'"; ?>){
					id_agent = $(value).attr('value');
					$("select[name='id_agents[]']").append($("<option>").val(id_agent).html('<i>' + agent_name + '</i>'));
					$("#id_agents2").find("option[value='" + id_agent + "']").remove();
				}
				
				//If empty the selectbox
				if ($("#id_agents2 option").length == 0) {
					$("select[name='id_agents2[]']")
						.append($("<option>").val("")
						.html("<?php echo __('None'); ?>"));
				}
			});
		});
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    $("#module_right").click (function () {
            
      jQuery.each($("select[name='name_modules[]'] option:selected"), function (key, value) {
        module_name = $(value).html();
        if (module_name != <?php echo "'".__('None')."'"; ?>){
          name_module = $(value).attr('value');
          
          //Remove the none value
          $("#name_modules2").find("option[value='']").remove();
          
          $("select[name='name_modules2[]']").append($("<option>").val(name_module).html('<i>' + module_name + '</i>'));
          $("#name_modules").find("option[value='" + name_module + "']").remove();
        }
      });
    });
    
    $("#module_left").click(function() {
      jQuery.each($("select[name='name_modules2[]'] option:selected"), function (key, value) {
        module_name = $(value).html();
        if (module_name != <?php echo "'".__('None')."'"; ?>){
          name_module = $(value).attr('value');
          $("select[name='name_modules[]']").append($("<option>").val(name_module).html('<i>' + module_name + '</i>'));
          $("#name_modules2").find("option[value='" + name_module + "']").remove();
        }
    
    //If empty the selectbox
    if ($("#name_modules2 option").length == 0) {
      $("select[name='name_modules2[]']")
        .append($("<option>").val("")
        .html("<?php echo __('None'); ?>"));
    }
  });
});
    
    
    
    
     $( "#form_step_2" ).submit(function( event ) {
       
       if($( "#id_agents2 option" ).length < 2){
         alert( <?php echo "'" . __('Select at least two agents ') . "'"; ?> );
        event.preventDefault();        
       }
       else if($( "#id_agents2 option" ).val() == ''){
         alert( <?php echo "'" . __('Please set agent distinct than ') . '"' . __('None') . '"' . "'"; ?> );
        event.preventDefault(); 
       }
       else{
         $("#id_agents2>option").prop("selected", true);
       }
     }); 
     
     
     $( "#form_step_3" ).submit(function( event ) {
          $("#name_modules2>option").prop("selected", true);
     }); 
         
         
     $(".zero_hundred").keydown(function( event ) {
       input_zero_hundred = $(this).val();
     });
     
     $(".zero_hundred").keyup(function( event ) {
       if($(this).val() > 100){
         $(this).val(100);
         return false;
       }
       else if ($(this).val() < 0) {
         $(this).val(0);
         return false;
       }
     }); 
     
     
    //  $('.is_critical_check').click(function(){
    //    
    //   
    //  });
              
        $('.check_agent').keyup(function(event){
            
          if($(this).val() == ''){
            $('.create_agent_check').prop('disabled',true);
            $('.check_image_agent').attr('src','images/error_1.png');
            $('.check_image_label').html('Should not be empty');
          }
          else{
            $.ajax({
            type: "POST",
            url: "ajax.php",
            data: {"page" : "godmode/reporting/cluster_agent_check",
            "name_agent" : $(this).val(),
            },
            success: function(data) {
              if(data == 0){
                console.log(data);
                $('.create_agent_check').prop('disabled',false);
                $('.check_image_agent').attr('src','images/exito.png');
                $('.check_image_label').html('');
              }
              else{
                console.log(data);
                $('.create_agent_check').prop('disabled',true);
                $('.check_image_agent').attr('src','images/error_1.png');       
                $('.check_image_label').html('Agent name alredy exists');         
              }
            }
            });
          }
            
        });
        
        
         
         
	});
</script>
