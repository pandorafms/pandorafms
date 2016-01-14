<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2012 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<title>Pandora FMS - Installation Wizard</title>
		<meta http-equiv="expires" content="0">
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<meta name="resource-type" content="document">
		<meta name="distribution" content="global">
		<meta name="author" content="Pandora FMS Development Team">
		<meta name="copyright" content="This is GPL software. Created by Sancho Lerena and many others">
		<meta name="keywords" content="pandora, fms, monitoring, network, system, GPL, software">
		<meta name="robots" content="index, follow">
		<link rel="icon" href="images/pandora.ico" type="image/ico">
		<link rel="stylesheet" href="include/styles/install.css" type="text/css">
	</head>
	<script type="text/javascript">
		options_text = new Array('An existing Database','A new Database');
		options_values = new Array('db_exist','db_new');
		function ChangeDBDrop(causer) {
			if (causer.value != 'db_exist') {
				window.document.step2_form.drop.checked = 0;
				window.document.step2_form.drop.disabled = 1;
			}
			else {
				window.document.step2_form.drop.disabled = 0;
			}
		}
		function ChangeDBAction(causer) {
			var i = 0;
			if (causer.value == 'oracle') {
				window.document.step2_form.db_action.length = 1;
			}
			else {
				window.document.step2_form.db_action.length = 2;
			}
			while (i < window.document.step2_form.db_action.length) {
				window.document.step2_form.db_action.options[i].value = options_values[i];
				window.document.step2_form.db_action.options[i].text = options_text[i];
				i++;
			}
			window.document.step2_form.db_action.options[window.document.step2_form.db_action.length-1].selected=1;
			ChangeDBDrop(window.document.step2_form.db_action);
		}
	</script>
	<body>
		<div style='height: 10px'>
			<?php
