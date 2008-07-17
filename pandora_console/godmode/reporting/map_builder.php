<?php

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development and project architecture and management
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas, info@artica.es
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

if (comprueba_login () != 0) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to access map builder");
	include ("general/noaccess.php");
	exit;
}

if (! give_acl ($config['id_user'], 0, "AW")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to access map builder");
	include ("general/noaccess.php");
	exit;
}

require ('include/functions_visual_map.php');

$id_layout = (int) get_parameter ('id_layout');
$edit_layout = (bool) get_parameter ('edit_layout');
$create_layout = (bool) get_parameter ('create_layout');
$update_layout = (bool) get_parameter ('update_layout');
$delete_layout = (bool) get_parameter ('delete_layout');
$create_layout_data = (bool) get_parameter ('create_layout_data');
$update_layout_data = (bool) get_parameter ('update_layout_data');
$delete_layout_data = (bool) get_parameter ('delete_layout_data');
$update_layout_data_coords = (bool) get_parameter ('update_layout_data_coords');
$get_layout_data = (bool) get_parameter ('get_layout_data');
$get_background_info = (bool) get_parameter ('get_background_info');

$name = '';
$id_group = 0;
$width = 0;
$height = 0;
$background = '';

if ($create_layout) {
	$name = (string) get_parameter ('name');
	$id_group = (int) get_parameter ('id_group');
	$width = (int) get_parameter ('width');
	$height = (int) get_parameter ('height');
	$background = (string) get_parameter ('background');
	if ($background != '') {
		$bg_info = getimagesize ('images/console/background/'.$background);
		$width = $bg_info[0];
		$height = $bg_info[1];
	}
	$sql = sprintf ('INSERT INTO tlayout (name, id_group, background, height, width)
			VALUES ("%s", %d, "%s", %d, %d)',
			$name, $id_group, $background, $height, $width);
	$result = mysql_query ($sql);
	if ($result) {
		echo '<h3 class="suc">'.lang_string ("create_ok").'</h3>';
		$id_layout = mysql_insert_id ();
	} else {
		echo '<h3 class="err">'.lang_string ("create_no").'</h3>';
	}
	if (defined ('AJAX')) {
		exit;
	}
}

if ($delete_layout) {
	$sql = sprintf ('DELETE FROM tlayout_data WHERE id_layout = %d', $id_layout);
	mysql_query ($sql);
	$sql = sprintf ('DELETE FROM tlayout WHERE id = %d', $id_layout);
	$result = mysql_query ($sql);
	if ($result) {
		echo '<h3 class="suc">'.lang_string ("delete_ok").'</h3>';
	} else {
		echo '<h3 class="err">'.lang_string ("delete_no").'</h3>';
	}
	$id_layout = 0;
}

if ($update_layout) {
	$name = (string) get_parameter ('name');
	$id_group = (int) get_parameter ('id_group');
	$width = (int) get_parameter ('width');
	$height = (int) get_parameter ('height');
	$background = (string) get_parameter ('background');
	$bg_info = getimagesize ('images/console/background/'.$background);
	if (! $width)
		$width = $bg_info[0];
	if (! $height)
		$height = $bg_info[1];
	$sql = sprintf ('UPDATE tlayout SET name = "%s", background = "%s", 
			height = %d, width = %d
			WHERE id = %d',
			$name, $background, $height, $width, $id_layout);
	$result = mysql_query ($sql);
	if ($result) {
		echo '<h3 class="suc">'.lang_string ("update_ok").'</h3>';
	} else {
		echo '<h3 class="err">'.lang_string ("update_no").'</h3>';
	}
	if (defined ('AJAX')) {
		exit;
	}
}

if ($get_background_info) {
	$file = (string) get_parameter ('background');
	
	$info = getimagesize ('images/console/background/'.$file);
	$info['width'] = $info[0];
	$info['height'] = $info[1];
	if (defined ('AJAX')) {
		echo json_encode ($info);
		exit;
	}
}

if ($get_layout_data) {
	$id_layout_data = (int) get_parameter ('id_layout_data');
	$layout_data = get_db_row ('tlayout_data', 'id', $id_layout_data);
	$layout_data['id_agent'] = give_agent_id_from_module_id ($layout_data['id_agente_modulo']);
	if (defined ('AJAX')) {
		echo json_encode ($layout_data);
		exit;
	}
}

