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

if (! check_acl ($config['id_user'], 0, "PM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Agent Management");
	require ("general/noaccess.php");
	return;
}

echo "<script type='text/javascript' src='include/javascript/d3.3.5.14.js'></script>" . "\n";

function push_table_row ($row, $id = false) {
	global $table;
	
	if ($id)
		$data = array ($id => $row);
	else
		$data = array ($row);
	
	$table->data = array_merge ($table->data, $data);
}


$table->id = 'network_component';
$table->width = '100%';
$table->class = 'databox';
$table->style = array ();
$table->style[0] = 'font-weight: bold';
$table->style[2] = 'font-weight: bold';
$table->colspan = array ();
if(!enterprise_installed()) {
	$table->colspan[0][1] = 3;
}
$table_simple->colspan[7][1] = 4;
$table_simple->colspan[8][1] = 4;
$table_simple->colspan[9][1] = 4;
$table->data = array ();

$table->data[0][0] = __('Name');
$table->data[0][1] = html_print_input_text ('name', $name, '', 55, 255, true);
if (enterprise_installed()) {
	if(defined('METACONSOLE')) {
		$table->data[0][2] = __('Wizard level');
		$wizard_levels = array('basic' => __('Basic'),
			'advanced' => __('Advanced'));
		$table->data[0][3] = html_print_select($wizard_levels,'wizard_level',$wizard_level,'','',-1,true, false, false). ' ' .ui_print_help_icon ('meta_access', true);
	}
	else {
		$table->data[0][2] = '';
		$table->data[0][3] = html_print_input_hidden('wizard_level', $wizard_level, true);
	}
}

$table->data[1][0] = __('Type') . ' ' . ui_print_help_icon ('module_type', true);
$sql = sprintf ('SELECT id_tipo, descripcion
	FROM ttipo_modulo
	WHERE categoria IN (%s)
	ORDER BY descripcion',
	implode (',', $categories));
$table->data[1][1] = html_print_select_from_sql ($sql, 'type',
	$type, 'javascript: type_change();', '', '', true,
	false, false, false, true, false, false, false, 0);

// Store the relation between id and name of the types on a hidden field
$sql = sprintf ('SELECT id_tipo, nombre
		FROM ttipo_modulo
		WHERE categoria IN (%s)
		ORDER BY descripcion',
		implode (',', $categories));
$type_names = db_get_all_rows_sql($sql);

$type_names_hash = array();
foreach($type_names as $tn) {
	$type_names_hash[$tn['id_tipo']] = $tn['nombre'];
}

$table->data[1][1] .= html_print_input_hidden('type_names',
	base64_encode(json_encode($type_names_hash)),true);

$table->data[1][2] = __('Module group');
$table->data[1][3] = html_print_select_from_sql ('SELECT id_mg, name
	FROM tmodule_group ORDER BY name',
	'id_module_group', $id_module_group, '', '', '', true, false, false,
	false, true, false, false, false, 0);

$table->data[2][0] = __('Group');
$table->data[2][1] = html_print_select (network_components_get_groups (),
	'id_group', $id_group, '', '', '', true, false, false);
$table->data[2][2] = __('Interval');
$table->data[2][3] = html_print_extended_select_for_time ('module_interval' , $module_interval, '', '', '0', false, true);

$table->data[3][0] = __('Dynamic Interval') .' ' . ui_print_help_icon ('dynamic_threshold', true);
$table->data[3][1] = html_print_extended_select_for_time ('dynamic_interval', $dynamic_interval, '', 'None', '0', 10, true, 'width:150px',false);
$table->data[3][1] .= '<a onclick=advanced_option_dynamic()>' . html_print_image('images/cog.png', true, array('title' => __('Advanced options Dynamic Threshold'))) . '</a>';

$table->data[3][2] = '<span><em>'.__('Dynamic Min. ').'</em>';
$table->data[3][2] .= html_print_input_text ('dynamic_min', $dynamic_min, '', 10, 255, true);
$table->data[3][2] .= '<br /><em>'.__('Dynamic Max.').'</em>';
$table->data[3][2] .= html_print_input_text ('dynamic_max', $dynamic_max, '', 10, 255, true);
$table->data[3][3] = '<span><em>'.__('Dynamic Two Tailed: ').'</em>';
$table->data[3][3] .= html_print_checkbox ("dynamic_two_tailed", 1, $dynamic_two_tailed, true);

