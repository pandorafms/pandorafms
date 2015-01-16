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
//Groups and tags
$result_groups = group_get_groups_list($config['id_user'], $strict_user, 'AR', true, true);

$count = count($result_groups);

if ($count == 1) {
	if ($result_groups[0]['_id_'] == 0) {
		unset($result_groups[0]);
	}
}
ui_pagination($count);

if (!empty($result_groups)) {

	echo '<table cellpadding="0" cellspacing="0" style="margin-top:10px;" class="databox" border="0" width="98%">';
		echo "<tr>";
		echo "<th style='width: 26px;'>" . __("Force") . "</th>";
		echo "<th width='30%' style='min-width: 60px;'>" . __("Group") . "</th>";
		echo "<th width='10%' style='min-width: 60px;'>" . __("Agents") . "</th>";
		echo "<th width='10%' style='min-width: 60px;'>" . __("Agent unknown") . "</th>";
		echo "<th width='10%' style='min-width: 60px;'>" . __("Agents not init") . "</th>";
		echo "<th width='10%' style='min-width: 60px;'>" . __("Unknown") . "</th>";
		echo "<th width='10%' style='min-width: 60px;'>" . __("Not Init") . "</th>";
		echo "<th width='10%' style='min-width: 60px;'>" . __("Normal") . "</th>";
		echo "<th width='10%' style='min-width: 60px;'>" . __("Warning") . "</th>";
		echo "<th width='10%' style='min-width: 60px;'>" . __("Critical") . "</th>";
		echo "<th width='10%' style='min-width: 60px;'>" . __("Alert fired") . "</th>";
		
		foreach ($result_groups as $data) {

			// Calculate entire row color
			if ($data["_monitors_alerts_fired_"] > 0){
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
			elseif (($data["_monitors_unknown_"] > 0) ||  ($data["_agents_unknown_"] > 0)) {
				$color_class = 'group_view_unk';
				$status_image = ui_print_status_image ('agent_no_monitors_ball.png', "", true);
			}
			elseif ($data["_monitors_ok_"] > 0)  {
				$color_class = 'group_view_ok';
				$status_image = ui_print_status_image ('agent_ok_ball.png', "", true);
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
			
			// Groupname
			echo "<td>";
			if (isset($data['_is_tag_'])) {
				$deep = "";
				$link = "";
			} else {
				$deep = groups_get_group_deep ($data['_id_']);
				$link = "<a href='index.php?sec=estado&sec2=operation/agentes/estado_agente&group_id=".$data['_id_']."'>";
			}
			
			$group_name = "<b><span style='font-size: 7.5pt'>" . ui_print_truncate_text($data['_name_'], 50) . "</span></b>";

			$item_icon = '';
			if (isset($data['_iconImg_']) && !empty($data['_iconImg_']))
				$item_icon = $data['_iconImg_'];

			echo $link . $deep . $item_icon ."&nbsp;" . $group_name . "</a>";

			echo "</td>";
			
			// Total agents
			echo "<td style='font-weight: bold; font-size: 18px;' align='center' class='$color_class'>";
			if (isset($data['_is_tag_'])) {
				$link = "";
			} else {
				$link = "<a class='group_view_data $color_class' style='font-weight: bold; font-size: 18px; text-align: center;' 
				href='index.php?sec=estado&sec2=operation/agentes/estado_agente&group_id=".$data['_id_']."'>";
			}
			if ($data["_total_agents_"] > 0) {
				echo $link . $data["_total_agents_"] . "</a>";
			}
			echo "</td>";
			
			// Agents unknown
			echo "<td class='group_view_data group_view_data_unk $color_class' style='font-weight: bold; font-size: 18px; text-align: center;'>";
			if (isset($data['_is_tag_'])) {
				$link = "";
			} else {
				$link = "<a class='group_view_data $color_class' style='font-weight: bold; font-size: 18px; text-align: center;' 
				href='index.php?sec=estado&sec2=operation/agentes/estado_agente&group_id=".$data['_id_']."&status=" . AGENT_STATUS_UNKNOWN ."'>";
			}
			if ($data["_agents_unknown_"] > 0) {
				echo $link . $data["_agents_unknown_"] . "</a>";
			}
			echo "</td>";
			
			// Agents not init
			echo "<td class='group_view_data group_view_data_unk $color_class' style='font-weight: bold; font-size: 18px; text-align: center;'>";
			if (isset($data['_is_tag_'])) {
				$link = "";
			} else {
				$link = "<a class='group_view_data $color_class' style='font-weight: bold; font-size: 18px; text-align: center;' 
				href='index.php?sec=estado&sec2=operation/agentes/estado_agente&group_id=".$data['_id_']."&status=" . AGENT_STATUS_NOT_INIT ."'>";
			}
			if ($data["_agents_not_init_"] > 0) {
				echo $link . $data["_agents_not_init_"] . "</a>";
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
			if ($data["_monitors_unknown_"] > 0) {
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
			if ($data["_monitors_not_init_"] > 0) {
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
			if ($data["_monitors_ok_"] > 0) {
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
			if ($data["_monitors_warning_"] > 0) {
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
			if ($data["_monitors_critical_"] > 0) {
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
			if ($data["_monitors_alerts_fired_"] > 0){
				echo $link . $data["_monitors_alerts_fired_"] . "</a>";
			}
			echo '</td>';
			
			echo "</tr>";
		}
	echo '</table>';
} else {
	echo "<div class='nf'>" . __('There are no defined agents') .
		"</div>";
}
?>