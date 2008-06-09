<?php
// Pandora - the Free monitoring system
// ====================================
// Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2006 Artica Soluciones Tecnologicas S.L, info@artica.es
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

// Load global vars
require("include/config.php");

if (comprueba_login() == 0) 
	if (give_acl($id_user, 0, "UM")==1) {
	// Init. vars
	$comentarios = "";
	$direccion = "";
	$telefono = "";
	$password = "";
    $password2 = "";
	$id_usuario_mio = "";
	$nombre_real = "";
	$nivel = 0;
	// Default is create mode (creacion)
	$modo = "creacion";
	
	if (isset($_GET["borrar_grupo"])){ // if modified some parameter
		$grupo= entrada_limpia($_GET["borrar_grupo"]);
		$query_del2="DELETE FROM tusuario_perfil WHERE id_up = ".$grupo;
		$resq1=mysql_query($query_del2);
	}
		
	if (isset($_GET["id_usuario_mio"])){ // if any parameter changed
		$modo="edicion";
		$id_usuario_mio = entrada_limpia($_GET["id_usuario_mio"]);
		// Read user data to include in form
		$query1="SELECT * FROM tusuario WHERE id_usuario = '".$id_usuario_mio."'";
		$resq1=mysql_query($query1);
		$rowdup=mysql_fetch_array($resq1);
		if (!$rowdup)
			{
			echo "<h3 class='error'>".$lang_label["user_error"]."</h3>";
			echo "</table>";
			include ("general/footer.php");
			exit;
			}
		else
		$password="";
        $password2="";
		$comentarios=$rowdup["comentarios"];
		$direccion=$rowdup["direccion"];
		$telefono=$rowdup["telefono"]; 
		$nivel =$rowdup["nivel"]; 
		$nombre_real=$rowdup["nombre_real"];
	}
	
	// Edit user
	if (isset ($_POST["edicion"])){		
		// We do it
		if (isset ($_POST["pass1"])){
			$nombre = entrada_limpia($_POST["nombre"]);
			$nombre_real=entrada_limpia($_POST["nombre_real"]);
			$nombre_viejo = entrada_limpia($_POST["id_usuario_antiguo"]);
			$password = entrada_limpia($_POST["pass1"]);
			$password2 = entrada_limpia($_POST["pass2"]);	
			if ($password <> $password2){
				echo "<h3 class='error'>".$lang_label["pass_nomatch"]."</h3>";
			}
			else {
			if (isset($_POST["nivel"]))
			$nivel = entrada_limpia($_POST["nivel"]);
			$direccion = entrada_limpia($_POST["direccion"]);
			$telefono = entrada_limpia($_POST["telefono"]);
			$comentarios = entrada_limpia($_POST["comentarios"]);
			
            if ($password != ""){
				$password=md5($password);
				$sql = "UPDATE tusuario SET nombre_real ='".$nombre_real."', id_usuario ='".$nombre."', password = '".$password."', telefono ='".$telefono."', direccion ='".$direccion." ', nivel = '".$nivel."', comentarios = '".$comentarios."' WHERE id_usuario = '".$nombre_viejo."'";
			}
			else 	
				$sql = "UPDATE tusuario SET nombre_real ='".$nombre_real."', id_usuario ='".$nombre."', telefono ='".$telefono."', direccion ='".$direccion." ', nivel = '".$nivel."', comentarios = '".$comentarios."' WHERE id_usuario = '".$nombre_viejo."'";
			$resq2=mysql_query($sql);

			// Add group
			if (isset($_POST["grupo"]))
				if ($_POST["grupo"] <> ""){
					$grupo = $_POST["grupo"];
					$perfil = $_POST["perfil"];
					$id_usuario_edit = $_SESSION["id_usuario"];
					$sql = "INSERT INTO tusuario_perfil (id_usuario,id_perfil,id_grupo,assigned_by) VALUES ('".$nombre."',$perfil,$grupo,'".$id_usuario_edit."')";
					// echo "DEBUG:".$sql;
					$resq2=mysql_query($sql);
				}
			
			$query1="SELECT * FROM tusuario WHERE id_usuario = '".$nombre."'";
			$id_usuario_mio = $nombre;
			$resq1=mysql_query($query1);
			$rowdup=mysql_fetch_array($resq1);
			$password="";
            $password2= "";
			$comentarios=$rowdup["comentarios"];
			$direccion=$rowdup["direccion"];
			$telefono=$rowdup["telefono"]; 
			$nivel = $rowdup["nivel"];
			$nombre_real=$rowdup["nombre_real"];
			$modo ="edicion";
			echo "<h3 class='suc'>".$lang_label["update_user_ok"]."</h3>";
		}
		}
		else {
			echo "<h3 class='error'>".$lang_label["update_user_no"]."</h3>";
		}
	} 

	// Create user
	if (isset($_GET["nuevo_usuario"])){
		// Get data from POST
		$nombre = entrada_limpia($_POST["nombre"]);
		$password = entrada_limpia($_POST["pass1"]);
		$password2 = entrada_limpia($_POST["pass2"]);
		$nombre_real=entrada_limpia($_POST["nombre_real"]);
		if ($password <> $password2){
			echo "<h3 class='error'>".$lang_label["pass_nomatch"]."</h3>";
		}
		$direccion = entrada_limpia($_POST["direccion"]);
		$telefono = entrada_limpia($_POST["telefono"]);
		$comentarios = entrada_limpia($_POST["comentarios"]);
		if (isset($_POST["nivel"]))
			$nivel = entrada_limpia($_POST["nivel"]);
		$password=md5($password);
		$ahora = date("Y/m/d H:i:s");
		$sql_insert = "INSERT INTO tusuario (id_usuario,direccion,password,telefono,fecha_registro,nivel,comentarios, nombre_real) VALUES ('".$nombre."','".$direccion."','".$password."','".$telefono."','".$ahora."','".$nivel."','".$comentarios."','".$nombre_real."')";
		$resq1=mysql_query($sql_insert);
			if (! $resq1)
				echo "<h3 class='error'>".$lang_label["create_user_no"]."</h3>";
			else {
				echo "<h3 class='suc'>".$lang_label["create_user_ok"]."</h3>";
			}
		$id_usuario_mio = $nombre;
		$modo ="edicion";
        $password = "";
        $password2 = "";
	}
		echo "<h2>".$lang_label["user_management"]." &gt; ";
		if (isset($_GET["alta"])){
			if ($_GET["alta"]==1){
				echo $lang_label["create_user"];
			}
		}
		if (isset($_GET["id_usuario_mio"]) OR isset($_GET["nuevo_usuario"])){
			echo $lang_label["update_user"];
		}
	echo "</h2>";

?> 
	<table width='500' cellpadding='4' cellspacing='4' class='databox_color'>
	<?php 
	if (isset($_GET["alta"]))
		echo '<form name="new_user" method="post" action="index.php?sec=gusuarios&sec2=godmode/users/configure_user&nuevo_usuario=1">';
	else
		echo '<form name="user_mod" method="post" action="index.php?sec=gusuarios&sec2=godmode/users/configure_user&id_usuario_mio='.$id_usuario_mio.'">';
	?>
	<tr>
	<td class="datos"><?php echo $lang_label["id_user"] ?></td>
	<td class="datos"><input type="text" name="nombre" value="<?php echo $id_usuario_mio ?>"></td>
	<tr><td class="datos2"><?php echo $lang_label["real_name"] ?>
	<td class="datos2"><input type="text" name="nombre_real" value="<?php echo $nombre_real ?>"></td>

<?PHP
    echo '<tr><td class="datos">';
    echo lang_string ("password");
    echo '<td class="datos">';
    echo '<input type="password" name="pass1" value="'.$password.'"></td>';
    echo '<tr><td class="datos2">';
    echo lang_string ("password"). " ". lang_string ("confirmation").'</td>';
	echo '<td class="datos">';
    echo '<input type="password" name="pass2" value="'.$password2.'"></td>';
	echo '<tr><td class="datos">E-Mail</td>';
?>
	<td class="datos"><input type="text" name="direccion" size="40" value="<?php echo $direccion ?>"></td>
	<tr><td class="datos2"><?php echo $lang_label["telefono"] ?></td>
	<td class="datos2"><input type="text" name="telefono" value="<?php echo $telefono ?>"></td>
	<tr><td class="datos"><?php echo $lang_label["global_profile"] ?></td>
	
	<td class="datos">
	<?php if ($nivel == "1"){
		echo $lang_label["administrator"].'<input type="radio" class="chk" name="nivel" value="1" checked><a href="#" class="tip">&nbsp;<span>'.$help_label["users_msg1"].'</span></a>&nbsp;';
		echo $lang_label["normal_user"].'<input type="radio" class="chk" name="nivel" value="0"><a href="#" class="tip">&nbsp;<span>'.$help_label["users_msg2"].'</span></a>';
	} else {
		echo $lang_label["administrator"].'<input type="radio" class="chk" name="nivel" value="1"><a href="#" class="tip">&nbsp;<span>'.$help_label["users_msg1"].'</span></a>&nbsp;';
		echo $lang_label["normal_user"].'<input type="radio" class="chk" name="nivel" value="0" checked><a href="#" class="tip">&nbsp;<span>'.$help_label["users_msg2"].'</span></a>';
	}
	?>		
	<tr><td class="datos2" colspan="2"><?php echo $lang_label["comments"] ?></td>
	<tr><td class="datos" colspan="2">
	<textarea name="comentarios" cols="60" rows="4"><?php echo $comentarios ?></textarea>
	</td></tr>
	
	<?php
	if ($modo == "edicion") { // Only show groups for existing users
		// Combo for group
		echo '<input type="hidden" name="edicion" value="1">';
		echo '<input type="hidden" name="id_usuario_antiguo" value="'.$id_usuario_mio.'">';
		
		echo '<tr><td class="datos2">'.$lang_label["group_avail"].'</td>
		<td class="datos2">
		<select name="grupo" class="w155">';
		echo "<option value=''>".$lang_label["none"];
		$sql1='SELECT * FROM tgrupo ORDER BY nombre';
		$result=mysql_query($sql1);
		while ($row=mysql_fetch_array($result)){
			echo "<option value='".$row["id_grupo"]."'>".$row["nombre"]."</option>";
		}
		echo '</select>';
		
		echo '</td></tr>';
		echo "<tr><td class='datos'>".$lang_label["profiles"]."</td>";
		echo "<td class='datos'>
		<select name='perfil' class='w155'>";
		$sql1='SELECT * FROM tperfil ORDER BY name';
		$result=mysql_query($sql1);
		while ($row=mysql_fetch_array($result)){
			echo "<option value='".$row["id_perfil"]."'>".$row["name"]."</option>";
		}
		echo '</select>';
		echo '</td>';
		echo '</tr></table>';
		echo "<table width=500>";
		echo "<tr><td align='right'>";
		echo "<input name='uptbutton' type='submit' class='sub upd' value='".$lang_label["update"]."'></td></tr></table><br>";
		// Show user profile / groups assigned
		$sql1='SELECT * FROM tusuario_perfil WHERE id_usuario = "'.$id_usuario_mio.'"';
		$result=mysql_query($sql1);
		
		echo '<h3>'.$lang_label["listGroupUser"].'</h3>';
		echo "<table width='500' cellpadding='4' cellspacing='4' class='databox'>";
		if (mysql_num_rows($result)){
			echo '<tr>';
			$color=1;
			while ($row=mysql_fetch_array($result)){
				if ($color == 1){
					$tdcolor = "datos";
					$color = 0;
					}
				else {
					$tdcolor = "datos2";
					$color = 1;
				}
				echo '<td class="'.$tdcolor.'">';
				echo "<b style='margin-left:10px'>".dame_perfil($row["id_perfil"])."</b> / ";
				echo "<b>".dame_grupo($row["id_grupo"])."</b>";
				echo '<td class="'.$tdcolor.'t"><a href="index.php?sec=gusuarios&sec2=godmode/users/configure_user&id_usuario_mio='.$id_usuario_mio.'&borrar_grupo='.$row["id_up"].' " onClick="if (!confirm(\' '.$lang_label["are_you_sure"].'\')) return false;"><img border=0 src="images/cross.png"></a><tr>';
			}
		}
		else {
			echo '<div class="nf">'.$lang_label["no_profile"].'</div>';
		}
	}	
	?>

	<?php if (isset($_GET["alta"])){
		echo '</tr></table>';
		echo '<table width="500">';
		echo '<tr><td align="right">';
		echo '<input name="crtbutton" type="submit" class="sub wand" value="'.$lang_label["create"].'">';
	} 
	?> 
	</form>
	</td></tr></table>

<?php
} // end security check
else {
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access User Management");
		require ("general/noaccess.php");
	} 
?>