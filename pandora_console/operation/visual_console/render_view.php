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

global $config;

// Login check
require ($config['homedir'] . '/include/functions_visual_map.php');

check_login ();

if (!defined('METACONSOLE'))
	$id_layout = (int) get_parameter ('id');
else
	$id_layout = (int) get_parameter ('id_visualmap');

if (!defined('METACONSOLE')) {
	if ($id_layout) {
		$default_action = 'edit';
	}
	else {
		$default_action = 'new';
	}
	$action = get_parameterBetweenListValues('action', array('new', 'save', 'edit', 'update', 'delete'), $default_action);
}
else {
	$action = get_parameterBetweenListValues('action2', array('new', 'save', 'edit', 'update', 'delete'), $default_action);
}

$refr = (int) get_parameter ('refr', $config['vc_refr']);
$vc_refr = false;

if (isset($config['vc_refr']) and $config['vc_refr'] != 0)
	$view_refresh = $config['vc_refr'];
else
	$view_refresh = '60';

// Get input parameter for layout id
if (! $id_layout) {
	db_pandora_audit("ACL Violation","Trying to access visual console without id layout");
	include ("general/noaccess.php");
	exit;
}

$layout = db_get_row ('tlayout', 'id', $id_layout);

if (! $layout) {
	db_pandora_audit("ACL Violation","Trying to access visual console without id layout");
	include ("general/noaccess.php");
	exit;
}

$id_group = $layout["id_group"];
$layout_name = $layout["name"];
$fullscreen = $layout["fullscreen"];
$background = $layout["background"];
$bwidth = $layout["width"];
$bheight = $layout["height"];

$pure_url = "&pure=" . $config["pure"];

if (! check_acl ($config["id_user"], $id_group, "RR")) {
	db_pandora_audit("ACL Violation", "Trying to access visual console without group access");
	require ("general/noaccess.php");
	exit;
}

// Render map
$options = array();

$options['consoles_list']['text'] = '<a href="index.php?sec=reporting&sec2=godmode/reporting/map_builder&refr=' . $refr . '">' .
	html_print_image ("images/visual_console.png", true, array ("title" => __('Visual consoles list'))) .'</a>';

if (check_acl ($config["id_user"], $id_group, "RW")) {
	$url_base = 'index.php?sec=reporting&sec2=godmode/reporting/visual_console_builder&action=';
	
	$hash = md5($config["dbpass"]. $id_layout. $config["id_user"]);
	
	$options['public_link']['text'] = '<a href="' . ui_get_full_url('operation/visual_console/public_console.php?hash='.$hash.'&id_layout='.$id_layout.'&id_user='.$config["id_user"]) . '" target="_blank">'.
		html_print_image ("images/camera_mc.png", true, array ("title" => __('Show link to public Visual Console'))).'</a>';
	$options['public_link']['active'] = false;
	
	$options['data']['text'] = '<a href="' . $url_base . $action . '&tab=data&id_visual_console=' . $id_layout . '">' . 
		html_print_image ("images/op_reporting.png", true, array ("title" => __('Main data'))) .'</a>';
	$options['list_elements']['text'] = '<a href="' . $url_base . $action . '&tab=list_elements&id_visual_console=' . $id_layout . '">' .
		html_print_image ("images/list.png", true, array ("title" => __('List elements'))) .'</a>';
	$options['wizard']['text'] = '<a href="' . $url_base . $action . '&tab=wizard&id_visual_console=' . $id_layout . '">' .
		html_print_image ("images/wand.png", true, array ("title" => __('Wizard'))) .'</a>';
	$options['editor']['text'] = '<a href="' . $url_base . $action . '&tab=editor&id_visual_console=' . $id_layout . '">' .
		html_print_image ("images/builder.png", true, array ("title" => __('Builder'))) .'</a>';
}

$options['view']['text'] = '<a href="index.php?sec=reporting&sec2=operation/visual_console/render_view&id=' . $id_layout . '&refr=' . $view_refresh . '">' . html_print_image ("images/operation.png", true, array ("title" => __('View'))) .'</a>';
$options['view']['active'] = true;

