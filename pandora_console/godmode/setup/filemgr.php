<?php 

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2009 Artica Soluciones Tecnológicas, http://www.artica.es
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

// Load global vars
require_once ("include/config.php");

check_login ();

if (! give_acl ($config['id_user'], 0, "PM") || ! is_user_admin ($config['id_user'])) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to access File manager");
	require ("general/noaccess.php");
	exit;
}

// Upload file
if (isset($_GET["upload_file"])) {

	if (isset($_FILES["userfile"]) && ( $_FILES['userfile']['name'] != "" )){ //if file
		$tipo = $_FILES['userfile']['type'];

		$filename= $_FILES['userfile']['name'];
		$filesize = $_FILES['userfile']['size'];
		$directory = get_parameter ("directory","");
		
		// Copy file to directory and change name
		$nombre_archivo = $config["homedir"]."/".$directory."/".$filename;
		if (!(copy($_FILES['userfile']['tmp_name'], $nombre_archivo ))){
			echo "<h3 class=error>".__("attach_error")."</h3>";
		} else {
			// Delete temporal file
			unlink ($_FILES['userfile']['tmp_name']);
		}
		
	}
}

// Delete file
$delete = get_parameter ("delete", "");
if ($delete != ""){
	echo "<h1>".__("Deleting file")."</h1>";
	$file = get_parameter ("delete", "");
	$directory = get_parameter ("directory", "");

	$full_filename = $directory . "/". $file;
	if (!is_dir ($full_filename)){
		echo "<h3>".__("Deleting")." ".$full_filename."</h3>";
		unlink ($full_filename);
	}
}

echo "<h1>".__("File manager")."</h1>";

$current_directory = get_parameter ("directory", "/");

// CREATE DIR
// Upload file
if (isset($_GET["create_dir"])) {
	$newdir = get_parameter ("newdir","");
	if ($newdir != ""){
		mkdir($current_directory."/".$newdir);
		echo "<h3>".__("Created directory '$newdir'")."</h3>";
	}

}


// A miminal security check to avoid directory traversal
if (preg_match("/\.\./", $current_directory))
	$current_directory = "images";
if (preg_match("/^\//", $current_directory))
	$current_directory = "images";
if (preg_match("/^manager/", $current_directory))
	$current_directory = "images";

echo "<form method='post' action='index.php?sec=gsetup&amp;sec2=godmode/setup/filemgr&upload_file' enctype='multipart/form-data'>";
echo "<table cellpadding='4' cellspacing='4' width='550' class='databox'>";

echo "<tr><td class='datos'>";
echo __("Base directory");
echo "<td class='datos'>";


/* Add custom directories here */
$fallback_directory = "images";

$available_directory["images"] = "images";
$available_directory["attachment"] = "attachment";
$available_directory["include/languages"] = "languages";

$banned_directory["include"] = 1;
$banned_directory["godmode"] = 1;
$banned_directory["operation"] = 1;
$banned_directory["reporting"] = 1;
$banned_directory["general"] = 1;
$banned_directory["enterprise"] = 1;

if (isset($banned_directory[$current_directory]))
	$current_directory = $fallback_directory;

// Current directory
$available_directory[$current_directory] = $current_directory;

print_select ($available_directory, 'directory', $current_directory, '', '', '',  false, false);
echo "&nbsp;&nbsp;<input type=submit value='".__("Go")."'>";

$real_directory = $config["homedir"] . "/". $current_directory;

if (is_writable($real_directory)) {
	echo "<tr><td class='datos'>";
	echo __("Upload new file");
	echo "<td class='datos'>";
	echo "<input type='file' size=25 name='userfile' value='userfile'>";
	echo "&nbsp;&nbsp;";
	echo "<input type=submit value='".__("Upload")."'>";
	echo "</form>";
	echo "</table>";
} else {
	echo "</form>";
	echo "</table>";
	echo "<h3 class='error'>".__('Current directory is not writtable by HTTP Server')."</h3>";
	echo '<p>';
	echo __('Please check that current directory has write rights for HTTP server');
	echo "</p>";
}


