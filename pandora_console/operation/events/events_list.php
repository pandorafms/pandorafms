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

require_once ($config['homedir']. "/include/functions_events.php"); //Event processing functions
require_once ($config['homedir']. "/include/functions_alerts.php"); //Alerts processing functions
require_once ($config['homedir']. "/include/functions.php");
require_once($config['homedir'] . "/include/functions_agents.php"); //Agents funtions
require_once($config['homedir'] . "/include/functions_users.php"); //Users functions
require_once ($config['homedir'] . '/include/functions_groups.php');

require_once ($config["homedir"] . '/include/functions_graph.php');
require_once ($config["homedir"] . '/include/functions_tags.php');

check_login ();

if (! check_acl ($config["id_user"], 0, "ER")) {
	db_pandora_audit("ACL Violation",
		"Trying to access event viewer");
	require ("general/noaccess.php");
	return;
}

if (is_ajax()) {
	$get_filter_values = get_parameter('get_filter_values', 0);
	$save_event_filter = get_parameter('save_event_filter', 0);
	$update_event_filter = get_parameter('update_event_filter', 0);
	$get_event_filters = get_parameter('get_event_filters', 0);
	
	// Get db values of a single filter
	if ($get_filter_values) {
		$id_filter = get_parameter('id');
		
		$event_filter = events_get_event_filter($id_filter);
		
		$event_filter['id_name'] = io_safe_output($event_filter['id_name']);
		$event_filter['tag_with'] = base64_encode(io_safe_output($event_filter['tag_with']));
		$event_filter['tag_without'] = base64_encode(io_safe_output($event_filter['tag_without']));
		
		echo json_encode($event_filter);
	}
	
	// Saves an event filter
	if ($save_event_filter) {
		$values = array();
		$values['id_name'] = get_parameter('id_name');
		$values['id_group'] = get_parameter('id_group'); 
		$values['event_type'] = get_parameter('event_type');
		$values['severity'] = get_parameter('severity');
		$values['status'] = get_parameter('status');
		$values['search'] = get_parameter('search');
		$values['text_agent'] = get_parameter('text_agent');
		$values['pagination'] = get_parameter('pagination');
		$values['event_view_hr'] = get_parameter('event_view_hr');
		$values['id_user_ack'] = get_parameter('id_user_ack');
		$values['group_rep'] = get_parameter('group_rep');
		$values['tag_with'] = get_parameter('tag_with', json_encode(array()));
		$values['tag_without'] = get_parameter('tag_without', json_encode(array()));
		$values['filter_only_alert'] = get_parameter('filter_only_alert');
		$values['id_group_filter'] = get_parameter('id_group_filter');
		
		$result = db_process_sql_insert('tevent_filter', $values);
		
		if ($result === false) {
			echo 'error';
		}
		else {
			echo $result;
		}
	}
	
	if ($update_event_filter) {
		$values = array();
		$id = get_parameter('id');
		$values['id_group'] = get_parameter('id_group'); 
		$values['event_type'] = get_parameter('event_type');
		$values['severity'] = get_parameter('severity');
		$values['status'] = get_parameter('status');
		$values['search'] = get_parameter('search');
		$values['text_agent'] = get_parameter('text_agent');
		$values['pagination'] = get_parameter('pagination');
		$values['event_view_hr'] = get_parameter('event_view_hr');	
		$values['id_user_ack'] = get_parameter('id_user_ack');
		$values['group_rep'] = get_parameter('group_rep');
		$values['tag_with'] = get_parameter('tag_with', json_encode(array()));
		$values['tag_without'] = get_parameter('tag_without', json_encode(array()));
		$values['filter_only_alert'] = get_parameter('filter_only_alert');
		$values['id_group_filter'] = get_parameter('id_group_filter');
		
		$result = db_process_sql_update('tevent_filter',
			$values, array('id_filter' => $id));
		
		if ($result === false) {
			echo 'error';
		}
		else {
			echo 'ok';
		}
	}
	
	if ($get_event_filters) {
		$event_filter = events_get_event_filter_select();
		
		echo json_encode($event_filter);
	}
	
	return;
}

// Get the tags where the user have permissions in Events reading tasks
$tags = tags_get_user_tags($config['id_user'], 'ER');

// Error div for ajax messages
echo "<div id='show_filter_error'>";
echo "</div>";

if ($id_agent == 0 && $text_agent != __('All')) {
	$id_agent = -1;
}

/////////////////////////////////////////////
// Build the condition of the events query

$sql_post = "";

$id_user = $config['id_user'];

require('events.build_query.php');

// Now $sql_post have all the where condition
/////////////////////////////////////////////

$id_name = get_parameter('id_name', '');

echo "<br>";

// Trick to catch if any filter button has been pushed (don't collapse filter)
// or the filter was open before click or autorefresh is in use (collapse filter)
$update_pressed = get_parameter_post('update', '');
$update_pressed = (int) !empty($update_pressed);

