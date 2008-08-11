<?php
// Pandora FMS - the Flexible Monitoring System
// ========================================
// Copyright (c) 2008 Evi Vanoost <vanooste@rcbi.rochester.edu>
// Copyright (c) 2008 Esteban Sanchez <estebans@artica.es>
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
require("include/config.php");

check_login();

if (! give_acl ($config['id_user'], 0, "AW")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access downtime scheduler");
	require ("general/noaccess.php");
	return;
}

//Initialize data
$id_agent = get_parameter ("id_agent");
$modules = get_modules_in_agent ($id_agent);
$name = '';
$description = '';
$date_from = (string) get_parameter ('date_from', date ('Y-m-j'));
$time_from = (string) get_parameter ('time_from', date ('h:iA'));
$date_to = (string) get_parameter ('date_to', date ('Y-m-j'));
$time_to = (string) get_parameter ('time_to', date ('h:iA'));

$create_downtime = (int) get_parameter ('create_downtime');
$delete_downtime = (int) get_parameter ('delete_downtime');
$edit_downtime = (int) get_parameter ('edit_downtime');
$update_downtime = (int) get_parameter ('update_downtime');
$id_downtime = (int) get_parameter ('id_downtime');

//Here cometh the parsing of the entered form
if ($delete_downtime) {
	$sql = sprintf ("DELETE FROM tplanned_downtime WHERE id = %d",
			$id_downtime);
	echo $sql;
	$result = process_sql ($sql);
	if ($result === false) {
		echo '<h3 class="error">'.__('delete_no').'</h3>';
	} else {
		echo '<h3 class="suc">'.__('delete_ok').'</h3>';
	}
}

if ($create_downtime || $update_downtime) {
	$description = (string) get_parameter ('description');
	$name = (string) get_parameter ('name');
	$id_module = (int) get_parameter ('id_agent_module');
	$datetime_from = strtotime ($date_from.' '.$time_from);
	$datetime_to = strtotime ($date_to.' '.$time_to);
	
	echo $datetime_from.' > '.$datetime_to;
	if ($datetime_from > $datetime_to) {
		echo '<h3 class="error">'.__('create_no').': START &gt; END</h3>';
	} else {
		$sql = '';
		if ($create_downtime) {
			$sql = sprintf ("INSERT INTO tplanned_downtime (`name`,
				`description`, `date_from`, `date_to`, `id_agent_module`) 
				VALUES ('%s','%s',%d,%d,%d)",
				$name, $description, $datetime_from,
				$datetime_to, $id_module);
		} else if ($update_downtime) {
			$sql = sprintf ("UPDATE tplanned_downtime 
				SET `name`='%s', `description`='%s', `date_from`=%d,
				`date_to`=%d, `id_agent_module`=%d 
				WHERE `id` = '%d'",
				$name, $description, $datetime_from,
				$datetime_to, $id_module, $id_downtime);
		}
		
		$result = process_sql ($sql);
		if ($result === false) {
			echo '<h3 class="error">'.__('create_no').'</h3>';
		} else {
			echo '<h3 class="suc">'.__('create_ok').'</h3>';
		}
	}
}

if ($id_downtime) {
	$sql = sprintf ("SELECT `id`, `name`, `description`, `date_from`, `date_to`, `id_agent_module`
			FROM `tplanned_downtime` WHERE `id` = %d",
			$id_downtime);
	
	$result = get_db_row_sql ($sql);
	$name = $result["name"];
	$description = $result["description"];
	$id_agent_module = $result["id_agent_module"];
	$date_from = strftime ('%Y-%m-%d', $result["date_from"]);
	$date_to = strftime ('%Y-%m-%d', $result["date_to"]);
}

//Page header
echo '<h3>'.__('Planned Downtime Form').' <img class="img_help" src="images/help.png" onClick="pandora_help(\'planned_downtime\')" /></h3>';

$table->class = 'databox_color';
$table->width = '90%';
$table->data = array ();
$table->data[0][0] = __('Name');
$table->data[0][1] = print_input_text ('name', $name, '', 25, 40, true);
$table->data[1][0] = __('Module');
$table->data[1][1] = print_input_text ('name', $name, '', 25, 40, true);
$table->data[2][0] = __('Description');
$table->data[2][1] = print_textarea ('description', 2, 60, $description, '', true);
$table->data[3][0] = __('Time from');
$table->data[3][1] = print_input_text ('date_from', $date_from, '', 10, 10, true);
$table->data[3][1] .= print_input_text ('time_from', $time_from, '', 7, 7, true);
$table->data[4][0] = __('Time to');
$table->data[4][1] = print_input_text ('date_from', $date_from, '', 10, 10, true);
$table->data[4][1] .= print_input_text ('time_to', $time_to, '', 7, 7, true);

echo '<form method="POST" action="index.php?sec=gagente&sec2=godmode/agentes/planned_downtime">';
print_table ($table);
print_input_hidden ('id_agent', $id_agent);
echo '<div class="action-buttons" style="width: '.$table->width.'">';
if ($id_downtime) {
	print_input_hidden ('update_downtime', 1);
	print_input_hidden ('id_downtime', $id_downtime);
	print_submit_button (__('Update'), 'updbutton', false, 'class="sub upd"');
} else {
	print_input_hidden ('create_downtime', 1);
	print_submit_button (__('Add'), 'crtbutton', false, 'class="sub wand"');
}
echo '</div>';
echo '</form>';

//Start Overview of existing planned downtime
echo '<h3>'.__('Planned Downtime').':</h3>';
$table->width = '90%';
$table->data = array ();
$table->head = array ();
$table->head[0] = __('name');
$table->head[1] = __('module');
$table->head[2] = __('time_from');
$table->head[3] = __('time_to');

$sql = sprintf ("SELECT tplanned_downtime.id, tplanned_downtime.name, tplanned_downtime.id_agent_module, tplanned_downtime.date_from, tplanned_downtime.date_to 
	FROM tplanned_downtime, tagente_modulo WHERE tplanned_downtime.id_agent_module = tagente_modulo.id_agente_modulo 
	AND tagente_modulo.id_agente = %d AND date_to > UNIX_TIMESTAMP(NOW())", $id_agent);
$downtimes = get_db_all_rows_sql ($sql);
if ($downtimes === false) {
	$table->colspan[0][0] = 5;
	$table->data[0][0] = __('No planned downtime');
	$downtimes = array();
}

foreach ($downtimes as $downtime) {
	$data = array ();
	
	$data[0] = $downtime['name'];
	$data[1] = dame_nombre_modulo_agentemodulo ($downtime['id_agent_module']);
	$data[2] = date ("Y-m-d H:i", $downtime['date_from']);
	$data[3] = date ("Y-m-d H:i", $downtime['date_to']);
	$data[4] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/planned_downtime&id_agent='.
		$id_agent.'&delete_downtime=1&id_downtime='.$downtime['id'].'">
		<img src="images/cross.png" border="0" alt="'.__('delete').'"></a>';
	$data[4] .= '<a href="index.php?sec=gagente&sec2=godmode/agentes/planned_downtime&id_agent='.
		$id_agent.'&edit_downtime=1&id_downtime='.$downtime['id'].'">
		<img src="images/config.png" border="0" alt="'.__('Update').'"></a>';
	
	array_push ($table->data, $data);
}
print_table ($table);
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
	$("#text-time_from, #text-time_to").timeEntry ({
		spinnerImage: 'images/time-entry.png',
		spinnerSize: [20, 20, 0]
		});
	$("#text-date_from, #text-date_to").datepicker ();
	$.datepicker.regional["<?= $config['language'] ?>"];
});
</script>
