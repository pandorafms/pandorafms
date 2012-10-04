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

global $config;

// Login check
if (isset ($_GET["direct"])) {
	require_once ("../../include/config.php");
	require_once ("../../include/auth/mysql.php");
	require_once("../../include/functions_netflow.php");
	$nick = get_parameter ("nick");
	$pass = get_parameter ("pass");
	if (isset ($nick) && isset ($pass)) {

		$nick = process_user_login ($nick, $pass);
		if ($nick !== false) {
			unset ($_GET["sec2"]);
			$_GET["sec"] = "general/logon_ok";
			db_logon ($nick, $_SERVER['REMOTE_ADDR']);
			$_SESSION['id_usuario'] = $nick;
			$config['id_user'] = $nick;
			//Remove everything that might have to do with people's passwords or logins
			unset ($_GET['pass'], $pass, $_POST['pass'], $_REQUEST['pass'], $login_good);
		}
		else {
			// User not known
			$login_failed = true;
			require_once ('general/login_page.php');
			db_pandora_audit("Logon Failed", "Invalid login: ".$nick, $nick);
			exit;
		}
	}
} else {
	include_once($config['homedir'] . "/include/functions_graph.php");
	include_once($config['homedir'] . "/include/functions_ui.php");
	include_once($config['homedir'] . "/include/functions_netflow.php");
	ui_require_javascript_file ('calendar');
}

check_login ();

if (! check_acl ($config["id_user"], 0, "AR")) {
	db_pandora_audit("ACL Violation",
		"Trying to access event viewer");
	require ("general/noaccess.php");
	return;
}

$id = io_safe_input (get_parameter('id'));

if ($id) {
	$permission = netflow_check_report_group ($id, true);
	if (!$permission) { //no tiene permisos para acceder a un informe
		require ("general/noaccess.php");
		return;
	}
}

$period = get_parameter('period', '86400');
$date = get_parameter_post ('date', date ("Y/m/d", get_system_time ()));
$time = get_parameter_post ('time', date ("H:i:s", get_system_time ()));

$end_date = strtotime ($date . " " . $time);
$start_date = $end_date - $period;

// Generate an XML report
if (isset ($_GET["xml"])) {
	header ('Content-type: application/xml; charset="utf-8"', true);
	netflow_xml_report ($id, $start_date, $end_date);
	return;
}

$buttons['report_list'] = '<a href="index.php?sec=netf&sec2=operation/netflow/nf_reporting">'
	. html_print_image ("images/edit.png", true, array ("title" => __('Report list')))
	. '</a>';

//Header
if (! defined ('METACONSOLE')) {
	ui_print_page_header (__('Netflow'), "images/networkmap/so_cisco_new.png", false, "", false, $buttons);
} else {
	$nav_bar = array(array('link' => 'index.php?sec=main', 'text' => __('Main')),
		array('link' => 'index.php?sec=netf&sec2=' . $config['homedir'] . '/operation/netflow/nf_reporting', 'text' => __('Netflow reports')),
		array('link' => 'index.php?sec=netf&sec2=' . $config['homedir'] . '/operation/netflow/nf_view', 'text' => __('View netflow report')));
	ui_meta_print_page_header($nav_bar);
}

echo '<form method="post" action="' . $config['homeurl'] . 'index.php?sec=netf&sec2=' . $config['homedir'] . '/operation/netflow/nf_view&amp;id='.$id.'">';

	$table->width = '60%';
	$table->border = 0;
	$table->cellspacing = 3;
	$table->cellpadding = 5;
	$table->class = "databox_color";
	$table->style[0] = 'vertical-align: top;';
	
	$table->data = array ();
	
	$table->data[0][0] = '<b>'.__('Date').'</b>';
	
	$table->data[0][1] = html_print_input_text ('date', $date, false, 10, 10, true);
	$table->data[0][1] .= html_print_image ("images/calendar_view_day.png", true, array ("alt" => "calendar", "onclick" => "scwShow(scwID('text-date'),this);"));
	$table->data[0][1] .= html_print_input_text ('time', $time, false, 10, 5, true);
	
	$table->data[1][0] = '<b>'.__('Interval').'</b>';
	$table->data[1][1] = html_print_select (netflow_get_valid_intervals (), 'period', $period, '', '', 0, true, false, false);
	
	$table->data[2][0] = '<b>'.__('Export').'</b>';
	if (! defined ('METACONSOLE')) {
		$table->data[2][1] = '<a title="XML" href="' . $config['homeurl'] . 'ajax.php?page=' . $config['homedir'] . '/operation/netflow/nf_view&id='.$id."&date=$date&time=$time&period=$period&xml=1\">" . html_print_image("images/database_lightning.png", true) . '</a>';
	} else {
		$table->data[2][1] = '<a title="XML" href="' . $config['homeurl'] . '../../ajax.php?page=' . $config['homedir'] . '/operation/netflow/nf_view&id='.$id."&date=$date&time=$time&period=$period&xml=1\">" . html_print_image("images/database_lightning.png", true) . '</a>';
	}

	html_print_table ($table);
	
	echo '<div class="action-buttons" style="width:60%;">';
	html_print_submit_button (__('Update'), 'updbutton', false, 'class="sub upd"');
	echo '</div>';
echo'</form>';

$report = db_get_row_sql('SELECT * FROM tnetflow_report WHERE id_report =' . (int)$id);
if (empty ($report)){
	echo fs_error_image();
	return;
}

$report_name = $report['id_name'];
$connection_name = $report['server_name'];
$report_contents = db_get_all_rows_sql("SELECT * FROM tnetflow_report_content WHERE id_report='$id' ORDER BY `order`");
if (empty ($report_contents)) {
	echo fs_error_image();
	return;
}

// Process report items
foreach ($report_contents as $content_report) {
	
	// Get report item
	$report_id = $content_report['id_report'];
	$content_id = $content_report['id_rc'];
	$max_aggregates= $content_report['max'];
	$type = $content_report['show_graph'];
	
	// Get item filters
	$filter = db_get_row_sql("SELECT * FROM tnetflow_filter WHERE id_sg = '" . io_safe_input ($content_report['id_filter']) . "'", false, true);
	
	if ($filter['aggregate'] != 'none') {
		echo '<h4>' . $filter['id_name'] . ' (' . __($filter['aggregate']) . '/' . __($filter['output']) . ')</h4>';
	}
	else {
		echo '<h4>' . $filter['id_name'] . ' (' . __($filter['output']) . ')</h4>';
	}
	
	// Build a unique id for the cache
	$unique_id = $report_id . '_' . $content_id . '_' . ($end_date - $start_date);
	
	// Draw
	netflow_draw_item ($start_date, $end_date, $type, $filter, $max_aggregates, $unique_id, $connection_name);
}
?>