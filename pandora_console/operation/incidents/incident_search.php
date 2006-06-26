<?php
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandora@gmail.com>, 2005-2006

// Cargamos variables globales
require("include/config.php");
//require("include/functions.php");
//require("include/functions_db.php");
if (comprueba_login() == 0) {

echo "<img src='images/pulpo_lupa.gif' align='right' class='bot'>";	
echo "<h2>".$lang_label["incident_manag"]."</h2>";
echo "<h3>".$lang_label["find_crit"]." <a href='help/".substr($language_code,0,2)."/chap4.php#43' target='_help'><img src='images/ayuda.gif' border='0' class='help'></a></h3>";
?>
<table width="500" cellpadding="3" cellspacing="3">
<form name="busqueda" method="post" action="index.php?sec=incidencias&sec2=operation/incidents/incident">
<td class='lb' rowspan="4" width="5">
<tr>
<td class="datos"><?php echo $lang_label["user"] ?>
<td class="datos">
<select name="usuario">
	<option value=""><?php echo $lang_label["all"] ?>
	<?php 
	$sql1='SELECT * FROM tusuario ORDER BY id_usuario';
	$result=mysql_query($sql1);
	while ($row=mysql_fetch_array($result)){
		echo "<option>".$row["id_usuario"];
	}
	?>
</select>
<tr><td class="datos"><?php echo $lang_label["free_text_search"] ?>
<td class="datos"><input type="text" size="45" name="texto">
<tr><td class="datos" colspan="2">
<i><?php echo $lang_label["free_text_search_msg"] ?></i>

<tr><td align="right" colspan="3">
<?php echo "<input name='uptbutton' type='submit' class='sub' value='".$lang_label["search"]."'>"; ?>

</form>
</table>
<?php 

} // fin pagina
?>