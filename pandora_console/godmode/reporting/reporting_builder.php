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
$id_user=$_SESSION["id_usuario"];
global $REMOTE_ADDR;

if (comprueba_login() != 0) {
	audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access report builder");
	include ("general/noaccess.php");
	exit;
}

if ((give_acl($id_user,0,"AW") != 1 ) AND (dame_admin($id_user)!=1)) {
	audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to access graph builder");
	include ("general/noaccess.php");
	exit;
}

if (defined ('AJAX')) {
	$get_report_type_data_source = (bool) get_parameter ('get_report_type_data_source');
	
	if ($get_report_type_data_source) {
		$id_report_type = (string) get_parameter ('id_report_type');
		
		echo get_report_type_data_source ($id_report_type);
		exit ();
	}
	exit ();
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
$report_id_group = (int) get_parameter ('report_id_group');
$id_agent = (int) get_parameter ('id_agent');
$id_group = (int) get_parameter ('id_group');
$add_content = (bool) get_parameter ('add_content');
$edit_sla_report_content = (bool) get_parameter ('edit_sla_report_content');
$content_up = (bool) get_parameter ('content_up');
$content_down = (bool) get_parameter ('content_down');

if (isset($_GET["get_agent"])) {
 	$id_agent = $_POST["id_agent"];
}

// Delete module SQL code
if ($delete_report_content) {
	$id_report_content = (int) get_parameter ('id_report_content');
	$order = get_db_value ('`order`', 'treport_content', 'id_rc', $id_report_content);
	$sql = sprintf ('UPDATE treport_content SET `order` = `order` -1 WHERE id_report = %d AND `order` > %d', $id_report, $order);
	mysql_query ($sql);
	$sql = sprintf ('DELETE FROM treport_content WHERE id_rc = %d', $id_report_content);
	$result = mysql_query ($sql);
	if ($result)
		echo "<h3 class='suc'>".lang_string ('delete_ok')."</h3>";
	else
		echo "<h3 class='error'>".lang_string ('delete_no')."</h3>";
}

// Move content up
if ($content_up) {
	$id_report_content = (int) get_parameter ('id_report_content');
	$order = get_db_value ('`order`', 'treport_content', 'id_rc', $id_report_content);
	/* Set the previous element order to the current of the content we want to change */
	$sql = sprintf ('UPDATE treport_content SET `order` = `order` + 1 WHERE id_report = %d AND `order` = %d',
			$id_report, $order - 1);
	$result = mysql_query ($sql);
	$sql = sprintf ('UPDATE treport_content SET `order` = `order` - 1 WHERE id_rc = %d', $id_report_content);
	$result = mysql_query ($sql);
}

// Move content down
if ($content_down) {
	$id_report_content = (int) get_parameter ('id_report_content');
	$order = get_db_value ('`order`', 'treport_content', 'id_rc', $id_report_content);
	/* Set the previous element order to the current of the content we want to change */
	$sql = sprintf ('UPDATE treport_content SET `order` = `order` - 1 WHERE id_report = %d AND `order` = %d',
			$id_report, $order + 1);
	$result = mysql_query ($sql);
	$sql = sprintf ('UPDATE treport_content SET `order` = `order` + 1 WHERE id_rc = %d', $id_report_content);
	$result = mysql_query ($sql);
}

// Delete report SQL code
if ($delete_report) {
	$sql = sprintf ('DELETE FROM treport_content WHERE id_report = %d', $id_report);
	$sql2 = sprintf ('DELETE FROM treport WHERE id_report = %d', $id_report);
	$res = mysql_query ($sql);
	$res2 = mysql_query ($sql2);
	if ($res AND $res2)
		echo "<h3 class=suc>".lang_string ('delete_reporting_ok')."</h3>";
	else
		echo "<h3 class=error>".lang_string ('delete_reporting_no')."</h3>";
	$id_report = 0;
}

// Add module SQL code
if ($add_content) {
	if (! $id_report) {
		audit_db($id_user,$REMOTE_ADDR, "Hack attempt","Parameter trash in report builder");
		include ("general/noaccess.php");
		exit ();
	}
	$id_agent_module = (int) get_parameter ('id_module');
	$period = (int) get_parameter ('period');
	$type = (string) get_parameter ('type');
	$id_custom_graph = (int) get_parameter ('id_custom_graph');
	
	$order = (int) get_db_value ('COUNT(*)', 'treport_content', 'id_report', $id_report);

	$sql = sprintf ('INSERT INTO treport_content (id_report, id_gs, id_agent_module,
			`order`, type, period) 
			VALUES (%d, %s, %s, %d, "%s", %d)',
			$id_report, $id_custom_graph ? $id_custom_graph : "NULL",
			$id_agent_module ? $id_agent_module : "NULL",
			$order, $type, $period * 3600);
	if ($result = mysql_query($sql)) {
		echo "<h3 class=suc>".lang_string ('create_reporting_ok')."</h3>";
		$id_agent = 0;
		$id_agent_module = 0;
		$report_id_group = 0;
		$period = 0;
		$type = 0;
		$id_custom_graph = 0;
		$sla_max = 0;
		$sla_min = 0;
		$sla_limit = 0;
	} else {
		echo "<h3 class=error>".lang_string ('create_reporting_no')."</h3>";
		/* Do not unset so the values are kept in the form */
	}
}