if ($create_layout_data) {
	$layout_data_type = (string) get_parameter ("type");
	$layout_data_label = (string) get_parameter ("label");
	$layout_data_image = (string) get_parameter ("image");
	$layout_data_id_agent_module = (int) get_parameter ("module");
	$layout_data_label_color = (string) get_parameter ("label_color");
	$layout_data_parent_item = (int) get_parameter ("parent_item");
	$layout_data_period = (int) get_parameter ("period");
	$layout_data_map_linked = (int) get_parameter ("map_linked");
	$layout_data_width = (int) get_parameter ("width");
	$layout_data_height = (int) get_parameter ("height");
	
	$sql = sprintf ('INSERT INTO tlayout_data (id_layout, label, id_layout_linked,
			label_color, image, type, id_agente_modulo, parent_item, period, no_link_color,
			width, height) 
			VALUES (%d, "%s", %d, "%s", "%s", %d, %d, %d, %d, 1, %d, %d)',
			$id_layout, $layout_data_label,
			$layout_data_map_linked,
			$layout_data_label_color,
			$layout_data_image, $layout_data_type,
			$layout_data_id_agent_module,
			$layout_data_parent_item, $layout_data_period * 3600,
			$layout_data_width, $layout_data_height);
	$result = mysql_query ($sql);
	
	if ($result) {
		echo '<h3 class="suc">'.lang_string ("create_ok").'</h3>';
	} else {
		echo '<h3 class="error">'.lang_string ("create_no").'</h3>';
	}
	if (defined ('AJAX')) {
		exit;
	}
}

if ($update_layout_data_coords) {
	$id_layout_data = (int) get_parameter ('id_layout_data');
	$layout_data_x = (int) get_parameter ("coord_x");
	$layout_data_y = (int) get_parameter ("coord_y");
	
	$sql = sprintf ('UPDATE tlayout_data SET
			pos_x = %d, pos_y = %d
			WHERE id = %d',
			$layout_data_x, $layout_data_y, $id_layout_data);
	$result = mysql_query ($sql);
	
	if (defined ('AJAX')) {
		exit;
	}
}

if ($delete_layout_data) {
	$ids_layout_data = (array) get_parameter ('ids_layout_data');
	
	foreach ($ids_layout_data as $id_layout_data) {
		$sql = sprintf ('UPDATE tlayout_data SET parent_item = 0 WHERE parent_item = %d',
				$id_layout_data);
		$result = mysql_query ($sql);
		$sql = sprintf ('DELETE FROM tlayout_data WHERE id = %d',
				$id_layout_data);
		$result = mysql_query ($sql);
	}
	
	if (defined ('AJAX')) {
		exit;
	}
}

if ($update_layout_data) {
	$id_layout_data = (int) get_parameter ('id_layout_data');
	$layout_data_type = (int) get_parameter ("type");
	$layout_data_label = (string) get_parameter ("label");
	$layout_data_image = (string) get_parameter ("image");
	$layout_data_id_agent_module = (int) get_parameter ("module");
	$layout_data_label_color = (string) get_parameter ("label_color");
	$layout_data_parent_item = (int) get_parameter ("parent_item");
	$layout_data_period = (int) get_parameter ("period");
	$layout_data_map_linked = (int) get_parameter ("map_linked");
	$layout_data_width = (int) get_parameter ("width");
	$layout_data_height = (int) get_parameter ("height");
	
	$sql = sprintf ('UPDATE tlayout_data SET
			image = "%s", label = "%s",
			label_color = "%s",
			id_agente_modulo = %d,
			type = %d, parent_item = %d,
			period = %d, id_layout_linked = %d,
			width = %d, height = %d
			WHERE id = %d',
			$layout_data_image, $layout_data_label,
			$layout_data_label_color,
			$layout_data_id_agent_module,
			$layout_data_type, $layout_data_parent_item,
			$layout_data_period * 3600,
			$layout_data_map_linked,
			$layout_data_width, $layout_data_height,
			$id_layout_data);
	$result = mysql_query ($sql);
	
	if ($result) {
		echo '<h3 class="suc">'.lang_string ("modify_ok").'</h3>';
	} else {
		echo '<h3 class="error">'.lang_string ("modify_no").'</h3>';
	}
}

