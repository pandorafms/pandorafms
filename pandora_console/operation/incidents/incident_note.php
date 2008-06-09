<?php

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2008 Sancho Lerena, <slerena@gmail.com>
// Copyright (c) 2005-2008 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Load global vars
require("include/config.php");

if (comprueba_login() == 0) {

	$id_inc = $_GET["id_inc"];
	$now=date("Y/m/d H:i:s");

	// Create Note
	echo "<h2>".$lang_label["incident_manag"]." &gt; ";
	echo $lang_label["note_title"]." #".$id_inc."</h2>";
	echo "<table cellpadding='4' cellspacing='4' class='databox' width='550px'>
	<form name='nota' method='post' action='index.php?sec=incidencias&sec2=operation/incidents/incident_detail&insertar_nota=1&id=".$id_inc."'>";
	echo "<tr><td class='datos'><b>".$lang_label["date"]."</b>";
	echo "<td class='datos'>".$now."</td>";
	echo "<input type='hidden' name='timestamp' value='".$now."'>";
	echo "<input type='hidden' name='id_inc' value='".$id_inc."'>";
	echo '<tr><td colspan="3" class="datos2"><textarea name="nota" rows="20" cols="80" style="height: 300px;">';
	echo '</textarea>';
	echo '</td></tr>';
	echo '</table><table width="550">';
	echo '<tr><td align="right">
	<input name="addnote" type="submit" class="sub wand" value="'.$lang_label["add"].'">';
	echo '</table>';

} // end page