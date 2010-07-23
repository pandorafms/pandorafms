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

if (! give_acl ($config['id_user'], 0, "AR")) {
	audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation",
		"Trying to access node graph builder");
	include ("general/noaccess.php");
	exit;
}

require_once ('include/functions_networkmap.php');

// Load variables
$layout = (string) get_parameter ('layout', 'radial');
$depth = (string) get_parameter ('depth', 'all'); // 0 to all
$nooverlap = (int) get_parameter ('nooverlap', 0);
$modwithalerts = (int) get_parameter ('modwithalerts', 0);
$showmodules = (int) get_parameter ('showmodules', 0);
$hidepolicymodules = (int) get_parameter ('hidepolicymodules', 0);
$pure = (int) get_parameter ('pure');
$zoom = (float) get_parameter ('zoom');
$ranksep = (float) get_parameter ('ranksep', 2.5);
$simple = (int) get_parameter ('simple', 0);
$regen = (int) get_parameter ('regen',1); // Always regen by default
$font_size = (int) get_parameter ('font_size', 12);
$group = (int) get_parameter ('group', 0);
$module_group = (int) get_parameter ('module_group', 0);
$center = (int) get_parameter ('center', 0);

// Layout selection
$layout_array = array (
			'circular' => 'circular',
			'radial' => 'radial',
			'spring1' => 'spring 1',
			'spring2' => 'spring 2',
			'flat' => 'flat');

echo '<form action="index.php?sec=estado&amp;sec2=operation/agentes/networkmap&amp;tab=groups&amp;pure='.$pure.'&amp;center='.$center.'" method="post">';
echo '<table cellpadding="4" cellspacing="4" class="databox" width="99%">';
echo '<tr><td>';
echo '<table cellpadding="0" cellspacing="0" border="0" width="100%">';
echo '<tr>';
echo '<td valign="top">' . __('Group') . '<br />';
print_select_from_sql ('SELECT id_grupo, nombre FROM tgrupo WHERE id_grupo > 0 ORDER BY nombre', 'group', $group, '', 'All', 0, false);
echo '</td>';
echo '<td valign="top">' . __('Module group') . '<br />';
print_select_from_sql ('SELECT id_mg, name FROM tmodule_group', 'module_group', $module_group, '', 'All', 0, false);
echo '</td>';
echo '<td valign="top">' . __('Layout') . '<br />';
print_select ($layout_array, 'layout', $layout, '', '', '');
echo '</td>';
echo '<td valign="top">' . __('Depth') . '<br />';
$depth_levels = array('all' => __('All'), 'agent' => __('Agents'), 'group' => __('Groups'));
print_select ($depth_levels, 'depth', $depth, '', '', '', 0, false, false);
echo '</td>';
echo '</tr></table>';

echo '</td></tr><tr><td>';

echo '<table cellpadding="0" cellspacing="0" border="0" width="100%">';
echo '<tr>';
echo '<td valign="top">' . __('No Overlap') . '<br />';
print_checkbox ('nooverlap', '1', $nooverlap);
echo '</td>';

echo '<td valign="top">' . __('Only modules with alerts') . '<br />';
print_checkbox ('modwithalerts', '1', $modwithalerts);
echo '</td>';

if(($depth == 'all') && $config['enterprise_installed']) {
	echo '<td valign="top">' . __('Hide policy modules') . '<br />';
	print_checkbox ('hidepolicymodules', '1', $hidepolicymodules);
	echo '</td>';
}

echo '<td valign="top">' . __('Simple') . '<br />';
print_checkbox ('simple', '1', $simple);
echo '</td>';

echo '<td valign="top">' . __('Regenerate') . '<br />';
print_checkbox ('regen', '1', $regen);
echo '</td>';

