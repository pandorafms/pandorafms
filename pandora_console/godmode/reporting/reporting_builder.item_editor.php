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
global $config;

// Login check
check_login ();

if (! give_acl ($config['id_user'], 0, "IW")) {
	pandora_audit("ACL Violation",
		"Trying to access report builder");
	require ("general/noaccess.php");
	exit;
}

switch ($action) {
	case 'new':
		$actionParameter = 'save';
		$type = get_parameter('type', 'SLA');
		$period = 86400;
		$idAgent = null;
		$idCustomGraph = null;
		$text = null;
		$header = null;
		$idCustom = null;
		$url = null;
		$field = null;
		$line = null;
		$description = null;
		$sql = null;
		$group = null;
		
		$monday = true;
		$tuesday = true;
		$wednesday = true;
		$thursday = true;
		$friday = true;
		$saturday = true;
		$sunday = true;
		$time_from = '00:00';
		$time_to = '23:59';
		break;
	default:
		$actionParameter = 'update';
		$item = get_db_row_filter('treport_content', array('id_rc' => $idItem));
		
		$type = $item['type'];
		switch ($type) {
			case 'avg_value':
				$period = $item['period'];
				$description = $item['description'];
				$idAgentModule = $item['id_agent_module'];
				$idAgent = get_db_value_filter('id_agente', 'tagente_modulo', array('id_agente_modulo' => $idAgentModule));
				break;
			case 'simple_baseline_graph':
			case 'simple_graph':
				$description = $item['description'];
				$idAgentModule = $item['id_agent_module'];
				$idAgent = get_db_value_filter('id_agente', 'tagente_modulo', array('id_agente_modulo' => $idAgentModule));
				$period = $item['period'];
				break;
			case 'custom_graph':
				$description = $item['description'];
				$period = $item['period'];
				$idCustomGraph = $item['id_gs'];
				break;
			case 'SLA':
				$description = $item['description'];
				$period = $item['period'];
				
				$monday = $item['monday'];
				$tuesday = $item['tuesday'];
				$wednesday = $item['wednesday'];
				$thursday = $item['thursday'];
				$friday = $item['friday'];
				$saturday = $item['saturday'];
				$sunday = $item['sunday'];
				$time_from = $item['time_from'];
				$time_to = $item['time_to'];
				break;
			case 'monitor_report':
				$description = $item['description'];
				$idAgentModule = $item['id_agent_module'];
				$idAgent = get_db_value_filter('id_agente', 'tagente_modulo', array('id_agente_modulo' => $idAgentModule));
				$idAgentModule = $item['id_agent_module'];
				$period = $item['period'];
				break;
			case 'avg_value':
				$description = $item['description'];
				$idAgentModule = $item['id_agent_module'];
				$idAgent = get_db_value_filter('id_agente', 'tagente_modulo', array('id_agente_modulo' => $idAgentModule));
				$idAgentModule = $item['id_agent_module'];
				$period = $item['period'];
				break;
			case 'max_value':
				$description = $item['description'];
				$idAgentModule = $item['id_agent_module'];
				$idAgent = get_db_value_filter('id_agente', 'tagente_modulo', array('id_agente_modulo' => $idAgentModule));
				$idAgentModule = $item['id_agent_module'];
				$period = $item['period'];
				break;
			case 'min_value':
				$description = $item['description'];
				$idAgentModule = $item['id_agent_module'];
				$idAgent = get_db_value_filter('id_agente', 'tagente_modulo', array('id_agente_modulo' => $idAgentModule));
				$idAgentModule = $item['id_agent_module'];
				$period = $item['period'];
				break;
			case 'sumatory':
				$description = $item['description'];
				$idAgentModule = $item['id_agent_module'];
				$idAgent = get_db_value_filter('id_agente', 'tagente_modulo', array('id_agente_modulo' => $idAgentModule));
				$idAgentModule = $item['id_agent_module'];
				$period = $item['period'];
				break;
			case 'text':
				$description = $item['description'];
				$text = $item['text'];
				break;
			case 'sql':
				$description = $item['description'];
				$sql = $item['external_source'];
				$idCustom = $item['treport_custom_sql_id'];
				$header = $item['header_definition'];
				break;
			case 'sql_graph_pie':
				$description = $item['description'];
				$sql = $item['external_source'];
				$idCustom = $item['treport_custom_sql_id'];
				break;
			case 'sql_graph_vbar':
				$description = $item['description'];
				$sql = $item['external_source'];
				$idCustom = $item['treport_custom_sql_id'];
				break;
			case 'sql_graph_hbar':
				$description = $item['description'];
				$sql = $item['external_source'];
				$idCustom = $item['treport_custom_sql_id'];
				break;
			case 'url':
				$description = $item['description'];
				$url = $item['column_separator'];
				break;
			case 'database_serialized':
				$description = $item['description'];
				$idAgentModule = $item['id_agent_module'];
				$idAgent = get_db_value_filter('id_agente', 'tagente_modulo', array('id_agente_modulo' => $idAgentModule));
				$header = $item['header_definition'];
				$field = $item['column_separator'];
				$line = $item['line_separator'];
				$period = $item['period'];
				break;
			case 'TTRT':
				$description = $item['description'];
				$idAgentModule = $item['id_agent_module'];
				$idAgent = get_db_value_filter('id_agente', 'tagente_modulo', array('id_agente_modulo' => $idAgentModule));
				$period = $item['period'];
				break;
			case 'TTO':
				$description = $item['description'];
				$idAgentModule = $item['id_agent_module'];
				$idAgent = get_db_value_filter('id_agente', 'tagente_modulo', array('id_agente_modulo' => $idAgentModule));
				$period = $item['period'];
				break;
			case 'MTBF':
				$description = $item['description'];
				$idAgentModule = $item['id_agent_module'];
				$idAgent = get_db_value_filter('id_agente', 'tagente_modulo', array('id_agente_modulo' => $idAgentModule));
				$period = $item['period'];
				break;
			case 'MTTR':
				$description = $item['description'];
				$idAgentModule = $item['id_agent_module'];
				$idAgent = get_db_value_filter('id_agente', 'tagente_modulo', array('id_agente_modulo' => $idAgentModule));
				$period = $item['period'];
				break;
			case 'alert_report_module':
				$description = $item['description'];
				$idAgentModule = $item['id_agent_module'];
				$idAgent = get_db_value_filter('id_agente', 'tagente_modulo', array('id_agente_modulo' => $idAgentModule));
				$period = $item['period'];
				break;
			case 'alert_report_agent':
				$description = $item['description'];
				$idAgent = $item['id_agent'];
				$period = $item['period'];
				break;
			case 'event_report_agent':
				$description = $item['description'];
				$idAgent = $item['id_agent'];
				$period = $item['period'];
				break;
			case 'event_report_group':
				$description = $item['description'];
				$period = $item['period'];
				$group = $item['id_agent'];
				break;
			case 'event_report_module':
				$description = $item['description'];
				$idAgentModule = $item['id_agent_module'];
				$idAgent = get_db_value_filter('id_agente', 'tagente_modulo', array('id_agente_modulo' => $idAgentModule));
				$period = $item['period'];
				break;
		}
		
		break;
}

