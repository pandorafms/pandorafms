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


function pandora_files_repo_install () {
	global $config;
	
	
	if (isset($config['files_repo_installed']) && $config['files_repo_installed'] == 1)
		return;
	
	$full_extensions_dir = $config['homedir']."/".EXTENSIONS_DIR."/";
	$full_sql_dir = $full_extensions_dir."files_repo/sql/";
	
	$file_path = '';
	switch ($config['dbtype']) {
		case "mysql":
			$file_path = $full_sql_dir . 'files_repo.sql';
			break;
		case "postgresql":
			$file_path = $full_sql_dir . 'files_repo.postgreSQL.sql';
			break;
		case "oracle":
			$file_path = $full_sql_dir . 'files_repo.oracle.sql';
			break;
	}
	
	if (!empty($file_path)) {
		$result = db_process_file($file_path);
		
		if ($result) {
			/* Configuration values */
			$values = array(
					"token" => "files_repo_installed",
					"value" => 1
				);
			db_process_sql_insert('tconfig', $values);
		}
	}
}

function pandora_files_repo_uninstall () {
	global $config;
	
	switch ($config["dbtype"]) {
		case "mysql":
			db_process_sql('DROP TABLE `tfiles_repo_group`');
			db_process_sql('DROP TABLE `tfiles_repo`');
			db_process_sql('DELETE FROM `tconfig`
				WHERE `token` LIKE "files_repo_%"');
			break;
		case "postgresql":
			db_process_sql('DROP TABLE "tfiles_repo_group"');
			db_process_sql('DROP TABLE "tfiles_repo"');
			db_process_sql('DELETE FROM "tconfig"
				WHERE "token" LIKE \'files_repo_%\'');
			break;
		case "oracle":
			db_process_sql('DROP TRIGGER "tfiles_repo_group_inc"');
			db_process_sql('DROP SEQUENCE "tfiles_repo_group_s"');
			db_process_sql('DROP TABLE "tfiles_repo_group"');
			db_process_sql('DROP TRIGGER "tfiles_repo_inc"');
			db_process_sql('DROP SEQUENCE "tfiles_repo_s"');
			db_process_sql('DROP TABLE "tfiles_repo"');
			db_process_sql('DELETE FROM tconfig
				WHERE token LIKE \'files_repo_%\'');
			break;
	}

	if (!empty($config['attachment_store']))
		delete_dir($config['attachment_store']."/files_repo");
}

function pandora_files_repo_godmode () {
	global $config;
	
	if (!isset($config['files_repo_installed']) || !$config['files_repo_installed']) {
		ui_print_error_message(__('Extension not installed'));
	}
	
	// ACL Check
	check_login ();
	if (! check_acl ($config['id_user'], 0, "PM")) {
		db_pandora_audit("ACL Violation", "Trying to access to Files repository");
		require ("general/noaccess.php");
		return;
	}
	
	// Header tabs
	$godmode['text'] = '<a href="index.php?sec=godmode/extensions&sec2=extensions/files_repo">'
			. html_print_image ("images/setup.png", true, array ("title" => __('Administration view')))
			. "</a>";
	$godmode['godmode'] = 1;
	$godmode['active'] = 1;
	
	$operation['text'] = '<a href="index.php?sec=extensions&sec2=extensions/files_repo">'
			. html_print_image ("images/operation.png", true, array ("title" => __('Operation view')))
			. "</a>";
	$operation['operation'] = 1;
	
	$onheader = array('godmode' => $godmode, 'operation' => $operation);
	// Header
	ui_print_page_header (__("Files repository manager"), "images/extensions.png", false, "", true, $onheader);
	
	$full_extensions_dir = $config['homedir']."/".EXTENSIONS_DIR."/";
	require_once ($full_extensions_dir . "files_repo/functions_files_repo.php");
	
	// Directory files_repo check
	if (!files_repo_check_directory(true)) {
		return;
	}
	
	$server_content_length = 0;
	if (isset($_SERVER['CONTENT_LENGTH']))
		$server_content_length =  $_SERVER['CONTENT_LENGTH'];
	
	// Check for an anoying error that causes the $_POST and $_FILES arrays
	// were empty if the file is larger than the post_max_size
	if (intval($server_content_length) > 0 && empty($_POST)) {
		ui_print_error_message(__('The file exceeds the maximum size'));
	}
	
	// GET and POST parameters
	$file_id = (int) get_parameter ("file_id");
	$add_file = (bool) get_parameter ("add_file");
	$update_file = (bool) get_parameter ("update_file");
	$delete_file = (bool) get_parameter ("delete");
	
	// File add or update
	if ( $add_file || ($update_file && $file_id > 0) ) {
		$groups = get_parameter ("groups", array());
		$public = (bool) get_parameter ("public");
		$description = io_safe_output((string) get_parameter ("description"));
		if (mb_strlen($description, "UTF-8") > 200) {
			$description = mb_substr($description, 0, 200, "UTF-8");
		}
		$description = io_safe_input($description);
		
		if ($add_file) {
			$result = files_repo_add_file("upfile", $description, $groups, $public);
		} elseif ($update_file) {
			$result = files_repo_update_file($file_id, $description, $groups, $public);
			$file_id = 0;
		}
		if ($result['status'] == false) {
			ui_print_error_message($result['message']);
		}
	}
	// File delete
	if ($delete_file && $file_id > 0) {
		$result = files_repo_delete_file($file_id);
		if ($result !== -1) {
			ui_print_result_message($result, __('Successfully deleted'), __('Could not be deleted'));
		}
		$file_id = 0;
	}
	
	// FORM
	require ($full_extensions_dir."files_repo/files_repo_form.php");
	if (!$file_id) {
		// LIST
		$manage = true;
		require ($full_extensions_dir."files_repo/files_repo_list.php");
	}
}

function pandora_files_repo_operation () {
	global $config;
	
	// Header tabs
	$onheader = array();
	if (check_acl($config['id_user'], 0, "PM")) {
		$godmode['text'] = '<a href="index.php?sec=godmode/extensions&sec2=extensions/files_repo">'
				. html_print_image ("images/setup.png", true, array ("title" => __('Administration view')))
				. "</a>";
		$godmode['godmode'] = 1;
			
		$operation['text'] = '<a href="index.php?sec=extensions&sec2=extensions/files_repo">'
				. html_print_image ("images/operation.png", true, array ("title" => __('Operation view')))
				. "</a>";
		$operation['operation'] = 1;
		$operation['active'] = 1;
		
		$onheader = array('godmode' => $godmode, 'operation' => $operation);
	}
	// Header
	ui_print_page_header (__("Files repository"), "images/extensions.png", false, "", false, $onheader);
	
	$full_extensions_dir = $config['homedir']."/".EXTENSIONS_DIR."/";
	require_once ($full_extensions_dir."files_repo/functions_files_repo.php");
	
	// Directory files_repo check
	if (!files_repo_check_directory(true)) {
		return;
	}
	
	// LIST
	$full_extensions_dir = $config['homedir']."/".EXTENSIONS_DIR."/";
	
	require ($full_extensions_dir . "files_repo/files_repo_list.php");
}

extensions_add_operation_menu_option(__('Files repository'), null, null, "v1r1");
extensions_add_main_function('pandora_files_repo_operation');
extensions_add_godmode_menu_option(__('Files repository manager'), 'PM', null, null, "v1r1");
extensions_add_godmode_function('pandora_files_repo_godmode');

//pandora_files_repo_uninstall();
pandora_files_repo_install();

?>
