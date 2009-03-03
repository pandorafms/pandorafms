<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License (LGPL)
// as published by the Free Software Foundation for version 2.
// 
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Load global vars
require_once ("include/config.php");
require_once ("include/functions_events.php");

check_login ();

if (give_acl ($config['id_user'], 0, "AR") != 1) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", 
	"Trying to access Agent view (Grouped)");
	require ("general/noaccess.php");
	exit;
}

require_once ("include/functions_reporting.php");

//This is an intermediary function to print out a set of cells
//Cells is an array with the explanation, value, link and color
function print_cells_temp ($cells) {
	foreach ($cells as $key => $row) {
		//Switch class around
		$class = (($key % 2) ? "datos2" : "datos");
		echo '<tr><td class="'.$class.'"><b>'.$row[0].'</b></td>';
		if ($row[1] === 0) {
			$row[1] = "-";
		}
		echo '<td class="'.$class.'" style="text-align:right;"><a class="big_data" href="'.safe_input ($row["href"]).'" style="color: '.$row["color"].';">'.$row[1].'</a></td></tr>';
	}	
}


echo "<h2>".__('Pandora Agents')." &gt; ".__('Tactical view')."</h2>";

$data = get_group_stats ();

echo '<div style="width:265px; float:left;  padding-right: 40px;" id="leftcolumn">';
// Monitor checks

$table->width = "100%";
$table->class = "databox";
$table->cellpadding = 0;
$table->cellspacing = 0;
$table->border = 0;
$table->head = array ();
$table->data = array ();
$table->style = array ();

$img = "reporting/fgraph.php?tipo=progress&height=20&width=260&mode=0&percent=";

$table->style[0] = "padding-top:4px; padding-bottom:4px;";
$table->data[0][0] ='<b>'.__('Monitor health').'</b>';

$table->style[1] = "padding-top:4px; padding-bottom:4px;";
$table->data[1][0] = print_image ($img.$data["monitor_health"], true, array ("title" => $data["monitor_health"].'% '.__('of monitors up')));

$table->style[2] = "padding-top:4px; padding-bottom:4px;";
$table->data[2][0] = '<b>'.__('Module sanity').'</b>';

$table->style[3] = "padding-top:4px; padding-bottom:4px;";
$table->data[3][0] = print_image ($img.$data["module_sanity"], true, array ("title" => $data["module_sanity"].'% '.__('of total modules inited')));

$table->style[4] = "padding-top:4px; padding-bottom:4px;";
$table->data[4][0] = '<b>'.__('Alert level').'</b>';

$table->style[5] = "padding-top:4px; padding-bottom:4px;";
$table->data[5][0] = print_image ($img.$data["alert_level"], true, array ("title" => $data["alert_level"].'% '.__('of defined alerts not fired')));
	
print_table ($table);
unset ($table);

echo '<table class="databox" cellpadding="4" cellspacing="4" width="100%">';
echo '<tr><th colspan="2">'.__('Monitor checks').'</th></tr>';
	
$cells = array ();
$cells[0][0] = __('Monitor checks');
$cells[0][1] = $data["monitor_checks"];
$cells[0]["href"] = "index.php?sec=estado&sec2=operation/agentes/status_monitor&refr=60&status=-1"; //All
$cells[0]["color"] = "#000";

$cells[1][0] = __('Monitors normal');
$cells[1][1] = $data["monitor_ok"];
$cells[1]["href"] = "index.php?sec=estado&sec2=operation/agentes/status_monitor&refr=60&status=0"; //Up
$cells[1]["color"] = "#000";

$cells[2][0] = __('Monitors warning');
$cells[2][1] = $data["monitor_warning"];
$cells[2]["href"] = "index.php?sec=estado&sec2=operation/agentes/status_monitor&refr=60&status=1"; //Down
$cells[2]["color"] = "#FFB900";
	
$cells[3][0] = __('Monitors critical');
$cells[3][1] = $data["monitor_critical"];
$cells[3]["href"] = "index.php?sec=estado&sec2=operation/agentes/status_monitor&refr=60&status=2"; //Down
$cells[3]["color"] = "#f00";

