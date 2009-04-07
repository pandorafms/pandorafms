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
enterprise_include ("operation/snmpconsole/snmp_view.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "AR")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access SNMP Console");
	require ("general/noaccess.php");
	exit;
}

// Read parameters
$filter_agent = (string) get_parameter ("filter_agent", '');
$filter_oid = (string) get_parameter ("filter_oid", '');
$filter_severity = (int) get_parameter ("filter_severity", -1);
$filter_fired = (int) get_parameter ("filter_fired", -1);
$search_string = (string) get_parameter ("search_string", '');
$pagination = (int) get_parameter ("pagination", $config["block_size"]);
$offset = (int) get_parameter ('offset',0);
$url = "index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_view&filter_agent=".$filter_agent."&filter_oid=".$filter_oid."&filter_severity=".$filter_severity."&filter_fired=".$filter_fired."&search_string=".$search_string."&pagination=".$pagination."&offset=".$offset;

// OPERATIONS

// Delete SNMP Trap entry Event (only incident management access).
if (isset ($_GET["delete"])){
	$id_trap = (int) get_parameter_get ("delete", 0);
	if ($id_trap > 0 && give_acl ($config['id_user'], 0, "IM")) {
		$sql = sprintf ("DELETE FROM ttrap WHERE id_trap = %d", $id_trap);
		$result = process_sql ($sql);
		print_result_message ($result,
			__('Successfully deleted'),
			__('Could not be deleted'));
	} else {
		audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
			"Trying to delete SNMP event ID #".$id_trap);
	}
}

// Check Event (only incident write access).
if (isset ($_GET["check"])) {
	$id_trap = (int) get_parameter_get ("check", 0);
	if ($id_trap > 1 && give_acl ($config['id_user'], 0, "IW")) {
		$sql = sprintf ("UPDATE ttrap SET status = 1, id_usuario = '%s' WHERE id_trap = %d", $config["id_user"], $id_trap);
		$result = process_sql ($sql);
		print_result_message ($result,
			__('Successfully updated'),
			__('Could not be updated'));
	} else {
		audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
			"Trying to checkout SNMP Trap ID".$id_trap);
	}
}

// Mass-process DELETE
if (isset ($_POST["deletebt"])) {
	$trap_ids = get_parameter_post ("snmptrapid", array ());
	if (is_array ($trap_ids) && give_acl ($config['id_user'], 0, "IW")) {
		foreach ($trap_ids as $id_trap) {
			$sql = sprintf ("DELETE FROM ttrap WHERE id_trap = %d", $id_trap);
			process_sql ($sql);
		}
	} else {
		audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
			"Trying to mass-delete SNMP Trap ID");
	}
}

// Mass-process UPDATE
if (isset ($_POST["updatebt"])) {
	$trap_ids = get_parameter_post ("snmptrapid", array ());
	if (is_array ($trap_ids) && give_acl ($config['id_user'], 0, "IW")) {
		foreach ($trap_ids as $id_trap) {
			$sql = sprintf ("UPDATE ttrap SET status = 1, id_usuario = '%s' WHERE id_trap = %d", $config["id_user"], $id_trap);
			process_sql ($sql);
                }
	} else {
		audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
			"Trying to mass-delete SNMP Trap ID");
	}
}

echo "<h2>" . __('SNMP console');

if ($config["pure"]) {
	echo '&nbsp;<a target="_top" href="'.$url.'&pure=0&refr=30"><img src="images/monitor.png" title="'.__('Normal screen').'" /></a>';
} else {
	// Fullscreen
	echo '&nbsp;<a target="_top" href="'.$url.'&pure=1&refr=0"><img src="images/monitor.png" title="'.__('Full screen').'" /></a>';
}
echo "</h2>";

$sql = sprintf ("SELECT * FROM ttrap ORDER BY timestamp DESC LIMIT %d,%d",$offset,$config['block_size']);
$traps = get_db_all_rows_sql ($sql);

// No traps 
if (empty ($traps)) {
	echo '<div class="nf">'.__('There are no SNMP traps in database').'</div>';
	return;
}

