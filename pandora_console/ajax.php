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

if ((! file_exists("include/config.php")) || (! is_readable("include/config.php"))) {
	exit;
}

require_once ('include/config.php');
require_once ('include/functions.php');
require_once ('include/functions_db.php');

if (!isset ($config["auth"])) {
	require_once ("include/auth/mysql.php");
} else {
	require_once ("include/auth/".$config["auth"]["scheme"].".php");
}

// Real start
session_start ();

// Check user
check_login ();

define ('AJAX', true);

$config["remote_addr"] = $_SERVER['REMOTE_ADDR'];

$page = (string) get_parameter ('page');
$page = safe_url_extraclean ($page);
$page .= '.php';
$config["id_user"] = $_SESSION["id_usuario"];
session_write_close ();
if (file_exists ($page)) {
	require_once ($page);
} else {
	echo '<br /><b class="error">Sorry! I can\'t find the page '.$page.'!</b>';
}
?>
