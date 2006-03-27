<?php
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandora@gmail.com>, 2005-2006

require("include/config.php");
function create_message($usuario_origen, $usuario_destino, $subject, $mensaje){
	$ahora=date("Y/m/d H:i:s");
	require ("include/config.php");
	require ("include/languages/language_".$language_code.".php");
	$sql='INSERT INTO tmensajes (id_usuario_origen, id_usuario_destino, subject, mensaje, timestamp) VALUES ("'.$usuario_origen.'", "'.$usuario_destino.'", "'.$subject.'", "'.$mensaje.'","'.$ahora.'")';
	$result=mysql_query($sql);
	if ($result) echo "<h3 class='suc'>".$lang_label["message_ok"]."</h3>";
	else echo "<h3 class='error'>".$lang_label["message_no"]."</h3>";
	}

function create_message_g($usuario_origen, $usuario_destino, $subject, $mensaje){
	$ahora=date("Y/m/d H:i:s");
	require ("include/config.php");
	require ("include/languages/language_".$language_code.".php");
	$sql='INSERT INTO tmensajes (id_usuario_origen, id_usuario_destino, subject, mensaje, timestamp) VALUES ("'.$usuario_origen.'", "'.$usuario_destino.'", "'.$subject.'", "'.$mensaje.'","'.$ahora.'")';
	$result=mysql_query($sql);
	if ($result) $error=0;
	else $error=1;
	return $error;
	}

//First Queries
$iduser=$_SESSION['id_usuario'];
$sql1='SELECT * FROM tusuario WHERE id_usuario !="'.$iduser.'"';
$resultado=mysql_query($sql1);
$sql2='SELECT COUNT(*) FROM tmensajes WHERE id_usuario_destino="'.$iduser.'" AND estado="FALSE";';
$resultado2=mysql_query($sql2);
$row2=mysql_fetch_array($resultado2);
$sql3='SELECT * FROM tgrupo';
$resultado3=mysql_query($sql3);
	
echo '<h1>'.$lang_label["messages"].'</h1>';
if (isset($_GET["nuevo_mensaje"])){
	// Create message
	$usuario_destino = entrada_limpia($_POST["u_destino"]);
	$subject = entrada_limpia($_POST["subject"]);
	$mensaje = entrada_limpia($_POST["mensaje"]);
	create_message($iduser, $usuario_destino, $subject, $mensaje);
}

