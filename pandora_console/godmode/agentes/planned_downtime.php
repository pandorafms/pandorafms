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


// Copyright (c) 2008 Evi Vanoost <vanooste@rcbi.rochester.edu>
// Please see http://pandora.sourceforge.net for full contribution list


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
$name = '';
$description = '';
$date_from = (string) get_parameter ('date_from', date ('Y-m-j'));
$time_from = (string) get_parameter ('time_from', date ('h:iA'));
$date_to = (string) get_parameter ('date_to', date ('Y-m-j'));
$time_to = (string) get_parameter ('time_to', date ('h:iA'));

$first_create = (int) get_parameter ('first_create', 0);
$first_update = (int) get_parameter ('first_update', 0);

$create_downtime = (int) get_parameter ('create_downtime');
$delete_downtime = (int) get_parameter ('delete_downtime');
$edit_downtime = (int) get_parameter ('edit_downtime');
$update_downtime = (int) get_parameter ('update_downtime');
$id_downtime = (int) get_parameter ('id_downtime',0);

$insert_downtime_agent = (int) get_parameter ("insert_downtime_agent", 0);
$delete_downtime_agent = (int) get_parameter ("delete_downtime_agent", 0);


// INSERT A NEW DOWNTIME_AGENT ASSOCIATION
if ($insert_downtime_agent == 1){
	$agents = $_POST["id_agent"];
	for ($a=0;$a <count($agents); $a++){ 
		$id_agente_dt = $agents[$a];
		$sql = "INSERT INTO tplanned_downtime_agents (id_downtime, id_agent) VALUES ($id_downtime, $id_agente_dt)";		
		$result = process_sql ($sql);
	}
}

// DELETE A DOWNTIME_AGENT ASSOCIATION
if ($delete_downtime_agent == 1){

	$id_da = get_parameter ("id_downtime_agent");
	
	$sql = "DELETE FROM tplanned_downtime_agents WHERE id = $id_da";
	$result = process_sql ($sql);
}

// DELETE WHOLE DOWNTIME!
if ($delete_downtime) {
	$sql = sprintf ("DELETE FROM tplanned_downtime WHERE id = %d", $id_downtime);
	$result = process_sql ($sql);
	$sql = sprintf ("DELETE FROM tplanned_downtime_agents WHERE id = %d", $id_downtime);
	$result2 = process_sql ($sql);

	if (($result === false) OR ($result2 === false)){
		echo '<h3 class="error">'.__('Not deleted. Error deleting data').'</h3>';
	} else {
		echo '<h3 class="suc">'.__('Deleted successfully').'</h3>';
	}
}

// UPDATE OR CREATE A DOWNTIME (MAIN DATA, NOT AGENT ASSOCIATION)

if ($create_downtime || $update_downtime) {
	$description = (string) get_parameter ('description');
	$name = (string) get_parameter ('name');
	$datetime_from = strtotime ($date_from.' '.$time_from);
	$datetime_to = strtotime ($date_to.' '.$time_to);
	
	if ($datetime_from > $datetime_to) {
		echo '<h3 class="error">'.__('Not created. Error inserting data').': START &gt; END</h3>';
	} else {
		$sql = '';
		if ($create_downtime) {
			$sql = sprintf ("INSERT INTO tplanned_downtime (`name`,
				`description`, `date_from`, `date_to`) 
				VALUES ('%s','%s',%d,%d)",
				$name, $description, $datetime_from,
				$datetime_to);
		} else if ($update_downtime) {
			$sql = sprintf ("UPDATE tplanned_downtime 
				SET `name`='%s', `description`='%s', `date_from`=%d,
				`date_to`=%d 
				WHERE `id` = '%d'",
				$name, $description, $datetime_from,
				$datetime_to, $id_downtime);
		}
		
		$result = process_sql ($sql);
		if ($result === false) {
			echo '<h3 class="error">'.__('Not created. Error inserting data').'</h3>';
		} else {
			echo '<h3 class="suc">'.__('Created successfully').'</h3>';
		}
	}
}

