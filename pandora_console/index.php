<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2009 Artica Soluciones Tecnologicas, http://www.artica.es
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

//Set character encoding to UTF-8 - fixes a lot of multibyte character headaches
if (function_exists ('mb_internal_encoding')) {
	mb_internal_encoding ("UTF-8");
}

// Set to 1 to do not check for installer or config file (for development!).
$develop_bypass = 1;

if ($develop_bypass != 1) {
	// If no config file, automatically try to install
	if (! file_exists ("include/config.php")) {
		if (! file_exists ("install.php")) {
			include ("general/error_noconfig.php");
			exit;
		} else {
			include ("install.php");
			exit;
		}
	}
	// Check for installer presence
	if (file_exists ("install.php")) {
		include "general/error_install.php";
		exit;
	}
	// Check perms for config.php
	if ((substr (sprintf ('%o', fileperms('include/config.php')), -4) != "0600") &&
		(substr (sprintf ('%o', fileperms('include/config.php')), -4) != "0660") &&
		(substr (sprintf ('%o', fileperms('include/config.php')), -4) != "0640")) {
		include "general/error_perms.php";
		exit;
	}
}

if ((! file_exists("include/config.php")) || (! is_readable("include/config.php"))) {
	include ("general/error_noconfig.php");
	exit;
}

// Real start
session_start();
require_once ("include/config.php");
require_once ("include/functions.php");
require_once ("include/functions_db.php");

/* Enterprise support */
if (file_exists (ENTERPRISE_DIR."/load_enterprise.php")) {
	include (ENTERPRISE_DIR."/load_enterprise.php");
}

load_extensions ($config['extensions']);

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"><html><head>';

// Pure mode (without menu, header and footer).
$config["pure"] = (bool) get_parameter ("pure", 0);

// Auto Refresh page
$config["refr"] = (int) get_parameter ("refr", 0);
if ($config["refr"] > 0) {
	// Agent selection filters and refresh
	$query = 'http' . (isset ($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == TRUE ? 's': '') . '://' . $_SERVER['SERVER_NAME'];
	if ($_SERVER['SERVER_PORT'] != 80 && (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == TRUE && $_SERVER['SERVER_PORT'] != 443)) {
		$query .= ":" . $_SERVER['SERVER_PORT'];
	}
	
	$query .= $_SERVER['SCRIPT_NAME'];
	if (sizeof ($_REQUEST))
		 //Some (old) browsers don't like the ?&key=var
		$query .= '?1=1';
		
	//We don't clean these variables up as they're only being passed along
	foreach ($_GET as $key => $value) {
		/* Avoid the 1=1 */
		if ($key == 1)
			continue;
		$query .= '&'.$key.'='.$value;
	}
	foreach ($_POST as $key => $value) {
		$query .= '&'.$key.'='.$value;
	}
	
	echo '<meta http-equiv="refresh" content="'.$config["refr"].'; URL='.$query.'">';
}

enterprise_include ('index.php');

echo '<title>Pandora FMS - '.__('the Flexible Monitoring System').'</title>
<meta http-equiv="expires" content="0" />
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<meta name="resource-type" content="document" />
<meta name="distribution" content="global" />
<meta name="author" content="Sancho Lerena" />
<meta name="copyright" content="This is GPL software. Created by Sancho Lerena and others" />
<meta name="keywords" content="pandora, monitoring, system, GPL, software" />
<meta name="robots" content="index, follow" />
<link rel="icon" href="images/pandora.ico" type="image/ico" />
<link rel="stylesheet" href="include/styles/'.$config["style"].'.css" type="text/css" />
<script type="text/javascript" src="include/javascript/wz_jsgraphics.js"></script>
<script type="text/javascript" src="include/javascript/jquery.js"></script>
<script type="text/javascript" src="include/javascript/pandora.js"></script>';

enterprise_hook ('load_html_header');

echo '</head>';

// Show custom background
if ($config["pure"] == 0) {
	echo '<body bgcolor="#555555">';
} else {
	echo '<body bgcolor="#ffffff">';
}

$REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];
$config["remote_addr"] = $_SERVER['REMOTE_ADDR'];

