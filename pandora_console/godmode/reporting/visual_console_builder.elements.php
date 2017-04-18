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

// Login check
global $config;

check_login ();

// Visual console required
if (empty($visualConsole)) {
	db_pandora_audit("ACL Violation",
		"Trying to access report builder");
	require ("general/noaccess.php");
	exit;
}

// ACL for the existing visual console
// if (!isset($vconsole_read))
// 	$vconsole_read = check_acl ($config['id_user'], $visualConsole['id_group'], "VR");
if (!isset($vconsole_write))
	$vconsole_write = check_acl($config['id_user'],
		$visualConsole['id_group'], "VW");
if (!isset($vconsole_manage))
	$vconsole_manage = check_acl($config['id_user'],
		$visualConsole['id_group'], "VM");

if (!$vconsole_write && !$vconsole_manage) {
	db_pandora_audit("ACL Violation",
		"Trying to access report builder");
	require ("general/noaccess.php");
	exit;
}

require_once ($config['homedir'].'/include/functions_visual_map.php');
require_once ($config['homedir'].'/include/functions_agents.php');
enterprise_include_once('include/functions_visual_map.php');
enterprise_include_once('meta/include/functions_agents_meta.php');
enterprise_include_once('meta/include/functions_users_meta.php');

//Arrays for select box.
$backgrounds_list = list_files(
	$config['homedir'] . '/images/console/background/', "jpg", 1, 0);
$backgrounds_list = array_merge($backgrounds_list,
	list_files($config['homedir'] . '/images/console/background/', "png", 1, 0));

$images_list = array ();
$all_images = list_files ($config['homedir'] . '/images/console/icons/',
	"png", 1, 0);
foreach ($all_images as $image_file) {
	if (strpos ($image_file, "_bad"))
		continue;
	if (strpos ($image_file, "_ok"))
		continue;
	if (strpos ($image_file, "_warning"))
		continue;
	$image_file = substr ($image_file, 0, strlen ($image_file) - 4);
	$images_list[$image_file] = $image_file;
}

$table = new stdClass();
$table->width = '100%';
$table->class = 'databox data';

$table->head = array();
$table->head['icon'] = '';
$table->head[0] = __('Label') . '<br>' . __('Agent') . ' / ' . __('Group');
$table->head[1] = __('Image') . '<br>' . __('Module') . ' / ' .  __('Custom graph');
$table->head[2] = __('Width x Height<br>Max value');
$table->head[3] = __('Position') . '<br>' . __('Period');
$table->head[4] = __('Parent') . '<br>' . __('Map linked');
$table->head[5] = "";
$table->head[5] .= '&nbsp;&nbsp;&nbsp;' . html_print_checkbox('head_multiple_delete',
	'', false, true, false, 'toggle_checkbox_multiple_delete();');
$table->head[5] .= '&nbsp;&nbsp;&nbsp;<span title="' . __('Action') . '">' .
	__('A.') . '</span>';

$table->size = array();
$table->size['icon'] = '1%';
$table->size[0] = '25%';
$table->align = array();

if (!defined('METACONSOLE')) {
	$table->headstyle[0] = "text-align:left;";
	$table->headstyle[1] = "text-align:left";
	$table->headstyle[2] = "text-align:left";
	$table->headstyle[3] = "text-align:left";
	$table->headstyle[4] = "text-align:left";
	$table->headstyle[5] = "text-align:left";
	$table->align[0] = "left";
	$table->align[1] = "left";
	$table->align[2] = "left";
	$table->align[3] = "left";
	$table->align[4] = "left";
	$table->align[5] = "left";
}
$table->data = array();

//Background
$table->data[0]['icon'] = '';
$table->data[0][0] = __('Background');
$table->data[0][1] = html_print_select($backgrounds_list, 'background', $visualConsole['background'], '', 'None', '', true, false, true, '', false, 'width: 120px;');
$table->data[0][2] = html_print_input_text('width', $visualConsole['width'], '', 3, 5, true) .
	' x ' .
	html_print_input_text('height', $visualConsole['height'], '', 3, 5, true);
$table->data[0][3] = $table->data[0][4] = $table->data[0][5] = '';

$i = 1;
$layoutDatas = db_get_all_rows_field_filter ('tlayout_data',
	'id_layout', $idVisualConsole);
if ($layoutDatas === false)
	$layoutDatas = array();

$alternativeStyle = true;


