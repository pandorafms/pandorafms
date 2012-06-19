<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

//Set character encoding to UTF-8 - fixes a lot of multibyte character headaches
if (function_exists ('mb_internal_encoding')) {
	mb_internal_encoding ("UTF-8");
}

// Set to 1 to do not check for installer or config file (for development!).
// Activate gives more error information, not useful for production sites
$develop_bypass = 0;

if ($develop_bypass != 1) {
	// If no config file, automatically try to install
	if (! file_exists ("include/config.php")) {
		if (! file_exists ("install.php")) {
			include ("general/error_noconfig.php");
			exit;
		}
		else {
			include ("install.php");
			exit;
		}
	}
	if (filesize("include/config.php") == 0) {
		include ("install.php");
		exit;
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
	include_once (ENTERPRISE_DIR."/load_enterprise.php");
}


if (!empty ($config["https"]) && empty ($_SERVER['HTTPS'])) {
	$query = '';
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
	$url = ui_get_full_url($query);
	
	// Prevent HTTP response splitting attacks
	// http://en.wikipedia.org/wiki/HTTP_response_splitting
	$url = str_replace ("\n", "", $url);
	
	header ('Location: '.$query);
	exit; //Always exit after sending location headers
}

// Pure mode (without menu, header and footer).
$config["pure"] = (bool) get_parameter ("pure");

// Auto Refresh page (can now be disabled anywhere in the script)
if (get_parameter ("refr"))
	$config["refr"] = (int) get_parameter ("refr");

/* Hack to change IE render version if visual console editor is rendered or not  */
$sec2 = get_parameter_get ('sec2');
$sec2 = safe_url_extraclean ($sec2);
$page = $sec2; //Reference variable for old time sake
$tab_vc = get_parameter_get('tab', '');
ob_start ();
?>
<!--[if !IE]> -->
<?php
echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
?>
<![endif]-->
<!--[if IE]>																																									
<?php  
if ($sec2 == 'godmode/reporting/visual_console_builder' and $tab_vc == 'editor')  
	echo '<!--<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">-->'."\n";
else 
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";

echo '<html xmlns="http://www.w3.org/1999/xhtml">'."\n";
?>
<![endif]-->
<!--[if IE]>																																									
<?php  

if ($sec2 == 'godmode/reporting/visual_console_builder' and $tab_vc == 'editor') { 

	echo '<meta http-equiv="X-UA-Compatible" content="IE=7" >' . "\n"; 
} 
else { 

	echo '<meta http-equiv="X-UA-Compatible" content="IE=9" >' . "\n"; 
}	
?>
<![endif]-->
<?php 
echo '<head>';

//This starts the page head. In the call back function, things from $page['head'] array will be processed into the head
ob_start ('ui_process_page_head');

// Enterprise main 
enterprise_include ('index.php');

// This tag is included in the buffer passed to ui_process_page_head so 
// technically it can be stripped
echo '</head>'."\n";
require_once ("include/functions_themes.php");
ob_start ('ui_process_page_body');

$config["remote_addr"] = $_SERVER['REMOTE_ADDR'];



$sec = get_parameter_get ('sec');
$sec = safe_url_extraclean ($sec);

$process_login = false;

$searchPage = false;
$search = get_parameter_get("head_search_keywords");
if (strlen($search) > 0) {
	$config['search_keywords'] = trim(get_parameter('keywords'));
	// If not search category providad, we'll use an agent search
	$config['search_category'] = get_parameter('search_category', 'agents');
	if (($config['search_keywords'] != 'Enter keywords to search') && (strlen($config['search_keywords']) > 0))
		$searchPage = true;
}

// Hash login process
if (! isset ($config['id_user']) && isset ($_GET["loginhash"])) {
	$loginhash_data = get_parameter("loginhash_data", "");
	$loginhash_user = get_parameter("loginhash_user", "");
	
	if ($config["loginhash_pwd"] != "" && $loginhash_data == md5($loginhash_user.$config["loginhash_pwd"])) {
		db_logon ($loginhash_user, $_SERVER['REMOTE_ADDR']);
		$_SESSION['id_usuario'] = $loginhash_user;
		$config["id_user"] = $loginhash_user;
	}
	else {
		require_once ('general/login_page.php');
		db_pandora_audit("Logon Failed (loginhash", "", "system");
		while (@ob_end_flush ());
		exit ("</html>");
	}
}
elseif (! isset ($config['id_user']) && isset ($_GET["login"])) {
	// Login process 
	include_once('include/functions_db.php');//Include it to use escape_string_sql function
	$config["auth_error"] = ""; //Set this to the error message from the authorization mechanism
	$nick = get_parameter_post ("nick"); //This is the variable with the login
	$pass = get_parameter_post ("pass"); //This is the variable with the password
	$nick = db_escape_string_sql($nick);
	$pass = db_escape_string_sql($pass);
	
	// process_user_login is a virtual function which should be defined in each auth file.
	// It accepts username and password. The rest should be internal to the auth file.
	// The auth file can set $config["auth_error"] to an informative error output or reference their internal error messages to it
	// process_user_login should return false in case of errors or invalid login, the nickname if correct
	$nick_in_db = process_user_login ($nick, $pass);
	
	if ($nick_in_db !== false) {
		$process_login = true;
		
		unset ($_GET["sec2"]);
		$_GET["sec"] = "general/logon_ok";
		db_logon ($nick_in_db, $_SERVER['REMOTE_ADDR']);
		$_SESSION['id_usuario'] = $nick_in_db;
		$config['id_user'] = $nick_in_db;
		//Remove everything that might have to do with people's passwords or logins
		unset ($_GET['pass'], $pass, $_POST['pass'], $_REQUEST['pass'], $login_good);
		
		$user_language = get_user_language ($config['id_user']);
		
		$l10n = NULL;
		if (file_exists ('./include/languages/'.$user_language.'.mo')) {
			$l10n = new gettext_reader (new CachedFileReader ('./include/languages/'.$user_language.'.mo'));
			$l10n->load_tables();
		}
	}
	else {
		// User not known
		$login_failed = true;
		require_once ('general/login_page.php');
		db_pandora_audit("Logon Failed", "Invalid login: ".$nick, $nick);
		while (@ob_end_flush ());
		exit ("</html>");
	}
}
elseif (! isset ($config['id_user'])) {
	// There is no user connected
	
	require_once ('general/login_page.php');
	while (@ob_end_flush ());
	exit ("</html>");
}

// Log off
if (isset ($_GET["bye"])) {
	include ("general/logoff.php");
	$iduser = $_SESSION["id_usuario"];
	db_logoff ($iduser, $_SERVER['REMOTE_ADDR']);
	// Unregister Session (compatible with 5.2 and 6.x, old code was deprecated
	unset($_SESSION['id_usuario']);
	unset($iduser);
	while (@ob_end_flush ());
	exit ("</html>");
}

/**
 * Load the basic configurations of extension and add extensions into menu.
 * Load here, because if not, some extensions not load well, I don't why.
 */
extensions_load_extensions ($config['extensions']);
if ($process_login) {
	 /* Call all extensions login function */
	extensions_call_login_function ();
	
	
	
	//Set the initial global counter for chat.
	users_get_last_global_counter('session');
}

//Get old parameters before navigation.
$old_sec = '';
$old_sec2 = '';
$old_page = '';
if (isset($_SERVER['HTTP_REFERER']))
	$old_page = $_SERVER['HTTP_REFERER'];
$chunks = explode('?', $old_page);
if (count($chunks) == 2) {
	$chunks = explode('&', $chunks[1]);
	
	foreach ($chunks as $chunk) {
		if (strstr($chunk, 'sec=') !== false) {
			$old_sec = str_replace('sec=', '', $chunk);
		}
		if (strstr($chunk, 'sec2=') !== false) {
			$old_sec = str_replace('sec2=', '', $chunk);
		}
	}
}

$_SESSION['new_chat'] = false;
if ($old_sec2 == 'operation/users/webchat') {
	users_get_last_global_counter('session');
}

if ($page == 'operation/users/webchat') {
	//Reload the global counter.
	users_get_last_global_counter('session');
}

if (isset($_SESSION['global_counter_chat']))
	$old_global_counter_chat = $_SESSION['global_counter_chat'];
else
	$old_global_counter_chat = users_get_last_global_counter('return');
$now_global_counter_chat = users_get_last_global_counter('return');

if ($old_global_counter_chat != $now_global_counter_chat) {
	if (!users_is_last_system_message())
		$_SESSION['new_chat'] = true;
}

// Display login help info dialog
if (get_parameter ('login', 0) == 1) {
	
	// If it's configured to not skip this
	if (!isset($config['skip_login_help_dialog']) or $config['skip_login_help_dialog'] == 0) {
		
		include_once("general/login_help_dialog.php");
		
	}
	
}

// Header
if ($config["pure"] == 0) {
	echo '<div id="container"><div id="head">';
	require ("general/header.php");
	echo '</div><div id="page"><div id="menu">';
	require ("general/main_menu.php");
	echo '</div>';
}
else {
	echo '<div id="main_pure">';
}

// http://es2.php.net/manual/en/ref.session.php#64525
// Session locking concurrency speedup!
session_write_close (); 


// Main block of content
if ($config["pure"] == 0) {
	echo '<div id="main">';
}



// Page loader / selector
if ($searchPage) {
	require ('operation/search_results.php');
}
else {
	if ($page != "") {
		$page .= '.php';
		// Enterprise ACL check
		if (enterprise_hook ('enterprise_acl', array ($config['id_user'], $sec, $sec2, true)) == false) {
			require ("general/noaccess.php");
		}
		elseif (file_exists ($page)) {
			if (! extensions_is_extension ($page)) {
				require_once($page);
			}
			else {
				if ($sec[0] == 'g')
					extensions_call_godmode_function (basename ($page));
				else
					extensions_call_main_function (basename ($page));
			}
		} 
		else echo '<br /><strong class="error">'.__('Sorry! I can\'t find the page!').'</strong>';
	}
	else require ("general/logon_ok.php");
}

if ($config["pure"] == 0) {
	echo '</div>'; // main
	echo '<div style="clear:both">&nbsp;</div>';
	echo '</div>'; // page (id = page)
}
else {
	echo "</div>"; // main_pure
}

if ($config["pure"] == 0) {
	echo '<div id="foot">';
	require ("general/footer.php");
	echo '</div>';
	echo '</div>'; //container div
}
while (@ob_end_flush ());

db_print_database_debug ();
echo '</html>';

$run_time = format_numeric (microtime (true) - $config['start_time'], 3);
echo "\n<!-- Page generated in $run_time seconds -->\n";
?>
