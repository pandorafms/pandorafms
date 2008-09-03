<?php
// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
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

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Pandora FMS - Installation Wizard</title>
<meta http-equiv="expires" content="0">
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<meta name="resource-type" content="document">
<meta name="distribution" content="global">
<meta name="author" content="Sancho Lerena, Raul Mateos">
<meta name="copyright" content="This is GPL software. Created by Sancho Lerena and others">
<meta name="keywords" content="pandora, fms, monitoring, network, system, GPL, software">
<meta name="robots" content="index, follow">
<link rel="icon" href="images/pandora.ico" type="image/ico">
<link rel="stylesheet" href="include/styles/pandora_minimal.css" type="text/css">
<link rel="stylesheet" href="include/styles/install.css" type="text/css">
</head>
<body bgcolor="#555555">

<?php

error_reporting(0);

function check_extension ( $ext, $label ){
	echo "<tr><td>";
	echo "<span class='arr'> $label </span>";
	echo "</td><td>";
	if (!extension_loaded($ext)){
		echo "<img src='images/dot_red.png'>";
		return 1;
	} else {
		echo "<img src='images/dot_green.png'>";
		return 0;
	}
	echo "</td></tr>";
}

function check_include ( $ext, $label ){
	echo "<tr><td>";
	echo "<span class='arr'> $label </span>";
	echo "</td><td>";
	if (!include($ext)){
		echo "<img src='images/dot_red.png'>";
		return 1;
	} else {
		echo "<img src='images/dot_green.png'>";
		return 0;
	}
	echo "</td></tr>";
}

function check_exists ( $file, $label ){
	echo "<tr><td>";
	echo "<span class='arr'> $label </span>";
	echo "</td><td>";
	if (!file_exists ($file)){
		echo " <img src='images/dot_red.png'>";
		return 1;
	} else {
		echo " <img src='images/dot_green.png'>";
		return 0;
	}
	echo "</td></tr>";
}

function check_generic ( $ok, $label ){
	echo "<tr><td>";
	echo "<span class='arr'> $label </span>";
	echo "</td><td>";
	if ($ok == 0 ){
		echo " <img src='images/dot_red.png'>";
		return 1;
	} else {
		echo " <img src='images/dot_green.png'>";
		return 0;
	}
	echo "</td></tr>";
}

function check_writable ( $fullpath, $label ){
	echo "<tr><td>";
	echo "<span class='arr'> $label </span>";
	echo "</td><td>";
	if (file_exists($fullpath))
		if (is_writable($fullpath)){
			echo " <img src='images/dot_green.png'>";
			echo "</td></tr>";
			return 0;
		} else {
			echo " <img src='images/dot_red.png'>";
			echo "</td></tr>";
			return 1;
		}
	else {
		echo " <img src='images/dot_red.png'>";
		echo "</td></tr>";
		return 1;
	}

}


function check_variable ( $var, $value, $label, $mode ){
	echo "<tr><td>";
	echo "<span class='arr'> $label </span>";
	echo "</td><td>";
	if ($mode == 1){
		if ($var >= $value){
			echo " <img src='images/dot_green.png'>";
			return 0;
		} else {
			echo " <img src='images/dot_red.png'>";
			return 1;
		}
	} elseif ($var == $value){
			echo " <img src='images/dot_green.png'>";
			return 0;
	} else {
		echo " <img src='images/dot_red.png'>";
		return 1;
	}
	echo "</td></tr>";
}

function parse_mysql_dump($url){
	if (file_exists($url)){
   		$file_content = file($url);
   		$query = "";
   		foreach($file_content as $sql_line){
			if(trim($sql_line) != "" && strpos($sql_line, "--") === false){
				$query .= $sql_line;
				if(preg_match("/;[\040]*\$/", $sql_line)){
                			//echo "DEBUG $query <br>"; //Uncomment for debug
					if (!$result = mysql_query($query)) {
					//	echo mysql_errno() . ": " . mysql_error(); //Uncomment for debug
						return 0;
					}
					$query = "";
				}
			}
		}
		return 1;
	} else {
		return 0;
	}
}

function random_name ($size){
	$temp = "";
	for ($a=0;$a< $size;$a++)
		$temp = $temp. chr(rand(122,97));
	return $temp;
}

