<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Login check
require ('include/functions_visual_map.php');

check_login ();

$id_layout = (int) get_parameter ('id');
$refr = (int) get_parameter ('refr');

// Get input parameter for layout id
// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
if (! $id_layout) {
	audit_db ($config["id_user"],$REMOTE_ADDR, "ACL Violation","Trying to access visual console without id layout");
	include ("general/noaccess.php");
	exit;
}

$layout = get_db_row ('tlayout', 'id', $id_layout);

if (! $layout) {
	audit_db ($config["id_user"], $REMOTE_ADDR, "ACL Violation","Trying to access visual console without id layout");
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

if (! give_acl ($config["id_user"], $id_group, "AR")) {
	audit_db ($config["id_user"], $REMOTE_ADDR, "ACL Violation", "Trying to access visual console without group access");
	require ("general/noaccess.php");
	exit;
}

// Render map
echo "<h1>".$layout_name."&nbsp;&nbsp;";

if ($config["pure"] == 0) {
	echo "<a href='index.php?sec=visualc&sec2=operation/visual_console/render_view&id=$id_layout&refr=$refr&pure=1'>";
	echo "<img src='images/monitor.png' title='".__('Full screen mode')."'>";
	echo "</a>";
} else {
	echo "<a href='index.php?sec=visualc&sec2=operation/visual_console/render_view&id=$id_layout&pure=0&refr=$refr'>";
	echo "<img src='images/monitor.png' title='".__('Back to normal mode')."'>";
	echo "</a>";
}

echo '</h1>';

print_pandora_visual_map ($id_layout);


echo '<form method="post" action="index.php?sec=visualc&sec2=operation/visual_console/render_view">';
print_input_hidden ('pure', $config["pure"]);
print_input_hidden ('id', $id_layout);
print_table ($table);
echo "</form>";
?>

<script type="text/javascript" src="include/javascript/pandora_visual_console.js"></script>
<script language="javascript" type="text/javascript">
$(document).ready (function () {
	draw_lines (lines, 'layout_map');
});
</script>