$version = '6.1dev';
$build = '160114';
			$banner = "v$version Build $build";
			
			error_reporting(0);
			
			// ---------------
			// Main page code
			// ---------------
			
			if (! isset($_GET["step"])) {
				install_step1();
			}
			else {
				$step = $_GET["step"];
				switch ($step) {
					case 11: install_step1_licence();
						break;
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
		</div>
	</body>
</html>

<?php
function check_extension ( $ext, $label ) {
	echo "<tr><td>";
	echo "<span class='arr'> $label </span>";
	echo "</td><td>";
	if (!extension_loaded($ext)) {
		echo "<img src='images/dot_red.png'>";
		return 1;
	}
	else {
		echo "<img src='images/dot_green.png'>";
		return 0;
	}
	echo "</td></tr>";
}

function check_include ( $ext, $label ) {
	echo "<tr><td>";
	echo "<span class='arr'> $label </span>";
	echo "</td><td>";
	if (!include($ext)) {
		echo "<img src='images/dot_red.png'>";
		return 1;
	}
	else {
		echo "<img src='images/dot_green.png'>";
		return 0;
	}
	echo "</td></tr>";
}

function check_exists ( $file, $label ) {
	echo "<tr><td>";
	echo "<span class='arr'> $label </span>";
	echo "</td><td>";
	if (!file_exists ($file)) {
		echo " <img src='images/dot_red.png'>";
		return 1;
	}
	else {
		echo " <img src='images/dot_green.png'>";
		return 0;
	}
	echo "</td></tr>";
}

function check_generic ( $ok, $label ) {
	echo "<tr><td>";
	echo "<span class='arr'> $label </span>";
	echo "</td><td>";
	if ($ok == 0 ) {
		echo " <img src='images/dot_red.png'>";
		return 1;
	}
	else {
		echo " <img src='images/dot_green.png'>";
		return 0;
	}
	echo "</td></tr>";
}

function check_writable ( $fullpath, $label ) {
	echo "<tr><td>";
	echo "<span class='arr'> $label </span>";
	echo "</td><td>";
	if (file_exists($fullpath))
		if (is_writable($fullpath)) {
			echo " <img src='images/dot_green.png'>";
			echo "</td></tr>";
			return 0;
		}
		else {
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

function check_variable ( $var, $value, $label, $mode ) {
	echo "<tr><td>";
	echo "<span class='arr'> $label </span>";
	echo "</td><td>";
	if ($mode == 1) {
		if ($var >= $value) {
			echo " <img src='images/dot_green.png'>";
			return 0;
		}
		else {
			echo " <img src='images/dot_red.png'>";
			return 1;
		}
	}
	elseif ($var == $value) {
		echo " <img src='images/dot_green.png'>";
		return 0;
	}
	else {
		echo " <img src='images/dot_red.png'>";
		return 1;
	}
	echo "</td></tr>";
}

function parse_mysql_dump($url) {
	if (file_exists($url)) {
		$file_content = file($url);
		$query = "";
		foreach($file_content as $sql_line) {
			if (trim($sql_line) != "" && strpos($sql_line, "--") === false) {
				$query .= $sql_line;
				if(preg_match("/;[\040]*\$/", $sql_line)) {
					if (!$result = mysql_query($query)) {
						echo mysql_error(); //Uncomment for debug
						echo "<i><br>$query<br></i>";
						return 0;
					}
					$query = "";
				}
			}
		}
		return 1;
	}
	else
		return 0;
}

function parse_postgresql_dump($connection, $url, $debug = false) {
	if (file_exists($url)) {
		$file_content = file($url);
		
		$query = "";
		
		foreach ($file_content as $sql_line) {
			$clean_line = trim($sql_line);
			$comment = preg_match("/^(\s|\t)*--.*$/", $clean_line);
			if ($comment) {
				continue;
			}
			
			if (empty($clean_line)) {
				continue;
			}
			
			$query .= $clean_line;
			
			//Check if the end of query with the the semicolon and any returns in the end of line
			if(preg_match("/;[\040]*\$/", $clean_line)) {
				//And execute and clean buffer
				pg_send_query($connection, $query);
				
				$result = pg_get_result($connection);
				
				if ($debug) {
					var_dump($query);
					var_dump(pg_result_error($result));
				}
				
				if (pg_result_status($result) == PGSQL_FATAL_ERROR) {
					echo pg_result_error($result);
					echo "<i><br>$query<br></i>";
					
					return 0;
				}
				
				$query = "";
			}
		}
		
		return 1;
	}
	else {
		return 0;
	}
}

function parse_oracle_dump($connection, $url, $debug = false) {
	if (file_exists($url)) {
		$file_content = file($url);
		
		$query = "";
		$plsql_block = false;
		
		$datetime_tz_format = oci_parse($connection, 'alter session set NLS_TIMESTAMP_TZ_FORMAT =\'YYYY-MM-DD HH24:MI:SS\'');
		$datetime_format = oci_parse($connection, 'alter session set NLS_TIMESTAMP_FORMAT =\'YYYY-MM-DD HH24:MI:SS\'');
		$date_format = oci_parse($connection, 'alter session set NLS_DATE_FORMAT =\'YYYY-MM-DD HH24:MI:SS\'');
		$decimal_separator = oci_parse($connection, 'alter session set NLS_NUMERIC_CHARACTERS =\',.\'');

		oci_execute($datetime_tz_format);
		oci_execute($datetime_format);
		oci_execute($date_format);
		oci_execute($decimal_separator);

		oci_free_statement($datetime_tz_format);
		oci_free_statement($datetime_format);
		oci_free_statement($date_format);
		oci_free_statement($decimal_separator);
		
		foreach ($file_content as $sql_line) {
			$clean_line = trim($sql_line);
			$comment = preg_match("/^(\s|\t)*--.*$/", $clean_line);
			if ($comment) {
				continue;
			}
			
			if (empty($clean_line)) {
				continue;
			}
			
			//Support for PL/SQL blocks
			if (preg_match("/^BEGIN$/", $clean_line)) {
				$query .= $clean_line . ' ';
				$plsql_block = true;
			}
			else {
				$query .= $clean_line;
			}
			
			//Check query's end with a back slash and any returns in the end of line or if it's a PL/SQL block 'END;;' string
			if ((preg_match("/;[\040]*\$/", $clean_line) && !$plsql_block) || 
				(preg_match("/^END;;[\040]*\$/", $clean_line) && $plsql_block)) {
				$plsql_block = false;
				//Execute and clean buffer
				
				//Delete the last semicolon from current query
				$query = substr($query, 0, strlen($query) - 1);
				$sql = oci_parse($connection, $query);
				$result = oci_execute($sql);
				
				if ($debug) {
					var_dump($query);
				}
				
				if (!$result) {
					$e = oci_error($sql);
					echo "<tr><td><div class='warn'>Errors creating schema:</div><div style=\"overflow:auto; height:50px;\" >";
					echo htmlentities($e['message'], ENT_QUOTES);
					echo "<i><br>$query<br></i>";
					echo "</div></td></tr>";
					
					return 0;
				}
				
				$query = "";
				oci_free_statement($sql);
			}
		}
		
		return 1;
	}
	else {
		return 0;
	}
}

function oracle_drop_all_objects ($connection) {
	//Drop all objects of the current installation
	$stmt = oci_parse($connection,
		"BEGIN " .
			"FOR cur_rec IN (SELECT object_name, object_type " . 
				"FROM user_objects " .
					"WHERE  object_type IN ('TABLE', 'VIEW', 'PACKAGE', 'PROCEDURE', 'FUNCTION', 'SEQUENCE', 'SNAPSHOT', 'MATERIALIZED VIEW')) LOOP " .
				"BEGIN " . 
					"IF cur_rec.object_type = 'TABLE' THEN " .
						"EXECUTE IMMEDIATE 'DROP ' || cur_rec.object_type || ' \"' || cur_rec.object_name || '\" CASCADE CONSTRAINTS'; " .
					"ELSE " .
						"EXECUTE IMMEDIATE 'DROP ' || cur_rec.object_type || ' \"' || cur_rec.object_name || '\"'; " .
					"END IF; " .
				"EXCEPTION " .
					"WHEN OTHERS THEN " .
						"DBMS_OUTPUT.put_line('FAILED: DROP ' || cur_rec.object_type || ' \"' || cur_rec.object_name || '\"'); " .
					"END; " .
			"END LOOP; " .
		"END; ");
	
	$result = oci_execute($stmt);
	oci_free_statement($stmt);
	
	return 0;
}

function random_name ($size) {
	$temp = "";
	for ($a=0;$a< $size;$a++)
		$temp = $temp. chr(rand(122,97));
	
	return $temp;
}

function print_logo_status ($step, $step_total) {
	global $banner;
	
	return "
		<div id='logo_img'>
			<div>
			<img src='images/pandora_tinylogo_open.png' border='0'><br>
			<span style='font-size: 9px;'>$banner</span>
			</div>
			<div class='installation_step'>
			<b>Install step $step of $step_total</b>
			</div>
		</div>";
}

//
// This function adjusts path settings in pandora db for FreeBSD.
//
// All packages and configuration files except operating system's base files
// are installed under /usr/local in FreeBSD. So, path settings in pandora db
// for some programs should be changed from the Linux default. 
//
function adjust_paths_for_freebsd($engine, $connection = false) {

	$adjust_sql = array(
			"update trecon_script set script = REPLACE(script,'/usr/share','/usr/local/share');",
			"update tconfig set value = REPLACE(value,'/usr/bin','/usr/local/bin') where token='netflow_daemon' OR token='netflow_nfdump' OR token='netflow_nfexpire';",
			"update talert_commands set command = REPLACE(command,'/usr/bin','/usr/local/bin');",
			"update talert_commands set command = REPLACE(command,'/usr/share', '/usr/local/share');",
			"update tplugin set execute = REPLACE(execute,'/usr/share','/usr/local/share');",
			"update tevent_response set target = REPLACE(target,'/usr/share','/usr/local/share');",
			"insert into tconfig (token, value) VALUES ('graphviz_bin_dir', '/usr/local/bin');"
			);

	for ($i = 0; $i < count ($adjust_sql); $i++) {
		switch ($engine) {
			case 'mysql':
				$result = mysql_query($adjust_sql[$i]);
				break;
			case 'oracle':
				//Delete the last semicolon from current query
				$query = substr($adjust_sql[$i], 0, strlen($adjust_sql[$i]) - 1);
				$sql = oci_parse($connection, $query);
				$result = oci_execute($sql);
				break;
			case 'pgsql':
				pg_send_query($connection, $adjust_sql[$i]);
				$result = pg_get_result($connection);
				break;
		}
		if (!$result) {
			return 0;
		}
	}

	return 1;
}

function install_step1() {
	global $banner;
	
	echo "
	<div id='install_container'>
	<div id='wizard'>
	" . print_logo_status (1,6) . "
		<div id='install_box'>
			<h2>Welcome to Pandora FMS installation Wizard</h2>
			<p>This wizard helps you to quick install Pandora FMS console and main database in your system.</p>
			<p>In four steps, this installer will check all dependencies and will create your configuration, ready to use.</p>
			<p>For more information, please refer to documentation.<br>
			<i>Pandora FMS Development Team</i></p>
		";
		if (file_exists("include/config.php")) {
			echo "<div class='warn'><b>Warning:</b> You already have a config.php file. 
			Configuration and database would be overwritten if you continued.</div>";
		}
		echo "<br>";
		echo "<table width=100%>";
		$writable = check_writable ( "include", "Checking if ./include is writable");
		if (file_exists("include/config.php"))
			$writable += check_writable ( "include/config.php", "Checking if include/config.php is writable");
		echo "</table>";
		
		echo "<div class='warn'><b>Warning:</b> This installer will <b>overwrite and destroy</b> 
		your existing Pandora FMS configuration and <b>Database</b>. Before continue, 
		please <b>be sure that you have no valuable Pandora FMS data in your Database</b>.<br>
		</div>";
		
		echo "<div class='info'><b>Upgrade</b>: 
		If you want to upgrade from Pandora FMS 4.x to 5.0 version, please use the migration tool inside /extras directory in this setup.
		</div>";

		if ($writable == 0) {
			echo "</div>";
			echo "<a id='step11' href='install.php?step=11'><img align='right' style='margin-top: 20px;' src='images/arrow_next.png' border='0'></a>";
		}
		else {
			echo "<div class='err'><b>ERROR:</b>You need to setup permissions to be able to write in ./include directory</div>";
			echo "</div>";
		}
				
		echo "<div style='clear:both;'></div>";
		echo "
	</div>
	<div id='foot_install'>
		<i>Pandora FMS is an OpenSource Software project registered at 
		<a target='_new' href='http://pandora.sourceforge.net'>SourceForge</a></i>
	</div>
	</div>";
}

function install_step1_licence() {
	echo "
	<div id='install_container'>
	<div id='wizard'>
	" . print_logo_status (2,6) . "
		<div id='install_box'>
			<h2>GPL2 Licence terms agreement</h2>
			<p>Pandora FMS is an OpenSource software project licensed under the GPL2 licence. Pandora FMS includes, as well, another software also licensed under LGPL and BSD licenses. Before continue, <i>you must accept the licence terms.</i>.
			<p>For more information, please refer to our website at http://pandorafms.org and contact us if you have any kind of question about the usage of Pandora FMS</p>
<p>If you dont accept the licence terms, please, close your browser and delete Pandora FMS files.</p>
		";
	
	if (!file_exists("COPYING")) {
		echo "<div class='warn'><b>Licence file 'COPYING' is not present in your distribution. This means you have some 'partial' Pandora FMS distribution. We cannot continue without accepting the licence file.</b>";
		echo "</div>";
	}
	else {
		echo "<form method=post action='install.php?step=2'>";
		echo "<textarea name='gpl2' cols=52 rows=15 style='width: 100%;'>";
		echo file_get_contents ("COPYING");
		echo "</textarea>";
		echo "<p>";
		echo "<div style='text-align: right;'><input type=submit value='Yes, I accept licence terms'></div>";
	}
	echo "</div>";
		
	echo "</div>
	<div style='clear: both;height: 1px;'><!-- --></div>
	<div id='foot_install'>
		<i>Pandora FMS is an OpenSource Software project registered at 
		<a target='_new' href='http://pandora.sourceforge.net'>SourceForge</a></i>
	</div>
	</div>";
}

function install_step2() {
	
	echo "
	<div id='install_container'>
	<div id='wizard'>
	" . print_logo_status (3,6) . "
		<div id='install_box'>";
		echo "<h2>Checking software dependencies</h2>";
			echo "<table border=0 width=230>";
			$res = 0;
			$res += check_variable(phpversion(),"5.2","PHP version >= 5.2",1);
			$res += check_extension("gd","PHP GD extension");
			$res += check_extension("ldap","PHP LDAP extension");
			$res += check_extension("snmp","PHP SNMP extension");
			$res += check_extension("session","PHP session extension");
			$res += check_extension("gettext","PHP gettext extension");
			$res += check_extension("mbstring","PHP Multibyte String");
			$res += check_extension("zip","PHP Zip");
			$res += check_extension("zlib","PHP Zlib extension");
			$res += check_extension("json","PHP json extension");
			$res += check_extension("curl","CURL (Client URL Library)");
			if (PHP_OS == "FreeBSD") {
				$res += check_exists ("/usr/local/bin/twopi","Graphviz Binary");
			}
			else if (PHP_OS == "NetBSD") {
				$res += check_exists ("/usr/pkg/bin/twopi","Graphviz Binary");
			}
			else if ( substr(PHP_OS, 0, 3) == 'WIN' ) {
				$res += check_exists ("..\\..\\..\\Graphviz\\bin\\twopi.exe", "Graphviz Binary");
			}
			else {
				$res += check_exists ("/usr/bin/twopi","Graphviz Binary");
			}
			
			echo "<tr><td>";
			echo "<span style='display: block; font-family: verdana,arial,sans;
				font-size: 8.5pt;margin-top: 2px; font-weight: bolder;'>DB Engines</span>";
			echo "</td><td>";
			echo "</td></tr>";
			check_extension("mysql", "PHP MySQL extension");
			check_extension("pgsql", "PHP PostgreSQL extension");
			check_extension("oci8", "PHP Oracle extension");
			echo "</table>";
			
			if ($res > 0) {
				echo "
				<div class='err'>You have some incomplete 
				dependencies. Please correct them or this installer 
				will not be able to finish your installation.
				</div>
				<div class='err'>
					Remember, if you install any PHP module to comply
					with these dependences, you <b>need to restart</b>
					your HTTP/Apache server after it to use the new
					modules.
				</div>
				<div style='text-align: right;'>
				Ignore it. <a href='install.php?step=3' style='font-weight: bolder;'>Force install Step #3</a>
				</div>";
				echo "</div>";
			}
			else {
				echo "</div>";
				echo "<a id='step3' href='install.php?step=3'><br>
				<img align='right' src='images/arrow_next.png' border='0' alt=''></a>
				<div style='clear: both;'><!-- --></div>";
			}
			echo "
		</div>
		<div style='clear: both;'><!-- --></div>
	</div>
	<div id='foot_install'>
		<i>Pandora FMS is an OpenSource Software project registered at 
		<a target='_new' href='http://pandora.sourceforge.net'>SourceForge</a></i>
	</div>
	</div>";
}


function install_step3() {
	$options = '';
	if (extension_loaded("mysql")) {
		$options .= "<option value='mysql'>MySQL</option>";
	}
	if (extension_loaded("pgsql")) {
		$options .= "<option value='pgsql'>PostgreSQL</option>";
	}
	if (extension_loaded("oci8")) {
		$options .= "<option value='oracle'>Oracle</option>";
	}
	
	$error = false;
	if (empty($options)) {
		$error = true;
	}
	
	echo "
	<div id='install_container'>
	<div id='wizard'>
	" . print_logo_status (4,6) . "
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
			You can also deploy the scheme into an existing Database. 
			In this case you need a privileged Database user and password of that instance. 
			</p>
			<p>
			Now, please, complete all details to configure your database and environment setup.
			</p>
			<div class='warn'>
			<b>Warning:</b> This installer will <b>overwrite and destroy</b> your existing 
			Pandora FMS configuration and <b>Database</b>. Before continue, 
			please <b>be sure that you have no valuable Pandora FMS data in your Database.</b>
			<br><br>
			</div>";
			
			if (extension_loaded("oci8")) {
				echo  " <div class='warn'>For Oracle installation an existing Database with a privileged user is needed.</div>";
			}
	if (!$error) {
		echo "<form method='post' name='step2_form' action='install.php?step=4'>";
	}
	
	echo "<table cellpadding=6 width=100% border=0 style='text-align: left;'>";
	echo "<tr><td>";
	echo "DB Engine<br>";
	
	
	if ($error) {
		echo "
			<div class='warn'>
			<b>Warning:</b> You haven't a any DB engine with PHP. Please check the previous step to DB engine dependencies.
			</div>";
	}
	else {
		echo "<select name='engine' onChange=\"ChangeDBAction(this)\">";
		echo $options;
		echo "</select>";
		
		echo "<td>";
		echo " Installation in <br>";
		echo "<select name='db_action' onChange=\"ChangeDBDrop(this)\">";
		echo "<option value='db_new'>A new Database</option>";
		echo "<option value='db_exist'>An existing Database</option>";
		echo "</select>";
	}
	echo "		<tr><td>DB User with privileges<br>
				<input class='login' type='text' name='user' value='root' size=20>
				
				<td>DB Password for this user<br>
				<input class='login' type='password' name='pass' value='' size=20>
				
				<tr><td>DB Hostname<br>
				<input class='login' type='text' name='host' value='localhost' size=20>
				
				<td>DB Name (pandora by default)<br>
				<input class='login' type='text' name='dbname' value='pandora' size=20>
				
				<tr><td valign=top>
				Drop Database if exists<br>
				<input class='login' type='checkbox' name='drop' value=1>				
				
				
				<td>Full path to HTTP publication directory<br>
					<span style='font-size: 9px'>For example /var/www/pandora_console/</span>
				<br>
				<input class='login' type='text' name='path' style='width: 240px;' 
				value='".dirname (__FILE__)."'>
				
				<tr><td></td><td>URL path to Pandora FMS Console<br>
				<span style='font-size: 9px'>For example '/pandora_console'</span>
				</br>
				<input class='login' type='text' name='url' style='width: 250px;' 
				value='".dirname ($_SERVER["SCRIPT_NAME"])."'>
			</table>
			";
	

	echo "</div>";
	
	if (!$error) {
		echo "<input type='image' src='images/arrow_next.png' value='Step #4' id='step4'>";
	}
	
	echo "</form>";
	
	echo "<div style='clear:both;'></div>";
	echo "</div>
		<div id='foot_install'>
			<i>Pandora FMS is an OpenSource Software project registered at 
			<a target='_new' href='http://pandora.sourceforge.net'>SourceForge</a></i>
		</div>
	</div>";
}

function install_step4() {
	$pandora_config = "include/config.php";
	
	if ( (! isset($_POST["user"])) || (! isset($_POST["dbname"])) || (! isset($_POST["host"])) || 
	(! isset($_POST["pass"])) || (!isset($_POST['engine'])) || (! isset($_POST["db_action"])) ) {
		$dbpassword = "";
		$dbuser = "";
		$dbhost = "";
		$dbname = "";
		$engine = "";
		$dbaction = "";
	}
	else {
		$engine = $_POST['engine'];
		$dbpassword = $_POST["pass"];
		$dbuser = $_POST["user"];
		$dbhost = $_POST["host"];
		$dbaction = $_POST["db_action"];
		if (isset($_POST["drop"]))
			$dbdrop = $_POST["drop"];
		else
			$dbdrop = 0;
		
		$dbname = $_POST["dbname"];
		if (isset($_POST["url"]))
			$url = $_POST["url"];
		else
			$url = "http://localhost";
		if (isset($_POST["path"])) {
			$path = $_POST["path"];
			$path = str_replace("\\", "/", $path); // Windows compatibility
		}
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
	<div id='wizard'>
	" . print_logo_status(5,6) . "
		<div id='install_box'>
			<h2>Creating database and default configuration file</h2>
			<table width='100%'>";
			switch ($engine) {
				case 'mysql':
					if (! mysql_connect ($dbhost, $dbuser, $dbpassword)) {
						check_generic ( 0, "Connection with Database");
					}
					else {
						check_generic ( 1, "Connection with Database");
						
						// Drop database if needed and don't want to install over an existing DB
						if ($dbdrop == 1) {
							mysql_query ("DROP DATABASE IF EXISTS `$dbname`");
						}
						
						// Create schema
						if ($dbaction == 'db_new' || $dbdrop == 1) {
							$step1 = mysql_query ("CREATE DATABASE `$dbname`");
							check_generic ($step1, "Creating database '$dbname'");
						}
						else {
							$step1 = 1;
						}
						if ($step1 == 1) {
							$step2 = mysql_select_db($dbname);
							check_generic ($step2, "Opening database '$dbname'");
							
							$step3 = parse_mysql_dump("pandoradb.sql");
							check_generic ($step3, "Creating schema");
							
							$step4 = parse_mysql_dump("pandoradb_data.sql");
							check_generic ($step4, "Populating database");
							if (PHP_OS == "FreeBSD") {
								$step_freebsd = adjust_paths_for_freebsd ($engine);
								check_generic ($step_freebsd, "Adjusting paths in database for FreeBSD");
							}
							
							$random_password = random_name (8);
							$host = 'localhost';
							if ($dbhost != 'localhost')
								$host = $_SERVER['SERVER_ADDR'];
							$step5 = mysql_query ("GRANT ALL PRIVILEGES ON `$dbname`.* to pandora@$host 
								IDENTIFIED BY '".$random_password."'");
							mysql_query ("FLUSH PRIVILEGES");
							check_generic ($step5, "Established privileges for user pandora. A new random password has been generated: <b>$random_password</b><div class='warn'>Please write it down, you will need to setup your Pandora FMS server, editing the </i>/etc/pandora/pandora_server.conf</i> file</div>");
							
							$step6 = is_writable("include");
							check_generic ($step6, "Write permissions to save config file in './include'");
							
							$cfgin = fopen ("include/config.inc.php","r");
							$cfgout = fopen ($pandora_config,"w");
							$config_contents = fread ($cfgin, filesize("include/config.inc.php"));
							$dbtype = 'mysql';
							$config_new = '<?php
							// Begin of automatic config file
							$config["dbtype"] = "' . $dbtype . '"; //DB type (mysql, postgresql...in future others)
							$config["dbname"]="'.$dbname.'";			// MySQL DataBase name
							$config["dbuser"]="pandora";			// DB User
							$config["dbpass"]="'.$random_password.'";	// DB Password
							$config["dbhost"]="'.$dbhost.'";			// DB Host
							$config["homedir"]="'.$path.'";		// Config homedir
							/*
							----------Attention--------------------
							Please note that in certain installations:
								- reverse proxy.
								- web server in other ports.
								- https
							
							This variable might be dynamically altered.
							
							But it is save as backup in the
							$config["homeurl_static"]
							for expecial needs.
							----------Attention--------------------
							*/
							$config["homeurl"]="'.$url.'";			// Base URL
							$config["homeurl_static"]="'.$url.'";			// Don\'t  delete
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
					break;
				case 'oracle':
					$connection = oci_connect($dbuser, $dbpassword, '//' . $dbhost . '/' . $dbname);
					if (!$connection) {
						check_generic(0, "Connection with Database");
					}
					else {
						check_generic(1, "Connection with Database");
						
						// Drop all objects if needed
						if ($dbdrop == 1) {
							oracle_drop_all_objects($connection);
						}
						
						$step1 = parse_oracle_dump($connection, "pandoradb.oracle.sql");
						
						check_generic($step1, "Creating schema");
						
						if ($step1) {
							$step2 = parse_oracle_dump($connection, "pandoradb.data.oracle.sql");
						}
						
						check_generic ($step2, "Populating database");
						
						if (PHP_OS == "FreeBSD")
						{
							$step_freebsd = adjust_paths_for_freebsd ($engine, $connection);
							check_generic ($step_freebsd, "Adjusting paths in database for FreeBSD");
						}
						
						echo "<tr><td><div class='warn'>Please, you will need to setup your Pandora FMS server, editing the </i>/etc/pandora/pandora_server.conf</i> file and set database password.</div></tr></td>";
						
						if ($step2) {
							$step3 = is_writable("include");
						}
						
						check_generic ($step3, "Write permissions to save config file in './include'");
						
						if ($step3) {
							$cfgin = fopen ("include/config.inc.php","r");
							$cfgout = fopen ($pandora_config,"w");
							$config_contents = fread ($cfgin, filesize("include/config.inc.php"));
							$dbtype = 'oracle';
							$config_new = '<?php
							// Begin of automatic config file
							$config["dbtype"] = "' . $dbtype . '"; 	//DB type (mysql, postgresql, oracle)
							$config["dbname"]="' . $dbname . '";		// Oracle DataBase name
							$config["dbuser"]="' . $dbuser . '";		// DB User
							$config["dbpass"]="' . $dbpassword . '";	// DB Password
							$config["dbhost"]="' . $dbhost . '";		// DB Host
							$config["homedir"]="' . $path . '";		// Config homedir
							/*
							----------Attention--------------------
							Please note that in certain installations:
								- reverse proxy.
								- web server in other ports.
								- https
							
							This variable might be dynamically altered.
							
							But it is save as backup in the
							$config["homeurl_static"]
							for expecial cases.
							----------Attention--------------------
							*/
							$config["homeurl"]="' . $url . '";		// Base URL
							$config["homeurl_static"]="'.$url.'";			// Don\'t  delete
							// End of automatic config file
							?>';
							$step4 = fputs ($cfgout, $config_new);
							$step4 = $step4 + fputs ($cfgout, $config_contents);
							if ($step4 > 0)
								$step4 = 1;
							fclose ($cfgin);
							fclose ($cfgout);
							chmod ($pandora_config, 0600);
						}
						
						check_generic ($step4, "Created new config file at '" . $pandora_config . "'");
						
						if (($step4 + $step3 + $step2 + $step1) == 4) {
							$everything_ok = 1;
						}
						
					}
					break;
				case 'pgsql':
					$step1 = $step2 = $step3 = $step4 = $step5 = $step6 = $step7 = 0;
					
					$connection = pg_connect("host='" . $dbhost . "' dbname='postgres' user='" . $dbuser . "' password='" . $dbpassword . "'");
					if ($connection === false) {
						check_generic(0, "Connection with Database");
					}
					else {
						check_generic(1, "Connection with Database");
						
						// Drop database if needed
						if ($dbdrop == 1 && $dbaction == 'db_exist') {
							$result = pg_query($connection, "DROP DATABASE \"" . $dbname . "\";");
						}
						
						if ($dbaction != 'db_exist' || $dbdrop == 1) {
							pg_send_query($connection, "CREATE DATABASE \"" . $dbname . "\" WITH ENCODING 'utf8';");
							$result = pg_get_result($connection);
							if (pg_result_status($result) != PGSQL_FATAL_ERROR) {
								$step1 = 1;
							}
						
							check_generic ($step1, "Creating database '$dbname'");
						}
						else {
							$step1 = 1;
						}
						
						check_generic ($step1, "Creating database '$dbname'");
						
						if ($step1 == 1) {
							//Reopen DB because I don't know how to use DB in PostgreSQL
							pg_close($connection);
							
							$connection = pg_connect("host='" . $dbhost . "' dbname='" . $dbname .
								"' user='" . $dbuser . "' password='" . $dbpassword . "'");
							
							if ($connection !== false) {
								$step2 = 1;
							}
						}
						
						check_generic ($step2, "Opening database '$dbname'");
						
						if ($step2) {
							$step3 = parse_postgresql_dump($connection, "pandoradb.postgreSQL.sql");
						}
						
						check_generic($step3, "Creating schema");
						
						if ($step3) {
							$step4 = parse_postgresql_dump($connection, "pandoradb.data.postgreSQL.sql");
						}
						
						check_generic ($step4, "Populating database");
						
						if (PHP_OS == "FreeBSD") {
							$step_freebsd = adjust_paths_for_freebsd ($engine, $connection);
							check_generic ($step_freebsd, "Adjusting paths in database for FreeBSD");
						}
						
						if ($step4) {
							$random_password = random_name (8);
							
							pg_query($connection, "DROP USER pandora");
							pg_send_query($connection, "CREATE USER pandora WITH PASSWORD '" . $random_password . "'");
							$result = pg_get_result($connection);
							
							if (pg_result_status($result) != PGSQL_FATAL_ERROR) {
								//Set the privileges for DB
								pg_send_query($connection, "GRANT ALL PRIVILEGES ON DATABASE pandora TO pandora;");
								$result = pg_get_result($connection);
								
								$setDBPrivileges = 0;
								if (pg_result_status($result) != PGSQL_FATAL_ERROR) {
									$setDBPrivileges = 1;
								}
								
								if ($setDBPrivileges) {
									//Set the privileges for each tables.
									pg_send_query($connection, "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public';");
									$result = pg_get_result($connection);
									
									$tables = array();
									while ($row = pg_fetch_assoc($result)) {
										$tables[] = $row['table_name'];
									}
									
									$correct = 1;
									foreach ($tables as $table) {
										pg_send_query($connection, "GRANT ALL PRIVILEGES ON TABLE " . $table . " TO pandora;");
										$result = pg_get_result($connection);
										
										if (pg_result_status($result) == PGSQL_FATAL_ERROR) {
											$correct = 0;
											break;
										}
										
										//For each table make owner pandora
										pg_send_query($connection, "ALTER TABLE " . $table . " OWNER TO pandora;");
										$result = pg_get_result($connection);
										
										if (pg_result_status($result) == PGSQL_FATAL_ERROR) {
											$correct = 0;
											break;
										}
										
										//INI ----- Grant for secuences
										pg_send_query($connection, "SELECT column_name FROM information_schema.columns WHERE table_name = '" . $table . "';");
										$result2 = pg_get_result($connection);
										
										$columns = array();
										while ($row = pg_fetch_assoc($result2)) {
											$columns[] = $row['column_name'];
										}
										
										//Check for each column if it have a sequence to grant
										foreach ($columns as $column) {
											pg_send_query($connection, "SELECT pg_get_serial_sequence('" . $table . "', '" . $column . "');");
											$result3 = pg_get_result($connection);
											
											$sequence = pg_fetch_assoc($result3);
											if (!empty($sequence['pg_get_serial_sequence'])) {
												pg_send_query($connection, "GRANT ALL PRIVILEGES ON SEQUENCE " . $sequence['pg_get_serial_sequence'] . " to pandora;");
												$result4 = pg_get_result($connection);
												
												if (pg_result_status($result4) == PGSQL_FATAL_ERROR) {
													$correct = 0;
													break;
												}
											}
										}
										//END ----- Grant for secuences
										
									}
									
									if ($correct) {
										$step5 = 1;
									}
								}
							}
						}
						
						check_generic ($step5, "Established privileges for user pandora. A new random password has been generated: <b>$random_password</b><div class='warn'>Please write it down, you will need to setup your Pandora FMS server, editing the </i>/etc/pandora/pandora_server.conf</i> file</div>");
						
						if ($step5) {
							$step6 = is_writable("include");
						}
						
						check_generic ($step6, "Write permissions to save config file in './include'");
						
						if ($step6) {
							$cfgin = fopen ("include/config.inc.php","r");
							$cfgout = fopen ($pandora_config,"w");
							$config_contents = fread ($cfgin, filesize("include/config.inc.php"));
							$dbtype = 'postgresql';
							$config_new = '<?php
							// Begin of automatic config file
							$config["dbtype"] = "' . $dbtype . '"; //DB type (mysql, postgresql...in future others)
							$config["dbname"]="'.$dbname.'";			// MySQL DataBase name
							$config["dbuser"]="pandora";			// DB User
							$config["dbpass"]="'.$random_password.'";	// DB Password
							$config["dbhost"]="'.$dbhost.'";			// DB Host
							$config["homedir"]="'.$path.'";		// Config homedir
							/*
							----------Attention--------------------
							Please note that in certain installations:
								- reverse proxy.
								- web server in other ports.
								- https
							
							This variable might be dynamically altered.
							
							But it is save as backup in the
							$config["homeurl_static"]
							for expecial cases.
							----------Attention--------------------
							*/
							$config["homeurl"]="'.$url.'";			// Base URL
							$config["homeurl_static"]="'.$url.'";			// Don\'t  delete
							// End of automatic config file
							?>';
							$step7 = fputs ($cfgout, $config_new);
							$step7 = $step7 + fputs ($cfgout, $config_contents);
							if ($step7 > 0)
								$step7 = 1;
							fclose ($cfgin);
							fclose ($cfgout);
							chmod ($pandora_config, 0600);
						}
						
						check_generic ($step7, "Created new config file at '".$pandora_config."'");
						
						if (($step7 + $step6 + $step5 + $step4 + $step3 + $step2 + $step1) == 7) {
							$everything_ok = 1;
						}
					}
					break;
			}
		echo "</table>";
				
			if ($everything_ok == 1) {
				echo "</div>";
				echo "<div style='margin-top: 20px;'><a id='step5' href='install.php?step=5'>
				<img align='right' src='images/arrow_next.png' border='0' alt=''></a></div>";
			}
			else {
				$info = "<div class='err'><b>There were some problems.
				Installation was not completed.</b> 
				<p>Please correct failures before trying again.
				All database ";
				if ($engine == 'oracle')
					$info .= "objects ";
				else
					$info .= "schemes ";
				
				$info .= "created in this step have been dropped. </p>
				</div>";
				echo $info;
				
				switch ($engine) {
					case 'mysql':
						if (mysql_error() != "") {
							echo "<div class='err'> <b>ERROR:</b> ". mysql_error().".</div>";
						}
						
						if ($step1 == 1) {
							mysql_query ("DROP DATABASE $dbname");
						}
						break;
					case 'pgsql':
						break;
					case 'oracle':
						oracle_drop_all_objects($connection);
						break;
				}
				echo "</div>";
			}
			
		echo "<div style='clear: both;'></div>";
		echo "
		</div>
		<div id='foot_install'>
			<i>Pandora FMS is an Open Source Software project registered at 
			<a target='_new' href='http://pandora.sourceforge.net'>SourceForge</a></i>
		</div>
	</div>";
}


function install_step5() {
	echo "
	<div id='install_container'>
	<div id='wizard'>
	" . print_logo_status (6,6) . "
		<div id='install_box'>
			<h2>Installation complete</h2>
			<p>For security, you now must manually delete this installer 
			('<i>install.php</i>') file before trying to access to your Pandora FMS console.
			<p>You should also install Pandora FMS Servers before trying to monitor anything;
			please read documentation on how to install it.</p>
			<p>Default user is <b>'admin'</b> with password <b>'pandora'</b>, 
			please change it both as soon as possible.</p>
			<p>Don't forget to check <a href='http://pandorafms.com'>http://pandorafms.com</a> 
			for updates.
			<p>Select if you want to rename '<i>install.php</i>'.</p>
			<form method='post' action='index.php'>
				<input type='submit' name='rn_file' value='Yes, rename the file'>
				<input type='hidden' name='rename_file' value='1'>
			</form>
			<p><br><b><a id='access_pandora' href='index.php'>Click here to access to your Pandora FMS console</a>.</b>
			</p>
		</div>";

	echo "</div>
	<div id='foot_install'>
		<i>Pandora FMS is an OpenSource Software project registered at 
		<a target='_new' href='http://pandora.sourceforge.net'>SourceForge</a></i>
	</div>
</div>";
}
?>
