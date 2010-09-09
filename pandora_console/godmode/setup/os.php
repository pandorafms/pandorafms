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

if (! give_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
	audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation", "Trying to access Setup Management");
	require ("general/noaccess.php");
	return;
}

$action = get_parameter('action', 'new');
$idOS = get_parameter('id_os', 0);

if ($idOS) {
	$os = get_db_row_filter('tconfig_os', array('id_os' => $idOS));
	$name = $os['name'];
	$description = $os['description'];
	$icon = $os['icon_name'];
}
else {
	$name = get_parameter('name', '');
	$description = get_parameter('description', '');
	$icon = get_parameter('icon',0);
}

// Header
print_page_header(__('Edit OS'), "", false, "", true);

switch ($action) {
	default:
	case 'new':
		$actionHidden = 'save';
		$textButton = __('Create');
		$classButton = 'class="sub next"';
		break;
	case 'edit':
		$actionHidden = 'update';
		$textButton = __('Update');
		$classButton = 'class="sub upd"';
		break;
	case 'save':
		$values = array();
		$values['name'] = $name;
		$values['description'] = $description;
		
		if (($icon !== 0) && ($icon != '')) {
			$values['icon_name'] = $icon;
		}
		$resultOrId = process_sql_insert('tconfig_os', $values);
		
		if ($resultOrId === false) {
			print_error_message(__('Fail to create OS'));
			$actionHidden = 'save';
			$textButton = __('Create');
			$classButton = 'class="sub next"';
		}
		else {
			$idOs = $resultOrId;
			print_success_message(__('Success to create OS'));
			$actionHidden = 'update';
			$textButton = __('Update');
			$classButton = 'class="sub upd"';
		}
		break;
	case 'update':
		$name = get_parameter('name', '');
		$description = get_parameter('description', '');
		$icon = get_parameter('icon',0);
		
		$values = array();
		$values['name'] = $name;
		$values['description'] = $description;
		
		if (($icon !== 0) && ($icon != '')) {
			$values['icon_name'] = $icon;
		}
		$result = process_sql_update('tconfig_os', $values, array('id_os' => $idOS));
		
		print_result_message($result, __('Success to update OS'), __('Error to update OS'));
		
		$actionHidden = 'update';
		$textButton = __('Update');
		$classButton = 'class="sub upd"';
		break;
	case 'delete':
		$sql = 'SELECT COUNT(id_os) AS count FROM tagente WHERE id_os = ' . $idOS;
		$count = get_db_all_rows_sql($sql);
		$count = $count[0]['count'];
		
		if ($count > 0) {
			print_error_message(__('There are agents with this OS.'));
		}
		else {
			$result = (bool)process_sql_delete('tconfig_os', array('id_os' => $idOS));
			
			print_result_message($result, __('Success to delete'), __('Error to delete'));
		}
		
		$idOS = 0;
		$name = get_parameter('name', '');
		$description = get_parameter('description', '');
		$icon = get_parameter('icon',0);
		
		$actionHidden = 'save';
		$textButton = __('Create');
		$classButton = 'class="sub next"';
		break;
}

$table = null;

$table->width = '80%';
$table->head[0] = '';
$table->head[1] = __('Name');
$table->head[2] = __('Description');
$table->head[3] = '';
$table->align[0] = 'center';
$table->align[3] = 'center';
$table->size[0] = '20px';
$table->size[3] = '20px';

$osList = get_db_all_rows_in_table('tconfig_os');

$table->data = array();
foreach ($osList as $os) {
	$data = array();
	$data[] = print_os_icon($os['id_os'], false, true);
	$data[] = '<a href="index.php?sec=gsetup&sec2=godmode/setup/os&action=edit&id_os=' . $os['id_os'] . '">' . safe_output($os['name']) . '</a>';
	$data[] = printTruncateText(safe_output($os['description']), 25, true, true);
	if ($os['id_os'] > 13) {
		$data[] = '<a href="index.php?sec=gsetup&sec2=godmode/setup/os&action=delete&id_os=' . $os['id_os'] . '"><img src="images/cross.png" /></a>';
	}
	else {
		//The original icons of pandora don't delete.
		$data[] = '';
	}
	
	$table->data[] = $data;
}

$htmlListOS = print_table($table, true);

toggle($htmlListOS,__('List of OS'), __('Toggle'));

echo '<form id="form_setup" method="post">';
unset($table->head);
unset($table->align);
unset($table->size);
unset($table->data);

$table->width = '50%';

$table->style[0] = 'font-weight: bolder; vertical-align: top;';

$table->data[0][0] = __('Name:');
$table->data[0][1] = print_input_text('name', $name, __('Name'), 20, 30, true);
$table->data[1][0] = __('Description');
$table->data[1][1] = print_textarea('description', 5, 10, $description, '', true);
$icons = get_list_os_icons_dir();
$table->data[2][0] = __('Icon');
$table->data[2][1] = print_select($icons, 'icon',  $icon, 'show_icon_OS();', __('None'), 0, true);
$table->data[2][1] .= ' <span id="icon_image">' . print_os_icon($idOS, false, true) . '</span>';


echo '<div class="action-buttons" style="width: '.$table->width.'">';
print_button(__('New OS'), 'new_button', false, 'new_os();', 'class="sub add"');
echo '</div>';
echo '<form action="post">';
print_table($table);

print_input_hidden('id_os', $idOS);
print_input_hidden ('action', $actionHidden);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
print_submit_button ($textButton, 'update_button', false, $classButton);
echo '</div>';
echo '</form>';

function get_list_os_icons_dir() {
	global $config;
	
	$return = array();
	
	$items = scandir($config['homedir'] . '/images/os_icons');
	
	foreach ($items as $item) {
		if (strstr($item, '_small.png') || strstr($item, '_small.gif')
			|| strstr($item, '_small.jpg')) {
			continue;
		}
		if (strstr($item, '.png') || strstr($item, '.gif')
			|| strstr($item, '.jpg')) {
			$return[$item] = $item;
		}
	}
	
	return $return;
}
?>
<script type="text/javascript">
function new_os() {
	location.href='<?php
		if ($config['https']) echo "https://";
		else echo "http://";
		echo $_SERVER['SERVER_NAME'] . $config['homeurl'] . '/index.php?sec=gsetup&sec2=godmode/setup/os&action=new';
		?>';
}

function show_icon_OS() {
	$("#icon_image").html('<img src="images/os_icons/' + $("#icon").val() + '" />');
}
</script>