if ($update_pressed || $open_filter) {
	$open_filter = true;
}

$table = html_get_predefined_table('transparent', 2);
$table->width = '98%';
$table->style[0] = 'text-align:left;';
$table->style[1] = 'text-align:right;';

//Link to toggle filter
if ($open_filter) {
	$table->data[0][0] = '<a href="#" id="tgl_event_control"><b>' . __('Event control filter') . '</b>&nbsp;'.html_print_image ("images/go.png", true, array ("title" => __('Toggle filter(s)'), "id" => 'toggle_arrow')).'</a>';
}
else {
	$table->data[0][0] = '<a href="#" id="tgl_event_control"><b>' . __('Event control filter') . '</b>&nbsp;'.html_print_image ("images/down.png", true, array ("title" => __('Toggle filter(s)'), "id" => 'toggle_arrow')).'</a>';
}

$table->data[0][1] = '<a id="events_graph_link" href="javascript:show_events_graph_dialog()">' . html_print_image('images/chart_curve.png', true, array('title' => __('Show events graph'))) . '</a>';

html_print_table($table);

$filters = events_get_event_filter_select();

// Some translated words to be used from javascript
html_print_div(array('hidden' => true,
	'id' => 'not_filter_loaded_text', 'content' => __('No filter loaded')));
html_print_div(array('hidden' => true,
	'id' => 'filter_loaded_text', 'content' => __('Filter loaded')));
html_print_div(array('hidden' => true,
	'id' => 'save_filter_text', 'content' => __('Save filter')));
html_print_div(array('hidden' => true,
	'id' => 'load_filter_text', 'content' => __('Load filter')));

// Save filter div for dialog
echo '<div id="save_filter_layer" style="display: none">';
$table->id = 'save_filter_form';
$table->width = '98%';
$table->cellspacing = 4;
$table->cellpadding = 4;
$table->class = 'databox';
$table->styleTable = 'font-weight: bold; color: #555; text-align:left;';
$table->style[0] = 'width: 50%; width:50%;';

$data = array();
$table->rowid[0] = 'update_save_selector';
$data[0] = html_print_radio_button('filter_mode', 'new', '', true, true) . __('New filter') . '<br><br>';
$data[1] = html_print_radio_button('filter_mode', 'update', '', false, true) . __('Update filter') . '<br><br>';
$table->data[] = $data;
$table->rowclass[] = '';

$data = array();
$table->rowid[1] = 'save_filter_row1';
$data[0] = __('Filter name') . '<br>';
$data[0] .= html_print_input_text ('id_name', '', '', 15, 255, true);
$data[1] = __('Filter group') . '<br>';
$data[1] .= html_print_select_groups($config["id_user"], "ER", true, 'id_group', $id_group, '', '', 0, true, false, false, 'w130');
$table->data[] = $data;
$table->rowclass[] = '';

$data = array();
$table->rowid[2] = 'save_filter_row2';
$data[0] = html_print_submit_button (__('Save filter'), 'save_filter', false, 'class="sub upd"', true);
$table->colspan[2][0] = 2;
$table->cellstyle[2][0] = 'text-align:right;';
$table->data[] = $data;
$table->rowclass[] = '';

$data = array();
$table->rowid[3] = 'update_filter_row1';
$data[0] = __("Overwrite filter") . '<br>';
$data[0] .= html_print_select ($filters, "overwrite_filter", '', '', '', 0, true);
$data[1] = html_print_submit_button (__('Update filter'), 'update_filter', false, 'class="sub upd"', true);
$table->data[] = $data;
$table->rowclass[] = '';

html_print_table($table);
unset($table);
echo '</div>';

// Load filter div for dialog
echo '<div id="load_filter_layer" style="display: none">';
$table->id = 'load_filter_form';
$table->width = '98%';
$table->cellspacing = 4;
$table->cellpadding = 4;
$table->class = 'databox';
$table->styleTable = 'font-weight: bold; color: #555; text-align:left;';
$table->style[0] = 'width: 50%; width:50%;';

$data = array();
$table->rowid[3] = 'update_filter_row1';
$data[0] = __("Load filter") . '<br>';
$data[0] .= html_print_select ($filters, "filter_id", '', '', __('None'), 0, true);
$data[1] = html_print_submit_button (__('Load filter'), 'load_filter', false, 'class="sub upd"', true);
$table->data[] = $data;
$table->rowclass[] = '';

html_print_table($table);
unset($table);
echo '</div>';

// TAGS
$tags_select_with = array();
$tags_select_without = array();
$tag_with_temp = array();
$tag_without_temp = array();
foreach ($tags as $id_tag => $tag) {
	if (array_search($id_tag, $tag_with) === false) {
		$tags_select_with[$id_tag] = $tag;
	}
	else {
		$tag_with_temp[$id_tag] = $tag;
	}
	
	if (array_search($id_tag, $tag_without) === false) {
		$tags_select_without[$id_tag] = $tag;
	}
	else {
		$tag_without_temp[$id_tag] = $tag;
	}
}