$table->data[4][0] = __('Warning status') . ' ' . ui_print_help_icon ('warning_status', true);
$table->data[4][1] = '<span id="minmax_warning"><em>'.__('Min.').'&nbsp;</em>&nbsp;';
$table->data[4][1] .= html_print_input_text ('min_warning', $min_warning,
	'', 5, 15, true);
$table->data[4][1] .= '<br /><em>'.__('Max.').'</em>&nbsp;';
$table->data[4][1] .= html_print_input_text ('max_warning', $max_warning,
	'', 5, 15, true) . '</span>';
$table->data[4][1] .= '<span id="string_warning"><em>'.__('Str.').' </em>&nbsp;';
$table->data[4][1] .= html_print_input_text ('str_warning', $str_warning,
	'', 5, 15, true) . '</span>';
$table->data[4][1] .= '<br /><em>'.__('Inverse interval').'</em>';
$table->data[4][1] .= html_print_checkbox ("warning_inverse", 1, $warning_inverse, true);

$table->data[4][2] = '<svg id="svg_dinamic" width="350" height="200" style="padding:40px; padding-left: 100px; margin-bottom: 60px;"> </svg>';
$table->colspan[4][2] = 2;
$table->rowspan[4][2] = 3;

$table->data[5][0] = __('Critical status'). ' ' . ui_print_help_icon ('critical_status', true);
$table->data[5][1] = '<span id="minmax_critical"><em>'.__('Min.').'&nbsp;</em>&nbsp;';
$table->data[5][1] .= html_print_input_text ('min_critical', $min_critical,
	'', 5, 15, true);
$table->data[5][1] .= '<br /><em>'.__('Max.').'</em>&nbsp;';
$table->data[5][1] .= html_print_input_text ('max_critical', $max_critical,
	'', 5, 15, true) . '</span>';
$table->data[5][1] .= '<span id="string_critical"><em>'.__('Str.').' </em>&nbsp;';
$table->data[5][1] .= html_print_input_text ('str_critical', $str_critical,
	'', 5, 15, true) . '</span>';
$table->data[5][1] .= '<br /><em>'.__('Inverse interval').'</em>';
$table->data[5][1] .= html_print_checkbox ("critical_inverse", 1, $critical_inverse, true);

$table->data[6][0] = __('FF threshold') . ' ' . ui_print_help_icon ('ff_threshold', true);
$table->colspan[6][1] = 3;
$table->data[6][1] = html_print_radio_button ('each_ff', 0, '', $each_ff, true) . ' ' . __('All state changing') . ' : ';
$table->data[6][1] .= html_print_input_text ('ff_event', $ff_event,
	'', 5, 15, true) . '<br />';
$table->data[6][1] .= html_print_radio_button ('each_ff', 1, '', $each_ff, true) . ' ' . __('Each state changing') . ' : ';
$table->data[6][1] .= __('To normal');
$table->data[6][1] .= html_print_input_text ('ff_event_normal', $ff_event_normal, '', 5, 15, true) . ' ';
$table->data[6][1] .= __('To warning');
$table->data[6][1] .= html_print_input_text ('ff_event_warning', $ff_event_warning, '', 5, 15, true) . ' ';
$table->data[6][1] .= __('To critical');
$table->data[6][1] .= html_print_input_text ('ff_event_critical', $ff_event_critical, '', 5, 15, true);

$table->data[7][0] = __('Historical data');
$table->data[7][1] = html_print_checkbox ("history_data", 1, $history_data, true);

$table->data[8][0] = __('Min. Value');
$table->data[8][1] = html_print_input_text ('min', $min, '', 5, 15, true). ' ' . ui_print_help_tip (__('Any value below this number is discarted'), true);
$table->data[8][2] = __('Max. Value');
$table->data[8][3] = html_print_input_text ('max', $max, '', 5, 15, true) . ' ' . ui_print_help_tip (__('Any value over this number is discarted'), true);
$table->data[9][0] = __('Unit');
$table->data[9][1] = html_print_input_text ('unit', $unit, '', 12, 25, true);

