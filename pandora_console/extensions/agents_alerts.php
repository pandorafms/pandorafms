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
		$updated_info = __("Updated at realtime");
	}
	
	$updated_time = html_print_image ("images/information.png", true, array ("title" => __('Last update'), "style" => 'margin: 5px 3px 0px 10px')).$updated_info;
	
	$refr = get_parameter('refr', 30); // By default 30 seconds
	
	$group_id = get_parameter('group_id', 0);
	$offset = get_parameter('offset', 0);
	$hor_offset = get_parameter('hor_offset', 0);
	$block = 20;
	
	$groups = users_get_groups ();
	
	$filter_groups = '<form method="post" action="' . ui_get_url_refresh (array ('offset' => 0, 'hor_offset' => 0)).'">';
	$filter_groups .= '<b>'.__('Group').'</b>';
	$filter_groups .= html_print_select_groups(false, "AR", true, 'group_id', $group_id, 'this.form.submit()', '', '', true, false, true, '', false , 'width: 100px; margin-right: 10px;; margin-top: 5px;');
	$filter_groups .= '</form>';
	
	$comborefr = '<form method="post" action="' . ui_get_url_refresh (array ('offset' => 0, 'hor_offset' => 0)).'">';
	$comborefr .= '<b>'.__('Refresh').'</b>';
	$comborefr .= html_print_select (
		array('30' => '30 '.__('seconds'),
			(string)SECONDS_1MINUTE => __('1 minute'),
			(string)SECONDS_2MINUTES => __('2 minutes'),
			(string)SECONDS_5MINUTES => __('5 minutes'),
			(string)SECONDS_10MINUTES => __('10 minutes'))
			, 'refr', $config['refr'], $script = 'this.form.submit()', '', 0, true, false, false, '', false, 'width: 100px; margin-right: 10px; margin-top: 5px;');
	$comborefr .= "</form>";
	
	if ($config["pure"] == 0) {
		$fullscreen = '<a href="index.php?extension_in_menu=estado&amp;sec=extensions&amp;sec2=extensions/agents_alerts&amp;pure=1&amp;offset='.$offset.'&group_id='.$group_id.'">'
			. html_print_image ("images/fullscreen.png", true, array ("title" => __('Full screen mode')))
			. "</a>";
	}
	else {
		$fullscreen = '<a href="index.php?extension_in_menu=estado&amp;sec=extensions&amp;sec2=extensions/agents_alerts&amp;refr=0&amp;offset='.$offset.'&group_id='.$group_id.'">'
			. html_print_image ("images/normalscreen.png", true, array ("title" => __('Back to normal mode')))
			. "</a>";
		$config['refr'] = $refr;
	}
	
	$onheader = array('updated_time' => $updated_time, 'fullscreen' => $fullscreen, 
		'combo_groups' => $filter_groups);
	
	if ($config['pure'] == 1) {
		$onheader['combo_refr'] = $comborefr;
	}
	
	// Header
	ui_print_page_header (__("Agents/Alerts"), "images/bell.png", false, "", false, $onheader);
	
	// Old style table, we need a lot of special formatting,don't use table function
	// Prepare old-style table
	
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
		echo "<div class='nf'>" . __('There are no agents with alerts')."</div>";
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
	ui_pagination ($nagents);
	echo "<br>";
	
	echo '<table cellpadding="4" cellspacing="4" border="0" width=98%>';
	echo "<th width='140px' height='25px'>".__("Agents")." / ".__("Alert templates")."</th>";
	
	if ($hor_offset > 0) {
		$new_hor_offset = $hor_offset-$block;
		echo "<th width='20px' style='vertical-align:top; padding-top: 35px;' rowspan='".($nagents+1)."'><a href='index.php?sec=extensions&sec2=extensions/agents_alerts&refr=0&hor_offset=".$new_hor_offset."&offset=".$offset."&group_id=".$group_id."'>".html_print_image("images/darrowleft.png",true, array('title' => __('Previous templates')))."</a> </th>";
	}
	
	if (!empty($templates)) {
		$sql = sprintf('SELECT id, name
			FROM talert_templates WHERE id IN (%s)',implode(',',array_keys($templates)));
		
		$templates_raw = db_get_all_rows_sql($sql);
	}
	
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
		echo '<th width="20px" >'. html_print_image('images/information.png', true, array('title' => io_safe_output($tname))) ."</th>";
	}
	
	if (($hor_offset + $block) < $ntemplates) {
		$new_hor_offset = $hor_offset+$block;
		echo "<th width='20px' style='vertical-align:top; padding-top: 35px;' rowspan='".($nagents+1)."'><a href='index.php?sec=extensions&sec2=extensions/agents_alerts&hor_offset=".$new_hor_offset."&offset=".$offset."&group_id=".$group_id."'>".html_print_image("images/darrowright.png",true, array('title' => __('More templates')))."</a> </th>";
	}
	
	foreach ($agents as $agent) {
		echo '<tr>';
		// Name of the agent
		echo '<td style="font-weight:bold;">'.$agent['nombre'].'</td>';
		
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
								
				echo '<td style="text-align:center;'.$cellstyle.'"> ';
				
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
				
		$actionDefault = db_get_value_sql("SELECT id_alert_action FROM talert_templates WHERE id = " . $alert['id_alert_template']);
		
		$actionText = '';
		
		if (!empty($actions)) {
			$actionText = '<div style="margin-left: 10px;"><ul class="action_list">';
			foreach ($actions as $action) {
				$actionText .= '<div><span class="action_name"><li>' . $action['name'];
				if ($action["fires_min"] != $action["fires_max"]){
					$actionText .=  " (".$action["fires_min"] . " / ". $action["fires_max"] . ")";
				}
				$actionText .= '</li></span><br /></div>';
			}
			$actionText .= '</ul></div>';
		}
		else {
			if(!empty($actionDefault)) {
				$actionText = db_get_sql ("SELECT name FROM talert_actions WHERE id = $actionDefault"). " <i>(".__("Default") . ")</i>";
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
	
	$agent = modules_get_agentmodule_agent_name($alerts[0]['id_agent_module']);
	$template = alerts_get_alert_template_name($alerts[0]['id_alert_template']);
	
	echo '<div id="alerts_details_'.$id.'" title="'.__('Agent').': '.$agent.' / '.__('Template').': '.$template.'" style="display:none">'.$content.'</div>';
}

extensions_add_operation_menu_option(__("Agents/Alerts view"), 'estado', null, "v1r1");
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
			},
			bgiframe: jQuery.browser.msie
		});
	}
</script>
