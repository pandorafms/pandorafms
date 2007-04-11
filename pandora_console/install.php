<?php

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@openideas.info
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas
// Copyright (c) 2004-2007 Raul Mateos Martin, raulofpandora@gmail.com
// Copyright (c) 2006-2007 Jose Navarro jose@jnavarro.net
// Copyright (c) 2006-2007 Jonathan Barajas, jonathan.barajas[AT]gmail[DOT]com

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation version 2
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
<meta http-equiv="content-type" content="text/html; charset=ISO-8859-15">
<meta name="resource-type" content="document">
<meta name="distribution" content="global">
<meta name="author" content="Sancho Lerena, Raul Mateos">
<meta name="copyright" content="This is GPL software. Created by Sancho Lerena and others">
<meta name="keywords" content="pandora, fms, monitoring, network, system, GPL, software">
<meta name="robots" content="index, follow">
<link rel="icon" href="images/pandora.ico" type="image/ico">
<link rel="stylesheet" href="include/styles/pandora.css" type="text/css">
</head><body background='images/backgrounds/background11.jpg'>
<?PHP

error_reporting(0);

function check_extension ( $ext, $label ){
	echo "<tr><td>";
	echo "<img src='images/arrow.gif'> $label";
	echo "</td><td>";
	if (!extension_loaded($ext)){
		echo "<img src='images/dot_red.gif'>";
		return 1;
	} else {
		echo "<img src='images/dot_green.gif'>";
		return 0;
	}
	echo "</td></tr>";
}

function check_include ( $ext, $label ){
	echo "<tr><td>";
	echo "<img src='images/arrow.gif'> $label";
	echo "</td><td>";
	if (!include($ext)){
		echo "<img src='images/dot_red.gif'>";
		return 1;
	} else {
		echo "<img src='images/dot_green.gif'>";
		return 0;
	}
	echo "</td></tr>";
}

function check_exists ( $file, $label ){
	echo "<tr><td>";
	echo "<img src='images/arrow.gif'> $label";
	echo "</td><td>";
	if (!file_exists ($file)){
		echo " <img src='images/dot_red.gif'>";
		return 1;
	} else {
		echo " <img src='images/dot_green.gif'>";
		return 0;
	}
	echo "</td></tr>";
}

function check_generic ( $ok, $label ){
	echo "<tr><td>";
	echo "<img src='images/arrow.gif'> $label";
	echo "</td><td>";
	if ($ok == 0 ){
		echo " <img src='images/dot_red.gif'>";
		return 1;
	} else {
		echo " <img src='images/dot_green.gif'>";
		return 0;
	}
	echo "</td></tr>";
}

