<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

include_once($config['homedir'] . "/include/functions_agents.php");
include_once($config['homedir'] . "/include/functions_modules.php");
include_once($config['homedir'] . '/include/functions_users.php');

function mainAgentsAlerts() {
	global $config;
	
	// Load global vars
	require_once ("include/config.php");
	require_once ("include/functions_reporting.php");
	
	check_login ();
	// ACL Check
	if (! check_acl ($config['id_user'], 0, "AR")) {
		db_pandora_audit("ACL Violation", 
		"Trying to access Agent view (Grouped)");
		require ("general/noaccess.php");
		exit;
	}
	
	// Update network modules for this group
	// Check for Network FLAG change request
	// Made it a subquery, much faster on both the database and server side
	if (isset ($_GET["update_netgroup"])) {
		$group = get_parameter_get ("update_netgroup", 0);
		if (check_acl ($config['id_user'], $group, "AW")) {
			$where = array("id_agente" => "ANY(SELECT id_agente FROM tagente WHERE id_grupo = " . $group);
			
			db_process_sql_update('tagente_modulo', array("flag" => 1), $where);
		}
		else {
			db_pandora_audit("ACL Violation", "Trying to set flag for groups");
			require ("general/noaccess.php");
			exit;
		}
	}
	
	if ($config["realtimestats"] == 0) {
		$updated_info = __('Last update'). " : ". ui_print_timestamp (db_get_sql ("SELECT min(utimestamp) FROM tgroup_stat"), true);
	}
	else {
	//	$updated_info = __("Updated at realtime");
		$updated_info = "";
	}
	
	$updated_time = $updated_info;
	$create_alert = (int)get_parameter ("create_alert",0);
	
	if($create_alert){
		$template2 = get_parameter("template");
		$module_action_threshold = get_parameter("module_action_threshold");
		
		$id_alert = alerts_create_alert_agent_module ($create_alert, $template2);
		
		if ($id_alert !== false) {
			$action_select = get_parameter("action_select",0);
			
			if ($action_select != 0) {
				$values = array();
				$values['fires_min'] = 0;
				$values['fires_max'] = 0;
				$values['module_action_threshold'] =
					(int)get_parameter ('module_action_threshold');
				
				alerts_add_alert_agent_module_action ($id_alert, $action_select, $values);
			}
		}
		
	}
	$refr = get_parameter('refr', 30); // By default 30 seconds
	$show_modules = (bool) get_parameter ("show_modules",0);
	$group_id = get_parameter('group_id', 0);
	$offset = get_parameter('offset', 0);
	$hor_offset = get_parameter('hor_offset', 0);
	$block = 20;
	
	$groups = users_get_groups ();
	
	$filter_groups .= '<b>'.__('Group').'</b>';
	$filter_groups .= html_print_select_groups(false, "AR", true, 'group_id', $group_id, false, '', '', true, false, true, '', false , 'width: 100px; margin-right: 10px;; margin-top: 5px;');
	
	$check = '<b>'.__('Show modules without alerts').'</b>';
	$check .= html_print_checkbox('slides_ids[]', $d['id'], $show_modules, true, false, '', true);
	
	
	$comborefr = '<form method="post" action="' . ui_get_url_refresh (array ('offset' => 0, 'hor_offset' => 0)).'">';
	$comborefr .= '<b>'.__('Refresh').'</b>';
	$comborefr .= html_print_select (
		array('30' => '30 '.__('seconds'),
			(string)SECONDS_1MINUTE => __('1 minute'),
			(string)SECONDS_2MINUTES => __('2 minutes'),
			(string)SECONDS_5MINUTES => __('5 minutes'),
			(string)SECONDS_10MINUTES => __('10 minutes'))
			, 'refr', (int)get_parameter('refr', 0), $script = 'this.form.submit()', '', 0, true, false, false, '', false, 'width: 100px; margin-right: 10px; margin-top: 5px;');
	$comborefr .= "</form>";
	
	if ($config["pure"] == 0) {
		$fullscreen['text'] = '<a href="'.ui_get_url_refresh(array ('pure' => 1)).'">'
			. html_print_image ("images/full_screen.png", true, array ("title" => __('Full screen mode')))
			. "</a>";
	}
	else {
		$fullscreen['text'] = '<a href="'.ui_get_url_refresh(array ('pure' => 0)).'">'
			. html_print_image ("images/normal_screen.png", true, array ("title" => __('Back to normal mode')))
			. "</a>";
		$config['refr'] = $refr;
	}
	
	$onheader = array('updated_time' => $updated_time, 'fullscreen' => $fullscreen, 
		'combo_groups' => $filter_groups);
	
	if ($config['pure'] == 1) {
		$onheader['combo_refr'] = $comborefr;
	}
	
	// Header
	ui_print_page_header (__("Agents/Alerts"), "images/op_alerts.png", false, "", false, $updated_time);
	
	// Old style table, we need a lot of special formatting,don't use table function
	// Prepare old-style table
	echo '<table class="databox filters" cellpadding="0" cellspacing="0" border="0" style="width:100%;">';
	echo "<tr>";
	echo "<td>" . $filter_groups  . "</td>";
	echo "<td>" . $check  . "</td>";
	if ($config['pure'] == 1) 
		echo "<td>" . $comborefr  . "</td>";
	echo "<td> <strong>" . __("Full screen") . "</strong>" . $fullscreen['text'] . "</td>";
	echo "</tr>";
	echo "</table>";
	
	if($show_modules){
		
		
		if($group_id > 0){
			$grupo = " AND tagente.id_grupo = $group_id";
		} else {
			$grupo ='';
		}
		
		$offset_modules = get_parameter ("offset",0);
		$sql_count = "SELECT COUNT(tagente_modulo.nombre) FROM tagente_modulo 
		INNER JOIN tagente ON tagente.id_agente = tagente_modulo.id_agente
		WHERE id_agente_modulo NOT IN (SELECT id_agent_module FROM talert_template_modules) 
		$grupo";
		$count_agent_module = db_get_all_rows_sql($sql_count);
		
		$sql = "SELECT tagente.alias, tagente_modulo.nombre, 
		tagente_modulo.id_agente_modulo FROM tagente_modulo 
		INNER JOIN tagente ON tagente.id_agente = tagente_modulo.id_agente
		WHERE id_agente_modulo NOT IN (SELECT id_agent_module FROM talert_template_modules) 
		$grupo LIMIT 20 OFFSET $offset_modules";
		$agent_modules = db_get_all_rows_sql($sql);
		
		ui_pagination ($count_agent_module[0]['COUNT(tagente_modulo.nombre)'],
		ui_get_url_refresh(),0,0,false,'offset',true,'',
			'',false,'alerts_modules');
		
		$table->width = '100%';
		$table->class = "databox data";
		$table->id = "table_agent_module";
		$table->data = array ();
		
		$table->head[0] = __('Agents');
		$table->head[1] = __('Modules');
		$table->head[2] = __('Actions');
		
		$table->style[0]= 'width: 25%;';
		$table->style[1]= 'width: 33%;';
		$table->style[2]= 'width: 33%;';
		
		foreach($agent_modules as $agent_module) {
			$data[0] = io_safe_output($agent_module['alias']);
			$data[1] = io_safe_output($agent_module['nombre']);
			$uniqid = $agent_module['id_agente_modulo'];
			$data[2] = "<a title='".__('Create alert')."' href='javascript:show_add_alerts(\"$uniqid\")'>".html_print_image('images/add_mc.png', true)."</a>";
			array_push ($table->data, $data);
			
			$table2->width = '100%';
			$table2->id = "table_add_alert";
			$table2->class = 'databox filters';
			$table2->data = array ();
			// $data[0] = 
			$table2->data[0][0] = __('Actions');

			$groups_user = users_get_groups($config["id_user"]);
			if (!empty($groups_user)) {
				$groups = implode(',', array_keys($groups_user));
				$sql = "SELECT id, name FROM talert_actions WHERE id_group IN ($groups)";
				$actions = db_get_all_rows_sql($sql);
			}

			$table2->data[0][1] = html_print_select(
				index_array($actions, 'id', 'name'), 'action_select', '', '',
				__('Default action'), '0', true, '', true, '', false,
				'width: 250px;');
			$table2->data[0][1] .= '<span id="advanced_action" class="advanced_actions invisible"><br>';
			$table2->data[0][1] .= __('Number of alerts match from').' ';
			$table2->data[0][1] .= html_print_input_text ('fires_min', '', '', 4, 10, true);
			$table2->data[0][1] .= ' ' . __('to') . ' ';
			$table2->data[0][1] .= html_print_input_text ('fires_max', '', '', 4, 10, true);
			$table2->data[0][1] .= ui_print_help_icon ("alert-matches", true,
				ui_get_full_url(false, false, false, false));
			$table2->data[0][1] .= '</span>';
			if (check_acl ($config['id_user'], 0, "LM")) {

				$table2->data[0][1] .= '<a style="margin-left:5px;" href="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_action&pure='.$pure.'">';
				$table2->data[0][1] .= html_print_image ('images/add.png', true);
				$table2->data[0][1] .=  '<span style="margin-left:5px;vertical-align:middle;">'.__('Create Action').'</span>';
				$table2->data[0][1] .= '</a>';
			}
			
			$table2->data[1][0] = __('Template');
			$own_info = get_user_info ($config['id_user']);
			if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM"))
				$templates = alerts_get_alert_templates (false, array ('id', 'name'));
			else {
				$usr_groups = users_get_groups($config['id_user'], 'LW', true);
				$filter_groups = '';
				$filter_groups = implode(',', array_keys($usr_groups));
				$templates = alerts_get_alert_templates (array ('id_group IN (' . $filter_groups . ')'), array ('id', 'name'));
			}

			$table2->data[1][1] = html_print_select (index_array ($templates, 'id', 'name'),
				'template', '', '', __('Select'), 0, true, false, true, '', false, 'width: 250px;');
			$table2->data[1][1] .= ' <a class="template_details invisible" href="#">' .
				html_print_image("images/zoom.png", true, array("class" => 'img_help')) . '</a>';
			if (check_acl ($config['id_user'], 0, "LM")) {
				$table2->data[1][1] .= '<a href="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_template&pure='.$pure.'">';
				$table2->data[1][1] .= html_print_image ('images/add.png', true);
				$table2->data[1][1] .=  '<span style="margin-left:5px;vertical-align:middle;">'.__('Create Template').'</span>';
				$table2->data[1][1] .= '</a>';
			}
			$table2->data[2][0] = __('Threshold');
			$table2->data[2][1] = html_print_input_text ('module_action_threshold', '0', '', 5, 7, true);
			$table2->data[2][1] .= ' ' . __('seconds') . ui_print_help_icon ('action_threshold', true);
			
			$content2 = '<form class="add_alert_form" method="post">';
			$content2 .= html_print_table($table2,true);
			
			$content2 .= '<div class="action-buttons" style="width: '.$table2->width.'">';
			$content2 .= html_print_submit_button (__('Add alert'), 'add', false, 'class="sub wand"',true);
			$content2 .= html_print_input_hidden ('create_alert', $uniqid,true);
			$content2 .= '</div></form>';
			
			$module_name = ui_print_truncate_text(io_safe_output($agent_module['nombre']), 40, false, true, false, '&hellip;', false);
			echo '<div id="add_alerts_dialog_'.$uniqid.'" title="'.__('Agent').': '.$agent_module['alias'].' / '.__('module').': '.$module_name.'" style="display:none">'.$content2.'</div>';
		}
		
		html_print_table($table);
		
	} else {
		
		$filter = array ('offset' => (int) $offset,
			'limit' => (int) $config['block_size']);
		$filter_count = array();
		
		if ($group_id > 0) {
			$filter['id_grupo'] = $group_id;
			$filter_count['id_grupo'] = $group_id;
		}
		
		// Get the id of all agents with alerts
		$sql = 'SELECT DISTINCT(id_agente)
			FROM tagente_modulo
			WHERE id_agente_modulo IN
				(SELECT id_agent_module
				FROM talert_template_modules)';
		$agents_with_alerts_raw = db_get_all_rows_sql($sql);
		
		if ($agents_with_alerts_raw === false) {
			$agents_with_alerts_raw = array();
		}
		
		$agents_with_alerts = array();
		foreach ($agents_with_alerts_raw as $awar) {
			$agents_with_alerts[] = $awar['id_agente'];
		}
		
		$filter['id_agente'] = $agents_with_alerts;
		$filter_count['id_agente'] = $agents_with_alerts;
		
		$agents = agents_get_agents ($filter);
		
		$nagents = count(agents_get_agents ($filter_count));
		
		if ($agents == false) {
			ui_print_info_message ( array('no_close'=>true, 'message'=> __('There are no agents with alerts') ) );
			return;
		}
		
		$all_alerts = agents_get_alerts_simple ();
		
		if($config["pure"] == 1) {
			$block = count($all_alerts);
		}
		
		$templates = array();
		$agent_alerts = array();
		foreach ($all_alerts as $alert) {
			$templates[$alert['id_alert_template']] = '';
			$agent_alerts[$alert['agent_name']][$alert['id_alert_template']][] = $alert;
		}
		
		// Prepare pagination
		ui_pagination ($nagents,false,0,0,false,'offset',true,'',
			'',array('count' => '', 'offset' => 'offset_param'),'alerts_agents');
		
		echo '<table class="databox data" cellpadding="0" cellspacing="0" border="0" width=100%>';
		echo "<th width='140px' >".__("Agents")." / ".__("Alert templates")."</th>";
		
		if ($hor_offset > 0) {
			$new_hor_offset = $hor_offset-$block;
			echo "<th width='20px' style='' rowspan='".($nagents+1)."'>
					<a href='index.php?sec=extensions&sec2=extensions/agents_alerts&refr=0&hor_offset=".
						$new_hor_offset."&offset=".$offset."&group_id=".$group_id."'>".
							html_print_image("images/darrowleft.png",true, array('title' => __('Previous templates')))."</a> </th>";
		}
		
		$templates_raw = array();
		if (!empty($templates)) {
			$sql = sprintf('SELECT id, name
				FROM talert_templates
				WHERE id IN (%s)',implode(',',array_keys($templates)));
			
			$templates_raw = db_get_all_rows_sql($sql);
		}
		
		if (empty($templates_raw))
			$templates_raw = array();
		
		$alerts = array();
		$ntemplates = 0;
		foreach ($templates_raw as $temp) {
			if (isset($templates[$temp['id']]) && $templates[$temp['id']] == '') {
				$ntemplates++;
				if ($ntemplates <= $hor_offset || $ntemplates > ($hor_offset+$block)) {
					continue;
				}
				$templates[$temp['id']] = $temp['name'];
			}
		}
		
		foreach ($templates as $tid => $tname) {
			if ($tname == '') {
				continue;
			}
			echo '<th width="20px" >'. io_safe_output($tname) . html_print_image('images/information_alerts.png', true, array('title' => io_safe_output($tname),'style' => 'margin-left:5px' )) ."</th>";
		}
		
		if (($hor_offset + $block) < $ntemplates) {
			$new_hor_offset = $hor_offset+$block;
			echo "<th width='20px' style='' rowspan='".($nagents+1)."'>
				<a href='index.php?sec=extensions&sec2=extensions/agents_alerts&hor_offset=".$new_hor_offset."&offset=".
					$offset."&group_id=".$group_id."'>".html_print_image("images/darrowright.png",true, array('title' => __('More templates')))."</a> </th>";
		}
		
		foreach ($agents as $agent) {
			$alias = db_get_row ('tagente', 'id_agente', $agent['id_agente']);
			echo '<tr>';
			// Name of the agent
			echo '<td style="font-weight:bold;">'.$alias['alias'].'</td>';
			
			// Alerts of the agent
			$anyfired = false;
			foreach ($templates as $tid => $tname) {
				if ($tname == '') {
					continue;
				}
				if (isset($agent_alerts[$agent['nombre']][$tid])) {
					foreach($agent_alerts[$agent['nombre']][$tid] as $alert) {
						if($alert["times_fired"] > 0) {
							$anyfired = true;
						}
					}
					
					$cellstyle = '';
					if($anyfired) {
						$cellstyle = 'background:'.COL_ALERTFIRED.';';
					}
					
					echo '<td style=";'.$cellstyle.'"> ';
					
					$uniqid = uniqid();
					echo "<div>";
					
					echo count($agent_alerts[$agent['nombre']][$tid])." ".__('Alerts')." ";
					
					echo "<a href='javascript:show_alerts_details(\"$uniqid\")'>".html_print_image('images/zoom.png', true)."</a>";
					
					echo "</div>";
					
					print_alerts_summary_modal_window($uniqid, $agent_alerts[$agent['nombre']][$tid]);
				}
				else {
					echo '<td style="text-align:center"> ';
				}
				echo '</td>';
			}
			echo '</tr>';
		}
		
		echo '</table>';
	}
	
	
}

// Print the modal window for the summary of each alerts group
function print_alerts_summary_modal_window($id, $alerts) {
	
	$table->width = '98%';
	$table->class = "databox";
	$table->data = array ();
	
	$table->head[0] = __('Module');
	$table->head[1] = __('Action');
	$table->head[2] = __('Last fired');
	$table->head[3] = __('Status');
	
	foreach($alerts as $alert) {
		$data[0] = modules_get_agentmodule_name ($alert['id_agent_module']);
		
		$actions = alerts_get_alert_agent_module_actions ($alert['id']);
		
		$actionDefault = db_get_value_sql("
			SELECT id_alert_action
			FROM talert_templates
			WHERE id = " . $alert['id_alert_template']);
		
		$actionText = '';
		
		if (!empty($actions)) {
			$actionText = '<div style="margin-left: 10px;"><ul class="action_list">';
			foreach ($actions as $action) {
				$actionText .= '<div><span class="action_name"><li>' . $action['name'];
				if ($action["fires_min"] != $action["fires_max"]) {
					$actionText .=  " (".$action["fires_min"] . " / ". $action["fires_max"] . ")";
				}
				$actionText .= '</li></span><br /></div>';
			}
			$actionText .= '</ul></div>';
		}
		else {
			if (!empty($actionDefault)) {
				$actionText = db_get_sql ("SELECT name
					FROM talert_actions
					WHERE id = $actionDefault") .
					" <i>(" . __("Default") . ")</i>";
			}
		}
		
		$data[1] = $actionText;
		$data[2] = ui_print_timestamp ($alert["last_fired"], true);
		
		$status = STATUS_ALERT_NOT_FIRED;
		
		if ($alert["times_fired"] > 0) {
			$status = STATUS_ALERT_FIRED;
			$title = __('Alert fired').' '.$alert["times_fired"].' '.__('times');
		}
		elseif ($alert["disabled"] > 0) {
			$status = STATUS_ALERT_DISABLED;
			$title = __('Alert disabled');
		}
		else {
			$status = STATUS_ALERT_NOT_FIRED;
			$title = __('Alert not fired');
		}
		
		$data[3] = ui_print_status_image($status, $title, true);
		
		array_push ($table->data, $data);
	}
	
	$content = html_print_table($table,true);
	
	$agent = modules_get_agentmodule_agent_alias($alerts[0]['id_agent_module']);
	$template = alerts_get_alert_template_name($alerts[0]['id_alert_template']);
	
	echo '<div id="alerts_details_'.$id.'" title="'.__('Agent').': '.$agent.' / '.__('Template').': '.$template.'" style="display:none">'.$content.'</div>';
}

extensions_add_operation_menu_option(__("Agents/Alerts view"), 'estado', null, "v1r1","view");
extensions_add_main_function('mainAgentsAlerts');

ui_require_jquery_file('pandora');

?>

<script type="text/javascript">
	function show_alerts_details(id) {
		$("#alerts_details_"+id).dialog({
			resizable: true,
			draggable: true,
			modal: true,
			height: 280,
			width: 800,
			overlay: {
				opacity: 0.5,
				background: "black"
			}
		});
	}
	
	function show_add_alerts(id) {
		$("#add_alerts_dialog_"+id).dialog({
			resizable: true,
			draggable: true,
			modal: true,
			height: 235,
			width: 600,
			overlay: {
				opacity: 0.5,
				background: "black"
			}
		});
	}
	
	// checkbox-slides_ids
	$(document).ready(function () {
		$('#checkbox-slides_ids').click(function(){
			if ($('#checkbox-slides_ids').prop('checked')){
				var url = location.href.replace("&show_modules=true", "");
				location.href = url+"&show_modules=true";
			} else {
				var url = location.href.replace("&show_modules=true", "");
				var re = /&offset=\d*/g;
				location.href = url.replace(re, "");
			}
		});
		
		$('#group_id').change(function(){
			var regx = /&group_id=\d*/g;
			var url = location.href.replace(regx, "");
			location.href = url+"&group_id="+$("#group_id").val();
		});

	});
	
</script>