$intervals = array ();
$intervals[300] = human_time_description_raw (300);
$intervals[600] = human_time_description_raw (600);
$intervals[86400] = human_time_description_raw (86400);
$intervals[432000] = human_time_description_raw (432000);
$intervals[1296000] = human_time_description_raw (1296000);
$intervals[2592000] = human_time_description_raw (2592000);

$urlForm = 'index.php?sec=greporting&sec2=godmode/reporting/reporting_builder&tab=item_editor&action=' . $actionParameter . '&id_report=' . $idReport;

echo '<form action="' . $urlForm . '" method="post">';
print_input_hidden('id_item', $idItem);
?>
<table style="" class="databox" id="" border="0" cellpadding="4" cellspacing="4" width="90%">
	<tbody>
		<tr id="row_type" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Type'); ?></td>
			<td style="">
				<?php
				if ($action == 'new') { 
					print_select(get_report_types(), 'type', $type, 'chooseType();', '', '');
				}
				else {
					echo get_report_name($type);
					echo '<input type="hidden" id="type" name="type" value="' . $type . '" />';
				}
				?>
			</td>
		</tr>
		<tr id="row_description" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Description'); ?></td>
			<td style=""><?php echo print_textarea('description', 3, 25, $description); ?></td>
		</tr>
		<tr id="row_period" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Period'); ?></td>
			<td style=""><?php print_extended_select_for_time ($intervals, 'period', $period, '', '', '0', 10); echo __(" seconds."); ?></td>
		</tr>
		<tr id="row_working_time">
			<td style="vertical-align: top;"><?php echo __('Working time');?></td>
			<td>
				<table border="0">
					<tr>
						<td><?php echo __('Monday'); print_checkbox('monday', 1, $monday);?></td>
						<td><?php echo __('Tuesday'); print_checkbox('tuesday', 1, $tuesday);?></td>
						<td><?php echo __('Wednesday'); print_checkbox('wednesday', 1, $wednesday);?></td>
						<td><?php echo __('Thursday'); print_checkbox('thursday', 1, $thursday);?></td>
						<td><?php echo __('Friday'); print_checkbox('friday', 1, $friday);?></td>
						<td><?php echo __('Saturday'); print_checkbox('saturday', 1, $saturday);?></td>
						<td><?php echo __('Sunday'); print_checkbox('sunday', 1, $sunday);?></td>
					</tr>
					<tr>
						<td><?php echo __('Time from');?></td>
						<td colspan="6"><?php print_input_text ('time_from', $time_from, '', 7, 7);?></td>
					</tr>
					<tr>
						<td><?php echo __('Time to');?></td>
						<td colspan="6"><?php print_input_text ('time_to', $time_to, '', 7, 7);?></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr id="row_group" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Group');?></td>
			<td style=""><?php print_select(get_all_groups(), 'group', $group, '', __('All'), '0'); ?></td>
		</tr>
		<tr id="row_agent" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Agent'); ?></td>
			<td style="">
				<?php
				print_input_hidden('id_agent', $idAgent) .
					print_input_text_extended ('agent', get_agent_name ($idAgent), 'text-agent', '', 30, 100, false, '',
						array('style' => 'background: url(images/lightning.png) no-repeat right;'))
						. '<a href="#" class="tip">&nbsp;<span>' . __("Type at least two characters to search") . '</span></a>';
				?>
			</td>
		</tr>
		<tr id="row_module" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Module'); ?></td>
			<td style="">
				<?php
				if($idAgent) {
					$sql = "SELECT id_agente_modulo, nombre FROM tagente_modulo WHERE id_agente =  " . $idAgent;
					print_select_from_sql($sql, 'id_agent_module', $idAgentModule, '', '', '0');
				}
				else {	
					?>
					<select id="id_agent_module" name="id_agent_module" disabled="disabled">
						<option value="0"><?php echo __('Select an Agent first'); ?></option>
					</select>
					<?php
				}
				?>
			</td>
		</tr>
		<tr id="row_custom_graph" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Custom graph'); ?></td>
			<td style="">
				<?php
				print_select_from_sql ('SELECT id_graph, name FROM tgraph WHERE private = 0 OR (private = 1 AND id_user = "'.$config["id_user"].'")', 'id_custom_graph', $idCustomGraph, '', '--', 0);
				?>
			</td>
		</tr>
		<tr id="row_text" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Text'); ?></td>
			<td style=""><?php print_textarea('text', 5, 25, $text); ?></td>
		</tr>
		<tr id="row_query" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Query SQL'); ?></td>
			<td style=""><?php print_textarea('sql', 5, 25, $sql); ?></td>
		</tr>
		<tr id="row_header" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Serialized header') . '<a href="#" class="tip">&nbsp;<span>' . __("The separator character is | .") . '</span></a>';?></td>
			<td style=""><?php print_input_text('header', $header, '', 40, 90); ?></td>
		</tr>
		<tr id="row_custom" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Custom SQL template'); ?></td>
			<td style=""><?php print_select_from_sql('SELECT id, name FROM treport_custom_sql', 'id_custom', $idCustom, 'chooseSQLquery()', '--', '0'); ?></td>
		</tr>
		<tr id="row_custom_example">
			<td style="vertical-align: top;"><?php echo __('SQL preview'); ?></td>
			<td style="" id="sql_example"></td> 
		</tr>
		<tr id="row_url" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('URL'); ?></td>
			<td style=""><?php print_input_text('url', $url, '', 40, 90); ?></td>
		</tr>
		<tr id="row_field_separator" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Field separator'); ?></td>
			<td style=""><?php print_input_text('field', $field, '', 2, 4); ?></td>
		</tr>
		<tr id="row_line_separator" style="" class="datos">
			<td style="vertical-align: top;"><?php echo __('Line separator'); ?></td>
			<td style=""><?php print_input_text('line', $line, '', 2, 4); ?></td>
		</tr>
	</tbody>
