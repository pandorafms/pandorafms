<?php
// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development, project architecture and management.
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.

//Pandora Version
if (!isset($build_version))
	$build_version="PC080221";
if (!isset($pandora_version))
	$pandora_version="v1.4-dev";
	
// Database configuration (default ones)
//$dbname="pandora";		// MySQL DataBase
//$dbuser="pandora";		// DB User 
//$dbpassword="pandora";	// Password
//$dbhost="localhost";		// MySQL Host

// This is used for reporting, please add "/" character at the end
//$config_homedir = "/var/www/pandora/";

// Do not display any ERROR
//error_reporting(0); // Need to use active console at this moment

// Display ALL errors
error_reporting(E_ERROR);

// This is directory where placed "/attachment" directory, to upload files stores. 
// This MUST be writtable by http server user, and should be in pandora root. 
// By default, Pandora adds /attachment to this, so by default is the pandora console home dir

$attachment_store=$config_homedir;

// Default font used for graphics (a Free TrueType font included with Pandora FMS)
$config_fontpath = $config_homedir."/reporting/FreeSans.ttf";

// Style (pandora by default)
$config_style = "pandora";

// Read remaining config tokens from DB
if (! mysql_connect($dbhost,$dbuser,$dbpassword)){ 

//Non-persistent connection. If you want persistent conn change it to mysql_pconnect()
	exit ('<html><head><title>Pandora Error</title>
	<link rel="stylesheet" href="./include/styles/pandora.css" type="text/css">
	</head><body><div align="center">
	<div id="db_f">
		<div>
		<a href="index.php"><img src="images/pandora_logo.png" border="0"></a>
		</div>
	<div id="db_ftxt">
		<h1 id="log_f" class="error">Pandora Console Error DB-001</h1>
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
if($result2=mysql_query("SELECT * FROM tconfig")){
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
		case "style": $config_style=$row2["value"];
                        break;
		}
	}
} else {
	 exit ('<html><head><title>Pandora Error</title>
	         <link rel="stylesheet" href="./include/styles/pandora.css" type="text/css">
	         </head><body><div align="center">
	         <div id="db_f">
                 <div>
                 <a href="index.php"><img src="images/pandora_logo.png" border="0"></a>
                 </div>
	         <div id="db_ftxt">
                 <h1 id="log_f" class="error">Pandora Console Error DB-002</h1>
                 Cannot load configuration variables. Please check your database setup in the
                 <b>./include/config.php</b> file and read documentation.<i><br><br>
                  Probably database schema is created but there are no data inside it or you have a problem with DB access credentials.
                 </i><br>
	         </div>
	         </div></body></html>');
}	


if ($language_code == 'ast_es') {
	$help_code='ast';
	}
else $help_code = substr($language_code,0,2);

?>
