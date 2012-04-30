<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package Include
 * @subpackage Filemanager
 */

/**#@+
 * Constants
 */
define ('MIME_UNKNOWN', 0);
define ('MIME_DIR', 1);
define ('MIME_IMAGE', 2);
define ('MIME_ZIP', 3);
define ('MIME_TEXT', 4);
/**#@-*/

if (!function_exists ('mime_content_type')) {
	/**
	 * Gets the MIME type of a file.
	 *
	 * Help function in case mime_magic is not loaded on PHP.
	 *
	 * @param string Filename to get MIME type.
	 *
	 * @return The MIME type of the file.
	 */
	function mime_content_type ($filename) {
		$mime_types = array (
			'txt' => 'text/plain',
			'htm' => 'text/html',
			'html' => 'text/html',
			'php' => 'text/html',
			'css' => 'text/css',
			'js' => 'application/javascript',
			'json' => 'application/json',
			'xml' => 'application/xml',
			'swf' => 'application/x-shockwave-flash',
			'flv' => 'video/x-flv',
			// images
			'png' => 'image/png',
			'jpe' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'jpg' => 'image/jpeg',
			'gif' => 'image/gif',
			'bmp' => 'image/bmp',
			'ico' => 'image/vnd.microsoft.icon',
			'tiff' => 'image/tiff',
			'tif' => 'image/tiff',
			'svg' => 'image/svg+xml',
			'svgz' => 'image/svg+xml',
			// archives
			'zip' => 'application/zip',
			'rar' => 'application/x-rar-compressed',
			'exe' => 'application/x-msdownload',
			'msi' => 'application/x-msdownload',
			'cab' => 'application/vnd.ms-cab-compressed',
			'gz' => 'application/x-gzip',
			'gz' => 'application/x-bzip2',
			// audio/video
			'mp3' => 'audio/mpeg',
			'qt' => 'video/quicktime',
			'mov' => 'video/quicktime',
			// adobe
			'pdf' => 'application/pdf',
			'psd' => 'image/vnd.adobe.photoshop',
			'ai' => 'application/postscript',
			'eps' => 'application/postscript',
			'ps' => 'application/postscript',
			// ms office
			'doc' => 'application/msword',
			'rtf' => 'application/rtf',
			'xls' => 'application/vnd.ms-excel',
			'ppt' => 'application/vnd.ms-powerpoint',
			// open office
			'odt' => 'application/vnd.oasis.opendocument.text',
			'ods' => 'application/vnd.oasis.opendocument.spreadsheet'
		);

		$ext = strtolower (array_pop (explode ('.', $filename)));
		if (array_key_exists ($ext, $mime_types)) {
			return $mime_types[$ext];
		} elseif (function_exists ('finfo_open')) {
			$finfo = finfo_open (FILEINFO_MIME);
			$mimetype = finfo_file ($finfo, $filename);
			finfo_close ($finfo);
			return $mimetype;
		} else {
			$temp = exec ("file ".$filename);
			if (isset($temp) && $temp != '')
				return $temp;
			else
				return 'application/octet-stream';
		}
	}
}

$upload_file_or_zip = (bool) get_parameter('upload_file_or_zip');

if ($upload_file_or_zip) {
	$decompress = get_parameter('decompress');
	if (!$decompress) {
		$upload_file = true;
		$upload_zip = false;
	}
	else {
		$upload_file = false;
		$upload_zip = true;
	}
}
else {
	$upload_file = (bool) get_parameter ('upload_file');
	$upload_zip = (bool) get_parameter ('upload_zip');
}

