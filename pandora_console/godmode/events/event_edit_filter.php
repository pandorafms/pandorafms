<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

global $config;

check_login ();

if (! check_acl ($config["id_user"], 0, "EW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access event viewer");
	require ("general/noaccess.php");
	
	return;
}

$id = (int) get_parameter ('id');
$update = (string)get_parameter('update', 0);
$create = (string)get_parameter('create', 0);

if ($id) {
	$permission = events_check_event_filter_group ($id);
	if (!$permission) { // User doesn't have permissions to see this filter
		require ("general/noaccess.php");
		
		return;
	}
}

if ($id) {
	$filter = events_get_event_filter ($id);
	$id_group_filter = $filter['id_group_filter'];
	$id_group = $filter['id_group'];
	$id_name = $filter['id_name'];
	$event_type = $filter['event_type'];
	$severity = $filter['severity'];
	$status = $filter['status'];
	$search = $filter['search'];
	$text_agent = $filter['text_agent'];
	$pagination = $filter['pagination'];
	$event_view_hr = $filter['event_view_hr'];
	$id_user_ack = $filter['id_user_ack'];
	$group_rep = $filter['group_rep'];

	$tag_with_json = $filter['tag_with'];	
	$tag_with_json_clean = io_safe_output($tag_with_json);
	$tag_with_base64 = base64_encode($tag_with_json_clean) ;
	
	$tag_without_json = $filter['tag_without'];
	$tag_without_json_clean = io_safe_output($tag_without_json);
	$tag_without_base64 = base64_encode($tag_without_json_clean) ;

	$filter_only_alert = $filter['filter_only_alert'];
}
else {
	$id_group = '';
	$id_group_filter = '';
	$id_name = '';
	$event_type = '';
	$severity = '';
	$status = '';
	$search = '';
	$text_agent = __('All');
	$pagination = '';
	$event_view_hr = '';
	$id_user_ack = '';
	$group_rep = '';
	$tag_with_json = $tag_with_json_clean = json_encode(array());
	$tag_with_base64 = base64_encode($tag_with_json);
	$tag_without_json = $tag_without_json_clean = json_encode(array());
	$tag_without_base64 = base64_encode($tag_without_json);
	$filter_only_alert = '';
}

if($update || $create) {
	$id_group = (string) get_parameter ('id_group');
	$id_group_filter = get_parameter('id_group_filter');
	$id_name = (string) get_parameter ('id_name');
	$event_type = get_parameter('event_type', '');
	$severity = get_parameter('severity', '');
	$status = get_parameter('status', '');
	$search = get_parameter('search', '');
	$text_agent = get_parameter('text_agent', __('All'));
	$pagination = get_parameter('pagination', '');
	$event_view_hr = get_parameter('event_view_hr', '');
	$id_user_ack = get_parameter('id_user_ack', '');
	$group_rep = get_parameter('group_rep', '');
	
	$tag_with_base64 = get_parameter('tag_with', json_encode(array()));
	$tag_with_json = io_safe_input(base64_decode($tag_with_base64));

	$tag_without_base64 = get_parameter('tag_without', json_encode(array()));
	$tag_without_json = io_safe_input(base64_decode($tag_without_base64));

	$filter_only_alert = get_parameter('filter_only_alert','');
	
	$values = array (
		'id_name' => $id_name,	
		'id_group_filter' => $id_group_filter,
		'id_group' => $id_group,
		'event_type' => $event_type,
		'severity' => $severity,
		'status' => $status,
		'search' => $search,
		'text_agent' => $text_agent,
		'pagination' => $pagination,
		'event_view_hr' => $event_view_hr,
		'id_user_ack' => $id_user_ack,
		'group_rep' => $group_rep,
		'tag_with' => $tag_with_json,
		'tag_without' => $tag_without_json,
		'filter_only_alert' => $filter_only_alert);
}

if ($update) {
	if ($id_name == '') {
		ui_print_error_message (__('Not updated. Blank name'));
	}
	else {
		$result = db_process_sql_update ('tevent_filter', $values, array ('id_filter' => $id));
		
		ui_print_result_message ($result,
			__('Successfully updated'),
			__('Not updated. Error updating data'));
	}
}

