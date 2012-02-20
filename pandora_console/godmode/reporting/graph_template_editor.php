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

check_login ();

if (! check_acl ($config['id_user'], 0, "IW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access graph builder");
	include ("general/noaccess.php");
	exit;
}
	
$update = get_parameter('edit_template', 0);
$create = get_parameter('create_template', 0);
$id_template = get_parameter('id', 0);
	

if ($create){
	$stacked = get_parameter('stacked');
	$events = get_parameter('events');
	$period = get_parameter('period');
	$name = get_parameter('name');
	$description = get_parameter('description');
	$id_group = get_parameter('id_group');
	$width = get_parameter('width');
	$height = get_parameter('height');

	$values = array (
			'name' => $name,
			'id_user' => $config['id_user'],
			'period' => $period,
			'width' => $width,
			'height' => $height,
			'id_group' => $id_group,
			'stacked' => $stacked,
			'private' => 0,
			'events' => $events,
			'description' => $description
			);
	$id_template = db_process_sql_insert('tgraph_template', $values);
}	

$buttons['graph_list'] = '<a href="index.php?sec=greporting&sec2=godmode/reporting/graph_template_list">'
		. html_print_image ("images/god6.png", true, array ("title" => __('Template list')))
		. '</a>';

$buttons['template_editor'] = '<a href="index.php?sec=greporting&sec2=godmode/reporting/graph_template_item_editor&id='.$id_template.'">'
		. html_print_image ("images/config.png", true, array ("title" => __('Template editor')))
		. '</a>';
			
// Header
ui_print_page_header (__('Graph template editor'), "", false, "", true, $buttons);

if ($create) {
	if ($id_template === false) {
			ui_print_error_message ('Error creating template');
		} else {
			ui_print_success_message ('Template created successfully');
		}
}

if ($id_template) {
	$template_data = db_get_row_sql("SELECT * FROM tgraph_template WHERE id_graph_template = " . $id_template);
	$stacked = $template_data['stacked'];
	$events = $template_data['events'];
	$period = $template_data['period'];
	$name = $template_data['name'];
	$description = $template_data['description'];
	$id_group = $template_data['id_group'];
	$width = $template_data['width'];
	$height = $template_data['height'];
} else {
	$id_agent = 0;
	$id_module = 0;
	$id_group = 0;
	$name = "";
	$description = "";
	$width = 550;
	$height = 210;
	$period = 86400;
	$events = 0;
	$factor = 1;
	$stacked = 0;
}

if ($update) {
	$stacked = get_parameter('stacked');
	$events = get_parameter('events');
	$period = get_parameter('period');
	$name = get_parameter('name');
	$description = get_parameter('description');
	$id_group = get_parameter('id_group');
	$width = get_parameter('width');
	$height = get_parameter('height');
	
	if ($name == '') {
		ui_print_error_message (__('Not updated. Blank name'));
    } else {
		$result = db_process_sql_update ('tgraph_template',
			array (
				'name' => $name,
				'period' => $period,
				'width' => $width,
				'height' => $height,
				'id_group' => $id_group,
				'stacked' => $stacked,
				'events' => $events,
				'description' => $description
				),
			array ('id_graph_template' => $id_template));
        ui_print_result_message ($result, __('Template updated successfully'), __('Error updating template'));
	}
}

// -----------------------
// CREATE/EDIT GRAPH TEMPLATE FORM
// -----------------------

echo "<table width='98%' cellpadding=4 cellspacing=4 class='databox_color'>";

if ($id_template)
	echo "<form method='post' action='index.php?sec=greporting&sec2=godmode/reporting/graph_template_editor&edit_template=1&id=" . $id_template . "'>";
else
	echo "<form method='post' action='index.php?sec=greporting&sec2=godmode/reporting/graph_template_editor&create_template=1&id=" . $id_template . "'>";

echo "<tr>";
echo "<td class='datos'><b>".__('Name')."</b></td>";
echo "<td class='datos'><input type='text' name='name' size='25' ";

echo "value='" . $name . "'>";

$own_info = get_user_info ($config['id_user']);
if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM"))
	$return_all_groups = true;
else	
	$return_all_groups = false;

echo "<td><b>".__('Group')."</b></td><td>" .
	html_print_select_groups($config['id_user'], "AR", $return_all_groups, 'graph_id_group', $id_group, '', '', '', true) .
	"</td></tr>";
echo "<tr>";

echo "<td class='datos2'><b>".__('Description')."</b></td>";
echo "<td class='datos2' colspan=3><textarea name='description' style='height:45px;' cols=55 rows=2>";

echo $description;

echo "</textarea>";
echo "</td></tr>";
echo "<tr>";
echo "<td class='datos'>";
echo "<b>".__('Width')."</b></td>";
echo "<td class='datos'>";
echo "<input type='text' name='width' value='$width' size=6></td>";
echo "<td class='datos2'>";
echo "<b>".__('Height')."</b></td>";
echo "<td class='datos2'>";
echo "<input type='text' name='height' value='$height' size=6></td></tr>";

$periods = array(3600 => "1 ".__('hour'), 7200 => "2 ".__('hours'), 10800 => "3 ".__('hours'),
					21600 => "6 ".__('hours'), 43200 => "12 ".__('hours'), 86400 => "1 ".__('day'),
					172800 => "2 ".__('days'), 345600 => "4 ".__('days'), 604800 => __('Last week'),
					1296000 => "15 ".__('days'), 2592000 => __('Last month'), 5184000 => "2 ".__('months'),
					15552000 => "6 ".__('months'), 31104000 => __('1 year'), 31104000 => __('1 year'));

echo "<tr>";
echo "<td class='datos'>";
echo "<b>".__('Period')."</b></td>";
echo "<td class='datos'>";
html_print_select ($periods, 'period', $period);
echo "</td><td class='datos2'>";
echo "<b>".__('Stacked')."</b></td>";
echo "<td class='datos2'>";

include_once($config["homedir"] . "/include/functions_graph.php");

$stackeds = array(
	GRAPH_AREA => __('Area'),
	GRAPH_STACKED_AREA => __('Stacked area'),
	GRAPH_LINE => __('Line'),
	GRAPH_STACKED_LINE => __('Stacked line'));
html_print_select ($stackeds, 'stacked', $stacked);
echo "</td>";

echo "<tr>";
echo "<td class='datos'>";
echo "<b>".__('View events')."</b></td>";
echo "<td class='datos'>";
html_print_checkbox('events', 1, $events);
echo "</td>";

echo "<td></td><td></td>";

echo "</tr>";
echo "<tr><td colspan='4' align='right'>";
if ($id_template) {
	echo "<input type=submit class='sub upd' value='".__('Update')."'>";
}
else {
	echo "<input type=submit class='sub next' value='".__('Create')."'>";
}
echo "</td></tr></table>";
echo "</form>";
