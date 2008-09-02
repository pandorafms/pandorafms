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

echo "<table width=100% cellpadding=0 cellspacing=0 style='margin:0px; padding:0px;' border=0>";
echo "<tr>";
echo "<td>";

// Yes, put here your corporate logo instead pandora_logo_head.png

echo '<a href="index.php"><img src="images/pandora_logo_head.png" border="0" alt="logo" /></a>';

// Margin to logo

echo "<td width=20>";

// First column
echo "<td>";
echo '<img src="images/user_'.((dame_admin ($_SESSION["id_usuario"]) == 1) ? 'suit' : 'green' ).'.png" class="bot">&nbsp;'.'<a class="white">'.__('You are ').'[<b>'.$_SESSION["id_usuario"].'</b>]</a>';

echo "<br><br>";

echo '<a class="white_bold" href="index.php?bye=bye"><img src="images/lock.png" class="bot">&nbsp;'. __('Logout').'</a>';



// Second column
echo "<td>";
echo '<a class="white_bold" href="index.php?sec=main"><img src="images/information.png" class="bot">&nbsp;'.__('General information').'</a>';

echo "<br><br>";

echo '<a class="white_bold" href="index.php?sec=estado_server&sec2=operation/servers/view_server&refr=60">';
if (check_server_status () == 0)
	echo '<img src="images/error.png" class="bot" />&nbsp;'.__('Server status: DOWN');
else
    echo '<img src="images/ok.png" class="bot" />&nbsp;'.__('System ready');
echo "</a>";


// Third column
// Autorefresh
echo "<td>";
if (get_parameter ("refr") != 0) 
	echo '<a class="white_grey_bold" href="'.((substr($_SERVER['REQUEST_URI'],-1) != "/") ? $_SERVER['REQUEST_URI'] : 'index.php?' ).'&refr=0"><img src="images/page_lightning.png" class="bot" />&nbsp;'. __('Autorefresh').'</a>';
else
	echo '<a class="white_bold" href="'.((substr($_SERVER['REQUEST_URI'],-1) != "/") ? $_SERVER['REQUEST_URI'] : "index.php?" ).'&refr=5"><img src="images/page_lightning.png" class="bot" />&nbsp;'.__('Autorefresh').'</a>';

echo "<br><br>";

echo '<a class="white_bold" href="index.php?sec=eventos&sec2=operation/events/events&refr=5"><img src="images/lightning_go.png" class="bot" />&nbsp;'.__('Events').'</a>';

// logo

echo "<td>";
echo '<div id="head_r"><span id="logo_text1">Pandora</span> <span id="logo_text2">FMS</span></div>';

echo "</table>";
/*
if(!isset ($_SESSION["id_usuario"])) {
	echo "</div>";
	return;
}
$table->width=480;
$table->border=0;
$table->cellpadding=3;
$table->size=array("30%");
$table->class="inherit";
$table->rowclass=array("inherit","inherit");

$table->data[] = array (
			// First column
    			'<img src="images/user_'.((dame_admin ($_SESSION["id_usuario"]) == 1) ? 'suit' : 'green' ).'.png" class="bot">&nbsp;'.'<a class="white">'.__('You are ').'[<b>'.$_SESSION["id_usuario"].'</b>]</a>',
			// Second column 
			'<a class="white_bold" href="index.php?sec=main"><img src="images/information.png" class="bot">&nbsp;'.__('General information').'</a>',
			// Third column 
			// Autorefresh
			((get_parameter ("refr") != 0) ?
        			'<a class="white_grey_bold" href="'.((substr($_SERVER['REQUEST_URI'],-1) != "/") ? $_SERVER['REQUEST_URI'] : 'index.php?' ).'&refr=0"><img src="images/page_lightning.png" class="bot" />&nbsp;'. __('Autorefresh').'</a>'
    			:
        			'<a class="white_bold" href="'.((substr($_SERVER['REQUEST_URI'],-1) != "/") ? $_SERVER['REQUEST_URI'] : "index.php?" ).'&refr=5"><img src="images/page_lightning.png" class="bot" />&nbsp;'.__('Autorefresh').'</a>'
    			)
		);

$table->data[] = array (
			'<a class="white_bold" href="index.php?bye=bye"><img src="images/lock.png" class="bot">&nbsp;'. __('Logout').'</a>',
			'<a class="white_bold" href="index.php?sec=estado_server&sec2=operation/servers/view_server&refr=60">'.
    			((check_server_status () == 0) ?
				'<img src="images/error.png" class="bot" />&nbsp;'.__('Server status: DOWN')
			:
        			'<img src="images/ok.png" class="bot" />&nbsp;'.__('System ready')
			).'</a>',
    			// Event - refresh
			'<a class="white_bold" href="index.php?sec=eventos&sec2=operation/events/events&refr=5"><img src="images/lightning_go.png" class="bot" />&nbsp;'.__('Events').'</a>'
		);
print_table ($table);
unset ($table);
echo "</div>";
*/

?>
