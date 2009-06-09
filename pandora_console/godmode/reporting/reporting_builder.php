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


// Login check
require_once ("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "AW")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access report builder");
	require ("general/noaccess.php");
	exit;
}

require_once ("include/functions_reports.php");

if (is_ajax ()) {
	$get_report_type_data_source = (bool) get_parameter ('get_report_type_data_source');
	
	if ($get_report_type_data_source) {
		$id_report_type = (string) get_parameter ('id_report_type');
		
		echo get_report_type_data_source ($id_report_type);
		return;
	}
	return;
}

$edit_report = (bool) get_parameter ('edit_report');
$create_report = (bool) get_parameter ('create_report');
$delete_report = (int) get_parameter ('delete_report');
$update_report = (int) get_parameter ('update_report');
$delete_report_content = (bool) get_parameter ('delete_report_content');
$report_name = (string) get_parameter ('report_name');
$report_description = (string) get_parameter ('report_description');
$report_private = (bool) get_parameter ('report_private', 0);
$id_report = (int) get_parameter ('id_report');
$report_id_group = (int) get_parameter ('report_id_group', get_user_first_group ());
$id_agent = (int) get_parameter ('id_agent');
$id_group = (int) get_parameter ('id_group');
$add_content = (bool) get_parameter ('add_content');
$edit_sla_report_content = (bool) get_parameter ('edit_sla_report_content');
$content_up = (bool) get_parameter ('content_up');
$content_down = (bool) get_parameter ('content_down');

// Delete module SQL code
if ($delete_report_content) {
	$id_report_content = (int) get_parameter ('id_report_content');
	
	$result = delete_report_content ($id_report_content);
	print_result_message ($result,
		__('Successfully deleted'),
		__('Could not be deleted'));
}

// Move content up
if ($content_up) {
	$id_report_content = (int) get_parameter ('id_report_content');
	move_report_content_up ($id_report_content, $id_report);
}

// Move content down
if ($content_down) {
	$id_report_content = (int) get_parameter ('id_report_content');
	move_report_content_down ($id_report_content, $id_report);
}

// Delete report SQL code
if ($delete_report) {
	$result = delete_report ($id_report);
	print_result_message ($result,
		__('Successfully deleted'),
		__('Could not be deleted'));
	$id_report = 0;
}

// Add module SQL code
if ($add_content) {
	if (! $id_report) {
		audit_db ($config['id_user'], $REMOTE_ADDR, "Hack attempt", "Parameter trash in report builder");
		require ("general/noaccess.php");
		exit ();
	}
	$id_agent_module = (int) get_parameter ('id_module');
	$period = (int) get_parameter ('period');
	$type = (string) get_parameter ('type');
	$id_agent = (int) get_parameter ('id_agent');
	$id_custom_graph = (int) get_parameter ('id_custom_graph');
	$module_description = (string) get_parameter ('module_description');
	
	$values = array ();
	$values['id_gs'] = $id_custom_graph ? $id_custom_graph : NULL;
	$values['id_agent_module'] = $id_agent_module ? $id_agent_module : NULL;
	$values['type'] = $type;
	$values['period'] = $period * 3600;
	$values['description'] = $module_description;
	$values['id_agent'] = $id_agent;
	
	$result = create_report_content ($id_report, $values);
	if ($result !== false) {
		echo '<h3 class="suc">'.__('Reporting successfully created').'</h3>';
		$id_agent = 0;
		$id_agent_module = 0;
		$report_id_group = 1;
		$period = 0;
		$type = 0;
		$id_custom_graph = 0;
		$sla_max = 0;
		$sla_min = 0;
		$sla_limit = 0;
		$module_description = "";
	} else {
		echo '<h3 class="error">'.__('There was a problem creating reporting')."</h3>";
		/* Do not unset so the values are kept in the form */
	}
}

// Create report
if ($create_report) {
	$values = array ();
	$values['description'] = $report_description;
	$values['private'] = $report_private;
	$id_report = create_report ($report_name, $report_id_group, $values);
	print_result_message ($id_report,
		__('Successfully created'),
		__('Could not be created'));
}

