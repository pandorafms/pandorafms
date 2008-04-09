<?php

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@openideas.info
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas

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
<title>Pandora FMS - Upgrade Wizard</title>
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

<body>
<?php

echo "<h1>INSTALLER IS NOT WORKING YET. PLEASE DO NOT USE</h1>";
exit;

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

function migrate_data (){

	check_generic (1, "Updating tagente_datos table");
	$sql1="SELECT * FROM tagente_datos WHERE utimestamp = 0 ";
	$result1=mysql_query($sql1);
	while ($row1=mysql_fetch_array($result1)){
	        $id = $row1["id_agente_datos"];
	        $timestamp = $row1["timestamp"];
	        $utimestamp = strtotime($timestamp);
	        $sql2="UPDATE tagente_datos SET utimestamp = '$utimestamp' WHERE id_agente_datos = $id";
	        mysql_query($sql2);
	}
	flush();

	check_generic (1,"Updating tagente_datos_string table");
	$sql1="SELECT * FROM tagente_datos_string WHERE utimestamp = 0 ";
	$result1=mysql_query($sql1);
	while ($row1=mysql_fetch_array($result1)){
	        $id = $row1["id_tagente_datos_string"];
	        $timestamp = $row1["timestamp"];
	        $utimestamp = strtotime($timestamp);
	        $sql2="UPDATE tagente_datos SET utimestamp = '$utimestamp' WHERE id_tagente_datos_string = $id";
	        mysql_query($sql2);
	}
	flush();

	check_generic (1,"Updating tagente_estado table");
	$sql1="SELECT * FROM tagente_estado WHERE utimestamp = 0";
	$result1=mysql_query($sql1);
	while ($row1=mysql_fetch_array($result1)){
	        $id = $row1["id_agente_estado"];
	        $timestamp = $row1["timestamp"];
	        $utimestamp = strtotime($timestamp);
	        $sql2="UPDATE tagente_estado SET utimestamp = '$utimestamp', last_execution_try = '$utimestamp' WHERE id_agente_estado = $id";
	        mysql_query($sql2);
	}

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
	<div id='install_container'>
	<h1>Pandora FMS upgrade wizard. Step #1 of 5</h1>
	<div id='wizard' style='height: 390px;'>
		<div id='install_box'>
			<h1>Welcome to Pandora FMS 1.3 upgrade Wizard</h1>
			<p>This wizard helps you to quick upgrade Pandora FMS console in your system. This tool is <b>only</b> to 
upgrade Pandora FMS 1.2 to Pandora FMS 1.3</p>
			<p>For more information, please refer to documentation.</p>
			<i>Pandora FMS Development Team</i>
			<div class='info'>Before start with upgrade process. Please <b>STOP NOW all your Pandora FMS</b></div>";
		
		if (file_exists("include/config.php")){
			echo "<div class='warn'><b>Warning:</b> You already have a config.php file in this directory, please backup and move it before continue.</div>";
		}
		echo "<div class='warn'><b>Warning:</b> This upgrade tool will <b>overwrite and change</b> your existing Pandora FMS
<b>Database</b> and only could be used to upgrade fron Pandora FMS 1.2 to Pandora FMS 1.3. Before continue, please <b>be sure that you 
have made a SQL backup using mysqldump system tool as described in documentation.</b><br></div>";
		echo "
		</div>
		<div id='logo_img'>
			<img src='images/pandora_logo.png' border='0'><br>
			<img src='images/step0.png' border='0'>
		</div>
		<div id='install_img'>
			<a href='upgrade.php?step=2'><img align='right' src='images/arrow_next.png' border=0></a>
		</div>
	</div>
	<div id='foot_install'>
		<i>Pandora FMS is a Free Software project registered at <a target='_new' href='http://pandora.sourceforge.net'>SourceForge</a></i>
	</div>
	</div>";
}


