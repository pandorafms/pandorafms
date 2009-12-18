<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
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

check_login();

$module_id = get_parameter_get ("id", 0);
$period = get_parameter ("period", 86400);
$group = get_agentmodule_group ($module_id);

if (! give_acl ($config['id_user'], $group, "AR") || $module_id == 0) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access Agent Data view");
	require ("general/noaccess.php");
	return;
}

if (isset ($_GET["delete"])) {
	$delete = get_parameter_get ("delete", 0);
	$sql = sprintf ("DELETE FROM tagente_datos WHERE id_agente_datos = %d", $delete);
	process_sql ($sql);
} elseif (isset($_GET["delete_text"])) {
	$delete = get_parameter_get ("delete_string", 0);
	$sql = sprintf ("DELETE FROM tagente_datos_string WHERE id_tagente_datos_string = %d", $delete);
	process_sql ($sql);
}

// Different query for string data type
if (preg_match ("/string/", get_moduletype_name (get_agentmodule_type ($module_id)))) {
	$sql = sprintf ("SELECT * 
		FROM tagente_datos_string 
		WHERE id_agente_modulo = %d AND utimestamp > %d 
		ORDER BY utimestamp DESC
		LIMIT %d OFFSET %d", $module_id, get_system_time () - $period, $config['block_size'], get_parameter ('offset'));
	$sqlCount = sprintf ("SELECT COUNT(*) FROM tagente_datos_string WHERE id_agente_modulo = %d AND utimestamp > %d ORDER BY utimestamp DESC", $module_id, get_system_time () - $period);
	$string_type = 1;
} else {
	$sql = sprintf ("SELECT * 
		FROM tagente_datos 
		WHERE id_agente_modulo = %d AND utimestamp > %d 
		ORDER BY utimestamp DESC
		LIMIT %d OFFSET %d", $module_id, get_system_time () - $period, $config['block_size'], get_parameter ('offset'));
	$sqlCount = sprintf ("SELECT COUNT(*) FROM tagente_datos WHERE id_agente_modulo = %d AND utimestamp > %d ORDER BY utimestamp DESC", $module_id, get_system_time () - $period);
	$string_type = 0;
}

$countData = get_db_value_sql($sqlCount); 
$result = get_db_all_rows_sql ($sql, true);
if ($result === false) {
	$result = array ();
}

echo "<h2>".__('Received data from')." ".get_agentmodule_agent_name ($module_id)." / ".get_agentmodule_name ($module_id)." </h2>";
echo "<h3>" . __("From the last") . " " . human_time_description ($period) ."</h3>";

echo "<form method='post' action='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=4&tab=data_view&id=17'>";
echo __("Choose a time from now") . ": ";
$intervals = array ();
$intervals[3600] = human_time_description_raw (3600); // 1 hour
$intervals[86400] = human_time_description_raw (86400); // 1 day 
$intervals[604800] = human_time_description_raw (604800); // 1 week
$intervals[2592000] = human_time_description_raw (2592000); // 1 month
echo print_extended_select_for_time ($intervals, 'period', $period, 'this.form.submit();', '', '0', 10) . __(" seconds.");
echo "</form><br />";

if ($result === false) {
	echo '<h3 class="error">'.__('There was a problem locating the source of the graph').'</h3>';
}
else {
	pagination ($countData, false) ;
	
	echo '
	<table width="600" cellpadding="3" cellspacing="3" border="0" class="databox" id="table1">
		<thead>
			<tr>
				<th class="header c0"  scope="col">' . __('Delete') . '</th>
				<th class="header c1"  scope="col">' . __('Timestamp') . '</th>
				<th class="header c2"  scope="col">' . __('Data') . '</th></tr>
		</thead>
	';
	$count = 0;
	foreach ($result as $row) {
		
		if (($count % 2) == 0) 
			$classPairOdd = 'rowPair';
		else
			$classPairOdd = 'rowOdd';
		
		if ($count > 100) break;
		$count++;
		
		echo('<tr id="table1-3" style="" class="datos ' . $classPairOdd . '">');
		
		if (give_acl ($config['id_user'], $group, "AW") ==1) {
			if ($string_type == 0) {
				echo('<td id="table1-' . $count . '-0" style="text-align: center;" class="datos">
					<a href="index.php?sec=estado&sec2=operation/agentes/datos_agente&period='.$period.'&id='.$module_id.'&delete='.$row["id_agente_datos"].'&offset=' . get_parameter ('offset') . '"><img src="images/cross.png" border="0" /></a>
				</td>');
			}
			else {
				echo('<td id="table1-' . $count . '-0" style="text-align: center;" class="datos">
					<a href="index.php?sec=estado&sec2=operation/agentes/datos_agente&period='.$period.'&id='.$module_id.'&delete_string='.$row["id_tagente_datos_string"].'&offset=' . get_parameter ('offset') . '"><img src="images/cross.png" border="0" /></a>
				</td>');
			}
		}
		else {
			echo('<td id="table1-' . $count . '-0" style="text-align: center;" class="datos"></td>');
		}
		
		// This returns data with absolute user-defined timestamp format
		// and numeric by data managed with 2 decimals, and not using Graph format 
		// (replacing 1000 by K and 1000000 by G, like version 2.x
		
		echo('<td id="table1-' . $count . '-1" style="text-align: center;" class="datos">' .
			date ($config["date_format"], $row["utimestamp"]) .
			'</td>');
		if (is_numeric ($row["datos"])) {
			echo('<td id="table1-' . $count . '-2" style="text-align: center;" class="datos">' .
				format_numeric($row["datos"],2) .
			'</td>');
		}
		else {
			echo('<td id="table1-' . $count . '-2" style="text-align: center;" class="datos">' .
				safe_input ($row["datos"]) .
			'</td>');
		}
		echo('</tr>');
	}
	echo '</table>';
	
	pagination ($countData, false) ;
	echo "<h3>" . __('Total') . ' ' . $countData . ' ' . __('Data') . "</h3>";
}

?>