function check_variable ( $var, $value, $label, $mode ){
	echo "<tr><td>";
	echo "<img src='images/arrow.gif'> $label";
	echo "</td><td>";
	if ($mode == 1){
		if ($var >= $value){
			echo " <img src='images/dot_green.gif'>";
			return 0;
		} else {
			echo " <img src='images/dot_red.gif'>";
			return 1;
		}
	} elseif ($var == $value){
			echo " <img src='images/dot_green.gif'>";
			return 0;
	} else {
		echo " <img src='images/dot_red.gif'>";
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
					if (!$result = mysql_query($query))
						return 0;
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
	<div align='center' class='mt35'>
	<h1>Pandora FMS instalation wizard. Step #1 of 4</h1>
	<div id='wizard' style='height: 310px;'>
		<div id='install_box'>
			<h1>Welcome to Pandora FMS installation Wizard</h1>
			<p>This wizard helps you to quick install Pandora FMS console in your system.</p>
			<p>In three steps checks all dependencies and make your configuration for a quick installation.</p> 
			<p>For more information, please refer to documentation.</p>
			<i>Pandora FMS Development team</i>
		";
		if (file_exists("include/config.php")){
			echo "<p><img src='images/info.png' valign='bottom'><b> Warning: You already have a config.php file. Configuration and database would be overwritten if you continued.</b></p>";
		}
		echo "
		</div>
		<div class='box'>
			<img src='images/logo_login.gif' border='0'><br>			
		</div>
		<div class='box'>
			<img src='images/step0.png' border='0'>
		</div>
		<div id='install_box' style='margin-bottom: 25px;margin-left: 25px;'>
			<a href='install.php?step=2'><img align='right' src='images/arrow_next.png' border=0></a>
			</div>
		</div>
		<div id='foot'>
			<i>Pandora FMS is a Free Software project registered at 
			<a target='_new' href='http://pandora.sourceforge.net'>SourceForge</a></i>
		</div>
	</div>";
}



function install_step2() {
	echo "
	<div align='center' class='mt35'>
	<h1>Pandora FMS console instalation wizard. Step #2 of 4</h1>
	<div id='wizard' style='height: 250px;'>
		<div id='install_box'>";
		echo "<h1>Checking software dependencies</h1>";
			echo "<table border=0 width=230>";
			$res = 0;
			$res += check_variable(phpversion(),"4.3","PHP version >= 4.3.x",1);
			$res += check_extension("mysql","PHP MySQL extension");
			//$res += check_extension("curl","PHP Curl extension");
			$res += check_extension("gd","PHP gd extension");
			$res += check_extension("snmp","PHP smmp extension");
			$res += check_extension("session","PHP session extension");
			$res += check_include("PEAR.php","PEAR PHP Library");
			//$res += check_exists ("/usr/bin/pdflatex","PDF Latex in /usr/bin/pdflatex");
			echo "</table>
		</div>
		<div class='box'>
			<img src='images/logo_login.gif' border='0'' alt=''>
		</div>
		<div class='box'>
			<img src='images/step1.png' border='0' alt=''>
		</div>
		<div id='install_box' style='margin-bottom: 25px;margin-left: 25px;'>";
			if ($res > 0) {
				echo "<p><img src='images/info.png'> You have some uncomplete 
				dependencies. Please correct them or this installer 
				will not be able to finish your installation.
				</p>
				Ignore it. <a href='install.php?step=3'>Force install Step #3</a>";
			} else {
				echo "<a href='install.php?step=3'><img align='right' src='images/arrow_next.png' border=0 alt=''></a>";
			}
			echo "
		</div>
		</div>
		</div>
		<div id='foot'>";
	echo '<i>Pandora FMS is a Free Software project registered at <a target="_new" href="http://pandora.sourceforge.net">SourceForge</a></i>';
	echo "</div></div>";
}

function install_step3() {
	echo "
	<div align='center' class='mt35'>
	<h1>Pandora FMS console instalation wizard. Step #3 of 4 </h1>
	<div id='wizard' style='height: 540px;'>
		<div id='install_box'>
			<h1>Environment and database setup</h1>
			<p>
			This wizard will create your Pandora FMS database, and populate it with all the data needed to run for the first time. 
			You need a privileged user to create database schema, this is usually root user. 
			Information about <i>root</i> user will not be used or stored anymore.
			</p>
			<p>
			Now, please, complete all details to configure your database and enviroment setup. <b>NOTICE</b> that database will be destroyed if it already exists!.
			</p>
			<form method='post' action='install.php?step=4'>
				<div>DB User with privileges on MySQL</div>
				<input class='login' type='text' name='user' value='root'>

				<div>DB Password for this user</div>
				<input class='login' type='passwordzx' name='pass' value=''>
				
				<div>DB Hostname of MySQL</div>
				<input class='login' type='text' name='host' value='localhost'>

				<div>DB Name (pandora by default)</div>
				<input class='login' type='text' name='dbname' value='pandora'>

				<div>Full path to HTTP publication directory.<br>
				<span class='f9b'>For example /var/www/pandora_console/</span>
				</div>
				<input class='login' type='text' name='path' style='width: 190px;' value='/var/www/pandora_console/'>

				<div>Full local URL to Pandora FMS Console. <br>
				<span class='f9b'>For example http://localhost/pandora_console</span>
				</div>
				<input class='login' type='text' name='url' style='width: 250px;'  value='http://localhost/pandora_console'>
				
				<div><input align='right' style='align: right; width:70px; height: 16px;' type='image' src='images/arrow_next.png'  value='Step #4'></div>
			</form>
			</div>
			<div class='box'>
				<img src='images/logo_login.gif' border='0' alt=''>
			</div>
			<div class='box'>
				<img src='images/step2.png' border='0' alt=''>
			</div>
		</div>
		<div id='foot'>
			<i>Pandora FMS is a Free Software project registered at 
			<a target='_ne' href='http://pandora.sourceforge.net'>SourceForge</a></i>
		</div>
	</div>";
}



function install_step4() {
	$pandora_config = "include/config.php";

	if ( (! isset($_POST["user"])) || (! isset($_POST["dbname"])) || (! isset($_POST["host"])) || (! isset($_POST["pass"])) ) {
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
	<div align='center' class='mt35'>
	<h1>Pandora FMS Console instalation wizard. Step #4 of 4 </h1>
	<div id='wizard' style='height: 320px;'>
		<div id='install_box'>
			<h1>Creating database and default configuration file</h1>
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
					$step5 = mysql_query ("GRANT ALL PRIVILEGES ON $dbname.* to pandora@localhost IDENTIFIED BY '".$random_password."'");
					mysql_query ("FLUSH PRIVILEGES");
					check_generic ($step5, "Established privileges for user pandora <br> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;password <i>'$random_password'</i>");
	
					$step6 = is_writable("include");
					check_generic ($step6, "Write permissions to save config file in './include'");
						
					$cfgin = fopen ("include/config.inc.php","r");
					$cfgout = fopen ($pandora_config,"w");
					$config_contents = fread ($cfgin, filesize("include/config.inc.php"));
	
					$config_new = '<?PHP
// Begin of automatic config file
$dbname="'.$dbname.'";			// MySQL DataBase name
$dbuser="pandora";			// DB User
$dbpassword="'.$random_password.'";	// DB Password
$dbhost="'.$dbhost.'";			// DB Host
$config_homedir="'.$path.'";		// Config homedir
$BASE_URL="'.$url.'";			// Base URL
// End of automatic config file
?>';
					$step7 = fputs ($cfgout, $config_new);
					$step7 = $step7 + fputs ($cfgout, $config_contents);
					if ($step7 > 0)
						$step7 = 1;
					fclose ($cfgin);
					fclose ($cfgout);
					check_generic ($step7, "Created new config file at '".$pandora_config."'");
				}
			}
			if (($step7 + $step6 + $step5 + $step4 + $step3 + $step2 + $step1) == 7) {
				$everything_ok = 1;
			}
		echo "</table></div>
		<div class='box'>
			<img src='images/logo_login.gif' border='0' alt=''>
		</div>
		<div class='box'>
			<img src='images/step3.png' border='0' alt=''>
		</div>
		
		<div id='install_box' style='margin-bottom: 25px;margin-left: 25px;'>";
			if ($everything_ok == 1) {
				echo "<a href='install.php?step=5'><img align='right' src='images/arrow_next.png' border=0 class=''></a>";
			} else {
				echo "<img src='images/info.png'> You got some problems. Installation is not completed. 
				<p>Please correct failures before trying again. All database schemes created in this step have been dropped.</p>";

				if (mysql_error() != "")
					echo "<p><img src='images/info.png'> <b>ERROR:</b> ". mysql_error()."</p>";

				mysql_query ("DROP DATABASE $dbname");
			}		
		echo "
		</div>
	</div>
	<div id='foot'>
		<i>Pandora FMS is a Free Software project registered at 
		<a target='_new' href='http://pandora.sourceforge.net'>SourceForge</a></i>
	</div>
</div>";
}

function install_step5() {
	echo "
	<div align='center' class='mt35'>
	<h1>Pandora FMS console instalation wizard. Finished</h1>
	<div id='wizard' style='height: 300px;'>
		<div id='install_box'>
			<h1>Installation complete</h1>
			<p>You now must delete manually this installer for security, ('install.php') before trying to access to your Pandora FMS console.
			<p>Don't forget to check <a href='http://pandora.sourceforge.net'>http://pandora.sourceforge.net</a> for updates.
			<p><a href='index.php'>Click here to access to your Pandora FMS console</A></p>
		</div>
		<div class='box'>
			<img src='images/logo_login.gif' border='0'></a><br>			
		</div>
		<div class='box'>
			<img src='images/step4.png' border='0'><br>
		</div>
	</div>
	<div id='foot'>
		<i>Pandora FMS is a Free Software project registered at 
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