// Update report
if ($update_report) {
	$values = array ();
	$values['name'] = $report_name;
	$values['description'] = $report_description;
	$values['private'] = $report_private;
	$values['id_group'] = $report_id_group;
	
	$result = update_report ($id_report, $values);
	print_result_message ($result,
		__('Successfully updated'),
		__('Could not be updated'));
}

if ($id_report) {
	$report = get_report ($id_report);
	if ($report === false)
		require ("general/noaccess.php");
	$report_name = $report["name"];
	$report_description = $report["description"];
	$report_private = $report["private"];
	$report_id_user = $report["id_user"];
	$report_id_group = $report['id_group'];
}

$agents = get_group_agents ($report_id_group);

if ($edit_sla_report_content) {
	/* Edit SLA report form */
	$add_sla = (bool) get_parameter ('add_sla');
	$delete_sla = (bool) get_parameter ('delete_sla');
	$id_report_content = (int) get_parameter ('id_report_content');
	$id_module = (int) get_parameter ('id_module');
	
	$sla_max = '';
	$sla_min = '';
	$sla_limit = '';
	if ($add_sla) {
		$sla_max = (float) get_parameter ('sla_max');
		$sla_min = (float) get_parameter ('sla_min');
		$sla_limit = (float) get_parameter ('sla_limit');
		
		if ($id_module) {
			$result = process_sql_insert ('treport_content_sla_combined',
				array ('id_report_content' => $id_report_content,
					'id_agent_module' => $id_module,
					'sla_max' => $sla_max,
					'sla_min' => $sla_min,
					'sla_limit' => $sla_limit));
			
			if ($result !== false) {
				echo "<h3 class=suc>".__('SLA was successfully created')."</h3>";
			} else {
				echo "<h3 class=error>".__('There was a problem creating SLA')."</h3>";
			}
		} else {
			echo "<h3 class=error>".__('Module is not set')."</h3>";
		}
	}
	if ($delete_sla) {
		$id_sla = (int) get_parameter ('id_sla');
		$sql = sprintf ('DELETE FROM treport_content_sla_combined WHERE id = %d', $id_sla);
		$result = process_sql ($sql);
		if ($result !== false) {
			echo "<h3 class=suc>".__('SLA was successfully delete')."</h3>";
		} else { 
			echo "<h3 class=error>".__('There was a problem deleting SLA')."</h3>";
		}
	}
	$report_content = get_db_row ('treport_content', 'id_rc', $id_report_content);

	/* Show all SLAs defined in the report content */	
	echo '<h2>'.__('Reporting')." &raquo; ";
	echo __('Custom reporting builder')." &raquo; ";
	echo '<a href="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&edit_report=1&id_report='.
		$id_report.'">'.$report['name'].'</a></h2>';
	echo '<h2>'.__('SLAs defined')."</h2>";

	$table->id = 'table-sla-list';
	$table->width = '500px';
	$table->align = array ();
	$table->align[5] = 'center';
	$table->data = array ();
	$table->head = array ();
	$table->head[0] = __('Agent');
	$table->head[1] = __('Module');
	$table->head[2] = __('SLA Min. (value)');
	$table->head[3] = __('SLA Max. (value)');
	$table->head[4] = __('SLA Limit (%)');
	$table->head[5] = __('Delete');

	$slas = get_db_all_rows_field_filter ('treport_content_sla_combined', 'id_report_content', $id_report_content);
	if ($slas) {
		foreach ($slas as $sla) {
			$data = array ();
			
			$data[0] = get_agentmodule_agent_name ($sla['id_agent_module']);
			$data[1] = get_agentmodule_name ($sla['id_agent_module']);
			$data[2] = $sla['sla_min'];
			$data[3] = $sla['sla_max'];
			$data[4] = $sla['sla_limit'].'%';
			$data[5] = '<a href="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&id_report='.
					$id_report.'&edit_sla_report_content=1&delete_sla=1&id_report_content='.
					$id_report_content.'&id_sla='.$sla['id'].'"><img src="images/cross.png"></a>';
			
			array_push ($table->data, $data);
		}
	}
	
	if (sizeof ($slas)) {
		print_table ($table);
	}

	echo "<h2>".__('SLA report construction')."</h2>";
	$table->id = 'table-add-sla';
	$table->width = '500px';
	$table->data = array ();
	$table->style = array ();
	$table->size = array ();
	$table->size[0] = '150px';
	$table->head = array ();
	$table->style[0] = 'font-weight: bold';
	$table->data[0][0] = __('Agent');
	$table->data[0][1] = print_select ($agents, 'id_agent', 0, '', '--', 0, true);
	$table->data[1][0] = __('Module');
	$table->data[1][1] = print_select (array (), 'id_module', 0, '', '--', 0, true);
	$table->data[2][0] = __('SLA Min. (value)');
	$table->data[2][1] = print_input_text ('sla_min', $sla_min, '', 5, 10, true);
	$table->data[3][0] = __('SLA Max. (value)');
	$table->data[3][1] = print_input_text ('sla_max', $sla_max, '', 5, 10, true);
	$table->data[4][0] = __('SLA Limit (%)');
	$table->data[4][1] = print_input_text ('sla_limit', $sla_limit, '', 5, 10, true);
	
	echo '<form method="post" action="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&id_report='.
		$id_report.'&edit_sla_report_content=1&id_report_content='.$id_report_content.'">';
	print_table ($table);

	print_input_hidden ('add_sla', 1);
	print_input_hidden ('edit_sla_report_content', 1);
	print_input_hidden ('id_report_content', $id_report_content);

	echo '<div class="action-buttons" style="width: 500px;">';
	print_submit_button (__('Create'), 'add', false, 'class="sub wand"');
	echo '</div>';

	echo '</form>';
	
} elseif ($edit_report || $id_report) {
	 /* Edit and creation report form */
	$id_agent = (int) get_parameter ("id_agent");
	echo "<h2>".__('Reporting')." &raquo; ";
	echo __('Custom reporting builder');
	
	echo " <a href='index.php?sec=reporting&sec2=operation/reporting/reporting_viewer&id=".$id_report."'><img src='images/reporting.png'></A>";
	echo "</h2>";
	
	$table->id = 'table-edit-report';
	$table->width = '500px';
	$table->data = array ();
	$table->style = array ();
	$table->style[0] = 'font-weight: bold';
	$table->data[0][0] = __('Report name');
	$table->data[0][1] = print_input_text ('report_name', $report_name, '', 35, 150, true);
	
	$table->data[0][1] .= "&nbsp;&nbsp;<a href='index.php?sec=reporting&sec2=operation/reporting/reporting_viewer&id=$id_report' title='".__('View report')."'><img src='images/reporting.png'></a>";
	
	$table->data[1][0] = __('Group');
	
	$group_select = get_user_groups ($config['id_user']);
	$table->data[1][1] = print_select ($group_select, 'report_id_group', $report_id_group, '', '', '', true);
	$table->data[1][1] .= ' <span id="group_preview">';
	$table->data[1][1] .= print_group_icon ($report_id_group, true);
	$table->data[1][1] .= '</span>';
	
	$table->data[2][0] = __('Private');
	$table->data[2][1] = print_checkbox ('report_private', 1, $report_private, true);
	$table->data[3][0] = __('Description');
	$table->data[3][1] = print_textarea ('report_description', 3, 40, $report_description, '', true);

	echo "<form method='post' action='index.php?sec=greporting&sec2=godmode/reporting/reporting_builder'>";
	print_table ($table);
	// Button
	echo '<div class="action-buttons" style="width: 500px;">';
	print_input_hidden ('edit_report', 1);
	if ($id_report) {
		print_input_hidden ('id_report', $id_report);
		print_input_hidden ('update_report', 1);
		print_submit_button (__('Update'), 'submit', false, 'class="sub upd"');
	} else {
		print_input_hidden ('create_report', 1);
		print_submit_button (__('Create'), 'submit', false, 'class="sub wand"');
	}
	echo "</div>";
	echo "</form>";
	
	if ($id_report) {
		// Part 2 - Add new items to report
		echo "<h2>".__('Add item to report')."</h2>";
		
		/* We're reusing some $table attributes */
		$table->id = 'table-add-item';
		$table->rowstyle = array ();
		$table->rowstyle[2] = 'display: none';
		$table->rowstyle[3] = 'display: none';
		$table->rowstyle[4] = 'display: none';
		$table->size = array ();
		$table->size[0] = '200px';
		$table->data = array ();

		$table->data[0][0] = __('Reporting type');
		$types = get_report_types ();
		asort ($types);
		$table->data[0][1] = print_select ($types, 'type', -1, '', '--', -1, true);
		$table->data[1][0] = __('Period');
		$periods = array ();
		$periods[1] = __('1 hour');
		$periods[2] = '2 '.__('hours');
		$periods[3] = '3 '.__('hours');
		$periods[6] = '6 '.__('hours');
		$periods[12] = '12 '.__('hours');
		$periods[24] = __('1 day');
		$periods[48] = __('2 days');
		$periods[168] = __('1 week');
		$periods[360] = __('2 weeks');
		$periods[720] = __('1 month');
		$periods[2160] = __('3 months');
		$periods[4320] = __('6 months');
		$table->data[1][1] = print_select ($periods, 'period', 0, '', '--', 0, true, false, false, false);

		$table->data[2][0] = __('Source agent');
		$table->data[2][1] = print_select ($agents, 'id_agent', $id_agent, '', '--', 0, true);
		
		$table->data[3][0] = __('Module');
		$modules = array ();
		if ($id_agent) {
			$modules = get_db_all_rows_filter ('tagente_modulo',
				array ('id_agente' => $id_agent, 'order' => 'nombre'),
				array ('id_agente_modulo', 'nombre'));
		}
		$table->data[3][1] = print_select ($modules, 'id_module', 0, '', '--', 0, true);
		
		$table->data[4][0] = __('Custom graph name');
		$table->data[4][1] = print_select_from_sql ('SELECT id_graph, name FROM tgraph WHERE private = 0 OR (private = 1 AND id_user = "'.$config["id_user"].'")',
							'id_custom_graph', 0, '', '--', 0, true);

		$module_description = "";
		$table->data[5][0] = __('Description');
		$table->data[5][1] = print_textarea ("module_description", 3, 35, $module_description, 'height: 50px;', true) ;

		echo "<form method='post' action='index.php?sec=greporting&sec2=godmode/reporting/reporting_builder'>";
		print_table ($table);
		echo '<div class="action-buttons" style="width: 500px;">';
		print_input_hidden ('add_content', 1);
		print_input_hidden ('id_report', $id_report);
		print_submit_button (__('Add'), 'add', false, 'class="sub wand"');
		echo "</div>";
		echo "</form>";
		
		// Part 3 - List of already assigned report items
		echo "<h2>".__('Report items')."</h2>";
		$table->id = 'table-assigned-reports';
		$table->width = '90%';
		$table->data = array ();
		$table->head = array ();
		$table->size = array ();
		$table->rowstyle = array ();
		$table->head[0] = __('Order');
		$table->head[1] = __('Type');
		$table->head[2] = __('Agent');
		$table->head[3] = __('Module');
		$table->head[4] = __('Period');
		$table->head[5] = __('Options');
		$table->align = array ();
		$table->align[0] = 'center';
		$table->align[5] = 'center';
		if ($report_id_user == $config['id_user']) {
			$table->align[6] = 'center';
			$table->head[6] = __('Delete');
		}
		
		$report_contents = get_report_contents ($id_report);
		
		if (sizeof ($report_contents)) {
			$first_id = $report_contents[0]['id_rc'];
			$last_id = $report_contents[sizeof ($report_contents) - 1]['id_rc'];
		}
		if ($report_contents !== false) {
			foreach ($report_contents as $report_content) {
				$data = array ();
				$data[0] = '';
				if ($first_id != $report_content['id_rc']) {
					$data[0] .= '<form method="post" style="display:inline">';
					$data[0] .= print_input_hidden ('edit_report', 1, true);
					$data[0] .= print_input_hidden ('id_report', $id_report, true);
					$data[0] .= print_input_hidden ('content_up', 1, true);
					$data[0] .= print_input_hidden ('id_report_content', $report_content['id_rc'], true);
					$data[0] .= print_input_image ('up', 'images/up.png', 1, '',
						true, array ('title' => __('Up')));
					$data[0] .= '</form>';
				}
				if ($last_id != $report_content['id_rc']) {
					$data[0] .= '<form method="post" style="display:inline">';
					$data[0] .= print_input_hidden ('edit_report', 1, true);
					$data[0] .= print_input_hidden ('id_report', $id_report, true);
					$data[0] .= print_input_hidden ('content_down', 1, true);
					$data[0] .= print_input_hidden ('id_report_content', $report_content['id_rc'], true);
					$data[0] .= print_input_image ('down', 'images/down.png', 1, '',
						true, array ('title' => __('Down')));
					$data[0] .= '</form>';
				}
				$data[1] = get_report_name ($report_content['type']);
				$data[2] = get_agent_name ($report_content['id_agent']);
				$data[3] = '--';
				if (get_report_type_data_source ($report_content['type']) == 'module') {
					if ($report_content['id_agent_module'] > 0)
						$data[2] = strtolower (get_agentmodule_agent_name ($report_content['id_agent_module']));
					$data[3] = strtolower (get_db_value ('descripcion', 'tagente_modulo', 'id_agente_modulo', $report_content['id_agent_module']));
				}
				$data[4] = human_time_description ($report_content['period']);
				$data[5] = '';
				if ($report_content['type'] == 'SLA') {
					$data[5] = '<a href="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&id_report='.$id_report.'&edit_sla_report_content=1&id_report_content='.$report_content['id_rc'].'"><img src="images/setup.png"></a>';
				}
				if ($report_id_user == $config['id_user'] || is_user_admin ($config['id_user'])) {
					$data[6] = '<form method="post" style="display:inline" onsubmit="if (!confirm (\''.__('Are you sure?').'\')) return false">';
					$data[6] .= print_input_hidden ('edit_report', 1, true);
					$data[6] .= print_input_hidden ('id_report', $id_report, true);
					$data[6] .= print_input_hidden ('delete_report_content', 1, true);
					$data[6] .= print_input_hidden ('id_report_content', $report_content['id_rc'], true);
					$data[6] .= print_input_image ('delete', 'images/cross.png', 1, '',
						true, array ('title' => __('Delete')));
					$data[6] .= '</form>';
				}
			
				array_push ($table->data, $data);
			}
		}
		print_table ($table);
	}
} else {
	// Report LIST
	echo "<h2>".__('Reporting')." &raquo; ";
	echo __('Custom reporting')."</h2>";

	$reports = get_reports (array ('order' => 'name'),
		array ('name', 'id_report', 'description', 'private', 'id_user', 'id_group'));
	$table->width = '0px';
	if (sizeof ($reports)) {
		$table->id = 'report_list';
		$table->width = '720px';
		$table->head = array ();
		$table->align = array ();
		$table->align[2] = 'center';
		$table->align[4] = 'center';
		$table->data = array ();
		$table->head[0] = __('Report name');
		$table->head[1] = __('Description');
		$table->head[2] = __('Private');
		$table->head[3] = __('Group');
		$table->head[4] = '';
		$table->size = array ();
		$table->size[4] = '40px';
		
		foreach ($reports as $report) {
		
			if (!is_user_admin ($config["id_user"])){
				if ($report["private"] && $report["id_user"] != $config['id_user'])
					if (!give_acl ($config["id_user"], $report["id_group"], "AW"))
						continue;
				if (!give_acl ($config["id_user"], $report["id_group"], "AW"))
					continue;
			}

			$data = array ();
			$data[0] = '<a href="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&edit_report=1&id_report='.
					$report['id_report'].'">'.$report['name'].'</a>';
			$data[1] = $report['description'];
			if ($report["private"] == 1)
				$data[2] = __('Yes');
			else
				$data[2] = __('No');
				
			$data[3] = get_group_name($report['id_group']);
			$data[4] = '<form method="post" style="display:inline" onsubmit="if (!confirm (\''.__('Are you sure?').'\')) return false">';
			$data[4] .= print_input_hidden ('id_report', $report['id_report'], true);
			$data[4] .= print_input_hidden ('delete_report', 1, true);
			$data[4] .= print_input_image ('delete', 'images/cross.png', 1, '',
				true, array ('title' => __('Delete')));
				$data[4] .= '</form>';
			
			array_push ($table->data, $data);
						
		}
		print_table ($table);
	} else {
		echo "<div class='nf'>".__('There are no defined reportings')."</div>";
	}
	
	echo '<form method="post" action="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder">';
	echo '<div class="action-buttons" style="width: 720px;">';
	print_input_hidden ('edit_report', 1);
	print_submit_button (__('Create report'), 'create', false, 'class="sub next"');
	echo "</div>";
	echo "</form>";
}