// Upload file
if ($upload_file) {
	// Load global vars
	global $config;
	
	$config['filemanager'] = array();
	$config['filemanager']['correct_upload_file'] = 0;
	$config['filemanager']['message'] = null;
	
	check_login ();
	
	if (! check_acl ($config['id_user'], 0, "PM")) {
		db_pandora_audit("ACL Violation", "Trying to access File manager");
		require ("general/noaccess.php");
		return;
	}
	
	if (isset ($_FILES['file']) && $_FILES['file']['name'] != "") {
		$filename = $_FILES['file']['name'];
		$filesize = $_FILES['file']['size'];
		$real_directory = (string) get_parameter('real_directory');
		$directory = (string) get_parameter ('directory');
		
		$hash = get_parameter('hash', '');
		$testHash = md5($real_directory . $directory . $config['dbpass']);
		
		if ($hash != $testHash) {
			$config['filemanager']['message'] = ui_print_error_message(__('Security error'), '', true);
		}
		else {
			// Copy file to directory and change name
			if ($directory == '') {
				$nombre_archivo = $real_directory .'/'. $filename;
			}
			else {
				$nombre_archivo = $config['homedir'].'/'.$directory.'/'.$filename;
			}
			
			if (! @copy ($_FILES['file']['tmp_name'], $nombre_archivo )) {
				$config['filemanager']['message'] = ui_print_error_message(__('Upload error'), '', true);
			}
			else {
				$config['filemanager']['correct_upload_file'] = 1;
				$config['filemanager']['message'] = ui_print_success_message(__('Upload correct'), '', true);
				
				// Delete temporal file
				unlink ($_FILES['file']['tmp_name']);
			}
		}		
	}
}

// Create text file 
$create_text_file = (bool) get_parameter ('create_text_file');
if ($create_text_file) {
	// Load global vars
	global $config;
	
	$config['filemanager'] = array();
	$config['filemanager']['correct_upload_file'] = 0;
	$config['filemanager']['message'] = null;
	
	check_login ();
	
	if (! check_acl ($config['id_user'], 0, "PM")) {
		db_pandora_audit("ACL Violation", "Trying to access File manager");
		require ("general/noaccess.php");
		return;
	}
	
	$filename = io_safe_output(get_parameter('name_file'));
	
	if ($filename != "") {

		$real_directory = (string) get_parameter('real_directory');
		$real_directory = io_safe_output($real_directory);
		$directory = (string) get_parameter ('directory');
		$directory = io_safe_output($directory);
		
		$hash = get_parameter('hash', '');
		$testHash = md5($real_directory . $directory . $config['dbpass']);
		
		if ($hash != $testHash) {
			echo "<h4 class=error>".__('Security error.')."</h4>";
		}
		else {
			if ($directory == '') {
				$nombre_archivo = $real_directory .'/'. $filename;
			}
			else {
				$nombre_archivo = $config['homedir'].'/'.$directory.'/'.$filename;
			}
			if (! @touch($nombre_archivo)) {
				$config['filemanager']['message'] = ui_print_error_message(__('Error creating file'), '', true);
			}
			else {
				$config['filemanager']['message'] = ui_print_success_message(__('Upload correct'), '', true);
				$config['filemanager']['correct_upload_file'] = 1;
			}
		}
	}
	else {
		$config['filemanager']['message'] = ui_print_error_message(__('Error creating file with empty name'), '', true);
	}
}

// Upload zip
if ($upload_zip) {
	// Load global vars
	global $config;
	
	$config['filemanager'] = array();
	$config['filemanager']['correct_upload_file'] = 0;
	$config['filemanager']['message'] = null;
	
	check_login ();
	
	if (! check_acl ($config['id_user'], 0, "PM")) {
		db_pandora_audit("ACL Violation", "Trying to access File manager");
		require ("general/noaccess.php");
		return;
	}
	
	if (isset ($_FILES['file']) && $_FILES['file']['name'] != "") {
		$filename = $_FILES['file']['name'];
		$filesize = $_FILES['file']['size'];
		$real_directory = (string) get_parameter('real_directory');
		$directory = (string) get_parameter ('directory');
		
		$hash = get_parameter('hash', '');
		$testHash = md5($real_directory . $directory . $config['dbpass']);
		
		if ($hash != $testHash) {
			$config['filemanager']['message'] = ui_print_error_message(__('Security error'), '', true);
		}
		else {
			// Copy file to directory and change name
			if ($directory == '') {
				$nombre_archivo = $real_directory .'/'. $filename;
			}
			else {
				$nombre_archivo = $config['homedir'].'/'.$directory.'/'.$filename;
			}
			if (! @copy ($_FILES['file']['tmp_name'], $nombre_archivo )) {
				$config['filemanager']['message'] = ui_print_error_message(__('Attach error'), '', true);
			}
			else {
				// Delete temporal file
				unlink ($_FILES['file']['tmp_name']);
				
				//Extract the zip file
				$zip = new ZipArchive;
				$pathname = $config['homedir'].'/'.$directory.'/';
				
				if ($zip->open($nombre_archivo) === true) {
					$zip->extractTo($pathname);
					unlink($nombre_archivo);
				}
				$config['filemanager']['message'] = ui_print_success_message(__('Upload correct'), '', true);
				$config['filemanager']['correct_upload_file'] = 1;
			}
		}
	}
}

