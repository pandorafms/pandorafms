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
	//session_write_close();
}

// Global & session management
require_once ('../../include/config.php');
require_once ($config['homedir'] . '/include/auth/mysql.php');
require_once ($config['homedir'] . '/include/functions.php');
require_once ($config['homedir'] . '/include/functions_db.php');
require_once ($config['homedir'] . '/include/functions_reporting.php');
require_once ($config['homedir'] . '/include/functions_graph.php');
require_once ($config['homedir'] . '/include/functions_modules.php');
require_once ($config['homedir'] . '/include/functions_agents.php');
require_once ($config['homedir'] . '/include/functions_tags.php');

check_login ();

// Metaconsole connection to the node
$server_id = (int) get_parameter("server");
if (is_metaconsole() && !empty($server_id)) {
	$server = metaconsole_get_connection_by_id($server_id);
	
	// Error connecting
	if (metaconsole_connect($server) !== NOERR) {
		echo "<html>";
			echo "<body>";
				ui_print_error_message(__('There was a problem connecting with the node'));
			echo "</body>";
		echo "</html>";
		exit;
	}
}

$user_language = get_user_language ($config['id_user']);
if (file_exists ('../../include/languages/'.$user_language.'.mo')) {
	$l10n = new gettext_reader (new CachedFileReader ('../../include/languages/'.$user_language.'.mo'));
	$l10n->load_tables();
}

echo '<link rel="stylesheet" href="../../include/styles/pandora.css" type="text/css"/>';

