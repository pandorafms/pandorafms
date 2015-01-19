<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

global $config;

$tab = get_parameter('tab', 'group');
$search = get_parameter('search', '');
$status = get_parameter('status', AGENT_STATUS_ALL);


// ---------------------Tabs -------------------------------------------
$enterpriseEnable = false;
if (enterprise_include_once('include/functions_policies.php') !== ENTERPRISE_NOT_HOOK) {
	$enterpriseEnable = true;
}

$url = 'index.php?' .
	'sec=estado&' .
	'sec2=operation/tree&' .
	'refr=0&' .
	'pure=' . (int)get_parameter('pure', 0) . '&' .
	'tab=%s';

$tabs = array();
$tabs['os'] = array(
	'text' => "<a href='" . sprintf($url, "os") . "'>" .
		html_print_image("images/operating_system.png", true,
			array("title" => __('OS'))) . "</a>",
	'active' => ($tab == "os"));

$tabs['group'] = array(
	'text' => "<a href='" . sprintf($url, "group") . "'>" .
		html_print_image("images/group.png", true,
			array("title" => __('Groups'))) . "</a>",
	'active' => ($tab == "group"));

$tabs['module_group'] = array(
	'text' => "<a href='" . sprintf($url, "module_group") . "'>" .
		html_print_image("images/module_group.png", true,
			array("title" => __('Module groups'))) . "</a>",
	'active' => ($tab == "module_group"));

$tabs['module'] = array(
	'text' => "<a href='" . sprintf($url, "module") . "'>" .
		html_print_image("images/brick.png", true,
			array("title" => __('Modules'))) . "</a>",
	'active' => ($tab == "module"));

$tabs['tag'] = array(
	'text' => "<a href='" . sprintf($url, "tag") . "'>" .
		html_print_image("images/tag.png", true,
			array("title" => __('Tags'))) . "</a>",
	'active' => ($tab == "tag"));

if ($enterpriseEnable) {
	$tabs['policies'] = array(
		'text' => "<a href='" . sprintf($url, "policies") . "'>" .
			html_print_image("images/policies_mc.png", true,
				array("title" => __('Policies'))) . "</a>",
		'active' => ($tab == "policies"));
}

$header_title = "";
switch ($tab) {
	case 'os':
		$header_title =
			__('Tree view - Sort the agents by OS');
		break;
	case 'group':
		$header_title =
			__('Tree view - Sort the agents by groups');
		break;
	case 'module_group':
		$header_title =
			__('Tree view - Sort the agents by module groups');
		break;
	case 'module':
		$header_title =
			__('Tree view - Sort the agents by modules');
		break;
	case 'tag':
		$header_title =
			__('Tree view - Sort the agents by tags');
		break;
}

ui_print_page_header(
	$header_title, "images/extensions.png", false, "", false, $tabs);
// ---------------------Tabs -------------------------------------------


// --------------------- form filter -----------------------------------
$table = null;
$table->width = "100%";

$table->data[0][0] = __('Agent status');
$fields = array ();
$fields[AGENT_STATUS_ALL] = __('All'); //default
$fields[AGENT_STATUS_NORMAL] = __('Normal'); 
$fields[AGENT_STATUS_WARNING] = __('Warning');
$fields[AGENT_STATUS_CRITICAL] = __('Critical');
$fields[AGENT_STATUS_UNKNOWN] = __('Unknown');
$fields[AGENT_STATUS_NOT_INIT] = __('Not init');
$table->data[0][1] = html_print_select($fields,
	"status",
	$status,
	'',
	'',
	0,
	true);
$table->data[0][2] = __('Search agent');
$table->data[0][3] = html_print_input_text(
	"search", $search, '', 40, 30, true);
$table->data[0][4] = html_print_submit_button(
	__('Filter'), "uptbutton", false, 'class="sub search"', true);

echo '<form id="tree_search" method="post" action="index.php?sec=monitoring&sec2=operation/tree&refr=0&tab='.$tab.'&pure='.$config['pure'].'">';
html_print_table($table);
echo '</form>';

// --------------------- form filter -----------------------------------
ui_include_time_picker();
ui_require_jquery_file("ui.datepicker-" . get_user_language(), "include/javascript/i18n/");

ui_require_javascript_file("TreeController", "include/javascript/tree/");
html_print_image('images/spinner.gif', false,
	array('class' => "loading_tree",
		'style' => 'display: none;'));
