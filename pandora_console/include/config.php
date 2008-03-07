<?php
// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development, project architecture and management.
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.

// Database configuration (default ones)

$config["dbname"]="pandora";
$config["dbuser"]="pandora";
$config["dbpass"]="none";
$config["dbhost"]="localhost";

// This is used for reporting, please add "/" character at the end
$config["homedir"]="/var/www/pandora_console/";
$config["homeurl"]="/pandora_console/";

// Do not display any ERROR
//error_reporting(0); // Need to use active console at this moment

// Display ALL errors
error_reporting(E_ALL);

// This is directory where placed "/attachment" directory, to upload files stores. 
// This MUST be writtable by http server user, and should be in pandora root. 
// By default, Pandora adds /attachment to this, so by default is the pandora console home dir

$config["attachment_store"]=$config["homedir"];

// Default font used for graphics (a Free TrueType font included with Pandora FMS)
$config["fontpath"] = $config["homedir"]."/include/FreeSans.ttf";

// Style (pandora by default)
$config["style"] = "pandora";

include ("config_process.php");
?>
