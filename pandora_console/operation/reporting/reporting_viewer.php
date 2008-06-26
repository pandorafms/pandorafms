<?php

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development and project architecture and management
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas, info@artica.es
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Login check
global $REMOTE_ADDR;

if (comprueba_login() != 0) {
	audit_db ($id_user, $REMOTE_ADDR, "ACL Violation","Trying to access graph builder");
	include ("general/noaccess.php");
	exit;
}

$id_report = (int) get_parameter ('id');

if (! $id_report) {
	audit_db ($id_user, $REMOTE_ADDR, "HACK Attempt","Trying to access graph viewer withoud ID");
	include ("general/noaccess.php");
	exit;
}

$report = get_db_row ('treport', 'id_report', $id_report);

if (! give_acl ($id_user, $report['id_group'], "AR") AND ! dame_admin ($id_user)) {
	audit_db ($id_user, $REMOTE_ADDR, "ACL Violation","Trying to access graph builder");
	include ("general/noaccess.php");
	exit;
}

require ("include/functions_reporting.php");

/* Check if the user can see the graph */
/* FIXME: It will check AR permissions against agent or group of the report */
if ($report['id_user'] != $id_user && ! dame_admin ($id_user) && ! $report['private']) {
	return;
}

$date = (string) get_parameter ('date', date ('Y-m-j'));
$time = (string) get_parameter ('time', date ('h:iA'));

echo "<h2>".lang_string ('reporting')." &gt; ";
echo lang_string ('custom_reporting')." - ";
echo $report['name']."</h2>";

$table->width = '750px';
$table->class = 'databox';
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->size = array ();
$table->size[0] = '50px';
$table->data = array ();
$table->data[0][0] = '<img src="images/reporting.png" width="32" height="32">';
if ($report['description'] != '')
	$table->data[0][1] = $report['description'];
else
	$table->data[0][1] = $report['name'];
$table->data[1][0] = lang_string ('date');
$table->data[1][1] = print_input_text ('date', $date, '', 10, 10, true). ' ';
$table->data[1][1] .= print_input_text ('time', $time, '', 7, 7, true). ' ';
$table->data[1][1] .= print_submit_button (lang_string ('update'), 'date_submit', false, 'class="sub next"', true);

echo '<form method="post" action="">';
print_table ($table);
print_input_hidden ('id_report', $id_report);
echo '</form>';

/* We must add javascript here. Otherwise, the date picker won't 
   work if the date is not correct because php is returning. */
?>

<link rel="stylesheet" href="include/styles/datepicker.css" type="text/css" media="screen">
<link rel="stylesheet" href="include/styles/timeentry.css" type="text/css" media="screen">
<script type="text/javascript" src="include/javascript/jquery.js"></script>
<script src="include/javascript/jquery.ui.core.js"></script>
<script src="include/javascript/jquery.ui.datepicker.js"></script>
<script src="include/languages/date_<?= $config['language'] ?>.js"></script>
<script src="include/languages/time_<?= $config['language'] ?>.js"></script>
<script src="include/javascript/jquery.timeentry.js"></script>

<script language="javascript" type="text/javascript">
$(document).ready (function () {
	$("#text-time").timeEntry ({spinnerImage: 'images/time-entry.png', spinnerSize: [20, 20, 0]});
	$("#text-date").datepicker ();
	$.datepicker.regional["<?= $config['language'] ?>"];
});
</script>

<?php
$datetime = strtotime ($date.' '.$time);

if ($datetime === false || $datetime == -1) {
	echo '<h3 class="error">'.lang_string ('invalid_date').'</h3>';
	return;
}
/* Date must not be older than now */
if ($datetime > time ()) {
	echo '<h3 class="error">'.lang_string ('date_older_than_now').'</h3>';
	return;
}

$table->data = array ();
$table->size = array ();
$table->style = array ();
$table->width = '750px';
$table->class = 'databox';
$table->rowclasses = array ();

