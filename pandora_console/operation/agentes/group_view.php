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

// Load global vars
require_once ("include/config.php");
require_once ("include/functions_reporting.php");
require_once ($config['homedir'] . "/include/functions_agents.php");
require_once ($config['homedir'] . '/include/functions_users.php');
require_once ("include/functions_groupview.php");

check_login ();
// ACL Check
$agent_a = check_acl ($config['id_user'], 0, "AR");
$agent_w = check_acl ($config['id_user'], 0, "AW");

if (!$agent_a && !$agent_w) {
	db_pandora_audit("ACL Violation", 
	"Trying to access Agent view (Grouped)");
	require ("general/noaccess.php");
	exit;
}
$offset = get_parameter('offset', 0);
// Update network modules for this group
// Check for Network FLAG change request
// Made it a subquery, much faster on both the database and server side
if (isset ($_GET["update_netgroup"])) {
	$group = get_parameter_get ("update_netgroup", 0);
	
	if (check_acl ($config['id_user'], $group, "AW")) {
		if ($group == 0) {
			db_process_sql_update('tagente_modulo', array('flag' => 1));
		}
		else {
			db_process_sql("UPDATE `tagente_modulo`
				SET `flag` = 1
				WHERE `id_agente` = ANY(SELECT id_agente
					FROM tagente
					WHERE id_grupo = " . $group . ")");
		}
	}
	else {
		db_pandora_audit("ACL Violation", "Trying to set flag for groups");
		require ("general/noaccess.php");
		exit;
	}
}

if ($config["realtimestats"] == 0) {
	$updated_time ="<a href='index.php?sec=estado&sec2=operation/agentes/tactical&force_refresh=1'>";
	$updated_time .= __('Last update'). " : ". ui_print_timestamp (db_get_sql ("SELECT min(utimestamp) FROM tgroup_stat"), true);
	$updated_time .= "</a>";
}
else {
	$updated_time = __("Updated at realtime");
}

// Header
ui_print_page_header (__("Group view"), "images/group.png", false, "", false, $updated_time);

$strict_user = db_get_value('strict_acl', 'tusuario', 'id_user', $config['id_user']);

$all_data = groupview_status_modules_agents ($config['id_user'], $strict_user, ($agent_a == true) ? 'AR' : (($agent_w == true) ? 'AW' : 'AR'), $strict_user);

$total_agentes = 0;
$monitor_ok = 0;
$monitor_warning = 0;
$monitor_critical = 0;
$monitor_unknown = 0;
$monitor_not_init = 0;
$agents_unknown = 0;
$agents_critical = 0;
$agents_notinit = 0;
$all_alerts_fired = 0;

foreach ($all_data as $group_all_data) {
	$total_agentes += $group_all_data["_total_agents_"];
	$monitor_ok += $group_all_data["_monitors_ok_"];
	$monitor_warning += $group_all_data["_monitors_warning_"];
	$monitor_critical += $group_all_data["_monitors_critical_"];
	$monitor_unknown += $group_all_data["_monitors_unknown_"];
	$monitor_not_init += $group_all_data["_monitors_not_init_"];
	
	$agents_unknown += $group_all_data["_agents_unknown_"];
	$agents_notinit += $group_all_data["_agents_not_init_"];
	$agents_critical += $group_all_data["_agents_critical_"];

	$all_alerts_fired += $group_all_data["_monitors_alerts_fired_"];
}

$total = $monitor_ok + $monitor_warning + $monitor_critical + $monitor_unknown + $monitor_not_init;

//Monitors
$total_ok = format_numeric (($monitor_ok*100)/$total,2);
$total_warning = format_numeric (($monitor_warning*100)/$total,2);
$total_critical = format_numeric (($monitor_critical*100)/$total,2);
$total_unknown = format_numeric (($monitor_unknown*100)/$total,2);
$total_monitor_not_init = format_numeric (($monitor_not_init*100)/$total,2);
//Agents
$total_agent_unknown = format_numeric (($agents_unknown*100)/$total_agentes,2);
$total_agent_critical = format_numeric (($agents_critical*100)/$total_agentes,2);
$total_not_init = format_numeric (($agents_notinit*100)/$total_agentes,2);

echo '<table cellpadding="0" cellspacing="0" border="0" width="100%" class="databox">';
	echo "<tr>";
		echo "<th colspan=2 style='text-align: center;'>" . __("Summary of the status groups") . "</th>";
	echo "</tr>";
	echo "<tr>";
		echo "<th width=30% style='text-align:center'>" . __("Agents") . "</th>";
		echo "<th width=70% style='text-align:center'>" . __("Modules") . "</th>";
	echo "</tr>";
	echo "<tr height=70px'>";
		echo "<td align='center'>";
			echo "<span id='sumary' style='background-color:#B2B2B2;'>". $total_agent_unknown ."%</span>";
			echo "<span id='sumary' style='background-color:#5bb6e5;'>". $total_not_init ."%</span>";
			echo "<span id='sumary' style='background-color:#FC4444;'>". $total_agent_critical ."%</span>";
		echo "</td>";
		echo "<td align='center'>";
			echo "<span id='sumary' style='background-color:#FC4444;'>". $total_critical ."%</span>";
			echo "<span id='sumary' style='background-color:#FAD403;'>". $total_warning ."%</span>";
			echo "<span id='sumary' style='background-color:#80BA27;'>". $total_ok ."%</span>";
			echo "<span id='sumary' style='background-color:#B2B2B2;'>". $total_unknown ."%</span>";
			echo "<span id='sumary' style='background-color:#5bb6e5;'>". $total_monitor_not_init ."%</span>";
		echo "</td>";
	echo "</tr>";
echo "</table>";

//Groups and tags
$result_groups = groupview_get_groups_list($config['id_user'], $strict_user,
	($agent_a == true) ? 'AR' : (($agent_w == true) ? 'AW' : 'AR'), true, true);

$count = count($result_groups);

if ($count == 1) {
	if ($result_groups[0]['_id_'] == 0) {
		unset($result_groups[0]);
	}
}

ui_pagination($count);

if (!empty($result_groups)) {

	echo '<table cellpadding="0" cellspacing="0" style="margin-top:10px;" class="databox data" border="0" width="100%">';
		echo "<tr>";
			echo "<th colspan=2 ></th>";
			echo "<th colspan=4 class='difference' style='text-align:center'>" . __("Agents") . "</th>";
			echo "<th colspan=6 style='text-align:center'>" . __("Modules") . "</th>";
		echo "</tr>";
		
		echo "<tr>";
			echo "<th style='width: 26px;'>" . __("Force") . "</th>";
			echo "<th width='30%' style='min-width: 60px;'>" . __("Group") . "/" . __("Tags") . "</th>";
			echo "<th width='10%' style='min-width: 60px;text-align:center;'>" . __("Total") . "</th>";
			echo "<th width='10%' style='min-width: 60px;text-align:center;'>" . __("Unknown") . "</th>";
			echo "<th width='10%' style='min-width: 60px;text-align:center;'>" . __("Not init") . "</th>";
			echo "<th width='10%' style='min-width: 60px;text-align:center;'>" . __("Critical") . "</th>";
			echo "<th width='10%' style='min-width: 60px;text-align:center;'>" . __("Unknown") . "</th>";
			echo "<th width='10%' style='min-width: 60px;text-align:center;'>" . __("Not Init") . "</th>";
			echo "<th width='10%' style='min-width: 60px;text-align:center;'>" . __("Normal") . "</th>";
			echo "<th width='10%' style='min-width: 60px;text-align:center;'>" . __("Warning") . "</th>";
			echo "<th width='10%' style='min-width: 60px;text-align:center;'>" . __("Critical") . "</th>";
			echo "<th width='10%' style='min-width: 60px;text-align:center;'>" . __("Alert fired") . "</th>";
		echo "</tr>";
		
		$result_groups = array_slice($result_groups, $offset, $config['block_size']);
		
		foreach ($result_groups as $data) {

			$groups_id = $data["_id_"];

			// Calculate entire row color
			if ($data["_monitors_alerts_fired_"] > 0) {
				$color_class = 'group_view_alrm';
				$status_image = ui_print_status_image ('agent_alertsfired_ball.png', "", true);
			}
			elseif ($data["_monitors_critical_"] > 0) {
				$color_class = 'group_view_crit';
				$status_image = ui_print_status_image ('agent_critical_ball.png', "", true);
			}
			elseif ($data["_monitors_warning_"] > 0) {
				$color_class = 'group_view_warn';
				$status_image = ui_print_status_image ('agent_warning_ball.png', "", true);
			}
			elseif ($data["_monitors_ok_"] > 0)  {
				
				$color_class = 'group_view_ok';
				$status_image = ui_print_status_image ('agent_ok_ball.png', "", true);
			}
			elseif (($data["_monitors_unknown_"] > 0) ||  ($data["_agents_unknown_"] > 0)) {
				$color_class = 'group_view_unk';
				$status_image = ui_print_status_image ('agent_no_monitors_ball.png', "", true);
			}
			else {
				$color_class = '';
				$status_image = ui_print_status_image ('agent_no_data_ball.png', "", true);
			}
			
			echo "<tr style='height: 35px;'>";
			
			// Force
			echo "<td class='group_view_data' style='text-align: center; vertica-align: middle;'>";
			if (!isset($data['_is_tag_']) && check_acl ($config['id_user'], $data['_id_'], "AW")) {
				echo '<a href="index.php?sec=estado&sec2=operation/agentes/group_view&update_netgroup='.$data['_id_'].'">' .
					html_print_image("images/target.png", true, array("border" => '0', "title" => __('Force'))) . '</a>';
			}
			echo "</td>";
			
			$prefix = "";
			if (!isset($data['_is_tag_'])) {
				if ($data['_id_'] != 0) {
					$prefix = '&nbsp;&nbsp;&nbsp;&nbsp;';
				}
			}
			
			// Groupname and Tags
			echo "<td>";
			if (isset($data['_is_tag_'])) {
				$deep = "";
				$link = "<a href='index.php?sec=monitoring&sec2=operation/tree&tag_id=".$data['_id_']. "'>";
			} else {
				$deep = groups_get_group_deep ($data['_id_']);
				$link = "<a href='index.php?sec=estado&sec2=operation/agentes/estado_agente&group_id=".$data['_id_']."'>";
			}
			
			$group_name = "<b><span style='font-size: 7.5pt'>" . ui_print_truncate_text($data['_name_'], 50) . "</span></b>";
			
			$item_icon = '';
			if (isset($data['_iconImg_']) && !empty($data['_iconImg_']))
				$item_icon = $data['_iconImg_'];
			
			if ($data['_name_'] != "All")
				echo $deep . $link . $group_name . "</a>";
			else
				echo $link . $group_name . "</a>";

			if (isset($data['_is_tag_'])){
				echo '<a>' . html_print_image("images/tag.png", true, array("border" => '0', "style" => 'width:18px;margin-left:5px', "title" => __('Tag'))) . '</a>' ;
			}

			echo "</td>";
			
			// Total agents
			echo "<td style='font-weight: bold; font-size: 18px;' align='center' class='$color_class'>";
			if (isset($data['_is_tag_'])) {
				$link = "<a class='group_view_data $color_class' style='font-weight: bold; font-size: 18px; text-align: center;'
				href='index.php?sec=monitoring&sec2=operation/tree&tag_id=".$data['_id_']. "'>";
			} else {
				$link = "<a class='group_view_data $color_class' style='font-weight: bold; font-size: 18px; text-align: center;' 
				href='index.php?sec=estado&sec2=operation/agentes/estado_agente&group_id=".$data['_id_']."'>";
			}
			if ($data["_id_"] == 0) {
				echo $link . $total_agentes . "</a>";
			}
			if ($data["_total_agents_"] > 0 && $data["_id_"] != 0) {
				echo $link . $data["_total_agents_"] . "</a>";
			}
			echo "</td>";
			
			// Agents unknown
			echo "<td class='group_view_data group_view_data_unk $color_class' style='font-weight: bold; font-size: 18px; text-align: center;'>";
			if (isset($data['_is_tag_'])) {
				$link = "<a class='group_view_data $color_class' style='font-weight: bold; font-size: 18px; text-align: center;'
				href='index.php?sec=monitoring&sec2=operation/tree&tag_id=".$data['_id_']. "&status=" . AGENT_STATUS_UNKNOWN ."'>";
			} else {
				$link = "<a class='group_view_data $color_class' style='font-weight: bold; font-size: 18px; text-align: center;' 
				href='index.php?sec=estado&sec2=operation/agentes/estado_agente&group_id=".$data['_id_']."&status=" . AGENT_STATUS_UNKNOWN ."'>";
			}
			if (($data["_id_"] == 0) && ($agents_unknown != 0)) {
				echo $link . $agents_unknown . "</a>";
			}
			if ($data["_agents_unknown_"] > 0 && ($data["_id_"] != 0)) {
				echo $link . $data["_agents_unknown_"] . "</a>";
			}
			echo "</td>";
			
			// Agents not init
			echo "<td class='group_view_data group_view_data_unk $color_class' style='font-weight: bold; font-size: 18px; text-align: center;'>";
			if (isset($data['_is_tag_'])) {
				$link = "<a class='group_view_data $color_class' style='font-weight: bold; font-size: 18px; text-align: center;'
				href='index.php?sec=monitoring&sec2=operation/tree&tag_id=".$data['_id_']. "&status=" . AGENT_STATUS_NOT_INIT ."'>";
			} else {
				$link = "<a class='group_view_data $color_class' style='font-weight: bold; font-size: 18px; text-align: center;' 
				href='index.php?sec=estado&sec2=operation/agentes/estado_agente&group_id=".$data['_id_']."&status=" . AGENT_STATUS_NOT_INIT ."'>";
			}
			if (($data["_id_"] == 0) && ($agents_notinit != 0)) {
				echo $link . $agents_notinit . "</a>";
			}

			if ($data["_agents_not_init_"] > 0 && ($data["_id_"] != 0)) {
				echo $link . $data["_agents_not_init_"] . "</a>";
			}
			echo "</td>";
			
			// Agents critical
			echo "<td class='group_view_data group_view_data_unk $color_class' style='font-weight: bold; font-size: 18px; text-align: center;'>";
			if (isset($data['_is_tag_'])) {
				$link = "<a class='group_view_data $color_class' style='font-weight: bold; font-size: 18px; text-align: center;'
				href='index.php?sec=monitoring&sec2=operation/tree&tag_id=".$data['_id_']. "&status=" . AGENT_STATUS_CRITICAL ."'>";
			} else {
				$link = "<a class='group_view_data $color_class' style='font-weight: bold; font-size: 18px; text-align: center;' 
				href='index.php?sec=estado&sec2=operation/agentes/estado_agente&group_id=".$data['_id_']."&status=" . AGENT_STATUS_CRITICAL ."'>";
			}
			if (($data["_id_"] == 0) && ($agents_critical != 0)) {
				echo $link . $agents_critical . "</a>";
			}

			if ($data["_agents_critical_"] > 0 && ($data["_id_"] != 0)) {
				echo $link . $data["_agents_critical_"] . "</a>";
			}
			echo "</td>";
			
			// Monitors unknown
			echo "<td class='group_view_data group_view_data_unk $color_class' style='font-weight: bold; font-size: 18px; text-align: center;'>";
			if (!isset($data['_is_tag_'])) {
				$link = "<a class='group_view_data $color_class' style='font-weight: bold; font-size: 18px; text-align: center;' 
				href='index.php?sec=estado&sec2=operation/agentes/status_monitor&ag_group=".$data['_id_']."&status=" . AGENT_MODULE_STATUS_UNKNOWN . "'>";
			} else {
				$link = "<a class='group_view_data $color_class' style='font-weight: bold; font-size: 18px; text-align: center;' 
				href='index.php?sec=estado&sec2=operation/agentes/status_monitor&tag_filter=".$data['_id_']."&status=" . AGENT_MODULE_STATUS_UNKNOWN . "'>";
			}
			if (($data["_id_"] == 0) && ($monitor_unknown != 0)) {
				echo $link . $monitor_unknown . "</a>";
			}
			if ($data["_monitors_unknown_"] > 0 && ($data["_id_"] != 0)) {
				echo $link . $data["_monitors_unknown_"] . "</a>";
			}
			echo "</td>";
			
			// Monitors not init
			echo "<td class='group_view_data group_view_data_unk $color_class' style='font-weight: bold; font-size: 18px; text-align: center;'>";
			if (!isset($data['_is_tag_'])) {
				$link = "<a class='group_view_data $color_class' style='font-weight: bold; font-size: 18px; text-align: center;' 
				href='index.php?sec=estado&sec2=operation/agentes/status_monitor&ag_group=".$data['_id_']."&status=" . AGENT_MODULE_STATUS_NOT_INIT . "'>";
			} else {
				$link = "<a class='group_view_data $color_class' style='font-weight: bold; font-size: 18px; text-align: center;' 
				href='index.php?sec=estado&sec2=operation/agentes/status_monitor&tag_filter=".$data['_id_']."&status=" . AGENT_MODULE_STATUS_NOT_INIT . "'>";
			}
			if (($data["_id_"] == 0) && ($monitor_not_init != 0)) {
				echo $link . $monitor_not_init . "</a>";
			}
			if ($data["_monitors_not_init_"] > 0 && ($data["_id_"] != 0)) {
				echo $link . $data["_monitors_not_init_"] . "</a>";
			}
			echo "</td>";
			
			// Monitors OK
			echo "<td class='group_view_data group_view_data_ok $color_class' style='font-weight: bold; font-size: 18px; text-align: center;'>";
			if (!isset($data['_is_tag_'])) {
				$link = "<a class='group_view_data $color_class' style='font-weight: bold; font-size: 18px; text-align: center;' 
				href='index.php?sec=estado&sec2=operation/agentes/status_monitor&ag_group=".$data['_id_']."&status=" . AGENT_MODULE_STATUS_NORMAL . "'>";
			} else {
				$link = "<a class='group_view_data $color_class' style='font-weight: bold; font-size: 18px; text-align: center;' 
				href='index.php?sec=estado&sec2=operation/agentes/status_monitor&tag_filter=".$data['_id_']."&status=" . AGENT_MODULE_STATUS_NORMAL . "'>";
			}
			if (($data["_id_"] == 0) && ($monitor_ok != 0)) {
				echo $link . $monitor_ok . "</a>";
			}
			if ($data["_monitors_ok_"] > 0 && ($data["_id_"] != 0)) {
				echo $link . $data["_monitors_ok_"] . "</a>";
			}
			echo "</td>";
			
			// Monitors Warning
			echo "<td class='group_view_data group_view_data_warn $color_class' style='font-weight: bold; font-size: 18px; text-align: center;'>";
			if (!isset($data['_is_tag_'])) {
				$link = "<a class='group_view_data group_view_data_warn $color_class' style='font-weight: bold; font-size: 18px; text-align: center;' 
				href='index.php?sec=estado&sec2=operation/agentes/status_monitor&ag_group=".$data['_id_']."&status=" . AGENT_MODULE_STATUS_WARNING . "'>";
			} else {
				$link = "<a class='group_view_data group_view_data_warn $color_class' style='font-weight: bold; font-size: 18px; text-align: center;' 
				href='index.php?sec=estado&sec2=operation/agentes/status_monitor&tag_filter=".$data['_id_']."&status=" . AGENT_MODULE_STATUS_WARNING . "'>";
			}
			if (($data["_id_"] == 0) && ($monitor_warning != 0)) {
				echo $link . $monitor_warning . "</a>";
			}
			if ($data["_monitors_warning_"] > 0 && ($data["_id_"] != 0)) {
				echo $link . $data["_monitors_warning_"] . "</a>";
			}
			echo "</td>";
			
			// Monitors Critical
			echo "<td class='group_view_data group_view_data_crit $color_class' style='font-weight: bold; font-size: 18px; text-align: center;'>";
			if (!isset($data['_is_tag_'])) {
				$link = "<a class='group_view_data $color_class' style='font-weight: bold; font-size: 18px; text-align: center;' 
				href='index.php?sec=estado&sec2=operation/agentes/status_monitor&ag_group=".$data['_id_']."&status=" . AGENT_MODULE_STATUS_CRITICAL_BAD . "'>";
			} else {
				$link = "<a class='group_view_data $color_class' style='font-weight: bold; font-size: 18px; text-align: center;' 
				href='index.php?sec=estado&sec2=operation/agentes/status_monitor&tag_filter=".$data['_id_']."&status=" . AGENT_MODULE_STATUS_CRITICAL_BAD . "'>";
			}
			if (($data["_id_"] == 0) && ($monitor_critical != 0)) {
				echo $link . $monitor_critical . "</a>";
			}
			if ($data["_monitors_critical_"] > 0 && ($data["_id_"] != 0)) {
				echo $link . $data["_monitors_critical_"] . "</a>";
			}
			echo "</td>";
			
			// Alerts fired
			echo "<td class='group_view_data group_view_data_alrm $color_class' style='font-weight: bold; font-size: 18px;  text-align: center;'>";
			if (!isset($data['_is_tag_'])) {
				$link = "<a class='group_view_data $color_class' style='font-weight: bold; font-size: 18px; text-align: center;' 
				href='index.php?sec=estado&sec2=operation/agentes/alerts_status&ag_group=".$data['_id_']."&filter=fired'>";
			} else {
				$link = "<a class='group_view_data $color_class' style='font-weight: bold; font-size: 18px; text-align: center;' 
				href='index.php?sec=estado&sec2=operation/agentes/alerts_status&tag_filter=".$data['_id_']."&filter=fired'>";
			}
			if (($data["_id_"] == 0) && ($all_alerts_fired != 0)) {
				echo $link . $all_alerts_fired . "</a>";
			}
			if ($data["_monitors_alerts_fired_"] > 0 && ($data["_id_"] != 0)) {
				echo $link . $data["_monitors_alerts_fired_"] . "</a>";
			}
			echo '</td>';
			
			echo "</tr>";
		}
	echo '</table>';
} else {
	ui_print_info_message ( __('There are no defined agents'));
}
?>