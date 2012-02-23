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

// Login check
check_login ();

if (! check_acl ($config['id_user'], 0, "IW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access template wizard");
	require ("general/noaccess.php");
	exit;
}

$id_template = get_parameter('id_template');
$agent_filter = (string) get_parameter ('agent_filter', '');
$agent_filter2 = (string) get_parameter ('agent_filter2', '');
$template_selected = get_parameter ('template_selected');
$cleanup_template = get_parameter('cleanup_template', 0);
$action_wizard = get_parameter ('action_wizard');
$template = get_parameter('templates');

if (is_ajax()) {
	$cleanup_template = get_parameter('cleanup_template', 0);
	$id_template_cleanup = get_parameter('id_template_cleanup');

	// Cleanup applied template from database
	if ($cleanup_template){
		$sql = "SELECT id_graph FROM tgraph WHERE id_graph_template=$id_template_cleanup";
		$id_graphs = db_get_all_rows_sql($sql);
		
		$result = db_process_sql_delete('tgraph', array('id_graph_template' => $id_template_cleanup));
		
		if($result) {
			foreach ($id_graphs as $id_graph) {
				$result_aux = db_process_sql_delete('tgraph_source', array('id_graph' => $id_graph));
			}
			echo 1;
		} else {
			echo 0;
		}	
	}

	return;
}

// Result for cleanup functionality
echo '<div id="sucess_cleanup" style="display:none">'; 
		ui_print_success_message(__('Cleanup sucessfully'));
echo '</div>';	
echo '<div id="wrong_cleanup" style="display:none">';
		ui_print_error_message(__('Cleanup error'));
echo '</div>';

$buttons['template_list'] = '<a href="index.php?sec=greporting&sec2=godmode/reporting/graph_template_list">'
		. html_print_image ("images/god6.png", true, array ("title" => __('Template list')))
		. '</a>';
		
// Header
ui_print_page_header (__('Wizard template'), "", false, "", true, $buttons);

// Apply templates action
if ($action_wizard == 'apply') {
	$agents_selected = (array) get_parameter ('id_agents2');
	$template_selected = get_parameter('templates');

	if (empty($agents_selected) || empty($template_selected))
		$result = false;
	else {
		$result = reporting_apply_report_template_graph($agents_selected, $template_selected);
	}
}