echo "<div id='tree-controller-recipient' style='float: left;'>";
echo "</div>";
echo "<div id='tree-controller-detail-recipient' style='float: right;'>";
echo "</div>";
?>
<script type="text/javascript">
	var treeController = TreeController.getController();
	
	processTreeSearch();
	
	$("form#tree_search").submit(function(e) {
		e.preventDefault();
	
		processTreeSearch();
	});
	
	function processTreeSearch () {
		// Clear the tree
		if (typeof treeController.recipient != 'undefined' && treeController.recipient.length > 0)
			treeController.recipient.empty();

		$(".loading_tree").show();
		
		var parameters = {};
		parameters['page'] = "include/ajax/tree.ajax";
		parameters['getChildren'] = 1;
		parameters['filter'] = {};
		parameters['type'] = "<?php echo $tab; ?>";
		parameters['filter']['searchAgent'] = $("input#text-search").val();
		parameters['filter']['status'] = $("select#status").val();
		parameters['childrenMethod'] = 'live';
		parameters['countModuleStatusMethod'] = 'live';
		parameters['countAgentStatusMethod'] = 'live';
		
		$.ajax({
			type: "POST",
			url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
			data: parameters,
			success: function(data) {
				if (data.success) {
					$(".loading_tree").hide();
					
					treeController.init({
						recipient: $("div#tree-controller-recipient"),
						detailRecipient: $("div#tree-controller-detail-recipient"),
						page: page,
						tree: data.tree,
						baseURL: "<?php echo ui_get_full_url('', false, false, false); ?>",
						ajaxURL: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
						filter: {
							search: parameters['filter']['search'],
							status: parameters['filter']['status']
						},
						counterTitles: {
							total: {
								agents: "<?php echo __('Total agents'); ?>",
								modules: "<?php echo __('Total modules'); ?>",
								none: "<?php echo __('Total'); ?>"
							},
							fired: {
								agents: "<?php echo __('Fired alerts'); ?>",
								modules: "<?php echo __('Fired alerts'); ?>",
								none: "<?php echo __('Fired alerts'); ?>"
							},
							critical: {
								agents: "<?php echo __('Critical agents'); ?>",
								modules: "<?php echo __('Critical modules'); ?>",
								none: "<?php echo __('Critical'); ?>"
							},
							warning: {
								agents: "<?php echo __('Warning agents'); ?>",
								modules: "<?php echo __('Warning modules'); ?>",
								none: "<?php echo __('Warning'); ?>"
							},
							unknown: {
								agents: "<?php echo __('Unknown agents'); ?>",
								modules: "<?php echo __('Unknown modules'); ?>",
								none: "<?php echo __('Unknown'); ?>"
							},
							not_init: {
								agents: "<?php echo __('Not init agents'); ?>",
								modules: "<?php echo __('Not init modules'); ?>",
								none: "<?php echo __('Not init'); ?>"
							},
							ok: {
								agents: "<?php echo __('Normal agents'); ?>",
								modules: "<?php echo __('Normal modules'); ?>",
								none: "<?php echo __('Normal'); ?>"
							}
						}
					});
				}
			},
			dataType: "json"
		});
	}

	// Show the modal window of an module
	var moduleDetailsWindow = $("<div></div>");
	moduleDetailsWindow
		.hide()
		.prop("id", "module_details_window")
		.appendTo('body');

	function show_module_detail_dialog(module_id, id_agent, server_name, offset, period) {
		var params = {};

		if (period == -1) {
			period = $('#period').val();
			params.selection_mode = $('input[name=selection_mode]:checked').val();
			params.date_from = $('#text-date_from').val();
			params.time_from = $('#text-time_from').val();
			params.date_to = $('#text-date_to').val();
			params.time_to = $('#text-time_to').val();
		}

		params.page = "include/ajax/module";
		params.get_module_detail = 1;
		params.server_name = server_name;
		params.id_agent = id_agent;
		params.id_module = module_id;
		params.offset = offset;
		params.period = period;
		
		$.ajax({
			type: "POST",
			url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
			data: params,
			dataType: "html",
			success: function(data) {
				$("#module_details_window").hide ()
					.empty ()
					.append (data)
					.dialog ({
						resizable: true,
						draggable: true,
						modal: true,
						overlay: {
							opacity: 0.5,
							background: "black"
						},
						width: 650,
						height: 500
					})
					.show ();
					refresh_pagination_callback(module_id, id_agent, server_name);
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
			
		$.datepicker.setDefaults($.datepicker.regional[ "<?php echo get_user_language(); ?>"]);
		$("#text-date_from, #text-date_to").datepicker({dateFormat: "<?php echo DATE_FORMAT_JS; ?>"});
		
	}
	
	function refresh_pagination_callback (module_id, id_agent, server_name) {
		
		$(".binary_dialog").click( function() {
			
			var classes = $(this).attr('class');
			classes = classes.split(' ');
			var offset_class = classes[2];
			offset_class = offset_class.split('_');
			var offset = offset_class[1];
			
			var period = $('#period').val();
			
			show_module_detail_dialog(module_id, id_agent, server_name, offset, period);
			return false;
		});
	}

</script>