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

require_once('include/functions_networkmap.php');

if (is_ajax()) {
	ob_clean();

	$delete_networkmaps = (bool) get_parameter('delete_networkmaps');
	if ($delete_networkmaps) {
		
		$results = array();
		$ids_networkmap = (array) get_parameter('ids_networkmap');
		
		foreach ($ids_networkmap as $id) {
			$store_group = (int) db_get_value('store_group', 'tnetwork_map', 'id_networkmap',$id_networkmap);
			
			// ACL
			// $networkmap_read = check_acl ($config['id_user'], $store_group, "MR");
			$networkmap_write = check_acl ($config['id_user'], $store_group, "MW");
			$networkmap_manage = check_acl ($config['id_user'], $store_group, "MM");
			
			if ($networkmap_manage) {
				$results[$id] = (bool) networkmap_delete_networkmap($id);
			}
			else if ($networkmap_write) {
				$results[$id] = (bool) networkmap_delete_user_networkmap($config['id_user'], $id);
			}
		}
		
		// None permission
		if (!empty($ids_networkmap) && empty($results)) {
			db_pandora_audit("ACL Violation", "Trying to access Networkmap deletion");
			$results = -1;
		}
		
		echo json_encode($results);
		return;
	}
	return;
}

$strict_user = db_get_value('strict_acl', 'tusuario', 'id_user', $config['id_user']);

ui_print_page_header(__('Network map'), "images/op_network.png", false, "network_map", false);

// Delete networkmap action
$id_networkmap = get_parameter ('id_networkmap', 0);
$delete_networkmap = get_parameter ('delete_networkmap', 0);

if ($delete_networkmap) {

	// ACL
	// $networkmap_read = check_acl ($config['id_user'], $store_group, "MR");
	$networkmap_write = check_acl ($config['id_user'], $store_group, "MW");
	$networkmap_manage = check_acl ($config['id_user'], $store_group, "MM");
	
	if ($networkmap_manage || is_user_admin ($config['id_user'])) {
		$result = networkmap_delete_networkmap($id_networkmap);
	}
	else if ($networkmap_write) {
		$result = networkmap_delete_user_networkmap($config['id_user'], $id_networkmap);
	}
	$message = ui_print_result_message ($result,
		__('Network map deleted successfully'),
		__('Could not delete network map'), '', true);
		
	echo $message;
	$id_networkmap = 0;
}

// Filter form
$group_search = (int) get_parameter('group_search');
$type_search = get_parameter('type_filter', '0');

// Display table
$table = new StdClass();
$table->width = "100%";
$table->class = "databox data";

$table->style = array();
$table->style[0] = '';
$table->style[1] = 'text-align: left;';
$table->style[2] = 'text-align: left;';
$table->style[3] = 'text-align: left;';
$table->style[4] = 'text-align: left;';

$table->size = array();
$table->size[0] = '60%';
$table->size[1] = '60px';
$table->size[2] = '70px';

if ($networkmaps_write || $networkmaps_manage) {
	$table->size[3] = '30px';
	$table->size[4] = '30px';
}

$table->head = array();
$table->head[0] = __('Name');
$table->head[1] = __('Type');
$table->head[2] = __('Group');
if ($networkmaps_write || $networkmaps_manage) {
	$table->head[3] = __('Delete');
	// Checkbox to select all the another checkboxes
	$table->head[4] = html_print_checkbox('check_delete_all', 0, false, true);
}
$id_groups = array_keys(users_get_groups());

// Create filter
$where = array();
$where['store_group'] = $id_groups;
// Order by type field
$where['order'] = 'type';

if (!empty($group_search))
	$where['store_group'] = $group_search;

if ($type_search != '0')
	$where['type'] = $type_search;

//Check for maps only visible for this user
$user_info = users_get_user_by_id($config['id_user']);

