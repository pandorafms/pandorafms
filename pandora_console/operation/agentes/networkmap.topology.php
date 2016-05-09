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

// Networkmap id required
if (!isset($id_networkmap)) {
	db_pandora_audit("ACL Violation",
		"Trying to access node graph builder");
	require ("general/noaccess.php");
	exit;
}

// Get the group for ACL
if (!isset($store_group)) {
	$store_group = db_get_value("store_group", "tnetwork_map", "id_networkmap", $id_networkmap);
	if ($store_group === false) {
		db_pandora_audit("ACL Violation",
			"Trying to accessnode graph builder");
		require ("general/noaccess.php");
		exit;
	}
}

// ACL for the networkmap permission
if (!isset($networkmap_read))
	$networkmap_read = check_acl ($config['id_user'], $store_group, "MR");
if (!isset($networkmap_write))
	$networkmap_write = check_acl ($config['id_user'], $store_group, "MW");
if (!isset($networkmap_manage))
	$networkmap_manage = check_acl ($config['id_user'], $store_group, "MM");

if (!$networkmap_read && !$networkmap_write && !$networkmap_manage) {
	db_pandora_audit("ACL Violation",
		"Trying to access node graph builder");
	include ("general/noaccess.php");
	exit;
}

require_once ('include/functions_networkmap.php');

$strict_user = db_get_value('strict_acl', 'tusuario', 'id_user', $config['id_user']);

// Set filter
$filter = networkmap_get_filter ($layout);

if (!isset($text_filter)) {
	$text_filter = '';
}

if (!isset($size_canvas)) {
	$size_canvas = null;
}


// Generate dot file
$graph = networkmap_generate_dot(__('Pandora FMS'), $group, $simple,
	$font_size, $layout, $nooverlap, $zoom, $ranksep, $center, $regen,
	$pure, $id_networkmap, $show_snmp_modules, true, true,
	$text_filter, $l2_network, null, $dont_show_subgroups, $strict_user,
	$size_canvas);

if ($graph === false) {
	ui_print_error_message (__('Map could not be generated'));
	echo '<div class="nf">' . __('No agents found') . '</div>';
	
	return;
}

// Generate image and map
// If image was generated just a few minutes ago, then don't regenerate
// (it takes long) unless regen checkbox is set
$filename_map = io_safe_output($config["attachment_store"]) . "/networkmap_" . $filter;
$filename_img = io_safe_output($config["attachment_store"]) . "/networkmap_" . $filter . "_" . $font_size;
$filename_dot = io_safe_output($config["attachment_store"]) . "/networkmap_" . $filter;
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
$filename_map .= "_" . $id_networkmap . ".map";
$filename_img .= "_" . $id_networkmap . ".png";
$filename_dot .= "_" . $id_networkmap . ".dot";

if ($regen != 1 && file_exists ($filename_img) &&
	filemtime ($filename_img) > get_system_time () - SECONDS_5MINUTES) {
	$result = true;
}
else {
	$fh = @fopen ($filename_dot, 'w');
	if ($fh === false) {
		$result = false;
	}
	else {
		fwrite ($fh, $graph);
		
		
		$graphviz_path = (!empty($config['graphviz_bin_dir'])) ?
			io_safe_output($config['graphviz_bin_dir'] . "/")
			:
			"";
		$is_windows = strtoupper(substr(PHP_OS, 0, 3)) == 'WIN';
		if ($is_windows) {
			$graphviz_path = str_replace("/", "\\", $graphviz_path );
			$filename_map = str_replace("/", "\\", $filename_map );
			$filename_img = str_replace("/", "\\", $filename_img );
			$filename_dot = str_replace("/", "\\", $filename_dot );
			$filter = $filter . '.exe';
		}
		$cmd = escapeshellarg($graphviz_path . $filter) .
			" -Tplain -o /tmp/caca.txt " .
			" -Tcmapx " . escapeshellarg("-o$filename_map") .
			" -Tpng ". escapeshellarg("-o$filename_img") .
			" " . escapeshellarg($filename_dot);
			
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
	echo "<div style='text-align:center'>";
	$image_url = str_replace(realpath(io_safe_output($config['homedir'])), "", realpath($filename_img));
	html_print_image ($image_url . "?" . (microtime(true) * 10000), false, array ("alt" => __('Network map'), "usemap" => "#networkmap"));
	echo "</div>";
	require ($filename_map);
}
else {
	ui_print_error_message (__('Map could not be generated'));
	echo $result;
	echo "<div class='warn'>Apparently something went wrong executing the command or writing the output.</div>";
	echo "<br />Is ".$filter." (usually part of GraphViz) and echo installed and able to be executed by the webserver process?";
	echo "<br /><br /> Is your webserver restricted from executing command line tools through the <code>system()</code> call (PHP Safe Mode or SELinux)";
	echo "<br /><br /> Is ".$config["attachment_store"]." writeable by the webserver process? To change this do the following (POSIX-based systems): chown &lt;apache user&gt; ".$config["attachment_store"];
	
	return;
}
?>