if ($create) {	
	$id = db_process_sql_insert('tevent_filter', $values);
	
	if ($id === false) {
		ui_print_error_message ('Error creating filter');
	}
	else {
		ui_print_success_message ('Filter created successfully');
	}
}

$own_info = get_user_info ($config['id_user']);

$table->width = '98%';
$table->border = 0;
$table->cellspacing = 3;
$table->cellpadding = 5;
$table->class = "databox_color";
$table->style[0] = 'vertical-align: top;';

$table->valign[1] = 'top';

$table->data = array ();
$table->data[0][0] = '<b>'.__('Filter name').'</b>';
$table->data[0][1] = html_print_input_text ('id_name', $id_name, false, 20, 80, true);

$table->data[1][0] = '<b>'.__('Filter group').'</b>' . ui_print_help_tip(__('This group will be use to restrict the visibility of this filter with ACLs'), true);
$table->data[1][1] = html_print_select_groups($config['id_user'], "EW", 
	$own_info['is_admin'], 'id_group_filter', $id_group_filter, '', '', -1, true,
	false, false);

$table->data[2][0] = '<b>'.__('Group').'</b>';
$table->data[2][1] = html_print_select_groups($config['id_user'], "EW", 
	users_can_manage_group_all(), 'id_group', $id_group, '', '', -1, true,
	false, false);

$types = get_event_types ();
// Expand standard array to add not_normal (not exist in the array, used only for searches)
$types["not_normal"] = __("Not normal");

$table->data[3][0] = '<b>' . __('Event type') . '</b>';
$table->data[3][1] = html_print_select ($types, 'event_type', $event_type, '', __('All'), '', true);

$table->data[4][0] = '<b>' . __('Severity') . '</b>';
$table->data[4][1] = html_print_select (get_priorities (), "severity", $severity, '', __('All'), '-1', true);

$fields = events_get_all_status();

$table->data[5][0] = '<b>' . __('Event status') . '</b>';
$table->data[5][1] = html_print_select ($fields, 'status', $status, '', '', '', true);

$table->data[6][0] = '<b>' . __('Free search') . '</b>';
$table->data[6][1] = html_print_input_text ('search', io_safe_output($search), '', 15, 255, true);

$table->data[7][0] = '<b>' . __('Agent search') . '</b>';
$params = array();
$params['return'] = true;
$params['show_helptip'] = true;
$params['input_name'] = 'text_agent';
$params['value'] = $text_agent;
$params['selectbox_group'] = 'id_group';

if(defined('METACONSOLE')) {
	$params['javascript_page'] = 'enterprise/meta/include/ajax/events.ajax';
}

ui_print_agent_autocomplete_input($params);

$table->data[7][1] = ui_print_agent_autocomplete_input($params);

$lpagination[25] = 25;
$lpagination[50] = 50;
$lpagination[100] = 100;
$lpagination[200] = 200;
$lpagination[500] = 500;
$table->data[8][0] = '<b>' . __('Block size for pagination') . '</b>';
$table->data[8][1] = html_print_select ($lpagination, "pagination", $pagination, '', __('Default'), $config["block_size"], true);

$table->data[9][0] = '<b>' . __('Max. hours old') . '</b>';
$table->data[9][1] = html_print_input_text ('event_view_hr', $event_view_hr, '', 5, 255, true);

$table->data[10][0] = '<b>' . __('User ack.') . '</b>'. ' ' . ui_print_help_tip (__('Choose between the users who have validated an event. '), true);
$users = users_get_info ();
$table->data[10][1] = html_print_select ($users, "id_user_ack", $id_user_ack, '', __('Any'), 0, true);

$repeated_sel[0] = __("All events");
$repeated_sel[1] = __("Group events");
$table->data[11][0] = '<b>' . __('Repeated') . '</b>';
$table->data[11][1] = html_print_select ($repeated_sel, "group_rep", $group_rep, '', '', '', true);


$tag_with = json_decode($tag_with_json_clean, true);
if(empty($tag_with)) {
	$tag_with = array();
}
$tag_without = json_decode($tag_without_json_clean, true);
if(empty($tag_without)) {
	$tag_without = array();
}

