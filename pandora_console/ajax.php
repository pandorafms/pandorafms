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


if ((! file_exists("include/config.php")) || (! is_readable("include/config.php"))) {
        exit;
}

require ('include/config.php');

// Check for correct language file presence
if (file_exists ('include/languages/language_'.$config['language'].'.php')) {
	include 'include/languages/language_'.$config['language'].'.php';
} else {
	include "include/languages/language_en.php";
}

require ('include/functions.php');
require ('include/functions_db.php');

// Real start
session_start();

// Check user
check_login ();

define ('AJAX', true);

$page = (string) get_parameter ('page');
$page .= '.php';
session_write_close ();
if (file_exists ($page)) {
	$id_user = $_SESSION["id_usuario"];
	require ($page);
} else {
	echo "<br><b class='error'>Sorry! I can't find the page $page!</b>";
}
?>