function install_step1() {
	echo "
	<div id='install_container'>
	<h1>Pandora FMS installation wizard. Step #1 of 4</h1>
	<div id='wizard' style='height: 490px;'>
		<div id='install_box'>
			<h2>Welcome to Pandora FMS 2.0 installation Wizard</h2>
			<p>This wizard helps you to quick install Pandora FMS console in your system.</p>
			<p>In four steps checks all dependencies and make your configuration 
			for a quick installation.</p>
			<p>For more information, please refer to documentation.</p>
			<i>Pandora FMS Development Team</i>
		";
		if (file_exists("include/config.php")){
			echo "<div class='warn'><b>Warning:</b> You already have a config.php file. 
			Configuration and database would be overwritten if you continued.</div>";
		}
		echo "<table width=100%>";
		$writable = check_writable ( "include", "Checking if ./config is writable");
		if (file_exists("include/config.php"))
			$writable += check_writable ( "include/config.php", "Checking if include/config.php is writable");
		echo "</table>";

		echo "<div class='warn'><b>Warning:</b> This installer will <b>overwrite and destroy</b> 
		your existing Pandora FMS configuration and <b>Database</b>. Before continue, 
		please <b>be sure that you have no valuable Pandora FMS data in your Database</b>.<br>
		</div>";

		echo "<div class='info'><b>Upgrade</b>: 
		If you want to upgrade from Pandora FMS 1.3.x to 2.0 version, 
		please download the migration tool from our website at 
		<a href='http://www.pandorafms.com'>PandoraFMS.com web site</a>.</div>";
		
		echo "
		</div>
		<div id='logo_img'>
			<img src='images/pandora_logo.png' border='0'><br>
			<img src='images/step0.png' border='0'>
		</div>
		<div id='install_img'>";
		if ($writable == 0)
			echo "
			<a href='install.php?step=2'><img align='right' src='images/arrow_next.png' border='0'></a>";
		else
			echo "<div class='warn'><b>ERROR:</b>You need to setup permissions to be able to write in ./include directory</div>";
		echo "
		</div>
	</div>
	<div id='foot_install'>
			<i>Pandora FMS is an OpenSource Software project registered at 
		<a target='_new' href='http://pandora.sourceforge.net'>SourceForge</a></i>
	</div>
	</div>";
}


function install_step2() {
	echo "
	<div id='install_container'>
	<h1>Pandora FMS console installation wizard. Step #2 of 4</h1>
	<div id='wizard' style='height: 390px;'>
		<div id='install_box'>";
		echo "<h2>Checking software dependencies</h2>";
			echo "<table border=0 width=230>";
			$res = 0;
			$res += check_variable(phpversion(),"4.3","PHP version >= 4.3.x",1);
			$res += check_extension("mysql","PHP MySQL extension");
			$res += check_extension("gd","PHP gd extension");
			$res += check_extension("snmp","PHP SNMP extension");
			$res += check_extension("session","PHP session extension");
			$res += check_extension("gettext","PHP gettext extension");
			$res += check_include("PEAR.php","PEAR PHP Library");
			$res += check_include("DB.php","PEAR:DB PHP Library");
			$res += check_include("XML/RPC.php","PEAR XML/RPC.php PHP Library");
			$res += check_exists ("/usr/bin/twopi","Graphviz Binary");

			echo "</table>
		</div>
		<div id='logo_img'>
			<img src='images/pandora_logo.png' border='0'' alt=''><br>
			<img src='images/step1.png' border='0' alt=''>
		</div>
		<div id='install_img'>";
			if ($res > 0) {
				echo "
				<div class='warn'>You have some incomplete 
				dependencies. Please correct them or this installer 
				will not be able to finish your installation.
				</div>
				Ignore it. <a href='install.php?step=3'>Force install Step #3</a>";
			} else {
				echo "<a href='install.php?step=3'>
				<img align='right' src='images/arrow_next.png' border='0' alt=''></a>";
			}
			echo "
		</div>
	</div>
	<div id='foot_install'>
			<i>Pandora FMS is an OpenSource Software project registered at 
		<a target='_new' href='http://pandora.sourceforge.net'>SourceForge</a></i>
	</div>
	</div>";
}


