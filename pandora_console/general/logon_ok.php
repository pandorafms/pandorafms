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

if(tags_has_user_acl_tags()) {
	ui_print_tags_warning();
}
// ---------------------------------------------------------------------------
// Site news !
// ---------------------------------------------------------------------------

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
echo '<div style="width:30%; float:left; padding-left: 30px;" id="rightcolumn">';
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
$table->data[2][0] = '<b>'.__('Module sanity').'</b>';
$table->data[3][0] =
	progress_bar($data["module_sanity"], 280, 20, $data["module_sanity"].'% '.__('of total modules inited'), 0);
$table->data[4][0] = '<b>'.__('Alert level').'</b>';
$table->data[5][0] =
	progress_bar($data["alert_level"], 280, 20, $data["alert_level"].'% '.__('of defined alerts not fired'), 0);

html_print_table ($table);
unset ($table);

///////////////
// Overview
///////////////

// Link URLS
$urls = array();
$urls['total_agents'] = "index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=60";
$urls['monitor_checks'] = "index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=60&amp;status=-1";
$urls['monitor_critical'] = "index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=60&amp;status=2";
$urls['monitor_warning'] = "index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=60&amp;status=1";
$urls['monitor_ok'] = "index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=60&amp;status=0";
$urls['monitor_unknown'] = "index.php?sec=estado&amp;sec2=operation/agentes/status_monitor&amp;refr=60&amp;status=3";
$urls['monitor_alerts'] = "index.php?sec=estado&amp;sec2=operation/agentes/alerts_status&amp;refr=60";
$urls['monitor_alerts_fired'] = "index.php?sec=estado&amp;sec2=operation/agentes/alerts_status&amp;refr=60&filter=fired";
if (check_acl ($config['id_user'], 0, "UM")) {
	$urls['defined_users'] = "index.php?sec=gusuarios&amp;sec2=godmode/users/user_list";
}
else{
	$urls['defined_users'] = 'javascript:';
}

// Transparent table as template to build subtables
$table_transparent->class = "none";
$table_transparent->cellpadding = 0;
$table_transparent->cellspacing = 0;
$table_transparent->head = array ();
$table_transparent->data = array ();
$table_transparent->style[0] = $table_transparent->style[1] = $table_transparent->style[2] = $table_transparent->style[3] = 'text-align:center; width: 25%;';
$table_transparent->width = "100%";

// Agents and modules table
$table_am = clone $table_transparent;

$tdata = array();
$tdata[0] = html_print_image('images/bricks.png', true, array('title' => __('Total agents'), 'width' => '20px'));
$tdata[1] = $data["total_agents"] == 0 ? '-' : $data["total_agents"];
$tdata[1] = '<a style="color: black;" class="big_data" href="' . $urls['total_agents'] . '">' . $tdata[1] . '</a>';

$tdata[2] = html_print_image('images/brick.png', true, array('title' => __('Monitor checks'), 'width' => '20px'));
$tdata[3] = $data["monitor_checks"] == 0 ? '-' : $data["monitor_checks"];
$tdata[3] = '<a style="color: black;" class="big_data" href="' . $urls['monitor_checks'] . '">' . $tdata[3] . '</a>';
$table_am->rowclass[] = '';
$table_am->data[] = $tdata;

// Modules by status table
$table_mbs = clone $table_transparent;

$tdata = array();
$tdata[0] = html_print_image('images/status_sets/default/agent_critical_ball.png', true, array('title' => __('Monitor critical'), 'width' => '20px'));
$tdata[1] = $data["monitor_critical"] == 0 ? '-' : $data["monitor_critical"];
$tdata[1] = '<a style="color: ' . COL_CRITICAL . ';" class="big_data" href="' . $urls['monitor_critical'] . '">' . $tdata[1] . '</a>';

$tdata[2] = html_print_image('images/status_sets/default/agent_warning_ball.png', true, array('title' => __('Monitor warning'), 'width' => '20px'));
$tdata[3] = $data["monitor_warning"] == 0 ? '-' : $data["monitor_warning"];
$tdata[3] = '<a style="color: ' . COL_WARNING_DARK . ';" class="big_data" href="' . $urls['monitor_warning'] . '">' . $tdata[3] . '</a>';
$table_mbs->rowclass[] = '';
$table_mbs->data[] = $tdata;

$tdata = array();
$tdata[0] = html_print_image('images/status_sets/default/agent_ok_ball.png', true, array('title' => __('Monitor normal'), 'width' => '20px'));
$tdata[1] = $data["monitor_ok"] == 0 ? '-' : $data["monitor_ok"];
$tdata[1] = '<a style="color: ' . COL_NORMAL . ';" class="big_data" href="' . $urls["monitor_ok"] . '">' . $tdata[1] . '</a>';

