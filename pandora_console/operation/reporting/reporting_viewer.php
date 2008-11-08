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


// Login check
require("include/config.php");

check_login();

$id_report = (int) get_parameter ('id');

if (! $id_report) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "HACK Attempt",
		"Trying to access graph viewer withoud ID");
	include ("general/noaccess.php");
	exit;
}

$report = get_db_row ('treport', 'id_report', $id_report);

if (! give_acl ($config['id_user'], $report['id_group'], "AR")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation","Trying to access graph reader");
	include ("general/noaccess.php");
	exit;
}

require ("include/functions_reporting.php");

/* Check if the user can see the graph */
if ($report['private'] && ($report['id_user'] != $config['id_user'] && ! dame_admin ($config['id_user']))) {
	include ("general/noaccess.php");
	return;
}

$date = (string) get_parameter ('date', date ('Y-m-j'));
$time = (string) get_parameter ('time', date ('h:iA'));

echo "<h2>".__('Reporting')." &gt; ";
echo __('Custom reporting')." - ";
echo $report['name']."</h2>";

$table->width = '99%';
$table->class = 'databox';
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->size = array ();
$table->size[0] = '50px';
$table->data = array ();
$table->data[0][0] = '<img src="images/reporting.png" width="32" height="32" />';
if ($report['description'] != '') {
	$table->data[0][1] = $report['description'];
} else {
	$table->data[0][1] = $report['name'];
}
$table->data[1][0] = __('Date');
$table->data[1][1] = print_input_text ('date', $date, '', 10, 10, true). ' ';
$table->data[1][1] .= print_input_text ('time', $time, '', 7, 7, true). ' ';
$table->data[1][1] .= print_submit_button (__('Update'), 'date_submit', false, 'class="sub next"', true);

echo '<form method="post" action="">';
print_table ($table);
print_input_hidden ('id_report', $id_report);
echo '</form>';

echo '<div id="loading">';
echo '<img src="images/wait.gif" border="0" /><br />';
echo '<strong>'.__('Loading').'...</strong>';
echo '</div>';

/* We must add javascript here. Otherwise, the date picker won't 
   work if the date is not correct because php is returning. */
?>

<link rel="stylesheet" href="include/styles/datepicker.css" type="text/css" media="screen">
<link rel="stylesheet" href="include/styles/timeentry.css" type="text/css" media="screen">
<script type="text/javascript" src="include/javascript/jquery.js"></script>
<script src="include/javascript/jquery.ui.core.js"></script>
<script src="include/javascript/jquery.ui.datepicker.js"></script>
<script src="include/javascript/jquery.timeentry.js"></script>
<script src="include/languages/date_<?php echo $config['language']; ?>.js"></script>
<script src="include/languages/time_<?php echo $config['language']; ?>.js"></script>

<script language="javascript" type="text/javascript">

$(document).ready (function () {
	$("#loading").slideUp ();
	$("#text-time").timeEntry ({spinnerImage: 'images/time-entry.png', spinnerSize: [20, 20, 0]});
	$("#text-date").datepicker ();
	$.datepicker.regional["<?php echo $config['language']; ?>"];
});
</script>

<?php
$datetime = strtotime ($date.' '.$time);

if ($datetime === false || $datetime == -1) {
	echo '<h3 class="error">'.__('Invalid date selected').'</h3>';
	return;
}
/* Date must not be older than now */
if ($datetime > time ()) {
	echo '<h3 class="error">'.__('Selected date is older than current date').'</h3>';
	return;
}

$table->size = array ();
$table->style = array ();
$table->width = '99%';
$table->class = 'databox report_table';
$table->rowclass = array ();
$table->rowclass[0] = 'datos3';

