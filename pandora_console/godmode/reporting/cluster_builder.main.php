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
  
  echo "<form method='post' id='form_step_2' action='index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&step=2&assign_agents=1&id_cluster=".$id_cluster."'>";

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
  $cluster_agents_in = agents_get_cluster_agents($id_cluster);    
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

  echo "<div style='width:50%'><input style='float:right;' type=submit name='store' class='sub upd' value='".__('Update and go next')."'></div>";
  echo "</form>";
  
}
elseif ($step == 3) {
  
  echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/cluster_builder&step=3&assign_modules=1&id_cluster=".$id_cluster."'>";

  echo "<table width='50%' cellpadding=4 cellspacing=4 class='databox filters'>";

  echo "<tr>";
  echo "<td class='datos'><b>".__('Cluster name')."</b></td>";
  echo "<td class='datos'>".clusters_get_name($id_cluster)."</td>";
  echo "</tr>";
  
  echo "<tr>";
  echo "<td class='datos'><b>".__('Adding common modules')."</b></td>";
  echo "</tr>";
  
  echo "<tr>";
  echo "<td  class='datos'>";
    // $attr = array('id' => 'image-select_all_available', 'title' => __('Select all'), 'style' => 'cursor: pointer;');
  echo "<b>" . __('Common in agents')."</b>&nbsp;&nbsp;" . html_print_image ('images/tick.png', true, $attr, false, true);
  echo "</td>";
  
  echo "<td class='datos'>";
  echo "</td>";
  
  echo "<td  class='datos'>";
    // $attr = array('id' => 'image-select_all_apply', 'title' => __('Select all'), 'style' => 'cursor: pointer;');
  echo "<b>" . __('Added common modules')."</b>&nbsp;&nbsp;" . html_print_image ('images/tick.png', true, $attr, false, true);
  echo "</td>";
  echo "<tr>";
  echo "<td class='datos'>";
  
  $cluster_agents_in = agents_get_cluster_agents($id_cluster);
  
  //  $cluster_modules_all = db_get_all_rows_sql('SELECT DISTINCT nombre, id_agente_modulo
  //       FROM tagente_modulo t1
  //       WHERE  t1.delete_pending = 0
  //         AND t1.id_agente IN ()
  //         AND (
  //             SELECT count(nombre)
  //             FROM tagente_modulo t2
  //             t1.nombre = t2.nombre
  //             AND t2.id_agente IN () = (' . count($cluster_agents_in) . ')
  //             ORDER BY nombre');
    
  
  html_print_select ($cluster_modules_all, 'id_modules[]', 0, false, '', '', false, true, true, '', false, 'width: 100%;', $option_style);
  
  echo "</td>";
  
  echo "<td class='datos'>";
  echo "<br />";
  html_print_image ('images/darrowright.png', false, array ('id' => 'right', 'title' => __('Add modules to cluster')));
  echo "<br /><br /><br />";
  html_print_image ('images/darrowleft.png', false, array ('id' => 'left', 'title' => __('Drop modules to cluster')));
  echo "<br /><br /><br />";
  echo "</td>";
  
  echo "<td class='datos'>";
  
  html_print_select ($cluster_modules_in, 'id_agents2[]', 0, false, '', '', false, true, true, '', false, 'width: 100%;', $option_style);
  
  echo "</td>";
  echo "</tr>";
  echo "</table>";

  echo "<div style='width:50%'><input style='float:right;' type=submit name='store' class='sub upd' value='".__('Update and Next')."'></div>";
  echo "</form>";
      
}
elseif (condition) {
  html_debug('paso 4');
}

?>

<script type="text/javascript">
	
	
	function filterByGroup(idGroup, idSelect) {
		$('#loading_group').show();
		
		$('#id_agents' + idSelect).empty ();
		search = $("#text-agent_filter" + idSelect).val();
		
		jQuery.post (
			<?php
			echo "'" . ui_get_full_url(false, false, false, false) . "'";
			?>
			+ "/ajax.php", {
				"page" : "godmode/groups/group_list",
				"get_group_agents" : 1,
				"search" : search,
				"id_group" : idGroup,
				// Add a key prefix to avoid auto sorting in js object conversion
				"keys_prefix" : "_",
				// Juanma (22/05/2014) Fix: Dont show void agents in template wizard
				"show_void_agents" : 0
			},
			function (data, status) {
				
				var group_agents = new Array();
				var group_agents_keys = new Array();
				
				jQuery.each (data, function (id, value) {
					// Remove keys_prefix from the index
					id = id.substring(1);
					
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
				
				refresh_agents($("#text-agent_filter"+idSelect).attr('value'), agents_out_keys, agents_out, $("#id_agents"+idSelect), <?php if (defined('METACONSOLE')) echo 1; else echo 0; ?>);		
			},
			"json"
		);
	}
	
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
		if ($('#hidden-metaconsole_activated').val() == 1) {
			filterByGroupMetaconsole($("#group").val(), '');
		}
		else {
			filterByGroup($("#group").val(), '');
		}
		
		$("select[name='group']").change(function(){
			if ($('#hidden-metaconsole_activated').val() == 1) {
				filterByGroupMetaconsole($(this).val(), '');
			}
			else {
				filterByGroup($(this).val(), '');
			}
		});
		
		$("#text-agent_filter").keyup (function () {
			$('#loading_filter').show();
			refresh_agents($(this).val(), agents_out_keys, agents_out, $("#id_agents"), <?php if (is_metaconsole()) echo 1; else echo 0; ?>);
		});
		
		$("input[name='select_all_left']").click(function () {
			$('#id_agents option').map(function() {
				$(this).prop('selected', true);
			});
			
			return false;
		});
		$("input[name='select_all_right']").click(function () {
			$('#id_agents2 option').map(function() {
				$(this).prop('selected', true);
			});
			
			return false;
		});
		
		$("#right").click (function () {
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
		
		$("#left").click(function() {
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
    
     $( "#form_step_2" ).submit(function( event ) {
       if($( "#id_agents2 option" ).val() == ''){
         alert( <?php echo "'" . __('Please set agent distinct than ') . '"' . __('None') . '"' . "'"; ?> );
        event.preventDefault(); 
       }
       else{
         $("#id_agents2>option").prop("selected", true);
       }
     });     
	});
</script>