$tdata[2] = html_print_image('images/status_sets/default/agent_no_monitors_ball.png', true, array('title' => __('Monitor unknown'), 'width' => '20px'));
$tdata[3] = $data["monitor_unknown"] == 0 ? '-' : $data["monitor_unknown"];
$tdata[3] = '<a style="color: ' . COL_UNKNOWN . ';" class="big_data" href="' . $urls["monitor_unknown"] . '">' . $tdata[3] . '</a>';
$table_mbs->rowclass[] = '';
$table_mbs->data[] = $tdata;

$tdata = array();
$table_mbs->colspan[count($table_mbs->data)][0] = 4;
$tdata[0] = '<div style="margin: auto; width: 250px;">' . graph_agent_status (false, 250, 150, true) . '</div>';
$table_mbs->rowclass[] = '';
$table_mbs->data[] = $tdata;

// Alerts table
$table_al = clone $table_transparent;

$tdata = array();
$tdata[0] = html_print_image('images/bell.png', true, array('title' => __('Defined alerts'), 'width' => '20px'));
$tdata[1] = $data["monitor_alerts"] == 0 ? '-' : $data["monitor_alerts"];
$tdata[1] = '<a style="color: black;" class="big_data" href="' . $urls["monitor_alerts"] . '">' . $tdata[1] . '</a>';

$tdata[2] = html_print_image('images/bell_error.png', true, array('title' => __('Fired alerts'), 'width' => '20px'));
$tdata[3] = $data["monitor_alerts_fired"] == 0 ? '-' : $data["monitor_alerts_fired"];
$tdata[3] = '<a style="color: ' . COL_ALERTFIRED . ';" class="big_data" href="' . $urls["monitor_alerts_fired"] . '">' . $tdata[3] . '</a>';
$table_al->rowclass[] = '';
$table_al->data[] = $tdata;

// Users table
$table_us = clone $table_transparent;

$tdata = array();
$tdata[0] = html_print_image('images/group.png', true, array('title' => __('Defined users'), 'width' => '20px'));
$tdata[1] = count (get_users ());
$tdata[1] = '<a style="color: black;" class="big_data" href="' . $urls["defined_users"] . '">' . $tdata[1] . '</a>';

$tdata[2] = $tdata[3] = '&nbsp;';
$table_us->rowclass[] = '';
$table_us->data[] = $tdata;

// Overview table build
$table->class = "databox";
$table->cellpadding = 4;
$table->cellspacing = 4;
$table->head = array ();
$table->data = array ();
$table->style[0] = 'text-align:center;';
$table->width = "100%";
$table->head[0] = __('Pandora FMS Overview');
$table->head_colspan[0] = 4; 

// Total agents and modules
$tdata = array();
$tdata[0] = '<fieldset class="databox" style="width:97%;">
				<legend style="text-align:left; color: #666;">' . 
					__('Total agents and monitors') . 
				'</legend>' . 
				html_print_table($table_am, true) . '</fieldset>';
$table->rowclass[] = '';
$table->data[] = $tdata;

// Modules by status
$tdata = array();
$tdata[0] = '<fieldset class="databox" style="width:97%;">
				<legend style="text-align:left; color: #666;">' . 
					__('Monitors by status') . 
				'</legend>' . 
				html_print_table($table_mbs, true) . '</fieldset>';
$table->rowclass[] = '';
$table->data[] = $tdata;

// Alerts
$tdata = array();
$tdata[0] = '<fieldset class="databox" style="width:97%;">
				<legend style="text-align:left; color: #666;">' . 
					__('Defined and fired alerts') . 
				'</legend>' . 
				html_print_table($table_al, true) . '</fieldset>';
$table->rowclass[] = '';
$table->data[] = $tdata;

// Users
$tdata = array();
$tdata[0] = '<fieldset class="databox" style="width:97%;">
				<legend style="text-align:left; color: #666;">' . 
					__('Users') . 
				'</legend>' . 
				html_print_table($table_us, true) . '</fieldset>';
$table->rowclass[] = '';
$table->data[] = $tdata;

html_print_table($table);
unset($table);

echo "</div>";
echo '<div id="activity" style="width:87%;">';
echo "<br /><br />";

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
			WHERE (`utimestamp` > UNIX_TIMESTAMP(NOW()) - " . SECONDS_1WEEK . ") 
				AND `id_usuario` = '%s' ORDER BY `utimestamp` DESC LIMIT 10", $config["id_user"]);
		break;
	case "postgresql":
		$sql = sprintf ("SELECT \"id_usuario\", accion, fecha, \"ip_origen\", descripcion
			FROM tsesion
			WHERE (\"utimestamp\" > ceil(date_part('epoch', CURRENT_TIMESTAMP)) - " . SECONDS_1WEEK . ") 
				AND \"id_usuario\" = '%s' ORDER BY \"utimestamp\" DESC LIMIT 10", $config["id_user"]);
		break;
	case "oracle":
		$sql = sprintf ("SELECT id_usuario, accion, fecha, ip_origen, descripcion
			FROM tsesion
			WHERE ((utimestamp > ceil((sysdate - to_date('19700101000000','YYYYMMDDHH24MISS')) * (" . SECONDS_1DAY . ")) - " . SECONDS_1WEEK . ") 
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
?>
