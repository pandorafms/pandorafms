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

if (! give_acl ($config['id_user'], 0, "AR")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation",
		"Trying to access node graph builder");
	include ("general/noaccess.php");
	exit;
}

require_once ('include/functions_networkmap.php');

// Load variables
$layout = (string) get_parameter ('layout', 'radial');
$nooverlap = (int) get_parameter ('nooverlap', 0);
$pure = (int) get_parameter ('pure');
$zoom = (float) get_parameter ('zoom');
$ranksep = (float) get_parameter ('ranksep', 2.5);
$simple = (int) get_parameter ('simple', 0);
$regen = (int) get_parameter ('regen',1); // Always regen by default
$font_size = (int) get_parameter ('font_size', 12);
$group = (int) get_parameter ('group', 0);
$center = (int) get_parameter ('center', 0);

/* Main code */

echo '<h2>'.__('Pandora agents').' &raquo; '.__('Network map').'&nbsp;';
if ($pure == 1) {
	echo '<a href="index.php?sec=estado&amp;sec2=operation/agentes/networkmap&amp;pure=0">';
	print_image ("images/normalscreen.png", false, array ('title' => __('Normal screen'), 'alt' => __('Normal screen')));
	echo '</a>';
} else {
	echo '<a href="index.php?sec=estado&amp;sec2=operation/agentes/networkmap&amp;pure=1">';
	print_image ("images/fullscreen.png", false, array ('title' => __('Normal screen'), 'alt' => __('Normal screen')));
	echo '</a>';
}
echo '</h2>';

// Layout selection
$layout_array = array (
			'circular' => 'circular',
			'radial' => 'radial',
			'spring1' => 'spring 1',
			'spring2' => 'spring 2',
			'flat' => 'flat');

echo '<form action="index.php?sec=estado&amp;sec2=operation/agentes/networkmap&amp;pure='.$pure.'&amp;center='.$center.'" method="post">';
echo '<table cellpadding="4" cellspacing="4" class="databox">';
echo '<tr>';
echo '<td valign="top">' . __('Group') . ' &nbsp;';
print_select_from_sql ('SELECT id_grupo, nombre FROM tgrupo WHERE id_grupo > 1 ORDER BY nombre', 'group', $group, '', 'All', 0, false);
echo '</td>';
echo '<td valign="top">' . __('Layout') . ' &nbsp;';
print_select ($layout_array, 'layout', $layout, '', '', '');
echo '</td>';

echo '<td valign="top">' . __('No Overlap') . ' &nbsp;';
print_checkbox ('nooverlap', '1', $nooverlap);
echo '</td>';

echo '<td valign="top">' . __('Simple') . ' &nbsp;';
print_checkbox ('simple', '1', $simple);
echo '</td>';

echo '<td valign="top">' . __('Regenerate') . ' &nbsp;';
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

	echo '<td valign="top">' . __('Zoom') . ' &nbsp;';
	print_select ($zoom_array, 'zoom', $zoom, '', '', '');
	echo '</td>';
	
}

if ($nooverlap == 1){
	echo "<td>";
	echo __('Distance between nodes') . ' &nbsp;';
	print_input_text ('ranksep', $ranksep, $alt = 'Separation between elements in the map (in Non-overlap mode)', 3, 4, 0);
	echo "</td>";
}

echo "<td>";
echo __('Font') . ' &nbsp;';
print_input_text ('font_size', $font_size, $alt = 'Font size (in pt)', 3, 4, 0);
echo "</td>";

//echo '  Display groups  <input type="checkbox" name="group" value="group" class="chk"/>';
echo '<td>';
print_submit_button (__('Update'), "updbutton", false, 'class="sub upd"');
echo '</td></tr>';
echo '</table></form>';

// Set filter
$filter = get_filter ($layout);

// Generate dot file
$graph = generate_dot (__('Pandora FMS'), $group, $simple, $font_size, $layout, $nooverlap, $zoom, $ranksep, $center, $regen, $pure);

if ($graph === false) {
	print_error_message (__('Map could not be generated'));
	echo __('No agents found');
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
		echo "<br /> Apparently something went wrong reading the output.<br />";
		echo "<br /> Is ".$config["attachment_store"]." readable by the webserver process?";
		return;
	}
	print_image ($filename_img, false, array ("alt" => __('Network map'), "usemap" => "#networkmap"));
	require ($filename_map);
} else {
	print_error_message (__('Map could not be generated'));
	echo $result;
	echo "<br /> Apparently something went wrong executing the command or writing the output.";
	echo "<br /><br /> Is ".$filter." (usually part of GraphViz) and echo installed and able to be executed by the webserver process?";
	echo "<br /><br /> Is your webserver restricted from executing command line tools through the <code>system()</code> call (PHP Safe Mode or SELinux)";
	echo "<br /><br /> Is ".$config["attachment_store"]." writeable by the webserver process? To change this do the following (POSIX-based systems): chown &lt;apache user&gt; ".$config["attachment_store"];
	return;
}

?>
