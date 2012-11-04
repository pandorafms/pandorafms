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

// Header
ui_print_page_header(
	__("Planned Downtime") . ui_print_help_icon ('planned_downtime', true),
	"images/god1.png",
	false,
	"",
	true,
	"");

// Load global vars
global $config;

check_login();

//Initialize data
$id_agent = get_parameter ("id_agent");
$id_group = (int) get_parameter ("id_group", 0);
$name = (string) get_parameter ('name', '');
$description = (string) get_parameter ('description', '');
$once_date_from = (string) get_parameter ('once_date_from', date ('Y-m-j'));
$once_time_from = (string) get_parameter ('once_time_from', date ('h:iA'));
$once_date_to = (string) get_parameter ('once_date_to', date ('Y-m-j'));
$once_time_to = (string) get_parameter ('once_time_to', date ('h:iA'));
$periodically_day_from = (int) get_parameter ('periodically_day_from', 1);
$periodically_day_to = (int) get_parameter ('periodically_day_to', 31);
$periodically_time_from = (string) get_parameter ('periodically_time_from', date ('h:iA'));
$periodically_time_to = (string) get_parameter ('periodically_time_to', date ('h:iA'));

$first_create = (int) get_parameter ('first_create', 0);

$create_downtime = (int) get_parameter ('create_downtime');

$stop_downtime = (int) get_parameter ('stop_downtime');
$edit_downtime = (int) get_parameter ('edit_downtime');
$update_downtime = (int) get_parameter ('update_downtime');
$id_downtime = (int) get_parameter ('id_downtime',0);

$insert_downtime_agent = (int) get_parameter ("insert_downtime_agent", 0);
$delete_downtime_agent = (int) get_parameter ("delete_downtime_agent", 0);

$groups = users_get_groups ();

$type_downtime = get_parameter('type_downtime', 'quiet');
$type_execution = get_parameter('type_execution', 'once');
$type_periodicity = get_parameter('type_periodicity', 'weekly');

$monday = (bool) get_parameter ('monday');
$tuesday = (bool) get_parameter ('tuesday');
$wednesday = (bool) get_parameter ('wednesday');
$thursday = (bool) get_parameter ('thursday');
$friday = (bool) get_parameter ('friday');
$saturday = (bool) get_parameter ('saturday');
$sunday = (bool) get_parameter ('sunday');

// STOP DOWNTIME
if ($stop_downtime == 1) {
	$sql = "SELECT * FROM tplanned_downtime where id=$id_downtime";
	$result = db_get_row_sql($sql);
	$name = $result['name'];
	$description = $result['description'];
	$date_from = $result['date_from'];
	$executed = $result['executed'];
	$id_group = $result['id_group'];
	$only_alerts = $result['only_alerts'];
	$date_stop = date ("Y-m-j",get_system_time ());
	$time_stop = date ("h:iA",get_system_time ());
	$date_time_stop = strtotime ($date_stop . ' ' . $time_stop);
	
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
	$agents = (array)get_parameter("id_agents", array());
	$module_names = (array)get_parameter("module", array());
	$all_modules = false;
	if (empty($module_names))
		$all_modules = true;
	else {
		//It is empty.
		if ($module_names[0] == "0")
			$all_modules = true;
	}
	
	for ($a=0;$a <count($agents); $a++) {
		$id_agente_dt = $agents[$a];
		
		
		$values = array(
			'id_downtime' => $id_downtime,
			'id_agent' => $id_agente_dt,
			'all_modules' => $all_modules);
		
		$result = db_process_sql_insert('tplanned_downtime_agents', $values);
		if ($result && !$all_modules) {
			foreach ($module_names as $module_name) {
				$module =
					modules_get_agentmodule_id($module_name, $id_agente_dt);
				$values = array(
					'id_downtime' => $id_downtime,
					'id_agent' => $id_agente_dt,
					'id_agent_module' => $module["id_agente_modulo"]);
				$result = db_process_sql_insert('tplanned_downtime_modules', $values);
			}
		}
	}
}

