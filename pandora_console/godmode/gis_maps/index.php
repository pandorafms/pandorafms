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

require_once ('include/functions_gis.php');

ui_require_javascript_file('openlayers.pandora');

if (! check_acl ($config['id_user'], 0, "IW")) {
	db_pandora_audit("ACL Violation", "Trying to access map builder");
	require ("general/noaccess.php");
	return;
}

if (is_ajax ()) {
	$action = get_parameter('action');
	$id_map = get_parameter('id_map');
	
	// Set to not default the actual default map
	$returnOperationDB =  db_process_sql_update('tgis_map', array('default_map' => 0), array('default_map' => 1));

	// Set default the new default map
	$returnOperationDB =  db_process_sql_update('tgis_map', array('default_map' => 1), array('id_tgis_map' => $id_map));
	
	if ($returnOperationDB === false)
		$data['correct'] = false;
	else
		$data['correct'] = true;
	
	echo json_encode($data);
	
	return;
}


$action = get_parameter('action');
$own_info = get_user_info($config['id_user']);
if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM"))
	$display_default_column = true;
else
	$display_default_column = false;

switch ($action) {
	case 'delete_map':
		$idMap = get_parameter('map_id');
		gis_delete_map($idMap);
		break;
}

ui_print_page_header (__('GIS Maps builder'), "images/server_web.png", false, "gis_map_builder", true);

$table->width = '98%';
$table->head[0] = __('Map name');
$table->head[1] = __('Group');
$table->head[2] = __('View');
if ($display_default_column)
	$table->head[3] = __('Default');
$table->head[4] = '<span title="Operations">' . __('Op.') . '</span>';

$table->align[1] = 'center';
$table->align[2] = 'center';
$table->align[3] = 'center';
$table->align[4] = 'center';
$table->size = array();
$table->size[4] = '60px';

$maps = db_get_all_rows_in_table ('tgis_map','map_name');

$table->data = array();

$defaultMapId = null;

if ($maps){
	$own_info = get_user_info($config['id_user']);
	foreach ($maps as $map) {
		if (!check_acl ($config["id_user"], $map["group_id"], "IR")) {
			continue;
		}
		if ($map['group_id'] == 0 && (!$own_info['is_admin'] || !check_acl ($config['id_user'], 0, "PM")))
			continue;
		$checked = false;
		if ($map['default_map']) {
			$checked = true;
			$defaultMapId = $map['id_tgis_map'];
		}
		
		$table_info = array('<a href="index.php?sec=godgismaps&sec2=godmode/gis_maps/configure_gis_map&map_id='.$map['id_tgis_map'].'&amp;action=edit_map">' . $map['map_name'] . '</a>',
			ui_print_group_icon ($map['group_id'], true),
			'<a href="index.php?sec=gismaps&sec2=operation/gis_maps/render_view&map_id='.$map['id_tgis_map'].'">' . html_print_image ("images/eye.png", true).'</a>');
		if ($display_default_column) {
			$default_button = html_print_radio_button_extended('default_map', $map['id_tgis_map'], '', $checked, false, "setDefault(" . $map['id_tgis_map'] . ");", '', true);
			array_push($table_info, $default_button);
		}
		$buttons = '<a href="index.php?sec=godgismaps&amp;sec2=godmode/gis_maps/configure_gis_map&map_id='.$map['id_tgis_map'].'&amp;action=edit_map">' . html_print_image ("images/config.png", true).'</a>&nbsp;&nbsp;';
		$buttons .= '<a href="index.php?sec=godgismaps&amp;sec2=godmode/gis_maps/index&amp;map_id='.$map['id_tgis_map'].'&amp;action=delete_map" onclick="return confirmDelete();">' . html_print_image ("images/cross.png", true).'</a>';
		array_push ($table_info, $buttons);
		$table->data[] = $table_info;
	}
}
	
if (!empty ($table->data)) {
	html_print_table($table);
}
else {
	echo '<div class="nf">'.('No maps defined').'</div>';	
}

echo '<div class="action-buttons" style="width: '.$table->width.'; margin-top: 5px;">';
echo '<form action="index.php?sec=godgismaps&amp;sec2=godmode/gis_maps/configure_gis_map" method="post">';
html_print_input_hidden ('action','new_map');
html_print_submit_button (__('Create'), '', false, 'class="sub next"');
echo '</form>';
echo '</div>';
?>
<script type="text/javascript">
var defaultMapId = "<?php echo $defaultMapId; ?>";

function confirmDelete() {
	if (confirm('<?php echo __('Caution: Do you want delete the map?');?>'))
		return true;
	
	return false;
}

function setDefault(id_tgis_map) {
	if (confirm('<?php echo __('Do you want to set default the map?');?>')) {
		jQuery.ajax ({
			data: "page=godmode/gis_maps/index&action=set_default&id_map="  + id_tgis_map,
			type: "POST",
			dataType: 'json',
			url: "ajax.php",
			timeout: 10000,
			success: function (data) {
				if (data.correct == 0) {
					alert('<?php echo __('There was error on setup the default map.');?>');
				}
			}
		});
	}
	else {
		jQuery.each($("input[name=default_map]"), function() {
			if ($(this).val() == defaultMapId) {
				$(this).attr("checked", "checked");
			}
			else {
				$(this).removeAttr("checked");
			}
		});
	}
}
</script>