?>
<table style="" class="databox" id="" border="0" cellpadding="4" cellspacing="4" width="98%">
<tbody>
	<tr style="" class="datos">
		<td>
				<?php 
					echo '<form method="post" action="index.php?sec=greporting&sec2=godmode/reporting/graph_template_wizard&action=wizard&id_template=' . $id_template . '">';

					// List all available templates
					$own_info = get_user_info ($config['id_user']);
					if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM"))
						$return_all_group = true;
					else
						$return_all_group = false;
					
					$templates = reporting_template_graphs_get_user ($config['id_user'], false, true, 'IW');
					
					if ($templates === false)
						$template_select = array();
					else{
						foreach ($templates as $template){
							$template_select[$template['id_graph_template']] = $template['name'];
						}
					}
				
					echo  __('Templates') . "&nbsp;&nbsp;&nbsp;&nbsp;";
					html_print_select($template_select, 'templates', $template_selected, '', __('None'), '0', false, false, true, '', false, 'width:180px;');
					echo "&nbsp;&nbsp;";
					echo '<a id="cleanup_template" href="index.php?sec=greporting&sec2=godmode/reporting/graph_template_wizard&cleanup_template=1&id_template=' . $id_template .'">'; 
					
					html_print_image ('images/clean.png', false, array ('title' => __('Clean up template')));
					echo '</a>';
				?>
		</td>
		<td></td>
	</tr>

	<tr style="" class="datos">
		<td>
				<?php
					echo __('Filter group') . "&nbsp;&nbsp;";
					html_print_select(groups_get_all(), 'group', '', "", __('All'), '0', false, false, true, '', false, 'width:180px;'); 
					echo "<div id='loading_group' class='loading_div' style='display:none; float:left;'><img src='images/spinner.gif'></div>";
				?>
		</td>

	</tr>
	<tr style="" class="datos">
		<td>
				<?php
					echo __('Filter agent') . "&nbsp;&nbsp;&nbsp;";
					html_print_input_text ('agent_filter', $agent_filter, '', 22, 150);
					echo "<div id='loading_filter' class='loading_div' style='display:none; float:left;'><img src='images/spinner.gif'></div>";
				?>
		</td>

	</tr>	
	
	<tr style="" class="datos">
		<td>
				<?php
					echo "<b>" . __('Agents available')."</b>&nbsp;&nbsp;" . html_print_submit_button (__('Select all'), 'select_all', false, 'class="sub upd"', true);
				?>
		</td>
		<td></td>
		<td>
				<?php
					echo "<b>" . __('Agents to apply')."</b>&nbsp;&nbsp;" . html_print_submit_button (__('Select all'), 'select_all2', false, 'class="sub upd"', true);
				?>
		</td>
	</tr>		
	
	<tr style="" class="datos">
		<td>
		<?php 
			$option_style = array();
			/* This will keep agents that will be added to the template */ 
			$template_agents_in = array();
			
			$template_agents_all = agents_get_group_agents(0, false, '');
			$template_agents_out = array();
			$template_agents_out = array_diff_key($template_agents_all, $template_agents_in);
			
			$template_agents_in_keys = array_keys($template_agents_in);
			$template_agents_out_keys = array_keys($template_agents_out);
			
			html_print_select ($template_agents_out, 'id_agents[]', 0, false, '', '', false, true, true, '', false, 'width: 100%;', $option_style);
		?>
		</td>
		<td>
		<br>
		<?php 
			html_print_image ('images/darrowright.png', false, array ('id' => 'right', 'title' => __('Add agents to template')));
		?>
		<br><br><br><br>
		<?php 
			html_print_image ('images/darrowleft.png', false, array ('id' => 'left', 'title' => __('Undo agents to template')));
		?>	
		<br><br><br>
		</td>
		<td>
		<?php
			$option_style = array();
			/* Agents applied to the template */
			html_print_select ($template_agents_in, 'id_agents2[]', 0, false, '', '', false, true, true, '', false, 'width: 100%;', $option_style);
		?>
		</td>
	</tr>

</tbody>
</table>

<?php 

echo '<div class="action-buttons" style="width: 98%;">';
html_print_input_hidden('action_wizard', 'apply');
html_print_submit_button (__('Apply template'), 'apply', false, 'class="sub next"');
echo '</div>';
echo '</form>';

// Choose a weird separator
$separator = ';;..;;..;;';

html_print_input_hidden('separator', $separator);
html_print_input_hidden('agents_in', implode($separator, $template_agents_in));
html_print_input_hidden('agents_in_keys', implode($separator, $template_agents_in_keys));
html_print_input_hidden('agents_out', implode($separator, $template_agents_out));
html_print_input_hidden('agents_out_keys', implode($separator, $template_agents_out_keys));
?>

<script language="javascript" type="text/javascript">
var agents_out;
var agents_out_keys;
var agents_in;
var pending_delete_ids;
var agents_in_keys;
var separator;