// DELETE A DOWNTIME_AGENT ASSOCIATION
if ($delete_downtime_agent == 1) {
	
	$id_da = (int)get_parameter ("id_downtime_agent", 0);
	$id_agent_delete = (int)get_parameter('id_agent', 0);
	
	$row_to_delete = db_get_row('tplanned_downtime_agents', 'id', $id_da);
	
	$result = db_process_sql_delete('tplanned_downtime_agents',
		array('id' => $id_da));
	
	if ($result) {
		//Delete modules in downtime
		db_process_sql_delete('tplanned_downtime_modules',
			array('id_downtime' => $row_to_delete['id_downtime'],
				'id_agent' => $id_agent_delete));
	}
}

// UPDATE OR CREATE A DOWNTIME (MAIN DATA, NOT AGENT ASSOCIATION)
if ($create_downtime || $update_downtime) {
	$check = db_get_value ('name', 'tplanned_downtime', 'name', $name);
	
	$datetime_from = strtotime ($once_date_from.' '.$once_time_from);
	$datetime_to = strtotime ($once_date_to.' '.$once_time_to);
	
	if ($datetime_from > $datetime_to) {
		echo '<h3 class="error">'.__('Not created. Error inserting data').': START &gt; END</h3>';
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
						'executed' => 0,
						'id_group' => $id_group,
						'only_alerts' => 0,
						'monday' => $monday,
						'tuesday' => $tuesday,
						'wednesday' => $wednesday,
						'thursday' => $thursday,
						'friday' => $friday,
						'saturday' => $saturday,
						'sunday' => $sunday,
						'periodically_time_from' => $periodically_time_from,
						'periodically_time_to' => $periodically_time_to,
						'periodically_day_from' => $periodically_day_from,
						'periodically_day_to' => $periodically_day_to,
						'type_downtime' => $type_downtime,
						'type_execution' => $type_execution,
						'type_periodicity' => $type_periodicity
						);
					$result = db_process_sql_insert(
						'tplanned_downtime', $values);
				}
				else {
					echo "<h3 class='error'>" .
						__('Each planned downtime must have a different name')."</h3>";
				}
			}
			else {
				echo '<h3 class="error">' .
					__('Planned downtime must have a name').'</h3>';
			}
		}
		else if ($update_downtime) {
			if (trim(io_safe_output($name)) != '') {
				$values = array(
					'name' => $name,
					'description' => $description,
					'date_from' => $datetime_from,
					'date_to' => $datetime_to,
					'executed' => 0,
					'id_group' => $id_group,
					'only_alerts' => 0,
					'monday' => $monday,
					'tuesday' => $tuesday,
					'wednesday' => $wednesday,
					'thursday' => $thursday,
					'friday' => $friday,
					'saturday' => $saturday,
					'sunday' => $sunday,
					'periodically_time_from' => $periodically_time_from,
					'periodically_time_to' => $periodically_time_to,
					'periodically_day_from' => $periodically_day_from,
					'periodically_day_to' => $periodically_day_to,
					'type_downtime' => $type_downtime,
					'type_execution' => $type_execution,
					'type_periodicity' => $type_periodicity
					);
				$result = db_process_sql_update('tplanned_downtime', $values, array('id' => $id_downtime));
			}
			else {
				echo '<h3 class="error">'.__('Planned downtime must have a name').'</h3>';
			}
		}
		
		if ($result === false) {
			if ($create_downtime) {
				echo '<h3 class="error">'.__('Could not be created').'</h3>';
			}
			else {
				echo '<h3 class="error">'.__('Could not be updated').'</h3>';
			}
		}
		else {
			if ($create_downtime && $name && !$check) {
				$id_downtime = $result;
				echo '<h3 class="suc">' . __('Successfully created') . '</h3>';
			}
			else if ($update_downtime && $name) {
				echo '<h3 class="suc">'.__('Successfully updated').'</h3>';
			}
		}
	}
}

