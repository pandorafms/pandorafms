<?php
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandora@gmail.com>, 2005-2006

// Load global vars
require("include/config.php");
//require("include/functions.php");
//require("include/functions_db.php");
if (comprueba_login() == 0) {
	
	$view_mode = 0;
	$id_usuario = $_SESSION["id_usuario"];
	
	if (isset ($_GET["ver"])){ // Only view mode, 
		$id = $_GET["ver"]; // ID given as parameter
		if ($id_usuario == $id)
			$view_mode = 0;
		else
			$view_mode = 1;
	}

	$query1="SELECT * FROM tusuario WHERE id_usuario = '".$id."'";
	$resq1=mysql_query($query1);
	$rowdup=mysql_fetch_array($resq1);
	$nombre=$rowdup["id_usuario"];
	
	// Obtenemos el ID del usuario para modificar los datos del usuario actual
	// no podemos pasar el ID como parámetro, sino seria muy facil acceder 
	// a los datos de otro usuario

	if (isset ($_GET["modificado"])){
		// Se realiza la modificación
		if (isset ($_POST["pass1"])){
			if ( $_POST["nombre"] != $_SESSION["id_usuario"]) {
				audit_db($_SESSION["id_usuario"],$REMOTE_ADDR,"Security Alert. Trying to modify another user: (".$_POST['nombre'].") ","Security Alert");
				no_permission;
			}
				
			// $nombre = $_POST["nombre"]; // Don't allow change name !!
			$pass1 = entrada_limpia($_POST["pass1"]);
			$pass2 = entrada_limpia($_POST["pass2"]);
			$direccion = entrada_limpia($_POST["direccion"]);
			$telefono = entrada_limpia($_POST["telefono"]);
			$nombre_real = entrada_limpia($_POST["nombre_real"]);
			if ($pass1 != $pass2) {
				echo "<h3 class='error'>".$lang_label["pass_nomatch"]."</h3>";
			}
			else {echo "<h3 class='suc'>".$lang_label["update_user_ok"]."</h3>";}
			//echo "<br>DEBUG para ".$nombre;
			//echo "<br>Comentarios:".$comentarios;	
			$comentarios = entrada_limpia($_POST["comentarios"]);
			if (dame_password($nombre)!=$pass1){
				// Only when change password
				$pass1=md5($pass1);
				$sql = "UPDATE tusuario SET nombre_real = '".$nombre_real."', password = '".$pass1."', telefono ='".$telefono."', direccion ='".$direccion." ', comentarios = '".$comentarios."' WHERE id_usuario = '".$nombre."'";
			}
			else 
				$sql = "UPDATE tusuario SET nombre_real = '".$nombre_real."', telefono ='".$telefono."', direccion ='".$direccion." ', comentarios = '".$comentarios."' WHERE id_usuario = '".$nombre."'";
			$resq2=mysql_query($sql);
			
			// Ahora volvemos a leer el registro para mostrar la info modificada
			// $id is well known yet
			$query1="SELECT * FROM tusuario WHERE id_usuario = '".$id."'";
			$resq1=mysql_query($query1);
			$rowdup=mysql_fetch_array($resq1);
			$nombre=$rowdup["id_usuario"];			
		}
		else {
			echo "<h3 class='error'>".$lang_label["pass_nomatch"]."</h3>";
		}
	} 
		echo "<h2>".$lang_label["users_"]."</h2>";
		echo "<h3>".$lang_label["user_edit_title"]."</h3>";

	// Sino se obtiene la variable "modificado" es que se esta visualizando la informacion y
	// preparandola para su modificacion, no se almacenan los datos
	
	$nombre=$rowdup["id_usuario"];
	if ($view_mode == 0)
		$password=$rowdup["password"];
	else 	
		$password="This is not good idea :-)";
	
	$comentarios=$rowdup["comentarios"];
	$direccion=$rowdup["direccion"];
	$telefono=$rowdup["telefono"];
	$nombre_real=$rowdup["nombre_real"];

	?>
	<table cellpadding="3" cellspacing="3" class="fon">
	<?php 
	if ($view_mode == 0) 
		echo '<form name="user_mod" method="post" action="index.php?sec=usuarios&sec2=operation/users/user_edit&ver='.$id_usuario.'&modificado=1">';
	else 	
		echo '<form name="user_mod" method="post" action="">';
	?>
	<tr><td class="datos"><?php echo $lang_label["id_user"] ?>
	<td class="datos"><input class=input type="text" name="nombre" value="<?php echo $nombre ?>" disabled>
	<tr><td class="datos"><?php echo $lang_label["real_name"] ?>
	<td class="datos"><input class=input type="text" name="nombre_real" value="<?php echo $nombre_real ?>">
	<tr><td class="datos"><?php echo $lang_label["password"] ?>
	<td class="datos"><input class=input type="password" name="pass1" value="<?php echo $password ?>">
	<tr><td class="datos"><?php echo $lang_label["password"]; echo " ".$lang_label["confirmation"]?>
	<td class="datos"><input class=input type="password" name="pass2" value="<?php echo $password ?>">
	<tr><td class="datos">E-Mail
	<td class="datos"><input class=input type="text" name="direccion" size="40" value="<?php echo $direccion ?>">
	<tr><td class="datos"><?php echo $lang_label["telefono"] ?>
	<td class="datos"><input class=input type="text" name="telefono" value="<?php echo $telefono ?>">
	<tr><td class="datos" colspan="2"><?php echo $lang_label["comments"] ?>
	<tr><td class="datos" colspan="2"><textarea name="comentarios" cols=50 rows=4><?php echo $comentarios ?></textarea>
	
<?php
		// Don't delete this!!
	if ($view_mode ==0){
		echo '<tr><td colspan="2" align="right">';
		echo "<input name='uptbutton' type='submit' class='sub' value='".$lang_label["update"]."'>";
	}
	echo '<tr><td></td></tr>';
	
	$sql1='SELECT * FROM tusuario_perfil WHERE id_usuario = "'.$nombre.'"';
	$result=mysql_query($sql1);
	if (mysql_num_rows($result)){
		echo '<tr><td colspan="2"><h3>'.$lang_label["listGroupUser"].'</h3></td>';
		while ($row=mysql_fetch_array($result)){
			echo '<tr><td colspan="2">';
			echo "&nbsp;&nbsp;&nbsp;";
			echo "<b>".dame_perfil($row["id_perfil"])."</b> / ";
			echo "<b>".dame_grupo($row["id_grupo"])."</b>";	
		}

	}
	else { echo '<tr><td class="red" colspan="2">'.$lang_label["no_profile"]; }

	echo '</form></td></tr></table> ';

} // fin pagina

?>