if ($id_layout) {
	$layout = get_db_row ('tlayout', 'id', $id_layout);
	$name = $layout['name'];
	$background = $layout['background'];
	$id_group = $layout['id_group'];
	$width = $layout['width'];
	$height = $layout['height'];
}

if (! $edit_layout && ! $id_layout) {
	echo "<h2>".lang_string ("reporting")." &gt; ".lang_string ("map_builder")."</h2>";
	
	$table->width = '500px';
	$table->data = array ();
	$table->head = array ();
	$table->head[0] = lang_string ('map_name');
	$table->head[1] = lang_string ('group');
	$table->head[2] = lang_string ('delete');
	$table->align = array ();
	$table->align[2] = 'center';
	
	$maps = get_db_all_rows_in_table ('tlayout','name');
	foreach ($maps as $map) {
		$data = array ();
		
		$data[0] = '<a href="index.php?sec=greporting&sec2=godmode/reporting/map_builder&id_layout='.$map['id'].'">'.$map['name'].'</a>';
		$data[1] = '<img src="images/'.dame_grupo_icono ($map['id_group']).'.png" /> ';
		$data[1] .= dame_nombre_grupo ($map['id_group']);
		$data[2] = '<a href="index.php?sec=greporting&sec2=godmode/reporting/map_builder&id_layout='.$map['id'].'&delete_layout=1">
			<img src="images/cross.png"></a>';
		array_push ($table->data, $data);
	}
	print_table ($table);
	
	echo '<div class="action-buttons" style="width: '.$table->width.'">';
	echo '<form action="index.php?sec=greporting&sec2=godmode/reporting/map_builder" method="post">';
	print_input_hidden ('edit_layout', 1);
	print_submit_button (lang_string ('create'), '', false, 'class="sub wand"');
	echo '</form>';
	echo '</div>';
} else {
	echo "<h2>".lang_string ("reporting")." &gt; ";
	echo lang_string ("map_builder");
	pandora_help ("map_builder");
	echo "</h2>";
	
	$backgrounds_list = list_files ('images/console/background/', "jpg", 1, 0);
	$backgrounds_list = array_merge ($backgrounds_list, list_files ('images/console/background/', "png", 1, 0));
	$groups = get_user_groups ($config['id_user']);
	
	$table->width = '300px';
	$table->data = array ();
	$table->data[0][0] = lang_string ('name');
	$table->data[0][1] = print_input_text ('name', $name, '', 15, 50, true);
	$table->data[1][0] = lang_string ('group');
	$table->data[1][1] = print_select ($groups, 'id_group', $id_group, '', '', '', true);
	$table->data[2][0] = lang_string ('background');
	$table->data[2][1] = print_select ($backgrounds_list, 'background', $background, '', 'None', '', true);
	
	if ($id_layout) {
		$table->data[3][0] = lang_string ('width');
		$table->data[3][1] = print_input_text ('width', $width, '', 3, 5, true);
		$table->data[4][0] = lang_string ('height');
		$table->data[4][1] = print_input_text ('height', $height, '', 3, 5, true);
	}
	echo '<form action="index.php?sec=greporting&sec2=godmode/reporting/map_builder" method="post">';
	print_table ($table);
	
	echo '<div style="width: '.$table->width.'" class="action-buttons">';
	if ($id_layout) {
		print_submit_button (lang_string ('update'), 'update_layout', false, 'class="sub upd"');
		print_input_hidden ('update_layout', 1);
		print_input_hidden ('id_layout', $id_layout);
	} else {
		print_submit_button (lang_string ('create'), 'create_layout', false, 'class="sub wand"');
		print_input_hidden ('create_layout', 1);
	}
	echo '</div>';
	echo '</form>';
	
	if ($id_layout) {
		/* Show visual map preview */
		echo '<h1>'.lang_string ('preview').'</h1>';
		print_pandora_visual_map ($id_layout, false, true);
		
		$images_list = array ();
		$all_images = list_files ('images/console/icons/', "png", 1, 0);
		foreach ($all_images as $image_file) {
			if (strpos ($image_file, "_bad"))
				continue;
			if (strpos ($image_file, "_ok"))
				continue;
			$image_file = substr ($image_file, 0, strlen ($image_file) - 4);
			$images_list[$image_file] = $image_file;
		}
		
		echo '<div style="width: 770px">';
		/* Layout data trash */
		echo '<form id="form_layout_data_trash" action="" method="post">';
		echo '<div id="layout_trash_drop">';
		echo '<h1>'.lang_string ('Map element trash').'</h1>';	
		echo lang_string ('Drag an element here to delete from the map');
		echo '<span id="elements"> </span>';
		print_input_hidden ('delete_layout_data', 1);
		print_input_hidden ('id_layout', $id_layout);
		
		echo '<div class="action-buttons" style="margin-top: 180px">';
		print_submit_button (lang_string ('delete'), 'delete_buttons', true, 'class="sub delete"');
		echo '</div>';
		echo '</div>';
		echo '</form>';
		
		/* Layout_data editor form */
		$intervals = array ();
		$intervals[1] = lang_string ('Hour');
		$intervals[2] = "2 ".lang_string ('Hours');
		$intervals[3] = "3 ".lang_string ('Hours');
		$intervals[6] = "6 ".lang_string ('Hours');
		$intervals[12] = "12 ".lang_string ('Hours');
		$intervals[24] = lang_string ('Last day');
		$intervals[48] = "2 ". lang_string ('days');
		$intervals[168] = lang_string ('Last week');
		$intervals[360] = lang_string ('15 days');
		$intervals[720] = lang_string ('Last Month');
		$intervals[1440] = lang_string ('Two Months');
		$intervals[4320] = lang_string ('Six Months');
		
		$all_agents = get_agents_in_group ($id_group);
		$agents = array ();
		if ($all_agents !== false) {
			foreach ($all_agents as $agent) {
				$agents[$agent['id_agente']] = strtolower($agent['nombre']);
			}
			asort($agents);
		}

		echo '<div id="layout_editor_drop">';
		echo '<h1>'.lang_string ('Map element editor').'</h1>';
		echo lang_string ('Drag an element here to edit the properties');
		
		$table->data = array ();
		$table->id = 'table_layout_data';
		$table->rowstyle = array ();
		$table->rowstyle[3] = 'display: none';
		$table->rowstyle[4] = 'display: none';
		
		$table->data[0][0] = lang_string ('label');
		$table->data[0][1] = print_input_text ('label', '', '', 20, 200, true);
		$table->data[1][0] = lang_string ('label_color');
		$table->data[1][1] = print_input_text ('label_color', '#000000', '', 7, 7, true);
		$table->data[2][0] = lang_string ('type');
		$table->data[2][1] = print_select (get_layout_data_types (), 'type', '', '', '', '', true);
		$table->data[3][0] = lang_string ('height');
		$table->data[3][1] = print_input_text ('height', '', '', 5, 5, true);
		$table->data[4][0] = lang_string ('width');
		$table->data[4][1] = print_input_text ('width', '', '', 5, 5, true);
		$table->data[5][0] = lang_string ('agent');
		$table->data[5][1] = print_select ($agents, 'agent', '', '', '--', 0, true);
		$table->data[6][0] = lang_string ('module');
		$table->data[6][1] = print_select (array (), 'module', '', '', '--', 0, true);
		$table->data[7][0] = lang_string ('period');
		$table->data[7][1] = print_select ($intervals, 'period', '', '', '--', 0, true);
		$table->data[8][0] = lang_string ('image');
		$table->data[8][1] = print_select ($images_list, 'image', '', '', 'None', '', true);
		$table->data[8][1] .= '<div id="image_preview"> </div>';
		$table->data[9][0] = lang_string ('parent');
		$table->data[9][1] = print_select_from_sql ('SELECT id, label FROM tlayout_data WHERE id_layout = '.$id_layout,
							'parent_item', '', '', 'None', '', true);
		$table->data[10][0] = lang_string ('map_linked');
		$table->data[10][1] = print_select_from_sql ('SELECT id, name FROM tlayout WHERE id != '.$id_layout,
							'map_linked', '', '', 'None', '', true);
		
		echo '<form id="form_layout_data_editor" method="post" action="index.php?sec=greporting&sec2=godmode/reporting/map_builder">';
		print_table ($table);
		print_input_hidden ('create_layout_data', 1);
		print_input_hidden ('update_layout_data', 0);
		print_input_hidden ('id_layout', $id_layout);
		print_input_hidden ('id_layout_data', 0);
		echo '<div style="width: '.$table->width.'" class="action-buttons">';
		print_submit_button (lang_string ('create'), 'create_layout_data_button', false, 'class="sub wand"');
		echo '</div>';
		echo '</form>';
		echo '</div>';
		echo '</div>';
	}
}
?>