$add_with_tag_disabled = empty($tags_select_with);
$remove_with_tag_disabled = empty($tag_with_temp);
$add_without_tag_disabled = empty($tags_select_without);
$remove_without_tag_disabled = empty($tag_without_temp);

$tabletags->id = 'filter_events_tags';
$tabletags->width = '100%';
$tabletags->cellspacing = 4;
$tabletags->cellpadding = 4;
$tabletags->class = '';
$tabletags->styleTable = 'border: 0px;';

$data = array();
$data[0] = __('Events with following tags') . '<br>';
$data[0] .= html_print_select ($tags_select_with, 'select_with', '', '', '', 0,
	true, false, true, '', false, 'width: 120px;') . '<br>';
$data[1] = __('Events without following tags') . '<br>';
$data[1] .= html_print_select ($tags_select_without, 'select_without', '', '', '', 0,
	true, false, true, '', false, 'width: 120px;') . '<br>';
$tabletags->data[] = $data;
$tabletags->rowclass[] = '';

$data = array();
$data[0] = html_print_button(__('Add'), 'add_with', $add_with_tag_disabled,
	'', 'class="add sub"', true);
$data[0] .= html_print_input_hidden('tag_with', $tag_with_base64, true);
$data[0] .= html_print_button(__('Remove'), 'remove_with', $remove_with_tag_disabled,
	'', 'class="delete sub"', true);

$data[1] = html_print_button(__('Add'), 'add_without', $add_without_tag_disabled,
	'', 'class="add sub"', true);
$data[1] .= html_print_input_hidden('tag_without', $tag_without_base64, true);
$data[1] .= html_print_button(__('Remove'), 'remove_without', $remove_without_tag_disabled,
	'', 'class="delete sub"', true);
$tabletags->data[] = $data;
$tabletags->rowclass[] = '';

$data = array();
$data[0] = html_print_select ($tag_with_temp, 'tag_with_temp', array(), '', '',
	0, true, true, true, '', false, "width: 120px; height: 50px;") . '<br>';
$data[1] = html_print_select ($tag_without_temp, 'tag_without_temp', array(), '', '',
	0, true, true, true, '', false, "width: 120px; height: 50px;") . '<br>';
$tabletags->data[] = $data;
$tabletags->rowclass[] = '';
// END OF TAGS

//Start div
echo '<div id="event_control" style="display:none">';

// Table for filter controls
echo '<form id="form_filter" method="post" action="index.php?sec=eventos&amp;sec2=operation/events/events&amp;refr='.$config["refr"].'&amp;pure='.$config["pure"].'&amp;section=' . $section . '&amp;history='.(int)$history.'">';

// Hidden field with the loaded filter name
html_print_input_hidden('id_name', $id_name);

// Hidden open filter flag
// If autoupdate is in use collapse filter
if ($open_filter) {
	html_print_input_hidden('open_filter', 'true');
} 
else{
	html_print_input_hidden('open_filter', 'false');
}

$table->id = 'events_filter_form';
$table->width = '99%';
$table->cellspacing = 4;
$table->cellpadding = 4;
$table->class = 'databox';
$table->styleTable = 'font-weight: bold; color: #555;';
$table->data = array();

$data = array();
$data[0] = __('Group') . '<br>';
$data[0] .= html_print_select_groups($config["id_user"], "ER", true,
	'id_group', $id_group, '', '', 0, true, false, false, 'w130');
$data[1] = __('Event type') . '<br>';
$types = get_event_types ();
// Expand standard array to add not_normal (not exist in the array, used only for searches)
$types["not_normal"] = __("Not normal");
$data[1] .= html_print_select ($types, 'event_type', $event_type, '', __('All'), '', true);
$data[2] = __('Severity') . '<br>';
$data[2] .= html_print_select (get_priorities (), "severity", $severity, '', __('All'), '-1', true, false, false);
$data[3] = '<fieldset class="databox" style="width:90%;"><legend>' . __('Tags') . '</legend>' . html_print_table($tabletags, true) . '</fieldset>';
$table->colspan[count($table->data)][3] = 2;
$table->rowspan[count($table->data)][3] = 4;
$table->data[] = $data;
$table->rowclass[] = '';

$data = array();
$data[0] = __('Event status') . '<br>';
$fields = events_get_all_status();
$data[0] .= html_print_select ($fields, 'status', $status, '', '', '', true);
$data[1] = __('Max. hours old') . '<br>';
$data[1] .= html_print_input_text ('event_view_hr', $event_view_hr, '', 5, 255, true);
$data[2] = __("Repeated") . '<br>';
$repeated_sel[0] = __("All events");
$repeated_sel[1] = __("Group events");
$data[2] .= html_print_select ($repeated_sel, "group_rep", $group_rep, '', '', 0, true);
$table->data[] = $data;
$table->rowclass[] = '';