// Create report
if ($create_report) {
	$sql = sprintf ('INSERT INTO treport (name, description, id_user, private, id_group) 
			VALUES ("%s", "%s", "%s", %d, %d)',
			$report_name, $report_description, $config['id_user'], $report_private, $report_id_group);
	$result = mysql_query ($sql);
	if ($result)
		echo "<h3 class=suc>".lang_string ('create_reporting_ok')."</h3>";
	else
		echo "<h3 class=error>".lang_string ('create_reporting_no')."</h3>";
	$id_report = mysql_insert_id ();
}

// Update report
if ($update_report) {
	$sql = sprintf ('UPDATE treport SET name = "%s", 
			description = "%s", private = %d
			WHERE id_report = %d',
			$report_name, $report_description,
			$report_private, $id_report);
	$result = mysql_query ($sql);
	if ($result)
		echo "<h3 class=suc>".lang_string ('modify_ok')."</h3>";
	else
		echo "<h3 class=error>".lang_string ('modify_no')."</h3>";
}

if ($id_report) {
	$report = get_db_row ('treport', 'id_report', (int) $id_report);
	$report_name = $report["name"];
	$report_description = $report["description"];
	$report_private = $report["private"];
	$report_id_user = $report["id_user"];
	$report_id_group = $report['id_group'];
}