$(document).ready (function () {
	// Get the agents in both sides from the hidden fields
	separator = $("#hidden-separator").attr('value');
	var aux;
	aux = $("#hidden-agents_in").attr('value');
	agents_in = aux.split(separator);
	aux = $("#hidden-agents_in_keys").attr('value');
	agents_in_keys = aux.split(separator);
	aux = $("#hidden-agents_out").attr('value');
	agents_out = aux.split(separator);
	aux = $("#hidden-agents_out_keys").attr('value');
	agents_out_keys = aux.split(separator);
	
	$("select[name='group']").change(function(){
		filterByGroup($(this).val(), '');
	});
	
	$("select[name='group2']").change(function(){
		filterByGroup($(this).val(), '2');
	});
	
	function filterByGroup(idGroup, idSelect) {
		$('#loading_group'+idSelect).show();

		$('#id_agents'+idSelect).empty ();
		search = $("#text-agent_filter"+idSelect).val();

		jQuery.post ("ajax.php",
				{"page" : "godmode/groups/group_list",
				"get_group_agents" : 1,
				"search" : search,
				"id_group" : idGroup
				},
				function (data, status) {			
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
					
					refresh_agents($("#text-agent_filter"+idSelect).attr('value'), agents_out_keys, agents_out, $("#id_agents"+idSelect));		
				},
				"json"
			);	
	}		
	
	$("#group").click (function () {
		$(this).css ("width", "auto"); 
	});
			
	$("#group").blur (function () {
		$(this).css ("width", "180px"); 
	});	
	
	$("#group2").click (function () {
		$(this).css ("width", "auto"); 
	});
			
	$("#group2").blur (function () {
		$(this).css ("width", "180px"); 
	});	
	
	function refresh_agents(start_search, keys, values, select) {
		var n = 0;
		var i = 0;
		select.empty();

		values.forEach(function(item) {
			var re = new RegExp(start_search,"gi");
			match = item.match(re);

			if(match != null) {
				select.append ($("<option></option>").attr("value", keys[n]).html(values[n]));
				i++;
			}
			n++;
		});
		if(i == 0) {
			$(select).empty ();
			$(select).append ($("<option></option>").attr ("value", 0).html ('<?php echo __('None');?>'));
		}
		
		$('.loading_div').hide();
	}
	
	$("#text-agent_filter").keyup (function () {
		$('#loading_filter').show();
		refresh_agents($(this).val(), agents_out_keys, agents_out, $("#id_agents"));
	});	
	
	$("#text-agent_filter2").keyup (function () {
		$('#loading_filter2').show();
		refresh_agents($(this).val(), agents_in_keys, agents_in, $("#id_agents2"));
	});	
	
	$("#right").click (function () {
		jQuery.each($("select[name='id_agents[]'] option:selected"), function (key, value) {
				agent_name = $(value).html();
				if (agent_name != <?php echo "'".__('None')."'"; ?>){
					id_agent = $(value).attr('value');
					$("select[name='id_agents2[]']").append($("<option>").val(id_agent).html('<i>' + agent_name + '</i>'));
					$("#id_agents").find("option[value='" + id_agent + "']").remove();
				}
		});			
	});

	$("#left").click(function(){
		jQuery.each($("select[name='id_agents2[]'] option:selected"), function (key, value) {
				agent_name = $(value).html();
				if (agent_name != <?php echo "'".__('None')."'"; ?>){
					id_agent = $(value).attr('value');
					$("select[name='id_agents[]']").append($("<option>").val(id_agent).html('<i>' + agent_name + '</i>'));
					$("#id_agents2").find("option[value='" + id_agent + "']").remove();
				}
		});				
	});

	$("#submit-apply").click(function () {
		$('#id_agents2 option').map(function(){
			$(this).attr('selected','selected');
		});
		
		//Prevent from applying template 'None' over agent 	
		if ($("#templates").val() == 0){
			alert( <?php echo "'" . __('Please set template distinct than ') . '"' . __('None') . '"' . "'"; ?> );
			return false;
		}		
		
		if (!confirm ( <?php echo "'" . __('Are you sure?') . "'"; ?> ))
			return false;
		
	});	
	
	$("#submit-select_all").click(function () {
		$('#id_agents option').map(function(){
			$(this).attr('selected','selected');
		});
		
		return false;
	});	
	
	$("#submit-select_all2").click(function () {
		$('#id_agents2 option').map(function(){
			$(this).attr('selected','selected');
		});
		
		return false;
	});		
	
	$("#cleanup_template").click(function () {
		// Prevent user of current action
		if (! confirm ( <?php echo "'" . __('This will be delete all reports created in previous template applications. Do you want to continue?') . "'"; ?> )) 
			return false;		
		
		// Prevent from applying template 'None' over agent 	
		if ($("#templates").val() == 0){
			alert( <?php echo "'" . __('Please set template distinct than ') . '"' . __('None') . '"' . "'"; ?> );
			return false;
		}
		
		// Cleanup applied template
		var params = [];
		var result;
		params.push("cleanup_template=1");
		params.push("id_template_cleanup=" + $("#templates").val());
		params.push("page=godmode/reporting/graph_template_wizard");
		jQuery.ajax ({
			data: params.join ("&"),
			type: 'POST',
			url: action="ajax.php",
			async: false,
			timeout: 10000,
			success: function (data) {
				result = data;
				
				if (result == 1)
					$("#sucess_cleanup").css("display", "");
				else
					$("#wrong_cleanup").css("display", "");
			}
		});
		
		return false;
					
	});			
		
});

</script>
