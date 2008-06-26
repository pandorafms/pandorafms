<?php
// Pandora FMS - the Free Monitoring System
// ========================================
// Copyright (c) 2008 Artica Soluciones Tecnológicas, http://www.artica.es
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

echo '
<div id="head_r">
	<span id="logo_text1">Pandora</span> <span id="logo_text2">FMS</span>
</div>
<div id="head_l">
	<a href="index.php"><img src="images/pandora_logo_head.png" border="0" alt="logo"></a>
</div>

';
echo "<div id='head_m'>";

echo "<table width=520 border='0' cellpadding=3>
	<tr>";
if (isset ($_SESSION["id_usuario"])){
    // Fist column
    echo "<td width=30%>";
	if (dame_admin($_SESSION["id_usuario"])==1)
		echo "<img src='images/user_suit.png' class='bot'> ";
	else
		echo "<img src='images/user_green.png' class='bot'> ";
	echo "<a class='white'>".lang_string ("has_connected"). '
	[<b>'. $_SESSION["id_usuario"]. '</b>]</a>';

    // Second column 
    echo "<td>";
	echo "<a class='white_bold' href='index.php?sec=main'><img src='images/information.png' class='bot'> ". lang_string ("information")."</a>";
	
    // Third column 
    echo "<td>";
    // Autorefresh
    if ((isset($_GET["refr"])) OR (isset($_POST["refr"]))) {
        echo "<a class='white_grey_bold' href='".$_SERVER['REQUEST_URI']."&refr=0'><img src='images/page_lightning.png' class='bot'> ". lang_string("Autorefresh")."</a>";
    } else {
        echo "<a class='white_bold' href='".$_SERVER['REQUEST_URI']."&refr=5'><img src='images/page_lightning.png' class='bot'> ". lang_string("Autorefresh")."</a>";
    }


    echo "<tr><td>";
    echo "<a class='white_bold' href='index.php?bye=bye'><img src='images/lock.png' class='bot'> ". lang_string ("logout")."</a>";
    
    echo "<td>";
    $server_status = check_server_status ();
    if ($server_status == 0)
        echo "<a class='white_bold' href='index.php?sec=estado_server&sec2=operation/servers/view_server&refr=60'><img src='images/error.png' class='bot'> ". lang_string("Server status: DOWN")."</a>";
    else
        echo "<a class='white_bold' href='index.php?sec=estado_server&sec2=operation/servers/view_server&refr=60'><img src='images/ok.png' class='bot'> ". lang_string("System ready")."</a>";

    echo "<td>";
    // Event - refresh
    echo "<a class='white_bold' href='index.php?sec=eventos&sec2=operation/events/events&refr=5'><img src='images/lightning_go.png' class='bot'> ". lang_string("events")."</a>";


}
echo "</tr></table>";
echo "</div>";

?>