// Toggle filters
echo '<a href="#" onmousedown="toggleDiv(\'filters\');"><b>'.__('Toggle filters').'</b>&nbsp;<img src="images/wand.png" /></a>';

echo '<form method="POST" action="index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_view&refr='.$config["refr"].'&pure='.$config["pure"].'">';
$table->width = '90%';
$table->size = array ();
$table->size[0] = '120px';
$table->data = array ();

// Set filters
$agents = array ();
$oids = array ();
$severities = get_priorities ();
$alerted = array (__('Not fired'), __('fired'));
foreach ($traps as $trap) {
	$agent = get_agent_with_ip ($trap['source']);
	$agents[$trap["source"]] = $agent !== false ? $agent["nombre"] : $trap["source"];
	$oid = enterprise_hook ('get_oid', array ($trap));
	if ($oid === ENTERPRISE_NOT_HOOK) {
		$oid = $trap["oid"];
	}
	$oids[$oid] = $oid;
}

if ($config["pure"] == 1) {
	echo '<div id="filters" style="display:none;">';
} else {
	echo '<div id="filters" style="display:block;">'; //There is no value all to property display
}

// Agent select
$table->data[0][0] = '<strong>'.__('Agent').'</strong>';
$table->data[0][1] = print_select ($agents, 'filter_agent', $filter_agent, 'javascript:this.form.submit();', __('All'), '', true);

// OID select
$table->data[0][2] = '<strong>'.__('OID').'</strong>';
$table->data[0][3] = print_select ($oids, 'filter_oid', $filter_oid, 'javascript:this.form.submit();', __('All'), '', true);

// Alert status select
$table->data[1][0] = '<strong>'.__('Alert').'</strong>';
$table->data[1][1] = print_select ($alerted, "filter_fired", $filter_fired, 'javascript:this.form.submit();', __('All'), '-1', true);

// String search_string
$table->data[1][2] = '<strong>'.__('Search value').'</strong>';
$table->data[1][3] = print_input_text ('search_string', $search_string, '', 25, 0, true);

// Block size for pagination select
$table->data[2][0] = '<strong>'.__('Block size for pagination').'</strong>';
$lpagination[25]=25;
$lpagination[50]=50;
$lpagination[100]=100;
$lpagination[200]=200;
$lpagination[500]=500;
$table->data[2][1] = print_select ($lpagination, "pagination", $pagination, 'this.form.submit();', __('Default'), $config["block_size"], true);

// Severity select
$table->data[2][2] = '<strong>'.__('Severity').'</strong>';
$table->data[2][3] = print_select ($severities, 'filter_severity', $filter_severity, 'this.form.submit();', __('All'), -1, true);

print_table ($table);
unset ($table);

echo '</form>';
echo '</div>';

echo '<br />';

// Prepare index for pagination
$trapcount = get_db_sql ("SELECT COUNT(*) FROM ttrap");
pagination ($trapcount, "index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_view&filter_agent=".$filter_agent."&filter_oid=".$filter_oid."&pagination=".$pagination."&offset=".$offset."&refr=".$config["refr"]."&pure=".$config["pure"], $offset, $pagination);

echo '<form name="eventtable" method="POST" action="index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_view&pagination='.$pagination.'&offset='.$offset.'">';

$table->cellpadding = 4;
$table->cellspacing = 4;
$table->width = 735;
$table->class = "databox";
$table->head = array ();
$table->size = array ();
$table->data = array ();
$table->align = array ();

$table->head[0] = __('Status');
$table->align[0] = "center";

$table->head[1] = __('SNMP Agent');
$table->align[1] = "center";

$table->head[2] = __('OID');
$table->align[2] = "center";

$table->head[3] = __('Value');
$table->align[3] = "center";

$table->head[4] = __('Custom');
$table->align[4] = "center";

$table->head[5] = __('User ID');
$table->align[5] = "center";

$table->head[6] = __('Timestamp');
$table->size[6] = 130;
$table->align[6] = "center";

$table->head[7] = __('Alert');
$table->align[7] = "center";

