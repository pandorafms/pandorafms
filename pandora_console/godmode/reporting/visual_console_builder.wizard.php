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

if (! give_acl ($config['id_user'], 0, "IW")) {
	pandora_audit("ACL Violation",
		"Trying to access report builder");
	require ("general/noaccess.php");
	exit;
}

require_once('godmode/reporting/visual_console_builder.constans.php');
require_once ('include/functions_visual_map.php');

$table->id = 'wizard_table';
$table->width = '65%';
$table->data = array ();
$table->style = array ();
$table->style[0] = 'font-weight: bold; vertical-align:top';
$table->style[2] = 'font-weight: bold';
$table->size = array ();
$table->data = array ();

$images_list = array ();
$all_images = list_files ('images/console/icons/', "png", 1, 0);
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

$table->data[0][0] = __('Image');
$table->data[0][1] = print_select ($images_list, 'image', '', '', '', '', true);

$table->data[1][0] = __('Image range (px)');
$table->data[1][1] = print_input_text ('range', 50, '', 5, 5, true);

$table->data[2][0] = __('Image size (px)');
$table->data[2][1] = __('Width').': '.print_input_text ('width', 0, '', 5, 5, true);
$table->data[2][1] .= '<br />'.__('Height').': '.print_input_text ('height', 0, '', 5, 5, true);

$table->data[3][0] = __('Agents');
$table->data[3][1] = print_select (get_group_agents ($visualConsole['id_group'], false, "none"),
	'id_agents[]', 0, false, '', '', true, true);
	
$table->data[4][0] = __('Modules');
$table->data[4][1] = print_select (array (), 'module[]', 0, false, '', '', true, true);

echo '<form method="post" action="index.php?sec=gmap&sec2=godmode/reporting/visual_console_builder&tab=' . $activeTab  . '&id_visual_console=' . $visualConsole["id"] . '" onsubmit="if (! confirm(\''.__('Are you sure to add many elements\nin visual map?').'\')) return false;">';
print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
print_input_hidden ('action', 'update');
print_input_hidden ('id_visual_console', $visualConsole["id"]);
print_submit_button (__('Add'), 'go', false, 'class="sub wizard wand"');
echo '</div>';
echo '</form>';

//Trick for it have a traduct text for javascript.
echo '<span id="any_text" style="display: none;">' . __('Any') . '</span>';
?>
<script language="javascript" type="text/javascript">
var show_only_enabled_modules = true;
$(document).ready (function () {
	//$("#id_agents").change (agent_changed);
	$("#id_agents").change (agent_changed_by_multiple_agents);
});
</script>