echo "<h2>".__("Current directory"). " : ".$current_directory . " <a href='index.php?manager=filemgr&directory=$current_directory'><img src='images/arrow_refresh.png' border=0></a></h2>";
// Upload form

	// List files
	
	$directoryHandler = "";
	$result = array ();
	if (! $directoryHandler = @opendir ($real_directory)) {
		echo ("<pre>\nerror: directory \"$current_directory\" doesn't exist!\n</pre>\n");
		return 0;
	}
	
	while (false !== ($fileName = @readdir ($directoryHandler))) {
		$result[$fileName] = $fileName;
		// TODO: Read filetype (image, directory)
		//       If directory create a link to navigate.
	}
	asort($result, SORT_STRING);
	
	if (@count ($result) === 0) {
		echo __ ("No files found");
	} else {
		asort ($result);
		
		echo "<table width='750' class='listing'>";
		
		$prev_dir = split( "/", $current_directory );
		$prev_dir_str = "";
		for ($ax = 0; $ax < (count($prev_dir)-1); $ax++){

			$prev_dir_str .= $prev_dir[$ax];
			if ($ax < (count($prev_dir)-2))
				$prev_dir_str .= "/";
		}

		if ($prev_dir_str != ""){
			echo "<tr><td colspan=6>";
			echo "<a href='index.php?sec=gsetup&amp;sec2=godmode/setup/filemgr&directory=$prev_dir_str'>".__("Go prev. directory")." <img src='images/go-previous.png' border=0></a>";
			echo "</th></tr>";
		}
		echo "<tr><th>";
		echo __ ("Filename");
		echo "<th>";
		echo __ ("Image info");
		echo "<th>";
		echo __ ("Last update");
		echo "<th>";
		echo __ ("Owner");
		echo "<th>";
		echo __ ("Perms");
		echo "<th>";
		echo __ ("Filesize");
		echo "<th>";
		echo __ ("File type");
		echo "<th>";
		echo __ ("Directory");
		echo "<th>";
		echo __ ("Del");
		while (@count($result) > 0){
			$temp = array_shift ($result);
			$fullfilename = $current_directory.'/'.$temp;
			$mimetype = "";
			if (($temp != "..") AND ($temp != ".")){
				echo "<tr><td>";
				if (!is_dir ($current_directory.'/'.$temp)){	
					echo "<a href='$fullfilename'>$temp</A>";
				} else
					echo "<a href='index.php?sec=gsetup&amp;sec2=godmode/setup/filemgr&directory=$current_directory/$temp'>/$temp</a>";

				echo "<td>";
				if (preg_match("/image/", $mimetype)){
					list($ancho, $altura, $tipo, $atr) = getimagesize($fullfilename);
					echo $ancho."x".$altura;
				}	
				echo "<td>";
				if (!is_dir ($fullfilename))
					echo date("F d Y H:i:s.", filemtime($fullfilename));
				echo "<td>";
				if (!is_dir ($fullfilename))
					echo fileowner($fullfilename);

				echo "<td>";
				if (!is_dir ($fullfilename))
					if (!is_readable($fullfilename))
						echo "<font color=#ff0000>";
					echo __("Read");				

				echo "<td>";
				if (!is_dir ($fullfilename))
					echo filesize($fullfilename);
				else
					echo "&lt;DIR&gt;";

				echo "<td>";
				if (!is_dir ($fullfilename))
					echo $mimetype;
				else
					echo "&lt;DIR&gt;";

				echo "<td align=center>";
				if (!is_dir ($fullfilename))
					echo "<img src='images/disk.png' border=0>";
				else
					echo "<img src='images/drive_network.png' border=0>";
				echo "<td>";
				echo "<a href='index.php?sec=gsetup&amp;sec2=godmode/setup/filemgr&directory=$current_directory&delete=$temp'><img src='images/cross.png' border=0></a>";
			}
		}
		echo "</table>";

		if (is_writable($current_directory)){
			echo "<br><br>";
			echo "<form method='post' action='index.php?sec=gsetup&amp;sec2=godmode/setup/filemgr&create_dir=1&directory=$current_directory'>";
			echo __("Create directory");
			echo "&nbsp;&nbsp;";
			echo "<input type=text size=15 name='newdir'>";
			echo "&nbsp;&nbsp;";
			echo "<input type=submit value='Make dir'>";
			echo "</form>";
		}
	}


?>