$cells[4][0] = __('Monitors unknown');
$cells[4][1] = $data["monitor_unknown"];
$cells[4]["href"] = "index.php?sec=estado&sec2=operation/agentes/status_monitor&refr=60&status=3"; //Unknown
$cells[4]["color"] = "#C0C0C0";

$cells[5][0] = __('Monitors not init');
$cells[5][1] = $data["monitor_not_init"];
$cells[5]["color"] = "#f00";
$cells[5]["href"] = "#";

$cells[6][0] = __('Alerts defined');
$cells[6][1] = $data["monitor_alerts"];
$cells[6]["href"] = "index.php?sec=estado&sec2=operation/agentes/alerts_status&refr=60"; //All alerts defined
$cells[6]["color"] = "#000";

$cells[7][0] = __('Alerts fired');
$cells[7][1] = $data["monitor_alerts_fired"];
$cells[7]["href"] = "index.php?sec=eventos&sec2=operation/events/events&search=&event_type=alert_fired"; //Fired alert events
$cells[7]["color"] = "#f00";

print_cells_temp ($cells);
	
echo '<tr><th colspan="2">'.__('Summary').'</th></tr>';

$cells = array ();
$cells[0][0] = __('Total Agents');
$cells[0][1] = $data["total_agents"];
$cells[0]["color"] = "#000";
$cells[0]["href"] = "index.php?sec=estado&sec2=operation/agentes/estado_agente&refr=60";

$cells[1][0] = __('Uninitialized modules');
$cells[1][1] = $data["server_sanity"] . "%";
$cells[1]["color"] = "#000";
$cells[1]["href"] = "index.php?sec=estado_server&sec2=operation/servers/view_server&refr=60";

print_cells_temp ($cells);

echo "</table>";
echo '</div>'; //Left column

echo '<div style="width: 450px; float:left;" id="rightcolumn">';

// Server information

$serverinfo = get_server_info ();
$total_modules = get_agent_modules_count ();
$cells = array ();

if ($serverinfo) {

	echo '<table class="databox" cellpadding="4" cellspacing="4" style="width:100%;">';
	echo '<thead>
	<tr>
	<th colspan="4" style="background-color:#799E48">'.__('Tactical server information').'</th>
	</tr>';
	echo '<tr>
	<th style="font-weight:none;">'.__('Name').'</th>
	<th style="font-weight:none;">'.__('Status').'</th>
	<th style="font-weight:none;">'.__('Load').'</th>
	<th style="font-weight:none;">'.__('Lag').pandora_help ("serverlag", true).'</th>
	</tr></thead><tbody>';

	foreach ($serverinfo as $server_id => $server_info) {
	$data = array ();
	$data[0] = $server_info["name"];

	if ($server_info["status"] == 0){
		$data[1] = print_image ("images/pixel_red.png", true, array ("width" => 20, "height" => 20));
	} else {
		$data[1] = print_image ("images/pixel_green.png", true, array ("width" => 20, "height" => 20));
	}

	if ($server_info["modules"] > 0 && $total_modules > 0) {
		$percent = $server_info["modules"] / ($total_modules / 100);
	} else {
		$percent = 0;
	}
	$data[2] = print_image ("reporting/fgraph.php?tipo=progress&percent=".$percent."&height=18&width=80", true, array ("title" => $server_info["modules"]." ".__('of')." ".$total_modules));

	$data[3] = $server_info["lag"]." / ".$server_info["module_lag"];

	array_push ($cells, $data);
	}

	foreach ($cells as $key => $row) {
	//Switch class around
	$class = (($key % 2) ? "datos2" : "datos");
	echo '<tr>
		<td class="'.$class.'">'.$row[0].'</td>
		<td class="'.$class.'" style="text-align:center;">'.$row[1].'</td>
		<td class="'.$class.'" style="text-align:center;">'.$row[2].'</td>
		<td class="'.$class.'" style="text-align:right;">'.$row[3].'</td>
	</tr>';
	}
	echo '</tbody></table>';
} else {
	echo '<div class="nf">'.__('There are no servers configured into the database').'</div>';
}
print_events_table ("", 10, 450);

echo '</div>';
?>
