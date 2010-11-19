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

check_login ();

if (! give_acl ($config['id_user'], 0, "PM")) {
	pandora_audit("ACL Violation",
		"Trying to access extensions list");
	include ("general/noaccess.php");
	exit;
}

if (sizeof ($config['extensions']) == 0) {
	echo '<h3>'.__('There are no extensions defined').'</h3>';
	return;
}

// Header
print_page_header (__('Extensions'). " &raquo; ". __('Defined extensions'), "images/extensions.png", false, "", true, "" );

$enterprise = (bool)get_parameter('enterprise', 0);
$delete = get_parameter ("delete", "");
$enabled = get_parameter("enabled", "");
$disabled = get_parameter("disabled", "");


if ($delete != ""){
	if ($enterprise) {
		if (!file_exists($config["homedir"]."/enterprise/extensions/ext_backup"))
		{
			mkdir($config["homedir"]."/enterprise/extensions/ext_backup");
		}
	}
	else {
		if (!file_exists($config["homedir"]."/extensions/ext_backup"))
		{
			mkdir($config["homedir"]."/extensions/ext_backup");
		}
	}
	
	if ($enterprise) {
		$source = $config["homedir"]."/enterprise/extensions/" . $delete;
		$endFile = $config["homedir"]."/enterprise/extensions/ext_backup/" . $delete;
	}
	else {
		$source = $config["homedir"]."/extensions/" . $delete;
		$endFile = $config["homedir"]."/extensions/ext_backup/" . $delete;
	}
		
	
	rename($source, $endFile);
	
	?>
	<script type="text/javascript">
	$(document).ready(function() {
			var href = location.href.replace(/&enterprise=(0|1)&delete=.*/g, "");
			location = href;
		}
	);
	</script>
	<?php
}


if ($enabled != '') {
	if ($enterprise) {
		$endFile = $config["homedir"]."/enterprise/extensions/" . $enabled;
		$source= $config["homedir"]."/enterprise/extensions/disabled/" . $enabled;
	}
	else {
		$endFile = $config["homedir"]."/extensions/" . $enabled;
		$source = $config["homedir"]."/extensions/disabled/" . $enabled;
	}
	
	rename($source, $endFile);
	
	?>
	<script type="text/javascript">
	$(document).ready(function() {
			var href = location.href.replace(/&enterprise=(0|1)&enabled=.*/g, "");
			location = href;
		}
	);
	</script>
	<?php
}

if ($disabled != '') {
	if ($enterprise) {
		if (!file_exists($config["homedir"]."/enterprise/extensions/disabled"))
		{
			mkdir($config["homedir"]."/enterprise/extensions/disabled");
		}
	}
	else {
		if (!file_exists($config["homedir"]."/extensions/disabled"))
		{
			mkdir($config["homedir"]."/extensions/disabled");
		}
	}
	
	if ($enterprise) {
		$source = $config["homedir"]."/enterprise/extensions/" . $disabled;
		$endFile = $config["homedir"]."/enterprise/extensions/disabled/" . $disabled;
	}
	else {
		$source = $config["homedir"]."/extensions/" . $disabled;
		$endFile = $config["homedir"]."/extensions/disabled/" . $disabled;
	}
		
	
	rename($source, $endFile);
	
	?>
	<script type="text/javascript">
	$(document).ready(function() {
			var href = location.href
			href = href.replace(/&enterprise=(0|1)&disabled=.*/g, "");
			console.log(href);
			location = href;
		}
	);
	</script>
	<?php
}

$extensions = getExtensionInfo();

$table->width = '95%';

