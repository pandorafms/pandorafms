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

// Load globar vars
global $config;

//Ajax tooltip to deploy modules's tag info.
if (is_ajax ()) {
	$get_tag_tooltip = (bool) get_parameter ('get_tag_tooltip', 0);
	$get_relations_tooltip = (bool) get_parameter ('get_relations_tooltip', 0);
	
	
	if ($get_tag_tooltip) {
		$id_agente_modulo = (int) get_parameter ('id_agente_modulo');
		if ($id_agente_modulo == false)
			return;
		$tags = tags_get_module_tags($id_agente_modulo);
		
		
		if ($tags === false)
			$tags = array();
		
		echo '<h3>' . __("Tag's information") . '</h3>';
		echo "<table border='0'>";
		foreach ($tags as $tag) {
			echo "<tr>";
			
			echo "<td>";
			if (tags_get_module_policy_tags($tag, $id_agente_modulo)) {
				html_print_image('images/policies.png', false, array('style' => 'vertical-align: middle;'));
			}
			echo "</td>";
			
			echo "<td>";
			echo tags_get_name($tag);
			echo "</td>";
			
			echo "</tr>";
		}
		echo "</table>";
		
		return;
	}
	
	
	if ($get_relations_tooltip) {
		$id_agente_modulo = (int) get_parameter ('id_agente_modulo');
		if ($id_agente_modulo == false)
			return;
		$id_agente = modules_get_agentmodule_agent($id_agente_modulo);
		
		$params = array(
			'id_agent' => $id_agente,
			'id_module' => $id_agente_modulo
		);
		$relations = modules_get_relations($params);
		
		if (empty($relations))
			return;
		
		$table_relations = new stdClass();
		$table_relations->id = 'module_' . $id_agente_modulo . '_relations';
		$table_relations->width = '100%';
		$table_relations->class = 'databox filters';
		$table_relations->style = array();
		$table_relations->style[0] = 'font-weight: bold;';
		$table_relations->style[2] = 'font-weight: bold;';
		$table_relations->head = array();
		$table_relations->head[0] = __("Relationship information");
		$table_relations->head_colspan[0] = 4;
		$table_relations->data = array();
		
		foreach ($relations as $relation) {
			if ($relation['module_a'] == $id_agente_modulo) {
				$id_module = $relation['module_b'];
			}
			else {
				$id_module = $relation['module_a'];
			}
			$id_agent = modules_get_agentmodule_agent($id_module);
			
			$data = array();
			$data[0] = __('Agent');
			$data[1] = ui_print_agent_name ($id_agent, true);
			$data[2] = __('Module');
			$data[3] = "<a href='index.php?sec=gagente&sec2=godmode/agentes/configurar_agente
				&id_agente=$id_agent&tab=module&edit_module=1&id_agent_module=$id_module'>" .
				ui_print_truncate_text(modules_get_agentmodule_name($id_module), 'module_medium', true, true, true, '[&hellip;]') . "</a>";
			$table_relations->data[] = $data;
		}
		html_print_table($table_relations);
		
		return;
	}
	return;
}

if (!isset ($id_agente)) {
	//This page is included, $id_agente should be passed to it.
	db_pandora_audit("HACK Attempt",
		"Trying to get the monitor list without id_agent passed");
	include ("general/noaccess.php");
	exit;
}

$id_agent = (int)get_parameter('id_agente');
$status_filter_monitor = (int)get_parameter('status_filter_monitor', -1);
$status_text_monitor = get_parameter('status_text_monitor', '');
$sort_field = get_parameter('sort_field', 'name');
$sort = get_parameter('sort', 'up');


echo "<h4 style='padding-top:0px !important;'>";

echo ui_print_help_tip(__('To see the list of modules paginated, enable this option in the Styles Configuration.'), true) .
	__('Full list of monitors') . '&nbsp;' .
	reporting_tiny_stats ($agent, true, 'modules');

$modules_not_init = agents_monitor_notinit($id_agente);
if (!empty($modules_not_init)) {
	echo clippy_context_help("modules_not_init");
}
echo "</h4>";


ob_start();


print_form_filter_monitors($id_agente, $status_filter_monitor, $status_text_monitor);

echo "<div id='module_list_loading'>" .
	html_print_image('images/spinner.gif', true) .
	'</div>';
echo "<div id='module_list'>" .
		
	"</div>";


$html_toggle = ob_get_clean();
ui_toggle($html_toggle,
	__('List of modules'),
	'status_monitor_agent',
	false);