function install_step2() {
	echo "
	<div id='install_container'>
	<h1>Pandora FMS upgrade wizard. Step #2 of 5</h1>
	<div id='wizard' style='height: 340px;'>
		<div id='install_box'>";
		echo "<h1>Checking software dependencies</h1>";
			echo "<table border=0 width=230>";
			$res = 0;
			$res += check_variable(phpversion(),"4.3","PHP version >= 4.3.x",1);
			$res += check_extension("mysql","PHP MySQL extension");
			//$res += check_extension("curl","PHP Curl extension");
			$res += check_extension("gd","PHP gd extension");
			$res += check_extension("snmp","PHP SNMP extension");
			$res += check_extension("session","PHP session extension");
			$res += check_include("PEAR.php","PEAR PHP Library");
			$step6 = is_writable("include");
			check_generic ($step6, "Write permissions to save config file in './include'");
			if ($step6 == 0)
				$res++;
			
			//$res += check_exists ("/usr/bin/pdflatex","PDF Latex in /usr/bin/pdflatex");
			echo "</table>
		</div>
		<div id='logo_img'>
			<img src='images/pandora_logo.png' border='0' alt=''><br>
			<img src='images/step1.png' border='0' alt=''>
		</div>
		<div id='install_img'>";
			if ($res > 0) {
				echo "<div class='warn'>You have some uncomplete 
				dependencies. Please correct them or this wizard tool 
				will not be able to finish your installation.
				</div><br>
				Ignore it. <a href='upgrade.php?step=3'>Ignore it and go to Step #3</a>";
			} else {
				echo "<a href='upgrade.php?step=3'><img align='right' src='images/arrow_next.png' border=0 alt=''></a>";
			}
			echo "
		</div>
	</div>
	<div id='foot_install'>
		<i>Pandora FMS is a Free Software project registered at 
		<a target='_new' href='http://pandora.sourceforge.net'>SourceForge</a></i>
	</div>
	</div>";
}