// Have any data to show ?
if ($id_downtime > 0) {
	switch ($config["dbtype"]) {
		case "mysql":
			$sql = sprintf ("SELECT *
				FROM `tplanned_downtime` WHERE `id` = %d",
				$id_downtime);
			break;
		case "postgresql":
			$sql = sprintf ("SELECT *
				FROM \"tplanned_downtime\" WHERE \"id\" = %d",
				$id_downtime);
			break;
		case "oracle":
			$sql = sprintf ("SELECT *
				FROM tplanned_downtime WHERE id = %d",
				$id_downtime);
			break;
	}
	
	$result = db_get_row_sql ($sql);
	$name = $result["name"];
	$description = $result["description"];
	$once_date_from = strftime ('%Y-%m-%d', $result["date_from"]);
	$once_date_to = strftime ('%Y-%m-%d', $result["date_to"]);
	$once_time_from = strftime ('%I:%M%p', $result["date_from"]);
	$once_time_to = strftime ('%I:%M%p', $result["date_to"]);
	$monday = $result['monday'];
	$tuesday = $result['tuesday'];
	$wednesday = $result['wednesday'];
	$thursday = $result['thursday'];
	$friday = $result['friday'];
	$saturday = $result['saturday'];
	$sunday = $result['sunday'];
	$periodically_time_from = $result['periodically_time_from'];
	$periodically_time_to = $result['periodically_time_to'];
	$periodically_day_from = $result['periodically_day_from'];
	$periodically_day_to = $result['periodically_day_to'];
	$type_downtime = $result['type_downtime'];
	$type_execution = $result['type_execution'];
	$type_periodicity = $result['type_periodicity'];
	
	if ($id_group == 0)
		$id_group = $result['id_group'];
}

$table->class = 'databox_color';
$table->width = '98%';
$table->data = array ();
$table->data[0][0] = __('Name');
$table->data[0][1] = html_print_input_text ('name', $name, '', 25, 40, true);
$table->data[1][0] = __('Group');
$table->data[1][1] = html_print_select_groups(false, "AR", true, 'id_group', $id_group, '', '', 0, true);
$table->data[2][0] = __('Description');
$table->data[2][1] = html_print_textarea ('description', 3, 35, $description, '', true);
$disabled_type = false;

if ($id_downtime > 0) {
	$disabled_type = true;
}
$table->data[3][0] = __('Type');
$table->data[3][1] = html_print_select(array('quiet' => __('Quiet'),
	'disable_agents' => __('Disabled Agents'),
	'disable_agents_alerts' => __('Disabled only Alerts')),
	'type_downtime', $type_downtime, '', '', 0, true, false, true,
	'', $disabled_type);
$table->data[4][0] = __('Execution');
$table->data[4][1] = html_print_select(array('once' => __('once'),
	'periodically' => __('Periodically')),
	'type_execution', $type_execution, 'change_type_execution();', '', 0, true);

$days = array_combine(range(1, 31), range(1, 31));
$table->data[5][0] = __('Configure the time');
$table->data[5][1] = "
	<div id='once_time' style='display: none;'>
		<table>
			<tr>
				<td>" . __('From:') . "</td>
				<td>".
				html_print_input_text ('once_date_from', $once_date_from, '', 10, 10, true) .
				html_print_input_text ('once_time_from', $once_time_from, '', 7, 7, true) . 
				"</td>
			</tr>
			<tr>
				<td>" . __('To:') . "</td>
				<td>".
				html_print_input_text ('once_date_to', $once_date_to, '', 10, 10, true) .
				html_print_input_text ('once_time_to', $once_time_to, '', 7, 7, true) . 
				"</td>
			</tr>
		</table>
	</div>
	<div id='periodically_time' style='display: none;'>
		<table>
			<tr>
				<td>" . __('Type Periodicity:') . "</td>
				<td>".
					html_print_select(array(
						'weekly' => __('Weekly'),
						'monthly' => __('Monthly')),
					'type_periodicity', $type_periodicity, 'change_type_periodicity();', '', 0, true) .
				"</td>
			</tr>
			<tr>
				<td colspan='2'>
					<table id='weekly_item' style='display: none;'>
						<tr>
							<td>" . __('Mon') .
							html_print_checkbox ('monday', 1, $monday, true) .
							"</td>
							<td>" . __('Tue') .
							html_print_checkbox ('tuesday', 1, $tuesday, true) .
							"</td>
							<td>" . __('Wed') .
							html_print_checkbox ('wednesday', 1, $wednesday, true) .
							"</td>
							<td>" . __('Thu') .
							html_print_checkbox ('thursday', 1, $thursday, true) .
							"</td>
							<td>" . __('Fri') .
							html_print_checkbox ('friday', 1, $friday, true) .
							"</td>
							<td>" . __('Sat') .
							html_print_checkbox ('saturday', 1, $saturday, true) .
							"</td>
							<td>" . __('Sun') .
							html_print_checkbox ('sunday', 1, $sunday, true) .
							"</td>
						</tr>
					</table>
					<table id='monthly_item' style='display: none;'>
						<tr>
							<td>" . __('From day:') . "</td>
							<td>".
								html_print_select($days,
								'periodically_day_from', $periodically_day_from, '', '', 0, true) .
							"</td>
							<td>" . __('To day:') . "</td>
							<td>".
								html_print_select($days,
								'periodically_day_to', $periodically_day_to, '', '', 0, true) .
							"</td>
						</tr>
					</table>
					<table>
						<tr>
							<td>" . __('From hour:') . "</td>
							<td>".
							html_print_input_text (
								'periodically_time_from',
								$periodically_time_from, '', 7, 7, true) . 
							"</td>
							<td>" . __('To hour:') . "</td>
							<td>".
							html_print_input_text (
								'periodically_time_to',
								$periodically_time_to, '', 7, 7, true) . 
							"</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</div>";

