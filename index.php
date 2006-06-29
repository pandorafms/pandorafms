<?PHP 
// Pandora - the Free monitoring system
// ====================================
// Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2006 Artica Soluciones Tecnológicas S.L, info@artica.es
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

session_start(); 
include "include/config.php";
include "include/languages/language_".$language_code.".php";
require("include/functions.php"); // Including funcions.
require("include/functions_db.php");
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<?php
// Refresh page
if (isset ($_GET["refr"])){
	$intervalo = entrada_limpia($_GET["refr"]);
	// Agent selection filters and refresh
 	if (isset ($_POST["ag_group"])){
		$ag_group = $_POST["ag_group"];
		$query='http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].'&ag_group_refresh='.$ag_group;
		echo '<meta http-equiv="refresh" content="'.$intervalo.'; URL='.$query.'">';
	}
	else 
		echo '<meta http-equiv="refresh" content="'.$intervalo.'">';	
}
?>
<title>Pandora - <?php echo $lang_label["header_title"]; ?></title>
<meta http-equiv="expires" content="0">
<meta http-equiv="content-type" content="text/html; charset=ISO-8859-15">
<meta name="resource-type" content="document">
<meta name="distribution" content="global">
<meta name="author" content="Sancho Lerena, Raul Mateos">
<meta name="copyright" content="This is GPL software. Created by Sancho Lerena and others">
<meta name="keywords" content="pandora, monitoring, system, GPL, software">
<meta name="robots" content="index, follow">
<link rel="icon" href="images/pandora.ico" type="image/ico">
<link rel="stylesheet" href="include/styles/pandora.css" type="text/css">
</head>
<body>
<?php

	$REMOTE_ADDR = getenv("REMOTE_ADDR");
   	global $REMOTE_ADDR;
 
   	if ( (! isset($_SESSION['id_usuario'])) AND (isset($_GET["login"]))){ // Login process
		$nick = entrada_limpia($_POST["nick"]);
		$pass = entrada_limpia($_POST["pass"]);
		// Connect to Database
		$sql1='SELECT * FROM tusuario WHERE id_usuario = "'.$nick.'"';
		$result=mysql_query($sql1); 
		// Every registry
		if ($row=mysql_fetch_array($result)){
			if ($row["password"]==md5($pass)){
			// Login OK
				// Nick could be uppercase or lowercase (select in mysql is not case sensitive)
				// We get DB nick to put in PHP Session variable, to avoid problems with case-sensitive usernames :)
				// Thanks to David MuÃ±iz for Bug discovery :)
				$nick = $row["id_usuario"];
				unset($_GET["sec2"]);
				$_GET["sec"]="general/logon_ok";
				update_user_contact($nick);
				logon_db($nick,$REMOTE_ADDR);
				$_SESSION['id_usuario']=$nick;
				
			}
			else { // Login failed (bad password)
				unset($_GET["sec2"]);
				include "general/logon_failed.php";
				// change password to do not show all string
				$primera = substr($pass,0,1);
				$ultima = substr($pass,strlen($pass)-1,1);
				$pass = $primera."****".$ultima;
				audit_db($nick,$REMOTE_ADDR,"Logon Failed","Incorrect password: ".$nick." / ".$pass);
				include "general/footer.php";
				exit;
			}
		}
		else { // User not known
			unset($_GET["sec2"]);
			include "general/logon_failed.php";
			$primera = substr($pass,0,1);
			$ultima = substr($pass,strlen($pass)-1,1);
			$pass = $primera."****".$ultima;
			audit_db($nick,$REMOTE_ADDR,"Logon Failed","Invalid username: ".$nick." / ".$pass);
			include "general/footer.php";
			exit;
		}
	}
	// If there is no user connected
	elseif (! isset($_SESSION['id_usuario'])) {
		include "general/login_page.php";
		exit;
	}
	
	if (isset($_GET["logoff"])){ // LOG OFF
		unset($_GET["sec2"]);
		$_GET["sec"]="general/logoff";
		$iduser=$_SESSION["id_usuario"];
		logoff_db($iduser,$REMOTE_ADDR);
		session_unregister("id_usuario");
	}
?>
<div id="page">
	<div id="menu"><?php require("general/main_menu.php"); ?></div>
	<div id="main">
		<div id='head'><?php require("general/header.php"); ?></div>
		<?php
		if (isset($_GET["sec2"])){
		  	$pagina = parametro_limpio($_GET["sec2"]);
		  	if ($pagina <> "") {
				if(file_exists($pagina.".php")) {
					require($pagina.".php");
				}
				else print "<br><b class='error'>Sorry! I can't find the page!</b>";
			}
		}
		elseif (isset($_GET["sec"] )){
	  	  	$pagina = parametro_limpio($_GET["sec"]);
			if(file_exists($pagina.".php")) {
				require($pagina.".php");
			}
			else print "<br><b class='error'>Sorry! I can't find the page!</b>";
		}
		else
			require("general/logon_ok.php");  //default
		?>
	</div>
</div>
<?php require("general/footer.php") ?>
</body>
</html>