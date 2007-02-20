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
// Load global vars
require("include/config.php");

if (comprueba_login() == 0) {

	$id_inc = $_GET["id_inc"];
	$ahora=date("Y/m/d H:i:s");

	// Create Note
	echo "<h2>".$lang_label["incident_manag"]."</h2>";
	echo "<h3>".$lang_label["note_title"]." #".$id_inc."<a href='help/".$help_code."/chap3.php#331' target='_help' class='help'>&nbsp;<span>".$lang_label["help"]."</span></a></h3>";
	echo "<table cellpadding=3 cellspacing=3 border=0><form name='nota' method='post' action='index.php?sec=incidencias&sec2=operation/incidents/incident_detail&insertar_nota=1&id=".$id_inc."'>";
	echo "<tr><td class='lb' rowspan='2' width='5'><td class='datos'><b>".$lang_label["date"]."</b>";
	echo "<td class='datos'>".$ahora;
	echo "<input type='hidden' name='timestamp' value='".$ahora."'>";
	echo "<input type='hidden' name='id_inc' value='".$id_inc."'>";
	echo '<tr><td colspan="3" class="datos2"><textarea name="nota" rows="20" cols="85">';
	echo '</textarea>';
	echo '<tr><td colspan="3"><div class="raya"></div></td></tr>';
	echo '<tr><td colspan="3" align="right"><input name="addnote" type="submit" class="sub" value="'.$lang_label["add"].'">';
	echo '</table>';

} // end page