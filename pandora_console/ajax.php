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
session_write_close ();
if (file_exists ($page)) {
	require_once ($page);
}
else {
	echo '<br /><b class="error">Sorry! I can\'t find the page '.$page.'!</b>';
}
?>