?>
<script type="text/javascript">
	var sort_field = '<?php echo $sort_field; ?>';
	var sort_rows = '<?php echo $sort; ?>';
	var filter_status = -1;
	var filter_text = "";
	reset_filter_modules ();
	
	$(document).ready(function() {
		/*filter_modules();
		var parameters = {};
		
		parameters["list_modules"] = 1;
		parameters["id_agente"] = <?php echo $id_agente; ?>;
		parameters["page"] = "include/ajax/module";
		
		jQuery.ajax ({
			data: parameters,
			type: 'POST',
			url: "ajax.php",
			dataType: 'html',
			success: function (data) {
				$("#module_list_loading").hide();
				
				$("#module_list").empty();
				$("#module_list").html(data);
			}
		});
		*/
	});
	
	function order_module_list(sort_field_param, sort_rows_param) {
		sort_field = sort_field_param;
		sort_rows = sort_rows_param;
		
		var parameters = {};
		
		parameters["list_modules"] = 1;
		parameters["id_agente"] = <?php echo $id_agente; ?>;
		parameters["sort_field"] = sort_field;
		parameters["sort"] = sort_rows;
		parameters["status_filter_monitor"] = filter_status;
		parameters["status_text_monitor"] = filter_text;
		parameters["status_module_group"] = filter_group;
		parameters["page"] = "include/ajax/module";
		
		
		$("#module_list").empty();
		$("#module_list_loading").show();
		
		
		jQuery.ajax ({
			data: parameters,
			type: 'POST',
			url: "ajax.php",
			dataType: 'html',
			success: function (data) {
				$("#module_list_loading").hide();
				
				$("#module_list").empty();
				$("#module_list").html(data);
			}
		});
	}
	
	function filter_modules() {
		filter_status = $("#status_filter_monitor").val();
		filter_group = $("#status_module_group").val();
		filter_text = $("input[name='status_text_monitor']").val();
		
		var parameters = {};
		
		parameters["list_modules"] = 1;
		parameters["id_agente"] = <?php echo $id_agente; ?>;
		parameters["sort_field"] = sort_field;
		parameters["sort"] = sort_rows;
		parameters["status_filter_monitor"] = filter_status;
		parameters["status_text_monitor"] = filter_text;
		parameters["status_module_group"] = filter_group;
		parameters["filter_monitors"] = 1;
		parameters["monitors_change_filter"] = 1;
		parameters["page"] = "include/ajax/module";
		
		
		$("#module_list").empty();
		$("#module_list_loading").show();
		
		
		jQuery.ajax ({
			data: parameters,
			type: 'POST',
			url: "ajax.php",
			dataType: 'html',
			success: function (data) {
				$("#module_list_loading").hide();
				
				$("#module_list").empty();
				$("#module_list").html(data);
			}
		});
	}
	
	function reset_filter_modules() {
		$("#status_filter_monitor").val(-1);
		$("#status_module_group").val(-1);
		$("input[name='status_text_monitor']").val("");
		
		filter_modules();
	}
	
	function pagination_list_modules(offset) {
		var parameters = {};
		
		parameters["list_modules"] = 1;
		parameters["id_agente"] = <?php echo $id_agente; ?>;
		parameters["offset"] = offset;
		parameters["sort_field"] = sort_field;
		parameters["sort"] = sort_rows;
		parameters["status_filter_monitor"] = filter_status;
		parameters["status_text_monitor"] = filter_text;
		parameters["status_module_group"] = filter_group;
		parameters["filter_monitors"] = 0;
		parameters["monitors_change_filter"] = 0;
		parameters["page"] = "include/ajax/module";
		
		
		$("#module_list").empty();
		$("#module_list_loading").show();
		
		
		jQuery.ajax ({
			data: parameters,
			type: 'POST',
			url: "ajax.php",
			dataType: 'html',
			success: function (data) {
				$("#module_list_loading").hide();
				
				$("#module_list").empty();
				$("#module_list").html(data);
			}
		});
	}
</script>
<?php

ui_require_css_file ('cluetip');
ui_require_jquery_file ('cluetip');

echo "<div id='module_details_dialog' style='display: none;'></div>";

ui_include_time_picker();
ui_require_jquery_file("ui.datepicker-" . get_user_language(), "include/javascript/i18n/");
?>

