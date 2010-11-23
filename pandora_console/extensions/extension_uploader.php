<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

function extension_uploader_extensions() {
	global $config;
	
	print_page_header (__("Uploader extension"), "images/extensions.png", false, "", true, "");

	$upload = (bool)get_parameter('upload', 0);
	
	if ($upload) {
		$error = $_FILES['extension']['error'];
		
		if ($error == 0) {			
			$zip = new ZipArchive;
			
			$tmpName = $_FILES['extension']['tmp_name'];
			
			$pathname = $config['homedir'] . '/' . EXTENSIONS_DIR . '/';
			
			if ($zip->open($tmpName) === true) {
				$result = $zip->extractTo($pathname);
			}
			else {
				$result = false;
			}
		}
		else {
			$result = false;
		}
		
		if ($result) {
			pandora_audit ("Extension manager", "Upload extension " . $_FILES['extension']['name']);
		}
		
		print_result_message ($result, __('Success to upload extension'),
			__('Fail to upload extension'));
	}

	$table = null;
	
	$table->width = '50%';
	$table->data = array();
	$table->data[0][0] = __('Upload extension');
	$table->data[0][1] = print_input_file('extension', true) .
		print_help_tip (__("Upload the extension as a zip file."), true);
	
	echo "<form method='post' enctype='multipart/form-data'>";
	print_table($table);
	echo "<div style='text-align: right; width: " . $table->width . "'>";
    print_input_hidden('upload', 1);
    print_submit_button(__('Upload'), 'submit', false, 'class="sub add"');
    echo "</div>";
	echo "</form>";
}

add_godmode_menu_option(__('Extension uploader'), 'AM', 'gextensions');
add_extension_godmode_function('extension_uploader_extensions');
?>