// CREATE DIR
$create_dir = (bool) get_parameter ('create_dir');
if ($create_dir) {
	global $config;
	
	$config['filemanager'] = array();
	$config['filemanager']['correct_create_dir'] = 0;
	$config['filemanager']['message'] = null;
	
	$directory = (string) get_parameter ('directory', "/");
	$directory = io_safe_output($directory);
	$hash = get_parameter('hash', '');
	$testHash = md5($directory . $config['dbpass']);
	
	if ($hash != $testHash) {
		 echo "<h4 class=error>".__('Security error.')."</h4>";
	}
	else {
		$dirname = (string) get_parameter ('dirname');
		$dirname = io_safe_output($dirname);
		if ($dirname != '') {
			@mkdir ($directory.'/'.$dirname);
			$config['filemanager']['message'] = ui_print_success_message(__('Directory created'), '', true);
			
			$config['filemanager']['correct_create_dir'] = 1;
		}
		else {
			$config['filemanager']['message'] = ui_print_error_message(__('Error creating file with empty name'), '', true);
		}
	}
}

//DELETE FILE OR DIR
$delete_file = (bool) get_parameter ('delete_file');
if ($delete_file) {
	global $config;

	$config['filemanager'] = array();
	$config['filemanager']['delete'] = 0;
	$config['filemanager']['message'] = null;
	
	$filename = (string) get_parameter ('filename');
	$filename = io_safe_output($filename);
	$hash = get_parameter('hash', '');
	$testHash = md5($filename . $config['dbpass']);
	
	if ($hash != $testHash) {
		 $config['filemanager']['message'] = ui_print_error_message(__('Security error'), '', true);
	}
	else {
		$config['filemanager']['message'] = ui_print_success_message(__('Deleted'), '', true);
		if (is_dir ($filename)) {		
			rmdir ($filename);
			$config['filemanager']['delete'] = 1;
		} else {
			unlink ($filename);
			$config['filemanager']['delete'] = 1;
		}
	}
}

/**
 * Recursive delete directory and empty or not directory.
 * 
 * @param string $dir The dir to deletete
 */
function filemanager_delete_directory($dir)
{
	if ($handle = opendir($dir))
	{
		while (false !== ($file = readdir($handle))) {
			if (($file != ".") && ($file != "..")) {
	
				if (is_dir($dir . $file))
				{
					if (!rmdir($dir . $file))
					{
						filemanager_delete_directory($dir . $file . '/');
					}
				}
				else
				{
					unlink($dir . $file);
				}
			}
		}
		closedir($handle);
		rmdir($dir);
	}
}

/**
 * Read a directory recursibly and return a array with the files with
 * the absolute path and relative
 * 
 * @param string $dir absoute dir to scan
 * @param string $relative_path Relative path to scan, by default ''
 * 
 * @return array The files in the dirs, empty array for empty dir of files.
 */
function filemanager_read_recursive_dir($dir, $relative_path = '') {
	$return = array();
	
	if ($handle = opendir($dir))
	{
		while (false !== ($entry = readdir($handle))) {
			if (($entry != ".") && ($entry != "..")) {
				if (is_dir($dir . $entry))
				{
					$return = array_merge($return, filemanager_read_recursive_dir($dir . $entry . '/', $relative_path . $entry . '/' ));
				}
				else
				{
					$return[] = array('relative' => $relative_path . $entry, 'absolute' => $dir . $entry);
				}
			}
		}
		closedir($handle);
	}
	
	return $return;
}

/**
 * The main function to show the directories and files.
 * 
 * @param string $real_directory The string of dir as realpath.
 * @param string $relative_directory The string of dir as relative path.
 * @param string $url The url to set in the forms and some links in the explorer.
 * @param string $father The directory father don't navigate bottom this.
 * @param boolean $editor The flag to set the edition of text files.
 */
