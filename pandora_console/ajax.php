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

if ((! file_exists("include/config.php")) || (! is_readable("include/config.php"))) {
	exit;
}

require_once ('include/config.php');
require_once ('include/functions.php');
require_once ('include/functions_db.php');
require_once ('include/auth/mysql.php');

// Real start
session_start ();

// Hash login process
if (isset ($_GET["loginhash"])) {
	
	$loginhash_data = get_parameter("loginhash_data", "");
	$loginhash_user = get_parameter("loginhash_user", "");
	
	if ($config["loginhash_pwd"] != ""
		&& $loginhash_data == md5($loginhash_user.$config["loginhash_pwd"])) {
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

// Check user
check_login ();

define ('AJAX', true);

/* Enterprise support */
if (file_exists (ENTERPRISE_DIR."/load_enterprise.php")) {
	include_once (ENTERPRISE_DIR."/load_enterprise.php");
}

$config["remote_addr"] = $_SERVER['REMOTE_ADDR'];

$page = (string) get_parameter ('page');
$page = safe_url_extraclean ($page);
$page .= '.php';
$config["id_user"] = $_SESSION["id_usuario"];
$isFunctionSkins = enterprise_include_once ('include/functions_skins.php');
if ($isFunctionSkins !== ENTERPRISE_NOT_HOOK)
	$config["relative_path"] = enterprise_hook('skins_set_image_skin_path',array($config['id_user']));

// Load user language
$user_language = get_user_language ($config['id_user']);

$l10n = NULL;
if (file_exists ('./include/languages/'.$user_language.'.mo')) {
	$l10n = new gettext_reader (new CachedFileReader ('./include/languages/'.$user_language.'.mo'));
	$l10n->load_tables();
}

if (isset($_SERVER['HTTP_REFERER'])) {
	// Not cool way of know if we are executing from metaconsole or normal console
	if (strpos($_SERVER['HTTP_REFERER'], ENTERPRISE_DIR . '/meta/') !== false)
		define ('METACONSOLE', true);
}
session_write_close ();

if (file_exists ($page)) {
	require_once ($page);
}
else {
	echo '<br /><b class="error">Sorry! I can\'t find the page '.$page.'!</b>';
}
?>
