<?php
// Pandora - the Free monitoring system
// ====================================
// Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2006 Artica Soluciones Tecnologicas S.L, info@artica.es
// Copyright (c) 2004-2006 Raul Mateos Martin, raulofpandora@gmail.com
// Copyright (c) 2008-2008 Evi Vanoost, vanooste@rcbi.rochester.edu
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

// Load global vars
require_once ("include/config.php");

function create_message ($usuario_origen, $usuario_destino, $subject, $mensaje) {
	$sql = sprintf ("INSERT INTO tmensajes (id_usuario_origen, id_usuario_destino, subject, mensaje, timestamp)
	VALUES ('%s', '%s', '%s', '%s',NOW())",$usuario_origen,$usuario_destino,$subject,$mensaje);
	(int) $result = process_sql ($sql);
	if ($result == 1) {
		echo '<h3 class="suc">'.__('Message successfully sent').'</h3>';
	} else {
		echo '<h3 class="error">'.__('There was a problem sending message').' - Dest: '.$usuario_destino.'</h3>';
	}
}

//First Queries
$iduser = $_SESSION['id_usuario'];

if (isset ($_GET["nuevo_mensaje"])){
	// Create message
	$usuario_destino = get_parameter ("u_destino");
	$subject = get_parameter ("subject");
	$mensaje = get_parameter ("mensaje");
	create_message ($iduser, $usuario_destino, $subject, $mensaje);
}

if (isset ($_GET["nuevo_mensaje_g"])){
	// Create message to groups
	$dest_group = get_parameter ("g_destino");
	$subject = get_parameter ("subject");
	$message = get_parameter ("mensaje");
	$sql = sprintf ("SELECT id_usuario FROM tusuario_perfil WHERE id_grupo ='%d'",$dest_group);
	$result = get_db_all_rows_sql ($sql);
	if ($result === false) {
		echo "<h3 class='error'>".__('There was a problem sending message')."</h3>";
	} else {
		foreach ($result as $row) {
			create_message ($iduser, $row["id_usuario"], $subject, $message);
		}
	}
}
echo "<h2>".__('Messages')." - ";

if (isset ($_GET["nuevo"])) { //create message
	echo __('New message').'</h2>';
	echo '<form name="new_mes" method="POST" action="index.php?sec=messages&sec2=operation/messages/message&nuevo_mensaje=1">
	<table width="600" class="databox_color" cellpadding="4" cellspacing="4"><tr>
	<td class="datos">'.__('From').':</td>
	<td class="datos"><strong>'.$iduser.'</strong></td>
	</tr><tr>
	<td class="datos2">'.__('To').':</td>
	<td class="datos2">';
	if (isset ($_POST["u_destino"])) {
		echo '<b>'.$_POST["u_destino"].'</b><input type="hidden" name="u_destino" value='.$_POST["u_destino"].'>';
	} else {
		echo '<select name="u_destino" width="120">';
		$groups = get_user_groups ($iduser);
		foreach ($groups as $id => $group) {
			if (!isset ($group_id)) {
				$group_id = "id_grupo = ".$id;
			} else { 
				$group_id .= " OR id_grupo = ".$id;
			}
		}
		$sql = sprintf ("SELECT DISTINCT(id_usuario) FROM tusuario_perfil WHERE %s",$group_id);
		$result = get_db_all_rows_sql ($sql);
		foreach ($result as $row) {
			echo '<option value="'.$row["id_usuario"].'">'.$row["id_usuario"].'</option>';
		}
		echo '</select>';
	}
	echo '</td></tr><tr><td class="datos">'.__('Subject').':</td><td class="datos">';
		if (isset ($_POST["subject"])) {
			echo '<input name="subject" value="'.get_parameter_post ("subject").'" size=70>';
		} else { 
			echo '<input name="subject" size=60>';
		}
	echo '</td></tr><tr><td class="datos2">'.__('Message').':</td>
	<td class="datos"><textarea name="mensaje" rows="15" cols="70">';
		if (isset ($_POST["mensaje"])) {
			echo get_parameter_post ("mensaje");
		}
	echo '</textarea></td></tr><tr><td></td><td colspan="3">
	<input type="submit" class="sub wand" name="send_mes" value="'.__('Send message').'"></form></td></tr></table>';
} elseif (isset ($_GET["nuevo_g"])) {
	echo __('New message').'</h2>';
	echo '<form name="new_mes" method="post" action="index.php?sec=messages&sec2=operation/messages/message&nuevo_mensaje_g=1">
	<table width=600 class="databox_color" cellpadding=4 cellspacing=4>
	<tr><td class="datos">'.__('From').':</td>
	<td class="datos"><strong>'.$iduser.'</strong></td></tr>
	<tr><td class="datos2">'.__('To').':</td><td class="datos2">';
	echo '<select name="g_destino" class="w130">';
	$groups = get_user_groups ($iduser);
        foreach ($groups as $id => $group) {
		if(!isset ($group_id)) {
			$group_id = "id_grupo = ".$id;
		} else {
			$group_id .= " OR id_grupo = ".$id;
		}
	}
	// This query makes that we can send messages to groups we have access
	// to, not only the ones we belong to																										
	$sql = sprintf ("SELECT DISTINCT(id_grupo) FROM tusuario_perfil WHERE %s",$group_id);	
	$result = get_db_all_rows_sql ($sql);
	foreach ($result as $row) {
		echo '<option value="'.$row["id_grupo"].'">'.dame_nombre_grupo($row["id_grupo"]).'</option>';
	}
	echo '</select></td></tr>
	<tr><td class="datos">'.__('Subject').':</td><td class="datos"><input name="subject" size="60"></td></tr><tr>
	<td class="datos2">'.__('Message').':</td>
	<td class="datos"><textarea name="mensaje" rows="12" cols="60"></textarea></td>
	</tr><tr><td></td><td colspan="3">
	<input type="submit" class="sub wand" name="send_mes" value="'.__('Send message').'"></form></td></tr></table>';
} elseif (isset($_GET["leer"])) {

	$id_mensaje = get_parameter_get("id_mensaje");
	$sql = sprintf("SELECT id_usuario_origen, subject, mensaje FROM tmensajes WHERE id_usuario_destino='%s' AND id_mensaje=%d" , $iduser, $id_mensaje);
    $row = get_db_row_sql ($sql);
	process_sql ("UPDATE tmensajes SET estado=1 WHERE id_mensaje = ".$id_mensaje);

	echo '<table class="databox_color" width=650 cellpadding=4 cellspacing=4>
	<form method="post" name="reply_mes" action="index.php?sec=messages&sec2=operation/messages/message&nuevo">
	<tr><td class="datos">'.__('From').':</td>
	<td class="datos"><b>'.$row["id_usuario_origen"].'</b></td></tr>';
	
	// Subject
	echo '<tr><td class="datos2">'.__('Subject').':</td>
	<td class="datos2" valign="top"><b>'.$row["subject"].'</b></td></tr>';
	
	// text
	echo '<tr><td class="datos" valign="top">'.__('Message').':</td>
	<td class="datos"><textarea name="mensaje" rows="15" cols=70 readonly>'.$row["mensaje"].'</textarea></td></tr>
	</table>
        <input type="hidden" name="u_destino" value="'.$row["id_usuario_origen"].'">
        <input type="hidden" name="subject" value="Re: '.$row["subject"].'">
        <input type="hidden" name="mensaje" value="'.$row["id_usuario_origen"].__(' wrote').': '.$row["mensaje"].'">';
	echo '<table width=650 cellpadding=4 cellspacing=4>';
	echo "<tr><td align=right>";
	echo '<input type="submit" class="sub next" name="send_mes" value="'.__('Reply').'">';
	echo '</form>';
	echo "</td></tr></table>";
} 
if (isset ($_GET["leer"]) || (!isset ($_GET["nuevo"]) && !isset ($_GET["nuevo_g"]))) {	
	echo __('Read messages')."</h2>";

	//Delete messages if borrar is set
	if (isset ($_GET["borrar"])){
		$id_message = get_parameter_get ("id_mensaje");
		$sql = sprintf ("DELETE FROM tmensajes WHERE id_usuario_destino='%s' AND id_mensaje=%d",$iduser,$id_message);
		(int) $result = process_sql ($sql);
		if ($result > 0) {
			echo '<h3 class="suc">'.__('Message sucessfully deleted').'</h3>';
		} else {
			echo '<h3 class="error">'.__('There was a problem deleting message').'</h3>';
		}
	}
	
	//Get number of messages
	$sql = sprintf("SELECT COUNT(id_mensaje) FROM tmensajes WHERE id_usuario_destino='%s' AND estado=0",$iduser);
	$num_messages = get_db_sql ($sql);
				
	if ($num_messages > 0){
		echo '<p>'.__('You have ').' <b>'.$num_messages.'</b> <img src="images/email.png">'.__(' unread message(s).').'</p>';
	}
	$sql = sprintf ("SELECT id_mensaje, id_usuario_origen, subject, timestamp, estado FROM tmensajes WHERE id_usuario_destino='%s' ORDER BY `timestamp` DESC",$iduser);
	$result = get_db_all_rows_sql ($sql);
	if ($result === false) {
		echo "<div class='nf'>".__('There are no messages')."</div>";
	} else {
		$color = 1;
		echo '<table width="650" class="databox" cellpadding="4" cellspacing="4"><tr>
		<th>'.__('Read').'</th>
		<th>'.__('Sender').'</th>
		<th>'.__('Subject').'</th>
		<th>'.__('Timestamp').'</th>
		<th>'.__('Delete').'</th></tr>';
															
		foreach ($result as $row) {
			if ($color == 1){
				$tdcolor = "datos";
				$color = 0;
			} else {
				$tdcolor = "datos2";
				$color = 1;
			}
			echo '<tr><td align="center" class="'.$tdcolor.'">';
			echo '<a href="index.php?sec=messages&sec2=operation/messages/message&leer=1&id_mensaje='.$row["id_mensaje"].'">';
			if ($row["estado"]==1) {
				$img = "email_open.png";
			} else {
				$img = "email.png";
			}
			echo '<img src="images/'.$img.'" border="0"></a></td>';
			echo '<td class="'.$tdcolor.'">'. $row["id_usuario_origen"].'</td>';
			echo '<td class="'.$tdcolor.'"><a href="index.php?sec=messages&sec2=operation/messages/message&leer=1&id_mensaje='.$row["id_mensaje"].'"><b>';
			if ($row["subject"]) {
				echo $row["subject"];
			} else {
				echo __('No subject');
			}
			echo '</b></a></td><td class="'.$tdcolor.'">'.format_datetime(strtotime($row["timestamp"])).'</td>
			<td class="'.$tdcolor.'" align="center"><a href="index.php?sec=messages&sec2=operation/messages/message&borrar=1&id_mensaje='.$row["id_mensaje"].'">
			<img src="images/cross.png" border="0"></a></td></tr>';
		}
		echo "</table>";
	}
	echo '<div class="action-buttons" style="width: 650px">';
	echo '<form method="post" name="new_mes" action="index.php?sec=messages&sec2=operation/messages/message&nuevo">
	<input type="submit" class="sub next" name="send_mes" value="'.__('New message').'"></form>';
	echo "</div>";
}
?>