$group_name = dame_grupo ($report['id_group']);
$contents = get_db_all_rows_field_filter ("treport_content", "id_report", $id_report, "`order`");
if ($contents === false) {
	return;
};
foreach ($contents as $content) {
	$table->data = array ();
	$table->head = array ();
	$table->style = array ();
	$table->colspan = array ();
	$table->rowstyle = array ();
	
	$module_name = get_db_value ('nombre', 'tagente_modulo', 'id_agente_modulo', $content['id_agent_module']);
	$agent_name = dame_nombre_agente_agentemodulo ($content['id_agent_module']);
	
	switch ($content["type"]) {
	case 1:
	case 'simple_graph':
		$table->colspan[1][0] = 4;
		$data = array ();
		$data[0] = '<h4>'.__('Simple graph').'</h4>';
		$data[1] = '<h4>'.$agent_name.' - '.$module_name.'</h4>';
		$data[2] = '<h4>'.human_time_description ($content['period']).'</h4>';
		array_push ($table->data, $data);
		
		$data = array ();
		$data[0] = '<img src="reporting/fgraph.php?tipo=sparse&id='.$content['id_agent_module'].'&height=230&width=720&period='.$content['period'].'&date='.$datetime.'&avg_only=1&pure=1" border="0" alt="">';
		array_push ($table->data, $data);
		
		break;
	case 2:
	case 'custom_graph':
		$graph = get_db_row ("tgraph", "id_graph", $content['id_gs']);
		$data = array ();
		$data[0] = '<h4>'.__('Custom graph').'</h4>';
		$data[1] = "<h4>".$graph["name"]."</h4>";
		$data[2] = "<h4>".human_time_description ($content['period'])."</h4>";
		array_push ($table->data, $data);
		
		$result = get_db_all_rows_field_filter ("tgraph_source", "id_graph", $content['id_gs']);
		$modules = array ();
		$weights = array ();
		if ($result === false)
			$result = array();
		
		foreach ($result as $content2) {
			array_push ($modules, $content2['id_agent_module']);
			array_push ($weights, $content2["weight"]);
		}
		
		$graph_width = get_db_sql ("SELECT width FROM tgraph WHERE id_graph = ".$content["id_gs"]);
		$graph_height= get_db_sql ("SELECT height FROM tgraph WHERE id_graph = ".$content["id_gs"]);


		$table->colspan[1][0] = 4;
		$data = array ();
		$data[0] = '<img src="reporting/fgraph.php?tipo=combined&id='.implode (',', $modules).'&weight_l='.implode (',', $weights).'&height='.$graph_height.'&width='.$graph_width.'&period='.$content['period'].'&date='.$datetime.'&stacked='.$graph["stacked"].'&pure=1" border="1" alt="">';
		array_push ($table->data, $data);
		
		break;
	case 3:
	case 'SLA':
		$table->colspan[0][0] = 2;
		$table->style[1] = 'text-align: right';
		$data = array ();
		$data[0] = '<h4>'.__('S.L.A').'</h4>';
		$data[1] = '<h4>'.human_time_description ($content['period']).'</h4>';;
		$n = array_push ($table->data, $data);
		
		$slas = get_db_all_rows_field_filter ('treport_content_sla_combined',
							'id_report_content', $content['id_rc']);
		if ($slas === false) {
			$data = array ();
			$table->colspan[1][0] = 3;
			$data[0] = __('There are no SLAs defined');
			array_push ($table->data, $data);
			$slas = array ();
		}
		
		$sla_failed = false;
		foreach ($slas as $sla) {
			$data = array ();
			
			$table->colspan[$n][0] = 2;
			$data[0] = '<strong>'.__('Agent')."</strong> : ";
			$data[0] .= dame_nombre_agente_agentemodulo ($sla['id_agent_module'])."<br />";
			$data[0] .= '<strong>'.__('Module')."</strong> : ";
			$data[0] .= dame_nombre_modulo_agentemodulo ($sla['id_agent_module'])."<br />";
			$data[0] .= '<strong>'.__('SLA Max. (value)')."</strong> : ";
			$data[0] .= $sla['sla_max']."<br />";
			$data[0] .= '<strong>'.__('SLA Min. (value)')."</strong> : ";
			$data[0] .= $sla['sla_min']."<br />";
			$data[0] .= '<strong>'.__('SLA Limit')."</strong> : ";
			$data[0] .= $sla['sla_limit'];
			$sla_value = get_agent_module_sla ($sla['id_agent_module'], $content['period'],
							$sla['sla_min'], $sla['sla_max'], $datetime);
			if ($sla_value === false) {
				$data[1] = '<span style="font: bold 3em Arial, Sans-serif; color: #0000FF;">';
				$data[1] .= __('Unknown');
			} else {
				if ($sla_value >= $sla['sla_limit'])
					$data[1] = '<span style="font: bold 3em Arial, Sans-serif; color: #000000;">';
				else {
					$sla_failed = true;
					$data[1] = '<span style="font: bold 3em Arial, Sans-serif; color: #ff0000;">';
				}
				$data[1] .= format_numeric ($sla_value). " %";
			}
			$data[1] .= "</span>";
			
			$n = array_push ($table->data, $data);
		}
		if (!empty ($slas)) {
			$data = array ();
			if ($sla_failed == false)
				$data[0] = '<span style="font: bold 3em Arial, Sans-serif; color: #000000;">'.__('Ok').'</span>';
			else
				$data[0] = '<span style="font: bold 3em Arial, Sans-serif; color: #ff0000;">'.__('Fail').'</span>';
			$n = array_push ($table->data, $data);
			$table->colspan[$n - 1][0] = 3;
			$table->rowstyle[$n - 1] = 'text-align: right';
		}
		
		break;
	case 4:
	case 'event_report':
		$table->colspan[0][0] = 2;
		$id_agent = dame_agente_id ($agent_name);
		$data = array ();
		$data[0] = "<h4>".__('Event report')."</h4>";
		$data[1] = "<h4>".human_time_description ($content['period'])."</h4>";
		array_push ($table->data, $data);
		
		$table->colspan[1][0] = 3;
		$data = array ();
		$table_report = event_reporting ($report['id_group'], $content['period'], $datetime, true);
		$table_report->class = 'databox';
		$table_report->width = '100%';
		$data[0] = print_table ($table_report, true);
		array_push ($table->data, $data);
		
		break;
	case 5:
	case 'alert_report':
		$data = array ();
		$data[0] = "<h4>".__('Alert report')."</h4>";
		$data[1] = "<h4>$group_name</h4>";
		$data[2] = "<h4>".human_time_description ($content['period'])."</h4>";
		array_push ($table->data, $data);
		
		$data = array ();
		$table->colspan[1][0] = 3;
		$data[0] = alert_reporting ($report['id_group'], $content['period'], $datetime, true);
		array_push ($table->data, $data);
		
		break;
	case 6:
	case 'monitor_report':
		$data = array ();
		$data[0] = "<h4>".__('Monitor report')."</h4>";
		$data[1] = "<h4>$agent_name - $module_name</h4>";
		$data[2] = "<h4>".human_time_description ($content['period'])."</h4>";
		array_push ($table->data, $data);
		
		$data = array ();
		$monitor_value = format_numeric (get_agent_module_sla ($content['id_agent_module'], $content['period'], 1, 1, $datetime));
		$data[0] = '<p style="font: bold 3em Arial, Sans-serif; color: #000000;">';
		$data[0] .= $monitor_value.' % <img src="images/b_green.png" height="32" width="32" /></p>';
		$monitor_value = format_numeric (100 - $monitor_value, 2) ;
		$data[1] = '<p style="font: bold 3em Arial, Sans-serif; color: #ff0000;">';
		$data[1] .= $monitor_value.' % <img src="images/b_red.png" height="32" width="32" /></p>';
		array_push ($table->data, $data);
		
		break;
	case 7:
	case 'avg_value':
		$data = array ();
		$data[0] = "<h4>".__('Avg. Value')."</h4>";
		$data[1] = "<h4>$agent_name - $module_name</h4>";
		$data[2] = "<h4>".human_time_description ($content['period'])."</h4>";
		array_push ($table->data, $data);
		
		$data = array ();
		$table->colspan[1][0] = 2;
		$value = format_numeric (get_agent_module_value_average ($content['id_agent_module'], $content['period'], $datetime));
		$data[0] = '<p style="font: bold 3em Arial, Sans-serif; color: #000000;">'.$value.'</p>';
		array_push ($table->data, $data);
		
		break;
	case 8:
	case 'max_value':
		$data = array ();
		$data[0] = "<h4>".__('Max. Value')."</h4>";
		$data[1] = "<h4>$agent_name - $module_name</h4>";
		$data[2] = "<h4>".human_time_description ($content['period'])."</h4>";
		array_push ($table->data, $data);
		
		$data = array ();
		$table->colspan[1][0] = 2;
		$value = format_numeric (get_agent_module_value_max ($content['id_agent_module'], $content['period'], $datetime));
		$data[0] = '<p style="font: bold 3em Arial, Sans-serif; color: #000000;">'.$value.'</p>';
		array_push ($table->data, $data);
		
		break;
	case 9:
	case 'min_value':
		$data = array ();
		$data[0] = "<h4>".__('Min. Value')."</h4>";
		$data[1] = "<h4>$agent_name - $module_name</h4>";
		$data[2] = "<h4>".human_time_description ($content['period'])."</h4>";
		array_push ($table->data, $data);
		
		$data = array ();
		$table->colspan[1][0] = 2;
		$value = format_numeric (get_agent_module_value_min ($content['id_agent_module'], $content['period'], $datetime));
		$data[0] = '<p style="font: bold 3em Arial, Sans-serif; color: #000000;">'.$value.'</p>';
		array_push ($table->data, $data);
		
		break;
	case 10:
	case 'sumatory':
		$data = array ();
		$data[0] = "<h4>".__('Sumatory')."</h4>";
		$data[1] = "<h4>$agent_name - $module_name</h4>";
		$data[2] = "<h4>".human_time_description ($content['period'])."</h4>";
		array_push ($table->data, $data);
		
		$data = array ();
		$table->colspan[1][0] = 2;
		$value = format_numeric (get_agent_module_value_sumatory ($content['id_agent_module'], $content['period'], $datetime));
		$data[0] = '<p style="font: bold 3em Arial, Sans-serif; color: #000000;">'.$value.'</p>';
		array_push ($table->data, $data);
		
		break;
	case 11:
	case 'general_group_report':
		$data = array ();
		$data[0] = "<h4>".__('Group')."</h4>";
		$data[1] = "<h4>$group_name</h4>";
		array_push ($table->data, $data);
		
		$data = array ();
		$table->colspan[1][0] = 2;
		$data[0] = general_group_reporting ($report['id_group'], true);
		array_push ($table->data, $data);
		
		break;
	case 12:
	case 'monitor_health':
		$data = array ();
		$data[0] = "<h4>".__('Monitor health')."</h4>";
		$data[1] = "<h4>$group_name</h4>";
		$data[2] = "<h4>".human_time_description ($content['period'])."</h4>";
		array_push ($table->data, $data);
		
		$data = array ();
		$table->colspan[1][0] = 4;
		$data[0] = monitor_health_reporting ($report['id_group'], $content['period'], $datetime, true);
		array_push ($table->data, $data);
		
		break;
	case 13:
	case 'agents_detailed':
		$data = array ();
		$data[0] = "<h4>".__('Agents detailed view')."</h4>";
		$data[1] = "<h4>$group_name</h4>";
		array_push ($table->data, $data);
		$table->colspan[0][0] = 2;
		
		$data = array ();
		$table->colspan[1][0] = 3;
		$data[0] = get_agents_detailed_reporting ($report['id_group'], $content['period'], $datetime, true);
		array_push ($table->data, $data);
		
		break;
	}
	print_table ($table);
	flush ();
}

?>
