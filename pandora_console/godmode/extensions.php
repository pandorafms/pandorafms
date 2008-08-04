<?php

// Pandora FMS - the Free monitoring system
// ========================================
// Copyright (c) 2004-2007 Sancho Lerena, slerena@gmail.com
// Main PHP/SQL code development and project architecture and management
// Copyright (c) 2005-2007 Artica Soluciones Tecnologicas, info@artica.es
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

if (! give_acl ($config['id_user'], 0, "AR") && ! dame_admin ($config['id_user'])) {
	audit_db ($id_usuario,$REMOTE_ADDR, "ACL Violation","Trying to access extensions list");
	include ("general/noaccess.php");
	exit;
}

if (sizeof ($config['extensions']) == 0) {
	echo '<h3>'.lang_string ('There are no extensions defined').'</h3>';
	return;
}

echo '<h2>'.lang_string ('Defined extensions')."</h2>";
$table->width = '95%';
$table->head = array ();
$table->head[0] = lang_string ('Name');
$table->data = array ();

foreach ($config['extensions'] as $extension) {
	if ($extension['godmode_function'] == '')
		continue;
	$data = array ();
	$data[0] = '<a href="index.php?sec=gextensions&sec2='.$menu['sec2'].'" class="mn">'.$menu['name'];
	array_push ($table->data, $data);
}

print_table ($table);
?>
