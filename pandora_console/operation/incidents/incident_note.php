<?php
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2005

// Cargamos variables globales
require("include/config.php");
//require("include/functions.php");
//require("include/functions_db.php");
if (comprueba_login() == 0) {

	$id_inc = $_GET["id_inc"];
	$ahora=date("Y/m/d H:i:s");

	// Crear Nota
	echo "<h2>".$lang_label["incident_manag"]."</h2>";
	echo "<h3>".$lang_label["note_title"]." #".$id_inc." </h3>";
	echo "<table cellpadding=3 cellspacing=3 border=0><form name='nota' method='post' action='index.php?sec=incidencias&sec2=operation/incidents/incident_detail&insertar_nota=1&id=".$id_inc."'>";
	echo "<tr><td class='datos'><b>".$lang_label["date"]."</b>";
	echo "<td class='datos'>".$ahora;
	echo "<input type='hidden' name='timestamp' value='".$ahora."'>";
	echo "<input type='hidden' name='id_inc' value='".$id_inc."'>";
	echo '<tr><td colspan=2 class="datos"><textarea name="nota" rows="20" cols="85">';
	echo '</textarea>';
	echo '<tr><td colspan=2 class="datos" align="right"><input name="addnote" type="submit" class="sub" value="'.$lang_label["add"].'">';
	echo '</table>';


} // fin pagina