$sec2 = get_parameter_get ('sec2');
$sec2 = safe_url_extraclean ($sec2);
$page = $sec2; //Reference variable for old time sake

$sec = get_parameter_get ('sec');
$sec = safe_url_extraclean ($sec);

// Hash login process
if (! isset ($_SESSION['id_usuario']) && isset ($_GET["loginhash"])) {
	$loginhash_data = get_parameter("loginhash_data", "");
	$loginhash_user = get_parameter("loginhash_user", "");
	
	if ($loginhash_data == md5($loginhash_user.$config["loginhash_pwd"])) {
		update_user_contact ($loginhash_user);
		logon_db ($loginhash_user, $REMOTE_ADDR);
		$_SESSION['id_usuario'] = $loginhash_user;
		$config["id_user"] = $loginhash_user;
	} else {
		require_once ('general/login_page.php');
		audit_db ("system", $REMOTE_ADDR, "Logon Failed (loginhash", "");
		exit;
	}
}

// Login process 
elseif (! isset ($_SESSION['id_usuario']) && isset ($_GET["login"])) {
	$nick = get_parameter_post ("nick");
	$pass = get_parameter_post ("pass");
	// Connect to Database
	$sql = sprintf ("SELECT `id_usuario`, `password` FROM `tusuario` WHERE `id_usuario` = '%s'", $nick);
	$row = get_db_row_sql ($sql);
	
	// For every registry
	if ($row !== false && $row["password"] == md5 ($pass)) {
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
		$config['id_user'] = $nick;
		unset ($_GET['pass'], $pass, $_POST['pass'], $_REQUEST['pass']);
	} else {
		// User not known
		$login_failed = true;
		require_once ('general/login_page.php');
		audit_db ($nick, $REMOTE_ADDR, "Logon Failed",
			  "Invalid login: ".$nick);
		exit;
	}
} elseif (! isset ($_SESSION['id_usuario'])) {
	// There is no user connected
	require_once ('general/login_page.php');
	echo '</body></html>';
	exit;
} else {
	// There is session for id_usuario
	$config["id_user"] = $_SESSION["id_usuario"];
}

// Log off
if (isset ($_GET["bye"])) {
	include ("general/logoff.php");
	$iduser = $_SESSION["id_usuario"];
	logoff_db ($iduser, $REMOTE_ADDR);
	session_unregister ("id_usuario");
	exit;
}

// http://es2.php.net/manual/en/ref.session.php#64525
// Session locking concurrency speedup!
session_write_close(); 

// Header
if ($config["pure"] == 0) {
	echo '<div id="container"><div id="head">';
	require ("general/header.php"); 
	echo '</div><div id="page"><div id="menu">';
	require ("general/main_menu.php");
	echo '</div>';
} else {
	echo '<div id="main_pure">';
}

// Main block of content
if ($config["pure"] == 0) {
	echo '<div id="main">';
}

// Page loader / selector
if ($page != "") {
	$page .= '.php';
	if (file_exists ($page)) {
		if (! is_extension ($page)) {
			require ($page);
		} else {
			if ($sec[0] == 'g') {
				extension_call_godmode_function (basename ($page));
			} else {
				extension_call_main_function (basename ($page));
			}
		}
	} else {
		echo '<br><b class="error">'.__('Sorry! I can\'t find the page!').'</b>';
	}
} else {
	if (enterprise_hook ('load_logon_ok') === ENTERPRISE_NOT_HOOK) {
		require ("general/logon_ok.php");
	}
}

if ($config["pure"] == 0) {
	echo '</div>'; // main
	echo '<div style="clear:both">&nbsp;</div>';
	echo '</div>'; // page (id = page)
} else {
	echo "</div>"; // main_pure
}

if ($config["pure"] == 0) {
	echo '<div id="foot">';
	require ("general/footer.php");
	echo '</div>';
	echo '</div>';
}

echo '</body></html>';
?>
