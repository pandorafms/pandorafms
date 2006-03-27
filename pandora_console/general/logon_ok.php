<?php
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandora@gmail.com>, 2004-2006

echo "<div class='jus'>";
$nick = $_SESSION['id_usuario'];
echo "<h1>".$lang_label["welcome_title"]."</h1>";
echo $lang_label["main_text"];
echo "<br><br>";
echo $lang_label["has_connected"]." <b>".$nick."</b> - ";
if (dame_admin($nick)==1){	
	echo $lang_label["administrator"].'. ';
}
else {
	echo $lang_label["normal_user"].'. ';
}

echo "<div id='activity'>";
// Show last activity from this user
echo "<h2>".$lang_label["user_last_activity"]."</h2>";
// Show table header
echo '<table cellpadding="3" cellspacing="3" width="700"><tr>'; 
echo '<th>'.$lang_label["user"].'</th>';
echo '<th>'.$lang_label["action"].'</th>';
echo '<th class="w130">'.$lang_label["date"].'</th>';
echo '<th>'.$lang_label["src_address"].'</th>';
echo '<th class="w200">'.$lang_label["comments"].'</th></tr>';

// Skip offset records
$query1="SELECT * FROM tsesion WHERE (TO_DAYS(fecha) > TO_DAYS(NOW()) -7) AND ID_usuario = '".$nick."' ORDER BY fecha DESC limit 15"; 

	$result=mysql_query($query1);
	$contador = 5; // Max items
	while (($row=mysql_fetch_array($result)) and ($contador > 0))
	{
	$usuario=$row["ID_usuario"];
		echo '<tr><td class="datos"><b class="f9">'.$usuario."</b>";
		echo '<td class="datosf9">';
		echo $row["accion"];
		echo '<td class="datosf9">';
		echo $row["fecha"];
		echo '<td class="datosf9">';
		echo $row["IP_origen"];
		echo '<td class="datosf9">';
		echo $row["descripcion"];
		echo '</tr>';
		$contador--;
	}

	echo "<tr><td colspan='5'><div class='raya'></div></td></tr></table></div>";

	$sql='SELECT COUNT(*) FROM tmensajes WHERE id_usuario_destino="'.$nick.'" AND estado="FALSE";';
	$resultado=mysql_query($sql);
	$row=mysql_fetch_array($resultado);
	if ($row["COUNT(*)"]!=0){
		echo "<div style='margin-left: 8px'>".$lang_label["new_message_bra"]."<b><a href='index.php?sec=messages&sec2=operation/messages/message'>". $row["COUNT(*)"] . "</b> <img src='images/mail.gif' border='0'></a>".$lang_label["new_message_ket"]."</div>";
			}

	echo "<h2>".$lang_label["stat_title"]."</h2>";

	$query1="SELECT COUNT(*) FROM tusuario";
	$result=mysql_query($query1);
	$row=mysql_fetch_array($result);
	echo "<img src='images/usuarios.gif' align='middle' alt=''>&nbsp;&nbsp;";
	echo $lang_label["there_are"].$row[0]." ".$lang_label["user_defined"];
	echo "<br><br>";

	$query1="SELECT COUNT(*) FROM tagente";
	$result=mysql_query($query1);
	$row=mysql_fetch_array($result);
	echo "<img src='images/agentes.gif' align='middle' alt=''>&nbsp;&nbsp;";
	echo $lang_label["there_are"].$row[0]." ".$lang_label["agent_defined"];
	echo "<br><br>";

	$query1="SELECT COUNT(*) FROM tagente_datos";
	$result=mysql_query($query1);
	$row=mysql_fetch_array($result);
	echo "<img src='images/datos.gif' align='middle' alt=''>&nbsp;&nbsp;";
	echo $lang_label["there_are"].$row[0]." ".$lang_label["data_harvested"];
	echo "<br><br>";

	$query1="SELECT COUNT(*) FROM talerta_agente_modulo";
	$result=mysql_query($query1);
	$row=mysql_fetch_array($result);
	echo "<img src='images/alertas.gif' align='middle' alt=''>&nbsp;&nbsp;";
	echo $lang_label["there_are"].$row[0]." ".$lang_label["alert_defined"];
	echo "<br><br>";

	$query1="SELECT * FROM tagente_estado ORDER BY timestamp DESC";
	$result=mysql_query($query1);
	$row=mysql_fetch_array($result);
	// Take the first element only
	echo "<img src='images/time.gif' align='middle' alt=''>&nbsp;&nbsp;";
	echo $lang_label["data_timestamp"].$row["timestamp"];
	echo "</div>";
?>