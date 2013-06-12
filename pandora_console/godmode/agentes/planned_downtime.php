<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Load global vars
global $config;

check_login();

if (! check_acl ($config['id_user'], 0, "AW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access downtime scheduler");
	require ("general/noaccess.php");
	return;
}

require_once ('include/functions_users.php');

//Initialize data
$id_agent = get_parameter ("id_agent");
$id_group = (int) get_parameter ("id_group", 0);
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
$stop_downtime = (int) get_parameter ('stop_downtime');
$edit_downtime = (int) get_parameter ('edit_downtime');
$update_downtime = (int) get_parameter ('update_downtime');
$id_downtime = (int) get_parameter ('id_downtime',0);

$insert_downtime_agent = (int) get_parameter ("insert_downtime_agent", 0);
$delete_downtime_agent = (int) get_parameter ("delete_downtime_agent", 0);

$groups = users_get_groups ();

$only_alerts = (bool) get_parameter ('only_alerts', 0);

// Header
ui_print_page_header (__("Planned Downtime") . ui_print_help_icon ('planned_downtime', true), "images/god1.png", false, "", true, "");

// STOP DOWNTIME
if ($stop_downtime == 1) {
	$sql = "SELECT * FROM tplanned_downtime where id = $id_downtime";
	$result = db_get_row_sql($sql);
	$name = $result['name'];
	$description = $result['description'];
	$date_from = $result['date_from'];
	$executed = $result['executed'];
	$id_group = $result['id_group'];
	$only_alerts = $result['only_alerts'];
	$date_stop = date ("Y-m-j",get_system_time ());
	$time_stop = date ("h:iA",get_system_time ());
	$date_time_stop = strtotime ($date_stop.' '.$time_stop);
	
	$values = array(
		'name' => $name,
		'description' => $description,
		'date_from' => $date_from,
		'date_to' => $date_time_stop,
		'executed' => $executed,
		'id_group' => $id_group,
		'only_alerts' => $only_alerts
		);
	
	$result = db_process_sql_update('tplanned_downtime', $values, array ('id' => $id_downtime));
}

// INSERT A NEW DOWNTIME_AGENT ASSOCIATION
if ($insert_downtime_agent == 1) {
	$agents = $_POST["id_agent"];
	for ($a=0;$a <count($agents); $a++) {
		$id_agente_dt = $agents[$a];
		
		$values = array(
			'id_downtime' => $id_downtime,
			'id_agent' => $id_agente_dt);
		$result = db_process_sql_insert('tplanned_downtime_agents', $values);
	}
}

// DELETE A DOWNTIME_AGENT ASSOCIATION
if ($delete_downtime_agent == 1) {
	
	$id_da = get_parameter ("id_downtime_agent");
	
	$result = db_process_sql_delete('tplanned_downtime_agents', array('id' => $id_da));
}

// DELETE WHOLE DOWNTIME!
if ($delete_downtime) {
	$result = db_process_sql_delete('tplanned_downtime', array('id' => $id_downtime));
	
	$result2 = db_process_sql_delete('tplanned_downtime_agents', array('id' => $id_downtime));
	
	if (($result === false) OR ($result2 === false)){
		echo '<h3 class="error">'.__('Not deleted. Error deleting data').'</h3>';
	}
	else {
		echo '<h3 class="suc">'.__('Successfully deleted').'</h3>';
	}
}