$table->data[9][2] = __('Discard unknown events');
$table->data[9][3] = html_print_checkbox('throw_unknown_events', 1,
	network_components_is_disable_type_event($id, EVENTS_GOING_UNKNOWN), true);

$table->data[10][0] = __('Critical instructions'). ui_print_help_tip(__("Instructions when the status is critical"), true);
$table->data[10][1] = html_print_textarea ('critical_instructions', 2, 65, $critical_instructions, '', true);
$table->colspan[10][1] = 3;

$table->data[11][0] = __('Warning instructions'). ui_print_help_tip(__("Instructions when the status is warning"), true);
$table->data[11][1] = html_print_textarea ('warning_instructions', 2, 65, $warning_instructions, '', true);
$table->colspan[11][1] = 3;

$table->data[12][0] = __('Unknown instructions'). ui_print_help_tip(__("Instructions when the status is unknown"), true);
$table->data[12][1] = html_print_textarea ('unknown_instructions', 2, 65, $unknown_instructions, '', true);
$table->colspan[12][1] = 3;

$next_row = 13;

if (check_acl ($config['id_user'], 0, "PM")) {
	$table->data[$next_row][0] = __('Category');
	$table->data[$next_row][1] = html_print_select(categories_get_all_categories('forselect'), 'id_category', $id_category, '', __('None'), 0, true);
	$table->data[$next_row][2] = $table->data[$next_row][3] = $table->data[$next_row][4] = '';
	$next_row++;
}
else {
	// Store in a hidden field if is not visible to avoid delete the value
	$table->data[12][1] .= html_print_input_hidden ('id_category', $id_category, true);
}

$table->data[$next_row][0] =  __('Tags');

if ($tags == '') {
	$tags_condition_not = '1 = 1';
	$tags_condition_in = '1 = 0';
}
else {
	$tags = str_replace(",", "','", $tags);
	$tags_condition_not = "name NOT IN ('".$tags."')";
	$tags_condition_in = "name IN ('".$tags."')";
}

$table->data[$next_row][1] = '<b>' . __('Tags available') . '</b><br>';
$table->data[$next_row][1] .= html_print_select_from_sql (
	"SELECT name AS name1, name AS name2
	FROM ttag 
	WHERE $tags_condition_not
	ORDER BY name", 'id_tag_available[]', '', '','','',
	true, true, false, false, 'width: 200px', '5');
$table->data[$next_row][2] =  html_print_image('images/darrowright.png', true, array('id' => 'right', 'title' => __('Add tags to module'))); //html_print_input_image ('add', 'images/darrowright.png', 1, '', true, array ('title' => __('Add tags to module')));
$table->data[$next_row][2] .= '<br><br><br><br>' . html_print_image('images/darrowleft.png', true, array('id' => 'left', 'title' => __('Delete tags to module'))); //html_print_input_image ('add', 'images/darrowleft.png', 1, '', true, array ('title' => __('Delete tags to module')));

$table->data[$next_row][3] = '<b>' . __('Tags selected') . '</b><br>';
$table->data[$next_row][3] .=  html_print_select_from_sql (
	"SELECT name AS name1, name AS name2
	FROM ttag 
	WHERE $tags_condition_in
	ORDER BY name",
	'id_tag_selected[]', '', '','','', true, true, false,
	false, 'width: 200px', '5');
	
