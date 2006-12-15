<?php
// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandora@gmail.com>, 2004-2006
?>
<br>
<a href="index.php"><img src="images/logo_menu.gif" border="0" alt="logo"></a>
<div id='ver'><?php echo $pandora_version; ?></div>

<?php 
require ("operation/menu.php");
if (! isset ($_SESSION["id_usuario"])) {
	echo '<div class="f10">' . $lang_label["not_connected"];
	echo '<br /><br />';
	echo '<form method="post" action="index.php?login=1">';
	echo '<div class="f9b">Login</div><input class="login" type="text" name="nick">';
	echo '<div class="f9b">Password</div><input class="login" type="password" name="pass">';
	echo '<div><input name="login" type="submit" class="sub" value="' . $lang_label["login"] .'"></div>';
	echo '<br />IP: <b class="f10">' . $REMOTE_ADDR . '</b><br /></div>';
	
} else {
	
	$iduser = $_SESSION['id_usuario'];
	require ("godmode/menu.php");
	echo '<div class="w155f10"><form method="post" action="index.php?logoff=1">';
	echo '<input type="hidden" name="bye" value="bye">';
	echo '<input name="logoff" type="submit" class="sub" value="' . $lang_label["logout"] . '">';
	echo '</form>' . $lang_label["has_connected"] . '<br />';
	echo '[<b class="f10">' . $iduser . '</b>]<br />';
	echo "<br />IP: <b class='f10'>" . $REMOTE_ADDR . "</b><br /></div><div>&nbsp;</div>";
	require ("links_menu.php");
}
?>