foreach ($layoutDatas as $layoutData) {
	$idLayoutData = $layoutData['id'];
	
	//line between rows
	$table->data[$i][0] = '<hr>';
	$table->colspan[$i][0] = '8';
	
	switch ($layoutData['type']) {
		case STATIC_GRAPH:
			$table->data[$i + 1]['icon'] =
				html_print_image('images/camera.png', true,
					array('title' => __('Static Graph')));
			break;
		case PERCENTILE_BAR:
			$table->data[$i + 1]['icon'] =
				html_print_image('images/chart_bar.png', true,
					array('title' => __('Percentile Bar')));
			break;
		case PERCENTILE_BUBBLE:
			$table->data[$i + 1]['icon'] =
				html_print_image('images/dot_red.png', true,
					array('title' => __('Percentile Bubble')));
			break;
		case MODULE_GRAPH:
			$table->data[$i + 1]['icon'] =
				html_print_image('images/chart_curve.png', true,
					array('title' => __('Module Graph')));
			break;
		case SIMPLE_VALUE:
			$table->data[$i + 1]['icon'] =
				html_print_image('images/binary.png', true,
					array('title' => __('Simple Value')));
			break;
		case SIMPLE_VALUE_MAX:
			$table->data[$i + 1]['icon'] =
				html_print_image('images/binary.png', true,
					array('title' => __('Simple Value (Process Max)')));
			break;
		case SIMPLE_VALUE_MIN:
			$table->data[$i + 1]['icon'] =
				html_print_image('images/binary.png', true,
					array('title' => __('Simple Value (Process Min)')));
			break;
		case SIMPLE_VALUE_AVG:
			$table->data[$i + 1]['icon'] =
				html_print_image('images/binary.png', true,
					array('title' => __('Simple Value (Process Avg)')));
			break;
		case LABEL:
			$table->data[$i + 1]['icon'] =
				html_print_image('images/tag_red.png', true,
					array('title' => __('Label')));
			break;
		case ICON:
			$table->data[$i + 1]['icon'] =
				html_print_image('images/photo.png', true,
					array('title' => __('Icon')));
			break;
		case BOX_ITEM:
			$table->data[$i + 1]['icon'] =
				html_print_image('images/box_item.png', true,
					array('title' => __('Box')));
			break;
		case GROUP_ITEM:
			$table->data[$i + 1]['icon'] =
				html_print_image('images/group_green.png', true,
					array('title' => __('Group')));
			break;
		case LINE_ITEM:
			$table->data[$i + 1]['icon'] =
				html_print_image('images/line_item.png', true,
					array('title' => __('Line')));
			break;
		default:
			if (enterprise_installed()) {
				$table->data[$i + 1]['icon'] =
					enterprise_visual_map_print_list_element('icon', $layoutData);
			}
			else {
				$table->data[$i + 1]['icon'] = '';
			}
			break;
	}
	
	
	
	//First row
	
	//Label
	switch ($layoutData['type']) {
		case ICON:
		case BOX_ITEM:
		case LINE_ITEM:
			// hasn't the label.
			$table->data[$i + 1][0] = '';
			break;
		default:
			$table->data[$i + 1][0] = '<span style="width: 150px; display: block;">' .
				html_print_input_hidden('label_' . $idLayoutData, $layoutData['label'], true) .
				'<a href="javascript: show_dialog_label_editor(' . $idLayoutData . ');">' . __('Edit label') .'</a>' .                      
				'</span>';
			break;
	}
	
	
	//Image
	switch ($layoutData['type']) {
		case STATIC_GRAPH:
		case ICON:
		case GROUP_ITEM:
		case SERVICE:
			$table->data[$i + 1][1] =
				html_print_select ($images_list,
					'image_' . $idLayoutData, $layoutData['image'], '',
					'None', '', true,  false, true, '', false,
					"width: 120px");
			break;
		default:
			$table->data[$i + 1][1] = '';
			break;
	}
	
	
	
	//Width and height
	switch ($layoutData['type']) {
		case LINE_ITEM:
			// hasn't the width and height.
			$table->data[$i + 1][2] = '';
			break;
		default:
			$table->data[$i + 1][2] = html_print_input_text('width_' . $idLayoutData, $layoutData['width'], '', 2, 5, true) .
				' x ' .
				html_print_input_text('height_' . $idLayoutData, $layoutData['height'], '', 2, 5, true);
			break;
	}
	
	//Position
	switch ($layoutData['type']) {
		case LINE_ITEM:
			// hasn't the width and height.
			$table->data[$i + 1][3] = '';
			break;
		default:
			$table->data[$i + 1][3] = '( ' . html_print_input_text('left_' . $idLayoutData, $layoutData['pos_x'], '', 2, 5, true) .
				' , ' . html_print_input_text('top_' . $idLayoutData, $layoutData['pos_y'], '', 2, 5, true) .
				' )';
			break;
	}
	
	
	//Parent
	switch ($layoutData['type']) {
		case BOX_ITEM:
		case LINE_ITEM:
			$table->data[$i + 1][4] = "";
			break;
		default:
			$parents = visual_map_get_items_parents($idVisualConsole);
			$table->data[$i + 1][4] = html_print_select($parents,
				'parent_' . $idLayoutData, $layoutData['parent_item'],
				'', __('None'), 0, true);
	}
	
	//Delete row button
	if (!defined('METACONSOLE')) {
		$url_delete = "index.php?" .
			"sec=network&" .
			"sec2=godmode/reporting/visual_console_builder&" .
			"tab=" . $activeTab  . "&" .
			"action=delete&" .
			"id_visual_console=" . $visualConsole["id"] . "&" .
			"id_element=" . $idLayoutData;
	}
	else {
		$url_delete = "index.php?" .
			"operation=edit_visualmap&" .
			"sec=screen&" .
			"sec2=screens/screens&" .
			"action=visualmap&" .
			"pure=" . (int)get_parameter('pure', 0) . "&" .
			"tab=list_elements&" .
			"action2=delete&" .
			"id_visual_console=" . $visualConsole["id"] . "&" .
			"id_element=" . $idLayoutData;
		
	}
	
	$table->data[$i + 1][5] = "";
	$table->data[$i + 1][5] .= html_print_checkbox('multiple_delete_items', $idLayoutData, false, true);
	$table->data[$i + 1][5] .= '<a href="' . $url_delete . '" ' . 
		'onclick="javascript: if (!confirm(\'' . __('Are you sure?') . '\')) return false;">' .
			html_print_image('images/cross.png', true) . '</a>';
	
	//Second row
	$table->data[$i + 2]['icon'] = '';
	
	
	//Agent
	switch ($layoutData['type']) {
		case GROUP_ITEM:
			$own_info = get_user_info($config['id_user']);
			if (!$own_info['is_admin'] && !check_acl($config['id_user'], 0, "PM"))
				$return_all_group = false;
			else
				$return_all_group = true;
			$table->data[$i + 2][0] = html_print_select_groups(false, "AR",
				$return_all_group, 'group_' . $idLayoutData,
				$layoutData['id_group'], '', '', 0, true);
			break;
		case BOX_ITEM:
		case ICON:
		case LABEL:
		case LINE_ITEM:
			$table->data[$i + 2][0] = '';
			break;
		default:
			$cell_content_enterprise = false;
			if (enterprise_installed()) {
				$cell_content_enterprise =
					enterprise_visual_map_print_list_element('agent', $layoutData);
			}
			if ($cell_content_enterprise === false) {
				$params = array();
				$params['return'] = true;
				$params['show_helptip'] = true;
				$params['size'] = 20;
				$params['input_name'] = 'agent_' . $idLayoutData;
				$params['javascript_is_function_select'] = true;
				$params['selectbox_id'] = 'module_' . $idLayoutData;
				if (is_metaconsole()) {
					$params['javascript_ajax_page'] = '../../ajax.php';
					$params['disabled_javascript_on_blur_function'] = true;
					
					$params['print_input_id_server'] = true;
					$params['input_id_server_id'] = $params['input_id_server_name'] = 'id_server_id_' . $idLayoutData;
					$params['input_id_server_value'] = $layoutData['id_metaconsole'];
					$params['metaconsole_enabled'] = true;
					$params['print_hidden_input_idagent'] = true;
					$params['hidden_input_idagent_name'] = 'id_agent_' . $idLayoutData;
					$params['hidden_input_idagent_value'] = $layoutData['id_agent'];
					
					$params['value'] = agents_meta_get_name($layoutData['id_agent'],
						"none", $layoutData['id_metaconsole'], true);
				}
				else {
					$params['print_hidden_input_idagent'] = true;
					$params['hidden_input_idagent_name'] = 'id_agent_' . $idLayoutData;
					$params['hidden_input_idagent_value'] = $layoutData['id_agent'];
					$params['value'] = agents_get_name($layoutData['id_agent']);
				}
				
				if ($layoutData['id_agent'] == 0 and $layoutData['id_custom_graph'] != 0) {
					$table->data[$i + 2][0] = __("Custom graph");
				} else {
					$table->data[$i + 2][0] = ui_print_agent_autocomplete_input($params);
				}
			}
			else {
				$table->data[$i + 2][0] = $cell_content_enterprise;
			}
			break;
	}
	
	
	//Module
	switch ($layoutData['type']) {
		case ICON:
		case LABEL:
		case BOX_ITEM:
		case LINE_ITEM:
		case GROUP_ITEM:
			$table->data[$i + 2][1] = '';
			break;
		default:
			if ($layoutData['id_layout_linked'] != 0) {
				//It is a item that links with other visualmap
				break;
			}
			
			$cell_content_enterprise = false;
			if (enterprise_installed()) {
				$cell_content_enterprise =
					enterprise_visual_map_print_list_element('module', $layoutData);
			}
			if ($cell_content_enterprise === false) {
				if (!defined('METACONSOLE')) {
					$modules = agents_get_modules($layoutData['id_agent']);
				}
				else {
					if ($layoutData['id_agent'] != 0) {
						$modules = agents_meta_get_modules(
							$layoutData['id_metaconsole'],
							$layoutData['id_agent']);
					}
				}
				
				$modules = io_safe_output($modules);
				
				if ($layoutData['id_agent'] == 0 and $layoutData['id_custom_graph'] != 0) {
					$table->data[$i + 2][1] = html_print_select_from_sql(
							"SELECT id_graph, name FROM tgraph", 'custom_graph_' . $idLayoutData,
							$layoutData['id_custom_graph'], '', __('None'), 0, true);
				} else {
					$table->data[$i + 2][1] = html_print_select($modules,
						'module_' . $idLayoutData,
						$layoutData['id_agente_modulo'], '', '---', 0, true,
						false, true, '', false, "width: 120px");
				}
			}
			else {
				$table->data[$i + 2][1] = $cell_content_enterprise;
			}
			break;
	}
	
	
	
	//Empty
	$table->data[$i + 2][2] = '';
	
	//Period
	switch ($layoutData['type']) {
		case MODULE_GRAPH:
		case SIMPLE_VALUE_MAX:
		case SIMPLE_VALUE_MIN:
		case SIMPLE_VALUE_AVG:
			$table->data[$i + 2][3] =
				html_print_extended_select_for_time(
					'period_' . $idLayoutData,
					$layoutData['period'], '', '--', '0', 10, true);
			break;
		default:
			$table->data[$i + 2][3] = '';
			break;
	}
	
	//Map linked
	switch ($layoutData['type']) {
		case LINE_ITEM:
		case BOX_ITEM:
			$table->data[$i + 2][4] = "";
			break;
		default:
			$table->data[$i + 2][4] = html_print_select_from_sql(
					'SELECT id, name
					FROM tlayout
					WHERE id != ' . $idVisualConsole,
				'map_linked_' . $idLayoutData,
				$layoutData['id_layout_linked'], '', 'None', '', true,
				false, true, '', false, "width: 120px");
			break;
	}
	
	$table->data[$i + 2][5] = '';
	
	if ($alternativeStyle) {
		$table->rowclass[$i + 1] = 'rowOdd';
		$table->rowclass[$i + 2] = 'rowOdd';
	}
	else {
		$table->rowclass[$i + 1] = 'rowPair';
		$table->rowclass[$i + 2] = 'rowPair';
	}
	$alternativeStyle = !$alternativeStyle;
	
	$i = $i + 3;
}