if (isset($_GET["nuevo_mensaje_g"])){
	// Create message to groups
	$grupo_destino = entrada_limpia($_POST["g_destino"]);
	$subject = entrada_limpia($_POST["subject"]);
	$mensaje = entrada_limpia($_POST["mensaje"]);
	$sql= 'SELECT id_usuario FROM tusuario_perfil WHERE id_grupo ='. $grupo_destino;
	$result = mysql_query($sql);

	if (mysql_fetch_row($result)){
		while ($row=mysql_fetch_array($result)){
			$error=create_message_g($iduser, $row["id_usuario"], $subject, $mensaje);
		}
		if ($error==0) echo "<h3 class='suc'>".$lang_label["message_ok"]."</h3>";
		else echo "<h3 class='error'>".$lang_label["message_no"]."d</h3>";
	}
	else {echo "<h3 class='error'>".$lang_label["message_no"]."</h3>";}
}
if (isset($_GET["nuevo"]) || isset($_GET["nuevo_g"])){
	if (isset($_GET["nuevo"])){ //create message
	
		echo '<h3>'.$lang_label["new_message"].'</h3>';
		echo '
		<form name="new_mes" method="post" action="index.php?sec=messages&sec2=operation/messages/message&nuevo_mensaje=1">
		<table>
		<tr><td class="datos">'.$lang_label["m_from"].':</td><td><b>'.$iduser.'</b></td></tr>
		<tr><td class="datos">'.$lang_label["m_to"].':</td><td>';
		if (isset($_POST["u_destino"])) {
			echo '<b>'.$_POST["u_destino"].'</b><input type="hidden" name="u_destino" value='.$_POST["u_destino"].'>';
			}
		else{
			echo '<select name="u_destino" class="w100">';
				while ($row=mysql_fetch_array($resultado))
				{echo "<option value='".$row["id_usuario"]."'>".$row["id_usuario"]."</option>";} 
			echo '</select>';
			}
		echo '</td></tr>
		<tr><td class="datos">'.$lang_label["subject"].':</td><td>';
			if (isset($_POST["subject"])) {
			echo '</b><input name="subject" value="'.$_POST["subject"].'" class="w255">';
			}
			else echo '<input name="subject" class="w255">';
		echo '</td></tr>
		<tr><td class="datos">'.$lang_label["message"].':</td></tr>
		<tr><td class="datos" colspan="4"><textarea name="mensaje" rows="10" class= "w540">';
			if (isset($_POST["mensaje"])) {
			echo $_POST["mensaje"];
			}
		echo '</textarea></td></tr>
		<tr><td colspan="2"><div class="noraya"></div></td></tr>
		<tr><td colspan="2" align="right">
		<input type="submit" class="sub" name="send_mes" value="'.$lang_label["send_mes"].'"></form></td></tr>';
	}
	
	if (isset($_GET["nuevo_g"])){
		echo '<h3>'.$lang_label["new_message_g"].'</h3>';
		echo '
		<form name="new_mes" method="post" action="index.php?sec=messages&sec2=operation/messages/message&nuevo_mensaje_g=1">
		<table>
		<tr><td class="datos">'.$lang_label["m_from"].':</td><td><b>'.$iduser.'</b></td></tr>
		<tr><td class="datos">'.$lang_label["m_to"].':</td><td>';
			echo '<select name="g_destino" class="w100">';
				while ($row3=mysql_fetch_array($resultado3))
				if ($row3["id_grupo"] != 1){
					{echo "<option value='".$row3["id_grupo"]."'>".$row3["nombre"]."</option>";}
				}
			echo '</select>';
		echo '</td></tr>
		<tr><td class="datos">'.$lang_label["subject"].':</td><td><input name="subject" class="w255"></td></tr>
		<tr><td class="datos">'.$lang_label["message"].':</td></tr>
		<tr><td class="datos" colspan="4"><textarea name="mensaje" rows="10" class="w540"></textarea></td></tr>
		<tr><td colspan="2" align="right">
		<input type="submit" class="sub" name="send_mes" value="'.$lang_label["send_mes"].'"></form></td></tr>';
	}
}
else {

	if (isset($_GET["borrar"])){
		$id_mensaje = $_GET["id_mensaje"];
		$sql5='DELETE FROM tmensajes WHERE id_usuario_destino="'.$iduser.'" AND id_mensaje="'.$id_mensaje.'"';
		$resultado5=mysql_query($sql5);
		if ($resultado5) {echo "<h3 class='suc'>".$lang_label["del_message_ok"]."</h3>";}
		else {echo "<h3 class='suc'>".$lang_label["del_message_no"]."</h3>";}
	}
	
	//List
	
	echo "<h3>".$lang_label["read_mes"]."</h3>";
	if ($row2["COUNT(*)"]!=0){
		echo $lang_label["new_message_bra"]."<b> ".$row2["COUNT(*)"]."</b> <img src='images/mail.gif'>".$lang_label["new_message_ket"]."<br><br>";
		}
	$sql3='SELECT * FROM tmensajes WHERE id_usuario_destino="'.$iduser.'"';
	$resultado3=mysql_query($sql3);
	if (mysql_num_rows($resultado3)) {
		echo "<table class='w550'><tr><th>".$lang_label["read"]."</th><th>".$lang_label["sender"]."</th><th>".$lang_label["subject"]."</th><th>".$lang_label["timestamp"]."</th><th>".$lang_label["delete"]."</th></tr>";
		while ($row3=mysql_fetch_array($resultado3)){
				echo "<tr>";
				if ($row3["estado"]==1) echo "<td align='center' class='datos'><img src='images/read.gif' border=0></td>";
				else echo "<td align='center' class='datos'><img src='images/unread.gif' border=0></td>";
				echo "<td class='datos'><b><a href=index.php?sec=usuarios&sec2=operation/users/user_edit&ver=".$row3["id_usuario_origen"].">".$row3["id_usuario_origen"]."</b></td><td class='w230d'><a href='index.php?sec=messages&sec2=operation/messages/message&leer=1&id_mensaje=".$row3["id_mensaje"]."'>";
				if ($row3["subject"]) echo $row3["subject"]."</a>";
				else echo "<i>".$lang_label["no_subject"]."</i></a>";
				echo "</a></td><td class='w135d'>".$row3["timestamp"]."</td>";
				echo "<td class='datos' align='center'><a href='index.php?sec=messages&sec2=operation/messages/message&borrar=1&id_mensaje=".$row3["id_mensaje"]."'><img src='images/delete.gif' border='0'></a></td></tr>";
			}
		echo "<tr><td colspan='5'><div class='raya'></div></td></tr>";
	}
	else echo "<div class='red'>".$lang_label["no_messages"]."</div><table>"; //no messages
	
	//read mess
	if (isset($_GET["leer"])){
		$id_mensaje = $_GET["id_mensaje"];
		$sql4='SELECT * FROM tmensajes WHERE id_usuario_destino="'.$iduser.'" AND id_mensaje="'.$id_mensaje.'"';
		$sql41='UPDATE tmensajes SET estado="1" WHERE id_mensaje="'.$id_mensaje.'"';
		$resultado4=mysql_query($sql4);
		$row4=mysql_fetch_array($resultado4);
		$resultado41=mysql_query($sql41);
		echo '
		<table>
		<form method="post" name="reply_mes" action="index.php?sec=messages&sec2=operation/messages/message&nuevo">
		<tr><td></td></tr><tr><td class="w90d">'.$lang_label["from"].':</td><td><b>'.$row4["id_usuario_origen"].'</b></td></tr>
		<tr><td class="datos">'.$lang_label["subject"].':</td><td><b>'.$row4["subject"].'</b></td></tr>
		<tr><td class="datos">'.$lang_label["message"].':</td>
		<tr><td class="datos" colspan="4"><textarea name="mensaje" rows="10" class="w540" readonly>'.$row4["mensaje"].'</textarea></td></tr>
		<tr><td colspan="2" align="right">
		<input type="hidden" name="u_destino" value="'.$row4["id_usuario_origen"].'">
		<input type="hidden" name="subject" value="Re: '.$row4["subject"].'">
		<input type="hidden" name="mensaje" value="'.$row4["id_usuario_origen"].$lang_label["wrote"].': '.$row4["mensaje"].'">
		<input type="submit" class="sub" name="send_mes" value="'.$lang_label["reply"].'">
		</form></td></tr>';
	}
	else echo '<tr><td colspan="5"><div class="noraya"></div></td></tr><tr><td colspan="5" align="right"><form method="post" name="new_mes" action="index.php?sec=messages&sec2=operation/messages/message&nuevo"><input type="submit" class="sub" name="send_mes" value="'.$lang_label["new_message"].'"></form></td></tr>';
}
 echo '</table>';
 ?>