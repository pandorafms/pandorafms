<?php

// Pandora - the Free monitoring system
// ====================================
// Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2006 Artica Soluciones Tecnologicas, info@artica.es
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

// This is the base config file

//Pandora Version
$build_version="PC061208"; //PCyymmdd
$pandora_version="v1.3 Beta 1"; 

// Database configuration
$dbname="pandora";	// MySQL DataBase
$dbuser="pandora";	// DB User 
$dbpassword="pandora";	// Password
$dbhost="localhost";	// MySQL Host
$dbtype="mysql"; 	// Type of Database, now only "mysql" its supported
$attachment_store="/var/www/pandora_console";	//This is directory where placed "attachment" directory, to upload files stores. This MUST be writtable by wwwserver user, and should be in pandora root. Please append "/" to the end :-)
$config_fontpath = "../reporting/FreeSans.ttf";	// Change this to your font folder, if needed.

// Do not display any ERROR
// error_reporting(0);

// Display all errores, warnings and notices
error_reporting(E_ALL);

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
mysql_select_db($dbname);
$result2=mysql_query("SELECT * FROM tconfig");
while ($row2=mysql_fetch_array($result2)){
	switch ($row2["token"]) {
		case "language_code": $language_code=$row2["value"];
						break;
		case "block_size": $block_size=$row2["value"];
						break;
		case "days_purge": $days_purge=$row2["value"];
						break;
		case "days_compact": $days_compact=$row2["value"];
						break;
		case "graph_res": $config_graph_res=$row2["value"];
						break;
		case "step_compact": $config_step_compact=$row2["value"];
						break;
		case "truetype": $config_truetype=$row2["value"];
						break;
		case "graph_order": $config_graph_order=$row2["value"];
						break;
		case "bgimage": $config_bgimage=$row2["value"];
						break;
	}
}
if ($language_code == 'ast_es') {
	$help_code='ast';
	}
else $help_code = substr($language_code,0,2);

?>