</table>
<?php
print_SLA_list('90%', $action, $idItem);
echo '<div class="action-buttons" style="width: 90%">';
if ($action == 'new') {
	print_submit_button(__('Create item'), 'create_item', false, 'class="sub wand"');
}
else {
	print_submit_button(__('Edit item'), 'edit_item', false, 'class="sub upd"');
}
echo '</div>';
echo '</form>';

require_jquery_file ('timeentry');
require_jquery_file ('autocomplete');
require_javascript_file('pandora');
if ($enterpriseEnable) {
	enterpriseTextBox();
}

function print_SLA_list($width, $action, $idItem = null) {
	?>
	<table class="databox" id="sla_list" border="0" cellpadding="4" cellspacing="4" width="90%">
		<thead>
			<tr>
				<th class="header" scope="col"><?php echo __('Agent');?></th>
				<th class="header" scope="col"><?php echo __('Module');?></th>
				<th class="header" scope="col"><?php echo __('SLA Min. (value)');?></th>
				<th class="header" scope="col"><?php echo __('SLA Max. (value)');?></th>
				<th class="header" scope="col"><?php echo __('SLA Limit (%)');?></th>
				<th class="header" scope="col"><?php echo __('Action');?></th>
			</tr>
		</thead>
			<?php
			switch ($action) {
				case 'new':
					?>
					<tr id="sla_template" style="" class="datos">
						<td colspan="6"><?php echo __('Please save the SLA for start to add items in this list.');?></td>
					</tr>
					<?php
					break;
				case 'save':
				case 'update':
				case 'edit':
					echo '<tbody id="list_sla">';
					$itemsSLA = get_db_all_rows_filter('treport_content_sla_combined', array('id_report_content' => $idItem));
					if ($itemsSLA === false) {
						$itemsSLA = array();
					}
					foreach ($itemsSLA as $item) {
						$idAgent = get_db_value_filter('id_agente', 'tagente_modulo', array('id_agente_modulo' => $item['id_agent_module']));
						$nameAgent = get_agent_name ($idAgent);
						$nameModule = get_db_value_filter('nombre', 'tagente_modulo', array('id_agente_modulo' => $item['id_agent_module']));
						
						echo '<tr id="sla_' . $item['id'] . '" style="" class="datos">
								<td>' . $nameAgent . '</td>
								<td>' . $nameModule . '</td>
								<td>' . $item['sla_min'] . '</td>
								<td>' . $item['sla_max'] . '</td>
								<td>' . $item['sla_limit'] . '</td>
								<td style="text-align: center;">
									<a href="javascript: deleteSLARow(' . $item['id'] . ');"><img src="images/cross.png" /></a>
								</td>
							</tr>';
					}
					echo '</tbody>';
					?>
					<tbody id="sla_template">
						<tr id="row" style="display: none;" class="datos">
							<td class="agent_name"></td>
							<td class="module_name"></td>
							<td class="sla_min"></td>
							<td class="sla_max"></td>
							<td class="sla_limit"></td>
							<td style="text-align: center;"><a class="delete_button" href="javascript: deleteSLARow(0);"><img src="images/cross.png"></a></td>
						</tr>
					</tbody>
					<tbody>
						<tr id="sla_form" style="" class="datos">
							<td>
								<input id="hidden-id_agent_sla" name="id_agent_sla" value="" type="hidden">
								<input style="background: transparent url(images/lightning.png) no-repeat right;" name="agent_sla" id="text-agent_sla" size="15" maxlength="20" type="text"><a href="#" class="tip">&nbsp;<span>Type at least two characters to search</span></a></td>
							<td><select id="id_agent_module_sla" name="id_agente_modulo_sla" disabled="disabled"><option value="0"><?php echo __('Select an Agent first'); ?></option></select></td>
							<td><input name="sla_min" id="text-sla_min" size="10" maxlength="10" type="text"></td>
							<td><input name="sla_max" id="text-sla_max" size="10" maxlength="10" type="text"></td>
							<td><input name="sla_limit" id="text-sla_limit" size="10" maxlength="10" type="text"></td>
							<td style="text-align: center;"><a href="javascript: addSLARow();"><img src="images/disk.png"></a></td>
						</tr>
					</tbody>
					<?php
					break;
				default:
					break;
			}
			?>
	</table>
	<span style="display: none" id="module_sla_text"><?php echo __('Select an Agent first'); ?></span>
	<?php
}
?>
<script>
$(document).ready (function () {
	agent_module_autocomplete('#text-agent', '#hidden-id_agent', '#id_agent_module');
	agent_module_autocomplete('#text-agent_sla', '#hidden-id_agent_sla', '#id_agent_module_sla');
	chooseType();
	chooseSQLquery();

	$("#text-time_to, #text-time_from").timeEntry ({
		spinnerImage: 'images/time-entry.png',
		spinnerSize: [20, 20, 0],
		show24Hours: true
		}
	);
});