echo '<form method="POST" action="index.php?sec=gagente&amp;sec2=godmode/agentes/planned_downtime.editor">';

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
	echo '<h4>' . __('Available agents') . ':</h4>';
	
	$filter_group = get_parameter("filter_group", 0);
	
	$filter_cond = '';
	if($filter_group > 0)
		$filter_cond = " AND id_grupo = $filter_group ";
	$sql = sprintf ("SELECT tagente.id_agente, tagente.nombre,
			tagente.id_grupo
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
	
	echo "<form method=post action='index.php?sec=gagente&sec2=godmode/agentes/planned_downtime.editor&id_downtime=$id_downtime'>";
	
	html_print_select_groups(false, "AR", true, 'filter_group', $filter_group, '', '', '', false, false, true, '', false, 'width:180px');
	
	echo "<br /><br />";
	html_print_submit_button (__('Filter by group'), '', false, 'class="sub next"',false);
	echo "</form>";
	
	echo "<form method=post action='index.php?sec=gagente&sec2=godmode/agentes/planned_downtime.editor&insert_downtime_agent=1&id_downtime=$id_downtime'>";
	
	echo html_print_select ($data, "id_agents[]", '', '', '', 0, false, true, true, '', false, 'width: 180px;');
	echo '<h4>' . __('Available modules:') . 
		ui_print_help_tip (__('Only for type Quiet for downtimes.'), true) . '</h4>';
	
	if ($type_downtime != 'quiet')
		echo '<div style="display: none;">';
	echo html_print_select (array(), "module[]", '', '', '', 0, false, true, true, '', false, 'width: 180px;');
	echo "</div>";
	echo "<br /><br /><br />";
	html_print_submit_button (__('Add'), '', $disabled_add_button, 'class="sub next"',false);
	echo "</form>";
	echo "</table>";
	
	//Start Overview of existing planned downtime
	echo '<h4>'.__('Agents planned for this downtime').':</h4>';
	
	$sql = sprintf ("SELECT tagente.nombre, tplanned_downtime_agents.id,
			tagente.id_os, tagente.id_agente, tagente.id_grupo,
			tagente.ultimo_contacto, tplanned_downtime_agents.all_modules
		FROM tagente, tplanned_downtime_agents
		WHERE tplanned_downtime_agents.id_agent = tagente.id_agente
			AND tplanned_downtime_agents.id_downtime = %d ",$id_downtime);
	
	$downtimes = db_get_all_rows_sql ($sql);
	if ($downtimes === false) {
		echo '<div class="nf">'. __('There are no scheduled downtimes').'</div>';
	}
	else {
		$table->id = 'list';
		$table->class = 'databox';
		$table->width = '98%';
		$table->data = array ();
		$table->head = array ();
		$table->head[0] = __('Name');
		$table->head[1] = __('Group');
		$table->head[2] = __('OS');
		$table->head[3] = __('Last contact');
		$table->head['count_modules'] = __('Modules');
		$table->head[5] = __('Actions');
		$table->align[5] = "center";
		$table->size[5] = "5%";
		
		foreach ($downtimes as $downtime) {
			$data = array ();
			
			$data[0] = $downtime['nombre'];
			
			$data[1] = db_get_sql ("SELECT nombre
				FROM tgrupo
				WHERE id_grupo = ". $downtime["id_grupo"]);
			
			$data[2] = ui_print_os_icon ($downtime["id_os"], true, true);
			
			$data[3] = $downtime["ultimo_contacto"];
			
			if ($downtime["all_modules"]) {
				$data['count_modules'] = __("All modules");
			}
			else {
				$data['count_modules'] = __("Some modules");
			}
			
			$data[5] = '<a href="javascript:show_editor_module(' . $downtime["id_agente"] . ');">' .
				html_print_image("images/config.png", true, array("border" => '0', "alt" => __('Delete'))) . "</a>";
			$data[5] .= '<a href="index.php?sec=gagente&amp;sec2=godmode/agentes/planned_downtime.editor'.
				'&amp;id_agent=' . $downtime["id_agente"] . 
				'&amp;delete_downtime_agent=1&amp;id_downtime_agent='.$downtime["id"].'&amp;id_downtime='.$id_downtime.'">' .
				html_print_image("images/cross.png", true, array("border" => '0', "alt" => __('Delete'))) . "</a>";
			
			$table->data['agent_' . $downtime["id_agente"]] = $data;
		}
		html_print_table ($table);
	}
}

$table = null;
$table->id = 'loading';
$table->colspan['loading'][0] = '6';
$table->style[0] = 'text-align: center;';
$table->data = array();
$table->data['loading'] = array();
$table->data['loading'][0] = html_print_image("images/spinner.gif", true);
echo "<div style='display: none;'>";
html_print_table ($table);
echo "</div>";

$table = null;
$table->id = 'editor';
$table->colspan['module'][1] = '5';
$table->data = array();
$table->data['module'] = array();
$table->data['module'][0] = '';
$table->data['module'][1] = "<h4>" . __('Modules') . "</h4>";

//List of modules, empty, it is populated by javascript.
$table->data['module'][1] = "
	<table cellspacing='4' cellpadding='4' border='0' width='98%'
		id='modules_in_agent' class='databox_color'>
		<thead>
			<tr>
				<th scope='col' class='header c0'>" . __('Module') . "</th>
				<th scope='col' class='header c1'>" . __('Action') . "</th>
			</tr>
		</thead>
		<tbody>
			<tr class='datos' id='template' style='display: none;'>
				<td class='name_module' style=''></td>
				<td class='cell_delete_button' style='text-align: center; width:10%;' id=''>"
					. '<a class="link_delete"
						onclick="if(!confirm(\'' . __('Are you sure?') . '\')) return false;"
						href="">'
					. html_print_image("images/cross.png", true,
							array("border" => '0', "alt" => __('Delete'))) . "</a>"
				. "</td>
			</tr>
			<tr class='datos2' id='add_modules_row'>
				<td class='datos2' style='' id=''>"
					. __("Add Module:") . '&nbsp;'
					. html_print_select(array(),
						'modules', '', '', '', 0, true)
				. "</td>
				<td class='datos2 button_cell' style='text-align: center; width:10%;' id=''>"
					. '<div id="add_button_div">'
					. '<a class="add_button" href="">'
					. html_print_image("images/add.png", true,
							array("border" => '0', "alt" => __('Add'))) . "</a>"
					. '</div>'
					. "<div id='spinner_add' style='display: none;'>"
					. html_print_image("images/spinner.gif", true)
					. "</div>"
				. "</td>
			</tr>
		</tbody></table>";

echo "<div style='display: none;'>";
html_print_table ($table);
echo "</div>";

echo "<div style='display: none;'>";
echo "<div id='spinner_template'>";
html_print_image("images/spinner.gif");
echo "</div>";
echo "</div>";

echo "<div id='some_modules_text' style='display: none;'>";
echo __('Some modules');
echo "</div>";

echo "<div id='some_modules_text' style='display: none;'>";
echo __('Some modules');
echo "</div>";

echo "<div id='all_modules_text' style='display: none;'>";
echo __("All modules");
echo "</div>";

ui_require_jquery_file ("ui-timepicker-addon");

?>
<script language="javascript" type="text/javascript">
	var id_downtime = <?php echo $id_downtime?>;
	var action_in_progress = false;
	
	function change_type_execution() {
		switch ($("#type_execution").val()) {
			case 'once':
				$("#periodically_time").hide();
				$("#once_time").show();
				break;
			case 'periodically':
				$("#once_time").hide();
				$("#periodically_time").show();
				break;
		}
	}
	
	function change_type_periodicity() {
		switch ($("#type_periodicity").val()) {
			case 'weekly':
				$("#monthly_item").hide();
				$("#weekly_item").show();
				break;
			case 'monthly':
				$("#weekly_item").hide();
				$("#monthly_item").show();
				break;
		}
	}
	
	function show_editor_module(id_agent) {
		//Avoid freak states.
		if (action_in_progress)
			return;
		
		//Check if the row editor module exists 
		if ($('#loading_' + id_agent).length > 0) {
			//The row exists
			$('#loading_' + id_agent).remove();
		}
		else {
			if ($('#module_editor_' + id_agent).length == 0) {
				$("#list-agent_" + id_agent).after(
					$("#loading-loading").clone().attr('id', 'loading_' + id_agent));
				
				jQuery.post ('ajax.php', 
					{"page": "include/ajax/planned_downtime.ajax",
					"get_modules_downtime": 1,
					"id_agent": id_agent,
					"id_downtime": id_downtime
					},
					function (data) {
						if (data['correct']) {
							//Check if the row editor module exists 
							if ($('#loading_' + id_agent).length > 0) {
								//The row exists
								$('#loading_' + id_agent).remove();
								
								$("#list-agent_" + id_agent).after(
									$("#editor-module").clone()
										.attr('id', 'module_editor_' + id_agent)
										.hide());
								
								fill_row_editor(id_agent, data);
							}
						}
					},
					"json"
				);
			}
			else {
				if ($('#module_editor_' + id_agent).is(':visible')) {
					$('#module_editor_' + id_agent).hide();
				}
				else {
					$('#module_editor_' + id_agent).css('display', '');
				}
			}
		}
	}
	
	function fill_row_editor(id_agent, data) {
		//$("#modules", $('#module_editor_' + id_agent)).empty();
		
		//Fill the select for to add modules
		$.each(data['in_agent'], function(id_module, name) {
			$("#modules", $('#module_editor_' + id_agent))
				.append($("<option value='" + id_module + "'>" + name + "</option>"));
		});
		$(".add_button", $('#module_editor_' + id_agent)).
			attr('href', 'javascript:' +
				'add_module_in_downtime(' + id_agent + ')');
		
		
		//Fill the list of modules
		$.each(data['in_downtime'], function(id_module, name) {
			var template_row = $("#template").clone();
			
			$(template_row).css('display', '');
			$(template_row).attr('id', 'row_module_in_downtime_' + id_module);
			$(".name_module", template_row).html(name);
			$(".link_delete", template_row).attr('href',
				'javascript:' +
				'delete_module_from_downtime(' + id_downtime + ',' + id_module + ');');
			
			$("#add_modules_row", $('#module_editor_' + id_agent))
				.before(template_row);
		});
		
		//.show() is crap, because put a 'display: block;'.
		$('#module_editor_' + id_agent).css('display', '');
	}
	
	function add_row_module(id_downtime, id_agent, id_module, name) {
		var template_row = $("#template").clone();
		
		$(template_row).css('display', '');
		$(template_row).attr('id', 'row_module_in_downtime_' + id_module);
		$(".name_module", template_row).html(name);
		$(".link_delete", template_row).attr('href',
			'javascript:' +
			'delete_module_from_downtime(' + id_downtime + ',' + id_module + ');');
		
		$("#add_modules_row", $('#module_editor_' + id_agent))
			.before(template_row);
		
	}
	
	function fill_selectbox_modules(id_downtime, id_agent) {
		jQuery.post ('ajax.php', 
			{"page": "include/ajax/planned_downtime.ajax",
				"get_modules_downtime": 1,
				"id_agent": id_agent,
				"id_downtime": id_downtime,
				"none_value": 1
			},
			function (data) {
				if (data['correct']) {
					$("#modules", $('#module_editor_' + id_agent)).empty();
					
					//Fill the select for to add modules
					$.each(data['in_agent'], function(id_module, name) {
						$("#modules", $('#module_editor_' + id_agent))
							.append($("<option value='" + id_module + "'>" + name + "</option>"));
					});
					
					$("#modules", $('#module_editor_' + id_agent)).val(0);
				}
			},
			"json"
		);
	}
	
	function add_module_in_downtime(id_agent) {
		var module_sel = $("#modules", $('#module_editor_' + id_agent)).val();
		
		if (module_sel == 0) {
			alert('<?php echo __("Please select a module."); ?>');
		}
		else {
			action_in_progress = true;
			
			$("#add_button_div", $('#module_editor_' + id_agent)).toggle();
			$("#spinner_add", $('#module_editor_' + id_agent)).toggle();
			
			jQuery.post ('ajax.php', 
				{"page": "include/ajax/planned_downtime.ajax",
					"add_module_into_downtime": 1,
					"id_agent": id_agent,
					"id_module": module_sel,
					"id_downtime": id_downtime
				},
				function (data) {
					if (data['correct']) {
						$("#list-agent_"
							+ id_agent
							+ '-count_modules').html(
								$("#some_modules_text").html());
						
						add_row_module(id_downtime, id_agent,
							module_sel, data['name']);
						fill_selectbox_modules(id_downtime, id_agent);
						
						
						$("#add_button_div", $('#module_editor_' + id_agent))
							.toggle();
						$("#spinner_add", $('#module_editor_' + id_agent))
							.toggle();
					}
					
					action_in_progress = false;
				},
				"json"
			);
		}
	}
	
	function delete_module_from_downtime(id_downtime, id_module) {
		var spinner = $("#spinner_template").clone();
		var old_cell_content =
			$(".cell_delete_button", "#row_module_in_downtime_" + id_module)
			.clone(true);
		
		$(".cell_delete_button", "#row_module_in_downtime_" + id_module)
			.html(spinner);
		
		action_in_progress = true;
		
		jQuery.post ('ajax.php', 
			{"page": "include/ajax/planned_downtime.ajax",
			"delete_module_from_downtime": 1,
			"id_downtime": id_downtime,
			"id_module": id_module
			},
			function (data) {
				if (data['correct']) {
					fill_selectbox_modules(id_downtime, data['id_agent']);
					
					$("#row_module_in_downtime_" + id_module).remove();
					
					if (data['all_modules']) {
						$("#list-agent_"
							+ data['id_agent']
							+ '-count_modules').html(
								$("#all_modules_text").html());
					}
				}
				else {
					$(".cell_delete_button", "#row_module_in_downtime_" + id_module)
						.html($(old_cell_content));
				}
				
				action_in_progress = false;
			},
			"json"
		);
	}
	
	$(document).ready (function () {
		$("#id_agents").change(agent_changed_by_multiple_agents);
		
		change_type_execution();
		change_type_periodicity();
		
		$("#text-periodically_time_from, #text-periodically_time_to, #text-once_time_from, #text-once_time_to").timepicker({
			showSecond: true,
			timeFormat: 'hh:mm:ss',
			timeOnlyTitle: '<?php echo __('Choose time');?>',
			timeText: '<?php echo __('Time');?>',
			hourText: '<?php echo __('Hour');?>',
			minuteText: '<?php echo __('Minute');?>',
			secondText: '<?php echo __('Second');?>',
			currentText: '<?php echo __('Now');?>',
			closeText: '<?php echo __('Close');?>'});
		$("#text-once_date_from, #text-once_date_to").datepicker ();
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
