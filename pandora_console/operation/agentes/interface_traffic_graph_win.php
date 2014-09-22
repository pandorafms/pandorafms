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


if (! isset($_SESSION['id_usuario'])) {
	session_start();
	session_write_close();
}

// Global & session management
require_once ('../../include/config.php');
require_once ('../../include/auth/mysql.php');
require_once ($config['homedir'] . '/include/functions.php');
require_once ($config['homedir'] . '/include/functions_db.php');
require_once ($config['homedir'] . '/include/functions_reporting.php');
require_once ($config['homedir'] . '/include/functions_graph.php');
require_once ($config['homedir'] . '/include/functions_custom_graphs.php');
require_once ($config['homedir'] . '/include/functions_modules.php');

// Hash login process
if (! isset ($config['id_user']) && get_parameter("loginhash", 0)) {
	$loginhash_data = get_parameter("loginhash_data", "");
	$loginhash_user = get_parameter("loginhash_user", "");
	
	if ($config["loginhash_pwd"] != "" && $loginhash_data == md5($loginhash_user.$config["loginhash_pwd"])) {
		db_logon ($loginhash_user, $_SERVER['REMOTE_ADDR']);
		$_SESSION['id_usuario'] = $loginhash_user;
		$config["id_user"] = $loginhash_user;
		
		$hash_connection_data = true;
	}
	
}

check_login();

$user_language = get_user_language($config['id_user']);
if (file_exists ('../../include/languages/'.$user_language.'.mo')) {
	$l10n = new gettext_reader (new CachedFileReader ('../../include/languages/'.$user_language.'.mo'));
	$l10n->load_tables();
}

echo '<link rel="stylesheet" href="../../include/styles/pandora.css" type="text/css"/>';

$params_json = base64_decode((string) get_parameter('params'));
$params = json_decode($params_json, true);

$interface_name = (string) $params['interface_name'];
$agent_id = (int) $params['agent_id'];
$interface_traffic_modules = array(
		__('In') => (int) $params['traffic_module_in'],
		__('Out') => (int) $params['traffic_module_out']
	);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
<?php
		// Parsing the refresh before sending any header
		$refresh = (int) get_parameter('refresh', SECONDS_5MINUTES);
		if ($refresh > 0) {
			$query = ui_get_url_refresh(false);
			
			echo '<meta http-equiv="refresh" content="'.$refresh.'; URL='.$query.'" />';
		}
?>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>Pandora FMS Graph (<?php echo agents_get_name($agent_id) . ' - ' . $interface_name; ?>)</title>
		<link rel="stylesheet" href="../../include/styles/pandora_minimal.css" type="text/css" />
		<script type='text/javaScript' src='../../include/javascript/calendar.js'></script>
		<script type='text/javascript' src='../../include/javascript/pandora.js'></script>
		<script type='text/javascript' src='../../include/javascript/jquery-1.9.0.js'></script>
		<script type='text/javascript' src='../../include/javascript/jquery.pandora.js'></script>
		<script type='text/javascript'>
			<!--
			window.onload = function() {
				// Hack to repeat the init process to period select
				var periodSelectId = $('[name="period"]').attr('class');
				
				period_select_init(periodSelectId);
			};
			
			function show_others() {
				if (!$("#checkbox-avg_only").attr('checked')) {
					$("#hidden-show_other").val(1);
				}
				else {
					$("#hidden-show_other").val(0);
				}
			}
			//-->
		</script>
	</head>
	<body bgcolor="#ffffff" style='background:#ffffff;'>
