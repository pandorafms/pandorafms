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


require_once ("include/config.php");

// This solves problems in enterprise load
global $config;

check_login ();

/* Call all extensions login function */
//extensions_call_login_function ();

require_once ("include/functions_reporting.php");
require_once ($config["homedir"] . '/include/functions_graph.php');

ui_print_page_header (__('Welcome to Pandora FMS Web Console'));

// ---------------------------------------------------------------------------
// Site news !
// ---------------------------------------------------------------------------

echo "<table><tr><td>";
echo '<div style="width:50%; float:left; padding-right: 30px;" id="leftcolumn">';

switch ($config["dbtype"]) {
	case "mysql":
	case "postgresql":
		$sql = "SELECT subject,timestamp,text,author FROM tnews ORDER by timestamp DESC LIMIT 3";
		break;
	case "oracle":
		$sql = "SELECT subject,timestamp,text,author FROM tnews where rownum <= 3 ORDER by timestamp DESC";
		break;
}

$news = db_get_all_rows_sql ($sql);
if ($news !== false) {
	echo '<table cellpadding="4" cellspacing="4" class="databox">';
	if ($config["prominent_time"] == "timestamp") {
		$comparation_suffix = "";
	}
	else {
		$comparation_suffix = __('ago');
	}
	foreach ($news as $article) {
		echo '<tr><th><b>'.$article["subject"].'</b></th></tr>';
		echo '<tr><td>'.__('by').' <b>'.$article["author"].'</b> <i>' . ui_print_timestamp ($article["timestamp"], true).'</i> ' . $comparation_suffix . '</td></tr>';
		echo '<tr><td class="datos">';
		echo nl2br ($article["text"]);
		echo '</td></tr>';
	}
	echo '</table>';
}
else {
	echo '<div>'.__('No news articles at this moment').'</div>';
}
echo '</div>';


// ---------------------------------------------------------------------------
// Site stats (global!)
// ---------------------------------------------------------------------------
echo '<div style="width:40%; float:left; padding-left: 30px;" id="rightcolumn">';
$data = reporting_get_group_stats ();

$table->class = "databox";
$table->cellpadding = 4;
$table->cellspacing = 4;
$table->head = array ();
$table->data = array ();
$table->width = "100%";

$table->data[0][0] ='<b>'.__('Monitor health').'</b>';
$table->data[1][0] = 
	progress_bar($data["monitor_health"], 280, 20, $data["monitor_health"].'% '.__('of monitors up'), 0);
$table->rowstyle[1] = 'text-align: center;';

$table->data[2][0] = '<b>'.__('Module sanity').'</b>';
$table->data[3][0] =
	progress_bar($data["module_sanity"], 280, 20, $data["module_sanity"].'% '.__('of total modules inited'), 0);
$table->rowstyle[3] = 'text-align: center;';

$table->data[4][0] = '<b>'.__('Alert level').'</b>';
$table->data[5][0] =
	progress_bar($data["alert_level"], 280, 20, $data["alert_level"].'% '.__('of defined alerts not fired'), 0);
$table->rowstyle[5] = 'text-align: center;';

html_print_table ($table);
unset ($table);

echo '<table class="databox" cellpadding="4" cellspacing="4" width="100%">';
echo '<thead><tr><th colspan="2">'.__('Pandora FMS Overview').'</th></tr></thead><tbody>';

$cells = array ();
$cells[0][0] = __('Total agents');
$cells[0][1] = $data["total_agents"];
$cells[0]["color"] = "#000";
$cells[0]["href"] = "index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=60";

$cells[1][0] = __('Monitor checks');
$cells[1][1] = $data["monitor_checks"];
$cells[1]["color"] = "#000";
$cells[1]["href"] = "index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=60&amp;status=-1";

$cells[2][0] = __('Monitors critical');
$cells[2][1] = $data["monitor_critical"];
$cells[2]["color"] = "#c00";
$cells[2]["href"] = "index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=60&amp;status=2";

$cells[3][0] = __('Monitors warning');
$cells[3][1] = $data["monitor_warning"];
$cells[3]["color"] = "#ffb900";
$cells[3]["href"] = "index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=60&amp;status=1";

$cells[4][0] = __('Monitors normal');
$cells[4][1] = $data["monitor_ok"];
$cells[4]["color"] = "#8ae234";
$cells[4]["href"] = "index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=60&amp;status=0";

