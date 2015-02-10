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

if (! check_acl ($config['id_user'], 0, "AR")) {
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
		if ( check_acl ($config['id_user'], 0, "RW") ||  check_acl ($config['id_user'], 0, "RM") ) {
			if (check_acl ($config['id_user'], 0, "RM")) {
					$result = false;
					$results = array();
					$ids_networkmap = (array) get_parameter ('ids_networkmap');
					foreach ($ids_networkmap as $id) {
						$results[$id] = (bool) networkmap_delete_networkmap($id);
					}
					echo json_encode($results);
					return;
				}
			else{
				if (check_acl ($config['id_user'], 0, "RW")) {
					$result = false;
					$results = array();
					$ids_networkmap = (array) get_parameter ('ids_networkmap');
					foreach ($ids_networkmap as $id) {
						$results[$id] = (bool) networkmap_delete_user_networkmap($config['id_user'], $id);
					}
					echo json_encode($results);
					return;
				}
			}
		}else{
		db_pandora_audit("ACL Violation",
				"Trying to access Networkmap deletion");
			echo json_encode(-1);
			return;
		}
	}
	return;
}

$strict_user = db_get_value('strict_acl', 'tusuario', 'id_user', $config['id_user']);

ui_print_page_header(__('Network map'), "images/op_network.png", false, "network_map", false);

// Delete networkmap action
$id_networkmap = get_parameter ('id_networkmap', 0);
$delete_networkmap = get_parameter ('delete_networkmap', 0);

if ($delete_networkmap) {
	if (is_user_admin ($config['id_user'])){
		$result = networkmap_delete_networkmap($id_networkmap);
	}
	elseif (check_acl ($config['id_user'], 0, "RM")) {
		$result = networkmap_delete_networkmap($id_networkmap);
	}elseif (check_acl ($config['id_user'], 0, "RW")) {
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

?>
<form method="post" action="index.php?sec=network&amp;sec2=operation/agentes/networkmap_list">
	<table style='width: 100%' class='databox'>
		<tr>
			<td class='datos' >
				<?php echo __('Group'); ?>
			</td>
			<td class='datos'>
				<?php
				html_print_select_groups($config['id_user'], 'AR',
					true, 'group_search', $group_search);
				?>
			</td>
			<td class='datos'>
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

// Display table
$table = new StdClass();
$table->width = "98%";

$table->style = array();
$table->style[0] = '';
$table->style[1] = 'text-align: center;';
$table->style[2] = 'text-align: center;';
$table->style[3] = 'text-align: center;';
$table->style[4] = 'text-align: center;';
$table->style[5] = 'text-align: center;';

$table->size = array();
$table->size[0] = '80%';
$table->size[1] = '60px';
$table->size[2] = '30px';

if (check_acl ($config['id_user'], 0, "RW") || check_acl ($config['id_user'], 0, "RM")) {
	$table->size[3] = '30px';
	$table->size[4] = '30px';
}

$table->head = array();
$table->head[0] = __('Name');
$table->head[1] = __('Type');
$table->head[2] = __('Group');
if (check_acl ($config['id_user'], 0, "RW") || check_acl ($config['id_user'], 0, "RM")) {
	$table->head[3] = __('Edit');
	$table->head[4] = __('Delete');
	// Checkbox to select all the another checkboxes
	$table->head[5] = html_print_checkbox('check_delete_all', 0, false, true);
}
$id_groups = array_keys(users_get_groups());

// Create filter
$where = array();
$where['id_group'] = $id_groups;
// Order by type field
$where['order'] = 'type';

if (!empty($group_search))
	$where['id_group'] = $group_search;

if ($type_search != '0')
	$where['type'] = $type_search;

//Check for maps only visible for this user
$user_info = users_get_user_by_id($config['id_user']);

//If the user is not admin only user map are shown.
//if (!$user_info['is_admin']) {
//	$where['id_user'] = $config['id_user'];
//}

$network_maps = db_get_all_rows_filter('tnetwork_map', $where);

if ($network_maps === false) {
	echo "<div class='warn'>" . __('Not networkmap defined.') .
		"</div>";
}
else {
	$table->data = array();
	foreach ($network_maps as $network_map) {
		// If enterprise not loaded then skip this code
		if ($network_map['type'] == 'policies' and (!defined('PANDORA_ENTERPRISE')))
			continue;
	
		if (($network_map['type'] == 'radial_dynamic' || $network_map['type'] == 'policies') && ($strict_user)) {
			continue;
		}
			
		$data = array();
		$data[0] = '<b><a href="index.php?sec=network&sec2=operation/agentes/networkmap&tab=view&id_networkmap=' . $network_map['id_networkmap'] . '">' . $network_map['name'] . '</a></b>';
		$data[1] = $network_map['type'];
		
		$data[2] = ui_print_group_icon ($network_map['id_group'], true);
		if (check_acl ($config['id_user'], 0, "RW") || check_acl ($config['id_user'], 0, "RM")) {
			$data[3] = '<a href="index.php?sec=network&sec2=operation/agentes/networkmap&tab=edit&edit_networkmap=1&id_networkmap=' . $network_map['id_networkmap'] . '" alt="' . __('Config') . '">' . html_print_image("images/config.png", true) . '</a>';
			$data[4] = '<a href="index.php?sec=network&sec2=operation/agentes/networkmap_list&delete_networkmap=1&id_networkmap=' . $network_map['id_networkmap'] . '" alt="' . __('Delete') . '" onclick="javascript: if (!confirm(\'' . __('Are you sure?') . '\')) return false;">' . html_print_image('images/cross.png', true) . '</a>';
			// The value of the checkbox will be the networkmap id to recover it in js to perform the massive deletion
			$data[5] = html_print_checkbox('check_delete', $network_map['id_networkmap'], false, true);
		}
		
		$table->data[] = $data;
	}
	
	html_print_table($table);
}

// Create networkmap form
if (check_acl ($config['id_user'], 0, "RW") || check_acl ($config['id_user'], 0, "RM")) {
	$table_manage = new StdClass();
	$table_manage->width = "100%";
	$table_manage->style = array();
	$table_manage->style[0] = 'font-weight: bold';
	$table_manage->style[3] = 'text-align: right';
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
		if ($("input#submit-crt").prop('disabled')) {console.log("asd");
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
					console.log(data);

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
