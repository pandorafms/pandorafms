<?php
// Begin of automatic config file
$config["dbname"]="pandora";			// MySQL DataBase name
$config["dbuser"]="pandora";			// DB User
$config["dbpass"]="pandora";	// DB Password
$config["dbhost"]="localhost";			// DB Host
$config["homedir"]="/var/www/pandora_console/";		// Config homedir
$config["homeurl"]="http://localhost/pandora_console";			// Base URL
// End of automatic config file
?><?php
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
// Database configuration (default ones)

// Default values

// $config["dbname"]="pandora";
// $config["dbuser"]="pandora";
// $config["dbpass"]="pandora";
// $config["dbhost"]="localhost";

// This is used for reporting, please add "/" character at the end
// $config["homedir"]="/var/www/pandora_console/";
// $config["homeurl"]="/pandora_console/";

// Do not display any ERROR
//error_reporting(0); // Need to use active console at this moment

// Display ALL errors
error_reporting(E_ALL);



include ("config_process.php");
?>
