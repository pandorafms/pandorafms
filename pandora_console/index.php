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

if ((! file_exists ("include/config.php")) || (! is_readable ("include/config.php"))) {
	include ("general/error_noconfig.php");
	exit;
}

// Real start
session_start ();
require_once ("include/config.php");

/* Enterprise support */
if (file_exists (ENTERPRISE_DIR."/load_enterprise.php")) {
	include (ENTERPRISE_DIR."/load_enterprise.php");
}

load_extensions ($config['extensions']);

if (!empty ($config["https"]) && empty ($_SERVER['HTTPS'])) {
	$query = 'https://' . $_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'];
	if (sizeof ($_REQUEST))
		//Some (old) browsers don't like the ?&key=var
		$query .= '?1=1';
	
	//We don't clean these variables up as they're only being passed along
	foreach ($_GET as $key => $value) {
		if ($key == 1)
			continue;
		$query .= '&'.$key.'='.$value;
	}
	foreach ($_POST as $key => $value) {
		$query .= '&'.$key.'='.$value;
	}
	header ('Location: '.$query);
	exit; //Always exit after sending location headers
}

// Pure mode (without menu, header and footer).
$config["pure"] = (bool) get_parameter ("pure");

// Auto Refresh page (can now be disabled anywhere in the script)
$config["refr"] = (int) get_parameter ("refr");

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
echo '<html xmlns="http://www.w3.org/1999/xhtml">'."\n";
echo '<head>';
//This starts the page head. In the call back function, things from $page['head'] array will be processed into the head
ob_start ('process_page_head');

enterprise_include ('index.php');

//This tag is included in the buffer passed to process_page_head so technically it can be stripped
echo '</head>'."\n";

ob_start ('process_page_body');

$REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];
$config["remote_addr"] = $_SERVER['REMOTE_ADDR'];

$sec2 = get_parameter_get ('sec2');
$sec2 = safe_url_extraclean ($sec2);
$page = $sec2; //Reference variable for old time sake

$sec = get_parameter_get ('sec');
$sec = safe_url_extraclean ($sec);

// Hash login process
if (! isset ($config['id_user']) && isset ($_GET["loginhash"])) {
	$loginhash_data = get_parameter("loginhash_data", "");
	$loginhash_user = get_parameter("loginhash_user", "");
	
	if ($loginhash_data == md5($loginhash_user.$config["loginhash_pwd"])) {
		logon_db ($loginhash_user, $REMOTE_ADDR);
		$_SESSION['id_usuario'] = $loginhash_user;
		$config["id_user"] = $loginhash_user;
	} else {
		require_once ('general/login_page.php');
		audit_db ("system", $REMOTE_ADDR, "Logon Failed (loginhash", "");
		while (@ob_end_flush ());
		exit ("</html>");
	}
} elseif (! isset ($config['id_user']) && isset ($_GET["login"])) {
	// Login process 
	
	$config["auth_error"] = ""; //Set this to the error message from the authorization mechanism
	$nick = get_parameter_post ("nick"); //This is the variable with the login
	$pass = get_parameter_post ("pass"); //This is the variable with the password
	
	// process_user_login is a virtual function which should be defined in each auth file.
	// It accepts username and password. The rest should be internal to the auth file.
	// The auth file can set $config["auth_error"] to an informative error output or reference their internal error messages to it
	// process_user_login should return false in case of errors or invalid login, the nickname if correct
	$nick = process_user_login ($nick, $pass);
			
	if ($nick !== false) {
		unset ($_GET["sec2"]);
		$_GET["sec"] = "general/logon_ok";
		logon_db ($nick, $REMOTE_ADDR);
		$_SESSION['id_usuario'] = $nick;
		$config['id_user'] = $nick;
		//Remove everything that might have to do with people's passwords or logins
		unset ($_GET['pass'], $pass, $_POST['pass'], $_REQUEST['pass'], $login_good);
	} else {
		// User not known
		$login_failed = true;
		require_once ('general/login_page.php');
		audit_db ($nick, $REMOTE_ADDR, "Logon Failed", "Invalid login: ".$nick);
		while (@ob_end_flush ());
		exit ("</html>");
	}
} elseif (! isset ($config['id_user'])) {
	// There is no user connected
	require_once ('general/login_page.php');
	while (@ob_end_flush ());
	exit ("</html>");
}

// Log off
if (isset ($_GET["bye"])) {
	include ("general/logoff.php");
	$iduser = $_SESSION["id_usuario"];
	logoff_db ($iduser, $REMOTE_ADDR);
	session_unregister ("id_usuario");
	while (@ob_end_flush ());
	exit ("</html>");
}

// http://es2.php.net/manual/en/ref.session.php#64525
// Session locking concurrency speedup!
session_write_close (); 

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

// Check permissions
if (!is_writable ("attachment")){
	echo '<h3 class="error">'.__('Attachment directory is not writable by HTTP Server').'</h3>';
	echo '<p>'.__('Please check that the web server has write rights on the {HOMEDIR}/attachment directory').'</p>';
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
		echo '<br /><strong class="error">'.__('Sorry! I can\'t find the page!').'</strong>';
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
	echo '</div>'; //container div
}
while (@ob_end_flush ());

print_database_debug ();
echo '</html>';

$run_time = format_numeric (microtime (true) - $config['start_time'], 3);
echo "\n<!-- Page generated in $run_time seconds -->\n";
?>
