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

function mainAgentsModules() {
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
	
	$updated_time = $updated_info;
	
	$modulegroup = get_parameter('modulegroup', 0);
	$refr = get_parameter('refr', 30); // By default 30 seconds
	
	$group_id = (int)get_parameter('group_id', 0);
	$offset = (int)get_parameter('offset', 0);
	$hor_offset = (int)get_parameter('hor_offset', 0);
	$block = $config['block_size'];
	
	$groups = users_get_groups ();
	
	$filter_module_groups = '<form method="post" action="' . ui_get_url_refresh (array ('offset' => $offset, 'hor_offset' => $offset,'group_id' => $group_id, 'modulegroup' => $modulegroup)).'">';
	$filter_module_groups .= '<b>'.__('Module group').'</b>';
	$filter_module_groups .= html_print_select_from_sql ("SELECT * FROM tmodule_group ORDER BY name",
		'modulegroup', $modulegroup, 'this.form.submit()',__('All'), 0, true, false, true, false, 'width: auto;');
	$filter_module_groups .= '</form>';
	
	$filter_groups = '<form method="post" action="' . ui_get_url_refresh (array ('offset' => $offset, 'hor_offset' => $offset,'group_id' => $group_id, 'modulegroup' => $modulegroup)).'">';
	$filter_groups .= '<b>'.__('Group').'</b>';
	$filter_groups .= html_print_select_groups(false, "AR", true, 'group_id', $group_id, 'this.form.submit()', '', '', true, false, true, '', false , 'width: auto;');
	$filter_groups .= '</form>';
	
	$comborefr = '<form method="post" action="' . ui_get_url_refresh (array ('offset' => $offset, 'hor_offset' => $offset,'group_id' => $group_id, 'modulegroup' => $modulegroup)).'">';
	$comborefr .= '<b>'.__('Refresh').'</b>';
	$comborefr .= html_print_select (
		array('30' => '30 ' . __('seconds'),
			(string)SECONDS_1MINUTE => __('1 minute'),
			(string)SECONDS_2MINUTES => __('2 minutes'),
			(string)SECONDS_5MINUTES => __('5 minutes'),
			(string)SECONDS_10MINUTES => __('10 minutes')),
		'refr', (int)get_parameter('refr', 0),
		$script = 'this.form.submit()', '', 0, true, false, false, '',
		false, 'width: 100px; margin-right: 10px; margin-top: 5px;');
	$comborefr .= "</form>";
	
	if ($config["pure"] == 0) {
		$fullscreen['text'] = '<a href="index.php?extension_in_menu=estado&amp;sec=extensions&amp;sec2=extensions/agents_modules&amp;pure=1&amp;offset='.$offset.'&group_id='.$group_id.'&modulegroup='.$modulegroup.'">'
			. html_print_image ("images/full_screen.png", true, array ("title" => __('Full screen mode')))
			. "</a>";
	}
	else {
		$fullscreen['text'] = '<a href="index.php?extension_in_menu=estado&amp;sec=extensions&amp;sec2=extensions/agents_modules&amp;refr=0&amp;offset='.$offset.'&group_id='.$group_id.'&modulegroup='.$modulegroup.'">'
			. html_print_image ("images/normal_screen.png", true, array ("title" => __('Back to normal mode')))
			. "</a>";
		$config['refr'] = $refr;
	}
	
	$onheader = array('updated_time' => $updated_time, 'fullscreen' => $fullscreen, 
		'combo_module_groups' => $filter_module_groups,
		'combo_groups' => $filter_groups);
	
	if ($config['pure'] == 1) {
		$onheader['combo_refr'] = $comborefr;
	}
	
	// Header
	ui_print_page_header (__("Agents/Modules"), "images/module_mc.png", false, "", false, $updated_time);
	
	// Old style table, we need a lot of special formatting,don't use table function
	// Prepare old-style table
	echo '<table class="databox filters" cellpadding="0" cellspacing="0" border="0" style="width:100%;">';
	echo "<tr>";
	echo "<td>" . $filter_module_groups . "</td>";
	echo "<td>" . $filter_groups  . "</td>";
	if ($config['pure'] == 1) 
		echo "<td>" . $comborefr  . "</td>";
	echo "<td> <strong>" . __("Full screen") . "</strong>" . $fullscreen['text'] . "</td>";
	echo "</tr>";
	echo "</table>";
	
	$agents = '';
	$agents = agents_get_group_agents($group_id,array('disabled' => 0));
	$agents = array_keys($agents);
	
	$filter_module_group = array('disabled' => 0);
	
	if ($modulegroup > 0) {
		$filter_module_group['id_module_group'] = $modulegroup;
	}
	$count = 0;
	foreach ($agents as $agent) {
		$module = agents_get_modules($agent, false,
			$filter_module_group, true, false);
		if ($module == false) {
			unset($agents[$count]);
		}
		$count++;
	}
	$total_pagination = count($agents);
	$all_modules = agents_get_modules($agents, false,
		$filter_module_group, true, false);
	
	$modules_by_name = array();
	$name = '';
	$cont = 0;
	
	foreach ($all_modules as $key => $module) {
		if ($module == $name) {
			$modules_by_name[$cont-1]['id'][] = $key;
		}
		else {
			$name = $module;
			$modules_by_name[$cont]['name'] = $name;
			$modules_by_name[$cont]['id'][] = $key;
			$cont ++;
		}
	}
	
	if ($config["pure"] == 1) {
		$block = count($modules_by_name);
	}
	
	$filter_groups = array ('offset' => (int) $offset,
		'limit' => (int) $config['block_size'], 'disabled' => 0,'id_agente'=>$agents);
	
	if ($group_id > 0) {
		$filter_groups['id_grupo'] = $group_id;
	}
	
	$agents = agents_get_agents ($filter_groups);
	$nagents = count($agents);
	
	if ($all_modules == false || $agents == false) {
		ui_print_info_message ( array('no_close'=>true, 'message'=> __('There are no agents with modules') ) );
		return;
	}
	
	echo '<table cellpadding="4" cellspacing="4" border="0" style="width:100%;" class="agents_modules_table">';
	
	echo "<tr>";
	
	echo "<th width='140px' style='text-align: right !important; padding-right:13px;'>" . __("Agents") . " / " . __("Modules") . "</th>";
	
	if ($hor_offset > 0) {
		$new_hor_offset = $hor_offset-$block;
		echo "<th width='20px' " .
			"style='vertical-align:top; padding-top: 35px;' " .
			"rowspan='" . ($nagents + 1) . "'>" .
			"<a href='index.php?" .
				"extension_in_menu=estado&" .
				"sec=extensions&" .
				"sec2=extensions/agents_modules&" .
				"refr=0&" .
				"hor_offset=" . $new_hor_offset . "&" .
				"offset=" . $offset . "&" .
				"group_id=" . $group_id . "&" .
				"modulegroup=" . $modulegroup . "'>" .
				html_print_image("images/arrow_left.png", true,
					array('title' => __('Previous modules'))) . 
			"</a>" .
			"</th>";
	}
	$nmodules = 0;
	foreach ($modules_by_name as $module) {
		$nmodules++;
		
		if ($nmodules <= $hor_offset || $nmodules > ($hor_offset+$block)) {
			continue;
		}
		
		$text = ui_print_truncate_text(io_safe_output($module['name']), 'module_small');
		
		echo '<th align="center" width="20px" id="th_module_r_' . $nmodules . '" class="th_class_module_r">
				<div style="width: 30px;">
					<div id="div_module_r_' . $nmodules . '" style="display: none;padding-left:10px" class="rotate_text_module">' .
						$text .
					'</div>
				</div>
			</th>';
	}
	
	if (($hor_offset + $block) < $nmodules) {
		$new_hor_offset = $hor_offset+$block;
		echo "<th width='20px' " .
			"style='vertical-align:top; padding-top: 35px;' " .
			"rowspan='".($nagents+1)."'>" .
			"<a href='index.php?" .
				"extension_in_menu=estado&" .
				"sec=extensions&".
				"sec2=extensions/agents_modules&".
				"hor_offset=" . $new_hor_offset . "&".
				"offset=" . $offset . "&" .
				"group_id=" . $group_id . "&" .
				"modulegroup=" . $modulegroup . "'>" .
				html_print_image(
					"images/arrow.png", true,
					array('title' => __('More modules'))) .
			"</a>" .
			"</th>";
	}
	
	echo "</tr>";
	
	$filter_agents = array('offset' => (int) $offset, 'disabled' => 0);
	if ($group_id > 0) {
		$filter_agents['id_grupo'] = $group_id;
	}
	// Prepare pagination
	ui_pagination ($total_pagination);
	
	foreach ($agents as $agent) {
		// Get stats for this group
		$agent_status = agents_get_status($agent['id_agente']);
		$alias = db_get_row ("tagente", "id_agente", $agent['id_agente']);
		if (empty($alias['alias'])){
			$alias['alias'] = $agent['nombre'];
		}
		
		switch($agent_status) {
			case 4: // Alert fired status
				$rowcolor = 'group_view_alrm';
				break;
			case 1: // Critical status
				$rowcolor = 'group_view_crit';
				break;
			case 2: // Warning status
				$rowcolor = 'group_view_warn';
				break;
			case 0: // Normal status
				$rowcolor = "group_view_ok";
				break;
			case 3: 
			case -1: 
			default: // Unknown status
				$rowcolor = 'group_view_unk';
				break;
		}
		
		echo "<tr style='height: 25px;'>";
		
		echo "<td class='$rowcolor'>
			<a class='$rowcolor' href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$agent['id_agente']."'>" .
			$alias['alias'] .
			"</a></td>";
		$agent_modules = agents_get_modules($agent['id_agente'], false, $filter_module_group, true, false);
		
		$nmodules = 0;
		
		foreach ($modules_by_name as $module) {
			$nmodules++;
			
			if ($nmodules <= $hor_offset || $nmodules > ($hor_offset+$block)) {
				continue;
			}
			
			$match = false;
			foreach ($module['id'] as $module_id) {
				if (!$match && array_key_exists($module_id,$agent_modules)) {
					$status = modules_get_agentmodule_status($module_id);
					echo "<td style='text-align: center;'>";
					$win_handle = dechex(crc32($module_id.$module["name"]));
					$graph_type = return_graphtype (modules_get_agentmodule_type($module_id));
					$link ="winopeng('" .
						"operation/agentes/stat_win.php?" .
						"type=$graph_type&" .
						"period=" . SECONDS_1DAY . "&" .
						"id=" . $module_id . "&" .
						"label=" . rawurlencode(
							urlencode(
								base64_encode($module["name"]))) . "&" .
						"refresh=" . SECONDS_10MINUTES . "', 'day_".$win_handle."')";
					
					echo '<a href="javascript:'.$link.'">';
					switch ($status) {
						case AGENT_MODULE_STATUS_NORMAL:
							ui_print_status_image ('module_ok.png', modules_get_last_value($module_id), false, array('width' => '20px', 'height' => '20px'));
							break;
						case AGENT_MODULE_STATUS_CRITICAL_BAD:
							ui_print_status_image ('module_critical.png', modules_get_last_value($module_id), false, array('width' => '20px', 'height' => '20px'));
							break;
						case AGENT_MODULE_STATUS_WARNING:
							ui_print_status_image ('module_warning.png', modules_get_last_value($module_id), false, array('width' => '20px', 'height' => '20px'));
							break;
						case AGENT_MODULE_STATUS_UNKNOWN:
							ui_print_status_image ('module_unknown.png', modules_get_last_value($module_id), false, array('width' => '20px', 'height' => '20px'));
							break;
						case 4:
							ui_print_status_image ('module_alertsfired.png', modules_get_last_value($module_id), false, array('width' => '20px', 'height' => '20px'));
							break;
					}
					echo '</a>';
					echo "</td>";
					$match = true;
				}
			}
			
			if (!$match) {
				echo "<td></td>";
			}
		}
		
		echo "</tr>";
	}
	
	echo "</table>";
	
	echo "<div class='legend_basic' style='width: 96%'>";
	
	echo "<table>";
	echo "<tr><td colspan='2' style='padding-bottom: 10px;'><b>" . __('Legend') . "</b></td></tr>";
	echo "<tr><td class='legend_square_simple'><div style='background-color: " . COL_ALERTFIRED . ";'></div></td><td>" . __("Orange cell when the module has fired alerts") . "</td></tr>";
	echo "<tr><td class='legend_square_simple'><div style='background-color: " . COL_CRITICAL . ";'></div></td><td>" . __("Red cell when the module has a critical status") . "</td></tr>";
	echo "<tr><td class='legend_square_simple'><div style='background-color: " . COL_WARNING . ";'></div></td><td>" . __("Yellow cell when the module has a warning status") . "</td></tr>";
	echo "<tr><td class='legend_square_simple'><div style='background-color: " . COL_NORMAL . ";'></div></td><td>" . __("Green cell when the module has a normal status") . "</td></tr>";
	echo "<tr><td class='legend_square_simple'><div style='background-color: " . COL_UNKNOWN . ";'></div></td><td>" . __("Grey cell when the module has an unknown status") . "</td></tr>";
	echo "</table>";
	echo "</div>";
	
	echo "
		<style type='text/css'>
			.rotate_text_module {
				-ms-transform: rotate(270deg);
				-webkit-transform: rotate(270deg);
				-moz-transform: rotate(270deg);
				-o-transform: rotate(270deg);
				writing-mode: lr-tb;
				white-space: nowrap;
			}
		</style>
		<script type='text/javascript'>
			$(document).ready(function () {
				//Get max width of name of modules
				max_width = 0;
				$.each($('.th_class_module_r'), function (i, elem) {
					id = $(elem).attr('id').replace('th_module_r_', '');
					
					width = $(\"#div_module_r_\" + id).width();
					
					if (max_width < width) {
						max_width = width;
					} 
				});
				
				$.each($('.th_class_module_r'), function (i, elem) {
					id = $(elem).attr('id').replace('th_module_r_', '');
					$(\"#th_module_r_\" + id).height(($(\"#div_module_r_\" + id).width() + 10) + 'px');
					
					//$(\"#div_module_r_\" + id).css('margin-top', (max_width - $(\"#div_module_r_\" + id).width()) + 'px');
					$(\"#div_module_r_\" + id).css('margin-top', (max_width - 20) + 'px');
					$(\"#div_module_r_\" + id).show();
				});
			});
		</script>
		";
}

extensions_add_operation_menu_option(__("Agents/Modules view"), 'estado', 'agents_modules/icon_menu.png', "v1r1","view");
extensions_add_main_function('mainAgentsModules');

?>