function filemanager_file_explorer($real_directory, $relative_directory, $url, $father = '', $editor = false, $readOnly = false) {
	global $config;
	
	?>
	<script type="text/javascript">
	function show_form_create_folder() {
		$("#table1-1").css('display', '');
		
		$("#main_buttons").css("display", "none");
		$("#create_folder").css("display", "");
	}

	function show_upload_file() {
		$("#table1-1").css('display', '');
		
		$("#main_buttons").css("display", "none");
		$("#upload_file").css("display", "");
	}

	function show_create_text_file() {
		$("#table1-1").css('display', '');
		
		$("#main_buttons").css("display", "none");
		$("#create_text_file").css("display", "");
	}

	function show_main_buttons_folder() {
		//$("#main_buttons").css("display", "");
		$("#table1-1").css('display', 'none');
		$("#create_folder").css("display", "none");
		$("#upload_file").css("display", "none");
		$("#create_text_file").css("display", "none");
	}
	</script>
	<?php
	
	// List files
	if (! is_dir ($real_directory)) {
		echo __('Directory %s doesn\'t exist!', $relative_directory);
		return;
	}
	
	$files = filemanager_list_dir ($real_directory);
	
	$table->width = '98%';
	$table->class = 'listing';
	
	$table->colspan = array ();
	$table->data = array ();
	$table->head = array ();
	$table->size = array ();
	
	$table->align[1] = 'center';
	$table->align[2] = 'center';
	$table->align[3] = 'center';
	$table->align[4] = 'center';
	
	$table->size[0] = '24px';
	
	$table->head[0] = '';
	$table->head[1] = __('Name');
	$table->head[2] = __('Last modification');
	$table->head[3] = __('Size');
	$table->head[4] = __('Actions');
	
	$prev_dir = explode ("/", $relative_directory);
	$prev_dir_str = "";
	for ($i = 0; $i < (count ($prev_dir) - 1); $i++) {
		$prev_dir_str .= $prev_dir[$i];
		if ($i < (count ($prev_dir) - 2))
			$prev_dir_str .= "/";
	}
	
	if (($prev_dir_str != '') && ($father != $relative_directory)) {
		$table->data[0][0] = html_print_image ('images/go_previous.png', true);
		$table->data[0][1] = '<a href="' . $url . '&directory='.$prev_dir_str.'&hash2=' . md5($prev_dir_str.$config['dbpass']) . '">';
		$table->data[0][1] .= __('Parent directory');
		$table->data[0][1] .='</a>';
		
		$table->colspan[0][1] = 5;
	}
	
	if (is_writable ($real_directory)) {
		$table->rowstyle[1] = 'display: none;';
		$table->data[1][0] = '';
		$table->data[1][1] = '';
//		$table->data[1][1] -= '<div id="main_buttons">';
//		$table->data[1][1] .= html_print_button(__('Create folder'), 'folder', false, 'show_form_create_folder();', "class='sub'", true);
//		$table->data[1][1] .= html_print_button(__('Upload file/s'), 'up_files', false, 'show_upload_file();', "class='sub'", true);
//		$table->data[1][1] .= html_print_button(__('Create text file'), 'create_file', false, 'show_create_text_file();', "class='sub'", true);
//		$table->data[1][1] .= '</div>';
		
		$table->data[1][1] .= '<div id="create_folder" style="display: none;">';
		$table->data[1][1] .= html_print_button(__('Close'), 'close', false, 'show_main_buttons_folder();', "class='sub' style='float: left;'", true);
		$table->data[1][1] .= '<form method="post" action="' . $url . '">';
		$table->data[1][1] .= html_print_input_text ('dirname', '', '', 15, 255, true);
		$table->data[1][1] .= html_print_submit_button (__('Create'), 'crt', false, 'class="sub next"', true);
		$table->data[1][1] .= html_print_input_hidden ('directory', $relative_directory, true);
		$table->data[1][1] .= html_print_input_hidden ('create_dir', 1, true);
		$table->data[1][1] .= html_print_input_hidden('hash', md5($relative_directory . $config['dbpass']), true);
		$table->data[1][1] .= html_print_input_hidden('hash2', md5($relative_directory . $config['dbpass']), true);
		$table->data[1][1] .= '</form>';
		$table->data[1][1] .= '</div>';
		
		$table->data[1][1] .= '<div id="upload_file" style="display: none;">';
		$table->data[1][1] .= html_print_button(__('Close'), 'close', false, 'show_main_buttons_folder();', "class='sub' style='float: left;'", true);
		$table->data[1][1] .= '<form method="post" action="' . $url . '" enctype="multipart/form-data">';
		$table->data[1][1] .= ui_print_help_tip (__("The zip upload in this dir, easy to upload multiple files."), true);
		$table->data[1][1] .= html_print_input_file ('file', true, false);
		$table->data[1][1] .= html_print_checkbox('decompress', 1, false, true);
		$table->data[1][1] .= __('Decompress');
		$table->data[1][1] .= '&nbsp;&nbsp;&nbsp;';
		$table->data[1][1] .= html_print_submit_button (__('Go'), 'go', false, 'class="sub next"', true);
		$table->data[1][1] .= html_print_input_hidden ('real_directory', $real_directory, true);
		$table->data[1][1] .= html_print_input_hidden ('directory', $relative_directory, true);
		$table->data[1][1] .= html_print_input_hidden('hash', md5($real_directory . $relative_directory . $config['dbpass']), true);
		$table->data[1][1] .= html_print_input_hidden('hash2', md5($relative_directory . $config['dbpass']), true);
		$table->data[1][1] .= html_print_input_hidden ('upload_file_or_zip', 1, true);
		$table->data[1][1] .= '</form>';	
		$table->data[1][1] .= '</div>';
		
		$table->data[1][1] .= '<div id="create_text_file" style="display: none;">';
		$table->data[1][1] .= html_print_button(__('Close'), 'close', false, 'show_main_buttons_folder();', "class='sub' style='float: left;'", true);
		$table->data[1][1] .= '<form method="post" action="' . $url . '">';
		$table->data[1][1] .= html_print_input_text('name_file', '', '', 30, 50, true);
		$table->data[1][1] .= html_print_submit_button (__('Create'), 'create', false, 'class="sub"', true);
		$table->data[1][1] .= html_print_input_hidden ('real_directory', $real_directory, true);
		$table->data[1][1] .= html_print_input_hidden ('directory', $relative_directory, true);
		$table->data[1][1] .= html_print_input_hidden('hash', md5($real_directory . $relative_directory . $config['dbpass']), true);
		$table->data[1][1] .= html_print_input_hidden ('create_text_file', 1, true);
		$table->data[1][1] .= '</form>';	
		$table->data[1][1] .= '</div>';
		
		$table->colspan[1][1] =5;
	}
	
	foreach ($files as $fileinfo) {
		$data = array ();
		
		switch ($fileinfo['mime']) {
			case MIME_DIR:
				$data[0] = html_print_image ('images/mimetypes/directory.png', true);
				break;
			case MIME_IMAGE:
				$data[0] = html_print_image ('images/mimetypes/image.png', true);
				break;
			case MIME_ZIP:
				$data[0] = html_print_image ('images/mimetypes/zip.png', true);
				break;
			case MIME_TEXT:
				$data[0] = html_print_image ('images/mimetypes/text.png', true);
				break;
			default:
				$data[0] = html_print_image ('images/mimetypes/unknown.png', true);
				break;
		}
		
		if ($fileinfo['is_dir']) {
			$data[1] = '<a href="' . $url . '&directory='.$relative_directory.'/'.$fileinfo['name'].'&hash2=' . md5($relative_directory.'/'.$fileinfo['name'].$config['dbpass']) . '">'.$fileinfo['name'].'</a>';
		}
		else {
			$hash = md5($fileinfo['url'] . $config['dbpass']);
			$data[1] = '<a href="include/get_file.php?file='.base64_encode($fileinfo['url']).'&hash=' . $hash . '">'.$fileinfo['name'].'</a>';
		}
		$data[2] = ui_print_timestamp ($fileinfo['last_modified'], true,
			array ('prominent' => true));
		if ($fileinfo['is_dir']) {
			$data[3] = '';
		}
		else {
			$data[3] = ui_format_filesize ($fileinfo['size']);
		}
		
		//Actions buttons
		//Delete button
		$data[4] = '';
		$data[4] .= '<span style="">';
		if (is_writable ($fileinfo['realpath'])  &&
			(! is_dir ($fileinfo['realpath']) || count (scandir ($fileinfo['realpath'])) < 3)) {
			$data[4] .= '<form method="post" action="' . $url . '" style="display: inline;">';
			$data[4] .= '<input type="image" src="images/cross.png" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
			$data[4] .= html_print_input_hidden ('filename', $fileinfo['realpath'], true);
			$data[4] .= html_print_input_hidden('hash', md5($fileinfo['realpath'] . $config['dbpass']), true);
			$data[4] .= html_print_input_hidden ('delete_file', 1, true);

			$relative_dir = str_replace($config['homedir'], '', dirname($fileinfo['realpath']));
			if ($relative_dir[0] == '/') {
				$relative_dir = substr($relative_dir, 1);
			}
			$hash2 = md5($relative_dir . $config['dbpass']);

			$data[4] .= html_print_input_hidden ('directory', $relative_dir, true);
			$data[4] .= html_print_input_hidden ('hash2', $hash2, true);
			$data[4] .= '</form>';
			
			if (($editor) && (!$readOnly)) {
				if ($fileinfo['mime'] == MIME_TEXT) {
					$data[4] .= "<a style='vertical-align: top;' href='$url&edit_file=1&location_file=" . $fileinfo['realpath'] . "&hash=" . md5($fileinfo['realpath'] . $config['dbpass']) . "' style='float: left;'>" . html_print_image('images/edit.png', true, array("style" => 'margin-top: 2px;')) . "</a>";
				}
			}
		}
		$data[4] .= '</span>';
	
		array_push ($table->data, $data);
	}
	
	if (!$readOnly) {
		if (is_writable ($real_directory)) {
			echo "<div style='text-align: right; width: " . $table->width . ";'>";
			echo "<a href='javascript:show_form_create_folder();' style='margin-right: 3px;' title='" . __('Create directory') . "'>";
			echo html_print_image('images/mimetypes/directory.png', true); 
			echo "</a>";
			echo "<a href='javascript: show_create_text_file();' style='margin-right: 3px;' title='" . __('Create text') . "'>";
			echo html_print_image('images/mimetypes/text.png', true);
			echo "</a>";
			echo "<a href='javascript: show_upload_file();' title='" . __('Upload file/s') . "'>";
			echo html_print_image('images/mimetypes/unknown.png', true); 
			echo "</a>";
			echo "</div>";
		}
		else {
			echo "<div style='text-align: right; width: " . $table->width . "; color:#AC4444;'>";
			echo "<image src='images/info.png' />" . __('The directory is read-only');
			echo "</div>";
		}
	}
	html_print_table ($table);
}

