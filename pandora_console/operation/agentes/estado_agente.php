<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Load global vars
require_once ("include/config.php");
require_once ("include/functions_reporting.php");
check_login ();

if (! give_acl ($config['id_user'], 0, "AR")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access agent main list view");
	require ("general/noaccess.php");
	return;
}

if (is_ajax ()) {
	$get_agent_module_last_value = (bool) get_parameter ('get_agent_module_last_value');
	
	if ($get_agent_module_last_value) {
		$id_module = (int) get_parameter ('id_agent_module');
		
		if (! give_acl ($config['id_user'], get_agentmodule_group ($id_module), "AR")) {
			audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
				"Trying to access agent main list view");
			echo json_encode (false);
			return;
		}
		echo json_encode (get_agent_module_last_value ($id_module));
		return;
	}
	return;
}

// Take some parameters (GET)
$group_id = get_parameter ("group_id", 0);
$search = get_parameter ("search", "");

echo "<h2>".__('Pandora Agents')." &raquo; ".__('Summary')."</h2>";

if ($group_id > 1) {
	echo '<form method="post" action="'.get_url_refresh (array ('group_id' => $group_id)).'">';
} else {
	echo '<form method="post" action="'.get_url_refresh ().'">';
}

echo '<table cellpadding="4" cellspacing="4" class="databox" width="95%">';
echo '<tr><td style="white-space:nowrap;">'.__('Group').': ';

$groups = get_user_groups ();
print_select ($groups, 'group_id', $group_id, 'this.form.submit()', '', '');

echo '</td><td style="white-space:nowrap;">';

echo __('Free text for search').' (*): ';

print_input_text ("search", $search, '', 15);

echo '</td><td style="white-space:nowrap;">';

print_submit_button (__('Search'), "srcbutton", '', array ("class" => "sub")); 

echo '</td><td style="width:40%;">&nbsp;</td></tr></table></form>';

if ($search != ""){
	$search_sql = array ("string" => '%'.$search.'%');
} else {
	$search_sql = array ();
}

// Show only selected groups	
if ($group_id > 1) {
	$agent_names = get_group_agents ($group_id, $search_sql, "upper");
// Not selected any specific group
} else {
	$user_group = get_user_groups ($config["id_user"], "AR");
	$agent_names = get_group_agents (array_keys ($user_group), $search_sql, "upper");
}

if (!empty ($agent_names)) {
	$num_agents = get_db_sql (sprintf ("SELECT COUNT(*) FROM tagente WHERE id_agente IN (%s)", implode (",", array_keys ($agent_names))));
	$agents = get_db_all_rows_sql (sprintf ("SELECT * FROM tagente WHERE id_agente IN (%s) ORDER BY nombre ASC LIMIT %d,%d", implode (",", array_keys ($agent_names))));
}

if (empty ($agents)) {
	$agents = array ();
}

// Prepare pagination
pagination ($num_agents, get_url_refresh (array ('group_id' => $group_id, 'search' => $search)));

// Show data.
$table->cellpadding = 4;
$table->cellspacing = 4;
$table->width = "98%";
$table->class = "databox";

$table->head = array ();
$table->head[0] = __('Agent');
$table->head[1] = __('OS');
$table->head[2] = __('Interval');
$table->head[3] = __('Group');
$table->head[4] = __('Modules');
$table->head[5] = __('Status');
$table->head[6] = __('Alerts');
$table->head[7] = __('Last contact');

$table->align = array ();
$table->align[1] = "center";
$table->align[2] = "center";
$table->align[3] = "center";
$table->align[4] = "center";
$table->align[5] = "center";
$table->align[6] = "center";
$table->align[7] = "right";

$table->data = array ();

foreach ($agents as $agent) {
	$agent_info = get_agent_module_info ($agent["id_agente"]);
	
	$data = array ();
	
	$data[0] = '';
	if (give_acl ($config['id_user'], $agent["id_grupo"], "AW")) {
		$data[0] .= '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;id_agente='.$agent["id_agente"].'">';
		$data[0] .= print_image ("images/setup.png", true, array ("border" => 0, "width" => 16));
		$data[0] .= '</a>&nbsp;';
	}
		
	$data[0] .= print_agent_name ($agent["id_agente"], true, "upper");
	
	$data[1] = print_os_icon ($agent["id_os"], false, true);

	if ($agent_info["interval"] > $agent["intervalo"]) {
		$data[2] = '<span class="green">'.$agent_info["interval"].'</span>';
	} else {
		$data[2] = $agent["intervalo"];
	}
	
	$data[3] = print_group_icon ($agent["id_grupo"], true);
	
	$data[4] = '<b>';
	$data[4] .= $agent_info["modules"];
	if ($agent_info["monitor_normal"] > 0)
		$data[4] .= '</b> : <span class="green">'.$agent_info["monitor_normal"].'</span>';
	if ($agent_info["monitor_warning"] > 0)
		$data[4] .= ' : <span class="yellow">'.$agent_info["monitor_warning"].'</span>';
	if ($agent_info["monitor_critical"] > 0)
		$data[4] .= ' : <span class="red">'.$agent_info["monitor_critical"].'</span>';
	if ($agent_info["monitor_down"] > 0)
		$data[4] .= ' : <span class="grey">'.$agent_info["monitor_down"].'</span>';
	
	$data[5] = $agent_info["status_img"];
	
	$data[6] = $agent_info["alert_img"];
	
	$data[7] = print_timestamp ($agent_info["last_contact"], true);
	
	array_push ($table->data, $data);
}

if (!empty ($table->data)) {
	print_table ($table);
	unset ($table);
	require ("bulbs.php");
} else {
	echo '<div class="nf">'.__('There are no agents included in this group').'</div>';
}

if (give_acl ($config['id_user'], 0, "LM") || give_acl ($config['id_user'], 0, "AW")
		|| give_acl ($config['id_user'], 0, "PM") || give_acl ($config['id_user'], 0, "DM")
		|| give_acl ($config['id_user'], 0, "UM")) {
	
	echo '<form method="post" action="index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente">';
		print_input_hidden ('new_agent', 1);
		print_submit_button (__('Create agent'), 'crt', false, 'class="sub next"');
	echo '</form>';
}
?>