$data = array();
$data[0] = __('Free search') . '<br>';
$data[0] .= html_print_input_text ('search', io_safe_output($search), '', 25, 255, true);
$data[1] = __('Agent search') . '<br>';
$params = array();
$params['show_helptip'] = true;
$params['input_name'] = 'text_agent';
$params['value'] = $text_agent;
$params['return'] = true;

if ($meta) {
	$params['javascript_page'] = 'enterprise/meta/include/ajax/events.ajax';
}
else {
	$params['print_hidden_input_idagent'] = true;
	$params['hidden_input_idagent_name'] = 'id_agent';
	$params['hidden_input_idagent_value'] = $id_agent;
}

$data[1] .= ui_print_agent_autocomplete_input($params);
$data[2] = __('User ack.') . '<br>';
$users = users_get_info ();
$data[2] .= html_print_select($users, "id_user_ack", $id_user_ack, '',
	__('Any'), 0, true);
$table->data[] = $data;
$table->rowclass[] = '';

$data = array();
$data[0] = __("Alert events") . '<br>';
$data[0] .= html_print_select (array('-1' => __('All'), '0' => __('Filter alert events'), '1' => __('Only alert events')), "filter_only_alert", $filter_only_alert, '', '', '', true);
$data[1] = __('Block size for pagination') . '<br>';
$lpagination[25] = 25;
$lpagination[50] = 50;
$lpagination[100] = 100;
$lpagination[200] = 200;
$lpagination[500] = 500;
$data[1] .= html_print_select ($lpagination, "pagination", $pagination, '', __('Default'), $config["block_size"], true);
$data[2] = '';
$table->data[] = $data;
$table->rowclass[] = '';


$data = array();
$table->data[] = $data;
$table->rowclass[] = '';

//The buttons

$data = array();
$data[0] = '<div style="width:100%; text-align:left">';
$data[0] .= '<a href="javascript:" onclick="show_save_filter_dialog();">' . html_print_image("images/disk.png", true, array("border" => '0', "title" => __('Save filter'), "alt" => __('Save filter'))) . '</a> &nbsp;';
$data[0] .= '<a href="javascript:" onclick="show_load_filter_dialog();">' . html_print_image("images/load.png", true, array("border" => '0', "title" => __('Load filter'), "alt" => __('Load filter'))) . '</a>&nbsp;';
if (empty($id_name)) {
	$data[0] .= '[<span id="filter_loaded_span" style="font-weight: normal">' . __('No filter loaded') . '</span>]';
}
else {
	$data[0] .= '[<span id="filter_loaded_span" style="font-weight: normal">' . __('Filter loaded') . ': ' . $id_name . '</span>]';
}
$data[0] .= '</div>';

$data[1] = html_print_submit_button (__('Update'), 'update', false, 'class="sub upd"', true);
$table->colspan[count($table->data)][1] = 4;
$table->rowstyle[count($table->data)] = 'text-align:right;';
$table->data[] = $data;
$table->rowclass[] = '';

html_print_table($table);

unset($table);

echo "</form>"; //This is the filter div

echo "</div>";

$event_table = events_get_events_table($meta, $history);

if ($group_rep == 0) {
	switch ($config["dbtype"]) {
		case "mysql":
			$sql = "SELECT *, 1 event_rep
				FROM $event_table
				WHERE 1=1 " . $sql_post . "
				ORDER BY utimestamp DESC LIMIT ".$offset.",".$pagination;
			break;
		case "postgresql":
			$sql = "SELECT *, 1 event_rep
				FROM $event_table
				WHERE 1=1 " . $sql_post . "
				ORDER BY utimestamp DESC LIMIT ".$pagination." OFFSET ".$offset;
			break;
		case "oracle":
			$set = array();
			$set['limit'] = $pagination;
			$set['offset'] = $offset;
			$sql = "SELECT *, 1 event_rep
				FROM $event_table
				WHERE 1=1 " . $sql_post . "
				ORDER BY utimestamp DESC"; 
			$sql = oracle_recode_query ($sql, $set);
			break;
	}
	
	//Extract the events by filter (or not) from db
	$result = db_get_all_rows_sql ($sql);
}
else {
	$result = events_get_events_grouped($sql_post, $offset, $pagination, $meta, $history);
}

if (!empty($result)) {		
	$graph .= '<fieldset class="databox tactical_set" style="width:93%;">
			<legend>' . 
				__('Events generated -by module-') . 
			'</legend>' . 
			grafico_eventos_grupo(350, 148, rawurlencode ($sql_post), $meta, $history) . '</fieldset>';
	html_print_div(array('id' => 'events_graph', 'hidden' => true, 'content' => $graph));
}

// Delete rnum field generated by oracle_recode_query() function
if (($config['dbtype'] == 'oracle') && ($result !== false)) {
	for ($i=0; $i < count($result); $i++) {
		unset($result[$i]['rnum']);
	}
}

