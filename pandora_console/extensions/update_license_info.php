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



if (! check_acl ($config['id_user'], 0, 'PM')) {
	db_pandora_audit("ACL Violation", "Trying to use Open Update Manager extension");
	include ("general/noaccess.php");
	return;
}

function update_manage_license_info_main() {
	update_manage_license_info();
}

function update_manage_license_info(){

	ui_print_page_header (__('Update manager').' - '. __('License info'), "images/extensions.png", false, "", true, "" );

	global $config;
	include_once("include/functions_db.php");

	$is_enterprise = enterprise_include_once('include/functions_license.php');

	enterprise_hook('license_show_info');
}

if (defined('PANDORA_ENTERPRISE')) {
	extensions_add_godmode_menu_option (__('Update manager license info'), 'PM');
	extensions_add_godmode_function ('update_manage_license_info_main');
}