$label = get_parameter('label');
$label = base64_decode($label);
$id = get_parameter('id');
//$label = rawurldecode(urldecode(base64_decode(get_parameter('label', ''))));
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<?php
		// Parsing the refresh before sending any header
		$refresh = (int) get_parameter ("refresh", -1);
		if ($refresh > 0) {
			$query = ui_get_url_refresh (false);
			
			echo '<meta http-equiv="refresh" content="'.$refresh.'; URL='.$query.'" />';
		}
		?>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>Pandora FMS Graph (<?php echo modules_get_agentmodule_agent_name ($id) . ' - ' . $label; ?>)</title>
		<link rel="stylesheet" href="../../include/styles/pandora_minimal.css" type="text/css" />
		<link rel="stylesheet" href="../../include/styles/jquery-ui-1.10.0.custom.css" type="text/css" />
		<script type='text/javascript' src='../../include/javascript/pandora.js'></script>
		<script type='text/javascript' src='../../include/javascript/jquery-1.9.0.js'></script>
		<script type='text/javascript' src='../../include/javascript/jquery.pandora.js'></script>
		<script type='text/javascript' src='../../include/javascript/jquery.jquery-ui-1.10.0.custom.js'></script>
		<script type='text/javascript'>
			<!--
			window.onload = function() {
				// Hack to repeat the init process to period select
				var periodSelectId = $('[name="period"]').attr('class');
				
				period_select_init(periodSelectId);
			};
			
			function show_others() {
				if ($('#checkbox-avg_only').is(":checked") == true) {
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
		
		// Module id
		$id = (int) get_parameter ("id", 0);
		// Agent id
		$agent_id = (int) modules_get_agentmodule_agent($id);
		if (empty($id) || empty($agent_id)) {
			ui_print_error_message(__('There was a problem locating the source of the graph'));
			exit;
		}
		
		// ACL
		$permission = false;
		$agent_group = (int) agents_get_agent_group($agent_id);
		$strict_user = (bool) db_get_value("strict_acl", "tusuario",
			"id_user", $config['id_user']);
		
		if (!empty($agent_group)) {
			if ($strict_user) {
				$permission = tags_check_acl_by_module($id, $config['id_user'], 'RR') === true;
			}
			else {
				$permission = check_acl($config['id_user'], $agent_group, "RR");
			}
		}
		
		if (!$permission) {
			require ($config['homedir'] . "/general/noaccess.php");
			exit;
		}
		
		$draw_alerts = get_parameter("draw_alerts", 0);

		if(isset($config['only_average'])){
			$avg_only = 1;
		} 
		else {
			$avg_only = 0;
		}

		$show_other = get_parameter('show_other');
		if (isset($show_other)) {
			$avg_only = $show_other;
		}

		$period = get_parameter ("period", SECONDS_1DAY);
		$id = get_parameter ("id", 0);
		$width = get_parameter ("width", STATWIN_DEFAULT_CHART_WIDTH);
		$height = get_parameter ("height", STATWIN_DEFAULT_CHART_HEIGHT);
		$label = get_parameter ("label", "");
		$start_date = get_parameter ("start_date", date("Y/m/d"));
		$start_time = get_parameter ("start_time", date("H:i:s"));
		$draw_events = get_parameter ("draw_events", 0);
		$graph_type = get_parameter ("type", "sparse");
		$zoom = get_parameter ("zoom", 1);
		$baseline = get_parameter ("baseline", 0);
		$show_events_graph = get_parameter ("show_events_graph", 0);
		$show_percentil_95 = get_parameter ("show_percentil_95", 0);
		$time_compare_separated = get_parameter ("time_compare_separated", 0);
		$time_compare_overlapped = get_parameter ("time_compare_overlapped", 0);
		$unknown_graph = get_parameter_checkbox ("unknown_graph", 1);
		
		// To avoid the horizontal overflow
		$width -= 20;
		
		$time_compare = false;
		
		if ($time_compare_separated) {
			$time_compare = 'separated';
		}
		else if ($time_compare_overlapped) {
			$time_compare = 'overlapped';
		}
		
		if ($zoom > 1) {
			$height = $height * ($zoom / 2.1);
			$width = $width * ($zoom / 1.4);
			
			echo "<script type='text/javascript'>window.resizeTo($width + 80, $height + 120);</script>";
		}
		
		// Build date
		$date = strtotime("$start_date $start_time");
		$now = time();
		
		if ($date > $now)
			$date = $now;
		
		$urlImage = ui_get_full_url(false, false, false, false);
		
		$unit = db_get_value('unit', 'tagente_modulo', 'id_agente_modulo', $id);
		
		// log4x doesnt support flash yet
		//
		if ($config['flash_charts'] == 1)
			echo '<div style="margin-left: 70px; padding-top: 10px;">';
		else
			echo '<div style="margin-left: 50px; padding-top: 10px;">';
		
		switch ($graph_type) {
			case 'boolean':
				echo grafico_modulo_boolean ($id, $period, $draw_events,
					$width, $height, $label, $unit, $draw_alerts,
					$avg_only, false, $date, false, $urlImage,
					'adapter_' . $graph_type, $time_compare,
					$unknown_graph);
				echo '<br>';
				if ($show_events_graph)
					echo graphic_module_events($id, $width, $height,
						$period, $config['homeurl'], $zoom,
						'adapted_' . $graph_type, $date, true);
				break;
			case 'sparse':
				echo grafico_modulo_sparse ($id, $period, $draw_events,
					$width, $height, $label, $unit, $draw_alerts,
					$avg_only, false, $date, $unit, $baseline, 0, true,
					false, $urlImage, 1, false,
					'adapter_' . $graph_type, $time_compare,
					$unknown_graph, true, 'white',
					(($show_percentil_95)? 95 : null));
				echo '<br>';
				if ($show_events_graph)
					echo graphic_module_events($id, $width, $height,
						$period, $config['homeurl'], $zoom,
						'adapted_' . $graph_type, $date, true);
				break;
			case 'string':
				echo grafico_modulo_string ($id, $period, $draw_events,
					$width, $height, $label, null, $draw_alerts, 1,
					false, $date, false, $urlImage,
					'adapter_' . $graph_type);
				echo '<br>';
				if ($show_events_graph)
					echo graphic_module_events($id, $width, $height,
						$period, $config['homeurl'], $zoom,
						'adapted_' . $graph_type, $date, true);
				break;
			case 'log4x':
				echo grafico_modulo_log4x ($id, $period, $draw_events,
					$width, $height, $label, $unit, $draw_alerts, 1,
					$pure, $date);
				echo '<br>';
				if ($show_events_graph)
					echo graphic_module_events($id, $width, $height,
						$period, $config['homeurl'], $zoom, '', $date, true);
				break;
			default:
				echo fs_error_image ('../images');
				break;
		}
		echo '</div>';
		
		////////////////////////////////////////////////////////////////
		// SIDE MENU
		////////////////////////////////////////////////////////////////
		$params = array();
		// TOP TEXT
		//Use the no_meta parameter because this image is only in the base console
		$params['top_text'] = "<div style='color: white; width: 100%; text-align: center; font-weight: bold; vertical-align: top;'>" . html_print_image('images/wrench_blanco.png', true, array('width' => '16px'), false, false, true) . ' ' . __('Pandora FMS Graph configuration menu') . "</div>";
		$params['body_text'] = "<div class='menu_sidebar_outer'>";
		$params['body_text'] .=__('Please, make your changes and apply with the <i>Reload</i> button');
		
		// MENU
		$params['body_text'] .= '<form method="get" action="stat_win.php">';
		$params['body_text'] .= html_print_input_hidden ("id", $id, true);
		$params['body_text'] .= html_print_input_hidden ("label", $label, true);
		
		if (!empty($server_id))
			$params['body_text'] .= html_print_input_hidden ("server", $server_id, true);
		
		if (isset($_GET["type"])) {
			$type = get_parameter_get ("type");
			$params['body_text'] .= html_print_input_hidden ("type", $type, true);
		}
		
		// FORM TABLE
		
		$table = html_get_predefined_table('transparent', 2);
		$table->width = '98%';
		$table->id = 'stat_win_form_div';
		$table->style[0] = 'text-align:left; padding: 7px;';
		$table->style[1] = 'text-align:left;';
		//$table->size[0] = '50%';
		$table->styleTable = 'border-spacing: 4px;';
		$table->class = 'alternate';
		
		$data = array();
		$data[0] = __('Refresh time');
		$data[1] = html_print_extended_select_for_time("refresh",
			$refresh, '', '', 0, 7, true);
		$table->data[] = $data;
		$table->rowclass[] = '';
		
		if ($graph_type != "boolean" && $graph_type != "string") {
			$data = array();
			$data[0] = __('Avg. Only');
			$data[1] = html_print_checkbox ("avg_only", 1,
				(bool)$avg_only, true, false, 'show_others()');
			$data[1] .= html_print_input_hidden('show_other', 0, true);
			$table->data[] = $data;
			$table->rowclass[] = '';
		}
		
		$data = array();
		$data[0] = __('Begin date');
		$data[1] = html_print_input_text ("start_date", $start_date,'', 10, 20, true);
		$table->data[] = $data;
		$table->rowclass[] = '';
		
		$data = array();
		$data[0] = __('Begin time');
		$data[1] = html_print_input_text ("start_time", $start_time,'', 10, 10, true);
		$table->data[] = $data;
		$table->rowclass[] = '';
		
		$data = array();
		$data[0] = __('Zoom factor');
		$options = array ();
		$options[$zoom] = 'x' . $zoom;
		$options[1] = 'x1';
		$options[2] = 'x2';
		$options[3] = 'x3';
		$options[4] = 'x4';
		$data[1] = html_print_select ($options, "zoom", $zoom, '', '', 0, true);
		$table->data[] = $data;
		$table->rowclass[] = '';
		
		$data = array();
		$data[0] = __('Time range');
		$data[1] = html_print_extended_select_for_time('period',
			$period, '', '', 0, 7, true);
		$table->data[] = $data;
		$table->rowclass[] = '';
		
		$data = array();
		$data[0] = __('Show events');
		$disabled = false;
		if (isset($config['event_replication'])) {
			if ($config['event_replication'] && !$config['show_events_in_local']) {
				$disabled = true;
			}
		}
		$data[1] = html_print_checkbox ("draw_events", 1,
			(bool)$draw_events, true, $disabled);
		if ($disabled) {
			$data[1] .= ui_print_help_tip(
				__('Show events is disabled because this Pandora node is set the event replication.'), true);
		}
		$table->data[] = $data;
		$table->rowclass[] = '';
		
		$data = array();
		$data[0] = __('Show alerts');
		$data[1] = html_print_checkbox ("draw_alerts", 1, (bool) $draw_alerts, true);
		$table->data[] = $data;
		$table->rowclass[] = '';
		
		$data = array();
		$data[0] = __('Show event graph');
		$data[1] = html_print_checkbox ("show_events_graph", 1, (bool) $show_events_graph, true);
		$table->data[] = $data;
		$table->rowclass[] = '';
		
		switch ($graph_type) {
			case 'boolean':
			case 'sparse':
				$data = array();
				$data[0] = __('Show percentil 95º');
				$data[1] = html_print_checkbox ("show_percentil_95", 1, (bool) $show_percentil_95, true);
				$table->data[] = $data;
				$table->rowclass[] ='';
				
				$data = array();
				$data[0] = __('Time compare (Overlapped)');
				$data[1] = html_print_checkbox ("time_compare_overlapped", 1, (bool) $time_compare_overlapped, true);
				$table->data[] = $data;
				$table->rowclass[] = '';
				
				$data = array();
				$data[0] = __('Time compare (Separated)');
				$data[1] = html_print_checkbox ("time_compare_separated", 1, (bool) $time_compare_separated, true);
				$table->data[] = $data;
				$table->rowclass[] = '';
				
				$data = array();
				$data[0] = __('Show unknown graph');
				$data[1] = html_print_checkbox ("unknown_graph", 1, (bool) $unknown_graph, true);
				$table->data[] = $data;
				$table->rowclass[] = '';
				break;
		}
		
		$form_table = html_print_table($table, true);
		
		unset($table);
		
		$table->id = 'stat_win_form';
		$table->width = '100%';
		$table->cellspacing = 2;
		$table->cellpadding = 2;
		$table->class = 'databox';
		
		$data = array();
		$data[0] = html_print_div(array('id' => 'field_list', 'content' => $form_table,
			'style' => 'overflow: auto; height: 220px'), true);
		$table->data[] = $data;
		$table->rowclass[] = '';
		
		$data = array();
		$data[0] = '<div style="width:100%; text-align:right;">' .
			html_print_submit_button(__('Reload'), "submit", false,
				'class="sub upd"', true) .
			"</div>";
		$table->data[] = $data;
		$table->rowclass[] = '';
		
		$params['body_text'] .= html_print_table($table, true);
		$params['body_text'] .= '</form>';
		$params['body_text'] .= '</div>'; // outer
		
		// ICONS
		$params['icon_closed'] = '/images/graphmenu_arrow_hide.png';
		$params['icon_open'] = '/images/graphmenu_arrow.png';
		
		// SIZE
		$params['width'] = 500;
		
		// POSITION
		$params['position'] = 'left';
		
		html_print_side_layer($params);
		
		// Hidden div to forced title
		html_print_div(array('id' => 'forced_title_layer',
			'class' => 'forced_title_layer', 'hidden' => true));
		?>
		
	</body>
</html>

<?php
// Echo the script tags of the datepicker and the timepicker
// Modify the user language cause the ui.datepicker language files use - instead _
$custom_user_language = str_replace('_', '-', $user_language);
ui_require_jquery_file("ui.datepicker-" . $custom_user_language, "include/javascript/i18n/", true);
ui_include_time_picker(true);
?>

<script>
	$('#checkbox-time_compare_separated').click(function() {
		$('#checkbox-time_compare_overlapped').removeAttr('checked');
	});
	$('#checkbox-time_compare_overlapped').click(function() {
		$('#checkbox-time_compare_separated').removeAttr('checked');
	});
	
	
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
				if (show_overview) {
					window.resizeTo(width_window + 20, height_window + 50);
				}
				else {
					window.resizeTo(width_window + 20, height_window + 200);
				}
				show_overview = !show_overview;
				
			});
	<?php
	}
	?>
	
	// Add datepicker and timepicker
	$("#text-start_date").datepicker({
		dateFormat: "<?php echo DATE_FORMAT_JS; ?>"
	});
	$("#text-start_time").timepicker({
		showSecond: true,
		timeFormat: '<?php echo TIME_FORMAT_JS; ?>',
		timeOnlyTitle: '<?php echo __('Choose time');?>',
		timeText: '<?php echo __('Time');?>',
		hourText: '<?php echo __('Hour');?>',
		minuteText: '<?php echo __('Minute');?>',
		secondText: '<?php echo __('Second');?>',
		currentText: '<?php echo __('Now');?>',
		closeText: '<?php echo __('Close');?>'
	});
	
	$.datepicker.setDefaults($.datepicker.regional["<?php echo $custom_user_language; ?>"]);
	
	$(window).ready(function() {
		$("#field_list").css('height', ($(window).height() - 160) + 'px');
	});
	
	$(window).resize(function() {
		$("#field_list").css('height', ($(window).height() - 160) + 'px');
	});
</script>