$all_agents = get_agents_in_group ($report_id_group);
$agents = array ();
foreach ($all_agents as $agent) {
	$agents[$agent['id_agente']] = $agent['nombre'];
}
asort ($agents);

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
		$sla_max = (int) get_parameter ('sla_max');
		$sla_min = (int) get_parameter ('sla_min');
		$sla_limit = (int) get_parameter ('sla_limit');
		
		$sql = sprintf ('INSERT INTO treport_content_sla_combined (id_report_content, 
				id_agent_module, sla_max, sla_min, sla_limit) VALUES (%d, %d, %d, %d, %d)',
				$id_report_content, $id_module, $sla_max, $sla_min, $sla_limit);
		
		if ($id_module) {
			$result = mysql_query ($sql);
			if ($result)
				echo "<h3 class=suc>".lang_string ('add_sla_ok')."</h3>";
			else 
				echo "<h3 class=error>".lang_string ('add_sla_no')."</h3>";
		} else {
			echo "<h3 class=error>".lang_string ('sla_module_is_null')."</h3>";
		}
	}
	if ($delete_sla) {
		$id_sla = (int) get_parameter ('id_sla');
		$sql = sprintf ('DELETE FROM treport_content_sla_combined WHERE id = %d', $id_sla);
		$result = mysql_query ($sql);
		if ($result)
			echo "<h3 class=suc>".lang_string ('delete_sla_ok')."</h3>";
		else 
			echo "<h3 class=error>".lang_string ('delete_sla_no')."</h3>";
	}
	$report_content = get_db_row ('treport_content', 'id_rc', $id_report_content);

	/* Show all SLAs defined in the report content */	
	echo '<h2>'.lang_string ('reporting')." &gt; ";
	echo lang_string ('custom_reporting_builder')." &gt; ";
	echo '<a href="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&edit_report=1&id_report='.
		$id_report.'">'.$report['name'].'</a></h2>';
	echo '<h2>'.lang_string ('defined_slas')."</h2>";

	$table->id = 'table-sla-list';
	$table->width = '500px';
	$table->align = array ();
	$table->align[5] = 'center';
	$table->data = array ();
	$table->head = array ();
	$table->head[0] = lang_string ('agent');
	$table->head[1] = lang_string ('module');
	$table->head[2] = lang_string ('sla_min');
	$table->head[3] = lang_string ('sla_max');
	$table->head[4] = lang_string ('sla_limit');
	$table->head[5] = lang_string ('delete');

	$slas = get_db_all_rows_field_filter ('treport_content_sla_combined', 'id_report_content', $id_report_content);
	foreach ($slas as $sla) {
		$data = array ();
		
		$data[0] = dame_nombre_agente_agentemodulo ($sla['id_agent_module']);
		$data[1] = dame_nombre_modulo_agentemodulo ($sla['id_agent_module']);
		$data[2] = $sla['sla_min'];
		$data[3] = $sla['sla_max'];
		$data[4] = $sla['sla_limit'].'%';
		$data[5] = '<a href="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&id_report='.
				$id_report.'&edit_sla_report_content=1&delete_sla=1&id_report_content='.
				$id_report_content.'&id_sla='.$sla['id'].'"><img src="images/cross.png"></a>';
		
		array_push ($table->data, $data);
	}
	
	if (sizeof ($slas)) {
		print_table ($table);
	}

	echo "<h2>".lang_string ('sla_construction')."</h2>";
	$table->id = 'table-add-sla';
	$table->width = '500px';
	$table->data = array ();
	$table->style = array ();
	$table->size = array ();
	$table->size[0] = '150px';
	$table->head = array ();
	$table->style[0] = 'font-weight: bold';
	$table->data[0][0] = lang_string ('agent');
	$table->data[0][1] = print_select ($agents, 'id_agent', 0, '', '--', 0, true);
	$table->data[1][0] = lang_string ('module');
	$table->data[1][1] = print_select (array (), 'id_module', 0, '', '--', 0, true);
	$table->data[2][0] = lang_string ('sla_min');
	$table->data[2][1] = print_input_text ('sla_min', $sla_min, '', 5, 10, true);
	$table->data[3][0] = lang_string ('sla_max');
	$table->data[3][1] = print_input_text ('sla_max', $sla_max, '', 5, 10, true);
	$table->data[4][0] = lang_string ('sla_limit');
	$table->data[4][1] = print_input_text ('sla_limit', $sla_limit, '', 5, 10, true);
	
	echo '<form method="post" action="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&id_report='.
		$id_report.'&edit_sla_report_content=1&id_report_content='.$id_report_content.'">';
	print_table ($table);
	print_input_hidden ('add_sla', 1);
	print_input_hidden ('edit_sla_report_content', 1);
	print_input_hidden ('id_report_content', $id_report_content);
	echo '<div class="action-buttons" style="width: '.$table->width.'">';
	print_submit_button (lang_string ('create'), 'add', false, 'class="sub wand"');
	echo '</div>';
	echo '</form>';
} else if ($edit_report || $id_report) {
	 /* Edit and creation report form */
	if (isset($_POST["id_agent"]))
		$id_agent = $_POST["id_agent"];
	else
		$id_agent = 0;
	echo "<h2>".lang_string ('reporting')." &gt; ";
	echo lang_string ('custom_reporting_builder')."</h2>";
	
	$table->id = 'table-edit-report';
	$table->width = '500px';
	$table->data = array ();
	$table->style = array ();
	$table->style[0] = 'font-weight: bold';
	$table->data[0][0] = lang_string ('report_name');
	$table->data[0][1] = print_input_text ('report_name', $report_name, '', 35, 150, true);
	$table->data[1][0] = lang_string ('group');
	if ($report_id_group) {
		/* Changing the group is not allowed. */
		$table->data[1][1] = '<a href="index.php?sec=estado&sec2=operation/agentes/estado_agente&refr=60&group_id='.
				$report_id_group.'">'.dame_grupo ($report_id_group).'</a>';
	} else {
		$table->data[1][1] = print_select_from_sql ('SELECT id_grupo, nombre FROM tgrupo ORDER BY nombre',
								'report_id_group', $report_id_group, '', '--', 0, true);
	}
	$table->data[1][1] .= ' <span id="icon_preview">';
	if ($report_id_group) {
		$table->data[1][1] .= '<img src="images/groups_small/'.dame_grupo_icono ($report_id_group).'.png" />';
	}
	$table->data[1][1] .= '</span>';
	$table->data[2][0] = lang_string ('private');
	$table->data[2][1] = print_checkbox ('report_private', 1, $report_private, true);
	$table->data[3][0] = lang_string ('description');
	$table->data[3][1] = print_textarea ('report_description', 3, 40, $report_description, '', true);

	echo "<form method='post' action='index.php?sec=greporting&sec2=godmode/reporting/reporting_builder'>";
	print_table ($table);
	// Button
	echo '<div class="action-buttons" style="width: '.$table->width.'">';
	print_input_hidden ('edit_report', 1);
	if ($id_report) {
		print_input_hidden ('id_report', $id_report);
		print_input_hidden ('update_report', 1);
		print_submit_button (lang_string ('update'), 'submit', false, 'class="sub next"');
	} else {
		print_input_hidden ('create_report', 1);
		print_submit_button (lang_string ('create'), 'submit', false, 'class="sub wand"');
	}
	echo "</div>";
	echo "</form>";
	
	if ($id_report) {
		// Part 2 - Add new items to report
		echo "<h2>".lang_string ('reporting_item_add')."</h2>";
		
		/* We're reusing some $table attributes */
		$table->id = 'table-add-item';
		$table->rowstyle = array ();
		$table->rowstyle[2] = 'display: none';
		$table->rowstyle[3] = 'display: none';
		$table->rowstyle[4] = 'display: none';
		$table->size = array ();
		$table->size[0] = '200px';
		$table->data = array ();

		$table->data[0][0] = lang_string ('reporting_type');
		$types = get_report_types ();
		asort ($types);
		$table->data[0][1] = print_select ($types, 'type', -1, '', '--', -1, true);
		$table->data[1][0] = lang_string ('period');
		$periods = array ();
		$periods[1] = lang_string ('hour');
		$periods[2] = '2 '.lang_string ('hours');
		$periods[3] = '3 '.lang_string ('hours');
		$periods[6] = '6 '.lang_string ('hours');
		$periods[12] = '12 '.lang_string ('hours');
		$periods[24] = lang_string ('last_day');
		$periods[48] = lang_string ('two_days');
		$periods[360] = lang_string ('last_week');
		$periods[720] = lang_string ('last_month');
		$periods[4320] = lang_string ('six_months');
		$table->data[1][1] = print_select ($periods, 'period', 0, '', '--', 0, true);

		$table->data[2][0] = lang_string ('source_agent');
		$table->data[2][1] = print_select ($agents, 'id_agent', $id_agent, '', '--', 0, true);
		
		$table->data[3][0] = lang_string ('module');
		$modules = array ();
		if ($id_agent) {
			$sql = sprintf ('SELECT * FROM tagente_modulo WHERE id_agente = %d ORDER BY nombre', $id_agent);
			$modules = get_db_all_rows_sql ($sql);
		}
		$table->data[3][1] = print_select ($modules, 'id_module', 0, '', '--', 0, true);
		
		$table->data[4][0] = lang_string ('custom_graph_name');
		$table->data[4][1] = print_select_from_sql ('SELECT id_graph, name FROM tgraph',
							'id_custom_graph', 0, '', '--', 0, true);

		echo "<form method='post' action='index.php?sec=greporting&sec2=godmode/reporting/reporting_builder'>";
		print_table ($table);
		echo '<div class="action-buttons" style="width: '.$table->width.'">';
		print_input_hidden ('add_content', 1);
		print_input_hidden ('id_report', $id_report);
		print_submit_button (lang_string ('add'), 'add', false, 'class="sub wand"');
		echo "</div>";
		echo "</form>";
		
		// Part 3 - List of already assigned report items
		echo "<h2>".lang_string ('report_items')."</h2>";
		$table->id = 'table-assigned-reports';
		$table->width = '90%';
		$table->data = array ();
		$table->head = array ();
		$table->size = array ();
		$table->rowstyle = array ();
		$table->head[0] = lang_string ('order');
		$table->head[1] = lang_string ('type');
		$table->head[2] = lang_string ('agent');
		$table->head[3] = lang_string ('module');
		$table->head[4] = lang_string ('period');
		$table->head[5] = lang_string ('Options');
		$table->align = array ();
		$table->align[0] = 'center';
		$table->align[5] = 'center';
		if ($report_id_user == $config['id_user']) {
			$table->align[6] = 'center';
			$table->head[6] = lang_string ('delete');
		}
		
		$sql = sprintf ('SELECT * FROM treport_content WHERE id_report = %d ORDER BY `order`', $id_report);
		$report_contents = get_db_all_rows_sql ($sql);
		if (sizeof ($report_contents)) {
			$first_id = $report_contents[0]['id_rc'];
			$last_id = $report_contents[sizeof ($report_contents) - 1]['id_rc'];
		}
		foreach ($report_contents as $report_content) {
			$data = array ();
			$data[0] = '';
			if ($first_id != $report_content['id_rc']) {
				$data[0] .= '<a href="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&edit_report=1&id_report='.
						$id_report.'&content_up=1&id_report_content='.$report_content['id_rc'].
						'"><img src="images/up.png" title="'.lang_string ('up').'"></a>';
			}
			if ($last_id != $report_content['id_rc']) {
				$data[0] .= '<a href="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&edit_report=1&id_report='.
						$id_report.'&content_down=1&id_report_content='.$report_content['id_rc'].
						'"><img src="images/down.png" title="'.lang_string ('down').'"></a>';
			}
			$data[1] = get_report_name ($report_content['type']);
			$data[2] = '--';
			$data[3] = '--';
			if (get_report_type_data_source ($report_content['type']) == 'module') {
				$data[2] = dame_nombre_agente_agentemodulo ($report_content['id_agent_module']);
				$data[3] = get_db_value ('descripcion', 'tagente_modulo', 'id_agente_modulo', $report_content['id_agent_module']);
			}
			$data[4] = human_time_description ($report_content['period']);
			$data[5] = '';
			if ($report_content['type'] == 'SLA') {
				$data[5] = '<a href="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&id_report='.$id_report.'&edit_sla_report_content=1&id_report_content='.$report_content['id_rc'].'"><img src="images/setup.png"></a>';
			}
			if ($report_id_user == $config['id_user']) {
				$data[6] = '<a href="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&id_report='.$id_report.'&delete_report_content=1&id_report_content='.$report_content['id_rc'].'"><img src="images/cross.png"></a>';
			}
			
			array_push ($table->data, $data);
		}
		print_table ($table);
	}
} else {
	// Report LIST
	echo "<h2>".lang_string ('reporting')." &gt; ";
	echo lang_string ('custom_reporting')."</h2>";

	$reports = get_db_all_rows_in_table ('treport');
	$table->width = '0px';
	if (sizeof ($reports)) {
		$table->id = 'report_list';
		$table->width = '600px';
		$table->head = array ();
		$table->align = array ();
		$table->align[2] = 'center';
		$table->data = array ();
		$table->head[0] = lang_string ('report_name');
		$table->head[1] = lang_string ('description');
		$table->head[2] = lang_string ('delete');
		
		foreach ($reports as $report) {
			if ($report["private"] || $report["id_user"] != $config['id_user'])
				continue;
			$data = array ();
			$data[0] = '<a href="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&edit_report=1&id_report='.
					$report['id_report'].'">'.$report['name'].'</a>';
			$data[1] = $report['description'];
			$data[2] = '<a href="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&delete_report=1&id_report='.
					$report['id_report'].'"><img src="images/cross.png"></a>';

			array_push ($table->data, $data);
			
		}
		print_table ($table);
	} else {
		echo "<div class='nf'>".lang_string ('no_reporting_def')."</div>";
	}
	
	echo '<form method="post" action="index.php?sec=greporting&sec2=godmode/reporting/reporting_builder">';
	echo '<div class="action-buttons" style="width: '.$table->width.'">';
	print_input_hidden ('edit_report', 1);
	print_submit_button (lang_string ('add'), 'create', false, 'class="sub next"');
	echo "</div>";
	echo "</form>";
}
?>

