<?php

// Pandora - the Free monitoring system
// ====================================
// Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2006 Artica Soluciones Tecnologicas, info@artica.es
// Copyright (c) 2004-2006 Raul Mateos Martin, raulofpandora@gmail.com
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

	echo "<div class='jus'>";
	$nick = $_SESSION['id_usuario'];
	echo "<h1>" . $lang_label["welcome_title"] . "</h1>";
	echo "<p>";
	echo $lang_label["main_text"];
	echo "</p>";

	echo "<div id='activity'>";
	// Show last activity from this user
	echo "<h2>" . $lang_label["user_last_activity"] . "</h2>";
	// Show table header
	echo '<table cellpadding="3" cellspacing="3" width="700"><tr>'; 
	echo '<th>' . $lang_label["user"] . '</th>';
	echo '<th>' . $lang_label["action"] . '</th>';
	echo '<th class="w130">' . $lang_label["date"] . '</th>';
	echo '<th>' . $lang_label["src_address"] . '</th>';
	echo '<th class="w200">' . $lang_label["comments"] . '</th></tr>';

	// Skip offset records
	$query1="SELECT * FROM tsesion WHERE (TO_DAYS(fecha) > TO_DAYS(NOW()) - 7) AND ID_usuario = '" . $nick . "' ORDER BY fecha DESC limit 15";

	$result = mysql_query ($query1);
	$contador = 5; // Max items
	$color = 1;
	while (($row = mysql_fetch_array ($result)) and ($contador > 0)) {
		
		if ($color == 1){
			$tdcolor = "datos";
			$color = 0;
		} else {
			$tdcolor = "datos2";
			$color = 1;
		}
		
		$usuario = $row["ID_usuario"];
		echo '<tr><td class="' . $tdcolor . '">';
		echo '<b class="' . $tdcolor . 'f9">' . $usuario . '</b>';
		echo '<td class="' . $tdcolor . 'f9">';
		echo $row["accion"];
		echo '<td class="' . $tdcolor . 'f9">';
		echo $row["fecha"];
		echo '<td class="' . $tdcolor . 'f9">';
		echo $row["IP_origen"];
		echo '<td class="' . $tdcolor . 'f9">';
		echo $row["descripcion"];
		echo '</tr>';
		
		$contador--;
	}

	echo "<tr><td colspan='5'><div class='raya'></div>";
	echo "</td></tr></table>";
	echo "</div>"; // activity

	$sql='SELECT COUNT(*) FROM tmensajes WHERE id_usuario_destino="' . $nick . '" AND estado="FALSE";';
	$resultado = mysql_query ($sql);
	$row = mysql_fetch_array ($resultado);
	if ($row["COUNT(*)"] != 0){
		
		echo '<div style="margin-left: 8px">' . $lang_label["new_message_bra"];
		echo '<b><a href="index.php?sec=messages&sec2=operation/messages/message">';
		echo $row["COUNT(*)"] . '</b> <img src="images/mail.gif" border="0"></a>';
		echo $lang_label["new_message_ket"] . '</div>';
	}

	echo '<h2 class="mgb25">' . $lang_label["stat_title"] . '</h2>';

	$query1 = "SELECT COUNT(id_usuario) FROM tusuario";
	$result = mysql_query ($query1);
	$row = mysql_fetch_array ($result);
	echo '<span class="users">';
	echo $lang_label["there_are"] ."<b>". $row[0] . '</b> ' . $lang_label["user_defined"];
	echo '</span>';

	$query1 = "SELECT COUNT(id_agente) FROM tagente";
	$result = mysql_query ($query1);
	$row = mysql_fetch_array ($result);
	echo '<span class="agents">';
	echo $lang_label["there_are"] . "<b>".$row[0]."</b> ". $lang_label["agent_defined"];
	echo '</span>';

	$query1 = "SELECT COUNT(id_agente_datos) FROM tagente_datos";
	$result = mysql_query ($query1);
	$row = mysql_fetch_array ($result);
	echo '<span class="data">';
	echo $lang_label["there_are"] . "<b>".$row[0] . '</b> ' . $lang_label["data_harvested"];
	echo '</span>';

	$query1 = "SELECT COUNT(*) FROM talerta_agente_modulo";
	$result = mysql_query ($query1);
	$row = mysql_fetch_array ($result);
	echo "<span class='alerts'>";
	echo $lang_label["there_are"] . "<b>".$row[0] .'</b> ' . $lang_label["alert_defined"];
	echo "</span>";
	
	echo '<span class="time">';
	$query1 = "SELECT timestamp FROM tagente_estado ORDER BY timestamp DESC LIMIT 1";
	$result = mysql_query($query1);
	if ($row = mysql_fetch_array($result)) {	// Take the first element only
		echo $lang_label["data_timestamp"] . "<b>".$row["timestamp"]."</b>";
	} else {
		echo 'No data received yet!';
	}
	echo '</span>';
	
	echo '</div>'; // class "jus"
?>