if ($pure == "1") {
	// Zoom
	$zoom_array = array (
		'1' => 'x1',
		'1.2' => 'x2',
		'1.6' => 'x3',
		'2' => 'x4',
		'2.5' => 'x5',
		'5' => 'x10',
	);

	echo '<td valign="top">' . __('Zoom') . '<br />';
	print_select ($zoom_array, 'zoom', $zoom, '', '', '', 0, false, false, false);
	echo '</td>';
	
}

if ($nooverlap == 1){
	echo "<td>";
	echo __('Distance between nodes') . '<br />';
	print_input_text ('ranksep', $ranksep, $alt = 'Separation between elements in the map (in Non-overlap mode)', 3, 4, 0);
	echo "</td>";
}

echo "<td>";
echo __('Font') . '<br />';
print_input_text ('font_size', $font_size, $alt = 'Font size (in pt)', 2, 4, 0);
echo "</td>";

//echo ' Display groups <input type="checkbox" name="group" value="group" class="chk"/>';
echo '<td>';
print_submit_button (__('Update'), "updbutton", false, 'class="sub upd"');
echo '</td></tr>';
echo '</table>';
echo '</table></form>';

// Set filter
$filter = get_filter ($layout);

// Generate dot file
$graph = generate_dot_groups (__('Pandora FMS'), $group, $simple, $font_size, $layout, $nooverlap, $zoom, $ranksep, $center, $regen, $pure, $modwithalerts, $module_group, $hidepolicymodules, $depth);

if ($graph === false) {
	print_error_message (__('Map could not be generated'));
	echo '<div class="nf">' . __('No agents found') . '</div>';
	return;
}

// Generate image and map
// If image was generated just a few minutes ago, then don't regenerate (it takes long) unless regen checkbox is set
$filename_map = $config["attachment_store"]."/networkmap_".$layout;
$filename_img = "attachment/networkmap_".$layout."_".$font_size;
$filename_dot = $config["attachment_store"]."/networkmap_".$layout;
if ($simple) {
	$filename_map .= "_simple";
	$filename_img .= "_simple";
	$filename_dot .= "_simple";
}
if ($nooverlap) {
	$filename_map .= "_nooverlap";
	$filename_img .= "_nooverlap";
	$filename_dot .= "_nooverlap";
}
$filename_map .= ".map";
$filename_img .= ".png";
$filename_dot .= ".dot";

if ($regen != 1 && file_exists ($filename_img) && filemtime ($filename_img) > get_system_time () - 300) {
	$result = true;
} else {
	$fh = @fopen ($filename_dot, 'w');
	if ($fh === false) {
		$result = false;
	} else {
		fwrite ($fh, $graph);
		$cmd = "$filter -Tcmapx -o".$filename_map." -Tpng -o".$filename_img." ".$filename_dot;
		$result = system ($cmd);
		fclose ($fh);
		unlink ($filename_dot);
	}
}

if ($result !== false) {
	if (! file_exists ($filename_map)) {
		print_error_message (__('Map could not be generated'));
		echo $result;
		echo "<div class='warn'>Apparently something went wrong reading the output.</div>";
		echo "<br />Is ".$config["attachment_store"]." readable by the webserver process?";
		echo "<br /><br /> Is ".$filter." (usually part of GraphViz) and echo installed and able to be executed by the webserver process?";
		return;
	}
	print_image ($filename_img, false, array ("alt" => __('Network map'), "usemap" => "#networkmap"));
	require ($filename_map);
} else {
	print_error_message (__('Map could not be generated'));
	echo $result;
	echo "<div class='warn'>Apparently something went wrong executing the command or writing the output.</div>";
	echo "<br />Is ".$filter." (usually part of GraphViz) and echo installed and able to be executed by the webserver process?";
	echo "<br /><br /> Is your webserver restricted from executing command line tools through the <code>system()</code> call (PHP Safe Mode or SELinux)";
	echo "<br /><br /> Is ".$config["attachment_store"]." writeable by the webserver process? To change this do the following (POSIX-based systems): chown &lt;apache user&gt; ".$config["attachment_store"];
	return;
}

?>
