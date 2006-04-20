<?php
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandora@gmail.com>, 2005-2006

// Load globar vars
require("include/config.php");
//require("include/functions.php");
//require("include/functions_db.php");
if (comprueba_login() == 0) 
	if (give_acl($id_user, 0, "UM")==1) {
		if (isset($_GET["borrar_usuario"])){ // se ha modificado algun parametro de agente
			$nombre= entrada_limpia($_GET["borrar_usuario"]);
			// Delete user
			// Delete cols from table tgrupo_usuario
			
			$query_del1="DELETE FROM tgrupo_usuario WHERE usuario = '".$nombre."'";
			$query_del2="DELETE FROM tusuario WHERE id_usuario = '".$nombre."'";
			$resq1=mysql_query($query_del1);
			$resq1=mysql_query($query_del2);
               	 	if (! $resq1)
               	         	echo "<h3 class='error'>".$lang_label["delete_user_no"]."</h3>";
               	 	else
                        	echo "<h3 class='suc'>".$lang_label["delete_user_ok"]."</h3>";
		}
?>

<h2><?php echo $lang_label["user_management"] ?><a href="help/<?php echo substr($language_code,0,2);?>/chap2.php#22" target="_help"><img src="images/ayuda.gif" border="0" class="help"></a></h2>
<h3><?php echo $lang_label["users"] ?></h3>
 
<table cellpadding=3 cellspacing=3 width=550>
<th class="w80"><?php echo $lang_label["user_ID"]?>
<th><?php echo $lang_label["last_contact"]?>
<th><?php echo $lang_label["profile"]?>
<th><?php echo $lang_label["name"]?>
<th width=30><?php echo $lang_label["delete"]?>

<?php
$query1="SELECT * FROM tusuario";
$resq1=mysql_query($query1);
// Inicializacion variables
$nombre = "";
$nivel = "";
$comentarios = "";
$fecha_registro = "";

while ($rowdup=mysql_fetch_array($resq1)){
	$nombre=$rowdup["id_usuario"];
	$nivel =$rowdup["nivel"];
	$comentarios =$rowdup["nombre_real"];
	$fecha_registro =$rowdup["fecha_registro"];
	echo "<tr><td class=datos>";
	echo "<a href='index.php?sec=gusuarios&sec2=godmode/usuarios/configurar_usuarios&id_usuario_mio=".$nombre."'><b>".$nombre."</b></a>";
	echo "<td class=datos>".$fecha_registro;
	echo "<td class=datos>";
	if ($nivel == 1) 
		echo "<img src='images/admin.gif'>";
	else
		echo "<img src='images/user.gif'>";
	
	$sql1='SELECT * FROM tusuario_perfil WHERE id_usuario = "'.$nombre.'"';
	$result=mysql_query($sql1);
	echo "<a href='#' class='tip'>&nbsp;<span>";
	if (mysql_num_rows($result)){
		while ($row=mysql_fetch_array($result)){
			echo dame_perfil($row["id_perfil"])."/ ";
			echo dame_grupo($row["id_grupo"])."<br>";
		}
	}
	else { echo $lang_label["no_profile"]; }
	echo "</span></a>";
	
	echo "<td class=datos>".$comentarios;
	echo '<td class=datos align="center"><a href="index.php?sec=gusuarios&sec2=godmode/usuarios/lista_usuarios&borrar_usuario='.$nombre.'" onClick="if (!confirm(\' '.$lang_label["are_you_sure"].'\')) return false;"><img border=0 src="images/cancel.gif"></a>  ';
}
	echo "<tr><td colspan='5'><div class='raya'></div></td></tr>";
	echo "<tr><td colspan='5' align='right'>";
	echo "<form method=post action='index.php?sec=gusuarios&sec2=godmode/usuarios/configurar_usuarios&alta=1'>";
        echo "<input type='submit' class='sub' name='crt' value='".$lang_label["create_user"]."'>";
        echo "</form></td></tr></table>";

echo "</table>";

} // fin comprobacion seguridad pagina
 else {
                audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access User Management");
                require ("general/noaccess.php");
}        
?>