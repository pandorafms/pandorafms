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

if (! give_acl ($config['id_user'], 0, "PM")) {
	audit_db ($config['id_user'], $REMOTE_ADDR, "ACL Violation", "Trying to access File manager");
	require ("general/noaccess.php");
	return;
}

require_once ("include/functions_filemanager.php");

$delete_file = (bool) get_parameter ('delete_file');
$upload_file = (bool) get_parameter ('upload_file');
$create_dir = (bool) get_parameter ('create_dir');

// Upload file
if ($upload_file) {
	if (isset ($_FILES['file']) && $_FILES['file']['name'] != "") {
		$filename = $_FILES['file']['name'];
		$filesize = $_FILES['file']['size'];
		$directory = (string) get_parameter ('directory');
		
		// Copy file to directory and change name
		$nombre_archivo = $config['homedir'].'/'.$directory.'/'.$filename;
		if (! @copy ($_FILES['file']['tmp_name'], $nombre_archivo )) {
			echo "<h3 class=error>".__('attach_error')."</h3>";
		} else {
			// Delete temporal file
			unlink ($_FILES['file']['tmp_name']);
		}
		
	}
}

if ($delete_file) {
	echo "<h1>".__('Deleting file')."</h1>";
	$file = (string) get_parameter ('filename');
	$directory = (string) get_parameter ('directory');

	$full_filename = $directory.'/'.$file;
	if (!is_dir ($full_filename)){
		echo "<h3>".__('Deleting')." ".$full_filename."</h3>";
		unlink ($full_filename);
	}
}

echo "<h1>".__('File manager')."</h1>";

$directory = (string) get_parameter ('directory', "/");

// CREATE DIR
if ($create_dir) {
	$dirname = (string) get_parameter ('dirname');
	if ($dirname) {
		@mkdir ($directory.'/'.$dirname);
		echo '<h3>'.__('Created directory %s', $dirname).'</h3>';
	}
}

// A miminal security check to avoid directory traversal
if (preg_match ("/\.\./", $directory))
	$directory = "images";
if (preg_match ("/^\//", $directory))
	$directory = "images";
if (preg_match ("/^manager/", $directory))
	$directory = "images";

/* Add custom directories here */
$fallback_directory = "images";

$banned_directories['include'] = true;
$banned_directories['godmode'] = true;
$banned_directories['operation'] = true;
$banned_directories['reporting'] = true;
$banned_directories['general'] = true;
$banned_directories[ENTERPRISE_DIR] = true;

if (isset ($banned_directories[$directory]))
	$directory = $fallback_directory;

// Current directory
$available_directories[$directory] = $directory;

$real_directory = realpath ($config['homedir'].'/'.$directory);

$table->width = '50%';

$table->data = array ();

if (! is_file_manager_writable_dir ($real_directory)) {
	echo "<h3 class='error'>".__('Current directory is not writable by HTTP Server')."</h3>";
	echo '<p>';
	echo __('Please check that current directory has write rights for HTTP server');
	echo '</p>';
} else {
	$table->data[1][0] = __('Upload file');
	$table->data[1][1] = print_input_file ('file', true, false);
	$table->data[1][2] = print_submit_button (__('Go'), 'go', false,
		'class="sub next"', true);
	$table->data[1][2] .= print_input_hidden ('directory', $directory, true);
	$table->data[1][2] .= print_input_hidden ('upload_file', 1, true);
}

echo '<form method="post" action="index.php?sec=gsetup&amp;sec2=godmode/setup/file_manager" enctype="multipart/form-data">';
print_table ($table);
echo '</form>';

echo '<h2>'.__('Index of %s', $directory).'</h2>';

// List files
if (! is_dir ($real_directory)) {
	echo __('Directory %s doesn\'t exist!', $directory);
	return;
}

$files = list_file_manager_dir ($real_directory);

$table->width = '90%';
$table->class = 'listing';

$table->colspan = array ();
$table->data = array ();
$table->head = array ();
$table->size = array ();

$table->size[0] = '24px';

$table->head[0] = '';
$table->head[1] = __('Name');
$table->head[2] = __('Last modification');
$table->head[3] = __('Size');
$table->head[4] = '';

$prev_dir = split ("/", $directory);
$prev_dir_str = "";
for ($i = 0; $i < (count ($prev_dir) - 1); $i++) {
	$prev_dir_str .= $prev_dir[$i];
	if ($i < (count ($prev_dir) - 2))
		$prev_dir_str .= "/";
}

if ($prev_dir_str != '') {
	$table->data[0][0] = print_image ('images/go_previous.png', true);
	$table->data[0][1] = '<a href="index.php?sec=gsetup&amp;sec2=godmode/setup/file_manager&directory='.$prev_dir_str.'">';
	$table->data[0][1] .= __('Parent directory');
	$table->data[0][1] .='</a>';
	
	$table->colspan[0][1] = 5;
}

if (is_writable ($real_directory)) {
	$table->data[1][0] = print_image ('images/mimetypes/directory.png', true,
		array ('title' => __('Create directory')));
	$table->data[1][1] = '<form method="post" action="index.php?sec=gsetup&amp;sec2=godmode/setup/file_manager">';
	$table->data[1][1] .= print_input_text ('dirname', '', '', 15, 255, true);
	$table->data[1][1] .= print_submit_button (__('Create'), 'crt', false, 'class="sub next"', true);
	$table->data[1][1] .= print_input_hidden ('directory', $directory, true);
	$table->data[1][1] .= print_input_hidden ('create_dir', 1, true);
	$table->data[1][1] .= '</form>';
	
	$table->colspan[0][1] = 5;
}

foreach ($files as $fileinfo) {
	$data = array ();
	
	switch ($fileinfo['mime']) {
	case MIME_DIR:
		$data[0] = print_image ('images/mimetypes/directory.png', true);
		break;
	case MIME_IMAGE:
		$data[0] = print_image ('images/mimetypes/image.png', true);
		break;
	case MIME_ZIP:
		$data[0] = print_image ('images/mimetypes/zip.png', true);
		break;
	default:
		$data[0] = print_image ('images/mimetypes/unknown.png', true);
	}
	
	if ($fileinfo['is_dir']) {
		$data[1] = '<a href="index.php?sec=gsetup&sec2=godmode/setup/file_manager&directory='.$directory.'/'.$fileinfo['name'].'">'.$fileinfo['name'].'</a>';
	} else {
		$data[1] = '<a href="'.$fileinfo['url'].'">'.$fileinfo['name'].'</a>';
	}
	$data[2] = print_timestamp ($fileinfo['last_modified'], true,
		array ('prominent' => true));
	if ($fileinfo['is_dir']) {
		$data[3] = '';
	} else {
		$data[3] = format_filesize ($fileinfo['size']);
	}
	
	array_push ($table->data, $data);
}

print_table ($table);
?>
