<?php
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandora@gmail.com>, 2005-2006

// Cargamos variables globales
require("include/config.php");
if ( (give_acl($id_user, 0, "LM")==1)){
	// Var init
	$descripcion = "";
	$nombre = "";
	$comando ="";
	
	if (isset($_GET["id_alerta"])){
		$id_alerta = entrada_limpia($_GET["id_alerta"]);
		$sql1='SELECT * FROM talerta WHERE id_alerta = '.$id_alerta;
		$result=mysql_query($sql1);
		if ($row=mysql_fetch_array($result)){
			$descripcion = $row["descripcion"];
			$nombre= $row["nombre"];
			$comando = $row["comando"];
		} else
			{
			echo "<h3 class='error'>".$lang_label["alert_error"]."</h3>";
			echo "</table>";
			include ("general/footer.php");
			exit;
		}
	}
	
	$creacion_alerta = 0;
	if (isset($_GET["creacion"])){
		// Conecto con la BBDD
		$creacion_alerta = 1;

		// Codigo de creacion de la alerta
	}
	
?>
<h2><?php echo $lang_label["alert_config"];?></h2>
<h3><?php if (isset($_GET["creacion"])){echo $lang_label["create_alert"];} if (isset($_GET["id_alerta"])){echo $lang_label["mod_alert"];} ?><a href="help/<?php echo substr($language_code,0,2);?>/chap3.php#3221" target="_help" class="help">&nbsp;<span><?php echo $lang_label["help"];?></span></a></h3>

<table width="500" cellspacing="3" cellpadding="3">

<form name="alerta" method="post" action="index.php?sec=galertas&sec2=godmode/alertas/modificar_alerta&id_alerta=<?php echo $id_alerta ?>">

<?php
	if ($creacion_alerta == 1)
		echo "<input type='hidden' name='crear_alerta' value='1'>";
	else {
		echo "<input type='hidden' name='update_alerta' value='1'>";
		echo "<input type='hidden' name='id_alerta' value='".$id_alerta."'>";
	}
?>
<tr><td class='lb' rowspan='3' width='5'>
<td class="datos"><?php echo $lang_label["alertname"] ?>
<td class="datos"><input type="text" name="nombre" size=30 value="<?php echo $nombre ?>">

<tr>
<td class="datos2"><?php echo $lang_label["command"] ?>
<td class="datos2"><input type="text" name="comando" size="50" value="<?php echo $comando ?>">
<a href='#' class='tip'>&nbsp;<span>
<b>Macros:</b><br>
_field1_<br>
_field2_<br>
_field3_<br>
_agent_<br>
_timestamp_<br>
_data_<br>
</span></a>

<tr><td class="datos"><?php echo $lang_label["description"] ?>
<td class="datos"><textarea name="descripcion" cols="50" rows="7">
<?php echo $descripcion ?>
</textarea>
<tr><td colspan='3'><div class='raya'></div></td></tr>
<tr><td colspan="3" align="right">
<?php if (isset($_GET["creacion"])){echo "<input name='crtbutton' type='submit' class='sub' value='".$lang_label["create"]."'>";}
else {echo "<input name='uptbutton' type='submit' class='sub' value='".$lang_label["update"]."'>";} ?>
</form>

</table>

<?php
} // end page
else {
		audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Alert Management");
		require ("general/noaccess.php");
	}	
?>