function install_step3() {
	echo "
	<div id='install_container'>
	<h1>Pandora FMS console installation wizard. Step #3 of 4 </h1>
	<div id='wizard' style='height: 640px;'>
		<div id='install_box'>
			<h2>Environment and database setup</h2>
			<p>
			This wizard will create your Pandora FMS database, 
			and populate it with all the data needed to run for the first time.
			</p>
			<p>
			You need a privileged user to create database schema, this is usually <b>root</b> user.
			Information about <b>root</b> user will not be used or stored anymore.
			</p>
			<p>
			Now, please, complete all details to configure your database and environment setup.
			</p>
			<div class='warn'>
			<b>Warning:</b> This installer will <b>overwrite and destroy</b> your existing 
			Pandora FMS configuration and <b>Database</b>. Before continue, 
			please <b>be sure that you have no valuable Pandora FMS data in your Database.</b>
			<br><br>
			</div>
			<form method='post' action='install.php?step=4'>
				<div>DB User with privileges on MySQL</div>
				<input class='login' type='text' name='user' value='root'>

				<div>DB Password for this user</div>
				<input class='login' type='password' name='pass' value=''>
				
				<div>DB Hostname of MySQL</div>
				<input class='login' type='text' name='host' value='localhost'>

				<div>DB Name (pandora by default)</div>
				<input class='login' type='text' name='dbname' value='pandora'>

				<div>Full path to HTTP publication directory<br>
					<span class='f9b'>For example /var/www/pandora_console/. 
					Needed for graphs and attachments.
					</span>
				</div>
				<input class='login' type='text' name='path' style='width: 190px;' 
				value='".dirname (__FILE__)."'>

				<div>URL path to Pandora FMS Console<br>
				<span class='f9b'>For example '/pandora_console'</span>
				</div>
				<input class='login' type='text' name='url' style='width: 250px;' 
				value='".dirname ($_SERVER['PHP_SELF'])."'>
				
				<div align='right'>
				<input type='image' src='images/arrow_next.png' value='Step #4' id='step4'>
				</div>
			</form>
			</div>
			<div id='logo_img'>
				<img src='images/pandora_logo.png' border='0' alt=''><br>
				<img src='images/step2.png' border='0' alt=''>
			</div>
		</div>
		<div id='foot_install'>
			<i>Pandora FMS is an OpenSource Software project registered at 
			<a target='_new' href='http://pandora.sourceforge.net'>SourceForge</a></i>
		</div>
	</div>";
}


