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


// Load global vars
global $config;

check_login ();

// ACL for the general permission
$networkmaps_read = check_acl ($config['id_user'], 0, "MR");
$networkmaps_write = check_acl ($config['id_user'], 0, "MW");
$networkmaps_manage = check_acl ($config['id_user'], 0, "MM");

$id = (int)get_parameter('id_networkmap', 0);
$edit_networkmap = (int)get_parameter('edit_networkmap', 0);
$add_networkmap = (int)get_parameter('add_networkmap', 0);

if ($edit_networkmap) {
	$name = (string) get_parameter ('name');
	$description = (string) get_parameter ('description');
	$id_group = (int) get_parameter ('id_group');
	$type = (string) get_parameter ('type');

	if ($name == "") {
		$result = 0;
	}
	else {
		$result = maps_update_map ($id,
			array ('name' => $name,
				'id_group' => $id_group,
				'description' => $description),
				'type' => $type);
	}

	$info = ' Name: ' . $name . ' Description: ' . $description . ' ID group: ' . $id_group . ' Type: ' . $type;

	if ($id) {
		db_pandora_audit("Networkmap management", "Update networkmap #" . $id, false, false, $info);
	}
	else {
		db_pandora_audit("Networkmap management", "Fail to update networkmap #$id", false, false, $info);
	}

	ui_print_result_message ($result,
		__('Successfully updated'),
		__('Could not be updated'));
}
else if ($add_networkmap) {

}

?>
