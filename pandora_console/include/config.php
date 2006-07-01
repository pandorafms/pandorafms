<?php

// Pandora - The Free Monitoring System
// This code is protected by GPL license.
// Este codigo esta protegido por la licencia GPL.
// Sancho Lerena <slerena@gmail.com>, 2003-2006
// Raul Mateos <raulofpandora@gmail.com>, 2005-2006

// Base config file

//Pandora Version

$build_version="PC060630"; //PCddmmyy
$pandora_version="v1.2 Beta 2"; 

// Database configuration
$dbname="pandora";	// MySQL DataBase
$dbuser="pandora";	// DB User 
$dbpassword="pandora";	// Password
$dbhost="localhost";	// MySQL Host
$dbtype="mysql"; 	// Type of Database, now only "mysql" its supported
$attachment_store="/var/www/pandora_console";	//This is directory where placed "attachment" directory, to upload files stores. This MUST be writtable by wwwserver user, and should be in pandora root. Please append "/" to the end :-)
$config_fontpath = "/usr/share/fonts/truetype/msttcorefonts/arial.ttf";


// Read rest of config from DB
if (! mysql_pconnect($dbhost,$dbuser,$dbpassword)){ //Persistent connection. If you want non-persistent conn change it to mysql_connect()
	exit ('<html><head><title>Pandora Error</title><link rel="stylesheet" href="./include/styles/pandora.css" type="text/css"></head><body><div align="center">
<div id="db_f"><div><a href="index.php"><img src="images/logo_menu.gif" border="0"></a></div><div id="db_ftxt"><h1 id="db_fh1" class="error">Pandora Console Error DB-001</h1>Cannot connect with Database, please check your database setup in the <b>./include/config.php</b> file and read documentation.<i><br><br>Probably any of your user/database/hostname values are incorrect or database is not running.</i><br><br><font class="error"><b>MySQL ERROR:</b> '. mysql_error().'</font><br>&nbsp;</div></div></body></html>');
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
?>
