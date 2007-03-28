<?php
// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development and project architecture and management
// Copyright (c) 2004-2007 Raul Mateos Martin, raulofpandora@gmail.com
// CSS and some PHP additions
// Copyright (c) 2006-2007 Jonathan Barajas, jonathan.barajas[AT]gmail[DOT]com
// Javascript Active Console code.
// Copyright (c) 2006 Jose Navarro <contacto@indiseg.net>
// Additions to Pandora FMS 1.2 graph code and new XML reporting template management
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas, info@artica.es
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
// This is the base config file

//Pandora Version
$build_version="PC070328"; //PCyymmdd
$pandora_version="v1.3 devel"; 

// Database configuration
$dbname="pandora";	// MySQL DataBase
$dbuser="pandora";	// DB User 
$dbpassword="pandora";	// Password
$dbhost="localhost";	// MySQL Host
$dbtype="mysql"; 	// Type of Database, now only "mysql" its supported
$attachment_store="/var/www/pandora_console";	//This is directory where placed "attachment" directory, to upload files stores. This MUST be writtable by wwwserver user, and should be in pandora root. Please append "/" to the end :-)
$config_fontpath = "../reporting/FreeSans.ttf";	// Change this to your font folder, if needed.

// Do not display any ERROR
//error_reporting(E_ALL);
error_reporting(E_ALL);


// Uncomment next  to Display all errors, warnings and notices
// error_reporting(E_ALL);

// Read rest of config from DB
if (! mysql_connect($dbhost,$dbuser,$dbpassword)){ 
//Non-persistent connection. If you want persistent conn change it to mysql_pconnect()
	exit ('<html><head><title>Pandora Error</title>
	<link rel="stylesheet" href="./include/styles/pandora.css" type="text/css">
	</head><body><div align="center">
	<div id="db_f">
		<div>
		<a href="index.php"><img src="images/logo_menu.gif" border="0"></a>
		</div>
	<div id="db_ftxt">
		<h1 id="db_fh1" class="error">Pandora Console Error DB-001</h1>
		Cannot connect with Database, please check your database setup in the 
		<b>./include/config.php</b> file and read documentation.<i><br><br>
		Probably any of your user/database/hostname values are incorrect or 
		database is not running.</i><br><br><font class="error">
		<b>MySQL ERROR:</b> '. mysql_error().'</font>
		<br>&nbsp;
	</div>
	</div></body></html>');
}

// Default values for config
$language_code = "en";
$block_size = 25;
$days_purge = 30;
$days_compact = 7;
$config_graph_res = 3;
$config_step_compact = 1;
$config_bgimage = "background4.jpg";
$config_show_unknown = 0;
$config_show_lastalerts = 0;

mysql_select_db($dbname);
$result2=mysql_query("SELECT * FROM tconfig");
while ($row2=mysql_fetch_array($result2)){
	switch ($row2["token"]) {
		case "language_code": $language_code = $row2["value"];
			break;
		case "block_size": $block_size = $row2["value"];
			break;
		case "days_purge": $days_purge = $row2["value"];
			break;
		case "days_compact": $days_compact = $row2["value"];
			break;
		case "graph_res": $config_graph_res = $row2["value"];
			break;
		case "show_unknown": $config_show_unknown = $row2["value"];
			break;
		case "show_lastalerts": $config_show_lastalerts = $row2["value"];
			break;
	}
}

// Adjist helpcode from language_code
if ($language_code == 'ast_es')
	$help_code = 'ast';
else
	$help_code = substr($language_code,0,2);

?>