// UPDATE OR CREATE A DOWNTIME (MAIN DATA, NOT AGENT ASSOCIATION)
$result = false;
if ($create_downtime || $update_downtime) {
	$description = (string) get_parameter ('description');
	$name = (string) get_parameter ('name');
	$check = db_get_value ('name', 'tplanned_downtime', 'name', $name);
	
	$datetime_from = strtotime ($date_from.' '.$time_from);
	$datetime_to = strtotime ($date_to.' '.$time_to);
	
	if ($datetime_from > $datetime_to) {
		echo '<h3 class="error">' . __('Not created. Error inserting data').': START &gt; END</h3>';
	}
	else {
		$sql = '';
		if ($create_downtime) {
			if (trim(io_safe_output($name)) != '') {
				if (!$check) {
					$values = array(
						'name' => $name,
						'description' => $description,
						'date_from' => $datetime_from,
						'date_to' => $datetime_to,
						'id_group' => $id_group,
						'only_alerts' => (int)$only_alerts);
					$result = db_process_sql_insert('tplanned_downtime', $values);
					$id_downtime = $result;
				}
				else {
					echo "<h3 class='error'>".__('Each planned downtime must have a different name')."</h3>";
				}
			}
			else {
				echo '<h3 class="error">'.__('Planned downtime must have a name').'</h3>';
			}
		}
		else if ($update_downtime) {
			if (trim(io_safe_output($name)) != '') {
				$values = array(
					'name' => $name,
					'description' => $description,
					'date_from' => $datetime_from,
					'date_to' => $datetime_to,
					'id_group' => $id_group,
					'only_alerts' => (int)$only_alerts);
				$result = db_process_sql_update('tplanned_downtime', $values, array('id' => $id_downtime));
			}
			else {
				echo '<h3 class="error">'.__('Planned downtime must have a name').'</h3>';
			}
		}
		
		if ($result === false) {
			if($create_downtime) {
				echo '<h3 class="error">'.__('Could not be created').'</h3>';
			}
			else {
				echo '<h3 class="error">'.__('Could not be updated').'</h3>';
			}
		}
		else {
			if ($create_downtime && $name && !$check) {
				echo '<h3 class="suc">'.__('Successfully created').'</h3>';
			}
			else if ($update_downtime && $name) {
				echo '<h3 class="suc">'.__('Successfully updated').'</h3>';
			}
		}
	}
}