/**
 * 
 * @param string $real_directory The string of dir as realpath.
 * @param string $relative_directory The string of dir as relative path.
 * @param string $url The url to set in the forms and some links in the explorer.
 */
function filemanager_box_upload_file_complex($real_directory, $relative_directory, $url = '') {
	global $config;
	
	$table->width = '100%';
	
	$table->data = array ();
	
	if (! filemanager_is_writable_dir ($real_directory)) {
		echo "<h3 class='error'>".__('Current directory is not writable by HTTP Server')."</h3>";
		echo '<p>';
		echo __('Please check that current directory has write rights for HTTP server');
		echo '</p>';
	} else {
		$table->data[1][0] = __('Upload') . ui_print_help_tip (__("The zip upload in this dir, easy to upload multiple files."), true);
		$table->data[1][1] = html_print_input_file ('file', true, false);
		$table->data[1][2] = html_print_radio_button('zip_or_file', 'zip', __('Multiple files zipped'), false, true);
		$table->data[1][3] = html_print_radio_button('zip_or_file', 'file', __('One'), true, true);
		$table->data[1][4] = html_print_submit_button (__('Go'), 'go', false,
			'class="sub next"', true);
		$table->data[1][4] .= html_print_input_hidden ('real_directory', $real_directory, true);
		$table->data[1][4] .= html_print_input_hidden ('directory', $relative_directory, true);
		$table->data[1][4] .= html_print_input_hidden('hash', md5($real_directory . $relative_directory . $config['dbpass']), true);
		$table->data[1][4] .= html_print_input_hidden ('upload_file_or_zip', 1, true);
	}
	
	echo '<form method="post" action="' . $url . '" enctype="multipart/form-data">';
	html_print_table ($table);
	echo '</form>';	
}

