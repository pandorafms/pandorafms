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

check_login ();

if (! check_acl ($config['id_user'], 0, "IW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access graph builder");
	include ("general/noaccess.php");
	exit;
}

require_once($config['homedir'] . "/include/functions_agents.php");
require_once($config['homedir'] . "/include/functions_modules.php");
require_once($config['homedir'] . "/include/functions_groups.php");

$editGraph = (bool) get_parameter('edit_graph', 0);

if (isset ($_GET["get_agent"])) {
	$id_agent = $_POST["id_agent"];
	if (isset($_POST["chunk"]))
		$chunkdata = $_POST["chunk"];
}

if ($editGraph) {
	$graphRows = db_get_all_rows_sql("SELECT t1.*,
		(SELECT t3.nombre 
			FROM tagente AS t3 
			WHERE t3.id_agente = 
				(SELECT t2.id_agente 
					FROM tagente_modulo AS t2
					WHERE t2.id_agente_modulo = t1.id_agent_module)) 
		AS agent_name
		FROM tgraph_source AS t1
		WHERE t1.id_graph = " . $id);
	$module_array = array();
	$weight_array = array();
	$agent_array = array();
	
	if($graphRows === false) {
			$graphRows = array();
	}
	
	foreach ($graphRows as $graphRow) {
		$idgs_array[] = $graphRow['id_gs'];
		$module_array[] = $graphRow['id_agent_module'];
		$weight_array[] = $graphRow['weight'];
		$agent_array[] = $graphRow['agent_name'];
	}
	
	$graphInTgraph = db_get_row_sql("SELECT * FROM tgraph WHERE id_graph = " . $id);
	$stacked = $graphInTgraph['stacked'];
	$events = $graphInTgraph['events'];
	$period = $graphInTgraph['period'];
	$width = $graphInTgraph['width'];
	$height = $graphInTgraph['height'];
	
	$modules = implode(',', $module_array);
	$weights = implode(',', $weight_array);
}

// Modules table
if(count($module_array) > 0){
	echo "<table width='98%' cellpadding=4 cellpadding=4 class='databox'>";
	echo "<tr>
	<th>".__('Agent')."</th>
	<th>".__('Module')."</th>
	<th>".__('Weight')."</th>
	<th>".__('Delete')."</th>";
	$color = 0;
	for ($a = 0; $a < count($module_array); $a++){
		// Calculate table line color
		if ($color == 1){
			$tdcolor = "datos";
			$color = 0;
		}
		else {
			$tdcolor = "datos2";
			$color = 1;
		}

		echo "<tr><td class='$tdcolor'>" . $agent_array[$a] . "</td>";
		echo "<td class='$tdcolor'>";
		echo modules_get_agentmodule_name ($module_array[$a])."</td>";
		echo "<td class='$tdcolor' align='center'>";
		echo "<table><tr>";
		$new_weight = $weight_array[$a]-0.125;
		echo "<td align='center'><a href='index.php?sec=reporting&sec2=godmode/reporting/graph_builder&edit_graph=1&tab=graph_editor&change_weight=1&weight=".$new_weight."&id=". $id ."&graph=" . $idgs_array[$a] . "'>".html_print_image('images/down.png', true, array ('title' => __('Decrease Weight')))."</a></td>";
		echo "<td align='center'>$weight_array[$a]</td>";
		$new_weight = $weight_array[$a]+0.125;
		echo "<td align='center'><a href='index.php?sec=reporting&sec2=godmode/reporting/graph_builder&edit_graph=1&tab=graph_editor&change_weight=1&weight=".$new_weight."&id=". $id ."&graph=" . $idgs_array[$a] . "'>".html_print_image('images/up.png', true, array ('title' => __('Increase Weight')))."</a></td>";
		echo "</tr></table>";
		echo "</td>";
		echo "<td class='$tdcolor' align='center'>";
		echo '<a onClick="if (!confirm (\'Are you sure?\')) return false;" href="index.php?sec=reporting&sec2=godmode/reporting/graph_builder&edit_graph=1&tab=graph_editor&delete_module=1&id='. $id .'&delete=' . $idgs_array[$a] . '">'.html_print_image('images/cross.png', true, array ('title' => __('Delete'))).'</a>';

		echo "</td></tr>";
	}
	echo "</table>";
}

//Configuration form

echo '<span id ="none_text" style="display: none;">' . __('None') . '</span>';
echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/graph_builder&tab=graph_editor&add_module=1&edit_graph=1&id=" . $id . "'>";

echo "<table width='98%' cellpadding='4' cellpadding='4' class='databox_color'>";
echo "<tr>";
echo "<td colspan='3'>".__('Filter group')."</td>";
echo "</tr><tr>";
echo "<td colspan='3'>".html_print_select(groups_get_all(), 'group', '', "filterByGroup($('#group').val());", __('All'), '0', true)."</td>";
echo "</tr><tr>";
echo "<td style='vertical-align: top;'>".__('Agents')."</td>";
echo "<td></td>";
echo "<td style='vertical-align: top;'>".__('Modules')."</td>";
echo "</tr><tr>";
// If metaconsole is activated
/*if ($config['metaconsole'] == 1) {
	enterprise_include_once('include/functions_metaconsole.php');
	
	$connection_names = enterprise_hook('metaconsole_get_connection_names');
	if ($connection_names === false)
		$connection_names = array();

	$agents_tmp = array();
	$agents = array();	
	
	foreach ($connection_names as $connection) {
		$connection_data = enterprise_hook('metaconsole_get_connection', array($connection));
		
		$connection_result = enterprise_hook('metaconsole_load_external_db', array($connection_data)); 
		
		if ($connection_result == NOERR) {
			$agents_tmp = agents_get_group_agents ();
			
			if ($agents_tmp === false)
				$agents_tmp = array();
				
			foreach ($agents_tmp as $agent_key => $agent_name) {
				$agents[$connection_data['server_name'] . '|' . $agent_key] = $agent_name . ' (' . $connection_data['server_name'] . ')';
			}
		}
		
		enterprise_hook('metaconsole_restore_db');
	}

}
else {*/
	echo "<td>".html_print_select (agents_get_group_agents(), 'id_agents[]', 0, false, '', '', true, true, true, '', false, 'width:300px;')."</td>";
//}	
echo "<td style='vertical-align: center; text-align: center;'>" . html_print_image("images/darrowright.png", true) . "</td>";
echo "<td>".html_print_select (array (), 'module[]', 0, false, '', 0, true, true, true, '', false, 'width:300px;')."</td>";
echo "</tr><tr>";
echo "<td colspan='3'>";
echo "<table cellpadding='4'><tr>";
echo "<td>".__('Weight')."</td>";
echo "<td><input type='text' name='weight' value='1' size=3></td>";
echo "</tr></table>";
echo "</td>";
echo "</tr><tr>";
echo "<td colspan='3' align='right'><input type=submit name='store' class='sub add' value='".__('Add')."'></td>";
echo "</tr></table></form>";

ui_require_jquery_file ('pandora.controls');
ui_require_jquery_file ('ajaxqueue');
ui_require_jquery_file ('bgiframe');
ui_require_jquery_file ('autocomplete');

?>
<script language="javascript" type="text/javascript">
$(document).ready (function () {
	$(document).data('text_for_module', $("#none_text").html());
	
	$("#id_agents").change(agent_changed_by_multiple_agents);
});

function filterByGroup(idGroup) {
	$('#id_agents').empty ();
	$('#module').empty();
	$("#module").append ($("<option></option>").attr ("value", 0).html ('<?php echo __('None');?>'));
	
	jQuery.post ("ajax.php",
			{"page" : "godmode/groups/group_list",
			"get_group_agents" : 1,
			"id_group" : idGroup
			},
			function (data, status) {
				i = 0
				jQuery.each (data, function (id, value) {
					i++;
					$("#id_agents").append ($("<option></option>").attr ("value", id).html (value));
				});
				if (i == 0) {
					$("#id_agents").append ($("<option></option>").attr ("value", 0).html ('<?php echo __('None');?>'));
				}
			},
			"json"
		);
}
</script>