$group_name = dame_grupo ($report['id_group']);
$sql = sprintf ('SELECT * FROM treport_content WHERE id_report = %d ORDER BY `order`', $id_report);
$contents = get_db_all_rows_sqlfree ($sql);
foreach ($contents as $content) {
	unset ($modules);
	unset ($weights);
	$module_name = get_db_value ('nombre', 'tagente_modulo', 'id_agente_modulo', $content['id_agent_module']);
	$agent_name = dame_nombre_agente_agentemodulo ($content['id_agent_module']);
	
	switch ($content["type"]) {
	case 1:
	case 'simple_graph':
		$table->colspan[1][0] = 3;
		$data = array ();
		$data[0] = '<h4>'.lang_string ('simple_graph').'</h4>';
		$data[1] = '<h4>'.$agent_name.' - '.$module_name.'</h4>';
		$data[2] = '<h4>'.human_time_description($content['period']).'</h4>';
		$n = array_push ($table->data, $data);
		$table->rowclass[$n - 1] = 'datos3';
		
		// $n is the number of elements in the array, so it will be the index of the next element
		$table->colspan[$n][0] = 3;
		$data = array ();
		$data[0] = '<img src="reporting/fgraph.php?tipo=sparse&id='.$content['id_agent_module'].'&height=230&width=720&period='.$content['period'].'&date='.$datetime.'&avg_only=1&pure=1" border="0" alt="">';
		array_push ($table->data, $data);
		
		break;
	case 2:
	case 'custom_graph':
		$graph = get_db_row ("tgraph", "id_graph", $content['id_gs']);
		$sql2 = sprintf ('SELECT * FROM tgraph_source WHERE id_graph = %d', $content['id_gs']);
		$res2 = mysql_query($sql2);
		while ($content2 = mysql_fetch_array($res2)) {
			$weight = $content2["weight"];
			$content['id_agent_module'] = $content2["id_agent_module"];
			if (!isset($modules)){
				$modules = $content['id_agent_module'];
				$weights = $weight;
			} else {
				$modules = $modules.",".$content['id_agent_module'];
				$weights = $weights.",".$weight;
			}
		}
		$data = array ();
		$data[0] = '<h4>'.lang_string ('custom_graph').'</h4>';
		$data[1] = "<h4>".$graph["name"]."</h4>";
		$data[2] = "<h4>".human_time_description($content['period'])."</h4>";
		$n = array_push ($table->data, $data);
		$table->rowclass[$n - 1] = 'datos3';
		
		$table->colspan[$n][0] = 3;
		$data = array ();
		$data[0] = '<img src="reporting/fgraph.php?tipo=combined&id='.$modules.'&weight_l='.$weights.'&height=230&width=720&period='.$content['period'].'&date='.$datetime.'&stacked='.$graph["stacked"].'&pure=1" border="1" alt="">';
		array_push ($table->data, $data);
		
		break;
	case 3:
	case 'SLA':
		$data = array ();
		$data[0] = '<h4>'.lang_string ('SLA').'</h4>';
		$data[1] = '<h4>'.$group_name.'</h4>';
		$data[2] = '<h4>'.human_time_description ($content['period']).'</h4>';
		$n = array_push ($table->data, $data);
		$table->rowclass[$n - 1] = 'datos3';
		
		$slas = get_db_all_rows_field_filter ('treport_content_sla_combined',
							'id_report_content', $content['id_rc']);
		if (sizeof ($slas) == 0) {
			$data = array ();
			$table->colspan[$n][0] = 3;
			$data[0] = lang_string ('no_defined_slas');
			array_push ($table->data, $data);
		}
		foreach ($slas as $sla) {
			$data = array ();
			
			$table->colspan[$n][0] = 2;
			$data[0] = '<span style="font-size: 0.6em">';
			$data[0] .= lang_string ('sla_max')." : ".$sla['sla_max']."<br>";
			$data[0] .= lang_string ('sla_min')." : ".$sla['sla_min']."<br>";
			$data[0] .= lang_string ('sla_limit')." : ".$sla['sla_limit']."<br>";
			$data[0] .= "</span>";
			
			$sla_value = return_module_SLA ($sla['id_agent_module'], $content['period'],
							$sla['sla_min'], $sla['sla_max'], $datetime);
			if ($sla_value === false) {
				$data[1] = '<span style="font: bold 3em Arial, Sans-serif; color: #0000FF;">';
				$data[1] .= lang_string ('unknown');
			} else {
				if ($sla_value >= $sla['sla_limit'])
					$data[1] = '<span style="font: bold 3em Arial, Sans-serif; color: #000000;">';
				else
					$data[1] = '<span style="font: bold 3em Arial, Sans-serif; color: #ff0000;">';
				$data[1] .= format_numeric ($sla_value). " %";
			}
			$data[1] .= "</span>";
			
			$n = array_push ($table->data, $data);
		}
		
		break;
	case 4:
	case 'event_report':
		$id_agent = dame_agente_id ($agent_name);
		$data = array ();
		$data[0] = "<h4>".lang_string ('event_report')."</h4>";
		$data[1] = "<h4>$agent_name - $module_name</h4>";
		$data[2] = "<h4>".human_time_description ($content['period'])."</h4>";
		$n = array_push ($table->data, $data);
		$table->rowclass[$n - 1] = 'datos3';
		
		$table->colspan[$n][0] = 3;
		$data = array ();
		$data[0] = event_reporting ($id_agent, $content['period'], $datetime, true);
		array_push ($table->data, $data);
		
		break;
	case 5:
	case 'alert_report':
		$data = array ();
		$data[0] = "<h4>".lang_string ('alert_report')."</h4>";
		$data[1] = "<h4>$group_name</h4>";
		$data[2] = "<h4>".human_time_description ($content['period'])."</h4>";
		$n = array_push ($table->data, $data);
		$table->rowclass[$n - 1] = 'datos3';
		
		$data = array ();
		$table->colspan[$n][0] = 3;
		$data[0] = alert_reporting ($report['id_group'], $content['period'], $datetime, true);
		array_push ($table->data, $data);
		
		break;
	case 6:
	case 'monitor_report':
		$data = array ();
		$data[0] = "<h4>".lang_string ('monitor_report')."</h4>";
		$data[1] = "<h4>$agent_name - $module_name</h4>";
		$data[2] = "<h4>".human_time_description ($content['period'])."</h4>";
		$n = array_push ($table->data, $data);
		$table->rowclass[$n - 1] = 'datos3';
		
		$data = array ();
		$monitor_value = format_numeric (return_module_SLA ($content['id_agent_module'], $content['period'], 1, 1, $datetime));
		$data[0] = '<p style="font: bold 3em Arial, Sans-serif; color: #000000;">';
		$data[0] .= $monitor_value.' % <img src="images/b_green.png" height="32" width="32"></p>';
		$monitor_value2 = format_numeric (100 - $monitor_value, 2) ;
		$data[1] = "<p style='font: bold 3em Arial, Sans-serif; color: #ff0000;'>";
		$data[1] .= $monitor_value2.' % <img src="images/b_red.png" height="32" width="32"></p>';
		array_push ($table->data, $data);
		
		break;
	case 7:
	case 'avg_value':
		$data = array ();
		$data[0] = "<h4>".lang_string ('avg_value')."</h4>";
		$data[1] = "<h4>$agent_name - $module_name</h4>";
		$data[2] = "<h4>".human_time_description ($content['period'])."</h4>";
		$n = array_push ($table->data, $data);
		$table->rowclass[$n - 1] = 'datos3';
		
		$data = array ();
		$table->colspan[$n][0] = 2;
		$value = format_numeric (return_moduledata_avg_value ($content['id_agent_module'], $content['period'], $datetime));
		$data[0] = '<p style="font: bold 3em Arial, Sans-serif; color: #000000;">'.$value.'</p>';
		array_push ($table->data, $data);
		
		break;
	case 8:
	case 'max_value':
		$data = array ();
		$data[0] = "<h4>".lang_string ('max_value')."</h4>";
		$data[1] = "<h4>$agent_name - $module_name</h4>";
		$data[2] = "<h4>".human_time_description ($content['period'])."</h4>";
		$n = array_push ($table->data, $data);
		$table->rowclass[$n - 1] = 'datos3';
		
		$data = array ();
		$table->colspan[$n][0] = 2;
		$value = format_numeric (return_moduledata_max_value ($content['id_agent_module'], $content['period'], $datetime));
		$data[0] = '<p style="font: bold 3em Arial, Sans-serif; color: #000000;">'.$value.'</p>';
		array_push ($table->data, $data);
		
		break;
	case 9:
	case 'min_value':
		$data = array ();
		$data[0] = "<h4>".lang_string ('min_value')."</h4>";
		$data[1] = "<h4>$agent_name - $module_name</h4>";
		$data[2] = "<h4>".human_time_description ($content['period'])."</h4>";
		$n = array_push ($table->data, $data);
		$table->rowclass[$n - 1] = 'datos3';
		
		$data = array ();
		$table->colspan[$n][0] = 2;
		$value = format_numeric (return_moduledata_min_value ($content['id_agent_module'], $content['period'], $datetime));
		$data[0] = '<p style="font: bold 3em Arial, Sans-serif; color: #000000;">'.$value.'</p>';
		array_push ($table->data, $data);
		
		break;
	case 10:
	case 'sumatory':
		$data = array ();
		$data[0] = "<h4>".lang_string ('sumatory')."</h4>";
		$data[1] = "<h4>$agent_name - $module_name</h4>";
		$data[2] = "<h4>".human_time_description ($content['period'])."</h4>";
		$n = array_push ($table->data, $data);
		$table->rowclass[$n - 1] = 'datos3';
		
		$data = array ();
		$table->colspan[$n][0] = 2;
		$value = format_numeric (return_moduledata_sum_value ($content['id_agent_module'], $content['period'], $datetime));
		$data[0] = '<p style="font: bold 3em Arial, Sans-serif; color: #000000;">'.$value.'</p>';
		array_push ($table->data, $data);
		
		break;
	case 11:
	case 'general_group_report':
		$data = array ();
		$data[0] = "<h4>".lang_string ('group')."</h4>";
		$data[1] = "<h4>$group_name</h4>";
		$n = array_push ($table->data, $data);
		$table->rowclass[$n - 1] = 'datos3';
		
		$data = array ();
		$table->colspan[$n][0] = 2;
		$value = format_numeric (return_moduledata_sum_value ($content['id_agent_module'], $content['period'], $datetime));
		$data[0] = general_group_reporting ($report['id_group'], true);
		array_push ($table->data, $data);
		
		break;
	case 12:
	case 'monitor_health':
		$data = array ();
		$data[0] = "<h4>".lang_string ('monitor_health')."</h4>";
		$data[1] = "<h4>$group_name</h4>";
		$data[2] = "<h4>".human_time_description ($content['period'])."</h4>";
		$n = array_push ($table->data, $data);
		$table->rowclass[$n - 1] = 'datos3';
		
		$data = array ();
		$table->colspan[$n][0] = 3;
		$data[0] = monitor_health_reporting ($report['id_group'], $content['period'], $datetime, true);
		array_push ($table->data, $data);
		
		break;
	case 13:
	case 'agents_detailed':
		$data = array ();
		$data[0] = "<h4>".lang_string ('agents_detailed')."</h4>";
		$data[1] = "<h4>$group_name</h4>";
		$data[2] = "";
		$n = array_push ($table->data, $data);
		$table->rowclass[$n - 1] = 'datos3';
		
		$data = array ();
		$table->colspan[$n][0] = 3;
		$data[0] = agents_detailed_reporting ($report['id_group'], $content['period'], $datetime, true);
		array_push ($table->data, $data);
		
		break;
	}
}

print_table ($table);

?>