// Show create / update form
	
	if (($first_create != 0) OR ($first_update != 0)){
		// Have any data to show ?
		if ($id_downtime > 0) {
			$sql = sprintf ("SELECT `id`, `name`, `description`, `date_from`, `date_to`
					FROM `tplanned_downtime` WHERE `id` = %d",
					$id_downtime);
			
			$result = get_db_row_sql ($sql);
			$name = $result["name"];
			$description = $result["description"];
			$date_from = strftime ('%Y-%m-%d', $result["date_from"]);
			$date_to = strftime ('%Y-%m-%d', $result["date_to"]);
		}
		
			
		$table->class = 'databox_color';
		$table->width = '90%';
		$table->data = array ();
		$table->data[0][0] = __('Name');
		$table->data[0][1] = print_input_text ('name', $name, '', 25, 40, true);
		$table->data[2][0] = __('Description');
		$table->data[2][1] = print_textarea ('description', 3, 35, $description, '', true);
		$table->data[3][0] = __('Timestamp from');
		$table->data[3][1] = print_input_text ('date_from', $date_from, '', 10, 10, true);
		$table->data[3][1] .= print_input_text ('time_from', $time_from, '', 7, 7, true);
		
		$table->data[4][0] = __('Timestamp to');
		$table->data[4][1] = print_input_text ('date_to', $date_to, '', 10, 10, true);
		$table->data[4][1] .= print_input_text ('time_to', $time_to, '', 7, 7, true);
		
		echo '<form method="POST" action="index.php?sec=gagente&sec2=godmode/agentes/planned_downtime">';

		if ($id_downtime > 0){
			echo "<table width=100% border=0 cellpadding=4 >";
			echo "<tr><td width=65% valign='top'>";
		}
	
		//Editor form
		echo '<h3>'.__('Planned Downtime Form').' <img class="img_help" src="images/help.png" onClick="pandora_help(\'planned_downtime\')" /></h3>';
		print_table ($table);
	
	
		print_input_hidden ('id_agent', $id_agent);
		echo '<div class="action-buttons" style="width: 90%">';
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
		
	if ($id_downtime > 0) {

		echo "<td valign=top>";
		// Show available agents to include into downtime
		echo '<h3>'.__('Available Agents').':</h3>';
	
	
		$filter_group = get_parameter("filter_group", -1);
		if ($filter_group != -1)
			$filter_cond = " AND id_grupo = $filter_group ";
		else
			$filter_cond = "";
		$sql = sprintf ("SELECT tagente.id_agente, tagente.nombre FROM tagente WHERE tagente.id_agente NOT IN (SELECT tagente.id_agente FROM tagente, tplanned_downtime_agents WHERE tplanned_downtime_agents.id_agent = tagente.id_agente AND tplanned_downtime_agents.id_downtime = %d) AND disabled = 0 $filter_cond ORDER by tagente.nombre", $id_downtime);
		
		$downtimes = get_db_all_rows_sql ($sql);
		$data = array ();
		if ($downtimes)
			foreach ($downtimes as $downtime) {		
				$data[$downtime['id_agente']] = $downtime['nombre'];
			}
	
		echo "<form method=post action='index.php?sec=gagente&sec2=godmode/agentes/planned_downtime&first_update=1&id_downtime=$id_downtime'>";
	
		print_select_from_sql ("SELECT id_grupo, nombre FROM tgrupo WHERE id_grupo > 1", "filter_group", $filter_group, '', __("Any"), -1, false, false);
		echo "<br><br>";
		echo print_submit_button (__('Filter by group'), '', false, 'class="sub next"',false);
		echo "</form>";
	
		echo "<form method=post action='index.php?sec=gagente&sec2=godmode/agentes/planned_downtime&first_update=1&insert_downtime_agent=1&id_downtime=$id_downtime'>";
	
		echo print_select ($data, "id_agent[]", '', '', '', 0, false, true);
		echo "<br><br><br>";
		echo print_submit_button (__('Add'), '', false, 'class="sub next"',false);
		echo "</form>";
		echo "</table>";
		
		//Start Overview of existing planned downtime
		echo '<h3>'.__('Agents planned for this downtime').':</h3>';
		$table->class = 'databox';
		$table->width = '80%';
		$table->data = array ();
		$table->head = array ();
		$table->head[0] = __('Name');
		$table->head[1] = __('Group');
		$table->head[2] = __('OS');
		$table->head[3] = __('Last contact');
		$table->head[4] = __('Remove');
		
		$sql = sprintf ("SELECT tagente.nombre, tplanned_downtime_agents.id, tagente.id_os, tagente.id_agente, tagente.id_grupo, tagente.ultimo_contacto FROM tagente, tplanned_downtime_agents WHERE tplanned_downtime_agents.id_agent = tagente.id_agente AND tplanned_downtime_agents.id_downtime = %d ",$id_downtime);
		
		$downtimes = get_db_all_rows_sql ($sql);
		if ($downtimes === false) {
			$table->colspan[0][0] = 5;
			$table->data[0][0] = __('No planned downtime');
			$downtimes = array();
		}
		
		foreach ($downtimes as $downtime) {
			$data = array ();
			
			$data[0] = $downtime['nombre'];
	
			$data[1] = get_db_sql ("SELECT nombre FROM tgrupo WHERE id_grupo = ". $downtime["id_grupo"]);
	
	
			$data[2] = '<img src="images/'.dame_so_icon($downtime["id_os"]).'"> - '.dame_so_name($downtime["id_os"]);
			$data[3] = $downtime["ultimo_contacto"];
	
			$data[4] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/planned_downtime&id_agent='.
				$id_agent.'&delete_downtime_agent=1&first_update=1&id_downtime_agent='.$downtime["id"].'&id_downtime='.$id_downtime.'">
				<img src="images/cross.png" border="0" alt="'.__('Delete').'"></a>';
	
			
			array_push ($table->data, $data);
		}
		print_table ($table);
	}
} else {

	// View available downtimes present in database (if any of them)
		$table->class = 'databox';
		//Start Overview of existing planned downtime
		echo '<h3>'.__('Planned Downtime present on system').':</h3>';
		$table->width = '90%';
		$table->data = array ();
		$table->head = array ();
		$table->head[0] = __('Name #Ag.');
		$table->head[1] = __('Description');
		$table->head[2] = __('From');
		$table->head[3] = __('To');
		$table->head[4] = __('Del');
		$table->head[5] = __('Upd');
		$table->head[6] = __('Running');

		$sql = "SELECT * FROM tplanned_downtime";
		$downtimes = get_db_all_rows_sql ($sql);
		if (!$downtimes) {
			echo '<div class="nf">'.__('No planned downtime').'</div>';
		}
		else {
			foreach ($downtimes as $downtime) {
				$data = array();
				$total  = get_db_sql ("SELECT COUNT(id_agent) FROM tplanned_downtime_agents WHERE id_downtime = ".$downtime["id"]);

				$data[0] = $downtime['name']. " ($total)";
				$data[1] = $downtime['description'];
				$data[2] = date ("Y-m-d H:i", $downtime['date_from']);
				$data[3] = date ("Y-m-d H:i", $downtime['date_to']);
				if ($downtime["executed"] == 0){
					$data[4] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/planned_downtime&id_agent='.
					$id_agent.'&delete_downtime=1&id_downtime='.$downtime['id'].'">
					<img src="images/cross.png" border="0" alt="'.__('Delete').'"></a>';
					$data[5] = '<a href="index.php?sec=gagente&sec2=godmode/agentes/planned_downtime&edit_downtime=1&first_update=1&id_downtime='.$downtime['id'].'">
					<img src="images/config.png" border="0" alt="'.__('Update').'"></a>';
				}
				if ($downtime["executed"] == 0)
					$data[6] = "<img src='images/pixel_green.png' width=20 height=20>";
				else
					$data[6] = "<img src='images/pixel_red.png' width=20 height=20>";

				array_push ($table->data, $data);
			}
			print_table ($table);
		}
	echo '<div class="action-buttons" style="width: '.$table->width.'">';

		echo "<form method=post action='index.php?sec=gagente&sec2=godmode/agentes/planned_downtime'>";
		echo "<input type=hidden name='first_create' value='1'>";
		echo "<input type=submit class='sub wand' value='".__('Create')."'>";
		echo "</form>";
}

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