function install_step3() {
	echo "
	<div id='install_container'>
	<h1>Pandora FMS upgrade wizard. Step #3 of 5 </h1>
	<div id='wizard' style='height: 520px;'>
		<div id='install_box'>
			<h1>Environment and database setup</h1>
			<p>
			This wizard will transform your Pandora FMS database, and populate it with all the data needed to run for the first time, modifying existing data to be used by the new version.
			</p>
			<p>
			You need a user to modify and create database schema, this is usually the existant <b>pandora</b> user, you could check on config.php file of Pandora FMS 1.2 installation.
			</p>";
			echo "<form method='post' action='upgrade.php?step=4'>
				<div>DB User with privileges on MySQL</div>
				<input class='login' type='text' name='user' value='pandora'>

				<div>DB Password for this user</div>
				<input class='login' type='password' name='pass' value=''>
				
				<div>DB Hostname of MySQL</div>
				<input class='login' type='text' name='host' value='localhost'>

				<div>DB Name (pandora by default)</div>
				<input class='login' type='text' name='dbname' value='pandora'>

				<div>Full path to HTTP publication directory<br>
				<span class='f9b'>For example /var/www/pandora_console/. Needed for graphs and attachments.</span>
				</div>
				<input class='login' type='text' name='path' style='width: 190px;' value='/var/www/pandora_console/'>

				<div>Full local URL to Pandora FMS Console<br>
				<span class='f9b'>For example http://localhost/pandora_console</span>
				</div>
				<input class='login' type='text' name='url' style='width: 250px;' value='http://localhost/pandora_console'>
				
				<div align='right'><input type='image' src='images/arrow_next.png' value='Step #4' id='step4'></div>
			</form>
			</div>
			<div id='logo_img'>
				<img src='images/pandora_logo.png' border='0' alt=''><br>
				<img src='images/step2.png' border='0' alt=''>
			</div>
		</div>
		<div id='foot_install'>
			<i>Pandora FMS is a Free Software project registered at 
			<a target='_new' href='http://pandora.sourceforge.net'>SourceForge</a></i>
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
	$step1=0; $step2=0; $step3=0;
	$step4=0; $step5=0; $step6=0; $step7=0;
	echo "<div id='install_container'>
	<h1>Pandora FMS upgrade wizard. Step #4 of 5</h1>
	<div id='wizard' style='height: 540px;'>
		<div id='install_box'>
			<h1>Modifing database schema and adding data</h1>This could take a while... please wait</h2>
			<table>";
			if (! mysql_connect ($dbhost,$dbuser,$dbpassword)) {
				check_generic ( 0, "Connection with Database");
			} else {
				check_generic ( 1, "Connection with Database");
				// Create schema
				if (mysql_select_db($dbname))
					$step2 = 1;
				else
					$step2 = 0;
				check_generic ($step2, "Opening database '$dbname'");
				flush();
				$step3 = (parse_mysql_dump("pandoradb_12_to_13.sql"));
				check_generic ($step3, "Schema manipulation");
				flush();
				$step4 = parse_mysql_dump("pandoradbdata_12_to_13.sql");
				check_generic ($step4, "Populating new schema and converting data");
				flush();
				$cfgin = fopen ("include/config.inc.php","r");
				$cfgout = fopen ($pandora_config,"w");
				$config_contents = fread ($cfgin, filesize("include/config.inc.php"));
	
				$config_new = '<?php
// Begin of automatic config file
$dbname="'.$dbname.'";			// MySQL DataBase name
$dbuser="'.$dbuser.'";			// DB User
$dbpassword="'.$dbpassword.'";	// DB Password
$dbhost="'.$dbhost.'";			// DB Host
$config_homedir="'.$path.'";		// Config homedir
$BASE_URL="'.$url.'";			// Base URL
// End of automatic config file
?>';
				$step5 = fputs ($cfgout, $config_new);
				$step5 = $step5 + fputs ($cfgout, $config_contents);
				if ($step5 > 0)
					$step5 = 1;
				fclose ($cfgin);
				fclose ($cfgout);
				chmod ($pandora_config, 0600);
				check_generic ($step5, "Created new config file at '".$pandora_config."'");
				
			}
			if (($step5 + $step4 + $step3 + $step2 ) == 4) {
				$everything_ok = 1;
				migrate_data();
			} 

		echo "</table></div>
		<div id='logo_img'>
			<img src='images/pandora_logo.png' border='0' alt=''><br>
			<img src='images/step3.png' border='0' alt=''>
		</div>
		
		<div id='install_img'>";
			if ($everything_ok == 1) {
				echo "<br><br><a href='upgrade.php?step=5'><img align='right' src='images/arrow_next.png' border='0' alt=''></a>";
			} else {
				echo "<div class='warn'><b>There was some problems. Installation is not completed.</b> 
				<p>Please correct failures, and restore original DB before trying again.</div>";

				if (mysql_error() != "")
					echo "<div class='warn'> <b>ERROR:</b> ". mysql_error().".</div>";
			}		
		echo "
		</div>
	</div>
	<div id='foot_install'>
		<i>Pandora FMS is a Free Software project registered at 
		<a target='_new' href='http://pandora.sourceforge.net'>SourceForge</a></i>
	</div>
</div>";

}

function install_step5() {
	echo "
	<div id='install_container'>
	<h1>Pandora FMS upgrade wizard. Finished</h1>
	<div id='wizard' style='height: 300px;'>
		<div id='install_box'>
			<h1>Upgrade complete!</h1>
			<p>You now must delete manually installer and upgrade tool ('<i>install.php</i>, <i>upgrade.php</i>') files for security before trying to access to your Pandora FMS console.
			<p>Don't forget to check <a href='http://pandora.sourceforge.net'>http://pandora.sourceforge.net</a> for updates.
			<p><a href='index.php'>Click here to access to your Pandora FMS console</a></p>
		</div>
		<div id='logo_img'>
			<img src='images/pandora_logo.png' border='0'><br>
			<img src='images/step4.png' border='0'><br>
		</div>
	</div>
	<div id='foot_install'>
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
	case 6: install_step6();
		break;
	}
}

?>
</body>
</html>