function install_step4() {
	$pandora_config = "include/config.php";

	if ( (! isset($_POST["user"])) || (! isset($_POST["dbname"])) || (! isset($_POST["host"])) || 
	(! isset($_POST["pass"])) ) {
		$dbpassword = "";
		$dbuser = "";
		$dbhost = "";
		$dbname = "";
	} else {
		$dbpassword = $_POST["pass"];
		$dbuser = $_POST["user"];
		$dbhost = $_POST["host"];
		$dbname = $_POST["dbname"];
		if (isset($_POST["url"]))
			$url = $_POST["url"];
		else
			$url = "http://localhost";
		if (isset($_POST["path"]))
			$path = $_POST["path"];
		else
			$path = "/var/www";
	}
	$everything_ok = 0;
	$step1=0;
	$step2=0;
	$step3=0;
	$step4=0; $step5=0; $step6=0; $step7=0;
	echo "
	<div id='install_container'>
	<h1>Pandora FMS Console installation wizard. Step #4 of 4</h1>
	<div id='wizard' style='height: 380px;'>
		<div id='install_box'>
			<h2>Creating database and default configuration file</h2>
			<table>";
			if (! mysql_connect ($dbhost,$dbuser,$dbpassword)) {
				check_generic ( 0, "Connection with Database");
			} else {
				check_generic ( 1, "Connection with Database");
				// Create schema
				$step1 = mysql_query ("CREATE DATABASE $dbname");
				check_generic ($step1, "Creating database '$dbname'");
				if ($step1 == 1){
					$step2 = mysql_select_db($dbname);
					check_generic ($step2, "Opening database '$dbname'");
	
					$step3 = parse_mysql_dump("pandoradb.sql");
					check_generic ($step3, "Creating schema");
			
					$step4 = parse_mysql_dump("pandoradb_data.sql");
					check_generic ($step4, "Populating database");
	
					$random_password = random_name (8);
					$step5 = mysql_query ("GRANT ALL PRIVILEGES ON $dbname.* to pandora@localhost 
					IDENTIFIED BY '".$random_password."'");
					mysql_query ("FLUSH PRIVILEGES");
					check_generic ($step5, "Established privileges for user pandora <br> 
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;password <i>'$random_password'</i>");
	
					$step6 = is_writable("include");
					check_generic ($step6, "Write permissions to save config file in './include'");
						
					$cfgin = fopen ("include/config.inc.php","r");
					$cfgout = fopen ($pandora_config,"w");
					$config_contents = fread ($cfgin, filesize("include/config.inc.php"));
					$config_new = '<?php
// Begin of automatic config file
$config["dbname"]="'.$dbname.'";			// MySQL DataBase name
$config["dbuser"]="pandora";			// DB User
$config["dbpass"]="'.$random_password.'";	// DB Password
$config["dbhost"]="'.$dbhost.'";			// DB Host
$config["homedir"]="'.$path.'";		// Config homedir
$config["homeurl"]="'.$url.'";			// Base URL
// End of automatic config file
?>';
					$step7 = fputs ($cfgout, $config_new);
					$step7 = $step7 + fputs ($cfgout, $config_contents);
					if ($step7 > 0)
						$step7 = 1;
					fclose ($cfgin);
					fclose ($cfgout);
					chmod ($pandora_config, 0600);
					check_generic ($step7, "Created new config file at '".$pandora_config."'");
				}
			}
			if (($step7 + $step6 + $step5 + $step4 + $step3 + $step2 + $step1) == 7) {
				$everything_ok = 1;
			}
		echo "</table></div>
		<div id='logo_img'>
			<img src='images/pandora_logo.png' border='0' alt=''><br>
			<img src='images/step3.png' border='0' alt=''>
		</div>
		
		<div id='install_img'>";
			if ($everything_ok == 1) {
				echo "<br><br><a href='install.php?step=5'>
				<img align='right' src='images/arrow_next.png' border='0' alt=''></a>";
			} else {
				echo "<div class='warn'><b>There was some problems.
				Installation is not completed.</b> 
				<p>Please correct failures before trying again.
				All database schemes created in this step have been dropped. 
				Try to reload this page if you have a present Pandora FMS configuration.</p>
				</div>";

				if (mysql_error() != "")
					echo "<div class='warn'> <b>ERROR:</b> ". mysql_error().".</div>";

				mysql_query ("DROP DATABASE $dbname");
			}		
		echo "
		</div>
	</div>
	<div id='foot_install'>
			<i>Pandora FMS is an OpenSource Software project registered at 
		<a target='_new' href='http://pandora.sourceforge.net'>SourceForge</a></i>
	</div>
</div>";
}


function install_step5() {
	echo "
	<div id='install_container'>
	<h1>Pandora FMS console installation wizard. Finished</h1>
	<div id='wizard' style='height: 300px;'>
		<div id='install_box'>
			<h2>Installation complete</h2>
			<p>You now must delete manually this installer ('<i>install.php</i>') 
			file for security before trying to access to your Pandora FMS console.
			<p>You should also install the Pandora FMS Servers before trying to monitor anything, 
			please read documentation on how to install it.</p>
			<p>Don't forget to check <a href='http://pandorafms.com'>http://pandorafms.com</a> 
			for updates.
			<p><br><b><a href='index.php'>Click here to access to your Pandora FMS console</a></b>
			</p>
		</div>
		<div id='logo_img'>
			<img src='images/pandora_logo.png' border='0'><br>
			<img src='images/step4.png' border='0'><br>
		</div>
	</div>
	<div id='foot_install'>
			<i>Pandora FMS is an OpenSource Software project registered at 
		<a target='_new' href='http://pandora.sourceforge.net'>SourceForge</a></i>
	</div>
</div>";
}


// ---------------
// Main page code
// ---------------

if (! isset($_GET["step"])){
	install_step1();
} else {
	$step = $_GET["step"];
	switch ($step) {
	case 2: install_step2();
		break;
	case 3: install_step3();
		break;
	case 4: install_step4();
		break;
	case 5: install_step5();
		break;
	}
}

?>
</body>
</html>