if (!defined('METACONSOLE')) {
	if ($config["pure"] == 0) {
		$options['pure']['text'] = '<a href="index.php?sec=reporting&amp;sec2=operation/visual_console/render_view&amp;id='.$id_layout.'&amp;refr='.((int)get_parameter('refr', 0)).'&amp;pure=1">' . html_print_image ("images/full_screen.png", true, array ("title" => __('Full screen mode')))
			. "</a>";
	}
	else {
		$options['pure']['text'] = '<a href="index.php?sec=reporting&amp;sec2=operation/visual_console/render_view&amp;id='.$id_layout.'&amp;refr='.((int)get_parameter('refr', 0)).'">'
			. html_print_image ("images/normal_screen.png", true, array ("title" => __('Back to normal mode')))
			. "</a>";
		
		// In full screen, the manage options are not available
		$options = array('view' => $options['view'], 'pure' => $options['pure']);
	}
	$options['pure']['active'] = false;
}

//Set the hidden value for the javascript
if (defined('METACONSOLE')) {
	html_print_input_hidden('metaconsole', 1);
}
else {
	html_print_input_hidden('metaconsole', 0);
	ui_print_page_header (__("Visual console") . " &raquo; " . $layout_name, "images/op_reporting.png", false, '', false, $options);
}

visual_map_print_visual_map ($id_layout);

$values = array ();
$values[5] = human_time_description_raw (5);
$values[30] = human_time_description_raw (30);
$values[SECONDS_1MINUTE] = human_time_description_raw(SECONDS_1MINUTE);
$values[SECONDS_2MINUTES] = human_time_description_raw(SECONDS_2MINUTES);
$values[SECONDS_5MINUTES] = human_time_description_raw(SECONDS_5MINUTES);
$values[SECONDS_10MINUTES] = human_time_description_raw(SECONDS_10MINUTES);
$values[SECONDS_30MINUTES] = human_time_description_raw(SECONDS_30MINUTES);

$table->width = '90%';
$table->data = array ();
$table->style = array ();
$table->style[2] = 'text-align: center';
$table->data[0][0] = __('Autorefresh time');

if (empty($config["vc_refr"])) {
	$vc_refr = true;
	$config["vc_refr"] = $refr;
}

$table->data[0][1] = html_print_select ($values, 'refr', $config["vc_refr"], '', 'N/A', 0, true, false, false);
$table->data[0][2] = html_print_submit_button (__('Refresh'), '', false, 'class="sub next"', true);
$table->data[0][2] .= html_print_input_hidden ('vc_refr', $config["vc_refr"], true);

if ($vc_refr) {
	$config["vc_refr"] = 0;
}

echo '<div style="height:30px">&nbsp;</div>';

if ($config['pure'] && ((int)get_parameter('refr', 0)) != 0) {
	echo '<div id="countdown"><br /></div>';
}

echo '<div style="height:30px">&nbsp;</div>';

if (!defined('METACONSOLE'))
	echo '<form method="post" action="index.php?sec=reporting&amp;sec2=operation/visual_console/render_view">';
else
	echo '<form method="post" action="index.php?sec=screen&sec2=screens/screens&action=visualmap">';

html_print_input_hidden ('pure', $config["pure"]);
if (!defined('METACONSOLE'))
	html_print_input_hidden ('id', $id_layout);
else
	html_print_input_hidden ('id_visualmap', $id_layout);
html_print_table ($table);
echo '</form>';

if ($config["pure"] && ((int)get_parameter('refr', 0)) != 0) {
	ui_require_jquery_file ('countdown');
	ui_require_css_file ('countdown');
}
ui_require_javascript_file ('wz_jsgraphics');
ui_require_javascript_file ('pandora_visual_console');
?>
<script language="javascript" type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
	$("#refr").change(function () {
		$("#hidden-vc_refr").val($("#refr option:selected").val());
	});
	
	<?php
	if ($config["pure"] && ((int)get_parameter('refr', 0)) > 0) {
	?>
		t = new Date();
		t.setTime (t.getTime() + <?php echo ((int)get_parameter('refr', 0)) * 1000; ?>);
		$("#countdown").countdown({
			until: t,
			format: 'MS',
			description: '<?php echo __('Until refresh'); ?>'
			});
	<?php
	}
	?>
	draw_lines (lines, 'layout_map');
});
/* ]]> */
</script>