if ($group_rep == 0) {
	$sql = "SELECT COUNT(id_evento)
		FROM $event_table
		WHERE 1=1 " . $sql_post;
}
else {
	$sql = "SELECT COUNT(1)
		FROM (SELECT 1
			FROM $event_table
			WHERE 1=1 " . $sql_post . "
			GROUP BY evento, id_agentmodule) AS t";
}

$total_events = (int) db_get_sql ($sql);
if (empty ($result)) {
	$result = array ();
}

$allow_action = true;
$allow_pagination = true;

require('events.build_table.php');

unset($table);

// Values to be used from javascript library
html_print_input_hidden('ajax_file', ui_get_full_url("ajax.php", false, false, false));
html_print_input_hidden('meta', (int)$meta);
html_print_input_hidden('history', (int)$history);

ui_require_jquery_file('json');
?>
<script language="javascript" type="text/javascript">
/*<![CDATA[ */

var select_with_tag_empty = <?php echo (int)$remove_with_tag_disabled;?>;
var select_without_tag_empty = <?php echo (int)$remove_without_tag_disabled;?>;
var origin_select_with_tag_empty = <?php echo (int)$add_with_tag_disabled;?>;
var origin_select_without_tag_empty = <?php echo (int)$add_without_tag_disabled;?>;

var val_none = 0;
var text_none = "<?php echo __('None'); ?>";