$cells[5][0] = __('Monitors unknown');
$cells[5][1] = $data["monitor_unknown"];
$cells[5]["color"] = "#aaa";
$cells[5]["href"] = "index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=60&amp;status=3";

$cells[6][0] = __('Alerts defined');
$cells[6][1] = $data["monitor_alerts"];
$cells[6]["color"] = "#000";
$cells[6]["href"] = "index.php?sec=estado&amp;sec2=operation/agentes/alerts_status&amp;refr=60";

$cells[7][0] = __('Users defined');
$cells[7][1] = count (get_users ());
$cells[7]["color"] = "#000";
if (check_acl ($config['id_user'], 0, "UM")) {
	$user_link = 'index.php?sec=gusuarios&amp;sec2=godmode/users/user_list';
}
else{
	$user_link = '#';
}
$cells[7]["href"] = $user_link;

foreach ($cells as $key => $row) {
	//Switch class around
	$class = (($key % 2) ? "datos2" : "datos");
	echo '<tr><td class="'.$class.'"><b>'.$row[0].'</b></td>';
	if ($row[1] === 0) {
		$row[1] = "-";
	}
	echo '<td class="'.$class.'" style="text-align:right;"><a class="big_data" href="'.$row["href"].'" style="color: '.$row["color"].';">'.$row[1].'</a></td></tr>';
}

echo '</tbody></table>';
echo "</div>";

echo "</td></tr><tr><td>";

echo '<div id="activity" style="width:100%;">';
// Show last activity from this user
echo "<h4>" . __('This is your last activity in Pandora FMS console') . "</h4>";

$table->width = '98%'; //Don't specify px
$table->data = array ();
$table->size = array ();
$table->size[2] = '130px';
$table->size[4] = '200px';
$table->head = array ();
$table->head[0] = __('User');
$table->head[1] = __('Action');
$table->head[2] = __('Date');
$table->head[3] = __('Source IP');
$table->head[4] = __('Comments');

switch ($config["dbtype"]) {
	case "mysql":
		$sql = sprintf ("SELECT id_usuario,accion,fecha,ip_origen,descripcion
			FROM tsesion
			WHERE (`utimestamp` > UNIX_TIMESTAMP(NOW()) - 604800) 
				AND `id_usuario` = '%s' ORDER BY `utimestamp` DESC LIMIT 10", $config["id_user"]);
		break;
	case "postgresql":
		$sql = sprintf ("SELECT \"id_usuario\", accion, fecha, \"ip_origen\", descripcion
			FROM tsesion
			WHERE (\"utimestamp\" > ceil(date_part('epoch', CURRENT_TIMESTAMP)) - 604800) 
				AND \"id_usuario\" = '%s' ORDER BY \"utimestamp\" DESC LIMIT 10", $config["id_user"]);
		break;
	case "oracle":
		$sql = sprintf ("SELECT id_usuario, accion, fecha, ip_origen, descripcion
			FROM tsesion
			WHERE ((utimestamp > ceil((sysdate - to_date('19700101000000','YYYYMMDDHH24MISS')) * (86400)) - 604800) 
				AND id_usuario = '%s') AND rownum <= 10 ORDER BY utimestamp DESC", $config["id_user"]);
		break;
}

$sessions = db_get_all_rows_sql ($sql);

if ($sessions === false)
	$sessions = array (); 

foreach ($sessions as $session) {
	$data = array ();
	
	switch ($config["dbtype"]) {
		case "mysql":
		case "oracle":
			$session_id_usuario = $session['id_usuario'];
			$session_ip_origen = $session['ip_origen'];
			break;
		case "postgresql":
			$session_id_usuario = $session['id_usuario'];
			$session_ip_origen = $session['ip_origen'];
			break;
	}
	
	
	$data[0] = '<strong>' . $session_id_usuario . '</strong>';
	$data[1] = $session['accion'];
	$data[2] = $session['fecha'];
	$data[3] = $session_ip_origen;
	$data[4] = io_safe_output ($session['descripcion']);
	
	array_push ($table->data, $data);
}
echo "<div style='width:100%; overflow-x:auto;'>";
html_print_table ($table);
echo "</div>";
echo "</div>"; // activity

echo "</td></tr></table>";
?>
