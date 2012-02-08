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

if (! check_acl ($config["id_user"], 0, "IR")) {
	db_pandora_audit("ACL Violation",
		"Trying to access event viewer");
	require ("general/noaccess.php");
	return;
}

$id = (int) get_parameter ('id');
$update = (string)get_parameter('update', 0);
$create = (string)get_parameter('create', 0);

if ($id){
	$permission = events_check_event_filter_group ($id);
	if (!$permission) { // User doesn't have permissions to see this filter
		require ("general/noaccess.php");
		return;
	}
}

$buttons = array(
		'view' => array('active' => false, 
			'text' => '<a href="index.php?sec=geventos&sec2=operation/events/events">' . 
			html_print_image("images/zoom.png", true, array("title" => __('View events'))) . '</a>'),
		'filter' => array('active' => true, 
			'text' => '<a href="index.php?sec=geventos&sec2=godmode/events/events&amp;section=filter">' .
			html_print_image("images/lightning_go.png", true, array ("title" => __('Create filter'))) . '</a>'),
		'fields' => array('active' => false, 	
			'text' => '<a href="index.php?sec=geventos&sec2=godmode/events/events&amp;section=fields">' .
			html_print_image("images/god6.png", true, array ("title" => __('Custom fields'))) . '</a>'),
	);
	
ui_print_page_header (__("Manage events") . ' - ' . __('Filters'), "images/lightning_go.png", false, "", true, $buttons);	

if ($id) {
	$filter = events_get_event_filter ($id);
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
	$tag = $filter['tag'];
	$filter_only_alert = $filter['filter_only_alert'];
} else {
	$id_group = '';
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
	$tag = '';
	$filter_only_alert = '';
}

if ($update) {
	$id_group = (string) get_parameter ('id_group');
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
	$tag = get_parameter('tag', '');
	$filter_only_alert = get_parameter('filter_only_alert','');				
	
	if ($id_name == '') {
                ui_print_error_message (__('Not updated. Blank name'));
    } else {
		$values = array ('id_filter' => $id,
			'id_name' => $id_name,			
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
			'tag' => $tag,
			'filter_only_alert' => $filter_only_alert
		);

		$result = db_process_sql_update ('tevent_filter', $values, array ('id_filter' => $id));
			
		ui_print_result_message ($result,
			__('Successfully updated'),
			__('Not updated. Error updating data'));
	}
}

if ($create) {
	$id_group = (string) get_parameter ('id_group');
	$id_name = (string) get_parameter ('id_name');
	$event_type = get_parameter('event_type', '');
	$severity = get_parameter('severity', '');
	$status = get_parameter('status', '');
	$search = get_parameter('search', '');
	$text_agent = get_parameter('text_agent', '');
	$pagination = get_parameter('pagination', '');
	$event_view_hr = get_parameter('event_view_hr', '');
	$id_user_ack = get_parameter('id_user_ack', '');
	$group_rep = get_parameter('group_rep',' ');
	$tag = get_parameter('tag', '');
	$filter_only_alert = get_parameter('filter_only_alert', '');	

	$values = array (
			'id_name' => $id_name,			
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
			'tag' => $tag,
			'filter_only_alert' => $filter_only_alert
	);

	$id = db_process_sql_insert('tevent_filter', $values);
	
	if ($id === false) {
		ui_print_error_message ('Error creating filter');
	} else {
		ui_print_success_message ('Filter created successfully');
	}
}

$table->width = '98%';
$table->border = 0;
$table->cellspacing = 3;
$table->cellpadding = 5;
$table->class = "databox_color";
$table->style[0] = 'vertical-align: top;';

$table->data = array ();
$table->data[0][0] = '<b>'.__('Name').'</b>';
$table->data[0][1] = html_print_input_text ('id_name', $id_name, false, 20, 80, true);

$own_info = get_user_info ($config['id_user']);
$table->data[1][0] = '<b>'.__('Group').'</b>';
$table->data[1][1] = html_print_select_groups($config['id_user'], "IW", 
		$own_info['is_admin'], 'id_group', $id_group, '', '', -1, true,
		false, false);
		
$types = get_event_types ();
// Expand standard array to add not_normal (not exist in the array, used only for searches)
$types["not_normal"] = __("Not normal");		
	
$table->data[2][0] = '<b>' . __('Event type') . '</b>';
$table->data[2][1] = html_print_select ($types, 'event_type', $event_type, '', __('All'), '', true);

