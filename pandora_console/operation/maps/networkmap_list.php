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

check_login ();

// ACL for the general permission
$networkmaps_read = check_acl ($config['id_user'], 0, "MR");
$networkmaps_write = check_acl ($config['id_user'], 0, "MW");
$networkmaps_manage = check_acl ($config['id_user'], 0, "MM");

if (!$networkmaps_read && !$networkmaps_write && !$networkmaps_manage) {
	db_pandora_audit("ACL Violation",
		"Trying to access Networkmap builder");
	if (is_ajax()) {
		return;
	}
	else {
		include ("general/noaccess.php");
		exit;
	}
}

$migrate_open_networkmaps = (int)get_parameter('migrate_open_networkmaps');

if ($migrate_open_networkmaps)
	migration_open_networkmaps();

ui_print_page_header(__('Network map'), "images/op_network.png", false, "network_map", false);

$id = (int)get_parameter('id_networkmap', 0);
$delete_networkmap = (bool)get_parameter('delete_networkmap', 0);
$duplicate_networkmap = (bool)get_parameter('duplicate_networkmap', 0);

if ($delete_networkmap) {
	$result_delete = networkmap_delete_networkmap($id);

	if ($result_delete)
		db_pandora_audit( "Networkmap management", "Delete networkmap #$id");
	else
		db_pandora_audit( "Networkmap management", "Fail try to delete networkmap #$id");

	ui_print_result_message ($result_delete,
		__('Successfully deleted'),
		__('Could not be deleted'));
}

if ($duplicate_networkmap) {
	//FUNCION
	//$result_duplicate = networkmap_duplicate($id);
	$result_duplicate = array();

	ui_print_result_message ($result,
		__('Successfully duplicate'),
		__('Could not be duplicate'));
}

$table = new stdClass();
$table->width = "100%";
$table->class = "databox data";
$table->headstyle['name'] = 'text-align: center;';
$table->headstyle['type'] = 'text-align: center;';
if (enterprise_installed()) {
	$table->headstyle['nodes'] = 'text-align: center;';
}
$table->headstyle['group'] = 'text-align: center;';
$table->headstyle['copy'] = 'text-align: center;';
$table->headstyle['edit'] = 'text-align: center;';
$table->headstyle['delete'] = 'text-align: center;';

$table->style = array();
$table->style['name'] = 'text-align: left;';
$table->style['type'] = 'text-align: center;';
if (enterprise_installed()) {
	$table->style['nodes'] = 'text-align: center;';
}
$table->style['group'] = 'text-align: center;';
$table->style['copy'] = 'text-align: center;';
$table->style['edit'] = 'text-align: center;';
$table->style['delete'] = 'text-align: center;';

$table->size = array();
$table->size['name'] = '60%';
$table->size['type'] = '30px';
if (enterprise_installed()) {
	$table->size['nodes'] = '30px';
}
$table->size['group'] = '30px';
$table->size['copy'] = '30px';
$table->size['edit'] = '30px';
$table->size['delete'] = '30px';

$table->head = array();
$table->head['name'] = __('Name');
$table->head['type'] = __('Type');
if (enterprise_installed()) {
	$table->head['nodes'] = __('Nodes');
}
$table->head['group'] = __('Group');
$table->head['copy'] = __('Copy');
$table->head['edit'] = __('Edit');
$table->head['delete'] = __('Delete');

//FUNCION
//$networkmaps = networkmap_get_networkmaps();
$networkmaps = array();

if (empty($networkmaps)) {
	ui_print_info_message (
		array('no_close'=>true,
			'message'=> __('There are no networkmaps defined.') ) );
}
else {
	foreach ($networkmaps as $networkmap) {
		$data = array();

		$data['name'] = $networkmap['name'];

		/*CUANDO HAYA VENTANA DE EDICIÓN SE REDIRIGE ALLÍ
		$data['name'] = '<a href="index.php?' .
			'sec=maps&' .
			'sec2=enterprise/dashboard/main_dashboard&' .
			'id_dashboard=' . $networkmap['id'] .'">' .
			$networkmap['name'] . '</a>';*/

		$data['type'] = $networkmap['type'];

		if (enterprise_installed()) {
			//FUNCION
			//$data['nodes'] = networkmap_get_nodes();
			$data['nodes'] = 0;
		}

		if (!empty($networkmap['id_user'])) {
			$data['group'] = __('Private for (%s)', $networkmap['id_user']);
		}
		else {
			$data['groups'] =
				ui_print_group_icon($networkmap['id_group'], true);
		}

		$data['copy'] = '<a href="index.php?' . '" alt="' . __('Copy') . '">' .
			html_print_image("images/copy.png", true) . '</a>';

			/*CUANDO HAYA FORMA DE COPIAR SE ACTUALIZARÁ
			'<a href="index.php?' .
			'sec=reporting&amp;' .
			'sec2=' . ENTERPRISE_DIR . '/dashboard/dashboards&amp;' .
			'duplicate_dashboard=1&amp;id_dashboard=' . $dashboard['id'] . '" alt="' . __('Copy') . '">' .
			html_print_image("images/copy.png", true) . '</a>';*/

		$data['edit'] = '<a href="index.php?' . '" alt="' . __('Edit') . '">' .
			html_print_image("images/edit.png", true) . '</a>';

			/*CUANDO HAYA FORMA DE EDITAR SE ACTUALIZARÁ*/

		$data['delete'] = '<a href="index.php?' . '" alt="' . __('Delete') . '" onclick="javascript: if (!confirm(\'' . __('Are you sure?') . '\')) return false;">' .
			html_print_image('images/cross.png', true) . '</a>';

			/*CUANDO HAYA FORMA DE BORRAR SE ACTUALIZARÁ
			'<a href="index.php?' .
			'sec=reporting&amp;' .
			'sec2=' . ENTERPRISE_DIR . '/dashboard/dashboards&amp;' .
			'delete_dashboard=1&amp;id_dashboard=' . $dashboard['id'] . '" alt="' . __('Delete') . '" onclick="javascript: if (!confirm(\'' . __('Are you sure?') . '\')) return false;">' .
			html_print_image('images/cross.png', true) . '</a>';*/

		$table->data[] = $data;
	}
	html_print_table($table);
}

?>
<a href="index.php?sec=network&sec2=operation/maps/networkmap_list&migrate_open_networkmaps=1">(temp, this is for minor relases) migrate open networkmaps</a>

<script type="text/javascript">
</script>