require_jquery_file ('pandora.controls');
?>
<script language="javascript" type="text/javascript">
/* <![CDATA[ */
function refresh_table () {
	$('#table-add-item > tbody > tr:odd td').removeClass('datos2').addClass('datos');
	$('#table-add-item > tbody > tr:even td').removeClass('datos').addClass('datos2');
}

function agent_changed () {
	var id_agent = this.value;
	$('#id_module').fadeOut ('normal', function () {
		$('#id_module').empty ();
		var inputs = [];
		inputs.push ("id_agent=" + id_agent);
		inputs.push ("get_agent_modules_json=1");
		inputs.push ("page=operation/agentes/ver_agente");
		jQuery.ajax ({
			data: inputs.join ("&"),
			type: 'GET',
			url: "ajax.php",
			timeout: 10000,
			dataType: 'json',
			success: function (data) {
				$('#id_module').append ($('<option></option>').attr ('value', 0).text ("--"));
				jQuery.each (data, function (i, val) {
					s = html_entity_decode (val['nombre']);
					$('#id_module').append ($('<option></option>').attr ('value', val['id_agente_modulo']).text (s.toLowerCase()));
				});
				$('#id_module').fadeIn ('normal');
			}
		});
	});
}

var previous_report_type;

function report_type_changed () {
	var inputs = [];
	inputs.push ("get_report_type_data_source=1");
	inputs.push ("id_report_type=" + this.value);
	inputs.push ("page=godmode/reporting/reporting_builder");
	jQuery.ajax ({
		data: inputs.join ("&"),
		type: 'GET',
		url: action="ajax.php",
		timeout: 10000,
		success: function (report_type) {
			module_inputs = '#table-add-item-2, #table-add-item-3';
			custom_graph_inputs = '#table-add-item-4';
			if (previous_report_type != report_type) {
				$('#id_module').empty ().append ($('<option></option>').attr ('value', 0).text ("--"));
				$('#id_agent').attr ('value', '0');
				$('#id_group').attr ('value', '0');
				switch (report_type) {
				case 'module':
					$(custom_graph_inputs).fadeOut ('normal', function () {
						$(module_inputs).fadeIn ('normal');
					});
					break;
				case 'custom-graph':
					$(module_inputs).fadeOut ('normal', function () {
						$(custom_graph_inputs).fadeIn ('normal');
					});
					
					break;	
				case 'sla':
				case 'agent-group':
					$(module_inputs).fadeOut ('normal');
					$(custom_graph_inputs).fadeOut ('normal');
					
					break;
				
				default:
				}
			}
			previous_report_type = report_type;
		}
	});
}

$(document).ready (function () {
	$('#id_agent').change (agent_changed);
	$('#type').change (report_type_changed);
	$('#report_id_group').pandoraSelectGroupAgent ();
});
/* ]]> */
</script>