<script type="text/javascript">
/* <![CDATA[ */
	$("a.tag_details").cluetip ({
			arrows: true,
			clickThrough: false,
			attribute: 'href',
			cluetipClass: 'default'
		});
	$("a.relations_details").cluetip ({
			width: 500,
			arrows: true,
			clickThrough: false,
			attribute: 'href',
			cluetipClass: 'default',
			sticky: true,
			mouseOutClose: 'both',
			closeText: '<?php html_print_image("images/cancel.png") ?>'
		});
		
	// Show the modal window of an module
	function show_module_detail_dialog(module_id, id_agent, server_name, offset, period,module_name) {
		var server_name = '';
		var extra_parameters = '';
		if ($('input[name=selection_mode]:checked').val()) {
			
			period = $('#period').val();
			
			var selection_mode = $('input[name=selection_mode]:checked').val();
			var date_from = $('#text-date_from').val();
			var time_from = $('#text-time_from').val();
			var date_to = $('#text-date_to').val();
			var time_to = $('#text-time_to').val();
			
			extra_parameters = '&selection_mode=' + selection_mode + '&date_from=' + date_from + '&date_to=' + date_to + '&time_from=' + time_from + '&time_to=' + time_to;
		}
		title = <?php echo "\"" . __("Module: ") . "\"" ?>;
		$.ajax({
			type: "POST",
			url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
			data: "page=include/ajax/module&get_module_detail=1&server_name="+server_name+"&id_agent="+id_agent+"&id_module=" + module_id+"&offset="+offset+"&period="+period + extra_parameters,
			dataType: "html",
			success: function(data) {
				$("#module_details_dialog").hide ()
					.empty ()
					.append (data)
					.dialog ({
						resizable: true,
						draggable: true,
						modal: true,
						title: title + module_name,
						overlay: {
							opacity: 0.5,
							background: "black"
						},
						width: 650,
						height: 500
					})
					.show ();
					refresh_pagination_callback (module_id, id_agent, "",module_name);
					datetime_picker_callback();
					forced_title_callback();
			}
		});
	}
	function datetime_picker_callback() {
		
		$("#text-time_from, #text-time_to").timepicker({
			showSecond: true,
			timeFormat: '<?php echo TIME_FORMAT_JS; ?>',
			timeOnlyTitle: '<?php echo __('Choose time');?>',
			timeText: '<?php echo __('Time');?>',
			hourText: '<?php echo __('Hour');?>',
			minuteText: '<?php echo __('Minute');?>',
			secondText: '<?php echo __('Second');?>',
			currentText: '<?php echo __('Now');?>',
			closeText: '<?php echo __('Close');?>'});
			
		$("#text-date_from, #text-date_to").datepicker({dateFormat: "<?php echo DATE_FORMAT_JS; ?>"});
		
		$.datepicker.setDefaults($.datepicker.regional[ "<?php echo get_user_language(); ?>"]);
	}
	datetime_picker_callback();
	
	function refresh_pagination_callback (module_id, id_agent, server_name,module_name) {
		$(".binary_dialog").click( function() {
			
			var classes = $(this).attr('class');
			classes = classes.split(' ');
			var offset_class = classes[2];
			offset_class = offset_class.split('_');
			var offset = offset_class[1];
			
			var period = $('#period').val();
			
			show_module_detail_dialog(module_id, id_agent, server_name, offset, period,module_name);
			return false;
		});
	}
/* ]]> */
</script>
<?php
function print_form_filter_monitors($id_agent, $status_filter_monitor = -1,
	$status_text_monitor = '', $status_module_group=-1) {
	
	$form_text = '';
	$table = new stdClass();
	$table->class = "databox filters";
	$table->width = "100%";
	$table->style[0] = 'font-weight: bold;';
	$table->style[2] = 'font-weight: bold;';
	$table->style[4] = 'font-weight: bold;';
	$table->data[0][0] = html_print_input_hidden('filter_monitors', 1, true);
	$table->data[0][0] .= html_print_input_hidden('monitors_change_filter', 1, true);
	$table->data[0][0] .= __('Status:');
	$status_list = array(
		-1 => __('All'),
		AGENT_MODULE_STATUS_CRITICAL_BAD => __('Critical'),
		AGENT_MODULE_STATUS_CRITICAL_ALERT => __('Alert'),
		AGENT_MODULE_STATUS_NORMAL => __('Normal'),
		AGENT_MODULE_STATUS_NOT_NORMAL => __('Not Normal'),
		AGENT_MODULE_STATUS_WARNING => __('Warning'),
		AGENT_MODULE_STATUS_UNKNOWN => __('Unknown'));
	
	$table->data[0][1] = html_print_select ($status_list,
		'status_filter_monitor', $status_filter_monitor, '', '', 0,
		true);
	
	$table->data[0][2] = __('Free text for search (*):') . ui_print_help_tip(
	__('Search by module name, list matches.'),true);
	
	$table->data[0][3] = html_print_input_text('status_text_monitor', $status_text_monitor, '', 30, 100, true);
	$table->data[0][4] = __('Module group');
	$rows = db_get_all_rows_sql("SELECT * FROM tmodule_group where id_mg in (SELECT id_module_group from tagente_modulo where id_agente = $id_agent )  ORDER BY name");
	
	$rows_select[-1] = __('All');
	if (!empty($rows)) {
		foreach ($rows as $module_group)
			$rows_select[$module_group['id_mg']] = __($module_group['name']);
	}
	$table->data[0][5] = html_print_select ($rows_select,'status_module_group', $status_module_group, '', '',0, true);
	$table->data[0][6] = html_print_button(__('Filter'), 'filter', false, 'filter_modules();', 'class="sub search"', true);
	$table->data[0][7] = '&nbsp;' . html_print_button(__('Reset'), 'filter', false, 'reset_filter_modules();', 'class="sub upd" style="margin-top:0px;"', true);
	$form_text .= html_print_table($table, true);
	
	$filter_hidden = false;
	
	if ($status_filter_monitor == -1 && $status_text_monitor == '' && $status_module_group == -1 ) {
		$filter_hidden = true;
	}
	
	echo $form_text;
}
?>
