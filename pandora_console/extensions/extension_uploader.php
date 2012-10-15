<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

function extension_uploader_extensions() {
	global $config;
	
	if (!check_acl($config['id_user'], 0, "PM")) {
		db_pandora_audit("ACL Violation",
			"Trying to access Group Management");
		require ("general/noaccess.php");
		
		return;
	}
	
	ui_print_page_header (__("Uploader extension"),
		"images/extensions.png", false, "", true, "");
	
	$upload = (bool)get_parameter('upload', 0);
	$upload_enteprise = (bool)get_parameter('upload_enterprise', 0);
	
	if ($upload) {
		$error = $_FILES['extension']['error'];
		
		if ($error == 0) {
			$zip = new ZipArchive;
			
			$tmpName = $_FILES['extension']['tmp_name'];
			
			if ($upload_enteprise) {
				$pathname = $config['homedir'] . '/' . ENTERPRISE_DIR . '/' . EXTENSIONS_DIR . '/';
			}
			else {
				$pathname = $config['homedir'] . '/' . EXTENSIONS_DIR . '/';
			}
			
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
			db_pandora_audit ("Extension manager", "Upload extension " . $_FILES['extension']['name']);
		}
		
		ui_print_result_message ($result, __('Success to upload extension'),
			__('Fail to upload extension'));
	}
	
	$table = null;
	
	$table->width = '98%';
	$table->data = array();
	$table->data[0][0] = __('Upload extension');
	$table->data[0][1] = html_print_input_file('extension', true) .
		ui_print_help_tip (__("Upload the extension as a zip file."), true);
	if (enterprise_installed()) {
		$table->data[0][2] = __('Upload enterprise extension') . "&nbsp;" .
			html_print_checkbox('upload_enterprise', 1, false, true);
	}
	
	echo "<form method='post' enctype='multipart/form-data'>";
	html_print_table($table);
	echo "<div style='text-align: right; width: " . $table->width . "'>";
	html_print_input_hidden('upload', 1);
	html_print_submit_button(__('Upload'), 'submit', false, 'class="sub add"');
	echo "</div>";
	echo "</form>";
}

extensions_add_godmode_menu_option(__('Extension uploader'), 'AM', null, null, "v1r1");
extensions_add_godmode_function('extension_uploader_extensions');
?>