/**
 * Print the box of fields for upload file.
 * 
 * @param string $real_directory The string of dir as realpath.
 * @param string $relative_directory The string of dir as relative path.
 * @param string $url The url to set in the forms and some links in the explorer.
 */
function filemanager_box_upload_file_explorer($real_directory, $relative_directory, $url = '') {
	global $config;
	
	$table->width = '50%';
	
	$table->data = array ();
	
	if (! filemanager_is_writable_dir ($real_directory)) {
		echo "<h3 class='error'>".__('Current directory is not writable by HTTP Server')."</h3>";
		echo '<p>';
		echo __('Please check that current directory has write rights for HTTP server');
		echo '</p>';
	} else {
		$table->data[1][0] = __('Upload file');
		$table->data[1][1] = html_print_input_file ('file', true, false);
		$table->data[1][2] = html_print_submit_button (__('Go'), 'go', false,
			'class="sub next"', true);
		$table->data[1][2] .= html_print_input_hidden ('real_directory', $real_directory, true);
		$table->data[1][2] .= html_print_input_hidden ('directory', $relative_directory, true);
		$table->data[1][2] .= html_print_input_hidden('hash', md5($real_directory . $relative_directory . $config['dbpass']), true);
		$table->data[1][2] .= html_print_input_hidden ('upload_file', 1, true);
	}
	
	echo '<form method="post" action="' . $url . '" enctype="multipart/form-data">';
	html_print_table ($table);
	echo '</form>';
}