$network_maps = db_get_all_rows_filter('tnetwork_map', $where);
$count_maps  = db_get_all_rows_filter('tnetwork_map');
if ($count_maps === false) {
	require($config['homedir']."/general/firts_task/network_map.php");
}
else {
	?>
	<form method="post" action="index.php?sec=network&amp;sec2=operation/agentes/networkmap_list">
		<table style='width: 100%' class='databox filters'>
			<tr>
				<td class='datos' style="font-weight:bold;">
					<?php echo __('Group'); ?>
				</td>
				<td class='datos'>
					<?php
					html_print_select_groups($config['id_user'], 'AR',
						true, 'group_search', $group_search);
					?>
				</td>
				<td class='datos' style="font-weight:bold;">
					<?php echo __('Type'); ?>
				</td>
				<td class='datos'>
					<?php
					$networkmap_filter_types = networkmap_get_filter_types($strict_user);
					html_print_select($networkmap_filter_types, 'type_filter',
						$type_search, '', __('All'), 0, false);
					?>
				</td>
				<td class='datos'>
					<?php
					html_print_submit_button (__('Filter'), 'crt', false,
						'class="sub search"');
					?>
				</td>
			</tr>
		</table>
	</form>
	<?php

	$table->data = array();
	foreach ($network_maps as $network_map) {
		$store_group = (int) db_get_value('store_group',
			'tnetwork_map', 'id_networkmap',
			$network_map['id_networkmap']);
		
		// ACL
		$networkmap_read = check_acl ($config['id_user'], $store_group, "MR");
		$networkmap_write = check_acl ($config['id_user'], $store_group, "MW");
		$networkmap_manage = check_acl ($config['id_user'], $store_group, "MM");
		
		// ACL
		if (!$networkmap_read && !$networkmap_write && !$networkmap_manage)
			continue;
		
		// If enterprise not loaded then skip this code
		if ($network_map['type'] == 'policies' && !defined('PANDORA_ENTERPRISE'))
			continue;
	
		if (($network_map['type'] == 'radial_dynamic' || $network_map['type'] == 'policies') && $strict_user) {
			continue;
		}
		
		$data = array();
		$data[0] = '<b><a href="index.php?sec=network&sec2=operation/agentes/networkmap&tab=' . $network_map['type']
			. '&id_networkmap=' . $network_map['id_networkmap'] . '">' . io_safe_output($network_map['name']) . '</a></b>';
		$data[1] = $network_map['type'];
		$data[2] = ui_print_group_icon ($network_map['store_group'], true);
		
		if ($networkmap_write || $networkmap_manage) {
			$data[3] = '<a href="index.php?sec=network&sec2=operation/agentes/networkmap_list&delete_networkmap=1&id_networkmap=' . $network_map['id_networkmap'] . '" alt="' . __('Delete') . '" onclick="javascript: if (!confirm(\'' . __('Are you sure?') . '\')) return false;">' . html_print_image('images/cross.png', true) . '</a>';
			// The value of the checkbox will be the networkmap id to recover it in js to perform the massive deletion
			$data[4] = html_print_checkbox('check_delete', $network_map['id_networkmap'], false, true);
		}
		
		$table->data[] = $data;
	}
	
	html_print_table($table);
	// Create networkmap form
	if ($networkmaps_write || $networkmaps_manage) {
		$table_manage = new StdClass();
		$table_manage->width = "100%";
		$table_manage->class = "databox filters";
		$table_manage->style = array();
		$table_manage->style[0] = 'font-weight: bold';
		$table_manage->style[2] = 'font-weight: bold';
		$table_manage->style[4] = 'text-align: center';
		$table_manage->size = array();
		$table_manage->head = array();
		$table_manage->data = array();

		$actions = array(
				'create' => __('Create'),
				'delete' => __('Delete')
			);
		$networkmap_types = networkmap_get_types($strict_user);
		$delete_options = array(
				'selected' => __('Delete selected')
			);

		$row = array();
		$row[] = __('Action');
		$row[] = html_print_select($actions, 'action', 'create', '', '', 0, true, false, false);
		$row[] = __('Type');
		$row[] = html_print_select($networkmap_types, 'tab', 'topology', '', '', 0, true)
			. html_print_select($delete_options, 'delete_options', 'selected', '', '', 0, true, false, false);
		$row[] = html_print_submit_button (__('Execute'), 'crt', false, 'class="sub next"', true)
			. html_print_image("images/spinner.gif", true, array('id' => 'action-loading', 'style' => 'display: none;'));

		$table_manage->data[] = $row;

		echo '<form id="networkmap_action" method="post" action="index.php?sec=network&amp;sec2=operation/agentes/networkmap">';
		html_print_table($table_manage);
		html_print_input_hidden('add_networkmap', 0);
		html_print_input_hidden('delete_networkmaps', 0);
		echo "</form>";
	}
}
?>

