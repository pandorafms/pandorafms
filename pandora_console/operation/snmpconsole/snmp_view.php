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

check_login ();

if (! give_acl ($config['id_user'], 0, "AR")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access SNMP Console");
	require ("general/noaccess.php");
	exit;
}

// OPERATIONS

// Delete SNMP Trap entry Event (only incident management access).
if (isset ($_GET["delete"])){
	$id_trap = (int) get_parameter_get ("delete", 0);
	if ($id_trap > 0 && give_acl ($config['id_user'], 0, "IM")) {
		$sql = sprintf ("DELETE FROM ttrap WHERE id_trap = %d", $id_trap);
		$result = process_sql ($sql);
		print_error_message ($result, __('Event successfully deleted'), __('Error removing event'));
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
		print_error_message ($result, __('Event successfully updated'), __('Error updating event'));
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

echo "<h2>Pandora SNMP &gt; ".__('SNMP console')."</h2>";

$offset = (int) get_parameter ('offset',0);


$sql = sprintf ("SELECT * FROM ttrap ORDER BY timestamp DESC LIMIT %d,%d",$offset,$config['block_size']);
$traps = get_db_all_rows_sql ($sql);

if (empty ($traps)) {
	echo '<div class="nf">'.__('There are no SNMP traps in database').'</div>';
	return;
}

echo '<table border="0" width="735"><thead><th style="width:33%">'.__('Status').'</th>';
echo '<th style="width:34%">'.__('Alert').'</th>';
echo '<th style="width:33%">'.__('Action').'</th></thead><tbody><tr>';
echo '<td class="f9" style="padding-left: 30px;">';
echo '<img src="images/pixel_green.png" width="20" height="20" /> - '.__('Validated event');
echo '<br />';
echo '<img src="images/pixel_red.png" width="20" height="20" /> - '.__('Not validated event');
echo '</td><td class="f9" style="padding-left: 30px;">';
echo '<img src="images/pixel_yellow.png" width="20" height="20" /> - '.__('Alert fired');
echo '</td><td class="f9" style="padding-left: 30px;">';
echo '<img src="images/ok.png" /> - '.__('Validate event');
echo '<br />'; 
echo '<img src="images/cross.png" /> - '.__('Delete event');
echo '</td></tr></tbody></table>';
echo '<br />';

// Prepare index for paginationi
$trapcount = get_db_sql ("SELECT COUNT(*) FROM ttrap");
pagination ($trapcount, "index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_view", $offset);

echo "<br />";
echo '<form name="eventtable" method="POST" action="index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_view&refr=60&offset='.$offset.'">';

$table->cellpadding = 4;
$table->cellspacing = 4;
$table->width = 735;
$table->class = "databox";
$table->head = array ();
$table->size = array ();
$table->data = array ();

$table->head[0] = __('Status');
$table->head[1] = __('SNMP Agent');
$table->head[2] = __('OID');
$table->head[3] = __('Value');
$table->head[4] = __('Custom');
$table->head[5] = __('User ID');

$table->head[6] = __('Timestamp');
$table->size[6] = 130;

$table->head[7] = __('Alert');
$table->head[8] = __('Action');

$table->head[9] = print_checkbox_extended ("allbox", 1, false, false, "javascript:CheckAll();", 'class="chk" title="'.__('All').'"', true);

// Skip offset records
foreach ($traps as $trap) {
	$data = array ();
	
	//Status
	if ($trap["status"] == 0) {
		$data[0] = '<img src="images/pixel_red.png" title="'.__('Not validated').'" width="20" height="20" />';
	} else {
		$data[0] = '<img src="images/pixel_green.png" title="'.__('Validated').'" width="20" height="20" />';
	}

	// Agent matching
	$agent = get_db_row ('tagente', 'direccion', $trap['source']);
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
		$data[1] .= '<b>'.$agent["nombre"].'</b></a>';
	}

	//OID
	$data[2] = $trap["oid"];
	if (empty ($data[2]))
		$data[2] = __('N/A');
	
	//Value
	$data[3] = substr ($trap["value"], 0, 15);
	
	if (empty ($data[3])) {
		$data[3] = __('N/A');
	} elseif (strlen ($trap["value"]) > 15) {
		$data[3] = '<span title="'.$trap["value"].'">'.$data[3].'...</span>';
	}
	
	//Custom
	$data[4] = $trap["value_custom"];

	if (empty ($data[4])) {
		$data[4] = __('N/A');
	} elseif (strlen ($trap["value_custom"]) > 15) {
		$data[4] = '<span title="'.$trap["custom_value"].'">'.$data[4].'...</span>';
	}	

	//User
	if (!empty ($trap["status"])) {
		$data[5] = '<a href="index.php?sec=usuarios&sec2=operation/users/user_edit&ver='.$trap["id_usuario"].'">'.substr ($trap["id_usuario"], 0, 8).'</a>';
		$data[5] .= '<a href="#" class="tip">&nbsp;<span>'.dame_nombre_real($trap["id_usuario"]).'</span></a>';
	} else {
		$data[5] = '--';
	}
	
	// Timestamp
	$data[6] = $trap["timestamp"];

	//Alert fired
	if (!empty ($trap["alerted"])) {
		$data[7] = '<img src="images/pixel_yellow.png" width="40" height="18" border="0" title="'.__('Alert fired').'" />';
	} else {
		$data[7] = '--';
	}

	//Actions
	$data[8] = "";
	if (empty ($trap["status"]) && give_acl ($config["id_user"], 0, "IW")) {
		$data[8] .= '<a href="index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_view&check='.$trap["id_trap"].'"><img src="images/ok.png" border="0" title="'.__('Validate').'" /></a>';
	}
	if (give_acl ($config["id_user"], 0, "IW")) {
		$data[8] .= '<a href="index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_view&delete='.$trap["id_trap"].'&refr=60&offset='.$offset.'" onClick="javascript:confirm(\''.__('Are you sure').'\')"><img src="images/cross.png" border="0" title="'.__('Delete').'"/></a>';
	}
	
	$data[9] = print_checkbox_extended ("snmptrapid[]", $trap["id_trap"], false, false, '', 'class="chk"', true);

	array_push ($table->data, $data);
}

print_table ($table);
unset ($table);

echo '<div style="width:735px; text-align:right;">';
print_submit_button (__('Validate'), "updatebt", false, 'class="sub ok"');

if (give_acl ($config['id_user'], 0, "IM")) {
	print_submit_button (__('Delete'), "deletebt", false, 'class="sub delete" onClick="javascript:confirm(\''.__('Are you sure').'\')"');
}
echo "</div></form>";


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
//-->
</script>