$(document).ready( function() {
	// If the events are not charged, dont show graphs link
	if ($('#events_graph').val() == undefined) {
		$('#events_graph_link').hide();
	}
	
	// Don't collapse filter if update button has been pushed
	if ($("#hidden-open_filter").val() == 'true'){
		$("#event_control").toggle();
	}
	
	// If selected is not 'none' show filter name
	if ( $("#filter_id").val() != 0 ) {
		$("#row_name").css('visibility', '');
		$("#submit-update_filter").css('visibility', '');
	}
	
	$("#submit-load_filter").click(function () {
		// If selected 'none' flush filter
		if ( $("#filter_id").val() == 0 ) {
			$("#hidden-id_name").val('');
			$("#ev_group").val(0);
			$("#event_type").val('');
			$("#severity").val(-1);
			$("#status").val(3);
			$("#text-search").val('');
			$("#text_id_agent").val( <?php echo '"' . __('All') . '"' ?> );
			$("#pagination").val(25);
			$("#text-event_view_hr").val(8);
			$("#id_user_ack").val(0);
			$("#group_rep").val(1);
			$("#tag").val('');
			$("#filter_only_alert").val(-1);
			$("#row_name").css('visibility', 'hidden');
			$("#submit-update_filter").css('visibility', 'hidden');
			$("#id_group").val(0);
			
			clear_tags_inputs();
			
			// Update the view of filter load with no loaded filters message
			$('#filter_loaded_span').html($('#not_filter_loaded_text').html());
		}
		// If filter selected then load filter
		else {
			$('#row_name').css('visibility', '');
			$("#submit-update_filter").css('visibility', '');
			jQuery.post ("<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
				{"page" : "operation/events/events_list",
				"get_filter_values" : 1,
				"id" : $('#filter_id').val()
				},
				function (data) {
					jQuery.each (data, function (i, val) {
						if (i == 'id_name')
							$("#hidden-id_name").val(val);
						if (i == 'id_group')
							$("#ev_group").val(val);
						if (i == 'event_type')
							$("#event_type").val(val);
						if (i == 'severity')
							$("#severity").val(val);
						if (i == 'status')
							$("#status").val(val);
						if (i == 'search')
							$("#text-search").val(val);
						if (i == 'text_agent')
							$("#text_id_agent").val(val);
						if (i == 'pagination')
							$("#pagination").val(val);
						if (i == 'event_view_hr')
							$("#text-event_view_hr").val(val);
						if (i == 'id_user_ack')
							$("#id_user_ack").val(val);
						if (i == 'group_rep')
							$("#group_rep").val(val);
						if (i == 'tag_with') {
							$("#hidden-tag_with").val(val);
						}
						if (i == 'tag_without') {
							$("#hidden-tag_without").val(val);
						}
						if (i == 'filter_only_alert')
							$("#filter_only_alert").val(val);
						if (i == 'id_group_filter')
							$("#id_group").val(val);
					});
					reorder_tags_inputs();
					// Update the info with the loaded filter
					$('#filter_loaded_span').html($('#filter_loaded_text').html() + ': ' + $("#hidden-id_name").val());
					
					// Update the view with the loaded filter
					$('#submit-update').trigger('click');
				},
				"json"
			);
		}
		
		// Close dialog
		$('.ui-dialog-titlebar-close').trigger('click');
	});
	
	// Filter save mode selector
	$("[name='filter_mode']").click(function() {
		if ($(this).val() == 'new') {
			$('#save_filter_row1').show();
			$('#save_filter_row2').show();
			$('#update_filter_row1').hide();
		}
		else {
			$('#save_filter_row1').hide();
			$('#save_filter_row2').hide();
			$('#update_filter_row1').show();
		}
	});
	
	// This saves an event filter
	$("#submit-save_filter").click(function () {
		// If the filter name is blank show error
		if ($('#text-id_name').val() == '') {
			$('#show_filter_error').html('<h3 class="error"> <?php echo __('Filter name cannot be left blank'); ?> </h3>');
			
			// Close dialog
			$('.ui-dialog-titlebar-close').trigger('click');
			return false;
		}
		
		var id_filter_save;
		
		jQuery.post ("<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
			{"page" : "operation/events/events_list",
			"save_event_filter" : 1,
			"id_name" : $("#text-id_name").val(),
			"id_group" : $("#ev_group").val(),
			"event_type" : $("#event_type").val(),
			"severity" : $("#severity").val(),
			"status" : $("#status").val(),
			"search" : $("#text-search").val(),
			"text_agent" : $("#text_id_agent").val(),
			"pagination" : $("#pagination").val(),
			"event_view_hr" : $("#text-event_view_hr").val(),
			"id_user_ack" : $("#id_user_ack").val(),
			"group_rep" : $("#group_rep").val(),
			"tag_with": Base64.decode($("#hidden-tag_with").val()),
			"tag_without": Base64.decode($("#hidden-tag_without").val()),
			"filter_only_alert" : $("#filter_only_alert").val(),
			"id_group_filter": $("#id_group").val()
			},
			function (data) {
				if (data == 'error') {
					$('#show_filter_error').html('<h3 class="error"> <?php echo __('Error creating filter'); ?> </h3>');
				}
				else {
					id_filter_save = data;
					$('#show_filter_error').html('<h3 class="suc"> <?php echo __('Filter created'); ?> </h3>');
				}
			});
		
		// First remove all options of filters select
		$('#filter_id').find('option').remove().end();
		// Add 'none' option the first
		$('#filter_id').append ($('<option></option>').html ( <?php echo "'" . __('none') . "'" ?> ).attr ("value", 0));	
		// Reload filters select
		jQuery.post ("<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
			{
				"page" : "operation/events/events_list",
				"get_event_filters" : 1
			},
			function (data) {
				jQuery.each (data, function (i, val) {
					s = js_html_entity_decode(val);
					
					if (i == id_filter_save){
						$('#filter_id').append ($('<option selected="selected"></option>').html (s).attr ("value", i));
					}
					else {
						$('#filter_id').append ($('<option></option>').html (s).attr ("value", i));	  
					}
				});
			},
			"json"
			);
		$("#submit-update_filter").css('visibility', '');
		
		// Close dialog
		$('.ui-dialog-titlebar-close').trigger('click');
		
		// Update the info with the loaded filter
		$("#hidden-id_name").val($('#text-id_name').val());
		$('#filter_loaded_span').html($('#filter_loaded_text').html() + ': ' + $('#text-id_name').val());
					
		return false;
	});
	
	// This updates an event filter
	$("#submit-update_filter").click(function () {
		var id_filter_update =  $("#overwrite_filter").val();
		var name_filter_update = $("#overwrite_filter option[value='"+id_filter_update+"']").text();
		
		jQuery.post ("<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
			{"page" : "operation/events/events_list",
			"update_event_filter" : 1,
			"id" : $("#filter_id").val(),
			"id_group" : $("#ev_group").val(),
			"event_type" : $("#event_type").val(),
			"severity" : $("#severity").val(),
			"status" : $("#status").val(),
			"search" : $("#text-search").val(),
			"text_agent" : $("#text_id_agent").val(),
			"pagination" : $("#pagination").val(),
			"event_view_hr" : $("#text-event_view_hr").val(),
			"id_user_ack" : $("#id_user_ack").val(),
			"group_rep" : $("#group_rep").val(),
			"tag_with" : Base64.decode($("#hidden-tag_with").val()),
			"tag_without" : Base64.decode($("#hidden-tag_without").val()),
			"filter_only_alert" : $("#filter_only_alert").val(),
			"id_group_filter": $("#id_group").val()
			},
			function (data) {
				if (data == 'ok') {
					$('#show_filter_error').html('<h3 class="suc"> <?php echo __('Filter updated'); ?> </h3>');
				}
				else {
					$('#show_filter_error').html('<h3 class="error"> <?php echo __('Error updating filter'); ?> </h3>');
				}
			});
			
			// First remove all options of filters select
			$('#filter_id').find('option').remove().end();
			// Add 'none' option the first
			$('#filter_id').append ($('<option></option>').html ( <?php echo "'" . __('none') . "'" ?> ).attr ("value", 0));	
			// Reload filters select
			jQuery.post ("<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
				{"page" : "operation/events/events_list",
					"get_event_filters" : 1
				},
				function (data) {
					jQuery.each (data, function (i, val) {
						s = js_html_entity_decode(val);
						if (i == id_filter_update) {
							$('#filter_id').append ($('<option selected="selected"></option>').html (s).attr ("value", i));
						}
						else {
							$('#filter_id').append ($('<option></option>').html (s).attr ("value", i));
						}
					});
				},
				"json"
				);
				
			// Close dialog
			$('.ui-dialog-titlebar-close').trigger('click');
			
			// Update the info with the loaded filter
			$("#hidden-id_name").val($('#text-id_name').val());
			$('#filter_loaded_span').html($('#filter_loaded_text').html() + ': ' + name_filter_update);
			return false;
	});
	
	// Change toggle arrow when it's clicked
	$("#tgl_event_control").click(function() {
		if ($("#toggle_arrow").attr("src").match(/[^\.]+down\.png/) == null){
			var params = [];
			params.push("get_image_path=1");
			params.push("img_src=images/down.png");
			params.push("page=include/ajax/skins.ajax");
			params.push("only_src=1");
			jQuery.ajax ({
				data: params.join ("&"),
				type: 'POST',
				url: action="<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
				async: false,
				timeout: 10000,
				success: function (data) {
					$("#toggle_arrow").attr('src', data);
				}
			});
		}
		else {
			var params = [];
			params.push("get_image_path=1");
			params.push("img_src=images/go.png");
			params.push("page=include/ajax/skins.ajax");
			params.push("only_src=1");
			jQuery.ajax ({
				data: params.join ("&"),
				type: 'POST',
				url: action="<?php echo ui_get_full_url("ajax.php", false, false, false); ?>",
				async: false,
				timeout: 10000,
				success: function (data) {
					$("#toggle_arrow").attr('src', data);
				}
			});
		}
	});
	
	$("#button-add_with").click(function() {
		click_button_add_tag("with");
		});
	
	$("#button-add_without").click(function() {
		click_button_add_tag("without");
		});
	
	$("#button-remove_with").click(function() {
		click_button_remove_tag("with");
	});
	
	$("#button-remove_without").click(function() {
		click_button_remove_tag("without");
	});
	
});

