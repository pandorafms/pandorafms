<?php

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2008 Sancho Lerena, slerena@openideas.info
// Copyright (c) 2005-2008 Artica Soluciones Tecnologicas

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
<title>Pandora FMS - Upgrade Wizard from 1.3 version </title>
<meta http-equiv="expires" content="0">
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<meta name="resource-type" content="document">
<meta name="distribution" content="global">
<meta name="author" content="Sancho Lerena, ArticaST">
<meta name="copyright" content="This is GPL software. Created by Sancho Lerena and others">
<meta name="keywords" content="pandora, fms, monitoring, network, system, GPL, software">
<meta name="robots" content="index, follow">
<link rel="icon" href="images/pandora.ico" type="image/ico">
<link rel="stylesheet" href="include/styles/pandora_minimal.css" type="text/css">
<link rel="stylesheet" href="include/styles/install.css" type="text/css">
</head>

<body>
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
                    mysql_query($query);
                    $query = "";
                }
            }
        }
        return 1;
    } else {
        return 0;
    }
}

function install_step1() {
	echo "
	<div id='install_container'>
	<h1>Pandora FMS upgrade wizard (from 1.3). Step #1 of 3</h1>
	<div id='wizard' style='height: 390px;'>
	    <div id='install_box'>
		    <h1>Welcome to Pandora FMS 1.3.1 upgrade Wizard</h1>
			<p>This wizard helps you to quick upgrade Pandora FMS console in your system. This tool is <b>only</b> to 
upgrade Pandora FMS 1.3 to Pandora FMS 1.3.1</p>
			<p>For more information, please refer to documentation.</p>
			<i>Pandora FMS Development Team</i>
			<div class='info'>Before start with upgrade process. Please <b>STOP NOW all your Pandora FMS servers</b></div>
        ";
		
		if (!file_exists("include/config.php")){
			echo "<div class='warn'><b>Warning:</b> You don't have a existant <i>config.php</i> in ./include directory. You need to have your current config.php accesible for this upgrade tool.</div>";
            echo "</div>";
            echo "<div id='logo_img'>
                <img src='images/pandora_logo.png' border='0'><br>
                <img src='images/step0.png' border='0'>
            </div>";
		} else {
        
		    echo "<div class='warn'><b>Warning:</b> This upgrade tool will not overwrite and change your existing Pandora FMS <b>Database</b> and only could be used to upgrade fron Pandora FMS 1.3 to Pandora FMS 1.3.1.  Before continue, please <b>be sure that you have made a SQL backup using mysqldump system tool as described in documentation.</b><br></div>";
		    echo "
		</div>
		    <div id='logo_img'>
			    <img src='images/pandora_logo.png' border='0'><br>
			    <img src='images/step0.png' border='0'>
		    </div>
		    <div id='install_img'>
			    <a href='upgrade_from_1.3.php?step=2'><img align='right' src='images/arrow_next.png' border=0></a>
		    </div>";
        }
	echo "</div>
	<div id='foot_install'>
		<i>Pandora FMS is a Free Software project registered at <a target='_new' href='http://pandora.sourceforge.net'>SourceForge</a></i>
	</div>
	</div>";
}

function install_step2() {
    include "../include/config.php";
	echo "
	<div id='install_container'>
	<h1>Pandora FMS upgrade wizard (from 1.3). Step #2 of 3</h1>
	<div id='wizard' style='height: 340px;'>
		<div id='install_box'>";
		echo "<h1>Upgrading...</h1>";
        echo "<table>";
        $step3 = parse_mysql_dump ("pandoradbdata_1.3_to_1.3.1.sql");
        check_generic ($step3, "Schema update");
        echo "
        </table>
		</div>
		<div id='logo_img'>
			<img src='images/pandora_logo.png' border='0' alt=''><br>
			<img src='images/step1.png' border='0' alt=''>
		</div>
		<div id='install_img'>";
		echo "<a href='upgrade_from_1.3.php?step=3'><img align='right' src='images/arrow_next.png' border=0 alt=''></a>";	
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
