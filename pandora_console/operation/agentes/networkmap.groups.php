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

if (! check_acl ($config['id_user'], 0, "AR")) {
	db_pandora_audit("ACL Violation",
		"Trying to access node graph builder");
	include ("general/noaccess.php");
	exit;
}

require_once ('include/functions_networkmap.php');

// Set filter
$filter = networkmap_get_filter ($layout);


// Generate dot file
$graph = networkmap_generate_dot_groups (__('Pandora FMS'), $group, $simple, $font_size, $layout, $nooverlap, $zoom, $ranksep, $center, $regen, $pure, $modwithalerts, $module_group, $hidepolicymodules, $depth, $id_networkmap);

if ($graph === false) {
	ui_print_error_message (__('Map could not be generated'));
	echo '<div class="nf">' . __('No agents found') . '</div>';
	return;
}

// Generate image and map
// If image was generated just a few minutes ago, then don't regenerate (it takes long) unless regen checkbox is set
$filename_map = safe_url_extraclean ($config["attachment_store"])."/networkmap_".$filter;
$filename_img = "attachment/networkmap_".$filter."_".$font_size;
$filename_dot = safe_url_extraclean ($config["attachment_store"])."/networkmap_".$filter;
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
$filename_map .= "_".$id_networkmap.".map";
$filename_img .= "_".$id_networkmap.".png";
$filename_dot .= "_".$id_networkmap.".dot";

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
		ui_print_error_message (__('Map could not be generated'));
		echo $result;
		echo "<div class='warn'>Apparently something went wrong reading the output.</div>";
		echo "<br />Is ".$config["attachment_store"]." readable by the webserver process?";
		echo "<br /><br /> Is ".$filter." (usually part of GraphViz) and echo installed and able to be executed by the webserver process?";
		return;
	}
	html_print_image ($filename_img, false, array ("alt" => __('Network map'), "usemap" => "#networkmap"));
	require ($filename_map);
} else {
	ui_print_error_message (__('Map could not be generated'));
	echo $result;
	echo "<div class='warn'>Apparently something went wrong executing the command or writing the output.</div>";
	echo "<br />Is ".$filter." (usually part of GraphViz) and echo installed and able to be executed by the webserver process?";
	echo "<br /><br /> Is your webserver restricted from executing command line tools through the <code>system()</code> call (PHP Safe Mode or SELinux)";
	echo "<br /><br /> Is ".$config["attachment_store"]." writeable by the webserver process? To change this do the following (POSIX-based systems): chown &lt;apache user&gt; ".$config["attachment_store"];
	return;
}

?>