/**
 * Print the box of fields for upload file zip.
 * 
 * @param unknown_type $real_directory
 * @param unknown_type $relative_directory
 * @param string $url The url to set in the forms and some links in the explorer.
 */
function filemanager_box_upload_zip_explorer($real_directory, $relative_directory, $url = '') {
	global $config;
	
	$table->width = '60%';
	
	$table->data = array ();
	
	if (! filemanager_is_writable_dir ($real_directory)) {
		echo "<h3 class='error'>".__('Current directory is not writable by HTTP Server')."</h3>";
		echo '<p>';
		echo __('Please check that current directory has write rights for HTTP server');
		echo '</p>';
	} else {
		$table->data[1][0] = __('Upload zip file: ') . ui_print_help_tip (__("The zip upload in this dir, easy to upload multiple files."), true);
		$table->data[1][1] = html_print_input_file ('file', true, false);
		$table->data[1][2] = html_print_submit_button (__('Go'), 'go', false,
			'class="sub next"', true);
		$table->data[1][2] .= html_print_input_hidden ('real_directory', $real_directory, true);
		$table->data[1][2] .= html_print_input_hidden ('directory', $relative_directory, true);
		$table->data[1][2] .= html_print_input_hidden('hash', md5($real_directory . $relative_directory . $config['dbpass']), true);
		$table->data[1][2] .= html_print_input_hidden ('upload_zip', 1, true);
	}
	
	echo '<form method="post" action="' . $url . '" enctype="multipart/form-data">';
	html_print_table ($table);
	echo '</form>';
}

/**
 * Print the box of fields for create the text file.
 * 
 * @param unknown_type $real_directory
 * @param unknown_type $relative_directory
 * @param string $url The url to set in the forms and some links in the explorer.
 */
function filemanager_box_create_text_explorer($real_directory, $relative_directory, $url = '') {
	global $config;
	
	$table->width = '60%';
	
	$table->data = array ();
	
	if (! filemanager_is_writable_dir ($real_directory)) {
		echo "<h3 class='error'>".__('Current directory is not writable by HTTP Server')."</h3>";
		echo '<p>';
		echo __('Please check that current directory has write rights for HTTP server');
		echo '</p>';
	} else {
		$table->data[1][0] = __('Create text file: ');
		$table->data[1][1] = html_print_input_text('name_file', '', '', 30, 50, true);
		$table->data[1][2] = html_print_submit_button (__('Create'), 'create', false,
			'class="sub"', true);
		$table->data[1][2] .= html_print_input_hidden ('real_directory', $real_directory, true);
		$table->data[1][2] .= html_print_input_hidden ('directory', $relative_directory, true);
		$table->data[1][2] .= html_print_input_hidden('hash', md5($real_directory . $relative_directory . $config['dbpass']), true);
		$table->data[1][2] .= html_print_input_hidden ('create_text_file', 1, true);
	}
	
	echo '<form method="post" action="' . $url . '">';
	html_print_table ($table);
	echo '</form>';
}

