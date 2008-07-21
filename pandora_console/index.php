<?php

// Pandora FMS - the Free Monitoring System
// ========================================
// Copyright (c) 2008 Artica Soluciones Tecnológicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Pandora FMS uses icons from famfamfam, licensed under CC Atr. 2.5
// Silk icon set 1.3 (cc) Mark James, http://www.famfamfam.com/lab/icons/silk/
// Pandora FMS uses Pear Image::Graph code

$build_version="PC080226";
$pandora_version="v2.0-dev";
global $build_version;
global $pandora_version;

// Set to 1 to do not check for installer or config file (for development!).
$develop_bypass = 1;

if ($develop_bypass != 1){
	// If no config file, automatically try to install
	if (! file_exists("include/config.php")){
		if (!file_exists("install.php")){
			include ("general/error_noconfig.php");
			exit;
		} else
			include ("install.php");
		exit;
	}
	// Check for installer presence
	if (file_exists("install.php")){
		include "general/error_install.php";
		exit;
	}
	// Check perms for config.php
	if ((substr(sprintf('%o', fileperms('include/config.php')), -4) != "0600") &&
	    (substr(sprintf('%o', fileperms('include/config.php')), -4) != "0660") &&
	    (substr(sprintf('%o', fileperms('include/config.php')), -4) != "0640"))
	{
		include "general/error_perms.php";
		exit;
	}
}

if ((! file_exists("include/config.php")) OR (! is_readable("include/config.php"))){
	include ("general/error_noconfig.php");
	exit;
}

// Real start
session_start();
require_once ("include/config.php");
require_once ("include/languages/language_".$config["language"].".php");
require_once ("include/functions.php");
require_once ("include/functions_db.php");
//We should require this or you might end up with some empty strings
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<?php
// Pure mode (without menu, header and footer).
$config["pure"] = get_parameter("pure",0);

// Auto Refresh page
$intervalo = get_parameter ("refr",0);
if ($intervalo > 0){
	// Agent selection filters and refresh
	$query = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == TRUE ? 's': '') . '://' . $_SERVER['SERVER_NAME'];
	if ($_SERVER['SERVER_PORT'] != 80)
		$query .= ":" . $_SERVER['SERVER_PORT'];
	
	$query .= $_SERVER['SCRIPT_NAME'];
	if (isset ($_REQUEST["refr"])) {
		$query .= '?';
		
		foreach ($_POST as $key => $value) {
			$query .= '&'.$key.'='.$value;
		}
		foreach ($_GET as $key => $value) {
			$query .= '&'.$key.'='.$value;
		}
	}
	if (isset ($_POST["ag_group"])) {
		$ag_group = $_POST["ag_group"];
		$query = 'http://' . $_SERVER['SERVER_NAME'];
		if ($_SERVER['SERVER_PORT'] != 80)
			$query .= ":" . $_SERVER['SERVER_PORT'];
		$query .= $_SERVER['REQUEST_URI'] . '&ag_group_refresh=' . $ag_group;
	} else {
		echo '<meta http-equiv="refresh" content="' . $intervalo . '; URL=' . $query . '">';
	}
}

?>
<title>Pandora FMS - <?php echo lang_string("header_title"); ?></title>
<meta http-equiv="expires" content="0">
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<meta name="resource-type" content="document">
<meta name="distribution" content="global">
<meta name="author" content="Sancho Lerena">
<meta name="copyright" content="This is GPL software. Created by Sancho Lerena and others">
<meta name="keywords" content="pandora, monitoring, system, GPL, software">
<meta name="robots" content="index, follow">
<link rel="icon" href="images/pandora.ico" type="image/ico">
<link rel="stylesheet" href="include/styles/<?php echo $config['style']; ?>.css" type="text/css">

<script type="text/javascript" src="include/javascript/wz_jsgraphics.js"></script>
<script type="text/javascript" src="include/javascript/pandora.js"></script>
</head>

<?php
// Show custom background
if ($config["pure"] == 0)
	echo '<body bgcolor="#555555">';
else
	echo '<body bgcolor="#FFFFFF">';
$REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];