$tags = tags_search_tag(false, false, true);
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

$table->colspan[13][0] = '2';
$table->data[13][0] = '<b>' . __('Events with following tags') . '</b>';
$table->data[14][0] = html_print_select ($tags_select_with, 'select_with',
	'', '', '', 0, true, false, true, '', false, 'width: 120px;');
$table->data[14][1] = html_print_button(__('Add'), 'add_whith', $add_with_tag_disabled,
	'', 'class="add sub"', true);

$table->data[15][0] = html_print_select ($tag_with_temp,
	'tag_with_temp', array(), '', '', 0, true, true,
	true, '', false, "width: 120px; height: 50px;");
$table->data[15][0] .= html_print_input_hidden('tag_with',
	$tag_with_base64, true);
$table->data[15][1] = html_print_button(__('Remove'),
	'remove_whith', $remove_with_tag_disabled, '', 'class="delete sub"', true);



$table->colspan[16][0] = '2';
$table->data[16][0] = '<b>' . __('Events without following tags') . '</b>';
$table->data[17][0] = html_print_select ($tags_select_without, 'select_without',
	'', '', '', 0, true, false, true, '', false, 'width: 120px;');
$table->data[17][1] = html_print_button(__('Add'), 'add_whithout', $add_without_tag_disabled,
	'', 'class="add sub"', true);

$table->data[18][0] = html_print_select ($tag_without_temp,
	'tag_without_temp', array(), '', '', 0, true, true,
	true, '', false, "width: 120px; height: 50px;");
$table->data[18][0] .= html_print_input_hidden('tag_without',
	$tag_without_base64, true);
$table->data[18][1] = html_print_button(__('Remove'), 'remove_whithout', $remove_without_tag_disabled,
	'', 'class="delete sub"', true);



$table->data[19][0] = '<b>' . __('Alert events') . '</b>';
$table->data[19][1] = html_print_select(
	array('-1' => __('All'),
		'0' => __('Filter alert events'),
		'1' => __('Only alert events')),
	"filter_only_alert", $filter_only_alert, '', '', '', true);

echo '<form method="post" action="index.php?sec=geventos&sec2=godmode/events/events&section=edit_filter&pure='.$config['pure'].'">';
html_print_table ($table);


echo '<div class="action-buttons" style="width: '.$table->width.'">';
if ($id) {
	html_print_input_hidden ('update', 1);
	html_print_input_hidden ('id', $id);
	html_print_submit_button (__('Update'), 'crt', false, 'class="sub upd"');
}
else {
	html_print_input_hidden ('create', 1);
	html_print_submit_button (__('Create'), 'crt', false, 'class="sub wand"');
}
echo '</div>';
echo '</form>';

ui_require_jquery_file ('bgiframe');
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
	$("#button-add_whith").click(function() {
		click_button_add_tag("with");
		});
	
	$("#button-add_whithout").click(function() {
		click_button_add_tag("without");
		});
	
	$("#button-remove_whith").click(function() {
		click_button_remove_tag("with");
	});
	
	$("#button-remove_whithout").click(function() {
		click_button_remove_tag("without");
	});
	
});


function click_button_remove_tag(what_button) {
	if (what_button == "with") {
		id_select_origin = "#select_with";
		id_select_destiny = "#tag_with_temp";
		id_button_remove = "#button-remove_whith";
		id_button_add = "#button-add_whith";
		
		select_origin_empty = origin_select_with_tag_empty;
	}
	else { //without
		id_select_origin = "#select_without";
		id_select_destiny = "#tag_without_temp";
		id_button_remove = "#button-remove_whithout";
		id_button_add = "#button-add_whithout";
		
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
		id_button_remove = "#button-remove_whith";
		id_button_add = "#button-add_whith";
		
		select_destiny_empty = select_with_tag_empty;
	}
	else { //without
		id_select_origin = "#select_without";
		id_select_destiny = "#tag_without_temp";
		id_button_remove = "#button-remove_whithout";
		id_button_add = "#button-add_whithout";
		
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
/* ]]> */
</script>