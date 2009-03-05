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



error_reporting(E_ALL);

// Local settings for marquee extension

$MAX_MARQUEE_EVENTS=5;
$MARQUEE_INTERVAL=90;
$MARQUEE_FONT_SIZE="32px";
$MARQUEE_SPEED=12;

$output = "";
require_once "../../include/config.php";
require_once "../../include/functions.php";
require_once "../../include/functions_db.php";

$sql = "SELECT evento, timestamp, id_agente FROM tevento ORDER BY utimestamp DESC LIMIT 0 , $MAX_MARQUEE_EVENTS";

$result=mysql_query($sql);
while($row=mysql_fetch_array($result,MYSQL_ASSOC)) {
	$agente = "";
	if ($row["id_agente"] != 0){
		$agente = get_db_sql ("SELECT nombre FROM tagente WHERE id_agente = ". $row["id_agente"]);
		$agente = $agente . " : ";
	}
	$output .= strtoupper($agente) . $row["evento"]. " , ". human_time_comparation($row["timestamp"]);
	$output .= ".&nbsp;&nbsp;&nbsp;&nbsp;.&nbsp;&nbsp;&nbsp;&nbsp;.&nbsp;&nbsp;&nbsp;&nbsp;.&nbsp;&nbsp;&nbsp;&nbsp;.&nbsp;&nbsp;&nbsp;&nbsp;.&nbsp;&nbsp;&nbsp;&nbsp;. ";
}


echo "<html>";
echo "<head>";
echo "<title>Pandora FMS - Latest events </title>";

$query = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == TRUE ? 's': '') . '://' . $_SERVER['SERVER_NAME'];
if ($_SERVER['SERVER_PORT'] != 80)
	$query .= ":" . $_SERVER['SERVER_PORT'];	
$query .= $_SERVER['SCRIPT_NAME'];
echo '<meta http-equiv="refresh" content="' . $MARQUEE_INTERVAL . '; URL=' . $query . '">';
echo '<link rel="icon" href="../../images/pandora.ico" type="image/ico">';
echo "</head>";

echo "<body bgcolor='#000000' >";
echo "<br><br>";
echo "<center>";
echo "<div style='font-size:$MARQUEE_FONT_SIZE; color: #fff'>";
echo "<marquee width=95% scrollamount=$MARQUEE_SPEED>$output</marquee>";
echo "</center>";
echo "</div>";
echo "</body>";

?>