// Login process 
if ( (! isset ($_SESSION['id_usuario'])) && (isset ($_GET["login"]))) {
	$nick = get_parameter_post ("nick");
	$pass = get_parameter_post ("pass");
	// Connect to Database
	$sql1 = sprintf("SELECT `id_usuario`, `password` FROM `tusuario` WHERE `id_usuario` = '%s'",$nick);
	$row = get_db_row_sql ($sql1);
	
	// For every registry
	if ($row !== false){
		if ($row["password"] == md5 ($pass)){
			// Login OK
			// Nick could be uppercase or lowercase (select in MySQL
			// is not case sensitive)
			// We get DB nick to put in PHP Session variable,
			// to avoid problems with case-sensitive usernames.
			// Thanks to David Muñiz for Bug discovery :)
			$nick = $row["id_usuario"];
			unset ($_GET["sec2"]);
			$_GET["sec"] = "general/logon_ok";
			update_user_contact ($nick);
			logon_db ($nick, $REMOTE_ADDR);
			$_SESSION['id_usuario'] = $nick;
			
		} else {
			// Login failed (bad password)
			unset ($_GET["sec2"]);
			include "general/logon_failed.php";
			// change password to do not show all string
			$primera = substr ($pass,0,1);
			$ultima = substr ($pass, strlen ($pass) - 1, 1);
			$pass = $primera . "****" . $ultima;
			audit_db ($nick, $REMOTE_ADDR, "Logon Failed",
				  "Incorrect password: " . $nick . " / " . $pass);
			exit;
		}
	} else {
		// User not known
		unset ($_GET["sec2"]);
		include "general/logon_failed.php";
		$primera = substr ($pass, 0, 1);
		$ultima = substr ($pass, strlen ($pass) - 1, 1);
		$pass = $primera . "****" . $ultima;
		audit_db ($nick, $REMOTE_ADDR, "Logon Failed",
			  "Invalid username: " . $nick . " / " . $pass);
		exit;
	}
} elseif (! isset ($_SESSION['id_usuario'])) {
	// There is no user connected
	include "general/login_page.php";
	exit;
} else {
	// There is session for id_usuario
	$config["id_user"] = $_SESSION["id_usuario"];
}

// Log off
if (isset ($_GET["bye"])) {
	include "general/logoff.php";
	$iduser = $_SESSION["id_usuario"];
	logoff_db ($iduser, $REMOTE_ADDR);
	session_unregister ("id_usuario");
	exit;
}
$pagina = "";
if (isset ($_GET["sec2"])){
	$sec2 = get_parameter_get ('sec2');
	$sec2 = parameter_extra_clean ($sec2);
	$pagina = $sec2;
} else
	$sec2 = "";
	
if (isset ($_GET["sec"])){
	$sec = get_parameter_get ('sec');
	$sec = parameter_extra_clean ($sec);
	$pagina = $sec2;
} else {
	$sec = "";
}

// http://es2.php.net/manual/en/ref.session.php#64525
// Session locking concurrency speedup!
session_write_close(); 

// Header
if ($config["pure"] == 0){
	echo '<div id="container">';
	echo '<div id="head">';
	require ("general/header.php"); 
	echo '</div>';
	echo '<div id="page">';
	echo '<div id="menu">';
	require ("general/main_menu.php");
	echo '</div>';
} else {
	echo '<div id="main_pure">';
}

// Main block of content
if ($config["pure"] == 0){
	echo '<div id="main">';
}

// Page loader / selector
if ($pagina != ""){
	if (file_exists ($pagina.".php")) {
		require ($pagina.".php");
	} else {
		echo "<br><b class='error'>".lang_string("Sorry! I can't find the page!")."</b>";
	}	
} else
	require ("general/logon_ok.php");  //default

if ($config["pure"] == 0){    
	echo '</div>'; // main
	echo '<div style="clear:both"></div>';
	echo '</div>'; // page
} else {
	echo "</div>";
}

if ($config["pure"] == 0) {
	echo '<div id="foot">';
	require ("general/footer.php");
	echo '</div>';
	echo '</div>';
}

echo '</body></html>';

?>