$next_row++;
?>
<script type="text/javascript">
	$(document).ready (function () {
		$("#type").change(function () {
			var type_selected = $(this).val();
			var type_names = jQuery.parseJSON(Base64.decode($('#hidden-type_names').val()));
			
			var type_name_selected = type_names[type_selected];
			
			if (type_name_selected.match(/_string$/) == null) {
				// Numeric types
				$('#string_critical').hide();
				$('#string_warning').hide();
				$('#minmax_critical').show();
				$('#minmax_warning').show();
			}
			else {
				// String types
				$('#string_critical').show();
				$('#string_warning').show();
				$('#minmax_critical').hide();
				$('#minmax_warning').hide();
			}
		});
		
		$("#type").trigger('change');

		//Dynamic_interval;
		disabled_status();
		$('#dynamic_interval_select').change (function() {
			disabled_status();
		});

		//Dynamic_options_advance;
		$('#network_component-3-2').hide();
		$('#network_component-3-3').hide();

		//paint graph stutus critical and warning:
		paint_graph_values();
		$('#text-min_warning').on ('input', function() {
			paint_graph_values();
			if (isNaN($('#text-min_warning').val()) && !($('#text-min_warning').val() == "-")){
				$('#text-min_warning').val(0);
			}
		});
		$('#text-max_warning').on ('input', function() {
			paint_graph_values();
			if (isNaN($('#text-max_warning').val()) && !($('#text-max_warning').val() == "-")){
				$('#text-max_warning').val(0);
			}
		});
		$('#text-min_critical').on ('input', function() {
			paint_graph_values();
			if (isNaN($('#text-min_critical').val()) && !($('#text-min_critical').val() == "-")){
				$('#text-min_critical').val(0);
			}
		});
		$('#text-max_critical').on ('input', function() {
			paint_graph_values();
			if (isNaN($('#text-max_critical').val()) && !($('#text-max_critical').val() == "-")){
				$('#text-max_critical').val(0);
			}
		});
		$('#checkbox-warning_inverse').change (function() {
			paint_graph_values();
		});
		$('#checkbox-critical_inverse').change (function() {
			paint_graph_values();
		});
	});

	//readonly and add class input
	function disabled_status () {
		if($('#dynamic_interval_select').val() != 0){
			$('#text-min_warning').prop('readonly', true);
			$('#text-min_warning').addClass('readonly');
			$('#text-max_warning').prop('readonly', true);
			$('#text-max_warning').addClass('readonly');
			$('#text-min_critical').prop('readonly', true);
			$('#text-min_critical').addClass('readonly');
			$('#text-max_critical').prop('readonly', true);
			$('#text-max_critical').addClass('readonly');
		} else {
			$('#text-min_warning').prop('readonly', false);
			$('#text-min_warning').removeClass('readonly');
			$('#text-max_warning').prop('readonly', false);
			$('#text-max_warning').removeClass('readonly');
			$('#text-min_critical').prop('readonly', false);
			$('#text-min_critical').removeClass('readonly');
			$('#text-max_critical').prop('readonly', false);
			$('#text-max_critical').removeClass('readonly');
		}
	}

	//Dynamic_options_advance;
	function advanced_option_dynamic() {
		if($('#network_component-3-2').is(":visible")){
			$('#network_component-3-2').hide();
			$('#network_component-3-3').hide();
		} else {
			$('#network_component-3-2').show();
			$('#network_component-3-3').show();
		}
	}

	//function paint graph
	function paint_graph_values(){
		//Parse integrer
		var min_w = parseInt($('#text-min_warning').val());
			if(min_w == '0.00'){ min_w = 0; }
		var max_w = parseInt($('#text-max_warning').val());
			if(max_w == '0.00'){ max_w = 0; }
		var min_c = parseInt($('#text-min_critical').val());
			if(min_c =='0.00'){ min_c = 0; }
		var max_c = parseInt($('#text-max_critical').val());
			if(max_c =='0.00'){ max_c = 0; }
		var inverse_w = $('input:checkbox[name=warning_inverse]:checked').val();
			if(!inverse_w){ inverse_w = 0; }
		var inverse_c = $('input:checkbox[name=critical_inverse]:checked').val();
			if(!inverse_c){ inverse_c = 0; }
		//inicialiced error
		var error_w = 0;
		var error_c = 0;
		//if haven't error
		if(max_w == 0 || max_w > min_w){
			if(max_c == 0 || max_c > min_c){
				paint_graph_status(min_w, max_w, min_c, max_c, inverse_w, inverse_c, error_w, error_c);
			} else {
				error_c = 1;
				paint_graph_status(0,0,0,0,0,0, error_w, error_c);
			}
		} else {
			error_w = 1;
			paint_graph_status(0,0,0,0,0,0, error_w, error_c);
		}
	}

	//function use d3.js for paint graph
	function paint_graph_status(min_w, max_w, min_c, max_c, inverse_w, inverse_c, error_w, error_c) {
		
		//Check if they are numbers
		if(isNaN(min_w)){ min_w = 0; };
		if(isNaN(max_w)){ max_w = 0; };
		if(isNaN(min_c)){ min_c = 0; };
		if(isNaN(max_c)){ max_c = 0; };

		//messages legend
		var legend_normal = '<?php echo __("Normal Status");?>';
		var legend_warning = '<?php echo __("Warning Status");?>';
		var legend_critical = '<?php echo __("Critical Status");?>';

		//remove elements
		d3.select("#svg_dinamic rect").remove();
		$("#text-max_warning").removeClass("input_error");
		$("#text-max_critical").removeClass("input_error");

		//if haven't errors
		if (error_w == 0 && error_c == 0){
			//parse element
			min_w = parseInt(min_w);
			min_c = parseInt(min_c);
			max_w = parseInt(max_w);
			max_c = parseInt(max_c);
			
			//inicialize var
			var range_min = 0;
			var range_max = 0;
			var range_max_min = 0;
			var range_max_min = 0;
			
			//Find the lowest possible value
			if(min_w < 0 || min_c < 0){
				if(min_w < min_c){
					range_min = min_w - 100;
				} else {
					range_min = min_c - 100;	
				}
			} else if (min_w > 0 || min_c > 0) {
				if(min_w > min_c){
					range_max_min = min_w;
				} else {
					range_max_min = min_c;	
				}
			} else {
				if(min_w < min_c){
					range_min = min_w - 100;
				} else {
					range_min = min_c - 100;	
				}
			}

			//Find the maximum possible value
			if(max_w > max_c){
				range_max = max_w + 100 + range_max_min;
			} else {
				range_max = max_c + 100 + range_max_min;
			}
			
			//Controls whether the maximum = 0 is infinite
			if((max_w == 0 || max_w == 0.00) && min_w != 0){
				max_w = range_max;
			}
			if((max_c == 0 || max_c == 0.00) && min_c != 0){
				max_c = range_max;
			}
			
			//Scale according to the position
			position = 200 / (range_max-range_min);
			
			//axes
			var yScale = d3.scale.linear()
			    .domain([range_min, range_max])
			    .range([100, -100]);

		    var yAxis = d3.svg.axis()
		            .orient("left")
		            .scale(yScale);

		    //create svg
			var svg = d3.select("#svg_dinamic");
			//delete elements
			svg.selectAll("g").remove();
			svg.selectAll("rect").remove();
			svg.selectAll("text").remove();
			svg.append("g")
				.attr("transform", "translate(0, 100)")
				.call(yAxis);
			
			//legend Normal text
			svg.append("text")
					.attr("x", 0)
					.attr("y", -20)
					.attr("fill", 'black')
					.style("font-family", "arial")
					.style("font-weight", "bold")
					.style("font-size", 10)
					.html(legend_normal)
					.style("text-anchor", "first");

			//legend Normal rect
			svg.append("rect")
				.attr("id", "legend_normal")
		       	.attr("x", 72)
		       	.attr("y", -30)
		       	.attr("width", 10)
		       	.attr("height", 10)
		  		.style("fill", "#82B92E");

		  	//legend Warning text
			svg.append("text")
					.attr("x", 91)
					.attr("y", -20)
					.attr("fill", 'black')
					.style("font-family", "arial")
					.style("font-weight", "bold")
					.style("font-size", 10)
					.html(legend_warning)
					.style("text-anchor", "first");

			//legend Warning rect
			svg.append("rect")
				.attr("id", "legend_warning")
		       	.attr("x", 168)
		       	.attr("y", -30)
		       	.attr("width", 10)
		       	.attr("height", 10)
		  		.style("fill", "#ffd731");

		  	//legend Critical text
			svg.append("text")
					.attr("x", 187)
					.attr("y", -20)
					.attr("fill", 'black')
					.style("font-family", "arial")
					.style("font-weight", "bold")
					.style("font-size", 10)
					.html(legend_critical)
					.style("text-anchor", "first");

			//legend critical rect
			svg.append("rect")
				.attr("id", "legend_critical")
		       	.attr("x", 258)
		       	.attr("y", -30)
		       	.attr("width", 10)
		       	.attr("height", 10)
		  		.style("fill", "#fc4444");

			//styles for number and axes
		    svg.selectAll("g .domain")
		    	.style("stroke-width",  2)
		    	.style("fill",  "none")
		    	.style("stroke", "black");

		    svg.selectAll("g .tick text")
		    	.style("font-size", "9pt")
				.style("font-weight", "initial");

		    //estatus normal
			svg.append("rect")
				.attr("id", "warning_rect")
		       	.attr("x", 3)
		       	.attr("y", 0)
		       	.attr("width", 300)
		       	.attr("height", 200)
		  		.style("fill", "#82B92E");
		  	
		  	//controls the inverse warning
		  	if(inverse_w == 0){
				svg.append("rect").transition()
					.duration(600)
					.attr("id", "warning_rect")
			       	.attr("x", 3)
			       	.attr("y", ((range_max - min_w) * position) - ((max_w - min_w) * position))
			       	.attr("width", 300)
			       	.attr("height", ((max_w - min_w) * position))
			  		.style("fill", "#ffd731");
		  	}
		  	else {
		  		svg.append("rect").transition()
		  			.duration(600)
					.attr("id", "warning_rect")
			       	.attr("x", 3)
			       	.attr("y", 200 - ((min_w -range_min) * position))
			       	.attr("width", 300)
			       	.attr("height", (min_w -range_min) * position)
			  		.style("fill", "#ffd731");
			  	
			  	svg.append("rect").transition()
			  		.duration(600)
					.attr("id", "warning_inverse_rect")
			       	.attr("x", 3)
			       	.attr("y",  0)
			       	.attr("width", 300)
			       	.attr("height", ((range_max - min_w) * position) - ((max_w - min_w) * position))
			  		.style("fill", "#ffd731");
			  	
		  	}
		  	//controls the inverse critical
		  	if(inverse_c == 0){
			    svg.append("rect").transition()
			    	.duration(600)
			    	.attr("id", "critical_rect")
			       	.attr("x", 3)
			       	.attr("y", ((range_max - min_c) * position) - ((max_c - min_c) * position))
			       	.attr("width", 300)
			       	.attr("height", ((max_c - min_c) * position))
			  		.style("fill", "#fc4444");
		  	}
		  	else {
		  		svg.append("rect").transition()
					.duration(600)
					.attr("id", "critical_rect")
			       	.attr("x", 3)
			       	.attr("y", 200 - ((min_c -range_min) * position))
			       	.attr("width", 300)
			       	.attr("height", (min_c -range_min) * position)
			  		.style("fill", "#fc4444");
			  	
			  	svg.append("rect").transition()
					.duration(600)
					.attr("id", "critical_inverse_rect")
			       	.attr("x", 3)
			       	.attr("y", 0)
			       	.attr("width", 300)
			       	.attr("height", ((range_max - min_c) * position) - ((max_c - min_c) * position))
			  		.style("fill", "#fc4444");
		  	}
		}
		else {
			var message_error_warning = '<?php echo __("Please introduce a maximum warning higher than the minimun warning") ?>';
			var message_error_critical = '<?php echo __("Please introduce a maximum critical higher than the minimun critical") ?>';

			d3.select("#svg_dinamic rect").remove();
			//create svg
			var svg = d3.select("#svg_dinamic");
			svg.selectAll("g").remove();
			svg.selectAll("rect").remove();
			svg.selectAll("text").remove();
			//message error warning
			if (error_w == 1) {
				$("#text-max_warning").addClass("input_error");
				svg.append("text")
					.attr("x", -90)
					.attr("y", 10)
					.attr("fill", 'black')
					.style("font-family", "arial")
					.style("font-weight", "bold")
					.style("font-size", 14)
					.style("fill", "red")
					.html(message_error_warning)
					.style("text-anchor", "first");
			}
			//message error critical
			if (error_c == 1) {
				$("#text-max_critical").addClass("input_error");
				svg.append("text")
					.attr("x", -90)
					.attr("y", 105)
					.attr("fill", 'black')
					.style("font-family", "arial")
					.style("font-weight", "bold")
					.style("font-size", 14)
					.style("fill", "red")
					.html(message_error_critical)
					.style("text-anchor", "first");	
			}

		}
	}
</script>
