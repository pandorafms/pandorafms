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
	audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation",
		"Trying to access report builder");
	require ("general/noaccess.php");
	exit;
}

$groups = get_user_groups ();

switch ($action) {
	case 'new':
		$actionButtonHtml = print_submit_button(__('Add'), 'add', false, 'class="sub wand"', true);
		$hiddenFieldAction = 'save'; 
		break;
	case 'update':
	case 'edit':
		$actionButtonHtml = print_submit_button(__('Edit'), 'edit', false, 'class="sub upd"', true);
		$hiddenFieldAction = 'update'; 
		break;
}

$table->width = '80%';
$table->id = 'add_alert_table';
$table->class = 'databox';
$table->head = array ();
$table->data = array ();
$table->size = array ();
$table->size = array ();
$table->size[0] = '10%';
$table->size[1] = '90%';
$table->style[0] = 'font-weight: bold; vertical-align: top;';

$table->data['name'][0] = __('Name');
$table->data['name'][1] = print_input_text('name', $reportName, __('Name'), 20, 40, true);

$table->data['group'][0] = __('Group');
$table->data['group'][1] = print_select ($groups, 'id_group', $idGroupReport, false, '', '', true);

$table->data['description'][0] = __('Description');
$table->data['description'][1] = print_textarea('description', 5, 15, $description, '', true);

echo '<form class="" method="post">';
print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
echo $actionButtonHtml;
print_input_hidden('action', $hiddenFieldAction);
print_input_hidden('id_report', $idReport);
echo '</div></form>';
?>