$table->head[8] = __('Action');
$table->align[8] = "center";

$table->head[9] = print_checkbox_extended ("allbox", 1, false, false, "javascript:CheckAll();", 'class="chk" title="'.__('All').'"', true);
$table->align[9] = "center";

// Skip offset records
$idx = 0;
foreach ($traps as $trap) {
	$data = array ();

	// Apply filters
	if ($filter_agent != '' && $trap["source"] != $filter_agent) {
		continue;
	}
	
	$oid = enterprise_hook ('get_oid', array ($trap));
	if ($oid === ENTERPRISE_NOT_HOOK) {
		$oid = $trap["oid"];
	}

	if ($filter_oid != '' && $oid != $filter_oid) {
		continue;
	}

	if ($filter_fired != -1 && $trap["alerted"] != $filter_fired) {
		continue;
	}

	$severity = enterprise_hook ('get_severity', array ($trap));
	if ($severity === ENTERPRISE_NOT_HOOK) {
		$severity = $trap["alerted"] == 1 ? $trap["priority"] : 1;
	}

	if ($filter_severity != -1 && $severity != $filter_severity) {
		continue;
	}

	if ($search_string != '' && ereg ($search_string, $trap["value"]) == 0 && 
	    ereg ($search_string, $trap["value_custom"]) == 0) {
	    continue;
	}

	//Status
	if ($trap["status"] == 0) {
		$data[0] = '<img src="images/pixel_red.png" title="'.__('Not validated').'" width="20" height="20" />';
	} else {
		$data[0] = '<img src="images/pixel_green.png" title="'.__('Validated').'" width="20" height="20" />';
	}

	// Agent matching source address
	$agent = get_agent_with_ip ($trap['source']);
	if ($agent !== false && ! give_acl ($config["id_user"], $agent["id_grupo"], "AR")) {
		//Agent found, no rights
		continue;
	} elseif ($agent === false) {
		//Agent not found
		$data[1] = $trap["source"];
		if (give_acl ($config["id_user"], 0, "AW")) {
			//We have rights to create agents
			$data[1] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&create_agent=1&direccion='.$data[1].'" title="'.__('Create agent').'">'.$data[1].'</a>';	
		}
	} else {
		//Agent found
		$data[1] = '<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$agent["id_agente"].'" title="'.__('View agent details').'">';
		$data[1] .= '<strong>'.$agent["nombre"].'</strong></a>';
	}

	//OID
	if (empty ($trap["oid"])) {
		$data[2] = __('N/A');
	} else {
		$data[2] = enterprise_hook ('editor_link', array ($trap));
		if ($data[2] === ENTERPRISE_NOT_HOOK) {
			$data[2] = $trap["oid"];
		}
	}

	//Value
	$data[3] = substr ($trap["value"], 0, 15);
	
	if (empty ($data[3])) {
		$data[3] = __('N/A');
	} elseif (strlen ($trap["value"]) > 15) {
		$data[3] = '<span title="'.$trap["value"].'">'.$data[3].'...</span>';
	}
	
	//Custom
	$data[4] = '<span title="' . $trap["oid_custom"] . '">' . $trap["value_custom"] . '</span>';

	if (empty ($data[4])) {
		$data[4] = __('N/A');
	} elseif (strlen ($trap["value_custom"]) > 15) {
		$data[4] = '<span title="'.$trap["value_custom"].'">'.$data[4].'...</span>';
	}	

	//User
	if (!empty ($trap["status"])) {
		$data[5] = '<a href="index.php?sec=usuarios&sec2=operation/users/user_edit&ver='.$trap["id_usuario"].'">'.substr ($trap["id_usuario"], 0, 8).'</a>';
		$data[5] .= '<a href="#" class="tip">&nbsp;<span>'.dame_nombre_real($trap["id_usuario"]).'</span></a>';
	} else {
		$data[5] = '--';
	}
	
	// Timestamp
	$data[6] = '<span title="'.$trap["timestamp"].'">';
	$data[6] .= human_time_comparation ($trap["timestamp"]);
	$data[6] .= '</span>';
	
	// Use alert severity if fired
	if (!empty ($trap["alerted"])) {
		$data[7] = '<img src="images/pixel_yellow.png" width="20" height="20" border="0" title="'.__('Alert fired').'" />';		
	} else {
		$data[7] = '<img src="images/pixel_gray.png" width="20" height="20" border="0" title="'.__('Alert not fired').'" />';
	}

	// Severity	
	$table->rowclass[$idx] = get_priority_class ($severity);

	//Actions
	$data[8] = "";
	if (empty ($trap["status"]) && give_acl ($config["id_user"], 0, "IW")) {
		$data[8] .= '<a href="index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_view&check='.$trap["id_trap"].'"><img src="images/ok.png" border="0" title="'.__('Validate').'" /></a>';
	}
	if (give_acl ($config["id_user"], 0, "IW")) {
		$data[8] .= '<a href="index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_view&delete='.$trap["id_trap"].'&offset='.$offset.'" onClick="javascript:confirm(\''.__('Are you sure').'\')"><img src="images/cross.png" border="0" title="'.__('Delete').'"/></a>';
	}
	
	$data[9] = print_checkbox_extended ("snmptrapid[]", $trap["id_trap"], false, false, '', 'class="chk"', true);

	array_push ($table->data, $data);
	$idx++;
}