function click_button_remove_tag(what_button) {
	if (what_button == "with") {
		id_select_origin = "#select_with";
		id_select_destiny = "#tag_with_temp";
		id_button_remove = "#button-remove_with";
		id_button_add = "#button-add_with";
		
		select_origin_empty = origin_select_with_tag_empty;
	}
	else { //without
		id_select_origin = "#select_without";
		id_select_destiny = "#tag_without_temp";
		id_button_remove = "#button-remove_without";
		id_button_add = "#button-add_without";
		
		select_origin_empty = origin_select_without_tag_empty;
	}
	
	if ($(id_select_destiny + " option:selected").length == 0) {
		return; //Do nothing
	}
	
	if (select_origin_empty) {
		$(id_select_origin + " option").remove();
		
		if (what_button == "with") {
			origin_select_with_tag_empty = false;
		}
		else { //without
			origin_select_without_tag_empty = false;
		}
		
		$(id_button_add).removeAttr('disabled');
	}
	
	//Foreach because maybe the user select several items in
	//the select.
	jQuery.each($(id_select_destiny + " option:selected"), function(key, element) {
		val = $(element).val();
		text = $(element).text();
		
		$(id_select_origin).append($("<option value='" + val + "'>" + text + "</option>"));
	});
	
	$(id_select_destiny + " option:selected").remove();
	
	if ($(id_select_destiny + " option").length == 0) {
		$(id_select_destiny).append($("<option value='" + val_none + "'>" + text_none + "</option>"));
		$(id_button_remove).attr('disabled', 'true');
		
		if (what_button == 'with') {
			select_with_tag_empty = true;
		}
		else { //without
			select_without_tag_empty = true;
		}
	}
	
	replace_hidden_tags(what_button);
}

function click_button_add_tag(what_button) {
	if (what_button == 'with') {
		id_select_origin = "#select_with";
		id_select_destiny = "#tag_with_temp";
		id_button_remove = "#button-remove_with";
		id_button_add = "#button-add_with";
		
		select_destiny_empty = select_with_tag_empty;
	}
	else { //without
		id_select_origin = "#select_without";
		id_select_destiny = "#tag_without_temp";
		id_button_remove = "#button-remove_without";
		id_button_add = "#button-add_without";
		
		select_destiny_empty = select_without_tag_empty;
	}
	
	without_val = $(id_select_origin).val();
	without_text = $(id_select_origin + " option:selected").text();
	
	if (select_destiny_empty) {
		$(id_select_destiny).empty();
		
		if (what_button == 'with') {
			select_with_tag_empty = false;
		}
		else { //without
			select_without_tag_empty = false;
		}
	}
	
	$(id_select_destiny).append($("<option value='" + without_val + "'>" + without_text + "</option>"));
	$(id_select_origin + " option:selected").remove();
	$(id_button_remove).removeAttr('disabled');
	
	if ($(id_select_origin + " option").length == 0) {
		$(id_select_origin).append($("<option value='" + val_none + "'>" + text_none + "</option>"));
		$(id_button_add).attr('disabled', 'true');
		
		if (what_button == 'with') {
			origin_select_with_tag_empty = true;
		}
		else { //without
			origin_select_without_tag_empty = true;
		}
	}
	
	replace_hidden_tags(what_button);
}