<script type="text/javascript">

	// Add a listener to the 'All' checkbox to select or unselect all the another checkboxes
	$("input[name=\"check_delete_all\"]").change(function (e) {
		e.preventDefault();

		$("input[name=\"check_delete\"]").prop("checked", this.checked);
	});
	$("input[name=\"check_delete\"]").change(function (e) {
		e.preventDefault();

		if (!this.checked)
			$("input[name=\"check_delete_all\"]").prop("checked", false);
	});

	// Add a listener to change the action options
	$("select#action").change(function (e) {
		e.preventDefault();

		$selectCreateOptions = $("select#tab");
		$selectDeleteOptions = $("select#delete_options");

		$hiddenCreate = $("input#hidden-add_networkmap");
		$hiddenDelete = $("input#hidden-delete_networkmaps");

		if ($(this).val() == 'create') {
			$selectCreateOptions.show();
			$hiddenCreate.val(1);

			$selectDeleteOptions.hide();
			$hiddenDelete.val(0);
		}
		else if ($(this).val() == 'delete') {
			$selectCreateOptions.hide();
			$hiddenCreate.val(0);

			$selectDeleteOptions.show();
			$hiddenDelete.val(1);
		}
	}).change();

	$("form#networkmap_action").submit(function (e) {
		if ($("input#submit-crt").prop('disabled')) {
			e.preventDefault();
		}
		else if ($("select#action").val() == 'delete') {
			e.preventDefault();

			networkmap_delete_list_items();
		}
	});

	function networkmap_delete_list_items () {
		var networkmapIDs = [];

		var $btnSubmit = $("input#submit-crt");
		var $imgLoading = $("img#action-loading");

		$btnSubmit.hide();
		$btnSubmit.prop('disabled', true);
		$imgLoading.show();

		$("input[name=\"check_delete\"]:checked").each(function (index, element) {
			networkmapIDs.push($(element).val());
		});

		if (networkmapIDs.length > 0) {
			$.ajax({
				type: 'POST',
				url: 'ajax.php',
				data: {
					page: "operation/agentes/networkmap_list",
					delete_networkmaps: 1,
					ids_networkmap: networkmapIDs
				},
				dataType: "json",
				complete: function () {
					$btnSubmit.show();
					$btnSubmit.prop('disabled', false);
					$imgLoading.hide();
				},
				success: function (data) {

					if (data === -1) {
						alert("<?php echo __('The session may be expired'); ?>");
					}
					else if (!$.isEmptyObject(data)) {

						$.each(data, function (id, result) {
							if (result) {
								$("input[name=\"check_delete\"][value=\""+id+"\"]")
									.parent().parent().remove();
							}
						});
					}
				},
				fail: function () {
					alert("<?php echo __('Error'); ?>");
				}
			});
		}
		else {
			$btnSubmit.show();
			$btnSubmit.prop('disabled', false);
			$imgLoading.hide();

			alert("<?php echo __('None selected'); ?>");
		}
	}

</script>