$pure = get_parameter('pure', 0);

if (!defined('METACONSOLE')) {
	echo '<form method="post" action="index.php?sec=network&sec2=godmode/reporting/visual_console_builder&tab=' . $activeTab  . '&id_visual_console=' . $visualConsole["id"] . '">';
}
else {
	echo "<form method='post' action='index.php?operation=edit_visualmap&sec=screen&sec2=screens/screens&action=visualmap&pure=0&tab=list_elements&id_visual_console=" . $idVisualConsole . "'>";
}
if (!defined('METACONSOLE')) 
	echo '<div class="action-buttons" style="width: ' . $table->width . '; margin-bottom:15px;">';
if (!defined('METACONSOLE')) {
	html_print_input_hidden ('action', 'update');
}
else {
	html_print_input_hidden ('action2', 'update');
}

html_print_table($table);

echo '<div class="action-buttons" style="width: ' . $table->width . '">';
html_print_submit_button (__('Update'), 'go', false, 'class="sub next"');
echo "&nbsp;";
html_print_button(__('Delete'), 'delete', false, 'submit_delete_multiple_items();', 'class="sub delete"');
echo '</div>';
echo '</form>';

// Form for multiple delete
if (!defined('METACONSOLE')) {
	$url_multiple_delete = "index.php?" .
		"sec=network&" .
		"sec2=godmode/reporting/visual_console_builder&" .
		"tab=" . $activeTab  . "&" .
		"id_visual_console=" . $visualConsole["id"];
	
	echo '<form id="form_multiple_delete" method="post" action="' .
		$url_multiple_delete . '">';
}
else {
	$url_multiple_delete = "index.php?" .
		"operation=edit_visualmap&" .
		"sec=screen&" .
		"sec2=screens/screens&" .
		"action=visualmap&" .
		"pure=0&" .
		"tab=list_elements&" .
		"id_visual_console=" . $idVisualConsole;
	
	echo "<form id='form_multiple_delete' method='post' action=" .
		$url_multiple_delete . ">";
}
if (!defined('METACONSOLE')) {
	html_print_input_hidden ('action', 'multiple_delete');
}
else {
	html_print_input_hidden ('action2', 'multiple_delete');
}
html_print_input_hidden ('id_visual_console', $visualConsole["id"]);
html_print_input_hidden('id_item_json', '');
echo '</form>';


