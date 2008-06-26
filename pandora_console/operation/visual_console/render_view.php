<?php

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development and project architecture and management
// Copyright (c) 2005-2008 Artica Soluciones Tecnologicas, info@artica.es
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Login check
global $config;
global $REMOTE_ADDR;

require ('include/functions_visual_map.php');

if (comprueba_login() != 0) {
	audit_db($config["id_user"],$REMOTE_ADDR, "ACL Violation","Trying to access graph builder");
	include ("general/noaccess.php");
	exit;
}

$id_layout = (int) get_parameter ('id');
$refr = (int) get_parameter ('refr');

// Get input parameter for layout id
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
if (! $id_layout) {
	audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to access visual console without id layout");
	include ("general/noaccess.php");
	exit;
}

$layout = get_db_row ('tlayout', 'id', $id_layout);

if (! $layout) {
	audit_db($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to access visual console without id layout");
	include ("general/noaccess.php");
	exit;
}

$id_group = $layout["id_group"];
$layout_name = $layout["name"];
$fullscreen = $layout["fullscreen"];
$background = $layout["background"];
$bwidth = $layout["width"];
$bheight = $layout["height"];

$pure_url = "&pure=".$config["pure"];

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
// RENDER MAP !
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
echo "<h1>".$layout_name;

if ($config["pure"] == 0){
	echo lang_string("Full screen mode");
	echo "&nbsp;";
	echo "<a href='index.php?sec=visualc&sec2=operation/visual_console/render_view&id=$id_layout&refr=$refr&pure=1'>";
	echo "<img src='images/monitor.png' title='".lang_string("Full screen mode")."'>";
	echo "</a>";
} else {
	echo lang_string("Back to normal mode");
	echo "&nbsp;";
	echo "<a href='index.php?sec=visualc&sec2=operation/visual_console/render_view&id=$id_layout&pure=0&refr=$refr'>";
	echo "<img src='images/monitor.png' title='".lang_string("Back to normal mode")."'>";
	echo "</a>";
}

echo "</h1>";

if ($refr) {
	echo '<div id="countdown">';
	echo '</div>';
}

print_pandora_visual_map ($id_layout);

echo "<div style='height:30px'>";
echo "</div>";

$refresh_values = array ();
$refresh_values[5] = "5 ". lang_string ('seconds');
$refresh_values[30] = "30 ". lang_string ('seconds');
$refresh_values[60] = "1 ". lang_string ('minutes');
$refresh_values[120] = "2 ". lang_string ('minutes');
$refresh_values[300] = "5 ". lang_string ('minutes');
$refresh_values[600] = "10 ". lang_string ('minutes');
$refresh_values[1800] = "30 ". lang_string ('minutes');

$table->width = '300px';
$table->data = array ();
$table->data[0][0] = lang_string ('auto_refresh_time');
$table->data[0][1] = print_select ($refresh_values, 'refr', $refr, '', 'N/A', 0, true);
$table->data[0][2] = print_submit_button (lang_string ('refresh'), '', false, 'class="sub next"', true);

echo '<form method="post" action="index.php?sec=visualc&sec2=operation/visual_console/render_view">';
print_input_hidden ('pure', $config["pure"]);
print_input_hidden ('id', $id_layout);
print_table ($table);
echo "</form>";
?>

<link rel="stylesheet" href="include/styles/countdown.css" type="text/css" />
<script type="text/javascript" src="include/javascript/jquery.js"></script>
<script type="text/javascript" src="include/javascript/jquery.countdown.js"></script>
<script type="text/javascript" src="include/languages/countdown_<?=$config['language']?>.js"></script>
<script type="text/javascript" src="include/javascript/pandora_visual_console.js"></script>
<script language="javascript" type="text/javascript">
$(document).ready (function () {
<?php if ($refr) : ?>
	t = new Date();
	t.setTime (t.getTime() + <?=$refr * 1000?>);
	$.countdown.setDefaults($.countdown.regional["<?=$config['language']?>"]);
	$("#countdown").countdown({until: t, format: 'MS', description: '<?=lang_string ("Until refresh")?>'});
<?php endif; ?>
	draw_lines (lines, 'layout_map');
});
</script>
