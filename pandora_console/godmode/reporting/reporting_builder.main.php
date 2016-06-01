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

if (! check_acl ($config['id_user'], 0, "RW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access report builder");
	require ("general/noaccess.php");
	exit;
}

require_once ($config['homedir'].'/include/functions_users.php');

$groups = users_get_groups ();

switch ($action) {
	case 'new':
		$actionButtonHtml = html_print_submit_button(__('Save'),
			'add', false, 'class="sub wand"', true);
		$hiddenFieldAction = 'save';
		break;
	case 'update':
	case 'edit':
		$actionButtonHtml = html_print_submit_button(__('Update'),
			'edit', false, 'class="sub upd"', true);
		$hiddenFieldAction = 'update';
		break;
}

$table = new stdClass();
$table->width = '100%';
$table->id = 'add_alert_table';
$table->class = 'databox filters';
$table->head = array ();

if (is_metaconsole()) {
	$table->head[0] = __('Main data');
	$table->head_colspan[0] = 4;
	$table->headstyle[0] = 'text-align: center';
}

$table->data = array ();
$table->size = array ();
$table->size = array ();
$table->size[0] = '15%';
$table->size[1] = '90%';
if(!is_metaconsole())
	$table->style[0] = 'font-weight: bold; vertical-align: top;';
else
	$table->style[0] = 'font-weight: bold;';
$table->data['name'][0] = __('Name');
$table->data['name'][1] = html_print_input_text('name', $reportName,
	__('Name'), 80, 100, true, false, true);

$table->data['group'][0] = __('Group');

$write_groups = users_get_groups_for_select(false, "RW",
	users_can_manage_group_all(0, "RW"), true, false, 'id_grupo');

// If the report group is not among the RW groups (special permission) we add it
if (!isset($write_groups[$idGroupReport]) && $idGroupReport) {
	$write_groups[$idGroupReport] = groups_get_name($idGroupReport);
}

$table->data['group'][1] = html_print_select($write_groups, 'id_group',
	$idGroupReport, false, '', '', true,false,false);

if ($report_id_user == $config['id_user'] ||
	is_user_admin ($config["id_user"])) {
	//S/he is the creator of report (or admin) and s/he can change the access.
	$type_access = array('group_view' => __('Only the group can view the report'),
		'group_edit' => __('The next group can edit the report'),
		'user_edit' => __('Only the user and admin user can edit the report')
		);
	$table->data['access'][0] = __('Write Access') .
		ui_print_help_tip(__('For example, you want a report that the people of "All" groups can see but you want to edit only for you or your group.'), true);
	$table->data['access'][1] = html_print_select ($type_access, 'type_access',
		$type_access_selected, 'change_type_access(this)', '', 0, true);
	
	$style = "display: none;";
	if ($type_access_selected == 'group_edit')
		$style = "";
	$table->data['access'][1] .= '<span style="' . $style . '" class="access_subform" id="group_edit">
		' .
		html_print_select_groups(false, "RW", false,
			'id_group_edit', $id_group_edit, false, '', '', true) . '
		</span>';
}

if ($enterpriseEnable) {
	$non_interactive_check = false;
	if (isset($non_interactive)) {
		$non_interactive_check = $non_interactive;
	}
	
	$table->data['interactive_report'][0] = __('Non interactive report');
	$table->data['interactive_report'][1] =__('Yes') .
		'&nbsp;&nbsp;&nbsp;' .
		html_print_radio_button('non_interactive', 1, '',
			$non_interactive_check, true) .'&nbsp;&nbsp;';
	$table->data['interactive_report'][1] .= __('No') .
		'&nbsp;&nbsp;&nbsp;' .
		html_print_radio_button('non_interactive', 0, '',
			$non_interactive_check, true);
}

$table->data['description'][0] = __('Description');
$table->data['description'][1] = html_print_textarea('description', 5, 15, $description, '', true);

echo '<form class="" method="post">';
html_print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
echo $actionButtonHtml;
html_print_input_hidden('action', $hiddenFieldAction);
html_print_input_hidden('id_report', $idReport);
echo '</div></form>';
?>
<script type="text/javascript">
	function change_type_access(select_item) {
		$(".access_subform").hide();
		switch ($(select_item).val()) {
			case 'group_view':
				break;
			case 'group_edit':
				$("#group_edit").show();
				break;
			case 'user_edit':
				break;
		}
	}
</script>