<?php
		
		// Get input parameters
		$period = (int) get_parameter('period', SECONDS_1HOUR);
		$width = (int) get_parameter("width", 555);
		$height = (int) get_parameter("height", 245);
		$start_date = (string) get_parameter("start_date", date("Y-m-d"));
		$zoom = (int) get_parameter ("zoom", 1);
		$baseline = get_parameter ("baseline", 0);
		
		if ($zoom > 1) {
			$height = $height * ($zoom / 2.1);
			$width = $width * ($zoom / 1.4);
			
			echo "<script type='text/javascript'>window.resizeTo($width + 120, $height + 320);</script>";
		}
		
		$current = date("Y-m-d");
		
		if ($start_date != $current)
			$date = strtotime($start_date);
		else
			$date = $utime;
		
		$urlImage = ui_get_full_url(false);
		
		if ($config['flash_charts'] == 1)
			echo '<div style="margin-left: 70px; padding-top: 10px;">';
		else
			echo '<div style="margin-left: 50px; padding-top: 10px;">';
		
		custom_graphs_print(0,
			$height,
			$width,
			$period,
			null,
			false,
			$date,
			false,
			'white',
			array_values($interface_traffic_modules),
			$config['homeurl'],
			array_keys($interface_traffic_modules),
			false);
		
		echo '</div>';
		
		///////////////////////////
		// SIDE MENU
		///////////////////////////
		$side_layer_params = array();
		// TOP TEXT
		$side_layer_params['top_text'] = "<div style='color: white; width: 100%; text-align: center; font-weight: bold; vertical-align: top;'>" . html_print_image('images/config_mc.png', true, array('width' => '16px')) . ' ' . __('Pandora FMS Graph configuration menu') . "</div>";
		$side_layer_params['body_text'] = "<div class='menu_sidebar_outer'>";
		$side_layer_params['body_text'] .=__('Please, make your changes and apply with the <i>Reload</i> button');
		
		// MENU
		$side_layer_params['body_text'] .= '<form method="get" action="interface_traffic_graph_win.php">';
		$side_layer_params['body_text'] .= html_print_input_hidden("params", base64_encode($params_json), true);
		
		if (isset($hash_connection_data)) {
			$side_layer_params['body_text'] .= html_print_input_hidden("loginhash", "auto", true);
			$side_layer_params['body_text'] .= html_print_input_hidden("loginhash_data", $loginhash_data, true);
			$side_layer_params['body_text'] .= html_print_input_hidden("loginhash_user", $loginhash_user, true);
		}
		
		// FORM TABLE
		
		$table = html_get_predefined_table('transparent', 2);
		$table->width = '98%';
		$table->id = 'stat_win_form_div';
		$table->style[0] = 'text-align:left; padding: 7px;';
		$table->style[1] = 'text-align:left;';
		$table->styleTable = 'border-spacing: 4px;';
		$table->class = 'alternate';

		$data = array();
		$data[0] = __('Refresh time');
		$data[1] = html_print_extended_select_for_time("refresh", $refresh, '', '', 0, 7, true);
		$table->data[] = $data;
		$table->rowclass[] = '';
		
		$data = array();
		$data[0] = __('Begin date');
		$data[1] = html_print_input_text ("start_date", substr ($start_date, 0, 10),'', 15, 255, true);
		$data[1] .= html_print_image ("images/calendar_view_day.png", true, array ("onclick" => "scwShow(scwID('text-start_date'),this);", "style" => 'vertical-align: bottom;'));
		$table->data[] = $data;
		$table->rowclass[] = '';
		
		$data = array();
		$data[0] = __('Time range');
		$data[1] = html_print_extended_select_for_time('period', $period, '', '', 0, 7, true);
		$table->data[] = $data;
		$table->rowclass[] = '';
		
		$data = array();
		$data[0] = __('Zoom factor');
		$options = array();
		$options[$zoom] = 'x'.$zoom;
		$options[1] = 'x1';
		$options[2] = 'x2';
		$options[3] = 'x3';
		$options[4] = 'x4';
		$data[1] = html_print_select($options, "zoom", $zoom, '', '', 0, true);
		$table->data[] = $data;
		$table->rowclass[] = '';
		
		$form_table = html_print_table($table, true);
		
		unset($table);
		
		$table->id = 'stat_win_form';
		$table->width = '100%';
		$table->cellspacing = 2;
		$table->cellpadding = 2;
		$table->class = 'databox';
		
		$data = array();
		$data[0] = html_print_div(array('content' => $form_table, 'style' => 'overflow: auto; height: 220px'), true);
		$table->data[] = $data;
		$table->rowclass[] = '';
		
		$data = array();
		$data[0] = '<div style="width:100%; text-align:right;">' . html_print_submit_button (__('Reload'), "submit", false, 'class="sub upd"', true) . "</div>";
		$table->data[] = $data;
		$table->rowclass[] = '';
		
		$side_layer_params['body_text'] .= html_print_table($table, true);
		$side_layer_params['body_text'] .= '</form>';
		$side_layer_params['body_text'] .= '</div>'; // outer
		
		// ICONS
		$side_layer_params['icon_closed'] = '/images/graphmenu_arrow_hide.png';
		$side_layer_params['icon_open'] = '/images/graphmenu_arrow.png';
		
		// SIZE
		$side_layer_params['width'] = 500;
		
		// POSITION
		$side_layer_params['position'] = 'left';
		
		html_print_side_layer($side_layer_params);
		
		// Hidden div to forced title
		html_print_div(array('id' => 'forced_title_layer', 'class' => 'forced_title_layer', 'hidden' => true));
?>
		
	</body>
</html>
<script>
	
<?php
	//Resize window when show the overview graph.
	if ($config['flash_charts']) {
?>
		var show_overview = false;
		var height_window;
		var width_window;
		$(document).ready(function() {
			height_window = $(window).height();
			width_window = $(window).width();
		});
		
		$("*").filter(function() {
			if (typeof(this.id) == "string")
				return this.id.match(/menu_overview_graph.*/);
			else
				return false;
			}).click(function() {
				show_overview = !show_overview;
			});
<?php
	}
?>
	
	forced_title_callback();
</script>
