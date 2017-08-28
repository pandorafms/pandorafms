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
	$vconsole_write = check_acl ($config['id_user'], $visualConsole['id_group'], "VW");
if (!isset($vconsole_manage))
	$vconsole_manage = check_acl ($config['id_user'], $visualConsole['id_group'], "VM");

if (!$vconsole_write && !$vconsole_manage) {
	db_pandora_audit("ACL Violation",
		"Trying to access report builder");
	require ("general/noaccess.php");
	exit;
}

$metaconsole_hack = '';
if (defined('METACONSOLE')) {
	$metaconsole_hack = '../../';
}


require_once($config['homedir'] . '/include/functions_visual_map.php');
require_once($config['homedir'] . '/include/functions_visual_map_editor.php');
enterprise_include_once('include/functions_visual_map_editor.php');

visual_map_editor_print_toolbox();

$background = $visualConsole['background'];
$widthBackground = $visualConsole['width'];
$heightBackground = $visualConsole['height'];

$layoutDatas = db_get_all_rows_field_filter ('tlayout_data',
	'id_layout', $idVisualConsole);
if ($layoutDatas === false)
	$layoutDatas = array();

//Set the hidden value for the javascript
if (defined('METACONSOLE')) {
	html_print_input_hidden('metaconsole', 1);
}
else {
	html_print_input_hidden('metaconsole', 0);
}
visual_map_editor_print_hack_translate_strings();
visual_map_editor_print_item_palette($visualConsole['id'], $background);

if (!defined('METACONSOLE')) {
	echo '<div id="frame_view" style="width: 100%; height: 500px; overflow: scroll; margin: 0 auto;">';
}
else {
	echo '<div id="frame_view" style="overflow: auto; margin: 0px auto; padding: 5px; ">';
}
echo '<div id="background" class="" style="top:0px;
margin: 0px auto;border: 1px lightgray solid; width: ' . $widthBackground . 'px; height: ' . $heightBackground . 'px;background-color: '.$visualConsole["background_color"].';">';
echo "<div id='background_grid'
	style='position: absolute; display: none; overflow: hidden;
	background: url(" . ui_get_full_url('images/console/background/white_boxed.jpg', false, false, false) . ");
	background-repeat: repeat; width: " . $widthBackground . "px; height: " . $heightBackground . "px;'></div>";


//Print the layout datas from the DB.
foreach ($layoutDatas as $layoutData) {
	$layoutData['status_calculated'] = visual_map_get_status_element($layoutData);
	
	// Pending delete and disable modules must be ignored
	$delete_pending_module = db_get_value ("delete_pending",
		"tagente_modulo", "id_agente_modulo", $layoutData["id_agente_modulo"]);
	$disabled_module = db_get_value ("disabled",
		"tagente_modulo", "id_agente_modulo", $layoutData["id_agente_modulo"]);
	
	if ($delete_pending_module == 1 || $disabled_module == 1)
		continue;
	
	switch ($layoutData['type']) {
		case LINE_ITEM:
			visual_map_print_user_line_handles($layoutData);
			visual_map_print_user_lines($layoutData);
			break;
		default:
			visual_map_print_item("write", $layoutData, 
				null, true, false, false);
			break;
	}
	
	
	
	
	html_print_input_hidden('status_' . $layoutData['id'],
		$layoutData['status_calculated']);
	
}

echo "<img style='position: absolute; top: 0px; left: 0px;' id='background_img' src='" . $metaconsole_hack .
			"images/console/background/" . $background . "' width='100%' height='100%' />";

echo '</div>';
echo '</div>';

html_print_input_hidden('background_width', $widthBackground);
html_print_input_hidden('background_height', $heightBackground);

$backgroundSizes = getimagesize(
	$config['homedir'] . '/images/console/background/' . $background);
html_print_input_hidden('background_original_width', $backgroundSizes[0]);
html_print_input_hidden('background_original_height', $backgroundSizes[1]);
html_print_input_hidden('id_visual_console', $visualConsole['id']);
html_print_input_hidden('message_size', __('Min allowed size is 1024x768'));