$table->head = array();
$table->head[] = __('File');
$table->head[] = "<span title='" . __("Enterprise") . "'>" . __('E.') . "</span>";
$table->head[] = "<span title='" . __("Godmode Function") . "'>" . __('G.F.') . "</span>";
$table->head[] = "<span title='" . __("Godmode Menu") . "'>" . __('G.M.') . "</span>";
$table->head[] = "<span title='" . __("Operation Menu") . "'>" . __('O.M.') . "</span>";
$table->head[] = "<span title='" . __("Operation Function") . "'>" . __('O.F.') . "</span>";
$table->head[] = "<span title='" . __("Login Function") . "'>" . __('L.F.') . "</span>";
$table->head[] = "<span title='" . __("Agent operation tab") . "'>" . __('A.O.T.') . "</span>";
$table->head[] = "<span title='" . __("Agent godmode tab") . "'>" . __('A.G.T.') . "</span>";
$table->head[] = "<span title='" . __("Operation") . "'>" . __('O.') . "</span>";

$table->width = array();
$table->width[] = '30%';
$table->width[] = '22px';
$table->width[] = '44px';
$table->width[] = '44px';
$table->width[] = '44px';
$table->width[] = '44px';
$table->width[] = '66px';
$table->width[] = '66px';
$table->width[] = '44px';

$table->align = array();
$table->align[] = 'left';
$table->align[] = 'center';
$table->align[] = 'center';
$table->align[] = 'center';
$table->align[] = 'center';
$table->align[] = 'center';
$table->align[] = 'center';
$table->align[] = 'center';
$table->align[] = 'center';
$table->align[] = 'center';

$table->data = array();
foreach ($extensions as $file => $extension) {
	$data = array();
	
	
	$on = '<img src="images/dot_green.png" />';
	$off = '<img src="images/dot_red.png" />';
	if (!$extension['enabled']) {
		$on = '<img src="images/dot_green.disabled.png" />';
		$off = '<img src="images/dot_red.disabled.png" />';
		$data[] = '<i style="color: grey;">' . $file . '</i>';
	}
	else {
		$data[] = $file;
	}
	
	if ($extension['enterprise']) {
		$data[] = $on;
	}
	else {
		$data[] = $off;
	}
	
	if ($extension['godmode_function']) {
		$data[] = $on;
	}
	else {
		$data[] = $off;
	}
	
	if ($extension['godmode_menu']) {
		$data[] = $on;
	}
	else {
		$data[] = $off;
	}
	
	if ($extension['operation_menu']) {
		$data[] = $on;
	}
	else {
		$data[] = $off;
	}
	
	if ($extension['operation_function']) {
		$data[] = $on;
	}
	else {
		$data[] = $off;
	}
	
	if ($extension['login_function']) {
		$data[] = $on;
	}
	else {
		$data[] = $off;
	}
	
	if ($extension['extension_ope_tab']) {
		$data[] = $on;
	}
	else {
		$data[] = $off;
	}
	
	if ($extension['extension_god_tab']) {
		$data[] = $on;
	}
	else {
		$data[] = $off;
	}
	
	if (!$extension['enabled']) {
		$data[] = '<a title="' . __('Delete') . '" href="index.php?sec=gextensions&amp;sec2=godmode/extensions&enterprise=' . (int)$extension['enterprise'] . '&delete='.$file.'" class="mn"><img src="images/cross.disabled.png"></a>' .
			' <a title="' . __('Enable') . '" href="index.php?sec=gextensions&amp;sec2=godmode/extensions&enterprise=' . (int)$extension['enterprise'] . '&enabled='.$file.'" class="mn"><img src="images/lightbulb_off.png"></a>';
	}
	else {
		$data[] = '<a title="' . __('Delete') . '" href="index.php?sec=gextensions&amp;sec2=godmode/extensions&enterprise=' . (int)$extension['enterprise'] . '&delete='.$file.'" class="mn"><img src="images/cross.png"></a>' .
			' <a title="' . __('Disable') . '"  href="index.php?sec=gextensions&amp;sec2=godmode/extensions&enterprise=' . (int)$extension['enterprise'] . '&disabled='.$file.'" class="mn"><img src="images/lightbulb.png"></a>';
	}
	
	$table->data[] = $data;
}
print_table ($table);
?>
