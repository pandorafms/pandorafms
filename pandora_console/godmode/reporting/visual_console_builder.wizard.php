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
global $config;

check_login ();

if (! check_acl ($config['id_user'], 0, "IW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access report builder");
	require ("general/noaccess.php");
	exit;
}

require_once('godmode/reporting/visual_console_builder.constans.php');
require_once ('include/functions_visual_map.php');
require_once($config['homedir'] . "/include/functions_agents.php");

$table->id = 'wizard_table';
$table->width = '98%';
$table->data = array ();
$table->style = array ();
$table->style[0] = 'font-weight: bold; vertical-align:top';
$table->style[2] = 'font-weight: bold';
$table->size = array ();
$table->data = array ();

$images_list = array ();
$all_images = list_files ('images/console/icons/', "png", 1, 0);
foreach ($all_images as $image_file) {
	if (strpos ($image_file, "_bad"))
		continue;
	if (strpos ($image_file, "_ok"))
		continue;
	if (strpos ($image_file, "_warning"))
		continue;
	$image_file = substr ($image_file, 0, strlen ($image_file) - 4);
	$images_list[$image_file] = $image_file;
}

$type_list = array(
	STATIC_GRAPH => __('Static Graph'),
	PERCENTILE_BAR => __('Percentile Item'),
	MODULE_GRAPH => __('Module graph'),
	SIMPLE_VALUE => __('Simple value')
);

$table->rowstyle["all_0"] = 'display: none;';
$table->data["all_0"][0] = __('Type');
$table->data["all_0"][1] = html_print_select ($type_list, 'type', '', 'hidden_rows()', '', '', true);

$table->rowstyle["staticgraph"] = 'display: none;';
$table->data["staticgraph"][0] = __('Image');
$table->data["staticgraph"][1] = html_print_select ($images_list, 'image', '', '', '', '', true);

$table->rowstyle["all_1"] = 'display: none;';
$table->data["all_1"][0] = __('Range between elements (px)');
$table->data["all_1"][1] = html_print_input_text ('range', 50, '', 5, 5, true);

$table->rowstyle["staticgraph_modulegraph"] = 'display: none;';
$table->data["staticgraph_modulegraph"][0] = __('Size (px)');
$table->data["staticgraph_modulegraph"][1] = __('Width').': '.html_print_input_text ('width', 0, '', 5, 5, true);
$table->data["staticgraph_modulegraph"][1] .= '<br />'.__('Height').': '.html_print_input_text ('height', 0, '', 5, 5, true);

$table->rowstyle["modulegraph"] = 'display: none;';
$table->data["modulegraph"][0] = __('Period');
$table->data["modulegraph"][1] = html_print_extended_select_for_time ('period', '', '', '', '', false, true);

$table->rowstyle["simplevalue"] = 'display: none;';
$table->data["simplevalue"][0] = __('Process');
$table->data["simplevalue"][1] = html_print_select (
	array (SIMPLE_VALUE_MIN => __('Min value'), 
	SIMPLE_VALUE_MAX => __('Max value'),
	SIMPLE_VALUE_AVG => __('Avg value')), 'process_value', '', '', '', '', true);

$table->rowstyle["percentileitem_1"] = 'display: none;';
$table->data["percentileitem_1"][0] = __('Width (px)');
$table->data["percentileitem_1"][1] = html_print_input_text ('percentileitem_width', 0, '', 5, 5, true);

$table->rowstyle["percentileitem_2"] = 'display: none;';
$table->data["percentileitem_2"][0] = __('Max value');
$table->data["percentileitem_2"][1] = html_print_input_text ('max_value', 0, '', 5, 5, true);

$table->rowstyle["percentileitem_3"] = 'display: none;';
$table->data["percentileitem_3"][0] = __('Type');
$table->data["percentileitem_3"][1] = html_print_radio_button_extended('type_percentile', 'percentile', ('Percentile'), 'percentile', false, '', 'style="float: left;"', true) .
	html_print_radio_button_extended('type_percentile', 'bubble', ('Bubble'), 'percentile', false, '', 'style="float: left;"', true);

$table->rowstyle["percentileitem_4"] = 'display: none;';
$table->data["percentileitem_4"][0] = __('Value to show');
$table->data["percentileitem_4"][1] = html_print_radio_button_extended('value_show', 'percent', ('Percent'), 'value', false, '', 'style="float: left;"', true) .
	html_print_radio_button_extended('value_show', 'value', ('Value'), 'value', false, '', 'style="float: left;"', true);

$table->rowstyle["all_2"] = 'display: none;';
$table->data["all_2"][0] = __('Groups');
$table->data["all_2"][1] = html_print_select_groups($config['id_user'], "AR", true,
	'groups', '', '', '', 0, true); 