//Trick for it have a traduct text for javascript.
echo '<span id="ip_text" style="display: none;">' . __('IP') . '</span>';
?>
<div id="dialog_label_editor">
	<input id="active_id_layout_data" type="hidden" />
	<textarea id="tinyMCE_editor" name="tinyMCE_editor"></textarea>
</div>
<?php
ui_require_css_file ('color-picker');

ui_require_jquery_file ('colorpicker');
ui_require_jquery_file ('pandora.controls');
ui_require_javascript_file ('wz_jsgraphics');
ui_require_javascript_file ('pandora_visual_console');
ui_require_jquery_file('ajaxqueue');
ui_require_jquery_file('bgiframe');
ui_require_javascript_file('tiny_mce', 'include/javascript/tiny_mce/');
?>

<script type="text/javascript">
	$(document).ready (function () {
		
		tinymce.init({
			selector: "#tinyMCE_editor",
			theme : "advanced",
			<?php
			if ($config['style'] == 'pandora_legacy') {
				echo 'content_css: "' .
					ui_get_full_url('include/styles/pandora_legacy.css', false, false, false) . '",' . "\n";
			}
			else {
				echo 'content_css: "' .
					ui_get_full_url('include/styles/pandora.css', false, false, false) . '",' . "\n";
			}
			?>
			theme_advanced_font_sizes :
				"4pt=.visual_font_size_4pt, " +
				"6pt=.visual_font_size_6pt, " +
				"8pt=.visual_font_size_8pt, " +
				"14pt=.visual_font_size_14pt, " +
				"24pt=.visual_font_size_24pt, " +
				"36pt=.visual_font_size_36pt, " +
				"72pt=.visual_font_size_72pt",
			theme_advanced_toolbar_location : "top",
			theme_advanced_toolbar_align : "left",
			theme_advanced_buttons1 : "bold,italic, |, image, link, |, forecolor, fontsizeselect",
			theme_advanced_buttons2 : "",
			theme_advanced_buttons3 : "",
			theme_advanced_statusbar_location : "none",
			width: "400",
			height: "200",
			nowrap: true
		});
		
		$("#dialog_label_editor").hide ()
			.dialog ({
				title: "<?php echo __("Edit label");?>",
				resizable: false,
				draggable: true,
				modal: true,
				overlay: {
					opacity: 0.5,
					background: "black"
				},
				width: 450,
				height: 300,
				autoOpen: false,
				beforeClose: function() {
					var id_layout_data = $("#active_id_layout_data").val();
					var label = tinyMCE.activeEditor.getContent();
					
					$("#hidden-label_" + id_layout_data).val(label);
				}
		});
	});
	
	var idText = $("#ip_text").html();
	
	function show_dialog_label_editor(id_layout_data) {
		var label = $("#hidden-label_" + id_layout_data).val();
		
		$("#active_id_layout_data").val(id_layout_data);
		
		$("#tinyMCE_editor").val(label);
		tinyMCE.activeEditor.setContent(label);
		$("#dialog_label_editor").dialog("open");
	}
	
	function toggle_checkbox_multiple_delete() {
		checked_head_multiple = $("input[name='head_multiple_delete']")
			.is(":checked");
		
		$("input[name='multiple_delete_items']")
			.prop("checked", checked_head_multiple);
	}
	
	function submit_delete_multiple_items() {
		delete_items = [];
		jQuery.each($("input[name='multiple_delete_items']:checked"),
			function(i, item) {
				delete_items.push($(item).val());
			}
		);
		
		
		$("input[name='id_item_json']").val(JSON.stringify(delete_items));
		$("#form_multiple_delete").submit();
	}
</script>
