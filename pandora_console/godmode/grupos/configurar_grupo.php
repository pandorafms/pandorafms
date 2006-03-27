<?php
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandora@gmail.com>, 2005-2006

// Cargamos variables globales
require("include/config.php");
if (comprueba_login() == 0)
   $id_user = $_SESSION["id_usuario"];
   if (give_acl($id_user, 0, "PM")==1) {
	// Inic vars
	$id_grupo = "";
	$nombre = "";
	
	
	if (isset($_GET["creacion_grupo"])){ //
		$creacion_grupo = entrada_limpia($_GET["creacion_grupo"]);
	} else
		$creacion_grupo = 0;
		
	if (isset($_GET["id_grupo"])){
		// Conecto con la BBDD
		$id_grupo = entrada_limpia($_GET["id_grupo"]);
		$sql1='SELECT * FROM tgrupo WHERE id_grupo = '.$id_grupo;
		$result=mysql_query($sql1);
		if ($row=mysql_fetch_array($result)){
			$nombre = $row["nombre"];
			$icono = $row["icon"];
		} else
			{
			echo "<h3 class='error'>".$lang_label["group_error"]."</h3>";
			echo "</table>";                                        
			include ("general/footer.php");                       
			exit;
			}
	}

	echo "<h2>".$lang_label["group_management"]."</h2>";
	if (isset($_GET["creacion_grupo"])) {echo "<h3>".$lang_label["create_group"]."</h3>";}
	if (isset($_GET["id_grupo"])) {echo "<h3>".$lang_label["update_group"]."</h3>";}
	
?>
<table width="450">

<form name="grupo" method="post" action="index.php?sec=gagente&sec2=godmode/grupos/lista_grupos">

<?php
	if ($creacion_grupo == 1)
		echo "<input type='hidden' name='crear_grupo' value='1'>";
	else {
		echo "<input type='hidden' name='update_grupo' value='1'>";
		echo "<input type='hidden' name='id_grupo' value='".$id_grupo."'>";
	}
?>
<tr><th rowspan=3 width=5>
<tr><td class="datos"><?php echo $lang_label["group_name"] ?><td class="datos"><input type="text" name="nombre" size="35" value="<?php echo $nombre ?>">
<tr><td class="datos"><?php echo $lang_label["icon"] ?><td class="datos"><input type="icon" name="icon" size="25" value="<?php if (isset($icono)){echo $icono;} ?>">
<tr><td></td></tr>
<tr><td colspan="3" align="right"><?php if (isset($_GET["creacion_grupo"])){echo "<input name='crtbutton' type='submit' class='sub' value='".$lang_label["create"]."'>";}
else {echo "<input name='uptbutton' type='submit' class='sub' value='".$lang_label["update"]."'>";} ?>
</form>

</table>

<?php
   } // fin pagina
else {
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Group Management2");
		require ("general/noaccess.php");
	}
?>