<link rel="stylesheet" href="include/styles/color-picker.css" type="text/css" />
<script type="text/javascript" src="include/javascript/jquery.js"></script>
<script type="text/javascript" src="include/javascript/pandora_visual_console.js"></script>
<script type="text/javascript" src="include/javascript/pandora_visual_console.js"></script>
<script type="text/javascript" src="include/javascript/jquery.ui.core.js"></script>
<script type="text/javascript" src="include/javascript/jquery.ui.draggable.js"></script>
<script type="text/javascript" src="include/javascript/jquery.ui.droppable.js"></script>
<script type="text/javascript" src="include/javascript/jquery.colorpicker.js"></script>

<script language="javascript" type="text/javascript">

function agent_changed (event, id_agent, selected) {
	if (id_agent == undefined)
		id_agent = this.value;
	$('#form_layout_data_editor #module').attr ('disabled', 1);
	$('#form_layout_data_editor #module').empty ();
	$('#form_layout_data_editor #module').append (new Option ("<?=lang_string ('Loading')?>...", 0));
	jQuery.post ('ajax.php', 
		{page: "operation/agentes/ver_agente",
		get_agent_modules_json: 1,
		id_agent: id_agent
		},
		function (data) {
			$('#form_layout_data_editor #module').empty ();
			$('#form_layout_data_editor #module').append (new Option ("--", 0));
			jQuery.each (data, function (i, val) {
				s = html_entity_decode (val['nombre']);
				$('#form_layout_data_editor #module').append (new Option (s, val['id_agente_modulo']));
				$('#form_layout_data_editor #module').fadeIn ('normal');
			});
			if (selected != undefined)
				$('#form_layout_data_editor #module').attr ('value', selected);
			$('#form_layout_data_editor #module').attr ('disabled', 0);
		},
		"json"
	);
}

