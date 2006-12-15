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
echo $lang_label["main_text"];
echo "<br /><br />";
echo $lang_label["has_connected"] . " <b>" . $nick . "</b> - ";

if (dame_admin ($nick) == 1) {
	echo $lang_label["administrator"] . '. ';
} else {
	echo $lang_label["normal_user"] . '. ';
}

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
		echo '"<b class="' . $tdcolor . 'f9">' . $usuario . '</b>';
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

	echo "<tr><td colspan='5'><div class='raya'></div></td></tr></table></div>";

	$sql='SELECT COUNT(*) FROM tmensajes WHERE id_usuario_destino="' . $nick . '" AND estado="FALSE";';
	$resultado = mysql_query ($sql);
	$row = mysql_fetch_array ($resultado);
	if ($row["COUNT(*)"] != 0){
		
		echo '<div style="margin-left: 8px">' . $lang_label["new_message_bra"];
		echo '<b><a href="index.php?sec=messages&sec2=operation/messages/message">';
		echo $row["COUNT(*)"] . '</b> <img src="images/mail.gif" border="0"></a>';
		echo $lang_label["new_message_ket"] . '</div>';
	}

	echo '<h2>' . $lang_label["stat_title"] . '</h2>';

	$query1 = "SELECT COUNT(*) FROM tusuario";
	$result = mysql_query ($query1);
	$row = mysql_fetch_array ($result);
	echo '<img src="images/usuarios.gif" align="middle" alt="">&nbsp;&nbsp;';
	echo $lang_label["there_are"] . $row[0] . ' ' . $lang_label["user_defined"];
	echo '<br /><br />';

	$query1 = "SELECT COUNT(*) FROM tagente";
	$result = mysql_query ($query1);
	$row = mysql_fetch_array ($result);
	echo '<img src="images/agentes.gif" align="middle" alt="">&nbsp;&nbsp;';
	echo $lang_label["there_are"] . $row[0]  .' ' . $lang_label["agent_defined"];
	echo '<br /><br />';

	$query1 = "SELECT COUNT(id_agente_datos) FROM tagente_datos";
	$result = mysql_query ($query1);
	$row = mysql_fetch_array ($result);
	echo '<img src="images/datos.gif" align="middle" alt="">&nbsp;&nbsp;';
	echo $lang_label["there_are"] . $row[0] . ' ' . $lang_label["data_harvested"];
	echo '<br /><br />';

	$query1 = "SELECT COUNT(*) FROM talerta_agente_modulo";
	$result = mysql_query ($query1);
	$row = mysql_fetch_array ($result);
	echo '<img src="images/alertas.gif" align="middle" alt="">&nbsp;&nbsp;';
	echo $lang_label["there_are"] . $row[0] .' ' . $lang_label["alert_defined"];
	echo '<br /><br />';

	$query1 = "SELECT * FROM tagente_estado ORDER BY timestamp DESC";
	$result = mysql_query($query1);
	$row = mysql_fetch_array($result);
	// Take the first element only
	echo '<img src="images/time.gif" align="middle" alt="">&nbsp;&nbsp;';
	echo $lang_label["data_timestamp"] . $row["timestamp"];
	echo '</div>';
?>