function replace_hidden_tags(what_button) {
	if (what_button == 'with') {
		id_select_destiny = "#tag_with_temp";
		id_hidden = "#hidden-tag_with";
	}
	else { //without
		id_select_destiny = "#tag_without_temp";
		id_hidden = "#hidden-tag_without";
	}
	
	value_store = [];
	
	jQuery.each($(id_select_destiny + " option"), function(key, element) {
		val = $(element).val();
		
		value_store.push(val);
	});
	
	$(id_hidden).val(Base64.encode(jQuery.toJSON(value_store)));
}

function clear_tags_inputs() {
	$("#hidden-tag_with").val(Base64.encode(jQuery.toJSON([])));
	$("#hidden-tag_without").val(Base64.encode(jQuery.toJSON([])));
	reorder_tags_inputs();
}

function reorder_tags_inputs() {
	$('#select_with option[value="' + val_none + '"]').remove();
	jQuery.each($("#tag_with_temp option"), function(key, element) {
		val = $(element).val();
		text = $(element).text();
		
		if (val == val_none)
			return;
		
		$("#select_with").append($("<option value='" + val + "'>" + text + "</option>"));
	});
	$("#tag_with_temp option").remove();
	
	
	$('#select_without option[value="' + val_none + '"]').remove();
	jQuery.each($("#tag_without_temp option"), function(key, element) {
		val = $(element).val();
		text = $(element).text();
		
		if (val == val_none)
			return;
		
		$("#select_without").append($("<option value='" + val + "'>" + text + "</option>"));
	});
	$("#tag_without_temp option").remove();
	
	
	tags_base64 = $("#hidden-tag_with").val();
	tags = jQuery.parseJSON(Base64.decode(tags_base64));
	jQuery.each(tags, function(key, element) {
		if ($("#select_with option[value='" + element + "']").length == 1) {
			text = $("#select_with option[value='" + element + "']").text();
			val = $("#select_with option[value='" + element + "']").val();
			$("#tag_with_temp").append($("<option value='" + val + "'>" + text + "</option>"));
			$("#select_with option[value='" + element + "']").remove();
		}
	});
	if ($("#select_with option").length == 0) {
		origin_select_with_tag_empty = true;
		$("#button-add_with").attr('disabled', 'true');
		$("#select_with").append($("<option value='" + val_none + "'>" + text_none + "</option>"));
	}
	else {
		origin_select_with_tag_empty = false;
		$("#button-add_with").removeAttr('disabled');
	}
	if ($("#tag_with_temp option").length == 0) {
		select_with_tag_empty = true;
		$("#button-remove_with").attr('disabled', 'true');
		$("#tag_with_temp").append($("<option value='" + val_none + "'>" + text_none + "</option>"));
	}
	else {
		select_with_tag_empty = false;
		$("#button-remove_with").removeAttr('disabled');
	}
	
	tags_base64 = $("#hidden-tag_without").val();
	tags = jQuery.parseJSON(Base64.decode(tags_base64));
	jQuery.each(tags, function(key, element) {
		if ($("#select_without option[value='" + element + "']").length == 1) {
			text = $("#select_without option[value='" + element + "']").text();
			val = $("#select_without option[value='" + element + "']").val();
			$("#tag_without_temp").append($("<option value='" + val + "'>" + text + "</option>"));
			$("#select_without option[value='" + element + "']").remove();
		}
	});
	if ($("#select_without option").length == 0) {
		origin_select_without_tag_empty = true;
		$("#button-add_without").attr('disabled', 'true');
		$("#select_without").append($("<option value='" + val_none + "'>" + text_none + "</option>"));
	}
	else {
		origin_select_without_tag_empty = false;
		$("#button-add_without").removeAttr('disabled');
	}
	if ($("#tag_without_temp option").length == 0) {
		select_without_tag_empty = true;
		$("#button-remove_without").attr('disabled', 'true');
		$("#tag_without_temp").append($("<option value='" + val_none + "'>" + text_none + "</option>"));
	}
	else {
		select_without_tag_empty = false;
		$("#button-remove_without").removeAttr('disabled');
	}
}

// Show the modal window of an module
function show_events_graph_dialog() {
	$("#events_graph").hide ()
			.dialog ({
				resizable: true,
				draggable: true,
				modal: true,
				overlay: {
					opacity: 0.5,
					background: "black"
				},
				width: 400,
				height: 360
			})
			.show ();
}
/* ]]> */
</script>