$(document).ready (function () {
	if (lines)
		draw_lines (lines, 'layout_map');
	$('#background').change (function () {
		background = this.value;
		if (background == '')
			return;
		/* We have to get the info using AJAX because it was not 
		  possible to kwown the image dimensions using javascript 
		  in some cases where the image was not loaded */
		jQuery.post ('ajax.php', 
			{page: "godmode/reporting/map_builder",
			get_background_info: 1,
			background: background
			},
			function (data) {
				$("#layout_map").css ('backgroundImage', 'url(images/console/background/' + background + ')');
				$("#layout_map").css ('width', data['width'] + 'px');
				$("#layout_map").css ('height', data['height'] + 'px');
				$('#text-width').attr ('value', data['width']);
				$('#text-height').attr ('value', data['height']);
			},
			"json"
		);
	});
	$('#text-width').keyup (function () {
		$("#layout_map").css ('width', this.value + 'px');
	});
	$('#text-height').keyup (function () {
		$("#layout_map").css ('height', this.value + 'px');
	});
	$(".layout-data").draggable ({helper: 'clone'});
	$("#layout_map").droppable ({
		accept: ".layout-data",
		drop: function (ev, ui) {
			margin_left = parseInt ($(ui.draggable[0]).css ('margin-left'));
			margin_top = parseInt ($(ui.draggable[0]).css ('margin-top'));
			coord_x = margin_left + ui.position.left;
			coord_y = margin_top + ui.position.top;
			$(ui.draggable[0]).css ('margin-left', coord_x + 'px');
			$(ui.draggable[0]).css ('margin-top', coord_y + 'px');
			id = ui.draggable[0].id.split ("-").pop ();
			jQuery.post ('ajax.php', 
				{page: "godmode/reporting/map_builder",
				update_layout_data_coords: 1,
				id_layout_data: id,
				coord_x: coord_x,
				coord_y: coord_y
				},
				function () {
					refresh_lines (lines, 'layout_map');
				},
				"html"
			);
		}
	});
	$("#layout_editor_drop").droppable ({
		accept: ".layout-data",
		drop: function (ev, ui) {
			id = ui.draggable[0].id.split ("-").pop ();
			jQuery.post ('ajax.php', 
				{page: "godmode/reporting/map_builder",
				get_layout_data: 1,
				id_layout_data: id
				},
				function (data) {
					$("#form_layout_data_editor #text-label").attr ('value', data['label']);
					$("#form_layout_data_editor #type").attr ('value', data['type']);
					$("#form_layout_data_editor #type").change ();
					$("#form_layout_data_editor #image").attr ('value', data['image']);
					$("#form_layout_data_editor #width").attr ('value', data['width']);
					$("#form_layout_data_editor #height").attr ('value', data['height']);
					$("#form_layout_data_editor #image").change ();
					$("#form_layout_data_editor #id_layout_data").attr ('value', data['id']);
					$("#form_layout_data_editor #period").attr ('value', data['period'] / 3600);
					$("#form_layout_data_editor #agent").attr ('value', data['id_agent']);
					$("#form_layout_data_editor #parent_item").attr ('value', data['parent_item']);
					$("#form_layout_data_editor #map_linked").attr ('value', data['id_layout_linked']);
					$("#form_layout_data_editor #hidden-update_layout_data").attr ('value', 1);
					$("#form_layout_data_editor #hidden-create_layout_data").attr ('value', 0);
					$("#form_layout_data_editor #hidden-id_layout_data").attr ('value', id);
					$("#form_layout_data_editor #submit-create_layout_data_button").attr ('value', "<?=lang_string ('update')?>").removeClass ('wand').addClass ('upd');
					$("#form_layout_data_editor #text-label_color").attr ('value', data['label_color']);
					$(".ColorPickerDivSample").css ('background-color', data['label_color']);
					agent_changed (null, data['id_agent'], data['id_agente_modulo']);
				},
				"json"
			);
		}
	});
	$("#layout_trash_drop").droppable ({
		accept: ".layout-data",
		drop: function (ev, ui) {
			image = $('#'+ ui.draggable[0].id + " img").eq (0);
			elements = $("#" + this.id + " img").length;
			id = ui.draggable[0].id.split ("-").pop ();
			$(ui.draggable[0]).clone ().css ('margin-left', 60 * elements).
				css ('margin-top', 0). attr ('id', 'delete-layout-data-' + id).
				appendTo ("#"+this.id + " #elements");
			$(ui.draggable[0]).remove ();
			$('<input type="hidden" name="ids_layout_data[]" />').attr ('value', id).
				appendTo ($("#form_layout_data_trash"));
			$("#form_layout_data_trash #submit-delete_buttons").removeAttr ('disabled');
			setTimeout (function() { refresh_lines (lines, 'layout_map'); }, 1000);
		}
	});
	$("#form_layout_data_editor #image").change (function () {
		$("#image_preview").empty ();
		if (this.value != '') {
			$("#image_preview").append ($('<img />').attr ('src', 'images/console/icons/' + this.value + '.png'));
			$("#image_preview").append ($('<img />').attr ('src', 'images/console/icons/' + this.value + '_ok.png'));
			$("#image_preview").append ($('<img />').attr ('src', 'images/console/icons/' + this.value + '_bad.png'));
		}
	});
	$("#form_layout_data_editor #agent").change (agent_changed);
	$("#form_layout_data_editor #type").change (function () {
		if (this.value == 0) {
			$("#table_layout_data #table_layout_data-3, #table_layout_data #table_layout_data-4").fadeOut ();
			$("#table_layout_data #table_layout_data-8").fadeIn ();
		} else {
			$("#table_layout_data #table_layout_data-3, #table_layout_data #table_layout_data-4").fadeIn ();
			$("#table_layout_data #table_layout_data-8").fadeOut ();
		}
		
	});
	$("#form_layout_data_editor #text-label_color").attachColorPicker();
});
</script>
