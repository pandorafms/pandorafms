<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 20012 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Real start



require_once ("../../include/config.php");

// Set root on homedir, as defined in setup
chdir ($config["homedir"]);

session_start ();
ob_start ();
echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
echo '<html xmlns="http://www.w3.org/1999/xhtml">'."\n";
echo '<head>';

// This starts the page head. In the call back function,
// things from $page['head'] array will be processed into the head
ob_start ('ui_process_page_head');

require ('include/functions_visual_map.php');

// Auto Refresh page (can now be disabled anywhere in the script)
$config["refr"] = (int) get_parameter ("refr");
$config["remote_addr"] = $_SERVER['REMOTE_ADDR'];

$hash = get_parameter ('hash');
$id_layout = (int) get_parameter ('id_layout');
$config["id_user"] = get_parameter ('id_user');

$myhash = md5($config["dbpass"].$id_layout. $config["id_user"]);

// Check input hash
if ( $myhash != $hash){
	exit;
}

$refr = (int) get_parameter ('refr', $config['vc_refr']);
$vc_refr = false;
$layout = db_get_row ('tlayout', 'id', $id_layout);

if (! $layout) {
	db_pandora_audit("ACL Violation","Trying to access visual console without id layout");
	include ("../../general/noaccess.php");
	exit;
}

$id_group = $layout["id_group"];
$layout_name = $layout["name"];
$fullscreen = $layout["fullscreen"];
$background = $layout["background"];
$bwidth = $layout["width"];
$bheight = $layout["height"];

if (!isset($config['pure']))
	$config['pure'] = 0;

// Render map
$options = array();
echo '<div style="width: 95%; background: white; margin: 20px auto 20px auto; box-shadow: 10px 10px 5px #000;">';
echo "<h1>". $layout_name. "</h1>";

visual_map_print_visual_map ($id_layout, true, true, null, null, '../../');

$values = array ();
$values[5] = human_time_description_raw (5);
$values[30] = human_time_description_raw (30);
$values[60] = human_time_description_raw (60);
$values[120] = human_time_description_raw (120);
$values[300] = human_time_description_raw (300);
$values[600] = human_time_description_raw (600);
$values[1800] = human_time_description_raw (1800);

$table->width = '90%';
$table->data = array ();
$table->style = array ();
$table->style[2] = 'text-align: center';
$table->data[0][0] = __('Autorefresh time');

if (empty($config["vc_refr"])){
	$vc_refr = true;
	$config["vc_refr"] = $refr;	
}

$table->data[0][1] = html_print_select ($values, 'refr', $config["vc_refr"], '', 'N/A', 0, true, false, false);
$table->data[0][2] = html_print_submit_button (__('Refresh'), '', false, 'class="sub next"', true);
$table->data[0][2] .= html_print_input_hidden ('vc_refr', $config["vc_refr"], true);

if ($vc_refr){
	$config["vc_refr"] = 0;
}

echo '<div style="height:30px">&nbsp;</div>';

if ($config['pure'] && $config["refr"] != 0) {
	echo '<div id="countdown"><br /></div>';
}

echo '<div style="height:30px">&nbsp;</div>';

echo '<form method="post">';
html_print_input_hidden ('pure', $config["pure"]);
html_print_input_hidden ('id', $id_layout);
html_print_table ($table);
echo '</form>';
echo '</div>';

if ($config["pure"] && $config["refr"] != 0) {
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
	
<?php if ($config["pure"] && $config["refr"] > 0): ?>
	t = new Date();
	t.setTime (t.getTime() + <?php echo $config["refr"] * 1000; ?>);
	$("#countdown").countdown({until: t, format: 'MS', description: '<?php echo __('Until refresh'); ?>'});
	
<?php endif; ?>
	draw_lines (lines, 'layout_map');
});
/* ]]> */
</script>