////////////////////////////////
// Show create / update form
////////////////////////////////
if (($first_create != 0) OR ($first_update != 0) ||
	($create_downtime && $result)) {
	// Have any data to show ?
	if ($id_downtime > 0) {
		switch ($config["dbtype"]) {
			case "mysql":
				$sql = sprintf ("SELECT `id`, `name`, `description`, `date_from`, `date_to`, `id_group`, `only_alerts`
					FROM `tplanned_downtime` WHERE `id` = %d",
					$id_downtime);
				break;
			case "postgresql":
				$sql = sprintf ("SELECT \"id\", \"name\", \"description\", \"date_from\", \"date_to\", \"id_group\", \"only_alerts\"
					FROM \"tplanned_downtime\" WHERE \"id\" = %d",
					$id_downtime);
				break;
			case "oracle":
				$sql = sprintf ("SELECT id, name, description, date_from, date_to, id_group, only_alerts
					FROM tplanned_downtime WHERE id = %d",
					$id_downtime);
				break;
		}
		
		$result = db_get_row_sql ($sql);
		$name = $result["name"];
		$description = $result["description"];
		$date_from = strftime ('%Y-%m-%d', $result["date_from"]);
		$date_to = strftime ('%Y-%m-%d', $result["date_to"]);
		$time_from = strftime ('%I:%M%p', $result["date_from"]);
		$time_to = strftime ('%I:%M%p', $result["date_to"]);
		$only_alerts = $result["only_alerts"];
		
		if ($id_group == 0)
			$id_group = $result['id_group'];
	}
	
	$table->class = 'databox_color';
	$table->width = '98%';
	$table->data = array ();
	$table->data[0][0] = __('Name');
	$table->data[0][1] = html_print_input_text ('name', $name, '', 25, 40, true);
	$table->data[2][0] = __('Description');
	$table->data[2][1] = html_print_textarea ('description', 3, 35, $description, '', true);
	$table->data[3][0] = __('Timestamp from');
	$table->data[3][1] = html_print_input_text ('date_from', $date_from, '', 10, 10, true);
	$table->data[3][1] .= html_print_input_text ('time_from', $time_from, '', 7, 7, true);

	$table->data[4][0] = __('Timestamp to');
	$table->data[4][1] = html_print_input_text ('date_to', $date_to, '', 10, 10, true);
	$table->data[4][1] .= html_print_input_text ('time_to', $time_to, '', 7, 7, true);

	$table->data[5][0] = __('Group');
	$table->data[5][1] = html_print_select_groups(false, "AR", true, 'id_group', $id_group, '', '', 0, true);
	$table->data[6][0] = __('Only alerts');
	$table->data[6][1] = html_print_checkbox('only_alerts', 1, $only_alerts, true);
	echo '<form method="POST" action="index.php?sec=gagente&amp;sec2=godmode/agentes/planned_downtime">';

	if ($id_downtime > 0) {
		echo "<table width=100% border=0 cellpadding=4 >";
		echo "<tr><td width=75% valign='top'>";
	}

	//Editor form
	html_print_table ($table);

	html_print_input_hidden ('id_agent', $id_agent);
	echo '<div class="action-buttons" style="width: 90%">';
	if ($id_downtime) {
		html_print_input_hidden ('update_downtime', 1);
		html_print_input_hidden ('id_downtime', $id_downtime);
		html_print_submit_button (__('Update'), 'updbutton', false, 'class="sub upd"');
	}
	else {
		html_print_input_hidden ('create_downtime', 1);
		html_print_submit_button (__('Add'), 'crtbutton', false, 'class="sub wand"');
	}
	echo '</div>';
	echo '</form>';

	if ($id_downtime > 0) {

		echo "<td valign=top>";
		// Show available agents to include into downtime
		echo '<h4>'.__('Available agents').':</h4>';

		$filter_group = get_parameter("filter_group", 0);

		$filter_cond = '';
		if($filter_group > 0)
			$filter_cond = " AND id_grupo = $filter_group ";
		$sql = sprintf ("SELECT tagente.id_agente, tagente.nombre, tagente.id_grupo
			FROM tagente
			WHERE tagente.id_agente NOT IN (
					SELECT tagente.id_agente
					FROM tagente, tplanned_downtime_agents
					WHERE tplanned_downtime_agents.id_agent = tagente.id_agente
						AND tplanned_downtime_agents.id_downtime = %d
				) AND disabled = 0 $filter_cond
			ORDER by tagente.nombre", $id_downtime);
		$downtimes = db_get_all_rows_sql ($sql);
		$data = array ();
		if ($downtimes) {
			foreach ($downtimes as $downtime) {
				if (check_acl ($config["id_user"], $downtime['id_grupo'], "AR")) {
					$data[$downtime['id_agente']] = $downtime['nombre'];
				}
			}
		}

		$disabled_add_button = false;
		if (empty($data)) {
			$disabled_add_button = true;
		}

		echo "<form method=post action='index.php?sec=gagente&sec2=godmode/agentes/planned_downtime&first_update=1&id_downtime=$id_downtime'>";

		html_print_select_groups(false, "AR", true, 'filter_group', $filter_group, '', '', '', false, false, true, '', false, 'width:180px');

		echo "<br /><br />";
		html_print_submit_button (__('Filter by group'), '', false, 'class="sub next"',false);
		echo "</form>";

		echo "<form method=post action='index.php?sec=gagente&sec2=godmode/agentes/planned_downtime&first_update=1&insert_downtime_agent=1&id_downtime=$id_downtime'>";

		echo html_print_select ($data, "id_agent[]", '', '', '', 0, false, true, true, '', false, 'width: 180px;');
		echo "<br /><br /><br />";
		html_print_submit_button (__('Add'), '', $disabled_add_button, 'class="sub next"',false);
		echo "</form>";
		echo "</table>";

		//Start Overview of existing planned downtime
		echo '<h4>'.__('Agents planned for this downtime').':</h4>';

		$sql = sprintf ("SELECT tagente.nombre, tplanned_downtime_agents.id,
				tagente.id_os, tagente.id_agente, tagente.id_grupo,
				tagente.ultimo_contacto
			FROM tagente, tplanned_downtime_agents
			WHERE tplanned_downtime_agents.id_agent = tagente.id_agente
				AND tplanned_downtime_agents.id_downtime = %d ",$id_downtime);

		$downtimes = db_get_all_rows_sql ($sql);
		if ($downtimes === false) {
			echo '<div class="nf">'. __('There are no scheduled downtimes').'</div>'; 	 
	    } 	 
	    else {
			$table->class = 'databox';
			$table->width = '98%';
			$table->data = array ();
			$table->head = array ();
			$table->head[0] = __('Name');
			$table->head[1] = __('Group');
			$table->head[2] = __('OS');
			$table->head[3] = __('Last contact');
			$table->head[4] = __('Remove');
			$table->align[4] = "center";;

			foreach ($downtimes as $downtime) {
				$data = array ();

				$data[0] = $downtime['nombre'];

				$data[1] = db_get_sql ("SELECT nombre FROM tgrupo WHERE id_grupo = ". $downtime["id_grupo"]);

				$data[2] = ui_print_os_icon ($downtime["id_os"], true, true);

				$data[3] = $downtime["ultimo_contacto"];

				$data[4] = '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/planned_downtime&amp;id_agent='.
					$id_agent.'&amp;delete_downtime_agent=1&amp;first_update=1&amp;id_downtime_agent='.$downtime["id"].'&amp;id_downtime='.$id_downtime.'">' .
					html_print_image("images/cross.png", true, array("border" => '0', "alt" => __('Delete')));

				array_push ($table->data, $data);
			}
			html_print_table ($table);
		}
	}
}
else {

	// View available downtimes present in database (if any of them)
		$table->class = 'databox';
		//Start Overview of existing planned downtime
		$table->width = '98%';
		$table->data = array ();
		$table->head = array ();
		$table->head[0] = __('Name #Ag.');
		$table->head[1] = __('Description');
		$table->head[2] = __('Group');
		$table->head[3] = __('From');
		$table->head[4] = __('To');
		$table->head[5] = __('Affect');
		$table->head[6] = __('Delete');
		$table->head[7] = __('Update');
		$table->head[8] = __('Running');
		$table->head[9] = __('Stop downtime');
		$table->align[2] = "center";
		$table->align[5] = "center";
		$table->align[6] = "center";
		$table->align[7] = "center";
		$table->align[8] = "center";
		$table->align[9] = "center";

		if(!empty($groups)) {
			$sql = "SELECT * FROM tplanned_downtime WHERE id_group IN (" . implode (",", array_keys ($groups)) . ")";
			$downtimes = db_get_all_rows_sql ($sql);
		}
		else {
			$downtimes = array();
		}

		if (!$downtimes) { 	                
			echo '<div class="nf">'.__('No planned downtime').'</div>'; 	 
	    } 	 
	    else {
			foreach ($downtimes as $downtime) {
				$data = array();
				$total  = db_get_sql ("SELECT COUNT(id_agent) FROM tplanned_downtime_agents WHERE id_downtime = ".$downtime["id"]);

				$data[0] = $downtime['name']. " ($total)";
				$data[1] = $downtime['description'];
				$data[2] = ui_print_group_icon ($downtime['id_group'], true);
				$data[3] = date ("Y-m-d H:i", $downtime['date_from']);
				$data[4] = date ("Y-m-d H:i", $downtime['date_to']);
				if($downtime['only_alerts']) {
					$data[5] = __('Only alerts');
				}
				else {
					$data[5] = __('All');
				}
				if ($downtime["executed"] == 0){
					$data[6] = '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/planned_downtime&amp;id_agent='.
					$id_agent.'&amp;delete_downtime=1&amp;id_downtime='.$downtime['id'].'">' .
					html_print_image("images/cross.png", true, array("border" => '0', "alt" => __('Delete')));
					$data[7] = '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/planned_downtime&amp;edit_downtime=1&amp;first_update=1&amp;id_downtime='.$downtime['id'].'">' .
					html_print_image("images/config.png", true, array("border" => '0', "alt" => __('Update'))) . '</a>';
				}
				else {
					$data[6]= "N/A";
					$data[7]= "N/A";

				}
				if ($downtime["executed"] == 0)
					$data[8] = html_print_image ("images/pixel_green.png", true, array ('width' => 20, 'height' => 20, 'alt' => __('Executed')));
				else
					$data[8] = html_print_image ("images/pixel_red.png", true, array ('width' => 20, 'height' => 20, 'alt' => __('Not executed')));

				if ($downtime["executed"] != 0) {
					$data[9] = '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/planned_downtime&amp;stop_downtime=1&amp;id_downtime='.$downtime['id'].'">' .
					html_print_image("images/cancel.png", true, array("border" => '0', "alt" => __('Stop downtime')));
				}

				array_push ($table->data, $data);
			}
			html_print_table ($table);
		}
	echo '<div class="action-buttons" style="width: '.$table->width.'; margin-top: 5px;">';

	echo '<form method="post" action="index.php?sec=gagente&amp;sec2=godmode/agentes/planned_downtime">';
	html_print_input_hidden ("first_create", 1);
	html_print_submit_button (__('Create'), 'create', false, 'class="sub next"');
	echo '</form>';
	echo '</div>';
}

ui_require_css_file ('datepicker');
ui_require_jquery_file ('ui.core');
ui_require_jquery_file ('ui.datepicker');
ui_require_jquery_file ('timeentry');

?>
<script language="javascript" type="text/javascript">

$(document).ready (function () {
	$("#text-time_from, #text-time_to").timeEntry ({
		spinnerImage: 'images/time-entry.png',
		spinnerSize: [20, 20, 0]
		});
	$("#text-date_from, #text-date_to").datepicker ();
	$.datepicker.regional["<?php echo $config['language']; ?>"];


	$("#filter_group").click (
	function () {
		$(this).css ("width", "auto");
	});

	$("#filter_group").blur (function () {
		$(this).css ("width", "180px");
	});

	$("#id_agent").click (
	function () {
		$(this).css ("width", "auto");
	});

	$("#id_agent").blur (function () {
		$(this).css ("width", "180px");
	});

});
</script>