// No matching traps
if ($idx == 0) {
	echo '<div class="nf">'.__('No matching traps found').'</div>';
} else {
	print_table ($table);	
}

unset ($table);

echo '<div style="width:735px; text-align:right;">';
print_submit_button (__('Validate'), "updatebt", false, 'class="sub ok"');

if (give_acl ($config['id_user'], 0, "IM")) {
	echo "&nbsp;";
	print_submit_button (__('Delete'), "deletebt", false, 'class="sub delete" onClick="javascript:confirm(\''.__('Are you sure').'\')"');
}
echo "</div></form>";


echo '<div style="float:left; padding-left:30px; line-height: 17px; vertical-align: top; width:120px;">';
echo '<h3>' . __('Status') . '</h3>';
echo '<img src="images/pixel_green.png" width="20" height="20" /> - ' . __('Validated');
echo '<br />';
echo '<img src="images/pixel_red.png" width="20" height="20" /> - ' . __('Not validated');
echo '</div>';
echo '<div style="float:left; padding-left:30px; line-height: 17px; vertical-align: top; width:120px;">';
echo '<h3>' . __('Alert') . '</h3>';
echo '<img src="images/pixel_yellow.png" width="20" height="20" /> - '  .__('Fired');
echo '<br />';
echo '<img src="images/pixel_gray.png" width="20" height="20" /> - ' . __('Not fired');
echo '</div>';
echo '<div style="float:left; padding-left:30px; line-height: 19px; vertical-align: top; width:120px;">';
echo '<h3>' . __('Action') . '</h3>';
echo '<img src="images/ok.png" width="18" height="18" /> - '  .__('Validate');
echo '<br />';
echo '<img src="images/cross.png" width="18" height="18" /> - ' . __('Delete');
echo '</div>';
echo '<div style="float:left; padding-left:30px; line-height: 17px; vertical-align: top; width:120px;">';
echo '<h3>'.__('Legend').'</h3>';
foreach (get_priorities () as $num => $name) {
	echo '<span class="'.get_priority_class ($num).'">'.$name.'</span>';
	echo '<br />';
}
echo '</div>';
echo '<div style="clear:both;">&nbsp;</div>';
?>

<script language="JavaScript" type="text/javascript">
<!--
function CheckAll() {
        for (var i = 0; i < document.eventtable.elements.length; i++) {
                var e = document.eventtable.elements[i];
                if (e.type == 'checkbox' && e.name != 'allbox')
                        e.checked = !e.checked;
        }
}

function toggleDiv (divid){
	if (document.getElementById(divid).style.display == 'none'){
		document.getElementById(divid).style.display = 'block';
	} else {
		document.getElementById(divid).style.display = 'none';
	}
}
//-->
</script>