function chooseSQLquery() {
	var idCustom = $("#id_custom").val();

	if (idCustom == 0) {
		$("#sql_example").html('');
	}
	else {
		$("#sql_example").html('<img src="images/spinner.gif" />');
		
		var params = [];
		params.push("get_custom_sql=1");
		params.push("id=" + idCustom);
		params.push("page=include/ajax/reporting.ajax");
		jQuery.ajax ({
			data: params.join ("&"),
			type: 'POST',
			url: action="ajax.php",
			timeout: 10000,
			dataType: 'json',
			success: function (data) {
				if (data['correct']) {
					$("#sql_example").html(data['sql']);
				}
			}
		});
	}
}

function deleteSLARow(id_row) {
	//ajax to delete
	var params = [];
	params.push("delete_sla_item=1");
	params.push("id=" + id_row);
	params.push("page=include/ajax/reporting.ajax");
	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: action="ajax.php",
		timeout: 10000,
		dataType: 'json',
		success: function (data) {
			if (data['correct']) {
				$("#sla_" + id_row).remove();
			}
		}
	});
}

function addSLARow() {
	var nameAgent = $("input[name=agent_sla]").val();
	var idAgent = $("input[name=id_agent_sla]").val();
	var idModule = $("#id_agent_module_sla").val();
	var nameModule = $("#id_agent_module_sla :selected").text();
	var slaMin = $("input[name=sla_min]").val();
	var slaMax = $("input[name=sla_max]").val();
	var slaLimit = $("input[name=sla_limit]").val();

	if ((idAgent != '') && (slaMin != '') && (slaMax != '')
		&& (slaLimit != '')) {
		var params = [];
		params.push("add_sla=1");
		params.push("id=" + $("input[name=id_item]").val());
		params.push("id_module=" + idModule);
		params.push("sla_min=" + slaMin);
		params.push("sla_max=" + slaMax);
		params.push("sla_limit=" + slaLimit);
		params.push("page=include/ajax/reporting.ajax");
		jQuery.ajax ({
			data: params.join ("&"),
			type: 'POST',
			url: action="ajax.php",
			timeout: 10000,
			dataType: 'json',
			success: function (data) {
				if (data['correct']) {
					row = $("#sla_template").clone();
					
					$("#row", row).css('display', '');
					$("#row", row).attr('id', 'sla_' + data['id']);
					$(".agent_name", row).html(nameAgent);
					$(".module_name", row).html(nameModule);
					$(".sla_min", row).html(slaMin);
					$(".sla_max", row).html(slaMax);
					$(".sla_limit", row).html(slaLimit);
					$(".delete_button", row).attr('href', 'javascript: deleteSLARow(' + data['id'] + ');');
					
					$("#list_sla").append($(row).html());
				
					$("input[name=id_agent_sla]").val('');
					$("input[name=agent_sla]").val('');
					$("#id_agent_module_sla").empty();
					$("#id_agent_module_sla").attr('disabled', 'true');
					$("#id_agent_module_sla").append(
						$("<option></option>")
						.attr ("value", 0)
						.html ($("#module_sla_text").html()));
					$("input[name=sla_min]").val('');
					$("input[name=sla_max]").val('');
					$("input[name=sla_limit]").val('');					
				}
			}
		});
	}
}