$table->data[3][0] = '<b>' . __('Severity') . '</b>';
$table->data[3][1] = html_print_select (get_priorities (), "severity", $severity, '', __('All'), '-1', true);
	
$fields = events_get_all_status();
	
$table->data[4][0] = '<b>' . __('Event status') . '</b>';
$table->data[4][1] = html_print_select ($fields, 'status', $status, '', '', '', true);
	
$table->data[5][0] = '<b>' . __('Free search') . '</b>';
$table->data[5][1] = html_print_input_text ('search', io_safe_output($search), '', 15, 255, true);

$table->data[6][0] = '<b>' . __('Agent search') . '</b>';
$src_code = html_print_image('images/lightning.png', true, false, true);
$table->data[6][1] = html_print_input_text_extended ('text_agent', $text_agent, 'text_id_agent', '', 30, 100, false, '',
array('style' => 'background: url(' . $src_code . ') no-repeat right;'), true)
. '<a href="#" class="tip">&nbsp;<span>' . __("Type at least two characters to search") . '</span></a>';
	
$lpagination[25] = 25;
$lpagination[50] = 50;
$lpagination[100] = 100;
$lpagination[200] = 200;
$lpagination[500] = 500;	
$table->data[7][0] = '<b>' . __('Block size for pagination') . '</b>';
$table->data[7][1] = html_print_select ($lpagination, "pagination", $pagination, '', __('Default'), $config["block_size"], true);
	
$table->data[8][0] = '<b>' . __('Max. hours old') . '</b>';
$table->data[8][1] = html_print_input_text ('event_view_hr', $event_view_hr, '', 5, 255, true);

$table->data[9][0] = '<b>' . __('User ack.') . '</b>';
$users = users_get_info ();
$table->data[9][1] = html_print_select ($users, "id_user_ack", $id_user_ack, '', __('Any'), 0, true);

$repeated_sel[0] = __("All events");
$repeated_sel[1] = __("Group events");
$table->data[10][0] = '<b>' . __('Repeated') . '</b>';
$table->data[10][1] = html_print_select ($repeated_sel, "group_rep", $group_rep, '', '', '', true);	

$tags = tags_search_tag();
if($tags === false) {
	$tags = array();
}

$tags_name = array();
foreach($tags as $t) {
	$tags_name[$t['name']] = $t['name'];
}

$table->data[11][0] = '<b>' . __('Tag') . '</b>';
$table->data[11][1] = html_print_select ($tags_name, "tag", $tag, '', __('All'), "", true);	

$table->data[12][0] = '<b>' . __('Alert events') . '</b>';
$table->data[12][1] = html_print_select (array('-1' => __('All'), '0' => __('Filter alert events'), '1' => __('Only alert events')), "filter_only_alert", $filter_only_alert, '', '', '', true);
	
echo '<form method="post" action="index.php?sec=geventos&sec2=godmode/events/event_edit_filter">';
html_print_table ($table);
echo '<div class="action-buttons" style="width: '.$table->width.'">';
if ($id) {
	html_print_input_hidden ('update', 1);
	html_print_input_hidden ('id', $id);
	html_print_submit_button (__('Update'), 'crt', false, 'class="sub upd"');
} else {
	html_print_input_hidden ('create', 1);
	html_print_submit_button (__('Create'), 'crt', false, 'class="sub wand"');
}
echo '</div>';
echo '</form>';

ui_require_jquery_file ('bgiframe');
ui_require_jquery_file ('autocomplete');
?>

<script language="javascript" type="text/javascript">
/* <![CDATA[ */
$(document).ready( function() {

	$("#text_id_agent").autocomplete(
			"ajax.php",
			{
				minChars: 2,
				scroll:true,
				extraParams: {
					page: "operation/agentes/exportdata",
					search_agents: 1,
					add: '<?php echo json_encode(array('-1' => "All", '0' => "System"));?>',
					id_group: function() { return $("#id_group").val(); }
				},
				formatItem: function (data, i, total) {
					if (total == 0)
						$("#text_id_agent").css ('background-color', '#cc0000');
					else
						$("#text_id_agent").css ('background-color', '');
					if (data == "")
						return false;
					
					return data[0]+'<br><span class="ac_extra_field"><?php echo __("IP") ?>: '+data[1]+'</span>';
				},
				delay: 200
			}
		);

});
/* ]]> */
</script>
