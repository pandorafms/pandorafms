<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Load global vars
require_once ("include/config.php");

check_login ();

require_once ('include/functions_gis.php');

require_javascript_file('openlayers.pandora');

if (! give_acl ($config['id_user'], 0, "IW")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to access map builder");
	require ("general/noaccess.php");
	return;
}

if (is_ajax ()) {
	$action = get_parameter('action');
	$id_map = get_parameter('id_map');
	
	$returnOperationDB =  process_sql_update('tgis_map', array('default_map' => 1), array('id_tgis_map' => $id_map));
	
	if ($returnOperationDB === false)
		$data['correct'] = false;
	else
		$data['correct'] = true;
	
	echo json_encode($data);
	
	return;
}


$action = get_parameter('action');

switch ($action) {
	case 'delete_map':
		$idMap = get_parameter('id_map');
		deleteMap($idMap);
		break;
}

echo "<h2>" . __('GIS Maps') . " &raquo; " . __('Builder') . "</h2>";

$table->width = '500px';
$table->head[0] = __('Map name');
$table->head[1] = __('Group');
$table->head[2] = __('View');
$table->head[3] = __('Default');
$table->head[4] = __('Delete');

$table->align[1] = 'center';
$table->align[2] = 'center';
$table->align[3] = 'center';
$table->align[4] = 'center';

$maps = get_db_all_rows_in_table ('tgis_map','map_name');

$table->data = array();

if ($maps !== false) {
	foreach ($maps as $map) {
		
		$checked = false;
		if ($map['default_map'])
			$checked = true;
		
		$table->data[] = array('<a href="index.php?sec=gismaps&sec2=operation/gis_maps/render_view&id='.$map['id_tgis_map'].'&amp;action=edit_map">' . $map['map_name'] . '</a>',
			print_group_icon ($map['group_id'], true),
			'<a href="index.php?sec=gismaps&sec2=operation/gis_maps/render_view&id='.$map['id_tgis_map'].'">' . print_image ("images/eye.png", true).'</a>',
			print_radio_button_extended('default_map', $map['id_tgis_map'], '', $checked, false, "setDefault(" . $map['id_tgis_map'] . ");", '', true),
			'<a href="index.php?sec=godgismaps&amp;sec2=godmode/gis_maps/index&amp;id_map='.$map['id_tgis_map'].'&amp;action=delete_map">' . print_image ("images/cross.png", true).'</a>'); 
	}
}

print_table($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
echo '<form action="index.php?sec=godgismaps&amp;sec2=godmode/gis_maps/configure_gis_map" method="post">';
print_input_hidden ('action','new_map');
print_submit_button (__('Create'), '', false, 'class="sub next"');
echo '</form>';
echo '</div>';
?>
<script type="text/javascript">
function setDefault(id_tgis_map) {
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
</script>