function chooseType() {
	type = $("#type").val();
	
	$("#row_description").css('display', 'none');
	$("#row_period").css('display', 'none');
	$("#row_agent").css('display', 'none');
	$("#row_module").css('display', 'none');
	$("#row_period").css('display', 'none');
	$("#row_custom_graph").css('display', 'none');
	$("#row_text").css('display', 'none');
	$("#row_query").css('display', 'none');
	$("#row_header").css('display', 'none');
	$("#row_custom").css('display', 'none');
	$("#row_url").css('display', 'none');
	$("#row_field_separator").css('display', 'none');
	$("#row_line_separator").css('display', 'none');
	$("#sla_list").css('display', 'none');
	$("#row_custom_example").css('display', 'none');
	$("#row_group").css('display', 'none');
	$("#row_working_time").css('display', 'none');
	
	switch (type) {
		case 'event_report_group':
			$("#row_description").css('display', '');
			$("#row_period").css('display', '');
			$("#row_group").css('display', '');
			break;
		case 'simple_graph':
		case 'simple_baseline_graph':
			$("#row_description").css('display', '');
			$("#row_agent").css('display', '');
			$("#row_module").css('display', '');
			$("#row_period").css('display', '');
			break;
		case 'custom_graph':
			$("#row_description").css('display', '');
			$("#row_period").css('display', '');
			$("#row_custom_graph").css('display', '');
			break;
		case 'SLA':
			$("#row_description").css('display', '');
			$("#row_period").css('display', '');
			$("#sla_list").css('display', '');
			$("#row_working_time").css('display', '');
			break;
		case 'monitor_report':
			$("#row_description").css('display', '');
			$("#row_agent").css('display', '');
			$("#row_module").css('display', '');
			$("#row_period").css('display', '');
			break;
		case 'avg_value':
			$("#row_description").css('display', '');
			$("#row_agent").css('display', '');
			$("#row_module").css('display', '');
			$("#row_period").css('display', '');
			break;
		case 'max_value':
			$("#row_description").css('display', '');
			$("#row_agent").css('display', '');
			$("#row_module").css('display', '');
			$("#row_period").css('display', '');
			break;
		case 'min_value':
			$("#row_description").css('display', '');
			$("#row_agent").css('display', '');
			$("#row_module").css('display', '');
			$("#row_period").css('display', '');
			break;
		case 'sumatory':
			$("#row_description").css('display', '');
			$("#row_agent").css('display', '');
			$("#row_module").css('display', '');
			$("#row_period").css('display', '');
			break;
		case 'agent_detailed':
			$("#row_description").css('display', '');
			$("#row_agent").css('display', '');
			$("#row_period").css('display', '');
			break;
		case 'text':
			$("#row_description").css('display', '');
			$("#row_text").css('display', '');
			break;
		case 'sql':
			$("#row_description").css('display', '');
			$("#row_query").css('display', '');
			$("#row_header").css('display', '');
			$("#row_custom").css('display', '');
			$("#row_custom_example").css('display', '');
			break;
		case 'sql_graph_pie':
			$("#row_description").css('display', '');
			$("#row_query").css('display', '');
			$("#row_custom").css('display', '');
			$("#row_custom_example").css('display', '');
			break;
		case 'sql_graph_hbar':
			$("#row_description").css('display', '');
			$("#row_query").css('display', '');
			$("#row_custom").css('display', '');
			$("#row_custom_example").css('display', '');
			break;
        case 'sql_graph_vbar':
			$("#row_description").css('display', '');
			$("#row_query").css('display', '');
			$("#row_custom").css('display', '');
			$("#row_custom_example").css('display', '');
			break;
		case 'url':
			$("#row_description").css('display', '');
			$("#row_url").css('display', '');
			break;
		case 'database_serialized':
			$("#row_description").css('display', '');
			$("#row_agent").css('display', '');
			$("#row_module").css('display', '');
			$("#row_header").css('display', '');
			$("#row_field_separator").css('display', '');
			$("#row_line_separator").css('display', '');
			$("#row_period").css('display', '');
			break;
		case 'TTRT':
			$("#row_description").css('display', '');
			$("#row_agent").css('display', '');
			$("#row_module").css('display', '');
			$("#row_period").css('display', '');
			break;
		case 'TTO':
			$("#row_description").css('display', '');
			$("#row_agent").css('display', '');
			$("#row_module").css('display', '');
			$("#row_period").css('display', '');
			break;
		case 'MTBF':
			$("#row_description").css('display', '');
			$("#row_agent").css('display', '');
			$("#row_module").css('display', '');
			$("#row_period").css('display', '');
			break;
		case 'MTTR':
			$("#row_description").css('display', '');
			$("#row_agent").css('display', '');
			$("#row_module").css('display', '');
			$("#row_period").css('display', '');
			break;
		case 'alert_report_module':
			$("#row_description").css('display', '');
			$("#row_agent").css('display', '');
			$("#row_module").css('display', '');
			$("#row_period").css('display', '');
			break;
		case 'alert_report_agent':
			$("#row_description").css('display', '');
			$("#row_agent").css('display', '');
			$("#row_period").css('display', '');
			break;
		case 'event_report_agent':
			$("#row_description").css('display', '');
			$("#row_agent").css('display', '');
			$("#row_period").css('display', '');
			break;
		case 'event_report_module':
			$("#row_description").css('display', '');
			$("#row_agent").css('display', '');
			$("#row_module").css('display', '');
			$("#row_period").css('display', '');
			break;
	}
}
</script>