// Loading dialog
echo "<div id='loading_in_progress_dialog' style='display: none; text-align: center;' title='" . __('Action in progress') . "'>" .
	__('Loading in progress') . '<br />' .
	html_print_image("images/spinner.gif", true) .
	"</div>";

echo "<div id='saving_in_progress_dialog' style='display: none; text-align: center;' title='" . __('Action in progress') . "'>" .
	__('Saving in progress') . '<br />' .
	html_print_image("images/spinner.gif", true) .
	"</div>";

echo "<div id='delete_in_progress_dialog' style='display: none; text-align: center;' title='" . __('Action in progress') . "'>" .
	__('Deletion in progress') . '<br />' .
	html_print_image("images/spinner.gif", true) .
	"</div>";

//CSS
ui_require_css_file ('color-picker');
ui_require_css_file ('jquery-ui-1.8.17.custom');


//Javascript
//ui_require_jquery_file('jquery-ui-1.8.17.custom.min');
ui_require_jquery_file('colorpicker');
ui_require_javascript_file('wz_jsgraphics');
ui_require_javascript_file('pandora_visual_console');
ui_require_javascript_file('visual_console_builder.editor', 'godmode/reporting/');
ui_require_javascript_file_enterprise('functions_visualmap', defined('METACONSOLE'));
ui_require_javascript_file('tiny_mce', 'include/javascript/tiny_mce/');

// Javascript file for base 64 encoding of label parameter 
ui_require_javascript_file ('encode_decode_base64');
?>
<style type="text/css">
.ui-resizable-handle {
	background: transparent !important;
	border: transparent !important;
}
</style>
<script type="text/javascript">
	id_visual_console = <?php echo $visualConsole['id']; ?>;
	visual_map_main();
	
	tinyMCE.init({
		mode : "exact",
		elements: "text-label",
		theme : "advanced",
		<?php
		if ($config['style'] == 'pandora_legacy') {
			echo 'content_css: "' . ui_get_full_url('include/styles/pandora_legacy.css', false, false, false) . '",' . "\n";
		}
		else {
			echo 'content_css: "' . ui_get_full_url('include/styles/pandora.css', false, false, false) . '",' . "\n";
		}
		?>
		theme_advanced_font_sizes :
			"4pt=.visual_font_size_4pt, " +
			"6pt=.visual_font_size_6pt, " +
			"8pt=.visual_font_size_8pt, " +
			"10pt=.visual_font_size_10pt, " +
			"12pt=.visual_font_size_12pt, " +
			"14pt=.visual_font_size_14pt, " +
			"24pt=.visual_font_size_24pt, " +
			"36pt=.visual_font_size_36pt, " +
			"72pt=.visual_font_size_72pt, " +
			"96pt=.visual_font_size_96pt, " +
			"128pt=.visual_font_size_128pt, " +
			"154pt=.visual_font_size_154pt, " +
			"196pt=.visual_font_size_196pt",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_buttons1 : "bold,italic, |, justifyleft, justifycenter, justifyright, |,undo, redo, |, image, link, |, fontselect, |, forecolor, fontsizeselect, |, code",
		theme_advanced_buttons2 : "",
		theme_advanced_buttons3 : "",
		theme_advanced_statusbar_location : "none",
		width: "400",
		height: "200",
		nowrap: true,
		plugins : "noneditable",
		inline_styles : true,
		valid_children : "+body[style]",
		element_format : "html",
		editor_deselector : "noselected"
	});

	$('.item img').each(function(){
		
		if($(this).css('float')=='left' || $(this).css('float')=='right'){
			
		$(this).css('margin-top',(parseInt($(this).parent().css('height'))/2-parseInt($(this).css('height'))/2)+'px');
		$(this).css('margin-left','');
		}
		else{
			$(this).css('margin-left',(parseInt($(this).parent().css('width'))/2-parseInt($(this).css('width'))/2)+'px');
			$(this).css('margin-top','');
		}
		
	});
	
	$('#process_value').change(function(){
		if($(this).val() == 0){
			$('#period_row').css('display','none');
		}
		else{
			$('#period_row').css('display','');
		}
	});
			
</script>
