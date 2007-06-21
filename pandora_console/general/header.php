<?php
// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@openideas.info
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas
// Copyright (c) 2004-2007 Raul Mateos Martin, raulofpandora@gmail.com
// Copyright (c) 2006-2007 Jose Navarro jose@jnavarro.net
// Copyright (c) 2006-2007 Jonathan Barajas, jonathan.barajas[AT]gmail[DOT]com

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
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

echo "<table width=620 border='0'>
	<tr>";
if (isset ($_SESSION["id_usuario"])){
	echo "<td width=40%>";
	$id_usuario = entrada_limpia ($_SESSION["id_usuario"]);
	if (dame_admin($_SESSION["id_usuario"])==1)
		echo "<img src='images/user_suit.png' class='bot'> ";
	else
		echo "<img src='images/user_green.png' class='bot'> ";
	echo "<a class='white'>".$lang_label["has_connected"]. '
	[<b>'. $id_usuario. '</b>]</a>';
	echo "<br>";
	echo "<a class='white_bold' href='index.php?bye=bye'><img src='images/lock.png' class='bot'> ". $lang_label["logout"]."</a>";
	echo "</td><td width='25'> </td><td>";
	echo "<a class='white_bold' href='index.php?sec=main'><img src='images/information.png' class='bot'> ". $lang_label["information"]."</a>";
	echo "<br>";	
	echo "<a class='white_bold' href='help/en/toc.php'><img src='images/help.png' class='bot'> ". $lang_label["help"]."</a>";
}
echo "</tr></table>";
echo "</div>";

?>