/**
 * Get the available directories of the file manager.
 *
 * @return array An array with all the directories where the file manager can
 * operate.
 */
function filemanager_get_available_directories () {
	global $config;
	
	$dirs = array ();
	$dirs['images'] = "images";
	$dirs['attachment'] = "attachment";
	$dirs['languages'] = "include/languages";
	
	foreach ($dirs as $dirname) {
		$dirpath = realpath ($config['homedir'].'/'.$dirname);
		$dir = opendir ($dirpath);
		while ($file = @readdir ($dir)) {
			/* Ignore hidden files */
			if ($file[0] == '.')
				continue;
			$filepath = $dirpath.'/'.$file;
			if (is_dir ($filepath)) {
				$dirs[$dirname.'/'.$file] = $dirname.'/'.$file;
			}
		}
	}
	
	return $dirs;
}

/**
 * Check if a dirname is available for the file manager.
 *
 * @param string Dirname to check.
 * 
 * @return array An array with all the directories where the file manager can
 * operate.
 */
function filemanager_is_available_directory ($dirname) {
	$dirs = filemanager_get_available_directories ();
	
	return isset ($dirs[$dirname]);
}

/**
 * Check if a directory is writable.
 *
 * @param string Directory path to check.
 * @param bool If set, it will try to make the directory writeable if it's not.
 *
 * @param bool Wheter the directory is writeable or not.
 */
function filemanager_is_writable_dir ($dirpath, $force = false) {
	if (filemanager_is_available_directory (basename ($dirpath)))
		return is_writable ($dirpath);
	if (filemanager_is_writable_dir (realpath ($dirpath.'/..')))
		return true;
	else if (! $force)
			return is_writable ($dirpath);
	
	return (is_writable ($dirpath) || @chmod ($dirpath, 0755));
}

/**
 * Check if a directory is writable.
 *
 * @param string Directory path to check.
 * @param bool If set, it will try to make the directory writeable if it's not.
 *
 * @param bool Wheter the directory is writeable or not.
 */
function filemanager_get_file_info ($filepath) {
	global $config;
	
	$realpath = realpath ($filepath);
	
	$info = array ('mime' => MIME_UNKNOWN,
		'mime_extend' => mime_content_type ($filepath),
		'link' => 0,
		'is_dir' => false,
		'name' => basename ($realpath),
		'url' => str_replace('//', '/', $config['homeurl'].str_ireplace ($config['homedir'], '', $realpath)),
		'realpath' => $realpath,
		'size' => filesize ($realpath),
		'last_modified' => filemtime ($realpath)
	);
	
	$zip_mimes = array ('application/zip',
		'application/x-rar-compressed',
		'application/x-gzip',
		'application/x-bzip2');
	if (is_dir ($filepath)) {
		$info['mime'] = MIME_DIR;
		$info['is_dir'] = true;
		$info['size'] = 0;
	} else if (strpos ($info['mime_extend'], 'image') !== false) {
		$info['mime'] = MIME_IMAGE;
	} else if (in_array ($info['mime_extend'], $zip_mimes)) {
		$info['mime'] = MIME_ZIP;
	} else if (strpos ($info['mime_extend'], 'text') !== false) {
		$info['mime'] = MIME_TEXT;
	}
	
	return $info;
}

/**
 * Check if a directory is writable.
 *
 * @param string Directory path to check.
 * @param bool If set, it will try to make the directory writeable if it's not.
 *
 * @param bool Wheter the directory is writeable or not.
 */
function filemanager_list_dir ($dirpath) {
	$files = array ();
	$dirs = array ();
	$dir = opendir ($dirpath);
	while ($file = @readdir ($dir)) {
		/* Ignore hidden files */
		if ($file[0] == '.')
			continue;
		$info = filemanager_get_file_info ($dirpath.'/'.$file);
		if ($info['is_dir']) {
			$dirs[$file] = $info;
		} else {
			$files[$file] = $info;
		}
	}
	ksort ($files);
	ksort ($dirs);
	closedir ($dir);
	
	return array_merge ($dirs, $files);
}
?>