<script type="text/javascript" src="include/javascript/jquery.js"></script>

<script language="javascript" type="text/javascript">

function refresh_table () {
	$('##table-add-item > tbody > tr:odd td').removeClass('datos2').addClass('datos');
	$('##table-add-item > tbody > tr:even td').removeClass('datos').addClass('datos2');
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
					if (val['descripcion'] == "") {
						s = html_entity_decode (val['nombre']);
					} else {
						s = html_entity_decode (val['descripcion']);
					}
					$('#id_module').append ($('<option></option>').attr ('value', val['id_agente_modulo']).text (s));
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

function group_changed () {
	var inputs = [];
	inputs.push ("get_group_json=1");
	inputs.push ("id_group=" + this.value);
	inputs.push ("page=godmode/groups/group_list");
	jQuery.ajax ({
		data: inputs.join ("&"),
		type: 'GET',
		url: action="ajax.php",
		timeout: 10000,
		dataType: 'json',
		success: function (data) {
			var data_ = data;
			$('#icon_preview').fadeOut ('normal', function () {
				$('#icon_preview').empty ();
				if (data_ != null) {
					$('#icon_preview').append ($('<img />').attr ('src', 'images/groups_small/'+data['icon']+'.png'));
				}
				$('#icon_preview').fadeIn ();
			});
		}
	});
}

$(document).ready (function () {
	$('#id_agent').change (agent_changed);
	$('#type').change (report_type_changed);
	$('#report_id_group').change (group_changed);
}); 
</script>