$table->rowstyle["all_3"] = 'display: none;';
$table->data["all_3"][0] = __('Agents');
$table->data["all_3"][1] = html_print_select (agents_get_group_agents (0, false, "none", false, true),
	'id_agents[]', 0, false, '', '', true, true);

$table->rowstyle["all_4"] = 'display: none;';
$table->data["all_4"][0] = __('Modules');
$table->data["all_4"][1] = html_print_select (array (), 'module[]', 0, false, __('None'), -1, true, true);

$table->rowstyle["all_5"] = 'display: none;';
$table->data["all_5"][0] = __('Label');
$label_type = array ('agent_module' => __('Agent - Module'),
	'module' => __('Module'),
	'agent' => __('Agent'),
	'none' => __('None'));
$table->data["all_5"][1] = html_print_select ($label_type, 'label_type', 'agent_module', '', '', '', true);

echo '<form method="post"
	action="index.php?sec=reporting&sec2=godmode/reporting/visual_console_builder&tab=' . $activeTab  . '&id_visual_console=' . $visualConsole["id"] . '"
	onsubmit="if (! confirm(\''.__('Are you sure to add many elements\nin visual map?').'\')) return false; else return check_fields();">';
$table->size[0] = "20%";
$table->size[1] = "80%";
html_print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
html_print_input_hidden ('action', 'update');
html_print_input_hidden ('id_visual_console', $visualConsole["id"]);
html_print_submit_button (__('Add'), 'go', false, 'class="sub wizard wand"');
echo '</div>';
echo '</form>';

//Trick for it have a traduct text for javascript.
echo '<span id="any_text" style="display: none;">' . __('Any') . '</span>';
echo '<span id="none_text" style="display: none;">' . __('None') . '</span>';
echo '<span id="loading_text" style="display: none;">' . __('Loading...') . '</span>';
?>
<script type="text/javascript">
var show_only_enabled_modules = true;
$(document).ready (function () {
	hidden_rows();
	
	$("#groups").change (function () {
		$('#id_agents').attr('disabled', true);
		$('#id_agents').empty ();
		$('#id_agents').append ($('<option></option>').html($("#loading_text").html()));
		$('#id_agents').css ("width", "auto");
		$('#id_agents').css ("max-width", "");
		
		var data_params = {"page": "include/ajax/agent",
			"get_agents_group": 1,
			"id_group": $("#groups").val(),
			"mode": "json"};
		
		jQuery.ajax ({
			data: data_params,
			async: false,
			type: 'POST',
			url: action="ajax.php",
			timeout: 10000,
			dataType: 'json',
			success: function (data) {
				$('#id_agents').empty ();
				
				if (isEmptyObject(data)) {
					var noneText = $("#none_text").html(); //Trick for catch the translate text.
					
					$('#id_agents').append ($('<option></option>').html (noneText).attr ("None", "").attr('value', -1).attr('selected', true));
				}
				else {
					jQuery.each (data, function (i, val) {
						s = js_html_entity_decode(val);
						$('#id_agents').append ($('<option></option>').html (s).attr("value", i));
					});
				}
				
				$('#id_agents').css ("width", "auto");
				$('#id_agents').css ("max-width", "");
				
				
				$('#id_agents').removeAttr('disabled');
				
				return;
			}
		});
		
		return;
	});
	$("#id_agents").change (agent_changed_by_multiple_agents);
});

function check_fields() {
	switch ($("#type").val()) {
		case "<?php echo PERCENTILE_BAR;?>":
		case "<?php echo MODULE_GRAPH;?>":
		case "<?php echo SIMPLE_VALUE;?>":
			if (($("#module").val() == "-1") || ($("#module").val() == null)) {
				alert("<?php echo __("Please select any module or modules.");?>");
				return false;
			}
			else {
				return true;
			}
			break;
		default:
			return true;
			break;
	}
}

function hidden_rows() {
	$("tr", "#wizard_table").hide(); //Hide all in the form table
	//Show the id ".*-all_.*"
	$("tr", "#wizard_table").filter(function () {return /^wizard_table\-all.*/.test(this.id); }).show();
	
	switch ($("#type").val()) {
		case "<?php echo STATIC_GRAPH;?>":
			$("tr", "#wizard_table").filter(function () {return /^.*staticgraph.*/.test(this.id); }).show();
			break;
		case "<?php echo PERCENTILE_BAR;?>":
			$("tr", "#wizard_table").filter(function () {return /^.*percentileitem.*/.test(this.id); }).show();
			break;
		case "<?php echo MODULE_GRAPH;?>":
			$("tr", "#wizard_table").filter(function () {return /^.*modulegraph.*/.test(this.id); }).show();
			break;
		case "<?php echo SIMPLE_VALUE;?>":
			$("tr", "#wizard_table").filter(function () {return /^.*simplevalue.*/.test(this.id); }).show();
			break;
	}
}
</script>
